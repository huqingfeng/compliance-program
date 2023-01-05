<?php

class FTE2012AutomotiveComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new FTE2012AutomotiveComplianceProgramReportPrinter();
    }

    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $group = new ComplianceViewGroup('required', 'Required');

        $scrView = new CompleteScreeningComplianceView($start, $end);
        $scrView->setReportName('Wellness Screening');
        $group->addComplianceView($scrView);

        $hraView = new CompleteHRAComplianceView($start, $end);
        $hraView->setReportName('Health Risk Assessment');
        $group->addComplianceView($hraView);

        $points = new ComplianceViewGroup('points', 'Points');
        $points->setPointsRequiredForCompliance(7);

        $bmiOrFat = new ComplyWithBodyFatBMIScreeningTestComplianceView($start, $end);
        $bmiOrFat->setReportName('Best of Body Fat or BMI');
        $bmiOrFat->setUseHraFallback(true);
        $bmiOrFat->overrideBMITestRowData(null, null, 29.999, null);
        $bmiOrFat->overrideBodyFatTestRowData(null, null, 25.999, null);
        $bmiOrFat->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $bmiOrFat->setStatusSummary(ComplianceStatus::COMPLIANT, 'Body Fat: &lt;26, BMI: &lt;30');

        $points->addComplianceView($bmiOrFat);

        $cot = new ComplyWithCotinineScreeningTestComplianceView($start, $end);
        $cot->setReportName('Cotinine (Tobacco Use)');
        $cot->setStatusSummary(ComplianceStatus::COMPLIANT, 'No Use');
        $cot->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(4, 0, 0, 0));

        $points->addComplianceView($cot);

        $ldl = new ComplyWithLDLScreeningTestComplianceView($start, $end);
        $ldl->setReportName('LDL Cholesterol');
        $ldl->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldl->overrideTestRowData(null, null, 160, null);

        $points->addComplianceView($ldl);

        $bp = new ComplyWithBloodPressureScreeningTestComplianceView($start, $end);
        $bp->setReportName('Blood Pressure');
        $bp->setUseHraFallback(true);
        $bp->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bp->overrideSystolicTestRowData(null, null, 140, null);
        $bp->overrideDiastolicTestRowData(null, null, 90, null);

        $points->addComplianceView($bp);

        $gl = new ComplyWithGlucoseScreeningTestComplianceView($start, $end);
        $gl->setReportName('Glucose');
        $gl->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gl->overrideTestRowData(null, null, 125, null);

        $points->addComplianceView($gl);

        $tri = new ComplyWithTriglyceridesScreeningTestComplianceView($start, $end);
        $tri->setReportName('Triglycerides');
        $tri->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tri->overrideTestRowData(null, null, 199, null);

        $points->addComplianceView($tri);

        $this->addComplianceViewGroup($group);
        $this->addComplianceViewGroup($points);
    }
}

class FTE2012AutomotiveComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    protected function getStatusMappings(ComplianceView $view)
    {
        $status = parent::getStatusMappings($view);

        if(isset($status[ComplianceStatus::COMPLIANT])) {
            return array(ComplianceStatus::COMPLIANT => $status[ComplianceStatus::COMPLIANT]);
        } else {
            return array();
        }
    }

    public function printClientMessage()
    {
        $user = sfContext::getInstance()->getUser()->getUser();

        ?>
    <style type="text/css">
        #overviewCriteria {
            width:100%;
            border-collapse:collapse;
        }

        #overviewCriteria th {
            background-color:#42669A;
            color:#FFFFFF;
            font-weight:normal;
            font-size:11pt;
            padding:5px;
        }

        #overviewCriteria td {
            width:33.3%;
            vertical-align:top;
        }
    </style>

    <!--<p style="font-weight:bold"><a href="/compliance_programs?id=203">How would I have scored in 2011 with the new 2012 criteria?</a></p>-->

    <!--<p><?php echo sprintf('Hello %s,', $user->getFullName()) ?></p>-->
    <p>Hello, and welcome to the FTE automotive Wellness Website! </p>
    <p>In addition to being a great resource for health-related topics, this site allows you to review the results from
        your 2012 wellness screening. </p>
    <p>When you complete your health screening in the fall of 2012, your results will be updated, and you will be able
        to determine
        if you qualify for the premium discount. </p>
    <p>You must complete the following steps in the fall of 2012 to receive your premium incentive of up to $70.00 per
        month ($840.00 per year) beginning 1/1/2013.


    </p>

    <p>Step 1 - Your participation in the screening and completion of the on-line HRA - $20.00 credit per month.
        Regardless of your health status, you achieve this credit simply by participating. </p>
    <p>Step 2 - If you achieve 7 or more points - you will earn an additional $50.00 credit per month</p>
    <p>A reasonable alternative will be provided to people who cannot reach the health standards as a result of a
        medical condition or for
        whom achievement of the standard is medically inadvisable. </p>


    <p><a href="/resources/3950/Pregnancyexceptionform2012.pdf">Are you pregnant or 6 months postpartum?</a></p>

    <p>The current requirements and your current status for each are summarized below.</p>
    <?php
    }
}