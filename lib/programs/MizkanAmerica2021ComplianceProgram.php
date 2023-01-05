<?php
use hpn\steel\query\SelectQuery;


class MizkanAmericaLearningAlternativeComplianceView extends ComplianceView
{
    public function __construct($programStart, $programEnd, $alias)
    {
        $this->start = $programStart;
        $this->end = $programEnd;
        $this->alias = $alias;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'incredible_technologies_alt_'.$this->alias;
    }

    public function getDefaultReportName()
    {
        return 'Complete eLearning '.$this->alias;
    }

    public function getStatus(User $user)
    {
        $screeningView = new CompleteScreeningComplianceView($this->start, $this->end);
        $screeningView->setComplianceViewGroup($this->getComplianceViewGroup());

        if($screeningView->getStatus($user)->isCompliant()) {
            $elearningView = new CompleteELearningGroupSet($this->start, $this->end, $this->alias);
            $elearningView->setComplianceViewGroup($this->getComplianceViewGroup());
            $elearningView->setNumberRequired(1);

            if($elearningView->getStatus($user)->isCompliant()) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Elearning Lesson Completed');
            }
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
    }

    protected $alias;
    protected $start;
    protected $end;
}

class MizkanAmerica2021ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->setShowUserFields(null, null, null, false, true, null, null, null, true);

        $printer->addCallbackField('member_id', function (User $user) {
            return $user->member_id;
        });

        $printer->addCallbackField('date_of_birth', function (User $user) {
            return $user->date_of_birth;
        });

        $printer->addCallbackField('ADP File #', function (User $user) {
            return $user->getDepartment();
        });

        $printer->addCallbackField('Expires', function (User $user) {
            return $user->expires;
        });

        $printer->addCallbackField('Termination Date', function (User $user) {
            return $user->terminationdate;
        });

        return $printer;
    }


    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MizkanAmerica2021ComplianceProgramReportPrinter();
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

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('1. Complete the wellness screening');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Take the Health Risk Assessment', '/content/989'));
        $screeningView->addLink(new Link('View Full Screening Report', '/content/989'));
        $screeningView->setAttribute('goal', '8/31/2021');
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(3);

        $ldlView = new ComplyWithTotalLDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('A. Achieve <strong>LDL Cholesterol</strong> of 99 or less <strong>–OR–</strong> have your 2021 result be at least 15 mg/dL lower than your 2020 LDL result');
        $ldlView->overrideTestRowData(null, null, 99, null);
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->setAttribute('goal', '&le; 99');
        $ldlView->setAttribute('screening_view', true);
        $ldlView->addLink(new Link('Complete Lessons on Cholesterol', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $this->configureViewForElearningAlternativeAndImprovement($ldlView, 'cholesterol', 'ldl', 'decrease', 15);
        $group->addComplianceView($ldlView);


        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('B. Achieve <strong>Triglycerides</strong> of 150 or less <strong>–OR–</strong> have your 2021 result be at least 15 mg/dL lower than your 2020 Triglyceride result');
        $trigView->overrideTestRowData(null, null, 150, null);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0 ,0));
        $trigView->setAttribute('goal', '&le; 150');
        $trigView->setAttribute('screening_view', true);
        $trigView->addLink(new Link('Complete Lessons on Cholesterol', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $this->configureViewForElearningAlternativeAndImprovement($trigView, 'cholesterol', 'triglycerides', 'decrease', 15);
        $group->addComplianceView($trigView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('C. Achieve <strong>Blood Glucose</strong> of 99 or less <strong>–OR–</strong> have your 2021 result be at least 15 mg/dL lower than 2020 your Glucose result');
        $gluView->overrideTestRowData(null, null, 99, null);
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $gluView->setAttribute('goal', '&le; 99');
        $gluView->addLink(new Link('Complete Lessons on Diabetes', '/content/9420?action=lessonManager&tab_alias=diabetes'));
        $this->configureViewForElearningAlternativeAndImprovement($gluView, 'diabetes', 'glucose', 'decrease', 15);
        $group->addComplianceView($gluView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('D. Achieve <strong>Body-Mass Index (BMI)</strong> of 27.5 or less <strong>–OR–</strong> have your 2021 result be at least 3 points lower than your 2020 BMI result');
        $bmiView->overrideTestRowData(null, null, 27.5, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '&le; 27.5');
        $bmiView->setAttribute('screening_view', true);
        $bmiView->addLink(new Link('Complete Lessons on Weight Management', '/content/9420?action=lessonManager&tab_alias=body_fat'));
        $this->configureViewForElearningAlternativeAndImprovement($bmiView, 'body_fat', 'bmi', 'decrease', 3);
        $group->addComplianceView($bmiView);


        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('E. Achieve <strong>Tobacco-Free screening results</strong> (Negative or No for Cotinine) ');
        $cotinineView->setName('cotinine');
        $cotinineView->emptyLinks();
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative Result or N (No)');
        $cotinineView->setAttribute('screening_view', true);
        $cotinineView->addLink(new Link('Complete Lessons on Tobacco Cessation', '/content/9420?action=lessonManager&tab_alias=tobacco_2019'));
        $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $view = $status->getComplianceView();

            $startDate = date('Y-m-d', $view->getStartDate());
            $endDate = date('Y-m-d', $view->getEndDate());
            $elearningEnabled = false;

            if($status->getAttribute('real_result') == 'TNP' || $status->getAttribute('real_result') == 'QNS') {
                $status->setComment('Test Not Taken');
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }

            if ($status->getStatus() != ComplianceStatus::COMPLIANT && ($status->getComment() == "No Screening" || $status->getComment() == "Test Not Taken")) {
                $tobacco = SelectQuery::create()
                ->select('cigarettesmoking')
                ->from('screening')
                ->where('user_id = ?', array($user->getId()))
                ->andWhere('date BETWEEN ? and ?', array($startDate, $endDate))
                ->andWhere('cigarettesmoking IS NOT NULL')
                ->hydrateSingleScalar()
                ->execute();

                if(!empty($tobacco)) {
                    if($tobacco == 'User' || $tobacco == 'user') {
                       $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                       $status->setComment($tobacco);
                       $elearningEnabled = true;
                    }
                }
            }

            if(($status->getStatus() == ComplianceStatus::NOT_COMPLIANT && $status->getComment() != "No Screening" && $status->getComment() != "Test Not Taken") || $elearningEnabled) {
                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), 'tobacco_2019');
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->useAlternateCode(true);

                $elearningStatus = $elearningView->getStatus($user);

                $numberCompleted = count($elearningStatus->getAttribute('lessons_completed', array()));

                if($numberCompleted >= 6) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                    $status->setComment('eLearning Alternative Applied');
                }
            }


        });
        $group->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($group);
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

                if($numberCompleted >= 6) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        if($coreGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $healthGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT){
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
    }
}

class MizkanAmerica2021ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css">
            .headerRow {
                background-color:#88b2f6;
                font-weight:bold;
                font-size:10pt;
                height:46px;
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
                height:13in;
                clear: both;
                margin-bottom: 15px;
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

            #not_compliant_notes p{
                margin: 3px 0;
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
                    4205 Westbrook Drive<br />
                    Aurora, IL 60504
                </div>

                <div style="float: right;">
                    <img src="/images/empower/mizkanamerica_log_2019.jpg" style="height:80px;"  />
                </div>
            </p>

            <p style="margin-left:0.75in; padding-top:.56in; clear: both;">
                <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>
                <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
            </p>


            <p>
                Thank you for participating in the 2021 Mizkan America Wellness Screening. In partnership with Empower
                 Health Services (EHS), your employer has selected five "Healthy Standards" for you to strive to achieve.
            </p>

            <p>
                Each of the criteria has a financial incentive of $16.80 per pay period (an annual value of $1,008 collectively)
                linked to scoring in the goal range.  If your spouse/domestic partner is covered under the medical plan with
                MA, the credits earned will be divided between you and your spouse.
            </p>

            <p style="font-weight: bold;">2021 Wellness-Screening Results</p>

            <p>The chart below displays your results and indicates if you have earned the incentive for each criteria.</p>

            <p>
                If you did NOT earn the full incentive based on the screening results listed below,
                <span style="font-weight: bold;">you may still earn the credit by completing
                <span style="text-decoration: underline;">six E-Learning lessons for each health standard</span>
                 where you did not meet the healthy target.</span>
            </p>

            <p>
                <ul>
                    <li>You can access the E-Learning lessons by clicking on the links in the last column of the chart below</li>
                    <li>If you are receiving this notice in hard-copy format, the link to access the E-Learning lessons
                     is <a href="http://www.empowerhealthservices.hpn.com">www.empowerhealthservices.hpn.com</a></li>
                    <li>Simply log in and click on the Wellness Rewards Program tile</li>
                    <li>You will see this page and the links to E-Learning lessons below</li>
                    <li>The Wellness Credits earned during the 2021 Wellness Screening will be effective on 1/1/2022</li>
                </ul>
            </p>

            <p style="font-weight: bold; color:red; text-align: center;text-decoration:underline">
                For existing employees/spouses - The deadline for completing the e-learning lessons in order to retain your wellness credits for the 2022 plan year was Tuesday, August 31, 2021.<br /><br />

                For new hires - the deadline for completing the e-learning lessons is 45 days from your date of hire.
            </p>

            <?php echo $this->getTable($status) ?>

            <p style="font-weight: bold; text-align: center; font-size:10pt;">
                Thank you again for your participation in the 2021 Mizkan America Wellness Screening.  Should you have any
                questions, please contact EHS by calling 866-367-6974 or via email at <a href="mailto:support@empowerhealthservices.com">support@empowerhealthservices.com</a>.
            </p>
        </div>



        <?php
    }

    private function getTable($status)
    {
        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 260px;">1. Annual Wellness Screening</th>
                        <th colspan="2">Incentive Goal</th>
                        <th>Your Result</th>
                        <th>Goal Met</th>
                        <th>Links</th>
                    </tr>

                    <?php $viewStatus = $status->getComplianceViewStatus('complete_screening') ?>
                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td style="text-align: left; padding-left: 10px;">Complete the 2021 Mizkan Wellness Screening <br /> <span style="font-size:10pt;">(on-site or at an approved lab)</span></td>
                        <td colspan="2">Complete Screening by <br /><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>Completed Screening on <br /><?php echo $viewStatus->getComment() ?></td>
                        <td><?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td>
                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left;">2. Healthy Standards Targeted <br/> & Incentive Goal</th>
                        <th>Y1: 2020 Result</th>
                        <th>Y2: 2021 Result</th>
                        <th>Change Y2-Y1</th>
                        <th>Goal Met</th>
                        <th>Links if Needed or Interested</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left; padding-left: 10px;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getAttribute('2020_result') ?></td>
                            <?php if($viewStatus->getComplianceView()->getName() == 'cotinine') : ?>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <?php else: ?>
                            <td><?php echo $viewStatus->getAttribute('2021_result') ?></td>
                            <?php endif ?>
                            <td><?php echo $viewStatus->getAttribute('2020_2021_change') ?></td>
                            <td>
                                <?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                            </td>
                            <td>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}