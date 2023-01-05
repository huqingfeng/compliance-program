<?php

class PlaceHolderComplianceView extends ComplianceView
{
    public function __construct($status = ComplianceStatus::NA_COMPLIANT, $points = null)
    {
        $this->status = $status;
        $this->points = $points;
    }

    public function allowPointsOverride()
    {
        return $this->allowPointsOverride === null ?
            parent::allowPointsOverride() : $this->allowPointsOverride;
    }

    public function getDefaultName()
    {
        return 'place_holder_'.$this->getReportName();
    }

    public function getDefaultReportName()
    {
        return 'Place Holder';
    }

    public function getStatus(User $user)
    {
        return new ComplianceViewStatus($this, $this->status, $this->points);
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
    private $status;
    private $points;
}
