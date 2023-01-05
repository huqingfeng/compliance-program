<?php
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

class AttendHealthCareSafetyWorkshopComplianceView extends CompleteActivityComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity(59);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $status = count($records) ?
            ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

        return new ComplianceViewStatus($this, $status);
    }

    private $pointsPerRecord = null;
    private $isNumberRequired = false;
}

class Bepex20102011ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroupEnd = strtotime('2010-11-30');
        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.date('F d, Y', $coreGroupEnd));

        $screeningView = new CompleteScreeningComplianceView($programStart, $coreGroupEnd);
        $screeningView->setReportName('Annual Health Screening');
        $screeningView->setName('complete_screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $coreGroupEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Annual Online Health Assessment (HPA)');
        $coreGroup->addComplianceView($hraView);

        $cultureSurveyView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $cultureSurveyView->setReportName('Complete Well-Being Culture Survey');
        $cultureSurveyView->setName('culture_survey');
        $cultureSurveyView->addLink(new Link('Available August 2011', '#'));
        $coreGroup->addComplianceView($cultureSurveyView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'And, earn 250 or more points from A-M below by '.date('F d, Y', $programEnd));

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setName('total_cholesterol');
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($totalCholesterolView);
//
        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setName('hdl');
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setName('ldl');
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setName('triglycerides');
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setName('glucose');
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setName('body_fat_bmi');
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
        $doctorInformationView->setAttribute('link', '/content/1094#2aKBHM');
        $doctorInformationView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $doctorInformationView->setName('have_doctor');
        $extraGroup->addComplianceView($doctorInformationView);

        $preventiveExamsView = new ObtainPreventiveExamComplianceView($programStart, $programEnd, 5);
        $preventiveExamsView->setReportName('Do recommended Preventive Screenings/Exams');
        $preventiveExamsView->setAttribute('link', '/content/1094#2cRPS');
        $preventiveExamsView->setMaximumNumberOfPoints(20);
        $preventiveExamsView->setName('do_preventive_exams');
        $extraGroup->addComplianceView($preventiveExamsView);

        $fluVaccineView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setAttribute('link', '/content/1094#2dAFV');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $extraGroup->addComplianceView($fluVaccineView);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setAttribute('link', '/content/1094#2eRPA');
        $physicalActivityView->setMaximumNumberOfPoints(100);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setName('physical_activity');
        $extraGroup->addComplianceView($physicalActivityView);

        $workWithHealthCoachView = new GraduateFromCoachingSessionComplianceView($programStart, $programEnd);
        $workWithHealthCoachView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $workWithHealthCoachView->setName('work_with_health_coach');
        $workWithHealthCoachView->setAttribute('link', '/content/1094#2fIndHC');
        $workWithHealthCoachView->setReportName('Individual Coaching Sessions Completed');
        $workWithHealthCoachView->addLink(new Link('Health Coach Info & Sign-Up', '/content/8733'));
        $extraGroup->addComplianceView($workWithHealthCoachView);

        $onsiteHealthCoachView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $onsiteHealthCoachView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(30, 15, 0, 0));
        $onsiteHealthCoachView->setName('work_with_onsite_health_coach');
        $onsiteHealthCoachView->setAttribute('link', '/content/1094#2gOnsiteHC');
        $onsiteHealthCoachView->setReportName('Onsite Group Coaching Sessions Completed');
        $extraGroup->addComplianceView($onsiteHealthCoachView);

        $attendWorkshopView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 10);
        $attendWorkshopView->setReportName('Other BWell Traings & Events Attended');
        $attendWorkshopView->setAttribute('link', '/content/1094#2hEvents');
        $attendWorkshopView->setName('attend_workshop');
        $attendWorkshopView->setMaximumNumberOfPoints(100);
        $extraGroup->addComplianceView($attendWorkshopView);

        $additionalELearningLessonsView = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd, 1);
        $additionalELearningLessonsView->setReportName('Complete e-Learning Lessons');
        $additionalELearningLessonsView->setAttribute('link', '/content/1094#2iEL');
        $additionalELearningLessonsView->setName('elearning');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(20);
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setName('volunteering');
        $volunteeringView->setReportName('Volunteer Time to Charity');
        $volunteeringView->setAttribute('link', '/content/1094#2jVT');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(100);
        $extraGroup->addComplianceView($volunteeringView);

        $mindfulActivityView = new TimeBasedMindfulActivityComplianceView($programStart, $programEnd);
        $mindfulActivityView->setMaximumNumberOfPoints(30);
        $mindfulActivityView->setName('mindful_activity');
        $mindfulActivityView->setReportName('Resilience-Building Activities');
        $mindfulActivityView->setAttribute('link', '/content/1094#2kResBuild');
        $extraGroup->addComplianceView($mindfulActivityView);

        $workshopView = new AttendHealthCareSafetyWorkshopComplianceView($programStart, $programEnd);
        $workshopView->setMaximumNumberOfPoints(20);
        $workshopView->setName('safety_workshop');
        $workshopView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $workshopView->setReportName('Complete the Health Care Safety Workshop on April 18, 2011');
        $workshopView->setAttribute('link', '/content/1094#2lHCS');
        $extraGroup->addComplianceView($workshopView);

        $engageView = new EngageLovedOneComplianceView($programStart, $programEnd, 30);
        $engageView->setName('engage_loved_one');
        $engageView->setReportName('Engage a Loved One in a Well-Being Activity');
        $engageView->setAttribute('link', '/content/1094#2mEngage');
        $engageView->setMaximumNumberOfPoints(100);
        $extraGroup->addComplianceView($engageView);

        $extraGroup->setPointsRequiredForCompliance(250);
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
            $printer = new BepexComplianceProgramReportPrinter();
        }

        return $printer;
    }
}

class BepexComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $i = 0;

        extract($coreGroupStatus->getComplianceViewStatuses());
        extract($pointGroupStatus->getComplianceViewStatuses());
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


    <div class="pageHeading">2010-2011 Well-Being Rewards Program</div>


    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the 2010-2011 Well-Being Rewards Program. To receive the Well-Being Reward,
        eligible employees MUST take action and meet the following requirements by the deadlines noted:</p>
    <ol>
        <li>Complete ALL of the core required actions; AND</li>
        <li>Earn 250 or more points from key screening results and key actions taken for good health.</li>
    </ol>
    <p>
        Employees who complete the required core actions (some by November 30, 2010), commit to and earn 250 or more
        points from key screening results and other available actions by September 30, 2011:
    </p>
    <ol type="A">
        <li>
            Will receive their choice of the following rewards in 2011:
            <ul>
                <li>A health plan premium contribution credit of $20/month in 2011: OR</li>
                <li>8 well-being time off hours that can be used in 2011.</li>
            </ul>
        </li>
        <li>AND, will pre-qualify for $20, $30 or $40/month in premium contribution credits – OR – 8, 12 or 16
            well-being time off hours in 2012, if 250, 300 or 350 points are earned through September 30, 2011.
        </li>
    </ol>
    <p><a href="/content/1094">Click here to learn more about the 2010-2011 rewards program, the related actions and
        other details.</a></p>

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
            <?php $rowPrinter = function (ComplianceViewStatus $status) use (&$i) { ?>
            <tr>
                <td><a href="<?php echo $status->getComplianceView()
                    ->getAttribute('link') ?>"><?php echo sprintf('%s. %s', getLetterFromNumber($i), $status
                    ->getComplianceView()->getReportName()) ?></a></td>
                <td class="center"><?php echo $status->getComment() ?></td>
                <td class="center"><img src="<?php echo $status->getLight() ?>" class="light"/></td>
                <td class="links"><?php echo implode("\n", $status->getComplianceView()->getLinks()) ?></td>
            </tr>
            <?php $i++;
        } ?>
            <?php $rowPrinter($complete_screening) ?>
            <?php $rowPrinter($complete_hra) ?>
            <?php $rowPrinter($culture_survey) ?>
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
                    <?php echo $coreGroupStatus->isCompliant() ? 'Great – Core Actions Done!' : 'NOT Done Yet' ?>
                </td>
            </tr>
            <?php $i = 1 ?>
            <tr class="headerRow">
                <th>2. <?php echo $pointGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td># Points Earned</td>
                <td># Points Possible</td>
                <td>Links</td>
            </tr>
            <tr>
                <td><a href="/content/1094#2aKBHM">A. Have these screening results in the ideal zone:</a></td>
                <td colspan="3"></td>
            </tr>
            <?php $rowPrinter = function (ComplianceViewStatus $status, $showLinks = false) { ?>
            <tr>
                <td>
                    <ul>
                        <li><?php echo $status->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td class="center"><?php echo $status->getPoints(); ?></td>
                <td class="center"><?php echo $status->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <?php if($showLinks) : ?>
                <td rowspan="8" class="links">
                    <a href="?preferredPrinter=ScreeningProgramReportPrinter">Click here for the 8 results</a><br/><br/>
                    <a href="/content/989">Click for all screening results</a><br/><br/>
                    Click on any measure for more info & to improve
                </td>
                <?php endif ?>
            </tr>
            <?php } ?>

            <?php $rowPrinter($total_cholesterol, true) ?>
            <?php $rowPrinter($hdl) ?>
            <?php $rowPrinter($ldl) ?>
            <?php $rowPrinter($triglycerides) ?>
            <?php $rowPrinter($glucose) ?>
            <?php $rowPrinter($blood_pressure) ?>
            <?php $rowPrinter($body_fat_bmi) ?>
            <?php $rowPrinter($non_smoker_view) ?>

            <?php $rowPrinter = function (ComplianceViewStatus $status) use (&$i) { ?>
            <tr>
                <td><a href="<?php echo $status->getComplianceView()
                    ->getAttribute('link') ?>"><?php echo sprintf('%s. %s', getLetterFromNumber($i), $status
                    ->getComplianceView()->getReportName()) ?></a></td>
                <td class="center"><?php echo $status->getPoints(); ?></td>
                <td class="center"><?php echo $status->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <?php
                    foreach($status->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>
            <?php $i++;
        } ?>
            <?php $rowPrinter($have_doctor) ?>
            <?php $rowPrinter($do_preventive_exams) ?>
            <?php $rowPrinter($flu_vaccine) ?>
            <?php $rowPrinter($physical_activity) ?>
            <?php $rowPrinter($work_with_health_coach) ?>
            <?php $rowPrinter($work_with_onsite_health_coach) ?>
            <?php $rowPrinter($attend_workshop) ?>
            <?php $rowPrinter($elearning) ?>
            <?php $rowPrinter($volunteering) ?>
            <?php $rowPrinter($mindful_activity) ?>
            <?php $rowPrinter($safety_workshop) ?>
            <?php $rowPrinter($engage_loved_one) ?>
            <tr class="headerRow">
                <th>3. Deadlines, Requirements & Status</th>
                <td># Points</td>
                <td>Status</td>
                <td>Needed By <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?></td>
            </tr>
            <tr>
                <td style="text-align: right;">Status of 1ABC + ≥ 250 points as of: <?php echo date('m/d/Y'); ?> =</td>
                <td class="center"><?php echo $status->getPoints(); ?></td>
                <td class="center"><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
                <td class="center"></td>
            </tr>
        </tbody>
    </table>
    <?php
    }
}