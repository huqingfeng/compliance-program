<?php

class HraScoreComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $minimumScore)
    {
        $this->setDateRange($startDate, $endDate);
        $this->minimumScore = $minimumScore;
    }

    public function getDefaultName()
    {
        return 'hra_score_'.$this->minimumScore;
    }

    public function getDefaultReportName()
    {
        return 'HRA Score '.$this->minimumScore;
    }

    public function getStatus(User $user)
    {
        $hra = HRA::getNewestHRABetweenDates(
            $user, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d')
        );

        if($hra) {
            return new ComplianceViewStatus(
                $this,
                $hra->getTotalScore() >= $this->minimumScore ?
                    ComplianceStatus::COMPLIANT :
                    ComplianceStatus::NOT_COMPLIANT,
                null,
                $hra->getTotalScore()
            );
        } else {
            return new ComplianceViewStatus(
                $this, ComplianceStatus::NOT_COMPLIANT
            );
        }
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    private $minimumScore;
}