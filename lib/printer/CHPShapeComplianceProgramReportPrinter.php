<?php

class CHPShapeComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $this->printCSS();
        $this->printHeader($status);
        ?>
    <table id="phiptable">
        <tbody>
            <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
            <?php $group = $groupStatus->getComplianceViewGroup() ?>
            <tr class="headerrow">
                <td><?php echo $group->getReportName() ?></td>
                <td>Details</td>
                <td>Status</td>
                <td>I did this</td>
            </tr>
            <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <?php $view = $viewStatus->getComplianceView() ?>
                <tr class="requiredrow view-<?php echo $view->getName() ?>">
                    <td class="requirementscolumn">
                        <strong><?php echo $view->getReportName() ?></strong>
                        <?php if($view->getAttribute('more_info_link')) : ?>
                        <a href="<?php echo $view->getAttribute('more_info_link') ?>">- more info</a>
                        <?php endif ?>
                        <br/>
                        <?php echo $view->getAttribute('about') ?>
                        <br/>
                        <?php echo implode(' ', $view->getLinks()) ?>
                    </td>
                    <td class="myrequirementscolumn">
                        <?php echo $view->getStatusSummary(ComplianceStatus::COMPLIANT) ?>
                    </td>
                    <td class="statuscolumn">
                        <img src="<?php echo $viewStatus->getLight() ?>" alt="" class="status-<?php echo $viewStatus->getStatus() ?>" />
                    </td>
                    <td>
                        <?php if(!$viewStatus->isCompliant() && $view->getAttribute('did_this_link')) : ?>
                        <a href="<?php echo $view->getAttribute('did_this_link') ?>">I did this</a>
                        <?php endif ?>
                        <?php if(!$viewStatus->isCompliant() && $linkAdd = $view->getAttribute('link_add')) : ?>
                        <?php echo $linkAdd ?>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
            <?php endforeach ?>
        </tbody>
    </table>
    <?php
        $this->printFooter($status);
    }

    public function printCSS()
    {
        ?>
    <style type="text/css">
        #phiptable {
            margin:25px 20px 10px;
            border-collapse:collapse;
        }

        #phiptable a {
            color:#CC6633;
            text-decoration:underline;
        }

        #phiptable td {
            padding:3px;
            font-size:.95em;
        }

        #phiptable a:hover {
            color:#691A90;
        }

        #phiptable .nametitle {
            font-size:1.3em;
            font-weight:bold;
        }

        #phiptable .headerrow td {
            background-color:#000000;
            color:#FFFFFF;
            border-left:1px solid #CC6633;
            border-right:1px solid #CC6633;
            font-size:1.1em;
        }

        #phiptable .title {
            font-weight:bold;
        }

        #phiptable tbody td {
            vertical-align:top;
        }

        #phiptable .requiredrow td {
            padding-bottom:10px;
        }

        #phiptable .requirementscolumn {
            width:33%;
            border-right:1px solid #CC6633;
        }

        #phiptable .requirementscolumn a.moreinfo {
            font-style:italic;
        }

        #phiptable .myrequirementscolumn {
            width:30%;
            border-right:1px solid #CC6633;
        }

        #phiptable .statuscolumn {
            width:10%;
            border-right:1px solid #CC6633;
        }

        #phiptable .subnote {
            font-size:.8em;
        }
    </style>
    <?php
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        echo '<p class="nametitle">Hello ', $status->getUser(), '</p>';
    }

    public function printFooter(ComplianceProgramStatus $status)
    {

    }
}