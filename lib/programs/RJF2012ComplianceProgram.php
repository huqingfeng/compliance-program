<?php

class RJF2012ExceedLearningComplianceView extends CompleteActivityComplianceView
{
    /**
     * @return ActivityTrackerActivity
     */
    public function getActivity()
    {
        return new ActivityTrackerActivity(45);
    }

    public function __construct($startDate, $endDate)
    {
        parent::__construct($startDate, $endDate);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $hours = 0;

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[62])) {
                $hours += $answers[62]->getAnswer();
            }
        }

        $points = floor($hours);

        return new ComplianceViewStatus($this, null, $points);
    }
}

class RJF2012TrackGoal extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'track_goal';
    }

    public function getDefaultReportName()
    {
        return 'Goal';
    }

    public function getStatus(User $user)
    {
        $actOne = new CompleteArbitraryActivityComplianceView(
            '2001-01-01', '2012-12-31', 238, 10
        );

        $actTwo = new CompleteArbitraryActivityComplianceView(
            '2001-01-01', '2012-12-31', 239, 40
        );

        $actOneLink = current($actOne->getLinks());
        $actTwoLink = current($actTwo->getLinks());

        $actOneLink->setLinkText('Establish your goal');
        $actTwoLink->setLinkText('Reflect on your progress');

        $points = $actOne->getStatus($user)->getPoints() +
            $actTwo->getStatus($user)->getPoints();

        $this->addLink($actOneLink);

        if($points > 0) {
            $this->addLink(new Link('Track your progress', '/content/12048?action=showActivity&activityidentifier=240'));
            $this->addLink($actTwoLink);
        }

        return new ComplianceViewStatus($this, null, $points);
    }
}

class RJFWorkWithDoctor2012ComplianceView extends PlaceHolderComplianceView
{
}

class RJF2012ComplianceProgram extends ComplianceProgram
{
    public function isNewHire(User $user)
    {
        if($user->hiredate) {
            $hireDate = strtotime($user->hiredate);

            return $hireDate >= strtotime('2012-06-01') &&
                $hireDate <= strtotime('2012-12-31');
        } else {
            return false;
        }
    }

    public function isNotNewHire(User $user)
    {
        return !$this->isNewHire($user);
    }

    public function getNumberOfPointsRequired(User $user)
    {
        return $this->isNewHire($user) ? 50 : 100;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'All Core Actions Required by '.date('F d, Y', $programEnd));

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('complete_hra');
        $hraView->emptyLinks()->addLink(new Link('Take HA', '/content/989'));
        $hraView->setReportName('Online Health Assessment (HA)');
        $hraView->setAttribute('report_name_link', '/content/1094#1aHA');
        $hraView->setEvaluateCallback(array($this, 'isNotNewHire'));
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('On-site Health Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('report_name_link', '/content/1094#1bHS');
        $screeningView->setEvaluateCallback(array($this, 'isNotNewHire'));
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

        // Build the extra group
        $extraGroup = new ComplianceViewGroup('points', 'And, earn %s or more points from A-M below.');

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);

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
        $doctorInformationView->setAttribute('report_name_link', '/content/1094#2bMD');
        $extraGroup->addComplianceView($doctorInformationView);

        $preventiveExamsView = new ObtainPreventiveExamComplianceView($programStart, $programEnd, 5);
        $preventiveExamsView->setReportName('Recommended Preventive Screenings/Exams');
        $preventiveExamsView->setMaximumNumberOfPoints(20);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#2cRPS');
        $extraGroup->addComplianceView($preventiveExamsView);

        $fluVaccineView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $fluVaccineView->setAttribute('report_name_link', '/content/1094#2dAFV');
        $extraGroup->addComplianceView($fluVaccineView);

        $attendView = new AttendCompanyWorkshopComplianceView($programStart, $programEnd, 5);
        $attendView->setReportName('Attend Other Key Well-being Events / Trainings');
        $attendView->setAttribute('report_name_link', '/content/1094#2eKeyWB');
        $attendView->setMaximumNumberOfPoints(30);
        $extraGroup->addComplianceView($attendView);

        $mainStreetView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 236, 5);
        $mainStreetView->setMaximumNumberOfPoints(5);
        $mainStreetView->setReportName('Use MainstreetMedica.com or Health Advocate');
        $mainStreetView->setAttribute('report_name_link', '/content/1094#2fMainstreetMed');
        $extraGroup->addComplianceView($mainStreetView);

        $physicalActivityView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $physicalActivityView->setReportName('Regular Physical Activity');
        $physicalActivityView->setMaximumNumberOfPoints(45);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(45);
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#2gRPA');
        $extraGroup->addComplianceView($physicalActivityView);

        $goalView = new RJF2012TrackGoal($programStart, $programEnd);
        $goalView->setReportName('Establish a well-being goal');
        $goalView->setAttribute('report_name_link', '/content/1094#2hWBgoal');
        $goalView->setMaximumNumberOfPoints(50);
        $extraGroup->addComplianceView($goalView);

        $helloWalletView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 237, 15);
        $helloWalletView->setMaximumNumberOfPoints(15);
        $helloWalletView->setReportName('Register for the free HelloWallet resource on MMC PeopleLink');
        $helloWalletView->setAttribute('report_name_link', '/content/1094#2iHelloWallet');
        $extraGroup->addComplianceView($helloWalletView);

        $additionalELearningLessonsView = new CompleteAdditionalELearningLessonsComplianceView($programStart, $programEnd, 1);
        $additionalELearningLessonsView->setReportName('Complete e-Learning Lessons');
        $additionalELearningLessonsView->setName('elearning');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(20);
        $additionalELearningLessonsView->setAttribute('report_name_link', '/content/1094#2jEL');
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $learningOrgRequirementsView = new RJF2012ExceedLearningComplianceView($programStart, $programEnd, 45, 5);
        $learningOrgRequirementsView->setReportName('Exceed Learning Org Requirements');
        $learningOrgRequirementsView->setName('learning_requirements');
        $learningOrgRequirementsView->setMaximumNumberOfPoints(5);
        $learningOrgRequirementsView->setAttribute('report_name_link', '/content/1094#2kExceed');
        $extraGroup->addComplianceView($learningOrgRequirementsView);

        $stretchingBreak = new CompleteConfigurableActivityComplianceView($programStart, $programEnd, 46, 5);
        $stretchingBreak->setReportName('Lead a Stretching or "Brain Break" for your work group');
        $stretchingBreak->setName('brain_break');
        $stretchingBreak->setMaximumNumberOfPoints(5);
        $stretchingBreak->setAttribute('report_name_link', '/content/1094#2lBrainBrk');
        $extraGroup->addComplianceView($stretchingBreak);

        $volunteeringView = new VolunteeringComplianceView($programStart, $programEnd);
        $volunteeringView->setName('volunteering');
        $volunteeringView->setReportName('Volunteer Time');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(35);
        $volunteeringView->setAttribute('report_name_link', '/content/1094#2mVT');
        $extraGroup->addComplianceView($volunteeringView);

        $extraGroup->setPointsRequiredForCompliance(array($this, 'getNumberOfPointsRequired'));
        $this->addComplianceViewGroup($extraGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new RJF2012ScreeningPrinter();
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
            $printer = new RJF2012ComplianceProgramReportPrinter();
        }

        return $printer;
    }

}

class RJF2012ScreeningPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        ?>
    <table border="0" width="100%" style="font-size: 10px;" id="ratingsTable">
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
        </td>
        <td bgcolor="#ffff00" align="center" width="73">
        </td>
        <td bgcolor="#ff909a" align="center" width="112">
        </td>
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
                    <strong>Body Mass Index <br>
                    </strong>• men &amp; women<br>
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

class RJF2012ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setScreeningResultsLink(new Link('Have These Screening Results in the Ideal Zone:', '/content/1094#2aKBHM'));

        $pointsRequired = $status->getComplianceProgram()->getNumberOfPointsRequired($status->getUser());

        // Plugin dynamic points required into the view group name

        $pointsGroup = $status->getComplianceViewGroupStatus('points')
            ->getComplianceViewGroup();

        $pointsGroup->setReportName(sprintf($pointsGroup->getReportName(), $pointsRequired));
        ?>
    <style type="text/css">
        .phipTable .headerRow {
            background-color:#002AAE;
            font-weight:normal;
            color:#FFFFFF;
            font-size:10pt;
        }

        #legendText {
            text-align:center;
            background-color:#002AAE;
            font-weight:normal;
            color:#FFFFFF;
            font-size:12pt;
            margin-bottom:5px;
        }

        .phipTable {
            width:100%;
            border-collapse:collapse;
            font-size:8pt;
        }
    </style>

    <div class="pageHeading">2012 Wellbeing Rewards/To-Dos Summary & Action Center</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>
        Welcome to your summary page for the 2012 Wellbeing Rewards program. Employees meeting all criteria will each
        receive $39.58/month in reduced health insurance premiums ($475/year). To receive the incentives, eligible
        employees MUST take action and meet all criteria below by November 30, 2012:
    </p>

    <ol>
        <li>
            Complete <strong>ALL</strong> of the core required actions; AND
        </li>
        <li>
            Earn <?php echo $pointsRequired ?>
            or more points from key screening results and key actions taken for good health.
        </li>
    </ol>

    <p>
        Recognizing that being well is about more than being physically fit, the 2012 Wellbeing Rewards program
        encourages employees to look at five different aspects of Wellbeing: Community, Social, Financial,
        Physical/Emotional, and Career. Below we note the area of wellbeing that each criteria addresses. RJF will
        continue to focus on "total wellbeing" awareness in 2012.
    </p>

    <div class="pageHeading">
        <a href="/content/1094">
            Click here for the 2012 Wellbeing Rewards Criteria Details
        </a>.
    </div>

    <p>
        <strong>Update Notice:</strong>
        To get actions done and earn extra points, click on the links below. If the status is not green for any option
        you did or are working on, you may need to go back and enter missing information or additional entries to earn
        more points. The status for wellness screening will not change until after your report is mailed. Thanks for
        your actions and patience!</i>
    </p>
    <?php
    }
}
