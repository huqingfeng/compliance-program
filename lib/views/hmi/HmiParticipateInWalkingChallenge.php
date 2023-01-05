<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class HmiParticipateInWalkingChallenge extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $groupAlias, $pointsForParticipation, $requireTeam = true)
    {
        $this->setDateRange($startDate, $endDate);
        $this->pointsForParticipation = $pointsForParticipation;
        $this->groupAlias = $groupAlias;
        $this->requireTeam = $requireTeam;
    }

    public function getDefaultName()
    {
        return 'participate_in_walking_challenge';
    }

    public function getDefaultReportName()
    {
        return 'Participate In Walking Challenge';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $points = 0;

        $allTeamData = get_walking_teams($this->groupAlias, $user->id);

        $teamData = count($allTeamData) ? reset($allTeamData) : array();

        $totalSteps = 0;

        if(!$this->requireTeam || isset($teamData[$user->id])) {
            $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

            if($data && isset($data['total_steps']) && $data['total_steps'] > 0) {
                $points = $this->pointsForParticipation;

                $totalSteps = $data['total_steps'];
            }
        }

        $status = new ComplianceViewStatus($this, null, $points);
        $status->setAttribute('total_steps', $totalSteps);

        return $status;
    }

    private $requireTeam;
    private $groupAlias;
    private $pointsForParticipation;
}