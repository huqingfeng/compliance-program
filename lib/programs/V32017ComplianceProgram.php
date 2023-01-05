<?php

class V32017ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new V32017ComplianceProgramReportPrinter();

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $that = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);

        $printer->addCallbackField('walk_team_first_quarter', function(User $user) {
            return $user->getGroupValueFromTypeName('First Quarter Walking Challenge');
        });

        foreach($this->summaryRanges as $name => $dates) {
            $printer->addStatusFieldCallback($name, function(ComplianceProgramStatus $status) use($name) {
                return $status->getComplianceViewStatus('walk_10k')->getAttribute($name);
            });
        }


        $printer->addStatusFieldCallback('Steps 03/01/2017 - 03/31/2017', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2017-03-01', '2017-03-31');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });

        $printer->addStatusFieldCallback('Steps 05/01/2017 - 06/30/2017', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2017-05-01', '2017-06-30');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });

        $printer->addStatusFieldCallback('Steps 09/01/2017 - 09/30/2017', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2017-09-01', '2017-09-30');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });

        $printer->addStatusFieldCallback('Steps 12/01/2017 - 12/15/2017', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2017-12-01', '2017-12-15');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });

        return $printer;
    }

    public function loadGroups()
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $reqGroup = new ComplianceViewGroup('required', 'Required Activities');


        $hra = new CompleteHRAComplianceView('2016-12-21', $endDate);
        $hra->setReportName('Employee completes the Health Power Assessment');
        $hra->setAttribute('report_name_link', '/content/1094#1aHPA');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $hra->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $hra->setUseOverrideCreatedDate(true);
        $reqGroup->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($startDate, $endDate);
        $scr->setReportName('Employee participates in the 2017 Wellness Screening');
        $scr->setAttribute('report_name_link', '/content/1094#1bAnnScreen');
        $scr->emptyLinks();
        $scr->addLink(new Link('Sign-Up', '/compliance/hmi-2016/schedule/content/wms2-appointment-center'));
        $scr->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $scr->setUseOverrideCreatedDate(true);
        $reqGroup->addComplianceView($scr);

        $this->addComplianceViewGroup($reqGroup);


        $actGroup = new ComplianceViewGroup('activities', 'Annual / Self-Care Wellness Activities');
        $actGroup->setPointsRequiredForCompliance(0);

        $physicianInfo = new PlaceHolderComplianceView(null, 0);
        $physicianInfo->setMaximumNumberOfPoints(5);
        $physicianInfo->setName('physician_info');
        $physicianInfo->setReportName('Provide personal physician info at time of Wellness screening');
        $physicianInfo->addLink(new FakeLink('Admin will enter', '#'));
        $physicianInfo->setStatusSummary(ComplianceStatus::COMPLIANT, 'Provide Physicians info to HMI');
        $physicianInfo->setAttribute('points_per_activity', 5);
        $physicianInfo->setAttribute('report_name_link', '/content/1094#2aPhysInfo');
        $actGroup->addComplianceView($physicianInfo);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setReportName('Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $glucoseView->setAttribute('points_per_activity', 5);
        $glucoseView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Glucose < 100 mg/dL');
        $glucoseView->addLink(new Link('Results', '/content/989'));
        $actGroup->addComplianceView($glucoseView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $trigView->setReportName('Triglycerides');
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $trigView->setAttribute('points_per_activity', 5);
        $trigView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $trigView->overrideTestRowData(null, null, 149.999, null);
        $trigView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Triglycerides < 150 mg dl');
        $actGroup->addComplianceView($trigView);

        $cholesterolHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($startDate, $endDate);
        $cholesterolHDLRatioView->setReportName('Total HDL Ratio');
        $cholesterolHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $cholesterolHDLRatioView->setAttribute('points_per_activity', 5);
        $cholesterolHDLRatioView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $cholesterolHDLRatioView->overrideTestRowData(null, null, 4.969, null);
        $cholesterolHDLRatioView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Total Cholesterol to HDL Ratio < 4.97');
        $actGroup->addComplianceView($cholesterolHDLRatioView);

        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $endDate);
        $tcView->setReportName('Total Cholesterol');
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $tcView->setAttribute('points_per_activity', 5);
        $tcView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Total Cholesterol < 200 mg/dL');
        $actGroup->addComplianceView($tcView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $ldlView->setReportName('LDL Cholesterol');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $ldlView->setAttribute('points_per_activity', 5);
        $ldlView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $ldlView->overrideTestRowData(null, null, 99.999, null);
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, 'LDL Cholesterol < 100 mg/dL');
        $actGroup->addComplianceView($ldlView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $bloodPressureView->setAttribute('points_per_activity', 5);
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, 89);
        $bloodPressureView->setStatusSummary(ComplianceStatus::COMPLIANT, 'BP < 130/85 mmHg');
        $bloodPressureView->setMergeScreenings(true);
        $bloodPressureView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $actGroup->addComplianceView($bloodPressureView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($startDate, $endDate);
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $bmiView->setReportName('BMI');
        $bmiView->setAttribute('points_per_activity', 5);
        $bmiView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $bmiView->overrideTestRowData(null, null, 27.499, null);
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, 'BMI < 27.5');
        $bmiView->setUseHraFallback(true);
        $actGroup->addComplianceView($bmiView);

        $phyExam = new PlaceHolderComplianceView(null, 0);
        $phyExam->setMaximumNumberOfPoints(15);
        $phyExam->setName('phy_exam');
        $phyExam->setReportName('Annual Physical Exam & Screening follow up');
        $phyExam->addLink(new Link('Verification form', 'https://static.hpn.com/wms2/documents/clients/v3/V3_2017_PreventiveCare_Cert.pdf'));
        $phyExam->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $phyExam->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam');
        $phyExam->setAttribute('points_per_activity', 15);
        $phyExam->setAttribute('report_name_link', '/content/1094#2cAnnPhys');
        $actGroup->addComplianceView($phyExam);

        $prevServFlu = new PlaceHolderComplianceView(null, 0);
        $prevServFlu->setMaximumNumberOfPoints(15);
        $prevServFlu->setName('prev_serv_flu');
        $prevServFlu->setReportName('Preventative Services such as vaccines/flu shot');
        $prevServFlu->addLink(new Link('Verification form', 'https://static.hpn.com/wms2/documents/clients/v3/V3_2017_PreventiveCare_Cert.pdf'));
        $prevServFlu->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $prevServFlu->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventive service such as vaccines, eye & dental exams, etc.');
        $prevServFlu->setAttribute('points_per_activity', 5);
        $prevServFlu->setAttribute('report_name_link', '/content/1094#2dPrevServ');
        $prevServFlu->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($prevServFlu);

        $prevServMammogram = new PlaceHolderComplianceView(null, 0);
        $prevServMammogram->setMaximumNumberOfPoints(30);
        $prevServMammogram->setName('prev_serv_mammogram');
        $prevServMammogram->setReportName('Preventative Services such as mammogram, prostate exam');
        $prevServMammogram->addLink(new Link('Verification form', 'https://static.hpn.com/wms2/documents/clients/v3/V3_2017_PreventiveCare_Cert.pdf'));
        $prevServMammogram->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $prevServMammogram->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventive service such as mammogram, prostate exam, colonoscopy, etc.');
        $prevServMammogram->setAttribute('points_per_activity', 10);
        $prevServMammogram->setAttribute('report_name_link', '/content/1094#2dPrevServ');
        $prevServMammogram->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($prevServMammogram);

        $fluShot = new PlaceHolderComplianceView(null, 0);
        $fluShot->setMaximumNumberOfPoints(5);
        $fluShot->setName('donate_blood');
        $fluShot->setReportName('Receive a flu shot');
        $fluShot->addLink(new Link('Verification form', 'https://static.hpn.com/wms2/documents/clients/v3/V3_2017_PreventiveCare_Cert.pdf'));
        $fluShot->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $fluShot->setStatusSummary(ComplianceStatus::COMPLIANT, 'Donate Blood or Get a Flu Shot');
        $fluShot->setAttribute('points_per_activity', 1);
        $fluShot->setAttribute('report_name_link', '/content/1094#2dFluShot');
        $fluShot->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($fluShot);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(25);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new Link('Verification form', 'https://static.hpn.com/wms2/documents/clients/v3/V3_2017_WellnessEventCert.pdf'));
        $smoking->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $smoking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a Smoking Cessation Course offered by the IL Quitline<br /><a href="http://www.quityes.org" >www.quityes.org</a>');
        $smoking->setAttribute('points_per_activity', 25);
        $smoking->setAttribute('report_name_link', '/content/1094#2eSmokingCessg');
        $smoking->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($smoking);

        $ineligibleLessonIDs = array(1270,1255,1287,1257,614,595,661,1011,639,616,178,180,1156,1012,1026,166,
            167,165,539,226,573,664,1118,1274,176,184,662,181,649,924,420,467,187,99,
            418,468,419,465,1178,655,688,910,660,1207,708,37,38,178,20,1118,1284,1190,
            696,1191,198,700,88,1258,1204,1194,574,599,600,436,280,1218,195,381,1308,1200,
            1266,725,596,638,597,663,703,647,649,1254,516,670,634,610,452,611,713,790,29,
            1091,239,1023,641,598,528,52,1032,567,623,624,263,399,1264,190,1322,753,706,
            629,914,656,469,736,1117);

        $lessons = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, null, $ineligibleLessonIDs, 2);
        $lessons->setAttribute('points_per_activity', 2);
        $lessons->setReportName('eLearning Lessons');
        $lessons->setName('elearning_lesson');
        $lessons->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete eLearning lessons');
        $lessons->setMaximumNumberOfPoints(8);
        $lessons->setAttribute('report_name_link', '/content/1094#2feLearn');
        $lessons->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($lessons);

        $kickOff = new PlaceHolderComplianceView(null, 0);
        $kickOff->setMaximumNumberOfPoints(2);
        $kickOff->setName('kickoff');
        $kickOff->setReportName('Wellness Kick off');
        $kickOff->addLink(new FakeLink('Admin will enter', '#'));
        $kickOff->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend the wellness kick off meeting');
        $kickOff->setAttribute('points_per_activity', 2);
        $kickOff->setAttribute('report_name_link', '/content/1094#2gKickoff');
        $kickOff->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($kickOff);

        $nutProgram = new PlaceHolderComplianceView(null, 0);
        $nutProgram->setMaximumNumberOfPoints(25);
        $nutProgram->setName('nut_program');
        $nutProgram->setReportName('Health/Nutrition Program Annual Membership');
        $nutProgram->addLink(new Link('Verification Form <br />', 'https://static.hpn.com/wms2/documents/clients/v3/V3_2017_WellnessEventCert.pdf'));
        $nutProgram->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $nutProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'Membership (13 weeks) in health program such as Weight Watchers, Seattle Sutton, Jenny Craig, etc.');
        $nutProgram->setAttribute('points_per_activity', 25);
        $nutProgram->setAttribute('report_name_link', '/content/1094#2hNutr');
        $nutProgram->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($nutProgram);

        $physAct = new PhysicalActivityComplianceView($startDate, $endDate);
        $physAct->setMaximumNumberOfPoints(50);
        $physAct->setMinutesDivisorForPoints(150);
        $physAct->setAttribute('points_per_activity', '1 point/ 150 minutes');
        $physAct->setReportName('Activity Tracking');
        $physAct->setStatusSummary(ComplianceStatus::COMPLIANT, 'Track a minimum of 150 minutes of activity/week on the HMI website');
        $physAct->setAttribute('report_name_link', '/content/1094#2iFitness');
        $physAct->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($physAct);

        $fruitExchange = new PlaceHolderComplianceView(null, 0);
        $fruitExchange->setMaximumNumberOfPoints(5);
        $fruitExchange->setName('fruit_exchange');
        $fruitExchange->setReportName('Fruit/Veggie Exchange');
        $fruitExchange->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in the Fruit/Veggie Exchange');
        $fruitExchange->setAttribute('points_per_activity', 1);
        $fruitExchange->addLink(new Link('Food Exchange Form', '/resources/5261/Revitalize-Food Exchange Poster_rev.pdf'));
        $fruitExchange->setAttribute('report_name_link', '/content/1094#2jFruitVeggie');
        $fruitExchange->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($fruitExchange);

        $otherActivities = new PlaceHolderComplianceView(null, 0);
        $otherActivities->setMaximumNumberOfPoints(100);
        $otherActivities->setName('other_activities');
        $otherActivities->setReportName('Other Revitalize Activities');
        $otherActivities->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a designated activity and earn the specified number of points (TBD)');
        $otherActivities->setAttribute('points_per_activity', 'TBD');
        $otherActivities->addLink(new FakeLink('Admin will enter', '#'));
        $otherActivities->setAttribute('report_name_link', '/content/1094#2kAthlEvent');
        $otherActivities->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($otherActivities);

        $walkRun = new PlaceHolderComplianceView(null, 0);
        $walkRun->setMaximumNumberOfPoints(40);
        $walkRun->setName('walk_run');
        $walkRun->setReportName('Participate in a walk/run (1 pt. per/k). Example 5k = 5 points');
        $walkRun->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in walk/run (1 pt. per/k)');
        $walkRun->setAttribute('points_per_activity', 1);
        $walkRun->addLink(new Link('Verification Form <br />', 'https://static.hpn.com/wms2/documents/clients/v3/V3_2017_WellnessEventCert.pdf'));
        $walkRun->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $walkRun->setAttribute('report_name_link', '/content/1094#2kAthlEvent');
        $walkRun->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($walkRun);


        $halfMar = new PlaceHolderComplianceView(null, 0);
        $halfMar->setMaximumNumberOfPoints(20);
        $halfMar->setName('half_mar');
        $halfMar->setReportName('Participate in half-marathon, Sprint distance triathlon, or Bike Tour (25-50 miles)');
        $halfMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a half-marathon, Sprint distance triathlon, or Bike Tour (25-50 miles)');
        $halfMar->setAttribute('points_per_activity', 20);
        $halfMar->setAttribute('report_name_link', '/content/1094#2kAthlEvent');
        $halfMar->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($halfMar);

        $fullMar = new PlaceHolderComplianceView(null, 0);
        $fullMar->setMaximumNumberOfPoints(30);
        $fullMar->setName('full_mar');
        $fullMar->setReportName('Participate in marathon or Olympic distance triathlon, or Bike Tour (51-100 miles)');
        $fullMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a marathon or Olympic distance triathlon, or Bike Tour (51-100 miles)');
        $fullMar->setAttribute('points_per_activity', 30);
        $fullMar->setAttribute('report_name_link', '/content/1094#2kAthlEvents');
        $fullMar->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($fullMar);


        $bikeUnder5 = new PlaceHolderComplianceView(null, 0);
        $bikeUnder5->setMaximumNumberOfPoints(4);
        $bikeUnder5->setName('bike_under_5');
        $bikeUnder5->setReportName('Log a bike trip under 5 miles');
        $bikeUnder5->setStatusSummary(ComplianceStatus::COMPLIANT, 'Log a bike trip under 5 miles');
        $bikeUnder5->setAttribute('points_per_activity', '1 points/ trip');
        $bikeUnder5->setAttribute('report_name_link', '/content/1094#2kAthlEvent');
        $bikeUnder5->addLink(new Link('Verification Form', 'https://static.hpn.com/wms2/documents/clients/v3/V3_2017_WellnessEventCert.pdf'));
        $bikeUnder5->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $bikeUnder5->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($bikeUnder5);

        $bike10 = new PlaceHolderComplianceView(null, 0);
        $bike10->setMaximumNumberOfPoints(8);
        $bike10->setName('bike_10');
        $bike10->setReportName('Log a bike trip of 5-10 miles');
        $bike10->setStatusSummary(ComplianceStatus::COMPLIANT, 'Log a bike trip of 5-10 miles');
        $bike10->setAttribute('points_per_activity', 'Additional 2 points / trip');
        $bike10->setAttribute('report_name_link', '/content/1094#2kAthlEvent');
        $bike10->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($bike10);

        $bikeAbove10 = new PlaceHolderComplianceView(null, 0);
        $bikeAbove10->setMaximumNumberOfPoints(8);
        $bikeAbove10->setName('bike_above_10');
        $bikeAbove10->setReportName('Log a bike trip > 10 miles');
        $bikeAbove10->setStatusSummary(ComplianceStatus::COMPLIANT, 'Log a bike trip > 10 miless');
        $bikeAbove10->setAttribute('points_per_activity', 'Additional 2 points / trip');
        $bikeAbove10->setAttribute('report_name_link', '/content/1094#2kAthlEvent');
        $bikeAbove10->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($bikeAbove10);

        $walk6k = new HmiMultipleAverageStepsComplianceView(6000, 1);
        $walk6k->setMaximumNumberOfPoints(12);
        $walk6k->setName('walk_6k');
        $walk6k->setReportName('Walk an average of 6,000 steps/day');
        $walk6k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 6,000 steps/day');
        $walk6k->setAttribute('points_per_activity', 1);
        $walk6k->setAttribute('report_name_link', '/content/1094#2lWalk');
        $walk6k->setUseJawbone(true);
        $walk6k->setUseMoves(true);
        $walk6k->addLink(new Link('Fitbit Sync <br /><br />', '/content/ucan-fitbit-individual'));
        $walk6k->addLink(new Link('Moves Sync <br /><br />', '/standalone/moves'));
        $walk6k->addLink(new Link('Jawbone Sync <br />', '/standalone/jawbone'));
        $walk6k->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($walk6k);

        $walk8k = new HmiMultipleAverageStepsComplianceView(8000, 2);
        $walk8k->setMaximumNumberOfPoints(24);
        $walk8k->setName('walk_8k');
        $walk8k->setReportName('Walk an average of 8,000 steps/day');
        $walk8k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('points_per_activity', 2);
        $walk8k->setAttribute('report_name_link', '/content/1094#2lWalk');
        $walk8k->setUseJawbone(true);
        $walk8k->setUseMoves(true);
        $walk8k->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($walk8k);

        $walk10k = new HmiMultipleAverageStepsComplianceView(10000, 2);
        $walk10k->setMaximumNumberOfPoints(24);
        $walk10k->setName('walk_10k');
        $walk10k->setReportName('Walk an average of 10,000 steps/day');
        $walk10k->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('points_per_activity', 2);
        $walk10k->setAttribute('report_name_link', '/content/1094#2lWalk');
        $walk10k->setUseJawbone(true);
        $walk10k->setUseMoves(true);
        $walk10k->setUseOverrideCreatedDate(true);
        $actGroup->addComplianceView($walk10k);

        foreach($this->summaryRanges as $dates) {
            $walk6k->addDateRange($dates[0], $dates[1]);
            $walk8k->addDateRange($dates[0], $dates[1]);
            $walk10k->addDateRange($dates[0], $dates[1]);
        }

        foreach($this->summaryRanges as $name => $dates) {
            $walk6k->addSummaryDateRange($name, $dates[0], $dates[1]);
            $walk8k->addSummaryDateRange($name, $dates[0], $dates[1]);
            $walk10k->addSummaryDateRange($name, $dates[0], $dates[1]);
        }

        $this->addComplianceViewGroup($actGroup);

        $quarterGroup = new ComplianceViewGroup('quarterly', 'Information');
        $quarterGroup->setPointsRequiredForCompliance(0);

        $biggestLoserQuarter1 = new PlaceHolderComplianceView(null, 0);
        $biggestLoserQuarter1->setMaximumNumberOfPoints(10);
        $biggestLoserQuarter1->setName('biggest_loser_contest_quarter1');
        $biggestLoserQuarter1->setReportName('Weight Loss Challenge');
        $biggestLoserQuarter1->setStatusSummary(ComplianceStatus::COMPLIANT, 'Weight Loss Challenge');
        $biggestLoserQuarter1->setAttribute('points_per_activity', 10);
        $biggestLoserQuarter1->setAttribute('report_name_link', '/content/1094#4awtloss');
        $biggestLoserQuarter1->addLink(new FakeLink('Admin will enter', '#'));
        $biggestLoserQuarter1->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($biggestLoserQuarter1);

        $walkingChallengeQuarter1 = new PlaceHolderComplianceView(null, 0);
        $walkingChallengeQuarter1->setMaximumNumberOfPoints(10);
        $walkingChallengeQuarter1->setName('team_walking_challenge_quarter1');
        $walkingChallengeQuarter1->setReportName('Quarterly Walking Challenge Q1');
        $walkingChallengeQuarter1->setStatusSummary(ComplianceStatus::COMPLIANT, '-	Quarterly Walking Challenge');
        $walkingChallengeQuarter1->setAttribute('points_per_activity', 10);
        $walkingChallengeQuarter1->setAttribute('report_name_link', '/content/1094#3aWeightLoss');
        $walkingChallengeQuarter1->addLink(new FakeLink('Admin will enter', '#'));
        $walkingChallengeQuarter1->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($walkingChallengeQuarter1);

        $brownBagAttendanceQuarter1  = new PlaceHolderComplianceView(null, 0);
        $brownBagAttendanceQuarter1->setMaximumNumberOfPoints(2);
        $brownBagAttendanceQuarter1->setName('bag_attendance_quarter1');
        $brownBagAttendanceQuarter1->setReportName('Brown Bag Attendance Q1');
        $brownBagAttendanceQuarter1->setStatusSummary(ComplianceStatus::COMPLIANT, '-	Brown Bag Attendance');
        $brownBagAttendanceQuarter1->setAttribute('points_per_activity', 2);
        $brownBagAttendanceQuarter1->setAttribute('report_name_link', '/content/1094#3bActivity');
        $brownBagAttendanceQuarter1->addLink(new FakeLink('Admin will enter', '#'));
        $brownBagAttendanceQuarter1->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($brownBagAttendanceQuarter1);

        $weightElearningQuarter1  = new CompleteELearningGroupSet($startDate, $endDate, 'weight_management');
        $weightElearningQuarter1->setMaximumNumberOfPoints(2);
        $weightElearningQuarter1->setPointsPerLesson(2);
        $weightElearningQuarter1->useAlternateCode(true);
        $weightElearningQuarter1->setName('weight_elearning_quarter1');
        $weightElearningQuarter1->setReportName('Weight Management eLearning (bonus)');
        $weightElearningQuarter1->setStatusSummary(ComplianceStatus::COMPLIANT, '- Weight Management eLearning (bonus)');
        $weightElearningQuarter1->setAttribute('points_per_activity', 2);
        $weightElearningQuarter1->useAlternateCode(true);
        $weightElearningQuarter1->setUseOverrideCreatedDate(true);
        $weightElearningQuarter1->setAttribute('report_name_link', '/content/1094#3cNutrition');

        $quarterGroup->addComplianceView($weightElearningQuarter1);

        $topicQuizQuarter1  = new PlaceHolderComplianceView(null, 0);
        $topicQuizQuarter1->setMaximumNumberOfPoints(1);
        $topicQuizQuarter1->setName('quarter_topic_quiz_quarter1');
        $topicQuizQuarter1->setReportName('Quarterly Topic Quiz 1');
        $topicQuizQuarter1->setStatusSummary(ComplianceStatus::COMPLIANT, '- Quarterly Topic Quiz');
        $topicQuizQuarter1->setAttribute('points_per_activity', 1);
        $topicQuizQuarter1->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $topicQuizQuarter1->addLink(new FakeLink('Admin will enter', '#'));
        $topicQuizQuarter1->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($topicQuizQuarter1);

        $corporateChallengeQuarter2  = new PlaceHolderComplianceView(null, 0);
        $corporateChallengeQuarter2->setMaximumNumberOfPoints(12);
        $corporateChallengeQuarter2->setName('v3_challenge_quarter2');
        $corporateChallengeQuarter2->setReportName('V3 Corporate Challenge');
        $corporateChallengeQuarter2->setStatusSummary(ComplianceStatus::COMPLIANT, '- V3 Corporate Challenge');
        $corporateChallengeQuarter2->setAttribute('points_per_activity', 12);
        $corporateChallengeQuarter2->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $corporateChallengeQuarter2->addLink(new FakeLink('Admin will enter', '#'));
        $corporateChallengeQuarter2->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($corporateChallengeQuarter2);

        $walkingChallengeQuarter2 = new PlaceHolderComplianceView(null, 0);
        $walkingChallengeQuarter2->setMaximumNumberOfPoints(10);
        $walkingChallengeQuarter2->setName('team_walking_challenge_quarter2');
        $walkingChallengeQuarter2->setReportName('Quarterly Walking Challenge Q2');
        $walkingChallengeQuarter2->setStatusSummary(ComplianceStatus::COMPLIANT, '-	Quarterly Walking Challenge');
        $walkingChallengeQuarter2->setAttribute('points_per_activity', 10);
        $walkingChallengeQuarter2->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $walkingChallengeQuarter2->addLink(new FakeLink('Admin will enter', '#'));
        $walkingChallengeQuarter2->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($walkingChallengeQuarter2);


        $exerciseElearningQuarter2  = new CompleteELearningGroupSet($startDate, $endDate, 'exercise_fitness_muscles');
        $exerciseElearningQuarter2->setMaximumNumberOfPoints(2);
        $exerciseElearningQuarter2->setPointsPerLesson(2);
        $exerciseElearningQuarter2->setName('exercise_elearning_quarter2');
        $exerciseElearningQuarter2->setReportName('Exercise, Fitness, & Muscles eLearning (bonus)');
        $exerciseElearningQuarter2->setStatusSummary(ComplianceStatus::COMPLIANT, '- Exercise, Fitness, & Muscles eLearning (bonus)');
        $exerciseElearningQuarter2->setAttribute('points_per_activity', 2);
        $exerciseElearningQuarter2->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $exerciseElearningQuarter2->useAlternateCode(true);
        $exerciseElearningQuarter2->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($exerciseElearningQuarter2);

        $topicQuizQuarter2  = new PlaceHolderComplianceView(null, 0);
        $topicQuizQuarter2->setMaximumNumberOfPoints(1);
        $topicQuizQuarter2->setName('quarter_topic_quiz_quarter2');
        $topicQuizQuarter2->setReportName('Quarterly Topic Quiz 2');
        $topicQuizQuarter2->setStatusSummary(ComplianceStatus::COMPLIANT, '- Quarterly Topic Quiz');
        $topicQuizQuarter2->setAttribute('points_per_activity', 1);
        $topicQuizQuarter2->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $topicQuizQuarter2->addLink(new FakeLink('Admin will enter', '#'));
        $topicQuizQuarter2->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($topicQuizQuarter2);

        $maintainCampaignQuarter2  = new PlaceHolderComplianceView(null, 0);
        $maintainCampaignQuarter2->setMaximumNumberOfPoints(3);
        $maintainCampaignQuarter2->setName('maintain_campaign_quarter2');
        $maintainCampaignQuarter2->setReportName('Maintain Campaign 2nd Quarter');
        $maintainCampaignQuarter2->setStatusSummary(ComplianceStatus::COMPLIANT, '- Maintain Campaign');
        $maintainCampaignQuarter2->setAttribute('points_per_activity', 3);
        $maintainCampaignQuarter2->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $maintainCampaignQuarter2->addLink(new FakeLink('Admin will enter', '#'));
        $maintainCampaignQuarter2->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($maintainCampaignQuarter2);

        $nutritionChallengeQuarter3  = new PlaceHolderComplianceView(null, 0);
        $nutritionChallengeQuarter3->setMaximumNumberOfPoints(10);
        $nutritionChallengeQuarter3->setName('nutrition_challenge_quarter3');
        $nutritionChallengeQuarter3->setReportName('Nutrition Challenge');
        $nutritionChallengeQuarter3->setStatusSummary(ComplianceStatus::COMPLIANT, '-	Quarterly Challenge');
        $nutritionChallengeQuarter3->setAttribute('points_per_activity', 10);
        $nutritionChallengeQuarter3->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $nutritionChallengeQuarter3->addLink(new FakeLink('Admin will enter', '#'));
        $nutritionChallengeQuarter3->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($nutritionChallengeQuarter3);

        $walkingChallengeQuarter3 = new PlaceHolderComplianceView(null, 0);
        $walkingChallengeQuarter3->setMaximumNumberOfPoints(10);
        $walkingChallengeQuarter3->setName('team_walking_challenge_quarter3');
        $walkingChallengeQuarter3->setReportName('Quarterly Walking Challenge Q3');
        $walkingChallengeQuarter3->setStatusSummary(ComplianceStatus::COMPLIANT, '-	Quarterly Walking Challenge');
        $walkingChallengeQuarter3->setAttribute('points_per_activity', 10);
        $walkingChallengeQuarter3->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $walkingChallengeQuarter3->addLink(new FakeLink('Admin will enter', '#'));
        $walkingChallengeQuarter3->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($walkingChallengeQuarter3);

        $brownBagAttendanceQuarter3  = new PlaceHolderComplianceView(null, 0);
        $brownBagAttendanceQuarter3->setMaximumNumberOfPoints(2);
        $brownBagAttendanceQuarter3->setName('bag_attendance_quarter3');
        $brownBagAttendanceQuarter3->setReportName('Brown Bag Attendance Q3');
        $brownBagAttendanceQuarter3->setStatusSummary(ComplianceStatus::COMPLIANT, '-	Brown Bag Attendance');
        $brownBagAttendanceQuarter3->setAttribute('points_per_activity', 2);
        $brownBagAttendanceQuarter3->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $brownBagAttendanceQuarter3->addLink(new FakeLink('Admin will enter', '#'));
        $brownBagAttendanceQuarter3->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($brownBagAttendanceQuarter3);

        $bonusElearningQuarter3  = new CompleteELearningGroupSet($startDate, $endDate, 'health_core');
        $bonusElearningQuarter3->setMaximumNumberOfPoints(2);
        $bonusElearningQuarter3->setPointsPerLesson(2);
        $bonusElearningQuarter3->setName('bonus_elearning_quarter3');
        $bonusElearningQuarter3->setReportName('BONUS eLearning');
        $bonusElearningQuarter3->setStatusSummary(ComplianceStatus::COMPLIANT, '-	BONUS eLearning');
        $bonusElearningQuarter3->setAttribute('points_per_activity', 2);
        $bonusElearningQuarter3->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $bonusElearningQuarter3->useAlternateCode(true);
        $bonusElearningQuarter3->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($bonusElearningQuarter3);

        $topicQuizQuarter3  = new PlaceHolderComplianceView(null, 0);
        $topicQuizQuarter3->setMaximumNumberOfPoints(1);
        $topicQuizQuarter3->setName('quarter_topic_quiz_quarter3');
        $topicQuizQuarter3->setReportName('Quarterly Topic Quiz 3');
        $topicQuizQuarter3->setStatusSummary(ComplianceStatus::COMPLIANT, '- Quarterly Topic Quiz');
        $topicQuizQuarter3->setAttribute('points_per_activity', 1);
        $topicQuizQuarter3->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $topicQuizQuarter3->addLink(new FakeLink('Admin will enter', '#'));
        $topicQuizQuarter3->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($topicQuizQuarter3);

        $maintainCampaignQuarter3  = new PlaceHolderComplianceView(null, 0);
        $maintainCampaignQuarter3->setMaximumNumberOfPoints(3);
        $maintainCampaignQuarter3->setName('maintain_campaign_quarter3');
        $maintainCampaignQuarter3->setReportName('Maintain Campaign 3nd Quarter');
        $maintainCampaignQuarter3->setStatusSummary(ComplianceStatus::COMPLIANT, '- Maintain Campaign');
        $maintainCampaignQuarter3->setAttribute('points_per_activity', 3);
        $maintainCampaignQuarter3->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $maintainCampaignQuarter3->addLink(new FakeLink('Admin will enter', '#'));
        $maintainCampaignQuarter3->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($maintainCampaignQuarter3);

        $quarterlyChallengeQuarter4  = new PlaceHolderComplianceView(null, 0);
        $quarterlyChallengeQuarter4->setMaximumNumberOfPoints(10);
        $quarterlyChallengeQuarter4->setName('quarterly_challenge_quarter4');
        $quarterlyChallengeQuarter4->setReportName('Quarterly Challenge');
        $quarterlyChallengeQuarter4->setStatusSummary(ComplianceStatus::COMPLIANT, '- Quarterly Challenge');
        $quarterlyChallengeQuarter4->setAttribute('points_per_activity', 10);
        $quarterlyChallengeQuarter4->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $quarterlyChallengeQuarter4->addLink(new FakeLink('Admin will enter', '#'));
        $quarterlyChallengeQuarter4->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($quarterlyChallengeQuarter4);

        $that = $this;

        $walkingChallengeQuarter4 = new PlaceHolderComplianceView(null, 0);
        $walkingChallengeQuarter4->setMaximumNumberOfPoints(10);
        $walkingChallengeQuarter4->setName('team_walking_challenge_quarter4');
        $walkingChallengeQuarter4->setReportName('Quarterly Walking Challenge Q4');
        $walkingChallengeQuarter4->setStatusSummary(ComplianceStatus::COMPLIANT, '-	Quarterly Walking Challenge');
        $walkingChallengeQuarter4->setAttribute('points_per_activity', 10);
        $walkingChallengeQuarter4->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $walkingChallengeQuarter4->addLink(new FakeLink('Admin will enter', '#'));
        $walkingChallengeQuarter4->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($walkingChallengeQuarter4);

        $bonusElearningQuarter4  = new CompleteELearningGroupSet($startDate, $endDate, 'stress_reslience');
        $bonusElearningQuarter4->setMaximumNumberOfPoints(2);
        $bonusElearningQuarter4->setPointsPerLesson(2);
        $bonusElearningQuarter4->setName('bonus_elearning_quarter4');
        $bonusElearningQuarter4->setReportName('BONUS eLearning');
        $bonusElearningQuarter4->setStatusSummary(ComplianceStatus::COMPLIANT, '-	 BONUS eLearning');
        $bonusElearningQuarter4->setAttribute('points_per_activity', 2);
        $bonusElearningQuarter4->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $bonusElearningQuarter4->useAlternateCode(true);
        $bonusElearningQuarter4->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($bonusElearningQuarter4);

        $topicQuizQuarter4  = new PlaceHolderComplianceView(null, 0);
        $topicQuizQuarter4->setMaximumNumberOfPoints(3);
        $topicQuizQuarter4->setName('quarter_topic_quiz_quarter4');
        $topicQuizQuarter4->setReportName('Quarterly Topic Quiz 4');
        $topicQuizQuarter4->setStatusSummary(ComplianceStatus::COMPLIANT, '- End of Year Quiz');
        $topicQuizQuarter4->setAttribute('points_per_activity', 3);
        $topicQuizQuarter4->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $topicQuizQuarter4->addLink(new FakeLink('Admin will enter', '#'));
        $topicQuizQuarter4->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($topicQuizQuarter4);

        $maintainCampaignQuarter4  = new PlaceHolderComplianceView(null, 0);
        $maintainCampaignQuarter4->setMaximumNumberOfPoints(4);
        $maintainCampaignQuarter4->setName('maintain_campaign_quarter4');
        $maintainCampaignQuarter4->setReportName('Maintain Campaign 4nd Quarter');
        $maintainCampaignQuarter4->setStatusSummary(ComplianceStatus::COMPLIANT, '- Maintain Campaign');
        $maintainCampaignQuarter4->setAttribute('points_per_activity', 4);
        $maintainCampaignQuarter4->setAttribute('report_name_link', '/content/1094#4hteamwin');
        $maintainCampaignQuarter4->addLink(new FakeLink('Admin will enter', '#'));
        $maintainCampaignQuarter4->setUseOverrideCreatedDate(true);
        $quarterGroup->addComplianceView($maintainCampaignQuarter4);

        $this->addComplianceViewGroup($quarterGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if(!$this->evaluateOverall) {
            return;
        }

    }

    protected function getExcludedViews()
    {
        return array(
            'physician_info',
            'comply_with_glucose_screening_test',
            'comply_with_triglycerides_screening_test',
            'comply_with_total_hdl_cholesterol_ratio_screening_test',
            'comply_with_total_cholesterol_screening_test',
            'comply_with_ldl_screening_test',
            'blood_pressure',
            'comply_with_bmi_screening_test',
            'phy_exam'
        );
    }

    public function getActiveMinutes(\User $user)
    {
        $start = new \DateTime('2015-12-04');
        $end = new \DateTime('2015-12-18');

        $count = 0;

        foreach(get_fitbit_activities_data($user) as $day => $m) {
            $stamp = strtotime($day);

            if ($start->format('U') <= $stamp && $end->format('U') >= $stamp) {
                $count += $m;
            }
        }

        return $count;
    }

    private $summaryRanges = array(
        'steps_2017-01' => array('2017-01-01', '2017-01-31'),
        'steps_2017-02' => array('2017-02-01', '2017-02-28'),
        'steps_2017-03' => array('2017-03-01', '2017-03-31'),
        'steps_2017-04' => array('2017-04-01', '2017-04-30'),
        'steps_2017-05' => array('2017-05-01', '2017-05-31'),
        'steps_2017-06' => array('2017-06-01', '2017-06-30'),
        'steps_2017-07' => array('2017-07-01', '2017-07-31'),
        'steps_2017-08' => array('2017-08-01', '2017-08-31'),
        'steps_2017-09' => array('2017-09-01', '2017-09-30'),
        'steps_2017-10' => array('2017-10-01', '2017-10-31'),
        'steps_2017-11' => array('2017-11-01', '2017-11-30'),
        'steps_2017-12' => array('2017-12-01', '2017-12-31')
    );

    protected $evaluateOverall = true;
}

class V32017ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);
        $this->setShowTotal(false);

        $this->addStatusCallbackColumn('Requirement', function($status) {
            return $status->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT);
        });

        $this->addStatusCallbackColumn('Points Per Activity', function($status) {
            if($status->getComplianceView()->getComplianceViewGroup()->getName() == 'bonus') {
                return $status->getComment();
            } else {
                return $status->getComplianceView()->getAttribute('points_per_activity');
            }
        });

        $this->addStatusCallbackColumn('Max Points', function($status) {
            return $status->getComplianceView()->getMaximumNumberOfPoints();
        });

        $this->addStatusCallbackColumn('Points Earned', function($status) {
            return $status->getPoints();
        });
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <style type="text/css">
            .pageHeading { display:none; }

            #status-table th,
            .phipTable .headerRow {
                background-color:#007698;
                color:#FFF;
            }

            #status-table th,
            #status-table td {
                padding:5px;
                text-align:center;
                border:1px solid #CACACA;
            }

            .phipTable,
            .phipTable th,
            .phipTable td {
                font-size:0.95em;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                $('.points').each(function() {
                    $(this).remove();
                });

                $('.headerRow-required').children(':eq(1), :eq(2)').remove();
                $('.view-complete_hra').children(':eq(1), :eq(2), :eq(3), :eq(4)').remove();
                $('.view-complete_screening').children(':eq(1), :eq(2), :eq(3), :eq(4)').remove();
                $('.headerRow-activities').children(':eq(5), :eq(6)').remove();
                $('.headerRow-activities').children(':eq(3)').html('Maximum Points');
                $('.headerRow-activities').children(':eq(4)').html('Points earned');
                $('.headerRow-quarterly').children(':eq(5), :eq(6)').remove();
                $('.headerRow-quarterly').children(':eq(3)').html('Maximum Points');
                $('.headerRow-quarterly').children(':eq(4)').html('Points earned');

                $('.headerRow-quarterly th').attr('colspan', 2);
                $('.headerRow-quarterly td:first').remove();

                $('.view-comply_with_glucose_screening_test td:first')
                    .attr('rowspan', '7')
                    .html('<strong>B</strong>. <a href="/content/1094#2bBiometric">Based on Wellness Screening & HPA Results</a>');

                $('.view-comply_with_glucose_screening_test td:last')
                    .attr('rowspan', '7');

                $('.view-comply_with_glucose_screening_test td:last')
                    .html('<a target="_self" href="/compliance/hmi-2016/my-results">Results</a>');

                $('.view-comply_with_triglycerides_screening_test td:first, .view-comply_with_triglycerides_screening_test td:last').remove();
                $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test td:first, .view-comply_with_total_hdl_cholesterol_ratio_screening_test td:last').remove();
                $('.view-comply_with_total_cholesterol_screening_test td:first, .view-comply_with_total_cholesterol_screening_test td:last').remove();
                $('.view-comply_with_ldl_screening_test td:first, .view-comply_with_ldl_screening_test td:last').remove();
                $('.view-blood_pressure td:first, .view-blood_pressure td:last').remove();
                $('.view-comply_with_bmi_screening_test td:first, .view-comply_with_bmi_screening_test td:last').remove();

                $('.view-phy_exam td:first')
                    .html('<strong>C</strong>. <a href="/content/1094#2cAnnPhys">Annual Physical Exam & Screening follow up</a>');

                // Span rows for prev services / flushot
                $('.view-prev_serv_flu td:first')
                    .attr('rowspan', 3)
                    .html('<strong>D</strong>. <a href="/content/1094#2dPrevServ">Preventative Services</a>');

                $('.view-smoking td:first')
                    .html('<strong>E</strong>. <a href="/content/1094#2eSmokingCess">Smoking Cessation</a>');

                $('.view-elearning_lesson td:first')
                    .html('<strong>F</strong>. <a href="/content/1094#2feLearn">eLearning Lessons</a>');

                $('.view-kickoff td:first')
                    .html('<strong>G</strong>. <a href="/content/1094#2gKickoff">Wellness Kick off</a>');

                $('.view-nut_program td:first')
                    .html('<strong>H</strong>. <a href="/content/1094#2hNutr">Health/Nutrition Program Annual Membership</a>');

                $('.view-activity_21 td:first')
                    .html('<strong>I</strong>. <a href="/content/1094#2iFitness">Activity Tracking</a>');

                $('.view-fruit_exchange td:first')
                    .html('<strong>J</strong>. <a href="/content/1094#2jFruitVeggie">Fruit/Veggie Exchange</a>');

                $('.view-other_activities td:first')
                    .html('<strong>K</strong>. <a href="/content/1094#2jFruitVeggie">Other Revitalize Activities</a>');

                $('.view-prev_serv_mammogram td:first').remove();
                $('.view-donate_blood td:first').remove();

                $('.headerRow-required td:first').remove();
                $('.headerRow-required td:first').remove();
                $('.headerRow-required th').attr('colspan', 3);
                $('.view-complete_hra td:eq(0)').attr('colspan', 3);
                $('.view-complete_screening td:eq(0)').attr('colspan', 3);

                // Missing headers
                $('.headerRow-bonus td:eq(0)').html('Requirement');
                $('.headerRow-bonus td:eq(1)').html('Result');
                $('.headerRow-activities td:eq(0)').html('Requirement');
                $('.headerRow-activities td:eq(1)').html('Points Per Activity');
                $('.headerRow-quarterly td:eq(0)').html('Points Per Activity');

                // Span 5k/10k etc events
                $('.view-walk_run td:first')
                    .attr('rowspan', '3')
                    .html('<strong>L</strong>. <a href="/content/1094#2kAthlEvent">Athletic Events</a>');
                $('.view-walk_run td:last').attr('rowspan', '3');

                $('.view-half_mar td:first').remove();
                $('.view-half_mar td:last').remove();
                $('.view-full_mar td:first').remove();
                $('.view-full_mar td:last').remove();

                $('.view-bike_under_5 td:first')
                    .attr('rowspan', '3')
                    .html('<strong>M</strong>. <a href="/content/1094#2kAthlEvent">Bike to Work Week</a>');
                $('.view-bike_under_5 td:last').attr('rowspan', '3');

                $('.view-bike_10 td:first').remove();
                $('.view-bike_10 td:last').remove();
                $('.view-bike_above_10 td:first').remove();
                $('.view-bike_above_10 td:last').remove();

                $('.view-walk_6k td:first')
                    .attr('rowspan', '3')
                    .html('<strong>N. <a href="/content/1094#2lWalk">Individual Walking Program</a></strong> ' +
                    '<em style="font-size: 9pt;">(Points will be awarded ' +
                    'at the end of each month based on the average steps logged ' +
                    'during the period)</em>');
                $('.view-walk_6k td:last').attr('rowspan', '3');

                $('.view-walk_8k td:first').remove();
                $('.view-walk_8k td:last').remove();
                $('.view-walk_10k td:first').remove();
                $('.view-walk_10k td:last').remove();


                $('.view-biggest_loser_contest_quarter1 td:first')
                    .attr('rowspan', '5')
                    .html('<div style="margin: 0 10px;"><strong>A. <a href="/content/1094#3aWeightLoss">1st Quarter Topic:</a> <br />' +
                    '<span style="color:red">Weight Loss</span>' +
                    '</strong><br /> (January – March)</div>');

                $('.view-team_walking_challenge_quarter1 td:first').remove();
                $('.view-bag_attendance_quarter1 td:first').remove();
                $('.view-weight_elearning_quarter1 td:first').remove();
                $('.view-quarter_topic_quiz_quarter1 td:first').remove();

                $('.view-v3_challenge_quarter2 td:first')
                    .attr('rowspan', '5')
                    .html('<div style="margin: 0 10px;"><strong>B. <a href="/content/1094#3bActivity">2nd Quarter Topic:</a> <br />' +
                    '<span style="color:red">Activity</span>' +
                    '</strong><br /> (April – June)</div>');

                $('.view-team_walking_challenge_quarter2 td:first').remove();
//                $('.view-bag_attendance_quarter2 td:first').remove();
                $('.view-exercise_elearning_quarter2 td:first').remove();
                $('.view-quarter_topic_quiz_quarter2 td:first').remove();
                $('.view-maintain_campaign_quarter2 td:first').remove();

                $('.view-nutrition_challenge_quarter3 td:first')
                    .attr('rowspan', '6')
                    .html('<div style="margin: 0 10px;"><strong>C. <a href="/content/1094#3cNutrition">3rd Quarter Topic:</a> <br />' +
                    '<span style="color:red">TBD</span>' +
                    '</strong><br /> (July – September)</div>');

                $('.view-team_walking_challenge_quarter3 td:first').remove();
                $('.view-bag_attendance_quarter3 td:first').remove();
                $('.view-bonus_elearning_quarter3 td:first').remove();
                $('.view-quarter_topic_quiz_quarter3 td:first').remove();
                $('.view-maintain_campaign_quarter3 td:first').remove();

                $('.view-quarterly_challenge_quarter4 td:first')
                    .attr('rowspan', '6')
                    .html('<div style="margin: 0 10px;"><strong>D. <a href="/content/1094#3dStressMan">4th Quarter Topic:</a> <br />' +
                    '<span style="color:red">TBD</span>' +
                    '</strong><br /> (October – December)</div>');

                $('.view-team_walking_challenge_quarter4 td:first').remove();
                $('.view-bag_attendance_quarter4 td:first').remove();
                $('.view-bonus_elearning_quarter4 td:first').remove();
                $('.view-quarter_topic_quiz_quarter4 td:first').remove();
                $('.view-maintain_campaign_quarter4 td:first').remove();

                $('.view-maintain_campaign_quarter4').after(
                    '<tr class="headerRow headerRow-quarterly">' +
                    '<th colspan="2"><strong>4</strong>. Total Points Earned</th>' +
                    '<td colspan="4"><?php echo $status->getPoints() ?></td>' +
                    '</tr>'
                );
            });
        </script>

        <div class="page-header">
            <div class="row">
                <div class="col-md-3">
                    <img src="https://static.hpn.com/wms2/images/clients/v3/v3_2017_report_logo.jpg" style="width:100px;" />
                </div>
                <div class="col-md-9" style="margin-top:70px; text-align: center">
                    <h4 style="color:red;">Revitalize 2017 Wellness Program</h4>
                </div>

            </div>

        </div>

        <p>V3 cares about your health! We continue to partner with Health Maintenance Institute (HMI) to manage
            our Wellness Program. This program will provide you with fun, robust programming options geared
            towards specific areas of your health that need improvement and provide a way to better,
            healthier living.</p>


        <p style="font-weight:bold;text-align:center;">
            HOW DOES THE PROGRAM WORK?</p>

        <p> <strong>Employees and spouses that complete the 2017 Wellness Screening and Health Power
                Assessment (HPA) are eligible to participate.</strong> Participation in the program will earn wellness
            points that will be tracked in the table below. Rewards will be based on points earned in 2017. </p>

        <p style="background-color:yellow; font-weight: bold">
            There are changes to the 2017 point structure. To earn the full wellness incentive,
            Participants must earn 120 points in 2017 (10 points per Quarter & 80 additional points).
        </p>

        <p style="color:red; font-weight: bold">
            Employees will earn a monetary rewards based on the total number of points accumlated between
            January 1, 2017 – December 31, 2017. Each level is outlined in the chart below.
        </p>

        <table style="width:100%" id="status-table">
            <tr>
                <th colspan="5">V3 Walking Program</th>
            </tr>
            <tr>
                <td>Walking Program Participants</td>
                <td colspan="4">
                    <div class="row">
                        <div class="col-md-2">
                            <img src="/resources/5259/v3FreeFitbitZip.png" />
                        </div>
                        <div class="col-md-10" style="text-align: left">
                            <div>All Participants will pay the $20 participation fee.</div>
                            <div>New Participants will receive a Free FitBit Zip ($40 Value)</div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>Quarter</th>
                <th>Employee Points</th>
                <th>Employee Reward</th>
                <th>Spouse Points</th>
                <th>Spouse Reward</th>
            </tr>
            <tr>
                <td>1</td>
                <td>Accumulate at least 10 points</td>
                <td>$50</td>
                <td>At least 10 points</td>
                <td>$25</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Accumulate at least 10 points</td>
                <td>$50</td>
                <td>At least 10 points</td>
                <td>$25</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Accumulate at least 10 points</td>
                <td>$50</td>
                <td>At least 10 points</td>
                <td>$25</td>
            </tr>
            <tr>
                <td>4</td>
                <td>Accumulate at least 10 points</td>
                <td>$50</td>
                <td>At least 10 points</td>
                <td>$25</td>
            </tr>
            <tr>
                <td>Annual</td>
                <td>Accumulate 80 points or more in addition to the 10 points per quarter</td>
                <td>$200</td>
                <td>At least 80 points or more in addition to the 10 points per quarter</td>
                <td>$100</td>
            </tr>
            <tr style="font-weight:bold;">
                <td>Annual</td>
                <td>
                    <div>
                        Maximum Employee Rewards
                    </div>
                    <div>
                        (<u>Total 120 points or more</u> by earning
                        10 points per quarter and 80
                        additional points throughout the year)
                    </div>
                </td>
                <td>$400</td>
                <td>
                    <div>
                        Maximum Spouse Rewards
                    </div>
                    <div>
                        (<u>Total 120 points or more</u> by earning
                        10 points per quarter and 80
                        additional points throughout the year)
                    </div>
                </td>
                <td>$200</td>
            </tr>
            <tr>
                <td>GRAND RAFFLE</td>
                <td colspan="4">
                    For every 10 points earned over 120, <br />
                    Employees will earn 1 entry into the Grand Raffle!<br />
                    **Must earn at least 10 points per quarter to be eligible
                </td>
            </tr>
        </table>

        <p style="text-align:center; color:red;">
            The maximum reward available per year is $400 for employees and $200 for spouses.
            Grand Raffle drawing will be held in mid-January, 2018.
        </p>

        <p style="font-size:8pt;">
            <em><span style="font-weight: bold; text-decoration: underline;">Notice:</span>
                The Revitalize Wellness Program is committed to helping you achieve your best health. Rewards for participating in a
                wellness progam are available to all employees and spouses. If you think you might be unable to meet a standard for a reward
                under this wellness program, you might qualify for an opportunity to earn the same reward by different means. Contact Human
                Resources and we will work with you (and, if you wish, your doctor) to find a wellness program with the same reward that is right for
                you in light of your health status. </em>
        </p>
        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        ?>

        <style type="text/css">
            #quarterly-points {
                display: table-cell;
            }

            #quarterly-points .quarterly-row {
                margin: 5px;
            }

            #quarterly-points .quarterly-row .quarterly-title {
                margin-left: 300px;
            }

            #quarterly-points .quarterly-row .quarterly-light {
                margin-left: 80px;
            }

            #quarterly-points .quarterly-row .quarterly-points {
                margin-left: 70px;
            }
        </style>

        <br/>



        <?php
    }


}
