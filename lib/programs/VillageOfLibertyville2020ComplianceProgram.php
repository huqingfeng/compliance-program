<?php

class VillageOfLibertyville2020WeeklyLogComplianceView extends CompleteActivityComplianceView
{
    static $validWeeks = array(
        1 => '09/28/2020 through 10/04/2020',
        2 => '10/05/2020 through 10/11/2020',
        3 => '10/12/2020 through 10/18/2020',
        4 => '10/19/2020 through 10/25/2020',
        5 => '10/26/2020 through 11/01/2020',
        6 => '11/02/2020 through 11/08/2020',
        7 => '11/09/2020 through 11/15/2020',
        8 => '11/16/2020 through 11/22/2020',
        9 => '11/23/2020 through 11/29/2020',
        10 => '11/30/2020 through 12/06/2020',
        11 => '12/07/2020 through 12/13/2020',
        12 => '12/14/2020 through 12/20/2020',
        13 => '12/21/2020 through 12/27/2020',
        14 => '12/28/2020 through 01/03/2021',
        15 => '01/04/2021 through 01/10/2021',
        16 => '01/11/2021 through 01/17/2021',
        17 => '01/18/2021 through 01/24/2021',
        18 => '01/25/2021 through 01/31/2021',
        19 => '02/01/2021 through 02/07/2021',
        20 => '02/08/2021 through 02/14/2021',
        21 => '02/15/2021 through 02/21/2021',
        22 => '02/22/2021 through 02/28/2021',
        23 => '03/01/2021 through 03/07/2021',
        24 => '03/08/2021 through 03/14/2021',
        25 => '03/15/2021 through 03/21/2021',
        26 => '03/22/2021 through 03/28/2021',
        27 => '03/29/2021 through 04/04/2021',
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

class VillageOfLibertyville2020TobaccoFormComplianceView extends ComplianceView
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
class VillageOfLibertyville2020ComplianceProgram extends ComplianceProgram
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

        if (sfConfig::get('app_wms2')) {
            header('Location: /compliance/hmi-2016/my-rewards/wms1/content/reportcard?id=55');
            die;
        }

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreEndDate = '2020-11-24';

        $quarterlyDateRange = $this->getQuerterlyRanges();

        // Build the core group
        $coreGroup = new ComplianceViewGroup('core', 'Areas A-C are required by November 24, 2020 in order to earn the $50 gift certificate.');

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
        $screeningView->setReportName('Annual Wellness Screening (Onsite, Offsite or Healthcare Provider)');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/wms2-appointment-center'));
        $screeningView->addLink(new Link('Results', '/compliance/hmi-2016/my-health/content/my-health?tab=screening'));
        $coreGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $coreEndDate);
        $hraView->setReportName('Complete the Health Power Assessment');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-results/content/my-health'));
        $coreGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($coreGroup);

        $numbers = new ComplianceViewGroup('points', 'Gain 125 points through actions taken in option areas D-T by the below deadlines in order to earn the two additional $25 gift cards.');

        $elearn = new PlaceHolderComplianceView(null, 0);
        $elearn->setReportName('Complete e-Learning Lessons - 5 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setMaximumNumberOfPoints(30);
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



        $virtualWebinar = new PlaceHolderComplianceView(null, 0);
        $virtualWebinar->setReportName('Attend a virtual webinar offered by the EAP - 15 points for each webinar attended');
        $virtualWebinar->setMaximumNumberOfPoints(30);
        $virtualWebinar->setName('virtual_webinar');
        $virtualWebinar->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 104275, 15);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $virtualWebinar->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=104275'));
        $numbers->addComplianceView($virtualWebinar);


        $financialWebinar = new PlaceHolderComplianceView(null, 0);
        $financialWebinar->setReportName('Attend a virtual Financial webinar - ICMA-RC or Nationwide');
        $financialWebinar->setMaximumNumberOfPoints(15);
        $financialWebinar->setName('financial_webinar');
        $financialWebinar->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 104276, 15);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $financialWebinar->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=104276'));
        $numbers->addComplianceView($financialWebinar);


        $physicalActivityView = new PlaceHolderComplianceView(null, 0);
        $physicalActivityView->setReportName('Regular Physical Activity - 1 pt for each 30 minutes of activity');
        $physicalActivityView->setName('physical_activity');
        $physicalActivityView->setMaximumNumberOfPoints(75);
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
        $steps8K->setMaximumNumberOfPoints(75);
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
        $preventiveExamsView->setReportName('Receive a Preventative Exam - 10 pts per exam');
        $preventiveExamsView->setMaximumNumberOfPoints(30);
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
        $wellnessRun->setMaximumNumberOfPoints(50);
        $wellnessRun->setReportName('Participate in a Virtual Wellness Run/Walk - 25 points per activity *');
        $wellnessRun->setName('run_walk');
        $wellnessRun->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 548, 25);
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

        $ondemandFitness = new PlaceHolderComplianceView(null, 0);
        $ondemandFitness->setMaximumNumberOfPoints(25);
        $ondemandFitness->setReportName('Take a free on-demand fitness, yoga, or meditation class - YouTube or Google search free class - 5 points per class');
        $ondemandFitness->setName('ondemand_fitness');
        $ondemandFitness->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 104295, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $ondemandFitness->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=104295'));
        $numbers->addComplianceView($ondemandFitness);

        $water = new PlaceHolderComplianceView(null, 0);
        $water->setMaximumNumberOfPoints(40);
        $water->setName('water');
        $water->setReportName('Drink 6-8 glasses of pure water per day for a week - 5 pts per week');
        $water->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VillageOfLibertyville2020WeeklyLogComplianceView($startDate, $endDate, 613, 213, 5);
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
        $vegetable->setMaximumNumberOfPoints(40);
        $vegetable->setName('vegetable');
        $vegetable->setReportName('Eat 1 serving of a vegetable a day for a week - 5 pts per week');
        $vegetable->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VillageOfLibertyville2020WeeklyLogComplianceView($startDate, $endDate, 620, 213, 5);
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
        $fruit->setMaximumNumberOfPoints(40);
        $fruit->setName('fruit');
        $fruit->setReportName('Eat 2 servings of fruit a day for a week - 5 pts per week');
        $fruit->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VillageOfLibertyville2020WeeklyLogComplianceView($startDate, $endDate, 612, 213, 5);
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

        $meatless = new PlaceHolderComplianceView(null, 0);
        $meatless->setMaximumNumberOfPoints(25);
        $meatless->setReportName('Go Meatless on Mondays - 5 points for each Meatless Monday');
        $meatless->setName('meatless');
        $meatless->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 104296, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $meatless->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=104296'));
        $numbers->addComplianceView($meatless);

        $volunteeringView = new PlaceHolderComplianceView(null, 0);
        $volunteeringView->setReportName('Regular Volunteering - 2 pts for each hour of volunteering');
        $volunteeringView->setName('volunteering');
        $volunteeringView->setMaximumNumberOfPoints(20);
        $volunteeringView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new VolunteeringComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setMinutesDivisorForPoints(30);
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

                $alternative = new VillageOfLibertyville2020TobaccoFormComplianceView($startDate, $endDate);
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
            'quarter1' => array('2020-10-01', '2021-01-31'),
            'quarter2' => array('2021-02-01', '2021-03-31')
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
            $printer = new VillageOfLibertyville2020ComplianceProgramReportPrinter();
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


class VillageOfLibertyville2020ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
                $('.view-virtual_webinar').children(':eq(0)').html('<strong>E.</strong> Attend a virtual webinar offered by the EAP - 15 points for each webinar attended');
                $('.view-financial_webinar').children(':eq(0)').html('<strong>F.</strong> Attend a virtual Financial webinar - ICMA-RC or Nationwide');
                $('.view-physical_activity').children(':eq(0)').html('<strong>G.</strong> Regular Physical activity - 1 pt for each 30 minutes of activity');
                $('.view-daily_steps_8000').children(':eq(0)').html('<strong>H.</strong> Daily Steps - 1 pt per 8,000 steps');
                $('.view-do_preventive_exams').children(':eq(0)').html('<strong>I.</strong> Receive a Preventative Exam - 10 pts per exam');
                $('.view-run_walk').children(':eq(0)').html('<strong>J.</strong> Participate in a Virtual Wellness Run/Walk - 25 points per activity *');
                $('.view-ondemand_fitness').children(':eq(0)').html('<strong>K.</strong> Take a free on-demand fitness, yoga, or meditation class - YouTube or Google search free class - 5 points per class');
                $('.view-water').children(':eq(0)').html('<strong>L.</strong> Drink 6-8 glasses of pure water per day for a week - 5 pts per week');
                $('.view-vegetable').children(':eq(0)').html('<strong>M.</strong> Eat 1 serving of a vegetable a day for a week - 5 pts per week');
                $('.view-fruit').children(':eq(0)').html('<strong>N.</strong> Eat 2 servings of fruit a day for a week - 5 pts per week');
                $('.view-meatless').children(':eq(0)').html('<strong>O.</strong> Go Meatless on Mondays - 5 points for each Meatless Monday');
                $('.view-volunteering').children(':eq(0)').html('<strong>P.</strong> Regular Volunteering - 2 pts for each hour of volunteering');
                $('.view-non_smoker').children(':eq(0)').html('<strong>Q.</strong> Non-Smoker / Non-Tobacco User');
                $('.view-flu_vaccine').children(':eq(0)').html('<strong>R.</strong> Annual Flu Vaccine');
                $('.view-donate_blood').children(':eq(0)').html('<strong>S.</strong> Donate Blood');
                $('.view-doctor').children(':eq(0)').html('<strong>T.</strong> Have a Primary Care Doctor');





                $('.view-doctor').after('<tr class="headerRow headerRow-footer"><td class="center">Status of All Criteria = </td><td></td><td></td><td>Minimum Points Needed</td></tr>')
                $('.headerRow-footer').after('<tr class="quarter_one"><td style="text-align: right;">By 1/31/2021</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_1_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_1_points') >= 125 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">125</td></tr>')
                $('.quarter_one').after('<tr class="quarter_two"><td style="text-align: right;">By 3/31/2021</td><td style="text-align: center;"><?php echo $status->getAttribute('quarter_2_points') ?></td><td class="status"><img src="<?php echo $status->getAttribute('quarter_2_points') >= 125 ? '/images/lights/greenlight.gif' : '/images/lights/redlight.gif' ?>" class="light" /></td><td style="text-align: center;">125</td></tr>')



            });
        </script>

        <p><strong>Hello <?php echo $status->getUser()->getFullName() ?></strong></p>

        <p>Welcome to your summary page for the 2020-2021 Wellness Rewards benefit at Village of Libertyville.</p>

        <p>You have the opportunity to earn $100 in gift cards. The deadline to complete all actions is March 31, 2021.</p>

        <p>
            To receive the initial $50 gift certificate, you MUST register with HMI, complete the annual wellness
            screening and complete the Health Power Assessment by November 24, 2020.
        </p>

        <p>
            To receive an additional $25 gift card, you MUST earn 125 or more points from key actions taken for good
            health by Jan 31, 2021.
        </p>

        <p>
            To receive the last $25 gift card, you MUST earn 125 or more points from key actions taken for good health
            by Mar 31, 2021.
        </p>

        <p>
            All those who actively participate in the program through March 31, 2021, and earn the minimum point
            requirements for each deadline (Screening/Assessment, January 31, 2021 and March 31, 2021) will
            automatically be entered into a raffle to win a Grand Prize $200 gift card!
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>


        <div style="margin-top: 20px;">
            <div style="margin-right: 160px;">
                *Virtual Run/Walk Examples <br /><br />
                - Libertyville Virtual Twilight Shuffle <br />
                - Allstate Virtual Hot Chocolate 5K/5K <br />
                - Participate in a Virtual Wellness Run/Walk of your choice
            </div>

        </div>


        <?php

    }
}