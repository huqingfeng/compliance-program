<?php

use hpn\steel\query\SelectQuery;

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));


class Selig2020ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('03/01/2020 - 11/30/2020 - Points', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $program = $this->cloneForEvaluation('2020-03-01', '2020-11-30');

            $program->setActiveUser($user);

            $evaluateProgramStatus = $program->getStatus();

            return $evaluateProgramStatus->getPoints();
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Program');
//        $coreGroup->setPointsRequiredForCompliance(50);

        $hraScreeningView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScreeningView->setReportName('Wellness Screening & Assessment');
        $hraScreeningView->setName('complete_hra_screening');
        $hraScreeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $hraScreeningView->setAttribute('requirement', 'Employee completes the wellness screening & health assessment <strong>BEFORE 11/30/20</strong>');
        $hraScreeningView->setAttribute('points_per_activity', '50');
        $hraScreeningView->emptyLinks();
        $hraScreeningView->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $hraScreeningView->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $hraScreeningView->setUseOverrideCreatedDate(true);
        $coreGroup->addComplianceView($hraScreeningView);

        $physicalExam = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT, 0);
        $physicalExam->setReportName('Follow Up Physical Exam');
        $physicalExam->setName('physical_exam');
        $physicalExam->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $physicalExam->setAttribute('requirement', 'Follow-up with your medical provider and receive your Annual Physical Exam <strong>BEFORE 11/30/20</strong>. <br>Please note, many providers are able to complete annual physicals via telemedicine at this time. Please contact your provider if you would like to complete this activity via telemedicine');
        $physicalExam->setAttribute('points_per_activity', '50');
        $physicalExam->emptyLinks();
        $physicalExam->setAllowPointsOverride(true);
        $physicalExam->addLink(new Link('Verification Form <br />', '/resources/10556/Selig_Healthcare_Provider_Form_2020.pdf'));
        $physicalExam->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $coreGroup->addComplianceView($physicalExam);

        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        $spouseScreening = new RelatedUserCompleteComplianceViewsComplianceView($this, array('complete_hra_screening'), array(Relationship::SPOUSE));
        $spouseScreening->setReportName('Health & Wellness Screening for Spouse');
        $spouseScreening->setName('spouse_screening');
        $spouseScreening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $spouseScreening->setAttribute('requirement', 'Eligible Spouse or Domestic Partner, completes the Wellness Screening & Health Assessment <strong>BEFORE 11/30/20</strong>');
        $spouseScreening->setAttribute('points_per_activity', '15');
        $spouseScreening->emptyLinks();
        $wellnessGroup->addComplianceView($spouseScreening);

        $dependentScreening = new RelatedUserCompleteComplianceViewsComplianceView($this, array('complete_hra_screening'), array(Relationship::OTHER_DEPENDENT));
        $dependentScreening->setReportName('Health & Wellness Screening for Dependent 18+');
        $dependentScreening->setName('dependent_screening');
        $dependentScreening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $dependentScreening->setAttribute('requirement', 'Dependent 18 + completes 2020 Wellness Screening and Health Assessment <strong>BEFORE 11/30/20</strong>');
        $dependentScreening->setAttribute('points_per_activity', '15');
        $dependentScreening->emptyLinks();
        $wellnessGroup->addComplianceView($dependentScreening);

        $preventiveServiceView = new PlaceHolderComplianceView(null, 0);
        $preventiveServiceView->setName('preventive_service');
        $preventiveServiceView->setReportName('Preventive Exams Submit proof of completion to HMI via the wellness portal.');
        $preventiveServiceView->setAttribute('requirement', 'Complete a gender and age-appropriate preventive service: Dental, vision, pap smear, mammogram exam, prostate exam, tetanus shot/ immunization, or rectal exam.');
        $preventiveServiceView->setAttribute('points_per_activity', '10');
        $preventiveServiceView->emptyLinks();
        $preventiveServiceView->addLink(new Link('Verification Form <br />', '/resources/10556/Selig_Healthcare_Provider_Form_2020.pdf'));
        $preventiveServiceView->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $preventiveServiceView->setMaximumNumberOfPoints(40);
        $wellnessGroup->addComplianceView($preventiveServiceView);

        $flushotView = new PlaceHolderComplianceView(null, 0);
        $flushotView->setName('flushot');
        $flushotView->setReportName('Flu Shot');
        $flushotView->setAttribute('requirement', 'Receive a Flu Shot at the Selig event or via your medical provider');
        $flushotView->setAttribute('points_per_activity', '10');
        $flushotView->emptyLinks();
        $flushotView->setMaximumNumberOfPoints(10);
        $wellnessGroup->addComplianceView($flushotView);

        $spousePreventiveServiceView = new PlaceHolderComplianceView(null, 0);
        $spousePreventiveServiceView->setName('spouse_preventive_service');
        $spousePreventiveServiceView->setReportName('Spouse/ Dependent 18+ Preventive Exam Submit proof of completion to HMI via the wellness portal');
        $spousePreventiveServiceView->setAttribute('requirement', 'Complete a gender and age-appropriate preventive service: pap smear, mammogram exam, prostate exam, tetanus shot/ immunization, or rectal exam. Check with your personal physician for necessary tests.');
        $spousePreventiveServiceView->setAttribute('points_per_activity', '5');
        $spousePreventiveServiceView->emptyLinks();
        $spousePreventiveServiceView->addLink(new Link('Verification Form', '/resources/10556/Selig_Healthcare_Provider_Form_2020.pdf'));
        $spousePreventiveServiceView->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $spousePreventiveServiceView->setMaximumNumberOfPoints(20);
        $wellnessGroup->addComplianceView($spousePreventiveServiceView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('eLearning');
        $elearn->setName('elearning');
        $elearn->setAttribute('requirement', 'Complete an online eLearning course');
        $elearn->setAttribute('points_per_activity', '5');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(20);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('eLearning Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $elearn->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($elearn);

        $bcbsView = new PlaceHolderComplianceView(null, 0);
        $bcbsView->setName('bcbs');
        $bcbsView->setReportName('<strong>BCBS OnMyTime Online Course</strong><br /> Smoking Cessation / Stress Management / Nutrition / Weight Management.');
        $bcbsView->setAttribute('requirement', 'Complete a 12 module course through BCBS Well On Target');
        $bcbsView->setAttribute('points_per_activity', '15');
        $bcbsView->emptyLinks();
        $bcbsView->addLink(new Link('www.wellontarget.com', 'http://www.wellontarget.com'));
        $bcbsView->addLink(new Link('Verification Form', '/resources/10557/Selig_2020_WellnessEventCert.pdf'));
        $bcbsView->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $bcbsView->setMaximumNumberOfPoints(45);
        $wellnessGroup->addComplianceView($bcbsView);

        $weightLossView = new PlaceHolderComplianceView(null, 0);
        $weightLossView->setName('individual_weight_loss');
        $weightLossView->setReportName('<strong>Individual Weight Loss Program</strong><br /> Submit proof of completion to HMI via the wellness portal.');
        $weightLossView->setAttribute('requirement', 'Complete 12 weeks of an individual weight loss program such as Jenny Craig, Weight Watchers, etc.');
        $weightLossView->setAttribute('points_per_activity', '15');
        $weightLossView->emptyLinks();
        $weightLossView->addLink(new Link('Verification Form', '/resources/10557/Selig_2020_WellnessEventCert.pdf'));
        $weightLossView->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $weightLossView->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($weightLossView);

        $kickOffView = new PlaceHolderComplianceView(null, 0);
        $kickOffView->setName('kick_off');
        $kickOffView->setReportName('<strong>Other Selig Wellness Events</strong>');
        $kickOffView->setAttribute('requirement', 'Participate in a designated wellness activity and earn the specified # of points (TBD). ');
        $kickOffView->setAttribute('points_per_activity', '10');
        $kickOffView->emptyLinks();
        $kickOffView->addLink(new FakeLink('Admin will enter', '#'));
        $kickOffView->setMaximumNumberOfPoints(50);
        $wellnessGroup->addComplianceView($kickOffView);

        $donateBlood = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 455, 1);
        $donateBlood->setName('volunteering');
        $donateBlood->setReportName('Volunteering');
        $donateBlood->setAttribute('points_per_activity', '1');
        $donateBlood->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($donateBlood);

        $drink = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 75341, 1);
        $drink->setName('drink');
        $drink->setReportName('Drink at least 48 oz of water a day');
        $drink->setAttribute('points_per_activity', '1');
        $drink->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($drink);

        $fruit = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 75342, 1);
        $fruit->setName('fruit');
        $fruit->setReportName('Eat 5 or more servings of fruit/vegetables a day');
        $fruit->setAttribute('points_per_activity', '1');
        $fruit->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($fruit);

        $donateBlood = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 75343, 5);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate Blood');
        $donateBlood->setAttribute('points_per_activity', '5');
        $donateBlood->setMaximumNumberOfPoints(10);
        $wellnessGroup->addComplianceView($donateBlood);


        $this->addComplianceViewGroup($wellnessGroup);


        $activitiesGroup = new ComplianceViewGroup('wellness_activities', 'Activities');
        $activitiesGroup->setPointsRequiredForCompliance(0);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('<strong>Personal Exercise Activity Tracker</strong><br /><br /> Log 150 minutes of activity up to 5 times a month of activity online using the HMI Activity tracker.  Points will automatically be awarded.');
        $physicalActivityView->setName('exercise_activity');
        $physicalActivityView->setAttribute('requirement', 'Complete 150 minutes of activity up to 5 times/month.');
        $physicalActivityView->setAttribute('points_per_activity', '1 point per 150 min');
        $physicalActivityView->setMaximumNumberOfPoints(30);
        $physicalActivityView->setMinutesDivisorForPoints(150);
        $physicalActivityView->setMonthlyPointLimit(5);
        $physicalActivityView->emptyLinks();
        $physicalActivityView->addLink(new Link('Enter/Edit Activity', '/content/12048?action=showActivity&amp;activityidentifier=21'));
        $activitiesGroup->addComplianceView($physicalActivityView);

        $walk6k = new HmiMultipleAverageStepsComplianceView(6000, 1);
        $walk6k->setMaximumNumberOfPoints(12);
        $walk6k->setName('walk_6k');
        $walk6k->setReportName('<strong>Individual Activity/Walking Program</strong><br /><br /> Points will be earned based on the average steps logged for each month of the Wellness Program.');
        $walk6k->setAttribute('requirement', 'Walk an average of 6,000 steps/day');
        $walk6k->setAttribute('points_per_activity', 1);
        $walk6k->addLink(new Link('My Steps <br /><br />', '/content/ucan-fitbit-individual'));
        $walk6k->addLink(new Link('Fitbit Sync <br />', '/content/ucan-fitbit-individual'));
        $walk6k->setUseOverrideCreatedDate(true);
        $activitiesGroup->addComplianceView($walk6k);

        $walk8k = new HmiMultipleAverageStepsComplianceView(8000, 1);
        $walk8k->setMaximumNumberOfPoints(12);
        $walk8k->setName('walk_8k');
        $walk8k->setReportName('Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('requirement', 'Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('points_per_activity', 'Additional 1 point');
        $walk8k->setAttribute('report_name_link', '/content/1094#2lWalk');
        $walk8k->setUseOverrideCreatedDate(true);
        $activitiesGroup->addComplianceView($walk8k);

        $walk10k = new HmiMultipleAverageStepsComplianceView(10000, 1);
        $walk10k->setMaximumNumberOfPoints(12);
        $walk10k->setName('walk_10k');
        $walk10k->setReportName('Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('requirement', 'Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('points_per_activity', 'Additional 1 point');
        $walk10k->setAttribute('report_name_link', '/content/1094#2lWalk');
        $walk10k->setUseOverrideCreatedDate(true);
        $activitiesGroup->addComplianceView($walk10k);


        foreach($this->ranges as $name => $dates) {
            $walk6k->addDateRange($dates[0], $dates[1]);
            $walk8k->addDateRange($dates[0], $dates[1]);
            $walk10k->addDateRange($dates[0], $dates[1]);
        }

        $runRaceView = new PlaceHolderComplianceView(null, 0);
        $runRaceView->setReportName('<strong>Run / Walk a Race</strong><br /><br /> Submit proof of participation such as registration form, race results, or bib number to HMI via the wellness portal.');
        $runRaceView->setName('run_race');
        $runRaceView->setAttribute('requirement', 'Participate in a walk/run (1 pt. per/k) Example: 5k = 5 points');
        $runRaceView->setAttribute('points_per_activity', '1');
        $runRaceView->setMaximumNumberOfPoints(30);
        $runRaceView->addLink(new Link('Verification form', '/resources/10557/Selig_2020_WellnessEventCert.pdf'));
        $runRaceView->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $activitiesGroup->addComplianceView($runRaceView);

        $halfMarathonView = new PlaceHolderComplianceView(null, 0);
        $halfMarathonView->setName('half_marathon');
        $halfMarathonView->setAttribute('requirement', 'Participate in a half-marathon, Sprint distance triathlon, or Bike Tour (25-50 miles)');
        $halfMarathonView->setAttribute('points_per_activity', '15');
        $halfMarathonView->setMaximumNumberOfPoints(15);
        $activitiesGroup->addComplianceView($halfMarathonView);

        $marathonView = new PlaceHolderComplianceView(null, 0);
        $marathonView->setName('marathon');
        $marathonView->setAttribute('requirement', 'Participate in a marathon or Olympic distance triathlon, or Bike Tour (51-100 miles)');
        $marathonView->setAttribute('points_per_activity', '30');
        $marathonView->setMaximumNumberOfPoints(30);
        $activitiesGroup->addComplianceView($marathonView);

        $this->addComplianceViewGroup($activitiesGroup);


        $walkingGroup = new ComplianceViewGroup('walking_program', 'walking');
        $walkingGroup->setPointsRequiredForCompliance(0);

        $challengeOne = new PlaceHolderComplianceView(null, 0);
        $challengeOne->setName('challenge_one');
        $challengeOne->setReportName('<strong>Activity A: Selig Fitness Challenge #1</strong><br /><br /> Monday, May 4 - Sunday, June 7');
        $challengeOne->setAttribute('requirement', 'Rules and point values will be outlined in the challenge flyer.');
        $challengeOne->setAttribute('points_per_activity', '15');
        $challengeOne->setMaximumNumberOfPoints(100);
        $walkingGroup->addComplianceView($challengeOne);

        $challengeTwo = new PlaceHolderComplianceView(null, 0);
        $challengeTwo->setName('challenge_two');
        $challengeTwo->setReportName('<strong>Activity B: Selig Fitness Challenge #2</strong><br /><br /> Monday, Aug 3 - Sunday, Sept 6');
        $challengeTwo->setAttribute('requirement', 'Rules and point values will be outlined in the challenge flyer.');
        $challengeTwo->setAttribute('points_per_activity', '15');
        $challengeTwo->setMaximumNumberOfPoints(100);
        $walkingGroup->addComplianceView($challengeTwo);

        $this->addComplianceViewGroup($walkingGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new ScreeningProgramReportPrinter();

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
            $printer = new Selig2020ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    private $ranges = array(
        'mar' => array('2020-03-01', '2020-03-31'),
        'apr' => array('2020-04-01', '2020-04-30'),
        'may' => array('2020-05-01', '2020-05-31'),
        'jun' => array('2020-06-01', '2020-06-30'),
        'jul' => array('2020-07-01', '2020-07-31'),
        'aug' => array('2020-08-01', '2020-08-31'),
        'sep' => array('2020-09-01', '2020-09-30'),
        'oct' => array('2020-10-01', '2020-10-31'),
        'nov' => array('2020-11-01', '2020-11-30')
    );

}


class Selig2020ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra_screening');
        $physicalExamStatus = $coreGroupStatus->getComplianceViewStatus('physical_exam');

        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness_programs');
        $wellnessGroup = $wellnessGroupStatus->getComplianceViewGroup();
        $spouseScrStatus = $wellnessGroupStatus->getComplianceViewStatus('spouse_screening');
        $dependentScrStatus = $wellnessGroupStatus->getComplianceViewStatus('dependent_screening');
        $preventiveServiceStatus = $wellnessGroupStatus->getComplianceViewStatus('preventive_service');
        $flushotStatus = $wellnessGroupStatus->getComplianceViewStatus('flushot');
        $spousePreventiveServiceStatus = $wellnessGroupStatus->getComplianceViewStatus('spouse_preventive_service');
        $elearning = $wellnessGroupStatus->getComplianceViewStatus('elearning');
        $bcbs = $wellnessGroupStatus->getComplianceViewStatus('bcbs');
        $weightLoss = $wellnessGroupStatus->getComplianceViewStatus('individual_weight_loss');
        $kickOff = $wellnessGroupStatus->getComplianceViewStatus('kick_off');
        $volunteering = $wellnessGroupStatus->getComplianceViewStatus('volunteering');
        $drink = $wellnessGroupStatus->getComplianceViewStatus('drink');
        $fruit = $wellnessGroupStatus->getComplianceViewStatus('fruit');
        $donateBlood = $wellnessGroupStatus->getComplianceViewStatus('donate_blood');

        $walkingActivitiesStatus = $status->getComplianceViewGroupStatus('wellness_activities');
        $exerciseActivityStatus = $walkingActivitiesStatus->getComplianceViewStatus('exercise_activity');
        $walk6kStatus = $walkingActivitiesStatus->getComplianceViewStatus('walk_6k');
        $walk8kStatus = $walkingActivitiesStatus->getComplianceViewStatus('walk_8k');
        $walk10kStatus = $walkingActivitiesStatus->getComplianceViewStatus('walk_10k');
        $runRaceStatus = $walkingActivitiesStatus->getComplianceViewStatus('run_race');
        $halfMarathonStatus = $walkingActivitiesStatus->getComplianceViewStatus('half_marathon');
        $marathonStatus = $walkingActivitiesStatus->getComplianceViewStatus('marathon');

        $walkingProgramStatus = $status->getComplianceViewGroupStatus('walking_program');
        $challengeOne = $walkingProgramStatus->getComplianceViewStatus('challenge_one');
        $challengeTwo = $walkingProgramStatus->getComplianceViewStatus('challenge_two');


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
                border:1px solid #222;
                text-align: center;
            }

        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned
        </script>
        <!-- Text atop report card-->
        <p>
            <div style="width: 35%; float: left">
                <img src="/resources/9337/selig_logo.jpg" style="width: 150px;" />
            </div>

            <div style="text-align: center; width: 62%; float: right; border: 1px solid #000000;">
                <div style="padding: 20px 10px;">
                    <span style="font-size: 12pt; font-weight: bold; color: #6e9de5">2020 Wellness Program Calendar</span> <br />
                    <span>Your participation will qualify you for a
                    lump-sum payment of up to $600! </span>
                </div>
            </div>
        </p> <br />

        <p style="clear: both">Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Selig's wellness program will be tracked by our Partner, HMI (Health Maintenance Institute) <a href="http://www.myhmihealth.com/">http://www.myhmihealth.com/</a>.   HMI is a HIPAA-compliant, biometric screening vendor that will maintain the confidentiality of your personal health information.  There is no need to worry that your personal health information will be seen by anyone within the Selig organization, as this program will be housed electronically by HMI.</p>

        <p>
            <strong>HOW DOES THE PROGRAM WORK?</strong> To participate in the Selig Group Wellness Program, employees are required to complete the spring 2020 Health Screening & Assessment.  Participation in the screening and other wellness activities (outlined here) will earn points that will be tracked through the HMI website.
        </p>

        <p>Participants will first register an account on the HMI site: <a href="http://www.myhmihealth.com">www.myhmihealth.com</a> using the site code: <strong>SELIG</strong>.  Once you have enrolled on the HMI site you will be able to complete the required health questionnaire.</p>

        <p>
            <strong>You must complete the Health Screening by November 30, 2020 AND complete the Follow-Up Physical Exam by November 30, 2020 to earn an incentive.</strong>
        </p>

        <div>
        <style>
        #programTable td { line-height: 18px; }
        #programTable td ul li { padding: 0; margin: 0; margin-bottom: 8px;  }
        #programTable td ul { padding: 30px; margin: 0; }
        </style>
            <table id="programTable">
                <tr style="background-color:#0070C0; color: #fff">
                    <th style="width: 200px;">Incentive Deadlines</th>
                    <th>Complete Health Screening &amp; Follow-Up Physical Exam (100 points total)</th>
                    <th>Complete Health Screening  &amp; Follow-Up Physical Exam AND Earn a total of  150 Points in the Wellness Program</th>
                </tr>
                <tr>
                    <td>July 31, 2020/August 31, 2020</td>
                    <td><ul><li>$300 Lump Sum Payment</li></ul></td>
                    <td><ul><li>$600 Lump Sum Payment</li></ul></td>
                </tr>
                <tr>
                    <td>November 30, 2020</td>
                    <td><ul><li>$300 Lump Sum Payment (if not earned in July)</li></ul></td>
                    <td>
                    <ul><li>$600 Lump Sum Payment ($300 if a $300 lump sum payment was earned in July)</li>
                        <li>Raffle Entry for a $500 Amazon Gift Card</li></ul>
                     </td>
                </tr>
            </table>
            <p>
            Selig's HR Group will monitor your participation in the Wellness Program throughout the year.  The amount of 
            the lump-sum payment received in August/Sept and/or December will depend on the number of points earned as of July 31, 2020 / August 31, 2020 and November 30, 2020.
            </p>
            <p>
                Claire O'Donnell (<a href="mailto:codonnell@assuranceagency.com">codonnell@assuranceagency.com</a>) will update the points on the 15th and 30th of each month.
            </p>
            <p style="color: red;">
                <u>**REASONABLE ALTERNATIVE:</u>  If it is unreasonably difficult due to a medical condition for you to achieve
                the standards for the reward under this program, call HMI at 847-635-6580 and we will work with you to develop another way to qualify for the reward.
            </p>
        </div><br />

        <table class="phipTable">
            <tbody>
            <tr><th colspan="6" style="height:36px; text-align:center; color: white; background-color:#436EEE; font-size:11pt">2020 Wellness Rewards Program</th></tr>
            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Required Activities</span></th></tr>
            <tr class="headerRow headerRow-core">
                <th>Program</th>
                <th class="center">Requirement</th>
                <th class="center">Status</th>
                <th class="center">Points per Activity</th>
                <th class="center">Points Earned</th>
                <th colspan="3" class="center">Tracking Method</th>
            </tr>
            <tr class="view-complete_hra_screening">
                <td>
                    <strong>A</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getReportName() ?>
                </td>
                <td>
                    <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
                </td>
                <td>
                    <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('points_per_activity') ?>
                </td>
                <td>
                    <?php echo $completeScreeningStatus->getPoints() ?>
                </td>
                <td colspan="3" class="center">
                    <?php foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr class="view-physical_exam">
                <td>
                    <strong>B</strong>. <?php echo $physicalExamStatus->getComplianceView()->getReportName() ?>
                </td>
                <td>
                    <?php echo $physicalExamStatus->getComplianceView()->getAttribute('requirement') ?>
                </td>
                <td class="center">
                    <img src="<?php echo $physicalExamStatus->getLight(); ?>" class="light"/>
                </td>
                <td>
                    <?php echo $physicalExamStatus->getComplianceView()->getAttribute('points_per_activity') ?>
                </td>
                <td>
                    <?php echo $physicalExamStatus->getPoints() ?>
                </td>
                <td colspan="3" class="center">
                    <?php foreach($physicalExamStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Annual / Self-Care Wellness Activities</span></th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Max Points</td>
                <td class="center">Points Earned</td>
                <td class="center">Tracking Method</td>
            </tr>
            <tr>
                <td>
                    <strong>A</strong>. <?php echo $spouseScrStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $spouseScrStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $spouseScrStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $spouseScrStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $spouseScrStatus->getPoints() ?></td>
                <td class="center" rowspan="2">
                    <?php foreach($spouseScrStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>B</strong>. <?php echo $dependentScrStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $dependentScrStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $dependentScrStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $dependentScrStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $dependentScrStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td  rowspan="2">
                    <strong>C</strong>. <strong>Preventive Exams</strong> <br /><br />
                    Submit proof of completion to HMI via the wellness portal.
                </td>
                <td class="requirement"><?php echo $preventiveServiceStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $preventiveServiceStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $preventiveServiceStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $preventiveServiceStatus->getPoints() ?></td>
                <td class="center" rowspan="2">
                    <?php foreach($preventiveServiceStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?><br /><br /><br />

                    Sign in at Selig Event
                </td>
            </tr>

            <tr>
                <td class="requirement"><?php echo $flushotStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $flushotStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $flushotStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $flushotStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td>
                    <strong>D</strong>. <strong>Spouse/ Dependent 18+ Preventive Exam</strong> <br /><br />
                    Submit proof of completion to HMI via the wellness portal.
                </td>
                <td class="requirement"><?php echo $spousePreventiveServiceStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $spousePreventiveServiceStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $spousePreventiveServiceStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $spousePreventiveServiceStatus->getPoints() ?></td>
                <td class="center">
                    <?php foreach($spousePreventiveServiceStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>


            <tr>
                <td>
                    <strong>E</strong>. <?php echo $elearning->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $elearning->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $elearning->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $elearning->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $elearning->getPoints() ?></td>
                <td class="center">
                    <?php foreach($elearning->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>F</strong>. <?php echo $bcbs->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $bcbs->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $bcbs->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bcbs->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bcbs->getPoints() ?></td>
                <td class="center">
                    <?php foreach($bcbs->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>G</strong>. <?php echo $weightLoss->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $weightLoss->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $weightLoss->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $weightLoss->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $weightLoss->getPoints() ?></td>
                <td class="center">
                    <?php foreach($weightLoss->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>H</strong>. <?php echo $kickOff->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $kickOff->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $kickOff->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $kickOff->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $kickOff->getPoints() ?></td>
                <td class="center">
                    Sign in at Event
                </td>
            </tr>

            <tr>
                <td>
                    <strong>I</strong>. <?php echo $volunteering->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $volunteering->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $volunteering->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $volunteering->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $volunteering->getPoints() ?></td>
                <td class="center">
                    <?php foreach($volunteering->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>J</strong>. <?php echo $drink->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $drink->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $drink->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $drink->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $drink->getPoints() ?></td>
                <td class="center">
                    <?php foreach($drink->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>K</strong>. <?php echo $fruit->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $fruit->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $fruit->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $fruit->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $fruit->getPoints() ?></td>
                <td class="center">
                    <?php foreach($fruit->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>L</strong>. <?php echo $donateBlood->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $donateBlood->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $donateBlood->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $donateBlood->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $donateBlood->getPoints() ?></td>
                <td class="center">
                    <?php foreach($donateBlood->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Physical Wellness Activities</span></th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Max Points</td>
                <td class="center">Points Earned</td>
                <td class="center">Tracking Method</td>
            </tr>

            <tr>
                <td>
                    <strong>A</strong>. <?php echo $exerciseActivityStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $exerciseActivityStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $exerciseActivityStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $exerciseActivityStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $exerciseActivityStatus->getPoints() ?></td>
                <td class="center">
                    <?php foreach($exerciseActivityStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td rowspan="3">
                    <strong>B</strong>. <?php echo $walk6kStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $walk6kStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $walk6kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk6kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk6kStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <?php foreach($walk6kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement"><?php echo $walk8kStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $walk8kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk8kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk8kStatus->getPoints() ?></td>
            </tr>

            <tr>

                <td class="requirement"><?php echo $walk10kStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $walk10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk10kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk10kStatus->getPoints() ?></td>
            </tr>

            <tr>
                <td rowspan="3">
                    <strong>C</strong>. <?php echo $runRaceStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $runRaceStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $runRaceStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $runRaceStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $runRaceStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <?php foreach($runRaceStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement"><?php echo $halfMarathonStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $halfMarathonStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $halfMarathonStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $halfMarathonStatus->getPoints() ?></td>
            </tr>

            <tr>

                <td class="requirement"><?php echo $marathonStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $marathonStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $marathonStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $marathonStatus->getPoints() ?></td>
            </tr>

            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Selig Fitness Program: 2 Challenges This Year - See Dates Below</span></th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Max Points</td>
                <td class="center">Points Earned</td>
                <td class="center">Tracking Method</td>
            </tr>

            <tr>
                <td>
                    <?php echo $challengeOne->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $challengeOne->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $challengeOne->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center">Refer to Challenge Flyer</td>
                <td class="center"><?php echo $challengeOne->getPoints() ?></td>
                <td class="center" rowspan="2">Admin will enter points</td>
            </tr>

            <tr>
                <td>
                    <?php echo $challengeTwo->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $challengeTwo->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $challengeTwo->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center">Refer to Challenge Flyer</td>
                <td class="center"><?php echo $challengeTwo->getPoints() ?></td>
            </tr>

            </tbody>
        </table>

        <?php
    }


    public $showUserNameInLegend = true;
}
