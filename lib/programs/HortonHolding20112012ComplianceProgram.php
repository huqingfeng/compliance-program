<?php

class HortonHolding20112012ScreeningTestPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        ?>
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

class HortonHolding20112012Printer extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setScreeningResultsLink(new Link('Have These Screening Results in the Ideal Zone:', '/content/1094#2PhysKBHM'));
        $endDateText = $status->getComplianceProgram()->getEndDate('F j, Y');

        $pointsGroup = $status->getComplianceViewGroupStatus('points')->getComplianceViewGroup();

        $pointsRequired = $pointsGroup->getPointsRequiredForCompliance();

        // Hack in the per-user points by replacing the default..
        $pointsGroup->setReportName(str_replace(
            HortonHolding20112012ComplianceProgram::POINTS_REQUIRED,
            $pointsRequired,
            $pointsGroup->getReportName()
        ));

        $this->tableHeaders['total_status'] = "Status of 1ABC + ≥ $pointsRequired points as of:";
        $this->tableHeaders['total_link'] = $endDateText

        ?>
    <style type="text/css">
        .phipTable {
            width:100%;
            border-collapse:collapse;
            font-size:8pt;
        }

        .phipTable .headerRow {
            background-color:#385D81;
            font-weight:normal;
            color:#FFFFFF;
            font-size:10pt;
        }

        #legendText {
            text-align:center;
            background-color:#385D81;
            font-weight:normal;
            color:#FFFFFF;
            font-size:12pt;
            margin-bottom:5px;
        }

        .phipTable .all-5-areas-of-well-being {
            background-color:#000;
            color:#FFF;
        }

        .phipTable .community-well-being {
            background-color:#5500B0;
        }

        .phipTable .career-well-being {
            background-color:#0043B0;
        }

        .phipTable .financial-well-being {
            background-color:#26B000;
        }

        .phipTable .social-well-being {
            background-color:#00A4B0;
        }

        .phipTable .physical-emotional-well-being {
            background-color:#B00000;
        }
    </style>

    <div class="pageHeading">2011-2012 Well-Being Rewards Program</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the 2011-2012 Well-Being Rewards Program. To receive the Well-Being Reward,
        eligible employees MUST take action and meet the following requirements by <?php echo $endDateText ?>:</p>
    <ol>
        <li>Complete ALL of the core required actions; AND</li>
        <li>
            Earn <?php echo $pointsRequired ?> or more points from key screening
            results and key actions taken for good health.
        </li>
    </ol>
    <p>
        Your Reward varies based on your medical benefit coverage:
    </p>
    <ul>
        <li>Employees on Horton’s medical plan who complete the required core actions and earn 125 or more points from
            key screening results and other available actions by <?php echo $endDateText ?> will receive a check for 10%
            of the cost of their annual medical premium.
        </li>
        <li>Employees NOT on the medical plan will receive a check for 10% of the premium for employee-only coverage in
            the HRA plan.
        </li>
    </ul>
    <p style="text-align:center"><a href="/content/1094">Click here to learn more about the 2011-2012 rewards program,
        the related actions and other details.</a></p>

    <p>Updates & Timing: To get actions done and earn points, click on the links below. If the points or status did not
        change for an item you are working on, you may need to go back and enter missing information or entries to earn
        more points. The status for wellness screening will not change until after your report is mailed. Thanks for
        your actions and patience!</p>

    <?php
    }
}

class HortonHolding20112012ComplianceProgram extends ComplianceProgram
{
    const POINTS_REQUIRED = 125;
    const HIRE_ALL_NOT_REQUIRED = '2012-05-01';
    const HIRE_SCREENING_NOT_REQUIRED = '2011-10-10';

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        // Per Ben, if they have NA for everything, give them a white final light

        if($status->getPoints() == 0 && $status->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new HortonHolding20112012ScreeningTestPrinter();
            $printer->bindGroup('points');

            $screening = ScreeningTable::getInstance()->findCompletedForUserBetweenDates(
                $this->getActiveUser(),
                new DateTime('@'.$this->getStartDate()),
                new DateTime('@'.$this->getEndDate()),
                array('execute' => false)
            )->limit(1)->fetchOne();

            if($screening) {
                $printer->setPageTitle(sprintf('Points Earned From My Wellness Screening On %s',
                    $screening->getDateTimeObject('date')->format('m/d/Y')
                ));
            } else {
                $printer->setPageTitle('You have not had a Wellness Screening.');
            }
        } else {
            $printer = new HortonHolding20112012Printer();
        }

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $wellBeingSection = 'All 5 Areas of Well-Being';
        $communitySection = 'Community Well-Being';
        $careerSection = 'Career Well-Being';
        $financialSection = 'Financial Well-Being';
        $socialSection = 'Social Well-Being';
        $physicalSection = 'Physical/Emotional Well-Being';


        $core = new ComplianceViewGroup('core', 'All Core Actions Required by '.$this->getEndDate('F j, Y'));

        $hra = new CompleteHRAComplianceView('2011-09-01', $endDate);
        $hra->setReportName('Complete the Annual Online Health Power Assessment (HPA)');
        $hra->setAttribute('report_name_link', '/content/1094#1aHPA');
        $hra->setEvaluateCallback(function (User $user) {
            if($user->hiredate && ($s = $user->getDateTimeObject('hiredate')->format('U'))) {
                return $s < strtotime(HortonHolding20112012ComplianceProgram::HIRE_ALL_NOT_REQUIRED);
            } else {
                return true;
            }
        });
        $core->addComplianceView($hra);

        $scr = new CompleteScreeningComplianceView($startDate, $endDate);
        $scr->setReportName('Complete the Annual Onsite Health Screening');
        $scr->setAttribute('report_name_link', '/content/1094#1bHS');
        $scr->setEvaluateCallback(function (User $user) {
            if($user->hiredate && ($s = $user->getDateTimeObject('hiredate')->format('U'))) {
                return $s < strtotime(HortonHolding20112012ComplianceProgram::HIRE_SCREENING_NOT_REQUIRED);
            } else {
                return true;
            }
        });
        $core->addComplianceView($scr);

        $coach = new GraduateFromCoachingSessionComplianceView($startDate, $endDate);
        $coach->setReportName('Ongoing Phone Health Coaching (Required for some)');
        $coach->setAttribute('report_name_link', '/content/1094#1dHC');
        $coach->setRequireTargeted(true);
        $coach->setEvaluateCallback(function (User $user) {
            if($user->hiredate && ($s = $user->getDateTimeObject('hiredate')->format('U'))) {
                return $s < strtotime(HortonHolding20112012ComplianceProgram::HIRE_SCREENING_NOT_REQUIRED);
            } else {
                return true;
            }
        });
        $core->addComplianceView($coach);

        $this->addComplianceViewGroup($core);

        $points = new ComplianceViewGroup('points', 'And, earn '.self::POINTS_REQUIRED.' or more points from the different areas of well-being by '.$this->getEndDate('F j, Y'));
        $points->setPointsRequiredForCompliance(function (User $user = null) {
            $pointsRequired = HortonHolding20112012ComplianceProgram::POINTS_REQUIRED;

            if($user) {
                if($user->hiredate && ($s = $user->getDateTimeObject('hiredate')->format('U'))) {
                    return $s < strtotime(HortonHolding20112012ComplianceProgram::HIRE_ALL_NOT_REQUIRED) ?
                        $pointsRequired : 0;
                } else {
                    return $pointsRequired;
                }
            } else {
                return $pointsRequired;
            }
        });

        $career = new PlaceHolderComplianceView(null, 0);
        $career->setReportName('Set a Career, Social, Financial, or Family Well-Being Goal');
        $career->setName('career_goal');
        $career->setAttribute('report_name_link', '/content/1094#2all5goal');
        $career->setMaximumNumberOfPoints(50);
        $points->addComplianceView($career, false, $wellBeingSection);

        $onsite = new PlaceHolderComplianceView(null, 0);
        $onsite->setMaximumNumberOfPoints(45);
        $onsite->setAllowPointsOverride(true);
        $onsite->setReportName('Attend Onsite Well-Being Coaching');
        $onsite->setAttribute('report_name_link', '/content/1094#2all5coach');
        $onsite->setName('onsite_coaching');
        $points->addComplianceView($onsite, false, $wellBeingSection);

        $elearn = new CompleteELearningGroupSet($startDate, $endDate, 'required_2012');
        $elearn->setName('elearn_req');
        $elearn->setPointsPerLesson(5);
        $elearn->setAllowAllLessons(true);
        $elearn->setReportName('Complete e-Learning Lessons');
        $elearn->setAttribute('report_name_link', '/content/1094#2all5eLearn');
        $elearn->setMaximumNumberOfPoints(35);
        $points->addComplianceView($elearn, false, $wellBeingSection);

        $sem = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 67, 10);
        $sem->setReportName('Attend It\'s Cool to Be Healthy SEMINARS');
        $sem->setAttribute('report_name_link', '/content/1094#2all5CoolSem');
        $sem->setMaximumNumberOfPoints(50);
        $points->addComplianceView($sem, false, $wellBeingSection);

        $vol = new VolunteeringComplianceView($startDate, $endDate);
        $vol->setReportName('Volunteer Your Time for Charity and Professional Organizations');
        $vol->setAttribute('report_name_link', '/content/1094#2CommVol');
        $vol->setMaximumNumberOfPoints(50);
        $vol->setMinutesDivisorForPoints(60);
        $vol->setPointsMultiplier(5);
        $points->addComplianceView($vol, false, $communitySection);

        $dev = new MinutesBasedActivityComplianceView($startDate, $endDate, 68);
        $dev->setMaximumNumberOfPoints(15);
        $dev->setMinutesDivisorForPoints(60);
        $dev->setReportName('Engage in Professional Development Activities');
        $dev->setAttribute('report_name_link', '/content/1094#2CarProfDev');
        $points->addComplianceView($dev, false, $careerSection);

        $edu = new MinutesBasedActivityComplianceView($startDate, $endDate, 69, 72);
        $edu->setReportName('Attend Formal Education Classes');
        $edu->setAttribute('report_name_link', '/content/1094#2CarEd');
        $edu->setMaximumNumberOfPoints(35);
        $edu->setMinutesDivisorForPoints(1 / 15);
        $points->addComplianceView($edu, false, $careerSection);

        $sur = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $sur->setReportName('Complete the Well-Being Culture & Benefit/Rewards Survey (Spring/Summer 2012)');
        $sur->setAttribute('report_name_link', '/content/1094#2CarSurvey');
        $sur->setMaximumNumberOfPoints(15);
        $points->addComplianceView($sur, false, $careerSection);

        $skill = new ViewLinkComplianceView($startDate, $endDate, new Link('View', 'http://apps.cignabehavioral.com/web/basicsite/consumer/consumer.jsp'));
        $skill->setReportName('Complete a Working Skill Builder or Online Seminar');
        $skill->setName('work_life_view');
        $skill->setAttribute('report_name_link', '/content/1094#2CarOnlineSem');
        $skill->emptyLinks();
        $skill->addLink(new Link('Instructions', '/content/1094#2CarOnlineSem'));
        $skill->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $points->addComplianceView($skill, false, $careerSection);

        $incrFin = new RegexBasedActivityComplianceView($startDate, $endDate, 99, 82);
        $incrFin->setReportName('Save More Money');
        $incrFin->setAttribute('report_name_link', '/content/1094#2FinSave');
        $incrFin->setMaximumNumberOfPoints(10);
        $points->addComplianceView($incrFin, false, $financialSection);

        $finSem = new ViewLinkComplianceView($startDate, $endDate, new Link('View', 'http://apps.cignabehavioral.com/web/basicsite/consumer/consumer.jsp'));
        $finSem->setName('skill_builder');
        $finSem->setReportName('Complete a Working Skill Builder or Online Seminar');
        $finSem->setAttribute('report_name_link', '/content/1094#2FinOnlineSem');
        $finSem->emptyLinks();
        $finSem->addLink(new Link('Instructions', '/content/1094#2FinOnlineSem'));
        $finSem->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $points->addComplianceView($finSem, false, $financialSection);

        $fargoTools = new ViewLinkComplianceView($startDate, $endDate, new Link('View', 'http://www.wellsfargoadvantagefunds.com/wfweb/wf/retirement/tools/living.jsp'));
        $fargoTools->setName('wells_fargo');
        $fargoTools->setReportName('Use Wells Fargo Online Retirement Tools');
        $fargoTools->setAttribute('report_name_link', '/content/1094#2FinWellsFargo');
        $fargoTools->emptyLinks();
        $fargoTools->addLink(new Link('Instructions', '/content/1094#2FinWellsFargo'));
        $fargoTools->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $points->addComplianceView($fargoTools, false, $financialSection);

        $coolEvents = new RegexBasedActivityComplianceView($startDate, $endDate, 72, 73, false);
        $coolEvents->setReportName('Attend It\'s Cool to Be Healthy Events & Group Challenges');
        $coolEvents->setAttribute('report_name_link', '/content/1094#2SocEvents');
        $coolEvents->setMaximumNumberOfPoints(45);
        $points->addComplianceView($coolEvents, false, $socialSection);

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $endDate);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/content/1094#2PhysKBHM');
        $points->addComplianceView($totalCholesterolView, false, $physicalSection);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($startDate, $endDate);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($hdlCholesterolView, false, $physicalSection);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($ldlCholesterolView, false, $physicalSection);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($trigView, false, $physicalSection);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($glucoseView, false, $physicalSection);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($bloodPressureView, false, $physicalSection);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $endDate);
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $points->addComplianceView($bodyFatBMIView, false, $physicalSection);

        $nonSmokerView = new ComplyWithCotinineScreeningTestComplianceView($startDate, $endDate);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker/Low Exposure to Nicotine/Cotinine');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $points->addComplianceView($nonSmokerView, false, $physicalSection);

        $smokingProgram = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 73, 10);
        $smokingProgram->setReportName('Complete a Recommended Smoking Cessation Program. (For tobacco users)');
        $smokingProgram->setAttribute('report_name_link', '/content/1094#2PhysTobacco');
        $smokingProgram->setMaximumNumberOfPoints(30);
        $points->addComplianceView($smokingProgram, false, $physicalSection);

        $doctorView = new UpdateDoctorInformationComplianceView($startDate, $endDate);
        $doctorView->setReportName('Have a Main Doctor');
        $doctorView->setAttribute('report_name_link', '/content/1094#2PhysMainDoc');
        $doctorView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $points->addComplianceView($doctorView, false, $physicalSection);

        $preventiveExamsView = new ObtainPreventiveExamComplianceView($startDate, $endDate, 5);
        $preventiveExamsView->setReportName('Do Recommended Preventive Screenings/Exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#2PhysPrevScreen');
        $preventiveExamsView->setMaximumNumberOfPoints(20);
        $preventiveExamsView->setName('do_preventive_exams');
        $points->addComplianceView($preventiveExamsView, false, $physicalSection);

        $fluVaccineView = new FluVaccineActivityComplianceView($startDate, $endDate);
        $fluVaccineView->setReportName('Have an Annual Flu Vaccine');
        $fluVaccineView->setAttribute('report_name_link', '/content/1094#2PhysFluVac');
        $fluVaccineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $fluVaccineView->setName('flu_vaccine');
        $points->addComplianceView($fluVaccineView, false, $physicalSection);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $endDate);
        $physicalActivityView->setReportName('Get Regular Physical Activity');
        $physicalActivityView->setAttribute('report_name_link', '/content/1094#2PhysPhysAct');
        $physicalActivityView->setMaximumNumberOfPoints(100);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->_setID(71);
        $points->addComplianceView($physicalActivityView, false, $physicalSection);

        $mindfulActivityView = new MinutesBasedActivityComplianceView($startDate, $endDate, 70);
        $mindfulActivityView->setMinutesDivisorForPoints(15);
        $mindfulActivityView->setMaximumNumberOfPoints(30);
        $mindfulActivityView->setName('mindful_activity');
        $mindfulActivityView->setReportName('Complete Relaxation/Resilience-Building Activities');
        $mindfulActivityView->setAttribute('report_name_link', '/content/1094#2Relax');
        $points->addComplianceView($mindfulActivityView, false, $physicalSection);

        $this->addComplianceViewGroup($points);
    }
}