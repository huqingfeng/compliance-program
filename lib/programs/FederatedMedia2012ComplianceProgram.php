<?php

class FederatedMedia2012ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>
    <p>Hi <?php echo $_user ?>,</p>
    <p>
        If you comply with your program, you will see green lights.
    </p>
    <?php
    }
}

class FederatedMedia2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new FederatedMedia2012ComplianceProgramReportPrinter();
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('Required For Premium Incentive');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);

        $requiredGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($requiredGroup);

        $optionalGroup = new ComplianceViewGroup('Optional');

        $anthemHRAView = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $anthemHRAView->setReportName('Complete Anthem HRA');
        $anthemHRAView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete HRA');
        $anthemHRAView->addLink(new Link('Do HRA', 'http://www.anthem.com/'));
        $optionalGroup->addComplianceView($anthemHRAView);

        $this->addComplianceViewGroup($optionalGroup);
    }
}