<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

define('BEACON_FIVE_FOR_FIVE_CAMPAIGN', 'beacon_five_for_five_campaign_2016');
define('BEACON_FIVE_FOR_FIVE_CAMPAIGN_START_DATE', '2016-01-01');
define('BEACON_FIVE_FOR_FIVE_CAMPAIGN_END_DATE', '2016-12-31');

class Beacon5For5CampaignWeeklyPointsComplianceView extends ComplianceView
{
    public function __construct($week)
    {
        $this->week = $week;
    }

    public function getDefaultName()
    {
        return 'weekly_points';
    }

    public function getDefaultReportName()
    {
        return 'Weekly Points';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord(BEACON_FIVE_FOR_FIVE_CAMPAIGN, true);
        $points = BeaconFiveForFiveCampaignCommitmentForm::getPoints($record);

        $weeklyPoints = 0;
        if(isset($points[$this->week]['total_points'])) {
            $weeklyPoints = $points[$this->week]['total_points'];
        }

        $status = new ComplianceViewStatus($this, null, $weeklyPoints);

        return $status;
    }
}

class Beacon5For5CampaignAdminPrinter extends BasicComplianceProgramAdminReportPrinter
{
    public function __construct($record)
    {
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

class Beacon5For5Campaign2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $record = $this->getComplianceProgramRecord();

        return new Beacon5For5CampaignAdminPrinter($record);
    }

    public function getTeamDashboardPrinter()
    {
        return new Beacon5For5CampaignTeamDashboardPrinter();
    }

    public function getTeamData(sfActions $actions)
    {
        $activeProgramRecord = ComplianceProgramRecordTable::getInstance()->find(695);

        $teams = $activeProgramRecord->getTeams();

        $allUsersPoints = BeaconFiveForFiveCampaignCommitmentForm::getAllUsersPoints($actions);

        $allTeamData = array();
        foreach($teams as $team) {
            $teamData = array('points' => 0, 'users' => array());

            foreach($team['users'] as $userId => $userData) {
                if(isset($allUsersPoints[$userId]) && $userData['accepted']) {
                    $user = UserTable::getInstance()->find($userId);

                    $teamData['users'][] = array(
                        'points' => $allUsersPoints[$userId],
                        'name' => $user->getFullName()
                    );

                    $teamData['points'] += $allUsersPoints[$userId];
                }
            }

            $teamData['average_points'] = count($team['users']) < 1 ?
                0 : round($teamData['points'] / count($team['users']), 2);

            uasort($teamData['users'], function($a, $b) {
                return $b['points'] - $a['points'];
            });

            $allTeamData[$team['name']] = $teamData;
        }

        return $allTeamData;
    }

    public function getBuddyDashboardPrinter()
    {
        return new Beacon5For5CampaignBuddyDashboardPrinter();
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Beacon5For5CampaignProgramReportPrinter();
    }

    public function getAverageWeeklySteps(ComplianceProgramStatus $status)
    {
        $startDate = new \DateTime($this->getStartDate('Y-m-d'));
        $today = new \DateTime(date('Y-m-d'));

        $daysToAverage = max(1, $today->diff($startDate)->format('%a'));
        $weeksToAverage = max(1, round($daysToAverage / 7));

        return round($this->summarizeUserStatusForTeamLeaderboard($status) / $weeksToAverage);
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
                'team_members_invite_end_date' => false,
                'team_create_end_date'      => '2016-05-01',
                'team_leaderboard'           => true,
                'force_spouse_with_employee'  => true,
                'force_spouse_with_employee_excluded_spouses'   => array(2887676),
                'points_label'               => 'points',
                'total_steps'               => false,
            ) + $this->options;

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $operations = new ComplianceViewGroup('operations', '5 For 5 Campaign');

        $week1View = new Beacon5For5CampaignWeeklyPointsComplianceView('week1');
        $week1View->setReportName('Week 1 Points');
        $week1View->setName('week1_points');
        $week1View->addLink(new Link('Log fruits & vegetables', '/compliance_programs/localAction?id=695&local_action=5for5_dashboard'));
        $operations->addComplianceView($week1View);

        $week2View = new Beacon5For5CampaignWeeklyPointsComplianceView('week2');
        $week2View->setReportName('Week 2 Points');
        $week2View->setName('week2_points');
        $week2View->addLink(new Link('Log fruits & vegetables', '/compliance_programs/localAction?id=695&local_action=5for5_dashboard'));
        $operations->addComplianceView($week2View);

        $week3View = new Beacon5For5CampaignWeeklyPointsComplianceView('week3');
        $week3View->setReportName('Week 3 Points');
        $week3View->setName('week3_points');
        $week3View->addLink(new Link('Log fruits & vegetables', '/compliance_programs/localAction?id=695&local_action=5for5_dashboard'));
        $operations->addComplianceView($week3View);

        $week4View = new Beacon5For5CampaignWeeklyPointsComplianceView('week4');
        $week4View->setReportName('Week 4 Points');
        $week4View->setName('week4_points');
        $week4View->addLink(new Link('Log fruits & vegetables', '/compliance_programs/localAction?id=695&local_action=5for5_dashboard'));
        $operations->addComplianceView($week4View);

        $week5View = new Beacon5For5CampaignWeeklyPointsComplianceView('week5');
        $week5View->setReportName('Week 5 Points');
        $week5View->setName('week5_points');
        $week5View->addLink(new Link('Log fruits & vegetables', '/compliance_programs/localAction?id=695&local_action=5for5_dashboard'));
        $operations->addComplianceView($week5View);

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
                    '<img src="/resources/7181/web_banner_5weeks.jpg" />' +
                    '<p style="font-weight:bold">Your campaign runs <?php echo $this->getStartDate('m/d/Y') ?> ' +
                    'through <?php echo $this->getEndDate('m/d/Y') ?>.</p>'
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
You have been invited by your coworker to participate in the 5 For 5 campaign.

- To accept the invitation
- Login to Circlewell.com and click on the 5 For 5 button to start logging your activity!
- On your Mark...Get Set...Go!!
EOT
            ),

            'buddy_request' => array(
                'subject' => 'You have been sent a buddy invitation.',
                'body'    => <<<EOT
You have received a buddy invitation to participate in the 5 For 5 campaign.

- To accept the invitation
- Login to Circlewell.com and click on the 5 For 5 button to start logging your activity!
- On your Mark...Get Set...Go!!
EOT
            )
        );
    }

    public function execute5For5Dashboard(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $form = new BeaconFiveForFiveCampaignCommitmentForm(array(), array('user' => $user));

        $record = $user->getNewestDataRecord(BEACON_FIVE_FOR_FIVE_CAMPAIGN, true);

        $points = BeaconFiveForFiveCampaignCommitmentForm::getPoints($record);
        $clientPoints = BeaconFiveForFiveCampaignCommitmentForm::getClientPoints($actions);

        ?>
        <?php use_asset_bundle('FancyBox') ?>

        <script type="text/javascript">

            $(document).ready(function(){

                var anchor = self.document.location.hash.substring(1);
                if(anchor) {
                    $('.nav.nav-tabs a[name='+anchor+']').click();
                }

                $('#healthy_holiday_recipes .health_recipe_list  .fancy_box').each(function() {
                    $(this).fancybox();
                });

                $('body form').removeClass('menagerie');

                $('#participate').change(function(){
                    if($('#participate').is(':checked')) {
                        $('#not_participate, #not_participate_reason').prop('disabled', true);
                    } else {
                        $('#not_participate, #not_participate_reason').prop('disabled', false);
                    }
                });

                $('#not_participate').change(function(){
                    if($('#not_participate').is(':checked')) {
                        $('#participate, #maintain_current_weight, #lose_weight').prop('disabled', true);
                    } else {
                        $('#participate, #maintain_current_weight, #lose_weight').prop('disabled', false);
                    }
                });

                $('#maintain_current_weight').change(function() {
                    if($('#maintain_current_weight').is(':checked')) {
                        $('#lose_weight').prop('disabled', true);
                    } else {
                        $('#lose_weight').prop('disabled', false);
                    }
                });

                $('#lose_weight').change(function() {
                    if($('#lose_weight').is(':checked')) {
                        $('#maintain_current_weight').prop('disabled', true);
                    } else {
                        $('#maintain_current_weight').prop('disabled', false);
                    }
                });


                $('#print_your_strategies_link').click(function(){
                    $(this).closest('form').submit();
                });

                $('#smoothie_recipes  .fancy_box').each(function() {
                    $(this).fancybox();
                });

                $('.week_name').click(function() {
                    $(this).parent().children('.week_content').toggleClass('hide');
                });
            });
        </script>

        <style type="text/css">
            #statement p {
                width: 92%;
                padding: 20px 30px 20px 30px;
            }

            #head_menu {
                font-size: 8.5pt;
                font-weight: bold;
                margin-left: 6px;
            }

            #head_menu .nav.nav-tabs li{
                margin-left: -6px;
            }

            .nav.nav-tabs a[name=serving_sizes]{
                background-image: url(/images/amway/wellness_web_portal/button1.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs .active a[name=serving_sizes]{
                background-image: url(/images/amway/wellness_web_portal/Button1_roll.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs a[name=eat_the_rainbow]{
                background-image: url(/images/amway/wellness_web_portal/button2.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs .active a[name=eat_the_rainbow]{
                background-image: url(/images/amway/wellness_web_portal/Button2_roll.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs a[name=nutrition_label]{
                background-image: url(/images/amway/wellness_web_portal/button3.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs .active a[name=nutrition_label]{
                background-image: url(/images/amway/wellness_web_portal/Button3_roll.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs a[name=eat_more_servings]{
                background-image: url(/images/amway/wellness_web_portal/button4.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs .active a[name=eat_more_servings]{
                background-image: url(/images/amway/wellness_web_portal/Button4_roll.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs a[name=smoothie_basics]{
                background-image: url(/images/amway/wellness_web_portal/button5.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs .active a[name=smoothie_basics]{
                background-image: url(/images/amway/wellness_web_portal/Button5_roll.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs a[name=record_your_food]{
                background-image: url(/images/amway/wellness_web_portal/button5.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }

            .nav.nav-tabs .active a[name=record_your_food]{
                background-image: url(/images/amway/wellness_web_portal/Button5_roll.png);
                background-size: 100% 100%;
                background-repeat: no-repeat;
                color:#eee;
            }


            #weight_maintenance_strategies_section ul {
                list-style-type: none;
            }

            #weight_maintenance_strategies_section ul li label {
                font-size: 10pt;
            }

            #calorie_activities_table tr th {
                background-color: #E67A45;
                color: #FFFFFF;
                padding: 5px;
            }

            #calorie_activities_table tr td {
                padding: 5px;
            }

            .health_recipe_list h5 {
                text-align: center;
            }

            #fruit_list ul {
                list-style-type: none;
            }

            #fruit_list ul li {
                padding-bottom: 2px;
            }

            #health_holiday_form_section div {
                padding-bottom: 10px;
            }

            .lbs input {
                width: 38px;
            }

            #weight_records_section table{
                text-align: center;
            }

            #record_your_food select {
                width:15%;
                margin-right:10px;
            }

            .week_name {
                text-align: center;
                color:#ffffff;
                background-color: purple;
                font-size: 12pt;
                font-weight: bold;
                padding: 8px 35px 8px 14px;
                margin: 20px 0;
                cursor:pointer;
            }

            .hide {
                display: none;
            }

            #five-for-five {
                min-width: 650px;
            }

            #five-for-five-tab-content {
                overflow: visible;
            }


            #wms1 {
                font-size: 12px;
            }
        </style>

        <div id="five-for-five">
            <div id="header_image"><img src="<?php
                echo sfConfig::get('mod_legacy_chp_five_for_five_campaign_header_image', '/resources/5402/Smoothies_030415_header.png')
                ?>" style="width:100%; height: 300px;"/></div>


            <div class="tabbable" id="head_menu">
                <ul class="nav nav-tabs">
                    <li class="active" style="width:18%">
                        <a data-toggle="tab" href="#record_your_food" name="record_your_food"><span>Record your food</span></a>
                    </li>
                    <li style="width:16%">
                        <a data-toggle="tab" href="#serving_sizes" name="serving_sizes"><span>Serving Sizes</span></a>
                    </li>
                    <li style="width:18%">
                        <a data-toggle="tab" href="#eat_the_rainbow" name="eat_the_rainbow"><span>Eat the Rainbow</span></a>
                    </li>
                    <li style="width:15%">
                        <a data-toggle="tab" href="#nutrition_label" name="nutrition_label"><span>Nutrition Label</span></a>
                    </li>
                    <li style="width:18%">
                        <a data-toggle="tab" href="#eat_more_servings" name="eat_more_servings"><span>Eat More Servings</span></a>
                    </li>
                    <li style="width:18%">
                        <a data-toggle="tab" href="#smoothie_basics" name="smoothie_basics"><span>Smoothie Basics</span></a>
                    </li>
                </ul>
            </div>


            <div id="five-for-five-tab-content" class="tab-content">
                <div class="tab-pane active" id="record_your_food">
                    <div>
                        <div style="width: 36%; height: 300px;float: left; color: #ffffff; background-color: #8dc63f;border-radius: 25px; margin: 30px 30px;">
                            <div style="width: 80%; margin: 30px auto;font-size: 12pt; height:100px; "><div style="font-size: 15pt;">Challenge 1:</div><br />
                                To eat 5 servings of fruits and vegetables,<br />
                                5 days weekly, for the next 5 weeks.
                                <ul class="team_points" style="list-style-type: none; font-size: 8pt;">
                                    <li>+ 10 points for each fruit or vegetable you record</li>
                                    <li>+ 20 points for recording 5 in a day</li>
                                    <li>+ 50 points for recording 25 for the week</li>
                                </ul>
                            </div>
                        </div>
                        <div style="width: 36%; height: 300px; float: left; color: #ffffff; background-color: #652d95;border-radius: 25px;margin: 30px 30px;">
                            <div style="width: 80%; margin: 30px auto;font-size: 12pt; height:100px; "><div style="font-size: 15pt;">Challenge 2:</div><br />
                                Try a NEW fruit or vegetable<br />
                                every week.<br /><br />
                                Complete a nutrition related <a href="/content/9420?action=lessonManager&tab_alias=foods_nutrition">eLearning lesson</a>
                                each week of the campaign.<br /><br />
                                <ul class="team_points" style="list-style-type: none; font-size: 9pt;">
                                    <li>+50 points for trying something new</li>
                                    <li>+50 Points for completing e-learning modules</li>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <div style="clear: both; margin-left: 30px; font-weight: bold; font-size:11pt; margin-bottom: 20px;">
                                Record your fruits and vegetables by selecting them from the drop down arrows below and then click submit.
                            </div>
                            <div  style="clear: both; margin-left: 30px; font-size: 12pt; font-weight: bold;">What is a serving?</div>
                            <ul  style="width: 36%; float: left; margin: 10px 50px;">
                                <li>1 medium-size fruit</li>
                                <li>1⁄2 cup (4oz) of 100% fruit juice or vegetable juice</li>
                                <li>1⁄2 cup cooked, frozen or canned vegetables or fruit</li>
                            </ul>

                            <ul  style="width: 36%; float: left; margin: 10px 50px;">
                                <li>1 cup of raw leafy vegetables</li>
                                <li>1⁄2 cup cooked dry peas or beans</li>
                                <li>1⁄4 cup dried fruit</li>
                            </ul>
                        </div>

                        <div style="margin-top: 35px; font-size: 11pt;">
                            <br /><br /><a href="/compliance_programs?id=695">Back to my dashboard</a>
                        </div>

                        <div style="color: red; margin-top: 20px; font-size: 12pt;">
                            Don’t forget to click “submit” after your entry
                        </div>
                    </div>

                    <?php echo $form->renderFormTag('/compliance_programs/localAction?id=695&local_action=save_your_fruits') ?>
                    <?php echo $form->renderGlobalErrors() ?>
                    <?php echo $form->renderHiddenFields() ?>
                    <div  style="clear:both;">
                        <?php foreach(BeaconFiveForFiveCampaignCommitmentForm::getWeeks($user) as $week => $weekName) :?>
                            <?php $startEndDates = BeaconFiveForFiveCampaignCommitmentForm::getWeekStartEndDates(); ?>
    <!--                        --><?php //if($week != 'week1' && isset($startEndDates[$week]['start_date']) && $startEndDates[$week]['start_date'] > date('Y-m-d')) continue; ?>

                            <div style="padding-top:15px;" id="<?php echo $week ?>">
                                <div class="week_name"><?php echo $weekName ?></div>

                                <div class="week_content">
                                    <div><span  class="label label-success" style="line-height:20px; padding-top: 6px;">New Fruit or Vegetable:</span> <?php echo $form["{$week}_new_fruit"] ?></div>

                                    <?php foreach(BeaconFiveForFiveCampaignCommitmentForm::getDays() as $day => $dayName) : ?>
                                        <span class="label label-success" style="line-height:20px; padding-top: 6px;"><?php echo $dayName ?></span>

                                        <?php foreach(BeaconFiveForFiveCampaignCommitmentForm::getFruitTypes() as $fruitType) :?>
                                            <?php echo $form["{$week}_{$day}_{$fruitType}"] ?>
                                        <?php endforeach ?>

                                        <span class="label label-info" style="line-height:20px; padding-top: 6px; width: 50px"><?php echo $points[$week][$day] ?> Points</span>
                                        <br />
                                    <?php endforeach ?>

                                    <div style="line-height:20px; width: 135px;  font-size: 11pt; margin-right: 20px; color: #3a87ad; display: inline; font-weight: bold">My Weekly Points: <?php echo $points[$week]['total_points']; ?></div>
                                    <div style="line-height:20px; width: 220px;  font-size: 11pt; margin-right: 20px; color: #3a87ad; display: inline; font-weight: bold">Company Average Points: <?php echo $clientPoints[$week]['average_points']; ?></div>
                                    <div style="line-height:20px; width: 190px;  font-size: 11pt; color: #3a87ad; display: inline; font-weight: bold">Weekly Leader Points: <?php echo $clientPoints[$week]['max_points']; ?></div>

                                    <div><input type="submit" class="btn btn-primary" style="margin-top: 20px;"></div>
                                </div>
                            </div>
                        <?php endforeach ?>

                    </div>
                    </form>

                    <div style="clear: both"></div>
                    <br/>

                    <div id="points" class="label label-info" style="font-size: 12pt; font-weight: bold; line-height:20px; margin-top: 20px;">
                        <span style="margin-right:20px;">My Total Points: <?php echo $points['total_points'] ?></span>
                        <span style="margin-right:20px;">Company Total Points: <?php echo $clientPoints['total_points']; ?></span>
                        <span>Company Leader: <?php echo $clientPoints['leader_name']; ?></span>
                    </div>
                </div>

                <div class="tab-pane" id="serving_sizes">

                    <div style="margin-bottom: 20px;">
                        <img src="<?php
                        echo sfConfig::get('mod_legacy_chp_five_for_five_campaign_serving_sizes', '/resources/5395/Serving_Sizes_body.png')
                        ?>"  style="width: 100%;"/>
                    </div>

                </div>

                <div class="tab-pane" id="eat_the_rainbow">

                    <div style="margin-bottom: 20px;">
                        <img src="<?php
                        echo sfConfig::get('mod_legacy_chp_five_for_five_campaign_eat_the_rainbow', '/resources/5365/five_for_five_campaign_nutrition_challenge.png')
                        ?>"  style="width: 100%;"/>
                    </div>

                </div>

                <div class="tab-pane" id="nutrition_label">

                    <div style="margin-bottom: 20px;">
                        <img src="<?php
                        echo sfConfig::get('mod_legacy_chp_five_for_five_campaign_nutrition_label', '/resources/5397/Nutrition_Labels_030415_body.png')
                        ?>"  style="width: 100%;"/>
                    </div>

                </div>

                <div class="tab-pane" id="eat_more_servings">

                    <div style="margin-bottom: 20px;">
                        <img src="<?php
                        echo sfConfig::get('mod_legacy_chp_five_for_five_eat_more_servings', '/resources/5399/Servings_TIPS_body.png')
                        ?>"  style="width: 100%;"/>
                    </div>

                </div>

                <div class="tab-pane" id="smoothie_basics">

                    <div style="margin-bottom: 20px;">
                        <img src="/resources/7193/Beacon_Smoothies_5weeks.png"  style="width: 100%;"/>

                        <div id="smoothie_recipes" style="margin-left: 60px">
                            <div>
                                <a href="/resources/5419/Apple_Yogurt_Smoothie.png" class="fancy_box">
                                    <img src="/images/chp/five_for_five_campaign/five_to_five_apple.png" style="width: 11%; margin-right:8%;"/>
                                </a>
                                <a href="/resources/5421/Banana_Breakfast_Smoothie.png" class="fancy_box">
                                    <img src="/images/chp/five_for_five_campaign/five_to_five_banana.png" style="width: 13%; margin-right:8%;"/>
                                </a>
                                <a href="/resources/5423/Peachy_Power_Smoothie.png" class="fancy_box">
                                    <img src="/images/chp/five_for_five_campaign/five_to_five_peach.png" style="width: 13%; margin-right:8%;"/>
                                </a>
                                <a href="/resources/5425/Strawberry_Fields_Smoothie.png" class="fancy_box">
                                    <img src="/images/chp/five_for_five_campaign/five_to_five_strawberry.png" style="width: 15%; margin-right:9%;"/>
                                </a>
                            </div>
                            <div>
                                <a href="/resources/5420/Avocado_Melon_Smoothie.png" class="fancy_box">
                                    <img src="/images/chp/five_for_five_campaign/five_to_five_avocado.png" style="width: 13%; margin-left:8%;"/>
                                </a>
                                <a href="/resources/5422/Blues_Buster_Smoothie.png" class="fancy_box">
                                    <img src="/images/chp/five_for_five_campaign/five_to_five_blue_buster.png" style="width: 13%; margin-left:8%;"/>
                                </a>
                                <a href="/resources/5424/Pomegranate_Smoothie.png" class="fancy_box">
                                    <img src="/images/chp/five_for_five_campaign/five_to_five_pomegranate.png" style="width: 11%; margin-left:8%;"/>
                                </a>
                                <a href="/resources/5426/Vanilla-¡_Banana_Almond_Smoothie.png" class="fancy_box">
                                    <img src="/images/chp/five_for_five_campaign/five_to_five_almond.png" style="width: 16%; margin-left:8%;"/>
                                </a>
                            </div>
                        </div>

                        <img src="/resources/7253/Beacon_Smoothies_5weeks_footer.png"  style="width: 100%;"/>
                    </div>

                </div>
            </div>
        </div>

        <?php
    }


    public function executeSaveYourFruits($actions)
    {
        $user = $actions->getSessionUser();

        $form = new BeaconFiveForFiveCampaignCommitmentForm(array(), array('user' => $user));

        $weeks = array_keys(BeaconFiveForFiveCampaignCommitmentForm::getWeeks($user));
        $weekStartEndDates = BeaconFiveForFiveCampaignCommitmentForm::getWeekStartEndDates();

        if($form->isValidForRequest($actions->getRequest())) {
            $values = $form->getValues();
            $record = $user->getNewestDataRecord(BEACON_FIVE_FOR_FIVE_CAMPAIGN, true);

            if($record->exists()) {
                foreach($values as $field => $value) {
                    if(!empty($value)) {
                        foreach($weekStartEndDates as $week => $startEndDate) {
                            if(strpos($field, $week) !== false
                                && date('Y-m-d') <  $startEndDate['start_date']) {

                                $actions->getUser()->setErrorFlash('Sorry, you cannot enter Fruits&Vegetables for future weeks');
                                $actions->redirect('/compliance_programs/localAction?id=695&local_action=5for5_dashboard#record_your_food');
                            }
                        }

                        $record->$field = $value != '0' ? $value : '0';
                    }
                }

                $record->save();

                $points = BeaconFiveForFiveCampaignCommitmentForm::getPoints($record);

                foreach($weeks as $week){
                    if(isset($points[$week]['total_points'])) {
                        $totalPointsName = $week.'_total_points';
                        $record->$totalPointsName = $points[$week]['total_points'];
                    }
                }

                $record->total_points = $points['total_points'];

                $record->save();
            }

            $actions->getUser()->setNoticeFlash('Saved!');
            $actions->redirect('/compliance_programs/localAction?id=695&local_action=5for5_dashboard#record_your_food');

        }

        $actions->redirect('/compliance_programs/localAction?id=695&local_action=5for5_dashboard');
    }

    public function getLocalActions()
    {
        return array(
            '5for5_dashboard' => array($this, 'execute5For5Dashboard'),
            'save_your_fruits'  => array($this, 'executeSaveYourFruits'),
        );
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

class Beacon5For5CampaignProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {


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
                <th>Number of Points</th>
                <th>Action Links</th>
            </tr>
            <?php $this->printViewRow($status, 'week1_points', 1) ?>
            <?php $this->printViewRow($status, 'week2_points', 2) ?>
            <?php $this->printViewRow($status, 'week3_points', 3) ?>
            <?php $this->printViewRow($status, 'week4_points', 4) ?>
            <?php $this->printViewRow($status, 'week5_points', 5) ?>
            <tr>
                <th style="text-align:left">Total number of points</th>
                <td style="text-align:center"><?php echo number_format($status->getPoints()) ?></td>
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

class Beacon5For5CampaignTeamDashboardPrinter extends Beacon5For5CampaignBuddyAndTeamDashboardPrinter implements TeamDashboardPrinter
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

class Beacon5For5CampaignBuddyDashboardPrinter extends Beacon5For5CampaignBuddyAndTeamDashboardPrinter implements BuddyDashboardPrinter
{

    public function printReport(ComplianceProgramStatus $localStatus, ComplianceProgramStatus $userStatus)
    {
        $this->_printReport(array($localStatus, $userStatus), 'Buddy');
    }
}


abstract class Beacon5For5CampaignBuddyAndTeamDashboardPrinter
{
    protected function _printReport(array $statuses, $heading = 'User')
    {
        $totalPoints = 0;
        $totalWeeklyAvg = 0;
        $numStatuses = count($statuses);
        ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th><?php echo $heading ?></th>
                <th>Total Points</th>
                <th>Average Weekly Points</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($statuses as $status) : ?>
                <?php $avgWeekly = $status->getComplianceProgram()->getAverageWeeklySteps($status) ?>
                <?php $statusPoints = $status->getPoints(); ?>
                <tr>
                    <td><?php echo $status->getUser() ?></td>
                    <td><?php echo number_format($statusPoints) ?></td>
                    <td><?php echo number_format($avgWeekly) ?></td>
                </tr>
                <?php $totalWeeklyAvg += $avgWeekly ?>
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
                <th><?php echo $numStatuses ? number_format(round($totalWeeklyAvg / $numStatuses, 2)) : 0 ?></th>
            </tr>
            </tfoot>
        </table>
        <?php
    }
}


class BeaconFiveForFiveCampaignCommitmentForm extends BaseForm
{
    public function configure()
    {
        $user = $this->getOption('user');

        $record = $user->getNewestDataRecord(BEACON_FIVE_FOR_FIVE_CAMPAIGN, true);

        $default = array();
        if($record->exists()){
            foreach($record->getAllDataFields() as $field) {
                if(BEACON_FIVE_FOR_FIVE_CAMPAIGN_START_DATE <= date('Y-m-d', strtotime($field->getField('creation_date')))
                    && date('Y-m-d', strtotime($field->getField('creation_date'))) <= BEACON_FIVE_FOR_FIVE_CAMPAIGN_END_DATE) {
                    $default[$field->getName()] = $field->getValue() != '0' ? $field->getValue() : false;
                }
            }
        }

        $weeks = array_keys(BeaconFiveForFiveCampaignCommitmentForm::getWeeks($user));
        $days = array_keys(BeaconFiveForFiveCampaignCommitmentForm::getDays());
        $fruitTypes = BeaconFiveForFiveCampaignCommitmentForm::getFruitTypes();


        $widgets = array();
        $validators = array();

        $fruitChoices = BeaconFiveForFiveCampaignCommitmentForm::getFruitChoices();

        foreach($weeks as $week) {
            foreach($days as $day) {
                foreach($fruitTypes as $fruitType) {
                    $widgets["{$week}_{$day}_{$fruitType}"] = new sfWidgetFormChoice(array('choices' => $fruitChoices));
                    $validators["{$week}_{$day}_{$fruitType}"] = new sfValidatorChoice(array('choices' => array_keys($fruitChoices), 'required' => false));
                }
            }

            $widgets["{$week}_new_fruit"] = new sfWidgetFormInputText();
            $validators["{$week}_new_fruit"] = new sfValidatorString(array('required' => false));
        }

        $this->setWidgets($widgets);
        $this->setValidators($validators);

        $this->setDefaults($default);

        if(!empty($default['start_weight'])) {
            $this->addWidgetAttributes(array('start_weight' => array('readonly' => 'readonly')));
        }
    }

    public static function getPoints($record, $startDate = false, $endDate = false)
    {
        $user = $record->getUser();
        $weeks = array_keys(BeaconFiveForFiveCampaignCommitmentForm::getWeeks($user));
        $days = array_keys(BeaconFiveForFiveCampaignCommitmentForm::getDays());
        $fruitTypes = BeaconFiveForFiveCampaignCommitmentForm::getFruitTypes();
        $weekStartEndDates = BeaconFiveForFiveCampaignCommitmentForm::getWeekStartEndDates();

        $points = array(
            'total_points' => 0
        );

        $fields = $record->getAllDataFields();

        $dataFields = array();
        foreach($fields as $field) {
            if($startDate && $endDate && ($field->getField('creation_date') < $startDate && $field->getField('creation_date') > $endDate)) {
                continue;
            }
            $dataFields[$field->getName()] = $field->getValue();
        }

        foreach($weeks as $week) {
            $allFiveDays = true;
            $triedNewFruits = false;
            $points[$week]['bonus_points'] = 0;
            $points[$week]['tried_new_fruits'] = false;
            $points[$week]['completed_lessons'] = false;
            $points[$week]['completed_all_five'] = false;
            $points[$week]['total_points'] = 0;
            foreach($days as $day) {
                $allFiveServings = true;
                if(!isset($points[$week][$day])) $points[$week][$day] = 0;

                foreach($fruitTypes as $fruitType) {
                    $recordName = "{$week}_{$day}_{$fruitType}";
                    if(isset($dataFields[$recordName]) && $dataFields[$recordName] != '') {
                        $points[$week][$day] += 10;
                        $points[$week]['total_points'] += 10;
                    } else {
                        $allFiveDays = false;
                        $allFiveServings = false;
                    }
                }

                if($allFiveServings) {
                    $points[$week][$day] += 20;
                    $points[$week]['total_points'] += 20;
                }
            }

            $newFruitRecordName = "{$week}_new_fruit";
            if(isset($dataFields[$newFruitRecordName]) && $dataFields[$newFruitRecordName] != '') {
                $triedNewFruits = true;
            }

            if($allFiveDays) {
                $points[$week]['bonus_points'] += 50;
                $points[$week]['total_points'] += 50;
                $points[$week]['completed_all_five'] = true;
            }

            if($triedNewFruits) {
                $points[$week]['bonus_points'] += 50;
                $points[$week]['tried_new_fruits'] = true;
                $points[$week]['total_points'] += 50;
            }

            if(isset($weekStartEndDates[$week]['start_date']) && isset($weekStartEndDates[$week]['end_date'])) {
                $completedLessons = BeaconFiveForFiveCampaignCommitmentForm::getCompletedLessons($user, $weekStartEndDates[$week]['start_date'], $weekStartEndDates[$week]['end_date']);

                if(count($completedLessons) > 0) {
                    $points[$week]['bonus_points'] += 50;
                    $points[$week]['completed_lessons'] = true;
                    $points[$week]['total_points'] += 50;
                }
            }

            $points['total_points'] += $points[$week]['total_points'];
        }

        return $points;
    }

    public static function getCompletedLessons($user, $startDate, $endDate)
    {
        $client = $user->getClient();
        $alias = 'foods_nutrition';

        $lessonSets = ELearningCategorySet::getApplicableLessonSets($client, array($alias));
        $eligibleLessons = array_keys($lessonSets[$alias]);

        $completedLessons = ELearningLessonCompletion_v2::getAllCompletedLessons($user, $startDate, $endDate, true);

        $eligibleCompletedLessons = array();
        foreach($completedLessons as $completedLesson) {
            $lessonID = $completedLesson->getLesson()->getID();

            if(in_array($lessonID, $eligibleLessons)) {
                $eligibleCompletedLessons[] = $lessonID;
            }
        }

        return $eligibleCompletedLessons;
    }

    public static function getAllUsersPoints($actions)
    {
        $client = $actions->getUser()->getClient();

        $records = UserDataRecord::getRecordsForAllUsers(BEACON_FIVE_FOR_FIVE_CAMPAIGN, $client, true);

        $allUsersPoints = array();
        foreach($records as $record) {
            $userId = $record->getUser()->getId();

            $allUsersPoints[$userId] = $record->total_points;
        }

        return $allUsersPoints;
    }

    public static function getClientPoints($actions)
    {
        $user = $actions->getUser()->getUser();
        $client = $actions->getUser()->getClient();
        $weeks = array_keys(BeaconFiveForFiveCampaignCommitmentForm::getWeeks($user));
        $records = UserDataRecord::getRecordsForAllUsers(BEACON_FIVE_FOR_FIVE_CAMPAIGN, $client, true);

        $weeklyPoints = array();
        $totalPoints = 0;
        $companyLeader = array(
            'total_points'  => 0,
            'leader_name'   => null
        );

        foreach($records as $record) {
            foreach($weeks as $week) {
                if(!isset($weeklyPoints[$week])) {
                    $weeklyPoints[$week] = array(
                        'total_points'  => 0,
                        'max_points'    => 0
                    );
                }

                $totalPointsName = $week.'_total_points';
                if(isset($record->$totalPointsName) && $record->$totalPointsName != '') {
                    $weekPoints = $record->$totalPointsName;

                    $weeklyPoints[$week]['total_points'] += $weekPoints;
                    if($weekPoints > $weeklyPoints[$week]['max_points']) {
                        $weeklyPoints[$week]['max_points'] = $weekPoints;
                    }
                }
            }

            $totalPoints += $record->total_points;

            if($companyLeader['total_points'] < $record->total_points) {
                $companyLeader['total_points'] = $record->total_points;
                $companyLeader['leader_name'] = $record->getUser()->getFullName();
            }
        }


        foreach($weeklyPoints as $week => $points) {
            $weeklyPoints[$week]['average_points'] = round($points['total_points'] / count($records));
        }

        $weeklyPoints['total_points'] = $totalPoints;
        $weeklyPoints['leader_name'] = $companyLeader['leader_name'];

        return $weeklyPoints;
    }

    public static function getFruitTypes()
    {
        return array('blue', 'green', 'white', 'yellow', 'red');
    }

    public static function getDays()
    {
        return array(
            'day1' =>  'Day 1',
            'day2' =>  'Day 2',
            'day3' =>  'Day 3',
            'day4' =>  'Day 4',
            'day5' =>  'Day 5'
        );
    }

    public static function getWeeks(User $user)
    {
        return array(
            'week1' =>  'Week 1',
            'week2' =>  'Week 2',
            'week3' =>  'Week 3',
            'week4' =>  'Week 4',
            'week5' =>  'Week 5'
        );
    }

    public static function getWeekStartEndDates()
    {
        return array(
            'week1' =>  array(
                'start_date' => '2016-03-14',
                'end_date'   => '2016-03-20'
            ),
            'week2' =>  array(
                'start_date' => '2016-03-21',
                'end_date'   => '2016-03-27'
            ),
            'week3' =>  array(
                'start_date' => '2016-03-28',
                'end_date'   => '2016-04-03'
            ),
            'week4' =>  array(
                'start_date' => '2016-04-04',
                'end_date'   => '2016-04-10'
            ),
            'week5' =>  array(
                'start_date' => '2016-04-11',
                'end_date'   => '2016-04-15'
            )
        );
    }

    public static function getFruitChoices()
    {
        $fruitChoices =  array(
            'blackberries' => 'Blackberries',
            'blueberries' => 'Blueberries',
            'black_currants' => 'Black currants',
            'dried_plums' => 'Dried plums',
            'elderberries' => 'Elderberries',
            'purple_figs' => 'Purple figs',
            'purple_grapes' => 'Purple grapes',
            'plums' => 'Plums',
            'raisins' => 'Raisins',
            'purple_asparagus' => 'Purple asparagus',
            'purple_cabbage' => 'Purple cabbage',
            'purple_carrots' => 'Purple carrots',
            'eggplant' => 'Eggplant',
            'purple_belgian_endive' => 'Purple Belgian endive',
            'purple_peppers' => 'Purple peppers',
            'potatoes' => 'Potatoes (purple fleshed)',
            'black_salsify' => 'Black salsify',
            'avocados' => 'Avocados',
            'green_apples' => 'Green apples',
            'green_grapes' => 'Green grapes',
            'honeydew' => 'Honeydew',
            'kiwifruit' => 'Kiwifruit',
            'limes' => 'Limes',
            'green_pears' => 'Green pears',
            'artichokes' => 'Artichokes',
            'arugula' => 'Arugula',
            'asparagus' => 'Asparagus',
            'broccoflower' => 'Broccoflower',
            'broccoli' => 'Broccoli',
            'broccoli_rabe' => 'Broccoli rabe',
            'brussels_sprouts' => 'Brussels sprouts',
            'chinese_cabbage' => 'Chinese cabbage',
            'green_beans' => 'Green beans',
            'green_cabbage' => 'Green cabbage',
            'celery' => 'Celery',
            'chayote_squash' => 'Chayote squash',
            'cucumbers' => 'Cucumbers',
            'endive' => 'Endive',
            'leafy_greens' => 'Leafy greens',
            'leeks' => 'Leeks',
            'lettuce' => 'Lettuce',
            'green_onion' => 'Green onion',
            'okra' => 'Okra',
            'peas' => 'Peas',
            'green_pepper' => 'Green pepper',
            'sno_peas' => 'Sno Peas',
            'sugar_snap_peas' => 'Sugar snap peas',
            'spinach' => 'Spinach',
            'watercress' => 'Watercress',
            'zucchini' => 'Zucchini',
            'bananas' => 'Bananas',
            'brown_pears' => 'Brown pears',
            'dates' => 'Dates',
            'white_nectarines' => 'White nectarines',
            'white_peaches' => 'White peaches',
            'cauliflower' => 'Cauliflower',
            'garlic' => 'Garlic',
            'ginger' => 'Ginger',
            'jerusalem_artickoke' => 'Jerusalem artickoke',
            'jicama' => 'Jicama',
            'kohlrabi' => 'Kohlrabi',
            'mushrooms' => 'Mushrooms',
            'onions' => 'Onions',
            'parsnips' => 'Parsnips',
            'potatoes' => 'Potatoes (white fleshed)',
            'shallots' => 'Shallots',
            'turnips' => 'Turnips',
            'white_corn' => 'White Corn',
            'yellow apples' => 'Yellow apples',
            'apricots' => 'Apricots',
            'cantaloupe' => 'Cantaloupe',
            'cape_gooseberries' => 'Cape Gooseberries',
            'yellow_figs' => 'Yellow figs',
            'grapefruit' => 'Grapefruit',
            'golden_kiwifruit' => 'Golden kiwifruit',
            'lemon' => 'Lemon',
            'mangoes' => 'Mangoes',
            'nectarines' => 'Nectarines',
            'oranges' => 'Oranges',
            'papayas' => 'Papayas',
            'peaches' => 'Peaches',
            'yellow_pears' => 'Yellow pears',
            'persimmons' => 'Persimmons',
            'pineapples' => 'Pineapples',
            'tangerines' => 'Tangerines',
            'yellow_watermelon' => 'Yellow watermelon',
            'yellow_beets' => 'Yellow beets',
            'butternut_squash' => 'Butternut squash',
            'carrots' => 'Carrots',
            'yellow_peppers' => 'Yellow peppers',
            'yellow_potatoes' => 'Yellow potatoes',
            'pumpkin' => 'Pumpkin',
            'rutabagas' => 'Rutabagas',
            'yellow_summer_squash' => 'Yellow summer squash',
            'sweet_corn' => 'Sweet corn',
            'sweet_potatoes' => 'Sweet potatoes',
            'yellow_tomatoes' => 'Yellow tomatoes',
            'yellow_winter_squash' => 'Yellow winter squash',
            'red_apples' => 'Red apples',
            'blood_oranges' => 'Blood oranges',
            'cherries' => 'Cherries',
            'cranberries' => 'Cranberries',
            'red_grapes' => 'Red grapes',
            'pink_red_grapefruit' => 'Pink/Red grapefruit',
            'red_pears' => 'Red pears',
            'pomegranates' => 'Pomegranates',
            'raspberries' => 'Raspberries',
            'strawberries' => 'Strawberries',
            'watermelon' => 'Watermelon',
            'beets' => 'Beets',
            'red peppers' => 'Red peppers',
            'radishes' => 'Radishes',
            'radicchio' => 'Radicchio',
            'red_onions' => 'Red onions',
            'red_potatoes' => 'Red potatoes',
            'rhubarb' => 'Rhubarb',
            'tomatoes' => 'Tomatoes',
            'applesauce'    => 'Applesauce',
            'apples'        => 'Apples',
            'beans'         => 'Beans',
            'yellow_beans'  => 'Yellow beans',
            '100_percent_vegetable_juice'  => '100% Vegetable Juice'

        );

        ksort($fruitChoices);

        $priorizedChoices = array(
            ''                  => '',
            'fruit_smoothie'    => 'Fruit Smoothie',
            'green_smoothie'    => 'Green Smoothie',
            'salad_greens'      => 'Salad (Greens)',
            'salad_fruit'       => 'Salad (Fruit)',
            'vegetable_soup'    => 'Vegetable Soup',
            'fruit_juice'       => 'Fruit Juice',
            'vegetable_blend'   => 'Vegetable Blend'
        );

        $fruitChoices = array_merge($priorizedChoices, $fruitChoices);

        $fruitChoices = array_merge($fruitChoices, array('other' => 'Other'));

        return $fruitChoices;
    }
}