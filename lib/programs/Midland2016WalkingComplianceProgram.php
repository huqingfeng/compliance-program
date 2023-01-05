<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class Midland2016RangeStepsComplianceView extends DateBasedComplianceView
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
        $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        $points = 0;

        foreach($data['dates'] as $date) {
            if($date >= $this->threshold && ($date < $this->stopThreshold || $this->stopThreshold == null)) {
                $points += $this->pointsPer;
            }
        }

        return new ComplianceViewStatus($this, null, $points);
    }
}


class Midland2016WalkingComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowComment(false);
        $printer->setShowCompliant(false);

        $printer->addStatusFieldCallback('diff_q2_and_q1', function(ComplianceProgramStatus $status) {
            $diff = $status->getComplianceViewGroupStatus('quarter_two_group')->getPoints() -  $status->getComplianceViewGroupStatus('quarter_one_group')->getPoints();

            return abs($diff);
        });

        $printer->addStatusFieldCallback('diff_q3_and_q2', function(ComplianceProgramStatus $status) {
            $diff = $status->getComplianceViewGroupStatus('quarter_three_group')->getPoints() -  $status->getComplianceViewGroupStatus('quarter_two_group')->getPoints();

            return abs($diff);
        });

        $printer->addStatusFieldCallback('diff_q4_and_q3', function(ComplianceProgramStatus $status) {
            $diff = $status->getComplianceViewGroupStatus('quarter_four_group')->getPoints() -  $status->getComplianceViewGroupStatus('quarter_three_group')->getPoints();

            return abs($diff);
        });

        return $printer;
    }

    public function loadGroups()
    {
        $quarterOneGroup = new ComplianceViewGroup('quarter_one_group');
        $quarterOneGroup->setPointsRequiredForCompliance(75);

        $quarterOneStart = '2016-01-01';
        $quarterOneEnd = '2016-03-31';

        $quarterOne6K = new Midland2016RangeStepsComplianceView($quarterOneStart, $quarterOneEnd, 6000, 8000, 1);
        $quarterOne6K->setReportName('Quarter One 6000 steps');
        $quarterOne6K->setName('quarter_one_6000');
        $quarterOne6K->setAttribute('points_per_activity', '1');
        $quarterOne6K->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $quarterOneGroup->addComplianceView($quarterOne6K);

        $quarterOne8K = new Midland2016RangeStepsComplianceView($quarterOneStart, $quarterOneEnd, 8000, 10000, 2);
        $quarterOne8K->setReportName('Quarter One 8000 steps');
        $quarterOne8K->setName('quarter_one_8000');
        $quarterOne8K->setAttribute('points_per_activity', '2');
        $quarterOneGroup->addComplianceView($quarterOne8K);

        $quarterOne10K = new Midland2016RangeStepsComplianceView($quarterOneStart, $quarterOneEnd, 10000, null, 3);
        $quarterOne10K->setReportName('Quarter One 10000 steps');
        $quarterOne10K->setName('quarter_one_10000');
        $quarterOne10K->setAttribute('points_per_activity', '3');
        $quarterOneGroup->addComplianceView($quarterOne10K);

        $this->addComplianceViewGroup($quarterOneGroup);

        $quarterTwoGroup = new ComplianceViewGroup('quarter_two_group');
        $quarterTwoGroup->setPointsRequiredForCompliance(125);

        $quarterTwoStart = '2016-04-01';
        $quarterTwoEnd = '2016-06-30';

        $quarterTwo6K = new Midland2016RangeStepsComplianceView($quarterTwoStart, $quarterTwoEnd, 6000, 8000, 1);
        $quarterTwo6K->setReportName('Quarter Two 6000 steps');
        $quarterTwo6K->setName('quarter_two_6000');
        $quarterTwo6K->setAttribute('points_per_activity', '1');
        $quarterTwo6K->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $quarterTwoGroup->addComplianceView($quarterTwo6K);

        $quarterTwo8K = new Midland2016RangeStepsComplianceView($quarterTwoStart, $quarterTwoEnd, 8000, 10000, 2);
        $quarterTwo8K->setReportName('Quarter Two 8000 steps');
        $quarterTwo8K->setName('quarter_two_8000');
        $quarterTwo8K->setAttribute('points_per_activity', '2');
        $quarterTwoGroup->addComplianceView($quarterTwo8K);

        $quarterTwo10K = new Midland2016RangeStepsComplianceView($quarterTwoStart, $quarterTwoEnd, 10000, null, 3);
        $quarterTwo10K->setReportName('Quarter Two 10000 steps');
        $quarterTwo10K->setName('quarter_two_10000');
        $quarterTwo10K->setAttribute('points_per_activity', '3');
        $quarterTwoGroup->addComplianceView($quarterTwo10K);

        $this->addComplianceViewGroup($quarterTwoGroup);

        $quarterThreeGroup = new ComplianceViewGroup('quarter_three_group');
        $quarterThreeGroup->setPointsRequiredForCompliance(175);

        $quarterThreeStart = '2016-07-01';
        $quarterThreeEnd = '2016-09-30';

        $quarterThree6K = new Midland2016RangeStepsComplianceView($quarterThreeStart, $quarterThreeEnd, 6000, 8000, 1);
        $quarterThree6K->setReportName('Quarter Three 6000 steps');
        $quarterThree6K->setName('quarter_three_6000');
        $quarterThree6K->setAttribute('points_per_activity', '1');
        $quarterThree6K->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $quarterThreeGroup->addComplianceView($quarterThree6K);

        $quarterThree8K = new Midland2016RangeStepsComplianceView($quarterThreeStart, $quarterThreeEnd, 8000, 10000, 2);
        $quarterThree8K->setReportName('Quarter Three 8000 steps');
        $quarterThree8K->setName('quarter_three_8000');
        $quarterThree8K->setAttribute('points_per_activity', '2');
        $quarterThreeGroup->addComplianceView($quarterThree8K);

        $quarterThree10K = new Midland2016RangeStepsComplianceView($quarterThreeStart, $quarterThreeEnd, 10000, null, 3);
        $quarterThree10K->setReportName('Quarter Three 10000 steps');
        $quarterThree10K->setName('quarter_three_10000');
        $quarterThree10K->setAttribute('points_per_activity', '3');
        $quarterThreeGroup->addComplianceView($quarterThree10K);

        $this->addComplianceViewGroup($quarterThreeGroup);


        $quarterFourGroup = new ComplianceViewGroup('quarter_four_group');
        $quarterFourGroup->setPointsRequiredForCompliance(200);

        $quarterFourStart = '2016-10-01';
        $quarterFourEnd = '2016-12-31';

        $quarterFour6K = new Midland2016RangeStepsComplianceView($quarterFourStart, $quarterFourEnd, 6000, 8000, 1);
        $quarterFour6K->setReportName('Quarter Four 6000 steps');
        $quarterFour6K->setName('quarter_four_6000');
        $quarterFour6K->setAttribute('points_per_activity', '1');
        $quarterFour6K->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $quarterFourGroup->addComplianceView($quarterFour6K);

        $quarterFour8K = new Midland2016RangeStepsComplianceView($quarterFourStart, $quarterFourEnd, 8000, 10000, 2);
        $quarterFour8K->setReportName('Quarter Four 8000 steps');
        $quarterFour8K->setName('quarter_four_8000');
        $quarterFour8K->setAttribute('points_per_activity', '2');
        $quarterFourGroup->addComplianceView($quarterFour8K);

        $quarterFour10K = new Midland2016RangeStepsComplianceView($quarterFourStart, $quarterFourEnd, 10000, null, 3);
        $quarterFour10K->setReportName('Quarter Four 10000 steps');
        $quarterFour10K->setName('quarter_four_10000');
        $quarterFour10K->setAttribute('points_per_activity', '3');
        $quarterFourGroup->addComplianceView($quarterFour10K);

        $this->addComplianceViewGroup($quarterFourGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new Midland2016WalkingComplianceProgramReportPrinter();

        return $printer;
    }

}


class Midland2016WalkingComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {

        $quarterOneGroupStatus = $status->getComplianceViewGroupStatus('quarter_one_group');

        $quarterOne6kStatus = $quarterOneGroupStatus->getComplianceViewStatus('quarter_one_6000');
        $quarterOne8kStatus = $quarterOneGroupStatus->getComplianceViewStatus('quarter_one_8000');
        $quarterOne10kStatus = $quarterOneGroupStatus->getComplianceViewStatus('quarter_one_10000');


        $quarterTwoGroupStatus = $status->getComplianceViewGroupStatus('quarter_two_group');

        $quarterTwo6kStatus = $quarterTwoGroupStatus->getComplianceViewStatus('quarter_two_6000');
        $quarterTwo8kStatus = $quarterTwoGroupStatus->getComplianceViewStatus('quarter_two_8000');
        $quarterTwo10kStatus = $quarterTwoGroupStatus->getComplianceViewStatus('quarter_two_10000');


        $quarterThreeGroupStatus = $status->getComplianceViewGroupStatus('quarter_three_group');

        $quarterThree6kStatus = $quarterThreeGroupStatus->getComplianceViewStatus('quarter_three_6000');
        $quarterThree8kStatus = $quarterThreeGroupStatus->getComplianceViewStatus('quarter_three_8000');
        $quarterThree10kStatus = $quarterThreeGroupStatus->getComplianceViewStatus('quarter_three_10000');

        $quarterFourGroupStatus = $status->getComplianceViewGroupStatus('quarter_four_group');

        $quarterFour6kStatus = $quarterFourGroupStatus->getComplianceViewStatus('quarter_four_6000');
        $quarterFour8kStatus = $quarterFourGroupStatus->getComplianceViewStatus('quarter_four_8000');
        $quarterFour10kStatus = $quarterFourGroupStatus->getComplianceViewStatus('quarter_four_10000');

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
            <tr><th colspan="6" class="headerRow">2016 Midland Step Challenge</th></tr>

            <tr><td colspan="6" style="font-size: 10pt;">Midland is encouraging you become physically active each day! Employees can track their steps by using
                    their personal FitBit device. Points will be awarded when you click the “My Steps” link based on your steps.
                    Health experts agree, to gain the most health benefits from a step program - individual’s goal should be to
                    achieve 10,000 steps a day. Let’s get moving as we journey on the path to wellness! </td></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Total Points Earned</td>
                <td class="center">Tracking Method</td>
            </tr>


            <tr>
                <td rowspan="3">
                    &nbsp;Individual Walking Program <br /><br />
                    &nbsp;<strong>Quarter 1: 01/01/2016 - 03/31/2016</strong>
                </td>
                <td class="requirement">Walk an average of 6,000 steps/day </td>
                <td class="center"><?php echo $quarterOne6kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterOne6kStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <?php foreach($quarterOne6kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 8,000 steps/day </td>
                <td class="center"><?php echo $quarterOne8kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterOne8kStatus->getPoints() ?></td>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 10,000 steps/day </td>
                <td class="center"><?php echo $quarterOne10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterOne10kStatus->getPoints() ?></td>
            </tr>

            <tr class="headerRow">
                <td class="center" colspan="2">Q1 Total Points Earned </td>
                <td class="center" colspan="2"><?php echo $quarterOneGroupStatus->getPoints(); ?></td>
                <td class="center"><img src="<?php echo $quarterOneGroupStatus->getLight(); ?>" class="light" alt=""/></td>
            </tr>

            <tr>
                <td rowspan="3">
                    &nbsp;Individual Walking Program <br /><br />
                    &nbsp;<strong>Quarter 2: 04/01/2016 - 06/30/2016</strong>
                </td>
                <td class="requirement">Walk an average of 6,000 steps/day </td>
                <td class="center"><?php echo $quarterTwo6kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterTwo6kStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <?php foreach($quarterTwo6kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 8,000 steps/day </td>
                <td class="center"><?php echo $quarterTwo8kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterTwo8kStatus->getPoints() ?></td>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 10,000 steps/day </td>
                <td class="center"><?php echo $quarterTwo10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterTwo10kStatus->getPoints() ?></td>
            </tr>

            <tr class="headerRow">
                <td class="center" colspan="2">Q2 Total Points Earned </td>
                <td class="center" colspan="2"><?php echo $quarterTwoGroupStatus->getPoints(); ?></td>
                <td class="center"><img src="<?php echo $quarterTwoGroupStatus->getLight(); ?>" class="light" alt=""/></td>
            </tr>


            <tr>
                <td rowspan="3">
                    &nbsp;Individual Walking Program <br /><br />
                    &nbsp;<strong>Quarter 3: 07/01/2016 - 09/30/2016</strong>
                </td>
                <td class="requirement">Walk an average of 6,000 steps/day </td>
                <td class="center"><?php echo $quarterThree6kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterThree6kStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <?php foreach($quarterThree6kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 8,000 steps/day </td>
                <td class="center"><?php echo $quarterThree8kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterThree8kStatus->getPoints() ?></td>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 10,000 steps/day </td>
                <td class="center"><?php echo $quarterThree10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterThree10kStatus->getPoints() ?></td>
            </tr>

            <tr class="headerRow">
                <td class="center" colspan="2">Q3 Total Points Earned </td>
                <td class="center" colspan="2"><?php echo $quarterThreeGroupStatus->getPoints(); ?></td>
                <td class="center"><img src="<?php echo $quarterThreeGroupStatus->getLight(); ?>" class="light" alt=""/></td>
            </tr>


            <tr>
                <td rowspan="3">
                    &nbsp;Individual Walking Program <br /><br />
                    &nbsp;<strong>Quarter 4: 10/01/2016 - 12/31/2016</strong>
                </td>
                <td class="requirement">Walk an average of 6,000 steps/day </td>
                <td class="center"><?php echo $quarterFour6kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterFour6kStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <?php foreach($quarterFour6kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 8,000 steps/day </td>
                <td class="center"><?php echo $quarterFour8kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterFour8kStatus->getPoints() ?></td>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 10,000 steps/day </td>
                <td class="center"><?php echo $quarterFour10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $quarterFour10kStatus->getPoints() ?></td>
            </tr>

            <tr class="headerRow">
                <td class="center" colspan="2">Q4 Total Points Earned </td>
                <td class="center" colspan="2"><?php echo $quarterFourGroupStatus->getPoints(); ?></td>
                <td class="center"><img src="<?php echo $quarterFourGroupStatus->getLight(); ?>" class="light" alt=""/></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align: center; font-size: 10pt;">
                    Quarterly Raffles! Win incentives/prizes!! Here’s how to be entered to win: <br /><br />
                    Q1: Earn at least 75 pts between January 1 - March 31, 2016<br />
                    Q2: Earn at least 125 pts between April 1 - June 30, 2016<br />
                    Q3: Earn at least 175 pts between July 1 - September 30, 2016<br />
                    Q4: Earn at least 200 pts between October 1 - December 31, 2016<br /><br />
                    Each Quarter is increased to encourage you to increase your activity.<br /><br />
                    The “Most Improved” Award: We will be looking for our most improved participants on a
                    quarterly basis! Step up your game to Step up the Fun!

                </td>
            </tr>


            </tbody>
        </table>
    <?php
    }

}