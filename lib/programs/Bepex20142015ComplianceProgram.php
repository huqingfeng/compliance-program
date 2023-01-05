<?php

class Bepex20142015Printer extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        $endDateText = 'Needed By 09/30/2015';

        $this->tableHeaders['total_status'] = 'Status of 1ABCD + ≥ '.Bepex20142015ComplianceProgram::POINTS_REQUIRED.' points as of today:';
        $this->tableHeaders['total_link'] = $endDateText

        ?>
        <script type="text/javascript">
            $(function(){
                $('.phipTable .headerRow-core').children(':eq(1)').html('Due Date');
                $('.phipTable .view-complete_hra').children(':eq(1)').html('06/30/2015');
                $('.phipTable .view-complete_screening').children(':eq(1)').html('06/30/2015');
                $('.phipTable .view-survey').children(':eq(1)').html('July 2015');
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

    <div class="pageHeading">2014-2015 Well-Being Rewards Program</div>


    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the B-Well 2014-2015 Well-Being Rewards Program. To receive the Well-Being Reward, 
        eligible employees MUST take action and meet the following requirements by the deadlines noted:</p>
    <ol>
        <li>Complete ALL of the core required actions by the noted due dates; AND</li>
        <li>Between October 1, 2014 and September 30, 2015, earn 250 or more points from key actions taken for 
            well-being
        </li>
    </ol>
    <p>
        Employees who complete the required core actions (some by November 30, 2014), and earn 250 or more points from
        various well-being actions by September 30, 2015 will earn a reward:


    </p>
    <ol type="A">


        <ul>
            <li>Either Well-being time off (8, 12 or 16 well-being time off hours - based on total points earned); OR
            </li>
            <li>A health plan premium contribution discount ($20, $30 or $40/month - based on total points earned)</li>
        </ul>

    </ol>
    <p><a href="/content/1094">Click here to learn more about the 2014-2015 rewards program, the related actions and
        other details.</a></p>

    <p>Updates & Timing: To get actions done and earn points, click on the links below. If the points or status did not
        change for an item you are working on, you may need to go back and enter missing information or entries to earn
        more points. Thanks for your actions and patience!</p>

    <?php
    }
}

class Bepex20142015ComplianceProgram extends ComplianceProgram
{
    const POINTS_REQUIRED = 250;

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Bepex20142015Printer();
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $wellBeingSection = 'All 5 Areas of Well-Being';
        $communitySection = 'Community Well-Being';
        $careerSection = 'Career Well-Being';
        $financialSection = 'Financial Well-Being';
        $socialSection = 'Social Well-Being';
        $physicalSection = 'Physical/Emotional Well-Being';

        $core = new ComplianceViewGroup('core', 'All Core Actions Required by specified due date');

        $hra = new CompleteHRAComplianceView($startDate, '2015-06-30');
        $hra->setReportName('Complete the Annual Health Power Assessment (HPA)');
        $hra->setAttribute('report_name_link', '/content/1094#1HPA');
        $core->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($startDate, $endDate);
        $scr->setReportName('Complete an Age/Gender Appropriate Exam with a Physician');
        $scr->setAttribute('report_name_link', '/content/1094#2Screen');
        $scr->emptyLinks();
        $scr->addLink(new Link('Download Form', '/resources/5115/Bepex Provider Certification Form 2014-15.pdf'));
        $core->addComplianceView($scr);

        $survey = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $survey->setReportName('Complete the Bepex Culture survey');
        $survey->setAttribute('report_name_link', '/content/1094#3Survey');
        $survey->setName('survey');
        //$survey->addLink(new Link('Available in July 2012', '#'));
        $survey->addLink(new FakeLink('Available July 2015', '#'));

        $core->addComplianceView($survey);

        $this->addComplianceViewGroup($core);

        $points = new ComplianceViewGroup('points', 'And, earn at least '.self::POINTS_REQUIRED.' points from the different areas of well-being by '.$this->getEndDate('F j, Y'));
        $points->setPointsRequiredForCompliance(self::POINTS_REQUIRED);

        
        $EAPWebsite = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 390, 5);
        $EAPWebsite->setMaximumNumberOfPoints(50);
        $EAPWebsite->setReportName('Visit new Lifeworks EAP website and spend time getting familiar with each section around different areas of well-being');
        $EAPWebsite->setAttribute('report_name_link', '/content/1094#aEAP');
        $points->addComplianceView($EAPWebsite, false, $wellBeingSection);
        
        $sem = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 92, 15);
        $sem->setReportName('Attend Onsite B-Well Seminars');
        $sem->setAttribute('report_name_link', '/content/1094#bBewellSem');
        $sem->setMaximumNumberOfPoints(45);
        $points->addComplianceView($sem, false, $wellBeingSection);
            
        $healthCoaching = new PlaceHolderComplianceView(null, 0);
        $healthCoaching->setAllowPointsOverride(true);
        $healthCoaching->setName('health_coaching');
        $healthCoaching->setReportName('Complete up to 4 sessions with a phone-based Intrinsic Health Coach');
        $healthCoaching->setAttribute('report_name_link', '/content/1094#cCoach');
        $healthCoaching->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(40, 10, 0, 0));
        $healthCoaching->addLink(new Link('Info', '/content/1094#fOtherDept'));
        $points->addComplianceView($healthCoaching, false, $wellBeingSection);
                  
        
        $dev = new MinutesBasedActivityComplianceView($startDate, $endDate, 68);
        $dev->setMaximumNumberOfPoints(50);
        $dev->setMinutesDivisorForPoints(60);
        $dev->setPointsMultiplier(5);
        $dev->setReportName('Engage in Professional Development Activities');
        $dev->setAttribute('report_name_link', '/content/1094#dProfDev');
        $points->addComplianceView($dev, false, $careerSection);
        
        $virtualTrainingWebsite = new MinutesBasedActivityComplianceView($startDate, $endDate, 391);
        $virtualTrainingWebsite->setMaximumNumberOfPoints(80);
        $virtualTrainingWebsite->setMinutesDivisorForPoints(60);
        $virtualTrainingWebsite->setPointsMultiplier(5);
        $virtualTrainingWebsite->setReportName('New Virtual Training Website Activities');
        $virtualTrainingWebsite->setAttribute('report_name_link', '/content/1094#eVirtTrain');
        $points->addComplianceView($virtualTrainingWebsite, false, $careerSection);        
        
        $miniChallengeCareer = new PlaceHolderComplianceView(null, 0);
        $miniChallengeCareer->setAllowPointsOverride(true);
        $miniChallengeCareer->setReportName('Complete Month-Long Mini Challenge On the Topic of Career Well-Being in November 2014 (Deadline 12/05/2014)');
        $miniChallengeCareer->setAttribute('report_name_link', '/content/1094#fMiniChall1');
        $miniChallengeCareer->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $miniChallengeCareer->addLink(new Link('Download Form', '/resources/5107/FINAL - NOV - Career Challenge.pdf'));
        $points->addComplianceView($miniChallengeCareer, false, $careerSection);
        

        $vol = new VolunteeringComplianceView($startDate, $endDate);
        $vol->setReportName('Volunteer Your Time for Charity and Professional Organizations');
        $vol->setAttribute('report_name_link', '/content/1094#gCommVol');
        $vol->setMaximumNumberOfPoints(50);
        $vol->setMinutesDivisorForPoints(60);
        $vol->setPointsMultiplier(5);
        $points->addComplianceView($vol, false, $communitySection);
        
        $miniChallengeCommunity = new PlaceHolderComplianceView(null, 0);
        $miniChallengeCommunity->setAllowPointsOverride(true);
        $miniChallengeCommunity->setReportName('Complete Month-Long Mini Challenge on the Topic of Community Well-Being January 2015 (Deadline 02/06/2015)');
        $miniChallengeCommunity->setAttribute('report_name_link', '/content/1094#hMiniChall2');
        $miniChallengeCommunity->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $miniChallengeCommunity->addLink(new Link('Download Form', '/resources/5108/FINAL - JAN -Community Challenge.pdf'));
        $points->addComplianceView($miniChallengeCommunity, false, $communitySection);
        
        
        $attendRetireSecure = new RegexBasedActivityComplianceView($startDate, $endDate, 322, 117);
        $attendRetireSecure->setReportName('Attend a 1:1 “Retire Secure” Meeting with a Principal Financial Advisor');
        $attendRetireSecure->setAttribute('report_name_link', '/content/1094#iFinRetire');
        $attendRetireSecure->setMaximumNumberOfPoints(25);
        $points->addComplianceView($attendRetireSecure, false, $financialSection);
        
        $readWhatYouShouldDo = new RegexBasedActivityComplianceView($startDate, $endDate, 392, 141);
        $readWhatYouShouldDo->setReportName('Read "What You Should Know About Credit" in Lifeworks and Review Credit Report With One of the 3 Major Bureaus');
        $readWhatYouShouldDo->setAttribute('report_name_link', '/content/1094#jFinCredit');
        $readWhatYouShouldDo->setMaximumNumberOfPoints(10);
        $points->addComplianceView($readWhatYouShouldDo, false, $financialSection);        
        
        $simplifyYourFinances = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 393, 5);
        $simplifyYourFinances->setReportName('Listen to Podcasts – “Simplify Your Finances” and/or “Coping With Money Worries” on Lifeworks');
        $simplifyYourFinances->setAttribute('report_name_link', '/content/1094#kFinPod');
        $simplifyYourFinances->setMaximumNumberOfPoints(10);
        $points->addComplianceView($simplifyYourFinances, false, $financialSection);
          
        $attendOnSite = new PlaceHolderComplianceView(null, 0);
        $attendOnSite->setAllowPointsOverride(true);
        $attendOnSite->setReportName('Attend On-Site HealthPartners presentation on Healthcare Consumerism');
        $attendOnSite->setAttribute('report_name_link', '/content/1094#lConsumer');
        $attendOnSite->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $attendOnSite->addLink(new Link('Info', '/content/1094#fOtherDept'));
        $points->addComplianceView($attendOnSite, false, $financialSection);        
        
        $miniChallengeFinancial = new PlaceHolderComplianceView(null, 0);
        $miniChallengeFinancial->setAllowPointsOverride(true);
        $miniChallengeFinancial->setReportName('Complete Month-Long Mini Challenge on the Topic of Financial Well-Being in March 2015 (Deadline 04/03/2015)');
        $miniChallengeFinancial->setAttribute('report_name_link', '/content/1094#mMiniChall3');
        $miniChallengeFinancial->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $miniChallengeFinancial->addLink(new Link('Download Form', '/resources/5109/FINAL - MAR - Financial Challenge.pdf'));
        $points->addComplianceView($miniChallengeFinancial, false, $financialSection);   
        
        $bwellEvent = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 94, 5);
        $bwellEvent->setReportName('Attend B-Well Events');
        $bwellEvent->setAttribute('report_name_link', '/content/1094#nBewellEvents');
        $bwellEvent->setMaximumNumberOfPoints(40);
        $points->addComplianceView($bwellEvent, false, $socialSection);

        $qualityTime = new EngageLovedOneComplianceView($startDate, $endDate, 1);
        $qualityTime->setMaximumNumberOfPoints(40);
        $qualityTime->setReportName('Spend Quality Time with Your Loved Ones');
        $qualityTime->setAttribute('report_name_link', '/content/1094#oQualTime');
        $points->addComplianceView($qualityTime, false, $socialSection);
        
        $participateWithCoWorkers = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 394, 5);
        $participateWithCoWorkers->setMaximumNumberOfPoints(30);
        $participateWithCoWorkers->setReportName('Participate in a Group With Co-Workers Over a Common Interest i.e. walking group, a lunch bunch, book club, knitting circle, etc.');
        $participateWithCoWorkers->setAttribute('report_name_link', '/content/1094#pGroup');
        $points->addComplianceView($participateWithCoWorkers, false, $socialSection);    
        
        $miniChallengeSocial = new PlaceHolderComplianceView(null, 0);
        $miniChallengeSocial->setAllowPointsOverride(true);
        $miniChallengeSocial->setReportName('Complete Month-Long Mini Challenge on the Topic of Social Well-Being in May 2015 (Deadline 06/05/2015)');
        $miniChallengeSocial->setAttribute('report_name_link', '/content/1094#qMiniChall4');
        $miniChallengeSocial->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $miniChallengeSocial->addLink(new Link('Download Form', '/resources/5110/FINAL - MAY - Social Challenge.pdf'));
        $points->addComplianceView($miniChallengeSocial, false, $socialSection);   
        

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setAttribute('report_name_link', '/content/1094#rMainDoc');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $points->addComplianceView($doctorView, false, $physicalSection);

        $eyeExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 395, 20);
        $eyeExamView->setReportName('Have an Eye Exam');
        $eyeExamView->setMaximumNumberOfPoints(20);
        $eyeExamView->setAttribute('report_name_link', '/content/1094#sEyeExam');
        $points->addComplianceView($eyeExamView, false, $physicalSection);
        
        $dentalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 396, 20);
        $dentalExamView->setReportName('Have a Dental Exam');
        $dentalExamView->setMaximumNumberOfPoints(20);
        $dentalExamView->setAttribute('report_name_link', '/content/1094#tDentalExam');
        $points->addComplianceView($dentalExamView, false, $physicalSection);        

        $fluVaccineView = new FluVaccineActivityComplianceView($startDate, $endDate);
        $fluVaccineView->setReportName('Have an Annual Flu Vaccine in 2014-2015');
        $fluVaccineView->setAttribute('report_name_link', '/content/1094#uFluVac');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $points->addComplianceView($fluVaccineView, false, $physicalSection);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Get Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(80);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setPointsMultiplier(5);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#vPhysAct');
        $physicalActivityView->_setID(263);
        $points->addComplianceView($physicalActivityView, false, $physicalSection);

        $mindfulActivityView = new MinutesBasedActivityComplianceView($startDate, $endDate, 70);
        $mindfulActivityView->setMinutesDivisorForPoints(60);
        $mindfulActivityView->setPointsMultiplier(5);
        $mindfulActivityView->setMaximumNumberOfPoints(80);
        $mindfulActivityView->setName('mindful_activity');
        $mindfulActivityView->setReportName('Complete Relaxation/Resiliency-Building Activities');
        $mindfulActivityView->setAttribute('report_name_link', '/content/1094#wRelax');
        $points->addComplianceView($mindfulActivityView, false, $physicalSection);
        
        
        $seatedMeeting = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 397, 5);
        $seatedMeeting->setMaximumNumberOfPoints(5);
        $seatedMeeting->setReportName('Shift a Regularly Scheduled Seated Meeting to a Walking or Standing Meeting Instead');
        $seatedMeeting->setAttribute('report_name_link', '/content/1094#xWalkStand');
        $points->addComplianceView($seatedMeeting, false, $physicalSection);    
        
        $tryEquipment = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 398, 10);
        $tryEquipment->setMaximumNumberOfPoints(10);
        $tryEquipment->setReportName('Try a Piece of Equipment in the On-Site Fitness Center During the Workday (10-15 minutes)');
        $tryEquipment->setAttribute('report_name_link', '/content/1094#yEquipt');
        $points->addComplianceView($tryEquipment, false, $physicalSection);           
        
        
        $miniChallengePhysical = new PlaceHolderComplianceView(null, 0);
        $miniChallengePhysical->setAllowPointsOverride(true);
        $miniChallengePhysical->setReportName('Complete the Month – Long Mini Challenge on the Topic of Physical Well-Being in July 2015 (Deadline 08/07/2015)');
        $miniChallengePhysical->setAttribute('report_name_link', '/content/1094#zMiniChall5');
        $miniChallengePhysical->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $miniChallengePhysical->addLink(new Link('Download Form', '/resources/5111/FINAL - JULY - Physical Challenge.pdf'));
        $points->addComplianceView($miniChallengePhysical, false, $physicalSection);          
        
        $this->addComplianceViewGroup($points);
    }
}