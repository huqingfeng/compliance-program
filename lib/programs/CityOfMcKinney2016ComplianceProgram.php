<?php

$_SESSION['insurance_plan_rename'] = 'option';

class CityOfMcKinney2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->addCallbackField('employeeid', function (User $user) {
            return (string) $user->getEmployeeid();
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if(sfConfig::get('app_wms2')) {
            return new CityOfMcKinney2016WMS2Printer();
        } else {
            return new CityOfMcKinney2016WMS2Printer();
        }
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $coreGroup = new ComplianceViewGroup('core', 'HRA and Screening – must complete both for any rewards');
        $coreGroup->setAttribute('rewards', '$100');
        $coreGroup->setPointsRequiredForCompliance(100);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('hra');
        $hraView->setReportName('Complete the HRA');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setName('screening');
        $screeningView->setReportName('Complete Screening');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);


        $biometricGroup = new ComplianceViewGroup('biometric_measures', 'Biometric Goals – must meet 3 of the 5 biometric goals');
//        $biometricGroup->setPointsRequiredForCompliance(0);
        $biometricGroup->setAttribute('rewards', '$250');

        $waistView = new ComplyWithWaistScreeningTestComplianceView($programStart, $programEnd);
        $waistView->setName('waist');
        $waistView->setReportName('Waist Circumference');
        $waistView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $waistView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Men ≤ 40 <br />Women ≤ 35');
        $waistView->overrideTestRowData(null, null, 40, null, 'M');
        $waistView->overrideTestRowData(null, null, 35, null, 'F');
        $waistView->emptyLinks();
        $waistView->addLink(new Link('Physician Form', ''));
        $biometricGroup->addComplianceView($waistView);

        $bpView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bpView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $bpView->overrideSystolicTestRowData(null, null, 130, null);
        $bpView->overrideDiastolicTestRowData(null, null, 85, null);
        $bpView->emptyLinks();
        $bpView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=blood_pressure'));
        $bpView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 130/85');
        $biometricGroup->addComplianceView($bpView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $hdlView->setReportName('HDL');
        $hdlView->overrideTestRowData(null, 40, null, null, 'M');
        $hdlView->overrideTestRowData(null, 50, null, null, 'F');
        $hdlView->setStatusSummary(ComplianceStatus::COMPLIANT, 'men ≥ 40 / women ≥ 50');
        $hdlView->emptyLinks();
        $hdlView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $biometricGroup->addComplianceView($hdlView);

        $triView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $triView->overrideTestRowData(null, null, 150, null);
        $triView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 150');
        $triView->emptyLinks();
        $triView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $biometricGroup->addComplianceView($triView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $gluView->setReportName('Fasting Glucose');
        $gluView->overrideTestRowData(null, null, 100, null);
        $gluView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 100');
        $gluView->emptyLinks();
        $gluView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $biometricGroup->addComplianceView($gluView);

        $this->addComplianceViewGroup($biometricGroup);


        $preventativeGroup = new ComplianceViewGroup('preventative', 'Preventative Care Exam');
        $preventativeGroup->setPointsRequiredForCompliance(250);
        $preventativeGroup->setAttribute('rewards', '$250');

        $codes = ['99385','99386','99387','99395','99396','99397'];

        $preventative = new CompletePreventionPhysicalExamComplianceView('2016-12-01', $programEnd, $codes);
        $preventative->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(250, 0, 0, 0));
        $preventative->setName('preventative_care_exam');
        $preventative->setReportName('Preventative Care Exam - must be completed between <br /> &nbsp;&nbsp; 12/1/2016 and 12/1/2017.');

        $preventative->addLink(new Link('Physician Form', ''));
        $preventativeGroup->addComplianceView($preventative);

        $this->addComplianceViewGroup($preventativeGroup);

    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $biometricGroupStatus = $status->getComplianceViewGroupStatus('biometric_measures');
        $preventativeGroupStatus = $status->getComplianceViewGroupStatus('preventative');

        if(!CityOfMcKinney2016ComplianceProgram::isOptionTwo($user)) {
            $numCompliant = 0;
            $coreCompliant = false;

            foreach($coreGroupStatus->getComplianceViewStatuses() as $viewStatus) {
                if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $numCompliant++;
                }
            }

            if($numCompliant >= 2) {
                $coreGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
                $coreGroupStatus->setPoints(100);
                $coreCompliant = true;
            } else {
                $coreGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
                $coreGroupStatus->setPoints(0);
            }

            $numCompliant = 0;
            foreach($biometricGroupStatus->getComplianceViewStatuses() as $viewStatus) {
                if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $numCompliant++;
                }
            }

            if($numCompliant >= 3 && $coreCompliant) {
                $biometricGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
                $biometricGroupStatus->setPoints(250);
            } else if ($numCompliant >= 3) {
                $biometricGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
                $biometricGroupStatus->setPoints(0);
            } else {
                $biometricGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
                $biometricGroupStatus->setPoints(0);
            }

            $numCompliant = 0;
            foreach($preventativeGroupStatus->getComplianceViewStatuses() as $viewStatus) {
                if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $numCompliant++;
                }
            }

            if($numCompliant >= 1 && $coreCompliant) {
                $preventativeGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
                $preventativeGroupStatus->setPoints(250);
                $biometricGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
                $biometricGroupStatus->setPoints(250);
            }  else if ($numCompliant >= 1) {
                $preventativeGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
                $biometricGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
                $preventativeGroupStatus->setPoints(0);
            }  else {
                $preventativeGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
                $preventativeGroupStatus->setPoints(0);
            }

        } else {
            if($coreGroupStatus->getComplianceViewStatus('screening')->getStatus() == ComplianceStatus::COMPLIANT) {
                $biometricGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
            }
        }

        parent::evaluateAndStoreOverallStatus($status);
    }

    public static function isOptionTwo(User $user)
    {
        if(isset($_REQUEST['option_two'])) {
            return true;
        }

        return false;
    }
}

class CityOfMcKinney2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        $hraStatus = $coreGroupStatus->getComplianceViewStatus('hra');

        $screeningStatus = $coreGroupStatus->getComplianceViewStatus('screening');

        $biometricGroupStatus = $status->getComplianceViewGroupStatus('biometric_measures');

        $waistStatus = $biometricGroupStatus->getComplianceViewStatus('waist');

        $preventativeGroupStatus = $status->getComplianceViewGroupStatus('preventative');

        $preventativeCareStatus = $preventativeGroupStatus->getComplianceViewStatus('preventative_care_exam');

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

            #programTable {
                border-collapse: collapse;
                margin:0 auto;
            }

            #programTable tr th, #programTable tr td{
                border:1px solid #26B000;
            }

        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned
        </script>
        <!-- Text atop report card-->
        <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The City of McKinney Comprehensive Wellness Program</p>
        <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>
            Participants can earn up to 600 Wellness Dollars for completing the three activities/goals below by
            <strong>December 1, 2017</strong>.  Wellness Dollars earned in 2017 will be paid out in a lump-sum payment
            in January of 2018 (taxable income).

        </p>

        <p><strong>1. Biometric Screening and HRA</strong> – Both must be completed for any other reward payout.</p>

        <p><strong>2. Biometric Goals</strong> – Must meet 3 of the 5 biometric goals. Validated by biometric results from the city’s
            annual screening event or by submitting a physician form. Participants who cannot meet at least three
            goals may still earn the reward if they also complete the Preventative Care Exam and have a physician
            certify that he/she has reviewed the same biometric results with them.</p>

        <p><strong>3. Preventative Care Exam</strong> – Validated via claims feed or physician form.</p>

        <table id="legend">
            <tr>
                <td id="secondColumn">
                    <table id="secondColumnTable">
                        <tr>
                            <td>
                                <img src="/images/lights/greenlight.gif" class="light" alt=""/> Completed
                            </td>
                            <td>
                                <img src="/images/lights/yellowlight.gif" class="light" alt=""/> Partially Completed
                            </td>
                            <td>
                                <img src="/images/lights/redlight.gif" class="light" alt=""/> Not Completed
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="phipTable">
            <tbody>
                <tr class="headerRow headerRow-core">
                    <th colspan="2" class="center">Requirement</th>
                    <th class="center">Results</th>
                    <th class="center">Reward</th>
                    <th class="center">Links</th>
                    <th class="center">Status</th>
                </tr>
                <tr class="view-complete_hra_screening">
                    <td colspan="2">
                        <?php echo $hraStatus->getComplianceView()->getReportName() ?><br />
                    </td>
                    <td class="center">
                        <?php echo $hraStatus->getComment(); ?>
                    </td>
                    <td class="center">
                        <?php echo $hraStatus->getComplianceView()->getAttribute('rewards'); ?>
                    </td>
                    <td class="center">
                        <?php foreach($hraStatus->getComplianceView()->getLinks() as $link) {
                            echo $link->getHTML()."\n";
                        }?>
                    </td>
                    <td class="center">
                        <img src="<?php echo $biometricGroupStatus->getLight(); ?>" class="light"/>
                    </td>
                </tr>

                <tr class="view-complete_hra_screening">
                    <td colspan="2">
                        <?php echo $screeningStatus->getComplianceView()->getReportName() ?><br />

                    </td>
                    <td class="center">
                        <?php echo $screeningStatus->getComment(); ?>
                    </td>
                    <td class="center">
                        <?php echo $screeningStatus->getComplianceView()->getAttribute('rewards'); ?>
                    </td>
                    <td class="center">
                        <?php foreach($screeningStatus->getComplianceView()->getLinks() as $link) {
                            echo $link->getHTML()."\n";
                        }?>
                    </td>
                    <td class="center">
                        <img src="<?php echo $biometricGroupStatus->getLight(); ?>" class="light"/>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        Biometric Goals <br />
                        <div style="font-weight: bold;">
                            Waist measure: 	men ≤ 40” / women ≤ 35” <br />
                            Blood pressure: 	≤ 130/85 <br />
                            HDL cholesterol: 	men ≥ 40 / women ≥ 50 <br />
                            Triglycerides: 		≤ 150 <br />
                            Fasting glucose: 	≤ 100 <br />
                        </div>
                    </td>
                    <td class="center">
                        <?php echo $waistStatus->getComment(); ?>
                    </td>
                    <td class="center">
                        <?php echo $waistStatus->getComplianceView()->getAttribute('rewards'); ?>
                    </td>
                    <td class="center">
                        <?php foreach($waistStatus->getComplianceView()->getLinks() as $link) {
                            echo $link->getHTML()."\n";
                        }?>
                    </td>
                    <td class="center">
                        <img src="<?php echo $waistStatus->getLight(); ?>" class="light"/>
                    </td>
                </tr>


                <tr>
                    <td colspan="2">
                        <?php echo $preventativeCareStatus->getComplianceView()->getReportName() ?>
                    </td>
                    <td class="center">
                        <?php echo $preventativeCareStatus->getComment(); ?>
                    </td>
                    <td class="center">
                        <?php echo $preventativeCareStatus->getComplianceView()->getAttribute('rewards'); ?>
                    </td>
                    <td class="center">
                        <?php foreach($preventativeCareStatus->getComplianceView()->getLinks() as $link) {
                            echo $link->getHTML()."\n";
                        }?>
                    </td>
                    <td class="center">
                        <img src="<?php echo $preventativeCareStatus->getLight(); ?>" class="light"/>
                    </td>
                </tr>

            </tbody>
        </table>




        <?php
    }


    public $showUserNameInLegend = true;
}

class CityOfMcKinney2016WMS2Printer implements ComplianceProgramReportPrinter
{
    private function getHeader(ComplianceProgramStatus $status)
    {
        ?>

        <?php if(!CityOfMcKinney2016ComplianceProgram::isOptionTwo($status->getUser())) : ?>
        <div class="row">
            <div class="col-md-12">
                <div>
                    <p>Participants can earn up to $600 Wellness Dollars for completing the three activities/goals below by
                        <strong>December 1, 2017</strong>. Wellness Dollars earned in 2017 will be paid out in a lump-sum payment in January
                        of 2018.</p>

                    <ul>
                        <li><strong>Biometric Screening and HRA</strong> – Both must be completed for any other reward payout.</li>
                        <li>
                            <strong>Biometric Goals</strong> – Must meet 3 of the 5 biometric goals. Validated by biometric results from the
                            city’s annual screening event or by submitting a physician form. Participants who cannot meet
                            at least three goals may still earn the reward if they also complete the Preventative Care Exam.
                        </li>
                        <li>
                            <strong>Preventative Care Exam</strong> – Validated via claims feed for health plan members or preventative
                            care exam form.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <?php else : ?>

        <div class="row">
            <div class="col-md-12">
                <div>
                    <p>Participants electing this option will receive a monthly, employee-only membership to the new
                        McKinney aquatics and fitness facility, Apex Centre, valued at $41.67/month, $500 annually.
                        Participants can also earn $100 Wellness Dollars for completing both the Health Risk Assessment (HRA)
                        and an Annual Biometric Screen. The $100 lump-sum payout would occur in January of 2018.
                        Memberships will be effective sometime in early 2017 when the Apex Centre opens (or the month
                        following initial enrollment) and will be paid by the City for 12 months or until the end of the month in
                        which the employee terminates employment. Employees would also have the option to “buy up” to a
                        family membership, by having an additional $16.66/month ($200/year) deducted from their paychecks.
                        Employees terminating employment would need to reinstate their Apex Centre memberships on an
                        individual basis to continue using the facility.</p>
                </div>
            </div>
        </div>

        <?php endif ?>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $classForStatus = function($status) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $isOptionTwo = CityOfMcKinney2016ComplianceProgram::isOptionTwo($status->getUser());

        $biometricGroupText = function() use ($isOptionTwo) {
            if($isOptionTwo) {
                return 'Biometric Goals';
            } else {
                return 'Biometric Goals – must meet 3 of the 5 biometric goals';
            }
        };

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor) {
            ob_start();

            if ($group->getComplianceViewGroup()->getName() == 'biometric_measures') : ?>
                <table class="details-table">
                    <thead>
                    <tr>
                        <th class="biometric_name">Measure</th>
                        <th class="goal  text-center">Goal</th>
                        <th class="status">Status</th>
                        <th class="result text-center">Results</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1 ?>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $pct = $viewStatus->getPoints() / $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
                        <?php $class = $classFor($pct) ?>
                        <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                            <td class="biometric_name">
                                <?php echo $i ?>.
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            </td>
                            <td class="goal text-center"><?php echo $viewStatus->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT) ?></td>
                            <td class="status <?php echo $class ?>">
                            </td>
                            <td class="result text-center">
                                <?php echo $viewStatus->getComment() ?>
                            </td>
                        </tr>
                        <?php $i++ ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php else : ?>
                <table class="details-table">
                    <thead>
                    <tr>
                        <th class="name" colspan="2">Item</th>
                        <th class="status">Status</th>
                        <th class="result text-center"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1 ?>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $pct = $viewStatus->getPoints() / $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
                        <?php $class = $classFor($pct) ?>
                        <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                            <td class="name" colspan="2">
                                <?php echo $i ?>.
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            </td>
                            <td class="status <?php echo $class ?>">
                            </td>
                            <td class="result text-center">
                            </td>
                        </tr>
                        <?php $i++ ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
            <?php
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classForStatus, $groupTable, $isOptionTwo) {
            ob_start();

            $points = $group->getPoints();
            $target = $group->getComplianceViewGroup()->getMaximumNumberOfPoints();

            $class = $classForStatus($group->getStatus());
            ?>
            <tr class="picker open">
                <td class="name" colspan="2">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="status <?php echo $class ?>">
                </td>
                <td class="result text-center">
                    <?php echo $isOptionTwo && $group->getComplianceViewGroup()->getName() == 'biometric_measures' ? '' : $group->getComplianceViewGroup()->getAttribute('rewards'); ?>
                </td>
            </tr>
            <tr class="details open">
                <td colspan="4">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        };


        ?>

        <style type="text/css">
            #activities {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
                min-width: 500px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .picker .name {
                width: 602px;
            }

            .picker .result {
                width: 160px;
            }

            .picker .status {
                width: 65px;
            }

            .details-table .name {
                width: 560px;
            }

            .details-table .result {
                width: 130px;
            }

            .details-table .status {
                width: 65px;
            }

            .biometric_name {
                width: 282px;
            }

            .goal {
                width: 282px;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }


            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            #point-discounts {
                width: 100%;
            }

            #point-discounts td {
                vertical-align: middle;
                padding: 5px;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1.4em;
            }

            #legend {
                border-collapse: collapse;
            }

            #legend td {
                border: 1px solid #333333;
            }
            
            #legend .status {
                display: table-cell;
                width: 50px;
            }

            #legend .status_text {
                padding: 5px;
            }

        </style>
        <div class="row">
            <div class="col-md-12">
                <img src="/images/chp/wellness-rewards-banner.jpg" style="width: 100%"/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h1>My Report Card</h1>
            </div>
        </div>

        <?php echo $this->getHeader($status) ?>


        <table id="legend">
            <tr>
                <td id="secondColumn">
                    <table id="secondColumnTable">
                        <tr>
                            <td class="status success"></td>
                            <td class="status_text">Completed</td>
                        </tr>
                        <tr>
                            <td class="status warning"></td>
                            <td class="status_text">Partially Completed</td>
                        </tr>
                        <tr>
                            <td class="status danger"></td>
                            <td class="status_text">Not Completed</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th colspan="2"></th>
                        <th class="points">Status</th>
                        <th class="text-center">Reward</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('HRA and Screening – must complete both for any rewards', $status->getComplianceViewGroupStatus('core')) ?>
                    <?php echo $tableRow($biometricGroupText(), $status->getComplianceViewGroupStatus('biometric_measures')) ?>

                    <?php if(!CityOfMcKinney2016ComplianceProgram::isOptionTwo($status->getUser())) : ?>
                    <?php echo $tableRow('Preventative Care Exam', $status->getComplianceViewGroupStatus('preventative')) ?>
                    <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script type="text/javascript">
            $(function() {
                $.each($('#activities .picker'), function() {
                    $(this).click(function(e) {
                        if ($(this).hasClass('closed')) {
                            $(this).removeClass('closed');
                            $(this).addClass('open');
                            $(this).nextAll('tr.details').first().removeClass('closed');
                            $(this).nextAll('tr.details').first().addClass('open');
                        } else {
                            $(this).addClass('closed');
                            $(this).removeClass('open');
                            $(this).nextAll('tr.details').first().addClass('closed');
                            $(this).nextAll('tr.details').first().removeClass('open');
                        }
                    });
                });

//                $('.details-table .name').width($('.picker td.name').first().width());
//                $('.details-table .status').width($('.picker td.status').first().width());
//                $('.details-table .result').width($('.picker td.result').first().width());

                $more = $('#more-info-toggle');
                $moreContent = $('#more-info');

                $more.click(function(e) {
                    e.preventDefault();

                    if ($more.html() == 'More...') {
                        $moreContent.css({ display: 'block' });
                        $more.html('Less...');
                    } else {
                        $moreContent.css({ display: 'none' });
                        $more.html('More...');
                    }
                });
            });
        </script>
        <?php
    }
}