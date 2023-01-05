<?php

class CompleteConfigurableActivityComplianceView extends CompleteActivityComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function __construct($startDate, $endDate, $id, $pointsPerAttendance, $isNumberRequired = false)
    {
        $this->id = $id;
        parent::__construct($startDate, $endDate);
        $this->id = $id;
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

    private $id;
    private $pointsPerRecord = null;
    private $isNumberRequired = false;
}

