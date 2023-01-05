<?php

class FirstNationalBankofOttawa2020StepsComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $threshold, $pointsPer, $activityId, $questionId)
    {
        $this->setDateRange($startDate, $endDate);
        $this->threshold = $threshold;
        $this->pointsPer = $pointsPer;
        $this->activityId = $activityId;
        $this->questionId = $questionId;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return "hmi_multi_challenge_{$this->threshold}";
    }

    public function getDefaultReportName()
    {
        return "HMI Multi Challenge ({$this->threshold})";
    }

    public function getStatus(User $user)
    {

        $records = $this->getRecords($user);

        $manualSteps = [];
        foreach($records as $record) {
            $recordDate = date('Y-m-d', strtotime($record->getDate()));
            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->questionId])) {
                if (isset($manualSteps[$recordDate])) {
                    $manualSteps[$recordDate] += (int)$answers[$this->questionId]->getAnswer();
                } else {
                    $manualSteps[$recordDate] = (int)$answers[$this->questionId]->getAnswer();
                }
            }
        }

        $points = 0;
        foreach ($manualSteps as $entry) {
            if ($entry >= $this->threshold) $points += $this->pointsPer;
        }

        $status = new ComplianceViewStatus($this, null, floor($points));

        return $status;
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    private $threshold;
    private $pointsPer;
}

class FirstNationalBankofOttawa2020WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validWeeks = array(
        1 => '10/01/2019 through 10/06/2019',
        2 => '10/07/2019 through 10/13/2019',
        3 => '10/14/2019 through 10/20/2019',
        4 => '10/21/2019 through 10/27/2019',
        5 => '10/28/2019 through 11/03/2019',
        6 => '11/04/2019 through 11/10/2019',
        7 => '11/11/2019 through 11/17/2019',
        8 => '11/18/2019 through 11/24/2019',
        9 => '11/25/2019 through 12/01/2019',
        10 => '12/02/2019 through 12/08/2019',
        11 => '12/09/2019 through 12/15/2019',
        12 => '12/16/2019 through 12/22/2019',
        13 => '12/23/2019 through 12/29/2019',
        14 => '12/30/2019 through 01/05/2020',
        15 => '01/06/2020 through 01/12/2020',
        16 => '01/13/2020 through 01/19/2020',
        17 => '01/20/2020 through 01/26/2020',
        18 => '01/27/2020 through 02/02/2020',
        19 => '02/03/2020 through 02/09/2020',
        20 => '02/10/2020 through 02/16/2020',
        21 => '02/17/2020 through 02/23/2020',
        22 => '02/24/2020 through 03/01/2020',
        23 => '03/02/2020 through 03/08/2020',
        24 => '03/09/2020 through 03/15/2020',
        25 => '03/16/2020 through 03/22/2020',
        26 => '03/23/2020 through 03/29/2020',
        27 => '03/30/2020 through 04/05/2020',
        28 => '04/06/2020 through 04/12/2020',
        29 => '04/13/2020 through 04/19/2020',
        30 => '04/20/2020 through 04/26/2020',
        31 => '04/27/2020 through 05/03/2020',
        32 => '05/04/2020 through 05/10/2020',
        33 => '05/11/2020 through 05/17/2020',
        34 => '05/18/2020 through 05/24/2020',
        35 => '05/25/2020 through 05/31/2020',
        36 => '06/01/2020 through 06/07/2020',
        37 => '06/08/2020 through 06/14/2020',
        38 => '06/15/2020 through 06/21/2020',
        39 => '06/22/2020 through 06/28/2020',
        40 => '06/29/2020 through 07/05/2020',
        41 => '07/06/2020 through 07/12/2020',
        42 => '07/13/2020 through 07/19/2020',
        43 => '07/20/2020 through 07/26/2020',
        44 => '07/27/2020 through 08/02/2020',
        45 => '08/03/2020 through 08/09/2020',
        46 => '08/10/2020 through 08/16/2020',
        47 => '08/17/2020 through 08/23/2020',
        48 => '08/24/2020 through 08/30/2020',
        49 => '08/31/2020 through 09/06/2020',
        50 => '09/07/2020 through 09/13/2020',
        51 => '09/14/2020 through 09/20/2020',
        52 => '09/21/2020 through 09/27/2020',
    );

    public static function parseWeek($week)
    {
        return explode(',', str_replace(' through ', ',', $week));
    }

    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function __construct($startDate, $endDate, $activityId, $questionId, $pointPerWeek)
    {
        parent::__construct($startDate, $endDate);

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->activityId = $activityId;
        $this->questionId = $questionId;
        $this->pointPerWeek = $pointPerWeek;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->startDate, $this->endDate);

        $byWeek = array();

        foreach ($records as $record) {
            $answers = $record->getQuestionAnswers();

            if (isset($answers[$this->questionId])) {
                $byWeek[$answers[$this->questionId]->getAnswer()] = $answers[$this->questionId]->getAnswer();
            }
        }

        $points = 0;

        foreach(self::$validWeeks as $text) {
            if (isset($byWeek[$text])) {
                $points += $this->pointPerWeek;
            }
        }

        return new ComplianceViewStatus($this, null, $points);
    }


    private $activityId;
    private $questionId;
    private $pointPerWeek;
}

class FirstNationalBankofOttawa2020TobaccoFormComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->start_date = $startDate;
        $this->end_date = $endDate;
    }

    public function getDefaultName()
    {
        return 'non_smoker_view';
    }

    public function getDefaultReportName()
    {
        return 'Non Smoker';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $record = $user->getNewestDataRecord('midland_paper_tobacco_declaration');

        if($record->exists()
            && $record->agree
            && date('Y-m-d', strtotime($record->date)) >=  $this->start_date
            && date('Y-m-d', strtotime($record->date)) <=  $this->end_date) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, 50);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT, 0);
        }
    }
}
class FirstNationalBankofOttawa2020ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('Quarter 1 Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_1_points');
        });

        $printer->addStatusFieldCallback('Quarter 2 Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_2_points');
        });

        $printer->addStatusFieldCallback('Quarter 3 Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_3_points');
        });

        $printer->addStatusFieldCallback('Quarter 4 Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_4_points');
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $quarterlyDateRange = $this->getQuerterlyRanges();

        // Build the core group

        $numbers = new ComplianceViewGroup('points', 'Gain points through actions in order to earn the quarterly reward.');

        $screeningView = new PlaceHolderComplianceView(null, 0);
        $screeningView->setReportName('Participate in a Biometric Screening*');
        $screeningView->setName('screening');
        $screeningView->setMaximumNumberOfPoints(60);
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1114'));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $screeningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteScreeningComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(60);
                    }
                    $status->setAttribute($quarterName, 60);
                    $pointAdded = true;
                }
            }
        });
        $numbers->addComplianceView($screeningView);

        $bloodPressureView = new PlaceHolderComplianceView(null, 0);
        $bloodPressureView->setReportName('Blood Pressure (both numbers) ≤ 140/90');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setMaximumNumberOfPoints(50);
        $bloodPressureView->emptyLinks();
        $bloodPressureView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
                $alternative->overrideSystolicTestRowData(null, null, 140, null);
                $alternative->overrideDiastolicTestRowData(null, null, 90, null);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(50);
                    }
                    $status->setAttribute($quarterName, 50);
                    $pointAdded = true;
                }
            }
        });
        $numbers->addComplianceView($bloodPressureView);

        $ldlView = new PlaceHolderComplianceView(null, 0);
        $ldlView->setReportName('LDL Cholesterol ≤ 130');
        $ldlView->setName('ldl');
        $ldlView->setMaximumNumberOfPoints(50);
        $ldlView->emptyLinks();
        $ldlView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
                $alternative->overrideTestRowData(null, null, 130, null);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(50);
                    }
                    $status->setAttribute($quarterName, 50);
                    $pointAdded = true;
                }
            }
        });
        $numbers->addComplianceView($ldlView);

        $glucoseView = new PlaceHolderComplianceView(null, 0);
        $glucoseView->setReportName('Glucose ≤ 110');
        $glucoseView->setName('glucose');
        $glucoseView->setMaximumNumberOfPoints(50);
        $glucoseView->emptyLinks();
        $glucoseView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
                $alternative->overrideTestRowData(null, null, 110, null);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(50);
                    }
                    $status->setAttribute($quarterName, 50);
                    $pointAdded = true;
                }
            }
        });
        $numbers->addComplianceView($glucoseView);


        $bmiWaistView = new PlaceHolderComplianceView(null, 0);
        $bmiWaistView->setReportName('•	BMI ≤ 30 OR <br />•	Waist Circumference ≤ 40 (Men) ≤ 35 (Women)');
        $bmiWaistView->setName('bmi');
        $bmiWaistView->setMaximumNumberOfPoints(50);
        $bmiWaistView->emptyLinks();
        $bmiWaistView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new ComplyWithBMIScreeningTestComplianceView($startDate, $endDate);
                $alternative->overrideTestRowData(null, null, 30, null);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(50);
                    }
                    $status->setAttribute($quarterName, 50);
                    $pointAdded = true;
                } else {
                    $alternative = new ComplyWithWaistScreeningTestComplianceView($startDate, $endDate);
                    $alternative->overrideTestRowData(null, null, 40, null, 'M');
                    $alternative->overrideTestRowData(null, null, 35, null, 'F');
                    $alternative->setUseOverrideCreatedDate(true);
                    $alternativeStatus = $alternative->getStatus($user);

                    if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                        if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                            $status->setPoints(50);
                        }
                        $status->setAttribute($quarterName, 50);
                        $pointAdded = true;
                    }
                }
            }
        });
        $numbers->addComplianceView($bmiWaistView);

        $cotinineView = new PlaceHolderComplianceView(null, 0);
        $cotinineView->setReportName('Cotinine – Pass or Fail');
        $cotinineView->setName('cotinine');
        $cotinineView->setMaximumNumberOfPoints(50);
        $cotinineView->emptyLinks();
        $cotinineView->addLink(new Link('Affidavit', '/content/83525'));
        $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $record = $user->getNewestDataRecord('midland_paper_tobacco_declaration');

                if($record->exists()
                    && $record->agree
                    && date('Y-m-d', strtotime($record->date)) >=  date('Y-m-d', strtotime($startDate))
                    && date('Y-m-d', strtotime($record->date)) <=  date('Y-m-d', strtotime($endDate))) {

                    $status->setPoints(50);
                    $status->setAttribute($quarterName, 50);
                    $pointAdded = true;
                }
            }
        });
        $numbers->addComplianceView($cotinineView);

        $hraView = new PlaceHolderComplianceView(null, 0);
        $hraView->setName('complete_hra');
        $hraView->setReportName('Complete the Health Power Assessment*');
        $hraView->setMaximumNumberOfPoints(50);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HPA', '/content/989'));
        $hraView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $pointAdded = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                if($pointAdded) continue;
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteHRAComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints(50);
                    }
                    $status->setAttribute($quarterName, 50);
                    $pointAdded = true;
                }
            }
        });
        $numbers->addComplianceView($hraView);




        $elearn = new PlaceHolderComplianceView(null, 0);
        $elearn->setReportName('Complete E-Learning Lessons – 10 points per lesson');
        $elearn->setName('elearning');
        $elearn->setMaximumNumberOfPoints(50);
        $elearn->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningLessonsComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(10);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 50) {
                    $points = 50;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $elearn->emptyLinks();
        $elearn->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $numbers->addComplianceView($elearn);


        $steps10K = new PlaceHolderComplianceView(null, 0);
        $steps10K->setReportName('Daily Steps – 5 points per 10,000 steps ');
        $steps10K->setName('daily_steps_10000');
        $steps10K->setMaximumNumberOfPoints(200);
        $steps10K->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new FirstNationalBankofOttawa2020StepsComplianceView($startDate, $endDate, 10000, 5, 414, 110);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 200) {
                    $points = 200;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $steps10K->addLink(new Link('Enter/Update Info <br />', '/content/12048?action=showActivity&activityidentifier=414'));
        $numbers->addComplianceView($steps10K);


        $preventiveExamsView = new PlaceHolderComplianceView(null, 0);
        $preventiveExamsView->setReportName('Annual Physical/Preventive Exams Obtained – 25 points per exam');
        $preventiveExamsView->setMaximumNumberOfPoints(50);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 5172, 25);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 50) {
                    $points = 50;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $preventiveExamsView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=5172'));
        $numbers->addComplianceView($preventiveExamsView);


        $physicalActivityView = new PlaceHolderComplianceView(null, 0);
        $physicalActivityView->setReportName('Regular Physical Activity – 5 points for each hour of activity');
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setMaximumNumberOfPoints(200);
//        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new PhysicalActivityComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setMinutesDivisorForPoints(12);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 200) {
                    $points = 200;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $physicalActivityView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=21'));
        $numbers->addComplianceView($physicalActivityView);


        $vegetable = new PlaceHolderComplianceView(null, 0);
        $vegetable->setMaximumNumberOfPoints(200);
        $vegetable->setName('vegetable');
        $vegetable->setReportName('Eat 10 servings of fruit/vegetables in a week – 5 points');
        $vegetable->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new FirstNationalBankofOttawa2020WeeklyLogComplianceView($startDate, $endDate, 4481, 215, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 200) {
                    $points = 200;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $vegetable->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=4481'));
        $numbers->addComplianceView($vegetable);


        $sleep = new PlaceHolderComplianceView(null, 0);
        $sleep->setMaximumNumberOfPoints(200);
        $sleep->setName('sleep');
        $sleep->setReportName('Get at least 7 hours of restful sleep per night for a week - 5 points');
        $sleep->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new FirstNationalBankofOttawa2020WeeklyLogComplianceView($startDate, $endDate, 4482, 215, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getPoints() > 200) {
                    $points = 200;
                } else {
                    $points = $alternativeStatus->getPoints();
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $sleep->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=4482'));
        $numbers->addComplianceView($sleep);


        $dentalExam = new PlaceHolderComplianceView(null, 0);
        $dentalExam->setMaximumNumberOfPoints(50);
        $dentalExam->setReportName('Dental Exam - 25 points per exam');
        $dentalExam->setName('annual_dental_exam');
        $dentalExam->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 5171, 25);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();

                if($points > 0) {
                    if($points > 50) {
                        $points = 50;
                    }

                    if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                        $status->setPoints($points);
                    }
                    $status->setAttribute($quarterName, $points);
                }
            }
        });
        $dentalExam->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=5171'));
        $numbers->addComplianceView($dentalExam);



        $numbers->setPointsRequiredForCompliance(250);

        $this->addComplianceViewGroup($numbers);
    }

    protected function getQuerterlyRanges()
    {
        $ranges = array(
            'quarter1' => array('2019-10-01', '2019-12-31'),
            'quarter2' => array('2020-01-01', '2020-03-31'),
            'quarter3' => array('2020-04-01', '2020-06-30'),
            'quarter4' => array('2020-07-01', '2020-09-22'),
        );

        return $ranges;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $pointStatus = $status->getComplianceViewGroupStatus('points');

        $quarter1Points = 0;
        $quarter2Points = 0;
        $quarter3Points = 0;
        $quarter4Points = 0;
        foreach($pointStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getAttribute('quarter1')) {
                $quarter1Points += $viewStatus->getAttribute('quarter1');
            }

            if($viewStatus->getAttribute('quarter2')) {
                $quarter2Points += $viewStatus->getAttribute('quarter2');
            }

            if($viewStatus->getAttribute('quarter3')) {
                $quarter3Points += $viewStatus->getAttribute('quarter3');
            }

            if($viewStatus->getAttribute('quarter4')) {
                $quarter4Points += $viewStatus->getAttribute('quarter4');
            }
        }

        $status->setAttribute('quarter_1_points', $quarter1Points);
        $status->setAttribute('quarter_2_points', $quarter2Points);
        $status->setAttribute('quarter_3_points', $quarter3Points);
        $status->setAttribute('quarter_4_points', $quarter4Points);
        $status->setAttribute('total_points', $quarter1Points + $quarter2Points + $quarter3Points + $quarter4Points);
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
            $printer = new FirstNationalBankofOttawa2020ComplianceProgramReportPrinter();
            $printer->setShowTotal(false);
        }

        return $printer;
    }

    public function loadCompletedLessons($status, $user)
    {
        if($elearningLessonId = $status->getComplianceView()->getAttribute('elearning_lesson_id')) {
            $elearningView = new CompleteELearningLessonComplianceView($this->getStartDate(), $this->getEndDate(), new ELearningLesson_v2($elearningLessonId));

            $elearningStatus = $elearningView->getStatus($user);
            if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        }
    }
}


class FirstNationalBankofOttawa2020ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>


        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>
            Welcome to your Wellness Rewards Program at The First National Bank of Ottawa. In 2020, employees who
            completed the health screening and assessment in October 2019 will receive the same incentive on their health
            premium they received last year. As a further incentive, those employees who also earn at least 400 points
            throughout the year will receive a 5% premium discount.
        </p>

        <p>
            The activities below have all been assigned point values. There are one-time point earning opportunities*
            as well as daily point earning options.
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>

        <script type="text/javascript">
            $(function() {
                $('#legend tr td').children(':eq(2)').remove();
                $('#legend tr td').children(':eq(2)').remove();

                $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

                $('.headerRow-points').children(':eq(2)').html('Points Possible');

                $('.view-blood_pressure').children(':eq(0)').html('<span style="padding-left: 20px;">•	Blood Pressure (both numbers) ≤ 140/90</span>');
                $('.view-ldl').children(':eq(0)').html('<span style="padding-left: 20px;">•	LDL Cholesterol ≤ 130</span>');
                $('.view-glucose').children(':eq(0)').html('<span style="padding-left: 20px;">• Glucose ≤ 110</span>');
                $('.view-bmi').children(':eq(0)').html('<span style="padding-left: 20px;">• BMI ≤ 30 OR </span> <br /> <span style="padding-left: 20px;">• Waist Circumference ≤ 40 (Men) ≤ 35 (Women)</span>');
                $('.view-cotinine').children(':eq(0)').html('<span style="padding-left: 20px;">• Cotinine – Pass or Fail</span>');

                $('.view-screening').children('.links').attr('rowspan', 5);
                $('.view-blood_pressure').children('.links').remove();
                $('.view-ldl').children('.links').remove();
                $('.view-glucose').children('.links').remove();
                $('.view-bmi').children('.links').remove();

                $('.view-complete_hra').children(':eq(0)').html('<strong>B</strong>. Complete the Health Power Assessment*');
                $('.view-elearning').children(':eq(0)').html('<strong>C</strong>. Complete E-Learning Lessons – 10 points per lesson');
                $('.view-daily_steps_10000').children(':eq(0)').html('<strong>D</strong>. Daily Steps – 5 points per 10,000 steps');
                $('.view-do_preventive_exams').children(':eq(0)').html('<strong>E</strong>. Annual Physical/Preventive Exams Obtained – 25 points per exams');
                $('.view-physical_activity').children(':eq(0)').html('<strong>F</strong>. Regular Physical Activity – 5 points for each hour of activity');
                $('.view-vegetable').children(':eq(0)').html('<strong>G</strong>. Eat 10 servings of fruit/vegetables in a week – 5 points');
                $('.view-sleep').children(':eq(0)').html('<strong>H</strong>. Get at least 7 hours of restful sleep per night for a week - 5 points');
                $('.view-annual_dental_exam').children(':eq(0)').html('<strong>I</strong>. Dental Exam - 25 points per exam');



                $('.view-annual_dental_exam').after('<tr class="headerRow headerRow-footer"><td class="center">2. Deadlines, Requirements & Status </td><td>Total # Earned</td><td>Incentive Status</td><td>Minimum Points Needed</td></tr>')
                $('.headerRow-footer').after('<tr class="quarter_one"><td style="text-align: right;">Deadline 1: October 1 – December 31, 2019</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_1_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_1_points') >= 100 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">100</td></tr>')
                $('.quarter_one').after('<tr class="quarter_two"><td style="text-align: right;">Deadline 2: January 1 – March 31, 2020</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_2_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_2_points') >= 100 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">100</td></tr>')
                $('.quarter_two').after('<tr class="quarter_three"><td style="text-align: right;">Deadline 3: April 1 – June 30, 2020</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_3_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_3_points') >= 100 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">100</td></tr>')
                $('.quarter_three').after('<tr class="quarter_four"><td style="text-align: right;">Deadline 4: July 1 – September 22, 2020</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_4_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_4_points') >= 100 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">100</td></tr>')
                $('.quarter_four').after('<tr class="total_points"><td style="text-align: right;">Annual Point Total</td><td style="text-align: center;"><?php echo $status->getAttribute('total_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('total_points') >= 400 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">400</td></tr>')


            });
        </script>

        <?php

    }
}