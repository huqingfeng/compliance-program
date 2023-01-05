<?php

require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

use hpn\common\text\Escaper;

class Wheels2016ComplianceProgram extends ComplianceProgram
{
    const POINTS = 81;

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(true, true, true, null, null, null, null, null, true);
        $printer->setShowUserContactFields(true);
        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $oldPoints = $status->getComplianceViewGroupStatus('2015_points')->getPoints();
            $newPoints = $status->getComplianceViewGroupStatus('points')->getPoints();

            return array(
                '2015_points'                    => $oldPoints,
                '2016_points'                    => $newPoints,
                '2016_2015_change'               => bcsub($newPoints, $oldPoints, 1),
                Wheels2016ComplianceProgram::POINTS.'+ in 2014, or 3+ improvement' => $status->getComplianceProgram()->isCompliantFor1C($oldPoints, $newPoints)
            );
        });

        return $printer;
    }

    public function isCompliantFor1C($oldPoints, $newPoints)
    {
        return $newPoints >= Wheels2016ComplianceProgram::POINTS || ($oldPoints > 0 && $newPoints - $oldPoints >= 3);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Wheels2016ScreeningTestPrinter();
            $printer->setShowLight(true);
            $printer->setShowPoints(false);

            return $printer;
        } else {
            $printer = new Wheels2016ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function loadGroups()
    {
        $oldProgramRecord = ComplianceProgramRecordTable::getInstance()->find(483);

        $screeningTestAlias = 'wheels_2015';

        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('OK or Done', '/images/lights/greenlight.gif'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Borderline', '/images/lights/yellowlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done', '/images/lights/redlight.gif'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Received Yet', '/images/lights/whitelight.gif')
        ));

        $this->setComplianceStatusMapper($mapping);

        $pointsGroups = new ComplianceViewGroup('points', 'Know your numbers and learn more.');

        $oldPointsGroup = new ComplianceViewGroup('2015_points', '2015 Numbers');

        $screeningModel = new ComplianceScreeningModel();

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $screeningTestViews = $this->getScreeningTestViews($screeningModel, $screeningTestAlias, $startDate, $endDate, new \DateTime('2015-10-31'), '2015_', null, null);
        $oldScreeningTestViews = $this->getScreeningTestViews($screeningModel, $screeningTestAlias, '2015-07-01', '2016-06-30', new \DateTime('2015-10-31'), '', $oldProgramRecord, null);

        $core = new ComplianceViewGroup('core', 'Core actions required by 10/31/2016');

        $scrView = new CompleteScreeningComplianceView($startDate, $endDate);
        $scrView->setReportName('Complete Wellness Screening');
        $scrView->setAttribute('deadline', '10/31/2016');
        $scrView->setName('complete_screening');
        $scrView->emptyLinks();
        $scrView->addLink(new link('Sign-Up', '/content/advocatescreening'));
        $scrView->addLink(new link('Results', '/content/989'));
        $core->addComplianceView($scrView);

        $hpaView = new CompleteHRAComplianceView($startDate, $endDate);
        $hpaView->setReportName('Health Risk Assessment (HRA)');
        $hpaView->setAttribute('deadline', '10/31/2016');
        $hpaView->setName('complete_hra');
        $core->addComplianceView($hpaView);

        $this->addComplianceViewGroup($core);

        $tobaccoGroup = new ComplianceViewGroup('tobacco', 'Tobacco');

        $cotinineView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'cotinine', $startDate, $endDate, 'wheels_2013');
        $cotinineView->setReportName('Smoking Status');

        $tobaccoGroup->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($tobaccoGroup);

        $oldPointsGroup->setNumberOfViewsRequired(0);

        foreach($oldScreeningTestViews as $testView) {
            $oldPointsGroup->addComplianceView($testView);
            $testView->setOverrideComplianceProgramRecord($oldProgramRecord);
        }

        $this->addComplianceViewGroup($oldPointsGroup);

        $pointsGroups->setNumberOfViewsRequired(0);

        foreach($screeningTestViews as $testView) {
            $pointsGroups->addComplianceView($testView);
        }

        $useBmiOverBodyFatView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $useBmiOverBodyFatView->setReportName('Use BMI Over Body Fat');
        $useBmiOverBodyFatView->setName('bmi_over_body_fat');
        $useBmiOverBodyFatView->setAllowPointsOverride(true);
        $pointsGroups->addComplianceView($useBmiOverBodyFatView);

        $this->addComplianceViewGroup($pointsGroups);
    }

    private function getScreeningTestViews($screeningModel, $screeningTestAlias, $startDate, $endDate, \DateTime $dobDate, $namePrefix, $overrideRecord, $defaultBodyFatBmiTest)
    {
        $currentProgram = $this;

        $views = array();

        $viewsToConfigure = array();

        $totalCholesterolView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'cholesterol', $startDate, $endDate, $screeningTestAlias);
        $totalCholesterolView->setMaximumNumberOfPoints(8);
        $totalCholesterolView->setOverrideComplianceProgramRecord($overrideRecord);
        $totalCholesterolView->setAllowPointsOverride(true);

        $views[] = $totalCholesterolView;
        $viewsToConfigure[] = $totalCholesterolView;

        $hdlCholesterolView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'hdl', $startDate, $endDate, $screeningTestAlias);
        $hdlCholesterolView->setMaximumNumberOfPoints(9);
        $hdlCholesterolView->setOverrideComplianceProgramRecord($overrideRecord);
        $hdlCholesterolView->setAllowPointsOverride(true);

        $views[] = $hdlCholesterolView;
        $viewsToConfigure[] = $hdlCholesterolView;

        $ldlCholesterolView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'ldl', $startDate, $endDate, $screeningTestAlias);
        $ldlCholesterolView->setMaximumNumberOfPoints(9);
        $ldlCholesterolView->setOverrideComplianceProgramRecord($overrideRecord);
        $ldlCholesterolView->setAllowPointsOverride(true);

        $views[] = $ldlCholesterolView;
        $viewsToConfigure[] = $ldlCholesterolView;

        $trigView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'triglycerides', $startDate, $endDate, $screeningTestAlias);
        $trigView->setMaximumNumberOfPoints(10);
        $trigView->setOverrideComplianceProgramRecord($overrideRecord);
        $trigView->setAllowPointsOverride(true);

        $views[] = $trigView;
        $viewsToConfigure[] = $trigView;

        $glucoseView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'glucose', $startDate, $endDate, $screeningTestAlias);
        $glucoseView->setMaximumNumberOfPoints(20);
        $glucoseView->setOverrideComplianceProgramRecord($overrideRecord);
        $glucoseView->setAllowPointsOverride(true);

        $views[] = $glucoseView;
        $viewsToConfigure[] = $glucoseView;

        $systolicView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'systolic', $startDate, $endDate, $screeningTestAlias);
        $systolicView->setMaximumNumberOfPoints(12);
        $systolicView->setOverrideComplianceProgramRecord($overrideRecord);
        $systolicView->setAllowPointsOverride(true);
        $systolicView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');

        $views[] = $systolicView;
        $viewsToConfigure[] = $systolicView;

        $diastolicView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'diastolic', $startDate, $endDate, $screeningTestAlias);
        $diastolicView->setMaximumNumberOfPoints(12);
        $diastolicView->setOverrideComplianceProgramRecord($overrideRecord);
        $diastolicView->setAllowPointsOverride(true);
        $diastolicView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');

        $views[] = $diastolicView;
        $viewsToConfigure[] = $diastolicView;

        $bodyFatBMIView = new EvaluateBestScreeningTestResultComplianceView($screeningModel, array('bodyfat', 'bmi'), $startDate, $endDate, $screeningTestAlias);
        $bodyFatBMIView->setMaximumNumberOfPoints(20);
        $bodyFatBMIView->setOverrideComplianceProgramRecord($overrideRecord);
        $bodyFatBMIView->setAllowPointsOverride(true);
        $bodyFatBMIView->setDefaultTestName(function(User $user) use($currentProgram, $overrideRecord, $defaultBodyFatBmiTest) {
            $overrideView = $currentProgram->getComplianceView('bmi_over_body_fat');

            $newOverrideView = clone $overrideView;

            $newOverrideView->setOverrideComplianceProgramRecord($overrideRecord);

            if($newOverrideView->getMappedStatus($user)->isCompliant()) {
                return 'bmi';
            } else {
                return $defaultBodyFatBmiTest;
            }
        });

        $views[] = $bodyFatBMIView;

        foreach($bodyFatBMIView->getViews() as $bfBmiView) {
            $viewsToConfigure[] = $bfBmiView;
        }

        foreach($viewsToConfigure as $view) {
            /**
             * @var EvaluateScreeningTestResultComplianceView $view
             */
            $view->setNoGenderStatus(null, 0, 'Unknown Gender');
            $view->setNoScreeningStatus(null, 0, 'No Screening');
            $view->setNoTestResultStatus(null, 0, 'Test Not Taken');
            $view->setNoTestRowStatus(null, 0, 'Test Not Configured');
            $view->setDateOfBirthCalculationDate($dobDate);
        }

        foreach($views as $view) {
            $view->setName(sprintf('%s%s', $namePrefix, $view->getName()));
        }


        return $views;
    }
}

class Wheels2016ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $totalCholesterolStatus = $pointGroupStatus->getComplianceViewStatus('2015_comply_with_screening_test_cholesterol');
        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('2015_comply_with_screening_test_hdl');
        $ldlStatus = $pointGroupStatus->getComplianceViewStatus('2015_comply_with_screening_test_ldl');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('2015_comply_with_screening_test_triglycerides');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('2015_comply_with_screening_test_glucose');
        $systolicStatus = $pointGroupStatus->getComplianceViewStatus('2015_comply_with_screening_test_systolic');
        $diastolicStatus = $pointGroupStatus->getComplianceViewStatus('2015_comply_with_screening_test_diastolic');
        $bodyFatBMIStatus = $pointGroupStatus->getComplianceViewStatus('2015_comply_with_best_screening_test_bodyfat_bmi');

        $tobaccoGroupStatus = $status->getComplianceViewGroupStatus('tobacco');

        $nonSmokingStatus = $tobaccoGroupStatus->getComplianceViewStatus('comply_with_screening_test_cotinine');

        $newBiometricPoints = $pointGroupStatus->getPoints();
        $oldBiometricPoints = $status->getComplianceViewGroupStatus('2015_points')->getPoints();
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
                background-color:#6E8B3D;
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
                padding-left:20px
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
                background-color:#6E8B3D;
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
        </style>
        <div class="pageHeading">Rewards/To-Do Summary Page</div>
        <p>Hello <?php echo $status->getUser() ?>,</p>
        <p></p>
        <p>Welcome to your WellPursuit Incentive/To-Do summary page.</p>

        <p>Use the Action Links in the last column to get things done and learn more.</p>

        <p>Please contact the onsite lifestyle coach at <a href="awellness@wheels.com">awellness@wheels.com</a> if you have questions.</p>

        <p>Stay tuned for exciting wellness program changes in 2017!</p>

        <table class="phipTable" border="1">
            <thead id="legend">
            <tr>
                <td colspan="5">
                    <div id="legendText">Legend</div>
                    <?php
                    foreach($status->getComplianceProgram()->getComplianceStatusMapper()
                                ->getMappings() as $sstatus => $mapping) {
                        $printLegendEntry = false;

                        if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT) {
                            $printLegendEntry = true;
                        } else if($status->getComplianceProgram()->hasPartiallyCompliantStatus()) {
                            $printLegendEntry = true;
                        }

                        if($printLegendEntry) {
                            echo '<div class="legendEntry">';
                            echo '<img src="'.$mapping->getLight().'" class="light" />';
                            echo " = {$mapping->getText()}";
                            echo '</div>';
                        }
                    }
                    ?>
                </td>
            </tr>
            </thead>
            <tbody>
            <tr class="headerRow" style="height: 50px;">
                <th>1. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
                <td>Deadline</td>
                <td>Status</td>
                <td>Date Done</td>
                <td>Action Links</td>
            </tr>
            <tr>
                <td><a href="/content/1094#1aHS">A. <?php echo $completeScreeningStatus->getComplianceView()
                            ->getReportName() ?></a></td>
                <td class="center">
                    <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="center">
                    <?php echo $completeScreeningStatus->getComment(); ?>
                </td>
                <td class="links">
                    <?php
                    foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><a href="/content/1094#1bHPA">B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="center">
                    <?php echo $completeHRAStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <img src="<?php echo $completeHRAStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="center">
                    <?php echo $completeHRAStatus->getComment(); ?>
                </td>
                <td class="links">
                    <?php
                    foreach($completeHRAStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><a href="/content/1094#1bHPA">C. Get <?php echo Wheels2016ComplianceProgram::POINTS ?> biometric points from your 2016 Wellness Screening, OR; get 3 or more biometric points over last year's total.</a>
                </td>
                <td class="center">
                    <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('deadline') ?>
                </td>
                <td class="center">
                    <?php if($status->getComplianceProgram()->isCompliantFor1C($oldBiometricPoints, $newBiometricPoints)) : ?>
                        <img class="light" src="/images/lights/greenlight.gif" />
                    <?php else : ?>
                        <img class="light" src="/images/lights/redlight.gif" />
                    <?php endif ?>
                </td>
                <td class="center"></td>
                <td class="links" style="color:#005EA9">Determined by 4A or 4C (below)</td>
            </tr>
            <tr class="headerRow" style="height: 50px;">
                <th>2. <?php echo $pointGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td>My Results</td>
                <td>My Points</td>
                <td>Points Possible</td>
                <td>Links</td>
            </tr>
            <tr>
                <td style="color:#4169E1;font-size:10pt; padding-left:20px;">Are these measures in the healthy green zone and getting you the<br />
                    most points possible? If not, consider taking some e-lessons to <br />
                    learn more about it and what you can do for optimum results!</td>
                <td colspan="4" style="color:#4169E1;font-size:10pt; text-align: center">After screening results are received, click here for more details.</td>
            </tr>
            <tr>
                <td class="left">A. <?php echo $totalCholesterolStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $totalCholesterolStatus->getComment() ?></td>
                <td class="center"><?php echo $totalCholesterolStatus->getPoints(); ?></td>
                <td class="center"><?php echo $totalCholesterolStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td rowspan="4" class="links">
                    <a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Review Blood Fat Lessons</a>
                </td>
            </tr>
            <tr>
                <td class="left">B. <?php echo $hdlStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $hdlStatus->getComment() ?></td>
                <td class="center"><?php echo $hdlStatus->getPoints(); ?></td>
                <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>
            <tr>
                <td class="left">C. <?php echo $ldlStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $ldlStatus->getComment() ?></td>
                <td class="center"><?php echo $ldlStatus->getPoints(); ?></td>
                <td class="center"><?php echo $ldlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>

            </tr>
            <tr>
                <td class="left">D. <?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComment() ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>

            </tr>
            <tr>
                <td class="left">E. <?php echo $glucoseStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $glucoseStatus->getComment() ?></td>
                <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Review Blood Sugar Lessons</a>
                </td>

            </tr>

            <tr>
                <td class="left">F. <?php echo $systolicStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $systolicStatus->getComment() ?></td>
                <td class="center"><?php echo $systolicStatus->getPoints(); ?></td>
                <td class="center"><?php echo $systolicStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td rowspan="2" class="links">
                    <a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Review BP Lessons</a>
                </td>
            </tr>

            <tr>
                <td class="left">G. <?php echo $diastolicStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $diastolicStatus->getComment() ?></td>
                <td class="center"><?php echo $diastolicStatus->getPoints(); ?></td>
                <td class="center"><?php echo $diastolicStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>

            </tr>
            <tr>
                <td class="left">H. <?php echo $bodyFatBMIStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $bodyFatBMIStatus->getComment() ?></td>
                <td class="center"><?php echo $bodyFatBMIStatus->getPoints(); ?></td>
                <td class="center"><?php echo $bodyFatBMIStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <a href="/content/9420?action=lessonManager&tab_alias=body_fat">Review Body Metrics Lessons</a>
                </td>
            </tr>

            <tr class="headerRow" style="height: 50px;">
                <th>3. <?php echo $tobaccoGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
                <td>My Result</td>
                <td>Status</td>
                <td></td>
                <td>Action Links</td>
            </tr>
            <tr>
                <td><a href="/content/1094#1aHS">A. <?php echo $nonSmokingStatus->getComplianceView()
                            ->getReportName() ?></a></td>
                <td class="center">
                    <?php echo $nonSmokingStatus->getComment(); ?>
                </td>
                <td class="center">
                    <img src="<?php echo $nonSmokingStatus->getLight(); ?>" class="light"/>
                </td>
                <td class="center">

                </td>
                <td class="links">
                    <a href="/content/9420?action=lessonManager&tab_alias=tobacco">Review Tobacco Lessons</a>
                </td>
            </tr>

            <tr class="headerRow">
                <th colspan="2">4. My Point Totals &amp; Progress (from # 2 above)</th>
                <td>Totals</td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="2" class="left">
                    <div style="color:#4169E1;font-size:10pt;">My Annual Biometric Point Totals</div><br />
                    <div style="text-align:right;">A. Total Biometric Points for 2016 = </div>
                    <div style="text-align:right;">B. Total Points from 2015 = </div>
                    <div style="text-align:right;">-------------------------------------------- </div>
                    <div style="text-align:right;">C. Point Increase or (Decrease) in 2016 =</div>
                </td>
                <td class="center">
                    <div style="font-size:10pt;">&nbsp;</div><br />
                    <div style="text-align:center;"><?php echo $newBiometricPoints ?></div>
                    <div style="text-align:center;"><?php echo $oldBiometricPoints ?></div>
                    <div style="text-align:center;">&nbsp;</div>
                    <div style="text-align:center;"><?php echo $oldBiometricPoints > 0 ? bcsub($newBiometricPoints , $oldBiometricPoints, 1) : 'N/A' ?></div>
                </td>
                <td class="center">

                </td>
                <td class="center">

                </td>
            </tr>

            </tbody>
        </table>

        <?php
    }
}

class Wheels2016ScreeningTestPrinter extends ScreeningProgramReportPrinter
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
