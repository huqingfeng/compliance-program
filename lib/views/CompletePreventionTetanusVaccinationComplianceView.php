<?php

class CompletePreventionTetanusVaccinationComplianceView extends CompletePreventionComplianceView
{
    public function getPreventionType()
    {
        return PreventionType::TETANUS_VACCINATION;
    }
}