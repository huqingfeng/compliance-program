<?php

class CompletePreventionPapTestComplianceView extends CompletePreventionComplianceView
{
    public function getPreventionType()
    {
        return PreventionType::PAP_TEST;
    }

    public function requiredForUser(User $user)
    {
        return parent::requiredForUser($user) && $user->getGender() == Gender::FEMALE;
    }
}