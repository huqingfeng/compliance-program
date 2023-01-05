<?php

class CompleteScreeningTestComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $field)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);

        $this->field = $field;
    }

    public function getDefaultName()
    {
        return 'complete_screening_test_'.$this->field;
    }

    public function getDefaultReportName()
    {
        return 'Complete '.$this->field;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $dateTaken = ScreeningQuery::createQuery('s')
            ->select('s.date')
            ->forUser($user)
            ->takenBetween($this->getStartDateTime(), $this->getEndDateTime())
            ->withTestResult($this->field)
            ->orderBy('s.date DESC')
            ->limit(1)
            ->execute(array(), Doctrine_Core::HYDRATE_SINGLE_SCALAR);

        if($dateTaken) {
            $status = ComplianceStatus::COMPLIANT;
            $comment = date('m/d/Y', strtotime($dateTaken));
        } else {
            $status = ComplianceStatus::NOT_COMPLIANT;
            $comment = null;
        }

        return new ComplianceViewStatus($this, $status, null, $comment);
    }

    protected $field;
}