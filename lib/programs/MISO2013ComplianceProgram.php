<?php

class MISO2013ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowStatus(false, false, false);
        $printer->setShowText(true, true, true);
        $printer->setShowUserContactFields(null, null, true);

        $printer->addCallbackField('location', function (User $user) {
            return $user->getLocation();
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->emptyLinks();
        $screening->addLink(new Link('Results', '/content/989'));

        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);

        $required->addComplianceView($hra);

        $coaching = new AttendAppointmentComplianceView($startDate, $endDate);
        $coaching->bindTypeIds(array(11, 46, 21));
        $coaching->setName('coaching');
        $coaching->setReportName('Was Consulted');
        $required->addComplianceView($coaching);

        $this->addComplianceViewGroup($required);
    }
}