<?php

class EventLogComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $eventName)
    {
        $this->setDateRange($startDate, $endDate);
        $this->eventName = $eventName;
    }

    public function allowPointsOverride()
    {
        return $this->allowPointsOverride === null ?
            parent::allowPointsOverride() : $this->allowPointsOverride;
    }

    public function getDefaultName()
    {
        return 'event_log_'.$this->eventName;
    }

    public function getDefaultReportName()
    {
        return $this->eventName;
    }

    public function getStatus(User $user)
    {
        $eventLogs = EventLogTable::getInstance()->findForUserBetween(
            $user, $this->eventName, $this->getStartDateTime(), $this->getEndDateTime()
        );

        $status = $eventLogs->count() > 0 ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

        return new ComplianceViewStatus($this, $status);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function setAllowPointsOverride($boolean)
    {
        $this->allowPointsOverride = $boolean;
    }

    private $allowPointsOverride = null;
    private $eventName;
    private $status;
    private $points;
}
