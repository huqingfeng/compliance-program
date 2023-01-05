<?php
class Midland2014TobaccoFormComplianceView extends ComplianceView
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
class Midland2014ComplianceProgram extends ComplianceProgram
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
        $coreGroup = new ComplianceViewGroup('core', 'Areas A-D are required and must be completed by the December 31st, 2014 deadline.');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Onsite Wellness Screening');
        $screeningView->setAttribute('report_name_link', '/content/1094#1ascreen');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Power Assessment');
        $hraView->setAttribute('report_name_link', '/content/1094#1bhpa');
        $coreGroup->addComplianceView($hraView);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Have a Main Doctor');
        $doctorInformationView->setAttribute('report_name_link', '/content/1094#1cdoc');
        $coreGroup->addComplianceView($doctorInformationView);

        $this->addComplianceViewGroup($coreGroup);

        $contactInformationView = new UpdateContactInformationComplianceView($programStart, $programEnd);
        $contactInformationView->setReportName('Enter Key Contact Information');
        $coreGroup->addComplianceView($contactInformationView);        
        
        // Build the extra group

        $numbers = new ComplianceViewGroup('points', 'Gain points through actions taken in option areas E-K below by the December 31st, 2014 deadline. ');
        
        $screeningTestMapper = new ComplianceStatusPointMapper(50, 25, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 6);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->overrideTestRowData(0, 0, 199.999, 239.999);
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setAttribute('elearning_alias', 'cholesterol');
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->overrideTestRowData(0, 0, 4.5, 4.5);
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($hdlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAttribute('elearning_alias', 'cholesterol');
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->overrideTestRowData(0, 0, 149.999, 199.999);
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAttribute('elearning_alias', 'blood_sugars');
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(0, 0, 99.999, 125.999);
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAttribute('elearning_alias', 'blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 119.999, 139.999);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 79.999, 89.999);
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setEitherNonCompliantYieldsNonCompliant(true);
        $numbers->addComplianceView($bloodPressureView);

        $BMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Mass Index (BMI)');
        $BMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $BMIView->setAttribute('elearning_alias', 'body_fat');
        $BMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $BMIView->overrideTestRowData(0, 0, 24.899, 29.999);
        $BMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $numbers->addComplianceView($BMIView);

        $nonSmokerView = new Midland2014TobaccoFormComplianceView();
        $nonSmokerView->setAttribute('report_name_link', '/content/1094#2enonsmoke');        
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($nonSmokerView);
        
        $elearn = new CompleteRequiredELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('Complete recommended e-learning lessons');
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
        
        $blueCrossBlueShield = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 342, 25);
        $blueCrossBlueShield->setMaximumNumberOfPoints(25);
        $blueCrossBlueShield->setReportName('Register with Blue Cross Blue Shield’s Blue Access for Members');
        $blueCrossBlueShield->addLink(new Link('BCBS', 'http://www.bcbsil.com/member'));
        $numbers->addComplianceView($blueCrossBlueShield);
        
        $numbers->setPointsRequiredForCompliance(100);

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
            $printer = new Midland2014ComplianceProgramReportPrinter();
        }

        return $printer;
    }
    
    public function loadCompletedLessons($status, $user)
    {
        if($alias = $status->getComplianceView()->getAttribute('elearning_alias')) {
            $view = $this->getAlternateElearningView($status->getComplianceView()->getComplianceViewGroup(), $alias);

            $status->setAttribute(
                'elearning_lessons_completed',
                count($view->getStatus($user)->getAttribute('lessons_completed'))
            );
        }

        if($status->getComment() == '') {
            $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }
    }
    
     private function getAlternateElearningView($group, $alias)
    {
        $view = new CompleteELearningGroupSet($this->getStartDate(), $this->getEndDate(), $alias);

        $view->useAlternateCode(true);

        // These are "optional" - can't be completed for credit

        $view->setNumberRequired(999);

        $view->setComplianceViewGroup($group);

        return $view;
    }   
}


class Midland2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
     ?>

     <script type="text/javascript">     
         $(function() {
             $('.headerRow-points').next().children(':eq(0)').html('<strong>E.</strong> Biometric Results');
             $('.view-non_smoker_view').children(':eq(0)').html('<strong>F.</strong> <a href="/content/1094#2enonsmoke">Non Smoker</a>');
             $('.view-complete_elearning_required').children(':eq(0)').html('<strong>G.</strong> <a href="/content/1094#2felearn">Recommended e-Learning Lessons</a>');
             $('.view-activity_21').children(':eq(0)').html('<strong>H.</strong> <a href="/content/1094#2gphysact">Regular Physical Activity</a>');
             $('.view-midland_get_fit_challenge').children(':eq(0)').html('<strong>I.&nbsp;</strong> <a href="/content/1094#2hmidlandchall">Participate in the Midland Get Fit Challenge</a>');
             $('.view-activity_24').children(':eq(0)').html('<strong>J.</strong> <a href="/content/1094#2ivol">Regular Volunteering</a>');
             $('.view-place_holder_Get').children(':eq(0)').html('<strong>K.</strong> <a href="/content/1094#2jexam">Get an Annual Physical Exam</a>');
             $('.view-activity_342').children(':eq(0)').html('<strong>L.</strong> <a href="content/1094#2bluecross">Register with Blue Cross Blue Shield’s Blue Access for Members</a>');
             $('.view-complete_hra').children(':eq(3)').html('<a href="content/989">Take HPA</a>');
         });
     </script>
     
     <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

     <p>Welcome to your summary page for the 2014 Midland Paper Wellness Rewards benefit.</p>

     <p>To receive the incentive, you MUST take action and meet all criteria below:</p>

    <ol>
        <li>Complete <strong>ALL</strong> of the core required actions by December 31st, 2014.</li>
        <li>Get 400 or more points from key screening results and key actions taken for good health.
        </li>
    </ol>

    <p>Employees and eligible spouses meeting all criteria will each receive: $40 per month for employees electing
        employee only coverage and $50 per month for employees electing employee plus spouse, employee plus children, and family coverage.</p>
    </p>

     <div class="pageHeading"><a href="/content/1094">Click here for more details about the 2014 Wellness Rewards
         benefit and requirements</a>.
     </div>
    <?php
    }
    
    public function printReport(ComplianceProgramStatus $status)
    {        
        parent::printReport($status);
    }
}