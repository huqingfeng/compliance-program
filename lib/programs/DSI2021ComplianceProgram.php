<?php


class DSI2021ComplianceProgram extends ComplianceProgram
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
        return new DSI2021ComplianceProgramReportPrinter();
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
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('B.	Complete Empower Risk Assessment');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HPA', '/content/989'));
        $hraView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($hraView);



        $this->addComplianceViewGroup($coreGroup);


        $biometric = new ComplianceViewGroup('biometric', 'Biometric');
        $biometric->setPointsRequiredForCompliance(70);


        $systolicView = new ComplyWithSystolicBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $systolicView->setReportName('A. Blood pressure – Systolic');
        $systolicView->setName('systolic');
        $systolicView->overrideTestRowData(null, null, 119, null);
        $systolicView->setAttribute('goal', '≤ 119');
        $systolicView->emptyLinks();
        $systolicView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $result = $status->getAttribute('real_result');
            $target = 119;

            if($result && is_numeric($result)) {
                $difference = $result - $target;
                $status->setAttribute('result_target', $difference);

                if($difference <= 0) {
                    $status->setAttribute('health_risk_points', -5);
                } else {
                    $status->setAttribute('health_risk_points', $difference);
                }
            }
        });
        $biometric->addComplianceView($systolicView);

        $diastolicView = new ComplyWithDiastolicBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $diastolicView->setReportName('B. Blood Pressure – Diastolic');
        $diastolicView->setName('diastolic');
        $diastolicView->overrideTestRowData(null, null, 79, null);
        $diastolicView->setAttribute('goal', '≤ 79');
        $diastolicView->emptyLinks();
        $diastolicView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $result = $status->getAttribute('real_result');
            $target = 79;

            if($result && is_numeric($result)) {
                $difference = $result - $target;
                $status->setAttribute('result_target', $difference);

                if($difference <= 0) {
                    $status->setAttribute('health_risk_points', -5);
                } else {
                    $status->setAttribute('health_risk_points', $difference);
                }
            }
        });
        $biometric->addComplianceView($diastolicView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('C. LDL Cholesterol');
        $ldlView->setName('ldl');
        $ldlView->overrideTestRowData(null, null, 99, null);
        $ldlView->setAttribute('goal', '≤ 99');
        $ldlView->emptyLinks();
        $ldlView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $result = $status->getAttribute('real_result');
            $target = 99;

            if($result && is_numeric($result)) {
                $difference = $result - $target;
                $status->setAttribute('result_target', $difference);

                if($difference <= 0) {
                    $status->setAttribute('health_risk_points', -5);
                } else {
                    $status->setAttribute('health_risk_points', $difference);
                }
            }
        });
        $biometric->addComplianceView($ldlView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('D. Triglycerides');
        $trigView->setName('triglycerides');
        $trigView->overrideTestRowData(null, null, 149, null);
        $trigView->setAttribute('goal', '≤ 149');
        $trigView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $result = $status->getAttribute('real_result');
            $target = 149;

            if($result && is_numeric($result)) {
                $difference = $result - $target;
                $status->setAttribute('result_target', $difference);

                if($difference <= 0) {
                    $status->setAttribute('health_risk_points', -5);
                } else {
                    $status->setAttribute('health_risk_points', round($difference/10));
                }
            }
        });
        $biometric->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('E.	Glucose');
        $glucoseView->setName('glucose');
        $glucoseView->overrideTestRowData(null, null, 99, null);
        $glucoseView->setAttribute('goal', '≤ 99');
        $glucoseView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $result = $status->getAttribute('real_result');
            $target = 99;

            if($result && is_numeric($result)) {
                $difference = $result - $target;
                $status->setAttribute('result_target', $difference);

                if($difference <= 0) {
                    $status->setAttribute('health_risk_points', -5);
                } else {
                    $status->setAttribute('health_risk_points', $difference);
                }
            }
        });
        $biometric->addComplianceView($glucoseView);


        $tobaccoView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $tobaccoView->setReportName('F. Tobacco Use - <span style="font-size:10pt; font-style: italic;">includes any type: cigarettes, cigars, pipe, chew & dip</span>');
        $tobaccoView->setName('tobacco');
        $tobaccoView->setAttribute('goal', 'N or Negative');
        $tobaccoView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $result = $status->getComment();

            if($result == 'P' || $result == 'Positive') {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            } else if($result == 'N' || $result == 'Negative') {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setAttribute('health_risk_points', 0);
            } else {
                $status->setAttribute('health_risk_points', 40);
            }

        });
        $biometric->addComplianceView($tobaccoView);

        $this->addComplianceViewGroup($biometric);

        $forceOverrideGroup = new ComplianceViewGroup('Force Override');

        // Used for override to force compliant
        $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $forceCompliant->setName('force_compliant');
        $forceCompliant->setReportName('Force Overall Compliant');
        $forceOverrideGroup->addComplianceView($forceCompliant);


        $this->addComplianceViewGroup($forceOverrideGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $biometricGroup = $status->getComplianceViewGroupStatus('biometric');

        $forceCompliantStatus = $status->getComplianceViewStatus('force_compliant');

        $healthRiskPoints = 0;
        foreach($biometricGroup->getComplianceViewStatuses() as $status) {
            if($status->getAttribute('health_risk_points')) {
                $healthRiskPoints += $status->getAttribute('health_risk_points');
            }
        }

        if($healthRiskPoints <= 0) {
            $biometricGroup->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $biometricGroup->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        $biometricGroup->setAttribute('health_risk_points', $healthRiskPoints);

        if($forceCompliantStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

    }
}

class DSI2021ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                    <img src="/images/dsi/DSI_logo.png" style="height:41px;"  />
                </div>
            </p>

            <p style="clear: both">&nbsp;</p>

            <div style="margin-left:10px;">
                <p style="text-align: center; font-weight: bold; font-size: 14pt; color:green;">
                    2021-2022 Wellness Incentive Program
                </p>

                <p>
                    Thank you for your participation in the Empower Health screening program.  Through this program, each
                    participant is provided with an overall health risk score and a personal health risk score goal.
                </p>

                <p>
                    To earn the full incentive for this year, participants must achieve an overall risk score of 0 or less or meet their personal goal of improving by one risk level from last year.
                </p>

                <p>
                    In order to earn the full incentive for next year, participants will need to achieve an overall health
                    risk score of 0 or less or meet their personal health risk score goal.
                </p>
                
                <p style="font-weight: bold;">
                    New hires will earn the full incentive simply by completing the screening program in their first year of eligibility. No action is required beyond completion of the screening.
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
                        </p>

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
        $hraStatus = $status->getComplianceViewStatus('hra');

        $systolicStatus = $status->getComplianceViewStatus('systolic');
        $diastolicStatus = $status->getComplianceViewStatus('diastolic');
        $ldlStatus = $status->getComplianceViewStatus('ldl');
        $triglyceridesStatus = $status->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $status->getComplianceViewStatus('glucose');
        $tobaccoStatus = $status->getComplianceViewStatus('tobacco');

        $forceOverride = $status->getComplianceViewStatus('force_compliant');

        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr><td colspan="6" style="text-align: left"><?php echo $user->first_name.' '.$user->last_name ?></td></tr>

                    <tr class="headerRow">
                        <th colspan="2" style="text-align: left; width: 430px;">1. Get Started – Get these done by 1/31/2022</th>
                        <th colspan="2">Date Done</th>
                        <th colspan="1">Status</th>
                        <th colspan="1" style="width: 160px;">Action Links</th>
                    </tr>

                    <tr class="status-<?php echo $screeningStatus->getStatus() ?>">
                        <td colspan="2" style="text-align: left;" class="activity_name"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="2"><?php echo $screeningStatus->getComment() ?></td>
                        <td colspan="1"><?php echo $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Done' : 'Not Done' ?></td>
                        <td colspan="1" class="center">
                            <?php foreach($screeningStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $hraStatus->getStatus() ?>">
                        <td colspan="2" style="text-align: left;" class="activity_name"><?php echo $hraStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="2"><?php echo $hraStatus->getComment() ?></td>
                        <td colspan="1"><?php echo $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Done' : 'Not Done' ?></td>
                        <td colspan="1" class="center">
                            <?php foreach($hraStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left; width: 260px;">2. Some key health measures to get in the target goal range</th>
                        <th>Target Goal</th>
                        <th>My Result</th>
                        <th style="width: 130px;">The Amount My Result is Above or Below Target</th>
                        <th style="width: 100px;">Health Risk Points & Credits (-)</th>
                        <th style="width: 160px;">Action Links</th>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $systolicStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $systolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $systolicStatus->getComment() ?></td>
                        <td><?php echo $systolicStatus->getAttribute('result_target') ?></td>
                        <td><?php echo $systolicStatus->getAttribute('health_risk_points') ?></td>
                        <td colspan="1" rowspan="2" class="center">
                            <a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Explore e-lessons</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $diastolicStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $diastolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $diastolicStatus->getComment() ?></td>
                        <td><?php echo $diastolicStatus->getAttribute('result_target') ?></td>
                        <td><?php echo $diastolicStatus->getAttribute('health_risk_points') ?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $ldlStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $ldlStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $ldlStatus->getComment() ?></td>
                        <td><?php echo $ldlStatus->getAttribute('result_target') ?></td>
                        <td><?php echo $ldlStatus->getAttribute('health_risk_points') ?></td>
                        <td colspan="1" rowspan="2" class="center">
                            <a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Explore e-lessons</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $triglyceridesStatus->getComment() ?></td>
                        <td><?php echo $triglyceridesStatus->getAttribute('result_target') ?></td>
                        <td><?php echo $triglyceridesStatus->getAttribute('health_risk_points') ?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $glucoseStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $glucoseStatus->getComment() ?></td>
                        <td><?php echo $glucoseStatus->getAttribute('result_target') ?></td>
                        <td><?php echo $glucoseStatus->getAttribute('health_risk_points') ?></td>
                        <td colspan="1" class="center">
                            <a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Explore e-lessons</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="1"><?php echo $tobaccoStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $tobaccoStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td colspan="2"><?php echo $tobaccoStatus->getComment() ?></td>
                        <td><?php echo $tobaccoStatus->getAttribute('health_risk_points') ?></td>
                        <td colspan="1" class="center">
                            <a href="/content/9420?action=lessonManager&tab_alias=tobacco">Explore e-lessons</a>
                        </td>
                    </tr>

                    <tr class="headerRow">
                        <td colspan="4" style="text-align: left;">3.	Goals and My Total Health Risk Score</td>
                        <td>Health Risk Score</td>
                        <td>Is score > 0, personal goal met, or AQF received?</td>
                    </tr>



                    <tr>
                        <td colspan="4" style="text-align: left;">
                            <ol style="list-style-type: upper-alpha;">
                                <li>If your score is 0 or less &#8594; <span class="bold" style="color: green">Congratulations!  The lower the better!</span></li>
                                <li>If your score is 1-25 &#8594; Your goal is to reach 0 or less for next screening</li>
                                <li>If your score is 26-40 &#8594; Your goal is to reach 25 or less for next screening</li>
                                <li>If your score is 41 or more &#8594; Your goal is to reach 40 or less for next screening</li>
                            </ol>
                        </td>
                        <td colspan="1"><?php echo $status->getComplianceViewGroupStatus('biometric')->getAttribute('health_risk_points') ?></td>
                        <td colspan="1" class="center bold">
                              <?php
                              if($status->getComplianceViewGroupStatus('biometric')->getStatus() == ComplianceViewStatus::COMPLIANT) {
                                echo 'Yes, Congrats!';
                              } elseif($forceOverride->getStatus() == ComplianceStatus::COMPLIANT) {
                                echo 'AQF Received';
                              } else {
                                echo 'Not yet!  See 3E';
                              }
                              ?>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="6">
                            <p style="margin:10px; width:95%;">
                                E. If you did not earn the full incentive based on your most current screening results, you
                                 may still do so by having your physician complete and submit the Alternate Qualification Form by 2/18/2022. You can
                                 <a href="/pdf/clients/dsi/2021_DSI_AQF.pdf" download="2021_DSI_AQF">download</a>
                                 & the form here and submit it back to Empower Health via:
                                 <ul style="margin-left:100px; margin-top:-10px;text-align: left;">
                                    <li>Fax completed form to 630.385.0156 - Attn: Reports Department</li>
                                    <li>Mail completed form to: EHS Reports Department - 4205 Westbrook Drive, Aurora, IL 60504</li>
                                 </ul>
                            </p>

                            <p>
                                See action links to learn more about each & ways to reach & stay in the ideal range.
                                <a href="/compliance_programs/localAction?id=1625&local_action=dashboardCounts">About the health risk score.</a>
                            </p>
                        </td>
                    </tr>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }

}
