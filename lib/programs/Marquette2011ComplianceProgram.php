<?php

class Marquette2011ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    protected function printCustomRows($status)
    {
        $groupStatus = $status->getComplianceViewGroupStatus('totals');
        extract($groupStatus->getComplianceViewStatuses());
        $today = strtotime(date('Y'));
        ?>
    <tr class="headerRow">
        <th><?php echo sprintf('<strong>%s</strong>. %s', 2, $groupStatus->getComplianceViewGroup()
            ->getReportName()) ?></th>
        <td>Total # Earned</td>
        <td>Raffle Status</td>
        <td>Minimum Cumulative Points Needed</td>
    </tr>
    <tr>
        <td style="text-align:right"><?php echo $q1->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q1->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q1->isCompliant() ? 'Earned' : ($today > $q1
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 50 points</td>
    </tr>
    <?php if($today > $q1->getComplianceView()->getEndDate()) : ?>
    <tr>
        <td style="text-align:right"><?php echo $q2->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q2->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q2->isCompliant() ? 'Earned' : ($today > $q2
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 100 points</td>
    </tr>
    <?php endif ?>
    <?php if($today > $q2->getComplianceView()->getEndDate()) : ?>
    <tr>
        <td style="text-align:right"><?php echo $q3->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q3->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q3->isCompliant() ? 'Earned' : ($today > $q3
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 200 points - Bronze</td>
    </tr>
    <?php endif ?>
    <?php if($today > $q3->getComplianceView()->getEndDate()) : ?>
    <tr>
        <td style="text-align:right"><?php echo $q4->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q4->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q4->isCompliant() ? 'Earned' : ($today > $q4
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 300 points - Silver</td>
    </tr>
    <tr>
        <td style="text-align:right"><?php echo $gold->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $gold->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $gold->isCompliant() ? 'Earned' : ($today > $gold
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 500 points - Gold</td>
    </tr>
    <?php endif ?>
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
    <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

    <p>
        Welcome to your summary page for our 2011 Wellness Rewards! This year we are trying a somewhat different
        approach that we hope will have a broad appeal and, as a result, will help keep interest and wellness activity
        levels high throughout the year – here at the bank and at home.
    </p>

    <p>In 2011, the wellness rewards will be:</p>
    <ul>
        <li>Quarterly raffles with great prizes (electronics, gift certificates, savings bonds and other gifts); AND
        </li>
        <li>An annual raffle with a grand prize gift to be announced later in the year.

        </li>
    </ul>

    <p>
        To be eligible for each raffle, eligible employees must earn the points needed by certain deadlines (noted
        below). You can earn these points from actions taken for your good health and wellbeing across the action
        categories below.
    </p>
    <p> Eligible employees are defined as being on the Bank’s medical plan; are full-time or part-time working at least
        20 hours per week; have completed the new-hire probation period and; are non-seasonal.</p>

    <p>
        The number of points you earn throughout the year also move you toward the Bronze, Silver and Gold levels of
        achievement and success where you join with others in being recognized for outstanding efforts toward better
        health, health care and well-being.
    </p>

    <p style="text-align:center;"><a href="/content/1094">Click here for more details about the 2011 Wellness Rewards
        benefit and requirements.</a></p>
    <p style="text-align:center;"><a href="/content/301739">Click here to view quarterly raffle winners.</a></p>
    <p>
        <strong>Update Notice:</strong> To get actions done and earn extra points
        click on the links below. If the points or status did not change for an item
        you are working on, you may need to go back and enter missing information or
        entries to earn more points. Thanks for your actions and patience!
    </p>
    <?php
    }
}

class MarquetteMindfulEating extends ComplianceView
{
    public function __construct()
    {
        $this->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $this->addLink(new Link('Mindful Eating Program', '/content/mindfuleating'));
    }

    public function getStatus(User $user)
    {
        $complete = true;

        if(($record = $user->getNewestDataRecord('mindful_eating')) /* && $record->exists*/) {
            foreach(array(1, 3, 4, 5, 6) as $quizNumber) {
                if(!(boolean) $record->getDataFieldValue(sprintf('quiz%s_completed', $quizNumber))) {
                    $complete = false;
                    break;
                }
            }
        } else {
            $complete = false;
        }

        return new ComplianceViewStatus($this, $complete ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT);
    }

    public function getDefaultName()
    {
        return 'mindful_eating';
    }

    public function getDefaultReportName()
    {
        return 'Complete Mindful Eating Interactive Program';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }
}

class MarquetteCompleteRequiredELearningLessonsComplianceView extends CompleteELearningLessonsComplianceView
{
    public function getStatus(User $user)
    {
        $status = parent::getStatus($user);

        // If they completed any of the "bonus" historically, we give them credit
        // even if not between the dates.

        $elearningBonusView = new CompleteELearningGroupSet('2006-01-01', '2011-03-25', 'bonus_lessons');
        $elearningBonusView->setComplianceViewGroup($this->getComplianceViewGroup());
        $elearningBonusView->setPointsPerLesson(5);

        $bonusStatus = $elearningBonusView->getStatus($user);

        $status->setPoints($bonusStatus->getPoints() + $status->getPoints());

        return $status;
    }
}

class Marquette2011ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Marquette2011ComplianceProgramReportPrinter();
        $printer->setShowLegend(false);
        $printer->setShowPointBasedGroupTotal(true);
        $printer->setShowTotal(false);

        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Quarterly and Grand Raffle Deadlines, Requirements & Status');

        $q1Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2011-03-15'), array(), 50);
        $q1Deadline->setReportName('Q1 Status - By <strong>03/15/2011</strong>');
        $q1Deadline->setName('q1');
        $totalsGroup->addComplianceView($q1Deadline, true);

        $q2Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2011-06-30'), array(), 100);
        $q2Deadline->setReportName('Q2 Status - By <strong>06/30/2011</strong>');
        $q2Deadline->setName('q2');
        $totalsGroup->addComplianceView($q2Deadline, true);

        $q3Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2011-09-15'), array(), 200);
        $q3Deadline->setReportName('Q3 Status - By <strong>09/15/2011</strong>');
        $q3Deadline->setName('q3');
        $totalsGroup->addComplianceView($q3Deadline, true);

        $q4Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2011-12-15'), array(), 300);
        $q4Deadline->setReportName('Q4 Status - By <strong>12/15/2011</strong>');
        $q4Deadline->setName('q4');
        $totalsGroup->addComplianceView($q4Deadline, true);

        $goldView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2011-12-15'), array(), 500);
        $goldView->setReportName('Annual Grand Raffle Status - By <strong>12/15/2011</strong>');
        $goldView->setName('gold');
        $totalsGroup->addComplianceView($goldView, true);

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
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $required->addComplianceView($hra);

        $elearningView = new MarquetteCompleteRequiredELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setReportName('Complete e-Learning lessons');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(40);
        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(410);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $required->addComplianceView($physicalActivityView);

        $flushotView = new FluVaccineActivityComplianceView($startDate, $endDate);
        $flushotView->setReportName('Annual Flu Shot');
        $flushotView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $required->addComplianceView($flushotView);

        $preventiveView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 10);
        $preventiveView->setReportName('Preventive Screenings & Exams Obtained');
        $preventiveView->setMaximumNumberOfPoints(50);
        $required->addComplianceView($preventiveView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setReportName('Regular Volunteering - Type & Time');
        $volunteeringView->setMaximumNumberOfPoints(100);
        $volunteeringView->setMinutesDivisorForPoints(60);
        $required->addComplianceView($volunteeringView);

        $engageView = new EngageLovedOneComplianceView($startDate, $endDate, 10);
        $engageView->setReportName('Engage a Loved One/Friend Toward Better Health');
        $engageView->setMaximumNumberOfPoints(30);
        $required->addComplianceView($engageView);

        $goalView = new CompleteGoalsComplianceView($startDate, $endDate, 10);
        $goalView->setReportName('Create and Track Health Improvement Goals');
        $goalView->setMaximumNumberOfPoints(30);
        $required->addComplianceView($goalView);

        $shareStoryView = new ShareAStoryComplianceView($startDate, $endDate, 10);
        $shareStoryView->setReportName('Share a "It Helped" Story/Testimonial');
        $shareStoryView->setMaximumNumberOfPoints(30);
        $required->addComplianceView($shareStoryView);

        $required->addComplianceView(new MarquetteMindfulEating());

        $this->addComplianceViewGroup($required);
    }
}