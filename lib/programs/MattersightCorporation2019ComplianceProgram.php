<?php

class MattersightCorporation2019ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MattersightCorporation2019ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $coreGroup = new ComplianceViewGroup('core', 'Program');
//        $coreGroup->setPointsRequiredForCompliance(50);

        $hraScreeningEndDate = '2019-10-04';

        $screeningView = new CompleteScreeningComplianceView($programStart, $hraScreeningEndDate);
        $screeningView->setReportName('1. Complete the wellness screening');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->setAttribute('goal', '10/04/19');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $hraScreeningEndDate);
        $hraView->setReportName('2. Complete the EHA (optional)');
        $hraView->setName('complete_hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setAttribute('goal', '10/04/19');
        $coreGroup->addComplianceView($hraView);


        $this->addComplianceViewGroup($coreGroup);

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(4);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. BMI');
        $bmiView->overrideTestRowData(null, 18.5, 24.999, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '18.5 - 24.9');
        $bmiView->setAttribute('screening_view', true);
        $group->addComplianceView($bmiView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('2. LDL Cholesterol');
        $ldlView->overrideTestRowData(null, null, 99.999, null);
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->setAttribute('goal', '< 100');
        $ldlView->setAttribute('screening_view', true);
        $group->addComplianceView($ldlView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('3. HDL Cholesterol');
        $hdlView->overrideTestRowData(null, 40, 100, null);
        $hdlView->emptyLinks();
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->setAttribute('goal', '40 - 100');
        $hdlView->setAttribute('screening_view', true);
        $group->addComplianceView($hdlView);

        $hdlRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlRatioView->setReportName('4. Total Chol/HDL ratio');
        $hdlRatioView->overrideTestRowData(null, null, 4.999, null, 'M');
        $hdlRatioView->overrideTestRowData(null, null, 4.999, null, 'F');
        $hdlRatioView->emptyLinks();
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlRatioView->setAttribute('goal', '< 5.0');
        $hdlRatioView->setAttribute('screening_view', true);
        $group->addComplianceView($hdlRatioView);


        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('5. Glucose, Fasting');
        $gluView->overrideTestRowData(null, null, 99, null);
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $gluView->setAttribute('goal', '≤ 99');
        $group->addComplianceView($gluView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('6. Triglycerides');
        $trigView->overrideTestRowData(null, null, 149, null);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0 ,0));
        $trigView->setAttribute('goal', '≤ 149');
        $trigView->setAttribute('screening_view', true);
        $group->addComplianceView($trigView);

        $biometricsView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $biometricsView->setReportName('7. Total # above that are in the goal range');
        $biometricsView->setName('biometrics');
        $biometricsView->setAttribute('goal', '4 or more measures above are in the goal range');
        $group->addComplianceView($biometricsView);

        $this->addComplianceViewGroup($group);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $biometricStatus = $status->getComplianceViewStatus('biometrics');

        $numCompliant = 0;
        foreach($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getComplianceView()->getName() == 'biometrics') continue;

            if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numCompliant++;
        }

        if($numCompliant >= 4) {
            $biometricStatus->setStatus(ComplianceViewStatus::COMPLIANT);
        }
        $biometricStatus->setComment($numCompliant);

        $screeeningViewStatus = $status->getComplianceViewStatus('complete_screening');
        $biometricStatus = $status->getComplianceViewStatus('biometrics');
        if($screeeningViewStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $biometricStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT){
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
    }
}

class MattersightCorporation2019ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css">
            .headerRow {
                background-color:#88b2f6;
                font-weight:bold;
                font-size:10pt;
                height:46px;
            }

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
                margin: 3px 0;
            }

        </style>

        <style type="text/css" media="print">
            body {
                margin:0.5in;
                padding:0;
            }
        </style>

        <div class="letter">
            <p style="clear: both;">
                <div style="float: left;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4205 Westbrook Drive<br />
                    Aurora, IL 60504
                </div>

                <div style="float: right;">
                    <img src="/images/empower/mattersight_logo.jpg" style="height:36px;"  />
                </div>
            </p>

            <p style="margin-left:0.75in; padding-top:.56in; clear: both;">
                <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <p>
                <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
            </p>


            <p>Mattersight Corporation is committed to encourage and help support the health, care and wellbeing
                of everyone at work and their families – in part, through the wellness screenings, incentives
                and other resources offered as able each year.</p>

            <p>Your participating in a wellness screening is one of many actions you take that can benefit you
                throughout life.</p>

            <p>Earning the incentive this year involves completing a wellness screening and getting certain results in
                a goal range. These requirements, your results and incentive status are in the table below.  When you
                meet the required goals for the incentive, your name will be on a list that is sent to Mattersight
                Corporation for everyone earning the incentive.  Note about privacy:  As always, individual screening
                results are not shared with employers and remain confidential.</p>

            <?php echo $this->getTable($status) ?>

            <div id="not_compliant_notes">
                <div style="width: 56%; float: left">
                    <p>
                        Login to <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> any time to:
                        <ul>
                            <li>View all of your screening results and links in the report;  AND</li>
                            <li>Access powerful tools and resources for optimizing your health, care and wellbeing.</li>
                        </ul>

                        <p>
                            Thank you for getting your wellness screening done this year. This and many of your other
                            actions reflect how you value your own wellbeing and the wellbeing of others at home
                            and work.
                        </p>

                        Best Regards,<br />
                        Empower Health Services
                    </p>
                </div>

                <div style="width: 43%; float: right; background-color: #cceeff;">
                    <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Some of these online tools include:</div>
                    <div style="font-size: 9pt;">
                        <ul>
                            <li>Over 1,000 e-lessons</li>
                            <li>
                                The Healthwise® Knowledgebase for decisions about medical tests, medicines, other
                                treatments, risks and other topics
                            </li>
                            <li>Over 500 videos</li>
                            <li>Decision tools for over 170 elective care decisions</li>
                            <li>Cholesterol, body metrics, blood sugars, women’s health, men’s health and over 40 other learning centers.</li>
                        </ul>
                    </div>
                </div>
            </div>

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
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 260px;">A. Incentive Actions</th>
                        <th>Date Done</th>
                        <th>Goal Deadline</th>
                        <th>Goal Met</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('core')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td><?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left;">B. Incentive Measures</th>
                        <th>My Result</th>
                        <th>Goal Range</th>
                        <th>Goal Met</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td>
                                <?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left;">C. Incentive Requirement Summary</th>
                        <th colspan="3">My Incentive Status</th>
                    </tr>

                    <tr>
                        <th rowspan="2">Meet the Goals (Get a Yes) for A1 and B7 <br /> to receive the incentive.</th>
                        <?php if($status->getStatus() == ComplianceStatus::COMPLIANT) : ?>
                            <td colspan="3" style="background-color: #90FF8C">
                                <p>
                                    <strong>Congratulations!</strong> You met the goals to receive the incentive. No further action is required!
                                </p>
                            </td>
                         <?php else : ?>
                            <td colspan="3" style="background-color: #ffb3b3">
                                <p>
                                    <strong>Sorry!</strong>  You did NOT meet the goals required to receive the incentive.
                                    <strong>However</strong>, you may still earn the incentive by completing the
                                    <a href="/resources/10367/2019_Mattersight_Corporation_AQF_070819.pdf" target="_blank">Alternate Qualification Form</a>
                                    and sending it to Empower Health Services
                                </p>
                                <p>
                                    by fax at 630-385-0156 or mail
                                </p>
                                <strong>NO LATER THAN October 25, 2019</strong>
                            </td>
                         <?php endif ?>
                    </tr>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}