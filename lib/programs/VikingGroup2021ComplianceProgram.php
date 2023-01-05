<?php

class VikingGroup2021ComplianceProgram extends ComplianceProgram
{
    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $coachingSessionsStatus = $status->getComplianceViewStatus('coaching_sessions');

        foreach($pointGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $noncompliantValues = ['QNS', 'TNP', "DECLINED"];

            if (in_array(strtoupper($viewStatus->getAttribute('real_result')), $noncompliantValues)) {
                $viewStatus->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
            }
        }


        if ($pointGroupStatus->getPoints() >= 80 || $coachingSessionsStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } elseif($pointGroupStatus->getPoints() > 0) {
            $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Get Started');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Wellness Screening');
        $screeningView->setName('complete_screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete Health Power Assessment HRA');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the point group
        $pointGroup = new ComplianceViewGroup('points', 'Earn 80 or more points from A&B below');

        $screeningTestMapper = new ComplianceStatusPointMapper(20, 10, 0, 0);

        $totalCholesterolView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $pointGroup->addComplianceView($totalCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->overrideTestRowData(null, null, 99.999, 159.999);
        $pointGroup->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->overrideTestRowData(null, null, 149.999, 199.999);
        $pointGroup->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(50, 70, 99.999, 125.999, "M");
        $glucoseView->overrideTestRowData(40, 70, 99.999, 125.999, "F");
        $pointGroup->addComplianceView($glucoseView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setComplianceStatusPointMapper($screeningTestMapper);
        $bmiView->setReportName('BMI / Body Mass Index');
        $bmiView->overrideTestRowData(18.5, 18.5, 27.9, 29.999);
        $pointGroup->addComplianceView($bmiView);

        $pointGroup->setPointsRequiredForCompliance(80);
        $this->addComplianceViewGroup($pointGroup);


        $forceOverrideGroup = new ComplianceViewGroup('Force Override');

        // Used for override to force compliant
        $coachingCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $coachingCompliant->setName('coaching_sessions');
        $coachingCompliant->setReportName('Coaching Sessions');
        $forceOverrideGroup->addComplianceView($coachingCompliant);

        $this->addComplianceViewGroup($forceOverrideGroup);


    }


    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new VikingGroup2021ComplianceProgramReportPrinter();

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, true);

        return $printer;
    }

    protected $evaluateOverall = true;
}

class VikingGroup2021ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');

        $tobaccoStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_cotinine_screening_test');
        $ldlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_ldl_screening_test');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_triglycerides_screening_test');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_glucose_screening_test');
        $bmiStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_bmi_screening_test');

        ?>
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
        <script type="text/javascript">
            $(function() {
                $('#other_benefits_details').hide();

                $('#other_benefits').toggle(function() {
                    $('#other_benefits_details').show();
                }, function(){
                    $('#other_benefits_details').hide();
                });

                $('#criteria_text').hide();
                $('#criteria').toggle(function() {
                    $('#criteria_text').show();
                }, function() {
                    $('#criteria_text').hide();
                });
            });
        </script>

        <style type="text/css">
            .phipTable ul, .phipTable li {
                margin-top:0px;
                margin-bottom:0px;
                padding-top:0px;
                padding-bottom:0px;
            }

            .pageHeading {
                font-weight:bold;
                text-align:center;
                margin-bottom:20px;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .phipTable .headerRow {
                background-color:#01b0f1;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            .phipTable .headerRow th {
                text-align:left;
                font-weight:normal;
            }

            .phipTable .headerRow td {
                text-align:center;
            }

            .phipTable .links {
                text-align:center;
            }

            .center {
                text-align:center;
            }

            .white {
                background-color:#FFFFFF;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .right {
                text-align:right;
            }

            #legend, #legend tr, #legend td {
                padding:0px;
                margin:0px;
            }

            #legend td {

                padding-bottom:5px;
            }

            #legendText {
                text-align:center;
                background-color:#01b0f1;
                font-weight:normal;
                color:#FFFFFF;
                font-size:12pt;
                margin-bottom:5px;
            }

            .legendEntry {
                width:130px;
                float:left;
                text-align:center;
                padding-left:2px;
            }

            .address {
                margin: auto;
                width: 250px;
                clear: both;
            }
        </style>

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

        <p>Dear <?php echo $user ?>, <span style="text-align: right; float: right;"><?php echo date("m/d/Y") ?></span></p>
        <p></p>
        <p>
            Viking Group is committed to supporting the health and wellbeing of its employees and their families. A first
            step in promoting good health is awareness. That is why we offer the annual wellness screening through Empower
            Health and tie a financial incentive to it.
        </p>

        <p>
            This program can earn you $300 off of your annual medical premium for simply participating, and then an
            additional $500 for meeting your point goal (or alternative qualification process).  This is a total of $800
            off of your annual medical premium for 2022 (or $1600 if your spouse participates and completes the requirements).
        </p>

        <p>
            Each of the results listed below has a scoring range of 20 points (OK/Good), 10 points (Borderline risk), or 0
            points (At-Risk, high levels).
        </p>

        <p>
            In order to receive the maximum incentive, you must earn 80 points or more.  If you earn less than 80 points,
            only $300 of the incentive will be given.
        </p>

        <p>
            The alternate qualification process is to complete a certain number of individual health coaching sessions
            through Empower Health. If you earned 60-79 points, you can earn the maximum incentive by completing 3
            coaching sessions. If you earned less than 60 points, you can earn the maximum incentive by completing 6
            coaching sessions.
        </p>

        <p>
            No more than 1 coaching session may be completed each week and the deadline for completing all coaching
            sessions is January 31, 2022.  Health coaching will be available year-round to every program participant,
            but only until January 31, 2022 for incentive purposes.
        </p>

        <p>
          <b>
            If you are a new hire, you will have 10 weeks from your
            screening date to complete coaching sessions, if needed.
          </b>
        </p>

        <table class="phipTable" border="1">
            <thead>
            <tr>
                <td colspan="6">
                    <span class="legendEntry" style="font-weight: bold;"><?php echo $status->getUser() ?></span>
                </td>
            </tr>
            </thead>
            <tbody>
            <tr class="headerRow">
                <th style="width:380px;">1. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td>Date Done</td>
                <td colspan="2" >Status</td>
                <td style="width:180px;">Action Links</td>
            </tr>

            <tr>
                <td>A. <?php echo $completeScreeningStatus->getComplianceView()
                        ->getReportName() ?></td>
                <td class="center">
                    <?php echo $completeScreeningStatus->getComment(); ?>
                </td>
                <td colspan="2" class="center">
                    <?php echo $completeScreeningStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Done' : '' ?>
                </td>
                <td class="links">
                    <?php
                    foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="center">
                    <?php echo $completeHRAStatus->getComment(); ?>
                </td>
                <td colspan="2" class="center">
                    <?php echo $completeHRAStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Done' : '' ?>
                </td>
                <td class="links">
                    <?php
                    foreach($completeHRAStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr class="headerRow">
                <th>2. <?php echo $pointGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td ># Points Possible</td>
                <td>My Result</td>
                <td style="width:80px;">My Points</td>
                <td>Links</td>
            </tr>

            <tr>
                <td>A. Have these screening results in the ideal zone:</td>
                <td colspan="4">
                    Note: Your results from 1A (above) link to 2A (here)
                </td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $tobaccoStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $tobaccoStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="center"><?php echo $tobaccoStatus->getComment(); ?></td>
                <td class="center"><?php echo $tobaccoStatus->getPoints(); ?></td>
                <td rowspan="6" class="links">
                    Click /scroll to see how your results
                    can earn points</br>
                    –and–</br>
                    Resources that may be helpful for
                    the best results.
                </td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $ldlStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $ldlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="center"><?php echo $ldlStatus->getComment(); ?></td>
                <td class="center"><?php echo $ldlStatus->getPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComment(); ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="center"><?php echo $glucoseStatus->getComment(); ?></td>
                <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
            </tr>
            <tr>
                <td>
                    <ul>
                        <li><?php echo $bmiStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $bmiStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="center"><?php echo $bmiStatus->getComment(); ?></td>
                <td class="center"><?php echo $bmiStatus->getPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    B.Total points possible and earned as of <?php echo date('m/d/Y'); ?>:
                </td>
                <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
                <td class="center"></td>
                <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
            </tr>

            <tr class="headerRow">
                <th colspan="4">3. Reward Goal Status & Coaching Options to Earn Full Incentive</th>
                <td>Reward Status</td>
            </tr>

            <tr>
                <td colspan="3">A. Total Points Earned</td>
                <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
                <td class="center" rowspan="2">
                    <?php if ($status->getStatus() == ComplianceStatus::COMPLIANT): ?>
                        Full Incentive
                    <?php elseif($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT): ?>
                        Partial Incentive
                    <?php else : ?>
                        Not Yet
                    <?php endif;?>
                </td>
            </tr>

            <tr>
                <td colspan="3" style="width: 300px;">
                    <p>
                        <span style="display: block; float: left; width:2%;">B.</span>
                        <span style="display: block; float: right; width:97%">
                            If you did not earn the full incentive through your points, you may still earn the full incentive
                            by completing the required number of coaching sessions (noted below) by January 31, 2022:
                        </span>
                    </p>
                    <ol style="clear:both; padding-left: 10px;">
                        <li>If you earned 60-79 points, then complete 3 coaching sessions.</li>
                        <li>If you earned less than 60 points, then complete 6 coaching sessions.</li>
                    </ol>
                    <p>
                      <b>
                        If you are a new hire, you will have 10
                        weeks from your screening date to complete
                        coaching sessions, if needed.
                      </b>
                    </p>
                 </td>
                <td class="center"> Sign up for coaching by calling 800.882.2109 option 1</td>
            </tr>


            </tbody>
        </table>

        <div style="margin: 40px 0px; overflow: hidden;">
            <div style="width: 56%; float: left">
                <p>
                    Login to <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> any time to:
                <ul>
                    <li>View all of your screening results and links in the report;  AND</li>
                    <li>Access powerful tools and resources for optimizing your health, care and wellbeing.</li>
                </ul>

                <p>
                    Thank you for getting your wellness screening done this year. This and many of your other
                    actions reflect how you value your own wellbeing and the wellbeing of others at home
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

        <div style="padding: 20px; background:#CCFFCC; display: table; ">
            <div style="display: table-cell; vertical-align: middle; width: 30%; padding: 0px 20px;">
                <p>
                    Strive to get and keep
                    your key biometric
                    measures in the green
                    zone for good health.
                </p>
                <p style="font-style: italic; text-align: center;">Here’s why <i class="fa fa-arrow-right"></i></p>
            </div>
            <div style="display: table-cell; width: 70%;">
                Below are some of these key measures that are strongly connected with your
                powers to prevent and avoid one or more of the following:
                <ul>
                    <li>Clogged arteries, heart attacks and strokes;</li>
                    <li>Diabetes, loss of vision, amputations & other complications;</li>
                    <li>Certain cancers</li>
                    <li>Back pain, hip and knee replacements;</li>
                    <li>Loss of mobility and quality of life at a young age; and</li>
                    <li>Loss of life at a young age.</li>
                </ul>
            </div>
        </div>

        <div>
            <style>
                tr {
                    border-bottom: 1px solid #fff;
                }

                td {
                    padding: 10px 0px;
                }

                hr.divisor {
                    width: 50px;
                    margin: 2px 0px;
                    border-top: 1px solid #444;
                    border-bottom: none;
                }
            </style>
            <table border="0" width="95%" id="ratingsTable">
                <tbody>
                <tr>
                    <td>
                        &nbsp;</td>
                    <td align="center" width="72">
                        &nbsp;</td>
                    <td align="center" width="73">
                        &nbsp;</td>
                    <td align="center" width="112">
                        &nbsp;</td>
                </tr>
                <tr>
                    <td width="190" valign="middle">
                        Risk ratings &amp; colors <i class="fa fa-arrow-right"></i>
                    </td>
                    <td align="center" width="72" valign="middle">
                        <strong><font color="#006600">OK/Good</font></strong></td>
                    <td align="center" width="73" valign="middle">
                        <strong><font color="#ff9933">Borderline</font></strong></td>
                    <td align="center" width="112" valign="middle">
                        <strong><font color="#ff0000">At-Risk</font> </strong></td>
                    <td rowspan="11" style="width: 33%; padding: 20px;">
                        Earn up to <strong>20 points</strong> for each of your results based on the risk ranges noted in this section.
                        <br><br>
                        Links:
                        <ul>
                            <li><a href="/content/1006">All Results/Reports</a></li>
                        </ul>
                        <br>
                        <strong>Interpreting the ranges and colors:</strong>
                        <ul>
                            <li><strong>At-Risk</strong> = Call or visit your doctor and share this result. Ask if a follow-up visit is recommended.</li>
                            <li><strong>Borderline</strong> = Share and discuss this result on your next call or visit.</li>
                            <li><strong>OK/Good</strong> = Share these results on your next visit.</li>
                            <li>See your report and related links for more information</li>
                        </ul>
                        <strong>Lessons for Review:</strong>
                        <ul>
                            <li><a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Blood Fat Lessons</a></li>
                            <li><a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Blood Sugar Lessons</a></li>
                            <li><a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Blood Metric's Lessons</a></li>
                            <li><a href="/content/9420?action=lessonManager&tab_alias=tobacco">Tobacco Lessons</a></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td>
                        &nbsp;</td>
                    <td align="center" width="72">
                        &nbsp;</td>
                    <td align="center" width="73">
                        &nbsp;</td>
                    <td align="center" width="112">
                        &nbsp;</td>
                </tr>
                <tr height="36px" style="border: none;">
                    <td>
                        <p style="text-align: right; margin-right: 10px;">
                            <em>Points for each result<br>
                            </em><em>that falls in this column <i class="fa fa-arrow-right"></i></em></p>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72" class="grayArrow">
                        20 points
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73" class="grayArrow">
                        10 points
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112" class="grayArrow">
                        0 points
                    </td>
                </tr>
                <tr>
                    <td>
                        <u>Key measures and ranges</u></td>
                    <td bgcolor="#ccffcc" align="center" width="72">
                        &nbsp;</td>
                    <td bgcolor="#ffff00" align="center" width="73">
                        &nbsp;</td>
                    <td bgcolor="#ff909a" align="center" width="112">
                        &nbsp;</td>
                </tr>

                <tr>
                    <td>
                        <ol start="1">
                            <li>
                                <strong>LDL cholesterol</strong></li>
                        </ol>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72">
                        ≤ 99
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73">
                        100 - 159
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112">
                        ≥ 160
                    </td>
                </tr>

                <tr>
                    <td>
                        <ol start="2">
                            <li>
                                <strong>Triglycerides</strong></li>
                        </ol>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72">
                        < 150
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73">
                        150 - <200
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112">
                        ≥ 200
                    </td>
                </tr>

                <tr>
                    <td valign="top">
                        <ol start="3">
                            <li>
                                <strong>Glucose (Fasting)</strong>
                                <ul>
                                    <li>Men</li>
                                    <br>
                                    <br>
                                    <li>Women</li>
                                </ul>
                            </li>
                        </ol>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72" valign="top">
                        <br>
                        70 - <100<br><br><br>
                        70 - <100
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73">
                        <br>
                        100 - 125<br>
                        50 - <70<br><br>
                        100 - 125<br>
                        40 - <70
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112">
                        <br>
                        ≥ 126<br>
                        < 50<br><br>
                        ≥ 126<br>
                        < 40
                    </td>
                </tr>

                <tr>
                    <td valign="bottom">
                        <ol start="4">
                            <li>
                                <strong>Body Mass Index</strong>
                            </li>
                        </ol>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
                        18.5 - 27.9
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
                        28 - 29.9
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
                        ≥30
                    </td>
                </tr>

                <tr>
                    <td>
                        <ol start="4">
                            <li>
                                <strong>Tobacco (Cotinine)</strong></li>
                        </ol>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72">
                        Negative<br><br>
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73">
                        N/A
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112">
                        Positive
                    </td>
                </tr>

                </tbody>
            </table>
        </div>

        <?php
    }
}
