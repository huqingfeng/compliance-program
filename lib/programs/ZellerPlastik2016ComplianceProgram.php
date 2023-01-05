<?php

class ZellerPlastik2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->addEndStatusFieldCallBack('Compliance Program - Compliant', function(ComplianceProgramStatus $status) {
            $numberCompliant = 0;
            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $view = $viewStatus->getComplianceView();

                    if($view->getAttribute('screening_view')) {
                        if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                            // Alternative wasn't executed, so original_status is null. View still compliant

                            $numberCompliant++;
                        }
                    }
                }
            }

            if($numberCompliant >= 3) {
                return 'Yes';
            } else {
                return 'No';
            }
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new ZellerPlastik2016ComplianceProgramReportPrinter();
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
        $group->setPointsRequiredForCompliance(5);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('BMI');
        $bmiView->overrideTestRowData(0, 0, 29.9, null);
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 29.9');
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('screening_view', true);
        $group->addComplianceView($bmiView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('Fasting Glucose');
        $gluView->overrideTestRowData(0, 0, 99.99, null);
        $gluView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 99');
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $group->addComplianceView($gluView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setReportName('Total Cholesterol');
        $cholesterolView->overrideTestRowData(0, 0, 200, null);
        $cholesterolView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 200');
        $cholesterolView->emptyLinks();
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cholesterolView->setAttribute('screening_view', true);
        $group->addComplianceView($cholesterolView);

        $hdlRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlRatioView->setReportName('Total/HDL Ratio');
        $hdlRatioView->overrideTestRowData(0, 0, 5, null);
        $hdlRatioView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 5.0');
        $hdlRatioView->emptyLinks();
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlRatioView->setAttribute('screening_view', true);
        $group->addComplianceView($hdlRatioView);

        $triView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triView->overrideTestRowData(null, null, 149, null);
        $triView->setStatusSummary(ComplianceStatus::COMPLIANT, '0 - 149');
        $triView->emptyLinks();
        $triView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triView->setAttribute('screening_view', true);
        $group->addComplianceView($triView);

        $this->addComplianceViewGroup($group);
    }
}

class ZellerPlastik2016ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                width:7.6in;
                margin:0 0.5in;
            }

            #results th, td {
                padding:0.01in 0.05in;
                border:0.01in solid #000;
                text-align:center;
                padding: 1px;
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
                background-color:#DEDEDE;
            }


        </style>

        <style type="text/css" media="print">
            body {
                margin:0.5in;
                padding:0;
            }
        </style>

        <div class="letter">

            <p style="text-align:center;font-size:18pt;font-weight:bold;">Healthy Measures</p>

            <p style="margin-top:0.5in; margin-left:0.75in;">
                <br/> <br/> <br/> <br/> <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>Dear <?php echo $user->first_name ?>,</p>

            <p>Thank you for participating in the 2016 Wellness Screening. In partnership with Health Maintenance
            Institute, Zeller has selected five "Healthy Measures" for you to strive to achieve.</p>

            <p>As communicated, a medical premium discount will be offered to those employees who achieve at least
            3 out of the 5 “Healthy Measures” below. If you do not meet the requirements, you may still qualify
            for the incentive by having your doctor complete and sign the Alternate Qualification Form and fax it
             to Health Maintenance Institute at 847-635-0038. If you do not successfully complete either of these
             options, you will not be eligible for the employee medical premium discount this year.</p>

            <p>Your screening results are below. Your overall achievement is based on the total results passed. Your
             screening test results will remain confidential while your overall achievement (pass or fail) will be
              forwarded to Zeller Plastik.</p>

            <?php echo $this->getTable($status) ?>

            <?php if($this->getNumCompliant($status) >= 3) : ?>
                <p class="bund">RESULTS:</p>

                <p>CONGRATULATIONS! Based on your results of the 2016 wellness screening, you <span class="bund">DO</span> fall
                within the acceptable range for at least three “Healthy Measures” and therefore, qualify for the
                medical premium discount. No further action is required on your part.</p>

                <p>If you have any questions, please contact allison@hmihealth.com or call 847-635-6580.</p>

            <?php else : ?>
                <div id="not_compliant_notes">
                    <p class="bund">RESULTS:</p>

                    <p>Based on your results of the 2016 wellness screening, you <span class="bund">DO NOT</span>
                    fall within the acceptable range for at least three "Healthy Measures" and therefore, do not
                    qualify for the medical premium discount. Please see "Alternate Process" steps below in order
                    to still try and qualify for the discount. </p>

                    <p class="bund">ALTERNATE PROCESS:</p>

                    <p>In order to receive the discount, we <strong>must</strong> have documentation from your Physician acknowledging the "Healthy
                    Measure" areas of concern. This form (located on the back) will need to be completed by your physician, and returned
                    to Health Maintenance Institute. If your completed form is not received by the deadline, you will not qualify for the
                    discount. </p>

                    <p>If you have any questions, please contact allison@hmihealth.com or call 847-635-6580. </p>

                    <p>
                        Fax Number: 847-635-0038 <br />
                        Mailing Address: Health Maintenance Institute 2604 E. Dempster Street,
                        Suite 301, Park Ridge, IL 60068. Attn: Allison Osoba
                    </p>
                </div>
            <?php endif ?>

            <p>&nbsp;</p>


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
                        <th>Health Measure</th>
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
                                <?php if($viewStatus->getComplianceView()->getName() != 'cotinine') : ?>
                                <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                                <?php endif ?>
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


    /**
     * To show the pass table, a user has to be compliant for the program, and be compliant
     * without considering elearning lessons.
     */
    private function getNumCompliant($status)
    {
        $numberCompliant = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();

                if($view->getAttribute('screening_view')) {
                    if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                        // Alternative wasn't executed, so original_status is null. View still compliant

                        $numberCompliant++;
                    }
                }
            }
        }

        return $numberCompliant;
    }

}