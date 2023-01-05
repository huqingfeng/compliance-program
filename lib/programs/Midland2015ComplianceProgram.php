<?php
class Midland2015TobaccoFormComplianceView extends ComplianceView
{
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
        
        if($record->exists() && $record->agree) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 25);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}
class Midland2015ComplianceProgram extends ComplianceProgram
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
        $coreGroup = new ComplianceViewGroup('core', 'Areas A-C are required and must be completed by your screening date.');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Wellness Screening');
        $screeningView->setAttribute('report_name_link', '/content/1094#1ascreen');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Power Assessment');
        $hraView->setAttribute('report_name_link', '/content/1094#1bhpa');
        $coreGroup->addComplianceView($hraView);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Have a Main Doctor');
        $doctorInformationView->setAttribute('report_name_link', '/content/1094#1cdoc');
        $doctorInformationView->emptyLinks();
        $doctorInformationView->addLink(new Link('Enter/Update Info', '/my_account/updateDoctor?redirect=/compliance_programs?id=444'));
        $coreGroup->addComplianceView($doctorInformationView);

        $this->addComplianceViewGroup($coreGroup);

        $numbers = new ComplianceViewGroup('points', 'Gain points through actions taken in option areas D-L below by the December 31st, 2015 deadline. ');
        
        $screeningTestMapper = new ComplianceStatusPointMapper(50, 0, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
//        $totalCholesterolView->setAttribute('_screening_printer_hack', 6);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setAttribute('elearning_lesson_id', '184');
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->overrideTestRowData(0, 0, 239.999, 239.999);
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setAttribute('elearning_lesson_id', '1011');
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->overrideTestRowData(0, 0, 4.5, 4.5);
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($hdlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAttribute('elearning_lesson_id', '1');
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->overrideTestRowData(0, 0, 199.999, 199.999);
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAttribute('elearning_lesson_id', '115');
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(0, 0, 125.999, 125.999);
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAttribute('elearning_lesson_id', '177');
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 139.999, 139.999);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 89.999, 89.999);
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setEitherNonCompliantYieldsNonCompliant(true);
        $numbers->addComplianceView($bloodPressureView);

        $BMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Mass Index (BMI)');
        $BMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $BMIView->setAttribute('elearning_lesson_id', '180');
        $BMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $BMIView->overrideTestRowData(0, 0, 29.999, 29.999);
        $BMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($BMIView);

        $fluVaccineView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setAttribute('report_name_link', '/content/1094#2eflushot');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $numbers->addComplianceView($fluVaccineView);

        $nonSmokerView = new Midland2015TobaccoFormComplianceView();
        $nonSmokerView->setAttribute('report_name_link', '/content/1094#2enonsmoke');        
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($nonSmokerView);

        $ineligibleLessonIDs = array(184, 1011, 1, 115, 177, 180);
        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd, null, null, $ineligibleLessonIDs);
        $elearn->setReportName('Complete e-Learning Lessons');
        $elearn->setName('elearning');
        $elearn->setAttribute('report_name_link', '/content/1094#2felearn');
        $elearn->setPointsPerLesson(25);
        $elearn->setMaximumNumberOfPoints(75);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420'));
        $numbers->addComplianceView($elearn);        

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#2gphysact');
        $physicalActivityView->setMaximumNumberOfPoints(250);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $numbers->addComplianceView($physicalActivityView);

        $midlandFitChallengeView = new PlaceHolderComplianceView(null, 0);
        $midlandFitChallengeView->setName('midland_get_fit_challenge');
        $midlandFitChallengeView->setAttribute('report_name_link', '/content/1094#2hmidlandchall');
        $midlandFitChallengeView->setReportName('Participate in the Midland Get Fit Challenge');
        $midlandFitChallengeView->addLink(new Link('More Info', '/content/get-fit-challenge'));
        $midlandFitChallengeView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($midlandFitChallengeView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setReportName('Regular Volunteering');
        $volunteeringView->setAttribute('report_name_link', '/content/1094#2ivol');
        $volunteeringView->setMinutesDivisorForPoints(12);
        $volunteeringView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($volunteeringView);

        $preventiveScreeningsView = new PlaceHolderComplianceView(null, 0);
        $preventiveScreeningsView->setReportName('Get an Annual Physical Exam');
        $preventiveScreeningsView->setAttribute('report_name_link', '/content/1094#2jexam');
        $preventiveScreeningsView->addLink(new Link('More Info', '/content/annual_physical'));
        $preventiveScreeningsView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($preventiveScreeningsView);        
        
        $midlandPaper = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 424, 25);
        $midlandPaper->setMaximumNumberOfPoints(25);
        $midlandPaper->setReportName('Attend a Midland Paper Sponsored Wellness Event');
        $midlandPaper->setAttribute('report_name_link', '/content/1094#2lwellnessEvent');
        $numbers->addComplianceView($midlandPaper);
        
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
            $printer = new Midland2015ComplianceProgramReportPrinter();
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


class Midland2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
     ?>

     <script type="text/javascript">     
         $(function() {
             $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

            $('.headerRow-points').after('<tr><td><strong>D.</strong> Biometric Results: Gain points by having biometrics in “healthy ranges” OR complete related elearning lesson to receive full points.</td><td></td><td></td><td></td></tr>');
            $('.view-comply_with_total_cholesterol_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15913">Total Cholesterol</a></span>');
            $('.view-comply_with_total_cholesterol_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=444">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3734">Complete 1 Blood Fat Lesson</a>');
            $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15913">Total/HDL Cholesterol Ratio</a></span>');
             $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=444">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=4638">Complete 1 Blood Fat Lesson</a>');
            $('.view-comply_with_triglycerides_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15913">Triglycerides</a></span>');
             $('.view-comply_with_triglycerides_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=444">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=1">Complete 1 Blood Fat Lesson</a>');
            $('.view-comply_with_glucose_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15401">Glucose</a></span>');
             $('.view-comply_with_glucose_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=444">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3856">Complete 1 Blood Sugar Lesson</a>');
            $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15919">Blood Pressure</a></span>');
             $('.view-comply_with_blood_pressure_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=444">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3572">Complete 1 BP Lesson</a>');
            $('.view-comply_with_bmi_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15932">Body Mass Index (BMI)</a></span>');
             $('.view-comply_with_bmi_screening_test').children(':eq(3)').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=444">View Result</a><br /><a href="/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id=3574">Complete 1 Body Metric Lesson</a>');


             $('.view-flu_vaccine').children(':eq(0)').html('<strong>E.</strong> <a href="/content/1094#2eflushot">Annual Flu Vaccine</a>');
             $('.view-non_smoker_view').children(':eq(0)').html('<strong>F.</strong> <a href="/content/1094#2enonsmoke">Non Smoker</a>');
             $('.view-elearning').children(':eq(0)').html('<strong>G.</strong> <a href="/content/1094#2felearn">Complete e-Learning Lessons</a>');
             $('.view-activity_21').children(':eq(0)').html('<strong>H.</strong> <a href="/content/1094#2gphysact">Regular Physical Activity</a>');
             $('.view-midland_get_fit_challenge').children(':eq(0)').html('<strong>I.&nbsp;</strong> <a href="/content/1094#2hmidlandchall">Participate in the Midland Get Fit Challenge</a>');
             $('.view-activity_24').children(':eq(0)').html('<strong>J.</strong> <a href="/content/1094#2ivol">Regular Volunteering</a>');
             $('.view-place_holder_Get').children(':eq(0)').html('<strong>K.</strong> <a href="/content/1094#2jexam">Get an Annual Physical Exam</a>');
             $('.view-activity_424').children(':eq(0)').html('<strong>L.</strong> <a href="content/1094#2lwellnessEvent">Attend a Midland Paper Sponsored Wellness Event</a>');
             $('.view-complete_hra').children(':eq(3)').html('<a href="content/989">Take HPA</a>');
         });
     </script>
     
     <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

     <p>Welcome to your summary page for the 2015 Midland Paper Wellness Rewards benefit.</p>

     <p>To receive the incentive, you MUST take action and meet all criteria below:</p>

    <ol>
        <li>Complete <strong>ALL</strong> of the required actions by your screening date.</li>
        <li>Get 350 or more points from key screening results and key actions taken for good health.
        </li>
    </ol>

    <p>Employees and eligible spouses meeting all criteria will each receive: $40 per month for employees electing
        employee only coverage and $50 per month for employees electing employee plus spouse, employee plus children, and family coverage.</p>
    </p>

     <div class="pageHeading"><a href="/content/1094">Click here for more details about the 2015 Wellness Rewards
         benefit and requirements</a>.
     </div>
    <?php
    }
    
    public function printReport(ComplianceProgramStatus $status)
    {        
        parent::printReport($status);
    }
}