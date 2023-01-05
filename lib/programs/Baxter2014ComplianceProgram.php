<?php

class Baxter2014ComplianceLevelView extends ComplianceView
{
    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function __construct(ComplianceProgram $program)
    {
        $this->evaluationProgram = $program;
    }

    public function getStartDate($format = 'U')
    {
        return $this->evaluationProgram->getStartDate($format);
    }

    public function getEndDate($format = 'U')
    {
        return $this->evaluationProgram->getEndDate($format);
    }

    public function getDefaultReportName()
    {
        return 'Compliance Level';
    }

    public function getDefaultName()
    {
        return 'compliance_level';
    }

    public function getStatus(User $user)
    {
        $this->evaluationProgram->setActiveUser($this->getComplianceViewGroup()->getComplianceProgram()->getActiveUser());

        $overallStatus = $this->evaluationProgram->getStatus();

        if(!$overallStatus->getComplianceViewGroupStatus('bronze')->isCompliant()) {
            $level = 'None';
        } elseif(!$overallStatus->getComplianceViewGroupStatus('silver')->isCompliant()) {
            $level = 'Bronze';
        } elseif(!$overallStatus->getComplianceViewGroupStatus('gold')->isCompliant()) {
            $level = 'Silver';
        } else {
            $level = 'Gold';
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, $overallStatus->getPoints(), $level);
    }

    private $evaluationProgram;
}

class Baxter2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Baxter2014Printer();
        $printer->showResult(true);

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowCompliant(true);
        $printer->setShowStatus(false, false);
        $printer->setShowComment(false, false);
        $printer->setShowCompliant(false, false);
        $printer->setShowPoints(false, false);

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

        $hraView = new CompleteHRAComplianceView($programStart, '2015-04-30');
        $hraView->setReportName('Health Risk Assessment');
        $hraView->setName('hra');
        $bronzeGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, '2015-04-30');
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

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, '2015-03-01');
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $goldGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, '2015-03-01');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));
        $bloodPressureView->setUseHraFallback(true);
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');

        $goldGroup->addComplianceView($bloodPressureView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, '2015-03-01');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->overrideTestRowData(null, null, 140, 200);
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '> 140');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 200');

        $goldGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, '2015-03-01');
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 2, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');

        $goldGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, '2015-03-01');
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 1, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $goldGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, '2015-03-01');
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

        $levelGroup = new ComplianceViewGroup('levels', 'Status Level');

        $statusLevel = new Baxter2014ComplianceLevelView($this->cloneForEvaluation($programStart, '2015-03-01'));
        $statusLevel->setReportName('Level Achieved');
        $levelGroup->addComplianceView($statusLevel, true);

        $this->addComplianceViewGroup($levelGroup);
    }
}

class Baxter2014Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function printClientNote()
    {
        ?>
        <br/>
        <strong>Your Status Level:</strong>


        <?php echo $this->status->getComplianceViewStatus('compliance_level')->getComment() ?>
        <p style="font-size:.8em;"><em>Your health plan is committed to helping you achieve your best health. Rewards for participating
            in a wellness program are available to all employees. If you think you might be unable to meet a
            standard for reward under this wellness program, you might qualify for an opportunity to earn
            the same reward by different means. Contact Human Resources for more information.</em></p>
        <?php
        //$group = $groupStatus->getComplianceViewGroup();
    }

    public function printClientMessage()
    {
        ?>
        <p>
            <style type="text/css">
                #legendEntry3, #legendEntry2 {
                    display:none;
                }

                .group-levels, .view-compliance_level {
                    display:none;
                }
            </style>
        </p>

        <p style="color:red; font-weight:bold;">This is the NEW report card for 2014. To view the program that just ended,
            <a href="compliance_programs?id=256">Click Here</a>.</p>

        <p>
            Hi-Tech is committed to providing employees and their spouses the opportunity to earn incentives toward
            lowering your Health Care contribution costs. We have partnered with Circle Wellness, a member of Circle
            Health Partners, Inc. to assist our employees and their family. These programs are available to help you
            with getting on the road to better health and meeting your personal health goals (more information to
            follow).</p>

        <p>By participating in these activities now, it will allow you to identify
            some personal health goals to work toward and be able to earn incentives
            in the process. The incentives consist of either discounted premiums
            and/or enhanced benefit plan design (i.e. lower deductibles, etc.)</p>

        <p>How this program works:</p>

        <p><strong>BRONZE LEVEL:</strong>All full time employees must complete the
            health screening and HRA in order for their families to be offered the
            group health plan.</p>

        <p><strong>SILVER LEVEL:</strong>All full time employees and their spouses
            who participate and comply with all requirements outlined will be eligible
            to enroll in this plan level in July 2014. The requirements are:</p>

        <ol>
            <li>Complete HRA with Circle Wellness at time of screening (or <a href="/content/989">online </a>)
                no later than April 30, 2014. The HRA can be completed prior to the health screenings on-line.
            </li>

            <li>Complete Biometric Screening no later than April 30, 2014.</li>
            <li>Wellness Coaching with Circle Health (if contacted) – Participants that are invited by letter to participate in coaching must complete one coaching session by October 1, 2014.
            </li>
        </ol>

        <p><strong>GOLD LEVEL:</strong> All who comply with the Silver Level are eligible to participate.
            There are a total of 10 available points based on health results. Employees who collect at
            least 6 points will receive an additional health premium discount.</p>

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