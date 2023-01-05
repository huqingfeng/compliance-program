<?php

class RSUIComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2011';
    }

    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>
    <style type="text/css">
        .phipTable .resource {
            width:240px;
        }

        .phipTable .links {
            width:240px;
        }

        .phipTable .requirements {
            display:none;
        }

        .phipTable tr td.status, .phipTable tr td.links {
            vertical-align:top;
        }
    </style>
    <p>Hi <?php echo $_user->getFirstName() ?>,</p>
    <p>
        To qualify for the preferred medical rate, you are required to complete
        the screening and health risk assessment. Green lights will appear when
        that requirement has been fulfilled.
    </p>
    <?php
    }

    public function printClientNote()
    {
        ?>
    <br/>
    <br/>
    <p>
        NOTE: Please remember that the above requirements performed in 2011 qualify you for the Preferred Medical Rate
        for both 2011 and 2012. We are shifting to a calendar year system, meaning the Preferred Medical Rate will be
        determined January 1st based on the previous year’s requirements. Screenings in 2012 will then qualify for 2013
        incentives, etc.
    </p>
    <?php
    }
}

class RSUIComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new RSUIComplianceProgramReportPrinter();

        $printer->filterComplianceViews(function (ComplianceViewStatus $status) {
            return $status->getStatus() != ComplianceStatus::NA_COMPLIANT;
        });

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = parent::getAdminProgramReportPrinter();
        $printer->addGroupTypeByAlias('deduction_benefit_plan');
        $printer->addGroupTypeByAlias('plan_option_code');
        $printer->setShowUserFields(null, null, null, false, true);

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('Required for Preferred Medical Rate - To be completed in 2011');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Screening');
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take / View Results', '/content/989'));
        $hraView->addLink(new Link('Historical Results', '/content/301716'));
        $requiredGroup->addComplianceView($hraView);

        $consultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $consultationView->setReportName('Complete Consultation');
        $consultationView->setEvaluateCallback(array($this, 'viewRequired'));
        $consultationView->addLink(new Link('I did this', '/resources/2507/rsui.verification.2011.pdf'));

        $requiredGroup->addComplianceView($consultationView);


        $this->addComplianceViewGroup($requiredGroup);

        $oneGroup = new ComplianceViewGroup('Complete one of the following during 2011 to qualify for the 2012 Preferred Medical Rate');
        $oneGroup->addComplianceView(
            PlaceHolderComplianceView::create(ComplianceStatus::NOT_COMPLIANT)
                ->setName('one')
                ->setReportName('<strong>Complete one of the following</strong><br/><br/>Annual Physical<br/><br/>Mammogram<br/><br/>Colonoscopy<br/><br/>Dental Exam<br/><br/>Smoking Cessation Certificate<br/><br/>Annual Eye Exam<br/><br/>Nutrition/Education Seminar<br/><br/>4 Week Fitness Program')
                ->setEvaluateCallback(array($this, 'viewRequired'))
                ->addLink(new Link('I did this', '/resources/2507/rsui.verification.2011.pdf'))
        );

        $this->addComplianceViewGroup($oneGroup);
    }

    public function viewRequired(User $user)
    {
        return $user->getRelationshipType() == Relationship::EMPLOYEE;
    }
}