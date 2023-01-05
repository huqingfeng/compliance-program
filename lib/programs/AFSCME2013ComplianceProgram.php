<?php

class AFSCME2013TobaccoFree extends ComplianceView
{
    public function __construct(ComplianceView $view)
    {
        $this->view = $view;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('afscme_tobacco_free');

        if($this->view->getStatus($user)->isCompliant()) {
            $status = ComplianceStatus::COMPLIANT;
        } elseif($record->partial) {
            $status = ComplianceStatus::PARTIALLY_COMPLIANT;
        } else {
            $status = ComplianceStatus::NOT_COMPLIANT;
        }

        return new ComplianceViewStatus($this, $status);
    }

    public function getDefaultName()
    {
        return 'tobacco';
    }

    public function getDefaultReportName()
    {
        return 'Tobacco';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;

    }

    private $view;
}


class AFSCME2013QualifiedSupportComplianceView extends CompleteArbitraryActivityComplianceView
{
    /**
     * Don't return more than one record for a given month.
     *
     * @param User $user
     * @return array
     */
    protected function getRecords(User $user)
    {
        $records = array();

        $months = array();

        foreach(parent::getRecords($user) as $record) {
            $date = date('Y-m', strtotime($record->getDate()));

            if(!isset($months[$date])) {
                $records[] = $record;

                $months[$date] = true;
            }
        }

        return $records;
    }
}

class AFSCME2013BasicComplianceProgramAdminReportPrinter extends BasicComplianceProgramAdminReportPrinter
{
//    protected function showUser(User $user)
//    {
//        return in_array(trim(strtoupper($user->insurance_plan_type)), array(
//            'AFSCME ACTIVE/HIP PLAN/BCBSI',
//            'AFSCME ACT DEPEND HIP/BCBSI',
//            'AFSCME RETIREES HIP PLAN/BCBSI'
//        ));
//    }
}

class AFSCME2013ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new AFSCME2013BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('insurance_plan_type', function (User $user) {
            return $user->insurance_plan_type;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new AFSCME2013ScreeningPrinter();
            $printer->setShowLight(true);
            $printer->setShowPoints(false);

            foreach($this->getComplianceViewGroups() as $group) {
                foreach($group->getComplianceViews() as $view) {
                    if(is_callable(array($view, 'setUseDateForComment'))) {
                        $view->setUseDateForComment(false);
                    }
                }
            }
        } else {
            $printer = new AFSCME2013Printer();
        }

        return $printer;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if($status->getComplianceViewGroupStatus('key')->isCompliant()) {
            $status->getComplianceViewStatus('doc')->setStatus(ComplianceStatus::COMPLIANT);
        }

        $status->setPoints(null);

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if($rec = $viewStatus->getAttribute('newest_record')) {
                    $viewStatus->setComment($rec);
                }
            }
        }
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $mapping->addMapping(
            ComplianceStatus::NA_COMPLIANT, new ComplianceStatusMapping('N/A *', '/images/lights/whitelight.gif')
        );

        $this->setComplianceStatusMapper($mapping);

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $startEndDate = strtotime('2013-05-10');

        $keyEndDate = strtotime('2013-10-15');

        $learnStartDate = strtotime('2013-01-01');

        $demonstrateStartDate = strtotime('2013-01-01');

        $nonSmokerView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($startDate, $keyEndDate);
        $nonSmokerView->setUseDateForComment(true);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker/Non-User of Tobacco');
        $nonSmokerView->setAttribute('report_name_link', '/sitemaps/health_centers/15946');

        $start = new ComplianceViewGroup('start', 'Starting core actions required by deadline below:');

        $scr = new CompleteScreeningComplianceView($startDate, '2013-05-01');
        $scr->setReportName('Complete Wellness Screening');
        $scr->setAttribute('report_name_link', '/content/1094#1ascreen');
        $scr->emptyLinks();
        $scr->addLink(new Link('Results','/content/989'));
        $start->addComplianceView($scr);

        $hpa = new CompleteHRAComplianceView($startDate, '2013-05-01');
        $hpa->setReportName('Complete Health Power Assessment');
        $hpa->setAttribute('report_name_link', '/content/1094#1bhpa');
        $start->addComplianceView($hpa);

        $doc = new UpdateDoctorInformationComplianceView($startDate, $startEndDate);
        $doc->setReportName('Verify having a main doctor/primary care provider');
        $doc->setAttribute('report_name_link', '/content/1094#1cmaindoc');
        $start->addComplianceView($doc);

        $updateInfo = new UpdateContactInformationComplianceView($startDate, $startEndDate);
        $updateInfo->setReportName('Verify/Update my current contact information');
        $updateInfo->setAttribute('report_name_link', '/content/1094#1dpers');
        $start->addComplianceView($updateInfo);

        $workbook = new AFSCMEViewWorkbookComplianceView($startDate, $startEndDate);
        $workbook->setReportName('View your Health Navigator');
        $workbook->setAttribute('report_name_link', '/content/1094#1ehealthnav');
        $start->addComplianceView($workbook);

        $elearn = new CompleteRequiredELearningLessonsComplianceView($learnStartDate, '2013-05-10');
        $elearn->setReportName('Complete all mandatory e-learning lessons');
        $elearn->setAttribute('report_name_link', '/content/1094#1felearn');
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/elearning_middle_page'));
        $start->addComplianceView($elearn);

        $tobView = new AFSCME2013TobaccoFree($nonSmokerView);
        $tobView->setName('tob_view');
        $tobView->setReportName('Do Not Use Any Tobacco or Complete Cessation Program');
        $tobView->setAttribute('report_name_link', '/content/1094#1gtobacco');
        $tobView->setAttribute('deadline', '07/01/2013');
        $tobView->emptyLinks();
        $tobView->addLink(new Link('Enroll in Cessation Program', '/content/afscme-tobacco'));
        $start->addComplianceView($tobView);

        $this->addComplianceViewGroup($start);

        $ongoing = new ComplianceViewGroup('ongoing', 'Ongoing core actions all year');

        $calls = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $calls->setReportName('Make required calls to the Care Counselor BEFORE receiving certain types of health care and other times.  If a counselor calls you, return the call AND work with him/her until you are told you are finished.');
        $calls->setAttribute('report_name_link', '/content/1094#2acounsel');
        $calls->setName('calls_1');
        $calls->setAttribute('deadline', 'Within 5 days of being called each time');
        $calls->addLink(new Link('Learn More', '/content/5317 '));
        $ongoing->addComplianceView($calls);

        $callsTwo = new GraduateFromCoachingSessionComplianceView($startDate, $endDate);
        $callsTwo->setRequireTargeted(true);
        $callsTwo->setReportName('When Red, schedule a time for a call from a health coach and work with them.  When Yellow, keep working with your health coach at scheduled times until told you are done.');
        $callsTwo->setAttribute('report_name_link', '/content/1094#2bcoach');
        $callsTwo->setName('calls_2');
        $callsTwo->setAttribute('deadline', 'With 5 days of being notified.');
        $callsTwo->addLink(new Link('Learn More', '/content/1094#2bcoach'));
        $ongoing->addComplianceView($callsTwo);

        $callsThree = new CompleteAssignedELearningLessonsComplianceView($startDate, '2013-10-01');
        $callsThree->setReportName('Complete extra e-Learning lessons and decision tools recommended by Health Coach or Nurse.');
        $callsThree->setAttribute('report_name_link', '/content/1094#2ccoacheLearn');
        $callsThree->setName('calls_3');
        $callsThree->setAttribute('deadline', 'Within 30 days of recommendation');
        current($callsThree->getLinks())->setLinkText('View/Do Lessons');
        $ongoing->addComplianceView($callsThree);

        $this->addComplianceViewGroup($ongoing);


        $key = new ComplianceViewGroup('key', 'Key measures of health');

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $keyEndDate);
        $totalCholesterolView->setAttribute('_screening_printer_hack', 8);
        $totalCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $totalCholesterolView->setUseDateForComment(true);
        $totalCholesterolView->setAlternativeComplianceView($this->getAlternateElearningView($key, $learnStartDate, 'cholesterol'));
        $key->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($startDate, $keyEndDate);
        $hdlCholesterolView->setUseDateForComment(true);
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setAlternativeComplianceView($this->getAlternateElearningView($key, $learnStartDate, 'cholesterol'));
        $key->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($startDate, $keyEndDate);
        $ldlCholesterolView->setUseDateForComment(true);
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $ldlCholesterolView->setAlternativeComplianceView($this->getAlternateElearningView($key, $learnStartDate, 'cholesterol'));
        $key->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $keyEndDate);
        $trigView->setUseDateForComment(true);
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAlternativeComplianceView($this->getAlternateElearningView($key, $learnStartDate, 'cholesterol'));
        $key->addComplianceView($trigView, false);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $keyEndDate);
        $glucoseView->setUseDateForComment(true);
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAlternativeComplianceView($this->getAlternateElearningView($key, $learnStartDate, 'blood_sugars'));
        $key->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $keyEndDate);
        $bloodPressureView->setUseDateForComment(true);
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAlternativeComplianceView($this->getAlternateElearningView($key, $learnStartDate, 'blood_pressure'));
        $key->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $keyEndDate);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $bodyFatBMIView->setUseDateForComment(true);
        $bodyFatBMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');
        $bodyFatBMIView->setAlternativeComplianceView($this->getAlternateElearningView($key, $learnStartDate, 'body_fat'));
        $key->addComplianceView($bodyFatBMIView);

        $nonSmokerView->setAlternativeComplianceView($this->getAlternateElearningView($key, $learnStartDate, 'tobacco'));
        $key->addComplianceView($nonSmokerView);

        $this->addComplianceViewGroup($key);

        $keyMeasureCallback = array($this, 'keyMeasuresCompletedSoNotRequired');

        $dem = new ComplianceViewGroup('demonstrate', 'Get 3 or more health action areas below in the green by recording your efforts to improve and/or maintain your health.');
        $dem->setNumberOfViewsRequired(4);

        $phy = new PhysicalActivityComplianceView($demonstrateStartDate, $endDate);
        $phy->_setID(241);
        $phy->setReportName('Get Regular Exercise - Walk, bike, etc. at least 150 hours.');
        $phy->setAttribute('report_name_link', '/content/1094#4aphys');
        $phy->setFractionalDivisorForPoints(1);
        $phy->setMinutesDivisorForPoints(1);
        $phy->setCompliancePointStatusMapper(new CompliancePointStatusMapper(60 * 150, 1, 0, 0));
        $dem->addComplianceView($phy);

        $veri = new AFSCME2013QualifiedSupportComplianceView($demonstrateStartDate, $endDate, 243, 1);
        $veri->setCompliancePointStatusMapper(new CompliancePointStatusMapper(4, 1));
        $veri->setReportName('Verify Other Key Health Actions Taken - weight watchers, nutrition counseling, using PSP, classes on cooking diabetes, stress management or other health topics, etc.');
        $veri->setAttribute('report_name_link', '/content/1094#4bactions');
        $veri->setName('veri');
        $veri->setMaximumNumberOfPoints(100);
        $veri->setAttribute('deadline', date('m/d/Y', $endDate));
        $veri->setEvaluateCallback($keyMeasureCallback, ComplianceStatus::COMPLIANT);

        $dem->addComplianceView($veri);

        $doc = new PlaceHolderComplianceView(null, 0);
        $doc->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $doc->setReportName('Work with doctor to improve health');
        $doc->setAttribute('deadline', date('m/d/Y', $endDate));
        $doc->setAttribute('report_name_link', '/content/1094#4cworkDoc');
        $doc->setName('doc');
        $doc->setMaximumNumberOfPoints(100);
        $doc->addLink(new Link('Click For Form', '/resources/4155/A31 2013 PHIP To-Do 4C Doctor Support Form 011513.pdf'));
        $dem->addComplianceView($doc);


        $prev = new CompletePreventiveExamWithRollingStartDateLogicComplianceView(date('Y-m-d'), $endDate, 10);
        $prev->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $prev->setMaximumNumberOfPoints(20);
        $prev->setReportName('Record preventive screenings/exams obtained');
        $prev->setAttribute('report_name_link', '/content/1094#4dprevScreen');
        $dem->addComplianceView($prev);

        $imm = new CompleteImmunizationsWithRollingStartDateLogicComplianceView(date('Y-m-d'), $endDate, 10);
        $imm->setCompliancePointStatusMapper(new CompliancePointStatusMapper(20, 10, 0, 0));
        $imm->setMaximumNumberOfPoints(20);
        $imm->setReportName('Record Immunizations Obtained');
        $imm->setAttribute('report_name_link', '/content/1094#4eimmun');
        $dem->addComplianceView($imm);

        $this->addComplianceViewGroup($dem);
    }

    public function keyMeasuresCompletedSoNotRequired(User $user)
    {
        $group = $this->getComplianceViewGroup('key');

        return !$group->getStatusForUser($user)->isCompliant();
    }

    private function getAlternateElearningView($group, $startDate, $alias)
    {
        $view = new CompleteELearningGroupSet($startDate, '2013-10-15', $alias);

        $view->useAlternateCode(true);

        $view->setNumberRequired(2);

        $view->setComplianceViewGroup($group);

        return $view;
    }
}

class AFSCME2013Printer extends BasicComplianceProgramReportPrinter
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
        $this->setScreeningResultsLink(new Link('Have these measures in the healthy green zone or complete 2 related e-learning lessons to help improve each that is not green. ', '/content/1094#3abio'));

        $this->pageHeading = 'Personal Health Improvement Prescription / To-Do Summary Page';

        $this->numberScreeningCategory = false;
        $this->showName = true;
        $this->setShowTotal(false);

        $this->screeningAllResultsArea = '';
        $this->screeningLinkText = '';

        $this->addStatusCallbackColumn('Deadline', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            $default = $view instanceof DateBasedComplianceView ?
                $view->getEndDate('m/d/Y') : '';

            return $view->getAttribute('deadline', $default);
        });

        $this->screeningLinkArea = '';

        $this->tableHeaders['links'] = 'Action Links';
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <script type="text/javascript">
            $(function() {
                // Rework Action Links to line up eLearning links with appropriate views

                var $tc = $('td.links[rowspan="8"]').parent('tr');

                $tc.find('td.links').remove();

                var $topLinks = $tc.prev('tr').find('.links');

                var $glucose = $tc.next('tr').next('tr').next('tr').next('tr');

                var $bp = $glucose.next('tr');

                var $bmi = $bp.next('tr');

                var $tobacco = $bmi.next('tr');

                $topLinks.append('<a href="?preferredPrinter=ScreeningProgramReportPrinter&id=239">View All 8 Results after screening results are received.</a>');

                $tc.append('<td class="links" rowspan="4"><a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Review/Do 2 Blood Fat Lessons</a></td>');

                $glucose.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Review/Do 2 Blood Sugar Lessons</a></td>');

                $bp.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Review / Do 2 BP Lessons</a></td>');

                $bmi.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=body_fat">Review/Do 2 Body Metrics Lessons</a></td>');

                $tobacco.append('<td class="links"><a href="/content/9420?action=lessonManager&tab_alias=tobacco">Review/Do 2 Tobacco Lessons</a></td>');
            });
        </script>
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
        <p>Welcome to your new 2013 PHIP. As you know, all adult HIP participants must
            get certain things done throughout the year in order to maintain eligibility
            for the Health Improvement Plan benefit.</p>

        <p>All current requirements (To-Dos) are listed below:</p>

        <ul>
            <li>It is your responsibility to complete all of HIP’s requirements or risk
                disenrollment from HIP and transfer to the Standard Plan, which requires
                you to pay more of your health care costs.
            </li>
            <li>The enrollment agreement you signed with HIP obligates you to learn
                and do more to improve your health and work with health professionals
                to secure good quality, evidence-based health care.
            </li>
            <li>As a HIP member, you must demonstrate the you’re doing all you can do
                to become and stay healthy and make sound, evidence-based decisions
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
            <li>Use the Action Links in the right column to get things done or for more
                information.
            </li>
            <li>Please visit this page often to check your status, get To-Dos done
                and to see if new lessons or requirements have been added.
            </li>
        </ul>
        <?php
    }
}


class AFSCME2013ScreeningPrinter extends ScreeningProgramReportPrinter
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
    <table width="95%" border="0" style="margin-left: 10px;" id="ratingsTable2">
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
                    <strong><font color="#ff0000">At-Risk</font></strong></td>
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
                <td height="36">
                    <p><u>Key measures and ranges</u></p>
                </td>
                <td align="center" width="72" bgcolor="#ccffcc" class="grayArrow">
                </td>
                <td align="center" width="73" bgcolor="#ffff00" class="grayArrow">
                </td>
                <td align="center" width="112" bgcolor="#ff909a" class="grayArrow">
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
                            <strong>Tobacco</strong></li>
                    </ol>
                </td>
                <td align="center" width="72" bgcolor="#ccffcc">
                    Non-user
                </td>
                <td align="center" width="73" bgcolor="#ffff00">
                    User
                </td>
                <td align="center" width="112" bgcolor="#ff909a">
                    User
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    }
}