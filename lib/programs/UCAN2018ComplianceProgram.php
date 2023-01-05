<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class Ucan2018AverageStepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold)
    {
        $this->setDateRange($startDate, $endDate);

        $this->threshold = $threshold;

        $formattedThreshold = number_format($this->threshold);

        $this->setAttribute('requirement', "Walk an average of {$formattedThreshold} steps/day");
    }

    public function getDefaultStatusSummary($status)
    {
        if($status == ComplianceStatus::COMPLIANT) {
            return $this->getAttribute('requirement');
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return "hmi_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));
        require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/jawbone/lib/model/jawboneApi.php';
        require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/moves/lib/model/movesApi.php';

        $fitbitData = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        

        $jawboneData = JawboneApi::getJawboneData($user);
       
        $movesData = MovesApi::getMovesData($user, true);

        $averageDailySteps = 0;
        if(isset($fitbitData['average_daily_steps']) && $fitbitData['average_daily_steps'] > $averageDailySteps) {
            $averageDailySteps = $fitbitData['average_daily_steps'];
        }

        if(isset($jawboneData['average_daily_steps']) && $jawboneData['average_daily_steps'] > $averageDailySteps) {
            $averageDailySteps = $jawboneData['average_daily_steps'];
        }

        if(isset($movesData['average_daily_steps']) && $movesData['average_daily_steps'] > $averageDailySteps) {
            $averageDailySteps = $movesData['average_daily_steps'];
        }

        $compliantStatus =     $averageDailySteps > $this->threshold ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

        $status = new ComplianceViewStatus(
            $this,
            $compliantStatus,
            $compliantStatus == ComplianceStatus::COMPLIANT ? $this->threshold : 0
        );

        $status->setAttribute('average_daily_steps', isset($averageDailySteps) ? $averageDailySteps : 0);
        $status->setAttribute('total_steps', isset($fitbitData['total_steps']) ? $fitbitData['total_steps'] : 0);


        return $status;
    }

    private $threshold;
}


class UCAN2018ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new UCAN2018ComplianceProgramReportPrinter();

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('Steps', function(ComplianceProgramStatus $status) {
            return $status->getComplianceViewStatus('walk_nov_dec')->getAttribute('total_steps');
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $reqGroup = new ComplianceViewGroup('required', 'Required Activities');

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Employee completes the Health Power Assessment (HPA)');
        $hra->setAttribute('report_name_link', '/content/1094new2015-16#1ahpa');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $hra->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $reqGroup->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($startDate, $endDate);
        $scr->setReportName('Employee participates in the Offsite Wellness Screening (PPO members only)');
        $scr->setAttribute('report_name_link', '/content/1094new2015-16#1bannscreen');
        $scr->emptyLinks();
        $scr->addLink(new Link('Sign-Up', '/compliance/hmi-2016/schedule/content/wms2-appointment-center'));
        $scr->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $scr->addLink(new Link('MD Form', '/resources/9441/UCAN MD Form 2017.pdf'));
        $reqGroup->addComplianceView($scr);

        $personalPhysician = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $personalPhysician->setName('visit_personal_physician');
        $personalPhysician->setReportName('HMO, Visits Personal Physician for screening & lab work (all HMO members; alternative to offsite screening for PPO members).');
        $personalPhysician->addLink(new FakeLink('Download Consent Form', '#'));
        $reqGroup->addComplianceView($personalPhysician);

        $this->addComplianceViewGroup($reqGroup);

        $bonusGroup = new ComplianceViewGroup('bonus', 'Biometric Bonus Points');
        $bonusGroup->setPointsRequiredForCompliance(0);


        $hraScore65 = new HraScoreComplianceView($startDate, $endDate, 65);
        $hraScore65->setReportName('Receive a Health Power Score >= 65');
        $hraScore65->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $hraScore65->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 65');
        $hraScore65->setAttribute('points_per_activity', 5);
        $hraScore65->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bonusGroup->addComplianceView($hraScore65);

        $hraScore70 = new HraScoreComplianceView($startDate, $endDate, 70);
        $hraScore70->setReportName('Receive a Health Power Score >= 70');
        $hraScore70->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $hraScore70->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 70');
        $hraScore70->setAttribute('points_per_activity', 5);
        $hraScore70->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bonusGroup->addComplianceView($hraScore70);

        $hraScore80 = new HraScoreComplianceView($startDate, $endDate, 80);
        $hraScore80->setReportName('Receive a Health Power Score >= 80');
        $hraScore80->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $hraScore80->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 80');
        $hraScore80->setAttribute('points_per_activity', 10);
        $hraScore80->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bonusGroup->addComplianceView($hraScore80);

        $hraScore90 = new HraScoreComplianceView($startDate, $endDate, 90);
        $hraScore90->setReportName('Receive a Health Power Score >= 90');
        $hraScore90->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $hraScore90->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 90');
        $hraScore90->setAttribute('points_per_activity', 10);
        $hraScore90->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bonusGroup->addComplianceView($hraScore90);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setReportName('Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $glucoseView->setAttribute('points_per_activity', 10);
        $glucoseView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, '<100');
        $bonusGroup->addComplianceView($glucoseView);

        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $endDate);
        $tcView->setReportName('Total Cholesterol');
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $tcView->setAttribute('points_per_activity', 10);
        $tcView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setStatusSummary(ComplianceStatus::COMPLIANT, '<200');
        $bonusGroup->addComplianceView($tcView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($startDate, $endDate);
        $hdlView->setReportName('HDL Cholesterol');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $hdlView->setAttribute('points_per_activity', 10);
        $hdlView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $hdlView->overrideTestRowData(null, 50, null, null);
        $hdlView->setStatusSummary(ComplianceStatus::COMPLIANT, '>= 50');
        $bonusGroup->addComplianceView($hdlView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $ldlView->setReportName('LDL Cholesterol');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $ldlView->setAttribute('points_per_activity', 10);
        $ldlView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $ldlView->overrideTestRowData(null, null, 99.999, null);
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, '< 100');
        $bonusGroup->addComplianceView($ldlView);

        $cholesterolHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($startDate, $endDate);
        $cholesterolHDLRatioView->setReportName('TC/HDL Ratio');
        $cholesterolHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $cholesterolHDLRatioView->setAttribute('points_per_activity', 10);
        $cholesterolHDLRatioView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $cholesterolHDLRatioView->overrideTestRowData(null, null, 4.4, null);
        $cholesterolHDLRatioView->setStatusSummary(ComplianceStatus::COMPLIANT, '<= 4.4');
        $bonusGroup->addComplianceView($cholesterolHDLRatioView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $triglyceridesView->setReportName('Triglycerides');
        $triglyceridesView->setAttribute('points_per_activity', 10);
        $triglyceridesView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setStatusSummary(ComplianceStatus::COMPLIANT, '< 150');
        $bonusGroup->addComplianceView($triglyceridesView);

        $systolicView = new ComplyWithSystolicBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $systolicView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $systolicView->setReportName('Systolic Blood Pressure');
        $systolicView->setAttribute('points_per_activity', 5);
        $systolicView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $systolicView->overrideTestRowData(null, null, 125, null);
        $systolicView->setStatusSummary(ComplianceStatus::COMPLIANT, '<= 125');
        $bonusGroup->addComplianceView($systolicView);

        $diastolicView = new ComplyWithDiastolicBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $diastolicView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $diastolicView->setReportName('Diastolic Blood Pressure');
        $diastolicView->setAttribute('points_per_activity', 5);
        $diastolicView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $diastolicView->overrideTestRowData(null, null, 85, null);
        $diastolicView->setStatusSummary(ComplianceStatus::COMPLIANT, '<= 85');
        $bonusGroup->addComplianceView($diastolicView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($startDate, $endDate);
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $bmiView->setReportName('BMI');
        $bmiView->setAttribute('points_per_activity', 10);
        $bmiView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bmiView->overrideTestRowData(null, null, 27.499, null);
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '<27.5');
        $bonusGroup->addComplianceView($bmiView);

        $this->addComplianceViewGroup($bonusGroup);

        $actGroup = new ComplianceViewGroup('activities', 'Annual / Self-Care Wellness Activities');
        $actGroup->setPointsRequiredForCompliance(0);

        $phyExam = new PlaceHolderComplianceView(null, 0);
        $phyExam->setMaximumNumberOfPoints(20);
        $phyExam->setName('phy_exam');
        $phyExam->setReportName('Annual Physical Exam & Screening follow up');
        $phyExam->addLink(new Link('Preventive Care Certification Form', '/resources/10075/2018_PreventiveCare Cert.pdf'));
        $phyExam->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $phyExam->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam');
        $phyExam->setAttribute('points_per_activity', 20);
        $phyExam->setAttribute('report_name_link', '/content/1094new2015-16#3aannphys');
        $actGroup->addComplianceView($phyExam);

        $prevServ = new PlaceHolderComplianceView(null, 0);
        $prevServ->setMaximumNumberOfPoints(30);
        $prevServ->setName('prev_serv');
        $prevServ->setReportName('Preventative Services');
        $prevServ->addLink(new Link('Preventive Care Certification Form', '/resources/10075/2018_PreventiveCare Cert.pdf'));
        $prevServ->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $prevServ->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventative service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.  See attached wellness guides or check with your personal physician for necessary tests');
        $prevServ->setAttribute('points_per_activity', 10);
        $prevServ->setAttribute('report_name_link', '/content/1094new2015-16#3bprev');
        $actGroup->addComplianceView($prevServ);

        $fluShot = new PlaceHolderComplianceView(null, 0);
        $fluShot->setMaximumNumberOfPoints(10);
        $fluShot->setName('flu_shot');
        $fluShot->setReportName('Preventative Services - Flu Shot');
        $fluShot->addLink(new FakeLink('Admin will enter credit for onsite clinics', '#'));
        $fluShot->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a flu shot');
        $fluShot->setAttribute('points_per_activity', 10);
        $fluShot->setAttribute('report_name_link', '/content/1094new2015-16#3cflushot');
        $actGroup->addComplianceView($fluShot);

        $registerDownload = new PlaceHolderComplianceView(null, 0);
        $registerDownload->setMaximumNumberOfPoints(10);
        $registerDownload->setName('register_download');
        $registerDownload->setReportName('Register and Download');
        $registerDownload->addLink(new Link('Link to BlueAccess for Members', 'https://members.hcsc.net/wps/portal/bam', false, '_blank'));
        $registerDownload->setStatusSummary(ComplianceStatus::COMPLIANT, '<a href="https://members.hcsc.net/wps/portal/bam" target="_blank">BlueAccess Member Registration</a> AlwaysOn App Download');
        $registerDownload->setAttribute('points_per_activity', 10);
        $registerDownload->setAttribute('report_name_link', '/content/1094new2015-16#3cflushot');
        $actGroup->addComplianceView($registerDownload);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(25);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new FakeLink('Admin will enter<br />', '#'));
        $smoking->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete a Smoking Cessation Course offered by the Quitline: <a href="http://www.quityes.org">www.quityes.org</a>');
        $smoking->setAttribute('points_per_activity', 25);
        $smoking->setAttribute('report_name_link', '/content/1094new2015-16#3dsmoking');
        $actGroup->addComplianceView($smoking);

        $lessons = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, null, null, 5);
        $lessons->setAttribute('points_per_activity', 5);
        $lessons->setReportName('eLearning Lessons');
        $lessons->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete eLearning lessons');
        $lessons->setMaximumNumberOfPoints(25);
        $lessons->setAttribute('report_name_link', '/content/1094new2015-16#3eeLearn');
        $actGroup->addComplianceView($lessons);

        $onMyTime = new PlaceHolderComplianceView(null, 0);
        $onMyTime->setMaximumNumberOfPoints(25);
        $onMyTime->setName('mytime');
        $onMyTime->setReportName('OnMyTime Courses <br /> <a href="http://www.WellonTarget.com" target="_blank">www.WellonTarget.com</a>');
        $onMyTime->addLink(new Link('Submit Certificate of Completion Online', '/content/chp-document-uploader'));
        $onMyTime->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete BCBS Online Program via Well On Target* on Nutrition, Weight Management, Stress Management, Smoking Cessation, etc');
        $onMyTime->setAttribute('points_per_activity', 25);
        $onMyTime->setAttribute('report_name_link', '/content/1094new2015-16#3fonmytime');
        $actGroup->addComplianceView($onMyTime);

        $employeeBenefits = new PlaceHolderComplianceView(null, 0);
        $employeeBenefits->setMaximumNumberOfPoints(10);
        $employeeBenefits->setName('kickoff');
        $employeeBenefits->setReportName('Employee Benefits Fair');
        $employeeBenefits->addLink(new FakeLink('Sign in at Event', '#'));
        $employeeBenefits->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend the Employee Benefits Fair in May 21st -25th 2018');
        $employeeBenefits->setAttribute('points_per_activity', 10);
        $employeeBenefits->setAttribute('report_name_link', '/content/1094new2015-16#3gbenFair');
        $actGroup->addComplianceView($employeeBenefits);

        $hwagLnl = new PlaceHolderComplianceView(null, 0);
        $hwagLnl->setMaximumNumberOfPoints(30);
        $hwagLnl->setName('hwag_lnl');
        $hwagLnl->setReportName('HWAG Lunch & Learn Presentation');
        $hwagLnl->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend a HWAG Lunch and Learn Session');
        $hwagLnl->addLink(new FakeLink('Sign in at Presentation', '#'));
        $hwagLnl->setAttribute('points_per_activity', 15);
        $hwagLnl->setAttribute('report_name_link', '/content/1094new2015-16#3hHWAGLnL');
        $actGroup->addComplianceView($hwagLnl);

        $hwagQuiz = new PlaceHolderComplianceView(null, 0);
        $hwagQuiz->setMaximumNumberOfPoints(30);
        $hwagQuiz->setName('hwag_quiz');
        $hwagQuiz->setReportName('HWAG Quiz');
        $hwagQuiz->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete the HWAG quiz');
        $hwagQuiz->setAttribute('points_per_activity', 5);
        $hwagQuiz->setAttribute('report_name_link', '/content/1094new2015-16#3iQuiz');
        $hwagQuiz->addLink(new FakeLink('Complete Quiz Admin will Enter', '#'));
        $actGroup->addComplianceView($hwagQuiz);

        $nutProgram = new PlaceHolderComplianceView(null, 0);
        $nutProgram->setMaximumNumberOfPoints(30);
        $nutProgram->setName('on_target_member');
        $nutProgram->setReportName('Well onTarget Member Portal');
        $nutProgram->addLink(new Link('HWAG Certification Form <br />', '/resources/10074/2018_nonHWAG Event Cert.pdf'));
        $nutProgram->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $nutProgram->setStatusSummary(ComplianceStatus::COMPLIANT, '$25 Monthly Gym Membership and Blue Points Rewards');
        $nutProgram->setAttribute('points_per_activity', 10);
        $nutProgram->setAttribute('report_name_link', '/content/1094new2015-16#3jMembership');
        $actGroup->addComplianceView($nutProgram);

        $physAct = new PhysicalActivityComplianceView($startDate, $endDate);
        $physAct->setMaximumNumberOfPoints(160);
        $physAct->setMonthlyPointLimit(16);
        $physAct->setAttribute('points_per_activity', '16 points/month');
        $physAct->setReportName('Regular Fitness Training');
        $physAct->setStatusSummary(ComplianceStatus::COMPLIANT, 'Track a minimum of 90 minutes of activity/week on the HMI website');
        $physAct->setAttribute('report_name_link', '/content/1094new2015-16#3kphysact');
        $actGroup->addComplianceView($physAct);

        $fiveK = new PlaceHolderComplianceView(null, 0);
        $fiveK->setMaximumNumberOfPoints(40);
        $fiveK->setName('5k');
        $fiveK->setReportName('Participate in a 5k');
        $fiveK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 5k');
        $fiveK->setAttribute('points_per_activity', 20);
        $fiveK->addLink(new Link('HWAG Certification Form <br />', '/resources/10074/2018_nonHWAG Event Cert.pdf'));
        $fiveK->addLink(new Link('<br />Submit Form', '/content/chp-document-uploader'));
        $fiveK->setAttribute('report_name_link', '/content/1094new2015-16#3loRace');
        $actGroup->addComplianceView($fiveK);

        $bikeRace = new PlaceHolderComplianceView(null, 0);
        $bikeRace->setMaximumNumberOfPoints(60);
        $bikeRace->setName('bike_race');
        $bikeRace->setReportName('Participate in a Bike Race');
        $bikeRace->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a Bike Race');
        $bikeRace->setAttribute('points_per_activity', 30);
        $bikeRace->setAttribute('report_name_link', '/content/1094new2015-16#3loRace');
        $actGroup->addComplianceView($bikeRace);

        $tenK = new PlaceHolderComplianceView(null, 0);
        $tenK->setMaximumNumberOfPoints(80);
        $tenK->setName('10k');
        $tenK->setReportName('Participate in a 10K');
        $tenK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 10K');
        $tenK->setAttribute('points_per_activity', 40);
        $tenK->setAttribute('report_name_link', '/content/1094new2015-16#3loRace');
        $actGroup->addComplianceView($tenK);

        $halfMar = new PlaceHolderComplianceView(null, 0);
        $halfMar->setMaximumNumberOfPoints(100);
        $halfMar->setName('half_mar');
        $halfMar->setReportName('Participate in a half-marathon or Sprint distance triathlon');
        $halfMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a half-marathon or Sprint distance triathlon');
        $halfMar->setAttribute('points_per_activity', 50);
        $halfMar->setAttribute('report_name_link', '/content/1094new2015-16#3loRace');
        $actGroup->addComplianceView($halfMar);

        $other = new PlaceHolderComplianceView(null, 0);
        $other->setMaximumNumberOfPoints(100);
        $other->setName('other');
        $other->setReportName('Other HWAG Events');
        $other->setAttribute('points_per_activity', 50);
        $other->setStatusSummary(ComplianceStatus::COMPLIANT, 'Donate Blood CPR/AED Certified');
        $other->setAttribute('report_name_link', '/content/1094new2015-16#3pother');
        $other->addLink(new FakeLink('Admin will Enter', '#'));
        $actGroup->addComplianceView($other);

        $this->addComplianceViewGroup($actGroup);

        $quarterGroup = new ComplianceViewGroup('quarterly', 'UCAN HealthTrails Walking Challenges*');
        $quarterGroup->setPointsRequiredForCompliance(0);

        $walkJulyAug = new PlaceHolderComplianceView(null, 0);
        $walkJulyAug->setReportName('July 16th-August 26th');
        $walkJulyAug->setName('walk_july_aug');
        $walkJulyAug->setStatusSummary(ComplianceStatus::COMPLIANT, 'July 16th-August 26th');
        $walkJulyAug->setMaximumNumberOfPoints(40);
        $walkJulyAug->setAttribute('points_per_activity', 40);
        $walkJulyAug->setAttribute('report_name_link', '/content/1094new2015-16#4acInd');
        $walkJulyAug->addLink(new Link('<br />Submit Form', '/content/chp-document-uploader'));
        $quarterGroup->addComplianceView($walkJulyAug);

        $walkSepOct = new PlaceHolderComplianceView(null, 0);
        $walkSepOct->setReportName('September 17th-October 28th');
        $walkSepOct->setName('walk_sep_oct');
        $walkSepOct->setStatusSummary(ComplianceStatus::COMPLIANT, 'September 17th-October 28th');
        $walkSepOct->setMaximumNumberOfPoints(40);
        $walkSepOct->setAttribute('points_per_activity', 40);
        $walkSepOct->setAttribute('report_name_link', '/content/1094new2015-16#4acInd');
        $quarterGroup->addComplianceView($walkSepOct);

        $walkNovDec = new PlaceHolderComplianceView(null, 0);
        $walkNovDec->setReportName('November 19th-December 30th');
        $walkNovDec->setName('walk_nov_dec');
        $walkNovDec->setStatusSummary(ComplianceStatus::COMPLIANT, 'November 19th-December 30th');
        $walkNovDec->setMaximumNumberOfPoints(40);
        $walkNovDec->setAttribute('points_per_activity', 40);
        $walkNovDec->setAttribute('report_name_link', '/content/1094new2015-16#4acInd');
        $quarterGroup->addComplianceView($walkNovDec);
        

        $teamPart = new PlaceHolderComplianceView(null, 0);
        $teamPart->setMaximumNumberOfPoints(120);
        $teamPart->setName('team_participate');
        $teamPart->setReportName('UCAN Individual Walking Challenge Winner');
        $teamPart->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in Health Trails Individual Walking Challenge.  ');
        $teamPart->setAttribute('points_per_activity', 40);
        $teamPart->addLink(new Link('Submit Form <br />', '/content/chp-document-uploader'));
        $teamPart->addLink(new Link('Team Leaderboard', '/content/ucan-fitbit-leaderboards?type=team'));
        $teamPart->setAttribute('report_name_link', '/content/1094new2015-16#4dTeam');
        $quarterGroup->addComplianceView($teamPart);

        $teamWinner = new PlaceHolderComplianceView(null, 0);
        $teamWinner->setMaximumNumberOfPoints(120);
        $teamWinner->setName('team_winner');
        $teamWinner->setReportName('UCAN Team Walking Challenge Winners');
        $teamWinner->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in Health Trails Team Walking Challenge.  ');
        $teamWinner->setAttribute('points_per_activity', 40);
        $teamWinner->addLink(new Link('Submit Form <br />', '/content/chp-document-uploader'));
        $teamWinner->addLink(new Link('Team Leaderboard', '/content/ucan-fitbit-leaderboards?type=team'));
        $teamWinner->setAttribute('report_name_link', '/content/1094new2015-16#4eTeamWin');
        $quarterGroup->addComplianceView($teamWinner);

        $this->addComplianceViewGroup($quarterGroup);
    }
}

class UCAN2018ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);
        $this->setShowTotal(true);

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
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>
        <div style="padding: 10px 0; color:red;">
           *HealthTrails included within BCBS-IL package <br />
           **Submit proof to HMI Portal to receive points.
        </div>

        <div>
            <strong style="color:red;">HealthTrails Overview:</strong>  <br />
            What could be more fun than tracking health improvement on interactive trails from all over the world?
            Whether it's Spain's French Way, Denmark West Coast Trail, around the tropical city of Honolulu, or any
            of the other fascinating international trails, your employees are inspired to keep moving toward better
            health.
            <ul style="font-size:9pt;">
                <li>6-Week wellness challenges that work with Well onTarget</li>
                <li>Flexible design fits people of all abilities</li>
                <li>Fun, effective way to track health habits as you record your HealthTrails activity to move along the virtual trail</li>
                <li>Fuel success with easy-to-make recipes, resource page, and communication to ensure accountability</li>
            </ul>
        </div>

        <div>
            <strong><u>Notice:</u></strong> <br/>
            <div style="font-size:9pt;">
                2-Week Registration period prior to start date. This will allow members to recruit, and
                attract participation to take on the virtual walking trails. Members can take on the virtual trails as an
                individual or team challenge (1 leader up to 4 members per team).
            </div>
        </div>

        <div>* 2019 Dates will be announced beginning of December 2018. </div>

</div>

        <?php

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
                // Expand quarterly group header to be two columns
                $('.headerRow-quarterly th').attr('colspan', 2);
                $('.headerRow-quarterly td:first').remove();

                // Span rows for prev services / flushot
                $('.view-prev_serv td:first')
                    .html('<div style="text-align:center"><img src="/resources/4894/UCANwellnesslogo.jpg" alt="" /></div>' +
                    '<br/><strong>B</strong>. <a href="/content/1094#3bprev">Preventative Services</a>');

//
//                $('.view-flu_shot td:first').remove();



                // Span rows for quarterly challenges
                $('.view-big_win td:first')
                    .attr('rowspan', 4)
                    .html('<div style="text-align:center"><img src="/resources/4894/UCANwellnesslogo.jpg" alt="" /></div>' +
                    '<br/><strong>A-D</strong>. <a href="/content/1094#4awtloss">Quarterly Health Challenge</a>');

                $('.view-intune_stress td:first').remove();
                $('.view-lucky_7 td:first').remove();
                $('.view-eat_right td:first').remove();

                // Span rows for individual walking challenge
                $('.view-walk_july_aug td:first')
                    .attr('rowspan', 3)
                    .html('<strong>A</strong>. <a href="/content/1094#4iindwalk">HealthTrails Individual/Team Challenges</a>' +
                    '<br/><br/>Points will be awarded at the end of each 6-week challenge based on the average steps logged during the period (individual and team based). ');
                $('.view-walk_july_aug td:last').attr('rowspan', '3');
                $('.view-walk_sep_oct td:first').remove();
                $('.view-walk_sep_oct td:last').remove();
                $('.view-walk_nov_dec td:first').remove();
                $('.view-walk_nov_dec td:last').remove();

                $('.view-hmi_multi_challenge_8000 td:first').remove();
                $('.view-hmi_multi_challenge_10000 td:first').remove();

                // Remove first 2 cols from first group

                $('.headerRow-required td:first').remove();
                $('.headerRow-required td:first').remove();
                $('.view-complete_hra td:eq(1)').remove();
                $('.view-complete_screening td:eq(1)').remove();
                $('.view-visit_personal_physician td:eq(1)').remove();
                $('.view-complete_hra td:eq(1)').remove();
                $('.view-complete_screening td:eq(1)').remove();
                $('.view-visit_personal_physician td:eq(1)').remove();
                $('.headerRow-required th').attr('colspan', 3);
                $('.view-complete_hra td:eq(0)').attr('colspan', 3);
                $('.view-complete_screening td:eq(0)').attr('colspan', 3);
                $('.view-visit_personal_physician td:eq(0)').attr('colspan', 3);



                // Missing headers
                $('.headerRow-bonus td:eq(0)').html('Requirement');
                $('.headerRow-bonus td:eq(1)').html('Result');
                $('.headerRow-activities td:eq(0)').html('Requirement');
                $('.headerRow-activities td:eq(1)').html('Points Per Activity');
                $('.headerRow-quarterly td:eq(0)').html('Points Per Activity');

                // Span 5k/10k etc events
                $('.view-5k td:first')
                    .attr('rowspan', '4')
                    .html('<strong>L-O</strong>. Run/Walk a Race<br/><br/><p>' +
                    'In addition to earning points, <br/>' +
                    'Entry fees will be covered for UCAN sponsored races');

                $('.view-5k td:last').attr('rowspan', '4');
                $('.view-bike_race td:first').remove();
                $('.view-bike_race td:last').remove();
                $('.view-10k td:first').remove();
                $('.view-10k td:last').remove();
                $('.view-half_mar td:first').remove();
                $('.view-half_mar td:last').remove();


                // Replace normal space with a nonbreaking space to prevent word wrapping
                $('tr.view-complete_screening td.links a')[2].innerHTML = "MD&nbsp;Links";
            });
        </script>

        <div class="page-header">
            <h4>UCAN 2018-19 Wellness Program</h4>
        </div>

        <p>UCAN cares about your health! We have partnered with HMI Health and
            Axion RMS to implement our Wellness Program. The wellness
            program provides you with fun, robust programming options geared
            towards specific areas of your health that need improvement. Take
            action and commit to a healthier, happier life with your Wellness Program</p>


        <p style="font-weight:bold;text-align:center;">
            HOW DOES THE PROGRAM WORK?</p>

        <p><strong>Employees that complete the 2018 Health Screening and Health Power Assessment (HPA) are
                eligible to participate.</strong>
            Participation in the program will earn wellness points that will be tracked according to the table below.
            Rewards will be based on points earned between 7/1/2018 and 6/31/2019.</p>

        <p> Participants can earn points in the UCAN Be Health Program by achieving designated health OUTCOMES and
            through participating in the program activities. Employees earn <strong>cash rewards</strong> when they reach the
            designated points for each of the levels outlines in the chart below. <strong>The maximum cash reward available
                per year is $450!</strong></p>

        <table style="width:100%" id="status-table">
            <tr>
                <th>Status Level</th>
                <th>Participation</th>
                <th>Points</th>
                <th>Reward</th>
            </tr>
            <tr>
                <td>Bronze</td>
                <td>Health Power Assessment (HPA) and Health Screening</td>
                <td><strong>25 Total Points</strong></td>
                <td>$50</td>
            </tr>
            <tr>
                <td>Silver</td>
                <td>Accumulate 75 points</td>
                <td><strong>75 Total Points</strong></td>
                <td>$75</td>
            </tr>
            <tr>
                <td>Gold</td>
                <td>Complete Silver level and accumulate 75 additional points</td>
                <td><strong>150 Total Points</strong></td>
                <td>$125</td>
            </tr>
            <tr>
                <td>Platinum</td>
                <td>Complete Silver and Gold levels and accumulate 100 additional points</td>
                <td><strong>250 Total Points</strong></td>
                <td>$200</td>
            </tr>
        </table>


        <p style="text-align:center">Compliance reports will be generated
            monthly and rewards will be distributed via payroll as earned.</p>
        <?php
    }
}
