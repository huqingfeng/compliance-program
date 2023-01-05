<?php
class ComplyWithTriglyceridesScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'triglycerides';
    }

    public function getDefaultName()
    {
        return 'comply_with_triglycerides_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Triglycerides';
    }
}