<?php

class Wheaton2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Wheaton2013ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $group = new ComplianceViewGroup('healthy_measures', 'Healthy Measures');
        $group->setNumberOfViewsRequired(3);

        $bpView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bpView->overrideSystolicTestRowData(null, null, 130, null);
        $bpView->overrideDiastolicTestRowData(null, null, 90, null);
        $group->addComplianceView($bpView);

        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setStatusSummary(ComplianceStatus::COMPLIANT, '<200');
        $group->addComplianceView($tcView);

        $tcHdlView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $tcHdlView->overrideTestRowData(null, null, 4.96999, null);
        $tcHdlView->setStatusSummary(ComplianceStatus::COMPLIANT, '<4.97');
        $group->addComplianceView($tcHdlView);

        $triView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triView->overrideTestRowData(null, null, 149.999, null);
        $triView->setStatusSummary(ComplianceStatus::COMPLIANT, '<150');
        $group->addComplianceView($triView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->overrideTestRowData(null, null, 99, null);
        $group->addComplianceView($gluView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->overrideTestRowData(null, null, 29.999, null);
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '<30');
        $group->addComplianceView($bmiView);

        $this->addComplianceViewGroup($group);
    }
}

class Wheaton2013ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css">
            .bund {
                font-weight:bold;
                text-decoration:underline;
            }

            <?php if (!sfConfig::get('app_wms2')) : ?>
            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                width:8.5in;
                height:11in;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                width:5.5in;
                margin:0 1.5in;
            }

            #results th, td {
                padding:0.01in 0.05in;
                border:0.01in solid #000;
                text-align:center;
            }

            #results th {
                background-color:#FFFFFF;
            }

            #results .status-<?php echo ComplianceStatus::COMPLIANT ?> {
                background-color:#90FF8C;
            }

            #results .status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?> {
                background-color:#F9FF8C;
            }

            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                background-color:#FF948C;
            }

            #results td.your-result {
                text-align:left;
            }
        </style>

        <style type="text/css" media="print">
            body {
                margin:0.5in;
                padding:0;
            }
        </style>

        <div class="letter">
            <p style="font-size:14pt;font-style:italic;">
                <img style="width:0.85in;" src="/resources/4253/apple.png" alt="Apple" />
                Get Well with Wheaton
            </p>

            <p style="text-align:center;font-size:18pt;font-weight:bold;">Wellness Screening</p>

            <p style="margin-top:0.5in;margin-left:0.75in;">
                <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>Dear <?php echo $user->first_name ?>,</p>

            <?php if($user->relationship_type == Relationship::SPOUSE && $status->isCompliant()) : ?>
                <p>Thank you for participating in the 2013 "Get Well with Wheaton" Wellness Screening. In
                partnership with Health Maintenance Institute, Inc, the City of Wheaton has selected six "Healthy
                Measures" from areas where the largest population of City employees (and spouses) tested in
                unhealthy ranges in 2012. The City's goal is to create a culture of health among employees and their
                families while reducing the rate of annual healthcare expenditure growth.</p>

                <p>As communicated in November 2012, a medical premium discount will be offered for the next plan
                year, beginning July 1, 2013, if you and your spouse's results fall within the acceptable range for at least
                <span class="bund">three</span> out of the six "Healthy Measures" shown below. If you and your spouse do not fall within the
                acceptable range for at least three measures, you have the option of the "Alternate Process" in order to still
                receive the discount. If you or your spouse do not successfully pass or complete either of these options,
                your family will not be eligible for the employee medical premium discount this year.</p>

                <?php echo $this->getTable($status) ?>

                <span style="font-weight:bold;"><span style="text-decoration:underline;">RESULTS</span>:</span><br/>

                <p>Based on the results of <span class="bund">your</span> 2013 wellness screening alone, you are eligible to receive the medical
                    premium discount. A reminder that your employed spouse MUST have also participated in the wellness
                    screening, AND met the "Healthy Measures" criteria in order for your family to receive the discount under
                    your shared medical plan. Your name(s) will be provided to the City. The results of this screening are
                    completely confidential and by law, NO personal health information may be shared with the City of
                    Wheaton.</p>

            <?php elseif($user->relationship_type == Relationship::SPOUSE && !$status->isCompliant()) : ?>
                <p>Thank you for participating in the 2013 "Get Well with Wheaton" Wellness Screening. In
                partnership with Health Maintenance Institute, Inc, the City of Wheaton has selected six "Healthy
                Measures" from areas where the largest population of City employees (and spouses) tested in
                unhealthy ranges in 2012. The City's goal is to create a culture of health among employees and their
                families while reducing the rate of annual healthcare expenditure growth.</p>

                <p>As communicated in November 2012, a medical premium discount will be offered for the next plan
                year, beginning July 1, 2013, if you and your spouse's results fall within the acceptable range for at least
                <span class="bund">three</span> out of the six "Healthy Measures" shown below. If you and your spouse do not fall within the
                acceptable range for at least three measures, you have the option of the "Alternate Process" in order to still
                receive the discount. If you or your spouse do not successfully pass or complete either of these options,
                your family will not be eligible for the employee medical premium discount this year.</p>

                <?php echo $this->getTable($status) ?>

                <span style="font-weight:bold;"><span style="text-decoration:underline;">RESULTS</span>:</span><br/>

                <p>Based on your results of the 2013 wellness screening alone, you <span class="bund">DO NOT</span> fall within the acceptable
                    range for at least three "Healthy Measures" and therefore, do not qualify for the medical premium discount.
                    A reminder that your employed spouse MUST have also participated in the wellness screening, AND met
                    the "Healthy Measures" criteria in order for your family to receive the discount under your shared medical
                    plan. Please see "Alternate Process" steps below in order to still try and qualify for the discount.</p>

                <span style="font-weight:bold;"><span style="text-decoration:underline;">ALTERNATE PROCESS</span>:</span><br/>

                <p>In order to receive the discount we <strong>must</strong> have documentation from your Physician indicating
                    that you are currently under care for medical advice and direction relating to the "Healthy Measure"
                    areas of concern. Please return the form, located on the back of this letter, to Health Maintenance
                    Institute, complete with your Physician's signature by <strong>June 10th, 2013</strong>. If your completed form is not
                    received by the deadline, you will not qualify for the discount. <em>Fax number</em>: 847-635-0038. <em>Mailing
                    Address</em>: 2604 E. Dempster St. Suite 301, Park Ridge, IL 60068. Attn: Kathryn Robinson.</p>
            <?php elseif($status->isCompliant()) : ?>
                <p>Thank you for participating in the 2013 "Get Well with Wheaton" Wellness Screening. In
                partnership with Health Maintenance Institute, Inc, the City of Wheaton has selected six "Healthy
                Measures" from areas where the largest population of City employees (and spouses) tested in
                unhealthy ranges in 2012. The City's goal is to create a culture of health among employees and their
                families while reducing the rate of annual healthcare expenditure growth.</p>

                <p>As communicated in November 2012, a medical premium discount will be offered for the next plan
                year, beginning July 1, 2013, to those employees who fall within the acceptable range for at least three out
                of the six "Healthy Measures" shown below. If you do not fall within the acceptable range for at least <span class="bund">three</span>
                measures, you have the option of the "Alternate Process" in order to still receive the discount. If you do
                not successfully complete or pass either of these options, you will not be eligible for the employee medical
                premium discount this year.</p>

                <p>If your spouse is included in our medical plan, he/she must also meet the three out of six "Healthy
                Measures" or complete the "Alternate Process" option for you to receive the discount.</p>

                <?php echo $this->getTable($status) ?>

                <span style="font-weight:bold;"><span style="text-decoration:underline;">RESULTS</span>:</span><br/>

                <p>Based on the results of <span class="bund">your</span> 2013 wellness screening alone, you are eligible to receive the medical
                premium discount. However, if you have a spouse covered by the City's health insurance plan, he/she
                MUST have participated in the wellness screening, AND met the "Healthy Measure" criteria in order for you
                to receive the discount under your shared medical plan. Your name(s) will be provided to the City. The
                results of this screening are completely confidential and by law, NO personal health information may be
                shared with the City of Wheaton.</p>
            <?php else : ?>
                <p>Thank you for participating in the 2013 "Get Well with Wheaton" Wellness Screening. In
                partnership with Health Maintenance Institute, Inc, the City of Wheaton has selected six "Healthy
                Measures" from areas where the largest population of City employees (and spouses) tested in
                unhealthy ranges in 2012. The City's goal is to create a culture of health among employees and their
                families while reducing the rate of annual healthcare expenditure growth.</p>

                <p>As communicated in November 2012, a medical premium discount will be offered for the next plan
                year, beginning July 1, 2013, to those employees who fall within the acceptable range for at least three out
                of the six "Healthy Measures" shown below. If you do not fall within the acceptable range for at least <span class="bund">three</span>
                measures, you have the option of the "Alternate Process" in order to still receive the discount. If you do not
                successfully complete or pass either of these options, you will not be eligible for the employee medical
                premium discount this year.</p>

                <p>If your spouse is included in our medical plan, he/she must also meet the three out of six "Healthy
                Measures" or complete the "Alternate Process" option for you to receive the discount.</p>

                <?php echo $this->getTable($status) ?>

                <span style="font-weight:bold;"><span style="text-decoration:underline;">RESULTS</span>:</span><br/>

                <p>Based on <span class="bund">your</span> results of the 2013 wellness screening alone, you <span class="bund">DO NOT fall</span> within the acceptable
                    range for at least three "Healthy Measures" and therefore, do not qualify for the medical premium discount.
                    Please see "Alternate Process" steps below in order to still try and qualify for the discount.</p>

                <span style="font-weight:bold;"><span style="text-decoration:underline;">ALTERNATE PROCESS</span>:</span><br/>

                <p>In order to receive the discount, we <strong>must</strong> have documentation from your Physician indicating
                    that you are currently under care for medical advice and direction relating to the "Healthy Measure"
                    areas of concern. Please return the form, located on the back of this letter, to Health Maintenance
                    Institute, complete with your Physician's signature by <strong>June 10th, 2013</strong>. If your completed form is not
                    received by the deadline, you will not qualify for the discount. <em>Fax number</em>: 847-635-0038. <em>Mailing
                    Address</em>: 2604 E. Dempster St. Suite 301, Park Ridge, IL 60068. Attn: Kathryn Robinson.</p>
            <?php endif ?>

            <p>&nbsp;</p>

            <p style="text-align:center;font-size:8pt;">Please contact Health Maintenance Institute at 847-635-6580 if you have any questions.</p>

        </div>

        <?php
    }

    private function getTable($status)
    {
        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <thead>
                    <tr>
                        <th>"Healthy Measure"</th>
                        <th>Acceptable Range</th>
                        <th>Your Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT) ?></td>
                            <td class="your-result">
                                <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                                <?php echo $viewStatus->getComment() ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}