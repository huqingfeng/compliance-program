<?php

use hpn\steel\query\SelectQuery;

class Glenbrook20172018SpouseSemesterWrapperView extends ComplianceView
{
    public static function setFirstSemesterOnly($bool)
    {
        self::$firstSemesterOnly = $bool;
    }

    public function __construct(DateBasedComplianceView $view, $firstSemesterStartDate = false)
    {
        $this->view = $view;
        $this->firstSemesterStartDate = $firstSemesterStartDate;
    }

    public function setSemesterPoints($points)
    {
        $this->semesterPoints = $points;
    }

    public function getDefaultReportName()
    {
        return $this->view->getDefaultReportName();
    }

    public function getDefaultName()
    {
        return $this->view->getDefaultName();
    }

    public function getDefaultStatusSummary($status)
    {
        return $this->view->getDefaultStatusSummary($status);
    }

    public function getReportName($forHTML = false)
    {
        return $this->view->getReportName($forHTML);
    }

    public function getName()
    {
        return $this->view->getName();
    }

    public function getLinks()
    {
        return $this->view->getLinks();
    }

    public function getMaximumNumberOfPoints()
    {
        return 2 * $this->view->getMaximumNumberOfPoints();
    }

    public function getStatusSummary($status)
    {
        return $this->view->getStatusSummary($status);
    }

    public function getStatus(User $user)
    {
        $this->view->setMaximumNumberOfPoints($this->semesterPoints);

        if($this->firstSemesterStartDate) {
            $this->view->setStartDate($this->firstSemesterStartDate);
        } else {
            $this->view->setStartDate('2017-05-02');
        }

        $this->view->setEndDate('2017-12-15');

        $semesterOneStatus = $this->view->getMappedStatus($user);

        $this->view->setStartDate('2017-12-16');
        $this->view->setEndDate('2018-04-30');

        $semesterTwoStatus = $this->view->getMappedStatus($user);

        if(self::$firstSemesterOnly) {
            return new ComplianceViewStatus($this, null, $semesterOneStatus->getPoints());
        } else {
            return new ComplianceViewStatus($this, null, $semesterOneStatus->getPoints() + $semesterTwoStatus->getPoints());
        }
    }

    private static $firstSemesterOnly = false;
    private $semesterPoints;
    private $view;
}

class Glenbrook20172018SpouseComplianceProgram extends ComplianceProgram
{
    /**
     * Redirects users to the registration page if they are not registered.
     *
     * @param sfActions $actions
     */

    public function getRaffleTickets(ComplianceProgramStatus $status)
    {
        $oneAb = $status->getComplianceViewStatus('complete_hra')->isCompliant() &&
            $status->getComplianceViewStatus('complete_screening')->isCompliant();

        $program = $status->getComplianceProgram();

        $firstSemesterProgram = $program->cloneForEvaluation($program->getStartDate(), $program->getEndDate());

        $firstSemesterProgram->setActiveUser($status->getUser());

        Glenbrook20172018SpouseSemesterWrapperView::setFirstSemesterOnly(true);

        $firstSemesterProgramStatus = $firstSemesterProgram->getStatus();

        Glenbrook20172018SpouseSemesterWrapperView::setFirstSemesterOnly(false);

        return array(
            '2017_screening_raffle_tickets'          => $oneAb ? 1 : 0,
            '2017_winter_i_gift_card_raffle_tickets' => floor($firstSemesterProgramStatus->getPoints() / 100),
            '2018_spring_raffle_tickets'             => floor($status->getPoints() / 100),
            '2018_wearable_award_tickets'            => $status->getPoints() >= 400 ? 1 : 0
        );
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();
        $screeningStart = "2017-05-02";
        $screeningEnd = "2017-12-15";

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', '');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->emptyLinks()->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setReportName('Health Power Assessment (HPA)');
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($screeningStart, $screeningEnd);
        $screeningView->setReportName('Annual Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->addLink(new Link('Details', '/content/1075'));
        $screeningView->addLink(new Link('Dr.Option Form', '/resources/9690/GB 2017 Physician Option 082417.pdf'));
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);
    }

    public function getAdminProgramReportPrinter()
    {
        $program = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) use($program) {
            return $program->getRaffleTickets($status);
        });

        $printer->addCallbackField('Building', function(User $user) {
            return $user->getLocation();
        });

        $printer->addCallbackField('Rebate', function(User $user) {
            return $user->miscellaneous_data_1 ? $user->miscellaneous_data_1 : $user->miscellaneous_data_2;
        });

        $printer->addStatusFieldCallback('Shape Your Life Registration', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $start_date = sfConfig::get('app_legacy_mileage_monsters_start_date');
            $end_date = sfConfig::get('app_legacy_mileage_monsters_end_date');

            $user_registration = MileageRegistrants::getRegistrationForUser($user, $start_date, $end_date);

            if($user_registration !== false) {
                return 'Yes';
            } else {
                return 'No';
            }
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Glenbrook20172018SpouseScreeningPrinter();
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
            $printer = new Glenbrook20172018SpouseComplianceProgramReportPrinter();
        }

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2016-08-19';
}

class Glenbrook20172018SpouseScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        ?>
        <br/> <br/> <br/>
        <table border="0" width="100%" style="font-size: 10px;" id="ratingsTable">
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
                </td>
                <td align="center" width="72">
                </td>
                <td align="center" width="73">
                </td>
                <td align="center" width="112">
                </td>
            </tr>
            <tr height="36px">
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
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                </td>
            </tr>
            <tr>
                <td>
                    <ol>
                        <li>
                            <strong>Total cholesterol</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    &lt;200
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    200-240
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    &gt;240
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="2">
                        <li>
                            <strong>HDL cholesterol</strong><br />
                            • Men<br>
                            • Women
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    <br />
                    ≥40<br />
                    ≥50

                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    <br />
                    25-39<br />
                    49-25
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    <br />
                    &lt;25<br />
                    &lt;25
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
                    ≤129
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    130-158
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥159
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="4">
                        <li>
                            <strong>Blood pressure</strong><br>
                            Systolic<br>
                            Diastolic
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
                    &lt;120/<br>
                    &lt;80
                </td>
                <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
                    120-139/<br>
                    80-89
                </td>
                <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
                    ≥140/<br>
                    ≥90
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="5">
                        <li>
                            <strong>Glucose</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    &lt;100
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    100-124
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥125
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="6">
                        <li>
                            <strong>Triglycerides</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    &lt;150
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    150-199
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥200
                </td>
            </tr>
            <tr>
                <td valign="bottom">
                    <ol start="7">
                        <li>
                            The better of:<br>
                            <strong>Body Mass Index <br>
                            </strong>• men &amp; women<br>
                            - OR -<br>
                            <strong>% Body Fat:</strong><br>
                            • Men<br>
                            • Women
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
                    <p>
                        18.5&lt;25<br>
                        <br>
                        <br>
                        6&lt;18%<br>
                        14&lt;25%</p>
                </td>
                <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
                    <p>
                        25&lt;30<br>
                        <br>
                        <br>
                        18&lt;25<br>
                        25&lt;32</p>
                </td>
                <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
                    <p>
                        ≥30; &lt;18.5<br>
                        <br>
                        <br>
                        ≥25; &lt;6%<br>
                        ≥32; &lt;14%</p>
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="8">
                        <li>
                            <strong>Tobacco/Cotinine</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    &lt;2
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    2-9
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥10
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }
}

class Glenbrook20172018SpouseComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function __construct()
    {
        $this->screeningLinkArea = '<br/><br/>
      Green Range = 10 pts <br/>
      Yellow Range = 5 pts<br/>
      Red Range = 0 pts *<br/>
    ';
    }

    protected function showGroup($group)
    {
        return $group->getName() != 'evaluators';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(false);
//        $this->setScreeningResultsLink(new FakeLink('Have These Screening Results in the Ideal Zone:', '/content/1094#2aKBHM'));
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        ?>
        <script type="text/javascript">
            $(function(){
                $('.phipTable tbody th:first-child').html('');
            });
        </script>
        <style type="text/css">
            .pageHeading {
                display:none;
            }

            #altPageHeading {
                font-weight:bold;
                margin-bottom:20px;
                text-align:center;
            }

            .phipTable .headerRow {
                background-color:#26B000;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
            }

            #legend td {
                padding:8px !important;
            }

            .legendEntry {
                width:auto;
                float:right;
                display:inline-block;
                padding:0 8px;
            }

            #legendText {
                text-align:center;
                background-color:#FFF;
                font-weight:normal;
                color:#434343;
                font-size:12pt;
                font-weight:bold;
                float:left;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .links a {
                display:inline-block;
                margin:0 3px;
            }
        </style>

        <div id="altPageHeading">Glenbrook School District 225’s 2017-2018 Spouse Program</div>

        <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>


        <p>Welcome to your Summary Page for the 2017-2018 Glenbrook Medical Premium Rebate. Active Glenbrook employees and
            spouses on the medical plan may qualify for up to $250 in medical premium rebates. Employees can  earn a $175
            Medical Premium Rebate by completing a set of wellness actions. <strong>Additionally</strong>, if you complete the below, $75
            will be added to the total medical premium rebate for a total of $250 dollars.
            <br>To be eligible for the additional rebate you must:
        </p>
        <ol style="list-style-type: none;">
            <li><strong>A. Complete the Health Power Assessment (HPA)</strong></li><br />

            <li><strong>B. Participate in the Annual Wellness Screening (or qualified screening by your own doctor). </strong></li><br />

            <li><strong>These must be completed and submitted by 12/15/2017.</strong></li><br />
        </ol>

        <div class="pageHeading">
            <a href="/content/1094">
                Click here to view the full details of all Reward Activities listed below
            </a>.
</div>


        <?php
    }

    protected function printCustomRows($status)
    {

        ?>
        <tr>
            <td style="text-align:center;">
                <strong>Deadline 12/15/2017</strong>
            </td>
            <td style="text-align:center;">

            </td>
            <td style="text-align:center;" colspan="2">
                <?php if($status->getStatus() == ComplianceStatus::COMPLIANT) : ?>
                Congratulations, you fulfilled your portion of the spouse wellness rebate.
                <?php endif ?>
            </td>
        </tr>

        <?php
    }

    public $showUserNameInLegend = true;
}
