<?php
class ComplyWithGlucoseScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'glucose';
    }

    public function getDefaultName()
    {
        return 'comply_with_glucose_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Glucose';
    }
}