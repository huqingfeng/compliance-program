<?php

class USAFundsComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);

        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);

        $required->addComplianceView($hra);

        $consultation = new CompletePrivateConsultationComplianceView($startDate, $endDate);

        $required->addComplianceView($consultation);

        $this->addComplianceViewGroup($required);
    }
}