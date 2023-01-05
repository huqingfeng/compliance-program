<?php
class Midland2016TobaccoFormComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->start_date = $startDate;
        $this->end_date = $endDate;
    }

    public function getDefaultName()
    {
        return 'non_smoker_view';
    }

    public function getDefaultReportName()
    {
        return 'Non Smoker';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('midland_paper_tobacco_declaration');

        if($record->exists()
            && $record->agree
            && date('Y-m-d', strtotime($record->date)) >=  date('Y-m-d', $this->start_date)
            && date('Y-m-d', strtotime($record->date)) <=  date('Y-m-d', $this->end_date)) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 25);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}
class Midland2016ComplianceProgram extends ComplianceProgram
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

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'Areas A-B are required in order to receive the BASIC discount.');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Wellness Screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Complete the Health Power Assessment');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $numbers = new ComplianceViewGroup('points', 'Gain 350 points through actions taken in option areas C-L below by the Jan 31, 2017 deadline in order to receive the PREMIUM discount.');

        $screeningTestMapper = new ComplianceStatusPointMapper(50, 0, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
//        $totalCholesterolView->setAttribute('_screening_printer_hack', 6);
        $totalCholesterolView->setAttribute('elearning_lesson_id', '789');
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->overrideTestRowData(0, 0, 239.999, 239.999);
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setAttribute('elearning_lesson_id', '595');
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->overrideTestRowData(0, 0, 4.499, 4.499);
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($hdlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setAttribute('elearning_lesson_id', '99');
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->overrideTestRowData(0, 0, 199.999, 199.999);
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setAttribute('elearning_lesson_id', '105');
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(0, 0, 124.999, 124.999);
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setAttribute('elearning_lesson_id', '1309');
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 139.999, 139.999);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 89.999, 89.999);
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setEitherNonCompliantYieldsNonCompliant(true);
        $numbers->addComplianceView($bloodPressureView);

        $BMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Mass Index (BMI) < 30');
        $BMIView->setAttribute('elearning_lesson_id', '1118');
        $BMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $BMIView->overrideTestRowData(0, 0, 29.999, 29.999);
        $BMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($BMIView);

        $fluVaccineView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $numbers->addComplianceView($fluVaccineView);

        $nonSmokerView = new Midland2016TobaccoFormComplianceView($programStart, $programEnd);
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($nonSmokerView);

        $ineligibleLessonIDs = array(789, 595, 99, 105, 1309, 1118);
        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd, null, null, $ineligibleLessonIDs);
        $elearn->setReportName('Complete e-Learning Lessons - 25 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setPointsPerLesson(25);
        $elearn->setMaximumNumberOfPoints(75);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $numbers->addComplianceView($elearn);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each hour of physical activity');
        $physicalActivityView->setMaximumNumberOfPoints(250);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $numbers->addComplianceView($physicalActivityView);

        $midlandFitChallengeView = new PlaceHolderComplianceView(null, 0);
        $midlandFitChallengeView->setName('midland_get_fit_challenge');
        $midlandFitChallengeView->setReportName('Participate in Midland Get Fit Challenge');
        $midlandFitChallengeView->addLink(new Link('More Info', '/content/get-fit-challenge'));
        $midlandFitChallengeView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($midlandFitChallengeView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setReportName('Regular Volunteering – 5 pts for each hour of volunteering');
        $volunteeringView->setMinutesDivisorForPoints(12);
        $volunteeringView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($volunteeringView);

        $preventiveScreeningsView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 452, 25);
        $preventiveScreeningsView->setReportName('Get an Annual Physical Exam');
        $preventiveScreeningsView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($preventiveScreeningsView);

        $midlandPaper = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 424, 25);
        $midlandPaper->setMaximumNumberOfPoints(25);
        $midlandPaper->setReportName('Attend a Midland Paper Sponsored Wellness Event');
        $numbers->addComplianceView($midlandPaper);

        $blueCrossBlueShield = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 342, 10);
        $blueCrossBlueShield->setMaximumNumberOfPoints(10);
        $blueCrossBlueShield->setReportName('Register with Blue Cross Blue Shield’s Blue Access for Members');
        $blueCrossBlueShield->addLink(new Link('BCBS', 'http://www.bcbsil.com/member'));
        $numbers->addComplianceView($blueCrossBlueShield);

        $numbers->setPointsRequiredForCompliance(350);

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
            $printer = new Midland2016ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function loadCompletedLessons($status, $user)
    {
        if($elearningLessonId = $status->getComplianceView()->getAttribute('elearning_lesson_id')) {
            $elearningView = new CompleteELearningLessonComplianceView($this->getStartDate(), $this->getEndDate(), new ELearningLesson_v2($elearningLessonId));

            $elearningStatus = $elearningView->getStatus($user);
            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        }
    }
}


class Midland2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>

     <script type="text/javascript">     
         $(function() {
             $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

            $('.headerRow-points').after('<tr><td><strong>C.</strong> Biometric Results: Gain points by having biometrics in “healthy ranges” OR complete related elearning lesson to receive full points.</td><td></td><td></td><td></td></tr>');
            $('.view-comply_with_total_cholesterol_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Total Cholesterol < 240</span>');
            $('.view-comply_with_total_cholesterol_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=698">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=4401">Complete 1 Blood Fat Lesson</a>');
            $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Total/HDL Cholesterol Ratio < 4.5</span>');
             $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=698">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=4190">Complete 1 Blood Fat Lesson</a>');
            $('.view-comply_with_triglycerides_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Triglycerides < 200</span>');
             $('.view-comply_with_triglycerides_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=698">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3638">Complete 1 Blood Fat Lesson</a>');
            $('.view-comply_with_glucose_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Glucose < 125</span>');
             $('.view-comply_with_glucose_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=698">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=4015">Complete 1 Blood Sugar Lesson</a>');
            $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Blood Pressure < 140/90 (both numbers)</span>');
             $('.view-comply_with_blood_pressure_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=698">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=5028">Complete 1 BP Lesson</a>');
            $('.view-comply_with_bmi_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• Body Mass Index (BMI) < 30</span>');
             $('.view-comply_with_bmi_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=698">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=4769">Complete 1 Body Metric Lesson</a>');


             $('.view-flu_vaccine').children(':eq(0)').html('<strong>D.</strong> Annual Flu Vaccine');
             $('.view-non_smoker_view').children(':eq(0)').html('<strong>E.</strong> Non-Smoker');
             $('.view-elearning').children(':eq(0)').html('<strong>F.</strong> Complete e-Learning Lessons - 25 pts for each lesson done');
             $('.view-activity_21').children(':eq(0)').html('<strong>G.</strong> Regular Physical Activity - 1 pt for each hour of physical activity');
             $('.view-midland_get_fit_challenge').children(':eq(0)').html('<strong>H.&nbsp;</strong> Participate in Midland Get Fit Challenge');
             $('.view-activity_24').children(':eq(0)').html('<strong>I.</strong> Regular Volunteering – 5 pts for each hour of volunteering');
             $('.view-activity_452').children(':eq(0)').html('<strong>J.</strong> Get an Annual Physical Exam');
             $('.view-activity_424').children(':eq(0)').html('<strong>K.</strong> Attend a Midland Paper Sponsored Wellness Event');
             $('.view-activity_342').children(':eq(0)').html('<strong>L.</strong> Register with Blue Cross Blue Shield’s Blue Access for Members');
             <?php if(sfConfig::get('app_wms2')) : ?>
                 $('.view-complete_hra').children(':eq(3)').html('<a href="/compliance/hmi-2016/my-health">Take HPA</a>');
             <?php else : ?>
                $('.view-complete_hra').children(':eq(3)').html('<a href="content/989">Take HPA</a>');
             <?php endif ?>
         });
     </script>
     
     <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

     <p>Welcome to your summary page for the 2016 Midland Paper Wellness Rewards benefit.</p>

     <p>This year, you have the opportunity to earn a basic discount or a premium discount. The deadline to complete all actions is Jan. 31, 2017.</p>

     <p>
         To receive the basic discount, you MUST complete the annual wellness screening and complete the Health Power Assessment (Section 1
        below). Employees and eligible spouses meeting this criteria will each receive: $40 per month for employees electing employee only
        coverage and $50 per month for employees electing employee plus spouse, employee plus children and family coverage.
    </p>


    <p>
        To receive the premium discount, you MUST complete the annual wellness screening, complete the Health Power Assessment AND earn
        350 or more points from key screening results and key actions taken for good health (Section 1 & 2 below). Employees and eligible spouses
        meeting this criteria will each receive: a total of $65 per month for employees electing employee only coverage and a total of $90 per
        month for employees electing employee plus spouse, employee plus children and family coverage.
    </p>

    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
    }
}