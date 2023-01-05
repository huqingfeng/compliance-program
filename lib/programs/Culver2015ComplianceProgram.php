<?php
class Culver2015ScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        ?>
    <br/>
    <br/>
    <br/>
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
    <tr height="36px">
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
            &lt;200
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            200-240
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
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
        <td bgcolor="#ccffcc" align="center" width="72">
            ≥40
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            25-39
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
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
        <td bgcolor="#ccffcc" align="center" width="72">
            ≤129
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            130-158
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
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
        <td bgcolor="#ccffcc" align="center" width="72" valign="bottom">
            &lt;120/<br>
            &lt;80
        </td>
        <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
            120-139/<br>
            80-89
        </td>
        <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
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
        <td bgcolor="#ccffcc" align="center" width="72">
            &lt;100
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            100-124
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
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
        <td bgcolor="#ccffcc" align="center" width="72">
            &lt;150
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            150-199
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            ≥200
        </td>
    </tr>
    <tr>
        <td valign="bottom">
            <ol start="7">
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
                18.5&lt;25<br>
                <br>
                <br>
                6&lt;18%<br>
                14&lt;25%</p>
        </td>
        <td bgcolor="#ffff00" align="center" width="73" valign="bottom">
            <p>
                25&lt;30<br>
                <br>
                <br>
                18&lt;25<br>
                25&lt;32</p>
        </td>
        <td bgcolor="#ff909a" align="center" width="112" valign="bottom">
            <p>
                ≥30; &lt;18.5<br>
                <br>
                <br>
                ≥25; &lt;6%<br>
                ≥32; &lt;14%</p>
        </td>
    </tr>
    <tr>
        <td>
            <ol start="8">
                <li>
                    <strong>Tobacco/Cotinine</strong></li>
            </ol>
        </td>
        <td bgcolor="#ccffcc" align="center" width="72">
            &lt;2
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
            2-9
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
            ≥10
        </td>
    </tr>
    </tbody>
    </table>
    <?php
    }
}

class Culver2015CoachingView extends GraduateFromCoachingSessionComplianceView
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

class CulverWorkshop2015 extends AttendCompanyWorkshopComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(43);
    }
}

class Culver2015LifetimeActivityView extends CompleteActivityComplianceView
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

class Culver2015ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroupEnd = '2014-12-31';

        $notSpouse = function (User $user) {
            return $user->relationship_type != Relationship::SPOUSE;
        };

        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.'December 31, 2014');

        $screeningView = new CompleteScreeningComplianceView($programStart, $coreGroupEnd);
        $screeningView->setReportName('Complete Wellness Screening');
        $screeningView->setName('complete_screening');
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $coreGroupEnd);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete Health Power Assessment');
        $coreGroup->addComplianceView($hraView);

        $doctorView = new UpdateDoctorInformationComplianceView('2011-01-01', $coreGroupEnd);
        $doctorView->setReportName('Confirm Having a Main Doctor/Primary Care Provider');
        $doctorView->setName('doctor');
        $doctorView->setEvaluateCallback($notSpouse);
        $doctorView->emptyLinks();
        $doctorView->addLink(new Link('Confirm/Update Info', '/my_account/updateDoctor?redirect=/compliance_programs'));
        $coreGroup->addComplianceView($doctorView);

        $infoView = new UpdateContactInformationComplianceView('2011-01-01', $coreGroupEnd);
        $infoView->setReportName('Confirm/Update Key Contact Info (email, phone, address)');
        $infoView->setName('info');
        $infoView->setEvaluateCallback($notSpouse);
        $infoView->emptyLinks();
        $infoView->addLink(new Link('Confirm/Update Info', '/my_account/updateAll?redirect=/compliance_programs'));
        $coreGroup->addComplianceView($infoView);

        $ongoingHealthCoachingView = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $ongoingHealthCoachingView->setReportName('Return Health Coach calls -- If applicable');
        $ongoingHealthCoachingView->setName('ongoing_phone_coaching');
        $ongoingHealthCoachingView->addLink(new Link('Coach # & Info', '/content/1094#2eHC'));
        $ongoingHealthCoachingView->setEvaluateCallback($notSpouse);
        $coreGroup->addComplianceView($ongoingHealthCoachingView);

        $requiredLessons = new CompleteRequiredELearningLessonsComplianceView($programStart, $coreGroupEnd);
        $requiredLessons->setReportName('Complete any 4 new required e-learning lessons.');
        $requiredLessons->setName('required_elearning');
        $requiredLessons->setNumberRequired(4);
        $requiredLessons->setEvaluateCallback($notSpouse);
        $requiredLessons->emptyLinks();
        $requiredLessons->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=required'));
        //$requiredLessons->addLink(new Link('Coming Soon', '#'));
        $coreGroup->addComplianceView($requiredLessons);

        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'And, earn 200 or more points from A-H below by '.date('F d, Y', $programEnd));

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($totalCholesterolView);
//
        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $hdlCholesterolView->overrideTestRowData(25, 40, 998, 999);
        $extraGroup->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $ldlCholesterolView->overrideTestRowData(null, null, 129.999, 158.999);
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

        $preventiveExamsView = new Culver2015LifetimeActivityView(
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

        $fluVaccineView = new Culver2015LifetimeActivityView(
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

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Get Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(220);
        $physicalActivityView->setMonthlyPointLimit(220);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setPointsMultiplier(1);
        $physicalActivityView->setFractionalDivisorForPoints(4);
        $physicalActivityView->setName('physical_activity');
        $extraGroup->addComplianceView($physicalActivityView);

        $workWithHealthCoachView = new PlaceHolderComplianceView(null, 0);
        $workWithHealthCoachView->setAllowPointsOverride(true);
        $workWithHealthCoachView->setName('work_with_health_coach');
        $workWithHealthCoachView->setReportName('Work with Health Coach or Doctor on Health Goals');
        $workWithHealthCoachView->setMaximumNumberOfPoints(150);
        $workWithHealthCoachView->addLink(new Link('Coach # & Info', '/content/1094#2eHC'));
        $workWithHealthCoachView->addLink(new Link('Get Dr. Form', '/content/1094#2eHC'));
        $extraGroup->addComplianceView($workWithHealthCoachView);

        $attendWorkshopView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 5);
        $attendWorkshopView->setReportName('Health/Wellbeing Programs/Events Participated In');
        $attendWorkshopView->setName('attend_workshop');
        $attendWorkshopView->setMaximumNumberOfPoints(30);
        $extraGroup->addComplianceView($attendWorkshopView);

        $financialELearningLessonsView = new CompleteELearningGroupSet($programStart, $programEnd, 'fwc_financial');
        $financialELearningLessonsView->setNumberRequired(0);
        $financialELearningLessonsView->setPointsPerLesson(5);
        $financialELearningLessonsView->setReportName('Complete mini-lessons on financial wellness');
        $financialELearningLessonsView->setName('elearning_financial');
        $financialELearningLessonsView->setMaximumNumberOfPoints(20);
        $financialELearningLessonsView->emptyLinks();
        $financialELearningLessonsView->addLink(new Link('View/Do Lessons', '/sitemaps/financial_wellness3/24492'));
        $extraGroup->addComplianceView($financialELearningLessonsView);

        $additionalELearningLessonsView = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd);
        $additionalELearningLessonsView->setNumberRequired(0);
        $additionalELearningLessonsView->setMaximumNumberOfIneligibleLessonIDs(4);
        $additionalELearningLessonsView->setPointsPerLesson(5);
        $additionalELearningLessonsView->setReportName('Complete Extra e-Learning Lessons');
        $additionalELearningLessonsView->setName('elearning');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(20);
        $additionalELearningLessonsView->emptyLinks();
        $additionalELearningLessonsView->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $extraGroup->setPointsRequiredForCompliance(200);
        $this->addComplianceViewGroup($extraGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new Culver2015ScreeningPrinter();

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
            $printer = new Culver2015ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, true);

        return $printer;
    }
}

class Culver2015ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        $completeHRAStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $doctorStatus = $coreGroupStatus->getComplianceViewStatus('doctor');
        $infoStatus = $coreGroupStatus->getComplianceViewStatus('info');
        $coachStatus = $coreGroupStatus->getComplianceViewStatus('ongoing_phone_coaching');
        $elearningStatus = $coreGroupStatus->getComplianceViewStatus('required_elearning');

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
        $extraELearningFinancialStatus = $pointGroupStatus->getComplianceViewStatus('elearning_financial');
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
    <p>Welcome to your summary page for the 2014-2015 Culver Academies Wellness Rewards.</p>

    <p>To receive the incentives, eligible employees and spouses MUST take certain actions and meet the criteria
        specified below:</p>
    <ol>
        <li>Eligible employees must:</li>
        <ol style="list-style-type:lower-alpha;">
            <li>Complete ALL core actions listed in table below by December 31, 2014: AND</li>
            <li>Earn at least 200 points through the options in the table below by July 31, 2015.
        </ol>
        <li>Spouses must complete the Health Power Assessment (1A) and Wellness Screening (1B) by December 31, 2014</li>
    </ol>
    <p>By meeting the above requirements, eligible employees will receive the following rewards:</p>
    <ol style="list-style-type:upper-alpha;">
        <li>Premium contribution credit: The applicable lower Wellness Rate for premium contributions
            saving you over $300-$2800 this year depending on the benefits you elected.
            If either, an employee or spouse do not participate in the screening, your premium contribution doubles,
            costing you that much more.
        </li>
        <li>Flex benefit credit: A $200 contribution to their Flexible Benefit Account.</li>
    </ol>
    <p>Important: The incentive requirements apply to eligible employees with medical benefits through Culver.
        All criteria above must be met for the employee (family) to receive incentives A and B (above).</p>
    <p>More importantly, by taking these actions and getting more points:</p>
    <ul>
        <li>You will benefit from improvements in health, health care and wellbeing – now and throughout life; and</li>
        <li>Your efforts will help you avoid fewer health problems and related expenses each year; and</li>
        <li>You may be helping others in many of the same ways through your actions and encouragement along the way.
        </li>

    </ul>
    <p>Tips And Details:</p>
    <ul>
        <li>In the first column, click on the text in blue to learn why the action is important.</li>
        <li>Use the Action Links in the right column to get things done or more information.</li>
        <li><a href="content/1094">Click here</a> for more details about the requirements and benefits of each.</li>
        <li>If the points or status did not change for an item you are working on, you may need to go back and enter missing information
    or entries to earn more points. The status for wellness screening will not change until after your report is
    mailed.</li>
    </ul>
    <p></p>
    <p></p>
    <p style="text-align:center;">

    </p>
    <p></p>

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
        <td>Action Links</td>
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
        <td><a href="/content/1094#1bHPA">B. <?php echo $completeHRAStatus->getComplianceView()->getReportName() ?></a>
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
        <?php if($status->getUser()->relationship_type != Relationship::SPOUSE) : ?>
    <tr>
        <td><a href="/content/1094#cMD">C. <?php echo $doctorStatus->getComplianceView()->getReportName() ?></a></td>
        <td class="center">
            <?php echo $doctorStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $doctorStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($doctorStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#1dPCI">D. <?php echo $infoStatus->getComplianceView()->getReportName() ?></a></td>
        <td class="center">
            <?php echo $infoStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $infoStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($infoStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#1eCalls">E. <?php echo $coachStatus->getComplianceView()->getReportName() ?></a></td>
        <td class="center">
            <?php echo $coachStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $coachStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($coachStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#1freqeL">F. <?php echo $elearningStatus->getComplianceView()->getReportName() ?></a>
        </td>
        <td class="center">
            <?php echo $elearningStatus->getComment(); ?>
        </td>
        <td class="center">
            <img src="<?php echo $elearningStatus->getLight(); ?>" class="light"/>
        </td>
        <td class="links">
            <?php
            foreach($elearningStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML()."\n";
            }
            ?>
        </td>
    </tr>
        <?php endif ?>
    <tr>
        <td class="right" style="font-size: 7pt;">
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
        <td><a href="/content/1094#2bPS">B. <?php echo $preventiveScreeningsStatus->getComplianceView()
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
        <td><a href="/content/1094#2clmm">C. <?php echo $fluVaccineStatus->getComplianceView()->getReportName() ?></a>
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
        <td><a href="/content/1094#2dRPA">D. <?php echo $physicalActivityStatus->getComplianceView()
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
        <td><a href="/content/1094#2eHC">E. <?php echo $workWithHealthCoachStatus->getComplianceView()
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
        <td><a href="/content/1094#2fProg">F. <?php echo $workshopStatus->getComplianceView()->getReportName() ?></a>
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
        <td><a href="/content/1094#2geLearn">G. <?php echo $extraELearningFinancialStatus->getComplianceView()
            ->getReportName() ?></a></td>
        <td class="center"><?php echo $extraELearningFinancialStatus->getPoints(); ?></td>
        <td class="center"><?php echo $extraELearningFinancialStatus->getComplianceView()->getMaximumNumberOfPoints(); ?></td>
        <td class="links">
            <?php
            foreach($extraELearningFinancialStatus->getComplianceView()->getLinks() as $link) {
                echo $link->getHTML();
            }
            ?>
        </td>
    </tr>
    <tr>
        <td><a href="/content/1094#2geLearn">H. <?php echo $extraELearningStatus->getComplianceView()
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
        <td class="right">Total number as of <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $pointGroupStatus->getPoints(); ?></td>
        <td class="center"><?php echo $pointGroupStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?></td>
        <td style="text-align:center"><?php echo $pointGroupStatus->getComplianceViewGroup()
            ->getMaximumNumberOfPoints(); ?> points possible!
        </td>
    </tr>
    <tr class="headerRow">
        <th>3. Deadlines, Requirements & Status</th>
        <td># Points</td>
        <td>Status</td>
        <td>Needed By <?php echo $status->getComplianceProgram()->getEndDate('m/d/Y') ?></td>
    </tr>
    <tr>
        <td style="text-align: right;">Status of core actions + ≥ 200 points as of: <?php echo date('m/d/Y'); ?> =</td>
        <td class="center"><?php echo $status->getPoints(); ?></td>
        <td class="center"><img src="<?php echo $status->getLight(); ?>" class="light"/></td>
        <td class="center"></td>
    </tr>
    </tbody>
    </table>

    <?php
    }
}
