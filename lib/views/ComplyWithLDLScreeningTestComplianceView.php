<?php
class ComplyWithLDLScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'ldl';
    }

    public function getDefaultName()
    {
        return 'comply_with_ldl_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'LDL Cholesterol';
    }

    public function getStatus(User $user)
    {
        $status = parent::getStatus($user);

        if((string) $status->getComment() === '0') {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        return $status;
    }
}