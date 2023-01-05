<?php

class Midland2019TobaccoFormComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->start_date = $startDate;
        $this->end_date = $endDate;
    }

    public function getDefaultName()
    {
        return 'non_smoker_view';
    }

    public function getDefaultReportName()
    {
        return 'Non Smoker';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('midland_paper_tobacco_declaration');

        if($record->exists()
            && $record->agree
            && date('Y-m-d', strtotime($record->date)) >=  date('Y-m-d', $this->start_date)
            && date('Y-m-d', strtotime($record->date)) <=  date('Y-m-d', $this->end_date)) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 25);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}

class MidlandWMS3StepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $pointsPer, $userId)
    {
        $this->setDateRange($startDate, $endDate);
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
        $this->userId = $userId;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "regular_physical_activity_wms3";
    }

    public function getDefaultReportName()
    {
        return "Regular Physical Activity ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $_db = Database::getDatabase();
        $startDate = date('Y-m-d', $this->startDate);
        $endDate = date('Y-m-d', $this->endDate);

        $fitnessTrackingQuery =
            "SELECT ftd.activity_date, ftd.value FROM wms3.fitnessTracking_data ftd 
              LEFT JOIN wms3.fitnessTracking_participants ftp ON ftp.id = ftd.participant
              WHERE ftp.wms1Id = ".$this->userId." AND ftd.activity_date >= '".$startDate."' 
              AND ftd.activity_date <= '".$endDate."';";

        $data = $_db->getResultsForQuery($fitnessTrackingQuery);

        $steps = 0;

        foreach($data as $record) {
            $steps += $record['value'];
        }

        $points = floor($steps/$this->threshold);

        $status = new ComplianceViewStatus($this, null, $points);

        return $status;
    }

    private $threshold;
    private $pointsPer;
    private $userId;
}


class Midland2019ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function loadGroups()
    {

        if (!isset($_GET['admin'])) {
            header('Location: /compliance/midland-paper-2019/my-rewards/content/midland-paper-coming-soon');
            die;
        }

        $user = sfContext::getInstance()->getUser()->getUser();
        $wms1ID = $user->id;
        $wms2AcctID = $user->wms2_account_id;

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'Core Actions to get done by 1/31/20 for your Core Reward and toward your Bonus Reward:');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Wellness Screening');
        $screeningView->emptyLinks();
        $screeningView->setName('annual_screening');
        $screeningView->addLink(new Link('Sign-Up', '/content/wms2-appointment-center'));
        $screeningView->addLink(new Link('Results', '/compliance/midland-paper-2019/my-health'));


        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Complete the Health Power Assessment');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/compliance/midland-paper-2019/my-health'));
        $hraView->addLink(new Link('Results', '/compliance/midland-paper-2019/my-health'));
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $numbers = new ComplianceViewGroup('points', 'And, earn at least 350 points by 1/31/20 through the opportunities below for your Bonus Reward:');

        $screeningTestMapper = new ComplianceStatusPointMapper(50, 25, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
//        $totalCholesterolView->setAttribute('_screening_printer_hack', 6);
        $totalCholesterolView->setAttribute('elearning_lesson_id', '789');
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->overrideTestRowData(89.999, 99.999, 199.999, 240.999);
        $totalCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $totalCholesterolView->setName('cholesterol');
        $numbers->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setAttribute('elearning_lesson_id', '595');
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
//        $hdlCholesterolView->overrideTestRowData(0, 0, 4.499, 4.499);
        $hdlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $hdlCholesterolView->setName('hdl');
        $numbers->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithTotalLDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setAttribute('elearning_lesson_id', '595');
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->overrideTestRowData(0, 0, 129.999, 158.999);
        $ldlCholesterolView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $ldlCholesterolView->setName('ldl');
        $numbers->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setAttribute('elearning_lesson_id', '99');
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $trigView->overrideTestRowData(0, 0, 149.999, 199.999);
        $trigView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $trigView->setName('triglycerides');
        $numbers->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setAttribute('elearning_lesson_id', '105');
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $glucoseView->overrideTestRowData(49.999, 69.999, 99.999, 125.999);
        $glucoseView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $glucoseView->addLink(new Link('Blood Sugar & Tips Lessons', '/search-learn/core-strengths/sitemaps/health_centers/15401'));
        $glucoseView->setName('glucose');
        $numbers->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setAttribute('elearning_lesson_id', '1309');
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 119.999, 139.999);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 79.999, 89.999);
        $bloodPressureView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $bloodPressureView->setEitherNonCompliantYieldsNonCompliant(true);
        $bloodPressureView->setName('bp');
        $bloodPressureView->addLink(new Link('Blood Pressure Tips & Lessons', '/search-learn/core-strengths/sitemaps/health_centers/15919'));
        $numbers->addComplianceView($bloodPressureView);


        $BMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Mass Index (BMI)');
        $BMIView->setAttribute('elearning_lesson_id', '1118');
        $BMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $BMIView->overrideBMITestRowData(0, 18.5, 24.999, 29.999);
        $BMIView->overrideBodyFatTestRowData(0, 6, 17.999, 24.999, 'M');
        $BMIView->overrideBodyFatTestRowData(14, 14, 24.999, 31.999, 'F');
        $BMIView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $BMIView->addLink(new Link('BMI Tips & Lessons', '/search-learn/core-strengths/sitemaps/health_centers/15932'));
        $BMIView->setName('bmi');
        $numbers->addComplianceView($BMIView);

        $doc = new UpdateDoctorInformationComplianceView($programStart,$programEnd);
        $doc->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $doc->setReportName('Confirm that you have a main doctor for primary care');
        $doc->setName('doc');
        $numbers->addComplianceView($doc);
        // $numbers->addComplianceView($proviceDoctor);


        $fluVaccineView = new ImmunizationsActivityComplianceView($programStart, $programEnd, 25);
        $fluVaccineView->setReportName('Get Recommended Immunizations - flu, tetanus, pneumonia, others');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $numbers->addComplianceView($fluVaccineView);

        $nonSmokerView = new Midland2019TobaccoFormComplianceView($programStart, $programEnd);
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->setName('non_smoker');
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(25);
        $nonSmokerView->setReportName('Confirm Being a Non-Smoker');
        $numbers->addComplianceView($nonSmokerView);

        $ineligibleLessonIDs = array(789, 595, 99, 105, 1309, 1118);
        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd, null, null, $ineligibleLessonIDs);
        $elearn->setReportName('Complete e-Learning Lessons - 25 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setPointsPerLesson(25);
        $elearn->setMaximumNumberOfPoints(150);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=completed_compliance'));
        $numbers->addComplianceView($elearn);

        // $physicalActivityView = new MidlandWMS3StepsComplianceView($programStart, $programEnd, 2000, 1, $wms1ID);
        // $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each 2,000 Steps');
        // $physicalActivityView->setMaximumNumberOfPoints(250);
        // $physicalActivityView->addLink(new Link('Sync/Enter Steps', '/compliance/midland-paper-2019/fitness-tracking-wms3/compliance_programs?id=1358&forceRefresh=true&wms1Id='.$wms1ID.'&wms2AccountId='.$wms2AcctID.'&prefix=midland-paper-2019&device=1'));
        // $numbers->addComplianceView($physicalActivityView);

        $physicalActivityView = new CulverPhysicalActivityView($programStart, $programEnd);
        $physicalActivityView->setReportName('Get Regular Physical Activity - use one or both below to sync or update for exercise/activity points between 2/1/19 and 1/31/20');
        $physicalActivityView->setMaximumNumberOfPoints(250);

        $physicalActivityView->setName('physical_activity');

        $numbers->addComplianceView($physicalActivityView);

        $midlandFitChallengeView = new PlaceHolderComplianceView(null, 0);
        $midlandFitChallengeView->setName('midland_get_fit_challenge');
        $midlandFitChallengeView->setReportName('Participate in Midland Get Fit Challenge');
        $midlandFitChallengeView->addLink(new Link('More Info', '/content/get-fit-challenge'));
        $midlandFitChallengeView->setMaximumNumberOfPoints(25);
        $numbers->addComplianceView($midlandFitChallengeView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setReportName('Regular Volunteering – 5 pts for each hour of volunteering');
        $volunteeringView->setMinutesDivisorForPoints(12);
        $volunteeringView->setMaximumNumberOfPoints(100);
        $volunteeringView->setName('volunteer');
        $numbers->addComplianceView($volunteeringView);

        $preventiveScreeningsView = new CompletePreventiveExamComplianceView($programStart, $programEnd,25);
        $preventiveScreeningsView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $preventiveScreeningsView->setReportName('Get Recommended Preventive Screenings – annual exam, others');
        $preventiveScreeningsView->setName('preventative');
        $numbers->addComplianceView($preventiveScreeningsView);

        $midlandPaper = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 424, 25);
        $midlandPaper->setMaximumNumberOfPoints(25);
        $midlandPaper->setName('wellness_event');
        $midlandPaper->setReportName('Attend a Midland Paper Sponsored Wellness Event');
        $numbers->addComplianceView($midlandPaper);

        $blueCrossBlueShield = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 342, 10);
        $blueCrossBlueShield->setMaximumNumberOfPoints(10);
        $blueCrossBlueShield->setReportName('Register with Blue Cross Blue Shield’s Blue Access for Members');
        $blueCrossBlueShield->emptyLinks();
        $blueCrossBlueShield->setName('bcbs');
        $blueCrossBlueShield->addLink(new Link('BCBS', '/compliance_programs/localAction?id=1332&local_action=blue_cross_blue_shield', false, '_blank'));
        $blueCrossBlueShield->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('blue_cross_blue_shield_2018');

            if($record->exists()) {
                $status->setPoints(10);
            }
        });
        $numbers->addComplianceView($blueCrossBlueShield);

        $mdliveServices = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 555, 10);
        $mdliveServices->setMaximumNumberOfPoints(10);
        $mdliveServices->setReportName('Register with MDLive telehealth services');
        $mdliveServices->emptyLinks();
        $mdliveServices->setName('md_live');
        $mdliveServices->addLink(new Link('Register/Enter Info', '/compliance_programs/localAction?id=1332&local_action=md_live_services', false, '_blank'));
        $mdliveServices->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('md_live_services_2018');

            if($record->exists()) {
                $status->setPoints(10);
            }
        });
        $numbers->addComplianceView($mdliveServices);

        $registerEAP = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 1692, 10);
        $registerEAP->setMaximumNumberOfPoints(10);
        $registerEAP->setReportName('Register with/visit EAP website to learn more and see what’s there');
        $registerEAP->emptyLinks();
        $registerEAP->setName('eap');
        $registerEAP->addLink(new Link('Register/Visit EAP Site', '/compliance_programs/localAction?id=1332&local_action=register_eap', false, '_blank'));
        $registerEAP->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('register_eap_2018');

            if($record->exists()) {
                $status->setPoints(10);
            }
        });
        $numbers->addComplianceView($registerEAP);

        $bcbsWellOnTarget = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 1691, 10);
        $bcbsWellOnTarget->setMaximumNumberOfPoints(10);
        $bcbsWellOnTarget->setReportName('Register with BCBS WellOnTarget for discounts and more rewards');
        $bcbsWellOnTarget->emptyLinks();
        $bcbsWellOnTarget->setName('enter_info');
        $bcbsWellOnTarget->addLink(new Link('Register/Enter Info', '/compliance_programs/localAction?id=1332&local_action=bcbs_well_on_target', false, '_blank'));
        $bcbsWellOnTarget->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('bcbs_well_on_target_2018');

            if($record->exists()) {
                $status->setPoints(10);
            }
        });
        $numbers->addComplianceView($bcbsWellOnTarget);


        $numbers->setPointsRequiredForCompliance(350);

        $this->addComplianceViewGroup($numbers);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinterMidland' && $this->getActiveUser() !== null) {

            $printer = new $preferredPrinter;

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
            $printer = new Midland2019ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function loadCompletedLessons($status, $user)
    {
        if($elearningLessonId = $status->getComplianceView()->getAttribute('elearning_lesson_id')) {
            $elearningView = new CompleteELearningLessonComplianceView($this->getStartDate(), $this->getEndDate(), new ELearningLesson_v2($elearningLessonId));

            $elearningStatus = $elearningView->getStatus($user);
            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        }
    }

    public function executeBlueCrossBlueShield(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $user->getNewestDataRecord('blue_cross_blue_shield_2018', true);

        $actions->redirect('http://www.bcbsil.com/member');

    }

    public function executeMdLiveServices(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $user->getNewestDataRecord('md_live_services_2018', true);

        $actions->redirect('http://MDLIVE.com/bcbsil');

    }

    public function executeRegisterEap(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $user->getNewestDataRecord('register_eap_2018', true);

        $actions->redirect('http://www.guidanceresources.com');

    }


    public function executeBcbsWellOnTarget(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $user->getNewestDataRecord('bcbs_well_on_target_2018', true);

        $actions->redirect('https://members.hcsc.net/wps/portal/wellontarget');

    }

    public function getLocalActions()
    {
        return array(
            'blue_cross_blue_shield' => array($this, 'executeBlueCrossBlueShield'),
            'md_live_services'  => array($this, 'executeMdLiveServices'),
            'register_eap'  => array($this, 'executeRegisterEap'),
            'bcbs_well_on_target'  => array($this, 'executeBcbsWellOnTarget'),
        );
    }
}


class Midland2019ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        $coreStatus = $status->getComplianceViewGroupStatus('core');

        ?>

        <style>
            .bonus_panel {

            }

            .bonus_panel:last-child {
                border-bottom: 1px solid;
            }

            .bonus_panel td {
                border-bottom: none;
                border-top:none;
            }

            .bonus_panel td:nth-child(1) {
                text-align: right;
                padding-right: 10px;
            }

            .bonus_panel td:nth-child(2) {
                text-align: center;
            }

            .bonus_panel td:nth-child(3) {
                padding-left: 10px;
            }
        </style>
        <style type="text/css">
            .pageHeading {
                font-weight: bold;
                text-align: center;
                margin-bottom: 20px;
            }

            .phipTable {
                width: 100%;
                border-collapse: collapse;
            }

            .phipTable th, .phipTable td {
                border: 1px solid #000000;
                padding: 2px;
            }

            .phipTable .headerRow {
                background-color: #158E4C;
                font-weight: normal;
                color: #FFFFFF;
                font-size: 12pt;
            }

            .phipTable .headerRow th {
                text-align: left;
                font-weight: normal;
            }

            .phipTable .headerRow td {
                text-align: center;
            }

            .phipTable .links {
                text-align: center; width:10em;
            }

            .center, .points, .result {
                text-align: center;
            }

            .white {
                background-color: #FFFFFF;
            }

            .light {
                width: 25px;
            }

            .status {
                text-align: center;
            }
            
            #legend, #legend tr, #legend td {
                padding: 0px;
                margin: 0px;
                                text-align:center;
            }
            
            #legend td {

                padding-bottom: 5px;
            }

            
            #legendText {
                text-align: center;
                background-color: #158E4C;
                font-weight: normal;
                color: #FFFFFF;
                font-size: 12pt;    
                margin-bottom: 5px;
            }
            
            .legendEntry {
                width: 160px;
                float: left;
                text-align: center;
                padding-left: 2px;
            }
        </style>


        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>Welcome to your summary page for the 2020 Midland Paper Wellness Rewards benefit.</p>

        <p><em>Do you have medical benefit coverage through Midland Paper?</em> If yes, you can earn rewards of $480 to $1,080 ($40-$90 per month) in savings toward your 2020 medical plan premium contribution.</p>

        <p>
            Here’s how:
        <ol>
            <li><strong>Core Reward</strong> – complete the Wellness Screening and Health Power Assessment by 1/31/20 and get:
                <ol type="a">
                    <li>$480 ($40/month) if you have employee-only coverage; <strong>OR</strong></li>
                    <li>$600 ($50/month) if you have employee plus spouse/children/family coverage, and both you and your eligible spouse (if applicable) get both core actions done.</li>
                </ol>
            </li>
            <li><atrong>And a Bonus Reward</atrong> – get the core actions (above) done <strong>PLUS</strong> earn at least 350 points by 1/31/20 and:
                <ol type="a">
                    <li>Get an extra $300 ($25/month) if you have employee-only coverage; <strong>OR</strong></li>
                    <li>Get an extra $480 ($40/month) if you have employee plus spouse/children/family coverage, and both you and your eligible spouse (if applicable) get these things done.</li>
                </ol>
            </li>
        </ol>
        </p>
        <p>
            More importantly, taking these actions can add to your health and wellbeing now and in the future.
        </p>

        <p>
            Login anytime to see your Wellness Rewards To-Do summary below. The action links make it easy to get things done and earn your rewards. <br />
            <span style="font-size: 14pt; text-align: center; display: block;"><a href="/content/1094_midlandpaper_2019">Click here for more details about each action and point-earning option.</a> </span>
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    { 

        $light_map = [
            ComplianceStatus::NOT_COMPLIANT => "/compliance/midland-paper-2019/my-rewards/images/lights/redlight.gif",
            ComplianceViewStatus::PARTIALLY_COMPLIANT => "/compliance/midland-paper-2019/my-rewards/images/lights/yellowlight.gif",
            ComplianceViewStatus::NA_COMPLIANT => "/compliance/midland-paper-2019/my-rewards/images/lights/whitelight.gif",
            ComplianceViewStatus::COMPLIANT => "/compliance/midland-paper-2019/my-rewards/images/lights/greenlight.gif"
        ];

        $this->printHeader($status);
        $cholStatus = $status->getComplianceViewStatus('cholesterol');
        $screeningStatus = $status->getComplianceViewStatus('annual_screening');
        $hraStatus = $status->getComplianceViewStatus('hra');
        $hdlStatus = $status->getComplianceViewStatus('hdl');
        $ldlStatus = $status->getComplianceViewStatus('ldl');
        $trigStatus = $status->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $status->getComplianceViewStatus('glucose');
        $bpStatus = $status->getComplianceViewStatus('bp');
        $bmiStatus = $status->getComplianceViewStatus('bmi');
        $docStatus = $status->getComplianceViewStatus('doc');
        $fluStatus = $status->getComplianceViewStatus('flu_vaccine');
        $nonSmokerStatus = $status->getComplianceViewStatus('non_smoker');
        $elearnStatus = $status->getComplianceViewStatus('elearning');
        $getFitStatus = $status->getComplianceViewStatus('midland_get_fit_challenge');
        $volunteerStatus = $status->getComplianceViewStatus('volunteer');
        $preventativeStatus = $status->getComplianceViewStatus('preventative');
        $wellnessStatus = $status->getComplianceViewStatus('wellness_event');
        $bcbsStatus = $status->getComplianceViewStatus('bcbs');
        $mdLiveStatus = $status->getComplianceViewStatus('md_live');
        $eapStatus = $status->getComplianceViewStatus('eap');
        $enterInfoStatus = $status->getComplianceViewStatus('enter_info');
        $physicalStatus = $status->getComplianceViewStatus('physical_activity');
        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $overall_status = $light_map[ComplianceStatus::NOT_COMPLIANT];
        $overall_text = "Still some to go!";

        if($coreGroupStatus->getStatus() == ComplianceStatus::COMPLIANT && $pointGroupStatus->getPoints() >= 350) {
            $overall_status = $light_map[ComplianceStatus::COMPLIANT];
            $overall_text = 'Congrats!';
        } else {
            if($coreGroupStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT || $pointGroupStatus->getPoints() > 0) {
                $overall_status = $light_map[ComplianceStatus::PARTIALLY_COMPLIANT];
            }
        }
    ?>

        <table class="phipTable">
           <thead id="legend">
              <tr>
                 <td colspan="6">
                    <div id="legendText">Legend</div>
                    <div class="legendEntry"><img src="/compliance/midland-paper-2019/my-rewards/images/lights/greenlight.gif" class="light" alt="">= Criteria Met</div>
                    <div class="legendEntry"><img src="/compliance/midland-paper-2019/my-rewards/images/lights/whitelight.gif" class="light" alt="">= N/A</div>
                    <div class="legendEntry"><img src="/compliance/midland-paper-2019/my-rewards/images/lights/yellowlight.gif" class="light" alt="">= Working on It</div>
                    <div class="legendEntry"><img src="/compliance/midland-paper-2019/my-rewards/images/lights/redlight.gif" class="light" alt="">= Not Started</div>
                 </td>
              </tr>
           </thead>
           <tbody>
              <tr class="headerRow headerRow-core">
                 <th><strong>1</strong>. Core Actions to get done by 1/31/20 for your Core Reward and toward your Bonus Reward:</th>
                 <td></td>
                 <td>Completed</td>
                 <td>Status</td>
                 <td>Links</td>
              </tr>
              <tr class="view-complete_screening">
                 <td>
                    <strong>A</strong>. <?php echo $screeningStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="result"><?php echo $screeningStatus->getComment(); ?></td>
                 <td class="status"><img src="<?php echo $light_map[$screeningStatus->getStatus()]; ?>" class="light" alt=""></td>
                 <td class="links">
                     <?php foreach($screeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-complete_hra">
                 <td>
                    <strong>B</strong>. <?php echo $hraStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="result"><?php echo $hraStatus->getComment(); ?></td>
                 <td class="status"><img src="<?php echo $light_map[$hraStatus->getStatus()]; ?>" class="light" alt=""></td>
                 <td class="links">
                     <?php foreach($hraStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="headerRow headerRow-points">
                 <th><strong>2</strong>. And, earn at least 350 points by 1/31/20 through the opportunities below for your Bonus Reward:</th>
                 <td>My Steps</td>
                 <td># Points Earned</td>
                 <td># Points Possible</td>
                 <td>Links</td>
              </tr>
              <tr>
                 <td><strong>A. </strong>Biometric Results: Gain points by having biometrics in the healthier ranges –OR– complete related e-lessons to learn more and improve.</td>
                 <td></td>
                 <td></td>
                 <td></td>
                 <td style="text-align: center;"><a href="/compliance/midland-paper-2019/my-rewards/compliance_programs?preferredPrinter=ScreeningProgramReportPrinterMidland&amp;id=1445">View Results &amp; point scale</a></td>
              </tr>
              <tr class="view-cholesterol">
                 <td>
                    <span style="padding-left:16px;">1. <?php echo $cholStatus->getComplianceView()->getReportName(); ?></span>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $cholStatus->getPoints(); ?></td>
                 <td class="points">50</td>
                 <td class="links" rowspan="4"><a href="/search-learn/core-strengths/sitemaps/health_centers/15913">Blood Fat Tips &amp; Lessons</a></td>
              </tr>
              <tr class="view-hdl">
                 <td>
                    <span style="padding-left:16px;">2. <?php echo $hdlStatus->getComplianceView()->getReportName(); ?></span>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $hdlStatus->getPoints(); ?></td>
                 <td class="points">50</td>
                 
              </tr>
              <tr class="view-ldl">
                <td>
                    <span style="padding-left:16px;">3. <?php echo $ldlStatus->getComplianceView()->getReportName(); ?></span>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $ldlStatus->getPoints(); ?></td>
                 <td class="points">50</td>
                 
              </tr>
              <tr class="view-triglycerides">
                 <td>
                    <span style="padding-left:16px;">4. <?php echo $trigStatus->getComplianceView()->getReportName(); ?></span>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $trigStatus->getPoints(); ?></td>
                 <td class="points">50</td>
                 
              </tr>
              <tr class="view-glucose">
                 <td>
                    <span style="padding-left:16px;">5. <?php echo $glucoseStatus->getComplianceView()->getReportName(); ?></span>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $glucoseStatus->getPoints(); ?></td>
                 <td class="points">50</td>
                 <td class="links"><?php foreach($glucoseStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
              </tr>
              <tr class="view-bp">
                 <td>
                    <span style="padding-left:16px;">6. <?php echo $bpStatus->getComplianceView()->getReportName(); ?></span>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $bpStatus->getPoints(); ?></td>
                 <td class="points">50</td>
                 <td class="links"><?php foreach($bpStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
              </tr>
              <tr class="view-bmi">
                 <td>
                    <span style="padding-left:16px;">7. <?php echo $bmiStatus->getComplianceView()->getReportName(); ?></span>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $bmiStatus->getPoints(); ?></td>
                 <td class="points">50</td>
                 <td class="links"><?php foreach($bmiStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?></td>
              </tr>
              <tr class="view-doc">
                 <td>
                    <strong>B</strong>. <?php echo $docStatus->getComplianceView()->getReportName(); ?>                    
                 </td>
                 <td></td>
                 <td class="points"><?php echo $docStatus->getPoints(); ?></td>
                 <td class="points">25</td>
                 <td class="links">
                     <?php foreach($docStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-flu_vaccine">
                 <td>
                    <strong>C</strong>. <?php echo $fluStatus->getComplianceView()->getReportName(); ?>
                </td>
                <td></td>
                 <td class="points"><?php echo $fluStatus->getPoints(); ?></td>
                 <td class="points">75</td>
                 <td class="links">
                     <?php foreach($fluStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-non_smoker">
                 <td>
                    <strong>D</strong>. <?php echo $nonSmokerStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $nonSmokerStatus->getPoints(); ?></td>
                 <td class="points">25</td>
                 <td class="links">
                     <?php foreach($nonSmokerStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-elearning">
                 <td><strong>E</strong>. 
                    <?php echo $elearnStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $elearnStatus->getPoints(); ?></td>
                 <td class="points">150</td>
                 <td class="links">
                     <?php foreach($elearnStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-get_fit">
                 <td>
                    <strong>F</strong>. <?php echo $physicalStatus->getComplianceView()->getReportName(); ?>                    
                 </td>
                 <td></td>
                 <td class="points"></td>
                 <td class="points"></td>
                 <td class="links">
                 </td>
              </tr>

              <tr>
                <td><ul style="list-style-type: none;"><li>1) Fitbit Syncing or Manual Steps Entry</li></ul></td>
                <td class="center"><?php echo $physicalStatus->getComplianceView()->getAttribute('steps_data')['fitnessTracker']['steps']; ?></td>
                <td class="center"><?php echo $physicalStatus->getComplianceView()->getAttribute('steps_data')['fitnessTracker']['points']; ?></td>
                <td class="center"></td>
                <td class="links"><a href="/compliance_programs?id=1358&clientId=midland">Fitness Tracker</a></td>
            </tr>
            <tr>
                <td><ul style="list-style-type: none;"><li>2) Convert Activity Minutes to Steps</li></ul></td>
                <td class="center"><?php echo $physicalStatus->getComplianceView()->getAttribute('steps_data')['activities']['steps']; ?></td>
                <td class="center"><?php echo $physicalStatus->getComplianceView()->getAttribute('steps_data')['activities']['points']; ?></td>
                <td class="center"></td>
                <td class="links"><a href="/content/12048?action=showActivity&activityidentifier=21">Enter/Update Info</a></td>
            </tr>

            <tr>
                <td><ul style="list-style-type: none;"><li>3) Total Steps & Points based on 1 pt per 2,000 steps</li></ul></td>
                <td class="center"><?php echo $physicalStatus->getComplianceView()->getAttribute('steps_data')['activities']['steps'] + $physicalStatus->getComplianceView()->getAttribute('steps_data')['fitnessTracker']['steps']; ?></td>
                <td class="center"><?php echo $physicalStatus->getPoints();//getAttribute('steps_data')['activities']['points']; ?></td>
                <td class="center"><?php echo $physicalStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links"></td>
            </tr>
              <tr class="view-get_fit">
                 <td>
                    <strong>G</strong>. <?php echo $getFitStatus->getComplianceView()->getReportName(); ?>                    
                 </td>
                 <td></td>
                 <td class="points"><?php echo $getFitStatus->getPoints(); ?></td>
                 <td class="points">25</td>
                 <td class="links">
                    <?php foreach($getFitStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-volunteer">
                 <td>
                    <strong>H</strong>. <?php echo $volunteerStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $volunteerStatus->getPoints(); ?></td>
                 <td class="points">100</td>
                 <td class="links">
                    <?php foreach($volunteerStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-volunteer">
                 <td>
                    <strong>I</strong>. <?php echo $preventativeStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $preventativeStatus->getPoints(); ?></td>
                 <td class="points">100</td>
                 <td class="links">
                    <?php foreach($preventativeStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-volunteer">
                 <td>
                    <strong>J</strong>. <?php echo $wellnessStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $wellnessStatus->getPoints(); ?></td>
                 <td class="points">100</td>
                 <td class="links">
                    <?php foreach($wellnessStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-volunteer">
                 <td>
                    <strong>K</strong>. <?php echo $bcbsStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $bcbsStatus->getPoints(); ?></td>
                 <td class="points">100</td>
                 <td class="links">
                    <?php foreach($bcbsStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-volunteer">
                 <td>
                    <strong>L</strong>. <?php echo $mdLiveStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $mdLiveStatus->getPoints(); ?></td>
                 <td class="points">100</td>
                 <td class="links">
                    <?php foreach($mdLiveStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-volunteer">
                 <td>
                    <strong>M</strong>. <?php echo $eapStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $eapStatus->getPoints(); ?></td>
                 <td class="points">100</td>
                 <td class="links">
                    <?php foreach($eapStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr class="view-volunteer">
                 <td>
                    <strong>N</strong>. <?php echo $enterInfoStatus->getComplianceView()->getReportName(); ?>
                 </td>
                 <td></td>
                 <td class="points"><?php echo $enterInfoStatus->getPoints(); ?></td>
                 <td class="points">100</td>
                 <td class="links">
                    <?php foreach($enterInfoStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                 </td>
              </tr>
              <tr style="text-align:center;">
                <td>Points Earned & Possible as of <?php echo date("m/d/y")?></td>
                <td></td>
                <td><?php echo $status->getPoints(); ?></td>
                <td><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
                <td></td>
            </tr>
           </tbody>
           <tbody>
              <tr class="headerRow">
                 <td class="center">
                    <div style="width: 100%; text-align: left;">3. Status of All Criteria</div>
                 </td>
                 <td></td>
                 <td class="points"></td>
                 <td class="">Status</td>
                 <td colspan="">
                 </td>
              </tr>
              <tr class="bonus_panel">
                 <td>Core Reward status: Core Actions done as of <?php echo date('m/d/y'); ?> </td>
                 <td></td>
                 <td></td>
                 <td><img style="width:25px;" src="<?php echo $light_map[$coreGroupStatus->getStatus()]; ?>"></td>
                 <td><?php echo $overall_text; ?></td>
              </tr>
              <tr class="bonus_panel">
                 <td>Bonus Reward status: Core Actions done + ≥350 points earned</td>
                 <td></td>
                 <td></td>
                 <td><img style="width:25px;" src="<?php echo $overall_status; ?>"></td>
                 <td><?php echo $overall_text; ?></td>
              </tr>
           </tbody>
        </table>
            <?php

            // parent::printReport($status);
    }
}