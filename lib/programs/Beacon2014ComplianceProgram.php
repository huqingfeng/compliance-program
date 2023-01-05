<?php

class Beacon2014DataComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter() 
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        
        $printer->setShowUserFields(null, null, null, false, true, false, null, null, true);
        $printer->setShowUserLocation(false);
        $printer->setShowShowRelatedUserFields(false, false, false);
        $printer->setShowUserInsuranceTypes(false, false);
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
   
        return $printer;
    }
    
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Beacon2014Printer();
        $printer->showResult(true);
        $printer->setShowMaxPoints(true);

        return $printer;
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $screeningModel = new ComplianceScreeningModel();

        $screeningTestAlias = 'beacon_compliance_2014';

        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT => new ComplianceStatusMapping('Compliant', '/resources/3813/greenblock2.jpg'),
            ComplianceStatus::NOT_COMPLIANT => new ComplianceStatusMapping('Not Compliant', '/images/ministryhealth/redblock1.jpg')
        ));

        $this->setComplianceStatusMapper($mapping);

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $preventionEventGroup = new ComplianceViewGroup('required', 'Prevention Events');
        $preventionEventGroup->setPointsRequiredForCompliance(10);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Virtual Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $preventionEventGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($preventionEventGroup);

        $biometricsGroup = new ComplianceViewGroup('Requirements', 'Biometric Measures');
        $biometricsGroup->setPointsRequiredForCompliance(0);

        $biometricsMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $bloodPressureView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'blood_pressure', $programStart, $programEnd, $screeningTestAlias);
        $bloodPressureView->setComplianceStatusPointMapper($biometricsMapper);
        $bloodPressureView->setRequiredTests(array('systolic', 'diastolic'));
        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'triglycerides', $programStart, $programEnd, $screeningTestAlias);
        $triglView->setComplianceStatusPointMapper($biometricsMapper);
        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'glucose', $programStart, $programEnd, $screeningTestAlias);
        $glucoseView->setComplianceStatusPointMapper($biometricsMapper);
        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'total_cholesterol', $programStart, $programEnd, $screeningTestAlias);
        $cholesterolView->setComplianceStatusPointMapper($biometricsMapper);
        $cholesterolView->setRequiredTests(array('cholesterol'));
        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new EvaluateScreeningTestResultComplianceView($screeningModel, 'total_hdl_cholesterol_ratio', $programStart, $programEnd, $screeningTestAlias);
        $totalHDLRatioView->setComplianceStatusPointMapper($biometricsMapper);
        $totalHDLRatioView->setRequiredTests(array('totalhdlratio'));
        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bmiView = new EvaluateBestScreeningTestResultComplianceView($screeningModel, array('body_fat', 'bmi'), $programStart, $programEnd, $screeningTestAlias);
        $bmiView->setComplianceStatusPointMapper($biometricsMapper);
        $bmiView->getView('body_fat')->setRequiredTests(array('bodyfat'));
        $biometricsGroup->addComplianceView($bmiView);

        $this->addComplianceViewGroup($biometricsGroup);

        $preventiveGroup = new ComplianceViewGroup('Preventive Exams');
        $preventiveGroup->setPointsRequiredForCompliance(0);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 290, 6);
        $annualPhysicalExamView->setMaximumNumberOfPoints(6);
        $annualPhysicalExamView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Get a physical exam with appropriate tests for your age and gender as recommended by your physician.');
        $annualPhysicalExamView->setReportName('Annual Physical Exam');

        $preventiveGroup->addComplianceView($annualPhysicalExamView);
        
        $annualDentalCleaningView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 291, 2);
        $annualDentalCleaningView->setMaximumNumberOfPoints(2);
        $annualDentalCleaningView->setStatusSummary(ComplianceStatus::COMPLIANT, 'See your dentist for an annual cleaning');
        $annualDentalCleaningView->setReportName('Annual Dental Cleaning');

        $preventiveGroup->addComplianceView($annualDentalCleaningView);
        
        $annualEyeExamView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 292, 2);
        $annualEyeExamView->setMaximumNumberOfPoints(2);
        $annualEyeExamView->setStatusSummary(ComplianceStatus::COMPLIANT, 'See your eye doctor for an annual eye exam');
        $annualEyeExamView->setReportName('Annual Eye Exam');

        $preventiveGroup->addComplianceView($annualEyeExamView);        

        $this->addComplianceViewGroup($preventiveGroup);

        $pathGroup = new ComplianceViewGroup('paths', 'PATH- Activity');
        $pathGroup->setPointsRequiredForCompliance(0);

        $cardioView = new RegexBasedActivityComplianceView($programStart, $programEnd, 308, 112);
        $cardioView->setReportName('Fitness - Cardio Exercise');
        $cardioView->setMaximumNumberOfPoints(4);
        $cardioView->setStatusSummary(ComplianceStatus::COMPLIANT, '4 points- At least 10 hours of cardio exercise per month<br/>3 points- At least 8 hours of cardio exercise per month<br/>2 points- At least 6 hours of cardio exercise per month<br/><br/>');
        $pathGroup->addComplianceView($cardioView);

        $strengthView = new RegexBasedActivityComplianceView($programStart, $programEnd, 309, 113);
        $strengthView->setReportName('Fitness - Strength Training');
        $strengthView->setMaximumNumberOfPoints(4);
        $strengthView->setStatusSummary(ComplianceStatus::COMPLIANT, '4 points- At least 6 hours of strength training exercise per month<br/>3 points- At least 4 hours of strength training exercise per month<br/>2 points- At least 2 hours of strength training exercise per month');
        $pathGroup->addComplianceView($strengthView);
        
        $noTobaccoView = new RegexBasedActivityComplianceView($programStart, $programEnd, 317, 114);
        $noTobaccoView->setMaximumNumberOfPoints(4);
        $noTobaccoView->setReportName('Breathe Easy');
        $noTobaccoView->setStatusSummary(ComplianceStatus::COMPLIANT, '4 points- Non-tobacco user status or participate in smoking cessation program');
        $pathGroup->addComplianceView($noTobaccoView);

        $weightView = new RegexBasedActivityComplianceView($programStart, $programEnd, 318, 115);
        $weightView->setMaximumNumberOfPoints(4);
        $weightView->setReportName('Healthy Weight / Diet');
        $weightView->setStatusSummary(ComplianceStatus::COMPLIANT, '4 points- Participate in a formal weight loss program<br/><br/>OR (only a maximum of 4 points can be earned)<br/><br/>4 points- Drink the daily recommended 6-8 glasses of water each day AND eat the daily recommended servings of fruits (2-4 servings) and vegetable (3-5 servings) each day on at least 20 days of the month<br/>3 points- Drink the daily recommended 6-8 glasses of water each day AND eat the daily recommended servings of fruits (2-4 servings) and vegetable (3-5 servings) each day on at least 15 days of the month<br/>2 points- Drink the daily recommended 6-8 glasses of water each day AND eat the daily recommended servings of fruits (2-4 servings) and vegetable (3-5 servings) each day on at least 10 days of the month');
        $pathGroup->addComplianceView($weightView);

        $relaxView = new RegexBasedActivityComplianceView($programStart, $programEnd, 319, 116);
        $relaxView->setMaximumNumberOfPoints(4);
        $relaxView->setReportName('Stress Control');
        $relaxView->setStatusSummary(ComplianceStatus::COMPLIANT, '4 points- At least 7 hours of sleep per night AND at least 15 minutes per day to do something -just for you- to relax on at least 20 days of the month<br/>3 points- At least 7 hours of sleep per night AND at least 15 minutes per day to do something “just for you” to relax on at least 15 days of the month<br/><br/>OR (only a maximum of 4 points can be earned)<br/><br/>3 points- Take at least TWO weeks (at least one consecutive week at a time) of Vacation or MY TIME<br/>2 points- Take at least ONE consecutive week of Vacation or MY TIME');
        $pathGroup->addComplianceView($relaxView);

        $this->addComplianceViewGroup($pathGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if(!$this->evaluateOverall) {
            return;
        }

        $priorStatus = $status->getComplianceProgram()->get2013BiometricsComplianceViewStatuses($status->getUser());

        $status->setAttribute('prior_status', $priorStatus);
    }
    
    public function get2013BiometricsComplianceViewStatuses(User $user)
    {
        $program = $this->cloneForEvaluation('2013-01-01', '2013-12-31');

        $program->evaluateOverall = false;

        $program->setActiveUser($user);

        return $program->getStatus();
    }

    protected $evaluateOverall = true;
}

class Beacon2014Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setPageHeading('My Health Compass');
        $this->showTotalCompliance(true);
        $this->setPointsHeading('Points');
        $this->resultHeading = 'Result';
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
        </style>
        <?php
    }

    protected function printTotalStatus()
    {
        $status = $this->status;

        ?>
        <tr class="headerRow firstRow">
            <th>Total Status</th>
            <td></td>
            <td></td>
            <td></td>
            <td class="your_points">Points</td>
            <td class="your_points">Status</td>
            <td></td>
        </tr>
        <tr class="secondRow status-<?php echo $status->getStatus() ?>">
            <th>Overall Status</th>
            <td></td>
            <td></td>
            <td></td>
            <td style="text-align:center"><?php echo $status->getPoints() ?></td>
            <td class="your_points" style="text-align:center"><?php echo $status->getText() ?></td>
            <td></td>
        </tr>
        <?php
    }
    
    public function printReport(\ComplianceProgramStatus $status) 
    {
        $priorStatus = $status->getAttribute('prior_status');

        $escaper = new hpn\common\text\Escaper;

        $lastYearRequirementsGroup = $priorStatus->getComplianceViewGroupStatus('Requirements');

        ?>
        <script type="text/javascript">
            $(function() {
                $('.view-hra .requirements').html('Complete');
                $('.view-screening .requirements').html('Complete');

                var i = 0;

                var fixGroup = function(name) {
                    $('tr.group-' + name + ' td:eq(0)').attr('colspan', 2);
                    $('tr.group-' + name + ' td:eq(1)').remove();

                    $viewsInGroup = $('tr.group-' + name).nextUntil('tr.headerRow');

                    $viewsInGroup.find('td:eq(1)').attr('colspan', 2);
                    $viewsInGroup.find('td:eq(2)').remove();



                    $viewsInGroup.find('.summary').each(function() {
                        var target = $(this).html().trim();

                        if(target.length > 15) {
                            $(this).css('text-align', 'left');
                            $(this).html('<a id="target-popup-' + i + '" href="#" class="more-info-anchor" onclick="return false;"><i class="icon-info-sign" style="margin-right:3px"></i>' + target.substring(0, 15) + '...</a>');
                            $('.phipTable').append('<' + 'script type="text/javascript"' + '>' + '$(function () {$("#target-popup-' + i + '").popover({ "title": "More Information", content: "' + target.replace('/\"/g', "\\\"") + '", trigger:"hover", html:true }); });</' + 'script' + '>');
                            i++;
                        }
                    });
                };

                fixGroup('preventive-exams');
                fixGroup('paths');

                var $newResults = $('.group-requirements.totalRow');
                var $oldResults = $newResults.clone().addClass('group-requirements-2013');

                $newResults.addClass('group-requirements-2014').after($oldResults);

                //$newResults.find('th').html('2014 Biometric Point Totals');

                $oldResults.find('th').html('Biometric Point Totals');

                $oldResults.find('td:eq(2)').html('<?php echo $escaper->escapeJs($lastYearRequirementsGroup->getComplianceViewGroup()->getMaximumNumberOfPoints()) ?>');
                $oldResults.find('td:eq(3)').html('<?php echo $escaper->escapeJs($lastYearRequirementsGroup->getPoints()) ?>');

                $('.totalRow.group-preventive-exams th').html('Preventive Exams Totals');
                $('.totalRow.group-paths th').html('Path-Activity Totals');
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



        <p>Welcome to Beacon Memorial Health System Wellness Website! This site was developed not only to track your wellness requirements, but also to be used as a great resource for health related topics and questions. We encourage you to explore the site while also fulfilling your requirements. By completing the following steps in 2013 you will fulfill your requirements to participate in the Health Plan.
        </p>

        <div class="well" id="steps">
            <p><strong>Step 1</strong>- Complete your Virtual Wellness Screening. Screenings are scheduled November 1, 2013 - January 31, 2014.</p>

            <p><strong>Step 2</strong>- Complete your Health Risk Assessment. <a href="/content/1006">Click here </a>to complete your Questionnaire if you haven’t already done so.</p>
        </div>

        <p>The following legend gives you an idea of where your health status is:</p>
        <div style="padding:10px 0 20px 60px;">
            <table id="sample_table">
                <tr><th></th><th style="font-size:12pt;text-decoration: underline">Total Score</th></tr>
                <tr><td style="width:100px;background-color:red;padding:5px;"></td><td>0-33 points</td><td>Improvement Needed</td></tr>
                <tr><td style="width:100px;background-color:yellow;padding:5px;"></td><td>34-65 points</td><td>Borderline</td></tr>
                <tr><td style="width:100px;background-color:green;padding:5px;"></td><td>66-100 points</td><td>Recommended</td></tr>
            </table>
        </div>
            <!--<p>Once your results are displayed on your Health Compass, a reasonable alternative (see e-Learning description below) will be offered if you are not able to achieve these requirements. You may preview these lessons by clicking on the links for each biometric measure in your 2014 health compass preview.</p>

         <ul>
             <li>Complete the e-Learning lessons for the biometric measures through the links on your Health Compass. Once you complete all of the lessons for a particular biometric measure, your Health Compass will populate with the maximum
        points for that measure. e-Learning lessons must be completed by xx/xx/xxxx. Please contact the Circle Wellness Hotline if you have any questions: 1‐866‐682‐3020.
             </li>
         </ul>

         <p><span style="font-size:12pt;color:red;">The Health Compass below is a sample using your results from 2013 to show where you would score based on the 2014 program.</span></p> -->

    <?php
    }
}