<?php

use hpn\steel\query\SelectQuery;

class NSK2020CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter(),
                'required_fields'  => array('systolic', 'diastolic', 'triglycerides', 'hdl', 'bodyfat', 'cholesterol', 'glucose')
            )
        );

        return $data;
    }
}

class NSK2020ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new NSK2020WMS2Printer();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserFields(true, true, false, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);

        $printer->addCallbackField('hiredate', function(User $user) {
            return $user->getHiredate();
        });

        $printer->addCallbackField('location', function(User $user) {
            return $user->getLocation();
        });

        $printer->addCallbackField('employee_ssn', function (User $user) {
            if($user->getRelationshipType() == 2) {
                return $user->getRelationshipUser() ? $user->getRelationshipUser()->getSocialSecurityNumber() : '';
            } else {
                return $user->getSocialSecurityNumber();
            }
        });

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = array();

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($groupStatus->getComplianceViewGroup()->getName() == 'Requirements') {
                        $data[sprintf('%s Result', $viewName)] = $viewStatus->getComment();
                    } elseif($groupStatus->getComplianceViewGroup()->getName() == 'Prevention Event') {
                        $data[sprintf('%s Comment', $viewName)] = $viewStatus->getComment();
                    }

                    $data[sprintf('%s Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                }
            }

            $data['Ticker Countdown'] = $this->getTickerCountdown($status) != 0 ? $this->getTickerCountdown($status) : '';
            $data['Phone Consultation Showed'] = $this->getPhoneConsultShowed($user);
            $data['Total Points'] = $status->getPoints();
            $data['Total Points (Ignoring Alternatives)'] = $status->getAttribute('total_points_ignoring_alternatives');
            $data['Total Points (Last Year)'] = $status->getAttribute('prior_status')->getPoints();
            $data['Point Difference'] = $data['Total Points'] - $data['Total Points (Last Year)'];
            $data['Monthly Surcharge'] = $status->getComment();
            $data['Has Biometric Override'] = $status->getAttribute('has_biometric_override') ? 'Yes' : 'No';
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    protected function getTickerCountdown($status)
    {
        $tickerCountDown = 0;
        if($screeningDate = $status->getAttribute('screening_date')) {
            if(time() >= strtotime($screeningDate)) {
                $dateDiff = floor((time() - strtotime($screeningDate)) / (60 * 60 * 24));
                $tickerCountDown = max(0, 41 - $dateDiff);
            }
        }

        return $tickerCountDown;
    }

    protected function getPhoneConsultShowed($user)
    {
        $row = SelectQuery::create()
            ->select('a.date, at.showed')
            ->from('appointments a')
            ->innerJoin('appointment_times at')
            ->on('at.appointmentid = a.id')
            ->where('at.user_id = ?', array($user->getID()))
            ->andWhere('a.typeid = 21')
            ->andWhere('a.date BETWEEN ? AND ?', array('2020-01-01', '2020-12-31'))
            ->andWhere('at.showed = 1')
            ->hydrateSingleRow()
            ->groupBy('at.user_id')
            ->execute();

        $phoneConsultShowed = '';
        if(isset($row['date']) && isset($row['showed']) && $row['showed']) {
            $phoneConsultShowed .= 'Yes';
            $phoneConsultShowed .= " ({$row['date']})";
        }

        return $phoneConsultShowed;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        date_default_timezone_set('America/New_York');

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');

        $hpa = new CompleteHRAComplianceView($programStart, $programEnd);
        $hpa->setReportName('Health Risk Assessment (HRA)');
        $hpa->setName('hra');
        $hpa->emptyLinks();
        $preventionEventGroup->addComplianceView($hpa);

        $scr = new CompleteScreeningComplianceView($programStart, $programEnd);
        $scr->setReportName('Screening Program');
        $scr->setName('screening');
        $scr->emptyLinks();
        $preventionEventGroup->addComplianceView($scr);

        // Used for override to force compliant
        $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $forceCompliant->setName('force_compliant');
        $forceCompliant->setReportName('Force Overall Compliant');
        $preventionEventGroup->addComplianceView($forceCompliant);


        // Used for NSK "Results Are Ready" Email
        $screeningBiometric = new NSK2020CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningBiometric->setName('screening_biometrics');
        $screeningBiometric->setReportName('Screening With Labs and Biometrics');
        $preventionEventGroup->addComplianceView($screeningBiometric);


        $this->addComplianceViewGroup($preventionEventGroup);

        $tobaccoGroup = new ComplianceViewGroup('Tobacco');

        $smokingView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco');
        $smokingView->setUseDateForComment(true);
        $tobaccoGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($tobaccoGroup);

        $biometricsGroup = new ComplianceViewGroup('Requirements');
        $biometricsGroup->setPointsRequiredForCompliance(10);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setIndicateSelfReportedResults(false);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');


        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setIndicateSelfReportedResults(false);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');


        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setName('glucose');
        $glucoseView->setIndicateSelfReportedResults(false);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-64 or 100-125');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');


        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setIndicateSelfReportedResults(false);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');


        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setIndicateSelfReportedResults(false);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new NSK2013ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setIndicateSelfReportedResults(false);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setComplianceStatusPointMapper(new NSK2013BFMapper());
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);
        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);

        $biometricOverrideGroup = new ComplianceViewGroup('Biometric Override');

        $biometricOverrideView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $biometricOverrideView->setName('biometric_point_override');
        $biometricOverrideView->setReportName('Biometric Point Override');
        $biometricOverrideGroup->addComplianceView($biometricOverrideView);

        $this->addComplianceViewGroup($biometricOverrideGroup);
    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $alias, $allAliases)
    {
        $userIdsToIgnore = array();

        static $callCache = array(); // Keep the latest user's data around

        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use(&$callCache, $alias, $allAliases, $userIdsToIgnore) {
            $view = $status->getComplianceView();

            if($view->getStartDate('Y-m-d') != '2020-01-01') {
                return;
            }

            if(!isset($callCache['user_id']) || $user->id != $callCache['user_id']) {
                $callCache = array('user_id' => $user->id, 'aliases' => array());

                foreach($allAliases as $testAlias => $testsToCheck) {
                    foreach($testsToCheck as $testToCheck) {
                        if($testToCheck->getMappedStatus($user)->getPoints() < $testToCheck->getMaximumNumberOfPoints()) {
                            $callCache['aliases'][] = $testAlias;

                            break;
                        }
                    }
                }
            }

            if($status->getAttribute('has_result') && $status->getPoints() < $view->getMaximumNumberOfPoints()) {
                $aliasesToUse = in_array($user->id, $userIdsToIgnore) ?
                    array($alias) : $callCache['aliases'];

                if($user->client_id == '1935') {
                    $endDate = $view->getEndDate();
                } else {
                    $screeningDate = $status->getAttribute('date');

                    $endDate = $status->getAttribute('date') ? strtotime('+35 days', strtotime($screeningDate)) : $view->getEndDate();
                }

                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $endDate, $aliasesToUse);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());

                $elearningStatus = $elearningView->getStatus($user);

                if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $originalPoints = $status->getPoints();

                    $status->setComment(sprintf('%s<br/>(Alternative Used;<br/>Otherwise %s points; <br/> e-Learning completion date: %s)', $status->getComment(), $status->getPoints(), $elearningStatus->getComment()));
                    $status->setPoints($view->getMaximumNumberOfPoints());
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                    $status->setAttribute('extra_points', $view->getMaximumNumberOfPoints() - $originalPoints);
                } else {
                    if(date('Y-m-d') <= date('Y-m-d', $endDate)) {
                        $status->setAttribute('alternative_needed', true);
                    }
                }
            }
        });
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        $thisRequirements = $status->getComplianceViewGroupStatus('Requirements');
        $thisPrevention = $status->getComplianceViewGroupStatus('Prevention Event');
        $thisTobacco = $status->getComplianceViewGroupStatus('Tobacco');

        if(!$this->evaluateOverall) {
            return;
        }

        $hasBiometricOverride = false;

        if ($status->getComplianceViewStatus('force_compliant')->isCompliant()) {
            $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);
            $thisPrevention->setStatus(ComplianceStatus::COMPLIANT);
            $thisTobacco->setStatus(ComplianceStatus::COMPLIANT);

            $status->setStatus(ComplianceStatus::COMPLIANT);
            $hasBiometricOverride = true;
        }

        $biometricPoints = 0;

        $extraPoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                    $extraPoints += $viewExtraPoints;
                }

                if($viewStatus->getComplianceView()->getName() != 'force_compliant'
                    && $viewStatus->isCompliant()
                    && $viewStatus->getUsingOverride()) {

                    if($viewStatus->getComplianceView()->getStatusPointMapper() !== null
                        && $viewStatus->getComplianceView()->getStatusPointMapper()->getPoints($viewStatus->getAttribute('original_status')) != null) {
                        $originalPoints = $viewStatus->getComplianceView()->getStatusPointMapper()->getPoints($viewStatus->getAttribute('original_status'));
                    } elseif($viewStatus->getAttribute('original_points')){
                        $originalPoints = $viewStatus->getAttribute('original_points');
                    } else {
                        $originalPoints = 0;
                    }

                    $viewStatus->setComment(sprintf('%s<br/>(Alternative Used;<br/>Otherwise %s points;)', $viewStatus->getComment(), $originalPoints));
                }
            }
        }

        $status->setAttribute('total_points_ignoring_alternatives', $status->getPoints() - $extraPoints);

        $screeningDate = false;
        foreach($thisRequirements->getComplianceViewStatuses() as $viewStatus) {
            $biometricPoints += $viewStatus->getPoints();

            if($viewStatus->getAttribute('has_result') && $viewStatus->getAttribute('date')) {
                $screeningDate = $viewStatus->getAttribute('date');
            }
        }

        $status->setAttribute('screening_date', $screeningDate);
        $status->setAttribute('has_biometric_override', $hasBiometricOverride);


        if($this->getPhoneConsultShowed($user)) {
            $biometricPoints = $thisRequirements->getComplianceViewGroup()->getMaximumNumberOfPoints();
        } else if ($status->getComplianceViewStatus('biometric_point_override')->getStatus() == ComplianceStatus::COMPLIANT) {
            $biometricPoints = $thisRequirements->getComplianceViewGroup()->getMaximumNumberOfPoints();
        }

        $thisRequirements->setPoints($biometricPoints);

        if($hasBiometricOverride || $biometricPoints >= 10) {
            if($thisRequirements->getStatus() != ComplianceStatus::COMPLIANT) {
                $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);

                parent::evaluateAndStoreOverallStatus($status);
            }
        } else {
            if($thisRequirements->getStatus() != ComplianceStatus::NOT_COMPLIANT) {
                $thisRequirements->setStatus(ComplianceStatus::NOT_COMPLIANT);

                parent::evaluateAndStoreOverallStatus($status);
            }
        }

        $priorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($status->getUser(), '2019-01-01', '2019-12-31');
        $priorPriorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($status->getUser(), '2018-01-01', '2018-12-31');

        $status->setAttribute('prior_status', $priorStatus);
        $status->setAttribute('prior_prior_status', $priorPriorStatus);

        $allCompliant = true;
        foreach($thisPrevention->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getComplianceView()->getName() == 'force_compliant') continue;
            if($viewStatus->getComplianceView()->getName() == 'screening_biometrics') continue;

            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                $allCompliant = false;
            }
        }

        foreach($thisTobacco->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                $allCompliant = false;
            }
        }

        if($thisRequirements->getStatus() != ComplianceStatus::COMPLIANT) {
            $allCompliant = false;
        }

        if($allCompliant) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

        $thisPreventionCompliant = true;
        foreach($thisPrevention->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getComplianceView()->getName() == 'force_compliant') continue;
            if($viewStatus->getComplianceView()->getName() == 'screening_biometrics') continue;
            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                $thisPreventionCompliant = false;
            }
        }

        if ($this->isNewHire($status->getUser())) {
            $status->setComment('$0');
        } else {
            if($status->isCompliant()) {
                $status->setComment('$0');
            } elseif(($thisPreventionCompliant) && ($thisTobacco->isCompliant() || $biometricPoints >= 10 || $this->getPhoneConsultShowed($user))) {
                $status->setComment('$30');
            } elseif($thisPreventionCompliant) {
                $status->setComment('$45');
            } else {
                $status->setComment('$90');
            }
        }

        if($status->getComplianceViewStatus('force_compliant')->getComment() != '') {
            $status->setComment($status->getComplianceViewStatus('force_compliant')->getComment());
        }

        if($screeningDate = $status->getAttribute('screening_date')) {
            $status->setAttribute('ticker_countdown', $this->getTickerCountdown($status));
            $status->setAttribute('ticker_count_show', true);
        }
    }

    public function useParallelReport()
    {
        return false;
    }

    public function getPriorYearBiometricsComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        $program = $this->cloneForEvaluation($startDate, $endDate);

        // Change name of the cloned views so that new overrides don't apply to
        // last year's results.

        foreach($program->getComplianceViews() as $cV) {
            $cV->setUseOverrides(false);
        }

        $program->evaluateOverall = false;

        $program->setActiveUser($user);

        return $program->getStatus();
    }

    public function isSpouse(User $user)
    {
        return $user->getRelationshipType() == 2 ? true : false;
    }

    public function isNewHire(User $user)
    {
        $groupNewHireMapper = array(
            '1931'  => '2020-05-25',
            '1930'  => '2020-05-25',
            '1935'  => '2020-06-29',
            '1937'  => '2020-05-18',
            '1932'  => '2020-08-03',
            '1934'  => '2020-06-15',
            '1933'  => '2020-03-16'
        );

        if(isset($groupNewHireMapper[$user->client_id])
            && $user->getHiredate() >= $groupNewHireMapper[$user->client_id]) {
            return true;
        }

        return false;
    }

    private function getBmiView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBMIScreeningTestComplianceView'
        );

        $view->setIndicateSelfReportedResults(false);

        $view->addRange(4, 18.5, 25.0, 'E');
        $view->addRange(3, 17.0, 30.0, 'E');
        $view->addRange(2, 15.0, 35.0, 'E');
        $view->addRange(1, 13.0, 40.0, 'E');
        $view->setStatusSummary(0, '&lt;13 or &gt;40');


        return $view;
    }

    private function getBodyFatView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBodyFatScreeningTestComplianceView'
        );

        $view->setIndicateSelfReportedResults(false);

        $view->addRange(4, 2.0, 18.0, 'M');
        $view->addRange(3, 0.0, 25.0, 'M');
        $view->addRange(2, 0.0, 30.0, 'M');
        $view->addRange(1, 0.0, 35.0, 'M');
        $view->addDefaultStatusSummaryForGender(0, 'M', '&gt;35');


        $view->addRange(4, 12.0, 25.0, 'F');
        $view->addRange(3, 0.0, 32.0, 'F');
        $view->addRange(2, 0.0, 37.0, 'F');
        $view->addRange(1, 0.0, 42.0, 'F');
        $view->addDefaultStatusSummaryForGender(0, 'F', '&gt;42');




        return $view;
    }

    public function getRelatedUser(User $user)
    {
        $relatedUser = false;

        $relationshipUsers = array();

        if($user->relationship_user_id && !$user->relationshipUser->expired()) {
            $relationshipUsers[] = $user->relationshipUser;
        }

        foreach($user->relationshipUsers as $relationshipUser) {
            if(!$relationshipUser->expired()) {
                $relationshipUsers[] = $relationshipUser;
            }
        }

        foreach($relationshipUsers as $relationshipUser) {
            if(in_array($relationshipUser->relationship_type, $this->relationshipTypes)) {
                $relatedUser = $relationshipUser;

                break;
            }
        }

        return $relatedUser;
    }

    public function getRelatedUserComplianceStatus(User $user)
    {
        $relatedUser = $this->getRelatedUser($user);

        $programRecord = ComplianceProgramRecordTable::getInstance()->find(self::RECORD_ID);

        $program = $programRecord->getComplianceProgram();

        $program->setActiveUser($user);
        $status = $program->getStatus();
        $status->setStatus(ComplianceStatus::NA_COMPLIANT);

        if($relatedUser) {
            $program->setActiveUser($relatedUser);
            $status = $program->getStatus();
        }

        return $status;
    }

    private $relationshipTypes = array(Relationship::SPOUSE, Relationship::EMPLOYEE);
    protected $evaluateOverall = true;
    const RECORD_ID = 1496;
}


class NSK2020WMS2Printer implements ComplianceProgramReportPrinter
{
    public function getStatusMappings(ComplianceView $view)
    {
        if($view->getName() == 'bf_bmi') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                3 => ComplianceStatus::PARTIALLY_COMPLIANT,
                2 => ComplianceStatus::NOT_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT,
                0 => ComplianceStatus::NOT_COMPLIANT
            );
        } else {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                2 => ComplianceStatus::PARTIALLY_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT
            );
        }
    }

    public function getClass(ComplianceView $view)
    {
        if($view->getName() == 'bf_bmi') {
            return array(
                4 => 'success',
                3 => 'warning',
                2 => 'danger',
                1 => 'danger',
                0 => 'danger'
            );
        } else {
            return array(
                ComplianceStatus::COMPLIANT => 'success',
                ComplianceStatus::PARTIALLY_COMPLIANT => 'warning',
                ComplianceStatus::NOT_COMPLIANT => 'danger'
            );
        }
    }


    public function printReport(ComplianceProgramStatus $status)
    {
        $priorStatus = $status->getAttribute('prior_status');
        $priorPriorStatus = $status->getAttribute('prior_prior_status');
        $isSpouse = $status->getComplianceProgram()->isSpouse($status->getUser());
        $showSpouse = $isSpouse || $status->getComplianceProgram()->getRelatedUser($status->getUser());

        $escaper = new hpn\common\text\Escaper;

        $lastYearRequirementsGroup = $priorStatus->getComplianceViewGroupStatus('Requirements');
        $priorLastYearRequirementsGroup = $priorPriorStatus->getComplianceViewGroupStatus('Requirements');

        $requirementsStatus = $status->getComplianceViewGroupStatus('Requirements');

        $thisYearTotalPoints = $requirementsStatus->getPoints();
        $biometricStatus = $thisYearTotalPoints >= 10 ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;
        $tickerCountDown = $status->getAttribute('ticker_countdown');
        $tickerCountShow = $status->getAttribute('ticker_count_show');


        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $lastYearStatus = array();

        foreach($lastYearRequirementsGroup->getComplianceViewStatuses() as $viewStatus) {
            $lastYearStatus[$viewStatus->getComplianceView()->getName()] = $viewStatus;
        }

        $that = $this;

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor, $lastYearStatus, $that) {

            ob_start();

            if ($group->getComplianceViewGroup()->getName() == 'Requirements') : ?>
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>Test</th>
                        <th>Target</th>
                        <th class="text-center">Point Values</th>
                        <th class="text-center">Your Points</th>
                        <th class="text-center">Results</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $view = $viewStatus->getComplianceView() ?>
                        <?php $warningLabel = $view->getStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                        <?php $printed = false ?>
                        <?php $lastYearStatusO = $lastYearStatus[$viewStatus->getComplianceView()->getName()]; ?>
                        <?php $mappings = $that->getStatusMappings($view); ?>
                        <?php $class = $that->getClass($view); ?>
                        <?php $j = 0 ?>
                        <?php foreach($mappings as $sstatus => $mapping) : ?>
                            <?php if ($warningLabel !== null || $sstatus != ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                <tr>
                                    <?php if($j < 1) : ?>
                                        <td rowspan="<?php echo $warningLabel === null ? (count($mappings) - 1) : count($mappings) ?>">
                                            <?php echo $view->getReportName() ?>
                                            <br/>
                                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                <div><?php echo $link->getHTML() ?></div>
                                            <?php endforeach ?>
                                            <div>
                                                <div><strong>Last Year</strong></div>
                                                <div>Result: <?php echo $lastYearStatusO->getComment() ?></div>
                                                <div>Points: <?php echo $lastYearStatusO->getPoints() ?></div>
                                            </div>
                                        </td>
                                    <?php endif ?>

                                    <td><span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $view->getStatusSummary($sstatus) ?></span></td>
                                    <td class="text-center">
                                        <?php echo $view->getStatusPointMapper()->getPoints($sstatus) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($viewStatus->getComplianceView()->getName() == 'bf_bmi') : ?>
                                            <?php if($viewStatus->getPoints() == $sstatus) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $viewStatus->getPoints() ?></span>
                                            <?php endif ?>
                                        <?php elseif($viewStatus->getComplianceView()->getName() == 'comply_with_total_hdl_cholesterol_ratio_screening_test') : ?>
                                            <?php if($viewStatus->getPoints() > 0 && $sstatus == 4) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $viewStatus->getPoints() ?></span>
                                            <?php elseif($viewStatus->getPoints() == 0 && $sstatus < 4) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $viewStatus->getPoints() ?></span>
                                            <?php endif ?>
                                        <?php else : ?>
                                            <?php if($viewStatus->getStatus() == $sstatus) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $viewStatus->getPoints() ?></span>
                                            <?php endif ?>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($viewStatus->getComplianceView()->getName() == 'bf_bmi') : ?>
                                            <?php if($viewStatus->getPoints() == $sstatus) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>">
                                                    <?php echo $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                                                    <?php echo $viewStatus->getComment() ?>
                                                </span>
                                            <?php endif ?>
                                        <?php elseif($viewStatus->getComplianceView()->getName() == 'comply_with_total_hdl_cholesterol_ratio_screening_test') : ?>
                                            <?php if($viewStatus->getPoints() > 0 && $sstatus == 4) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>">
                                                    <?php echo $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                                                    <?php echo $viewStatus->getComment() ?>
                                                </span>
                                            <?php elseif($viewStatus->getPoints() == 0 && $sstatus < 4) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>">
                                                    <?php echo $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                                                    <?php echo $viewStatus->getComment() ?>
                                                </span>
                                            <?php endif ?>
                                        <?php else : ?>
                                            <?php if($viewStatus->getStatus() == $sstatus) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>">
                                                    <?php echo $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                                                    <?php echo $viewStatus->getComment() ?>
                                                </span>
                                            <?php endif ?>
                                        <?php endif ?>
                                    </td>
                                </tr>
                                <?php $j++ ?>
                            <?php endif ?>
                        <?php endforeach ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php else : ?>
                <table class="details-table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th class="points">Status</th>
                        <th class="text-center">Date of Exam</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1 ?>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php

                        if ($viewStatus->getComplianceView()->getName() == 'comply_with_cotinine_screening_test') {
                            if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                                $pct = 1;
                                $text = 'Done';
                            } elseif($viewStatus->getStatus() == ComplianceViewStatus::PARTIALLY_COMPLIANT) {
                                $pct = 0.5;
                                $text = 'Partially Done';
                            } elseif($viewStatus->getStatus() == ComplianceViewStatus::NOT_COMPLIANT
                                && $viewStatus->getAttribute('date')) {
                                $pct = 0;
                                $text = 'Positive Result';
                            } else {
                                $pct = 0;
                                $text = 'Not Done';
                            }
                        } else {
                            if ($viewStatus->isCompliant()) {
                                $pct = 1;
                            } else if ($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                                $pct = 0.5;
                            } else {
                                $pct = 0;
                            }

                            $text = $viewStatus->getText();
                        }

                        ?>
                        <?php $class = $classFor($pct) ?>
                        <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                            <td class="name">
                                <?php echo $i ?>.
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            </td>
                            <td class="points <?php echo $class ?>">
                                <?php echo $text ?>
                            </td>
                            <td class="links text-center">
                                <div><?php echo $viewStatus->getComment() ?></div>
                            </td>
                        </tr>
                        <?php $i++ ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
            <?php
            return ob_get_clean();
        };

        $classForCircleStatus = function($status) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else {
                return 'danger';
            }
        };

        $textForCircleStatus = function($status, $sectionName) {
            if($sectionName == 'required') {
                if ($status == ComplianceStatus::COMPLIANT) {
                    return '<div style="font-size:9pt; margin-top: 10px;">Done</div>';
                } else {
                    return '<div style="font-size:9pt; margin-top: 10px;">Not Done</div>';
                }
            } else {
                return '';
            }
        };

        $circle = function($status, $text, $sectionName, $tickerCountShow) use ($classForCircleStatus, $textForCircleStatus) {
            $class = $classForCircleStatus($status);
            ob_start();
            ?>
            <div class="circle-range <?php echo !$tickerCountShow ? 'hide' : '' ?>">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>"
                    <?php echo $sectionName == 'Requirements' ? 'style="background-color: #CCC;"' : '' ?>>
                    <div style="font-size: 1.2em; line-height: 1.0em; margin-top:-10px;">
                        <?php echo $text ?>
                        <?php echo $textForCircleStatus($status, $sectionName) ?>
                    </div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
            ob_start();


            if ($group->getComplianceViewGroup()->getMaximumNumberOfPoints() === null) {
                if($group->getComplianceViewGroup()->getName() == 'Prevention Event') {
                    if($group->getComplianceViewStatus('hra')->getStatus() == ComplianceViewStatus::COMPLIANT
                        && $group->getComplianceViewStatus('screening')->getStatus() == ComplianceViewStatus::COMPLIANT) {
                        $pct = 1;
                        $actual = 'Done';
                    } elseif($group->getComplianceViewStatus('hra')->getStatus() == ComplianceViewStatus::COMPLIANT
                        || $group->getComplianceViewStatus('screening')->getStatus() == ComplianceViewStatus::COMPLIANT) {
                        $pct = 0.5;
                        $actual = 'Not Done';
                    } else {
                        $pct = 0;
                        $actual = 'Not Done';
                    }
                } elseif($group->getComplianceViewGroup()->getName() == 'Tobacco') {
                    if($group->getComplianceViewStatus('comply_with_cotinine_screening_test')->getStatus() == ComplianceViewStatus::COMPLIANT) {
                        $pct = 1;
                        $actual = 'Done';
                    } elseif($group->getComplianceViewStatus('comply_with_cotinine_screening_test')->getStatus() == ComplianceViewStatus::PARTIALLY_COMPLIANT) {
                        $pct = 0.5;
                        $actual = 'Partially Done';
                    }  elseif($group->getComplianceViewStatus('comply_with_cotinine_screening_test')->getStatus() == ComplianceViewStatus::NOT_COMPLIANT
                        && $group->getComplianceViewStatus('comply_with_cotinine_screening_test')->getAttribute('date')) {
                        $pct = 0;
                        $actual = 'Positive Result';
                    } else {
                        $pct = 0;
                        $actual = 'Not Done';
                    }
                } else {
                    if ($group->isCompliant()) {
                        $pct = 1;
                    } else if ($group->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                        $pct = 0.5;
                    } else {
                        $pct = 0;
                    }

                    $actual = $group->getText();
                }


                $target = $group->getComplianceViewGroup()->getName() == 'Tobacco' ? 'Negative Result' : 'Done';
            } else {
                $points = $group->getPoints();
                $target = '<strong>'.$group->getComplianceViewGroup()->getMaximumNumberOfPoints().'</strong><br/>points';
                $actual = '<strong>'.$points.'</strong><br/>points';
                $pct = $points / $group->getComplianceViewGroup()->getPointsRequiredForCompliance();
            }

            $class = $classFor($pct);
            if($pct > 1) $pct = 1;

            ?>
            <tr class="picker closed">
                <td class="name">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="points target">
                    <?php echo $target ?>
                </td>
                <td class="points <?php echo $class ?>">
                    <?php echo $actual ?>
                </td>
                <td class="pct">
                    <div class="pgrs">
                        <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <tr class="details closed">
                <td colspan="4">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        };

        $maxPriorPoints = $escaper->escapeJs($lastYearRequirementsGroup->getComplianceViewGroup()->getMaximumNumberOfPoints());


        $relatedUserStatus = $status->getComplianceProgram()->getRelatedUserComplianceStatus($status->getUser());
        ?>
        <style type="text/css">
            #activities {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
                min-width: 500px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }

            .details-table .name {
                width: 300px;
            }

            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            #point-discounts {
                width: 100%;
            }

            #point-discounts td {
                vertical-align: middle;
                padding: 5px;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1em;
            }

            .view-force_compliant {
                display: none;
            }

            .view-screening_biometrics {
                display: none;
            }

            .point-totals th {
                text-align: right;
                padding-right: 10px;
            }

            .point-totals td {
                text-align: center;
            }

            .total-status td, .spouse-status td {
                text-align: center;
            }
        </style>
        <div class="row">
            <div class="col-md-12">
                <h1>2020 Incentive Report Card</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <a href="/compliance/nsk-2020/overview">Full Program Details</a><br/>
                <a href="/content/nsk-previous-program-years">Previous Program Years</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <div class="row">
            <div class="row">
                <div class="col-md-12"  style="margin-top: 15px;">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-12 col-md-offset-1">

                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-12 col-md-offset-1">
                                            <?php echo $circle(
                                                $biometricStatus,
                                                '<span class="circle-points"><br /><span style="font-size:21pt; font-weight: bold;">'.$tickerCountDown. '</span><br /><br />Days<br />Remaining</span>',
                                                'Requirements',
                                                $tickerCountShow
                                            ) ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-12 col-md-offset-1">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><br />

        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-warning">
                    IF YOU HAVE NOT ACCUMULATED 10 OR MORE POINTS IN YOUR REPORT CARD, YOU MUST CALL CIRCLE WELLNESS AT
                    866-682-3020 X204 WITHIN 14 DAYS OF YOUR SCREENING DATE TO SCHEDULE A PHONE CONSULTATION. PHONE
                    CONSULTATIONS WILL NEED TO BE COMPLETED WITHIN 40 DAYS OF YOUR SCREENING DATE. IF YOU QUALIFY FOR
                    EXTENDED RISK COACHING, YOU WILL BE INVITED DURING YOUR PHONE CONSULTATION AND REQUIRED TO PARTICIPATE
                    IN 4 QUARTERLY COACHING SESSIONS (PHONE OR E-MAIL) TO RECEIVE A LEVEL 1 ($0 SURCHARGE) INCENTIVE.
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Actual</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Prevention Events', $status->getComplianceViewGroupStatus('Prevention Event')) ?>
                    <?php echo $tableRow('Tobacco Status', $status->getComplianceViewGroupStatus('Tobacco')) ?>
                    <?php echo $tableRow('Biometric Measures', $status->getComplianceViewGroupStatus('Requirements')) ?>
                    <tr class="point-totals">
                        <th>2020 Point Totals</th>
                        <td><?php echo $maxPriorPoints ?></td>
                        <td><?php echo $status->getPoints() ?></td>
                    </tr>
                    <tr class="point-totals">
                        <th>2020 Without Alternatives</th>
                        <td><?php echo $maxPriorPoints ?></td>
                        <td><?php echo $status->getAttribute('total_points_ignoring_alternatives') ?></td>
                    </tr>
                    <tr class="point-totals">
                        <td colspan="4"><hr/></td>
                    </tr>
                    <tr class="total-status">
                        <th>
                            Your Program Status
                            <ul>
                                <li>Complete HRA, Screening and test negative for cotinine</li>
                                <li>Obtain 10+ points</li>
                            </ul>
                        </th>
                        <td colspan="3">
                            <?php if ($status->isCompliant()) : ?>
                                <span class="label label-success">Done</span>
                            <?php elseif ($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                <span class="label label-warning">Partially Done</span>
                            <?php else : ?>
                                <span class="label label-danger">Not Done</span>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php if ($showSpouse) : ?>
                        <?php $relatedUserStatus = $status->getComplianceProgram()->getRelatedUserComplianceStatus($status->getUser()); ?>
                        <tr class="spouse-status">
                            <th>Your Spouse's Status</th>
                            <td colspan="3">
                                <?php if ($relatedUserStatus->isCompliant()) : ?>
                                    <span class="label label-success">Done</span>
                                <?php elseif ($relatedUserStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                    <span class="label label-warning">Partially Done</span>
                                <?php else : ?>
                                    <span class="label label-danger">Not Done</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endif ?>
                    </tbody>
                </table>
                <br/>
                <p>
                    <strong>Pregnant?</strong> Are you pregnant or 6 months postpartum? You may complete a
                    <a href="/resources/10529/NSK_2020_Pregnancy_Exception_Form.pdf">pregnancy exception form</a>
                    and submit it to Circle Wellness to obtain credit for your screening.
                </p>
            </div>
        </div>
        <script type="text/javascript">
            $(function() {
                $.each($('#activities .picker'), function() {
                    $(this).click(function(e) {
                        if ($(this).hasClass('closed')) {
                            $(this).removeClass('closed');
                            $(this).addClass('open');
                            $(this).nextAll('tr.details').first().removeClass('closed');
                            $(this).nextAll('tr.details').first().addClass('open');
                        } else {
                            $(this).addClass('closed');
                            $(this).removeClass('open');
                            $(this).nextAll('tr.details').first().addClass('closed');
                            $(this).nextAll('tr.details').first().removeClass('open');
                        }
                    });
                });

                $('.details-table .name').width($('.picker td.name').first().width());
                $('.details-table .points').width($('.picker td.points').first().width());
                $('.details-table .links').width($('.picker td.pct').first().width());

                $more = $('#more-info-toggle');
                $moreContent = $('#more-info');

                $more.click(function(e) {
                    e.preventDefault();

                    if ($more.html() == 'More...') {
                        $moreContent.css({ display: 'block' });
                        $more.html('Less...');
                    } else {
                        $moreContent.css({ display: 'none' });
                        $more.html('More...');
                    }
                });
            });
        </script>
        <?php
    }
}
