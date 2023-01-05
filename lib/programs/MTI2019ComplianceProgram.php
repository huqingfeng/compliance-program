<?php

use hpn\steel\query\SelectQuery;

class MTI2019ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(null, null, true, false, true, false, null, null, true);
        $printer->setShowUserLocation(false);
        $printer->setShowShowRelatedUserFields(false, false, false);
        $printer->setShowUserInsuranceTypes(false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowStatus(false,false,false);
        $printer->setShowPoints(false,false,true);
        $printer->setShowComment(false,false,false);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($viewName == 'Health Risk Appraisal (HRA)') {
                        $data[sprintf('Prevention Events - %s - Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                        $data[sprintf('Prevention Events - %s - Comment', $viewName)] = $viewStatus->getComment();
                    } elseif($viewName == 'Virtual Wellness Screening') {
                        $data[sprintf('Prevention Events - %s - Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                        $data[sprintf('Prevention Events - %s - Comment', $viewName)] = $viewStatus->getComment();
                    }
                }
            }

            $data['Biometric Points'] = $status->getPoints();

            $quarterlyReportIds = array(
                1397 => 'Q1 2019',
                1398 => 'Q2 2019',
                1399 => 'Q3 2019',
                1400 => 'Q4 2019'
            );

            foreach($quarterlyReportIds as $quarterlyReportId => $quarterName) {
                $quarterlyReportStatus = $this->getQuarterlyProgramStatus($user, $quarterlyReportId);

                if ($quarterlyReportId == 1397) {
                    $data[sprintf('%s - Activites Points', $quarterName)] = $quarterlyReportStatus->getPoints();

                    $data[sprintf('%s - Total Points', $quarterName)] = $status->getPoints() + $quarterlyReportStatus->getPoints();
                } else {
                    $data[sprintf('%s - Activites Points', $quarterName)] = $quarterlyReportStatus->getPoints();
                }

            }

            return $data;
        });

        $printer->addCallbackField('Relationship Type', function (User $user) {
            return $user->getRelationshipType() == 0 ? 'E' : ($user->getRelationshipType() == 2 ? 'S' : $user->getRelationshipType(true));
        });

        $printer->addCallbackField('Spouse ID', function (User $user) {
            return $user->getMemberId();
        });

        return $printer;
    }

    protected function getQuarterlyProgramStatus($user, $recordId)
    {
        $programRecord = ComplianceProgramRecordTable::getInstance()->find($recordId);

        $program = $programRecord->getComplianceProgram();

        $program->setActiveUser($user);
        $status = $program->getStatus();

        return $status;
    }


    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if (sfConfig::get('app_wms2')) {
            return new MTI2019WMS2Printer();
        } else {
            $printer = new MTI2019Printer();
            $printer->showResult(true);
            $printer->setShowMaxPoints(true);
            return $printer;
        }
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function loadGroups()
    {
        $screeningModel = new ComplianceScreeningModel();

        $screeningTestAlias = 'beacon_compliance_2014';

        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT => new ComplianceStatusMapping('Compliant', '/resources/3813/greenblock2.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Compliant', '/images/ministryhealth/yellowblock1.jpg'),
            ComplianceStatus::NOT_COMPLIANT => new ComplianceStatusMapping('Not Compliant', '/images/ministryhealth/redblock1.jpg')
        ));

        $this->setComplianceStatusMapper($mapping);

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $preventionEventGroup = new ComplianceViewGroup('required', 'Prevention Events');
        $preventionEventGroup->setPointsRequiredForCompliance(20);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $screeningView->setReportName('Virtual Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $preventionEventGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($preventionEventGroup);

        $smokingGroup = new ComplianceViewGroup('Tobacco Status');
        $smokingGroup->setPointsRequiredForCompliance(10);

        $tobaccoView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $tobaccoView->setReportName('Tobacco Status');
        $tobaccoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 5, 0, 0));
        $tobaccoView->addLink(new Link('I completed a smoking cessation program', '/content/chp-document-uploader'));
        $tobaccoView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $tobaccoView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $this->configureViewForElearningAlternative($tobaccoView, 'smoking');

        $smokingGroup->addComplianceView($tobaccoView);

        $this->addComplianceViewGroup($smokingGroup);

        $biometricsGroup = new ComplianceViewGroup('Requirements', 'Biometric Measures');
        $biometricsGroup->setPointsRequiredForCompliance(50);

        $biometricsMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $bloodPressureView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'blood_pressure', $programStart, $programEnd, $screeningTestAlias);
        $bloodPressureView->setComplianceStatusPointMapper($biometricsMapper);
        $bloodPressureView->setRequiredTests(array('systolic', 'diastolic'));
        $this->configureViewForElearningAlternative($bloodPressureView, 'resalt_bloodpressure');
        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'triglycerides', $programStart, $programEnd, $screeningTestAlias);
        $triglView->setComplianceStatusPointMapper($biometricsMapper);
        $this->configureViewForElearningAlternative($triglView, 'resalt_triglycerides');
        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'glucose', $programStart, $programEnd, $screeningTestAlias);
        $glucoseView->setComplianceStatusPointMapper($biometricsMapper);
        $this->configureViewForElearningAlternative($glucoseView, 'resalt_bloodsugar');
        $biometricsGroup->addComplianceView($glucoseView);

        $totalHDLRatioView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'total_hdl_cholesterol_ratio', $programStart, $programEnd, $screeningTestAlias);
        $totalHDLRatioView->setComplianceStatusPointMapper($biometricsMapper);
        $totalHDLRatioView->setRequiredTests(array('totalhdlratio'));
        $this->configureViewForElearningAlternative($totalHDLRatioView, 'resalt_cholesterol');
        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bmiView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'bmi', $programStart, $programEnd, $screeningTestAlias);
        $bmiView->setComplianceStatusPointMapper($biometricsMapper);
        $this->configureViewForElearningAlternative($bmiView, 'resalt_bmi');
        $biometricsGroup->addComplianceView($bmiView);

        $this->addComplianceViewGroup($biometricsGroup);

        $preventiveGroup = new ComplianceViewGroup('Preventive Exams');
        $preventiveGroup->setPointsRequiredForCompliance(0);
    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $alias)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($alias) {
            $view = $status->getComplianceView();

            if($status->getAttribute('has_result') && !$status->isCompliant()) {
                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), $alias);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());

                $view->addLink(new Link('Alternative', "/content/9420?action=lessonManager&tab_alias={$alias}"));

                $elearningStatus = $elearningView->getStatus($user);

                if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                    $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                }
            }
        });
    }
}

class MTI2019Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setPageHeading('My Report Card (<a href="/compliance_programs?id=566">My Wellness Activities</a>)');
        $this->showTotalCompliance(true);
        $this->setPointsHeading('Points');
        $this->resultHeading = 'Result';
        $this->setShowLegend(false);
    }

    public function printCSS()
    {
        parent::printCSS();

        ?>
        <style type="text/css">
            .status-1 .your_points, .status-3 .your_points {
                background-color:red;
                color:#FFF;
            }

            .status-2 .your_points {
                background-color:yellow;
                color:#000;
            }

            .status-4 .your_points {
                background-color:green;
                color:#FFF;
            }

            #legendEntry3 {
                display:none;
            }

            td.summary {
                color:#345A92;
            }

            .phipTable .links {
                width:130px;
            }

            .pointBased .summary, .pointBased .points {
                font-size: 0.9em !important;
            }
        </style>
        <?php
    }

    protected function printTotalStatus()
    {

    }

    protected function printCustomRows(ComplianceProgramStatus $status)
    {
        ?>
        <tr class="headerRow">
            <th colspan="2">Total Biometric Points (80 possible)</th>
            <td id="spectrum_points"><?php echo $status->getPoints() ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>

        <?php
    }

    public function printReport(\ComplianceProgramStatus $status)
    {
        ?>
        <style type="text/css">
            .totalRow.group-tobacco-status { display:none; }
            .totalRow.group-requirements { display:none; }
        </style>
        <?php
        parent::printReport($status);
    }

    public function printClientNote()
    {

    }

    public function printClientMessage()
    {
        ?>
        <style type="text/css">
            .statusRow {
                background:#FFFFFF;
            }
            #legendEntry3, #legendEntry2 {
                display:none;
            }

            #sample_table {
                border-collapse: collapse;
            }

            #sample_table tr td{
                border: 1px solid #000000;
            }

            #sample_table tr th, #sample_table tr td{
                width: 100px;
            }

            .phipTable {
                border:0;
                margin-bottom:100px;
            }

            .phipTable tr {
                margin-bottom:0;
            }

            .headerRow {
                border-top:2px solid #D7D7D7;
            }

            #steps p {
                margin-bottom:0;
            }
        </style>


        <p>Welcome to MTI Wellness Website! This site was developed not only
            to track your wellness requirements, but also to be used as a great resource for health
            related topics and questions. We encourage you to explore the site while also fulfilling
            your requirements. By completing the items below in 2015 you can earn incentives.</p>

        <p>You will have the opportunity to earn incentive points in 2015/2016. You are not
            required to meet the target range for every measure. The criteria for meeting these ranges are listed below.</p>
        <p><a href="/content/989">View My 2014 Biometric Results</a></p>


        <?php
    }
}

class MTI2019WMS2Printer implements ComplianceProgramReportPrinter
{
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

        $circle = function($status, $text) use ($classForStatus) {
            $class = $status === 'beacon' ? 'beacon' : $classForStatus($status);
            ob_start();
            ?>
            <div class="circle-range">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>">
                    <div style="font-size: 1.3em; line-height: 1.0em;"><?php echo $text ?></div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };

        $circle2 = function($color) {
            ob_start();
            ?>
            <div style="width:30px; height: 30px; border-radius: 15px; background-color: <?php echo $color ?>;"></div>
            <?php

            return ob_get_clean();
        };

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor) {
            ob_start();

            if ($group->getComplianceViewGroup()->getName() == 'Requirements') : ?>
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>Test</th>
                        <th>Target</th>
                        <th class="text-center">Point Values</th>
                        <th class="text-center">Result</th>
                        <th class="text-center">Your Points</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $view = $viewStatus->getComplianceView() ?>
                        <tr>
                            <td rowspan="3">
                                <?php echo $view->getReportName() ?>
                                <br/>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                    <div><?php echo $link->getHTML() ?></div>
                                <?php endforeach ?>
                            </td>
                            <td><span class="label label-success"><?php echo $view->getStatusSummary(ComplianceStatus::COMPLIANT) ?></span></td>
                            <td class="text-center">
                                <?php echo $view->getStatusPointMapper()->getPoints(ComplianceStatus::COMPLIANT) ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) : ?>
                                    <span class="label label-success"><?php echo $viewStatus->getComment() ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) : ?>
                                    <span class="label label-success"><?php echo $viewStatus->getPoints() ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="label label-warning"><?php echo $view->getStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT) ?></span></td>
                            <td class="text-center">
                                <?php echo $view->getStatusPointMapper()->getPoints(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                    <span class="label label-success"><?php echo $viewStatus->getComment() ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                    <span class="label label-warning"><?php echo $viewStatus->getPoints() ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="label label-danger"><?php echo $view->getStatusSummary(ComplianceStatus::NOT_COMPLIANT) ?></span></td>
                            <td class="text-center">
                                <?php echo $view->getStatusPointMapper()->getPoints(ComplianceStatus::NOT_COMPLIANT) ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT) : ?>
                                    <span class="label label-success"><?php echo $viewStatus->getComment() ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT) : ?>
                                    <span class="label label-danger"><?php echo $viewStatus->getPoints() ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php else : ?>
                <table class="details-table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th class="points">Maximum</th>
                        <th class="points">Actual</th>
                        <th class="text-center">Links</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1 ?>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $pct = $viewStatus->getPoints() / $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
                        <?php $class = $classFor($pct) ?>
                        <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                            <td class="name">
                                <?php echo $i ?>.
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            </td>
                            <td class="points"><?php echo $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="points <?php echo $class ?>">
                                <?php echo $viewStatus->getPoints() ?>
                            </td>
                            <td class="links text-center">
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                    <div><?php echo $link->getHTML() ?></div>
                                <?php endforeach ?>
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

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
            ob_start();

            $points = $group->getPoints();
            $target = $group->getComplianceViewGroup()->getMaximumNumberOfPoints();

            $pct = $points / $target;

            $class = $classFor($pct);
            ?>
            <tr class="picker closed">
                <td class="name">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="points target">
                    <strong><?php echo $target ?></strong><br/>
                    points
                </td>
                <td class="points <?php echo $class ?>">
                    <strong><?php echo $points ?></strong><br/>
                    points
                </td>
                <td class="pct">
                    <div class="pgrs">
                        <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <tr class="details closed">
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

            .details-table .name {
                width: 300px;
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

        </style>
        <div class="row">
            <div class="col-md-12">
                <h1>My Report Card</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <a href="/content/mti-previous-program-years">Previous Program Years</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div>
                    <p>Screenings take place each November. To participate in the wellness program you must:</p>
                    <p><strong>1</strong>. Complete your Health Risk Assessment (HRA)</p>
                    <p><strong>2</strong>. Complete a screening</p>
                    <p><strong>3</strong>. Earn a minimum of 125 activity points each quarter</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <div class="row">
                                <div class="col-md-4">
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1 activity">
                                            <?php echo $circle(
                                                $status->getStatus(),
                                                '<span class="circle-points">'.$status->getPoints(). '</span><br/><br/>Your<br/>points'
                                            ) ?>
                                            <br/>
                                            <strong><?php echo $status->getComplianceProgram()->getMaximumNumberOfPoints() ?></strong> points possible
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Actual</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Prevention Events', $status->getComplianceViewGroupStatus('required')) ?>
                    <?php echo $tableRow('Tobacco Status', $status->getComplianceViewGroupStatus('Tobacco Status')) ?>
                    <?php echo $tableRow('Biometric Measures', $status->getComplianceViewGroupStatus('Requirements')) ?>
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

                $('.details-table .name').width($('.picker td.name').first().width());
                $('.details-table .points').width($('.picker td.points').first().width());
                $('.details-table .links').width($('.picker td.pct').first().width());

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