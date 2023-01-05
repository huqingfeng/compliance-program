<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class LoyolaUniversity2021ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->setShowUserFields(null,null,null,null,null,null,null,null,true);

        $printer->addStatusFieldCallback('Quarter 1 Points',  function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_1_points');
        });

        $printer->addStatusFieldCallback('Quarter 2 Points',  function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_2_points');
        });

        $printer->addStatusFieldCallback('Quarter 3 Points',  function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_3_points');
        });

        $printer->addStatusFieldCallback('Quarter 4 Points',  function(ComplianceProgramStatus $status) {
            return $status->getAttribute('quarter_4_points');
        });


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new LoyolaUniversity2021ComplianceReportPrinter();
        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $quarterlyDateRange = $this->getQuerterlyRanges();

        $pointsGroup = new ComplianceViewGroup('points', 'Gain points through actions taken in option areas A - Q below in order to earn the quarterly $75 reward.');

        $hra = new PlaceHolderComplianceView(null, 0);
        $hra->setName('hra');
        $hra->setReportName('Complete the Health Power Assessment ');
        $hra->setMaximumNumberOfPoints(50);
        $hra->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteHRAComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $alternativeStatus->setPoints(50);
                } else {
                    $alternativeStatus->setPoints(0);
                }

                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }

                $status->setAttribute($quarterName, $points);
            }
        });
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/loyola-2017/my-results/content/my-health'));
        $pointsGroup->addComplianceView($hra);


        $annualFluVaccineView = new PlaceHolderComplianceView(null, 0);
        $annualFluVaccineView->setMaximumNumberOfPoints(25);
        $annualFluVaccineView->setReportName('Annual Flu Vaccine');
        $annualFluVaccineView->setName('annual_flu_vaccine');
        $annualFluVaccineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 20, 25);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $annualFluVaccineView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=20'));
        $pointsGroup->addComplianceView($annualFluVaccineView);


        $volunteeringView = new PlaceHolderComplianceView(null, 0);
        $volunteeringView->setMaximumNumberOfPoints(50);
        $volunteeringView->setReportName('Regular Volunteering - 5 pts per volunteering activity');
        $volunteeringView->setName('volunteering');
        $volunteeringView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 607, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                $points = $alternativeStatus->getPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $volunteeringView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=607'));
        $pointsGroup->addComplianceView($volunteeringView);



        $donateBloodView = new PlaceHolderComplianceView(null, 0);
        $donateBloodView->setMaximumNumberOfPoints(40);
        $donateBloodView->setReportName('Donate Blood - 20 pts per donation');
        $donateBloodView->setName('donate_blood');
        $donateBloodView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 503, 20);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $donateBloodView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=503'));
        $pointsGroup->addComplianceView($donateBloodView);


        $maskView = new PlaceHolderComplianceView(null, 0);
        $maskView->setMaximumNumberOfPoints(30);
        $maskView->setReportName('Wear a mask when in proximity of other people – 1 pt per day.');
        $maskView->setName('mask');
        $maskView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 87423, 1);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $maskView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=87423'));
        $pointsGroup->addComplianceView($maskView);


        $drinkWaterView = new PlaceHolderComplianceView(null, 0);
        $drinkWaterView->setMaximumNumberOfPoints(30);
        $drinkWaterView->setReportName('Drink 6-8 glasses of pure water per day - 1 pt per day.');
        $drinkWaterView->setName('drink_water');
        $drinkWaterView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 608, 1);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $drinkWaterView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=608'));
        $pointsGroup->addComplianceView($drinkWaterView);


        $teladocView = new PlaceHolderComplianceView(null, 0);
        $teladocView->setMaximumNumberOfPoints(20);
        $teladocView->setReportName('Utilize Teladoc for General Medicine video consults with a licensed practitioner');
        $teladocView->setName('teladoc');
        $teladocView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 87424, 20);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $teladocView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=87424'));
        $pointsGroup->addComplianceView($teladocView);




        $preventiveExamView = new PlaceHolderComplianceView(null, 0);
        $preventiveExamView->setMaximumNumberOfPoints(50);
        $preventiveExamView->setReportName('Receive a Preventive Exam - 10 pts per exam');
        $preventiveExamView->setName('preventive_exam');
        $preventiveExamView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new  CompletePreventiveExamWithRollingStartDateLogicComplianceView($startDate, $endDate, 10);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setRollingStartDate(false);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }

                $status->setAttribute($quarterName, $points);
            }
        });
        $preventiveExamView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=26'));
        $pointsGroup->addComplianceView($preventiveExamView);

        $elearn = new PlaceHolderComplianceView(null, 0);
        $elearn->setReportName('Complete e-Learning Lessons - 20 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setMaximumNumberOfPoints(100);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $elearn->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningLessonsComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(20);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $pointsGroup->addComplianceView($elearn);

        $physicalActivityView = new PlaceHolderComplianceView(null, 0);
        $physicalActivityView->setReportName('Healthy Activity - 5 pts per hour of exercise');
        $physicalActivityView->setName('healthy_activity');
        $physicalActivityView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new PhysicalActivityComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setMinutesDivisorForPoints(12);
                $alternativeStatus = $alternative->getStatus($user);

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($alternativeStatus->getPoints());
                }
                $status->setAttribute($quarterName, $alternativeStatus->getPoints());
            }
        });
        $physicalActivityView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=21'));
        $pointsGroup->addComplianceView($physicalActivityView);

        $emergeTrainingView = new PlaceHolderComplianceView(null, 0);
        $emergeTrainingView->setMaximumNumberOfPoints(20);
        $emergeTrainingView->setReportName('Participate in a Virtual Emerge Training - 20 pts each');
        $emergeTrainingView->setName('emerge_training');
        $emergeTrainingView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 87425, 20);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $emergeTrainingView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=87425'));
        $pointsGroup->addComplianceView($emergeTrainingView);

        $benefitInfoView = new PlaceHolderComplianceView(null, 0);
        $benefitInfoView->setMaximumNumberOfPoints(50);
        $benefitInfoView->setReportName('Attend a Virtual Benefit Information Session (Sept/Oct) - 50 pts');
        $benefitInfoView->setName('benefit_info');
        $benefitInfoView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 1695, 50);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $benefitInfoView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=1695'));
        $pointsGroup->addComplianceView($benefitInfoView);

        $sleepView = new PlaceHolderComplianceView(null, 0);
        $sleepView->setMaximumNumberOfPoints(20);
        $sleepView->setReportName('Sleep at least 7 hours per evening - 2 pts per day');
        $sleepView->setName('sleep');
        $sleepView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 87426, 2);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $sleepView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=87426'));
        $pointsGroup->addComplianceView($sleepView);


        $meditationView = new PlaceHolderComplianceView(null, 0);
        $meditationView->setMaximumNumberOfPoints(20);
        $meditationView->setReportName('Practice Mindful Meditation – 1pt per day');
        $meditationView->setName('meditation');
        $meditationView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 87427, 1);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $meditationView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=87427'));
        $pointsGroup->addComplianceView($meditationView);


        $gymView = new PlaceHolderComplianceView(null, 0);
        $gymView->setMaximumNumberOfPoints(15);
        $gymView->setReportName('Sign up for gym membership, attend an online fitness class or add a piece of equipment to your home gym - 15pts');
        $gymView->setName('gym');
        $gymView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 87428, 15);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $gymView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=87428'));
        $pointsGroup->addComplianceView($gymView);


        $fruitsVegetablesView = new PlaceHolderComplianceView(null, 0);
        $fruitsVegetablesView->setMaximumNumberOfPoints(50);
        $fruitsVegetablesView->setReportName('Eat 3-5 servings of fruits and vegetables per day - 2 pts per day');
        $fruitsVegetablesView->setName('fruits_vegetables');
        $fruitsVegetablesView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 42654, 2);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $fruitsVegetablesView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=42654'));
//        $pointsGroup->addComplianceView($fruitsVegetablesView);


        $divvyMembershipView = new PlaceHolderComplianceView(null, 0);
        $divvyMembershipView->setMaximumNumberOfPoints(15);
        $divvyMembershipView->setReportName('Sign up for a Divvy membership - 15 pts');
        $divvyMembershipView->setName('divvy_membership');
        $divvyMembershipView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 42655, 15);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $divvyMembershipView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=42655'));
        $pointsGroup->addComplianceView($divvyMembershipView);

        $weightWatchersView = new PlaceHolderComplianceView(null, 0);
        $weightWatchersView->setMaximumNumberOfPoints(50);
        $weightWatchersView->setReportName('Attend a Virtual Weight Watchers meeting - 5 pts per meeting');
        $weightWatchersView->setName('weight_watchers');
        $weightWatchersView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 42656, 5);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);
                $points = $alternativeStatus->getPoints();
                $max = $status->getComplianceView()->getMaximumNumberOfPoints();
                if ($points >= $max) $points = $max;

                if(date('Y-m-d') >= $startDate && date('Y-m-d') <= $endDate) {
                    $status->setPoints($points);
                }
                $status->setAttribute($quarterName, $points);
            }
        });
        $weightWatchersView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=42656'));
        $pointsGroup->addComplianceView($weightWatchersView);



        $pointsGroup->setPointsRequiredForCompliance(125);
        $this->addComplianceViewGroup($pointsGroup);
    }

    protected function getQuerterlyRanges()
    {
        $ranges = array(
            'quarter1' => array('2020-09-15', '2020-11-30'),
            'quarter2' => array('2020-12-01', '2021-02-28'),
            'quarter3' => array('2021-03-01', '2021-05-31'),
            'quarter4' => array('2021-06-01', '2021-08-31')
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
    }

}



class LoyolaUniversity2021ComplianceReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->pageHeading = '<img src="/images/hmii/loyola/loyola_university_logo.png" style="width:320px;" /><br/ >
                                <br/ >LOYOLA UNIVERSITY CHICAGO <br /><br />2020-2021 WELLNESS REWARDS PROGRAM';
        $this->setScreeningResultsLink(new FakeLink('Complete eLearning Lessons', '#'));
    }

    public function printHeader(ComplianceProgramStatus $status)
    {


        ?>

        <script type="text/javascript">
            // whyyyyyyyyyyyy
            $(function(){
                $('#legend').hide();

                $('.headerRow.headerRow-points').children(':eq(2)').html('Max Points Possible per Quarter');

                $('.view-healthy_activity').children(':eq(2)').html('Unlimited');





            });
        </script>

        <style type="text/css">
            .status img {
                width:25px;
            }
        </style>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Welcome to your summary page for the 2020/2021 Wellness Rewards benefit at Loyola University.</p>

        <p>
            <div style="font-weight: bold;">For Employees ONLY:</div>
            To receive the $75 wellness reward, you MUST earn 125 points each period from actions taken for good health
             (Section 1 below). You may begin earning points starting <span style="font-weight: bold;">September 15, 2020</span>.
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>


        <p style="margin-top: 20px; margin-left: 200px;">
            <strong>Deadline 1:</strong> Earn at least 125 pts from <strong>September 15 - November 30, 2020</strong> <br/>
            <strong>Deadline 2:</strong> Earn at least 125 pts from <strong>December 1 - February 28, 2021</strong> <br/>
            <strong>Deadline 3:</strong> Earn at least 125 pts from <strong>March 1 - May 31, 2021</strong> <br/>
            <strong>Deadline 4:</strong> Earn at least 125 pts from <strong>June 1 - August 31, 2021</strong> <br/>
        </p>
        <?php
    }
}