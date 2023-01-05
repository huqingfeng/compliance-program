<?php

class WTTW2012ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
        <td style="text-align:right"><?php echo $q2->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q2->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q2->isCompliant() ? 'Earned' : ($today > $q2
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 150 points - Bronze</td>
    </tr>
    <tr>
        <td style="text-align:right"><?php echo $q3->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q3->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q3->isCompliant() ? 'Earned' : ($today > $q3
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 200 points - Silver</td>
    </tr>
    <tr>
        <td style="text-align:right"><?php echo $q4->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q4->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q4->isCompliant() ? 'Earned' : ($today > $q4
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 300 points - Gold</td>
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
    <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

    <p>
        Welcome to your summary page for the 2012 Wellness Rewards benefit at WWCI.
        This year, we are introducing a new wellness rewards incentive program.
    </p>

    <p>In 2012, the wellness rewards will be:</p>
    <ul>
        <li>Quarter 2 and 3 raffles with prizes such as movie tickets or $100 gift cards to Whole Foods or
            Dick’s Sporting Goods (these are just 2 examples of gift cards).
        </li>
        <li>Quarter 4 grand raffle for an iPad.</li>
    </ul>

    <p>
        To be eligible for each raffle, benefit eligible employees must earn the points needed
        by certain deadlines (noted below). You can earn these points from actions
        taken for your good health and wellbeing across the action categories below.
    </p>

    <p>
        The number of points you earn throughout the year also move you toward the
        Bronze, Silver and Gold levels of achievement and success where you join with
        others in being recognized for outstanding efforts toward better health, care
        and well-being.
    </p>

    <p style="text-align:center;"><a href="/content/1094">Click here for more details about the 2012 Wellness Rewards
        benefit and requirements.</a></p>

    <p>
        <strong>Update Notice:</strong> To get actions done and earn points
        click on the links below. If the points or status did not change for an item
        you are working on, you may need to go back and enter missing information or
        entries to earn more points. We wish you much success in your healthy endeavors.
    </p>
    <?php
    }
}

class WTTW2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new WTTW2012ComplianceProgramReportPrinter();
        $printer->setShowPointBasedGroupTotal(true);
        $printer->setShowTotal(false);
        $printer->setShowLegend(false);


        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Quarterly and Grand Raffle Deadlines, Requirements & Status');


        $q2Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2012-06-30'), array(), 150);
        $q2Deadline->setReportName('Q2 Status - By <strong>06/30/2012</strong>');
        $q2Deadline->setName('q2');
        $totalsGroup->addComplianceView($q2Deadline);

        $q3Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2012-09-30'), array(), 200);
        $q3Deadline->setReportName('Q3 Status - By <strong>09/30/2012</strong>');
        $q3Deadline->setName('q3');
        $totalsGroup->addComplianceView($q3Deadline);

        $q4Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2012-12-14'), array(), 300);
        $q4Deadline->setReportName('Q4 Status - By <strong>12/14/2012</strong>');
        $q4Deadline->setName('q4');
        $totalsGroup->addComplianceView($q4Deadline);

        //$goldView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2012-12-31'), array(), 300);
        //$goldView->setReportName('Annual Grand Raffle Status - By <strong>12/31/2012</strong>');
        //$goldView->setName('gold');
        //$totalsGroup->addComplianceView($goldView);

        $this->addComplianceViewGroup($totalsGroup);
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'Document the points you earn from any of these action areas by using the action links');
        $required->setPointsRequiredForCompliance(0);

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setReportName('Annual Onsite Wellness Screening');
        $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $screening->setAttribute('report_name_link', '/content/1094#ascreen');
        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Complete the Health Power Assessment');
        $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $hra->setAttribute('report_name_link', '/content/1094#bhpa');
        $required->addComplianceView($hra);

        $elearningView = new CompleteRequiredELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setReportName('Complete e-Learning lessons');
        $elearningView->setNumberRequired(0);
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(40);
        $elearningView->setAttribute('report_name_link', '/content/1094#celearn');
        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(360);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#dphysact');
        $required->addComplianceView($physicalActivityView);

        $flushotView = new FluVaccineActivityComplianceView($startDate, $endDate);
        $flushotView->setReportName('Annual Flu Shot');
        $flushotView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $flushotView->setAttribute('report_name_link', '/content/1094#eflushot');
        $required->addComplianceView($flushotView);

        $preventiveView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 10);
        $preventiveView->setReportName('Preventive Exams Obtained');
        $preventiveView->setMaximumNumberOfPoints(50);
        $preventiveView->setAttribute('report_name_link', '/content/1094#fexams');
        $required->addComplianceView($preventiveView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setReportName('Regular Volunteering');
        $volunteeringView->setMaximumNumberOfPoints(40);
        $volunteeringView->setAttribute('report_name_link', '/content/1094#gvol');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $required->addComplianceView($volunteeringView);

        $engageView = new EngageLovedOneComplianceView($startDate, $endDate, 10);
        $engageView->setReportName('Engage a Loved One/Friend Toward Better Health');
        $engageView->setMaximumNumberOfPoints(30);
        $engageView->setAttribute('report_name_link', '/content/1094#hengage');
        $required->addComplianceView($engageView);

        $goalView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 240, 10);
        $goalView->setReportName('Create and Track Health Improvement Goals');
        $goalView->setMaximumNumberOfPoints(30);
        $goalView->setAttribute('report_name_link', '/content/1094#igoals');
        $required->addComplianceView($goalView);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $doctorView->setAttribute('report_name_link', '/content/1094#jdoc');
        $required->addComplianceView($doctorView);

        $workshopView = new AttendCompanyWorkshopComplianceView($startDate, $endDate, 30);
        $workshopView->setReportName('Health Trainings / Events Attended');
        $workshopView->setMaximumNumberOfPoints(30);
        $workshopView->setAttribute('report_name_link', '/content/1094#kevent');
        $required->addComplianceView($workshopView);

        $this->addComplianceViewGroup($required);
    }
}