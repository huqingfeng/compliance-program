<?php

class SBMF2018ActivitiesFlushotComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate('2017-09-01');
        $this->setEndDate('2018-08-31');
    }

    public function getStatus(User $user)
    {
        foreach($user->getDataRecords('sbmf_flushots') as $udr) {

            $formatted_date = str_replace('-', '/', $udr->getDataFieldValue('date'));

            $date = strtotime($formatted_date);

            if($date >= $this->getStartDate() && $date <= $this->getEndDate()) {
                return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT);
            }
        }

        return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
    }

    public function getDefaultStatusSummary($constant)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'flushot';
    }

    public function getDefaultReportName()
    {
        return 'Flu Shot';
    }
}

class SBMF2018ActivitiesBloodDonationComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate('2017-09-01');
        $this->setEndDate('2018-08-31');
    }

    public function getStatus(User $user)
    {
        $number = 0;
        $comment = '';
        foreach($user->getDataRecords('sbmf_blood_donations') as $udr) {
            $date = strtotime($udr->getDataFieldValue('date'));
            if($date >= $this->getStartDate() && $date <= $this->getEndDate()) {
                $number++;
                $comment .= date('m/d/Y', $date).'<br/>';
            }
        }

        $status = $number >= 4 ?
            ComplianceStatus::COMPLIANT : (
            $number >= 2 ?
                ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT
            );

        return new ComplianceViewStatus($this, $status, null, $comment);
    }

    public function getDefaultStatusSummary($constant)
    {
        if($constant == ComplianceStatus::COMPLIANT) {
            return 'Four donations.';
        } else if($constant == ComplianceStatus::PARTIALLY_COMPLIANT) {
            return 'Two donations.';
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return 'blood_donation';
    }

    public function getDefaultReportName()
    {
        return 'Blood Donation';
    }
}



class SBMF2018ActivitiesComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new SBMF2018ActivitiesComplianceProgramReportPrinter();
        $printer->setPageHeading('My 2017 Incentive Activities');
        $printer->showTotalCompliance(true);
        $printer->showResult(true);
        $printer->setShowMaxPoints(false);
        $printer->pointValuesHeader = 'Maximum';
        $printer->showTotalCompliance(false);

        $printer->setShowNA(true);

        $printer->setShowLegend(true);
        $printer->setDoColor(false);

        return $printer;
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function  getPointsRequiredForCompliance()
    {
        return 6;
    }

    protected function constructCallback(ComplianceView $view)
    {
        return function (User $user) use ($view) {
            return $view->getMappedStatus($user)->getStatus() == ComplianceStatus::NOT_COMPLIANT;
        };
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group

        $otherMeasurementsGroup = new ComplianceViewGroup('other_measurements', 'Other Measurements');
        $otherMeasurementsGroup->setPointsRequiredForCompliance(1);

        $physicalView = new CompletePreventionPhysicalExamComplianceView($programStart, $programEnd);
        $physicalView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $physicalView->addLink(new Link('I did this', 'https://static.hpn.com/wms2/documents/clients/sbmf/2018_Physician_Biometric_Consent_Form.pdf'));
        $otherMeasurementsGroup->addComplianceView($physicalView);


        $flushotView = new SBMF2018ActivitiesFlushotComplianceView($programStart, $programEnd);
        $flushotView->setReportName('Flu Shot');
        $flushotView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $flushotView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Receive Flu Shot');
        $flushotView->emptyLinks();
        $otherMeasurementsGroup->addComplianceView($flushotView);


        $bloodView = new SBMF2018ActivitiesBloodDonationComplianceView($programStart, $programEnd);
        $bloodView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $otherMeasurementsGroup->addComplianceView($bloodView);

        $additionalLessons = new CompleteAdditionalELearningLessonsComplianceView($programStart, "2018-10-31");
        $additionalLessons->setReportName('Optional eLearning Lessons');
        $additionalLessons->setNumberRequired(4);
        $additionalLessons->setRequiredAlias(null);
        $additionalLessons->addIgnorableGroup('alt_bloodpressure');
        $additionalLessons->addIgnorableGroup('alt_bloodsugar');
        $additionalLessons->addIgnorableGroup('alt_cholesterol');
        $additionalLessons->addIgnorableGroup('alt_bmi');
        $additionalLessons->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $additionalLessons->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete 4 lessons for 1 point');
        $additionalLessons->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);


        $otherMeasurementsGroup->addComplianceView($additionalLessons);

        $this->addComplianceViewGroup($otherMeasurementsGroup);
    }

    public function getLocalActions()
    {
        return array(
            'previous_reports' => array($this, 'executePreviousReports'),
        );
    }

    public function executePreviousReports()
    {
        ?>
        <p><a href="/compliance_programs?id=3">Click here</a> for the 2010 scorecard.</p>
        <p><a href="/compliance_programs?id=122">Click here</a> for the 2011 scorecard.</p>
        <p><a href="/compliance_programs?id=202">Click here</a> for the 2012 scorecard.</p>
        <p><a href="/compliance_programs?id=270">Click here</a> for the 2013 scorecard.</p>
        <p><a href="/compliance_programs?id=359">Click here</a> for the 2014 scorecard.</p>
        <?php
    }
}

class SBMF2018ActivitiesComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>

        <script type="text/javascript">
            $(function() {
                $('.phipTable .headerRow').not('.totalRow').addClass("cursor");

                $('#steps').hide();
                $('#sub_steps').hide();
                $('#show_more_sub_steps').hide();


                $('#show_more_steps').toggle(function(){
                    $('#steps').show();
                    $('#show_more_sub_steps').show();
                    $('#show_more_steps a').html('Less...');
                }, function(){
                    $('#steps').hide();
                    $('#sub_steps').hide();
                    $('#show_more_sub_steps').hide();
                    $('#show_more_steps a').html('More...');
                });

                $('#show_more_sub_steps').toggle(function(){
                    $('#sub_steps').show();
                    $('#show_more_sub_steps a').html('Less...');
                }, function(){
                    $('#sub_steps').hide();
                    $('#show_more_sub_steps a').html('More...');
                });

                $('.actual_3, .progress_3').attr('rowspan',2);

                $('.actual_3b, .progress_3b').remove();

                $('.wms2_section .wms2_row').click(function(){
                    $('.wms2_row .wms2_title').toggleClass('closed');
                    $('.phipTable').toggle();
                });

            });

        </script>

        <ol><li><strong>Annual Physical Exam: </strong>
                When your physician completed the physical exam verification/biometric screening form and
                faxes the form to Circle Wellness, your scorecard will be credited with two (2) points.
                Physical exams need to be completed between September 1, 2017 and December 31, 2018.
            </li><li><strong>Annual Flu Shot: </strong>
                Employees and spouses who received a flu vaccination between September 1, 2017 and March 31, 2018 will
                receive two points on their wellness scorecard. It is the employee’s responsibility to ensure
                documentation of the flu vaccine is provided to the Human Resources Department if needed.
            </li><li><strong>Blood Donation: </strong>
                Employees and spouses who donate blood four times a year will receive two points on their
                wellness scorecard. Those who donate twice a year will earn one point. A double red cell
                donation will count as two donations. Blood donations must be completed between
                September 1, 2017 and December 31, 2018.
            </li></ol>




        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        global $_cr, $_user;

        $this->status = $status;
        $this->printCSS();
        ?>
        <div id="clientMessage"><?php $this->printClientMessage() ?></div>
<!--        <div class="pageHeading">--><?php //echo $this->pageHeading ?><!--</div>-->
<!--        <div id="clientNotice">--><?php //$this->printClientNotice() ?><!--</div>-->

        <div class="wms2_legend">
            <div class="wms2_row">
                <div class="color green"><div class="circle"></div></div>
                <div class="status">Done</div>
            </div>

            <div class="wms2_row">
                <div class="color yellow"><div class="circle"></div></div>
                <div class="status">Partially Done</div>
            </div>

            <div class="wms2_row">
                <div class="color red"><div class="circle"></div></div>
                <div class="status">Not Done</div>
            </div>
        </div>

        <table class="phipTable">
            <tbody>
            <?php if($this->showTotalCompliance) $this->printTotalStatus() ?>

            <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
                <?php $this->printGroupStatus($groupStatus) ?>

            <?php endforeach ?>

            </tbody>
        </table>
        <div id="clientNote"><?php $this->printClientNote() ?></div>
        <?php
        $this->status = null;
    }

    public function printGroupStatus(ComplianceViewGroupStatus $groupStatus)
    {
        $group = $groupStatus->getComplianceViewGroup();
        ?>

        <div class="wms2_header">
            <div class="wms2_row">
                <div class="wms2_title"></div>
                <div class="wms2_target">Target</div>
                <div class="wms2_actual">Actual</div>
                <div class="wms2_progress">Progress</div>
            </div>
        </div>
        <div class="wms2_section">
            <div class="wms2_row">
                <div class="wms2_title open"><?php echo $group->getReportName(); ?><span class="triangle"></span></div>
                <div class="wms2_target"><strong><?php echo $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints() ?></strong> <br/> points</div>
                <div class="wms2_actual <?php echo $this->determineStatusClass($groupStatus->getPoints() , $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints())?>"><strong><?php echo $groupStatus->getPoints() ?></strong> <br/> points</div>
                <div class="wms2_progress">
                    <div class="progress_bar">
                        <div class="status_bar <?php echo $this->determineStatusClass($groupStatus->getPoints() , $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints())?>" style="width: <?php echo $this->calcProgressBar($groupStatus->getPoints() , $groupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints())?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <tr class="headerRow <?php echo sprintf('group-%s', Doctrine_Inflector::urlize($group->getName())) ?>">

            <th>Item</th>
            <td><?php echo $this->targetHeader ?></td>
            <td class="w75"><?php echo $this->pointValuesHeader ?></td>
            <td class="w75"><?php echo $this->pointsHeading ?></td>
            <td>Progress</td>
            <td><?php echo $this->resultHeading ?></td>
            <td>Actions</td>

        </tr>
        <?php
        $number = 1;
        $i = 0;
        foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) :
            if($this->showNA || $viewStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {

                if($group->pointBased()) {
                    $this->printPointBasedViewStatus($viewStatus, $number);
                } else {
                    $this->printViewStatus($viewStatus, $number);
                }

                if($this->printViewNumber($viewStatus->getComplianceView())) {
                    $number++;
                }

                $i++;
            }
        endforeach;

    }

    private function determineStatusClass($value, $max) {
        if ($value == 0) {
            return "status_red";
        } else if ($value < $max) {
            return "status_yellow";
        } else {
            return "status_green";
        }
    }

    private function calcProgressBar($value, $max) {
        if ($value > 0) {
            return number_format(($value/$max)*100, 2);
        }
    }

    private function printPointBasedViewStatus(ComplianceViewStatus $viewStatus, $i)
    {
        $j = 0;
        $row = (string)$i;
        $printedPoints = false;
        $printedResult = false;

        $view = $viewStatus->getComplianceView();
        $mappings = $this->getStatusMappings($view);
        $group = $view->getComplianceViewGroup();

        if(!$this->printViewNumber($view)) {
            $i -= 1;
        }

        foreach($mappings as $sstatus => $mapping) :

            $domClasses = array(
                'statusRow', (!$j ? 'newViewRow' : ''),
                'pointBased', 'statusRow'.$sstatus,
                ($i % 2 ? 'mainRow' : 'alternateRow'),
                sprintf('status-%s', $viewStatus->getStatus()),
                sprintf('view-%s', $view->getName())
            );

            $correctStatus = $this->pointBasedViewStatusMatchesMapping($viewStatus, $sstatus);
            $onLastRow = ($j == count($mappings) - 1);
            ?>
            <tr class="<?php echo implode(' ', $domClasses) ?>">
                <?php if($j < 1) : ?>
                    <td class="resource" rowspan="<?php echo count($mappings) ?>">
                        <?php echo sprintf('%s%s', $this->printViewNumber($view) ? "$i. " : '', $view->getReportName(true)) ?>
                    </td>
                <?php endif ?>
                <td class="summary">
                    <?php echo $view->getStatusSummary($sstatus); ?>
                </td>
                <td class="points w75 status_blue">
                    <?php echo $view->getStatusPointMapper() ? $view->getStatusPointMapper()->getPoints($sstatus) : '' ?>
                </td>

                <?php
                    echo '<td class="w75 points your_points '.$this->determineStatusClass($viewStatus->getPoints(), $view->getStatusPointMapper()->getPoints($sstatus)).' actual_'.$row.'">', $viewStatus->getPoints(), '</td>';
                    echo '<td class="wms2_progress progress_'.$row.'"><div class="progress_bar">
                    <div class="status_bar '.$this->determineStatusClass($viewStatus->getPoints(), $view->getStatusPointMapper()->getPoints($sstatus)).'" style="width: '.
                        $this->calcProgressBar($viewStatus->getPoints(),$view->getStatusPointMapper()->getPoints($sstatus)).'%"></div>
                </div></td>';
                    $printedPoints = true;

                    echo '<td class="results">', $viewStatus->getComment(), '</td>';
                ?>

                <?php if(!$j) : ?>
                    <?php $this->printViewLinks($view) ?>
                <?php else : ?>
                    <td class="empty"></td>
                <?php endif ?>
                <?php $row.="b" ?>
            </tr>
            <?php
            $j++;
        endforeach;
    }

    private function printViewLinks(ComplianceView $view)
    {
        if($view->getComplianceViewGroup()->hasLinks()) {
            echo '<td class="links">'.implode('<br/>', $view->getLinks()).'</td>';
        } else {
            echo '<td class="empty"></td>';
        }
    }

    private function printViewStatus(ComplianceViewStatus $viewStatus, $i)
    {
        $view = $viewStatus->getComplianceView();
        $group = $view->getComplianceViewGroup();
        ?>
        <tr class="<?php echo sprintf('view-%s', $view->getName()) ?>">
            <td class="resource">
                <?php echo sprintf('%s%s', $this->printViewNumber($view) ? "$i. " : '', $view->getReportName()) ?>
            </td>
            <td class="requirements" colspan="<?php echo $this->showMaxPoints ? 3 : 2 ?>"><?php echo $view->getStatusSummary(ComplianceStatus::COMPLIANT); ?></td>
            <td class="status"><img src="<?php echo $viewStatus->getLight(); ?>" alt="" class="light"/></td>
            <?php if($this->showResult) echo '<td class="empty"></td>' ?>
            <?php $this->printViewLinks($view) ?>
        </tr>
        <?php
    }

    protected function printTotalStatus()
    {
        $nameColumns = 3;
        if($this->status->getPoints() === null) $nameColumns++;
        if($this->showResult) $nameColumns++;
        ?>
        <tr class="headerRow">
            <th colspan="<?php echo $nameColumns?>">Program Status</th>
            <td>Your Status</td>
            <td><?php echo $this->pointsHeading ?></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="<?php echo $nameColumns ?>">
                1. Your Status (On <?php echo date('m/d/Y') ?>)
            </td>
            <td class="status">
                <img
                        src="<?php echo $this->status->getLight() ?>"
                        alt=""
                        class="light"
                />
            </td>
            <?php if($this->status->getPoints() !== null) : ?>
                <td class="points"><?php echo $this->status->getPoints() ?></td>
            <?php endif ?>
        </tr>
        <?php
    }

    public function printClientNotice()
    {
    }

    public function printClientNote()
    {
        ?>
        <p>
            A maximum of 7 points are attainable.


        </p>
        <p>
            Note: A Physical exam/biometric screening is required for the wellness incentive offered in 2018. Points will be rewarded for completing the annual physical exam and successfully meeting the biometric benchmarks.
        </p>
        <p>
            If you cannot feasibly reach one or more of the
            Health Profile Measurements in the scorecard due to a medical condition,
            despite medical treatment, you can have your physician complete
            an <a href="https://static.hpn.com/wms2/documents/clients/sbmf/SBMF%20Verifcation%20%20Exception%20Form%20-%202018.pdf">exception form</a>
            to meet the Health Profile Measurement and obtain 2 points.
        </p>
        <?php
    }

    public function printCSS()
    {
        parent::printCSS();
        // They want only alternating views to switch colors.
        ?>
        <style type="text/css">

            #content {
                width: 850px;
            }

            #clientMessage, #programMessage {
                width: calc(100% - 220px);
                display: inline-block;
            }

            #clientNote {
                margin-top: 40px;
            }

            ol li {
                margin-bottom: 8px;
            }

            .wms2_legend {
                width: 200px;
                margin: auto;
                margin-bottom: 20px;
                box-sizing: border-box;
                display: inline-block;
                padding-left: 20px;
                position: relative;
            }

            .wms2_legend .wms2_row {
                width: 100%;
                margin-bottom: 10px;
            }

            .wms2_legend .wms2_row .color, .wms2_legend .wms2_row .status {
                display: inline-block;
            }

            .wms2_legend .wms2_row .status {
                margin-left: 10px;
                position: relative;
                top:-9px;
            }

            .wms2_legend .circle {
                width: 30px;
                height: 30px;
                border-radius: 50%;
            }

            .wms2_legend .color.green .circle {
                background: #74c36e;
            }

            .wms2_legend .color.yellow .circle {
                background: #fdb83b;
            }

            .wms2_legend .color.red .circle {
                background: #f15752;
            }

            .wms2_legend .color.white .circle {
                background: white;
                border: 1px solid black;
                box-sizing: border-box;
            }

            .wms2_header {
                display: table;
                width: 100%;
                border-collapse: collapse;
                overflow: hidden;
                margin-bottom:5px;
                font-size: 1em;
                font-weight: 600;
                color: #666;
            }

            .wms2_header .wms2_title {
                font-size: 1.2em;
                display: table-cell;
                width: 50%;
            }

            .wms2_header .wms2_target {
                display: table-cell;
                width: 75px;
                vertical-align: middle;
                text-align: center;
                padding-left: 5px;
            }

            .wms2_header .wms2_actual {
                display: table-cell;
                width: 75px;
                vertical-align: middle;
                text-align: center;
                padding-left: 5px;
            }

            .wms2_header .wms2_progress {
                display: table-cell;
                padding-left: 5px;
                text-align: center;
            }

            .wms2_header .wms2_row, .wms2_section .wms2_row {
                display: table-row;
            }

            .wms2_section .wms2_row {
                cursor: pointer;
            }

            .wms2_section .wms2_row:hover > * {
                box-shadow: 0px 0px 0px 2px #48c8e8 inset;
            }

            .wms2_section {
                height: 75px;
                display: table;
                width: 100%;
                border-collapse: collapse;
                overflow: hidden;
            }

            .wms2_section .wms2_title {
                font-size: 1.2em;
                display: table-cell;
                vertical-align: middle;
                width: 50%;
                background: #eee;
                padding-left: 10px;
                color: #666;
                position: relative;
            }

            .wms2_section .wms2_title.open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            .wms2_section .wms2_title.closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .wms2_section .wms2_title .triangle {
                position: absolute;
                right: 15px;
                top: 27px;
            }

            .wms2_section .wms2_target {
                display: table-cell;
                width: 75px;
                height: 75px;
                vertical-align: middle;
                border-left: 5px solid white;
                border-collapse: collapse;
                text-align: center;
                background: #48c7e8;
                color: white;
                font-size: 1em;
                line-height: 1.8em;
            }

            .wms2_section .wms2_actual {
                display: table-cell;
                width: 75px;
                height: 75px;
                vertical-align: middle;
                border-left: 5px solid white;
                border-collapse: collapse;
                text-align: center;
                background: #f15752;
                color: white;
                font-size: 1em;
                line-height: 1.8em;
            }

            .wms2_section .wms2_progress {
                display: table-cell;
                border-left: 5px solid white;
                background: #eee;
                padding: 8px;
                box-sizing: border-box;
            }

            .headerRow, .phipTable .headerRow th {
                font-weight: 600;
                color: #666;
            }

            .w75 {
                width: 75px;
            }

            .phipTable {
                width: calc(100% - 20px);
                margin-left: 20px;
            }

            .status_blue {
                background: #48c7e8 !important;
                color: white;
            }

            .status_green {
                background: #74c36e !important;
                color: white;
            }

            .status_yellow {
                background: #fdb83b !important;
                color: white;
            }

            .status_red {
                background: #f15752 !important;
                color: white;
            }

            .phipTable td.resource {
                width: 250px;
                color: #666;
            }

            .phipTable td.wms2_progress {
                width: 150px;
            }

            .phipTable, .phipTable tr.newViewRow {
                border: none;
                font-size: 1em;
            }

            .progress_bar {
                background: #ccc;
                width: 100%;
                height: 100%;
                min-height: 10px;
            }

            .progress_bar .status_bar {
                width: 2%;
                height: 100%;
                min-height: 10px;
                background: #fd3b3b;
            }

            d.requirements {
                text-align: center;
            }
            .phipTable .summary {
                width:220px;
                font-size: 1em;
                padding: 6px 8px !important;
                text-align: left;
            }

            .phipTable .points {
                font-size: 1em;
                border: 4px solid white;
            }

            .phipTable .cursor:hover {
                cursor: hand;
                cursor: pointer;
                opacity: .9;
            }

            .phipTable .indicator {
                width: 40px;
            }

            .phipTable .indicator img{
                width: 20px;
            }

            .phipTable .headerRow {
                height: 60px;
                background: transparent;
                font-weight:bold;
                color: #666;
            }


            .view-elearning_bmi .resource, .view-elearning_tc .resource, .view-elearning_gc .resource, .view-elearning_bp .resource {
                padding-left:4em;
            }

            .phipTable td { padding: 3px !important;}
            .pageHeading { display: none; }

        </style>
        <?php
    }

    protected function printViewNumber(ComplianceView $view)
    {
        return !$view->getAttribute('skip_view_number');
    }

    protected $resultHeading = 'Your Result';
    private $showNA = true;
    private $doColor = true;
    private $showMaxPoints = false;
    private $showLegend = true;
    private $targetHeader = 'Target';
    private $showTotalCompliance = false;
    private $showResult = true;
    protected $status = null;
    private $pageHeading = 'My Incentive Report Card';
    private $pointsHeading = 'Actual';
    public $requirementsHeader = 'Requirements';
    public $pointValuesHeader = 'Point Values';
}