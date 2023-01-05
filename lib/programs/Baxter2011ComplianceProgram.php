<?php

class Baxter2011ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Baxter2011Printer();
        $printer->showResult(true);

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Silver Level - Prevention');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Risk Assessment');
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Screening');
        $screeningView->emptyLinks();
        $preventionEventGroup->addComplianceView($screeningView);

        $privateConsultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $privateConsultationView->setReportName('Coaching (if applicable)');
        $privateConsultationView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete Coaching Program');
        $privateConsultationView->setOptional(true);
        $preventionEventGroup->addComplianceView($privateConsultationView);


        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Gold Level - Requirements');
        $biometricsGroup->setPointsRequiredForCompliance(6);

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $biometricsGroup->addComplianceView($smokingView);


        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $biometricsGroup->addComplianceView($bloodPressureView);

        /*$triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper( new ComplianceStatusPointMapper(2, 1, 2, 0) );
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');
        $biometricsGroup->addComplianceView($triglView);*/

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));
        $glucoseView->overrideTestRowData(null, 65, 99, 999);
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 64 or ≥ 100');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');
        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');
        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 1, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');
        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setReportName('Better of Body Fat/BMI');
        $bodyFatBMIView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));
        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }
}

class Baxter2011Printer extends CHPStatusBasedComplianceProgramReportPrinter
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
        Hi-Tech is committed to providing employees and their spouses the opportunity to earn incentives toward
        lowering your Health Care contribution costs. We have partnered with Circle Wellness, a member of Circle
        Health Partners, Inc. to assist our employees and their family. These programs are available to help you
        with getting on the road to better health and meeting your personal health goals (more information to
        follow).</p>

    <p>By participating in these activities now, it allows you to identify some personal health goals to work towards
        areas that will earn you incentives. The incentives consist of either discounted premiums and/or enhanced
        benefit
        plan design (i.e. lower deductibles, etc.). And, you’ll see we have made it even easier to participate this
        year!</p>

    <p>How this program works:</p>

    <p><strong>BRONZE LEVEL:</strong>All full time employees and their families
        will be offered this plan level.</p>

    <p><strong>SILVER LEVEL:</strong>All full time employees and their spouses
        who participate and comply with all requirements outlined will be eligible
        to enroll in this plan level in July 2012. The requirements are:</p>

    <ol>
        <li>Complete Health Assessment with Circle Wellness at time of screening
            (or <a href="/content/989online">online </a>) no later than August 31, 2011.
        </li>

        <li>Complete Biometric Screening with Summit Health no later than August 31, 2011.</li>
        <li>Wellness Coaching with Circle Health (if contacted) – 4 outbound coaching
            sessions are initiated throughout the year, participant who is contacted
            must complete coaching sessions no later than April 1, 2012.
        </li>
    </ol>

    <p><strong>GOLD LEVEL:</strong> All full time employees and their spouses who participate and comply with all
        the Silver Level requirements could be eligible to enroll, IF the EMPLOYEE achieves and maintains at least 6
        out of 10 possible points: There is also a reasonable alternative available if you cannot participate
        due to a medically approved condition by your physician.</p>

    <ol>
        <li>Non-Smoker (1 point available)</li>
        <li>Total Cholesterol (2 points available)</li>
        <li>Total/HDL Cholesterol Ratio (1 point available)</li>
        <li>Blood Pressure (2 points available)</li>
        <li>Blood Sugar (2 points available)</li>
        <li>BMI or Body Fat Percentage (the better result of the two tests) (2 points available)</li>
    </ol>

    <?php
    }
}