<?php

class CandlerGraduateFromCoachingSessionComplianceView extends GraduateFromCoachingSessionComplianceView
{
    public function getStatus(User $user)
    {
        require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/administration/userCenterLib.php', sfConfig::get('sf_root_dir'));

        if(validForCoaching($user)) {
            return parent::getStatus($user);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }
}

class CandlerWorkshop extends AttendCompanyWorkshopComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(43);
    }
}

class StJosephCandlerComplianceProgram extends ComplianceProgram
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

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Annual Online Health Assessment (HPA)');
        $coreGroup->addComplianceView($hraView);

        $safetyWorkshopView = new CandlerWorkshop($programStart, $programEnd, 1, true);
        $safetyWorkshopView->setName('safety_workshop');
        $safetyWorkshopView->setReportName('Complete the Getting Healthwise Workshop');
        $coreGroup->addComplianceView($safetyWorkshopView);

        $ongoingHealthCoachingView = new CandlerGraduateFromCoachingSessionComplianceView($programStart, $programEnd);
        $ongoingHealthCoachingView->setReportName('Ongoing Phone Coaching for High Risk');
        $ongoingHealthCoachingView->setName('ongoing_phone_coaching');
        $ongoingHealthCoachingView->addLink(new Link('Health Coach Info', '/content/8733'));
        $coreGroup->addComplianceView($ongoingHealthCoachingView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'And, earn 100 or more points from A-K below by '.date('F d, Y', $programEnd));

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
        $workWithHealthCoachView->setReportName('Onsite Coaching Sessions Completed (1 per quarter)');
        $workWithHealthCoachView->setMaximumNumberOfPoints(45);
        $extraGroup->addComplianceView($workWithHealthCoachView);

        $attendWorkshopView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 5);
        $attendWorkshopView->setReportName('It\'s Cool to Be Healthy Events Attended');
        $attendWorkshopView->setName('attend_workshop');
        $attendWorkshopView->setMaximumNumberOfPoints(40);
        $extraGroup->addComplianceView($attendWorkshopView);

        $additionalELearningLessonsView = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd, 1);
        $additionalELearningLessonsView->setReportName('Complete e-Learning Lessons');
        $additionalELearningLessonsView->setName('elearning');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(35);
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setName('volunteering');
        $volunteeringView->setReportName('Volunteer Time to Charity');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(30);
        $extraGroup->addComplianceView($volunteeringView);

        $mindfulActivityView = new MindfulActivityComplianceView($programStart, $programEnd, 1);
        $mindfulActivityView->setMaximumNumberOfPoints(30);
        $mindfulActivityView->setName('mindful_activity');
        $mindfulActivityView->setReportName('Resilience-Building Activities');
        $extraGroup->addComplianceView($mindfulActivityView);

        $seminarView = new AttendSeminarComplianceView($programStart, $programEnd, 10);
        $seminarView->setName('attend_seminar');
        $seminarView->setReportName('It\'s Cool to Be Healthy SEMINARS Attended');
        $seminarView->setMaximumNumberOfPoints(30);
        $extraGroup->addComplianceView($seminarView);

        $extraGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($extraGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new CandlerScreeningProgramReportPrinter();

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
            $printer = new CandlerComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true);

        return $printer;
    }
}

class CandlerScreeningProgramReportPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>
    <br/>
    <br/>
    <p>
        The following measures are strongly connected with your ability to prevent and avoid one or more of the
        following:
    </p>
    <ul>
        <li>
            Clogged arteries, heart attacks and strokes;
        </li>
        <li>
            Diabetes, loss of vision, amputations &amp; other complications;
        </li>
        <li>
            Certain cancers - lung, gum, lip, tongue, throat, breast . . .
        </li>
        <li>
            Back pain, hip and knee replacements;
        </li>
        <li>
            Loss of mobility and quality of life at a young age;&nbsp; and
        </li>
        <li>
            Loss of life at a young age.
        </li>
    </ul>
    <table border="0" id="ratingsTable" width="95%">
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
            &nbsp;</td>
        <td align="center" width="72">
            &nbsp;</td>
        <td align="center" width="73">
            &nbsp;</td>
        <td align="center" width="112">
            &nbsp;</td>
    </tr>
    <tr height="36px">
        <td>
            <p>
                <em>Points for each result<br/>
                </em><em>that falls in this column =</em></p>
        </td>
        <td align="center" bgcolor="#ccffcc" class="grayArrow" width="72">
            10 points
        </td>
        <td align="center" bgcolor="#ffff00" class="grayArrow" width="73">
            5 points
        </td>
        <td align="center" bgcolor="#ff909a" class="grayArrow" width="112">
            0 points
        </td>
    </tr>
    <tr>
        <td>
            <u>Key measures and ranges</u></td>
        <td align="center" bgcolor="#ccffcc" width="72">
            &nbsp;</td>
        <td align="center" bgcolor="#ffff00" width="73">
            &nbsp;</td>
        <td align="center" bgcolor="#ff909a" width="112">
            &nbsp;</td>
    </tr>
    <tr>
        <td>
            <ol>
                <li>
                    <strong>Total cholesterol</strong></li>
            </ol>
        </td>
        <td align="center" bgcolor="#ccffcc" width="72">
            &lt;200
        </td>
        <td align="center" bgcolor="#ffff00" width="73">
            200-240
        </td>
        <td align="center" bgcolor="#ff909a" width="112">
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
        <td align="center" bgcolor="#ccffcc" width="72">
            &ge;40
        </td>
        <td align="center" bgcolor="#ffff00" width="73">
            25-39
        </td>
        <td align="center" bgcolor="#ff909a" width="112">
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
        <td align="center" bgcolor="#ccffcc" width="72">
            &le;129
        </td>
        <td align="center" bgcolor="#ffff00" width="73">
            130-158
        </td>
        <td align="center" bgcolor="#ff909a" width="112">
            &ge;159
        </td>
    </tr>
    <tr>
        <td>
            <ol start="4">
                <li>
                    <strong>Blood pressure</strong><br/>
                    Systolic<br/>
                    Diastolic
                </li>
            </ol>
        </td>
        <td align="center" bgcolor="#ccffcc" valign="bottom" width="72">
            &lt;120/<br/>
            &lt;80
        </td>
        <td align="center" bgcolor="#ffff00" valign="bottom" width="73">
            120-139/<br/>
            80-89
        </td>
        <td align="center" bgcolor="#ff909a" valign="bottom" width="112">
            &ge;140/<br/>
            &ge;90
        </td>
    </tr>
    <tr>
        <td>
            <ol start="5">
                <li>
                    <strong>Glucose</strong></li>
            </ol>
        </td>
        <td align="center" bgcolor="#ccffcc" width="72">
            &lt;100
        </td>
        <td align="center" bgcolor="#ffff00" width="73">
            100-124
        </td>
        <td align="center" bgcolor="#ff909a" width="112">
            &ge;125
        </td>
    </tr>
    <tr>
        <td>
            <ol start="6">
                <li>
                    <strong>Triglycerides</strong></li>
            </ol>
        </td>
        <td align="center" bgcolor="#ccffcc" width="72">
            &lt;150
        </td>
        <td align="center" bgcolor="#ffff00" width="73">
            150-199
        </td>
        <td align="center" bgcolor="#ff909a" width="112">
            &ge;200
        </td>
    </tr>
    <tr>
        <td valign="bottom">
            <ol start="7">
                <li>
                    The better of:<br/>
                    <strong>Body Mass Index&nbsp;&nbsp;<br/>
                    </strong>&bull;&nbsp; men &amp; women<br/>
                    - OR -<br/>
                    <strong>% Body Fat:</strong><br/>
                    &bull; Men<br/>
                    &bull; Women
                </li>
            </ol>
        </td>
        <td align="center" bgcolor="#ccffcc" valign="bottom" width="72">
            <p>
                18.5&lt;25<br/>
                <br/>
                <br/>
                4&lt;18%<br/>
                12&lt;25%</p>
        </td>
        <td align="center" bgcolor="#ffff00" valign="bottom" width="73">
            <p>
                25&lt;30<br/>
                <br/>
                <br/>
                18&lt;25<br/>
                25&lt;32</p>
        </td>
        <td align="center" bgcolor="#ff909a" valign="bottom" width="112">
            <p>
                &ge;30; &lt;18.5<br/>
                <br/>
                <br/>
                &ge;25; &lt;4%<br/>
                &ge;32; &lt;12%</p>
        </td>
    </tr>
    <tr>
        <td>
            <ol start="8">
                <li>
                    <strong>Tobacco/Cotinine</strong></li>
            </ol>
        </td>
        <td align="center" bgcolor="#ccffcc" width="72">
            &lt;2
        </td>
        <td align="center" bgcolor="#ffff00" width="73">
            2-9
        </td>
        <td align="center" bgcolor="#ff909a" width="112">
            &ge;10
        </td>
    </tr>
    </tbody>
    </table>
    <p><strong>Maximum Possible = 80 points</strong></p>
    <?php
    }
}

class CandlerComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $safetyWorkshopStatus = $coreGroupStatus->getComplianceViewStatus('safety_workshop');
        $ongoingPhoneCoachingStatus = $coreGroupStatus->getComplianceViewStatus('ongoing_phone_coaching');

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
        $workWithHealthCoachStatus = $pointGroupStatus->getComplianceViewStatus('work_with_health_coach');
        $workshopStatus = $pointGroupStatus->getComplianceViewStatus('attend_workshop');
        $extraELearningStatus = $pointGroupStatus->getComplianceViewStatus('elearning');
        $volunteeringStatus = $pointGroupStatus->getComplianceViewStatus('volunteering');
        $mindfulActivityStatus = $pointGroupStatus->getComplianceViewStatus('mindful_activity');
        $seminarStatus = $pointGroupStatus->getComplianceViewStatus('attend_seminar');
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


    <div class="pageHeading">2012 Well-Being Rewards Program</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the 2012 Well-Being Rewards Program. To receive the Well-Being Reward, eligible
        employees MUST take action and meet the following requirements by September 30, 2012:</p>
    <ol>
        <li>Complete ALL of the core required actions; AND</li>
        <li>Earn 100 or more points from key screening results and key actions taken for good health.</li>
    </ol>
    <p>
        Your Reward varies based on your medical benefit coverage:
    </p>
    <ul>
        <li>Employees on the medical plan who complete the required core actions and earn 100 or more points from key
            screening results and other available actions by September 30, 2012 will receive a check for 10% of the cost
            of their annual medical premium.
        </li>
        <li>Employees NOT on the medical plan will receive a check for 10% of the premium for employee-only coverage in
            the HRA plan.
        </li>
    </ul>
    <p><a href="/content/1094">Click here to learn more about the 2012 rewards program, the related actions and other
        details.</a></p>

    <p>Updates & Timing: To get actions done and earn points, click on the links below. If the points or status did not
        change for an item you are working on, you may need to go back and enter missing information or entries to earn
        more points. The status for wellness screening will not change until after your report is mailed. Thanks for
        your actions and patience!</p>

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
        <td><a href="/content/1094#1aHS">A. <?php echo $completeScreeningStatus->getComplianceView()
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
        <td><a href="/content/1094#1bHA">B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></a>
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
        <td><a href="/content/1094#1cGHW">C. <?php echo $safetyWorkshopStatus->getComplianceView()
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
        <td><a href="/content/1094#1dHC">D. <?php echo $ongoingPhoneCoachingStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center">
            <?php echo $ongoingPhoneCoachingStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $ongoingPhoneCoachingStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($ongoingPhoneCoachingStatus->getComplianceView()->getLinks() as $link) {
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
        <td><a href="/content/1094#2fHC">F. <?php echo $workWithHealthCoachStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $workWithHealthCoachStatus->getPoints(); ?></td>
        <td class="center"><?php echo $workWithHealthCoachStatus->getComplianceView()
            ->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($workWithHealthCoachStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML().' ';
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2gCoolEvents">G. <?php echo $workshopStatus->getComplianceView()
            ->getReportName() ?></a></td>
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
        <td><a href="/content/1094#2jResBuild">J. <?php echo $mindfulActivityStatus->getComplianceView()
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
        <td><a href="/content/1094#2kCoolSem">K. <?php echo $seminarStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center"><?php echo $seminarStatus->getPoints(); ?></td>
        <td class="center"><?php echo $seminarStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($seminarStatus->getComplianceView()->getLinks() as $link) {
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
        <td style="text-align: right;">Status of 1ABCD + ≥ 100 points as of: <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $status->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
        <td class="center"></td>
    </tr>
    </tbody>
    </table>
    <?php
    }
}
