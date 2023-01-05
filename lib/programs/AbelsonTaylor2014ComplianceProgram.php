<?php

use hpn\steel\query\SelectQuery;


class AbelsonTaylor2014ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Program');
//        $coreGroup->setPointsRequiredForCompliance(50);
        
        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Power Profile Questionnaire');
        $hraView->setName('complete_hra');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $hraView->setAttribute('report_name_link', '/content/1094#ahpa');
        $hraView->setAttribute('requirement', 'Employee completes the Health Power Profile Questionnaire');
        $hraView->setAttribute('points_per_activity', '10');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $coreGroup->addComplianceView($hraView);
        
        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('2014 Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $screeningView->setAttribute('report_name_link', '/content/1094#bScreen');
        $screeningView->setAttribute('requirement', 'Employee participates in the 2014 Wellness Screening');
        $screeningView->setAttribute('points_per_activity', '10');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $coreGroup->addComplianceView($screeningView);
        
        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        // While it contains the employee constant, an employee can't have a
        // related employee user so it doesn't matter.
        $eligibleDependents = Relationship::get();

        $spouseHra = new RelatedUserCompleteComplianceViewsComplianceView($this, array('complete_hra'), $eligibleDependents);
        $spouseHra->setPointsPerCompletion(10);
        $spouseHra->setMaximumNumberOfPoints(20);
        $spouseHra->setReportName('Health Power Profile Questionnaire');
        $spouseHra->setName('spouse_hra');
        $spouseHra->setAttribute('points_per_activity', 10);
        $wellnessGroup->addComplianceView($spouseHra);

        $spouseScr = new RelatedUserCompleteComplianceViewsComplianceView($this, array('complete_screening'), $eligibleDependents);
        $spouseScr->setPointsPerCompletion(10);
        $spouseScr->setMaximumNumberOfPoints(20);
        $spouseScr->setReportName('2014 Wellness Screening');
        $spouseScr->setName('spouse_screening');
        $spouseScr->setAttribute('points_per_activity', 10);
        $wellnessGroup->addComplianceView($spouseScr);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 290, 6);
        $annualPhysicalExamView->setMaximumNumberOfPoints(10);
        $annualPhysicalExamView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Get a physical exam with appropriate tests for your age and gender as recommended by your physician.');
        $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening follow up');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->setAttribute('report_name_link', '/content/1094#ePhysExam');
        $annualPhysicalExamView->setAttribute('points_per_activity', '10');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link('Click here for verification form ', '/resources/4766/AT_PreventiveServices_Cert.031214.pdf'));
        $wellnessGroup->addComplianceView($annualPhysicalExamView);
        
        $wellnessKickOff = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 330, 10);
        $wellnessKickOff->setReportName('Wellness Kick-Off');
        $wellnessKickOff->setName('kick_off');
        $wellnessKickOff->setMaximumNumberOfPoints(10);
        $wellnessKickOff->setAttribute('requirement', 'Attend the Wellness Kick-Off meeting');
        $wellnessKickOff->setAttribute('points_per_activity', '10');
        $wellnessKickOff->setAttribute('report_name_link', '/content/1094#fKickoff');
        $wellnessKickOff->emptyLinks();
        $wellnessKickOff->addLink(new Link('Sign in at Event', '/content/events'));
        $wellnessGroup->addComplianceView($wellnessKickOff);
        
        $preventiveExamsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($programStart, $programEnd, 5);
        $preventiveExamsView->configureActivity(259, 91, array(
            'Colonoscopy'   => 60,
            'Dental Exam'   => 24,
            'Mammogram'     => 24,
            'Pap Test'      => 36,
            'Physical Exam' => 36,
            'PSA Test'      => 60
        ));
        $preventiveExamsView->setReportName("Preventive Services");
        $preventiveExamsView->setMaximumNumberOfPoints(30);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#gPrevServices');
        $preventiveExamsView->setAttribute('requirement', 'Receive a preventive service such as mammogram, prostate exam, routine dental exam, etc.');
        $preventiveExamsView->setAttribute('points_per_activity', '10');
        $preventiveExamsView->emptyLinks();
        $preventiveExamsView->addLink(new Link('Verification form /', '/resources/4766/AT_PreventiveServices_Cert.031214.pdf'));
        $preventiveExamsView->addLink(new Link(' Wellness Member Guidelines', '/resources/4767/childrens_wellness_member_guidelines.031214.pdf'));
        $wellnessGroup->addComplianceView($preventiveExamsView);
        
        $flushotView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $flushotView->setReportName('Flu Shot');
        $flushotView->setName('flu_shot');
        $flushotView->setMaximumNumberOfPoints(30);
        $flushotView->setAttribute('report_name_link', '/content/1094#hFluShot');
        $flushotView->setAttribute('requirement', 'Receive a flu Shot');
        $flushotView->setAttribute('points_per_activity', '10');
        $flushotView->emptyLinks();
        $flushotView->addLink(new Link('Verification form /', '/resources/4766/AT_PreventiveServices_Cert.031214.pdf'));
        //$flushotView->addLink(new Link('Sign in at Event', '/content/events'));
        //$flushotView->addLink(new Link('Click here for verification form', '/content/12048?action=showActivity&activityidentifier=20'));
        $wellnessGroup->addComplianceView($flushotView);      
        
        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('E-learning');
        $elearn->setName('elearning');
        $elearn->setAttribute('report_name_link', '/content/1094#iRecElearn');
        $elearn->setAttribute('requirement', 'Complete an online e-learning course');
        $elearn->setAttribute('points_per_activity', '5');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(25);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420'));
        $wellnessGroup->addComplianceView($elearn);

        $onMyTimeView = new PlaceHolderComplianceView(null, 0);
        $onMyTimeView->setReportName('OnMyTime Courses');
        $onMyTimeView->setName('on_my_time');
        $onMyTimeView->setAttribute('requirement', 'Complete BCBS Online Program via Well On Target');
        $onMyTimeView->setAttribute('points_per_activity', '15');
        $onMyTimeView->setAttribute('report_name_link', '/content/1094#jOnMyTime');
        $onMyTimeView->emptyLinks();
        $onMyTimeView->addLink((new Link('Click Here', 'http://wellontarget.com')));
        $onMyTimeView->setMaximumNumberOfPoints(45);
        $wellnessGroup->addComplianceView($onMyTimeView);
        
        $GLnlPrograms = new AttendEventComplianceView($programStart, $programEnd);
        $GLnlPrograms->bindTypeIds(array(9));
        $GLnlPrograms->setPointsPerAttendance(10);
        $GLnlPrograms->setReportName('Lunch and Learn Presentation');
        $GLnlPrograms->setName('lunch_and_learn');
        $GLnlPrograms->setAttribute('report_name_link', '/content/1094#kLnL');
        $GLnlPrograms->setAttribute('points_per_activity', '10');
        $GLnlPrograms->setMaximumNumberOfPoints(60);
        $GLnlPrograms->addLink(new Link('Sign in at Event', '/content/events'));
        $wellnessGroup->addComplianceView($GLnlPrograms);      
        
        $abelsonTaylorWellnessEventsView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $abelsonTaylorWellnessEventsView->setReportName('Other Abelson Taylor Wellness Events');
        $abelsonTaylorWellnessEventsView->setName('abelson_taylor_wellness_events');
        $abelsonTaylorWellnessEventsView->setMaximumNumberOfPoints(100);
        $abelsonTaylorWellnessEventsView->setAttribute('report_name_link', '/content/1094#lOther');
        $abelsonTaylorWellnessEventsView->setAttribute('requirement', 'Track a minimum of 90 minutes of activity/week on the HMI website');
        $abelsonTaylorWellnessEventsView->setAttribute('points_per_activity', '');
        $abelsonTaylorWellnessEventsView->emptyLinks();
        $abelsonTaylorWellnessEventsView->addLink(new Link('Admin will Enter', '#'));
        $wellnessGroup->addComplianceView($abelsonTaylorWellnessEventsView);
    
        $wellnessGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($wellnessGroup);     
        
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new AbelsonTaylor2014ComplianceProgramReportPrinter();

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2012-08-15';
}


class AbelsonTaylor2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        ?>

<?php
    }
    
    public function printReport(ComplianceProgramStatus $status) 
    {       
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $coreGroup = $coreGroupStatus->getComplianceViewGroup();
        
        $completeHraStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        
        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness_programs');
        $wellnessGroup = $wellnessGroupStatus->getComplianceViewGroup();

        $spouseHraStatus = $wellnessGroupStatus->getComplianceViewStatus('spouse_hra');
        $spouseScrStatus = $wellnessGroupStatus->getComplianceViewStatus('spouse_screening');
        
        $annualPhysicalExam = $wellnessGroupStatus->getComplianceViewStatus('annual_physical_exam');
        $kickOff = $wellnessGroupStatus->getComplianceViewStatus('kick_off');
        $preventiveExams = $wellnessGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $fluShot = $wellnessGroupStatus->getComplianceViewStatus('flu_shot');
        $elearning = $wellnessGroupStatus->getComplianceViewStatus('elearning');
        $onMyTime = $wellnessGroupStatus->getComplianceViewStatus('on_my_time');
        $lnl = $wellnessGroupStatus->getComplianceViewStatus('lunch_and_learn');
        $abelsonTaylorWellnessEvents = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events');
        
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
            height:46px;
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
        
        .phipTable th, .phipTable td {
            border:1px solid #000000;
            padding:2px;
        }
        
        .light {
            width:25px;
        }
        
        .center {
            text-align:center;
        }
        
        .section {
            height:16px; 
            color: white; 
            background-color:#436EEE;
        }
        
        .requirement {
            width: 350px;
        }
        
        #programTable {
            border-collapse: collapse;
            margin:0 auto;
        }
        
        #programTable tr th, #programTable tr td{
            border:1px solid #26B000;
        }
        
    </style>

    <script type="text/javascript">
        // Set max points text for misc points earned
    </script>
    <!-- Text atop report card-->
    <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The AbelsonTaylor Comprehensive Wellness Program</p>
    <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

    <p>Abelson Taylor cares about your health!  We have partnered with HMI Health and Axion RMS to implement our Wellness Program.   The wellness program provides you with fun, robust programming options geared towards specific areas of your health that need improvement.  This Wellness Program is your way to better, healthier living.

    </p>

    <p style="font-weight: bolder; font-size: 12pt; text-align: center">HOW DOES THE PROGRAM WORK?</p>
    <p>
        <span style="font-weight:bolder;">Employees that complete the 2014 Health Screening and HRA are eligible to participate.</span>
        Participation in the program will earn wellness points that will be tracked in the table below.  Rewards will be based on points earned between 3/1/14 and 2/28/2015 so plan your point accumulation accordingly.
    </p>
    
    <div>
        <table id="programTable">
            <tr style="background-color:#008787">
                <th>Status Level</th>
                <th>Participation</th>
                <th>Points</th>
                <th>Reward</th>
            </tr>
            <tr>
                <td>Bronze</td>
                <td>Completes Wellness Screening and Health Assessment Questionnaire</td>
                <td>Requirement for Program Participation</td>
                <td>$20 monthly premium discount (Discount good for maximum of 12 months).</td>
            </tr>
            <tr>
                <td>Silver</td>
                <td>Complete Bronze level and accumulate 50 points</td>
                <td>50 Total points</td>
                <td>$100 HSA (Health Savings Account) deposit OR one time premium discount</td>
            </tr>
            <tr>
                <td>Gold</td>
                <td>Complete Bronze and Silver levels and accumulate 50 additional points</td>
                <td>100 Total Points</td>
                <td>Additional $150 HSA deposit OR one time premium discount.</td>
            </tr>        
        </table>
    </div><br />
<p>	 Some tips for using the tables are as follows: If the text is underlined and in blue you can click on it to learn more.</p>

<table class="phipTable">
    <tbody>
        <tr><th colspan="6" style="height:36px; text-align:center; color: white; background-color:#436EEE;">AT 2014 Wellness Rewards Program</th></tr>
        <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Required Activities</span> A & B are required for participation in program</th></tr>
        <tr class="headerRow headerRow-core">
            <th colspan="2" class="center">Requirement</th>
            <th class="center">Status</th>
            <th colspan="3" class="center">Tracking Method</th>
        </tr>
        <tr class="view-complete_hra">
            <td colspan="2">
                <a href="<?php echo $completeHraStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>A</strong>. <?php echo $completeHraStatus->getComplianceView()->getAttribute('requirement') ?></a>
            </td>
            <td class="center">
                <img src="<?php echo $completeHraStatus->getLight(); ?>" class="light"/>
            </td>
            <td colspan="3" class="center">
                <?php foreach($completeHraStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        <tr class="view-complete_screening">
            <td colspan="2">
                <a href="<?php echo $completeScreeningStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>B</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?></a>
            </td>
            <td class="center">
                <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
            </td>
            <td colspan="3" class="center">
                <?php foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Point Earning Wellness Activities</span> C-L are additional activities to help you earn points</th></tr>
        
        <tr class="headerRow headerRow-wellness_programs">
            <td class="center">Activity</td>
            <td class="center">Requirement</td>
            <td class="center">Points Per Activity</td>
            <td class="center"># Points Earned</td>
            <td class="center">Max Points</td>
            <td class="center">Tracking Method</td>
        </tr>
        <tr>
            <td>
                <a href="<?php echo $spouseHraStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>C</strong>. <?php echo $spouseHraStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Eligible Spouse, Domestic Partner, and/or Dependents over age 18</td>
            <td class="center"><?php echo $spouseHraStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $spouseHraStatus->getPoints() ?></td>
            <td class="center"><?php echo $spouseHraStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($spouseHraStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        <tr>
            <td>
                <a href="<?php echo $spouseScrStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>D</strong>. <?php echo $spouseScrStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Eligible Spouse, Domestic Partner, and/or Dependents over age 18</td>
            <td class="center"><?php echo $spouseScrStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $spouseScrStatus->getPoints() ?></td>
            <td class="center"><?php echo $spouseScrStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($spouseScrStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr>
            <td>
                <a href="<?php echo $annualPhysicalExam->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>E</strong>. <?php echo $annualPhysicalExam->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Visit your personal physician to follow-up on your wellness screening and complete your annual exam</td>
            <td class="center"><?php echo $annualPhysicalExam->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $annualPhysicalExam->getPoints() ?></td>
            <td class="center"><?php echo $annualPhysicalExam->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($annualPhysicalExam->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr>
            <td>
                <a href="<?php echo $kickOff->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>F</strong>. <?php echo $kickOff->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Attend the wellness kick off meeting</td>
            <td class="center"><?php echo $kickOff->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $kickOff->getPoints() ?></td>
            <td class="center"><?php echo $kickOff->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($kickOff->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr>
            <td>
                <a href="<?php echo $preventiveExams->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>G</strong>. <?php echo $preventiveExams->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Receive a preventative service such as mammogram, prostate exam, immunization, vaccine, eye or dental exam, colonoscopy, etc.  
                See attached wellness guides or check with your personal physician for necessary tests.</td>
            <td class="center"><?php echo $preventiveExams->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $preventiveExams->getPoints() ?></td>
            <td class="center"><?php echo $preventiveExams->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($preventiveExams->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr>
            <td>
                <a href="<?php echo $fluShot->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>H</strong>. <?php echo $fluShot->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Receive a flu shot for yourself,Eligible Spouse, Domestic Partner, and/or Dependents over age 18</td>
            <td class="center"><?php echo $fluShot->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $fluShot->getPoints() ?></td>
            <td class="center"><?php echo $fluShot->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($fluShot->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr>
            <td>
                <a href="<?php echo $elearning->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>I</strong>. <?php echo $elearning->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Complete an online eLearning course</td>
            <td class="center"><?php echo $elearning->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $elearning->getPoints() ?></td>
            <td class="center"><?php echo $elearning->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($elearning->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr>
            <td>
                <a href="<?php echo $onMyTime->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>J</strong>. <?php echo $onMyTime->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Complete BCBS Online Program via Well On Target** <a href="/resources/4768/WOT_OnMyTimeCourse_DescriptionALL.031214.pdf">Wellontarget Instructions</a></td>
            <td class="center"><?php echo $onMyTime->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $onMyTime->getPoints() ?></td>
            <td class="center"><?php echo $onMyTime->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($onMyTime->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr>
            <td>
                <a href="<?php echo $lnl->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>K</strong>. <?php echo $lnl->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement">Attend a Health and Wellness Lunch and Learn (open enrollment meetings, other wellness meetings)</td>
            <td class="center"><?php echo $lnl->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $lnl->getPoints() ?></td>
            <td class="center"><?php echo $lnl->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($lnl->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr>
            <td>
                <a href="<?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>L</strong>. <?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="requirement"></td>
            <td class="center"><?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getAttribute('points_per_activity') ?></td>
            <td class="center"><?php echo $abelsonTaylorWellnessEvents->getPoints() ?></td>
            <td class="center"><?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            <td class="center">
                <?php foreach($abelsonTaylorWellnessEvents->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>        
        
        <tr>
            <td>
                <strong>Total Points</strong>
            </td>
            <td class="requirement"></td>
            <td class="center"></td>
            <td class="center"><?php echo $wellnessGroupStatus->getPoints() ?></td>
            <td class="center"><?php echo $wellnessGroup->getMaximumNumberOfPoints() ?></td>
            <td class="center"></td>
        </tr>        
    </tbody>
 </table>



            <br /><br /><p>Additional points can be achieved by participating in Wellness
        events onsite like open enrollment meeting, attending a wellness lunch and learn,
        approved online eLearning, participation in other approved events through our biometric
        screening provider, HMI. </p>
        <p>Achieving Silver and Gold Status will be paid out on a monthly basis.
            At the end of each month a report will be run from HMI and if an employee reaches
            silver or gold status then a deposit into an employee’s HSA (if employees are on
            a high deductible plan) or a one time premium discount will occur (if the employees
            are not on a high deductible plan or on an HMO plan).  Deposits or discounts will
            take place on the 15th of each month following achievement of Silver or Gold status. </p>


            
    <?php
    }


    public $showUserNameInLegend = true;
}
