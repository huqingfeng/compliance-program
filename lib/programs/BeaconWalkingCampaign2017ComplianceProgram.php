<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

$_SESSION['redirect_dashboard_link'] = '/compliance_programs/redirectToBeaconDashboard';

class BeaconWalkingCampaign2017ComplianceProgram extends ComplianceProgram
{

    public function getAdminProgramReportPrinter()
    {
        $record = $this->getComplianceProgramRecord();

        return new BeaconWalkingCampaignAdminPrinter($record);
    }

    public function getTeamDashboardPrinter()
    {
        return new CHPWalkingCampaignTeamDashboardPrinter();
    }

    public function getBuddyDashboardPrinter()
    {
        return new CHPWalkingCampaignBuddyDashboardPrinter();
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new BeaconWalkingCampaign2017ProgramReportPrinter();
    }

    public function getAverageDailySteps(ComplianceProgramStatus $status)
    {
        $startDate = new \DateTime($this->getStartDate('Y-m-d'));
        $today = new \DateTime(date('Y-m-d'));

        $daysToAverage = max(1, $today->diff($startDate)->format('%a'));

        return round($this->summarizeUserStatusForTeamLeaderboard($status) / $daysToAverage);
    }

    public function summarizeUserStatusForTeamLeaderboard(ComplianceProgramStatus $status)
    {
        return $status->getPoints();
    }

    public function loadGroups()
    {
        $this->options = array(
                'allow_teams'                => false,
                'team_members_minimum'       => 2,
                'team_members_maximum'       => 10,
                'team_members_invite_end_date' => sfConfig::get('compliance_team_members_invite_end_date', false),
                'team_create_end_date'      => sfConfig::get('compliance_create_new_team_end_date', false),
                'team_leaderboard'           => false,
                'points_label'               => 'steps'
            ) + $this->options;

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $operations = new ComplianceViewGroup('operations', 'Exercise Log');

        $fitbitView = new BeaconWalkingCampaignFitbitComplianceView($startDate, $endDate);
        $fitbitView->setReportName('FitBit Syncing');
        $fitbitView->setName('fitbit');
        $fitbitView->addLink(new Link('Give Permission to Sync', '/standalone/demo/authorizeFitbitForBeacon'));


        $operations->addComplianceView($fitbitView);

        $resView = new SumStepsInArbitraryActivityComplianceView($startDate, $endDate, 598, 110);
        $resView->setReportName('Enter Steps Manually');
        $resView->setName('steps');
        $operations->addComplianceView($resView);

        $this->addComplianceViewGroup($operations);
    }

    public function getDaysInRange()
    {
        $days = array();

        $startDate = new \DateTime($this->getStartDate('Y-m-d'));
        $endDate = new \DateTime($this->getEndDate('Y-m-d'));

        while($startDate <= $endDate) {
            $days[] = $startDate->format('Y-m-d');

            $startDate->add(new \DateInterval('P1D'));
        }

        return $days;
    }

    public function getOptions() {
        return $this->options;
    }

    public function getActionTemplateCustomizations()
    {
        ob_start();

        ?>
        <style type="text/css">
            #tab-choose-buddy { display:none; }

            .total-steps { display:none; }
        </style>

        <script type="text/javascript">
            $(function() {
                $('#compliance_tabs').before(
                    '<p style="text-align:center"><img src="https://services-cdb8477accec4d03acc6ba4d02428ede.hpn.com/resources/9331/Beacon_Nurses_Day_Header.png" alt="Beacon Walk a Mile in a Nurse\'s Shoes" /></p>' +
                    '<p style="font-weight:bold">Your campaign runs <?php echo $this->getStartDate('m/d/Y') ?> ' +
                    'through <?php echo $this->getEndDate('m/d/Y') ?>.</p>'
                );
                window.setTimeout(function () {
                    $('#beacon-manage').attr("href", $('.list-group-item.item-fitness-tracking-wms3').attr('href'));
                }, 500);
            });

        </script>
        <?php

        return ob_get_clean();
    }
}

class BeaconWalkingCampaign2017ProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        if($status->getComplianceViewStatus('fitbit')->getAttribute('data_refreshed')) {
            $status->getComplianceViewStatus('fitbit')->getComplianceView()->emptyLinks();
            $status->getComplianceViewStatus('fitbit')->getComplianceView()->addLink(new Link('View Steps', '/standalone/demo/showFitbitStepsForCHPDemo'));
        }

        ?>
        <style type="text/css">
            span.view-number {
                width:30px;
                display:inline-block;
            }

            span.name-tip {
                margin-left:50px;
            }

            #dashboard .center, #dashboard th {
                text-align:center;
            }
        </style>

        <h4 id="name-heading"><?php echo $status->getUser() ?></h4>

        <br/>

        <table class="table table-condensed table-striped" id="dashboard">
            <tbody>
            <tr>
                <th style="text-align:left"><?php echo $this->getGroupReportName($status, 'operations') ?></th>
                <th>Number of steps</th>
                <th>Action Links</th>
            </tr>
            <?php $this->printViewRow($status, 'fitbit', 1) ?>
            <?php $this->printViewRow($status, 'steps', 2) ?>
            <tr>
                <th style="text-align:left">Total number of steps</th>
                <td style="text-align:center"><?php echo number_format($status->getPoints()) ?></td>
                <td></td>
            </tr>
            <tr>
                <th style="text-align:left">Average daily steps</th>
                <td style="text-align:center"><?php echo number_format($status->getComplianceProgram()->getAverageDailySteps($status)) ?></td>
                <td></td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    private function printViewRow($status, $name, $number)
    {
        $viewStatus = $status->getComplianceViewStatus($name);
        $view = $viewStatus->getComplianceView();

        ?>
        <tr>
            <td>
                <?php echo sprintf('<span class="view-number">%s.</span> %s', $number, $view->getReportName()) ?>
            </td>
            <td class="center"><?php echo number_format($viewStatus->getPoints()) ?></td>
            <td class="center"><?php echo implode(' ', $view->getLinks()) ?></td>
        </tr>
        <?php
    }

    private function getGroupReportName($status, $group)
    {
        return $status->getComplianceViewGroupStatus($group)->getComplianceViewGroup()->getReportName();
    }
}
