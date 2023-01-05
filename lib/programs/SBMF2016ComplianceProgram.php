<?php

class SBMF2016BloodDonationComplianceView extends DateBasedComplianceView
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

class SBMF2016BMIMaintainComplianceView extends DateBasedComplianceView
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

class SBMF2016BMIReductionBonusComplianceView extends DateBasedComplianceView
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

class SBMF2016LifestyleProgramOne extends PlaceHolderComplianceView
{
}

class SBMF2016LifestyleProgramTwo extends PlaceHolderComplianceView
{
}

class SBMF2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowPoints(true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new SBMF2016ComplianceProgramReportPrinter();
        $printer->setPageHeading('My 2016 Incentive Scorecard');
        $printer->showTotalCompliance(true);
        $printer->showResult(true);
        $printer->setShowMaxPoints(false);
        $printer->pointValuesHeader = 'Maximum Points';

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

        $hraScreeningView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
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
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->overrideTestRowData(null, 0, 104, 110);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, '< 105');
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '105 - 110');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);

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

        $bmiReductionView = new SBMF2016BMIReductionBonusComplianceView($programStart, $programEnd);

        $bmiMaintain = new SBMF2016BMIMaintainComplianceView($programStart, $programEnd);
        $bmiMaintain->setReportName('BMI Maintained');
        $bmiMaintain->setName('bmi_maintain');
        $bmiMaintain->setStatusSummary(ComplianceStatus::COMPLIANT, 'BMI Unchanged from 2015');
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

    const ACTIVITY_RECORD_ID = 689;
}

class SBMF2016ComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    protected function printCustomRows(ComplianceProgramStatus $status)
    {
        $activityStatus = $status->getComplianceViewStatus('activities_points');

        ?>
        <script type="text/javascript">
            $(function() {
                var currentPoints = parseInt($('.phipTable tbody').children(':eq(1)').children('.points').html());

                var activityPoints = parseInt(<?php echo $activityStatus->getPoints() ?>);

                $('.phipTable tbody .group-health-profile-measurements-biometrics').not('.cursor').after('<tr class="headerRow" style="background-color:#3385FF;"><td></td><td style="text-align: left;">Incentive Activities Points</td><td colspan="2"></td><td>'+activityPoints+'</td><td colspan="2"></td></tr>');
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
                $('.phipTable .headerRow').not('.totalRow').addClass("cursor");

                $('.phipTable tbody').children(':eq(0)').html('<th colspan="4">Program Status</th><td>Your Status</td><td>Your Points</td>');

                $('.phipTable tbody').children().each(function(){
                    $(this).prepend('<td class="indicator"></td>');
                });

                $('.phipTable tbody').children().not('.headerRow').each(function(){
                    $(this).hide();
                });


                $('.phipTable tbody').children(':eq(0)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                $('.phipTable tbody').children(':eq(2)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                $('.phipTable tbody').children(':eq(4)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                $('.phipTable tbody').children(':eq(9)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                $('.phipTable tbody').children(':eq(27)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');


                $('.phipTable tbody').children(':eq(0)').toggle(function(){
                    $('.phipTable tbody').children(':eq(0)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                    $('.phipTable tbody').children(':eq(1)').show();
                }, function(){
                    $('.phipTable tbody').children(':eq(0)').children(':eq(0)').html('<img src="/images/icons/animation/plus.png"/>');
                    $('.phipTable tbody').children(':eq(1)').hide();
                });

                $('.phipTable tbody').children(':eq(2)').toggle(function(){
                    $('.phipTable tbody').children(':eq(2)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                    $('.phipTable tbody').children(':eq(3)').show();
                }, function(){
                    $('.phipTable tbody').children(':eq(2)').children(':eq(0)').html('<img src="/images/icons/animation/plus.png"/>');
                    $('.phipTable tbody').children(':eq(3)').hide();

                });

                $('.phipTable tbody').children(':eq(4)').toggle(function(){
                    $('.phipTable tbody').children(':eq(4)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                    $('.phipTable tbody').children(':eq(5)').show();
                    $('.phipTable tbody').children(':eq(6)').show();
                    $('.phipTable tbody').children(':eq(7)').show();
//                    $('.phipTable tbody').children(':eq(8)').show(); 
                }, function(){
                    $('.phipTable tbody').children(':eq(4)').children(':eq(0)').html('<img src="/images/icons/animation/plus.png"/>');
                    $('.phipTable tbody').children(':eq(5)').hide();
                    $('.phipTable tbody').children(':eq(6)').hide();
                    $('.phipTable tbody').children(':eq(7)').hide();
//                    $('.phipTable tbody').children(':eq(8)').hide();                                                           
                });

                $('.phipTable tbody').children(':eq(9)').toggle(function(){
                    $('.phipTable tbody').children(':eq(9)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                    $('.phipTable tbody').children(':eq(10)').show();
                    $('.phipTable tbody').children(':eq(11)').show();
                    $('.phipTable tbody').children(':eq(12)').show();
                    $('.phipTable tbody').children(':eq(13)').show();
                    $('.phipTable tbody').children(':eq(14)').show();
                    $('.phipTable tbody').children(':eq(15)').show();
                    $('.phipTable tbody').children(':eq(16)').show();
                    $('.phipTable tbody').children(':eq(17)').show();
                    $('.phipTable tbody').children(':eq(18)').show();
                    $('.phipTable tbody').children(':eq(19)').show();
                    $('.phipTable tbody').children(':eq(20)').show();
                    $('.phipTable tbody').children(':eq(21)').show();
                    $('.phipTable tbody').children(':eq(22)').show();
                    $('.phipTable tbody').children(':eq(23)').show();
                    $('.phipTable tbody').children(':eq(24)').show();
                    $('.phipTable tbody').children(':eq(25)').show();
//                    $('.phipTable tbody').children(':eq(26)').show();  
                }, function(){
                    $('.phipTable tbody').children(':eq(9)').children(':eq(0)').html('<img src="/images/icons/animation/plus.png"/>');
                    $('.phipTable tbody').children(':eq(10)').hide();
                    $('.phipTable tbody').children(':eq(11)').hide();
                    $('.phipTable tbody').children(':eq(12)').hide();
                    $('.phipTable tbody').children(':eq(13)').hide();
                    $('.phipTable tbody').children(':eq(14)').hide();
                    $('.phipTable tbody').children(':eq(15)').hide();
                    $('.phipTable tbody').children(':eq(16)').hide();
                    $('.phipTable tbody').children(':eq(17)').hide();
                    $('.phipTable tbody').children(':eq(18)').hide();
                    $('.phipTable tbody').children(':eq(19)').hide();
                    $('.phipTable tbody').children(':eq(20)').hide();
                    $('.phipTable tbody').children(':eq(21)').hide();
                    $('.phipTable tbody').children(':eq(22)').hide();
                    $('.phipTable tbody').children(':eq(23)').hide();
                    $('.phipTable tbody').children(':eq(24)').hide();
                    $('.phipTable tbody').children(':eq(25)').hide();
//                    $('.phipTable tbody').children(':eq(26)').hide();
                });

                $('.phipTable tbody').children(':eq(27)').toggle(function(){
                    $('.phipTable tbody').children(':eq(27)').children(':eq(0)').html('<img src="/images/icons/animation/minus.png"/>');
                    $('.phipTable tbody').children(':eq(28)').show();
                    $('.phipTable tbody').children(':eq(29)').show();
                    $('.phipTable tbody').children(':eq(30)').show();
                    $('.phipTable tbody').children(':eq(31)').show();
                    $('.phipTable tbody').children(':eq(32)').show();
//                    $('.phipTable tbody').children(':eq(33)').show();  

                }, function(){
                    $('.phipTable tbody').children(':eq(27)').children(':eq(0)').html('<img src="/images/icons/animation/plus.png"/>');
                    $('.phipTable tbody').children(':eq(28)').hide();
                    $('.phipTable tbody').children(':eq(29)').hide();
                    $('.phipTable tbody').children(':eq(30)').hide();
                    $('.phipTable tbody').children(':eq(31)').hide();
                    $('.phipTable tbody').children(':eq(32)').hide();
//                    $('.phipTable tbody').children(':eq(33)').hide();
                });

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


        <p><a href="/compliance_programs/localAction?id=686&local_action=previous_reports">Previous Year Scorecards</a></p>

        <p>Hello <?php echo $_user->getFullName(); ?>,</p>

        <p>Welcome to The SBMF Wellness Website! This site was developed to:</p>

        <ol>
            <li>Track your wellness activity</li>
            <li>Act as a great resource for health related topics and questions</li>
        </ol>

        <p id="show_more_steps"><a href="#">More...</a></p>

        <div id="steps">
            <p>By completing the following steps in 2016 you will be eligible for the wellness incentive offered in 2017:</p>

            <p><strong>Step 1</strong>: Complete your physical exam, HRA, and blood draw by August 31, 2016.</p>

            <p><strong>Step 2</strong>: You will again have the opportunity to earn incentive points in 2016. You will need to obtain a minimum of 9
                incentive points to be eligible for the wellness incentive offered in 2017. You are not required to meet the target range for each individual
                measure. The criteria for meeting these ranges is listed below in your report card. If you have a medical reason for not being able to
                reach one or more of the requirements listed below, you can complete the online eLearning solution assigned programs to obtain points
                or submit a medical exception form to Circle Wellness. As long as you accumulate a total of 9 points by 10/31/2016, you will be eligible
                for the wellness incentive offered for the 2017 plan year.
            </p>
        </div>

        <div id="show_more_sub_steps"><a href="#">More...</a></div>
        <div id="sub_steps">

            <p>Incentive Points can be earned in each of the following categories: </p>
            <ol>
                <li><strong>Smoking Status</strong>: Maintain non-smoker status or participate in the Quit for Life Smoking Cessation program.
                </li>
                <li><strong>Health Profile Measurements</strong>: Point values are assigned below in your report card for each measure. Meet the
                    requirements for the recommended ranges for your screening tests to earn incentive points. Two(2) points can be earned for results
                    within normal ranges and one(1) point for results within borderline ranges. If you obtain no points in one of the Health Profile Measurements,
                    you can complete the four online eLearning modules assigned to the measurement to obtain one point, or submit a medical exception form to
                    Circle Wellness and obtain two points. For the BMI measurement, bonus points can be earned by maintaining or reducing your BMI from your
                    2015 screening.
                </li>

            </ol>
        </div>

        <p><a href="compliance_programs?id=689">Click Here to View My Incentive Activities Page</a></p>



        <?php
    }

    public function printClientNotice()
    {
        $user = sfContext::getInstance()->getUser()->getUser();
        ?>
        Your scorecard will be updated throughout the year as incentive points are earned. Smoking status and Health Profile measurements will not be updated until completion of the August 2016 screenings. You may use your 2015 health screening results to give you an indication of your current potential to earn incentive points.
        <br/>
            <?php if($user->gender == Gender::FEMALE) : ?>
              <p><a href="/resources/7235/SBMF Verifcation Exception Form - 2016.pdf">Are you pregnant?</a></p>
            <?php endif ?>
           
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
            A Physical exam/biometric screening is required for the wellness incentive offered in 2017. Points will be
            rewarded for completing the annual physical exam and successfully meeting the biometric benchmarks.
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

            .group-incentive-activities-points {
                display:none;
            }
        </style>
        <?php
    }

    protected function printViewNumber(ComplianceView $view)
    {
        return !$view->getAttribute('skip_view_number');
    }
}