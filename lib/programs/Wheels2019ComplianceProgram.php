<?php

require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';


class Wheels2019ComplianceProgram extends ComplianceProgram
{


    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(true, true, true, null, null, null, null, null, true);
        $printer->setShowUserContactFields(true);


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Wheels2019ScreeningTestPrinter();
            $printer->setShowLight(true);
            $printer->setShowPoints(false);

            return $printer;
        } else {
            $printer = new Wheels2019ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('OK or Done', '/images/lights/greenlight.gif'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Borderline', '/images/lights/yellowlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done', '/images/lights/redlight.gif'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Received Yet', '/images/lights/whitelight.gif')
        ));

        $this->setComplianceStatusMapper($mapping);

        $screeningModel = new ComplianceScreeningModel();

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $core = new ComplianceViewGroup('core', 'Core Actions Required');

        $coreEndDate = '2020-08-21';

        $scrView = new CompleteScreeningComplianceView($startDate, $coreEndDate);
        $scrView->setReportName('Complete the Wellness Screening');
        $scrView->setAttribute('deadline', '10/15/2019');
        $scrView->setName('screening');
        $scrView->emptyLinks();
        $scrView->addLink(new link('Sign-Up', '/content/advocatescreening'));
        $scrView->addLink(new link('Results', '/content/989'));
        $core->addComplianceView($scrView);

        $hraView = new CompleteHRAComplianceView($startDate, $coreEndDate);
        $hraView->setReportName('Complete the Health Risk Assessment (HRA) Questionnaire');
        $hraView->setName('hra');
        $hraView->setAttribute('deadline', '10/15/2019');
        $core->addComplianceView($hraView);


        $tobacco = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobacco->setName('tobacco');
        $tobacco->setReportName('Screen Tobacco Free (Negative for Nicotine)');
        $tobacco->setAttribute('deadline',  '10/15/2019');
        $tobacco->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {


        });
        $core->addComplianceView($tobacco);

        $this->addComplianceViewGroup($core);


        $pointsGroups = new ComplianceViewGroup('points', 'Screening Results');
        $pointsGroups->setNumberOfViewsRequired(6);

        $hdlCholesterolView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $hdlCholesterolView->setName('hdl');
        $hdlCholesterolView->setReportName('HDL Cholesterol');
        $hdlCholesterolView->setAttribute('goal', '> 40 Men;  > 50 Women');
        $hdlCholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlCholesterolView->setAllowPointsOverride(true);
        $pointsGroups->addComplianceView($hdlCholesterolView);

        $trigView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $trigView->setName('triglycerides');
        $trigView->setReportName('Triglycerides');
        $trigView->setAttribute('goal', '<150');
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $trigView->setAllowPointsOverride(true);
        $pointsGroups->addComplianceView($trigView, false);

        $glucoseView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $glucoseView->setName('glucose');
        $glucoseView->setReportName('Glucose');
        $glucoseView->setAttribute('goal', '<100');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $glucoseView->setAllowPointsOverride(true);
        $pointsGroups->addComplianceView($glucoseView);

        $systolicView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $systolicView->setName('systolic');
        $systolicView->setReportName('a) Blood Pressure - Systolic;  and');
        $systolicView->setAttribute('goal', '<120');
        $systolicView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $systolicView->setAllowPointsOverride(true);
        $pointsGroups->addComplianceView($systolicView);

        $diastolicView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $diastolicView->setName('diastolic');
        $diastolicView->setReportName('b) Blood Pressure - Diastolic');
        $diastolicView->setAttribute('goal', '<80');
        $diastolicView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $diastolicView->setAllowPointsOverride(true);
        $pointsGroups->addComplianceView($diastolicView);

        $bmiView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bmiView->setReportName('a) BMI;  or');
        $bmiView->setName('bmi');
        $bmiView->setAttribute('goal', '18-24.9');
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAllowPointsOverride(true);
        $pointsGroups->addComplianceView($bmiView);

        $bodyFatView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bodyFatView->setReportName('b) Body Fat');
        $bodyFatView->setName('bodyfat');
        $bodyFatView->setAttribute('goal', 'Varies by gender & age.');
        $bodyFatView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bodyFatView->setAllowPointsOverride(true);
        $pointsGroups->addComplianceView($bodyFatView);


        $this->addComplianceViewGroup($pointsGroups);


        $remainingGroup = new ComplianceViewGroup('remaining', 'Next Steps / Remaining To-Dos');


        $sixHealthCoachingView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $sixHealthCoachingView->setReportName('Complete 6 Health Coaching Sessions');
        $sixHealthCoachingView->setName('6_health_coaching');
        $remainingGroup->addComplianceView($sixHealthCoachingView);

        $twoHealthActivitiesByCoachView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $twoHealthActivitiesByCoachView->setReportName('Complete 2 Health Activities assigned by Health Coach');
        $twoHealthActivitiesByCoachView->setName('2_health_activities_by_coach');
        $remainingGroup->addComplianceView($twoHealthActivitiesByCoachView);

        $fourOnsiteSessionsView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $fourOnsiteSessionsView->setReportName('Complete 4 Onsite Sessions with Nurse Care Manager');
        $fourOnsiteSessionsView->setName('4_onsite_sessions');
        $remainingGroup->addComplianceView($fourOnsiteSessionsView);

        $twoHealthActivitiesByNurseView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $twoHealthActivitiesByNurseView->setReportName('Complete 2 Health Activities assigned by Nurse Care Manager');
        $twoHealthActivitiesByNurseView->setName('2_health_activities_by_nurse');
        $remainingGroup->addComplianceView($twoHealthActivitiesByNurseView);


        $smokingCessationView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $smokingCessationView->setReportName('Smoking Cessation Health Coaching');
        $smokingCessationView->setName('smoking_cessation');
        $remainingGroup->addComplianceView($smokingCessationView);



        $this->addComplianceViewGroup($remainingGroup);

    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');

        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('glucose');
        $systolicStatus = $pointGroupStatus->getComplianceViewStatus('systolic');
        $diastolicStatus = $pointGroupStatus->getComplianceViewStatus('diastolic');
        $bmiStatus = $pointGroupStatus->getComplianceViewStatus('bmi');
        $bodyfatStatus = $pointGroupStatus->getComplianceViewStatus('bodyfat');


        $totalPoints = 0;
        $bloodPressureCompliant = $systolicStatus->getPoints() == 1 && $diastolicStatus->getPoints() == 1;
        $bmiBodyfatCompliant = $bmiStatus->getPoints() == 1 || $bodyfatStatus->getPoints() == 1;

        if($hdlStatus->getPoints() == 1) {
            $totalPoints += 1;
        }

        if($triglyceridesStatus->getPoints() == 1) {
            $totalPoints += 1;
        }

        if($glucoseStatus->getPoints() == 1) {
            $totalPoints += 1;
        }

        if($bloodPressureCompliant) {
            $totalPoints += 1;
        }

        if($bmiBodyfatCompliant) {
            $totalPoints += 1;
        }

        $pointGroupStatus->setPoints($totalPoints);
    }

}

class Wheels2019ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $screeningStatus = $coreGroupStatus->getComplianceViewStatus('screening');
        $hraStatus = $coreGroupStatus->getComplianceViewStatus('hra');
        $tobaccoStatus = $coreGroupStatus->getComplianceViewStatus('tobacco');

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('glucose');
        $systolicStatus = $pointGroupStatus->getComplianceViewStatus('systolic');
        $diastolicStatus = $pointGroupStatus->getComplianceViewStatus('diastolic');
        $bmiStatus = $pointGroupStatus->getComplianceViewStatus('bmi');
        $bodyfatStatus = $pointGroupStatus->getComplianceViewStatus('bodyfat');

        $remainingGroupStatus = $status->getComplianceViewGroupStatus('remaining');
        $sixHealthCoachingStatus = $remainingGroupStatus->getComplianceViewStatus('6_health_coaching');
        $twoHealthActivitiesByCoachStatus = $remainingGroupStatus->getComplianceViewStatus('2_health_activities_by_coach');
        $fourOnsiteSessionsStatus = $remainingGroupStatus->getComplianceViewStatus('4_onsite_sessions');
        $twoHealthActivitiesByNurseStatus = $remainingGroupStatus->getComplianceViewStatus('2_health_activities_by_nurse');
        $smokingCessationStatus = $remainingGroupStatus->getComplianceViewStatus('smoking_cessation');

        $bloodPressureCompliant = $systolicStatus->getPoints() == 1 && $diastolicStatus->getPoints() == 1;
        $bmiBodyfatCompliant = $bmiStatus->getPoints() == 1 || $bodyfatStatus->getPoints() == 1;

        ?>
        <style type="text/css">
            .phipTable ul, .phipTable li {
                margin-top:0px;
                margin-bottom:0px;
                padding-top:0px;
                padding-bottom:0px;
            }

            .pageHeading {
                font-weight:bold;
                text-align:center;
                margin-bottom:20px;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .phipTable .headerRow {
                background-color:#2e75b3;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            .phipTable .headerRow th {
                text-align:left;
                font-weight:normal;
            }

            .phipTable .headerRow td {
                text-align:center;
            }

            .phipTable .links {
                text-align:center;
            }

            .phipTable .left {
                /*padding-left:20px*/
            }

            .center {
                text-align:center;
            }

            .white {
                background-color:#FFFFFF;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .right {
                text-align:right;
            }

            #legend, #legend tr, #legend td {
                padding:0px;
                margin:0px;
            }

            #legend td {

                padding-bottom:5px;
            }

            #legendText {
                text-align:center;
                background-color:#2e75b3;
                font-weight:normal;
                color:#FFFFFF;
                font-size:12pt;
                margin-bottom:5px;
            }

            .legendEntry {
                width:160px;
                float:left;
                text-align:center;
                padding-left:2px;
            }

            .number {
                width: 20px;
            }
        </style>
        <div class="pageHeading">Rewards/To-Do Summary Page</div>
        <p>Hello <?php echo $status->getUser() ?>,</p>
        <p></p>
        <p>Welcome to your Wellness On Wheels Incentive/To-Do summary page.</p>

        <p>Use the Action Links in the last column to get things done and learn more.</p>


       

        <?php if($pointGroupStatus->getPoints() <= 1) : ?>
        <p style="text-align: center; font-size: 12pt; font-weight: bold;">0-1 Health Risks</p>
        <?php elseif($pointGroupStatus->getPoints() >= 2 && $pointGroupStatus->getPoints() <= 3) : ?>
        <p style="text-align: center; font-size: 12pt; font-weight: bold;">2-3 Health Risks</p>
        <?php else : ?>
        <p style="text-align: center; font-size: 12pt; font-weight: bold;">4+ Health Risks</p>
        <?php endif ?>

        <table class="phipTable" border="1">
            <tbody>
            <tr class="headerRow" style="height: 50px;">
                <th colspan="3">A. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
                <td>Deadline</td>
                <td>Date Completed</td>
                <td colspan="2">Goal Met</td>
                <td>Links</td>
            </tr>
            <tr>
                <td class="center number">1</td>
                <td colspan="2"><?php echo $screeningStatus->getComplianceView()
                            ->getReportName() ?></td>
                <td class="center">
                    <?php echo $screeningStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <?php echo $screeningStatus->getComment(); ?>
                </td>

                <td class="center" colspan="2">
                    <?php echo $screeningStatus->getStatus() == ComplianceStatus::COMPLIANT ? "Yes" : "";?>
                </td>

                <td class="links">
                    <?php
                    foreach($screeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td class="center">2</td>
                <td colspan="2"><?php echo $hraStatus->getComplianceView()
                        ->getReportName() ?></td>
                <td class="center">
                    <?php echo $hraStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <?php echo $hraStatus->getComment(); ?>
                </td>

                <td class="center" colspan="2">
                    <?php echo $hraStatus->getStatus() == ComplianceStatus::COMPLIANT ? "Yes" : "";?>
                </td>

                <td class="links">
                    <?php
                    foreach($hraStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td class="center">3</td>
                <td colspan="2"><?php echo $tobaccoStatus->getComplianceView()
                        ->getReportName() ?></td>
                <td class="center">
                    <?php echo $tobaccoStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <?php echo $tobaccoStatus->getComment(); ?>
                </td>

                <td class="center" colspan="2">
                    <?php echo $tobaccoStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : ($tobaccoStatus->getComment() ? 'No' : '');?>
                </td>
                <td class="links">
                    <?php
                    foreach($tobaccoStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>




            <tr class="headerRow" style="height: 50px;">
                <th colspan="3">B. <?php echo $pointGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td colspan="2">Healthy Ranges</td>
                <td>Result</td>
                <td>Risk Factors</td>
                <td>Links</td>
            </tr>

            <tr>
                <td class="center">1</td>
                <td colspan="2" class="left"><?php echo $hdlStatus->getComplianceView()->getReportName() ?></td>
                <td class="center" colspan="2"><?php echo $hdlStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center"><?php echo $hdlStatus->getComment(); ?></td>
                <td class="center"><?php echo $hdlStatus->getPoints(); ?></td>
                <td rowspan="8" class="links">
                    <a href="/resources/10453/Wheels_Risk_Factor_Ranges_093019.pdf" target="_blank">Results Incentive Goal Details</a>
                </td>
            </tr>

            <tr>
                <td class="center">2</td>
                <td colspan="2" class="left"><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></td>
                <td class="center" colspan="2"><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComment(); ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
            </tr>

            <tr>
                <td class="center">3</td>
                <td colspan="2" class="left"><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></td>
                <td class="center" colspan="2"><?php echo $glucoseStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center"><?php echo $glucoseStatus->getComment(); ?></td>
                <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
            </tr>

            <tr>
                <td class="center" rowspan="2">4</td>
                <td colspan="2" class="left"><?php echo $systolicStatus->getComplianceView()->getReportName() ?></td>
                <td class="center" colspan="2"><?php echo $systolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center"><?php echo $systolicStatus->getComment(); ?></td>
                <td class="center" rowspan="2"><?php echo $bloodPressureCompliant ? '1' : '0' ?></td>
            </tr>

            <tr>
                <td colspan="2" class="left"><?php echo $diastolicStatus->getComplianceView()->getReportName() ?></td>
                <td class="center" colspan="2"><?php echo $diastolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center"><?php echo $diastolicStatus->getComment(); ?></td>
            </tr>

            <tr>
                <td class="center" rowspan="2">5</td>
                <td  colspan="2"class="left"><?php echo $bmiStatus->getComplianceView()->getReportName() ?></td>
                <td class="center" colspan="2"><?php echo $bmiStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center"><?php echo $bmiStatus->getComment(); ?></td>
                <td class="center" rowspan="2"><?php echo $bmiBodyfatCompliant ? '1' : '0' ?></td>
            </tr>

            <tr>
                <td  colspan="2"class="left"><?php echo $bodyfatStatus->getComplianceView()->getReportName() ?></td>
                <td class="center" colspan="2"><?php echo $bodyfatStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center"><?php echo $bodyfatStatus->getComment(); ?></td>
            </tr>

            <tr>
                <td class="center">6</td>
                <td class="left" colspan="5">Totals</td>
                <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
            </tr>


            <tr class="headerRow" style="height: 50px;">
                <th colspan="2">C.  Next Steps / Remaining To-Dos </th>
                <td>If applicable --></td>
                <td class="center">Enroll By</td>
                <td class="center">Status</td>
                <td class="center">Complete By</td>
                <td class="center">Status</td>
                <td class="center">Links</td>
            </tr>


            <?php $count = 1 ?>
            <?php if($coreGroupStatus->getStatus() == ComplianceStatus::COMPLIANT && $pointGroupStatus->getPoints() <= 1) : ?>
            <tr>
                <td colspan="8">
                    <p class="alert alert-info" style="margin: 5px; text-align: center; font-size: 10pt;">
                        Congrats!	Keep up the great work maintaining your health goals, knowing your numbers and remaining tobacco free.
                    </p>

                </td>
            </tr>

            <?php elseif($tobaccoStatus->getComment() && $tobaccoStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT && $pointGroupStatus->getPoints() <= 1) : ?>
            <tr>
                <td class="center"><?php echo $count++ ?></td>
                <td colspan="2"><?php echo $smokingCessationStatus->getComplianceView()->getReportName() ?></td>
                <td class="center">3/2/2020</td>
                <td class="center"><?php echo $smokingCessationStatus->getComment() ?></td>
                <td class="center">8/21/2020</td>
                <td class="center"><img src="<?php echo $smokingCessationStatus->getLight() ?>" class="light" /></td>
                <td class="center">Enroll</td>
            </tr>

            <?php elseif($pointGroupStatus->getPoints() >= 2 && $pointGroupStatus->getPoints() <= 3) : ?>
                <?php if($tobaccoStatus->getComment() && $tobaccoStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT) : ?>
                <tr>
                    <td class="center"><?php echo $count++ ?></td>
                    <td colspan="2"><?php echo $smokingCessationStatus->getComplianceView()->getReportName() ?></td>
                    <td class="center">3/2/2020</td>
                    <td class="center"><?php echo $smokingCessationStatus->getComment() ?></td>
                    <td class="center">8/21/2020</td>
                    <td class="center"><img src="<?php echo $smokingCessationStatus->getLight() ?>" class="light" /></td>
                    <td class="center">Enroll</td>
                </tr>
                <?php endif ?>
                <tr>
                    <td class="center"><?php echo $count++ ?></td>
                    <td colspan="2"><?php echo $sixHealthCoachingStatus->getComplianceView()->getReportName() ?></td>
                    <td class="center">3/2/2020</td>
                    <td class="center"><?php echo $sixHealthCoachingStatus->getComment() ?></td>
                    <td class="center">8/21/2020</td>
                    <td class="center"><img src="<?php echo $sixHealthCoachingStatus->getLight() ?>" class="light" /></td>
                    <td class="center">Enroll</td>
                </tr>

                <tr>
                    <td class="center">2</td>
                    <td colspan="2"><?php echo $twoHealthActivitiesByCoachStatus->getComplianceView()->getReportName() ?></td>
                    <td class="center">3/2/2020</td>
                    <td class="center"><?php echo $twoHealthActivitiesByCoachStatus->getComment() ?></td>
                    <td class="center">8/21/2020</td>
                    <td class="center"><img src="<?php echo $twoHealthActivitiesByCoachStatus->getLight() ?>" class="light" /></td>
                    <td class="center">Enroll</td>
                </tr>

            <?php elseif($pointGroupStatus->getPoints() >= 4) : ?>
                <?php if($tobaccoStatus->getComment() && $tobaccoStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT) : ?>
                    <tr>
                        <td class="center"><?php echo $count++ ?></td>
                        <td colspan="2"><?php echo $smokingCessationStatus->getComplianceView()->getReportName() ?></td>
                        <td class="center">3/2/2020</td>
                        <td class="center"><?php echo $smokingCessationStatus->getComment() ?></td>
                        <td class="center">8/21/2020</td>
                        <td class="center"><img src="<?php echo $smokingCessationStatus->getLight() ?>" class="light" /></td>
                        <td class="center">Enroll</td>
                    </tr>
                <?php endif ?>
                <tr>
                    <td class="center"><?php echo $count++ ?></td>
                    <td colspan="2"><?php echo $fourOnsiteSessionsStatus->getComplianceView()->getReportName() ?></td>
                    <td class="center">3/2/2020</td>
                    <td class="center"><?php echo $fourOnsiteSessionsStatus->getComment() ?></td>
                    <td class="center">8/21/2020</td>
                    <td class="center"><img src="<?php echo $fourOnsiteSessionsStatus->getLight() ?>" class="light" /></td>
                    <td class="center">Enroll</td>
                </tr>

                <tr>
                    <td class="center"><?php echo $count++ ?></td>
                    <td colspan="2"><?php echo $twoHealthActivitiesByNurseStatus->getComplianceView()->getReportName() ?></td>
                    <td class="center">3/2/2020</td>
                    <td class="center"><?php echo $twoHealthActivitiesByNurseStatus->getComment() ?></td>
                    <td class="center">8/21/2020</td>
                    <td class="center"><img src="<?php echo $twoHealthActivitiesByNurseStatus->getLight() ?>" class="light" /></td>
                    <td class="center">Enroll</td>
                </tr>

            <?php endif ?>




            </tbody>
        </table>

        <?php
    }
}

class Wheels2019ScreeningTestPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        ?>
        <p>
            <a href="/compliance_programs?id=<?php echo $status->getComplianceProgram()->getID() ?>">Back to 2014 Wellness Rewards</a>
        </p>

        <?php parent::printReport($status); ?>

        <style type="text/css">
            .phipTable .headerRow {
                background-color:#547698;
            }
        </style>

        <br/>
        <br/>

        <table width="100%"
               border="0"
               cellpadding="3"
               cellspacing="0"
               class="tableCollapse"
               id="table3"
               style="font-size:10px">
            <tr>
                <td width="42%">Risk ratings & colors =</td>
                <td width="22%">
                    <div align="center" class="style4"><strong>OK/Good</strong></div>
                </td>
                <td width="17%">
                    <div align="center" class="style5">Borderline</div>
                </td>
                <td width="19%">
                    <div align="center"><strong><span class="style6">At-Risk</span> </strong></div>
                </td>
            </tr>

            <tr>
                <td><p><u>Key measures & ranges:</u></p></td>
                <td bgcolor="#CCFFCC"></td>
                <td bgcolor="#FFFF00"></td>
                <td bgcolor="#FF909A"></td>
            </tr>
            <tr>
                <td valign="top">
                    <ol>
                        <li><strong>Total cholesterol</strong></li>
                    </ol>
                </td>
                <td valign="top" bgcolor="#CCFFCC">
                    <div align="center">100 - < 200</div>
                </td>
                <td valign="top" bgcolor="#FFFF00">
                    <div align="center">200 - 240<br />
                        90 - &lt;100 </div>
                </td>
                <td valign="top" bgcolor="#FF909A">
                    <div align="center">> 240<br />
                        &lt; 90 </div>
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="2">
                        <li><strong>HDL cholesterol</strong> ^<br />
                            ? Men<br />
                            ? Women</li>
                    </ol>
                </td>
                <td bgcolor="#CCFFCC">
                    <div align="center">≥ 40<br />
                        ≥ 50 </div>
                </td>
                <td bgcolor="#FFFF00">
                    <div align="center">25 - &lt;40<br />
                        25 - &lt;50 </div>
                </td>
                <td bgcolor="#FF909A">
                    <div align="center">< 25<br />
                        &lt; 25 </div>
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="3">
                        <li><strong>LDL cholesterol</strong> ^</li>
                    </ol>
                </td>
                <td bgcolor="#CCFFCC">
                    <div align="center">≤ 129</div>
                </td>
                <td bgcolor="#FFFF00">
                    <div align="center">130 - &lt;159</div>
                </td>
                <td bgcolor="#FF909A">
                    <div align="center">? 159</div>
                </td>
            </tr>
            <tr>
                <td><ol start="4">
                        <li><strong>Triglycerides</strong></li>
                    </ol></td>
                <td bgcolor="#CCFFCC"><div align="center">&lt; 150</div></td>
                <td bgcolor="#FFFF00"><div align="center">150 - &lt;200</div></td>
                <td bgcolor="#FF909A"><div align="center">? 200</div></td>
            </tr>
            <tr>
                <td valign="top">
                    <ol start="5">
                        <li><strong>Glucose</strong> (Fasting)<br />
                            ? Men<br />
                            <br />
                            ? Women</li>
                    </ol>
                </td>
                <td valign="top" bgcolor="#CCFFCC">
                    <div align="center"><br />
                        70 - &lt;100<br />
                        <br />
                        <br />
                        70 - &lt;100 </div>
                </td>
                <td valign="top" bgcolor="#FFFF00">
                    <div align="center"><br />
                        100 - &lt;126<br />
                        50 - &lt;70<br />
                        <br />
                        100 - &lt;126<br />
                        40 - &lt;70 <br />
                    </div>
                </td>
                <td valign="top" bgcolor="#FF909A">
                    <div align="center"><br />
                        ? 126<br />
                        &lt; 50
                        <br />
                        <br />
                        ? 126 <br />
                        &lt; 40 </div>
                </td>
            </tr>
            <tr>
                <td><ol start="6">
                        <li><strong>Hemoglobin A1C</strong></li>
                    </ol></td>



                <td bgcolor="#CCFFCC"><div align="center">< 5.7</div></td>
                <td bgcolor="#FFFF00"><div align="center">5.7 - &lt;6.5</div></td>
                <td bgcolor="#FF909A"><div align="center">? 6.5</div></td>
            </tr>
            <tr>
                <td valign="bottom"><ol start="7">
                        <li><strong>Blood pressure</strong>*<br />
                            <br />
                            Systolic<br />
                            Diastolic </li>
                    </ol></td>
                <td bgcolor="#CCFFCC"><div align="center"><br />
                        &lt; 120<br />
                        &lt; 80 </div></td>
                <td bgcolor="#FFFF00"><div align="center"><br />
                        120 - &lt;140<br />
                        80 - &lt;90 </div></td>
                <td bgcolor="#FF909A"><div align="center"><br />
                        ? 140<br />
                        ? 90 </div></td>
            </tr>
            <tr>
                <td valign="bottom">
                    <ol start="8">
                        <li>The better of:<br />
                            <strong>Body Mass Index<br />
                            </strong>? men & women<br />
                            -- OR --<br />
                            <strong>% Body Fat:<br />
                            </strong>? Men<br />
                            ? Women
                        </li>
                    </ol>
                </td>
                <td bgcolor="#CCFFCC">
                    <div align="center">
                        <p><br />
                            18.5 - <25
                            <br />
                            <br />
                        </p>
                        <p>&nbsp;</p>
                        <p>6 - &lt;18%<br />
                            14 - &lt;25%</p>
                    </div>
                </td>
                <td bgcolor="#FFFF00">
                    <div align="center">
                        <p><br />
                            25 - <30 <br />
                            <br />
                        </p>
                        <p>&nbsp;</p>
                        <p>18 - &lt;25<br />
                            25 - &lt;32%</p>
                    </div>
                </td>
                <td bgcolor="#FF909A">
                    <div align="center">
                        <p><br />
                            ?30; <18.5<br />
                            <br />
                        </p>
                        <p>&nbsp;</p>
                        <p>            ?25; &lt;6%<br />
                            ?32; &lt;14%</p>
                    </div>
                </td>
            </tr>
            <tr>
                <td><ol start="9">
                        <li><strong>Tobacco</strong></li>
                    </ol></td>
                <td bgcolor="#CCFFCC"><div align="center">Non-user</div></td>
                <td bgcolor="#FFFF00"><div align="center">User</div></td>
                <td bgcolor="#FF909A"><div align="center">User</div></td>
            </tr>
        </table>
        <?php
    }
}
