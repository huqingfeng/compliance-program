<?php


class Semblex2021ComplianceProgram extends ComplianceProgram
{
    public function getLocalActions()
    {
        return array(
            'dashboardCounts' => array($this, 'executeHealthRiskScore')
        );
    }

    public function executeHealthRiskScore(sfActions $actions)
    {
        $this->setActiveUser($actions->getSessionUser());

        ?>

        <style type="text/css">
            .bold {
                font-weight: bold;
            }

            .title {
                font-size: 13pt;
            }
        </style>

        <div>
            <p class="bold title" style="text-align: center">About the Health Risk Score</p>

            <p class="bold title">How is my health risk score calculated?</p>

            <p>
                Your health risk score focuses on 6 of your screening results.  Having results in the target range
                decreases risks and can help with many health and wellbeing goals.  The lower your health risk score,
                the more your results can help toward your goals.  The higher the score, the greater the risks are of
                getting serious conditions and conditions that can affect your quality of life and other goals.
            </p>

            <p>
                <ul>
                    <li><span class="bold">Blood Pressure:</span> 1 point is added per BP unit above 119/79 <span class="bold">(Systolic/Diastolic)</span>. A credit of -5 points can be earned for EACH of your results that are at or below the target range.</li>
                    <li><span class="bold">LDL Cholesterol:</span> 1 point is added per LDL unit above 99 mg/dl.  A credit of -5 points can be earned if you are at or below your LDL target. </li>
                    <li><span class="bold">Glucose:</span> 1 point is added per Glucose unit above 99 mg/dl. A credit of -5 points can be earned if Glucose is at or below 99 mg/dl.</li>
                    <li><span class="bold">Triglycerides:</span> 1 point is added per 10 Triglyceride units above 149 mg/dl. A credit of -5 points can be earned if Triglycerides are at or below 149 mg/dl.</li>
                    <li><span class="bold">Tobacco Use:</span> 40 points are added for using any tobacco product.</li>
                </ul>
            </p>

            <p class="bold title">How is my personal health risk score goal set?</p>

            <p>A score of -20 means all 6 results are in the target range – the ideal goal!</p>

            <p>
                You may be there already. If not, a reasonable goal is making jumps of progress toward the ideal goal.
                Which of the 4 goals below applies to you?  Whatever your score is now, how fast and far can you jump
                toward and reach the ideal goal?  What actions will help you get there?
            </p>

            <ol>
                <li>If your current score is –20 to zero, congratulations!  Keep doing the things that help you to stay in this range. Better yet, take the actions needed to get to and stay at the ideal score of -20.</li>
                <li>If your current score is 1 to 25, then strive to reach a score of 0 or less.</li>
                <li>If your current score is 26 to 40, then strive to achieve a score of 25 or less</li>
                <li>If your current score is greater than 40, then strive to achieve a score of 40 or less</li>
            </ol>

            <p class="bold title">Looking for resources that can help with many health and wellbeing decisions and goals?</p>

            <p>
                Empower Health offers a wide variety of helpful resources through this website.  A place to start is by
                clicking on the e-lesson links for each screening result above.  Or, go to the home page and click on Health,
                Care & Wellbeing Resources to explore even 1,000+ e-lessons, 500+ videos, medical decision-making tools and more.
            </p>

            <p class="bold title">Need more Assistance?</p>

            <p>Should you have additional questions, please contact Empower Health by calling 866.367.6974 or by emailing <a href="mailto:support@empowerhealthservices.com">support@empowerhealthservices.com</a></p>
        </div>



        <?php


    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Semblex2021ComplianceProgramReportPrinter();
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
//        $coreGroup->setPointsRequiredForCompliance(50);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('A.Complete Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('View Full Results', '/content/989'));
        $screeningView->setAttribute('deadline', '11/12/21');
        $coreGroup->addComplianceView($screeningView);


        $this->addComplianceViewGroup($coreGroup);


        $biometric = new ComplianceViewGroup('biometric', 'Biometric');
        $biometric->setPointsRequiredForCompliance(4);


        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1.	BMI');
        $bmiView->setName('bmi');
        $bmiView->overrideTestRowData(null, null, 25.999, null);
        $bmiView->setAttribute('goal', '< 26 or 3 less than 2020');
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->emptyLinks();
        $bmiView->addLink(new Link('Lessons', '/content/9420?action=lessonManager&tab_alias=body_fat'));
        $this->configureViewForElearningAlternativeAndImprovement($bmiView, 'body_fat', 'bmi', 'decrease', 3);
        $biometric->addComplianceView($bmiView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('2.	LDL Cholesterol');
        $ldlView->setName('ldl');
        $ldlView->overrideTestRowData(null, null, 99.999, null);
        $ldlView->setAttribute('goal', '<100 or 10 less than 2020');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->emptyLinks();
        $ldlView->addLink(new Link('Lessons', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $this->configureViewForElearningAlternativeAndImprovement($ldlView, 'cholesterol', 'ldl', 'decrease', 10);
        $biometric->addComplianceView($ldlView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('3. Triglycerides');
        $trigView->setName('triglycerides');
        $trigView->overrideTestRowData(null, null, 149.999, null);
        $trigView->setAttribute('goal', '<150 or 10 less than 2020');
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $trigView->emptyLinks();
        $trigView->addLink(new Link('Lessons', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $this->configureViewForElearningAlternativeAndImprovement($trigView, 'cholesterol', 'triglycerides', 'decrease', 10);
        $biometric->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('4.	Glucose');
        $glucoseView->setName('glucose');
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setAttribute('goal', '<100 or 10 less than 2020');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $glucoseView->emptyLinks();
        $glucoseView->addLink(new Link('Lessons', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $this->configureViewForElearningAlternativeAndImprovement($glucoseView, 'blood_sugars', 'glucose', 'decrease', 10);
        $biometric->addComplianceView($glucoseView);

        $systolicView = new ComplyWithSystolicBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $systolicView->setReportName('A. Blood pressure – Systolic');
        $systolicView->setName('systolic');
        $systolicView->overrideTestRowData(null, null, 129.999, null);
        $systolicView->setAttribute('goal', 'Systolic <130');
        $systolicView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $systolicView->emptyLinks();
        $systolicView->addLink(new Link('Lessons', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $this->configureViewForElearningAlternativeAndImprovement($systolicView, 'blood_sugars', 'systolic', 'null', 0);
        $biometric->addComplianceView($systolicView);

        $diastolicView = new ComplyWithDiastolicBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $diastolicView->setReportName('B. Blood Pressure – Diastolic');
        $diastolicView->setName('diastolic');
        $diastolicView->overrideTestRowData(null, null, 78.999, null);
        $diastolicView->setAttribute('goal', 'Diastolic <90');
        $diastolicView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $diastolicView->emptyLinks();
        $diastolicView->addLink(new Link('Lessons', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $this->configureViewForElearningAlternativeAndImprovement($diastolicView, 'blood_sugars', 'diastolic', 'null', 0);
        $biometric->addComplianceView($diastolicView);

        $tobaccoView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $tobaccoView->setReportName('6. Tobacco Free (Cotinine)');
        $tobaccoView->setName('tobacco');
        $tobaccoView->setAttribute('goal', '<15 or N or Negative');
        $tobaccoView->overrideTestRowData(null, null, 14.999, null);
        $tobaccoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoView->emptyLinks();
        $tobaccoView->addLink(new Link('Lessons', '/content/9420?action=lessonManager&tab_alias=tobacco'));
        $this->configureViewForElearningAlternativeAndImprovement($tobaccoView, 'tobacco', 'cotinine', 'null', 0);
        $biometric->addComplianceView($tobaccoView);

        $this->addComplianceViewGroup($biometric);

        $forceOverrideGroup = new ComplianceViewGroup('Force Override');

        // Used for override to force compliant
        $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $forceCompliant->setName('force_compliant');
        $forceCompliant->setReportName('AQF Override');
        $forceOverrideGroup->addComplianceView($forceCompliant);


        $this->addComplianceViewGroup($forceOverrideGroup);
    }

    private function configureViewForElearningAlternativeAndImprovement(ComplianceView $view, $alias, $test, $calculationMethod = 'decrease', $threshold)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($alias, $test, $calculationMethod, $threshold) {
            $view = $status->getComplianceView();
            $viewPoints = $status->getPoints();

            $programStart = new \DateTime('@'.$view->getStartDate());
            $programEnd = new \DateTime('@'.$view->getEndDate());

            $lastStart = new \DateTime('2020-01-01');
            $lastEnd = new \DateTime('2020-12-31');


            static $cache = null;

            if ($cache === null || $cache['user_id'] != $user->id) {
                $cache = array(
                    'user_id' => $user->id,
                    'this' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $programStart, $programEnd, array('merge'=> true)),
                    'last' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $lastStart, $lastEnd, array('merge'=> true))
                );
            }

            if (($cache['this'] || $cache['last']) && $calculationMethod != 'null') {
                $isImproved = false;

                if($test == 'bmi') {
                    if(isset($cache['last']['height']) && isset($cache['last']['weight']) && $cache['last']['height'] && $cache['last']['weight']) {
                        if($cache['last']['height'] !== null && $cache['last']['weight'] !== null && is_numeric($cache['last']['height']) && is_numeric($cache['last']['weight']) && $cache['last']['height'] > 0) {
                            $bmi = ($cache['last']['weight'] * 703) / ($cache['last']['height'] * $cache['last']['height']);
                        } else {
                            $bmi = null;
                        }

                        $lastVal = round($bmi, 2);
                    } else {
                        $lastVal = isset($cache['last'][$test]) ? (float) $cache['last'][$test] : null;
                    }

                    if(isset($cache['this']['height']) && isset($cache['this']['weight']) && $cache['this']['height'] && $cache['this']['weight']) {
                        if($cache['this']['height'] !== null && $cache['this']['weight'] !== null && is_numeric($cache['this']['height']) && is_numeric($cache['this']['weight']) && $cache['this']['height'] > 0) {
                            $bmi = ($cache['this']['weight'] * 703) / ($cache['this']['height'] * $cache['this']['height']);
                        } else {
                            $bmi = null;
                        }

                        $thisVal = round($bmi, 2);
                    } else {
                        $thisVal = isset($cache['this'][$test]) ? (float) $cache['this'][$test] : null;
                    }

                } else {
                    $lastVal = isset($cache['last'][$test]) ? $cache['last'][$test] : null;
                    $thisVal = isset($cache['this'][$test]) ? $cache['this'][$test] : null;
                }

                if ($thisVal && $lastVal && is_numeric($thisVal) && is_numeric($lastVal)) {
                    $change = $thisVal - $lastVal;
                    $status->setAttribute('2020_2021_change', round($change, 1));

                    if($calculationMethod == 'decrease') {
                        if(($change + $threshold) <= 0) {
                            $isImproved = true;
                        }
                    } else {
                        if(($change - $threshold) >= 0) {
                            $isImproved = true;
                        }
                    }

                }

                $status->setAttribute('2020_result', $lastVal);
                $status->setAttribute('2021_result', $thisVal);


                if ($isImproved) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }


            $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();

            if($viewPoints < $maxPoints && isset($cache['this'][$test]) && !empty($cache['this'][$test])) {
                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), $alias);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->useAlternateCode(true);

                $elearningStatus = $elearningView->getStatus($user);

                $numberCompleted = count($elearningStatus->getAttribute('lessons_completed', array()));

                $status->setAttribute('elearning_completed', $numberCompleted);

                if($numberCompleted >= 8) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }


            $noncompliantValues = array('QNS', 'TNP', "DECLINED");
            if (in_array(strtoupper($status->getAttribute('real_result')), $noncompliantValues)) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }

        });
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $biometricGroup = $status->getComplianceViewGroupStatus('biometric');

        foreach($biometricGroup->getComplianceViewStatuses() as $biometricStatus) {
            if($biometricStatus->getAttribute('elearning_completed') >= 8) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        }

        if($biometricGroup->getStatus() == ComplianceStatus::COMPLIANT || $status->getComplianceViewStatus('force_compliant')->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

    }
}

class Semblex2021ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }


        ?>
        <style type="text/css">
            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                height:11in;
                margin: 0 20px;
                position: relative;
            }

            .headerRow {
                background-color:#88b2f6;
                font-weight:bold;
                font-size:10pt;
                height:35px;
            }

            .bund {
                font-weight:bold;
                text-decoration:underline;
            }

            .light {
                width:0.3in;
            }

            #results {
                width:98%;
                margin:0 0.1in;
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

            #not_compliant_notes p{
                margin: 3px 0;
            }

            #ratingsTable tr{
                height: 35px;
            }

            .activity_name {
                padding-left: 10px;
            }

            .noBorder {
                border:none !important;
            }

            .right {
                text-align: right;
                padding-right: 10px;
            }

            .bold {
                font-weight: bold;
            }

            .underline{
                text-decoration: underline;
            }

            .color_details {
                margin-bottom: 10px;
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
                <div style="float: left; width: 46%;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4205 Westbrook Drive<br />
                    Aurora, IL 60504
                </div>



                <div style="float: right; width: 48%; text-align: right">
                    <img src="/images/empower/semblex_logo.png" style="height:50px;"  />
                </div>
            </p>

            <p style="clear: both">&nbsp;</p>

            <div style="margin-left:10px;">
                <p style="text-align: center; font-weight: bold; font-size: 14pt; color:green; margin-bottom: 30px;">
                    2021 Wellness Incentive Program
                </p>

            <p>
                <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
            </p>

                <p>
                   Semblex is committed to supporting the health and wellbeing of its employees and their families. A key
                   step in promoting good health is awareness. That is why we offer the annual wellness screening through
                   Empower Health Services (EHS).
                </p>

                <p>
                    This year we have a new approach to wellness rewards. After you get your wellness screening done, check the table below.
                </p>

                <p>
                    If you meet 4 or more biometric goals below, you’re done – congratulations!
                </p>

                <p>
                    If not, as an alternative:  Complete a minimum of 8 e-learning lessons by from the library of lessons
                    available by clicking on the "Lessons" links in the chart below.  OR, a second alternative option is
                    to submit a completed Alternate Qualification Form.  This form can be downloaded in section C of the
                    chart below and must be filled out and signed by your physician.  The deadline for either alternative
                    option is 11/30/2021.
                </p>
            </div>


            <?php echo $this->getTable($status) ?>

            <div id="not_compliant_notes">
                <div style="width: 56%; float: left">
                    <p>
                        Login to <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> any time to:
                        <ul>
                            <li>View all of your screening results and links in the report;  AND</li>
                            <li>Access powerful tools and resources for optimizing your health, care and well-being.</li>
                        </ul>

                        <p>
                            Thank you for getting your wellness screening done this year. This and many of your other
                            actions reflect how you value your own well-being and the well-being of others at home
                            and work.
                        </p><br />

                        Best Regards,<br />
                        Empower Health Services
                    </p>
                </div>

                <div style="width: 43%; float: right; background-color: #cceeff;">
                    <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Some of these online tools include:</div>
                    <div style="font-size: 9pt;">
                        <ul>
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


        <p style="clear: both;">&nbsp;</p>

        <?php
    }

    private function getTable($status)
    {
        $user = $status->getUser();
        $screeningStatus = $status->getComplianceViewStatus('screening');

        $bmiStatus = $status->getComplianceViewStatus('bmi');
        $ldlStatus = $status->getComplianceViewStatus('ldl');
        $triglyceridesStatus = $status->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $status->getComplianceViewStatus('glucose');
        $systolicStatus = $status->getComplianceViewStatus('systolic');
        $diastolicStatus = $status->getComplianceViewStatus('diastolic');

        $tobaccoStatus = $status->getComplianceViewStatus('tobacco');

        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th colspan="2" style="text-align: left; width: 430px;">A.	Get Started !  ...for the goals below</th>
                        <th>Deadline</th>
                        <th colspan="2">Date Done</th>
                        <th colspan="4" style="width: 160px;">Action Links</th>
                    </tr>

                    <tr class="status-<?php echo $screeningStatus->getStatus() ?>">
                        <td colspan="2" style="text-align: left;" class="activity_name"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $screeningStatus->getComplianceView()->getAttribute('deadline') ?></td>
                        <td colspan="2"><?php echo $screeningStatus->getComment() ?></td>
                        <td colspan="4" class="center">
                            <?php foreach($screeningStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>


                    <tr class="headerRow">
                        <th colspan="2" style="text-align: left; width: 260px;">B. Reward Criteria:  Meet 4 or more biometric goals -OR- if needed as an alternative:  complete 8 or more lessons for any goal not met -OR- use option C below. </th>
                        <th colspan="4">Deadline = 11/30/21</th>
                        <th colspan="2">e-Lesson folders if needed for a goal not met</th>
                        <th rowspan="2">Reward Criteria  Met</th>
                    </tr>

                    <tr class="headerRow">
                        <th>Some Key Biometrics</th>
                        <th style="width: 180px;">2021 Results Goals</th>
                        <th>Y1: 2020 Result</th>
                        <th>Y2: 2021 Result</th>
                        <th>Change Y2-Y1</th>
                        <th>Goal Met</th>
                        <th>Links<br/> To-Do</th>
                        <th># Done</th>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $bmiStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $bmiStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $bmiStatus->getAttribute('2020_result') ?></td>
                        <td><?php echo $bmiStatus->getAttribute('2021_result') ?></td>
                        <td><?php echo $bmiStatus->getAttribute('2020_2021_change') ?></td>
                        <td><?php echo $bmiStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td class="center">
                            <?php foreach($bmiStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                        <td><?php echo $bmiStatus->getAttribute('elearning_completed') ?></td>
                        <td rowspan="9" class="status-<?php echo $status->getStatus() ?>"><?php echo $status->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'Not Yet' ?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $ldlStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $ldlStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $ldlStatus->getAttribute('2020_result') ?></td>
                        <td><?php echo $ldlStatus->getAttribute('2021_result') ?></td>
                        <td><?php echo $ldlStatus->getAttribute('2020_2021_change') ?></td>
                        <td><?php echo $ldlStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td class="center">
                            <?php foreach($ldlStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                        <td><?php echo $ldlStatus->getAttribute('elearning_completed') ?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $triglyceridesStatus->getAttribute('2020_result') ?></td>
                        <td><?php echo $triglyceridesStatus->getAttribute('2021_result') ?></td>
                        <td><?php echo $triglyceridesStatus->getAttribute('2020_2021_change') ?></td>
                        <td><?php echo $triglyceridesStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td class="center">
                            <?php foreach($triglyceridesStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                        <td><?php echo $triglyceridesStatus->getAttribute('elearning_completed') ?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $glucoseStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $glucoseStatus->getAttribute('2020_result') ?></td>
                        <td><?php echo $glucoseStatus->getAttribute('2021_result') ?></td>
                        <td><?php echo $glucoseStatus->getAttribute('2020_2021_change') ?></td>
                        <td><?php echo $glucoseStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td class="center">
                            <?php foreach($glucoseStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                        <td><?php echo $glucoseStatus->getAttribute('elearning_completed') ?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" rowspan="2">5. Blood Pressure</td>
                        <td><?php echo $systolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td colspan="3"><?php echo $systolicStatus->getComment() ? $systolicStatus->getComment() : $systolicStatus->getAttribute('real_result') ?></td>
                        <td rowspan="2"><?php echo $systolicStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td class="center" rowspan="2">
                            <?php foreach($systolicStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                        <td rowspan="2"><?php echo $systolicStatus->getAttribute('elearning_completed') ?></td>
                    </tr>

                    <tr>
                        <td><?php echo $diastolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td colspan="3"><?php echo $diastolicStatus->getComment() ? $diastolicStatus->getComment() : $diastolicStatus->getAttribute('real_result') ?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $tobaccoStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $tobaccoStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td colspan="3"><?php echo $tobaccoStatus->getComment() ? $tobaccoStatus->getComment() : $tobaccoStatus->getAttribute('real_result') ?></td>
                        <td><?php echo $tobaccoStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td class="center">
                            <?php foreach($tobaccoStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                        <td><?php echo $tobaccoStatus->getAttribute('elearning_completed') ?></td>
                    </tr>

                    <tr class="headerRow">
                        <td colspan="8" style="text-align: left;">C. Doctor Option to help reach goals</td>
                    </tr>

                    <tr>
                        <td colspan="4" style="text-align: left;">
                            Confirm you are working with your doctor on these goals. Just complete the
                            <a href="/resources/10678/Semblex_2021_AQF_081921.pdf" target="_blank">Alternative Qualification Form</a>
                             and send it to EHS by the deadline any of these ways:
                            <ol style="">
                                <li>Upload completed form to <a href="ehsupload.com" target="_blank">ehsupload.com</a></li>
                                <li>Fax completed form to 630.385.0156 - Attn: AQF Department</li>
                                <li>Mail completed form to: EHSAQF Department - 4205 Westbrook Drive, Aurora, IL 60504</li>
                            </ol>
                        </td>
                        <td colspan="4" class="center bold">
                              Fax or mail completed form by 11/30/21 <br /> &#8594; <a href="/resources/10678/Semblex_2021_AQF_081921.pdf" download="Semblex_2021_AQF_081921.pdf">Download Form</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }

}