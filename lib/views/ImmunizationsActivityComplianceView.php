<?php
class ImmunizationsActivityComplianceView extends CompleteActivityComplianceView
{
    public function __construct($startDate, $endDate, $pointsPerRecord)
    {
        parent::__construct($startDate, $endDate);

        $this->pointsPerRecord = $pointsPerRecord;
    }

    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->id);
    }

    public function _setId($id)
    {
        $this->id = $id;
        $this->emptyLinks();
        $this->addLink(new ActivityComplianceViewLink($this->getActivity()));
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        return $this->fixStatus(
            new ComplianceViewStatus($this, null, $this->pointsPerRecord * count($records)),
            $records
        );
    }

    private $id = 60;
    private $pointsPerRecord;
}

