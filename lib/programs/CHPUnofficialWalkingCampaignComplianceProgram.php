<?php

class CHPUnofficialWalkingCampaignComplianceProgram extends CHPWalkingCampaignComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $this->options = array(
            'allow_teams'                => false,
            'team_leaderboard'           => false
        ) + $this->options;
    }
}