<?php

use hpn\steel\query\SelectQuery;

class BeaconEvaluateScreeningTestResultComplianceView extends EvaluateScreeningTestResultComplianceView
{
    /**
     * Overrides logic to fetch screening data. Beacon has a custom activity
     * setup with the indicated ids below.
     */
    protected function getScreeningRow(User $user, \DateTime $start, \DateTime $end)
    {
        if($user->insurancetype) {
            return parent::getScreeningRow($user, $start, $end);
        }

        $rowKey = $this->getRowKey($user, $start, $end);

        if($this->rowKey == $rowKey) {
            return $this->row;
        } else {
            $biometricActivity = new ActivityTrackerActivity(339);
            $records = $biometricActivity->getRecords($user);

            $firstRecord = reset($records);

            if($firstRecord) {
                $screening = array(
                    'labid' => 27,
                    'date' => date('Y-m-d', strtotime($firstRecord->getField('date')))
                );

                $answerMap = array(
                    'systolic'      => 124,
                    'diastolic'     => 125,
                    'totalhdlratio' => 126,
                    'triglycerides' => 127,
                    'glucose'       => 128,
                    'cholesterol'   => 129,
                    'bmi'           => 130
                );

                $answers = $firstRecord->getQuestionAnswers();

                foreach($answerMap as $screeningTest => $answerId) {
                    if(isset($answers[$answerId])) {
                        $answer = $answers[$answerId]->getAnswer();

                        $screening[$screeningTest] = $answer;
                    }
                }
            } else {
                $screening = false;
            }

            $this->row = $screening ? $screening : null;
            $this->rowKey = $rowKey;

            return $this->row;
        }
    }

    private function getRowKey(User $user, \DateTime $start, \DateTime $end)
    {
        return "{$user->id},{$start->format('U')},{$end->format('U')}";
    }

    private $row = null;
    private $rowKey = null;
}

class Beacon2016CompleteHRAComplianceView extends CompleteHRAComplianceView
{
    public function getStatus(User $user)
    {
        if($user->insurancetype) {
            $this->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        } else {
            $this->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        }

        return parent::getStatus($user);
    }
}

class Beacon2016CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getStatus(User $user)
    {
        if($user->insurancetype) {
            $this->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));

            return parent::getStatus($user);
        } else {
            $this->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));

            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }
}

class Beacon2016DataComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(null, null, null, false, true, false, null, null, true);
        $printer->setShowUserLocation(false);
        $printer->setShowShowRelatedUserFields(false, false, false);
        $printer->setShowUserInsuranceTypes(false, true);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowStatus(false,false,false);
        $printer->setShowPoints(false,false,false);
        $printer->setShowComment(false,false,false);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
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

            $data['Compliance Program - Points'] = $status->getPoints();

            return $data;
        });

        $printer->addCallbackField('Relationship Type', function (User $user) {
            return $user->getRelationshipType() == 0 ? 'E' : ($user->getRelationshipType() == 2 ? 'S' : $user->getRelationshipType(true));
        });

        $printer->addCallbackField('Spouse ID', function (User $user) {
            return $user->getMemberId();
        });

        $printer->addCallbackField('Employee Id', function (User $user) {
            return (string) $user->employeeid;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if (sfConfig::get('app_wms2')) {
            return new Beacon2016WMS2Printer();
        } else {
            $printer = new Beacon2016Printer();
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

        $screeningStartDate = '2016-06-01';

        $preventionEventGroup = new ComplianceViewGroup('required', 'Prevention Events');
        $preventionEventGroup->setPointsRequiredForCompliance(50);

        $hraView = new Beacon2016CompleteHRAComplianceView('2016-05-01', '2016-09-30');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', sfConfig::get('app_wms2') ? '/compliance/beacon2016/my-health' : '/content/989'));
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($screeningStartDate, '2016-09-30');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $screeningView->setReportName('Virtual Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $screeningDate = SelectQuery::create()
                ->select('date')
                ->from('screening')
                ->where('user_id = ?', array($user->id))
                ->andWhere('date BETWEEN ? AND ?', array($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d')))
                ->hydrateSingleScalar()
                ->orderBy('date DESC')
                ->execute();

            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setComment(date('m/d/Y', strtotime($screeningDate)));
            }
        });
        $screeningView->emptyLinks();
        $preventionEventGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($preventionEventGroup);

        $smokingGroup = new ComplianceViewGroup('Tobacco Status');
        $smokingGroup->setPointsRequiredForCompliance(50);

        $tobaccoView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, '2016-09-30');
        $tobaccoView->setReportName('Tobacco Status');
        $tobaccoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $tobaccoView->addLink(new Link('I completed a smoking cessation program', '/content/chp-document-uploader'));
        $tobaccoView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $tobaccoView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $this->configureViewForElearningAlternative($tobaccoView, 'smoking');

        $smokingGroup->addComplianceView($tobaccoView);

        $this->addComplianceViewGroup($smokingGroup);

        $biometricsGroup = new ComplianceViewGroup('Requirements', 'Biometric Measures');
        $biometricsGroup->setPointsRequiredForCompliance(0);

        $biometricsMapper = new ComplianceStatusPointMapper(100, 50, 0, 0);

        $bloodPressureView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'blood_pressure', $screeningStartDate, $programEnd, $screeningTestAlias);
        $bloodPressureView->setComplianceStatusPointMapper($biometricsMapper);
        $bloodPressureView->setRequiredTests(array('systolic', 'diastolic'));
        $bloodPressureView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
        $this->configureViewForElearningAlternative($bloodPressureView, 'resalt_bloodpressure');
        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'triglycerides', $screeningStartDate, $programEnd, $screeningTestAlias);
        $triglView->setComplianceStatusPointMapper($biometricsMapper);
        $triglView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
        $this->configureViewForElearningAlternative($triglView, 'resalt_triglycerides');
        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'glucose', $screeningStartDate, $programEnd, $screeningTestAlias);
        $glucoseView->setComplianceStatusPointMapper($biometricsMapper);
        $glucoseView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
        $this->configureViewForElearningAlternative($glucoseView, 'resalt_bloodsugar');
        $biometricsGroup->addComplianceView($glucoseView);

        $totalHDLRatioView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'total_hdl_cholesterol_ratio', $screeningStartDate, $programEnd, $screeningTestAlias);
        $totalHDLRatioView->setComplianceStatusPointMapper($biometricsMapper);
        $totalHDLRatioView->setRequiredTests(array('totalhdlratio'));
        $totalHDLRatioView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
        $this->configureViewForElearningAlternative($totalHDLRatioView, 'resalt_cholesterol');
        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bmiView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'bmi', $screeningStartDate, $programEnd, $screeningTestAlias);
        $bmiView->setComplianceStatusPointMapper($biometricsMapper);
        $bmiView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
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
                $elearningView = new CompleteELearningGroupSet('2016-06-01', '2016-09-30', $alias);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());

                $view->addLink(new Link('Alternative', "/content/9420?action=lessonManager&tab_alias={$alias}"));

                $elearningStatus = $elearningView->getStatus($user);

                if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $originalPoints = $status->getPoints();

                    $status->setStatus(ComplianceStatus::COMPLIANT);
                    $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                    $status->setAttribute('extra_points', $view->getMaximumNumberOfPoints() - $originalPoints);
                }
            }
        });
    }


    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $extraPoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                    $extraPoints += $viewExtraPoints;
                }
            }
        }

        $status->setAttribute('total_points_ignoring_alternatives', $status->getPoints() - $extraPoints);
    }
}

class Beacon2016Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setPageHeading('My LiGHT Spectrum (<a href="/compliance_programs?id=527">View LiGHT Activities</a>)');
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
            <th colspan="2">Total Biometric Points (650 possible)</th>
            <td id="spectrum_points"><?php echo $status->getPoints() ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">Total LiGHT Activities Points (350 possible)</th>
            <td id="activities_points"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th colspan="2">My Total LiGHT Spectrum Points (1000 possible)</th>
            <td id="combined_points"></td>
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

            <?php if($status->getUser()->insurancetype) : ?>
                .screening-link { display:none; }
                .view-hra .links a { display:none; }
            <?php endif ?>
        </style>
        <script type="text/javascript">
            $(function() {
                $.get('/compliance_programs?id=527', function(fullPage) {
                    var $page = $(fullPage);

                    var activityPoints = parseInt($page.find('#activity_points').html(), 10);

                    $('#activities_points').html(activityPoints);

                    $('#combined_points').html(
                        '' + (activityPoints + <?php echo $status->getPoints() ?>)
                    );
                });

                $('.show_more').toggle(function(){
                    $('.hide').show();
                    $('.show_more a').html('Less...');
                }, function(){
                    $('.hide').hide();
                    $('.show_more a').html('More...');
                });


            });
        </script>
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

        <img src=" /resources/6479/icon_2016.png " style="width:200px; position:relative; top:-160px; left:580px;"/>
        <?php if($this->status->getUser()->insurancetype) : ?>
            <p>Welcome to your Wellness Website! This site was developed not only to track your wellness
                requirements to be covered under the medical plan, but also used to track wellness Activity
                points through the LiGHT Program.  We encourage you to explore the site as it is a great resource
                for health related topics and questions. </p>

            <span class="show_more"><a href="#">More...</a></span>

            <p class="hide">If you participated in the previous Virtual Wellness Screening Process,
                your results will be loaded into your LiGHT Spectrum, providing you wellness points.</p>

            <p class="hide">You will have the opportunity to earn incentive points through September 30, 2016. You are not required to
                meet the target range for every measure. The criteria for meeting these ranges are listed below in your LiGHT Spectrum.
                If your screening measure falls into a medium/at risk range (color coded below yellow or red), you have the option to
                complete (3) online eLearning Alternative’s that will be indicated in "Alternative" links from your LiGHT Spectrum below.</p>

            <p class="hide">Points earned through the Virtual Wellness Screening process combined with your LiGHT Activity points earned
                through September 30, 2016 will be applied to determine your level of discount on your medical insurance premiums in 2017.
                <strong>If both associate and spouse are covered on the medical plan an average of the two participant’s overall points
                    (both Spectrum and Activity) will be used to determine the percent discount.</strong></p>

            <div class="well" id="steps" style="color:red">
                <p>The next Virtual Wellness Process will take place June 1 - August 31, 2016.The Process consists of:</p>
                <p><strong>Step 1</strong>- Complete your Health Risk Assessment (HRA).</p>
                <p><strong>Step 2</strong>- Schedule your Virtual Wellness Screening appointment at designated lab
                    locations. Information regarding scheduling will be sent at a later date.</p>
            </div>
        <?php else : ?>
            <style type="text/css">
                .view-screening { display:none; }
            </style>

            <p>Welcome to Beacon Health System Wellness Website! This site was
                developed not only to track your wellness requirements, but also
                to be used as a great resource for health related topics and questions.
                We encourage you to explore the site while also fulfilling your
                requirements. By completing the items below in 2014 you can
                earn incentives!</p>

            <span class="show_more"><a href="#">More...</a></span>

            <p class="hide">You will have the opportunity to earn incentive points in 2015/2016.
                You are not required to meet the target range for every measure.
                The criteria for meeting these ranges are listed below in your
                LiGHT Spectrum. </p>
        <?php endif ?>

        <?php if($this->status->getUser()->insurancetype) : ?>
            <p>The following legend gives you an idea of where your health status is:</p>
            <div style="padding:10px 0 20px 60px;">
                <table id="sample_table">
                    <tr><th></th><th style="font-size:12pt;color: black;text-decoration: underline">Total Score</th></tr>
                    <tr><td style="width:120px;background-color:red;padding:5px;color: black;"><strong>No Discount</strong></td><td>50-399 points</td></tr>
                    <tr><td style="width:120px;background-color:yellow;padding:5px;color: black;"><strong>5% Discount</strong></td><td>400-799 points</td></tr>
                    <tr><td style="width:120px;background-color:green;padding:5px;color: black;"><strong>10% Discount</strong></td><td>800-1000 points</td></tr>
                </table>
            </div>
        <?php else : ?>
            <p>The following legend gives you an idea of where your health status is:</p>
            <div style="padding:10px 0 20px 60px;">
                <table id="sample_table">
                    <tr><th></th><th style="font-size:12pt;text-decoration: underline">Total Score</th></tr>
                    <tr><td style="width:100px;background-color:red;padding:5px;"></td><td>50-399 points</td></tr>
                    <tr><td style="width:100px;background-color:yellow;padding:5px;"></td><td>400-799 points</td></tr>
                    <tr><td style="width:100px;background-color:green;padding:5px;"></td><td>800-1000 points</td></tr>
                </table>
            </div>
        <?php endif ?>

        <div style="margin-top: 10px; margin-bottom: 10px;">
            <a href="/compliance_programs?id=335">2015 program LiGHT Spectrum</a> <br />
            <a href="/compliance_programs?id=365">2015 program LiGHT Activities</a>
        </div>

        <?php
    }
}

class Beacon2016WMS2Printer implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $activitiesProgramRecord = ComplianceProgramRecordTable::getInstance()->find(527);
        $activitiesProgram = $activitiesProgramRecord->getComplianceProgram();
        $activitiesProgram->setActiveUser($status->getUser());
        $activitiesStatus = $activitiesProgram->getStatus();

        if (!$status->getUser()->insurancetype) {
            $status->getComplianceViewGroupStatus('required')->getComplianceViewGroup()->setMaximumNumberOfPoints(100);
        }

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
                                    <span class="label label-warning"><?php echo $viewStatus->getComment() ?></span>
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
                                    <span class="label label-danger"><?php echo $viewStatus->getComment() ?></span>
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
        $totalStatus = ComplianceStatus::NOT_COMPLIANT;
        $totalPoints = $activitiesStatus->getPoints() + $status->getPoints();

        if ($totalPoints >= 800) {
            $totalStatus = ComplianceStatus::COMPLIANT;
        } else if ($totalPoints >= 400) {
            $totalStatus = ComplianceStatus::PARTIALLY_COMPLIANT;
        }

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

            <?php if($status->getUser()->insurancetype) : ?>
                .screening-link { display:none; }
                .view-hra .links a { display:none; }
            <?php endif ?>
        </style>
        <div class="row">
            <div class="col-md-12">
                <h1>MY LiGHT <small>SPECTRUM</small></h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <a href="/content/beacon-previous-program-years">Previous Program Years</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php if($status->getUser()->insurancetype) : ?>
                    <div>
                        <p>The next Virtual Wellness Process will take place June 1 - August 31, 2016.The Process consists of:</p>
                        <p><strong>Step 1</strong>- Complete your Health Risk Assessment (HRA).</p>
                        <p><strong>Step 2</strong>- Schedule and complete your Virtual Wellness Screening appointment at designated lab
                            locations. Information regarding scheduling will be sent at a later date.</p>
                    </div>
                <?php else : ?>
                    <style type="text/css">
                        .view-screening { display:none; }
                    </style>

                    <p><a href="#" id="more-info-toggle">More...</a></span></p>

                    <div id="more-info" style="display: none">
                        <p>You will have the opportunity to earn incentive points in 2015/2016.
                            You are not required to meet the target range for every measure.
                            The criteria for meeting these ranges are listed below in your
                            LiGHT Spectrum. </p>
                    </div>
                <?php endif ?>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1 activity">
                                            <?php echo $circle(
                                                'beacon',
                                                '<span class="circle-points">'.$status->getPoints(). '</span><br/><br/>Spectrum<br/>points'
                                            ) ?>
                                            <br/>
                                            <strong><?php echo $status->getComplianceProgram()->getMaximumNumberOfPoints() ?></strong> points possible
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <?php echo $circle(
                                                'beacon',
                                                '<span class="circle-points">'.$activitiesStatus->getPoints(). '</span><br/><br/>Activity<br/>points'
                                            ) ?>
                                            <br/>
                                            <strong><?php echo $activitiesProgram->getMaximumNumberOfPoints() ?></strong> points possible
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-10 col-md-offset-1">
                                            <?php echo $circle(
                                                $totalStatus,
                                                '<span class="circle-points">'.$totalPoints. '</span><br/><br/>Total<br/>points'
                                            ) ?>
                                            <br/>
                                            <strong>
                                                <?php echo
                                                    $status->getComplianceProgram()->getMaximumNumberOfPoints() +
                                                    $activitiesProgram->getMaximumNumberOfPoints()
                                                ?>
                                            </strong> points possible
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php if($status->getUser()->insurancetype) : ?>
                    <p><a href="#" id="more-info-toggle">More...</a></span></p>
    
                    <div id="more-info" style="display: none">
                        <div class="row">
                            <div class="col-md-6">
                                <p>If you participated in the previous Virtual Wellness Screening Process,
                                    your results will be loaded into your LiGHT Spectrum, providing you wellness points.</p>

                                <p>You will have the opportunity to earn incentive points through September 30, 2016. You are not required to
                                    meet the target range for every measure. The criteria for meeting these ranges are listed below in your LiGHT Spectrum.
                                    If your screening measure falls into a medium/at risk range (color coded below yellow or red), you have the option to
                                    complete (3) online eLearning Alternative’s that will be indicated in "Alternative" links from your LiGHT Spectrum below.</p>

                                <p>Points earned through the Virtual Wellness Screening process combined with your LiGHT Activity points earned
                                    through September 30, 2016 will be applied to determine your level of discount on your medical insurance premiums in 2017.
                                    <strong>If both associate and spouse are covered on the medical plan an average of the two participant’s overall points
                                        (both Spectrum and Activity) will be used to determine the percent discount.</strong></p>
                            </div>
                            <div class="col-md-6">
                                <p>Point Discounts:</p>

                                <table id="point-discounts">
                                    <tbody>
                                    <tr>
                                        <td><?php echo $circle2('#74C36E') ?></td>
                                        <td>
                                            800-1000 points
                                        </td>
                                        <?php if($status->getUser()->insurancetype) : ?>
                                            <td>10% Discount</td>
                                        <?php endif ?>
                                    </tr>
                                    <tr>
                                        <td><?php echo $circle2('#FDB83B') ?></td>
                                        <td>
                                            400-799 points
                                        </td>
                                        <?php if($status->getUser()->insurancetype) : ?>
                                            <td>5% Discount</td>
                                        <?php endif ?>
                                    </tr>
                                    <tr>
                                        <td><?php echo $circle2('#F15752') ?></td>
                                        <td>
                                            50-399 points
                                        </td>
                                        <?php if($status->getUser()->insurancetype) : ?>
                                            <td>No Discount</td>
                                        <?php endif ?>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif ?>
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

