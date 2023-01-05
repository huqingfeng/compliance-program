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

class Beacon2015CompleteHRAComplianceView extends CompleteHRAComplianceView
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

class Beacon2015CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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

class Beacon2015DataComplianceProgram extends ComplianceProgram
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
        $printer->setShowComment(false,false,true);
        
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
        $printer = new Beacon2015Printer();
        $printer->showResult(true);
        $printer->setShowMaxPoints(true);

        return $printer;
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
        $preventionEventGroup->setPointsRequiredForCompliance(50);

        $hraView = new Beacon2015CompleteHRAComplianceView($programStart, '2015-09-30');
        $hraView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, '2015-09-30');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $screeningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete');
        $screeningView->setReportName('Virtual Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $preventionEventGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($preventionEventGroup);

        $smokingGroup = new ComplianceViewGroup('Tobacco Status');
        $smokingGroup->setPointsRequiredForCompliance(50);

        $tobaccoView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, '2015-09-30');
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

        $bloodPressureView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'blood_pressure', $programStart, $programEnd, $screeningTestAlias);
        $bloodPressureView->setComplianceStatusPointMapper($biometricsMapper);
        $bloodPressureView->setRequiredTests(array('systolic', 'diastolic'));
        $bloodPressureView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
        $this->configureViewForElearningAlternative($bloodPressureView, 'resalt_bloodpressure');
        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'triglycerides', $programStart, $programEnd, $screeningTestAlias);
        $triglView->setComplianceStatusPointMapper($biometricsMapper);
        $triglView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
        $this->configureViewForElearningAlternative($triglView, 'resalt_triglycerides');
        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'glucose', $programStart, $programEnd, $screeningTestAlias);
        $glucoseView->setComplianceStatusPointMapper($biometricsMapper);
        $glucoseView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
        $this->configureViewForElearningAlternative($glucoseView, 'resalt_bloodsugar');
        $biometricsGroup->addComplianceView($glucoseView);

        $totalHDLRatioView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'total_hdl_cholesterol_ratio', $programStart, $programEnd, $screeningTestAlias);
        $totalHDLRatioView->setComplianceStatusPointMapper($biometricsMapper);
        $totalHDLRatioView->setRequiredTests(array('totalhdlratio'));
        $totalHDLRatioView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=339', false, '_self', 'screening-link'));
        $this->configureViewForElearningAlternative($totalHDLRatioView, 'resalt_cholesterol');
        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bmiView = new BeaconEvaluateScreeningTestResultComplianceView($screeningModel, 'bmi', $programStart, $programEnd, $screeningTestAlias);
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
                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), '2015-09-30', $alias);
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

class Beacon2015Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setPageHeading('My LiGHT Spectrum (<a href="/compliance_programs?id=365">View LiGHT Activities</a>)');
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
                $.get('/compliance_programs?id=365', function(fullPage) {
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


        <?php if($this->status->getUser()->insurancetype) : ?>
            <p>Welcome to your Wellness Website! This site was developed not only to track your wellness
                requirements to be covered under the medical plan, but also used to track wellness Activity
                points through the LiGHT Program.  We encourage you to explore the site as it is a great resource
                for health related topics and questions. </p>
            
            <span class="show_more"><a href="#">More...</a></span>

            <p class="hide">If you participated in the previous Virtual Wellness Screening Process (November 2014 – January 2015),
                your results will be loaded into your LiGHT Spectrum, replacing your previous screening results and any points earned
                under each lab measure. If this is your first year participating in the screening program, your results will be
                viewable in your LiGHT Spectrum upon completion of your screening in August/September 2015.</p>

            <p class="hide">You will have the opportunity to earn incentive points through September 30, 2015. You are not required to
                meet the target range for every measure. The criteria for meeting these ranges are listed below in your LiGHT Spectrum.
                If your screening measure falls into a medium/at risk range (color coded below yellow or red), you have the option to
                complete (3) online eLearning Alternative’s that will be indicated in "Alternative" links from your LiGHT Spectrum below.</p>

             <p class="hide">Points earned through the Virtual Wellness Screening process combined with your LiGHT Activity points earned
                 through September 30, 2015 will be applied to determine your level of discount on your medical insurance premiums in 2016.
                 <strong>If both associate and spouse are covered on the medical plan an average of the two participant’s overall points
                 (both Spectrum and Activity) will be used to determine the percent discount.</strong></p>

            <div class="well" id="steps">
                <p><strong>Step 1</strong>- Complete your Health Risk Assessment (HRA).</p>
                <p><strong>Step 2</strong>- Schedule your Virtual Wellness Screening appointment.  Appointments are only
                    available between August 1 – September 30, 2015 at designated lab locations.</p>
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

            <p class="hide">You will have the opportunity to earn incentive points in 2014/2015.
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





    <?php
    }
}
