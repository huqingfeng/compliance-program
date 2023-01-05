<?php

use hpn\steel\query\SelectQuery;

// class getAndAssembleFitnessData
// {
//     public function __construct($user, $token, $device)
//     {

//         $fitnessTrackingData['token']=$token;
//         $fitnessTrackingData['device']=$device;
//         $fitnessTrackingData['action']="walking";
//         $fitnessTrackingData['params']['pastDays']=30;
//         $fitnessTrackingData['params']['summary']=true;

//         require_once sfConfig::get('sf_root_dir').'/web/fitness/moves/wms3moves.php';


//         if (!isset($fitnessTracking->data)) {
//             $this->output = 0;
//         } else {
//             $this->output = ceil($fitnessTracking->data / 30);
//         }
//     }
// }

class LCMH2019ElearningComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->startDate = ctype_digit($startDate) ? $startDate : strtotime($startDate);
        $this->endDate = ctype_digit($endDate) ? $endDate : strtotime($endDate);

        if($this->startDate === false) {
            throw new \InvalidArgumentException("Invalid start date: $startDate");
        }

        if($this->endDate === false) {
            throw new \InvalidArgumentException("Invalid end: $endDate");
        }
    }

    public function getStatus(User $user)
    {
        $elearningDimentions = array(
            'physical',
            'emotional',
            'financial',
            'spiritual',
            'environmental',
            'career',
            'community',
            'intellectual'
        );

        $points = 0;
        foreach($elearningDimentions as $elearningDimention) {
            $elearningComplianceView  = new CompleteELearningGroupSet($this->startDate, $this->endDate, $elearningDimention);
            $elearningComplianceView->setComplianceViewGroup($this->getComplianceViewGroup());
            $elearningComplianceView->setNumberRequired(2);
            if($elearningComplianceView->getStatus($user)->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $points += 5;
            }
        }


        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, $points);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultReportName()
    {
        return 'Weight';
    }

    public function getDefaultName()
    {
        return 'android_weight';
    }

    public function setAllowPointsOverride($boolean)
    {
        $this->allowPointsOverride = $boolean;
    }


    private $allowPointsOverride = null;
    private $startDate;
    private $endDate;
}


class LCMH2019ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new LCMH2019ComplianceProgramReportPrinter();

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);
        $printer->setShowCompliant(false, null, null);
        $printer->setShowPoints(false,null,null);
        $printer->setShowComment(false,null,null);

        $printer->addStatusFieldCallback('Steps 08/01/2017 - 10/31/2018', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = get_all_fitbit_data($user->id, '2017-08-01', '2018-10-31');

            return isset($data['total_steps']) ? $data['total_steps'] : 0;
        });


        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $data['Compliance Program - Compliant'] = $status->isCompliant() ? 'Yes' : 'No';
            $data['Compliance Program - Points'] =  $status->getPoints() -
            $status->getComplianceViewStatus('fitbit')->getPoints();
            $data['Compliance Program - Comment'] = $status->getComment();

            return $data;
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $screeningStartDate = '2018-11-01';

        $actGroup = new ComplianceViewGroup('activities', 'Annual / Self-Care Wellness Activities');
        $actGroup->setPointsRequiredForCompliance(0);

        $selfCareSection = 'Time Out for Wellness:  Self-Care & Wellness Activities';

        $hraView = new CompleteHRAComplianceView($startDate, $endDate, false);
        $hraView->setMaximumNumberOfPoints(10);
        $hraView->setReportName('HPA Questionnaire');
        $hraView->setName('hra');
        $hraView->setAttribute('points_per_activity', '10');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $hraView->setAttribute('requirements', 'Complete the Health Power Assessment online.');
        $hraView->emptyLinks();
        $hraView->addLink(new Link(sprintf('Take %s', sfConfig::get('app_legacy_hra_abbreviation', 'HRA')), '/content/989'));
        $actGroup->addComplianceView($hraView, null, $selfCareSection);

        $screeningView = new CompleteScreeningComplianceView($screeningStartDate, $endDate);
        $screeningView->setMaximumNumberOfPoints(75);
        $screeningView->setReportName('Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->setAttribute('points_per_activity', '75');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0 ,0));
        $screeningView->setAttribute('requirements', '<span style="color: red;">Visit your personal physician for your wellness screening. Print out HP Form to take with you to your doctor.</span>');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $screeningView->addLink(new Link('Health Provider Form', '/resources/10213/LCMH_HP_Form_2018.pdf'));
        $actGroup->addComplianceView($screeningView, null, $selfCareSection);        

        // $hraScreeningView = new CompleteHRAAndScreeningComplianceView($screeningStartDate, $endDate);
        // $hraScreeningView->setMaximumNumberOfPoints(50);
        // $hraScreeningView->setReportName('Wellness Screening & HRA Questionnaire');
        // $hraScreeningView->setName('hra_screening');
        // $hraScreeningView->setAttribute('points_per_activity', '50');
        // $hraScreeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0 ,0));
        // $hraScreeningView->setAttribute('requirements', '<span style="color: red;">Employees must complete the wellness screening or Physical Exam and HRA to be eligible for the wellness program and incentive rewards</span>');
        // $actGroup->addComplianceView($hraScreeningView, null, $selfCareSection);

        // $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 290, 50);
        // $annualPhysicalExamView->setMaximumNumberOfPoints(50);
        // $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening follow up');
        // $annualPhysicalExamView->setName('annual_physical_exam');
        // $annualPhysicalExamView->setAttribute('points_per_activity', '50');
        // $annualPhysicalExamView->emptyLinks();
        // $annualPhysicalExamView->addLink(new Link('Verification Form', '/resources/10198/LCMH Healthcare Provider Form 2018.pdf'));
        // $annualPhysicalExamView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        // $annualPhysicalExamView->setAttribute('requirements', 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam.');
        // $actGroup->addComplianceView($annualPhysicalExamView, null, $selfCareSection);

        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($screeningStartDate, $endDate);
        $tcView->setReportName('Total Cholesterol');
        $tcView->setName('cholesterol');
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $tcView->setAttribute('points_per_activity', 10);
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setAttribute('requirements', 'Total Cholesterol <200 mg/dL');
        $actGroup->addComplianceView($tcView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($screeningStartDate, $endDate);
        $hdlView->setReportName('HDL Cholesterol');
        $hdlView->setName('hdl');
        $hdlView->setAttribute('requirements', 'HDL Cholesterol > 35 mg/dL  (men); >40 mg/dL (women)');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $hdlView->setAttribute('points_per_activity', 10);
        $hdlView->overrideTestRowData(null, 35.001, null, null, 'M');
        $hdlView->overrideTestRowData(null, 40.001, null, null, 'F');
        $actGroup->addComplianceView($hdlView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($screeningStartDate, $endDate);
        $ldlView->setReportName('LDL Cholesterol');
        $ldlView->setName('ldl');
        $ldlView->setAttribute('requirements', 'LDL Cholesterol <100 mg/dL');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $ldlView->setAttribute('points_per_activity', 10);
        $ldlView->overrideTestRowData(null, null, 99.999, null);
        $actGroup->addComplianceView($ldlView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($screeningStartDate, $endDate);
        $triglyceridesView->setReportName('Triglycerides');
        $triglyceridesView->setName('triglycerides');
        $triglyceridesView->setAttribute('requirements', 'Triglycerides &le; 150 mg/dL');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $triglyceridesView->setAttribute('points_per_activity', 10);
        $triglyceridesView->overrideTestRowData(null, null, 150, 150);
        $actGroup->addComplianceView($triglyceridesView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($screeningStartDate, $endDate);
        $glucoseView->setReportName('Glucose');
        $glucoseView->setName('glucose');
        $glucoseView->setAttribute('requirements', 'Fasting Glucose < 100 mg/dL');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $glucoseView->setAttribute('points_per_activity', 10);
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $actGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStartDate, $endDate);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setAttribute('requirements', 'Blood Pressure < 130/85');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0 ,0));
        $bloodPressureView->setAttribute('points_per_activity', 10);
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, null);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, null);
        $actGroup->addComplianceView($bloodPressureView);

        $preventiveExamsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($startDate, $endDate, 10);
        $preventiveExamsView->configureActivity(259, 91, array(
            'Colonoscopy'   => 60,
            'Dental Exam'   => 24,
            'Mammogram'     => 24,
            'Pap Test'      => 36,
            'Physical Exam' => 36,
            'PSA Test'      => 60
        ));
        $preventiveExamsView->setReportName("Preventive Services");
        $preventiveExamsView->setMaximumNumberOfPoints(100);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('requirements', 'Receive a preventive service such as mammogram, prostate exam, immunizations (including Hospital flu shot), vaccines, eye & dental exams, colonoscopy, etc.');
        $preventiveExamsView->setAttribute('points_per_activity', '10');
        $preventiveExamsView->emptyLinks();
        $preventiveExamsView->addLink(new Link('Verification Form', '/resources/10198/LCMH Healthcare Provider Form 2018.pdf'));
        $preventiveExamsView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $actGroup->addComplianceView($preventiveExamsView);

        $donateBlood = new PlaceHolderComplianceView(null, 0);
        $donateBlood->setMaximumNumberOfPoints(20);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate blood');
        $donateBlood->addLink(new FakeLink('Admin will enter', '#'));
        $donateBlood->setAttribute('requirements', 'Donate blood');
        $donateBlood->setAttribute('points_per_activity', 5);
        $actGroup->addComplianceView($donateBlood);

        $elearningTotalView = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $elearningTotalView->setReportName('eLearning Lessons');
        $elearningTotalView->setName('elearning_total');
        $elearningTotalView->setMaximumNumberOfPoints(50);
        $elearningTotalView->setPointsPerLesson(5);
        $elearningTotalView->setAttribute('requirements', 'Complete Suggested eLearning lessons');
        $elearningTotalView->setAttribute('points_per_activity', '5');
        $elearningTotalView->addLink(new Link('Elearning Center', '/content/9420?action=lessonManager&tab_alias=required'));
        $actGroup->addComplianceView($elearningTotalView);

        $smoking = new PlaceHolderComplianceView(null, 0);
        $smoking->setMaximumNumberOfPoints(20);
        $smoking->setName('smoking');
        $smoking->setReportName('Smoking Cessation');
        $smoking->addLink(new FakeLink('Admin will enter', '#'));
        $smoking->setAttribute('requirements', 'Complete a Smoking Cessation or Hypnosis Course offered by the LCM Health Center');
        $smoking->setAttribute('points_per_activity', 20);
        $actGroup->addComplianceView($smoking);

        $lcmWellnessView = new PlaceHolderComplianceView(null, 0);
        $lcmWellnessView->setReportName('Other LCMH Time Out For Wellness Activity');
        $lcmWellnessView->setName('lcm_activity');
        $lcmWellnessView->setAttribute('requirements', 'Participate in a designated LCM Wellness Activity and earn the specified number of points. (i.e. OE meetings/ Benefits Fair)');
        $lcmWellnessView->setAttribute('points_per_activity', 'varies');
        $lcmWellnessView->setMaximumNumberOfPoints(100);
        $actGroup->addComplianceView($lcmWellnessView);

        $lcmTrainingView = new PlaceHolderComplianceView(null, 0);
        $lcmTrainingView->setReportName('LCMH Hazmat Training');
        $lcmTrainingView->setName('lcm_training');
        $lcmTrainingView->setAttribute('requirements', 'LCMH Hazmat Training or recertification');
        $lcmTrainingView->setAttribute('points_per_activity', '20');
        $lcmTrainingView->setMaximumNumberOfPoints(20);
        $actGroup->addComplianceView($lcmTrainingView);

        $healthAcademyView = new PlaceHolderComplianceView(null, 0);
        $healthAcademyView->setReportName('LCM Health Education Programs / Screenings');
        $healthAcademyView->setName('health_academy');
        $healthAcademyView->setAttribute('requirements', 'Health Academy');
        $healthAcademyView->setAttribute('points_per_activity', '10');
        $healthAcademyView->setMaximumNumberOfPoints(50);
        $healthAcademyView->addLink(new Link('Verification Form <br />', '/resources/10199/LCMH Health Education Form.pdf'));
        $healthAcademyView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $actGroup->addComplianceView($healthAcademyView);

        $financialWellness = new PlaceHolderComplianceView(null, 0);
        $financialWellness->setReportName('Financial Wellness');
        $financialWellness->setName('financial_fair');
        $financialWellness->setAttribute('requirements', 'Attend upcoming Transamerica Financial Program(s). Verify logon and beneficiary designation through the Transamerica website');
        $financialWellness->setAttribute('points_per_activity', '5');
        $financialWellness->setMaximumNumberOfPoints(25);
        $financialWellness->setAllowPointsOverride(true);
        $financialWellness->addLink(new FakeLink('Admin will enter', '#'));
        $actGroup->addComplianceView($financialWellness);


        $diabetesToolKit = new PlaceHolderComplianceView(null, 0);
        $diabetesToolKit->setReportName('Diabetes Tool Kit');
        $diabetesToolKit->setName('diabetes_tool_kit');
        $diabetesToolKit->setAttribute('requirements', 'Diabetes Tool Kit');
        $diabetesToolKit->setAttribute('points_per_activity', '5');
        $diabetesToolKit->setMaximumNumberOfPoints(60);
        $actGroup->addComplianceView($diabetesToolKit);

        $inBalance = new PlaceHolderComplianceView(null, 0);
        $inBalance->setReportName('In Balance');
        $inBalance->setName('in_balance');
        $inBalance->setAttribute('requirements', 'In Balance');
        $inBalance->setAttribute('points_per_activity', '10');
        $inBalance->setMaximumNumberOfPoints(40);
        $actGroup->addComplianceView($inBalance);

        $wakeUpCallScreening = new PlaceHolderComplianceView(null, 0);
        $wakeUpCallScreening->setReportName('Wake Up Call Screening');
        $wakeUpCallScreening->setName('wake_up_call_screening');
        $wakeUpCallScreening->setAttribute('requirements', 'Wake Up Call Screening');
        $wakeUpCallScreening->setAttribute('points_per_activity', '25');
        $wakeUpCallScreening->setMaximumNumberOfPoints(25);
        $actGroup->addComplianceView($wakeUpCallScreening);

        $labScreenings = new PlaceHolderComplianceView(null, 0);
        $labScreenings->setReportName('Lab Screenings');
        $labScreenings->setName('lab_screenings');
        $labScreenings->setAttribute('requirements', 'Lab Screenings');
        $labScreenings->setAttribute('points_per_activity', '10');
        $labScreenings->setMaximumNumberOfPoints(20);
        $actGroup->addComplianceView($labScreenings);

        $freeScreenings = new PlaceHolderComplianceView(null, 0);
        $freeScreenings->setReportName('Free Screenings (blood pressure, skin cancer)');
        $freeScreenings->setName('free_screenings');
        $freeScreenings->setAttribute('requirements', 'Free Screenings (blood pressure, skin cancer)');
        $freeScreenings->setAttribute('points_per_activity', '5');
        $freeScreenings->setMaximumNumberOfPoints(10);
        $actGroup->addComplianceView($freeScreenings);

        $fitnessPrograms = new PlaceHolderComplianceView(null, 0);
        $fitnessPrograms->setReportName('Fitness Programs');
        $fitnessPrograms->setName('fitness_programs');
        $fitnessPrograms->setAttribute('requirements', 'Fitness Programs - must complete minimum of 8 session program');
        $fitnessPrograms->setAttribute('points_per_activity', '20');
        $fitnessPrograms->setMaximumNumberOfPoints(20);
        $actGroup->addComplianceView($fitnessPrograms);

        $annualEventFebruary = new PlaceHolderComplianceView(null, 0);
        $annualEventFebruary->setReportName('Annual Event - February (Heart Health)');
        $annualEventFebruary->setName('annual_event_february');
        $annualEventFebruary->setAttribute('requirements', 'Annual Event - February (Heart Health)');
        $annualEventFebruary->setAttribute('points_per_activity', '10');
        $annualEventFebruary->setMaximumNumberOfPoints(10);
        $actGroup->addComplianceView($annualEventFebruary);

        $annualEventEmployeeHealth = new PlaceHolderComplianceView(null, 0);
        $annualEventEmployeeHealth->setReportName('Annual Event - Employee Health Day');
        $annualEventEmployeeHealth->setName('annual_event_employee_health_day');
        $annualEventEmployeeHealth->setAttribute('requirements', 'Annual Event - Employee Health Day');
        $annualEventEmployeeHealth->setAttribute('points_per_activity', '10');
        $annualEventEmployeeHealth->setMaximumNumberOfPoints(10);
        $actGroup->addComplianceView($annualEventEmployeeHealth);

        $annualEventApril = new PlaceHolderComplianceView(null, 0);
        $annualEventApril->setReportName('Annual Event - April (Adult Health Fair)');
        $annualEventApril->setName('annual_event_april');
        $annualEventApril->setAttribute('requirements', 'Annual Event - April (Adult Health Fair)');
        $annualEventApril->setAttribute('points_per_activity', '10');
        $annualEventApril->setMaximumNumberOfPoints(10);
        $actGroup->addComplianceView($annualEventApril);

        $annualEventNightOut = new PlaceHolderComplianceView(null, 0);
        $annualEventNightOut->setReportName('Annual Event – Men\'s Wellness Event or Women\'s Wellness Event');
        $annualEventNightOut->setName('annual_event_night_out');
        $annualEventNightOut->setAttribute('requirements', 'Annual Event – Men\'s Wellness Event or Women\'s Wellness Event');
        $annualEventNightOut->setAttribute('points_per_activity', '10');
        $annualEventNightOut->setMaximumNumberOfPoints(10);
        $actGroup->addComplianceView($annualEventNightOut);

        $bodyworkServices = new PlaceHolderComplianceView(null, 0);
        $bodyworkServices->setReportName('Bodywork Services: massage, reflexology, cleansing ionic foot bath');
        $bodyworkServices->setName('bodywork_services');
        $bodyworkServices->setAttribute('requirements', 'Bodywork Services: massage, reflexology, cleansing ionic foot bath');
        $bodyworkServices->setAttribute('points_per_activity', '10');
        $bodyworkServices->setMaximumNumberOfPoints(10);
        $actGroup->addComplianceView($bodyworkServices);

        // $lCMBenefitFair = new PlaceHolderComplianceView(null, 0);
        // $lCMBenefitFair->setReportName('LCM Benefit Fair');
        // $lCMBenefitFair->setName('lcm_benefit_fair');
        // $lCMBenefitFair->setAttribute('requirements', 'Attend the LCM Benefit Fair on 11/16/17');
        // $lCMBenefitFair->setAttribute('points_per_activity', '5');
        // $lCMBenefitFair->setMaximumNumberOfPoints(5);
        // $actGroup->addComplianceView($lCMBenefitFair);



        $maintainWeightView = new PlaceHolderComplianceView(null, 0);
        $maintainWeightView->setReportName('Weight Loss Winner Challenge <br /><span style="font-size: 9pt;">IBW calculated by <a href="http://www.calculator.net/ideal-weight-calculator.html?ctype=standard&cage=48&csex=f&cheightfeet=5&cheightinch=3&cheightmeter=180&printit=0&x=66&y=15">healthy BMI recommendation</a> (18.5-25 for men & women)</span>');
        $maintainWeightView->setName('maintain_weight');
        $maintainWeightView->setAttribute('requirements', 'Maintain weight within 5 lbs. of IBW');
        $maintainWeightView->setAttribute('points_per_activity', '15');
        $maintainWeightView->setMaximumNumberOfPoints(15);
        $maintainWeightView->addLink(new FakeLink('Admin will enter', '#'));
        $actGroup->addComplianceView($maintainWeightView);

        $lostWeightView = new PlaceHolderComplianceView(null, 0);
        $lostWeightView->setReportName('Lose weight during the Challenge');
        $lostWeightView->setName('lost_weight');
        $lostWeightView->setAttribute('requirements', 'Lose weight during the Challenge');
        $lostWeightView->setAttribute('points_per_activity', '2 points/pound lost');
        $lostWeightView->setMaximumNumberOfPoints(30);
        $lostWeightView->addLink(new FakeLink('Admin will enter', '#'));
        $actGroup->addComplianceView($lostWeightView);

        $maintainMonthlyWeighView = new PlaceHolderComplianceView(null, 0);
        $maintainMonthlyWeighView->setReportName('Maintain Campaign Maintain weight within IBW range or 2% of ending weight from "Weight Loss Winner" Challenge.');
        $maintainMonthlyWeighView->setName('maintain_monthly_weigh');
        $maintainMonthlyWeighView->setAttribute('requirements', 'Monthly weigh in (April - September)');
        $maintainMonthlyWeighView->setAttribute('points_per_activity', '4');
        $maintainMonthlyWeighView->setMaximumNumberOfPoints(24);
        $maintainMonthlyWeighView->addLink(new FakeLink('Admin will enter', '#'));
        $actGroup->addComplianceView($maintainMonthlyWeighView);

        $maintainBonusView = new PlaceHolderComplianceView(null, 0);
        $maintainBonusView->setReportName('Bonus:  meet goal for all 6 months');
        $maintainBonusView->setName('maintain_bonus');
        $maintainBonusView->setAttribute('requirements', 'Bonus:  meet goal for all 6 months');
        $maintainBonusView->setAttribute('points_per_activity', '26');
        $maintainBonusView->setMaximumNumberOfPoints(26);
        $maintainBonusView->addLink(new FakeLink('Admin will enter', '#'));
        $actGroup->addComplianceView($maintainBonusView);

        $stepsSyncingView = new UcanAverageStepsComplianceView($startDate, $endDate, 10000);
        $stepsSyncingView->setReportName('Syncing Activity (validated methods only: Fitbit or Fitbit App)');
        $stepsSyncingView->setName('fitbit');
        $stepsSyncingView->addLink(new Link('Fitbit Syncing <br />', '/content/ucan-fitbit-individual'));
        $actGroup->addComplianceView($stepsSyncingView);

        $minutesToSteps = new CHPWalkingCampaignExerciseToSteps($startDate, $endDate, 415, 0);
        $minutesToSteps->setName('minutes_steps');
        $minutesToSteps->setReportName('Convert Active/Exercise Minutes to Steps');
        $actGroup->addComplianceView($minutesToSteps);

        $walk6k = new UcanMultipleAverageStepsComplianceView(6000, 1);
        $walk6k->setMaximumNumberOfPoints(12);
        $walk6k->setName('walk_6k');
        $walk6k->setReportName('Walk an average of 6,000 steps/day');
        $walk6k->setAttribute('requirements', 'Walk an average of 6,000 steps/day');
        $walk6k->setAttribute('points_per_activity', '1 Per month');
        $walk6k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk6k->addLink(new Link('My Steps', '/content/fitness-data-individual'));
        $actGroup->addComplianceView($walk6k);

        $walk8k = new UcanMultipleAverageStepsComplianceView(8000, 2);
        $walk8k->setMaximumNumberOfPoints(24);
        $walk8k->setName('walk_8k');
        $walk8k->setReportName('Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('requirements', 'Walk an average of 8,000 steps/day');
        $walk8k->setAttribute('points_per_activity', 'Additional 2 Per month');
        $walk8k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk8k->addLink(new Link('My Steps', '/content/fitness-data-individual'));
        $actGroup->addComplianceView($walk8k);

        $walk10k = new UcanMultipleAverageStepsComplianceView(10000, 2);
        $walk10k->setMaximumNumberOfPoints(24);
        $walk10k->setName('walk_10k');
        $walk10k->setReportName('Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('requirements', 'Walk an average of 10,000 steps/day');
        $walk10k->setAttribute('points_per_activity', 'Additional 2 Per month');
        $walk10k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk10k->addLink(new Link('My Steps', '/content/fitness-data-individual'));
        $actGroup->addComplianceView($walk10k);

        $walk15k = new UcanMultipleAverageStepsComplianceView(15000, 2);
        $walk15k->setMaximumNumberOfPoints(24);
        $walk15k->setName('walk_15k');
        $walk15k->setReportName('Walk an average of 15,000 steps/day');
        $walk15k->setAttribute('requirements', 'Walk an average of 15,000 steps/day');
        $walk15k->setAttribute('points_per_activity', 'Additional 2 Per month');
        $walk15k->setAttribute('report_name_link', '/content/1094#3hfitness');
        $walk15k->addLink(new Link('My Steps', '/content/fitness-data-individual'));
        $actGroup->addComplianceView($walk15k);


        foreach($this->ranges as $dateRanges) {
            $walk6k->addDateRange($dateRanges[0], $dateRanges[1]);
            $walk8k->addDateRange($dateRanges[0], $dateRanges[1]);
            $walk10k->addDateRange($dateRanges[0], $dateRanges[1]);
            $walk15k->addDateRange($dateRanges[0], $dateRanges[1]);
        }

        $timeOutView = new PlaceHolderComplianceView(null, 0);
        $timeOutView->setReportName('Participate in a Time Out for Wellness Team Walking Challenge');
        $timeOutView->setName('participate_time_out');
        $timeOutView->setAttribute('requirements', 'Participate in a <strong>Time Out for Wellness</strong> Team Walking Challenge <br /> (must average 3,000 steps/day to receive credit)');
        $timeOutView->setAttribute('points_per_activity', '10');
        $timeOutView->setMaximumNumberOfPoints(30);
        $timeOutView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($timeOutView);

        $bonusPointsForMembersView = new PlaceHolderComplianceView(null, 0);
        $bonusPointsForMembersView->setReportName('Bonus Points for members of the Winning Team');
        $bonusPointsForMembersView->setName('bonus_points_for_members');
        $bonusPointsForMembersView->setAttribute('requirements', 'Bonus Points for members of the Winning Team');
        $bonusPointsForMembersView->setAttribute('points_per_activity', '10');
        $bonusPointsForMembersView->setMaximumNumberOfPoints(30);
        $bonusPointsForMembersView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($bonusPointsForMembersView);

        $bonusPointsForTopWalkersView = new PlaceHolderComplianceView(null, 0);
        $bonusPointsForTopWalkersView->setReportName('Bonus Points for each of the Top 20 Walkers ');
        $bonusPointsForTopWalkersView->setName('bonus_points_for_top_walkers');
        $bonusPointsForTopWalkersView->setAttribute('requirements', 'Bonus Points for each of the Top 20 Walkers ');
        $bonusPointsForTopWalkersView->setAttribute('points_per_activity', '10');
        $bonusPointsForTopWalkersView->setMaximumNumberOfPoints(30);
        $bonusPointsForTopWalkersView->setAttribute('report_name_link', '/content/1094#3dsmoking');
        $actGroup->addComplianceView($bonusPointsForTopWalkersView);

        $this->addComplianceViewGroup($actGroup);
    }

    private $ranges = array(
            array('2018-11-01', '2018-11-30'),
            array('2018-12-01', '2018-12-31'),
            array('2019-01-01', '2019-01-31'),
            array('2019-02-01', '2019-02-28'),
            array('2019-03-01', '2019-03-31'),
            array('2019-04-01', '2019-04-30'),
            array('2019-05-01', '2019-05-31'),
            array('2019-06-01', '2019-06-30'),
            array('2019-07-01', '2019-07-31'),
            array('2019-08-01', '2019-08-31'),
            array('2019-09-01', '2019-09-30'),
            array('2019-10-01', '2019-10-31')
    );

}

class LCMH2019ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);
        $this->setShowTotal(false);

    }



    public function printReport(ComplianceProgramStatus $status)
    {
        $activitiesGroupStatus = $status->getComplianceViewGroupStatus('activities');


        $hraStatus = $activitiesGroupStatus->getComplianceViewStatus('hra');
        $screeningStatus = $activitiesGroupStatus->getComplianceViewStatus('screening');
        
        //$annuaPhysicalExamStatus = $activitiesGroupStatus->getComplianceViewStatus('annual_physical_exam');

        $cholesterolStatus = $activitiesGroupStatus->getComplianceViewStatus('cholesterol');
        $hdlStatus = $activitiesGroupStatus->getComplianceViewStatus('hdl');
        $ldlStatus = $activitiesGroupStatus->getComplianceViewStatus('ldl');
        $triglyceridesStatus = $activitiesGroupStatus->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $activitiesGroupStatus->getComplianceViewStatus('glucose');
        $bloodPressureStatus = $activitiesGroupStatus->getComplianceViewStatus('blood_pressure');

        $preventiveStatus = $activitiesGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $donateBloodStatus = $activitiesGroupStatus->getComplianceViewStatus('donate_blood');
        $elearningTotalStatus = $activitiesGroupStatus->getComplianceViewStatus('elearning_total');
        $smokingStatus = $activitiesGroupStatus->getComplianceViewStatus('smoking');

        $lcmActivityStatus = $activitiesGroupStatus->getComplianceViewStatus('lcm_activity');
        $lcmTrainingStatus = $activitiesGroupStatus->getComplianceViewStatus('lcm_training');
        $finanicalStatus = $activitiesGroupStatus->getComplianceViewStatus('financial_fair');
        $healthAcademyStatus = $activitiesGroupStatus->getComplianceViewStatus('health_academy');
        $diabetesToolKitStatus = $activitiesGroupStatus->getComplianceViewStatus('diabetes_tool_kit');
        $inBalanceStatus = $activitiesGroupStatus->getComplianceViewStatus('in_balance');
        $wakeUpCallScreeningStatus = $activitiesGroupStatus->getComplianceViewStatus('wake_up_call_screening');
        $labScreeningsStatus = $activitiesGroupStatus->getComplianceViewStatus('lab_screenings');
        $freeScreeningsStatus = $activitiesGroupStatus->getComplianceViewStatus('free_screenings');
        $fitnessProgramsStatus = $activitiesGroupStatus->getComplianceViewStatus('fitness_programs');
        $annualEventFebruaryStatus = $activitiesGroupStatus->getComplianceViewStatus('annual_event_february');
        $annualEventEmployeeHealthDayStatus = $activitiesGroupStatus->getComplianceViewStatus('annual_event_employee_health_day');
        $annualEventAprilStatus = $activitiesGroupStatus->getComplianceViewStatus('annual_event_april');
        $annualEventNightOutStatus = $activitiesGroupStatus->getComplianceViewStatus('annual_event_night_out');
        $bodyworkServicesStatus = $activitiesGroupStatus->getComplianceViewStatus('bodywork_services');
        // $lcmBenefitFairStatus = $activitiesGroupStatus->getComplianceViewStatus('lcm_benefit_fair');


        $maintainWeightStatus = $activitiesGroupStatus->getComplianceViewStatus('maintain_weight');
        $lostWeightStatus = $activitiesGroupStatus->getComplianceViewStatus('lost_weight');

        $maintainMonthlyWeighStatus = $activitiesGroupStatus->getComplianceViewStatus('maintain_monthly_weigh');
        $maintainBonusStatus = $activitiesGroupStatus->getComplianceViewStatus('maintain_bonus');

        $fitbitStatus = $status->getComplianceViewStatus('fitbit');

        $walk6kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_6k');
        $walk8kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_8k');
        $walk10kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_10k');
        $walk15kStatus = $activitiesGroupStatus->getComplianceViewStatus('walk_15k');
        $participateTimeOutStatus = $activitiesGroupStatus->getComplianceViewStatus('participate_time_out');
        $bonusPointsForMembersStatus = $activitiesGroupStatus->getComplianceViewStatus('bonus_points_for_members');
        $bonusPointsForTopWalkersStatus = $activitiesGroupStatus->getComplianceViewStatus('bonus_points_for_top_walkers');


//        if($status->getComplianceViewStatus('fitbit')->getAttribute('data_refreshed')) {
//            $status->getComplianceViewStatus('fitbit')->getComplianceView()->emptyLinks();
//            $status->getComplianceViewStatus('fitbit')->getComplianceView()->addLink(new Link('View Steps', '/content/ucan-fitbit-individual'));
//        }


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


            #status-table th,
            #elearningTable th,
            .phipTable .headerRow {
                background-color:#007698;
                color:#FFF;
            }

            #status-table th,
            #status-table td {
                padding:10px;
                text-align:center;
                border:1px solid #CACACA;
                font-weight: bold;
            }

            #elearningTable {
                margin: 20px auto;
                width: 100%;
                clear: both;
            }

            #elearningTable th,
            #elearningTable td {
                padding:5px;
                text-align:center;
                border:1px solid #CACACA;
                font-weight: bold;
            }

        </style>


        <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The LCMH Wellness Program</p>

        <p>
            <img src="/images/hmii/lcmh/lcmh_compliance_report_logo.png" style="float: left; width:18%" />

            <div style="border: 1px solid black; float: right; width:70%; margin-top: 20px;">
                <div style="margin: 10px;">
                    <div style="font-weight: bold; font-size: 12pt;">Time out for Wellness 2018-19</div>

                    <div style="color: #00a2ea; margin-top: 30px; font-size: 11pt;">The LCMH Wellness Committee works to provide
                     opportunities for employees to achieve optimal health by promoting a worksite culture that enhances
                     personal well-being.</div>
                </div>
            </div>
        </p>

        <p style="clear:both;">Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Employees that complete eLearning or any of the other Self-care & Wellness Activities outlined here
            will earn wellness points.</p>

        <p>To be eligible for the <strong>Time Out for Wellness</strong> premium reward and raffles, employees must earn the
            points needed before each deadline.</p>

        <p>
            The number of points accumulated will also earn employees the Bronze, Silver, or Gold level of recognition
             for outstanding efforts toward better health and well-being.
        </p>


        <p>Recognition will be based on points earned between 11/1/2018 and 10/31/19.</p>

        <p>Points carryover each period.  50 points are needed before each deadline period.</p>

        <table style="width:100%" id="status-table">
            <tr>
                <th>Incentive Deadline</th>
                <th>Requirements / Minimum Points Needed</th>
                <th>Recognition Status</th>
                <th>Reward(s)</th>
            </tr>
            <tr>
                <td>January 15, 2019</td>
                <td>Complete Any Wellness Activity(ies) to total 50 Points</td>
                <td>Qualified</td>
                <td>1st Quarter Premium Reward will reduce the Employee's Insurance Premium <span style="color: red;">(2/28/19 Pay date*)</span></td>
            </tr>
            <tr>
                <td>March 31, 2019</td>
                <td>100 points (50 points from prior period plus 50 points in current period)</td>
                <td>BRONZE</td>
                <td> 2nd Quarter Premium Reward will reduce the Employee's Insurance Premium <span style="color: red;">(5/9/19 Pay date*)</span> AND 5 Raffle Prizes valued at $40 each</td>
            </tr>
            <tr>
                <td>June 30, 2019</td>
                <td>150 points (100 points from prior periods plus 50 points in current period)</td>
                <td>SILVER</td>
                <td>3rd Quarter Premium Reward will reduce the Employee's Insurance Premium <span style="color: red;">(8/15/19 Pay date*)</span> AND 5 Raffle Prizes valued at $60 each</td>
            </tr>
            <tr>
                <td>October 31, 2019</td>
                <td>200 points (150 points from prior periods plus 50 points in current period)</td>
                <td>GOLD</td>
                <td>4th Quarter Premium Reward will reduce the Employee's Insurance Premium <span style="color: red;">(12/5/19 Pay date*)</span> AND 5 Raffle Prizes valued at $100 each</td>
            </tr>

        </table>

        <p style="color: red; margin: 10px 0;">
            * Must be an active, budgeted FTE employee and enrolled in the plan on the pay date above in order to receive reward.
            Employees in a registry or terminated status on any of the pay dates above are not eligible to receive reward.
        </p>

        <p>
            <img src="/images/hmii/lcmh/lcmh_compliance_report_logo.png" style="float: left; width:15%" />

            <div style="border: 1px solid black; float: right; width:70%; margin-top: 38px;">
                <div style="margin: 10px;">
                    <div style="font-weight: bold; font-size: 12pt;">
                        Time Out for Wellness <br />
                         2018-19 PROGRAM CALENDAR
                    </div>
                </div>
            </div>
        </p>

        <br />


        <table class="phipTable">
            <tbody>

            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Time Out for Wellness:</span> Self-Care & Wellness Activities</th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center" style="width:260px;">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Max Points</td>
                <td class="center">Total Points</td>
                <td class="center">Tracking Method</td>
            </tr>
            <tr>
                <td>
                    <strong>A</strong>. <?php echo $screeningStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $screeningStatus->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $screeningStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $screeningStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $screeningStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($screeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>B</strong>. <?php echo $hraStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $hraStatus->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $hraStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $hraStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $hraStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($hraStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            
            <tr>
                <td rowspan="6">
                    <strong>C</strong>. Biometric Bonus Points
                </td>
                <td class="requirement"><?php echo $cholesterolStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $cholesterolStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $cholesterolStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $cholesterolStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($cholesterolStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $hdlStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $hdlStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $hdlStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $hdlStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($hdlStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $ldlStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $ldlStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $ldlStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $ldlStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($ldlStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $triglyceridesStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $triglyceridesStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $triglyceridesStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $triglyceridesStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($triglyceridesStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $glucoseStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $glucoseStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $glucoseStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $glucoseStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($glucoseStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $bloodPressureStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $bloodPressureStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bloodPressureStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bloodPressureStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($bloodPressureStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td rowspan="2">
                    <strong>D</strong>. <?php echo $preventiveStatus ->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $preventiveStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $preventiveStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $preventiveStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $preventiveStatus ->getPoints() ?></td>
                <td class="center" rowspan="2">
                    <?php foreach($preventiveStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement"><?php echo $donateBloodStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $donateBloodStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $donateBloodStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $donateBloodStatus ->getPoints() ?></td>
            </tr>

            <tr>
                <td>
                    <strong>E</strong>. <?php echo $elearningTotalStatus ->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $elearningTotalStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $elearningTotalStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $elearningTotalStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $elearningTotalStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($elearningTotalStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>F</strong>. <?php echo $smokingStatus ->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $smokingStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $smokingStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $smokingStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $smokingStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr>
                <td rowspan="2">
                    <strong>G</strong>. <?php echo $lcmActivityStatus ->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $lcmActivityStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $lcmActivityStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $lcmActivityStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $lcmActivityStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $lcmTrainingStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $lcmTrainingStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $lcmTrainingStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $lcmTrainingStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr>
                <td rowspan="12">
                    <strong>H</strong>. <?php echo $healthAcademyStatus ->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $healthAcademyStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $healthAcademyStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $healthAcademyStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $healthAcademyStatus ->getPoints() ?></td>
                <td class="center" rowspan="12">
                    <?php foreach($healthAcademyStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement"><?php echo $diabetesToolKitStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $diabetesToolKitStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $diabetesToolKitStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $diabetesToolKitStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $inBalanceStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $inBalanceStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $inBalanceStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $inBalanceStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $wakeUpCallScreeningStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $wakeUpCallScreeningStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $wakeUpCallScreeningStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $wakeUpCallScreeningStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $labScreeningsStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $labScreeningsStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $labScreeningsStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $labScreeningsStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $freeScreeningsStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $freeScreeningsStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $freeScreeningsStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $freeScreeningsStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $fitnessProgramsStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $fitnessProgramsStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $fitnessProgramsStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $fitnessProgramsStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $annualEventFebruaryStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $annualEventFebruaryStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $annualEventFebruaryStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $annualEventFebruaryStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $annualEventEmployeeHealthDayStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $annualEventEmployeeHealthDayStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $annualEventEmployeeHealthDayStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $annualEventEmployeeHealthDayStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $annualEventAprilStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $annualEventAprilStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $annualEventAprilStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $annualEventAprilStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $annualEventNightOutStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $annualEventNightOutStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $annualEventNightOutStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $annualEventNightOutStatus ->getPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $bodyworkServicesStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $bodyworkServicesStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bodyworkServicesStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bodyworkServicesStatus ->getPoints() ?></td>
            </tr>

            
<tr>
                <td>
                    <strong>I</strong>. <?php echo $finanicalStatus ->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $finanicalStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $finanicalStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $finanicalStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $finanicalStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>



            <tr>
                <td rowspan="2">
                    <strong>J</strong>. <?php echo $maintainWeightStatus ->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $maintainWeightStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $maintainWeightStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $maintainWeightStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $maintainWeightStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>
            <tr>

                <td class="requirement"><?php echo $lostWeightStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $lostWeightStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $lostWeightStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $lostWeightStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>

            <tr>
                <td rowspan="2">
                    <strong>K</strong>. <?php echo $maintainMonthlyWeighStatus ->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $maintainMonthlyWeighStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $maintainMonthlyWeighStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $maintainMonthlyWeighStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $maintainMonthlyWeighStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>
            <tr>

                <td class="requirement"><?php echo $maintainBonusStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $maintainBonusStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $maintainBonusStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $maintainBonusStatus ->getPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>


            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Time Out For Wellness: </span> Activity Tracking & Walking Challenges</th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td colspan="2" class="center">
                    This program is designed to help you become physically active each day. <br />
                    Participants can track steps using one of the devices listed below.
                </td>
                <td class="center">Avg. Daily Steps</td>
                <td colspan="3" class="center">Action Links</td>
            </tr>

            <tr>
                <td colspan="2">
                    <?php echo $fitbitStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="center">
                    <?php
                        // $query = SelectQuery::create()
                        //     ->select('*')
                        //     ->from('user_data_records udr')
                        //     ->innerJoin('user_data_fields udf')
                        //     ->on('udr.id = udf.user_data_record_id')
                        //     ->where('udr.user_id = ?', array($status->getUser()->id))
                        //     ->andWhere('udf.field_name like "%walking%"')
                        //     ->andWhere('udr.type ="moves"')
                        //     ->orderBy(('udf.id desc'))
                        //     ->execute()
                        //     ->toArray();

                        // $days = 0;
                        // $movesSteps = 0;
                        // foreach ($query as $qK => $qV) {
                        //     $date = explode("_", $qV['field_name']);
                        //     $date = $date[0];
                        //     $date = strtotime($date);
                        //     if ($date > strtotime('-30 days')) {
                        //         $steps = json_decode($qV['field_value'], true);
                        //         $movesSteps += $steps['steps'];
                        //         $days++;
                        //     }
                        // }

                        // if ($days > 30) {
                        //     $movesSteps = ceil($movesSteps / 30);
                        // } else {
                        //     if ($days > 0) {
                        //         $movesSteps = $movesSteps / $days;
                        //     } else {
                        //         $movesSteps = 0;
                        //     }
                        // }

                        // $steps = ceil($movesSteps + number_format($fitbitStatus->getAttribute('average_daily_steps')));
                    $steps = 0;
                        echo $steps;
                    ?>

                </td>
                <td colspan="3" class="center"><?php echo implode(' ', $fitbitStatus->getComplianceView()->getLinks()) ?></td>
            </tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center">Max Points</td>
                <td class="center">Total Points</td>
                <td class="center">Tracking Method</td>
            </tr>

            <tr>
                <td rowspan="4" class="center">
                    <strong>Individual Walking Challenge</strong><br /><br />
                    Points will be awarded at the end of each period based on the average steps logged during the month.
                </td>
                <td class="requirement"><?php echo $walk6kStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $walk6kStatus ->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk6kStatus ->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk6kStatus ->getPoints() ?></td>
                <td class="center">
                    <?php foreach($walk6kStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>

                <td class="requirement"><?php echo $walk8kStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $walk8kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk8kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk8kStatus->getPoints() ?></td>
                <td class="center">
                    <?php foreach($walk8kStatus ->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement"><?php echo $walk10kStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $walk10kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk10kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk10kStatus->getPoints() ?></td>
                <td class="center">
                    <?php foreach($walk10kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement"><?php echo $walk15kStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $walk15kStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walk15kStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $walk15kStatus->getPoints() ?></td>
                <td class="center">
                    <?php foreach($walk15kStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td rowspan="3" class="center">
                    <strong>Time Out for Wellness<br />
                            Team Walking Challenges
                            </strong><br /><br />

                    Employees must log activity during each challenge to receive points.<br /><br />

                    Spring Challenge:
                    4/1 - 4/30/19<br />

                    Summer Challenge:
                    6/1 - 6/30/19<br />

                    Fall Challenge:
                    8/1 -8/31/19<br />



                </td>
                <td class="requirement"><?php echo $participateTimeOutStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $participateTimeOutStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $participateTimeOutStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $participateTimeOutStatus->getPoints() ?></td>
                <td class="center" rowspan="3">
                    <a href="/content/ucan-fitbit-leaderboards?type=team">Team Leaderboard</a>
                </td>
            </tr>

            <tr>

                <td class="requirement"><?php echo $bonusPointsForMembersStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bonusPointsForMembersStatus->getPoints() ?></td>

            </tr>

            <tr>

                <td class="requirement"><?php echo $bonusPointsForTopWalkersStatus ->getComplianceView()->getAttribute('requirements') ?></td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center"><?php echo $bonusPointsForTopWalkersStatus->getPoints() ?></td>

            </tr>
            <tr>
                <td colspan="3"  style="text-align: center">
                    <strong>Total Points</strong>
                </td>
                <td class="center"><?php echo $activitiesGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints()?></td>
                <td class="center"><?php echo $activitiesGroupStatus->getPoints()?></td>
                <td class="center"></td>
            </tr>
            </tbody>
        </table>

        <p style="font-size: 9pt; margin-top: 10px;">
            <em><strong>Notice:</strong> The <strong>LCMH Time Out for Wellness Program</strong> is committed to
            helping you achieve your best health. Rewards for participating in a wellness progam are available to
             all employees. If you think you might be unable to meet a standard for a reward under this wellness program,
              you might qualify for an opportunity to earn the same reward by different means. Contact Human Resources
              and we will work with you (and, if you wish, your doctor) to find a wellness program with the same reward
               that is right for you in light of your health status.</em>
        </p>

        <?php
    }

}

