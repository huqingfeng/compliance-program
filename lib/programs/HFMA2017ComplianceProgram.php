<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class HFMA2017BeatCFOComplianceView extends DateBasedComplianceView
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
            '2014-01-01', // For "This Month" data when program hasn't started
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

class HFMA2017ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Wellness Activities');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        $hraScreeningStartDate = '2017-01-01';
        $hraScreeningEndDate =  '2017-12-31';

        $hraScrView = new CompleteHRAAndScreeningComplianceView($hraScreeningStartDate, $hraScreeningEndDate);
        $hraScrView->setReportName('Health Power Assessment & 2017 Wellness Screening');
        $hraScrView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(35, 0, 0, 0));
        $hraScrView->setAttribute(
            'requirement', 'Participate in the HPA Assessment and 2017 Wellness Screening'
        );
        $hraScrView->setAttribute('points_per_activity', '35 points');
        $hraScrView->emptyLinks();
        $hraScrView->addLink(new Link('Do Assessment', '/content/989'));
        $hraScrView->addLink(new Link('Register', '/content/wms2-appointment-center'));
        $wellnessGroup->addComplianceView($hraScrView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($hraScreeningStartDate, $hraScreeningEndDate);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $bloodPressureView->setAttribute('requirement', 'BP < 130/85 mHg');
        $bloodPressureView->setAttribute('points_per_activity', '10 points');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, null);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, null);
        $bloodPressureView->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $wellnessGroup->addComplianceView($bloodPressureView);

        $hdlCholesterolView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $hdlCholesterolView->setAttribute('requirement', 'Total Cholesterol:HDL Ratio < 4.5');
        $hdlCholesterolView->setAttribute('points_per_activity', '10 points');
        $hdlCholesterolView->overrideTestRowData(null, null, 4.499, null);
        $wellnessGroup->addComplianceView($hdlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $trigView->setAttribute('requirement', 'Triglycerides ≤ 150');
        $trigView->setAttribute('points_per_activity', '10 points');
        $trigView->overrideTestRowData(null, null, 150, null);
        $wellnessGroup->addComplianceView($trigView, false);

        $annualPhysicalExamView = new PlaceHolderComplianceView(null, 0);
        $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening Follow-Up');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->setAttribute('requirement', 'Visit your personal physician to follow-up on the wellness screening and complete an annual exam');
        $annualPhysicalExamView->setAttribute('points_per_activity', '20 points');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link('Verification Form', 'https://static.hpn.com/wms2/documents/clients/hfma/2017_PreventiveCare%20Cert.pdf'));
        $annualPhysicalExamView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $wellnessGroup->addComplianceView($annualPhysicalExamView);

        $preventiveServiceView = new PlaceHolderComplianceView(null, 0);
        $preventiveServiceView->setName('preventive_service');
        $preventiveServiceView->setReportName('Preventive Services: Provide proof of exam on Healthcare Provider Certification form (available online and in HR)');
        $preventiveServiceView->setAttribute('requirement', 'Receive a preventive service such as mammogram, prostate exam, routine dental exam, etc.');
        $preventiveServiceView->setAttribute('points_per_activity', '10 points');
        $preventiveServiceView->emptyLinks();
        $preventiveServiceView->addLink(new Link('Verification Form', 'https://static.hpn.com/wms2/documents/clients/hfma/2017_PreventiveCare%20Cert.pdf'));
        $preventiveServiceView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $preventiveServiceView->setMaximumNumberOfPoints(30);
        $wellnessGroup->addComplianceView($preventiveServiceView);

        $flushotView = new PlaceHolderComplianceView(null, 0);
        $flushotView->setName('flushot');
        $flushotView->setReportName('Receive a flu shot');
        $flushotView->setAttribute('requirement', 'Receive a flu shot');
        $flushotView->setAttribute('points_per_activity', '10 points');
        $flushotView->emptyLinks();
        $flushotView->setMaximumNumberOfPoints(10);
        $wellnessGroup->addComplianceView($flushotView);

        $wellnessKickOff = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 330, 10);
        $wellnessKickOff->setReportName('Wellness Kick-Off');
        $wellnessKickOff->setMaximumNumberOfPoints(10);
        $wellnessKickOff->setAttribute('requirement', 'Attend the Wellness Kick-Off meeting');
        $wellnessKickOff->setAttribute('points_per_activity', '10 points');
        $wellnessKickOff->emptyLinks();
        $wellnessKickOff->addLink(new FakeLink('Sign in at Event', '/content/events'));
        $wellnessGroup->addComplianceView($wellnessKickOff);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('E-learning');
        $elearn->setName('elearning');
        $elearn->setAttribute('requirement', 'Complete an online e-learning course');
        $elearn->setAttribute('points_per_activity', '5 points');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(25);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('Take Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $wellnessGroup->addComplianceView($elearn);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Smoking Cessation');
        $smokingView->setAttribute('requirement', 'Complete a Smoking Cessation Course as recommended by EAP');
        $smokingView->setAttribute('points_per_activity', '25 points');
        $smokingView->setMaximumNumberOfPoints(25);
        $smokingView->addLink(new Link('Verification Form', 'https://static.hpn.com/wms2/documents/clients/hfma/2017_WellnessEventCert.pdf'));
        $smokingView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $wellnessGroup->addComplianceView($smokingView);

        $onMyTimeView = new PlaceHolderComplianceView(null, 0);
        $onMyTimeView->setName('on_my_time');
        $onMyTimeView->setReportName('OnMyTime Courses** Nutrition, Weight Management, Stress Management, Smoking Cessation, etc');
        $onMyTimeView->setAttribute('requirement', 'Complete BCBS Online Program via Well On Target');
        $onMyTimeView->setAttribute('points_per_activity', '25 points');
        $onMyTimeView->emptyLinks();
        $onMyTimeView->addLink((new Link('WellOnTarget', 'http://www.wellontarget.com')));
        $onMyTimeView->addLink(new Link('Verification Form', 'https://static.hpn.com/wms2/documents/clients/hfma/2017_WellnessEventCert.pdf'));
        $onMyTimeView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $onMyTimeView->setMaximumNumberOfPoints(25);
        $wellnessGroup->addComplianceView($onMyTimeView);


        $brownBagPresentationView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 331, 10);
        $brownBagPresentationView->setReportName('Brown Bag Presentation');
        $brownBagPresentationView->setAttribute('requirement', 'Attend a Brown Bag Session');
        $brownBagPresentationView->setAttribute('points_per_activity', '5 points');
        $brownBagPresentationView->emptyLinks();
        $brownBagPresentationView->addLink(new FakeLink('Sign in at Event', '/content/events'));
        $brownBagPresentationView->setMaximumNumberOfPoints(25);
        $wellnessGroup->addComplianceView($brownBagPresentationView);

        $otherWellnessEventsView = new PlaceHolderComplianceView(null, 0);
        $otherWellnessEventsView->setName('other_wellness_events');
        $otherWellnessEventsView->setAttribute('requirement', 'Participate in other HFMA designated Wellness Events');
        $otherWellnessEventsView->setAttribute('points_per_activity', 'Varies');
        $otherWellnessEventsView->setReportName('Other HFMA Wellness Events');
        $otherWellnessEventsView->setMaximumNumberOfPoints(100);
        $otherWellnessEventsView->setAllowPointsOverride(true);
        $otherWellnessEventsView->addLink(new FakeLink('Admin will enter', '#'));
        $wellnessGroup->addComplianceView($otherWellnessEventsView);

        $regularFitnessTrainingView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $regularFitnessTrainingView->setReportName('Regular Fitness Training');
        $regularFitnessTrainingView->setMaximumNumberOfPoints(160);
        $regularFitnessTrainingView->setMinutesDivisorForPoints(100);
        $regularFitnessTrainingView->setMonthlyPointLimit(16);
        $regularFitnessTrainingView->setPointsMultiplier(4);
        $regularFitnessTrainingView->setFractionalDivisorForPoints(1);
        $regularFitnessTrainingView->setAttribute('requirement', 'Track a minimum of 400 minutes of activity/month on the HMI website (effective 3/1/15)');
        $regularFitnessTrainingView->setAttribute('points_per_activity', '16 points per month');
        $wellnessGroup->addComplianceView($regularFitnessTrainingView);

        $this->addComplianceViewGroup($wellnessGroup);

        $marathonGroup = new ComplianceViewGroup('marathon', 'Program');
        $marathonGroup->setPointsRequiredForCompliance(0);
        $marathonGroup->setMaximumNumberOfPoints(30);

        $participateWalkRunView = new PlaceHolderComplianceView(null, 0);
        $participateWalkRunView->setName('participate_walk_run');
        $participateWalkRunView->setReportName('Participate in walk/run (1 pt/km)');
        $participateWalkRunView->setAttribute('requirement', 'Participate in walk/run (1 pt/km), Example: 5k = 5 points');
        $participateWalkRunView->setAttribute('points_per_activity', '1 point per km');
        $participateWalkRunView->setAllowPointsOverride(true);
        $participateWalkRunView->setMaximumNumberOfPoints(10);
        $participateWalkRunView->setPointsOverrideHonorsMaximum(false);
        $participateWalkRunView->addLink(new Link('Verification Form', 'https://static.hpn.com/wms2/documents/clients/hfma/2017_WellnessEventCert.pdf'));
        $participateWalkRunView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $marathonGroup->addComplianceView($participateWalkRunView);

        $participateHalfMarathonView = new PlaceHolderComplianceView(null, 0);
        $participateHalfMarathonView->setName('participate_half_marathon');
        $participateHalfMarathonView->setReportName('Participate in half-marathon, Sprint distance triathlon, or Bike Tour');
        $participateHalfMarathonView->setAttribute('requirement', 'Participate in half-marathon, Sprint distance triathlon, or Bike Tour (25-30 miles)');
        $participateHalfMarathonView->setAttribute('points_per_activity', '15');
        $participateHalfMarathonView->setMaximumNumberOfPoints(10);
        $participateHalfMarathonView->setAllowPointsOverride(true);
        $participateHalfMarathonView->setPointsOverrideHonorsMaximum(false);
        $marathonGroup->addComplianceView($participateHalfMarathonView);

        $participateMarathonView = new PlaceHolderComplianceView(null, 0);
        $participateMarathonView->setName('participate_marathon');
        $participateMarathonView->setReportName('Participate in a marathon or Olympic distance triathlon, or Bike Tour');
        $participateMarathonView->setAttribute('requirement', 'Participate in a marathon or Olympic distance triathlon, or Bike Tour (51-100 miles)');
        $participateMarathonView->setAttribute('points_per_activity', '30');
        $participateMarathonView->setMaximumNumberOfPoints(10);
        $participateMarathonView->setAllowPointsOverride(true);
        $participateMarathonView->setPointsOverrideHonorsMaximum(false);
        $marathonGroup->addComplianceView($participateMarathonView);

        $this->addComplianceViewGroup($marathonGroup);

        $walkingGroup = new ComplianceViewGroup('walking_programs', 'Walking Activities');
        $walkingGroup->setPointsRequiredForCompliance(0);

        $walkingChallengeSpringView = new PlaceHolderComplianceView(null, 0);
        $walkingChallengeSpringView->setReportName('HFMA Team Walking Challenge Spring');
        $walkingChallengeSpringView->setName('participate_in_walking_challenge_spring');
        $walkingChallengeSpringView->setAttribute('requirement', 'Participate in the Spring Walking Challenge (average at least 3,000 steps/day for credit)');
        $walkingChallengeSpringView->setAttribute('points_per_activity', '20 points');
        $walkingChallengeSpringView->setMaximumNumberOfPoints(20);
        $walkingChallengeSpringView->addLink(new Link('Stridekick Login', 'https://axion.stridekick.com'));
        $walkingGroup->addComplianceView($walkingChallengeSpringView);

        $walkingChallengeFallView = new PlaceHolderComplianceView(null, 0);
        $walkingChallengeFallView->setName('participate_in_walking_challenge_fall');
        $walkingChallengeFallView->setReportName('HFMA Team Walking Challenge Fall');
        $walkingChallengeFallView->setAttribute('requirement', 'Participate in the Fall Walking Challenge (average at least 3,000 steps/day for credit)');
        $walkingChallengeFallView->setAttribute('points_per_activity', '20 points');
        $walkingChallengeFallView->setMaximumNumberOfPoints(20);
        $walkingChallengeFallView->addLink(new Link('Stridekick Login', 'https://axion.stridekick.com'));
        $walkingGroup->addComplianceView($walkingChallengeFallView);

        $challengeWinerView = new PlaceHolderComplianceView(null, 0);
        $challengeWinerView->setName('participate_in_walking_challenge_team_winner');
        $challengeWinerView->setReportName('HFMA Team Walking Challenge Winner');
        $challengeWinerView->setAttribute('requirement', 'Team that wins the Spring or Fall Walking Challenge');
        $challengeWinerView->setAttribute('points_per_activity', '10 points');
        $challengeWinerView->setMaximumNumberOfPoints(20);
        $challengeWinerView->addLink(new FakeLink('Admin will enter', '#'));
        $walkingGroup->addComplianceView($challengeWinerView);


        $hfmaBeatCfoSummer = new PlaceHolderComplianceView(null, 0);
        $hfmaBeatCfoSummer->setReportName('OUTPACE JOE June');
        $hfmaBeatCfoSummer->setName('beat_cfo_summer');
        $hfmaBeatCfoSummer->setMaximumNumberOfPoints(10);
        $hfmaBeatCfoSummer->addAttributes(array(
            'points_per_activity' =>
                '10 points',

            'requirement' =>
                'Walk further than Joe Fifer during June'
        ));
        $hfmaBeatCfoSummer->addLink(new Link('Stridekick Login', 'https://axion.stridekick.com'));
        $walkingGroup->addComplianceView($hfmaBeatCfoSummer);

        $hfmaBeatCfoTopFive = new PlaceHolderComplianceView(null, 0);
        $hfmaBeatCfoTopFive->setReportName('OUTPACE JOE Top 5 Walkers');
        $hfmaBeatCfoTopFive->setName('beat_cfo_top_five');
        $hfmaBeatCfoTopFive->setMaximumNumberOfPoints(10);
        $hfmaBeatCfoTopFive->addAttributes(array(
            'points_per_activity' =>
                '5 points',

            'requirement' =>
                'Points will be awarded to the top 5 walkers during each period that do NOT Outpace Joe'
        ));
        $hfmaBeatCfoTopFive->addLink(new FakeLink('Admin will enter', '#'));
        $walkingGroup->addComplianceView($hfmaBeatCfoTopFive);

        $hfmaJourneyChallenge = new PlaceHolderComplianceView(null, 0);
        $hfmaJourneyChallenge->setReportName('HFMA Journey Challenge');
        $hfmaJourneyChallenge->setName('hfma_journey_challenge');
        $hfmaJourneyChallenge->setMaximumNumberOfPoints(20);
        $hfmaJourneyChallenge->addAttributes(array(
            'points_per_activity' =>
                '20 points',

            'requirement' =>
                'Participate in the HFMA Journey Challenge (average at least 3,000 steps/day for credit)'
        ));
        $hfmaJourneyChallenge->addLink(new FakeLink('Admin will enter', '#'));
        $walkingGroup->addComplianceView($hfmaJourneyChallenge);

        $createStridekick = new PlaceHolderComplianceView(null, 0);
        $createStridekick->setReportName('Create a Stridekick Challenge');
        $createStridekick->setName('create_stridekick');
        $createStridekick->setAttribute('points_per_activity', '10 points');
        $createStridekick->setMaximumNumberOfPoints(10);
        $createStridekick->setAttribute('requirement', 'Create a Stridekick Challenge');
        $createStridekick->addLink(new FakeLink('Admin will enter', '#'));
        $walkingGroup->addComplianceView($createStridekick);

        $participateStridekick = new PlaceHolderComplianceView(null, 0);
        $participateStridekick->setReportName('Participate in a Stridekick Challenge');
        $participateStridekick->setName('participate_stridekick');
        $participateStridekick->setAttribute('points_per_activity', '10 points');
        $participateStridekick->setMaximumNumberOfPoints(50);
        $participateStridekick->setAttribute('requirement', 'Participate in a Stridekick Challenge');
        $participateStridekick->addLink(new FakeLink('Admin will enter', '#'));
        $walkingGroup->addComplianceView($participateStridekick);

        $maintianWeightView = new PlaceHolderComplianceView(null, 0);
        $maintianWeightView->setName('maintain_weight');
        $maintianWeightView->setReportName('Maintain your weight during the Challenge');
        $maintianWeightView->setAttribute('requirement', 'Maintain your weight during the Challenge');
        $maintianWeightView->setAttribute('points_per_activity', '10 points');
        $maintianWeightView->setMaximumNumberOfPoints(10);
        $maintianWeightView->setAllowPointsOverride(true);
        $maintianWeightView->addLink(new FakeLink('Admin will enter', '#'));
        $walkingGroup->addComplianceView($maintianWeightView);

        $lostWeightView = new PlaceHolderComplianceView(null, 0);
        $lostWeightView->setName('lost_weight');
        $lostWeightView->setReportName('Lose weight during the challenge');
        $lostWeightView->setAttribute('requirement', 'Lose weight during the challenge');
        $lostWeightView->setAttribute('points_per_activity', '2 points / pound lost');
        $lostWeightView->setMaximumNumberOfPoints(12);
        $lostWeightView->setAllowPointsOverride(true);
        $walkingGroup->addComplianceView($lostWeightView);

        $this->addComplianceViewGroup($walkingGroup);


    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);
        

        $printer->addStatusFieldCallback('June - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2016-06-01',
                '2016-06-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('December - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2016-12-01',
                '2016-12-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('07/01/16 - 08/30/16 Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2016-07-01',
                '2016-08-30'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('09/19/16 - 10/31/16 Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2016-09-19',
                '2016-10-31'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('12/01/16 - 12/31/16 Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2016-12-01',
                '2016-12-31'
            );

            return $data['total_steps'];
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new HFMA2017ComplianceProgramReportPrinter();

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2012-08-15';
}


class HFMA2017ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        $marathonGroup = $status->getComplianceViewGroupStatus('marathon');

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
                padding: 10px;
                text-align: center;
                border:1px solid #0063dc;
            }

        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned

            $(function() {
                $('.phipTable .headerRow.headerRow-wellness_programs').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#436EEE;'>HFMA 2017 WELLNESS INCENTIVE PROGRAM</th></tr>");
                $('.phipTable .headerRow.headerRow-walking_programs').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#436EEE;'>HFMA 2017 WELLNESS INCENTIVE PROGRAM</th></tr>");

                $('.phipTable tr td.points').each(function() {
                    $(this).html($(this).html() + ' points');
                });

                $('.phipTable .headerRow.headerRow-wellness_programs').children(':eq(1)').html('Requirement');
                $('.phipTable .headerRow.headerRow-wellness_programs').children(':eq(2)').html('Points Per Activity');

                $('.phipTable .headerRow.headerRow-walking_programs').children(':eq(0)').html('<strong>2</strong>. Walking Activities');
                $('.phipTable .headerRow.headerRow-walking_programs').children(':eq(1)').html('Requirement');
                $('.phipTable .headerRow.headerRow-walking_programs').children(':eq(2)').html('Points Per Activity');

                $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)')
                    .html('<strong>B</strong>. BIOMETRIC BONUS POINTS: Based on Wellness Screening Results');
                $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)').attr('rowspan', 3);
                $('.view-comply_with_blood_pressure_screening_test td.links').attr('rowspan', 3);
                $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test').children(':eq(0)').remove();
                $('.view-comply_with_triglycerides_screening_test').children(':eq(0)').remove();
                $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test td.links').remove();
                $('.view-comply_with_triglycerides_screening_test td.links').remove();

                $('.view-annual_physical_exam').children(':eq(0)').html('<strong>C</strong>. Annual Physical Exam &amp; Screening Follow-Up');

                $('.view-preventive_service').children(':eq(0)').html('<strong>D</strong>. Preventive Services: Provide proof of exam on Healthcare Provider Certification form (available online and in HR)');
                $('.view-preventive_service').children(':eq(0)').attr('rowspan', 2);
                $('.view-preventive_service td.links').attr('rowspan', 2);
                $('.view-flushot').children(':eq(0)').remove();
                $('.view-flushot td.links').remove();

                $('.view-activity_330').children(':eq(0)').html('<strong>E</strong>. Wellness Kick-Off');
                $('.view-elearning').children(':eq(0)').html('<strong>F</strong>. E-learning');
                $('.view-comply_with_smoking_hra_question').children(':eq(0)').html('<strong>G</strong>. Smoking Cessation');
                $('.view-on_my_time').children(':eq(0)').html('<strong>H</strong>. OnMyTime Courses** Nutrition, Weight Management, Stress Management, Smoking Cessation, etc');
                $('.view-activity_331').children(':eq(0)').html('<strong>I</strong>. Brown Bag Presentation');
                $('.view-other_wellness_events').children(':eq(0)').html('<strong>J</strong>. Other HFMA Wellness Events');
                $('.view-activity_21').children(':eq(0)').html('<strong>K</strong>. Regular Fitness Training');

                $('.view-participate_walk_run').children(':eq(0)').html('<strong>L</strong>. Athletic Events');
                $('.view-participate_walk_run').children(':eq(0)').attr('rowspan', 3);
                $('.view-participate_walk_run').children(':eq(3)').html('<?php echo $marathonGroup->getPoints() ?> Points');
                $('.view-participate_walk_run').children(':eq(3)').attr('rowspan', 3);
                $('.view-participate_walk_run').children(':eq(4)').html('30 points');
                $('.view-participate_walk_run').children(':eq(4)').attr('rowspan', 3);
                $('.view-participate_walk_run td.links').attr('rowspan', 3);
                $('.view-participate_half_marathon').children(':eq(0)').remove();
                $('.view-participate_half_marathon td.points').remove();
                $('.view-participate_half_marathon td.links').remove();
                $('.view-participate_marathon').children(':eq(0)').remove();
                $('.view-participate_marathon td.points').remove();
                $('.view-participate_marathon td.links').remove();

                $('.view-participate_in_walking_challenge_spring').children(':eq(0)').html(
                    '<strong>A</strong>. HFMA Team Walking Challenge<br />' +
                    '<div>Spring:<br /><strong>March 20th—April 19th</strong></div><br />' +
                    '<div>Fall:<br /><strong>September 19th– October 17th</strong></div>'
                );
                $('.view-participate_in_walking_challenge_spring').children(':eq(0)').attr('rowspan', 3);
                $('.view-participate_in_walking_challenge_fall').children(':eq(0)').remove();
                $('.view-participate_in_walking_challenge_team_winner').children(':eq(0)').remove();

                $('.view-beat_cfo_summer').children(':eq(0)').html('<strong>B</strong>. ' +
                    'OUTPACE JOE<br /><br />' +
                    '<div style="margin-left:10px;">June 1st—30th</div><br />');
                $('.view-beat_cfo_summer').children(':eq(0)').attr('rowspan', 2);
                $('.view-beat_cfo_top_five').children(':eq(0)').remove();

                $('.view-hfma_journey_challenge').children(':eq(0)').html('<strong>C</strong>. ' +
                    'HFMA Journey Challenge<br /><br />' +
                    '<div style="margin-left:10px;">July 17th– August 16th</div><br />');


                $('.view-create_stridekick').children(':eq(0)').html('<strong>D</strong>. Individual Stridekick Walking Challenge');
                $('.view-create_stridekick').children(':eq(0)').attr('rowspan', 2);
                $('.view-create_stridekick td.links').attr('rowspan', 2);
                $('.view-participate_stridekick').children(':eq(0)').remove();
                $('.view-participate_stridekick td.links').remove();


                $('.view-maintain_weight').children(':eq(0)').html('<strong>E</strong>. ' +
                    'Hold it for the Holidays<br /><br />' +
                    '<div style="margin-left:10px;">November 15— January 3, 2018 (7 weeks)</div><br />');
                $('.view-maintain_weight').children(':eq(0)').attr('rowspan', 2);
                $('.view-maintain_weight td.links').attr('rowspan', 2);
                $('.view-lost_weight').children(':eq(0)').remove();
                $('.view-lost_weight td.links').remove();

                $('.view-activity_21').next().remove();

                $('.view-lost_weight').after(
                    '<tr class="headerRow">' +
                    '<td colspan="3">Total Points</td>' +
                    '<td><?php echo $status->getPoints() ?> Points</td>'  +
                    '<td><?php echo $status->getComplianceProgram()->getMaximumNumberOfPoints() ?> Points</td>' +
                    '<td></td></tr>'
                )
            });
        </script>
        <!-- Text atop report card-->
        <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The HFMA Wellness Program gives you tools and motivation to improve and maintain your health.</p>
        <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>HFMA cares about your health! We have partnered with HMI Health and Axion to implement our Wellness Program.
            The wellness program provides you with fun, robust programming options geared towards specific areas of your health
            that need improvement. This Wellness Program is your way to better, healthier living.</p>

        <p style="font-weight: bolder; font-size: 12pt; text-align: center">HOW DOES THE PROGRAM WORK?</p>
        <p>
            <span style="font-weight:bolder;">Employees that complete the 2017 Health Screening and HRA are eligible to participate.</span>
            Participation in the program will earn wellness points that will be tracked through the HMI Wellness Website
            at <a href="http://www.myhmihealth.com">www.myhmihealth.com</a>. Rewards will be based on points earned during the wellness year.
            (2/1/17 through 12/31/17)
        </p>

        <div>
            <p style="font-weight: bolder; font-size: 12pt; text-align: center">WELLNESS REWARDS STRUCTURE</p>
            <table id="programTable">
                <tr style="background-color:#008787; color: #ffffff">
                    <th>Criteria</th>
                    <th>Wellness Rewards</th>
                </tr>
                <tr>
                    <td>Earn 375 Points in the Wellness Program</td>
                    <td>Gold Award: $200 Gift Card</td>
                </tr>
                <tr>
                    <td>Earn 250 Points in the Wellness Program</td>
                    <td>Silver Award: $150 Gift Card</td>
                </tr>
                <tr>
                    <td>Earn 75 Points in the Wellness Program</td>
                    <td>Bronze Award: FitBit Zip ($60 value) (for new Wellness Program members)</td>
                </tr>
            </table>
        </div><br />


        <p style="font-weight: bold; text-align: center">See the following chart for a description of the wellness points you can earn.</p>

        <p>
            <strong>Notice:</strong> The HFMA Wellness Program is committed to helping you achieve your best health.
            Rewards for participating in awellness program are available to all employees. If you think you might be
            unable to meet a standard for a reward under thiswellness program, you might qualify for an opportunity to
            earn the same reward by different means. Contact Human Resources andwe will work with you (and, if you wish,
            your doctor) to find a wellness program with the same reward that is right for you in light ofyour health status.
        </p>


        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>

        <?php
    }


    public $showUserNameInLegend = true;
}
