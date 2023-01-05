<?php
class ComplyWithHDLScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'hdl';
    }

    public function getDefaultName()
    {
        return 'comply_with_hdl_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'HDL Cholesterol';
    }
}