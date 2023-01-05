<?php

class CompletePreventionMammographyComplianceView extends CompletePreventionComplianceView
{
    public function getPreventionType()
    {
        return PreventionType::MAMMOGRAPHY;
    }

    public function requiredForUser(User $user)
    {
        return parent::requiredForUser($user) && $user->getGender() == Gender::FEMALE;
    }
}