<?php

use hpn\steel\query\SelectQuery;

class LincolnElectric2016CompleteScreeningView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'use_creation_date' => true,
                'filter'           => $this->getFilter()
            )
        );

        return $data;
    }

    protected function evaluateStatus(User $user, $array)
    {
        if(!parent::evaluateStatus($user, $array)) {
            return false;
        }

        $cholesterol = trim((string) $array['cholesterol']);

        $ldl = trim((string) $array['ldl']);

        $hdl = trim((string) $array['hdl']);

        $triglycerides = trim((string) $array['triglycerides']);

        if($cholesterol == '' || $cholesterol == '0'
            || $ldl == '' || $ldl == '0'
            || $hdl == '' || $hdl == '0'
            || $triglycerides == '' || $triglycerides == '0') {

            return ComplianceStatus::PARTIALLY_COMPLIANT;
        }

        return ComplianceStatus::COMPLIANT;
    }
}

class LincolnElectric2016HomePageComplianceProgram extends ComplianceProgram
{
    public function loadEvaluators()
    {

    }

    public function loadGroups()
    {
        $group = new ComplianceViewGroup('Procedure');

        $screeningFormView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $screeningFormView->setReportName('Primary Care Physician Screening Form');
        $screeningFormView->setName('primary_care_physician_screening_form');
        $screeningFormView->emptyLinks();
        $screeningFormView->setAttribute('always_show_links', true);
        $screeningFormView->setAttribute('always_show_links_when_current', true);
        $screeningFormView->addLink(new Link('Download Form', '/resources/7953/FINAL PCP Form 2017 Program 071216.pdf', false, '_blank'));
        $screeningFormView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));

        $screeningFormView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($screeningFormView) {
            $activeProgramRecord = ComplianceProgramRecordTable::getInstance()
                ->findApplicableActive($user->client);

            $userProgram = $activeProgramRecord->getComplianceProgram();
            $userProgram->setActiveUser($user);

            $programStatus = $userProgram->getStatus();


            if($activeProgramRecord->id == 662) {
                if($programStatus->getComplianceViewStatus('complete_screening')->getStatus() == ComplianceStatus::COMPLIANT
                    && $programStatus->getComplianceViewStatus('nicotine_form')->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });

        $group->addComplianceView($screeningFormView);


        $healthLinc2016View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $healthLinc2016View->setAttribute('always_show_links_when_current', true);
        $healthLinc2016View->setName('healthLinc_2016');
        $healthLinc2016View->setReportName('Track HealthLinc Status 2016 Program');
        $healthLinc2016View->addLink(new Link('Click to View Status', '/compliance_programs'));
        $healthLinc2016View->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {

            $activeProgramRecord = ComplianceProgramRecordTable::getInstance()
                ->findApplicableActive($user->client);

            $userProgram = $activeProgramRecord->getComplianceProgram();
            $userProgram->setActiveUser($user);

            $programStatus = $userProgram->getStatus();


            if($activeProgramRecord->id == 662) {
                if($programStatus->getComplianceViewStatus('complete_screening')->getStatus() == ComplianceStatus::COMPLIANT
                    && $programStatus->getComplianceViewStatus('nicotine_form')->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });

        $group->addComplianceView($healthLinc2016View);

        $healthLinc2017View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $healthLinc2017View->setAttribute('always_show_links_when_current', true);
        $healthLinc2017View->setName('healthLinc_2017');
        $healthLinc2017View->setReportName('Track HealthLinc Status 2017 Program');
        $healthLinc2017View->addLink(new FakeLink('<div style="margin-top: -20px; color: red;">Tracking page will not be <br /> available until August 1st, 2016</div>', '#'));

        $group->addComplianceView($healthLinc2017View);

        $this->addComplianceViewGroup($group);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        if (($config = sfConfig::get('mod_compliance_programs_hmi_website_flow_record_integration'))
            && isset($config['compliance_program_record_id'], $config['views'])) {
            $record = ComplianceProgramRecordTable::getInstance()->find($config['compliance_program_record_id']);
            $extProgram = $record->getComplianceProgram() ;
            $extProgram->setActiveUser($status->getUser());
            $extStatus = $extProgram->getStatus();

            foreach($config['views'] as $extViewName => $localViewName) {
                if ($extStatus->getComplianceViewStatus($extViewName)->isCompliant()) {
                    $status->getComplianceViewStatus($localViewName)->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        }
    }
}


class LincolnElectric2016ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Program');

        $screeningView = new LincolnElectric2016CompleteScreeningView($this->getStartDate('Y-m-d H:i:s'), '2016-06-01 23:59:59');
        $screeningView->setReportName('2015 Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('requirement', 'Submit a Completed Preventive Care/Biometric Health Screening Form from your Primary Care Physician');
        $screeningView->setAttribute('deadline', '06/01/2016');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('See Results', '/content/989'));
        $screeningView->addLink(new Link('<br />Submit Form', '/content/chp-document-uploader'));
        $coreGroup->addComplianceView($screeningView);

        $nicotineFormView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT, 0);
        $nicotineFormView->setReportName('Submit Nicotine Attestation Form');
        $nicotineFormView->setName('nicotine_form');
        $nicotineFormView->setAttribute('requirement', 'Submit Nicotine Attestation Form');
        $nicotineFormView->setAttribute('deadline', '06/01/2016');
        $nicotineFormView->emptyLinks();
        $nicotineFormView->addLink(new FakeLink('Admin will enter', '#'));
        $coreGroup->addComplianceView($nicotineFormView);


        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'View Your Results');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        // While it contains the employee constant, an employee can't have a
        // related employee user so it doesn't matter.


        $BMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Mass Index (BMI)');
        $BMIView->setName('bmi');
        $BMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $BMIView->setAttribute('requirement', '18.5 – 24.9');
        $BMIView->overrideTestRowData(18.5, null, null, 24.9);
        $BMIView->addLink(new Link('Review Body Metrics Lessons', '/content/9420?action=lessonManager&tab_alias=body_fat'));
        $wellnessGroup->addComplianceView($BMIView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAttribute('requirement_systolic', 'Systolic 130 or lower');
        $bloodPressureView->setAttribute('requirement_diastolic', 'Diastolic 84 or lower');
        $bloodPressureView->overrideSystolicTestRowData(null, null, null, 130);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, null, 84);
        $bloodPressureView->addLink(new Link('Review Blood Pressure Lessons', '/content/9420?action=lessonManager&tab_alias=blood_pressure'));
        $bloodPressureView->setPostEvaluateCallback(function($status) {
            if($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        });
        $wellnessGroup->addComplianceView($bloodPressureView);


        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setName('ldl');
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $ldlCholesterolView->setAttribute('requirement', '< 130 mg/dL');
        $ldlCholesterolView->overrideTestRowData(null, null, null, 129.999);
        $ldlCholesterolView->addLink(new Link('Review Blood Fat Lessons', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $wellnessGroup->addComplianceView($ldlCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setName('hdl');
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setAttribute('requirement', '> 40 mg/dL');
        $hdlCholesterolView->overrideTestRowData(40.001, null, null, null);
        $wellnessGroup->addComplianceView($hdlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setName('triglycerides');
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAttribute('requirement', '< 150 mg/dL');
        $trigView->overrideTestRowData(null, null, null, 149.999);
        $trigView->addLink(new Link('Sign-Up', '/content/how-to-schedule'));
        $wellnessGroup->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setName('glucose');
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAttribute('requirement', '< 100 mg/dL');
        $glucoseView->overrideTestRowData(null, null, null, 99.999);
        $glucoseView->addLink(new Link('Review Blood Sugar Lessons', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $wellnessGroup->addComplianceView($glucoseView);

        $wellnessGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($wellnessGroup);

    }

    public function getAdminProgramReportPrinter()
    {
        $that = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(true, true, true);
        $printer->setShowUserFields(true, true, true, true, true, true, null, null, true);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);
        $printer->setShowComment(false, false, false);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {

            $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

            $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
            $nicotineStatus = $coreGroupStatus->getComplianceViewStatus('nicotine_form');

            return array(
                'Program - Compliant' => $coreGroupStatus->isCompliant() ? 'Yes' : 'No',
                'Program - Points' => $coreGroupStatus->getPoints(),
                'Program - Comment' => $coreGroupStatus->getComment(),
                'Program - 2015 Wellness Screening - Compliant' => $completeScreeningStatus->isCompliant() ? 'Yes' : 'No',
                'Program - 2015 Wellness Screening - Points' => $completeScreeningStatus->getPoints(),
                'Program - 2015 Wellness Screening - Comment' => $completeScreeningStatus->getComment(),
                'Program - Submit Nicotine Attestation Form - Compliant' => $nicotineStatus->isCompliant() ? 'Yes' : 'No',
                'Program - Submit Nicotine Attestation Form - Points' => $nicotineStatus->getPoints(),
                'Program - Submit Nicotine Attestation Form - Comment' => $nicotineStatus->getComment()
            );
        });


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new LincolnElectric2016ComplianceProgramReportPrinter();

        return $printer;
    }


}


class LincolnElectric2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $nicotineStatus = $coreGroupStatus->getComplianceViewStatus('nicotine_form');

        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness_programs');

        $optionalGroupStatus = $status->getComplianceViewGroupStatus('optional_cost');


        $bmiStatus = $wellnessGroupStatus->getComplianceViewStatus('bmi');
        $bloodPressureStatus = $wellnessGroupStatus->getComplianceViewStatus('blood_pressure');
        $ldlStatus = $wellnessGroupStatus->getComplianceViewStatus('ldl');
        $hdlStatus = $wellnessGroupStatus->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $wellnessGroupStatus->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $wellnessGroupStatus->getComplianceViewStatus('glucose');

        ?>
        <style type="text/css">
            .pageHeading {
                font-weight:bold;
                margin-bottom:20px;
                text-align:center;
            }

            .phipTable .headerRow {
                background-color: #0033FF;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
                height:36px;
                text-align: center;
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

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .links {
                text-align: center;
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

            .deadline, .result {
                width:100px;
                text-align: center;
            }

            .date-completed, .requirement, .status, .tier_hra, .tier_screening, .tier_num, .tier_premium {
                text-align: center;
            }

            #tier_table {
                margin:0 auto;
            }

            #tier_table td{
                padding-right: 20px;
                border-bottom:1px solid black;
                padding-top: 10px;
            }

            #tier_table span {
                color: red;
            }

            #bottom_statement {
                padding-top:20px;
            }

            #tier_total {
                font-weight: bold;
                text-align: center;
            }
        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned
        </script>
        <!-- Text atop report card-->
        <div class="pageHeading">2016 HealthLinc Workplace Wellness Program</div>

        <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

        <P>

            Welcome to your summary page for HealthLinc. This program is designed to promote health awareness,
            encourage healthy habits and bring our workforce together by fostering a culture that cares for each
            individual’s wellbeing. You are eligible to participate in this program. The program does not apply
            to spouses or dependents. Anyone who submits a completed qualified wellness screening from your Primary
            Care Physician OR completes the Nicotine Attestation form will receive the medical premium incentive.

           <br /> <br /> Thank you for your participation this year. The Company encourages you to continue to participate in future wellness initiatives.

            <br /> <br /> <font color="red">Please note that biometric screening and nicotine attestation status are updated weekly on Fridays. </font> </P>

            <?php if($status->getUser()->hasAttribute(Attribute::VIEW_PHI)) : ?>
            <p>Click <a href="/content/chp-document-uploader?admin=1">here</a> to view uploaded files</p>
            <?php endif ?>



        <table class="phipTable">
            <tbody>
            <tr class="headerRow headerRow-core">
                <th class="center">1. Core Actions Required By 06/01/2016</th>
                <th class="deadline">Deadline</th>
                <th class="date-completed">Date Completed</th>
                <th class="status">Status</th>
                <th class="links">Links</th>
            </tr>
            <tr>
                <td>
                    <strong>A</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?>
                </td>
                <td class="deadline">
                    <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="date-completed">
                    <?php echo $completeScreeningStatus->getComment() ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="center">
                    <?php foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>B</strong>. <?php echo $nicotineStatus->getComplianceView()->getAttribute('requirement') ?>
                </td>
                <td class="deadline">
                    <?php echo $nicotineStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="date-completed">
                    <?php echo $nicotineStatus->getComment() ?>
                </td>
                <td class="center">
                    <img src="<?php echo $nicotineStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>
            <tr>
                <td style="text-align: right">
                    Have You Earned The Program Incentive?
                </td>
                <td class="deadline">
                    06/01/2016
                </td>
                <td class="date-completed">
                    <?php echo $coreGroupStatus->getComment() ?>
                </td>
                <td class="center">
                    <img src="<?php echo $coreGroupStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="center" style="background-color: #0033FF;">
                </td>
            </tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">2. Biometrics Monitored</td>
                <td class="result">Result</td>
                <td class="requirement">Ideal Ranges</td>
                <td class="status">Status</td>
                <td class="links">Educational Resources</td>
            </tr>

            <tr>
                <td>
                    <strong>1</strong>. <?php echo $bmiStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $bmiStatus->getComment() ?></td>
                <td class="requirement"><?php echo $bmiStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $bmiStatus->getLight() ?>" class="light" /></td>
                <td class="links">
                    <?php foreach($bmiStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>2</strong>. <?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $bloodPressureStatus->getComment() ?></td>
                <td class="requirement">
                    <?php echo $bloodPressureStatus->getComplianceView()->getAttribute('requirement_systolic') ?><br />
                    <?php echo $bloodPressureStatus->getComplianceView()->getAttribute('requirement_diastolic') ?>

                </td>
                <td class="status"><img src="<?php echo $bloodPressureStatus->getLight() ?>" class="light" /></td>
                <td class="links">
                    <?php foreach($bloodPressureStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>3</strong>. <?php echo $ldlStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $ldlStatus->getComment() ?></td>
                <td class="requirement"><?php echo $ldlStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $ldlStatus->getLight() ?>" class="light" /></td>
                <td class="links" rowspan="3">
                    <?php foreach($ldlStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>4</strong>. <?php echo $hdlStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $hdlStatus->getComment() ?></td>
                <td class="requirement"><?php echo $hdlStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $hdlStatus->getLight() ?>" class="light" /></td>
            </tr>

            <tr>
                <td>
                    <strong>5</strong>. <?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $triglyceridesStatus->getComment() ?></td>
                <td class="requirement"><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $triglyceridesStatus->getLight() ?>" class="light" /></td>
            </tr>

            <tr>
                <td>
                    <strong>6</strong>. <?php echo $glucoseStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $glucoseStatus->getComment() ?></td>
                <td class="requirement"><?php echo $glucoseStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $glucoseStatus->getLight() ?>" class="light" /></td>
                <td class="links">
                    <?php foreach($glucoseStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>



            </tbody>
        </table>
        <?php
    }


    public $showUserNameInLegend = true;
}
