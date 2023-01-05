<?php

class CompleteArbitraryActivityComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function __construct($startDate, $endDate, $activityId, $pointsPerRecord)
    {
        $this->activityId = $activityId;
        $this->pointsPerRecord = $pointsPerRecord;

        parent::__construct($startDate, $endDate);
    }

    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        return new ComplianceViewStatus(
            $this,
            null,
            $this->pointsPerRecord * count($records)
        );
    }

    public function setRemoveDuplicates($boolean)
    {
        $this->removeDuplicates = $boolean;
    }

    public function latestRecordDate(User $user) {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
        if (!empty ($records[0])) {
            return date("m/d/Y", strtotime($records[0]->date));
        } else {
            return false;
        }
    }

    public function getRecords(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        if($this->removeDuplicates) {
            $ret = array();

            foreach($records as $record) {
                if(!isset($ret[$record->date])) {
                    $ret[$record->date] = $record;
                }
            }

            return array_values($ret);
        } else {
            return $records;
        }
    }

    private $removeDuplicates = false;
    private $activityId;
    private $pointsPerRecord;
}