<?php

class RiverViewFarms2018ComplianceProgram extends ComplianceProgram
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
        return new RiverViewFarms2018ComplianceProgramReportPrinter();
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
        $coreGroup->setPointsRequiredForCompliance(1);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete the wellness screening');
        $screeningView->setName('screening');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $screeningView->setAttribute('deadline', '08/17/2018');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('View Full Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

        $tobaccoGroup = new ComplianceViewGroup('tobacco', 'Tobacco');
        $tobaccoGroup->setPointsRequiredForCompliance(1);

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('1. Test Negative for Cotinine');
        $cotinineView->setName('cotinine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative');
        $tobaccoGroup->addComplianceView($cotinineView);

        $tobaccoAlternative = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $tobaccoAlternative->setReportName('2. OR - Confirm you are working with your doctor on these goals. Just complete the <a href="/resources/10103/River_View_Farms_AQF_061918.pdf">Alternative Qualification Form</a> then fax or mail to EHS by deadline.');
        $tobaccoAlternative->setName('tobacco_alternative');
        $tobaccoAlternative->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoAlternative->setAttribute('goal', 'Fax or mail completed form by 8/22/18 -> <a href="/resources/10103/River_View_Farms_AQF_061918.pdf" download>Download Form</a>');
        $tobaccoGroup->addComplianceView($tobaccoAlternative);

        $this->addComplianceViewGroup($tobaccoGroup);

        $healthGroup = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $healthGroup->setPointsRequiredForCompliance(3);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. BMI');
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->overrideTestRowData(null, null, 29.999, null);
        $bmiView->setAttribute('goal', '< 30');
        $healthGroup->addComplianceView($bmiView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('2. HDL Cholesterol');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->overrideTestRowData(null, 40.001, null, null);
        $hdlView->setAttribute('goal', '>40');
        $healthGroup->addComplianceView($hdlView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('3. LDL Cholesterol');
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->overrideTestRowData(null, null, 129.99, null);
        $ldlView->setAttribute('goal', '<130');
        $healthGroup->addComplianceView($ldlView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglyceridesView->setReportName('4. Triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triglyceridesView->overrideTestRowData(null, null, 159.999, null);
        $triglyceridesView->setAttribute('goal', '<160');
        $healthGroup->addComplianceView($triglyceridesView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('5. Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $glucoseView->overrideTestRowData(null, null, 109.999, null);
        $glucoseView->setAttribute('goal', '<110');
        $healthGroup->addComplianceView($glucoseView);

        $this->addComplianceViewGroup($healthGroup);


        $alternativeGroup = new ComplianceViewGroup('alternative', 'alternative');
        $alternativeGroup->setPointsRequiredForCompliance(1);

        $alternativeMeetDrView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $alternativeMeetDrView->setReportName('6. OR - Confirm you are working with your doctor on these goals. Just complete the <a href="/resources/10103/River_View_Farms_AQF_061918.pdf">Alternative Qualification Form</a> then fax or mail to EHS by deadline.');
        $alternativeMeetDrView->setName('alternative_meet_dr');
        $alternativeMeetDrView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeMeetDrView->setAttribute('goal', 'Fax or mail completed form by 8/22/18 -> <a href="/resources/10103/River_View_Farms_AQF_061918.pdf" download>Download Form</a>');
        $alternativeGroup->addComplianceView($alternativeMeetDrView);

        $this->addComplianceViewGroup($alternativeGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);
    }
}

class RiverViewFarms2018ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();
        $client = $user->getClient();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <style type="text/css">
            .headerRow {
                background-color:#639aff;
                font-weight:bold;
                font-size:9pt;
                height:20px;
            }

            .bund {
                font-weight:bold;
                text-decoration:underline;
            }

            <?php if (!sfConfig::get('app_wms2')) : ?>
            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                width:9.5in;
                height:11in;
                margin-left: 20px;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                width:8.4in;
                font-size: 9pt;
                margin: 0 auto;
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

            #text-area-top div{
                font-size: 10pt;
            }

            #text-area-bottom div{
                font-size: 10px;
            }

        </style>

        <style type="text/css" media="print">
            body {
                margin:0.2in;
                padding:0;
            }

            .letter {
                font-family:Arial, sans-serif;
                font-size:11pt;
                width:8.5in;
                height:11in;
            }
        </style>

        <div class="letter">
            <p style="clear: both;">
                <div style="float: left;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4255 Westbrook Drive #223<br />
                    Aurora, IL 60504
                </div>

                <div style="float: right;">
                    <img src="/images/empower/riverviewfarms_logo_2018.jpg" style="height:80px;"  />
                </div>
            </p>

            <p style="margin-left:3in; clear: both;">
                <?php echo $user->getFullName() ?> <br/>
                <?php echo $user->getFullAddress("<br/>") ?>
            </p>

            <div id="text-area-top">
                <div>
                    <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                    <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
                </div>


                <p><strong><?php echo $client->getName() ?></strong> is committed to supporting the health and wellbeing of its employees
                 and their families.  The first step in promoting good health is awareness. That is why we offer the annual
                  wellness screening through Empower Health Services (EHS).  </p>

                <div>
                    To encourage wellbeing, the use of this benefit and other related actions, River View Farms offers 2
                     per paycheck rewards with both totaling up to $260 over 12 months, as follows: <br />
                     <ul style="list-style-type: upper-alpha">
                        <li><strong>Health Action Reward:</strong> Get $5 per pay reward (up to $130/year) by meeting 3 of 5 screening result goals noted below.</li>
                        <li><strong>Tobacco Free Reward:</strong> And, get $5 per pay reward (up to $130/year) by having negative for the cotinine result.</li>
                    </ul>
                </div>

                <div>
                    Your employer is committed to helping you achieve your best health.  Rewards for participating in a
                    wellness program are available to all employees.  If you were unable to meet a standard for a reward
                    under this wellness program based on your biometric testing results or your current health condition
                    precludes you from participating in the program, you may use the Alternate Qualification Form link
                    under section B. #6.  Download and print this form.  Then work with your doctor to fill out and return
                    to EHS to receive your rewards points.  The EHS fax # and address are on the form.
                </div>
            </div>
            
            <?php echo $this->getTable($status) ?>

            <div id="text-area-bottom">
                <div style="width: 56%; float: left">
                    <div>
                        Login to <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> any time to:
                        <ul>
                            <li>View all of your screening results and links in the report;  AND</li>
                            <li>Access powerful tools and resources for optimizing your health, care and wellbeing.</li>
                        </ul>

                        <div>
                            Thank you for getting your wellness screening done this year. This and many of your other
                            actions reflect how you value your own wellbeing and the wellbeing of others at home
                            and work.
                        </div>

                        Best Regards,<br />
                        Empower Health Services
                    </div>
                </div>

                <div style="width: 43%; float: right; background-color: #cceeff;">
                    <div style="font-weight: bold; text-align: center; margin-bottom: 1px;">Some of these online tools include:</div>
                    <div>
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


        </div>

        <?php
    }

    private function getTable($status)
    {

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $screeningStatus = $status->getComplianceViewStatus('screening');

        $tobaccoGroupStatus = $status->getComplianceViewGroupStatus('tobacco');
        $cotinineStatus = $status->getComplianceViewStatus('cotinine');
        $alternativeStatus = $status->getComplianceViewStatus('tobacco_alternative');

        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $alternativeGroupStatus = $status->getComplianceViewGroupStatus('alternative');
        $alternativeMeetDrToddStatus = $status->getComplianceViewStatus('alternative_meet_dr');


        $measureAndAlternativeStatus = ComplianceStatus::NOT_COMPLIANT;
        if($healthyGroupStatus->getStatus() == ComplianceStatus::COMPLIANT || $alternativeGroupStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $measureAndAlternativeStatus = ComplianceStatus::COMPLIANT;
        }


        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: center; width: 320px;">Get Started!  ...for Rewards A&B below</th>
                        <th>Deadline</th>
                        <th>Date Done</th>
                        <th colspan="2">Action Link</th>
                    </tr>

                    <tr class="status-<?php echo $screeningStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $screeningStatus->getComplianceView()->getAttribute('deadline') ?></td>
                        <td><?php echo $screeningStatus->getComment() ?></td>
                        <td colspan="2" class="links text-center">
                            <?php foreach($screeningStatus->getComplianceView()->getLinks() as $link) : ?>
                                <div><?php echo $link->getHTML() ?></div>
                            <?php endforeach ?>
                        </td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left; width: 320px;">A. Tobacco Free Reward – Meet one of the tobacco goals (#1 or #2) below by 8/22/18.</th>
                        <th>Goal</th>
                        <th>Result</th>
                        <th>Met Goal</th>
                        <th style="width: 80px;">Incentive Req. Met</th>
                    </tr>

                    <tr class="status-<?php echo $cotinineStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $cotinineStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $cotinineStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $cotinineStatus->getComment() ?></td>
                        <td>
                            <?php if($cotinineStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td rowspan="2" class="status-<?php echo $tobaccoGroupStatus->getStatus() ?>" style="<?php echo $tobaccoGroupStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT ? 'background-color:  #ff6666' : '' ?>">
                            <?php echo $tobaccoGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'Not Yet' ?>
                        </td>
                    </tr>


                    <tr class="status-<?php echo $alternativeStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $alternativeStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="2"><?php echo $alternativeStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $alternativeStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left;">B.  Healthy Measures Incentive - Meet the goal for 3 or more of your results below (B1-5) –or– the alternative B6 if needed.</th>
                        <th style="width: 150px;">Goal</th>
                        <th>Result</th>
                        <th>Met Goal</th>
                        <th></th>
                    </tr>

                    <?php $i = 0; ?>
                    <?php foreach($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td>
                                <?php if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>
                            </td>
                            <?php if($i == 0 ) : ?>
                            <td rowspan="9" class="status-<?php echo $measureAndAlternativeStatus ?>" style="<?php echo $measureAndAlternativeStatus == ComplianceStatus::NOT_COMPLIANT ? 'background-color:  #ff6666' : '' ?>">
                                <?php if($measureAndAlternativeStatus == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    Not Yet
                                <?php endif ?>
                            </td>

                            <?php endif; $i++ ?>
                        </tr>
                    <?php endforeach ?>


                    <tr class="headerRow">
                        <th style="text-align: left;">Alternative for B1-5, if less than 3 goals are met:</th>
                        <th colspan="2"></th>
                        <th></th>
                    </tr>

                    <tr class="status-<?php echo $alternativeMeetDrToddStatus->getStatus() ?>">
                        <td style="text-align: left;" >
                           <?php echo $alternativeMeetDrToddStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td colspan="2"><?php echo $alternativeMeetDrToddStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td>
                            <div style="margin-top: 20px;">
                                <?php if($alternativeMeetDrToddStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>

                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}