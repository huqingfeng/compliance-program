<?php
class ComplyWithCotinineScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function  __construct($startDate, $endDate, $screening = null)
    {
        parent::__construct($startDate, $endDate, $screening);
        $this->addResultMapping('positive', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('Positive', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('Postivie', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('P', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('nos', ComplianceStatus::COMPLIANT, 'Negative');
        $this->addResultMapping('negative', ComplianceStatus::COMPLIANT, 'Negative');
        $this->addResultMapping('Negative', ComplianceStatus::COMPLIANT, 'Negative');
        $this->addResultMapping('N', ComplianceStatus::COMPLIANT, 'Negative');
        $this->addResultMapping('Non-User', ComplianceStatus::COMPLIANT, 'Non-User');
    }

    public function getTestName()
    {
        return 'cotinine';
    }

    public function getDefaultName()
    {
        return 'comply_with_cotinine_screening_test';
    }
}
