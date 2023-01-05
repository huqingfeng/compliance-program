<?php

use hpn\steel\query\SelectQuery;

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));


class Selig2021ComplianceProgram extends ComplianceProgram
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

        $hraScreeningView = new CompleteHRAAndScreeningComplianceView('2021-01-01', '2021-11-30');
        $hraScreeningView->setReportName('Wellness Screening & Assessment');
        $hraScreeningView->setName('complete_hra_screening');
        $hraScreeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $hraScreeningView->setAttribute('requirement', 'Employee completes the wellness screening & health assessment <strong>BEFORE 8/27/21</strong>');
        $hraScreeningView->setAttribute('points_per_activity', '50');
        $hraScreeningView->emptyLinks();
        $hraScreeningView->addLink(new Link('Results', '/compliance/hmi-2016/my-health/content/my-health?tab=screening'));
        $hraScreeningView->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health/content/my-health'));
        $hraScreeningView->setUseOverrideCreatedDate(true);
        $coreGroup->addComplianceView($hraScreeningView);

        $physicalExam = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT, 0);
        $physicalExam->setReportName('Follow Up Physical Exam');
        $physicalExam->setName('physical_exam');
        $physicalExam->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $physicalExam->setAttribute('requirement', 'Follow-up with your medical provider and receive your Annual Physical Exam <strong>BEFORE 11/30/21</strong>. <br>Please note, many providers are able to complete annual physicals via telemedicine at this time. Please contact your provider if you would like to complete this activity via telemedicine');
        $physicalExam->setAttribute('points_per_activity', '50');
        $physicalExam->emptyLinks();
        $physicalExam->setAllowPointsOverride(true);
        $physicalExam->addLink(new Link('Verification Form <br />', '/compliance/hmi-2016/my-rewards/wms1/pdf/clients/selig/Selig_Healthcare_Provider_Form_2021.pdf'));
        $physicalExam->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $coreGroup->addComplianceView($physicalExam);

        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        $spouseScreening = new RelatedUserCompleteComplianceViewsComplianceView($this, array('complete_hra_screening'), array(Relationship::SPOUSE));
        $spouseScreening->setReportName('Health & Wellness Screening for Spouse');
        $spouseScreening->setName('spouse_screening');
        $spouseScreening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $spouseScreening->setAttribute('requirement', 'Eligible Spouse or Domestic Partner, completes the Wellness Screening & Health Assessment <strong>BEFORE 11/30/21</strong>');
        $spouseScreening->setAttribute('points_per_activity', '15');
        $spouseScreening->emptyLinks();
        $wellnessGroup->addComplianceView($spouseScreening);

        $dependentScreening = new RelatedUserCompleteComplianceViewsComplianceView($this, array('complete_hra_screening'), array(Relationship::OTHER_DEPENDENT));
        $dependentScreening->setReportName('Health & Wellness Screening for Dependent 18+');
        $dependentScreening->setName('dependent_screening');
        $dependentScreening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $dependentScreening->setAttribute('requirement', 'Dependent 18 + completes 2021 Wellness Screening and Health Assessment <strong>BEFORE 11/30/21</strong>');
        $dependentScreening->setAttribute('points_per_activity', '15');
        $dependentScreening->emptyLinks();
        $wellnessGroup->addComplianceView($dependentScreening);

        $preventiveServiceView = new PlaceHolderComplianceView(null, 0);
        $preventiveServiceView->setName('preventive_service');
        $preventiveServiceView->setReportName('Preventive Exams Submit proof of completion to HMI via the wellness portal.');
        $preventiveServiceView->setAttribute('requirement', 'Complete a gender and age-appropriate preventive service: Dental, vision, pap smear, mammogram exam, prostate exam, tetanus shot/ immunization, or rectal exam.');
        $preventiveServiceView->setAttribute('points_per_activity', '10');
        $preventiveServiceView->emptyLinks();
        $preventiveServiceView->addLink(new Link('Verification Form <br />', '/compliance/hmi-2016/my-rewards/wms1/pdf/clients/selig/Selig_Healthcare_Provider_Form_2021.pdf'));
        $preventiveServiceView->addLink(new Link('Submit Records', '/compliance/hmi-2016/my-rewards/wms1/content/chp-document-uploader'));
        $preventiveServiceView->setMaximumNumberOfPoints(40);
        $wellnessGroup->addComplianceView($preventiveServiceView);

        $flushot = new PlaceHolderComplianceView(null, 0);
        $flushot->setName('flushot');
        $flushot->setReportName('Flu Shot');
        $flushot->setAttribute('requirement', 'Receive a Flu Shot at the Selig event or via your medical provider');
        $flushot->setAttribute('points_per_activity', '10');
        $flushot->emptyLinks();
        $flushot->setMaximumNumberOfPoints(10);
        $wellnessGroup->addComplianceView($flushot);

        $covidView = new PlaceHolderComplianceView(null, 0);
        $covidView->setName('covid_vaccine');
        $covidView->setReportName('Covid Vaccine');
        $covidView->setAttribute('requirement', 'Receive a COVID-19 Vaccine at a participating pharmacy or healthcare facility.<br>
            You must receive both doses of the COVID-19 vaccine if applicable to earn points');
        $covidView->setAttribute('points_per_activity', '25');
        $covidView->emptyLinks();
        $covidView->addLink(new Link('Verification Form <br />', '/compliance/hmi-2016/my-rewards/wms1/pdf/clients/selig/Selig_Healthcare_Provider_Form_2021.pdf'));
        $covidView->addLink(new Link('Submit Records', '/compliance/hmi-2016/my-rewards/wms1/content/chp-document-uploader'));
        $covidView->setMaximumNumberOfPoints(25);
        $wellnessGroup->addComplianceView($covidView);

        $spousePreventiveServiceView = new PlaceHolderComplianceView(null, 0);
        $spousePreventiveServiceView->setName('spouse_preventive_service');
        $spousePreventiveServiceView->setReportName('Spouse/ Dependent 18+ Preventive Exam Submit proof of completion to HMI via the wellness portal');
        $spousePreventiveServiceView->setAttribute('requirement', 'Complete a gender and age-appropriate preventive service: pap smear, mammogram exam, prostate exam, tetanus shot/ immunization, or rectal exam. Check with your personal physician for necessary tests.');
        $spousePreventiveServiceView->setAttribute('points_per_activity', '5');
        $spousePreventiveServiceView->emptyLinks();
        $spousePreventiveServiceView->addLink(new Link('Verification Form', '/compliance/hmi-2016/my-rewards/wms1/pdf/clients/selig/Selig_Healthcare_Provider_Form_2021.pdf'));
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
        $bcbsView->addLink(new Link('Verification Form', '/compliance/hmi-2016/my-rewards/wms1/pdf/clients/selig/Selig_2021_WellnessEventCert.pdf'));
        $bcbsView->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $bcbsView->setMaximumNumberOfPoints(45);
        $wellnessGroup->addComplianceView($bcbsView);

        $weightLossView = new PlaceHolderComplianceView(null, 0);
        $weightLossView->setName('individual_weight_loss');
        $weightLossView->setReportName('<strong>Individual Weight Loss Program</strong><br /> Submit proof of completion to HMI via the wellness portal.');
        $weightLossView->setAttribute('requirement', 'Complete 12 weeks of an individual weight loss program such as Jenny Craig, Weight Watchers, etc.');
        $weightLossView->setAttribute('points_per_activity', '15');
        $weightLossView->emptyLinks();
        $weightLossView->addLink(new Link('Verification Form', '/compliance/hmi-2016/my-rewards/wms1/pdf/clients/selig/Selig_2021_WellnessEventCert.pdf'));
        $weightLossView->addLink(new Link('Submit Records', '/content/chp-document-uploader'));
        $weightLossView->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($weightLossView);

        $kickOffView = new PlaceHolderComplianceView(null, 0);
        $kickOffView->setName('kick_off');
        $kickOffView->setReportName('<strong>Selig Wellness Events</strong>');
        $kickOffView->setAttribute('requirement', 'Participate in the wellness seminars listed below and earn 10 points for each seminar you attend<br><em>Nutrition for Chronic Disease Prevention<br>Sleep Hygiene<em>');
        $kickOffView->setAttribute('points_per_activity', '10');
        $kickOffView->emptyLinks();
        $kickOffView->addLink(new FakeLink('Admin will enter', '#'));
        $kickOffView->setMaximumNumberOfPoints(20);
        $wellnessGroup->addComplianceView($kickOffView);

        $donateBlood = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 455, 1);
        $donateBlood->setName('volunteering');
        $donateBlood->setReportName('Volunteering');
        $donateBlood->setAttribute('points_per_activity', '1');
        $donateBlood->setAttribute('requirement', 'Earn 1 point for each hour you participate in a volunteer activity.');
        $donateBlood->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($donateBlood);

        $drink = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 75341, 1);
        $drink->setName('drink');
        $drink->setReportName('Drink at least 48 oz of water a day');
        $drink->setAttribute('points_per_activity', '1');
        $drink->setAttribute('requirement', 'Earn 1 point per entry.');
        $drink->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($drink);

        $fruit = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 75342, 1);
        $fruit->setName('fruit');
        $fruit->setReportName('Eat 5 or more servings of fruit/vegetables a day');
        $fruit->setAttribute('points_per_activity', '1');
        $fruit->setAttribute('requirement', 'Earn 1 point per entry.');
        $fruit->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($fruit);

        $sleep = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 131208, 1);
        $sleep->setName('sleep');
        $sleep->setReportName('Achieve 7-9 hours of sleep per night');
        $sleep->setAttribute('points_per_activity', '1');
        $sleep->setAttribute('requirement', 'Earn 1 point per entry.');
        $sleep->setMaximumNumberOfPoints(15);
        $wellnessGroup->addComplianceView($sleep);

        $donateBlood = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 75343, 5);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate Blood');
        $donateBlood->setAttribute('points_per_activity', '5');
        $donateBlood->setAttribute('requirement', 'Earn 5 points each time you donate blood.');
        $donateBlood->setMaximumNumberOfPoints(10);
        $wellnessGroup->addComplianceView($donateBlood);


        $this->addComplianceViewGroup($wellnessGroup);


        $activitiesGroup = new ComplianceViewGroup('wellness_activities', 'Activities');
        $activitiesGroup->setPointsRequiredForCompliance(0);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('<strong>Personal Exercise Activity Tracker</strong><br /><em>Log 150 minutes of activity up to 5 times a month of activity online using the HMI Activity tracker.  Points will automatically be awarded.</em><br>');
        $physicalActivityView->setName('exercise_activity');
        $physicalActivityView->setAttribute('requirement', 'Complete 150 minutes of activity up to 5 times/month.');
        $physicalActivityView->setAttribute('points_per_activity', '1 point per 150 min');
        $physicalActivityView->setMaximumNumberOfPoints(30);
        $physicalActivityView->setMinutesDivisorForPoints(150);
        $physicalActivityView->setMonthlyPointLimit(5);
        $physicalActivityView->emptyLinks();
        $physicalActivityView->addLink(new Link('Enter/Edit Activity', '/content/12048?action=showActivity&amp;activityidentifier=21'));
        $activitiesGroup->addComplianceView($physicalActivityView);

        $walk6k = new PlaceHolderComplianceView();
        $walk6k->setMaximumNumberOfPoints(12);
        $walk6k->setName('walk_6k');
        $walk6k->setReportName('<strong>Individual Activity/Walking Program</strong><br /><em>Points will be earned based on the average steps logged for each month of the Wellness Program.</em><br>');
        $walk6k->setAttribute('requirement', 'Walk an average of 6,000 steps/day');
        $walk6k->setAttribute('points_per_activity', 1);
        $walk6k->addLink(new Link('My Steps <br /><br />', '/compliance/hmi-2016/my-rewards/wms1/content/fitness'));
        $walk6k->addLink(new Link('Fitbit Sync <br />', '/compliance/hmi-2016/my-rewards/wms1/content/fitness'));
        $walk6k->setUseOverrideCreatedDate(true);
        $walk6k->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
                       
            $user_id = $user->id;

            $records = SelectQuery::create()
                ->select('value, activity_date')
                ->from('wms3.fitnessTracking_data ftd')
                ->leftJoin('wms3.fitnessTracking_participants ftp')
                ->on('ftd.participant = ftp.id')
                ->where('ftp.wms1Id = '. $user_id)
                ->andWhere('ftd.type = 1')
                ->andWhere('ftd.status = 1')
                ->andWhere('ftd.activity_date >= "2021-01-01 00:00:00"')
                ->andWhere('ftd.activity_date < "2021-12-01 00:00:00"')
                ->execute()
                ->toArray();
            
            $months = [];
            $points = 0;

            foreach($records as $result) {
                $breakout = explode('-', $result['activity_date']);
                if (!isset($months[$breakout[1]])) $months[$breakout[1]] = 0;
                $months[$breakout[1]] += $result['value'];
            }

            foreach($months as $month => $value) {
                $days = cal_days_in_month(CAL_GREGORIAN, $month, 2021);

                if (($value / $days) >= 6000) $points++;
            }

            $status->setPoints($points);
        });
        $activitiesGroup->addComplianceView($walk6k);
        $walk8k = new PlaceHolderComplianceView();
        $walk8k->setMaximumNumberOfPoints(12);
        $walk8k->setName('walk_8k');
        $walk8k->setReportName('Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('requirement', 'Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('points_per_activity', 'Additional 1 point');
        $walk8k->setAttribute('report_name_link', '/content/1094#2lWalk');
        $walk8k->setUseOverrideCreatedDate(true);
        $walk8k->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
                       
            $user_id = $user->id;

            $records = SelectQuery::create()
                ->select('value, activity_date')
                ->from('wms3.fitnessTracking_data ftd')
                ->leftJoin('wms3.fitnessTracking_participants ftp')
                ->on('ftd.participant = ftp.id')
                ->where('ftp.wms1Id = '. $user_id)
                ->andWhere('ftd.type = 1')
                ->andWhere('ftd.status = 1')
                ->andWhere('ftd.activity_date >= "2021-01-01 00:00:00"')
                ->andWhere('ftd.activity_date < "2021-12-01 00:00:00"')
                ->execute()
                ->toArray();
            
            $months = [];
            $points = 0;

            foreach($records as $result) {
                $breakout = explode('-', $result['activity_date']);
                if (!isset($months[$breakout[1]])) $months[$breakout[1]] = 0;
                $months[$breakout[1]] += $result['value'];
            }

            foreach($months as $month => $value) {
                $days = cal_days_in_month(CAL_GREGORIAN, $month, 2021);

                if (($value / $days) >= 8000) $points++;
            }

            $status->setPoints($points);
        });
        $activitiesGroup->addComplianceView($walk8k);

        $walk10k = new PlaceHolderComplianceView();
        $walk10k->setMaximumNumberOfPoints(12);
        $walk10k->setName('walk_10k');
        $walk10k->setReportName('Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('requirement', 'Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('points_per_activity', 'Additional 1 point');
        $walk10k->setAttribute('report_name_link', '/content/1094#2lWalk');
        $walk10k->setUseOverrideCreatedDate(true);
        $walk10k->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
                       
            $user_id = $user->id;

            $records = SelectQuery::create()
                ->select('value, activity_date')
                ->from('wms3.fitnessTracking_data ftd')
                ->leftJoin('wms3.fitnessTracking_participants ftp')
                ->on('ftd.participant = ftp.id')
                ->where('ftp.wms1Id = '. $user_id)
                ->andWhere('ftd.type = 1')
                ->andWhere('ftd.status = 1')
                ->andWhere('ftd.activity_date >= "2021-01-01 00:00:00"')
                ->andWhere('ftd.activity_date < "2021-12-01 00:00:00"')
                ->execute()
                ->toArray();
            
            $months = [];
            $points = 0;

            foreach($records as $result) {
                $breakout = explode('-', $result['activity_date']);
                if (!isset($months[$breakout[1]])) $months[$breakout[1]] = 0;
                $months[$breakout[1]] += $result['value'];
            }

            foreach($months as $month => $value) {
                $days = cal_days_in_month(CAL_GREGORIAN, $month, 2021);

                if (($value / $days) >= 10000) $points++;
            }

            $status->setPoints($points);
        });
        $activitiesGroup->addComplianceView($walk10k);

        $runRaceView = new PlaceHolderComplianceView(null, 0);
        $runRaceView->setReportName('<strong>Run / Walk a Race</strong><br /><em>Submit proof of participation such as registration form, race results, or bib number to HMI via the wellness portal.</em><br>');
        $runRaceView->setName('run_race');
        $runRaceView->setAttribute('requirement', 'Participate in a walk/run (1 pt. per/k) Example: 5k = 5 points');
        $runRaceView->setAttribute('points_per_activity', '1');
        $runRaceView->setMaximumNumberOfPoints(30);
        $runRaceView->addLink(new Link('Verification form', '/compliance/hmi-2016/my-rewards/wms1/pdf/clients/selig/Selig_2021_WellnessEventCert.pdf'));
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
        $challengeOne->setReportName('<strong>Activity A: Selig Fitness Challenge #1</strong><br /><em>Dates TBD</em><br />');
        $challengeOne->setAttribute('requirement', 'Rules and point values will be outlined in the challenge flyer.');
        $challengeOne->setAttribute('points_per_activity', '15');
        $challengeOne->setMaximumNumberOfPoints(100);
        $walkingGroup->addComplianceView($challengeOne);

        $challengeTwo = new PlaceHolderComplianceView(null, 0);
        $challengeTwo->setName('challenge_two');
        $challengeTwo->setReportName('<strong>Activity B: Selig Fitness Challenge #2</strong><br /><em>Dates TBD</em><br />');
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
            $printer = new Selig2021ComplianceProgramReportPrinter();
        }

        return $printer;
    }
}


class Selig2021ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
        $covidStatus = $wellnessGroupStatus->getComplianceViewStatus('covid_vaccine');
        $spousePreventiveServiceStatus = $wellnessGroupStatus->getComplianceViewStatus('spouse_preventive_service');
        $elearning = $wellnessGroupStatus->getComplianceViewStatus('elearning');
        $bcbs = $wellnessGroupStatus->getComplianceViewStatus('bcbs');
        $weightLoss = $wellnessGroupStatus->getComplianceViewStatus('individual_weight_loss');
        $kickOff = $wellnessGroupStatus->getComplianceViewStatus('kick_off');
        $volunteering = $wellnessGroupStatus->getComplianceViewStatus('volunteering');
        $drink = $wellnessGroupStatus->getComplianceViewStatus('drink');
        $fruit = $wellnessGroupStatus->getComplianceViewStatus('fruit');
        $sleep = $wellnessGroupStatus->getComplianceViewStatus('sleep');
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

            #wms1 {
                font-family: "Roboto";
                font-size: 16px;
            }

            #wms1 p {
                margin: 20px 0 20px;
            }

            .pageHeading {
                display:none;
            }

            #altPageHeading {
                font-weight:bold;
                margin-bottom:20px;
                text-align:center;
            }

            .phipTable .headerRow {
                background-color:#455A64;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
                text-transform: uppercase;
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

            .phipTable .links a {
                display:inline-block;
                margin:0 3px;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .section {
                padding-top: 20px !important;
                padding-bottom: 20px!important;
            }

            .requirement {
                width: 350px;
            }

            #programTable, .phipTable {
                font-weight: 400;
                letter-spacing: 1px;
                border-radius: 4px;
                border-top: none;
                border-collapse: unset;
                overflow: hidden;
                margin-bottom: 20px;
            }

            #programTable tr th, .phipTable tr th {
                text-transform: uppercase;
                font-weight: 400;
                padding: 5px 10px;
                vertical-align: top;
            }

            #programTable tr th span {
                text-transform: none;
                font-size: 14px;
                line-height: 20px;
                display: inline-block;
                color: #eee; 
            }

            #programTable tr td, .phipTable tr td {
                border-bottom: 1px solid #B0BEC5;
                border-left: 1px solid #B0BEC5;
                border-right: 1px solid #B0BEC5;
                padding: 10px;
                font-size: 15px;
            }

            .phipTable tr.headerRow td  {
                border: none;
                line-height: 20px;
            }

            .phipTable tr td {
                font-size: 14px;
            }

            #programTable .centerBorder, .phipTable .centerBorder {
                border-left: none;
                border-right: none;
            }

            #programTable .leftCorner, .phipTable .leftCorner {
                border-bottom-left-radius: 4px;
            }

            #programTable .rightCorner, .phipTable .rightCorner {
                border-bottom-right-radius: 4px;
            }

            .phipTable .headerRow .leftCorner {
                border-top-left-radius: 4px;
                border-bottom-left-radius: 0px;
            }

            .phipTable .headerRow .rightCorner {
                border-top-right-radius: 4px;
                border-bottom-right-radius: 0px;
            }

            .main_header {
                text-align:center; 
                color: white; 
                background-color:#A5D14D; 
                font-size:16pt;
                border-radius: 4px;
            }

            .greenBorder {
                border-left: 4px solid #4CAF50 !important;
            }

            .redBorder {
                border-left: 4px solid #D32F2F !important;
            }

            .phipTable tr td.lightBorder {
                border-bottom: 1px solid #ECEFF1;
            }

            tr:not(.headerRow) td:last-of-type:not(.override) {
                font-size: 14px;
            }

            tr:not(.headerRow) .center {
                font-size: 15px;
            }

        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned
        </script>
        <!-- Text atop report card-->
        <p>
            <div style="width: 150px; float: right; margin-right:20px;">
                <img src="https://static.hpn.com/resources/9337/selig_logo.jpg" style="width: 100px;" />
            </div>
        </p> <br />

        <p style="clear: both">Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Selig's wellness program will be tracked by our Partner, HMI (Health Maintenance Institute) <a href="http://www.myhmihealth.com/">http://www.myhmihealth.com/</a>.   HMI is a HIPAA-compliant, biometric screening vendor that will maintain the confidentiality of your personal health information.  There is no need to worry that your personal health information will be seen by anyone within the Selig organization, as this program will be housed electronically by HMI.</p>

        <p>
            <strong>HOW DOES THE PROGRAM WORK?</strong> To participate in the Selig Group Wellness Program, employees are required to complete the spring 2021 Health Screening & Assessment.  Participation in the screening and other wellness activities (outlined here) will earn points that will be tracked through the HMI website.
        </p>

        <p>Participants will first register an account on the HMI site: <a href="http://www.myhmihealth.com">www.myhmihealth.com</a> using the site code: <strong>SELIG</strong>.  Once you have enrolled on the HMI site you will be able to complete the required health questionnaire.</p>

        <p>
            <strong>You must complete the Health Screening by August 27 AND complete the Follow-Up Physical Exam by November 30, 2021. Anyone hired after August 1, 2021 will have 30 days to complete the health screening.</strong>
        </p>

        <div>
            <style>
                #programTable td { line-height: 18px; }
                #programTable td ul li { padding: 0; margin: 0; margin-bottom: 8px;  }
                #programTable td ul { padding: 30px; margin: 0; }
            </style>
            <table id="programTable">
                <tr style="background-color:#455A64; color: #fff">
                    <th class="center" style="width: 260px; border-top-left-radius: 4px;">Required Steps/Activities</th>
                    <th class="center">Incentive <br><span style="text-align: left;">To earn each incentive, the steps must be completed in order</span></th>
                    <th class="center" style="width: 240px;">Incentive Deadlines</th>
                </tr>
                <tr>
                    <td><strong>Step 1:</strong><br>Complete Health Screening<br><span style="font-style: italic; color:#90A4AE;">(Deadline to complete Health Screening is August 27, 2021)</span></td>
                    <td class="centerBorder">$200 Lump Sum</td>
                    <td class="center"><strong>July 31, 2021</strong></td>
                </tr>
                <tr>
                    <td><strong>Step 2:</strong><br>Complete a Follow-Up Physical Exam</td>
                    <td class="centerBorder">Additional $200 Lump Sum</td>
                    <td class="center"><strong>July 31, 2021</strong><br>OR<br><strong>November 30, 2021</strong></td>
                </tr>
                <tr>
                    <td class="leftCorner"><strong>Step 3:</strong><br>Earn a total of 150 Points in the Wellness Program</td>
                    <td class="centerBorder">Additional $200 Lump Sum</td>
                    <td class="rightCorner center"><strong>July 31, 2021</strong><br>OR<br><strong>November 30, 2021</strong></td>
                </tr>
            </table>
            <p>
                Selig's HR Group will monitor your participation in the Wellness Program throughout the year. The amount of the lump-sum payment received after July 31 or November 30 will depend on the activities completed as of July 31, 2021 and November 30, 2021. Payments are made twice per year, after the deadline in which they were completed.
            </p>
            <p>
                Claire O'Donnell (<a href="mailto:codonnell@assuranceagency.com">codonnell@assuranceagency.com</a>) will update the points on the 15th and 30th of each month.
            </p>
            <p style="color: #D32F2F; padding: 10px 20px; border-radius: 4px; background: #f5f5f5;">
                <u>**REASONABLE ALTERNATIVE:</u>  If it is unreasonably difficult due to a medical condition for you to achieve
                the standards for the reward under this program, call HMI at 847-635-6580 and we will work with you to develop another way to qualify for the reward.
            </p>
        </div><br />

        <table class="phipTable">
            <tbody>
                <tr><th colspan="6" class="main_header">2021 Wellness Rewards Program</th></tr>
                <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Required Activities</span></th></tr>
                <tr class="headerRow headerRow-core">
                    <td class="center leftCorner" style="width: 400px;" colspan="2">Program Requirements</td>
                    <td class="center">Status</td>
                    <td class="center">Points / Activity</td>
                    <td class="center">Points Earned</td>
                    <td class="center rightCorner">Tracking Method</td>
                </tr>
                <tr class="view-complete_hra_screening">
                    <td style="width: 400px;" colspan="2">
                        <strong>A. <?php echo $completeScreeningStatus->getComplianceView()->getReportName() ?></strong><br>
                        <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?>
                    </td>
                    <?php if ($completeScreeningStatus->isCompliant()): ?>
                        <td class="center centerBorder greenBorder">
                            <i class="far fa-check" style="color: #4CAF50; font-size: 26pt;"></i>
                        </td>
                        <?php else: ?>
                            <td class="center centerBorder redBorder">
                                <i class="far fa-times" style="color: #D32F2F; font-size: 26pt;"></i>
                            </td>
                        <?php endif;?>
                        <td class="center">
                            <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('points_per_activity') ?>
                        </td>
                        <td class="centerBorder center">
                            <?php echo $completeScreeningStatus->getPoints() ?>
                        </td>
                        <td class="center">
                            <?php foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>
                    <tr class="view-physical_exam">
                        <td style="width: 400px;" colspan="2" class="leftCorner">
                            <strong>B. <?php echo $physicalExamStatus->getComplianceView()->getReportName() ?></strong><br>
                            <?php echo $physicalExamStatus->getComplianceView()->getAttribute('requirement') ?>
                        </td>
                        <?php if ($physicalExamStatus->isCompliant()): ?>
                            <td class="center centerBorder greenBorder">
                                <i class="far fa-check" style="color: #4CAF50; font-size: 26pt;"></i>
                            </td>
                            <?php else: ?>
                                <td class="center centerBorder redBorder">
                                    <i class="far fa-times" style="color: #D32F2F; font-size: 26pt;"></i>
                                </td>
                            <?php endif;?>
                            <td class="center">
                                <?php echo $physicalExamStatus->getComplianceView()->getAttribute('points_per_activity') ?>
                            </td>
                            <td class="centerBorder center">
                                <?php echo $physicalExamStatus->getPoints() ?>
                            </td>
                            <td class="center rightCorner">
                                <?php foreach($physicalExamStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Annual / Self-Care Wellness Activities</span></th></tr>

                        <tr class="headerRow headerRow-wellness_programs">
                            <td class="center leftCorner" colspan="2">Activity Requirements</td>
                            <td class="center">Points / Activity</td>
                            <td class="center">Max Points</td>
                            <td class="center">Points Earned</td>
                            <td class="center rightCorner">Tracking Method</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <strong>A. <?php echo $spouseScrStatus->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $spouseScrStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $spouseScrStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $spouseScrStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $spouseScrStatus->getPoints() ?></td>
                            <td class="center" rowspan="2">
                                <?php foreach($spouseScrStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <strong>B. <?php echo $dependentScrStatus->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $dependentScrStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $dependentScrStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $dependentScrStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder override"><?php echo $dependentScrStatus->getPoints() ?></td>
                        </tr>

                        <tr>
                            <td colspan="2" class="lightBorder">
                                <strong>C</strong>. <strong>Preventive Exams</strong><br />
                                <em>Submit proof of completion to HMI via the wellness portal.</em><br><br>
                                <?php echo $preventiveServiceStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder lightBorder"><?php echo $preventiveServiceStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center lightBorder"><?php echo $preventiveServiceStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder lightBorder"><?php echo $preventiveServiceStatus->getPoints() ?></td>
                            <td class="center" rowspan="2">
                                <?php foreach($preventiveServiceStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?><br /><br /><br />

                                Sign in at Selig Event
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <?php echo $flushotStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $flushotStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $flushotStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder override"><?php echo $flushotStatus->getPoints() ?></td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>D. Receive the COVID-19 Vaccine</strong><br />
                                <?php echo $covidStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $covidStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $covidStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $covidStatus->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($covidStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>E. Spouse/ Dependent 18+ Preventive Exam</strong><br />
                                <em>Submit proof of completion to HMI via the wellness portal.</em><br><br>
                                <?php echo $spousePreventiveServiceStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $spousePreventiveServiceStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $spousePreventiveServiceStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $spousePreventiveServiceStatus->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($spousePreventiveServiceStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>F. <?php echo $elearning->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $elearning->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $elearning->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $elearning->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $elearning->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($elearning->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>G. <?php echo $bcbs->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $bcbs->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $bcbs->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $bcbs->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $bcbs->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($bcbs->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>H. <?php echo $weightLoss->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $weightLoss->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $weightLoss->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $weightLoss->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $weightLoss->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($weightLoss->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>I. <?php echo $kickOff->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $kickOff->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $kickOff->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $kickOff->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $kickOff->getPoints() ?></td>
                            <td class="center">
                                Sign in at Event
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>J. <?php echo $volunteering->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $volunteering->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $volunteering->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $volunteering->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $volunteering->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($volunteering->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>K. <?php echo $drink->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $drink->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $drink->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $drink->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $drink->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($drink->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>L. <?php echo $fruit->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $fruit->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $fruit->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $fruit->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $fruit->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($fruit->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2" class="leftCorner">
                                <strong>M. <?php echo $sleep->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $sleep->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $sleep->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $sleep->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $sleep->getPoints() ?></td>
                            <td class="center rightCorner">
                                <?php foreach($sleep->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2" class="leftCorner">
                                <strong>N. <?php echo $donateBlood->getComplianceView()->getReportName() ?></strong><br>
                                <?php echo $donateBlood->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $donateBlood->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $donateBlood->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $donateBlood->getPoints() ?></td>
                            <td class="center rightCorner">
                                <?php foreach($donateBlood->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Physical Wellness Activities</span></th></tr>

                        <tr class="headerRow headerRow-wellness_programs">
                            <td class="center leftCorner" colspan="2">Activity Requirements</td>
                            <td class="center">Points / Activity</td>
                            <td class="center">Max Points</td>
                            <td class="center">Points Earned</td>
                            <td class="center rightCorner">Tracking Method</td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <strong>A.</strong> <?php echo $exerciseActivityStatus->getComplianceView()->getReportName() ?><br>
                                <?php echo $exerciseActivityStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $exerciseActivityStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $exerciseActivityStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder"><?php echo $exerciseActivityStatus->getPoints() ?></td>
                            <td class="center">
                                <?php foreach($exerciseActivityStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2" class="lightBorder">
                                <strong>B.</strong> <?php echo $walk6kStatus->getComplianceView()->getReportName() ?><br>
                                <?php echo $walk6kStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder lightBorder"><?php echo $walk6kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center lightBorder"><?php echo $walk6kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder lightBorder"><?php echo $walk6kStatus->getPoints() ?></td>
                            <td class="center" rowspan="3">
                                <?php foreach($walk6kStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2" class="lightBorder"><?php echo $walk8kStatus->getComplianceView()->getAttribute('requirement') ?></td>
                            <td class="center centerBorder lightBorder"><?php echo $walk8kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center lightBorder"><?php echo $walk8kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder override lightBorder"><?php echo $walk8kStatus->getPoints() ?></td>
                        </tr>

                        <tr>

                            <td colspan="2"><?php echo $walk10kStatus->getComplianceView()->getAttribute('requirement') ?></td>
                            <td class="center centerBorder"><?php echo $walk10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $walk10kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder override"><?php echo $walk10kStatus->getPoints() ?></td>
                        </tr>

                        <tr>
                            <td colspan="2" class="lightBorder">
                                <strong>C</strong>. <?php echo $runRaceStatus->getComplianceView()->getReportName() ?><br>
                                <?php echo $runRaceStatus->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder lightBorder"><?php echo $runRaceStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center lightBorder"><?php echo $runRaceStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder lightBorder"><?php echo $runRaceStatus->getPoints() ?></td>
                            <td class="center rightCorner" rowspan="3">
                                <?php foreach($runRaceStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2" class="lightBorder"><?php echo $halfMarathonStatus->getComplianceView()->getAttribute('requirement') ?></td>
                            <td class="center centerBorder lightBorder"><?php echo $halfMarathonStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center lightBorder"><?php echo $halfMarathonStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder override lightBorder"><?php echo $halfMarathonStatus->getPoints() ?></td>
                        </tr>

                        <tr>
                            <td colspan="2" class="leftCorner"><?php echo $marathonStatus->getComplianceView()->getAttribute('requirement') ?></td>
                            <td class="center centerBorder"><?php echo $marathonStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center"><?php echo $marathonStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="center centerBorder override"><?php echo $marathonStatus->getPoints() ?></td>
                        </tr>

                        <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Selig Fitness Program: 2 Challenges This Year - See Dates Below</span></th></tr>

                        <tr class="headerRow headerRow-wellness_programs">
                            <td class="center leftCorner" colspan="2">Activity Requirements</td>
                            <td class="center">Points / Activity</td>
                            <td class="center">Max Points</td>
                            <td class="center">Points Earned</td>
                            <td class="center rightCorner">Tracking Method</td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <?php echo $challengeOne->getComplianceView()->getReportName() ?><br>
                                <?php echo $challengeOne->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $challengeOne->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center">Refer to Challenge Flyer</td>
                            <td class="center centerBorder"><?php echo $challengeOne->getPoints() ?></td>
                            <td class="center rightCorner" rowspan="2">Admin will enter points</td>
                        </tr>

                        <tr>
                            <td colspan="2" class="leftCorner">
                                <?php echo $challengeTwo->getComplianceView()->getReportName() ?><br>
                                <?php echo $challengeTwo->getComplianceView()->getAttribute('requirement') ?>
                            </td>
                            <td class="center centerBorder"><?php echo $challengeTwo->getComplianceView()->getAttribute('points_per_activity') ?></td>
                            <td class="center">Refer to Challenge Flyer</td>
                            <td class="center centerBorder override"><?php echo $challengeTwo->getPoints() ?></td>
                        </tr>

                    </tbody>
                </table>

                <?php
            }


            public $showUserNameInLegend = true;
        }
