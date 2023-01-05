<?php


use hpn\data\stream\ZipStream;
use hpn\steel\query\SelectQuery;
set_time_limit(0);
ini_set('memory_limit', '2048M');

class compliance_programsActions extends sfActions
{
    public function preExecute()
    {
        LegacyMode::enable();

        // While the compliance code doesn't explicitly use these, some of the functions
        // inside the legacy codebase do. :(
        $GLOBALS['_db'] = Database::getDatabase();
        $GLOBALS['_user'] = $this->getUser()->getUser();

        // The printer needs a few helpers and its implemented in the controller
        // layer so the standard helpers haven't been loaded yet.
        $this->getContext()->getConfiguration()
            ->loadHelpers(sfConfig::get('sf_standard_helpers', array()), $this->getModuleName());
    }

    public function postExecute()
    {
        // ? calling legacymode disable messes things up
        // LegacyMode::disable();
    }

    /**
     * Needed so that activity tracker can point to this URL and work
     * for multiple CHP clients that all use the walking campaign program.
     */
    public function executeRedirectToChpDashboard(sfRequest $request)
    {
        $record = ComplianceProgramRecordTable::getInstance()
            ->findActiveCHPWalkingCampaignComplianceProgramRecord($this->getSessionClient());

        $recordIdMapper = $this->getCHPWalkingProgramRecordIdMapper();

        $destinationMissionBeach = sfConfig::get('mission_beach_redirect', false);

        if ($destinationMissionBeach){
            $this->redirect('/compliance_programs?id=1472');
        } elseif (isset($recordIdMapper[$this->getSessionClient()->id])) {
            $this->redirect('/compliance_programs?id=' . $recordIdMapper[$this->getSessionClient()->id]);
        } elseif($record) {
            $this->redirect('/compliance_programs?id=' . $record->id);
        } else {
            $this->forward404();
        }
    }

    public function executeRedirectToBeaconDashboard(sfRequest $request)
    {
        $record = ComplianceProgramRecordTable::getInstance()
            ->findActiveBeaconWalkingCampaignComplianceProgramRecord($this->getSessionClient());

        if($record) {
            $this->redirect('/compliance_programs?id=' . $record->id);
        } else {
            $this->forward404();
        }
    }

    public function executeRedirectToCMCSDashboard(sfRequest $request)
    {
        $record = ComplianceProgramRecordTable::getInstance()
            ->findActiveCMCSWalkingCampaignComplianceProgramRecord($this->getSessionClient());

        if($record) {
            $this->redirect('/compliance_programs?id=' . $record->id);
        } else {
            $this->forward404();
        }
    }

    public function executeNewTeam(sfRequest $request)
    {
        $record = $this->getComplianceProgramRecord($request);

        $this->program = $record->getComplianceProgram();

        $this->team_leaderboard = $this->program->getOption('team_leaderboard');

        $this->can_create_new_team = $this->program->getOptionCanCreateNewTeam();

        if(!$this->program->getOptionCanManageTeamsAndBuddies()) {
            $this->forward404();
        }

        if(!$this->program->getOptionCanCreateNewTeam()) {
            $this->forward404();
        }

        $this->record_id = $record->id;

        $this->program_name = $record->description;

        if(!$this->program->getOption('allow_teams')) {
            $this->forward404();
        }

        $this->form = new NewComplianceTeamForm();

        if($this->form->isValidForRequest($request)) {

            if ($this->isDuplicateTeam($this->record_id, $this->form->getValue('name'))) {
                $this->redirect("compliance_programs/newTeam?id={$this->record_id}&duplicate={$this->form->getValue('name')}");
            } else {
                $record->createTeam($this->form->getValue('name'), $this->getSessionUser()->id);

                $this->redirect("compliance_programs/manageTeam?id={$this->record_id}");
            }
        }
    }

    private function setupTeam(sfRequest $request, $forManaging = true, $acceptedUserOnly = null)
    {
        $sessionUserId = $this->getSessionUser()->id;

        $this->record = $this->getComplianceProgramRecord($request);

        $this->program = $this->record->getComplianceProgram();

        if(!$this->program->getOption('allow_teams')) {
            $this->forward404();
        }

        if($acceptedUserOnly === null) {
            $acceptedUserOnly = $this->program->getOption('team_members_maximum_for_accepted_users');
        }

        $this->team = $this->record->getTeamByUserId($sessionUserId, $acceptedUserOnly);

        if(!$this->team) {
            $this->forward404();
        } elseif($forManaging && $this->team['owner_user_id'] != $sessionUserId) {
            $this->forward401();
        }

        $this->team_members_minimum = $this->program->getOption('team_members_minimum');
        $this->team_members_maximum = $this->program->getOption('team_members_maximum');
        $this->team_members_invite_end_date = $this->program->getOption('team_members_invite_end_date');

        $this->alert_not_enough_members = $this->team_members_minimum > count($this->team['users']);

        if($this->team_members_invite_end_date
            && date('Y-m-d H:i:s', strtotime($this->team_members_invite_end_date)) < date('Y-m-d H:i:s')) {
            $this->alert_maximum_members = true;
        } else {
            $this->alert_maximum_members = $this->team_members_maximum <= count($this->team['users']);
        }

        $numberAccepted = 0;

        foreach($this->team['users'] as $user) {
            if($user['accepted']) {
                $numberAccepted++;
            }
        }

        $this->alert_not_enough_members_accepted = $numberAccepted < $this->team_members_minimum;
    }

    public function executeInvitationRespond(sfRequest $request)
    {
        $this->setupTeam($request, false, false);

        $userId = $this->getSessionUser()->id;
        $teamId = $this->team['id'];

        if($request->getParameter('_decline')) {
            $this->record->removeUserFromTeam($teamId, $userId);

            $this->getUser()->setNoticeFlash('You declined the invitation.');
        } elseif($request->getParameter('_accept')) {
            $this->record->acceptUserForTeam($teamId, $userId);

            $this->getUser()->setNoticeFlash('You accepted the invitation.');
        }

        $this->redirect("compliance_programs/index?id={$this->record->id}");
    }

    public function executeJoinTeam(sfRequest $request)
    {
        $this->record = $this->getComplianceProgramRecord($request);
        $this->record_id = $this->record->id;

        $this->program = $this->record->getComplianceProgram();

        if(!$this->program->getOptionCanManageTeamsAndBuddies()) {
            $this->forward404();
        }

        $this->can_create_new_team = $this->program->getOptionCanCreateNewTeam();

        $this->team_leaderboard = $this->program->getOption('team_leaderboard');
    }

    public function executeManageTeam(sfRequest $request)
    {
        $sessionUserId = $this->getSessionUser()->id;

        $this->setupTeam($request);

        $this->team_leaderboard = $this->program->getOption('team_leaderboard');

        $this->record_id = $this->record->id;

        $this->program_name = $this->record->description;

        $this->last_name = $request->getParameter('last_name');

        $numberOfMales = 0;
        $numberOfFemales = 0;

        $this->team = $this->record->getTeamByUserId($sessionUserId, false);

        foreach($this->team['users'] as $user) {
            if($user['gender'] == Gender::MALE) {
                $numberOfMales++;
            } elseif($user['gender'] == Gender::FEMALE) {
                $numberOfFemales++;
            }
        }

        $this->remove_males = ($maxMales = $this->program->getOption('team_members_maximum_males', null)) !== null && $numberOfMales >= $maxMales;
        $this->remove_females = ($maxFemales = $this->program->getOption('team_members_maximum_females', null)) !== null && $numberOfFemales >= $maxFemales;

        if($this->last_name) {
            $this->males = array();
            $this->females = array();

            $users = $this->searchForUsers($this->record, $this->last_name);

            foreach($users as $key => $user) {
                if($user['gender'] == Gender::MALE && !$this->remove_males) {
                    $this->males[$key] = $user;
                } elseif($user['gender'] == Gender::FEMALE && !$this->remove_females) {
                    $this->females[$key] = $user;
                }
            }
        }
    }

    public function executeChooseBuddy(sfRequest $request)
    {
        $this->record = $this->getComplianceProgramRecord($request);

        $this->record_id = $this->record->id;

        $this->program = $this->record->getComplianceProgram();

        $this->team_leaderboard = $this->program->getOption('team_leaderboard');

        if(!$this->program->getOptionCanManageTeamsAndBuddies()) {
            $this->forward404();
        }

        $this->last_name = $request->getParameter('last_name');

        if($this->last_name) {
            $this->users = $this->searchForUsers($this->record, $this->last_name);
        }

        $this->can_create_new_team = $this->program->getOptionCanCreateNewTeam();
    }

    public function executeInviteBuddy(sfRequest $request)
    {
        if(!($userId = $request->getPostParameter('user_id'))) {
            $this->forward404();
        }

        $sessionUser = $this->getSessionUser();

        $buddyUser = UserTable::getInstance()->find($userId);

        $this->record = $this->getComplianceProgramRecord($request);

        if(!$buddyUser) {
            $this->forward404();
        } elseif($this->userHasAssociation($this->record, $userId)) {
            $this->forward404();
        } elseif($this->userHasAssociation($this->record, $sessionUser->id)) {
            $this->forward404();
        }

        $this->record->createBuddyInvitation($sessionUser->id, $userId);

        $this->sendEmail($this->record->getComplianceProgram(), $buddyUser, 'buddy_request');

        $this->getUser()->setNoticeFlash('Your buddy request was submitted. Once verified by your buddy, your accounts will be linked.');

        $this->redirect("compliance_programs/index?id={$this->record->id}");
    }

    public function executeTeamLeaderboard(sfWebRequest $request)
    {
        $this->record = $this->getComplianceProgramRecord($request);

        $this->loadIndexProgramAndUser($request);

        $this->program = $this->compliance_program;

        $this->points_label = $this->compliance_program->getOption('points_label');
        $this->total_steps = $this->compliance_program->getOption('total_steps');

        if(!$this->compliance_program->getOption('team_leaderboard')) {
            $this->forward404();
        }

        $teams = $this->record->getTeams();


        $this->can_manage_teams_buddies = $this->compliance_program->getOptionCanManageTeamsAndBuddies();

        $this->can_create_new_team = $this->compliance_program->getOptionCanCreateNewTeam();

        $this->loadIndexTabVariables($request);

        $this->teams = array();

        $this->total_points = 0;

        if($teamData = $this->compliance_program->getTeamData($this)) {
            $this->teams = $teamData;
            foreach($this->teams as $team) {
                $this->total_points += $team['points'];
            }
        } else {
            foreach($teams as $team) {
                $numberOfUsersWithPoints = 0;

                $teamData = array('points' => 0, 'users' => array());

                foreach($team['users'] as $userId => $userData) {
                    if($userData['accepted']) {
                        $user = UserTable::getInstance()->find($userId);

                        $this->compliance_program->setActiveUser($user);

                        $status = $this->compliance_program->getStatus();

                        $teamData['users'][] = array(
                            'points' => $status->getPoints(),
                            'name' => (string)$status->getUser()
                        );

                        $totalPoints = $status->getPoints();

                        $teamData['points'] += $totalPoints;

                        $this->total_points += $totalPoints;

                        if($status->getPoints() > 0) {
                            $numberOfUsersWithPoints++;
                        }
                    }
                }

                $teamData['average_points'] = count($team['users']) < 1 ?
                    0 : round($teamData['points'] / count($team['users']), 2);

                uasort($teamData['users'], function($a, $b) {
                    return $b['points'] - $a['points'];
                });

                $this->teams[$team['name']] = $teamData;
            }
        }

        uasort($this->teams, function($a, $b) {
            return $b['average_points'] - $a['average_points'];
        });
    }

    public function executeTeamLeaderboardWMS3(sfWebRequest $request)
    {
        $this->record = $this->getComplianceProgramRecord($request);

        $this->loadIndexProgramAndUser($request);

        $this->program = $this->compliance_program;

        $this->points_label = $this->compliance_program->getOption('points_label');
        $this->total_steps = $this->compliance_program->getOption('total_steps');

        if(!$this->compliance_program->getOption('team_leaderboard')) {
            $this->forward404();
        }

        $teams = $this->record->getTeams();

        $this->can_manage_teams_buddies = $this->compliance_program->getOptionCanManageTeamsAndBuddies();

        $this->can_create_new_team = $this->compliance_program->getOptionCanCreateNewTeam();

        $this->loadIndexTabVariables($request);

        $teams = [];

        $this->total_points = 0;

        $campaign_data = json_decode($this->program->campaign_data);

        if (isset($campaign_data)) {
            foreach ($campaign_data as $index => $user) {
                if ($user->team_name != "N/A" && $user->team_name != "Team Name") {
                    if (!isset($teams[$user->team_name]))  $teams[$user->team_name] = [];

                    $teams[$user->team_name]["points"] = $user->team_total_steps;
                    $teams[$user->team_name]["average_points"] = $user->team_average_steps;
                    $teams[$user->team_name]["users"][$user->id]["name"] = $user->first_name . " " . $user->last_name;
                    $teams[$user->team_name]["users"][$user->id]["points"] = $user->total_steps;
                    if ($user->team_name != "Team Name") $this->total_points += $user->total_steps;
                }
            }
        }

        uasort($teams, function($a, $b) {
            return $b['average_points'] - $a['average_points'];
        });

        $this->teams = $teams;
    }

    public function executeTeamRemoveUser(sfWebRequest $request)
    {
        if(!($userId = $request->getPostParameter('user_id'))) {
            $this->forward404();
        }

        $this->setupTeam($request);

        $user = $this->searchForUsersQuery($this->record, false)
            ->andWhere('u.id = ?', array($userId))
            ->hydrateSingleRow()
            ->execute();

        if(!$user) {
            $this->forward404();
        }

        $this->record->removeUserFromTeam($this->team['id'], $user['id']);

        $this->getUser()->setNoticeFlash("{$user['first_name']} {$user['last_name']} was removed from the team.");

        $this->redirect("compliance_programs/manageTeam?id={$this->record->id}");
    }

    public function executeShowBuddyDashboard(sfWebRequest $request)
    {
        $sessionUser = $this->getSessionUser();

        $this->record = $this->getComplianceProgramRecord($request);

        $buddyRecord = $this->record->getBuddy($sessionUser->id);

        if(!$buddyRecord || !$buddyRecord['accepted']) {
            $this->forward404();
        }

        $this->program = $this->record->getComplianceProgram();

        $this->team_leaderboard = $this->program->getOption('team_leaderboard');

        $this->program->setActiveUser($sessionUser);

        $localUserStatus = $this->program->getStatus();

        $this->program->setActiveUser(UserTable::getInstance()->find($buddyRecord['buddy_user_id']));

        $buddyUserStatus = $this->program->getStatus();

        $this->program->setActiveUser(null);

        ob_start();

        $this->program->getBuddyDashboardPrinter()->printReport($localUserStatus, $buddyUserStatus);

        $this->setVar('rendering', ob_get_clean(), true);
    }

    public function executeShowTeamDashboard(sfWebRequest $request)
    {
        $sessionUser = $this->getSessionUser();

        $this->record = $this->getComplianceProgramRecord($request);

        $team = $this->record->getTeamByUserId($sessionUser->id);

        if(!$team) {
            $this->forward404();
        }

        $this->is_owner_user = $sessionUser->id == $team['owner_user_id'];

        $this->program = $this->record->getComplianceProgram();

        $this->team_leaderboard = $this->program->getOption('team_leaderboard');

        $this->can_manage_teams_buddies = $this->program->getOptionCanManageTeamsAndBuddies();

        $statuses = array();

        foreach($team['users'] as $userId => $userData) {
            if($userData['accepted']) {
                $user = UserTable::getInstance()->find($userId);

                $this->program->setActiveUser($user);

                $statuses[] = $this->program->getStatus();
            }
        }

        $this->program->setActiveUser(null);

        $this->setupTeam($request, false);

        ob_start();

        $this->program->getTeamDashboardPrinter()->printReport($team['name'], $statuses);

        $this->setVar('rendering', ob_get_clean(), true);
    }


    public function executeShowTeamDashboardWMS3(sfWebRequest $request)
    {
        $sessionUser = $this->getSessionUser();

        $this->record = $this->getComplianceProgramRecord($request);

        $team = $this->record->getTeamByUserId($sessionUser->id);

        if(!$team) {
            $this->forward404();
        }

        $this->is_owner_user = $sessionUser->id == $team['owner_user_id'];

        $this->team_name = $team['name'];

        $this->program = $this->record->getComplianceProgram();

        $this->team_leaderboard = $this->program->getOption('team_leaderboard');

        $this->alert_not_enough_members = false;

        $this->can_manage_teams_buddies = $this->program->getOptionCanManageTeamsAndBuddies();

        $campaign_data = json_decode($this->program->campaign_data);
        $users = [];

        $this->team_grand_total = 0;
        $this->team_average_total = 0;
        $this->team_average_daily = 0;

        $team_members = 0;

        if (isset($campaign_data)) {
            foreach ($campaign_data as $index => $user) {
                if ($index != 0 && $user->team_name == $team["name"] ) {
                    $users[$user->id] = $user;
                    $this->team_grand_total += $user->total_steps;
                    $this->team_average_total += $user->total_steps;
                    $this->team_average_daily += $user->daily_steps_average;
                    $team_members++;
                }
            }
            if ($team_members == 0) $team_members = 1;
            $this->team_average_total = $this->team_average_total / $team_members;
            $this->team_average_daily = $this->team_average_daily / $team_members;
            $this->campaign_data = $users;
        } else {
            $this->campaign_data = [];
        }

    }

    public function executeBuddyInvitationCancel(sfWebRequest $request)
    {
        $this->record = $this->getComplianceProgramRecord($request);

        $this->record->deleteBuddyInvitation($this->getSessionUser()->id, false);

        $this->getUser()->setNoticeFlash('Your buddy request was cancelled.');

        $this->redirect("compliance_programs/index?id={$this->record->id}");
    }

    public function executeBuddyRemove(sfWebRequest $request)
    {
        $this->record = $this->getComplianceProgramRecord($request);

        if ($this->canDeleteBuddy($this->record)) {
            $userIdRemoved = $this->record->deleteBuddyRecord($this->getSessionUser()->id);

            $this->getUser()->setNoticeFlash('Your buddy was removed.');

            if ($userRemoved = UserTable::getInstance()->find($userIdRemoved)) {
                $this->sendEmail($this->record->getComplianceProgram(), $userRemoved, 'buddy_removed');

                if ($this->record->getComplianceProgram()->getOption('force_spouse_with_employee') &&
                    $userRemoved->relationship_type != Relationship::EMPLOYEE) {
                    $this->record->deleteRegistrationRecord($userIdRemoved);
                }
            }
        }

        $this->redirect("compliance_programs/index?id={$this->record->id}");
    }

    public function executeBuddyInvitationRespond(sfWebRequest $request)
    {
       $this->record = $this->getComplianceProgramRecord($request);

        if($request->getParameter('_decline')) {
            $this->record->deleteBuddyInvitation($this->getSessionUser()->id, true);

            $this->getUser()->setNoticeFlash('You declined the invitation.');
        } elseif($request->getParameter('_accept')) {
            $this->record->acceptBuddyInvitation($this->getSessionUser()->id);

            $this->getUser()->setNoticeFlash('You accepted the invitation.');
        }

        $this->redirect("compliance_programs/index?id={$this->record->id}");
    }

    public function executeTeamDelete(sfWebRequest $request)
    {
        $this->setupTeam($request);

        if($this->team && count($this->team['users']) <= 1) {
            $this->record->deleteTeam($this->team['id']);

            $this->getUser()->setNoticeFlash('The team was deleted.');
        }

        $this->redirect("compliance_programs/index?id={$this->record->id}");
    }

    public function executeTeamEditName(sfWebRequest $request)
    {
        $this->setupTeam($request);

        $teamName = $request->getPostParameter('new_team_name');

        if($this->team && $teamName) {
            $this->record->updateTeam($this->team['id'], $teamName);

            $this->getUser()->setNoticeFlash('The team name was saved.');
        }

        $this->redirect("compliance_programs/manageTeam?id={$this->record->id}");
    }

    public function executeTeamInviteUser(sfWebRequest $request)
    {
        if(!($userId = $request->getPostParameter('user_id'))) {
            $this->forward404();
        }

        $this->setupTeam($request);

        $user = $this->searchForUsersQuery($this->record)
            ->andWhere('u.id = ?', array($userId))
            ->hydrateSingleRow()
            ->execute();

        $userObject = UserTable::getInstance()->find($userId);

        if(!$user || !$userObject) {
            $this->forward404();
        }

        $this->record->addUserToTeam($this->team['id'], $user['id'], false);

        $this->getUser()->setNoticeFlash("{$user['first_name']} {$user['last_name']} was invited to the team.");

        $this->sendEmail($this->record->getComplianceProgram(), $userObject, 'team_request');

        $this->redirect("compliance_programs/manageTeam?id={$this->record->id}");
    }

    public function executeRegister(sfRequest $request)
    {
        $sessionUser = $this->getSessionUser();

        $sessionUser->id = $_GET['wms2']['id'] ?? $sessionUser->id;

        $this->record = $this->getComplianceProgramRecord($request);

        $this->program = $this->record->getComplianceProgram();

        $isServiceRequest = $request->isServiceRequest();

        $registrationStartDate = $this->program->getOption('registration_start_date');

        $registrationEndDate = $this->program->getOption('registration_end_date');

        $registrationCloseRedirect = $this->program->getOption('registration_close_redirect');

        if(!isset($_GET['wms2']['register']) && !isset($_GET['admin']) && $registrationCloseRedirect && $registrationEndDate && time() > strtotime($registrationEndDate)) {
            $this->redirect($registrationCloseRedirect);
        } elseif(!$isServiceRequest && $registrationEndDate && time() > strtotime($registrationEndDate)) {
            $this->getUser()->setNoticeFlash($this->program->getOption('registration_close_message', 'Registration has closed.'));

            $this->redirect($this->generateUrl('homepage'));
        } elseif(!$isServiceRequest && $registrationStartDate && time() < strtotime($registrationStartDate)) {
            $this->getUser()->setNoticeFlash($this->program->getOption('registration_open_message', 'Registration has not opened yet.'));

            $this->redirect($this->generateUrl('homepage'));
        } else {
            $this->form = $this->program->getRegistrationForm();

            if($this->form->isValidForRequest($request)) {
                $errorCode = $this->record->createRegistrationRecord($sessionUser->id, $this->form->getValues());

                if ($errorCode == ComplianceProgramRecord::ERROR_NONE) {
                    $redirect = $this->program->getOption(
                        'registration_redirect',
                        "compliance_programs/index?id={$this->record->id}"
                    );

                    $this->getUser()->setNoticeFlash('You are now registered.');

                    $this->redirect($redirect);
                } else {
                    if ($errorCode == ComplianceProgramRecord::ERROR_EMPLOYEE_FULL) {
                        $this->getUser()->setErrorFlash('We cannot register you: Your spouse is already part of a full team or has a buddy. The employee must remove their current buddy or be removed from a team in order for you to register.', false);
                    } else if ($errorCode == ComplianceProgramRecord::ERROR_EMPLOYEE_NOT_REGISTERED) {
                        $this->getUser()->setErrorFlash('Your spouse must register first before we can register you for the program.', false);
                    } else {
                        $this->getUser()->setErrorFlash('An unknown registration error occurred. If this persists, please contact technical support.', false);
                    }

                    ob_start();

                    $this->program->getRegistrationFormPrinter()->printForm($this->form, "/compliance_programs/register?id={$this->record->id}", $sessionUser);

                    $this->setVar('rendering', ob_get_clean(), true);
                }
            } else {
                ob_start();

                $this->program->getRegistrationFormPrinter()->printForm($this->form, "/compliance_programs/register?id={$this->record->id}", $sessionUser);

                $this->setVar('rendering', ob_get_clean(), true);
            }
        }
    }

    public function executeLocalAction(sfRequest $request)
    {
        $this->record = $this->getComplianceProgramRecord($request);

        $actionName = $request->getParameter('local_action');

        $program = $this->record->getComplianceProgram();

        $localActions = $program->getLocalActions();

        if(isset($localActions[$actionName])) {
            ob_start();

            call_user_func($localActions[$actionName], $this);

            $this->setVar('rendering', ob_get_clean(), true);
        } else {
            $this->forward404();
        }
    }

    /**
     * Shows the active compliance program for the user.
     */
    public function executeIndex(sfRequest $request)
    {
        if($request->getParameter('_layout')) {
            $this->setLayout('minimal');
        }

        $this->record = $this->getComplianceProgramRecord($request);

        $this->loadIndexProgramAndUser($request);

        $this->can_manage_teams_buddies = $this->compliance_program->getOptionCanManageTeamsAndBuddies();

        $this->can_create_new_team = $this->compliance_program->getOptionCanCreateNewTeam();

        $this->team_leaderboard = $this->compliance_program->getOption('team_leaderboard');

        if($this->compliance_program->getOption('require_registration')) {
            if(!$this->record->getRegistrationRecord($this->user->id)) {
                $this->redirect("compliance_programs/register?id={$this->record->id}");
            }
        }

        $this->compliance_program->setActiveUser($this->user);

        $this->dispatcher->notify(new sfEvent($this->compliance_program, 'compliance_programs.view', array('user' => $this->user)));

        $GLOBALS['_user'] = $this->user;
        Piranha::getInstance()->setUser($this->user, false);

        $this->allow_teams = $this->compliance_program->getOption('allow_teams');

        if($this->allow_teams) {
            $this->loadIndexTabVariables($request);
        }
    }

    protected function loadIndexProgramAndUser($request)
    {
        $userQuery = UserTable::getInstance()->createQuery('u')
            ->where('u.id = ?', $this->getSessionUser()->id);

        $this->compliance_program = $this->getComplianceProgram($this->record, ComplianceProgram::MODE_INDIVIDUAL);
        $this->compliance_program->preQuery($userQuery);

        $this->collection = $userQuery->execute();
        $this->user = $this->collection->getFirst();

        if(!$this->user) {
            // This can happen for programs that override preQuery to only target
            // a subset of a population for the program.
            $this->compliance_program->handleInvalidUser($this);
        }
    }

    protected function canDeleteBuddy($record)
    {
        $program = $record->getComplianceProgram();

        $sessionUser = $this->getSessionUser();

        return (!$program->getOption('force_spouse_with_employee') || $sessionUser->relationship_type == Relationship::EMPLOYEE) &&
                $program->getOption('allow_team_buddy_removal') &&
                $program->getOptionCanManageTeamsAndBuddies();
    }

    protected function loadIndexTabVariables($request)
    {
        $this->team = $this->record->getTeamByUserId($this->user->id, false);

        $this->has_buddy_request = false;
        $this->sent_buddy_request = false;
        $this->has_buddy = false;
        $this->buddy_user = false;

        if($buddyRecord = $this->record->getBuddy($this->user->id)) {
            $this->buddy_user = UserTable::getInstance()->find($buddyRecord['buddy_user_id']);

            if($buddyRecord['accepted']) {
                $this->has_buddy = true;
                $this->allow_buddy_removal = $this->canDeleteBuddy($this->record);
            } elseif($buddyRecord['sent_request']) {
                $this->sent_buddy_request = true;
            } else {
                $this->has_buddy_request = true;
            }
        }

        $this->owner_user = $this->team ? UserTable::getInstance()->find($this->team['owner_user_id']) : false;

        $this->is_owner_user = $this->owner_user && $this->user->id == $this->owner_user->id;

        $this->has_team_invite = false;
        if($this->team && isset($this->team['users'])) {
            $acceptedMembers = 0;
            foreach ($this->team['users'] as $u) {
                if($u['accepted']) $acceptedMembers++;
            }

            $record = $this->getComplianceProgramRecord($request);

            $this->program = $record->getComplianceProgram();

            $this->team_members_maximum = $this->program->getOption('team_members_maximum');

            if(isset($this->team['users'][$this->user->id]['accepted']) && !$this->team['users'][$this->user->id]['accepted'] && $acceptedMembers < $this->team_members_maximum) {
                $this->has_team_invite = true;
            }
        }


        $this->has_team = $this->team && isset($this->team['users'][$this->user->id]['accepted']) && $this->team['users'][$this->user->id]['accepted'];

        if($this->has_team) {
            $this->setupTeam($request, false);
        }
    }

    public function executeFindComplianceProgram(sfRequest $request)
    {
        $this->form = new FindComplianceProgramForm();

        if($this->form->isValidForRequest($request)) {
            $programRecord = $this->form->getValue('id');
        }
    }

    public function executeEditComplianceProgramExceptions(sfRequest $request)
    {
        $this->forward404Unless(($this->user = UserTable::getInstance()
            ->find($request->getParameter('user_id'))) && $this->getSessionUser()->canViewUser($this->user));

        $recordTable = ComplianceProgramRecordTable::getInstance();

        $this->program_record = $request->getParameter('id') ?
            $recordTable->find($request->getParameter('id')) :
            $recordTable->findApplicableActive($this->getSessionClient());

        $this->sessionUser = $this->getSessionUser();

        if(!$this->program_record) {
            $this->forward404();
        } else if(!$this->getUser()->getAcl()->isGranted('clients', $this->program_record->client->uuid, 'read')) {
            $this->forward401();
        }

        $program = $this->getComplianceProgram($this->program_record, ComplianceProgram::MODE_ALL);

        $program->setActiveUser($this->user);


        if ($this->program_record->id == "1221") {
            $this->program_points = $program->getStatus()->getPoints();
        }


        $this->form = new ComplianceProgramExceptionForm(array(), array('user' => $this->user, 'compliance_program' => $program));

        if($this->form->isValidForRequest($request)) {
            $this->form->save();

            $this->getUser()->setNoticeFlash('Settings saved.');

            $this->dispatcher->notify(new sfEvent(
                $program,
                'compliance_programs.edit_exceptions',
                array('user' => $this->user)
            ));

            $this->redirect(sprintf(
                'compliance_programs/editComplianceProgramExceptions?id=%s&user_id=%s',
                $this->program_record->id,
                $this->user->id
            ));
        }
    }

    public function executeDownloadIndividualReports(sfRequest $request)
    {
        $this->form = $form = new DownloadIndividualReports(array(
            'disable_layout' => true
        ), array(
            'query' => $this->secureObject(ComplianceProgramRecordTable::getInstance()->findForBackend()),
        ));

        if(!$this->form->isValidForRequest($request)) {
            return sfView::SUCCESS;
        }

        $client = ClientTable::getInstance()->find($this->form->getValue('client_id'));

        $userIdValue = str_replace("\r", "", str_replace("\n", ",", str_replace("\r\n", "\n", $this->form->getValue('user_ids'))));

        $users = array_unique(array_filter(explode(',', $userIdValue)));

        $this->getContext()->getConfiguration()->loadHelpers(array('ServiceLogin'));

        $this->getResponse()->setDownloadHeaders(
            'application/zip',
            sprintf('compliance-individual-reports-generated-%s-%s-users.zip', date('YmdHis'), count($users)),
            true
        );

        return $this->renderCallable(function() use($users, $form) {
            flush();

            $record = ComplianceProgramRecordTable::getInstance()->find($form->getValue('compliance_program_record_id'));

            $zipStreamer = new ZipStream(function($data) {
                echo $data;

                flush();
            });

            $pdfRenderer = new HTMLPDFRenderer();
            $pdfRenderer->setMargin(4, 4, 4, 4);
            $pdfRenderer->setPrintMediaType(false);

            if(!count($users)) {
                $userQuery = $record->getUsers(array(
                    'execute'         => false
                ));

                $userQuery->select(sprintf('%s.id', $userQuery->getRootAlias()));

                $userQuery->setHydrationMode(Doctrine_Core::HYDRATE_ARRAY);

                foreach($userQuery->execute() as $userQueryRow) {
                    $users[] = $userQueryRow['id'];
                }
            }

            foreach($users as $userId) {
                if($userId && ($user = UserTable::getInstance()->find($userId))) {

                    if ($user->client_id == 4514) {
                        $renderURL = service_login_url_for($user, 'content_show', array('slug' => 'print_reportcard'));
                    } else {
                        $renderURL = service_login_url_for($user, 'compliance_programs_show', array(
                            'id'      => $form->getValue('compliance_program_record_id'),
                            '_layout' => $form->getValue('disable_layout') ? 1 : 0
                        ));
                    }

                    $zipStreamer->addFile("{$userId}.pdf", $pdfRenderer->render($renderURL));
                }
            }

            $zipStreamer->finish();
        });
    }

    /**
     * Builds a flat-file admin report of status for all active users of
     * a client for a program.
     *
     * @param sfRequest $request
     */
    public function executeAdminReport(sfRequest $request)
    {

        $user = $this->getSessionUser();

        $excludedClients = array();
        if($user->client_id == 2577) {
            $excludedClients = array(2251, 2277, 2320, 2401, 4033);
        }

        if($user->client_id == 1806) {
            $excludedClients = array(2251, 2277, 2320, 2401, 4033);
        }

        $this->form = new LegacyAdminReportForm(array(), array(
            'query'          => $this->secureObject(ComplianceProgramRecordTable::getInstance()->findForBackend(array('excluded_clients' => $excludedClients)))
        ));

        $_SESSION['compliance_mode'] = ComplianceProgram::MODE_ADMIN;

        if($this->form->isValidForRequest($request)) {
            $_SESSION['compliance_mode'] = ComplianceProgram::MODE_ADMIN;

            $programRecord = $this->form->getComplianceProgramRecord();
            $program = $this->secureObject($this->getComplianceProgram($programRecord, ComplianceProgram::MODE_ADMIN));
            $userQuery = $programRecord->getUsers(array(
                'execute'         => false,
                'include_expired' => $this->form->getValue('include_expired')
            ));
            $userCountQuery = clone $userQuery;
            $program->preQuery($userCountQuery);
            $numberOfUsers = $userCountQuery->count();
            $percentIncrement = $numberOfUsers > 0 ? 1 / $numberOfUsers * 100.0 : 0;

            $secureFile = new SecureFile();
            $secureFile->setName(sprintf('compliance-program-%s-%s.csv', $programRecord->getClient()
                ->getUsergroup(), date('YmdHis')));
            //$secureFile->setProgress(0);
            $secureFile->setContentType('text/csv');
            $secureFile->setUserID($this->getSessionUser()->getID());
            $secureFile->setTemporary(true);

            if($this->form->getValue('download')) {
                $printer = $program->getAdminProgramReportPrinter();
            } else {
                $secureFile->setContentType('text/html');
                $printer = new OnlineLightsProgramAdminReportPrinter();
            }

            $secureFile->save();
            $secureFile->redirectToStream($this->getResponse(), $this->getController());

            $programReport = $program->useParallelReport() ?
                new ParallelComplianceProgramReport($program, $userQuery, $this->getSessionUser()) :
                new ComplianceProgramReport($program, $userQuery, $this->getSessionUser());

            $i = 0;

            $this->getContext()->getEventDispatcher()
                ->connect('compliance_program.status_calculated', function (sfEvent $event) use (&$i, $secureFile, $percentIncrement) {
                $i += $percentIncrement;

                if($i >= 1) {
                    $secureFile->setProgress($secureFile->getProgress() + $i);
                    $secureFile->save();

                    $i = 0;
                }
            });

            ob_start();
            $printer->printAdminReport($programReport, fopen('php://output', 'w'));
            $secureFile->setData(ob_get_clean());
            $secureFile->save();

            //$secureFile->stream($this->getResponse());

            return sfView::NONE;
        }
    }

    public function executeBatchAdminReport(sfWebRequest $request)
    {
        $this->getContext()->getConfiguration()->loadHelpers(array('ServiceLogin'));

        $this->form = new BatchAdminReportForm();

        if($this->form->isValidForRequest($request)) {
            $clientId = $this->form->getValue('parent_client_id');

            $today = date('Y-m-d');

            $clientIds = ClientTable::getInstance()->getAllChildIDs($clientId);

            $programRecordQuery = ComplianceProgramRecordQuery::createQuery('q')
                ->whereIn('q.client_id', $clientIds)
                ->innerJoin('q.client c')
                ->select('q.id, q.description, c.name, q.start_date, q.end_date');

            if($this->form->getValue('active_only')) {
                $programRecordQuery->andWhere('q.active = 1');
            }

            $programRecords = $programRecordQuery
                ->setHydrationMode(Doctrine_Core::HYDRATE_SCALAR)
                ->execute();

            $this->getResponse()->setDownloadHeaders(
                'application/zip',
                "batch-admin-report-{$clientId}-{$today}.zip",
                true
            );

            $user = $this->getSessionUser();
            $userAccount = $this->getSessionUserAccount();
            $sfUser = $this->getUser();

            return $this->renderCallable(function () use ($programRecords, $user, $userAccount, $sfUser) {
                ob_implicit_flush(true);

                $zip = new ZipStream(function($data) {
                    echo $data;

                    flush();
                });

                $zip->addFile('Readme.txt', 'This file was automatically generated on '.date('Y-m-d').'.');

                $tempFile = tempnam(sys_get_temp_dir(), 'compliance_stream');

                try {
                    foreach($programRecords as $program) {
                        $name = sprintf('#%s - %s (%s through %s)',
                            $program['q_id'],
                            $program['c_name'],
                            $program['q_start_date'],
                            $program['q_end_date']
                        );

                        if($program['q_description']) {
                            $name .= " - {$program['q_description']}";
                        }

                        $name .= '.csv';

                        // Can't have / or \ in file names
                        $name = str_replace(array('/', '\\'), array('_', '_'), $name);

                        $token = UserLoginTokenTable::getInstance()->generate(
                            $user, $userAccount, $sfUser->getStorableInitiatingUserId()
                        );

                        $serviceUrl = service_login_url_for_token(
                            $token,
                            'compliance_program_stream',
                            array('id' => $program['q_id'])
                        );

                        $zip->addFile($name, fopen($serviceUrl, 'r'));
                    }

                    $zip->finish();

                } catch(Exception $e) {
                    @unlink($tempFile);

                    throw $e;
                }

                @unlink($tempFile);

            });
        }
    }

    public function executeCalculateComplianceProgramStatus(sfWebRequest $request)
    {
        /**
         * @var ComplianceProgramRecord $record
         */

        $record = ComplianceProgramRecordTable::getInstance()->find($request->getParameter('compliance_program_record_id'));

        if(!$record) {
            $this->forward404();
        }

        if($request->getParameter('api_key') != ParallelComplianceProgramReport::API_KEY) {
            $this->forward401();
        }

        $this->response->setContentType('application/json');

        $program = $record->getComplianceProgram();

        $data = array();

        foreach((array)$request->getParameter('ids') as $id) {
            $userId = (int) $id;

            if($user = UserTable::getInstance()->find($userId)) {
                $program->setActiveUser($user);

                $status = $program->getStatus();

                foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                    foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                        $data[$userId][$viewStatus->getComplianceView()->getName()] = array(
                            'status'         => $viewStatus->getStatus(),
                            'points'         => $viewStatus->getPoints(),
                            'comment'        => $viewStatus->getComment(),
                            'attributes'     => $viewStatus->getAttributes(),
                            'using_override' => $viewStatus->getUsingOverride()
                        );
                    }
                }
            }
        }

        return $this->renderText(json_encode($data));
    }

    public function executeStreamAdminReport(sfWebRequest $request)
    {
        $id = $request->getParameter('id');

        $programRecord = ComplianceProgramRecordTable::getInstance()->find($id);

        $this->forward404Unless($programRecord);

        $program = $this->getComplianceProgram($programRecord, ComplianceProgram::MODE_ADMIN);

        $this->getResponse()->setContentType('text/csv');

        $userQuery = $programRecord->getUsers(array('execute' => false));

        $programReport = new ComplianceProgramReport($program, $userQuery, $this->getSessionUser());

        $printer = $program->getAdminProgramReportPrinter();

        return $this->renderCallable(function () use ($programReport, $printer) {
            $printer->printAdminReport($programReport, fopen('php://output', 'w'));
        });
    }

    public function executeViewScreeningReport(sfWebRequest $request)
    {
        $recordTable = ComplianceProgramRecordTable::getInstance();

        $record = $request->getParameter('id') ? $recordTable->find($request->getParameter('id')) : $recordTable->findApplicableActive($this->getSessionClient());

        $this->secureObject($record);

        $userQuery = UserTable::getInstance()->createQuery('u')->where('u.id = ?', $this->getSessionUser()->id);

        $this->compliance_program = $this->getComplianceProgram($record, ComplianceProgram::MODE_INDIVIDUAL);
        $this->compliance_program->preQuery($userQuery);
        $this->collection = $userQuery->execute();
        $this->user = $this->collection->getFirst();

        if(!$this->user) {
            // This can happen for programs that override preQuery to only target
            // a subset of a population for the program.
            $this->compliance_program->handleInvalidUser($this);
        }

        $this->compliance_program->setActiveUser($this->user);

        $complianceStatus = $this->compliance_program->getStatus();

        $hpaReportView = $this->compliance_program->getComplianceViewByClassName('ViewViewBasedHpaReportComplianceView');

        if($hpaReportView) {
            // @TODO hardcoded sife-effects here
            // Calculating status for this view loads the links

            $hpaReportView->getStatus($this->user);

            $links = $hpaReportView->getLinks();

            if(count($links)) {
                $link = reset($links);

                $this->redirect($link->getLink());
            } else {
                $this->getUser()->setNoticeFlash(sfConfig::get('mod_compliance_programs_screening_report_unavailable_text', 'Your screening report is not available yet.'));
            }
        } else {
            $this->forward404();
        }
    }

    private function userHasAssociation(ComplianceProgramRecord $record, $userId)
    {
        $matchingUsers = $this->searchForUsersQuery($record, true)
            ->andWhere('u.id = ?', array($userId))
            ->execute()
            ->toArray();

        return count($matchingUsers) === 0;
    }

    private function isDuplicateTeam($record_id, $name) {
        $teamQuery = SelectQuery::create()
            ->select("name")
            ->from('compliance_program_record_teams')
            ->where('compliance_program_record_id = ?', array($record_id))
            ->andWhere('name = ?', array($name));
        $teamRecords = $teamQuery->hydrateSingleRow()->execute();
        return isset($teamRecords['name']);
    }

    private function searchForUsers(ComplianceProgramRecord $record, $lastName)
    {
        $forceSpouse = $record->getComplianceProgram()->getOption('force_spouse_with_employee');

        $lastNameSql = str_replace('%', '\%', trim($lastName));

        $userQuery = $this->searchForUsersQuery($record);

        $userQuery
            ->andWhere('u.last_name LIKE ?', array("{$lastNameSql}%"))
            ->andWhere('u.id != ?', array($this->getSessionUser()->id));

        if ($forceSpouse) {
            $excludedSpouses = $record->getComplianceProgram()->getOption('force_spouse_with_employee_excluded_spouses');

            if($excludedSpouses) {
                $userQuery->andWhere('(u.relationship_type = ? OR u.id IN ?)', array(Relationship::EMPLOYEE, $excludedSpouses));
            } else {
                $userQuery->andWhere('u.relationship_type = ?', array(Relationship::EMPLOYEE));
            }
        }

        return $userQuery->hydrateMapRow('id')->execute();
    }

    private function searchForUsersQuery(ComplianceProgramRecord $record, $ignoreIfTeamedOrBuddies = true)
    {
        $userQuery = SelectQuery::create()
            ->select("u.id, u.gender, u.first_name, u.middle_name, u.last_name, u.date_of_birth, CONCAT(u.first_name, IF(IFNULL(u.middle_name, '') = '', ' ', CONCAT(' ', SUBSTRING(u.middle_name, 1, 1), '. ')), u.last_name, ' (', c.name, ')') AS display_name")
            ->from('users u')
            ->leftJoin('clients c')
            ->on('c.id = u.client_id')
            ->leftJoin('compliance_program_record_team_users team_users')
            ->on('team_users.user_id = u.id')
            ->andOn('team_users.compliance_program_record_id = ?', array($record->id))
            ->leftJoin('compliance_program_record_buddies buddies')
            ->on('buddies.first_user_id = u.id OR buddies.second_user_id = u.id')
            ->andOn('buddies.compliance_program_record_id = ?', array($record->id));

        if($record->cascades) {
            $userQuery->where('u.client_id IN ?', array(ClientTable::getInstance()->getExpandedClientIDs(array($record->client_id))));
        } else {
            $userQuery->where('u.client_id = ?', array($record->client_id));
        }

        if($ignoreIfTeamedOrBuddies) {
            $userQuery
                ->andWhere('buddies.id IS NULL')
                ->andWhere('team_users.id IS NULL');
        }

        $userQuery
            ->andWhere('u.expires IS NULL OR u.expires > CURDATE()')
            ->andWhere('u.attributes & ? = 0', array(Attribute::DEMO_USER));


//        $userQuery->limit(100);

        return $userQuery;
    }

    private function getComplianceProgramRecord(sfRequest $request)
    {
        $recordTable = ComplianceProgramRecordTable::getInstance();

        $record = $request->getParameter('id') ?
            $recordTable->find($request->getParameter('id')) :
            $recordTable->findApplicableActive($this->getSessionClient());

        $this->secureObject($record);

        return $record;
    }

    /**
     * @param ComplianceProgramRecord $record
     * @return ComplianceProgram
     */
    private function getComplianceProgram(ComplianceProgramRecord $record, $mode)
    {
        $program = $record->getComplianceProgram();
        $program->setMode($mode);
        $program->setDispatcher($this->getContext()->getEventDispatcher());

        return $program;
    }

    private function sendEmail(ComplianceProgram $program, User $user, $index)
    {
        $content = $program->getEmailContent(array());

        if(isset($content[$index], $content[$index]['body'], $content[$index]['subject'])) {
            $user->sendEmail($content[$index]['subject'], $content[$index]['body']);
            $user->sendUserMessage($content[$index]['subject'], $content[$index]['body'], $user);
        }
    }

    private function getCHPWalkingProgramRecordIdMapper()
    {
        return array(
            '176'   => '1269',
            '2216'  => '1269',
            '2217'  => '1269',
        );
    }
}
