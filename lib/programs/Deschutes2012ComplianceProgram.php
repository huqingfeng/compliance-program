<?php

class Deschutes2012ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'Health risk assessment (HRA) actions required by  '.date('F d, Y', $programEnd));

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', 'http://www.deschutes.org/Administration/Deschutes-Onsite-Clinic-%28DOC%29/Health-Risk-Assessments.aspx'));
        $screeningView->addLink(new Link('Dates & Sites', '/content/wellness_screening'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete Health Assessment');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Deschutes2012ComplianceProgramReportPrinter();
    }
}

class Deschutes2012ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        ?>
    <style type="text/css">
        .phipTable ul, .phipTable li {
            margin-top:0px;
            margin-bottom:0px;
            padding-top:0px;
            padding-bottom:0px;
        }

        .pageHeading {
            font-weight:bold;
            text-align:center;
            margin-bottom:20px;
        }

        .phipTable {
            width:100%;
            border-collapse:collapse;
            font-size:8pt;
        }

        .phipTable th, .phipTable td {
            border:1px solid #000000;
            padding:2px;
        }

        .phipTable .headerRow {
            background-color:#002AAE;
            font-weight:normal;
            color:#FFFFFF;
            font-size:10pt;
        }

        .phipTable .headerRow th {
            text-align:left;
            font-weight:normal;
        }

        .phipTable .headerRow td {
            text-align:center;
        }

        .phipTable .links {
            text-align:center;
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

        .center {
            text-align:center;
        }

        .right {
            text-align:right;
        }

        #legend, #legend tr, #legend td {
            padding:0px;
            margin:0px;
        }

        #legend td {

            padding-bottom:5px;
        }

        #legendText {
            text-align:center;
            background-color:#002AAE;
            font-weight:normal;
            color:#FFFFFF;
            font-size:12pt;
            margin-bottom:5px;
        }

        .legendEntry {
            width:160px;
            float:left;
            text-align:center;
            padding-left:2px;
        }
    </style>
    <div class="pageHeading">Rewards/To-Do Summary Page</div>
    <p>Hello <?php echo $status->getUser() ?>,</p>
    <p></p>
    <p>Welcome to the Health Risk Assessment Rewards/To-Do summary page.</p>



    <p><strong>By completing Health Risk Assessment actions A and B (below) by January 31, 2012, employees
        will:</strong></p>

    <ul>
        <li>Receive a $65 health insurance premium holiday; a one-time waiver of your monthly payment for health
            insurance coverage in 2012.
        </li>
        <li>Receive a printed report with an analysis of your current health, risks and follow-up tips.</li>
        <li>Have free access (via the DOC), for recommended follow-up steps to improved health.</li>
    </ul>

    <p>In addition, by getting action items A and B done PLUS other actions recommended in your report:
    <ul>
        <li>You will benefit from improvements in health, health care and wellbeing – now and throughout life.</li>
        <li>Your efforts will help you avoid fewer health problems and related expenses each year.</li>
        <li>You may be helping others in many of the same ways through your actions and encouragement along the way.
        </li>
    </ul>
    </p>
    <p>Here are some tips about the table below and using it:
    <ul>
        <li>Use the Action Links in the right column to get things done or for more information.</li>
    </p>
    </ul>
    <p></p>
    <p></p>
    <p></p>
    
    <table class="phipTable" border="1">
        <thead id="legend">
            <tr>
                <td colspan="5">
                    <div id="legendText">Legend</div>
                    <?php
                    foreach($status->getComplianceProgram()->getComplianceStatusMapper()
                                ->getMappings() as $sstatus => $mapping) {
                        $printLegendEntry = false;

                        if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT) {
                            $printLegendEntry = true;
                        } else if($status->getComplianceProgram()->hasPartiallyCompliantStatus()) {
                            $printLegendEntry = true;
                        }

                        if($printLegendEntry) {
                            echo '<div class="legendEntry">';
                            echo '<img src="'.$mapping->getLight().'" class="light" />';
                            echo " = {$mapping->getText()}";
                            echo '</div>';
                        }
                    }
                    ?>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr class="headerRow">
                <th><?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
                <td>Deadline</td>
                <td>Done</td>
                <td>Status</td>
                <td>Links</td>
            </tr>
            <tr>
                <td>A. <?php echo $completeScreeningStatus->getComplianceView()->getReportName() ?></a></td>
                <td class="center">
                    <?php echo $completeScreeningStatus->getComplianceView()->getEndDate('m/d/Y') ?>
                </td>
                <td class="center">
                    <?php echo $completeScreeningStatus->getComment(); ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="links">
                    <?php
                    foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></a></td>
                <td class="center">
                    <?php echo $completeHRAStatus->getComplianceView()->getEndDate('m/d/Y') ?>
                </td>
                <td class="center">
                    <?php echo $completeHRAStatus->getComment(); ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeHRAStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="links">
                    <?php
                    foreach($completeHRAStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td class="right" style="font-size: 7pt;">
                    Health risk assessment (HRA) actions required
                    <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?>
                </td>
                <td></td>
                <td class="center">
                    <?php echo $coreGroupStatus->isCompliant() ? 'Yes' : 'No' ?>
                </td>
                <td class="center">
                    <?php echo $coreGroupStatus->isCompliant() ? 'All Requirements Met' : 'Not Done Yet'; ?>
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <?php
  }
}
