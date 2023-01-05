<?php

class VictorEnvelope2018ComplianceProgram extends ComplianceProgram
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
        return new VictorEnvelope2018ComplianceProgramReportPrinter();
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
        $cotinineView->setReportName('1. Test Negative for Cotinine in Jan 2018');
        $cotinineView->setName('cotinine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative');
        $coreGroup->addComplianceView($cotinineView);

        $alternativeAction = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $alternativeAction->setReportName('Alternative Actions');
        $alternativeAction->setName('alternative_actions');
        $alternativeAction->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeAction->setAttribute('goal', 'Get Done by 02/15/18');
        $coreGroup->addComplianceView($alternativeAction);

        $this->addComplianceViewGroup($coreGroup);

        $healthGroup = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $healthGroup->setPointsRequiredForCompliance(3);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. BMI');
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->overrideTestRowData(null, null, 27.999, null);
        $bmiView->setAttribute('goal', '< 28 or 2 less from 2017');
        $bmiView->setPostEvaluateCallback($this->checkImprovement('bmi', 'decrease', 2));
        $healthGroup->addComplianceView($bmiView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('2. HDL Cholesterol');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->overrideTestRowData(null, 40, null, null);
        $hdlView->setAttribute('goal', '≥ 40 or 4 more from 2017');
        $hdlView->setPostEvaluateCallback($this->checkImprovement('hdl', 'increase', 4));
        $healthGroup->addComplianceView($hdlView);

        $hdlRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlRatioView->setReportName('3. Total Chol/HDL ratio');
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlRatioView->overrideTestRowData(null, null, 4.999, null);
        $hdlRatioView->setAttribute('goal', '<5.0 or .25 less from 2017');
        $hdlRatioView->setPostEvaluateCallback($this->checkImprovement('totalhdlratio', 'decrease', 0.25));
        $healthGroup->addComplianceView($hdlRatioView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglyceridesView->setReportName('4. Triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setAttribute('goal', '<150 or 15 less from 2017');
        $triglyceridesView->setPostEvaluateCallback($this->checkImprovement('triglycerides', 'decrease', 15));
        $healthGroup->addComplianceView($triglyceridesView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('5. Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setAttribute('goal', '<100 or 10 less from 2017');
        $glucoseView->setPostEvaluateCallback($this->checkImprovement('glucose', 'decrease', 10));
        $healthGroup->addComplianceView($glucoseView);

        $this->addComplianceViewGroup($healthGroup);


        $alternativeGroup = new ComplianceViewGroup('alternative', 'alternative');
        $alternativeGroup->setPointsRequiredForCompliance(3);

        $alternativeMeetDrToddView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $alternativeMeetDrToddView->setReportName('a. Meet with Dr. Todd 2 times by 4/6/18');
        $alternativeMeetDrToddView->setName('alternative_meet_dr_todd');
        $alternativeMeetDrToddView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeMeetDrToddView->setAttribute('goal', '<div style="margin-top: 20px;">Have 1<sup>st</sup> meeting by 2/28/18; and 2<sup>nd</sup>  meeting by 4/6/18</div>');
        $alternativeGroup->addComplianceView($alternativeMeetDrToddView);

        $attendOnsiteSeminarView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $attendOnsiteSeminarView->setReportName('b. Attend on-site seminar');
        $attendOnsiteSeminarView->setName('attend_onsite_seminar');
        $attendOnsiteSeminarView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $attendOnsiteSeminarView->setAttribute('goal', 'Get done by 4/6/18');
        $alternativeGroup->addComplianceView($attendOnsiteSeminarView);

        $alternativeElearningView = new CompleteELearningGroupSet($programStart, $programEnd, '2018_lessons');
        $alternativeElearningView->setReportName('Complete a total of 3 more learning activities');
        $alternativeElearningView->setName('alternative_elearning');
        $alternativeElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeElearningView->setNumberRequired(3);
        $alternativeElearningView->setAttribute('goal', 'Get done by 4/6/18 <br /> <a href="/content/9420?action=lessonManager&tab_alias=2018_lessons">Click To-Do Lessons</a>');
        $alternativeGroup->addComplianceView($alternativeElearningView);

        $this->addComplianceViewGroup($alternativeGroup);
    }

        protected function checkImprovement($test, $calculationMethod = 'decrease', $threshold) {
            $programStart = new \DateTime('@'.$this->getStartDate());
            $programEnd = new \DateTime('@'.$this->getEndDate());

            $lastStart = new \DateTime('2016-12-01');
            $lastEnd = new \DateTime('2017-11-30');

            return function(ComplianceViewStatus $status, User $user) use ($test, $programStart, $programEnd, $lastStart, $lastEnd, $calculationMethod, $threshold) {
                static $cache = null;

                if ($cache === null || $cache['user_id'] != $user->id) {
                    $cache = array(
                        'user_id' => $user->id,
                        'this' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $programStart, $programEnd, array('merge'=> true)),
                        'last' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $lastStart, $lastEnd, array('merge'=> true))
                    );
                }

                if ($cache['this'] || $cache['last']) {
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
                        $lastVal = isset($cache['last'][$test]) ? (float) $cache['last'][$test] : null;
                        $thisVal = isset($cache['this'][$test]) ? (float) $cache['this'][$test] : null;
                    }

                    if ($thisVal && $lastVal) {
                        $change = $thisVal - $lastVal;
                        $status->setAttribute('2017_2018_change', $change);

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

                    $status->setAttribute('2017_result', $lastVal);
                    $status->setAttribute('2018_result', $thisVal);

                    if ($isImproved) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    }
                }
            };
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
        $alternativeStatus = $coreGroupStatus->getComplianceViewStatus('alternative_actions');


        if($cotinineStatus->getStatus() == ComplianceStatus::COMPLIANT || $alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $coreGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        }
    }
}

class VictorEnvelope2018ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                font-size:9pt;
                height:20px;
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
                width:8.4in;
                font-size: 9pt;
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
            }

            #text-area-bottom div{
                font-size: 10px;
            }

        </style>

        <style type="text/css" media="print">
            body {
                margin:0.2in;
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
                    <img src="/images/empower/victor_envelope_logo_2018.jpg" style="height:60px;"  />
                </div>
            </p>

            <p style="margin-left:3in; clear: both;">
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <div id="text-area-top">
                <div>
                    <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                    <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
                </div>


                <div><strong><?php echo $client->getName() ?></strong> is committed to supporting the health and wellbeing of its employees
                 and their families.  The first step in promoting good health is awareness.  That is why Victor Envelope offers
                 the annual health screenings through Empower Health Services.  To encourage employees to take advantage of
                 this wonderful benefit, Victor Envelope offers 2 incentives related to the program.</div>

                <div>
                    The first incentive is based on Nicotine use and carries a value of $60 per MONTH.  This will be measured
                     using a Cotinine test.  Participants must test negative for Cotinine in order to earn the incentive.
                </div>

                <div>The second incentive is based on scoring in the healthy range in at least 3 of 5 key areas or showing
                    a certain level of improvement over your 2016 results and carries a value of $60 per MONTH.</div>
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
                    <div style="font-weight: bold; text-align: center; margin-bottom: 1px;">Some of these online tools include:</div>
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
        $alternativeStatus = $status->getComplianceViewStatus('alternative_actions');

        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $alternativeGroupStatus = $status->getComplianceViewGroupStatus('alternative');
        $alternativeMeetDrToddStatus = $status->getComplianceViewStatus('alternative_meet_dr_todd');
        $attendOnsiteSeminarStatus = $status->getComplianceViewStatus('attend_onsite_seminar');
        $alternativeElearningStatus = $status->getComplianceViewStatus('alternative_elearning');



        $alternativeAllThreeStatus = ComplianceStatus::NOT_COMPLIANT;
        if($alternativeElearningStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $alternativeMeetDrToddStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $attendOnsiteSeminarStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $alternativeAllThreeStatus = ComplianceStatus::COMPLIANT;
        }


        $measureAndAlternativeStatus = ComplianceStatus::NOT_COMPLIANT;
        if($healthyGroupStatus->getStatus() == ComplianceStatus::COMPLIANT || $alternativeAllThreeStatus == ComplianceStatus::COMPLIANT) {
            $measureAndAlternativeStatus = ComplianceStatus::COMPLIANT;
        }


        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 320px;">A. Tobacco Free Incentive – Meet Goal for A1 or A2</th>
                        <th colspan="2">Goal</th>
                        <th colspan="2">Result</th>
                        <th>Met Goal</th>
                        <th style="width: 80px;">Incentive Req. Met</th>
                    </tr>

                    <tr class="status-<?php echo $cotinineStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $cotinineStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="2"><?php echo $cotinineStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td colspan="2"><?php echo $cotinineStatus->getComment() ?></td>
                        <td>
                            <?php if($cotinineStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td rowspan="2" class="status-<?php echo $coreStatus->getStatus() ?>">
                            <?php echo $coreStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                        </td>
                    </tr>


                    <tr class="status-<?php echo $alternativeStatus->getStatus() ?>">
                        <td style="text-align: left;">
                            <div>2.  OR Alternative - Get all of these actions done: </div>
                            <div style="margin-left: 20px; font-size: 8pt;">
                                   a) Sign-up for Freedom from Smoking® Program <br />
                                   b) Complete above Smoking Cessation Program <br />
                                   c) Email proof of completion to <a href="mailto:incentives@empowerhealthservices.com">incentives@empowerhealthservices.com</a> <br />
                                   d) Have initial consultation with Dr. Todd <br />
                                   e) Have final consultation with Dr. Todd <br />
                            </div>
                        </td>
                        <td colspan="2">
                            <div style="text-align: left;">
                                a.	Get Done by 02/15/18 <br />
                                b.	Get Done by 04/06/18 <br />
                                c.	Get Done by 04/06/18 <br />
                                d.	Meet by 02/28/18 <br />
                                e.	Meet by 04/6/18 <br />

                            </div>
                        </td>
                        <td colspan="2"><?php echo $alternativeStatus->getComment() ?></td>
                        <td>
                            <div style="margin-top: 20px;">
                                <?php if($alternativeStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>
                            </div>
                        </td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left;">B.  Healthy Measures Incentive Meet goal for 3 or more of  B1-5</th>
                        <th style="width: 150px;">Goal</th>
                        <th>Y1: 2017 Result</th>
                        <th>Y2: 2018 Result</th>
                        <th>Change Y2-Y1</th>
                        <th>Met Goal</th>
                        <th></th>
                    </tr>

                    <?php $i = 0; ?>
                    <?php foreach($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td><?php echo $viewStatus->getAttribute('2017_result') ?></td>
                            <td><?php echo $viewStatus->getAttribute('2018_result') ?></td>
                            <td><?php echo $viewStatus->getAttribute('2017_2018_change') ?></td>
                            <td>
                                <?php if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>
                            </td>
                            <?php if($i == 0 ) : ?>
                            <td rowspan="9" class="status-<?php echo $measureAndAlternativeStatus ?>">
                                <?php if($measureAndAlternativeStatus == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>
                            </td>

                            <?php endif; $i++ ?>
                        </tr>
                    <?php endforeach ?>


                    <tr class="headerRow">
                        <th style="text-align: left;">Or Alternative for B:  Meet goals for a, b & c</th>
                        <th colspan="4">Goal</th>
                        <th></th>
                    </tr>

                    <tr class="status-<?php echo $alternativeAllThreeStatus ?>">
                        <td style="text-align: left;" >
                           <?php echo $alternativeMeetDrToddStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td colspan="4"><?php echo $alternativeMeetDrToddStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                            <div style="margin-top: 20px;">
                                <?php if($alternativeMeetDrToddStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>

                            </div>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $attendOnsiteSeminarStatus->getStatus() ?>">
                        <td style="text-align: left;" >
                           <?php echo $attendOnsiteSeminarStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td colspan="4"><?php echo $attendOnsiteSeminarStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                                <?php if($attendOnsiteSeminarStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $alternativeElearningStatus->getStatus() ?>">
                        <td style="text-align: left;" >
                            c. Complete a total of 3 more learning activities done through any mix of these options:
                            <div style="margin-left: 20px;">
                                1) Complete targeted e-learning lessons <br />
                                2) Telephone consultations with Dr Todd <br />
                                3) Attend on-site seminars
                            </div>
                        </td>
                        <td colspan="4"><?php echo $alternativeElearningStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                            <?php if($alternativeElearningStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}