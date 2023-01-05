<?php

use hpn\steel\query\SelectQuery;

class SandCElectric2019CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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


class SandCElectric2019ComplianceProgram extends ComplianceProgram
{
    public function getTrack(User $user)
    {
        if($this->lastTrack && $this->lastTrack['user_id'] == $user->id) {
            return $this->lastTrack['track'];
        } else {
            $track = $user->getGroupValueFromTypeName('S&C Track 2019', '0-2 Risk Factors');

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
        $ampSignup->setAttribute('deadline', '03/31/2019');
        $ampSignup->addLink(new Link('Get AMP UP! Card', '/resources/10357/2019_AMP_UP_Sign_Up_Card.pdf'));
        $ampSignup->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($ampSignup);
        $this->addComplianceViewGroup($req);

        $screening = new SandCElectric2019CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screening->setAttribute('deadline', '03/31/2019');
        $screening->setReportName('Complete Biometric Screening');
        $screening->emptyLinks();
        $screening->setName('screening');
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/OnlineRegistrationRequest/LandingPage/7'));
        $screening->addLink(new Link('Results', '/content/989'));
        $screening->addLink(new Link('Physician Form', '/resources/10358/Physician Biometric Screening Collection Form_S&C 2019.pdf'));

        $req->addComplianceView($screening);

        $hra = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $hra->setReportName('Complete Health Risk Assessment');
        $hra->setAttribute('deadline', '03/31/2019');
        $hra->emptyLinks();
        $hra->setName('hra');
        $hra->addLink(new Link('Health Risk Assessment', "/surveys/39"));
        $hra->addLink(new Link('Health Risk Assessment (PDF)', '/resources/10359/2019_Short_Form_HRA_AMP_UP.pdf'));
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hra->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $view = $status->getComplianceView();

            $surveyView = new CompleteSurveyComplianceView(39);
            $surveyView->setComplianceViewGroup($view->getComplianceViewGroup());
            $surveyView->setName('alternative_'.$view->getName());

            $surveyStatus = $surveyView->getStatus($user);

            if($surveyStatus->getStatus() == ComplianceStatus::COMPLIANT
                && date('Y-m-d', strtotime($surveyStatus->getComment())) >= '2018-10-01')
            {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        });
        $req->addComplianceView($hra);


        $physView = $this->getTempView(
            'physical',
            'Complete 1 Annual Physical',
            '09/30/2019'
        );
        $physView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $req->addComplianceView($physView);

        $coreDiabetes = $this->getTempView(
            'core_diabetes',
            'Health Direction: Diabetes Prevention and Management – Attend 6 Focus Group Classes',
            '09/30/2019'
        );
        $req->addComplianceView($coreDiabetes);

        $coreBloodPressure = $this->getTempView(
            'core_blood_pressure',
            'Health Direction: Blood Pressure and Stress Management – Attend 6 Focus Group Classes',
            '09/30/2019'
        );
        $req->addComplianceView($coreBloodPressure);

        $coreNutrition = $this->getTempView(
            'core_nutrition',
            'Health Direction: Nutrition and Weight Management – Attend 6 Focus Group Classes',
            '09/30/2019'
        );
        $req->addComplianceView($coreNutrition);

        $coreMonthEngagementFocusClasses = $this->getTempView(
            'core_month_engagement_focus_classes',
            'Health Direction: 3-month engagement AND attend 6 focus classes assigned by Nurse Care Manager',
            '09/30/2019',
            array(new Link('Physician Attest Form (Alternative)', '/resources/10360/2019_AMP_UP_Physician_Attest_Form.pdf'))
        );
        $req->addComplianceView($coreMonthEngagementFocusClasses);




        $focusGroup = new ComplianceViewGroup('focus', 'Focus Groups');

        $focusDiabetesView = $this->getTempView(
            'focus_diabetes',
            'Diabetes Prevention and Management - Attend Focus Group',
            '09/30/2019',
            array(),
            ComplianceStatus::NA_COMPLIANT
        );
        $focusGroup->addComplianceView($focusDiabetesView);

        $focusBloodPressureView = $this->getTempView(
            'focus_blood_pressure',
            'Blood Pressure and Stress Management - Attend Focus Group',
            '09/30/2019',
            array(),
            ComplianceStatus::NA_COMPLIANT
        );
        $focusGroup->addComplianceView($focusBloodPressureView);

        $focusNutritionView = $this->getTempView(
            'focus_nutrition',
            'Nutrition and Weight Management - Attend Focus Group',
            '09/30/2019',
            array(),
            ComplianceStatus::NA_COMPLIANT
        );
        $focusGroup->addComplianceView($focusNutritionView);


        $this->addComplianceViewGroup($focusGroup);



        $settingsGroup = new ComplianceViewGroup('settings', 'Settings');

        $asOfDate = new PlaceHolderComplianceView();
        $asOfDate->setName('as_of_date');
        $asOfDate->setReportName('As Of Date');
        $settingsGroup->addComplianceView($asOfDate);

        $this->addComplianceViewGroup($settingsGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SandCElectric2019ComplianceProgramReportPrinter();
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
            $track = $user->getGroupValueFromTypeName('S&C Track 2019', '0-2 Risk Factors');

            return $track;
        });

        $printer->addCallbackField('Health Action Plan Completion', function (User $user) use($program) {
            $program->setActiveUser($user);
            $GHIndicator = $program->getGHIndicator();

            return $GHIndicator;
        });

        $printer->addCallbackField('Weigh In Screening Date', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            return isset($data['date']) ? $data['date'] : null;
        });

        $printer->addCallbackField('Weigh In - Height', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            return isset($data['height']) ? $data['height'] : null;
        });

        $printer->addCallbackField('Weigh In - Weight', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            return isset($data['weight']) ? $data['weight'] : null;
        });

        $printer->addCallbackField('Weigh In - BMI', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            return isset($bmi) ? $bmi : null;
        });

        $printer->addCallbackField('Weigh In - Body Fat ', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            return isset($data['bodyfat']) ? $data['bodyfat'] : null;
        });

        $printer->addCallbackField('Weigh In - Program Goal', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-03-31');

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
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-03-31');

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
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            return isset($data['date']) ? $data['date'] : null;
        });

        $printer->addCallbackField('Weight Out - Height', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            return isset($data['height']) ? $data['height'] : null;
        });

        $printer->addCallbackField('Weight Out - Weight', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            return isset($data['weight']) ? $data['weight'] : null;
        });

        $printer->addCallbackField('Weight Out - BMI', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
                $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
            }

            return isset($bmi) ? $bmi : null;
        });

        $printer->addCallbackField('Weight Out - Body Fat ', function (User $user) {
            $data = SandCElectric2019ComplianceProgram::getScreeningData($user, '2018-10-01', '2019-09-30');

            return isset($data['bodyfat']) ? $data['bodyfat'] : null;
        });

        return $printer;
    }

    public static function getScreeningData(User $user, $startDate = '2018-10-01', $endDate = '2019-09-30')
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
                'core_diabetes',
                'core_blood_pressure',
                'core_nutrition'
            ),
            '4+ Risk Factors'  => array(
                'core_month_engagement_focus_classes',
            )
        );

        $track = trim($this->getTrack($user));

        return $requiredCoreViews[$track];
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();
        $track = trim($this->getTrack($user));

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $focusGroupStatus = $status->getComplianceViewGroupStatus('focus');

        $allRequiredCoreViews = $this->getAllRequiredCoreViews();
        $trackRequiredCoreViews = $this->getTrackRequiredCoreViews($user);

        $allRequiredCompliant = true;
        foreach($allRequiredCoreViews as $allRequiredView) {
            $viewStatus = $status->getComplianceViewStatus($allRequiredView);
            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                $allRequiredCompliant = false;
            }
        }

        foreach($focusGroupStatus->getComplianceViewStatuses() as $focusViewStatus) {
            if($focusViewStatus->getPoints() > 0) {
                $focusViewStatus->setStatus(ComplianceStatus::COMPLIANT);
            }
        }

        if($track == '0-2 Risk Factors') {
            if($allRequiredCompliant) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } else {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }


        } elseif ($track == '3 Risk Factors') {
            $requiredCoreCompliant = false;

            foreach($trackRequiredCoreViews as $requiredView) {
                $viewStatus = $status->getComplianceViewStatus($requiredView);
                $coreViewPoints = $viewStatus->getPoints();

                if($requiredView == 'core_diabetes') {
                    $focusViewStatus = $status->getComplianceViewStatus('focus_diabetes');
                    $focusViewPoints = $focusViewStatus->getPoints();

                    if($coreViewPoints == 0 && $focusViewPoints >=1) {
                        $viewStatus->setPoints($focusViewPoints);
                    }

                    if($viewStatus->getPoints() >= 6) {
                        $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                        $requiredCoreCompliant = true;
                    }

                } elseif($requiredView == 'core_blood_pressure') {
                    $focusViewStatus = $status->getComplianceViewStatus('focus_blood_pressure');
                    $focusViewPoints = $focusViewStatus->getPoints();

                    if($coreViewPoints == 0 && $focusViewPoints >=1) {
                        $viewStatus->setPoints($focusViewPoints);
                    }

                    if($viewStatus->getPoints() >= 6) {
                        $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                        $requiredCoreCompliant = true;
                    }

                } elseif($requiredView == 'core_nutrition') {
                    $focusViewStatus = $status->getComplianceViewStatus('focus_nutrition');
                    $focusViewPoints = $focusViewStatus->getPoints();

                    if($coreViewPoints == 0 && $focusViewPoints >=1) {
                        $viewStatus->setPoints($focusViewPoints);
                    }

                    if($viewStatus->getPoints() >= 6) {
                        $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                        $requiredCoreCompliant = true;
                    }
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
            $totalFocusPoints = 0;

            foreach($trackRequiredCoreViews as $requiredView) {
                $viewStatus = $status->getComplianceViewStatus($requiredView);
                $coreViewPoints = $viewStatus->getPoints();

                if($requiredView == 'core_month_engagement_focus_classes') {
                    $focusViewStatus = $status->getComplianceViewStatus('focus_diabetes');
                    $totalFocusPoints += $focusViewStatus->getPoints();

                    $focusViewStatus = $status->getComplianceViewStatus('focus_blood_pressure');
                    $totalFocusPoints += $focusViewStatus->getPoints();

                    $focusViewStatus = $status->getComplianceViewStatus('focus_nutrition');
                    $totalFocusPoints += $focusViewStatus->getPoints();

                    if($viewStatus->getPoints() >= 3 && $totalFocusPoints >= 6) {
                        $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                        $requiredCoreCompliant = true;
                    }
                }
            }

            $focusGroupStatus->setPoints($totalFocusPoints);

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

class SandCElectric2019ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
        $this->pageHeading = '2019 Wellness Initiative Program';

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
                $('.view-core_diabetes').children(':eq(0)').attr('rowspan', 3);
                $('.view-core_diabetes').children(':eq(0)').html('<strong>E</strong>. Health Direction: Diabetes Prevention and Management – Attend 6 Focus Group Classes' +
                '<br /><span style="padding-left: 120px;">OR</span><br />'+
                '<strong>F</strong>. Health Direction: Blood Pressure and Stress Management – Attend 6 Focus Group Classes' +
                '<br /><span style="padding-left: 120px;">OR</span><br />'+
                '<strong>G</strong>. Health Direction: Nutrition and Weight Management – Attend 6 Focus Group Classes');
                $('.view-core_diabetes').children('.links').attr('rowspan', 3);
                $('.view-core_diabetes').children('.links').html('<a target="_self" href="/resources/10360/2019_AMP_UP_Physician_Attest_Form.pdf">Physician Attest Form (Alternative)</a>');

                $('.view-core_blood_pressure').children(':eq(0)').remove();
                $('.view-core_nutrition').children(':eq(0)').remove();
                $('.view-core_blood_pressure').children('.links').remove();
                $('.view-core_nutrition').children('.links').remove();

                <?php elseif($track == '4+ Risk Factors') : ?>
                $('.view-core_month_engagement_focus_classes').children(':eq(0)').html('<strong>E</strong>. Health Direction: 3-month engagement AND attend 6 focus classes assigned by Nurse Care Manager');

                <?php endif ?>


                $('.headerRow-smart').before(
                    '<tr id="core_status">' +
                    '<td colspan="4" style="text-align:right"><strong>Your incentive requirements status as of <?php echo $asOfDate ?></strong>*</td>' +
                    '<td style="text-align:center"><img src="<?php echo $status->getComplianceViewGroupStatus('core')->getLight() ?>" class="light" /></td>' +
                    '<td class="links"><a href="/resources/8767/Report Card Update Clarifications 2017.pdf" target="_blank">When Will My Report Card Be Updated?</a></td>' +
                    '</tr><tr style="height:50px;" class="blank_row"><td colspan="7"></td></tr>'
                );

                $('.headerRow-focus').children(':eq(2)').remove();
                $('.headerRow-focus').children(':eq(2)').attr('colspan', 2);


                $('.view-focus_nutrition').after(
                    '<tr id="num_of_green">' +
                    '<td colspan="2" style="text-align:right"><strong>Number of greens earned as of <?php echo $asOfDate ?></strong>*</td>' +
                    '<td colspan="2" style="text-align:center"><?php echo $status->getComplianceViewGroupStatus('focus')->getPoints()?></td>' +
                    '<td style="text-align:center"><img src="<?php echo $status->getLight() ?>" class="light" />' +
                    '<td></td>' +
                    '</tr>'
                );


                $('.headerRow-smart').children(':eq(2)').remove();
                $('.view-focus_diabetes').children(':eq(2)').remove();
                $('.view-focus_blood_pressure').children(':eq(2)').remove();
                $('.view-focus_nutrition').children(':eq(2)').remove();


                $('.headerRow-smart').children(':eq(2)').attr('colspan', 2);
                $('.view-focus_diabetes').children(':eq(2)').attr('colspan', 2);
                $('.view-focus_blood_pressure').children(':eq(2)').attr('colspan', 2);
                $('.view-focus_nutrition').children(':eq(2)').attr('colspan', 2);



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
