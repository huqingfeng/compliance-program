<?php
class SandCElectric2016CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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
                'required_fields'  => array('cholesterol')
            )
        );

        return $data;
    }
}


class SandCElectric2016ComplianceProgram extends ComplianceProgram
{
    public function getTrack(User $user)
    {
        if($this->lastTrack && $this->lastTrack['user_id'] == $user->id) {
            return $this->lastTrack['track'];
        } else {
            $track = $user->getGroupValueFromTypeName('S&C Track 2016', 'T2 - Lifestyle');

            $this->lastTrack = array('user_id' => $user->id, 'track' => $track);

            return $track;
        }
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $req = new ComplianceViewGroup('core', 'Requirements');

        $ampSignup = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $ampSignup->setName('amp_signup');
        $ampSignup->setReportName('AMP UP! Sign Up! Card');
        $ampSignup->setAttribute('deadline', '03/31/16');
        $ampSignup->setAttribute('report_name_link', '/content/1094#1asignup');
        $ampSignup->addLink(new Link('Get AMP UP! Card', '/resources/7160/SignUp-2015card_09-15_2up.pdf'));
        $ampSignup->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($ampSignup);
        $this->addComplianceViewGroup($req);

        $screening = new SandCElectric2016CompleteScreeningComplianceView($startDate, '2016-09-30');
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screening->setAttribute('deadline', '03/31/16');
        $screening->setReportName('Complete Biometric Screening');
        $screening->setAttribute('report_name_link', '/content/1094#1bscreen');
        $screening->emptyLinks();
        $screening->setName('screening');
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/OnlineRegistrationRequest/LandingPage/7'));
        $screening->addLink(new Link('Results', '/content/989'));
        $screening->addLink(new Link('Physician Form', '/resources/7163/PHYSICIAN Screening Collection Form 2016 (final).pdf'));

        $req->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, '2016-09-30');
        $hra->setReportName('Complete Health Risk Assessment');
        $hra->setAttribute('deadline', '03/31/16');
        $hra->emptyLinks();
        $hra->setName('hra');
        $hra->setAttribute('report_name_link', '/content/1094#1chra');
        $hra->addLink(new Link('Take HRA/See Results', '/content/989'));
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($hra);

        $scrView = $this->getTempView(
            'age_appropriate_screening',
            'Complete 1 Age-Appropriate Screening',
            '9/30/16',
            array(new Link('Get Form', '/resources/7166/2016 Exam Confirmation Form.pdf'))
        );
        $scrView->setAttribute('report_name_link', '/content/1094#1eagescreen');
        $req->addComplianceView($scrView);

        $physView = $this->getTempView(
            'physical',
            'Complete 1 Annual Physical',
            '9/30/16',
            array(new Link('Get Form', '/resources/7166/2016 Exam Confirmation Form.pdf'))
        );
        $physView->setAttribute('report_name_link', '/content/1094#1fannphys');
        $req->addComplianceView($physView);

        $smartActivities = $this->getTempView(
            'smart_activities',
            'Health Action Plan: Complete 3 Smart Activities',
            '9/30/16',
            array(new Link('See Options 2A-G below', '/content/1094#1gsmartalt'))
        );
        $smartActivities->setAttribute('report_name_link', '/content/1094#1gsmartalt');
        $req->addComplianceView($smartActivities);

        $smartMoveActivities = $this->getTempView(
            'smart_move_activities',
            '1 MUST be a Smart Move Activity',
            '9/30/16',
            array(new Link('Call to Schedule', '/content/1094#1hhealthplan'))
        );
        $smartMoveActivities->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($smartMoveActivities);

        $callsAndSmartMoveView = $this->getTempView(
            'hap_lifestyle_calls_and_smart_move_activities',
            'Health Action Plan: Complete 4 Lifestyle Health Calls and 1 Smart Move Activity (see below); OR',
            '9/30/16',
            array(new Link('Call to Schedule', '/content/1094#1hhealthplan'))
        );
        $callsAndSmartMoveView->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($callsAndSmartMoveView);

        $monthEngagementAndSmartActivitiesView = $this->getTempView(
            'hap_month_engagement_and_smart_activities',
            'Health Action Plan: 3-Month Engagement Required AND Complete 2 Smart Activities (see below); OR',
            '9/30/16',
            array(new Link('Call to Schedule', '/content/1094#1hhealthplan'))
        );
        $monthEngagementAndSmartActivitiesView->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($monthEngagementAndSmartActivitiesView);

        $hpaCustomizedTrackView = $this->getTempView(
            'hap_customized_track',
            'Health Action Plan: Customized Track for Individual; OR',
            '9/30/16',
            array(new Link('Call to Schedule', '/content/1094#1hhealthplan'))
        );
        $hpaCustomizedTrackView->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($hpaCustomizedTrackView);

        $callsAndSmartActivitiesView = $this->getTempView(
            'hap_lifestyle_calls_and_smart_activities',
            'Health Action Plan Alternative: Complete 6 Lifestyle Health Calls AND 4 Smart Activities (see below).',
            '9/30/16',
            array(new Link('See Options 2A-G below', '/content/1094#1gsmartalt'))
        );
        $callsAndSmartActivitiesView->setAttribute('report_name_link', '/content/1094#1gsmartalt');
        $req->addComplianceView($callsAndSmartActivitiesView);



        $altGroup = new ComplianceViewGroup('smart', 'Smart Activities');

        $smartMoveFitnessView = $this->getTempView(
            'smart_move_fitness',
            'Smart Move - Complete a Fitness Assessment',
            '9/30/16',
            array(new Link('Call to Schedule', '/content/1094#2gfitness'))
        );
        $smartMoveFitnessView->setAttribute('report_name_link', '/content/1094#2gfitness');
        $altGroup->addComplianceView($smartMoveFitnessView);

        $smartMoveChallengeView = $this->getTempView(
            'smart_move_challenge',
            'Smart Move - Participate in 1 Smart Challenge',
            '9/30/16',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $smartMoveChallengeView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($smartMoveChallengeView);

        $smartMoveClassView = $this->getTempView(
            'smart_move_class',
            'Smart Move - Attend Any 6 S&C Wellness Center Classes in a 6-Week Class Period',
            '9/30/16',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $smartMoveClassView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($smartMoveClassView);

        $smartMovePersonalTrainingView = $this->getTempView(
            'smart_move_personal_training',
            'Smart Move - Personal Training at the S&C Wellness Center (6 sessions)',
            '9/30/16',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $smartMovePersonalTrainingView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($smartMovePersonalTrainingView);

        $smartMoveLearnView = $this->getTempView(
            'smart_move_learn',
            'Smart Move - Attend Move \'n\' Learn',
            '9/30/16',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $smartMoveLearnView->setAttribute('report_name_link', '/content/1094#2elnl');
        $altGroup->addComplianceView($smartMoveLearnView);

        $smartLessonView = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, 1);
        $smartLessonView->setName('smart_lesson');
        $smartLessonView->setReportName('Smart U - Complete at least 1 eLearning lesson');
        $smartLessonView->setAttribute('deadline', '9/30/16');
        $smartLessonView->setAttribute('report_name_link', '/content/1094#2aelearn');
        $smartLessonView->setPointsPerLesson(1);
        $altGroup->addComplianceView($smartLessonView);

        $smartLunchView = $this->getTempView(
            'smart_lunch',
            'Smart U - Attend Lunch \'n\' Learn',
            '9/30/16',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $smartLunchView->setAttribute('report_name_link', '/content/1094#2elnl');
        $altGroup->addComplianceView($smartLunchView);

        $smartWeightView = $this->getTempView(
            'smart_weight',
            'Smart Health - Complete a Weight Watchers series',
            '9/30/16',
            array(new Link('Get Info or Sign Up', '/resources/4233/S&C_AHC_Weight_Watchers_at_Work-2013.pdf'))
        );
        $smartWeightView->setAttribute('report_name_link', '/content/1094#2cww');
        $altGroup->addComplianceView($smartWeightView);

        $smartDentalView = $this->getTempView(
            'smart_dental',
            'Smart Health - Complete a Dental Oral Exam or Dental Cleaning',
            '9/30/16',
            array(new Link('Get Form', ' /resources/7166/2016 Exam Confirmation Form.pdf'))
        );
        $smartDentalView->setAttribute('report_name_link', '/content/1094#2fdental');
        $altGroup->addComplianceView($smartDentalView);

        $smartHugView = $this->getTempView(
            'smart_hug',
            'Smart Health - Earn Credit for "Hug Your Heart" ',
            '9/30/16'
        );
        $smartHugView->setAttribute('report_name_link', '/content/1094#2fdental');
        $altGroup->addComplianceView($smartHugView);

        $smartStressView = $this->getTempView(
            'smart_stress',
            'Smart Health - Complete a Stress Test',
            '9/30/16'
        );
        $smartStressView->setAttribute('report_name_link', '/content/1094#2fdental');
        $altGroup->addComplianceView($smartStressView);


        $selfSmartMoveView = $this->getTempView(
            'self_smart_move',
            'Self Smart Move - Smart Move Activity Completed Outside of S&C Electric Company',
            '9/30/16',
            array(new Link('Get Form', '/resources/7169/2016 Self Smart Activity Form.pdf'))
        );
        $selfSmartMoveView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($selfSmartMoveView);

        $selfSmartUView = $this->getTempView(
            'self_smart_u',
            'Self Smart U - Smart U Activity Completed Outside of S&C Electric Company',
            '9/30/16',
            array(new Link('Get Form', '/resources/7169/2016 Self Smart Activity Form.pdf'))
        );
        $selfSmartUView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($selfSmartUView);

        $selfSmartHealthView = $this->getTempView(
            'self_smart_health',
            'Self Smart Health - Smart Health Activity Completed Outside of S&C Electric Company',
            '9/30/16',
            array(new Link('Get Form', '/resources/7169/2016 Self Smart Activity Form.pdf'))
        );
        $selfSmartHealthView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($selfSmartHealthView);

        $callsView = $this->getTempView(
            'calls',
            'Smart U - Participate in Lifestyle Health Calls',
            '9/30/16',
            array(new Link('Call to Schedule', '/content/1094#2bhealthcall'))
        );
        $callsView->setAttribute('report_name_link', '/content/1094#2bhealthcall');
        $altGroup->addComplianceView($callsView);


        $this->addComplianceViewGroup($altGroup);

        $settingsGroup = new ComplianceViewGroup('settings', 'Settings');

        $asOfDate = new PlaceHolderComplianceView();
        $asOfDate->setName('as_of_date');
        $asOfDate->setReportName('As Of Date');
        $settingsGroup->addComplianceView($asOfDate);

        $this->addComplianceViewGroup($settingsGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SandCElectric2016ComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter() {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $program = $this;

        $printer->setShowUserFields(null, null, true, false, true, null, null, null, true);
        $printer->setShowUserContactFields(true, null, true);

        $printer->addCallbackField('employee_id', function (User $user) {
            return $user->employeeid;
        });

        $printer->addCallbackField('member_id', function (User $user) {
            return $user->member_id;
        });

        $printer->addCallbackField('track', function (User $user) {
            $track = $user->getGroupValueFromTypeName('S&C Track 2016', 'T2 - Lifestyle');

            return $track;
        });

        $printer->addCallbackField('Health Action Plan Completion', function (User $user) use($program) {
            $program->setActiveUser($user);
            $GHIndicator = $program->getGHIndicator();

            return $GHIndicator;
        });

        $printer->addCallbackField('Weigh In Screening Date', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2016-08-31');

            return isset($data['date']) ? $data['date'] : null;
        });

        $printer->addCallbackField('Weigh In - Height', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2016-08-31');

            return isset($data['height']) ? $data['height'] : null;
        });

        $printer->addCallbackField('Weigh In - Weight', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2016-08-31');

            return isset($data['weight']) ? $data['weight'] : null;
        });

        $printer->addCallbackField('Weigh In - BMI', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2016-08-31');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            return isset($bmi) ? $bmi : null;
        });

        $printer->addCallbackField('Weigh In - Body Fat ', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2016-08-31');

            return isset($data['bodyfat']) ? $data['bodyfat'] : null;
        });

        $printer->addCallbackField('Weigh In - Program Goal', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2016-03-31');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            $programGoal = null;
            if(isset($bmi) && $bmi >= 18.5 && $bmi <= 24.9) {
                $programGoal = 'MAINTAIN';
            } elseif (isset($bmi) && $bmi > 24.9) {
                $idealBMI = 24.9;

                $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
                $idealDecreasedWeight = $data['weight'] - ($data['weight'] * 0.04);

                $programGoal = $idealBMIWeight >= $idealDecreasedWeight ? 'Reach Health BMI' : 'Change Initial Body Weight by 4%';
            } elseif (isset($bmi) && $bmi < 18.5) {
                $idealBMI = 18.5;

                $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
                $idealIncreasedWeight = $data['weight'] + ($data['weight'] * 0.04);

                $programGoal = $idealBMIWeight <= $idealIncreasedWeight ? 'Reach Health BMI' : 'Change Initial Body Weight by 4%';
            }

            return $programGoal;
        });

        $printer->addCallbackField('Weigh In - Goal Weight', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2016-03-31');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            $goalWeight = null;
            if(isset($bmi) && $bmi >= 18.5 && $bmi <= 24.9) {
                $goalWeight = $data['weight'];
            } elseif (isset($bmi) && $bmi > 24.9) {
                $idealBMI = 24.9;

                $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
                $idealDecreasedWeight = $data['weight'] - ($data['weight'] * 0.04);

                $goalWeight = round($idealBMIWeight >= $idealDecreasedWeight ? $idealBMIWeight : $idealDecreasedWeight, 2);
            } elseif (isset($bmi) && $bmi < 18.5) {
                $idealBMI = 18.5;

                $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
                $idealIncreasedWeight = $data['weight'] + ($data['weight'] * 0.04);

                $goalWeight = round($idealBMIWeight <= $idealIncreasedWeight ? $idealBMIWeight : $idealIncreasedWeight, 2);
            }

            return $goalWeight;
        });

        $printer->addCallbackField('Weight Out Screening Date', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            return isset($data['date']) ? $data['date'] : null;
        });

        $printer->addCallbackField('Weight Out - Height', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            return isset($data['height']) ? $data['height'] : null;
        });

        $printer->addCallbackField('Weight Out - Weight', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            return isset($data['weight']) ? $data['weight'] : null;
        });

        $printer->addCallbackField('Weight Out - BMI', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            return isset($bmi) ? $bmi : null;
        });

        $printer->addCallbackField('Weight Out - Body Fat ', function (User $user) {
            $data = SandCElectric2016ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            return isset($data['bodyfat']) ? $data['bodyfat'] : null;
        });

        return $printer;
    }

    public static function getScreeningData(User $user, $startDate = '2015-10-01', $endDate = '2016-08-31')
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime($startDate),
            new DateTime($endDate),
            array(
                'require_online'   => false,
                'merge'            => false,
                'order'             => true,
                'require_complete' => false,
                'required_fields'  => array('weight', 'height')
            )
        );

        return isset($data[0]) ? $data[0] : null;
    }

    public function getGHIndicator()
    {
        return $this->GHIndicator;
    }

    public function getAllRequiredCoreViews()
    {
        $allRequiredViews = array(
            'amp_signup',
            'screening',
            'hra',
            'age_appropriate_screening',
            'physical'
        );

        return $allRequiredViews;
    }

    public function getTrackRequiredCoreViews(User $user)
    {
        $requiredCoreViews = array(
            'T1 - Maintenance'  => array(
                'smart_activities',
                'smart_move_activities'
            ),
            'T2 - Lifestyle'  => array(
                'hap_lifestyle_calls_and_smart_move_activities',
                'hap_lifestyle_calls_and_smart_activities'
            ),
            'T3 - Care Management'  => array(
                'hap_month_engagement_and_smart_activities',
                'hap_lifestyle_calls_and_smart_activities'
            ),
            'T4 - Integrated Care Management'  => array(
                'hap_customized_track',
                'hap_lifestyle_calls_and_smart_activities'
            ),
        );

        $track = trim($this->getTrack($user));

        return $requiredCoreViews[$track];
    }

    public function getNumOfTrackRequiredCoreViews(User $user)
    {
        $numOfRequiredCoreViews = array(
            'T1 - Maintenance'  => 2,
            'T2 - Lifestyle'  => 1,
            'T3 - Care Management'  => 1,
            'T4 - Integrated Care Management'  => 1,
        );

        $track = trim($this->getTrack($user));

        return $numOfRequiredCoreViews[$track];
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();
        $track = trim($this->getTrack($user));

        $smartGroupStatus = $status->getComplianceViewGroupStatus('smart');
        $totalSmartDone = 0;
        $smartMoveDone = 0;
        $callsDone = 0;

        foreach($smartGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $viewName = $view->getName();
            $points = $viewStatus->getPoints();

            if($points > 0) {
                if (strpos($viewName, 'move') !== false) {
                    $smartMoveDone += $points;
                    $totalSmartDone += $points;
                } elseif ($viewName == 'calls') {
                    $callsDone += $points;
                } else {
                    $totalSmartDone += $points;
                }

                $viewStatus->setStatus(ComplianceViewStatus::COMPLIANT);
            }
        }

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $allRequiredCoreViews = $this->getAllRequiredCoreViews();
        $trackRequiredCoreViews = $this->getTrackRequiredCoreViews($user);
        $numberOfAllRequiredCoreViews = count($allRequiredCoreViews);
        $numberOfTrackRequiredCoreViews = $this->getNumOfTrackRequiredCoreViews($user);

        $allRequiredCoreNumberDone = 0;
        $trackRequiredCoreNumberDone = 0;

        $eitherOneCompliant = false;

        foreach($coreGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $viewName = $view->getName();
            $points = $viewStatus->getPoints();

            if(in_array($viewName, $allRequiredCoreViews)) {
                if($points > 0) {
                    $allRequiredCoreNumberDone++;
                    $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                }
            } elseif(in_array($viewName, $trackRequiredCoreViews)) {
                if($track == 'T1 - Maintenance') {
                    if($viewName == 'smart_activities') {
                        if($totalSmartDone >= 3) {
                            $trackRequiredCoreNumberDone++;
                            $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                        } elseif ($totalSmartDone > 0) {
                            $viewStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                        }

                        $viewStatus->setPoints($totalSmartDone);
                    } elseif ($viewName == 'smart_move_activities') {
                        if($smartMoveDone >= 1) {
                            $trackRequiredCoreNumberDone++;
                            $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                        }

                        $viewStatus->setPoints($smartMoveDone);
                    }

                } elseif ($track == 'T2 - Lifestyle') {
                    if($viewName == 'hap_lifestyle_calls_and_smart_move_activities') {
                        if($callsDone >= 4 && $smartMoveDone >= 1) {
                            $trackRequiredCoreNumberDone++;
                            $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                            $eitherOneCompliant = true;
                        } elseif ($callsDone >= 1 || $smartMoveDone >= 1) {
                            $viewStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                        }

                        $viewStatus->setPoints($callsDone);
                    } elseif ($viewName == 'hap_lifestyle_calls_and_smart_activities') {
                        if($callsDone >= 1 && $totalSmartDone >= 4 && $smartMoveDone >= 2) {
                            $trackRequiredCoreNumberDone++;
                            $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                            $eitherOneCompliant = true;
                        } elseif ($callsDone >= 1 || $totalSmartDone >= 1) {
                            $viewStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                        }

                        $viewStatus->setPoints($callsDone);
                    }
                } elseif ($track == 'T3 - Care Management') {
                    if($viewName == 'hap_month_engagement_and_smart_activities') {
                        if($points >= 1 && $totalSmartDone >= 2) {
                            $trackRequiredCoreNumberDone++;
                            $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                            $eitherOneCompliant = true;
                        } elseif ($points >= 1 || $totalSmartDone >= 1) {
                            $viewStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                        }

                    } elseif ($viewName == 'hap_lifestyle_calls_and_smart_activities') {
                        if($callsDone >= 6 && $totalSmartDone >= 4) {
                            $trackRequiredCoreNumberDone++;
                            $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                            $eitherOneCompliant = true;
                        } elseif ($callsDone >= 1 || $totalSmartDone >= 1) {
                            $viewStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                        }

                        $viewStatus->setPoints($callsDone);
                    }
                } elseif ($track == 'T4 - Integrated Care Management') {
                    if($viewName == 'hap_customized_track') {
                        if($points >= 1) {
                            $trackRequiredCoreNumberDone++;
                            $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                            $eitherOneCompliant = true;
                        }

                    } elseif ($viewName == 'hap_lifestyle_calls_and_smart_activities') {
                        if($callsDone >= 12 && $totalSmartDone >= 3) {
                            $trackRequiredCoreNumberDone++;
                            $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                            $eitherOneCompliant = true;
                        } elseif ($callsDone >= 1 || $totalSmartDone >= 1) {
                            $viewStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                        }

                        $viewStatus->setPoints($callsDone);
                    }
                }
            }
        }

        if($eitherOneCompliant) {
            foreach($trackRequiredCoreViews as $trackRequiredCoreView) {
                $viewStatus = $coreGroupStatus->getComplianceViewStatus($trackRequiredCoreView);
                $viewStatus->setStatus(ComplianceViewStatus::COMPLIANT);
            }
        }

        $smartCompliant = false;
        if($track == 'T1 - Maintenance') {
            if($totalSmartDone >= 3 && $smartMoveDone >= 1) {
                $smartCompliant = true;
            }
        } else {
            if($eitherOneCompliant) {
                $smartCompliant = true;
            }
        }

        if($smartCompliant) {
            $smartGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        } elseif ($totalSmartDone > 0) {
            $smartGroupStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $smartGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        $smartGroupStatus->setAttribute('number_completed_count', $totalSmartDone);

        if($allRequiredCoreNumberDone >= $numberOfAllRequiredCoreViews
            && $trackRequiredCoreNumberDone >= $numberOfTrackRequiredCoreViews) {
            $coreGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        } elseif(($allRequiredCoreNumberDone + $trackRequiredCoreNumberDone)  > 0) {
            $coreGroupStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $coreGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

    }

    private function getTempView($name, $reportName, $deadline, array $links = array())
    {
        $ageAppropriate = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $ageAppropriate->setName($name);
        $ageAppropriate->setReportName($reportName);
        $ageAppropriate->setAttribute('deadline', $deadline);
        $ageAppropriate->setAllowPointsOverride(true);

        foreach($links as $link) {
            $ageAppropriate->addLink($link);
        }

        return $ageAppropriate;
    }

    private $hideMarker = '<span class="hide-view">hide</span>';
    private $lastTrack = null;
    private $GHIndicator = 'No';
}

class SandCElectric2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    protected function showGroup($group)
    {
        $groupName = $group->getName();

        if($groupName == 'smart') {
            $this->tableHeaders['completed'] = 'Count Completed';
        } else {
            $this->tableHeaders['completed'] = 'Count Completed';
        }

        return $groupName != 'settings';
    }

    protected function getCompleted(ComplianceViewGroup $group,
                                    ComplianceViewStatus $viewStatus)
    {
        if($group->getName() == 'smart') {
            return $viewStatus->getPoints() != '' ? $viewStatus->getPoints() : 0;
        } else {
            return $viewStatus->getPoints() != '' ? $viewStatus->getPoints() : 0;
        }
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $program = $status->getComplianceProgram();

        $user = $status->getUser();

        $this->addStatusCallbackColumn('Deadline', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            $default = $view instanceof DateBasedComplianceView ?
                $view->getEndDate('m/d/Y') : '';

            return $view->getAttribute('deadline', $default);
        });

//        $this->addStatusCallbackColumn('Minimum Required', function (ComplianceViewStatus $status) use($program, $user) {
//            $view = $status->getComplianceView();
//
//            return $program->getMinimum($user, $view->getName());
//        });

        $this->addStatusCallbackColumn('Date Satisfied', function (ComplianceViewStatus $status) use($program, $user) {
            return $status->getComment();
        });

        $startDate = $status->getComplianceProgram()->getEndDate('F d, Y');

        $this->setShowLegend(true);
        $this->setShowTotal(false);
        $this->pageHeading = '2016 Wellness Initiative Program';

        $this->tableHeaders['links'] = 'Action Links';


        parent::printReport($status);
        ?>
        <br/>

        <p></p>
        <?php
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $program = $status->getComplianceProgram();
        $user = $status->getUser();
        $asOfDate = $status->getComplianceViewStatus('as_of_date')->getComment();

        $track = $status->getComplianceProgram()->getTrack($user);

        $allRequiredCoreViews = $status->getComplianceProgram()->getAllRequiredCoreViews();
        $trackRequiredCoreViews = $status->getComplianceProgram()->getTrackRequiredCoreViews($user);

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

//        $hapLifestyleViewStatus = $program->getComplianceView('hap_lifestyle_calls')->getStatus($user)->getStatus();
//        $hapMonthEngagementViewStatus = $program->getComplianceView('hap_month_engagement')->getStatus($user)->getStatus();
//        $hapCustomizedTrackViewStatus = $program->getComplianceView('hap_customized_track')->getStatus($user)->getStatus();
//        $smartAlternativeViewStatus = $program->getComplianceView('smart_activities')->getStatus($user)->getStatus();
//        $smartAlternativeAndHapLifestyleViewStatus = $program->getComplianceView('smart_activities_and_hap_lifestyle')->getStatus($user)->getStatus();

        if(!$asOfDate) {
            $asOfDate = date('m/d/Y', strtotime(sfConfig::get('app_compliance_programs_sandc_as_of_date', date('Y-m-d'))));
        }

        ?>
        <style type="text/css">
            #overviewCriteria {
                width:100%;
                border-collapse:collapse;
            }

            #legendText {
                background-color:#90C4DE;
            }

            #overviewCriteria th {
                background-color:#42669A;
                color:#FFFFFF;
                font-weight:normal;
                font-size:11pt;
                padding:5px;
            }

            #overviewCriteria td {
                width:33.3%;
                vertical-align:top;
            }

            .phipTable .headerRow {
                background-color:#90C4DE;
                font-size:16px;
            }

            .phipTable {
                font-size:12px;
                margin-bottom:10px;
            }

            .phipTable th, .phipTable td {
                padding:1px 2px;
                border:1px solid #a0a0a0;
            }

            .phipTable .points {
                width:80px;
            }

            .phipTable .gray_out {
                background-color:#D3D3D3;
            }

            .phipTable .links {
                width:190px;
            }

            .phipTable .headerRow-core {
                width: 60px;
            }

            .phipTable .bolder_border {
                border-left: 3px solid gray;
                border-right: 3px solid gray;
            }

            .phipTable .headerRow-core {
                border-top: 3px solid gray;
            }

            .phipTable #core_status {
                border-bottom: 3px solid gray;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                <?php foreach($coreGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $viewName = $viewStatus->getComplianceView()->getName() ?>
                    <?php if(!in_array($viewName, array_merge($allRequiredCoreViews, $trackRequiredCoreViews))) : ?>
                        $('.view-<?php echo $viewName ?>').hide();
                    <?php endif ?>
                <?php endforeach ?>


                <?php  if($track == 'T1 - Maintenance') : ?>
                    $('.view-smart_move_activities').children(':eq(0)').html('<span style="margin-left:90px;"><a href="/content/1094#1gsmartalt">1 MUST be a Smart Move Activity</a></span>');
                    $('.view-smart_move_activities').children('.links').html('<a target="_self" href="/content/1094#1gsmartalt">See Smart Activities Below</a>');
                    $('.view-smart_activities').children('.links').html('<a target="_self" href="/content/1094#1gsmartalt">See Smart Activities Below</a>');
                <?php elseif($track == 'T2 - Lifestyle') : ?>
                    $('.view-hap_lifestyle_calls_and_smart_activities').children(':eq(0)').remove();
                    $('.view-hap_lifestyle_calls_and_smart_move_activities').children(':eq(0)').attr('rowspan', 2);
                    $('.view-hap_lifestyle_calls_and_smart_move_activities').children(':eq(0)').html('<strong>F</strong>. <a href="/content/1094#1hhealthplan">Health Action Plan: Complete 4 Lifestyle Health Calls and 1 Smart Move Activity (see below)</a>' +
                    '<br /><span style="padding-left: 120px;">OR</span><br />'+
                    '<strong>G</strong>. <a href="/content/1094#1gsmartalt">Health Action Plan Alternative: Complete 1 Lifestyle Health Call AND 4 Smart Activities (2 MUST be Smart Moves - see below)</a>');
                    $('.view-hap_lifestyle_calls_and_smart_activities').children('.links').html('<a target="_self" href="/content/1094#1gsmartalt">See Smart Activities Below</a>');

                <?php elseif($track == 'T3 - Care Management') : ?>
                    $('.view-hap_lifestyle_calls_and_smart_activities').children(':eq(0)').remove();
                    $('.view-hap_month_engagement_and_smart_activities').children(':eq(0)').attr('rowspan', 2);
                    $('.view-hap_month_engagement_and_smart_activities').children(':eq(0)').html('<strong>F</strong>. <a href="/content/1094#1hhealthplan">Health Action Plan: 3-Month Engagement Required AND Complete 2 Smart Activities (see below)</a>' +
                    '<br /><span style="padding-left: 120px;">OR</span><br />'+
                    '<strong>G</strong>. <a href="/content/1094#1gsmartalt">Health Action Plan Alternative: Complete 6 Lifestyle Health Calls AND 4 Smart Activities (see below)</a>');
                <?php elseif($track == 'T4 - Integrated Care Management') : ?>
                    $('.view-hap_lifestyle_calls_and_smart_activities').children(':eq(0)').remove();
                    $('.view-hap_customized_track').children(':eq(0)').attr('rowspan', 2);
                    $('.view-hap_customized_track').children(':eq(0)').html('<strong>F</strong>. <a href="/content/1094#1hhealthplan">Health Action Plan: Customized Track for Each Individual</a>' +
                    '<br /><span style="padding-left: 120px;">OR</span><br />'+
                    '<strong>G</strong>. <a href="/content/1094#1gsmartalt">Health Action Plan Alternative: Complete 12 Lifestyle Health Calls PLUS 3 Smart Activities (see below)</a>');
                <?php endif ?>


                $('.headerRow-smart').before(
                    '<tr id="core_status">' +
                    '<td colspan="4" style="text-align:right"><strong>Your incentive requirements status as of <?php echo $asOfDate ?></strong>*</td>' +
                    '<td style="text-align:center"><img src="<?php echo $status->getComplianceViewGroupStatus('core')->getLight() ?>" class="light" /></td>' +
                    '<td id="section_complete_message"></td>' +
                    '</tr><tr style="height:50px;" class="blank_row"><td colspan="7"></td></tr>'
                );

                $('.view-self_smart_health').after(
                    '<tr id="num_of_green">' +
                    '<td colspan="2" style="text-align:right"><strong>Number of greens earned as of <?php echo $asOfDate ?></strong>*</td>' +
                    '<td colspan="2" style="text-align:center"><?php echo $status->getComplianceViewGroupStatus('smart')->getAttribute('number_completed_count') ?></td>' +
                    '<td style="text-align:center"><img src="<?php echo $status->getComplianceViewGroupStatus('smart')->getLight() ?>" class="light" />' +
                    '<td></td>' +
                    '</tr>'
                );


                $('.headerRow-smart').children(':eq(2)').remove();
                $('.view-smart_move_fitness').children(':eq(2)').remove();
                $('.view-smart_move_challenge').children(':eq(2)').remove();
                $('.view-smart_move_class').children(':eq(2)').remove();
                $('.view-smart_move_personal_training').children(':eq(2)').remove();
                $('.view-smart_move_learn').children(':eq(2)').remove();
                $('.view-smart_lesson').children(':eq(2)').remove();
                $('.view-smart_lunch').children(':eq(2)').remove();
                $('.view-smart_weight').children(':eq(2)').remove();
                $('.view-smart_dental').children(':eq(2)').remove();
                $('.view-smart_hug').children(':eq(2)').remove();
                $('.view-smart_stress').children(':eq(2)').remove();
                $('.view-self_smart_move').children(':eq(2)').remove();
                $('.view-self_smart_u').children(':eq(2)').remove();
                $('.view-self_smart_health').children(':eq(2)').remove();
                $('.view-calls').children(':eq(2)').remove();


                $('.headerRow-smart').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_move_fitness').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_move_challenge').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_move_class').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_move_personal_training').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_move_learn').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_lesson').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_lunch').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_weight').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_dental').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_hug').children(':eq(2)').attr('colspan', 2);
                $('.view-smart_stress').children(':eq(2)').attr('colspan', 2);
                $('.view-self_smart_move').children(':eq(2)').attr('colspan', 2);
                $('.view-self_smart_u').children(':eq(2)').attr('colspan', 2);
                $('.view-self_smart_health').children(':eq(2)').attr('colspan', 2);
                $('.view-calls').children(':eq(2)').attr('colspan', 2);

                $('#legend tr td').attr('colspan', 7);
                $('.view-calls').hide();
            });
        </script>

        <div class="row">
            <div class="span4">
                <?php echo $status->getUser()->getFullName() ?>
            </div>
        </div>
        <p></p>
        <p style="color:red;margin-left:24px;">Note: Some actions you took within the past 30-60 days may not show
            until next month. Please allow 30-60 days for updates relying on
            claims (1E and 1F) and/or any required forms you have submitted.</p>
        <p>If you have any questions/concerns about your report card please contact the AMP UP! Help Desk 800-761-5856  (M-F from 8am-8pm CST)</p>

        <div class="row" style="text-align: center; font-weight: bold;">
            Track = <?php echo $status->getComplianceProgram()->getTrack($status->getUser()) ?>
        </div><br/>
        <?php
    }
}
