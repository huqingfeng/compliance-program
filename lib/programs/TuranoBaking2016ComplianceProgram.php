<?php

use hpn\steel\query\SelectQuery;


class TuranoBaking2016ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Program');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Power Profile Questionnaire');
        $hraView->setName('complete_hra');
        $hraView->setAttribute('requirement', 'Complete the Health Risk Assessment (HRA) Questionnair');
        $hraView->setAttribute('deadline', '04/29/2016');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('2015 Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('requirement', 'Complete the Biometric Health Screening');
        $screeningView->setAttribute('deadline', '04/29/2016');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/how-to-schedule'));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

    }

    public function getAdminProgramReportPrinter()
    {
        $that = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(true, true, true);
        $printer->setShowUserFields(true, true, true, true, true, true, null, null, true);


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new TuranoBaking2016ComplianceProgramReportPrinter();

        return $printer;
    }


}


class TuranoBaking2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);

        $this->tableHeaders['points_possible'] = 'Maximum Possible Points';
        $this->screeningLinkArea = '<br/><br/>
      Green Range = 10 pts <br/>
      Yellow Range = 5 pts<br/><br/>
      <a href="/content/1094#2aKBHM"> Click here for more info &amp; tips to improve.</a><br/><br/>
      <em>Points appear 5-10 days after screening.</em>
    ';

        $this->addStatusCallbackColumn('Requirement', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            return $view->getAttribute('requirement');
        });

        $this->addStatusCallbackColumn('Points Per Activity', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();
            return $view->getAttribute('points_per_activity');
        });


    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(false);
        $this->setScreeningResultsLink(new Link('Have These Screening Results in the Ideal Zone:', '/content/1094#2aKBHM'));

        ?>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $completeHraStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');

        ?>
        <style type="text/css">
            .pageHeading {
                display:none;
            }

            #altPageHeading {
                font-weight:bold;
                margin-bottom:20px;
                text-align:center;
            }

            .phipTable .headerRow {
                background-color: #0033FF;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
                height:36px;
                text-align: center;
            }

            #legend td {
                padding:8px !important;
            }

            .legendEntry {
                width:auto;
                float:right;
                display:inline-block;
                padding:0 8px;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .links {
                text-align: center;
            }

            .phipTable .links a {
                display:inline-block;
                margin:0 3px;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .deadline, .result {
                width:100px;
                text-align: center;
            }

            .date-completed, .requirement, .status, .tier_hra, .tier_screening, .tier_num, .tier_premium {
                text-align: center;
            }

            #tier_table {
                margin:0 auto;
            }

            #tier_table td{
                padding-right: 20px;
                border-bottom:1px solid black;
                padding-top: 10px;
            }

            #tier_table span {
                color: red;
            }

            #bottom_statement {
                padding-top:20px;
            }

            #tier_total {
                font-weight: bold;
                text-align: center;
            }
        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned
        </script>
        <!-- Text atop report card-->
        <div class="pageHeading">2015 Bison Cares for You Program</div>

        <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

        <table class="phipTable">
            <tbody>
            <tr class="headerRow headerRow-core">
                <th class="center">1. Core Actions Required By 04/29/2016</th>
                <th class="deadline">Deadline</th>
                <th class="date-completed">Date Completed</th>
                <th class="status">Status</th>
                <th class="links">Links</th>
            </tr>
            <tr class="view-complete_hra">
                <td>
                    <strong>A</strong>. <?php echo $completeHraStatus->getComplianceView()->getAttribute('requirement') ?>
                </td>
                <td class="deadline">
                    <?php echo $completeHraStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="date-completed">
                    <?php echo $completeHraStatus->getComment() ?>
                </td>
                <td class="status">
                    <img src="<?php echo $completeHraStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="links">
                    <?php foreach($completeHraStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr class="view-complete_screening">
                <td>
                    <strong>B</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?>
                </td>
                <td class="deadline">
                    <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="date-completed">
                    <?php echo $completeScreeningStatus->getComment() ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="center">
                    <?php foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            </tbody>
        </table>
        <?php
    }


    public $showUserNameInLegend = true;
}
