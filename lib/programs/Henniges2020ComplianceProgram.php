<?php

use hpn\steel\query\SelectQuery;

class Henniges2020CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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
                'required_fields'  => array('cholesterol', 'glucose')
            )
        );

        return $data;
    }
}

class Henniges2020ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        

        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserLocation(true);
        $printer->setShowUserFields(true, true, true, false, true,  null, null, null, null, null, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);
        $printer->setShowUserContactFields(null, null, true);
        $printer->setShowShowRelatedUserFields(null, null, true);

        $printer->addCallbackField('gender', function (User $user) {
            return $user->getGender();
        });

        $printer->addCallbackField('location', function (User $user) {
            return $user->getLocation();
        });

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $hraStatus = $status->getComplianceViewStatus('hra');
            $screeningStatus  = $status->getComplianceViewStatus('screening');

            return array(
                'Hra Date'          => ($hraStatus->isCompliant() ? $hraStatus->getComment() : ''),
                'Screening Date'          => $screeningStatus->getComment(),
            );
        });

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            global $_db;
            global $_user;
            $data = array();

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($groupStatus->getComplianceViewGroup()->getName() == 'Requirements') {
                        $data[sprintf('%s Result', $viewName)] = $viewStatus->getComment();
                    } elseif($groupStatus->getComplianceViewGroup()->getName() == 'required') {
                        $data[sprintf('%s Comment', $viewName)] = $viewStatus->isCompliant() ? $viewStatus->getComment() : '';
                    }

                    $data[sprintf('%s Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                }
            }

            $data['Total Points'] = $status->getPoints();
            $data['Total Points (Ignoring Alternatives)'] = $status->getAttribute('total_points_ignoring_alternatives');
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Henniges2020WMS2Printer();
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function loadGroups()
    {

        global $_user;

        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT => new ComplianceStatusMapping('Compliant', '/resources/3813/greenblock2.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Compliant', '/images/ministryhealth/yellowblock1.jpg'),
            ComplianceStatus::NOT_COMPLIANT => new ComplianceStatusMapping('Not Compliant', '/images/ministryhealth/redblock1.jpg')
        ));

        $this->setComplianceStatusMapper($mapping);

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $screeningStart = '2019-10-01';
        $screeningEnd = '2020-12-31';
        $tobaccoEnd = "2020-09-30";

        $preventionEventGroup = new ComplianceViewGroup('required', 'Prevention Events');

        
        $hraEnd = "2020-12-31";
        if ($_user->client_id == 3751) {
            $hraEnd = "2020-12-31";
        }

        $hraView = new CompleteHRAComplianceView($programStart, $hraEnd);
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->setAttribute('deadline', date("m/d/Y", strtotime($hraEnd)));
        $hraView->addLink(new Link('Take HRA', sfConfig::get('app_wms2') ? '/compliance/henniges-automotive-2019/my-health' : '/content/989'));
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new Henniges2020CompleteScreeningComplianceView($screeningStart, $screeningEnd);
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $screeningView->setReportName('Physician Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->setAttribute('deadline',  date("m/d/Y", strtotime($screeningEnd)));
        $preventionEventGroup->addComplianceView($screeningView);

        $cotinineView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $cotinineView->setName('tobacco_status');
        $cotinineView->setReportName('Tobacco Status (Team Members only)');
        $cotinineView->setAttribute('deadline',  date("m/d/Y", strtotime($tobaccoEnd)));
        $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $client_id = $user->client_id;
            $relationship = $user->relationship_type;

            if ($client_id != 3421 || $relationship != Relationship::EMPLOYEE) {
                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
                $status->getComplianceView()->setAttribute('deadline', 'N/A');
            }

            if ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT && $status->getComment() == 'user') {
                $status->getComplianceView()->addLink(new Link('LivingFree (for tobacco users only)', '/search-learn/lifestyle-management/content/12088?filter=livingfree'));
            }
        });
        $preventionEventGroup->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($preventionEventGroup);

        $biometricsGroup = new ComplianceViewGroup('Requirements', 'Biometric Measures');
        $biometricsGroup->setPointsRequiredForCompliance(10);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStart, $screeningEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $biometricsGroup->addComplianceView($bloodPressureView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($screeningStart, $screeningEnd);
        $cholesterolView->setIndicateSelfReportedResults(false);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $biometricsGroup->addComplianceView($cholesterolView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($screeningStart, $screeningEnd);
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $biometricsGroup->addComplianceView($hdlView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($screeningStart, $screeningEnd);
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $biometricsGroup->addComplianceView($ldlView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($screeningStart, $screeningEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($screeningStart, $screeningEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $biometricsGroup->addComplianceView($glucoseView);

        $heightView = new ComplyWithHeightScreeningTestComplianceView($screeningStart, $screeningEnd);
        $heightView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $biometricsGroup->addComplianceView($heightView);

        $weightView = new ComplyWithWeightScreeningTestComplianceView($screeningStart, $screeningEnd);
        $weightView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $biometricsGroup->addComplianceView($weightView);

        $bodyFatBMIView = new GVT2015ComplyWithBodyFatBMIScreeningTestComplianceView($screeningStart, $screeningEnd);
        $bodyFatBMIView->setIndicateSelfReportedResults(false);
        $bodyFatBMIView->setBmiView($this->getBmiView($screeningStart, $screeningEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($screeningStart, $screeningEnd));
        $bodyFatBMIView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 3, 0, 0));
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);
        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);

    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if(!$this->evaluateOverall) {
            return;
        }

        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');

        $extraPoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                    $extraPoints += $viewExtraPoints;
                }
            }
        }

        $requiredCompliant = true;
        $requiredNumCompliant = 0;
        foreach($requiredGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $viewName = $viewStatus->getComplianceView()->getName();
            if($status->getUser()->getClientId() != 3421 && $viewName == 'tobacco_status') continue;

            if(!$viewStatus->isCompliant()) {
                $requiredCompliant = false;
            } else {
                $requiredNumCompliant++;
            }
        }

        if($requiredCompliant) {
            $requiredGroupStatus->setStatus(ComplianceViewGroupStatus::COMPLIANT);
        } elseif (!$requiredCompliant && $requiredNumCompliant > 0) {
            $requiredGroupStatus->setStatus(ComplianceViewGroupStatus::PARTIALLY_COMPLIANT);
        } else {
            $requiredGroupStatus->setStatus(ComplianceViewGroupStatus::NOT_COMPLIANT);
        }

        $thisRequirements = $status->getComplianceViewGroupStatus('Requirements');
        $thisPrevention = $status->getComplianceViewGroupStatus('required');

        $thisRequirementsCompliant = true;
        foreach($thisRequirements->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT && !$viewStatus->getAttribute('has_result')) {
                $thisRequirementsCompliant = false;
            }
        }
        if($thisRequirementsCompliant) $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);

        $thisYearTotalPoints = $thisRequirements->getPoints();

        $status->setPoints($thisYearTotalPoints);

        $status->setAttribute('total_points_ignoring_alternatives', $thisYearTotalPoints - $extraPoints);

        if($thisPrevention->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $thisRequirements->getStatus() == ComplianceViewGroupStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

    }

    public function getPriorYearBiometricsComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        $program = $this->cloneForEvaluation($startDate, $endDate);

        $program->evaluateOverall = false;

        $program->setActiveUser($user);

        return $program->getStatus();
    }

    private function getBmiView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBMIScreeningTestComplianceView'
        );

        $view->setIndicateSelfReportedResults(false);

        $view->addRange(4, 18.5, 25.0, 'E');
        $view->addRange(3, 17.0, 30.0, 'E');
        $view->addRange(2, 15.0, 35.0, 'E');
        $view->addRange(1, 13.0, 40.0, 'E');
        $view->setStatusSummary(0, '&lt;13 or &gt;40');


        return $view;
    }

    private function getBodyFatView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBodyFatScreeningTestComplianceView'
        );

        $view->setIndicateSelfReportedResults(false);

        $view->addRange(4, 2.0, 18.0, 'M');
        $view->addRange(3, 0.0, 25.0, 'M');
        $view->addRange(2, 0.0, 30.0, 'M');
        $view->addRange(1, 0.0, 35.0, 'M');
        $view->addDefaultStatusSummaryForGender(0, 'M', '&gt;35');


        $view->addRange(4, 12.0, 25.0, 'F');
        $view->addRange(3, 0.0, 32.0, 'F');
        $view->addRange(2, 0.0, 37.0, 'F');
        $view->addRange(1, 0.0, 42.0, 'F');
        $view->addDefaultStatusSummaryForGender(0, 'F', '&gt;42');

        return $view;
    }

    protected $evaluateOverall = true;
}

class Henniges2020WMS2Printer implements ComplianceProgramReportPrinter
{
    public function getStatusMappings(ComplianceView $view)
    {
        if($view->getName() == 'bf_bmi') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                3 => ComplianceStatus::PARTIALLY_COMPLIANT,
                2 => ComplianceStatus::NOT_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT,
                0 => ComplianceStatus::NOT_COMPLIANT
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
        if($view->getName() == 'bf_bmi') {

            return array(
                4 => 'success',
                3 => 'warning',
                2 => 'danger',
                1 => 'danger',
                0 => 'danger'
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

        $user = $status->getUser();

        $requiredStatus = $status->getComplianceViewGroupStatus('required');
        $requirementsStatus = $status->getComplianceViewGroupStatus('Requirements');

        $thisYearTotalPoints = $requirementsStatus->getPoints();
        $biometricStatus = $thisYearTotalPoints >=9 ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $that = $this;


        $classForCircleStatus = function($status) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else {
                return 'danger';
            }
        };

        $textForCircleStatus = function($status, $sectionName) {
            if($sectionName == 'required') {
                if ($status == ComplianceStatus::COMPLIANT) {
                    return '<div style="font-size:9pt; margin-top: 10px;">Done</div>';
                } else {
                    return '<div style="font-size:9pt; margin-top: 10px;">Not Done</div>';
                }
            } else {
                return '';
            }
        };

        $classForStatus = function($status) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $textForStatus = function($status, $view_name = NULL) {
            if($view_name == 'tobacco_status') {
                if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
                    return 'Non User';
                } else {
                    if($status->getComment() == 'user') {
                        return 'User';
                    }

                    return 'Not Done';
                }
            }

            if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
                return 'Done';
            } else if ($status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                $coachingViews = array(
                    'coaching_overall',
                    'coaching_session1',
                    'coaching_session2',
                    'coaching_session3',
                    'coaching_session4'
                );

                if($status->getComment() == 'Not Required') {
                    return 'Not Required';
                } elseif(in_array($view_name, $coachingViews)) {
                    return 'Pending';
                } else {
                    return 'Not Required';
                }

            } else {
                return 'Not Done';
            }
        };

        $textForSpouseStatus = function($status, $comment, $isCoaching = false) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'Done';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                if($isCoaching) {
                    if($comment == 'Not Required') {
                        return 'Not Required';
                    } else {
                        return 'Pending';
                    }
                } else {
                    return 'Not Required';
                }
            } else if ($status == ComplianceStatus::NOT_COMPLIANT) {
                return 'Not Done';
            }
        };

        $circle = function($status, $text, $sectionName) use ($classForCircleStatus, $textForCircleStatus) {
            $class = $status === 'GVT' ? 'GVT' : $classForCircleStatus($status);
            ob_start();
            ?>
            <div class="circle-range">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>"
                    <?php echo $sectionName == 'Requirements' ? 'style="background-color: #CCC;"' : '' ?>>
                    <div style="font-size: 1.2em; line-height: 1.0em;">
                        <?php echo $text ?>
                        <?php echo $textForCircleStatus($status, $sectionName) ?>
                    </div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };


        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor, $classForStatus, $textForStatus, $textForSpouseStatus, $that) {
            ob_start();

            if ($group->getComplianceViewGroup()->getName() == 'Requirements') : ?>
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>Test</th>
                        <th class="text-center">Results</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $view = $viewStatus->getComplianceView() ?>
                        <?php $warningLabel = $view->getStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                        <?php $mappings = $that->getStatusMappings($view); ?>
                        <?php $class = $that->getClass($view); ?>
                        <?php $j = 0 ?>
                        <tr>
                            <td>
                                <?php echo $view->getReportName() ?>
                                <br/>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                    <div><?php echo $link->getHTML() ?></div>
                                <?php endforeach ?>
                            </td>

                            <td class="text-center">
                                <?php if($viewStatus->getComplianceView()->getName() == 'bf_bmi') : ?>
                                    <?php foreach($mappings as $sstatus => $mapping) : ?>
                                        <?php if($viewStatus->getPoints() == $sstatus) : ?>
                                            <span class="label label-<?php echo $class[$sstatus] ?>"><?php echo $viewStatus->getComment() ?></span>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                <?php else : ?>
                                    <span class="label label-<?php echo isset($class[$viewStatus->getStatus()]) ? $class[$viewStatus->getStatus()] : '' ?>">
                                                <?php echo $viewStatus->getComment() ?>
                                            </span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <?php $j++ ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php else : ?>
                <table class="details-table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th class="points">Status</th>
                        <th class="points">deadline</th>
                        <th class="text-center">Links</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1 ?>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $class = $classForStatus($viewStatus->getStatus()); ?>
                        <?php $statusText = $textForStatus($viewStatus, $viewStatus->getComplianceView()->getName()) ?>
                        <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                            <td class="name">
                                <?php echo $i ?>.
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            </td>
                            <td class="points <?php echo $class ?>" <?php echo $class == 'info' ? 'style="background-color: #CCC;"' : ''?>><?php echo $statusText ?>
                            </td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('deadline') ?></td>
                            <td class="links text-center">
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                    <div><?php echo $link->getHTML() ?></div>
                                <?php endforeach ?>
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

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $classForStatus, $textForStatus, $groupTable) {
            ob_start();

            $numOfViews = 0;
            $numOfCompliant = 0;
            foreach($group->getComplianceViewStatuses() as $viewStatus) {
                $numOfViews++;
                if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numOfCompliant++;
            }

            ?>
            <?php $pct = $numOfCompliant / $numOfViews; ?>
            <?php $class = $classForStatus($group->getStatus()); ?>
            <?php $statusText = $textForStatus($group); ?>
            <tr id="<?php echo preg_replace('/\s+/', '', $name)?>" class="picker closed">
                <td class="name">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="target_status points success">Done
                </td>
                <td class="current_status points <?php echo $class ?>"><?php echo $statusText ?>
                </td>
                <td class="pct">
                    <div class="pgrs">
                        <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <tr class="details">
                <td colspan="4">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>

            <?php

            return ob_get_clean();
        };

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
                font-size: 1.4em;
            }

            .point-totals th {
                text-align: right;
                padding-right: 10px;
            }

            .point-totals td {
                text-align: center;
            }

            .hide {
                display: none;
            }

            #option_one_title, #option_two_title {
                background:none!important;
                border:none;
                padding:0!important;
                font: inherit;
                color: #2196f3;
                cursor: pointer;
            }

        </style>

        <div class="row">

            <div class="row">
                <div class="col-md-12" <?php echo !sfConfig::get('app_wms2') ? 'style="margin-top: 150px;"' : '' ?>>
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">

                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1 activity">
                                            <?php echo $circle(
                                                $requiredStatus->getStatus(),
                                                '<br/>Prevention <br/>Events',
                                                'required'
                                            ) ?>
                                            <br/>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div id="more-info">
                    <div class="row">
                        <div class="col-md-12">
                            <?php if($user->getClientId() == 3748) : ?>

                                <p>Welcome to the Henniges Automotive wellness program! </p>

                                <p>Click on <a href="/compliance/henniges-automotive-2019/program-overview/content/henniges_program_overview">Program Overview</a> link to view your incentive program details and requirements.</p>

                                <p>The report card outlines your requirements and will help you keep track of your progress throughout the program.
                                    If you choose not to complete the requirements, you will not be eligible for the incentive.</p>

                            <?php elseif($user->getClientId() == 3751) : ?>

                                <p>Welcome to the Henniges Automotive wellness program! </p>

                                <p>Click on <a href="/compliance/henniges-automotive-2019/program-overview/content/henniges_program_overview">Program Overview</a> link to view your incentive program details and requirements.</p>

                            <?php else : ?>

                                <p>Welcome to the Henniges Automotive wellness program! </p>

                                <p>Click on <a href="/compliance/henniges-automotive-2019/program-overview/content/henniges_program_overview">Program Overview</a> link to view your incentive program details and requirements.</p>

                                <p>The report card outlines your requirements and will help you keep track of your progress throughout the program.
                                    If you choose not to complete the requirements, a monthly surcharge will apply for each requirement.</p>

                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Status</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Prevention Events', $status->getComplianceViewGroupStatus('required')) ?>

                    <?php if($user->getClientId() == 3751 || $user->getClientId() == 3421) : ?>
                        <?php echo $tableRow('Physician Screening Results', $status->getComplianceViewGroupStatus('Requirements')) ?>
                    <?php endif ?>
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

                clientid = <?php echo $user->getClientId();?>;
                if (clientid != 3421) {
                    $('.view-tobacco_status').hide();
                }

                $('.details-table .name').width($('.picker td.name').first().width());
                $('.details-table .points').width($('.picker td.points').first().width());
                $('.details-table .links').width($('.picker td.pct').first().width());


                $('.view-coaching_session1').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 1</span>');
                $('.view-coaching_session2').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 2</span>');
                $('.view-coaching_session3').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 3</span>');
                $('.view-coaching_session4').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 4</span>');



                $optionOneTitle = $('#option_one_title');
                $optionOneContent = $('#option_one_content');

                $optionTwoTitle = $('#option_two_title');
                $optionTwoContent = $('#option_two_content');

                $optionOneTitle.click(function() {
                    $optionOneContent.toggleClass('hide');
                });

                $optionTwoTitle.click(function() {
                    $optionTwoContent.toggleClass('hide');
                });

                if ($('.view-screening .points').hasClass("success")) {
                    $('#PhysicianScreeningResults .current_status').removeClass('danger').addClass('success');
                    $('#PhysicianScreeningResults .current_status').html("Done");
                    $('#PhysicianScreeningResults .bar').removeClass('danger').addClass('success').css("width","100%");
                }
            });
        </script>
        <?php
    }
}

