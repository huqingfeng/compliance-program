<?php

class Justright2014ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroupEndDate = strtotime('2014-02-28');
        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.date('F d, Y', $coreGroupEndDate));

        $screeningView = new CompleteScreeningComplianceView($programStart, $coreGroupEndDate);
        $screeningView->setReportName('Wellness Screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $coreGroupEndDate);
        $hraView->setReportName('Health Power Assessment');
        $coreGroup->addComplianceView($hraView);

        $elearningRequiredView = new CompleteRequiredELearningLessonsComplianceView($programStart, $coreGroupEndDate);
        $coreGroup->addComplianceView($elearningRequiredView);

        $contactInformationView = new UpdateContactInformationComplianceView($programStart, $coreGroupEndDate);
        $contactInformationView->setReportName('Personal Contact Info Up-to-Date');
        $coreGroup->addComplianceView($contactInformationView);

        $doctorInformationView = new UpdateDoctorInformationComplianceView($programStart, $coreGroupEndDate);
        $doctorInformationView->setReportName('Have a Main Doctor');
        $coreGroup->addComplianceView($doctorInformationView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'And, earn 100 or more points from A-G below.');

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($totalCholesterolView);

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
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $extraGroup->addComplianceView($bodyFatBMIView);

        $nonSmokerView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Smoking Status');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($nonSmokerView);


        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(150);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $extraGroup->addComplianceView($physicalActivityView);

        $workWithHealthCoachView = new PlaceHolderComplianceView(null, 0);
        $workWithHealthCoachView->setName('work_with_health_coach_activity');
        $workWithHealthCoachView->setReportName('Work with Health Coach or Doctor on Goals');
        $workWithHealthCoachView->setMaximumNumberOfPoints(20);
        $workWithHealthCoachView->addLink(new Link('Health Coach Sign-Up', '/content/1051'));
        $workWithHealthCoachView->addLink(new Link('<br />Doctor Form', '/content/doctor_form'));
        $extraGroup->addComplianceView($workWithHealthCoachView);

        $attendWorkshopView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 5);
        $attendWorkshopView->setReportName('Health Trainings/Events Attended');
        $attendWorkshopView->setMaximumNumberOfPoints(30);
        $extraGroup->addComplianceView($attendWorkshopView);

        $additionalELearningLessonsView = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd, 1);
        $additionalELearningLessonsView->setReportName('Extra e-Learning Lessons Completed');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(20);
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $preventiveScreeningsView = new ObtainPreventiveExamComplianceView($programStart, $programEnd, 5);
        $preventiveScreeningsView->setMaximumNumberOfPoints(20);
        $extraGroup->addComplianceView($preventiveScreeningsView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setReportName('Regular Volunteering - Type & Time');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(100);
        $extraGroup->addComplianceView($volunteeringView);

        $extraGroup->setPointsRequiredForCompliance(100);

        $this->addComplianceViewGroup($extraGroup);
    }

    public function loadEvaluators()
    {
        $totalsGroup = new ComplianceViewGroup('totals', 'Totals');

        $firstTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-02-28'), array('core'), 25);
        $firstTotal->setName('first_total');
        $secondTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-02-28'), array('core'), 50);
        $secondTotal->setName('second_total');
        $thirdTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-05-31'), array('core'), 75);
        $thirdTotal->setName('third_total');
        $fourthTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2014-08-31'), array('core'), 100);
        $fourthTotal->setName('fourth_total');

        $totalsGroup->addComplianceView($firstTotal, true);
        $totalsGroup->addComplianceView($secondTotal, true);
        $totalsGroup->addComplianceView($thirdTotal, true);
        $totalsGroup->addComplianceView($fourthTotal, true);

        $this->addComplianceViewGroup($totalsGroup);
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
            $printer = new Wheaton2014ComplianceProgramReportPrinter();
        }

        return $printer;
    }

}


class Justright2014ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $completeRequiredELearningStatus = $coreGroupStatus->getComplianceViewStatus('complete_elearning_required');
        $updateDoctorInformationStatus = $coreGroupStatus->getComplianceViewStatus('update_doctor_information');
        $updateContactInformationStatus = $coreGroupStatus->getComplianceViewStatus('update_contact_information');

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $totalCholesterolStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_total_cholesterol_screening_test');
        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_hdl_screening_test');
        $ldlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_ldl_screening_test');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_triglycerides_screening_test');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_glucose_screening_test');
        $bloodPressureStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_blood_pressure_screening_test');
        $bodyFatBMIStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_body_fat_bmi_screening_test');
        $nonSmokingStatus = $pointGroupStatus->getComplianceViewStatus('non_smoker_view');

        $physicalActivityStatus = $pointGroupStatus->getComplianceViewStatus('activity_21');
        $healthCoachStatus = $pointGroupStatus->getComplianceViewStatus('work_with_health_coach_activity');
        $workshopStatus = $pointGroupStatus->getComplianceViewStatus('activity_23');
        $extraELearningStatus = $pointGroupStatus->getComplianceViewStatus('complete_elearning_additonal');
        $preventiveScreeningsStatus = $pointGroupStatus->getComplianceViewStatus('activity_26');
        $volunteeringStatus = $pointGroupStatus->getComplianceViewStatus('activity_24');

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


    <div class="pageHeading">Rewards/To-Do Summary Page</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the 2013-2014 Midland Paper Wellness Rewards benefit.</p>

    <p>To receive the incentive, you MUST take action and meet all criteria below:</p>

    <ol>
        <li>Complete <strong>ALL</strong> of the core required actions by February 28, 2014.</li>
        <li>Get 100 or more points from key screening results and key actions taken for good health - getting a minimum
            of 25 points each quarter by the quarterly deadline until 100 or more points is reached for the rewards
            year.
        </li>
    </ol>

    <p>Employees and eligible spouses meeting all criteria will each receive $360 in rebates/cash ($720/family).</p>

    <div class="pageHeading"><a href="/content/1094">Click here for more details about the 2013-2014 Wellness Rewards
        benefit and requirements</a>.
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
        <th>1. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
        <td>Date Done</td>
        <td>Status</td>
        <td>Links</td>
    </tr>
    <tr>
        <td>A. Annual Wellness Screening</td>
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
        <td>B. Annual Health Power Assessment</td>
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
        <td>C. Complete Key e-Learning Lessons</td>
        <td class="center">
            <?php echo $completeRequiredELearningStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $completeRequiredELearningStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($completeRequiredELearningStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td>D. Have a Main Doctor</td>
        <td class="center">
            <?php echo $updateDoctorInformationStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $updateDoctorInformationStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($updateDoctorInformationStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td>E. Personal Contact Info Up-to-Date</td>
        <td class="center">
            <?php echo $updateContactInformationStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $updateContactInformationStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($updateContactInformationStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="right" style="font-size: 7pt;">
            All Core Actions Done on or before 02/28/2014
        </td>
        <td></td>
        <td class="center">
            <?php echo $coreGroupStatus->isCompliant() ? 'Yes' : 'No'; ?>
        </td>
        <td class="center">Yes = 1st Quarter Criteria Met</td>
    </tr>
    <tr class="headerRow">
        <th>2. And, earn 100 or more points from A-G below.</th>
        <td># Points Earned</td>
        <td># Points Possible</td>
        <td>Links</td>
    </tr>
    <tr>
        <td>A. Have these screening results in the ideal zone:</td>
        <td colspan="3">

        </td>
    </tr>
    <tr>
        <td>
            <ul>
                <li>Total cholesterol</li>
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
                <li>HDL cholesterol</li>
            </ul>
        </td>
        <td class="center"><?php echo $hdlStatus->getPoints(); ?></td>
        <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li>LDL cholesterol</li>
            </ul>
        </td>
        <td class="center"><?php echo $ldlStatus->getPoints(); ?></td>
        <td class="center"><?php echo $ldlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li>Triglycerides</li>
            </ul>
        </td>
        <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
        <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>
            <ul>
                <li>Glucose</li>
            </ul>
        </td>
        <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
        <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>

    <tr>
        <td>
            <ul>
                <li>Blood pressure</li>
            </ul>
        </td>
        <td class="center"><?php echo $bloodPressureStatus->getPoints(); ?></td>
        <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>


    <tr>
        <td>
            <ul>
                <li>Better of body mass index or % body fat</li>
            </ul>
        </td>
        <td class="center"><?php echo $bodyFatBMIStatus->getPoints(); ?></td>
        <td class="center"><?php echo $bodyFatBMIStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>


    <tr>
        <td>
            <ul>
                <li>Non-Smoker/Low Exposure to Nicotine/Cotinine</li>
            </ul>
        </td>
        <td class="center"><?php echo $nonSmokingStatus->getPoints(); ?></td>
        <td class="center"><?php echo $nonSmokingStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td>B. Regular Physical Activity</td>
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
        <td>C. Work with Health Coach or Doctor on Goals</td>
        <td class="center"><?php echo $healthCoachStatus->getPoints(); ?></td>
        <td class="center"><?php echo $healthCoachStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($healthCoachStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td>D. Health Trainings/Events Attended</td>
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
        <td class="center"><?php echo $extraELearningStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($extraELearningStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>

    <tr>
        <td>F. Recommended Preventive Screenings/Exams Done</td>
        <td class="center"><?php echo $preventiveScreeningsStatus->getPoints(); ?></td>
        <td class="center"><?php echo $preventiveScreeningsStatus->getComplianceView()
            ->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($preventiveScreeningsStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td>G. Regular Volunteering - Type & Time</td>
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
        <td class="right">Total number as of <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
        <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
        <td></td>
    </tr>
    <tr class="headerRow">
        <th>3. Deadlines, Requirements & Status</th>
        <td># Earned</td>
        <td>Status</td>
        <td>Minimum Needed for Each Quarterly Credit</td>
    </tr>
    <tr>
        <td style="text-align: right;">Total & Credit Status: Through 02/28/2014 =</td>
        <td class="center"><?php echo $firstStatus->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $firstStatus->getLight(); ?>" class="light"/></td>
        <td class="center">25 or more points + 1A-E Done</td>
    </tr>
    <tr>
        <td style="text-align: right;">Through 2/28/2014 =</td>
        <td class="center"><?php echo $secondStatus->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $secondStatus->getLight(); ?>" class="light"/></td>
        <td class="center">25+ extra or 50+ cumulative</td>
    </tr>
    <tr>
        <td style="text-align: right;">Through 5/31/2014 =</td>
        <td class="center"><?php echo $thirdStatus->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $thirdStatus->getLight(); ?>" class="light"/></td>
        <td class="center">25+ extra or 75+ cumulative</td>
    </tr>
    <tr>
        <td style="text-align: right;">Through 8/31/2014 =</td>
        <td class="center"><?php echo $fourthStatus->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $fourthStatus->getLight(); ?>" class="light"/></td>
        <td class="center">25+ extra or 100+ cumulative</td>
    </tr>

    <tr>
        <td style="text-align: right;">Total & Raffle Status** as of <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $status->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
        <td class="center">150 or more cumulative</td>
    </tr>
    <tr>
        <td colspan="4">* Become eligible for raffle and other rewards and incentives with 150 or more points</td>
    </tr>
    </tbody>
    </table>
    <?php
    }
}