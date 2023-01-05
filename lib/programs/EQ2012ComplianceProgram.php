<?php

class EQ2012LearningAlternativeComplianceView extends ComplianceView
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
            $elearningView = new CompleteELearningGroupSet($this->start, $this->end, $this->alias);
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

class EQ2012ComplianceStatusPointMapper extends ComplianceStatusPointMapper
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

class EQ2012RequiredBodyFatBMIComplianceView extends ComplyWithBodyFatBMIScreeningTestComplianceView
{
    /**
     * Hacking out the name to new lines
     */
    public function getStatusSummary($status)
    {
        $string = parent::getStatusSummary($status);

        return $string === null ?
            null : preg_replace('[,]', '<br/>', $string);
    }
}

class EQ2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new EQ2012Printer();
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

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event Criteria');

        $hraScreeningView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScreeningView->setReportName('Screening Program & HRA');
        $hraScreeningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete Screening Program & HPA');
        $preventionEventGroup->addComplianceView($hraScreeningView);

        $consultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $consultationView->setReportName('Private Consultation');
        $consultationView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete Private Consultation');
        $preventionEventGroup->addComplianceView($consultationView);

        $this->addComplianceViewGroup($preventionEventGroup);

        $resourceGroup = new ComplianceViewGroup('Additional Wellness Criteria');
        $resourceGroup->setPointsRequiredForCompliance(0);


        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco Status');
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $smokingView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Non-Tobacco User');
        $smokingView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $smokingView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $smokingView->addLink(new Link('Alternative', '/resources/3786/quit_the_nic.pdf'));
        $smokingView->setAlternativeComplianceView(new EQ2012LearningAlternativeComplianceView($programStart, $programEnd, 'smoking_2011'));
        $resourceGroup->addComplianceView($smokingView);

        $bmiBodyFatView = new EQ2012RequiredBodyFatBMIComplianceView($programStart, $programEnd);
        $bmiBodyFatView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $bmiBodyFatView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bmiBodyFatView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $bmiBodyFatView->setReportName('Body Measures **');
        $bmiBodyFatView->setUseHraFallback(true);
        $bmiBodyFatView->setStatusSummary(ComplianceStatus::COMPLIANT, '&#8804;27.5 Body Fat %, &#8804;27.5 BMI, 0.9 Hip to Waist Ratio');
        $bmiBodyFatView->setAlternativeComplianceView(new EQ2012LearningAlternativeComplianceView($programStart, $programEnd, 'weight_management_2011'));
        $bmiBodyFatView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=weight_management_2011'));
        $bmiBodyFatView->overrideBMITestRowData(0, 0, 27.4, 30);
        $bmiBodyFatView->overrideBodyFatTestRowData(0, 0, 27.4, 30, 'F');
        $bmiBodyFatView->overrideBodyFatTestRowData(0, 0, 19.7, 30, 'M');

        $resourceGroup->addComplianceView($bmiBodyFatView);

        $tc = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $tc->setReportName('TC/HDL Ratio');
        $tc->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $tc->setStatusSummary(ComplianceStatus::COMPLIANT, '&#8804;5.2 Ratio');
        $tc->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $tc->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $tc->setAlternativeComplianceView(new EQ2012LearningAlternativeComplianceView($programStart, $programEnd, 'cholesterol_ratio'));
        $tc->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=cholesterol_ratio'));

        $resourceGroup->addComplianceView($tc);


        $healthPlan = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $healthPlan->setReportName('Consumer Driven Health Plan (CDHP)');
        $healthPlan->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $healthPlan->setStatusSummary(ComplianceStatus::COMPLIANT, 'Enrollment');
        $resourceGroup->addComplianceView($healthPlan);

        $reqView = new CompleteRequiredELearningLessonsComplianceView($programStart, $programEnd);
        $reqView->setReportName('Health Education (Pre-selected lessons)');
        $reqView->setMaximumNumberOfPoints(1);
        $reqView->setNumberRequired(2);
        $reqView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $reqView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete 2 pre-selected e-Learning lessons');
        $reqView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $reqView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $resourceGroup->addComplianceView($reqView);

        $additionalView = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd);
        $additionalView->setReportName('Health Education (Lessons of your choice)');
        $additionalView->setMaximumNumberOfPoints(1);
        $additionalView->setNumberRequired(2);
        $additionalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $additionalView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete any other 2 e-Learning lessons of your choice');
        $additionalView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $additionalView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $resourceGroup->addComplianceView($additionalView);


        $this->addComplianceViewGroup($resourceGroup);

    }
}

class EQ2012Printer extends CHPStatusBasedComplianceProgramReportPrinter
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

    <p>Hello <?php echo $user->getFullName() ?>,</p>

    <p>Welcome to The Environmental Quality Company Wellness Website! </p>
    <p>Use this site to track your wellness requirements. It’s also a great resource for health related topics and
        questions.</p>
    <p>Complete the following steps in 2012 to receive your incentive.</p>
    <table width="100%" border="1" bordercolor="#999999" cellpadding="5" style="border-collapse:collapse">
        <tr>
            <td width="7%" align="center"><strong style="color:#039; font-size:1.2em">Step 1</strong></td>
            <td width="93%">Complete your on-site health screening and HRA by 07/20/2012 OR on-demand screening and HRA
                on-line by 07/27/2012.<br/>
                <span style="font-size:.8em"></span></td>
        </tr>
        <tr>
            <td align="center"><strong style="color:#039; font-size:1.2em">Step 2</strong></td>
            <td>Complete your on-site or telephone consultation.<br/>
                Consultations will conclude on 09/01/2012.<br/>
                <span style="font-size:.8em"></span></td>
        </tr>
        <tr>
            <td align="center"><strong style="color:#039; font-size:1.2em">Step 3</strong></td>
            <td>Meet the recommended range requirements for your screening tests <strong>-OR-</strong> satisfy the
                alternative standard to receive an additional premium reduction* (based on the points earned and tracked
                on your report card).
            </td>
        </tr>
    </table>
    <p style="font-size:.8em">*These requirements are for all employees on the medical plan who want to receive the Star
        2 or Star 3 level premium differentials.<br/>
        <br/>
    </p>
    <p><strong style="color:#039; font-size:1.2em">Incentives</strong></p>
    <table width="100%" border="1" bordercolor="#FFF" cellpadding="5" style="border-collapse:collapse">
        <tr>
            <td width="7%" align="center" bgcolor="#003399"><strong style="color:#FFF">Level</strong></td>
            <td width="46%" align="center" bgcolor="#003399"><strong style="color:#FFF">What You Need To Do</strong>
            </td>
            <td width="47%" align="center" bgcolor="#003399"><strong style="color:#FFF">What You Get</strong></td>
        </tr>
        <tr>
            <td align="center" bgcolor="#CCCCCC"><strong>1 Star</strong></td>
            <td bgcolor="#CCCCCC"><p>Complete the health screening, HRA questionnaire and private consultation</p></td>
            <td bgcolor="#CCCCCC">$150 cash paid in 2012</td>
        </tr>
        <tr>
            <td align="center" bgcolor="#EEEEEE"><strong>2 Star</strong></td>
            <td bgcolor="#EEEEEE">Complete requirements for 1 Star Level requirements AND earn 10-13 points</td>
            <td bgcolor="#EEEEEE">$150 cash paid in 2012 AND $300/year premium reduction on your 2013 health care costs
                shares.
            </td>
        </tr>
        <tr>
            <td align="center" bgcolor="#D6D6D6"><strong>3 Star</strong></td>
            <td bgcolor="#D6D6D6">Complete 1 Star Level requirements AND earn 14 or more points</td>
            <td bgcolor="#D6D6D6">$150 cash paid in 2012 AND $900/year premium reduction on your 2013 health care costs
                shares.
            </td>
        </tr>
    </table>
    <p><strong>Earn wellness points by satisfying wellness criteria in up to five areas: </strong></p>
    <ul>
        <li><u>Tobacco Status</u>:  Maintain non-tobacco user status <strong>-OR-</strong> participate in <em>Quit the
            Nic</em> (offered through Blue Cross Blue Shield, Blue Health Connection at 1-800-775-2583) or other
            approved tobacco cessation program before December 1, 2012. You will need to continue all programs through
            completion and must submit verification to the Corporate Human Resources Department to receive credit. If
            you meet the EQ Quit the Nic program requirements, your incentive report card (below) will be updated with
            10 points at the end of November 2012. See your benefits guide or contact Corporate Human Resources
            Department for more details.(10 points)
        </li>
        <li><u>Weight Management</u>:  Achieve the recommended range for two of the three body composition measurements
            (includes BMI, Body Fat % or Hip to Waist Ratio) during the 2012
            health screening <strong>-OR-</strong> complete the designated Circle Health E-learning lesson by September
            1, 2012. (2 points)
        </li>
        <li><u>Cholesterol Management</u>:  Achieve the recommended range for Total/HDL Cholesterol Ratio during the
            2012 health screening <strong>-OR-</strong>
            complete the designated Circle Health e-Learning lesson by September 1, 2012. (2 points)
        </li>
        <li><u>Consumer Driven Health Plan (CDHP)</u>:  Enrolled in CDHP in 2012. (2 points)</li>
        <li><u>Health Education</u>:  Earn one point for completing “Living Healthy” and “How To Prevent Back Pain”
            e-Learning Lessons by September 1, 2012 via
            the Circle Wellness Website.  Earn one point for completing two e-Learning Lessons of your choice by
            September 1, 2012.
            You must complete the short quizzes at the end of each module to receive credit. (1 - 2 points)
        </li>
    </ul>
    <p>When you satisfy the criteria, your red light will change to a green light in your report card.<br/>
        <strong>Total Possible Points = 18</strong></p>
    <p>If it is unreasonably difficult due to a medical condition for you to satisfy one or more of the health standard
        wellness criteria under this program (tobacco status, weight management, or cholesterol management) or if it is
        medically in advisable for you to attempt to achieve these health standard wellness criteria for the reward, the
        alternative standards described above are provided to help you reach reward status.  If you have questions
        regarding the above alternative standards, please call Circle Health.</p>
    <p><strong>Pregnant?  </strong>Are you pregnant or 6 months postpartum? If so, <a
        href="/resources/3539/eqPregnancyException2012.doc">click here </a>for a pregnancy exception form for the body
        measurements.<strong></strong></p>
    <p style="color:red; font-weight:bold;">*YOU MUST COMPLETE BOTH THE HEALTH SCREENING AND CONSULTATION (REGARDLESS OF
        POINTS EARNED) IN ORDER TO RECEIVE A REDUCTION IN YOUR 2013 COST SHARES.*
    </p>



    <p>The current requirements and your current status for each are summarized
        below.</p>

    <!-- <table id="overviewCriteria">
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
     </table> -->
    <?php
    }

    public function printClientNote()
    {
        ?>
    <p style="text-align: left; margin-left: 80px; font-size: 8pt;">
    </p>
    <p style="text-align: left; margin-left: 80px; font-size: 8pt;">
        ** Achieve required results for a minimum 2 of the 3 Body Measurements (includes BMI, Body Fat % and Hip to
        Waist Ratio)</p>
    <?php
    }
}