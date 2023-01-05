<?php

class JustriteManufacturingAlternativeComplianceStatusMapper extends ComplianceStatusMapper
{
    public function __construct(array $mappings = array())
    {
        $this->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Criteria Met', '/images/lights/greenlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Started', '/images/lights/redlight.gif')
        ));
    }
}


class JustriteManufacturing2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new JustriteManufacturingAlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'Areas A-C are required in order to receive any premium discount.');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Wellness Screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Complete the Health Power Assessment');
        $coreGroup->addComplianceView($hraView);

        $rasPhysician = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $rasPhysician->setReportName('Complete RAS Physician Verification Form <br /><span style="padding-left:16px;font-size:9pt;">• Only required for those who did not initially earn the maximum 6 points.</span>');
        $rasPhysician->setName('ras_physician');
        $coreGroup->addComplianceView($rasPhysician);

        $this->addComplianceViewGroup($coreGroup);

        $numbers = new ComplianceViewGroup('points', 'Gain points through areas D-E below by the October 5, 2016 deadline in order to receive a premium discount.');

        $screeningTestMapper = new ComplianceStatusPointMapper(1, 0, 0, 0);

        $BMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Composition (BMI) < 28');
        $BMIView->setAttribute('elearning_lesson_id', '180');
        $BMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $BMIView->overrideTestRowData(0, 0, 27.999, 27.999);
        $BMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($BMIView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setAttribute('elearning_lesson_id', '177');
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 139.999, 139.999);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 89.999, 89.999);
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setEitherNonCompliantYieldsNonCompliant(true);
        $numbers->addComplianceView($bloodPressureView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setAttribute('elearning_lesson_id', '115');
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(0, 0, 109.999, 109.999);
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($glucoseView);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setAttribute('elearning_lesson_id', '184');
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->overrideTestRowData(0, 0, 199.999, 199.999);
        $totalCholesterolView->setPostEvaluateCallback(function($status, $user) use ($programStart, $programEnd) {
            $hdlRationView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
            $hdlRationView->overrideTestRowData(0, 0, 4, 4);

            if($hdlRationView->getStatus($user)->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            if($elearningLessonId = $status->getComplianceView()->getAttribute('elearning_lesson_id')) {
                $elearningView = new CompleteELearningLessonComplianceView($this->getStartDate(), $this->getEndDate(), new ELearningLesson_v2($elearningLessonId));

                $status->setAttribute('original_points', $status->getPoints());
                $elearningStatus = $elearningView->getStatus($user);
                if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });
        $numbers->addComplianceView($totalCholesterolView);

        $nonSmokerView = new PlaceHolderComplianceView(null, 0);
        $nonSmokerView->setName('non_smoker');
        $nonSmokerView->setReportName('Non-Smoker - Affidavit');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->setMaximumNumberOfPoints(2);
        $nonSmokerView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $nonSmokerView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($programStart, $programEnd) {
            $elearningView = new CompleteELearningLessonComplianceView($programStart, $programEnd, new ELearningLesson_v2(202));

            $elearningStatus = $elearningView->getStatus($user);
            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setPoints($status->getComplianceView()->getMaximumNumberOfPoints());
            }
        });
        $numbers->addComplianceView($nonSmokerView);

        $numbers->setPointsRequiredForCompliance(0);

        $this->addComplianceViewGroup($numbers);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new ScreeningProgramReportPrinter();

            $query = ScreeningTable::getInstance()
                ->getScreeningsForUser($this->getActiveUser(), array('execute' => false));
            $screenings = ScreeningTable::getInstance()
                ->getScreeningForDates($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), array('query' => $query));
            $screening = $screenings->getFirst();

            if($screening) {
                $printer->setPageTitle('Points Earned From My Wellness Screening On '.$screening->getDate());
            } else {
                $printer->setPageTitle('You have not had a Wellness Screening.');
            }
        } else {
            $printer = new JustriteManufacturing2016ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function loadCompletedLessons($status, $user)
    {
        if($elearningLessonId = $status->getComplianceView()->getAttribute('elearning_lesson_id')) {
            $elearningView = new CompleteELearningLessonComplianceView($this->getStartDate(), $this->getEndDate(), new ELearningLesson_v2($elearningLessonId));

            $status->setAttribute('original_points', $status->getPoints());
            $elearningStatus = $elearningView->getStatus($user);
            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        }
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $numberStatus = $status->getComplianceViewGroupStatus('points');
        $rasPhysicianStatus = $status->getComplianceViewStatus('ras_physician');

        $biometricPoints = 0;
        foreach($numberStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getAttribute('original_points')) {
                $biometricPoints += $viewStatus->getAttribute('original_points');
            }
        }

        $nonSmokerStatus = $status->getComplianceViewStatus('non_smoker');
        if($biometricPoints >= 4 && $nonSmokerStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
            $rasPhysicianStatus->setStatus(ComplianceViewStatus::COMPLIANT);
        }

        $hraStatus = $status->getComplianceViewStatus('complete_hra');
        $screeningStatus = $status->getComplianceViewStatus('complete_screening');
        $rasPhysicianStatus = $status->getComplianceViewStatus('ras_physician');

        if($hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT
            && $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT
            && $rasPhysicianStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }
    }
}


class JustriteManufacturing2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <style type="text/css">
            #programTable {
                width:700px;
                border-collapse: collapse;
                margin:10px auto;
            }

            #programTable tr th, #programTable tr td{
                border:1px solid #26B000;
                text-align: center;
            }

            .phipTable th, .phipTable td {
                border: 1px solid #000000 !important;
                padding: 2px;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

                $('.headerRow-points').after('<tr><td><strong>D.</strong> Biometric Results: Gain points by having biometrics in “healthy ranges” OR complete related elearning lesson to receive full points.</td><td></td><td></td><td></td></tr>');

                $('.view-ras_physician .result').attr('colspan', 3);
                $('.view-ras_physician .result').html('<a href="/resources/7902/Justrite RAS Form 2016.pdf">RAS Physician Verification Form</a>');

                $('.view-ras_physician .status').remove();
                $('.view-ras_physician .links').remove();

                $('.view-comply_with_bmi_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Body Composition (BMI) < 28</span>');
                $('.view-comply_with_bmi_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=790">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3574">Complete 1 Body Metric Lesson</a>');

                $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Blood Pressure < 140/90 (both numbers)</span>');
                $('.view-comply_with_blood_pressure_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=790">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3572">Complete 1 BP Lesson</a>');

                $('.view-comply_with_glucose_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Blood Sugar < 110</span>');
                $('.view-comply_with_glucose_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=790">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3856">Complete 1 Blood Sugar Lesson</a>');

                $('.view-comply_with_total_cholesterol_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Total Cholesterol < 200 OR TC/HDL Ratio ≤ 4.0</span>');
                $('.view-comply_with_total_cholesterol_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=790">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3734">Complete 1 Blood Fat Lesson</a>');

                $('.view-non_smoker').children(':eq(0)').html('<strong>E.</strong> Non-Smoker - Affidavit');
                $('.view-non_smoker').children(':eq(3)').html('<a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3935">Complete 1 Tobacco Lesson </a>');

                $('.view-complete_hra').children(':eq(3)').html('<a href="content/989">Take HPA</a>');
            });
        </script>

        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>Welcome to your summary page for the 2016 Justrite Screening Criteria and Premium Discounts.
            <strong>The deadline to complete all actions is October 5th, 2016.</strong></p>

        <p>
            A medical premium discount is offered to those who: <br/>
            <ul>
                <li>Participate in the Wellness Screening</li>
                <li>Complete the Health Power Assessment (HPA)</li>
                <li>Receive at least one point through their biometric levels</li>
            </ul>
        </p>

        <p>
            <strong>OPTIONAL</strong><br/>
            If you did not receive the maximum amount of points (6) from your biometric levels and would like to, you MUST:
            <ul>
                <li>Complete the RAS Physician Verification Form</li>
                <li>Complete the eLearning lesson(s) associated with the biometric level that is out of range. </li>
            </ul>
        </p>

        <p>Please see the medical premium discount charts below:</p>

        <table id="programTable">
            <tr style="font-weight: bold;">
                <td colspan="4">Medical Premium Discounts</td>
            </tr>
            <tr style="font-weight: bold;">
                <td colspan="2">Single - EE / EE + Child(ren)</td>
                <td colspan="2">Family - Family / EE + Spouse</td>
            </tr>
            <tr>
                <th>Points Earned</th>
                <th>Premium Discount</th>
                <th>Points Earned</th>
                <th>Premium Discount</th>
            </tr>
            <tr>
                <td>1 of 6 points</td>
                <td>5%</td>
                <td>1-3 of 12 points</td>
                <td>5%</td>
            </tr>
            <tr>
                <td>2 of 6 points</td>
                <td>10%</td>
                <td>4-5 of 12 points</td>
                <td>10%</td>
            </tr>
            <tr>
                <td>3 of 6 points</td>
                <td>15%</td>
                <td>6-7 of 12 points</td>
                <td>15%</td>
            </tr>
            <tr>
                <td>4 of 6 points</td>
                <td>20%</td>
                <td>8-9 of 12 points</td>
                <td>20%</td>
            </tr>
            <tr>
                <td>5 of 6 points</td>
                <td>25%</td>
                <td>10-11 of 12 points</td>
                <td>25%</td>
            </tr>
            <tr>
                <td>6 of 6 points</td>
                <td>30%</td>
                <td>12 of 12 points</td>
                <td>30%</td>
            </tr>
        </table>
        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
    }
}