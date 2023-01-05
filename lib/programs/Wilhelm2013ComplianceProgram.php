<?php

use hpn\common\text\Escaper;

class Wilhelm2013LearningAlternativeComplianceView extends ComplianceView
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
            $elearningView = new CompleteELearningGroupSet('2011-08-01', '2012-03-30', $this->alias);
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

class Wilhelm2013ComplianceStatusPointMapper extends ComplianceStatusPointMapper
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

class Wilhem2013RequiredBodyFatBMIWaistRatioComplianceView extends ComplyWithBodyFatBMIWaistRatioScreeningTestComplianceView
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

class Wilhem2013RequiredCholesterolComplianceView extends ComplianceView
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
        return $status == ComplianceStatus::COMPLIANT ? '<-5.0' : null;
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

class Wilhelm2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Wilhelm2013CHPComplianceProgramReportPrinter();
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
        $preventionEventGroup = new ComplianceViewGroup('Overview Criteria');
//        $preventionEventGroup->setPointsRequiredForCompliance(0);
        
        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Risk Assessment (HRA)');
        $preventionEventGroup->addComplianceView($hraView);
        
        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Screening');
        $preventionEventGroup->addComplianceView($screeningView);
        
        $consultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $consultationView->setReportName('Private Consultation');
        $preventionEventGroup->addComplianceView($consultationView);        
        
        $this->addComplianceViewGroup($preventionEventGroup);


        $resourceGroup = new ComplianceViewGroup('Resource');
//        $resourceGroup->setPointsRequiredForCompliance(0);

        //$physicalView = new CompletePreventionPhysicalExamComplianceView($programStart, $programEnd);
        //$physicalView->setComplianceStatusPointMapper($this->statusPointMapper);
        //$physicalView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete physical exam & share your screening results w/Family Physician');
        //$resourceGroup->addComplianceView($physicalView);

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

        $cholesterolView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper($this->statusPointMapper);
        $cholesterolView->setReportName('Total Cholesterol/HDL Ratio');
        $cholesterolView->setAlternativeComplianceView(new WilhelmLearningAlternativeComplianceView($programStart, $programEnd, 'required_cholesterol'));
        $cholesterolView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_cholesterol'));
        $resourceGroup->addComplianceView($cholesterolView);

        $bmiBodyFatWaistRatioView = new Wilhem2013RequiredBodyFatBMIWaistRatioComplianceView($programStart, $programEnd);
        $bmiBodyFatWaistRatioView->setAlternativeComplianceView(new WilhelmLearningAlternativeComplianceView($programStart, $programEnd, 'required_weight_management'));
        $bmiBodyFatWaistRatioView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_weight_management'));
        $bmiBodyFatWaistRatioView->overrideBMITestRowData(0, 0, 27.4, 30);
        $bmiBodyFatWaistRatioView->overrideBodyFatTestRowData(0, 0, 27.4, 30, 'F');
        $bmiBodyFatWaistRatioView->overrideBodyFatTestRowData(0, 0, 19.7, 30, 'M');
        $bmiBodyFatWaistRatioView->setUseHraFallback(true);
        $bmiBodyFatWaistRatioView->setReportName('Better of Body Fat and BMI');
        $bmiBodyFatWaistRatioView->setComplianceStatusPointMapper($this->statusPointMapper);

        $resourceGroup->addComplianceView($bmiBodyFatWaistRatioView);

        $this->addComplianceViewGroup($resourceGroup);

    }

    private $statusPointMapper;
}

class Wilhelm2013CHPComplianceProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        global $_user;

        $escaper = new Escaper();

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
        
        <script type="text/javascript">
            $(function() {
                $('.phipTable tbody').children(':eq(8)').children(':eq(0)').html('<br />4. Better of Body Fat and BMI');
            });
        </script>


        <p>Hello <?php echo $escaper->escapeHtml($_user->getFullName()) ?>,</p>

         <p>Welcome to The F.A. Wilhelm Wellness Website! This site was developed not only to track your wellness requirements,
            but also to be used as a great resource for health related topics and questions. We encourage you to explore the
            site while also fulfilling your requirements. By completing the following steps in xxxx, you will receive a discount beginning
            xx/xx/xxxx; Screenings will take place in August and consultations will be scheduled 5-6 weeks following the screenings. If you do not complete the following steps and meet the requirements, you will not receive the
            premium discount.</p>

        <p><strong>Step 1</strong>- Complete your on-site health screenings and Health Questionnaire (HQ) by xx/xx/xxxx (requirement for both, employee and
            spouse).</p>

        <p><strong>Step 2</strong>- Complete your on-site or telephone consultation. Consultations will conclude on xx/xx/xxxx
            (requirement for both, employee and spouse).</p>

        <p><strong>Step 3</strong>- Meet the requirements for the recommended ranges for your screening tests to receive an
            additional per pay rate reduction (dollar amounts are listed below in the report card). The criteria for meeting
            these ranges is listed below in your report card. If you have a medical reason for not being able to reach one or
            more of the requirements listed below, you will be provided with a reasonable alternative to manage your risk
            factor(s) (these requirements are for the employee and spouse).</p>

        <p>Are you pregnant or 6 months postpartum? If so, <a href="/downloads/F.A. Wilhelm/pregnancy.exception.form.2010.pdf">click
                here </a>for a pregnancy exception form for the body measurements.</p>

        <p><font color=red>YOU MUST COMPLETE ALL OF THE e-LEARNING LESSONS LISTED FOR THE FACTOR(S) IN WHICH YOU HAVE NOT MET THE REQUIREMENT(S) BY xx/xx/xxxx. BY COMPLETING ALL LESSONS YOUR RED LIGHT WILL CHANGE TO A</font> <font color=green>GREEN</font>
            <font color=red>LIGHT IN YOUR REPORT CARD. LINKS FOR THE "ALTERNATIVE" ARE FOUND BELOW IN THE REPORT CARD.</font></p>


        <p>The current requirements and your current status for each are summarized below.</p>



        <!--<table id="overviewCriteria">
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
                        <span>Complete the Wellness Screening, consultation, and earn a per rate pay reduction.</span>
                    </td>
                    <td>
                        <span>There will be a premium reduction available for the xxxx plan year.</span>
                    </td>
                    <td>
                        <span>You will be not be eligible for a premium reduction for the xxxx plan year.</span>
                    </td>
                </tr>
            </tbody>
        </table>-->
        <?php
    }
}