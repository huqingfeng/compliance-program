<?php


class VillageOfSchaumburg2020ComplianceProgram extends ComplianceProgram
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
        return new VillageOfSchaumburg2020ComplianceProgramReportPrinter();
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
        $screeningView->setReportName('A. Complete Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('B. Complete Health Power Assessment HRA');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HPA', '/content/989'));
        $hraView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($hraView);


        $elearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $elearningView->setReportName('• Complete 16 tobacco-related e-lessons; -or-');
        $elearningView->setName('elearning');
        $elearningView->useAlternateCode(true);
        $elearningView->setNumberRequired(16);
        $elearningView->emptyLinks();
        $coreGroup->addComplianceView($elearningView);

        $AQF = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $AQF->setReportName('• Verify you are working with your doctor on your tobacco goals by downloading and submitting the completed AQF form by December 11, 2020');
        $AQF->setName('aqf');
        $coreGroup->addComplianceView($AQF);


        $this->addComplianceViewGroup($coreGroup);


        $biometric = new ComplianceViewGroup('biometric', 'Biometric');
        $biometric->setPointsRequiredForCompliance(70);

        $tobaccoView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $tobaccoView->setReportName('• Tobacco Use');
        $tobaccoView->setName('tobacco');
        $tobaccoView->emptyLinks();
        $tobaccoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $tobaccoView->setAttribute('goal', 'Non-User');
        $biometric->addComplianceView($tobaccoView);


        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('• LDL cholesterol');
        $ldlView->overrideTestRowData(0, 0, 99.99, 129);
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 10, 0, 0));
        $biometric->addComplianceView($ldlView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('• Triglycerides');
        $trigView->overrideTestRowData(0, 0, 149.99, 199.999);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 10, 0 ,0));
        $biometric->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('• Glucose');
        $glucoseView->overrideTestRowData(0, 0, 99.99, 125);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 10, 0 ,0));
        $biometric->addComplianceView($glucoseView);


        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('• BMI / Body Mass Index');
        $bmiView->overrideTestRowData(null, 18.5, 28, 30);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 10, 0, 0));
        $biometric->addComplianceView($bmiView);



        $this->addComplianceViewGroup($biometric);


        $overrideGroup = new ComplianceViewGroup('override', 'Coaching Override');

        $coachingOverride = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $coachingOverride->setReportName('Coaching Override');
        $coachingOverride->setName('coaching_override');
        $overrideGroup->addComplianceView($coachingOverride);

        $this->addComplianceViewGroup($overrideGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if($status->getComplianceViewGroupStatus('biometric')->getPoints() >= 80) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } elseif($status->getComplianceViewGroupStatus('biometric')->getPoints() >= 60) {
            $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        if($status->getComplianceViewStatus('coaching_override')->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

    }
}

class VillageOfSchaumburg2020ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();
        $screeningStatus = $status->getComplianceViewStatus('screening');

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }



        ?>
        <style type="text/css">
            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                height:11.5in;
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

            <?php if($screeningStatus->getStatus() == ComplianceStatus::COMPLIANT) : ?>
            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                background-color:#ffb3b3;
            }
            <?php else : ?>
            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                background-color:#DEDEDE;
            }
            <?php endif ?>


            #results .rewards.status-<?php echo ComplianceStatus::COMPLIANT ?> {
                background-color:#90FF8C;
            }

            #results .rewards.status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?> {
                background-color:#ffffff;
            }

            #results .rewards.status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                background-color:#ffffff;
            }

            #not_compliant_notes p{
                margin: 3px 0;
            }

            #ratingsTable tr{
                height: 35px;
            }

            .activity_name {
                padding-left: 20px;
            }

            .noBorder {
                border:none !important;
            }

            .left {
                text-align: left;
                padding-left: 30px;
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
                <div style="float: left; width: 30%;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4205 Westbrook Drive<br />
                    Aurora, IL 60504
                </div>

                <div style="float: left; width: 30%;">
                    <img src="/images/empower/schaumurg_village_logo.png" style="height:60px;"  />
                </div>

                <div style="float: right; width: 30%;">
                    <?php echo $user->getFullName() ?> <br/>
                    <?php echo $user->getFullAddress("<br/>") ?>
                </div>
            </p>

            <p style="clear: both">&nbsp;</p>

            <p>
                <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
            </p>


            <p>
                The Village of Schaumburg is committed to supporting the health and wellbeing of its employees and their
                families. A first step in promoting good health is awareness.  That is why we offer the annual wellness
                screening through Empower Health and tie a financial incentive to it.
            </p>

            <p>
                The incentive amount is based on the amount of points earned from your screening results.  Each of the
                results listed below has a scoring range of 20 points (in range values), 10 points (moderate risk level,
                or ) 0 points (high risk level).  In order to receive the maximum incentive, you must earn 80 points or
                more.  If you earn between 60-79 points, you will receive 50% of the incentive.  If you earn less than
                60 points, no incentive will be given.
            </p>

            <p>
                If you did not earn the full incentive based on your screening results, you may still do so through the
                alternate qualification process.  The alternate qualification process is to complete a certain number
                of individual health coaching sessions through Empower Health.  If you earned 60-79 points, you can
                earn the maximum incentive by completing 3 coaching sessions.  If you earned less than 60 points, you
                can earn the maximum incentive by completing 6 coaching sessions.
            </p>

            <p>
                No more than 1 coaching session may be completed each week and the deadline for completing all coaching
                sessions is March 31, 2021.
            </p>

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

                        Best Regards,<br /><br />
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

        <div class="letter">
            <a name="second_page"></a>

            <div style="background-color: #cdffcc; width:100%; height: 200px; display: block; margin-top: 20px;">
                <div style="width: 20%; margin: 10px 20px; float: left;">
                    <div style="margin: 20px 20px; width: 90%;">
                        Strive to get and keep your key biometric measures in the green zone for good health.
                    </div>
                    <div style="margin: 20px 20px; width: 80%; text-align: right">
                        Here’s why &#8594;
                    </div>
                </div>

                <div style="margin: 20px; width: 70%; float: right; ">
                    <p>Below are some of these key measures that are strongly connected with your powers to prevent and avoid one or more of the following:</p>

                    <ol>
                        <li>Clogged arteries, heart attacks and strokes;</li>
                        <li>Diabetes, loss of vision, amputations & other complications;</li>
                        <li>Certain cancers;</li>
                        <li>Back pain, hip and knee replacements;</li>
                        <li>Loss of mobility and quality of life at a young age; and</li>
                        <li>Loss of life at a young age.</li>
                    </ol>
                </div>
            </div>

            <div>
                <div style="margin: 30px 10px; width: 55%; float: left">
                    <table border="0" style="margin-left: 10px;" id="ratingsTable">
                        <tbody>
                            <tr>
                                <td class="noBorder right bold">
                                    Risk ratings &amp; colors:
                                </td>
                                <td align="center"  class="noBorder" style="width: 80px;">
                                    <strong><font color="#006600">OK/Good</font></strong></td>
                                <td align="center" class="noBorder" style="width: 80px;">
                                    <strong><font color="#ff9933">Borderline</font></strong></td>
                                <td align="center" class="noBorder" style="width: 80px;">
                                    <strong><font color="#ff0000">At-Risk</font></strong></td>
                            </tr>
                            <tr>
                                <td class="noBorder right" style="font-size:10pt;">
                                    Points for each results: <br />that falls in these columns &#8594;
                                </td>
                                <td align="center" bgcolor="#ccffcc" class="grayArrow">
                                    20 points
                                </td>
                                <td align="center" bgcolor="#ffff00" class="grayArrow">
                                    10 points
                                </td>
                                <td align="center" bgcolor="#ff909a" class="grayArrow">
                                    0 points
                                </td>
                            </tr>
                            <tr height="36px">
                                <td height="36" class="noBorder right bold underline">
                                    Key measures and ranges
                                </td>
                                <td align="center" bgcolor="#ccffcc" class="grayArrow">
                                </td>
                                <td align="center" bgcolor="#ffff00" class="grayArrow">
                                </td>
                                <td align="center" bgcolor="#ff909a" class="grayArrow">
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder left bold">
                                    1. Tobacco Use
                                </td>
                                <td align="center" bgcolor="#ccffcc">
                                    Non-User
                                </td>
                                <td align="center" bgcolor="#ffff00">

                                </td>
                                <td align="center" bgcolor="#ff909a">
                                    User
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder left bold">
                                    2. LDL cholesterol
                                </td>
                                <td align="center" bgcolor="#ccffcc">
                                    <100
                                </td>
                                <td align="center" bgcolor="#ffff00">
                                    100 - 129
                                </td>
                                <td align="center" bgcolor="#ff909a">
                                    >129
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder left bold">
                                    3. Triglycerides
                                </td>
                                <td align="center" bgcolor="#ccffcc">
                                    &lt;150
                                </td>
                                <td align="center" bgcolor="#ffff00">
                                    150 - <200
                                </td>
                                <td align="center" bgcolor="#ff909a">
                                    ≥200
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder left bold">
                                    4. Glucose
                                </td>
                                <td align="center" bgcolor="#ccffcc" style="border-bottom: none;">
                                    <100
                                </td>
                                <td align="center" bgcolor="#ffff00" style="border-bottom: none;">
                                    100 - 125
                                </td>
                                <td align="center" bgcolor="#ff909a" style="border-bottom: none;">
                                    >125
                                </td>
                            </tr>


                            <tr>
                                <td class="noBorder left bold">
                                    5. BMI
                                </td>
                                <td align="center" valign="bottom" bgcolor="#ccffcc">
                                    18.5-28
                                </td>
                                <td align="center" valign="bottom" bgcolor="#ffff00">
                                    28.1-30
                                </td>
                                <td align="center" valign="bottom" bgcolor="#ff909a">
                                    >30
                                </td>
                            </tr>


                        </tbody>
                    </table>

                </div>
                <div style="width: 39%; float: right">
                    <p>
                        Earn up to <span style="font-weight: bold;">20 points</span> for each of your results based on the risk ranges noted in this section.<br /><br />
                        Links: <br />
                        <ul>
                            <li><a href="/content/989" target="_blank">All Results/Reports</a></li>
                        </ul>

                    </p>
                    <p>
                        <span style="font-weight: bold;">Interpreting the ranges and colors:</span>

                        <ul class="color_details">
                            <li><span style="font-weight: bold; text-decoration: underline;">At-Risk</span> = Call or visit your doctor and share this result. Ask if a follow-up visit is recommended.</li>
                            <li><span style="font-weight: bold; text-decoration: underline;">Borderline</span> = Share and discuss this result on your next call or visit.</li>
                            <li><span style="font-weight: bold; text-decoration: underline;">OK/Good</span> = Share these results on your next visit.</li>
                            <li>See your report and related links for more information.</li>
                        </ul>

                        <p>
                            <span style="font-weight: bold;">Lessons for Review:</span>
                            <ul>
                                <li><a href="/content/9420?action=lessonManager&tab_alias=body_fat">Blood Fat Lessons</a></li>
                                <li><a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Blood Sugar Lessons</a></li>
                                <li><a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Blood Pressure Lessons</a></li>
                                <li><a href="/content/9420?action=lessonManager&tab_alias=biometrics">Body Metric's Lessons</a></li>
                            </ul>
                        </p>
                    </p>

                </div>
            </div>


        </div>
        <?php
    }

    private function getTable($status)
    {
        $user = $status->getUser();
        $screeningStatus = $status->getComplianceViewStatus('screening');
        $hraStatus = $status->getComplianceViewStatus('hra');
        $aqfStatus = $status->getComplianceViewStatus('aqf');

        $totalPoints  = $status->getComplianceViewGroupStatus('biometric')->getPoints();

        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr><td colspan="4" style="text-align: left"><?php echo $user->first_name.' '.$user->last_name ?></td></tr>
                    <tr class="headerRow">
                        <th colspan="1" style="text-align: left; width: 430px;">1. Get Started - Get these done by January 30, 2021</th>
                        <th colspan="1">Date Done</th>
                        <th colspan="1">Status</th>
                        <th colspan="1" style="width: 160px;">Action Links</th>
                    </tr>

                    <tr class="status-<?php echo $screeningStatus->getStatus() ?>">
                        <td colspan="1" style="text-align: left;" class="activity_name"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="1"><?php echo $screeningStatus->getComment() ?></td>
                        <td colspan="1"><?php echo $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Done' : 'Not Done' ?></td>
                        <td colspan="1" class="center">
                            <?php foreach($screeningStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $hraStatus->getStatus() ?>">
                        <td colspan="1" style="text-align: left;" class="activity_name"><?php echo $hraStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="1"><?php echo $hraStatus->getComment() ?></td>
                        <td colspan="1"><?php echo $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td colspan="1" class="center">
                            <?php foreach($hraStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr class="headerRow">
                        <th colspan="1" style="text-align: left; width: 430px;">2. Earn 80 or more points from A&B below by January 30, 2021</th>
                        <th colspan="1"># Points Possible</th>
                        <th colspan="1"># Points Earned</th>
                        <th colspan="1" style="width: 160px;">Action Links</th>
                    </tr>

                    <tr>
                        <td colspan="1" style="text-align: left; background-color:#fff2cd;">A. Have these screening results in the ideal zone:</td>
                        <td colspan="3">Note:  Your results from 1A (above) link to 2A (here)	</td>
                    </tr>

                    <?php $i = 0 ?>
                    <?php foreach($status->getComplianceViewGroupStatus('biometric')->getComplianceViewStatuses() as $viewStatus) : ?>
                          <tr>
                            <td class="status-<?php echo $viewStatus->getStatus() ?>" style="padding-left:20px; text-align: left;" colspan="1"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td class="status-<?php echo $viewStatus->getStatus() ?>" colspan="1"><?php echo $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="status-<?php echo $viewStatus->getStatus() ?>" colspan="1"><?php echo $viewStatus->getPoints() ?></td>
                            <?php if($i == 0) : ?>
                            <td colspan="1" rowspan="6" class="center" style="width: 200px;">
                                <a href="#second_page">Click /scroll</a> to see how your results can earn points<br /> –and–<br />
                                Resources that may be helpful for the best results.
                            </td>
                            <?php $i++; ?>
                            <?php endif ?>
                        </tr>
                    <?php endforeach ?>

                    <tr>
                        <td colspan="1" style="text-align: left; background-color:#fff2cd;">B. Total points possible and earned as of <?php echo date('m/d/Y') ?>:</td>
                        <td colspan="1">100</td>
                        <td colspan="1" class="center" ><?php echo $totalPoints ?></td>
                    </tr>

                    <tr class="headerRow">
                        <th colspan="2" style="text-align: left; width: 430px;">3. Reward Goal Status & Coaching Options to Earn Full Incentive</th>
                        <th colspan="1"></th>
                        <th colspan="1">Reward Status</th>
                    </tr>

                    <tr class="rewards status-<?php echo $totalPoints >= 80 ? '4' : '1' ?>">
                        <td colspan="2" style="text-align: left;"><span>A.</span> Total Points earned</td>
                        <td colspan="1"><?php echo $totalPoints ?></td>
                        <td rowspan="2" class="center" >
                            <?php if($status->getStatus() == ComplianceStatus::COMPLIANT) :  ?>
                            Full Incentive
                           <?php elseif($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) :  ?>
                           Partial Incentive Earned
                           <?php else : ?>
                           No Incentive
                           <?php endif ?>
                        </td>
                    </tr>



                    <tr class="rewards status-<?php echo $status->getStatus() ?>">
                        <td colspan="2" style="text-align: left; ">
                            <div style="display: block; width: 4%; float:left;">B. </div>
                            <div style="display: block; width: 96%; float:right;">
                                <p>
                                    If you did not earn the full incentive through your points, you may earn the full
                                    incentive by completing the required number of coaching sessions by the deadlines as noted below.
                                </p>

                                <ul>
                                    <li>If you earned 60-79 points, then complete 3 coaching sessions.</li>
                                    <li>If you earned less than 60 points, then complete 6 coaching sessions.  </li>
                                </ul>

                                <p>If needed, the deadline to complete coaching sessions is:</p>

                                <ul>
                                    <li>If hired before 1/1/2021, complete coaching sessions by March 31, 2021; or</li>
                                    <li>If hired on or after 1/1/2021, complete coaching sessions within 90 days of when your wellness screening was done.</li>
                                </ul>
                            </div>


                        </td>

                        <td colspan="1">Sign up for coaching by calling 800.882.2109</td>
                    </tr>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }

}