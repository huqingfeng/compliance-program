<?php
class ComplyWithHeightScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'height';
    }

    public function getDefaultName()
    {
        return 'comply_with_height_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Height';
    }
}