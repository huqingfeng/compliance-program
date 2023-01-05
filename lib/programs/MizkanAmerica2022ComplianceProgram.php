<?php

use hpn\steel\query\SelectQuery;



class MizkanAmerica2022ComplianceProgram extends ComplianceProgram
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
        return new MizkanAmerica2022ComplianceProgramReportPrinter();
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
        $screeningView->setAttribute('goal', '5/31/2023');
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(3);

        $ldlView = new ComplyWithTotalLDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('A. Achieve <b>LDL Cholesterol</b> of 99 or less <b>–OR–</b> have your 2022 result be at least 15 mg/dL lower than your 2021 LDL result');
        $ldlView->overrideTestRowData(null, null, 99, null);
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->setAttribute('goal', '&le; 99');
        $ldlView->setAttribute('screening_view', true);
        $ldlView->addLink(new Link('Complete Lessons on Cholesterol', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $ldlView->setAllowNumericOnly(true);
        $this->configureViewForElearningAlternativeAndImprovement($ldlView, 'cholesterol', 'ldl', 'decrease', 15);
        $group->addComplianceView($ldlView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('B. Achieve <b>Triglycerides</b> of 150 or less <b>–OR–</b> have your 2022 result be at least 15 mg/dL lower than your 2021 Triglyceride result');
        $trigView->overrideTestRowData(null, null, 150, null);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $trigView->setAttribute('goal', '&le; 150');
        $trigView->setAttribute('screening_view', true);
        $trigView->addLink(new Link('Complete Lessons on Cholesterol', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $trigView->setAllowNumericOnly(true);
        $this->configureViewForElearningAlternativeAndImprovement($trigView, 'cholesterol', 'triglycerides', 'decrease', 15);
        $group->addComplianceView($trigView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('C. Achieve <b>Blood Glucose</b> of 99 or less <b>–OR–</b> have your 2022 result be at least 15 mg/dL lower than 2021 your Glucose result');
        $gluView->overrideTestRowData(null, null, 99, null);
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $gluView->setAttribute('goal', '&le; 99');
        $gluView->addLink(new Link('Complete Lessons on Diabetes', '/content/9420?action=lessonManager&tab_alias=diabetes'));
        $gluView->setAllowNumericOnly(true);
        $this->configureViewForElearningAlternativeAndImprovement($gluView, 'diabetes', 'glucose', 'decrease', 15);
        $group->addComplianceView($gluView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('D. Achieve <b>Body-Mass Index (BMI)</b> of 27.5 or less <b>–OR–</b> have your 2022 result be at least 3 points lower than your 2021 BMI result');
        $bmiView->overrideTestRowData(null, null, 27.5, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '&le; 27.5');
        $bmiView->setAttribute('screening_view', true);
        $bmiView->addLink(new Link('Complete Lessons on Weight Management', '/content/9420?action=lessonManager&tab_alias=body_fat'));
        $bmiView->setAllowNumericOnly(true);
        $this->configureViewForElearningAlternativeAndImprovement($bmiView, 'body_fat', 'bmi', 'decrease', 3);
        $group->addComplianceView($bmiView);


        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('E. Achieve <b>Tobacco-Free screening results</b> (Negative or No for Cotinine) ');
        $cotinineView->setName('cotinine');
        $cotinineView->emptyLinks();
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative Result or N (No)');
        $cotinineView->setAttribute('screening_view', true);
        $cotinineView->addLink(new Link('Complete Lessons on Tobacco Cessation', '/content/9420?action=lessonManager&tab_alias=tobacco_2019'));
        $cotinineView->setPostEvaluateCallback(function (ComplianceViewStatus $status, User $user) {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            $status->setComment('Test Not Taken');

            $view = $status->getComplianceView();

            $cotinineResult = $status->getAttribute('real_result');

            $startDate = date('Y-m-d', $view->getStartDate());
            $endDate = date('Y-m-d', $view->getEndDate());
            $elearningEnabled = false;


            $tobacco = SelectQuery::create()
                ->select('cigarettesmoking')
                ->from('screening')
                ->where('user_id = ?', array($user->getId()))
                ->andWhere('date BETWEEN ? and ?', array($startDate, $endDate))
                ->andWhere('cigarettesmoking IS NOT NULL')
                ->hydrateSingleScalar()
                ->execute();

            if (!empty($tobacco) && (($tobacco == 'User' || $tobacco == 'user'))) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                $status->setComment($tobacco);
                $elearningEnabled = true;
            }

            if (!empty($tobacco) && (($tobacco == 'Non-user' || $tobacco == 'non-user'))) {
                if(!empty($cotinineResult) && ($cotinineResult == 'Negative' || $cotinineResult == 'negative')) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                    $status->setComment($cotinineResult);
                } elseif (!empty($cotinineResult) && ($cotinineResult == 'Positive' || $cotinineResult == 'positive')) {
                    $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                    $status->setComment($cotinineResult);
                    $elearningEnabled = true;
                }
            }

            if ($elearningEnabled) {
                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), 'tobacco_2019');
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->useAlternateCode(true);

                $elearningStatus = $elearningView->getStatus($user);

                $numberCompleted = count($elearningStatus->getAttribute('lessons_completed', array()));

                if ($numberCompleted >= 6) {
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

            $lastStart = new \DateTime('2021-01-01');
            $lastEnd = new \DateTime('2021-12-31');


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
                    $lastVal = isset($cache['last'][$test]) && is_numeric($cache['last'][$test]) ? (float) $cache['last'][$test] : null;
                    $thisVal = isset($cache['this'][$test]) && is_numeric($cache['this'][$test]) ? (float) $cache['this'][$test] : null;
                }

                if ($thisVal && $lastVal) {
                    $change = $thisVal - $lastVal;
                    $status->setAttribute('2021_2022_change', round($change, 1));

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

                $status->setAttribute('2021_result', $lastVal);
                $status->setAttribute('2022_result', $thisVal);

                if ($isImproved) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }

            $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();

            if($viewPoints < $maxPoints && isset($cache['this'][$test]) && !empty($cache['this'][$test]) && is_numeric($cache['this'][$test])) {
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

        $noncompliantValues = array('QNS', 'TNP', "DECLINED", "TEST NOT TAKEN");
        foreach ($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if (in_array(strtoupper($viewStatus->getAttribute('real_result')), $noncompliantValues)) {
                $viewStatus->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
            }
        }

        if($coreGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $healthGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT){
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

    }
}

class MizkanAmerica2022ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if (trim(strtolower($user->miscellaneous_data_1)) == 'noletter')
            return;

        ?>

        <style type="text/css">
            html {
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content h1,
            #page #content h1 {
                margin-top: 2rem;
                padding: 0.5rem;
                background-color: #6fb001;
                color: white;
                text-align: center;
                font-family: Roboto;
                font-size: 1.75rem;
                font-weight: bold;
                border-radius: 0.25rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content aside,
            #page #content aside {
                margin-top: 2rem;
                padding: 1.5rem;
                background-color: #f8fafd;
                border: 1px solid #d1d9e8;
                border-radius: 0.25rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
                color: #263b4f;
            }

            #page #wms3-content .warning,
            #page #content .warning {
                position: relative;
                margin-bottom: 1rem;
                padding-left: 5rem;
                color: #E53935;
            }

            #page #wms3-content .warning i,
            #page #content .warning i {
                background-color: transparent !important;
                text-align: center;
                margin-top: -0.95rem;
                font-size: 1.25rem;
            }

            #page #wms3-content .warning i,
            #page #wms3-content q,
            #page #content .warning i,
            #page #content q {
                position: absolute;
                top: 50%;
                left: 2rem;
            }

            #page #wms3-content q,
            #page #content q {
                margin-top: -1.2rem;
                background-color: #ffb65e;
                text-align: left;
            }

            #page #wms3-content q:before,
            #page #wms3-content q:after,
            #page #content q:before,
            #page #content q:after {
                content: '';
                position: absolute;
                background-color: inherit;
            }

            #page #wms3-content q,
            #page #wms3-content q:before,
            #page #wms3-content q:after,
            #page #content q,
            #page #content q:before,
            #page #content q:after {
                display: inline-block;
                width: 1.5rem;
                height: 1.5rem;
                border-radius: 0;
                border-top-right-radius: 30%;
            }

            #page #wms3-content q,
            #page #content q {
                transform: rotate(-60deg) skewX(-30deg) scale(1, .866);
            }

            #page #wms3-content q:before,
            #page #content q:before {
                transform: rotate(-135deg) skewX(-45deg) scale(1.414, .707) translate(0, -50%);
            }

            #page #wms3-content q:after,
            #page #content q:after {
                transform: rotate(135deg) skewY(-45deg) scale(.707, 1.414) translate(50%);
            }

            #page #wms3-content table,
            #page #content table {
                border-collapse: separate;
                margin-bottom: 2rem;
                width: 100%;
                line-height: 1.5rem;
                font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
            }

            #page #wms3-content table + table,
            #page #content table + table {
                margin-top: 1rem;
            }

            #page #wms3-content th,
            #page #content th {
                padding: 1rem;
                background-color: #014265;
                color: white;
                border: 1px solid #014265;
                font-weight: bold;
                text-align: center;
            }

            #page #wms3-content tr:first-of-type th:first-of-type,
            #page #content tr:first-of-type th:first-of-type {
                border-top-left-radius: 0.25rem;
                text-align: left;
            }

            #page #wms3-content tr:first-of-type th:last-of-type,
            #page #content tr:first-of-type th:last-of-type {
                border-top-right-radius: 0.25rem;
            }

            #page #wms3-content td,
            #page #content td {
                padding: 1rem;
                color: #57636e;
                border-left: 1px solid #e8e8e8;
                border-bottom: 1px solid #e8e8e8;
                text-align: center;
            }

            #page #wms3-content tr.compliant td,
            #page #content tr.compliant td {
                background-color: #90FF8C !important;
            }

            #page #wms3-content tr.not_compliant td,
            #page #content tr.not_compliant td {
                background-color: #DEDEDE !important;
            }

            #page #wms3-content tr:last-of-type td:first-of-type,
            #page #content tr:last-of-type td:first-of-type {
                border-bottom-left-radius: 0.25rem;
            }

            #page #wms3-content td:last-of-type,
            #page #content td:last-of-type {
                border-right: 1px solid #e8e8e8;
            }

            #page #wms3-content tr:last-of-type td:last-of-type,
            #page #content tr:last-of-type td:last-of-type {
                border-bottom-right-radius: 0.25rem;
            }

            #page #wms3-content a,
            #page #content a {
                display: inline-block;
                color: #0085f4 !important;
                text-decoration: none !important;
            }

            #page #wms3-content a:hover,
            #page #wms3-content a:focus,
            #page #wms3-content a:active,
            #page #content a:hover,
            #page #content a:focus,
            #page #content a:active {
                color: #0052C1 !important;
                text-decoration: none !important;
            }

            #page #wms3-content i,
            #page #content i {
                width: 1.5rem;
                height: 1.5rem;
                line-height: 1.5rem;
                background-color: #ced2db;
                border-radius: 999px;
                color: white;
                font-size: 1.25rem;
            }

            #page #wms3-content i.fa-check,
            #page #content i.fa-check {
                background-color: #4fd3c2;
            }

            #page #wms3-content i.fa-exclamation,
            #page #content i.fa-exclamation {
                background-color: #ffb65e;
            }

            #page #wms3-content i.fa-times,
            #page #content i.fa-times {
                background-color: #dd7370;
            }

            #page #wms3-content .split,
            #page #content .split {
                display: flex;
                justify-content: space-between;
            }

            @media only screen and (max-width: 1060px) {
                #page #wms3-content table,
                #page #content table {
                    table-layout: auto;
                }
            }
        </style>

        <div class="split">
            <p style="margin: 0;">
                <img src="/images/empower/ehs_logo.jpg" style="height: 50px;"/> <br/>
                4205 Westbrook Drivec <br/>
                Aurora, IL 60504
            </p>
            <img src="/images/empower/mizkanamerica_log_2019.jpg" style="height: 50px;"/>
        </div>

        <p style="margin-left: 0.75in; padding-top: 0.56in; clear: both;">
            <?= $user->getFullName() ?> <br/>
            <?= $user->getFullAddress("<br/>") ?>
        </p>

        <aside>
            <div class="split">
                <p><b>Dear <?= $user->first_name ?>,</b></p>
                <p><b><?= date("m/d/Y") ?></b></p>
            </div>

            <p>
                Thank you for participating in the 2022 Mizkan America Wellness
                Screening. In partnership with Empower Health Services (EHS), your
                employer has selected five "Healthy Standards" for you to strive to
                achieve.
            </p>
            <p>
                Each of the criteria has a financial incentive of $16.80 per pay
                period (an annual value of $1,008 collectively) linked to scoring in
                the goal range. If your spouse/domestic partner is covered under the
                medical plan with MA, the credits earned will be divided between you
                and your spouse.
            </p>
        </aside>

        <h1>2022 Wellness-Screening Results</h1>

        <p>
            The chart below displays your results and indicates if you have earned
            the incentive for each criteria.
        </p>

        <p>
            If you did NOT earn the full incentive based on the screening results
            listed below, <b>you may still earn the credit by completing <u>six
                    E-Learning lessons for each health standard</u> where you did not meet
                the healthy target.</b>
        </p>

        <p>
        <ul>
            <li>
                You can access the E-Learning lessons by clicking on the links in
                the last column of the chart below
            </li>
            <li>
                If you are receiving this notice in hard-copy format, the link to
                access the E-Learning lessons is
                <a href="http://www.empowerhealthservices.hpn.com">www.empowerhealthservices.hpn.com
                </a>
            </li>
            <li>Simply log in and click on the Wellness Rewards Program tile</li>
            <li>You will see this page and the links to E-Learning lessons below</li>
            <li>
                The Wellness Credits earned during the 2022 Wellness Screening will be
                effective on 1/1/2023
            </li>
        </ul>
        </p>

        <aside class="warning">
            <q></q>
            <i class="fas fa-exclamation"></i>
            For existing employees/spouses - The deadline for completing the e-learning lessons in order to retain your wellness credits for the 2023 plan year was Wednesday, August 31, 2022. <br /><br />
            For new hires - the deadline for completing the e-learning lessons is 45 days from your date of hire.
        </aside>

        <?= $this->getTable($status) ?>

        <p style="text-align:center;font-size:10pt;">
            <b>
                Thank you again for your participation in the 2022 Mizkan America
                Wellness Screening. Should you have any questions, please contact EHS
                by calling <a href="tel:8663676974">866-367-6974</a> or via email at
                <a href="mailto:support@empowerhealthservices.com">support@empowerhealthservices.com</a>.
            </b>
        </p>

        <?php
    }

    private function getTable($status)
    {
        ob_start();

        ?>

        <table>
            <tbody>
            <tr>
                <th colspan="4">1. Annual Wellness Screening</th>
                <th colspan="2">Incentive Goal</th>
                <th>Your Result</th>
                <th>Goal Met</th>
                <th>Links</th>
            </tr>

            <?php
            $viewStatus = $status->getComplianceViewStatus('complete_screening');
            ?>

            <tr class="<?= $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'compliant' : 'not_compliant' ?>">
                <td colspan="4" style="text-align:left;">
                    Complete the 2022 Mizkan Wellness Screening
                    <br/>
                    <span style="font-size:10pt;">
                (on-site or at an approved lab)
              </span>
                </td>
                <td colspan="2">
                    Complete Screening by <?= $viewStatus->getComplianceView()->getAttribute('goal') ?>
                </td>
                <td>Completed Screening on <?= $viewStatus->getComment() ?></td>
                <td>
                    <?= $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                </td>
                <td>
                    <?php
                    foreach ($viewStatus->getComplianceView()->getLinks() as $link)
                        echo $link->getHTML() . '<br />';
                    ?>
                </td>
            </tr>

            <tr>
                <th colspan="4">1. Healthy Standards Targeted &amp; Incentive Goal</th>
                <th>Y1: 2021 Result</th>
                <th>Y2: 2022 Result</th>
                <th>Change Y2-Y1</th>
                <th>Goal Met</th>
                <th>Links if Needed or Interested</th>
            </tr>

            <?php foreach ($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus): ?>
                <tr class="<?= $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'compliant' : 'not_compliant' ?>">
                    <td colspan="4" style="text-align:left;">
                        <?= $viewStatus->getComplianceView()->getReportName() ?>
                    </td>
                    <td><?= $viewStatus->getAttribute('2021_result') ?></td>
                    <?php if ($viewStatus->getComplianceView()->getName() == 'cotinine'): ?>
                        <td><?= $viewStatus->getComment() ?></td>
                    <?php else: ?>
                        <td><?= $viewStatus->getAttribute('2022_result') ?></td>
                    <?php endif; ?>
                    <td><?= $viewStatus->getAttribute('2021_2022_change') ?></td>
                    <td>
                        <?= $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                    </td>
                    <td>
                        <?php
                        foreach ($viewStatus->getComplianceView()->getLinks() as $link)
                            echo $link->getHTML() . '<br />';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php

        return ob_get_clean();
    }
}

?>
