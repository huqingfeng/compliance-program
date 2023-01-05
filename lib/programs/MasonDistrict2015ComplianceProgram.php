<?php

class MasonDistrict2015CoachingView extends GraduateFromCoachingSessionComplianceView
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

class MasonDistrict2015TobaccoFormComplianceView extends ComplianceView
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
        $record = $user->getNewestDataRecord('MasonDistrict_paper_tobacco_declaration');
        
        if($record->exists() && $record->agree) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 25);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}
class MasonDistrict2015ComplianceProgram extends ComplianceProgram
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

        //$coachingView = new MasonDistrict2015CoachingView($programStart, $programEnd);
        //$coachingView->setReportName('Participate in intrinsic Health Coaching (for targeted individuals)');
        //$coachingView->setName('participate_coaching');
        //$coachingView->setAttribute('report_name_link', '/content/1094#1ascreen');
        //$coachingView->addLink(new Link('Learn More', '/content/1094#2bcoach'));
        //$coreGroup->addComplianceView($coachingView);

        $this->addComplianceViewGroup($coreGroup);


        // Build the extra group

        $numbers = new ComplianceViewGroup('points', 'Annual/Self-Care Wellness Activities');

        $customizedStartDate = '2015-01-01';
        $customizedEndDate = '2015-04-20';

        $doctorInformationView = new UpdateDoctorInformationComplianceView($customizedStartDate, $customizedEndDate);
        $doctorInformationView->setReportName('Have a Main Doctor');
        $doctorInformationView->setAttribute('requirements', 'Update Physicians info on website');
        //$doctorInformationView->setAttribute('report_name_link', '/content/1094#1cdoc');
        $doctorInformationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $doctorInformationView->emptyLinks();
        $doctorInformationView->addLink(new FakeLink('Entry Period Over', '#'));
        $numbers->addComplianceView($doctorInformationView);

        $preventiveScreeningsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($customizedStartDate, $customizedEndDate, 10);
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
        $preventiveScreeningsView->emptyLinks();
        $preventiveScreeningsView->addLink(new FakeLink('Entry Period Over', '#'));
        $numbers->addComplianceView($preventiveScreeningsView);

        $recommendedImmunizationView = new CompleteArbitraryActivityComplianceView($customizedStartDate, $customizedEndDate, 413, 10);
        $recommendedImmunizationView->setReportName('Get Recommended Immunizations');
        $recommendedImmunizationView->setAttribute('requirements', 'Get a Flu shot, or other immunizations
                                            and earn 10 points each.');
        //$recommendedImmunizationView->setAttribute('report_name_link', '/content/1094#2jexam');
        $recommendedImmunizationView->setMaximumNumberOfPoints(20);
        $recommendedImmunizationView->emptyLinks();
        $recommendedImmunizationView->addLink(new FakeLink('Entry Period Over', '#'));
        $numbers->addComplianceView($recommendedImmunizationView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('Complete eLearning Lessons');
        $elearn->setAttribute('requirements', 'Completion of two (2) Perspectives online Health eLearning Skill Builder courses and earn 37.5 points each');
        //$elearn->setAttribute('report_name_link', '/content/1094#2felearn');
        $elearn->setPointsPerLesson(37.5);
        $elearn->setMaximumNumberOfPoints(75);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420'));
        $numbers->addComplianceView($elearn);

        $fitnessClassesView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 430, 150);
        $fitnessClassesView->setReportName('Havana Park District Fitness classes');
        $fitnessClassesView->setAttribute('requirements', 'Participation in Havana Park District Fitness classes including: Barbell, FIT, HIT, or Stretchercise');
        $fitnessClassesView->setMaximumNumberOfPoints(150);
        $numbers->addComplianceView($fitnessClassesView);

        $volunteeringView = new VolunteeringComplianceView($customizedStartDate, $customizedEndDate);
        $volunteeringView->setReportName('Regular Volunteering');
        $volunteeringView->setAttribute('requirements', 'Volunteer your time and earn 5 points for each hour ');
        //$volunteeringView->setAttribute('report_name_link', '/content/1094#2ivol');
        $volunteeringView->setMinutesDivisorForPoints(12);
        $volunteeringView->setMaximumNumberOfPoints(100);
        $volunteeringView->emptyLinks();
        $volunteeringView->addLink(new FakeLink('Entry Period Over', '#'));
        $numbers->addComplianceView($volunteeringView);

        $seminarView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 431, 75);
        $seminarView->setReportName('MDH sponsored seminar');
        $seminarView->setName('seminars');
        $seminarView->setAttribute('requirements', 'Attend a MDH sponsored seminar and earn 75 points');
        //$seminarView->setAttribute('report_name_link', '/content/1094#2jexam');
        $seminarView->setMaximumNumberOfPoints(75);
        $numbers->addComplianceView($seminarView);

        $hraPointsView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraPointsView->setReportName('Employee completes the Health Risk Assessment');
        $hraPointsView->setName('hra_points');
        $hraPointsView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        //$hraPointsView->setAttribute('report_name_link', '/content/1094#1bhpa');
        $hraPointsView->emptyLinks();
        $hraPointsView->addLink(new Link('Take HRA', '/content/989'));
        $numbers->addComplianceView($hraPointsView);

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

        $quarter1View = new CompleteHRAComplianceView('2015-01-15', '2015-03-31');
        $quarter1View->setReportName('Q1 - Jan15 - March 31');
        $quarter1View->setName('quarter1');
        $quarter1View->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $quarter1View->emptyLinks();
        $quarter1View->addLink(new Link('Take HRA', '/content/1006'));
        $quarter1View->addLink(new Link(' Results', '/content/989'));
        $quarter1View->setAttribute('requirements', 'Completion of HRA (75 points)');
        $quarters->addComplianceView($quarter1View);

        $quarter2View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $quarter2View->setReportName('Q2 - April 1 - June 30');
        $quarter2View->setName('quarter2');
        $quarter2View->setAttribute('requirements', '75 Points Required');
        $quarters->addComplianceView($quarter2View);

        $quarter3View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $quarter3View->setReportName('Q3 - July 1 - September 30');
        $quarter3View->setName('quarter3');
        $quarter3View->setAttribute('requirements', '75 Points Required');
        $quarters->addComplianceView($quarter3View);

        $quarter4View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $quarter4View->setReportName('Q4 - Oct 1 - Dec 31');
        $quarter4View->setName('quarter4');
        $quarter4View->setAttribute('requirements', '75 Points Required');
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
            $printer = new MasonDistrict2015ComplianceProgramReportPrinter();
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


class MasonDistrict2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
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

            $('.view-participate_coaching').children(':eq(1)').remove();
            $('.view-participate_coaching').children(':eq(0)').attr('colspan', 2);

            $('.headerRow-points').children(':eq(1)').html('Requirements');
            $('.headerRow-points').children(':eq(0)').css('width', '200px');

            $('.view-work_with_health_coach_activity').next().children(':eq(0)').attr('colspan', 2);
            $('.view-work_with_health_coach_activity').next().children(':eq(0)').html('My Total Points :');
            $('.view-work_with_health_coach_activity').next().children(':eq(0)').css('text-align', 'center');

            $('.view-hra_points').next().children(':eq(0)').attr('colspan', 2);
            $('.view-hra_points').next().children(':eq(2)').html('300');

         });
     </script>
     
     <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

     <p>
         Mason District Hospital and Perspectives care about your health. Together they are introducing a new Living Well program to help you on your path to better health and wellbeing.
     </p>


    <p>To participate in the program you must complete the online HRA and receive a wellness screening. You will then be eligible to earn points starting
        January 15, 2015 through December 31, 2015 for various activities based on the chart. You will be required to complete the HRA questionnaire in Quarter
        1 for 75 points. You will then be required to earn 75 points each quarter thereafter (Q2, Q3, Q4) in order to continue achieving the incentive”.
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