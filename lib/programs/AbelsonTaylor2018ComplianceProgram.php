<?php

use hpn\steel\query\SelectQuery;

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class AbelsonTaylor2018IndividualActivityStepsComplianceView extends DateBasedComplianceView
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
            'quarter1' => array('2018-03-01', '2018-05-31'),
            'quarter2' => array('2018-06-01', '2018-08-31'),
            'quarter3' => array('2018-09-01', '2018-11-30'),
            'quarter4' => array('2018-12-01', '2019-02-28')
        );
        $quarter1Points = 0;
        $quarter2Points = 0;
        $quarter3Points = 0;
        $quarter4Points = 0;

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
                'start_date' => '2018-03-01',
                'end_date'   => '2018-03-31',
                'start_count'=> '2018-03-01'
            ),
            'apri'     => array(
                'start_date' => '2018-04-01',
                'end_date'   => '2018-04-30',
                'start_count'=> '2018-04-01'
            ),
            'may'        => array(
                'start_date' => '2018-05-01',
                'end_date'   => '2018-05-31',
                'start_count'=> '2018-05-01'
            ),
            'june'        => array(
                'start_date' => '2018-06-01',
                'end_date'   => '2018-06-30',
                'start_count'=> '2018-06-01'
            ),
            'july'      => array(
                'start_date' => '2018-07-01',
                'end_date'   => '2018-07-31',
                'start_count'=> '2018-07-01'
            ),
            'aug'       => array(
                'start_date' => '2018-08-01',
                'end_date'   => '2018-08-31',
                'start_count'=> '2018-08-01'
            ),
            'sep'       => array(
                'start_date' => '2018-09-01',
                'end_date'   => '2018-09-30',
                'start_count'=> '2018-09-01'
            ),
            'oct'       => array(
                'start_date' => '2018-10-01',
                'end_date'   => '2018-10-31',
                'start_count'=> '2018-10-01'
            ),
            'nov'      => array(
                'start_date' => '2018-11-01',
                'end_date'   => '2018-11-30',
                'start_count'=> '2018-11-01'
            ),
            'dec'       => array(
                'start_date' => '2018-12-01',
                'end_date'   => '2018-12-31',
                'start_count'=> '2018-12-01'
            ),
            'jan'       => array(
                'start_date' => '2019-01-01',
                'end_date'   => '2019-01-31',
                'start_count'=> '2019-01-01'
            ),
            'feb'       => array(
                'start_date' => '2019-02-01',
                'end_date'   => '2019-02-28',
                'start_count'=> '2019-02-01'
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

class AbelsonTaylor2018BeatCFOComplianceView extends DateBasedComplianceView
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

        foreach($fitbitData['dates'] as $date => $steps) {
            $maxSteps = $steps;

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

            foreach($fitbitData['dates'] as $date => $steps) {
                $maxSteps = $steps;

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

class AbelsonTaylor2018ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('03/01/2018 - 05/31/2018 - Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_1_points');
        });

        $printer->addStatusFieldCallback('06/01/2018 - 08/31/2018 - Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_2_points');
        });

        $printer->addStatusFieldCallback('09/01/2018 - 11/30/2018 - Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_3_points');
        });

        $printer->addStatusFieldCallback('12/01/2018 - 02/28/2019 - Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_4_points');
        });

        $printer->addStatusFieldCallback('08/01/2018 - 08/31/2018 - Steps', function(ComplianceProgramStatus $status) {
            $viewStatus = $status->getComplianceViewStatus('beat_cfo_aug');

            return $viewStatus->getAttribute('user_month_total');
        });

        $printer->addStatusFieldCallback('11/01/2018-11/31/2018 - Steps', function(ComplianceProgramStatus $status) {
            $viewStatus = $status->getComplianceViewStatus('beat_cfo_nov');

            return $viewStatus->getAttribute('user_month_total');
        });

        $printer->addStatusFieldCallback('01/15/2019-02/15/2019 - Steps', function(ComplianceProgramStatus $status) {
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
        $hraView->setAttribute('requirement', 'Employee completes the Health Power Profile Questionnaire');
        $hraView->setAttribute('points_per_activity', '10');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Do HPA', '/content/989'));
        $hraView->setUseOverrideCreatedDate(true);
        $coreGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Employee participates in Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('requirement', 'Employee participates in Wellness Screening');
        $screeningView->setAttribute('points_per_activity', '10');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/wms2-appointment-center'));
        $screeningView->addLink(new Link('MD Physician Form', '/resources/10010/Abelson Taylor MD Form 2018.pdf'));
        $screeningView->setUseOverrideCreatedDate(true);
        $coreGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        foreach(array('one', 'two') as $number) {
            $spouseHraScr = new PlaceHolderComplianceView(null, 0);
            $spouseHraScr->setName('spouse_hra_screening_'.$number);
            $spouseHraScr->setReportName('Eligible Spouse/Domestic Partner HPP & Wellness Screening '.strtoupper($number));
            $spouseHraScr->setAttribute('requirement', 'Eligible Spouse, Domestic Partner, and/or Dependents over age 18 complete the Health Power Profile & Wellness Screening');
            $spouseHraScr->setAttribute('points_per_activity', 20);
            $spouseHraScr->setAllowPointsOverride(true);
            $spouseHraScr->setMaximumNumberOfPoints(20);
            $spouseHraScr->addLink(new Link('Do HPA', '/content/989'));
            $spouseHraScr->setUseOverrideCreatedDate(true);
            $wellnessGroup->addComplianceView($spouseHraScr);
        }


        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setReportName('Blood Pressure');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $bloodPressureView->setAttribute('points_per_activity', 5);
        $bloodPressureView->setAttribute('requirement', 'Blood Pressure: <130/85 OR improved by 10% from year prior');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, 89);
        $bloodPressureView->setMergeScreenings(true);
        $bloodPressureView->setPostEvaluateCallback($this->checkImprovement(array('systolic', 'diastolic')));
        $bloodPressureView->setUseOverrideCreatedDate(true);
        $bloodPressureView->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $wellnessGroup->addComplianceView($bloodPressureView);

        $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglyceridesView->setReportName('Triglycerides');
        $triglyceridesView->setName('triglycerides');
        $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $triglyceridesView->overrideTestRowData(null, null, 149.999, null);
        $triglyceridesView->setAttribute('points_per_activity', 5);
        $triglyceridesView->setAttribute('requirement', 'Triglycerides: <150mg/dL OR improved by 10% from year prior');
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
        $hdlView->setAttribute('requirement', 'HDL Cholesterol: >40mg/dL (Men); >50mg/dL (Women) OR improved by 10% from year prior');
        $hdlView->setAttribute('goal', '≥ 40 Men /  ≥50 Women');
        $hdlView->setPostEvaluateCallback($this->checkImprovement(array('hdl'), 'increase'));
        $hdlView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($hdlView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setReportName('Fasting Glucose');
        $glucoseView->setName('glucose');
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0 ,0));
        $glucoseView->setAttribute('points_per_activity', 5);
        $glucoseView->setAttribute('requirement', 'Fasting Glucose: <100mg/dL OR improved by 10% from year prior');
        $glucoseView->overrideTestRowData(null, null, 99.999, null);
        $glucoseView->setPostEvaluateCallback($this->checkImprovement(array('glucose')));
        $glucoseView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($glucoseView);

        $waistView = new ComplyWithWaistScreeningTestComplianceView($programStart, $programEnd);
        $waistView->setReportName('Waist Circumference');
        $waistView->setName('waist');
        $waistView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $waistView->overrideTestRowData(null, null, 39.99, null, 'M');
        $waistView->overrideTestRowData(null, null, 34.99, null, 'F');
        $waistView->setAttribute('requirement', 'Waist Circumference: <40 inches (Men); <35 inches (Women)');
        $waistView->setAttribute('points_per_activity', 5);
        $waistView->setAttribute('goal', '≤40 Men / ≤35 Women');
        $waistView->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $waistView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($waistView);

        $annualPhysicalExamView = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 290, 15);
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $annualPhysicalExamView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Get a physical exam with appropriate tests for your age and gender as recommended by your physician.');
        $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening follow up');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->setAttribute('points_per_activity', '20');
        $annualPhysicalExamView->setAttribute('requirement', 'Visit your personal physician to follow-up on your wellness screening and complete your annual exam.');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link('Verification Form <br />', '/resources/10011/AT_PreventiveServices Cert2018.pdf'));
        $annualPhysicalExamView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $annualPhysicalExamView->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($annualPhysicalExamView);

        $wellnessFair = new PlaceHolderComplianceView(null, 0);
        $wellnessFair->setMaximumNumberOfPoints(10);
        $wellnessFair->setReportName('Wellness Fair Attendance');
        $wellnessFair->setName('wellness_fair');
        $wellnessFair->setAttribute('points_per_activity', 10);
        $wellnessFair->setAttribute('requirement', 'Attend AT Wellness Fair & Complete Survey');
        $wellnessFair->setUseOverrideCreatedDate(true);
        $wellnessFair->addLink(new FakeLink('Admin will Enter', '#'));
        $wellnessGroup->addComplianceView($wellnessFair);

        $wellnessKickOff = new PlaceHolderComplianceView(null, 0);
        $wellnessKickOff->setReportName('Wellness Kick off');
        $wellnessKickOff->setName('kick_off');
        $wellnessKickOff->setAttribute('points_per_activity', 10);
        $wellnessKickOff->setAttribute('requirement', 'Attend the wellness kick off meeting');
        $wellnessKickOff->setMaximumNumberOfPoints(10);
        $wellnessKickOff->emptyLinks();
        $wellnessKickOff->addLink(new FakeLink('Sign in at Event', '/content/events'));
        $wellnessKickOff->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($wellnessKickOff);


        $dateRangeMapper = array(
            'one' => array(
                'start_date'    => '2018-03-01',
                'end_date'    => '2018-05-30'
            ),
            'two' => array(
                'start_date'    => '2018-06-01',
                'end_date'    => '2018-08-31'
            ),
            'three' => array(
                'start_date'    => '2018-09-01',
                'end_date'    => '2018-11-30'
            ),
            'four' => array(
                'start_date'    => '2018-12-01',
                'end_date'    => '2019-02-28'
            )
        );

        foreach(array('one', 'two', 'three', 'four') as $number) {
            $startD = $dateRangeMapper[$number]['start_date'];
            $endD = $dateRangeMapper[$number]['end_date'];

            $preventiveExamsView = new CompletePreventiveExamWithRollingStartDateLogicComplianceView($startD, $endD, 5);
            $preventiveExamsView->configureActivity(259, 91, array(
                'Colonoscopy'   => 60,
                'Dental Exam'   => 24,
                'Mammogram'     => 24,
                'Pap Test'      => 36,
                'Physical Exam' => 36,
                'PSA Test'      => 60
            ));
            $preventiveExamsView->setName('do_preventive_exams_'.$number);
            $preventiveExamsView->setReportName('Preventive Services '.strtoupper($number));
            $preventiveExamsView->setMaximumNumberOfPoints(10);
            $preventiveExamsView->setAttribute('requirement', 'Receive a preventive service such as mammogram, prostate exam, immunizations, vaccines, eye & dental exams, colonoscopy, etc.  See attached wellness guides or check with your personal physician for necessary tests.');
            $preventiveExamsView->setAttribute('points_per_activity', '10');
            $preventiveExamsView->emptyLinks();
            $preventiveExamsView->addLink(new Link('Verification Form <br />', '/resources/10011/AT_PreventiveServices Cert2018.pdf'));
            $preventiveExamsView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
            $preventiveExamsView->setUseOverrideCreatedDate(true);
            $preventiveExamsView->setAllowPointsOverride(true);
            $wellnessGroup->addComplianceView($preventiveExamsView);
        }


        $dateRangeMapper = array(
            'one' => array(
                'start_date'    => '2018-03-01',
                'end_date'    => '2018-06-30'
            ),
            'two' => array(
                'start_date'    => '2018-07-01',
                'end_date'    => '2018-09-31'
            ),
            'three' => array(
                'start_date'    => '2018-10-01',
                'end_date'    => '2019-02-28'
            ),

        );
        foreach(array('one', 'two', 'three') as $number) {
            $startD = $dateRangeMapper[$number]['start_date'];
            $endD = $dateRangeMapper[$number]['end_date'];

            $flushotView = new FluVaccineActivityComplianceView($startD, $endD);
            $flushotView->setName('flu_shot_'.$number);
            $flushotView->setReportName('Flu Shot '.strtoupper($number));
            $flushotView->setMaximumNumberOfPoints(10);
            $flushotView->setAttribute('requirement', 'Receive a flu Shot');
            $flushotView->setAttribute('points_per_activity', '10');
            $flushotView->emptyLinks();
            $flushotView->addLink(new Link('Verification Form <br />', '/resources/10011/AT_PreventiveServices Cert2018.pdf'));
            $flushotView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
            $flushotView->setUseOverrideCreatedDate(true);
            $wellnessGroup->addComplianceView($flushotView);
        }


        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('E-learning');
        $elearn->setName('elearning');
        $elearn->setAttribute('requirement', 'Complete an online e-learning course');
        $elearn->setAttribute('points_per_activity', '10');
        $elearn->setPointsPerLesson(10);
        $elearn->setMaximumNumberOfPoints(40);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=completed_compliance'));
        $elearn->setUseOverrideCreatedDate(true);
        $wellnessGroup->addComplianceView($elearn);


        foreach(array('one', 'two', 'three', 'four', 'five') as $number) {
            $moduleCourseView = new PlaceHolderComplianceView(null, 0);
            $moduleCourseView->setName('module_course_'.$number);
            $moduleCourseView->setReportName('Managing Stress  <br /> &nbsp;&nbsp; Improving Nutrition  <br /> &nbsp;&nbsp; Quitting Tobacco <br /> &nbsp;&nbsp; Weight Management <br /> &nbsp;&nbsp; Get Active '.strtoupper($number));
            $moduleCourseView->setAttribute('requirement', 'Complete a 12 module course through BCBS Well On Target');
            $moduleCourseView->setAttribute('points_per_activity', '10');
            $moduleCourseView->emptyLinks();
            $moduleCourseView->addLink((new Link('Click Here', 'http://wellontarget.com')));
            $moduleCourseView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
            $moduleCourseView->setMaximumNumberOfPoints(10);
            $moduleCourseView->setUseOverrideCreatedDate(true);
            $moduleCourseView->setAllowPointsOverride(true);
            $wellnessGroup->addComplianceView($moduleCourseView);
        }


        $dateRangeMapper = array(
            'one' => array(
                'start_date'    => '2018-03-01',
                'end_date'    => '2018-08-31'
            ),
            'two' => array(
                'start_date'    => '2018-09-01',
                'end_date'    => '2019-02-28'
            )
        );

        foreach(array('one', 'two') as $number) {
            $startD = $dateRangeMapper[$number]['start_date'];
            $endD = $dateRangeMapper[$number]['end_date'];

            $compassServices = new CompleteArbitraryActivityComplianceView($startD, $endD, 330, 10);
            $compassServices->setName('compass_services_'.$number);
            $compassServices->setReportName('Compass Services '.strtoupper($number));
            $compassServices->setMaximumNumberOfPoints(5);
            $compassServices->setAttribute('requirement', 'Engage with the Health Pro Consultant');
            $compassServices->setAttribute('points_per_activity', '5');
            $compassServices->emptyLinks();
            $compassServices->setUseOverrideCreatedDate(true);
            $compassServices->setAllowPointsOverride(true);
            $wellnessGroup->addComplianceView($compassServices);
        }


        $dateRangeMapper = array(
            'one' => array(
                'start_date'    => '2018-03-01',
                'end_date'    => '2018-03-30'
            ),
            'two' => array(
                'start_date'    => '2018-04-01',
                'end_date'    => '2018-04-31'
            ),
            'three' => array(
                'start_date'    => '2018-05-01',
                'end_date'    => '2018-05-30'
            ),
            'four' => array(
                'start_date'    => '2018-06-01',
                'end_date'    => '2018-06-31'
            ),
            'five' => array(
                'start_date'    => '2018-07-01',
                'end_date'    => '2018-07-31'
            ),
            'six' => array(
                'start_date'    => '2018-08-01',
                'end_date'    => '2018-08-31'
            ),
            'seven' => array(
                'start_date'    => '2018-09-01',
                'end_date'    => '2018-09-31'
            ),
            'eight' => array(
                'start_date'    => '2018-10-01',
                'end_date'    => '2018-10-31'
            ),
            'nine' => array(
                'start_date'    => '2018-11-01',
                'end_date'    => '2018-11-31'
            ),
            'ten' => array(
                'start_date'    => '2018-12-01',
                'end_date'    => '2018-12-15'
            ),
            'eleven' => array(
                'start_date'    => '2018-12-16',
                'end_date'    => '2018-12-31'
            ),
            'twelve' => array(
                'start_date'    => '2019-01-01',
                'end_date'    => '2019-01-15'
            ),
            'thirteen' => array(
                'start_date'    => '2019-01-16',
                'end_date'    => '2019-01-30'
            ),
            'fourteen' => array(
                'start_date'    => '2019-02-01',
                'end_date'    => '2019-02-15'
            ),
            'fifteen' => array(
                'start_date'    => '2019-02-16',
                'end_date'    => '2019-02-28'
            )
        );


        foreach(array('one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen') as $number) {
            $startD = $dateRangeMapper[$number]['start_date'];
            $endD = $dateRangeMapper[$number]['end_date'];

            $abelsonTaylorWellnessEventsView = new PhysicalActivityComplianceView($startD, $endD);
            $abelsonTaylorWellnessEventsView->setName('abelson_taylor_wellness_events_'.$number);
            $abelsonTaylorWellnessEventsView->setReportName('Abelson Taylor Wellness Events '.strtoupper($number));
            $abelsonTaylorWellnessEventsView->setMaximumNumberOfPoints(10);
            $abelsonTaylorWellnessEventsView->setAttribute('requirement', 'Participate in a designated wellness activity or lunch and learn');
            $abelsonTaylorWellnessEventsView->setAttribute('points_per_activity', '10');
            $abelsonTaylorWellnessEventsView->emptyLinks();
            $abelsonTaylorWellnessEventsView->addLink(new FakeLink('Admin will Enter', '#'));
            $abelsonTaylorWellnessEventsView->setUseOverrideCreatedDate(true);
            $abelsonTaylorWellnessEventsView->setAllowPointsOverride(true);
            $wellnessGroup->addComplianceView($abelsonTaylorWellnessEventsView);
        }


        $dateRangeMapper = array(
            'one' => array(
                'start_date'    => '2018-03-01',
                'end_date'    => '2018-06-30'
            ),
            'two' => array(
                'start_date'    => '2018-07-01',
                'end_date'    => '2018-09-31'
            ),
            'three' => array(
                'start_date'    => '2018-10-01',
                'end_date'    => '2019-02-28'
            )
        );
        foreach(array('one', 'two', 'three') as $number) {
            $startD = $dateRangeMapper[$number]['start_date'];
            $endD = $dateRangeMapper[$number]['end_date'];

            $onYourOwnView = new PhysicalActivityComplianceView($startD, $endD);
            $onYourOwnView->setName('on_your_own_'.$number);
            $onYourOwnView->setReportName("'On Your Own' Event ".strtoupper($number));
            $onYourOwnView->setMaximumNumberOfPoints(15);
            $onYourOwnView->setAttribute('requirement', 'Participate in an external wellness event (5k run/walk, bike race, etc)');
            $onYourOwnView->setAttribute('points_per_activity', '15');
            $onYourOwnView->emptyLinks();
            $onYourOwnView->addLink(new Link('Provide documentation of completion', '/content/chp-document-uploader'));
            $onYourOwnView->setUseOverrideCreatedDate(true);
            $onYourOwnView->setAllowPointsOverride(true);
            $wellnessGroup->addComplianceView($onYourOwnView);
        }


        $wellnessGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($wellnessGroup);

        $walkingGroup = new ComplianceViewGroup('walking_programs', 'Program');
        $walkingGroup->setPointsRequiredForCompliance(0);

        $stepOne = new AbelsonTaylor2018IndividualActivityStepsComplianceView($programStart, $programEnd, 7500, 2);
        $stepOne->setReportName('Individual Walking Challenge');
        $stepOne->setName('step_one');
        $stepOne->setAttribute('points_per_activity', '2 points');
        $stepOne->setMaximumNumberOfPoints(24);
        $stepOne->addLink(new Link('Fitbit Sync <br /><br />', '/content/ucan-fitbit-individual'));
        $stepOne->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($stepOne);

        $stepTwo = new AbelsonTaylor2018IndividualActivityStepsComplianceView($programStart, $programEnd, 12000, 2);
        $stepTwo->setReportName('Individual Walking Challenge');
        $stepTwo->setName('step_two');
        $stepTwo->setAttribute('points_per_activity', 'Additional 2 points');
        $stepTwo->setMaximumNumberOfPoints(24);
        $stepTwo->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($stepTwo);

        $stepThree = new AbelsonTaylor2018IndividualActivityStepsComplianceView($programStart, $programEnd, 15000, 2);
        $stepThree->setReportName('Individual Walking Challenge');
        $stepThree->setName('step_three');
        $stepThree->setAttribute('points_per_activity', 'Additional 2 points');
        $stepThree->setMaximumNumberOfPoints(24);
        $stepThree->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($stepThree);

        $ATBeatCfoAug = new AbelsonTaylor2018BeatCFOComplianceView('2018-08-01', '2018-08-31', 414, 110, '2018-08');
        $ATBeatCfoAug->setName('beat_cfo_aug');
        $ATBeatCfoAug->setMaximumNumberOfPoints(15);
        $ATBeatCfoAug->addAttributes(array(
            'points_per_activity' =>
                '15',

            'requirement' =>
                '15 points each month you exceed the number of steps logged by CFO Keith Stenlund'
        ));
        $ATBeatCfoAug->addLink(new Link('My Steps', '/content/ucan-fitbit-individual'));
        $ATBeatCfoAug->addLink(new Link('Leaderboards', '/content/ucan-fitbit-leaderboards?type=individual'));
        $ATBeatCfoAug->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($ATBeatCfoAug);


        $ATBeatCfoNov = new AbelsonTaylor2018BeatCFOComplianceView('2018-11-01', '2018-11-31', 414, 110, '2018-11');
        $ATBeatCfoNov->setName('beat_cfo_nov');
        $ATBeatCfoNov->setMaximumNumberOfPoints(15);
        $ATBeatCfoNov->addAttributes(array(
            'points_per_activity' =>
                '5',

            'requirement' =>
                '5 point each month you exceed the number of steps logged by CFO Keith Stenlund'
        ));
        $ATBeatCfoNov->setUseOverrideCreatedDate(true);
        $walkingGroup->addComplianceView($ATBeatCfoNov);

        $ATBeatCfoJanuary = new AbelsonTaylor2018BeatCFOComplianceView('2019-01-15', '2019-02-15', 414, 110, '2019-01');
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

        $lastStart = new \DateTime('2017-03-01');
        $lastEnd = new \DateTime('2018-02-28');

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
            $printer = new AbelsonTaylor2018ComplianceProgramReportPrinter();
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
                    if(!$viewStatus->getAttribute('quarter_1_points')) $viewStatus->setAttribute('quarter_1_points', 0);
                    if(!$viewStatus->getAttribute('quarter_2_points')) $viewStatus->setAttribute('quarter_2_points', 0);
                    if(!$viewStatus->getAttribute('quarter_3_points')) $viewStatus->setAttribute('quarter_3_points', 0);
                    if(!$viewStatus->getAttribute('quarter_4_points')) $viewStatus->setAttribute('quarter_4_points', 0);


                    $viewPoints = $viewStatus->getPoints();
                    $pointAdded = false;
                    if($viewPoints > 0) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if ($viewStatus->getComplianceView()->getName() == "elearning") {
                                $dates = $viewStatus->getAttribute('lessons_completed_dates');

                                foreach($dates as $date) {
                                    $date = date('Y-m-d',$date['date']);
                                    $points = $view->getPointsPerLesson();
                                    $pointAdded = true;

                                    if($date && $startDate <= $date && $date <= $endDate) {
                                        if($quarterName == 'quarter1') {
                                            $view1Points = ($viewStatus->getAttribute('quarter_1_points')) ?: 0;
                                            $view1Points += $points;
                                            $quarter1Points += $points;
                                            $viewStatus->setAttribute('quarter_1_points', $view1Points);
                                        } elseif ($quarterName == 'quarter2') {
                                            $view2Points = ($viewStatus->getAttribute('quarter_2_points')) ?: 0;
                                            $view2Points += $points;
                                            $quarter2Points += $points;
                                            $viewStatus->setAttribute('quarter_2_points', $view2Points);
                                        } elseif ($quarterName == 'quarter3') {
                                            $view3Points = ($viewStatus->getAttribute('quarter_3_points')) ?: 0;
                                            $view3Points += $points;
                                            $quarter3Points += $points;
                                            $viewStatus->setAttribute('quarter_3_points', $view3Points);
                                        } elseif ($quarterName == 'quarter4') {
                                            $view4Points = ($viewStatus->getAttribute('quarter_4_points')) ?: 0;
                                            $view4Points += $points;
                                            $quarter4Points += $points;
                                            $viewStatus->setAttribute('quarter_4_points', $view4Points);
                                        }
                                    }
                                }

                            } else {

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
                                        $viewStatus->setAttribute('quarter_1_points', $viewPoints);
                                    } elseif ($quarterName == 'quarter2') {
                                        $quarter2Points += $viewPoints;
                                        $viewStatus->setAttribute('quarter_2_points', $viewPoints);
                                    } elseif ($quarterName == 'quarter3') {
                                        $quarter3Points += $viewPoints;
                                        $viewStatus->setAttribute('quarter_3_points', $viewPoints);
                                    } elseif ($quarterName == 'quarter4') {
                                        $quarter4Points += $viewPoints;
                                        $viewStatus->setAttribute('quarter_4_points', $viewPoints);
                                    }
                                    $pointAdded = true;
                                }
                            }
                        }

                        if(!$pointAdded) {
                            $quarter1Points += $viewPoints;
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
            'quarter1' => array('2018-03-01', '2018-05-31'),
            'quarter2' => array('2018-06-01', '2018-08-31'),
            'quarter3' => array('2018-09-01', '2018-11-30'),
            'quarter4' => array('2018-12-01', '2019-02-28')
        );

        return $ranges;
    }

}


class AbelsonTaylor2018ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        $spouseHraScrOneStatus = $wellnessGroupStatus->getComplianceViewStatus('spouse_hra_screening_one');
        $spouseHraScrTwoStatus = $wellnessGroupStatus->getComplianceViewStatus('spouse_hra_screening_two');


        $waistStatus = $wellnessGroupStatus->getComplianceViewStatus('waist');
        $bloodPressureStatus = $wellnessGroupStatus->getComplianceViewStatus('blood_pressure');
        $triglyceridesStatus = $wellnessGroupStatus->getComplianceViewStatus('triglycerides');
        $hdlStatus = $wellnessGroupStatus->getComplianceViewStatus('hdl');
        $glucoseStatus = $wellnessGroupStatus->getComplianceViewStatus('glucose');
        $annualPhysicalExam = $wellnessGroupStatus->getComplianceViewStatus('annual_physical_exam');

        $wellnessFairStatus = $wellnessGroupStatus->getComplianceViewStatus('wellness_fair');
        $wellnessKickoffStatus = $wellnessGroupStatus->getComplianceViewStatus('kick_off');
        $preventiveExamsOneStatus = $wellnessGroupStatus->getComplianceViewStatus('do_preventive_exams_one');
        $preventiveExamsTwoStatus = $wellnessGroupStatus->getComplianceViewStatus('do_preventive_exams_two');
        $preventiveExamsThreeStatus = $wellnessGroupStatus->getComplianceViewStatus('do_preventive_exams_three');
        $preventiveExamsFourStatus = $wellnessGroupStatus->getComplianceViewStatus('do_preventive_exams_four');

        $fluShotOneStatus = $wellnessGroupStatus->getComplianceViewStatus('flu_shot_one');
        $fluShotTwoStatus = $wellnessGroupStatus->getComplianceViewStatus('flu_shot_two');
        $fluShotThreeStatus = $wellnessGroupStatus->getComplianceViewStatus('flu_shot_three');
        $elearning = $wellnessGroupStatus->getComplianceViewStatus('elearning');

        $moduleCourseOneStatus = $wellnessGroupStatus->getComplianceViewStatus('module_course_one');
        $moduleCourseTwoStatus = $wellnessGroupStatus->getComplianceViewStatus('module_course_two');
        $moduleCourseThreeStatus = $wellnessGroupStatus->getComplianceViewStatus('module_course_three');
        $moduleCourseFourStatus = $wellnessGroupStatus->getComplianceViewStatus('module_course_four');
        $moduleCourseFiveStatus = $wellnessGroupStatus->getComplianceViewStatus('module_course_five');

        $compassServicesOneStatus = $wellnessGroupStatus->getComplianceViewStatus('compass_services_one');
        $compassServicesTwoStatus = $wellnessGroupStatus->getComplianceViewStatus('compass_services_two');


        $abelsonTaylorWellnessEventsOneStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_one');
        $abelsonTaylorWellnessEventsTwoStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_two');
        $abelsonTaylorWellnessEventsThreeStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_three');
        $abelsonTaylorWellnessEventsFourStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_four');
        $abelsonTaylorWellnessEventsFiveStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_five');
        $abelsonTaylorWellnessEventsSixStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_six');
        $abelsonTaylorWellnessEventsSevenStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_seven');
        $abelsonTaylorWellnessEventsEightStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_eight');
        $abelsonTaylorWellnessEventsNineStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_nine');
        $abelsonTaylorWellnessEventsTenStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_ten');
        $abelsonTaylorWellnessEventsElevenStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_eleven');
        $abelsonTaylorWellnessEventsTwelveStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_twelve');
        $abelsonTaylorWellnessEventsThirteenStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_thirteen');
        $abelsonTaylorWellnessEventsFourteenStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_fourteen');
        $abelsonTaylorWellnessEventsFifteenStatus = $wellnessGroupStatus->getComplianceViewStatus('abelson_taylor_wellness_events_fifteen');



        $onYourOwnOneStatus = $wellnessGroupStatus->getComplianceViewStatus('on_your_own_one');
        $onYourOwnTwoStatus = $wellnessGroupStatus->getComplianceViewStatus('on_your_own_two');
        $onYourOwnThreeStatus = $wellnessGroupStatus->getComplianceViewStatus('on_your_own_three');

        $walkingGroupStatus = $status->getComplianceViewGroupStatus('walking_programs');
        $walkingGroup = $walkingGroupStatus->getComplianceViewGroup();

        $walkingStepOneStatus = $walkingGroupStatus->getComplianceViewStatus('step_one');
        $walkingStepTwoStatus = $walkingGroupStatus->getComplianceViewStatus('step_two');
        $walkingStepThreeStatus = $walkingGroupStatus->getComplianceViewStatus('step_three');

        $beatCfoAugStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_aug');
        $beatCfoNovStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_nov');
        $beatCfoJanuaryStatus = $walkingGroupStatus->getComplianceViewStatus('beat_cfo_jan');

        if(date('Y-m-d') <= '2018-11-01') {
            $beatCfoStatus = $beatCfoAugStatus;
        } elseif(date('Y-m-d') <= '2019-01-01') {
            $beatCfoStatus = $beatCfoNovStatus;
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
            Your health and your family’s health is important not only to you but to Abelson Taylor as well! AT is expanding
            and enhancing our wellness program for employees and their families and want to reward you for participating. 
        </p>
        <p>
            The enhanced wellness program will be tracked by our biometric screening company, HMI (Health Maintenance Institute)
            <a href="http://www.myhmihealth.com">http://www.myhmihealth.com</a>. HMI is a HIPAA-compliant, outside vendor
            that will maintain the confidentiality of your personal health information. There is no need to worry that
            your personal health information will be seen by anyone within the AT organization. This program will be housed
            electronically but we have included an example of the different types of activities that will be eligible to
            receive points and rewards. 
        </p>

        <p>
            To participate in the AT wellness program you will first want to enroll on the HMI site:
            <a href="www.myhmihealth.com">www.myhmihealth.com</a> and register with the code: ABELSON.  Once you have
            enrolled on the HMI site you will be required to complete the health questionnaire.
        </p>

        <div>
            <table id="programTable">
                <tr style="background-color:#008787">
                    <th style="width: 200px;">Earning Period</th>
                    <th>Participation</th>
                    <th>Reward</th>
                </tr>
                <tr>
                    <td>3/1/2018 - 2/28/2019</td>
                    <td>
                        Completes annual physical OR onsite wellness screening & Health Questionnaire <br />
                        <span style="color: red;">**REQUIRED for Program Participation**</span>
                    </td>
                    <td>$40 monthly premium discount <br />(effective 6/1/18; discount good for maximum of 12 months).</td>
                </tr>
                <tr>
                    <td>3/1/2018 - 2/28/2019</td>
                    <td>Visit your personal physician to follow-up on your wellness screening and complete your annual exam.</td>
                    <td>Receive a credit of $55.00 towards the purchase of a NEW fitness tracking device.**</td>
                </tr>
                <tr>
                    <td>Period 1: 3/1/18 - 5/30/18</td>
                    <td>Earn <span style="color:red;">75</span> points in Period 1</td>
                    <td>$110 HSA deposit OR Premium Discount</td>
                </tr>
                <tr>
                    <td>Period 2: 6/1/18 - 8/31/18</td>
                    <td>Earn <span style="color:red;">40</span> points in Period 2</td>
                    <td>$110 HSA deposit OR Premium Discount</td>
                </tr>
                <tr>
                    <td>Period 3: 9/1/18 - 11/30/18</td>
                    <td>Earn <span style="color:red;">40</span> points in Period 3</td>
                    <td>$110 HSA deposit OR Premium Discount</td>
                </tr>
                <tr>
                    <td>Period 4: 12/1/18 - 2/28/19</td>
                    <td>Earn <span style="color:red;">40</span> points in Period 4</td>
                    <td>$110 HSA deposit OR Premium Discount</td>
                </tr>
                <tr>
                    <td>BONUS</td>
                    <td>Successfully complete 4 out of 4 periods</td>
                    <td>$200 HSA deposit <strong>OR</strong> Premium Discount</td>
                </tr>
            </table>
            <p style="text-align: center; font-weight: bold;">
                ** The credit will be automatically added to your paycheck upon eligibility. <br />
                Points will be awarded in the period documentation is received
            </p>
        </div><br />

        <p>
            Rewards will be paid out every 3 months.  At the end of each period, a report will be run from HMI and if an
            employee earns the necessary amount of points within that period then a deposit into an employee’s HSA (if
            employees are on a high deductible plan) or a one-time premium discount will occur (if the employees are not
            on a high deductible plan or on an HMO plan). <strong>All activities must be submitted during the period in which
            they were completed</strong>. Deposits or discounts will take place on the <u>last payroll</u> of each month following
            the end of each period.
        </p>

        <p>
            <strong>
                BONUS! Should you successfully complete 4 out of 4 periods, you will be eligible for an additional
                <u>$200</u> HSA deposit or Premium Discount.
            </strong>
        </p>

        <table class="phipTable">
            <tbody>
            <tr><th colspan="9" style="height:36px; text-align:center; color: white; background-color:#436EEE; font-size:11pt">2018 Wellness Rewards Program</th></tr>
            <tr><th colspan="9" class="section"><span style="font-weight:bolder; font-size: 12pt;">Required Activities</span> A & B are required for participation in program</th></tr>
            <tr class="headerRow headerRow-core">
                <th colspan="4" class="center">Requirement</th>
                <th colspan="2" class="center">Status</th>
                <th colspan="3" class="center">Tracking Method</th>
            </tr>
            <tr class="view-complete_hra">
                <td colspan="4">
                    <strong>A</strong>. <?php echo $completeHraStatus->getComplianceView()->getAttribute('requirement') ?>
                </td>
                <td colspan="2" class="center">
                    <img src="<?php echo $completeHraStatus->getLight(); ?>" class="light"/>
                </td>
                <td colspan="3" class="center">
                    <?php foreach($completeHraStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr class="view-complete_screening">
                <td colspan="4">
                    <strong>B</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?>
                </td>
                <td colspan="2" class="center">
                    <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
                </td>
                <td colspan="3" class="center">
                    <?php foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr><th colspan="9" class="section"><span style="font-weight:bolder; font-size: 12pt;">Point Earning Wellness Activities</span> A-L are additional activities to help you year points</th></tr>

            <tr class="headerRow headerRow-wellness_programs">
                <td class="center">Activity</td>
                <td class="center">Requirement</td>
                <td class="center">Points Per Activity</td>

                <td class="center"># Points Earned Period 1</td>
                <td class="center"># Points Earned Period 2</td>
                <td class="center"># Points Earned Period 3</td>
                <td class="center"># Points Earned Period 4</td>
                <td class="center">Max Points</td>
                <td class="center">Tracking Method</td>
            </tr>
            <tr>
                <td>
                    <strong>A</strong>. Eligible Spouse/Domestic Partner HPP & Wellness Screening
                </td>
                <td class="requirement"><?php echo $spouseHraScrOneStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $spouseHraScrOneStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $spouseHraScrOneStatus->getAttribute('quarter_1_points') + $spouseHraScrTwoStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $spouseHraScrOneStatus->getAttribute('quarter_2_points') + $spouseHraScrTwoStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $spouseHraScrOneStatus->getAttribute('quarter_3_points') + $spouseHraScrTwoStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $spouseHraScrOneStatus->getAttribute('quarter_4_points') + $spouseHraScrTwoStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $spouseHraScrOneStatus->getComplianceView()->getMaximumNumberOfPoints() + $spouseHraScrTwoStatus->getComplianceView()->getMaximumNumberOfPoints()?></td>
                <td class="center">
                    <?php foreach($spouseHraScrOneStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td rowspan="5">
                    <strong>B</strong>. Biometric Bonus Points
                </td>
                <td class="requirement"><?php echo $bloodPressureStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $bloodPressureStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center" rowspan="5">
                    <?php foreach($bloodPressureStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $triglyceridesStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $hdlStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $hdlStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $hdlStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $hdlStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $hdlStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $hdlStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $hdlStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $glucoseStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $glucoseStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $glucoseStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $glucoseStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $glucoseStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $glucoseStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td class="requirement"><?php echo $waistStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $waistStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $waistStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $waistStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $waistStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $waistStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $waistStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>
            <tr>
                <td>
                    <strong>C</strong>. <?php echo $annualPhysicalExam->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $annualPhysicalExam->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $annualPhysicalExam->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $annualPhysicalExam->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $annualPhysicalExam->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $annualPhysicalExam->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $annualPhysicalExam->getAttribute('quarter_4_points') ?></td>
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
                <td class="requirement"><?php echo $wellnessFairStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $wellnessFairStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $wellnessFairStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $wellnessFairStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $wellnessFairStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $wellnessFairStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $wellnessFairStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    Admin will enter
                </td>
            </tr>
            <tr>
                <td>
                    <strong>E</strong>. <?php echo $wellnessKickoffStatus->getComplianceView()->getReportName() ?>
                </td>
                <td class="requirement"><?php echo $wellnessKickoffStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $wellnessKickoffStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">Sign in at Event</td>
            </tr>
            <tr>
                <td>
                    <strong>F</strong>. Preventive Services
                </td>
                <td class="requirement"><?php echo $preventiveExamsOneStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $preventiveExamsOneStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $preventiveExamsOneStatus->getAttribute('quarter_1_points') + $preventiveExamsTwoStatus->getAttribute('quarter_1_points') + $preventiveExamsThreeStatus->getAttribute('quarter_1_points') + $preventiveExamsFourStatus->getAttribute('quarter_1_points')  ?></td>
                <td class="center"><?php echo $preventiveExamsOneStatus->getAttribute('quarter_2_points') + $preventiveExamsTwoStatus->getAttribute('quarter_2_points') + $preventiveExamsThreeStatus->getAttribute('quarter_2_points') + $preventiveExamsFourStatus->getAttribute('quarter_2_points')  ?></td>
                <td class="center"><?php echo $preventiveExamsOneStatus->getAttribute('quarter_3_points') + $preventiveExamsTwoStatus->getAttribute('quarter_3_points') + $preventiveExamsThreeStatus->getAttribute('quarter_3_points') + $preventiveExamsFourStatus->getAttribute('quarter_3_points')  ?></td>
                <td class="center"><?php echo $preventiveExamsOneStatus->getAttribute('quarter_4_points') + $preventiveExamsTwoStatus->getAttribute('quarter_4_points') + $preventiveExamsThreeStatus->getAttribute('quarter_4_points') + $preventiveExamsFourStatus->getAttribute('quarter_4_points')  ?></td>
                <td class="center"><?php echo $preventiveExamsOneStatus->getComplianceView()->getMaximumNumberOfPoints() + $preventiveExamsTwoStatus->getComplianceView()->getMaximumNumberOfPoints() + $preventiveExamsThreeStatus->getComplianceView()->getMaximumNumberOfPoints() + $preventiveExamsFourStatus->getComplianceView()->getMaximumNumberOfPoints()?></td>
                <td class="center">
                    <?php foreach($preventiveExamsOneStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>G</strong>. Flu Shot
                </td>
                <td class="requirement">Receive a flu shot for yourself,Eligible Spouse, Domestic Partner, and/or Dependents over age 18</td>
                <td class="center"><?php echo $fluShotOneStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $fluShotOneStatus->getAttribute('quarter_1_points') + $fluShotTwoStatus->getAttribute('quarter_1_points') + $fluShotThreeStatus->getAttribute('quarter_1_points')  ?></td>
                <td class="center"><?php echo $fluShotOneStatus->getAttribute('quarter_2_points') + $fluShotTwoStatus->getAttribute('quarter_2_points') + $fluShotThreeStatus->getAttribute('quarter_2_points')  ?></td>
                <td class="center"><?php echo $fluShotOneStatus->getAttribute('quarter_3_points') + $fluShotTwoStatus->getAttribute('quarter_3_points') + $fluShotThreeStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $fluShotOneStatus->getAttribute('quarter_4_points') + $fluShotTwoStatus->getAttribute('quarter_4_points') + $fluShotThreeStatus->getAttribute('quarter_4_points')  ?></td>
                <td class="center"><?php echo $fluShotOneStatus->getComplianceView()->getMaximumNumberOfPoints() + $fluShotTwoStatus->getComplianceView()->getMaximumNumberOfPoints() + $fluShotThreeStatus->getComplianceView()->getMaximumNumberOfPoints()  ?></td>
                <td class="center">
                    <?php foreach($fluShotOneStatus->getComplianceView()->getLinks() as $link) {
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
                <td class="center"><?php echo $elearning->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $elearning->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $elearning->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $elearning->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $elearning->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">
                    <?php foreach($elearning->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>I</strong>. Managing Stress  <br /> &nbsp;&nbsp; Improving Nutrition  <br /> &nbsp;&nbsp; Quitting Tobacco <br /> &nbsp;&nbsp; Weight Management <br /> &nbsp;&nbsp; Get Active
                </td>
                <td class="requirement">Complete a 12 module course through BCBS WellOnTraget</td>
                <td class="center"><?php echo $moduleCourseOneStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $moduleCourseOneStatus->getAttribute('quarter_1_points') + $moduleCourseTwoStatus->getAttribute('quarter_1_points') + $moduleCourseThreeStatus->getAttribute('quarter_1_points') + $moduleCourseFourStatus->getAttribute('quarter_1_points') + $moduleCourseFiveStatus->getAttribute('quarter_1_points')  ?></td>
                <td class="center"><?php echo $moduleCourseOneStatus->getAttribute('quarter_2_points') + $moduleCourseTwoStatus->getAttribute('quarter_2_points') + $moduleCourseThreeStatus->getAttribute('quarter_2_points') + $moduleCourseFourStatus->getAttribute('quarter_2_points') + $moduleCourseFiveStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $moduleCourseOneStatus->getAttribute('quarter_3_points') + $moduleCourseTwoStatus->getAttribute('quarter_3_points') + $moduleCourseThreeStatus->getAttribute('quarter_3_points') + $moduleCourseFourStatus->getAttribute('quarter_3_points') + $moduleCourseFiveStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $moduleCourseOneStatus->getAttribute('quarter_4_points') + $moduleCourseTwoStatus->getAttribute('quarter_4_points') + $moduleCourseThreeStatus->getAttribute('quarter_4_points') + $moduleCourseFourStatus->getAttribute('quarter_4_points') + $moduleCourseFiveStatus->getAttribute('quarter_4_points')  ?></td>
                <td class="center"><?php echo $moduleCourseOneStatus->getComplianceView()->getMaximumNumberOfPoints() + $moduleCourseTwoStatus->getComplianceView()->getMaximumNumberOfPoints() + $moduleCourseThreeStatus->getComplianceView()->getMaximumNumberOfPoints() + $moduleCourseFourStatus->getComplianceView()->getMaximumNumberOfPoints() + $moduleCourseFiveStatus->getComplianceView()->getMaximumNumberOfPoints()?></td>
                <td class="center">
                    <?php foreach($moduleCourseOneStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>


            <tr>
                <td>
                    <strong>J</strong>. Compass Services
                </td>
                <td class="requirement">Engage with the Health Pro Consultant</td>
                <td class="center"><?php echo $compassServicesOneStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $compassServicesOneStatus->getAttribute('quarter_1_points') + $compassServicesTwoStatus->getAttribute('quarter_1_points')  ?></td>
                <td class="center"><?php echo $compassServicesOneStatus->getAttribute('quarter_2_points') + $compassServicesTwoStatus->getAttribute('quarter_2_points')  ?></td>
                <td class="center"><?php echo $compassServicesOneStatus->getAttribute('quarter_3_points') + $compassServicesTwoStatus->getAttribute('quarter_3_points')  ?></td>
                <td class="center"><?php echo $compassServicesOneStatus->getAttribute('quarter_4_points') + $compassServicesTwoStatus->getAttribute('quarter_4_points')  ?></td>
                <td class="center"><?php echo $compassServicesOneStatus->getComplianceView()->getMaximumNumberOfPoints() + $compassServicesTwoStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                <td class="center">Admin will enter</td>
            </tr>

            <tr>
                <td>
                    <strong>K</strong>. Abelson Taylor Wellness Events
                </td>
                <td class="requirement"><?php echo $abelsonTaylorWellnessEventsOneStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $abelsonTaylorWellnessEventsOneStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center">
                    <?php
                    echo $abelsonTaylorWellnessEventsOneStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsTwoStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsThreeStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsFourStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsFiveStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsSixStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsSevenStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsEightStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsNineStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsTenStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsElevenStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsTwelveStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsThirteenStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsFourteenStatus->getAttribute('quarter_1_points')
                        + $abelsonTaylorWellnessEventsFifteenStatus->getAttribute('quarter_1_points')
                    ?>
                </td>
                <td class="center">
                    <?php
                    echo $abelsonTaylorWellnessEventsOneStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsTwoStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsThreeStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsFourStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsFiveStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsSixStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsSevenStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsEightStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsNineStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsTenStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsElevenStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsTwelveStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsThirteenStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsFourteenStatus->getAttribute('quarter_2_points')
                        + $abelsonTaylorWellnessEventsFifteenStatus->getAttribute('quarter_2_points')
                    ?>
                </td>
                <td class="center">
                    <?php
                    echo $abelsonTaylorWellnessEventsOneStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsTwoStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsThreeStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsFourStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsFiveStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsSixStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsSevenStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsEightStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsNineStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsTenStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsElevenStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsTwelveStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsThirteenStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsFourteenStatus->getAttribute('quarter_3_points')
                        + $abelsonTaylorWellnessEventsFifteenStatus->getAttribute('quarter_3_points')
                    ?>
                </td>
                <td class="center">
                    <?php
                    echo $abelsonTaylorWellnessEventsOneStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsTwoStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsThreeStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsFourStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsFiveStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsSixStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsSevenStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsEightStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsNineStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsTenStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsElevenStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsTwelveStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsThirteenStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsFourteenStatus->getAttribute('quarter_4_points')
                        + $abelsonTaylorWellnessEventsFifteenStatus->getAttribute('quarter_4_points')
                    ?>
                </td>
                <td class="center">
                    <?php
                    echo $abelsonTaylorWellnessEventsOneStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsTwoStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsThreeStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsFourStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsFiveStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsSixStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsSevenStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsEightStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsNineStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsTenStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsElevenStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsTwelveStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsThirteenStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsFourteenStatus->getComplianceView()->getMaximumNumberOfPoints()
                        + $abelsonTaylorWellnessEventsFifteenStatus->getComplianceView()->getMaximumNumberOfPoints()

                    ?>
                </td>
                <td class="center">Admin will enter</td>
            </tr>

            <tr>
                <td>
                    <strong>L</strong>. 'On Your Own' Event
                </td>
                <td class="requirement"><?php echo $onYourOwnOneStatus->getComplianceView()->getAttribute('requirement') ?></td>
                <td class="center"><?php echo $onYourOwnOneStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $onYourOwnOneStatus->getAttribute('quarter_1_points') + $onYourOwnTwoStatus->getAttribute('quarter_1_points') + $onYourOwnThreeStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $onYourOwnOneStatus->getAttribute('quarter_2_points') + $onYourOwnTwoStatus->getAttribute('quarter_2_points') + $onYourOwnThreeStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $onYourOwnOneStatus->getAttribute('quarter_3_points') + $onYourOwnTwoStatus->getAttribute('quarter_3_points') + $onYourOwnThreeStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $onYourOwnOneStatus->getAttribute('quarter_4_points') + $onYourOwnTwoStatus->getAttribute('quarter_4_points') + $onYourOwnThreeStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $onYourOwnOneStatus->getComplianceView()->getMaximumNumberOfPoints() + $onYourOwnTwoStatus->getComplianceView()->getMaximumNumberOfPoints() + $onYourOwnThreeStatus->getComplianceView()->getMaximumNumberOfPoints()?></td>
                <td class="center">
                    <?php foreach($onYourOwnOneStatus->getComplianceView()->getLinks() as $link) {
                        echo $link->getHTML()."\n";
                    }?>
                </td>
            </tr>


            <tr>
                <th colspan="9" class="section"><span style="font-weight:bolder; font-size: 12pt;">WALKING PROGRAM</span><br />
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
                <td class="center"><?php echo $walkingStepOneStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $walkingStepOneStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $walkingStepOneStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $walkingStepOneStatus->getAttribute('quarter_4_points') ?></td>
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
                <td class="center"><?php echo $walkingStepTwoStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $walkingStepTwoStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $walkingStepTwoStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $walkingStepTwoStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $walkingStepTwoStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                </td>
            </tr>

            <tr>
                <td class="requirement">Walk an average of 15,000 steps/day </td>
                <td class="center"><?php echo $walkingStepThreeStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $walkingStepThreeStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $walkingStepThreeStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $walkingStepThreeStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $walkingStepThreeStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center"><?php echo $walkingStepThreeStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
            </tr>

            <tr>
                <td>
                    &nbsp;Beat the CFO Challenge<br /><br />
                    &nbsp;August 2018 <br />
                    &nbsp;November 2018 <br />
                    &nbsp;Mid January - Mid Febuary 2019
                </td>
                <td class="requirement">15 point each month you exceed the number of steps logged by CFO Keith Stenlund</td>
                <td class="center"><?php echo $beatCfoAugStatus->getComplianceView()->getAttribute('points_per_activity') ?></td>
                <td class="center"><?php echo $beatCfoAugStatus->getAttribute('quarter_1_points') + $beatCfoNovStatus->getAttribute('quarter_1_points') + $beatCfoJanuaryStatus->getAttribute('quarter_1_points') ?></td>
                <td class="center"><?php echo $beatCfoAugStatus->getAttribute('quarter_2_points') + $beatCfoNovStatus->getAttribute('quarter_2_points') + $beatCfoJanuaryStatus->getAttribute('quarter_2_points') ?></td>
                <td class="center"><?php echo $beatCfoAugStatus->getAttribute('quarter_3_points') + $beatCfoNovStatus->getAttribute('quarter_3_points') + $beatCfoJanuaryStatus->getAttribute('quarter_3_points') ?></td>
                <td class="center"><?php echo $beatCfoAugStatus->getAttribute('quarter_4_points') + $beatCfoNovStatus->getAttribute('quarter_4_points') + $beatCfoJanuaryStatus->getAttribute('quarter_4_points') ?></td>
                <td class="center">45</td>
                <td class="center">
                    <?php foreach($beatCfoAugStatus->getComplianceView()->getLinks() as $link) {
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
                <th colspan="9" class="section"><span style="font-weight:bolder; font-size: 12pt;">Quarterly and Total Points</span><br />
                </th>
            </tr>
            <tr class="headerRow headerRow-wellness_programs">
                <td class="center" colspan="2">Period</td>
                <td class="center" colspan="2">Status</td>
                <td class="center" colspan="2">Points Earned</td>
                <td class="center" colspan="3"></td>
            </tr>

            <tr>
                <td colspan="2" class="center">
                    <strong>By 3/1/18 – 5/30/18</strong>
                </td>
                <td class="center" colspan="2"><img src="/images/lights/<?php echo $status->getAttribute('quarter_1_points') >= 75 ? 'greenlight' : 'redlight' ?>.gif" class="light"/></td>
                <td class="center" colspan="2"><?php echo $status->getAttribute('quarter_1_points') ?></td>
                <td class="center" colspan="3"></td>
            </tr>
            <tr>
                <td colspan="2" class="center">
                    <strong>By 6/1/18 – 8/31/18</strong>
                </td>
                <td class="center" colspan="2"><img src="/images/lights/<?php echo $status->getAttribute('quarter_2_points') >= 40 ? 'greenlight' : 'redlight' ?>.gif" class="light"/></td>
                <td class="center" colspan="2"><?php echo $status->getAttribute('quarter_2_points') ?></td>
                <td class="center" colspan="3"></td>
            </tr>
            <tr>
                <td colspan="2" class="center">
                    <strong>By 9/1/18 – 11/30/18</strong>
                </td>
                <td class="center" colspan="2"><img src="/images/lights/<?php echo $status->getAttribute('quarter_3_points') >= 40 ? 'greenlight' : 'redlight' ?>.gif" class="light"/></td>
                <td class="center" colspan="2"><?php echo $status->getAttribute('quarter_3_points') ?></td>
                <td class="center" colspan="3"></td>
            </tr>
            <tr>
                <td colspan="2" class="center">
                    <strong>By 12/1/18 – 2/28/19</strong>
                </td>
                <td class="center" colspan="2"><img src="/images/lights/<?php echo $status->getAttribute('quarter_4_points') >= 40 ? 'greenlight' : 'redlight' ?>.gif" class="light"/></td>
                <td class="center" colspan="2"><?php echo $status->getAttribute('quarter_4_points') ?></td>
                <td class="center" colspan="3"></td>
            </tr>
            </tbody>
        </table>

        <?php
    }


    public $showUserNameInLegend = true;
}
