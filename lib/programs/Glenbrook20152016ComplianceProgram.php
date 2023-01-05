<?php

use hpn\steel\query\SelectQuery;

class Glenbrook2016SemesterWrapperView extends ComplianceView
{
    public static function setFirstSemesterOnly($bool)
    {
        self::$firstSemesterOnly = $bool;
    }

    public function __construct(DateBasedComplianceView $view, $firstSemesterStartDate = false)
    {
        $this->view = $view;
        $this->firstSemesterStartDate = $firstSemesterStartDate;
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

        if($this->firstSemesterStartDate) {
            $this->view->setStartDate($this->firstSemesterStartDate);
        } else {
            $this->view->setStartDate('2015-05-02');
        }

        $this->view->setEndDate('2015-12-14');

        $semesterOneStatus = $this->view->getMappedStatus($user);

        $this->view->setStartDate('2015-12-15');
        $this->view->setEndDate('2016-05-02');

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

class Glenbrook20152016ComplianceProgram extends ComplianceProgram
{
    /**
     * Redirects users to the registration page if they are not registered.
     *
     * @param sfActions $actions
     */


    public function getRaffleTickets(ComplianceProgramStatus $status)
    {
        $oneAb = $status->getComplianceViewStatus('complete_hra')->isCompliant() &&
            $status->getComplianceViewStatus('complete_screening')->isCompliant();

        $program = $status->getComplianceProgram();

        $firstSemesterProgram = $program->cloneForEvaluation($program->getStartDate(), '2015-12-14');

        $firstSemesterProgram->setActiveUser($status->getUser());

        Glenbrook2016SemesterWrapperView::setFirstSemesterOnly(true);

        $firstSemesterProgramStatus = $firstSemesterProgram->getStatus();

        Glenbrook2016SemesterWrapperView::setFirstSemesterOnly(false);

        return array(
            '2015_screening_raffle_tickets'          => $oneAb ? 1 : 0,
            '2015_winter_i_gift_card_raffle_tickets' => floor($firstSemesterProgramStatus->getPoints() / 100),
            '2016_spring_raffle_tickets'             => floor($status->getPoints() / 100),
            '2016_wearable_award_tickets'            => $status->getPoints() >= 400 ? 1 : 0
        );
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $screeningStartDate = '2015-05-02';
        $hraScreeningContactEndDate = '2015-12-04';

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', '$250 Rebate Requirements - Verify/Update Contact Info, Get Annual
         Wellness Screening, Take HPA, Have Main / Primary Care Provider, and take 3 required e-Learning Lessons by December 4th, 2015.');

        $updateInfo = new UpdateContactInformationComplianceView($programStart, $hraScreeningContactEndDate);
        $updateInfo->setReportName('Verify/Update my current contact information');
        $coreGroup->addComplianceView($updateInfo);

        $screeningView = new CompleteScreeningComplianceView($programStart, $hraScreeningContactEndDate);
        $screeningView->setReportName('Annual Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Details', '/content/1075'));
        $screeningView->addLink(new Link('Sign-Up', '/content/1051'));
        $screeningView->addLink(new Link('Dr.Option Form', '/resources/5861/GB2015PhysicianOption.090115.pdf'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $hraScreeningContactEndDate);
        $hraView->setName('complete_hra');
        $hraView->emptyLinks()->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setReportName('Health Power Assessment (HPA)');
        $coreGroup->addComplianceView($hraView);

        $doctorView = new UpdateDoctorInformationComplianceView($programStart, $hraScreeningContactEndDate);
        $doctorView->setReportName('Have a Main / Primary Care Provider');
        $coreGroup->addComplianceView($doctorView);

        $medicalBenefits = new CompleteELearningLessonComplianceView($programStart, $hraScreeningContactEndDate, new ELearningLesson_v2(1424));
        $medicalBenefits->setName('medical_benefits');
        $medicalBenefits->setReportName('Complete District #225 Medical Benefits e-Learning Lesson (now available)');
        $lessonLink = current($medicalBenefits->getLinks());
        $lessonLink->setLinkText('Glenbrook 2015 Benefits e-Learning Lesson');
        $coreGroup->addComplianceView($medicalBenefits);

        $learningView = new CompleteELearningGroupSet($programStart, $hraScreeningContactEndDate, 'required_2015');
        $learningView->setReportName('Complete 2 additional e-Learning Lessons from Qualifying List');
        $learningView->setName('additional_elearning');
        $learningView->setNumberRequired(2);
        $coreGroup->addComplianceView($learningView);

        $this->addComplianceViewGroup($coreGroup);

        $keySection = 'Key Health Measures - earn up to 130 points';
        $pcSection = 'Preventive Care - earn up to 215 points';
        $learnSection = '"Learning - earn up to 435 points';
        $exerciseSection = 'Exercise / Fitness - earn up to 900 points';
        $socialMediaSection = 'Social Media - earn up to 10 points';

        $raffleGroup = new ComplianceViewGroup('points', 'Winter and Spring Raffles – earn points that translate to 1 raffle ticket for every 100 points earned by drawing deadlines.');

        $screeningView = new CompleteScreeningComplianceView($programStart, $hraScreeningContactEndDate);
        $screeningView->setReportName('Annual Wellness Screening');
        $screeningView->setName('complete_screening_points');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/content/989'));
        $raffleGroup->addComplianceView($screeningView, false, $keySection);

        $hraView = new CompleteHRAComplianceView($programStart, $hraScreeningContactEndDate);
        $hraView->setName('complete_hra_points');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $hraView->emptyLinks()->addLink(new Link('Take HPA', '/content/989'));
        $hraView->setReportName('Health Power Assessment (HPA)');
        $raffleGroup->addComplianceView($hraView, false, $keySection);

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($screeningStartDate, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);

        $raffleGroup->addComplianceView($totalCholesterolView, false, $keySection);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($screeningStartDate, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($hdlCholesterolView, false, $keySection);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($screeningStartDate, $programEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->overrideTestRowData(0, 0, 129, 158.999);
        $raffleGroup->addComplianceView($ldlCholesterolView, false, $keySection);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($screeningStartDate, $programEnd);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->overrideTestRowData(0, 30, 149, 199.999);
        $raffleGroup->addComplianceView($trigView, false, $keySection);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($screeningStartDate, $programEnd);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(40, 70, 99.9, 124.999);
        $raffleGroup->addComplianceView($glucoseView, false, $keySection);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStartDate, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($bloodPressureView, false, $keySection);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($screeningStartDate, $programEnd);
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $raffleGroup->addComplianceView($bodyFatBMIView, false, $keySection);

        $nonSmokerView = new ComplyWithCotinineScreeningTestComplianceView($screeningStartDate, $programEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker / Low Exposure to Nicotine/Cotinine');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $raffleGroup->addComplianceView($nonSmokerView, false, $keySection);


        $workWithHealthCoachView = new PlaceHolderComplianceView(null, 0);
        $workWithHealthCoachView->setName('work_with_health_coach');
        $workWithHealthCoachView->setReportName('Work with health coach or doctor to improve yellow or red screening results');
        $workWithHealthCoachView->setMaximumNumberOfPoints(40);
        $workWithHealthCoachView->addLink(new Link('Coach Info', '/content/8733'));
        $workWithHealthCoachView->addLink(new Link('Get Dr. Form', '/resources/5864/GBD225FormDrOptionforScreeningFollow-Up.091015.pdf'));
        $raffleGroup->addComplianceView($workWithHealthCoachView, false, $pcSection);

        $fluVaccineView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $fluVaccineView->setReportName('Obtain flu shot during onsite wellness screening date ');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $fluVaccineView->setName('flu_shot');
        $fluVaccineView->addLink(new Link('More Info', '/content/1094#2dflu'));
        $raffleGroup->addComplianceView($fluVaccineView, false, $pcSection);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Have a Main / Primary Care Provider');
        $doctorInformationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $doctorInformationView->setName('have_doctor');
        $raffleGroup->addComplianceView($doctorInformationView, false, $pcSection);

        $vacVaccineView = new CompleteArbitraryActivityComplianceView(strtotime('-10 years', strtotime(self::ROLLING_START_DATE_ACTIVITY_DATE)), $programEnd, 254, 5);
        $vacVaccineView->setMaximumNumberOfPoints(5);
        $vacVaccineView->setReportName('Have up-to-date Tetanus or Tdap immunization ');
        $vacVaccineView->setName('flu_vaccine');
        $raffleGroup->addComplianceView($vacVaccineView, false, $pcSection);

        $preventiveExamsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($programStart, $programEnd, 20);
        $preventiveExamsView->configureActivity(259, 91, array(
            'Colonoscopy'   => 60,
            'Dental Exam'   => 24,
            'Mammogram'     => 24,
            'Pap Test'      => 36,
            'Physical Exam' => 36,
            'PSA Test'      => 60
        ));
        $preventiveExamsView->setReportName('Have up-to-date recommended preventive exams ');
        $preventiveExamsView->setMaximumNumberOfPoints(100);
        $preventiveExamsView->setName('do_preventive_exams');
        $raffleGroup->addComplianceView($preventiveExamsView, false, $pcSection);

        $lnlPrograms = new AttendEventComplianceView($programStart, $programEnd);
        $lnlPrograms->bindTypeIds(array(9));
        $lnlPrograms->setPointsPerAttendance(75);
        $lnlPrograms->setReportName('Attend SYL Lunch & Learn programs');
        $lnlPrograms->setMaximumNumberOfPoints(300);
        $lnlPrograms->addLink(new Link('View Events/Sign-Up', '/content/4820?action=Listing&actions[Listing]=eventList&actions[Calendar]=eventBulletin&actions[My+Registrations+%26+Waitlists]=viewScheduledEvents'));
        $raffleGroup->addComplianceView($lnlPrograms, false, $learnSection);

        $elearnView = new CompleteELearningGroupSet($programStart, $programEnd, 'extra');
        $elearnView->setReportName('Complete recommended e-Learning Lessons  (max of 6)');
        $elearnView->setName('elearning');
        $elearnView->setMaximumNumberOfPoints(60);
        $elearnView->setPointsPerLesson(10);
        $elearnView->addLink(new Link('Review/Do Lessons', '/content/eLearning_middle_page'));
        $raffleGroup->addComplianceView($elearnView, false, $learnSection);

        $offsiteWellnessView = new PlaceHolderComplianceView(null, 0);
        $offsiteWellnessView->setAllowPointsOverride(true);
        $offsiteWellnessView->setReportName('Participate in offsite health/wellness programs');
        $offsiteWellnessView->addLink(new Link('Enter/Update Info', '/content/documentation_participation'));
        $offsiteWellnessView->setMaximumNumberOfPoints(75);
        $raffleGroup->addComplianceView($offsiteWellnessView, false, $learnSection);

        $offsiteFitView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 258, 100);
        $offsiteFitView->setReportName('Participation in offsite group fitness class (max 50 pts/semester)	');
        $offsiteFitView->setMaximumNumberOfPoints(100);
        $offsiteFitViewWrapper = new Glenbrook2016SemesterWrapperView($offsiteFitView);
        $offsiteFitViewWrapper->setSemesterPoints(50);
        $raffleGroup->addComplianceView($offsiteFitViewWrapper, false, $exerciseSection);

        $gymMembershipView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 256, 100);
        $gymMembershipView->setReportName('Gym membership and/or work with personal trainer (max 100 points each/semester)');
        $gymMembershipViewWrapper = new Glenbrook2016SemesterWrapperView($gymMembershipView);
        $gymMembershipViewWrapper->setSemesterPoints(100);
        $raffleGroup->addComplianceView($gymMembershipViewWrapper, false, $exerciseSection);

        $exerciseView = new PhysicalActivityComplianceView('2015-08-31', $programEnd);
        $exerciseView->setReportName('Other regular exercise (1 pt/hour;  max 200/year)');
        $exerciseView->_setID(260);
        $exerciseView->setMaximumNumberOfPoints(200);
        $exerciseView->setMinutesDivisorForPoints(60);
        $exerciseView->setPointsMultiplier(1);
        $exerciseView->setFractionalDivisorForPoints(1);

        $exerciseViewWrapper = new Glenbrook2016SemesterWrapperView($exerciseView, '2015-08-31');
        $exerciseViewWrapper->setSemesterPoints(100);
        $raffleGroup->addComplianceView($exerciseViewWrapper, false, $exerciseSection);

        $partEventsView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 257, 25);
        $partEventsView->setReportName('Participate in 5k or 10k Walk/Run Events between 05/02/15 & 05/02/2016 (25 points each)');
        $partEventsView->emptyLinks();
        $partEventsView->addLink(new Link('Report Event', '/content/documentation_participation'));
        $partEventsView->setMaximumNumberOfPoints(200);
        $raffleGroup->addComplianceView($partEventsView, false, $exerciseSection);

        $partMarathonsView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 378, 50);
        $partMarathonsView->setReportName('Participate in half or full marathons, triathlons, or iron man events between 05/02/15 & 05/02/2016 (50 points each)');
        $partMarathonsView->setMaximumNumberOfPoints(200);
        $partMarathonsView->emptyLinks();
        $partMarathonsView->addLink(new Link('Report Event', '/content/documentation_participation'));
        $raffleGroup->addComplianceView($partMarathonsView, false, $exerciseSection);

        $twitterView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 379, 5);
        $twitterView->setReportName('Shape Your Life Twitter Account');
        $twitterView->addLink(new Link('Twitter Account', 'https://twitter.com/ShapeYourLifeGB'));
        $twitterView->setMaximumNumberOfPoints(5);
        $raffleGroup->addComplianceView($twitterView, false, $socialMediaSection);

        $facebookView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 380, 5);
        $facebookView->setReportName('Liking the Shape Your Life Facebook Account');
        $facebookView->addLink(new Link('Facebook Account', 'https://www.facebook.com/pages/Shape-Your-Life/1465925567027190?ref=hl'));
        $facebookView->setMaximumNumberOfPoints(5);
        $raffleGroup->addComplianceView($facebookView, false, $socialMediaSection);

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

        $printer->addCallbackField('Building', function(User $user) {
            return $user->getLocation();
        });

        $printer->addCallbackField('Rebate', function(User $user) {
            return $user->miscellaneous_data_1 ? $user->miscellaneous_data_1 : $user->miscellaneous_data_2;
        });

        $printer->addStatusFieldCallback('Shape Your Life Registration', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $start_date = sfConfig::get('app_legacy_mileage_monsters_start_date');
            $end_date = sfConfig::get('app_legacy_mileage_monsters_end_date');

            $user_registration = MileageRegistrants::getRegistrationForUser($user, $start_date, $end_date);

            if($user_registration !== false) {
                return 'Yes';
            } else {
                return 'No';
            }
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Glenbrook20152016ScreeningPrinter();
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
            $printer = new Glenbrook20152016ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2015-08-19';
}

class Glenbrook20152016ScreeningPrinter extends ScreeningProgramReportPrinter
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
                    <strong>HDL cholesterol</strong><br />
                    • Men<br>
                    • Women
                </li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72">
            <br />
            ≥40<br />
            ≥50

        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            <br />
            25-39<br />
            49-25
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            <br />
            &lt;25<br />
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

class Glenbrook20152016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function __construct()
    {
        $this->screeningLinkArea = '<br/><br/>
      Green Range = 10 pts <br/>
      Yellow Range = 5 pts<br/>
      Red Range = 0 pts *<br/>
    ';
    }

    protected function showGroup($group)
    {
        return $group->getName() != 'evaluators';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(false);
//        $this->setScreeningResultsLink(new FakeLink('Have These Screening Results in the Ideal Zone:', '/content/1094#2aKBHM'));
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        ?>
    <script type="text/javascript">
        $(function(){
            $('td a[href="?preferredPrinter=ScreeningProgramReportPrinter&id=373"]').before('<em>Points appear 5-10 days after screening.</em> <br /><br />');

            $('.view-additional_elearning').after('<tr style="font-size:9pt; height:30px; text-align:center;"><td>$250 Rebate Reward Status</td><td></td><td><img src="<?php echo $coreGroupStatus->getLight(); ?>" class="light" alt=""/></td><td style="font-size:8pt;">Deadline - 12/04/2015</td></tr><tr style="height:36px;"><td colspan=4></td></tr>')

        });
    </script>
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

    <div id="altPageHeading">Glenbrook School District 225’s 2015-2016 Shape Your Life Program</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>


    <p>Welcome to your summary page for Glenbrook School District 225’s 2015-2016 Shape Your Life Program.
        This program is designed to promote health awareness, encourage healthy habits, and bring the
        district together by fostering a culture that cares for each individual’s well-being. Eligible
        employees who participate in the program can earn the following rewards:</p>

    <ol>
        <li><strong>$250 Medical Premium Rebate</strong> – By verifying your contact information, completing the
            Health Power Assessment (HPA), participating in the Annual Wellness Screening (or qualified screening
            by your own doctor), verifying you have a Main / Primacy Care Provider, and Completing 3 required
            e-Learning Lessons you will received $250 rebate on your medical benefit premiums. All activities
            must be completed by 12/04/2015. The $250 rebate will be distributed from January 2016 through June 2016.
        </li><br />

        <li><strong>Winter Raffle with prizes including Fitbits and iPad Minis</strong> – Choose from activities in 2A-R below
                to earn points for key actions you are taking for your health and wellbeing PLUS some bonus points
                for certain screening results. Get 1 raffle ticket for every 100 points earned. Deadline to enter
                points for Winter Raffle is 12/14/2015.
        </li><br />

        <li><strong>Spring Raffle with prizes including Fitbits and iPad Minis</strong> – Choose from activities in 2A-R below
                to earn points for key actions you are taking for your health and wellbeing PLUS some bonus points
                for certain screening results. Get 1 raffle ticket for every 100 points. Deadline to enter points
                for Spring Raffle is 05/02/2016.
        </li><br />

        <li><strong>2015 Prize</strong> – By earning a total 400 points you can receive the 2016 Shape Your Life Prize.
            Choose any activities that you would like to participate in from 2A-R to earn the 400 points required for
            the 2015 Prize. Deadline to enter points for 2016 Prize is 05/02/2016.
        </li>
    </ol>

    <p><a href="/content/1094">Click here </a>for additional details about program activities and their benefits.</p>


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
                1,690 points possible! <br/>
            </td>
        </tr>
        <tr class="headerRow">
            <th><strong>3</strong>. Rewards &amp; Eligibility Requirements</th>
            <td># Earned</td>
            <td colspan="2"># Possible</td>
        </tr>
        <tr>
            <td style="text-align:right;">
                2015 Winter Raffle: # Tickets by 12/14/2015
            </td>
            <td style="text-align:center;">
              <?php echo $raffleTickets['2015_winter_i_gift_card_raffle_tickets'] ?>
            </td>
            <td style="text-align:center;" colspan="2">
                About 13 tickets possible per person
            </td>
        </tr>
        <tr>
            <td style="text-align:right;">
                2016 Spring Raffle: # Tickets by 05/02/2016
            </td>
            <td style="text-align:center;">
                <?php echo $raffleTickets['2016_spring_raffle_tickets'] ?>
            </td>
            <td style="text-align:center;" colspan="2">
                16 tickets possible per person
            </td>
        </tr>
        <tr>
            <td style="text-align:right;">
                2016 SYL Prize: Earn 400 points by 05/02/2016
            </td>
            <td style="text-align:center;">
                <?php echo $raffleTickets['2016_wearable_award_tickets'] ?>
            </td>
            <td style="text-align:center;" colspan="2">
                1 prize possible per person
            </td>
        </tr>
        <?php
    }

    public $showUserNameInLegend = true;
}
