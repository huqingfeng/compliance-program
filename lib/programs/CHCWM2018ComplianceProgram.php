<?php

use hpn\steel\query\SelectQuery;

class CHCWM2018CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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
                'required_fields'  => array('cholesterol', 'glucose')
            )
        );

        return $data;
    }
}

class CHCWM2018WatchVideosComplianceView extends PlaceHolderComplianceView
{
    public function getStatus(User $user)
    {
        $status = parent::getStatus($user);

        if($user->getNewestDataRecord('chcwm_watch_video_2018')->exists()) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

        return $status;
    }
}


class CHCWM2018ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserLocation(true);
        $printer->setShowUserFields(true, true, true, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);
        $printer->setShowUserContactFields(true, null, true);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = array();

            $priorStatus = $status->getAttribute('prior_status');

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

            $data['Points Compliant'] = $user->getRelationshipType() == Relationship::SPOUSE || $status->getPoints() >= 9 || $status->getPoints() - $priorStatus->getPoints() > 2 ? 'Yes' : 'No';
            $data['Total Points'] = $status->getPoints();
            $data['Total Points (Ignoring Alternatives)'] = $status->getAttribute('total_points_ignoring_alternatives');
            $data['Total Points 2017'] = $priorStatus->getPoints();
            $data['Point Difference'] = $data['Total Points'] - $data['Total Points 2017'];
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
        return new CHCWM2018WMS2Printer();
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

        $screeningStartDate = '2017-05-01';
        $screeningEndDate = '2018-06-30';

        $preventionEventGroup = new ComplianceViewGroup('required', 'My Requirements');

        $hraView = new CompleteHRAComplianceView($programStart, '2018-06-30');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $hraView->setReportName('Online Health Risk Assessment (HRA) questionnaire');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->setAttribute('deadline', '6/30/2018');
        $hraView->addLink(new Link('Take HRA', sfConfig::get('app_wms2') ? '/compliance/gti-2016/my-health' : '/content/989'));
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CHCWM2018CompleteScreeningComplianceView($screeningStartDate, $screeningEndDate);
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $screeningView->setReportName('Wellness Screening (For more information, <a href="/resources/8821/Fingerstick Health Screening.pdf">click here</a>)');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Screening Sign-Up', '/compliance/cancerhematologycenters-2016/schedule'));
        $screeningView->setAttribute('deadline', '6/30/2018');
        $preventionEventGroup->addComplianceView($screeningView);

        $primaryCare = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $primaryCare->setName('primary_care');
        $primaryCare->setReportName('Annual Medical Physical');
        $primaryCare->setAttribute('deadline', '6/30/2018');
        $preventionEventGroup->addComplianceView($primaryCare);

        $dentalVisit = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $dentalVisit->setName('dental_visit');
        $dentalVisit->setReportName('Annual Dental Checkup');
        $dentalVisit->setAttribute('deadline', '6/30/2018');
        $dentalVisit->addLink(new Link('Details', '/resources/9956/2018 Dental Preventative Form.pdf'));
        $preventionEventGroup->addComplianceView($dentalVisit);

        $eLearningLessonsView = new CompleteELearningLessonsComplianceView($programStart, $screeningEndDate);
        $eLearningLessonsView->setNumberRequired(1);
        $eLearningLessonsView->setReportName('Complete 1 E-Learning Lesson');
        $eLearningLessonsView->setName('elearning');
        $eLearningLessonsView->setAttribute('deadline', '6/30/2018');
        $eLearningLessonsView->emptyLinks();
        $eLearningLessonsView->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $preventionEventGroup->addComplianceView($eLearningLessonsView);

        $coachingOverall = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingOverall->setName('coaching_overall');
        $coachingOverall->setReportName('Complete 4 Coaching Sessions (if applicable)');
        $coachingOverall->setAttribute('deadline', '12/31/2018');
        $coachingOverall->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $notRequired = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('not_required')
                ->from('compliance_view_status_overrides')
                ->where('user_id = ?', array($user->id))
                ->andWhere('compliance_program_record_id = ?', array(CHCWM2018ComplianceProgram::CHCWM_2018_RECORD_ID))
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
        $coachingSession1->setAttribute('deadline', '12/31/2018');
        $coachingSession1->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $notRequired = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('not_required')
                ->from('compliance_view_status_overrides')
                ->where('user_id = ?', array($user->id))
                ->andWhere('compliance_program_record_id = ?', array(CHCWM2018ComplianceProgram::CHCWM_2018_RECORD_ID))
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
        $coachingSession2->setAttribute('deadline', '12/31/2018');
        $coachingSession2->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $notRequired = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('not_required')
                ->from('compliance_view_status_overrides')
                ->where('user_id = ?', array($user->id))
                ->andWhere('compliance_program_record_id = ?', array(CHCWM2018ComplianceProgram::CHCWM_2018_RECORD_ID))
                ->andWhere('compliance_view_name = ?', array('coaching_session2'))
                ->execute();

            if($notRequired) {
                $status->setComment('Not Required');
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession2);

        $coachingSession3 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession3->setName('coaching_session3');
        $coachingSession3->setReportName('Session 3');
        $coachingSession3->setAttribute('deadline', '12/31/2018');
        $coachingSession3->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $notRequired = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('not_required')
                ->from('compliance_view_status_overrides')
                ->where('user_id = ?', array($user->id))
                ->andWhere('compliance_program_record_id = ?', array(CHCWM2018ComplianceProgram::CHCWM_2018_RECORD_ID))
                ->andWhere('compliance_view_name = ?', array('coaching_session3'))
                ->execute();

            if($notRequired) {
                $status->setComment('Not Required');
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession3);

        $coachingSession4 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession4->setName('coaching_session4');
        $coachingSession4->setReportName('Session 4');
        $coachingSession4->setAttribute('deadline', '12/31/2018');
        $coachingSession4->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $notRequired = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('not_required')
                ->from('compliance_view_status_overrides')
                ->where('user_id = ?', array($user->id))
                ->andWhere('compliance_program_record_id = ?', array(CHCWM2018ComplianceProgram::CHCWM_2018_RECORD_ID))
                ->andWhere('compliance_view_name = ?', array('coaching_session4'))
                ->execute();

            if($notRequired) {
                $status->setComment('Not Required');
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession4);

        $spouseParticipation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $spouseParticipation->setName('spouse_participation');
        $spouseParticipation->setReportName('Spouse Compliance');
        $spouseParticipation->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($user->getRelationshipType() == Relationship::EMPLOYEE) {
                $spouseUser = $user->getSpouseUser();
            } elseif($user->getRelationshipType() == Relationship::SPOUSE) {
                $spouseUser = $user->getEmployeeUser();
            }

            if(!$spouseUser || (isset($this->options['in_spouse']) && $this->options['in_spouse']))  {
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            } else {
                $program = ComplianceProgramRecordTable::getInstance()->find(CHCWM2018ComplianceProgram::CHCWM_2018_RECORD_ID)->getComplianceProgram(array('in_spouse' => true));
                $program->setActiveUser($spouseUser);
                $overallStatus = $program->getStatus();

                $requiredGroupStatus = $overallStatus->getComplianceViewGroupStatus('required');
                $hraStatus = $requiredGroupStatus->getComplianceViewStatus('hra');
                $screeningStatus = $requiredGroupStatus->getComplianceViewStatus('screening');
                $primaryCareStatus = $requiredGroupStatus->getComplianceViewStatus('primary_care');
                $dentalVisitStatus = $requiredGroupStatus->getComplianceViewStatus('dental_visit');
                $coachingStatus = $requiredGroupStatus->getComplianceViewStatus('coaching_overall');

                $numCompliant = 0;
                $allCompliant = true;
                $allNotRequired = true;

                $status->setAttribute('has_spouse', true);
                if($user->getRelationshipType() == Relationship::EMPLOYEE) {
                    $status->setAttribute('spouse_text', 'Spouse');
                } elseif($user->getRelationshipType() == Relationship::SPOUSE) {
                    $status->setAttribute('spouse_text', 'Employee');
                }

                $status->setAttribute('spouse_hra_status', $hraStatus->getStatus());
                $status->setAttribute('spouse_hra_comment', $hraStatus->getComment());
                if($hraStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                    $allCompliant = false;
                } else {
                    $numCompliant++;
                }
                if($hraStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                    $allNotRequired = false;
                }

                $status->setAttribute('spouse_screening_status', $screeningStatus->getStatus());
                $status->setAttribute('spouse_screening_comment', $screeningStatus->getComment());
                if($screeningStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                    $allCompliant = false;
                } else {
                    $numCompliant++;
                }
                if($screeningStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                    $allNotRequired = false;
                }

                $status->setAttribute('spouse_primary_care_status', $primaryCareStatus->getStatus());
                $status->setAttribute('spouse_primary_care_comment', $primaryCareStatus->getComment());
                if($primaryCareStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                    $allCompliant = false;
                } else {
                    $numCompliant++;
                }
                if($primaryCareStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                    $allNotRequired = false;
                }


                $status->setAttribute('spouse_dental_visit_status', $dentalVisitStatus->getStatus());
                $status->setAttribute('spouse_dental_visit_comment', $dentalVisitStatus->getComment());
                if($dentalVisitStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                    $allCompliant = false;
                } else {
                    $numCompliant++;
                }
                if($dentalVisitStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                    $allNotRequired = false;
                }


                $status->setAttribute('spouse_coaching_status', $coachingStatus->getStatus());
                $status->setAttribute('spouse_coaching_comment', $coachingStatus->getComment());
                if($coachingStatus->getStatus() != ComplianceStatus::COMPLIANT && $coachingStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                    $allCompliant = false;
                } else {
                    if ($coachingStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) $numCompliant++;
                }
                if($coachingStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                    $allNotRequired = false;
                }

                if($allNotRequired) {
                    $status->setStatus(ComplianceStatus::NA_COMPLIANT);
                } elseif($allCompliant) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } elseif( $numCompliant > 0) {
                    $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                }
            }

        });
        $preventionEventGroup->addComplianceView($spouseParticipation, true);

        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements', 'My Biometric Results Points');
        $biometricsGroup->setPointsRequiredForCompliance(0);

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

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStartDate, $screeningEndDate);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($screeningStartDate, $screeningEndDate);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->overrideTestRowData(null, null, 149, 199.99);
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥200');
        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($screeningStartDate, $screeningEndDate);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->overrideTestRowData(50, 70, 99.99, 125);
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-69.99 or 100-125');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');
        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($screeningStartDate, $screeningEndDate);
        $cholesterolView->setIndicateSelfReportedResults(false);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->overrideTestRowData(null, 100, 199, null);
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');
        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($screeningStartDate, $screeningEndDate);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');
        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new GVT2015ComplyWithBodyFatBMIScreeningTestComplianceView($screeningStartDate, $screeningEndDate);
        $bodyFatBMIView->setIndicateSelfReportedResults(false);
        $bodyFatBMIView->setBmiView($this->getBmiView($screeningStartDate, $screeningEndDate));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($screeningStartDate, $screeningEndDate));
        $bodyFatBMIView->setComplianceStatusPointMapper(new GVT2015BFMapper());
        $bodyFatBMIView->setMaximumNumberOfPoints(4);
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);

        $biometricsGroup->addComplianceView($bodyFatBMIView);


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

        $this->addComplianceViewGroup($biometricsGroup);
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

        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');
        $sessionOverallStatus = $requiredGroupStatus->getComplianceViewStatus('coaching_overall');
        $session1Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session1');
        $session2Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session2');
        $session3Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session3');
        $session4Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session4');

        $coachingStatus = array();

        $coachingStartDate = '2018-01-01';
        $coachingEndDate = '2018-12-31';

        $session = CoachingSessionTable::getInstance()->findMostRecentSessionForUser($status->getUser());
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

        if(isset($coachingStatus['session3'])
            && isset($coachingStatus['session3']['total_minutes'])
            && $coachingStatus['session3']['total_minutes'] > 2) {
            $session3Status->setStatus(ComplianceStatus::COMPLIANT);
            if(isset($coachingStatus['session3']['date'])) $session3Status->setComment($coachingStatus['session3']['date']);
        }

        if(isset($coachingStatus['session4'])
            && isset($coachingStatus['session4']['total_minutes'])
            && $coachingStatus['session4']['total_minutes'] > 2) {
            $session4Status->setStatus(ComplianceStatus::COMPLIANT);
            if(isset($coachingStatus['session4']['date'])) $session4Status->setComment($coachingStatus['session4']['date']);
        }


        if($session1Status->getStatus() == ComplianceStatus::COMPLIANT
            && $session2Status->getStatus() == ComplianceStatus::COMPLIANT
            && $session3Status->getStatus() == ComplianceStatus::COMPLIANT
            && $session4Status->getStatus() == ComplianceStatus::COMPLIANT) {
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
        $thisPrevention = $status->getComplianceViewGroupStatus('required');

        $thisYearTotalPoints = $thisRequirements->getPoints();

        if($thisYearTotalPoints >= 9) {
            $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $thisRequirements->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        $status->setPoints($thisYearTotalPoints);

        $status->setAttribute('total_points_ignoring_alternatives', $thisYearTotalPoints - $extraPoints);

        $priorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($status->getUser(), '2017-01-01', '2017-12-31');

        $status->setAttribute('prior_status', $priorStatus);

        if($thisPrevention->getStatus() == ComplianceViewGroupStatus::COMPLIANT & $thisYearTotalPoints >= 9) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

    }

    public function getPriorYearBiometricsComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        if($user->getRelationshipType() == Relationship::SPOUSE) {
            $record = ComplianceProgramRecordTable::getInstance()->find(CHCWM2018ComplianceProgram::CHCWM_2017_SPOUSE_RECORD_ID);
        } else {
            $record = ComplianceProgramRecordTable::getInstance()->find(CHCWM2018ComplianceProgram::CHCWM_2017_EMPLOYEE_RECORD_ID);
        }

        $program = $record->getComplianceProgram() ;

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

    public function getLocalActions()
    {
        return array(
            'watch_videos' => array($this, 'executeWatchVideo'),
            'redirect_report_card' => array($this, 'executeRedirectReportCard')
        );
    }

    public function executeWatchVideo($actions)
    {
        $user = $actions->getSessionUser();

        $user->getNewestDataRecord('chcwm_watch_video_2018', true);

        $actions->redirect('https://www.youtube.com/watch?v=iCVuly1tvpc');
    }

    public function executeRedirectReportCard($actions)
    {
        $actions->redirect(sprintf('/compliance_programs?id=%s', CHCWM2018ComplianceProgram::CHCWM_2018_RECORD_ID));
    }

    protected $evaluateOverall = true;
    const CHCWM_2018_RECORD_ID = 1301;
    const CHCWM_2017_EMPLOYEE_RECORD_ID = 982;
    const CHCWM_2017_SPOUSE_RECORD_ID = 979;
}

class CHCWM2018WMS2Printer implements ComplianceProgramReportPrinter
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

        $user = $status->getUser();

        $priorStatus = $status->getAttribute('prior_status');
        $lastYearRequirementsGroupStatus = $priorStatus->getComplianceViewGroupStatus('Requirements');
        $lastYearTotalPoints = $lastYearRequirementsGroupStatus->getPoints();

        $requiredStatus = $status->getComplianceViewGroupStatus('required');
        $requirementsStatus = $status->getComplianceViewGroupStatus('Requirements');

        $thisYearTotalPoints = $requirementsStatus->getPoints();
        $biometricStatus = $thisYearTotalPoints >=9 ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $that = $this;

        $lastYearStatus = array();

        foreach($lastYearRequirementsGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $lastYearStatus[$viewStatus->getComplianceView()->getName()] = $viewStatus;
        }

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

        $textForStatus = function($status) {
            if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
                return 'Done';
            } else if ($status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                $coachingViews = array(
                    'coaching_overall',
                    'coaching_session1',
                    'coaching_session2',
                    'coaching_session3',
                    'coaching_session4'
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

        $textForSpouseStatus = function($status, $comment, $isCoaching = false) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'Done';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                if($isCoaching) {
                    if($comment == 'Not Required') {
                        return 'Not Required';
                    } else {
                        return 'Pending';
                    }
                } else {
                    return 'Not Required';
                }
            } else if ($status == ComplianceStatus::NOT_COMPLIANT) {
                return 'Not Done';
            }
        };

        $circle = function($status, $text, $sectionName) use ($classForCircleStatus, $textForCircleStatus, $user) {
            $class = $status === 'GVT' ? 'GVT' : $classForCircleStatus($status);

            $style = '';
            if($user->getRelationshipType() == Relationship::SPOUSE && $sectionName == 'Requirements') {
                $style = 'style="background-color: #CCC;"';
            }


            ob_start();
            ?>
            <div class="circle-range">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>" <?php echo $style ?> >
                    <div style="font-size: 1.2em; line-height: 1.0em; margin-top:-10px;">
                        <?php echo $text ?>
                        <?php echo $textForCircleStatus($status, $sectionName) ?>
                    </div>
                </div>
            </div>
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
                                <?php $spouseText = $viewStatus->getAttribute('spouse_text') ?>

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
                                        <span style="padding-left: 20px;">• <?php echo $spouseText ?> Health Risk Assessment (HRA) questionnaire</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_hra_status')) ?>"
                                        <?php echo $classForStatus($viewStatus->getAttribute('spouse_hra_status')) == 'info' ? 'style="background-color: #CCC;"' : ''?>>
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_hra_status'), $viewStatus->getAttribute('spouse_hra_comment')) ?>
                                    </td>
                                    <td>6/30/2018</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>

                                <tr class="view-spouse_participation_screening">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• <?php echo $spouseText ?> Wellness Screening</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_screening_status')) ?>"
                                        <?php echo $classForStatus($viewStatus->getAttribute('spouse_screening_status')) == 'info' ? 'style="background-color: #CCC;"' : ''?>>
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_screening_status'), $viewStatus->getAttribute('spouse_screening_comment')) ?>
                                    </td>
                                    <td>6/30/2018</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>

                                <tr class="view-spouse_participation_screening">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• <?php echo $spouseText ?> Annual Medical Physical</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_primary_care_status')) ?>"
                                        <?php echo $classForStatus($viewStatus->getAttribute('spouse_primary_care_status')) == 'info' ? 'style="background-color: #CCC;"' : ''?>>
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_primary_care_status'), $viewStatus->getAttribute('spouse_primary_care_comment')) ?>
                                    </td>
                                    <td>6/30/2018</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>

                                <tr class="view-spouse_participation_screening">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• <?php echo $spouseText ?> Annual Dental Checkup</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_dental_visit_status')) ?>"
                                        <?php echo $classForStatus($viewStatus->getAttribute('spouse_dental_visit_status')) == 'info' ? 'style="background-color: #CCC;"' : ''?>>
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_dental_visit_status'), $viewStatus->getAttribute('spouse_dental_visit_comment')) ?>
                                    </td>
                                    <td>6/30/2018</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>

                                <tr class="view-spouse_participation_coaching">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• <?php echo $spouseText ?> Coaching</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_coaching_status')) ?>"
                                        <?php echo $classForStatus($viewStatus->getAttribute('spouse_coaching_status')) == 'info' ? 'style="background-color: #CCC;"' : ''?>>
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_coaching_status'), $viewStatus->getAttribute('spouse_coaching_comment'), true) ?>
                                    </td>
                                    <td>12/31/2018</td>
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

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $classForStatus, $textForStatus, $groupTable) {
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
                <?php $class = $classFor($pct); ?>
                <tr class="picker closed">
                    <td class="name">
                        <?php echo $name ?>
                        <div class="triangle"></div>
                    </td>
                    <td class="points <?php echo $group->getComplianceViewGroup()->getName() != 'Requirements' && $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? 'success' : '' ?> ">
                        <strong><?php echo $target ?></strong><br/>
                        points
                    </td>
                    <td class="points <?php echo $group->getComplianceViewGroup()->getName() != 'Requirements' && $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? $class : ''?>">
                        <strong><?php echo $points ?></strong><br/>
                        points
                    </td>
                    <td class="pct">
                        <div class="pgrs">
                            <div class="bar <?php echo $group->getComplianceViewGroup()->getName() != 'Requirements' && $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? $class : '' ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
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

            .hide {
                display: none;
            }

            #option_one_title, #option_two_title {
                background:none!important;
                border:none;
                padding:0!important;
                font: inherit;
                color: #2196f3;
                cursor: pointer;
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
                                        <div class="col-xs-8 col-xs-offset-2 col-md-12 col-md-offset-1 activity">
                                            <?php echo $circle(
                                                $requiredStatus->getStatus(),
                                                '<br/>My <br/><div style="margin-top:5px; margin-left: -8px">Requirements</div>',
                                                'required'
                                            ) ?>
                                            <br/>
                                        </div>
                                    </div>
                                </div>

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
                                                '<br/>My <br/>Biometric <br/>Results <br/>Points<br/><br/><span class="circle-points">'.$thisYearTotalPoints. '</span>',
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
                                Welcome to the <strong>CHCWM Wellness Together</strong> program for 2018. The goal of this
                                program is to encourage employees and their spouses to be aware of their health risks and
                                to complete recommended preventative testing. <strong>Wellness Together</strong> is available
                                to all employees and spouses who are on the CHCWM medical insurance plan. Employees <u>and
                                spouses</u> must complete the requirements detailed below to take advantage of the wellness
                                program benefits, which include a $650 annual discount on medical insurance premiums ($25 per paycheck),
                                opportunities to participate in Wellness Challenges, and a
                                <a href="/resources/9943/CHCWM Fitness Form_2018W.pdf" target="_blank">$180 reimbursement for 24
                                    visits per month to approved Fitness Centers</a>. This report card outlines your
                                requirements and will help you keep track of your progress throughout the program. If you
                                choose not to complete the requirements, you will not be eligible for the wellness program benefits.
                            </p>


                            <p>There are two options for meeting the requirements this year. Please select one below to view your requirements. </p>

                            <p>
                                <button id="option_two_title">1. I am doing a health screening with my physician.</button>
                            </p>

                            <div id="option_two_content" class="hide">
                                <ul style="list-style-type: decimal">
                                    <li>
                                        <a href="/compliance/cancerhematologycenters-2016/my-health">
                                            Complete an online Health Risk Assessment (HRA) questionnaire by <strong>June 30, 2018</strong>.
                                        </a>
                                    </li>
                                    <li>Complete a lipid panel/glucose screening with your doctor between <strong>May 1, 2017
                                            and June 30, 2018</strong>. Have your primary health care provider sign and add your results to the
                                        <a href="/resources/9954/2018 Primary Care Physician Form Option 1.pdf" target="_blank">
                                            2018 CHCWM Primary Care Physician Form – Option 1</a>.*</li>
                                    <li>Have your dentist sign the
                                        <a href="/resources/9956/2018 Dental Preventative Form.pdf" target="_blank">
                                            2018 Dental Preventative Form</a> confirming that you have had a preventative
                                        visit between 5/1/2017 and 6/30/2018.</li>
                                    <li>Complete 1 E-Learning Lesson – via the link on your report card below.</li>
                                    <li><strong>Employees only: </strong>Earn a total of 9 or more points on the report card
                                        OR earn 2 more points than the prior year OR satisfy the reasonable alternative.*</li>
                                    <li><strong>If notified by Circle Wellness,</strong> you must complete 4 coaching phone calls and/or emails by December 31, 2018.</li>
                                </ul>

                                <p>
                                    * Once your lab values are showing on your report card, a reasonable alternative will
                                    be offered if you weren't able to achieve 9 points. Complete the E-Learning lessons
                                    through the Alternative links in the biometric portion of your report card. Once you
                                    complete all the lessons for a particular biometric measure, your report card will
                                    populate with the maximum points for that measure. E-Learning lessons must be completed
                                    by July 31, 2018. You must have a total of nine points to qualify for the incentive.
                                    Please contact the Circle Wellness Hotline if you have any questions: 1-866-682-3020 ext. 204.
                                </p>
                            </div>

                            <p>
                                <button id="option_one_title">2. I am doing an onsite health screening at a CHCWM location.</button>
                            </p>

                            <div id="option_one_content" class="hide">
                                <ul style="list-style-type: decimal">
                                    <li>
                                        <a href="/compliance/cancerhematologycenters-2016/my-health">
                                            Complete an online Health Risk Assessment (HRA) questionnaire by <strong>June 30, 2018</strong>.
                                        </a>
                                    </li>
                                    <li>Complete an onsite health screening at CHCWM in April. Click <strong><a href="/compliance/cancerhematologycenters-2016/schedule">here</a></strong> to schedule an appointment.</li>
                                    <li>Have your primary health care provider sign the
                                        <a href="/resources/9955/2018 Primary Care Physician Form Option 2 - 1.pdf" target="_blank">
                                            2018 CHCWM Primary Care Physician Form – Option 2</a> confirming that you have had a preventative visit
                                        with them between 5/1/2017 and 6/30/2018.*</li>
                                    <li>Have your dentist sign the
                                        <a href="/resources/9956/2018 Dental Preventative Form.pdf" target="_blank">
                                            2018 Dental Preventative Form</a> confirming that you have had a preventative
                                        visit between 5/1/2017 and 6/30/2018.</li>
                                    <li>Complete 1 E-Learning Lesson – via the link on your report card below.</li>
                                    <li><strong>Employees only:</strong> Earn a total of 9 or more points on the report card OR earn 2 more points than the prior year OR satisfy the reasonable alternative.*</li>
                                    <li><strong>If notified by Circle Wellness,</strong> you must complete 4 coaching phone calls and/or emails by December 31, 2018.</li>
                                </ul>

                                <p>
                                    * Once your lab values are showing on your report card, a reasonable alternative will be
                                    offered if you weren't able to achieve 9 points. Complete the E-Learning lessons through
                                    the Alternative links in the biometric portion of your report card. Once you complete all
                                    the lessons for a particular biometric measure, your report card will populate with the
                                    maximum points for that measure. E-Learning lessons must be completed by July 31, 2018.
                                    You must have a total of nine points to qualify for the incentive. Please contact the
                                    Circle Wellness Hotline if you have any questions: 1-866-682-3020 ext. 204.
                                </p>
                            </div>

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
                    <?php echo $tableRow('My Requirements', $status->getComplianceViewGroupStatus('required')) ?>
                    <?php echo $tableRow('My Biometric Results Points', $status->getComplianceViewGroupStatus('Requirements')) ?>
                    </tbody>

                    <tr class="point-totals">
                        <th>2017 Point Totals</th>
                        <td></td>
                        <td><?php echo $lastYearTotalPoints ?></td>
                    </tr>
                    <tr class="point-totals">
                        <th>2018 Point Totals</th>
                        <td></td>
                        <td><?php echo $thisYearTotalPoints ?></td>
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
                $('.view-coaching_session3').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 3</span>');
                $('.view-coaching_session4').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 4</span>');

                <?php if($status->getComplianceViewStatus('spouse_participation')) : ?>
                $('.view-spouse_participation').children(':eq(0)').html('7. <?php echo $status->getComplianceViewStatus('spouse_participation')->getAttribute('spouse_text') ?> Compliance');
                <?php endif ?>

                $optionOneTitle = $('#option_one_title');
                $optionOneContent = $('#option_one_content');

                $optionTwoTitle = $('#option_two_title');
                $optionTwoContent = $('#option_two_content');

                $optionOneTitle.click(function() {
                    $optionOneContent.toggleClass('hide');
                });

                $optionTwoTitle.click(function() {
                    $optionTwoContent.toggleClass('hide');
                });
            });
        </script>
        <?php
    }
}