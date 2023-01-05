<?php
class SandCElectric2015CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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


class SandCElectric2015ComplianceProgram extends ComplianceProgram
{
    public function getTrack(User $user)
    {
        if($this->lastTrack && $this->lastTrack['user_id'] == $user->id) {
            return $this->lastTrack['track'];
        } else {
            $track = $user->getGroupValueFromTypeName('S&C Track 2015', 'T2 - Lifestyle');

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
        $ampSignup->setAttribute('deadline', '03/31/15');
        $ampSignup->setAttribute('report_name_link', '/content/1094#1asignup');
        $ampSignup->addLink(new Link('Get AMP UP! Card', '/resources/4919/05-14-SignUp-2014card-FILLABLE.060914.PDF'));
        $ampSignup->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($ampSignup);
        $this->addComplianceViewGroup($req);

        $screening = new SandCElectric2015CompleteScreeningComplianceView($startDate, '2015-06-30');
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screening->setAttribute('deadline', '03/31/15');
        $screening->setReportName('Complete Biometric Screening');
        $screening->setAttribute('report_name_link', '/content/1094#1bscreen');
        $screening->emptyLinks();
        $screening->setName('screening');
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/OnlineRegistrationRequest/LandingPage/7'));
        $screening->addLink(new Link('Results', '/content/989'));

        $req->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, '2015-09-30');
        $hra->setReportName('Complete Health Risk Assessment');
        $hra->setAttribute('deadline', '06/30/15');
        $hra->emptyLinks();
        $hra->setName('hra');
        $hra->setAttribute('report_name_link', '/content/1094#1chra');
        $hra->addLink(new Link('Take HRA/See Results', '/content/989'));
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($hra);

        $coachView = $this->getTempView(
            'coach',
            'Complete 1 Health Action Call',
            '06/30/15',
            array(new Link('Call to Schedule', '/content/1094#1dhacall'))
        );
        $coachView->setAttribute('report_name_link', '/content/1094#1dhacall');
        $req->addComplianceView($coachView);

        $scrView = $this->getTempView(
            'age_appropriate_screening',
            'Complete 1 Age-Appropriate Screening',
            '9/30/15',
            array(new Link('Get Form', '/resources/5122/2015 Exam Confirmation Form(final).pdf'))
        );
        $scrView->setAttribute('report_name_link', '/content/1094#1eagescreen');
        $req->addComplianceView($scrView);

        $physView = $this->getTempView(
            'physical',
            'Complete 1 Annual Physical',
            '9/30/15',
            array(new Link('Get Form', '/resources/5122/2015 Exam Confirmation Form(final).pdf'))
        );
        $physView->setAttribute('report_name_link', '/content/1094#1fannphys');
        $req->addComplianceView($physView);

        $lifestyleView = $this->getTempView(
            'hap_lifestyle',
            'Health Action Plan: Complete Lifestyle Health Calls (from 2B)',
            '9/30/15',
            array(new Link('Call to Schedule', '/content/1094#1hhealthplan'))
        );
        $lifestyleView->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($lifestyleView);     
        
        $hpaMonthEngagementView = $this->getTempView(
            'hap_month_engagement',
            'Health Action Plan: 3-Month Engagement; OR',
            '9/30/15',
            array(new Link('Call to Schedule', '/content/1094#1hhealthplan'))
        );
        $hpaMonthEngagementView->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($hpaMonthEngagementView);     
        
        $hpaCustomizedTrackView = $this->getTempView(
            'hap_customized_track',
            'Health Action Plan: Customized Track for Individual; OR',
            '9/30/15',
            array(new Link('Call to Schedule', '/content/1094#1hhealthplan'))
        );
        $hpaCustomizedTrackView->setAttribute('report_name_link', '/content/1094#1hhealthplan');
        $req->addComplianceView($hpaCustomizedTrackView);         

        $altView = $this->getTempView(
            'smart_alternative',
            'Complete Smart Alternative Activities (from 2)',
            '9/30/15',
            array(new Link('See Options 2A-G below', '/content/1094#1gsmartalt'))
        );
        $altView->setAttribute('report_name_link', '/content/1094#1gsmartalt');
        $req->addComplianceView($altView);     
        
        $altAndHapLifestyleView = $this->getTempView(
            'smart_alternative_and_hap_lifestyle',
            'Health Action Plan Alternative: Complete 6 Lifestyle Health Calls PLUS 3 Smart Alternative Activities (from Chart 2 below)',
            '9/30/15',
            array(new Link('See Options 2A-G below', '/content/1094#1gsmartalt'))
        );
        $altAndHapLifestyleView->setAttribute('report_name_link', '/content/1094#1gsmartalt');
        $req->addComplianceView($altAndHapLifestyleView);                

        $altGroup = new ComplianceViewGroup('smart', 'Smart Alternative Activities');

        $smartU = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, 1);
        $smartU->setName('lesson');
        $smartU->setReportName('Smart U - Complete at least 1 eLearning lesson');
        $smartU->setAttribute('deadline', '9/30/15');
        $smartU->setAttribute('report_name_link', '/content/1094#2aelearn');
        $smartU->setPointsPerLesson(1);
        $altGroup->addComplianceView($smartU);

        $callsView = $this->getTempView(
            'calls',
            'Smart U - Participate in 6 Lifestyle Health Calls (this does NOT include your Health Action Call)',
            '9/30/15',
            array(new Link('Call to Schedule', '/content/1094#2bhealthcall'))
        );
        $callsView->setAttribute('report_name_link', '/content/1094#2bhealthcall');
        $altGroup->addComplianceView($callsView);

        $lunchView = $this->getTempView(
            'lunch',
            'Smart U - Attend Lunch \'n\' Learns',
            '9/30/15',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $lunchView->setAttribute('report_name_link', '/content/1094#2elnl');
        $altGroup->addComplianceView($lunchView);

        $weightView = $this->getTempView(
            'weight',
            'Smart Health - Complete a Weight Watchers series',
            '9/30/15',
            array(new Link('Get Info or Sign Up', '/resources/4233/S&C_AHC_Weight_Watchers_at_Work-2013.pdf'))
        );
        $weightView->setAttribute('report_name_link', '/content/1094#2cww');
        $altGroup->addComplianceView($weightView);

        $dentalView = $this->getTempView(
            'dental',
            'Smart Health - Complete a Dental Oral Exam or Dental Cleaning',
            '9/30/15',
            array(new Link('Get Form', '/resources/5122/2015 Exam Confirmation Form(final).pdf'))
        );
        $dentalView->setAttribute('report_name_link', '/content/1094#2fdental');
        $altGroup->addComplianceView($dentalView);

        $fitnessView = $this->getTempView(
            'fitness',
            'Smart Move - Complete a Fitness Assessment',
            '9/30/15',
            array(new Link('Call to Schedule', '/content/1094#2gfitness'))
        );
        $fitnessView->setAttribute('report_name_link', '/content/1094#2gfitness');
        $altGroup->addComplianceView($fitnessView);

        $moveSmartChallengeView = $this->getTempView(
            'move_smart_challenge',
            'Smart Move - Participate in 1 Smart Challenge',
            '9/30/15',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $moveSmartChallengeView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($moveSmartChallengeView);

        $moveSmartClassView = $this->getTempView(
            'move_smart_class',
            'Smart Move - Attend any 6 S&C Wellness Center classes in a 6-week period',
            '9/30/15',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $moveSmartClassView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($moveSmartClassView);
        
        $moveSmartPersonalTrainingView = $this->getTempView(
            'move_smart_personal_training',
            'Smart Move - Personal Training at S&C Wellness Center',
            '9/30/15',
            array(new Link('See Topics &amp; Calendar', '/content/events'))
        );
        $moveSmartPersonalTrainingView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($moveSmartPersonalTrainingView);

        $selfSmartView = $this->getTempView(
            'self_smart',
            'Self Smart - Health Activity completed outside of S&C Electric Company',
            '9/30/15',
            array(new Link('Get Form', '/resources/5121/2015 Self Smart Activity Form (final).pdf'))
        );
        $selfSmartView->setAttribute('report_name_link', '/content/1094#2dchallenge');
        $altGroup->addComplianceView($selfSmartView);
        
        
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
        return new SandCElectric2015ComplianceProgramReportPrinter();
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
            $track = $user->getGroupValueFromTypeName('S&C Track 2015', 'T2 - Lifestyle');

            return $track;
        });

        $printer->addCallbackField('Health Action Plan Completion', function (User $user) use($program) {
            $program->setActiveUser($user);
            $GHIndicator = $program->getGHIndicator();

            return $GHIndicator;
        });

        $printer->addCallbackField('Weigh In Screening Date', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2014-10-01', '2015-08-31');

            return isset($data['date']) ? $data['date'] : null;
        });

        $printer->addCallbackField('Weigh In - Height', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2014-10-01', '2015-08-31');

            return isset($data['height']) ? $data['height'] : null;
        });

        $printer->addCallbackField('Weigh In - Weight', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2014-10-01', '2015-08-31');

            return isset($data['weight']) ? $data['weight'] : null;
        });

        $printer->addCallbackField('Weigh In - BMI', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2014-10-01', '2015-08-31');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            return isset($bmi) ? $bmi : null;
        });

        $printer->addCallbackField('Weigh In - Body Fat ', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2014-10-01', '2015-08-31');

            return isset($data['bodyfat']) ? $data['bodyfat'] : null;
        });

        $printer->addCallbackField('Weigh In - Program Goal', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2014-10-01', '2015-08-31');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            $programGoal = null;
            if(isset($bmi) && $bmi >= 18.5 && $bmi <= 24.9) {
                $programGoal = 'MAINTAIN';
            } elseif (isset($bmi) && $bmi > 24.9) {
                $idealBMI = 24.9;

                $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
                $idealDecreasedWeight = $data['weight'] - ($data['weight'] * 0.03);

                $programGoal = $idealBMIWeight >= $idealDecreasedWeight ? 'Reach Health BMI' : 'Change Initial Body Weight by 3%';
            } elseif (isset($bmi) && $bmi < 18.5) {
                $idealBMI = 18.5;

                $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
                $idealIncreasedWeight = $data['weight'] + ($data['weight'] * 0.03);

                $programGoal = $idealBMIWeight <= $idealIncreasedWeight ? 'Reach Health BMI' : 'Change Initial Body Weight by 3%';
            }

            return $programGoal;
        });

        $printer->addCallbackField('Weigh In - Goal Weight', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2014-10-01', '2015-08-31');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            $goalWeight = null;
            if(isset($bmi) && $bmi >= 18.5 && $bmi <= 24.9) {
                $goalWeight = $data['weight'];
            } elseif (isset($bmi) && $bmi > 24.9) {
                $idealBMI = 24.9;

                $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
                $idealDecreasedWeight = $data['weight'] - ($data['weight'] * 0.03);

                $goalWeight = round($idealBMIWeight >= $idealDecreasedWeight ? $idealBMIWeight : $idealDecreasedWeight, 2);
            } elseif (isset($bmi) && $bmi < 18.5) {
                $idealBMI = 18.5;

                $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
                $idealIncreasedWeight = $data['weight'] + ($data['weight'] * 0.03);

                $goalWeight = round($idealBMIWeight <= $idealIncreasedWeight ? $idealBMIWeight : $idealIncreasedWeight, 2);
            }

            return $goalWeight;
        });

        $printer->addCallbackField('Weight Out Screening Date', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            return isset($data['date']) ? $data['date'] : null;
        });

        $printer->addCallbackField('Weight Out - Height', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            return isset($data['height']) ? $data['height'] : null;
        });

        $printer->addCallbackField('Weight Out - Weight', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            return isset($data['weight']) ? $data['weight'] : null;
        });

        $printer->addCallbackField('Weight Out - BMI', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            return isset($bmi) ? $bmi : null;
        });

        $printer->addCallbackField('Weight Out - Body Fat ', function (User $user) {
            $data = SandCElectric2015ComplianceProgram::getScreeningData($user, '2015-10-01', '2015-11-06');

            return isset($data['bodyfat']) ? $data['bodyfat'] : null;
        });

        return $printer;
    }

    public static function getScreeningData(User $user, $startDate = '2014-10-01', $endDate = '2015-08-31')
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

    public function setActiveUser(User $user = null)
    {
        parent::setActiveUser($user);

        $smartGroupName = 'smart';

        if($smartGroup = $this->getComplianceViewGroup($smartGroupName)) {
            if($user === null) {
                $smartGroup->setNumberOfViewsRequired(null);
            } else {
                $smartGroup->setNumberOfViewsRequired(
                    $this->getMinimumRequired($user, $smartGroupName)
                );
            }
        }

        return $this;
    }

    public function getMinimumRequired(User $user, $groupName)
    {
        return $this->getMinimum($user, "{$groupName}_required");
    }

    public function getMinimum(User $user, $viewName)
    {
        $minimums = array(
            'amp_signup' => 1,
            'screening' => 1,
            'hra' => 1,
            'coach' => 1,
            'age_appropriate_screening' => 1,
            'physical' => 1,
            'smart_alternative' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 2,
                'T3 - Care Management' => 3,
                'T4 - Integrated Care Management' => 4
            ),
            'hap_lifestyle' => array(
                'T1 - Maintenance' => $this->hideMarker,
                'T2 - Lifestyle' => 3,
                'T3 - Care Management' => 6,
                'T4 - Integrated Care Management' => 12
            ),
            'smart_alternative_and_hap_lifestyle' => array(
                'T1 - Maintenance' => $this->hideMarker,
                'T2 - Lifestyle' => $this->hideMarker,
                'T3 - Care Management' => 6,
                'T4 - Integrated Care Management' => 12
            ),
            'hap_month_engagement' => array(
                'T1 - Maintenance' => $this->hideMarker,
                'T2 - Lifestyle' => $this->hideMarker,
                'T3 - Care Management' => 1,
                'T4 - Integrated Care Management' => $this->hideMarker
            ),
            'hap_customized_track' => array(
                'T1 - Maintenance' => $this->hideMarker,
                'T2 - Lifestyle' => $this->hideMarker,
                'T3 - Care Management' => $this->hideMarker,
                'T4 - Integrated Care Management' => 1
            ),
            'lesson' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 1,
                'T3 - Care Management' => 1,
                'T4 - Integrated Care Management' => 1,
            ),
            'calls' => array(
                'T1 - Maintenance' => 6,
                'T2 - Lifestyle' => 3,
                'T3 - Care Management' => 6,
                'T4 - Integrated Care Management' => 12,
            ),
            'weight' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 0,
                'T3 - Care Management' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'move_smart_challenge' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 0,
                'T3 - Care Management' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'move_smart_class' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 0,
                'T3 - Care Management' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'move_smart_personal_training' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 0,
                'T3 - Care Management' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'self_smart' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 0,
                'T3 - Care Management' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            
            'lunch' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 0,
                'T3 - Care Management' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'dental' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 0,
                'T3 - Care Management' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'fitness' => array(
                'T1 - Maintenance' => 1,
                'T2 - Lifestyle' => 0,
                'T3 - Care Management' => 0,
                'T4 - Integrated Care Management' => 0,
            ),
            'core_required' => array(
                'T1 - Maintenance' => '7',
                'T2 - Lifestyle' => '8',
                'T3 - Care Management' => '10',
                'T4 - Integrated Care Management' => '10',
            )
        );

        $minimums['smart_required'] = $minimums['smart_alternative'];

        $track = trim($this->getTrack($user));

        if(isset($minimums[$viewName])) {
            if(is_array($minimums[$viewName])) {
                if(isset($minimums[$viewName][$track])) {
                    return $minimums[$viewName][$track];
                } else {
                    return '';
                }
            } else {
                return $minimums[$viewName];
            }
        } else {
            return '';
        }
    }
    
    protected function getExcludedViews($track)
    {
        $excludedView = array();
        if ($track == 'T2 - Lifestyle') {
            $excludedView[] = 'smart_alternative';
            $excludedView[] = 'hap_lifestyle';
        } elseif ($track == 'T3 - Care Management') {
            $excludedView[] = 'smart_alternative';
            $excludedView[] = 'hap_lifestyle';
            $excludedView[] = 'hap_month_engagement';
            $excludedView[] = 'smart_alternative_and_hap_lifestyle';
        } elseif ($track == 'T4 - Integrated Care Management') {
            $excludedView[] = 'smart_alternative';
            $excludedView[] = 'hap_lifestyle';
            $excludedView[] = 'hap_customized_track';
            $excludedView[] = 'smart_alternative_and_hap_lifestyle';
        }
        
        return $excludedView;
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $firstPass = true;

        $user = $status->getUser();
        $track = trim($this->getTrack($user));
        
        $groupsCompliant = 0;
        
        $smartGroupStatus = $status->getComplianceViewGroupStatus('smart');
        $smartNumberDone = 0;
        $smartCompletedCount = 0;
        $smartCompliant = true;
        foreach($smartGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $minimumRequired = $this->getMinimum($user, $view->getName());
            
            if((string)$minimumRequired != $this->hideMarker) {
                if($minimumRequired > 0) {
                    $light =  $viewStatus->getPoints() >= $minimumRequired?
                        ComplianceStatus::COMPLIANT :
                        ($viewStatus->getPoints() > 0 ? ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT);

                    if($light == ComplianceStatus::COMPLIANT) {
                        $smartNumberDone++;
                    }
                    
                    if($track != 'T1 - Maintenance' &&  $viewStatus->getPoints() == 0) {
                        if($view->getName() == 'lesson' || $view->getName() == 'calls') {
                            $light = ComplianceStatus::NA_COMPLIANT;
                        }
                    }
                    
                    $viewStatus->setStatus($light);
                } else {
                    $viewStatus->getPoints() > 0 ? $viewStatus->setStatus(ComplianceStatus::COMPLIANT) : $viewStatus->setStatus(ComplianceStatus::NA_COMPLIANT);
                }
                
                if($track != 'T2 - Lifestyle' && $view->getName() == 'calls') {
                    if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) $smartCompletedCount++; 
                } elseif($track != 'T2 - Lifestyle' || $view->getName() != 'calls') {
                    $smartCompletedCount += $viewStatus->getPoints(); 
                }
            }
            
            if(!$viewStatus->isCompliant()) {
                 $smartCompliant = false;
            }
        }
        
        $smartGroupStatus->setAttribute('number_done', $smartNumberDone);
        $smartGroupStatus->setAttribute('number_completed_count', $smartCompletedCount);

        $groupMinimumRequired = $this->getMinimumRequired(
            $user, $smartGroupStatus->getComplianceViewGroup()->getName()
        );

        if($groupMinimumRequired == 'all') {
            if($smartCompliant) {
                $smartGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
            } elseif($smartCompletedCount > 0) {
                $smartGroupStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            } else {
                $smartGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        } elseif($smartCompletedCount >= $groupMinimumRequired) {
            $light = $groupMinimumRequired > 0 || $smartCompletedCount > 0 ?
                ComplianceStatus::COMPLIANT :
                ComplianceStatus::NA_COMPLIANT;

            $smartGroupStatus->setStatus($light);

            $groupsCompliant++;
        } elseif($smartCompletedCount > 0) {
            $smartGroupStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $smartGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
        
        
        
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $coreNumberDone = 0;
        $allCompliant = true;
        $smartAlternativeStatus = false;
        $oneStatus = false;
        $twoStatus = false;
        $oneStatusViewName = false;
        $twoStatusViewName = false;
        $this->GHIndicator = 'No';

        foreach($coreGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            
            $minimumRequired = $this->getMinimum($user, $view->getName());

            if((string)$minimumRequired != $this->hideMarker) {
                if($view->getName() == 'hap_lifestyle') {
                    $coreLight = $smartGroupStatus->getComplianceViewStatus('calls')->getStatus() == ComplianceStatus::COMPLIANT ?  
                        ComplianceStatus::COMPLIANT :
                        ($smartGroupStatus->getComplianceViewStatus('calls')->getPoints() > 0 ? ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
                    $viewStatus->setStatus($coreLight);
                    $viewStatus->setPoints($smartGroupStatus->getComplianceViewStatus('calls')->getPoints());
                } elseif ($view->getName() == 'smart_alternative') {
                    $coreLight = $smartCompletedCount >= $minimumRequired ? 
                        ComplianceStatus::COMPLIANT :
                        ($smartCompletedCount > 0 ? ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
                    $smartAlternativeStatus = $coreLight;
                    $viewStatus->setStatus($coreLight);
                    $viewStatus->setPoints($smartCompletedCount);
                } elseif ($view->getName() == 'smart_alternative_and_hap_lifestyle') {
                    $coreLight = $smartGroupStatus->getComplianceViewStatus('calls')->getStatus() == ComplianceStatus::COMPLIANT 
                            && $smartCompletedCount >= $this->getMinimum($user, 'smart_alternative')  ? 
                        ComplianceStatus::COMPLIANT :
                        ($smartGroupStatus->getComplianceViewStatus('calls')->getPoints() > 0 || $smartCompletedCount > 0 ? 
                                ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
                    $viewStatus->setStatus($coreLight);
                    $viewStatus->setPoints($smartGroupStatus->getComplianceViewStatus('calls')->getPoints());
                } elseif($viewStatus->getPoints() >= $minimumRequired) {
                    $coreLight = $minimumRequired > 0 || $viewStatus->getPoints() > 0 ?
                        ComplianceStatus::COMPLIANT :
                        ComplianceStatus::NA_COMPLIANT;
                    $viewStatus->setStatus($coreLight);
                } elseif($viewStatus->getPoints() > 0) {
                        $viewStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                        $coreLight = ComplianceStatus::PARTIALLY_COMPLIANT;
                } else {
                    $viewStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
                    $coreLight = ComplianceStatus::NOT_COMPLIANT;
                }

                if($track == 'T2 - Lifestyle') {
                    if($view->getName() == 'hap_lifestyle') {
                        $oneStatus = $coreLight;
                        $oneStatusViewName = 'hap_lifestyle';
                    } elseif ($view->getName() == 'smart_alternative') {
                        $twoStatus = $coreLight;
                        $twoStatusViewName = 'smart_alternative';
                    }
                } elseif ($track == 'T3 - Care Management') {
                    if($view->getName() == 'hap_month_engagement') {
                        $oneStatus = $coreLight;
                        $oneStatusViewName = 'hap_month_engagement';
                    } elseif ($view->getName() == 'smart_alternative_and_hap_lifestyle') {
                        $twoStatus = $coreLight;
                        $twoStatusViewName = 'smart_alternative_and_hap_lifestyle';
                    }
                } elseif ($track == 'T4 - Integrated Care Management') {
                    if($view->getName() == 'hap_customized_track') {
                        $oneStatus = $coreLight;
                        $oneStatusViewName = 'hap_customized_track';
                    } elseif ($view->getName() == 'smart_alternative_and_hap_lifestyle') {
                        $twoStatus = $coreLight;
                        $twoStatusViewName = 'smart_alternative_and_hap_lifestyle';
                    }
                }

                if(isset($coreLight) && $coreLight == ComplianceStatus::COMPLIANT) {
                    $coreNumberDone++;
                }
                
                if(!$viewStatus->isCompliant() && !in_array($view->getName(), $this->getExcludedViews($track))) {
                    $allCompliant = false;
                }
            }
        }

        if($track == 'T1 - Maintenance') {
            if($smartAlternativeStatus == ComplianceStatus::COMPLIANT) $this->GHIndicator = 'Yes';
        } elseif($oneStatus && $oneStatus == ComplianceStatus::COMPLIANT) {
            $this->GHIndicator = 'Yes';
            if($twoStatusViewName) $status->getComplianceViewStatus($twoStatusViewName)->setStatus(ComplianceStatus::COMPLIANT);
        } elseif ($twoStatus && $twoStatus == ComplianceStatus::COMPLIANT) {
            $this->GHIndicator = 'Yes';
            if($oneStatusViewName) $status->getComplianceViewStatus($oneStatusViewName)->setStatus(ComplianceStatus::COMPLIANT);
        }

        if($allCompliant && $oneStatus && $twoStatus) {
            if($oneStatus != ComplianceStatus::COMPLIANT
                && $twoStatus != ComplianceStatus::COMPLIANT) {
                $allCompliant = false;
            }
        }
        
        $coreGroupStatus->setAttribute('number_done', $coreNumberDone);

        $groupMinimumRequired = $this->getMinimumRequired(
            $user, $coreGroupStatus->getComplianceViewGroup()->getName()
        );

        if($allCompliant) {
            $coreGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
            $groupsCompliant++;
        } elseif($coreNumberDone > 0) {
            $coreGroupStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $coreGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        
        if($groupsCompliant >= 2) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
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

class SandCElectric2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        $this->addStatusCallbackColumn('Minimum Required', function (ComplianceViewStatus $status) use($program, $user) {
            $view = $status->getComplianceView();

            return $program->getMinimum($user, $view->getName());
        });
        
        $this->addStatusCallbackColumn('Date Satisfied', function (ComplianceViewStatus $status) use($program, $user) {
            return $status->getComment();
        });        

        $startDate = $status->getComplianceProgram()->getEndDate('F d, Y');

        $this->setShowLegend(true);
        $this->setShowTotal(false);
        $this->pageHeading = '2015 Wellness Initiative Program';

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
        
        $track = $status->getComplianceProgram()->getTrack($status->getUser());
        
        $hapLifestyleViewStatus = $program->getComplianceView('hap_lifestyle')->getStatus($user)->getStatus();
        $hapMonthEngagementViewStatus = $program->getComplianceView('hap_month_engagement')->getStatus($user)->getStatus();
        $hapCustomizedTrackViewStatus = $program->getComplianceView('hap_customized_track')->getStatus($user)->getStatus();
        $smartAlternativeViewStatus = $program->getComplianceView('smart_alternative')->getStatus($user)->getStatus();
        $smartAlternativeAndHapLifestyleViewStatus = $program->getComplianceView('smart_alternative_and_hap_lifestyle')->getStatus($user)->getStatus();

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
               <?php  if($track == 'T1 - Maintenance') : ?> 
                   $('.view-smart_alternative').children(':eq(0)').html('<strong>G</strong>. <a href="/content/1094#1gsmartalt">Health Action Plan: Complete 1 Smart Alternative Activity</a>');    
                   $('.view-smart_alternative').children(':eq(6)').html('<a target="_self" href="/content/1094#1gsmartalt">See Smart Alternative Activities Below</a>');
               <?php elseif($track == 'T2 - Lifestyle') : ?>
                   $('.view-smart_alternative').children(':eq(0)').remove();
                   $('.view-hap_lifestyle').children(':eq(0)').attr('rowspan', 2);
                   $('.view-hap_lifestyle').children(':eq(0)').html('<strong>G</strong>. <a href="/content/1094#1hhealthplan">Health Action Plan: Complete 3 Lifestyle Health Calls.</a>' +
                                                                    '<br /><span style="padding-left: 120px;">OR</span><br />'+
                                                                   '<strong>H</strong>. <a href="/content/1094#1gsmartalt">Health Action Plan Alternative: Complete 2 Smart Alternative Activities.</a>');
                                                               
                   <?php if($hapLifestyleViewStatus == ComplianceViewStatus::COMPLIANT || $smartAlternativeViewStatus == ComplianceViewStatus::COMPLIANT) : ?>
                        <?php if($hapLifestyleViewStatus != ComplianceViewStatus::COMPLIANT) : ?>
                            $('.view-hap_lifestyle').addClass('gray_out');
                        <?php elseif($smartAlternativeViewStatus != ComplianceViewStatus::COMPLIANT) : ?>
                            $('.view-smart_alternative').addClass('gray_out');
                        <?php endif ?>
                   <?php endif ?>
                       
                  $('.view-smart_alternative').children(':eq(5)').html('<a target="_self" href="/content/1094#1gsmartalt">See Smart Alternative Activities Below</a>');
                  $('.view-calls').hide();
                  $('.view-lunch').children(':eq(0)').html('<strong>B</strong>. <a href="/content/1094#2elnl">Smart U - Attend Lunch "n" Learns</a>');
                  $('.view-weight').children(':eq(0)').html('<strong>C</strong>. <a href="/content/1094#2cww">Smart Health - Complete a Weight Watchers series</a>');
                  $('.view-dental').children(':eq(0)').html('<strong>D</strong>. <a href="/content/1094#2fdental">Smart Health - Complete a Dental Oral Exam or Dental Cleaning</a>');
                  $('.view-fitness').children(':eq(0)').html('<strong>E</strong>. <a href="/content/1094#2gfitness">Smart Move - Complete a Fitness Assessment</a>');
                  $('.view-move_smart_challenge').children(':eq(0)').html('<strong>F</strong>. <a href="/content/1094#2dchallenge">Smart Move - Participate in 1 Smart Challenge</a>');
                  $('.view-move_smart_class').children(':eq(0)').html('<strong>G</strong>. <a href="/content/1094#2dchallenge">Smart Move - Attend any 6 S&amp;C Wellness Center classes in a 6-week period</a>');
                  $('.view-move_smart_personal_training').children(':eq(0)').html('<strong>H</strong>. <a href="/content/1094#2dchallenge">Smart Move - Personal Training at S&amp;C Wellness Center</a>');
                  $('.view-self_smart').children(':eq(0)').html('<strong>I</strong>. <a href="/content/1094#2dchallenge">Self Smart - Health Activity completed outside of S&amp;C Electric Company</a>');
                  
               <?php elseif($track == 'T3 - Care Management') : ?>
                   $('.view-smart_alternative_and_hap_lifestyle').children(':eq(6)').html('<a target="_self" href="/content/1094#1gsmartalt">See Smart Alternative Activities Below</a>');
                   $('.view-hap_lifestyle').hide();
                   $('.view-smart_alternative').hide();          
                   $('.view-smart_alternative_and_hap_lifestyle').children(':eq(0)').remove();
                   $('.view-hap_month_engagement').children(':eq(0)').attr('rowspan', 2);
                   $('.view-hap_month_engagement').children(':eq(0)').html('<strong>G</strong>. <a href="/content/1094#1hhealthplan">Health Action Plan: 3-Month Engagement</a>' +
                                                                          '<br /><br /><span style="padding-left: 120px;">OR</span><br /><br />'+
                                                                          '<strong>H</strong>. <a href="/content/1094#1gsmartalt">Health Action Plan Alternative: Complete 6 '+
                                                                          'Lifestyle Health Calls PLUS 3 Smart Alternative Activities (from Chart 2 below)</a>');

                   <?php if($hapMonthEngagementViewStatus == ComplianceViewStatus::COMPLIANT || $smartAlternativeAndHapLifestyleViewStatus == ComplianceViewStatus::COMPLIANT) : ?>
                        <?php if($hapMonthEngagementViewStatus != ComplianceViewStatus::COMPLIANT) : ?>
                            $('.view-hap_month_engagement').addClass('gray_out');
                        <?php elseif($smartAlternativeAndHapLifestyleViewStatus != ComplianceViewStatus::COMPLIANT) : ?>
                            $('.view-smart_alternative_and_hap_lifestyle').addClass('gray_out');
                        <?php endif ?>
                   <?php endif ?>
                   
               <?php elseif($track == 'T4 - Integrated Care Management') : ?>
                   $('.view-smart_alternative_and_hap_lifestyle').children(':eq(6)').html('<a target="_self" href="/content/1094#1gsmartalt">See Smart Alternative Activities Below</a>');
                   $('.view-hap_lifestyle').hide();
                   $('.view-smart_alternative').hide();
                   $('.view-smart_alternative_and_hap_lifestyle').children(':eq(0)').remove();
                   $('.view-hap_customized_track').children(':eq(0)').attr('rowspan', 2);
                   $('.view-hap_customized_track').children(':eq(0)').html('<strong>G</strong>. <a href="/content/1094#1hhealthplan">Health Action Plan: Customized Track for Individual;</a>' +
                                                                            '<br /><span style="padding-left: 120px;">OR</span><br /><br />'+
                                                                           '<strong>H</strong>. <a href="/content/1094#1gsmartalt">' + 
                                                                        'Health Action Plan Alternative: * * Complete 12 Lifestyle Health Calls PLUS 4 Smart Alternative Activities (from Chart 2 below)</a>');
                    $('.view-calls').children(':eq(0)').html('<strong>B</strong>. <a href="/content/1094#2bhealthcall">'+
                                        'Smart U - Participate in 12 Lifestyle Calls (this does NOT include your Health Action Call)</a>');
                   
                   <?php if($hapCustomizedTrackViewStatus  == ComplianceViewStatus::COMPLIANT || $smartAlternativeAndHapLifestyleViewStatus == ComplianceViewStatus::COMPLIANT) : ?>
                        <?php if($hapCustomizedTrackViewStatus  != ComplianceViewStatus::COMPLIANT) : ?>
                            $('.view-hap_customized_track').addClass('gray_out');
                        <?php elseif($smartAlternativeAndHapLifestyleViewStatus != ComplianceViewStatus::COMPLIANT) : ?>
                            $('.view-smart_alternative_and_hap_lifestyle').addClass('gray_out');
                        <?php endif ?>
                   <?php endif ?>

               <?php endif ?>
                
               $('.headerRow-Smart td:eq(2)').html('Current Count');

                $('.headerRow-smart').before(
                        '<tr id="core_status">' +
                        '<td colspan="5" style="text-align:right"><strong>Your incentive requirements status as of <?php echo $asOfDate ?></strong>*</td>' +
                        '<td style="text-align:center"><img src="<?php echo $status->getComplianceViewGroupStatus('core')->getLight() ?>" class="light" /></td>' +
                        '<td id="section_complete_message"></td>' +
                        '</tr><tr style="height:50px;" class="blank_row"><td colspan="7"></td></tr>'
                );

                <?php if($status->getComplianceViewGroupStatus('core')->isCompliant()) : ?>
                    $('#section_complete_message').html('You are eligible to receive the 2015 participation rewards beginning in 2016');
                <?php endif ?>

                $('.view-self_smart').after(
                    '<tr id="num_of_green">' +
                    '<td colspan="3" style="text-align:right"><strong>Number of greens earned as of <?php echo $asOfDate ?></strong>*</td>' +
                    '<td colspan="2" style="text-align:center"><?php echo $status->getComplianceViewGroupStatus('smart')->getAttribute('number_completed_count') ?></td>' +
                    '<td style="text-align:center"><img src="<?php echo $status->getComplianceViewGroupStatus('smart')->getLight() ?>" class="light" />' +
                    '<td></td>' +
                    '</tr>'
                );

                $('.phipTable tr span.hide-view').parent('td').parent('tr').hide();
                
                $('.headerRow-smart').children(':eq(2), :eq(3)').remove();
                $('.view-lesson').children(':eq(2), :eq(3)').remove();
                $('.view-calls').children(':eq(2), :eq(3)').remove();
                $('.view-lunch').children(':eq(2), :eq(3)').remove();
                $('.view-weight').children(':eq(2), :eq(3)').remove();
                $('.view-dental').children(':eq(2), :eq(3)').remove();
                $('.view-fitness').children(':eq(2), :eq(3)').remove();
                $('.view-move_smart_challenge').children(':eq(2), :eq(3)').remove();
                $('.view-move_smart_class').children(':eq(2), :eq(3)').remove();
                $('.view-move_smart_personal_training').children(':eq(2), :eq(3)').remove();
                $('.view-self_smart').children(':eq(2), :eq(3)').remove();
                
                
                $('.headerRow-smart').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-lesson').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-calls').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-lunch').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-weight').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-dental').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-fitness').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-move_smart_challenge').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-move_smart_class').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-move_smart_personal_training').children(':eq(1), :eq(2)').attr('colspan', 2);
                $('.view-self_smart').children(':eq(1), :eq(2)').attr('colspan', 2);            
                
                $('.blank_row').prevAll().each(function(){
                   $(this).addClass('bolder_border');
                });
                
                $('#legend tr td').attr('colspan', 7);
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
