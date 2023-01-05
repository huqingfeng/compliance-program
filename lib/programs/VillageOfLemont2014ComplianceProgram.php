<?php

class VillageOfLemont2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <td style="text-align:center;font-style:italic;"><?php echo $totalView->isCompliant() ? 'Earned' : ($today > $totalView
                ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
            <td class="points">≥ 275 points</td>
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

        <p>Welcome to your summary page for the 2014 Wellness Rewards benefit
            at Village of Lemont.This year, we are introducing a new wellness
            rewards incentive program.</p>

        <p>In 2014, employees reaching the necessary number of points will
            receive a discount on their 2015 health insurance premium
            (Jan-June). There will also be prizes for those employees who
            participate but are not on our health plans. Also, monthly raffles
            will be held for all those actively participating.</p>

        <p>The deadline to receive the 275 points (and obtain the premium
            discount) is December 31, 2014 (see below). You can earn
            these points from actions taken for your good health and wellbeing
            across the action categories below.</p>

        <p style="text-align:center;">
            <a href="/content/1094">
                Click here for more details about the 2014 Wellness Rewards
                benefit and requirements.
            </a>
        </p>

        <p><strong>Update Notice:</strong> To get actions done and earn points
            click on the links below. If the points or status did not change
            for an item you are working on, you may need to go back and enter
            missing information or entries to earn more points. We wish you
            much success in your healthy endeavors.</p>
        <?php
    }
}

class VillageOfLemont2014ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new VillageOfLemont2014ComplianceProgramReportPrinter();
        $printer->setShowPointBasedGroupTotal(true);
        $printer->setShowTotal(false);
        $printer->setShowLegend(false);


        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Deadlines, Requirements & Status');

        $totalView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-12-31'), array(), 275);
        $totalView->setReportName('<strong>By 12/31/2014</strong>');
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
        $elearningView->useAlternateCode(true);
        $elearningView->setMaximumNumberOfPoints(40);
        $elearningView->setAttribute('report_name_link', '/content/1094#celearn');
        $required->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(180);
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

        $donateBlood = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 340, 10);
        $donateBlood->setName('donate_blood');
        $donateBlood->setReportName('Donate Blood');
        $donateBlood->setMaximumNumberOfPoints(40);
        $donateBlood->setAttribute('report_name_link', '/content/1094#hengage');
        $required->addComplianceView($donateBlood);

        $cert = new CompleteArbitraryActivityComplianceView('2010-01-01', $endDate, 341, 15);
        $cert->setName('cert');
        $cert->setReportName('CPR/AED certified');
        $cert->setMaximumNumberOfPoints(15);
        $cert->setAttribute('report_name_link', '/content/1094#igoals');
        $required->addComplianceView($cert);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $doctorView->setAttribute('report_name_link', '/content/1094#jdoc');
        $required->addComplianceView($doctorView);

        $healthy = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 338, 1);
        $healthy->setMaximumNumberOfPoints(50);
        $healthy->setName('healthy');
        $healthy->setReportName('Drink 6-8 glasses of pure water per day');
        $healthy->setAttribute('report_name_link', '/content/1094#kevent');
        $required->addComplianceView($healthy);

        $this->addComplianceViewGroup($required);
    }
}