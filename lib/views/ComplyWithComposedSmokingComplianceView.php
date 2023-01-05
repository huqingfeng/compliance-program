<?php
/**
 * Smoking logic composed of both hra and screening results. The worse of the
 * two is evaluated.
 */
class ComplyWithComposedSmokingComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getDefaultName()
    {
        return 'composed_smoking';
    }

    public function getDefaultReportName()
    {
        return 'Smoking';
    }

    public function getDefaultStatusSummary($summary)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $cotinine = new ComplyWithCotinineScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $cotinine->setComplianceViewGroup($this->getComplianceViewGroup());

        $hra = new ComplyWithSmokingHRAQuestionComplianceView($this->getStartDate(), $this->getEndDate());
        $hra->setComplianceViewGroup($this->getComplianceViewGroup());

        $hraStatus = $hra->getStatus($user);
        $cotinineStatus = $cotinine->getStatus($user);

        if($hraStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
            $status = ComplianceStatus::NOT_COMPLIANT;
        } else if($cotinineStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
            $status = ComplianceStatus::NOT_COMPLIANT;
        } else if($hraStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
            $status = ComplianceStatus::PARTIALLY_COMPLIANT;
        } else if($cotinineStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
            $status = ComplianceStatus::PARTIALLY_COMPLIANT;
        } else if($hraStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $status = ComplianceStatus::COMPLIANT;
        } else if($cotinineStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $status = ComplianceStatus::COMPLIANT;
        } else {
            // Both are NA ..
            $status = ComplianceStatus::NA_COMPLIANT;
        }

        return new ComplianceViewStatus($this, $status);
    }
}