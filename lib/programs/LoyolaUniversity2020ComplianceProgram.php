<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class LoyolaUniversity2020ComplianceProgram extends ComplianceProgram
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
        $printer = new LoyolaUniversity2020ComplianceReportPrinter();
        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'A & B are required by November 27, 2019.');

        $hraScreeningStartDate = '2019-09-01';
        $hraScreeningEndDate = '2019-11-27';

        $quarterlyDateRange = $this->getQuerterlyRanges();

        $screening = new CompleteScreeningComplianceView($hraScreeningStartDate, $hraScreeningEndDate);
        $screening->setReportName('Annual Wellness Screening');
        $screening->setName('screening');
        $screening->emptyLinks();
        $screening->addLink(new Link('Results', '/compliance/hmi-2016/my-health'));
        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($hraScreeningStartDate, $hraScreeningEndDate);
        $hra->setName('hra');
        $hra->setReportName('Complete the Health Power Assessment ');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HPA', '/compliance/hmi-2016/my-health'));
        $required->addComplianceView($hra);

        $this->addComplianceViewGroup($required);

        $pointsGroup = new ComplianceViewGroup('points', 'Gain points through actions taken in option areas C - S below in order to earn the quarterly $75 reward.');

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


        $cprAecCertifiedView = new PlaceHolderComplianceView(null, 0);
        $cprAecCertifiedView->setMaximumNumberOfPoints(15);
        $cprAecCertifiedView->setReportName('CPR/AED Certified');
        $cprAecCertifiedView->setName('cpr_aec_certified');
        $cprAecCertifiedView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 341, 15);
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
        $cprAecCertifiedView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=341'));
        $pointsGroup->addComplianceView($cprAecCertifiedView);


        $drinkWaterView = new PlaceHolderComplianceView(null, 0);
        $drinkWaterView->setMaximumNumberOfPoints(50);
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

        $challengeView = new PlaceHolderComplianceView(null, 0);
        $challengeView->setMaximumNumberOfPoints(50);
        $challengeView->setReportName('Participate in a Wellness Challenge - 50 pts per challenge');
        $challengeView->setName('wellness_challenge');
        $challengeView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 553, 50);
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
        $challengeView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=553'));
        $pointsGroup->addComplianceView($challengeView);



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
        $elearn->setReportName('Complete e-Learning Lessons - 10 pts for each lesson done');
        $elearn->setName('elearning');
        $elearn->setMaximumNumberOfPoints(50);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('View/Do Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $elearn->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteELearningLessonsComplianceView($startDate, $endDate);
                $alternative->setUseOverrideCreatedDate(true);
                $alternative->setPointsPerLesson(10);
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

        $financialView = new PlaceHolderComplianceView(null, 0);
        $financialView->setMaximumNumberOfPoints(20);
        $financialView->setReportName('Participate in a Financial Wellness program through Emerge - 20 pts each');
        $financialView->setName('financial');
        $financialView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 562, 20);
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
        $financialView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=562'));
        $pointsGroup->addComplianceView($financialView);

        $benefitInfoView = new PlaceHolderComplianceView(null, 0);
        $benefitInfoView->setMaximumNumberOfPoints(50);
        $benefitInfoView->setReportName('Attend a Benefit Information Session (Sept/Oct) - 50 pts');
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

        $benefitHealthView = new PlaceHolderComplianceView(null, 0);
        $benefitHealthView->setMaximumNumberOfPoints(20);
        $benefitHealthView->setReportName('Attend a scheduled Benefit Fair - 20 pts.');
        $benefitHealthView->setName('benefit_health');
        $benefitHealthView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 1696, 20);
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
        $benefitHealthView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=1696'));
        $pointsGroup->addComplianceView($benefitHealthView);


        $organizedRaceView = new PlaceHolderComplianceView(null, 0);
        $organizedRaceView->setMaximumNumberOfPoints(20);
        $organizedRaceView->setReportName('Participate in a Loyola organized race - 20 pts per event');
        $organizedRaceView->setName('organized_race');
        $organizedRaceView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 42652, 20);
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
        $organizedRaceView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=42652'));
        $pointsGroup->addComplianceView($organizedRaceView);


        $loyolaServiceView = new PlaceHolderComplianceView(null, 0);
        $loyolaServiceView->setMaximumNumberOfPoints(20);
        $loyolaServiceView->setReportName('Participate in a Loyola Service Day Event - 20 pts');
        $loyolaServiceView->setName('loyola_service');
        $loyolaServiceView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 42653, 20);
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
        $loyolaServiceView->addLink(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=42653'));
        $pointsGroup->addComplianceView($loyolaServiceView);


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
        $pointsGroup->addComplianceView($fruitsVegetablesView);


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
        $weightWatchersView->setReportName('Attend a Weight Watchers meeting - 5 pts per meeting');
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
            'quarter1' => array('2019-09-01', '2019-11-30'),
            'quarter2' => array('2019-12-01', '2020-02-29'),
            'quarter3' => array('2020-03-01', '2020-05-31'),
            'quarter4' => array('2020-06-01', '2020-08-31')
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



class LoyolaUniversity2020ComplianceReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->pageHeading = '<img src="/images/hmii/loyola/loyola_university_logo.png" style="width:320px;" /><br/ >
                                <br/ >LOYOLA UNIVERSITY CHICAGO <br /><br />2019-2020 WELLNESS REWARDS PROGRAM';
        $this->setScreeningResultsLink(new FakeLink('Complete eLearning Lessons', '#'));
    }

    public function printHeader(ComplianceProgramStatus $status)
    {


        ?>

        <script type="text/javascript">
            // whyyyyyyyyyyyy
            $(function(){
                $('#legend tr td').html('<div id="legendText">Legend</div>' +
                    '<div class="legendEntry"><img src="/compliance/hmi-2016/my-rewards/wms1/images/lights/greenlight.gif" class="light" alt=""> = Criteria Met </div>' +
                    '<div class="legendEntry"> <img src="/compliance/hmi-2016/my-rewards/wms1/images/lights/redlight.gif" class="light" alt=""> = Not Started </div>');

                $('.headerRow.headerRow-points').children(':eq(2)').html('Max Points Possible per Quarter');

                $('.view-healthy_activity').children(':eq(2)').html('Unlimited');

                $('.view-annual_flu_vaccine').children(':eq(0)').html('<strong>C</strong>. Annual Flu Vaccine');
                $('.view-volunteering').children(':eq(0)').html('<strong>D</strong>. Regular Volunteering - 5 pts per volunteering activity');
                $('.view-donate_blood').children(':eq(0)').html('<strong>E</strong>. Donate Blood - 20 pts per donation');
                $('.view-cpr_aec_certified').children(':eq(0)').html('<strong>F</strong>. CPR/AED Certified');
                $('.view-drink_water').children(':eq(0)').html('<strong>G</strong>. Drink 6-8 glasses of pure water per day - 1 pt per day');
                $('.view-wellness_challenge').children(':eq(0)').html('<strong>H</strong>. Participate in a Wellness Challenge - 50 pts per challenge');
                $('.view-preventive_exam').children(':eq(0)').html('<strong>I</strong>. Receive a Preventive Exam - 10 pts per exam');
                $('.view-elearning').children(':eq(0)').html('<strong>J</strong>. Complete e-Learning Lessons - 10 pts for each lesson done');
                $('.view-healthy_activity').children(':eq(0)').html('<strong>K</strong>. Healthy Activity - 5 pts per hour of exercise');
                $('.view-financial').children(':eq(0)').html('<strong>L</strong>. Participate in a Financial Wellness program through Emerge - 20 pts each');
                $('.view-benefit_info').children(':eq(0)').html('<strong>M</strong>. Attend a Benefit Information Session (Sept/Oct) - 50 pts');
                $('.view-benefit_health').children(':eq(0)').html('<strong>N</strong>. Attend a scheduled Benefit Fair - 20 pts');
                $('.view-organized_race').children(':eq(0)').html('<strong>O</strong>. Participate in a Loyola organized race - 20 pts per event');
                $('.view-loyola_service').children(':eq(0)').html('<strong>P</strong>. Participate in a Loyola Service Day Event - 20 pts');
                $('.view-fruits_vegetables').children(':eq(0)').html('<strong>Q</strong>. Eat 3-5 servings of fruits and vegetables per day - 2 pts per day');
                $('.view-divvy_membership').children(':eq(0)').html('<strong>R</strong>. Sign up for a Divvy membership - 15 pts');
                $('.view-weight_watchers').children(':eq(0)').html('<strong>S</strong>. Attend a Weight Watchers meeting - 5 pts per meeting');




            });
        </script>

        <style type="text/css">
            .status img {
                width:25px;
            }
        </style>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>Welcome to your summary page for the 2019/2020 Wellness Rewards benefit at Loyola University.</p>

        <p>This year, employees and spouses (if applicable) on Loyola’s health insurance have the opportunity to earn a premium discount.</p>

        <p>
            To receive the reduced premium, you MUST register with HMI in the health portal, complete the annual wellness
            screening and complete the Health Power Assessment by November 27, 2019 (Section 1 below).
        </p>
        

        <p>
            <div style="font-weight: bold;">For Employees ONLY:</div>
            To receive the additional $75 quarterly wellness rewards, you MUST earn 125 points each quarter from actions
            taken for good health (Section 2 below). You may begin earning points starting September 1, 2019.
        </p>

        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>


        <p style="margin-top: 20px; margin-left: 200px;">
            <strong>Deadline 1:</strong> Earn at least 125 pts from <strong>September 1 - November 30, 2019</strong> <br/>
            <strong>Deadline 2:</strong> Earn at least 125 pts from <strong>December 1 - February 29, 2020</strong> <br/>
            <strong>Deadline 3:</strong> Earn at least 125 pts from <strong>March 1 - May 31, 2020</strong> <br/>
            <strong>Deadline 4:</strong> Earn at least 125 pts from <strong>June 1 - August 31, 2020</strong> <br/>
        </p>
        <?php
    }
}