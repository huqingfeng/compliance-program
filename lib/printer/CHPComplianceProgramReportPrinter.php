<?php

class CHPComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    protected function getFormattedPoints($value)
    {
        if($this->format_points_as_money) {
            if(round($value, 0) === (double) $value) {
                return '$'.number_format($value, 0);
            } else {
                return '$'.number_format($value, 2);
            }
        } else {
            return $value;
        }
    }

    /**
     * Applies a callback filter on display. Must return true to print.
     *
     * @return CHPComplianceProgramReportPrinter
     */
    public function filterComplianceViews($callback)
    {
        $this->callbacks[] = $callback;
    }

    public function printClientMessage()
    {
        $user = Piranha::getInstance()->getUser();
        $cr = Piranha::getInstance()->getContentReferencer();

        $templateVariables = array(
            'user' => array('full_name' => $user->getFullName())
        );

        $cr->printContent('110283', $templateVariables);
    }

    public function printClientNote()
    {
        $user = Piranha::getInstance()->getUser();
        $cr = Piranha::getInstance()->getContentReferencer();

        $templateVariables = array(
            'user' => array('full_name' => $user->getFullName())
        );

        $cr->printContent('112341', $templateVariables);
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        global $_user;

        ?>
    <style type="text/css">
        .pageHeading {
            color:#BC7D32;
            font-size:18pt;
            margin-bottom:20px;
        }

        .phipTable {
            width:100%;
            border-collapse:collapse;
            border:1px solid #D7D7D7;
        }

        .phipTable th, .phipTable td {
            padding:2px;
        }

        .phipTable .headerRow {
            background-color:#42669A;
            color:#FFFFFF;
            font-weight:normal;
            font-size:11pt;
        }

        .phipTable .headerRow th {
            text-align:left;
            padding:10px;
            font-weight:normal;
        }

        .phipTable .headerRow td {
            text-align:center;
            padding:10px;
        }

        .phipTable .links, .phipTable .points, .phipTable .maxpoints {
            text-align:center;
        }

        .phipTable .links {
            width:80px;
        }

        .center {
            text-align:center;
        }

        .white {
            background-color:#FFFFFF;
        }

        .light {
            width:25px;
        }

        .status {
            text-align:center;
        }

        .resource {
            width:200px;
        }

        .notes img {
            vertical-align:bottom;
        }

        #legend {
            clear:both;
            padding-bottom:15px;
            text-align:center;
        }

        #legendText {
            text-align:center;
            font-weight:bold;
            color:#42669A;
            font-size:12pt;
            margin-bottom:5px;
            width:120px;
            float:left;
        }

        #pageMessage {
            padding-bottom:20px;
        }

        #clientMessage {
            padding-bottom:20px;
        }

        .legendEntry {
            text-align:center;
            padding:2px;
        }

        .legendEntry .light {
            vertical-align:bottom;
        }

        .phipTable td.empty, .phipTable th.empty {
            padding:0px;
            margin:0px;
        }

        .notes .light {
            width:14px;
        }

        .notes {
            font-size:10pt;
        }
    </style>
    <div id="pageMessage">
        <div>Welcome: <strong><?php echo $_user->getFullName(); ?></strong></div>
        <div>
            <i>Using a shared computer?</i>
            <strong>If you are not <?php echo $_user->getFullName(); ?>, please click <a
                href="/logout">here</a>.</strong>
        </div>
    </div>

    <div id="clientMessage">
        <?php $this->printClientMessage() ?>
    </div>

    <div class="pageHeading"><?php echo $this->page_heading ?></div>

    <div id="legend">
        <div id="legendText">Legend</div>
        <?php foreach($status->getComplianceProgram()->getComplianceStatusMapper()
                          ->getMappings() as $sstatus => $mapping) {
        ?>
        <?php if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT || $status->getComplianceProgram()
            ->hasPartiallyCompliantStatus()
        ) {
            ?>
            <span class="legendEntry"><img src="<?php echo $mapping->getLight(); ?>"
                class="light"/> <?php echo $mapping->getText(); ?></span>
            <?php } ?>
        <?php } ?>
    </div>

    <table class="phipTable">
        <tbody>
            <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) { ?>
            <?php $group = $groupStatus->getComplianceViewGroup(); ?>
            <tr class="headerRow">
                <th colspan="<?php echo $group->pointBased() && $this->hide_status_when_point_based ? 2 : 1 ?>"><?php echo $group->getReportName(); ?></th>
                <?php if($this->requirements) : ?>
                <td class="requirements"><?php echo $this->requirements; ?></td>
                <?php endif ?>
                <?php if($group->pointBased()) { ?>
                <?php if(!$this->hide_status_when_point_based) : ?>
                    <td><?php echo $this->status; ?></td>
                    <?php endif ?>
                <td><?php echo $this->points; ?></td>
                <?php if($this->show_progress) : ?>
                <td>Progress</td>
                <?php endif ?>
                <td><?php echo $this->max_points; ?></td>
                <?php } else { ?>
                <td><?php echo $this->status; ?></td>
                <td class="empty" colspan="2"></td>
                <?php } ?>

                <?php if($group->hasLinks()) { ?>
                <td><?php echo $this->links; ?></td>
                <?php } else { ?>
                <td class="empty"></td>
                <?php } ?>
            </tr>
            <?php $number = 1; ?>
            <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) { ?>
                <?php $skip = false;
                foreach($this->callbacks as $call) {
                    if(!$call($viewStatus)) {
                        $skip = true;
                        break;
                    }
                } ?>

                <?php if($skip) continue; ?>
                <?php $view = $viewStatus->getComplianceView(); ?>
                <tr>
                    <td class="resource"
                        colspan="<?php echo $group->pointBased() && $this->hide_status_when_point_based ? 2 : 1 ?>"><?php echo $number++.". {$view->getReportName()}"; ?></td>
                    <?php if($this->requirements) : ?>
                    <td class="requirements"><?php echo $view->getStatusSummary(ComplianceStatus::COMPLIANT); ?></td>
                    <?php endif ?>
                    <?php if($group->pointBased()) { ?>
                    <?php if(!$this->hide_status_when_point_based) : ?>
                        <td class="status"><img src="<?php echo $viewStatus->getLight(); ?>" class="light"/></td>
                        <?php endif ?>
                    <td class="points">
                        <?php echo $this->getFormattedPoints($viewStatus->getPoints()); ?>
                    </td>
                    <?php if($this->show_progress) : ?>
                        <?php $progressPercentage = round($viewStatus->getPoints() / $view->getMaximumNumberOfPoints() * 100);  ?>
                        <td class="points">
                            <div class="progress" style="margin: auto;">
                                <div class="bar" style="width: <?php echo $progressPercentage; ?>%; background-image: linear-gradient(to bottom, #97cc7c, #8ecd6e)">
                                    <span style="color: #000000"><?php echo $progressPercentage; ?>%</span>
                                </div>
                            </div>
                        </td>
                    <?php endif ?>
                    <?php $this->printMaximumNumberOfPoints($view) ?>
                    <?php } else { ?>
                    <td class="status"><img src="<?php echo $viewStatus->getLight(); ?>" class="light"/></td>
                    <td class="empty" colspan="2"></td>
                    <?php } ?>

                    <?php if($group->hasLinks()) { ?>
                    <td class="links">
                        <?php foreach($view->getLinks() as $link) { ?>
                        <?php echo $link->getHTML(); ?>
                        <?php } ?>
                    </td>
                    <?php } else { ?>
                    <td class="empty"></td>
                    <?php } ?>
                </tr>
                <?php } ?>
                <?php if($group->pointBased() && $this->show_group_totals) : ?>
                    <tr>
                        <td colspan="<?php echo $this->requirements ? 2 : ($this->hide_status_when_point_based ? 2 : 1) ?>"><strong style="padding-left:25px;">Total</strong></td>
                        <?php if(!$this->hide_status_when_point_based) : ?>
                            <td class="status">
                                <img src="<?php echo $groupStatus->getLight(); ?>" class="light" />
                            </td>
                        <?php endif ?>
                        <?php if($this->show_progress) : ?>
                            <td></td>
                        <?php endif ?>
                        <td class="points">
                            <?php echo $this->getFormattedPoints($groupStatus->getPoints()) ?>
                        </td>
                        <?php $this->printMaximumNumberOfGroupPoints($group) ?>
                        <td class="empty"></td>
                    </tr>
                <?php endif ?>

            <?php } ?>

            <?php $this->printCustomRows($status); ?>
        </tbody>

    </table>
    <div id="clientNote">
        <?php $this->printClientNote() ?>
    </div>
    <?php
    }

    protected function printMaximumNumberOfGroupPoints(ComplianceViewGroup $group)
    {
        ?>
        <td class="maxpoints">
            <?php echo $this->getFormattedPoints($group->getMaximumNumberOfPoints()) ?>
        </td>
        <?php
    }

    protected function printMaximumNumberOfPoints(ComplianceView $view)
    {
        ?>
        <td class="maxpoints">
            <?php echo $this->getFormattedPoints($view->getMaximumNumberOfPoints()); ?>
        </td>
        <?php
    }

    protected function printCustomRows(ComplianceProgramStatus $status)
    {

    }

    public $show_group_totals = false;
    public $format_points_as_money = false;
    public $links = 'Links';
    public $max_points = 'Maximum Points';
    public $show_progress = false;
    public $page_heading = 'My Incentive Report Card';
    public $points = 'Points';
    public $requirements = 'Requirements';
    public $status = 'Status';
    public $hide_status_when_point_based = false;
    private $callbacks = array();
}