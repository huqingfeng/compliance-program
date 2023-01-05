<?php

class VictorEnvelope2017ComplianceProgram extends ComplianceProgram
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
        return new VictorEnvelope2017ComplianceProgramReportPrinter();
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
        $coreGroup->setPointsRequiredForCompliance(4);

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('1. Test Negative for Cotinine in January 2017');
        $cotinineView->setName('cotinine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative');
        $coreGroup->addComplianceView($cotinineView);

        $elearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $elearningView->setReportName('<div style="margin-left: 20px;">a) Complete 3 Tobacco E-Lessons</div>');
        $elearningView->setName('elearning');
        $elearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $elearningView->setNumberRequired(3);
        $elearningView->setAttribute('goal', '<div style="margin-top: 20px;">3 by 03/31/17: <a href="/content/9420?action=lessonManager&tab_alias=tobacco">Click To-Do</a></div>');
        $coreGroup->addComplianceView($elearningView);

        $meetDrFirstView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $meetDrFirstView->setReportName('<div style="margin-left: 20px;">b) Meet with Dr. Todd in February 2017</div>');
        $meetDrFirstView->setName('meet_dr_first');
        $meetDrFirstView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $meetDrFirstView->setAttribute('goal', '1<sup>st</sup> Meeting by 02/28/17');
        $coreGroup->addComplianceView($meetDrFirstView);

        $meetDrSecondView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $meetDrSecondView->setReportName('<div style="margin-left: 20px;">c) Meet again with Dr. Todd in April 2017</div>');
        $meetDrSecondView->setName('meet_dr_second');
        $meetDrSecondView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $meetDrSecondView->setAttribute('goal', '2<sup>nd</sup> Meeting by 04/30/17');
        $coreGroup->addComplianceView($meetDrSecondView);

        $this->addComplianceViewGroup($coreGroup);

        $healthGroup = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $healthGroup->setPointsRequiredForCompliance(3);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. BMI');
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->overrideTestRowData(null, null, 27.999, null);
        $bmiView->setAttribute('goal', '< 28');
        $healthGroup->addComplianceView($bmiView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('2. HDL Cholesterol');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->overrideTestRowData(null, 40, null, null);
        $hdlView->setAttribute('goal', '≥ 40');
        $healthGroup->addComplianceView($hdlView);

        $hdlRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlRatioView->setReportName('3. Total Chol/HDL ratio');
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlRatioView->overrideTestRowData(null, null, 4.999, null);
        $hdlRatioView->setAttribute('goal', '<5.0');
        $healthGroup->addComplianceView($hdlRatioView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglyceridesView->setReportName('4. Triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setAttribute('goal', '<150');
        $healthGroup->addComplianceView($triglyceridesView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('5. Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setAttribute('goal', '<100');
        $healthGroup->addComplianceView($glucoseView);

        $this->addComplianceViewGroup($healthGroup);


        $alternativeGroup = new ComplianceViewGroup('alternative', 'alternative');
        $alternativeGroup->setPointsRequiredForCompliance(3);

        $alternativeMeetDrFirstView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $alternativeMeetDrFirstView->setReportName('<div style="margin-left: 20px;">a. Meet with Dr. Todd in February 2017</div>');
        $alternativeMeetDrFirstView->setName('alternative_meet_dr_first');
        $alternativeMeetDrFirstView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeMeetDrFirstView->setAttribute('goal', '<div style="margin-top: 20px;">1<sup>st</sup> Meeting by 02/28/17</div>');
        $alternativeGroup->addComplianceView($alternativeMeetDrFirstView);

        $alternativeElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'blood_sugars');
        $alternativeElearningView->setReportName('<div style="margin-left: 20px;">b. Do 2 Lessons; OR 2 seminars; OR 2 calls</div>');
        $alternativeElearningView->setName('alternative_elearning');
        $alternativeElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeElearningView->setNumberRequired(2);
        $alternativeElearningView->setAttribute('goal', 'By 03/31/17: <a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Click To-Do</a>');
        $alternativeGroup->addComplianceView($alternativeElearningView);

        $alternativeMeetDrSecondView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $alternativeMeetDrSecondView->setReportName('<div style="margin-left: 20px;">c. Meet again with Dr. Todd in April  2017</div>');
        $alternativeMeetDrSecondView->setName('alternative_meet_dr_second');
        $alternativeMeetDrSecondView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeMeetDrSecondView->setAttribute('goal', '2<sup>nd</sup> Meeting by 04/30/17');
        $alternativeGroup->addComplianceView($alternativeMeetDrSecondView);

        $this->addComplianceViewGroup($alternativeGroup);
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


    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $cotinineStatus = $coreGroupStatus->getComplianceViewStatus('cotinine');
        $elearningStatus = $coreGroupStatus->getComplianceViewStatus('elearning');
        $meetDrFirstStatus = $coreGroupStatus->getComplianceViewStatus('meet_dr_first');
        $meetDrSecondStatus = $coreGroupStatus->getComplianceViewStatus('meet_dr_second');


        if($cotinineStatus->getStatus() == ComplianceStatus::COMPLIANT
            || ($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT
                && $meetDrFirstStatus->getStatus() == ComplianceStatus::COMPLIANT
                && $meetDrSecondStatus->getStatus() == ComplianceStatus::COMPLIANT)) {
            $coreGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        }
    }

    const VICTOR_ENVELOPE_2017_RECORD_ID = 1018;
}

class VictorEnvelope2017ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                height:26px;
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

            #text-area-top div{
                font-size: 10pt;
                margin-bottom: 2px;
            }

            #text-area-bottom div{
                font-size: 11px;
                margin: 1px 0;
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
                <img src="/images/empower/victor_envelope_logo.png" style="height:60px;"  />
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


                <div><?php echo $client->getName() ?> is committed to supporting the health, care and wellbeing of everyone
                    at work and their families – in part, through the wellness screenings, incentives and other resources
                    offered each year.</div>

                <div>
                    Your participation in the wellness screening is just one of many actions you can take that benefit your health.
                </div>

                <div>There are 2 incentives being offered this year.  The first is related to nicotine use.  The second is
                    related to overall health as measured by 5 key criteria. In May 2017, <?php echo $client->getName() ?>
                    will receive a list of everyone meeting the goals for 1 or both incentives.  Note about privacy:  As
                    always, individual screening results are not shared with employers and remain confidential.</div>
            </div>
            
            <?php echo $this->getTable($status) ?>

            <div id="text-area-bottom">
                <div style="width: 56%; float: left">
                    <div>
                        Click here for more details about Alternatives A1 and B7 (if needed).
                    </div>
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
                    <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Some of these online tools include:</div>
                    <div>
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

        <?php
    }

    private function getTable($status)
    {
        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $cotinineStatus = $status->getComplianceViewStatus('cotinine');
        $elearningStatus = $status->getComplianceViewStatus('elearning');
        $meetDrFirstStatus = $status->getComplianceViewStatus('meet_dr_first');
        $meetDrSecondStatus = $status->getComplianceViewStatus('meet_dr_second');

        $allThreeStatus = ComplianceStatus::NOT_COMPLIANT;
        if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $meetDrFirstStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $meetDrSecondStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $allThreeStatus = ComplianceStatus::COMPLIANT;
        }




        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $alternativeGroupStatus = $status->getComplianceViewGroupStatus('alternative');
        $alternativeElearningStatus = $status->getComplianceViewStatus('alternative_elearning');
        $alternativeMeetDrFirstStatus = $status->getComplianceViewStatus('alternative_meet_dr_first');
        $alternativeMeetDrSecondStatus = $status->getComplianceViewStatus('alternative_meet_dr_second');

        $alternativeAllThreeStatus = ComplianceStatus::NOT_COMPLIANT;
        if($alternativeElearningStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $alternativeMeetDrFirstStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $alternativeMeetDrSecondStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $alternativeAllThreeStatus = ComplianceStatus::COMPLIANT;
        }

        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 360px;">A. Tobacco	Free Incentive</th>
                        <th>Goal</th>
                        <th>Goal Met & Result</th>
                    </tr>

                    <tr class="status-<?php echo $cotinineStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $cotinineStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $cotinineStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                            <?php if($cotinineStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes Negative (<?php echo $cotinineStatus->getAttribute('date') ?>)
                            <?php else : ?>
                                No Positive (<?php echo $cotinineStatus->getAttribute('date') ?>)
                            <?php endif ?>
                        </td>
                    </tr>


                    <tr class="status-<?php echo $allThreeStatus ?>">
                        <td style="text-align: left;" rowspan="3">
                            <div>2.  OR Alternative - Get all of these actions done: </div>
                            <?php echo $elearningStatus->getComplianceView()->getReportName() ?>
                            <?php echo $meetDrFirstStatus->getComplianceView()->getReportName() ?>
                            <?php echo $meetDrSecondStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td><?php echo $elearningStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                            <div style="margin-top: 20px;">
                                <?php if($elearningStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes <?php echo count($elearningStatus->getAttribute('lessons_completed')) ?> done (<?php echo $elearningStatus->getComment() ?>)
                                <?php else : ?>
                                    No
                                <?php endif ?>
                            </div>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $meetDrFirstStatus->getStatus() ?>">
                        <td><?php echo $meetDrFirstStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                        <?php if($meetDrFirstStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                            Yes <?php echo $meetDrFirstStatus->getComment() ?>
                        <?php else : ?>
                            No
                        <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $meetDrSecondStatus->getStatus() ?>">
                        <td><?php echo $meetDrSecondStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                        <?php if($meetDrSecondStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                            Yes <?php echo $meetDrSecondStatus->getComment() ?>
                        <?php else : ?>
                            No
                        <?php endif ?>
                        </td>
                    </tr>



                    <tr class="status-<?php echo $coreStatus->getStatus() ?>">
                        <td style="text-align: right;" colspan="2">
                            3.	Tobacco Incentive Status: Meets goals for A1 or A2 &rarr;
                        </td>
                        <td><?php echo $coreStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left;">B.  Healthy Measures Incentive</th>
                        <th>Goal</th>
                        <th>Goal Met & Result</th>
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
                        <td style="text-align: right;" colspan="2">6. Have 3 or more of the above in the goal range; If <3, see B7 below  &rarr;</td>
                        <td>
                            <?php echo $healthyGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                        </td>
                    </tr>


                    <tr class="status-<?php echo $alternativeAllThreeStatus ?>">
                        <td rowspan="3" style="text-align: left;" >
                            <div>7. OR Alternative - Get all of these actions done:</div>

                            <div><?php echo $alternativeMeetDrFirstStatus->getComplianceView()->getReportName() ?></div>

                            <div><?php echo $alternativeElearningStatus->getComplianceView()->getReportName() ?></div>

                            <div><?php echo $alternativeMeetDrSecondStatus->getComplianceView()->getReportName() ?></div>

                        </td>
                        <td><?php echo $alternativeMeetDrFirstStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                            <div style="margin-top: 20px;">
                                <?php if($alternativeMeetDrFirstStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes <?php echo $alternativeMeetDrFirstStatus->getComment() ?>
                                <?php else : ?>
                                    No
                                <?php endif ?>

                            </div>

                        </td>
                    </tr>

                    <tr class="status-<?php echo $alternativeElearningStatus->getStatus() ?>">
                        <td><?php echo $alternativeElearningStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                                <?php if($alternativeElearningStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes <?php echo count($alternativeElearningStatus->getAttribute('lessons_completed')) ?> done (<?php echo $alternativeElearningStatus->getComment() ?>)
                                <?php else : ?>
                                    No
                                <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $alternativeMeetDrSecondStatus->getStatus() ?>">
                        <td><?php echo $alternativeMeetDrSecondStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                                <?php if($alternativeMeetDrSecondStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes <?php echo $alternativeMeetDrSecondStatus->getComment() ?>
                                <?php else : ?>
                                    No
                                <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $healthyGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT || $alternativeGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 4 : 1 ?>">
                        <td colspan="2" style="text-align: right">
                            8. Healthy Measures Status:   Meets goals for B6 or B7  &rarr;
                        </td>
                        <td>
                            <?php
                                echo $healthyGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT || $alternativeGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No'
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}