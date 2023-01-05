<?php

class ComplyWithSystolicBloodPressureScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function getTestName()
    {
        return 'systolic';
    }

    public function getDefaultName()
    {
        return 'comply_with_systolic_blood_pressure_screening_test';
    }

    public function getFallbackQuestionId()
    {
        return 60;
    }
}
