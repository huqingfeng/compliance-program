<?php
class ComplyWithBodyFatScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'bodyfat';
    }

    public function getDefaultName()
    {
        return 'comply_with_bodyfat_screening_test';
    }

    public function getDefaultReportName()
    {
        return 'Body Fat (%)';
    }

    protected function useRawFallbackQuestionValue()
    {
        return true;
    }

    public function getFallbackQuestionId()
    {
        return 603;
    }
}
