<?php

class FirstNationalBankofOttawa2021StepsComplianceView extends DateBasedComplianceView
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

class FirstNationalBankofOttawa2021WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validWeeks = array(
        1 => '09/21/2020 through 09/27/2020',
        2 => '09/28/2020 through 10/04/2020',
        3 => '10/05/2020 through 10/11/2020',
        4 => '10/12/2020 through 10/18/2020',
        5 => '10/19/2020 through 10/25/2020',
        6 => '10/26/2020 through 11/01/2020',
        7 => '11/02/2020 through 11/08/2020',
        8 => '11/09/2020 through 11/15/2020',
        9 => '11/16/2020 through 11/22/2020',
        10 => '11/23/2020 through 11/29/2020',
        11 => '11/30/2020 through 12/06/2020',
        12 => '12/07/2020 through 12/13/2020',
        13 => '12/14/2020 through 12/20/2020',
        14 => '12/21/2020 through 12/27/2020',
        15 => '12/28/2020 through 01/03/2021',
        16 => '01/04/2021 through 01/10/2021',
        17 => '01/11/2021 through 01/17/2021',
        18 => '01/18/2021 through 01/24/2021',
        19 => '01/25/2021 through 01/31/2021',
        20 => '02/01/2021 through 02/07/2021',
        21 => '02/08/2021 through 02/14/2021',
        22 => '02/15/2021 through 02/21/2021',
        23 => '02/22/2021 through 02/28/2021',
        24 => '03/01/2021 through 03/07/2021',
        25 => '03/08/2021 through 03/14/2021',
        26 => '03/15/2021 through 03/21/2021',
        27 => '03/22/2021 through 03/28/2021',
        28 => '03/29/2021 through 04/04/2021',
        29 => '04/05/2021 through 04/11/2021',
        30 => '04/12/2021 through 04/18/2021',
        31 => '04/19/2021 through 04/25/2021',
        32 => '04/26/2021 through 05/02/2021',
        33 => '05/03/2021 through 05/09/2021',
        34 => '05/10/2021 through 05/16/2021',
        35 => '05/17/2021 through 05/23/2021',
        36 => '05/24/2021 through 05/30/2021',
        37 => '05/31/2021 through 06/06/2021',
        38 => '06/07/2021 through 06/13/2021',
        39 => '06/14/2021 through 06/20/2021',
        40 => '06/21/2021 through 06/27/2021',
        41 => '06/28/2021 through 07/04/2021',
        42 => '07/05/2021 through 07/11/2021',
        43 => '07/12/2021 through 07/18/2021',
        44 => '07/19/2021 through 07/25/2021',
        45 => '07/26/2021 through 08/01/2021',
        46 => '08/02/2021 through 08/08/2021',
        47 => '08/09/2021 through 08/15/2021',
        48 => '08/16/2021 through 08/22/2021',
        49 => '08/23/2021 through 08/29/2021',
        50 => '08/30/2021 through 09/05/2021',
        51 => '09/06/2021 through 09/12/2021',
        52 => '09/13/2021 through 09/19/2021',
        53 => '09/20/2021 through 09/26/2021',
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

class FirstNationalBankofOttawa2021TobaccoFormComplianceView extends ComplianceView
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
class FirstNationalBankofOttawa2021ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('Section 1 Total Points', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('section_one_points');
        });

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

        $numbers = new ComplianceViewGroup('points', 'Section 1 - Screening - If an employee obtains 385 points in this area, they will receive the 5% incentive and requirements for the year are complete.');

        $screeningView = new PlaceHolderComplianceView(null, 0);
        $screeningView->setReportName('Participate in a Biometric Screening*');
        $screeningView->setName('screening');
        $screeningView->setMaximumNumberOfPoints(60);
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/1114'));
        $screeningView->addLink(new Link('Results', '/content/989'));
        $screeningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $overridePointAdded = false;
            $pointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $status->setAttribute('total_points', $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                        $status->setAttribute('total_points', $points);
                    }
                }
            } else {
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
            }

            if($pointAdded) {
                $status->setAttribute('total_points', 60);
            }
        });
        $numbers->addComplianceView($screeningView);

        $bloodPressureView = new PlaceHolderComplianceView(null, 0);
        $bloodPressureView->setReportName('Blood Pressure (both numbers) ≤ 140/90');
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setMaximumNumberOfPoints(50);
        $bloodPressureView->emptyLinks();
        $bloodPressureView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $overridePointAdded = false;
            $pointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $status->setAttribute('total_points', $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                        $status->setAttribute('total_points', $points);
                    }
                }
            } else {
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
            }

            if($pointAdded) {
                $status->setAttribute('total_points', 50);
            }
        });
        $numbers->addComplianceView($bloodPressureView);

        $ldlView = new PlaceHolderComplianceView(null, 0);
        $ldlView->setReportName('LDL Cholesterol ≤ 130');
        $ldlView->setName('ldl');
        $ldlView->setMaximumNumberOfPoints(50);
        $ldlView->emptyLinks();
        $ldlView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $overridePointAdded = false;
            $pointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $status->setAttribute('total_points', $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                        $status->setAttribute('total_points', $points);
                    }
                }
            } else {
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
            }

            if($pointAdded) {
                $status->setAttribute('total_points', 50);
            }
        });
        $numbers->addComplianceView($ldlView);

        $glucoseView = new PlaceHolderComplianceView(null, 0);
        $glucoseView->setReportName('Glucose ≤ 110');
        $glucoseView->setName('glucose');
        $glucoseView->setMaximumNumberOfPoints(50);
        $glucoseView->emptyLinks();
        $glucoseView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $overridePointAdded = false;
            $pointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $status->setAttribute('total_points', $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                        $status->setAttribute('total_points', $points);
                    }
                }
            } else {
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
            }

            if($pointAdded) {
                $status->setAttribute('total_points', 50);
            }
        });
        $numbers->addComplianceView($glucoseView);


        $bmiWaistView = new PlaceHolderComplianceView(null, 0);
        $bmiWaistView->setReportName('•	BMI ≤ 30 OR <br />•	Waist Circumference ≤ 40 (Men) ≤ 35 (Women)');
        $bmiWaistView->setName('bmi');
        $bmiWaistView->setMaximumNumberOfPoints(50);
        $bmiWaistView->emptyLinks();
        $bmiWaistView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $overridePointAdded = false;
            $pointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $status->setAttribute('total_points', $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                        $status->setAttribute('total_points', $points);
                    }
                }
            } else {
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
            }

            if($pointAdded) {
                $status->setAttribute('total_points', 50);
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
            $overridePointAdded = false;
            $pointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $status->setAttribute('total_points', $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                        $status->setAttribute('total_points', $points);
                    }
                }
            } else {
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
            }

            if($pointAdded) {
                $status->setAttribute('total_points', 50);
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
            $overridePointAdded = false;
            $pointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $status->setAttribute('total_points', $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                        $status->setAttribute('total_points', $points);
                    }
                }
            } else {
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
            }


            if($pointAdded) {
                $status->setAttribute('total_points', 50);
            }
        });
        $numbers->addComplianceView($hraView);

        $dentalExam = new PlaceHolderComplianceView(null, 0);
        $dentalExam->setMaximumNumberOfPoints(25);
        $dentalExam->setReportName('Dental Exam - 25 points per exam');
        $dentalExam->setName('annual_dental_exam');
        $dentalExam->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $overridePointAdded = false;
            $pointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $status->setAttribute('total_points', $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                        $status->setAttribute('total_points', $points);
                    }
                }
            } else {
                foreach($quarterlyDateRange as $quarterName => $dateRange) {
                    if($pointAdded) continue;
                    $startDate = $dateRange[0];
                    $endDate =  $dateRange[1];

                    $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 5171, 25);
                    $alternative->setUseOverrideCreatedDate(true);
                    $alternativeStatus = $alternative->getStatus($user);
                    $points = $alternativeStatus->getPoints();

                    if($points > 0) {
                        if($points > 25) {
                            $points = 25;
                        }

                        if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                            $status->setPoints($points);
                        }
                        $status->setAttribute($quarterName, $points);
                        $pointAdded = true;
                    }
                }
            }

            if($pointAdded) {
                $status->setAttribute('total_points', 25);
            }
        });
        $dentalExam->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=5171'));
        $numbers->addComplianceView($dentalExam);


        $elearn = new PlaceHolderComplianceView(null, 0);
        $elearn->setReportName('Complete E-Learning Lessons – 10 points per lesson');
        $elearn->setName('elearning');
        $elearn->setMaximumNumberOfPoints(50);
        $elearn->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $overridePointAdded = false;
            if($status->getUsingOverride()) {
                $points = $status->getPoints();
                $date = $status->getComment();

                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                    }
                }
            } else {
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
            if($status->getUsingOverride()) {
                $overridePointAdded = false;
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                    }
                }
            } else {
                foreach($quarterlyDateRange as $quarterName => $dateRange) {
                    $startDate = $dateRange[0];
                    $endDate =  $dateRange[1];

                    $alternative = new FirstNationalBankofOttawa2021StepsComplianceView($startDate, $endDate, 10000, 5, 414, 110);
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
            }
        });
        $steps10K->addLink(new Link('Enter/Update Info <br />', '/content/12048?action=showActivity&activityidentifier=414'));
        $numbers->addComplianceView($steps10K);


        $preventiveExamsView = new PlaceHolderComplianceView(null, 0);
        $preventiveExamsView->setReportName('Annual Physical/Preventive Exams Obtained – 25 points per exam');
        $preventiveExamsView->setMaximumNumberOfPoints(50);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            if($status->getUsingOverride()) {
                $overridePointAdded = false;
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                    }
                }
            } else {
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
            if($status->getUsingOverride()) {
                $overridePointAdded = false;
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                    }
                }
            } else {
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
            }

        });
        $physicalActivityView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=21'));
        $numbers->addComplianceView($physicalActivityView);


        $vegetable = new PlaceHolderComplianceView(null, 0);
        $vegetable->setMaximumNumberOfPoints(200);
        $vegetable->setName('vegetable');
        $vegetable->setReportName('Eat 10 servings of fruit/vegetables in a week – 5 points');
        $vegetable->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            if($status->getUsingOverride()) {
                $overridePointAdded = false;
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                    }
                }
            } else {
                foreach($quarterlyDateRange as $quarterName => $dateRange) {
                    $startDate = $dateRange[0];
                    $endDate =  $dateRange[1];

                    $alternative = new FirstNationalBankofOttawa2021WeeklyLogComplianceView($startDate, $endDate, 4481, 215, 5);
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
            }
        });
        $vegetable->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=4481'));
        $numbers->addComplianceView($vegetable);


        $sleep = new PlaceHolderComplianceView(null, 0);
        $sleep->setMaximumNumberOfPoints(200);
        $sleep->setName('sleep');
        $sleep->setReportName('Get at least 7 hours of restful sleep per night for a week - 5 points');
        $sleep->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            if($status->getUsingOverride()) {
                $overridePointAdded = false;
                $points = $status->getPoints();
                $date = $status->getComment();
                if($points) {
                    if($date) {
                        foreach($quarterlyDateRange as $quarterName => $dateRange) {
                            $startDate = $dateRange[0];
                            $endDate =  $dateRange[1];

                            if(!$overridePointAdded) {
                                if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                                    $status->setAttribute($quarterName, $points);
                                    $overridePointAdded = true;
                                }
                            }
                        }
                    }

                    if(!$overridePointAdded) {
                        $status->setAttribute('quarter1', $points);
                    }
                }
            } else {
                foreach($quarterlyDateRange as $quarterName => $dateRange) {
                    $startDate = $dateRange[0];
                    $endDate =  $dateRange[1];

                    $alternative = new FirstNationalBankofOttawa2021WeeklyLogComplianceView($startDate, $endDate, 4482, 215, 5);
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
            }
        });
        $sleep->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=4482'));
        $numbers->addComplianceView($sleep);




        $numbers->setPointsRequiredForCompliance(385);

        $this->addComplianceViewGroup($numbers);
    }

    protected function getQuerterlyRanges()
    {
        $ranges = array(
            'quarter1' => array('2020-09-23', '2020-12-31'),
            'quarter2' => array('2021-01-01', '2021-03-31'),
            'quarter3' => array('2021-04-01', '2021-06-30'),
            'quarter4' => array('2021-07-01', '2021-09-30'),
        );

        return $ranges;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $pointStatus = $status->getComplianceViewGroupStatus('points');

        $sectionOnePoints = 0;
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

            if($viewStatus->getAttribute('total_points')) {
                $sectionOnePoints += $viewStatus->getAttribute('total_points');
            }
        }

        $status->setAttribute('quarter_1_points', $quarter1Points);
        $status->setAttribute('quarter_2_points', $quarter2Points);
        $status->setAttribute('quarter_3_points', $quarter3Points);
        $status->setAttribute('quarter_4_points', $quarter4Points);
        $status->setAttribute('section_one_points', $sectionOnePoints);
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
            $printer = new FirstNationalBankofOttawa2021ComplianceProgramReportPrinter();
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


class FirstNationalBankofOttawa2021ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>


        <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>
            Welcome to your Wellness Rewards Program at The First National Bank of Ottawa! In 2021, employees who complete
            the health screening and assessment during the September 30th - October 16th timeframe will receive the same
            incentives on their health premium they received last year. As a further incentive, those employees who also
            earn 385 points in Section 1 (Screening) will receive a 5% premium discount.
        </p>

        <p>
            If you do not earn 385 points in Section 1 (Screening), you can still qualify for the 5% premium discount by
            earning 440 total points combined between Section 1 (Screening) and Section 2 (Wellness Activity).
            <strong>You will need to meet the deadlines and minimum points required at the bottom of this page.</strong>
        </p>

        <p>
            <strong>Section 1 – Screening</strong> - If an employee obtains 385 points in this area, they will receive
            the 5% incentive and requirements for the year are complete.
        </p>

        <p>
            <strong>Section 2 – Wellness Activity</strong> - If an employee did not meet the 385 points in Section 1,
            they can continue earning points until they have reached 440 points for the year. Points from each section
            will be combined towards the 440 point goal. Also, there are minimum points needed for each deadline to
            obtain the 5% discount.
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
                $('.view-annual_dental_exam').children(':eq(0)').html('<strong>C</strong>. Dental Exam - 25 points per exam*');
                $('.view-elearning').children(':eq(0)').html('<strong>A</strong>. Complete E-Learning Lessons – 10 points per lesson');
                $('.view-daily_steps_10000').children(':eq(0)').html('<strong>B</strong>. Daily Steps – 5 points per 10,000 steps');
                $('.view-do_preventive_exams').children(':eq(0)').html('<strong>C</strong>. Annual Physical/Preventive Exams Obtained – 25 points per exams');
                $('.view-physical_activity').children(':eq(0)').html('<strong>D</strong>. Regular Physical Activity – 5 points for each hour of activity');
                $('.view-vegetable').children(':eq(0)').html('<strong>E</strong>. Eat 10 servings of fruit/vegetables in a week – 5 points');
                $('.view-sleep').children(':eq(0)').html('<strong>F</strong>. Get at least 7 hours of restful sleep per night for a week - 5 points');



                $('.view-annual_dental_exam').after('<tr style="height: 20px;"><td colspan="4" style="border: none !important;"></td></tr><tr class="headerRow headerRow-second"><td class="center">Section 2 – Wellness Activity - If an employee did not meet the 385 points in Section 1, they can continue earning points until they have reached 440 points for the year. Points from each section will be combined towards the 440 point goal. Also, there are minimum points needed for each deadline to obtain the 5% discount.</td><td># Points Earned</td><td>Points Possible</td><td>Links</td></tr>');
                $('.view-sleep').after('<tr class="headerRow headerRow-footer"><td class="center">2. Deadlines, Requirements & Status </td><td>Total # Earned</td><td>Incentive Status</td><td>Minimum Points Needed</td></tr>')
                $('.headerRow-footer').after('<tr class="quarter_one"><td style="text-align: right;">Deadline 1: September 23 – December 31, 2020</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_1_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_1_points') >= 110 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">110</td></tr>')
                $('.quarter_one').after('<tr class="quarter_two"><td style="text-align: right;">Deadline 2: January 1 – March 31, 2021</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_2_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_2_points') >= 110 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">110</td></tr>')
                $('.quarter_two').after('<tr class="quarter_three"><td style="text-align: right;">Deadline 3: April 1 – June 30, 2021</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_3_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_3_points') >= 110 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">110</td></tr>')
                $('.quarter_three').after('<tr class="quarter_four"><td style="text-align: right;">Deadline 4: July 1 – September 30, 2021</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_4_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_4_points') >= 110 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">110</td></tr>')
                $('.quarter_four').after('<tr class="total_points"><td style="text-align: right;">Annual Point Total</td><td style="text-align: center;"><?php echo $status->getAttribute('total_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('total_points') >= 440 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">440</td></tr>')


            });
        </script>

        <?php

    }
}