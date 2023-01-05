<?php

class IntegrityAerospaceGroup2014With2012DataBFMapper extends ComplianceStatusPointMapper
{
    public function __construct()
    {

    }

    public function getMaximumNumberOfPoints()
    {
        return 4;
    }

    public function getPoints($status)
    {
        return $status;
    }

    private $mapping;
}

class IntegrityAerospaceGroup2014With2012DataComplyWithBodyFatBMIScreeningTestComplianceView extends ComplyWithBodyFatBMIScreeningTestComplianceView
{
    public function getStatusSummary($status)
    {
        return sprintf(
            'Body Fat: %s, BMI: %s',
            $this->bodyFatView->getStatusSummary($status),
            $this->bmiView->getStatusSummary($status)
        );
    }
}

class IntegrityAerospaceGroup2014With2012DataComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new IntegrityAerospaceGroup2014With2012DataPrinter();
        $printer->showResult(true);
        $printer->setShowMaxPoints(false);

        return $printer;
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT => new ComplianceStatusMapping('Compliant', '/images/lights/greenlight.gif'),
            ComplianceStatus::NOT_COMPLIANT => new ComplianceStatusMapping('Not Compliant', '/images/lights/redlight.gif')
        ));

        $this->setComplianceStatusMapper($mapping);

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();
        
        $learnStartDate = strtotime('2013-01-01');

        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');


        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Risk Assessment(HRA)');
        $hraView->emptyLinks();
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('On-site or On-demand Health screening');
        $screeningView->emptyLinks();
        $preventionEventGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($preventionEventGroup);

        $tobaccoGroup = new ComplianceViewGroup('Tobacco');

        $smokingView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco');
        $smokingView->addLink(new link('Complete Affidavit with HR', '/content/989'));
        $smokingView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Negative (Cotinine)');
        $tobaccoGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($tobaccoGroup);

        $biometricsGroup = new ComplianceViewGroup('Requirements');
        $biometricsGroup->setPointsRequiredForCompliance(6);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $bloodPressureView->setUseHraFallback(true);

        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');

        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 64 or ≥ 100');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');

        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');

        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new IntegrityAerospaceGroup2014With2012DataComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setComplianceStatusPointMapper(new IntegrityAerospaceGroup2014With2012DataBFMapper());
        $bodyFatBMIView->setReportName('Better of Body Fat/BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);
        $bodyFatBMIView->setUseHraFallback(true);

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }
    
    private function getAlternateElearningView($group, $startDate, $alias)
    {
        $view = new CompleteELearningGroupSet($startDate, $this->getEndDate(), $alias);

        $view->useAlternateCode(true);

        $view->setNumberRequired(2);

        $view->setComplianceViewGroup($group);

        return $view;
    }    

    private function getBmiView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBMIScreeningTestComplianceView'
        );

        $view->addRange(4, 18.5, 25.0, 'E');
        $view->addRange(3, 17.0, 30.0, 'E');
        $view->addRange(2, 15.0, 35.0, 'E');
        $view->addRange(1, 13.0, 40.0, 'E');
        $view->setStatusSummary(0, '&lt;13 or &gt;40');

        return $view;
    }

    private function getBodyFatView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBMIScreeningTestComplianceView'
        );

        $view->addRange(4, 2.0, 18.0, 'M');
        $view->addRange(3, 0.0, 25.0, 'M');
        $view->addRange(2, 0.0, 30.0, 'M');
        $view->addRange(1, 0.0, 35.0, 'M');
        $view->addDefaultStatusSummaryForGender(0, 'M', '&gt;35');


        $view->addRange(4, 12.0, 25.0, 'F');
        $view->addRange(3, 0.0, 32.0, 'F');
        $view->addRange(2, 0.0, 37.0, 'F');
        $view->addRange(1, 0.0, 42.0, 'F');
        $view->addDefaultStatusSummaryForGender(0, 'F', '&gt;42');


        return $view;
    }
}

class IntegrityAerospaceGroup2014With2012DataPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    protected function pointBasedViewStatusMatchesMapping($viewStatus, $mapping)
    {
        if($viewStatus->getComplianceView()->getName() == 'bf_bmi') {
            return $viewStatus->getPoints() == $mapping;
        } else {
            return parent::pointBasedViewStatusMatchesMapping($viewStatus, $mapping);
        }
    }

    protected function getStatusMappings(ComplianceView $view)
    {
        $mappings = parent::getStatusMappings($view);

        if($view->getName() == 'bf_bmi') {
            return array(
                4 => $mappings[ComplianceStatus::COMPLIANT],
                3 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                2 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                1 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                0 => $mappings[ComplianceStatus::NOT_COMPLIANT]
            );
        } else {
            return $mappings;
        }
    }

    public function printClientNote()
    {
        ?>
        <!--<p style="margin-top:20px;font-size:smaller;">*If an onsite screening is not available at your location, you will receive a packet from HR to bring to your physician to have your labs done.</p>

        <p style="margin-top:20px;font-size:smaller;">**If contacted for coaching, employees and spouses must complete two coaching calls by May 30, 2014.</p>


        <p style="margin-top:20px;font-size:smaller;">Pregnant? Are you pregnant or 6 months postpartum? You may complete a <a href="/resources/4267/IntegrityAerospaceGroupPregnancyExceptionForm.042313.pdf">Pregnancy Exception Form </a> and submit it to Circle Wellness to obtain credit for the body measurements (BMI and Body Fat).</p>
-->

    <?php
    }

public function printClientMessage()
{
    ?>
    <script type="text/javascript">
        $(function() {
            $('.totalRow.group-requirements').nextAll().each(function() {
                $(this).find(':nth-child(1)').attr('colspan', 2);
                $(this).find(':nth-child(2)').remove();
                $(this).find(':nth-child(2)').html('');
                $(this).find(':nth-child(4)').attr('colspan', 2);
                $(this).find(':nth-child(5)').remove();
            });

            $('.headerRow.group-prevention-event td:nth-child(2)').html('');
            $('.view-complete_hra .requirements').html('');
            $('.view-complete_screening .requirements').html('');
            $('.view-comply_with_cotinine_screening_test .links').html('Complete Affidavit with HR');
        });
    </script>
    <style type="text/css">
        .totalRow.group-campaigns {
            display:none;
        }

        .headerRow.group-campaigns {
            border-top: 2px solid #D7D7D7;
        }

        .view-bf_bmi.statusRow4 {
            background-color:#BEE3FE;
        }

        .view-bf_bmi.statusRow3 {
            background-color:#DDEBF4;
        }

        .view-bf_bmi.statusRow2 {
            background-color:#FFFDBD;
        }

        .view-bf_bmi.statusRow1 {
            background-color:#FFDC40;
        }

        .view-bf_bmi.statusRow0 {
            background-color:#FF6040;
        }

        .view-comply_with_cotinine_screening_test .requirements{
            text-align: center;
        }
    </style>
    <p>
        <style type="text/css">
            #legendEntry3, #legendEntry2 {
                display:none;
            }
        </style>
    </p>


    <p>
        <a href="/compliance_programs/index?id=225">
            View your 2013 Program
        </a>
    </p>
    <p>
        This is a SAMPLE report card populated with your 2012 screening results.   <a href="/compliance_programs?id=265">Your 2014 incentive</a> will be determined by your 2013 screening results and will populated within 3-5 days of your 2013 screening.</p>



    <p>

<?php
}
}