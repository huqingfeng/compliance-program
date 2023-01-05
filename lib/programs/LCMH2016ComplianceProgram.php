<?php

class LCMH2016ElearningComplianceView extends ComplianceView
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
            $elearningComplianceView->setNumberRequired(2);
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


class LCMH2016ComplianceProgram extends ComplianceProgram
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

        $printer->addStatusFieldCallback('Steps 01/01/2016 - 08/31/2016', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2016-01-01', '2016-08-31');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });

        $printer->addStatusFieldCallback('Steps 08/01/2016 - 08/31/2016', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2016-08-01', '2016-08-31');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });

        $printer->addStatusFieldCallback('Manual Steps 01/01/2016 - 08/31/2016', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $activityId = 414;
            $questionId = 110;

            $activity = new ActivityTrackerActivity($activityId);

            $records = $activity->getRecords($user, '2016-01-01', '2016-08-31');

            $steps = 0;

            foreach($records as $record) {
                $answers = $record->getQuestionAnswers();

                if(isset($answers[$questionId])) {
                    $steps += (int)$answers[$questionId]->getAnswer();
                }
            }

            return $steps;
        });

        $printer->addStatusFieldCallback('Convert Active/Exercise Minutes to Steps 01/01/2016 - 08/31/2016', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $activityId = 415;

            $activity = new ActivityTrackerActivity($activityId);

            $records = $activity->getRecords($user, '2016-01-01', '2016-08-31');

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
                $status->getComplianceViewStatus('steps')->getPoints();
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

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 290, 50);
        $annualPhysicalExamView->setMaximumNumberOfPoints(50);
        $annualPhysicalExamView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam.');
        $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening follow up');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->setAttribute('points_per_activity', '50');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link('Verification Form', '/resources/7001/LCM 2016_PreventiveCare Cert.pdf'));
        $annualPhysicalExamView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
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
        $preventiveExamsView->setMaximumNumberOfPoints(50);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventive service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.');
        $preventiveExamsView->setAttribute('points_per_activity', '10');
        $preventiveExamsView->emptyLinks();
        $preventiveExamsView->addLink(new Link('Verification Form', '/resources/7001/LCM 2016_PreventiveCare Cert.pdf'));
        $preventiveExamsView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $actGroup->addComplianceView($preventiveExamsView);

        $elearningTotalView = new LCMH2016ElearningComplianceView($startDate, $endDate);
        $elearningTotalView->setReportName('eLearning Lessons');
        $elearningTotalView->setName('elearning_total');
        $elearningTotalView->setMaximumNumberOfPoints(50);
        $elearningTotalView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete eLearning lessons from the aboveWellness Dimensions');
        $elearningTotalView->setAttribute('points_per_activity', '5');
        $elearningTotalView->addLink(new Link('Elearning Center', '/content/9420?action=lessonManager&tab_alias=required'));
        $actGroup->addComplianceView($elearningTotalView);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(20);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new FakeLink('Admin will enter', '#'));
        $smoking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a Smoking Cessation or Hypnosis Course offered by the LCM Health Center');
        $smoking->setAttribute('points_per_activity', 20);
        $actGroup->addComplianceView($smoking);

        $maintainWeightView = new PlaceHolderComplianceView(null, 0);
        $maintainWeightView->setReportName('Maintain weight within + 5 lbs. of IBW');
        $maintainWeightView->setName('maintain_weight');
        $maintainWeightView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Maintain weight within + 5 lbs. of IBW');
        $maintainWeightView->setAttribute('points_per_activity', '15');
        $maintainWeightView->setMaximumNumberOfPoints(15);
        $maintainWeightView->addLink(new FakeLink('Admin will enter', '#'));
        $actGroup->addComplianceView($maintainWeightView);

        $lostWeightView = new PlaceHolderComplianceView(null, 0);
        $lostWeightView->setReportName('Lose weight during the Challenge');
        $lostWeightView->setName('lost_weight');
        $lostWeightView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Lose weight during the Challenge');
        $lostWeightView->setAttribute('points_per_activity', '2 points/pound lost');
        $lostWeightView->setMaximumNumberOfPoints(30);
        $lostWeightView->addLink(new FakeLink('Admin will enter', '#'));
        $actGroup->addComplianceView($lostWeightView);

        $lcmWellnessView = new PlaceHolderComplianceView(null, 0);
        $lcmWellnessView->setReportName('Other LCM Time Out For Wellness Activity');
        $lcmWellnessView->setName('lcm_time');
        $lcmWellnessView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a designated LCM Wellness Activity and earn the specified number of points.');
        $lcmWellnessView->setAttribute('points_per_activity', 'varies');
        $lcmWellnessView->setMaximumNumberOfPoints(150);
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
        $walk6k->setMaximumNumberOfPoints(10);
        $walk6k->setName('walk_6k');
        $walk6k->setReportName('Walk an average of 6,000 steps/day');
        $walk6k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 6,000 steps/day');
        $walk6k->setAttribute('points_per_activity', 5);
        $walk6k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk6k->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $actGroup->addComplianceView($walk6k);

        $walk8k = new HmiMultipleAverageStepsComplianceView(8000, 10);
        $walk8k->setMaximumNumberOfPoints(20);
        $walk8k->setName('walk_8k');
        $walk8k->setReportName('Walk an average of 8,000 steps/day');
        $walk8k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('points_per_activity', 'Additional 10');
        $walk8k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk8k->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $actGroup->addComplianceView($walk8k);

        $walk10k = new HmiMultipleAverageStepsComplianceView(10000, 10);
        $walk10k->setMaximumNumberOfPoints(20);
        $walk10k->setName('walk_10k');
        $walk10k->setReportName('Walk an average of 10,000 steps/day');
        $walk10k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('points_per_activity', 'Additional 10');
        $walk10k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk10k->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $actGroup->addComplianceView($walk10k);

        foreach($this->ranges as $name => $dates) {
            $walk10k->addSummaryDateRange($name, $dates[0], $dates[1]);

            $walk6k->addDateRange($dates[0], $dates[1]);
            $walk8k->addDateRange($dates[0], $dates[1]);
            $walk10k->addDateRange($dates[0], $dates[1]);
        }

        $timeOutView = new PlaceHolderComplianceView(null, 0);
        $timeOutView->setReportName('Participate in Time Out for Wellness Team Walking Challenge');
        $timeOutView->setName('participate_time_out');
        $timeOutView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in the LCM Walking Works Program');
        $timeOutView->setAttribute('points_per_activity', '25');
        $timeOutView->setMaximumNumberOfPoints(50);
        $timeOutView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($timeOutView);

        $bonusPointsForMembersView = new PlaceHolderComplianceView(null, 0);
        $bonusPointsForMembersView->setReportName('Bonus Points for members of the Winning Team');
        $bonusPointsForMembersView->setName('bonus_points_for_members');
        $bonusPointsForMembersView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Bonus Points for members of the Winning Team');
        $bonusPointsForMembersView->setAttribute('points_per_activity', '10');
        $bonusPointsForMembersView->setMaximumNumberOfPoints(20);
        $bonusPointsForMembersView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($bonusPointsForMembersView);

        $bonusPointsForTopWalkersView = new PlaceHolderComplianceView(null, 0);
        $bonusPointsForTopWalkersView->setReportName('Bonus Points for each of the Top 10 Walkers ');
        $bonusPointsForTopWalkersView->setName('bonus_points_for_top_walkers');
        $bonusPointsForTopWalkersView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Bonus Points for each of the Top 10 Walkers ');
        $bonusPointsForTopWalkersView->setAttribute('points_per_activity', '10');
        $bonusPointsForTopWalkersView->setMaximumNumberOfPoints(20);
        $bonusPointsForTopWalkersView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($bonusPointsForTopWalkersView);

        $physicalELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'physical');
        $physicalELearningLessonsView->setNumberRequired(1);
        $physicalELearningLessonsView->setReportName('Elearning Physical');
        $physicalELearningLessonsView->setName('elearning_physical');
        $physicalELearningLessonsView->setMaximumNumberOfPoints(5);
        $physicalELearningLessonsView->setPointsPerLesson(5);
        $physicalELearningLessonsView->emptyLinks();
        $physicalELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($physicalELearningLessonsView);

        $emotionalELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'emotional');
        $emotionalELearningLessonsView->setNumberRequired(1);
        $emotionalELearningLessonsView->setReportName('Elearning Emotional');
        $emotionalELearningLessonsView->setName('elearning_emotional');
        $emotionalELearningLessonsView->setMaximumNumberOfPoints(5);
        $emotionalELearningLessonsView->setPointsPerLesson(5);
        $emotionalELearningLessonsView->emptyLinks();
        $emotionalELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($emotionalELearningLessonsView);

        $financialELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'financial');
        $financialELearningLessonsView->setNumberRequired(1);
        $financialELearningLessonsView->setReportName('Elearning Financial');
        $financialELearningLessonsView->setName('elearning_financial');
        $financialELearningLessonsView->setMaximumNumberOfPoints(5);
        $financialELearningLessonsView->setPointsPerLesson(5);
        $financialELearningLessonsView->emptyLinks();
        $financialELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($financialELearningLessonsView);

        $spiritualELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'spiritual');
        $spiritualELearningLessonsView->setNumberRequired(1);
        $spiritualELearningLessonsView->setReportName('Elearning Spiritual');
        $spiritualELearningLessonsView->setName('elearning_spiritual');
        $spiritualELearningLessonsView->setMaximumNumberOfPoints(5);
        $spiritualELearningLessonsView->setPointsPerLesson(5);
        $spiritualELearningLessonsView->emptyLinks();
        $spiritualELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($spiritualELearningLessonsView);

        $environmentalELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'environmental');
        $environmentalELearningLessonsView->setNumberRequired(1);
        $environmentalELearningLessonsView->setReportName('Elearning Environmental');
        $environmentalELearningLessonsView->setName('elearning_environmental');
        $environmentalELearningLessonsView->setMaximumNumberOfPoints(5);
        $environmentalELearningLessonsView->emptyLinks();
        $environmentalELearningLessonsView->setPointsPerLesson(5);
        $environmentalELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($environmentalELearningLessonsView);

        $careerELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'career');
        $careerELearningLessonsView->setNumberRequired(1);
        $careerELearningLessonsView->setReportName('Elearning Career');
        $careerELearningLessonsView->setName('elearning_career');
        $careerELearningLessonsView->setMaximumNumberOfPoints(5);
        $careerELearningLessonsView->setPointsPerLesson(5);
        $careerELearningLessonsView->emptyLinks();
        $careerELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($careerELearningLessonsView);

        $communityELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'community');
        $communityELearningLessonsView->setNumberRequired(1);
        $communityELearningLessonsView->setReportName('Elearning Community');
        $communityELearningLessonsView->setName('elearning_community');
        $communityELearningLessonsView->setMaximumNumberOfPoints(5);
        $communityELearningLessonsView->setPointsPerLesson(5);
        $communityELearningLessonsView->emptyLinks();
        $communityELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($communityELearningLessonsView);

        $intellectualELearningLessonsView = new CompleteELearningGroupSet($startDate, $endDate, 'intellectual');
        $intellectualELearningLessonsView->setNumberRequired(1);
        $intellectualELearningLessonsView->setReportName('Elearning Intellectual');
        $intellectualELearningLessonsView->setName('elearning_intellectual');
        $intellectualELearningLessonsView->setMaximumNumberOfPoints(5);
        $intellectualELearningLessonsView->setPointsPerLesson(5);
        $intellectualELearningLessonsView->emptyLinks();
        $intellectualELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $actGroup->addComplianceView($intellectualELearningLessonsView);

        $this->addComplianceViewGroup($actGroup);
    }

    private $ranges = array(
        'challenge 1' => array('2016-05-09', '2016-05-29'),
        'challenge 2' => array('2016-06-06', '2016-06-26')
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
        $maintainWeightStatus = $activitiesGroupStatus->getComplianceViewStatus('maintain_weight');
        $lostWeightStatus = $activitiesGroupStatus->getComplianceViewStatus('lost_weight');
        $lcmTimeStatus = $activitiesGroupStatus->getComplianceViewStatus('lcm_time');
        $walk6kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_6k');
        $walk8kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_8k');
        $walk10kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_10k');
        $participateTimeOutStatus = $activitiesGroupStatus->getComplianceViewStatus('participate_time_out');
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
                margin: 20px auto;
                width: 100%;
                clear: both;
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

        <p>
            <img src="	/resources/5297/lcmh_compliance_report_logo.png" style="float: left; width:18%" />

            <div style="border: 1px solid black; float: right; width:70%; margin-top: 20px;">
                <div style="margin: 10px;">
                    <div style="font-weight: bold; font-size: 12pt;">Time out for Wellness 2016</div>

                    <div style="color: #00a2ea; margin-top: 30px; font-size: 11pt;">The LCMH Wellness Committee works to provide
                     opportunities for employees to achieve optimal health by promoting a worksite culture that enhances
                     personal well-being.</div>
                </div>
            </div>
        </p>

        <p style="clear:both;">Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p style="clear:both;">Each month, <strong>Time Out for Wellness</strong> provides actions for improvement in
            the featured Dimension of Wellness, along with featured eLearning topics. </p>

        <p>Employees that complete eLearning or any of the other Self-care & Wellness
            Activities outlined here will earn wellness points.   </p>

        <p>To be eligible for the <strong>Time Out for Wellness</strong> raffles, employees must earn the
            points needed before each deadline.</p>

        <p>
            The number of points accumulated will also earn employees the Bronze, Silver, or Gold level of
            recognition for outstanding efforts toward better health and well-being.
        </p>


        <p>Recognition will be based on points earned between 1/1/2016 and 8/31/16.</p>

        <table style="width:100%" id="status-table">
            <tr>
                <th>Raffle Deadline</th>
                <th>Requirements / Minimum Points Needed</th>
                <th>Recognition Status</th>
                <th>Reward(s)</th>
            </tr>
            <tr>
                <td>February 29, 2016</td>
                <td>50 points</td>
                <td>BRONZE</td>
                <td>5 Prizes raffled valued at $40 each</td>
            </tr>
            <tr>
                <td>May 31, 2016</td>
                <td>75 points</td>
                <td>SILVER</td>
                <td>5 Prizes raffled valued at $60 each</td>
            </tr>
            <tr>
                <td>August 31, 2016</td>
                <td>125 points</td>
                <td>GOLD</td>
                <td>5 Prizes raffled valued at $100 each</td>
            </tr>

        </table>

        <p style="color: red; margin: 10px 0;">
            All employees achieving recognition status will be announced online at lcmh.org and
            will receive a certificate of achievement.
        </p>

        <p>
            <img src="	/resources/5297/lcmh_compliance_report_logo.png" style="float: left; width:15%" />

            <div style="border: 1px solid black; float: right; width:70%; margin-top: 38px;">
                <div style="margin: 10px;">
                    <div style="font-weight: bold; font-size: 12pt;">
                        Time Out for Wellness <br />
                         Dimensions of Wellness & Featured eLearning Topics
                    </div>
                </div>
            </div>
        </p>

        <br />
        <table id="elearningTable">
            <tr>
                <th>Month</th>
                <th>Dimension of Wellness</th>
                <th>eLearning Lesson</th>
                <th>Points per Activity</th>
                <th>Points Earned</th>
            </tr>

            <tr>
                <td>January</td>
                <td><a href="/resources/5293/Physical Action Items.pdf">Physical</a></td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=physical">January eLearning</a></td>
                <td>5</td>
                <td><?php echo $physicalElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>February</td>
                <td><a href="/resources/5289/Emotional Action Items.pdf">Emotional</a></td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=emotional">February eLearning</a></td>
                <td>5</td>
                <td><?php echo $emotionalElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>March</td>
                <td><a href="/resources/5291/Financial Action Items.pdf">Financial</a></td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=financial">March  eLearning</a></td>
                <td>5</td>
                <td><?php echo $financialElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>April</td>
                <td><a href="/resources/5294/Spiritual Action Items.pdf">Spiritual</a></td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=spiritual">April eLearning</a></td>
                <td>5</td>
                <td><?php echo $spiritualElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>May</td>
                <td><a href="/resources/5290/Environmental Acton Items.pdf">Environmental</a></td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=environmental">May eLearning</a></td>
                <td>5</td>
                <td><?php echo $environmentalElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>June</td>
                <td><a href="/resources/5287/Career Action Items.pdf">Career</a></td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=career">June eLearning</a></td>
                <td>5</td>
                <td><?php echo $careerElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>July</td>
                <td><a href="/resources/5288/Community Action Items.pdf" >Community</a></td>
                <td><a href="/content/9420?action=lessonManager&tab_alias=community">July eLearning</a></td>
                <td>5</td>
                <td><?php echo $communityElearningStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>August</td>
                <td><a href="/resources/5292/Intellectual Action Items.pdf">Intellectual</a></td>
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
                    <strong>A</strong>. <?php echo $annuaPhysicalExamStatus ->getComplianceView()->getReportName() ?>
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
                    <strong>B</strong>. <?php echo $preventiveStatus ->getComplianceView()->getReportName() ?>
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
                    <strong>C</strong>. <?php echo $elearningTotalStatus ->getComplianceView()->getReportName() ?>
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
                    <strong>D</strong>. <?php echo $smokingStatus ->getComplianceView()->getReportName() ?>
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
                <td rowspan="2">
                    <strong>E</strong>. Biggest Loser Weight Loss Challenge
                </td>
                <td class="requirement">Maintain weight within + 5 lbs. of IBW</td>
                <td class="center"><?php echo $maintainWeightStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $maintainWeightStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $maintainWeightStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr>

                <td class="requirement">Lose weight during the Challenge</td>
                <td class="center"><?php echo $lostWeightStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $lostWeightStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $lostWeightStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr>
                <td>
                    <strong>F</strong>. <?php echo $lcmTimeStatus ->getComplianceView()->getReportName() ?>
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
                    This program is designed to help you become physically active each day. Participants can track
                    steps in one of the ways listed below.
                </td>
                <td class="center">My Steps</td>
                <td colspan="3" class="center">Action Links</td>
            </tr>

            <?php $this->printViewRow($status, 'fitbit', 1) ?>
            <?php $this->printViewRow($status, 'steps', 2) ?>



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
                    <strong>Individual Walking Program</strong><br /><br />

                    Challenge 1:  5/9 – 5/29/16 <br />
                    Challenge 2:  6/6 -  6/26/16 <br /><br />
                    Points will be awarded at the end of each challenge based on the average steps logged during the period.
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
                    <strong>Time Out for Wellness<br />
                            Team Walking Challenges
                            </strong><br /><br />

                    Employees must log activity
                    during each month of the
                    challenge to receive points.<br /><br />

                    Spring Challenge:
                    4/1 - 4/30/16<br />

                    Fall Into Fitness Challenge:
                    8/1 -8/31/16

                </td>
                <td class="requirement">Participate in Time Out for Wellness Team Walking Challenge</td>
                <td class="center"><?php echo $participateTimeOutStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $participateTimeOutStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $participateTimeOutStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <a href="/content/ucan-fitbit-leaderboards?type=team">Team Leaderboard</a>
                </td>
            </tr>

            <tr>

                <td class="requirement">Bonus Points for members of the Winning Team</td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getPoints() ?></td>

            </tr>

            <tr>

                <td class="requirement">Bonus Points for each of the Top 10 Walkers </td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getPoints() ?></td>

            </tr>
            <tr class="headerRow headerRow-quarterly">
                <th colspan="2">&nbsp;&nbsp;Total Points Earned</th>
                <td colspan="4" style="text-align:center">
                    <?php
                    echo $status->getPoints() -
                        $status->getComplianceViewStatus('fitbit')->getPoints() -
                        $status->getComplianceViewStatus('steps')->getPoints();
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
