<?php

use hpn\steel\query\SelectQuery;

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/jawbone/lib/model/jawboneApi.php';
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/moves/lib/model/movesApi.php';

class AbelsonTaylor2016IndividualActivityStepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $points)
    {
        $this->setDateRange($startDate, $endDate);

        $this->threshold = $threshold;
        $this->points = $points;

        $formattedThreshold = number_format($this->threshold);

        $this->setAttribute('requirement', "Walk an average of {$formattedThreshold} steps/day");
    }

    public function getDefaultStatusSummary($status)
    {
        if($status == ComplianceStatus::COMPLIANT) {
            return $this->getAttribute('requirement');
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return "hmi_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {
        $fitbitData = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), null, false);

        try{
            JawboneApi::refreshJawboneData($user);
        } catch(Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }

        $jawboneData = JawboneApi::getJawboneData($user);

        try{
            MovesApi::refreshMovesData($user);
        } catch(Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }

        $movesData = MovesApi::getMovesData($user, true);


        $totalPoints = 0;
        $totalSteps = 0;
        foreach($this->getMonths() as $month) {
            if(date('Y-m-d') >= $month['start_count']) {
                $dates = $this->getDatesInRange($month['start_date'], $month['end_date']);

                $monthlySteps = 0;
                foreach($dates as $date) {
                    $steps = 0;

                    if(isset($fitbitData['dates'][$date]) && $fitbitData['dates'][$date] > $steps) {
                        $steps = $fitbitData['dates'][$date];
                    }

                    if(isset($jawboneData['dates'][$date]) && $jawboneData['dates'][$date] > $steps) {
                        $steps = $jawboneData['dates'][$date];
                    }

                    if(isset($movesData['dates'][$date]) && $movesData['dates'][$date] > $steps) {
                        $steps = $movesData['dates'][$date];
                    }

                    $monthlySteps += $steps;
                    $totalSteps += $steps;
                }
                $monthlyAverage = $monthlySteps/count($dates);

                if($monthlyAverage >= $this->threshold) {
                    $totalPoints += $this->points;
                }
            }
        }


        $status = new ComplianceViewStatus($this, null, $totalPoints);

        $status->setAttribute('total_steps', $totalSteps);

        return $status;
    }

    private function getMonths()
    {
        return array(
            'mar'   => array(
                'start_date' => '2016-03-01',
                'end_date'   => '2016-03-31',
                'start_count'=> '2016-03-01'
            ),
            'apri'     => array(
                'start_date' => '2016-04-01',
                'end_date'   => '2016-04-30',
                'start_count'=> '2016-04-01'
            ),
            'may'        => array(
                'start_date' => '2016-05-01',
                'end_date'   => '2016-05-31',
                'start_count'=> '2016-05-01'
            ),
            'june'        => array(
                'start_date' => '2016-06-01',
                'end_date'   => '2016-06-30',
                'start_count'=> '2016-06-01'
            ),
            'july'      => array(
                'start_date' => '2016-07-01',
                'end_date'   => '2016-07-31',
                'start_count'=> '2016-07-01'
            ),
            'aug'       => array(
                'start_date' => '2016-08-01',
                'end_date'   => '2016-08-31',
                'start_count'=> '2016-08-01'
            ),
            'sep'       => array(
                'start_date' => '2016-09-01',
                'end_date'   => '2016-09-30',
                'start_count'=> '2016-09-01'
            ),
            'oct'       => array(
                'start_date' => '2016-10-01',
                'end_date'   => '2016-10-31',
                'start_count'=> '2016-10-01'
            ),
            'nov'      => array(
                'start_date' => '2016-11-01',
                'end_date'   => '2016-11-30',
                'start_count'=> '2016-11-01'
            ),
            'dec'       => array(
                'start_date' => '2016-12-01',
                'end_date'   => '2016-12-31',
                'start_count'=> '2016-12-01'
            ),
            'jan'       => array(
                'start_date' => '2017-01-01',
                'end_date'   => '2017-01-31',
                'start_count'=> '2017-01-01'
            ),
            'feb'       => array(
                'start_date' => '2017-02-01',
                'end_date'   => '2017-02-28',
                'start_count'=> '2017-02-01'
            ),
        );
    }

    private function getDatesInRange($strDateFrom, $strDateTo)
    {
        $aryRange=array();

        $iDateFrom=mktime(1,0,0,substr($strDateFrom,5,2),substr($strDateFrom,8,2),substr($strDateFrom,0,4));
        $iDateTo=mktime(1,0,0,substr($strDateTo,5,2),substr($strDateTo,8,2),substr($strDateTo,0,4));

        if ($iDateTo>=$iDateFrom)
        {
            array_push($aryRange,date('Y-m-d',$iDateFrom));
            while ($iDateFrom<$iDateTo)
            {
                $iDateFrom += 86400;
                array_push($aryRange,date('Y-m-d',$iDateFrom));
            }
        }
        return $aryRange;
    }

    private $threshold;
}

class AbelsonTaylor2016BeatCFOComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $activityId, $questionId)
    {
        $this->setDateRange($startDate, $endDate);
        $this->activityId = $activityId;
        $this->questionId = $questionId;
    }

    public function getDefaultName()
    {
        return 'beat_cfo';
    }

    public function getDefaultReportName()
    {
        return 'Beat CFO';
    }

    public function getDefaultStatusSummary($status)
    {

    }

    public function getStatus(User $user)
    {
        $cfoData = $this->getCfoData();

        if(!$cfoData || !isset($cfoData['dates'])) {
            $cfoData = array('dates' => array());
        }

        // For each month in the program, if the CFO has data for the next month,
        // assume the prior month is done. If we have more average steps than
        // the cfo for that prior month, award points.
        $data = array('dates' => array());

        $fitbitData = get_all_fitbit_data(
            $user->id,
            '2016-01-01', // For "This Month" data when program hasn't started
            $this->getEndDate('Y-m-d')
        );

        try{
            JawboneApi::refreshJawboneData($user);
        } catch(Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }

        $jawboneData = JawboneApi::getJawboneData($user);

        try{
            MovesApi::refreshMovesData($user);
        } catch(Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }

        $movesData = MovesApi::getMovesData($user, true);

        foreach($fitbitData['dates'] as $date => $steps) {
            $maxSteps = $steps;

            if(isset($jawboneData['dates'][$date]) && $jawboneData['dates'][$date] > $maxSteps) {
                $maxSteps = $jawboneData['dates'][$date];
            }

            if(isset($movesData['dates'][$date]) && $movesData['dates'][$date] > $maxSteps) {
                $maxSteps = $movesData['dates'][$date];
            }

            $data['dates'][$date] = $maxSteps;
        }


        $points = 0;
        foreach($this->getCalculableMonths($cfoData) as $monthStr) {
            $userAverage = $this->totalSteps($data['dates'], $monthStr);
            $cfoAverage = $this->totalSteps($cfoData['dates'], $monthStr);

            if($userAverage > $cfoAverage) {
//                comment out since points will be entered by admin.
//                $points += 5;
            }
        }

        $currentMonthStr = date('Y-m');

        $viewStatus = new ComplianceViewStatus($this, null, $points);

        $viewStatus->addAttributes(array(
            'user_month_total' =>
                $this->totalSteps($data['dates'], $currentMonthStr),

            'cfo_month_total'  =>
                $this->totalSteps($cfoData['dates'], $currentMonthStr)
        ));

        return $viewStatus;
    }

    private function totalSteps($data, $monthStr)
    {
        $total = 0;

        $daysInMonth = $this->getDaysInMonth($monthStr);

        foreach($daysInMonth as $day) {
            if(isset($data[$day])) {
                $total += $data[$day];
            }
        }

        return $total;
    }

    private function getDaysInMonth($monthStr)
    {
        $days = array();

        $startDate = new \DateTime($monthStr.'-01');
        $endDate = new \DateTime($startDate->format('Y-m-t'));

        while($startDate <= $endDate) {
            $days[] = $startDate->format('Y-m-d');

            $startDate->add(new \DateInterval('P1D'));
        }

        return $days;
    }

    private function getCalculableMonths($cfoData)
    {
        $daysWithPoints = array_keys(
            array_filter($cfoData['dates'], function($el) {
                return $el > 0;
            })
        );

        $lastDayWithData = end($daysWithPoints);

        if(!$lastDayWithData) {
            return array();
        }

        $months = array();

        $startDate = $this->getStartDate('Y-m-01');

        $endDate = date(
            'Y-m-t',
            strtotime($lastDayWithData)
        );

        $startObject = new \DateTime($startDate);
        $endObject = new \DateTime($endDate);
        $endObject->sub(new \DateInterval('P1M'));

        while($startObject < $endObject) {
            $months[] = $startObject->format('Y-m');

            $startObject->add(new \DateInterval('P1M'));
        }

        return $months;
    }

    private function getCfoData()
    {
        $cfoUser = UserTable::getInstance()->find($this->cfoUserId);

        if($cfoUser) {
            $data = array('dates' => array());

            $fitbitData = get_all_fitbit_data(
                $cfoUser->id,
                $this->getStartDate('Y-m-d'),
                $this->getEndDate('Y-m-d')
            );

            try{
                JawboneApi::refreshJawboneData($cfoUser);
            } catch(Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

            $jawboneData = JawboneApi::getJawboneData($cfoUser);

            try{
                MovesApi::refreshMovesData($cfoUser);
            } catch(Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

            $movesData = MovesApi::getMovesData($cfoUser, true);



            foreach($fitbitData['dates'] as $date => $steps) {
                $maxSteps = $steps;

                if(isset($jawboneData['dates'][$date]) && $jawboneData['dates'][$date] > $maxSteps) {
                    $maxSteps = $jawboneData['dates'][$date];
                }

                if(isset($movesData['dates'][$date]) && $movesData['dates'][$date] > $maxSteps) {
                    $maxSteps = $movesData['dates'][$date];
                }

                $data['dates'][$date] = $maxSteps;
            }
        }

        return $data;
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    private $cfoUserId = 2781371;
}

class AbelsonTaylor2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('August - Steps', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $data = array('dates' => array());

            $fitbitData = get_all_fitbit_data(
                $user->id,
                '2016-08-01', // For "This Month" data when program hasn't started
                '2016-08-31'
            );

            try{
                JawboneApi::refreshJawboneData($user);
            } catch(Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

            $jawboneData = JawboneApi::getJawboneData($user);

            try{
                MovesApi::refreshMovesData($user);
            } catch(Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

            $movesData = MovesApi::getMovesData($user, true);


            foreach($fitbitData['dates'] as $date => $steps) {
                $maxSteps = $steps;

                if(isset($jawboneData['dates'][$date]) && $jawboneData['dates'][$date] > $maxSteps) {
                    $maxSteps = $jawboneData['dates'][$date];
                }

                if(isset($movesData['dates'][$date]) && $movesData['dates'][$date] > $maxSteps) {
                    $maxSteps = $movesData['dates'][$date];
                }

                $data['dates'][$date] = $maxSteps;
            }


            $data['total_steps'] = array_sum($data['dates']);

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('December - Steps', function(ComplianceProgramStatus $status) {
            $user = $status->getUser();

            $data = array('dates' => array());

            $fitbitData = get_all_fitbit_data(
                $user->id,
                '2016-12-01', // For "This Month" data when program hasn't started
                '2016-12-31'
            );

            try{
                JawboneApi::refreshJawboneData($user);
            } catch(Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

            $jawboneData = JawboneApi::getJawboneData($user);

            try{
                MovesApi::refreshMovesData($user);
            } catch(Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

            $movesData = MovesApi::getMovesData($user, true);

            foreach($fitbitData['dates'] as $date => $steps) {
                $maxSteps = $steps;

                if(isset($jawboneData['dates'][$date]) && $jawboneData['dates'][$date] > $maxSteps) {
                    $maxSteps = $jawboneData['dates'][$date];
                }

                if(isset($movesData['dates'][$date]) && $movesData['dates'][$date] > $maxSteps) {
                    $maxSteps = $movesData['dates'][$date];
                }

                $data['dates'][$date] = $maxSteps;
            }


            $data['total_steps'] = array_sum($data['dates']);

            return $data['total_steps'];
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Program');
//        $coreGroup->setPointsRequiredForCompliance(50);

        $hraView = new CompleteHRAComplianceView('2016-02-15', '2017-02-28');
        $hraView->setReportName('Health Power Profile Questionnaire');
        $hraView->setName('complete_hra');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $hraView->setAttribute('report_name_link', '/content/1094#ahpa');
        $hraView->setAttribute('requirement', 'Employee completes the Health Power Profile Questionnaire');
        $hraView->setAttribute('points_per_activity', '10');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView('2016-02-15', '2017-02-28');
        $screeningView->setReportName('Employee participates in Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $screeningView->setAttribute('report_name_link', '/content/1094#bScreen');
        $screeningView->setAttribute('requirement', 'Employee participates in Wellness Screening');
        $screeningView->setAttribute('points_per_activity', '10');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
        $screeningView->addLink(new Link('MD Physician Form', '/resources/7280/Abelson Taylor MD Form 2016.pdf'));
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        // While it contains the employee constant, an employee can't have a
        // related employee user so it doesn't matter.
        $eligibleDependents = Relationship::get();

        $spouseHraScr = new RelatedUserCompleteComplianceViewsComplianceView($this, array('complete_hra', 'complete_screening'), $eligibleDependents);
        $spouseHraScr->setPointsPerCompletion(20);
        $spouseHraScr->setMaximumNumberOfPoints(40);
        $spouseHraScr->setReportName('Health Power Profile Questionnaire & Wellness Screening');
        $spouseHraScr->setName('spouse_hra_screening');
        $spouseHraScr->setAttribute('points_per_activity', 20);
        $spouseHraScr->addLink(new Link('Do HPA', '/content/989'));
        $wellnessGroup->addComplianceView($spouseHraScr);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $bloodPressureView->setAttribute('points_per_activity', 5);
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, 89);
        $bloodPressureView->setMergeScreenings(true);
        $bloodPressureView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $bloodPressureView->addLink(new Link('Results', '/compliance_programs?preferredPrinter=ScreeningProgramReportPrinter&id=692'));
        $wellnessGroup->addComplianceView($bloodPressureView);

        $tcView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $tcView->setReportName('Total Cholesterol');
        $tcView->setName('cholesterol');
        $tcView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $tcView->setAttribute('points_per_activity', 5);
        $tcView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $tcView->overrideTestRowData(null, null, 199.999, null);
        $wellnessGroup->addComplianceView($tcView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('Glucose');
        $glucoseView->setName('glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $glucoseView->setAttribute('points_per_activity', 5);
        $glucoseView->setAttribute('report_name_link', '/content/1094#2bBiometric');
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $wellnessGroup->addComplianceView($glucoseView);

        $improvedBiometric = new PlaceHolderComplianceView(null, 0);
        $improvedBiometric->setMaximumNumberOfPoints(15);
        $improvedBiometric->setReportName('Improved Biometric Results');
        $improvedBiometric->setName('improved_biometric');
        $improvedBiometric->setAttribute('points_per_activity', 5);
        $wellnessGroup->addComplianceView($improvedBiometric);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 290, 15);
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $annualPhysicalExamView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Get a physical exam with appropriate tests for your age and gender as recommended by your physician.');
        $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening follow up');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->setAttribute('report_name_link', '/content/1094#ePhysExam');
        $annualPhysicalExamView->setAttribute('points_per_activity', '20');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link('Verification Form <br />', '/resources/7217/AT_PreventiveServices Cert2016.pdf'));
        $annualPhysicalExamView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $wellnessGroup->addComplianceView($annualPhysicalExamView);

        $wellnessFair = new PlaceHolderComplianceView(null, 0);
        $wellnessFair->setMaximumNumberOfPoints(5);
        $wellnessFair->setReportName('Wellness Fair Attendance');
        $wellnessFair->setName('wellness_fair');
        $wellnessFair->setAttribute('points_per_activity', 5);
        $wellnessGroup->addComplianceView($wellnessFair);

        $wellnessKickOff = new PlaceHolderComplianceView(null, 0);
        $wellnessKickOff->setReportName('Wellness Kick off');
        $wellnessKickOff->setName('kick_off');
        $wellnessKickOff->setAttribute('points_per_activity', 10);
        $wellnessKickOff->setMaximumNumberOfPoints(10);
        $wellnessKickOff->emptyLinks();
        $wellnessKickOff->addLink(new FakeLink('Sign in at Event', '/content/events'));
        $wellnessGroup->addComplianceView($wellnessKickOff);

        $preventiveExamsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($programStart, $programEnd, 5);
        $preventiveExamsView->configureActivity(259, 91, array(
            'Colonoscopy'   => 60,
            'Dental Exam'   => 24,
            'Mammogram'     => 24,
            'Pap Test'      => 36,
            'Physical Exam' => 36,
            'PSA Test'      => 60
        ));
        $preventiveExamsView->setReportName("Preventive Services");
        $preventiveExamsView->setMaximumNumberOfPoints(30);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setAttribute('report_name_link', '/content/1094#gPrevServices');
        $preventiveExamsView->setAttribute('requirement', 'Receive a preventive service such as mammogram, prostate exam, routine dental exam, etc.');
        $preventiveExamsView->setAttribute('points_per_activity', '10');
        $preventiveExamsView->emptyLinks();
        $preventiveExamsView->addLink(new Link('Verification Form <br />', '/resources/7217/AT_PreventiveServices Cert2016.pdf'));
        $preventiveExamsView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $wellnessGroup->addComplianceView($preventiveExamsView);

        $flushotView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $flushotView->setReportName('Flu Shot');
        $flushotView->setName('flu_shot');
        $flushotView->setMaximumNumberOfPoints(30);
        $flushotView->setAttribute('report_name_link', '/content/1094#hFluShot');
        $flushotView->setAttribute('requirement', 'Receive a flu Shot');
        $flushotView->setAttribute('points_per_activity', '10');
        $flushotView->emptyLinks();
        $flushotView->addLink(new Link('Verification Form <br />', '/resources/7217/AT_PreventiveServices Cert2016.pdf'));
        $flushotView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $wellnessGroup->addComplianceView($flushotView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('E-learning');
        $elearn->setName('elearning');
        $elearn->setAttribute('report_name_link', '/content/1094#iRecElearn');
        $elearn->setAttribute('requirement', 'Complete an online e-learning course');
        $elearn->setAttribute('points_per_activity', '5');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(25);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=completed_compliance'));
        $wellnessGroup->addComplianceView($elearn);

        $moduleCourseView = new PlaceHolderComplianceView(null, 0);
        $moduleCourseView->setReportName('Smoking Cessation  <br /> &nbsp;&nbsp; Stress Management  <br /> &nbsp;&nbsp; Nutrition Management');
        $moduleCourseView->setName('module_course');
        $moduleCourseView->setAttribute('requirement', 'Complete a 12 module course through BCBS WellOnTraget');
        $moduleCourseView->setAttribute('points_per_activity', '15');
        $moduleCourseView->setAttribute('report_name_link', '/content/1094#jOnMyTime');
        $moduleCourseView->emptyLinks();
        $moduleCourseView->addLink((new Link('Click Here', 'http://wellontarget.com')));
        $moduleCourseView->setMaximumNumberOfPoints(45);
        $wellnessGroup->addComplianceView($moduleCourseView);

        $GLnlPrograms = new AttendEventComplianceView($programStart, $programEnd);
        $GLnlPrograms->bindTypeIds(array(9));
        $GLnlPrograms->setPointsPerAttendance(10);
        $GLnlPrograms->setReportName('Lunch and Learn Presentation');
        $GLnlPrograms->setName('lunch_and_learn');
        $GLnlPrograms->setAttribute('report_name_link', '/content/1094#kLnL');
        $GLnlPrograms->setAttribute('points_per_activity', '10');
        $GLnlPrograms->setMaximumNumberOfPoints(100);
        $GLnlPrograms->addLink(new FakeLink('Sign in at Event', '/content/events'));
        $wellnessGroup->addComplianceView($GLnlPrograms);

        $compassServices = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 330, 10);
        $compassServices->setReportName('Compass Services');
        $compassServices->setName('compass_services');
        $compassServices->setMaximumNumberOfPoints(20);
        $compassServices->setAttribute('requirement', 'Participate in a Compass Educational Event or engage with the Health Pro Consultant');
        $compassServices->setAttribute('points_per_activity', '5');
        $compassServices->setAttribute('report_name_link', '/content/1094#fKickoff');
        $compassServices->emptyLinks();
        $wellnessGroup->addComplianceView($compassServices);

        $abelsonTaylorWellnessEventsView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $abelsonTaylorWellnessEventsView->setReportName('Other Abelson Taylor Wellness Events');
        $abelsonTaylorWellnessEventsView->setName('abelson_taylor_wellness_events');
        $abelsonTaylorWellnessEventsView->setMaximumNumberOfPoints(100);
        $abelsonTaylorWellnessEventsView->setAttribute('report_name_link', '/content/1094#lOther');
        $abelsonTaylorWellnessEventsView->setAttribute('requirement', 'Participate in a designated wellness activity and earn the specified number of points (TBD)');
        $abelsonTaylorWellnessEventsView->setAttribute('points_per_activity', 'TBD');
        $abelsonTaylorWellnessEventsView->emptyLinks();
        $abelsonTaylorWellnessEventsView->addLink(new FakeLink('Admin will Enter', '#'));
        $wellnessGroup->addComplianceView($abelsonTaylorWellnessEventsView);

        $wellnessGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($wellnessGroup);

        $walkingGroup = new ComplianceViewGroup('walking_programs', 'Program');
        $walkingGroup->setPointsRequiredForCompliance(0);

        $individualWalkingStart = '2016-03-01';
        $individualWalkingEnd = '2017-02-28';

        $stepOne = new AbelsonTaylor2016IndividualActivityStepsComplianceView($individualWalkingStart, $individualWalkingEnd, 7500, 1);
        $stepOne->setReportName('Individual Walking Challenge');
        $stepOne->setName('step_one');
        $stepOne->setAttribute('points_per_activity', '1 point per month');
        $stepOne->setMaximumNumberOfPoints(12);
        $stepOne->addLink(new Link('Fitbit Sync <br /><br />', '/content/ucan-fitbit-individual'));
        $stepOne->addLink(new Link('Moves Sync <br /><br />', '/standalone/moves'));
        $stepOne->addLink(new Link('Jawbone Sync <br />', '/standalone/jawbone'));
        $stepOne->setAttribute('report_name_link', '/content/1094#indwalk6k');
        $walkingGroup->addComplianceView($stepOne);

        $stepTwo = new AbelsonTaylor2016IndividualActivityStepsComplianceView($individualWalkingStart, $individualWalkingEnd, 12000, 1);
        $stepTwo->setReportName('Individual Walking Challenge');
        $stepTwo->setName('step_two');
        $stepTwo->setAttribute('points_per_activity', 'Additional 1 point per month');
        $stepTwo->setAttribute('report_name_link', '/content/1094#indwalk80k');
        $stepTwo->setMaximumNumberOfPoints(12);
        $walkingGroup->addComplianceView($stepTwo);

        $stepThree = new AbelsonTaylor2016IndividualActivityStepsComplianceView($individualWalkingStart, $individualWalkingEnd, 15000, 1);
        $stepThree->setReportName('Individual Walking Challenge');
        $stepThree->setName('step_three');
        $stepThree->setAttribute('points_per_activity', 'Additional 1 point per month');
        $stepThree->setAttribute('report_name_link', '/content/1094#indwalk10k');
        $stepThree->setMaximumNumberOfPoints(12);
        $walkingGroup->addComplianceView($stepThree);

        $ATBeatCfoApril = new AbelsonTaylor2016BeatCFOComplianceView('2016-04-01', '2016-04-30', 414, 110);
        $ATBeatCfoApril->setName('beat_cfo_april');
        $ATBeatCfoApril->setMaximumNumberOfPoints(5);
        $ATBeatCfoApril->addAttributes(array(
            'points_per_activity' =>
                '5',

            'requirement' =>
                '5 point each month you exceed the number of steps logged by CFO Keith Stenlund'
        ));
        $ATBeatCfoApril->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $ATBeatCfoApril->addLink(new Link('Leaderboards', '/content/ucan-fitbit-leaderboards?type=individual'));
        $walkingGroup->addComplianceView($ATBeatCfoApril);


        $ATBeatCfoAug = new AbelsonTaylor2016BeatCFOComplianceView('2016-08-01', '2016-08-31', 414, 110);
        $ATBeatCfoAug->setName('beat_cfo_aug');
        $ATBeatCfoAug->setMaximumNumberOfPoints(5);
        $ATBeatCfoAug->addAttributes(array(
            'points_per_activity' =>
                '5',

            'requirement' =>
                '5 point each month you exceed the number of steps logged by CFO Keith Stenlund'
        ));
        $walkingGroup->addComplianceView($ATBeatCfoAug);

        $ATBeatCfoDecember = new AbelsonTaylor2016BeatCFOComplianceView('2016-12-01', '2016-12-31', 414, 110);
        $ATBeatCfoDecember->setName('beat_cfo_december');
        $ATBeatCfoDecember->setMaximumNumberOfPoints(5);
        $ATBeatCfoDecember->addAttributes(array(
            'points_per_activity' =>
                '5',

            'requirement' =>
                '5 point each month you exceed the number of steps logged by CFO Keith Stenlund'
        ));
        $walkingGroup->addComplianceView($ATBeatCfoDecember);

        $this->addComplianceViewGroup($walkingGroup);

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
            $printer = new AbelsonTaylor2016ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    const ROLLING_START_DATE_ACTIVITY_DATE = '2012-08-15';
}


class AbelsonTaylor2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);

        $this->tableHeaders['points_possible'] = 'Maximum Possible Points';
        $this->screeningLinkArea = '<br/><br/>
      Green Range = 10 pts <br/>
      Yellow Range = 5 pts<br/><br/>
      <a href="/content/1094#2aKBHM"> Click here for more info &amp; tips to improve.</a><br/><br/>
      <em>Points appear 5-10 days after screening.</em>
    ';

        $this->addStatusCallbackColumn('Requirement', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            return $view->getAttribute('requirement');
        });

        $this->addStatusCallbackColumn('Points Per Activity', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();
            return $view->getAttribute('points_per_activity');
        });


    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(false);
        $this->setScreeningResultsLink(new Link('Have These Screening Results in the Ideal Zone:', '/content/1094#2aKBHM'));

        ?>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $completeHraStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');

        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness_programs');
        $wellnessGroup = $wellnessGroupStatus->getComplianceViewGroup();

        $spouseHraScrStatus = $wellnessGroupStatus->getComplianceViewStatus('spouse_hra_screening');
        $bloodPressureStatus = $wellnessGroupStatus->getComplianceViewStatus('blood_pressure');
        $cholesterolStatus = $wellnessGroupStatus->getComplianceViewStatus('cholesterol');
        $glucoseStatus = $wellnessGroupStatus->getComplianceViewStatus('glucose');
        $improvedBiometricStatus = $wellnessGroupStatus->getComplianceViewStatus('improved_biometric');
        $annualPhysicalExam = $wellnessGroupStatus->getComplianceViewStatus('annual_physical_exam');

        $wellnessFairStatus = $wellnessGroupStatus->getComplianceViewStatus('wellness_fair');
        $wellnessKickoffStatus = $wellnessGroupStatus->getComplianceViewStatus('kick_off');
        $preventiveExams = $wellnessGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $fluShot = $wellnessGroupStatus->getComplianceViewStatus('flu_shot');
        $elearning = $wellnessGroupStatus->getComplianceViewStatus('elearning');
        $moduleCourse = $wellnessGroupStatus->getComplianceViewStatus('module_course');
        $lnl = $wellnessGroupStatus->getComplianceViewStatus('lunch_and_learn');
        $compassServices = $wellnessGroupStatus->getComplianceViewStatus('compass_services');


        $abelsonTaylorWellnessEvents = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events');

        $walkingGroupStatus = $status->getComplianceViewGroupStatus('walking_programs');
        $walkingGroup = $walkingGroupStatus->getComplianceViewGroup();

        $walkingStepOneStatus = $walkingGroupStatus->getComplianceViewStatus('step_one');
        $walkingStepTwoStatus = $walkingGroupStatus->getComplianceViewStatus('step_two');
        $walkingStepThreeStatus = $walkingGroupStatus->getComplianceViewStatus('step_three');

        $beatCfoAprilStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_april');
        $beatCfoAugStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_aug');
        $beatCfoDecemberStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_december');

        if(date('M') == 'Dec') {
            $beatCfoStatus = $beatCfoDecemberStatus;
        } elseif (date('M') == 'Aug') {
            $beatCfoStatus = $beatCfoAugStatus;
        } else {
            $beatCfoStatus = $beatCfoAprilStatus;
        }

        ?>
        <style type="text/css">
            .pageHeading {
                display:none;
            }

            #altPageHeading {
                font-weight:bold;
                margin-bottom:20px;
                text-align:center;
            }

            .phipTable .headerRow {
                background-color:#26B000;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
                height:46px;
            }

            #legend td {
                padding:8px !important;
            }

            .legendEntry {
                width:auto;
                float:right;
                display:inline-block;
                padding:0 8px;
            }

            #legendText {
                text-align:center;
                background-color:#FFF;
                font-weight:normal;
                color:#434343;
                font-size:12pt;
                font-weight:bold;
                float:left;
            }

            .phipTable {
                width:100%;
                border-collapse:collapse;
                font-size:8pt;
            }

            .phipTable .links a {
                display:inline-block;
                margin:0 3px;
            }

            .phipTable th, .phipTable td {
                border:1px solid #000000;
                padding:2px;
            }

            .light {
                width:25px;
            }

            .center {
                text-align:center;
            }

            .section {
                height:16px;
                color: white;
                background-color:#436EEE;
            }

            .requirement {
                width: 350px;
            }

            #programTable {
                border-collapse: collapse;
                margin:0 auto;
            }

            #programTable tr th, #programTable tr td{
                border:1px solid #26B000;
            }

        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned
        </script>
        <!-- Text atop report card-->
        <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The AbelsonTaylor Comprehensive Wellness Program</p>
        <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>
            Your health and your family’s health is important not only to you but to Abelson Taylor as well! AT is expanding and
            enhancing our wellness program for employees and their families and want to reward you for participating. We have
            partnered with HMI Health and Axion to implement our Wellness Program. The wellness program provides you
            with fun, robust programming options geared towards specific areas of your health that need improvement. This
            Wellness Program is your way to better, healthier living.
        </p>

        <p style="font-weight: bolder; font-size: 12pt; text-align: center">HOW DOES THE PROGRAM WORK?</p>
        <p>
            <span style="font-weight:bolder;">Employees that complete the 2016 Health Screening and HRA are eligible to participate.</span>
            Participation in the program will earn wellness points that will be tracked in the table below. Rewards will be based on points
            earned between 3/1/2016 and 2/28/2017 so plan your point accumulation accordingly.
        </p>

        <div>
            <table id="programTable">
                <tr style="background-color:#008787">
                    <th>Status Level</th>
                    <th>Participation</th>
                    <th>Points</th>
                    <th>Reward</th>
                </tr>
                <tr>
                    <td>Bronze</td>
                    <td>Completes annual physical or onsite wellness screening & Health Questionnaire</td>
                    <td>Requirement for Program Participation</td>
                    <td>$20 monthly premium discount (Discount good for maximum of 12 months).</td>
                </tr>
                <tr>
                    <td>BONUS</td>
                    <td>Visit your personal physician to follow-up on your wellness screening and complete your
                        annual exam.</td>
                    <td>EARN 20 POINTS</td>
                    <td>Receive a Fitbit Zip <strong>OR</strong> a credit of $55.00 towards the purchase of a
                        NEW fitness tracking device.**</td>
                </tr>
                <tr>
                    <td>Silver</td>
                    <td>Complete Bronze level and accumulate 50 points</td>
                    <td>50 Total points</td>
                    <td>$100 HSA (Health Savings Account) deposit OR one time premium discount</td>
                </tr>
                <tr>
                    <td>Gold</td>
                    <td>Complete Bronze and Silver levels and accumulate 50 additional points</td>
                    <td>100 Total Points</td>
                    <td>Additional $150 HSA deposit OR one time premium discount.</td>
                </tr>
                <tr>
                    <td>Platinum</td>
                    <td>Complete Bronze, Sliver & Gold levels and accumulate 50 additional points</td>
                    <td>150 Total points</td>
                    <td>$200 HSA (Health Savings Account) deposit OR one time premium discount</td>
                </tr>
            </table>
            <p style="color: red; text-align: right;">** Employee must provide receipt as proof of purchase to receive reimbursement.</p>
        </div><br />
        <p>	Rewards will be paid out on a monthly basis. At the end of each month, a report will be run from HMI and if an
            employee reaches silver or gold status then a deposit into an employee’s HSA (if employees are on a high
            deductible plan) or a one-time premium discount will occur (if the employees are not on a high deductible plan
            or on an HMO plan). Deposits or discounts will take place on the 15th of each month following achievement of
            Silver, Gold or Platinum status.</p>

        <table class="phipTable">
            <tbody>
            <tr><th colspan="6" style="height:36px; text-align:center; color: white; background-color:#436EEE; font-size:11pt">2016 Wellness Rewards Program</th></tr>
            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Required Activities</span> A & B are required for participation in program</th></tr>
            <tr class="headerRow headerRow-core">
                <th colspan="2" class="center">Requirement</th>
                <th class="center">Status</th>
                <th colspan="3" class="center">Tracking Method</th>
            </tr>
            <tr class="view-complete_hra">
                <td colspan="2">
                    <a href="<?php echo $completeHraStatus->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>A</strong>. <?php echo $completeHraStatus->getComplianceView()->getAttribute('requirement') ?></a>
                </td>
                <td class="center">
                    <img src="<?php echo $completeHraStatus->getLight(); ?>" class="light"/>
                </td>
                <td colspan="3" class="center">
                    <?php foreach($completeHraStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr class="view-complete_screening">
                <td colspan="2">
                    <a href="<?php echo $completeScreeningStatus->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>B</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?></a>
                </td>
                <td class="center">
                    <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
                </td>
                <td colspan="3" class="center">
                    <?php foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Point Earning Wellness Activities</span> A-L are additional activities to help you year points</th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>
                <td class="center"># Points Earned</td>
                <td class="center">Max Points</td>
                <td class="center">Tracking Method</td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $spouseHraScrStatus->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>A</strong>. <?php echo $spouseHraScrStatus->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Eligible Spouse, Domestic Partner and/or eligible Dependents over age 18 complete the Wellness Screening AND Health Power Profile Questionnaire</td>
                <td class="center"><?php echo $spouseHraScrStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $spouseHraScrStatus->getPoints() ?></td>
                <td class="center"><?php echo $spouseHraScrStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    <?php foreach($spouseHraScrStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td rowspan="3">
                    <a href="<?php echo $bloodPressureStatus->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>B</strong>. Biometric Bonus Points</a>
                </td>
                <td class="requirement">Blood Pressure < 130/85</td>
                <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getPoints() ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center" rowspan="4">
                    <?php foreach($bloodPressureStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td class="requirement">Total Cholesterol < 200 mg/dL</td>
                <td class="center"><?php echo $cholesterolStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $cholesterolStatus->getPoints() ?></td>
                <td class="center"><?php echo $cholesterolStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement">Fasting Glucose < 100 mg/dL</td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $glucoseStatus->getPoints() ?></td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $improvedBiometricStatus->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>C</strong>. <?php echo $improvedBiometricStatus->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Submit proof of a follow-up screening with improved biometric results within range for values
                    listed above.</td>
                <td class="center"><?php echo $improvedBiometricStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $improvedBiometricStatus->getPoints() ?></td>
                <td class="center"><?php echo $improvedBiometricStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $annualPhysicalExam->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>D</strong>. <?php echo $annualPhysicalExam->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Visit your personal physician to follow-up on your wellness screening and complete your annual exam</td>
                <td class="center"><?php echo $annualPhysicalExam->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $annualPhysicalExam->getPoints() ?></td>
                <td class="center"><?php echo $annualPhysicalExam->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    <?php foreach($annualPhysicalExam->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
            </td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $wellnessFairStatus->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>E</strong>. <?php echo $wellnessFairStatus->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Attend AT Wellness Fair & Complete Survey</td>
                <td class="center"><?php echo $wellnessFairStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $wellnessFairStatus->getPoints() ?></td>
                <td class="center"><?php echo $wellnessFairStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    <?php foreach($wellnessFairStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $wellnessKickoffStatus->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>F</strong>. <?php echo $wellnessKickoffStatus->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Attend the wellness kick off meeting</td>
                <td class="center"><?php echo $wellnessKickoffStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getPoints() ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">Sign in at Event</td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $preventiveExams->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>G</strong>. <?php echo $preventiveExams->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Receive a preventative service such as mammogram, prostate exam, immunization, vaccine, eye or dental exam, colonoscopy, etc.
                    See attached wellness guides or check with your personal physician for necessary tests.</td>
                <td class="center"><?php echo $preventiveExams->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $preventiveExams->getPoints() ?></td>
                <td class="center"><?php echo $preventiveExams->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    <?php foreach($preventiveExams->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $fluShot->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>H</strong>. <?php echo $fluShot->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Receive a flu shot for yourself,Eligible Spouse, Domestic Partner, and/or Dependents over age 18</td>
                <td class="center"><?php echo $fluShot->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $fluShot->getPoints() ?></td>
                <td class="center"><?php echo $fluShot->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    <?php foreach($fluShot->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo $elearning->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>I</strong>. <?php echo $elearning->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Complete an online eLearning course</td>
                <td class="center"><?php echo $elearning->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $elearning->getPoints() ?></td>
                <td class="center"><?php echo $elearning->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    <?php foreach($elearning->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <a href="<?php echo $moduleCourse->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>J</strong>. <?php echo $moduleCourse->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">"Complete a 12 module course through BCBS WellOnTraget</td>
                <td class="center"><?php echo $moduleCourse->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $moduleCourse->getPoints() ?></td>
                <td class="center"><?php echo $moduleCourse->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    <?php foreach($moduleCourse->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <a href="<?php echo $lnl->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>K</strong>. <?php echo $lnl->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Attend a Health and Wellness Lunch and Learn (open enrollment meetings, other wellness meetings)</td>
                <td class="center"><?php echo $lnl->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $lnl->getPoints() ?></td>
                <td class="center"><?php echo $lnl->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    Sign in at Event
                </td>
            </tr>

            <tr>
                <td>
                    <a href="<?php echo $compassServices->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>L</strong>. <?php echo $compassServices->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement">Participate in a Compass Educational Event or engage with the Health Pro Consultant</td>
                <td class="center"><?php echo $compassServices->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $compassServices->getPoints() ?></td>
                <td class="center"><?php echo $compassServices->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">Admin will enter</td>
            </tr>

            <tr>
                <td>
                    <a href="<?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getAttribute('report_name_link')?>">
                        <strong>M</strong>. <?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getReportName() ?></a>
                </td>
                <td class="requirement"><?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $abelsonTaylorWellnessEvents->getPoints() ?></td>
                <td class="center"><?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">Admin will enter</td>
            </tr>

            <tr>
                <th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">WALKING PROGRAM</span><br />
                    This program is designed to help you become physically active each day. Participants can track steps
                    in one of the two ways listed below. Points will be awarded at the end of each month based on
                    the average steps logged during the period.
                </th></tr>

            <tr>
                <td rowspan="3">
                    &nbsp;Individual activity/walking
                    &nbsp;Program: <br /><br />
                    &nbsp;Points will be awarded at the end of each month
                    based on the average steps logged during the period
                </td>
                <td class="requirement">Walk an average of 7,500 steps/day </td>
                <td class="center"><?php echo $walkingStepOneStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo date('Y-m-d') > '2015-08-31' ? $walkingStepOneStatus->getPoints() : '0' ?></td>
                <td class="center"><?php echo $walkingStepOneStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center" rowspan="3">
                    <?php foreach($walkingStepOneStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 12,000 steps/day </td>
                <td class="center"><?php echo $walkingStepTwoStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo date('Y-m-d') > '2015-08-31' ? $walkingStepTwoStatus->getPoints() : '0' ?></td>
                <td class="center"><?php echo $walkingStepTwoStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 15,000 steps/day </td>
                <td class="center"><?php echo $walkingStepThreeStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo date('Y-m-d') > '2015-08-31' ? $walkingStepThreeStatus->getPoints() : '0'?></td>
                <td class="center"><?php echo $walkingStepThreeStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>

            <tr>
                <td>
                    &nbsp;Beat the CFO Challenge<br /><br />
                    &nbsp;April 2016 <br />
                    &nbsp;August 2016 <br />
                    &nbsp;December 2016
                </td>
                <td class="requirement">5 point each month you exceed the number of steps logged by CFO Keith Stenlund</td>
                <td class="center"><?php echo $beatCfoAprilStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $beatCfoAprilStatus->getPoints()  + $beatCfoAugStatus->getPoints() + $beatCfoDecemberStatus->getPoints()?></td>
                <td class="center">15</td>
                <td class="center">
                    <?php foreach($beatCfoAprilStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }
                    ?>
                    <br/><br/><strong>This Month</strong>
                    <br/><br/><div style="text-align:center; ">My Steps:
                        <?php echo $beatCfoStatus->getAttribute('user_month_total') ?>
                        <br />Keith's Steps
                        <?php echo $beatCfoStatus->getAttribute('cfo_month_total') ?>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Total Points</strong>
                </td>
                <td class="requirement"></td>
                <td class="center"></td>
                <td class="center"><?php echo $wellnessGroupStatus->getPoints() + $walkingGroupStatus->getPoints() ?></td>
                <td class="center"><?php echo $wellnessGroup->getMaximumNumberOfPoints() + $walkingGroup->getMaximumNumberOfPoints() ?></td>
                <td class="center"></td>
            </tr>
            </tbody>
        </table>

        <?php
    }


    public $showUserNameInLegend = true;
}
