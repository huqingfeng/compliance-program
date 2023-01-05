<?php


class VikingGroup2020ComplianceProgram extends ComplianceProgram
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
        return new VikingGroup2020ComplianceProgramReportPrinter();
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
        $screeningView->setReportName('• Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('• Empower Health Risk Assessment (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HPA', '/content/989'));
        $hraView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($hraView);


        $physician = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $physician->setReportName('B. Have your results faxed to your physician');
        $physician->setName('physician');
        $coreGroup->addComplianceView($physician);


        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('• Confirmed with a negative continine; -or-');
        $cotinineView->setName('cotinine');
        $cotinineView->emptyLinks();
        $coreGroup->addComplianceView($cotinineView);


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

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('• LDL cholesterol');
        $ldlView->overrideTestRowData(0, 0, 99, 159);
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 10, 0, 0));
        $biometric->addComplianceView($ldlView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('• Triglycerides');
        $trigView->overrideTestRowData(0, 0, 149.999, 199.999);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 10, 0 ,0));
        $biometric->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('• Glucose');
        $glucoseView->overrideTestRowData(50, 70, 100, 125, 'M');
        $glucoseView->overrideTestRowData(40, 70, 100, 125, 'F');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 10, 0 ,0));
        $biometric->addComplianceView($glucoseView);


        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('• BMI / Body Mass Index');
        $bmiView->overrideTestRowData(0, 18.5, 27.9, 29.9);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 10, 0, 0));
        $biometric->addComplianceView($bmiView);


        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('• Cotinine (Nicotine)');
        $cotinineView->setName('nicotine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $biometric->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($biometric);


        $aqfGroup = new ComplianceViewGroup('aqf_group', 'Alternate Qualification Form');

        $formView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $formView->setReportName('4. Or, if needed, turn-In a Completed Alternate Qualification Form <a href="/resources/10575/2020_TRS_AQF_072120.pdf">Click here</a> to	download the form and details.');
        $formView->setName('alternate_qualification_form');
        $aqfGroup->addComplianceView($formView);

        $this->addComplianceViewGroup($aqfGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);


    }
}

class VikingGroup2020ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                <div style="float: left; width: 30%;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4205 Westbrook Drive<br />
                    Aurora, IL 60504
                </div>

                <div style="float: left; width: 30%;">
                    <img src="/images/empower/viking_logo.jpg" style="height:60px;"  />
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
                Viking Group, Inc. is committed to supporting the health and well-being of its employees and their
                families. A first step in promoting good health is awareness. That is why we offer the annual wellness
                screening and tie in a financial premium incentive.
            </p>

            <p>
                <span style="font-weight: bold;">For fall of 2020, earning an $800 incentive toward your 2021 medical premiums will be based on getting these things done: </span>
                <ul>
                    <li>Completing the wellness screening and Empower HRA (automatic $300 premium incentive!);</li>
                    <li>Being tobacco free (testing negative for tobacco use) -or- an option if not; and</li>
                    <li>Having your results faxed to your physician.</li>
                </ul>
            </p>

            <p>
                If you test positive for tobacco use, you may still earn the $500 incentive by faxing your results to
                your physician AND: A) By completing at least 16 tobacco related e-lessons (allow about 8 hours); or B)
                Showing that you are working with your own physician to quit tobacco use by submitting an Alternate
                Qualification Form (AQF) to Empower Health.  The deadline for submitting the AQF is <span style="color: red;">December 11, 2020</span>.
                You may complete the online tobacco cessation program at any time during the plan year. The premium
                incentive will be prorated depending on the timing of completion.  Points will not be required in 2020.
                Points will be a requirement for the 2021 program.
            </p>

            <p>
                <span style="font-weight: bold;">When Viking has the fall of 2021 program, you can earn:</span>
                <ul>
                    <li>$300 off of your annual medical premium by simply completing #1 above, and then</li>
                    <li>$500 more by earning at least 70 points <span style="font-weight: bold;">-AND-</span> having your results faxed to your physician.</li>
                </ul>
            </p>

            <p>
                This is a total of $800 off of your annual medical premium for 2021 (or $1600 if your spouse participates
                and completes the requirements). Each of the results listed below has a scoring range from a high of 20
                points (in normal range level), to 10 points (moderate risk level), and finally 0 points (high risk level).
                If you are unable to earn 70 points based on your screening results, you will be offered an alternate
                means of achieving the full incentive.
            </p>

            <p>
                The chart below shows what needs to get done this year AND it shows points earned this year &#8594; as an
                example for the point goal next year in 2021. As stated, for 2021, you will receive the $300 in annual
                premium incentive for participating and the additional $500 incentive if you met the criteria noted above.
            </p>

            <?php echo $this->getTable($status) ?>
        </div>


        <p style="clear: both;">&nbsp;</p>

        <div class="letter">
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

            <div style="clear: both"></div>


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
                                <td class="noBorder right bold">
                                    Points for each results:
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
                                <td class="noBorder right bold">
                                    LDL cholesterol
                                </td>
                                <td align="center" bgcolor="#ccffcc">
                                    ≤99
                                </td>
                                <td align="center" bgcolor="#ffff00">
                                    100-159
                                </td>
                                <td align="center" bgcolor="#ff909a">
                                    ≥160
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder right bold">
                                    Triglycerides
                                </td>
                                <td align="center" bgcolor="#ccffcc">
                                    &lt;150
                                </td>
                                <td align="center" bgcolor="#ffff00">
                                    150≤200
                                </td>
                                <td align="center" bgcolor="#ff909a">
                                    ≥200
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder right bold">
                                    Glucose(Men)
                                </td>
                                <td align="center" bgcolor="#ccffcc" style="border-bottom: none;">
                                    70≤100
                                </td>
                                <td align="center" bgcolor="#ffff00" style="border-bottom: none;">
                                    100-125 <br />
                                    50≤70
                                </td>
                                <td align="center" bgcolor="#ff909a" style="border-bottom: none;">
                                    ≥126 <br />
                                    <50
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder right bold">
                                    Glucose(Women)
                                </td>
                                <td align="center" bgcolor="#ccffcc" style="border-top: none;">
                                    70≤100
                                </td>
                                <td align="center" bgcolor="#ffff00" style="border-top: none;">
                                    100-125 <br />
                                    40≤70
                                </td>
                                <td align="center" bgcolor="#ff909a" style="border-top: none;">
                                    ≥126 <br />
                                    <40
                                </td>
                            </tr>

                            <tr>
                                <td class="noBorder right bold">
                                    Body Mass Index
                                </td>
                                <td align="center" valign="bottom" bgcolor="#ccffcc">
                                    18.5 ≤ 27.9
                                </td>
                                <td align="center" valign="bottom" bgcolor="#ffff00">
                                    28-29.9
                                </td>
                                <td align="center" valign="bottom" bgcolor="#ff909a">
                                    ≥30
                                </td>
                            </tr>

                            <tr>
                                <td class="noBorder right bold">
                                    Cotinine
                                </td>
                                <td align="center" bgcolor="#ccffcc">
                                    Negative
                                </td>
                                <td align="center" bgcolor="#ffff00">
                                    N/A
                                </td>
                                <td align="center" bgcolor="#ff909a">
                                    Positive
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>
                <div style="width: 39%; float: right">
                    <p>
                        Earn up to <span style="font-weight: bold;">20 points</span> for each of your results based on the risk ranges noted in this section.<br /><br />
                        Links: <br />
                        1. <a href="/content/989" target="_blank">All Results/Reports</a>
                    </p>
                    <p>
                        <span style="font-weight: bold;">Interpreting the ranges and colors:</span>

                        <ol class="color_details">
                            <li><span style="font-weight: bold; text-decoration: underline;">At-Risk</span> = Call or visit your doctor and share this result. Ask if a follow-up visit is recommended.</li>
                            <li><span style="font-weight: bold; text-decoration: underline;">Borderline</span> = Share and discuss this result on your next call or visit.</li>
                            <li><span style="font-weight: bold; text-decoration: underline;">OK/Good</span> = Share these results on your next visit.</li>
                            <li><span style="font-weight: bold;">REMINDER – YOU MUST HAVE YOUR RESULTS FAXED TO YOUR PHYSICIAN IN ORDER TO EARN THE FULL INCENTIVE.</span></li>
                            <li>See your report and related links for more information.</li>
                        </ol>

                        <p>
                            <span style="font-weight: bold;">Lessons for Review:</span>
                            <ol>
                                <li><a href="/content/9420?action=lessonManager&tab_alias=body_fat">Blood Fat Lessons</a></li>
                                <li><a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Blood Sugar Lessons</a></li>
                                <li><a href="/content/9420?action=lessonManager&tab_alias=biometrics">Body Metric's Lessons</a></li>
                            </ol>
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
        $physicianStatus = $status->getComplianceViewStatus('physician');
        $cotinineStatus = $status->getComplianceViewStatus('cotinine');
        $elearningStatus = $status->getComplianceViewStatus('elearning');
        $aqfStatus = $status->getComplianceViewStatus('aqf');

        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr><td colspan="4" style="text-align: left"><?php echo $user->first_name.' '.$user->last_name ?></td></tr>
                    <tr class="headerRow">
                        <th colspan="1" style="text-align: left; width: 430px;">2020 Goals - Get these done by December 8, 2020</th>
                        <th colspan="1">Date Done</th>
                        <th colspan="1">Goal Met</th>
                        <th colspan="1" style="width: 160px;">Action Links</th>
                    </tr>

                    <tr>
                        <td colspan="4" style="text-align: left; background-color:#fff2cd;">A.	Complete these screening components</td>
                    </tr>

                    <tr class="status-<?php echo $screeningStatus->getStatus() ?>">
                        <td colspan="1" style="text-align: left;" class="activity_name"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="1"><?php echo $screeningStatus->getComment() ?></td>
                        <td colspan="1"><?php echo $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
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

                    <tr>
                        <td colspan="2" style="text-align: left; background-color:#fff2cd;">B. Have Empower Health fax your results to your physician</td>
                        <td colspan="1"><?php echo $physicianStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td colspan="1" class="center">
                            EHS can do if desired
                        </td>
                    </tr>

                    <tr>
                        <td colspan="1" style="text-align: left; background-color:#fff2cd;">C.	Be Tobacco Free -or- be trying &#8594;  via result or options below:</td>
                        <td colspan="1" class="headerRow">Result</td>
                        <td colspan="1" class="center" rowspan="4">
                            <?php echo $cotinineStatus->getStatus() == ComplianceViewStatus::COMPLIANT || $elearningStatus->getStatus() == ComplianceViewStatus::COMPLIANT || $aqfStatus->getStatus() == ComplianceViewStatus::COMPLIANT? 'Yes' : 'No' ?>
                        </td>
                        <td colspan="1" class="center" rowspan="4">
                            <a href="/content/9420?action=lessonManager&tab_alias=tobacco">View / Do Lessons</a> <br /><br />
                            <a href="/resources/10588/2020_Viking_Group_AQF_091720.pdf" download="2020_Viking_Group_AQF">Download AQF form</a>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $cotinineStatus->getStatus() ?>">
                        <td colspan="1" style="text-align: left;" class="activity_name"><?php echo $cotinineStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="1"><?php echo $cotinineStatus->getComment() ?></td>
                    </tr>


                    <tr class="status-<?php echo $elearningStatus->getStatus() ?>">
                        <td colspan="1" style="text-align: left;" class="activity_name"><?php echo $elearningStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="1"><?php echo count($elearningStatus->getAttribute('lessons_completed')) ?></td>
                    </tr>

                    <tr class="status-<?php echo $aqfStatus->getStatus() ?>">
                        <td colspan="1" style="text-align: left;" class="activity_name"><?php echo $aqfStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="1"><?php echo $aqfStatus->getComment() ?></td>
                    </tr>

                    <tr class="headerRow">
                        <td colspan="4" style="text-align: left;">Points earned in 2020 - as an example for the point goal next year in 2021</td>
                    </tr>

                    <tr>
                        <td colspan="1" style="text-align: left; background-color:#fff2cd;">A.	See if you can earn 70 or more points from these screening results:</td>
                        <td colspan="1" class="headerRow">Points Possible</td>
                        <td colspan="1" class="headerRow" class="center" >Points Earned</td>
                        <td colspan="1" class="headerRow" class="center">Action Links for results, tips & insights</td>
                    </tr>

                    <?php $i = 0 ?>
                    <?php foreach($status->getComplianceViewGroupStatus('biometric')->getComplianceViewStatuses() as $viewStatus) : ?>
                          <tr>
                            <td class="status-<?php echo $viewStatus->getStatus() ?>" style="text-align: left;" colspan="1"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td class="status-<?php echo $viewStatus->getStatus() ?>" colspan="1"><?php echo $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="status-<?php echo $viewStatus->getStatus() ?>" colspan="1"><?php echo $viewStatus->getPoints() ?></td>
                            <?php if($i == 0) : ?>
                            <td colspan="1" rowspan="5" class="center">
                                <a href="/content/989">Results</a> <br /><br />
                                <a href="/content/9420?action=lessonManager&tab_alias=biometrics">Some key lessons</a>
                            </td>
                            <?php $i++; ?>
                            <?php endif ?>
                        </tr>
                    <?php endforeach ?>

                    <tr>
                        <td colspan="1" style="text-align: left; background-color:#fff2cd; height:30px;">B. Points earned and reward goal status as of <?php echo date('m/d/Y') ?></td>
                        <td colspan="1">100</td>
                        <td colspan="1" class="center">
                            <?php echo $status->getComplianceViewGroupStatus('biometric')->getPoints() ?>
                        </td>
                        <td colspan="1" class="center">
                              <?php echo $status->getComplianceViewGroupStatus('biometric')->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Reward Goal Met!' : 'Not Met Yet' ?>
                        </td>
                    </tr>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }

}