<?php
use hpn\steel\query\SelectQuery;


class VillageofHuntleyLearningAlternativeComplianceView extends ComplianceView
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

class VillageofHuntley2022ComplianceProgram extends ComplianceProgram
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
        return new VillageofHuntley2022ComplianceProgramReportPrinter();
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

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Participate in the Biometric Screening by 3/2/2022');
        $screeningView->setName('complete_screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('View Full Results', '/content/my-health'));
        $coreGroup->addComplianceView($screeningView);



        $this->addComplianceViewGroup($coreGroup);

        $group = new ComplianceViewGroup('biometrics', 'Biometrics');
        $group->setPointsRequiredForCompliance(1);


        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setReportName('Blood Pressure of less than 130/90');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, null);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 89.999, null);
        $bloodPressureView->emptyLinks();
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bloodPressureView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($bloodPressureView,'blood_pressure');
        $group->addComplianceView($bloodPressureView);

        $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $gluView->setReportName('Glucose reading of less than 100');
        $gluView->overrideTestRowData(null, null, 99.999, null);
        $gluView->emptyLinks();
        $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $gluView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($gluView,'blood_sugars');
        $group->addComplianceView($gluView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setReportName('Triglycerides of less than 150');
        $trigView->overrideTestRowData(null, null, 149.999, null);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0 ,0));
        $trigView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($trigView,'cholesterol');
        $group->addComplianceView($trigView);


        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setReportName('Body Mass Index of less than 27');
        $bmiView->overrideTestRowData(null, null, 26.999, null);
        $bmiView->emptyLinks();
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bmiView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($bmiView,'body_fat');
        $group->addComplianceView($bmiView);


        $ldlRatioView = new ComplyWithTotalLDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $ldlRatioView->setReportName('LDL Cholesterol of less than 100');
        $ldlRatioView->overrideTestRowData(null, null, 99.99, null);
        $ldlRatioView->emptyLinks();
        $ldlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $ldlRatioView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($ldlRatioView,'cholesterol');
        $group->addComplianceView($ldlRatioView);


        $tobaccoView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $tobaccoView->setReportName('Tobacco User - Negative Test result');
        $tobaccoView->emptyLinks();
        $tobaccoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoView->setAttribute('screening_view', true);
        $this->configureViewForElearningAlternative($tobaccoView,'tobacco');
        $group->addComplianceView($tobaccoView);


        $this->addComplianceViewGroup($group);


        $alternativeGroup = new ComplianceViewGroup('alternative');

        $alternateForm =  new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $alternateForm->setName('alternate_qualification_form');
        $alternateForm->setReportName('Alternate Qualification Form');
        $alternativeGroup->addComplianceView($alternateForm);

        $this->addComplianceViewGroup($alternativeGroup);
    }


    protected function configureViewForElearningAlternative(ComplianceView $view, $alias)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($alias) {
            $view = $status->getComplianceView();

            $startDate = $view->getStartDate('Y-m-d');
            $endDate = $view->getEndDate('Y-m-d');

            $noncompliantValues = array('QNS', 'TNP', "DECLINED");

            if (in_array(strtoupper($status->getAttribute('real_result')), $noncompliantValues)) {
                $status->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
            }

            $screening = SelectQuery::create()
                ->hydrateSingleScalar()
                ->select('id')
                ->from('screening')
                ->where('user_id = ?', [$user->getId()])
                ->andWhere('date BETWEEN ? and ?', array($startDate, $endDate))
                ->execute();

            if($screening) {
                $numberRequired = 2;

                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), $alias);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());
                $elearningView->useAlternateCode(true);
                $elearningView->setNumberRequired($numberRequired);

                $view->addLink(new Link('Complete Lessons', "/content/9420?action=lessonManager&tab_alias={$alias}"));

                $elearningStatus = $elearningView->getStatus($user);

                if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceViewStatus::COMPLIANT);
                }

            }
        });
    }


    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $healthGroupStatus = $status->getComplianceViewGroupStatus('biometrics');

        $alterStatus = $status->getComplianceViewStatus('alternate_qualification_form');

        if($alterStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT) {
            foreach($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {
                $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
                $viewStatus->setPoints(1);
            }
        }

        $numCompliant = 0;
        foreach($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numCompliant++;
        }

        $status->setPoints($numCompliant);


        if($healthGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT){
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
    }
}

class VillageofHuntley2022ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
                    <img src="/images/empower/village_of_huntley_logo.png" style="height:100px;"  />
                </div>
            </p>


            <p>&nbsp;</p>

            <p>
                <span style="width:88%; display: inline-block">Dear <?php echo $user->first_name ?>,</span>
                <span style="width:10%; display: inline-block"><?php echo date('m/d/Y') ?></span>
            </p>


            <p>
                Welcome to the 2022 Wellness Incentive Program, provided for all full time and part time employees. The
                financial incentive is based on completing the biometric screening as well as passing six metric criteria
                listed below. The financial incentive will be $50.00 for each metric passed for a maximum of $300.00 total
                incentive.
            </p>

            <p>
                If you are unable to reach the healthy metric targets, two reasonable alternatives options are offered:
            </p>

            <p>
                The first reasonable alternative is to follow up with your own physician to have him or her complete the
                alternate qualification form and submit back to Empower Health no later than April 4, 2022. The completed
                form may be faxed into Empower Health at 630-385-0156.
            </p>

            <p>
                The second alternative option is to complete a total of 2 e-learning lessons on the metric criteria you
                did not pass. You must complete any/all e-learning lessons by April 4, 2022.
            </p>

            <p>
                If you complete either reasonable alternative, you will automatically receive a pass for all six required
                metrics and receive the full $300.00 financial incentive. If you do not complete either reasonable incentive,
                you will only receive an incentive amount for any passing metrics on your biometric screening.
            </p>

            <?php echo $this->getTable($status) ?>

            <p style="text-align: center; font-weight: bold;">
                Should you have any questions or need additional assistance,<br /> please contact Empower Health by calling 866.367.6974
            </p>


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
                        <th style="text-align: left; width: 260px;">A. ACTION ITEMS</th>
                        <th>Date Completed</th>
                        <th>Completed</th>
                        <th>Links</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('core')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><?php echo $viewStatus->getComplianceView()->getReportName() ?></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><img src="<?php echo $viewStatus->getLight(); ?>" class="light"/></td>
                            <td>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left; width: 300px;">B. BIOMETRICS</th>
                        <th>Your Results</th>
                        <th>Pass/Fail</th>
                        <th>Links</th>
                    </tr>

                    <?php foreach($status->getComplianceViewGroupStatus('biometrics')->getComplianceViewStatuses() as $viewStatus) : ?>
                        <tr class="status-<?php echo $viewStatus->getStatus() ?>">
                            <td style="text-align: left;"><span style=" margin-left:10px;">&bull; <?php echo $viewStatus->getComplianceView()->getReportName() ?></span></td>
                            <td><?php echo $viewStatus->getComment() ?></td>
                            <td><img src="<?php echo $viewStatus->getLight(); ?>" class="light"/></td>
                            <td>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) {
                                    echo $link->getHTML()."\n";
                                }?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                    <tr class="headerRow">
                        <th style="text-align: left;" colspan="4">REASONABLE ALTERNATIVE OPTIONS - Deadline is 4/4/2022 for either option</th>
                    </tr>

                    <tr>
                        <td rowspan="1">Visit primary care physician to complete and submit Alternate Qualification Form</td>
                        <td></td>
                        <td></td>
                        <td>
                            <a href="/resources/10729/Village_of_Huntley_2021_AQF_102221.pdf" download="Village_of_Huntley_2021_AQF">Download Form</a>
                        </td>
                    </tr>

                    <tr>
                        <td rowspan="1">Complete 2 E-learning lessons on each criteria you did not pass</td>
                        <td></td>
                        <td></td>
                        <td>
                            See Links Above
                        </td>
                    </tr>


                    <tr class="headerRow">
                        <th style="text-align: left; width: 300px;">D. INCENTIVE STATUS</th>
                        <th>Metrics Incentive Earned</th>
                        <th>Pass/Fail</th>
                        <th></th>
                    </tr>

                    <tr>
                        <td></td>
                        <td><?php echo $status->getPoints() ?></td>
                        <td><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
                        <td></td>
                    </tr>

                </tbody>
            </table>
        </p>
        <?php

        return ob_get_clean();
    }
}