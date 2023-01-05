<?php

class CamcraftLearningAlternativeComplianceView extends ComplianceView
{
    public function __construct($programStart, $programEnd, $alias)
    {
        $this->start = $programStart;
        $this->end = $programEnd;
        $this->alias = $alias;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'camcraft_alt_'.$this->alias;
    }

    public function getDefaultReportName()
    {
        return 'Complete eLearning '.$this->alias;
    }

    public function getStatus(User $user)
    {
        $screeningView = new CompleteScreeningComplianceView($this->start, $this->end);
        $screeningView->setComplianceViewGroup($this->getComplianceViewGroup());

        if($screeningView->getStatus($user)->isCompliant()) {
            $elearningView = new CompleteELearningGroupSet($this->start, $this->end, $this->alias);
            $elearningView->setComplianceViewGroup($this->getComplianceViewGroup());
            $elearningView->setNumberRequired(1);

            if($elearningView->getStatus($user)->isCompliant()) {
                return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, 'Elearning Lesson Completed');
            }
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
    }

    protected $alias;
    protected $start;
    protected $end;
}

class Camcraft2018ComplianceProgram extends ComplianceProgram
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

                    if($view->getAttribute('screening_view')) {
                        if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) {
                            // Alternative wasn't executed, so original_status is null. View still compliant

                            $numberCompliant++;
                        }
                    }
                }
            }

            if($numberCompliant >= 3 && $cotinineCompliant) {
                return 'Yes';
            } else {
                return 'No';
            }
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Camcraft2018ComplianceProgramReportPrinter();
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
        $bpView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        });
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

        $waistView = new ComplyWithWaistScreeningTestComplianceView($programStart, $programEnd);
        $waistView->setReportName('Waist Circumference');
        $waistView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Men ≤ 40 <br />Women ≤ 35');
        $waistView->overrideTestRowData(null, null, 40, null, 'M');
        $waistView->overrideTestRowData(null, null, 35, null, 'F');
        $waistView->emptyLinks();
        $waistView->addLink(new link('Complete 1 lesson', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $waistView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $waistView->setAttribute('screening_view', true);
        $group->addComplianceView($waistView);

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

class Camcraft2018ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                <br/> <br/> <br/> <br/> <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>Dear <?php echo $user->first_name ?>,</p>

            <p>Thank you for participating in the Wellness Screening. In partnership with Health Maintenance Institute,
                <?php echo $user->getClient()->getName() ?> has selected six “Health Standards” for you to strive to achieve.</p>

            <p>As communicated, a medical premium discount will be offered for those employees (and spouses) who fall
                within the acceptable range for at least <span class="bund">three</span> out of five "Health Standards"
                in addition to cotinine. If you (or your spouse) do not fall within the acceptable range for at least three
                measures, you have the option of the “Alternate Process” in order to still receive the discount. If you do
                not successfully complete either of these options, you will not be eligible for the employee medical
                premium discount this year.</p>

            <?php echo $this->getTable($status) ?>

            <?php if($this->getNumCompliant($status) >= 3 && $cotinineCompliant) : ?>
                <p class="bund">RESULTS:</p>

                <p>CONGRATULATIONS! Based on your results of the wellness screening, you <span class="bund">DO</span> fall
                    within the acceptable rage for at least three "Health Standards" and therefore, qualify for the medical
                    premium discount. No further action is required on your part.</p>

                <p style="color:red;"><span class="bund">*IMPORTANT*</span> If you have a spouse who is covered under our medical plan, they will need to individually
                    complete the criteria themselves, in order for you to receive the medical premium discount.</p>

                <p>If you have any questions, please contact allison@hmihealth.com or call 847-635-6580.</p>

            <?php elseif($this->getNumCompliant($status) >= 3 && !$cotinineCompliant) : ?>

                <p class="bund">RESULTS:</p>

                <p>Based on your results of the wellness screening, you do fall within the acceptable range for at least
                    three “Health Standards”. However, your cotinine result was not in the acceptable range. It is
                    <span class="bund">required</span> for you to enroll in American Lung Association's Smoking Cessation
                    Program by November 20, 2018 and complete thereafter.</p>

                <p style="color:red;"><span class="bund">*IMPORTANT*</span> If you have a spouse who is covered under our
                    medical plan, they will need to individually complete the criteria themselves, in order for you to
                    receive the medical premium discount.</p>

                <p>If you have any questions, please contact allison@hmihealth.com or call 847-635-6580.</p>

            <?php else : ?>
                <div id="not_compliant_notes">
                    <p class="bund">RESULTS:</p>

                    <p>Based on your results of the wellness screening, you <span class="bund">DO NOT</span> fall within
                        the acceptable range for at least three "Health Standards" and there fore, do not qualify for the
                        medical premium discount. Please see "Alternate Process" steps below in order to still try and
                        qualify for the discount.</p>

                    <p class="bund">ALTERNATE PROCESS:</p>

                    <p>In order to receive the discount, we <strong>must</strong> have documentation from your Physician
                        acknowledging the "Health Standard" areas of concern. Please return the form, which will be
                        included in your results packet, to Health Maintenance Institute, complete with your Physician's
                        signature. If your completed form is not receive by November 20, 2018, you will not qualify for the
                        discount.</p>

                    <p>Specifically for those who did not fall within the acceptable range for the cotinine test, it is
                        <span class="bund">required</span> for you to enroll in American Lung Association's Smoking
                        Cessation Program by November 20, 2018 and complete thereafter.</p>

                    <p style="color:red;"><span class="bund">*IMPORTANT*</span> If you have a spouse who is covered under
                        our medical plan, they will need to individually complete the criteria themselves, in order for
                        you to receive the medical premium discount.</p>

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