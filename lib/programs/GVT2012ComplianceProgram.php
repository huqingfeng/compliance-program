<?php

class GVT2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new GVT2012Printer();
        $printer->showResult(true);

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');

        $hraScreeningView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScreeningView->setReportName('Screening Program');
        $preventionEventGroup->addComplianceView($hraScreeningView);

        $privateConsultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $preventionEventGroup->addComplianceView($privateConsultationView);

        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements');
        $biometricsGroup->setPointsRequiredForCompliance(6);

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));

        $biometricsGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');

        $bloodPressureView->setUseHraFallback(true);
        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 1, 1, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');

        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 1, 0, 0));
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

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));

        $bodyFatBMIView->setUseHraFallback(true);
        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }
}

class GVT2012Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        ?>
    <p>
        <style type="text/css">
            #legendEntry3, #legendEntry2 {
                display:none;
            }
        </style>
    </p>


    <p>
        <a href="/compliance_programs/index?id=125">
            View your 2011 Program
        </a>
    </p>
    <p>
        This wellness web site was developed not only to track your wellness
        requirements, but also to be used as a great resource for health related
        topics and questions. We encourage you to explore the site while also
        fulfilling your requirement. By completing the following steps in 2012
        you will be eligible for a premium reduction in your health plan
        effective 01/01/2013.</p>
    <p>
        <strong>Step 1</strong>- Complete your on-site health screening and
        private one-on-one consultations for eligibility in the health plan.
        Screenings will be scheduled again beginning in August/September of 2012
        and consultations will follow.</p>
    <p>
        <strong>Step 2</strong>- Accumulate 6 points from the incentive report
        card requirements below. The criteria for meeting these ranges will be
        based on your 2012 health screening results. If you have a medical reason
        for not being able to reach one or more of the requirements listed below,
        you can contact Julia Paulus in Human Resources to discuss a
        reasonable alternative.</p>
    <p>Point-Earning Opportunities</p>
    <?php
    }
}