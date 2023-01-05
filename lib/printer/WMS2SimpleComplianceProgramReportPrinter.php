<?php

class WMS2SimpleComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Report Card';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {

    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $hraStatus = $status->getComplianceViewStatus('hra');
        $screeningStatus = $status->getComplianceViewStatus('screening');


        $class = function(ComplianceViewStatus $status) {
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                return 'panel-success';
            } elseif($status->getStatus() == ComplianceViewStatus::PARTIALLY_COMPLIANT) {
                return 'panel-warning';
            } elseif($status->getStatus() == ComplianceViewStatus::NOT_COMPLIANT) {
                return 'panel-danger';
            } else {
                return 'panel-default';
            }
        };


        $progress = function(ComplianceViewStatus $status) {
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                return 'Complete';
            } elseif($status->getStatus() == ComplianceViewStatus::PARTIALLY_COMPLIANT) {
                return 'In Progress';
            } elseif($status->getStatus() == ComplianceViewStatus::NOT_COMPLIANT) {
                return 'Incomplete';
            } else {
                return 'Not Required';
            }
        };

        ?>

        <style type="text/css">
            .panel {
                margin-bottom: 25px;
                background-color: #ffffff;
                border: 1px solid transparent;
                border-radius: 3px;
                -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
            }

            .panel-heading {
                padding: 10px 15px;
                border-bottom: 1px solid transparent;
                border-top-right-radius: 2px;
                border-top-left-radius: 2px;
            }

            .panel-default {
                border-color: #dddddd;
            }
            .panel-default > .panel-heading {
                color: #212121;
                background-color: #f5f5f5;
                border-color: #dddddd;
            }
            .panel-default > .panel-heading + .panel-collapse > .panel-body {
                border-top-color: #dddddd;
            }
            .panel-default > .panel-heading .badge {
                color: #f5f5f5;
                background-color: #212121;
            }
            .panel-default > .panel-footer + .panel-collapse > .panel-body {
                border-bottom-color: #dddddd;
            }

            .panel-success {
                border-color: #d6e9c6;
            }
            .panel-success > .panel-heading {
                color: #ffffff;
                background-color: #74c36e;
                border-color: #d6e9c6;
            }
            .panel-success > .panel-heading + .panel-collapse > .panel-body {
                border-top-color: #d6e9c6;
            }
            .panel-success > .panel-heading .badge {
                color: #74c36e;
                background-color: #ffffff;
            }
            .panel-success > .panel-footer + .panel-collapse > .panel-body {
                border-bottom-color: #d6e9c6;
            }

            .panel-warning {
                border-color: #ffc599;
            }
            .panel-warning > .panel-heading {
                color: #ffffff;
                background-color: #fdb83b;
                border-color: #ffc599;
            }
            .panel-warning > .panel-heading + .panel-collapse > .panel-body {
                border-top-color: #ffc599;
            }
            .panel-warning > .panel-heading .badge {
                color: #fdb83b;
                background-color: #ffffff;
            }
            .panel-warning > .panel-footer + .panel-collapse > .panel-body {
                border-bottom-color: #ffc599;
            }

            .panel-danger {
                border-color: #f7a4af;
            }
            .panel-danger > .panel-heading {
                color: #ffffff;
                background-color: #f15752;
                border-color: #f7a4af;
            }
            .panel-danger > .panel-heading + .panel-collapse > .panel-body {
                border-top-color: #f7a4af;
            }
            .panel-danger > .panel-heading .badge {
                color: #f15752;
                background-color: #ffffff;
            }
            .panel-danger > .panel-footer + .panel-collapse > .panel-body {
                border-bottom-color: #f7a4af;
            }

            .text-right {
                text-align: right;
            }

            .left {
                width: 60%;
                float: left;
            }

            .right {
                width: 38%;
                float: right;
            }

            .row {
                margin-left: -5px;
                margin-right: -5px;
            }

            #banner-image {
                margin: 20px 0;
            }

            #banner-image img{
                width: 100%;
            }

        </style>

        <div style="">
            <?php $this->printHeader($status) ?>

            <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
                <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <div class="panel <?php echo $class($viewStatus) ?>">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="left"><?php echo $viewStatus->getComplianceView()->getReportName() ?></div>
                            <div class="right text-right"><?php echo $progress($hraStatus) ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            <?php endforeach ?>
        </div>
        <?php
    }
}