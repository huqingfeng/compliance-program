<?php

use hpn\steel\query\SelectQuery;



class FiveStar2021CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter(),
                'required_fields'  => array('systolic', 'diastolic', 'triglycerides', 'hdl', 'bodyfat', 'cholesterol', 'glucose')
            )
        );

        return $data;
    }
}

class FiveStar2021ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new FiveStar2021WMS2Printer();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserFields(true, true, false, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);

        $printer->addCallbackField('hiredate', function(User $user) {
            return $user->getHiredate();
        });

        $printer->addCallbackField('location', function(User $user) {
            return $user->getLocation();
        });

        $printer->addCallbackField('employee_ssn', function (User $user) {
            if($user->getRelationshipType() == 2) {
                return $user->getRelationshipUser() ? $user->getRelationshipUser()->getSocialSecurityNumber() : '';
            } else {
                return $user->getSocialSecurityNumber();
            }
        });

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = array();

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($groupStatus->getComplianceViewGroup()->getName() == 'Requirements') {
                        $data[sprintf('%s Result', $viewName)] = $viewStatus->getComment();
                    } elseif($groupStatus->getComplianceViewGroup()->getName() == 'Prevention Event') {
                        $data[sprintf('%s Comment', $viewName)] = $viewStatus->getComment();
                    }

                    $data[sprintf('%s Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                }
            }

            $data['Total Points'] = $status->getPoints();
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }



    protected function getPhoneConsultShowed($user)
    {
        $row = SelectQuery::create()
            ->select('a.date, at.showed')
            ->from('appointments a')
            ->innerJoin('appointment_times at')
            ->on('at.appointmentid = a.id')
            ->where('at.user_id = ?', array($user->getID()))
            ->andWhere('a.typeid = 21')
            ->andWhere('a.date BETWEEN ? AND ?', array('2019-01-01', '2019-12-31'))
            ->andWhere('at.showed = 1')
            ->hydrateSingleRow()
            ->groupBy('at.user_id')
            ->execute();

        $phoneConsultShowed = '';
        if(isset($row['date']) && isset($row['showed']) && $row['showed']) {
            $phoneConsultShowed .= 'Yes';
            $phoneConsultShowed .= " ({$row['date']})";
        }

        return $phoneConsultShowed;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        date_default_timezone_set('America/New_York');

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();


        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');

        $hpa = new CompleteHRAComplianceView($programStart, $programEnd);
        $hpa->setReportName('Health Risk Assessment (HRA)');
        $hpa->setName('hra');
        $hpa->emptyLinks();
        $preventionEventGroup->addComplianceView($hpa);

        $scr = new CompleteScreeningComplianceView($programStart, $programEnd);
        $scr->setReportName('Screening Program');
        $scr->setName('screening');
        $scr->emptyLinks();
        $preventionEventGroup->addComplianceView($scr);


        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements');

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setIndicateSelfReportedResults(false);
        $bmiView->overrideTestRowData(0, 0, 29.999, 29.999);
        $bmiView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bmiView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥30');
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤29.9');
        $biometricsGroup->addComplianceView($bmiView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setIndicateSelfReportedResults(false);
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 135, 135);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 86, 86);
        $biometricsGroup->addComplianceView($bloodPressureView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setIndicateSelfReportedResults(false);
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $glucoseView->overrideTestRowData(0, 0, 108, 108);
        $biometricsGroup->addComplianceView($glucoseView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setIndicateSelfReportedResults(false);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');
        $totalHDLRatioView->overrideTestRowData(0, 0, 5, 5);
        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setName('tobacco');
        $smokingView->setReportName('Tobacco');
        $biometricsGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($biometricsGroup);


        $alternativeGroup = new ComplianceViewGroup('Alternatives');

        $elearningView = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearningView->setReportName('eLearning Alternatives');
        $elearningView->setName('alternatives');
        $elearningView->setNumberRequired(5);
        $elearningView->emptyLinks();
        $elearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $alternativeGroup->addComplianceView($elearningView);

        $this->addComplianceViewGroup($alternativeGroup);



        $forceOverrideGroup = new ComplianceViewGroup('Force Override');

        // Used for override to force compliant
        $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $forceCompliant->setName('force_compliant');
        $forceCompliant->setReportName('Force Overall Compliant');
        $forceOverrideGroup->addComplianceView($forceCompliant);


        $this->addComplianceViewGroup($forceOverrideGroup);
    }


    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        $thisPrevention = $status->getComplianceViewGroupStatus('Prevention Event');
        $thisRequirements = $status->getComplianceViewGroupStatus('Requirements');

        $numCompliant = 0;
        foreach($thisRequirements->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $numCompliant++;
            }
        }

        if($numCompliant >= 3) {
            $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);
        }

        if($thisPrevention->getStatus() == ComplianceStatus::COMPLIANT && $thisRequirements->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } elseif($status->getComplianceViewStatus('force_compliant')->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

        $appt = SelectQuery::create()
            ->select('at.id')
            ->from('appointment_times at')
            ->innerJoin('appointments a')
            ->on('a.id = at.appointmentid')
            ->where('a.date BETWEEN ? AND ?', array('2021-06-01', '2022-05-31'))
            ->andWhere('a.typeid IN (11, 21)')
            ->andWhere('at.user_id = ?', array($user->id))
            ->andWhere('at.showed = 1')
            ->hydrateSingleScalar()
            ->execute();

        if($appt) {
            $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }


    }

    public function useParallelReport()
    {
        return false;
    }


}


class FiveStar2021WMS2Printer implements ComplianceProgramReportPrinter
{
    public function getStatusMappings(ComplianceView $view)
    {
        if($view->getName() == 'waist_bmi') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                3 => ComplianceStatus::PARTIALLY_COMPLIANT,
                2 => ComplianceStatus::NOT_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT,
                0 => ComplianceStatus::NOT_COMPLIANT
            );
        } elseif($view->getName() == 'tobacco') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT
            );
        } else {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                2 => ComplianceStatus::PARTIALLY_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT
            );
        }
    }

    public function getClass(ComplianceView $view)
    {
        if($view->getName() == 'waist_bmi') {
            return array(
                4 => 'success',
                3 => 'warning',
                2 => 'danger',
                1 => 'danger',
                0 => 'danger'
            );
        } elseif($view->getName() == 'tobacco') {
            return array(
                ComplianceStatus::COMPLIANT => 'success',
                ComplianceStatus::NOT_COMPLIANT => 'danger'
            );
        } else {
            return array(
                ComplianceStatus::COMPLIANT => 'success',
                ComplianceStatus::PARTIALLY_COMPLIANT => 'warning',
                ComplianceStatus::NOT_COMPLIANT => 'danger'
            );
        }
    }


    public function printReport(ComplianceProgramStatus $status)
    {
        $escaper = new hpn\common\text\Escaper;

        $requirementsStatus = $status->getComplianceViewGroupStatus('Requirements');

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };


        $that = $this;

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor, $that) {

            ob_start();

            if ($group->getComplianceViewGroup()->getName() == 'Requirements') : ?>
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>Test</th>
                        <th>Target</th>
                        <th class="text-center">Results</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $view = $viewStatus->getComplianceView() ?>
                        <?php $warningLabel = $view->getStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                        <?php $printed = false ?>
                        <?php $mappings = $that->getStatusMappings($view); ?>
                        <?php $class = $that->getClass($view); ?>
                        <?php $j = 0 ?>
                        <?php foreach($mappings as $sstatus => $mapping) : ?>
                            <?php if ($warningLabel !== null || $sstatus != ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                <tr>
                                    <?php if($j < 1) : ?>
                                        <td rowspan="<?php echo $warningLabel === null ? (count($mappings) - 1) : count($mappings) ?>">
                                            <?php echo $view->getReportName() ?>
                                            <br/>
                                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                <div><?php echo $link->getHTML() ?></div>
                                            <?php endforeach ?>

                                        </td>
                                    <?php endif ?>

                                    <td><span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $view->getStatusSummary($sstatus) ?></span></td>

                                    <td class="text-center">
                                        <?php if($viewStatus->getStatus() == $sstatus) : ?>
                                            <span class="label label-<?php echo $class[$sstatus] ?>">
                                                <?php echo $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                                                <?php echo $viewStatus->getComment() ?>
                                            </span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                                <?php $j++ ?>
                            <?php endif ?>
                        <?php endforeach ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php else : ?>
                <table class="details-table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th class="points">Status</th>
                        <th class="text-center">Date of Exam</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1 ?>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php

                        if ($viewStatus->isCompliant()) {
                            $pct = 1;
                        } else if ($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                            $pct = 0.5;
                        } else {
                            $pct = 0;
                        }

                        $text = $viewStatus->getText();


                        ?>
                        <?php $class = $classFor($pct) ?>
                        <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                            <td class="name">
                                <?php echo $i ?>.
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            </td>
                            <td class="points <?php echo $class ?>">
                                <?php echo $text ?>
                            </td>
                            <td class="links text-center">
                                <div><?php echo $viewStatus->getComment() ?></div>
                            </td>
                        </tr>
                        <?php $i++ ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
            <?php
            return ob_get_clean();
        };


        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
            ob_start();


            if ($group->getComplianceViewGroup()->getMaximumNumberOfPoints() === null) {
                if($group->getComplianceViewGroup()->getName() == 'Prevention Event') {
                    if($group->getComplianceViewStatus('hra')->getStatus() == ComplianceViewStatus::COMPLIANT
                        && $group->getComplianceViewStatus('screening')->getStatus() == ComplianceViewStatus::COMPLIANT) {
                        $pct = 1;
                        $actual = 'Done';
                    } elseif($group->getComplianceViewStatus('hra')->getStatus() == ComplianceViewStatus::COMPLIANT
                        || $group->getComplianceViewStatus('screening')->getStatus() == ComplianceViewStatus::COMPLIANT) {
                        $pct = 0.5;
                        $actual = 'Not Done';
                    } else {
                        $pct = 0;
                        $actual = 'Not Done';
                    }
                } else {
                    if ($group->isCompliant()) {
                        $pct = 1;
                    } else if ($group->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                        $pct = 0.5;
                    } else {
                        $pct = 0;
                    }

                    $actual = $group->getText();
                }


                $target = 'Done';
            } else {
                $points = $group->getPoints();
                $target = '<strong>'.$group->getComplianceViewGroup()->getMaximumNumberOfPoints().'</strong><br/>points';
                $actual = '<strong>'.$points.'</strong><br/>points';
                $pct = $points / $group->getComplianceViewGroup()->getPointsRequiredForCompliance();
            }

            $class = $classFor($pct);
            if($pct > 1) $pct = 1;

            ?>
            <tr class="picker closed">
                <td class="name">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="points target">
                    <?php echo $target ?>
                </td>
                <td class="points <?php echo $class ?>">
                    <?php echo $actual ?>
                </td>
                <td class="pct">
                    <div class="pgrs">
                        <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <tr class="details closed">
                <td colspan="4">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        };

        $maxPriorPoints = $escaper->escapeJs($requirementsStatus->getComplianceViewGroup()->getMaximumNumberOfPoints());

        ?>
        <style type="text/css">
            #activities {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
                min-width: 500px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }

            .details-table .name {
                width: 300px;
            }

            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            #point-discounts {
                width: 100%;
            }

            #point-discounts td {
                vertical-align: middle;
                padding: 5px;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1em;
            }


            .point-totals th {
                text-align: right;
                padding-right: 10px;
            }

            .point-totals td {
                text-align: center;
            }

            .total-status td, .spouse-status td {
                text-align: center;
            }
        </style>
        <div class="row">
            <div class="col-md-12">
                <h1>2021 Incentive Report Card</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">

                <p>
                    Each employee and spouse on the plan will receive the annual $325 premium incentive in 2022 by completing the following steps in 2021:

                    <ul>
                        <li>
                            Complete the <a href="/compliance/fivestar/hra/content/my-health">health risk assessment</a>. This must be completed before you can
                            schedule your health screening.
                        </li>
                        <li>Complete a <a href="/compliance/fivestar/appointment/content/schedule-appointments">health screening onsite</a></li>
                        <li>
                            During your 2021 screening, meet 3 of 5 biometrics and earn the incentive. Biometrics being measured
                            for the incentive can be found below. If 3 of 5 are not met you can still earn the incentive by
                            completing 1 consult call with Circle Wellness by Oct 29, 2021.
                        </li>
                    </ul>
                </p>

            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Actual</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Prevention Events', $status->getComplianceViewGroupStatus('Prevention Event')) ?>
                    <?php echo $tableRow('Biometric Measures', $status->getComplianceViewGroupStatus('Requirements')) ?>
                    <tr>
                        <td colspan="4" style="font-weight: bold;">
                            If 3 of 5 biometric measures aren't met, you can earn the incentive by completing 1 coaching consult call by 10/29/2021. To schedule, call 866-682-3020 ext 204.
                        </td>
                    </tr>
                    <tr class="point-totals">
                        <td colspan="4"><hr/></td>
                    </tr>
                    <tr class="total-status">
                        <th>
                            Your Program Status
                            <ul>
                                <li>Complete Health Risk Assessment</li>
                                <li>Complete Screening</li>
                                <li>
                                    Meet 3 of 5 biometric measures, or complete 1 coaching consult call
                                </li>
                            </ul>
                        </th>
                        <td colspan="3">
                            <?php if ($status->isCompliant()) : ?>
                                <span class="label label-success">Done</span>
                            <?php elseif ($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                <span class="label label-warning">Partially Done</span>
                            <?php else : ?>
                                <span class="label label-danger">Not Done</span>
                            <?php endif ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <script type="text/javascript">
            $(function() {
                $.each($('#activities .picker'), function() {
                    $(this).click(function(e) {
                        if ($(this).hasClass('closed')) {
                            $(this).removeClass('closed');
                            $(this).addClass('open');
                            $(this).nextAll('tr.details').first().removeClass('closed');
                            $(this).nextAll('tr.details').first().addClass('open');
                        } else {
                            $(this).addClass('closed');
                            $(this).removeClass('open');
                            $(this).nextAll('tr.details').first().addClass('closed');
                            $(this).nextAll('tr.details').first().removeClass('open');
                        }
                    });
                });

                $('.details-table .name').width($('.picker td.name').first().width());
                $('.details-table .points').width($('.picker td.points').first().width());
                $('.details-table .links').width($('.picker td.pct').first().width());

                $more = $('#more-info-toggle');
                $moreContent = $('#more-info');

                $more.click(function(e) {
                    e.preventDefault();

                    if ($more.html() == 'More...') {
                        $moreContent.css({ display: 'block' });
                        $more.html('Less...');
                    } else {
                        $moreContent.css({ display: 'none' });
                        $more.html('More...');
                    }
                });
            });
        </script>
        <?php
    }
}
