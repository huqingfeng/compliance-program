<?php
class ComplyWithTotalLDLCholesterolRatioScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'ldl';
    }

    public function getDefaultName()
    {
        return 'comply_with_total_ldl_cholesterol_ratio_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Total/LDL Cholesterol Ratio';
    }
}