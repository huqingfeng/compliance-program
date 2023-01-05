<?php

class TransportationRepairServices2019LearningAlternativeComplianceView extends ComplianceView
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

class TransportationRepairServices2019ComplianceProgram extends ComplianceProgram
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
        return new TransportationRepairServices2019ComplianceProgramReportPrinter();
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
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->setAttribute('goal', '10/09/19');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('2. Complete the EHA (optional)');
        $hraView->setName('complete_hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setAttribute('goal', '10/09/19');
        $coreGroup->addComplianceView($hraView);


        $this->addComplianceViewGroup($coreGroup);

        $tobaccoGroup = new ComplianceViewGroup('tobacco', 'Tobacco');

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('1. Test Negative for Cotinine');
        $cotinineView->emptyLinks();
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $cotinineView->setAttribute('goal', 'Negative or 4 lessons');
        $cotinineView->setAttribute('screening_view', true);
        $cotinineView->setAttribute('value', '$480');
        $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($programStart, $programEnd) {
            $view = $status->getComplianceView();

            $elearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
            $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
            $elearningView->setName('alternative_'.$view->getName());
            $elearningView->useAlternateCode(true);
            $elearningView->setNumberRequired(4);

            $elearningStatus = $elearningView->getStatus($user);

            $status->setAttribute('lessons_completed', count($elearningStatus->getAttribute('lessons_completed', array())));

            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT
                && ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT || $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT )) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            $status->setAttribute('elearning_link', '<a href="/content/9420?action=lessonManager&tab_alias=tobacco" target="_blank">Review/Do Lessons</a>');
        });
        $tobaccoGroup->addComplianceView($cotinineView);

        $this->addComplianceViewGroup($tobaccoGroup);


        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(4);


        $lastYearStart = '2018-01-01';
        $lastYearEnd = '2018-12-31';


        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('1. BMI');
        $bmiView->overrideTestRowData(null, null, 29, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '≤29 or 2 less from 2018 or 3 lessons');
        $bmiView->setAttribute('screening_view', true);
        $bmiView->setAttribute('value', '$240');
        $bmiView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($programStart, $programEnd, $lastYearStart, $lastYearEnd) {
            $view = $status->getComplianceView();
            $thisYearResult = $status->getComment();


            $alternativeView = new ComplyWithBMIScreeningTestComplianceView($lastYearStart, $lastYearEnd);
            $alternativeView->setComplianceViewGroup($view->getComplianceViewGroup());
            $alternativeView->setName('alternative_'.$view->getName());
            $alternativeStatus = $alternativeView->getStatus($user);

            $lastYearResult = $alternativeStatus->getComment();


            $elearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'body_fat');
            $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
            $elearningView->setName('alternative_'.$view->getName());
            $elearningView->useAlternateCode(true);
            $elearningView->setNumberRequired(3);

            $elearningStatus = $elearningView->getStatus($user);

            $status->setAttribute('lessons_completed', count($elearningStatus->getAttribute('lessons_completed', array())));

            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT
                && ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT || $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT )) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } elseif(!empty($thisYearResult) && !empty($lastYearResult) && is_numeric($thisYearResult) && is_numeric($lastYearResult) && $lastYearResult - $thisYearResult >= 2) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            if(!empty($thisYearResult) && !empty($lastYearResult) && is_numeric($thisYearResult) && is_numeric($lastYearResult)) {
                $status->setAttribute('difference', round($thisYearResult - $lastYearResult, 2));
            }

            $status->setAttribute('last_year_result', $lastYearResult);
            $status->setAttribute('elearning_link', '<a href="/content/9420?action=lessonManager&tab_alias=body_fat" target="_blank">Review/Do Lessons</a>');
        });
        $group->addComplianceView($bmiView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->setReportName('2. LDL Cholesterol');
        $ldlView->overrideTestRowData(null, null, 130, null);
        $ldlView->emptyLinks();
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlView->setAttribute('goal', '≤130 or 10 less from 2018 or 3 lessons');
        $ldlView->setAttribute('screening_view', true);
        $ldlView->setAttribute('value', '$240');
        $ldlView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($programStart, $programEnd, $lastYearStart, $lastYearEnd) {
            $view = $status->getComplianceView();
            $thisYearResult = $status->getComment();

            $alternativeView = new ComplyWithLDLScreeningTestComplianceView($lastYearStart, $lastYearEnd);
            $alternativeView->setComplianceViewGroup($view->getComplianceViewGroup());
            $alternativeView->setName('alternative_'.$view->getName());
            $alternativeStatus = $alternativeView->getStatus($user);

            $lastYearResult = $alternativeStatus->getComment();

            $elearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'cholesterol');
            $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
            $elearningView->setName('alternative_'.$view->getName());
            $elearningView->useAlternateCode(true);
            $elearningView->setNumberRequired(3);

            $elearningStatus = $elearningView->getStatus($user);

            $status->setAttribute('lessons_completed', count($elearningStatus->getAttribute('lessons_completed', array())));

            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT
                && ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT || $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT )) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } elseif(!empty($thisYearResult) && !empty($lastYearResult) && is_numeric($thisYearResult) && is_numeric($lastYearResult) && $lastYearResult - $thisYearResult >= 10) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            if(!empty($thisYearResult) && !empty($lastYearResult) && is_numeric($thisYearResult) && is_numeric($lastYearResult)) {
                $status->setAttribute('difference', round($thisYearResult - $lastYearResult, 2));
            }

            $status->setAttribute('last_year_result', $lastYearResult);
            $status->setAttribute('elearning_link', '<a href="/content/9420?action=lessonManager&tab_alias=cholesterol" target="_blank">Review/Do Lessons</a>');
        });
        $group->addComplianceView($ldlView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('3. Triglycerides');
        $trigView->overrideTestRowData(null, null, 150, null);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0 ,0));
        $trigView->setAttribute('goal', '≤150 or 15 less from 2018 or 3 lessons');
        $trigView->setAttribute('screening_view', true);
        $trigView->setAttribute('value', '$240');
        $trigView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($programStart, $programEnd, $lastYearStart, $lastYearEnd) {
            $view = $status->getComplianceView();
            $thisYearResult = $status->getComment();

            $alternativeView = new ComplyWithTriglyceridesScreeningTestComplianceView($lastYearStart, $lastYearEnd);
            $alternativeView->setComplianceViewGroup($view->getComplianceViewGroup());
            $alternativeView->setName('alternative_'.$view->getName());
            $alternativeStatus = $alternativeView->getStatus($user);

            $lastYearResult = $alternativeStatus->getComment();

            $elearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'cholesterol');
            $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
            $elearningView->setName('alternative_'.$view->getName());
            $elearningView->useAlternateCode(true);
            $elearningView->setNumberRequired(3);

            $elearningStatus = $elearningView->getStatus($user);

            $status->setAttribute('lessons_completed', count($elearningStatus->getAttribute('lessons_completed', array())));

            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT
                && ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT || $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT )) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } elseif(!empty($thisYearResult) && !empty($lastYearResult) && is_numeric($thisYearResult) && is_numeric($lastYearResult) && $lastYearResult - $thisYearResult >= 15) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            if(!empty($thisYearResult) && !empty($lastYearResult) && is_numeric($thisYearResult) && is_numeric($lastYearResult)) {
                $status->setAttribute('difference', round($thisYearResult - $lastYearResult, 2));
            }

            $status->setAttribute('last_year_result', $lastYearResult);
            $status->setAttribute('elearning_link', '<a href="/content/9420?action=lessonManager&tab_alias=cholesterol" target="_blank">Review/Do Lessons</a>');
        });
        $group->addComplianceView($trigView);

        $this->addComplianceViewGroup($group);


        $aqfGroup = new ComplianceViewGroup('aqf_group', 'Alternate Qualification Form');

        $formView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $formView->setReportName('4. Or, if needed, turn-In a Completed Alternate Qualification Form <a href="/resources/10384/2019_TRS_AQF_071819.pdf">Click here</a> to	download the form and details.');
        $formView->setName('alternate_qualification_form');
        $aqfGroup->addComplianceView($formView);

        $this->addComplianceViewGroup($aqfGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $numCompliant = 0;
        foreach($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getComplianceView()->getName() == 'biometrics') continue;

            if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numCompliant++;
        }

        $screeeningViewStatus = $status->getComplianceViewStatus('complete_screening');
        if($screeeningViewStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT){
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
    }
}

class TransportationRepairServices2019ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                height:11in;
                margin: 0 20px;
            }
            <?php endif ?>

            .light {
                width:0.3in;
            }

            #results {
                width:98%;
                margin:0 0.1in;
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
                    <img src="/images/empower/TRS_logo_082018.png" style="height:60px;"  />
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


            <p>TRS is committed to supporting the health and wellbeing of its employees and their families. The first
             step in promoting good health is awareness. That is why we offer the annual wellness screening through Empower Health Services (EHS).</p>

            <p>
                TRS is offering 2 incentives related to the wellness program: A reward for being tobacco free; and, the
                second is for certain results being within the healthy range. Each reward has alternative actions if
                needed. <br />
                <ul style="list-style-type: upper-alpha">
                    <li>
                        <strong>Tobacco Free Reward:</strong> If results are negative for cotinine, it is worth $40 monthly for employees
                        with employee-only coverage or $20 monthly per person (employee and spouse) for employees with
                        employee + spouse or family coverage.
                    </li>
                    <li>
                        <strong>Healthy Measures Rewards:</strong> This includes incentives for 3 key measures: Body Mass Index, LDL
                        Cholesterol, and Triglycerides. Each is worth $20 monthly for employees with employee-only coverage
                        or $10 monthly per person (employee and spouse) for employees with employee + spouse or family coverage.
                    </li>
                </ul>

            </p>

            <p>
                Your employer is committed to helping you achieve your best health. Opportunities for participating in a
                wellness program and related rewards are available to all eligible employees and spouses. If you are unable
                to meet a standard for a reward under this wellness program based on your results or your current health
                condition precludes you from participating in the program, you may use the Alternate Qualification Form
                link found in B4 (below). Work with your doctor to complete the form. Then return it EHS to receive your
                rewards. The deadline, other details, EHS fax # and address are on the form that you can download in B4
                (below). If you have additional questions regarding the details of this program, please contact EHS at 866.367.6974
            </p>

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
                        <th style="text-align: left; width: 260px;">First action steps:</th>
                        <th colspan="2">Target Deadline</th>
                        <th colspan="4">Date Done</th>
                        <th>Goal Met</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('core')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td colspan="2"><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td colspan="4"><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left;">A. Tobacco Free Reward To-Dos</th>
                        <th colspan="2">Goal by 11/12/19</th>
                        <th colspan="2">2019 Result</th>
                        <th style="width:150px;">Lessons Completed</th>
                        <th>$Value/Family/Yr</th>
                        <th>Goal Met</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('tobacco')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td colspan="2"><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td colspan="2"><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo $viewStatus->getAttribute('lessons_completed') ?> Completed / <?php echo $viewStatus->getAttribute('elearning_link') ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('value') ?></td>
                            <td>
                                <?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left;">B. Healthy Measures Reward To-Do</th>
                        <th>Goal by 11/12/19</th>
                        <th>2018 Result</th>
                        <th>2019 Result</th>
                        <th>Change 2019-2018</th>
                        <th>Lessons Completed</th>
                        <th>$Value/Family/Yr</th>
                        <th>Goal Met</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
                          <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td><?php echo $viewStatus->getAttribute('last_year_result') ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo $viewStatus->getAttribute('difference') ?></td>
                            <td><?php echo $viewStatus->getAttribute('lessons_completed') ?> Completed / <?php echo $viewStatus->getAttribute('elearning_link') ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('value') ?></td>
                            <td>
                                <?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <?php foreach($status->getComplianceViewGroupStatus('aqf_group')->getComplianceViewStatuses() as $viewStatus) : ?>
                          <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;" colspan="4"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td colspan="3"><?php echo $viewStatus->getComment() ?></td>
                            <td>
                                <?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
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