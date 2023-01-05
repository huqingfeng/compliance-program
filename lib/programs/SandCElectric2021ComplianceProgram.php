<?php

use hpn\steel\query\SelectQuery;

class SandCElectric2021CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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


class SandCElectric2021ComplianceProgram extends ComplianceProgram
{
    public function getTrack(User $user)
    {
        if($this->lastTrack && $this->lastTrack['user_id'] == $user->id) {
            return $this->lastTrack['track'];
        } else {
            $track = $user->getGroupValueFromTypeName('S&C Track 2021', '0-2 Risk Factors');

            $this->lastTrack = array('user_id' => $user->id, 'track' => $track);

            return $track;
        }
    }

    public function loadGroups()
    {
        global $_user;

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $req = new ComplianceViewGroup('core', 'Requirements');

        $ampSignup = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $ampSignup->setName('amp_signup');
        $ampSignup->setReportName('AMP UP! Sign Up! Card');
        $ampSignup->setAttribute('deadline', '06/30/2021');
        $ampSignup->addLink(new Link('Get AMP UP! Card', '/resources/10357/2019_AMP_UP_Sign_Up_Card.pdf'));
        $ampSignup->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($ampSignup);


        $screening = new SandCElectric2021CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screening->setAttribute('deadline', '06/30/2021');
        $screening->setReportName('Complete Biometric Screening');
        $screening->emptyLinks();
        $screening->setName('screening');
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/OnlineRegistrationRequest/LandingPage/7'));
        $screening->addLink(new Link('Results', '/content/989'));
        $screening->addLink(new Link('Physician Form', '/resources/10658/Form_846_0321_physician screening.pdf'));

        $req->addComplianceView($screening);

        $hra = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $hra->setReportName('Complete Health Risk Assessment');
        $hra->setAttribute('deadline', '06/30/2021');
        $hra->emptyLinks();
        $hra->setName('hra');
        $hra->addLink(new Link('Health Risk Assessment', "/surveys/39"));
        $hra->addLink(new Link('<br />Health Risk Assessment (PDF)', '/resources/10659/032021_Health_Risk_Assessment_AU.pdf'));
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hra->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $view = $status->getComplianceView();

            $surveyView = new CompleteSurveyComplianceView(39);
            $surveyView->setComplianceViewGroup($view->getComplianceViewGroup());
            $surveyView->setName('alternative_'.$view->getName());

            $surveyStatus = $surveyView->getStatus($user);

            if($surveyStatus->getStatus() == ComplianceStatus::COMPLIANT
                && date('Y-m-d', strtotime($surveyStatus->getComment())) >= '2020-10-01')
            {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        });
        $req->addComplianceView($hra);


        $physView = $this->getTempView(
            'physical',
            'Complete 1 Annual Physical',
            '09/30/2021',
            array(new Link('Exam Confirmation Form', '/resources/10484/Form_855_Exam_Confirmation_10_19.pdf'))
        );
        $physView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($physView);

        $focusClasses = $this->getTempView(
            'focus_classes_3',
            'Health Support: Attend six 30-minute Focus Classes for 3 Risk Factors',
            '09/30/2021'
        );
        $focusClasses->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $points = $status->getPoints();

            if($points >= 6) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } elseif($points >= 1) {
                $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            }
        });
        $req->addComplianceView($focusClasses);

        $physicianAttest = $this->getTempView(
            'physician_attest_3',
            'Health Support Alternative: Submit Physician Attest Form for 3 Risk Factors',
            '09/30/2021'
        );
        $req->addComplianceView($physicianAttest);

        $nurseCare = $this->getTempView(
            'nurse_care',
            'Health Support: Complete a 3-month engagement with Nurse Care Manager, or Dietician for 4+ Risk Factors',
            '09/30/2021'
        );
        $req->addComplianceView($nurseCare);

        $focusClasses = $this->getTempView(
            'focus_classes_4',
            'Health Support: Attend six 30-minute Focus Classes for 4+ Risk Factors',
            '09/30/2021'
        );
        $focusClasses->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $points = $status->getPoints();

            if($points >= 6) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } elseif($points >= 1) {
                $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            }
        });
        $req->addComplianceView($focusClasses);

        $physicianAttest = $this->getTempView(
            'physician_attest_4',
            'Health Support Alternative: Submit Physician Attest Form for 4+ Risk Factors',
            '09/30/2021'
        );
        $req->addComplianceView($physicianAttest);


        $nutritionAssessment = $this->getTempView(
            'nutrition_assessment',
            'Complete a Nutrition Goal Setting Questionnaire',
            '09/30/2021',
            array(new Link('Nutrition Goal Setting Questionnaire', 'https://aahaaw.iad1.qualtrics.com/jfe/form/SV_0oinxXKluLxHDU2', false, '_blank'))
        );

        $fitnessAssessment = $this->getTempView(
            'fitness_assessment',
            'Complete a Fitness Goal Setting Questionnaire',
            '09/30/2021',
            array(new Link('Fitness Goal Setting Questionnaire', 'https://aahaaw.iad1.qualtrics.com/jfe/form/SV_1HpPoNuXxvhKnps', false, '_blank'))
        );

        $livongoProgram = $this->getTempView(
            'livongo_program',
            'Participation in the appropriate Livongo program (as determined by the Livongo program manager)',
            '09/30/2021'
        );

        $physicianAttestationForm = $this->getTempView(
            'physician_attestation_form',
            'Submission of the completed Physician Attestation Form 1087 that indicates you are managing your condition with the help of your PCP or other appropriate medical professional',
            '09/30/2021',
            array(new Link('Physician Attestation Form', '/resources/10657/Form_1087_Physician_attestation_0421.pdf', false, '_blank'))
        );

        $registeredNurse = $this->getTempView(
            'registered_nurse',
            '3-Month Engagement with Registered Nurse',
            '09/30/2021',
            array(new FakeLink('To sign-up, email Carol at <a href="mailto:Carol.Olbur@aah.org">Carol.Olbur@aah.org</a>', '#'))
        );

        $focusClasses = $this->getTempView(
            'focus_classes',
            'Attend 4 Focus Classes',
            '09/30/2021'
        );


        if($_user instanceof User && $this->getTrack($_user) == 'SCENARIO A (BLOOD DRAW)') {
            $req->addComplianceView($nutritionAssessment);
            $req->addComplianceView($fitnessAssessment);
            $req->addComplianceView($livongoProgram);
            $req->addComplianceView($physicianAttestationForm);
        } elseif($_user instanceof User && $this->getTrack($_user) == 'SCENARIO B (BLOOD DRAW)') {
            $req->addComplianceView($livongoProgram);
            $req->addComplianceView($physicianAttestationForm);
            $req->addComplianceView($focusClasses);
        } elseif($_user instanceof User && $this->getTrack($_user) == 'SCENARIO C (BLOOD DRAW)') {
            $req->addComplianceView($registeredNurse);
            $req->addComplianceView($livongoProgram);
            $req->addComplianceView($physicianAttestationForm);
        } else {
            $req->addComplianceView($nutritionAssessment);
            $req->addComplianceView($fitnessAssessment);
            $req->addComplianceView($registeredNurse);
            $req->addComplianceView($livongoProgram);
            $req->addComplianceView($physicianAttestationForm);
            $req->addComplianceView($focusClasses);
        }

        $this->addComplianceViewGroup($req);



        $settingsGroup = new ComplianceViewGroup('settings', 'Settings');

        $asOfDate = new PlaceHolderComplianceView();
        $asOfDate->setName('as_of_date');
        $asOfDate->setReportName('As Of Date');
        $settingsGroup->addComplianceView($asOfDate);

        $this->addComplianceViewGroup($settingsGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SandCElectric2021ComplianceProgramReportPrinter();
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
            $track = $user->getGroupValueFromTypeName('S&C Track 2021', '0-2 Risk Factors');

            return $track;
        });

        $printer->addCallbackField('Health Action Plan Completion', function (User $user) use($program) {
            $program->setActiveUser($user);
            $GHIndicator = $program->getGHIndicator();

            return $GHIndicator;
        });

        $printer->addCallbackField('Weigh In Screening Date', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2020-10-01', '2021-09-30');

            return isset($data['date']) ? $data['date'] : null;
        });

        $printer->addCallbackField('Weigh In - Height', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2020-10-01', '2021-09-30');

            return isset($data['height']) ? $data['height'] : null;
        });

        $printer->addCallbackField('Weigh In - Weight', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2020-10-01', '2021-09-30');

            return isset($data['weight']) ? $data['weight'] : null;
        });

        $printer->addCallbackField('Weigh In - BMI', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2020-10-01', '2021-09-30');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            return isset($bmi) ? $bmi : null;
        });

        $printer->addCallbackField('Weigh In - Body Fat ', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2020-10-01', '2021-09-30');

            return isset($data['bodyfat']) ? $data['bodyfat'] : null;
        });

        $printer->addCallbackField('Weigh In - Program Goal', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2020-10-01', '2021-09-30');

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
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2020-10-01', '2021-09-30');

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
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2021-10-01', '2021-11-06');

            return isset($data['date']) ? $data['date'] : null;
        });

        $printer->addCallbackField('Weight Out - Height', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2021-10-01', '2021-11-06');

            return isset($data['height']) ? $data['height'] : null;
        });

        $printer->addCallbackField('Weight Out - Weight', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2021-10-01', '2021-11-06');

            return isset($data['weight']) ? $data['weight'] : null;
        });

        $printer->addCallbackField('Weight Out - BMI', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2021-10-01', '2021-11-06');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            return isset($bmi) ? $bmi : null;
        });

        $printer->addCallbackField('Weight Out - Body Fat ', function (User $user) {
            $data = SandCElectric2021ComplianceProgram::getScreeningData($user, '2021-10-01', '2021-11-06');

            return isset($data['bodyfat']) ? $data['bodyfat'] : null;
        });

        return $printer;
    }

    public static function getScreeningData(User $user, $startDate = '2020-10-01', $endDate = '2021-09-30')
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
            'physical'
        );

        return $allRequiredViews;
    }

    public function getTrackRequiredCoreViews(User $user)
    {
        $requiredCoreViews = array(
            '0-2 Risk Factors'  => array(

            ),
            '3 Risk Factors'  => array(
                'focus_classes_3',
                'physician_attest_3'
            ),
            '4+ Risk Factors'  => array(
                'nurse_care',
                'focus_classes_4',
                'physician_attest_4'
            ),
            'RESULTS IN RANGE (BLOOD DRAW)'  => array(

            ),
            'SCENARIO A (BLOOD DRAW)'  => array(
                'nutrition_assessment',
                'fitness_assessment',
                'livongo_program',
                'physician_attestation_form',
            ),
            'SCENARIO B (BLOOD DRAW)'  => array(
                'livongo_program',
                'physician_attestation_form',
                'focus_classes'
            ),
            'SCENARIO C (BLOOD DRAW)'  => array(
                'registered_nurse',
                'livongo_program',
                'physician_attestation_form',
            ),
        );

        $track = trim($this->getTrack($user));

        return $requiredCoreViews[$track];
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();
        $track = trim($this->getTrack($user));

        $allRequiredCoreViews = $this->getAllRequiredCoreViews();
        $trackRequiredCoreViews = $this->getTrackRequiredCoreViews($user);

        $allRequiredCompliant = true;
        foreach($allRequiredCoreViews as $allRequiredView) {
            $viewStatus = $status->getComplianceViewStatus($allRequiredView);
            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                $allRequiredCompliant = false;
            }
        }


        if($track == '0-2 Risk Factors') {
            if($allRequiredCompliant) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } else {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }


        } elseif ($track == '3 Risk Factors' || $track == 'SCENARIO A (BLOOD DRAW)' || $track == 'SCENARIO B (BLOOD DRAW)' || $track == 'SCENARIO C (BLOOD DRAW)') {
            $requiredCoreCompliant = false;

            foreach($trackRequiredCoreViews as $requiredView) {
                $viewStatus = $status->getComplianceViewStatus($requiredView);
                if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $requiredCoreCompliant = true;
                }
            }

            if($requiredCoreCompliant) {
                foreach($trackRequiredCoreViews as $requiredView) {
                    $viewStatus = $status->getComplianceViewStatus($requiredView);
                    $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                }
            }

            if($allRequiredCompliant && $requiredCoreCompliant) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } else {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }

        } elseif ($track == '4+ Risk Factors') {
            $requiredCoreCompliant = false;


            $nurseCareStatus = $status->getComplianceViewStatus('nurse_care');
            $focusClassesStatus = $status->getComplianceViewStatus('focus_classes_4');
            $physicianAttestStatus = $status->getComplianceViewStatus('physician_attest_4');

            if(($nurseCareStatus->getStatus() == ComplianceStatus::COMPLIANT
                && $focusClassesStatus->getStatus() == ComplianceStatus::COMPLIANT)
                || $physicianAttestStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $requiredCoreCompliant = true;
            }

            if($requiredCoreCompliant) {
                foreach($trackRequiredCoreViews as $requiredView) {
                    $viewStatus = $status->getComplianceViewStatus($requiredView);
                    $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                }
            }

            if($allRequiredCompliant && $requiredCoreCompliant) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } else {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }

        }

    }

    private function getTempView($name, $reportName, $deadline, array $links = array(), $defaultStatus = ComplianceStatus::NOT_COMPLIANT)
    {
        $ageAppropriate = new PlaceHolderComplianceView($defaultStatus);
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

class SandCElectric2021ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    protected function showGroup($group)
    {
        $groupName = $group->getName();

        $this->tableHeaders['completed'] = 'Count Completed';

        return $groupName != 'settings';
    }

    protected function getCompleted(ComplianceViewGroup $group,
                                    ComplianceViewStatus $viewStatus)
    {
        return $viewStatus->getPoints() != '' ? $viewStatus->getPoints() : 0;
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


        $this->addStatusCallbackColumn('Date Satisfied', function (ComplianceViewStatus $status) use($program, $user) {
            return $status->getComment();
        });

        $startDate = $status->getComplianceProgram()->getEndDate('F d, Y');

        $this->setShowLegend(true);
        $this->setShowTotal(false);
        $this->pageHeading = '2021 Wellness Initiative Program';

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

                <?php if($track == '3 Risk Factors') : ?>
                $('.view-focus_classes_3').children(':eq(0)').attr('rowspan', 2);
                $('.view-focus_classes_3').children(':eq(0)').html('<strong>E</strong>. Health Support: Attend six 30-minute Focus Classes' +
                '<br /><span style="padding-left: 120px;">OR</span><br />'+
                '<strong>F</strong>. Health Support Alternative: Submit Physician Attest Form');
                $('.view-focus_classes_3').children('.links').attr('rowspan', 2);
                $('.view-focus_classes_3').children('.links').html('<a href="/resources/10485/Form_1087_Physician_attestation_original_10_19.pdf">Physician Attestation Form</a>');

                $('.view-physician_attest_3').children(':eq(0)').remove();
                $('.view-physician_attest_3').children('.links').remove();

                <?php elseif($track == '4+ Risk Factors') : ?>
                $('.view-nurse_care').children(':eq(0)').attr('rowspan', 3);
                $('.view-nurse_care').children(':eq(0)').html('<strong>E</strong>. Health Support: Complete a 3-month engagement with Nurse Care Manager, or Dietician' +
                    '<br /><span style="padding-left: 120px;">AND</span><br />'+
                    '<strong>F</strong>. Health Support: Attend six 30-minute Focus Classes' +
                    '<br /><span style="padding-left: 120px;">OR</span><br />'+
                    '<strong>G</strong>. Health Support Alternative: Submit Physician Attest Form');
                $('.view-nurse_care').children('.links').attr('rowspan', 3);
                $('.view-nurse_care').children('.links').html('<a href="/resources/10485/Form_1087_Physician_attestation_original_10_19.pdf">Physician Attestation Form</a>');

                $('.view-focus_classes_4').children(':eq(0)').remove();
                $('.view-physician_attest_4').children(':eq(0)').remove();

                $('.view-focus_classes_4').children('.links').remove();
                $('.view-physician_attest_4').children('.links').remove();

                <?php elseif($track == 'RESULTS IN RANGE (BLOOD DRAW)') : ?>
                $('.view-physical').children('.links').append('<a href="/resources/10646/physical_exam_chart_track4.jpg" target="_blank"><br />Physical Exam Frequency chart</a>');

                <?php elseif($track == 'SCENARIO A (BLOOD DRAW)') : ?>
                $('.view-physical').children('.links').append('<a href="/resources/10646/physical_exam_chart_track4.jpg" target="_blank"><br />Physical Exam Frequency chart</a>');

                $('.view-nutrition_assessment').children(':eq(0)').attr('rowspan', 4);
                $('.view-nutrition_assessment').children(':eq(0)').html('<strong>E</strong>. Next Steps: PICK ONE of the action items below' +
                    '<br /><ul style="list-style: none"><li>Complete a Nutrition Goal Setting Questionnaire'+
                    '<br /><span style="padding-left: 120px;">OR</span></li>'+
                    '<li>Complete a Fitness Goal Setting Questionnaire' +
                    '<br /><span style="padding-left: 120px;">OR</span></li>'+
                    '<li>Participation in the appropriate Livongo program (as determined by the Livongo program manager)' +
                    '<br /><span style="padding-left: 120px;">OR</span></li>'+
                    '<li>Submission of the completed Physician Attestation Form 1087 that indicates you are managing your condition with the help of your PCP or other appropriate medical professional</li></ul>');

                $('.view-fitness_assessment').children(':eq(0)').remove();
                $('.view-livongo_program').children(':eq(0)').remove();
                $('.view-physician_attestation_form').children(':eq(0)').remove();

                $('.view-nutrition_assessment').children('.links').css('height', '50px');
                $('.view-fitness_assessment').children('.links').css('height', '35px');
                $('.view-physician_attestation_form').children('.links').css('height', '100px');

                <?php elseif($track == 'SCENARIO B (BLOOD DRAW)') : ?>
                $('.view-physical').children('.links').append('<a href="/resources/10646/physical_exam_chart_track4.jpg" target="_blank"><br />Physical Exam Frequency chart</a>');

                $('.view-livongo_program').children(':eq(0)').attr('rowspan', 3);
                $('.view-livongo_program').children(':eq(0)').html('<strong>E</strong>. Next Steps: PICK ONE of the action items below' +
                    '<br /><ul style="list-style: none"><li>Participation in the appropriate Livongo program (as determined by the Livongo program manager)'+
                    '<br /><span style="padding-left: 120px;">OR</span></li>'+
                    '<li>Submission of the completed Physician Attestation Form 1087 that indicates you are managing your condition with the help of your PCP or other appropriate medical professional' +
                    '<br /><span style="padding-left: 120px;">OR</span></li>'+
                    '<li style="margin-left: 60px;">Attend 4 Focus Classes</li></ul>');

                $('.view-physician_attestation_form').children(':eq(0)').remove();
                $('.view-focus_classes').children(':eq(0)').remove();

                $('.view-livongo_program').children('.links').css('height', '65px');
                $('.view-physician_attestation_form').children('.links').css('height', '100px');

                <?php elseif($track == 'SCENARIO C (BLOOD DRAW)') : ?>
                $('.view-physical').children('.links').append('<a href="/resources/10646/physical_exam_chart_track4.jpg" target="_blank"><br />Physical Exam Frequency chart</a>');

                $('.view-registered_nurse').children(':eq(0)').attr('rowspan', 3);
                $('.view-registered_nurse').children(':eq(0)').html('<strong>E</strong>. Next Steps: PICK ONE of the action items below' +
                    '<br /><ul style="list-style: none"><li>3-Month Engagement with Registered Nurse'+
                    '<br /><span style="padding-left: 120px;">OR</span></li>'+
                    '<li>Participation in the appropriate Livongo program (as determined by the Livongo program manager)' +
                    '<br /><span style="padding-left: 120px;">OR</span></li>'+
                    '<li>Submission of the completed Physician Attestation Form 1087 that indicates you are managing your condition with the help of your PCP or other appropriate medical professional</li></ul>');

                $('.view-livongo_program').children(':eq(0)').remove();
                $('.view-physician_attestation_form').children(':eq(0)').remove();

                $('.view-registered_nurse').children('.links').css('height', '50px');
                $('.view-physician_attestation_form').children('.links').css('height', '100px');


                <?php endif ?>

                $('#legend tr td').attr('colspan', 7);
            });
        </script>

        <div class="row">
            <div class="span4">
                <?php echo $status->getUser()->getFullName() ?>
            </div>
        </div>
        <p></p>
        <p style="color:red;margin-left:24px;">Note: Some actions you took within the past 30-60 days may not show until next month. Please
            allow 30-60 days for updates relying on claims and/or any required forms you have submitted.</p>
        <p>If you have any questions/concerns about your report card please contact the AMP UP! Help Desk 800-761-5856 (M-F from 8am-8pm CST)</p>

        <div class="row" style="text-align: center; font-weight: bold;">
            <?php echo $status->getComplianceProgram()->getTrack($status->getUser()) ?>
        </div><br/>
        <?php
    }
}
