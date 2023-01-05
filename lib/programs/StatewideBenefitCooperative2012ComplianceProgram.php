<?php

class StatewideBenefitCoop2012LearningAlternativeComplianceView extends ComplianceView
{
    public function __construct($programStart, $programEnd, $alias)
    {
        $this->start = $programStart;
        $this->end = $programEnd;
        $this->alias = $alias;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'dcmh_alt_'.$this->alias;
    }

    public function getDefaultReportName()
    {
        return 'Complete eLearning '.$this->alias;
    }

    public function getStatus(User $user)
    {
        $screeningView = new CompleteScreeningComplianceView($this->start, $this->end);
        $screeningView->setComplianceViewGroup($this->getComplianceViewGroup());

        if($screeningView->getStatus($user)->isCompliant()) {
            $elearningView = new CompleteELearningGroupSet('2011-03-01', '2012-03-31', $this->alias);
            $elearningView->setComplianceViewGroup($this->getComplianceViewGroup());

            if($elearningView->getStatus($user)->isCompliant()) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
            }
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
    }

    protected $alias;
    protected $start;
    protected $end;
}

class StatewideBenefitCoop2012ComplianceStatusPointMapper extends ComplianceStatusPointMapper
{
    public function setUser(User $user)
    {
        $this->user = $user;

        if($this->user->getRelationshipType() == Relationship::EMPLOYEE || !$this->employeeOnly) {
            $baseMoney = 5;
        } else {
            // If spouse and employee only view, no money possible
            $baseMoney = 0;
        }

        parent::__construct($baseMoney, 0, 0, 0);
    }

    public function setEmployeeOnly($boolean)
    {
        $this->employeeOnly = $boolean;
    }

    private $user = null;
    private $employeeOnly = false;
}

class StatewideBenefitCoop2012RequiredBodyFatBMIComplianceView extends ComplyWithBodyFatBMIScreeningTestComplianceView
{
    /**
     * Hacking out the name to new lines
     */
    public function getStatusSummary($status)
    {
        $string = parent::getStatusSummary($status);

        return preg_replace('[,]', '<br/>', preg_replace('([A-Za-z:/])', '', ",{$string}"));
    }

    /**
     * Hacking out the name to new lines
     */
    public function getReportName($forHTML = false)
    {
        $string = parent::getReportName();

        return preg_replace('[/]', '<br/>', "/{$string}").' **';
    }
}

class StatewideBenefitCoop2012RequiredCholesterolComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getDefaultName()
    {
        return 'employee_required_cholesterol';
    }

    public function getDefaultReportName()
    {
        return 'TC/HDL Ratio & LDL Cholesterol';
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceStatus::COMPLIANT ? '≤5.2 for Ratio AND ≤135 for LDL' : null;
    }

    public function getStatus(User $user)
    {
        // Evaluate LDL, HDL, Total
        // Compliant in all 3 => compliant
        // NA in all of 3 => NA
        // Else red

        $ratioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($this->startDate, $this->endDate);
        $ratioView->setComplianceViewGroup($this->getComplianceViewGroup());
        $ratioView->overrideTestRowData(0, null, 5.2, null);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($this->startDate, $this->endDate);
        $ldlView->setComplianceViewGroup($this->getComplianceViewGroup());
        $ldlView->overrideTestRowData(0, null, 135, null);

        $statusCounts = array_count_values(
            array(
                $ratioView->getStatus($user)->getStatus(),
                $ldlView->getStatus($user)->getStatus()
            )
        );

        if(isset($statusCounts[ComplianceStatus::COMPLIANT]) && $statusCounts[ComplianceStatus::COMPLIANT] == 2) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else if(isset($statusCounts[ComplianceStatus::NA_COMPLIANT]) && $statusCounts[ComplianceStatus::NA_COMPLIANT] == 2) {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }

        return $parentStatus;
    }

    private $startDate;
    private $endDate;
}

class StatewideBenefitCoop2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new StatewideBenefitCoop2012Printer();
        $printer->links = 'Reasonable Alternatives';
        $printer->points = '$ Amount Incentive Earned';
        $printer->max_points = 'Max $ Amount Available';
        $printer->format_points_as_money = true;

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        if(!Piranha::getInstance()->getUser()->hasAnyAttributes(Attribute::CLIENT_ADMIN_USER | Attribute::VIEW_PHI)) {
            // If the session user doesn't have PHI or client admin,
            // Then we will limit to total points only, and turn off relationship names
            $printer->setShowComment(false, false, false);
            $printer->setShowPoints(true, false, true);
            $printer->setShowCompliant(false, false, false);
            $printer->setShowStatus(false, false, false);
            $printer->setShowShowRelatedUserFields(false, false);
        }

        return $printer;
    }

    public function getStatus()
    {
        $this->statusPointMapper->setUser($this->getActiveUser());

        return parent::getStatus();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->statusPointMapper = new DCMHComplianceStatusPointMapper();

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');
        $preventionEventGroup->setPointsRequiredForCompliance(0);

        $hraScreeningView = new CompleteHRAAndScreeningAndPrivateConsultationComplianceView($programStart, $programEnd);
        $hraScreeningView->setReportName('Screening Program and Private Consultation');
        $hraScreeningView->setComplianceStatusPointMapper($this->statusPointMapper);
        $preventionEventGroup->addComplianceView($hraScreeningView);

        $this->addComplianceViewGroup($preventionEventGroup);


        $resourceGroup = new ComplianceViewGroup('Resource');
        $resourceGroup->setPointsRequiredForCompliance(0);

        $physicalView = new CompletePreventionPhysicalExamComplianceView('2011-03-31', '2012-03-31');
        $physicalView->setComplianceStatusPointMapper($this->statusPointMapper);
        $physicalView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete physical exam & share your screening results w/Family Physician');
        $resourceGroup->addComplianceView($physicalView);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setComplianceStatusPointMapper($this->statusPointMapper);
        $smokingView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=smoking_2011'));
        $smokingView->setAlternativeComplianceView(new StatewideBenefitCoop2012LearningAlternativeComplianceView($programStart, $programEnd, 'smoking_2011'));
        $resourceGroup->addComplianceView($smokingView);


        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper($this->statusPointMapper);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 84, 100);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 134, 140);
        $bloodPressureView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=blood_pressure_2011'));
        $bloodPressureView->setAlternativeComplianceView(new StatewideBenefitCoop2012LearningAlternativeComplianceView($programStart, $programEnd, 'blood_pressure_2011'));
        $resourceGroup->addComplianceView($bloodPressureView);

        $cholesterolView = new StatewideBenefitCoop2012RequiredCholesterolComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper($this->statusPointMapper);
        $cholesterolView->setAlternativeComplianceView(new StatewideBenefitCoop2012LearningAlternativeComplianceView($programStart, $programEnd, 'cholesterol_2011'));

        $cholesterolView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=cholesterol_2011'));
        $resourceGroup->addComplianceView($cholesterolView);

        $bmiBodyFatView = new StatewideBenefitCoop2012RequiredBodyFatBMIComplianceView($programStart, $programEnd);
        $bmiBodyFatView->setAlternativeComplianceView(new StatewideBenefitCoop2012LearningAlternativeComplianceView($programStart, $programEnd, 'weight_management_2011'));
        $bmiBodyFatView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=weight_management_2011'));
        $bmiBodyFatView->overrideBMITestRowData(0, 0, 27.4, 30);
        $bmiBodyFatView->overrideBodyFatTestRowData(0, 0, 27.4, 30, 'F');
        $bmiBodyFatView->overrideBodyFatTestRowData(0, 0, 19.7, 30, 'M');
        $bmiBodyFatView->setComplianceStatusPointMapper($this->statusPointMapper);
        $resourceGroup->addComplianceView($bmiBodyFatView);

        $this->addComplianceViewGroup($resourceGroup);

    }

    private $statusPointMapper;
}

class StatewideBenefitCoop2012Printer extends CHPComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $user = Piranha::getInstance()->getUser();
        ?>
    <style type="text/css">
        #overviewCriteria {
            width:100%;
            border-collapse:collapse;
        }

        #overviewCriteria th {
            background-color:#42669A;
            color:#FFFFFF;
            font-weight:normal;
            font-size:11pt;
            padding:5px;
        }

        #overviewCriteria td {
            width:33.3%;
            vertical-align:top;
        }
    </style>
    <!--<p style="color:red; font-weight:bold;">This is the NEW report card for this program year.<br /><br />
   To view the program that just ended,  <a href="compliance_programs/index?id=4">Click Here</a>.</p>-->


    <p>Hello <?php echo $user->getFullName() ?>,</p>
    <p><span style="color:red; font-weight:bold;">This is your archived copy of the 2011 Incentive Program.</span></p>

    <p>Welcome to The DCMH Wellness Website! This site was developed not only
        to track your wellness requirements, but also to be used as a great
        resource for health related topics and questions. We encourage you to
        explore the site while also fulfilling your requirements. By completing
        the following steps in 2011/2012 discount beginning 12/26/2011;
        Screenings completed by 9/24/11; consultations will be scheduled
        3-4 weeks following the screenings in the fall of 2011. If you do not
        complete the following steps and meet the requirements, you will not
        receive the premium discount.</p>

    <p>NEW FOR 2011- EMPLOYEES NOT ON THE MEDICAL PLAN
        CAN PARTICIPATE IN THE WELLNESS PROGRAM. SEE AMY WICKENS
        IN HR FOR INCENTIVE DETAILS!</p>

    <p><strong>Step 1</strong>- Complete your on-site health screenings by
        9/24/11 (requirement for all employees and only spouses on the medical plan).</p>

    <p><strong>Step 2</strong>- Complete your on-site or telephone consultation.
        Consultations will conclude on 11/2/2011 (requirement for all employees and
        only spouses on the medical plan).</p>

    <p><strong>Step 3</strong>- Meet the requirements for the recommended ranges for your screening
        tests to receive an additional per pay rate reduction (dollar amounts are listed below in the
        report card). The criteria for meeting these ranges is listed below in your report card.
        If you are unable to reach one or more of the requirements listed in your report card,
        you can complete the e-learning lessons designated for your risk factor(s) below in the
        “Alternative” link(s)(these requirements are for all employees and only spouses on the medical plan).</p>

    <p>An annual physical exam will be automatically entered into your record
        from information received from your health plan. Updates are scheduled
        each quarter for claims that are paid through the previous quarter.
        Example: If your appointment was on February 9th and the claim was paid
        on March 20th, the claim will be updated on the website in April or May.
        OR you can complete a
        <a href="/resources/3321/DCMHVerification_Form_2011_2012.pdf">verification form</a>
        indicating that you had your physical completed. <span style="color:red">NON-INSURED EMPLOYEE
      PARTICIPANTS WILL NEED TO COMPLETE A VERIFICATION FORM FOR PHYSICALS.</span> Physical exams need to
        be completed between March 31, 2011 and March 31, 2012.</p>

    <p>Are you pregnant or 6 months postpartum? If so,
        <a href="/resources/3319/DCMH_Pregnancy_exception2011_2012.pdf">click here </a>
        for a pregnancy exception form for the body measurements.</p>

    <p><span style="color:red">YOU MUST COMPLETE ALL OF THE e-LEARNING LESSONS LISTED
      FOR THE FACTOR(S) IN WHICH YOU HAVE NOT MET THE REQUIREMENT(S)
      BY 3/31/2012. BY COMPLETING ALL LESSONS YOUR RED LIGHT WILL CHANGE TO A</span>
        <span style="color:green"></span><span style="color:red">LIGHT IN YOUR REPORT CARD</span>


    <p>The current requirements and your current status for each are summarized
        below.</p>

    <table id="overviewCriteria">
        <thead>
            <tr>
                <th>Overview Criteria</th>
                <th>Incentive if done:</th>
                <th>If not done:</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
            <span>Complete the Wellness Screening, consultation, and earn a per
              rate pay reduction.</span>
                </td>
                <td>
            <span>There will be a premium reduction available for the 2012 plan
              year.</span>
                </td>
                <td>
            <span>You will be not be eligible for a premium reduction for the
              2012 plan year.</span>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    }

    public function printClientNote()
    {
        ?>
    <p style="text-align: left; margin-left: 110px; font-size: 8pt;">
        * Per pay insurance premiums are subject to the listed reductions for
        fulfilling the incentives by March 31, 2012.</p>
    <p style="text-align: left; margin-left: 110px; font-size: 8pt;">
        ** Achieve required results for a minimum 1 of the 2 Body Measurements</p>
    <?php
    }
}