<?php

abstract class ComplyWithScreeningTestComplianceView extends DateBasedComplianceView
{
    const NO_SCREENING_TEXT = 'No Screening';
    const TEST_NOT_TAKEN_TEXT = 'Test Not Taken';

    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct($startDate, $endDate, $screening = null)
    {
        $this->setStartDate($startDate)->setEndDate($endDate);
        $this->testRows = array();

        if($screening !== null) {
            $this->passedScreening = $screening;
        }
    }

    public abstract function getTestName();

    public function getDefaultName()
    {
        return 'comply_with_screening_test_'.$this->getTestName();
    }

    public function getDefaultReportName()
    {
        return $this->getTestName();
    }

    public function getDefaultStatusSummary($status)
    {
        $mapping = array();

        $activeUser = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();
        $testName = $this->getTestName();
        $userGender = $activeUser->getGender();


        $testRow = $this->getTestRow($activeUser, $testName);

        if($testRow === null) {
            return null;
        } else {
            $haveLow = $testRow['low'] !== null && $testRow['low'] > 0;
            $haveHigh = $testRow['high'] !== null && $testRow['high'] > 0;
            $haveRiskLow = $testRow['risklow'] !== null && $testRow['risklow'] > 0;
            $haveRiskHigh = $testRow['riskhigh'] !== null && $testRow['riskhigh'] > 0;
            $havePanicLow = $testRow['paniclow'] !== null && $testRow['paniclow'] > 0;
            $havePanicHigh = $testRow['panichigh'] !== null && $testRow['panichigh'] > 0;

            if($status === ComplianceStatus::COMPLIANT) {
                if($haveLow && $haveHigh) {
                    return "{$testRow['low']}-{$testRow['high']}";
                } else if($haveLow) {
                    return "≥{$testRow['low']}";
                } else if($haveHigh) {
                    return "≤{$testRow['high']}";
                } else {
                    return null;
                }
            } else if($status === ComplianceStatus::PARTIALLY_COMPLIANT) {
                $not = $this->getStatusSummary(ComplianceStatus::NOT_COMPLIANT);
                if(!$this->getComplianceViewGroup()->getComplianceProgram()->hasPartiallyCompliantStatus()) {
                    return null;
                } else if($haveLow && $haveHigh && $haveRiskHigh && $haveRiskLow && $testRow['low'] == $testRow['risklow'] && $testRow['high'] == $testRow['riskhigh']) {
                    // If low=risklow and high=riskhigh, then we dont have a yellow...its red instead
                    return null;
                } else if($not) {
                    if($haveLow && $haveHigh) {
                        return "<{$testRow['low']}&>{$testRow['high']}";
                    } else if($haveLow) {
                        return "<{$testRow['low']}";
                    } else if($haveHigh) {
                        return ">{$testRow['high']}";
                    } else {
                        return null;
                    }
                } else {

                }
            } else if($status === ComplianceStatus::NOT_COMPLIANT) {
                if(!$this->getComplianceViewGroup()->getComplianceProgram()->hasPartiallyCompliantStatus()) {
                    if($haveLow && $haveHigh) {
                        return "<{$testRow['low']}&>{$testRow['high']}";
                    } else if($haveLow) {
                        return "<{$testRow['low']}";
                    } else if($haveHigh) {
                        return ">{$testRow['high']}";
                    }
                }

                if($haveRiskLow && $haveRiskHigh) {
                    return "<{$testRow['risklow']}&>{$testRow['riskhigh']}";
                } else if($haveRiskLow) {
                    return "<{$testRow['risklow']}";
                } else if($haveRiskHigh) {
                    return ">{$testRow['riskhigh']}";
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }
    }

    public function getStatus(User $user)
    {
        $testName = $this->getTestName();

        $screeningRow = $this->getScreeningRow($user, $this->getFields());
        $testRow = $this->getTestRow($user, $testName, $screeningRow);

        $noScreeningRow = $screeningRow === null;
        $noTestValue = $noScreeningRow || !isset($screeningRow[$testName]) || $screeningRow[$testName] === null || trim($screeningRow[$testName]) === '' || trim("{$screeningRow[$testName]}") === "0";
        $usingSelfReport = false;

        if($testRow === null) {
            return new ComplianceViewStatus($this, $this->noTestResultStatus);
        } else if($this->allowNumericOnly && !is_numeric($screeningRow[$testName])) {
            return new ComplianceViewStatus($this, $this->noTestResultStatus, null, self::NO_SCREENING_TEXT);
        } else {
            $useRawFallback = $this->useRawFallbackQuestionValue();

            if($this->useHraFallback && $useRawFallback && $noTestValue) {
                $rawValue = $this->computeFallbackHraStatus($user);

                if($rawValue !== false) {
                    $screeningRow = array(
                        'labid' => $testRow['labid'],
                        'date'  => $rawValue['date'],
                        $testName => $rawValue['value']
                    );

                    $noScreeningRow = false;
                    $noTestValue = false;
                    $usingSelfReport = true;
                }
            }

            if($noScreeningRow) {
                $noScreeningStatus = new ComplianceViewStatus($this, $this->noScreeningResultStatus, null, $this->useDate ? null : self::NO_SCREENING_TEXT);;

                if($this->useHraFallback && !$useRawFallback) {
                    if($fallBackStatus = $this->computeFallbackHraStatus($user)) {
                        return $fallBackStatus;
                    } else {
                        return $noScreeningStatus;
                    }
                } else {
                    return $noScreeningStatus;
                }
            } else if($noTestValue) {
                $noTestStatus = new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT, null, $this->useDate ? null : self::TEST_NOT_TAKEN_TEXT);

                if($this->useHraFallback && !$useRawFallback) {
                    if($fallBackStatus = $this->computeFallbackHraStatus($user)) {
                        return $fallBackStatus;
                    } else {
                        return $noTestStatus;
                    }
                } else {
                    return $noTestStatus;
                }
            } else {


                $haveLow = $testRow['low'] !== null && $testRow['low'] > 0;
                $haveHigh = $testRow['high'] !== null && $testRow['high'] > 0;
                $haveRiskLow = $testRow['risklow'] !== null && $testRow['risklow'] > 0;
                $haveRiskHigh = $testRow['riskhigh'] !== null && $testRow['riskhigh'] > 0;
                $havePanicLow = $testRow['paniclow'] !== null && $testRow['paniclow'] > 0;
                $havePanicHigh = $testRow['panichigh'] !== null && $testRow['panichigh'] > 0;

                $mappableTestValue = trim($screeningRow[$testName]);

                if(!is_numeric($mappableTestValue)) {
                    $mappableTestValue = strtolower($mappableTestValue);
                }

                if(isset($this->resultMappings[$mappableTestValue])) {
                    $comment = isset($this->resultMappingText[$mappableTestValue]) ?
                        $this->resultMappingText[$mappableTestValue] : $mappableTestValue;

                    $comment = $this->useDate ?
                        date('m/d/Y', strtotime($screeningRow['date'])) : $comment;

                    $status = new ComplianceViewStatus(
                        $this,
                        $this->resultMappings[$mappableTestValue],
                        null,
                        $comment
                    );

                    $status->setAttribute('date', date('m/d/Y', strtotime($screeningRow['date'])));

                    $status->setAttribute('has_result', true);

                    $status->setAttribute('real_result', $screeningRow[$testName]);

                    return $status;
                }

                $testValue = round($screeningRow[$testName], 2);

                $comment = $this->useDate ?
                    date('m/d/Y', strtotime($screeningRow['date'])) : $testValue;

                if($usingSelfReport && !$this->useDate && $this->indicateSr) {
                    $comment = "(SR) $testValue";
                }

                if($haveRiskLow && $testValue < $testRow['risklow']) {
                    // Not compliant
                    $status = new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT, null, $comment);
                } else if($haveRiskHigh && $testValue > $testRow['riskhigh']) {
                    $status = new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT, null, $comment);
                } else if(($haveLow && $testValue < $testRow['low']) || ($haveHigh && $testValue > $testRow['high'])) {
                    // Paritally compliant or not compliant

                    $giveRed = false;

                    if($testValue < $testRow['low'] && $testRow['low'] == $testRow['risklow']) {
                        $giveRed = true;
                    } else if($testValue > $testRow['high'] && $testRow['high'] == $testRow['riskhigh']) {
                        $giveRed = true;


                    }

                    if($giveRed) {
                        $status = new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT, null, $comment);
                    } else {
                        $status = new ComplianceViewStatus($this, ComplianceViewStatus::PARTIALLY_COMPLIANT, null, $comment);
                    }
                } else {
                    // Compliant
                    $status = new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, $comment);
                }

                $status->setAttribute('date', date('m/d/Y', strtotime($screeningRow['date'])));

                $status->setAttribute('has_result', true);

                $status->setAttribute('real_result', $screeningRow[$testName]);

                return $status;
            }
        }
    }

    /**
     * Adds a result mapping. This maps a specific result text to a status, bypassing
     * any tests checking, etc. Note that the result is not numeric, comparisons
     * will be done using lowercase on both the actual test result and the given
     * $result parameter.
     *
     * @param string $result
     * @param int $status
     * @return ComplyWithScreeningTestComplianceView This instance
     */
    public function addResultMapping($result, $status, $comment = null)
    {
        $key = is_numeric($result) ? $result : strtolower($result);

        $this->resultMappings[$key] = $status;
        $this->resultMappingText[$key] = $comment;

        return $status;
    }

    public function isResultMapping($result)
    {
        return isset($this->resultMappings[is_numeric($result) ? $result : strtolower($result)]);
    }

    public function setIndicateSelfReportedResults($bool)
    {
        $this->indicateSr = $bool;
    }

    /**
     * If true, all screenings will be merged before evaluating. This way, one
     * test on record A and a different test on record B would be merged together
     * supposing that record A doesnt have the given test on record B.
     *
     * @param boolean $boolean
     */
    public function setMergeScreenings($boolean)
    {
        $this->mergeScreenings = $boolean;
    }

    public function setNoTestResultStatus($status)
    {
        $this->noTestResultStatus = $status;

        return $this;
    }

    public function setNoScreeningResultStatus($status)
    {
        $this->noScreeningResultStatus = $status;

        return $this;
    }

    public function setUseDateForComment($boolean)
    {
        $this->useDate = $boolean;
    }

    public function setUseHraFallback($boolean)
    {
        $this->useHraFallback = $boolean;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function setAllowNumericOnly($boolean)
    {
        $this->allowNumericOnly = $boolean;
    }

    public function overrideTestRowData($riskLow, $low, $high, $riskHigh, $gender = 'E')
    {
        $this->testRows[$gender] = array(
            'gender'    => $gender,
            'risklow'   => $riskLow,
            'low'       => $low,
            'high'      => $high,
            'riskhigh'  => $riskHigh,
            'agelow'    => 0,
            'agehigh'   => 999,
            'labid'     => 1,
            'paniclow'  => -2,
            'panichigh' => -1
        );
    }

    private function getTestRow(User $user, $fieldName, $screeningRow = null)
    {
        if(isset($this->testRows[$user->getGender()])) {
            return $this->testRows[$user->getGender()];
        } else if(isset($this->testRows['E'])) {
            return $this->testRows['E'];
        } else {
            $userAge = $user->getAge($this->getStartDate(), true);
            $userGender = $user->getGender();

            // Default to 1 if no screening
            if($screeningRow === null) {
                $screeningRow = $this->getScreeningRow($user, array('date', 'labid'));
            }

            $labID = $screeningRow === null ? '1' : $screeningRow['labid'];

            $testQuery = "
                SELECT *
                FROM tests
                WHERE field = ?
                AND labid = ?
                AND (gender = 'E' OR gender = ?)
                AND ? BETWEEN agelow AND agehigh
                AND (client_id IS NULL OR client_id = ?) 
                AND (state IS NULL OR state = ?)   
                ORDER BY client_id DESC, state DESC
                LIMIT 1
            ";
            $_db = Piranha::getInstance()->getDatabase();
            $_db->executeSelect($testQuery, $fieldName, $labID, $userGender, $userAge, $user->client_id, $user->state);
            $testRow = $_db->getNextRow();
            if($testRow === false) {
                return null;
            } else {
                return $testRow;
            }
        }
    }

    protected function computeFallbackHraStatus(User $user)
    {
        if(!($questionId = $this->getFallbackQuestionId())) {
            return false;
        }

        $hraView = new ComplyWithArbitraryHraQuestionComplianceView(
            $this->getStartDateGetter(),
            $this->getEndDateGetter(),
            $questionId
        );

        $hraView->setComplianceViewGroup($this->getComplianceViewGroup());

        $hraQuestionStatus = $hraView->getStatus($user);

        $status = $hraQuestionStatus->getStatus();
        $comment = $hraQuestionStatus->getComment();

        if($this->useRawFallbackQuestionValue()) {
            if(is_numeric($comment) && $comment != '-1') {
                return array(
                    'value' => $comment,
                    'date'  => $hraQuestionStatus->getAttribute('date')
                );
            } else {
                return false;
            }

        } elseif($status != ComplianceStatus::NA_COMPLIANT && strpos($comment, 'N/A') === false && $comment !== null && $comment != '-1') {
            $returnComment = $this->indicateSr ? "(SR) $comment" : $comment;

            return new ComplianceViewStatus($this, $status, null, $returnComment);
        } else {
            return false;
        }
    }

    protected function useRawFallbackQuestionValue()
    {
        return false;
    }

    protected function getFallbackQuestionId()
    {
        return false;
    }

    protected function getFilter()
    {
        return $this->filter !== null ?
            $this->filter : function(array $screening) { return true; };
    }

    protected function getFields()
    {
        $testName = $this->getTestName();

        return $this->fields !== null ?
            $this->fields : array($testName, 'labid', 'date');
    }

    protected function getScreeningRow(User $user, $fields)
    {
        if($this->passedScreening !== null) {
            return $this->passedScreening ? $this->passedScreening->toArray() : null;
        }

        $screening = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
            $user,
            new DateTime($this->getStartDate('Y-m-d')),
            new DateTime($this->getEndDate('Y-m-d')),
            array(
                'fields'           => $fields,
                'merge'            => $this->mergeScreenings,
                'require_complete' => false,
                'filter'           => $this->getFilter()
            )
        );

        if($screening) {
            return $screening;
        } else {
            return null;
        }
    }

    protected $filter = null;
    protected $fields = null;
    protected $indicateSr = true;
    protected $useHraFallback = false;
    protected $useDate = false;
    protected $mergeScreenings = true;
    protected $resultMappings = array();
    protected $resultMappingText = array();
    protected $noTestResultStatus = ComplianceStatus::NA_COMPLIANT;
    protected $noScreeningResultStatus = ComplianceStatus::NOT_COMPLIANT;
    protected $passedScreening = null;
    protected $allowNumericOnly = false;
}