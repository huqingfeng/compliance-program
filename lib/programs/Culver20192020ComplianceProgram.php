<?php
class Culver20192020ScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        ?>
        <br/>
        <br/>
        <br/>
        <style>
            tr {
                border-bottom: 1px solid #fff;
            }

            td {
                padding: 10px 0px;
            }

            hr.divisor {
                width: 50px;
                margin: 2px 0px;
                border-top: 1px solid #444;
                border-bottom: none;
            }
        </style>
        <table border="0" width="95%" id="ratingsTable">
            <tbody>
            <tr>
                <td width="190">
                    Risk ratings &amp; colors =
                </td>
                <td align="center" width="72">
                    <strong><font color="#006600">OK/Good</font></strong></td>
                <td align="center" width="73">
                    <strong><font color="#ff9933">Borderline</font></strong></td>
                <td align="center" width="112">
                    <strong><font color="#ff0000">At-Risk</font> </strong></td>
            </tr>
            <tr>
                <td>
                    &nbsp;</td>
                <td align="center" width="72">
                    &nbsp;</td>
                <td align="center" width="73">
                    &nbsp;</td>
                <td align="center" width="112">
                    &nbsp;</td>
            </tr>
            <tr height="36px" style="border: none;">
                <td>
                    <p>
                        <em>Points for each result<br>
                        </em><em>that falls in this column =</em></p>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72" class="grayArrow">
                    10 points
                </td>
                <td bgcolor="#ffff00" align="center" width="73" class="grayArrow">
                    5 points
                </td>
                <td bgcolor="#ff909a" align="center" width="112" class="grayArrow">
                    0 points
                </td>
            </tr>
            <tr>
                <td>
                    <u>Key measures and ranges</u></td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    &nbsp;</td>
                <td bgcolor="#ffff00" align="center" width="73">
                    &nbsp;</td>
                <td bgcolor="#ff909a" align="center" width="112">
                    &nbsp;</td>
            </tr>
            <tr>
                <td>
                    <ol>
                        <li>
                            <strong>Total cholesterol</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    100 - <200<br><br>
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    200 - 240<br>
                    90 - <100
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    > 240<br>
                    < 90
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="2">
                        <li>
                            <strong>HDL cholesterol</strong>
                            <ul>
                                <li>Men</li>
                                <li>Woman</li>
                            </ul>
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    ≥ 40<br>
                    ≥ 50
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    25 < 40<br>
                    25 - <50
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    < 25<br>
                    < 25
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="3">
                        <li>
                            <strong>LDL cholesterol</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    ≤ 99
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    100 - 159
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 160
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="4">
                        <li>
                            <strong>Non-HDL cholesterol</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    ≤ 129
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    130 - 159
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 160
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="5">
                        <li>
                            <strong>Triglycerides</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    < 150
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    150 - <200
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 200
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <ol start="6">
                        <li>
                            <strong>Glucose (Fasting)</strong>
                            <ul>
                                <li>Men</li>
                                <br>
                                <br>
                                <li>Women</li>
                            </ul>
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72" valign="top">
                    <br>
                    70 - <100<br><br><br>
                    70 - <100
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    <br>
                    100 - 125<br>
                    50 - <70<br><br>
                    100 - 125<br>
                    40 - <70
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    <br>
                    ≥ 126<br>
                    < 50<br><br>
                    ≥ 126<br>
                    < 40
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="7">
                        <li>
                            <strong>Blood pressure</strong><br>
                            Systolic<hr class="divisor">
                            Diastolic
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    < 120<hr class="divisor">
                    < 80
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    120 - 139<hr class="divisor">
                    80 - 89
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 140<hr class="divisor">
                    ≥ 90
                </td>
            </tr>
            <tr>
                <td valign="bottom">
                    <ol start="8">
                        <li>
                            The better of:<br>
                            <strong>Body Mass Index&nbsp;&nbsp;<br>
                            </strong>•&nbsp; men &amp; women<br>
                            - OR -<br>
                            <strong>% Body Fat:</strong><br>
                            • Men<br>
                            • Women
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
                    <p>
                        18.5 - <25<br><br><br>
                        6 - <18%<br>
                        14 - <25%</p>
                </td>
                <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
                    <p>
                        25 - <30<br>
                        <br>
                        <br>
                        18 - <25%<br>
                        25 - <32%</p>
                </td>
                <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
                    <p>
                        ≥30; <18.5<br>
                        <br>
                        <br>
                        ≥25; <6%<br>
                        ≥32; <14%</p>
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="9">
                        <li>
                            <strong>Tobacco/Cotinine</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    < 2
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    2 - 9
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 10
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }
}




class Culver20192020ComplianceProgram extends ComplianceProgram
{
    private function getEvaluateComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        $program = $this->cloneForEvaluation($startDate, $endDate);

        $program->evaluateOverall = false;

        $program->setActiveUser($user);

        return $program->getStatus();
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if(!$this->evaluateOverall) {
            return;
        }



    }

    public function loadGroups()
    {

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());


        $coreGroup = new ComplianceViewGroup('core', 'Get these 2 core actions done by December 15, 2019 to keep your<br/> premium contributions low, saving at least $300-2,000+ a year in 2020.');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete the Wellness Screening via Oct-Dec Screenings or Doctor');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('goal', '2019/12/15');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Details/Schedule <br />', '/content/1051'));
        $screeningView->addLink(new Link('Doctor Form <br />', '/resources/10420/Culver_Physician_PCP_Option_form_091819.pdf'));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete the Health Power Assessment HRA');
        $hraView->setAttribute('goal', '2019/12/15');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do/Results', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);


        $extraGroup = new ComplianceViewGroup('extra', 'Meet goal B1 below by December 15, 2019 to avoid a $300/year<br /> tobacco/nicotine surcharge ($25/month) in 2020.');

        $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('Be Tobacco/Nicotine Free * (a Negative cotinine result from A1)');
        $cotinineView->setName('cotinine');
        $cotinineView->emptyLinks();
        $cotinineView->setAttribute('goal', 'Negative');
        $cotinineView->setAttribute('screening_view', true);
        $cotinineView->addLink(new Link('Results', '/content/989'));
        $extraGroup->addComplianceView($cotinineView);

        $tobaccoProgram = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $tobaccoProgram->setAllowPointsOverride(true);
        $tobaccoProgram->setName('tobacco_program');
        $tobaccoProgram->setReportName('Complete program offered by St. Joseph to be tobacco/free - <a href="/content/coming_soon">click here for details</a>');
        $tobaccoProgram->setAttribute('goal', 'Submit Form');
        $tobaccoProgram->addLink(new Link('Form', '/content/coming_soon'));
        $extraGroup->addComplianceView($tobaccoProgram);

        $doctor = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $doctor->setAllowPointsOverride(true);
        $doctor->setName('doctor');
        $doctor->setReportName('Work with a doctor (OurHealth, other) to be tobacco/free - <a href="/content/coming_soon">click here for details</a>');
        $doctor->setAttribute('goal', 'Submit Form');
        $doctor->addLink(new Link('Form', '/content/coming_soon'));
        $extraGroup->addComplianceView($doctor);

        $elearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'tobacco');
        $elearningView->setReportName('<a href="/sitemaps/health_centers/15946">Tips, Resources & e-lessons</a>');
        $elearningView->setName('elearning');
        $elearningView->setNumberRequired(6);
        $elearningView->setAttribute('goal', 'Strive for 6 or more lessons');
        $elearningView->emptyLinks();
        $elearningView->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias[]=tobacco'));
        $extraGroup->addComplianceView($elearningView);


        $this->addComplianceViewGroup($extraGroup);

        $rewardGroup = new ComplianceViewGroup('reward', 'More reward opportunities in 2020 - coming soon!');

        $this->addComplianceViewGroup($rewardGroup);

    }


    public function getLocalActions()
    {

    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Culver20192020ScreeningPrinter();

            $query = ScreeningTable::getInstance()
                ->getScreeningsForUser($this->getActiveUser(), array('execute' => false));
            $screenings = ScreeningTable::getInstance()
                ->getScreeningForDates($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), array('query' => $query));
            $screening = $screenings->getFirst();

            if($screening) {
                $printer->setPageTitle('Points Earned From My Wellness Screening On '.$screening->getDate());
            } else {
                $printer->setPageTitle('You have not had a Wellness Screening.');
            }
        } else {
            $printer = new Culver20192020ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, true);

        return $printer;
    }

    protected $evaluateOverall = true;
}

class Culver20192020ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');


        $extraGroupStatus = $status->getComplianceViewGroupStatus('extra');
        $cotinineStatus = $extraGroupStatus->getComplianceViewStatus('cotinine');
        $tobaccoProgramStatus = $extraGroupStatus->getComplianceViewStatus('tobacco_program');
        $doctorStatus = $extraGroupStatus->getComplianceViewStatus('doctor');
        $elearningStatus = $extraGroupStatus->getComplianceViewStatus('elearning');

        $rewardGroupStatus = $status->getComplianceViewGroupStatus('reward');

        ?>
        <script type="text/javascript">
            $(function() {

            });
        </script>

        <style type="text/css">
            .phipTable ul, .phipTable li {
                margin-top:0px;
                margin-bottom:0px;
                padding-top:0px;
                padding-bottom:0px;
            }

            .pageHeading {
                font-weight:bold;
                text-align:center;
                margin-bottom:20px;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .phipTable .headerRow {
                background-color:#0080ff;
                color: #FFFFFF;
                font-weight:bold;
                font-size:10pt;
                height:46px;
            }

            .phipTable .headerRow th {
                text-align:left;
                font-weight:normal;
            }

            .phipTable .headerRow td {
                text-align:center;
            }

            .phipTable .links {
                text-align:center;
            }

            .center {
                text-align:center;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .right {
                text-align:right;
            }

            #legend td {

                padding-bottom:5px;
            }

        </style>
        <div class="pageHeading">Rewards/To-Do Summary Page</div>
        <p>Hello <?php echo $status->getUser() ?>,</p>
        <p></p>
        <p>Welcome to your summary page for the Culver Academies Wellness Rewards from $300 to over $2,800 in 2020.</p>

        <p>To receive the rewards, eligibile employees and spouses MUST EACH take certain actions and meet the criteria specified below.</p>

        <p>For example,  both people each meet the action goals below in A and B to get the greatest rewards currently available.</p>

        <p>Thank you, in advance, for getting these things done soon for your wellbeing and the related rewards.</p>

        <p>Click here for other benefits and tips.</p>


        <table class="phipTable" border="1">
            <tbody>
            <tr class="headerRow">
                <th>A. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td style="width:100px;">Deadline Goal</td>
                <td>Completed</td>
                <td>Goal Met</td>
                <td>Action Links</td>
            </tr>

            <tr>
                <td>1. <?php echo $completeScreeningStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $completeScreeningStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center">
                    <?php echo $completeScreeningStatus->getComment(); ?>
                </td>
                <td class="center">
                    <?php echo $completeScreeningStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?>
                </td>
                <td class="links">
                    <?php
                    foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>2. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $completeHRAStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center">
                    <?php echo $completeHRAStatus->getComment(); ?>
                </td>
                <td class="center">
                    <?php echo $completeHRAStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?>
                </td>
                <td class="links">
                    <?php
                    foreach($completeHRAStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>


            <tr class="headerRow">
                <th>B. <?php echo $extraGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td>Goal (Result or Deadline)</td>
                <td>Result</td>
                <td>Goal Met</td>
                <td>Action Links</td>
            </tr>

            <tr>
                <td>1. <?php echo $cotinineStatus->getComplianceView()->getReportName() ?></td>
                <td class="center"><?php echo $cotinineStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center">
                    <?php echo $cotinineStatus->getComment(); ?>
                </td>
                <td class="center" rowspan="5">
                    <?php echo $extraGroupStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Yes' : 'No' ?>
                </td>
                <td class="links">
                    <?php
                    foreach($cotinineStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td colspan="3">
                    2. Support available to become tobacco/nicotine free:
                </td>
                <td></td>
            </tr>

            <tr>
                <td style="padding-left: 20px;">
                    a) <?php echo $tobaccoProgramStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="center"><?php echo $tobaccoProgramStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center">
                    <?php echo $tobaccoProgramStatus->getComment(); ?>
                </td>

                <td class="links">
                    <?php
                    foreach($tobaccoProgramStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>


            <tr>
                <td style="padding-left: 20px;">
                    b) <?php echo $doctorStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="center"><?php echo $doctorStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center">
                    <?php echo $doctorStatus->getComment(); ?>
                </td>

                <td class="links">
                    <?php
                    foreach($doctorStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td style="padding-left: 20px;">
                    c) <?php echo $elearningStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="center"><?php echo $elearningStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td class="center">
                    <?php echo count($elearningStatus->getAttribute('lessons_completed')); ?>
                </td>

                <td class="links">
                    <?php
                    foreach($elearningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td colspan="5">
                    Note:  If you or your covered spouse do not meet the goal for B by 12/15/19, the $25/month surcharge begins 1/1/2020. <br />
                    *  For reasonable alternatives, see Human Resources.
                </td>
            </tr>

            <tr class="headerRow">
                <th>C. <?php echo $rewardGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>

            </tbody>
        </table>

        <?php
    }
}
