<?php

class CompletePreventionVisionExamComplianceView extends CompletePreventionComplianceView
{
    public function getPreventionType()
    {
        return PreventionType::VISION;
    }
}