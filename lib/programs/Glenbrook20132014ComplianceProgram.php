<?php

use hpn\steel\query\SelectQuery;

class Glenbrook2014SemesterWrapperView extends ComplianceView
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

        $this->view->setStartDate('2013-08-19');
        $this->view->setEndDate('2013-12-31');

        $semesterOneStatus = $this->view->getMappedStatus($user);

        $this->view->setStartDate('2014-01-01');
        $this->view->setEndDate('2014-05-02');

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

class Glenbrook20132014ComplianceProgram extends ComplianceProgram
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
            ->on('r.user_id = u.id AND r.creation_date >= ?', array(new \DateTime('2013-08-01 00:00:00')))
            ->hydrateScalar()
            ->execute();

        $this->setBoundUserIds($userIds->toArray(), ComplianceProgram::MODE_ALL);

        parent::preQuery($query, $withViews);
    }

    public function getRaffleTickets(ComplianceProgramStatus $status)
    {
        $oneAb = $status->getComplianceViewStatus('complete_hra')->isCompliant() &&
            $status->getComplianceViewStatus('complete_screening')->isCompliant();

        $program = $status->getComplianceProgram();

        $firstSemesterProgram = $program->cloneForEvaluation($program->getStartDate(), '2013-12-17');

        $firstSemesterProgram->setActiveUser($status->getUser());

        Glenbrook2014SemesterWrapperView::setFirstSemesterOnly(true);

        $firstSemesterProgramStatus = $firstSemesterProgram->getStatus();

        Glenbrook2014SemesterWrapperView::setFirstSemesterOnly(false);

        return array(
            '2013_screening_raffle_tickets'          => $oneAb ? 1 : 0,
            '2013_winter_i_gift_card_raffle_tickets' => floor($firstSemesterProgramStatus->getPoints() / 100),
            '2014_spring_raffle_tickets'             => floor($status->getPoints() / 100),
            '2014_wearable_award_tickets'            => $status->getPoints() >= 500 ? 1 : 0
        );
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $screeningStartDate = '2013-06-01';

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Fall Screening Raffle – complete the online Health Power Assessment by October 1, 2013 PLUS either the onsite Shape Your Life Wellness Screening or other qualified wellness screening.');

        $screeningView = new CompleteScreeningComplianceView($screeningStartDate, $programEnd);
        $screeningView->setReportName('Annual Wellness Screening (Entry ticket for Fall Screening Raffle, points toward Winter and Spring Raffle tickets if HPA also completed)');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('report_name_link', '/content/1094#1ascreen');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Details', '/content/1075'));
        $screeningView->addLink(new Link('Sign-Up', '#'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->emptyLinks()->addLink(new Link('Do HPA', '#'));
        $hraView->setReportName('Health Power Assessment (HPA)');
        $hraView->setAttribute('report_name_link', '/content/1094#1bhpa');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $keySection = 'Key Health Measures - earn up to 80 points';
        $pcSection = 'Preventive Care - earn up to 440 points';
        $learnSection = 'Learning - earn up to 705 points';
        $exerciseSection = 'Exercise / Fitness - earn up to 1000 points';

        $raffleGroup = new ComplianceViewGroup('points', 'Winter and Spring Raffles – earn points that translate to 1 raffle ticket for every 100 points earned by drawing deadlines.');

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($screeningStartDate, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/content/1094#2aKBHM');

        $raffleGroup->addComplianceView($totalCholesterolView, false, $keySection);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($screeningStartDate, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->setAttribute('report_name_link', '/content/1094#2aKBHM');
        $raffleGroup->addComplianceView($hdlCholesterolView, false, $keySection);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($screeningStartDate, $programEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->setAttribute('report_name_link', '/content/1094#2aKBHM');
        $raffleGroup->addComplianceView($ldlCholesterolView, false, $keySection);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($screeningStartDate, $programEnd);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->setAttribute('report_name_link', '/content/1094#2aKBHM');
        $raffleGroup->addComplianceView($trigView, false, $keySection);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($screeningStartDate, $programEnd);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->setAttribute('report_name_link', '/content/1094#2aKBHM');
        $raffleGroup->addComplianceView($glucoseView, false, $keySection);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStartDate, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->setAttribute('report_name_link', '/content/1094#2aKBHM');
        $raffleGroup->addComplianceView($bloodPressureView, false, $keySection);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($screeningStartDate, $programEnd);
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setAttribute('report_name_link', '/content/1094#2aKBHM');
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
//        $bodyFatBMIView->addLink(new link('Green Range = 10 pts'));
        $raffleGroup->addComplianceView($bodyFatBMIView, false, $keySection);

        $nonSmokerView = new ComplyWithCotinineScreeningTestComplianceView($screeningStartDate, $programEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setAttribute('report_name_link', '/content/1094#2aKBHM');
        $nonSmokerView->setReportName('Non-Smoker / Low Exposure to Nicotine/Cotinine');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($nonSmokerView, false, $keySection);

        $hpaDone = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hpaDone->setScreeningDates($screeningStartDate, $programEnd);
        $hpaDone->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(300, 0, 0, 0));
        $hpaDone->setName('hpa_screen');
        $hpaDone->setReportName('HPA and Annual Wellness Screening');
        $hpaDone->setAttribute('report_name_link', '/content/1094#2bscreenHPA');
        $raffleGroup->addComplianceView($hpaDone, false, $pcSection);

        $workWithHealthCoachView = new PlaceHolderComplianceView(null, 0);
        $workWithHealthCoachView->setName('work_with_health_coach');
        $workWithHealthCoachView->setReportName('Work with health coach or doctor to improve yellow or red screening results');
        $workWithHealthCoachView->setMaximumNumberOfPoints(40);
        $workWithHealthCoachView->addLink(new Link('Coach Info', '#'));
        $workWithHealthCoachView->addLink(new Link('Get Dr. Form', '#'));
        $workWithHealthCoachView->setAttribute('report_name_link', '/content/1094#2ccoach');
        $raffleGroup->addComplianceView($workWithHealthCoachView, false, $pcSection);

        $fluVaccineView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $fluVaccineView->setReportName('Obtain flu shot during onsite wellness screening date ');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $fluVaccineView->setName('flu_shot');
        $fluVaccineView->addLink(new Link('More Info', '/content/1094#2dflu'));
        $fluVaccineView->setAttribute('report_name_link', '/content/1094#2dflu');
        $raffleGroup->addComplianceView($fluVaccineView, false, $pcSection);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Have a Main /Primary Care Provider');
        $doctorInformationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $doctorInformationView->setName('have_doctor');
        $doctorInformationView->setAttribute('report_name_link', '/content/1094#2eMD');
        $raffleGroup->addComplianceView($doctorInformationView, false, $pcSection);

        $vacVaccineView = new CompleteArbitraryActivityComplianceView(strtotime('-10 years', strtotime(self::ROLLING_START_DATE_ACTIVITY_DATE)), $programEnd, 254, 5);
        $vacVaccineView->setMaximumNumberOfPoints(5);
        $vacVaccineView->setReportName('Have up-to-date Tetanus or Tdap immunization ');
        $vacVaccineView->setName('flu_vaccine');
        $vacVaccineView->setAttribute('report_name_link', '/content/1094#2fTd');
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
        $raffleGroup->addComplianceView($preventiveExamsView, false, $pcSection);

        $lnlPrograms = new AttendEventComplianceView($programStart, $programEnd);
        $lnlPrograms->bindTypeIds(array(9));
        $lnlPrograms->setPointsPerAttendance(75);
        $lnlPrograms->setReportName('Attend SYL Lunch & Learn programs');
        $lnlPrograms->setAttribute('report_name_link', '/content/1094#2hlunch');
        $lnlPrograms->setMaximumNumberOfPoints(300);
        $lnlPrograms->addLink(new Link('View Events/Sign-Up', '#'));
        $raffleGroup->addComplianceView($lnlPrograms, false, $learnSection);

        $elearnView = new CompleteELearningGroupSet($programStart, $programEnd, 'extra');
        $elearnView->setReportName('Complete recommended e-Learning Lessons  (max of 3)');
        $elearnView->setName('elearning');
        $elearnView->setMaximumNumberOfPoints(30);
        $elearnView->setPointsPerLesson(10);
        $elearnView->setAttribute('report_name_link', '/content/1094#2ieLearn');
        $elearnView->emptyLinks();
        $elearnView->addLink(new Link('Review/Do Lessons', '#'));
        $raffleGroup->addComplianceView($elearnView, false, $learnSection);

        $offsiteWellnessView = new PlaceHolderComplianceView(null, 0);
        $offsiteWellnessView->setAllowPointsOverride(true);
        $offsiteWellnessView->setReportName('Participate in offsite health/wellness programs');
        $offsiteWellnessView->emptyLinks();
        $offsiteWellnessView->addLink(new Link('Enter/Update Info', '#'));
        $offsiteWellnessView->setAttribute('report_name_link', '/content/1094#2joffprogram');
        $offsiteWellnessView->setMaximumNumberOfPoints(75);
        $raffleGroup->addComplianceView($offsiteWellnessView, false, $learnSection);
        
        $weightLoss = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 287, 150);
        $weightLoss->setReportName('Participate in qualified weight loss program (150 points/program; max 150 pts/semester)');
        $weightLoss->setAttribute('report_name_link', '/content/1094#2kweightloss');
        $weightLossViewWrapper = new Glenbrook2014SemesterWrapperView($weightLoss);
        $weightLossViewWrapper->setSemesterPoints(150);        
        $weightLoss->setMaximumNumberOfPoints(300);
        $raffleGroup->addComplianceView($weightLoss);

        $onsiteFitnessView = new PlaceHolderComplianceView(null, 0);
        $onsiteFitnessView->setReportName('Participate in SYL onsite group fitness class (75 points/class that meets once a week; max 150 points/semester)');
        $onsiteFitnessView->setAttribute('report_name_link', '/content/1094#2lonFit');
        $onsiteFitnessView->setMaximumNumberOfPoints(300);
        $onsiteFitnessView->addLink(new Link('Fitness Classes Info', '#'));
        $raffleGroup->addComplianceView($onsiteFitnessView, false, $exerciseSection);

        $offsiteFitView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 258, 100);
        $offsiteFitView->setReportName('Participation in offsite group fitness class (max 50 pts/semester)	');
        $offsiteFitView->setAttribute('report_name_link', '/content/1094#2moffFit');
        $offsiteFitView->setMaximumNumberOfPoints(100);
        $offsiteFitViewWrapper = new Glenbrook2014SemesterWrapperView($offsiteFitView);
        $offsiteFitViewWrapper->setSemesterPoints(50);
        $raffleGroup->addComplianceView($offsiteFitViewWrapper, false, $exerciseSection);

        $gymMembershipView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 256, 100);
        $gymMembershipView->setReportName('Gym membership and/or work with personal trainer (max 100 points each/semester)');
        $gymMembershipView->setAttribute('report_name_link', '/content/1094#2ngym');
        $gymMembershipViewWrapper = new Glenbrook2014SemesterWrapperView($gymMembershipView);
        $gymMembershipViewWrapper->setSemesterPoints(100);
        $raffleGroup->addComplianceView($gymMembershipViewWrapper, false, $exerciseSection);

        $exerciseView = new PhysicalActivityComplianceView('2013-08-19', $programEnd);
        $exerciseView->setReportName('Other regular exercise (1 pt/hour;  max 200/year)');
        $exerciseView->setAttribute('report_name_link', '/content/1094#2oregEx');        
        $exerciseView->_setID(260);
        $exerciseView->setMaximumNumberOfPoints(200);
        $exerciseView->setMinutesDivisorForPoints(60);
        $exerciseView->setPointsMultiplier(1);
        $exerciseView->setFractionalDivisorForPoints(1);

        $exerciseViewWrapper = new Glenbrook2014SemesterWrapperView($exerciseView);
        $exerciseViewWrapper->setSemesterPoints(100);
        $raffleGroup->addComplianceView($exerciseViewWrapper, false, $exerciseSection);

        $partEventsView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 257, 50);
        $partEventsView->setReportName('Participate in Charity/Walk/Run Events (between 5/11/13 & 5/02/14)');
        $partEventsView->setAttribute('report_name_link', '/content/1094#2pcharity');
        $partEventsView->emptyLinks();
        $partEventsView->addLink(new Link('Report Event', '#'));
        $partEventsView->setMaximumNumberOfPoints(200);
        $raffleGroup->addComplianceView($partEventsView, false, $exerciseSection);

        $raffleGroup->setPointsRequiredForCompliance(100);

        $this->addComplianceViewGroup($raffleGroup);
    }

    public function getAdminProgramReportPrinter()
    {
        $program = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) use($program) {
            return $program->getRaffleTickets($status);
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Glenbrook20132014ScreeningPrinter();
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
            $printer = new Glenbrook20132014ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2013-08-19';
}

class Glenbrook20132014ScreeningPrinter extends ScreeningProgramReportPrinter
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

class Glenbrook20132014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

    <div id="altPageHeading">2013/2014 Rewards/To-Do Summary Page</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>


    <p>Welcome to your Glenbrook 2013-2014 Rewards/To-Do summary page.</p>
    <p>Glenbrook District 225 employees can earn rewards in the following ways:

    </p>
    <ol>
        <li>2013 Fall Screening Raffle: Get 1 ticket by completing the Health
            Power Assessment(HPA) <u>PLUS</u> the annual onsite wellness screening OR a qualified wellness screening by your own doctor.
            Raffle for over 30 prizes, including iPad Minis and FitBits, to be held on October 31st.<br/>
            <br/>
        </li>
        <li>2013 Winter Gift Card Raffle: Use options 2A-P below to earn points for key actions you are taking for your health
          and wellbeing PLUS some bonus points for certain screening results. Get 1 raffle
          ticket for every 100 points earned.  Twenty (20) surprise gift cards will be awarded before winter break.<br/><br/>
        </li>

        <li>2014 Spring Raffle: Use options 2A-P below to earn points for key actions you are taking for your health and
            wellbeing PLUS some bonus points for certain screening results. Get 1 raffle ticket for every 100 points.
            More points = more raffle tickets! Prizes include more iPad Minis and FitBits.<br /><br />

        </li>
        <li>2014 Wearable Award: Use options 2A-P below to earn points for key actions you are taking for your health and
            wellbeing PLUS some bonus points for certain screening results. Earn 500 points to receive the 2014 Wearable Award.
        </li>
    </ol>
    <p>All requirements for each raffle must be met by the deadlines noted under #3 in the table below.</p>
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
        $raffleTickets = $status->getComplianceProgram()->getRaffleTickets($status);

        ?>
        <tr>
            <td style="text-align:right; padding:8px;">
                Total earned as of <?php echo date('m/d/Y') ?> = <br/>
            </td>
            <td style="text-align:center">
                <?php echo $status->getPoints() ?> <br/>
            </td>
            <td colspan="2" style="text-align:center">
                2,225 points possible! <br/>
            </td>
        </tr>
        <tr class="headerRow">
            <th><strong>3</strong>. Rewards &amp; Eligibility Requirements</th>
            <td># Earned</td>
            <td colspan="2"># Possible</td>
        </tr>
        <tr>
            <td style="text-align:right;">
                2013 Screening Raffle: 1A & 1B done by
                10/01/13 =
            </td>
            <td style="text-align:center;">
                <?php echo $raffleTickets['2013_screening_raffle_tickets'] ?>
            </td>
            <td style="text-align:center;" colspan="2">
                1 ticket per person for the Fall raffle.
            </td>
        </tr>
        <tr>
            <td style="text-align:right;">
                2013 Winter I Gift Card Raffle:  # Tickets by 12/17/13
            </td>
            <td style="text-align:center;">
              <?php echo $raffleTickets['2013_winter_i_gift_card_raffle_tickets'] ?>
            </td>
            <td style="text-align:center;" colspan="2">
                About 12 tickets possible per person
            </td>
        </tr>
        <tr>
            <td style="text-align:right;">
                2014 Spring Raffle: # Tickets by 05/02/14
            </td>
            <td style="text-align:center;">
                <?php echo $raffleTickets['2014_spring_raffle_tickets'] ?>
            </td>
            <td style="text-align:center;" colspan="2">
                24 tickets possible per person for raffle
            </td>
        </tr>
        <tr>
            <td style="text-align:right;">
                2014 Wearable Award: Earn 500 points by 05/02/2014
            </td>
            <td style="text-align:center;">
                <?php echo $raffleTickets['2014_wearable_award_tickets'] ?>
            </td>
            <td style="text-align:center;" colspan="2">
                1 wearable possible per person
            </td>
        </tr>
        <?php
    }

    public $showUserNameInLegend = true;
}
