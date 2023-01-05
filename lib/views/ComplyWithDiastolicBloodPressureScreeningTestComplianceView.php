<?php
class ComplyWithDiastolicBloodPressureScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'diastolic';
    }

    public function getDefaultName()
    {
        return 'comply_with_diastolic_blood_pressure_screening_test';
    }

    public function getFallbackQuestionId()
    {
        return 61;
    }
}