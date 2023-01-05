<?php

class Wilhelm2012LearningAlternativeComplianceView extends ComplianceView
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
        return 'wilhelm_alt_'.$this->alias;
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
            $elearningView = new CompleteELearningGroupSet('2010-08-01', '2011-03-30', $this->alias);
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

class Wilhelm2012ComplianceStatusPointMapper extends ComplianceStatusPointMapper
{
    public function setUser(User $user)
    {
        $this->user = $user;

        if($this->user->getRelationshipType() == Relationship::EMPLOYEE) {
            $baseMoney = 5;
        } else if(!$this->employeeOnly) {
            $baseMoney = 2.5;
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

class Wilhem2012RequiredBodyFatBMIWaistRatioComplianceView extends ComplyWithBodyFatBMIWaistRatioScreeningTestComplianceView
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

class Wilhem2012RequiredCholesterolComplianceView extends ComplianceView
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
        return 'Cholesterol';
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceStatus::COMPLIANT ? 'Be compliant in HDL, LDL, and Total Cholesterol' : null;
    }

    public function getStatus(User $user)
    {
        // Evaluate LDL, HDL, Total
        // Compliant in all 3 => compliant
        // NA in all of 3 => NA
        // Else red

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($this->startDate, $this->endDate);
        $ldlView->setComplianceViewGroup($this->getComplianceViewGroup());
        $ldlView->overrideTestRowData(0, null, 142, null);


        $hdlView = new ComplyWithHDLScreeningTestComplianceView($this->startDate, $this->endDate);
        $hdlView->setComplianceViewGroup($this->getComplianceViewGroup());
        $hdlView->overrideTestRowData(0, 36, null, null);


        $totalView = new ComplyWithTotalCholesterolScreeningTestComplianceView($this->startDate, $this->endDate);
        $totalView->setComplianceViewGroup($this->getComplianceViewGroup());
        $totalView->overrideTestRowData(0, 0, 219, 240);


        $statusCounts = array_count_values(
            array(
                $ldlView->getStatus($user)->getStatus(),
                $hdlView->getStatus($user)->getStatus(),
                $totalView->getStatus($user)->getStatus()
            )
        );

        if(isset($statusCounts[ComplianceStatus::COMPLIANT]) && $statusCounts[ComplianceStatus::COMPLIANT] == 3) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else if(isset($statusCounts[ComplianceStatus::NA_COMPLIANT]) && $statusCounts[ComplianceStatus::NA_COMPLIANT] == 3) {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }
    }

    private $startDate;
    private $endDate;
}

class Wilhelm2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new CHPComplianceProgramReportPrinter();
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

        $this->statusPointMapper = new WilhelmComplianceStatusPointMapper();

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

        $physicalView = new CompletePreventionPhysicalExamComplianceView($programStart, $programEnd);
        $physicalView->setComplianceStatusPointMapper($this->statusPointMapper);
        $physicalView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete physical exam & share your screening results w/Family Physician');
        $resourceGroup->addComplianceView($physicalView);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setComplianceStatusPointMapper($this->statusPointMapper);
        $smokingView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_smoking'));
        $smokingView->setAlternativeComplianceView(new WilhelmLearningAlternativeComplianceView($programStart, $programEnd, 'required_smoking'));
        $resourceGroup->addComplianceView($smokingView);


        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper($this->statusPointMapper);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 84, 100);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 134, 140);
        $bloodPressureView->setUseHraFallback(true);
        $bloodPressureView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_blood_pressure'));
        $bloodPressureView->setAlternativeComplianceView(new WilhelmLearningAlternativeComplianceView($programStart, $programEnd, 'required_blood_pressure'));

        $resourceGroup->addComplianceView($bloodPressureView);

        $cholesterolView = new Wilhem2012RequiredCholesterolComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper($this->statusPointMapper);
        $cholesterolView->setAlternativeComplianceView(new WilhelmLearningAlternativeComplianceView($programStart, $programEnd, 'required_cholesterol'));
        $cholesterolView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_cholesterol'));
        $resourceGroup->addComplianceView($cholesterolView);

        $bmiBodyFatWaistRatioView = new Wilhem2012RequiredBodyFatBMIWaistRatioComplianceView($programStart, $programEnd);
        $bmiBodyFatWaistRatioView->setAlternativeComplianceView(new WilhelmLearningAlternativeComplianceView($programStart, $programEnd, 'required_weight_management'));
        $bmiBodyFatWaistRatioView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_weight_management'));
        $bmiBodyFatWaistRatioView->overrideBMITestRowData(0, 0, 27.4, 30);
        $bmiBodyFatWaistRatioView->overrideBodyFatTestRowData(0, 0, 27.4, 30, 'F');
        $bmiBodyFatWaistRatioView->overrideBodyFatTestRowData(0, 0, 19.7, 30, 'M');
        $bmiBodyFatWaistRatioView->setUseHraFallback(true);
        $bmiBodyFatWaistRatioView->setComplianceStatusPointMapper($this->statusPointMapper);

        $resourceGroup->addComplianceView($bmiBodyFatWaistRatioView);

        $this->addComplianceViewGroup($resourceGroup);

    }

    private $statusPointMapper;
}