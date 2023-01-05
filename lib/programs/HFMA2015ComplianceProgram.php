<?php

class HFMA2015CompleteScreeningView extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {
        if(!parent::evaluateStatus($user, $array)) {
            return false;
        }

        $cholesterol = trim((string) $array['cholesterol']);

        return $cholesterol != '' && $cholesterol != '0' ?
            ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;
    }
}

class HFMA2015BeatCFOComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setDateRange($startDate, $endDate);
    }

    public function getDefaultName()
    {
        return 'beat_cfo';
    }

    public function getDefaultReportName()
    {
        return 'Beat CFO';
    }

    public function getDefaultStatusSummary($status)
    {

    }

    public function getStatus(User $user)
    {
        $cfoData = $this->getCfoData();

        if(!$cfoData || !isset($cfoData['dates'])) {
            $cfoData = array('dates' => array());
        }

        // For each month in the program, if the CFO has data for the next month,
        // assume the prior month is done. If we have more average steps than
        // the cfo for that prior month, award points.

        $data = get_all_fitbit_data(
            $user->id,
            '2013-01-01', // For "This Month" data when program hasn't started
            $this->getEndDate('Y-m-d')
        );

        if(!$data || !isset($data['dates'])) {
            $data = array('dates' => array());
        }

        $points = 0;

        foreach($this->getCalculableMonths($cfoData) as $monthStr) {
            $userAverage = $this->totalSteps($data['dates'], $monthStr);
            $cfoAverage = $this->totalSteps($cfoData['dates'], $monthStr);

            if($userAverage > $cfoAverage) {
                $points += 5;
            }
        }

        $currentMonthStr = date('Y-m');

        $viewStatus = new ComplianceViewStatus($this, null, $points);

        $viewStatus->addAttributes(array(
            'user_month_total' =>
                $this->totalSteps($data['dates'], $currentMonthStr),

            'cfo_month_total'  =>
                $this->totalSteps($cfoData['dates'], $currentMonthStr)
        ));

        return $viewStatus;
    }

    private function totalSteps($data, $monthStr)
    {
        $total = 0;

        $daysInMonth = $this->getDaysInMonth($monthStr);

        foreach($daysInMonth as $day) {
            if(isset($data[$day])) {
                $total += $data[$day];
            }
        }

        return $total;
    }

    private function getDaysInMonth($monthStr)
    {
        $days = array();

        $startDate = new \DateTime($monthStr.'-01');
        $endDate = new \DateTime($startDate->format('Y-m-t'));

        while($startDate <= $endDate) {
            $days[] = $startDate->format('Y-m-d');

            $startDate->add(new \DateInterval('P1D'));
        }

        return $days;
    }

    private function getCalculableMonths($cfoData)
    {
        $daysWithPoints = array_keys(
            array_filter($cfoData['dates'], function($el) {
                return $el > 0;
            })
        );

        $lastDayWithData = end($daysWithPoints);

        if(!$lastDayWithData) {
            return array();
        }

        $months = array();

        $startDate = $this->getStartDate('Y-m-01');

        $endDate = date(
            'Y-m-t',
            strtotime($lastDayWithData)
        );

        $startObject = new \DateTime($startDate);
        $endObject = new \DateTime($endDate);
        $endObject->sub(new \DateInterval('P1M'));

        while($startObject < $endObject) {
            $months[] = $startObject->format('Y-m');

            $startObject->add(new \DateInterval('P1M'));
        }

        return $months;
    }

    private function getCfoData()
    {
        static $data = null;

        $cfoUser = UserTable::getInstance()->find($this->cfoUserId);

        if($cfoUser) {
            $data = get_all_fitbit_data(
                $cfoUser->id,
                $this->getStartDate('Y-m-d'),
                $this->getEndDate('Y-m-d')
            );
        }

        return $data;
    }

    private $cfoUserId = 2778225;
}

class HFMA2015ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        $hraScrView = new HFMA2015CompleteScreeningView('2015-01-01', $programEnd);
        $hraScrView->setReportName('Health Power Assessment & 2015 Wellness Screening');
        $hraScrView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(65, 0, 0, 0));
        $hraScrView->setAttribute('report_name_link', '/content/1094#1aHPA');
        $hraScrView->setAttribute(
            'requirement', 'Participate in the HPA Assessment and 2015 Wellness Screening'
        );
        $hraScrView->setAttribute('points_per_activity', '65 points');
        $hraScrView->emptyLinks();
        $hraScrView->addLink(new Link('Do Assessment', '/content/989'));
        $hraScrView->addLink(new Link('Register', '/content/ucan_scheduling'));
        $wellnessGroup->addComplianceView($hraScrView);

        $wellnessKickOff = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 330, 10);
        $wellnessKickOff->setReportName('Wellness Kick-Off');
        $wellnessKickOff->setMaximumNumberOfPoints(10);
        $wellnessKickOff->setAttribute('requirement', 'Attend the Wellness Kick-Off meeting');
        $wellnessKickOff->setAttribute('points_per_activity', '10 points');
        $wellnessKickOff->setAttribute('report_name_link', '/content/1094#1bKickoff');
        $wellnessKickOff->emptyLinks();
        $wellnessKickOff->addLink(new Link('Sign in at Event', '/content/events'));
        $wellnessGroup->addComplianceView($wellnessKickOff);

        $preventiveExamsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($programStart, $programEnd, 5);
        $preventiveExamsView->configureActivity(259, 91, array(
            'Colonoscopy'   => 60,
            'Dental Exam'   => 24,
            'Mammogram'     => 24,
            'Pap Test'      => 36,
            'Physical Exam' => 36,
            'PSA Test'      => 60
        ));
        $preventiveExamsView->setReportName("Preventive Services");
        $preventiveExamsView->setMaximumNumberOfPoints(30);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#1cPrevServices');
        $preventiveExamsView->setAttribute('requirement', 'Receive a preventive service such as mammogram, prostate exam, routine dental exam, etc.');
        $preventiveExamsView->setAttribute('points_per_activity', '10 points per exam');
        $preventiveExamsView->emptyLinks();
        $preventiveExamsView->addLink(new Link('Click here for the verification form', '/resources/5367/HFMA-2015_PreventiveCare Cert.021815.pdf'));
        $wellnessGroup->addComplianceView($preventiveExamsView);

        $flushotView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $flushotView->setReportName('Flu Shot');
        $flushotView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $flushotView->setAttribute('report_name_link', '/content/1094#1dFluShot');
        $flushotView->setAttribute('requirement', 'Receive a flu Shot');
        $flushotView->setAttribute('points_per_activity', '10 points');
        $flushotView->emptyLinks();
        $flushotView->addLink(new Link('Sign in at Event', '/content/events'));
        //$flushotView->addLink(new Link('Click here for verification form', '/content/12048?action=showActivity&activityidentifier=20'));
        $wellnessGroup->addComplianceView($flushotView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('E-learning');
        $elearn->setName('elearning');
        $elearn->setAttribute('report_name_link', '/content/1094#1eRecElearn');
        $elearn->setAttribute('requirement', 'Complete an online e-learning course');
        $elearn->setAttribute('points_per_activity', '5 points');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(25);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420'));

        $wellnessGroup->addComplianceView($elearn);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Smoking Cessation');
        $smokingView->setAttribute('requirement', 'Complete a Smoking Cessation Course as recommended by EAP');
        $smokingView->setAttribute('points_per_activity', '25 points');
        $smokingView->setAttribute('report_name_link', '/content/1094#1fSmokingCess');
        $smokingView->setMaximumNumberOfPoints(25);
        $smokingView->addLink(new Link('Click here for verification form', '/resources/5370/Smoking_Cessation_Certification_Form_2015_1.pdf'));
        $smokingView->setAlternativeComplianceView(new WilhelmLearningAlternativeComplianceView($programStart, $programEnd, 'required_smoking'));
        $wellnessGroup->addComplianceView($smokingView);

        $onMyTimeView = new PlaceHolderComplianceView(null, 0);
        $onMyTimeView->setReportName('OnMyTime Courses');
        $onMyTimeView->setAttribute('requirement', 'Complete BCBS Online Program via Well On Target');
        $onMyTimeView->setAttribute('points_per_activity', '25 points');
        $onMyTimeView->setAttribute('report_name_link', '/content/1094#1gOnMyTime');
        $onMyTimeView->emptyLinks();
        $onMyTimeView->addLink((new Link('Click Here', 'http://www.wellontarget.com')));
        $onMyTimeView->setMaximumNumberOfPoints(25);
        $wellnessGroup->addComplianceView($onMyTimeView);

        $brownBagPresentationView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 331, 10);
        $brownBagPresentationView->setReportName('Brown Bag Presentation');
        $brownBagPresentationView->setAttribute('requirement', 'Attend a Brown Bag Session');
        $brownBagPresentationView->setAttribute('points_per_activity', '5 points');
        $brownBagPresentationView->setAttribute('report_name_link', '/content/1094#1hBBPres');
        $brownBagPresentationView->emptyLinks();
        $brownBagPresentationView->addLink(new Link('Sign in at Event', '/content/events'));
        $brownBagPresentationView->setMaximumNumberOfPoints(25);
        $wellnessGroup->addComplianceView($brownBagPresentationView);

        $brownBagQuizView = new PlaceHolderComplianceView(null, 0);
        $brownBagQuizView->setReportName('Brown Bag Quiz');
        $brownBagQuizView->setAttribute('report_name_link', '/content/1094#1hBBPres');
        $brownBagQuizView->setAttribute('requirement', 'Complete the quiz after attending a Brown Bag Session');
        $brownBagQuizView->setAttribute('points_per_activity', '5 points');
        $brownBagQuizView->emptyLinks();
        $brownBagQuizView->addLink(new Link('Sign in at Event', '/content/events'));
        $brownBagQuizView->setMaximumNumberOfPoints(30);
//        $wellnessGroup->addComplianceView($brownBagQuizView);

        $healthAnnualMembershipView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 273, 10);
        $healthAnnualMembershipView->setReportName('Health Program Annual Membership');
        $healthAnnualMembershipView->setAttribute('report_name_link', '/content/1094#kAnnMemb');
        $healthAnnualMembershipView->setAttribute('requirement', 'Membership in health program such as health club, weight watchers, etc.');
        $healthAnnualMembershipView->setAttribute('points_per_activity', '10 points');
        $healthAnnualMembershipView->emptyLinks();
        $healthAnnualMembershipView->addLink(new link('Click here for verification form', '/resources/4660/HFMA_Annual_Membership_Form.pdf'));
        $healthAnnualMembershipView->setMaximumNumberOfPoints(10);
        //$healthAnnualMembershipView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=273'));
//        $wellnessGroup->addComplianceView($healthAnnualMembershipView);

        $regularFitnessTrainingView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $regularFitnessTrainingView->setReportName('Regular Fitness Training');
        $regularFitnessTrainingView->setMaximumNumberOfPoints(160);
        $regularFitnessTrainingView->setMinutesDivisorForPoints(90);
        $regularFitnessTrainingView->setMonthlyPointLimit(16);
        $regularFitnessTrainingView->setPointsMultiplier(4);
        $regularFitnessTrainingView->setFractionalDivisorForPoints(1);
        $regularFitnessTrainingView->setAttribute('report_name_link', '/content/1094#1iFitness');
        $regularFitnessTrainingView->setAttribute('requirement', 'Track a minimum of 400 minutes of activity / month on the HMI website');
        $regularFitnessTrainingView->setAttribute('points_per_activity', '16 points per month');
        $wellnessGroup->addComplianceView($regularFitnessTrainingView);

        $maintianWeightView = new PlaceHolderComplianceView(null, 0);
        $maintianWeightView->setName('maintain_weight');
        $maintianWeightView->setReportName('Maintain your weight during the Challenge');
        $maintianWeightView->setAttribute('report_name_link', '#');
        $maintianWeightView->setAttribute('requirement', 'Maintain your weight during the Challenge');
        $maintianWeightView->setAttribute('points_per_activity', '10 points');
        $maintianWeightView->setMaximumNumberOfPoints(10);
        $maintianWeightView->setAllowPointsOverride(true);
        $maintianWeightView->addLink(new Link('Administrator will award points', '#'));
        $wellnessGroup->addComplianceView($maintianWeightView);

        $loseWeightView = new PlaceHolderComplianceView(null, 0);
        $loseWeightView->setName('lose_weight');
        $loseWeightView->setReportName('Lose weight during the Challenge');
        $loseWeightView->setAttribute('report_name_link', '#');
        $loseWeightView->setAttribute('requirement', 'Lose weight during the Challenge');
        $loseWeightView->setAttribute('points_per_activity', '2 points');
        $loseWeightView->setMaximumNumberOfPoints(12);
        $loseWeightView->setAllowPointsOverride(true);
        $loseWeightView->addLink(new Link('Administrator will award points', '#'));
        $wellnessGroup->addComplianceView($loseWeightView);

        $healthCoachView = new PlaceHolderComplianceView(null, 0);
        $healthCoachView->setName('work_with_health_coach');
        $healthCoachView->setAttribute('report_name_link', '#');
        $healthCoachView->setAttribute('requirement', 'Other HFMA Wellness Events');
        $healthCoachView->setAttribute('points_per_activity', '10 points');
        $healthCoachView->setReportName('Other HFMA Wellness Events');
        $healthCoachView->setMaximumNumberOfPoints(100);
        $healthCoachView->setAllowPointsOverride(true);
        $healthCoachView->addLink(new Link('Administrator will award points', '#'));
        $wellnessGroup->addComplianceView($healthCoachView);

        $wellnessGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($wellnessGroup);

        $walkingStart = '2015-03-23';
        $walkingEnd = '2015-05-15';

        $walkingGroup = new ComplianceViewGroup('walking_programs', 'Program');
        $walkingGroup->setPointsRequiredForCompliance(0);

        $participateView = new HmiParticipateInWalkingChallenge('2015-03-23', '2015-05-15', 'hfma_walking', 0);
        $participateView->setReportName('HFMA Team Walking Challenge Spring');
        $participateView->setName('participate_in_walking_challenge_spring');
        $participateView->setAttribute('requirement', 'Participate in a Walking Spring Challenge');
        $participateView->setAttribute('points_per_activity', '40 points');
        $participateView->setMaximumNumberOfPoints(40);
        $participateView->setAttribute('report_name_link', '/content/1094#teamwalkchall');
        $participateView->addLink(new Link('Leaderboards', '/content/ucan-fitbit-leaderboards'));
        $walkingGroup->addComplianceView($participateView);

        $participateView = new HmiParticipateInWalkingChallenge('2015-09-21', '2015-11-13', 'hfma_walking', 0);
        $participateView->setReportName('HFMA Team Walking Challenge Fall');
        $participateView->setName('participate_in_walking_challenge_fall');
        $participateView->setAttribute('requirement', 'Participate in a Walking Fall Challenge');
        $participateView->setAttribute('points_per_activity', '40 points');
        $participateView->setMaximumNumberOfPoints(40);
        $participateView->setAttribute('report_name_link', '/content/1094#teamwalkchall');
        $participateView->addLink(new Link('Leaderboards', '/content/ucan-fitbit-leaderboards'));
        $walkingGroup->addComplianceView($participateView);

        $challengeWinerView = new PlaceHolderComplianceView(null, 0);
        $challengeWinerView->setName('challenge_winner');
        $challengeWinerView->setReportName('HFMA Team Walking Challenge Winner');
        $challengeWinerView->setMaximumNumberOfPoints(20);
        $challengeWinerView->setAttribute('requirement', 'Team that wins the Walking Challenge');
        $challengeWinerView->setAttribute('report_name_link', '/content/1094#teamwalkchallwinner');
        $challengeWinerView->setAttribute('points_per_activity', '10 points');
        $walkingGroup->addComplianceView($challengeWinerView);

        $individualWalkingStart = '2015-07-01';
        $individualWalkingEnd = '2015-08-30';

        $stepOne = new HmiAverageStepsComplianceView($individualWalkingStart, $individualWalkingEnd, 5000);
        $stepOne->setReportName('Individual Walking Challenge');
        $stepOne->setName('step_one');
        $stepOne->setAttribute('points_per_activity', '10 points');
        $stepOne->setMaximumNumberOfPoints(10);
        $stepOne->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $stepOne->setAttribute('report_name_link', '/content/1094#indwalk5k');
        $walkingGroup->addComplianceView($stepOne);

        $stepTwo = new HmiAverageStepsComplianceView($individualWalkingStart, $individualWalkingEnd, 7500);
        $stepTwo->setReportName('Individual Walking Challenge');
        $stepTwo->setName('step_two');
        $stepTwo->setAttribute('points_per_activity', 'Additional 10 points');
        $stepTwo->setAttribute('report_name_link', '/content/1094#indwalk75k');
        $stepTwo->setMaximumNumberOfPoints(20);
        $walkingGroup->addComplianceView($stepTwo);

        $stepThree = new HmiAverageStepsComplianceView($individualWalkingStart, $individualWalkingEnd, 10000);
        $stepThree->setReportName('Individual Walking Challenge');
        $stepThree->setName('step_three');
        $stepThree->setAttribute('points_per_activity', 'Additional 10 points');
        $stepThree->setAttribute('report_name_link', '/content/1094#indwalk10k');
        $stepThree->setMaximumNumberOfPoints(30);
        $walkingGroup->addComplianceView($stepThree);

        $hfmaBeatCfoSummer = new HFMA2015BeatCFOComplianceView(
            '2015-06-01', '2015-06-30'
        );

        $hfmaBeatCfoSummer->setReportName('OUTPACE JOE');
        $hfmaBeatCfoSummer->setName('beat_cfo_summer');
        $hfmaBeatCfoSummer->setMaximumNumberOfPoints(10);


        $hfmaBeatCfoSummer->addAttributes(array(
            'points_per_activity' =>
                '10 points each month you exceed Joe\'s steps',

            'requirement' =>
                'Walk further than Joe Fifer each month (June 1st—June 30th)'
        ));

        $walkingGroup->addComplianceView($hfmaBeatCfoSummer);


        $hfmaBeatCfoWinter = new HFMA2015BeatCFOComplianceView(
            '2015-12-01', '2015-12-31'
        );

        $hfmaBeatCfoWinter->setReportName('OUTPACE JOE');
        $hfmaBeatCfoWinter->setName('beat_cfo_winter');
        $hfmaBeatCfoWinter->setMaximumNumberOfPoints(10);


        $hfmaBeatCfoWinter->addAttributes(array(
            'points_per_activity' =>
                '10 points each month you exceed Joe\'s steps',

            'requirement' =>
                'Walk further than Joe Fifer each month (December 1—December 31st)'
        ));

        $walkingGroup->addComplianceView($hfmaBeatCfoWinter);

        $topThreeWalker = new PlaceHolderComplianceView(null, 0);
        $topThreeWalker->setName('top_three');
        $topThreeWalker->setReportName('If no one outpace Joe, points will be awarded to the top 3 walkers during each period');
        $topThreeWalker->setMaximumNumberOfPoints(10);
        $topThreeWalker->setAttribute('requirement', 'If no one outpace Joe, points will be awarded to the top 3 walkers during each period');
        $topThreeWalker->setAttribute('report_name_link', '/content/1094#teamwalkchallwinner');
        $topThreeWalker->setAttribute('points_per_activity', '5 points');
        $walkingGroup->addComplianceView($topThreeWalker);


        $this->addComplianceViewGroup($walkingGroup);


    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('Steps', function(ComplianceProgramStatus $status) {
            return $status->getComplianceViewStatus('step_one')->getAttribute('total_steps');
        });

        $printer->addStatusFieldCallback('June - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2015-06-01',
                '2015-06-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('July - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2015-07-01',
                '2015-07-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('August - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2015-08-01',
                '2015-08-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('September - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2015-09-01',
                '2015-09-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('October - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2015-10-01',
                '2015-10-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('November - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2015-11-01',
                '2015-11-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('December - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2015-12-01',
                '2015-12-31'
            );

            return $data['total_steps'];
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new HFMA2015ComplianceProgramReportPrinter();

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2012-08-15';
}


class HFMA2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);

        $this->tableHeaders['points_possible'] = 'Maximum Possible Points';
        $this->screeningLinkArea = '<br/><br/>
      Green Range = 10 pts <br/>
      Yellow Range = 5 pts<br/><br/>
      <a href="/content/1094#2aKBHM"> Click here for more info &amp; tips to improve.</a><br/><br/>
      <em>Points appear 5-10 days after screening.</em>
    ';

        $this->addStatusCallbackColumn('Requirement', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            return $view->getAttribute('requirement');
        });

        $this->addStatusCallbackColumn('Points Per Activity', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();
            return $view->getAttribute('points_per_activity');
        });


    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(false);
        $this->setScreeningResultsLink(new Link('Have These Screening Results in the Ideal Zone:', '/content/1094#2aKBHM'));

        $cfoSummerView = $status->getComplianceViewStatus('beat_cfo_summer');
        $cfoWinterView = $status->getComplianceViewStatus('beat_cfo_winter');

        if(date('M') == 'Dec') {
            $beatCfoStatus = $cfoWinterView;
        } else {
            $beatCfoStatus = $cfoSummerView;
        }

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
        
        #programTable {
            border-collapse: collapse;
            margin:0 auto;
        }
        
        #programTable tr th, #programTable tr td{
            border:1px solid #0063dc;
        }
        
    </style>

    <script type="text/javascript">
        // Set max points text for misc points earned

        $(function() {           
           $('.phipTable .headerRow.headerRow-wellness_programs').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#436EEE;'>HFMA WELLNESS PROGRAM</th></tr>");
           $('.phipTable .headerRow.headerRow-walking_programs').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#436EEE;'>WALKING PROGRAM</th></tr>");
           
           $('.phipTable tr td.points').each(function() {
               $(this).html($(this).html() + ' points');
           });
           
           $('.phipTable .headerRow.headerRow-wellness_programs').children(':eq(1)').html('Requirement');
           $('.phipTable .headerRow.headerRow-wellness_programs').children(':eq(2)').html('Points Per Activity');
           
           $('.phipTable .headerRow.headerRow-walking_programs').children(':eq(1)').html('Requirement');
           $('.phipTable .headerRow.headerRow-walking_programs').children(':eq(2)').html('Points Per Activity');
           
           $('.view-participate_in_walking_challenge_spring td.links').attr('rowspan', 3);
           $('.view-participate_in_walking_challenge_fall td.links').remove();
           $('.view-challenge_winner td.links').remove();

            $('.view-step_one td.links').attr('rowspan', 5);
            $('.view-step_two td.links').remove();
            $('.view-step_three td.links').remove();
            $('.view-beat_cfo_summer td.links').remove();
            $('.view-beat_cfo_winter td.links').remove();

            $('.view-step_one td.links').append(
                '<br/><br/><br/><br/><strong>This Month</strong>' +
                '<br/><br/><div style="text-align:left; margin-left:20px;">My Steps: ' +
                    <?php echo $beatCfoStatus->getAttribute('user_month_total') ?> +
                '<br/>Joe\'s Steps: ' +
                <?php echo $beatCfoStatus->getAttribute('cfo_month_total') ?> +
                '</div>'
            );

            $('.view-maintain_weight').children(':eq(0)').html('<strong>J</strong>. ' +
             '<a href="/content/1094#1jHolidays">Hold it for the Holidays</a><br /><br />' +
              '<div style="margin-left:10px;">November 19—January 7<br />(7 weeks)</div>');

            $('.view-maintain_weight').children(':eq(0)').attr('rowspan', 2);
            $('.view-lose_weight').children(':eq(0)').remove();

            $('.view-work_with_health_coach').children(':eq(0)').html('<strong>K</strong>. <a href="#">Other HFMA Wellness Events</a>');

            $('.view-participate_in_walking_challenge_spring').children(':eq(0)').html('<strong>A</strong>. ' +
             '<a href="/content/1094#2aTeamWalk">HFMA Team Walking Challenge</a><br />' +
              '<div style="margin-left:10px;">Spring Challenge:<br />March 23rd – May 15th<br /><br />Fall Challenge:<br />Sept. 21st – Nov. 13th </div>');
            $('.view-participate_in_walking_challenge_spring').children(':eq(0)').attr('rowspan', 2);
            $('.view-participate_in_walking_challenge_fall').children(':eq(0)').remove();

            $('.view-challenge_winner').children(':eq(0)').html('<strong>B</strong>. ' +
             '<a href="/content/1094#2bTeamWinner">HFMA Team Walking Challenge Winner</a>');

            $('.view-step_one').children(':eq(0)').html('<strong>C</strong>. ' +
             '<a href="/content/1094#2cIndWalk">Individual Walking Challenge</a><br /><br />' +
              '<div style="margin-left:10px;">Challenge period: <br />July 1st—August 30th</div>');
            $('.view-step_one').children(':eq(0)').attr('rowspan', 3);
            $('.view-step_two').children(':eq(0)').remove();
            $('.view-step_three').children(':eq(0)').remove();

            $('.view-beat_cfo_summer').children(':eq(0)').html('<strong>D</strong>. ' +
             '<a href="/content/1094#2dOutwalkJoe">OUTPACE JOE</a><br />' +
              '<div style="margin-left:10px;">June 1st—June 30th<br /><br />December 1—December 31st</div><br /><br /><br />');

            $('.view-beat_cfo_summer').children(':eq(0)').attr('rowspan', 3);
            $('.view-beat_cfo_winter').children(':eq(0)').remove();
            $('.view-top_three').children(':eq(0)').remove();
            $('.phipTable tbody').after('<tfoot><tr class="headerRow">' +
             '<td class="center" colspan="3">Status of All Criteria = </td>' +
              '<td class="points"><?php echo $status->getPoints() ?></td>' +
               '<td class="points"><?php echo $status->getComplianceProgram()->getMaximumNumberOfPoints() ?>' +
                '</td><td colspan=""></td>' +
                 '</tr></tfoot>');
        });
    </script>
    <!-- Text atop report card-->
    <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The HFMA comprehensive Wellness Program gives you tools and motivation to improve and maintain your health.</p>
    <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

    <p>HFMA cares about your health! We have partnered with HMI Health and Axion (formerly Mid American Group) to
    implement our Wellness Program. The wellness program provides you with fun, robust programming options geared
    towards specific areas of your health that need improvement. This Wellness Program is your way to better,
    healthier living.</p>

    <p style="font-weight: bolder; font-size: 12pt; text-align: center">HOW DOES THE PROGRAM WORK?</p>
    <p>
        <span style="font-weight:bolder;">Employees that complete the 2015 Health Screening and HRA are eligible to participate.</span>
        Participation in the program will earn wellness points that will be tracked through the HMI Wellness Website
        at <a href="http://www.myhmihealth.com">www.myhmihealth.com</a>. Rewards will be based on points earned between 2/1/15 and 12/31/15.
    </p>
    
    <div>
        <table id="programTable">
            <tr style="background-color:#008787">
                <th>Criteria</th>
                <th>Wellness Incentive</th>
            </tr>
            <tr>
                <td>Earn 350 Points in the Wellness Program</td>
                <td>Gold Award:  $200 gift card</td>
            </tr>
            <tr>
                <td>Earn 225 Points in the Wellness Program</td>
                <td>Silver Award:  $150 Gift card</td>
            </tr>
            <tr>
                <td>Earn 75 Points in the Wellness Program</td>
                <td>Bronze Award (for new Wellness Program members only): FitBit Zip ($60 value)</td>
            </tr>        
        </table>
    </div>


    <p>Some tips for using the table are as follows:
    <ul>
        <li>The first column lists the event or action you can complete.  If the text is <span style="color: #0276FD;">blue </span>you can click on it to learn more.  </li>
        <li>The second column lists the requirement.</li>
        <li>The third column shows the number of points available for each activity in a category.</li>
        <li>The fourth column shows the number of points earned.</li>
        <li>The fifth column lists the maximum number of points available in that category.</li>
        <li>The last column provides you information on how each activity is tracked along with links for further information.  In some cases it allows you to sign up or see results.</li>
    </ul></p>


<?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>
        <br /><br /><p><span style="font-weight: bold">Individual Walking Challenge</span> will start 07/01 and goes through 08/30.  Points will show at the end of the incentive period. FIT BIT is the only way STEPS will be tracked.
    </p>
        <table>
            <tr><td>Team Walking challenge Dates:</td><td>3/23/15 – 5/15/15</td></tr>
            <tr><td></td><td>9/21/15 – 11/13/15</td></tr>
        </table>


    <?php
    }


    public $showUserNameInLegend = true;
}
