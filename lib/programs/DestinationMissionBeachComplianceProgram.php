<?php

use hpn\steel\query\SelectQuery;
require_once('/data/wms1/trunk/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/libwms3.php');

$_SESSION['redirect_dashboard_link'] = '/compliance_programs?id=1472';

class DestinationMissionBeachExerciseToSteps extends CompleteArbitraryActivityComplianceView
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

            if(!isset($minutesStepsData[$record->getDate('Y-m-d')])) $minutesStepsData[$record->getDate('Y-m-d')] = 0;

            $minutesStepsData[$record->getDate('Y-m-d')] += $minutesExercised * $activityConversion;
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

class DestinationMissionBeachFitbitComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $points)
    {
        $this->setDateRange($startDate, $endDate);
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
        return new ComplianceViewStatus($this, null, $this->points);
    }

    public $useWms2 = false;
    private $points = 0;
}

class DestinationMissionBeachAdminPrinter extends BasicComplianceProgramAdminReportPrinter
{
    protected function showUser(User $user)
    {
        if ($user->getClient()->getConfigurationParameter('enable_mission_beach_opt_in', true)) {
            $missionBeachDataType = $user->getClient()->getConfigurationParameter('enable_mission_beach_data_type', "mission_beach");

            $record = $user->getNewestDataRecord($missionBeachDataType);
            if($record->exists()) {
                return $record->optIn;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

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

class DestinationMissionBeachWMS2AdminPrinter extends BasicComplianceProgramAdminReportPrinter
{
    public function __construct($record)
    {
        $this->setShowUserFields(true, true, true);
        $this->setShowUserContactFields(null, null, true);

        $this->setShowComment(false, false, false);
        $this->setShowCompliant(false, false, false);
        $this->setShowPoints(false, false, false);
        $this->setShowEmailAddresses(true, true, false);

    }
}

class DestinationMissionBeachComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $record = $this->getComplianceProgramRecord();

        return new DestinationMissionBeachAdminPrinter($record);
    }

    public function getTeamDashboardPrinter()
    {
        return new DestinationMissionBeachTeamDashboardPrinter();
    }

    public function getBuddyDashboardPrinter()
    {
        return new DestinationMissionBeachBuddyDashboardPrinter();
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new DestinationMissionBeachProgramReportPrinter();
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

    function determineDevice($device, $selection, $devices) {
        if (in_array($device, $devices)) {
            if ($device == $selection) return "selected";
        } else {
            return "hidden";
        }
    }

    public function loadGroups()
    {
        global $_db;

        $_user = Piranha::getInstance()->getUser();

        $wms1ID = $_user->id;

        $wms2AccountID = $_user->wms2_account_id;

        $program_id = $_GET["id"];

        $domain = parent::getDomain();

        $url = $domain."wms3/public/report?bypass=true&method=download&program_id=".$program_id."&type=mission_beach&return=true";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        $points = 0;

        if (empty($result)) {
            $this->campaign_data = "";
        } else {
            $this->campaign_data = $result;

            $campaign_data = json_decode($this->campaign_data);

            $points = 0;

            foreach($campaign_data as $user) {
                if ($user->id == $wms1ID) {
                    $points = $user->total_steps;
                }
            }
        }

        $this->options = array(
            'allow_teams'                => true,
            'team_members_minimum'       => 2,
            'team_members_maximum'       => sfConfig::get('mission_beach_team_members_limit', 10),
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

        $fitbitView = new DestinationMissionBeachFitbitComplianceView($startDate, $endDate, $points);

        if ((isset($this->options['use_wms2']) && $this->options['use_wms2']) || sfConfig::get('app_wms2', false)) {

            $devices = array_column($_db->getResultsForQuery('select d.* from wms3.fitnessTracking_devices as d left join wms3.fitnessTracking_data as dt on dt.device_id = d.id left join wms3.fitnessTracking_participants as p on p.id = dt.participant where p.wms1Id = '.$wms1ID.' group by d.id;'), "id");

            $device_id = getDefaultDeviceSelected($wms1ID);

            if(empty($device_id)) {
                $synced_steps = getSyncedSteps($wms1ID);

                $device_id = empty($synced_steps) ? 1 : $synced_steps;
            } else {
                $device_id = $device_id[0]['device_id'];
            }

            if (!empty($devices)) {
                $lock = "unlocked";
                if (!empty($device)) $lock = "";

                $device_selection_html =
                    '<div class="device_selection_box '.$lock.'">
                    <p>You may only use one device for this campaign. Please select your device below:</p>
                    <div class="device_selection '.$this->determineDevice(1, $device_id, $devices).'">
                    <span class="bubble"><span class="fill"></span></span> Fitbit</div>
                    <div class="selection_confirmation '.$this->determineDevice(1, $device_id, $devices).'">
                        <div><i class="far fa-exclamation-triangle"></i> Are you sure you want to select this device? You cannot change your selected device.</div>
                        <div class="btn btn-primary" data-action="select" data-id="1">Yes</div>
                        <div class="btn btn-secondary red btn-danger" data-action="close">No</div>
                    </div>
                    <div class="device_selection '.$this->determineDevice(2, $device_id, $devices).'">
                        <span class="bubble"><span class="fill"></span></span> Nokia Health(Withings)</div>
                    <div class="selection_confirmation '.$this->determineDevice(2, $device_id, $devices).'">
                        <div><i class="far fa-exclamation-triangle"></i> Are you sure you want to select this device? You cannot change your selected device.</div>
                        <div class="btn btn-primary" data-action="select" data-id="2">Yes</div>
                        <div class="btn btn-secondary red btn-danger" data-action="close">No</div>
                    </div>
                    <div class="device_selection '.$this->determineDevice(3, $device_id, $devices).'" data-id="3">
                    <span class="bubble"><span class="fill"></span></span> Google Fit</div>
                    <div class="selection_confirmation '.$this->determineDevice(3, $device_id, $devices).'">
                        <div><i class="far fa-exclamation-triangle"></i> Are you sure you want to select this device? You cannot change your selected device.</div>
                        <div class="btn btn-primary" data-action="select" data-id="3">Yes</div>
                        <div class="btn btn-secondary red btn-danger" data-action="close">No</div>
                    </div>
                    <div class="device_selection '.$this->determineDevice(4, $device_id, $devices).'" data-id="4">
                    <span class="bubble"><span class="fill"></span></span> Apple Healthkit</div>
                    <div class="selection_confirmation '.$this->determineDevice(4, $device_id, $devices).'">
                        <div><i class="far fa-exclamation-triangle"></i> Are you sure you want to select this device? You cannot change your selected device.</div>
                        <div class="btn btn-primary" data-action="select" data-id="4">Yes</div>
                        <div class="btn btn-secondary red btn-danger" data-action="close">No</div>
                    </div>
                </div>
                <span style="font-size: 1.5rem; display: inline-block; margin: 1rem 0;">To sync to Apple Health or Google Fit, <a href="/resources/10560/2020_CircleWellness_App_Tracker_Syncing.pdf" target="_blank">follow these directions</a> on our Circle Well App.</span>
                <p>Steps from one device/app can be synced for the duration of this campaign. Please select the device/app that you wish to use. Please note: If you change devices in the middle of the campaign, use the <b>Log Steps Manually</b> function to log those steps for the remainder of the campaign.</p>';
            } else {
                $device_selection_html = "";
            }

            $fitbitView->useWms2 = false;
            $fitbitView->setReportName('Device Syncing (FitBit, Nokia) <br />'.$device_selection_html);
            $fitbitView->addLink(new Link('<p style="margin-top: 10px;">Give Permission to Sync</p>', '/compliance/chp-program/fitness-tracking-wms3/content/wms3fitbit?wms1Id='.$wms1ID.'&wms2AccountId='.$wms2AccountID.'&prefix=chp-program', false, '_blank'));
            $fitbitView->setName('fitbit');
        } else {
            $fitbitView->setReportName('Device Syncing (FitBit, Nokia)');
            $fitbitView->setName('fitbit');
            $fitbitView->addLink(new Link('Give Permission to Sync', '/standalone/demo/authorizeFitbitForCHPDemo'));
        }

        $operations->addComplianceView($fitbitView);

        if (sfConfig::get('guag_enable_minutesToSteps', true)) {
            $minutesToSteps = new DestinationMissionBeachExerciseToSteps($startDate, $endDate, 333, 0);
            $minutesToSteps->setName('minutes_steps');
            $minutesToSteps->setReportName('Convert Active/Exercise Minutes to Steps');
            $operations->addComplianceView($minutesToSteps);
        }

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

        global $_user, $_db;

        $record = $this->getComplianceProgramRecord();
        $program = $record->getComplianceProgram();

        $teamRecord = $record->getTeamByUserId($_user->id);
        $teamOwner = $teamRecord['owner_user_id'];

        $totalSteps = 0;

        $activeProgramRecord = ComplianceProgramRecordTable::getInstance()->find(1524);

        $teams = $activeProgramRecord->getTeams();

        $campaign_data = json_decode($program->campaign_data);

        $banner_logic = sfConfig::get('mission_beach_banner_logic', "team");

        if (!empty($campaign_data)) {
            foreach($campaign_data as $user) {
                if ($banner_logic == "team") {
                    if ($user->team_name == $teamRecord["name"]) {
                        $totalSteps = $user->team_total_steps;
                        break;
                    }
                } else {
                    $totalSteps += $user->total_steps;
                }
            }
        }

        $banners = [400000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_1_Portland_Head.jpg",
                    800000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_2_Plymouth_Rock.jpg",
                   1200000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_3_Statue_of_Liberty.jpg",
                   1600000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_4_Washington_Monument.jpg",
                   2000000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_5_Booker_T_Monument.jpg",
                   2400000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_6_Campbell_Bridge.jpg",
                   2800000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_7_Magnolia.jpg",
                   3200000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_8_Horton_House.jpg",
                   3600000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_9_Cascades_Park.jpg",
                   4000000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_10_Naval_Museum.jpg",
                   4400000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_11_French_Quarter.jpg",
                   4800000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_12_Sallier_Oak.jpg",
                   5200000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_13_Brazoria_Refuge.jpg",
                   5600000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_14_San_Antonio_River_Walk.jpg",
                   6000000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_15_Magnolia_Market.jpg",
                   6400000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_16_Fort_Phantom.jpg",
                   6800000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_17_Plainview_Point.jpg",
                   7200000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_18_UFO_Museum.jpg",
                   7600000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_19_Canyon_Road.jpg",
                   8000000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_20_Four_Corners.jpg",
                   8400000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_21_Humphreys_Peak.jpg",
                   8800000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_22_Grand_Canyon.jpg",
                   9200000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_23_Hoover_Dam.jpg",
                   9600000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_24_Route_66_Museum.jpg",
                  10000000 => "/images/chp/destination_mission_beach/Mission_Beach_Banner_25_Mission_Beach.jpg"];

        $banner_url = "/images/chp/destination_mission_beach/mission_beach_main_banner.jpg";
        foreach($banners as $threshold => $url) {
            if ($totalSteps >= $threshold) $banner_url = $url;
        }

        ?>

        <script>
            $(function(){
                $(".unlocked .device_selection").click(function(){
                    $(".device_selection").removeClass("selected");
                    $(".selection_confirmation").removeClass("show");
                    $(this).next().addClass("show");
                    $(this).addClass("selected");
                });
                $('[data-action="select"]').click(function(){
                    var id = $(this).attr("data-id");
                    $.ajax({
                        url: '/content/device_preference?device_id='+ id + '&campaign=mission_beach',
                        type: 'GET',
                        dataType: 'json',
                        complete: function() {
                            location.reload();
                        }
                    });
                });

                $('[data-action="close"]').click(function(){
                    $(this).parent().removeClass("show").prev().removeClass("selected");
                });
            });
        </script>

        <style>

            .device_selection_box {
                margin-bottom: 10px;
            }

            .device_selection_box p {
                margin-bottom: 5px;
                font-weight: 600;
            }

            .device_selection {
                display: flex;
                line-height: 1rem;
                align-items: center;
                margin-bottom: 5px;
                color: #bdbdbd;
            }

            .unlocked .device_selection {
                cursor: pointer;
                color: #666666;
            }

            .device_selection .bubble {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                border: 2px solid #bdbdbd;
                margin-right: 10px;
                transition: border .3s ease-in-out, background .3s ease-in-out;
            }

            .device_selection .bubble .fill {
                width: 10px;
                height: 10px;
                display: inline-block;
                border-radius: 50%;
                transition: background .3s ease-in-out;
            }

            .selection_confirmation {
                display: none;
                line-height: 4rem;
                font-weight: bold;
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e0e0e0;
                font-size: 1.5rem;
            }

            .selection_confirmation.show {
                display: block;
            }

            .selection_confirmation i {
                font-size: 2rem;
                margin-right: 10px;
                display: inline-block;
                color: #fdb83b;
            }

            .unlocked .device_selection:hover .bubble .fill {
                background-color: #e0e0e0;
                transition: background .1s ease-in-out;
            }

            .unlocked .device_selection.selected .bubble .fill,.device_selection.selected .bubble .fill {
                background-color: #2196F3;
                transition: background .1s ease-in-out;
            }

            .device_selection.selected {
                color: #222;
            }

            .device_selection.selected .bubble {
                border-color: #2196F3;
                transition: border .1s ease-in-out, background .1s ease-in-out;
            }

            #wms1 img {
                max-width: initial !important;
            }

            .banner {
                margin-bottom: 20px;
            }

            .nav-tabs-centered .nav-tabs {
                text-align: left;
            }

            .alert-info {
                background: #355167;
            }

            .nav-tabs > li > a, .nav-tabs > li > a:focus {
                box-shadow: inset 0 -3px 0 #CFD8DC;
                font-weight: 600;
                color: #90A4AE;
            }

            .nav-tabs > li > a:hover, .nav-tabs > li > a:active {
                box-shadow: inset 0 -3px 0 #90A4AE;
                color: #90A4AE;
            }

            .nav-tabs > li.active > a, .nav-tabs > li.active > a:focus {
                box-shadow: inset 0 -3px 0 #2196f3;
            }

            .nav-tabs-centered .nav-tabs > li {
                margin-right: 20px;
            }

            .responsive-panel-body .alert-error {
                display: none;
            }

            .responsive-panel-body .alert-success, .responsive-panel-body .alert-warning  {
                background: #355167;
            }

            .responsive-panel-body .alert-success em {
                font-style: normal;
                font-weight: 600;
            }

            .alert input.btn.btn-primary {
                color: #ffffff;
                background: #66BB6A;
                border-color: #66BB6A;
                margin-right: 10px;
            }

            .alert input.btn.btn-primary:active {
                color: #ffffff;
                background: #509753;
                border-color: #509753;
            }

            #tab-invite-women td, #tab-invite-men td {
                font-size: 16px;
                line-height: 38px;
            }

            .table-striped > tbody > tr:nth-of-type(odd) {
                background-color: #ECEFF1;
            }

            .table > tbody > tr > td {
                border-color: #CFD8DC;
            }

            .steps-label.label.label-info:not(.total-steps) {
                padding: 10px 15px;
                background: #355167;
                border-radius: 2px;
                display: inline-block;
                min-width: 200px;
                text-align: right;
            }

            #total-steps .steps-label.label.label-info {
                background: #0e76bc;
            }

            .accordion-heading {
                margin-bottom: 10px;
                background-color: #ECEFF1;
                line-height: 33px;
                padding-left: 10px;
            }

            #wms1 input[type="text"], #wms1 textarea {
                outline: none !important;
            }

            #wms1 input[type="text"]:focus {
                border-color: #0e76bc;
            }

            #wms1 input[type="text"] {
                padding-left: 5px;
            }

            #wms1 span.label:not(.steps-label) {
                padding: 5px 15px;
                height: 38px;
                display: inline-block;
                line-height: 30px;
                font-size: 16px;
            }

            #wms1 span.label-info {
                background: #355167;
            }

            #wms1 span.label-success {
                background: #ADA27B;
            }

            #members td {
                font-size: 16px;
                line-height: 38px;
            }

            button.btn {
                outline: none !important;
            }

            #search-submit, #save_team_name {
                background-color: #0e76bc;
                color: #fff;
                border: none;
            }

            #search-submit:hover, #save_team_name:hover {
                background-color: #0D5585;
            }

            input.search-query {
                margin-right: 20px;
            }

            form.form-search {
                display: flex;
            }

            .well {
                background: #ECEFF1;
            }

            @media(max-width: 800px) {
                #wms1 .banner img {
                    width: 100%;
                }

                #wms1 .mobile {
                    display: inline-block !important;
                }

                #wms1 .desktop {
                    display: none !important;
                }

                #wms1 .btn {
                    white-space: normal;
                }
            }
        </style>

        <div class="banner"><img src="<?= $banner_url ?>"></div>

        <p style="font-weight:bold">Your campaign runs <?php echo $this->getStartDate('m/d/Y') ?>  through <?php echo $this->getEndDate('m/d/Y') ?>.</p>
        <p>Travel virtually with your co-workers across America starting in Bar Harbor, Maine and ending in Mission Beach, California. Points of interest will be highlighted online as your company's total steps increase.
        </p>


        <?php if (loadRegister()): ?>

        <style type="text/css">
            #tab-choose-buddy, .total-steps { display:none; }
        </style>

        <script type="text/html" id="new-buddy-instructions">
            <h4>You haven't joined a team yet.</h4>
            <p>To create a new team, select "Create a New Team" above. If you
                wish to join an existing team, contact the captain of the team
                and ask them to invite you.</p>
        </script>

        <script type="text/javascript">
            $(function() {


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

                var text = $('#total-steps .steps-label').text().trim().replace("total steps (Company Wide)", "TOTAL STEPS");
                $('#total-steps .steps-label').text(text);
            });
        </script>

        <?php else: ?>

        <style type="text/css">

            #wms1 * {
                display: none;
            }

            #optinButton {
                width: 210px;
                margin: auto;
                display: block !important;
                color: #fff;
                background-color: #0275d8;
                border: none;
                font-weight: 400;
                padding: 5px 10px;
                border-radius: 3px;
                line-height: 1.5;
                transition: background .15s ease-in-out;
            }

            #optinButton:hover {
                background-color: #119aff;
            }

            #optinMessage {
                text-align: center;
                display: block !important;
            }

        </style>

        <script type="text/javascript">
            $(function() {

                $('#optinButton').click(function(){
                    $.ajax({
                        method: "GET",
                        url: "/wms1/content/mission-beach-opt-in",
                        dataType: "json",
                        complete: function(data){
                            response = $.parseJSON(data.responseText);
                            if (response.code == "1" || response.code == "2") {
                                location.reload();
                            }
                        }
                    });

                });
            });

        </script>

        <?php endif; ?>

        <?php

        return ob_get_clean();
    }

    public function getEmailContent(array $variables)
    {
        return array(
            'team_request' => array(
                'subject' => 'You have been sent a team invitation.',
                'body'    => <<<EOT
You have been invited by your coworker to participate in the Mission Beach campaign.

To accept the invitation, login to Circlewell.com and click on the Mission Beach button to start logging your activity!
EOT
            ),

            'buddy_request' => array(
                'subject' => 'You have been sent a buddy invitation.',
                'body'    => <<<EOT
You have received a buddy invitation to participate in the Mission Beach campaign.

To accept the invitation, login to Circlewell.com and click on the Mission Beach button to start logging your activity!
On your Mark...Get Set...Go!!
EOT
            )
        );
    }
}

function loadRegister()
{
    global $_user;

    if (sfConfig::get('enable_mission_beach_opt_in', true)) {
        $user_id = $_user->id;

        $u = new User();
        $user = $u->getUserById($user_id);

        $missionBeachDataType = $user->getClient()->getConfigurationParameter('enable_mission_beach_data_type', "mission_beach");

        $record = $user->getNewestDataRecord($missionBeachDataType);
        return $record->optIn;

    } else {
        return true;
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

class DestinationMissionBeachProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
//        if($status->getComplianceViewStatus('fitbit')->getAttribute('data_refreshed')) {
//            $status->getComplianceViewStatus('fitbit')->getComplianceView()->emptyLinks();
//            $status->getComplianceViewStatus('fitbit')->getComplianceView()->addLink(new Link('View Steps', '/standalone/demo/showFitbitStepsForCHPDemo'));
//        }
        $user = $status->getUser();
        $user_id = $user->id;

        $wms2_route = explode("/", $_SERVER["HTTP_HPN_WMS2_SUBDIRECTORY"]);

        $device_id = getDefaultDeviceSelected($user->id);

        if(empty($device_id)) {
            $synced_steps = getSyncedSteps($user->id);

            $device_id = empty($synced_steps) ? 1 : $synced_steps;
        } else {
            $device_id = $device_id[0]['device_id'];
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

            .tracking-log {
                color: #fff !important;
                background: #4696ec;
                padding: 5px 20px;
                margin-bottom: 20px;
                display: inline-block;
                text-align: center;
                border-radius: 2px;
                text-transform: uppercase;
                letter-spacing: 1px;
                text-decoration: none !important;
            }

            .tracking-log:hover {
                background: #2D7DD3;
            }

            .tracking-log i {
                margin-right: 5px;
            }

            .table td > a {
              color: #4696ec;
              border-radius: 0.4rem;
              padding: 1.25rem 2.25rem;
              border: 1px solid #4696ec;
              text-transform: uppercase;
              margin-right: 3rem;
              margin-bottom: 3rem;
              position: relative;
            }

            .table td > a:hover, .table td > a:active {
              text-decoration: none;
              color: white;
              background-color: #4696ec;
            }

            .table td > a+a:before {
              position: absolute;
              width: 1px;
              background-color: #b3b3b3;
              height: 80%;
              top: 10%;
              right: 108%;
            }

            @media only screen and (min-width: 570px) {
              .table td > a+a:before {
                content: '';
              }
            }

            @media only screen and (max-width: 580px) {
              .table td > a {
                display: inline-block;
                padding: 1rem 2rem;
              }
            }

            .nav-tabs {
              text-align: left !important;
            }
        </style>

        <?php if (!loadRegister()): ?>
            <button id="optinButton">I would like to participate in Destination Mission Beach.</button>
        <?php endif; ?>

        <h4 id="name-heading"><?php echo $status->getUser() ?></h4>

        <a class="tracking-log" href="/images/chp/destination_mission_beach/Mission_Beach_Activity_Tracking_Log.pdf" download="Mission Beach Activity Tracking Log.pdf"><i class="fal fa-file-download"></i> Activity Tracking Log</a>
        <br/>

        <table class="table table-condensed table-striped" id="dashboard">
            <tbody>
                <tr>
                    <th style="text-align:left"><?php echo $this->getGroupReportName($status, 'operations') ?></th>
                    <th>Number of steps</th>
                    <th>Action Links</th>
                </tr>
                <?php $this->printViewRow($status, 'fitbit', 1) ?>

                <tr>
                    <td>
                        <span class="view-number">2.</span> Log Steps Manually
                    </td>
                    <td class="center"></td>
                    <td class="center"><a href="/compliance/<?= $wms2_route[2]?>/fitness-tracking-wms3/compliance_programs?id=1156&forceRefresh=true&wms1Id=<?= $user_id ?>&device=<?=$device_id?>">Enter/Update Info</a></td>
                </tr>

                <?php if (sfConfig::get('mission_beach_enable_minutesToSteps', false)) $this->printViewRow($status, 'minutes_steps', 3) ?>
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

class DestinationMissionBeachTeamDashboardPrinter extends DestinationMissionBeachBuddyAndTeamDashboardPrinter implements TeamDashboardPrinter
{
    public function printReport($teamName, array $programStatuses)
    {
        ?>
        <div class="page-header">
            <h3><?php echo $teamName ?></h3>
        </div>
        <?php
        $this->_printReport($programStatuses, 'Team Member');
    }
}

class DestinationMissionBeachBuddyDashboardPrinter extends DestinationMissionBeachBuddyAndTeamDashboardPrinter implements BuddyDashboardPrinter
{

    public function printReport(ComplianceProgramStatus $localStatus, ComplianceProgramStatus $userStatus)
    {
        $this->_printReport(array($localStatus, $userStatus), 'Buddy');
    }
}


abstract class DestinationMissionBeachBuddyAndTeamDashboardPrinter
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
