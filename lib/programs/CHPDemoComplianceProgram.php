<?php

class CHPDemoComplianceProgram extends SBMF2009ComplianceProgram
{
    public function loadGroups()
    {
        parent::loadGroups();

        $group = $this->getComplianceViewGroup('prevention_event');
        $group->addComplianceView(
            UpdateDoctorInformationComplianceView::create($this->getStartDate(), $this->getEndDate())
                ->setReportName('My primary care doctor\'s CURRENT contact info')
                ->setName('update_doctor')
        );
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new CHPStatusBasedComplianceProgramReportPrinter();
    }
}