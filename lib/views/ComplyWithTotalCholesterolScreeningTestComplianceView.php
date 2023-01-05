<?php
class ComplyWithTotalCholesterolScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'cholesterol';
    }

    public function getDefaultName()
    {
        return 'comply_with_total_cholesterol_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Total Cholesterol';
    }
}
