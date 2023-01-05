<?php

class VictorEnvelope2020ComplianceProgram extends ComplianceProgram
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
        return new VictorEnvelope2020ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $tobaccoGroup = new ComplianceViewGroup('tobacco', 'Tobacco');
        $tobaccoGroup->setPointsRequiredForCompliance(4);

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('1. Test Negative for Cotinine in Dec 2019');
        $cotinineView->setName('cotinine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative');
        $tobaccoGroup->addComplianceView($cotinineView);

        $initialConsultation = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $initialConsultation->setReportName('a) Initial Consultation with Dr. Todd by 3/3/20 either in person (when offered onsite) or telephonically <br />(IL Onsite Consults: 2/11/20 and AZ Consults: 3/3/20).  Watch for the sign-up for the onsite consultations)');
        $initialConsultation->setName('tobacco_initial_consultation');
        $initialConsultation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoGroup->addComplianceView($initialConsultation);

        $tobaccoElearning = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $tobaccoElearning->setReportName('b) Complete 8 Tobacco Related E-Learning Lessons by 4/1/20 – no more than 1 lesson may be completed per day. Click to <a href="/content/9420?action=lessonManager&tab_alias=tobacco">View/Do Lessons</a>');
        $tobaccoElearning->setName('tobacco_elearning');
        $tobaccoElearning->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoElearning->setNumberRequired(8);
        $tobaccoGroup->addComplianceView($tobaccoElearning);

        $tobaccoFinalConsultation = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $tobaccoFinalConsultation->setReportName('c) Final Consultation with Dr. Todd by 4/1/20 either in person (when offered onsite) or telephonically <br /> (IL Onsite Consults: 3/10/20 and AZ Consults: Telephonically by 4/1/20). Watch for the sign-up for the onsite consultations)');
        $tobaccoFinalConsultation->setName('tobacco_final_consultation');
        $tobaccoFinalConsultation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoGroup->addComplianceView($tobaccoFinalConsultation);

        $this->addComplianceViewGroup($tobaccoGroup);


        $healthGroup = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $healthGroup->setPointsRequiredForCompliance(3);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. BMI');
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->overrideTestRowData(null, null, 27.999, null);
        $bmiView->setAttribute('goal', '< 28 or 2 less from January 2019');
        $bmiView->setPostEvaluateCallback($this->checkImprovement('bmi', 'decrease', 2));
        $healthGroup->addComplianceView($bmiView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('2. HDL Cholesterol');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlView->overrideTestRowData(null, 40, null, null);
        $hdlView->setAttribute('goal', '≥ 40 or 4 more from January 2019');
        $hdlView->setPostEvaluateCallback($this->checkImprovement('hdl', 'increase', 4));
        $healthGroup->addComplianceView($hdlView);

        $hdlRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlRatioView->setReportName('3. Total Chol/HDL ratio');
        $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $hdlRatioView->overrideTestRowData(null, null, 4.999, null);
        $hdlRatioView->setAttribute('goal', '<5.0 or .25 less from January 2019');
        $hdlRatioView->setPostEvaluateCallback($this->checkImprovement('totalhdlratio', 'decrease', 0.25));
        $healthGroup->addComplianceView($hdlRatioView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglyceridesView->setReportName('4. Triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setAttribute('goal', '<150 or 15 less from January 2019');
        $triglyceridesView->setPostEvaluateCallback($this->checkImprovement('triglycerides', 'decrease', 15));
        $healthGroup->addComplianceView($triglyceridesView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('5. Glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setAttribute('goal', '<100 or 10 less from January 2019');
        $glucoseView->setPostEvaluateCallback($this->checkImprovement('glucose', 'decrease', 10));
        $healthGroup->addComplianceView($glucoseView);

        $this->addComplianceViewGroup($healthGroup);


        $alternativeGroup = new ComplianceViewGroup('alternative', 'alternative');
        $alternativeGroup->setPointsRequiredForCompliance(4);

        $alternativeMeetDrToddView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $alternativeMeetDrToddView->setReportName('a) Initial Consultation with Dr. Todd by 3/3/20 either in person (when offered onsite) or telephonically <br /> (IL Onsite Consults: 2/11/20 and AZ Consults: 3/3/20).  Watch for the sign-up for the onsite consultations) ');
        $alternativeMeetDrToddView->setName('alternative_meet_dr_todd');
        $alternativeMeetDrToddView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeGroup->addComplianceView($alternativeMeetDrToddView);

        $attendOnsiteSeminarView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $attendOnsiteSeminarView->setReportName('b) Attend 1 Seminar –  IL (in-person seminars) + AZ (virtual seminars) <br/> Seminar 1: 2/4/20 at 2pm CST and 2:45pm CST OR <br/> Seminar 2: 3/3/20 at 2pm CST and 2:45pm CST');
        $attendOnsiteSeminarView->setName('attend_onsite_seminar');
        $attendOnsiteSeminarView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeGroup->addComplianceView($attendOnsiteSeminarView);

        $alternativeElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'wellbeing_2019');
        $alternativeElearningView->setReportName('c) Complete THREE Activities (Targeted Empower E-Learning Lessons, Additional Seminar, or Telephonic Coaching with Dr. Todd or Karen) by 4/1/20. Click to <a href="/content/9420?action=lessonManager&tab_alias=wellbeing_2019">View/Do Lessons</a>');
        $alternativeElearningView->setName('alternative_elearning');
        $alternativeElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeElearningView->setNumberRequired(3);
        $alternativeGroup->addComplianceView($alternativeElearningView);

        $finalMeetDrToddView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $finalMeetDrToddView->setReportName('d) Final Consultation with Dr. Todd by 4/1/20 either in person (when offered onsite) or telephonically <br /> (IL Onsite Consults: 3/10/20 and AZ Consults: Telephonically by 4/1/20). Watch for the sign-up for the onsite consultations)');
        $finalMeetDrToddView->setName('final_meet_dr_todd');
        $finalMeetDrToddView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $alternativeGroup->addComplianceView($finalMeetDrToddView);


        $this->addComplianceViewGroup($alternativeGroup);
    }

        protected function checkImprovement($test, $calculationMethod = 'decrease', $threshold) {
            $programStart = new \DateTime('@'.$this->getStartDate());
            $programEnd = new \DateTime('@'.$this->getEndDate());

            $lastStart = new \DateTime('2019-01-01');
            $lastEnd = new \DateTime('2019-04-10');

            return function(ComplianceViewStatus $status, User $user) use ($test, $programStart, $programEnd, $lastStart, $lastEnd, $calculationMethod, $threshold) {
                static $cache = null;

                if ($cache === null || $cache['user_id'] != $user->id) {
                    $cache = array(
                        'user_id' => $user->id,
                        'this' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $programStart, $programEnd, array('merge'=> true)),
                        'last' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $lastStart, $lastEnd, array('merge'=> true))
                    );
                }

                if ($cache['this'] || $cache['last']) {
                    $isImproved = false;

                    if($test == 'bmi') {
                        if(isset($cache['last']['height']) && isset($cache['last']['weight']) && $cache['last']['height'] && $cache['last']['weight']) {
                            if($cache['last']['height'] !== null && $cache['last']['weight'] !== null && is_numeric($cache['last']['height']) && is_numeric($cache['last']['weight']) && $cache['last']['height'] > 0) {
                                $bmi = ($cache['last']['weight'] * 703) / ($cache['last']['height'] * $cache['last']['height']);
                            } else {
                                $bmi = null;
                            }

                            $lastVal = round($bmi, 2);
                        } else {
                            $lastVal = isset($cache['last'][$test]) ? (float) $cache['last'][$test] : null;
                        }

                        if(isset($cache['this']['height']) && isset($cache['this']['weight']) && $cache['this']['height'] && $cache['this']['weight']) {
                            if($cache['this']['height'] !== null && $cache['this']['weight'] !== null && is_numeric($cache['this']['height']) && is_numeric($cache['this']['weight']) && $cache['this']['height'] > 0) {
                                $bmi = ($cache['this']['weight'] * 703) / ($cache['this']['height'] * $cache['this']['height']);
                            } else {
                                $bmi = null;
                            }

                            $thisVal = round($bmi, 2);
                        } else {
                            $thisVal = isset($cache['this'][$test]) ? (float) $cache['this'][$test] : null;
                        }

                    } else {
                        $lastVal = isset($cache['last'][$test]) ? (float) $cache['last'][$test] : null;
                        $thisVal = isset($cache['this'][$test]) ? (float) $cache['this'][$test] : null;
                    }

                    if ($thisVal && $lastVal) {
                        $change = $thisVal - $lastVal;
                        $status->setAttribute('2017_2018_change', $change);

                        if($calculationMethod == 'decrease') {
                            if(($change + $threshold) <= 0) {
                                $isImproved = true;
                            }
                        } else {
                            if(($change - $threshold) >= 0) {
                                $isImproved = true;
                            }
                        }

                    }

                    $status->setAttribute('2019_result', $lastVal);
                    $status->setAttribute('2020_result', $thisVal);

                    if ($isImproved) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    }
                }
            };
        }

    public function getLocalActions()
    {
        return array(
            'cotinine_free_options'   => array($this, 'executeCotinineFreeOptions'),
            'enroll_coaching_program'   => array($this, 'executeEnrollCoachingProgram')
        );
    }


    public function executeCotinineFreeOptions(sfActions $actions)
    {
        ?>
        <p class="alert alert-info">
            The Cotinine test is included in the wellness screening for employees offered at Pekin Hospital. <br /><br />

            If interested, spouses can call Human Resources for occupational health clinics where the cotinine test is available.
        </p>

        <?php
    }

    public function executeEnrollCoachingProgram(sfActions $actions)
    {
        ?>
        <p class="alert alert-info">
            Enroll and complete Wellness Coaching Program. <br /><br />

            This is an individualized program designed to meet personal goals. Employees who choose this option will have: <br /><br />

            • Four coaching sessions with Adrienne Southerland (309.353.0204), our Health and Wellness Coordinator; AND<br />
            • Two coaching sessions with Susette Litwiller (309.353.0554), our Employee Health Nurse.<br /><br />

            During these sessions they will review each employee’s results with them and provide an individualized plan to start making healthy lifestyle changes.
        </p>

        <?php
    }


    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $tobaccoGroupStatus = $status->getComplianceViewGroupStatus('tobacco');
        $cotinineStatus = $tobaccoGroupStatus->getComplianceViewStatus('cotinine');
        $tobaccoInitialConsultation = $tobaccoGroupStatus->getComplianceViewStatus('tobacco_initial_consultation');
        $tobaccoElearning = $tobaccoGroupStatus->getComplianceViewStatus('tobacco_elearning');
        $tobaccoFinalConsultation = $tobaccoGroupStatus->getComplianceViewStatus('tobacco_final_consultation');


        if($cotinineStatus->getStatus() == ComplianceStatus::COMPLIANT
            || ($tobaccoInitialConsultation->getStatus() == ComplianceStatus::COMPLIANT
                && $tobaccoElearning->getStatus() == ComplianceStatus::COMPLIANT
                && $tobaccoFinalConsultation->getStatus() == ComplianceStatus::COMPLIANT)) {
            $tobaccoGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        }
    }
}

class VictorEnvelope2020ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                width:8.5in;
                height:11in;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                width:8.4in;
                font-size: 9pt;
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
        </style>

        <div class="letter">
            <p style="clear: both;">
                <div style="float: left;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4205 Westbrook Drive<br />
                    Aurora, IL 60504
                </div>

                <div style="float: right;">
                    <img src="/resources/10508/Logo_A_LiveWellApple_Illinois.jpg" style="height:80px;"  />
                    <img src="/resources/10509/Logo_A LiveWellApple_Arizona.jpg" style="height:85px;"  />
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


                <div>
                    <strong><?php echo $client->getName() ?></strong> is committed to supporting the health & wellbeing
                     of its employees and their families.  The first step in promoting good health is awareness.  That is why
                     Victor Envelope offers the annual health screenings through Empower Health Services and wellness
                     programming with Dr. Todd and Karen Roach.
                </div>

                <div>
                    <ul>
                        <li>The first incentive is the Tobacco Free Discount.</li>
                        <li>
                            The second incentive is the Wellness Discount based on meeting three of the five Health
                            Measures with the health screening or showing a certain level of improvement over your
                            January 2019 results.
                         </li>
                    </ul>
                </div>

                <p>
                    Alternative options are provided below if you did not earn your premium discounts through the health
                    screening. All activities must be completed by April 1, 2020 to earn the premium discounts.
                </p>
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
                            Your employer is committed to helping you achieve your best health. Discounted premiums for participating in a wellness program are available to all employees If you are unable to meet a standard for a discounted premiums under this wellness program based on your biometric testing results or your current health condition precludes you from participating in the program you are provided with an alternate means of earning the reward. If you have additional questions regarding the details of this program, please contact Empower Health Services at 866.367.6974.
                        </div>
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
        $tobaccoStatus = $status->getComplianceViewGroupStatus('tobacco');
        $cotinineStatus = $status->getComplianceViewStatus('cotinine');
        $tobaccoInitialConsultation = $status->getComplianceViewStatus('tobacco_initial_consultation');
        $tobaccoElearning = $status->getComplianceViewStatus('tobacco_elearning');
        $tobaccoFinalConsultation = $status->getComplianceViewStatus('tobacco_final_consultation');

        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $alternativeGroupStatus = $status->getComplianceViewGroupStatus('alternative');
        $alternativeMeetDrToddStatus = $status->getComplianceViewStatus('alternative_meet_dr_todd');
        $attendOnsiteSeminarStatus = $status->getComplianceViewStatus('attend_onsite_seminar');
        $alternativeElearningStatus = $status->getComplianceViewStatus('alternative_elearning');
        $finalMeetDrToddStatus = $status->getComplianceViewStatus('final_meet_dr_todd');


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
                        <th style="text-align: left; width: 320px;">A. Tobacco Free Discount – Meet Goal for A1</th>
                        <th colspan="2">Goal</th>
                        <th colspan="2">Result</th>
                        <th>Met Goal</th>
                        <th style="width: 80px;">Tobacco Free Discount Met</th>
                    </tr>

                    <tr class="status-<?php echo $cotinineStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $cotinineStatus->getComplianceView()->getReportName() ?></td>
                        <td colspan="2"><?php echo $cotinineStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td colspan="2"><?php echo $cotinineStatus->getComment() ?></td>
                        <td>
                            <?php if($cotinineStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                        <td rowspan="5" class="status-<?php echo $tobaccoStatus->getStatus() ?>">
                            <?php echo $tobaccoStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="5" style="text-align: left"><strong>OR Alternative to Earn Tobacco Free Discount –  Complete "a, b, and c" below</strong></td>
                    </tr>

                    <tr class="status-<?php echo $tobaccoInitialConsultation->getStatus() ?>">
                        <td colspan="5" style="text-align: left;"><?php echo $tobaccoInitialConsultation->getComplianceView()->getReportName() ?></td>
                        <td>
                            <?php if($tobaccoInitialConsultation->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $tobaccoElearning->getStatus() ?>">
                        <td colspan="5" style="text-align: left;"><?php echo $tobaccoElearning->getComplianceView()->getReportName() ?></td>
                        <td>
                            <?php if($tobaccoElearning->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $tobaccoFinalConsultation->getStatus() ?>">
                        <td colspan="5" style="text-align: left;"><?php echo $tobaccoFinalConsultation->getComplianceView()->getReportName() ?></td>
                        <td>
                            <?php if($tobaccoFinalConsultation->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                    </tr>


                    <tr class="headerRow">
                        <th style="text-align: left;">B.  Wellness Discount – Meet Goal for 3 or More of B1-5</th>
                        <th style="width: 150px;">Goal</th>
                        <th>Y3: December 2019 Result</th>
                        <th>Y2: January 2019 Result</th>
                        <th>Change Y3 – Y2</th>
                        <th>Met Goal</th>
                        <th>Wellness Discount Met</th>
                    </tr>

                    <?php $i = 0; ?>
                    <?php foreach($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td><?php echo $viewStatus->getAttribute('2020_result') ?></td>
                            <td><?php echo $viewStatus->getAttribute('2019_result') ?></td>
                            <td><?php echo $viewStatus->getAttribute('2019_2020_change') ?></td>
                            <td>
                                <?php if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>
                            </td>
                            <?php if($i == 0 ) : ?>
                            <td rowspan="10" class="status-<?php echo $measureAndAlternativeStatus ?>">
                                <?php if($measureAndAlternativeStatus == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>
                            </td>

                            <?php endif; $i++ ?>
                        </tr>
                    <?php endforeach ?>


                    <tr class="headerRow">
                        <th colspan="5" style="text-align: left;">OR Alternative to Earn Wellness Discount- Complete “a, b, c, and d” below</th>
                        <th></th>
                    </tr>

                    <tr class="status-<?php echo $alternativeGroupStatus->getStatus() ?>">
                        <td colspan="5" style="text-align: left;" >
                           <?php echo $alternativeMeetDrToddStatus->getComplianceView()->getReportName() ?>
                        </td>
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

                    <tr class="status-<?php echo $attendOnsiteSeminarStatus->getStatus() ?>">
                        <td colspan="5" style="text-align: left;" >
                           <?php echo $attendOnsiteSeminarStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td>
                                <?php if($attendOnsiteSeminarStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                    Yes
                                <?php else : ?>
                                    No
                                <?php endif ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $alternativeElearningStatus->getStatus() ?>">
                        <td colspan="5" style="text-align: left;" >
                            <?php echo $alternativeElearningStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td>
                            <?php if($alternativeElearningStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                    </tr>
                    <tr class="status-<?php echo $finalMeetDrToddStatus->getStatus() ?>">
                        <td colspan="5" style="text-align: left;" >
                            <?php echo $finalMeetDrToddStatus->getComplianceView()->getReportName() ?>
                        </td>
                        <td>
                            <?php if($finalMeetDrToddStatus->getStatus() == ComplianceViewStatus::COMPLIANT) : ?>
                                Yes
                            <?php else : ?>
                                No
                            <?php endif ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}