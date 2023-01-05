<?php

class HmiMultipleAverageStepsComplianceView extends ComplianceView
{
    public function __construct($threshold, $pointsPer)
    {
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
    }

    public function addDateRange($startDate, $endDate)
    {
        $this->ranges[] = array($startDate, $endDate);
    }

    public function addSummaryDateRange($name, $startDate, $endDate)
    {
        $this->summaryRanges[$name] = array($startDate, $endDate);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "hmi_multi_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Multi Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $points = 0;

        $steps = array();

        $totalSteps = array();

        $attributes = array();

        foreach($this->summaryRanges as $name => $range) {
            $view = new HmiAverageStepsComplianceView($range[0], $range[1], $this->threshold);
            $view->setComplianceViewGroup($this->getComplianceViewGroup());
            if($this->useJawbone) $view->setUseJawbone(true);
            if($this->useMoves) $view->setUseMoves(true);
            $viewStatus = $view->getStatus($user);
            $attributes[$name] = $viewStatus->getAttribute('total_steps', 0);

            if($viewStatus->isCompliant()) {
                $attributes[$name.'_points'] = $this->pointsPer;
            }
        }

        foreach($this->ranges as $range) {
            $view = new HmiAverageStepsComplianceView($range[0], $range[1], $this->threshold);
            if($this->useJawbone) $view->setUseJawbone(true);
            if($this->useMoves) $view->setUseMoves(true);
            $view->setComplianceViewGroup($this->getComplianceViewGroup());

            $viewStatus = $view->getStatus($user);

            if($viewStatus->isCompliant()) {
                $points += $this->pointsPer;
            }

            $steps[] = $viewStatus->getAttribute('average_daily_steps', 0);
            $totalSteps[] = $viewStatus->getAttribute('total_steps', 0);
        }

        $status = new ComplianceViewStatus($this, null, $points);

        foreach($attributes as $aName => $aValue) {
            $status->setAttribute($aName, $aValue);
        }

        $status->setAttribute('average_daily_steps', implode('; ', $steps));
        $status->setAttribute('total_steps', implode('; ', $totalSteps));


        return $status;
    }

    public function setUseJawbone($useJawbone)
    {
        $this->useJawbone = $useJawbone;
    }

    public function setUseMoves($useMoves)
    {
        $this->useMoves = $useMoves;
    }

    private $threshold;
    private $pointsPer;
    private $ranges = array();
    private $summaryRanges = array();
    private $useJawbone = false;
    private $useMoves = false;
}
