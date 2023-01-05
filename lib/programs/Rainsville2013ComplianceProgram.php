<?php

class Rainsville2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Rainsville2013Printer();
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
        $biometricsGroup->setPointsRequiredForCompliance(4);

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));

        $biometricsGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->overrideSystolicTestRowData(null, null, 125, 140);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 85, 90);
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '126 - 139/86 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $bloodPressureView->setUseHraFallback(true);

        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 1, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');

        $biometricsGroup->addComplianceView($triglView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
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

class Rainsville2013Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        ?>
        <div style="width:10in; margin:0; padding:0.35in 0.35in 0 0.35in;">
        <?php
        parent::printReport($status);
        ?>
        </div>
        <?php
    }

    public function printClientMessage()
    {
        global $_user;

        $user = $_user;

        ?>
    <p>
        <style type="text/css">
            #legendEntry3, #legendEntry2 {
                display:none;
            }

            #pageMessage {
                display:none;
            }
        </style>
    </p>

        <table style="width:100%;margin-bottom:1.0in;">
        <tr>
            <td style="width:70%;">
                Rainsville Technology<br/>
                c/o Circle Health Partners, Inc.<br/>
                450 East 96th St., Ste 500<br/>
                Indianapolis, IN 46240
            </td>
            <td style="width:294px;">

            </td>
        </tr>
    </table>
        <table style="width:100%;margin-bottom:0.6in;">
        <tr style="font-weight:bold;padding-top:10em;">
            <td style="width:70%;"><br/>
                <u>Personalized for:</u><br/>

                <div style="margin-left:0.5in;">
                    <?php echo $user ?><br/>
                    <?php echo $user->getFullAddress('<br/>') ?>
                </div>
            </td>
            <td><br/>
                Claims as of: <?php echo date('m/d/Y') ?>
            </td>
        </tr>
    </table>

    <div>
    </div>

    <p>
        This wellness web site was developed not only to track your wellness
        requirements, but also to be used as a great resource for health related
        topics and questions. We encourage you to explore the site while also
        fulfilling your requirement. By completing the following steps in 2013
        you will be eligible for the 2013 premium rate in your health plan
        effective 01/01/2014.</p>
    <p>
        <strong>Step 1</strong>- Complete your on-site health screening, health risk appraisal questionnaire (HRA) and
        private one-on-one consultations for eligibility in the health plan.
        Screenings will be scheduled again beginning in October of 2013
        and consultations will follow in November.</p>
    <p>
        <strong>Step 2</strong>- Accumulate 4 points from the incentive report
        card requirements below. The criteria for meeting these ranges will be
        based on your 2013 health screening results. If you have a medical reason
        for not being able to reach one or more of the requirements listed below,
        you can contact Pam Willingham in Human Resources to discuss a
        reasonable alternative.</p>
    <p>Point-Earning Opportunities</p>
    <?php
    }
}