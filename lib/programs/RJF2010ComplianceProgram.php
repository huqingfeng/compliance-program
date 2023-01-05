<?php

class RJFWorkWithDoctorComplianceView extends PlaceHolderComplianceView
{
}

class RJF2010ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.date('F d, Y', $programEnd));

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Health Screening');
        $screeningView->setName('complete_screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Annual Online Health Assessment (HPA)');
        $coreGroup->addComplianceView($hraView);

        $safetyWorkshopView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 1, true);
        $safetyWorkshopView->setName('safety_workshop');
        $safetyWorkshopView->setReportName('Complete the Health Care Safety Workshop');
        $coreGroup->addComplianceView($safetyWorkshopView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'And, earn 100 or more points from A-K below.');

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($totalCholesterolView);
//
        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $extraGroup->addComplianceView($bodyFatBMIView);

        $nonSmokerView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker/Low Exposure to Nicotine/Cotinine');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($nonSmokerView);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorInformationView->setReportName('Have a Main Doctor');
        $doctorInformationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $doctorInformationView->setName('have_doctor');
        $extraGroup->addComplianceView($doctorInformationView);

        $preventiveExamsView = new ObtainPreventiveExamComplianceView($programStart, $programEnd, 5);
        $preventiveExamsView->setReportName('Do recommended Preventive Screenings/Exams');
        $preventiveExamsView->setMaximumNumberOfPoints(20);
        $preventiveExamsView->setName('do_preventive_exams');
        $extraGroup->addComplianceView($preventiveExamsView);

        $fluVaccineView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $extraGroup->addComplianceView($fluVaccineView);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(100);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setName('physical_activity');
        $extraGroup->addComplianceView($physicalActivityView);

        $workWithHealthCoachView = new PlaceHolderComplianceView(null, 0);
        $workWithHealthCoachView->setName('work_with_health_coach');
        $workWithHealthCoachView->setReportName('Work with Health Coach (if eligible)');
        $workWithHealthCoachView->setMaximumNumberOfPoints(25);
        $workWithHealthCoachView->addLink(new Link('Health Coach Info & Sign-Up', '/content/8733'));
        $extraGroup->addComplianceView($workWithHealthCoachView);

        $doctorView = new RJFWorkWithDoctorComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $doctorView->setReportName('Work with Doctor on Health Goals');
        $doctorView->setMaximumNumberOfPoints(20);
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $doctorView->setName('work_with_doctor');
        $doctorView->addLink(new Link('More Info', '/content/1094#2gDoc'));
        $extraGroup->addComplianceView($doctorView);

        $attendWorkshopView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 5);
        $attendWorkshopView->setReportName('Health Trainings/Events Attended');
        $attendWorkshopView->setName('attend_workshop');
        $attendWorkshopView->setMaximumNumberOfPoints(30);
        $extraGroup->addComplianceView($attendWorkshopView);

        $additionalELearningLessonsView = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd, 1);
        $additionalELearningLessonsView->setReportName('Complete e-Learning Lessons');
        $additionalELearningLessonsView->setName('elearning');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(20);
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setName('volunteering');
        $volunteeringView->setReportName('Regular Volunteering - Type & Time');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(30);
        $extraGroup->addComplianceView($volunteeringView);

        $mindfulActivityView = new MindfulActivityComplianceView($programStart, $programEnd, 1);
        $mindfulActivityView->setMaximumNumberOfPoints(30);
        $mindfulActivityView->setName('mindful_activity');
        $extraGroup->addComplianceView($mindfulActivityView);

        $extraGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($extraGroup);
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
            $printer = new RJFComplianceProgramReportPrinter();
        }

        return $printer;
    }

}


class RJFComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $safetyWorkshopStatus = $coreGroupStatus->getComplianceViewStatus('safety_workshop');

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $totalCholesterolStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_total_cholesterol_screening_test');
        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_hdl_screening_test');
        $ldlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_ldl_screening_test');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_triglycerides_screening_test');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_glucose_screening_test');
        $bloodPressureStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_blood_pressure_screening_test');
        $bodyFatBMIStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_body_fat_bmi_screening_test');
        $nonSmokingStatus = $pointGroupStatus->getComplianceViewStatus('non_smoker_view');

        $haveDoctorStatus = $pointGroupStatus->getComplianceViewStatus('have_doctor');
        $preventiveScreeningsStatus = $pointGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $fluVaccineStatus = $pointGroupStatus->getComplianceViewStatus('flu_vaccine');
        $physicalActivityStatus = $pointGroupStatus->getComplianceViewStatus('physical_activity');
        $healthCoachStatus = $pointGroupStatus->getComplianceViewStatus('work_with_health_coach');
        $workWithDoctorStatus = $pointGroupStatus->getComplianceViewStatus('work_with_doctor');
        $workshopStatus = $pointGroupStatus->getComplianceViewStatus('attend_workshop');
        $extraELearningStatus = $pointGroupStatus->getComplianceViewStatus('elearning');
        $volunteeringStatus = $pointGroupStatus->getComplianceViewStatus('volunteering');
        $mindfulActivityStatus = $pointGroupStatus->getComplianceViewStatus('mindful_activity');
        ?>
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
            background-color:#002AAE;
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
            background-color:#002AAE;
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


    <div class="pageHeading">Rewards/To-Do Summary Page</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>
        Welcome to your summary page for the 2010 Wellness Rewards Program. To
        receive the incentives, eligible employees MUST take action and meet all
        criteria below:
    </p>

    <ol>
        <li>
            Complete <strong>ALL</strong> of the core required actions by
            <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?>
        </li>
        <li>
            Earn 100 or more points from key screening results and key actions taken
            for good health.
        </li>
    </ol>

    <p>
        Employees meeting all criteria will each receive $40/month in reduced
        health insurance premiums ($480/year).
    </p>

    <div class="pageHeading">
        <a href="/content/1094">
            Click here for the 2010 Wellness Rewards Criteria Details
        </a>.
    </div>

    <p>
        <strong>Update Notice:</strong>
        To get actions done and earn extra points click on the links below. If the
        center is not yet Green for any option you did or are working on, you may
        need to go back and enter missing information, or additional entries to
        earn more points. The center for wellness screening will not change until
        after your report is mailed. <i>Thanks for your actions and patience!</i>
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
        <th>1. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
        <td>Date Done</td>
        <td>Status</td>
        <td>Links</td>
    </tr>
    <tr>
        <td><a href="/content/1094#1aHA">A. <?php echo $completeScreeningStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center">
            <?php echo $completeScreeningStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
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
        <td><a href="/content/1094#1bHS">B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></a>
        </td>
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
    <tr>
        <td><a href="/content/1094#1cHCSW">C. <?php echo $safetyWorkshopStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center">
            <?php echo $safetyWorkshopStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $safetyWorkshopStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($safetyWorkshopStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="right" style="font-size: 7pt;">
            All Core Actions Done on or before
            <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?>
        </td>
        <td></td>
        <td class="center">
            <?php echo $coreGroupStatus->isCompliant() ? 'Yes' : 'No'; ?>
        </td>
        <td class="center">Yes = 1st Quarter Criteria Met</td>
    </tr>
    <tr class="headerRow">
        <th>2. <?php echo $pointGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
        <td># Points Earned</td>
        <td># Points Possible</td>
        <td>Links</td>
    </tr>
    <tr>
        <td><a href="/content/1094#2aKBHM">A. Have these screening results in the ideal zone:</a></td>
        <td colspan="3">

        </td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $totalCholesterolStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $totalCholesterolStatus->getPoints(); ?></td>
        <td class="center"><?php echo $totalCholesterolStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td rowspan="8" class="links">
            <a href="?preferredPrinter=ScreeningProgramReportPrinter">Click here for the 8 results</a><br/><br/>
            <a href="/content/989">Click for all screening results</a><br/><br/>
            Click on any measure for more info & to improve
        </td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $hdlStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $hdlStatus->getPoints(); ?></td>
        <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $ldlStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $ldlStatus->getPoints(); ?></td>
        <td class="center"><?php echo $ldlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
        <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
        <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>

    <tr>
        <td>
            <ul>
                <li><?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $bloodPressureStatus->getPoints(); ?></td>
        <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>


    <tr>
        <td>
            <ul>
                <li><?php echo $bodyFatBMIStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $bodyFatBMIStatus->getPoints(); ?></td>
        <td class="center"><?php echo $bodyFatBMIStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>


    <tr>
        <td>
            <ul>
                <li><?php echo $nonSmokingStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $nonSmokingStatus->getPoints(); ?></td>
        <td class="center"><?php echo $nonSmokingStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td><a href="/content/1094#2bMD">B. <?php echo $haveDoctorStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $haveDoctorStatus->getPoints(); ?></td>
        <td class="center"><?php echo $haveDoctorStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($haveDoctorStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2cRPS">C. <?php echo $preventiveScreeningsStatus->getComplianceView()
            ->getReportName() ?></a></td>
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
        <td><a href="/content/1094#2dAFV">D. <?php echo $fluVaccineStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $fluVaccineStatus->getPoints(); ?></td>
        <td class="center"><?php echo $fluVaccineStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($fluVaccineStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2eRPA">E. <?php echo $physicalActivityStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $physicalActivityStatus->getPoints(); ?></td>
        <td class="center"><?php echo $physicalActivityStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($physicalActivityStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>

    <tr>
        <td><a href="/content/1094#2fHC">F. <?php echo $healthCoachStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $healthCoachStatus->getPoints(); ?></td>
        <td class="center"><?php echo $healthCoachStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($healthCoachStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2gDoc">G. <?php echo $workWithDoctorStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $workWithDoctorStatus->getPoints(); ?></td>
        <td class="center"><?php echo $workWithDoctorStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($workWithDoctorStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2hKHE">H. <?php echo $workshopStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $workshopStatus->getPoints(); ?></td>
        <td class="center"><?php echo $workshopStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($workshopStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2iEL">I. <?php echo $extraELearningStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $extraELearningStatus->getPoints(); ?></td>
        <td class="center"><?php echo $extraELearningStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($extraELearningStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2jVT">J. <?php echo $volunteeringStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $volunteeringStatus->getPoints(); ?></td>
        <td class="center"><?php echo $volunteeringStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($volunteeringStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2kMA">K. <?php echo $mindfulActivityStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $mindfulActivityStatus->getPoints(); ?></td>
        <td class="center"><?php echo $mindfulActivityStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($mindfulActivityStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="right">Total number as of <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
        <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
        <td></td>
    </tr>
    <tr class="headerRow">
        <th>3. Deadlines, Requirements & Status</th>
        <td># Points</td>
        <td>Status</td>
        <td>Needed By <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?></td>
    </tr>

    <tr>
        <td style="text-align: right;">Total & Credit Status: Through <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $status->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
        <td class="center">100 or more points + 1ABC done</td>
    </tr>
    </tbody>
    </table>
    <?php
    }
}