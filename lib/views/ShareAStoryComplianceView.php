<?php

class ShareAStoryComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(44);
    }

    public function __construct($startDate, $endDate, $pointsPerAttendance)
    {
        parent::__construct($startDate, $endDate);

        $this->pointsPerRecord = $pointsPerAttendance;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $totalPoints = $this->pointsPerRecord * count($records);

        return new ComplianceViewStatus($this, null, $totalPoints);
    }

    private $pointsPerRecord = null;
}