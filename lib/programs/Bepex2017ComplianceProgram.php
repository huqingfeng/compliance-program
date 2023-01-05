<?php
class Bepex2017AdventureChallengeView extends CompleteArbitraryActivityComplianceView
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

class Bepex2017Printer extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        $endDateText = 'Needed By 09/30/2016';

        $this->tableHeaders['total_status'] = 'Status of 1ABC + ≥ '.Bepex2017ComplianceProgram::POINTS_REQUIRED.' points as of today:';
        $this->tableHeaders['total_link'] = $endDateText

        ?>
        <script type="text/javascript">
            $(function(){
                $('.view-purpose_activity').children(':eq(0)').html("<strong>NEW! B.</strong> Purpose Activity - What's your Why?");

                $('.view-screening').children(':eq(0)').html('<strong>NEW! B.</strong> Be current with all age- and gender related exams/screenings - Deadline is June 30, 2017');

                $('.view-ted_talk').children(':eq(0)').html('<strong>NEW! F.</strong> Ted Talk - "How to Make Stress Your Friend');

                $('.view-sign_pledge').children(':eq(0)').html('<strong>NEW! H.</strong> Sign the Safe Driving Pledge');

                $('.view-activity_580').children(':eq(0)').html('<strong>New! J.</strong> Read "Do You Need a Tax Professional" in Lifeworks');

                $('.view-activity_583').children(':eq(0)').html('<strong>NEW! K.</strong> Become a Better Healthcare Consumer: Read about Navigating the Healthcare System');

                $('.view-activity_589').children(':eq(0)').html('<strong>NEW! L.</strong> Become a Better Healthcare Consumer: Compare your clinic quality to another');

                $('.view-activity_586').children(':eq(0)').html('<strong>New! P. Register for Virtuwell or Comparable Telemedicine Service</strong>');

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

        <div class="pageHeading">2017 Well-Being Rewards Program</div>


        <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

        <p>Welcome to your summary page for the B-Well 2017 Well-Being Rewards Program. To receive the Well-Being Reward,
            eligible employees MUST take action and meet the following requirements by the deadlines noted:</p>
        <ol>
            <li>Complete ALL of the core required actions by the noted due dates; AND</li>
            <li>Between January 1, 2017 and September 30, 2017, earn 250 or more points from key actions taken for well-being
            </li>
        </ol>
        <p>
            Employees who complete the required core actions (some by February 28th, 2017), and earn 250 or more points
            from various well-being actions by September 30, 2017 will earn a reward:
        </p>
        <ol type="A">


            <ul>
                <li>Either Well-being time off (8, 12 or 16 well-being time off hours - based on total points earned); OR
                </li>
                <li>A health plan premium contribution discount ($20, $30 or $40/month - based on total points earned)</li>
            </ul>

        </ol>
        <p><a href="/content/1094new2015">Click here to learn more about the 2017 rewards program, the related actions and other details.</a></p>

        <p>Updates & Timing: To get actions done and earn points, click on the links below. If the points or status did not
            change for an item you are working on, you may need to go back and enter missing information or entries to earn
            more points. Thanks for your actions and patience!</p>

        <?php
    }
}

class Bepex2017ComplianceProgram extends ComplianceProgram
{
    const POINTS_REQUIRED = 250;

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Bepex2017Printer();
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

        $hra = new CompleteHRAComplianceView($startDate, '2017-03-17');
        $hra->setReportName('Complete the Annual Health Power Assessment (HPA) - Deadline is March 17, 2017');
        $core->addComplianceView($hra);

        $scr = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $scr->setName('screening');
        $scr->setReportName('Be current with all age- and gender related exams/screenings - Deadline is June 30, 2017');
        $scr->emptyLinks();
        $scr->addLink(new Link('Download Form', ' /resources/8746/Bepex Provider Certification Form 2017.pdf '));
        $core->addComplianceView($scr);

        $survey = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $survey->setReportName('Complete the B-Well Culture Survey - Deadline is July/August 2017');
        $survey->setName('survey');
        $survey->addLink(new FakeLink('Available July/August 2017', '#'));

        $core->addComplianceView($survey);

        $this->addComplianceViewGroup($core);

        $points = new ComplianceViewGroup('points', 'And, earn at least '.self::POINTS_REQUIRED.' points from the different areas of well-being by September 30, 2017 (unless alternative deadline noted)');
        $points->setPointsRequiredForCompliance(self::POINTS_REQUIRED);

        $sem = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 92, 15);
        $sem->setReportName('Attend Onsite B-Well Seminars (15 points per seminar)');
        $sem->setMaximumNumberOfPoints(45);
        $points->addComplianceView($sem, false, $wellBeingSection);

        $purposeActivity = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 568, 20);
        $purposeActivity->setName('purpose_activity');
        $purposeActivity->setReportName("Purpose Activity - What's your Why?");
        $purposeActivity->setMaximumNumberOfPoints(20);
        $points->addComplianceView($purposeActivity, false, $wellBeingSection);


        $bWellbeingActivities = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 571, 25);
        $bWellbeingActivities->setReportName('Participate in B-Well-sponsored Challenges/Campaigns (25 points per challenge)');
        $bWellbeingActivities->setName('b_wellbeing');
        $bWellbeingActivities->setMaximumNumberOfPoints(75);
        $bWellbeingActivities->addLink(new Link('Information', '/resources/8749/Participant Materials_2016.pdf'));
        $points->addComplianceView($bWellbeingActivities, false, $wellBeingSection);

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

        $tedTalkActivities = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 574, 10);
        $tedTalkActivities->setReportName('Ted Talk - "How to Make Stress Your Friend"');
        $tedTalkActivities->setName('ted_talk');
        $tedTalkActivities->setMaximumNumberOfPoints(10);
        $tedTalkActivities->addLink(new Link('Watch Ted Talk', 'http://www.ted.com/talks/kelly_mcgonigal_how_to_make_stress_your_friend'));
        $points->addComplianceView($tedTalkActivities, false, $careerSection);

        $vol = new VolunteeringComplianceView($startDate, $endDate);
        $vol->setReportName('Volunteer Your Time for Charity and Professional Organizations – Utilize Monthly VTO Policy (5 points per hour spent)');
        $vol->setMaximumNumberOfPoints(50);
        $vol->setMinutesDivisorForPoints(60);
        $vol->setPointsMultiplier(5);
        $points->addComplianceView($vol, false, $communitySection);

        $signPledge = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 577, 15);
        $signPledge->setReportName('Sign the Safe Driving Pledge');
        $signPledge->setName('sign_pledge');
        $signPledge->setMaximumNumberOfPoints(15);
        $signPledge->addLink(new Link('Download Pledge', '/resources/8758/Take Back Your Drive Pledge - National Safety Council.pdf'));
        $points->addComplianceView($signPledge, false, $communitySection);


        $attendRetireSecure = new RegexBasedActivityComplianceView($startDate, $endDate, 322, 117);
        $attendRetireSecure->setReportName('Attend a 1:1 “Retire Secure” Meeting with a Principal Financial Advisor (Summer 2017)');
        $attendRetireSecure->setMaximumNumberOfPoints(25);
        $points->addComplianceView($attendRetireSecure, false, $financialSection);

        $taxInfo = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 580, 15);
        $taxInfo->setReportName('Read "Do You Need a Tax Professional" in Lifeworks');
        $taxInfo->setMaximumNumberOfPoints(15);
        $taxInfo->addLink(new Link('Read Here', 'https://portal.lifeworks.com/portal/viewers/HPSArticle.aspx?HPSMaterialID=13440'));
        $points->addComplianceView($taxInfo, false, $financialSection);

        $healthcareConsumer = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 583, 10);
        $healthcareConsumer->setReportName('Become a Better Healthcare Consumer: Read about Navigating the Healthcare System');
        $healthcareConsumer->setMaximumNumberOfPoints(10);
        $healthcareConsumer->addLink(new Link("Read Here", "http://www.takingcharge.csh.umn.edu/navigate-healthcare-system"));
        $points->addComplianceView($healthcareConsumer, false, $financialSection);

        $healthcareConsumer = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 589, 20);
        $healthcareConsumer->setReportName('Become a Better Healthcare Consumer: Compare your clinic quality to another');
        $healthcareConsumer->setMaximumNumberOfPoints(20);
        $healthcareConsumer->addLink(new Link("Read Here", "http://MNHealthScores.Org"));
        $points->addComplianceView($healthcareConsumer, false, $financialSection);

        $checkCreditReport = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 449, 20);
        $checkCreditReport->setReportName('Check Your Credit Report');
        $checkCreditReport->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $points->addComplianceView($checkCreditReport, false, $financialSection);

        $bwellEvent = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 94, 5);
        $bwellEvent->setReportName('Attend B-Well Events');
        $bwellEvent->setMaximumNumberOfPoints(40);
        $points->addComplianceView($bwellEvent, false, $socialSection);

        $qualityTime = new EngageLovedOneComplianceView($startDate, $endDate, 1);
        $qualityTime->setMaximumNumberOfPoints(40);
        $qualityTime->setReportName('Spend Quality Time with Loved Ones');
        $points->addComplianceView($qualityTime, false, $socialSection);

        $registerVirtuwell = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 586, 10);
        $registerVirtuwell->setReportName('Register for Virtuwell or Comparable Telemedicine Service');
        $registerVirtuwell->setMaximumNumberOfPoints(10);
        $registerVirtuwell->addLink(new Link('Virtuwell', 'https://www.virtuwell.com/'));
        $points->addComplianceView($registerVirtuwell, false, $physicalSection);


        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
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
        $fluVaccineView->setReportName('Have an Annual Flu Vaccine in 2016-17 (Since summer of 2016)');
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
        $mindfulActivityView->setMinutesDivisorForPoints(30);
        $mindfulActivityView->setPointsMultiplier(2);
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