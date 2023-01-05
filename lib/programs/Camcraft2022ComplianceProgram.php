<?php

class Camcraft2022ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->addEndStatusFieldCallBack('Compliance Program - Compliant', function(ComplianceProgramStatus $status) {
            $cotinineCompliant = $status->getComplianceViewStatus('cotinine')->getStatus() == ComplianceViewStatus::COMPLIANT;
            $numberCompliant = 0;
            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $view = $viewStatus->getComplianceView();

                    if($view->getName() == 'cotinine') continue;

                    if($view->getAttribute('screening_view')) {
                        if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                            // Alternative wasn't executed, so original_status is null. View still compliant

                            $numberCompliant++;
                        }
                    }
                }
            }

            if($numberCompliant >= 2 && $cotinineCompliant) {
                return 'Yes';
            } else {
                return 'No';
            }
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Camcraft2022ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(5);

        $bpView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bpView->overrideSystolicTestRowData(null, null, 139.999, null);
        $bpView->overrideDiastolicTestRowData(null, null, 89.999, null);
        $bpView->emptyLinks();
        $bpView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=blood_pressure'));
        $bpView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bpView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Both numbers less than 140/90');
        $bpView->setAttribute('screening_view', true);
        $group->addComplianceView($bpView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('HDL');
        $hdlView->overrideTestRowData(null, 36, null, null);
        $hdlView->setStatusSummary(ComplianceStatus::COMPLIANT, '≥ 36');
        $hdlView->emptyLinks();
        $hdlView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->setAttribute('screening_view', true);

        $group->addComplianceView($hdlView);

        $triView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triView->overrideTestRowData(null, null, 174.999, null);
        $triView->setStatusSummary(ComplianceStatus::COMPLIANT, '<175');
        $triView->emptyLinks();
        $triView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=cholesterol'));
        $triView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triView->setAttribute('screening_view', true);
        $group->addComplianceView($triView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('Blood Glucose');
        $gluView->overrideTestRowData(null, null, 100, null);
        $gluView->emptyLinks();
        $gluView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $group->addComplianceView($gluView);

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('Cotinine');
        $cotinineView->setName('cotinine');
        $cotinineView->setStatusSummary(ComplianceStatus::COMPLIANT, '');
        $cotinineView->overrideTestRowData(null, null, 8, null);
        $cotinineView->emptyLinks();
        $cotinineView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('screening_view', true);
        $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setComment('Pass');
            } else {
                $status->setComment('Fail');
            }
        });
        $group->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($group);
    }
}

class Camcraft2022ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        $cotinineCompliant = $status->getComplianceViewStatus('cotinine')->getStatus() == ComplianceViewStatus::COMPLIANT;

        ?>
        <style type="text/css">
            .bund {
                font-weight:bold;
                text-decoration:underline;
            }

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

            #not_compliant_notes p{
                margin: 5px 0;
            }

        </style>

        <style type="text/css" media="print">
            body {
                margin:0.5in;
                padding:0;
            }
        </style>

        <div class="letter">

            <p style="text-align:center;font-size:18pt;font-weight:bold;">Health Assessment</p>

            <p style="margin-top:0.5in; margin-left:0.75in;">
                <?php if (sfConfig::get('app_wms2')) : ?>
                  <br/> <br/> <br/>
                <?php endif ?>
                <br/> <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>Dear <?php echo $user->first_name ?>,</p>

            <p>Thank you for participating in the Wellness Screening. In partnership with Health Maintenance Institute,
                <?php echo $user->getClient()->getName() ?> has selected five “Health Standards” for you to strive to achieve.</p>

            <p>As communicated, a medical premium discount will be offered for those employees (and spouses) who fall
                within the acceptable range for at least <span class="bund">two</span> out of four "Health Standards"
                in addition to cotinine. If you (or your spouse) do not fall within the acceptable range for at least two
                measures, you have the option of the “Alternate Process” in order to still receive the discount. If you do
                not successfully complete either of these options, you will not be eligible for the employee medical
                premium discount this year.</p>

            <?php echo $this->getTable($status) ?>

            <?php if($this->getNumCompliant($status) >= 2 && $cotinineCompliant) : ?>
                <p class="bund">RESULTS:</p>

                <p>CONGRATULATIONS! Based on your results of the wellness screening, you <span class="bund">DO</span>
                 fall within the acceptable rage for at least two "Health Standards" and cotinine and therefore, qualify
                  for the medical premium discount. No further action is required on your part.</p>

            <?php elseif($this->getNumCompliant($status) >= 2 && !$cotinineCompliant) : ?>

                <p class="bund">RESULTS:</p>

                <p>Based on your results of the wellness screening, you do fall within the acceptable range for at least
                    two "Health Standards". However, your cotinine result was not in the acceptable range. It is required
                  for you to enroll in American Lung Association's Smoking Cessation Program <span class="bund">
                    within 30 days of your screening date</span>.</p>

                <p style="color:red;"><span class="bund">*IMPORTANT*</span> If you have a spouse who is covered under our
                    medical plan, they will need to individually complete the criteria themselves, in order for you to
                    receive the medical premium discount.</p>

                <p>If you have any questions, please contact support@hmihealth.com or call 847-635-6580.</p>

            <?php else : ?>
                <div id="not_compliant_notes">
                    <p class="bund">RESULTS:</p>

                    <p>Based on your results of the wellness screening, you <span class="bund">DO NOT</span> fall
                     within the acceptable range for at least two "Health Standards" and there fore, do not qualify for
                     the medical premium discount. Please see "Alternate Process" steps below in order to still try
                     and qualify for the discount.</p>

                    <p class="bund">ALTERNATE PROCESS:</p>

                    <p>In order to receive the discount, we must have documentation from your Physician acknowledging the
                     "Health Standard" areas of concern. This <a href="https://static.hpn.com/pdf/clients/camcraft/Camcraft_Matrix_2022_AQF.pdf" target="_blank">
                     Alternative Qualification Form</a> will need to be completed by your physician and returned to Health
                      Maintenance Institute. If your completed form is not received <span class="bund">within 30 days
                      of your screening date</span>, you will not qualify for the discount</p>

                    <p>Specifically for those who did not fall within the acceptable range for the cotinine test, it is
                     required for you to enroll in American Lung Association's Smoking Cessation Program
                        <span class="bund">within 30 days of your screening date</span> and complete thereafter. </p>

                    <p style="color:red;"><span class="bund">*IMPORTANT*</span> If you have a spouse who is covered under
                        our medical plan, they will need to individually complete the criteria themselves, in order for
                        you to receive the medical premium discount.</p>

                    <p>
                        Upload completed form to ehsupload.com <br />
                        Fax completed form to 630.385.0156; Attn: Individual Program <br />
                        Mail completed form to: HMI Individual Program/AQF , 4205 Westbrook Drive, Aurora, IL 60504
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
                        <th>Health Standard</th>
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

                if($view->getName() == 'cotinine') continue;

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
