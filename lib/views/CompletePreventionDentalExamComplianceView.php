<?php

class CompletePreventionDentalExamComplianceView extends CompletePreventionComplianceView
{
    public function getPreventionType()
    {
        return PreventionType::DENTAL;
    }
}