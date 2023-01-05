<?php

use hpn\steel\query\SelectQuery;

class Harris2019CompleteScreeningView extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {
        if(!parent::evaluateStatus($user, $array)) {
            return false;
        }

        $requiredFields = array(
            'cholesterol'   => trim((string) $array['cholesterol']),
            'ldl'            => trim((string) $array['ldl']),
            'hdl'            => trim((string) $array['hdl']),
            'triglycerides'=> trim((string) $array['triglycerides']),
            'height'        => trim((string) $array['height']),
            'weight'        => trim((string) $array['weight']),
            'bmi'           => trim((string) $array['bmi']),
            'systolic'     => trim((string) $array['systolic']),
            'diastolic'    => trim((string) $array['diastolic'])
        );

        foreach($requiredFields as $requiredField) {
            if($requiredField == '' || $requiredField == '0') {
                return ComplianceStatus::PARTIALLY_COMPLIANT;
            }
        }

        if((trim((string) $array['glucose']) == '' || trim((string) $array['glucose']) == '0') && (trim((string) $array['ha1c']) == '' || trim((string) $array['ha1c']) == '0')) {
            return ComplianceStatus::PARTIALLY_COMPLIANT;
        }

        return ComplianceStatus::COMPLIANT;
    }

    public function getData(User $user)
    {
        $query = SelectQuery::create()
        ->select('s.id')
        ->from('screening s')
        ->innerJoin('users u')
        ->on('u.id = s.user_id')
        ->where('u.client_id = 3778')
        ->andWhere('(s.created_at <= ? || s.created_at >= ?)', array('2018-12-31', '2019-11-01'));

        $excludedScreeningIds = array();
        foreach($query->execute() as $row) {
            $excludedScreeningIds[] = $row['id'];
        }

        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter(),
                'excluded_screening_ids' => array_merge($excludedScreeningIds)
            )
        );

        return $data;
    }
}

class Harris2019HomePageComplianceProgram extends ComplianceProgram
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
        $screeningFormView->addLink(new Link('Download Form', '/resources/10348/Final_Harris_2019_PCP_Form_062519.pdf', false, '_blank'));
        $screeningFormView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));

        $screeningFormView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($screeningFormView) {
            $activeProgramRecord = ComplianceProgramRecordTable::getInstance()
                ->findApplicableActive($user->client);

            $userProgram = $activeProgramRecord->getComplianceProgram();
            $userProgram->setActiveUser($user);

            $programStatus = $userProgram->getStatus();


            if($activeProgramRecord->id == 1441) {
                if($programStatus->getComplianceViewStatus('complete_screening')->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });

        $group->addComplianceView($screeningFormView);

        $healthLinc2020View = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $healthLinc2020View->setAttribute('always_show_links_when_current', true);
        $healthLinc2020View->setName('healthLinc_2018');
        $healthLinc2020View->setReportName('Track Harris 2020 Wellness Program Status');
        $healthLinc2020View->addLink(new Link('View Status', '/compliance_programs?id=1441'));
        $healthLinc2020View->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $activeProgramRecord = ComplianceProgramRecordTable::getInstance()->find(1441);

            $userProgram = $activeProgramRecord->getComplianceProgram();
            $userProgram->setActiveUser($user);

            $programStatus = $userProgram->getStatus();

            $coreGroupStatus = $programStatus->getComplianceViewGroupStatus('core');

            if($coreGroupStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        });

        $group->addComplianceView($healthLinc2020View);
        $this->addComplianceViewGroup($group);
    }
}


class Harris2019ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();
        $currentProgram = $this;


        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Program');

        $screeningView = new Harris2019CompleteScreeningView($programStart, $programEnd);
        $screeningView->setReportName('2018 Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('requirement', 'Submit a Completed Preventive Care/Biometric Health Screening Form from your Primary Care Physician');
        $screeningView->setAttribute('deadline', '10/31/2019');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('See Results', '/content/989'));
        $screeningView->addLink(new Link('<br />Submit Form', '/content/chp-document-uploader'));
        $screeningView->addLink(new Link('<br />Download Form', '/resources/10348/Final_Harris_2019_PCP_Form_062519.pdf'));
        $coreGroup->addComplianceView($screeningView);

        $nicotineFormView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT, 0);
        $nicotineFormView->setReportName('Submit Nicotine Attestation Form');
        $nicotineFormView->setName('nicotine_form');
        $nicotineFormView->setAttribute('requirement', 'Submit Nicotine Attestation Form');
        $nicotineFormView->setAttribute('deadline', '10/31/2019');
        $nicotineFormView->emptyLinks();
        $nicotineFormView->addLink(new FakeLink('Admin will enter', '#'));
        $nicotineFormView->setPostEvaluateCallback(function($status) {
            if($status->getStatus() != ComplianceStatus::NOT_COMPLIANT || $status->getComment() != '') {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        });
        $coreGroup->addComplianceView($nicotineFormView);


        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'View Your Results');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        // While it contains the employee constant, an employee can't have a
        // related employee user so it doesn't matter.


        $BMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Mass Index (BMI)');
        $BMIView->setName('bmi');
        $BMIView->setAttribute('requirement', '18.5 – 24.9');
        $BMIView->overrideTestRowData(18.5, null, null, 24.9);
        $BMIView->addLink(new Link('Review Body Metrics Lessons', '/content/9420?action=lessonManager&tab_alias=body_fat'));
        $wellnessGroup->addComplianceView($BMIView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setName('blood_pressure');
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
        $ldlCholesterolView->setAttribute('requirement', '< 130 mg/dL');
        $ldlCholesterolView->overrideTestRowData(null, null, null, 129.999);
        $ldlCholesterolView->addLink(new Link('Review Blood Fat Lessons', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $wellnessGroup->addComplianceView($ldlCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setName('hdl');
        $hdlCholesterolView->setAttribute('requirement', '> 40 mg/dL');
        $hdlCholesterolView->overrideTestRowData(40.001, null, null, null);
        $wellnessGroup->addComplianceView($hdlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setName('triglycerides');
        $trigView->setAttribute('requirement', '< 150 mg/dL');
        $trigView->overrideTestRowData(null, null, null, 149.999);
        $trigView->addLink(new Link('Sign-Up', '/content/how-to-schedule'));
        $wellnessGroup->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setName('glucose');
        $glucoseView->setAttribute('requirement', '< 100 mg/dL');
        $glucoseView->overrideTestRowData(null, null, null, 99.999);
        $glucoseView->addLink(new Link('Review Blood Sugar Lessons', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $wellnessGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setName('cholesterol');
        $cholesterolView->setAttribute('requirement', '< 200 mg/dL');
        $cholesterolView->overrideTestRowData(null, null, null, 200);
        $cholesterolView->addLink(new Link('Review Blood Fat Lessons', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $wellnessGroup->addComplianceView($cholesterolView);

        $heightView = new ComplyWithHeightScreeningTestComplianceView($programStart, $programEnd);
        $heightView->setName('height');
        $heightView->setAttribute('requirement', 'N/A');
        $wellnessGroup->addComplianceView($heightView);

        $weightView = new ComplyWithWeightScreeningTestComplianceView($programStart, $programEnd);
        $weightView->setName('weight');
        $weightView->setAttribute('requirement', 'N/A');
        $wellnessGroup->addComplianceView($weightView);


        $wellnessGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($wellnessGroup);

    }

    public function getAdminProgramReportPrinter()
    {
        $that = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(true, true, true);
        $printer->setShowUserFields(true, true, true, true, true, true, null, null, true, true);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);
        $printer->setShowComment(false, false, false);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {

            $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

            $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
            $nicotineStatus = $coreGroupStatus->getComplianceViewStatus('nicotine_form');

            return array(
                'Program - Compliant' => $coreGroupStatus->isCompliant() ? 'Yes' : 'No',
                'Program - Comment' => $coreGroupStatus->getComment(),
                'Program - 2019 Wellness Screening - Compliant' => $completeScreeningStatus->isCompliant() ? 'Yes' : 'No',
                'Program - 2019 Wellness Screening - Comment' => $completeScreeningStatus->getComment(),
                'Program - Submit Nicotine Attestation Form - Compliant' => $nicotineStatus->isCompliant() ? 'Yes' : 'No',
                'Program - Submit Nicotine Attestation Form - Comment' => $nicotineStatus->getComment()
            );
        });


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new Harris2019ComplianceProgramReportPrinter();

        return $printer;
    }

}


class Harris2019ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
        $user = $status->getUser();
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $nicotineStatus = $coreGroupStatus->getComplianceViewStatus('nicotine_form');

        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness_programs');

        $optionalGroupStatus = $status->getComplianceViewGroupStatus('optional_cost');


        $bmiStatus = $wellnessGroupStatus->getComplianceViewStatus('bmi');
        $weightStatus = $wellnessGroupStatus->getComplianceViewStatus('weight');
        $heightStatus = $wellnessGroupStatus->getComplianceViewStatus('height');
        $bloodPressureStatus = $wellnessGroupStatus->getComplianceViewStatus('blood_pressure');
        $ldlStatus = $wellnessGroupStatus->getComplianceViewStatus('ldl');
        $hdlStatus = $wellnessGroupStatus->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $wellnessGroupStatus->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $wellnessGroupStatus->getComplianceViewStatus('glucose');
        $cholesterolStatus = $wellnessGroupStatus->getComplianceViewStatus('cholesterol');


        $endDateText = '10/31/2019';


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
        <div class="pageHeading">2020 Workplace Wellness Program</div>

        <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

        <P>

            Welcome to your summary page for the Harris Workplace Health Promotion Program. The current program is for
            employees only, spouses and/or dependents do not participate. Anyone who submits a completed qualified
            wellness screening from your Primary Care Physician AND the Nicotine Attestation from will receive the
            medical incentive for 2020.

            <br /> <br /> Thank you for your participation this year. Harris Product Groups encourages you to continue to participate in future wellness initiatives.

            <br /> <br /> <font color="red">Please note that it can take up until at least 1 week to get forms entered
                into the system after they are submitted. If you don’t see credit in the program please check back
                1 week after you’ve submitted the form before contacting tech support.
            </font> </P>

        <?php if($status->getUser()->hasAttribute(Attribute::VIEW_PHI)) : ?>
        <p>Click <a href="/content/chp-document-uploader?admin=1">here</a> to view uploaded files</p>
    <?php endif ?>



        <table class="phipTable">
            <tbody>
            <tr class="headerRow headerRow-core">
                <th class="center">1. Core Actions Required by <?php echo $endDateText ?></th>
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
                    10/31/2019
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
                <td class="links" rowspan="3">
                    <?php foreach($bmiStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>2</strong>. <?php echo $heightStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $heightStatus->getComment() ?></td>
                <td class="requirement"><?php echo $heightStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $heightStatus->getLight() ?>" class="light" /></td>
            </tr>

            <tr>
                <td>
                    <strong>3</strong>. <?php echo $weightStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $weightStatus->getComment() ?></td>
                <td class="requirement"><?php echo $weightStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $weightStatus->getLight() ?>" class="light" /></td>
            </tr>

            <tr>
                <td>
                    <strong>4</strong>. <?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?>
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
                    <strong>5</strong>. <?php echo $cholesterolStatus->getComplianceView()->getReportName() ?>
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
                    <strong>6</strong>. <?php echo $ldlStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $ldlStatus->getComment() ?></td>
                <td class="requirement"><?php echo $ldlStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $ldlStatus->getLight() ?>" class="light" /></td>
            </tr>

            <tr>
                <td>
                    <strong>7</strong>. <?php echo $hdlStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $hdlStatus->getComment() ?></td>
                <td class="requirement"><?php echo $hdlStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $hdlStatus->getLight() ?>" class="light" /></td>
            </tr>

            <tr>
                <td>
                    <strong>8</strong>. <?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="result"><?php echo $triglyceridesStatus->getComment() ?></td>
                <td class="requirement"><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="status"><img src="<?php echo $triglyceridesStatus->getLight() ?>" class="light" /></td>
            </tr>

            <tr>
                <td>
                    <strong>9</strong>. <?php echo $glucoseStatus->getComplianceView()->getReportName() ?>
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
