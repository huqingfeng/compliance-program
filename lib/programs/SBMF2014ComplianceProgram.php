<?php

class SBMF2014BloodDonationComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getStatus(User $user)
    {
        $number = 0;
        $comment = '';
        foreach($user->getDataRecords('sbmf_blood_donations') as $udr) {
            $date = strtotime($udr->getDataFieldValue('date'));
            if($date >= $this->getStartDate() && $date <= $this->getEndDate()) {
                $number++;
                $comment .= date('m/d/Y', $date).'<br/>';
            }
        }

        $status = $number >= 4 ?
            ComplianceStatus::COMPLIANT : (
            $number >= 2 ?
                ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT
            );

        return new ComplianceViewStatus($this, $status, null, $comment);
    }

    public function getDefaultStatusSummary($constant)
    {
        if($constant == ComplianceStatus::COMPLIANT) {
            return 'Four donations.';
        } else if($constant == ComplianceStatus::PARTIALLY_COMPLIANT) {
            return 'Two donations.';
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return 'blood_donation';
    }

    public function getDefaultReportName()
    {
        return 'Blood Donation';
    }
}

class SBMF2014BMIMaintainComplianceView extends DateBasedComplianceView
{
    public function __construct($start, $end)
    {
        $this->setStartDate($start);
        $this->setEndDate($end);
    }

    public function getStatus(User $user)
    {
        $oldBMIView = new ComplyWithBMIScreeningTestComplianceView(strtotime('-1 year', $this->getStartDate()), strtotime('-1 year', $this->getEndDate()));
        $oldBMIView->setComplianceViewGroup($this->getComplianceViewGroup());

        $oldBMIView->setUseHraFallback(true);
        $newBMIView = new ComplyWithBMIScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $newBMIView->setComplianceViewGroup($this->getComplianceViewGroup());

        $newBMIView->setUseHraFallback(true);

        $oldStatus = $oldBMIView->getStatus($user);
        $newStatus = $newBMIView->getStatus($user);

        $oldValue = $oldStatus->getComment();
        $newValue = $newStatus->getComment();

        $status = ComplianceStatus::NOT_COMPLIANT;
        $comment = null;

        if(is_numeric($oldValue) && is_numeric($newValue)) {
            $difference = $oldValue - $newValue;

            if($difference >= 0 && $difference < 1) {
                $status = ComplianceStatus::COMPLIANT;
                $comment = 'Maintained';
            }
        }

        return new ComplianceViewStatus($this, $status, null, $comment);
    }

    public function getDefaultStatusSummary($constant)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'bmi_maintain';
    }

    public function getDefaultReportName()
    {
        return 'BMI Maintain';
    }
}

class SBMF2014BMIReductionBonusComplianceView extends DateBasedComplianceView
{
    public function __construct($start, $end)
    {
        $this->setStartDate($start);
        $this->setEndDate($end);

        $this->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(3, 2, 0, 0));
    }

    public function getStatus(User $user)
    {
        $oldBMIView = new ComplyWithBMIScreeningTestComplianceView(strtotime('-1 year', $this->getStartDate()), strtotime('-1 year', $this->getEndDate()));
        $oldBMIView->setComplianceViewGroup($this->getComplianceViewGroup());

        $newBMIView = new ComplyWithBMIScreeningTestComplianceView($this->getStartDate(), $this->getEndDate());
        $newBMIView->setComplianceViewGroup($this->getComplianceViewGroup());


        $oldStatus = $oldBMIView->getStatus($user);
        $newStatus = $newBMIView->getStatus($user);

        $oldValue = $oldStatus->getComment();
        $newValue = $newStatus->getComment();

        if(is_numeric($oldValue) && is_numeric($newValue)) {
            $difference = $oldValue - $newValue;

            $differenceText = abs($difference);

            if($difference >= 2) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, "Decrease by $differenceText");
            } elseif($difference >= 1) {
                return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, null, "Decrease by $differenceText");
            } elseif($difference >= 0) {
                return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, "Decrease by $differenceText");
            } elseif($difference === 0) {
                return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, "No Change");
            } else {
                return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, "Increase by $differenceText");
            }
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, null, null);
        }
    }

    public function getDefaultStatusSummary($constant)
    {
        if($constant == ComplianceStatus::COMPLIANT) {
            return 'By 2';
        } else if($constant == ComplianceStatus::PARTIALLY_COMPLIANT) {
            return 'By 1';
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return 'bmi_reduction';
    }

    public function getDefaultReportName()
    {
        return 'BMI Reduction';
    }
}

class SBMF2014LifestyleProgramOne extends PlaceHolderComplianceView
{
}

class SBMF2014LifestyleProgramTwo extends PlaceHolderComplianceView
{
}

class SBMF2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new SBMF2014ComplianceProgramReportPrinter();
        $printer->setPageHeading('My 2014 Incentive Scorecard');
        $printer->showTotalCompliance(true);
        $printer->showResult(true);

        return $printer;
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function  getPointsRequiredForCompliance()
    {
        return 6;
    }

    protected function constructCallback(ComplianceView $view)
    {
        return function (User $user) use ($view) {
            return $view->getMappedStatus($user)->getStatus() == ComplianceStatus::NOT_COMPLIANT;
        };
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');
        $preventionEventGroup->setName('prevention_event');

        $hraScreeningView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScreeningView->setReportName('Screening Program');
        $hraScreeningView->setName('hra_screening');
        $preventionEventGroup->addComplianceView($hraScreeningView);

        //$privateConsultationView = new CompletePrivateConsultationComplianceView('2012-02-01', '2012-12-31');
        //$preventionEventGroup->addComplianceView($privateConsultationView);

        $this->addComplianceViewGroup($preventionEventGroup);


        $smokingStatusGroup = new ComplianceViewGroup('Smoking Status', null);
        $smokingStatusGroup->setPointsRequiredForCompliance(1);
        $smokingStatusGroup->setMaximumNumberOfPoints(2);

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $smokingStatusGroup->addComplianceView($smokingView);

        $freeClear = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $freeClear->setReportName('Quit for Life');
        $freeClear->setName('free_clear');
        $freeClear->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete Requirements');
        $freeClear->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $smokingStatusGroup->addComplianceView($freeClear);

        $this->addComplianceViewGroup($smokingStatusGroup);

        $biometricsGroup = new ComplianceViewGroup('Health Profile Measurements (Biometrics)');
        $biometricsGroup->setPointsRequiredForCompliance(1);
        $biometricsGroup->setMaximumNumberOfPoints(13);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->overrideSystolicTestRowData(null, null, 130, 140);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 90, 90);
        $bloodPressureView->setStatusSummary(ComplianceStatus::COMPLIANT, '??? 130/90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '??? 140/90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);

        $bloodPressureView->setUseHraFallback(true);
        $biometricsGroup->addComplianceView($bloodPressureView);

        $bpLearn = new CompleteELearningGroupSet('2014-05-01', '2014-10-31', 'access_bloodpressure');
        $bpLearn->setReportName('BP eLearning Lessons<br/><span style="font-size: 0.75em;">(Complete only if no points obtained after screening)</span>');
        $bpLearn->setName('elearning_bp');
        $bpLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bpLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $bpLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bpLearn->setAttribute('skip_view_number', true);
        $bpLearn->setEvaluateCallback($this->constructCallback($bloodPressureView));
        current($bpLearn->getLinks())->setLinkText('Access Lessons');
        $biometricsGroup->addComplianceView($bpLearn);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->overrideTestRowData(null, 0, 104, 110);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, '< 105');
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '105 - 110');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);

        $biometricsGroup->addComplianceView($glucoseView);

        $gcLearn = new CompleteELearningGroupSet('2014-05-01', '2014-10-31', 'access_bloodsugar');
        $gcLearn->setReportName('Glucose eLearning Lessons<br/><span style="font-size: 0.75em;">(Complete only if no points obtained after screening)</span>');
        $gcLearn->setName('elearning_gc');
        $gcLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $gcLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $gcLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gcLearn->setAttribute('skip_view_number', true);
        $gcLearn->setEvaluateCallback($this->constructCallback($glucoseView));
        $gcLearn->setAllowPastCompleted(false);
        current($gcLearn->getLinks())->setLinkText('Access Lessons');
        $biometricsGroup->addComplianceView($gcLearn);

        // This cholesterol is hacked to hell, be careful...
        $totalOrRatioView = new ComplyWithTotalCholesterolTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalOrRatioView->setReportName('<br/><center>Better Of<br/>Total Cholesterol<br/><strong>OR</strong><br/>Total/HDL Cholest. Ratio</center>');
        $totalOrRatioView->overrideTotalCholesterolTestRowData(null, null, 180, 190);
        $totalOrRatioView->overrideTotalHDLCholesterolRatioTestRowData(null, 1, 4, 6);
        $totalOrRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $totalOrRatioView->setStatusSummary(
            ComplianceStatus::COMPLIANT,
            'Total Chol: ??? 180<br/>Total/HDL Ratio: 1-4'
        );
        $totalOrRatioView->setStatusSummary(
            ComplianceStatus::PARTIALLY_COMPLIANT,
            'Total Chol: 181-190<br/>Total/HDL Ratio: 4.1-6'
        );

        $biometricsGroup->addComplianceView($totalOrRatioView);

        $tcLearn = new CompleteELearningGroupSet('2014-05-01', '2014-10-31', 'access_cholesterol');
        $tcLearn->setReportName('Cholesterol eLearning Lessons<br/><span style="font-size: 0.75em;">(Complete only if no points obtained after screening)</span>');
        $tcLearn->setName('elearning_tc');
        $tcLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $tcLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $tcLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tcLearn->setAttribute('skip_view_number', true);
        $tcLearn->setEvaluateCallback($this->constructCallback($totalOrRatioView));
        current($tcLearn->getLinks())->setLinkText('Access Lessons');
        $biometricsGroup->addComplianceView($tcLearn);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bmiView->setReportName('BMI');
        $bmiView->setUseHraFallback(true);

//    $bmiView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '25.1 - 29.9');
//    $bodyFatBMIView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $biometricsGroup->addComplianceView($bmiView);

        $bmiReductionView = new SBMF2014BMIReductionBonusComplianceView($programStart, $programEnd);

        $bmiMaintain = new SBMF2014BMIMaintainComplianceView($programStart, $programEnd);
        $bmiMaintain->setReportName('BMI Maintained');
        $bmiMaintain->setName('bmi_maintain');
        $bmiMaintain->setStatusSummary(ComplianceStatus::COMPLIANT, 'BMI Unchanged from 2013');
        $bmiMaintain->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiMaintain->setEvaluateCallback($this->constructCallback($bmiReductionView));
        $biometricsGroup->addComplianceView($bmiMaintain);

        $biometricsGroup->addComplianceView($bmiReductionView);

        $bmiLearn = new CompleteELearningGroupSet('2014-05-01', '2014-10-31', 'access_bmi');
        $bmiLearn->setReportName('BMI eLearning Lessons<br/><span style="font-size: 0.75em;">(Complete only if no points obtained after screening)</span>');
        $bmiLearn->setName('elearning_bmi');
        $bmiLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bmiLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $bmiLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiLearn->setAttribute('skip_view_number', true);
        $bmiLearn->setEvaluateCallback($this->constructCallback($bmiReductionView));
        current($bmiLearn->getLinks())->setLinkText('Access Lessons');

        $biometricsGroup->addComplianceView($bmiLearn);

        $this->addComplianceViewGroup($biometricsGroup);

        $examGroup = new ComplianceViewGroup('physical_exams', 'Physical Exams');
        $examGroup->setPointsRequiredForCompliance(1);

        $physicalView = new CompletePreventionPhysicalExamComplianceView('2013-09-01', '2014-09-01');
        $physicalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $physicalView->addLink(new Link('I did this', '/resources/4909/SBMF-Verification-Form-2014.053014.pdf'));
        $examGroup->addComplianceView($physicalView);

        $this->addComplianceViewGroup($examGroup);
        
        $flushotGroup = new ComplianceViewGroup('annual_flu_shot', 'Annual Flu Shot');
        $flushotGroup->setPointsRequiredForCompliance(1);
        
        $flushotView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $flushotView->setReportName('Flu Shot');
        $flushotView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $flushotView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive Flu Shot');
        $flushotView->emptyLinks();
        $flushotGroup->addComplianceView($flushotView);    
        
        $this->addComplianceViewGroup($flushotGroup);

        /*
        $managementProgramsGroup = new ComplianceViewGroup('lifestyle_management_programs', 'Lifestyle Management Programs');
        $managementProgramsGroup->setPointsRequiredForCompliance(1);

        $completedProgramsView1 = new SBMF2014LifestyleProgramOne();
        $completedProgramsView1->setName('sbmf_lifestyle_one');
        $completedProgramsView1->setReportName('Completed Program #1');
        $completedProgramsView1->setStatusSummary(ComplianceStatus::COMPLIANT, '');
        $completedProgramsView1->setComplianceStatusPointMapper( new ComplianceStatusPointMapper(2, 0, 0, 0) );
        $completedProgramsView1->addLink(new Link('Lifestyle Mgt', '/content/112343'));
        $managementProgramsGroup->addComplianceView($completedProgramsView1);

        $completedProgramsView2 = new SBMF2014LifestyleProgramTwo();
        $completedProgramsView2->setName('sbmf_lifestyle_two');
        $completedProgramsView2->setReportName('Completed Program #2');
        $completedProgramsView2->setStatusSummary(ComplianceStatus::COMPLIANT, '');
        $completedProgramsView2->setComplianceStatusPointMapper( new ComplianceStatusPointMapper(1, 0, 0, 0) );
        $completedProgramsView2->addLink(new Link('Lifestyle Mgt', '/content/112343'));
        $managementProgramsGroup->addComplianceView($completedProgramsView2);

        $this->addComplianceViewGroup($managementProgramsGroup);
        */

        $bloodView = new SBMF2014BloodDonationComplianceView('2013-09-01', '2014-09-01');
        $bloodView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));

        $bloodDonationGroup = new ComplianceViewGroup('volunteer_blood_donations', 'Volunteer Blood Donations');
        $bloodDonationGroup->setPointsRequiredForCompliance(1);
        $bloodDonationGroup->addComplianceView($bloodView);

        $this->addComplianceViewGroup($bloodDonationGroup);

        $additionalLessons = new CompleteAdditionalELearningLessonsComplianceView('2014-04-11', '2014-10-31');
        $additionalLessons->setReportName('Optional eLearning Lessons');
        $additionalLessons->setNumberRequired(4);
        $additionalLessons->setRequiredAlias(null);
        $additionalLessons->addIgnorableGroup('elearning_bp');
        $additionalLessons->addIgnorableGroup('elearning_gc');
        $additionalLessons->addIgnorableGroup('elearning_tc');
        $additionalLessons->addIgnorableGroup('elearning_bmi');
        $additionalLessons->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $additionalLessons->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete 4 lessons for 1 point');
        $additionalLessons->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);


        $group = new ComplianceViewGroup('elearning', 'eLearning Lessons');
        $group->addComplianceView($additionalLessons);
        $group->setPointsRequiredForCompliance(1);

        $this->addComplianceViewGroup($group);
    }
}

class SBMF2014ComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>


        <p>Hello <?php echo $_user->getFullName(); ?>,</p>

    <p>Welcome to The SBMF Wellness Website! This site was developed to:</p>

        <ol>
            <li>track your wellness activity</li>
            <li>act as a great resource for health related topics and questions.</li>
        </ol>

        <p>By completing the following steps in 2014 you will be eligible for the Premium health plan or any other incentive offered in 2015:</p>

        <p><strong>Step 1</strong>: Complete your health screening, HRA, and blood draw that will be scheduled in August, 2014.</p>

        <p><strong>Step 2</strong>: You will again have the opportunity to earn incentive points in 2014. You will need to obtain a minimum of 9
            incentive points to be eligible for the Premium health plan or any other incentive offered in 2015. You are not required to meet the target range for each individual
            measure. The criteria for meeting these ranges is listed below in your report card. If you have a medical reason for not being able to
            reach one or more of the requirements listed below, you can complete the online eLearning solution assigned programs to obtain points
            or submit a medical exception form to Circle Wellness. As long as you accumulate a total of 9 points by 10/31/2014, you will be eligible
            for the Premium health plan or any other incentive offered for the 2015 plan year.


        <p>Incentive Points can be earned in each of the following categories: </p>
        <ol>
            <li><strong>Smoking Status</strong>: Maintain non-smoker status or participate in the Quit for Life Smoking Cessation program.
            </li>
            <li><strong>Health Profile Measurements</strong>: Point values are assigned below in your report card for each measure. Meet the
                requirements for the recommended ranges for your screening tests to earn incentive points. Two(2) points can be earned for results
                within normal ranges and one(1) point for results within borderline ranges. If you obtain no points in one of the Health Profile Measurements,
                you can complete the four online eLearning modules assigned to the measurement to obtain one point, or submit a medical exception form to
                Circle Wellness and obtain two points. For the BMI measurement, bonus points can be earned by maintaining or reducing your BMI from your
                2013 screening. Measurements will be taken at the August 2014 onsite wellness screenings.
            </li>
            <li><strong>Annual Physical Exam</strong>: An annual physical exam will be automatically entered into your record from information received from your health plan. Updates
                are scheduled each quarter for claims that are paid through the previous quarter. Example: If your appointment was on February 9th and the claim was paid on March 20th,
                the claim will be updated on the website in April or May. OR you can complete a <a href="/resources/4909/SBMF-Verification-Form-2014.053014.pdf">verification form</a>
                indicating that you had your physical completed. Physical exams need to be completed between September 1, 2013 and September 1, 2014.
            </li>
            <li><strong>Annual Flu Shot</strong>: Employees and spouses who received a flu vaccination between September 1, 2013 and March 31, 2014
                will receive two points on their wellness scorecard. It is the employee???s responsibility to ensure documentation of the flu vaccine
                is provided to the Human Resources Department if needed.
            </li>
            <li><strong>Blood Donation</strong>: Employees and spouses who donate blood four times a year will receive two points on their wellness scorecard.
                Those who donate twice a year will earn one point. A double red cell donation will count as two donations. Blood donations must be completed between September 1, 2013 and September 1, 2014.
            </li>


        </ol>





    <?php
    }

    public function printClientNotice()
    {
        $user = sfContext::getInstance()->getUser()->getUser();
        ?>
        Your scorecard will be updated throughout the year as incentive points are earned. Smoking status and Health Profile measurements will not be updated until completion of the August 2014 screenings. You may use your 2013 health screening results to give you an indication of your current potential to??earn incentive points.
    <br/>
    ?? ?? <?php if($user->gender == Gender::FEMALE) : ?>
    ?? ?? ?? <p><a href="/resources/4908/SBMF-Pregnancy-Exception-Form-2014.053014.pdf">Are you pregnant?</a></p>
    ?? ?? <?php endif ?>
    ?? ??
    ?? ?? <?php
    }

    public function printClientNote()
    {
        ?>
    <p>
        A maximum of 20 points are attainable.


    </p>
    <p>
        <strong>Note:</strong>
        Screening Program
        is required for premium plan participation or any other incentive offered in 2015. No points are awarded for completing the screening program.
    </p>
    <p>
        If you cannot feasibly reach one or more of the
        Health Profile Measurements in the scorecard due to a medical condition,
        despite medical treatment, you can have your physician complete
        an <a href="/resources/4907/SBMF-Exception-Form-2014.053014.pdf">exception form</a>
        to meet the Health Profile Measurement and obtain 2 points.
    </p>
    <?php
    }

    public function printCSS()
    {
        parent::printCSS();
        // They want only alternating views to switch colors.
        ?>
    <style type="text/css">
        .requirements {
            text-align: center;
        }
        .phipTable .summary {
            width:120px;
        }

        .phipTable .mainRow td, .phipTable .mainRow th {
            background-color:#CCCC9A;
        }

        .phipTable .alternateRow td, .phipTable .alternateRow th {
            background-color:#CCCCCC;
        }

        .phipTable .totalRow td, .phipTable .totalRow th {
            background-color:#989898;
        }

        .view-elearning_bmi .resource, .view-elearning_tc .resource, .view-elearning_gc .resource, .view-elearning_bp .resource {
            padding-left:4em;
        }
    </style>
    <?php
    }

    protected function printViewNumber(ComplianceView $view)
    {
        return !$view->getAttribute('skip_view_number');
    }
}