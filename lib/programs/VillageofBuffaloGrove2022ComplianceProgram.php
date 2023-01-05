<?php

class VillageofBuffaloGrove2022ComplianceProgram extends ComplianceProgram
{
    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Get Started – Complete by April 22, 2022:');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Wellness Screening');
        $screeningView->setName('complete_screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete Empower Health Assessment (HRA)');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the point group
        $pointGroup = new ComplianceViewGroup('points', 'Earn 35+ points from your screening results OR submit a completed Alternate Qualification Form by May 13, 2022');

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->overrideTestRowData(90, 100, 199.999, 239.999);
        $pointGroup->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->overrideTestRowData(25, 40, 999, null, "M");
        $hdlCholesterolView->overrideTestRowData(25, 50, 999, null, "F");
        $pointGroup->addComplianceView($hdlCholesterolView);

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

        $bodyFatBMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setReportName('BMI / Body Mass Index');
        $bodyFatBMIView->overrideTestRowData(18.5, 18.5, 28, 31);
        $pointGroup->addComplianceView($bodyFatBMIView);

        $qualificationFormView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $qualificationFormView->setReportName('Turn-In a Completed Alternate Qualification Form');
        $qualificationFormView->setName('qualification_form');
        $qualificationFormView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(35, 0, 0, 0));
        $pointGroup->addComplianceView($qualificationFormView);

        $pointGroup->setPointsRequiredForCompliance(35);
        $this->addComplianceViewGroup($pointGroup);
    }


    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new VillageofBuffaloGrove2022ComplianceProgramReportPrinter();

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

class VillageofBuffaloGrove2022ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $totalCholesterolStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_total_cholesterol_screening_test');
        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_hdl_screening_test');
        $ldlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_ldl_screening_test');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_triglycerides_screening_test');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_glucose_screening_test');
        $bodyFatBMIStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_bmi_screening_test');
        $qualificationForm = $pointGroupStatus->getComplianceViewStatus('qualification_form');

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
                background-color:#3366FF;
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
                background-color:#3366FF;
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

        <div style="float: left;">
            <img style="height: 50px;" src="https://static.hpn.com/images/empower/ehs_logo.jpg"/>
            <p>
                4205 Westbrook Drive <br>
                Aurora, IL 60504
            </p>
        </div>



        <div class="address">
            <?php echo $user ?> <br/>
            <?php echo $user->getFullAddress("<br/>") ?>
        </div>

        <p>Dear <?php echo $user ?>, <span style="text-align: right; float: right;"><?php echo date("m/d/Y") ?></span></p>
        <p></p>
        <p>
            The Village of Buffalo Grove is committed to supporting the health and wellbeing of its employees and their
            families. A first step in promoting good health is awareness. That is why we offer the annual wellness
            screening through Empower Health Services. Participating in this program not only allows you to be proactive
            about your health but offers participants the chance to earn a financial incentive as well.
        </p>

        <p>
            The first half of the incentive ($240) is based on participating in the biometric screening and completing
            the online Health Risk Assessment (HRA).
        </p>

        <p>
            The second half of the incentive ($240) can be earned by accumulating a minimum of 35 points based on
            screening results. Each of the results listed below has a scoring range of 10 points (in-range values),
            5 points (moderate-risk level), or 0 points (high-risk level). If you are unable to earn at least 35 points
            based on your results, you can still receive the full incentive by following up with your primary care
            provider to complete an Alternate Qualification Form which can be submitted to Empower Health Services.
            This form can be downloaded by clicking the link in the lower right corner of the chart below.
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
                <th>1. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td colspan="2">Date Completed</td>
                <td>Status</td>
                <td>Action Links</td>
            </tr>

            <tr>
                <td>A. <?php echo $completeScreeningStatus->getComplianceView()
                            ->getReportName() ?></td>
                <td colspan="2" class="center">
                    <?php echo $completeScreeningStatus->getComment(); ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
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
                <td colspan="2" class="center">
                    <?php echo $completeHRAStatus->getComment(); ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeHRAStatus->getLight(); ?>" class="light"/>
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
                <td>My Result</td>
                <td># Points Earned</td>
                <td># Points Possible</td>
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
                        <li><?php echo $totalCholesterolStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $totalCholesterolStatus->getComment(); ?></td>
                <td class="center"><?php echo $totalCholesterolStatus->getPoints(); ?></td>
                <td class="center"><?php echo $totalCholesterolStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
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
                        <li><?php echo $hdlStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $hdlStatus->getComment(); ?></td>
                <td class="center"><?php echo $hdlStatus->getPoints(); ?></td>
                <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $ldlStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $ldlStatus->getComment(); ?></td>
                <td class="center"><?php echo $ldlStatus->getPoints(); ?></td>
                <td class="center"><?php echo $ldlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $triglyceridesStatus->getComment(); ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>
            <tr>
                <td>
                    <ul>
                        <li><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $glucoseStatus->getComment(); ?></td>
                <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $bodyFatBMIStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $bodyFatBMIStatus->getComment(); ?></td>
                <td class="center"><?php echo $bodyFatBMIStatus->getPoints(); ?></td>
                <td class="center"><?php echo $bodyFatBMIStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td colspan="2" style="width: 600px;">
                    B. If needed, work with your primary care provider to complete <a href="/resources/10743/Village_of_Buffalo_Grove_2022_AQF_012722.pdf" target="_blank">Alternate Qualification Form (AQF)</a>.
                    Download the form from the link to the right and submit it to Empower Health Services by <span style="font-weight: bold; text-decoration: underline;">May 13, 2022</span> via:

                    <ul>
                        <li><span style="font-weight: bold;">Upload</span> completed form to <a href="http://ehsupload.com" target="_blank">ehsupload.com</a></li>
                        <li><span style="font-weight: bold;">Fax</span> completed form to 630.385.0156 <br />(Attn: Reports Department)</li>
                        <li><span style="font-weight: bold;">Mail</span> completed form to: EHS Reports Department - 4205 Westbrook Drive, Aurora, IL 60504</li>
                    </ul>
                </td>
                <td class="center"><?php echo $qualificationForm->getPoints(); ?></td>
                <td class="center"><?php echo $qualificationForm->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="center"><a href="	/resources/10743/Village_of_Buffalo_Grove_2022_AQF_012722.pdf" download>Click here to download the AQF pdf</a></td>
            </tr>

            <tr>
                <td class="right" colspan="2">Points earned and reward goal status as of <?php echo date('m/d/Y'); ?> =</td>
                <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
                <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
                <td class="center">
                    <?php if ($pointGroupStatus->getPoints() >= 35):?>
                        Reward Goal Met! <img style="margin-left: 10px;" src="/images/lights/greenlight.gif" class="light">
                    <?php else:?>
                        Goal Not Yet Met <img style="margin-left: 10px;" src="/images/lights/yellowlight.gif" class="light">
                    <?php endif;?>
                </td>
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
                        Earn up to <strong>10 points</strong> for each of your results based on the risk ranges noted in this section.
                        <br><br>
                        Links:
                        <ul>
                            <li><a href="/content/1006">All Results/Reports</a></li>
                        </ul>
                        *NOTE: Both systolic and diastolic blood pressure results need to be in the better range for higher points.
                        <br><br>
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
                            <li><a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Blood Pressure Lessons</a></li>
                            <li><a href="/content/9420?action=lessonManager&tab_alias=body_fat">Body Metric's Lessons</a></li>
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
                        <p>
                            <em>Points for each result<br>
                            </em><em>that falls in this column <i class="fa fa-arrow-right"></i></em></p>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72" class="grayArrow">
                        10 points
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73" class="grayArrow">
                        5 points
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
                        <ol>
                            <li>
                                <strong>Total cholesterol</strong></li>
                        </ol>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72">
                        100 - <200<br><br>
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73">
                        200 - 239<br>
                        90 - <100
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112">
                        ≥ 240<br>
                        < 90
                    </td>
                </tr>
                <tr>
                    <td>
                        <ol start="2">
                            <li>
                                <strong>HDL cholesterol</strong>
                                <ul>
                                    <li>Men</li>
                                    <li>Woman</li>
                                </ul>
                            </li>
                        </ol>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72">
                        ≥ 40<br>
                        ≥ 50
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73">
                        25 < 40<br>
                        25 - <50
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112">
                        < 25<br>
                        < 25
                    </td>
                </tr>
                <tr>
                    <td>
                        <ol start="3">
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
                        <ol start="4">
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
                        <ol start="5">
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
                        <ol start="6">
                            <li>
                                The better of:<br>
                                <strong>Body Mass Index&nbsp;&nbsp;<br>
                                </strong>•&nbsp; men &amp; women<br>
                                - OR -<br>
                                <strong>% Body Fat:</strong><br>
                                • Men<br>
                                • Women
                            </li>
                        </ol>
                    </td>
                    <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
                        <p>
                            18.5 - 28.0<br><br><br>
                            6 - <18%<br>
                            14 - <25%</p>
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
                        <p>
                            28.1 - <31<br>
                            <br>
                            <br>
                            18 - <25%<br>
                            25 - <32%</p>
                    </td>
                    <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
                        <p>
                            ≥31.1; <18.5<br>
                            <br>
                            <br>
                            ≥25; <6%<br>
                            ≥32; <14%</p>
                    </td>
                </tr>
                <tr>
                    <td valign="middle" style="text-align: center; background: #00FF00">
                        <strong>Total Possible</strong> <i class="fa fa-arrow-right"></i>
                    </td>
                    <td bgcolor="#00FF00" align="center" width="72" valign="bottom">
                        <strong>60 points</strong>
                    </td>
                    <td bgcolor="#ffff00" align="center" width="73" valign="bottom">

                    </td>
                    <td bgcolor="#ff909a" align="center" width="112" valign="bottom">

                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <?php
    }
}
