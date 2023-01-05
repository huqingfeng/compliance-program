<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));


class HmiRangeStepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $pointsPer)
    {
        $this->setDateRange($startDate, $endDate);
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "hmi_range_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Range Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        $points = 0;

        foreach($data['dates'] as $date) {
            if($date >= $this->threshold) {
                $points += $this->pointsPer;
            }
        }

        return new ComplianceViewStatus($this, null, $points);
    }
}