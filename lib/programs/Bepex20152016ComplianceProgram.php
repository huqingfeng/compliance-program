<?php
class Bepex2015AdventureChallengeView extends CompleteArbitraryActivityComplianceView
{
    public function __construct($startDate, $endDate, $activityId, $pointsPerRecord)
    {
        $this->activityId = $activityId;
        $this->pointsPerRecord = $pointsPerRecord;

        parent::__construct($startDate, $endDate, $activityId, $pointsPerRecord);
    }

    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        $challengeTypes = array();
        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[176])) $challengeTypes[$answers[176]->getAnswer()] = $answers[176]->getAnswer();
        }

        $points = 0;
        foreach($challengeTypes as $challengeType) {
            $points += $this->pointsPerRecord;
        }

        return new ComplianceViewStatus($this, null, $points);
    }

    private $activityId;
    private $pointsPerRecord;
}

class Bepex20152016Printer extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        $endDateText = 'Needed By 09/30/2016';

        $this->tableHeaders['total_status'] = 'Status of 1ABCD + ≥ '.Bepex20152016ComplianceProgram::POINTS_REQUIRED.' points as of today:';
        $this->tableHeaders['total_link'] = $endDateText

        ?>
        <script type="text/javascript">
            $(function(){
                $('.phipTable .physical-emotional-well-being').next().children(':eq(0)').html('N. Have Key Biometric Measures in a Healthy Zone: ');
            });
        </script>
        <style type="text/css">
            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .headerRow {
                background-color:#385D81;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            #legendText {
                text-align:center;
                background-color:#385D81;
                font-weight:normal;
                color:#FFFFFF;
                font-size:12pt;
                margin-bottom:5px;
            }

            .phipTable .all-5-areas-of-well-being {
                background-color:#000;
                color:#FFF;
            }

            .phipTable .community-well-being {
                background-color:#5500B0;
            }

            .phipTable .career-well-being {
                background-color:#0043B0;
            }

            .phipTable .financial-well-being {
                background-color:#26B000;
            }

            .phipTable .social-well-being {
                background-color:#FF6600;
            }

            .phipTable .physical-emotional-well-being {
                background-color:#B00000;
            }
        </style>

        <div class="pageHeading">2015-2016 Well-Being Rewards Program</div>


        <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

        <p>Welcome to your summary page for the B-Well 2015-2016 Well-Being Rewards Program. To receive the Well-Being Reward,
            eligible employees MUST take action and meet the following requirements by the deadlines noted:</p>
        <ol>
            <li>Complete ALL of the core required actions by the noted due dates; AND</li>
            <li>Between October 1, 2015 and September 30, 2016, earn 250 or more points from key actions taken
                for well-being
            </li>
        </ol>
        <p>
            Employees who complete the required core actions (some by October 28th, 2015), and earn 250 or
            more points from various well-being actions by September 30, 2016 will earn a reward:
        </p>
        <ol type="A">


            <ul>
                <li>Either Well-being time off (8, 12 or 16 well-being time off hours - based on total points earned); OR
                </li>
                <li>A health plan premium contribution discount ($20, $30 or $40/month - based on total points earned)</li>
            </ul>

        </ol>
        <p><a href="/content/1094new2015">Click here to learn more about the 2015-2016 rewards program, the related actions
                and other details.</a></p>

        <p>Updates & Timing: To get actions done and earn points, click on the links below. If the points or status did not
            change for an item you are working on, you may need to go back and enter missing information or entries to earn
            more points. Thanks for your actions and patience!</p>

        <?php
    }
}

class Bepex20152016ComplianceProgram extends ComplianceProgram
{
    const POINTS_REQUIRED = 250;

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Bepex20152016Printer();
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $wellBeingSection = 'ALL 5 AREAS OF WELL-BEING';
        $communitySection = 'COMMUNITY WELL-BEING';
        $careerSection = 'CAREER WELL-BEING';
        $financialSection = 'FINANCIAL WELL-BEING';
        $socialSection = 'SOCIAL WELL-BEING';
        $physicalSection = 'PHYSICAL/EMOTIONAL WELL-BEING';

        $core = new ComplianceViewGroup('core', 'All Core Actions Required by specified due date');

        $hra = new CompleteHRAComplianceView($startDate, '2015-10-28');
        $hra->setReportName('Complete the Annual Health Power Assessment (HPA) - Deadline is October 28th, 2015');
        $core->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($startDate, '2015-11-19');
        $scr->setReportName('Complete the Onsite Health Screening - Deadline is November 19th, 2015');
        $scr->emptyLinks();
        $scr->addLink(new Link('Schedule', '/content/1051'));
        $scr->addLink(new Link('Results', '/content/989'));
        $core->addComplianceView($scr);

        $survey = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $survey->setReportName('Complete the B-Well Survey');
        $survey->setName('survey');
        $survey->addLink(new FakeLink('Available July 2016', '#'));

        $core->addComplianceView($survey);

        $this->addComplianceViewGroup($core);

        $points = new ComplianceViewGroup('points', 'And, earn at least '.self::POINTS_REQUIRED.' points from the different areas of well-being by September 30, 2016 (unless alternative deadline noted)');
        $points->setPointsRequiredForCompliance(self::POINTS_REQUIRED);

        $sem = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 92, 15);
        $sem->setReportName('Attend Onsite B-Well Seminars (15 points per seminar)');
        $sem->setMaximumNumberOfPoints(45);
        $points->addComplianceView($sem, false, $wellBeingSection);

        $teamPlan = new PlaceHolderComplianceView(null, 0);
        $teamPlan->setAllowPointsOverride(true);
        $teamPlan->setName('team_plan');
        $teamPlan->setReportName('Choose Your Own Adventure Challenge – create your team plan (due: January 31, 2016)');
        $teamPlan->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $teamPlan->addLink(new FakeLink('Admin will enter', '#'));
        $teamPlan->addLink(new Link('Information', '/resources/6320/Choose Your Own Well-being Adventure_Bepex Edits_FINAL.pdf'));
        $points->addComplianceView($teamPlan, false, $wellBeingSection);

        $wellbeingAdventure = new Bepex2015AdventureChallengeView($startDate, $endDate, 479, 25);
        $wellbeingAdventure->setName('well_being_adventure');
        $wellbeingAdventure->setReportName('Choose Your Own Adventure Challenge – each well-being adventure (25 points each area of well-being adventure)');
        $wellbeingAdventure->setMaximumNumberOfPoints(125);
        $points->addComplianceView($wellbeingAdventure, false, $wellBeingSection);

        $ownWellbeingActivities = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 500, 5);
        $ownWellbeingActivities->setReportName('Participate in a Choose your Adventure Activity hosted by another team (5 points for each well-being adventure)');
        $ownWellbeingActivities->setName('own_wellbeing');
        $ownWellbeingActivities->setMaximumNumberOfPoints(50);
        $points->addComplianceView($ownWellbeingActivities, false, $wellBeingSection);

        $dev = new MinutesBasedActivityComplianceView($startDate, $endDate, 68);
        $dev->setMaximumNumberOfPoints(50);
        $dev->setMinutesDivisorForPoints(60);
        $dev->setPointsMultiplier(5);
        $dev->setReportName('Engage in Professional Development Activities (5 points per hour)');
        $points->addComplianceView($dev, false, $careerSection);

        $virtualTrainingWebsite = new MinutesBasedActivityComplianceView($startDate, $endDate, 391);
        $virtualTrainingWebsite->setMaximumNumberOfPoints(80);
        $virtualTrainingWebsite->setMinutesDivisorForPoints(60);
        $virtualTrainingWebsite->setPointsMultiplier(5);
        $virtualTrainingWebsite->setReportName('New Virtual Training Website Activities (5 points per hour spent)');
        $points->addComplianceView($virtualTrainingWebsite, false, $careerSection);

        $vol = new VolunteeringComplianceView($startDate, $endDate);
        $vol->setReportName('Volunteer Your Time for Charity and Professional Organizations – Utilize Monthly Time Police (5 points per hour spent)');
        $vol->setMaximumNumberOfPoints(50);
        $vol->setMinutesDivisorForPoints(60);
        $vol->setPointsMultiplier(5);
        $points->addComplianceView($vol, false, $communitySection);


        $attendRetireSecure = new RegexBasedActivityComplianceView($startDate, $endDate, 322, 117);
        $attendRetireSecure->setReportName('Attend a 1:1 “Retire Secure” Meeting with a Principal Financial Advisor (Summer 2016)');
        $attendRetireSecure->setMaximumNumberOfPoints(25);
        $points->addComplianceView($attendRetireSecure, false, $financialSection);

        $readTelephoneTextingFraud = new RegexBasedActivityComplianceView($startDate, $endDate, 443, 164);
        $readTelephoneTextingFraud->setReportName('Read “Protecting Against Telephone and Texting Fraud” in Lifeworks');
        $readTelephoneTextingFraud->setMaximumNumberOfPoints(10);
        $points->addComplianceView($readTelephoneTextingFraud, false, $financialSection);


        $checkCreditReport = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 449, 10);
        $checkCreditReport->setReportName('Check Your Credit Report');
        $checkCreditReport->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $points->addComplianceView($checkCreditReport, false, $financialSection);

        $bwellEvent = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 94, 5);
        $bwellEvent->setReportName('Attend B-Well Events');
        $bwellEvent->setMaximumNumberOfPoints(40);
        $points->addComplianceView($bwellEvent, false, $socialSection);

        $qualityTime = new EngageLovedOneComplianceView($startDate, $endDate, 1);
        $qualityTime->setMaximumNumberOfPoints(40);
        $qualityTime->setReportName('Spend Quality Time with Loved Ones');
        $points->addComplianceView($qualityTime, false, $socialSection);

        $participateWithCoWorkers = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 394, 5);
        $participateWithCoWorkers->setMaximumNumberOfPoints(30);
        $participateWithCoWorkers->setReportName('Participate in a Group With Co-Workers Over a Common Interest i.e. walking group, a lunch bunch, book club, knitting circle, etc. (5 points per group meeting)');
        $points->addComplianceView($participateWithCoWorkers, false, $socialSection);


        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $endDate);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $points->addComplianceView($totalCholesterolView, false, $physicalSection);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($startDate, $endDate);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($hdlCholesterolView, false, $physicalSection);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($ldlCholesterolView, false, $physicalSection);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($trigView, false, $physicalSection);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($glucoseView, false, $physicalSection);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($bloodPressureView, false, $physicalSection);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $endDate);
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $points->addComplianceView($bodyFatBMIView, false, $physicalSection);

        $nonSmokerView = new ComplyWithSmokingHRAQuestionComplianceView($startDate, $endDate);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker/Low Exposure to Nicotine/Cotinine');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($nonSmokerView, false, $physicalSection);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $points->addComplianceView($doctorView, false, $physicalSection);

        $eyeExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 395, 20);
        $eyeExamView->setReportName('Have an Eye Exam');
        $eyeExamView->setMaximumNumberOfPoints(20);
        $points->addComplianceView($eyeExamView, false, $physicalSection);

        $dentalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 396, 20);
        $dentalExamView->setReportName('Have a Dental Exam');
        $dentalExamView->setMaximumNumberOfPoints(20);
        $points->addComplianceView($dentalExamView, false, $physicalSection);

        $fluVaccineView = new FluVaccineActivityComplianceView($startDate, $endDate);
        $fluVaccineView->setReportName('Have an Annual Flu Vaccine in 2015-2016');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $points->addComplianceView($fluVaccineView, false, $physicalSection);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Get Regular Physical Activity (5 points per hour)');
        $physicalActivityView->setMaximumNumberOfPoints(80);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setPointsMultiplier(5);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->_setID(263);
        $points->addComplianceView($physicalActivityView, false, $physicalSection);

        $mindfulActivityView = new MinutesBasedActivityComplianceView($startDate, $endDate, 70);
        $mindfulActivityView->setMinutesDivisorForPoints(60);
        $mindfulActivityView->setPointsMultiplier(5);
        $mindfulActivityView->setMaximumNumberOfPoints(80);
        $mindfulActivityView->setName('mindful_activity');
        $mindfulActivityView->setReportName('Complete Relaxation/Resiliency-Building Activities');
        $points->addComplianceView($mindfulActivityView, false, $physicalSection);


        $seatedMeeting = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 397, 5);
        $seatedMeeting->setMaximumNumberOfPoints(5);
        $seatedMeeting->setReportName('Shift a Regularly Scheduled Seated Meeting to a Walking or Standing Meeting Instead');
        $points->addComplianceView($seatedMeeting, false, $physicalSection);

        $tryEquipment = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 398, 10);
        $tryEquipment->setMaximumNumberOfPoints(10);
        $tryEquipment->setReportName('Try a Piece of Equipment in the On-Site Fitness Center During the Workday (10-15 minutes)');
        $points->addComplianceView($tryEquipment, false, $physicalSection);


        $this->addComplianceViewGroup($points);
    }
}