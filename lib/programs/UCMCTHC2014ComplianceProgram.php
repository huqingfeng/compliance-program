<?php

use hpn\wms\model\UCMCTHCModel;

class UCMCTHC2014ComplianceProgram extends ComplianceProgram
{
    public function getTeamDashboardPrinter()
    {
        return new UCMCTHC2014TeamDashboardPrinter();
    }

    public function getBuddyDashboardPrinter()
    {
        return new UCMCTHC2014BuddyDashboardPrinter();
    }

    public function getRegistrationForm()
    {
        return new UCMCTHC2014RegistrationForm();
    }

    public function getRegistrationFormPrinter()
    {
        return new UCMCTHC2014RegistrationFormPrinter();
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new UCMCTHC2014ProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $program = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(null, null, null, null, null, null, null, true);

        $printer->setShowUserContactFields(true, true, true);

        $record = $this->getComplianceProgramRecord();

        $printer->addCallbackField('age', function(User $user) {
            return $user->getAge();
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

        $printer->addMultipleCallbackFields(function (User $user) use($record, $program) {
            $prePostFields = array(
                'date',
                'bmi',
                'bodyfat',
                'body_fat_method',
                'chest',
                'diastolic',
                'exercise_level',
                'height',
                'hips',
                'systolic',
                'thigh',
                'waist',
                'waist_hip',
                'weight'
            );

            $ret = array();

            $preData = $program->getPreScreeningData($user);
            $postData = $program->getPostScreeningData($user);
            $assembledData = $program->getAssembledScreeningData($preData, $postData);

            foreach($prePostFields as $field) {
                $ret["pre_$field"] = isset($preData[$field]) ? $preData[$field] : '';
                $ret["post_$field"] = isset($postData[$field]) ? $postData[$field] : '';
                $ret["pre_post_points_$field"] = isset($assembledData['points'][$field]) ?
                    $assembledData['points'][$field] : 0;
            }

            return $ret;
        });

        $printer->addMultipleCallbackFields(function(User $user) use($record) {
            $registration = $record->getRegistrationRecord($user->id);

            return array(
                'registered' => $registration ? 1 : 0,
                'department' => isset($registration['department']) ? $registration['department'] : ''
            );
        });

        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(true, false, false);
        $printer->setShowPoints(true, false, false);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            return array(
                'aerobic_minutes'     => $status->getComplianceViewStatus('aerobic_exercise')->getAttribute('minutes', 0),
                'resistance_exercise' => $status->getComplianceViewStatus('resistance_exercise')->getAttribute('minutes', 0),
                'exercise_points'     => $status->getComplianceViewStatus('exercise')->getPoints(),
                'education_points'    => $status->getComplianceViewStatus('elearning')->getPoints(),
            );
        });

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '3000M');

        $program = $this;

        $this->options = array(
            'allow_teams'                    => true,
            'allow_team_buddy_removal'       => true,
            'team_members_minimum'           => 6,
            'team_members_maximum'           => 6,
            'team_members_maximum_males'     => 3,
            'require_registration'           => true,
            'force_spouse_with_employee'     => true,
            'registration_end_date'          => '2015-12-31 23:59:59',
            'team_buddy_management_end_date' => '2015-12-31 23:59:59',
            'registration_redirect'          => '/compliance_programs/?id=307'
        ) + $this->options;

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $operations = new ComplianceViewGroup('operations', 'Exercise Log');

        $resView = new SumMinutesInArbitraryActivityComplianceView($startDate, $endDate, 326);
        $resView->setReportName('Resistance Exercise');
        $resView->setAttribute('name_tip', '3 points/30 minutes');
        $resView->setName('resistance_exercise');
        $operations->addComplianceView($resView);

        $aerView = new SumMinutesInArbitraryActivityComplianceView($startDate, $endDate, 327);
        $aerView->setAttribute('name_tip', '2 points/30 minutes');
        $aerView->setReportName('Aerobic Exercise');
        $aerView->setName('aerobic_exercise');
        $operations->addComplianceView($aerView);

        $elearn = new CompleteELearningGroupSet($startDate, '2015-03-28', 'required_thc_2015');
        $elearn->setName('elearning');
        $elearn->setAttribute('goal', '3 topics');
        $elearn->setPointsPerLesson(25);
        $elearn->setMaximumNumberOfPoints(75);
        $elearn->setReportName('eLearning & Other Ed.');
        $elearn->setForceAllowPointsOverride();
        $elearn->setAttribute('name_tip', '25 points/program');
        $elearn->setPostEvaluateCallback(function($status) {
            $numberDone = floor($status->getPoints() / 25);

            $status->setAttribute('time_topics', "{$numberDone} completed");
        });
        $elearn->emptyLinks();
        $elearn->addLink(new Link('E-Learning Lessons', '/content/9420?action=lessonManager&tab_alias=required_thc_2015'));
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

            $resistanceMinutesByDay = $resistanceStatus->getAttribute('minutes_by_day');

            $dailyPoints = array();

            $minutesUsed = 0;

            $resistanceDivider = 10;
            $resistanceDailyCap = 12;
            $aerobicDivider = 15;
            $aerobicDailyCap = 8;
            $dailyCap = 12;
            $dailyMinutesCap = 120;
            $totalMinutesCap = 4800;
            $totalCap = 480;

            foreach($resistanceMinutesByDay as $day => $minutes) {
                if(!isset($dailyPoints[$day])) {
                    $dailyPoints[$day] = 0;
                }

                $cappedMinutes = min($dailyMinutesCap, $minutes);

                $minutesAllowed = max(0, $totalMinutesCap - $minutesUsed);

                $resistanceMinutes = min($cappedMinutes, $minutesAllowed);

                $minutesUsed += $resistanceMinutes;

                $dailyPoints[$day] += min($resistanceDailyCap, round($resistanceMinutes / $resistanceDivider, 2));
            }

            foreach($aerobicStatus->getAttribute('minutes_by_day') as $day => $minutes) {
                if(!isset($dailyPoints[$day])) {
                    $dailyPoints[$day] = 0;
                }

                $minutesAllowed = max(0, $totalMinutesCap - $minutesUsed);

                $resistanceMinutes = isset($resistanceMinutesByDay[$day]) ?
                    $resistanceMinutesByDay[$day] : 0;

                $aerobicMinutesAllowed = max(0, $dailyMinutesCap - $resistanceMinutes);

                $aerobicMinutesCounted = min($minutesAllowed, min($aerobicMinutesAllowed, $minutes));

                $minutesUsed += $aerobicMinutesCounted;

                $dailyPoints[$day] += min($aerobicDailyCap, round($aerobicMinutesCounted / $aerobicDivider, 2));
            }

            $totalPoints = 0;

            foreach($dailyPoints as $day => $points) {
                $dailyPoints[$day] = min($dailyCap, $points);

                $totalPoints += $dailyPoints[$day];
            }

            $today = date('Ymd');

            $status->setAttribute('minutes_today', $aerobicStatus->getAttribute('minutes_today') + $resistanceStatus->getAttribute('minutes_today'));
            $status->setAttribute('points_today', isset($dailyPoints[$today]) ? $dailyPoints[$today] : 0);
            $status->setAttribute('minutes', min($totalMinutesCap, $aerobicStatus->getAttribute('minutes') + $resistanceStatus->getAttribute('minutes')));

            $status->setPoints(min($totalCap, $totalPoints));
        });

        $totals->addComplianceView($exercisePoints);

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

            <p><div style="font-weight:bold">DEADLINE:</div> All team and buddy selections need to be made by January 24th, 2015.</p>

            <p>Note: Employees choosing not to participate on a team or with a buddy, you will be considered an individual.</p>
        </script>

        <script type="text/html" id="new-manage-team-instructions">
            <p>To invite a person to your team, enter their last name below and select <em>Search</em>.<p>
            <p>Then, select <em>Invite</em> next to the appropriate person. A team must have 6 members (including the captain) with a maximum of 3 men.</p>
            <p>Skip this step if you are not a team captain. AND contact Employee Wellness at <a href="mailto:hr.wellness@uchospitals.edu">hr.wellness@uchospitals.edu</a> to delete the team name you have accidentally created.</p>
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
            team mebers by February 7, 2015.
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
        return array('pre_post_measurements' => array($this, 'executePrePostMeasurements'));
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
            $data = Screening::getMergedData($user, new \DateTime('2015-02-15'), new \DateTime('2015-04-09'), array(
                    'filter'    => function($array) {
                        return (
                            isset($array['body_fat_method']) &&
                            in_array(trim(strtolower($array['body_fat_method'])), array('biomeasure', 'omron'))
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
}

class SumMinutesInArbitraryActivityComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function __construct($startDate, $endDate, $activityId)
    {
        $this->activityId = $activityId;

        parent::__construct($startDate, $endDate);
    }

    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        $minutes = 0;

        $todayMinutes = 0;

        $minutesByDay = array();

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            $day = date('Ymd', $record->getDate('U'));

            if(isset($answers[ActivityTrackerQuestion::MINUTES])) {
                $answer = (int)$answers[ActivityTrackerQuestion::MINUTES]->getAnswer();

                if($day == date('Ymd')) {
                    $todayMinutes += $answer;
                }

                if(!isset($minutesByDay[$day])) {
                    $minutesByDay[$day] = 0;
                }

                $minutesByDay[$day] += $answer;

                $minutes += $answer;
            }
        }

        $status = new ComplianceViewStatus(
            $this,
            ComplianceStatus::NA_COMPLIANT
        );

        $status->setAttribute('minutes_by_day', $minutesByDay);
        $status->setAttribute('minutes', $minutes);
        $status->setAttribute('minutes_today', $todayMinutes);
        $status->setAttribute('time_topics', "{$minutes} minutes");

        return $status;
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $activityId;
}

class UCMCTHC2014ProgramReportPrinter implements ComplianceProgramReportPrinter
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
                            <li><a href="/content/1051">Make an appointment for your pre-contest measurements</a></li>
                        </ul>

                        <p>Thank you for participating!</p>
                    </div>
                    <div class="span5">
                        <p><img src="/resources/5721/Step-It-Up-banner.080315.jpeg" alt="" /></p>
                        <p><a href="/content/1051">Schedule / Adjust your Post-Contest Appointment</a></p>
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
                    <td class="center"><?php echo $exercise->getAttribute('minutes_today') ?> minutes *</td>
                    <td class="center"><?php echo $exercise->getAttribute('points_today') ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <span class="view-number">2.</span> Exercise Cumulative
                        <br/>
                        <span class="name-tip"><small>Maximum of 480 points</small></span>
                    </td>
                    <td class="center"><?php echo $exercise->getAttribute('minutes') ?> minutes *</td>
                    <td class="center"><?php echo $exercise->getPoints() ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <span class="view-number">3.</span> eLearning &amp; Other Ed.
                        <br/>
                        <span class="name-tip"><small>Maximum of 75 points  <br /> </small></span>
                    </td>
                    <td class="center"><?php echo $status->getComplianceViewStatus('elearning')->getAttribute('time_topics') ?></td>
                    <td class="center"><?php echo $status->getComplianceViewStatus('elearning')->getPoints() ?></td>
                    <td class="center">
                        <?php echo implode(' ', $status->getComplianceViewStatus('elearning')->getComplianceView()->getLinks()) ?>
                    </td>
                </tr>
                <?php $this->printViewRow($status, 'pre_post', 4, true, true) ?>
                <tr>
                    <td><span class="view-number">5.</span> Total Points</td>
                    <td></td>
                    <td class="center"><?php echo $status->getPoints() ?></td>
                    <td><?php echo $status->getComplianceViewGroupStatus('totals')->getComplianceViewGroup()->getAttribute('max_possible') ?></td>
                </tr>
            </tbody>
        </table>

        <p>* Exercise maximums of 120 total minutes/day and 4,800 cumulative minutes count toward your points.</p>

        <h4 id="rewards-heading">Prizes!</h4>

        <p><div style="font-weight: bold">Goal Prize</div><p>
            <p>Earn a sports duffle bag PLUS a chance to win a new bicycle if you:
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

class UCMCTHC2014TeamDashboardPrinter extends UCMCTHC2014BuddyAndTeamDashboardPrinter implements TeamDashboardPrinter
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

class UCMCTHC2014BuddyDashboardPrinter extends UCMCTHC2014BuddyAndTeamDashboardPrinter implements BuddyDashboardPrinter
{
    public function printReport(ComplianceProgramStatus $localStatus, ComplianceProgramStatus $userStatus)
    {
        $this->_printReport(array($localStatus, $userStatus), 'Buddy');
    }
}


abstract class UCMCTHC2014BuddyAndTeamDashboardPrinter
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
                    <p><img src="/resources/5721/Step-It-Up-banner.080315.jpeg" alt="" /></p>
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

class UCMCTHC2014RegistrationForm extends BaseForm
{
    public function configure()
    {
        $departments = array(
            '',
            'Not Applicable - I am signing up as a spouse/partner (and am not an employee/staff)',
            'Senior Management (executive administrator, vice president, etc.)',
            'Management (director, manager, supervisor, etc.; NOT senior management)',
            'Non-Clinical Professional (professional positions in finance, medical/legal, information technology, human resources, marketing, public relations, development, grant and contracts administration, etc.)',
            'Administrative Support (executive assistant, administrative assistant, special assistant, lead coordinator, etc.)',
            'Clerical (patient service coordinator, secretary, project assistant, accounts clerk, medical records clerk, data entry clerk, biller, coder, etc.)',
            'Skilled Maintenance (carpenter, electrician, general maintenance, etc.)',
            'Support Services (food services, environmental services, housekeeper, inventory/receiving specialist, patient transport, public safety, etc.)',
            'Research',
            'Physician/Resident/Physician’s Assistant/Advanced Practice Nurse',
            'Nursing – RN providing direct patient care (NOT in a managerial position)',
            'Nursing – RN in specialty role (clinical specialist, case manager, RN educator, clinical research etc.)',
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

class UCMCTHC2014RegistrationFormPrinter implements RegistrationFormPrinter
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
                            For the duration of the 2015 THC (i.e., from the time I sign-up through the date
                            THC prize winners are publicly announced), I hereby authorize University of Chicago
                            Medical Center Wellness and Health Management staff to access and utilize the
                            personal information I submit or have collected under the THC, including my body fat
                            percentage, body circumference, weight and blood pressure, for purposes of
                            administering the THC, Well Rewards, and determining the winners of THC prizes; and B)
                        <span style="color:#FF0000">I understand that all information collected for this
                            program will be kept strictly confidential.</span></p>
                    <?php else : ?>
                        <p>By signing up for the Total Health Challenge (THC): A)
                            For the duration of the 2015 THC (i.e., from the time I sign-up through the date
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
                        unions recognized in Illinois and same sex domestic partners). If a participant is unable to
                        accomplish this goal due to physical limitations or a doctor’s restrictions, we will work with
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
