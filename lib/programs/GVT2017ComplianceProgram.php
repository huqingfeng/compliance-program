<?php

use hpn\steel\query\SelectQuery;

class GVT2017ComplianceProgram extends ComplianceProgram
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
        $printer->setShowUserContactFields(null, null, true);

        $printer->addCallbackField('expiration_date', function (User $user) {
            return $user->expires;
        });

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
            $data['Total Points 2016'] = $status->getAttribute('prior_status')->getPoints();
            $data['Point Difference'] = $data['Total Points'] - $data['Total Points 2016'];
            $data['Monthly Surcharge'] = $status->getComment();
            $data['Level']  = $status->getAttribute('level');
            $data['Department']  = $status->getUser()->getDepartment();
            $data['Shift']  = $status->getUser()->getShift();
            $data['Section ']  = $status->getUser()->getSection();
            $data['Spouse Compliant'] = $status->getAttribute('spouse_status');
//            $data['Has Biometric Override'] = $status->getAttribute('has_biometric_override') ? 'Yes' : 'No';
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new GVT2017WMS2Printer();
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

        $hraView = new CompleteHRAComplianceView($programStart, '2017-08-15');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->setAttribute('deadline', '08/15/2017');
        $hraView->addLink(new Link('Take HRA', sfConfig::get('app_wms2') ? '/compliance/gti-2016/my-health' : '/content/989'));
        $hraView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $status->setAttribute('discount_required', true);
            if($status->isCompliant()) {
                $status->setAttribute('discount_compliant', true);
            } else {
                $status->setAttribute('discount_compliant', false);
            }
        });
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, '2017-08-15');
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $screeningView->setReportName('Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', sfConfig::get('app_wms2') ? '/compliance/gti-2016/schedule/wms1/content/1114' : '/content/1114'));
        $screeningView->setAttribute('deadline', '08/15/2017');
        $screeningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $status->setAttribute('discount_required', true);
            if($status->isCompliant()) {
                $status->setAttribute('discount_compliant', true);
            } else {
                $status->setAttribute('discount_compliant', false);
            }
        });
        $preventionEventGroup->addComplianceView($screeningView);

        $privateConsultationView = new CompletePrivateConsultationComplianceView($programStart, '2017-09-15');
        $privateConsultationView->setName('consultation');
        $privateConsultationView->setReportName('Consultation');
        $privateConsultationView->addLink(new Link('Sign-Up', sfConfig::get('app_wms2') ? '/compliance/gti-2016/schedule/wms1/content/1114' : '/content/1114'));
        $privateConsultationView->setAttribute('deadline', '09/15/2017');
        $privateConsultationView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $status->setAttribute('discount_required', true);
            if($status->isCompliant()) {
                $status->setAttribute('discount_compliant', true);
            } else {
                $status->setAttribute('discount_compliant', false);
            }
        });
        $preventionEventGroup->addComplianceView($privateConsultationView);

        $coachingRequiredUsers = GVT2017ComplianceProgram::getCoachingRequiredUsers();

        $coachingOverall = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingOverall->setName('coaching_overall');
        $coachingOverall->setReportName('Complete 4 Coaching Sessions (if applicable)');
        $coachingOverall->setAttribute('deadline', '12/31/2017');
        $coachingOverall->setPreMapCallback(function(ComplianceViewStatus $status, User $user) use ($coachingRequiredUsers) {
            if(in_array($user->id, $coachingRequiredUsers)) {
                $status->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
            }
        });
        $preventionEventGroup->addComplianceView($coachingOverall);

        $coachingSession1 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession1->setName('coaching_session1');
        $coachingSession1->setReportName('Session 1');
        $coachingSession1->setAttribute('deadline', '12/31/2017');
        $coachingSession1->setPreMapCallback(function(ComplianceViewStatus $status, User $user) use ($coachingRequiredUsers) {
            if(in_array($user->id, $coachingRequiredUsers)) {
                $status->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession1);

        $coachingSession2 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession2->setName('coaching_session2');
        $coachingSession2->setReportName('Session 2');
        $coachingSession2->setAttribute('deadline', '12/31/2017');
        $coachingSession2->setPreMapCallback(function(ComplianceViewStatus $status, User $user) use ($coachingRequiredUsers) {
            if(in_array($user->id, $coachingRequiredUsers)) {
                $status->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession2);

        $coachingSession3 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession3->setName('coaching_session3');
        $coachingSession3->setReportName('Session 3');
        $coachingSession3->setAttribute('deadline', '12/31/2017');
        $coachingSession3->setPreMapCallback(function(ComplianceViewStatus $status, User $user) use ($coachingRequiredUsers) {
            if(in_array($user->id, $coachingRequiredUsers)) {
                $status->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession3);

        $coachingSession4 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession4->setName('coaching_session4');
        $coachingSession4->setReportName('Session 4');
        $coachingSession4->setAttribute('deadline', '12/31/2017');
        $coachingSession4->setPreMapCallback(function(ComplianceViewStatus $status, User $user) use ($coachingRequiredUsers) {
            if(in_array($user->id, $coachingRequiredUsers)) {
                $status->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
            }
        });
        $preventionEventGroup->addComplianceView($coachingSession4);

//        COMMENTED OUT FOR FUTURE USE
//        $spouseParticipation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
//        $spouseParticipation->setName('spouse_participation');
//        $spouseParticipation->setReportName('Spouse Compliance');
//        $spouseParticipation->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
//            $status->setAttribute('discount_required', true);
//
//            $spouseUser = $user->getSpouseUser();
//
//            if($user->getRelationshipType() != Relationship::EMPLOYEE || !$spouseUser)  {
//                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
//                $status->setAttribute('discount_compliant', true);
//            } else {
//                $program = ComplianceProgramRecordTable::getInstance()->find(GVT2017ComplianceProgram::GVT_EMPLOYEE_2017_RECORD_ID)->getComplianceProgram();
//                $program->setActiveUser($spouseUser);
//                $overallStatus = $program->getStatus();
//
//                $requiredGroupStatus = $overallStatus->getComplianceViewGroupStatus('required');
//                $hraStatus = $requiredGroupStatus->getComplianceViewStatus('hra');
//                $screeningStatus = $requiredGroupStatus->getComplianceViewStatus('screening');
//                $consultationStatus = $requiredGroupStatus->getComplianceViewStatus('consultation');
//                $coachingStatus = $requiredGroupStatus->getComplianceViewStatus('coaching_overall');
//
//                $numCompliant = 0;
//                $discountCompliant = true;
//
//                $status->setAttribute('has_spouse', true);
//
//                $status->setAttribute('spouse_hra_status', $hraStatus->getStatus());
//                if($hraStatus->isCompliant()) {
//                    $numCompliant++;
//                } else {
//                    $discountCompliant = false;
//                }
//
//                $status->setAttribute('spouse_screening_status', $screeningStatus->getStatus());
//                if($screeningStatus->isCompliant()) {
//                    $numCompliant++;
//                } else {
//                    $discountCompliant = false;
//                }
//
//                $status->setAttribute('spouse_consultation_status', $consultationStatus->getStatus());
//                if($consultationStatus->isCompliant()) {
//                    $numCompliant++;
//                } else {
//                    $discountCompliant = false;
//                }
//
//                $status->setAttribute('spouse_coaching_status', $coachingStatus->getStatus());
//                if($coachingStatus->isCompliant()) {
//                    $numCompliant++;
//                }
//
//                if($numCompliant == 4) {
//                    $status->setStatus(ComplianceStatus::COMPLIANT);
//                } elseif( $numCompliant > 0) {
//                    $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
//                }
//
//                $status->setAttribute('discount_compliant', $discountCompliant);
//            }
//
//        });
//        $preventionEventGroup->addComplianceView($spouseParticipation, true);
//
        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements', 'Biometric Measures');
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
        $biometricsGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->overrideTestRowData(null, null, 149, 199.99);
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥200');
        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setName('glucose');
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
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);
        $biometricsGroup->addComplianceView($bodyFatBMIView);


        $this->addComplianceViewGroup($biometricsGroup);

    }

    public static function getCoachingRequiredUsers()
    {
        return array(
            '2534371',
            '2534401',
            '2996736',
            '2747387',
            '2534781',
            '2992853',
            '2872984',
            '2996750',
            '2748729',
            '2996756',
            '2993069',
            '2872960',
            '2993072',
            '2992958',
            '2747340',
            '2747461',
            '2996776',
            '2536101',
            '2872851',
            '2644158',
            '2536531',
            '2865553',
            '2536751',
            '2996786',
            '2536781',
            '2710677',
            '2710678',
            '2636971',
            '2537141',
            '2637102',
            '2537261',
            '2537431',
            '3003636',
            '2537611',
            '2872862',
            '2997615',
            '2873329',
            '2637115',
            '2997126',
            '2992868',
            '2997129',
            '2872903',
            '2872933',
            '2872877',
            '2872986',
            '2992898',
            '2993090',
            '2872917',
            '2872959',
            '2992919',
            '2747312',
            '2872859',
            '2872857',
            '2992916',
            '2747400',
            '2992880',
            '2872884',
            '2534421',
            '2872905',
            '2535171',
            '2865567',
            '2993057',
            '2872842',
            '2992991',
            '2997123',
            '3006594',
            '2996800',
            '2996808',
            '2538401',
            '2538581',
            '2872816',
            '2993063',
            '2539071',
            '2539081',
            '2539181',
            '2539381',
            '2747370',
            '2747201',
            '2872803',
            '2997609',
            '2992946',
            '2992955',
            '2872890',
            '2992901',
            '2865601',
            '2747384',
            '2538461',
            '2537221',
            '2537391',
            '2787512',
            '2872981',
            '2539811',
            '2747429',
            '2865520',
            '2872951',
            '2989640'
        );
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
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

        $coachingStartDate = '2017-08-01';
        $coachingEndDate = '2017-12-31';

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
                        $isContact = false;

                        if($reportEdit !== false) {
                            if($reportEdit->getFieldValue('communication_type') != '' && $reportEdit->getFieldValue('communication_type') == 'contact') {
                                $isContact = true;
                            } elseif($reportEdit->getFieldValue('communication_type') == '' && max(0, $reportEdit->getFieldValue('total_minutes')) >= 5) {
                                $isContact = true;
                            }
                        }

                        foreach($recordedFields as $recordedField) {
                            $name = $recordedField->getFieldName();
                            $value = $recordedField->getFieldValue();
                            if(empty($value) || !empty($defaults[$name])) continue;

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

        $requiredGroupCompliant = true;
        foreach($requiredGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if(!$viewStatus->isCompliant()) $requiredGroupCompliant = false;
        }

        if($requiredGroupCompliant) {
            $requiredGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

        $extraPoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                    $extraPoints += $viewExtraPoints;
                }
            }
        }

        $status->setAttribute('total_points_ignoring_alternatives', $status->getPoints() - $extraPoints);

        $thisRequirements = $status->getComplianceViewGroupStatus('Requirements');
        $thisPrevention = $status->getComplianceViewGroupStatus('required');

        $priorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($status->getUser(), '2016-01-01', '2016-12-31');
        $priorRequirements = $priorStatus->getComplianceViewGroupStatus('Requirements');

        $status->setAttribute('prior_status', $priorStatus);

        $coachingViews = array(
            'coaching_overall',
            'coaching_session1',
            'coaching_session2',
            'coaching_session3',
            'coaching_session4'
        );
        $preventionCompliant = true;
        foreach($thisPrevention->getComplianceViewStatuses() as $viewStatus) {
            if(in_array($viewStatus->getComplianceView()->getName(), $coachingViews)) continue;
            if(!$viewStatus->isCompliant()) $preventionCompliant = false;
        }

        if($preventionCompliant) {
            $thisPrevention->setStatus(ComplianceStatus::COMPLIANT);
        } elseif($thisPrevention->isCompliant()) {
            $thisPrevention->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        }

        if($thisRequirements->getPoints() >= 10) {
            $thisRequirements->setStatus(ComplianceViewGroupStatus::COMPLIANT);
        } elseif ($thisRequirements->getPoints() >= 6) {
            $thisRequirements->setStatus(ComplianceViewGroupStatus::PARTIALLY_COMPLIANT);
        } else {
            $thisRequirements->setStatus(ComplianceViewGroupStatus::NOT_COMPLIANT);
        }

        $improvementPoints = $thisRequirements->getPoints() - $priorRequirements->getPoints();
        $status->setAttribute('improvement_points', $improvementPoints);

        if($thisPrevention->getStatus() == ComplianceViewGroupStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }


        if($thisPrevention->getStatus() == ComplianceViewGroupStatus::COMPLIANT && $status->getUser()->getRelationshipType() == Relationship::EMPLOYEE) {
            if($thisRequirements->getPoints() >= 10 || $thisRequirements->getPoints() - 2 >= $priorRequirements->getPoints()) {
                $status->setAttribute('level', 'one');
            } elseif($thisRequirements->getPoints() >= 6) {
                $status->setAttribute('level', 'two');
            } elseif($thisRequirements->getPoints() >= 1) {
                $status->setAttribute('level', 'three');
            }
        }

        $status->setAttribute('spouse_status', $this->getSpouseStatus($status->getUser()));
        $status->setPoints($thisRequirements->getPoints());
    }

    private function getSpouseStatus($user)
    {
        $spouseUser = $user->getSpouseUser();

        if($user->getRelationshipType() != Relationship::EMPLOYEE || !$spouseUser)  {
            return 'No Spouse';
        } else {
            $program = ComplianceProgramRecordTable::getInstance()->find(GVT2017ComplianceProgram::GVT_EMPLOYEE_2017_RECORD_ID)->getComplianceProgram();
            $program->setActiveUser($spouseUser);
            $program->evaluateOverall = false;
            $overallStatus = $program->getStatus();

            $requiredGroupStatus = $overallStatus->getComplianceViewGroupStatus('required');
            $hraStatus = $requiredGroupStatus->getComplianceViewStatus('hra');
            $screeningStatus = $requiredGroupStatus->getComplianceViewStatus('screening');
            $consultationStatus = $requiredGroupStatus->getComplianceViewStatus('consultation');

            $numCompliant = 0;
            if($hraStatus->isCompliant()) {
                $numCompliant++;
            }

            if($screeningStatus->isCompliant()) {
                $numCompliant++;
            }

            if($consultationStatus->isCompliant()) {
                $numCompliant++;
            }

            if($numCompliant == 3) {
                return "Yes";
            } else {
                return "No";
            }
        }
    }

    public function getPriorYearBiometricsComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        $program = new GVT2015ComplianceProgram($startDate, $endDate);

        // Change name of the cloned views so that new overrides don't apply to
        // last year's results.

        foreach($program->getComplianceViews() as $cV) {
            $cV->setUseOverrides(false);
        }

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

    const GVT_EMPLOYEE_2017_RECORD_ID = 1129;
    protected $evaluateOverall = true;
}

class GVT2017WMS2Printer implements ComplianceProgramReportPrinter
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
        $maxPriorPoints = $escaper->escapeJs($lastYearRequirementsGroup->getComplianceViewGroup()->getMaximumNumberOfPoints());

        $requiredStatus = $status->getComplianceViewGroupStatus('required');
        $requirementsStatus = $status->getComplianceViewGroupStatus('Requirements');


        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $classForRequirements = function($rawPct) {
            if($rawPct >= 10) {
                return 'success';
            } elseif($rawPct >= 6) {
                return 'warning';
            } else {
                return 'danger';
            }
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

                if(in_array($status->getComplianceView()->getName(), $coachingViews)) {
                    return 'N/A';
                } else {
                    return 'Not Required';
                }

            } else if ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
                return 'Not Done';
            }
        };

        $textForGroupStatus = function($status) {
            if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
                return 'Done';
            }  else {
                return 'Not Done';
            }
        };

        $textForSpouseStatus = function($status, $isCoaching = false) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'Done';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                if($isCoaching) {
                    return 'N/A';
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
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>">
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
                                    <td>07/31/2016</td>
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
                                    <td>07/31/2016</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>

                                <tr class="view-spouse_participation_consultation">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• Spouse Consultation</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_consultation_status')) ?>">
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_consultation_status')) ?>
                                    </td>
                                    <td>09/16/2016</td>
                                    <td class="links text-center">
                                    </td>
                                </tr>

                                <tr class="view-spouse_participation_coaching">
                                    <td class="name">
                                        <span style="padding-left: 20px;">• Spouse Coaching</span>
                                    </td>
                                    <td class="points <?php echo $classForStatus($viewStatus->getAttribute('spouse_coaching_status')) ?>"
                                        <?php echo $classForStatus($viewStatus->getAttribute('spouse_coaching_status')) == 'info' ? 'style="background-color: #CCC;"' : ''?>>
                                        <?php echo $textForSpouseStatus($viewStatus->getAttribute('spouse_coaching_status'), true) ?>
                                    </td>
                                    <td>12/31/2016</td>
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

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $classForStatus, $classForRequirements, $textForGroupStatus, $groupTable) {
            ob_start();

            $numOfViews = 0;
            $numOfCompliant = 0;
            foreach($group->getComplianceViewStatuses() as $viewStatus) {
                $numOfViews++;
                if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numOfCompliant++;
            }

            if ($group->getComplianceViewGroup()->getName() == 'Requirements') : ?>

                <?php $points = $group->getPoints(); ?>
                <?php $target = $group->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?>
                <?php $pct = $points / $target; ?>
                <?php $class = $classForRequirements($points); ?>
                <tr class="picker closed">
                    <td class="name">
                        <?php echo $name ?>
                        <div class="triangle"></div>
                    </td>
                    <td class="points success">
                        <strong><?php echo $target ?></strong><br/>
                        points
                    </td>
                    <td class="points <?php echo $class ?>">
                        <strong><?php echo $points ?></strong><br/>
                        points
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
            <?php else : ?>
                <?php $pct = $group->getStatus() == ComplianceStatus::COMPLIANT ? 1 : $numOfCompliant / $numOfViews; ?>
                <?php $class = $classForStatus($group->getStatus()); ?>
                <?php $text = $textForGroupStatus($group); ?>
                <tr class="picker closed">
                    <td class="name">
                        <?php echo $name ?>
                        <div class="triangle"></div>
                    </td>
                    <td class="points success">Done
                    </td>
                    <td class="points <?php echo $class ?>"><?php echo $text ?>
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

        $requiredStatusForDiscount = true;
        foreach($requiredStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getAttribute('discount_required') && !$viewStatus->getAttribute('discount_compliant')) {
                $requiredStatusForDiscount = false;
            }
        }

        $totalStatus = ComplianceStatus::NOT_COMPLIANT;
        if ($requiredStatusForDiscount && ($requirementsStatus->getPoints() >= 10 || $status->getAttribute('improvement_points') >= 2)) {
            $totalStatus = ComplianceStatus::COMPLIANT;
        } else if ($requiredStatusForDiscount && $requirementsStatus->getPoints() >= 6) {
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
                                            <?php echo $circle(
                                                $requirementsStatus->getStatus(),
                                                '<br/>Biometric<br/>Points<br/><br/><span class="circle-points">'.$requirementsStatus->getPoints(). '</span>',
                                                'Requirements'
                                            ) ?>
                                            <br/>
                                            <strong><?php echo $requirementsStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></strong> points possible
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <?php echo $circle(
                                                $totalStatus,
                                                '<br/>Point<br/>Discount',
                                                'total_status'
                                            ) ?>
                                            <br/>
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
                        <div class="col-md-6">
                            <p>
                                <strong>
                                    By completing the following steps in 2017, associates will be eligible for a premium
                                    reduction in their health plan effective 01/01/2018.
                                </strong>
                            </p>
                            <p><strong>Step 1</strong>- GTI-insured associates and their insured spouse must complete an HRA, on-site
                                health screening, private one-on-one consultation and 4 follow-up coaching sessions (if they qualify and
                                are notified by Circle Wellness). Screenings will be scheduled beginning in June at all three locations.
                                Onsite consultations will follow.
                            </p>
                            <p><strong>Step 2</strong>- The number of points the associate earns on their 2017 report card
                                will determine the premium reduction for 2018.* Spouses only need to complete the requirements in step 1.</p>

                            <p>*If you have a medical reason for not being able to reach one or more of the
                                requirements, you can contact Christina Myers in Human Resources to discuss
                                a reasonable alternative.
                            </p>
                        </div>

                        <div class="col-md-6">
                            <p>Point Discounts:</p>

                            <table id="point-discounts">
                                <tbody>
                                <tr>
                                    <td><?php echo $circle2('#74C36E') ?></td>
                                    <td>
                                        Level 1: Score 10-15 points or have a two year improvement over the previous year’s results*
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php echo $circle2('#FDB83B') ?></td>
                                    <td>
                                        Level 2: Score 6-9 points
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php echo $circle2('#F15752') ?></td>
                                    <td>
                                        Level 3: Score 1-5 points
                                    </td>
                                </tr>
                                </tbody>
                            </table>
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
                    </tbody>

                    <tr class="point-totals">
                        <th>2017 Point Totals</th>
                        <td></td>
                        <td><?php echo $requirementsStatus->getPoints() ?></td>
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
                $('.view-coaching_session3').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 3</span>');
                $('.view-coaching_session4').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 4</span>');

                $('.view-spouse_participation').children(':eq(0)').html('5. Spouse Compliance');
            });
        </script>
        <?php
    }
}

