<?php
class ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView extends ComplyWithSmokingHRAQuestionComplianceView
{
    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceStatus::COMPLIANT:
                return 'Non-Smoker';
                break;
            case ComplianceStatus::NOT_COMPLIANT:
                return 'Smoker';
            default:
                return null;
                break;
        }
    }

    public function getStatus(User $user)
    {
        $status = parent::getStatus($user);

        if($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

        return $status;
    }
}
