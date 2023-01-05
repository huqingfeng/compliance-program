<?php

class Smalley2014ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->emptyLinks();
        $screening->addLink(new Link('My Results', '/content/989'));

        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HRA', '/content/989'));

        $required->addComplianceView($hra);

        $this->addComplianceViewGroup($required);
    }
}