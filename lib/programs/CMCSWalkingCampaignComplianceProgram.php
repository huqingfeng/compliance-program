<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

$_SESSION['redirect_dashboard_link'] = '/compliance_programs/redirectToCMCSDashboard';

class CMCSWalkingCampaignExerciseToSteps extends CompleteArbitraryActivityComplianceView
{
    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        $points = 0;

        $minutesStepsData = array();

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            $activityConversion = isset($answers[123]) && isset(self::$activityStepsPerMinute[$answers[123]->getAnswer()]) ?
                self::$activityStepsPerMinute[$answers[123]->getAnswer()] : 0;

            $minutesExercised = isset($answers[1]) ? (int)$answers[1]->getAnswer() : 0;

            $points += $minutesExercised * $activityConversion;

            $minutesStepsData[$record->getDate('Y-m-d')] = $minutesExercised * $activityConversion;
        }

        $status =  new ComplianceViewStatus($this, null, $points);
        $status->setAttribute('minutes_steps_data', $minutesStepsData);

        return $status;
    }

    public static function getActivityStepsPerMinute()
    {
        return self::$activityStepsPerMinute;
    }

    private static $activityStepsPerMinute = array(
        'Aerobics (low impact)' => 145,
        'Aerobics (intense)' => 203,
        'Badminton, casual' => 131,
        'Badminton, competitive' => 203,
        'Basketball (leisurely)' => 116,
        'Basketball (game)' => 230,
        'Bicycling, leisurely (10-11.9 mph)' => 116,
        'Bicycling, moderate, (12-13.9 mph)' => 200,
        'Bicycling vigorous, (14-15.9 mph)' => 250,
        'Bicycling, stationary' => 203,
        'Bowling' => 87,
        'Boxing' => 348,
        'Canoeing, light' => 87,
        'Chopping wood' => 174,
        'Circuit training' => 232,
        'Dancing' => 131,
        'Elliptical trainer' => 203,
        'Firewood, carrying' => 145,
        'Firewood, sawing with handsaw' => 217,
        'Firewood, stacking' => 145,
        'Football' => 260,
        'Gardening, light' => 116,
        'Gardening, heavy' => 174,
        'Gardening, weeding' => 131,
        'Golfing, without a cart' => 131,
        'Golfing, with a cart' => 101,
        'Grocery shopping' => 67,
        'Handball' => 348,
        'Hiking, general' => 172,
        'Hiking, 10-20 pound load' => 217,
        'Hiking, 21-42 pound load' => 232,
        'Horseback riding' => 116,
        'Horseback riding, trotting' => 188,
        'Housework, light' => 72,
        'Housework, mopping floors' => 101,
        'Housework, scrubbing the floor' => 110,
        'Housework, vacuuming' => 101,
        'Housework, washing windows' => 87,
        'Ice skating' => 203,
        'Judo' => 290,
        'Jumping rope, fast' => 348,
        'Jumping rope, moderate' => 290,
        'Karate' => 290,
        'Kickboxing' => 290,
        'Mowing the lawn' => 160,
        'Orienteering' => 260,
        'Painting' => 131,
        'Pilates' => 101,
        'Ping-Pong' => 116,
        'Racquetball, casual' => 203,
        'Racquetball, competitive' => 290,
        'Raking leaves' => 125,
        'Roller skating' => 203,
        'Rowing, light' => 101,
        'Rowing, moderate' => 203,
        'Running, 10 mph (6 min/mile)' => 463,
        'Running, 8 mph (7.5 min/mile)' => 391,
        'Running, 6 mph (10 min/mile)' => 290,
        'Running, 5 mph (12 min/mile)' => 232,
        'Scuba diving' => 203,
        'Skiing, cross-country, intense' => 260,
        'Skiing, cross-country, moderate' => 232,
        'Skiing, cross-country, slow' => 203,
        'Skiing, downhill' => 174,
        'Skiing, water' => 174,
        'Snow shoveling' => 174,
        'Snowboarding, light' => 150,
        'Snowboarding, moderate' => 182,
        'Soccer, recreational' => 203,
        'Soccer, competitive' => 290,
        'Softball' => 145,
        'Squash' => 348,
        'Stair climbing, machine' => 260,
        'Stair climbing, moderate' => 334,
        'Stair climbing, slow' => 232,
        'Stair climbing, vigorous' => 434,
        'Stretching' => 72,
        'Swimming, backstroke' => 203,
        'Swimming, breaststroke' => 290,
        'Swimming, butterfly' => 319,
        'Swimming, freestyle' => 203,
        'Swimming, leisure' => 174,
        'Swimming, treading water' => 116,
        'Tae kwon do' => 290,
        'Tennis, doubles' => 174,
        'Tennis, singles' => 232,
        'Trampoline' => 101,
        'Volleyball, leisurely' => 87,
        'Volleyball, game' => 232,
        'Walking, leisurely' => 100,
        'Walking, moderate' => 110,
        'Walking, vigorous' => 110,
        'Washing the car' => 87,
        'Water aerobics' => 116,
        'Waxing the car' => 131,
        'Weight training, moderate' => 87,
        'Weight training, vigorous' => 174,
        'Yard work' => 145,
        'Yoga' => 72,
        'Zumba' => 203

    );
}

class CMCSWalkingCampaignFitbitComplianceView extends DateBasedComplianceView
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

class CMCSWalkingCampaignAdminPrinter extends BasicComplianceProgramAdminReportPrinter
{
    public function __construct($record)
    {
        $this->setShowUserFields(true, true, true);
        $this->setShowUserContactFields(null, null, true);

        $this->setShowComment(false, false, false);
        $this->setShowCompliant(false, false, false);
        $this->setShowPoints(false, false, false);
        $this->setShowEmailAddresses(true, true, false);

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

        $this->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) use($record) {
            $program = $record->getComplianceProgram();

            $user = $status->getUser();

            $fitbitStepsData = $status->getComplianceViewStatus('fitbit')->getAttribute('fitbit_steps_data');
            $manualStepsData = $status->getComplianceViewStatus('steps')->getAttribute('manual_steps_data');
            $minutesStepsData = $status->getComplianceViewStatus('minutes_steps')->getAttribute('minutes_steps_data');

            $totalStepsData = array();

            $days = $status->getComplianceProgram()->getDaysInRange();

            foreach($days as $date) {
                if(!isset($totalStepsData[$date])) $totalStepsData[$date] = 0;

                if(isset($fitbitStepsData[$date])) $totalStepsData[$date] += $fitbitStepsData[$date];

                if(isset($manualStepsData[$date])) $totalStepsData[$date] += $manualStepsData[$date];

                if(isset($minutesStepsData[$date])) $totalStepsData[$date] += $minutesStepsData[$date];
            }

            foreach($totalStepsData as $date => $steps) {
                $data[sprintf('Daily Steps - %s', $date)] = $steps;
            }

            $totalTeamPoints = 0;
            if($teamRecord = $record->getTeamByUserId($user->id)) {
                $teamOwner = $teamRecord['owner_user_id'];

                $invitationAccepted = false;
                $teamNumber = 0;
                foreach($teamRecord['users'] as $teamUserData) {
                    if($teamUserData['id'] == $user->id && $teamUserData['accepted']) {
                        $invitationAccepted = true;
                    }

                    $teamUser = UserTable::getInstance()->find($teamUserData['id']);

                    $program->setActiveUser($teamUser);

                    $teamMemberStatus = $program->getStatus();

                    $teamName = $teamRecord['name'];

                    $totalTeamPoints += $teamMemberStatus->getPoints();

                    $teamNumber++;
                }

                $teamAverageSteps = round($totalTeamPoints/$teamNumber);
            }

            $data['Participant Total Steps'] = $status->getPoints();
            $data['Participant Daily Steps Average'] = $status->getComplianceProgram()->getAverageDailySteps($status);
            $data['Invitation Accept (Y/N)'] = isset($invitationAccepted) && $invitationAccepted ? 'Yes' : 'No';
            $data['Team Captain'] = isset($teamOwner) && $teamOwner == $user->id ? 'Yes' : 'No';
            $data['Team Total Steps'] = $totalTeamPoints;
            $data['Team Name']  = isset($teamName) ? $teamName : '';
            $data['Team Average Steps']  = isset($teamAverageSteps) ? $teamAverageSteps : '';

            return $data;
        });
    }

}

class CMCSWalkingCampaignComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $record = $this->getComplianceProgramRecord();

        return new CMCSWalkingCampaignAdminPrinter($record);
    }

    public function getTeamDashboardPrinter()
    {
        return new CMCSWalkingCampaignTeamDashboardPrinter();
    }

    public function getBuddyDashboardPrinter()
    {
        return new CMCSWalkingCampaignBuddyDashboardPrinter();
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new CMCSWalkingCampaignProgramReportPrinter();
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
            'points_label'               => 'steps'
        ) + $this->options;

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $operations = new ComplianceViewGroup('operations', 'Exercise Log');

        $fitbitView = new CMCSWalkingCampaignFitbitComplianceView($startDate, $endDate);

        $fitbitView->setReportName('FitBit Syncing');
        $fitbitView->setName('fitbit');
        $fitbitView->addLink(new Link('Give Permission to Sync', '/standalone/demo/authorizeFitbitForCMCS'));


        $operations->addComplianceView($fitbitView);

        $resView = new CMCSSumStepsInArbitraryActivityComplianceView($startDate, $endDate, 601, 110);
        $resView->setReportName('Enter Steps Manually');
        $resView->setName('steps');
        $operations->addComplianceView($resView);

        $minutesToSteps = new CMCSWalkingCampaignExerciseToSteps($startDate, $endDate, 604, 0);
        $minutesToSteps->setName('minutes_steps');
        $minutesToSteps->setReportName('Convert Active/Exercise Minutes to Steps');
        $operations->addComplianceView($minutesToSteps);

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

                $('#dashboard a').each(function(){
                    if ($(this).text() == "Manage Trackers") {
                        var prefix = $('.item-home').attr('href');
                        prefix = prefix.split("/");
                        var url = $('.item-fitness-tracking-wms3').attr('href') + "&prefix=" + prefix[2];
                        if (url != undefined) {
                            $(this).attr('href', url);
                        }

                    }
                });

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

class CMCSTeamlessWalkingCampaignComplianceProgram extends CMCSWalkingCampaignComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->options['allow_teams'] = false;
        $this->options['team_members_minimum'] = 0;
        $this->options['team_members_maximum'] = 0;
    }
}

class CMCSTwoPersonTeamWalkingCampaignComplianceProgram extends CMCSWalkingCampaignComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->options['team_members_minimum'] = 2;
        $this->options['team_members_maximum'] = 2;
    }
}

class CMCSThreePersonTeamWalkingCampaignComplianceProgram extends CMCSWalkingCampaignComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->options['team_members_minimum'] = 2;
        $this->options['team_members_maximum'] = 3;
    }
}

class CMCSFiftyPersonTeamWalkingCampaignComplianceProgram extends CMCSWalkingCampaignComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->options['team_members_minimum'] = 2;
        $this->options['team_members_maximum'] = 50;
    }
}

class CMCSInfinitePersonTeamWalkingCampaignComplianceProgram extends CMCSWalkingCampaignComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        unset($this->options['team_members_maximum']);
    }
}

class CMCSWms2WalkingCampaignComplianceProgram extends CMCSWalkingCampaignComplianceProgram
{
    public function loadGroups()
    {
        $this->options = array(
                'allow_teams'                => true,
                'team_members_minimum'       => sfConfig::get('compliance_team_members_minimum', 2),
                'team_members_maximum'       => sfConfig::get('compliance_team_members_maximum', 10),
                'team_members_invite_end_date' => sfConfig::get('compliance_team_members_invite_end_date', false),
                'team_create_end_date'      => sfConfig::get('compliance_create_new_team_end_date', false),
                'team_leaderboard'           => true,
                'force_spouse_with_employee'  => true,
                'points_label'               => 'steps'
            ) + $this->options;

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $operations = new ComplianceViewGroup('operations', 'Exercise Log');

        $fitbitView = new CMCSWalkingCampaignFitbitComplianceView($startDate, $endDate);
        $fitbitView->useWms2 = true;
        $fitbitView->setReportName('Device Syncing (e.g. FitBit, Withings (Nokia), Moves)');
        $fitbitView->setName('fitbit');
        $fitbitView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $view = $status->getComplianceView();
        });

        $operations->addComplianceView($fitbitView);

        $resView = new CMCSSumStepsInArbitraryActivityComplianceView($startDate, $endDate, 601, 110);
        $resView->setReportName('Enter Steps Manually');
        $resView->setName('steps');
        $operations->addComplianceView($resView);

        $minutesToSteps = new CMCSWalkingCampaignExerciseToSteps($startDate, $endDate, 604, 0);
        $minutesToSteps->setName('minutes_steps');
        $minutesToSteps->setReportName('Convert Active/Exercise Minutes to Steps');
        $operations->addComplianceView($minutesToSteps);

        $this->addComplianceViewGroup($operations);
    }
}

class CMCSSumStepsInArbitraryActivityComplianceView extends CompleteActivityComplianceView
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
        $manualStepsData = array();

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->questionId])) {
                $steps += (int)$answers[$this->questionId]->getAnswer();
                $manualStepsData[$record->getDate('Y-m-d')] = (int)$answers[$this->questionId]->getAnswer();
            }
        }

        $status = new ComplianceViewStatus($this, null, $steps);
        $status->setAttribute('manual_steps_data', $manualStepsData);

        return $status;
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $activityId;
    private $questionId;
}

class CMCSWalkingCampaignProgramReportPrinter implements ComplianceProgramReportPrinter
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

class CMCSWalkingCampaignTeamDashboardPrinter extends CMCSWalkingCampaignBuddyAndTeamDashboardPrinter implements TeamDashboardPrinter
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

class CMCSWalkingCampaignBuddyDashboardPrinter extends CMCSWalkingCampaignBuddyAndTeamDashboardPrinter implements BuddyDashboardPrinter
{

    public function printReport(ComplianceProgramStatus $localStatus, ComplianceProgramStatus $userStatus)
    {
        $this->_printReport(array($localStatus, $userStatus), 'Buddy');
    }
}


abstract class CMCSWalkingCampaignBuddyAndTeamDashboardPrinter
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
