<?php

class Perspectives2015CoachingView extends GraduateFromCoachingSessionComplianceView
{
    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/administration/userCenterLib.php', sfConfig::get('sf_root_dir'));

        if(validForCoaching($user)) {
            return parent::getStatus($user);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }
}

class Perspectives2015TobaccoFormComplianceView extends ComplianceView
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
        $record = $user->getNewestDataRecord('Perspectives_paper_tobacco_declaration');
        
        if($record->exists() && $record->agree) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 25);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}

class PerspectivesDemoOne2015ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'Requirement');


        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Employee completes the Health Risk Assessment');
        //$hraView->setAttribute('report_name_link', '/content/1094#1bhpa');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Employee participates in the 2015 Wellness Screening');
        //$screeningView->setAttribute('report_name_link', '/content/1094#1ascreen');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        //$coachingView = new Perspectives2015CoachingView($programStart, $programEnd);
        //$coachingView->setReportName('Participate in intrinsic Health Coaching (for targeted individuals)');
        //$coachingView->setName('participate_coaching');
        //$coachingView->setAttribute('report_name_link', '/content/1094#1ascreen');
        //$coachingView->addLink(new Link('Learn More', '/content/1094#2bcoach'));
        //$coreGroup->addComplianceView($coachingView);

        $this->addComplianceViewGroup($coreGroup);


        // Build the extra group

        $numbers = new ComplianceViewGroup('points', 'Annual/Self-Care Wellness Activities');

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Have a Main Doctor');
        $doctorInformationView->setAttribute('requirements', 'Update Physicians info on website');
        //$doctorInformationView->setAttribute('report_name_link', '/content/1094#1cdoc');
        $doctorInformationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $numbers->addComplianceView($doctorInformationView);

        $preventiveScreeningsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($programStart, $programEnd, 10);
        $preventiveScreeningsView->configureActivity(259, 91, array(
            'Colonoscopy'   => 60,
            'Dental Exam'   => 24,
            'Mammogram'     => 24,
            'Pap Test'      => 36,
            'Physical Exam' => 36,
            'PSA Test'      => 60
        ));
        $preventiveScreeningsView->setReportName('Preventive Services');
        $preventiveScreeningsView->setAttribute('requirements', 'Receive a preventive service such as
                                            mammogram, prostate exam, eye & dental exams, colonoscopy,
                                            etc. Earn 10 points for each service or exam.');
        //$preventiveScreeningsView->setAttribute('report_name_link', '/content/1094#2jexam');
        $preventiveScreeningsView->setMaximumNumberOfPoints(20);
        $numbers->addComplianceView($preventiveScreeningsView);

        $recommendedImmunizationView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 413, 10);
        $recommendedImmunizationView->setReportName('Get Recommended Immunizations');
        $recommendedImmunizationView->setAttribute('requirements', 'Get a Flu shot, or other immunizations
                                            and earn 10 points each.');
        //$recommendedImmunizationView->setAttribute('report_name_link', '/content/1094#2jexam');
        $recommendedImmunizationView->setMaximumNumberOfPoints(20);
        $numbers->addComplianceView($recommendedImmunizationView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('Complete eLearning Lessons');
        $elearn->setName('complete_elearning_lessons');
        $elearn->setAttribute('requirements', 'Complete eLearning lessons and earn 5 points per lesson');
        //$elearn->setAttribute('report_name_link', '/content/1094#2felearn');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(75);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420'));
        $numbers->addComplianceView($elearn);

        $fitbitStepsView = new HmiRangeStepsComplianceView($programStart, $programEnd, 8000, 1);
        $fitbitStepsView->setReportName('Fitbit Steps');
        $fitbitStepsView->setName('fitbit_steps');
        $fitbitStepsView->setAttribute('requirements', 'Earn 1 point for each day you reach 8,000 steps per day');
        $fitbitStepsView->setMaximumNumberOfPoints(150);
        $fitbitStepsView->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $numbers->addComplianceView($fitbitStepsView);

        $healthCoachingView = new PlaceHolderComplianceView(null, 0);
        $healthCoachingView->setReportName('Health Coaching');
        $healthCoachingView->setName('health_coaching');
        $healthCoachingView->setAttribute('requirements', 'Earn 25 points per session');
        $healthCoachingView->addLink(new FakeLink('Admin Will Enter', '#'));
        $healthCoachingView->setMaximumNumberOfPoints(100);
        //$volunteeringView->setAttribute('report_name_link', '/content/1094#2ivol');
        $numbers->addComplianceView($healthCoachingView);

        $seminarView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 412, 10);
        $seminarView->setReportName('Misc. Health Events and Seminars');
        $seminarView->setName('seminars');
        $seminarView->setAttribute('requirements', 'Attend a company sponsored health event or seminar and earn 10 points.');
        //$seminarView->setAttribute('report_name_link', '/content/1094#2jexam');
        $seminarView->setMaximumNumberOfPoints(30);
        $numbers->addComplianceView($seminarView);

        $screeningTestMapper = new ComplianceStatusPointMapper(25, 0, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
//        $totalCholesterolView->setAttribute('_screening_printer_hack', 6);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setAttribute('elearning_lesson_id', '184');
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->overrideTestRowData(0, 0, 239.999, 239.999);
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $totalCholesterolView->setAttribute('requirements', 'Earn 25 points for each health measure in a desirable range');
        $totalCholesterolView->addLink(new Link('View Result', '/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=449'));
        $numbers->addComplianceView($totalCholesterolView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAttribute('elearning_lesson_id', '115');
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(0, 0, 125.999, 125.999);
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $glucoseView->setAttribute('requirements', 'Earn 25 points for each health measure in a desirable range');
        $glucoseView->addLink(new Link('View Result', '/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=449'));
        $numbers->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAttribute('elearning_lesson_id', '177');
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 139.999, 139.999);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 89.999, 89.999);
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setEitherNonCompliantYieldsNonCompliant(true);
        $bloodPressureView->setAttribute('requirements', 'Earn 25 points for each health measure in a desirable range');
        $bloodPressureView->addLink(new Link('View Result', '/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=449'));
        $numbers->addComplianceView($bloodPressureView);


        //$coachView = new GraduateFromCoachingSessionComplianceView($programStart, $programEnd);
        //$coachView->setName('work_with_health_coach_activity');
        //$coachView->setAttribute('requirements', 'Work with a health coach to achieve your health goals.');
        //$coachView->setReportName('Health Coaching');
        //$coachView->setAttribute('report_name_link', '/content/1094#1ascreen');
        //$coachView->addLink(new Link('Enter Information', 'content/8733'));
        //$coachView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(150, 0, 0, 0));
        //$numbers->addComplianceView($coachView);


        $numbers->setPointsRequiredForCompliance(100);

        $this->addComplianceViewGroup($numbers);

        $quarters = new ComplianceViewGroup('quarters', 'Tracking Quarterly Status');
        $quarters->setPointsRequiredForCompliance(200);

        $quarter1View = new CompleteHRAComplianceView('2015-01-15', '2015-03-31');
        $quarter1View->setReportName('Q1 - Jan15 - March 31');
        $quarter1View->setName('quarter1');
        $quarter1View->emptyLinks();
        $quarter1View->addLink(new Link(' Results', '/content/989'));
        $quarter1View->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $quarter1View->setAttribute('requirements', 'Complete HRA and Biometrics');
        $quarters->addComplianceView($quarter1View);

        $quarter2View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $quarter2View->setReportName('Q2 - April 1 - June 30');
        $quarter2View->setName('quarter2');
        $quarter2View->setAttribute('requirements', '150 Points Required over 2 Quarters');
        $quarter2View->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(150, 0, 0, 0));
        $quarters->addComplianceView($quarter2View);

        $quarter3View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $quarter3View->setReportName('Q3 - July 1 - September 30');
        $quarter3View->setName('quarter3');
        $quarter3View->setAttribute('requirements', '225 Points Required over 3 Quarters');
        $quarter3View->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(225, 0, 0, 0));
        $quarters->addComplianceView($quarter3View);

        $quarter4View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $quarter4View->setReportName('Q4 - Oct 1 - Dec 31');
        $quarter4View->setName('quarter4');
        $quarter4View->setAttribute('requirements', '300 Points Required over 4 Quarters');
        $quarter4View->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(300, 0, 0, 0));
        $quarters->addComplianceView($quarter4View);

        $this->addComplianceViewGroup($quarters);

    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $pointsGroupStatus = $status->getComplianceViewGroupStatus('points');
        $pointsGroupPoints = $pointsGroupStatus->getPoints();

        $quarter2Status = $status->getComplianceViewStatus('quarter2');
        if($pointsGroupPoints >= 150) {
            $quarter2Status->setStatus(ComplianceStatus::COMPLIANT);
        }

        $quarter3Status = $status->getComplianceViewStatus('quarter3');
        if($pointsGroupPoints >= 225) {
            $quarter3Status->setStatus(ComplianceStatus::COMPLIANT);
        }

        $quarter4Status = $status->getComplianceViewStatus('quarter4');
        if($pointsGroupPoints >= 300) {
            $quarter4Status->setStatus(ComplianceStatus::COMPLIANT);
        }
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
            $printer = new Perspectives2015ComplianceProgramReportPrinter();
            $printer->setShowPointBasedGroupTotal(true);
            $printer->setShowTotal(false);
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


class Perspectives2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        $quarterOneStatus = $status->getComplianceViewStatus('quarter1');
        $quarterTwoStatus = $status->getComplianceViewStatus('quarter2');
        $quarterThreeStatus = $status->getComplianceViewStatus('quarter3');
        $quarterFourStatus = $status->getComplianceViewStatus('quarter4');

     ?>

    <style type="text/css" >
        .callback {
            text-align: left;
        }
    </style>

     <script type="text/javascript">     
         $(function() {
            $('.callback').each(function(){
                $(this).css('text-align', 'left');
                $(this).css('padding-left', '5px');
            });

            $('.headerRow-core').children(':eq(1)').remove();
            $('.headerRow-core').children(':eq(0)').attr('colspan', 2);

            $('.view-complete_hra').children(':eq(1)').remove();
            $('.view-complete_hra').children(':eq(0)').attr('colspan', 2);

            $('.view-complete_screening').children(':eq(1)').remove();
            $('.view-complete_screening').children(':eq(0)').attr('colspan', 2);
            $('.view-complete_screening .result').html('01/27/2015');
            $('.view-complete_screening .status').html('<img src="/images/lights/greenlight.gif" class="light" alt="">') ;
            $('.view-update_doctor_information').children(':eq(2)').html('5');
             $('.view-activity_259').children(':eq(2)').html('10');
             $('.view-activity_413').children(':eq(2)').html('10');
             $('.view-complete_elearning_lessons').children(':eq(2)').html('45');
             $('.view-fitbit_steps').children(':eq(2)').html('50');
             $('.view-seminars').after('<tr><td><strong>H.</strong> Biometric Results</td><td colspan="4"></td></tr>');
             $('.view-comply_with_total_cholesterol_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15913">Total Cholesterol</a></span>');
             $('.view-comply_with_glucose_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15913">Glucose</a></span>');
             $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)').html('<span style="padding-left:16px;">• <a href="/sitemaps/health_centers/15913">Blood Pressure</a></span>');
             $('.view-comply_with_total_cholesterol_screening_test').children(':eq(2)').html('25');

             $('.view-comply_with_blood_pressure_screening_test').next().children(':eq(0)').after('<td></td>');
             $('.view-comply_with_blood_pressure_screening_test').next().children(':eq(2)').html('175');

             $('.view-quarter1').prev().children(':eq(3)').html('Status');
             $('.view-quarter1').children(':eq(2)').html('75');
             $('.view-quarter1').children(':eq(3)').html('<img src="/images/lights/greenlight.gif" class="light" alt="">');
             $('.view-quarter2').children(':eq(2)').html('150');
             $('.view-quarter2').children(':eq(3)').html('<img src="/images/lights/greenlight.gif" class="light" alt="">');
             $('.view-quarter3').children(':eq(3)').html('<img src="<?php echo $quarterThreeStatus->getLight() ?>" class="light" alt="">');
             $('.view-quarter4').children(':eq(3)').html('<img src="<?php echo $quarterFourStatus->getLight() ?>" class="light" alt="">');

             $('.view-quarter4').next().hide();


            $('.view-participate_coaching').children(':eq(1)').remove();
            $('.view-participate_coaching').children(':eq(0)').attr('colspan', 2);

            $('.headerRow-points').children(':eq(1)').html('Requirements');
            $('.headerRow-points').children(':eq(0)').css('width', '200px');

            $('.view-hra_points').next().children(':eq(0)').attr('colspan', 2);

         });
     </script>
     
     <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

     <p>
         Company X and Perspectives care about your health. Together they are introducing a new Living Well program to help you on your path to better health and wellbeing.
     </p>


    <p>To participate in the program you must complete the online HRA and receive a wellness screening. You will then be eligible to earn points starting
        January 15, 2015 through December 31, 2015 for various activities based on the chart. You will be required to complete the HRA questionnaire in Quarter
        1 for 75 points. You will then be required to earn 75 points each quarter thereafter (Q2, Q3, Q4) in order to continue achieving the incentive.
        You will be required to earn 300 points by the Q4 deadline.
    </p>

    <?php
    }
    
    public function printReport(ComplianceProgramStatus $status)
    {
        $program = $status->getComplianceProgram();

        $user = $status->getUser();
        $this->pageHeading = 'Healthy Activity Tracker';

        $this->addStatusCallbackColumn('Requirements', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();
            return $view->getAttribute('requirements');
        });

        parent::printReport($status);
    }
}