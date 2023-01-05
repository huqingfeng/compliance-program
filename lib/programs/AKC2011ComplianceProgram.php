<?php
class AKCScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        echo '<br/><br/><br/>'.Content::getApplicableContent('1175');
    }
}

class AKC2011CoachingView extends GraduateFromCoachingSessionComplianceView
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

class AKCWorkshop2011 extends AttendCompanyWorkshopComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(43);
    }
}

class AKC2011ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.date('F d, Y', $programEnd));

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Wellness Screening');
        $screeningView->setName('complete_screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete Health Power Assessment');
        $coreGroup->addComplianceView($hraView);
        
        $coachingView = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);       
        $coachingView->setName('coaching');
        $coachingView->setReportName('Health Coaching');
        $coreGroup->addComplianceView($coachingView);



        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'Other things that can add to your well-being & other goals.
     Try to get 100 or more points by
 '.date('F d, Y', $programEnd));

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

        $doctorView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorView->setReportName('Have Main Doctor/Primary Care Provider');
        $doctorView->setName('doctor');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $extraGroup->addComplianceView($doctorView);

        $preventiveExamsView = new ObtainPreventiveExamComplianceView($programStart, $programEnd, 5);
        $preventiveExamsView->setReportName('Get recommended Preventive Screenings/Exams');
        $preventiveExamsView->setMaximumNumberOfPoints(20);
        $preventiveExamsView->setName('do_preventive_exams');
        $extraGroup->addComplianceView($preventiveExamsView);

        $fluVaccineView = new ImmunizationsActivityComplianceView($programStart, $programEnd, 5);
        $fluVaccineView->setMaximumNumberOfPoints(20);
        $fluVaccineView->setReportName('Get recommended Immunizations');
        $fluVaccineView->setName('flu_vaccine');
        $extraGroup->addComplianceView($fluVaccineView);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Get Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(300);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setName('physical_activity');
        $extraGroup->addComplianceView($physicalActivityView);

        $additionalELearningLessonsView = new CompleteRequiredELearningLessonsComplianceView($programStart, $programEnd);
        $additionalELearningLessonsView->setNumberRequired(0);
        $additionalELearningLessonsView->setPointsPerLesson(5);
        $additionalELearningLessonsView->setReportName('Complete e-Learning Lessons');
        $additionalELearningLessonsView->setName('elearning');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(50);
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setName('volunteering');
        $volunteeringView->setReportName('Regular Volunteering - Type & Time');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(100);
        $extraGroup->addComplianceView($volunteeringView);
        
        $attendWellnessSeminarView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $attendWellnessSeminarView->setReportName('Attend Wellness Seminar');
        $attendWellnessSeminarView->setMaximumNumberOfPoints(200);
        $attendWellnessSeminarView->setMinutesDivisorForPoints(60);
        $attendWellnessSeminarView->setMonthlyPointLimit(30);
        $attendWellnessSeminarView->setName('attend_wellness_seminar');
        $extraGroup->addComplianceView($attendWellnessSeminarView);

        $extraGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($extraGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new AKCScreeningPrinter();

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
            $printer = new AKC2011ComplianceProgramReportPrinter();
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

class AKC2011ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $coreHealthCoaching = $coreGroupStatus->getComplianceViewStatus('coaching');

        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $totalCholesterolStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_total_cholesterol_screening_test');
        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_hdl_screening_test');
        $ldlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_ldl_screening_test');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_triglycerides_screening_test');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_glucose_screening_test');
        $bloodPressureStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_blood_pressure_screening_test');
        $bodyFatBMIStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_body_fat_bmi_screening_test');
        $nonSmokingStatus = $pointGroupStatus->getComplianceViewStatus('non_smoker_view');

        $haveDoctorStatus = $pointGroupStatus->getComplianceViewStatus('doctor');
        $preventiveScreeningsStatus = $pointGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $fluVaccineStatus = $pointGroupStatus->getComplianceViewStatus('flu_vaccine');
        $physicalActivityStatus = $pointGroupStatus->getComplianceViewStatus('physical_activity');
        $healthCoachStatus = $pointGroupStatus->getComplianceViewStatus('work_with_health_coach');
        $extraELearningStatus = $pointGroupStatus->getComplianceViewStatus('elearning');
        $volunteeringStatus = $pointGroupStatus->getComplianceViewStatus('volunteering');
        $mindfulActivityStatus = $pointGroupStatus->getComplianceViewStatus('mindful_activity');
        $seminarStatus = $pointGroupStatus->getComplianceViewStatus('attend_wellness_seminar');
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
    <p>Hello <?php echo $status->getUser() ?>,</p>
    <p></p>
    <p>Welcome to the new My Rewards/To-Do Summary page that is part of the Perfect Balance wellness benefit at Angie's
        Kettle Corn (AKC).</p>

    <p><span style="color:blue">Do you want to keep the lowest premium contribution, receive the greatest savings and other benefits?
    </p></span>

    <p><strong>By taking action and getting 1A and 1B done by December 1, 2011:</strong></p>

    <ul>
        <li>You will save <strong><u>$600</strong></u> in annual premium contributions in 2012 if you/your family will
            have medical benefit coverage in 2012 through AKC.
        </li>
        <li>If you do not have medical benefits through AKC, you will receive __ (TBD).</li>

    </ul>
    <p><strong>Note: If you do not get 1A and 1B done by the deadline, your medical benefit premium contribution will be
        $50 per month less beginning January 2012 for 12 months. </strong></p>

    <p>In addition, by getting 1A and 1B done PLUS your other actions and points in #2 below:
    <ul>
        <li>You will benefit from improvements in health, health care and wellbeing – now and throughout life.</li>
        <li>And, your efforts will help you avoid fewer health problems and related expenses each year.</li>
        <li>And, you may be helping others in many of the same ways through your actions and encouragement along the
            way.
        </li>
    </ul>
    </p>
    <p>Here are some tips about the table below and using it:
    <ul>
        <li>In the first column, click on the text in blue to learn why the action is important.</li>
        <li>Use the Action Links in the right column to get things done or more information.</li>
        <li>And, you may be helping others in many of the same ways through your actions and encouragement along the
            way.
        </li>
        <li><a href="content/1094">Click here</a> for more details about the requirements and rewards.
    </p>
    </ul>
    <p></p>
    <p></p>
    <p></p>
    
    <table class="phipTable" border="1">
    <thead id="legend">
        <tr>
            <td colspan="5">
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
        <td>Deadline</td>
        <td>Done</td>
        <td>Status</td>
        <td>Links</td>
    </tr>
    <tr>
        <td><a href="/content/1094#1ascreen">A. <?php echo $completeScreeningStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center">
            <?php echo $completeScreeningStatus->getComplianceView()->getEndDate('m/d/Y') ?>
        </td>
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
        <td><a href="/content/1094#1bhpa">B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center">
            <?php echo $completeHRAStatus->getComplianceView()->getEndDate('m/d/Y') ?>
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
        <td><a href="/content/1094#1bhpa">C. <?php echo $coreHealthCoaching->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center">
            <?php //echo $coreHealthCoaching->getComplianceView()->getEndDate('m/d/Y') ?>
        </td>
        <td class="center">
            <?php echo $coreHealthCoaching->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $coreHealthCoaching->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($coreHealthCoaching->getComplianceView()->getLinks() as $link) {
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
            <?php echo $coreGroupStatus->isCompliant() ? 'All Requirements Met' : 'Not Done Yet'; ?>
        </td>
        <td></td>
    </tr>
    <tr class="headerRow">
        <th colspan="2">2. <?php echo $pointGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
        <td># Points Earned</td>
        <td># Points Possible</td>
        <td>Links</td>
    </tr>
    <tr>
        <td colspan="2"><a href="/content/1094#2abio">A. Earn points based on how healthy these measures are:</a></td>
        <td colspan="3">
            <em>Note: Your results from 1A (above) link to 2A (here)</em>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <ul>
                <li><?php echo $totalCholesterolStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $totalCholesterolStatus->getPoints(); ?></td>
        <td class="center"><?php echo $totalCholesterolStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td rowspan="8" class="links">
            <a href="?id=104&preferredPrinter=ScreeningProgramReportPrinter">Click here for the 8 results</a><br/><br/>
            <a href="/content/989">Click for all screening results</a><br/><br/>
            Click on any measure for more info & to improve
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <ul>
                <li><?php echo $hdlStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $hdlStatus->getPoints(); ?></td>
        <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <ul>
                <li><?php echo $ldlStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $ldlStatus->getPoints(); ?></td>
        <td class="center"><?php echo $ldlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <ul>
                <li><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
        <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <ul>
                <li><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
        <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>

    <tr>
        <td colspan="2">
            <ul>
                <li><?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $bloodPressureStatus->getPoints(); ?></td>
        <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>


    <tr>
        <td colspan="2">
            <ul>
                <li><?php echo $bodyFatBMIStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $bodyFatBMIStatus->getPoints(); ?></td>
        <td class="center"><?php echo $bodyFatBMIStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>


    <tr>
        <td colspan="2">
            <ul>
                <li><?php echo $nonSmokingStatus->getComplianceView()->getReportName() ?></li>
            </ul>
        </td>
        <td class="center"><?php echo $nonSmokingStatus->getPoints(); ?></td>
        <td class="center"><?php echo $nonSmokingStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
    </tr>
    <tr>
        <td colspan="2"><a href="/content/1094#2bmaindoc">B. <?php echo $haveDoctorStatus->getComplianceView()
            ->getReportName() ?></a></td>
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
        <td colspan="2"><a href="/content/1094#2cprevscreen">C. <?php echo $preventiveScreeningsStatus
            ->getComplianceView()->getReportName() ?></a></td>
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
        <td colspan="2"><a href="/content/1094#2dimmun">D. <?php echo $fluVaccineStatus->getComplianceView()
            ->getReportName() ?></a></td>
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
        <td colspan="2"><a href="/content/1094#2ephys">E. <?php echo $physicalActivityStatus->getComplianceView()
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
        <td colspan="2"><a href="/content/1094#2felearn">F. <?php echo $extraELearningStatus->getComplianceView()
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
        <td colspan="2"><a href="/content/1094#2gvol">G. <?php echo $volunteeringStatus->getComplianceView()
            ->getReportName() ?></a></td>
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
        <td colspan="2"><a href="/content/1094#2gvol">G. <?php echo $seminarStatus->getComplianceView()
            ->getReportName() ?></a></td>
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
        <td class="right" colspan="2">My Total Points as of: <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
        <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
        <td></td>
    </tr>
    </tbody>
    </table>
    <?php
  }
}
