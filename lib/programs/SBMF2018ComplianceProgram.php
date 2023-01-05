<?php

class SBMF2018BMIMaintainComplianceView extends DateBasedComplianceView
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

class SBMF2018BMIReductionBonusComplianceView extends DateBasedComplianceView
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

            $differenceText = round(abs($difference), 2);

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

class SBMF2018LifestyleProgramOne extends PlaceHolderComplianceView
{
}

class SBMF2018LifestyleProgramTwo extends PlaceHolderComplianceView
{
}

class SBMF2018ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowPoints(true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new SBMF2018ComplianceProgramReportPrinter();
        $printer->setPageHeading('My 2017 Incentive Scorecard');
        $printer->showTotalCompliance(true);
        $printer->showResult(true);
        $printer->setShowMaxPoints(false);
        $printer->pointValuesHeader = 'Maximum';

        $printer->setShowNA(true);

        $printer->setShowLegend(true);
        $printer->setDoColor(false);

        return $printer;
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function getPointsRequiredForCompliance()
    {
        return 9;
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

        $hraScreeningView = new CompleteHRAAndScreeningSeparateDateComplianceView("2018-03-01", $programEnd, $programStart, $programEnd);
        $hraScreeningView->setReportName('Screening Program');
        $hraScreeningView->setName('hra_screening');
        $preventionEventGroup->addComplianceView($hraScreeningView);

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
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->overrideSystolicTestRowData(null, null, 130, 140);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 90, 90);
        $bloodPressureView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 130/90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 140/90');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);

        $bloodPressureView->setUseHraFallback(true);
        $biometricsGroup->addComplianceView($bloodPressureView);

        $bpLearn = new CompleteELearningGroupSet($programStart, $programEnd, 'alt_bloodpressure');
        $bpLearn->setReportName('BP eLearning Lessons<br/><span style="font-size: 1em;">(Complete only if no points obtained after screening)</span>');
        $bpLearn->setName('elearning_bp');
        $bpLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bpLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $bpLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bpLearn->setAttribute('skip_view_number', true);
//        $bpLearn->setEvaluateCallback($this->constructCallback($bloodPressureView));
        current($bpLearn->getLinks())->setLinkText('Access Lessons');
        $biometricsGroup->addComplianceView($bpLearn);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setName('glucose');
        $glucoseView->setReportName('Glucose OR Ha1c* <br />*Ha1c only run if Glucose is >110');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->overrideTestRowData(null, 0, 104, 110);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, '< 105');
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '105 - 110');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $glucoseView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($programStart, $programEnd) {
            if($status->getPoints() == 0) {
                $view = $status->getComplianceView();

                $ha1cView = new ComplyWithHa1cScreeningTestComplianceView($programStart, $programEnd);
                $ha1cView->setName('ha1c');
                $ha1cView->overrideTestRowData(4.599, 4.599, 5.9, 7.0);
                $ha1cView->setComplianceViewGroup($view->getComplianceViewGroup());
                $ha1cView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
                $ha1cStatus = $ha1cView->getStatus($user);

                $glucoseResult = $status->getComment();
                $ha1cResult = $ha1cStatus->getComment();

                if($ha1cResult) {
                    $status->setComment($glucoseResult.'/'.$ha1cResult);
                }

                if($ha1cStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setPoints(2);
                } elseif($ha1cStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                    $status->setPoints(1);
                }
            }

        });
        $biometricsGroup->addComplianceView($glucoseView);

        $gcLearn = new CompleteELearningGroupSet($programStart, $programEnd, 'alt_bloodsugar');
        $gcLearn->setReportName('Glucose eLearning Lessons<br/><span style="font-size: 1em;">(Complete only if no points obtained after screening)</span>');
        $gcLearn->setName('elearning_gc');
        $gcLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $gcLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $gcLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gcLearn->setAttribute('skip_view_number', true);
//        $gcLearn->setEvaluateCallback($this->constructCallback($glucoseView));
//        $gcLearn->setAllowPastCompleted(false);
        current($gcLearn->getLinks())->setLinkText('Access Lessons');
        $biometricsGroup->addComplianceView($gcLearn);

        // This cholesterol is hacked to hell, be careful...
        $totalOrRatioView = new ComplyWithTotalCholesterolTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalOrRatioView->setName('total_hdl_ratio');
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

        $tcLearn = new CompleteELearningGroupSet($programStart, $programEnd, 'alt_cholesterol');
        $tcLearn->setReportName('Cholesterol eLearning Lessons<br/><span style="font-size: 1em;">(Complete only if no points obtained after screening)</span>');
        $tcLearn->setName('elearning_tc');
        $tcLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $tcLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $tcLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tcLearn->setAttribute('skip_view_number', true);
//        $tcLearn->setEvaluateCallback($this->constructCallback($totalOrRatioView));
        current($tcLearn->getLinks())->setLinkText('Access Lessons');
        $biometricsGroup->addComplianceView($tcLearn);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bmiView->setName('bmi');
        $bmiView->setReportName('BMI');
        $bmiView->setUseHraFallback(true);
        $biometricsGroup->addComplianceView($bmiView);

        $bmiReductionView = new SBMF2018BMIReductionBonusComplianceView($programStart, $programEnd);

        $bmiMaintain = new SBMF2018BMIMaintainComplianceView($programStart, $programEnd);
        $bmiMaintain->setReportName('BMI Maintained');
        $bmiMaintain->setName('bmi_maintain');
        $bmiMaintain->setStatusSummary(ComplianceStatus::COMPLIANT, 'BMI Unchanged from 2017');
        $bmiMaintain->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiMaintain->setEvaluateCallback($this->constructCallback($bmiReductionView));
        $biometricsGroup->addComplianceView($bmiMaintain);

        $biometricsGroup->addComplianceView($bmiReductionView);

        $bmiLearn = new CompleteELearningGroupSet($programStart, $programEnd, 'alt_bmi');
        $bmiLearn->setReportName('BMI eLearning Lessons<br/><span style="font-size: 1em;">(Complete only if no points obtained after screening)</span>');
        $bmiLearn->setName('elearning_bmi');
        $bmiLearn->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $bmiLearn->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete assigned lessons');
        $bmiLearn->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiLearn->setAttribute('skip_view_number', true);
//        $bmiLearn->setEvaluateCallback($this->constructCallback($bmiReductionView));
        current($bmiLearn->getLinks())->setLinkText('Access Lessons');

        $biometricsGroup->addComplianceView($bmiLearn);

        $this->addComplianceViewGroup($biometricsGroup);


        $activitiesGroup = new ComplianceViewGroup('Incentive Activities Points');

        $activitiesView = new PlaceHolderComplianceView(null, 0);
        $activitiesView->setName('activities_points');
        $activitiesView->setReportName('Incentive Activities Points');
        $activitiesGroup->addComplianceView($activitiesView);
        $that = $this;
        $activitiesView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($that) {
            $status->setPoints($that->getActivityComplianceStatus($user)->getPoints());
        });

        $this->addComplianceViewGroup($activitiesGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $bloodPressueStatus = $status->getComplianceViewStatus('blood_pressure');
        $glucoseStatus = $status->getComplianceViewStatus('glucose');
        $totalHDLRatioStatus = $status->getComplianceViewStatus('total_hdl_ratio');
        $bmiStatus = $status->getComplianceViewStatus('bmi');

        $hraScreeningStatus = $status->getComplianceViewStatus('hra_screening');

        $hasBiometrics = true;
        if(!$bloodPressueStatus->getAttribute('has_result')) {
            $hasBiometrics = false;
        } elseif(!$glucoseStatus->getAttribute('has_result')) {
            $hasBiometrics = false;
        } elseif(!$totalHDLRatioStatus->getAttribute('has_result')) {
            $hasBiometrics = false;
        } elseif(!$bmiStatus->getAttribute('has_result')) {
            $hasBiometrics = false;
        }

        if($hasBiometrics
            && $hraScreeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT
            && $status->getPoints() >= 9) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

    }

    public function getActivityComplianceStatus(User $user)
    {
        $programRecord = ComplianceProgramRecordTable::getInstance()->find(self::ACTIVITY_RECORD_ID);

        $program = $programRecord->getComplianceProgram();

        $program->setActiveUser($user);
        $status = $program->getStatus();

        return $status;
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
        <p><a href="/compliance_programs?id=463">Click here</a> for the 2010 scorecard.</p>
        <p><a href="/compliance_programs?id=464">Click here</a> for the 2011 scorecard.</p>
        <p><a href="/compliance_programs?id=465">Click here</a> for the 2012 scorecard.</p>
        <p><a href="/compliance_programs?id=466">Click here</a> for the 2013 scorecard.</p>
        <p><a href="/compliance_programs?id=467">Click here</a> for the 2014 scorecard.</p>
        <p><a href="/compliance_programs?id=468">Click here</a> for the 2015 scorecard.</p>
        <?php
    }

    const ACTIVITY_RECORD_ID = 1336;
}

class SBMF2018ComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    protected function printCustomRows(ComplianceProgramStatus $status)
    {
        $activityStatus = $status->getComplianceViewStatus('activities_points');

        ?>
        <script type="text/javascript">
            $(function() {
                var currentPoints = parseInt($('.phipTable tbody').children(':eq(1)').children('.points').html());

                var activityPoints = parseInt(<?php echo $activityStatus->getPoints() ?>);

                $('#incentive_activities_points').next().append('<tr class="headerRow"><td></td><td style="text-align: center;">Total Points</td><td ></td><td>'+activityPoints+'</td><td></td></tr>');

                $('.phipTable').hide();

                $('.wms2_section').click(function(){
                    $(this).find('.wms2_title').toggleClass('closed');
                    $(this).next().toggle();
                });
            });
        </script>

        <?php
    }

    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>

        <script type="text/javascript">
            $(function() {

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

            $(function(){
                $('#showmeMoreTrigger').on('click', function(evt){
                    evt.preventDefault();
                    if ($('#showmeMore').hasClass('show')) {
                        $('#showmeMore').addClass('hide');
                        $('#showmeMore').removeClass('show');
                        $(this).text('More');
                    } else {
                        $('#showmeMore').addClass('show');
                        $('#showmeMore').removeClass('hide');
                        $(this).text('Less');
                    }
                });
            });

        </script>

        <img src="/assets/images/clients/chp/banners/incentives.png?v=2b0b449" alt="Page Banner" class="page-banner img-responsive center-block">
        <div id="programMessage" style="margin-top:20px;">
            <p>By completing the following steps in 2018 you will be eligible for the wellness incentive offered in 2019:</p>
            <ul><li><strong>Step 1: </strong>Complete your physical exam, HRA, and blood draw by December 31, 2018.</li><li><strong>Step 2: </strong>
                    You will again have the opportunity to earn incentive points in 2018.
                    You will need to obtain a minimum of 9 incentive points to be eligible for the wellness incentive
                    offered in 2019. You are not required to meet the target range for each individual measure. The
                    criteria for meeting these ranges is listed below in your report card. If you have a medical
                    reason for not being able to reach one or more of the requirements listed below, you can
                    complete the online eLearning solution assigned programs to obtain points or submit a medical
                    exception form to Circle Wellness. As long as you accumulate a total of 9 points by 10/31/2018,
                    you will be eligible for the wellness incentive offered for the 2019 plan year.
                </li></ul>
            <p><a href="#" id="showmeMoreTrigger">More</a></p>
        </div>

        <div id="showmeMore" class="hide"><div><p>Incentive Points can be earned in each of the following categories: </p><ul><li><strong>Smoking Status: </strong>Maintain non-smoker status or participate in the Quit for Life Smoking Cessation program.</li><li><strong>Health Profile Measurements: </strong>
                        Point values are assigned below in your report card for each measure. Meet the requirements
                        for the recommended ranges for your screening tests to earn incentive points. Two(2) points can
                        be earned for results within normal ranges and one(1) point for results within borderline
                        ranges. If you obtain no points in one of the Health Profile Measurements, you can complete
                        the four online eLearning modules assigned to the measurement to obtain one point, or submit
                        a medical exception form to Circle Wellness and obtain two points. For the BMI measurement,
                        bonus points can be earned by maintaining or reducing your BMI from your 2017 screening.
                        <span class="text-danger"> *</span></li></ul></div></div>

        <?php
    }

    public function printClientNotice()
    {

    }

    public function printClientNote()
    {
        ?>
        <p>
            A maximum of 20 points are attainable.


        </p>
        <p>
            <strong>Note:</strong>
            A Physical exam/biometric screening is required for the wellness incentive offered in 2018. Points will be
            rewarded for completing the annual physical exam and successfully meeting the biometric benchmarks.
        </p>
        <p>
            If you cannot feasibly reach one or more of the
            Health Profile Measurements in the scorecard due to a medical condition,
            despite medical treatment, you can have your physician complete
            an <a href="https://static.hpn.com/wms2/documents/clients/sbmf/SBMF%20Verifcation%20%20Exception%20Form%20-%202018.pdf">exception form</a>
            to meet the Health Profile Measurement and obtain 2 points.
        </p>
        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        global $_cr, $_user;

        $this->status = $status;
        $this->printCSS();
        ?>
        <div id="clientMessage"><?php $this->printClientMessage() ?></div>

        <div class="wms2_legend">
            <div class="wms2_row">
                <div class="color green"><div class="circle"></div></div>
                <div class="status">Done</div>
            </div>

            <div class="wms2_row">
                <div class="color yellow"><div class="circle"></div></div>
                <div class="status">Partially Done</div>
            </div>

            <div class="wms2_row">
                <div class="color red"><div class="circle"></div></div>
                <div class="status">Not Done</div>
            </div>
        </div>

        <?php if($this->showTotalCompliance) $this->printTotalStatus() ?>


        <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
            <?php $this->printGroupStatus($groupStatus) ?>
        <?php endforeach ?>

        <?php $this->printCustomRows($status) ?>

        <div id="clientNote"><?php $this->printClientNote() ?></div>
        <?php
        $this->status = null;
    }

    protected function printTotalStatus()
    {
        $nameColumns = 3;
        if($this->status->getPoints() === null) $nameColumns++;
        if($this->showResult) $nameColumns++;
        ?>

        <div class="wms2_header">
            <div class="wms2_row">
                <div class="wms2_title"></div>
                <div class="wms2_target">Target</div>
                <div class="wms2_actual">Status</div>
                <div class="wms2_progress">Progress</div>
            </div>
        </div>

        <div class="wms2_section">
            <div class="wms2_row">
                <div class="wms2_title open closed">Program Status <span class="triangle"></span></div>
                <div class="wms2_target"><strong>Done</strong></div>
                <div class="wms2_actual <?php echo $this->lightToStatus($this->status->getLight())?>">
                    <strong>
                        <?php echo $this->lightToStatus($this->status->getLight(), false)?>
                    </strong>
                </div>
                <div class="wms2_progress">
                    <div class="progress_bar">
                        <div class="status_bar <?php echo $this->lightToStatus($this->status->getLight())?>" style="width: <?php echo $this->statusToWidth( $this->lightToStatus($this->status->getLight()))?>"></div>
                    </div>
                </div>
            </div>
        </div>
        <table class="phipTable">
            <tbody>
                <tr class="headerRow">
                    <th colspan="<?php echo $nameColumns?>">Item</th>
                    <td>Status</td>
                    <td><?php echo $this->pointsHeading ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="<?php echo $nameColumns ?>">
                        1. Your Status (On <?php echo date('m/d/Y') ?>)
                    </td>
                    <td class="wms2_status <?php echo $this->lightToStatus($this->status->getLight())?>">
                        <strong>
                            <?php echo $this->lightToStatus($this->status->getLight(), false)?>
                        </strong>
                    </td>
                    <?php if($this->status->getPoints() !== null) : ?>
                        <td class="points"><?php echo $this->status->getPoints() ?></td>
                    <?php endif ?>
                </tr>
            </tbody>
        </table>
        <?php
    }

    private function calcProgressBar($value, $max) {
        if ($value > 0) {
            return number_format(($value/$max)*100, 2);
        }
    }

    private function determineStatusClass($value, $max) {
        if ($value == 0) {
            return "status_red";
        } else if ($value < $max) {
            return "status_yellow";
        } else {
            return "status_green";
        }
    }

    private function statusToWidth($status = "status_red"){
        if ($status == "status_green") {
            return "100%";
        } else if ($status == "status_yellow") {
            return "50%";
        } else {
            return "2%";
        }
    }

    private function lightToStatus($href, $isClass = true) {
        if ($href == "/images/lights/greenlight.gif")
            return ($isClass)? "status_green":"Done";
        elseif ($href == "/images/lights/yellowlight.gif")
            return ($isClass)? "status_yellow":"Partially Done";
        else
            return ($isClass)? "status_red":"Not Done";
    }

    public function printGroupStatus(ComplianceViewGroupStatus $groupStatus)
    {
        $group = $groupStatus->getComplianceViewGroup();

        if ($group->pointBased()) :
        ?>
        <div class="wms2_section gap" id="<?php echo strtolower(str_replace(' ','_', $group->getReportName())); ?>">
            <div class="wms2_row">
                <div class="wms2_title open closed"><?php echo $group->getReportName(); ?><span class="triangle"></span></div>
                <div class="wms2_target"><strong><?php echo $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></strong> <br/> points</div>
                <div class="wms2_actual <?php echo $this->determineStatusClass($groupStatus->getPoints() , $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints())?>"><strong><?php echo $groupStatus->getPoints() ?></strong> <br/> points</div>
                <div class="wms2_progress">
                    <div class="progress_bar">
                        <div class="status_bar <?php echo $this->determineStatusClass($groupStatus->getPoints() , $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints())?>" style="width: <?php echo $this->calcProgressBar($groupStatus->getPoints() , $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints())?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="wms2_section gap" id="<?php echo strtolower(str_replace(' ','_', $group->getReportName())); ?>">
            <div class="wms2_row">
                <div class="wms2_title open closed"><?php echo $group->getReportName(); ?><span class="triangle"></span></div>
                <div class="wms2_target"><strong>Done</strong> <br/> </div>
                <div class="wms2_actual <?php echo $this->lightToStatus($groupStatus->getLight())?>">
                    <strong>
                        <?php echo $this->lightToStatus($groupStatus->getLight(), false)?>
                    </strong>
                </div>
                <div class="wms2_progress" data-light="<?php echo $groupStatus->getLight()?>">
                    <div class="progress_bar">
                        <div class="status_bar <?php echo $this->lightToStatus($groupStatus->getLight())?>" style="width: <?php echo $this->statusToWidth( $this->lightToStatus($groupStatus->getLight()))?>"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>


        <table class="phipTable">
            <tbody>

        <tr class="headerRow <?php echo sprintf('group-%s', Doctrine_Inflector::urlize($group->getName())) ?>">
            <?php if($group->pointBased()) : ?>
                <th>Item</th>
                <td><?php echo $this->targetHeader ?></td>
                <td><?php echo $this->pointValuesHeader ?></td>
                <?php if($this->showMaxPoints) : ?>
                    <td>Maximum Points</td>
                <?php endif ?>
                <td><?php echo $this->pointsHeading ?></td>
                <?php if($this->showResult) echo '<td>'.$this->resultHeading.'</td>' ?>
            <?php else : ?>
                <th>Item</th>
                <td colspan="<?php echo $this->showMaxPoints ? 3 : 2 ?>"><?php echo $this->requirementsHeader ?></td>
                <td>Your Status</td>
                <?php if($this->showResult) echo '<td class="empty"></td>' ?>
            <?php endif ?>

            <?php if($group->hasLinks()) : ?>
                <td>Links</td>
            <?php else : ?>
                <td class="empty"></td>
            <?php endif ?>
        </tr>
        <?php
        $number = 1;
        $i = 0;
        foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) :
            if($this->showNA || $viewStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                if($group->pointBased()) {
                    $this->printPointBasedViewStatus($viewStatus, $number);
                } else {
                    $this->printViewStatus($viewStatus, $number);
                }

                if($this->printViewNumber($viewStatus->getComplianceView())) {
                    $number++;
                }

                $i++;
            }
        endforeach;
        ?>

        <?php if($i && $group->pointBased()) : ?>
        <tr class="headerRow totalRow <?php echo sprintf('status-%s', $groupStatus->getStatus()) ?> <?php echo sprintf('group-%s', Doctrine_Inflector::urlize($group->getName())) ?>">
            <th>Totals</th>
            <td></td>
            <td></td>
            <?php if($this->showMaxPoints) : ?>
                <td>
                    <?php echo $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?>
                </td>
            <?php endif ?>
            <td class="total_points">
                <?php echo $groupStatus->getPoints() ?>
            </td>
            <?php if($this->showResult) echo '<td class="empty">'.$groupStatus->getComment().'</td>' ?>
            <td class="empty"></td>
        </tr>

    <?php endif ?>
            </tbody>
        </table>
        <?php
    }

    private function printViewStatus(ComplianceViewStatus $viewStatus, $i)
    {
        $view = $viewStatus->getComplianceView();
        $group = $view->getComplianceViewGroup();
        ?>
        <tr class="<?php echo sprintf('view-%s', $view->getName()) ?>">
            <td class="resource">
                <?php echo sprintf('%s%s', $this->printViewNumber($view) ? "$i. " : '', $view->getReportName()) ?>
            </td>
            <td class="requirements" colspan="<?php echo $this->showMaxPoints ? 3 : 2 ?>"><?php echo $view->getStatusSummary(ComplianceStatus::COMPLIANT); ?></td>
            <td class="wms2_status <?php echo $this->lightToStatus($viewStatus->getLight())?>" data-light="<?php echo $viewStatus->getLight() ?>">
                <strong>
                    <?php echo $this->lightToStatus($viewStatus->getLight(), false)?>
                </strong>
            </td>
            <?php if($this->showResult) echo '<td class="empty"></td>' ?>
            <?php $this->printViewLinks($view) ?>
        </tr>
        <?php
    }

    private function printPointBasedViewStatus(ComplianceViewStatus $viewStatus, $i)
    {
        $j = 0;
        $printedPoints = false;
        $printedResult = false;

        $view = $viewStatus->getComplianceView();
        $mappings = $this->getStatusMappings($view);
        $group = $view->getComplianceViewGroup();

        $rowspan = count($mappings);
        if(!$this->printViewNumber($view)) {
            $i -= 1;
        }

        foreach($mappings as $sstatus => $mapping) :
            $domClasses = array(
                'statusRow', (!$j ? 'newViewRow' : ''),
                'pointBased', 'statusRow'.$sstatus,
                ($i % 2 ? 'mainRow' : 'alternateRow'),
                sprintf('status-%s', $viewStatus->getStatus()),
                sprintf('view-%s', $view->getName())
            );

            $correctStatus = $this->pointBasedViewStatusMatchesMapping($viewStatus, $sstatus);
            $onLastRow = ($j == count($mappings) - 1);
            ?>
            <tr class="<?php echo implode(' ', $domClasses) ?>">
                <?php if($j < 1) : ?>
                    <td class="resource" rowspan="<?php echo count($mappings) ?>">
                        <?php echo sprintf('%s%s', $this->printViewNumber($view) ? "$i. " : '', $view->getReportName(true)) ?>
                    </td>
                <?php endif ?>
                <td class="summary">
                    <?php echo $view->getStatusSummary($sstatus); ?>
                </td>
                <td class="points w75 status_blue">
                    <?php echo $view->getStatusPointMapper() ? $view->getStatusPointMapper()->getPoints($sstatus) : '' ?>
                </td>

                <?php
                if(!$printedPoints) :
                    echo '<td rowspan="'.$rowspan.'" class="w75 points your_points '.$this->determineStatusClass($viewStatus->getPoints(), $view->getStatusPointMapper()->getPoints($sstatus)).' ">', $viewStatus->getPoints(), '</td>';
                    $printedPoints = true;
                    if($this->showResult) :
                        echo '<td class="points">', $viewStatus->getComment(), '</td>';
                    endif; else :
                    echo '<td class="points"></td>';
                    if($this->showResult) :
                        echo '<td class="points"></td>';
                    endif;
                endif
                ?>
                <?php if(!$j) : ?>
                    <?php $this->printViewLinks($view) ?>
                <?php else : ?>
                    <td class="empty"></td>
                <?php endif ?>
            </tr>
            <?php
            $j++;
        endforeach;
    }

    private function printViewLinks(ComplianceView $view)
    {
        if($view->getComplianceViewGroup()->hasLinks()) {
            echo '<td class="links">'.implode('<br/>', $view->getLinks()).'</td>';
        } else {
            echo '<td class="empty"></td>';
        }
    }

    public function printCSS()
    {
        parent::printCSS();
        // They want only alternating views to switch colors.
        ?>
        <style type="text/css">
            #content {
                width: 850px;
            }

            #clientMessage {
                width: calc(100% - 220px);
                display: inline-block;
            }

            #clientNote {
                margin-top: 40px;
            }

            ol li {
                margin-bottom: 8px;
            }

            .wms2_legend {
                width: 200px;
                margin: auto;
                margin-bottom: 20px;
                box-sizing: border-box;
                display: inline-block;
                padding-left: 20px;
                position: relative;
            }

            .wms2_legend .wms2_row {
                width: 100%;
                margin-bottom: 10px;
            }

            .wms2_legend .wms2_row .color, .wms2_legend .wms2_row .status {
                display: inline-block;
            }

            .wms2_legend .wms2_row .status {
                margin-left: 10px;
                position: relative;
                top:-9px;
            }

            .wms2_legend .circle {
                width: 30px;
                height: 30px;
                border-radius: 50%;
            }

            .wms2_legend .color.green .circle {
                background: #74c36e;
            }

            .wms2_legend .color.yellow .circle {
                background: #fdb83b;
            }

            .wms2_legend .color.red .circle {
                background: #f15752;
            }

            .wms2_legend .color.white .circle {
                background: white;
                border: 1px solid black;
                box-sizing: border-box;
            }

            .wms2_header {
                display: table;
                width: 100%;
                border-collapse: collapse;
                overflow: hidden;
                margin-bottom:5px;
                font-size: 1em;
                font-weight: 600;
                color: #666;
            }

            .wms2_header .wms2_title {
                font-size: 1.2em;
                display: table-cell;
                width: 50%;
            }

            .wms2_header .wms2_target {
                display: table-cell;
                width: 75px;
                vertical-align: middle;
                text-align: center;
                padding-left: 5px;
            }

            .wms2_header .wms2_actual {
                display: table-cell;
                width: 75px;
                vertical-align: middle;
                text-align: center;
                padding-left: 5px;
            }

            .wms2_header .wms2_progress {
                display: table-cell;
                padding-left: 5px;
                text-align: center;
            }

            .wms2_header .wms2_row, .wms2_section .wms2_row {
                display: table-row;
            }

            .wms2_section .wms2_row {
                cursor: pointer;
            }

            .wms2_section .wms2_row:hover > * {
                box-shadow: 0px 0px 0px 2px #48c8e8 inset;
            }

            .wms2_section {
                height: 75px;
                display: table;
                width: 100%;
                border-collapse: collapse;
                overflow: hidden;
            }

            .wms2_section.gap {
                margin-top: 5px;
            }

            .wms2_section .wms2_title {
                font-size: 1.2em;
                display: table-cell;
                vertical-align: middle;
                width: 50%;
                background: #eee;
                padding-left: 10px;
                color: #666;
                position: relative;
            }

            .wms2_section .wms2_title.open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            .wms2_section .wms2_title.closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .wms2_section .wms2_title .triangle {
                position: absolute;
                right: 15px;
                top: 27px;
            }

            .wms2_section .wms2_target {
                display: table-cell;
                width: 75px;
                height: 75px;
                vertical-align: middle;
                border-left: 5px solid white;
                border-collapse: collapse;
                text-align: center;
                background: #48c7e8;
                color: white;
                font-size: 1em;
                line-height: 1.8em;
            }

            .wms2_section .wms2_actual {
                display: table-cell;
                width: 75px;
                height: 75px;
                vertical-align: middle;
                border-left: 5px solid white;
                border-collapse: collapse;
                text-align: center;
                background: #f15752;
                color: white;
                font-size: 1em;
                line-height: 1.8em;
            }

            .wms2_section .wms2_progress {
                display: table-cell;
                border-left: 5px solid white;
                background: #eee;
                padding: 8px;
                box-sizing: border-box;
            }

            .headerRow, .phipTable .headerRow th {
                font-weight: 600;
                color: #666;
                background: white;
            }

            .headerRow td {
                background: white;
            }

            .w75 {
                width: 75px;
            }

            .phipTable {
                width: calc(100% - 20px);
                margin-left: 20px;
                margin-bottom: 20px;
                display: none;
            }

            .status_blue {
                background: #48c7e8 !important;
                color: white;
            }

            .status_green {
                background: #74c36e !important;
                color: white;
            }

            .status_yellow {
                background: #fdb83b !important;
                color: white;
            }

            .status_red {
                background: #f15752 !important;
                color: white;
            }

            .wms2_status {
                display: table-cell;
                width: 60px;
                height: 50px;
                vertical-align: middle;
                border-left: 5px solid white;
                border-collapse: collapse;
                text-align: center;
                background: #f15752;
                color: white;
                font-size: 1em;
                line-height: 1.8em;
            }

            .phipTable td.resource {
                width: 250px;
                color: #666;
            }

            .phipTable td.wms2_progress {
                width: 150px;
            }

            .phipTable, .phipTable tr.newViewRow {
                border: none;
                font-size: 1em;
            }

            .progress_bar {
                background: #ccc;
                width: 100%;
                height: 100%;
                min-height: 10px;
            }

            .progress_bar .status_bar {
                width: 2%;
                height: 100%;
                min-height: 10px;
                background: #fd3b3b;
            }

            d.requirements {
                text-align: center;
            }
            .phipTable .summary {
                width:220px;
                font-size: 1em;
                padding: 6px 8px !important;
                text-align: left;
            }

            .phipTable .points {
                font-size: 1em;
                border: 4px solid white;
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
                height: 50px;
                background: transparent;
                font-weight:bold;
                color: #666;
            }

            .phipTable .mainRow td, .phipTable .mainRow th {
                /*            background-color:#CCCC9A;*/
            }

            .phipTable .alternateRow td, .phipTable .alternateRow th {
                /*            background-color:#CCCCCC;*/
            }



            .view-elearning_bmi .resource, .view-elearning_tc .resource, .view-elearning_gc .resource, .view-elearning_bp .resource {
                padding-left:4em;
            }

            .phipTable td { padding: 3px !important;}
            .pageHeading { display: none; }
            .phipTable .headerRow:hover {
                background: #eee;
            }

        </style>
        <?php
    }

    protected function printViewNumber(ComplianceView $view)
    {
        return !$view->getAttribute('skip_view_number');
    }

    protected $resultHeading = 'Your Result';
    private $showNA = true;
    private $doColor = true;
    private $showMaxPoints = false;
    private $showLegend = true;
    private $targetHeader = 'Target';
    private $showTotalCompliance = true;
    private $showResult = true;
    protected $status = null;
    private $pageHeading = 'My Incentive Report Card';
    private $pointsHeading = 'Actual';
    public $requirementsHeader = 'Requirements';
    public $pointValuesHeader = 'Point Values';
}