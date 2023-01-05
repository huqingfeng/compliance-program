<?php

class CompletePreventionPSAComplianceView extends CompletePreventionComplianceView
{
    public function getPreventionType()
    {
        return PreventionType::PSA;
    }

    public function requiredForUser(User $user)
    {
        return parent::requiredForUser($user) && $user->getGender() == Gender::MALE;
    }
}