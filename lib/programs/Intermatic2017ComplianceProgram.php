<?php

use \hpn\steel\query\SelectQuery;

class IntermaticLearningAlternativeComplianceView extends ComplianceView
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
        return 'intermatic_alt_'.$this->alias;
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

class Intermatic2017ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->addEndStatusFieldCallBack('Compliance Program - Compliant', function(ComplianceProgramStatus $status) {
            $numberCompliant = 0;
            $measureGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

            foreach($measureGroupStatus->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();

                if($view->getAttribute('screening_view')) {
                    if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                        // Alternative wasn't executed, so original_status is null. View still compliant

                        $numberCompliant++;
                    }
                }
            }

            if($numberCompliant >= 4) {
                return 'Yes';
            } else {
                return 'No';
            }
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Intermatic2017ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(5);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('BMI');
        $bmiView->setName('bmi');
        $bmiView->overrideTestRowData(0, 0, 26.99, null);
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 26.9');
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('screening_view', true);
        $bmiView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $view = $status->getComplianceView();

            $hra = SelectQuery::create()
                ->select('height_text, weight_text')
                ->from('hra')
                ->where('user_id = ?', array($user->id))
                ->andWhere('date BETWEEN ? AND ?', array($view->getStartDate('Y-m-d'), $view->getEndDate('Y-m-d')))
                ->andWhere('height_text IS NOT NULL AND weight_text IS NOT NULL')
                ->orderBy('date DESC')
                ->hydrateSingleRow()
                ->execute();

            if(!$status->getAttribute('has_result')) {
                if(!empty($hra['weight_text']) && $hra['height_text']) {
                    $bmi = round(($hra['weight_text'] * 703) / ($hra['height_text'] * $hra['height_text']), 2);

                    $status->setComment($bmi);

                    if($bmi <= 26.99) {
                        $status->setStatus(ComplianceViewStatus::COMPLIANT);
                    }
                }
            }

        });
        $group->addComplianceView($bmiView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('Fasting Glucose');
        $gluView->setName('glucose');
        $gluView->overrideTestRowData(null, null, 99, null);
        $gluView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 99');
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $group->addComplianceView($gluView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('LDL');
        $ldlView->setName('ldl');
        $ldlView->overrideTestRowData(0, 0, 129, null);
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 129');
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->setAttribute('screening_view', true);
        $group->addComplianceView($ldlView);

        $triView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triView->setName('triglycerides');
        $triView->overrideTestRowData(null, null, 149, null);
        $triView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 149');
        $triView->emptyLinks();
        $triView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triView->setAttribute('screening_view', true);
        $group->addComplianceView($triView);

        $hraScore = new HraScoreComplianceView($programStart, $programEnd, 65);
        $hraScore->setReportName('HPA Score');
        $hraScore->setName('hra_score');
        $hraScore->emptyLinks();
        $hraScore->setStatusSummary(ComplianceStatus::COMPLIANT, '65 - 100');
        $hraScore->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hraScore->setAttribute('screening_view', true);
        $hraScore->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $thisYearResult = $status->getComment();

            $view = $status->getComplianceView();

            $lastHraScore = new HraScoreComplianceView('2016-04-14', '2016-12-31', 65);
            $lastHraScore->setComplianceViewGroup($view->getComplianceViewGroup());
            $lastHraScore->setName('alternative_'.$lastHraScore->getName());

            $lastHraScoreStatus = $lastHraScore->getStatus($user);
            $lastYearResult = $lastHraScoreStatus->getComment();

            if($thisYearResult && $lastYearResult) {
                $isImproved = true;

                if ($lastYearResult * 0.90 < $thisYearResult) {
                    $isImproved = false;
                }

                if ($isImproved) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });
        $group->addComplianceView($hraScore);

        $this->addComplianceViewGroup($group);

        $cotinineGroup = new ComplianceViewGroup('cotinine');

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('Cotinine');
        $cotinineView->setName('cotinine');
        $cotinineView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Pass');
        $cotinineView->overrideTestRowData(null, null, 8, null);
        $cotinineView->emptyLinks();
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('screening_view', false);
        $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setComment('Pass');
            } else {
                $status->setComment('Fail');
            }
        });
        $cotinineGroup->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($cotinineGroup);

    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        $programStart = new \DateTime('@'.$this->getStartDate());
        $programEnd = new \DateTime('@'.$this->getEndDate());

        $lastStart = new \DateTime('2016-04-14');
        $lastEnd = new \DateTime('2016-12-31');

        $screeningData = array(
            'user_id' => $user->id,
            'this' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $programStart, $programEnd),
            'last' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $lastStart, $lastEnd)
        );

        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $this->checkImprovement($healthGroupStatus, $screeningData);

    }

    private function checkImprovement($groupStatus, $screeningData)
    {
        foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
            $viewName = $viewStatus->getComplianceView()->getName();
            if($viewName == 'hra_score') continue;

            if ($screeningData['this'] && $screeningData['last']) {
                $isImproved = true;

                $lastVal = isset($screeningData['last'][0][$viewName]) ? (float) $screeningData['last'][0][$viewName] : null;
                $thisVal = isset($screeningData['this'][0][$viewName]) ? (float) $screeningData['this'][0][$viewName] : null;


                if (!$thisVal || !$lastVal || $lastVal * 0.90 < $thisVal) {
                    $isImproved = false;
                }

                if ($isImproved) {
                    $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        }
    }

}

class Intermatic2017ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $measureGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $conitineGroupStatus = $status->getComplianceViewGroupStatus('cotinine');

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
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

            #results th {
                background-color:#FFFFFF;
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

            <p style="text-align:center;font-size:18pt;font-weight:bold;">Healthy Measures</p>

            <p style="margin-top:0.5in; margin-left:0.75in;">
                <br/> <br/> <br/> <br/> <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>Dear <?php echo $user->first_name ?>,</p>

            <p>Thank you for participating in the 2017 Wellness Screening. In partnership with Health Maintenance
             Institute, Intermatic has selected five "Healthy Measures" for you to strive to achieve.</p>

            <p>As communicated, a medical premium discount will be offered to those employees who achieve at least 4
                out of the 5 "Healthy Measures" below OR achieve a 10% improvement in any two of the "Healthy Measures"
                from your 2016 screening. If you do not meet the requirements, you may still qualify for the incentive by
                having your doctor complete and sign the Alternate Qualification Form and fax it to Health Maintenance
                Institute at 847-635-0038. If you do not successfully complete either of these options, you will not be
                eligible for the employee medical premium discount this year.</p>

            <p>The results of your cotinine test can also impact your premiums and can be found at the bottom of this page.</p>

            <?php echo $this->getTable($measureGroupStatus) ?>

            <?php if($this->getNumCompliant($status) >= 4) : ?>
                <p class="bund">RESULTS:</p>

                <p>CONGRATULATIONS! Based on your results of the 2017 wellness screening, you either fall within the
                    acceptable range for at least four "Healthy Measures" OR achieved a 10% improvement in at least two
                    of the "Healthy Measures". Therefore you qualify for the medical premium discount. No further action
                    is required on your part.</p>

                <p>If you have any questions, please contact <a href="mailto:allison@hmihealth.com">allison@hmihealth.com</a> or call 847-635-6580.</p>
            <?php else : ?>
                <div id="not_compliant_notes">
                    <p class="bund">RESULTS:</p>

                    <p>Based on your results of the 2017 wellness screening, you <span class="bund">DO NOT</span> fall within
                        the acceptable range for at least four "Healthy Measures" and you did not achieve a 10% improvement
                        in any two of the "Healthy Measures", therefore, you do not qualify for the medical premium discount.
                        Please see "Alternate Process" steps below in order to still try and qualify for the discount.</p>

                    <p class="bund">ALTERNATE PROCESS:</p>

                    <p>In order to receive the discount, we <strong>must</strong> have documentation from your Physician acknowledging
                        the "Healthy Measure" areas of concern. This form (located on the back) will need to be completed by
                        your physician, and returned to Health Maintenance Institute. If your completed form is not received
                        by the deadline, you will not qualify for the discount.</p>

                    <p>
                        If you have any questions, please contact <a href="mailto:allison@hmihealth.com">allison@hmihealth.com</a> or call 847-635-6580.
                    </p>

                    <div style="margin: 0 auto; width: 80%;">
                        <div style="float: left; width: 38%;">
                            <div style="float: left; width: 38%;">
                                Fax Number:
                            </div>
                            <div style="float: right; width: 61%;">
                                847-635-0038 <br />
                                Attn: Allison Osoba
                            </div>
                        </div>
                        <div style="float: right; width: 50%;">
                            <div style="float: left; width: 38%;">
                                Mailing Address:
                            </div>

                            <div style="float: right; width: 61%;">
                                Health Maintenance Institute 2604 E. Dempster Street,
                                Suite 301, Park Ridge, IL 60068.
                            </div>

                        </div>
                    </div>
                   <div style="clear:both"></div>
                </div>
            <?php endif ?>

            <?php echo $this->getTable($conitineGroupStatus) ?>

            <p>If you have failed the cotinine test, please see HR on how to receive your incentive through an alternate way.</p>


        </div>

        <?php
    }

    private function getTable($groupStatus)
    {
        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <thead>
                    <tr>
                        <th>Health Measure</th>
                        <th>Acceptable Range</th>
                        <th><?php echo $groupStatus->getComplianceViewGroup()->getName() != 'cotinine' ? 'Within Range OR 10% Improvement' : 'Your Result' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $viewStatus->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT) ?></td>
                        <td class="your-result">
                            <?php if($viewStatus->getComplianceView()->getName() != 'cotinine') : ?>
                                <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                            <?php endif ?>
                            <?php echo $viewStatus->getComment() ?>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }


    /**
     * To show the pass table, a user has to be compliant for the program, and be compliant
     * without considering elearning lessons.
     */
    private function getNumCompliant($status)
    {
        $numberCompliant = 0;

        $measureGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        foreach($measureGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();

            if($view->getAttribute('screening_view')) {
                if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                    // Alternative wasn't executed, so original_status is null. View still compliant

                    $numberCompliant++;
                }
            }
        }

        return $numberCompliant;
    }

}