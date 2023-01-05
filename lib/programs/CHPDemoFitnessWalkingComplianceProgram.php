<?php

use hpn\steel\query\SelectQuery;

class CHPDemoFitnessWalkingSunshineView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'walking_on_sunshine';
    }

    public function getDefaultReportName()
    {
        return 'Walking on Sunshine';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $walkingSunshineTeamData = get_walking_teams('walking_on_sunshine_team');

        $participated = false;
        foreach($walkingSunshineTeamData as $team) {
            if(in_array($user->getId(), array_keys($team))) $participated = true;
        }

        if($participated) {
            return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, 40);
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
    }
}

class CHPDemoFitnessHolidayHustleView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'holiday_hustle';
    }

    public function getDefaultReportName()
    {
        return 'Holiday Hustle';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $holidayHustleTeamData = get_walking_teams('holiday_hustle_team');

        $participated = false;
        foreach($holidayHustleTeamData as $team) {
            if(in_array($user->getId(), array_keys($team))) $participated = true;
        }

        if($participated) {
            return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT, 40);
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
    }
}

class CHPDemoFitnessTeamWalkingChallengeWinnerView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'team_winner';
    }

    public function getDefaultReportName()
    {
        return 'HMI Team Walking Challenge Winner';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $record = $user->getNewestDataRecord('fitbit_steps_data');

        $fitbitData = get_all_fitbit_data(null, WALKING_START_DATE, WALKING_END_DATE);

        $holidayHustleTeamData = get_walking_teams('holiday_hustle_team');
        $walkingSunshineTeamData = get_walking_teams('walking_on_sunshine_team');

        $holidayHustleTeams = get_all_team_data($holidayHustleTeamData, $fitbitData);
        $walkingSunshineTeams = get_all_team_data($walkingSunshineTeamData, $fitbitData);

        $holidayHustleWinTeam = reset($holidayHustleTeams);
        $walkingSunshineWinTeam = reset($walkingSunshineTeams);

        $holidayHustleTeamWin = false;
        $walkingSunshineTeamWin = false;

        $points = 0;

        foreach($holidayHustleWinTeam['users'] as $userId => $userData) {
            if($userId == $user->getId()) $holidayHustleTeamWin = true;
        }

        foreach($walkingSunshineWinTeam['users'] as $userId => $userData) {
            if($userId == $user->getId()) $walkingSunshineTeamWin = true;
        }

        if($holidayHustleTeamWin) {
            $points += 10;
        }

        if($walkingSunshineTeamWin) {
            $points += 10;
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, $points);
    }
}

class HMIIndividualWalkingChallengeSixThousandSteps extends CompleteArbitraryActivityComplianceView
{
    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $data = get_all_fitbit_data($user->id, WALKING_START_DATE, WALKING_END_DATE);

        $records = $this->getRecords($user);

        $points = 0;

        if(isset($data['average_daily_steps']) && $data['average_daily_steps'] > 6000) {
            $points = $this->getMaximumNumberOfPoints();
        } else {
            $points = $this->pointsPerRecord * count($records);
        }

        return new ComplianceViewStatus(
            $this,
            null,
            $points
        );
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $activityId;
    private $pointsPerRecord;
}

class HMIIndividualWalkingChallengeEightThousandSteps extends CompleteArbitraryActivityComplianceView
{
    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $data = get_all_fitbit_data($user->id, WALKING_START_DATE, WALKING_END_DATE);

        $records = $this->getRecords($user);

        $points = 0;
        if(isset($data['average_daily_steps']) &&$data['average_daily_steps'] > 8000) {
            $points = $this->getMaximumNumberOfPoints();
        } else {
            $points = $this->pointsPerRecord * count($records);
        }

        return new ComplianceViewStatus(
            $this,
            null,
            $points
        );
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $activityId;
    private $pointsPerRecord;
}

class HMIIndividualWalkingChallengeTenThousandSteps extends CompleteArbitraryActivityComplianceView
{
    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $data = get_all_fitbit_data($user->id, WALKING_START_DATE, WALKING_END_DATE);

        $records = $this->getRecords($user);

        $points = 0;
        if(isset($data['average_daily_steps']) && $data['average_daily_steps'] > 10000) {
            $points = $this->getMaximumNumberOfPoints();
        } else {
            $points = $this->pointsPerRecord * count($records);
        }

        return new ComplianceViewStatus(
            $this,
            null,
            $points
        );
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $activityId;
    private $pointsPerRecord;
}


class CHPDemoFitnessSemesterWrapperView extends ComplianceView
{
    public static function setFirstSemesterOnly($bool)
    {
        self::$firstSemesterOnly = $bool;
    }

    public function __construct(DateBasedComplianceView $view)
    {
        $this->view = $view;
    }

    public function setSemesterPoints($points)
    {
        $this->semesterPoints = $points;
    }

    public function getDefaultReportName()
    {
        return $this->view->getDefaultReportName();
    }

    public function getDefaultName()
    {
        return $this->view->getDefaultName();
    }

    public function getDefaultStatusSummary($status)
    {
        return $this->view->getDefaultStatusSummary($status);
    }

    public function getReportName($forHTML = false)
    {
        return $this->view->getReportName($forHTML);
    }

    public function getName()
    {
        return $this->view->getName();
    }

    public function getLinks()
    {
        return $this->view->getLinks();
    }

    public function getMaximumNumberOfPoints()
    {
        return 2 * $this->view->getMaximumNumberOfPoints();
    }

    public function getStatusSummary($status)
    {
        return $this->view->getStatusSummary($status);
    }

    public function getStatus(User $user)
    {
        $this->view->setMaximumNumberOfPoints($this->semesterPoints);

        $this->view->setStartDate('2012-08-05');
        $this->view->setEndDate('2013-01-21');

        $semesterOneStatus = $this->view->getMappedStatus($user);

        $this->view->setStartDate('2013-01-22');
        $this->view->setEndDate('2013-06-15');

        $semesterTwoStatus = $this->view->getMappedStatus($user);

        if(self::$firstSemesterOnly) {
            return new ComplianceViewStatus($this, null, $semesterOneStatus->getPoints());
        } else {
            return new ComplianceViewStatus($this, null, $semesterOneStatus->getPoints() + $semesterTwoStatus->getPoints());
        }
    }

    private static $firstSemesterOnly = false;
    private $semesterPoints;
    private $view;
}

class CHPDemoFitnessComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $optionalProgramSection = 'Optional Programs';
        $healthTrainingEventsSection = 'Health Training Events';
        $walkingProgramsSection = 'Walking Programs';

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Required Programs');
        $coreGroup->setPointsRequiredForCompliance(50);

        $hraScrView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScrView->setReportName('Health Power Assessment & Wellness Screening');
        $hraScrView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $hraScrView->setAttribute('report_name_link', '/content/##1ahpa');
        $hraScrView->emptyLinks();
        $hraScrView->addLink(new Link('Do HPA', '/content/989'));
        $hraScrView->addLink(new Link('Sign-Up', '/content/ucan_scheduling'));

        $coreGroup->addComplianceView($hraScrView);

        $this->addComplianceViewGroup($coreGroup);


        $wellnessGroup = new ComplianceViewGroup('points', 'Wellness programs');

        $elearn = new CompleteRequiredELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('Complete recommended e-learning lessons');
        $elearn->setAttribute('report_name_link', '/content/##2aelearn');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(25);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420'));
        $wellnessGroup->addComplianceView($elearn, false, $optionalProgramSection);

        $regularFitnessTrainingView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $regularFitnessTrainingView->setReportName('Regular Fitness Training');
        $regularFitnessTrainingView->setMaximumNumberOfPoints(100);
        $regularFitnessTrainingView->setMinutesDivisorForPoints(90);
        $regularFitnessTrainingView->setMonthlyPointLimit(16);
        $regularFitnessTrainingView->setPointsMultiplier(4);
        $regularFitnessTrainingView->setFractionalDivisorForPoints(1);
        $regularFitnessTrainingView->setAttribute('report_name_link', '/content/##2bphysact');
        $wellnessGroup->addComplianceView($regularFitnessTrainingView, false, $optionalProgramSection);

        $flushotView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $flushotView->setReportName('Annual Flu Shot');
        $flushotView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $flushotView->setAttribute('report_name_link', '/content/##2cflushot');
        $wellnessGroup->addComplianceView($flushotView, false, $optionalProgramSection);

        $preventiveExamsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($programStart, $programEnd, 5);
        $preventiveExamsView->configureActivity(259, 91, array(
            'Colonoscopy'   => 60,
            'Dental Exam'   => 24,
            'Mammogram'     => 24,
            'Pap Test'      => 36,
            'Physical Exam' => 36,
            'PSA Test'      => 60
        ));
        $preventiveExamsView->setReportName('Have up-to-date recommended preventive exams ');
        $preventiveExamsView->setMaximumNumberOfPoints(30);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/##2dexams');
        $preventiveExamsView->emptyLinks();
        $preventiveExamsView->addLink(new Link('Click here for the verification form.', '#'));
        $wellnessGroup->addComplianceView($preventiveExamsView, false, $optionalProgramSection);

        $doctorView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorView->setReportName('Have Main Doctor/Primary Care Provider');
        $doctorView->setAttribute('report_name_link', '/content/##2edoc');
        $doctorView->setName('doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $wellnessGroup->addComplianceView($doctorView, false, $optionalProgramSection);

        $workWithHealthCoachView = new PlaceHolderComplianceView(null, 0);
        $workWithHealthCoachView->setName('work_with_health_coach');
        $workWithHealthCoachView->setAttribute('report_name_link', '/content/##2fcoach');
        $workWithHealthCoachView->setReportName('Health Coaching');
        $workWithHealthCoachView->setMaximumNumberOfPoints(50);
        $workWithHealthCoachView->setAllowPointsOverride(true);
        $workWithHealthCoachView->addLink(new Link('Health Coach Info & Sign-Up', '/content/8733'));
        $wellnessGroup->addComplianceView($workWithHealthCoachView, false, $optionalProgramSection);

        $HWAGLnlPrograms = new AttendEventComplianceView($programStart, $programEnd);
        $HWAGLnlPrograms->bindTypeIds(array(9));
        $HWAGLnlPrograms->setPointsPerAttendance(10);
        $HWAGLnlPrograms->setReportName('HWAG Lunch and Learn');
        $HWAGLnlPrograms->setAttribute('report_name_link', '/content/##2gLnL');
        $HWAGLnlPrograms->setMaximumNumberOfPoints(50);
        $HWAGLnlPrograms->addLink(new Link('An administrator will update this for you.', '#'));
        $wellnessGroup->addComplianceView($HWAGLnlPrograms, false, $healthTrainingEventsSection);

        $HWAGQuizView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 272, 5);
        $HWAGQuizView->setReportName('HWAG Quiz');
        $HWAGQuizView->setAttribute('report_name_link', '/content/##2hquiz');
        $HWAGQuizView->emptyLinks();
        $HWAGQuizView->setMaximumNumberOfPoints(15);
        //$HWAGQuizView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=272'));
        $wellnessGroup->addComplianceView($HWAGQuizView, false, $healthTrainingEventsSection);

        $healthAnnualMembershipView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 273, 10);
        $healthAnnualMembershipView->setReportName('Health Program Annual Membership');
        $healthAnnualMembershipView->setAttribute('report_name_link', '/content/##2imember');
        $healthAnnualMembershipView->emptyLinks();
        $healthAnnualMembershipView->addLink(new link('Certification Form', '#'));
        $healthAnnualMembershipView->setMaximumNumberOfPoints(20);
        //$healthAnnualMembershipView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=273'));
        $wellnessGroup->addComplianceView($healthAnnualMembershipView, false, $healthTrainingEventsSection);

        $runWalk5kView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 279, 20);
        $runWalk5kView->setReportName('Run/walk a race (5k)');
        $runWalk5kView->setAttribute('report_name_link', '/content/##2j5k');
        $runWalk5kView->emptyLinks();
        $runWalk5kView->addLink(new link('Certification Form', '#'));
        $runWalk5kView->setMaximumNumberOfPoints(40);
        //$runWalk5kView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=279'));
        $wellnessGroup->addComplianceView($runWalk5kView, false, $healthTrainingEventsSection);

        $runWalk10kView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 280, 30);
        $runWalk10kView->setReportName('Run/walk a race (10k)');
        $runWalk10kView->setAttribute('report_name_link', '/content/##2k10k');
        $runWalk10kView->emptyLinks();
        $runWalk10kView->addLink(new link('Certification Form', '#'));
        $runWalk10kView->setMaximumNumberOfPoints(60);
        //$runWalk10kView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=280'));
        $wellnessGroup->addComplianceView($runWalk10kView, false, $healthTrainingEventsSection);

        $runWalkHalfMarathonView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 281, 50);
        $runWalkHalfMarathonView->setReportName('Run/walk a race (a half Marathon)');
        $runWalkHalfMarathonView->setAttribute('report_name_link', '/content/##2lhalfman');
        $runWalkHalfMarathonView->emptyLinks();
        $runWalkHalfMarathonView->addLink(new link('Certification Form', '#'));
        $runWalkHalfMarathonView->setMaximumNumberOfPoints(100);
        //$runWalkHalfMarathonView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=281'));
        $wellnessGroup->addComplianceView($runWalkHalfMarathonView, false, $healthTrainingEventsSection);

        $runWalkFullMarathonView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 282, 100);
        $runWalkFullMarathonView->setReportName('Run/walk a race (a full Marathon)');
        $runWalkFullMarathonView->setAttribute('report_name_link', '/content/##2mfullman');
        $runWalkFullMarathonView->emptyLinks();
        $runWalkFullMarathonView->addLink(new link('Certification Form', '#'));
        $runWalkFullMarathonView->setMaximumNumberOfPoints(100);
        //$runWalkFullMarathonView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=282'));
        $wellnessGroup->addComplianceView($runWalkFullMarathonView, false, $healthTrainingEventsSection);

        $tournamentParticipationView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 283, 10);
        $tournamentParticipationView->setReportName('Tournament Participation');
        $tournamentParticipationView->setAttribute('report_name_link', '/content/##2ntourn');
        $tournamentParticipationView->emptyLinks();
        $tournamentParticipationView->addLink(new link('Certification Form', '#'));
        $tournamentParticipationView->setMaximumNumberOfPoints(20);
        //$tournamentParticipationView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=283'));
        $wellnessGroup->addComplianceView($tournamentParticipationView, false, $healthTrainingEventsSection);

        $sportTeamParticipationView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 284, 30);
        $sportTeamParticipationView->setReportName('Sport Team Participation');
        $sportTeamParticipationView->setAttribute('report_name_link', '/content/##2oteam');
        $sportTeamParticipationView->emptyLinks();
        $sportTeamParticipationView->addLink(new link('Certification Form', '#'));
        $sportTeamParticipationView->setMaximumNumberOfPoints(60);
        //$sportTeamParticipationView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=284'));
        $wellnessGroup->addComplianceView($sportTeamParticipationView, false, $healthTrainingEventsSection);

        $fitnessClubParticipationView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 285, 30);
        $fitnessClubParticipationView->setReportName('Fitness Club Participation');
        $fitnessClubParticipationView->setAttribute('report_name_link', '/content/##2pfitclub');
        $fitnessClubParticipationView->emptyLinks();
        $fitnessClubParticipationView->setMaximumNumberOfPoints(60);
        //$fitnessClubParticipationView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=285'));
        $wellnessGroup->addComplianceView($fitnessClubParticipationView, false, $healthTrainingEventsSection);

        $miscPointsView = new PlaceHolderComplianceView(null, 0);
        $miscPointsView->setName('misc_points');
        $miscPointsView->setReportName('Misc. Points Earned');
        $miscPointsView->emptyLinks();
        $miscPointsView->setAttribute('report_name_link', '/content/##2qmisc');
        $wellnessGroup->addComplianceView($miscPointsView, false, $healthTrainingEventsSection);

        $UCANTeamWalkingChallengeWinnerView = new CHPDemoFitnessTeamWalkingChallengeWinnerView();
        $UCANTeamWalkingChallengeWinnerView->setMaximumNumberOfPoints(20);
        $wellnessGroup->addComplianceView($UCANTeamWalkingChallengeWinnerView, false, $walkingProgramsSection);

        $UCANIndividualWalkingChallenge6kView = new HMIIndividualWalkingChallengeSixThousandSteps($programStart, $programEnd, 276, 25);
        $UCANIndividualWalkingChallenge6kView->setReportName('HMI Individual Walking Challenge (6,000 steps)');
        $UCANIndividualWalkingChallenge6kView->setAttribute('report_name_link', '/content/##2r6kchall');
        $UCANIndividualWalkingChallenge6kView->emptyLinks();
        $UCANIndividualWalkingChallenge6kView->setMaximumNumberOfPoints(25);
        $UCANIndividualWalkingChallenge6kView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=276'));
        $wellnessGroup->addComplianceView($UCANIndividualWalkingChallenge6kView, false, $walkingProgramsSection);

        $UCANIndividualWalkingChallenge8kView = new HMIIndividualWalkingChallengeEightThousandSteps($programStart, $programEnd, 277, 10);
        $UCANIndividualWalkingChallenge8kView->setReportName('HMI Individual Walking Challenge (8,000 steps)');
        $UCANIndividualWalkingChallenge8kView->setAttribute('report_name_link', '/content/##2s8kchall');
        $UCANIndividualWalkingChallenge8kView->emptyLinks();
        $UCANIndividualWalkingChallenge8kView->setMaximumNumberOfPoints(10);
        $UCANIndividualWalkingChallenge8kView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=277'));
        $wellnessGroup->addComplianceView($UCANIndividualWalkingChallenge8kView, false, $walkingProgramsSection);

        $UCANIndividualWalkingChallenge10kView = new HMIIndividualWalkingChallengeTenThousandSteps($programStart, $programEnd, 278, 15);
        $UCANIndividualWalkingChallenge10kView->setReportName('HMI Individual Walking Challenge (10,000 steps)');
        $UCANIndividualWalkingChallenge10kView->setAttribute('report_name_link', '/content/##2t10kchall');
        $UCANIndividualWalkingChallenge10kView->emptyLinks();
        $UCANIndividualWalkingChallenge10kView->setMaximumNumberOfPoints(15);
        $UCANIndividualWalkingChallenge10kView->addLink(new Link('Enter', '/content/12048?action=showActivity&activityidentifier=278'));
        $wellnessGroup->addComplianceView($UCANIndividualWalkingChallenge10kView, false, $walkingProgramsSection);

        $walkingOnSunshineView = new CHPDemoFitnessWalkingSunshineView();
        $walkingOnSunshineView->setAttribute('report_name_link', '/content/##walking_program');
        $walkingOnSunshineView->setMaximumNumberOfPoints(40);
        $wellnessGroup->addComplianceView($walkingOnSunshineView, false, $walkingProgramsSection);

        $holidayHustleView = new CHPDemoFitnessHolidayHustleView();
        $holidayHustleView->setAttribute('report_name_link', '/content/##walking_program');
        $holidayHustleView->setMaximumNumberOfPoints(40);
        $holidayHustleView->addLink(new Link('Enter/Update Info', '/content/ucan-fitbit-individual'));
        $wellnessGroup->addComplianceView($holidayHustleView, false, $walkingProgramsSection);

        $wellnessGroup->setPointsRequiredForCompliance(100);



        $this->addComplianceViewGroup($wellnessGroup);


    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new CHPDemoFitnessComplianceProgramReportPrinter();

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2012-08-15';
}


class CHPDemoFitnessComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);

        $this->screeningLinkArea = '<br/><br/>
      Green Range = 10 pts <br/>
      Yellow Range = 5 pts<br/><br/>
      <a href="/content/##2aKBHM"> Click here for more info &amp; tips to improve.</a><br/><br/>
      <em>Points appear 5-10 days after screening.</em>
    ';
    }


public function printHeader(ComplianceProgramStatus $status)
{
    $this->setShowTotal(false);
    $this->setScreeningResultsLink(new Link('Have These Screening Results in the Ideal Zone:', '/content/##2aKBHM'));

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
    </style>

    <script type="text/javascript">
        // Set max points text for misc points earned

        $(function() {
            $('tr.view-misc_points td.points:eq(1)').html('Varies');

            $('tr.headerRow.viewSectionRow.walking-programs').next().after("<tr><th colspan='4' style='text-align:center; color: white; background-color:#436EEE;'>Individual Walking Programs</th></tr>");

            $('tr.view-activity_278').after("<tr><th colspan='4' style='text-align:center; color: white; background-color:#436EEE;'>Team Walking Challenges</th></tr>");

        });
    </script>
    <!-- Text atop report card-->
    <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

    <!--<p>All participants on UCAN’s health plan will receive a $25/month reduction on their health plan premiums when the HPA and blood draw are completed. Non-smokers will receive an additional $5/month reduction on their health plan premiums for a total of $30/month premium reduction available. A non-smoker affidavit will be required. </p>

    <p>
        This discount will be applied July 1st, 2013 to December 31, 2013. Participants will be required to earn 100 points for participating in the wellness program June 1st to December 31st to continue their discount from January 1st, 2014 to June 30th, 2014.
    </p> -->


    <p>Some tips for using the table are as follows:
    <ul>
        <li>The first column lists the event or action you can complete.  If the text is <span style="color: #0276FD;">blue </span>you can click on it to learn more.  </li>
        <li>The second column lists completion of the task and/or the number of points you have earned to date. </li>
        <li>The third column shows the possible number of points available in each category. </li>
        <li>The last column provides you with links for further information or it allows you to sign up or see results.   </li>
    </ul></p>

    <p>By participating in the incentive program you are receiving benefits from improvement in your health and wellbeing.  Your efforts can help you avoid health problems and related expenses each year.  </p>





<?php
}

    public $showUserNameInLegend = true;
}
