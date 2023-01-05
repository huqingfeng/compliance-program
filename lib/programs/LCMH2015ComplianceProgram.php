<?php

class LCMH2015ElearningComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->startDate = ctype_digit($startDate) ? $startDate : strtotime($startDate);
        $this->endDate = ctype_digit($endDate) ? $endDate : strtotime($endDate);

        if($this->startDate === false) {
            throw new \InvalidArgumentException("Invalid start date: $startDate");
        }

        if($this->endDate === false) {
            throw new \InvalidArgumentException("Invalid end: $endDate");
        }
    }

    public function getStatus(User $user)
    {
        $elearningDimentions = array(
            'physical',
            'emotional',
            'financial',
            'spiritual',
            'environmental',
            'career',
            'community',
            'intellectual'
        );

        $points = 0;
        foreach($elearningDimentions as $elearningDimention) {
            $elearningComplianceView  = new CompleteELearningGroupSet($this->startDate, $this->endDate, $elearningDimention);
            $elearningComplianceView->setComplianceViewGroup($this->getComplianceViewGroup());
            $elearningComplianceView->setNumberRequired(1);
            if($elearningComplianceView->getStatus($user)->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $points += 5;
            }
        }


        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, $points);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultReportName()
    {
        return 'Weight';
    }

    public function getDefaultName()
    {
        return 'android_weight';
    }

    public function setAllowPointsOverride($boolean)
    {
        $this->allowPointsOverride = $boolean;
    }


    private $allowPointsOverride = null;
    private $startDate;
    private $endDate;
}


class LCMH32015ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new V32015ComplianceProgramReportPrinter();

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);
        $printer->setShowCompliant(false, null, null);
        $printer->setShowPoints(false,null,null);
        $printer->setShowComment(false,null,null);

        foreach($this->ranges as $name => $dates) {
            $printer->addStatusFieldCallback($name, function(ComplianceProgramStatus $status) use($name) {
                return $status->getComplianceViewStatus('walk_10k')->getAttribute($name);
            });
        }

        $printer->addStatusFieldCallback('Steps 05/13/2015 - 08/12/2015', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2015-05-13', '2015-08-12');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });

        $printer->addStatusFieldCallback('Manual Steps 05/13/2015 - 08/12/2015', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $activityId = 414;
            $questionId = 110;

            $activity = new ActivityTrackerActivity($activityId);

            $records = $activity->getRecords($user, '2015-05-13', '2015-08-12');

            $steps = 0;

            foreach($records as $record) {
                $answers = $record->getQuestionAnswers();

                if(isset($answers[$questionId])) {
                    $steps += (int)$answers[$questionId]->getAnswer();
                }
            }

            return $steps;
        });

        $printer->addStatusFieldCallback('Convert Active/Exercise Minutes to Steps 05/13/2015 - 08/12/2015', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $activityId = 415;

            $activity = new ActivityTrackerActivity($activityId);

            $records = $activity->getRecords($user, '2015-05-13', '2015-08-12');

            $activityStepsPerMinute = CHPWalkingCampaignExerciseToSteps::getActivityStepsPerMinute();


            $points = 0;
            foreach($records as $record) {
                $answers = $record->getQuestionAnswers();

                $activityConversion = isset($answers[123]) && isset($activityStepsPerMinute[$answers[123]->getAnswer()]) ?
                    $activityStepsPerMinute[$answers[123]->getAnswer()] : 0;

                $minutesExercised = isset($answers[1]) ? (int)$answers[1]->getAnswer() : 0;

                $points += $minutesExercised * $activityConversion;
            }
            return $points;
        });

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $data['Compliance Program - Compliant'] = $status->isCompliant() ? 'Yes' : 'No';
            $data['Compliance Program - Points'] =  $status->getPoints() -
                                                    $status->getComplianceViewStatus('fitbit')->getPoints() -
                                                    $status->getComplianceViewStatus('steps')->getPoints() -
                                                    $status->getComplianceViewStatus('minutes_steps')->getPoints();
            $data['Compliance Program - Comment'] = $status->getComment();

            return $data;
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $actGroup = new ComplianceViewGroup('activities', 'Annual / Self-Care Wellness Activities');
        $actGroup->setPointsRequiredForCompliance(0);

        $selfCareSection = 'Time Out for Wellness:  Self-Care & Wellness Activities';

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 290, 10);
        $annualPhysicalExamView->setMaximumNumberOfPoints(10);
        $annualPhysicalExamView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam.');
        $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening follow up');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->setAttribute('report_name_link', '/content/1094#ePhysExam');
        $annualPhysicalExamView->setAttribute('points_per_activity', '10');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link('Click here for verification form ', '/resources/5295/LCM 2015_PreventiveCare Cert.pdf'));
        $actGroup->addComplianceView($annualPhysicalExamView, null, $selfCareSection);

        $preventiveExamsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($startDate, $endDate, 10);
        $preventiveExamsView->configureActivity(259, 91, array(
            'Colonoscopy'   => 60,
            'Dental Exam'   => 24,
            'Mammogram'     => 24,
            'Pap Test'      => 36,
            'Physical Exam' => 36,
            'PSA Test'      => 60
        ));
        $preventiveExamsView->setReportName("Preventive Services");
        $preventiveExamsView->setMaximumNumberOfPoints(40);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#dPrevServices');
        $preventiveExamsView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventive service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.');
        $preventiveExamsView->setAttribute('points_per_activity', '10');
        $preventiveExamsView->emptyLinks();
        $preventiveExamsView->addLink(new Link('Click here for the verification form', '/resources/5295/LCM 2015_PreventiveCare Cert.pdf'));
        $actGroup->addComplianceView($preventiveExamsView);

        $elearningTotalView = new LCMH2015ElearningComplianceView($startDate, $endDate);
        $elearningTotalView->setReportName('eLearning Lessons');
        $elearningTotalView->setName('elearning_total');
        $elearningTotalView->setMaximumNumberOfPoints(40);
        $elearningTotalView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete eLearning lessons from the aboveWellness Dimensions');
        $elearningTotalView->setAttribute('points_per_activity', '5');
        $elearningTotalView->setAttribute('report_name_link', '/content/1094#dPrevServices');
        $elearningTotalView->addLink(new Link('Elearning Center', '/content/9420?action=lessonManager&tab_alias=required'));
        $actGroup->addComplianceView($elearningTotalView);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(20);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new FakeLink('Admin will enter', '#'));
        $smoking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a Smoking Cessation or Hypnosis Course offered by the LCM Health Center');
        $smoking->setAttribute('points_per_activity', 20);
        $smoking->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($smoking);

        $volunteeringView = new PlaceHolderComplianceView(null, 0);
        $volunteeringView->setReportName('Volunteering');
        $volunteeringView->setName('volunteering');
        $volunteeringView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Donate your time to a LCM-designated charity or other non-profit organization');
        $volunteeringView->setAttribute('points_per_activity', '5 points/hour volunteered');
        $volunteeringView->setMaximumNumberOfPoints(40);
        $volunteeringView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $volunteeringView->addLink(new Link('Click here for the certificate', '/resources/5296/LCM 2015_VolunteerCert.pdf'));
        $actGroup->addComplianceView($volunteeringView);

        $lcmWellnessView = new PlaceHolderComplianceView(null, 0);
        $lcmWellnessView->setReportName('Other LCM Time Out For Wellness Activity');
        $lcmWellnessView->setName('lcm_time');
        $lcmWellnessView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a designated LCM Wellness Activity and earn the specified number of points.');
        $lcmWellnessView->setAttribute('points_per_activity', 'varies');
        $lcmWellnessView->setMaximumNumberOfPoints(150);
        $lcmWellnessView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($lcmWellnessView);

        $fitbitView = new CHPWalkingCampaignFitbitComplianceView($startDate, $endDate);
        $fitbitView->setReportName('FitBit Syncing');
        $fitbitView->setName('fitbit');
        $fitbitView->addLink(new Link('Authorize Sync', '/content/ucan-fitbit-individual'));
        $actGroup->addComplianceView($fitbitView);

        $resView = new SumStepsInArbitraryActivityComplianceView($startDate, $endDate, 414, 110);
        $resView->setReportName('Enter Steps Manually');
        $resView->setName('steps');
        $actGroup->addComplianceView($resView);

        $minutesToSteps = new CHPWalkingCampaignExerciseToSteps($startDate, $endDate, 415, 0);
        $minutesToSteps->setName('minutes_steps');
        $minutesToSteps->setReportName('Convert Active/Exercise Minutes to Steps');
        $actGroup->addComplianceView($minutesToSteps);

        $walk6k = new HmiMultipleAverageStepsComplianceView(6000, 5);
        $walk6k->setMaximumNumberOfPoints(15);
        $walk6k->setName('walk_6k');
        $walk6k->setReportName('Walk an average of 6,000 steps/day');
        $walk6k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 6,000 steps/day');
        $walk6k->setAttribute('points_per_activity', 5);
        $walk6k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk6k->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $actGroup->addComplianceView($walk6k);

        $walk8k = new HmiMultipleAverageStepsComplianceView(8000, 10);
        $walk8k->setMaximumNumberOfPoints(30);
        $walk8k->setName('walk_8k');
        $walk8k->setReportName('Walk an average of 8,000 steps/day');
        $walk8k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('points_per_activity', 10);
        $walk8k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk8k->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $actGroup->addComplianceView($walk8k);

        $walk10k = new HmiMultipleAverageStepsComplianceView(10000, 10);
        $walk10k->setMaximumNumberOfPoints(30);
        $walk10k->setName('walk_10k');
        $walk10k->setReportName('Walk an average of 10,000 steps/day');
        $walk10k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('points_per_activity', 10);
        $walk10k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk10k->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $actGroup->addComplianceView($walk10k);

        foreach($this->ranges as $name => $dates) {
            $walk10k->addSummaryDateRange($name, $dates[0], $dates[1]);

            $walk6k->addDateRange($dates[0], $dates[1]);
            $walk8k->addDateRange($dates[0], $dates[1]);
            $walk10k->addDateRange($dates[0], $dates[1]);
        }

        $lcmWalkingView = new PlaceHolderComplianceView(null, 0);
        $lcmWalkingView->setReportName('Participate in the LCM Walking Works Program');
        $lcmWalkingView->setName('participate_lcm_walking');
        $lcmWalkingView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in the LCM Walking Works Program');
        $lcmWalkingView->setAttribute('points_per_activity', '25');
        $lcmWalkingView->setMaximumNumberOfPoints(25);
        $lcmWalkingView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($lcmWalkingView);

        $bonusPointsForMembersView = new PlaceHolderComplianceView(null, 0);
        $bonusPointsForMembersView->setReportName('Bonus Points for members of the Winning Team');
        $bonusPointsForMembersView->setName('bonus_points_for_members');
        $bonusPointsForMembersView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Bonus Points for members of the Winning Team');
        $bonusPointsForMembersView->setAttribute('points_per_activity', '10');
        $bonusPointsForMembersView->setMaximumNumberOfPoints(10);
        $bonusPointsForMembersView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($bonusPointsForMembersView);

        $bonusPointsForTopWalkersView = new PlaceHolderComplianceView(null, 0);
        $bonusPointsForTopWalkersView->setReportName('Bonus Points for each of the Top 10 Walkers ');
        $bonusPointsForTopWalkersView->setName('bonus_points_for_top_walkers');
        $bonusPointsForTopWalkersView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Bonus Points for each of the Top 10 Walkers ');
        $bonusPointsForTopWalkersView->setAttribute('points_per_activity', '10');
        $bonusPointsForTopWalkersView->setMaximumNumberOfPoints(10);
        $bonusPointsForTopWalkersView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($bonusPointsForTopWalkersView);

        $physicalELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'physical');
        $physicalELearningLessonsView->setNumberRequired(0);
        $physicalELearningLessonsView->setReportName('Get a head start with your annual checkup');
        $physicalELearningLessonsView->setName('elearning_physical');
        $physicalELearningLessonsView->setMaximumNumberOfPoints(5);
        $physicalELearningLessonsView->setPointsPerLesson(5);
        $physicalELearningLessonsView->emptyLinks();
        $physicalELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($physicalELearningLessonsView);

        $emotionalELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'emotional');
        $emotionalELearningLessonsView->setNumberRequired(0);
        $emotionalELearningLessonsView->setReportName('Get a head start with your annual checkup');
        $emotionalELearningLessonsView->setName('elearning_emotional');
        $emotionalELearningLessonsView->setMaximumNumberOfPoints(5);
        $emotionalELearningLessonsView->setPointsPerLesson(5);
        $emotionalELearningLessonsView->emptyLinks();
        $emotionalELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($emotionalELearningLessonsView);

        $financialELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'financial');
        $financialELearningLessonsView->setNumberRequired(0);
        $financialELearningLessonsView->setReportName('Lights, camera…colonoscopy!LCM Personal Health Day');
        $financialELearningLessonsView->setName('elearning_financial');
        $financialELearningLessonsView->setMaximumNumberOfPoints(5);
        $financialELearningLessonsView->setPointsPerLesson(5);
        $financialELearningLessonsView->emptyLinks();
        $financialELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($financialELearningLessonsView);

        $spiritualELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'spiritual');
        $spiritualELearningLessonsView->setNumberRequired(0);
        $spiritualELearningLessonsView->setReportName('Be alert – defeat distracted driving LENT');
        $spiritualELearningLessonsView->setName('elearning_spiritual');
        $spiritualELearningLessonsView->setMaximumNumberOfPoints(5);
        $spiritualELearningLessonsView->setPointsPerLesson(5);
        $spiritualELearningLessonsView->emptyLinks();
        $spiritualELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($spiritualELearningLessonsView);

        $environmentalELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'environmental');
        $environmentalELearningLessonsView->setNumberRequired(0);
        $environmentalELearningLessonsView->setReportName('Asthma relief is in the air');
        $environmentalELearningLessonsView->setName('elearning_environmental');
        $environmentalELearningLessonsView->setMaximumNumberOfPoints(5);
        $environmentalELearningLessonsView->emptyLinks();
        $environmentalELearningLessonsView->setPointsPerLesson(5);
        $environmentalELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($environmentalELearningLessonsView);

        $careerELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'career');
        $careerELearningLessonsView->setNumberRequired(0);
        $careerELearningLessonsView->setReportName('Summer Safety Smarts');
        $careerELearningLessonsView->setName('elearning_career');
        $careerELearningLessonsView->setMaximumNumberOfPoints(5);
        $careerELearningLessonsView->setPointsPerLesson(5);
        $careerELearningLessonsView->emptyLinks();
        $careerELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($careerELearningLessonsView);

        $communityELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'community');
        $communityELearningLessonsView->setNumberRequired(0);
        $communityELearningLessonsView->setReportName('Red alert: know the facts about sunburn');
        $communityELearningLessonsView->setName('elearning_community');
        $communityELearningLessonsView->setMaximumNumberOfPoints(5);
        $communityELearningLessonsView->setPointsPerLesson(5);
        $communityELearningLessonsView->emptyLinks();
        $communityELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($communityELearningLessonsView);

        $intellectualELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'intellectual');
        $intellectualELearningLessonsView->setNumberRequired(0);
        $intellectualELearningLessonsView->setReportName('Looking out for your eye health');
        $intellectualELearningLessonsView->setName('elearning_intellectual');
        $intellectualELearningLessonsView->setMaximumNumberOfPoints(5);
        $intellectualELearningLessonsView->setPointsPerLesson(5);
        $intellectualELearningLessonsView->emptyLinks();
        $intellectualELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($intellectualELearningLessonsView);

        $this->addComplianceViewGroup($actGroup);
    }

    private $ranges = array(
        'first_quarter' => array('2015-01-01', '2015-02-28'),
        'second_quarter' => array('2015-03-01', '2015-05-31'),
        'third_quarter' => array('2015-06-01', '2015-08-31')
    );

}

class V32015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);
        $this->setShowTotal(false);

    }



    public function printReport(ComplianceProgramStatus $status)
    {
        $activitiesGroupStatus = $status->getComplianceViewGroupStatus('activities');

        $physicalElearningStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_physical');
        $emotionalElearningStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_emotional');
        $financialElearningStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_financial');
        $spiritualElearningStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_spiritual');
        $environmentalElearningStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_environmental');
        $careerElearningStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_career');
        $communityElearningStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_community');
        $intellectualElearningStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_intellectual');

        $annuaPhysicalExamStatus = $activitiesGroupStatus->getComplianceViewStatus('annual_physical_exam');
        $preventiveStatus = $activitiesGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $elearningTotalStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_total');
        $smokingStatus = $activitiesGroupStatus->getComplianceViewStatus('smoking');
        $volunteeringStatus = $activitiesGroupStatus->getComplianceViewStatus('volunteering');
        $lcmTimeStatus = $activitiesGroupStatus->getComplianceViewStatus('lcm_time');
        $walk6kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_6k');
        $walk8kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_8k');
        $walk10kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_10k');
        $participateLCMWalkingStatus = $activitiesGroupStatus->getComplianceViewStatus('participate_lcm_walking');
        $bonusPointsForMembersStatus = $activitiesGroupStatus->getComplianceViewStatus('bonus_points_for_members');
        $bonusPointsForTopWalkersStatus = $activitiesGroupStatus->getComplianceViewStatus('bonus_points_for_top_walkers');


        if($status->getComplianceViewStatus('fitbit')->getAttribute('data_refreshed')) {
            $status->getComplianceViewStatus('fitbit')->getComplianceView()->emptyLinks();
            $status->getComplianceViewStatus('fitbit')->getComplianceView()->addLink(new Link('View Steps', '/content/ucan-fitbit-individual'));
        }


        $stepsPerMinute = json_encode(
            $status->getComplianceProgram()
                ->getComplianceView('minutes_steps')
                ->getActivityStepsPerMinute()
        );


        ?>

        <style type="text/css">
            .pageHeading {
                display:none;
            }

            #altPageHeading {
                font-weight:bold;
                margin-bottom:20px;
                text-align:center;
            }

            .phipTable .headerRow {
                background-color:#26B000;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
                height:46px;
            }

            #legend td {
                padding:8px !important;
            }

            .legendEntry {
                width:auto;
                float:right;
                display:inline-block;
                padding:0 8px;
            }

            #legendText {
                text-align:center;
                background-color:#FFF;
                font-weight:normal;
                color:#434343;
                font-size:12pt;
                font-weight:bold;
                float:left;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .links a {
                display:inline-block;
                margin:0 3px;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .section {
                height:16px;
                color: white;
                background-color:#436EEE;
            }

            .requirement {
                width: 350px;
            }


            #status-table th,
            #elearningTable th,
            .phipTable .headerRow {
                background-color:#007698;
                color:#FFF;
            }

            #status-table th,
            #status-table td {
                padding:5px;
                text-align:center;
                border:1px solid #CACACA;
                font-weight: bold;
            }

            #elearningTable {
                margin: 10px auto;
            }

            #elearningTable th,
            #elearningTable td {
                padding:5px;
                text-align:center;
                border:1px solid #CACACA;
                font-weight: bold;
            }

        </style>

        <script id="activity_steps_per_minute" type="text/plain">
            <?php echo $stepsPerMinute ?>
        </script>


        <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The LCMH Wellness Program</p>
        <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p style="clear:both;">Each month, <strong>Time Out for Wellness</strong> provides actions for improvement in
            the featured Dimension of Wellness, along with featured eLearning topics. </p>

        <p>Employees that complete eLearning or any of the other Self-care & Wellness
            Activities outlined here will earn wellness points.   </p>

        <p>Every 25 points earned during the Quarter will earn 1 entry in the
            Quarterly Wellness Raffle.</p>
        <ul>
            <li>To be eligible for the quarterly prize raffles, employees must earn
                the points needed before each deadline.</li>
            <li>Rewards will be based on points earned between 1/1/15 and 8/31/2015
                so plan your point accumulation accordingly.</li>
        </ul>

        <table style="width:100%" id="status-table">
            <tr>
                <th>Raffle Period</th>
                <th>Deadline Date</th>
                <th>Points Equivalent</th>
            </tr>
            <tr>
                <td>Quarter 1</td>
                <td>February 28, 2015</td>
                <td rowspan="3">25 points = 1 Raffle Entry</td>
            </tr>
            <tr>
                <td>Quarter 2</td>
                <td>May 31, 2015</td>
            </tr>
            <tr>
                <td>Quarter 3</td>
                <td>August 31, 2015</td>
            </tr>

        </table>

        <p>The number of points earned will also
            move employees toward the Bronze, Silver, and Gold levels of recognition for
            outstanding efforts toward better health and well-being. (optional)</p>

        <table style="width:100%" id="status-table">
            <tr>
                <th>Status Level</th>
                <th>Requirement</th>
                <th>Bonus</th>
            </tr>
            <tr>
                <td>Bronze</td>
                <td>Accumulate 50 points</td>
                <td>2 bonus raffle entries</td>

            </tr>
            <tr>
                <td>Silver</td>
                <td>Accumulate 75 points</td>
                <td>3 bonus raffle entries</td>

            </tr>
            <tr>
                <td>Gold</td>
                <td>Accumulate 100 points</td>
                <td>4 bonus raffle entries</td>
            </tr>

        </table>
        <br />
        <table id="elearningTable">
            <tr>
                <th>Month</th>
                <th>Dimension of Wellness</th>
                <th>BCBS  Topics</th>
                <th>eLearning Lesson</th>
                <th>Points per Activity</th>
                <th>Points Earned</th>
            </tr>

            <tr>
                <td>January 2015</td>
                <td><a href="/resources/5293/Physical Action Items.pdf">Physical</a></td>
                <td>Get a head start with your annual checkup</td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=physical">January eLearning</a></td>
                <td>5</td>
                <td><?php echo $physicalElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>February 2015</td>
                <td><a href="/resources/5289/Emotional Action Items.pdf">Emotional</a></td>
                <td>Don’t be a heartbreaker – treat bad cholesterol</td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=emotional">February eLearning</a></td>
                <td>5</td>
                <td><?php echo $emotionalElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>March 2015</td>
                <td><a href="/resources/5291/Financial Action Items.pdf">Financial</a></td>
                <td>Lights, camera…colonoscopy! LCM Personal Health Day</td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=financial">March  eLearning</a></td>
                <td>5</td>
                <td><?php echo $financialElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>April 2015</td>
                <td><a href="/resources/5294/Spiritual Action Items.pdf">Spiritual</a></td>
                <td>Be alert – defeat distracted driving LENT</td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=spiritual">April eLearning</a></td>
                <td>5</td>
                <td><?php echo $spiritualElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>May 2015</td>
                <td><a href="/resources/5290/Environmental Acton Items.pdf">Environmental</a></td>
                <td>Asthma relief is in the air</td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=environmental">May eLearning</a></td>
                <td>5</td>
                <td><?php echo $environmentalElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>June 2015</td>
                <td><a href="/resources/5287/Career Action Items.pdf">Career</a></td>
                <td>Summer Safety Smarts</td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=career">June eLearning</a></td>
                <td>5</td>
                <td><?php echo $careerElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>July 2015</td>
                <td><a href="/resources/5288/Community Action Items.pdf" >Community</a></td>
                <td>Red alert: know the facts about sunburn</td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=community">July eLearning</a></td>
                <td>5</td>
                <td><?php echo $communityElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>August 2015</td>
                <td><a href="/resources/5292/Intellectual Action Items.pdf">Intellectual</a></td>
                <td>Looking out for your eye health</td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=intellectual">August eLearning</a></td>
                <td>5</td>
                <td><?php echo $intellectualElearningStatus->getPoints() ?></td>
            </tr>
        </table>

        <table class="phipTable">
            <tbody>

            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Time Out for Wellness:</span> Self-Care & Wellness Activities</th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center" style="width:260px;">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Max Points</td>
                <td class="center">Total Points</td>
                <td class="center">Tracking Method</td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $annuaPhysicalExamStatus ->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>A</strong>. <?php echo $annuaPhysicalExamStatus ->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Visit your personal physician to follow-up on your wellness screening and complete your annual exam.</td>
                <td class="center"><?php echo $annuaPhysicalExamStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $annuaPhysicalExamStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $annuaPhysicalExamStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($annuaPhysicalExamStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $preventiveStatus ->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>B</strong>. <?php echo $preventiveStatus ->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Receive a preventive service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.</td>
                <td class="center"><?php echo $preventiveStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $preventiveStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $preventiveStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($preventiveStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <a href="<?php echo $elearningTotalStatus ->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>C</strong>. <?php echo $elearningTotalStatus ->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Complete eLearning lessons from the above Wellness Dimensions</td>
                <td class="center"><?php echo $elearningTotalStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $elearningTotalStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $elearningTotalStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($elearningTotalStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <a href="<?php echo $smokingStatus ->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>D</strong>. <?php echo $smokingStatus ->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Complete a Smoking Cessation or Hypnosis Course offered by the LCM Health Center</td>
                <td class="center"><?php echo $smokingStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $smokingStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $smokingStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr>
                <td>
                    <a href="<?php echo $volunteeringStatus ->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>E</strong>. <?php echo $volunteeringStatus ->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Donate your time to a LCM-designated charity or other non-profit organization</td>
                <td class="center"><?php echo $volunteeringStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $volunteeringStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $volunteeringStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($volunteeringStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <a href="<?php echo $lcmTimeStatus ->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>F</strong>. <?php echo $lcmTimeStatus ->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Participate in a designated LCM Wellness Activity and earn the specified number of points.</td>
                <td class="center"><?php echo $lcmTimeStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $lcmTimeStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $lcmTimeStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Time Out For Wellness: </span> Activity Tracking & Walking Challenges</th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td colspan="2" class="center">
                    This program is designed to help you become physically active each day.
                    Participants can track steps in one of the three ways listed below.
                    Points will be awarded at the end of each quarter based on the average
                    steps logged during the period.
                </td>
                <td class="center">My Steps</td>
                <td colspan="3" class="center">Action Links</td>
            </tr>

            <?php $this->printViewRow($status, 'fitbit', 1) ?>
            <?php $this->printViewRow($status, 'steps', 2) ?>
            <?php $this->printViewRow($status, 'minutes_steps', 3) ?>



            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Max Points</td>
                <td class="center">Total Points</td>
                <td class="center">Tracking Method</td>
            </tr>

            <tr>
                <td rowspan="3" class="center">
                    <strong>Individual Walking Program</strong><br />

                    Quarter 1:  1/1 – 2/28/15 <br />
                    Quarter 2:  3/1 – 5/31/15 <br />
                    Quarter 3:  6/1 -  8/31/15
                </td>
                <td class="requirement">Walk an average of 6,000 steps/day</td>
                <td class="center"><?php echo $walk6kStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk6kStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk6kStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($walk6kStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>

                <td class="requirement">Walk an average of 8,000 steps/day</td>
                <td class="center"><?php echo $walk8kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk8kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk8kStatus->getPoints() ?></td>
                <td class="center">
                    <?php foreach($walk8kStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>

                <td class="requirement">Walk an average of 10,000 steps/day</td>
                <td class="center"><?php echo $walk10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk10kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk10kStatus->getPoints() ?></td>
                <td class="center">
                    <?php foreach($walk10kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td rowspan="3" class="center">
                    <strong>Walking Works Team<br />
                    Walking Challenge <br />
                    May 13 – August 12, 2015</strong><br />

                    Employees must log activity
                    during each month of the
                    challenge to receive points.

                </td>
                <td class="requirement">Participate in the LCM Walking Works Program</td>
                <td class="center"><?php echo $participateLCMWalkingStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $participateLCMWalkingStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $participateLCMWalkingStatus->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr>

                <td class="requirement">Bonus Points for members of the Winning Team</td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr>

                <td class="requirement">Bonus Points for each of the Top 10 Walkers </td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>
            <tr class="headerRow headerRow-quarterly">
                <th colspan="2">&nbsp;&nbsp;Total Points Earned</th>
                <td colspan="4" style="text-align:center">
                    <?php
                        echo $status->getPoints() -
                             $status->getComplianceViewStatus('fitbit')->getPoints() -
                             $status->getComplianceViewStatus('steps')->getPoints() -
                             $status->getComplianceViewStatus('minutes_steps')->getPoints();
                    ?>
                </td>
            </tr>
            </tbody>
        </table>

    <?php
    }

    private function printViewRow($status, $name, $number)
    {
        $viewStatus = $status->getComplianceViewStatus($name);
        $view = $viewStatus->getComplianceView();

        ?>
        <tr>
            <td colspan="2">
                <?php echo sprintf('<span class="view-number">%s.</span> %s', $number, $view->getReportName()) ?>
            </td>
            <td class="center"><?php echo number_format($viewStatus->getPoints()) ?></td>
            <td colspan="3" class="center"><?php echo implode(' ', $view->getLinks()) ?></td>
        </tr>
    <?php
    }


}
