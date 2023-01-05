<?php

class CompletePreventionPhysicalExamComplianceView extends CompletePreventionComplianceView
{
    public function getPreventionType()
    {
        return PreventionType::PHYSICAL;
    }
}