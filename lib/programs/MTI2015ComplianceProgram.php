<?php

use hpn\steel\query\SelectQuery;

class MTI2015ComplianceProgram extends ComplianceProgram
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

        return $printer;
    }
    
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new MTI2015Printer();
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
        $biometricsGroup->setPointsRequiredForCompliance(0);

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

class MTI2015Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setPageHeading('My Report Card (<a href="/compliance_programs?id=536">My Wellness Activities</a>)');
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
            your requirements. By completing the items below in 2014 you can earn incentives.</p>

        <p>You will have the opportunity to earn incentive points in 2014/2015. You are not
        required to meet the target range for every measure. The criteria for meeting these ranges are listed below.</p>


    <?php
    }
}
