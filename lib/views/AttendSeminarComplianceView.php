<?php
class AttendSeminarComplianceView extends CompleteActivityComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity(42);
    }

    public function __construct($startDate, $endDate, $pointsPerAttendance, $isNumberRequired = false)
    {
        parent::__construct($startDate, $endDate);
        $this->pointsPerRecord = $pointsPerAttendance;
        $this->isNumberRequired = $isNumberRequired;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $points = $this->isNumberRequired ? null : ($this->pointsPerRecord === null ? null : $this->pointsPerRecord * count($records));
        $status = $this->isNumberRequired ? (count($records) >= $this->pointsPerRecord ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT) : null;

        return new ComplianceViewStatus($this, $status, $points);
    }

    private $pointsPerRecord = null;
    private $isNumberRequired = false;
}


