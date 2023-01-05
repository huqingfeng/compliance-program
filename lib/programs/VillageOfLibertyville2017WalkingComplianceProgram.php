<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/jawbone/lib/model/jawboneApi.php';
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/moves/lib/model/movesApi.php';

class VillageOfLibertyville2017RangeStepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $stopThreshold, $pointsPer)
    {
        $this->setDateRange($startDate, $endDate);
        $this->threshold = $threshold;
        $this->stopThreshold = $stopThreshold;
        $this->pointsPer = $pointsPer;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "hmi_range_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Range Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $fitbitData = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        $points = 0;
        $totalSteps = 0;
        foreach($fitbitData['dates'] as $date => $steps) {
            if($date < $this->getStartDate('Y-m-d') || $date > $this->getEndDate('Y-m-d')) continue;
            if($steps >= $this->threshold && ($steps < $this->stopThreshold || $this->stopThreshold == null)) {
                $points += $this->pointsPer;
            }

            $totalSteps += $steps;
        }

        $status = new ComplianceViewStatus($this, null, $points);

        $status->setAttribute('total_steps', $totalSteps);

        return $status;
    }
}


class VillageOfLibertyville2017WalkingComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowComment(false);
        $printer->setShowCompliant(false);

        $printer->addStatusFieldCallback('Steps 05/01/2017-06/18/2017', function(ComplianceProgramStatus $status) {
            $quarterOneStatus = $status->getComplianceViewStatus('quarter_one_5000');

            return $quarterOneStatus->getAttribute('total_steps');
        });

        return $printer;
    }

    public function loadGroups()
    {
        $quarterOneGroup = new ComplianceViewGroup('quarter_one_group');
        $quarterOneGroup->setPointsRequiredForCompliance(75);

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $quarterOne5K = new VillageOfLibertyville2017RangeStepsComplianceView($startDate, $endDate, 5000, 7500, 1);
        $quarterOne5K->setReportName('Quarter One 5000 steps');
        $quarterOne5K->setName('quarter_one_5000');
        $quarterOne5K->setAttribute('points_per_activity', '1');
        $quarterOne5K->addLink(new Link('My Steps', '/content/fitness-data-individual'));
        $quarterOneGroup->addComplianceView($quarterOne5K);

        $quarterOne75K = new VillageOfLibertyville2017RangeStepsComplianceView($startDate, $endDate, 7500, 10000, 2);
        $quarterOne75K->setReportName('Quarter One 7500 steps');
        $quarterOne75K->setName('quarter_one_7500');
        $quarterOne75K->setAttribute('points_per_activity', '2');
        $quarterOneGroup->addComplianceView($quarterOne75K);

        $quarterOne10K = new VillageOfLibertyville2017RangeStepsComplianceView($startDate, $endDate, 10000, null, 3);
        $quarterOne10K->setReportName('Quarter One 10000 steps');
        $quarterOne10K->setName('quarter_one_10000');
        $quarterOne10K->setAttribute('points_per_activity', '3');
        $quarterOneGroup->addComplianceView($quarterOne10K);

        $this->addComplianceViewGroup($quarterOneGroup);
        


    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new VillageOfLibertyville2017WalkingComplianceProgramReportPrinter();

        return $printer;
    }

}


class VillageOfLibertyville2017WalkingComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {

        $quarterOneGroupStatus = $status->getComplianceViewGroupStatus('quarter_one_group');

        $quarterOne5kStatus = $quarterOneGroupStatus->getComplianceViewStatus('quarter_one_5000');
        $quarterOne75kStatus = $quarterOneGroupStatus->getComplianceViewStatus('quarter_one_7500');
        $quarterOne10kStatus = $quarterOneGroupStatus->getComplianceViewStatus('quarter_one_10000');
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
                background-color:#2F73BC;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
                height:46px;
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

            #legendText {
                text-align:center;
                background-color:#FFF;
                font-weight:normal;
                color:#434343;
                font-size:12pt;
                font-weight:bold;
                float:left;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
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

            .section {
                height:16px;
                color: white;
                background-color:#436EEE;
            }

            .requirement {
                width: 350px;
            }

            #programTable {
                border-collapse: collapse;
                margin:0 auto;
            }

            #programTable tr th, #programTable tr td{
                border:1px solid #26B000;
            }

        </style>


        <table class="phipTable">
            <tbody>
            <tr><th colspan="6" class="headerRow">2017 Walking Challenge</th></tr>

            <tr>
                <td colspan="6" style="font-size: 10pt;">
                    The Village of Libertyville is encouraging you to become physically active each day! Employees can track
                    their steps by using their personal device. Points will be awarded when you click the "My Steps" link
                    based on your steps. Health experts agree that to gain the most health benefits from a step program—individual's
                    goal should be to achieve 10,000 steps a day. Let’s get moving as we journey on the path to wellness!
                </td>
            </tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Total Points Earned</td>
                <td class="center">Tracking Method</td>
            </tr>


            <tr>
                <td rowspan="3">
                    &nbsp;Walking Challenge<br /><br />
                    &nbsp;<strong>5/1/17—6/18/17</strong>
                </td>
                <td class="requirement">Walk 5,000 Steps per Day</td>
                <td class="center"><?php echo $quarterOne5kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterOne5kStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <?php foreach($quarterOne5kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk 7,500 Steps per Day</td>
                <td class="center"><?php echo $quarterOne75kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterOne75kStatus->getPoints() ?></td>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk 10,000 Steps per Day</td>
                <td class="center"><?php echo $quarterOne10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterOne10kStatus->getPoints() ?></td>
            </tr>

            <tr class="headerRow">
                <td class="center" colspan="2">Total Points Earned </td>
                <td class="center" colspan="2"><?php echo $quarterOneGroupStatus->getPoints(); ?></td>
                <td class="center"></td>
            </tr>

            <tr>
                <td colspan="6" >
                    <div>
                        <div style="font-size: 12pt; text-align: center; margin-bottom: 20px;">
                            <span style="margin-left: 50px;"><a href="/content/ucan-fitbit-individual">Sync Fitbit</a></span>
                            <span style="margin-left: 50px;"><a href="/standalone/jawbone">Sync Jawbone</a></span>
                            <span style="margin-left: 50px;"><a href="/standalone/moves">Sync Moves App</a></span>
                        </div>

                        <div style="text-align: center; font-size:11pt;">
                            2017 Walking Challenge <br />
                            May 1, 2017 – June 18, 2017
                        </div>

                        <div style="font-size: 10pt;">
                            <ul>
                                <li>
                                    Walk to earn points May 1, 2017 through June 18, 2017
                                </li>
                                <li>
                                    Points are earned individually. The Top 10 point earning individuals will automatically be
                                    entered to win a $100 gift card! Two winners will be selected.
                                </li>
                                <li>
                                    Team Challenge: Individuals participating in the walking challenge will automatically be
                                    placed on a Mystery Team. To keep everyone guessing (and walking) until the very last
                                    day, mystery teams will be revealed at the end of the challenge.
                                    All members of the Top point earning team will win a $100 gift card.
                                </li>
                            </ul>
                        </div>


                    </div>
                </td>
            </tr>


            </tbody>
        </table>
        <?php
    }

}