<?php

use \hpn\steel\query\SelectQuery;

class MacAllisterMachinery2019ComplianceProgram extends ComplianceProgram
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
        return new MacAllisterMachinery2019ComplianceProgramReportPrinter();
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
        $screeningView->setReportName('1. Complete the wellness screening');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->setAttribute('goal', '9/30/19');
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

        $tobaccoGroup = new ComplianceViewGroup('tobacco', 'Tobacco Use Status');

        $tobaccoView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $tobaccoView->setReportName('Tobacco Use Status');
        $tobaccoView->emptyLinks();
        $tobaccoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoView->setAttribute('goal', 'Non-User');
        $tobaccoView->setAttribute('screening_view', true);
        $tobaccoView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($programStart, $programEnd) {
            $view = $status->getComplianceView();

            $elearningView = new CompleteELearningGroupSet('2019-04-02', $programEnd, 'tobacco_2019');
            $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
            $elearningView->setName('alternative_'.$view->getName());
            $elearningView->useAlternateCode(true);
            $elearningView->setNumberRequired(5);

            $elearningStatus = $elearningView->getStatus($user);

            $lessonCompleted = count($elearningStatus->getAttribute('lessons_completed', array()));

            $status->setAttribute('lessons_completed', $lessonCompleted);

            if($status->getComment() != '') {
                if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setComment('Non-User');
                } else {
                    $status->setComment('User');
                }
            }

            if($lessonCompleted >= 5 && $status->getStatus() != ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                $healthRiskPoints = '0';
            } else {
                $healthRiskPoints = '1';
            }

            $status->setAttribute('health_risk_points', $healthRiskPoints);
        });
        $tobaccoGroup->addComplianceView($tobaccoView);

        $this->addComplianceViewGroup($tobaccoGroup);

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(3);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. BMI');
        $bmiView->setName('bmi');
        $bmiView->overrideTestRowData(0, 0, 25, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '≤ 25');
        $bmiView->setAttribute('screening_view', true);
        $bmiView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($programStart, $programEnd) {
            $startDate = date('Y-m-d', $programStart);
            $endDate = date('Y-m-d', $programEnd);
            $view = $status->getComplianceView();

            $waist = SelectQuery::create()
                    ->select('waist')
                    ->from('screening')
                    ->where('user_id = ?', array($user->id))
                    ->andWhere('date BETWEEN ? AND ?', array($startDate, $endDate))
                    ->hydrateSingleScalar()
                    ->execute();

            if($waist) {
                if(($user->getGender() == 'M' && $waist < 40) || ($user->getGender() == 'F' && $waist < 35)) {
                    $alternativeView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
                    $alternativeView->setComplianceViewGroup($view->getComplianceViewGroup());
                    $alternativeView->setName('alternative_'.$view->getName());
                    $alternativeView->overrideTestRowData(0, 0, 30.999, null);
                    $alternativeStatus = $alternativeView->getStatus($user);

                    if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    }
                }

                $status->setAttribute('waist', $waist);
            }

            if($status->getComment() != 'Test Not Taken' && $status->getComment() != 'No Screening') {
                if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                    $healthRiskPoints = '0';
                } else {
                    $healthRiskPoints = '1';
                }
            } else {
                $healthRiskPoints = 'Not Completed';
            }

            $status->setAttribute('health_risk_points', $healthRiskPoints);
        });
        $group->addComplianceView($bmiView);

        $bpView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bpView->setReportName('2. Blood Pressure');
        $bpView->setName('blood_pressure');
        $bpView->overrideSystolicTestRowData(null, null, 140, null);
        $bpView->overrideDiastolicTestRowData(null, null, 90, null);
        $bpView->emptyLinks();
        $bpView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bpView->setAttribute('goal', '≤140 / ≤90');
        $bpView->setAttribute('screening_view', true);
        $bpView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($programStart, $programEnd) {
            $view = $status->getComplianceView();
            $age = $user->getAge();
            $status->setAttribute('age', $age);

            if($age >= 60) {
                $alternativeView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
                $alternativeView->setComplianceViewGroup($view->getComplianceViewGroup());
                $alternativeView->setName('alternative_'.$view->getName());
                $alternativeView->overrideSystolicTestRowData(null, null, 150, null);
                $alternativeView->overrideDiastolicTestRowData(null, null, 90, null);
                $alternativeStatus = $alternativeView->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }

            if($status->getComment() != 'Test Not Taken' && $status->getComment() != 'No Screening') {
                if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                    $healthRiskPoints = '0';
                } else {
                    $healthRiskPoints = '1';
                }
            } else {
                $healthRiskPoints = 'Not Completed';
            }

            $status->setAttribute('health_risk_points', $healthRiskPoints);
        });
        $group->addComplianceView($bpView);


        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $tcView->setReportName('3. Total Cholesterol');
        $tcView->setName('cholesterol');
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0 ,0));
        $tcView->setAttribute('goal', '< 200');
        $tcView->setAttribute('screening_view', true);
        $tcView->addResultMapping('<100', ComplianceStatus::COMPLIANT, '<100');
        $tcView->addResultMapping('>400', ComplianceStatus::NOT_COMPLIANT, '>400');
        $tcView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($programStart, $programEnd) {
            $startDate = date('Y-m-d', $programStart);
            $endDate = date('Y-m-d', $programEnd);
            $view = $status->getComplianceView();

            $hdlRatio = SelectQuery::create()
                    ->select('totalhdlratio')
                    ->from('screening')
                    ->where('user_id = ?', array($user->id))
                    ->andWhere('date BETWEEN ? AND ?', array($startDate, $endDate))
                    ->hydrateSingleScalar()
                    ->execute();

            if($hdlRatio) {
                $hdlRatio = round($hdlRatio, 1);
                if($hdlRatio <= 4) {
                    $alternativeView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
                    $alternativeView->setComplianceViewGroup($view->getComplianceViewGroup());
                    $alternativeView->setName('alternative_'.$view->getName());
                    $alternativeView->overrideTestRowData(null, 200, 240, null);
                    $alternativeStatus = $alternativeView->getStatus($user);

                    if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    }
                }

                $status->setAttribute('total_hdl_ratio', $hdlRatio);
            }

            if($status->getComment() != 'Test Not Taken' && $status->getComment() != 'No Screening') {
                if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                    $healthRiskPoints = '0';
                } else {
                    $healthRiskPoints = '1';
                }
            } else {
                $healthRiskPoints = 'Not Completed';
            }

            $status->setAttribute('health_risk_points', $healthRiskPoints);
        });
        $group->addComplianceView($tcView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('4. Glucose, Fasting');
        $gluView->setName('glucose');
        $gluView->overrideTestRowData(null, null, 100, null);
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $gluView->setAttribute('goal', '≤ 100');
        $gluView->addResultMapping('>600', ComplianceStatus::NOT_COMPLIANT, '>600');
        $gluView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($programStart, $programEnd) {
            if($status->getComment() != 'Test Not Taken' && $status->getComment() != 'No Screening') {
                if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                    $healthRiskPoints = '0';
                } else {
                    $healthRiskPoints = '1';
                }
            } else {
                $healthRiskPoints = 'Not Completed';
            }

            $status->setAttribute('health_risk_points', $healthRiskPoints);
        });
        $group->addComplianceView($gluView);

        $this->addComplianceViewGroup($group);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');


        $notCompliant = 0;
        $notComplete = 0;
        foreach($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getAttribute('health_risk_points') == '1') {
                $notCompliant++;
            }

            if($viewStatus->getAttribute('health_risk_points') == 'Not Completed') $notComplete++;
        }

        if($notComplete >= 1) {
            $status->setAttribute('overral_status', 'no_screening');
            $status->setAttribute('health_risk_points', 'Not Completed');
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        } elseif ($notCompliant >= 1) {
            $status->setAttribute('overral_status', false);
            $status->setAttribute('health_risk_points', $notCompliant);
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        } else {
            $status->setAttribute('overral_status', true);
            $status->setAttribute('health_risk_points', $notCompliant);
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }
    }
}

class MacAllisterMachinery2019ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                background-color:#ffb3b3;
            }

            #not_compliant_notes p{
                margin: 3px 0;
            }

            .footer {
                text-align: left;
                padding-top: 0px;
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
                    <img src="/images/empower/macallister_machinery_logo.jpg" style="height:80px;"  />
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


            <p>Thank you for your participation in the Empower Health Wellness Screening Program. Your company has an
             incentive program based on participating in the biometric screening, being a non-tobacco user and having
              healthy readings in the key metrics listed below.</p>

            <p>Please read over the chart below to see what areas you have passed and what areas may still need attention.</p>

            <?php echo $this->getTable($status) ?>

            <div id="not_compliant_notes">
                <div style="width: 56%; float: left; font-size:9pt;">
                    <p>
                        <p style="color: red;">
                            <strong>* IMPORTANT:</strong> See your wellness screening results report that has recommended ideal ranges for
                            your good health wellbeing.  For example, the ideal range for blood pressure is ≤119/≤79.
                            Anything over these numbers increases your risks for cardiovascular disease, strokes and kidney failure.
                        </p>
                        <p>Login to <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> any time to:</p>
                        <ul>
                            <li>View all of your screening results and links in the report;  AND</li>
                            <li>Access powerful tools and resources for optimizing your health, care and wellbeing.</li>
                        </ul>

                        <p>
                            Thank you for getting your wellness screening done this year. This and many of your other
                            actions reflect how you value your own wellbeing and the wellbeing of others at home
                            and work.
                        </p>

                    </p>
                </div>

                <div style="width: 43%; float: right; background-color: #cceeff; margin-top:6px;">
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

            <p style="clear: both; font-size:9pt;">
                Your employer is committed to helping you achieve your best health. Rewards for participating
                in a wellness program are available to all employees. If you are unable to meet a standard for
                a reward under this wellness program based on your biometric testing results or your current
                health condition precludes you from participating in the program, you will be provided with
                an alternate means of earning the reward. The specific details of the alternate qualification
                process, including requirements and deadlines, are explained in your incentive report card.
                If you have additional questions regarding the details of this program, please contact Empower
                Health Services at 866.367.6974.<br /><br />

                Best Regards,<br />
                Empower Health Services
            </p>
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
                        <th style="text-align: left; width: 360px;">A. Incentive Actions</th>
                        <th>Goal Deadline</th>
                        <th>Date Done</th>
                        <th>Completed</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('core')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left; width: 260px;">B. Tobacco Use Status</th>
                        <th>Goal</th>
                        <th>My Result</th>
                        <th>Health Risk Points</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('tobacco')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left; padding-left:18px;">Be a Non-User per Tobacco Affidavit</td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td rowspan="2"><?php echo $viewStatus->getAttribute('health_risk_points') ?></td>
                        </tr>

                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left; padding-left:18px;">
                                Or &#8594; Complete 5 tobacco-related e-learning lessons &#8594;
                                <a href="/content/9420?action=lessonManager&tab_alias=tobacco_2019">View/Do Lessons</a>
                            </td>
                            <td>≥5</td>
                            <td><?php echo $viewStatus->getAttribute('lessons_completed') ?></td>
                        </tr>
                    <?php endforeach ?>


                    <tr class="headerRow">
                        <th style="text-align: left;">C. Biometric Measures</th>
                        <th>Goal</th>
                        <th>My Result</th>
                        <th>Health Risk Points</th>
                    </tr>

                    <?php $viewStatus = $status->getComplianceViewStatus('bmi') ?>
                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td style="text-align: left;">1. BMI &#8594; any waist size</td>
                        <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td rowspan="2"><?php echo $viewStatus->getComment() ?></td>
                        <td rowspan="2">
                            <?php echo $viewStatus->getAttribute('health_risk_points') ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td style="text-align: left; padding-left:18px;">Or &#8594; If waist is <40M; <35F; My waist = <?php echo $viewStatus->getAttribute('waist') ?></td>
                        <td><31</td>
                    </tr>

                    <?php $viewStatus = $status->getComplianceViewStatus('blood_pressure') ?>
                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td style="text-align: left;">2. Blood Pressure &#8594; any age – see note*</td>
                        <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td rowspan="2"><?php echo $viewStatus->getComment() ?></td>
                        <td rowspan="2">
                            <?php echo $viewStatus->getAttribute('health_risk_points') ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td style="text-align: left; padding-left:18px;">Or &#8594; If ≥60 years;  My age = <?php echo $viewStatus->getAttribute('age') ?></td>
                        <td>≤150 / ≤90</td>
                    </tr>


                    <?php $viewStatus = $status->getComplianceViewStatus('cholesterol') ?>
                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td style="text-align: left;">3. Total Cholesterol &#8594; Any TC/HDL ratio</td>
                        <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td rowspan="2"><?php echo $viewStatus->getComment() ?></td>
                        <td rowspan="2">
                            <?php echo $viewStatus->getAttribute('health_risk_points') ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td style="text-align: left; padding-left:18px;">Or &#8594; if TC/HDL is ≤4.0;  My TC/HDL ratio = <?php echo $viewStatus->getAttribute('total_hdl_ratio') ?></td>
                        <td>200 - 240</td>
                    </tr>

                    <?php $viewStatus = $status->getComplianceViewStatus('glucose') ?>
                    <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                        <td style="text-align: left;">4. Glucose, Fasting</td>
                        <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $viewStatus->getComment() ?></td>
                        <td><?php echo $viewStatus->getAttribute('health_risk_points') ?></td>
                    </tr>


                    <tr>
                        <?php if($status->getAttribute('overral_status') === 'no_screening') : ?>
                            <td colspan="3" style="background-color: #ffb3b3">
                                <p>
                                    Please complete the wellness screening.
                                </p>
                            </td>
                            <td style="background-color: #ffb3b3"><?php echo $status->getAttribute('health_risk_points') ?></td>
                        <?php elseif($status->getAttribute('overral_status')) : ?>
                            <td colspan="3" style="background-color: #90FF8C">
                                <p>
                                    <strong>Congratulations!</strong> You have no health risks that apply to this program and have earned the full incentive.
                                </p>
                            </td>
                            <td style="background-color: #90FF8C"><?php echo $status->getAttribute('health_risk_points') ?></td>
                         <?php else : ?>
                            <td colspan="3" style="background-color: #ffb3b3">
                                <p style="margin:10px 20px;">
                                    You have 1 or more results that are considered a Health Risk.  If you are under the
                                    care of a physician to address a particular Health Risk, you can still earn the
                                    incentive by working with your physician to complete and return the Alternate
                                    Qualification Form (AQF).
                                    <a href="/resources/10305/2019_MacAllister Machinery__AQF_012519.pdf">Click here to download the AQF.</a>
                                </p>
                                <p>
                                    AQF's are due NO LATER THAN October 22, 2019.
                                </p>
                            </td>
                            <td style="background-color: #ffb3b3"><?php echo $status->getAttribute('health_risk_points') ?></td>
                         <?php endif ?>
                    </tr>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }

}