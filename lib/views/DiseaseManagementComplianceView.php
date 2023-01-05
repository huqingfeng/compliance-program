<?php

class DiseaseManagementComplianceView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'disease_management';
    }

    public function getDefaultReportName()
    {
        return 'Disease Management';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $newestDataRecord = $user->getNewestDataRecord('disease_management');

        if($newestDataRecord->exists() && $newestDataRecord->completed) {
            $status = ComplianceStatus::COMPLIANT;
        } else if($newestDataRecord->exists() && $newestDataRecord->required) {
            $status = ComplianceStatus::NOT_COMPLIANT;
        } else {
            $status = ComplianceStatus::NA_COMPLIANT;
        }

        return new ComplianceViewStatus($this, $status);
    }
}