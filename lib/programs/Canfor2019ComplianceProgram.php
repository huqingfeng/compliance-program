<?php

class CanforLearningAlternativeComplianceView extends ComplianceView
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
        return 'incredible_technologies_alt_'.$this->alias;
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

class Canfor2019ComplianceProgram extends ComplianceProgram
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
        return new Canfor2019ComplianceProgramReportPrinter();
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
        $coreGroup->setAttribute('incentive_compliant_points', 40);
//        $coreGroup->setPointsRequiredForCompliance(50);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('1. Complete the wellness screening - <a href="/content/989">Sign-Up</a>');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->setAttribute('goal', '11/8/19');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('2. Complete the Empower Risk Assessment - <a href="/content/989">Click to get done</a>');
        $hraView->setName('complete_hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setAttribute('goal', '11/8/19');
        $coreGroup->addComplianceView($hraView);


        $this->addComplianceViewGroup($coreGroup);

        $measuresGroup = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $measuresGroup->setPointsRequiredForCompliance(1);
        $measuresGroup->setAttribute('incentive_compliant_points', 95);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd, true);
        $bmiView->setReportName('1. Maintain a BMI / Body Mass Index of ≤32.0;  or');
        $bmiView->overrideTestRowData(null, null, 32, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '&le;32.0');
        $bmiView->setAttribute('incentive_not_earned', '$0 / See Note * Still possible ?');
        $bmiView->setAttribute('incentive_earned', '$95 / mo');
        $bmiView->setAttribute('incentive_goal', '$95 /mo ($1,140/yr)');
        $bmiView->setAttribute('screening_view', true);
        $measuresGroup->addComplianceView($bmiView);

        $bmiElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'body_fat');
        $bmiElearningView->setName('elearning_bmi');
        $bmiElearningView->setReportName('2. <a href="/content/9420?action=lessonManager&tab_alias=body_fat" target="_blank">Click here to complete 4  e-learning lessons (*maximum of 1 per week)</a> related to BMI.');
        $bmiElearningView->setNumberRequired(4);
        $bmiElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $measuresGroup->addComplianceView($bmiElearningView);

        $bmiCoachingView = new PlaceHolderComplianceView(ComplianceViewStatus::NA_COMPLIANT);
        $bmiCoachingView->setReportName('3. Complete 4 telephonic health coaching sessions for BMI. <a href="/resources/10414/Canfor_Alternate_Standard_BMI_v5_072419.pdf">Click here for details.</a>');
        $bmiCoachingView->setName('bmi_coaching');
        $bmiCoachingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $measuresGroup->addComplianceView($bmiCoachingView);

        $this->addComplianceViewGroup($measuresGroup);


        $tobaccoGroup = new ComplianceViewGroup('tobacco_incentive', 'Tobacco Incentive');
        $tobaccoGroup->setPointsRequiredForCompliance(1);
        $tobaccoGroup->setAttribute('incentive_compliant_points', 115);

        $tobaccoView = new PlaceHolderComplianceView(ComplianceViewStatus::NA_COMPLIANT);
        $tobaccoView->setReportName('1. Complete the Tobacco Affidavit <a href="/resources/10413/Canfor_Tobacco_Use_Affidavit_081919.pdf">Click here for details.</a>');
        $tobaccoView->setName('tobacco');
        $tobaccoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoView->setAttribute('goal', 'Non User');
        $tobaccoView->setAttribute('incentive_not_earned', '$0 / See Note * Still possible ?');
        $tobaccoView->setAttribute('incentive_earned', '$115 / mo');
        $tobaccoView->setAttribute('incentive_goal', '$115 /mo ($1,140/yr)');
        $tobaccoView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                $status->setComment('No Response');
            } elseif ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
                $status->setComment('User');
            } elseif ($status->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setComment('Non User');
            }
        });
        $tobaccoGroup->addComplianceView($tobaccoView);

        $tobaccoElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $tobaccoElearningView->setName('elearning_tobacco');
        $tobaccoElearningView->setReportName('2. <a href="/content/9420?action=lessonManager&tab_alias=tobacco" target="_blank">Click here to complete 4  e-learning lessons (*maximum of 1 per week)</a> related to Tobacco..');
        $tobaccoElearningView->setNumberRequired(4);
        $tobaccoElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoGroup->addComplianceView($tobaccoElearningView);

        $tobaccoCoachingView = new PlaceHolderComplianceView(ComplianceViewStatus::NA_COMPLIANT);
        $tobaccoCoachingView->setReportName('3. Complete 4 telephonic health coaching sessions for tobacco. <a href="/resources/10415/Canfor_Alternate_Standard_Tobacco_v5_072419.pdf">Click here for details.</a>');
        $tobaccoCoachingView->setName('tobacco_coaching');
        $tobaccoCoachingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoGroup->addComplianceView($tobaccoCoachingView);

        $this->addComplianceViewGroup($tobaccoGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $screeningStatus = $status->getComplianceViewStatus('complete_screening');
        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $elearningBmi = $status->getComplianceViewStatus('elearning_bmi');
        $elearningTobacco = $status->getComplianceViewStatus('elearning_tobacco');

        if($elearningBmi->getStatus() != ComplianceStatus::COMPLIANT
            && $elearningBmi->getAttribute('lessons_completed')
            && count($elearningBmi->getAttribute('lessons_completed'))) {
            $elearningBmi->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        }

        if($elearningTobacco->getStatus() != ComplianceStatus::COMPLIANT
            && $elearningTobacco->getAttribute('lessons_completed')
            && count($elearningTobacco->getAttribute('lessons_completed'))) {
            $elearningTobacco->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        }


        $totalPoints = 0;
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            if($groupStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $totalPoints += $groupStatus->getComplianceViewGroup()->getAttribute('incentive_compliant_points');
            }
        }

        if($coreStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $healthGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT){
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        $status->setAttribute('incentive_compliant_points', $totalPoints);
    }
}

class Canfor2019ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                margin:0.1in 0;
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

            #root_note {
                background-color: #ccffcc;
                margin-top: 20px;
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
                    <img src="/images/empower/canfor__logo.png" style="height:66px;"  />
                </div>

                <div style="float: right; width:60%">
                    <h5>2020 Incentive Rewards & To-Dos</h5>
                </div>
            </p>


            <div style="clear: both">
                <div style="float: left; width: 48%; margin-top: 10px; display: block">
                    <p>
                        Hello <?php echo $user->first_name ?>,
                    </p>

                    <p>
                        We care about your health and wellbeing and are proud to continue offering a wellbeing program with
                         incentives to help you achieve your best health now and in the years to come.
                    </p>

                    <p>Below you can find your current rewards status.</p>
                </div>

                <div style="float: right; width: 50%;">
                    <div style="border:1px solid #000000; width: 48%; float:left;">
                        <div style="margin: 10%;">
                            <strong>REMINDER</strong> - <span style="font-size: 9pt;">Option B2 and C2 require completing 1 lessons* each week until 4 lessons are completed over
                            three or more weeks. Extra lessons each week do not count toward the goal.</span>
                        </div>
                    </div>


                    <div style="border:1px solid #000000; width: 48%; float:right;">
                        <div style="margin: 10%;">
                            <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Goal Status Colors</div>

                            <img src="/images/lights/greenlight.gif" class="light"> = Goal Met / Done<br />
                            <img src="/images/lights/yellowlight.gif" class="light"> = Working on It<br />
                            <img src="/images/lights/redlight.gif" class="light"> = Not Met or Started
                        </div>

                    </div>
                </div>
            </div>
            <div style="clear: both"></div>



            <?php echo $this->getTable($status) ?>

            <div id="not_compliant_notes">
                <div style="width: 56%; float: left">
                    <p>
                        Interested in resources for better health, health care and wellbeing?
                        <ul>
                            <li><a href="/sitemaps/healthwellbeingtools">Click here to access these and others -></a></li>
                        </ul><br/>

                        And,click below to:
                        <ul>
                            <li><a href="/content/989">See all my screening results</a></li>
                            <li><a href="/content/company-resources">My company resources.</a></li>
                        </ul>
                    </p>
                </div>

                <div style="width: 43%; float: right; background-color: #cceeff;">
                    <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Online tools and resources for you!</div>
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

            <div id="root_note">
                <div style="padding: 10px;">
                    <p>
                        *  The incentive savings amount is the surcharge that will be avoided if you met the goal(s) for each incentive. <br />
                        Coaching option details and the Tobacco form  are also available at the Canfor Human Resources department.
                    </p>
                </div>
            </div>

            <p>&nbsp;</p>
        </div>

        <?php
    }

    private function getTable($status)
    {
        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $screeningStatus = $status->getComplianceViewStatus('complete_screening');
        $hraStatus = $status->getComplianceViewStatus('complete_hra');

        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $tobaccoGroupStatus = $status->getComplianceViewGroupStatus('tobacco_incentive');


        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 320px;">A. Core Actions - meet both goals below to get this incentive:</th>
                        <th>Goal Deadline</th>
                        <th>Date Done</th>
                        <th>Goal Status</th>
                        <th>Possible Incentive* Savings if Goal  Met</th>
                        <th>My Actual Incentive * Savings Earned</th>
                    </tr>

                    <tr>
                        <td class="status-<?php echo $screeningStatus->getStatus() ?>" style="text-align: left;"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td class="status-<?php echo $screeningStatus->getStatus() ?>"><?php echo $screeningStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td class="status-<?php echo $screeningStatus->getStatus() ?>"><?php echo $screeningStatus->getComment() ?></td>
                        <td class="status-<?php echo $screeningStatus->getStatus() ?>"><img src="<?php echo $screeningStatus->getLight(); ?>" class="light"/></td>
                        <td rowspan="2" class="status-<?php echo $coreStatus->getStatus() ?>" >
                            $40/mos ($480/yr)
                        </td>
                        <td rowspan="2" class="status-<?php echo $coreStatus->getStatus() ?>" >
                            <?php echo $coreStatus->getStatus() == ComplianceStatus::COMPLIANT ? '$40/ mos' : '$0/mo'?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $hraStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $hraStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $hraStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $hraStatus->getComment() ?></td>
                        <td><img src="<?php echo $hraStatus->getLight(); ?>" class="light"/></td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left;" colspan="2">B. BMI Incentive – meet 1 or more goal below:</th>
                        <th>My Result</th>
                        <th>Goal Status</th>
                        <th></th>
                        <th></th>
                    </tr>

                    <?php $i = 0 ?>
                    <?php foreach($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() == ComplianceStatus::COMPLIANT ? ComplianceStatus::COMPLIANT : '' ?>">
                            <td style="text-align: left;"  colspan="2"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><img src="<?php echo $viewStatus->getLight(); ?>" class="light"/></td>
                            <?php if($i == 0) : ?>
                            <td rowspan="3" class="status-<?php echo $healthyGroupStatus->getStatus() ?>">
                                $95/ mos ($1,140/yr)
                            </td>
                            <td rowspan="3" class="status-<?php echo $healthyGroupStatus->getStatus() ?>">
                                <?php echo $healthyGroupStatus->getStatus() == ComplianceStatus::COMPLIANT ? '$95 / mos' : '$0/mo'?>
                            </td>
                            <?php endif; $i++?>
                        </tr>
                    <?php endforeach ?>


                    <tr class="headerRow">
                        <th style="text-align: left;" colspan="2">C. Tobacco Incentive – meet 1 or more goal below:</th>
                        <th>My Result</th>
                        <th>Goal Status</th>
                        <th></th>
                        <th></th>
                    </tr>

                    <?php $i = 0 ?>
                    <?php foreach($tobaccoGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() == ComplianceStatus::COMPLIANT ? ComplianceStatus::COMPLIANT : '' ?>">
                            <td style="text-align: left;"  colspan="2"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><img src="<?php echo $viewStatus->getLight(); ?>" class="light"/></td>
                            <?php if($i == 0) : ?>
                            <td rowspan="3" class="status-<?php echo $tobaccoGroupStatus->getStatus() ?>">
                                $115/ mos ($1,380/yr)
                            </td>
                            <td rowspan="3" class="status-<?php echo $tobaccoGroupStatus->getStatus() ?>">
                                <?php echo $tobaccoGroupStatus->getStatus() == ComplianceStatus::COMPLIANT ? '$115 / mos' : '$0/mo'?>
                            </td>
                            <?php endif; $i++?>
                        </tr>
                    <?php endforeach ?>


                    <tr class="headerRow">
                        <th style="text-align: left;" colspan="4">D. Incentive Rewards Summary  -></th>
                        <th>Maximum Possible</th>
                        <th>My Savings Amount *</th>
                    </tr>

                    <tr>
                        <th colspan="4">
                            Totals as of <?php echo date('m/d/Y') ?>
                        </th>
                        <th><strong>$250/ mo ($3,000/yr)</strong></th>
                        <?php if($status->getStatus() == ComplianceStatus::COMPLIANT) : ?>
                            <td style="background-color: #90FF8C">
                                $<?php echo $status->getAttribute('incentive_compliant_points') ?> / mo
                            </td>
                         <?php else : ?>
                            <td>
                                $<?php echo $status->getAttribute('incentive_compliant_points') ?> / mo
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