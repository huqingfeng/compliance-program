<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class Pewag2017ComplianceProgram extends ComplianceProgram
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
            $printer = new Pewag2017ComplianceReportPrinter();
            $printer->setShowTotal(false);
        }


        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'A & B are required by February 6, 2017.');

        $hraScreeningEndDate = '2017-02-06';


        $screening = new CompleteScreeningComplianceView('2017-01-17', $hraScreeningEndDate);
        $screening->setReportName('Annual Wellness Screening');
        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView('2016-12-15', $hraScreeningEndDate);
        $hra->setReportName('Complete the Health Power Assessment ');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $required->addComplianceView($hra);

        $this->addComplianceViewGroup($required);

        $pointsGroup = new ComplianceViewGroup('points', 'Gain points through actions taken in option areas C - L below by the Dec 18, 2017 deadline in order to be put in the raffle for the quarterly $20 gift cards.');


        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $ldlCholesterolView->setName('ldl');
        $ldlCholesterolView->setReportName('LDL Cholesterol ≤ 130');
        $ldlCholesterolView->overrideTestRowData(null, null, 130, null);
        $ldlCholesterolView->setMaximumNumberOfPoints(10);
        $ldlCholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $pointsGroup->addComplianceView($ldlCholesterolView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setName('glucose');
        $glucoseView->setReportName('Glucose ≤ 100');
        $glucoseView->overrideTestRowData(null, null, 100, null);
        $glucoseView->setMaximumNumberOfPoints(10);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $pointsGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setReportName('Blood Pressure both numbers ≤ 130/85');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 130, null);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 85, null);
        $bloodPressureView->setMaximumNumberOfPoints(10);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $pointsGroup->addComplianceView($bloodPressureView);

        $nonSmokerView = new VillageOfLibertyville2016TobaccoFormComplianceView($startDate, $endDate);
        $nonSmokerView->setReportName('Non-Smoker');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(25);
        $pointsGroup->addComplianceView($nonSmokerView);

        $elearningView = new CompleteELearningLessonsComplianceView($startDate, $endDate);
        $elearningView->setPointsPerLesson(5);
        $elearningView->setMaximumNumberOfPoints(100);
        $elearningView->setName('elearning');
        $elearningView->setReportName('Complete e-Learning Lessons - 5 pts for each lesson done');
        $pointsGroup->addComplianceView($elearningView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each 30 minutes of activity');
        $physicalActivityView->setMaximumNumberOfPoints(250);
        $physicalActivityView->setMinutesDivisorForPoints(30);
        $pointsGroup->addComplianceView($physicalActivityView);

        $wellnessSeminar = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 592, 25);
        $wellnessSeminar->setReportName('Participate in a Wellness Seminar - 25 pts per activity');
        $wellnessSeminar->setName('wellness_name');
        $wellnessSeminar->setMaximumNumberOfPoints(100);
        $pointsGroup->addComplianceView($wellnessSeminar);

        $preventiveExam = new  CompletePreventiveExamWithRollingStartDateLogicComplianceView($startDate, $endDate, 10);
        $preventiveExam->setReportName('Receive a Preventive Exam - 10 pts per exam');
        $preventiveExam->setMaximumNumberOfPoints(40);
        $preventiveExam->setName('preventive_exam');
        $pointsGroup->addComplianceView($preventiveExam);

        $volunteeringView = new VolunteeringComplianceView($startDate, $endDate);
        $volunteeringView->setReportName('Regular Volunteering - 5 pts per hour of volunteering');
        $volunteeringView->setMinutesDivisorForPoints(12);
        $volunteeringView->setMaximumNumberOfPoints(50);
        $pointsGroup->addComplianceView($volunteeringView);

        $doc = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doc->setMaximumNumberOfPoints(25);
        $doc->setReportName('Have a Main Doctor');
        $doc->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
        $pointsGroup->addComplianceView($doc);

        $blueCrossBlueShield = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 342, 25);
        $blueCrossBlueShield->setMaximumNumberOfPoints(25);
        $blueCrossBlueShield->setReportName('Register with Blue Cross Blue Shield\'s Blue Access for Members');
        $blueCrossBlueShield->addLink(new Link('BCBS', 'http://www.bcbsil.com/member'));
        $pointsGroup->addComplianceView($blueCrossBlueShield);

        $pointsGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($pointsGroup);
    }

}



class Pewag2017ComplianceReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setScreeningResultsLink(new FakeLink('Complete eLearning Lessons', '#'));
    }

    public function printHeader(ComplianceProgramStatus $status)
    {


        ?>

        <script type="text/javascript">
            $(function(){
                $('#legend tr td').html('<div id="legendText">Legend</div>' +
                '<div class="legendEntry"><img src="/compliance/hmi-2016/my-rewards/wms1/images/lights/greenlight.gif" class="light" alt=""> = Criteria Met </div>' +
                '<div class="legendEntry"> <img src="/compliance/hmi-2016/my-rewards/wms1/images/lights/redlight.gif" class="light" alt=""> = Not Started </div>');

                $('.view-ldl').before('<td><strong>C</strong>. Biometric Results</td><td class="points"></td><td class="points"></td><td class="links"></td>')
                $('.view-ldl').children(':eq(0)').html('<span style="padding-left: 60px;"><strong>•</strong> LDL Cholesterol ≤ 130</span>');
                $('.view-ldl').children('.links').html('<a href="/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=997">Click here for the 3 results</a>');
                $('.view-ldl').children('.links').attr('rowspan', 3);



                $('.view-glucose').children(':eq(0)').html('<span style="padding-left: 60px;"><strong>•</strong> Glucose ≤ 100</span>');
                $('.view-glucose').children('.links').remove();

                $('.view-blood_pressure').children(':eq(0)').html('<span style="padding-left: 60px;"><strong>•</strong> Blood Pressure both numbers ≤ 130/85</span>');
                $('.view-blood_pressure').children('.links').remove();


                $('.phipTable tbody').after('<tfoot><tr class="headerRow">' +
                '<td class="center" colspan="1">Status of All Criteria = </td>' +
                '<td class="points"><?php echo $status->getPoints() ?></td>' +
                '<td class="points"><?php echo $status->getComplianceProgram()->getMaximumNumberOfPoints() ?>' +
                '</td><td colspan=""></td>' +
                '</tr></tfoot>');


            });
        </script>

        <style type="text/css">
            .status img {
                width:25px;
            }
        </style>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Welcome to your summary page for the 2017 Wellness Rewards benefit at Pewag.</p>

        <p>You MUST register with HMI, complete the annual wellness screening and complete the Health Power Assessment
            by February 6, 2017. (Section 1 below).</p>

        <p>
            Each quarter there will be three $20 Dick’s Sporting Goods gift cards raffled off. You MUST earn the required
            quarterly points from key screening results and actions taken for good health to be eligible for the raffle.
            (Section 2 below).
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>


        <p style="margin-top: 20px; margin-left: 200px;">
            <strong>Quarter 1:</strong> Earn at least 75 pts by March 31, 2017 <br/>
            <strong>Quarter 2:</strong> Earn at least 150 pts by June 30, 2017 <br/>
            <strong>Quarter 3:</strong> Earn at least 250 pts by September 30, 2017 <br/>
            <strong>Quarter 4:</strong> Earn at least 350 pts by December 18, 2017 <br />
        </p>
        <?php
    }
}