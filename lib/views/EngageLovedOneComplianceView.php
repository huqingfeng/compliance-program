<?php
class EngageLovedOneComplianceView extends CompleteActivityComplianceView
{
    public function __construct($startDate, $endDate, $pointsPerEngagement)
    {
        parent::__construct($startDate, $endDate);
        $this->pointsPerRecord = $pointsPerEngagement;
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity(ActivityTrackerActivity::ENGAGE_OTHER_PERSON);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $totalPoints = count($records) * $this->pointsPerRecord;

        return new ComplianceViewStatus($this, null, $totalPoints);
    }

    private $pointsPerRecord;
}
