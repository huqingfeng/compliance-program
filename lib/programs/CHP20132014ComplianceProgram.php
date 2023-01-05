<?php

class CHP20132014Printer extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        $endDateText = 'Needed By 09/30/2014';

        $this->tableHeaders['total_status'] = 'Status of 1ABCD + ≥ '.CHP20132014ComplianceProgram::POINTS_REQUIRED.' points as of:';
        $this->tableHeaders['total_link'] = $endDateText

        ?>
        <script type="text/javascript">
            $(function(){
                $('.phipTable .physical-emotional-well-being').next().children(':eq(0)').html('R. Have Key Biometric Measures in a Health Zone:');
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

    <div class="pageHeading">2013-2014 Well-Being Rewards Program</div>


    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the 2013-2014 Well-Being Rewards Program. To receive the Well-Being Reward,
        eligible employees MUST take action and meet the following requirements by the deadlines noted:</p>
    <ol>
        <li>Complete ALL of the core required actions by the noted due dates; AND</li>
        <li>Between October 1, 2013 and September 30, 2014, earn 250 or more points from key actions taken for
            well-being
        </li>
    </ol>
    <p>
        Employees who complete the required core actions (some by November 16, 2013), and earn 250 or more points from
        various well-being actions by September 30, 2013 will earn a reward:


    </p>
    <ol type="A">


        <ul>
            <li>Either Well-being time off (8, 12 or 16 well-being time off hours - based on total points earned); OR
            </li>
            <li>A health plan premium contribution discount ($20, $30 or $40/month - based on total points earned)</li>
        </ul>

    </ol>
    <p><a href="/content/1094">Click here to learn more about the 2013-2014 rewards program, the related actions and
        other details.</a></p>

    <p>Updates & Timing: To get actions done and earn points, click on the links below. If the points or status did not
        change for an item you are working on, you may need to go back and enter missing information or entries to earn
        more points. Thanks for your actions and patience!</p>

    <?php
    }
}

class CHP20132014ComplianceProgram extends ComplianceProgram
{
    const POINTS_REQUIRED = 250;

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new CHP20132014Printer();
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

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Complete the Annual Online Health Power Assessment (HPA)');
        $hra->setAttribute('report_name_link', '/content/1094#1HPA');
        $core->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($startDate, $endDate);
        $scr->setReportName('Complete the Onsite Health Screening');
        $scr->setAttribute('report_name_link', '/content/1094#2Screen');
        $core->addComplianceView($scr);

        $survey = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $survey->setReportName('Complete the Well-Being Evaluation survey');
        $survey->setAttribute('report_name_link', '/content/1094#3Survey');
        $survey->setName('survey');
        //$survey->addLink(new Link('Available in July 2012', '#'));
        $survey->addLink(new FakeLink('Available July, 2014 ', '#'));

        $core->addComplianceView($survey);

        $this->addComplianceViewGroup($core);

        $points = new ComplianceViewGroup('points', 'And, earn '.self::POINTS_REQUIRED.' or more points from the different areas of well-being by '.$this->getEndDate('F j, Y'));
        $points->setPointsRequiredForCompliance(self::POINTS_REQUIRED);

        $selectTopic = new PlaceHolderComplianceView(null, 0);
        $selectTopic->setAllowPointsOverride(true);
        $selectTopic->setName('wellbeing_goal');
        $selectTopic->setReportName('Select a Well-Being Topic on the LifeWorks Website that is Important to You and You are Interested in Learning More About by June, 1 2014');
        $selectTopic->setAttribute('report_name_link', '/content/1094#aLifeworks');
        $selectTopic->addLink(new Link('Info', '/content/1094#aLifeworks'));
        $selectTopic->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $points->addComplianceView($selectTopic, false, $wellBeingSection);

        $occasion = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 316, 15);
        $occasion->setMaximumNumberOfPoints(45);
        $occasion->setReportName('On At Least 3 Separate Occasions, Spend a Minimum of 30 Minutes with the LifeWorks Resources Applicable to Your Well-Being Focus Area');
        $occasion->setAttribute('report_name_link', '/content/1094#aLifeworks');
        $points->addComplianceView($occasion, false, $wellBeingSection);

        $evaluation = new PlaceHolderComplianceView(null, 0);
        $evaluation->setAllowPointsOverride(true);
        $evaluation->setReportName('Complete a Brief Evaluation by Aug 29, 2014');
        $evaluation->setAttribute('report_name_link', '/content/1094#aLifeworks');
        $evaluation->addLink(new Link('Info', '/content/1094#aLifeworks'));
        $evaluation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $points->addComplianceView($evaluation, false, $wellBeingSection);        
        
        $healthyThinking = new PlaceHolderComplianceView(null, 0);
        $healthyThinking->setAllowPointsOverride(true);
        $healthyThinking->setReportName('Complete the Healthy Thinking Program');
        $healthyThinking->setAttribute('report_name_link', '/content/1094#bHealthyThink');
        $healthyThinking->addLink(new Link('Info', '/content/1094#bHealthyThink'));
        $healthyThinking->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $points->addComplianceView($healthyThinking, false, $wellBeingSection);        
        
        $sem = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 92, 15);
        $sem->setReportName('Attend Onsite B-Well Seminars');
        $sem->setAttribute('report_name_link', '/content/1094#cBewellSem');
        $sem->setMaximumNumberOfPoints(45);
        $points->addComplianceView($sem, false, $wellBeingSection);
        
        
        $completeMission = new PlaceHolderComplianceView(null, 0);
        $completeMission->setAllowPointsOverride(true);
        $completeMission->setReportName('Complete the “Clarify How I align with CHP’s Mission/Vision” exercise');
        $completeMission->setAttribute('report_name_link', '/content/1094#dmission');
        $completeMission->addLink(new Link('Info', '/content/1094#dmission'));
        $completeMission->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $points->addComplianceView($completeMission, false, $careerSection);  
        
        $meet = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 320, 25);
        $meet->setReportName('Meet with your Manager to discuss the “Clarify How I align with CHP’s Mission/Vision” exercise');
        $meet->setAttribute('report_name_link', '/content/1094#eManager');
        $meet->setMaximumNumberOfPoints(25);
        $points->addComplianceView($meet, false, $careerSection);        

        $completeQuiz = new PlaceHolderComplianceView(null, 0);
        $completeQuiz->setAllowPointsOverride(true);
        $completeQuiz->setReportName('Successfully complete the “Get to Know Other Department’s” quiz');
        $completeQuiz->setAttribute('report_name_link', '/content/1094#fOtherDept');
        $completeQuiz->addLink(new Link('Info', '/content/1094#fOtherDept'));
        $completeQuiz->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $points->addComplianceView($completeQuiz, false, $careerSection); 
        
        $dev = new MinutesBasedActivityComplianceView($startDate, $endDate, 68);
        $dev->setMaximumNumberOfPoints(30);
        $dev->setMinutesDivisorForPoints(60);
        $dev->setReportName('Engage in Professional Development Activities');
        $dev->setAttribute('report_name_link', '/content/1094#gProfDev');
        $points->addComplianceView($dev, false, $careerSection);

        $vol = new VolunteeringComplianceView($startDate, $endDate);
        $vol->setReportName('Volunteer Your Time for Charity and Professional Organizations');
        $vol->setAttribute('report_name_link', '/content/1094#hCommVol');
        $vol->setMaximumNumberOfPoints(50);
        $vol->setMinutesDivisorForPoints(60);
        $vol->setPointsMultiplier(5);
        $points->addComplianceView($vol, false, $communitySection);

        $attendRetireSecure = new RegexBasedActivityComplianceView($startDate, $endDate, 322, 117);
        $attendRetireSecure->setReportName('Attend a 1:1 “Retire Secure” Meeting with a Principal Financial Advisor');
        $attendRetireSecure->setAttribute('report_name_link', '/content/1094#iFinRetire');
        $attendRetireSecure->setMaximumNumberOfPoints(25);
        $points->addComplianceView($attendRetireSecure, false, $financialSection);
        
        $reviewCredit = new RegexBasedActivityComplianceView($startDate, $endDate, 323, 118);
        $reviewCredit->setReportName('Review your Credit Report with Equifax, TransUnion or Experian');
        $reviewCredit->setAttribute('report_name_link', '/content/1094#jFinCredit');
        $reviewCredit->setMaximumNumberOfPoints(20);
        $points->addComplianceView($reviewCredit, false, $financialSection);        
        
        $participateLifeWork = new RegexBasedActivityComplianceView($startDate, $endDate, 324, 119);
        $participateLifeWork->setReportName('Participate in the LifeWorks podcast “Saving More & Spending Less”');
        $participateLifeWork->setAttribute('report_name_link', '/content/1094#kFinBudg');
        $participateLifeWork->setMaximumNumberOfPoints(15);
        $points->addComplianceView($participateLifeWork, false, $financialSection);        
        
        $learnCredit = new RegexBasedActivityComplianceView($startDate, $endDate, 325, 120);
        $learnCredit->setReportName('Learn about “Credit and Your Consumer Rights” through LifeWorks');
        $learnCredit->setAttribute('report_name_link', '/content/1094#lConsRts');
        $learnCredit->setMaximumNumberOfPoints(15);
        $points->addComplianceView($learnCredit, false, $financialSection);                    
        

        $bwellEvent = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 94, 5);
        $bwellEvent->setReportName('Attend B-Well Events');
        $bwellEvent->setAttribute('report_name_link', '/content/1094#mBewellEvents');
        $bwellEvent->setMaximumNumberOfPoints(45);
        $points->addComplianceView($bwellEvent, false, $socialSection);

        $qualityTime = new EngageLovedOneComplianceView($startDate, $endDate, 1);
        $qualityTime->setMaximumNumberOfPoints(40);
        $qualityTime->setReportName('Spend Quality Time with Your Loved Ones');
        $qualityTime->setAttribute('report_name_link', '/content/1094#nQualTime');
        $points->addComplianceView($qualityTime, false, $socialSection);

        $spouseSurvey = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $spouseSurvey->setName('spouse_survey');
        $spouseSurvey->setReportName('Have Your Spouse Complete the Health Assessment');
        $spouseSurvey->setAttribute('report_name_link', '/content/1094#oSpouse');
        $spouseSurvey->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 0, 0, 0));
        $points->addComplianceView($spouseSurvey, false, $socialSection);


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
        $doctorView->setAttribute('report_name_link', '/content/1094#qMainDoc');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $points->addComplianceView($doctorView, false, $physicalSection);

        $preventiveExamsView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 26, 10);
        $preventiveExamsView->setReportName('Do Recommended Preventive Screenings/Exams');
        $preventiveExamsView->setMaximumNumberOfPoints(40);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#rPrevScrn');
        $points->addComplianceView($preventiveExamsView, false, $physicalSection);

        $fluVaccineView = new FluVaccineActivityComplianceView($startDate, $endDate);
        $fluVaccineView->setReportName('Have Annual Flu Vaccine in 2013');
        $fluVaccineView->setAttribute('report_name_link', '/content/1094#sFluVac');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $points->addComplianceView($fluVaccineView, false, $physicalSection);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Get Regular Physical Activity ');
        $physicalActivityView->setMaximumNumberOfPoints(50);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/12048?action=showActivity&activityidentifier=1');
        $physicalActivityView->_setID(263);
        $points->addComplianceView($physicalActivityView, false, $physicalSection);

        $mindfulActivityView = new MinutesBasedActivityComplianceView($startDate, $endDate, 70);
        $mindfulActivityView->setMinutesDivisorForPoints(15);
        $mindfulActivityView->setMaximumNumberOfPoints(50);
        $mindfulActivityView->setName('mindful_activity');
        $mindfulActivityView->setReportName('Relaxation/Resilience-Building Activities');
        $mindfulActivityView->setAttribute('report_name_link', '/content/1094#uRelax');
        $points->addComplianceView($mindfulActivityView, false, $physicalSection);

        $this->addComplianceViewGroup($points);
    }
}