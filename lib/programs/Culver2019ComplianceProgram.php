<?php
class Culver2019ScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        ?>
        <br/>
        <br/>
        <br/>
        <style>
            tr {
                border-bottom: 1px solid #fff;
            }

            td {
                padding: 10px 0px;
            }

            hr.divisor {
                width: 50px;
                margin: 2px 0px;
                border-top: 1px solid #444;
                border-bottom: none;
            }
        </style>
        <table border="0" width="95%" id="ratingsTable">
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
            <tr height="36px" style="border: none;">
                <td>
                    <p>
                        <em>Points for each result<br>
                        </em><em>that falls in this column =</em></p>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72" class="grayArrow">
                    10 points
                </td>
                <td bgcolor="#ffff00" align="center" width="73" class="grayArrow">
                    5 points
                </td>
                <td bgcolor="#ff909a" align="center" width="112" class="grayArrow">
                    0 points
                </td>
            </tr>
            <tr>
                <td>
                    <u>Key measures and ranges</u></td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    &nbsp;</td>
                <td bgcolor="#ffff00" align="center" width="73">
                    &nbsp;</td>
                <td bgcolor="#ff909a" align="center" width="112">
                    &nbsp;</td>
            </tr>
            <tr>
                <td>
                    <ol>
                        <li>
                            <strong>Total cholesterol</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    100 - <200<br><br>
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    200 - 240<br>
                    90 - <100
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    > 240<br>
                    < 90
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="2">
                        <li>
                            <strong>HDL cholesterol</strong>
                            <ul>
                                <li>Men</li>
                                <li>Woman</li>
                            </ul>
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    ≥ 40<br>
                    ≥ 50
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    25 < 40<br>
                    25 - <50
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    < 25<br>
                    < 25
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="3">
                        <li>
                            <strong>LDL cholesterol</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    ≤ 99
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    100 - 159
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 160
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="4">
                        <li>
                            <strong>Non-HDL cholesterol</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    ≤ 129
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    130 - 159
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 160
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="5">
                        <li>
                            <strong>Triglycerides</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    < 150
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    150 - <200
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 200
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <ol start="6">
                        <li>
                            <strong>Glucose (Fasting)</strong>
                            <ul>
                                <li>Men</li>
                                <br>
                                <br>
                                <li>Women</li>
                            </ul>
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72" valign="top">
                    <br>
                    70 - <100<br><br><br>
                    70 - <100
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    <br>
                    100 - 125<br>
                    50 - <70<br><br>
                    100 - 125<br>
                    40 - <70
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    <br>
                    ≥ 126<br>
                    < 50<br><br>
                    ≥ 126<br>
                    < 40
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="7">
                        <li>
                            <strong>Blood pressure</strong><br>
                            Systolic<hr class="divisor">
                            Diastolic
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    < 120<hr class="divisor">
                    < 80
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    120 - 139<hr class="divisor">
                    80 - 89
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 140<hr class="divisor">
                    ≥ 90
                </td>
            </tr>
            <tr>
                <td valign="bottom">
                    <ol start="8">
                        <li>
                            The better of:<br>
                            <strong>Body Mass Index&nbsp;&nbsp;<br>
                            </strong>•&nbsp; men &amp; women<br>
                            - OR -<br>
                            <strong>% Body Fat:</strong><br>
                            • Men<br>
                            • Women
                        </li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
                    <p>
                        18.5 - <25<br><br><br>
                        6 - <18%<br>
                        14 - <25%</p>
                </td>
                <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
                    <p>
                        25 - <30<br>
                        <br>
                        <br>
                        18 - <25%<br>
                        25 - <32%</p>
                </td>
                <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
                    <p>
                        ≥30; <18.5<br>
                        <br>
                        <br>
                        ≥25; <6%<br>
                        ≥32; <14%</p>
                </td>
            </tr>
            <tr>
                <td>
                    <ol start="9">
                        <li>
                            <strong>Tobacco/Cotinine</strong></li>
                    </ol>
                </td>
                <td bgcolor="#ccffcc" align="center" width="72">
                    < 2
                </td>
                <td bgcolor="#ffff00" align="center" width="73">
                    2 - 9
                </td>
                <td bgcolor="#ff909a" align="center" width="112">
                    ≥ 10
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }
}

class Culver2019CoachingView extends GraduateFromCoachingSessionComplianceView
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

class CulverWorkshop2017 extends AttendCompanyWorkshopComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(43);
    }
}

class Culver2019LifetimeActivityView extends CompleteActivityComplianceView
{
    public function __construct($startDate, $endDate, $activityId, $typeQuestionId, $pointsPerRecord)
    {
        $this->pointsPerRecord = $pointsPerRecord;
        $this->activityId = $activityId;
        $this->typeQuestionId = $typeQuestionId;

        parent::__construct($startDate, $endDate);
    }

    public function setTypeDateMap(array $map)
    {
        $this->map = $map;
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, '1920-01-01', '2020-12-31');

        $numRecords = 0;

        $end = $this->getEndDate('Y-m-d');
        $start = $this->getStartDate();

        foreach($records as $record) {
            $date = date('Y-m-d', strtotime($record->getDate()));

            $answers = $record->getQuestionAnswers();

            $answer = isset($answers[$this->typeQuestionId]) ?
                $answers[$this->typeQuestionId]->getAnswer() : null;

            if(isset($this->map[$answer])) {
                $earliest = date('Y-m-d', strtotime($this->map[$answer], $start));

                if($earliest <= $date && $end >= $date) {
                    $numRecords++;
                }
            }
        }

        return new ComplianceViewStatus($this, null, $this->pointsPerRecord * $numRecords);
    }

    private $map = array();
    private $activityId;
    private $pointsPerRecord;
    private $typeQuestionId;
}

class Culver2019ComplianceProgram extends ComplianceProgram
{
    private function getEvaluateComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        $program = $this->cloneForEvaluation($startDate, $endDate);

        $program->evaluateOverall = false;

        $program->setActiveUser($user);

        return $program->getStatus();
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if(!$this->evaluateOverall) {
            return;
        }

        $programForRaffle1EvaluationStatus = $status->getComplianceProgram()->getEvaluateComplianceViewStatuses($status->getUser(), '2018-08-01', '2018-12-15');
        $pointsGroupPointsForRaffle1 = $programForRaffle1EvaluationStatus->getComplianceViewGroupStatus('points')->getPoints();

        $programForRaffle2EvaluationStatus = $status->getComplianceProgram()->getEvaluateComplianceViewStatuses($status->getUser(), '2018-08-01', '2019-03-01');
        $pointsGroupPointsForRaffle2 = $programForRaffle2EvaluationStatus->getComplianceViewGroupStatus('points')->getPoints();

        $programForRaffle3EvaluationStatus = $status->getComplianceProgram()->getEvaluateComplianceViewStatuses($status->getUser(), '2018-08-01', '2019-05-01');
        $pointsGroupPointsForRaffle3 = $programForRaffle3EvaluationStatus->getComplianceViewGroupStatus('points')->getPoints();

        $programForRaffleFlexEvaluationStatus = $status->getComplianceProgram()->getEvaluateComplianceViewStatuses($status->getUser(), '2018-08-01', '2019-07-31');
        $pointsGroupPointsForRaffleFlex = $programForRaffleFlexEvaluationStatus->getComplianceViewGroupStatus('points')->getPoints();

        $raffle1Status = $status->getComplianceViewStatus('raffle1');
        $raffle2Status = $status->getComplianceViewStatus('raffle2');
        $raffle3Status = $status->getComplianceViewStatus('raffle3');
        $raffleFlexStatus = $status->getComplianceViewStatus('raffle_flex');

        $raffle1Status->setPoints($pointsGroupPointsForRaffle1);
        $raffle2Status->setPoints($pointsGroupPointsForRaffle2);
        $raffle3Status->setPoints($pointsGroupPointsForRaffle3);
        $raffleFlexStatus->setPoints($pointsGroupPointsForRaffleFlex);

        if ($pointsGroupPointsForRaffle1 >= 200) {
            $raffle1Status->setStatus(ComplianceViewStatus::COMPLIANT);
        }

        if ($pointsGroupPointsForRaffle2 >= 300) {
            $raffle2Status->setStatus(ComplianceViewStatus::COMPLIANT);
        }

        if ($pointsGroupPointsForRaffle3 >= 400) {
            $raffle3Status->setStatus(ComplianceViewStatus::COMPLIANT);
        }

        if ($pointsGroupPointsForRaffleFlex >= 200 || $raffle1Status->getStatus() == ComplianceViewStatus::COMPLIANT) {
            $raffleFlexStatus->setStatus(ComplianceViewStatus::COMPLIANT);
        }

    }

    public function loadGroups()
    {

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroupEnd = '2018-12-31';

        $notSpouse = function (User $user) {
            return $user->relationship_type != Relationship::SPOUSE;
        };

        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.'December 31, 2018');


        $screeningView = new CompleteScreeningComplianceView($programStart, $coreGroupEnd);
        $screeningView->setReportName('Complete Wellness Screening');
        $screeningView->setName('complete_screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $coreGroupEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete Health Power Assessment');
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $raffleGroup = new ComplianceViewGroup('raffle', 'Points Earned and Status for $200 to >$3,000 in rewards:');

        $raffle1 = new PlaceHolderComplianceView(null, 0);
        $raffle1->setName('raffle1');
        $raffle1->setReportName('Raffle 1:   ≥200 points by December 15, 2018');
        $raffleGroup->addComplianceView($raffle1);

        $raffle2 = new PlaceHolderComplianceView(null, 0);
        $raffle2->setName('raffle2');
        $raffle2->setReportName('Raffle 2:   ≥300 points by March 1, 2019');
        $raffleGroup->addComplianceView($raffle2);

        $raffle3 = new PlaceHolderComplianceView(null, 0);
        $raffle3->setName('raffle3');
        $raffle3->setReportName('Raffle 3:   ≥400 points by May 1, 2019');
        $raffleGroup->addComplianceView($raffle3);

        $raffleFlex = new PlaceHolderComplianceView(null, 0);
        $raffleFlex->setName('raffle_flex');
        $raffleFlex->setReportName('$200 Flex Benefit:   ≥200 points by July 31, 2019');
        $raffleGroup->addComplianceView($raffleFlex);

        $this->addComplianceViewGroup($raffleGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'And, earn 200 or more points from A-H below by '.date('F d, Y', $programEnd));

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->overrideTestRowData(25, 40, 999, null, "M");
        $hdlCholesterolView->overrideTestRowData(25, 50, 999, null, "F");
        $extraGroup->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->overrideTestRowData(null, null, 99.999, 159.999);
        $extraGroup->addComplianceView($ldlCholesterolView);

        $nonHdlCholesterolView = new ComplyWithNonHDLCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $nonHdlCholesterolView->overrideTestRowData(null, null, 129.999, 159.999);
        $nonHdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($nonHdlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->overrideTestRowData(50, 70, 100, 126, "M");
        $hdlCholesterolView->overrideTestRowData(40, 70, 100, 126, "F");
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

        $elearningView = new CompleteELearningGroupSet($programStart, $programEnd, 'recommended2018');
        $elearningView->setReportName('Complete Recommended e-Learning Lessons – 10 points/lesson');
        $elearningView->setName('elearning_2018');
        $elearningView->setNumberRequired(1);
        $elearningView->setPointsPerLesson(10);
        $elearningView->setMaximumNumberOfPoints(50);
        $extraGroup->addComplianceView($elearningView);

        $physicalActivityView = new CulverPhysicalActivityView($programStart, $programEnd);
        $physicalActivityView->setReportName('Get Regular Exercise - use one or both below to sync or update for exercise/activity points between 8/1/18 and 7/31/19');
        $physicalActivityView->setMaximumNumberOfPoints(260);
        $physicalActivityView->setMonthlyPointLimit(260);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setPointsMultiplier(1);
        $physicalActivityView->setFractionalDivisorForPoints(4);
        $physicalActivityView->setName('physical_activity');
        
        $extraGroup->addComplianceView($physicalActivityView);

        $attendBenefitsFairView = new PlaceHolderComplianceView(null, 0);
        $attendBenefitsFairView->setAllowPointsOverride(true);
        $attendBenefitsFairView->setName('attend_benefits_fair');
        $attendBenefitsFairView->setReportName('Attend Benefits Fair / Open Enrollment Meetings');
        $attendBenefitsFairView->setMaximumNumberOfPoints(50);
        $attendBenefitsFairView->addLink(new FakeLink('From Attendance Lists', '#'));
        $extraGroup->addComplianceView($attendBenefitsFairView);

        $doctorView = new UpdateDoctorInformationComplianceView($programStart, $programEnd);
        $doctorView->setReportName('Confirm Having a Main Doctor/Primary Care Provider');
        $doctorView->setName('doctor');
//        $doctorView->setEvaluateCallback($notSpouse);
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $doctorView->setMaximumNumberOfPoints(10);
        $doctorView->emptyLinks();
        $doctorView->addLink(new Link('Enter or Update Info', '/my_account/updateDoctor?redirect=/compliance_programs'));
        $extraGroup->addComplianceView($doctorView);

        $infoView = new UpdateContactInformationComplianceView($programStart, $programEnd);
        $infoView->setReportName('Confirm/Update Key Contact Info – email, address');
        $infoView->setName('info');
        $infoView->setMaximumNumberOfPoints(10);
        $infoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
       // $infoView->setEvaluateCallback($notSpouse);
        $infoView->emptyLinks();
        $infoView->addLink(new Link('Enter or Update Info', '/my_account/updateAll?redirect=/compliance_programs'));
        $extraGroup->addComplianceView($infoView);

        $preventiveExamsView = new Culver2019LifetimeActivityView(
            $programStart, $programEnd, 26, 42, 5
        );
        $preventiveExamsView->setReportName('Get recommended Preventive Screenings/Exams');
        $preventiveExamsView->setMaximumNumberOfPoints(20);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setTypeDateMap(array(
            'Physical Exam'                     => '-12 months',
            'Blood pressure'                    => '-12 months',
            'Cholesterol and Glucose levels '   => '-12 months',
            'Colonoscopy'                       => '-5 years',
            'Dental Exam'                       => '-12 months',
            'Vision Exam'                       => '-12 months',
            'Pap Test'                          => '-12 months',
            'Clinical Breast Exam'              => '-24 months',
            'Mammogram'                         => '-24 months',
            'Clinical Testicular Exam'          => '-12 months',
            'PSA Test'                          => '-12 months',
            'Digital Exam'                      => '-5 years',
            'Bone Density'                      => '-5 years',
            'HA1C'                              => '-12 months'
        ));
        $extraGroup->addComplianceView($preventiveExamsView);

        $fluVaccineView = new Culver2019LifetimeActivityView(
            $programStart, $programEnd, 60, 63, 5
        );
        $fluVaccineView->setMaximumNumberOfPoints(20);
        $fluVaccineView->setReportName('Get recommended Immunizations');
        $fluVaccineView->setName('flu_vaccine');
        $fluVaccineView->setTypeDateMap(array(
            'Flu shot'                                => '-6 months',
            'Pneumonia'                               => '-80 years',
            'Tetanus (Td)'                            => '-10 years',
            'Shingles'                                => '-80 years',
            'Tetanus, diptheria & Pertussis (Tdap)'   => '-80 years',
            'Hepatitis A'                             => '-80 years',
            'Hepatitis B'                             => '-80 years',
            'Measles, mumps & rubella (MMR)'          => '-80 years',
            'Polio'                                   => '-80 years'
        ));
        $extraGroup->addComplianceView($fluVaccineView);

        $workWithHealthCoachView = new PlaceHolderComplianceView(null, 0);
        $workWithHealthCoachView->setAllowPointsOverride(true);
        $workWithHealthCoachView->setName('work_with_health_coach');
        $workWithHealthCoachView->setReportName('Work with Health Coach or Doctor on Health Goals');
        $workWithHealthCoachView->setMaximumNumberOfPoints(50);
        $workWithHealthCoachView->addLink(new Link('Coach # & Info', '/content/1369_details#2eHC'));
        $workWithHealthCoachView->addLink(new Link('Get Dr. Form', '/content/1369_details#2eHC'));
        $extraGroup->addComplianceView($workWithHealthCoachView);

        $attendWorkshopView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 5);
        $attendWorkshopView->setReportName('Health/Wellbeing Programs/Events Participated In');
        $attendWorkshopView->setName('attend_workshop');
        $attendWorkshopView->setMaximumNumberOfPoints(100);
        $extraGroup->addComplianceView($attendWorkshopView);

        $additionalELearningLessonsView = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $additionalELearningLessonsView->setNumberRequired(0);
        $additionalELearningLessonsView->setPointsPerLesson(5);
        $additionalELearningLessonsView->setReportName('Complete Extra e-Learning Lessons');
        $additionalELearningLessonsView->setName('elearning');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(20);
        $additionalELearningLessonsView->emptyLinks();
        $additionalELearningLessonsView->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $exploreEAP = new PlaceHolderComplianceView(null, 0);
        $exploreEAP->setMaximumNumberOfPoints(10);
        $exploreEAP->setName('explore_epa');
        $exploreEAP->setReportName('Explore the New Avenues EAP & work life balance resources site');
        $exploreEAP->addLink(new Link('Click to Explore', '/compliance_programs/localAction?id=1369&local_action=explore_epa', false, '_blank'));
        $exploreEAP->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('explore_epa_2018');

            if($record->exists()) {
                $status->setPoints(10);
            }
        });
        $extraGroup->addComplianceView($exploreEAP);

        $exploreAnthem = new PlaceHolderComplianceView(null, 0);
        $exploreAnthem->setMaximumNumberOfPoints(10);
        $exploreAnthem->setName('explore_anthem');
        $exploreAnthem->setReportName('Explore the Anthem website for resources, providers & rewards');
        $exploreAnthem->addLink(new Link('Click to Explore', '/compliance_programs/localAction?id=1369&local_action=explore_anthem', false, '_blank'));
        $exploreAnthem->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('explore_anthem_2018');

            if($record->exists()) {
                $status->setPoints(10);
            }
        });
        $extraGroup->addComplianceView($exploreAnthem);

        $learnAnthem = new PlaceHolderComplianceView(null, 0);
        $learnAnthem->setMaximumNumberOfPoints(10);
        $learnAnthem->setName('learn_anthem');
        $learnAnthem->setReportName('Learn about Anthem\'s LiveHealth Online for video Dr. visits');
        $learnAnthem->addLink(new Link('Click to Learn More', '/compliance_programs/localAction?id=1369&local_action=learn_anthem', false, '_blank'));
        $learnAnthem->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $record = $user->getNewestDataRecord('learn_anthem_2018');

            if($record->exists()) {
                $status->setPoints(10);
            }
        });
        $extraGroup->addComplianceView($learnAnthem);


        $extraGroup->setPointsRequiredForCompliance(200);

        $this->addComplianceViewGroup($extraGroup);

    }

    public function executeExploreEpa(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $user->getNewestDataRecord('explore_epa_2018', true);

        $actions->redirect('http://www.newavenuesonline.com/work-life-balance');

    }

    public function executeExploreAnthem(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $user->getNewestDataRecord('explore_anthem_2018', true);

        $actions->redirect('https://www.anthem.com/login/');
    }

    public function executeLearnAnthem(sfActions $actions)
    {
        $user = $actions->getSessionUser();

        $user->getNewestDataRecord('learn_anthem_2018', true);

        $actions->redirect('https://livehealthonline.com/');
    }

    public function getLocalActions()
    {
        return array(
            'explore_epa' => array($this, 'executeExploreEpa'),
            'explore_anthem'  => array($this, 'executeExploreAnthem'),
            'learn_anthem'  => array($this, 'executeLearnAnthem')
        );
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Culver2019ScreeningPrinter();

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
            $printer = new Culver2019ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, true);

        return $printer;
    }

    protected $evaluateOverall = true;
}

class Culver2019ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');

        $raffleGroupStatus = $status->getComplianceViewGroupStatus('raffle');
        $raffle1Status = $raffleGroupStatus->getComplianceViewStatus('raffle1');
        $raffle2Status = $raffleGroupStatus->getComplianceViewStatus('raffle2');
        $raffle3Status = $raffleGroupStatus->getComplianceViewStatus('raffle3');
        $raffleFlexStatus = $raffleGroupStatus->getComplianceViewStatus('raffle_flex');


        $pointGroupStatus = $status->getComplianceViewGroupStatus('points');
        $totalCholesterolStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_total_cholesterol_screening_test');
        $hdlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_hdl_screening_test');
        $ldlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_ldl_screening_test');
        $nonHdlStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_non_hdl_screening_test');
        $triglyceridesStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_triglycerides_screening_test');
        $glucoseStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_glucose_screening_test');
        $bloodPressureStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_blood_pressure_screening_test');
        $bodyFatBMIStatus = $pointGroupStatus->getComplianceViewStatus('comply_with_body_fat_bmi_screening_test');
        $nonSmokingStatus = $pointGroupStatus->getComplianceViewStatus('non_smoker_view');
        $elearningStatus = $pointGroupStatus->getComplianceViewStatus('elearning_2018');

        $haveDoctorStatus = $pointGroupStatus->getComplianceViewStatus('doctor');
        $infoStatus = $pointGroupStatus->getComplianceViewStatus('info');
        $preventiveScreeningsStatus = $pointGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $fluVaccineStatus = $pointGroupStatus->getComplianceViewStatus('flu_vaccine');
        $physicalActivityStatus = $pointGroupStatus->getComplianceViewStatus('physical_activity');
        
        $workWithHealthCoachStatus = $pointGroupStatus->getComplianceViewStatus('work_with_health_coach');
        $workshopStatus = $pointGroupStatus->getComplianceViewStatus('attend_workshop');
        $extraELearningStatus = $pointGroupStatus->getComplianceViewStatus('elearning');
        $attendBenefitsFairStatus = $pointGroupStatus->getComplianceViewStatus('attend_benefits_fair');
        $exploreEpaStatus = $pointGroupStatus->getComplianceViewStatus('explore_epa');
        $exploreAnthemStatus = $pointGroupStatus->getComplianceViewStatus('explore_anthem');
        $learnAnthemStatus = $pointGroupStatus->getComplianceViewStatus('learn_anthem');



        ?>
        <script type="text/javascript">
            $(function() {
                $('#other_benefits_details').hide();

                $('#other_benefits').toggle(function() {
                    $('#other_benefits_details').show();
                }, function(){
                    $('#other_benefits_details').hide();
                });

                $('#criteria_text').hide();
                $('#criteria').toggle(function() {
                    $('#criteria_text').show();
                }, function() {
                    $('#criteria_text').hide();
                });
            });
        </script>

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
                width:130px;
                float:left;
                text-align:center;
                padding-left:2px;
            }
        </style>
        <div class="pageHeading">Rewards/To-Do Summary Page</div>
        <p>Hello <?php echo $status->getUser() ?>,</p>
        <p></p>
        <p>Welcome to your summary page for the 2018-2019 Culver Academies Wellness Rewards from $50 to over $300-$2,800.</p>

        <p>To receive the rewards, eligible employees and spouses MUST EACH take certain actions and meet the
            criteria specified below – <a href="#" id="criteria">click to see:</a></p>

        <div id="criteria_text">
            <ol>
                <li>For the premium contribution credit: </li>
                <ul>
                    <li>Eligible Employees AND Spouses must complete ALL core actions listed in table below by December
                        31, 2018.</li>
                </ul>
                <li>To be eligible for raffles/drawings with $3,000 in total rewards given away, number 1 must be done, AND:</li>
                <ul>
                    <li>For Raffle 1 - Employees or spouses must earn 200 points by December 15, 2018.</li>
                    <li>For Raffle 2 - Employees or spouses must earn 300 points by March 1, 2019</li>
                    <li>For Raffle 3 - Employees or spouses must earn 400 points by May 1, 2019</li>
                </ul>
                <li>For the $200 flexible benefit credit, number 1 must be done, AND:</li>
                <ul>
                    <li>Employees must earn at least 200 points through the options in the table below by July 31, 2019.</li>
                </ul>
            </ol>
            <p>By meeting the above requirements, eligible employees/families will receive the following rewards:</p>
            <ol style="list-style-type:upper-alpha;">
                <li>Premium contribution credit:   The applicable lower Wellness Rate for premium contributions saving
                    you over $300-$2,800 this year depending on which health plan you have through Culver.  If 1A and 1B
                    do not get done by the deadline, your premium contribution doubles, costing you that much more.
                </li>
                <li>Raffle Rewards:  $3,000+ in equipment, Amazon &/or other gift card rewards with $1,000 in
                    rewards won per raffle.</li>
                <li>Flex benefit credit:  A $200 contribution to the Flexible Benefit Account (one per eligible family)</li>
            </ol>
            <p>Important:  The incentive requirements apply to <u>each</u> eligible employee <u>and</u> spouse with medical benefits
                through Culver.  With employee/spouse coverage or employee/full family coverage, <u><strong>both</strong></u> the employee AND
                spouse must meet all applicable criteria for the family to receive incentives A & C (above).</p>
        </div>

        <span id="other_benefits"><a href="#">Other benefits, tips & details.</a></span>

        <div id="other_benefits_details">
            In addition, by taking these actions and getting more points:
            <ul>
                <li>You will benefit from improvements in health, health care and wellbeing - now and throughout life; and</li>
                <li>Your efforts will help you avoid fewer health problems and related expenses each year; and</li>
                <li>You may be helping others in many of the same ways through your actions and encouragement along the way.</li>
            </ul>
            Here are some tips about the table below and using it:
            <ul>
                <li>In the first column, click on the text in blue to learn why the action is important.</li>
                <li>Use the Action Links in the right column to get things done or more information.</li>
                <li><a href="/content/1369_details">Click here</a> for more details about the requirements and benefits of each.</li>
            </ul>
        </div>

        <p></p>
        <p></p>
        <p style="text-align:center;">

        </p>
        <p></p>

        <table class="phipTable" border="1">
            <thead id="legend">
            <tr>
                <td colspan="5">
                    <div id="legendText">Legend</div>
                    <div class="legendEntry" style="font-weight: bold;"><?php echo $status->getUser() ?></div>
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
                <th colspan="2">1. <?php echo $coreGroupStatus->getComplianceViewGroup()->getReportName() ?>:</th>
                
                <td>Date Done</td>
                <td>Status</td>
                <td>Action Links</td>
            </tr>

            <tr>
                <td colspan="2"><a href="/content/1369_details#1aHS">A. <?php echo $completeScreeningStatus->getComplianceView()
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
                <td colspan="2"><a href="/content/1369_details#1bHPA">B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></a>
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
                <td class="right" style="font-size: 7pt;" colspan="2">
                    All Core Actions Done on or before
                    <?php echo $completeHRAStatus->getComplianceView()->getEndDate('m/d/Y') ?>
                </td>
                <td></td>
                <td class="center">
                    <?php echo $coreGroupStatus->isCompliant() ? 'Yes' : 'No' ?>
                </td>
                <td class="center">
                    <?php echo $coreGroupStatus->isCompliant() ? 'All Requirements Met' : 'Not Done Yet'; ?>
                </td>
            </tr>

            <tr class="headerRow">
                <th colspan="2">2. <?php echo $raffleGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td>Points</td>
                <td>Status</td>
                <td>Links</td>
            </tr>

            <tr>
                <td colspan="2">
                    <a href="/content/1369_details#2aRaffle">A. <?php echo $raffle1Status->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="center"><?php echo $raffle1Status->getPoints(); ?></td>
                <td class="center">
                    <?php echo $raffle1Status->isCompliant() ? 'Yes' : 'No' ?>
                </td>
                <td class="links">Employees & Spouses can Win</td>
            </tr>

            <tr>
                <td colspan="2">
                    <a href="/content/1369_details#2aRaffle">B. <?php echo $raffle2Status->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="center"><?php echo $raffle2Status->getPoints(); ?></td>
                <td class="center">
                    <?php echo $raffle2Status->isCompliant() ? 'Yes' : 'No' ?>
                </td>
                <td class="links">Employees & Spouses can Win</td>
            </tr>

            <tr>
                <td colspan="2">
                    <a href="/content/1369_details#2aRaffle">C. <?php echo $raffle3Status->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="center"><?php echo $raffle3Status->getPoints(); ?></td>
                <td class="center">
                    <?php echo $raffle3Status->isCompliant() ? 'Yes' : 'No' ?>
                </td>
                <td class="links">Employees & Spouses can Win</td>
            </tr>

            <tr>
                <td colspan="2">
                    <a href="/content/1369_details#2aRaffle">D. <?php echo $raffleFlexStatus->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="center"><?php echo $raffleFlexStatus->getPoints(); ?></td>
                <td class="center">
                    <?php echo $raffleFlexStatus->isCompliant() ? 'Yes' : 'No' ?>
                </td>
                <td class="links">Each Eligible Employee</td>
            </tr>

            <tr class="headerRow">
                <th>3. <?php echo $pointGroupStatus->getComplianceViewGroup()->getReportName() ?></th>
                <td>My Steps</td>
                <td># Points Earned</td>
                <td># Points Possible</td>
                <td>Links</td>
            </tr>

            <tr>
                <td colspan="2"><a href="/content/1369_details#3aKBHM">A. Have these screening results in the ideal zone:</a></td>
                <td colspan="3">

                </td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $totalCholesterolStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td></td>
                <td class="center"><?php echo $totalCholesterolStatus->getPoints(); ?></td>
                <td class="center"><?php echo $totalCholesterolStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td rowspan="9" class="links">
                    <a href="?preferredPrinter=ScreeningProgramReportPrinter">Click here for the 9 results</a><br/><br/>
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
                <td></td>
                <td class="center"><?php echo $hdlStatus->getPoints(); ?></td>
                <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $ldlStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td></td>
                <td class="center"><?php echo $ldlStatus->getPoints(); ?></td>
                <td class="center"><?php echo $ldlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $nonHdlStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td></td>
                <td class="center"><?php echo $nonHdlStatus->getPoints(); ?></td>
                <td class="center"><?php echo $nonHdlStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td></td>
                <td class="center"><?php echo $triglyceridesStatus->getPoints(); ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>
            <tr>
                <td>
                    <ul>
                        <li><?php echo $glucoseStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td></td>
                <td class="center"><?php echo $glucoseStatus->getPoints(); ?></td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td></td>
                <td class="center"><?php echo $bloodPressureStatus->getPoints(); ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>


            <tr>
                <td>
                    <ul>
                        <li><?php echo $bodyFatBMIStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td></td>
                <td class="center"><?php echo $bodyFatBMIStatus->getPoints(); ?></td>
                <td class="center"><?php echo $bodyFatBMIStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>
                    <ul>
                        <li><?php echo $nonSmokingStatus->getComplianceView()->getReportName() ?></li>
                    </ul>
                </td>
                <td></td>
                <td class="center"><?php echo $nonSmokingStatus->getPoints(); ?></td>
                <td class="center"><?php echo $nonSmokingStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
            </tr>

            <tr>
                <td>B. <?php echo $elearningStatus->getComplianceView()
                            ->getReportName() ?></td>
                            <td></td>
                <td class="center"><?php echo $elearningStatus->getPoints(); ?></td>
                <td class="center"><?php echo $elearningStatus->getComplianceView()
                        ->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <?php
                    foreach($elearningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>C. <?php echo $physicalActivityStatus->getComplianceView()->getReportName(); ?></td>
                <td></td>
                <td class="center"></td>
                <td class="center"></td>
                <td class="links"></td>
            </tr>
            <tr>
                <td><ul style="list-style-type: none;"><li>1) Fitbit Syncing or Manual Steps Entry</li></ul></td>
                <td class="center"><?php echo $physicalActivityStatus->getComplianceView()->getAttribute('steps_data')['fitnessTracker']['steps']; ?></td>
                <td class="center"></td>
                <td class="center"></td>
                <td class="links"><a href="/content/wms3fitbit?id=1370&clientId=culver">Fitness Tracker</a></td>
            </tr>
            <tr>
                <td><ul style="list-style-type: none;"><li>2) Convert Activity Minutes to Steps</li></ul></td>
                <td class="center"><?php echo $physicalActivityStatus->getComplianceView()->getAttribute('steps_data')['activities']['steps']; ?></td>
                <td class="center"></td>
                <td class="center"></td>
                <td class="links"><a href="/content/12048?action=showActivity&activityidentifier=21">Enter/Update Info</a></td>
            </tr>

            <tr>
                <td><ul style="list-style-type: none;"><li>3) Total Steps & Points based on 1 pt per 2,000 steps (260 point max)</li></ul></td>
                <td class="center"><?php echo $physicalActivityStatus->getComplianceView()->getAttribute('steps_data')['activities']['steps'] + $physicalActivityStatus->getComplianceView()->getAttribute('steps_data')['fitnessTracker']['steps']; ?></td>
                <td class="center"><?php echo $physicalActivityStatus->getPoints() ?></td>
                <td class="center"><?php echo $physicalActivityStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links"></td>
            </tr>

            <tr>
                <td>D. <?php echo $attendBenefitsFairStatus->getComplianceView()
                            ->getReportName() ?></td>
                            <td></td>
                <td class="center"><?php echo $attendBenefitsFairStatus->getPoints(); ?></td>
                <td class="center"><?php echo $attendBenefitsFairStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    From Attendance Lists
                </td>
            </tr>

            <tr>
                <td>E. <?php echo $haveDoctorStatus->getComplianceView()
                            ->getReportName() ?></td>
                            <td></td>
                <td class="center"><?php echo $haveDoctorStatus->getPoints(); ?></td>
                <td class="center"><?php echo $haveDoctorStatus->getComplianceView()
                        ->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <?php
                    foreach($haveDoctorStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>F. <?php echo $infoStatus->getComplianceView()
                            ->getReportName() ?></td>
                            <td></td>
                <td class="center"><?php echo $infoStatus->getPoints(); ?></td>
                <td class="center"><?php echo $infoStatus->getComplianceView()
                        ->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <?php
                    foreach($infoStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>G. <?php echo $preventiveScreeningsStatus->getComplianceView()
                            ->getReportName() ?></td>
                            <td></td>
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
                <td>H. <?php echo $fluVaccineStatus->getComplianceView()->getReportName() ?></td>
                <td></td>
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
                <td>I. <?php echo $workWithHealthCoachStatus->getComplianceView()
                            ->getReportName() ?></td>
                            <td></td>
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
                <td>J. <?php echo $workshopStatus->getComplianceView()->getReportName() ?></td>
                <td></td>
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
                <td>K. <?php echo $extraELearningStatus->getComplianceView()->getReportName() ?></td>
                <td></td>
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
                <td>L. <?php echo $exploreEpaStatus->getComplianceView()->getReportName() ?></td>
                <td></td>
                <td class="center"><?php echo $exploreEpaStatus->getPoints(); ?></td>
                <td class="center"><?php echo $exploreEpaStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <?php
                    foreach($exploreEpaStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML();
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>M. <?php echo $exploreAnthemStatus->getComplianceView()->getReportName() ?></td>
                <td></td>
                <td class="center"><?php echo $exploreAnthemStatus->getPoints(); ?></td>
                <td class="center"><?php echo $exploreAnthemStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <?php
                    foreach($exploreAnthemStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML();
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>N. <?php echo $learnAnthemStatus->getComplianceView()->getReportName() ?></td>
                <td></td>
                <td class="center"><?php echo $learnAnthemStatus->getPoints(); ?></td>
                <td class="center"><?php echo $learnAnthemStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
                <td class="links">
                    <?php
                    foreach($learnAnthemStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML();
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td class="right">Points earned as of <?php echo date('m/d/Y'); ?> =</td>
                <td></td>
                <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
                <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
                <td style="text-align:center"><?php echo $pointGroupStatus->getComplianceViewGroup()
                        ->getMaximumNumberOfPoints(); ?> points possible!
                </td>
            </tr>
            </tbody>
        </table>

        <?php
    }
}
