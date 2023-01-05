<?php
class ComplyWithNonHDLCholesterolScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'non_hdl_cholesterol';
    }

    public function getDefaultName()
    {
        return 'comply_with_non_hdl_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Non HDL Cholesterol';
    }
}