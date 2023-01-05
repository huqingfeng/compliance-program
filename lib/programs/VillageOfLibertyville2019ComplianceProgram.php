<?php

class VillageOfLibertyville2019WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validWeeks = array(
        1 => '10/07/2019 through 10/13/2019',
        2 => '10/14/2019 through 10/20/2019',
        3 => '10/21/2019 through 10/27/2019',
        4 => '10/28/2019 through 11/03/2019',
        5 => '11/04/2019 through 11/10/2019',
        6 => '11/11/2019 through 11/17/2019',
        7 => '11/18/2019 through 11/24/2019',
        8 => '11/25/2019 through 12/01/2019',
        9 => '12/02/2019 through 12/08/2019',
        10 => '12/09/2019 through 12/15/2019',
        11 => '12/16/2019 through 12/22/2019',
        12 => '12/23/2019 through 12/29/2019',
        13 => '12/30/2019 through 01/05/2020',
        14 => '01/06/2020 through 01/12/2020',
        15 => '01/13/2020 through 01/19/2020',
        16 => '01/20/2020 through 01/26/2020',
        17 => '01/27/2020 through 02/02/2020',
        18 => '02/03/2020 through 02/09/2020',
        19 => '02/10/2020 through 02/16/2020',
        20 => '02/17/2020 through 02/23/2020',
        21 => '02/24/2020 through 03/01/2020',
        22 => '03/02/2020 through 03/08/2020',
        23 => '03/09/2020 through 03/15/2020',
        24 => '03/16/2020 through 03/22/2020',
        25 => '03/23/2020 through 03/29/2020'
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

class VillageOfLibertyville2019TobaccoFormComplianceView extends ComplianceView
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
class VillageOfLibertyville2019ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addEndStatusFieldCallBack('Points 10/02/2019 - 01/31/2020', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_1_points');
        });
        $printer->addEndStatusFieldCallBack('Points 02/01/2020 - 03/31/2020', function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_2_points');
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreEndDate = '2019-11-21';

        $quarterlyDateRange = $this->getQuerterlyRanges();

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'Areas A-C are required by November 21, 2019 in order to earn the $50 gift certificate.');

        $registerHMIView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT, 0);
        $registerHMIView->setReportName('Register with HMI');
        $registerHMIView->setName('register_hmi_site');
        $registerHMIView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($programStart, $coreEndDate) {
            if($user->created_at >= $programStart
                && $user->created_at <= $coreEndDate) {
                $status->setStatus(ComplianceViewStatus::COMPLIANT);
            }
        });
        $coreGroup->addComplianceView($registerHMIView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $coreEndDate);
        $screeningView->setReportName('Annual Wellness Screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/wms2-appointment-center'));
        $screeningView->addLink(new Link('Results', '/compliance/hmi-2016/my-health'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $coreEndDate);
        $hraView->setReportName('Complete the Health Power Assessment');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HPA', '/content/989'));
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $numbers = new ComplianceViewGroup('points', 'Gain 125 points through actions taken in option areas D-U by the below deadlines in order to earn the two additional $25 gift cards.');

        $elearn = new PlaceHolderComplianceView(null, 0);
        $elearn->setReportName('Complete e-Learning Lessons - 10 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setMaximumNumberOfPoints(50);
        $elearn->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningLessonsComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(5);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $numbers->addComplianceView($elearn);

        $bloodPressurElearning = new PlaceHolderComplianceView(null, 0);
        $bloodPressurElearning->setReportName('Complete Blood Pressure e-Learning Lessons - 10 pts for each lesson done');
        $bloodPressurElearning->setName('blood_pressure_elearning');
        $bloodPressurElearning->setMaximumNumberOfPoints(30);
        $bloodPressurElearning->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $view=  $status->getComplianceView();

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningGroupSet($startDate, $endDate, 'blood_pressure');
                $alternative->setComplianceViewGroup($view->getComplianceViewGroup());
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(10);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $bloodPressurElearning->addLink(new Link('View/Do Lessons', '/search-learn/elearning/content/9420?action=lessonManager&tab_alias[]=blood_pressure'));
        $numbers->addComplianceView($bloodPressurElearning);

        $bodyFatElearning = new PlaceHolderComplianceView(null, 0);
        $bodyFatElearning->setReportName('Complete Body Fat/BMI e-Learning Lessons - 10 pts for each lesson done');
        $bodyFatElearning->setName('bodyfat_elearning');
        $bodyFatElearning->setMaximumNumberOfPoints(30);
        $bodyFatElearning->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $view = $status->getComplianceView();

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningGroupSet($startDate, $endDate, 'body_fat');
                $alternative->setComplianceViewGroup($view->getComplianceViewGroup());
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(10);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $bodyFatElearning->addLink(new Link('View/Do Lessons', '/search-learn/elearning/content/9420?action=lessonManager&tab_alias[]=body_fat'));
        $numbers->addComplianceView($bodyFatElearning);

        $exerciseElearning = new PlaceHolderComplianceView(null, 0);
        $exerciseElearning->setReportName('Complete Exercise e-Learning Lessons - 10 pts for each lesson done');
        $exerciseElearning->setName('exercise_elearning');
        $exerciseElearning->setMaximumNumberOfPoints(30);
        $exerciseElearning->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $view = $status->getComplianceView();

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningGroupSet($startDate, $endDate, 'exercise_fitness_muscles');
                $alternative->setComplianceViewGroup($view->getComplianceViewGroup());
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(10);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $exerciseElearning->addLink(new Link('View/Do Lessons', '/search-learn/elearning/content/9420?action=lessonManager&tab_alias[]=exercise_fitness_muscles'));
        $numbers->addComplianceView($exerciseElearning);

        $physicalActivityView = new PlaceHolderComplianceView(null, 0);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each 30 minutes of activity');
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setMaximumNumberOfPoints(150);
//        $physicalActivityView->setMonthlyPointLimit(30);
        $physicalActivityView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new PhysicalActivityComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setMinutesDivisorForPoints(30);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $physicalActivityView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=21'));
        $numbers->addComplianceView($physicalActivityView);

        $steps8K = new PlaceHolderComplianceView(null, 0);
        $steps8K->setReportName('Daily Steps - 1 pt per 8,000 steps');
        $steps8K->setName('daily_steps_8000');
        $steps8K->setMaximumNumberOfPoints(100);
        $steps8K->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VillageOfLibertyville2017RangeStepsComplianceView($startDate, $endDate, 5000, null, 1);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $steps8K->addLink(new Link('Sync Fitbit <br />', '/content/ucan-fitbit-individual'));
        $numbers->addComplianceView($steps8K);

        $preventiveExamsView = new PlaceHolderComplianceView(null, 0);
        $preventiveExamsView->setReportName('Receive a Preventive Exam - 10 points per exam');
        $preventiveExamsView->setMaximumNumberOfPoints(50);
        $preventiveExamsView->setName('do_preventive_exams');
        $preventiveExamsView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 26, 10);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $preventiveExamsView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=26'));
        $numbers->addComplianceView($preventiveExamsView);

        $wellnessRun = new PlaceHolderComplianceView(null, 0);
        $wellnessRun->setMaximumNumberOfPoints(100);
        $wellnessRun->setReportName('Participate in a Wellness Run/Walk - 50 pts per activity *');
        $wellnessRun->setName('run_walk');
        $wellnessRun->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 548, 50);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $wellnessRun->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=548'));
        $numbers->addComplianceView($wellnessRun);

        $wellnessEvent = new PlaceHolderComplianceView(null, 0);
        $wellnessEvent->setMaximumNumberOfPoints(150);
        $wellnessEvent->setReportName('Attend a Sponsored Wellness Event - 50 pts per activity ** ');
        $wellnessEvent->setName('wellness_event');
        $wellnessEvent->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 424, 50);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $wellnessEvent->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=424'));
        $numbers->addComplianceView($wellnessEvent);

        $sportsComplex = new PlaceHolderComplianceView(null, 0);
        $sportsComplex->setMaximumNumberOfPoints(50);
        $sportsComplex->setReportName('Take a Class at the Sports Complex - 25 pts per class');
        $sportsComplex->setName('sports_complex');
        $sportsComplex->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 611, 25);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $sportsComplex->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=611'));
        $numbers->addComplianceView($sportsComplex);

        $water = new PlaceHolderComplianceView(null, 0);
        $water->setMaximumNumberOfPoints(50);
        $water->setName('water');
        $water->setReportName('Drink 6-8 glasses of pure water per day for a week - 5 pts per week');
        $water->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VillageOfLibertyville2019WeeklyLogComplianceView($startDate, $endDate, 613, 213, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $water->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=613'));
        $numbers->addComplianceView($water);

        $vegetable = new PlaceHolderComplianceView(null, 0);
        $vegetable->setMaximumNumberOfPoints(50);
        $vegetable->setName('vegetable');
        $vegetable->setReportName('Eat 1 serving of a vegetable a day for a week - 5 pts per week');
        $vegetable->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VillageOfLibertyville2019WeeklyLogComplianceView($startDate, $endDate, 620, 213, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $vegetable->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=620'));
        $numbers->addComplianceView($vegetable);

        $fruit = new PlaceHolderComplianceView(null, 0);
        $fruit->setMaximumNumberOfPoints(50);
        $fruit->setName('fruit');
        $fruit->setReportName('Eat 2 servings of fruit a day for a week - 5 pts per week');
        $fruit->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VillageOfLibertyville2019WeeklyLogComplianceView($startDate, $endDate, 612, 213, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $fruit->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=612'));
        $numbers->addComplianceView($fruit);

        $volunteeringView = new PlaceHolderComplianceView(null, 0);
        $volunteeringView->setReportName('Regular Volunteering - 1 pt for each hour of volunteering');
        $volunteeringView->setName('volunteering');
        $volunteeringView->setMaximumNumberOfPoints(25);
        $volunteeringView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VolunteeringComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setMinutesDivisorForPoints(60);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $volunteeringView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=24'));
        $numbers->addComplianceView($volunteeringView);

        $nonSmokerView = new PlaceHolderComplianceView(null, 0);
        $nonSmokerView->setReportName('Non-Smoker / Non-Tobacco User');
        $nonSmokerView->setName('non_smoker');
        $nonSmokerView->setPostEvaluateCallback(array($this, 'loadCompletedLessons'));
        $nonSmokerView->setAttribute('elearning_alias', 'tobacco');
        $nonSmokerView->emptyLinks();
        $nonSmokerView->addLink(new Link('Tobacco Form', '/content/83525'));
        $nonSmokerView->setMaximumNumberOfPoints(50);
        $nonSmokerView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $quarterOneEarned = false;

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VillageOfLibertyville2019TobaccoFormComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    if($quarterName == 'quarter1' && $alternativeStatus->getPoints() > 0) {
                        $quarterOneEarned = true;
                    }

                    if($quarterName == 'quarter1' || !$quarterOneEarned) {
                        $status->setPoints($alternativeStatus->getPoints());
                    }
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $numbers->addComplianceView($nonSmokerView);

        $fluVaccineView = new PlaceHolderComplianceView(null, 0);
        $fluVaccineView->setReportName('Annual Flu Vaccine');
        $fluVaccineView->setName('flu_vaccine');
        $fluVaccineView->setMaximumNumberOfPoints(25);
        $fluVaccineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $quarterOneEarned = false;
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 20, 25);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    if($quarterName == 'quarter1' && $alternativeStatus->getPoints() > 0) {
                        $quarterOneEarned = true;
                    }

                    if($quarterName == 'quarter1' || !$quarterOneEarned) {
                        $status->setPoints($alternativeStatus->getPoints());
                    }
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $fluVaccineView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=20'));
        $numbers->addComplianceView($fluVaccineView);

        $donateBlood = new PlaceHolderComplianceView(null, 0);
        $donateBlood->setMaximumNumberOfPoints(50);
        $donateBlood->setReportName('Donate Blood');
        $donateBlood->setName('donate_blood');
        $donateBlood->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $quarterOneEarned = false;

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 503, 50);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    if($quarterName == 'quarter1' && $alternativeStatus->getPoints() > 0) {
                        $quarterOneEarned = true;
                    }

                    if($quarterName == 'quarter1' || !$quarterOneEarned) {
                        $status->setPoints(max(0, $alternativeStatus->getPoints()));
                    }
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $donateBlood->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=503'));
        $numbers->addComplianceView($donateBlood);

        $doc = new PlaceHolderComplianceView(null, 0);
        $doc->setReportName('Have a Primary Care Doctor');
        $doc->setName('doctor');
        $doc->setMaximumNumberOfPoints(25);
        $doc->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $quarterOneEarned = false;

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new UpdateDoctorInformationComplianceView($startDate,$endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $alternativeStatus->setPoints(25);
                }

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    if($quarterName == 'quarter1' && $alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                        $quarterOneEarned = true;
                    }

                    if($quarterName == 'quarter1' || !$quarterOneEarned) {
                        $status->setPoints(max(0, $alternativeStatus->getPoints()));
                    }
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $doc->addLink(new Link('Enter/Update Info', '/compliance/hmi-2016/my-rewards/wms1/my_account/updateDoctor?redirect=/compliance_programs'));
        $numbers->addComplianceView($doc);

        $numbers->setPointsRequiredForCompliance(250);

        $this->addComplianceViewGroup($numbers);
    }

    protected function getQuerterlyRanges()
    {
        $ranges = array(
            'quarter1' => array('2019-10-02', '2020-01-31'),
            'quarter2' => array('2020-02-01', '2020-03-31')
        );

        return $ranges;
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $pointStatus = $status->getComplianceViewGroupStatus('points');

        $quarter1Points = 0;
        $quarter2Points = 0;
        foreach($pointStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getAttribute('quarter1')) {
                $quarter1Points += $viewStatus->getAttribute('quarter1');
            }

            if($viewStatus->getAttribute('quarter2')) {
                $quarter2Points += $viewStatus->getAttribute('quarter2');
            }
        }

        $status->setAttribute('quarter_1_points', $quarter1Points);
        $status->setAttribute('quarter_2_points', $quarter2Points);
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
            $printer = new VillageOfLibertyville2019ComplianceProgramReportPrinter();
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


class VillageOfLibertyville2019ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{

    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>

        <style type="text/css">

        </style>

        <script type="text/javascript">
            $(function() {
                $('#legend tr td').children(':eq(2)').remove();
                $('#legend tr td').children(':eq(2)').remove();

                $('.headerRow-points').next().next().children(':eq(3)').css('width', '200px');

                $('.view-elearning').children(':eq(0)').html('<strong>D.</strong> Complete e-Learning Lessons - 5 pts for each lesson done');
                $('.view-blood_pressure_elearning').children(':eq(0)').html('<strong>E.</strong> Complete Blood Pressure e-Learning Lessons - 10 pts for each lesson done');
                $('.view-bodyfat_elearning').children(':eq(0)').html('<strong>F.</strong> Complete Body Fat/BMI e-Learning Lessons - 10 pts for each lesson done');
                $('.view-exercise_elearning').children(':eq(0)').html('<strong>G.</strong> Complete Exercise e-Learning Lessons - 10 pts for each lesson done ');
                $('.view-physical_activity').children(':eq(0)').html('<strong>H.</strong> Regular Physical activity - 1 pt for each 30 minutes of activity');
                $('.view-daily_steps_8000').children(':eq(0)').html('<strong>I.</strong> Daily Steps - 1 pt per 8,000 steps');
                $('.view-do_preventive_exams').children(':eq(0)').html('<strong>J.</strong> Receive a Preventative Exam - 10 pts per exam');
                $('.view-run_walk').children(':eq(0)').html('<strong>K.</strong> Participate in a Wellness Run/Walk - 50 pts per activity*');
                $('.view-wellness_event').children(':eq(0)').html('<strong>L.</strong> Attend a Sponsored Wellness Event - 50 pts per activity**');
                $('.view-sports_complex').children(':eq(0)').html('<strong>M.</strong> Take a Class at the Sports Complex - 25 pts per class');
                $('.view-water').children(':eq(0)').html('<strong>N.</strong> Drink 6-8 glasses of pure water per day for a week - 5 pts per week');
                $('.view-vegetable').children(':eq(0)').html('<strong>O.</strong> Eat 1 serving of a vegetable a day for a week - 5 pts per week');
                $('.view-fruit').children(':eq(0)').html('<strong>P.</strong> Eat 2 servings of fruit a day for a week - 5 pts per week');
                $('.view-volunteering').children(':eq(0)').html('<strong>Q.</strong> Regular Volunteering - 1 pt for each hour of volunteering');
                $('.view-non_smoker').children(':eq(0)').html('<strong>R.</strong> Non-Smoker / Non-Tobacco User');
                $('.view-flu_vaccine').children(':eq(0)').html('<strong>S.</strong> Annual Flu Vaccine');
                $('.view-donate_blood').children(':eq(0)').html('<strong>T.</strong> Donate Blood');
                $('.view-doctor').children(':eq(0)').html('<strong>U.</strong> Have a Primary Care Doctor');




                $('.view-doctor').after('<tr class="headerRow headerRow-footer"><td class="center">Status of All Criteria = </td><td></td><td></td><td>Minimum Points Needed</td></tr>')
                $('.headerRow-footer').after('<tr class="quarter_one"><td style="text-align: right;">By 01/31/2020</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_1_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_1_points') >= 125 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">125</td></tr>')
                $('.quarter_one').after('<tr class="quarter_two"><td style="text-align: right;">By 03/31/2020</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_2_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_2_points') >= 125 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">125</td></tr>')



            });
        </script>

        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>Welcome to your summary page for the 2019-20 Wellness Rewards benefit at Village of Libertyville.</p>

        <p>You have the opportunity to earn $100 in gift cards. The deadline to complete all actions is March 31, 2020.</p>

        <p>
            To receive the initial $50 gift certificate, you MUST register with HMI, complete the annual wellness
            screening and complete the Health Power Assessment by November 21, 2019.
        </p>

        <p>
            To receive an additional $25 gift card, you MUST earn 125 or more points from key actions taken for good
            health by Jan 31, 2020.
        </p>

        <p>
            To receive the last $25 gift card, you MUST earn 125 or more points from key actions taken for good health
            by Mar 31, 2020.
        </p>

        <p>
            New this year, all those who actively participate in the program through March 31, 2020, and earn the minimum
            point requirements for each deadline (Screening/Assessment, January 31, 2020 and March 31, 2020) will
            automatically be entered into a raffle to win a Grand Prize $200 gift card!
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>


        <div style="margin-top: 20px;">
            <div style="float: left; margin-right: 160px;">
                * Run/Walk Examples <br /><br />
                - Heart Walk <br />
                - Walk to Cure Diabetes <br />
                - Libertyville Twilight Shuffle <br />
                - Participate in a Wellness Walk/Run of your choice
            </div>

            <div style="float: left;">
                ** Sponsored Wellness Events <br /><br />
                - Wellness & Benefit Fair (11/12/2019) <br />
                - Lunch & Learn (Fall/Winter 2019) <br />
                - Lunch & Learn (Spring 2020)
            </div>
        </div>

        <div style="clear: both"></div>

        <?php

    }
}