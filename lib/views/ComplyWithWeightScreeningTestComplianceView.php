<?php
class ComplyWithWeightScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'weight';
    }

    public function getDefaultName()
    {
        return 'comply_with_weight_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Weight';
    }
}