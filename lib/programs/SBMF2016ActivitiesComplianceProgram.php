<?php

class SBMF2016ActivitiesFlushotComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getStatus(User $user)
    {
        foreach($user->getDataRecords('sbmf_flushots') as $udr) {
            $date = strtotime($udr->getDataFieldValue('date'));
            if($date >= $this->getStartDate() && $date <= $this->getEndDate()) {
                return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT);
            }
        }

        return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
    }

    public function getDefaultStatusSummary($constant)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'flushot';
    }

    public function getDefaultReportName()
    {
        return 'Flu Shot';
    }
}

class SBMF2016ActivitiesBloodDonationComplianceView extends DateBasedComplianceView
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

class SBMF2016ActivitiesBMIMaintainComplianceView extends DateBasedComplianceView
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

class SBMF2016ActivitiesBMIReductionBonusComplianceView extends DateBasedComplianceView
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


class SBMF2016ActivitiesComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new SBMF2015SecondaryComplianceProgramReportPrinter();
        $printer->setPageHeading('My 2016 Incentive Activities');
        $printer->showTotalCompliance(true);
        $printer->showResult(true);
        $printer->setShowMaxPoints(false);
        $printer->pointValuesHeader = 'Maximum Points';
        $printer->showTotalCompliance(false);

        $printer->setShowNA(true);

        $printer->setShowLegend(true);
        $printer->setDoColor(false);

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

//        $this->addComplianceViewGroup($preventionEventGroup);


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

//        $this->addComplianceViewGroup($smokingStatusGroup);

        $biometricsGroup = new ComplianceViewGroup('Health Profile Measurements (Biometrics)');
        $biometricsGroup->setPointsRequiredForCompliance(1);
        $biometricsGroup->setMaximumNumberOfPoints(13);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->overrideSystolicTestRowData(null, null, 130, 140);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 90, 90);
        $bloodPressureView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 130/90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 140/90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);

        $bloodPressureView->setUseHraFallback(true);
        $biometricsGroup->addComplianceView($bloodPressureView);

        $bpLearn = new CompleteELearningGroupSet($programStart, $programEnd, 'access_bloodpressure');
        $bpLearn->setReportName('BP eLearning Lessons<br/><span style="font-size: 1em;">(Complete only if no points obtained after screening)</span>');
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

        $gcLearn = new CompleteELearningGroupSet($programStart, $programEnd, 'access_bloodsugar');
        $gcLearn->setReportName('Glucose eLearning Lessons<br/><span style="font-size: 1em;">(Complete only if no points obtained after screening)</span>');
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
            'Total Chol: ≤ 180<br/>Total/HDL Ratio: 1-4'
        );
        $totalOrRatioView->setStatusSummary(
            ComplianceStatus::PARTIALLY_COMPLIANT,
            'Total Chol: 181-190<br/>Total/HDL Ratio: 4.1-6'
        );

        $biometricsGroup->addComplianceView($totalOrRatioView);

        $tcLearn = new CompleteELearningGroupSet($programStart, $programEnd, 'access_cholesterol');
        $tcLearn->setReportName('Cholesterol eLearning Lessons<br/><span style="font-size: 1em;">(Complete only if no points obtained after screening)</span>');
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
        $biometricsGroup->addComplianceView($bmiView);

        $bmiReductionView = new SBMF2015BMIReductionBonusComplianceView($programStart, $programEnd);

        $bmiMaintain = new SBMF2015BMIMaintainComplianceView($programStart, $programEnd);
        $bmiMaintain->setReportName('BMI Maintained');
        $bmiMaintain->setName('bmi_maintain');
        $bmiMaintain->setStatusSummary(ComplianceStatus::COMPLIANT, 'BMI Unchanged from 2014');
        $bmiMaintain->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiMaintain->setEvaluateCallback($this->constructCallback($bmiReductionView));
        $biometricsGroup->addComplianceView($bmiMaintain);

        $biometricsGroup->addComplianceView($bmiReductionView);

        $bmiLearn = new CompleteELearningGroupSet($programStart, $programEnd, 'access_bmi');
        $bmiLearn->setReportName('BMI eLearning Lessons<br/><span style="font-size: 1em;">(Complete only if no points obtained after screening)</span>');
        $bmiLearn->setName('elearning_bmi');
        $bmiLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bmiLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $bmiLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiLearn->setAttribute('skip_view_number', true);
        $bmiLearn->setEvaluateCallback($this->constructCallback($bmiReductionView));
        current($bmiLearn->getLinks())->setLinkText('Access Lessons');

        $biometricsGroup->addComplianceView($bmiLearn);

//        $this->addComplianceViewGroup($biometricsGroup);

        $otherMeasurementsGroup = new ComplianceViewGroup('other_measurements', 'Other Measurements');
        $otherMeasurementsGroup->setPointsRequiredForCompliance(1);

        $physicalView = new CompletePreventionPhysicalExamComplianceView($programStart, $programEnd);
        $physicalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $physicalView->addLink(new Link('I did this', '/resources/7529/2016 Physician Biometric Consent Form.pdf'));
        $otherMeasurementsGroup->addComplianceView($physicalView);


        $flushotView = new SBMF2016ActivitiesFlushotComplianceView('2015-09-01', '2016-08-31');
        $flushotView->setReportName('Flu Shot');
        $flushotView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $flushotView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive Flu Shot');
        $flushotView->emptyLinks();
        $otherMeasurementsGroup->addComplianceView($flushotView);


        $bloodView = new SBMF2016BloodDonationComplianceView('2015-09-01', '2016-08-31');
        $bloodView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $otherMeasurementsGroup->addComplianceView($bloodView);

        $additionalLessons = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd);
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


        $otherMeasurementsGroup->addComplianceView($additionalLessons);

        $this->addComplianceViewGroup($otherMeasurementsGroup);
    }

    public function getLocalActions()
    {
        return array(
            'previous_reports' => array($this, 'executePreviousReports'),
        );
    }

    public function executePreviousReports()
    {
        ?>
        <p><a href="/compliance_programs?id=3">Click here</a> for the 2010 scorecard.</p>
        <p><a href="/compliance_programs?id=122">Click here</a> for the 2011 scorecard.</p>
        <p><a href="/compliance_programs?id=202">Click here</a> for the 2012 scorecard.</p>
        <p><a href="/compliance_programs?id=270">Click here</a> for the 2013 scorecard.</p>
        <p><a href="/compliance_programs?id=359">Click here</a> for the 2014 scorecard.</p>
        <?php
    }
}

class SBMF2015SecondaryComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>

        <script type="text/javascript">
            $(function() {
                $('.phipTable .headerRow').not('.totalRow').addClass("cursor");

                $('#steps').hide();
                $('#sub_steps').hide();
                $('#show_more_sub_steps').hide();


                $('#show_more_steps').toggle(function(){
                    $('#steps').show();
                    $('#show_more_sub_steps').show();
                    $('#show_more_steps a').html('Less...');
                }, function(){
                    $('#steps').hide();
                    $('#sub_steps').hide();
                    $('#show_more_sub_steps').hide();
                    $('#show_more_steps a').html('More...');
                });

                $('#show_more_sub_steps').toggle(function(){
                    $('#sub_steps').show();
                    $('#show_more_sub_steps a').html('Less...');
                }, function(){
                    $('#sub_steps').hide();
                    $('#show_more_sub_steps a').html('More...');
                });

            });

        </script>



        <p>Hello <?php echo $_user->getFullName(); ?>,</p>

        <p>Your Incentive Activities Page!</p>


        <ol>

            <li><strong>Annual Physical Exam</strong>: When your physician completed the
                physical exam verification/biometric screening form and faxes the form to Circle Wellness, your
                scorecard will be credited with two (2) points. Physical exams need to be completed between September 1, 2015 and August 31, 2016.
            </li>
            <li><strong>Annual Flu Shot</strong>: Employees and spouses who received a flu vaccination between September 1, 2015 and March 31, 2016
                will receive two points on their wellness scorecard. It is the employee’s responsibility to ensure documentation of the flu vaccine
                is provided to the Human Resources Department if needed.
            </li>
            <li><strong>Blood Donation</strong>: Employees and spouses who donate blood four times a year will receive two points on their wellness scorecard.
                Those who donate twice a year will earn one point. A double red cell donation will count as two donations. Blood donations must be completed between September 1, 2015 and August 31, 2016.
            </li>
        </ol>






        <?php
    }

    public function printClientNotice()
    {
        $user = sfContext::getInstance()->getUser()->getUser();
        ?>
        Your scorecard will be updated throughout the year as incentive points are earned. You may use your 2015 health screening results to give you an indication of your current potential to earn incentive points.
        <br/>
        <p><a href="compliance_programs?id=686">Click Here to View My Incentive Scorecard</a></p>

           
            <?php
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
            is required for the wellness incentive offered in 2017. No points are awarded for completing the screening program.
        </p>
        <p>
            If you cannot feasibly reach one or more of the
            Health Profile Measurements in the scorecard due to a medical condition,
            despite medical treatment, you can have your physician complete
            an <a href="/resources/7214/SBMF Verifcation Exception Form - 2016.pdf">exception form</a>
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
                font-size: 1em;
            }

            .phipTable .points {
                font-size: 1em;
            }

            .phipTable .cursor:hover {
                cursor: hand;
                cursor: pointer;
                opacity: .9;
            }

            .phipTable .indicator {
                width: 40px;
            }

            .phipTable .indicator img{
                width: 20px;
            }

            .phipTable .headerRow {
                height: 60px;
            }

            .phipTable .mainRow td, .phipTable .mainRow th {
                /*            background-color:#CCCC9A;*/
            }

            .phipTable .alternateRow td, .phipTable .alternateRow th {
                /*            background-color:#CCCCCC;*/
            }

            .phipTable .totalRow td, .phipTable .totalRow th {
                background-color:#89be44;

            }

            .view-elearning_bmi .resource, .view-elearning_tc .resource, .view-elearning_gc .resource, .view-elearning_bp .resource {
                padding-left:4em;
            }

            .phipTable tr.newViewRow, .phipTable tr.totalRow {
                border-top:8px solid #89be44 !important;
            }
        </style>
        <?php
    }

    protected function printViewNumber(ComplianceView $view)
    {
        return !$view->getAttribute('skip_view_number');
    }
}