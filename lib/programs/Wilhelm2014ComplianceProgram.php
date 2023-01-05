<?php

class Wilhelm2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Wilhelm2014Printer();
        $printer->showResult(true);

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Screening Program');
        $preventionEventGroup->addComplianceView($screeningView);

        $privateConsultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $preventionEventGroup->addComplianceView($privateConsultationView);

        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements');
        $biometricsGroup->setPointsRequiredForCompliance(4);

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco Status');
        $smokingView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Non-Tobacco User');
        $smokingView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'Tobacco User');
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $smokingView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_smoking'));

        $biometricsGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->overrideSystolicTestRowData(null, null, 125, 140);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 85, 90);
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '126 - 139/86 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $bloodPressureView->setUseHraFallback(true);
        $bloodPressureView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_blood_pressure'));

        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 1, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');
        $triglView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_cholesterol'));

        $biometricsGroup->addComplianceView($triglView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');
        $totalHDLRatioView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=all_lessons'));

        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bodyFatBMIView->setUseHraFallback(true);
        $bodyFatBMIView->addLink(new Link('Alternative', '/content/9420?action=lessonManager&tab_alias=required_weight_management'));

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }
}

class Wilhelm2014Printer extends CHPStatusBasedComplianceProgramReportPrinter
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

            <script type="text/javascript">
                $(function() {
                    $('.group-requirements:first td:last').html('Alternatives');
                });
            </script>
        </p>



        <div>
        </div>

        <p>
            This wellness web site was developed not only to track your wellness requirements, but also
            to be used as a great resource for health related topics and questions. We encourage you to explore
            the site while also fulfilling your requirement. By completing the following steps in 2014 you will
            be eligible for a reduction of $xxx.xx in your health plan premium effective xx/xx/xxxx.</p>
        <p>
            <strong>Step 1</strong>- Complete your on-site health screening, health risk appraisal questionnaire
            (HRA) and private one-on-one consultations. Screenings will be
            scheduled again in the summer of 2014 and consultations will follow.</p>
        <p>
            <strong>Step 2</strong>- Accumulate X points from the incentive report card requirements below.
            The criteria for meeting these ranges will be based on your 2014 health screening results.
            If you are unable to reach any of the requirements, you may take the e-learning lessons in
            the “Alternative” link next to “Your Result” to obtain points for those requirements.</p>

        <p>Are you pregnant or 6 months postpartum? If so, <a href="/downloads/F.A. Wilhelm/pregnancy.exception.form.2010.pdf">click
                here </a>for a pregnancy exception form for the body measurements.</p>

    <?php
    }
}