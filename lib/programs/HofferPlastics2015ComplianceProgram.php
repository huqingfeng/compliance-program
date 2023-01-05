<?php

use hpn\steel\query\SelectQuery;


class HofferPlastics2015ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Program');

        $hraView = new CompleteHRAComplianceView('2015-09-01', '2015-12-31');
        $hraView->setReportName('Health Power Profile Questionnaire');
        $hraView->setName('complete_hra');
        $hraView->setAttribute('requirement', 'Complete the Online Health Risk Assessment (HRA) Questionnaire');
        $hraView->setAttribute('deadline', '12/31/2015');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView('2015-09-01', '2015-12-31');
        $screeningView->setReportName('2015 Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('requirement', 'Complete the Biometric Health Screening');
        $screeningView->setAttribute('deadline', '12/31/2015');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/how-to-schedule'));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        // While it contains the employee constant, an employee can't have a
        // related employee user so it doesn't matter.

        $nonSmokerView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-tobacco User');
        $nonSmokerView->setAttribute('report_name_link', '/sitemaps/health_centers/15946');
        $nonSmokerView->setAttribute('requirement', 'Must be non-tobacco user based on HRA response');
        $nonSmokerView->addLink(new Link('Review Tobacco Lessons', '/content/9420?action=lessonManager&tab_alias=tobacco'));
        $wellnessGroup->addComplianceView($nonSmokerView);


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

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setName('cholesterol');
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setAttribute('requirement', '< 200 mg/dL');
        $totalCholesterolView->addLink(new Link('Review Blood Fat Lessons', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $totalCholesterolView->overrideTestRowData(null, null, null, 199.999);
        $wellnessGroup->addComplianceView($totalCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setName('ldl');
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $ldlCholesterolView->setAttribute('requirement', '< 130 mg/dL');
        $ldlCholesterolView->overrideTestRowData(null, null, null, 129.999);
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


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new HofferPlastics2015ComplianceProgramReportPrinter();

        return $printer;
    }


}


class HofferPlastics2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        $completeHraStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');

        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness_programs');

        $optionalGroupStatus = $status->getComplianceViewGroupStatus('optional_cost');


        $noTobaccoUserStatus = $wellnessGroupStatus->getComplianceViewStatus('non_smoker_view');
        $bmiStatus = $wellnessGroupStatus->getComplianceViewStatus('bmi');
        $bloodPressureStatus = $wellnessGroupStatus->getComplianceViewStatus('blood_pressure');
        $cholesterolStatus = $wellnessGroupStatus->getComplianceViewStatus('cholesterol');
        $ldlStatus = $wellnessGroupStatus->getComplianceViewStatus('ldl');
        $hdlStatus = $wellnessGroupStatus->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $wellnessGroupStatus->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $wellnessGroupStatus->getComplianceViewStatus('glucose');

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
        <div class="pageHeading">2015 Bison Cares for You Program</div>

        <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

                <table class="phipTable">
                    <tbody>
                    <tr class="headerRow headerRow-core">
                        <th class="center">1. Core Actions Required By 12/31/2015</th>
                        <th class="deadline">Deadline</th>
                        <th class="date-completed">Date Completed</th>
                        <th class="status">Status</th>
                        <th class="links">Links</th>
                    </tr>
                    <tr class="view-complete_hra">
                        <td>
                            <strong>A</strong>. <?php echo $completeHraStatus->getComplianceView()->getAttribute('requirement') ?>
                        </td>
                        <td class="deadline">
                            <?php echo $completeHraStatus->getComplianceView()->getAttribute('deadline') ?>
                        </td>
                        <td class="date-completed">
                            <?php echo $completeHraStatus->getComment() ?>
                        </td>
                        <td class="status">
                            <img src="<?php echo $completeHraStatus->getLight(); ?>" class="light"/>
                        </td>
                        <td class="links">
                            <?php foreach($completeHraStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>
                    <tr class="view-complete_screening">
                        <td>
                            <strong>B</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?>
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

                    <tr class="headerRow headerRow-wellness_programs">
                        <td class="center">2. Biometrics Monitored</td>
                        <td class="result">Result</td>
                        <td class="requirement">Required Ranges</td>
                        <td class="status">Status</td>
                        <td class="links">Links</td>
                    </tr>
                    <tr>
                        <td>
                            <strong>1</strong>. <?php echo $noTobaccoUserStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td class="result"><?php echo $noTobaccoUserStatus->getComment() ?></td>
                        <td class="requirement"><?php echo $noTobaccoUserStatus->getComplianceView()->getAttribute('requirement') ?></td>
                        <td class="status"><img src="<?php echo $noTobaccoUserStatus->getLight() ?>" class="light" /></td>
                        <td class="links">
                            <?php foreach($noTobaccoUserStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <strong>2</strong>. <?php echo $bmiStatus->getComplianceView()->getReportName() ?>
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
                            <strong>3</strong>. <?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?>
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
                            <strong>4</strong>. <?php echo $cholesterolStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td class="result"><?php echo $cholesterolStatus->getComment() ?></td>
                        <td class="requirement"><?php echo $cholesterolStatus->getComplianceView()->getAttribute('requirement') ?></td>
                        <td class="status"><img src="<?php echo $cholesterolStatus->getLight() ?>" class="light" /></td>
                        <td class="links" rowspan="4">
                            <?php foreach($cholesterolStatus->getComplianceView()->getLinks() as $link) {
                                echo $link->getHTML()."\n";
                            }?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>4</strong>. <?php echo $ldlStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td class="result"><?php echo $ldlStatus->getComment() ?></td>
                        <td class="requirement"><?php echo $ldlStatus->getComplianceView()->getAttribute('requirement') ?></td>
                        <td class="status"><img src="<?php echo $ldlStatus->getLight() ?>" class="light" /></td>
                    </tr>

                    <tr>
                        <td>
                            <strong>5</strong>. <?php echo $hdlStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td class="result"><?php echo $hdlStatus->getComment() ?></td>
                        <td class="requirement"><?php echo $hdlStatus->getComplianceView()->getAttribute('requirement') ?></td>
                        <td class="status"><img src="<?php echo $hdlStatus->getLight() ?>" class="light" /></td>
                    </tr>

                    <tr>
                        <td>
                            <strong>6</strong>. <?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td class="result"><?php echo $triglyceridesStatus->getComment() ?></td>
                        <td class="requirement"><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('requirement') ?></td>
                        <td class="status"><img src="<?php echo $triglyceridesStatus->getLight() ?>" class="light" /></td>
                    </tr>

                    <tr>
                        <td>
                            <strong>7</strong>. <?php echo $glucoseStatus->getComplianceView()->getReportName() ?>
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
