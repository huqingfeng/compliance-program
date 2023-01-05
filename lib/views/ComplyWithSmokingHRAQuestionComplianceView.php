<?php
class ComplyWithSmokingHRAQuestionComplianceView extends ComplyWithHRAQuestionComplianceView
{
    public function getQuestionID()
    {
        return 36;
    }

    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceStatus::COMPLIANT:
                return 'Non-Smoker';
                break;
            case ComplianceStatus::PARTIALLY_COMPLIANT:
                return 'Previous Smoker';
                break;
            case ComplianceStatus::NOT_COMPLIANT:
                return 'Smoker';
            default:
                return null;
                break;
        }
    }

    public function getDefaultName()
    {
        return 'comply_with_smoking_hra_question';
    }

    public function getDefaultReportName()
    {
        return 'Smoking Status';
    }
}
