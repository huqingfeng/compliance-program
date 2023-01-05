<?php

class CHPStatusBasedComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function showTotalCompliance($boolean)
    {
        $this->showTotalCompliance = $boolean;
    }

    public function showResult($boolean)
    {
        $this->showResult = $boolean;
    }

    public function setShowMaxPoints($boolean)
    {
        $this->showMaxPoints = $boolean;
    }

    public function setPageHeading($heading)
    {
        $this->pageHeading = $heading;
    }

    public function setPointsHeading($heading)
    {
        $this->pointsHeading = $heading;
    }

    public function setShowLegend($boolean)
    {
        $this->showLegend = $boolean;

        return $this;
    }

    public function setTargetHeader($header)
    {
        $this->targetHeader = $header;

        return $this;
    }

    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        $_cr = Piranha::getInstance()->getContentReferencer();
        $_cr->printContent('110283', array('user' => array('full_name' => $_user->getFullName())));
    }

    public function printClientNotice()
    {
        // Default = nothing
    }

    public function printClientNote()
    {
        global $_user, $_cr;
        $_cr->printContent('112341', array('user' => array('full_name' => $_user->getFullName())));
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        global $_cr, $_user;

        $this->status = $status;
        $this->printCSS();
        ?>
    <div id="clientMessage"><?php $this->printClientMessage() ?></div>
    <div class="pageHeading"><?php echo $this->pageHeading ?></div>
    <div id="clientNotice"><?php $this->printClientNotice() ?></div>
    <?php if($this->showLegend) : ?>
    <div id="legend">
        <div id="legendText">Legend</div>
        <?php foreach($this->getLegendMappings($status) as $sstatus => $mapping) : ?>
        <span class="legendEntry" id="legendEntry<?php echo $sstatus ?>">
          <img src="<?php echo $mapping->getLight() ?>" class="light" alt=""/>
            <?php echo $mapping->getText() ?>
        </span>
        <?php endforeach ?>
    </div>
    <?php endif ?>
    <table class="phipTable">
        <tbody>
            <?php if($this->showTotalCompliance) $this->printTotalStatus() ?>

            <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
            <?php $this->printGroupStatus($groupStatus) ?>
            <?php endforeach ?>

            <?php $this->printCustomRows($status) ?>
        </tbody>
    </table>
    <div id="clientNote"><?php $this->printClientNote() ?></div>
    <?php
        $this->status = null;
    }

    protected function printCustomRows(ComplianceProgramStatus $status)
    {

    }

    private function printGroupStatus(ComplianceViewGroupStatus $groupStatus)
    {
        $group = $groupStatus->getComplianceViewGroup();
        ?>
    <tr class="headerRow <?php echo sprintf('group-%s', Doctrine_Inflector::urlize($group->getName())) ?>">
        <?php if($group->pointBased()) : ?>
        <th><?php echo $group->getReportName(); ?></th>
        <td><?php echo $this->targetHeader ?></td>
        <td><?php echo $this->pointValuesHeader ?></td>
        <?php if($this->showMaxPoints) : ?>
            <td>Maximum Points</td>
            <?php endif ?>
        <td><?php echo $this->pointsHeading ?></td>
        <?php if($this->showResult) echo '<td>'.$this->resultHeading.'</td>' ?>
        <?php else : ?>
        <th><?php echo $group->getReportName(); ?></th>
        <td colspan="<?php echo $this->showMaxPoints ? 3 : 2 ?>"><?php echo $this->requirementsHeader ?></td>
        <td>Your Status</td>
        <?php if($this->showResult) echo '<td class="empty"></td>' ?>
        <?php endif ?>

        <?php if($group->hasLinks()) : ?>
        <td>Links</td>
        <?php else : ?>
        <td class="empty"></td>
        <?php endif ?>
    </tr>
    <?php
        $number = 1;
        $i = 0;
        foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) :
            if($this->showNA || $viewStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                if($group->pointBased()) {
                    $this->printPointBasedViewStatus($viewStatus, $number);
                } else {
                    $this->printViewStatus($viewStatus, $number);
                }

                if($this->printViewNumber($viewStatus->getComplianceView())) {
                    $number++;
                }

                $i++;
            }
        endforeach;
        ?>

    <?php if($i && $group->pointBased()) : ?>
    <tr class="headerRow totalRow <?php echo sprintf('status-%s', $groupStatus->getStatus()) ?> <?php echo sprintf('group-%s', Doctrine_Inflector::urlize($group->getName())) ?>">
        <th>Totals</th>
        <td></td>
        <td></td>
        <?php if($this->showMaxPoints) : ?>
        <td>
            <?php echo $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?>
        </td>
        <?php endif ?>
        <td class="total_points">
            <?php echo $groupStatus->getPoints() ?>
        </td>
        <?php if($this->showResult) echo '<td class="empty">'.$groupStatus->getComment().'</td>' ?>
        <td class="empty"></td>
    </tr>
    <?php endif ?>

    <?php
    }

    /**
     * Implemented to allow SBMF to skip printing numbers on some views. Override
     * to add logic.
     *
     * @param ComplianceView $view
     * @return bool
     */
    protected function printViewNumber(ComplianceView $view)
    {
        return true;
    }

    private function printViewStatus(ComplianceViewStatus $viewStatus, $i)
    {
        $view = $viewStatus->getComplianceView();
        $group = $view->getComplianceViewGroup();
        ?>
    <tr class="<?php echo sprintf('view-%s', $view->getName()) ?>">
        <td class="resource">
            <?php echo sprintf('%s%s', $this->printViewNumber($view) ? "$i. " : '', $view->getReportName()) ?>
        </td>
        <td class="requirements" colspan="<?php echo $this->showMaxPoints ? 3 : 2 ?>"><?php echo $view->getStatusSummary(ComplianceStatus::COMPLIANT); ?></td>
        <td class="status"><img src="<?php echo $viewStatus->getLight(); ?>" alt="" class="light"/></td>
        <?php if($this->showResult) echo '<td class="empty"></td>' ?>
        <?php $this->printViewLinks($view) ?>
    </tr>
    <?php
    }

    protected function pointBasedViewStatusMatchesMapping($viewStatus, $mapping)
    {
        $view = $viewStatus->getComplianceView();

        $spoints = $view->getStatusPointMapper() ? $view->getStatusPointMapper()->getPoints($mapping) : null;

        return $viewStatus->getStatus() == $mapping || $viewStatus->getPoints() == $spoints;
    }

    private function printPointBasedViewStatus(ComplianceViewStatus $viewStatus, $i)
    {
        $j = 0;
        $printedPoints = false;
        $printedResult = false;

        $view = $viewStatus->getComplianceView();
        $mappings = $this->getStatusMappings($view);
        $group = $view->getComplianceViewGroup();

        if(!$this->printViewNumber($view)) {
            $i -= 1;
        }

        foreach($mappings as $sstatus => $mapping) :
            $domClasses = array(
                'statusRow', (!$j ? 'newViewRow' : ''),
                'pointBased', 'statusRow'.$sstatus,
                ($i % 2 ? 'mainRow' : 'alternateRow'),
                sprintf('status-%s', $viewStatus->getStatus()),
                sprintf('view-%s', $view->getName())
            );

            $correctStatus = $this->pointBasedViewStatusMatchesMapping($viewStatus, $sstatus);
            $onLastRow = ($j == count($mappings) - 1);
            ?>
        <tr class="<?php echo implode(' ', $domClasses) ?>">
            <?php if($j < 1) : ?>
            <td class="resource" rowspan="<?php echo count($mappings) ?>">
                <?php echo sprintf('%s%s', $this->printViewNumber($view) ? "$i. " : '', $view->getReportName(true)) ?>
            </td>
            <?php endif ?>
            <td class="summary">
                <?php echo $view->getStatusSummary($sstatus); ?>
            </td>
            <td class="points">
                <?php echo $view->getStatusPointMapper() ? $view->getStatusPointMapper()->getPoints($sstatus) : '' ?>
            </td>

            <?php if($this->showMaxPoints) : ?>
            <td class="points">
                <?php if($view->getStatusPointMapper()) : ?>
                <?php
                $mappingPoints = $view->getStatusPointMapper()->getPoints($sstatus);
                $correctPoints = $view->getMaximumNumberOfPoints() == $mappingPoints;
                if($correctPoints) echo $view->getMaximumNumberOfPoints();
                ?>
                <?php elseif($onLastRow) : ?>
                    <?php echo $view->getMaximumNumberOfPoints(); ?>
                <?php endif ?>
            </td>
            <?php endif; ?>

            <?php
            if(!$printedPoints && ($correctStatus || $onLastRow)) :
                echo '<td class="points your_points">', $viewStatus->getPoints(), '</td>';
                $printedPoints = true;
                if($this->showResult) :
                    echo '<td class="points">', $viewStatus->getComment(), '</td>';
                endif; else :
                echo '<td class="points"></td>';
                if($this->showResult) :
                    echo '<td class="points"></td>';
                endif;
            endif
            ?>
            <?php if(!$j) : ?>
            <?php $this->printViewLinks($view) ?>
            <?php else : ?>
            <td class="empty"></td>
            <?php endif ?>
        </tr>
        <?php
            $j++;
        endforeach;
    }

    private function printViewLinks(ComplianceView $view)
    {
        if($view->getComplianceViewGroup()->hasLinks()) {
            echo '<td class="links">'.implode('<br/>', $view->getLinks()).'</td>';
        } else {
            echo '<td class="empty"></td>';
        }
    }

    protected function printTotalStatus()
    {
        $nameColumns = 3;
        if($this->status->getPoints() === null) $nameColumns++;
        if($this->showResult) $nameColumns++;
        ?>
    <tr class="headerRow">
        <th colspan="<?php echo $nameColumns?>">Program Status</th>
        <td>Your Status</td>
        <td><?php echo $this->pointsHeading ?></td>
        <td></td>
    </tr>
    <tr>
        <td colspan="<?php echo $nameColumns ?>">
            1. Your Status (On <?php echo date('m/d/Y') ?>)
        </td>
        <td class="status">
            <img
                src="<?php echo $this->status->getLight() ?>"
                alt=""
                class="light"
                />
        </td>
        <?php if($this->status->getPoints() !== null) : ?>
        <td class="points"><?php echo $this->status->getPoints() ?></td>
        <?php endif ?>
    </tr>
    <?php
    }

    protected function getLegendMappings()
    {
        $mappings = $this->status
            ->getComplianceProgram()
            ->getComplianceStatusMapper()
            ->getMappings();

        $eligibleMappings = array();
        foreach($mappings as $status => $mapping) {
            $partialAllowed = $this->status
                ->getComplianceProgram()
                ->hasPartiallyCompliantStatus();
            $partial = $status == ComplianceStatus::PARTIALLY_COMPLIANT;

            if($partialAllowed || !$partial) {
                $eligibleMappings[$status] = $mapping;
            }
        }

        return $eligibleMappings;
    }

    protected function getStatusMappings(ComplianceView $view)
    {
        $eligibleMappings = array();
        foreach($this->getLegendMappings() as $sstatus => $mapping) {
            if($view->getStatusSummary($sstatus) !== null) {
                $eligibleMappings[$sstatus] = $mapping;
            }
        }

        return $eligibleMappings;
    }

    public function printCSS()
    {
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
            border:2px solid #D7D7D7;
            font-size:0.86em;
        }

        .phipTable th, .phipTable td {
            padding:2px;
        }

        .phipTable .headerRow {
            background-color:#42669A;
            color:#FFFFFF;
            font-weight:normal;
        }

        .phipTable .headerRow th {
            text-align:left;
            padding:8px;
            font-weight:normal;
        }

        .phipTable .headerRow td {
            text-align:center;
            padding:10px;
        }

        .phipTable .links, .phipTable .points, .phipTable .maxpoints {
            text-align:center;
        }

        .phipTable .your_points {
            font-weight:bold;
        }

        .phipTable .links {
            width:80px;
            font-size:0.75em;
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

        .status, .summary {
            text-align:center;
        }

        .resource {
            width:200px;
            background-color:#FFFFFF;
            color:#345A92;
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

        .pointBased .summary, .pointBased .points {
            font-size:0.75em
        }

        tr.pointBased td {
            padding:0px;
        }

        .phipTable tr.newViewRow {
            border-top:2px solid #D7D7D7;
            margin-bottom:5px;
        }

            <?php if($this->doColor) : ?>
        .statusRow1 {
            background-color:#FFFDBD;
            color:#000000;
        }

        .statusRow2 {
            background-color:#DDEBF4;
            color:#000000;
        }

        .statusRow3 {
            background-color:#BEE3FE;
            color:#000000;
        }

        .statusRow4 {
            background-color:#BEE3FE;
            color:#000000;
        }
            <?php endif ?>

        #clientNotice {
            font-size:1.0em;
            color:#FF0000;
        }
    </style>
    <?php
    }

    public function setDoColor($boolean)
    {
        $this->doColor = $boolean;
    }

    public function setShowNA($boolean)
    {
        $this->showNA = $boolean;
    }

    protected $resultHeading = 'Your Result';
    private $showNA = true;
    private $doColor = true;
    private $showMaxPoints = true;
    private $showLegend = true;
    private $targetHeader = 'Target';
    private $showTotalCompliance = false;
    private $showResult = false;
    protected $status = null;
    private $pageHeading = 'My Incentive Report Card';
    private $pointsHeading = 'Your Points';
    public $requirementsHeader = 'Requirements';
    public $pointValuesHeader = 'Point Values';
}