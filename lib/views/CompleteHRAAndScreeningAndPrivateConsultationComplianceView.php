<?php

class CompleteHRAAndScreeningAndPrivateConsultationComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getDefaultName()
    {
        return 'complete_screening_hra';
    }

    public function getDefaultReportName()
    {
        return 'Screening Program and Private Consultation';
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Complete Screening Program and Private Consultation' : null;
    }

    public function getStatus(User $user)
    {
        $hraView = new CompleteHRAAndScreeningComplianceView($this->startDate, $this->endDate);
        $hraView->setComplianceViewGroup($this->getComplianceViewGroup());

        $consultationView = new CompletePrivateConsultationComplianceView($this->startDate, $this->endDate);
        $consultationView->setComplianceViewGroup($this->getComplianceViewGroup());
        $hraCompliance = $hraView->getStatus($user)->isCompliant();
        $consultationCompliance = $consultationView->getStatus($user)->isCompliant();

        $complianceStatus = null;
        $complianceText = null;

        if($hraCompliance && $consultationCompliance) {
            $complianceText = 'Screening Program and Consultation Completed';
            $complianceStatus = ComplianceViewStatus::COMPLIANT;
        } else {
            $complianceStatus = ComplianceViewStatus::NOT_COMPLIANT;

            if($hraCompliance) {
                $complianceText = 'Screening Program Completed';
            } else if($consultationCompliance) {
                $complianceText = 'Private Consultation Completed';
            }
        }

        return new ComplianceViewStatus($this, $complianceStatus, null, $complianceText);
    }

    private $startDate;
    private $endDate;
}