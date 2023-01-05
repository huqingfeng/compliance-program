<?php

class HMI2017CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'use_creation_date' => true,
                'filter'           => $this->getFilter()
            )
        );

        return $data;
    }
}

class HMI2017ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new HMI2017CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setName('screening');

        $required->addComplianceView($screening);

        $this->addComplianceViewGroup($required);
    }
}