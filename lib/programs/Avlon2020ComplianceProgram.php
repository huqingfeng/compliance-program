<?php
use hpn\steel\query\SelectQuery;

class AvlonLearningAlternativeComplianceView extends ComplianceView
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

class Avlon2020ComplianceProgram extends ComplianceProgram
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
        return new Avlon2020ComplianceProgramReportPrinter();
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

        $screeningHraEnd = '2020-07-31';

        $screeningView = new CompleteScreeningComplianceView($programStart, $screeningHraEnd);
        $screeningView->setReportName('1. Complete the wellness screening - <a href="/content/1114">Sign-Up</a>');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->setAttribute('goal', '7/17/20');
        $screeningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $date = SelectQuery::create()
                ->select('date')
                ->from('screening')
                ->where('user_id = ?', array($user->id))
                ->andWhere('date BETWEEN ? AND ?', array('2020-01-01', '2020-12-31'))
                ->hydrateSingleScalar(true)
                ->limit(1)
                ->execute();

            if($date && !$status->getComment()) {
                $status->setComment(date('m/d/Y', strtotime($date)));
            }
        });
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $screeningHraEnd);
        $hraView->setReportName('2. Complete the Empower Risk Assessment - <a href="/content/989">Click to get done</a>');
        $hraView->setName('complete_hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setAttribute('goal', '7/17/20');
        $coreGroup->addComplianceView($hraView);


        $this->addComplianceViewGroup($coreGroup);

        $measuresGroup = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $measuresGroup->setPointsRequiredForCompliance(1);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $screeningHraEnd, true);
        $bmiView->setReportName('1. Maintain a BMI / Body Mass Index of ≤29.5;  or');
        $bmiView->overrideTestRowData(null, null, 29.5, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '&le;32.0');
        $bmiView->setAttribute('incentive_not_earned', '$0 / See Note * Still possible ?');
        $bmiView->setAttribute('incentive_earned', '$95 / mo');
        $bmiView->setAttribute('incentive_goal', '$95 /mo ($1,140/yr)');
        $bmiView->setAttribute('screening_view', true);
        $measuresGroup->addComplianceView($bmiView);

        $waistView = new ComplyWithWaistScreeningTestComplianceView($programStart, $screeningHraEnd);
        $waistView->setName('waist');
        $waistView->setReportName('2. Have a waist measurement of ≤34.5 inches if female/ ≤37 inches if male.');
        $waistView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $waistView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Men ≤ 37 <br />Women ≤ 34.5');
        $waistView->overrideTestRowData(null, null, 37.1, null, 'M');
        $waistView->overrideTestRowData(null, null, 34.6, null, 'F');
        $waistView->emptyLinks();
        $measuresGroup->addComplianceView($waistView);

        $this->addComplianceViewGroup($measuresGroup);

        $alt3Group = new ComplianceViewGroup('alternate_3', 'Health Assessment Alternate 3');

        $completeConsultation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $completeConsultation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $completeConsultation->setName('complete_consultation');
        $completeConsultation->setReportName('a. Complete consultation with Dr. Todd by 8/5/20. <a href="https://drtoddandkaren.as.me/avlon">Click here for details.</a>');
        $alt3Group->addComplianceView($completeConsultation);

        $elearningAlt3 = new CompleteELearningGroupSet($programStart, $programEnd, 'body_fat');
        $elearningAlt3->setName('elearning_body_fat');
        $elearningAlt3->setReportName('b. <a href="/content/9420?action=lessonManager&tab_alias=body_fat" target="_blank">Click here to complete 5 e-learning lessons</a> related to BMI.');
        $elearningAlt3->setNumberRequired(5);
        $elearningAlt3->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $alt3Group->addComplianceView($elearningAlt3);

        $attendSeminarAlt3 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $attendSeminarAlt3->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $attendSeminarAlt3->setName('attend_seminar_alt3');
        $attendSeminarAlt3->setReportName('c. Attend 1 onsite seminar with Dr. Todd.');
        $alt3Group->addComplianceView($attendSeminarAlt3);

        $finalConsultationAlt3 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $finalConsultationAlt3->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $finalConsultationAlt3->setName('final_consultation_alt3');
        $finalConsultationAlt3->setReportName('d. Complete final consultation with Dr. Todd by 8/26/20. <a href="https://drtoddandkaren.as.me/avlon">Click here for details.</a>');
        $alt3Group->addComplianceView($finalConsultationAlt3);

        $this->addComplianceViewGroup($alt3Group);

        $alt4Group = new ComplianceViewGroup('alternate_4', 'Health Assessment Alternate 4');
        $initialConsultation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $initialConsultation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $initialConsultation->setName('initial_consultation');
        $initialConsultation->setReportName('a. Initial consultation with Dr. Todd by 8/5/20. <a href="https://drtoddandkaren.as.me/avlon">Click here for details.</a>');
        $alt4Group->addComplianceView($initialConsultation);

        $attendSeminarAlt4 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $attendSeminarAlt4->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $attendSeminarAlt4->setName('attend_seminar_alt4');
        $attendSeminarAlt4->setReportName('b. Attend 2 onsite seminars with Dr. Todd.');
        $alt4Group->addComplianceView($attendSeminarAlt4);

        $finalConsultation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $finalConsultation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
        $finalConsultation->setName('final_consultation_alt4');
        $finalConsultation->setReportName('c. Complete consultation with Dr. Todd by 8/26/20.');
        $alt4Group->addComplianceView($finalConsultation);

        $this->addComplianceViewGroup($alt4Group);

        $tobaccoGroup = new ComplianceViewGroup('tobacco_incentive', 'Tobacco Incentive');
        $tobaccoGroup->setPointsRequiredForCompliance(1);

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $screeningHraEnd);
        $cotinineView->setReportName('1. Test Negative for Cotinine (tobacco/nicotine)');
        $cotinineView->setName('cotinine');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative');
        $tobaccoGroup->addComplianceView($cotinineView);

        $tobaccoElearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $tobaccoElearningView->setName('elearning_tobacco');
        $tobaccoElearningView->setReportName('2. <a href="/content/9420?action=lessonManager&tab_alias=tobacco" target="_blank">Click here to complete 5 e-learning lessons</a> related to Tobacco.');
        $tobaccoElearningView->setNumberRequired(5);
        $tobaccoElearningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoGroup->addComplianceView($tobaccoElearningView);

        $this->addComplianceViewGroup($tobaccoGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $tobaccoStatus = $status->getComplianceViewGroupStatus('tobacco_incentive');
        $alt3Group = $status->getComplianceViewGroupStatus('alternate_3');
        $alt4Group = $status->getComplianceViewGroupStatus('alternate_4');

        if ($alt3Group->getStatus() == ComplianceViewGroupStatus::COMPLIANT || $alt4Group->getStatus() == ComplianceViewGroupStatus::COMPLIANT) {
            $healthGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

        if($coreStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $healthGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $tobaccoStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT){
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

    }
}

function determineGoal($status) {
    if ($status == 4)
        return "Yes";
    else {
        return "No";
    }
}

class Avlon2020ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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


            #results .status-<?php echo ComplianceStatus::COMPLIANT ?> {
                /*background-color:#90FF8C;*/
            }

            #results .status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?> {
                /*background-color:#F9FF8C;*/
            }

            #results .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?> {
                /*background-color:#DEDEDE;*/
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

        <script type="text/javascript">
            $(function() {
                $('.status-waist').after("<tr><td colspan='4' style='background:#CCFFFF'>Or, complete alternative 3 or 4 below</td></tr>");
                $('.status-cotinine').after("<tr><td colspan='4' style='background:#CCFFFF'>Or, complete alternative 2</td></tr>");
            })
        </script>

        <div class="letter">
            <p style="clear: both;">
                <div style="float: left; width:40%">
                    <img src="https://master.hpn.com/resources/10085/ehs logo.png"  />
                <p style="font-size: 1rem; margin-top: 10px;font-weight: 600;">
                    4205 Westbrook Drive<br>
                    Aurora, IL 60504
                </p>
                </div>

                <div style="float: right;">
                    <img src="https://master.hpn.com/resources/10091/Avlon_Logo.jpg" style="border-radius: 10px; height:100px;"  />
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
                        Avlon is committed to supporting the health and wellbeing of its employees and their families. 
                        The first step in promoting good health is awareness. That is why Avlon offers the annual health
                        screenings through Empower Health Services. To encourage employees to take advantage of this
                        wonderful benefit, Avlon offers 4 incentives related to the program. 
                    </p>

                    <p>Employees can save up to $520 for the 2020-21 plan year by participating and attaining certain health
                        measures or 1 of the alternatives. Here’s how: Employees can earn $10 per pay period for A (2 actions)
                        <span class="bu">and</span> earn $5 for B (meet the BMI or waist goal) <span class="bu">and</span> another $5 for C (meet the tobacco goal) – a total
                        of up to $20 per pay period. The alternative actions make it possible for everyone to succeed with
                        A, B and C!
                    </p>

                    <p>And, spouses can receive a $10 gift card by participating and attaining certain health measures or 1 of the alternatives.</p>
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

        <div style="padding-top: 20px;">
            <p>
                <strong>Please note:</strong>   Avlon is committed to helping you achieve your best health.  This wellness rewards program is available to all employees and spouses.  If you are unable to meet a goal for a reward under this program based on your wellness screening results –or– a current health condition precludes you from participating in the program, you will be provided with an alternate means of earning the reward.  The specific details of the alternate qualification process, including requirements and deadlines, are explained in the above incentive report sections B and C.  If you have additional questions regarding the details of this program, please contact Empower Health Services at 866.367.6974.
            </p>
        </div>

        </div>

        <?php
    }

    private function getTable($status)
    {
        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $screeningStatus = $status->getComplianceViewStatus('complete_screening');
        $hraStatus = $status->getComplianceViewStatus('complete_hra');

        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $alt3Group = $status->getComplianceViewGroupStatus('alternate_3');
        $alt4Group = $status->getComplianceViewGroupStatus('alternate_4');

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
                        <th>Goal Met</th>
                        <th>Incentive Criteria Met</th>
                    </tr>

                    <tr>
                        <td class="status-<?php echo $screeningStatus->getStatus() ?>" style="text-align: left;"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td class="status-<?php echo $screeningStatus->getStatus() ?>"><?php echo $screeningStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td class="status-<?php echo $screeningStatus->getStatus() ?>"><?php echo $screeningStatus->getComment() ?></td>
                        <td class="status-<?php echo $screeningStatus->getStatus() ?>"><?php echo determineGoal($screeningStatus->getStatus()) ?></td>
                        <td rowspan="2">
                            <?= ($coreStatus->getStatus() == ComplianceStatus::COMPLIANT)? "Yes":"No" ?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $hraStatus->getComplianceView()->getName() ?>">
                        <td style="text-align: left;"><?php echo $hraStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $hraStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $hraStatus->getComment() ?></td>
                        <td><?php echo determineGoal($hraStatus->getStatus()) ?></td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left;" colspan="2">B. BMI &/or Waist Incentive – meet 1 or more goal below:</th>
                        <th>My Result</th>
                        <th>Goal Met</th>
                        <th></th>
                    </tr>

                    <?php $i = 0 ?>
                    <?php foreach($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getComplianceView()->getName() ?>">
                            <td style="text-align: left;"  colspan="2"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo determineGoal($viewStatus->getStatus()) ?></td>
                            <?php if($i == 0) : ?>
                            <td rowspan="5">
                                <?= ($healthyGroupStatus->getStatus() == ComplianceStatus::COMPLIANT)? "Yes":"No" ?>
                            </td>

                            <?php endif; $i++?>
                        </tr>
                    <?php endforeach ?>

                    <tr>
                        <td style="text-align: left" colspan="2">
                            3. Complete these actions below:
                            <?php foreach($alt3Group->getComplianceViewStatuses() as $viewStatus) : ?>
                                <div style="margin-left:20px;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></div>
                            <?php endforeach;?>
                        </td>
                        <td style="vertical-align: bottom">
                            <br>
                            <?php foreach($alt3Group->getComplianceViewStatuses() as $viewStatus) : ?>
                                <div><?php echo $viewStatus->getComment() ?></div>
                            <?php endforeach;?>
                        </td>
                        <td>
                            <?= ($alt3Group->getStatus() == ComplianceStatus::COMPLIANT)? "Yes":"No" ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: left" colspan="2">
                            4. Complete these actions below:
                            <?php foreach($alt4Group->getComplianceViewStatuses() as $viewStatus) : ?>
                                <div style="margin-left:20px;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></div>
                            <?php endforeach;?>
                        </td>
                        <td style="vertical-align: bottom">
                            <br>
                            <?php foreach($alt4Group->getComplianceViewStatuses() as $viewStatus) : ?>
                                <div><?php echo $viewStatus->getComment() ?></div>
                            <?php endforeach;?>
                        </td>
                        <td>
                            <?= ($alt4Group->getStatus() == ComplianceStatus::COMPLIANT)? "Yes":"No" ?>
                        </td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left;" colspan="2">C. Tobacco Incentive – meet 1 or more goal below:
                        </th>
                        <th>My Result</th>
                        <th>Goal Met</th>
                        <th></th>
                    </tr>

                    <?php $i = 0 ?>
                    <?php foreach($tobaccoGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getComplianceView()->getName() ?>">
                            <td style="text-align: left;"  colspan="2"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo determineGoal($viewStatus->getStatus()) ?></td>
                            <?php if($i == 0) : ?>
                            <td rowspan="4">
                                <?= ($tobaccoGroupStatus->getStatus() == ComplianceStatus::COMPLIANT)? "Yes":"No" ?>
                            </td>

                            <?php endif; $i++?>
                        </tr>
                    <?php endforeach ?>


                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}