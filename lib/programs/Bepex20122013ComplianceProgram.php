<?php

class Bepex20122013Printer extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        $endDateText = $status->getComplianceProgram()->getEndDate('F j, Y');

        $this->tableHeaders['total_status'] = 'Status of 1ABCD + ≥ '.Bepex20122013ComplianceProgram::POINTS_REQUIRED.' points as of:';
        $this->tableHeaders['total_link'] = $endDateText

        ?>
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

    <div class="pageHeading">2012-2013 Well-Being Rewards Program</div>


    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the 2012-2013 Well-Being Rewards Program. To receive the Well-Being Reward,
        eligible employees MUST take action and meet the following requirements by the deadlines noted:</p>
    <ol>
        <li>Complete ALL of the core required actions by the noted due dates; AND</li>
        <li>Between October 1, 2012 and September 30, 2013, earn 250 or more points from key actions taken for
            well-being
        </li>
    </ol>
    <p>
        Employees who complete the required core actions (some by November 16, 2012), and earn 250 or more points from
        various well-being actions by September 30, 2013 will earn a reward:


    </p>
    <ol type="A">


        <ul>
            <li>Either Well-being time off (8, 12 or 16 well-being time off hours - based on total points earned); OR
            </li>
            <li>A health plan premium contribution discount ($20, $30 or $40/month - based on total points earned)</li>
        </ul>

    </ol>
    <p><a href="/content/1094">Click here to learn more about the 2012-2013 rewards program, the related actions and
        other details.</a></p>

    <p>Updates & Timing: To get actions done and earn points, click on the links below. If the points or status did not
        change for an item you are working on, you may need to go back and enter missing information or entries to earn
        more points. Thanks for your actions and patience!</p>

    <?php
    }
}

class Bepex20122013ComplianceProgram extends ComplianceProgram
{
    const POINTS_REQUIRED = 250;

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Bepex20122013Printer();
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

        $hra = new CompleteHRAComplianceView('2012-10-15', '2012-11-28');
        $hra->setReportName('Complete the Annual Online Health Power Assessment (HPA) by extended deadline - 11/28/2012');
        $hra->setAttribute('report_name_link', '/content/1094#1HPA');
        $core->addComplianceView($hra);

        //$scr = new CompleteScreeningComplianceView($startDate, '2012-11-16');
        //$scr = new CompleteScreeningComplianceView($startDate, '2012-11-16');
        //$scr->setReportName('Complete the Annual Onsite Health Screening');
        //$scr->setAttribute('report_name_link', '/content/1094#1bHS');
        //$core->addComplianceView($scr);

        $survey = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $survey->setReportName('Complete the Well-Being Culture Survey by 07/31/2013');
        $survey->setAttribute('report_name_link', '/content/1094#2Survey');
        $survey->setName('survey');
        //$survey->addLink(new Link('Available in July 2012', '#'));
        $survey->addLink(new FakeLink('Bepex ', '#'));

        $core->addComplianceView($survey);

        $wisehealth = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $wisehealth->setReportName('Be current on all age-appropriate physical exams by 06/30/2013');
        $wisehealth->setName('wisehealth');
        $wisehealth->setAttribute('report_name_link', '/content/1094#3Screenings');
        //$wisehealth->addLink(new Link('Available in March 2012', '#'));
        $wisehealth->addLink(new Link('Bepex Provider Certification Form 2012-2013', '/resources/3978/Bepex Provider Certification Form 2012-2013.pdf'));

        $core->addComplianceView($wisehealth);

        $this->addComplianceViewGroup($core);

        $points = new ComplianceViewGroup('points', 'And, earn '.self::POINTS_REQUIRED.' or more points from the different areas of well-being by '.$this->getEndDate('F j, Y'));
        $points->setPointsRequiredForCompliance(self::POINTS_REQUIRED);

        /*$career = new PlaceHolderComplianceView(null, 0);
        $career->setReportName('Declare your intent to set a well-being goal');
        $career->setName('career_goal');
        $career->setAttribute('report_name_link', '/content/1094#2allgoal');
        $career->setMaximumNumberOfPoints(10);
        $career->addLink(new FakeLink('Declare by October 31, 2011', '#'));
        $points->addComplianceView($career, false, $wellBeingSection);

        $report = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, null);
        $report->setReportName('Report on What You Learned About Yourself');
        $report->setName('report_self');
        $report->setAttribute('report_name_link', '/content/1094#2allgoal');
        $report->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(90, 0, 0, 0));
        $report->addLink(new FakeLink('Report by August 31, 2012', '#'));
        $points->addComplianceView($report, false, $wellBeingSection);*/

        $coach = new PlaceHolderComplianceView(null, 0);
        $coach->setAllowPointsOverride(true);
        $coach->setName('wellbeing_goal');
        $coach->setReportName('Set and Work Towards a Personal Well-Being Goal');
        $coach->setAttribute('report_name_link', '/content/1094#aGoal');
        $coach->addLink(new Link('WB Coaching Overview   ', '/resources/3979/WB Coaching Overview.pdf'));
        $coach->addLink(new Link('Bepex 2012-2013 Well-Being Goal Overview', '/resources/3980/Bepex 2012-2013 Well-Being Goal Overview.pdf'));
        $coach->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(200, 0, 0, 0));
        $points->addComplianceView($coach, false, $wellBeingSection);

        $elearn = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $elearn->setPointsPerLesson(5);
        $elearn->setName('elearn');
        $elearn->setReportName('Complete eLearning Lessons');
        $elearn->setAttribute('report_name_link', '/content/1094#bELearn');
        $elearn->setMaximumNumberOfPoints(15);
        $points->addComplianceView($elearn, false, $wellBeingSection);

        $sem = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 92, 10);
        $sem->setReportName('Attend Onsite B-Well Seminars');
        $sem->setAttribute('report_name_link', '/content/1094#cBewellSem');
        $sem->setMaximumNumberOfPoints(50);
        $points->addComplianceView($sem, false, $wellBeingSection);

        $dev = new MinutesBasedActivityComplianceView($startDate, $endDate, 68);
        $dev->setMaximumNumberOfPoints(15);
        $dev->setMinutesDivisorForPoints(60);
        $dev->setReportName('Engage in Professional Development Activities');
        $dev->setAttribute('report_name_link', '/content/1094#dProfDev');
        $points->addComplianceView($dev, false, $careerSection);

        $edu = new MinutesBasedActivityComplianceView($startDate, $endDate, 69, 72);
        $edu->setReportName('Attend Formal Education Classes');
        $edu->setAttribute('report_name_link', '/content/1094#eEdClass');
        $edu->setMaximumNumberOfPoints(20);
        $edu->setMinutesDivisorForPoints(1 / 15);
        $points->addComplianceView($edu, false, $careerSection);

        $carGoal = new RegexBasedActivityComplianceView($startDate, $endDate, 96, 80);
        $carGoal->setReportName('Complete the Career Goal That Was Set With Your Manager');
        $carGoal->setAttribute('report_name_link', '/content/1094#fCarGoal');
        $carGoal->setMaximumNumberOfPoints(20);
        $points->addComplianceView($carGoal, false, $careerSection);

        $carSem = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 97, 5);
        $carSem->setReportName('Complete an Online Career-Related Seminar Through LifeWorks');
        $carSem->setAttribute('report_name_link', '/content/1094#gOnlineSem');
        $carSem->setMaximumNumberOfPoints(20);
        $points->addComplianceView($carSem, false, $careerSection);

        $vol = new VolunteeringComplianceView($startDate, $endDate);
        $vol->setReportName('Volunteer Your Time for Charity and Professional Organizations');
        $vol->setAttribute('report_name_link', '/content/1094#hCommVol');
        $vol->setMaximumNumberOfPoints(50);
        $vol->setMinutesDivisorForPoints(60);
        $vol->setPointsMultiplier(5);
        $points->addComplianceView($vol, false, $communitySection);

        $incrFin = new RegexBasedActivityComplianceView($startDate, $endDate, 89, 75);
        $incrFin->setReportName('Save More Money');
        $incrFin->setAttribute('report_name_link', '/content/1094#iFinSave');
        $incrFin->setMaximumNumberOfPoints(10);
        $points->addComplianceView($incrFin, false, $financialSection);

        $budget = new RegexBasedActivityComplianceView($startDate, $endDate, 93, 77);
        $budget->setReportName('Complete the Financial Well-Being Campaign');
        $budget->setAttribute('report_name_link', '/content/1094#jFinBudg');
        $budget->setMaximumNumberOfPoints(30);
        $points->addComplianceView($budget, false, $financialSection);

        $pBudget = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 98, 10);
        $pBudget->setName('personal_budget');
        $pBudget->setReportName('Complete the Personal Budget Online Seminar or Use Retirement Planner Tool');
        $pBudget->setAttribute('report_name_link', '/content/1094#kFinOnlineSem');
        $pBudget->setMaximumNumberOfPoints(20);
        $points->addComplianceView($pBudget, false, $financialSection);

        $bwellEvent = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 94, 5);
        $bwellEvent->setReportName('Attend B-Well Events');
        $bwellEvent->setAttribute('report_name_link', '/content/1094#lBewellEvents');
        $bwellEvent->setMaximumNumberOfPoints(30);
        $points->addComplianceView($bwellEvent, false, $socialSection);

        $qualityTime = new EngageLovedOneComplianceView($startDate, $endDate, 1);
        $qualityTime->setMaximumNumberOfPoints(40);
        $qualityTime->setReportName('Spend Quality Time with Your Loved Ones');
        $qualityTime->setAttribute('report_name_link', '/content/1094#mQualTime');
        $points->addComplianceView($qualityTime, false, $socialSection);

        $spouseSurvey = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $spouseSurvey->setName('spouse_survey');
        $spouseSurvey->setReportName('Have Your Spouse Complete the Health Assessment');
        $spouseSurvey->setAttribute('report_name_link', '/content/1094#nSpouse');
        $spouseSurvey->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $points->addComplianceView($spouseSurvey, false, $socialSection);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setAttribute('report_name_link', '/content/1094#oMainDoc');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $points->addComplianceView($doctorView, false, $physicalSection);

        $preventiveExamsView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 262, 5);
        $preventiveExamsView->setReportName('Have a Dental and/or Vision Exam');
        $preventiveExamsView->setMaximumNumberOfPoints(10);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#pDentalVision');
        $points->addComplianceView($preventiveExamsView, false, $physicalSection);

        $fluVaccineView = new FluVaccineActivityComplianceView($startDate, $endDate);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setAttribute('report_name_link', '/content/1094#qFluVac');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $points->addComplianceView($fluVaccineView, false, $physicalSection);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(50);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#rPhysAct');
        $physicalActivityView->_setID(263);
        $points->addComplianceView($physicalActivityView, false, $physicalSection);

        $mindfulActivityView = new MinutesBasedActivityComplianceView($startDate, $endDate, 70);
        $mindfulActivityView->setMinutesDivisorForPoints(15);
        $mindfulActivityView->setMaximumNumberOfPoints(50);
        $mindfulActivityView->setName('mindful_activity');
        $mindfulActivityView->setReportName('Relaxation/Resilience-Building Activities');
        $mindfulActivityView->setAttribute('report_name_link', '/content/1094#sRelax');
        $points->addComplianceView($mindfulActivityView, false, $physicalSection);

        $this->addComplianceViewGroup($points);
    }
}