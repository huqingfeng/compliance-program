<?php

class CHPWMS3DemoComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new CHPWMS3DemoWMS3Printer();
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new ComplianceStatusMapper(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Done', '/images/shape/lights/done.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Done', '/images/shape/lights/incomplete.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done', '/images/shape/lights/notdoneyet_new_color.jpg'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('N/A', '/images/shape/lights/notrequired.jpg')
        )));

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $hraGroup = new ComplianceViewGroup('requirements_hra');

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('HRA (Health Risk Appraisal)');
        $hra->setName('hra');
        $hra->setAttribute('about', '');
        $hra->setStatusSummary(ComplianceStatus::COMPLIANT, 'Can only be completed annually between March 1, 2021 and May 1, 2021.');
        $hra->setAttribute('did_this_link', '/content/i_did_this');

        $hraGroup->addComplianceView($hra);
        $this->addComplianceViewGroup($hraGroup);


        $tobaccoGroup = new ComplianceViewGroup('requirements_tobacco');

        $tobacco = new Shape2017BMINicotineCardComplianceProgramView(ComplianceStatus::NOT_COMPLIANT);
        $tobacco->setReportName('BMI/Nicotine Card');
        $tobacco->setName('tobacco_bmi');
        $tobacco->setStatusSummary(ComplianceStatus::COMPLIANT, 'Can only be completed annually between March 1, 2021 and May 1, 2021.');
        $tobacco->setAttribute('about', 'BMI/Nicotine card must be completed at the Fitness Factory or with your Primary Care Physician.');
        $tobacco->setAttribute('did_this_link', '/content/i_did_this');
        $tobaccoGroup->addComplianceView($tobacco);

        $this->addComplianceViewGroup($tobaccoGroup);


        $physicalGroup = new ComplianceViewGroup('requirements_physical');

        $phye = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $phye->setName('physical');
        $phye->setReportName('Preventive Physical');
        $phye->setAttribute('about', 'Fax or mail the Physical Form to Circle Wellness. <a href="/resources/8133/2017-18 Employee Physical Form.pdf" target="_blank">Click here</a> for the form.');
        $phye->setAttribute('did_this_link', '/content/i_did_this');
        $phye->setStatusSummary(ComplianceStatus::COMPLIANT, 'Employee Year! Physical must be completed sometime between May 1, 2021 and May 1, 2022.');
        $phye->setEvaluateCallback(array($this, 'physicalIsRequired'));
        $phye->setPreMapCallback(function(ComplianceViewStatus $status, User $user) {
            $startDate = '2015-05-01';
            $endDate = '2017-05-01';

            if(!$status->isCompliant() && !$status->getUsingOverride()) {

                $prevPhysicalView = new CompletePreventionPhysicalExamComplianceView($startDate, $endDate);

                if($prevPhysicalView->getStatus($user)->isCompliant()) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } else {
                    $scrView = new ShapeCompleteScreeningComplianceView($startDate, $endDate);

                    if($scrView->getStatus($user)->isCompliant()) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    }
                }
            }
        });
        $physicalGroup->addComplianceView($phye);

        $this->addComplianceViewGroup($physicalGroup);


        $diseaseGroup = new ComplianceViewGroup('requirements_disease');

        $requiredUserIds = $this->getDiseaseManagementUserIds();
        $disease = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $disease->setReportName('Care Management');
        $disease->setName('disease_management');
        $disease->setAttribute('about', 'Only required if contacted directly by Priority Health via mail.');
        $disease->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you are required to participate in the Care Management program, you must enroll by October 31, 2021 and meet all requirements by May 1, 2022. Please note that your status light will be updated once at the beginning of November 2021, and weekly beginning in March 2022.');
        $disease->setPreMapCallback(function(ComplianceViewStatus $status, User $user) use ($requiredUserIds) {
            if(in_array($user->id, $requiredUserIds)) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        });
        $diseaseGroup->addComplianceView($disease);

        $this->addComplianceViewGroup($diseaseGroup);


        $weightGroup = new ComplianceViewGroup('requirements_weight');

        $weightProgram = new Shape2017WeightManagementNicotineProgramView();
        $weightProgram->setReportName('Complete the 2020-2021 Wellness Program');
        $weightProgram->setName('completed_program');
        $weightProgram->setAttribute('about', 'Only required if currently enrolled in Wellness Program.');
        $weightProgram->setAttribute('did_this_link', '/content/i_did_this');
        $weightProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you are in the Wellness Program, all requirements must be completed by February 28, 2022.');
        $weightGroup->addComplianceView($weightProgram);

        $this->addComplianceViewGroup($weightGroup);


        $wellnessGroup = new ComplianceViewGroup('requirements_wellness');

        $enrollProgram = new Shape2017FamilyWellnessComplianceProgramView($startDate, $endDate);
        $enrollProgram->setReportName('Enroll/Re-enroll in 2021-2022 Wellness Program');
        $enrollProgram->setName('enroll_program');
        $enrollProgram->setAttribute('about', 'Only required if BMI is 30 or greater <br /> or you use nicotine products.');
        $enrollProgram->setAttribute('link_add', 'Call Fitness Factory for an appointment.');
        $enrollProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'If required, must attend a group enrollment session in the month of May.');
        $wellnessGroup->addComplianceView($enrollProgram);

        $this->addComplianceViewGroup($wellnessGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $enrollStatus = $status->getComplianceViewStatus('enroll_program');

        if(!$enrollStatus->getAttribute('show_not_required') && !$enrollStatus->getAttribute('show_actual_green')) {
            if($enrollStatus->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                $status->getComplianceViewGroupStatus('requirements_wellness')->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            }
        }
    }


    public function physicalIsRequired(User $user)
    {
        return $user->relationship_type == Relationship::EMPLOYEE;
    }

    public function getDiseaseManagementUserIds()
    {
        return array();
    }
}

function getResponsiveStatus($status) {
    if ($status == ComplianceStatus::COMPLIANT) {
        return "<div class='status-ring status-success'><i class='fa fa-check'></i></div><div class='status-text'>DONE</div>";
    } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
        return "<div class='status-ring status-warning'></div><div class='status-text'>NOT DONE</div>";
    } else if ($status == ComplianceStatus::NOT_COMPLIANT) {
        return "<div class='status-ring status-danger'><i class='fa fa-times'></i></div><div class='status-text'>NOT COMPLIANT</div>";
    } else if ($status == ComplianceStatus::NA_COMPLIANT) {
        return "<div class='status-ring'></div><div class='status-text'>NOT REQUIRED</div>";
    }
}


class CHPWMS3DemoWMS3Printer implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
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

        $circle = function($status, $text) use ($classForStatus) {
            $class = $status === 'shape' ? 'shape' : $classForStatus($status);
            ob_start();
            ?>
            <div class="circle-range ring-<?= $class ?>">
                <div class="circle-range-inner circle-range-inner-<?= $class ?>">
                    <div style="font-size: 1.2em; line-height: 1.5em; width: 107px; position: relative; left: -11px;"><?= $text ?></div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };


        $groupTable = function(ComplianceViewGroupStatus $group) use ($classForStatus) {
            ob_start();
            ?>

            <table class="details-table">
                <tbody>
                <?php $i = 1 ?>
                <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $view = $viewStatus->getComplianceView() ?>
                    <?php $class = $classForStatus($viewStatus->getStatus()) ?>
                    <tr class="<?= 'view-', $viewStatus->getComplianceView()->getName() ?>">
                        <td class="requirementscolumn">
                            <strong>DETAILS</strong>
                            <br/>
                            <?= $view->getAttribute('about') ?>
                            <br/>
                            <div>
                                <?= $view->getStatusSummary(ComplianceStatus::COMPLIANT) ?>
                            </div>
                            <?= implode(' ', $view->getLinks()) ?>
                        </td>
                        <td class="status">
                            <?= getResponsiveStatus($viewStatus->getStatus()) ?>
                            <?php if(!$viewStatus->isCompliant() && $view->getAttribute('did_this_link')) : ?>
                                <a class="btn btn-primary" href="<?= $view->getAttribute('did_this_link') ?>">Check In</a>
                            <?php endif ?>
                            <?php if(!$viewStatus->isCompliant() && $linkAdd = $view->getAttribute('link_add')) : ?>
                                <?= $linkAdd ?>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php $i++ ?>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classForStatus, $groupTable) {
            ob_start();

            $class = $classForStatus($group->getStatus());
            ?>
            <tr class="picker">
                <td class="name">
                    <?= $name ?>
                    <div class="triangle open"></div>
                </td>
                <td class="points <?= $class ?>">
                    Status
                </td>
            </tr>
            <tr class="details">
                <td colspan="2">
                    <?= $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        };



        ?>

        <div style="max-width:900px;margin:0 auto;">


        <style type="text/css">
            #activities {
                width: 100%;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px 20px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 150px;
                font-size: 1.6rem;
            }
            #activities .status {
                vertical-align: top;
                text-align: center;
                width: 150px;
                box-sizing: border-box;
            }

            #activities .links {
                vertical-align: top;
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
                text-align: center;
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

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
            }

            .details-table {
                width: 100%;
            }

            .details-table .requirementscolumn {
                vertical-align: text-top;
                padding-left: 20px;
            }

            .details-table .requirementscolumn strong {
                font-size: 1.5rem;
            }

            .details-table .comment {
                width: 280px;
                vertical-align: text-top;
            }

            .details-table tr td {
                padding: 20px 10px 40px;
            }


            /*.triangle {*/
                /*position: absolute;*/
                /*right: 15px;*/
                /*top: 8px;*/
                /*width: 0;*/
                /*height: 0;*/
                /*border-style: solid;*/
                /*border-width: 12.5px 0 12.5px 21.7px;*/
                /*border-color: transparent transparent transparent #90A4AE;*/
            /*}*/

            /*.triangle.closed {*/
                /*width: 0;*/
                /*height: 0;*/
                /*border-style: solid;*/
                /*border-width: 12.5px 0 12.5px 21.7px;*/
                /*border-color: transparent transparent transparent #90A4AE;*/
            /*}*/

            /*.triangle.open {*/
                /*width: 0;*/
                /*height: 0;*/
                /*border-style: solid;*/
                /*border-width: 21.7px 12.5px 0 12.5px;*/
                /*border-color: #48c8e8 transparent transparent transparent;*/
            /*}*/


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

            #total_points {
                display: inline-block;
                height: 100%;
                margin-top: 46%;
                font-size: 1.3em;
            }

            #header-text {
                font-weight: bold;
                font-size: 1.1em;
            }
        </style>
        <div class="row">
            <div class="col-md-12">
                <div id="header-text">
                    <p style="font-weight:bold">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec pellentesque mi. Praesent non mi est. Phasellus rutrum lacus nec sapien commodo efficitur. Ut in lorem vehicula, facilisis est eget, auctor odio. Aliquam erat volutpat.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <div class="row">
                                <div class="col-md-4">
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-12 col-md-offset-0">
                                            <?= $circle(
                                                $status->getStatus(),
                                                '2021-2022<br/>Annual<br/>Requirements'
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                </div>
                            </div>
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
            <div class="col-md-12 negative-buffer">
                <table id="activities">
                    <tbody>
                    <?= $tableRow('HRA (Health Risk Appraisal)', $status->getComplianceViewGroupStatus('requirements_hra')) ?>
                    <?= $tableRow('BMI/Nicotine Card', $status->getComplianceViewGroupStatus('requirements_tobacco')) ?>
                    <?= $tableRow('Preventive Physical', $status->getComplianceViewGroupStatus('requirements_physical')) ?>
                    <?= $tableRow('Care Management', $status->getComplianceViewGroupStatus('requirements_disease')) ?>
                    <?= $tableRow('Complete the 2021-2022 Wellness Program', $status->getComplianceViewGroupStatus('requirements_weight')) ?>
                    <?= $tableRow('Enroll/Re-enroll in 2021-2022 Wellness Program', $status->getComplianceViewGroupStatus('requirements_wellness')) ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script type="text/javascript">
            $(function() {
//                $.each($('#activities .picker'), function() {
//                    $(this).click(function(e) {
//                        if ($(this).hasClass('closed')) {
//                            $(this).removeClass('closed');
//                            $(this).addClass('open');
//                            $(this).nextAll('tr.details').first().removeClass('closed');
//                            $(this).nextAll('tr.details').first().addClass('open');
//                        } else {
//                            $(this).addClass('closed');
//                            $(this).removeClass('open');
//                            $(this).nextAll('tr.details').first().addClass('closed');
//                            $(this).nextAll('tr.details').first().removeClass('open');
//                        }
//                    });
//                });

                $('.view-disease_management .status-<?= ComplianceStatus::NA_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/notrequired.jpg');

                <?php if($status->getComplianceViewStatus('enroll_program')->getAttribute('show_not_required')) : ?>
                $('.view-enroll_program .status-<?= ComplianceStatus::NA_COMPLIANT ?>').attr('src', '/images/shape/lights/notrequired.jpg');
                <?php elseif($status->getComplianceViewStatus('enroll_program')->getAttribute('show_actual_green')) : ?>
                $('.view-enroll_program .status-<?= ComplianceStatus::COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?= ComplianceStatus::PARTIALLY_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?= ComplianceStatus::NOT_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?= ComplianceStatus::NA_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                <?php else : ?>
                $('.view-enroll_program .status-<?= ComplianceStatus::NA_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/pending.jpg');
                $('.view-enroll_program .status-<?= ComplianceStatus::COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?= ComplianceStatus::NOT_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/notcompliant.jpg');
                <?php endif ?>
            });
        </script>
        <link rel="stylesheet" type="text/css" href="/css/clients/chp/wms2.css">
      </div>
        <?php
    }
}
