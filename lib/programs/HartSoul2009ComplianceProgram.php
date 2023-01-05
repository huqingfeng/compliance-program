<?php
class HartSoul2009CompleteHRAByDeadlineOrWithin30DaysOfHire extends CompleteHRAComplianceView
{
    public function getStatus(User $user)
    {
        $this->userReference = $user;
        $status = parent::getStatus($user);
        $this->userReference = null;

        return $status;
    }

    public function getEndDate($format = 'U')
    {
        if($this->userReference === null) {
            return parent::getEndDate($format);
        } else if(in_array($this->userReference->id, array(2587631, 2587849))) {
            // If the person is one of the special exceptions, give them through...now..
            // Once we have the namespaced configuration stuff done, this needs to be
            // removed and made configurable.
            return date('Y-m-d');
        } else {
            // They have 30 days after their hire date if they are hired after 09/15/09
            $hireDate = strtotime($this->userReference->hiredate);
            $lastAllowedDay = strtotime('2009-09-15');
            if($hireDate > $lastAllowedDay) {
                return date($format, strtotime('+30 days', $hireDate));
            } else {
                return parent::getEndDate($format);
            }
        }
    }

    private $userReference = null;
}

class HartSoul2009ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by September 15, 2009');

        $hraView = new HartSoul2009CompleteHRAByDeadlineOrWithin30DaysOfHire('2008-08-01', '2009-09-15');
        $hraView->setReportName('Annual Health Power Assessment');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'And,  earn 25 or more points from A-I below each quarter by the deadlines in #3 below.');

        $requiredELearning = new CompleteRequiredELearningLessonsComplianceView($programStart, $programEnd);
        $requiredELearning->setPointsPerLesson(5);

        $extraGroup->addComplianceView($requiredELearning);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(360);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->addLink(new Link('SIM Bonus', '/content/301723'));
        $extraGroup->addComplianceView($physicalActivityView);

        $flushotView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $flushotView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $extraGroup->addComplianceView($flushotView);

        $attendWorkshopView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 5);
        $attendWorkshopView->addLink(new Link('Calendar', '/content/6962'));
        $attendWorkshopView->setReportName('Health Trainings/Events Attended');
        $attendWorkshopView->setMaximumNumberOfPoints(100);
        $extraGroup->addComplianceView($attendWorkshopView);

        $additionalELearningLessonsView = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd, 3);
        $additionalELearningLessonsView->setReportName('Extra e-Learning Lessons Completed');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(30);
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $preventiveScreeningsView = new ObtainPreventiveExamComplianceView($programStart, $programEnd, 5);
        $preventiveScreeningsView->setMaximumNumberOfPoints(25);
        $extraGroup->addComplianceView($preventiveScreeningsView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setReportName('Regular Volunteering - Type & Time');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(100);
        $extraGroup->addComplianceView($volunteeringView);

        $engageView = new EngageLovedOneComplianceView($programStart, $programEnd, 5);
        $engageView->setMaximumNumberOfPoints(25);
        $extraGroup->addComplianceView($engageView);

        $gacGoalsView = new CompleteGoalsComplianceView($programStart, $programEnd, 5);
        $gacGoalsView->setMaximumNumberOfPoints(25);
        $extraGroup->addComplianceView($gacGoalsView);

        $extraGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($extraGroup);
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Totals');

        $firstTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2009-12-20'), array('core'), 25);
        $firstTotal->setName('first_total');
        $secondTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2009-12-21', '2010-03-31'), array('core'), 25);
        $secondTotal->setName('second_total');
        $thirdTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2010-04-01', '2010-06-30'), array('core'), 25);
        $thirdTotal->setName('third_total');
        $fourthTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation('2010-07-01', '2010-09-30'), array('core'), 25);
        $fourthTotal->setName('fourth_total');

        $totalsGroup->addComplianceView($firstTotal, true);
        $totalsGroup->addComplianceView($secondTotal, true);
        $totalsGroup->addComplianceView($thirdTotal, true);
        $totalsGroup->addComplianceView($fourthTotal, true);

        $this->addComplianceViewGroup($totalsGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new HartSoulComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }
}


class HartSoulComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        ;

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');

        $requiredELearningStatus = $pointGroupStatus->getComplianceViewStatus('complete_elearning_required');
        $physicalActivityStatus = $pointGroupStatus->getComplianceViewStatus('activity_21');
        $flushotStatus = $pointGroupStatus->getComplianceViewStatus('activity_20');
        $workshopStatus = $pointGroupStatus->getComplianceViewStatus('activity_23');
        $extraELearningStatus = $pointGroupStatus->getComplianceViewStatus('complete_elearning_additonal');
        $preventiveScreeningsStatus = $pointGroupStatus->getComplianceViewStatus('activity_26');
        $volunteeringStatus = $pointGroupStatus->getComplianceViewStatus('activity_24');
        $engageStatus = $pointGroupStatus->getComplianceViewStatus('activity_25');
        $healthCoachStatus = $pointGroupStatus->getComplianceViewStatus('work_with_health_coach_activity');
        $goalsStatus = $pointGroupStatus->getComplianceViewStatus('complete_gac_goals');

        $totalGroupStatus = $status->getComplianceViewGroupStatus('totals');
        $firstStatus = $totalGroupStatus->getComplianceViewStatus('first_total');
        $secondStatus = $totalGroupStatus->getComplianceViewStatus('second_total');
        $thirdStatus = $totalGroupStatus->getComplianceViewStatus('third_total');
        $fourthStatus = $totalGroupStatus->getComplianceViewStatus('fourth_total');

        ?>
    <style type="text/css">
        .phipTable ul, .phipTable li {
            margin-top:0px;
            margin-bottom:0px;
            padding-top:0px;
            padding-bottom:0px;
        }

        .pageHeading {
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
            background-color:#158E4C;
            font-weight:normal;
            color:#FFFFFF;
            font-size:10pt;
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

        .white {
            background-color:#FFFFFF;
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

        #legend, #legend tr, #legend td {
            padding:0px;
            margin:0px;
        }

        #legend td {

            padding-bottom:5px;
        }

        #legendText {
            text-align:center;
            background-color:#158E4C;
            font-weight:normal;
            color:#FFFFFF;
            font-size:12pt;
            margin-bottom:5px;
        }

        .legendEntry {
            width:160px;
            float:left;
            text-align:center;
            padding-left:2px;
        }
    </style>


    <div class="pageHeading">Incentive/To-Do Summary Page</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the 2009-2010 Hart & Soul Wellness Incentive program. To receive the incentive,
        eligible employees and spouses MUST EACH take action and meet all criteria below:</p>

    <ol>
        <li>Complete <strong>ALL</strong> of the core required actions by September 15, 2009*; AND</li>
        <li>Each quarter, get 25 or more points from key screening results and/or key actions taken for good health - by
            the quarterly deadlines indicated below.
        </li>
    </ol>

    <p>Employees and eligible spouses meeting all criteria will each receive $240 in health insurance premium credit
        ($480/family).</p>

    <div class="pageHeading">
        <div>* If hired after September 15, 2009, #1 must be completed within 30 days of your first day of work.</div>
        <br/>

        <div><a href="/content/1025">Click here for more details about the 2009-2010 Wellness Incentive benefit and
            requirements</a>.
        </div>
    </div>
    <p>
        <strong>Update Notice:</strong> To get actions done and earn extra points click on the links below.
        If the center is not yet Green for any option you did or are working on, you may need to go back and
        enter missing information, or additional entries to earn more points. The center for wellness screening
        will not change until after your report is mailed. <i>Thanks for your actions and patience!</i>
    </p>

    <table class="phipTable" border="1">
    <thead id="legend">
        <tr>
            <td colspan="4">
                <div id="legendText">Legend</div>
                <?php
                foreach($status->getComplianceProgram()->getComplianceStatusMapper()
                            ->getMappings() as $sstatus => $mapping) {
                    $printLegendEntry = false;
                    if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT) {
                        $printLegendEntry = true;
                    } else if($status->getComplianceProgram()->hasPartiallyCompliantStatus()) {
                        $printLegendEntry = true;
                    }

                    if($printLegendEntry) {
                        echo '<div class="legendEntry">';
                        echo '<img src="'.$mapping->getLight().'" class="light" />';
                        echo " = {$mapping->getText()}";
                        echo '</div>';
                    }
                }
                ?>
            </td>
        </tr>
    </thead>
    <tbody>
        <tr class="headerRow">
            <th>1. Core Actions Required by September 15, 2009:</th>
            <td>Date Done</td>
            <td>Status</td>
            <td>Links</td>
        </tr>
        <tr>
            <td>A. Annual Health Power Assessment</td>
            <td class="center">
                <?php echo $completeHRAStatus->getComment(); ?>
            </td>
            <td class="center">
                <img src="<?php echo $completeHRAStatus->getLight(); ?>" class="light"/>
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
            <th>2. And, earn 25 or more points from A-I below each quarter by the deadlines in #3 below.</th>
            <td># Points Earned</td>
            <td># Points Possible</td>
            <td>Links</td>
        </tr>


        <tr>
            <td>A. Recommended e-Learning lessons</td>
            <td class="center"><?php echo $requiredELearningStatus->getPoints(); ?></td>
            <td class="center"><?php echo $requiredELearningStatus->getComplianceView()
                ->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($requiredELearningStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>B. Regular Physical Activity***</td>
            <td class="center"><?php echo $physicalActivityStatus->getPoints(); ?></td>
            <td class="center"><?php echo $physicalActivityStatus->getComplianceView()
                ->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($physicalActivityStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>C. Annual Flu Shot</td>
            <td class="center"><?php echo $flushotStatus->getPoints(); ?></td>
            <td class="center"><?php echo $flushotStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($flushotStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>D. Attend Wellness Seminars or Other Key Events</td>
            <td class="center"><?php echo $workshopStatus->getPoints(); ?></td>
            <td class="center"><?php echo $workshopStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($workshopStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>E. Extra e-Learning Lessons Completed</td>
            <td class="center"><?php echo $extraELearningStatus->getPoints(); ?></td>
            <td class="center"><?php echo $extraELearningStatus->getComplianceView()
                ->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($extraELearningStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>F. Preventive Screenings & Exams Obtained</td>
            <td class="center"><?php echo $preventiveScreeningsStatus->getPoints(); ?></td>
            <td class="center"><?php echo $preventiveScreeningsStatus->getComplianceView()
                ->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($preventiveScreeningsStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>G. Regular Volunteering – Type & Time</td>
            <td class="center"><?php echo $volunteeringStatus->getPoints(); ?></td>
            <td class="center"><?php echo $volunteeringStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($volunteeringStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>H. Engage a Loved One/Friend Toward Better Health</td>
            <td class="center"><?php echo $engageStatus->getPoints(); ?></td>
            <td class="center"><?php echo $engageStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($engageStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>I. Create and Track Health Improvement Goals</td>
            <td class="center"><?php echo $goalsStatus->getPoints(); ?></td>
            <td class="center"><?php echo $goalsStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            <td class="links">
                <?php
                foreach($goalsStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td class="right">Total number as of <?php echo date('m/d/Y'); ?> =</td>
            <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
            <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()
                ->getMaximumNumberOfPoints(); ?></td>
            <td></td>
        </tr>
        <tr class="headerRow">
            <th>3. Deadlines, Requirements & Status</th>
            <td># Earned</td>
            <td>Status</td>
            <td>Minimum Needed for Each Quarterly Credit</td>
        </tr>
        <tr>
            <td style="text-align: right;">Total & Credit Status: Through <strong>12/20/2009</strong> =</td>
            <td class="center"><?php echo $firstStatus->getPoints(); ?></td>
            <td class="center"><img src="<?php echo $firstStatus->getLight(); ?>" class="light"/></td>
            <td class="center">25 or more points + 1A Done</td>
        </tr>
        <tr>
            <td style="text-align: right;">Points from 12/21/2009 through <strong>03/31/2010</strong> =</td>
            <td class="center"><?php echo $secondStatus->getPoints(); ?></td>
            <td class="center"><img src="<?php echo $secondStatus->getLight(); ?>" class="light"/></td>
            <td class="center">25 or more points + 1A done</td>
        </tr>
        <tr>
            <td style="text-align: right;">Points from 4/1/2010 through <strong>06/30/2010</strong> =</td>
            <td class="center"><?php echo $thirdStatus->getPoints(); ?></td>
            <td class="center"><img src="<?php echo $thirdStatus->getLight(); ?>" class="light"/></td>
            <td class="center">25 or more points + 1A done</td>
        </tr>
        <tr>
            <td style="text-align: right;">Points from 7/1/2010 through <strong>09/30/2010</strong> =</td>
            <td class="center"><?php echo $fourthStatus->getPoints(); ?></td>
            <td class="center"><img src="<?php echo $fourthStatus->getLight(); ?>" class="light"/></td>
            <td class="center">25 or more points + 1A done</td>
        </tr>

        <tr>
            <td style="text-align: right;">Cumulative Total & Raffle Status** as of <?php echo date('m/d/Y'); ?> =</td>
            <td class="center"><?php echo $status->getPoints(); ?></td>
            <td class="center"><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
            <td class="center">150 or more cumulative</td>
        </tr>
        <tr>
            <td colspan="4">
                <div>*&nbsp;&nbsp;&nbsp;New hires should contact Human Resources regarding alternative deadlines.</div>
                <div>**&nbsp;&nbsp;Become eligible for raffle and other rewards and incentives with 150 or more
                    points.
                </div>
                <div>***&nbsp;&nbsp;If you logged 900 or more SIM minutes, you were given another 600 minutes (i.e. 10
                    extra credit points)
                </div>

            </td>
        </tr>
    </tbody>
    </table>
    <?php
    }
}


?>
