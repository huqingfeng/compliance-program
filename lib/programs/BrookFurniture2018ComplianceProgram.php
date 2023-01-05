<?php
use hpn\steel\query\SelectQuery;

class BrookFurniture2018ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowCompliant(false, null, null);

        $printer->addEndStatusFieldCallBack('Compliance Program - Compliant', function(ComplianceProgramStatus $status) {
            $cotinineCompliant = $status->getComplianceViewStatus('cotinine')->getStatus() == ComplianceViewStatus::COMPLIANT;
            $numberCompliant = $status->getComplianceViewGroupStatus("healthy_measures")->getPoints();

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
        return new BrookFurniture2018ComplianceProgramReportPrinter();
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
        $group->setPointsRequiredForCompliance(3);

        $hdlView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('Cholesterol/HDL Ratio');
        $hdlView->overrideTestRowData(null, null, 4.9, null);
        $hdlView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 4.9');
        $hdlView->emptyLinks();
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->setAttribute('screening_view', true);

        $group->addComplianceView($hdlView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->overrideTestRowData(null, null, 129, null);
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 129');
        $ldlView->setAttribute('screening_view', true);
        $group->addComplianceView($ldlView);

        $triView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triView->overrideTestRowData(null, null, 149, null);
        $triView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 149');
        $triView->emptyLinks();
        $triView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triView->setAttribute('screening_view', true);
        $group->addComplianceView($triView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('Blood Glucose');
        $gluView->overrideTestRowData(null, null, 99, null);
        $gluView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 99');
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $group->addComplianceView($gluView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('BMI');
        $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 30');
        $bmiView->overrideTestRowData(null, null, 30, null );
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('screening_view', true);
        $bmiView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){

            if ($status->getComment() == "No Screening" || $status->getComment() == "Test Not Taken") {
                $hra = SelectQuery::create()
                    ->select('height_text as height, weight_text as weight')
                    ->from('hra')
                    ->where('user_id = ?', array($user->id))
                    ->andWhere('date BETWEEN ? and ?', array(date("Y-m-d", $this->getStartDate()), date("Y-m-d", $this->getEndDate())))
                    ->limit(1)
                    ->execute()->toArray();

                $hra = $hra[0];
                
                if (is_numeric($hra["weight"]) && is_numeric($hra["height"])) {
                    $bmi = number_format(($hra["weight"] / ($hra["height"]*$hra["height"])) * 703, 2);

                    $status->setComment($bmi);

                    if ($bmi <= 30) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    } else {
                        $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                    }
                }
            }
        });
        $group->addComplianceView($bmiView);

        $this->addComplianceViewGroup($group);

        $groupContinine = new ComplianceViewGroup('cotinine_group', 'Cotinine Group');

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('Cotinine');
        $cotinineView->setName('cotinine');
        $cotinineView->setStatusSummary(ComplianceStatus::COMPLIANT, '');
        $cotinineView->emptyLinks();
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('screening_view', true);
        $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setComment('Pass');
            } else {
                $status->setComment('Fail');
            }
        });
        $groupContinine->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($groupContinine);
    }
}

class BrookFurniture2018ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        $cotinineCompliant = $status->getComplianceViewStatus('cotinine')->getStatus() == ComplianceViewStatus::COMPLIANT;

        $core_points = $status->getComplianceViewGroupStatus("healthy_measures")->getPoints();

        ?>
        <style type="text/css">
            .bund {
                font-weight:bold;
            }

            .red_text {
                color: red;
                font-weight: normal;
                font-style: italic;
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

            <p>Dear <?php echo $user->first_name . " " . $user->last_name ?>,</p>

            <p>Thank you for participating in the Wellness Screening. In partnership with Health Maintenance Institute,
                Brook Furniture Rental has selected six “Health Standards” for you to strive to achieve.</p>

            <p>Team members that <strong>pass the Cotinine Test AND meet 3 of the 5 Health Standards outlined below will be rewarded the Wellness Healthy Discount</strong> and earn the maximum discount available for their 2019 medical premiums.</p>

            <p>If you did not meet these standards, you will have the option of completing the <strong>ALTERNATE PROCESS</strong> outlined in the section below. <strong>Team members that satisfy all the designated ALTERNATE PROCESS requirements will also earn the Wellness Healthy Discount</strong> upon completion of the requirements.</p>

            <?php echo $this->getTable($status) ?>

            <?php if($core_points >= 3 && $cotinineCompliant) : ?>
                <p class="bund">RESULTS:</p>

                <p>CONGRATULATIONS! You have earned the <strong>Healthy Wellness Discount for 2019</strong>. Based on your wellness screening results,
                    you passed the Cotinine Test and met at least 3 of the required Health Standards.</p>

                <p>No further action is required on your part. </p>

            <?php elseif($core_points >= 3 && !$cotinineCompliant) : ?>

                <p class="bund">RESULTS:</p>

                <p>Based on your wellness screening results, you met at least 3 of the 5 Health Standards but <strong>did NOT Pass
                    the Cotinine Test</strong>. You may still earn the <strong>Wellness Healthy Discount</strong> by
                    completing the <strong>ALTERNATE PROCESS</strong> requirements.</p>

                <p class="bund">ALTERNATE PROCESS <span class="red_text">(HMI must receive proof of completion by March 30, 2019)</span>:</p>

                <p>To earn the <strong>Wellness Healthy Discount</strong>, you must complete an approved tobacco cessation
                    program and provide proof of completion to HMI by March 30, 2019. <strong>Contact 1-800-QUIT NOW</strong>
                    to be connected directly to your state’s tobacco Quitline, a FREE telephone-based tobacco cessation service.</p>

                <p>If you are unable to, or it is medically unadvisable for you to meet this requirement, please contact HMI at (847) 635-6580.</p>

            <?php elseif($core_points < 3 && $cotinineCompliant) : ?>

                <p class="bund">RESULTS:</p>

                <p>Based on your wellness screening results, you passed the Cotinine test, but <strong>DID NOT meet at least 3 of the 5
                    Health Standards</strong>. You may still earn the <strong>Wellness Healthy Discount</strong> by completing the <strong>ALTERNATE PROCESS</strong> requirements.</p>

                <p class="bund">ALTERNATE PROCESS <span class="red_text">(HMI must receive completed form by November 16, 2018)</span>:</p>

                <p>To earn the <strong>Wellness Healthy Discount</strong>, Health Maintenance Institute (HMI) <strong>must</strong> have documentation from
                    your Physician acknowledging the "Health Standard" areas of concern. Please follow-up with your Physician
                    and submit the <strong>Alternate Qualification Form</strong> included in your results packet, <span style="color: red;">complete with your Physician's
                    signature</span>. If your completed form is not received by HMI on or before <strong>November 16, 2018</strong>, you will not qualify
                    for the <strong>Wellness Healthy Discount.</strong></p>

                <p>If you are unable to meet this requirement, please contact HMI at (847) 635-6580.</p>

            <?php else : ?>
                <div id="not_compliant_notes">
                    <p class="bund">RESULTS:</p>

                    <p>Based on your wellness screening results, you <strong>DID NOT meet at least 3 of the 5 Health Standards AND</strong> you
                        <strong>FAILED the Cotinine test</strong>. You may still earn the <strong>Wellness Healthy Discount</strong> by completing <strong>BOTH ALTERNATE PROCESS</strong> requirements.</p>

                    <p class="bund">ALTERNATE PROCESS:</p>

                    <p><strong>1.</strong> <span class="red_text">Complete Alternate Qualification Form & submit to HMI by November 16, 2018:</span></p>

                    <p>To earn the <strong>Wellness Healthy Discount</strong>, Health Maintenance Institute (HMI) <strong>must</strong> have documentation from
                        your Physician acknowledging the "Health Standard" areas of concern. Please follow-up with your Physician
                        and submit the <strong>Alternate Qualification Form</strong> included in your results packet, <span style="color: red;">complete with your Physician's
                    signature</span>. If your completed form is not received by HMI on or before <strong>November 16, 2018</strong>, you will not qualify
                        for the <strong>Wellness Healthy Discount.</strong></p>

                    <p><strong>2.</strong> <span class="red_text">Complete approved Tobacco Cessation Program before March 30, 2019:</span></p>

                    <p>You must complete an approved tobacco cessation program and provide proof of completion to HMI by March 30, 2019.
                        <strong>Contact 1-800-QUIT NOW</strong> to be connected directly to your state’s tobacco Quitline, a FREE telephone-based tobacco cessation service.</p>

                    <p>If you are unable to, or it is medically unadvisable for you to meet this requirement, please contact HMI at (847) 635-6580.</p>

                    <p>Team Members will receive the Wellness Healthy Discount once BOTH requirements are completed and verified.
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
                                <img src="<?php echo $viewStatus->getLight() ?>" class="light" />
                                <?php echo $viewStatus->getComment() ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <?php foreach($status->getComplianceViewGroupStatus('cotinine_group')->getComplianceViewStatuses() as $viewStatus) : ?>
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