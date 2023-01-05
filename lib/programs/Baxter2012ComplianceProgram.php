<?php

class Baxter2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Baxter2012Printer();
        $printer->showResult(true);

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('client_name', function (User $user) {
            return (string) $user->client->name;
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $bronzeGroup = new ComplianceViewGroup('bronze', 'Bronze Level');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Risk Assessment');
        $hraView->setName('hra');
        $bronzeGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Screening');
        $screeningView->setName('screening');
        $screeningView->emptyLinks();
        $bronzeGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($bronzeGroup);

        $silverGroup = new ComplianceViewGroup('silver', 'Silver Level - Prevention');

        $spouseStatus = new RelatedUserCompleteComplianceViewsComplianceView(
            $this,
            array('hra', 'screening'),
            array(Relationship::EMPLOYEE, Relationship::SPOUSE)
        );

        $spouseStatus->setReportName('Spouse Status');
        $spouseStatus->setName('spouse_hra_screening');
        $spouseStatus->setStatusSummary(ComplianceStatus::COMPLIANT, 'Spouse complete HRA & Screening');
        $silverGroup->addComplianceView($spouseStatus);

        $privateConsultationView = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $privateConsultationView->setName('consultation');
        $privateConsultationView->setReportName('Coaching (if applicable)');
        $privateConsultationView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete Coaching Program');
        $privateConsultationView->setOptional(true);
        $silverGroup->addComplianceView($privateConsultationView);

        $this->addComplianceViewGroup($silverGroup);

        $goldGroup = new ComplianceViewGroup('gold', 'Gold Level - Requirements');
        $goldGroup->setPointsRequiredForCompliance(6);

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $goldGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));
        $bloodPressureView->setUseHraFallback(true);
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');

        $goldGroup->addComplianceView($bloodPressureView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->overrideTestRowData(null, null, 140, 200);
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '> 140');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 200');

        $goldGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');

        $goldGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 1, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $goldGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setReportName('Better of Body Fat/BMI');
        $bodyFatBMIView->setUseHraFallback(true);
        $bodyFatBMIView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));

        $goldGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($goldGroup);
    }

    public function loadEvaluators()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $levelGroup = new ComplianceViewGroup('levels', 'Status Levels');

        $bronzeLevel = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($programStart, $programEnd), array('bronze'));
        $bronzeLevel->setName('bronze_view');
        $bronzeLevel->setReportName('Bronze');
        $levelGroup->addComplianceView($bronzeLevel, true);

        $silverLevel = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($programStart, $programEnd), array('bronze', 'silver'));
        $silverLevel->setName('silver_view');
        $silverLevel->setReportName('Silver');

        $levelGroup->addComplianceView($silverLevel, true);

        $goldLevel = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($programStart, $programEnd), array('bronze', 'silver', 'gold'));
        $goldLevel->setName('gold_view');
        $goldLevel->setReportName('Gold');

        $levelGroup->addComplianceView($goldLevel, true);

        $this->addComplianceViewGroup($levelGroup);
    }
}

class Baxter2012Printer extends CHPStatusBasedComplianceProgramReportPrinter
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

    <p style="color:red; font-weight:bold;">This is the NEW report card for 2012. To view the program that just ended,
        <a href="compliance_programs/index?id=126">Click Here</a>.</p>

    <p>
        Hi-Tech is committed to providing employees and their spouses the opportunity to earn incentives toward
        lowering your Health Care contribution costs. We have partnered with Circle Wellness, a member of Circle
        Health Partners, Inc. to assist our employees and their family. These programs are available to help you
        with getting on the road to better health and meeting your personal health goals (more information to
        follow).</p>

    <p>By participating in these activities now, it will allow you to identify
        some personal health goals to work toward and be able to earn incentives
        in the process. The incentives consist of either discounted premiums
        and/or enhanced benefit plan design (i.e. lower deductibles, etc.). And,
        you’ll see we have made it even easier to participate this year!</p>

    <p>How this program works:</p>

    <p><strong>BRONZE LEVEL:</strong>All full time employees must complete the
        health screening and HRA in order for their families to be offered the
        group health plan.</p>

    <p><strong>SILVER LEVEL:</strong>All full time employees and their spouses
        who participate and comply with all requirements outlined will be eligible
        to enroll in this plan level in July 2012. The requirements are:</p>

    <ol>
        <li>Complete HRA with Circle Wellness at time of screening (or <a href="/content/989">online </a>)
            no later than May 4, 2012. The HRA can be completed prior to the health screenings on-line.
        </li>

        <li>Complete Biometric Screening with Summit Health no later than May 4. 2012.</li>
        <li>Wellness Coaching with Circle Health (if contacted) – 4 outbound coaching sessions are
            initiated throughout the year, participant who has been contacted based on 2012 health
            screenings must complete 2 coaching sessions by December 31, 2012 and 2 additional sessions by April 1, 2013.
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