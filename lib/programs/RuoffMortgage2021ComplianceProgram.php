<?php

use hpn\steel\query\SelectQuery;



class RuoffMortgage2021CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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

class RuoffMortgage2021ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new RuoffMortgage2021WMS2Printer();
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
        $printer->setShowUserContactFields(null, null, true);

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

                    if($viewName == 'Screening Program') {
                        $data['Enrollment Date'] = $user->planenrolldate;
                    }
                }
            }

            $data['Total Points'] = $status->getPoints();
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        date_default_timezone_set('America/New_York');

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();


        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');

        $hpa = new CompleteHRAComplianceView('2021-07-01', $programEnd);
        $hpa->setReportName('Health Risk Assessment (HRA)');
        $hpa->setName('hra');
        $hpa->emptyLinks();
        $hpa->setPreMapCallback(function(ComplianceViewStatus $status, User $user){
            if($user->planenrolldate && $this->validateDate($user->planenrolldate)) {
                $endDate =  date('Y-m-d', strtotime('+30 days', strtotime($user->planenrolldate)));

                $alternative = new CompleteHRAComplianceView('2021-07-01', $endDate);

                $alternativeStatus = $alternative->getStatus($user);

                $status->setStatus($alternativeStatus->getStatus());
            }
        });
        $preventionEventGroup->addComplianceView($hpa);

        $scr = new CompleteScreeningComplianceView($programStart, $programEnd);
        $scr->setReportName('Screening Program');
        $scr->setName('screening');
        $scr->emptyLinks();
        $scr->setPreMapCallback(function(ComplianceViewStatus $status, User $user){
            if($user->planenrolldate && $this->validateDate($user->planenrolldate)) {
                $endDate =  date('Y-m-d', strtotime('+30 days', strtotime($user->planenrolldate)));

                $alternative = new CompleteScreeningComplianceView($this->getStartDate('Y-m-d'), $endDate);

                $alternativeStatus = $alternative->getStatus($user);

                $status->setStatus($alternativeStatus->getStatus());
            }
        });
        $preventionEventGroup->addComplianceView($scr);


        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements');

        $bmiWaistView = new ComplyWithBMIWaistScreeningTestComplianceView($programStart, $programEnd);
        $bmiWaistView->setReportName('Waist Circumference/BMI');
        $bmiWaistView->overrideBMITestRowData(0, 0, 29.999, 29.999);
        $bmiWaistView->overrideWaistTestRowData(0, 0, 35, 35, 'F');
        $bmiWaistView->overrideWaistTestRowData(0, 0, 40, 40, 'M');
        $bmiWaistView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bmiWaistView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥30');
        $bmiWaistView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤29.9');
        $bmiWaistView->setPreMapCallback(function(ComplianceViewStatus $status, User $user){
            $view = $status->getComplianceView();

            if($user->getGender() == 'M') {
                $view->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'Waist > 40 inches OR BMI > 29.9');
                $view->setStatusSummary(ComplianceStatus::COMPLIANT, 'Waist ≤ 40 inches OR BMI ≤ 29.9');
            } else {
                $view->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'Waist > 35 inches OR BMI > 29.9');
                $view->setStatusSummary(ComplianceStatus::COMPLIANT, 'Waist ≤ 35 inches OR BMI ≤ 29.9');
            }

            $alternative = new ComplyWithBMIWaistScreeningTestComplianceView('2020-07-01', '2021-06-30');
            $alternativeStatus = $alternative->getStatus($user);


            if($user->getGender() == 'M') {
                $alternative->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'Waist > 35 inches OR BMI > 29.9');
                $alternative->setStatusSummary(ComplianceStatus::COMPLIANT, 'Waist ≤ 35 inches OR BMI ≤ 29.9');
            } else {
                $alternative->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'Waist > 40 inches OR BMI > 29.9');
                $alternative->setStatusSummary(ComplianceStatus::COMPLIANT, 'Waist ≤ 40 inches OR BMI ≤ 29.9');
            }

            $status->setAttribute('last_status', $alternativeStatus->getStatus());
            $status->setAttribute('last_result', $alternativeStatus->getComment());
        });
        $bmiWaistView->setPostEvaluateCallback($this->checkImprovement(array('bmi', 'waist')));
        $biometricsGroup->addComplianceView($bmiWaistView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setIndicateSelfReportedResults(false);
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $glucoseView->overrideTestRowData(0, 0, 108, 108);
        $glucoseView->setPreMapCallback(function(ComplianceViewStatus $status, User $user){
            $alternative = new ComplyWithGlucoseScreeningTestComplianceView('2020-07-01', '2021-06-30');
            $alternative->setIndicateSelfReportedResults(false);
            $alternative->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
            $alternative->overrideTestRowData(0, 0, 108, 108);
            $alternativeStatus = $alternative->getStatus($user);

            $status->setAttribute('last_status', $alternativeStatus->getStatus());
            $status->setAttribute('last_result', $alternativeStatus->getComment());
        });
        $glucoseView->setPostEvaluateCallback($this->checkImprovement(array('glucose')));
        $biometricsGroup->addComplianceView($glucoseView);


        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setIndicateSelfReportedResults(false);
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 135, 135);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 86, 86);
        $bloodPressureView->setPreMapCallback(function(ComplianceViewStatus $status, User $user){
            $systolic = $status->getComplianceView()->getSystolicView()->getStatus($user)->getComment();
            $diastolic = $status->getComplianceView()->getDiastolicView()->getStatus($user)->getComment();

            if($systolic > 135 || $diastolic > 86) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }

            $alternative = new ComplyWithBloodPressureScreeningTestComplianceView('2020-07-01', '2021-06-30');
            $alternative->setIndicateSelfReportedResults(false);
            $alternative->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
            $alternative->overrideSystolicTestRowData(0, 0, 135, 135);
            $alternative->overrideDiastolicTestRowData(0, 0, 86, 86);
            $alternativeStatus = $alternative->getStatus($user);

            $systolicLastYear = $alternativeStatus->getComplianceView()->getSystolicView()->getStatus($user)->getComment();
            $diastolicLastYear = $alternativeStatus->getComplianceView()->getDiastolicView()->getStatus($user)->getComment();

            if($systolicLastYear > 135 || $diastolicLastYear > 86) {
                $alternativeStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }

            $status->setAttribute('last_status', $alternativeStatus->getStatus());
            $status->setAttribute('last_result', $alternativeStatus->getComment());
        });
        $bloodPressureView->setPostEvaluateCallback($this->checkImprovement(array('systolic', 'diastolic')));
        $biometricsGroup->addComplianceView($bloodPressureView);


        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setIndicateSelfReportedResults(false);
        $ldlView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $ldlView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 140');
        $ldlView->overrideTestRowData(0, 0, 140, 140);
        $ldlView->setPreMapCallback(function(ComplianceViewStatus $status, User $user){
            $alternative = new ComplyWithLDLScreeningTestComplianceView('2020-07-01', '2021-06-30');
            $alternative->setIndicateSelfReportedResults(false);
            $alternative->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
            $alternative->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 140');
            $alternative->overrideTestRowData(0, 0, 140, 140);
            $alternativeStatus = $alternative->getStatus($user);

            $status->setAttribute('last_status', $alternativeStatus->getStatus());
            $status->setAttribute('last_result', $alternativeStatus->getComment());
        });
        $ldlView->setPostEvaluateCallback($this->checkImprovement(array('ldl')));
        $biometricsGroup->addComplianceView($ldlView);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setName('tobacco');
        $smokingView->setReportName('Tobacco');
        $smokingView->setPreMapCallback(function(ComplianceViewStatus $status, User $user){
            $alternative = new ComplyWithSmokingHRAQuestionComplianceView('2020-07-01', '2021-06-30');
            $alternativeStatus = $alternative->getStatus($user);

            $status->setAttribute('last_status', $alternativeStatus->getStatus());
            $status->setAttribute('last_result', $alternativeStatus->getComment());
        });
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

    protected function checkImprovement(array $tests, $calculationMethod = 'decrease') {
        $programStart = new \DateTime('@'.$this->getStartDate());
        $programEnd = new \DateTime('@'.$this->getEndDate());

        $lastStart = new \DateTime('2020-07-01');
        $lastEnd = new \DateTime('2021-06-30');

        return function(ComplianceViewStatus $status, User $user) use ($tests, $programStart, $programEnd, $lastStart, $lastEnd, $calculationMethod) {
            static $cache = null;

            if ($cache === null || $cache['user_id'] != $user->id) {
                $cache = array(
                    'user_id' => $user->id,
                    'this' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $programStart, $programEnd),
                    'last' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $lastStart, $lastEnd)
                );
            }

            if (count($tests) > 0 && $cache['this'] && $cache['last']) {
                $isImproved = false;

                foreach($tests as $test) {
                    if($test == 'bmi') {
                        if($cache['last'][0]['height'] !== null && $cache['last'][0]['weight'] !== null && is_numeric($cache['last'][0]['height']) && is_numeric($cache['last'][0]['weight']) && $cache['last'][0]['height'] > 0) {
                            $cache['last'][0][$test] = ($cache['last'][0]['weight'] * 703) / ($cache['last'][0]['height'] * $cache['last'][0]['height']);
                        }

                        if($cache['this'][0]['height'] !== null && $cache['this'][0]['weight'] !== null && is_numeric($cache['this'][0]['height']) && is_numeric($cache['this'][0]['weight']) && $cache['this'][0]['height'] > 0) {
                            $cache['this'][0][$test] = ($cache['this'][0]['weight'] * 703) / ($cache['this'][0]['height'] * $cache['this'][0]['height']);
                        }
                    }


                    $lastVal = isset($cache['last'][0][$test]) ? (float) $cache['last'][0][$test] : null;
                    $thisVal = isset($cache['this'][0][$test]) ? (float) $cache['this'][0][$test] : null;


                    if($lastVal && $thisVal) {
                        if($calculationMethod == 'decrease') {
                            if (!$thisVal || !$lastVal || $lastVal * 0.90 >= $thisVal) {
                                $isImproved = true;

                                break;
                            }
                        } else {
                            if (!$thisVal || !$lastVal || $lastVal * 1.10 <= $thisVal) {
                                $isImproved = true;

                                break;
                            }
                        }
                    }
                }

                if ($isImproved) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        };
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
            ->andWhere('a.typeid IN (35)')
            ->andWhere('at.user_id = ?', array($user->id))
            ->andWhere('at.showed = 1')
            ->execute()->toArray();

        if(count($appt) >= 2) {
            $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }


    }

    public function useParallelReport()
    {
        return false;
    }


}


class RuoffMortgage2021WMS2Printer implements ComplianceProgramReportPrinter
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
                        <th class="text-center">Previous Results</th>
                        <th class="text-center">Current Results</th>
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
                                        <?php if($viewStatus->getAttribute('last_status') == $sstatus) : ?>
                                            <span>
                                                <?php echo $viewStatus->getAttribute('last_result') ?>
                                            </span>
                                        <?php endif ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if($viewStatus->getStatus() == $sstatus) : ?>
                                            <span class="label label-<?php echo $class[$sstatus] ?>">
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
                    Employees on the Ruoff Mortgage medical plan will receive an annual $650 premium incentive in 2022 by completing the following steps in 2021:

                    <ul>
                        <li>
                            Complete the <a href="/compliance/ruoffmortgage/hra/content/my-health">health risk assessment</a>.
                            This must be completed before you can schedule your health screening. (Deadline September 17th).
                        </li>
                        <li>Complete a <a href="/compliance/ruoffmortgage/appointment/content/schedule-appointments">health screening</a>. (Deadline September 17th).</li>
                        <li>
                            Meet 3 of 5 biometrics and earn the incentive. If 3 of 5 are not met, you can still earn the
                            incentive by completing 2 coaching calls with Circle Wellness by December 3, 2021.
                        </li>
                    </ul>

                    <p>
                        Biometrics being measured for the incentive can be found below. If you do not meet the goal range
                        for a biometric but improved by 10% over last year, that improvement will count as meeting the
                        goal range for that biometric.
                    </p>

                    <p>
                        Note: New hires have 30 days from your medical effective date to complete the HRA and Screening.
                        If you qualify for coaching consult calls, you will be notified of your deadline to complete your calls.
                    </p>
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
                            If 3 of 5 biometric measures aren’t met, you can earn the incentive by completing 2 coaching
                            consult calls by December 3, 2021. To schedule your coaching session, call 866-682-3020 ext 204
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
                                    Meet 3 of 5 biometric measures, or complete 2 coaching consult calls
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
