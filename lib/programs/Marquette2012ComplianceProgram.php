<?php

class Marquette2012ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    protected function printCustomRows($status)
    {
        $groupStatus = $status->getComplianceViewGroupStatus('totals');
        $today = strtotime(date('Y'));
        ?>
    <tr class="headerRow">
        <th><?php echo sprintf('<strong>%s</strong>. %s', 2, $groupStatus->getComplianceViewGroup()
            ->getReportName()) ?></th>
        <td>Total # Earned</td>
        <td>Raffle Status</td>
        <td>Minimum Cumulative Points Needed</td>
    </tr>
    <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
    <?php $view = $viewStatus->getComplianceView() ?>

    <?php if($today >= $view->getAttribute('first_day')) : ?>
        <tr>
            <td style="text-align:right">
                <?php echo $view->getReportName() ?>
            </td>
            <td class="points">
                <?php echo $viewStatus->getPoints() ?>
            </td>
            <td style="text-align:center;font-style:italic;">
                <?php
                echo $viewStatus->isCompliant() ? 'Earned' : (
                $today > $view->getEndDate() ? 'Unearned' : 'In Progress'
                )
                ?>
            </td>
            <td class="points">
                &ge; <?php echo $view->getPointsRequired() ?> points
            </td>
        </tr>
        <?php endif ?>
    <?php endforeach ?>
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

    <p>Welcome to your summary page for our 2012 Wellness Rewards! </p>

    <p>In 2012 the wellness rewards will be monthly raffles with great prizes
        ( electronics, gift cards, and other gifts.) </p>

    <p>To be eligible for each raffle, eligible employees must earn the points
        needed by monthly deadlines noted below. You can earn these points from
        actions taken for your good health and wellbeing across the action
        categories below.</p>

    <p>Eligible employees are defined as being on the Bank’s medical plan; are
        full-time or part-time working at least 20 hours per week; have completed
        the new hire probation and; are non-seasonal.</p>

    <p>To get started take the HPA this month you will automatically earn
        25 points and be eligible of a drawing of a $25 I tunes card. </p>

    <p>Continue to earn 25 or more points each month and you will be eligible
        for additional monthly raffles throughout the entire year. If you miss a
        month don’t worry you can catch up by taking past elearning lessons and
        other activities. Keep watching this website for new monthly e learning
        lessons and activities. </p>

    <p><a href="/content/1094">Click here </a> for more details about the 2012
        Wellness Rewards benefit and requirements. </p>

    <p><a href="/content/301739">Click here </a> to view the monthly raffle
        winners.</p>

    <?php
    }
}

class Marquette2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Marquette2012ComplianceProgramReportPrinter();
        $printer->setShowLegend(false);
        $printer->setShowPointBasedGroupTotal(true);
        $printer->setShowTotal(false);

        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup(
            'totals',
            'Monthly Deadlines, Requirements & Status'
        );

        foreach(range(1, 12) as $month) {
            $pointsRequired = $month * 25;

            $firstDay = strtotime(date(sprintf('2012-%02d-01', $month)));
            $lastDay = strtotime(date(sprintf('2012-%02d-t', $month), $firstDay));

            $lastDayText = date('F jS, Y', $lastDay);

            $evalView = new ProgramStatusEvaluatorComplianceView(
                $this->cloneForEvaluation(
                    $this->getStartDate(),
                    $lastDay
                ),
                array(),
                $pointsRequired
            );

            $evalView->setAttribute('first_day', $firstDay);

            $evalView->setName('evaluator_'.$month);
            $evalView->setReportName("By $lastDayText");
            $totalsGroup->addComplianceView($evalView, true);
        }

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

        $elearningView = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setReportName('Complete e-Learning lessons');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(10);
        $elearningView->setMaximumNumberOfPoints(400);
        $elearningView->emptyLinks();
        $elearningView->addLink(new Link('e-Learning Center', '/content/9420?action=lessonManager&tab_alias=marquette_'.date('m').'_required'));
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


        $this->addComplianceViewGroup($required);
    }
}