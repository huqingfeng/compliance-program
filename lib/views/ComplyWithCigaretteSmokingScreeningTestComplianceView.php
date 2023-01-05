<?php
class ComplyWithCigaretteSmokingScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function  __construct($startDate, $endDate, $screening = null)
    {
        parent::__construct($startDate, $endDate, $screening);
        $this->addResultMapping('User', ComplianceStatus::NOT_COMPLIANT, 'User');
        $this->addResultMapping('Non-User', ComplianceStatus::COMPLIANT, 'Non-User');
        $this->addResultMapping('Non User', ComplianceStatus::COMPLIANT, 'Non User');
    }

    public function getTestName()
    {
        return 'cigarettesmoking';
    }

    public function getDefaultName()
    {
        return 'comply_with_cigarette_smoking__screening_test';
    }
}
