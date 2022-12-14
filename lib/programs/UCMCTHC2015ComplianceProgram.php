<?php

use hpn\wms\model\UCMCTHCModel;
use hpn\steel\query\SelectQuery;

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class UCMCWalkingCampaignFitbitComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $points)
    {
        $this->setDateRange($startDate, $endDate);

        $this->threshold = $threshold;
        $this->points = $points;
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
          refresh_fitbit_data($user);
        } catch (Exception $e) {
          error_log($e->getMessage());
          error_log($e->getTraceAsString());
        }

        $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        $startDate = new \DateTime($this->getStartDate('Y-m-d'));
        $endDate = new \DateTime($this->getEndDate('Y-m-d'));

        $days = array();

        while($startDate->format('Y-m-d') <= $endDate->format('Y-m-d')) {
            $days[$startDate->format('Y-m-d')] = $startDate->format('N');

            $startDate->add(new \DateInterval('P1D'));
        }

        $weeks = array();
        $week = array();
        $first = true;
        foreach($days as $d => $dow) {
            if ($first) {
                $first = false;

                for ($i = 1; $i < $dow; $i++) {
                    $week[] = false;
                }
            }

            $week[] = $d;

            if ($dow == 7) {
                $weeks[] = $week;
                $week = array();
            }
        }

        if (count($week)) {
            while(count($week) < 7) {
                $week[] = false;
            }

            $weeks[] = $week;
        }


        $totalSteps = 0;
        $totalPoints = 0;
        $thisWeekSteps = 0;
        $todaySteps = 0;

        foreach($weeks as $days){
            $weeklySteps = 0;
            foreach($days as $day) {
                if(isset($data['dates'][$day])) {
                    $weeklySteps += $data['dates'][$day];
                    $totalSteps += $data['dates'][$day];

                    if($day == date('Y-m-d')) {
                        $thisWeekSteps = $weeklySteps;
                        $todaySteps = $data['dates'][$day];
                    }
                }
            }

            if($weeklySteps >= $this->threshold) $totalPoints += $this->points;
        }

        $status = new ComplianceViewStatus(
            $this,
            null,
            $totalPoints,
            $thisWeekSteps);
        
        $status->setAttribute('total_steps', $totalSteps);
        $status->setAttribute('today_steps', $todaySteps);

        return $status;
    }

    private $threshold;
    private $points;
}

class UCMCTHC2015ComplianceProgram extends ComplianceProgram
{
    public function handleInvalidUser(sfActions $actions)
    {
        $actions->getUser()->setNoticeFlash('Sorry, 2016 Total Health Challenge has ended');

        $actions->redirect('/');
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        $userIds = SelectQuery::create()
            ->from('users u')
            ->select('u.id')
            ->leftJoin('compliance_program_record_user_registrations r')
            ->on('r.user_id = u.id AND r.compliance_program_record_id = ?', array(self::UCMC_TOTAL_HEALTH_CHALLENGE_RECORD_ID))
            ->leftJoin('compliance_program_record_team_users t')
            ->on('t.user_id = u.id AND t.compliance_program_record_id = ?', array(self::UCMC_TOTAL_HEALTH_CHALLENGE_RECORD_ID))
            ->leftJoin('compliance_program_record_buddies b1')
            ->on('b1.first_user_id = u.id AND b1.compliance_program_record_id = ?', array(self::UCMC_TOTAL_HEALTH_CHALLENGE_RECORD_ID))
            ->leftJoin('compliance_program_record_buddies b2')
            ->on('b2.second_user_id = u.id AND b2.compliance_program_record_id = ?', array(self::UCMC_TOTAL_HEALTH_CHALLENGE_RECORD_ID))
            ->where('(r.id IS NOT NULL OR t.id IS NOT NULL OR b1.id IS NOT NULL OR b2.id IS NOT NULL)')
            ->hydrateScalar()
            ->execute();

        $this->setBoundUserIds($userIds->toArray(), ComplianceProgram::MODE_ALL);

        parent::preQuery($query, $withViews);
    }

    public function getTeamDashboardPrinter()
    {
        return new UCMCTHC2015TeamDashboardPrinter();
    }

    public function getBuddyDashboardPrinter()
    {
        return new UCMCTHC2015BuddyDashboardPrinter();
    }

    public function getRegistrationForm()
    {
        return new UCMCTHC2015RegistrationForm();
    }

    public function getRegistrationFormPrinter()
    {
        return new UCMCTHC2015RegistrationFormPrinter();
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new UCMCTHC2015ProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $program = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(null, null, null, null, null, null, null, true);

        $printer->setShowUserContactFields(false, true, true);

        $printer->setShowEmailAddresses(true, true, false);

        $printer->setShowUserInsuranceTypes(false, false);

        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(true, false, false);
        $printer->setShowPoints(true, false, false);

        $record = $this->getComplianceProgramRecord();

        $printer->addCallbackField('Total Health Challenge Age', function(User $user) use ($program) {
            $appointmentPreDate = $program->getPreAppointmentDate($user);

            return $appointmentPreDate ? $user->getAge($appointmentPreDate) : '';
        });

        $printer->addCallbackField('gender', function(User $user) {
            return $user->gender;
        });

        $printer->addMultipleCallbackFields(function (User $user) use($record) {
            if($teamRecord = $record->getTeamByUserId($user->id)) {
                return array(
                    'team_name'         => "#{$teamRecord['id']}: {$teamRecord['name']}",
                    'team_owner'        => (string) UserTable::getInstance()->find($teamRecord['owner_user_id']),
                    'buddy_pair'        => '',
                    'buddy_pair_status' => ''
                );
            } elseif($buddyRecord = $record->getBuddy($user->id)) {
                $buddyUser = UserTable::getInstance()->find($buddyRecord['buddy_user_id']);

                return array(
                    'team_name'         => '',
                    'team_owner'        => '',
                    'buddy_pair'        => $buddyUser ? "#{$buddyRecord['id']}: {$buddyUser}" : '',
                    'buddy_pair_status' => $buddyUser && $buddyRecord['accepted'] ? 'Complete' : 'Pending'
                );
            } else {
                return array(
                    'team_name'         => '',
                    'team_owner'        => '',
                    'buddy_pair'        => '',
                    'buddy_pair_status' => ''
                );
            }
        });

        $printer->addMultipleCallbackFields(function(User $user) use($record) {
            $registration = $record->getRegistrationRecord($user->id);

            return array(
                'registered' => $registration ? 1 : 0,
                'department' => isset($registration['department']) ? $registration['department'] : ''
            );
        });

        $printer->addMultipleCallbackFields(function (User $user) use($record, $program) {
            $prePostFields = array(
                'chest',
                'waist',
                'hips',
                'thigh'
            );

            $ret = array();

            $preData = $program->getPreScreeningData($user);
            $postData = $program->getPostScreeningData($user);
            $assembledData = $program->getAssembledScreeningData($preData, $postData);

            $ret["pre_date"] = isset($preData["date"]) ? $preData["date"] : '';
            $ret["post_date"] = isset($postData["date"]) ? $postData["date"] : '';

            $ret["pre_systolic"] = isset($preData["systolic"]) ? $preData["systolic"] : '';
            $ret["pre_diastolic"] = isset($preData["diastolic"]) ? $preData["diastolic"] : '';

            $ret["post_systolic"] = isset($postData["systolic"]) ? $postData["systolic"] : '';
            $ret["post_diastolic"] = isset($postData["diastolic"]) ? $postData["diastolic"] : '';

            $ret["blood_pressure_points"] = isset($assembledData['points']["systolic"]) ?
                $assembledData['points']["systolic"] : 0;


            $ret["pre_weight"] = isset($preData["weight"]) ? $preData["weight"] : '';
            $ret["post_weight"] = isset($postData["weight"]) ? $postData["weight"] : '';
            $ret["pre_post_points_weight"] = isset($assembledData['points']["weight"]) ?
                $assembledData['points']["weight"] : 0;

            $ret["pre_height"] = isset($preData["height"]) ? $preData["height"] : '';
            $ret["post_height"] = isset($postData["height"]) ? $postData["height"] : '';

            $ret["pre_body_fat_method"] = isset($preData["body_fat_method"]) ? $preData["body_fat_method"] : '';
            $ret["post_body_fat_method"] = isset($postData["body_fat_method"]) ? $postData["body_fat_method"] : '';

            $ret["pre_bodyfat"] = isset($preData["bodyfat"]) ? $preData["bodyfat"] : '';
            $ret["post_bodyfat"] = isset($postData["bodyfat"]) ? $postData["bodyfat"] : '';
            $ret["pre_post_points_bodyfat"] = isset($assembledData['points']["bodyfat"]) ?
                $assembledData['points']["bodyfat"] : 0;

            foreach($prePostFields as $field) {
                $ret["pre_$field"] = isset($preData[$field]) ? $preData[$field] : '';
                $ret["post_$field"] = isset($postData[$field]) ? $postData[$field] : '';
                $ret["pre_post_points_$field"] = isset($assembledData['points'][$field]) ?
                    $assembledData['points'][$field] : 0;
            }

            return $ret;
        });


        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) use ($program, $record) {
            return array(
                'aerobic_minutes'          => $status->getComplianceViewStatus('aerobic_exercise')->getAttribute('minutes', 0),
                'resistance_exercise'     => $status->getComplianceViewStatus('resistance_exercise')->getAttribute('minutes', 0),
                'exercise_points'          => $status->getComplianceViewStatus('exercise')->getPoints(),
                'steps_total'               => $status->getComplianceViewStatus('fitbit')->getAttribute('total_steps', 0),
                'steps_total_points'       => $status->getComplianceViewStatus('fitbit')->getPoints(),
                'education_points'         => $status->getComplianceViewStatus('elearning')->getPoints()
            );
        });

        $printer->addEndStatusFieldCallBack('goal_prize_eligible', function(ComplianceProgramStatus $status) use ($program){
            $preData = $program->getPreScreeningData($status->getUser());
            $postData = $program->getPostScreeningData($status->getUser());

            $currentUser = $program->getActiveUser();

            $goalPrizeEligible = true;
            if(!$preData) {
                $goalPrizeEligible = false;
            } elseif(!$postData) {
                $goalPrizeEligible = false;
            } elseif($status->getComplianceViewStatus('exercise')->getAttribute('minutes', 0) < 1440
            && (isset($preData['weight']) && isset($postData['weight']) && ($preData['weight'] - $postData['weight']) < 10)) {
                $goalPrizeEligible = false;
            }

            return  $goalPrizeEligible ? 'Yes' : 'No';
        });

        $printer->addEndStatusFieldCallBack('buddy_pair_total_score', function(ComplianceProgramStatus $status) use ($program, $record){
            $totalScore = 0;

            $user = $status->getUser();

            $userPreData = $program->getPreScreeningData($user);
            $userPostData = $program->getPostScreeningData($user);

            if($userPreData && $userPostData) {
                $totalScore += $status->getPoints();
            }

            if($buddyRecord = $record->getBuddy($status->getUser()->id)) {
                $buddyUser = UserTable::getInstance()->find($buddyRecord['buddy_user_id']);

                $buddyPreData = $program->getPreScreeningData($buddyUser);
                $buddyPostData = $program->getPostScreeningData($buddyUser);

                if($buddyPreData && $buddyPostData) {
                    $program->setActiveUser($buddyUser);
                    $totalScore += $program->getStatus()->getPoints();
                }
            }

            return $totalScore;
        });

        $printer->addEndStatusFieldCallBack('team_total_score', function(ComplianceProgramStatus $status) use ($program, $record){
            $currentUser = $program->getActiveUser();

            $teamRecord = $record->getTeamByUserId($status->getUser()->id);

            $totalTeamPoints = 0;
            foreach($teamRecord['users'] as $teamMember) {
                $teamUser = UserTable::getInstance()->find($teamMember['id']);

                $teamPreData = $program->getPreScreeningData($teamUser);
                $teamPostData = $program->getPostScreeningData($teamUser);

                if($teamPreData && $teamPostData) {
                    $program->setActiveUser($teamUser);
                    $totalTeamPoints += $program->getStatus()->getPoints();
                    $program->setActiveUser($currentUser);
                }
            }

            return $totalTeamPoints;
        });

        return $printer;
    }

    public function getPreAppointmentDate(User $user, $typeId = 49)
    {
        return SelectQuery::create()
            ->select('date')
            ->from('screening')
            ->where('user_id = ?', array($user->id))
            ->andWhere('date BETWEEN ? AND ?', array('2016-01-15', '2016-01-29'))
            ->andWhere('(body_fat_method = ? OR body_fat_method = ? OR body_fat_method = ?)', array('biomeasure', 'omron', 'other'))
            ->hydrateSingleScalar()
            ->execute();
    }

    public function loadSessionParameters()
    {
        $_SESSION['manua_override_fitbit_parameters'] = array(
            'activity_id' => '288',
            'question_id' => '110',
            'start_date' => '2016-01-25',
            'end_date' => '2016-03-31',
            'product_name'  => 'Total Health Challenge',
            'header_text'  => '<p><a href="/compliance_programs?id=307">Back to My Dashboard</a></p>',
            'override' => 0
        );
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '3000M');

        $isServiceLogin = sfContext::getInstance()->getRequest()->isServiceRequest();

        $this->loadSessionParameters();

        $program = $this;

        $this->options = array(
                'allow_teams'                    => true,
                'allow_team_buddy_removal'       => false,
                'team_members_minimum'           => 6,
                'team_members_maximum'           => 6,
                'team_members_maximum_males'     => 3,
                'require_registration'           => true,
                'force_spouse_with_employee'     => true,
                'registration_end_date'          => '2016-01-24 23:59:59',
                'team_buddy_management_end_date' => '2016-02-08 23:59:59',
                'registration_redirect'          => '/content/1051'
            ) + $this->options;

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $operations = new ComplianceViewGroup('operations', 'Exercise Log');

        $resView = new SumMinutesInExerciseCalendarComplianceView('resistence');
        $resView->setReportName('Resistance Exercise');
        $resView->setAttribute('name_tip', '3 points/30 minutes');
        $resView->setName('resistance_exercise');
        $resView->emptyLinks();
//        $resView->addLink(new FakeLink('Begins January 25th', '#'));
        if($isServiceLogin) $resView->addLink(new Link('Log Workout Entries', '/compliance_programs/localAction?id=307&local_action=exercise_calendar'));
        $operations->addComplianceView($resView);

        $aerView = new SumMinutesInExerciseCalendarComplianceView('aerobic');
        $aerView->setAttribute('name_tip', '2 points/30 minutes');
        $aerView->setReportName('Aerobic Exercise');
        $aerView->setName('aerobic_exercise');
        $aerView->emptyLinks();
//        $aerView->addLink(new FakeLink('Begins January 25th', '#'));
        if($isServiceLogin) $aerView->addLink(new Link('Log Workout Entries', '/compliance_programs/localAction?id=307&local_action=exercise_calendar'));
        $operations->addComplianceView($aerView);

        $elearn = new CompleteELearningGroupSet($startDate, '2016-03-27', 'required_thc_2016');
        $elearn->setName('elearning');
        $elearn->setAttribute('goal', '3 topics');
        $elearn->setPointsPerLesson(25);
        $elearn->setMaximumNumberOfPoints(75);
        $elearn->setReportName('eLearning & Other Ed.');
        $elearn->emptyLinks();
//        $elearn->addLink(new FakeLink('Begins January 25th', '#'));
        $elearn->addLink(new Link('Onsite In-Services', '/content/ucmc-thc-2016_in-services'));
        $elearn->addLink(new Link('<br />Complete E-Learning Lessons', '/content/9420?action=lessonManager&tab_alias=required_thc_2016'));
        $elearn->setForceAllowPointsOverride();
        $elearn->setAttribute('name_tip', '25 points/program');
        $elearn->setPostEvaluateCallback(function($status) {
            $numberDone = floor($status->getPoints() / 25);

            $status->setAttribute('time_topics', "{$numberDone} completed");
        });
//        $elearn->emptyLinks();
        $operations->addComplianceView($elearn);

        $this->addComplianceViewGroup($operations);

        $totals = new ComplianceViewGroup('totals', 'Total Health Challenge (THC) Totals');
        $totals->setAttribute('max_possible', ' ');

        $exercisePoints = new PlaceHolderComplianceView(null, 0);
        $exercisePoints->setName('exercise');
        $exercisePoints->setReportName('Exercise');
        $exercisePoints->setPostEvaluateCallback(function($status, $user) use($resView, $aerView) {
            $resistanceStatus = $resView->getStatus($user);
            $aerobicStatus = $aerView->getStatus($user);

            $minutesByDay = $resistanceStatus->getAttribute('minutes_by_day');

            $dailyPoints = array();

            $totalAerobicMinutes = 0;
            $totalResistanceMinutes = 0;
            $dailyLimitMinutes = 120;
            $totalLimitMinutes = 4800;
            $totalLimitPoints = 480;

            foreach($minutesByDay as $day => $minutes) {
                if(!isset($dailyPoints[$day])) {
                    $dailyPoints[$day] = 0;
                }

                if(($dailyLimitMinutes - $minutes['resistence']) > 0) {
                    $totalResistanceMinutes += $minutes['resistence'];
                    $resistancePoints = round($minutes['resistence']/30*3, 1);
                    if(($dailyLimitMinutes - $minutes['resistence'] - $minutes['aerobic']) > 0) {
                        $totalAerobicMinutes += $minutes['aerobic'];
                        $aerobicPoints = round($minutes['aerobic']/30*2, 1);
                    } else {
                        $totalAerobicMinutes += ($dailyLimitMinutes - $minutes['resistence']);
                        $aerobicPoints = round(($dailyLimitMinutes - $minutes['resistence'])/30*2, 1);
                    }
                } else {
                    $totalResistanceMinutes += 120;
                    $totalAerobicMinutes += 0;
                    $resistancePoints = 12;
                    $aerobicPoints = 0;
                }

                $dailyPoints[$day] = $resistancePoints + $aerobicPoints;
            }

            if(($totalLimitMinutes - $totalResistanceMinutes) > 0) {
                $totalResistancePoints = round($totalResistanceMinutes/30*3, 1);
                if(($totalLimitMinutes - $totalResistanceMinutes - $totalAerobicMinutes) > 0) {
                    $totalAerobicPoints = round($totalAerobicMinutes/30*2, 1);
                } else {
                    $totalAerobicPoints = round(($totalLimitMinutes - $totalResistanceMinutes)/30*2, 1);
                }
            } else {
                $totalResistancePoints = $totalLimitPoints;
                $totalAerobicPoints = 0;
            }

            $totalMinutes = min($totalLimitMinutes, $totalAerobicMinutes + $totalResistanceMinutes);
            $totalPoints = $totalResistancePoints + $totalAerobicPoints;


            $today = date('Y-m-d');

            $status->setAttribute('minutes_today', $aerobicStatus->getAttribute('minutes_today') + $resistanceStatus->getAttribute('minutes_today'));
            $status->setAttribute('points_today', isset($dailyPoints[$today]) ? $dailyPoints[$today] : 0);
            $status->setAttribute('minutes', $totalMinutes);

            $status->setPoints($totalPoints);
        });

        $totals->addComplianceView($exercisePoints);

        $fitbitView = new UCMCWalkingCampaignFitbitComplianceView($startDate, $endDate, 70000, 5);
        $fitbitView->setReportName('Steps');
        $fitbitView->setName('fitbit');
//        $fitbitView->addLink(new FakeLink('Begins January 25th', '#'));
        $fitbitView->addLink(new Link('Sync Fitbit/View Steps <br />', '/content/ucan-fitbit-individual'));
        if($isServiceLogin) $fitbitView->addLink(new Link('Enter Steps Manually', '/content/12048?action=showActivity&activityidentifier=288'));
        $totals->addComplianceView($fitbitView);

        $prePost = new PlaceHolderComplianceView(null, 0);
        $prePost->setName('pre_post');
        $prePost->setReportName('Pre/Post Measures & Progress');
        $prePost->setAttribute('max_possible', ' ');
        $prePost->addLink(new Link('View Details', '/compliance_programs/localAction?id=307&local_action=pre_post_measurements'));

        $prePost->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($program) {
            $assembledData = $program->getAssembledScreeningData(
                $program->getPreScreeningData($user),
                $program->getPostScreeningData($user)
            );

            $points = 0;

            foreach($assembledData['points'] as $field => $pVal) {
                $points += (float) $pVal;
            }

            $status->setPoints($points);
        });
        $totals->addComplianceView($prePost);

        $this->addComplianceViewGroup($totals);
    }

    public function getActionTemplateCustomizations()
    {
        ob_start();

        ?>
        <script type="text/html" id="new-buddy-instructions">
            <h4>How to participate on a team or as a buddy:</h4><br />
            <p><div style="font-weight:bold">TEAMS:</div> If you are a team captain, select <em>Create a New Team</em> (above) to invite 5 other team members. If you wish
            to join an existing team, contact the team captain and ask them to invite you. All 5 members must accept their invite for
            the team to be complete.</p>

            <p>If a spouse is joining a team, the employee of the spouse must be added
                and confirmed to the team prior to the spouse registering for the program.   Once the employee is on the team,
                the spouse can register for the program and then will be added to the same team.</p><br />


            <p><div style="font-weight:bold">BUDDIES:</div>If participating with a buddy, select <em>Choose a Buddy</em> (above) or accept the invite from your buddy.</p>

            <p>Employees and participating spouses will automatically be buddies if not joining a team.</p><br />

            <p><div style="font-weight:bold">DEADLINE:</div> All team and buddy selections need to be made by January 23rd, 2016.</p>

            <p>Note: Employees choosing not to participate on a team or with a buddy, you will be considered an individual.</p>
        </script>

        <script type="text/html" id="new-manage-team-instructions">
            <p>To invite a person to your team, enter their last name below and select <em>Search</em>.<p>
            <p>Then, select <em>Invite</em> next to the appropriate person. A team must have 6 members (including the captain) with a maximum of 3 men.</p>
            <p>Skip this step if you are not a team captain. AND click the "Members" tab above to delete the team name you have accidentally created</p>
        </script>

        <script type="text/html" id="new-new-team-instructions">
            <p><div style="font-weight: bold">This page is for Team Captains only!</div></p>
            <p>If you are the team captain, type in a unique team name below and select <em>Create</em>. Then, you will have the ability to
                invite 5 people to your team. A team must have 6 members (including the captain) with a maximum of 3 men (per team).</p>

            <p>If you are NOT a team captain, click <em>Cancel</em>. If you wish to join a team contact the team captain and ask them to invite you.</p>
        </script>

        <script type="text/html" id="new-alert-more-accepted-people">
            Please note: It is your responsibility as the team captain to make sure all members accept the invite. Once
            the invite has been accepted, "Team Member" will appear next to their name. Your team must have 6 confirmed
            team members by February 6, 2016.
        </script>

        <script type="text/javascript">
            $(function() {
                $('#no-team-or-buddy-instructions').html($('#new-buddy-instructions').html());

                $('#invite-buddy-instructions p').append(' Skip this step if you do not want a buddy.');

                $('#manage-team-instructions').html($('#new-manage-team-instructions').html());

                $('#new-team-instructions').html($('#new-new-team-instructions').html());

                $('#alert-more-accepted-people p').html($('#new-alert-more-accepted-people').html());
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
You have received a team invitation for the University of Chicago Medical Center's Total Health Challenge.

- To view your team invitation and accept or decline it:
- Login to www.ucmcwellness.org
- Select Total Health Challenge and register if you haven't already. Then, select Accept/Decline Invitation.
EOT
            ),

            'buddy_request' => array(
                'subject' => 'You have been sent a buddy request.',
                'body'    => <<<EOT
You have received a buddy invitation for the University of Chicago Medical Center's Total Health Challenge.

- To view your buddy invitation and accept or decline it:
- Login to www.ucmcwellness.org
- Select Total Health Challenge and register if you haven't already. Then, select Accept/Decline Invitation.
EOT
            ),

            'buddy_removed' => array(
                'subject' => 'You were removed as a buddy.',
                'body' => <<<EOT
You were removed as a buddy for the University of Chicago Medical Center's Total Health Challenge.

- To add a new buddy or join a team:
- Login to www.ucmcwellness.org
- Select Total Health Challenge
EOT

            )
        );
    }

    public function getLocalActions()
    {
        return array(
            'pre_post_measurements' => array($this, 'executePrePostMeasurements'),
            'exercise_calendar' => array($this, 'executeExerciseCalendar'),
            'exercise_entry' => array($this, 'executeExerciseEntry'),
            'delete_exercise_entry' => array($this, 'executeDeleteExerciseEntry')
        );
    }

    public function getPreScreeningData(User $user)
    {
        if($this->preCall && $this->preCall['id'] == $user->id) {
            return $this->preCall['data'];
        } else {
            $model = new UCMCTHCModel();

            $data = $model->getPreData($user);

            $this->preCall = array('id' => $user->id, 'data' => $data);

            return $data;
        }
    }

    public function getPostScreeningData(User $user)
    {
        if($this->postCall && $this->postCall['id'] == $user->id) {
            return $this->postCall['data'];
        } else {
            $data = Screening::getMergedData($user, new \DateTime('2016-03-01'), new \DateTime('2016-04-01'), array(
                    'filter'    => function($array) {
                        return (
                            isset($array['body_fat_method']) &&
                            in_array(trim(strtolower($array['body_fat_method'])), array('biomeasure', 'omron', 'other'))
                        );
                    }
                )
            );

            $this->postCall = array('id' => $user->id, 'data' => $data);

            return $data;
        }
    }

    public function getAssembledScreeningData($preData, $postData)
    {
        $markPos = function($val) {
            if($val >= 0) {
                return "+$val";
            }  else {
                return $val;
            }
        };

        $havePreBp = isset($preData['systolic'], $preData['diastolic']);
        $havePostBp = isset($postData['systolic'], $postData['diastolic']);

        $haveBp = $havePreBp && $havePostBp;

        $preInches = $this->getScreeningInchesData($preData);
        $postInches = $this->getScreeningInchesData($postData);

        $pre = array(
            'date' => isset($preData['date']) ? date('m/d/Y', strtotime($preData['date'])) : '',
            'systolic' => $havePreBp ? $preData['systolic'] : '',
            'diastolic' => $havePreBp ? $preData['diastolic'] : '',
            'weight' => isset($preData['weight']) ? $preData['weight'] : '',
            'bodyfat' => isset($preData['bodyfat']) ? $preData['bodyfat'] : '',
            'inches' => $preInches['text']
        );

        $post = array(
            'date' => isset($postData['date']) ? date('m/d/Y', strtotime($postData['date'])) : '',
            'systolic' => $havePostBp ? $postData['systolic'] : '',
            'diastolic' => $havePostBp ? $postData['diastolic'] : '',
            'weight' => isset($postData['weight']) ? $postData['weight'] : '',
            'bodyfat' => isset($postData['bodyfat']) ? $postData['bodyfat'] : '',
            'inches' => $postInches['text']
        );

        $change = array(
            'systolic' => $havePostBp && $havePreBp ?
                $markPos($post['systolic'] - $pre['systolic']) : '',

            'diastolic' => $havePostBp && $havePreBp ?
                $markPos($post['diastolic'] - $pre['diastolic']) : '',

            'weight' => $pre['weight'] && $post['weight'] ?
                $markPos($post['weight'] - $pre['weight']) : '',

            'bodyfat' => $pre['bodyfat'] && $post['bodyfat'] ?
                $markPos($post['bodyfat'] - $pre['bodyfat']) : '',

            'inches' => $markPos($postInches['total'] - $preInches['total'])
        );

        $inchesPointsText = '';
        $inchesChangeText = '';

        if($preData && $postData) {
            $points = array(
                'systolic' => (
                    ($post['systolic'] <= 120 && $post['diastolic'] <= 80) ||
                    ($change['systolic'] <= 0 && $change['diastolic'] <= 0 &&
                        ($change['systolic'] < 0 || $change['diastolic'] < 0))
                ) ? 25 : 0,

                'diastolic' => 0,

                'weight' => $pre['weight'] && $post['weight'] ? max(0, ($pre['weight'] - $post['weight']) * 5) : 0,

                'bodyfat' => $pre['bodyfat'] && $post['bodyfat'] ? max(0, ($pre['bodyfat'] - $post['bodyfat']) * 25) : 0,
            );

            $inchesFields = array(
                'chest' => 'Chest',
                'hips'  => 'Hips',
                'thigh' => 'Thigh',
                'waist' => 'Waist'
            );

            $inchesPoints = 0;

            foreach($inchesFields as $inchField => $inchFieldName) {
                $preInch = isset($preData[$inchField]) && $preData[$inchField] ? $preData[$inchField] : null;
                $postInch = isset($postData[$inchField]) && $postData[$inchField] ? $postData[$inchField] : null;

                if($preInch !== null && $postInch !== null) {
                    $inchesChange = $postInch - $preInch;

                    $inchFieldPoints = $inchesChange < 0 ? round(-$inchesChange, 2) * 10 : 0;

                    $inchesPointsText .= "$inchFieldName: $inchFieldPoints<br/>";
                    $inchesChangeText .= "$inchFieldName: {$markPos($inchesChange)}\"<br/>";

                    $points[$inchField] = $inchFieldPoints;

                    $inchesPoints += $inchFieldPoints;
                }
            }

//            $points['inches']  = $inchesPoints;

        } else {
            $points = array(
                'systolic' => 0,
                'diastolic' => 0,
                'weight' => 0,
                'bodyfat' => 0,
                'inches' => 0
            );
        }

        $empty = array(
            'date' => '',
            'systolic' => '',
            'diastolic' => '',
            'weight' => '',
            'bodyfat' => '',
            'inches' => ''
        );

        return array(
            'have_bp' => $haveBp,
            'pre' => $pre,
            'post' => $postData ? $post : $empty,
            'change' => $preData && $postData ? $change : $empty,
            'points' => $points,
            'inches_change' => $inchesChangeText,
            'inches_points' => $inchesPointsText
        );
    }

    private function getScreeningInchesData($scrData)
    {
        $ret = array(
            'total' => 0,
            'text'  => '',
            'chest' => 0,
            'hips'  => 0,
            'thigh' => 0,
            'waist' => 0
        );

        if(isset($scrData['chest'])) {
            $ret['text'] .= "Chest: {$scrData['chest']}\"<br/>";
            $ret['total'] += (float)$scrData['chest'];
            $ret['chest'] = (float)$scrData['chest'];
        }

        if(isset($scrData['hips'])) {
            $ret['text'] .= "Hips: {$scrData['hips']}\"<br/>";
            $ret['total'] += (float)$scrData['hips'];
            $ret['hips'] = (float)$scrData['hips'];
        }

        if(isset($scrData['thigh'])) {
            $ret['text'] .= "Thigh: {$scrData['thigh']}\"<br/>";
            $ret['total'] += (float)$scrData['thigh'];
            $ret['thigh'] = (float)$scrData['thigh'];
        }

        if(isset($scrData['waist'])) {
            $ret['text'] .= "Waist: {$scrData['waist']}\"<br/>";
            $ret['total'] += (float)$scrData['waist'];
            $ret['waist'] = (float)$scrData['waist'];
        }

        return $ret;
    }

    public function executeExerciseEntry(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        if(!($day = $actions->getRequestParameter('day')) || !($day = strtotime($day))) {
            $actions->forward404();
        }

        //ActivityTrackerQuestion::MINUTES

        $dateTime = new \DateTime('@'.$day);

        $date = $actions->getRequestParameter('day');
        $entryNum = $actions->getRequestParameter('entry_number');

        $form = new BaseForm();

        $form->setWidgets(array(
            'aerobic_minutes' => new sfWidgetFormInputText(),
            'resistence_minutes' => new sfWidgetFormInputText(array('label' => 'Resistance minutes')),
            'entry_number'  => new sfWidgetFormInputHidden()
        ));

        $form->setValidators(array(
            'aerobic_minutes' => new sfValidatorNumber(array('required' => false)),
            'resistence_minutes' => new sfValidatorNumber(array('required' => false)),
            'entry_number' => new sfValidatorString(array('required' => false))
        ));

        if($entryNum) {
            $record = $user->getNewestDataRecord(self::UCMC_TOTAL_HEALTH_CHALLENGE_FITNESS_RECORD_TYPE);
            if($record->exists()) {
                if($record->getDataFieldValue("{$date}_{$entryNum}_aerobic_minutes") && $record->getDataFieldValue("{$date}_{$entryNum}_resistence_minutes")) {
                    $form->setDefaults(array(
                        'aerobic_minutes'       => $record->getDataFieldValue("{$date}_{$entryNum}_aerobic_minutes"),
                        'resistence_minutes'    => $record->getDataFieldValue("{$date}_{$entryNum}_resistence_minutes"),
                        'entry_number'           => $entryNum));

                }
            }
        }

        if ($form->isValidForRequest($actions->getRequest())) {
            if(date('Y-m-d', strtotime($date)) > date('Y-m-d')) {
                $actions->getUser()->setErrorFlash('Sorry, you cannot enter minutes for future days');
                $actions->redirect('/compliance_programs/localAction?id=307&local_action=exercise_calendar');
            }
            $record = $user->getNewestDataRecord(self::UCMC_TOTAL_HEALTH_CHALLENGE_FITNESS_RECORD_TYPE, true);

            $aerobicMinutes = $form->getValue('aerobic_minutes');
            $resistenceMinutes = $form->getValue('resistence_minutes');
            $entryNum   = $form->getValue('entry_number');

            if(($aerobicMinutes || $resistenceMinutes) && ($aerobicMinutes > 0 || $resistenceMinutes> 0)) {
                if($entryNum) {
                    if($record->getDataFieldValue("{$date}_{$entryNum}_aerobic_minutes") && $record->getDataFieldValue("{$date}_{$entryNum}_resistence_minutes")) {
                        $record->setDataFieldValue("{$date}_{$entryNum}_aerobic_minutes", $aerobicMinutes);
                        $record->setDataFieldValue("{$date}_{$entryNum}_resistence_minutes", $resistenceMinutes);

                        $record->save();

                        $actions->redirect(sprintf('/compliance_programs/localAction?id=307&local_action=exercise_entry&day=%s', $date));
                    }
                } else {
                    foreach($this->getNumOfEntries() as $num) {
                        if(!$record->getDataFieldValue("{$date}_{$num}_aerobic_minutes") && !$record->getDataFieldValue("{$date}_{$num}_resistence_minutes")) {
                            $record->setDataFieldValue("{$date}_{$num}_date_entered", date('Y-m-d'));
                            $record->setDataFieldValue("{$date}_{$num}_aerobic_minutes", $aerobicMinutes);
                            $record->setDataFieldValue("{$date}_{$num}_resistence_minutes", $resistenceMinutes);

                            $record->save();
                            break;
                        }
                    }
                }
            }

            if(($aerobicMinutes + $resistenceMinutes) >= 120){
                $actions->getUser()->setErrorFlash('Your information was saved. Reminder: A maximum of 120 minutes / day will be counted to your score.');
            } else {
                $actions->getUser()->setNoticeFlash('Your information was saved.');
            }
            $actions->redirect('/compliance_programs/localAction?id=307&local_action=exercise_calendar');
        }

        $record = $user->getNewestDataRecord(self::UCMC_TOTAL_HEALTH_CHALLENGE_FITNESS_RECORD_TYPE);

        ?>

        <style type="text/css">
            #previous_entries_table tr th, #previous_entries_table tr td {
                padding: 10px 10px 10px 10px;
                text-align: center;
            }
        </style>

        <div class="page-header">
            <p><a href="/compliance_programs/localAction?id=307&local_action=exercise_calendar">Back to Calendar</a></p>
            <h3>Logging exercise information for <?php echo $dateTime->format('l m/d/Y') ?></h3>
        </div>
        <?php echo $form->renderFormTag('/compliance_programs/localAction?id=307&local_action=exercise_entry&day='.$dateTime->format('Y-m-d')) ?>
            <ul>
                <?php echo $form ?>
                <li class="actions">

                </li>
            </ul>
            <div class="form-actions">
                    <input type="submit" value="Save" class="btn btn-primary" />
                    <a class="btn btn-default" href="/compliance_programs/localAction?id=307&local_action=exercise_calendar">
                        Cancel
                    </a>
            </div>
        </form>

        <?php if($record->exists() && count($record->getAllDataFieldValues()) > 0) : ?>
            <table id="previous_entries_table">
                <tr><th>Aerobic Minutes</th><th>Resistance Minutes</th><th>Date Entered</th></tr>

                <?php foreach($this->getNumOfEntries() as $num) : ?>
                <?php if($record->getDataFieldValue("{$date}_{$num}_aerobic_minutes") || $record->getDataFieldValue("{$date}_{$num}_resistence_minutes")) : ?>
                <tr>
                    <td><?php echo $record->getDataFieldValue("{$date}_{$num}_aerobic_minutes") ?></td>
                    <td><?php echo $record->getDataFieldValue("{$date}_{$num}_resistence_minutes") ?></td>
                    <td><?php echo $record->getDataFieldValue("{$date}_{$num}_date_entered") ?></td>
                    <td>
                        <form class="form-search input-append" method="post" action="<?php echo url_for("/compliance_programs/localAction?id=".$this->getComplianceProgramRecord()->getId()."&local_action=delete_exercise_entry")?>">
                            <input type="hidden" name="record_id" value="<?php echo $record->getId() ?>"/>
                            <input type="hidden" name="date" value="<?php echo $date?>" />
                            <input type="hidden" name="entry_number" value="<?php echo $num?>" />
                            <button type="submit" class="btn" id="search-submit">Delete</button>
                        </form>
                    </td>
                    <td>
                        <div class="form-search input-append">
                            <a style="text-decoration: none; color: #333333"
                                href="<?php echo sprintf(
                                                    "/compliance_programs/localAction?id=%s&local_action=exercise_entry&day=%s&entry_number=%s",
                                                    $this->getComplianceProgramRecord()->getId(),
                                                    $date,
                                                    $num) ?>"><button type="button" class="btn">Edit</button></a>
                        </div>
                    </td>
                </tr>
                <?php endif ?>
                <?php endforeach ?>
            </table>
        <?php endif ?>
        <?php
    }

    public function executeDeleteExerciseEntry(sfActions $actions)
    {
        $recordID = $actions->getRequestParameter('record_id');
        $date = $actions->getRequestParameter('date');
        $num = $actions->getRequestParameter('entry_number');

        $record = new UserDataRecord($recordID);

        if($record->exists()) {
            if($date && !empty($date) && !empty($num)) {
                $record->getDataField("{$date}_{$num}_date_entered")->delete();
                $record->getDataField("{$date}_{$num}_aerobic_minutes")->delete();
                $record->getDataField("{$date}_{$num}_resistence_minutes")->delete();

                $record->save();

                $actions->getUser()->setNoticeFlash('Your entry for '.$date.' has been deleted.');

                $actions->redirect(sprintf(
                    '/compliance_programs/localAction?id=%s&local_action=exercise_entry&day=%s',
                    $this->getComplianceProgramRecord()->getId(),
                    $date
                ));
            }
        }
    }

    protected function getNumOfEntries()
    {
        $num = array();
        for($i=1; $i<12; $i++) {
            $num[] = $i;
        }
        return $num;
    }

    public function executeExerciseCalendar(sfActions $actions)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $range = $this->getStartDate();


        $user = $actions->getSessionUser();

        $numTestWeeks = 0;

//        $startDate = new \DateTime($this->getStartDate('Y-m-d'));
        $startDate = new \DateTime('2016-01-25');
        $endDate = new \DateTime($this->getEndDate('Y-m-d'));

        $record = $user->getNewestDataRecord(self::UCMC_TOTAL_HEALTH_CHALLENGE_FITNESS_RECORD_TYPE);
        $fitbitData = get_all_fitbit_data($user->id, '2016-01-11', $this->getEndDate('Y-m-d'), null, false);

        $days = array();

        while($startDate->format('Y-m-d') <= $endDate->format('Y-m-d')) {
            $days[$startDate->format('Y-m-d')] = $startDate->format('N');

            $startDate->add(new \DateInterval('P1D'));
        }

        $weeks = array();

        $week = array();

        $first = true;

        foreach($days as $d => $dow) {
            if ($first) {
                $first = false;

                for ($i = 1; $i < $dow; $i++) {
                    $week[] = false;
                }
            }

            $week[] = $d;

            if ($dow == 7) {
                $weeks[] = $week;
                $week = array();
            }
        }

        if (count($week)) {
            while(count($week) < 7) {
                $week[] = false;
            }

            $weeks[] = $week;
        }

        $headerRow = function($heading) {
           ?>
            <tr>
                <th></th>
                <th>Monday</th>
                <th>Tuesday</th>
                <th>Wednesday</th>
                <th>Thursday</th>
                <th>Friday</th>
                <th>Saturday</th>
                <th>Sunday</th>
                <th>Weekly Totals</th>
                <th>Weekly Points</th>
            </tr>
            <?php
        };

        ?>
        <p><a id="back-to-report-card" href="/compliance_programs?id=307">Back to My Dashboard</a></p>

        <style type="text/css">
            #page.container {
                width: 984px;
            }

            #ucmc-thc-calendar {
                width: 100%;
                font-size: 0.9em;
            }

            #ucmc-thc-calendar .week-first th {
                padding: 30px 0;
                text-align: center;
                background-color: #0A246A;
                color: #FFF;
            }

            #ucmc-thc-calendar .week {

            }

            #ucmc-thc-calendar .day, #ucmc-thc-calendar .type, #ucmc-thc-calendar .total {
                position: relative;
            }

            #ucmc-thc-calendar .total {
                text-align: center;
            }

            #ucmc-thc-calendar .day a {
                display: block;
                height: 150px;
                border: 2px solid #AAA;
                text-decoration: none;
                color: inherit;
                text-align: center;
            }

            #ucmc-thc-calendar .day a:hover {
                border-color: #0f86ff;
            }

            #ucmc-thc-calendar .aerobic {
                position: absolute;
                top: 15px;
                width: 100%;
            }

            #ucmc-thc-calendar .resistence {
                position: absolute;
                top: 65px;
                width: 100%;
            }

            #ucmc-thc-calendar .steps {
                position: absolute;
                top: 115px;
                width: 100%;
            }
        </style>

        <table id="ucmc-thc-calendar">
            <tbody>
                <?php
                    foreach($weeks as $n => $days) {
                        $fDays = array();

                        $aerobic = array();
                        $resistence = array();
                        $fitbitSteps = array();
                        $weekTotals = array(
                            'aerobic_minutes' => 0,
                            'resistence_minutes' => 0,
                            'fitbit_steps' => 0
                        );
                        $weekPoints = array(
                            'aerobic_minutes' => 0,
                            'resistence_minutes' => 0,
                            'fitbit_steps' => 0
                        );

                        foreach($days as $d) {
                            $fDays[] = date('m/d/Y', strtotime($d));

                            if($record->exists()){
                                $aerobicMinutes = 0;
                                $resistenceMinutes = 0;

                                foreach($this->getNumOfEntries() as $num) {
                                    $aerobicMinutes += $record->getDataFieldValue("{$d}_{$num}_aerobic_minutes") ? $record->getDataFieldValue("{$d}_{$num}_aerobic_minutes") : 0;
                                    $resistenceMinutes += $record->getDataFieldValue("{$d}_{$num}_resistence_minutes") ? $record->getDataFieldValue("{$d}_{$num}_resistence_minutes") : 0;
                                }

                                $aerobic[$d] = $aerobicMinutes;
                                $resistence[$d] = $resistenceMinutes;

                                $weekTotals['aerobic_minutes'] += $aerobicMinutes;
                                $weekTotals['resistence_minutes'] += $resistenceMinutes;

                                $dailyLimitMinutes = 120;

                                if(($dailyLimitMinutes - $resistenceMinutes) > 0) {
                                    $resistencePoints = round($resistenceMinutes/30*3, 1);
                                    if(($dailyLimitMinutes - $resistenceMinutes - $aerobicMinutes) > 0) {
                                        $aerobicPoints = round($aerobicMinutes/30*2, 1);
                                    } else {
                                        $aerobicPoints = round(($dailyLimitMinutes - $resistenceMinutes)/30*2, 1);
                                    }
                                } else {
                                    $resistencePoints = 12;
                                    $aerobicPoints = 0;
                                }

                                $weekPoints['aerobic_minutes'] += $aerobicPoints;
                                $weekPoints['resistence_minutes'] += $resistencePoints;
                            }

                            if(isset($fitbitData['dates'][$d])) {
                                $fitbitSteps[$d] = $fitbitData['dates'][$d];

                                $weekTotals['fitbit_steps'] += $fitbitData['dates'][$d];
                            }
                        }

                        $weekPoints['fitbit_steps'] = $weekTotals['fitbit_steps'] >= 70000 ? 5 : 0;

                        ?>
                        <tr class="week-first">
                            <th class="type">
                                <?php if ($n < $numTestWeeks) : ?>
                                    Test Week <?php echo $n + 1 ?>
                                <?php else : ?>
                                    Week <?php echo $n + 1 - $numTestWeeks ?>
                                <?php endif ?>
                            </th>
                            <th><?php echo $days[0] ? "Monday <br/> {$fDays[0]}" : '' ?></th>
                            <th><?php echo $days[1] ? "Tuesday <br/> {$fDays[1]}" : '' ?></th>
                            <th><?php echo $days[2] ? "Wednesday <br/> {$fDays[2]}" : '' ?></th>
                            <th><?php echo $days[3] ? "Thursday <br/> {$fDays[3]}" : '' ?></th>
                            <th><?php echo $days[4] ? "Friday <br/> {$fDays[4]}" : '' ?></th>
                            <th><?php echo $days[5] ? "Saturday <br/> {$fDays[5]}" : '' ?></th>
                            <th><?php echo $days[6] ? "Sunday <br/> {$fDays[6]}" : '' ?></th>
                            <th>Weekly Totals</th>
                            <th>Weekly Points</th>
                        </tr>
                        <tr class="week">
                            <td class="type">
                                <div class="aerobic">Aerobic</div>
                                <div class="resistence">Resistance</div>
                                <div class="steps">Steps</div>
                            </td>
                            <td class="<?php echo $days[0] ? 'day' : '' ?>">
                                <?php if($days[0]) : ?>
                                    <a href="<?php echo '/compliance_programs/localAction?id=307&local_action=exercise_entry&day='.$days[0] ?>">
                                        <div class="aerobic"><?php echo isset($aerobic[$days[0]]) ? number_format($aerobic[$days[0]]) : 0 ?></div>
                                        <div class="resistence"><?php echo isset($resistence[$days[0]]) ? number_format($resistence[$days[0]]) : 0 ?></div>
                                        <div class="steps"><?php echo isset($fitbitSteps[$days[0]]) ? number_format($fitbitSteps[$days[0]]) : 0 ?></div>
                                    </a>
                                <?php endif ?>
                            </td>
                            <td class="<?php echo $days[1] ? 'day' : '' ?>">
                                <?php if($days[1]) : ?>
                                    <a href="<?php echo '/compliance_programs/localAction?id=307&local_action=exercise_entry&day='.$days[1] ?>">
                                        <div class="aerobic"><?php echo isset($aerobic[$days[1]]) ? number_format($aerobic[$days[1]]) : 0 ?></div>
                                        <div class="resistence"><?php echo isset($resistence[$days[1]]) ? number_format($resistence[$days[1]]) : 0 ?></div>
                                        <div class="steps"><?php echo isset($fitbitSteps[$days[1]]) ? number_format($fitbitSteps[$days[1]]) : 0 ?></div>
                                    </a>
                                <?php endif ?>
                            </td>
                            <td class="<?php echo $days[2] ? 'day' : '' ?>">
                                <?php if($days[2]) : ?>
                                    <a href="<?php echo '/compliance_programs/localAction?id=307&local_action=exercise_entry&day='.$days[2] ?>">
                                        <div class="aerobic"><?php echo isset($aerobic[$days[2]]) ? number_format($aerobic[$days[2]]) : 0 ?></div>
                                        <div class="resistence"><?php echo isset($resistence[$days[2]]) ? number_format($resistence[$days[2]]) : 0 ?></div>
                                        <div class="steps"><?php echo isset($fitbitSteps[$days[2]]) ? number_format($fitbitSteps[$days[2]]) : 0 ?></div>
                                    </a>
                                <?php endif ?>
                            </td>
                            <td class="<?php echo $days[3] ? 'day' : '' ?>">
                                <?php if($days[3]) : ?>
                                    <a href="<?php echo '/compliance_programs/localAction?id=307&local_action=exercise_entry&day='.$days[3] ?>">
                                        <div class="aerobic"><?php echo isset($aerobic[$days[3]]) ? number_format($aerobic[$days[3]]) : 0 ?></div>
                                        <div class="resistence"><?php echo isset($resistence[$days[3]]) ? number_format($resistence[$days[3]]) : 0 ?></div>
                                        <div class="steps"><?php echo isset($fitbitSteps[$days[3]]) ? number_format($fitbitSteps[$days[3]]) : 0 ?></div>
                                    </a>
                                <?php endif ?>
                            </td>
                            <td class="<?php echo $days[4] ? 'day' : '' ?>">
                                <?php if($days[4]) : ?>
                                    <a href="<?php echo '/compliance_programs/localAction?id=307&local_action=exercise_entry&day='.$days[4] ?>">
                                        <div class="aerobic"><?php echo isset($aerobic[$days[4]]) ? number_format($aerobic[$days[4]]) : 0 ?></div>
                                        <div class="resistence"><?php echo isset($resistence[$days[4]]) ? number_format($resistence[$days[4]]) : 0 ?></div>
                                        <div class="steps"><?php echo isset($fitbitSteps[$days[4]]) ? number_format($fitbitSteps[$days[4]]) : 0 ?></div>
                                    </a>
                                <?php endif ?>
                            </td>
                            <td class="<?php echo $days[5] ? 'day' : '' ?>">
                                <?php if($days[5]) : ?>
                                    <a href="<?php echo '/compliance_programs/localAction?id=307&local_action=exercise_entry&day='.$days[5] ?>">
                                        <div class="aerobic"><?php echo isset($aerobic[$days[5]]) ? number_format($aerobic[$days[5]]) : 0 ?></div>
                                        <div class="resistence"><?php echo isset($resistence[$days[5]]) ? number_format($resistence[$days[5]]) : 0 ?></div>
                                        <div class="steps"><?php echo isset($fitbitSteps[$days[5]]) ? number_format($fitbitSteps[$days[5]]) : 0 ?></div>
                                    </a>
                                <?php endif ?>
                            </td>
                            <td class="<?php echo $days[6] ? 'day' : '' ?>">
                                <?php if($days[6]) : ?>
                                    <a href="<?php echo '/compliance_programs/localAction?id=307&local_action=exercise_entry&day='.$days[6] ?>">
                                        <div class="aerobic"><?php echo isset($aerobic[$days[6]]) ? number_format($aerobic[$days[6]]) : 0 ?></div>
                                        <div class="resistence"><?php echo isset($resistence[$days[6]]) ? number_format($resistence[$days[6]]) : 0 ?></div>
                                        <div class="steps"><?php echo isset($fitbitSteps[$days[6]]) ? number_format($fitbitSteps[$days[6]]) : 0 ?></div>
                                    </a>
                                <?php endif ?>
                            </td>
                            <td class="total">
                                <div class="aerobic"><?php echo isset($weekTotals['aerobic_minutes']) ? number_format($weekTotals['aerobic_minutes']) : 0 ?></div>
                                <div class="resistence"><?php echo isset($weekTotals['resistence_minutes']) ? number_format($weekTotals['resistence_minutes']) : 0 ?></div>
                                <div class="steps"><?php echo isset($weekTotals['fitbit_steps']) ? number_format($weekTotals['fitbit_steps']) : 0 ?></div>
                            </td>
                            <td class="total">
                                <div class="aerobic"><?php echo isset($weekPoints['aerobic_minutes']) ? $weekPoints['aerobic_minutes'] : 0 ?></div>
                                <div class="resistence"><?php echo isset($weekPoints['resistence_minutes']) ? $weekPoints['resistence_minutes'] : 0 ?></div>
                                <div class="steps"><?php echo isset($weekPoints['fitbit_steps']) ? $weekPoints['fitbit_steps'] : 0 ?></div>
                            </td>
                        </tr>

                        <?php
                    }
                ?>
            </tbody>
        </table>
        <?php
    }

    public function executePrePostMeasurements(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $scrData = $this->getPreScreeningData($user);

        $postData = $this->getPostScreeningData($user);

        $data = $this->getAssembledScreeningData($scrData, $postData);

        $preSystolic = isset($scrData['systolic'], $scrData['diastolic']) ?
            $scrData['systolic'] : '';

        $preDiastolic = isset($scrData['systolic'], $scrData['diastolic']) ?
            $scrData['systolic'] : '';

        ?>
        <p><a id="back-to-report-card" href="/compliance_programs?id=307">Back to My Dashboard</a></p>

        <style type="text/css">
            #pre-post .my-change,
            #pre-post .my-points,
            #pre-post .pre,
            #pre-post .post {
                width:115px;
            }
        </style>

        <div class="page-header">
            <h3>Measures/Progress Points</h3>
        </div>

        <table id="pre-post" class="table table-striped">
            <thead>
            <tr>
                <th></th>
                <th class="pre">
                    Pre: <?php echo $data['pre']['date'] ?>
                </th>
                <th class="post">Post: <?php echo $data['post']['date'] ?></th>
                <th class="my-change">My Change</th>
                <th class="my-points">My Points</th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <th style="text-align:right" colspan="4">Total Points as of <?php echo date('m/d/Y') ?></th>
                <td><?php echo array_sum($data['points']) ?></td>
            </tr>
            </tfoot>
            <tbody>
            <tr>
                <td>Blood Pressure<br/>
                    25 pts if post is &le;120/&le;80 or if either post is lower than pre, but neither is higher
                </td>
                <td class="pre"><?php echo $data['pre']['systolic'].'/'.$data['pre']['diastolic'] ?></td>
                <td class="post"><?php echo $data['have_bp'] ? $data['post']['systolic'].'/'.$data['post']['diastolic'] : '' ?></td>
                <td class="my-change"><?php echo $data['have_bp'] ? $data['change']['systolic'].'/'.$data['change']['diastolic'] : '' ?></td>
                <td class="my-points"><?php echo $data['points']['systolic'] ?></td>
            </tr>
            <tr>
                <td>Weight - 5 pts per pound lost</td>
                <td class="pre"><?php echo $data['pre']['weight'] ?></td>
                <td class="post"><?php echo $data['post']['weight'] ?></td>
                <td class="my-change"><?php echo $data['change']['weight'] ?></td>
                <td class="my-points"><?php echo $data['points']['weight'] ?></td>
            </tr>
            <tr>
                <td>Body Fat - 25 pts per 1% lost</td>
                <td class="pre"><?php echo $data['pre']['bodyfat'] ?></td>
                <td class="post"><?php echo $data['post']['bodyfat'] ?></td>
                <td class="my-change"><?php echo $data['change']['bodyfat'] ?></td>
                <td class="my-points"><?php echo $data['points']['bodyfat'] ?></td>
            </tr>
            <tr>
                <td>Inches - 10 pts per inch lost</td>
                <td class="pre"><?php echo $data['pre']['inches'] ?></td>
                <td class="post"><?php echo $data['post']['inches'] ?></td>
                <td class="my-change"><?php echo $data['inches_change'] ?></td>
                <td class="my-points"><?php echo $data['inches_points'] ?></td>
            </tr>
            </tbody>
        </table>
    <?php
    }

    private $preCall = null;
    private $postCall = null;

    const UCMC_TOTAL_HEALTH_CHALLENGE_FITNESS_RECORD_TYPE = 'ucmc_thc_fitness_2016';
    const UCMC_TOTAL_HEALTH_CHALLENGE_RECORD_ID = 307;
}

class SumMinutesInExerciseCalendarComplianceView extends ComplianceView
{
    public function __construct($exerciseType)
    {
        $this->exerciseType = $exerciseType;
    }

    public function getDefaultReportName()
    {
        return $this->view->getDefaultReportName();
    }

    public function getDefaultName()
    {
        return $this->view->getDefaultName();
    }

    public function getDefaultStatusSummary($status)
    {
        return $this->view->getDefaultStatusSummary($status);
    }

    public function getStatus(User $user)
    {
        $startDate = new \DateTime('2016-01-25');
        $endDate = new \DateTime('2016-03-20');

        $record = $user->getNewestDataRecord('ucmc_thc_fitness_2016');

        $days = array();

        while($startDate->format('Y-m-d') <= $endDate->format('Y-m-d')) {
            $days[] = $startDate->format('Y-m-d');

            $startDate->add(new \DateInterval('P1D'));
        }

        $aerobicTotalMinutes = 0;
        $resistenceTotalMinutes = 0;
        $minutesByDay = array();
        $aerobicTodayMinutes = 0;
        $resistenceTodayMinutes = 0;

        if($record->exists()) {
            foreach($days as $d) {
                $aerobicMinutes = 0;
                $resistenceMinutes = 0;
                foreach($this->getNumOfEntries() as $num) {
                    $aerobicMinutes += $record->getDataFieldValue("{$d}_{$num}_aerobic_minutes") ? $record->getDataFieldValue("{$d}_{$num}_aerobic_minutes") : 0;
                    $resistenceMinutes += $record->getDataFieldValue("{$d}_{$num}_resistence_minutes") ? $record->getDataFieldValue("{$d}_{$num}_resistence_minutes") : 0;
                }

                $aerobicTotalMinutes += $aerobicMinutes;
                $resistenceTotalMinutes += $resistenceMinutes;

                $minutesByDay[$d]['aerobic'] = $aerobicMinutes;
                $minutesByDay[$d]['resistence'] = $resistenceMinutes;

                if($d == date('Y-m-d')) {
                    $aerobicTodayMinutes = $aerobicMinutes;
                    $resistenceTodayMinutes = $resistenceMinutes;
                }
            }
        }

        $status = new ComplianceViewStatus(
            $this,
            ComplianceStatus::NA_COMPLIANT
        );


        if($this->exerciseType == 'resistence') {
            $status->setAttribute('minutes_by_day', $minutesByDay);
            $status->setAttribute('minutes', $resistenceTotalMinutes);
            $status->setAttribute('minutes_today', $resistenceTodayMinutes);

            $formattedResistenceTotalMinutes = number_format($resistenceTotalMinutes);
            $status->setAttribute('time_topics', "{$formattedResistenceTotalMinutes} minutes");
        } else {
            $status->setAttribute('minutes_by_day', $minutesByDay);
            $status->setAttribute('minutes', $aerobicTotalMinutes);
            $status->setAttribute('minutes_today', $aerobicTodayMinutes);

            $formattedAerobicTotalMinutes = number_format($aerobicTotalMinutes);
            $status->setAttribute('time_topics', "{$formattedAerobicTotalMinutes} minutes");
        }

        return $status;
    }

    protected function getNumOfEntries()
    {
        $num = array();
        for($i=1; $i<12; $i++) {
            $num[] = $i;
        }
        return $num;
    }
}

class UCMCTHC2015ProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $exercise = $status->getComplianceViewStatus('exercise');

        ?>
        <script type="text/javascript">
            $(function() {
                $('#ucmc-thc-header').insertBefore($('#compliance_tabs'));
            });
        </script>

        <div class="page-header" id="ucmc-thc-header">
            <div class="row">
                    <div class="span7">
                        <p>Welcome <?php echo $status->getUser()->first_name ?>,</p>
                        <p>If interested, you can also participate with a buddy or team.</p>

                        <p>From this dashboard you can:</p>

                        <ul>
                            <li>Make Buddy or Team decisions if applicable</li>
                            <li>Enter your daily exercise minutes</li>
                            <li>Earn extra learning points</li>
                            <li><a href="/content/THC_learnmore">Learn more</a> about the prizes, other details and FAQs</li>
                            <li><a href="/content/1051">Make an appointment for your post-contest measurements</a></li>
                        </ul>

                        <p>Thank you for participating!</p>
                    </div>
                    <div class="span5">
                        <p><img src="/resources/6971/UCMC_THC_2016_Dates012516.png" alt="" /></p>
                        <p style="color: red;">Post-Contest Measures are March 21-26, 2016</p>
                        <p><a href="/content/1051">Schedule / Adjust your Post-Contest Appointment</a></p>
                        <p><a href="/resources/7100/AllWinnersCombined.pdf" target="_blank">2015 Total Health Challenge Top Scores</a></p>
                        <p><a href="/content/ucmc-thc-tools-links">Tips and Tools for Your THC Goals</a></p>

                    </div>
            </div>
        </div>

        <style type="text/css">
            span.view-number {
                width:30px;
                display:inline-block;
            }

            span.name-tip {
                margin-left:50px;
            }

            #dashboard th, #name-heading, #rewards-heading {
                color:#8B0020;
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
                    <th style="text-align:left">A. <?php echo $this->getGroupReportName($status, 'operations') ?></th>
                    <th>Minutes of Exercise</th>
                    <th></th>
                    <th>Action Links</th>
                </tr>
                <?php $this->printViewRow($status, 'resistance_exercise', 1) ?>
                <?php $this->printViewRow($status, 'aerobic_exercise', 2) ?>
                <tr>
                    <th style="text-align:left">B. <?php echo $this->getGroupReportName($status, 'totals') ?></th>
                    <th>My Time & Topics</th>
                    <th>My THC Points</th>
                    <th>Action Links</th>
                </tr>
                <tr>
                    <td>
                        <span class="view-number">1.</span> Total Exercise Today
                        <br/>
                        <span class="name-tip"><small>Maximum of 120 minutes &amp; 12 points/day</small></span>
                    </td>
                    <td class="center"><?php echo number_format($exercise->getAttribute('minutes_today')) ?> minutes *</td>
                    <td class="center"><?php echo $exercise->getAttribute('points_today') ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <span class="view-number">2.</span> Exercise Cumulative
                        <br/>
                        <span class="name-tip"><small>Maximum of 480 points</small></span>
                    </td>
                    <td class="center"><?php echo number_format($exercise->getAttribute('minutes')) ?> minutes *</td>
                    <td class="center"><?php echo $exercise->getPoints() ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <span class="view-number">3.</span> Steps
                        <br/>
                        <span class="name-tip"><small>5 points/week for 70,000 steps</small></span>
                    </td>
                    <td class="center"><?php echo number_format($status->getComplianceViewStatus('fitbit')->getComment()) ?></td>
                    <td class="center"><?php echo $status->getComplianceViewStatus('fitbit')->getPoints() ?></td>
                    <td class="center">
                        <?php echo implode(' ', $status->getComplianceViewStatus('fitbit')->getComplianceView()->getLinks()) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="view-number">4.</span> eLearning &amp; Other Ed.
                        <br/>
                        <span class="name-tip"><small>Maximum of 75 points  <br /> </small></span>
                    </td>
                    <td class="center"><?php echo $status->getComplianceViewStatus('elearning')->getAttribute('time_topics') ?></td>
                    <td class="center"><?php echo $status->getComplianceViewStatus('elearning')->getPoints() ?></td>
                    <td class="center">
                        <?php echo implode(' ', $status->getComplianceViewStatus('elearning')->getComplianceView()->getLinks()) ?>
                    </td>
                </tr>
                <?php $this->printViewRow($status, 'pre_post', 5, true, true) ?>
                <tr>
                    <td><span class="view-number">6.</span> Total Points</td>
                    <td></td>
                    <td class="center"><?php echo $status->getPoints() ?></td>
                    <td><?php echo $status->getComplianceViewGroupStatus('totals')->getComplianceViewGroup()->getAttribute('max_possible') ?></td>
                </tr>
            </tbody>
        </table>

        <p>* Exercise maximums of 120 total minutes/day and 4,800 cumulative minutes count toward your points.</p>

        <h4 id="rewards-heading">Prizes!</h4>

        <p><div style="font-weight: bold">Goal Prize</div><p>
            <p>Earn a 2016 Total Health Challenge wireless mini Bluetooth speaker PLUS a chance to win the
            Southwest Airlines Vacation get-away package if you:
                <ul>
                    <li>Complete your pre- and post- measurements; AND</li>
                    <li>Lose 10 pounds OR log a minimum of 1,440 minutes of exercise during the challenge</li>
                </ul>

        <p><div style="font-weight: bold">Cash Prizes</div>
            <p>Individual Cash Prizes - 1st Place Male/Female in each age class<br />
               Buddy Cash Prizes - 1st Place Buddy Team<br />
                Team Cash Prizes - Top 5 Teams</p>

        <p><div style="font-weight: bold">Top Scoring Leader Award</div>Open to all levels of management.</p>

        <?php
    }

    private function printViewRow($status, $name, $number, $isInOperations = true, $forcePoints = false)
    {
        $viewStatus = $status->getComplianceViewStatus($name);
        $view = $viewStatus->getComplianceView();

        ?>
        <tr class="<?php echo "view-{$name}" ?>">
            <td>
                <?php echo sprintf('<span class="view-number">%s.</span> %s', $number, $view->getReportName()) ?>
                <?php if($nameTip = $view->getAttribute('name_tip')) : ?>
                    <br/>
                    <span class="name-tip"><small><?php echo $nameTip ?></small></span>
                <?php endif ?>

            </td>
            <td class="center">
                <?php echo $viewStatus->getAttribute('time_topics') ?>
            </td>

            <?php if($isInOperations) : ?>
                <td class="points center"><?php if($forcePoints) { echo $viewStatus->getPoints(); } ?></td>
                <td class="center"><?php echo implode(' ', $view->getLinks()) ?></td>
            <?php else : ?>
                <td class="points center"><?php echo $viewStatus->getPoints() ?></td>
                <td><?php echo $view->getAttribute('max_possible') ?></td>
            <?php endif ?>
        </tr>
    <?php
    }

    private function getGroupReportName($status, $group)
    {
        return $status->getComplianceViewGroupStatus($group)->getComplianceViewGroup()->getReportName();
    }
}

class UCMCTHC2015TeamDashboardPrinter extends UCMCTHC2015BuddyAndTeamDashboardPrinter implements TeamDashboardPrinter
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

class UCMCTHC2015BuddyDashboardPrinter extends UCMCTHC2015BuddyAndTeamDashboardPrinter implements BuddyDashboardPrinter
{
    public function printReport(ComplianceProgramStatus $localStatus, ComplianceProgramStatus $userStatus)
    {
        $this->_printReport(array($localStatus, $userStatus), 'Buddy');
    }
}


abstract class UCMCTHC2015BuddyAndTeamDashboardPrinter
{
    protected function _printReport(array $statuses, $heading = 'Users')
    {
        $totalPoints = 0;
        $totalMinutes = 0;

        ?>
        <script type="text/javascript">
            $(function() {
                $('#ucmc-thc-header').insertBefore($('#compliance_tabs'));
            });
        </script>

        <div class="page-header" id="ucmc-thc-header">
            <div class="row">
                <div class="span12" style="text-align:center">
                    <p><img src="/resources/6971/UCMC_THC_2016_Dates012516.png" alt="" /></p>
                </div>
            </div>
        </div>

        <table class="table table-striped">
            <thead>
            <tr>
                <th><?php echo $heading ?></th>
                <th>Exercise Minutes</th>
                <th>Total Points *</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($statuses as $status) : ?>
                <?php $statusMinutes = $status->getComplianceViewStatus('exercise')->getAttribute('minutes', 0); ?>
                <?php $statusPoints = $status->getPoints(); ?>
                <tr>
                    <td><?php echo $status->getUser() ?></td>
                    <td><?php echo $statusMinutes; ?></td>
                    <td><?php echo $statusPoints; ?></td>
                    <td></td>
                </tr>
                <?php $totalMinutes += $statusMinutes; ?>
                <?php $totalPoints += $statusPoints; ?>
            <?php endforeach ?>
            </tbody>
            <tfoot>
            <tr>
                <th>Grand Totals</th>
                <th><?php echo $totalMinutes; ?></th>
                <th><?php echo $totalPoints; ?></th>
            </tr>
            <tr>
                <td colspan="3">
                    * Total points from cumulative exercise and education updates plus measure/progress when
                    determined at the end of the program.
                </td>
            </tr>
            </tfoot>
        </table>
    <?php
    }
}

class UCMCTHC2015RegistrationForm extends BaseForm
{
    public function configure()
    {
        $departments = array(
            '',
            'Not Applicable - I am signing up as a spouse/Civil Union partner (and am not an employee/staff)',
            'Senior Management (executive administrator, vice president, etc.)',
            'Management (director, manager, supervisor, etc.; NOT senior management)',
            'Non-Clinical Professional (professional positions in finance, medical/legal, information technology, human resources, marketing, public relations, development, grant and contracts administration, etc.)',
            'Administrative Support (executive assistant, administrative assistant, special assistant, lead coordinator, etc.)',
            'Clerical (patient service coordinator, secretary, project assistant, accounts clerk, medical records clerk, data entry clerk, biller, coder, etc.)',
            'Skilled Maintenance (carpenter, electrician, general maintenance, etc.)',
            'Support Services (food services, environmental services, housekeeper, inventory/receiving specialist, patient transport, public safety, etc.)',
            'Research',
            'Physician/Resident/Physician???s Assistant/Advanced Practice Nurse',
            'Nursing ??? RN providing direct patient care (NOT in a managerial position)',
            'Nursing ??? RN in specialty role (clinical specialist, case manager, RN educator, clinical research etc.)',
            'Technician/Technologist (radiation therapist, imaging tech, radiology tech, vascular tech, biomedical tech, biomedical tech, emergency medical tech, medical lab tech etc.)',
            'Clinical Professional (pharmacist, dietitian, respiratory, physical therapist, occupational therapist, social worker etc.)',
            'BSD non-clinical faculty, academics, or postdocs'
        );

        $departments = array_combine($departments, $departments);

        $departments[''] = 'Select One';

        $this->setWidgets(array(
            'first_name'    => new sfWidgetFormInputText(array(), array('class' => 'span6', 'readonly' => 'readonly')),
            'last_name'     => new sfWidgetFormInputText(array(), array('class' => 'span6', 'readonly' => 'readonly')),
            'department'    => new sfWidgetFormSelect(array('choices' => $departments), array('class' => 'span12')),
            'date_of_birth' => new sfWidgetFormInputText(array(), array('class' => 'span5', 'readonly' => 'readonly')),
            'gender'        => new sfWidgetFormSelect(array('choices' => Gender::get(true)), array('class' => 'span2', 'readonly' => 'readonly')),
            'employee_id'   => new sfWidgetFormInputText(array(), array('class' => 'span5', 'readonly' => 'readonly')),
            'email_address' => new sfWidgetFormInputText(array(), array('class' => 'span6', 'readonly' => 'readonly')),
            'phone_number'  => new sfWidgetFormInputText(array(), array('class' => 'span6', 'readonly' => 'readonly')),
            'agree'         => new sfWidgetFormInputCheckbox(array('value_attribute_value' => 1))
        ));

        $this->setValidators(array(
            'first_name'    => new sfValidatorPass(),
            'last_name'     => new sfValidatorPass(),
            'department'    => new sfValidatorChoice(array('choices' => array_keys($departments))),
            'date_of_birth' => new sfValidatorPass(),
            'gender'        => new sfValidatorPass(),
            'employee_id'   => new sfValidatorPass(),
            'email_address' => new sfValidatorPass(),
            'phone_number'  => new sfValidatorPass(),
            'agree'         => new sfValidatorChoice(array('choices' => array(1)))
        ));
    }
}

class UCMCTHC2015RegistrationFormPrinter implements RegistrationFormPrinter
{
    public function printForm(BaseForm $form, $url, User $user)
    {
        $userEmails = $userEmails = $user->getEmailAddresses();;

        $formDefaults = array(
            'first_name'    => $user->first_name,
            'last_name'     => $user->last_name,
            'date_of_birth' => date('m/d/Y', strtotime($user->date_of_birth)),
            'gender'        => $user->gender,
            'employee_id'   => $user->employeeid,
            'phone_number'  => $user->day_phone_number,
            'email_address' => isset($userEmails['Primary']) ? $userEmails['Primary']['email_address'] : ''
        );

        $form->setDefaults($formDefaults);
        ?>
        <style type="text/css">
            .registration-form legend {
                text-transform:uppercase;
                color:#8B0020;
                border-color:#8B0020;
            }

            #pedometer, #goal, #t_shirt_size {
                width:auto !important;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                $('.registration-form .error_list').parent().addClass('error').addClass('text-error');
            });
        </script>

        <p>Hi <?php echo $user->first_name ?>,</p>

        <p>You haven't signed up for the <em>Total Health Challenge</em> program. To do
            this, fill out the form below and select Sign Up.</p>

        <p>If you need to make changes to any grayed out fields below, visit
            <a href="<?php echo url_for('my_account') ?>">my account</a>.</p>

        <br/>

        <?php echo $form->renderFormTag('' , array('append_class' => 0, 'class' => 'registration-form')) ?>
        <span><?php echo $form->renderGlobalErrors() ?></span>

        <fieldset>
            <legend>1. Contact Information</legend>

            <div class="row">
            <span class="span6">
                <label>First name</label>
                <?php echo $form['first_name'] ?>
            </span>

            <span class="span6">
                <label>Last name</label>
                <?php echo $form['last_name'] ?>
            </span>


            </div>

            <div class="row">
            <span class="span5">
                <label>Date of birth</label>
                <?php echo $form['date_of_birth'] ?>
            </span>

            <span class="span2">
                <label>Gender</label>
                <?php echo $form['gender'] ?>
            </span>

            <span class="span5">
                <label>UChicago ID</label>
                <?php echo $form['employee_id'] ?>
            </span>
            </div>

            <div class="row">
            <span class="span6">
                <label>Email</label>
                <?php echo $form['email_address'] ?>
            </span>

            <span class="span6">
                <label>Phone</label>
                <?php echo $form['phone_number'] ?>
            </span>

            </div>

            <div class="row">
            <span class="span12">
                <label>Job Responsibility - Choose one:</label>
                <?php echo $form['department'] ?>
        <?php echo $form['department']->renderError() ?>
            </span>
            </div>

        </fieldset>

        <fieldset>
            <legend>2. Please Read!</legend>

            <div class="row">
                <span class="span12">
                    <?php if($user->client_id == 2251) : ?>
        <p>By signing up for the Total Health Challenge (THC): A)
            For the duration of the 2016 THC (i.e., from the time I sign-up through the date
            THC prize winners are publicly announced), I hereby authorize University of Chicago
            Medical Center Wellness and Health Management staff to access and utilize the
            personal information I submit or have collected under the THC, including my body fat
            percentage, body circumference, weight and blood pressure, for purposes of
            administering the THC, Well Rewards, and determining the winners of THC prizes; and B)
                        <span style="color:#FF0000">I understand that all information collected for this
                            program will be kept strictly confidential.</span></p>
    <?php else : ?>
        <p>By signing up for the Total Health Challenge (THC): A)
            For the duration of the 2016 THC (i.e., from the time I sign-up through the date
            THC prize winners are publicly announced), I hereby authorize University of Chicago
            Medical Center Wellness and Health Management staff to access and utilize the
            personal information I submit or have collected under the THC, including my body fat
            percentage, body circumference, weight and blood pressure, for purposes of
            administering the THC and determining the winners of THC prizes; and B)
                        <span style="color:#FF0000">I understand that all information collected for this
                            program will be kept strictly confidential.</span></p>
    <?php endif ?>

                    <p>The goal of 1440 minutes of exercise is derived from current ACSM guidelines. However, we want
                        the program to be accessible to all eligible employees and spouses (including partners in civil
                         unions recognized in Illinois). If a participant is unable to
                        accomplish this goal due to physical limitations or a doctor???s restrictions, we will work with
                        the participant to provide alternatives to this goal on a case by case basis.</p>

                    <p style="color:#0000FF">It is recommended that men 45 years of age and older and women 55 years
                        of age and older, or who have either chronic disease (for example: heart disease, diabetes,
                        limiting arthritis) or risk factors (such as: high blood pressure, injury, obesity, heavy smoking
                        or high blood cholesterol) consult their physician prior to beginning an exercise program.</p>

                        <div style="text-align:right;font-style:italic"><small>- Journal of the American Medical Association, Vol. 273, N0.5</small></div>
                    </blockquote>
                </span>
            </div>

            <div class="row">
            <span class="span12">
                <label class="checkbox">
                    <?php echo $form['agree'] ?>
        <?php echo $form['agree']->renderError() ?>
                    I understand the terms as shown above.
                </label>

            </span>
            </div>
        </fieldset>

        <div class="form-actions">
            <?php echo $form->renderHiddenFields() ?>

            <input type="submit" value="Sign Up" class="btn btn-primary" />
        </div>
        </form>
    <?php
    }
}
