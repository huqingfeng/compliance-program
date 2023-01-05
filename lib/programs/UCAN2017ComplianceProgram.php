<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class UcanMultipleAverageStepsComplianceView extends ComplianceView
{
    public function __construct($threshold, $pointsPer)
    {
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
    }

    public function addDateRange($startDate, $endDate)
    {
        $this->ranges[] = array($startDate, $endDate);
    }

    public function addSummaryDateRange($name, $startDate, $endDate)
    {
        $this->summaryRanges[$name] = array($startDate, $endDate);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "hmi_multi_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Multi Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $points = 0;

        $steps = array();

        $totalSteps = array();

        $attributes = array();

        foreach($this->summaryRanges as $name => $range) {
            $view = new UcanAverageStepsComplianceView($range[0], $range[1], $this->threshold);
            $view->setComplianceViewGroup($this->getComplianceViewGroup());
            $viewStatus = $view->getStatus($user);
            $attributes[$name] = $viewStatus->getAttribute('total_steps', 0);
        }

        foreach($this->ranges as $range) {
            $view = new UcanAverageStepsComplianceView($range[0], $range[1], $this->threshold);

            $view->setComplianceViewGroup($this->getComplianceViewGroup());

            $viewStatus = $view->getStatus($user);

            if($viewStatus->isCompliant()) {
                $points += $this->pointsPer;
            }

            $steps[] = $viewStatus->getAttribute('average_daily_steps', 0);
            $totalSteps[] = $viewStatus->getAttribute('total_steps', 0);
        }

        $status = new ComplianceViewStatus($this, null, $points);

        foreach($attributes as $aName => $aValue) {
            $status->setAttribute($aName, $aValue);
        }

        $status->setAttribute('average_daily_steps', implode('; ', $steps));
        $status->setAttribute('total_steps', implode('; ', $totalSteps));


        return $status;
    }

    private $threshold;
    private $pointsPer;
    private $ranges = array();
    private $summaryRanges = array();
}

class UcanAverageStepsComplianceView extends DateBasedComplianceView
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
        require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/moves/lib/model/movesApi.php';

        $fitbitData = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        $movesData = MovesApi::getMovesData($user, true);

        $averageDailySteps = 0;
        if(isset($fitbitData['average_daily_steps']) && $fitbitData['average_daily_steps'] > $averageDailySteps) {
            $averageDailySteps = $fitbitData['average_daily_steps'];
        }

        if(isset($movesData['average_daily_steps']) && $movesData['average_daily_steps'] > $averageDailySteps) {
            $averageDailySteps = $movesData['average_daily_steps'];
        }

        $status = new ComplianceViewStatus(
            $this,
            $averageDailySteps > $this->threshold ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT
        );

        $status->setAttribute('average_daily_steps', isset($averageDailySteps) ? $averageDailySteps : 0);
        $status->setAttribute('total_steps', isset($fitbitData['total_steps']) ? $fitbitData['total_steps'] : 0);


        return $status;
    }

    private $threshold;
}


class UCAN2017ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new UCAN2017ComplianceProgramReportPrinter();

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('Steps', function(ComplianceProgramStatus $status) {
            return $status->getComplianceViewStatus('walk_10k')->getAttribute('total_steps');
        });

        $printer->addStatusFieldCallback('Steps 03/01/2017 - 03/31/2017', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2017-03-01', '2017-03-31');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $reqGroup = new ComplianceViewGroup('required', 'Required Activities');

        $hra = new CompleteHRAComplianceView("2017-05-12", "2018-05-31");
        $hra->setReportName('Employee completes the Health Power Assessment');
        $hra->setAttribute('report_name_link', '/content/1094new2015-16#1ahpa');
        $reqGroup->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView("2017-05-12", "2018-05-31");
        $scr->setReportName('Employee participates in the Offsite Wellness Screening (PPO members only)');
        $scr->setAttribute('report_name_link', '/content/1094new2015-16#1bannscreen');
        $scr->emptyLinks();
        $scr->addLink(new Link('Sign-Up', '/compliance/hmi-2016/schedule/content/wms2-appointment-center'));
        $scr->addLink(new Link('Results', '/compliance/hmi-2016/my-health'));
        $scr->addLink(new Link('MD Form', '/resources/9441/UCAN MD Form 2017.pdf'));
        $reqGroup->addComplianceView($scr);

        $this->addComplianceViewGroup($reqGroup);

        $bonusGroup = new ComplianceViewGroup('bonus', 'Biometric Bonus Points');
        $bonusGroup->setPointsRequiredForCompliance(0);

        $biometricStartDate = '2017-05-12';

        $hraScore65 = new HraScoreComplianceView($biometricStartDate, $endDate, 65);
        $hraScore65->setReportName('Receive a Health Power Score >= 65');
        $hraScore65->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $hraScore65->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 65');
        $hraScore65->setAttribute('points_per_activity', 5);
        $hraScore65->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bonusGroup->addComplianceView($hraScore65);

        $hraScore70 = new HraScoreComplianceView($biometricStartDate, $endDate, 70);
        $hraScore70->setReportName('Receive a Health Power Score >= 70');
        $hraScore70->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $hraScore70->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 70');
        $hraScore70->setAttribute('points_per_activity', 5);
        $hraScore70->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bonusGroup->addComplianceView($hraScore70);

        $hraScore80 = new HraScoreComplianceView($biometricStartDate, $endDate, 80);
        $hraScore80->setReportName('Receive a Health Power Score >= 80');
        $hraScore80->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $hraScore80->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 80');
        $hraScore80->setAttribute('points_per_activity', 10);
        $hraScore80->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bonusGroup->addComplianceView($hraScore80);

        $hraScore90 = new HraScoreComplianceView($biometricStartDate, $endDate, 90);
        $hraScore90->setReportName('Receive a Health Power Score >= 90');
        $hraScore90->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $hraScore90->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a Health Power Score >= 90');
        $hraScore90->setAttribute('points_per_activity', 10);
        $hraScore90->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $bonusGroup->addComplianceView($hraScore90);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($biometricStartDate, $endDate);
        $glucoseView->setReportName('Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $glucoseView->setAttribute('points_per_activity', 10);
        $glucoseView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setStatusSummary(ComplianceStatus::COMPLIANT, '<100');
        $bonusGroup->addComplianceView($glucoseView);

        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($biometricStartDate, $endDate);
        $tcView->setReportName('Total Cholesterol');
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $tcView->setAttribute('points_per_activity', 10);
        $tcView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setStatusSummary(ComplianceStatus::COMPLIANT, '<200');
        $bonusGroup->addComplianceView($tcView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($biometricStartDate, $endDate);
        $hdlView->setReportName('HDL Cholesterol');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $hdlView->setAttribute('points_per_activity', 10);
        $hdlView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $hdlView->overrideTestRowData(null, 50, null, null);
        $hdlView->setStatusSummary(ComplianceStatus::COMPLIANT, '>= 50');
        $bonusGroup->addComplianceView($hdlView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($biometricStartDate, $endDate);
        $ldlView->setReportName('LDL Cholesterol');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $ldlView->setAttribute('points_per_activity', 10);
        $ldlView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $ldlView->overrideTestRowData(null, null, 99.999, null);
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, '< 100');
        $bonusGroup->addComplianceView($ldlView);

        $cholesterolHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($biometricStartDate, $endDate);
        $cholesterolHDLRatioView->setReportName('TC/HDL Ratio');
        $cholesterolHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $cholesterolHDLRatioView->setAttribute('points_per_activity', 10);
        $cholesterolHDLRatioView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $cholesterolHDLRatioView->overrideTestRowData(null, null, 4.4, null);
        $cholesterolHDLRatioView->setStatusSummary(ComplianceStatus::COMPLIANT, '<= 4.4');
        $bonusGroup->addComplianceView($cholesterolHDLRatioView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($biometricStartDate, $endDate);
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $triglyceridesView->setReportName('Triglycerides');
        $triglyceridesView->setAttribute('points_per_activity', 10);
        $triglyceridesView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setStatusSummary(ComplianceStatus::COMPLIANT, '< 150');
        $bonusGroup->addComplianceView($triglyceridesView);

        $systolicView = new ComplyWithSystolicBloodPressureScreeningTestComplianceView($biometricStartDate, $endDate);
        $systolicView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $systolicView->setReportName('Systolic Blood Pressure');
        $systolicView->setAttribute('points_per_activity', 5);
        $systolicView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $systolicView->overrideTestRowData(null, null, 125, null);
        $systolicView->setStatusSummary(ComplianceStatus::COMPLIANT, '<= 125');
        $bonusGroup->addComplianceView($systolicView);

        $diastolicView = new ComplyWithDiastolicBloodPressureScreeningTestComplianceView($biometricStartDate, $endDate);
        $diastolicView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $diastolicView->setReportName('Diastolic Blood Pressure');
        $diastolicView->setAttribute('points_per_activity', 5);
        $diastolicView->setAttribute('report_name_link', '/content/1094new2015-16#2abiometrics');
        $diastolicView->overrideTestRowData(null, null, 85, null);
        $diastolicView->setStatusSummary(ComplianceStatus::COMPLIANT, '<= 85');
        $bonusGroup->addComplianceView($diastolicView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($biometricStartDate, $endDate);
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
        $phyExam->addLink(new Link('Preventive Care Certification Form', '/resources/9435/2017_PreventiveCare Cert.pdf'));
        $phyExam->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $phyExam->setStatusSummary(ComplianceStatus::COMPLIANT, 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam');
        $phyExam->setAttribute('points_per_activity', 20);
        $phyExam->setAttribute('report_name_link', '/content/1094new2015-16#3aannphys');
        $actGroup->addComplianceView($phyExam);

        $prevServ = new PlaceHolderComplianceView(null, 0);
        $prevServ->setMaximumNumberOfPoints(30);
        $prevServ->setName('prev_serv');
        $prevServ->setReportName('Preventative Services');
        $prevServ->addLink(new Link('Preventive Care Certification Form', '/resources/9435/2017_PreventiveCare Cert.pdf'));
        $prevServ->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $prevServ->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a preventative service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.  See attached wellness guides or check with your personal physician for necessary tests');
        $prevServ->setAttribute('points_per_activity', 10);
        $prevServ->setAttribute('report_name_link', '/content/1094new2015-16#3bprev');
        $actGroup->addComplianceView($prevServ);

        $fluShot = new PlaceHolderComplianceView(null, 0);
        $fluShot->setMaximumNumberOfPoints(10);
        $fluShot->setName('flu_shot');
        $fluShot->setReportName('Preventative Services - Flu Shot');
        $fluShot->addLink(new Link('Preventive Care Certification Form', '/resources/9435/2017_PreventiveCare Cert.pdf'));
        $fluShot->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $fluShot->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive a flu shot');
        $fluShot->setAttribute('points_per_activity', 10);
        $fluShot->setAttribute('report_name_link', '/content/1094new2015-16#3cflushot');
        $actGroup->addComplianceView($fluShot);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(25);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new FakeLink('Admin will enter<br />', '#'));
        $smoking->addLink(new Link('Illinois Tobacco Quitline', 'http://www.quityes.org'));
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
        $onMyTime->setReportName('OnMyTime Courses');
        $onMyTime->addLink(new FakeLink('Admin will enter<br />', '#'));
        $onMyTime->addLink(new Link('Well On Target', 'http://www.wellontarget.com/'));
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
        $employeeBenefits->setStatusSummary(ComplianceStatus::COMPLIANT, 'Attend the Employee Benefits Fair in June 2015');
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
        $nutProgram->setName('nut_program');
        $nutProgram->setReportName('Health/Nutrition Program Annual Membership');
        $nutProgram->addLink(new Link('HWAG Certification Form', '/resources/9432/2017_nonHWAG Event Cert.pdf'));
        $nutProgram->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $nutProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'Membership in health program such as health club, weight watchers, etc. or a healthy food program such as a Fruit of the month club, Community Supported Agriculture, etc');
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
        $fiveK->addLink(new Link('HWAG Certification Form', '/resources/9432/2017_nonHWAG Event Cert.pdf'));
        $fiveK->addLink(new Link('<br />Submit Form', '/content/chp-document-uploader'));
        $fiveK->setAttribute('report_name_link', '/content/1094new2015-16#3loRace');
        $actGroup->addComplianceView($fiveK);

        $tenK = new PlaceHolderComplianceView(null, 0);
        $tenK->setMaximumNumberOfPoints(60);
        $tenK->setName('10k');
        $tenK->setReportName('Participate in a 10K');
        $tenK->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a 10K');
        $tenK->setAttribute('points_per_activity', 30);
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

        $fullMar = new PlaceHolderComplianceView(null, 0);
        $fullMar->setMaximumNumberOfPoints(100);
        $fullMar->setName('full_mar');
        $fullMar->setReportName('Participate in a marathon or Olympic distance triathlon');
        $fullMar->setStatusSummary(ComplianceStatus::COMPLIANT, 'Participate in a marathon or Olympic distance triathlon');
        $fullMar->setAttribute('points_per_activity', 100);
        $fullMar->setAttribute('report_name_link', '/content/1094new2015-16#3loRace');
        $actGroup->addComplianceView($fullMar);

        $other = new PlaceHolderComplianceView(null, 0);
        $other->setMaximumNumberOfPoints(100);
        $other->setName('other');
        $other->setReportName('Other HWAG Events');
        $other->setAttribute('report_name_link', '/content/1094new2015-16#3pother');
        $other->addLink(new FakeLink('Complete Quiz Admin will Enter', '#'));
        $actGroup->addComplianceView($other);

        $this->addComplianceViewGroup($actGroup);

        $quarterGroup = new ComplianceViewGroup('quarterly', 'FitBit Walking Challenges**');
        $quarterGroup->setPointsRequiredForCompliance(0);

        $walk6000 = new UcanMultipleAverageStepsComplianceView(6000, 3);
        $walk6000->setReportName('Walk an average of 6,000 steps/day');
        $walk6000->setName('walk_6k');
        $walk6000->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 6,000 steps/day');
        $walk6000->setMaximumNumberOfPoints(36);
        $walk6000->setAttribute('points_per_activity', 3);
        $walk6000->addLink(new Link('Fitbit Sync <br /><br />', '/content/ucan-fitbit-individual'));
        $walk6000->addLink(new Link('Moves Sync <br /><br />', '/standalone/moves'));
        $walk6000->addLink(new Link('Jawbone Sync <br />', '/standalone/jawbone'));
        $walk6000->setAttribute('report_name_link', '/content/1094new2015-16#4acInd');
        $quarterGroup->addComplianceView($walk6000);

        $walk8000 = new UcanMultipleAverageStepsComplianceView(8000, 6);
        $walk8000->setReportName('Walk an average of 8,000 steps/day');
        $walk8000->setName('walk_8k');
        $walk8000->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 8,000 steps/day');
        $walk8000->setMaximumNumberOfPoints(72);
        $walk8000->setAttribute('points_per_activity', 6);
        $walk8000->setAttribute('report_name_link', '/content/1094new2015-16#4acInd');
        $quarterGroup->addComplianceView($walk8000);

        $walk10000 = new UcanMultipleAverageStepsComplianceView(10000, 10);
        $walk10000->setReportName('Walk an average of 10,000 steps/day');
        $walk10000->setName('walk_10k');
        $walk10000->setStatusSummary(ComplianceStatus::COMPLIANT, 'Walk an average of 10,000 steps/day');
        $walk10000->setMaximumNumberOfPoints(120);
        $walk10000->setAttribute('points_per_activity', 10);
        $walk10000->setAttribute('report_name_link', '/content/1094new2015-16#4acInd');
        $quarterGroup->addComplianceView($walk10000);

        $ranges = array(
            array('2017-06-01', '2017-06-31'),
            array('2017-07-01', '2017-07-31'),
            array('2017-08-01', '2017-08-31'),
            array('2017-09-01', '2017-09-31'),
            array('2017-10-01', '2017-10-30'),
            array('2017-11-01', '2017-11-30'),
            array('2017-12-01', '2017-12-30'),
            array('2018-01-01', '2018-01-31'),
            array('2018-02-01', '2018-02-31'),
            array('2018-03-01', '2018-03-31'),
            array('2018-04-01', '2018-04-31'),
            array('2018-05-01', '2018-05-31')
        );

        foreach($ranges as $dateRanges) {
            $walk6000->addDateRange($dateRanges[0], $dateRanges[1]);
            $walk8000->addDateRange($dateRanges[0], $dateRanges[1]);
            $walk10000->addDateRange($dateRanges[0], $dateRanges[1]);
        }

        $teamPart = new PlaceHolderComplianceView(null, 0);
        $teamPart->setMaximumNumberOfPoints(150);
        $teamPart->setName('team_participate');
        $teamPart->setReportName('UCAN Walking Challenge');
        $teamPart->setStatusSummary(ComplianceStatus::COMPLIANT, 'Must average 4,000 steps/day during challenge to receive credit.');
        $teamPart->setAttribute('points_per_activity', 25);
        $teamPart->addLink(new FakeLink('Admin will enter', '#'));
        $teamPart->addLink(new Link('Team Leaderboard', '/content/ucan-fitbit-leaderboards?type=team'));
        $teamPart->setAttribute('report_name_link', '/content/1094new2015-16#4dTeam');
        $quarterGroup->addComplianceView($teamPart);

        $teamWinner = new PlaceHolderComplianceView(null, 0);
        $teamWinner->setMaximumNumberOfPoints(60);
        $teamWinner->setName('team_winner');
        $teamWinner->setReportName('UCAN Walking Challenge Winner');
        $teamWinner->setStatusSummary(ComplianceStatus::COMPLIANT, 'Team that wins the HWAG Team Walking Challenge');
        $teamWinner->setAttribute('points_per_activity', 10);
        $teamWinner->addLink(new FakeLink('Admin will enter', '#'));
        $teamWinner->addLink(new Link('Team Leaderboard', '/content/ucan-fitbit-leaderboards?type=team'));
        $teamWinner->setAttribute('report_name_link', '/content/1094new2015-16#4eTeamWin');
        $quarterGroup->addComplianceView($teamWinner);

        $this->addComplianceViewGroup($quarterGroup);
    }
}

class UCAN2017ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
        <div style="padding: 10px 0;">
            **Walking Program Participation Fee Required
        </div>

        <div>
            Cost for the UCAN Walking Program is $30 for the 1-year incentive period and will be deducted via payroll
            ($1.50 per pay period for 20 periods). REGISTRATION IS REQUIRED at: <a href="https://goo.gl/forms/Pq8VPtfg8JA4egDg2">https://goo.gl/forms/Pq8VPtfg8JA4egDg2</a> <br />
            <ul style="font-size:9pt;">
                <li>In addition to Fitbit, participants may now use Jawbone devices or the Moves App
                (for iPhone or Android) to directly sync their activity to the HMI Wellness Portal. </li>
                <li>Only the Fitbit Zip device will be available for purchase via payroll deduction. DEADLINE TO ORDER
                 IS FRIDAY, JUNE 16TH, 2017. Cost is $80 and includes the Fitbit Zip device AND tracking for the 1-year
                  incentive period ($4.00 per pay period for 20 periods)</li>
            </ul>
        </div>

        <div>
            <strong><u>Notice:</u></strong> <br/>
            <div style="font-size:9pt;">
                The UCAN BeHealthy Wellness Program is committed to helping you achieve your best health. Rewards for
                participating in a wellness progam are available to all employees. If you think you might be unable to
                meet a standard for a reward under this wellness program, you might qualify for an opportunity to earn
                the same reward by different means. Contact HMI at 847-635-6580 and we will work with you (and, if you
                wish, your doctor) to find a wellness program with the same reward that is right for you in light of
                your health status.
            </div>
        </div>

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
                $('.view-walk_6k td:first')
                    .attr('rowspan', 3)
                    .html('<strong>A</strong>. <a href="/content/1094#4iindwalk">Monthly Individual Walking Challenge</a>' +
                    '<br/><br/>Points will be awarded at the end of' +
                    ' each quarter based on the average steps logged' +
                    ' during the period.  Max Points listed are for' +
                    ' the entire year, all 4 quarters.');
                $('.view-walk_6k td:last').attr('rowspan', '3');
                $('.view-walk_8k td:first').remove();
                $('.view-walk_8k td:last').remove();
                $('.view-walk_10k td:first').remove();
                $('.view-walk_10k td:last').remove();

                $('.view-hmi_multi_challenge_8000 td:first').remove();
                $('.view-hmi_multi_challenge_10000 td:first').remove();

                // Remove first 2 cols from first group

                $('.headerRow-required td:first').remove();
                $('.headerRow-required td:first').remove();
                $('.view-complete_hra td:eq(1)').remove();
                $('.view-complete_screening td:eq(1)').remove();
                $('.view-complete_hra td:eq(1)').remove();
                $('.view-complete_screening td:eq(1)').remove();
                $('.headerRow-required th').attr('colspan', 3);
                $('.view-complete_hra td:eq(0)').attr('colspan', 3);
                $('.view-complete_screening td:eq(0)').attr('colspan', 3);

                //add a row below 1B screening
                $('.view-complete_screening').after(
                    '<tr><td colspan="3"><strong>B</strong>. Visits Personal Physician for screening & lab work (all HMO members; alternative to offsite screening for PPO members)</td></tr>'
                );
                $('.view-complete_screening td:eq(1)').attr('rowspan', 2);
                $('.view-complete_screening td:eq(2)').attr('rowspan', 2);
                $('.view-complete_screening td:eq(3)').attr('rowspan', 2);


                // Missing headers
                $('.headerRow-bonus td:eq(0)').html('Requirement');
                $('.headerRow-bonus td:eq(1)').html('Result');
                $('.headerRow-activities td:eq(0)').html('Requirement');
                $('.headerRow-activities td:eq(1)').html('Points Per Activity');
                $('.headerRow-quarterly td:eq(0)').html('Points Per Activity');

                // Span 5k/10k etc events
                $('.view-5k td:first')
                    .attr('rowspan', '4')
                    .html('<strong>L-O</strong>. Run/Walk a Race<br/><br/><p>In addition to ' +
                    'earning points, Entry fees will be covered ' +
                    'for UCAN sponsored races: <br/> ' +
                    '&bull; Lawndale 5k (Sept) <br/> ' +
                    '&bull; AIDS Run/Walk (Oct) <br/> ' +
                    '&bull; Turkey Trot (Nov) <br/> ' +
                    '&bull; Earth Day 5K (April) <br/> ');

                $('.view-5k td:last').attr('rowspan', '4');
                $('.view-10k td:first').remove();
                $('.view-10k td:last').remove();
                $('.view-half_mar td:first').remove();
                $('.view-half_mar td:last').remove();
                $('.view-full_mar td:first').remove();
                $('.view-full_mar td:last').remove();

                // Replace normal space with a nonbreaking space to prevent word wrapping
                $('tr.view-complete_screening td.links a')[2].innerHTML = "MD&nbsp;Links";
            });
        </script>

        <div class="page-header">
            <h4>UCAN 2017-18 Wellness Program</h4>
        </div>

        <p>UCAN cares about your health! We have partnered with HMI Health and
            Axion RMS to implement our Wellness Program. The wellness
            program provides you with fun, robust programming options geared
            towards specific areas of your health that need improvement. This
            Wellness Program is your way to better, healthier living.</p>


        <p style="font-weight:bold;text-align:center;">
            HOW DOES THE PROGRAM WORK?</p>

        <p><strong>Employees that complete the 2017 Health Screening and Health Power Assessment (HPA) are
                eligible to participate.</strong>
            Participation in the program will earn wellness points that will be tracked according to the table below.
            Rewards will be based on points earned between 6/1/17 and 5/31/2018.</p>

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
                <td>Silver</td>
                <td>Accumulate 75 points</td>
                <td><strong>75 Total Points</strong></td>
                <td>$100</td>
            </tr>
            <tr>
                <td>Gold</td>
                <td>Complete Silver levels and accumulate 75 additional points</td>
                <td><strong>150 Total Points</strong></td>
                <td>$150</td>
            </tr>
            <tr>
                <td>Platinum</td>
                <td>Complete Silver and Gold levels and accumulate 100 additional points</td>
                <td><strong>250 Total Points</strong></td>
                <td>$200</td>
            </tr>
        </table>

        <p style="color: red">
            Please note that participants do not receive a $75 reward just for completing the screening, but that scoring in
            the healthy range on many of the biometrics may result in reaching the Silver level immediately!
        </p>

        <p style="text-align:center">Compliance reports will be generated
            monthly and rewards will be distributed via payroll as earned.</p>
        <?php
    }
}
