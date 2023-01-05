<?php


class DentonCartage2018ComplianceProgram extends ComplianceProgram
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
        return new DentonCartage2018ComplianceProgramReportPrinter();
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

        $screeningView = new CompleteScreeningComplianceView($programStart, '2018-06-30');
        $screeningView->setReportName('Complete the onsite or offsite wellness screening – this provides the information needed to help you earn the incentives for B below.');
        $screeningView->setName('screening');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screeningView->setAttribute('deadline', '6/30/2018');
        $coreGroup->addComplianceView($screeningView);


        $this->addComplianceViewGroup($coreGroup);

        $healthGroup = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $healthGroup->setPointsRequiredForCompliance(3);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. BMI');
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->overrideTestRowData(null, null, 29, null);
        $bmiView->setAttribute('goal', '≤ 29 or 1 less from 2017');
        $bmiView->setPostEvaluateCallback($this->checkImprovement('bmi', 'decrease', 1));
        $healthGroup->addComplianceView($bmiView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('2. LDL Cholesterol');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->overrideTestRowData(null, null, 130, null);
        $ldlView->setAttribute('goal', '≤130 or 15 less from 2017');
        $ldlView->setPostEvaluateCallback($this->checkImprovement('hdl', 'decrease', 15));
        $healthGroup->addComplianceView($ldlView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglyceridesView->setReportName('3. Triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setAttribute('goal', '<150 or 15 less from 2017');
        $triglyceridesView->setPostEvaluateCallback($this->checkImprovement('triglycerides', 'decrease', 15));
        $healthGroup->addComplianceView($triglyceridesView);


        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('4. Cotinine');
        $cotinineView->setName('cotinine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative');
        $cotinineView->setPostEvaluateCallback($this->checkImprovement('cotinine', 'none', 0));
        $healthGroup->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($healthGroup);


        $alternativeGroup = new ComplianceViewGroup('alternative', 'alternative');
        $alternativeGroup->setPointsRequiredForCompliance(2);

        $alternativeQualificationFormView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $alternativeQualificationFormView->setReportName('a. Submit completed Alternative Qualification Form;  or');
        $alternativeQualificationFormView->setName('alternative_qualification_form');
        $alternativeQualificationFormView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeQualificationFormView->setAttribute('goal', '<a href="/resources/10077/Denton Cartage_AQF_042518.pdf" target="_blank">Download Form</a> Submit completed form by 9/1/18');
        $alternativeGroup->addComplianceView($alternativeQualificationFormView);

        $alternativeMeetDrToddView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $alternativeMeetDrToddView->setReportName('b. Meet with Dr. Todd 3 times by 9/1/18 <br /> <a href="http://www.inspirecorporatewellness.com/dentoncartage" target="_blank">Click here for phone # and other details.</a>');
        $alternativeMeetDrToddView->setName('alternative_meet_dr_todd');
        $alternativeMeetDrToddView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeMeetDrToddView->setAttribute('goal', '<div style="margin-top: 20px;">1<sup>st</sup> meeting by 7/17/18; <br /> 2<sup>nd</sup>  meeting by 8/15/18 <br /> 3<sup>rd</sup>  meeting by 9/1/18</div>');
        $alternativeGroup->addComplianceView($alternativeMeetDrToddView);


        $this->addComplianceViewGroup($alternativeGroup);
    }

    protected function checkImprovement($test, $calculationMethod = 'decrease', $threshold) {
        $programStart = new \DateTime('@'.$this->getStartDate());
        $programEnd = new \DateTime('@'.$this->getEndDate());

        $lastStart = new \DateTime('2017-01-01');
        $lastEnd = new \DateTime('2017-12-30');

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

                } elseif($test == 'cotinine') {
                    $lastVal = isset($cache['last'][$test]) ? $cache['last'][$test] : null;
                    $thisVal = isset($cache['this'][$test]) ? $cache['this'][$test] : null;
                } else {
                    $lastVal = isset($cache['last'][$test]) ? (float) $cache['last'][$test] : null;
                    $thisVal = isset($cache['this'][$test]) ? (float) $cache['this'][$test] : null;
                }

                if ($thisVal && $lastVal) {
                    $change = $thisVal - $lastVal;

                    if($calculationMethod == 'none') {
                        $status->setAttribute('2017_2018_change', '');
                    } else {
                        $status->setAttribute('2017_2018_change', $change);
                    }


                    if($calculationMethod == 'decrease') {
                        if(($change + $threshold) <= 0) {
                            $isImproved = true;
                        }
                    } elseif($calculationMethod == 'none') {
                        $isImproved = false;
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


    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $cotinine = $status->getComplianceViewStatus('cotinine');
    }
}

class DentonCartage2018ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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

            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                width:100%
                height:11in;
            }

            @media print {
                .letter {
                    font-family:Arial, sans-serif;
                    font-size:11pt;
                    width:8.5in;
                    height:11in;
                }

               #results {
                    width:8.4in;
                    font-size: 9pt;
                }
            }

            .light {
                width:0.3in;
            }

            #results {
                width:8.4in;
                font-size: 9pt;
                margin:0 auto;
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
                <img src="/images/empower/denton_cartage_logo_2018.png" style="height:60px;"  />
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


                <p><strong><?php echo $client->getName() ?></strong> is committed to supporting the health and wellbeing
                    of its employees and their families.  The first step in promoting good health is awareness.  That is
                    why Denton Cartage offers the annual health screenings through Empower Health Services.  To encourage
                    employees to take advantage of this wonderful benefit, Denton Cartage offers 4 incentives related to
                    the program. </p>

                <p>
                    Employee (families) can earn up to $1,040 for the 2018-19 plan year by meeting the goals for 4 health
                    measures noted in B (below).  Earn $5 a week for each health measure if you meet the goal for that
                    measure.  If you miss any goal, there are 2 alternative ways you can get the $5 a week.  If all 4
                    goals met, they total to $20 a week ($1,040 annually).
                </p>

                <p>
                    For employees with employee <strong>and</strong> spouse coverage - <strong>both</strong> employee
                    <strong>and</strong> spouse must meet <strong>EACH</strong> requirement in order to earn the full
                    incentive.  If one person meets a requirement goal, 50% of the incentive is earned.  If both meet
                    the goal, 100% is earned.
                </p>
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
        $screeningStatus = $status->getComplianceViewStatus('screening');

        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $alternativeQualificationFormStatus = $status->getComplianceViewStatus('alternative_qualification_form');
        $alternativeMeetDrToddStatus = $status->getComplianceViewStatus('alternative_meet_dr_todd');


        $alternativeAllThreeStatus = ComplianceStatus::NOT_COMPLIANT;
        if($alternativeMeetDrToddStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $alternativeQualificationFormStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $alternativeAllThreeStatus = ComplianceStatus::COMPLIANT;
        }


        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 320px;">A. Complete the following by June 30, 2018:</th>
                        <th colspan="2">Deadline</th>
                        <th colspan="2">Date Done</th>
                        <th>Met Goal</th>
                    </tr>

                    <tr class="status-<?php echo $screeningStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="2"><?php echo $screeningStatus->getComplianceView()->getAttribute('deadline') ?></td>
                        <td colspan="2"><?php echo $screeningStatus->getComment() ?></td>
                        <td>
                            <?php if($screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                    </tr>



                    <tr class="headerRow">
                        <th style="text-align: left;">B.  Health Measures Incentives – to earn up to $1,040 in the 2018-19 plan year:</th>
                        <th style="width: 150px;">Goal</th>
                        <th>Y1: 2017 Result</th>
                        <th>Y2: 2018 Result</th>
                        <th>Change Y2-Y1</th>
                        <th>Met Goal</th>
                    </tr>

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
                    </tr>
                    <?php endforeach ?>


                    <tr class="headerRow">
                        <th style="text-align: left;">Or – Alternative if any goal is not met, then get a OR b below done by the  deadline(s) noted:</th>
                        <th colspan="4">Goal</th>
                        <th></th>
                    </tr>

                    <tr class="status-<?php echo $alternativeQualificationFormStatus->getStatus() ?>">
                        <td style="text-align: left;" >
                           <?php echo $alternativeQualificationFormStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td colspan="4"><?php echo $alternativeQualificationFormStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                            <?php if($alternativeQualificationFormStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
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


                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}