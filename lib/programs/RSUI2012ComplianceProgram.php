<?php

class RSUI2012ComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2012';
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
    <p><a href="/request_archive_collections/show?id=4">View your 2011 report card</a></p>

    <p>Hi <?php echo $_user->getFirstName() ?>,</p>
    <p>
        To earn the 2013 Preferred Medical Rate, you are required to complete the following during 2012:</p>
    <ul>
        <li>Biometric Screening (employee and covered spouse or domestic partner)</li>
        <li>Completion of Health Risk Appraisal (employee and covered spouse or domestic partner)</li>
        <li>One on One Consultation (employee only)</li>
        <li>Completion of a Preventative Visit (employee only)</li>
    </ul>
    <p>Green lights will appear when that requirement has been fulfilled. Please note this is your personal report card
        and will not reflect the status of your spouse or domestic partner. </p>

    </p>
    <?php
    }

    public function printClientNote()
    {
        ?>
    <br/>
    <br/>

    <?php
    }
}

class RSUI2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new RSUI2012ComplianceProgramReportPrinter();

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

        $requiredGroup = new ComplianceViewGroup('Required for Preferred Medical Rate - To be completed in 2012');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/content/989'));
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take / View Results', '/content/989'));
        //$hraView->addLink(new Link('Historical Results', '/content/301716'));
        $requiredGroup->addComplianceView($hraView);

        $consultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $consultationView->setReportName('Complete Consultation');
        $consultationView->setEvaluateCallback(array($this, 'viewRequired'));
        $consultationView->addLink(new Link('I did this', '/content/i_did_this'));

        $requiredGroup->addComplianceView($consultationView);

        $oneView = new AttendAppointmentComplianceView($programStart, $programEnd);
        $oneView->setName('one');
        $oneView->bindTypeIds(array(43));
        $oneView->setReportName('
      <strong>Complete one of the following</strong>
      <br/>
      <br/><div style="padding-left:24px;">
      Annual Physical<br/><br/>
      Mammogram<br/><br/>
      Colonoscopy<br/><br/>
      Dental Exam<br/><br/>
      Smoking Cessation Certificate<br/><br/>
      Annual Eye Exam<br/><br/>
      Mid-Year Checkup
      </div>
    ');

        $oneView->setEvaluateCallback(array($this, 'viewRequired'));
        $oneView->addLink(new Link('I did this', '/content/i_did_this'));


        $requiredGroup->addComplianceView($oneView);

        $this->addComplianceViewGroup($requiredGroup);
    }

    public function viewRequired(User $user)
    {
        return $user->getRelationshipType() == Relationship::EMPLOYEE;
    }
}