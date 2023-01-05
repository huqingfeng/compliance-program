<?php

class MEI2011MindfulActivityComplianceView extends CompleteActivityComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity(ActivityTrackerActivity::MINDFUL_ACTIVITY);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $minutes = 0;

        foreach($records as $record) {
            $questionAnswers = $record->getQuestionAnswers();

            if(isset($questionAnswers[ActivityTrackerQuestion::MINUTES])) {
                $qMinutes = (int) $questionAnswers[ActivityTrackerQuestion::MINUTES]->getAnswer();

                $minutes += $qMinutes;
            }
        }

        $totalPoints = floor($minutes / 20);

        return new ComplianceViewStatus($this, null, $totalPoints);
    }
}

class MEI2011ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.date('F d, Y', $programEnd));

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Annual Health Screening');
        $screeningView->setName('complete_screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView('2011-06-01', $programEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Annual Online Health Assessment (HPA)');
        $coreGroup->addComplianceView($hraView);

        $surveyView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $surveyView->setReportName('Complete Well-Being Culture Survey (Spring 2012)');
        $surveyView->setName('well_being_survey');
        $coreGroup->addComplianceView($surveyView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'Earn 100 or more points from A-J below by '.$this->getEndDate('m/d/Y'));

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
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $extraGroup->addComplianceView($bodyFatBMIView);

        $nonSmokerView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
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

        $doctorView = new RJFWorkWithDoctorComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $doctorView->setReportName('Work with Doctor on Health Goals');
        $doctorView->setMaximumNumberOfPoints(20);
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $doctorView->setName('work_with_doctor');
        $doctorView->addLink(new Link('More Info', '/content/1094#2gDoc'));
        $doctorView->addLink(new Link('I did this', '/resources/2407/mei incentive dcm note from doctor 081210.pdf'));
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

        $mindfulActivityView = new MEI2011MindfulActivityComplianceView($programStart, $programEnd, 1);
        $mindfulActivityView->setMaximumNumberOfPoints(30);
        $mindfulActivityView->setName('mindful_activity');
        $extraGroup->addComplianceView($mindfulActivityView);

        $financialView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $financialView->setReportName('Increase your financial well-being');
        $financialView->setName('financial');
        $financialView->addLink(new Link('Verify that you have increased your 401K contribution', '/content/mei-compliance-401k'));
        $financialView->addLink(new Link('Complete one or more Money 101 lessons', '/content/mei-compliance-link?link='.urlencode('http://money.cnn.com/magazines/moneymag/money101/index.html'), false, '_blank'));
        $financialView->setMaximumNumberOfPoints(10);
        $extraGroup->addComplianceView($financialView);

        $drugView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $drugView->setReportName('Use drug/supplement safety & interaction tools');
        $drugView->setName('drug');

        $drugView->addLink(new Link('Access the HealthWise Knowledge base', '/content/mei-compliance-link?link='.urlencode('/healthwise/knowledge-base/documents/ug5135'), false, '_blank'));
        $drugView->addLink(new Link('View the National Institutes of Health drug information center', '/content/mei-compliance-link?link='.urlencode('http://www.nlm.nih.gov/medlineplus/druginformation.html'), false, '_blank'));

        $drugView->setMaximumNumberOfPoints(10);
        $extraGroup->addComplianceView($drugView);

        $extraGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($extraGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new MEI2011ScreeningPrinter();
        } else {
            $printer = new MEI2011ComplianceProgramReportPrinter();
        }

        return $printer;
    }
}


class MEI2011ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $surveyStatus = $coreGroupStatus->getComplianceViewStatus('well_being_survey');

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
        $workWithDoctorStatus = $pointGroupStatus->getComplianceViewStatus('work_with_doctor');
        $workshopStatus = $pointGroupStatus->getComplianceViewStatus('attend_workshop');
        $extraELearningStatus = $pointGroupStatus->getComplianceViewStatus('elearning');
        $volunteeringStatus = $pointGroupStatus->getComplianceViewStatus('volunteering');
        $mindfulActivityStatus = $pointGroupStatus->getComplianceViewStatus('mindful_activity');

        $drugStatus = $pointGroupStatus->getComplianceViewStatus('drug');
        $financialStatus = $pointGroupStatus->getComplianceViewStatus('financial');
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
        Welcome to your summary page for the 2012 Wellness Rewards Program. To
        receive the incentives, eligible employees MUST take action and meet all
        criteria below by <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?>:
    </p>

    <ol>
        <li>
            Complete <strong>ALL</strong> of the core required actions; And
        </li>
        <li>
            Earn 100 or more points from key screening results and key actions taken
            for good health.
        </li>
    </ol>

    <p>
        Employees meeting all criteria will each receive a reward of $300 (paid after 6/30/2012).
    </p>

    <div class="pageHeading">
        <a href="/content/1094">
            Click here for the 2012 Wellness Rewards Criteria Details
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
        <td><a href="/content/1094#1cHCSW">C. <?php echo $surveyStatus->getComplianceView()->getReportName() ?></a></td>
        <td class="center">
            <?php echo $surveyStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $surveyStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($surveyStatus->getComplianceView()->getLinks() as $link) {
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
            <?php echo $coreGroupStatus->isCompliant() ? 'Yes' : 'No' ?>
        </td>
        <td class="center">
            <?php echo $coreGroupStatus->isCompliant() ? 'Great – Core Actions Done!' : 'NOT Done Yet'; ?>
        </td>
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
        <td><a href="/content/1094#2fDoc">F. <?php echo $workWithDoctorStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $workWithDoctorStatus->getPoints(); ?></td>
        <td class="center"><?php echo $workWithDoctorStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($workWithDoctorStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML().' ';
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2gKHE">G. <?php echo $workshopStatus->getComplianceView()->getReportName() ?></a>
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
        <td><a href="/content/1094#2hEL">H. <?php echo $extraELearningStatus->getComplianceView()
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
        <td><a href="/content/1094#2iVT">I. <?php echo $volunteeringStatus->getComplianceView()->getReportName() ?></a>
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
        <td><a href="/content/1094#2jMA">J. <?php echo $mindfulActivityStatus->getComplianceView()
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
        <td><a href="/content/1094#2kFin">K. <?php echo $financialStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $financialStatus->getPoints(); ?></td>
        <td class="center"><?php echo $financialStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            $i = 0;
            foreach($financialStatus->getComplianceView()->getLinks() as $link) {
                if($i) echo '<br/>';

                echo $link->getHTML();

                $i++;
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2lDrug">L. <?php echo $drugStatus->getComplianceView()->getReportName() ?></a></td>
        <td class="center"><?php echo $drugStatus->getPoints(); ?></td>
        <td class="center"><?php echo $drugStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            $i = 0;
            foreach($drugStatus->getComplianceView()->getLinks() as $link) {
                if($i) echo '<br/>';

                echo $link->getHTML();

                $i++;
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


class AFSCME2012Printer extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>
    <p style="font-size:smaller;">* Not applicable at this time. Will change
        if a Care Counselor or Health Coach is trying to reach you, made
        recommendations to complete and/or required calls are not being made.</p>
    <?php
    }

    public function showGroup($group)
    {
        if($group->getName() == 'demonstrate') {
            $this->tableHeaders['completed'] = 'Updated';
        } else {
            $this->tableHeaders['completed'] = 'Completed';
        }

        return true;
    }

    public function __construct()
    {
        $this->setScreeningResultsLink(new Link('Try To Have These Screening Results in the healthy green zone:', '/content/1094#3abio'));

        $this->numberScreeningCategory = false;
        $this->showName = true;
        $this->setShowTotal(false);

        $this->screeningLinkText = 'Click for these %s results and health status of each';

        $this->addStatusCallbackColumn('Deadline', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            $default = $view instanceof DateBasedComplianceView ?
                $view->getEndDate('m/d/Y') : '';

            return $view->getAttribute('deadline', $default);
        });
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
    <style type="text/css">
        .phipTable {
            width:100%;
            border-collapse:collapse;
            font-size:8pt;
        }

        .phipTable .headerRow, #legendText {
            background-color:#002AAE;
            font-weight:normal;
            color:#FFFFFF;
            font-size:10pt;
        }

        .status img {
            width:25px;
        }
    </style>
    <p>Welcome to your new 2012 PHIP. As you know, all adult HIP participants must
        get certain things done throughout the year in order to maintain eligibility
        for the Health Improvement Plan benefit.</p>

    <p>All current requirements (To-Dos) are listed below:</p>

    <ul>
        <li>It us your responsibility to complete all of HIP’s requirements or risk
            disenrollment from HIP and transfer to the Standard Plan, which requires
            you to pay more of your health care costs.
        </li>
        <li>The enrollment agreement you signed with HIP obligates you to learn
            and do more to improve your health and work with health professionals
            in securing good quality, evidence-based health care.
        </li>
        <li>As a HIP member, you must demonstrate the you’re doing all you can do
            to get and stay healthy and make sound, evidence-based decisions
            concerning your use of health care.
        </li>
    </ul>
    <p>Here's what the table below says:</p>
    <ul>
        <li>The current requirements and your current status for each are
            summarized below.
        </li>
        <li>In the first column, click on the text in blue to learn why the
            action is important ­ <a href="/content/1094">or click here for all actions</a>.
        </li>
        <li>Use the Action Links in the right column to get things done or more
            information.
        </li>
        <li>Please visit this page often to check your status, get To-Dos done
            and to see if new lessons or requirements have been added.
        </li>
    </ul>
    <?php
    }
}


class MEI2011ScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        ?>
    <a href="/compliance_programs">Back to Rewards</a>
    <br/>
    <br/>
    <?php parent::printReport($status) ?>
    <br/>
    <br/>
    <table width="100%" border="0" style="font-size: 10px;" id="ratingsTable">
    <tbody>
    <tr>
        <td width="190">
            Risk ratings &amp; colors =
        </td>
        <td align="center" width="72">
            <strong><font color="#006600">OK/Good</font></strong></td>
        <td align="center" width="73">
            <strong><font color="#ff9933">Borderline</font></strong></td>
        <td align="center" width="112">
            <strong><font color="#ff0000">At-Risk</font> </strong></td>
    </tr>
    <tr>
        <td>
        </td>
        <td align="center" width="72">
        </td>
        <td align="center" width="73">
        </td>
        <td align="center" width="112">
        </td>
    </tr>
    <tr height="36px">
        <td>
            <p>
                <em>Points for each result<br>
                </em><em>that falls in this column =</em></p>
        </td>
        <td align="center" width="72" bgcolor="#ccffcc" class="grayArrow">
            10 points
        </td>
        <td align="center" width="73" bgcolor="#ffff00" class="grayArrow">
            5 points
        </td>
        <td align="center" width="112" bgcolor="#ff909a" class="grayArrow">
            0 points
        </td>
    </tr>
    <tr>
        <td>
            <u>Key measures and ranges</u></td>
        <td align="center" width="72" bgcolor="#ccffcc">
        </td>
        <td align="center" width="73" bgcolor="#ffff00">
        </td>
        <td align="center" width="112" bgcolor="#ff909a">
        </td>
    </tr>
    <tr>
        <td>
            <ol>
                <li>
                    <strong>Total cholesterol</strong></li>
            </ol>
        </td>
        <td align="center" width="72" bgcolor="#ccffcc">
            &lt;200
        </td>
        <td align="center" width="73" bgcolor="#ffff00">
            200-240
        </td>
        <td align="center" width="112" bgcolor="#ff909a">
            &gt;240
        </td>
    </tr>
    <tr>
        <td>
            <ol start="2">
                <li>
                    <strong>HDL cholesterol</strong></li>
            </ol>
        </td>
        <td align="center" width="72" bgcolor="#ccffcc">
            ≥40
        </td>
        <td align="center" width="73" bgcolor="#ffff00">
            25-39
        </td>
        <td align="center" width="112" bgcolor="#ff909a">
            &lt;25
        </td>
    </tr>
    <tr>
        <td>
            <ol start="3">
                <li>
                    <strong>LDL cholesterol</strong></li>
            </ol>
        </td>
        <td align="center" width="72" bgcolor="#ccffcc">
            ≤129
        </td>
        <td align="center" width="73" bgcolor="#ffff00">
            130-158
        </td>
        <td align="center" width="112" bgcolor="#ff909a">
            ≥159
        </td>
    </tr>
    <tr>
        <td>
            <ol start="4">
                <li>
                    <strong>Blood pressure</strong><br>
                    Systolic<br>
                    Diastolic
                </li>
            </ol>
        </td>
        <td align="center" width="72" valign="bottom" bgcolor="#ccffcc">
            &lt;120/<br>
            &lt;80
        </td>
        <td align="center" width="73" valign="bottom" bgcolor="#ffff00">
            120-139/<br>
            80-89
        </td>
        <td align="center" width="112" valign="bottom" bgcolor="#ff909a">
            ≥140/<br>
            ≥90
        </td>
    </tr>
    <tr>
        <td>
            <ol start="5">
                <li>
                    <strong>Glucose</strong></li>
            </ol>
        </td>
        <td align="center" width="72" bgcolor="#ccffcc">
            &lt;100
        </td>
        <td align="center" width="73" bgcolor="#ffff00">
            100-124
        </td>
        <td align="center" width="112" bgcolor="#ff909a">
            ≥125
        </td>
    </tr>
    <tr>
        <td>
            <ol start="6">
                <li>
                    <strong>Triglycerides</strong></li>
            </ol>
        </td>
        <td align="center" width="72" bgcolor="#ccffcc">
            &lt;150
        </td>
        <td align="center" width="73" bgcolor="#ffff00">
            150-199
        </td>
        <td align="center" width="112" bgcolor="#ff909a">
            ≥200
        </td>
    </tr>
    <tr>
        <td valign="bottom">
            <ol start="7">
                <li>
                    The better of:<br>
                    <strong>Body Mass Index <br>
                    </strong>• men &amp; women<br>
                    - OR -<br>
                    <strong>% Body Fat:</strong><br>
                    • Men<br>
                    • Women
                </li>
            </ol>
        </td>
        <td align="center" width="72" valign="bottom" bgcolor="#ccffcc">
            <p>
                18.5&lt;25<br>
                <br>
                <br>
                4&lt;18%<br>
                12&lt;25%</p>
        </td>
        <td align="center" width="73" valign="bottom" bgcolor="#ffff00">
            <p>
                25&lt;30<br>
                <br>
                <br>
                18&lt;25<br>
                25&lt;32</p>
        </td>
        <td align="center" width="112" valign="bottom" bgcolor="#ff909a">
            <p>
                ≥30; &lt;18.5<br>
                <br>
                <br>
                ≥25; &lt;4%<br>
                ≥32; &lt;12%</p>
        </td>
    </tr>
    <tr>
        <td>
            <ol start="8">
                <li>
                    <strong>Tobacco/Cotinine</strong></li>
            </ol>
        </td>
        <td align="center" width="72" bgcolor="#ccffcc">
            &lt;2
        </td>
        <td align="center" width="73" bgcolor="#ffff00">
            2-9
        </td>
        <td align="center" width="112" bgcolor="#ff909a">
            ≥10
        </td>
    </tr>
    </tbody>
    </table>
    <?php
    }
}
