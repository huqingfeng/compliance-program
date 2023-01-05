<?php
class ObtainPreventiveExamComplianceView extends CompleteActivityComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity(ActivityTrackerActivity::PREVENTIVE_EXAM);
    }

    public function __construct($startDate, $endDate, $pointsPerAttendance)
    {
        parent::__construct($startDate, $endDate);
        $this->pointsPerRecord = $pointsPerAttendance;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $totalPoints = $this->pointsPerRecord === null ? null : $this->pointsPerRecord * count($records);

        return $this->fixStatus(
            new ComplianceViewStatus($this, null, $totalPoints),
            $records
        );
    }

    private $pointsPerRecord = null;
}
