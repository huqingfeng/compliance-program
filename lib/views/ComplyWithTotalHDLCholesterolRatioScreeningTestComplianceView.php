<?php
class ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'totalhdlratio';
    }

    public function getDefaultName()
    {
        return 'comply_with_total_hdl_cholesterol_ratio_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Total/HDL Cholesterol Ratio';
    }
}