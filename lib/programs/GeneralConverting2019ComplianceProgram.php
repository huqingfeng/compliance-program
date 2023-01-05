<?php

class GeneralConverting2019ComplianceProgram extends ComplianceProgram
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
        return new GeneralConverting2019ComplianceProgramReportPrinter();
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $coreGroup = new ComplianceViewGroup('core', 'Program');

        $hraScrView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScrView->setReportName('Health Power Assessment & Wellness Screening');
        $hraScrView->setName('hra_screening');
        $hraScrView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(700, 0, 0, 0));
        $hraScrView->setAttribute('report_name_link', '/content/##1ahpa');
        $hraScrView->emptyLinks();
        $hraScrView->addLink(new Link('Do HPA', '/content/989'));
        $hraScrView->addLink(new Link('Sign-Up', '/content/ucan_scheduling'));
        $coreGroup->addComplianceView($hraScrView);

        $this->addComplianceViewGroup($coreGroup);

        $biometricGroup = new ComplianceViewGroup('biometric', 'Biometric Compliance');

        $hdlRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlRatioView->setReportName('3. HDL/total cholesterol ratio');
        $hdlRatioView->setName('hdl');
        $hdlRatioView->overrideTestRowData(null, null, 4.999, null, 'M');
        $hdlRatioView->overrideTestRowData(null, null, 4.999, null, 'F');
        $hdlRatioView->emptyLinks();
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $hdlRatioView->setAttribute('goal', '< 5');
        $biometricGroup->addComplianceView($hdlRatioView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('4. LDL cholesterol');
        $ldlView->setName('ldl');
        $ldlView->overrideTestRowData(0, 0, 100, null);
        $ldlView->setAttribute('goal', '≤ 99');
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $biometricGroup->addComplianceView($ldlView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('5. Triglycerides');
        $trigView->setName('triglycerides');
        $trigView->overrideTestRowData(null, null, 151, null);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0 ,0));
        $trigView->setAttribute('goal', '≤ 150');
        $biometricGroup->addComplianceView($trigView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('5. Glucose');
        $gluView->setName('glucose');
        $gluView->overrideTestRowData(null, null, 100, null);
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $gluView->setAttribute('goal', '≤ 99');
        $biometricGroup->addComplianceView($gluView);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd, true);
        $bmiView->setReportName('7. Body Mass Index');
        $bmiView->setName('bmi');
        $bmiView->overrideTestRowData(null, null, 29, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $bmiView->setAttribute('goal', '≤ 28');
        $biometricGroup->addComplianceView($bmiView);

        $this->addComplianceViewGroup($biometricGroup);

        $tobaccoGroup = new ComplianceViewGroup('tobacco_free', '8. Tobacco Free');
        $tobaccoGroup->setPointsRequiredForCompliance(100);
        $tobaccoGroup->setMaximumNumberOfPoints(100);

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('a. Negative (low/no) exposure to nicotine;');
        $cotinineView->setName('cotinine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative');
        $tobaccoGroup->addComplianceView($cotinineView);

        $tobaccoElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $tobaccoElearningView->setName('elearning_tobacco');
        $tobaccoElearningView->setReportName('b. Complete 5 tobacco-related e-learning lessons');
        $tobaccoElearningView->setAttribute('goal', '5');
        $tobaccoElearningView->setNumberRequired(5);
        $tobaccoElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
        $tobaccoGroup->addComplianceView($tobaccoElearningView);

        $this->addComplianceViewGroup($tobaccoGroup);

        $extraGroup = new ComplianceViewGroup('extra_group', 'Extra Group');

        $shareResults = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 1693, 150);
        $shareResults->setReportName('9. Share Results of Wellness Screening with Your Doctor *');
        $shareResults->setMaximumNumberOfPoints(150);
        $shareResults->setAttribute('link', '/content/12048?action=showActivity&activityidentifier=1693');
        $shareResults->setName('share_results');
        $extraGroup->addComplianceView($shareResults);

        $getScreening = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 26, 150);
        $getScreening->setReportName('10. Get Recommended Preventive Screenings/Exams *');
        $getScreening->setName('get_screening');
        $getScreening->setMaximumNumberOfPoints(150);
        $getScreening->setAttribute('link', '/content/12048?action=showActivity&activityidentifier=26');
        $extraGroup->addComplianceView($getScreening);

        $elearning = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearning->setReportName('11. Complete e-Learning Lessons (75 points/lesson)');
        $elearning->setName('elearning');
        $elearning->setAttribute('link', '/content/9420?action=lessonManager&tab_alias=all_lessons');
        $elearning->setPointsPerLesson(75);
        $elearning->setMaximumNumberOfPoints(300);
        $elearning->emptyLinks();
        $extraGroup->addComplianceView($elearning);

        $this->addComplianceViewGroup($extraGroup);

        $B1Reward = new ComplianceViewGroup('health_action_reward', 'Health Action Reward');
        $B2Reward = new ComplianceViewGroup('tobacco_free_reward', 'Tobacco Free Reward');

        $this->addComplianceViewGroup($B1Reward);
        $this->addComplianceViewGroup($B2Reward);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $tobaccoFree = $status->getComplianceViewGroupStatus('tobacco_free');
        $b1Reward = $status->getComplianceViewGroupStatus('health_action_reward');
        $b2Reward = $status->getComplianceViewGroupStatus('tobacco_free_reward');

        $points = $status->getPoints();

        $b2Reward->setStatus($tobaccoFree->getStatus());

        if($points >= 1000){
            $status->setStatus(ComplianceStatus::COMPLIANT);
            $status->setAttribute("goal", "Goal Met!");
            $b1Reward->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            $status->setAttribute("goal", "Not Met Yet");
            $b1Reward->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

    }
}

class GeneralConverting2019ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
            return;
        }

        ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
        <style type="text/css">
            .bu {
                font-weight: 600;
                text-decoration: underline;
            }

            #content {
                padding: 2em 0 8em 0;
            }

            .headerRow {
                background-color:#639aff;
                font-weight:bold;
                font-size:10pt;
                height:46px;
                color: white;
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
                margin: auto;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                margin:0.1in 0;
            }

            #results th, td {
                padding:0.01in 0.05in;
                border:0.01in solid #000;
                text-align:center;
                padding: 1px;
            }

            #not_compliant_notes p{
                margin: 3px 0;
            }

            #root_note {
                background-color: #ccffcc;
                margin-top: 20px;
            }

            .underline {
                text-decoration: underline;
            }

            .subItem {
                padding-left:10px;
                display: inline-block;
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
                <div style="float: left; width:40%">
                    <img src="https://master.hpn.com/resources/10085/ehs logo.png"  />
                <p style="font-size: 1rem; margin-top: 10px;font-weight: 600;">
                    4205 Westbrook Dr<br>
                    Aurora, IL 60504
                </p>
                </div>

                <div style="float: right; background: #bfbfbf; padding: 20px;">
                    <img src="https://master.hpn.com/resources/10098/GeneralConvertingLogo.png" style="border-radius: 10px; height:80px;"  />
                </div>
            </p>

            <div style="color: #2B1EF5; font-weight: 600; clear: both; overflow-x: visible; width: 250px; margin: auto;">
                <?= $user->first_name . " " . $user->last_name ?> <br>
                <?= $user->address_line_1 ?><br>
                <?= $user->city . ", " . $user->state . ", " . $user->zip_code ?>
            </div>

            <div style="clear: both">
                <div style="width: 100%; margin-top: 10px; display: block">
                    <p style="font-size: 1rem; font-weight: 600;">
                        <span style="float:left;">Dear <span style="color: #2B1EF5;"><?php echo $user->first_name ?>:</span></span>
                        <span style="float:right; color: #2B1EF5;"><?php echo date("m/d/Y") ?></span>
                    </p>
                    <div style="margin-bottom: 10px; clear: both;"></div>
                    <p>
                        General Converting is committed to supporting the health and wellbeing of its employees and their
                        families.  The first step in promoting good health is awareness.  That is why we offer the annual
                        wellness screening through Empower Health Services.
                    </p>

                    <p>To encourage wellbeing, the use of this benefit and other related actions, General Converting offers
                        2 reward discounts on the medical benefit premium contribution^ that each employee/family can earn
                        as noted below:
                    </p>

                    <ol type="A">
                        <li><strong>Health Action Reward:</strong> Earn 1,000 points out of the 1,900 possible points by 8/15/19. <strong>Discount = $100/month</strong>.</li>
                        <li><strong>Tobacco Free Reward:</strong> Test negative or complete 5 online tobacco cessation lessons by 8/15/19. <strong>Discount = $100/month</strong>.</li>
                    </ol>

                    <p><span class="underline">^ Important:</span> The incentive requirements apply to <span class="underline">each</span> eligible employee <span class="underline">and</span> spouse with medical benefits
                                through General Converting.  With employee/spouse coverage or employee/full family coverage, <strong class="underline">both</strong> the
                        employee AND spouse must meet all applicable criteria for the family to receive incentives A & B (above).</p>
                </div>

            </div>
            <div style="clear: both"></div>

            <?php echo $this->getTable($status) ?>

            <div id="not_compliant_notes">
                <div style="width: 56%; float: left">
                    <p>
                        Login to <a href="https://empowerhealthservices.hpn.com/">https://empowerhealthservices.hpn.com/</a> any time to:
                        <ul>
                            <li>View all of your screening results and links in the report; AND</li>
                            <li>Access powerful tools and resources for optimizing your health, care and wellbeing.</li>
                        </ul>

                        <p>
                            Thank you for getting your wellness screening done this year. This and many of your other actions
                            reflect how you value your own wellbeing and the wellbeing of others at home and work.
                        </p>

                        <p style="margin-top: 10px;">
                            Best Regards,<br>
                            Empower Health Services
                        </p>
                    </p>
                </div>

                <div style="width: 40%; padding: 10px; float: right; background-color: #DAEEF3;">
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
                            <li>Cholesterol, body metrics, blood sugars, women's health, men's health and over 40 other learning centers.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div style="clear: both;"></div>

        </div>

        <?php
    }

    private function getTable($status)
    {
        $user = $status->getUser();

        $hraScreening = $status->getComplianceViewStatus('hra_screening');
        $hraScreeningView = $hraScreening->getComplianceView();

        $hdl = $status->getComplianceViewStatus('hdl');
        $hdlView = $hdl->getComplianceView();

        $ldl = $status->getComplianceViewStatus('ldl');
        $ldlView = $ldl->getComplianceView();

        $triglycerides = $status->getComplianceViewStatus('triglycerides');
        $triglyceridesView = $triglycerides->getComplianceView();

        $glucose = $status->getComplianceViewStatus('glucose');
        $glucoseView = $glucose->getComplianceView();

        $bmi = $status->getComplianceViewStatus('bmi');
        $bmiView = $bmi->getComplianceView();

        $tobaccoGroup = $status->getComplianceViewGroupStatus('tobacco_free');
        $tobaccoGroupView = $tobaccoGroup->getComplianceViewGroup();

        $cotinine = $status->getComplianceViewStatus('cotinine');
        $cotinineView = $cotinine->getComplianceView();

        $elearningTobacco = $status->getComplianceViewStatus('elearning_tobacco');
        $elearningTobaccoView = $elearningTobacco->getComplianceView();

        $tobaccoPoints = $cotinine->getPoints()+$elearningTobacco->getPoints();
        if ($tobaccoPoints > 100) $tobaccoPoints = 100;

        $shareResults = $status->getComplianceViewStatus('share_results');
        $shareResultsView = $shareResults->getComplianceView();

        $getScreening = $status->getComplianceViewStatus('get_screening');
        $getScreeningView = $getScreening->getComplianceView();

        $elearning = $status->getComplianceViewStatus('elearning');
        $elearningView = $elearning->getComplianceView();

        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr>
                        <th colspan="6" style="text-align: left;"><?= $user->first_name . " " . $user->last_name ?></th>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left; width: 320px;" colspan="2">A. Earn 1,000 or more points by 8/15/2019 in any of the ways below:</th>
                        <th style="min-width:100px;">Date Done</th>
                        <th># Points Possible</th>
                        <th># Points Earned</th>
                        <th>Action Link</th>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="2">1. Complete the Wellness Screening</td>
                        <td><?= $hraScreening->getAttribute('screening_comment')?></td>
                        <td rowspan="2"><?= $hraScreeningView->getMaximumNumberOfPoints(); ?></td>
                        <td rowspan="2"><?= $hraScreening->getPoints(); ?></td>
                        <td><a href="/content/1051?action=appointmentList&filter[type]=">Sign-Up</a> <a href="/content/989">Results</a></td>
                    </tr>

                    <tr>
                        <td style="text-align: left;" colspan="2">2. Complete the Empower Health Assessment</td>
                        <td><?= $hraScreening->getAttribute('hra_comment')?></td>
                        <td><a href="/content/989">Complete EHA</a> <a href="/content/989">Results</a></td>
                    </tr>

                    <tr>
                        <th><em style="font-weight: 400;">Points for screening results in row #s 3-8 below.</em></th>
                        <th class="headerRow">Goal for 100 points</th>
                        <th class="headerRow">My Result</th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;"><?= $hdlView->getReportName()?></td>
                        <td><?= $hdlView->getAttribute('goal')?></td>
                        <td><?= $hdl->getComment()?></td>
                        <td><?= $hdlView->getMaximumNumberOfPoints()?></td>
                        <td><?= $hdl->getPoints()?></td>
                        <td rowspan="5"><a href="/content/989">Click for all screening results.</a></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;"><?= $ldlView->getReportName()?></td>
                        <td><?= $ldlView->getAttribute('goal')?></td>
                        <td><?= $ldl->getComment()?></td>
                        <td><?= $ldlView->getMaximumNumberOfPoints()?></td>
                        <td><?= $ldl->getPoints()?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;"><?= $triglyceridesView->getReportName()?></td>
                        <td><?= $triglyceridesView->getAttribute('goal')?></td>
                        <td><?= $triglycerides->getComment()?></td>
                        <td><?= $triglyceridesView->getMaximumNumberOfPoints()?></td>
                        <td><?= $triglycerides->getPoints()?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;"><?= $glucoseView->getReportName()?></td>
                        <td><?= $glucoseView->getAttribute('goal')?></td>
                        <td><?= $glucose->getComment()?></td>
                        <td><?= $glucoseView->getMaximumNumberOfPoints()?></td>
                        <td><?= $glucose->getPoints()?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;"><?= $bmiView->getReportName()?></td>
                        <td><?= $bmiView->getAttribute('goal')?></td>
                        <td><?= $bmi->getComment()?></td>
                        <td><?= $bmiView->getMaximumNumberOfPoints()?></td>
                        <td><?= $bmi->getPoints()?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;">
                            <?= $tobaccoGroupView->getReportName()?><br>
                            <span class="subItem"><?= $cotinineView->getReportName()?></span>
                            <div style="text-align: center">OR</div>
                            <span class="subItem"><?= $elearningTobaccoView->getReportName()?></span>
                        </td>
                        <td>
                            <?= $cotinineView->getAttribute('goal')?><br>
                            <?= $elearningTobaccoView->getAttribute('goal')?>
                        </td>
                        <td>
                            <?= ($cotinine->getComment()) ?: "N/A"?><br>
                            <?= count($elearningTobacco->getAttribute("lessons_completed"))?>
                        </td>
                        <td><?= $tobaccoGroupView->getMaximumNumberOfPoints()?></td>
                        <td><?= $tobaccoPoints ?></td>
                        <td><?php foreach ($elearningTobaccoView->getLinks() as $link){$link->setLinkText("Review/Do Lessons");echo $link->getHTML() . " ";}?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;" colspan="2"><?= $shareResultsView->getReportName()?></td>
                        <td><?= $shareResultsView->latestRecordDate($user)?></td>
                        <td><?= $shareResultsView->getMaximumNumberOfPoints()?></td>
                        <td><?= $shareResults->getPoints()?></td>
                        <td><a href="<?= $shareResultsView->getAttribute('link')?>">Enter or Update Info *</a></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;" colspan="2"><?= $getScreeningView->getReportName()?></td>
                        <td><?= $getScreeningView->latestRecordDate($user)?></td>
                        <td><?= $getScreeningView->getMaximumNumberOfPoints()?></td>
                        <td><?= $getScreening->getPoints()?></td>
                        <td><a href="<?= $getScreeningView->getAttribute('link')?>">Enter or Update Info *</a></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;" colspan="2"><?= $elearningView->getReportName()?></td>
                        <td><?= count($elearning->getAttribute("lessons_completed"))?></td>
                        <td><?= $elearningView->getMaximumNumberOfPoints()?></td>
                        <td><?= $elearning->getPoints()?></td>
                        <td><a href="<?= $elearningView->getAttribute('link')?>">Review/Do Lessons</a></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;" colspan="3">
                            <div style="text-align: right; font-weight: 600;">12. Total points possible and earned as of: <?= date("m-d-Y") ?> <i class="fa fa-arrow-right"></i></div>
                            <em style="color: red;">*  #9 and #10 can be done anytime from 1/1/19 to 8/15/19 for points – just use the related
                                action link to enter the date done, Dr. name, address, & other info required.</em>
                        </td>
                        <td>1,900</td>
                        <td><?= $status->getPoints()?></td>
                        <td><?= $status->getAttribute("goal")?></td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left; width: 320px;" colspan="3">B. Reward Goals & Status:</th>
                        <th>Reward Goal</th>
                        <th># Points Earned</th>
                        <th>Reward Goal Status</th>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;" colspan="3">
                            <div style="text-align: right;">1. Health Action Reward - points from row #12 above <i class="fa fa-arrow-right"></i></div>
                        </td>
                        <td>1,000</td>
                        <td><?= $status->getPoints()?></td>
                        <td><?= $status->getAttribute("goal")?></td>
                    </tr>

                    <tr>
                        <td style="text-align: left; width: 320px;" colspan="3">
                            <div style="text-align: right;">2. Tobacco Free Reward - points from row #8 <i class="fa fa-arrow-right"></i></div>
                        </td>
                        <td>100</td>
                        <td><?= $tobaccoPoints?></td>
                        <td><?= ($tobaccoPoints >= 100) ? "Goal Met!" : "Not Met Yet"?></td>
                    </tr>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}