<?php

use hpn\wms\model\ScreeningTestModel;

class EvaluateScreeningTestResultComplianceView extends DateBasedComplianceView
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return $user->hasCredential(Attribute::VIEW_PHI);
    }

    public function __construct(ComplianceScreeningModel $screeningModel, $testName, $startDate, $endDate, $alias = 'default')
    {
        $this->screeningModel = $screeningModel;

        $this->setDateRange($startDate, $endDate);

        $this->testName = $testName;

        $this->requiredTests[] = $testName;

        $this->screeningTestModel = ScreeningTestModel::instance($alias);
    }

    public function allowPointsOverride()
    {
        return $this->allowPointsOverride === null ?
            parent::allowPointsOverride() : $this->allowPointsOverride;
    }

    public function setAllowPointsOverride($boolean)
    {
        $this->allowPointsOverride = $boolean;
    }

    public function getTestAbbreviation()
    {
        $activeUser = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();

        $screeningTest = $activeUser && $activeUser->gender ? $this->screeningTestModel->getScreeningTest($this->testName, $this->getMatchesForUser($activeUser)) : null;

        return $screeningTest && $screeningTest['abbreviation'] ? $screeningTest['abbreviation'] : $this->testName;
    }

    public function getTestName()
    {
        return $this->testName;
    }

    public function getTestTitle()
    {
        $activeUser = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();

        $screeningTest = $activeUser && $activeUser->gender ? $this->screeningTestModel->getScreeningTest($this->testName, $this->getMatchesForUser($activeUser)) : null;

        return $screeningTest && $screeningTest['title'] ? $screeningTest['title'] : $this->testName;
    }

    public function getDefaultName()
    {
        return "comply_with_screening_test_{$this->testName}";
    }

    public function getDefaultReportName()
    {
        return $this->getTestTitle();
    }

    public function getDefaultStatusSummary($status)
    {
        $statusMap = array_flip($this->statusMap);

        if(!isset($statusMap[$status])) {
            return null;
        }

        $activeUser = $this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser();

        $screeningTestRange = $this->screeningTestModel->findScreeningTestRangeForStatus($this->testName, $statusMap[$status], $this->getMatchesForUser($activeUser));

        return $screeningTestRange ? $screeningTestRange['range_text'] : null;
    }

    public function getStatus(User $user)
    {
        // We save the most recent calculated status incase the same user is
        // called multiple times - could be common for situations where views
        // depend on other ones.

        if($this->lastStatus && $this->lastStatusUserId && $this->lastStatusUserId == $user->id) {
            return $this->lastStatus;
        } else {
            $status = $this->_getStatus($user);

            $this->lastStatus = $status;
            $this->lastStatusUserId = $user->id;

            return $status;
        }
    }

    /**
     * Sets the date that is used to calculate the user's date of birth.
     *
     * If null, this view's start date is used instead.
     *
     * @param DateTime $date
     */
    public function setDateOfBirthCalculationDate(\DateTime $date = null)
    {
        $this->dateOfBirthCalculationDate = $date;
    }

    public function setNoGenderStatus($status, $points, $comment)
    {
        $this->noGenderStatus = array($status, $points, $comment);
    }

    public function setNoScreeningStatus($status, $points, $comment)
    {
        $this->noScreeningStatus = array($status, $points, $comment);
    }

    public function setNoTestResultStatus($status, $points, $comment)
    {
        $this->noTestResultStatus = array($status, $points, $comment);
    }

    public function setNoTestRowStatus($status, $points, $comment)
    {
        $this->noTestRowStatus = array($status, $points, $comment);
    }

    /**
     * Sets the required tests for this view. This defaults to the view that
     * this test is for but may be overridden by calling this method.
     *
     * @param array $tests
     * @param bool $testMissing If true, test missing status will be used. If false, screening missing status will be used.
     */
    public function setRequiredTests(array $tests, $testMissing = true)
    {
        $this->requiredTests = $tests;
        $this->requiredTestsTestMissing = $testMissing;
    }

    protected function getMatchesForUser(User $user)
    {
        $row = $this->getScreeningRow($user, $this->getStartDateTime(), $this->getEndDateTime());

        // @TODO Fix default screening lab being hardcoded here

        $screeningLabId = $row ? $row['labid'] : 1;

        $dobCalculationDate = $this->dateOfBirthCalculationDate !== null ?
            $this->dateOfBirthCalculationDate->format('U') : $this->getStartDate();

        return array(
            'age'              => $user->getAge($dobCalculationDate, true),
            'gender'           => $user->gender,
            'client_id'        => $user->client_id,
            'screening_lab_id' => $screeningLabId
        );
    }

    protected function getScreeningRow(User $user, \DateTime $start, \DateTime $end)
    {
        return $this->screeningModel->getScreeningRow($user, $this->getStartDateTime(), $this->getEndDateTime());
    }

    protected function _getStatus(User $user)
    {
        if(!$user->gender) {
            $status = new ComplianceViewStatus($this, $this->noGenderStatus[0], $this->noGenderStatus[1], $this->noGenderStatus[2]);
            $status->setAttribute('has_result', false);

            return $status;
        }

        $row = $this->getScreeningRow($user, $this->getStartDateTime(), $this->getEndDateTime());

        if(!$row) {
            $status = new ComplianceViewStatus($this, $this->noScreeningStatus[0], $this->noScreeningStatus[1], $this->noScreeningStatus[2]);
            $status->setAttribute('has_result', false);

            return $status;
        }

        foreach($this->requiredTests as $requiredTest) {
            if(!isset($row[$requiredTest]) || $row[$requiredTest] === null || trim($row[$requiredTest]) === '' || trim($row[$requiredTest]) === '0') {
                $noTestStatus = $this->requiredTestsTestMissing ? $this->noTestResultStatus : $this->noScreeningStatus;

                $status = new ComplianceViewStatus($this, $noTestStatus[0], $noTestStatus[1], $noTestStatus[2]);
                $status->setAttribute('has_result', false);

                return $status;
            }
        }

        $testResults = $this->screeningTestModel->evaluateResults($row, $this->getMatchesForUser($user), array($this->testName));

        if(!isset($testResults[$this->testName])) {
            $status = new ComplianceViewStatus($this, $this->noTestRowStatus[0], $this->noTestRowStatus[1], $this->noTestRowStatus[2]);
            $status->setAttribute('has_result', false);

            return $status;
        }

        $testResult = $testResults[$this->testName];

        $status = new ComplianceViewStatus(
            $this,
            isset($this->statusMap[$testResult['status']]) ? $this->statusMap[$testResult['status']] : null,
            $testResult['points'],
            $testResult['result']
        );

        $status->setAttribute('has_result', true);

        return $status;
    }

    protected $noGenderStatus = array(ComplianceStatus::NOT_COMPLIANT, null, 'Unknown Gender');
    protected $noTestRowStatus = array(ComplianceStatus::NOT_COMPLIANT, null, 'Test Not Configured');
    protected $noTestResultStatus = array(ComplianceStatus::NOT_COMPLIANT, null, 'Test Not Taken');
    protected $noScreeningStatus = array(ComplianceStatus::NOT_COMPLIANT, null, 'No Screening');

    protected $requiredTests = array();

    protected $requiredTestsTestMissing = true;

    protected $statusMap = array(
        ScreeningTestModel::STATUS_GOOD       => ComplianceStatus::COMPLIANT,
        ScreeningTestModel::STATUS_BORDERLINE => ComplianceStatus::PARTIALLY_COMPLIANT,
        ScreeningTestModel::STATUS_RISK       => ComplianceStatus::NOT_COMPLIANT
    );

    protected $screeningModel;
    protected $screeningTestModel;
    protected $testName;
    protected $lastStatus;
    protected $lastStatusUserId;
    protected $dateOfBirthCalculationDate;
    private $allowPointsOverride = null;
}