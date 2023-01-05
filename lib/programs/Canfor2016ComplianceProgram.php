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

class Canfor2016ComplianceProgram extends ComplianceProgram
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
        return new Canfor2016ComplianceProgramReportPrinter();
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
        $screeningView->setAttribute('goal', '12/09/16');
        $screeningView->setAttribute('incentive_compliant_points', 115);
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('2. Complete the EHA');
        $hraView->setName('complete_hra');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setAttribute('goal', '12/09/16');
        $coreGroup->addComplianceView($hraView);


        $this->addComplianceViewGroup($coreGroup);

        $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
        $group->setPointsRequiredForCompliance(4);

        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd, true);
        $bmiView->setReportName('1. BMI / Body Mass Index');
        $bmiView->overrideTestRowData(null, null, 32, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('goal', '≤32.0');
        $bmiView->setAttribute('incentive_not_earned', '$0 / See Note * Still possible ?');
        $bmiView->setAttribute('incentive_earned', '$95 / mo');
        $bmiView->setAttribute('incentive_goal', '$95 /mo ($1,140/yr)');
        $bmiView->setAttribute('incentive_compliant_points', 95);
        $bmiView->setAttribute('screening_view', true);
        $group->addComplianceView($bmiView);


        $tobaccoView = new PlaceHolderComplianceView(ComplianceViewStatus::NA_COMPLIANT);
        $tobaccoView->setReportName('2. Complete the Tobacco Affidavit');
        $tobaccoView->setName('tobacco');
        $tobaccoView->setAttribute('goal', 'Non User');
        $tobaccoView->setAttribute('incentive_not_earned', '$0 / See Note * Still possible ?');
        $tobaccoView->setAttribute('incentive_earned', '$95 / mo');
        $tobaccoView->setAttribute('incentive_goal', '$95 /mo ($1,140/yr)');
        $tobaccoView->setAttribute('incentive_compliant_points', 95);
        $tobaccoView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                $status->setComment('No Response');
            } elseif ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
                $status->setComment('User');
            } elseif ($status->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setComment('Non User');
            }
        });
        $group->addComplianceView($tobaccoView);

        $this->addComplianceViewGroup($group);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $screeningStatus = $status->getComplianceViewStatus('complete_screening');
        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        $totalPoints = 0;

        if($screeningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $totalPoints += $screeningStatus->getComplianceView()->getAttribute('incentive_compliant_points');
        }

        foreach($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $totalPoints += $viewStatus->getComplianceView()->getAttribute('incentive_compliant_points');
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

class Canfor2016ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                <div style="float: left;">
                    <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
                    4255 Westbrook Drive #223<br />
                    Aurora, IL 60504
                </div>

                <div style="float: right;">
                    <img src="/images/empower/canfor__logo.png" style="height:66px;"  />
                </div>
            </p>

            <p style="margin-left:3in; padding-top:.56in; clear: both;">
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


            <p><?php echo $client->getName() ?> is committed to encourage and help support the health, care and wellbeing
                of everyone at work and their families – in part, through the wellness screenings, incentives
                and other resources offered each year.</p>

            <p>Your participation in a wellness screening is one of many actions you take that can benefit you
                throughout life.</p>

            <p>Earning the incentive this year involves completing a wellness screening, the Empower Health
                Assessment (EHA) and getting certain results in a goal range. These requirements, your results
                and incentive status are in the table below.  When you meet the required goals for the incentive,
                your name will be on a list that will be sent to <?php echo $client->getName() ?>.  Note about privacy:  As always, individual
                screening results are not shared with employers and remain confidential.</p>

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
                            Thank you for getting your wellness screening this year. This and many of your other
                            actions reflect how you value your own wellbeing and the wellbeing of others at home
                            and work.
                        </p><br />

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

            <div style="clear: both;"></div>

            <div id="root_note">
                <div style="padding: 10px;">
                    <p>
                        *  The incentive savings amount is the surcharge that will be avoided if you met the goal(s) for each incentive.
                    </p>

                    <p style="color: red;">
                        Note: You may still be able earn missing incentives by meeting the goal or Health Coaching Option
                        requirements. Details were provided at the screening. These coaching option details can also be
                        downloaded by <a href="/resources/8965/Canfor 2017 Alt Standard BMI Tobacco Rev 03101.pdf" target="_blank">clicking here</a>
                        and are available at the <?php echo $client->getName() ?> Human Resources department.
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

        ob_start();
        ?>
        <p style="text-align:center">
            <table id="results">
                <tbody>
                    <tr class="headerRow">
                        <th style="text-align: left; width: 260px;">A. Incentive Actions – both A1 or A1&A2 must be done to get this incentive.</th>
                        <th>Date Done</th>
                        <th>Goal Deadline</th>
                        <th>Goal Met</th>
                        <th>Possible Incentive* Savings if Goal  Met</th>
                        <th>My Actual Incentive * Savings Earned</th>
                    </tr>

                    <tr class="status-<?php echo $screeningStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $screeningStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $screeningStatus->getComment() ?></td>
                        <td><?php echo $screeningStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                        <td rowspan="2" style="<?php echo $screeningStatus->getStatus() != ComplianceStatus::COMPLIANT ? 'background-color: #FFFFFF' : ''?>">
                            $115/mo ($1,380/yr)
                        </td>
                        <td rowspan="2"  style="<?php echo $screeningStatus->getStatus() != ComplianceStatus::COMPLIANT ? 'background-color: #FFFFFF' : ''?>">
                            <?php echo $screeningStatus->getStatus() == ComplianceStatus::COMPLIANT ? '$115/ mo' : '$0/mo'?>
                        </td>
                    </tr>

                    <tr class="status-<?php echo $hraStatus->getStatus() ?>">
                        <td style="text-align: left;"><?php echo $hraStatus->getComplianceView()->getReportName() ?></td>
                        <td><?php echo $hraStatus->getComment() ?></td>
                        <td><?php echo $hraStatus->getComplianceView()->getAttribute('goal') ?></td>
                        <td><?php echo $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?></td>
                    </tr>

                    <tr class="headerRow">
                        <th style="text-align: left;">B. Incentive Measures</th>
                        <th>My Result</th>
                        <th>Goal Range</th>
                        <th>Goal Met</th>
                        <th></th>
                        <th></th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('goal') ?></td>
                            <td>
                                <?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Yes' : 'No' ?>
                            </td>
                            <td>
                                <?php echo $viewStatus->getComplianceView()->getAttribute('incentive_goal') ?>
                            </td>
                            <td style="<?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'background-color: #90FF8C' : 'background-color: #ffb3b3' ?>">
                                <?php echo $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT
                                    ? $viewStatus->getComplianceView()->getAttribute('incentive_earned')
                                    : $viewStatus->getComplianceView()->getAttribute('incentive_not_earned') ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left;" colspan="4">C. Incentive Requirement Summary</th>
                        <th>Maximum Possible</th>
                        <th>My Savings Amount *</th>
                    </tr>

                    <tr>
                        <th colspan="4">
                            Totals -> Congrats if you are getting the maximum possible.  <br />
                            If less, see the note ** below.
                        </th>
                        <th><strong>$305 / mo ($3,660/yr)</strong></th>
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