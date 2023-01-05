<?php

class PekinHospital2017ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new PekinHospital2017ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $coreGroup = new ComplianceViewGroup('core', 'Program');
        $coreGroup->setPointsRequiredForCompliance(1);

        $clientProgramMapper = $this->getClientProgramMapper();

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('1. Test Negative for Cotinine (click for free options)');
        $cotinineView->setName('cotinine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative');
        $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($clientProgramMapper) {
            if(isset($clientProgramMapper[$user->getClientId()])) {
                $programId = $clientProgramMapper[$user->getClientId()];
            } else {
                $programId = 1018;
            }

            $view = $status->getComplianceView();

            $view->setReportName(sprintf(
                    '1. Test Negative for Cotinine (<a href="/compliance_programs/localAction?id=%s&local_action=cotinine_free_options">click for free options</a>)',
                    $programId)
            );
        });
        $coreGroup->addComplianceView($cotinineView);

        $learningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $learningView->setReportName('2. OR complete 6 Tobacco E-Lessons (<a href="/content/9420?action=lessonManager&tab_alias=tobacco">click to-do</a>)');
        $learningView->setName('elearning');
        $learningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $learningView->setNumberRequired(6);
        $learningView->setAttribute('goal', '6	lessons');
        $coreGroup->addComplianceView($learningView);

        $this->addComplianceViewGroup($coreGroup);

        $healthGroup = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $healthGroup->setPointsRequiredForCompliance(3);

        $waistView = new ComplyWithWaistScreeningTestComplianceView($programStart, $programEnd);
        $waistView->setReportName('1. Waist');
        $waistView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $waistView->overrideTestRowData(null, null, 40, null, 'M');
        $waistView->overrideTestRowData(null, null, 35, null, 'F');
        $waistView->setAttribute('goal', '≤40 Men / ≤35 Women');
        $healthGroup->addComplianceView($waistView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('2. HDL Cholesterol');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->overrideTestRowData(null, 40, null, null, 'M');
        $hdlView->overrideTestRowData(null, 50, null, null, 'F');
        $hdlView->setAttribute('goal', '≥ 40 Men /  ≥50 Women');
        $healthGroup->addComplianceView($hdlView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglyceridesView->setReportName('3. Triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setAttribute('goal', '<150');
        $healthGroup->addComplianceView($triglyceridesView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('4. Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setAttribute('goal', '<100');
        $healthGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setReportName('5. Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, 89);
        $bloodPressureView->setAttribute('goal', '130/<85');
        $healthGroup->addComplianceView($bloodPressureView);

        $this->addComplianceViewGroup($healthGroup);


        $optionGroup = new ComplianceViewGroup('options', 'Options');
        $optionGroup->setPointsRequiredForCompliance(1);

        $qualificationFormView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $qualificationFormView->setReportName('Turn-In a Completed Alternate Qualification Form (click for form & details); OR');
        $qualificationFormView->setName('qualification_form');
        $qualificationFormView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $qualificationFormView->setAttribute('goal', '<div style="margin-top: 5px;">Turn in by 4/21/17</div>');
        $optionGroup->addComplianceView($qualificationFormView);

        $coachingProgramView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $coachingProgramView->setReportName('Enroll in and complete Pekin Wellness Coaching Program (click for details)');
        $coachingProgramView->setName('coaching_program');
        $coachingProgramView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $coachingProgramView->setAttribute('goal', '<div style="margin-top: 5px;">Complete by 4/21/17</div>');
        $coachingProgramView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($clientProgramMapper) {
            if(isset($clientProgramMapper[$user->getClientId()])) {
                $programId = $clientProgramMapper[$user->getClientId()];
            } else {
                $programId = 1018;
            }

            $view = $status->getComplianceView();

            $view->setReportName(sprintf(
                    'b. Enroll in and complete Pekin Wellness Coaching  Program (<a href="/compliance_programs/localAction?id=%s&local_action=enroll_coaching_program">click for details</a>)',
                    $programId)
            );
        });
        $optionGroup->addComplianceView($coachingProgramView);

        $this->addComplianceViewGroup($optionGroup);
    }

    public function getClientProgramMapper()
    {
        return array(
            '3334'  => 1018,
            '3340'  => 1036,
            '3379'  => 1039
        );
    }

    public function getLocalActions()
    {
        return array(
            'cotinine_free_options'   => array($this, 'executeCotinineFreeOptions'),
            'enroll_coaching_program'   => array($this, 'executeEnrollCoachingProgram')
        );
    }


    public function executeCotinineFreeOptions(sfActions $actions)
    {
        ?>
        <p class="alert alert-info">
            The Cotinine test is included in the wellness screening for employees offered at Pekin Hospital. <br /><br />

            If interested, spouses can call Human Resources for occupational health clinics where the cotinine test is available.
        </p>

        <?php
    }

    public function executeEnrollCoachingProgram(sfActions $actions)
    {
        ?>
        <p class="alert alert-info">
            Enroll and complete Wellness Coaching Program. <br /><br />

            This is an individualized program designed to meet personal goals. Employees who choose this option will have: <br /><br />

            • Four coaching sessions with Adrienne Southerland (309.353.0204), our Health and Wellness Coordinator; AND<br />
            • Two coaching sessions with Susette Litwiller (309.353.0554), our Employee Health Nurse.<br /><br />

            During these sessions they will review each employee’s results with them and provide an individualized plan to start making healthy lifestyle changes.
        </p>

        <?php
    }

    const PERKIN_HOSPITAL_2017_RECORD_ID = 1018;
    const PERKIN_HOSPITAL_SPOUSE_CLIENT_ID = 3379;
}

class PekinHospital2017ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();
        $client = $user->getClient();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css">
            .headerRow {
                background-color:#639aff;
                font-weight:bold;
                font-size:10pt;
                height:30px;
            }

            .bund {
                font-weight:bold;
                text-decoration:underline;
            }

            <?php if (!sfConfig::get('app_wms2')) : ?>
            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                width:8.5in;
                height:11in;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                width:7.6in;
                margin:0 0.5in;
            }

            #results th, td {
                padding:0.01in 0.05in;
                border:0.01in solid #000;
                text-align:center;
                padding: 1px;
            }


            #results .status-<?php echo ComplianceStatus::COMPLIANT ?> {
                background-color:#90FF8C;
            }

            #results .status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?> {
                background-color:#F9FF8C;
            }

            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                background-color:#DEDEDE;
            }

            #results .status-not-compliant {
                background-color: #f95273;
            }

            #text-area-top div{
                font-size: 10pt;
                margin-bottom: 2px;
            }

            #text-area-bottom div{
                font-size: 11px;
                margin: 0;
            }

            .alignleft {
                float: left;
            }
            .alignright {
                float: right;
            }

        </style>

        <style type="text/css" media="print">
            body {
                margin:0.5in;
                padding:0;
            }
        </style>

        <div class="letter">
            <p style="clear: both;">
                <div style="float: left;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4255 Westbrook Drive #223<br />
                    Aurora, IL 60504
                </div>

                <div style="float: right;">
                    <img src="/images/empower/perkin_hospital_logo.jpg" style="height:88px;"  />
                </div>
            </p>

            <p style="margin-left:3in; padding-top:.56in; clear: both;">
                <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <div id="text-area-top">
                <div>
                    <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                    <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
                </div>

                <?php if($user->getClientId() == PekinHospital2017ComplianceProgram::PERKIN_HOSPITAL_SPOUSE_CLIENT_ID) : ?>

                    <p>Pekin Hospital is committed to encouraging and helping support the health, care, and wellbeing of all
                        employees and their families - in part, through the wellness screenings, incentives, and other
                        resources offered each year.</p>

                    <p>
                        For spouses in 2017, an incentive linked to being a non-tobacco user is available. No incentive for
                        the healthy measures readings is offered for spouses.
                    </p>

                    <p>
                        In order to earn the non-tobbaco user incentive, interested spouses must either receive a negative
                        result on a cotinine test or complete 6 tobacco E-learning lessons. Directions for either can be
                        found by clicking the link next to each option.
                    </p>

                <?php else : ?>

                    <p>Pekin Hospital is committed to encouraging and helping support the health, care, and
                        wellbeing of all employees and their families - in part, through the wellness screenings, incentives,
                        and other resources offered each year.</p>

                    <p>
                        In 2017, there are two incentive programs being offered. The first incentive is based on being a
                        non-tobacco user. Participants may earn this incentive by receiving a negative cotinine test result
                        or by completing 6 e-learning lessons on tobacco cessation. The second incentive is linked to overall
                        wellness and can be earned by scoring in the healthy range in 3 out of the 5 criteria. Those who do
                        not meet this standard may still earn the incentive by completing and submitting an alternate
                        qualification form or by completing the Pekin Wellness Coaching Program.
                    </p>

                    <p>These requirements, your results and incentive status are listed in the table below. Pekin Hospital
                        will receive monthly lists of those who qualify for 1 or both incentives. As always, individual
                        screening results are not shared with employers and remain confidential.</p>

                <?php endif ?>

            </div>

            <?php echo $this->getTable($status) ?>

            <div id="text-area-bottom">
                <div style="width: 56%; float: left">
                    <div>
                        Login to <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> any time to:
                        <ul>
                            <li>View all of your screening results and links in the report;  AND</li>
                            <li>Access powerful tools and resources for optimizing your health, care and wellbeing.</li>
                        </ul>

                        <div>
                            Thank you for getting your wellness screening done this year. This and many of your other
                            actions reflect how you value your own wellbeing and the wellbeing of others at home
                            and work.
                        </div>

                        Best Regards,<br />
                        Empower Health Services
                    </div>
                </div>

                <div style="width: 43%; float: right; background-color: #cceeff;">
                    <div style="font-weight: bold; text-align: center;">Some of these online tools include:</div>
                    <div>
                        <ul style="font-size: 7.5pt; margin-bottom: 0;">
                            <li>Over 1,000 e-lessons</li>
                            <li>
                                The Healthwise® Knowledgebase for decisions about medical tests, medicines, other
                                treatments, risks and other topics
                            </li>
                            <li>Over 500 videos</li>
                            <li>Decision tools for over 170 elective care decisions</li>
                            <li>Cholesterol, body metrics, blood sugars, women’s health, men’s health and over 40 other learning centers.</li>
                        </ul>
                    </div>
                </div>
            </div>


        </div>

        <?php
    }

    private function getTable($status)
    {
        $user = $status->getUser();

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $cotinineStatus = $status->getComplianceViewStatus('cotinine');
        $elearningStatus = $status->getComplianceViewStatus('elearning');

        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $optionsGroupStatus = $status->getComplianceViewGroupStatus('options');
        $qualificationStatus = $status->getComplianceViewStatus('qualification_form');
        $coachingStatus = $status->getComplianceViewStatus('coaching_program');

        ob_start();
        ?>
        <div style="text-align:center; margin-bottom: 2px;">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 360px;"><div class="alignleft">A. Tobacco Free Incentive</div><div class="alignright">-	for employees & spouses</div></th>
                        <th>Goal</th>
                        <th>Goal Met</th>
                    </tr>

                    <tr class="status-<?php echo $cotinineStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $cotinineStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $cotinineStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                            <?php if($cotinineStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes N (<?php echo $cotinineStatus->getAttribute('date') ?>)
                            <?php else : ?>
                                No P (<?php echo $cotinineStatus->getAttribute('date') ?>)
                            <?php endif ?>
                        </td>

                    </tr>

                    <tr class="status-<?php echo $elearningStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $elearningStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $elearningStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                        <?php if($elearningStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                            Yes <?php echo count($elearningStatus->getAttribute('lessons_completed')) ?> done <?php echo $elearningStatus->getComment() ?>
                        <?php else : ?>
                            No
                        <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $coreStatus->getStatus() == ComplianceStatus::COMPLIANT ? $coreStatus->getStatus() : 'not-compliant' ?>">
                        <td style="text-align: right;" colspan="2">
                            3.	Tobacco Incentive Status: Meets goals for A1 or A2 &rarr; <div>The sooner you get a Yes, the sooner you start saving $56.34/month in 2017</div>
                        </td>
                        <td><?php echo $coreStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                    </tr>
                </tbody>

                <tbody <?php echo $user->getClientId() == PekinHospital2017ComplianceProgram::PERKIN_HOSPITAL_SPOUSE_CLIENT_ID ? 'Class="hidden"' : '' ?>>
                    <tr class="headerRow">
                        <th style="text-align: left;"><div class="alignleft">B.  Healthy Measures Incentive</div><div class="alignright">- for employees</div></th>
                        <th>Goal</th>
                        <th>Goal Met</th>
                    </tr>

                    <?php foreach($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td>
                                <?php if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes - <?php echo $viewStatus->getComment() ?>
                                <?php else : ?>
                                    No - <?php echo $viewStatus->getComment() ?>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="status-<?php echo $healthyGroupStatus->getStatus() ?>">
                        <td style="text-align: right;" colspan="2">6. Get 3 or more of the above in the goal range; If <3, see B7 below  &rarr;</td>
                        <td>
                            <?php echo $healthyGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                        </td>
                    </tr>


                    <tr class="status-<?php echo $optionsGroupStatus->getStatus() ?>">
                        <td rowspan="2" style="text-align: left">
                            <div>7. If needed, complete one of these Options:</div>

                            <div style="margin-left: 20px; font-size: 9pt;">a. Turn-In a Completed Alternate Qualification Form (
                            <a href="/resources/8836/EHS Pekin Hospital IRC Alt To-Do Doctor Support Form 013017.pdf">
                            click for form & details</a>); OR</div>

                            <div style="margin-left: 20px; font-size: 9pt;">
                                <?php echo $coachingStatus->getComplianceView()->getReportName() ?>
                            </div>


                        </td>
                        <td><?php echo $qualificationStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                                <?php if($qualificationStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes <?php echo $qualificationStatus->getComment() ?>
                                <?php else : ?>
                                    No
                                <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $optionsGroupStatus->getStatus() ?>">
                        <td><?php echo $coachingStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                                <?php if($coachingStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes <?php echo $coachingStatus->getComment() ?>
                                <?php else : ?>
                                    No
                                <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $healthyGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT || $optionsGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 4 : 'not-compliant' ?>">
                        <td colspan="2" style="text-align: right">
                            8. Healthy Measures Incentive Status: Meets goals for B6 or B7  &rarr;
                        </td>
                        <td>
                            <?php
                                echo $healthyGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT || $optionsGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No'
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php

        return ob_get_clean();
    }
}