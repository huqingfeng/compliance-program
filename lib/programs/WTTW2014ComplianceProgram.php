<?php

class WTTW2014ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct() 
    {
        $this->pageHeading = '';
    }
    
    protected function printCustomRows($status)
    {
        $groupStatus = $status->getComplianceViewGroupStatus('totals');
        extract($groupStatus->getComplianceViewStatuses());
        $today = strtotime(date('Y'));
        ?>
    <!--<tr class="headerRow">
        <th><?php echo sprintf('<strong>%s</strong>. %s', 2, $groupStatus->getComplianceViewGroup()
            ->getReportName()) ?></th>
        <td>Total # Earned</td>
        <td>Raffle Status</td>
        <td>Minimum Cumulative Points Needed</td>
    </tr>

   < <tr>
        <td style="text-align:right"><?php echo $q2->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q2->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q2->isCompliant() ? 'Earned' : ($today > $q2
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 150 points - Silver</td>
    </tr>

    <tr>
        <td style="text-align:right"><?php echo $q4->getComplianceView()->getReportName() ?></td>
        <td class="points"><?php echo $q4->getPoints() ?></td>
        <td style="text-align:center;font-style:italic;"><?php echo $q4->isCompliant() ? 'Earned' : ($today > $q4
            ->getComplianceView()->getEndDate() ? 'Unearned' : 'In Progress') ?></td>
        <td class="points">≥ 250 points - Gold</td>
    </tr>-->

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
        <strong> You may track your progress in any of the action areas that you wish over the course of 2014.</strong>
    </p>
    <?php
    }
}

class WTTW2014ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new WTTW2014ComplianceProgramReportPrinter();
        $printer->setShowPointBasedGroupTotal(true);
        $printer->setShowTotal(false);
        $printer->setShowLegend(false);


        return $printer;
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Raffle Deadlines, Requirements & Status');


        $q2Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-12-31'), array(), 150);
        $q2Deadline->setReportName('Q2 Status - By <strong>06/30/2014</strong>');
        $q2Deadline->setName('q2');
        $totalsGroup->addComplianceView($q2Deadline);

        /*$q3Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-09-30'), array(), 200);
        $q3Deadline->setReportName('Q3 Status - By <strong>09/30/2014</strong>');
        $q3Deadline->setName('q3');
        $totalsGroup->addComplianceView($q3Deadline);*/

        $q4Deadline = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-12-31'), array(), 250);
        $q4Deadline->setReportName('Q4 Status - By <strong>12/31/2014</strong>');
        $q4Deadline->setName('q4');
        $totalsGroup->addComplianceView($q4Deadline);

        //$goldView = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-12-31'), array(), 300);
        //$goldView->setReportName('Annual Grand Raffle Status - By <strong>12/31/2014</strong>');
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