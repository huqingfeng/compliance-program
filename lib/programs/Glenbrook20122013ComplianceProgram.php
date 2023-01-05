<?php

use hpn\steel\query\SelectQuery;

class Glenbrook2013SemesterWrapperView extends ComplianceView
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

class Glenbrook2013WearableRewardComplianceView extends ComplianceView
{
    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function __construct(array $sections)
    {
        $this->sections = $sections;
    }

    public function getDefaultReportName()
    {
        return 'Wearable Reward';
    }

    public function getDefaultName()
    {
        return 'wearable_reward';
    }

    public function getStatus(User $user)
    {
        $compliant = true;

        $attributes = array();

        foreach($this->sections as $section) {
            $sectionPoints = $this->getPoints($user, $section['views']);

            $attributes[$section['name']] = $sectionPoints;

            if($sectionPoints < $section['points']) {
                $compliant = false;
            }
        }

        $status = new ComplianceViewStatus(
            $this,
            $compliant ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT
        );

        foreach($attributes as $key => $value) {
            $status->setAttribute($key, $value);
        }

        return $status;
    }

    private function getPoints(User $user, $views)
    {
        $points = 0;

        foreach($views as $view) {
            $points += $view->getMappedStatus($user)->getPoints();
        }

        return $points;
    }

    private $sections;
}

class Glenbrook20122013ComplianceProgram extends ComplianceProgram
{
    /**
     * Redirects users to the registration page if they are not registered.
     *
     * @param sfActions $actions
     */
    public function handleInvalidUser(sfActions $actions)
    {
        $actions->getUser()->setNoticeFlash('You must register for the program below before viewing your report card.');
        $actions->redirect('/content/1179');
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        $userIds = SelectQuery::create()
            ->from('users u')
            ->select('u.id')
            ->innerJoin('mileage_registrants r')
            ->on('r.user_id = u.id AND r.creation_date >= ?', array(new \DateTime('2012-08-01 00:00:00')))
            ->hydrateScalar()
            ->execute();

        $this->setBoundUserIds($userIds->toArray(), ComplianceProgram::MODE_INDIVIDUAL);

        parent::preQuery($query, $withViews);
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Fall Raffle – complete BOTH the Annual Shape Your Life Wellness Screening and Health Power Assessment by October 5, 2012.');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Wellness Screening (for Fall raffle + Spring raffle points)');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('report_name_link', '/content/1094#1ascreen');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Details', '/content/1075'));
        $screeningView->addLink(new Link('Sign-Up', '/content/1051'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->emptyLinks()->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setReportName('Health Power Assessment  (for Fall raffle + Spring raffle points)');
        $hraView->setAttribute('report_name_link', '/content/1094#1bhpa');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $keySection = 'Key Health Measures - earn up to 80 points';
        $pcSection = 'Preventive Care - earn up to 440 points';
        $learnSection = 'Learning - earn up to 405 points';
        $exerciseSection = 'Exercise / Fitness - earn up to 1000 points';

        $raffleGroup = new ComplianceViewGroup('points', 'Spring Raffle – earn points that translate to 1 raffle ticket for every 100 points by April 30, 2013');

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);

        $raffleGroup->addComplianceView($totalCholesterolView, false, $keySection);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($hdlCholesterolView, false, $keySection);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($ldlCholesterolView, false, $keySection);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($trigView, false, $keySection);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($glucoseView, false, $keySection);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($bloodPressureView, false, $keySection);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $raffleGroup->addComplianceView($bodyFatBMIView, false, $keySection);

        $nonSmokerView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker / Low Exposure to Nicotine/Cotinine');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($nonSmokerView, false, $keySection);

        $hpaDone = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hpaDone->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(300, 0, 0, 0));
        $hpaDone->setName('hpa_screen');
        $hpaDone->setReportName('HPA and Annual Wellness Screening');
        $hpaDone->setAttribute('report_name_link', '/content/1094#2bscreenHPA');
        $raffleGroup->addComplianceView($hpaDone, false, $pcSection);

        $workWithHealthCoachView = new PlaceHolderComplianceView(null, 0);
        $workWithHealthCoachView->setName('work_with_health_coach');
        $workWithHealthCoachView->setReportName('Work with health coach or doctor to improve yellow or red screening results');
        $workWithHealthCoachView->setMaximumNumberOfPoints(40);
        $workWithHealthCoachView->addLink(new Link('Coach Info', '/content/8733'));
        $workWithHealthCoachView->addLink(new Link('Get Dr. Form', '/resources/3934/GBD225 Form Dr Option for Screening Follow-Up 080812.pdf'));
        $workWithHealthCoachView->setAttribute('report_name_link', '/content/1094#2ccoach');
        $raffleGroup->addComplianceView($workWithHealthCoachView, false, $pcSection);

        $fluVaccineView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $fluVaccineView->setReportName('Obtain flu shot during onsite wellness screening date ');
        $fluVaccineView->addLink(new Link('Sign-Up', '#'));
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $fluVaccineView->setName('flu_shot');
        $fluVaccineView->setAttribute('report_name_link', '/content/1094#2dflu');
        $fluVaccineView->emptyLinks();
        $raffleGroup->addComplianceView($fluVaccineView, false, $pcSection);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Have a Main /Primary Care Provider');
        $doctorInformationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $doctorInformationView->setName('have_doctor');
        $doctorInformationView->setAttribute('report_name_link', '/content/1094#2eMD');
        $doctorInformationView->emptyLinks();
        $raffleGroup->addComplianceView($doctorInformationView, false, $pcSection);

        $vacVaccineView = new CompleteArbitraryActivityComplianceView(strtotime('-10 years', strtotime(self::ROLLING_START_DATE_ACTIVITY_DATE)), $programEnd, 254, 5);
        $vacVaccineView->setMaximumNumberOfPoints(5);
        $vacVaccineView->setReportName('Have up-to-date Tetanus or Tdap immunization ');
        $vacVaccineView->setName('flu_vaccine');
        $vacVaccineView->setAttribute('report_name_link', '/content/1094#2fTd');
        $vacVaccineView->emptyLinks();
        $raffleGroup->addComplianceView($vacVaccineView, false, $pcSection);

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
        $preventiveExamsView->setMaximumNumberOfPoints(25);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#2gprevExam');
        $preventiveExamsView->emptyLinks();
        $raffleGroup->addComplianceView($preventiveExamsView, false, $pcSection);

        $lnlPrograms = new AttendEventComplianceView($programStart, $programEnd);
        $lnlPrograms->bindTypeIds(array(9));
        $lnlPrograms->setPointsPerAttendance(75);
        $lnlPrograms->setReportName('Attend SYL Lunch & Learn programs');
        $lnlPrograms->setAttribute('report_name_link', '/content/1094#2hlunch');
        $lnlPrograms->setMaximumNumberOfPoints(300);
        $lnlPrograms->addLink(new Link('View Events/Sign-Up', '/content/4820?action=Listing&actions[Listing]=eventList&actions[Calendar]=eventBulletin&actions[My+Registrations+%26+Waitlists]=viewScheduledEvents'));
        $raffleGroup->addComplianceView($lnlPrograms, false, $learnSection);

        $elearnView = new CompleteELearningGroupSet($programStart, $programEnd, 'required_2012');
        $elearnView->setReportName('Complete recommended e-Learning Lessons  (max of 3)');
        $elearnView->setName('elearning');
        $elearnView->setMaximumNumberOfPoints(30);
        $elearnView->setPointsPerLesson(10);
        $elearnView->setAttribute('report_name_link', '/content/1094#2ieLearn');
        $elearnView->emptyLinks();
        $elearnView->addLink(new Link('Review/Do Lessons', '/content/eLearning_middle_page'));
        $raffleGroup->addComplianceView($elearnView, false, $learnSection);

        $offsiteWellnessView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 405, 25);
        $offsiteWellnessView->setReportName('Participate in offsite health/wellness programs');
        $offsiteWellnessView->emptyLinks();
        $offsiteWellnessView->setAttribute('report_name_link', '/content/1094#2eKeyWB');
        $offsiteWellnessView->setMaximumNumberOfPoints(75);
        $raffleGroup->addComplianceView($offsiteWellnessView, false, $learnSection);

        $onsiteFitnessView = new PlaceHolderComplianceView(null, 0);
        $onsiteFitnessView->setReportName('Participation in SYL onsite group fitness class (max 150 pts/semester)');
        $onsiteFitnessView->setAttribute('report_name_link', '/content/1094#2konFit');
        $onsiteFitnessView->setMaximumNumberOfPoints(300);
        $onsiteFitnessView->addLink(new Link('Fitness Classes Info', '/content/sylfitnesssched'));
        $raffleGroup->addComplianceView($onsiteFitnessView, false, $exerciseSection);

        $offsiteFitView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 258, 100);
        $offsiteFitView->setReportName('Participation in offsite group fitness class (max 50 pts/semester)	');
        $offsiteFitView->setAttribute('report_name_link', '/content/1094#2loffFit');
        $offsiteFitView->emptyLinks();
        $offsiteFitView->setMaximumNumberOfPoints(100);
        $offsiteFitViewWrapper = new Glenbrook2013SemesterWrapperView($offsiteFitView);
        $offsiteFitViewWrapper->setSemesterPoints(50);
        $raffleGroup->addComplianceView($offsiteFitViewWrapper, false, $exerciseSection);

        $gymMembershipView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 256, 100);
        $gymMembershipView->setReportName('Gym membership and/or work with personal trainer (max 100 points each/semester)');
        $gymMembershipView->setAttribute('report_name_link', '/content/1094#22nRPA');
        $gymMembershipView->emptyLinks();
        $gymMembershipViewWrapper = new Glenbrook2013SemesterWrapperView($gymMembershipView);
        $gymMembershipViewWrapper->setSemesterPoints(100);
        $raffleGroup->addComplianceView($gymMembershipViewWrapper, false, $exerciseSection);

        $exerciseView = new PhysicalActivityComplianceView('2012-09-17', $programEnd);
        $exerciseView->setReportName('Other regular exercise (1 pt/hour;  max 200/year)');
        $exerciseView->setAttribute('report_name_link', '/content/1094#2mgym');        
        $exerciseView->_setID(260);
        $exerciseView->setMaximumNumberOfPoints(200);
        $exerciseView->setMinutesDivisorForPoints(60);
        $exerciseView->setPointsMultiplier(1);
        $exerciseView->setFractionalDivisorForPoints(1);
        $exerciseView->emptyLinks();

        $exerciseViewWrapper = new Glenbrook2013SemesterWrapperView($exerciseView);
        $exerciseViewWrapper->setSemesterPoints(100);
        $raffleGroup->addComplianceView($exerciseViewWrapper, false, $exerciseSection);

        $partEventsView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 257, 50);
        $partEventsView->setReportName('Participate in Charity/Walk/Run Events (between 5/11/12 & 5/10/13)');
        $partEventsView->setAttribute('report_name_link', '/content/1094#2ocharity');
        $partEventsView->emptyLinks();
        $partEventsView->addLink(new Link('Report Event', '/content/documentation_participation'));
        $partEventsView->setMaximumNumberOfPoints(200);
        $raffleGroup->addComplianceView($partEventsView, false, $exerciseSection);

        $raffleGroup->setPointsRequiredForCompliance(100);

        $this->addComplianceViewGroup($raffleGroup);

        $evaluators = new ComplianceViewGroup('evaluators', 'Evaluators');

        $wearableRewardView = new Glenbrook2013WearableRewardComplianceView(array(
            array(
                'name'   => 'cj',
                'points' => 150,
                'views'  => array($workWithHealthCoachView, $fluVaccineView, $doctorInformationView, $vacVaccineView, $preventiveExamsView, $lnlPrograms, $elearnView, $offsiteWellnessView),
            ),
            array(
                'name'   => 'ko',
                'points' => 350,
                'views'  => array($onsiteFitnessView, $offsiteFitViewWrapper, $gymMembershipViewWrapper, $exerciseViewWrapper, $partEventsView),
            )
        ));

        $wearableRewardView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));

        $evaluators->addComplianceView($wearableRewardView, true);

        $this->addComplianceViewGroup($evaluators);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Glenbrook20122013ScreeningPrinter();
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
            $printer = new Glenbrook20122013ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2012-08-15';
}

class Glenbrook20122013ScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        ?>
    <br/> <br/> <br/>
    <table border="0" width="100%" style="font-size: 10px;" id="ratingsTable">
    <tbody>
    <tr>
        <td width="190">
            Risk ratings &amp; colors =
        </td>
        <td align="center" width="72">
            <strong><font color="#006600">OK/Good</font></strong></td>
        <td align="center" width="73">
            <strong><font color="#ff9933">Borderline</font></strong></td>
        <td align="center" width="112">
            <strong><font color="#ff0000">At-Risk</font> </strong></td>
    </tr>
    <tr>
        <td>
        </td>
        <td align="center" width="72">
        </td>
        <td align="center" width="73">
        </td>
        <td align="center" width="112">
        </td>
    </tr>
    <tr height="36px">
        <td>
            <p>
                <em>Points for each result<br>
                </em><em>that falls in this column =</em></p>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72" class="grayArrow">
            10 points
        </td>
        <td bgcolor="#ffff00" align="center" width="73" class="grayArrow">
            5 points
        </td>
        <td bgcolor="#ff909a" align="center" width="112" class="grayArrow">
            0 points
        </td>
    </tr>
    <tr>
        <td>
            <u>Key measures and ranges</u></td>
        <td bgcolor="#ccffcc" align="center" width="72">
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
        </td>
    </tr>
    <tr>
        <td>
            <ol>
                <li>
                    <strong>Total cholesterol</strong></li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72">
            &lt;200
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            200-240
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            &gt;240
        </td>
    </tr>
    <tr>
        <td>
            <ol start="2">
                <li>
                    <strong>HDL cholesterol</strong></li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72">
            ≥40
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            25-39
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            &lt;25
        </td>
    </tr>
    <tr>
        <td>
            <ol start="3">
                <li>
                    <strong>LDL cholesterol</strong></li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72">
            ≤129
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            130-158
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            ≥159
        </td>
    </tr>
    <tr>
        <td>
            <ol start="4">
                <li>
                    <strong>Blood pressure</strong><br>
                    Systolic<br>
                    Diastolic
                </li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
            &lt;120/<br>
            &lt;80
        </td>
        <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
            120-139/<br>
            80-89
        </td>
        <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
            ≥140/<br>
            ≥90
        </td>
    </tr>
    <tr>
        <td>
            <ol start="5">
                <li>
                    <strong>Glucose</strong></li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72">
            &lt;100
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            100-124
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            ≥125
        </td>
    </tr>
    <tr>
        <td>
            <ol start="6">
                <li>
                    <strong>Triglycerides</strong></li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72">
            &lt;150
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            150-199
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            ≥200
        </td>
    </tr>
    <tr>
        <td valign="bottom">
            <ol start="7">
                <li>
                    The better of:<br>
                    <strong>Body Mass Index <br>
                    </strong>• men &amp; women<br>
                    - OR -<br>
                    <strong>% Body Fat:</strong><br>
                    • Men<br>
                    • Women
                </li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
            <p>
                18.5&lt;25<br>
                <br>
                <br>
                6&lt;18%<br>
                14&lt;25%</p>
        </td>
        <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
            <p>
                25&lt;30<br>
                <br>
                <br>
                18&lt;25<br>
                25&lt;32</p>
        </td>
        <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
            <p>
                ≥30; &lt;18.5<br>
                <br>
                <br>
                ≥25; &lt;6%<br>
                ≥32; &lt;14%</p>
        </td>
    </tr>
    <tr>
        <td>
            <ol start="8">
                <li>
                    <strong>Tobacco/Cotinine</strong></li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72">
            &lt;2
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            2-9
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            ≥10
        </td>
    </tr>
    </tbody>
    </table>
    <?php
    }
}

class Glenbrook20122013ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->screeningLinkArea = '<br/><br/>
      Green Range = 10 pts <br/>
      Yellow Range = 5 pts<br/><br/>
      <a href="/content/1094#2aKBHM"> Click here for more info &amp; tips to improve.</a><br/><br/>
      <em>Points appear 5-10 days after screening.</em>
    ';
    }

    protected function showGroup($group)
    {
        return $group->getName() != 'evaluators';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(false);
        $this->setScreeningResultsLink(new Link('Have These Screening Results in the Ideal Zone:', '/content/1094#2aKBHM'));

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

    <div id="altPageHeading">2012/2013 Rewards/To-Do Summary Page</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>


    <p>Welcome to your Glenbrook 2012-13 Rewards/To-Do summary page.</p>
    <p>Glenbrook District 225 employees can earn rewards in the following ways:

    </p>
    <ol>
        <li>2012 Screening Raffle: Get 1 ticket for the Fall raffle by completing the annual wellness screening AND health
            power assessment. Prizes include 8 iPods and 4 iPads!<br/>
            <br/>
        </li>
        <li>2012 Semester I Gift Card Raffle: Use options 2A-O below to earn points for key actions you are taking for your health
          and wellbeing PLUS some bonus points for certain screening results. Get 1 raffle
          ticket for every 100 points earned.  Twenty (20) surprise gift cards will be awarded<br/><br/>
        </li>

        <li>2013 Semester II Raffle: Use options 2A-O below to earn points for key actions you are taking for your health and
            wellbeing PLUS some bonus points for certain screening results. Get 1 raffle ticket for every 100 points.
            More points = more raffle tickets! Prizes include more iPads and iPods.<br /><br />

        </li>
        <li>2013 Wearable Award: Earn 500 or more total points as follows:
            <ul><li>150 or more points or must be from preventive care and/or learning actions C-J;  AND</li>
            <li>350 ore more points must be from exercise actions K-O.</li></ul>

        </li>
    </ol>
    <p>All requirements for each raffle must be met by the deadlines noted in the table below.</p>
    <p>Here are some tips about the table below and using it:

    <ul>
        <li>In the first column, click on the text in blue to learn why the action is important.</li>
        <li>Use the Action Links in the right column to get things done or more information.
        </li>
        <li><a href="/content/1094">Click here </a>for more details about the actions, benefits and rewards.
        </li>


    </ul>    </p>

    <div class="pageHeading">
        <a href="/content/1094">
            Click here to view the full details of all Reward Activities listed below
        </a>.
    </div>


    <?php
    }

    protected function printCustomRows($status)
    {
        $oneAb = $status->getComplianceViewStatus('complete_hra')->isCompliant() &&
            $status->getComplianceViewStatus('complete_screening')->isCompliant();

        $program = $status->getComplianceProgram();

        $firstSemesterProgram = $program->cloneForEvaluation($program->getStartDate(), '2012-12-18');

        $firstSemesterProgram->setActiveUser($status->getUser());

        Glenbrook2013SemesterWrapperView::setFirstSemesterOnly(true);

        $firstSemesterProgramStatus = $firstSemesterProgram->getStatus();

        Glenbrook2013SemesterWrapperView::setFirstSemesterOnly(false);

        $wearable = $status->getComplianceViewStatus('wearable_reward');
        $wearableMax = $wearable->getComplianceView()->getMaximumNumberOfPoints();
        ?>
    <tr>
        <td style="text-align:right; padding:8px;">
            Total earned as of <?php echo date('m/d/Y') ?> = <br/>
            Points sub-total from C-J = <br/>
            Points sub-total from K-O =
        </td>
        <td style="text-align:center">
            <?php echo $status->getPoints() ?> <br/>
            <?php echo $wearable->getAttribute('cj') ?> <br/>
            <?php echo $wearable->getAttribute('ko') ?>
        </td>
        <td colspan="2" style="text-align:center">
            1,925 points possible! <br/>
            575 points possible!  <br/>
            1,000 points possible!
        </td>
    </tr>
    <tr class="headerRow">
        <th><strong>3</strong>. Rewards &amp; Eligibility Requirements</th>
        <td># Earned</td>
        <td colspan="2"># Possible</td>
    </tr>
    <tr>
        <td style="text-align:right;">
            2012 Screening Raffle: 1A & 1B done by
            10/05/12 =
        </td>
        <td style="text-align:center;">
            <?php echo $oneAb ? 1 : 0 ?>
        </td>
        <td style="text-align:center;" colspan="2">
            1 ticket per person for the Fall raffle.
        </td>
    </tr>
    <tr>
        <td style="text-align:right;">
            2012 Semester I Gift Card Raffle:  # Tickets by 12/18/12 <?php //echo date('m/d/Y') ?> = <br/>
            <!--End-of-Year Raffle: # Tickets by April 30, 2012 <?php //echo date('m/d/Y') ?>  = -->
        </td>
        <td style="text-align:center;">
          <?php echo floor($firstSemesterProgramStatus->getPoints() / 100) ?>
        </td>
        <td style="text-align:center;" colspan="2">
            About 10 tickets possible per person
        </td>
    </tr>




    <tr>
        <td style="text-align:right;">
            2013 Semester II Raffle: # Tickets by 04/30/13 <?php //echo date('m/d/Y') ?> = <br/>
            <!--End-of-Year Raffle: # Tickets by April 30, 2012 <?php //echo date('m/d/Y') ?>  = -->
        </td>
        <td style="text-align:center;">
            <?php echo floor($status->getPoints() / 100) ?>
        </td>
        <td style="text-align:center;" colspan="2">
            19 tickets possible per person for raffle
        </td>
    </tr>





    <tr>
        <td style="text-align:right;">
            2013 Wearable Award: &ge; 150 points from C-J AND &ge; 350 points from K-O by 04/30/13 =
        </td>
        <td style="text-align:center;">
            <?php echo $wearable->getPoints() ?>
        </td>
        <td style="text-align:center;" colspan="2">
            1 wearable possible per person
        </td>
    </tr>

    <?php
    }

    public $showUserNameInLegend = true;
}
