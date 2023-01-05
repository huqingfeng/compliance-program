<?php
class ComplyWithHa1cScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'ha1c';
    }

    public function getDefaultName()
    {
        return 'comply_with_ha1c_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Ha1c';
    }
}