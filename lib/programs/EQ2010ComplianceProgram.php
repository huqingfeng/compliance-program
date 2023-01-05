<?php

class EQ2010ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $group = new ComplianceViewGroup('required', 'Required');

        $group
            ->addComplianceView(CompleteHRAComplianceView::create($start, $end))
            ->addComplianceView(CompleteScreeningComplianceView::create($start, $end))
            ->addComplianceView(CompletePrivateConsultationComplianceView::create($start, $end));

        $this->addComplianceViewGroup($group);
    }

    public function  getAdminProgramReportPrinter()
    {
        $printer = parent::getAdminProgramReportPrinter();
        $printer->setShowCompliant(true, false, true);
        $printer->setShowPoints(false, false, false);
        $printer->setShowComment(false, false, true);

        return $printer;
    }
}