<?php

class XRayIndustriesWalkingCampaignAdminPrinter extends BasicComplianceProgramAdminReportPrinter
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

        $this->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
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

            return $data;
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

    protected function showUser(User $user)
    {
        return $user->getRelationshipType() != Relationship::SPOUSE;
    }
}

class XRayIndustriesWalkingCampaignComplianceProgram extends CHPWalkingCampaignComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $record = $this->getComplianceProgramRecord();

        return new XRayIndustriesWalkingCampaignAdminPrinter($record);
    }


    public function loadGroups()
    {
        parent::loadGroups();

        $this->options['team_members_minimum'] = 2;
        $this->options['team_members_maximum'] = 50;
    }


}