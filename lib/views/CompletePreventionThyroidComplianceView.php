<?php

class CompletePreventionThyroidComplianceView extends CompletePreventionComplianceView
{
    public function getPreventionType()
    {
        return PreventionType::THYROID;
    }
}