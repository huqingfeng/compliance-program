<?php

class SummedComplianceViewPointsComplianceView extends ComplianceView
{
    public function __construct(array $views)
    {
        $this->views = $views;
    }

    public function getDefaultName()
    {
        return 'summed_views';
    }

    public function getDefaultReportName()
    {
        return 'Summed Views';
    }

    public function getStatus(User $user)
    {
        $points = 0;

        foreach($this->views as $view) {
            $viewStatus = $view->getStatus($user);

            $points += $viewStatus->getPoints();
        }

        return new ComplianceViewStatus($this, null, $points);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    protected $views;
}
