<?php
class CulverCompleteAerobicExerciseComplianceView extends ComplianceView
{
    public function getStatus(User $user)
    {
        $dataRecord = $user->getNewestDataRecord('culver_aerobic_exercise');

        $points = null;

        if($dataRecord) {
            $points = $dataRecord->points;
        }

        return new ComplianceViewStatus($this, null, $points ? $points : 0);
    }

    public function getMaximumNumberOfPoints()
    {
        return 8;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'complete_aerobic_exercise';
    }

    public function getDefaultReportName()
    {
        return 'Aerobic Exercise';
    }
}