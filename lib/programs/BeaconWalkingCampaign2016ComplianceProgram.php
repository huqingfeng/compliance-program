<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class BeaconWalkingCampaignExerciseToSteps extends CompleteArbitraryActivityComplianceView
{
    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        $points = 0;

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            $activityConversion = isset($answers[158]) && isset(self::$activityStepsPerMinute[$answers[158]->getAnswer()]) ?
                self::$activityStepsPerMinute[$answers[158]->getAnswer()] : 0;

            $minutesExercised = isset($answers[1]) ? (int)$answers[1]->getAnswer() : 0;

            $points += $minutesExercised * $activityConversion;
        }

        return new ComplianceViewStatus(
            $this,
            null,
            $points
        );
    }

    public static function getActivityStepsPerMinute()
    {
        return self::$activityStepsPerMinute;
    }
    private static $activityStepsPerMinute = array(
        'Aerobic Dancing Class'	=> 127,
        'Aerobic Fitness Class' => 181,
        'Aerobics, Low Impact' => 125,
        'Aerobics, Step' => 153,
        'Backpacking' => 181,
        'Badminton, Casual' => 131,
        'Badminton, Competitve' => 203,
        'Ballet Dancing' => 120,
        'Baseball' => 130,
        'Basketball, Game' => 145,
        'Basketball, Recreational' => 130,
        'Bicycling, Easy Pace' => 130,
        'Bicycling, Moderate Pace' => 170,
        'Bicycling, Vigerous' => 200,
        'Billards / Pool' => 76,
        'Bowling' => 71,
        'Bowling on the Wii' => 61,
        'Boxing, Non-competitive' => 131,
        'Boxing, Competitive' => 222,
        'Calisthenics' => 106,
        'Canoeing' => 91,
        'Cheerleading' => 100,
        'Children\'s Playground Game' => 136,
        'Circuit Training' => 199,
        'Climbing, Rock / Mountain' => 270,
        'Cooking' => 61,
        'Croquet' => 76,
        'Dancing, Class' => 109,
        'Dancing, Salsa / Country / Swing' => 109,
        'Dancing, Party' => 109,
        'Drill Team' => 153,
        'Eletronic Sports, Wii / PS3' => 91,
        'Elliptical Trainer' => 203,
        'Fencing' => 182,
        'Firewood-carrying / chopping' => 60,
        'Fishing' => 91,
        'Football' => 199,
        'Frisbee' => 91,
        'Gardening' => 80,
        'Golf, Carrying Clubs' => 109,
        'Golf, Powered Cart' => 80,
        'Grocery Shopping' => 67,
        'Gymnastics' => 121,
        'Handball' => 348,
        'Hiking' => 172,
        'Hiking, Orienteering' => 232,
        'Hockey, Field and Ice' => 240,
        'Home / Auto Repair' => 91,
        'Horseback Riding' => 90,
        'Horseshoes' => 71,
        'Housework, Light' => 72,
        'Ice Skating, General' => 84,
        'Ice Skating, Moderate' => 122,
        'In-line Skating' => 190,
        'Jogging' => 181,
        'Judo & Karate' => 236,
        'Jumping Rope, Fast' => 300,
        'Jumping Rope, Moderate' => 250,
        'Kayaking' => 152,
        'Kickball' => 212,
        'Kickboxing' => 290,
        'Lacrosse' => 242,
        'Minature Golf' => 91,
        'Mopping' => 60,
        'Mowing Lawn' => 120,
        'Painting (Room)' => 78,
        'Pilates' => 91,
        'Punching Bag' => 180,
        'Racking Lawn / Leaves' => 121,
        'Racquetball, Casual' => 181,
        'Racquetball, Competitive' => 254,
        'Rock Climbing' => 244,
        'Rollerblading' => 156,
        'Rowing' => 147,
        'Rowing Machine' => 212,
        'Rugby' => 303,
        'Running, 12 minute mile' => 178,
        'Running, 10 minute mile' => 222,
        'Running, 8 minute mile' => 278,
        'Sailing, Boat and Board' => 91,
        'Scrubbing Floors' => 71,
        'Scuba Diving' => 203,
        'Shopping' => 70,
        'Shoveling Snow' => 145,
        'Skateboarding' => 102,
        'Skeeball' => 52,
        'Skiing, Light / Moderate' => 109,
        'Skiing, Cross-Country' => 114,
        'Sledding' => 158,
        'Snowboarding' => 182,
        'Snowmobling' => 106,
        'Snowshoeing' => 181,
        'Soccer, Recreational' => 181,
        'Soccer, Competitve' => 145,
        'Softball' => 152,
        'Spinning' => 200,
        'Squash' => 348,
        'Stair Climbing, Machine' => 200,
        'Stair Climbing, Down Stairs' => 71,
        'Stair Climbing, Up Stairs' => 181,
        'Stretching' => 15,
        'Surfing' => 91,
        'Swimming, Backstroke' => 181,
        'Swimming, Butterfly' => 272,
        'Swimming, Freestyle' => 181,
        'Swimming, Leisure' => 174,
        'Swimming, Treading Water' => 116,
        'Table Tennis' => 120,
        'Tae Bow' => 250,
        'Tae Kwon Do' => 290,
        'Tai Chi' => 40,
        'Tennis' => 200,
        'Trampoline' => 90,
        'Vacuuming' => 94,
        'Volleyball' => 91,
        'Walking, Stroll' => 61,
        'Walking, Average' => 84,
        'Washing a Car' => 71,
        'Water Aerobics' => 116,
        'Water Polo'    =>	303,
        'Water Skiing'  => 145,
        'Waxing a Car'  => 80,
        'Weight Lifting' => 67,
        'Wrestling' =>	145,
        'Yard Work' =>	89,
        'Yoga' => 45
    );

}

class BeaconWalkingCampaignFitbitComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setDateRange($startDate, $endDate);
    }

    public function getDefaultName()
    {
        return 'fitbit';
    }

    public function getDefaultReportName()
    {
        return 'Fitbit';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        try {
            $dataRefreshed = refresh_fitbit_data($user);
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }

        $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        $status = new ComplianceViewStatus($this, null, isset($data['total_steps']) ? $data['total_steps'] : 0);
        $status->setAttribute('data_refreshed', $dataRefreshed);

        return $status;
    }
}

class BeaconWalkingCampaignAdminPrinter extends BasicComplianceProgramAdminReportPrinter
{
    public function __construct($record)
    {
        $this->setEmployeeOnly(true);

        $this->setShowUserFields(true, true, true);

        $this->setShowComment(false, false, false);
        $this->setShowCompliant(false, false, false);
        $this->setShowPoints(true, false, false);

        $this->addMultipleCallbackFields(function (User $user) use($record) {
            if($teamRecord = $record->getTeamByUserId($user->id)) {
                return array(
                    'team_name'  => "#{$teamRecord['id']}: {$teamRecord['name']}"
                );
            } else {
                return array(
                    'team_name'  => ''
                );
            }
        });
    }

    protected function postProcess(array $data)
    {
        $teams = array();

        foreach($data as $containerKey => $dataContainer) {
            $row = $dataContainer['data'];

            if(!isset($teams[$row['team_name']])) {
                $teams[$row['team_name']] = array();
            }

            $teams[$row['team_name']][] = (int) $row['Compliance Program - Points'];
        }

        foreach($data as $containerKey => $dataContainer) {
            $row = $dataContainer['data'];

            $teamName = $row['team_name'];

            unset($row['team_name']);

            $teamData = $teams[$teamName];

            $teamAverage = array_sum($teamData) / count($teamData);

            $row['Team Name'] = $teamName;
            $row['Team Average Points'] = $teamName ? round($teamAverage) : '';

            $data[$containerKey]['data'] = $row;
        }

        return $data;
    }
}

class BeaconWalkingCampaign2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $record = $this->getComplianceProgramRecord();

        return new BeaconWalkingCampaignAdminPrinter($record);
    }

    public function getTeamDashboardPrinter()
    {
        return new BeaconWalkingCampaignTeamDashboardPrinter();
    }

    public function getBuddyDashboardPrinter()
    {
        return new BeaconWalkingCampaignBuddyDashboardPrinter();
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new BeaconWalkingCampaignProgramReportPrinter();
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
                'allow_teams'                => true,
                'team_members_minimum'       => 2,
                'team_members_maximum'       => 10,
                'team_members_invite_end_date' => sfConfig::get('compliance_team_members_invite_end_date', false),
                'team_create_end_date'      => sfConfig::get('compliance_create_new_team_end_date', false),
                'team_leaderboard'           => true,
                'force_spouse_with_employee'  => true,
                'force_spouse_with_employee_excluded_spouses'   => array(2766323, 2705898, 2619885, 2771017, 2765698, 2765646, 2776442, 2766814, 2766217, 2769655, 2768453, 2765252, 2765697, 2768008),
                'points_label'               => 'steps'
            ) + $this->options;

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $operations = new ComplianceViewGroup('operations', 'Exercise Log');

        $fitbitView = new BeaconWalkingCampaignFitbitComplianceView($startDate, $endDate);
        $fitbitView->setReportName('FitBit Syncing');
        $fitbitView->setName('fitbit');
        $fitbitView->addLink(new Link('Give Permission to Sync', '/standalone/demo/authorizeFitbitForCHPDemo'));

        $operations->addComplianceView($fitbitView);

        $resView = new SumStepsInArbitraryActivityComplianceView($startDate, $endDate, 332, 110);
        $resView->setReportName('Enter Steps Manually');
        $resView->setName('steps');
        $operations->addComplianceView($resView);

        $minutesToSteps = new BeaconWalkingCampaignExerciseToSteps($startDate, $endDate, 437, 0);
        $minutesToSteps->setName('minutes_steps');
        $minutesToSteps->setReportName('Convert Active/Exercise Minutes to Steps');
        $operations->addComplianceView($minutesToSteps);

        $this->addComplianceViewGroup($operations);
    }

    public function getActionTemplateCustomizations()
    {
        ob_start();

        ?>
        <style type="text/css">
            #tab-choose-buddy { display:none; }

            .total-steps { display:none; }
        </style>

        <script type="text/html" id="new-buddy-instructions">
            <h4>You haven't joined a team yet.</h4>
            <p>To create a new team, select "Create a New Team" above. If you
                wish to join an existing team, contact the captain of the team
                and ask them to invite you.</p>
        </script>

        <script type="text/javascript">
            $(function() {
                $('#compliance_tabs').before(
                    '<p style="text-align:center"><img src="/resources/4917/Go_Banner_FINAL.png" alt="" /></p>' +
                    '<p style="font-weight:bold">Your campaign runs <?php echo $this->getStartDate('m/d/Y') ?> ' +
                    'through <?php echo $this->getEndDate('m/d/Y') ?>.</p>' +
                    '<p>Whether you choose to challenge yourself or team up ' +
                    'with a group of coworkers for additional motivation, we ' +
                    'hope that you will be encouraged to "Get up and Go!" and ' +
                    'gain a sense of accomplishment in knowing you are ' +
                    'increasing your activity level.</p>'
                );

                $('#no-team-or-buddy-instructions').html($('#new-buddy-instructions').html());
            });
        </script>
        <?php

        return ob_get_clean();
    }

    public function getEmailContent(array $variables)
    {
        return array(
            'team_request' => array(
                'subject' => 'You have been sent a team invitation.',
                'body'    => <<<EOT
You have been invited by your coworker to participate in the Get Up and Go activity campaign.

- To accept the invitation
- Login to Circlewell.com and click on the Get Up and Go button to start logging your activity!
- On your Mark...Get Set...Go!!
EOT
            ),

            'buddy_request' => array(
                'subject' => 'You have been sent a buddy invitation.',
                'body'    => <<<EOT
You have received a buddy invitation to participate in the Get Up and Go activity campaign.

- To accept the invitation
- Login to Circlewell.com and click on the Get Up and Go button to start logging your activity!
- On your Mark...Get Set...Go!!
EOT
            )
        );
    }
}

class CHPTeamlessWalkingCampaignComplianceProgram extends BeaconWalkingCampaign2016ComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->options['allow_teams'] = false;
        $this->options['team_members_minimum'] = 0;
        $this->options['team_members_maximum'] = 0;
    }
}

class CHPTwoPersonTeamWalkingCampaignComplianceProgram extends BeaconWalkingCampaign2016ComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->options['team_members_minimum'] = 2;
        $this->options['team_members_maximum'] = 2;
    }
}

class CHPThreePersonTeamWalkingCampaignComplianceProgram extends BeaconWalkingCampaign2016ComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->options['team_members_minimum'] = 2;
        $this->options['team_members_maximum'] = 3;
    }
}

class CHPInfinitePersonTeamWalkingCampaignComplianceProgram extends BeaconWalkingCampaign2016ComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        unset($this->options['team_members_maximum']);
    }
}

class SumStepsInArbitraryActivityComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function __construct($startDate, $endDate, $activityId, $questionId)
    {
        $this->activityId = $activityId;
        $this->questionId = $questionId;

        parent::__construct($startDate, $endDate);
    }

    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        $steps = 0;

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->questionId])) {
                $steps += (int)$answers[$this->questionId]->getAnswer();
            }
        }

        return new ComplianceViewStatus(
            $this,
            null,
            $steps
        );
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $activityId;
    private $questionId;
}

class BeaconWalkingCampaignProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        if($status->getComplianceViewStatus('fitbit')->getAttribute('data_refreshed')) {
            $status->getComplianceViewStatus('fitbit')->getComplianceView()->emptyLinks();
            $status->getComplianceViewStatus('fitbit')->getComplianceView()->addLink(new Link('View Steps', '/standalone/demo/showFitbitStepsForCHPDemo'));
        }

        $stepsPerMinute = json_encode(
            $status->getComplianceProgram()
                ->getComplianceView('minutes_steps')
                ->getActivityStepsPerMinute()
        );

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

        <script id="activity_steps_per_minute" type="text/plain">
            <?php echo $stepsPerMinute ?>
        </script>

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
            <?php $this->printViewRow($status, 'minutes_steps', 3) ?>
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

class BeaconWalkingCampaignTeamDashboardPrinter extends BeaconWalkingCampaignBuddyAndTeamDashboardPrinter implements TeamDashboardPrinter
{
    public function printReport($teamName, array $programStatuses)
    {
        ?>
        <div class="page-header">
            <h5><?php echo $teamName ?></h5>
        </div>
        <?php
        $this->_printReport($programStatuses, 'Team Member');
    }
}

class BeaconWalkingCampaignBuddyDashboardPrinter extends BeaconWalkingCampaignBuddyAndTeamDashboardPrinter implements BuddyDashboardPrinter
{

    public function printReport(ComplianceProgramStatus $localStatus, ComplianceProgramStatus $userStatus)
    {
        $this->_printReport(array($localStatus, $userStatus), 'Buddy');
    }
}


abstract class BeaconWalkingCampaignBuddyAndTeamDashboardPrinter
{
    protected function _printReport(array $statuses, $heading = 'User')
    {
        $totalPoints = 0;
        $totalDailyAvg = 0;
        $numStatuses = count($statuses);
        ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th><?php echo $heading ?></th>
                <th>Total Steps</th>
                <th>Average Daily Steps</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($statuses as $status) : ?>
                <?php $avgDaily = $status->getComplianceProgram()->getAverageDailySteps($status) ?>
                <?php $statusPoints = $status->getPoints(); ?>
                <tr>
                    <td><?php echo $status->getUser() ?></td>
                    <td><?php echo number_format($statusPoints) ?></td>
                    <td><?php echo number_format($avgDaily) ?></td>
                </tr>
                <?php $totalDailyAvg += $avgDaily ?>
                <?php $totalPoints += $statusPoints; ?>
            <?php endforeach ?>
            </tbody>
            <tfoot>
            <tr>
                <th>Grand Totals</th>
                <th colspan="2"><?php echo number_format($totalPoints) ?></th>
            </tr>
            <tr>
                <th>Average (By member)</th>
                <th><?php echo $numStatuses ? number_format(round($totalPoints / $numStatuses, 2)) : 0 ?></th>
                <th><?php echo $numStatuses ? number_format(round($totalDailyAvg / $numStatuses, 2)) : 0 ?></th>
            </tr>
            </tfoot>
        </table>
        <?php
    }
}
