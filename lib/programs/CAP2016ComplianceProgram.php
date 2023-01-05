<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class CAPCompleteRequiredELearningLessonsComplianceView extends CompleteELearningLessonsComplianceView
{
    public function getStatus(User $user)
    {
        $status = parent::getStatus($user);

        $lessonsCompleted = $status->getAttribute('lessons_completed_dates');

        $lessonsCompletedPerWeeks = array();
        foreach($lessonsCompleted as $lesson) {
            $lessonsCompletedPerWeeks[date('W', $lesson['date'])] = date('W', $lesson['date']);
        }

        $pointsPerLesson = $this->getPointsPerLesson();
        $totalPoints = $pointsPerLesson * count($lessonsCompletedPerWeeks);

        $status->setPoints($totalPoints);

        return $status;
    }
}

Class CAPWeeklyLimitActivityComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $activityId, $pointPerWeek, $numRequired)
    {
        $this->setDateRange('2016-01-25', $endDate);
        $this->activityId = $activityId;
        $this->pointPerWeek = $pointPerWeek;
        $this->numRequired = $numRequired;

        $this->addLink(new ActivityComplianceViewLink($this->getActivity()));
    }

    public function getDefaultStatusSummary($status)
    {
        if($status == ComplianceStatus::COMPLIANT) {
            return $this->getAttribute('requirement');
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return "cap_challenge_{$this->activityId}";
    }

    public function getDefaultReportName()
    {
        return "CAP Challenge ({$this->activityId})";
    }

    public function getStatus(User $user) {
        $records = $this->getRecords($user);

        $weeks = $this->getWeeks();

        $weekNumbers = array();
        foreach($records as $record) {
            $recordWeek = date('W', strtotime($record->getDate()));

            if(!isset($weekNumbers[$recordWeek])) $weekNumbers[$recordWeek] = 0;
            $weekNumbers[$recordWeek] += 1;

        }

        $totalPoints = 0;
        foreach($weekNumbers as $week => $number) {
            if($number >= $this->numRequired) {
                $totalPoints += $this->pointPerWeek;
            }
        }

        $status = new ComplianceViewStatus(
            $this,
            null,
            $totalPoints
        );

        return $status;
    }


    protected function getWeeks()
    {
        $startDate = strtotime($this->getStartDate('Y-m-d'));
        $endDate = strtotime($this->getEndDate('Y-m-d'));
        $now = $startDate;

        $weeks = array();
        while ($now <= $endDate) {
            $weeks[] = date('W', $now);
            $now = strtotime('+1 week', $now);
        }

        return $weeks;
    }



    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

}

class CAPMultipleAverageStepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $pointsPer)
    {
        $this->setDateRange($startDate, $endDate);
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "hmi_multi_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Multi Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

        $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        $points = 0;

        foreach($data['dates'] as $date) {
            if($date >= $this->threshold) {
                $points += $this->pointsPer;
            }
        }

        $status = new ComplianceViewStatus($this, null, $points);

        return $status;
    }

    private $threshold;
    private $pointsPer;
}


class CAP2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    protected function printCustomRows($status)
    {
        $groupStatus = $status->getComplianceViewGroupStatus('totals');

        $totalView = $groupStatus->getComplianceViewStatus('total');
        extract($groupStatus->getComplianceViewStatuses());
        $today = strtotime(date('Y'));
        ?>
        <tr class="headerRow">
            <th><?php echo sprintf('<strong>%s</strong>. %s', 2, $groupStatus->getComplianceViewGroup()
                    ->getReportName()) ?></th>
            <td>Total # Earned</td>
            <td>Status</td>
            <td>Minimum Points Needed</td>
        </tr>

        <tr>
            <td style="text-align:right"><?php echo $totalView->getComplianceView()->getReportName() ?></td>
            <td class="points"><?php echo $totalView->getPoints() ?></td>
            <td style="text-align:center;font-style:italic;"><?php echo $totalView->isCompliant() ? 'Completed' : ($today > $totalView
                    ->getComplianceView()->getEndDate() ? 'Incomplete' : 'In Progress') ?></td>
            <td class="points">≥ 600 points</td>
        </tr>

        <?php
    }

    protected function printStatusView($view)
    {

    }

    protected function showGroup($group)
    {
        return $group->getName() == 'required';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <script type="text/javascript">
            $(function() {
//                $('.view-complete_hra').next().children(':eq(0)').html('<strong>C</strong>. Biometric Results')
            });
        </script>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Welcome to your summary page for the 2016 Wellness Rewards Program at the College of American Pathologists. </p>

        <p>In 2016, employees reaching the necessary number of points will be entered to win 1 of 3 raffle prizes!
        </p>

        <p>The deadline to earn 600 points and be entered into the raffle is December 16th, 2016. You can earn these points,
            starting February 1st, from actions taken for your good health and wellbeing across the action categories below.
        </p>

        <p><strong>Please Note: </strong> You must log in your points for <strong>weekly</strong> activities (actions F-K below) by Noon on Monday for the
            previous week. If you do not log in your points by this deadline, you will have lost the opportunity to gain those points. </p>

        <p>
            We wish you much success in your healthy endeavors!
        </p>
        <?php
    }
}

class CAP2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new ScreeningProgramReportPrinter();

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
            $printer = new CAP2016ComplianceProgramReportPrinter();
//        $printer->setShowPointBasedGroupTotal(true);
            $printer->setShowTotal(false);
            $printer->setShowLegend(false);
        }

        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Deadlines, Requirements & Status');

        $totalView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), $this->getEndDate()), array(), 600);
        $totalView->setReportName('<strong>By 12/16/2016</strong>');
        $totalView->setName('total');
        $totalsGroup->addComplianceView($totalView);

        $this->addComplianceViewGroup($totalsGroup);
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'Document the points you earn from any of these action areas by using the action links');
        $required->setPointsRequiredForCompliance(0);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Complete the Health Power Assessment');
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/content/1006'));
        $hra->addLink(new Link('Results', '/content/989'));
        $required->addComplianceView($hra);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 452, 50);
        $annualPhysicalExamView->setMaximumNumberOfPoints(50);
        $annualPhysicalExamView->setReportName('Get an Annual Physical Exam');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $required->addComplianceView($annualPhysicalExamView);

        $annualFluView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 20, 40);
        $annualFluView->setMaximumNumberOfPoints(40);
        $annualFluView->setReportName('Annual Flu Vaccine');
        $annualFluView->setName('annual_flu_vaccine');
        $required->addComplianceView($annualFluView);

        $preventiveView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 20);
        $preventiveView->setReportName('Preventive Exams Obtained - 20 pts for each exam done this year');
        $preventiveView->setMaximumNumberOfPoints(40);
        $required->addComplianceView($preventiveView);

        $volunteeringView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 455, 50);
        $volunteeringView->setReportName('Volunteering - 50 pts per volunteer activity');
        $volunteeringView->setMaximumNumberOfPoints(100);
        $required->addComplianceView($volunteeringView);


        $elearningView = new CAPCompleteRequiredELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setReportName('Complete one e-Learning Lesson per week - 1 pt for each lesson done');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(1);
        $elearningView->setMaximumNumberOfPoints(46);
        $elearningView->emptyLinks();
        $elearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $required->addComplianceView($elearningView);

        $physicalActivityView = new CAPWeeklyLimitActivityComplianceView($startDate, $endDate, 458, 10, 3);
        $physicalActivityView->setReportName('Regular Physical Activity 3 times a week for at least 30 minutes - 10 points per week');
        $physicalActivityView->setMaximumNumberOfPoints(460);
        $required->addComplianceView($physicalActivityView);

        $recycleView = new CAPWeeklyLimitActivityComplianceView($startDate, $endDate, 461, 1, 3);
        $recycleView->setReportName('Recycle 3 times a week - 1 pt per week');
        $recycleView->setMaximumNumberOfPoints(46);
        $required->addComplianceView($recycleView);

        $fruitView = new CAPWeeklyLimitActivityComplianceView($startDate, $endDate, 464, 2, 7);
        $fruitView->setReportName('Eat 1 serving of fruit a day for a week - 2 pts per week');
        $fruitView->setMaximumNumberOfPoints(92);
        $required->addComplianceView($fruitView);

        $vegetableView = new CAPWeeklyLimitActivityComplianceView($startDate, $endDate, 467, 2, 7);
        $vegetableView->setReportName('Eat 1 serving of vegetable a day for a week - 2 pts per week');
        $vegetableView->setMaximumNumberOfPoints(92);
        $required->addComplianceView($vegetableView);

        $waterView = new CAPWeeklyLimitActivityComplianceView($startDate, $endDate, 470, 2, 7);
        $waterView->setReportName('Drink 6 glasses of water a day for a week - 2 pts per week');
        $waterView->setMaximumNumberOfPoints(92);
        $required->addComplianceView($waterView);


        $this->addComplianceViewGroup($required);
    }
}