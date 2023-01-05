<?php

use hpn\steel\query\SelectQuery;

define('XRAY_FIVE_FOR_FIVE_CAMPAIGN', 'xray_svnfive_for_five_campaign_2017');

class XrayFiveForFive2017ComplianceView extends ComplianceView
{

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return '5_for_5';
    }

    public function getDefaultReportName()
    {
        return '5 for 5 campaign';
    }

    public function getStatus(User $user)
    {
        $recordType = XRAY_FIVE_FOR_FIVE_CAMPAIGN;

        $record = $user->getNewestDataRecord($recordType);

        if($record->exists()) {
            $points = XrayFiveForFiveCampaignCommitmentForm::getPoints($record);
            $weeks = array_keys(XrayFiveForFiveCampaignCommitmentForm::getWeeks($user));

            foreach($weeks as $week){
                if(!isset($points[$week]['completed_all_five'])
                    || !isset($points[$week]['completed_lessons'])
                    || !$points[$week]['completed_all_five']) {
                    return new ComplianceViewStatus($this, ComplianceViewStatus::NA_COMPLIANT);
                }
            }

            return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT);
        }

        return new ComplianceViewStatus($this, ComplianceViewStatus::NA_COMPLIANT);
    }

}

class XrayTechnologiesEmployee2018ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserLocation(true);
        $printer->setShowUserFields(true, true, false, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);
        $printer->setShowUserContactFields(null, null, true);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $data = array();

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($groupStatus->getComplianceViewGroup()->getName() == 'Requirements') {
                        $data[sprintf('%s Result', $viewName)] = $viewStatus->getComment();
                    } elseif($groupStatus->getComplianceViewGroup()->getName() == 'required') {
                        $data[sprintf('%s Comment', $viewName)] = $viewStatus->getComment();
                    }

                    $data[sprintf('%s Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                }
            }

            $data['Total Points'] = $status->getPoints();
            $data['Total Points (Ignoring Alternatives)'] = $status->getAttribute('total_points_ignoring_alternatives');
            $data['Total Alternatives Points'] = $status->getPoints() - $status->getAttribute('total_points_ignoring_alternatives');
            $data['Total Points 2016'] = $status->getAttribute('prior_status')->getPoints();
            $data['Point Difference'] = $data['Total Points'] - $data['Total Points 2016'];
            $data['Monthly Surcharge'] = $status->getComment();
            $data['Department']  = $status->getUser()->getDepartment();
            $data['Shift']  = $status->getUser()->getShift();
            $data['Section ']  = $status->getUser()->getSection();
//            $data['Has Biometric Override'] = $status->getAttribute('has_biometric_override') ? 'Yes' : 'No';
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if (sfConfig::get('app_wms2', true)) {
            return new XrayTechnologiesEmployee2018WMS2Printer();
        } else {
            $printer = new XrayTechnologiesEmployee2018Printer();
            $printer->showResult(true);
            $printer->setShowMaxPoints(true);

            return $printer;
        }
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT => new ComplianceStatusMapping('Compliant', '/resources/3813/greenblock2.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Compliant', '/images/ministryhealth/yellowblock1.jpg'),
            ComplianceStatus::NOT_COMPLIANT => new ComplianceStatusMapping('Not Compliant', '/images/ministryhealth/redblock1.jpg')
        ));

        $this->setComplianceStatusMapper($mapping);

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $preventionEventGroup = new ComplianceViewGroup('required', 'Prevention Events');

        $hraView = new CompleteHRAComplianceView('2017-08-01', '2017-11-30');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->setAttribute('deadline', '11/30/2017');
        $hraView->addLink(new Link('Take HRA', sfConfig::get('app_wms2') ? '/compliance/gti-2016/my-health' : '/content/989'));
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView('2017-08-01', '2017-11-30');
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $screeningView->setReportName('Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->setAttribute('deadline', '11/30/2017');
        $preventionEventGroup->addComplianceView($screeningView);


        $coachingOverall = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingOverall->setName('coaching_overall');
        $coachingOverall->setReportName('Complete 2 Coaching Sessions (if applicable)');
        $coachingOverall->setAttribute('deadline', '05/31/2018');
        $coachingOverall->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $notRequired = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('not_required')
                ->from('compliance_view_status_overrides')
                ->where('user_id = ?', array($user->id))
                ->andWhere('compliance_program_record_id = ?', array(XrayTechnologiesEmployee2018ComplianceProgram::XRAYTECHNOLOGIES_EMPLOYEE_2018_RECORD_ID))
                ->andWhere('compliance_view_name = ?', array('coaching_overall'))
                ->execute();

            if($notRequired) {
                $status->setComment('Not Required');
            }
        });
        $preventionEventGroup->addComplianceView($coachingOverall);

        $coachingSession1 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession1->setName('coaching_session1');
        $coachingSession1->setReportName('Session 1');
        $coachingSession1->setAttribute('deadline', '05/31/2018');
        $coachingSession1->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $notRequired = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('not_required')
                ->from('compliance_view_status_overrides')
                ->where('user_id = ?', array($user->id))
                ->andWhere('compliance_program_record_id = ?', array(XrayTechnologiesEmployee2018ComplianceProgram::XRAYTECHNOLOGIES_EMPLOYEE_2018_RECORD_ID))
                ->andWhere('compliance_view_name = ?', array('coaching_session1'))
                ->execute();

            if($notRequired) {
                $status->setComment('Not Required');
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession1);

        $coachingSession2 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession2->setName('coaching_session2');
        $coachingSession2->setReportName('Session 2');
        $coachingSession2->setAttribute('deadline', '05/31/2018');
        $coachingSession2->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $notRequired = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('not_required')
                ->from('compliance_view_status_overrides')
                ->where('user_id = ?', array($user->id))
                ->andWhere('compliance_program_record_id = ?', array(XrayTechnologiesEmployee2018ComplianceProgram::XRAYTECHNOLOGIES_EMPLOYEE_2018_RECORD_ID))
                ->andWhere('compliance_view_name = ?', array('coaching_session2'))
                ->execute();

            if($notRequired) {
                $status->setComment('Not Required');
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession2);

        $spouseParticipation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $spouseParticipation->setName('spouse_participation');
        $spouseParticipation->setReportName('Spouse Compliance');
        $spouseParticipation->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $spouseUser = $user->getSpouseUser();

            if($user->getRelationshipType() != Relationship::EMPLOYEE || !$spouseUser|| (isset($this->options['in_spouse']) && $this->options['in_spouse']))  {
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            } else {
                $program = ComplianceProgramRecordTable::getInstance()->find(XrayTechnologiesSpouse2018ComplianceProgram::XRAYTECHNOLOGIES_SPOUSE_2018_RECORD_ID)->getComplianceProgram(array('in_employee' => true));
                $program->setActiveUser($spouseUser);
                $overallStatus = $program->getStatus();

                $requiredGroupStatus = $overallStatus->getComplianceViewGroupStatus('required');
                $hraStatus = $requiredGroupStatus->getComplianceViewStatus('hra');
                $screeningStatus = $requiredGroupStatus->getComplianceViewStatus('screening');
                $coachingStatus = $requiredGroupStatus->getComplianceViewStatus('coaching_overall');

                $numCompliant = 0;

                $status->setAttribute('has_spouse', true);

                $status->setAttribute('spouse_hra_status', $hraStatus->getStatus());
                if($hraStatus->isCompliant()) {
                    $numCompliant++;
                }

                $status->setAttribute('spouse_screening_status', $screeningStatus->getStatus());
                if($screeningStatus->isCompliant()) {
                    $numCompliant++;
                }

                $status->setAttribute('spouse_coaching_status', $coachingStatus->getStatus());
                $status->setAttribute('spouse_coaching_comment', $coachingStatus->getComment());
                if($coachingStatus->isCompliant()) {
                    $numCompliant++;
                }

                if($numCompliant == 3) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } elseif( $numCompliant > 0) {
                    $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                }
            }

        });
        $preventionEventGroup->addComplianceView($spouseParticipation, true);

        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements', 'Biometric Measures');
        $biometricsGroup->setPointsRequiredForCompliance(9);

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco');
        $smokingView->setName('tobacco');
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $smokingView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setComment('Non-Smoker');
            } elseif($status->getStatus() == ComplianceViewStatus::NA_COMPLIANT) {
                $status->setComment('No Result');
            } else {
                $status->setComment('Smoker');
            }
        });
//        $biometricsGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->overrideTestRowData(null, null, 149, 199.99);
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥200');
        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->overrideTestRowData(50, 70, 99.99, 125);
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-69.99 or 100-125');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');
        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setIndicateSelfReportedResults(false);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');
        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');
        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new GVT2015ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setIndicateSelfReportedResults(false);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setComplianceStatusPointMapper(new GVT2015BFMapper());
        $bodyFatBMIView->setMaximumNumberOfPoints(4);
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);


        $aliasBloodPressure = 'blood_pressure';
        $aliasTriglycerides = 'triglycerides';
        $aliasBloodSugar = 'blood_sugar';
        $aliasTotalCholesterol = 'total_cholesterol';
        $aliasCholesterolRatio = 'cholesterol_ratio';
        $aliasBmi = 'body_fat_bmi';

        $this->configureViewForElearningAlternative($bloodPressureView, $aliasBloodPressure);
        $this->configureViewForElearningAlternative($triglView, $aliasTriglycerides);
        $this->configureViewForElearningAlternative($glucoseView, $aliasBloodSugar);
        $this->configureViewForElearningAlternative($cholesterolView, $aliasTotalCholesterol);
        $this->configureViewForElearningAlternative($totalHDLRatioView, $aliasCholesterolRatio);
        $this->configureViewForElearningAlternative($bodyFatBMIView, $aliasBmi);

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);

        $wellnessCampaignGroup = new ComplianceViewGroup('wellness_campaign', 'Wellness Campaign Points');
        $wellnessCampaignGroup->setPointsRequiredForCompliance(9);

        $fiveForFiveIgnoredUsers = XrayTechnologiesEmployee2018ComplianceProgram::getFiveForFiveIgnoredUsers();

        $healthyHolidays = new Android2014WeightProgram('2017-07-21', '2018-05-31');
        $healthyHolidays->setMaximumNumberOfPoints(2);
        $healthyHolidays->setReportName('Healthy Holidays');
        $healthyHolidays->setName('healthy_holidays');
        $healthyHolidays->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $healthyHolidays->setStatusSummary(ComplianceStatus::COMPLIANT, ' ');
        $healthyHolidays->setAllowPointsOverride(true);

        $wellnessCampaignGroup->addComplianceView($healthyHolidays);

        $getUpAndGO = new PlaceHolderComplianceView();
        $getUpAndGO->setMaximumNumberOfPoints(2);
        $getUpAndGO->setName('get_up_and_go');
        $getUpAndGO->setReportName('Get Up and Go');
        $getUpAndGO->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $getUpAndGO->setAllowPointsOverride(true);
        $getUpAndGO->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($fiveForFiveIgnoredUsers) {
//            $participate = SelectQuery::create()
//                ->select('id')
//                ->from('compliance_program_record_team_users')
//                ->where('compliance_program_record_id = 737')
//                ->andWhere('user_id = ?', array($user->id))
//                ->andWhere('accepted = 1')
//                ->hydrateSingleScalar()
//                ->execute();
//
//            if($participate) {
//                $status->setStatus(ComplianceViewStatus::COMPLIANT);
//            }
//
//            if(in_array($user->id, $fiveForFiveIgnoredUsers)) {
//                $status->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
//            }
        });
        $wellnessCampaignGroup->addComplianceView($getUpAndGO);

        $fiveForFiveView = new XrayFiveForFive2017ComplianceView();
        $fiveForFiveView->setMaximumNumberOfPoints(2);
        $fiveForFiveView->setName('5_for_5');
        $fiveForFiveView->setReportName('5 for 5 campaign');

        $wellnessCampaignGroup->addComplianceView($fiveForFiveView);

        $this->addComplianceViewGroup($wellnessCampaignGroup);

    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $alias)
    {
        static $callCache = array(); // Keep the latest user's data around

        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use(&$callCache, $alias) {
            $view = $status->getComplianceView();

            if($status->getAttribute('has_result') && $status->getPoints() < $view->getMaximumNumberOfPoints()) {
                $numberRequired = ($view->getMaximumNumberOfPoints() - $status->getPoints()) * 2;

                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), $alias);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());
                $elearningView->setPointsPerLesson(0.5);
                $elearningView->setNumberRequired($numberRequired);

                $view->addLink(new Link('Alternative', "/content/9420?action=lessonManager&tab_alias={$alias}&numberRequired={$numberRequired}"));

                $elearningStatus = $elearningView->getStatus($user);
                $elearningPoints = $elearningStatus->getPoints();

                if(floor($elearningPoints) != $elearningPoints) {
                    $elearningPoints = $elearningPoints - 0.5;
                }

                if($elearningPoints >= 1) {
                    $originalPoints = $status->getPoints();

                    $status->setPoints(min($status->getComplianceView()->getMaximumNumberOfPoints(), $originalPoints + $elearningPoints));

                    if($status->getPoints() >= $status->getComplianceView()->getMaximumNumberOfPoints()) {
                        $status->setStatus(ComplianceViewStatus::COMPLIANT);
                    } else {
                        $status->setStatus(ComplianceViewStatus::PARTIALLY_COMPLIANT);
                    }

                    $status->setAttribute('extra_points', $elearningPoints);
                    $status->setComment(sprintf('%s<br/>(Alternative Used;<br/>Otherwise %s points; <br/> e-Learning completion date: %s)', $status->getComment(), $originalPoints, $elearningStatus->getComment()));
                } else {
                    $status->setAttribute('alternative_needed', true);
                }
            }
        });
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if(!$this->evaluateOverall) {
            return;
        }

        $user = $status->getUser();

        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');
        $sessionOverallStatus = $requiredGroupStatus->getComplianceViewStatus('coaching_overall');
        $session1Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session1');
        $session2Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session2');

        $coachingStatus = array();

        $coachingStartDate = '2017-08-01';
        $coachingEndDate = '2018-05-30';

        $session = CoachingSessionTable::getInstance()->findMostRecentSessionForUser($user);
        if(is_object($session)) {
            $reports = CoachingReportTable::getInstance()->findActiveReports($session);

            foreach($reports as $report) {
                if(is_object($report)) {
                    if($report->getDate('Y-m-d') < $coachingStartDate || $report->getDate('Y-m-d') > $coachingEndDate) continue;

                    $reportEdit = CoachingReportEditTable::getInstance()->findMostRecentEdit($report);

                    if(is_object($reportEdit)) {
                        $recordedDocument = $reportEdit->getRecordedDocument();
                        $recordedFields = $recordedDocument->getRecordedDocumentFields();

                        $coachingData = array();
                        $isContact = true;
                        foreach($recordedFields as $recordedField) {
                            $name = $recordedField->getFieldName();
                            $value = $recordedField->getFieldValue();
                            if(empty($value) || !empty($defaults[$name])) continue;

                            if($name == 'attempt' && !empty($name)) $isContact = false;
                            $coachingData[$name] = $value;
                        }

                        if($isContact) {
                            if(!isset($coachingStatus['session1'])) {
                                $coachingStatus['session1'] = $coachingData;
                            } elseif(!isset($coachingStatus['session2'])) {
                                $coachingStatus['session2'] = $coachingData;
                            } elseif(!isset($coachingStatus['session3'])) {
                                $coachingStatus['session3'] = $coachingData;
                            } elseif(!isset($coachingStatus['session4'])) {
                                $coachingStatus['session4'] = $coachingData;
                            }
                        }
                    }
                }
            }
        }

        if(isset($coachingStatus['session1'])
            && isset($coachingStatus['session1']['total_minutes'])
            && $coachingStatus['session1']['total_minutes'] > 2) {
            $session1Status->setStatus(ComplianceStatus::COMPLIANT);
            if(isset($coachingStatus['session1']['date'])) $session1Status->setComment($coachingStatus['session1']['date']);
        }

        if(isset($coachingStatus['session2'])
            && isset($coachingStatus['session2']['total_minutes'])
            && $coachingStatus['session2']['total_minutes'] > 2) {
            $session2Status->setStatus(ComplianceStatus::COMPLIANT);
            if(isset($coachingStatus['session2']['date'])) $session2Status->setComment($coachingStatus['session2']['date']);
        }


        if($session1Status->getStatus() == ComplianceStatus::COMPLIANT
            && $session2Status->getStatus() == ComplianceStatus::COMPLIANT) {
            $sessionOverallStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

        $extraPoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                    $extraPoints += $viewExtraPoints;
                }
            }
        }

        $requiredCompliant = true;
        $requiredNumCompliant = 0;
        foreach($requiredGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if(!$viewStatus->isCompliant()) {
                $requiredCompliant = false;
            } else {
                $requiredNumCompliant++;
            }
        }

        if($requiredCompliant) {
            $requiredGroupStatus->setStatus(ComplianceViewGroupStatus::COMPLIANT);
        } elseif (!$requiredCompliant && $requiredNumCompliant > 0) {
            $requiredGroupStatus->setStatus(ComplianceViewGroupStatus::PARTIALLY_COMPLIANT);
        } else {
            $requiredGroupStatus->setStatus(ComplianceViewGroupStatus::NOT_COMPLIANT);
        }

        $thisRequirements = $status->getComplianceViewGroupStatus('Requirements');
        $wellnessStatus = $status->getComplianceViewGroupStatus('wellness_campaign');
        $thisPrevention = $status->getComplianceViewGroupStatus('required');

        $thisYearTotalPoints = $thisRequirements->getPoints() + $wellnessStatus->getPoints();

        $status->setPoints($thisYearTotalPoints);

        $status->setAttribute('total_points_ignoring_alternatives', $thisYearTotalPoints - $extraPoints);

        if(!isset($this->options['in_spouse']) || !$this->options['in_spouse']) {
            $priorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($user, '2016-08-01', '2017-07-30');

            $status->setAttribute('prior_status', $priorStatus);
        }


        if($thisPrevention->getStatus() == ComplianceViewGroupStatus::COMPLIANT) {
            if($user->gender == 'M') {
                if($thisYearTotalPoints >= 9) $status->setStatus(ComplianceStatus::COMPLIANT);
            } else{
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        }

    }

    public function getPriorYearBiometricsComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        $program = $this->cloneForEvaluation($startDate, $endDate);

        $program->evaluateOverall = false;

        $program->setActiveUser($user);

        return $program->getStatus();
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

    public static function getFiveForFiveIgnoredUsers()
    {
        return array(
            2718180,
            2718138,
            2718010,
            2977481,
            2917457,
            2718305,
            2718002,
            2718003,
            2718012,
            2718023,
            2718024,
            2718027,
            2718028,
            2718056,
            2718062,
            2988722,
            2757632,
            2913110,
            2718074,
            2718075,
            2718077,
            2718101,
            2903654,
            2732365,
            2718106,
            2870510,
            2927153,
            2916176,
            2718129,
            2912303,
            2721988,
            2718156,
            2718157,
            2903648,
            2718167,
            2774577,
            2718177,
            2718178,
            2718183,
            2763860,
            2718193,
            2718195,
            2718196,
            2718203,
            2718209,
            2926250,
            2732373,
            2718229,
            2718231,
            2988743,
            2718241,
            2718242,
            2718253,
            2718268,
            2718274,
            2718277,
            2791543,
            2718293,
            2718295,
            2718298,
            2718300,
            2904593,
            2718312,
            2718320,
            2911859,
            2989577,
            2718152,
            2988719
        );
    }

    protected $evaluateOverall = true;
    const XRAYTECHNOLOGIES_EMPLOYEE_2018_RECORD_ID = 1257;
}

class XrayTechnologiesEmployee2018Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setPageHeading('My LiGHT Spectrum (<a href="/compliance_programs?id=527">View LiGHT Activities</a>)');
        $this->showTotalCompliance(true);
        $this->setPointsHeading('Points');
        $this->resultHeading = 'Result';
        $this->setShowLegend(false);
    }

    protected function pointBasedViewStatusMatchesMapping($viewStatus, $mapping)
    {
        if($viewStatus->getComplianceView()->getName() == 'bf_bmi') {
            return $viewStatus->getPoints() == $mapping;
        } else {
            return parent::pointBasedViewStatusMatchesMapping($viewStatus, $mapping);
        }
    }

    protected function getStatusMappings(ComplianceView $view)
    {
        $mappings = parent::getStatusMappings($view);

        if($view->getName() == 'bf_bmi') {
            return array(
                4 => $mappings[ComplianceStatus::COMPLIANT],
                3 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                2 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                1 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                0 => $mappings[ComplianceStatus::NOT_COMPLIANT]
            );
        } else {
            return $mappings;
        }
    }

    public function printCSS()
    {
        parent::printCSS();

        ?>
        <style type="text/css">
            .status-1 .your_points, .status-3 .your_points {
                background-color:red;
                color:#FFF;
            }

            .status-2 .your_points {
                background-color:yellow;
                color:#000;
            }

            .status-4 .your_points {
                background-color:green;
                color:#FFF;
            }

            #legendEntry3 {
                display:none;
            }

            td.summary {
                color:#345A92;
            }

            .phipTable .links {
                width:130px;
            }

            .pointBased .summary, .pointBased .points {
                font-size: 0.9em !important;
            }
        </style>
        <?php
    }

    protected function printTotalStatus()
    {

    }

    protected function printCustomRows(ComplianceProgramStatus $status)
    {
        ?>
        <tr class="headerRow">
            <th colspan="2">Total Biometric Points (650 possible)</th>
            <td id="spectrum_points"><?php echo $status->getPoints() ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">Total LiGHT Activities Points (350 possible)</th>
            <td id="activities_points"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My Total LiGHT Spectrum Points (1000 possible)</th>
            <td id="combined_points"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <?php
    }

    public function printReport(\ComplianceProgramStatus $status)
    {
        ?>
        <style type="text/css">
            .totalRow.group-requirements { display:none; }

        </style>
        <script type="text/javascript">
            $(function() {
                $.get('/compliance_programs?id=527', function(fullPage) {
                    var $page = $(fullPage);

                    var activityPoints = parseInt($page.find('#activity_points').html(), 10);

                    $('#activities_points').html(activityPoints);

                    $('#combined_points').html(
                        '' + (activityPoints + <?php echo $status->getPoints() ?>)
                    );
                });

                $('.show_more').toggle(function(){
                    $('.hide').show();
                    $('.show_more a').html('Less...');
                }, function(){
                    $('.hide').hide();
                    $('.show_more a').html('More...');
                });
            });
        </script>
        <?php
        parent::printReport($status);
    }

    public function printClientNote()
    {

    }

    public function printClientMessage()
    {
        ?>
        <style type="text/css">
            .statusRow {
                background:#FFFFFF;
            }
            #legendEntry3, #legendEntry2 {
                display:none;
            }

            #sample_table {
                border-collapse: collapse;
            }

            #sample_table tr td{
                border: 1px solid #000000;
            }

            #sample_table tr th, #sample_table tr td{
                width: 100px;
            }

            .phipTable {
                border:0;
                margin-bottom:100px;
            }

            .phipTable tr {
                margin-bottom:0;
            }

            .headerRow {
                border-top:2px solid #D7D7D7;
            }

            #steps p {
                margin-bottom:0;
            }
        </style>

        <img src=" /resources/6479/icon_2016.png " style="width:200px; position:relative; top:-160px; left:580px;"/>

        <p><strong>By completing the following steps in 2017, associates will be
                eligible for a premium reduction in their health plan effective
                01/01/2018.</strong>
        </p>
        <p><strong>Step 1</strong>- GTI-insured associates and their insured spouse must complete an HRA, on-site
            health screening, private one-on-one consultation and 4 follow-up coaching sessions (if they qualify and
            are notified by Circle Wellness). Screenings will be scheduled beginning in June at all three locations.
            Onsite consultations will follow.
        </p>
        <p><strong>Step 2</strong>- The number of points the associate earns on their 2017 report
            card will determine the premium reduction for 2018. Spouses need only
            to complete the requirements in step 1.</p>
        <ul>
            <li>Level 1: Score 10-15 points or have a two year improvement over the previous year’s results*</li>
            <li>Level 2: Score 6-9 points</li>
            <li>Level 3: Score 1-5 points</li>
        </ul>
        <p>*If you have a medical reason for not being able to reach one or more
            of the requirements, you can contact Kamara Means in Human
            Resources to discuss a reasonable alternative.
        </p>

        <?php
    }
}

class XrayTechnologiesEmployee2018WMS2Printer implements ComplianceProgramReportPrinter
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
        $escaper = new hpn\common\text\Escaper;

        $priorStatus = $status->getAttribute('prior_status');
        $lastYearRequirementsGroup = $priorStatus->getComplianceViewGroupStatus('Requirements');

        $requiredStatus = $status->getComplianceViewGroupStatus('required');
        $requirementsStatus = $status->getComplianceViewGroupStatus('Requirements');
        $wellnessStatus = $status->getComplianceViewGroupStatus('wellness_campaign');

        $thisYearTotalPoints = $requirementsStatus->getPoints() + $wellnessStatus->getPoints();
        $biometricStatus = $thisYearTotalPoints >=9 ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $that = $this;

        $lastYearStatus = array();

        foreach($lastYearRequirementsGroup->getComplianceViewStatuses() as $viewStatus) {
            $lastYearStatus[$viewStatus->getComplianceView()->getName()] = $viewStatus;
        }

        $classForStatus = function($status) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $classForRequirements = function($points) {
            if($points >= 9) {
                return 'success';
            } else if ($points > 0) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $textForStatus = function($status) {
            if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
                return 'Done';
            } else if ($status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                $coachingViews = array(
                    'coaching_overall',
                    'coaching_session1',
                    'coaching_session2'
                );

                if($status->getComment() == 'Not Required') {
                    return 'Not Required';
                } elseif(in_array($status->getComplianceView()->getName(), $coachingViews)) {
                    return 'Pending';
                } else {
                    return 'Not Required';
                }

            } else {
                return 'Not Done';
            }
        };

        $textForSpouseStatus = function($status, $isCoaching = false, $comment = null) {
            if($comment !== null && $comment == 'Not Required') {
                return $comment;
            }

            if ($status == ComplianceStatus::COMPLIANT) {
                return 'Done';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                if($isCoaching) {
                    return 'Pending';
                } else {
                    return 'Not Required';
                }
            } else if ($status == ComplianceStatus::NOT_COMPLIANT) {
                return 'Not Done';
            }
        };

        $circle = function($status, $text, $sectionName) use ($classForStatus) {
            $class = $status === 'GVT' ? 'GVT' : $classForStatus($status);
            ob_start();
            ?>
            <div class="circle-range">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>"
                    <?php echo $sectionName == 'total_status' && $status != ComplianceStatus::COMPLIANT ? 'style="background-color: #CCC;"' : '' ?>>
                    <div style="font-size: 1.2em; line-height: 1.0em;"><?php echo $text ?></div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };

        $circle2 = function($color) {
            ob_start();
            ?>
            <div style="width:30px; height: 30px; border-radius: 15px; background-color: <?php echo $color ?>;"></div>
            <?php

            return ob_get_clean();
        };

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor, $classForStatus, $textForStatus, $textForSpouseStatus, $lastYearStatus, $that) {
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
                                        <?php elseif ($viewStatus->getComplianceView()->getName() == 'comply_with_total_hdl_cholesterol_ratio_screening_test'): ?>
                                            <?php if($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT && $sstatus == ComplianceStatus::NOT_COMPLIANT) : ?>
                                                <span class="label label-<?php echo $class[ComplianceStatus::NOT_COMPLIANT] ?>"><?php echo $viewStatus->getPoints() ?></span>
                                            <?php elseif($viewStatus->getStatus() == $sstatus) : ?>
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
                                                <span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $viewStatus->getComment() ?></span>
                                            <?php endif ?>
                                        <?php elseif ($viewStatus->getComplianceView()->getName() == 'comply_with_total_hdl_cholesterol_ratio_screening_test'): ?>
                                            <?php if($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT && $sstatus == ComplianceStatus::NOT_COMPLIANT) : ?>
                                                <span class="label label-<?php echo $class[ComplianceStatus::NOT_COMPLIANT] ?>"><?php echo $viewStatus->getComment() ?></span>
                                            <?php elseif($viewStatus->getStatus() == $sstatus) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $viewStatus->getComment() ?></span>
                                            <?php endif ?>
                                        <?php else : ?>
                                            <?php if($viewStatus->getStatus() == $sstatus) : ?>
                                                <span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $viewStatus->getComment() ?></span>
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
                        <th class="points">deadline</th>
                        <th class="text-center">Links</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1 ?>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $class = $classForStatus($viewStatus->getStatus()) ?>
                        <?php $statusText = $textForStatus($viewStatus) ?>
                        <?php if($viewStatus->getComplianceView()->getName() == 'spouse_participation') : ?>
                            <?php if($viewStatus->getAttribute('has_spouse')) : ?>

                                <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                                    <td class="name">
                                        <?php echo $i ?>.
                                        <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                                    </td>
                                    <td class="points <?php echo $class ?>" <?php echo $class == 'info' ? 'style="background-color: #CCC;"' : ''?>><?php echo $statusText ?>
                                    </td>
                                    <td></td>
                                    <td class="links text-center">
                                        <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                            <div><?php echo $link->getHTML() ?></div>
                                        <?php endforeach ?>
                                    </td>
                                </tr>

                                <tr class="view-spouse_participation_hra">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• Spouse HRA</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_hra_status')) ?>">
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_hra_status')) ?>
                                    </td>
                                    <td>11/30/2017</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>

                                <tr class="view-spouse_participation_screening">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• Spouse Screening</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_screening_status')) ?>">
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_screening_status')) ?>
                                    </td>
                                    <td>11/30/2017</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>


                                <tr class="view-spouse_participation_coaching">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• Spouse Coaching</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_coaching_status')) ?>"
                                        <?php echo $classForStatus($viewStatus->getAttribute('spouse_coaching_status')) == 'info' ? 'style="background-color: #CCC;"' : ''?>>
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_coaching_status'), true, $viewStatus->getAttribute('spouse_coaching_comment')) ?>
                                    </td>
                                    <td>05/31/2018</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>

                            <?php endif ?>
                        <?php else : ?>
                            <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                                <td class="name">
                                    <?php echo $i ?>.
                                    <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                                </td>
                                <td class="points <?php echo $class ?>" <?php echo $class == 'info' ? 'style="background-color: #CCC;"' : ''?>><?php echo $statusText ?>
                                </td>
                                <td><?php echo $viewStatus->getComplianceView()->getAttribute('deadline') ?></td>
                                <td class="links text-center">
                                    <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                        <div><?php echo $link->getHTML() ?></div>
                                    <?php endforeach ?>
                                </td>
                            </tr>
                        <?php endif ?>

                        <?php $i++ ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
            <?php
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $classForStatus, $classForRequirements, $textForStatus, $groupTable) {
            ob_start();

            $numOfViews = 0;
            $numOfCompliant = 0;
            foreach($group->getComplianceViewStatuses() as $viewStatus) {
                $numOfViews++;
                if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numOfCompliant++;
            }

            if ($group->getComplianceViewGroup()->getName() == 'Requirements' || $group->getComplianceViewGroup()->getName() == 'wellness_campaign') : ?>

                <?php $points = $group->getPoints(); ?>
                <?php $target = $group->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?>
                <?php $pct = $points / $target; ?>
                <?php $class = $classForRequirements($points); ?>
                <tr class="picker closed">
                    <td class="name">
                        <?php echo $name ?>
                        <div class="triangle"></div>
                    </td>
                    <td class="points <?php echo $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? 'success' : '' ?> ">
                        <strong><?php echo $target ?></strong><br/>
                        points
                    </td>
                    <td class="points <?php echo $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? $class : ''?>">
                        <strong><?php echo $points ?></strong><br/>
                        points
                    </td>
                    <td class="pct">
                        <div class="pgrs">
                            <div class="bar <?php echo $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? $class : ''?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                        </div>
                    </td>
                </tr>
                <tr class="details closed">
                    <td colspan="4">
                        <?php echo $groupTable($group) ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php $pct = $numOfCompliant / $numOfViews; ?>
                <?php $class = $classForStatus($group->getStatus()); ?>
                <?php $statusText = $textForStatus($group); ?>
                <tr class="picker closed">
                    <td class="name">
                        <?php echo $name ?>
                        <div class="triangle"></div>
                    </td>
                    <td class="points success">Done
                    </td>
                    <td class="points <?php echo $class ?>"><?php echo $statusText ?>
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

            <?php endif ?>

            <?php

            return ob_get_clean();
        };
        $totalStatus = ComplianceStatus::NOT_COMPLIANT;

        if ($requiredStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $requirementsStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
        ) {
            $totalStatus = ComplianceStatus::COMPLIANT;
        } else if ($requiredStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            || $requirementsStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT) {
            $totalStatus = ComplianceStatus::PARTIALLY_COMPLIANT;
        }

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
                font-size: 1.4em;
            }

            .point-totals th {
                text-align: right;
                padding-right: 10px;
            }

            .point-totals td {
                text-align: center;
            }

        </style>

        <div class="row">

            <div class="row">
                <div class="col-md-12" <?php echo !sfConfig::get('app_wms2') ? 'style="margin-top: 150px;"' : '' ?>>
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1 activity">
                                            <?php echo $circle(
                                                $requiredStatus->getStatus(),
                                                '<br/>Prevention <br/>Events',
                                                'required'
                                            ) ?>
                                            <br/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <?php echo $circle(
                                                $biometricStatus,
                                                '<br/>Biometric<br/>Points<br/><br/><span class="circle-points">'.$thisYearTotalPoints. '</span>',
                                                'Requirements'
                                            ) ?>
                                            <br/>
                                            <strong><?php echo $requirementsStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></strong> points possible
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <p><a href="#" id="more-info-toggle">Less...</a></span></p>

                <div id="more-info">
                    <div class="row">
                        <div class="col-md-12">
                            <p>
                                Welcome to the 2018 X-Ray Industries Wellness Program. In order to qualify for the $600 incentive in 2018,
                                employees who are on the medical plan and are participating in wellness must meet 4 objectives to qualify
                                for the 2018 incentive. Spouses on the medical plan will only be required to meet requirements 1, 2 and 3 to qualify for the 2018 incentive.
                            </p>
                            <ul style="list-style: decimal">
                                <li>Complete an online Health Risk Assessment by <strong>November 30, 2017</strong>.</li>
                                <li>Complete an onsite Health Screening by <strong>November 30, 2017</strong>.*</li>
                                <li><strong>If notified by Circle Wellness</strong>, you must complete 2 coaching contacts by May 31, 2018 or you will lose the incentive effective June 1, 2018.</li>
                                <li><strong>Employees only</strong>: Earn a total of 9 or more points on the report card or satisfy the reasonable alternative.</li>
                            </ul>
                            <p>
                                Once your lab values are showing on your report card, a reasonable alternative will be offered
                                if you weren’t able to achieve these requirements. You will have a choice of two reasonable alternatives:
                            </p>
                            <p>
                                • Complete the ELearning lessons through the links on your report card. Once you complete all of the lessons
                                for a particular biometric measure, your report card will populate with the maximum points for that measure.
                                ELearning lessons must be completed by December 8, 2017. You must have a total of nine points to qualify
                                for the incentive. Please contact the Circle Wellness Hotline if you have any questions: 1-866-682-3020 ext. 204.
                                <br/><br/>
                                OR<br/><br/>
                                • Bring a <a href="/resources/9615/2017 X-RAY Physician Exception Form.pdf" target="_blank">Physician Exception Form</a>
                                to your physician to complete. The form must be returned by December 8, 2017.
                            </p>

                            <p>
                                If an onsite screening is not available at your location you may request a packet from HR to
                                bring to your physician to have your health screening done. You can also call 866.682.3020 ext.
                                204 to request a packet to visit a lab location.
                            </p>
                            <p>
                                If you are pregnant or 6 months postpartum you may complete a
                                <a href="/resources/9618/2017 X-RAY Pregnancy Exception Form.pdf" target="_blank">Pregnancy Exception Form</a>
                                and submit it to Circle Wellness to obtain credit for the body measurements (BMI and Body Fat).
                            </p>

                            <p>
                                The report card outlines your requirements and will keep track of your progress throughout
                                the program. If you or your X-Ray Industries insured spouse choose not to complete the requirements,
                                you will not be eligible for the incentive.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Status</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Prevention Events', $status->getComplianceViewGroupStatus('required')) ?>
                    <?php echo $tableRow('Biometric Measures', $status->getComplianceViewGroupStatus('Requirements')) ?>
                    <?php echo $tableRow('Wellness Campaign Points', $status->getComplianceViewGroupStatus('wellness_campaign')) ?>
                    </tbody>

                    <tr class="point-totals">
                        <th>2017 Point Totals</th>
                        <td></td>
                        <td><?php echo $thisYearTotalPoints ?></td>
                    </tr>
                    <tr class="point-totals">
                        <th>2016 Point Totals</th>
                        <td></td>
                        <td><?php echo $lastYearRequirementsGroup->getPoints() ?></td>
                    </tr>
                </table>
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

                $('.view-coaching_session1').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 1</span>');
                $('.view-coaching_session2').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 2</span>');

                $('.view-spouse_participation').children(':eq(0)').html('5. Spouse Compliance');
            });
        </script>
        <?php
    }
}

