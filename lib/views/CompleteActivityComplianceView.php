<?php

abstract class CompleteActivityComplianceView extends DateBasedComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public abstract function getActivity();

    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $this->addLink(new ActivityComplianceViewLink($this->getActivity()));

        $this->configure();
    }

    protected function configure() { }

    public function getDefaultName()
    {
        return 'activity_'.$this->getActivity()->getID();
    }

    public function getDefaultReportName()
    {
        return $this->getActivity()->getName();
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    protected function fixStatus(ComplianceViewStatus $status, $records)
    {
        if(count($records)) {
            $newest = reset($records);

            $status->setAttribute('newest_record', $newest->getDate('m/d/Y'));
        }

        return $status;
    }
}

