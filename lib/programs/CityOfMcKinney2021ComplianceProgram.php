<?php

$_SESSION['insurance_plan_rename'] = 'option';

class CityOfMcKinney2021ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(true, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->addCallbackField('employeeid', function (User $user) {
            return (string) $user->getEmployeeid();
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if(sfConfig::get('app_wms2')) {
            return new CityOfMcKinney2021WMS2Printer();
        } else {
            return new CityOfMcKinney2021WMS2Printer();
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

        $preventative = new CompletePreventionPhysicalExamComplianceView($programStart, $programEnd, $codes);
        $preventative->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(250, 0, 0, 0));
        $preventative->setName('preventative_care_exam');
        $preventative->setReportName('Preventative Care Exam - must be completed between <br /> &nbsp;&nbsp; 12/1/2020 and 11/30/2021.');

        $preventative->addLink(new Link('Physician Form', ''));
        $preventativeGroup->addComplianceView($preventative);

        $this->addComplianceViewGroup($preventativeGroup);

    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        $screeningStatus = $status->getComplianceViewStatus('screening');
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $biometricGroupStatus = $status->getComplianceViewGroupStatus('biometric_measures');
        $preventativeGroupStatus = $status->getComplianceViewGroupStatus('preventative');

        if(!CityOfMcKinney2021ComplianceProgram::isOptionTwo($user)) {
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
            } else {
                $coreGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
                $coreGroupStatus->setPoints(0);
            }

            if ($screeningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $coreCompliant = true;
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
            } else {
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


class CityOfMcKinney2021WMS2Printer implements ComplianceProgramReportPrinter
{
    private function getHeader(ComplianceProgramStatus $status)
    {
        ?>

        <?php if(!CityOfMcKinney2021ComplianceProgram::isOptionTwo($status->getUser())) : ?>
        <div class="row">
            <div class="col-md-12">
                <div>
                    <p>Participants can earn up to $600 Wellness Dollars for completing the three activities/goals below by
                        <strong>December 1, 2021</strong>. Wellness Dollars earned in 2021 will be paid out in a lump-sum payment in January
                        of 2022.</p>

                    <ul>
                        <li><strong>Biometric Screening and Health Risk Assessment (HRA)</strong> – Both must be completed for any other reward payout.</li>
                        <li>
                            <strong>Biometric Goals</strong> – Must meet 3 of the 5 biometric goals. Validated by biometric results from the
                            city’s annual screening event or by submitting a physician form. Participants who cannot meet
                            at least three goals may still earn the reward if they also complete the Preventative Care Exam.
                        </li>
                        <li>
                            <strong>Preventative Care Exam</strong> – Validated via claims feed for health plan members or
                            preventative care exam form. Claims feeds from Cigna will be updated quarterly.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <?php else : ?>

        <div class="row">
            <div class="col-md-12">
                <div>
                    <p>Participants electing this option will receive a monthly, employee-only membership to the new McKinney
                        aquatics and fitness facility, Apex Centre, valued at $41.67/month, $500 annually. Participants can also
                        earn 100 Wellness Dollars for completing both the Health Risk Assessment (HRA) and an Annual Biometric
                        Screen by <strong>December 1, 2021</strong>. The $100 lump-sum payout would occur in January of 2021. Memberships will be
                        paid by the City annually or until the end of the month in which the employee terminates employment.
                        Employees will have the option to “buy up” to a family membership, having an additional $16.66/month
                        ($200/year) deducted from their paychecks. Employees terminating employment would need to purchase a
                        new Apex Centre membership on an individual basis to continue using the facility.</p>
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

        $isOptionTwo = CityOfMcKinney2021ComplianceProgram::isOptionTwo($status->getUser());

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
                <a href="/compliance/mckinney-2021-interface/my-incentives/compliance_programs?id=1471">View Previous Year's Report Card</a>
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

                    <?php if(!CityOfMcKinney2021ComplianceProgram::isOptionTwo($status->getUser())) : ?>
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