<?php

use hpn\steel\query\SelectQuery;

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/jawbone/lib/model/jawboneApi.php';
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/standalone/lib/moves/lib/model/movesApi.php';

class AbelsonTaylor2017IndividualActivityStepsComplianceView extends DateBasedComplianceView
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

        $quarterlyRanges = array(
            'quarter1' => array('2017-03-01', '2017-05-31'),
            'quarter2' => array('2017-06-01', '2017-08-31'),
            'quarter3' => array('2017-09-01', '2017-11-30'),
            'quarter4' => array('2017-12-01', '2018-02-28')
        );
        $quarter1Points = 0;
        $quarter2Points = 0;
        $quarter3Points = 0;
        $quarter4Points = 0;

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

                    foreach($quarterlyRanges as $quarterName => $dateRange) {
                        $startDate = $dateRange[0];
                        $endDate =  $dateRange[1];
                        if($startDate <= $month['start_count'] && $month['start_count'] <= $endDate) {
                            if($quarterName == 'quarter1') {
                                $quarter1Points += $this->points;
                            } elseif ($quarterName == 'quarter2') {
                                $quarter2Points += $this->points;
                            } elseif ($quarterName == 'quarter3') {
                                $quarter3Points += $this->points;
                            } elseif ($quarterName == 'quarter4') {
                                $quarter4Points += $this->points;
                            }
                        }
                    }
                }
            }
        }

//        echo $totalSteps;

        $status = new ComplianceViewStatus($this, null, $totalPoints);

        $status->setAttribute('quarter_1_points', $quarter1Points);
        $status->setAttribute('quarter_2_points', $quarter2Points);
        $status->setAttribute('quarter_3_points', $quarter3Points);
        $status->setAttribute('quarter_4_points', $quarter4Points);
        $status->setAttribute('total_steps', $totalSteps);

        return $status;
    }

    private function getMonths()
    {
        return array(
            'mar'   => array(
                'start_date' => '2017-03-01',
                'end_date'   => '2017-03-31',
                'start_count'=> '2017-03-01'
            ),
            'apri'     => array(
                'start_date' => '2017-04-01',
                'end_date'   => '2017-04-30',
                'start_count'=> '2017-04-01'
            ),
            'may'        => array(
                'start_date' => '2017-05-01',
                'end_date'   => '2017-05-31',
                'start_count'=> '2017-05-01'
            ),
            'june'        => array(
                'start_date' => '2017-06-01',
                'end_date'   => '2017-06-30',
                'start_count'=> '2017-06-01'
            ),
            'july'      => array(
                'start_date' => '2017-07-01',
                'end_date'   => '2017-07-31',
                'start_count'=> '2017-07-01'
            ),
            'aug'       => array(
                'start_date' => '2017-08-01',
                'end_date'   => '2017-08-31',
                'start_count'=> '2017-08-01'
            ),
            'sep'       => array(
                'start_date' => '2017-09-01',
                'end_date'   => '2017-09-30',
                'start_count'=> '2017-09-01'
            ),
            'oct'       => array(
                'start_date' => '2017-10-01',
                'end_date'   => '2017-10-31',
                'start_count'=> '2017-10-01'
            ),
            'nov'      => array(
                'start_date' => '2017-11-01',
                'end_date'   => '2017-11-30',
                'start_count'=> '2017-11-01'
            ),
            'dec'       => array(
                'start_date' => '2017-12-01',
                'end_date'   => '2017-12-31',
                'start_count'=> '2017-12-01'
            ),
            'jan'       => array(
                'start_date' => '2018-01-01',
                'end_date'   => '2018-01-31',
                'start_count'=> '2018-01-01'
            ),
            'feb'       => array(
                'start_date' => '2018-02-01',
                'end_date'   => '2018-02-28',
                'start_count'=> '2018-02-01'
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

class AbelsonTaylor2017BeatCFOComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $activityId, $questionId, $month)
    {
        $this->setDateRange($startDate, $endDate);
        $this->activityId = $activityId;
        $this->questionId = $questionId;
        $this->month       = $month;
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
            $this->getStartDate('Y-m-d'),
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

        $viewStatus = new ComplianceViewStatus($this, null, $points);

        $viewStatus->addAttributes(array(
            'user_month_total' =>
                $this->totalSteps($data['dates'], $this->month),

            'cfo_month_total'  =>
                $this->totalSteps($cfoData['dates'], $this->month)
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

class AbelsonTaylor2017ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('03/01/2017 - 05/31/2017 - Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_1_points');
        });

        $printer->addStatusFieldCallback('06/01/2017 - 08/31/2017 - Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_2_points');
        });

        $printer->addStatusFieldCallback('09/01/2017 - 11/30/2017 - Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_3_points');
        });

        $printer->addStatusFieldCallback('12/01/2017 - 02/28/2018 - Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_4_points');
        });

        $printer->addStatusFieldCallback('07/01/2017 - 07/31/2017 - Steps', function(ComplianceProgramStatus $status) {
            $viewStatus = $status->getComplianceViewStatus('beat_cfo_july');

            return $viewStatus->getAttribute('user_month_total');
        });

        $printer->addStatusFieldCallback('10/01/2017-10/31/2017 - Steps', function(ComplianceProgramStatus $status) {
            $viewStatus = $status->getComplianceViewStatus('beat_cfo_oct');

            return $viewStatus->getAttribute('user_month_total');
        });

        $printer->addStatusFieldCallback('01/01/2018-01/31/2018 - Steps', function(ComplianceProgramStatus $status) {
            $viewStatus = $status->getComplianceViewStatus('beat_cfo_jan');

            return $viewStatus->getAttribute('user_month_total');
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

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Power Profile Questionnaire');
        $hraView->setName('complete_hra');
        $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $hraView->setAttribute('requirement', 'Employee completes the Health Power Profile Questionnaire');
        $hraView->setAttribute('points_per_activity', '10');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setUseOverrideCreatedDate(true);
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Employee participates in Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $screeningView->setAttribute('requirement', 'Employee participates in Wellness Screening');
        $screeningView->setAttribute('points_per_activity', '10');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/wms2-appointment-center'));
        $screeningView->addLink(new Link('MD Physician Form', '/resources/8974/Abelson Taylor MD Form 2017.pdf'));
        $screeningView->setUseOverrideCreatedDate(true);
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
        $spouseHraScr->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($spouseHraScr);

        $waistView = new ComplyWithWaistScreeningTestComplianceView($programStart, $programEnd);
        $waistView->setReportName('Waist Circumference');
        $waistView->setName('waist');
        $waistView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $waistView->overrideTestRowData(null, null, 39.99, null, 'M');
        $waistView->overrideTestRowData(null, null, 34.99, null, 'F');
        $waistView->setAttribute('points_per_activity', 5);
        $waistView->setAttribute('goal', '≤40 Men / ≤35 Women');
        $waistView->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $waistView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($waistView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $bloodPressureView->setAttribute('points_per_activity', 5);
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, 89);
        $bloodPressureView->setMergeScreenings(true);
        $bloodPressureView->setPostEvaluateCallback($this->checkImprovement(array('systolic', 'diastolic')));
        $bloodPressureView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($bloodPressureView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglyceridesView->setReportName('Triglycerides');
        $triglyceridesView->setName('triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setAttribute('points_per_activity', 5);
        $triglyceridesView->setAttribute('goal', '<150');
        $triglyceridesView->setPostEvaluateCallback($this->checkImprovement(array('triglycerides')));
        $triglyceridesView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($triglyceridesView);

        $hdlView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlView->setReportName('HDL Cholesterol');
        $hdlView->setName('hdl');
        $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $hdlView->overrideTestRowData(null, 40.01, null, null, 'M');
        $hdlView->overrideTestRowData(null, 50.01, null, null, 'F');
        $hdlView->setAttribute('points_per_activity', 5);
        $hdlView->setAttribute('goal', '≥ 40 Men /  ≥50 Women');
        $hdlView->setPostEvaluateCallback($this->checkImprovement(array('hdl'), 'increase'));
        $hdlView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($hdlView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('Fasting Glucose');
        $glucoseView->setName('glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $glucoseView->setAttribute('points_per_activity', 5);
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setPostEvaluateCallback($this->checkImprovement(array('glucose')));
        $glucoseView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($glucoseView);


        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 290, 15);
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $annualPhysicalExamView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Get a physical exam with appropriate tests for your age and gender as recommended by your physician.');
        $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening follow up');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->setAttribute('points_per_activity', '20');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link('Verification Form <br />', '/resources/8971/AT_PreventiveServices Cert2017.pdf'));
        $annualPhysicalExamView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $annualPhysicalExamView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($annualPhysicalExamView);

        $wellnessFair = new PlaceHolderComplianceView(null, 0);
        $wellnessFair->setMaximumNumberOfPoints(10);
        $wellnessFair->setReportName('Wellness Fair Attendance');
        $wellnessFair->setName('wellness_fair');
        $wellnessFair->setAttribute('points_per_activity', 10);
        $wellnessFair->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($wellnessFair);

        $wellnessKickOff = new PlaceHolderComplianceView(null, 0);
        $wellnessKickOff->setReportName('Wellness Kick off');
        $wellnessKickOff->setName('kick_off');
        $wellnessKickOff->setAttribute('points_per_activity', 10);
        $wellnessKickOff->setMaximumNumberOfPoints(10);
        $wellnessKickOff->emptyLinks();
        $wellnessKickOff->addLink(new FakeLink('Sign in at Event', '/content/events'));
        $wellnessKickOff->setUseOverrideCreatedDate(true);
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
        $preventiveExamsView->setAttribute('requirement', 'Receive a preventive service such as mammogram, prostate exam, routine dental exam, etc.');
        $preventiveExamsView->setAttribute('points_per_activity', '10');
        $preventiveExamsView->emptyLinks();
        $preventiveExamsView->addLink(new Link('Verification Form <br />', '/resources/8971/AT_PreventiveServices Cert2017.pdf'));
        $preventiveExamsView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $preventiveExamsView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($preventiveExamsView);

        $flushotView = new FluVaccineActivityComplianceView($programStart, $programEnd);
        $flushotView->setReportName('Flu Shot');
        $flushotView->setName('flu_shot');
        $flushotView->setMaximumNumberOfPoints(30);
        $flushotView->setAttribute('requirement', 'Receive a flu Shot');
        $flushotView->setAttribute('points_per_activity', '10');
        $flushotView->emptyLinks();
        $flushotView->addLink(new Link('Verification Form <br />', '/resources/8971/AT_PreventiveServices Cert2017.pdf'));
        $flushotView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $flushotView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($flushotView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('E-learning');
        $elearn->setName('elearning');
        $elearn->setAttribute('requirement', 'Complete an online e-learning course');
        $elearn->setAttribute('points_per_activity', '5');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(40);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=completed_compliance'));
        $elearn->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($elearn);

        $moduleCourseView = new PlaceHolderComplianceView(null, 0);
        $moduleCourseView->setReportName('Smoking Cessation  <br /> &nbsp;&nbsp; Stress Management  <br /> &nbsp;&nbsp; Nutrition Management');
        $moduleCourseView->setName('module_course');
        $moduleCourseView->setAttribute('requirement', 'Complete a 12 module course through BCBS WellOnTraget');
        $moduleCourseView->setAttribute('points_per_activity', '15');
        $moduleCourseView->emptyLinks();
        $moduleCourseView->addLink((new Link('Click Here', 'http://wellontarget.com')));
        $moduleCourseView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $moduleCourseView->setMaximumNumberOfPoints(45);
        $moduleCourseView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($moduleCourseView);

        $educationVideos = new PlaceHolderComplianceView(null, 0);
        $educationVideos->setReportName('Benefit Education Videos');
        $educationVideos->setName('education_videos');
        $educationVideos->setAttribute('points_per_activity', '5');
        $educationVideos->setMaximumNumberOfPoints(20);
        $educationVideos->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($educationVideos);

        $compassServices = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 330, 10);
        $compassServices->setReportName('Compass Services');
        $compassServices->setName('compass_services');
        $compassServices->setMaximumNumberOfPoints(10);
        $compassServices->setAttribute('requirement', 'Engage with the Health Pro Consultant');
        $compassServices->setAttribute('points_per_activity', '5');
        $compassServices->emptyLinks();
        $compassServices->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($compassServices);

        $abelsonTaylorWellnessEventsView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $abelsonTaylorWellnessEventsView->setReportName('Abelson Taylor Wellness Events');
        $abelsonTaylorWellnessEventsView->setName('abelson_taylor_wellness_events');
        $abelsonTaylorWellnessEventsView->setMaximumNumberOfPoints(150);
        $abelsonTaylorWellnessEventsView->setAttribute('requirement', 'Participate in a designated wellness activity or lunch and learn');
        $abelsonTaylorWellnessEventsView->setAttribute('points_per_activity', '10');
        $abelsonTaylorWellnessEventsView->emptyLinks();
        $abelsonTaylorWellnessEventsView->addLink(new FakeLink('Admin will Enter', '#'));
        $abelsonTaylorWellnessEventsView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($abelsonTaylorWellnessEventsView);

        $wellnessGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($wellnessGroup);

        $walkingGroup = new ComplianceViewGroup('walking_programs', 'Program');
        $walkingGroup->setPointsRequiredForCompliance(0);

        $stepOne = new AbelsonTaylor2017IndividualActivityStepsComplianceView($programStart, $programEnd, 7500, 2);
        $stepOne->setReportName('Individual Walking Challenge');
        $stepOne->setName('step_one');
        $stepOne->setAttribute('points_per_activity', '2 points per month');
        $stepOne->setMaximumNumberOfPoints(24);
        $stepOne->addLink(new Link('Fitbit Sync <br /><br />', '/content/ucan-fitbit-individual'));
        $stepOne->addLink(new Link('Moves Sync <br /><br />', '/standalone/moves'));
        $stepOne->addLink(new Link('Jawbone Sync <br />', '/standalone/jawbone'));
        $stepOne->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($stepOne);

        $stepTwo = new AbelsonTaylor2017IndividualActivityStepsComplianceView($programStart, $programEnd, 12000, 2);
        $stepTwo->setReportName('Individual Walking Challenge');
        $stepTwo->setName('step_two');
        $stepTwo->setAttribute('points_per_activity', 'Additional 2 points per month');
        $stepTwo->setMaximumNumberOfPoints(24);
        $stepTwo->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($stepTwo);

        $stepThree = new AbelsonTaylor2017IndividualActivityStepsComplianceView($programStart, $programEnd, 15000, 2);
        $stepThree->setReportName('Individual Walking Challenge');
        $stepThree->setName('step_three');
        $stepThree->setAttribute('points_per_activity', 'Additional 2 points per month');
        $stepThree->setMaximumNumberOfPoints(24);
        $stepThree->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($stepThree);

        $ATBeatCfoJuly = new AbelsonTaylor2017BeatCFOComplianceView('2017-07-01', '2017-07-31', 414, 110, '2017-07');
        $ATBeatCfoJuly->setName('beat_cfo_july');
        $ATBeatCfoJuly->setMaximumNumberOfPoints(15);
        $ATBeatCfoJuly->addAttributes(array(
            'points_per_activity' =>
                '15',

            'requirement' =>
                '15 points each month you exceed the number of steps logged by CFO Keith Stenlund'
        ));
        $ATBeatCfoJuly->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $ATBeatCfoJuly->addLink(new Link('Leaderboards', '/content/ucan-fitbit-leaderboards?type=individual'));
        $ATBeatCfoJuly->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($ATBeatCfoJuly);


        $ATBeatCfoOct = new AbelsonTaylor2017BeatCFOComplianceView('2017-10-01', '2017-10-31', 414, 110, '2017-10');
        $ATBeatCfoOct->setName('beat_cfo_oct');
        $ATBeatCfoOct->setMaximumNumberOfPoints(15);
        $ATBeatCfoOct->addAttributes(array(
            'points_per_activity' =>
                '5',

            'requirement' =>
                '5 point each month you exceed the number of steps logged by CFO Keith Stenlund'
        ));
        $ATBeatCfoOct->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($ATBeatCfoOct);

        $ATBeatCfoJanuary = new AbelsonTaylor2017BeatCFOComplianceView('2018-01-01', '2018-01-31', 414, 110, '2018-01');
        $ATBeatCfoJanuary->setName('beat_cfo_jan');
        $ATBeatCfoJanuary->setMaximumNumberOfPoints(15);
        $ATBeatCfoJanuary->addAttributes(array(
            'points_per_activity' =>
                '5',

            'requirement' =>
                '5 points each month you exceed the number of steps logged by CFO Keith Stenlund'
        ));
        $ATBeatCfoJanuary->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($ATBeatCfoJanuary);

        $this->addComplianceViewGroup($walkingGroup);

    }

    protected function checkImprovement(array $tests, $calculationMethod = 'decrease') {
        $programStart = new \DateTime('@'.$this->getStartDate());
        $programEnd = new \DateTime('@'.$this->getEndDate());

        $lastStart = new \DateTime('2016-03-01');
        $lastEnd = new \DateTime('2017-02-28');

        return function(ComplianceViewStatus $status, User $user) use ($tests, $programStart, $programEnd, $lastStart, $lastEnd, $calculationMethod) {
            static $cache = null;

            if ($cache === null || $cache['user_id'] != $user->id) {
                $cache = array(
                    'user_id' => $user->id,
                    'this' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $programStart, $programEnd),
                    'last' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $lastStart, $lastEnd)
                );
            }

            if (count($tests) > 0 && $cache['this'] && $cache['last']) {
                $isImproved = true;

                foreach($tests as $test) {
                    $lastVal = isset($cache['last'][0][$test]) ? (float) $cache['last'][0][$test] : null;
                    $thisVal = isset($cache['this'][0][$test]) ? (float) $cache['this'][0][$test] : null;

                    if($calculationMethod == 'decrease') {
                        if (!$thisVal || !$lastVal || $lastVal * 0.90 < $thisVal) {
                            $isImproved = false;

                            break;
                        }
                    } else {
                        if (!$thisVal || !$lastVal || $lastVal * 1.10 > $thisVal) {
                            $isImproved = false;

                            break;
                        }
                    }
                }

                if ($isImproved) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        };
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
            $printer = new AbelsonTaylor2017ComplianceProgramReportPrinter();
        }

        return $printer;
    }



    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $quarterlyDateRange = $this->getQuerterlyRanges();
        $walkingProgramViews = array(
            'step_one', 'step_two', 'step_three'
        );

        $quarter1Points = 0;
        $quarter2Points = 0;
        $quarter3Points = 0;
        $quarter4Points = 0;
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();
                if(in_array($view->getName(), $walkingProgramViews)) {
                    $quarter1Points += $viewStatus->getAttribute('quarter_1_points');
                    $quarter2Points += $viewStatus->getAttribute('quarter_2_points');
                    $quarter3Points += $viewStatus->getAttribute('quarter_3_points');
                    $quarter4Points += $viewStatus->getAttribute('quarter_4_points');
                } else {
                    $viewPoints = $viewStatus->getPoints();
                    $pointAdded = false;
                    if($viewPoints > 0) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            $date = false;
                            if(date('m/d/Y', strtotime($viewStatus->getComment())) === $viewStatus->getComment()) {
                                $date = date('Y-m-d', strtotime($viewStatus->getComment()));
                            } elseif(date('Y-m-d', strtotime($viewStatus->getComment())) === $viewStatus->getComment()) {
                                $date = date('Y-m-d', strtotime($viewStatus->getComment()));
                            } elseif(date('m/d/Y', strtotime($viewStatus->getAttribute('original_comment'))) === $viewStatus->getAttribute('original_comment')) {
                                $date = date('Y-m-d', strtotime($viewStatus->getAttribute('original_comment')));
                            } elseif(date('m/d/Y', strtotime($viewStatus->getAttribute('date'))) === $viewStatus->getAttribute('date')) {
                                $date = date('Y-m-d', strtotime($viewStatus->getAttribute('date')));
                            } elseif(date('Y-m-d H:i:s', strtotime($viewStatus->getAttribute('override_created_date'))) == $viewStatus->getAttribute('override_created_date')) {
                                $date = date('Y-m-d', strtotime($viewStatus->getAttribute('override_created_date')));
                            }

                            if($date && $startDate <= $date && $date <= $endDate && !$pointAdded) {
                                if($quarterName == 'quarter1') {
                                    $quarter1Points += $viewPoints;
                                } elseif ($quarterName == 'quarter2') {
                                    $quarter2Points += $viewPoints;
                                } elseif ($quarterName == 'quarter3') {
                                    $quarter3Points += $viewPoints;
                                } elseif ($quarterName == 'quarter4') {
                                    $quarter4Points += $viewPoints;
                                }
                                $pointAdded = true;

//                                echo $viewStatus->getComplianceView()->getName().'-'.$quarterName.'-'.$viewPoints.'<br />';
                            }

                        }

                        if(!$pointAdded) {
                            $quarter1Points += $viewPoints;
//                            echo 'not found '.$viewStatus->getComplianceView()->getName().'-'.$viewPoints.'<br />';
                        }

                    }
                }
            }
        }


        $status->setAttribute('quarter_1_points', $quarter1Points);
        $status->setAttribute('quarter_2_points', $quarter2Points);
        $status->setAttribute('quarter_3_points', $quarter3Points);
        $status->setAttribute('quarter_4_points', $quarter4Points);
    }


    protected function getQuerterlyRanges()
    {
        $ranges = array(
            'quarter1' => array('2017-03-01', '2017-05-31'),
            'quarter2' => array('2017-06-01', '2017-08-31'),
            'quarter3' => array('2017-09-01', '2017-11-30'),
            'quarter4' => array('2017-12-01', '2018-02-28')
        );

        return $ranges;
    }

}


class AbelsonTaylor2017ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
        $waistStatus = $wellnessGroupStatus->getComplianceViewStatus('waist');
        $bloodPressureStatus = $wellnessGroupStatus->getComplianceViewStatus('blood_pressure');
        $triglyceridesStatus = $wellnessGroupStatus->getComplianceViewStatus('triglycerides');
        $hdlStatus = $wellnessGroupStatus->getComplianceViewStatus('hdl');
        $glucoseStatus = $wellnessGroupStatus->getComplianceViewStatus('glucose');
        $annualPhysicalExam = $wellnessGroupStatus->getComplianceViewStatus('annual_physical_exam');

        $wellnessFairStatus = $wellnessGroupStatus->getComplianceViewStatus('wellness_fair');
        $wellnessKickoffStatus = $wellnessGroupStatus->getComplianceViewStatus('kick_off');
        $preventiveExams = $wellnessGroupStatus->getComplianceViewStatus('do_preventive_exams');
        $fluShot = $wellnessGroupStatus->getComplianceViewStatus('flu_shot');
        $elearning = $wellnessGroupStatus->getComplianceViewStatus('elearning');
        $moduleCourse = $wellnessGroupStatus->getComplianceViewStatus('module_course');
        $educationVideos = $wellnessGroupStatus->getComplianceViewStatus('education_videos');
        $compassServices = $wellnessGroupStatus->getComplianceViewStatus('compass_services');


        $abelsonTaylorWellnessEvents = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events');

        $walkingGroupStatus = $status->getComplianceViewGroupStatus('walking_programs');
        $walkingGroup = $walkingGroupStatus->getComplianceViewGroup();

        $walkingStepOneStatus = $walkingGroupStatus->getComplianceViewStatus('step_one');
        $walkingStepTwoStatus = $walkingGroupStatus->getComplianceViewStatus('step_two');
        $walkingStepThreeStatus = $walkingGroupStatus->getComplianceViewStatus('step_three');

        $beatCfoJulyStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_july');
        $beatCfoOctStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_oct');
        $beatCfoJanuaryStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_jan');

        if(date('Y-m-d') <= '2017-10-01') {
            $beatCfoStatus = $beatCfoJulyStatus;
        } elseif(date('Y-m-d') <= '2018-01-01') {
            $beatCfoStatus = $beatCfoOctStatus;
        } else {
            $beatCfoStatus = $beatCfoJanuaryStatus;
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
                text-align: center;
            }

            #programTable tr th, #programTable tr td{
                border:1px solid #26B000;
                text-align: center;
            }

        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned
        </script>
        <!-- Text atop report card-->
        <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The AbelsonTaylor Comprehensive Wellness Program</p>
        <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>
            Your health and your family’s health is important not only to you but to Abelson Taylor as well! AT is
            expanding and enhancing our wellness program for employees and their families and want to reward you for
            participating. We have partnered with HMI Health and Assurance to implement our Wellness Program.
            The wellness program provides you with fun, robust programming options geared towards specific areas of
            your health that need improvement. This Wellness Program is your way to better, healthier living.
        </p>

        <p style="font-weight: bolder; font-size: 12pt; text-align: center">HOW DOES THE PROGRAM WORK?</p>
        <p>
            <span style="font-weight:bolder;">Employees that complete the 2017 Health Screening and HRA are eligible to participate.</span>
            Participation in the program will earn wellness points that will be tracked in the table below. Rewards will be based on points
            earned between 3/1/2017 and 2/28/2018 so plan your point accumulation accordingly.
        </p>

        <div>
            <table id="programTable">
                <tr style="background-color:#008787">
                    <th style="width: 200px;">Earning Period</th>
                    <th>Participation</th>
                    <th>Reward</th>
                </tr>
                <tr>
                    <td>3/1/2017 - 2/28/2018</td>
                    <td>Completes annual physical or onsite wellness screening & Health Questionnaire</td>
                    <td>$20 monthly premium discount (Discount good for maximum of 12 months).</td>
                </tr>
                <tr>
                    <td>3/1/2017 - 2/28/2018</td>
                    <td>Visit your personal physician to follow up on your wellness screening and complete
                        annual exam.</td>
                    <td>Receive a Fitbit Zip <strong>OR</strong> a credit of $53.00 towards the purchase of a
                        NEW fitness tracking device.</td>
                </tr>
                <tr>
                    <td>Period 1: 3/1/17 - 5/30/17</td>
                    <td>Earn 70 points</td>
                    <td>$110</td>
                </tr>
                <tr>
                    <td>Period 2: 6/1/17 - 8/31/17</td>
                    <td>Earn 30 points</td>
                    <td>$110</td>
                </tr>
                <tr>
                    <td>Period 3: 9/1/17 - 11/30/17</td>
                    <td>Earn 30 points</td>
                    <td>$110</td>
                </tr>
                <tr>
                    <td>Period 4: 12/1/17 - 2/28/18</td>
                    <td>Earn 30 points</td>
                    <td>$110</td>
                </tr>
                <tr>
                    <td>BONUS</td>
                    <td>Successfully complete 4/4 periods</td>
                    <td>$200 wellness reimbursement <strong>OR</strong> HSA contribution</td>
                </tr>
            </table>
            <p style="color: red; text-align: right;">** Employee must provide receipt as proof of purchase to receive reimbursement.</p>
        </div><br />

        <table class="phipTable">
            <tbody>
            <tr><th colspan="6" style="height:36px; text-align:center; color: white; background-color:#436EEE; font-size:11pt">2017 Wellness Rewards Program</th></tr>
            <tr><th colspan="6" class="section"><span style="font-weight:bolder; font-size: 12pt;">Required Activities</span> A & B are required for participation in program</th></tr>
            <tr class="headerRow headerRow-core">
                <th colspan="2" class="center">Requirement</th>
                <th class="center">Status</th>
                <th colspan="3" class="center">Tracking Method</th>
            </tr>
            <tr class="view-complete_hra">
                <td colspan="2">
                        <strong>A</strong>. <?php echo $completeHraStatus->getComplianceView()->getAttribute('requirement') ?>
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
                        <strong>B</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?>
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
                        <strong>A</strong>. <?php echo $spouseHraScrStatus->getComplianceView()->getReportName() ?>
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
                <td rowspan="5">
                    <strong>B</strong>. Biometric Bonus Points
                </td>
                <td class="requirement">Waist Circumference: <40 inches (Men); <35 inches (Women)</td>
                <td class="center"><?php echo $waistStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $waistStatus->getPoints() ?></td>
                <td class="center"><?php echo $waistStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center" rowspan="5">
                    <?php foreach($waistStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td class="requirement">Blood Pressure < 130/85 OR improved by 10% from year prior</td>
                <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getPoints() ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement">Triglycerides: <150mg/dL OR improved by 10% from year prior</td>
                <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getPoints() ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement">HDL Cholesterol: >40 mg/dL (Men); >50 mg/dL (Women) OR improved by 10% from year prior</td>
                <td class="center"><?php echo $hdlStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $hdlStatus->getPoints() ?></td>
                <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement">Fasting Glucose: <100 mg/dL OR improved by 10% from year prior</td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $glucoseStatus->getPoints() ?></td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td>
                        <strong>C</strong>. <?php echo $annualPhysicalExam->getComplianceView()->getReportName() ?>
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
                        <strong>D</strong>. <?php echo $wellnessFairStatus->getComplianceView()->getReportName() ?>
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
                        <strong>E</strong>. <?php echo $wellnessKickoffStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement">Attend the wellness kick off meeting</td>
                <td class="center"><?php echo $wellnessKickoffStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getPoints() ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">Sign in at Event</td>
            </tr>
            <tr>
                <td>
                        <strong>F</strong>. <?php echo $preventiveExams->getComplianceView()->getReportName() ?>
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
                        <strong>G</strong>. <?php echo $fluShot->getComplianceView()->getReportName() ?>
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
                        <strong>H</strong>. <?php echo $elearning->getComplianceView()->getReportName() ?>
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
                        <strong>I</strong>. <?php echo $moduleCourse->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement">Complete a 12 module course through BCBS WellOnTraget</td>
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
                        <strong>J</strong>. <?php echo $educationVideos->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement">Watch 3 benefit videos and complete quiz</td>
                <td class="center"><?php echo $educationVideos->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $educationVideos->getPoints() ?></td>
                <td class="center"><?php echo $educationVideos->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                </td>
            </tr>

            <tr>
                <td>
                        <strong>K</strong>. <?php echo $compassServices->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement">Engage with the Health Pro Consultant</td>
                <td class="center"><?php echo $compassServices->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $compassServices->getPoints() ?></td>
                <td class="center"><?php echo $compassServices->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">Admin will enter</td>
            </tr>

            <tr>
                <td>
                        <strong>L</strong>. <?php echo $abelsonTaylorWellnessEvents->getComplianceView()->getReportName() ?>
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
                <td class="center"><?php echo $walkingStepOneStatus->getPoints() ?></td>
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
                <td class="center"><?php echo $walkingStepTwoStatus->getPoints() ?></td>
                <td class="center"><?php echo $walkingStepTwoStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 15,000 steps/day </td>
                <td class="center"><?php echo $walkingStepThreeStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walkingStepThreeStatus->getPoints() ?></td>
                <td class="center"><?php echo $walkingStepThreeStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>

            <tr>
                <td>
                    &nbsp;Beat the CFO Challenge<br /><br />
                    &nbsp;July 2017 <br />
                    &nbsp;October 2017 <br />
                    &nbsp;January 2018
                </td>
                <td class="requirement">15 point each month you exceed the number of steps logged by CFO Keith Stenlund</td>
                <td class="center"><?php echo $beatCfoJulyStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $beatCfoJulyStatus->getPoints()  + $beatCfoOctStatus->getPoints() + $beatCfoJanuaryStatus->getPoints()?></td>
                <td class="center">45</td>
                <td class="center">
                    <?php foreach($beatCfoJulyStatus->getComplianceView()->getLinks() as $link) {
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
