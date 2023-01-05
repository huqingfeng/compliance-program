<?php

class IntegrityAerospaceGroup2013ComplianceProgram extends ComplianceProgram
{
    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $mapping = new ComplianceStatusMapper();


        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Completed', '/images/shape/lights/notext-green.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Completed', '/images/shape/lights/notext-yellow.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Completed', '/images/shape/lights/notext-red.jpg'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Required', '/images/shape/lights/notext-black.jpg')
        ));

        $this->setComplianceStatusMapper($mapping);

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $hraView = new CompleteHRAComplianceView($programStart, '2012-11-30');
        $hraView->setName('hra');
        $hraView->setReportName('HRA (Health Risk Appraisal)');
        $requiredGroup->addComplianceView($hraView);

        $screeningForm = new CompleteScreeningComplianceView($programStart, '2012-11-30');
        $screeningForm->setName('screening');
        $screeningForm->setReportName('Biometric Screening');
        $requiredGroup->addComplianceView($screeningForm);

        $coachingView = new AttendAppointmentComplianceView($programStart, '2013-05-31');
        $coachingView->setReportName('Health Coaching');
        $coachingView->setName('coaching');
        $coachingView->setNumberRequired(2);
        $coachingView->setOptional(true);
        $requiredGroup->addComplianceView($coachingView);

        $this->addComplianceViewGroup($requiredGroup);

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
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-64 or 100-125');
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

        $bodyFatBMIView = new NSK2012ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setUseHraFallback(true);
        $bodyFatBMIView->setComplianceStatusPointMapper(new NSK2012BFMapper());
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $p = new IntegrityAerospaceGroup2013ComplianceProgramReportPrinter();
        $p->showResult(true);


        return $p;
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
            'ComplyWithBodyFatScreeningTestComplianceView'
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

class IntegrityAerospaceGroup2013ComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
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
                3 => $mappings[ComplianceStatus::NOT_COMPLIANT],
                2 => $mappings[ComplianceStatus::NOT_COMPLIANT],
                1 => $mappings[ComplianceStatus::NOT_COMPLIANT],
                0 => $mappings[ComplianceStatus::NOT_COMPLIANT]
            );
        } else {
            return $mappings;
        }
    }

    public function printClientMessage()
    {
        ?>
        <p>
            The Integrity Aerospace Group’s Wellness Program is focused on helping you and your family stay healthy. There
            are 2 requirements (unless you are contacted and need to complete the coaching calls) to maintain compliance in
            2013 and be eligible for the $600 incentive.
        </p>

        <p>
            Spouses that are on IAG’s medical plan are required to participate in order for the employee to receive the $600
            incentive.


        </p>

        <p>
            The requirements below outline the steps you will need to complete. If you choose not to participate in the
            wellness program, you will not be eligible for the $600 incentive.</p>
        <?php
    }
}