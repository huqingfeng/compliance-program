<?php

class SEKOLogistics2020ComplianceProgram extends ComplianceProgram
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
        return new SEKOLogistics2020ComplianceProgramReportPrinter();
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

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('1. Participate in the Biometric Screening');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('View Full Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('2. Complete the Health Risk Assessment');
        $hraView->setName('complete_hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $coreGroup->addComplianceView($hraView);


        $this->addComplianceViewGroup($coreGroup);

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(2);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. Body Mass Index of 27.0 or less -or- alternative *');
        $bmiView->overrideTestRowData(null, null, 27, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($bmiView, $programStart, $programEnd, 'weight_management');
        $group->addComplianceView($bmiView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('2. Triglycerides of 150 or less -or- alternative *');
        $trigView->overrideTestRowData(null, null, 150, null);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0 ,0));
        $trigView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($trigView, $programStart, $programEnd, 'cholesterol');
        $group->addComplianceView($trigView);

        $hdlRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlRatioView->setReportName('3. Cholesterol Ratio of 5.0 or less -or- alternative *');
        $hdlRatioView->overrideTestRowData(null, null, 5, null, 'M');
        $hdlRatioView->overrideTestRowData(null, null, 5, null, 'F');
        $hdlRatioView->emptyLinks();
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlRatioView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($hdlRatioView, $programStart, $programEnd, 'cholesterol');
        $group->addComplianceView($hdlRatioView);

        $this->addComplianceViewGroup($group);
    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $startDate, $endDate, $alias)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($startDate, $endDate, $alias) {
            $view = $status->getComplianceView();

            $elearningView = new CompleteELearningGroupSet($startDate, $endDate, $alias);
            $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
            $elearningView->setName('alternative_'.$view->getName());
            $elearningView->useAlternateCode(true);
            $elearningView->setNumberRequired(3);

            $elearningStatus = $elearningView->getStatus($user);

            $status->setAttribute('lessons_completed', $elearningStatus->getAttribute('lessons_completed', array()));

            if(($status->getComment() != ''  && $status->getComment() != 'QNS' && $status->getComment() != 'Declined' && $status->getComment() != 'Test Not Taken')
                && ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT || $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT)
                && $elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            $status->getComplianceView()->addLink(new Link('Complete Lessons', "/content/9420?action=lessonManager&tab_alias={$alias}"));

            $status->setAttribute('alternate_status_object', $elearningStatus);
        });

    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        if($coreGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $healthGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT){
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
    }
}

class SEKOLogistics2020ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                clear: both;
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
                    <img src="/images/empower/seko_logo.jpg" style="height:88px;"  />
                </div>
            </p>



            <p style="margin-left:0.75in; padding-top:.56in; clear: both;">
                <br/> <br/>
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <p>&nbsp;</p>
            <p>&nbsp;</p>

            <h4 style="text-align: center">2020 Wellness Incentive Program</h4>

            <p>
                <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
            </p>


            <p>
                Welcome to the 2020 Wellness Incentive Program.  Earning the financial incentive is based on completing
                the Health Risk Assessment and the biometric screening -AND- meeting 2 out of 3 three biometric health
                goals listed below.
            </p>

            <p>
                If your results do not reach the health goal, the two reasonable alternative options offered are:
            </p>

            <p>
                <ul>
                    <li>Complete at least 3 e-Learning lessons for each goal you did not meet.  Lessons must be completed by 10/31/2020; OR</li>
                    <li>Follow-up with your own physician to have him or her complete the Alternate Qualification Form.  Please fax the completed form to Empower Health at 630.882.0448 no later than 10/31/2020.  </li>
                </ul>
            </p>

            <?php echo $this->getTable($status) ?>

            <p style="text-align: center; font-weight: bold;">
                Should you have any questions or need additional assistance, <br />please contact Empower Health by calling 866.367.6974
            </p>
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
                        <th style="text-align: left; width: 300px;">A. ACTION ITEMS - Deadline 9/30/2020</th>
                        <th>Date Done</th>
                        <th colspan="2">Completed</th>
                        <th>Links</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('core')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td colspan="2"><img src="<?php echo $viewStatus->getLight(); ?>" class="light"/></td>
                            <td>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left;">B. BIOMETRIC HEALTH GOALS <<br /> – meet 2 or more goals below</th>
                        <th>Your Results</th>
                        <th>Lessons Completed</th>
                        <th>Goal Met /Not Met</th>
                        <th>Links</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td style="font-size: 9pt;">
                                <?php echo count($viewStatus->getAttribute('lessons_completed')); ?>
                            </td>
                            <td><img src="<?php echo $viewStatus->getLight(); ?>" class="light"/></td>
                            <td>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th colspan="5" style="text-align: left; padding-left: 20px;">*  REASONABLE ALTERNATIVE OPTIONS – Deadline 10/31/2020</th>
                    </tr>

                    <tr>
                        <td colspan="4">Complete 3 e-Learning lessons per goal not met  – OR –</td>
                        <td>See Links Above</td>
                    </tr>

                    <tr>
                        <td colspan="4">Visit primary care physician to complete and submit Alternate Qualification Form</td>
                        <td><a href="/resources/10530/2020_SEKO Logistics_AQF_010520.pdf">Download Form</a></td>
                    </tr>

                    <tr class="headerRow">
                        <th colspan="3" style="text-align: left; padding-left: 20px;">INCENTIVE STATUS</th>
                        <th colspan="2" style="text-align: center;">Earned/Not Earned</th>
                    </tr>

                    <tr>
                        <th colspan="3">A1 & A2 completed -AND- 2 or more goals met in B</th>
                        <td><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
                        <td style="font-weight: bold; color: <?php echo $status->getStatus() == ComplianceStatus::COMPLIANT ? 'green' : 'red'  ?>">
                            <?php echo $status->getStatus() == ComplianceStatus::COMPLIANT ? 'Earned' : 'Not Earned'  ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}