<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class HFMA2018ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $quarterlyDateRange = $this->getQuerterlyRanges();

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $annualActivitiesGroup = new ComplianceViewGroup('annual_activities', 'Annual Activities');
        $annualActivitiesGroup->setPointsRequiredForCompliance(100);

        $annualPhysicalExamView = new PlaceHolderComplianceView(null, 0);
        $annualPhysicalExamView->setReportName('Annual Physical Exam & Screening Follow-Up');
        $annualPhysicalExamView->setName('annual_physical_exam');
        $annualPhysicalExamView->setAttribute('requirement', 'Visit your personal physician to follow-up on the wellness screening and complete an annual exam');
        $annualPhysicalExamView->setAttribute('points_per_activity', '20 points');
        $annualPhysicalExamView->emptyLinks();
        $annualPhysicalExamView->addLink(new Link('Verification Form', '/resources/10008/HFMA 2018_PreventiveCare Cert.pdf'));
        $annualPhysicalExamView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $annualPhysicalExamView->setMaximumNumberOfPoints(20);
        $annualPhysicalExamView->setAllowPointsOverride(true);
        $annualPhysicalExamView->setUseOverrideCreatedDate(true);
        $annualActivitiesGroup->addComplianceView($annualPhysicalExamView);

        $preventiveServiceView = new PlaceHolderComplianceView(null, 0);
        $preventiveServiceView->setName('preventive_service');
        $preventiveServiceView->setReportName('Preventive Services');
        $preventiveServiceView->setAttribute('requirement', 'Receive a preventive service such as mammogram, prostate exam, routine dental exam, etc.');
        $preventiveServiceView->setAttribute('points_per_activity', '10 points per exam');
        $preventiveServiceView->emptyLinks();
        $preventiveServiceView->addLink(new Link('Verification Form', '/resources/10008/HFMA 2018_PreventiveCare Cert.pdf'));
        $preventiveServiceView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $preventiveServiceView->setMaximumNumberOfPoints(30);
        $preventiveServiceView->setAllowPointsOverride(true);
        $preventiveServiceView->setUseOverrideCreatedDate(true);
        $annualActivitiesGroup->addComplianceView($preventiveServiceView);

        $flushotView = new PlaceHolderComplianceView(null, 0);
        $flushotView->setName('flushot');
        $flushotView->setReportName('Receive a flu shot');
        $flushotView->setAttribute('requirement', 'Receive a flu shot');
        $flushotView->setAttribute('points_per_activity', '10 points');
        $flushotView->emptyLinks();
        $flushotView->setMaximumNumberOfPoints(10);
        $flushotView->setAllowPointsOverride(true);
        $flushotView->setUseOverrideCreatedDate(true);
        $annualActivitiesGroup->addComplianceView($flushotView);

        $donateBloodView = new PlaceHolderComplianceView(null, 0);
        $donateBloodView->setName('donate_blood');
        $donateBloodView->setAttribute('requirement', 'Donate Blood');
        $donateBloodView->setAttribute('points_per_activity', '5 points');
        $donateBloodView->setReportName('Donate Blood');
        $donateBloodView->setMaximumNumberOfPoints(25);
        $donateBloodView->setAllowPointsOverride(true);
        $donateBloodView->addLink(new FakeLink('Admin will enter', '#'));
        $donateBloodView->setUseOverrideCreatedDate(true);
        $annualActivitiesGroup->addComplianceView($donateBloodView);

        $mealPreparationServiceView = new PlaceHolderComplianceView(null, 0);
        $mealPreparationServiceView->setName('meal_preparation_service');
        $mealPreparationServiceView->setAttribute('requirement', 'Submit proof of meal service membership (i.e. HelloFresh, Blue Apron, HomeChef, Plated, etc.)');
        $mealPreparationServiceView->setAttribute('points_per_activity', '5 points/month');
        $mealPreparationServiceView->setReportName('Meal Preparation Service');
        $mealPreparationServiceView->setMaximumNumberOfPoints(25);
        $mealPreparationServiceView->setAllowPointsOverride(true);
        $mealPreparationServiceView->setUseOverrideCreatedDate(true);
        $mealPreparationServiceView->addLink(new FakeLink('Admin will enter', '#'));
        $annualActivitiesGroup->addComplianceView($mealPreparationServiceView);

        $healthProgramMembershipView = new PlaceHolderComplianceView(null, 0);
        $healthProgramMembershipView->setName('health_program_membership');
        $healthProgramMembershipView->setAttribute('requirement', 'Submit proof of health program membership of at least 3 months (i.e. Weight Watchers, Jenny Craig, etc.)');
        $healthProgramMembershipView->setAttribute('points_per_activity', '25 points');
        $healthProgramMembershipView->setReportName('Health Program Membership');
        $healthProgramMembershipView->setMaximumNumberOfPoints(25);
        $healthProgramMembershipView->setAllowPointsOverride(true);
        $healthProgramMembershipView->setUseOverrideCreatedDate(true);
        $healthProgramMembershipView->addLink(new FakeLink('Admin will enter', '#'));
        $annualActivitiesGroup->addComplianceView($healthProgramMembershipView);

        $regularFitnessTrainingView = new PhysicalActivityComplianceView($programStart, $programEnd);
        $regularFitnessTrainingView->setReportName('Regular Fitness Training');
        $regularFitnessTrainingView->setName('regular_fitness_training');
        $regularFitnessTrainingView->setMaximumNumberOfPoints(160);
        $regularFitnessTrainingView->setMinutesDivisorForPoints(100);
        $regularFitnessTrainingView->setMonthlyPointLimit(16);
        $regularFitnessTrainingView->setPointsMultiplier(4);
        $regularFitnessTrainingView->setFractionalDivisorForPoints(1);
//        $regularFitnessTrainingView->setAllowPointsOverride(true);
        $regularFitnessTrainingView->setUseOverrideCreatedDate(true);
        $regularFitnessTrainingView->setAttribute('requirement', 'Track a minimum of 400 minutes of activity/month');
        $regularFitnessTrainingView->setAttribute('points_per_activity', '16 points per month');
        $regularFitnessTrainingView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
            $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                $alternative = new PhysicalActivityComplianceView($startDate, $endDate);
                $alternative->setMaximumNumberOfPoints(160);
                $alternative->setMinutesDivisorForPoints(100);
                $alternative->setMonthlyPointLimit(16);
                $alternative->setPointsMultiplier(4);
                $alternative->setFractionalDivisorForPoints(1);
                $alternative->setUseOverrideCreatedDate(true);
                $alternativeStatus = $alternative->getStatus($user);

                if($maxPoints > 0) {
                    if(($maxPoints - $alternativeStatus->getPoints()) > 0) {
                        $status->setAttribute($quarterName, $alternativeStatus->getPoints());
                        $maxPoints -= $alternativeStatus->getPoints();
                    } else {
                        $status->setAttribute($quarterName, $maxPoints);
                        $maxPoints = 0;
                    }
                }
            }
        });

        $annualActivitiesGroup->addComplianceView($regularFitnessTrainingView);

        $participateWalkRunView = new PlaceHolderComplianceView(null, 0);
        $participateWalkRunView->setName('participate_walk_run');
        $participateWalkRunView->setReportName('Participate in walk/run (1 pt/km)');
        $participateWalkRunView->setAttribute('requirement', 'Participate in walk/run (1 pt/km), Example: 5k = 5 points');
        $participateWalkRunView->setAttribute('points_per_activity', '1 point per km');
        $participateWalkRunView->setAllowPointsOverride(true);
        $participateWalkRunView->setMaximumNumberOfPoints(30);
        $participateWalkRunView->setPointsOverrideHonorsMaximum(false);
        $participateWalkRunView->setUseOverrideCreatedDate(true);
        $participateWalkRunView->addLink(new Link('Verification Form', '/resources/10009/2018_WellnessEventCert.pdf'));
        $participateWalkRunView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $annualActivitiesGroup->addComplianceView($participateWalkRunView);

        $participateHalfMarathonView = new PlaceHolderComplianceView(null, 0);
        $participateHalfMarathonView->setName('participate_half_marathon');
        $participateHalfMarathonView->setReportName('Participate in half-marathon, Sprint distance triathlon, or Bike Tour');
        $participateHalfMarathonView->setAttribute('requirement', 'Participate in half-marathon, Sprint distance triathlon, or Bike Tour (25-30 miles)');
        $participateHalfMarathonView->setAttribute('points_per_activity', '15 points');
        $participateHalfMarathonView->setMaximumNumberOfPoints(30);
        $participateHalfMarathonView->setAllowPointsOverride(true);
        $participateHalfMarathonView->setPointsOverrideHonorsMaximum(false);
        $participateHalfMarathonView->setUseOverrideCreatedDate(true);
        $annualActivitiesGroup->addComplianceView($participateHalfMarathonView);

        $participateMarathonView = new PlaceHolderComplianceView(null, 0);
        $participateMarathonView->setName('participate_marathon');
        $participateMarathonView->setReportName('Participate in a marathon or Olympic distance triathlon, or Bike Tour');
        $participateMarathonView->setAttribute('requirement', 'Participate in a marathon or Olympic distance triathlon, or Bike Tour (51-100 miles)');
        $participateMarathonView->setAttribute('points_per_activity', '30 points');
        $participateMarathonView->setMaximumNumberOfPoints(30);
        $participateMarathonView->setAllowPointsOverride(true);
        $participateMarathonView->setPointsOverrideHonorsMaximum(false);
        $participateMarathonView->setUseOverrideCreatedDate(true);
        $annualActivitiesGroup->addComplianceView($participateMarathonView);

        $elearn = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearn->setReportName('E-learning');
        $elearn->setName('elearning');
        $elearn->setAttribute('requirement', 'Complete an online e-learning course');
        $elearn->setAttribute('points_per_activity', '5 points');
        $elearn->setPointsPerLesson(5);
        $elearn->setMaximumNumberOfPoints(25);
//        $elearn->setAllowPointsOverride(true);
        $elearn->setUseOverrideCreatedDate(true);
        $elearn->emptyLinks();
        $elearn->addLink(new Link('Take Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
        $annualActivitiesGroup->addComplianceView($elearn);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Smoking Cessation');
        $smokingView->setName('smoking_cessation');
        $smokingView->setAttribute('requirement', 'Complete a Smoking Cessation Course as recommended by EAP');
        $smokingView->setAttribute('points_per_activity', '25 points');
        $smokingView->setMaximumNumberOfPoints(25);
//        $smokingView->setAllowPointsOverride(true);
        $smokingView->setUseOverrideCreatedDate(true);
        $smokingView->addLink(new Link('Verification Form', '/resources/10009/2018_WellnessEventCert.pdf'));
        $smokingView->addLink(new Link('Submit Form', '/content/chp-document-uploader'));
        $annualActivitiesGroup->addComplianceView($smokingView);

        $otherWellnessEventsView = new PlaceHolderComplianceView(null, 0);
        $otherWellnessEventsView->setName('other_wellness_events');
        $otherWellnessEventsView->setAttribute('requirement', 'Participate in other HFMA designated Wellness Events');
        $otherWellnessEventsView->setAttribute('points_per_activity', 'Varies');
        $otherWellnessEventsView->setReportName('Other HFMA Wellness Events');
        $otherWellnessEventsView->setMaximumNumberOfPoints(100);
        $otherWellnessEventsView->setAllowPointsOverride(true);
        $otherWellnessEventsView->setUseOverrideCreatedDate(true);
        $otherWellnessEventsView->addLink(new FakeLink('Admin will enter', '#'));
        $annualActivitiesGroup->addComplianceView($otherWellnessEventsView);

        $this->addComplianceViewGroup($annualActivitiesGroup);


        $quarterOneGroup = new ComplianceViewGroup('quarter_one', '1ST QUARTER: FINANCIAL FITNESS');
        $quarterOneGroup->setPointsRequiredForCompliance(100);

        $hraScrView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScrView->setReportName('Wellness Screening & Health Power Assessment');
        $hraScrView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(35, 0, 0, 0));
        $hraScrView->setAllowPointsOverride(true);
        $hraScrView->setMaximumNumberOfPoints(35);
        $hraScrView->setAttribute(
            'requirement', 'Complete the HMI Biometric Screening & online HPA Onsite: Tuesday, February 27 Offsite by Friday, March 2nd (contact HMI to schedule)'
        );
        $hraScrView->setAttribute('points_per_activity', '35 points');
        $hraScrView->emptyLinks();
        $hraScrView->addLink(new Link('Do Assessment', '/content/989'));
        $hraScrView->addLink(new Link('Register', '/content/wms2-appointment-center'));
        $quarterOneGroup->addComplianceView($hraScrView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $bloodPressureView->setAttribute('requirement', 'BP < 130/85 mHg');
        $bloodPressureView->setAttribute('points_per_activity', '10 points');
        $bloodPressureView->overrideSystolicTestRowData(null, null, 129.999, null);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 84.999, null);
        $bloodPressureView->setAllowPointsOverride(true);
        $bloodPressureView->addLink(new Link('Results', '/compliance/hmi-2016/my-results'));
        $quarterOneGroup->addComplianceView($bloodPressureView);

        $hdlCholesterolView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $hdlCholesterolView->setAttribute('requirement', 'Total Cholesterol:HDL Ratio < 4.5');
        $hdlCholesterolView->setAttribute('points_per_activity', '10 points');
        $hdlCholesterolView->overrideTestRowData(null, null, 4.499, null);
        $hdlCholesterolView->setAllowPointsOverride(true);
        $quarterOneGroup->addComplianceView($hdlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $trigView->setAttribute('requirement', 'Triglycerides ≤ 150');
        $trigView->setAttribute('points_per_activity', '10 points');
        $trigView->overrideTestRowData(null, null, 150, null);
        $trigView->setAllowPointsOverride(true);
        $quarterOneGroup->addComplianceView($trigView, false);

        $wellnessKickOff = new CompleteArbitraryActivityComplianceView($programStart, $programEnd, 330, 10);
        $wellnessKickOff->setReportName('Wellness Kick-Off');
        $wellnessKickOff->setMaximumNumberOfPoints(10);
        $wellnessKickOff->setAttribute('requirement', 'Attend the Wellness Kick-Off Event');
        $wellnessKickOff->setAttribute('points_per_activity', '10 points');
        $wellnessKickOff->emptyLinks();
        $wellnessKickOff->addLink(new FakeLink('Sign in at Event', '/content/events'));
//        $wellnessKickOff->setAllowPointsOverride(true);
        $quarterOneGroup->addComplianceView($wellnessKickOff);

        $unifedTrustView = new PlaceHolderComplianceView(null, 0);
        $unifedTrustView->setName('unifed_trust');
        $unifedTrustView->setReportName('Unified Trust retirement planning');
        $unifedTrustView->setAttribute('requirement', 'Complete Unified ARI form (Additional Retirement Information) Email to: <a href="mailto:customerservice@unifedtrust.com">customerservice@unifedtrust.com</a>');
        $unifedTrustView->setAttribute('points_per_activity', '15');
        $unifedTrustView->setMaximumNumberOfPoints(15);
        $unifedTrustView->setAllowPointsOverride(true);
        $quarterOneGroup->addComplianceView($unifedTrustView);

        $educatonalSessionsQ1View = new PlaceHolderComplianceView(null, 0);
        $educatonalSessionsQ1View->setName('educatonal_sessions_q1');
        $educatonalSessionsQ1View->setReportName('Educational Sessions');
        $educatonalSessionsQ1View->setAttribute('requirement', 'Attend educational session or webinar hosted by Unified Trust, Sikich');
        $educatonalSessionsQ1View->setAttribute('points_per_activity', '5');
        $educatonalSessionsQ1View->setMaximumNumberOfPoints(5);
        $educatonalSessionsQ1View->setAllowPointsOverride(true);
        $quarterOneGroup->addComplianceView($educatonalSessionsQ1View);

        $quarterlyChallengesView = new PlaceHolderComplianceView(null, 0);
        $quarterlyChallengesView->setName('quarterly_challenges');
        $quarterlyChallengesView->setReportName('QUARTERLY CHALLENGES');
        $quarterlyChallengesView->setAttribute('requirement', 'Complete Unified Trust ARI planning form (Additional Retirement Information) <br>Email to: <a href="mailto:customerservice@unifiedtrust.com">customerservice@unifiedtrust.com</a>');
        $quarterlyChallengesView->setAttribute('points_per_activity', '25');
        $quarterlyChallengesView->setMaximumNumberOfPoints(25);
        $quarterlyChallengesView->setAllowPointsOverride(true);
        $quarterOneGroup->addComplianceView($quarterlyChallengesView);

        $this->addComplianceViewGroup($quarterOneGroup);


        $quarterTwoGroup = new ComplianceViewGroup('quarter_two', '2nd QUARTER: SPRING TRAINING');
        $quarterTwoGroup->setPointsRequiredForCompliance(100);

        $educatonalSessionsQ2View = new PlaceHolderComplianceView(null, 0);
        $educatonalSessionsQ2View->setName('educatonal_sessions_q2');
        $educatonalSessionsQ2View->setReportName('Educational Sessions');
        $educatonalSessionsQ2View->setAttribute('requirement', 'Attend Workout in your Workspace Educational Session');
        $educatonalSessionsQ2View->setAttribute('points_per_activity', '5');
        $educatonalSessionsQ2View->setMaximumNumberOfPoints(5);
        $educatonalSessionsQ2View->setAllowPointsOverride(true);
        $quarterTwoGroup->addComplianceView($educatonalSessionsQ2View);

        $quarterlyQuizQ2View = new PlaceHolderComplianceView(null, 0);
        $quarterlyQuizQ2View->setName('quarterly_quiz_q2');
        $quarterlyQuizQ2View->setReportName('Quarterly Quiz');
        $quarterlyQuizQ2View->setAttribute('requirement', 'Complete a quarterly quiz');
        $quarterlyQuizQ2View->setAttribute('points_per_activity', '5');
        $quarterlyQuizQ2View->setMaximumNumberOfPoints(10);
        $quarterlyQuizQ2View->setAllowPointsOverride(true);
        $quarterTwoGroup->addComplianceView($quarterlyQuizQ2View);

        $HFMAFitnessChallengeView = new PlaceHolderComplianceView(null, 0);
        $HFMAFitnessChallengeView->setName('hfma_fitness_challenge');
        $HFMAFitnessChallengeView->setReportName('HFMA Fitness Challenge');
        $HFMAFitnessChallengeView->setAttribute('requirement', 'Complete HFMA Fitness Challenge: What Happens in Vegas');
        $HFMAFitnessChallengeView->setAttribute('points_per_activity', '25');
        $HFMAFitnessChallengeView->setMaximumNumberOfPoints(25);
        $HFMAFitnessChallengeView->setAllowPointsOverride(true);
        $quarterTwoGroup->addComplianceView($HFMAFitnessChallengeView);

        $stepItUpView = new PlaceHolderComplianceView(null, 0);
        $stepItUpView->setName('step_it_up');
        $stepItUpView->setReportName('Step It Up');
        $stepItUpView->setAttribute('requirement', 'Step It Up— Take the Stairs Challenge (2 weeks)');
        $stepItUpView->setAttribute('points_per_activity', '15');
        $stepItUpView->setMaximumNumberOfPoints(15);
        $stepItUpView->setAllowPointsOverride(true);
        $quarterTwoGroup->addComplianceView($stepItUpView);

        $flashChallengeView = new PlaceHolderComplianceView(null, 0);
        $flashChallengeView->setName('flash_challenge');
        $flashChallengeView->setReportName('Complete a Flash Challenge');
        $flashChallengeView->setAttribute('requirement', 'Complete a Flash Challenge');
        $flashChallengeView->setAttribute('points_per_activity', '5');
        $flashChallengeView->setMaximumNumberOfPoints(20);
        $flashChallengeView->setAllowPointsOverride(true);
        $quarterTwoGroup->addComplianceView($flashChallengeView);

        $this->addComplianceViewGroup($quarterTwoGroup);


        $quarterThreeGroup = new ComplianceViewGroup('quarter_three', '3RD QUARTER: MORE MATTERS');
        $quarterThreeGroup->setPointsRequiredForCompliance(100);

        $educatonalSessionsQ3View = new PlaceHolderComplianceView(null, 0);
        $educatonalSessionsQ3View->setName('educatonal_sessions_q3');
        $educatonalSessionsQ3View->setReportName('Educational Sessions');
        $educatonalSessionsQ3View->setAttribute('requirement', 'Attend the More Matters (Nutrition) Educational Session');
        $educatonalSessionsQ3View->setAttribute('points_per_activity', '5');
        $educatonalSessionsQ3View->setMaximumNumberOfPoints(5);
        $educatonalSessionsQ3View->setAllowPointsOverride(true);
        $quarterThreeGroup->addComplianceView($educatonalSessionsQ3View);

        $quarterlyQuizQ3View = new PlaceHolderComplianceView(null, 0);
        $quarterlyQuizQ3View->setName('quarterly_quiz_q3');
        $quarterlyQuizQ3View->setReportName('Quarterly Quiz');
        $quarterlyQuizQ3View->setAttribute('requirement', 'Complete a quarterly quiz');
        $quarterlyQuizQ3View->setAttribute('points_per_activity', '5');
        $quarterlyQuizQ3View->setMaximumNumberOfPoints(10);
        $quarterlyQuizQ3View->setAllowPointsOverride(true);
        $quarterThreeGroup->addComplianceView($quarterlyQuizQ3View);

        $hydrateView = new PlaceHolderComplianceView(null, 0);
        $hydrateView->setName('hydrate');
        $hydrateView->setReportName('Hydrate to Feel Great Challenge');
        $hydrateView->setAttribute('requirement', 'Hydrate to Feel Great Challenge');
        $hydrateView->setAttribute('points_per_activity', '5');
        $hydrateView->setMaximumNumberOfPoints(5);
        $hydrateView->setAllowPointsOverride(true);
        $quarterThreeGroup->addComplianceView($hydrateView);

        $fiveADayView = new PlaceHolderComplianceView(null, 0);
        $fiveADayView->setName('5_a_day');
        $fiveADayView->setReportName('5 a Day Challenge (2 weeks)');
        $fiveADayView->setAttribute('requirement', '5 a Day Challenge (2 weeks)');
        $fiveADayView->setAttribute('points_per_activity', '15');
        $fiveADayView->setMaximumNumberOfPoints(15);
        $fiveADayView->setAllowPointsOverride(true);
        $quarterThreeGroup->addComplianceView($fiveADayView);

        $moreFruitView = new PlaceHolderComplianceView(null, 0);
        $moreFruitView->setName('more_fruit');
        $moreFruitView->setReportName('30 Days to More Fruit & Veggies');
        $moreFruitView->setAttribute('requirement', '30 Days to More Fruit & Veggies');
        $moreFruitView->setAttribute('points_per_activity', '15');
        $moreFruitView->setMaximumNumberOfPoints(15);
        $moreFruitView->setAllowPointsOverride(true);
        $quarterThreeGroup->addComplianceView($moreFruitView);

        $this->addComplianceViewGroup($quarterThreeGroup);


        $quarterFourGroup = new ComplianceViewGroup('quarter_four', '4TH QUARTER: MINDFULNESS');
        $quarterFourGroup->setPointsRequiredForCompliance(100);

        $educatonalSessionsQ4View = new PlaceHolderComplianceView(null, 0);
        $educatonalSessionsQ4View->setName('educatonal_sessions_q4');
        $educatonalSessionsQ4View->setReportName('Educational Sessions');
        $educatonalSessionsQ4View->setAttribute('requirement', 'Attend the Meditation Demo / Sleep Strategies Educational Session');
        $educatonalSessionsQ4View->setAttribute('points_per_activity', '5');
        $educatonalSessionsQ4View->setMaximumNumberOfPoints(5);
        $educatonalSessionsQ4View->setAllowPointsOverride(true);
        $quarterFourGroup->addComplianceView($educatonalSessionsQ4View);

        $quarterlyQuizQ4View = new PlaceHolderComplianceView(null, 0);
        $quarterlyQuizQ4View->setName('quarterly_quiz_q4');
        $quarterlyQuizQ4View->setReportName('Quarterly Quiz');
        $quarterlyQuizQ4View->setAttribute('requirement', 'Complete a quarterly quiz');
        $quarterlyQuizQ4View->setAttribute('points_per_activity', '5');
        $quarterlyQuizQ4View->setMaximumNumberOfPoints(10);
        $quarterlyQuizQ4View->setAllowPointsOverride(true);
        $quarterFourGroup->addComplianceView($quarterlyQuizQ4View);

        $greaterHappinessView = new PlaceHolderComplianceView(null, 0);
        $greaterHappinessView->setName('greater_happiness');
        $greaterHappinessView->setReportName('30 Days to Greater Happiness');
        $greaterHappinessView->setAttribute('requirement', '30 Days to Greater Happiness');
        $greaterHappinessView->setAttribute('points_per_activity', '15');
        $greaterHappinessView->setMaximumNumberOfPoints(15);
        $greaterHappinessView->setAllowPointsOverride(true);
        $quarterFourGroup->addComplianceView($greaterHappinessView);

        $betterSleepView = new PlaceHolderComplianceView(null, 0);
        $betterSleepView->setName('better_sleep');
        $betterSleepView->setReportName('31 Days to Better Sleep');
        $betterSleepView->setAttribute('requirement', '31 Days to Better Sleep');
        $betterSleepView->setAttribute('points_per_activity', '15');
        $betterSleepView->setMaximumNumberOfPoints(15);
        $betterSleepView->setAllowPointsOverride(true);
        $quarterFourGroup->addComplianceView($betterSleepView);

        $gratitudeJournalView = new PlaceHolderComplianceView(null, 0);
        $gratitudeJournalView->setName('gratitude_journal');
        $gratitudeJournalView->setReportName('30 Day Gratitude Journal');
        $gratitudeJournalView->setAttribute('requirement', '30 Day Gratitude Journal');
        $gratitudeJournalView->setAttribute('points_per_activity', '15');
        $gratitudeJournalView->setMaximumNumberOfPoints(15);
        $gratitudeJournalView->setAllowPointsOverride(true);
        $quarterFourGroup->addComplianceView($gratitudeJournalView);

        $this->addComplianceViewGroup($quarterFourGroup);


        $overridesActivitiesGroup = new ComplianceViewGroup('compliance_overrides', 'Annual Activities Overrides');

        foreach(array_keys($quarterlyDateRange) as $quarter) {
            foreach($this->getAnnualActivityNames() as $viewName => $reportName) {
                $overrideView = new PlaceHolderComplianceView(null, 0);
                $overrideView->setName($viewName.'_'.$quarter);
                $overrideView->setReportName($reportName.' '.strtoupper($quarter));
                $overrideView->setAllowPointsOverride(true);
                $overridesActivitiesGroup->addComplianceView($overrideView);
            }
        }
        $this->addComplianceViewGroup($overridesActivitiesGroup);

    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $quarterlyDateRange = $this->getQuerterlyRanges();

        $annualGroupStatus = $status->getComplianceViewGroupStatus('annual_activities');

        $quarter1Points = 0;
        $quarter2Points = 0;
        $quarter3Points = 0;
        $quarter4Points = 0;
        foreach($annualGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();

            // Skip it here and will handle regular_fitness_training below
            if($view->getName() == 'regular_fitness_training') continue;

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
                    } elseif(date('m/d/Y', strtotime($viewStatus->getAttribute('newest_record'))) == $viewStatus->getAttribute('newest_record')) {
                        $date = date('Y-m-d', strtotime($viewStatus->getAttribute('newest_record')));
                    }

                    if($date && $startDate <= $date && $date <= $endDate && !$pointAdded) {
                        if($quarterName == 'q1') {
                            $quarter1Points += $viewPoints;
                        } elseif ($quarterName == 'q2') {
                            $quarter2Points += $viewPoints;
                        } elseif ($quarterName == 'q3') {
                            $quarter3Points += $viewPoints;
                        } elseif ($quarterName == 'q4') {
                            $quarter4Points += $viewPoints;
                        }
                        $pointAdded = true;
                    }

                }

                if(!$pointAdded) {
                    $quarter1Points += $viewPoints;
                }

            }
        }

        $regularFitnessTrainingStatus = $annualGroupStatus->getComplianceViewStatus('regular_fitness_training');
        $quarter1Points += $regularFitnessTrainingStatus->getAttribute('q1');
        $quarter2Points += $regularFitnessTrainingStatus->getAttribute('q2');
        $quarter3Points += $regularFitnessTrainingStatus->getAttribute('q3');
        $quarter4Points += $regularFitnessTrainingStatus->getAttribute('q4');

        $status->setAttribute('q1_annual_points', $quarter1Points);
        $status->setAttribute('q2_annual_points', $quarter2Points);
        $status->setAttribute('q3_annual_points', $quarter3Points);
        $status->setAttribute('q4_annual_points', $quarter4Points);

        $quarter1Override = 0;
        $quarter2Override = 0;
        $quarter3Override = 0;
        $quarter4Override = 0;

        $overrideGroupStatus = $status->getComplianceViewGroupStatus('compliance_overrides');
        foreach($overrideGroupStatus->getComplianceViewStatuses() as $overrideViewStatus) {
            $overrideViewPoints = $overrideViewStatus->getPoints();
            if($overrideViewPoints > 0) {
                $overrideViewName = $overrideViewStatus->getComplianceView()->getName();
                foreach(array_keys($this->getAnnualActivityNames()) as $annualActivityName) {
                    if(strpos($overrideViewName, $annualActivityName) !== false) {
                        $annualActivityStatus = $status->getComplianceViewStatus($annualActivityName);
                        $annualActivityPoints = $annualActivityStatus->getPoints();
                        $annualActivityMaxPoints = $annualActivityStatus->getComplianceView()->getMaximumNumberOfPoints();
                        if($annualActivityPoints < $annualActivityMaxPoints) {
                            $totalPoints = min($annualActivityMaxPoints, $annualActivityPoints + $overrideViewPoints);

                            $actualOverrideAddedPoints = $totalPoints - $annualActivityPoints;
                            if(strpos($overrideViewName, 'q1') !== false) {
                                $quarter1Override += $actualOverrideAddedPoints;
                            } elseif (strpos($overrideViewName, 'q2') !== false) {
                                $quarter2Override += $actualOverrideAddedPoints;
                            } elseif (strpos($overrideViewName, 'q3') !== false) {
                                $quarter3Override += $actualOverrideAddedPoints;
                            } elseif (strpos($overrideViewName, 'q4') !== false) {
                                $quarter4Override += $actualOverrideAddedPoints;
                            }
                        }
                    }
                }
            }
        }
        $status->setAttribute('q1_override_points', $quarter1Override);
        $status->setAttribute('q2_override_points', $quarter2Override);
        $status->setAttribute('q3_override_points', $quarter3Override);
        $status->setAttribute('q4_override_points', $quarter4Override);
    }


    protected function getAnnualActivityNames()
    {
        return array(
            'annual_physical_exam' => 'Annual Physical Exam & Screening Follow-Up',
            'preventive_service' => 'Preventive Services',
            'flushot' => 'Receive a flu shot',
            'donate_blood' => 'Donate Blood',
            'meal_preparation_service' => 'Meal Preparation Service',
            'health_program_membership' => 'Health Program Membership',
            'regular_fitness_training' => 'Regular Fitness Training',
            'participate_walk_run' => 'Participate in walk/run (1 pt/km)',
            'participate_half_marathon' => 'Participate in half-marathon, Sprint distance triathlon, or Bike Tour',
            'participate_marathon' => 'Participate in a marathon or Olympic distance triathlon, or Bike Tour',
            'elearning' => 'E-learning',
            'smoking_cessation' => 'Smoking Cessation',
            'other_wellness_events' => 'Other HFMA Wellness Events'
        );

    }

    protected function getQuerterlyRanges()
    {
        $ranges = array(
            'q1' => array('2018-02-01', '2018-03-31'),
            'q2' => array('2018-04-01', '2018-06-30'),
            'q3' => array('2018-07-01', '2018-09-30'),
            'q4' => array('2018-10-01', '2018-12-31')
        );

        return $ranges;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(null, null, true);

        $printer->addStatusFieldCallback('February - Steps', function(ComplianceProgramStatus $status) {
            $data = get_all_fitbit_data(
                $status->getUser()->id,
                '2018-02-01',
                '2018-02-28'
            );

            return $data['total_steps'];
        });

        $printer->addStatusFieldCallback('Quarter 1 Total Points (2/1-3/31/18)', function(ComplianceProgramStatus $status) {
            $q1GroupPoints = $status->getComplianceViewGroupStatus('quarter_one')->getPoints();
            $q1AnnualPoints = $status->getAttribute('q1_annual_points');
            $q1OverridePoints = $status->getAttribute('q1_override_points');
            return $q1GroupPoints + $q1AnnualPoints + $q1OverridePoints;
        });

        $printer->addStatusFieldCallback('Quarter 2 Total Points (4/1-6/30/18)', function(ComplianceProgramStatus $status) {
            $q2GroupPoints = $status->getComplianceViewGroupStatus('quarter_two')->getPoints();
            $q2AnnualPoints = $status->getAttribute('q2_annual_points');
            $q2OverridePoints = $status->getAttribute('q2_override_points');
            return $q2GroupPoints + $q2AnnualPoints + $q2OverridePoints;
        });

        $printer->addStatusFieldCallback('Quarter 3 Total Points (7/1-9/30/18)', function(ComplianceProgramStatus $status) {
            $q3GroupPoints = $status->getComplianceViewGroupStatus('quarter_three')->getPoints();
            $q3AnnualPoints = $status->getAttribute('q3_annual_points');
            $q3OverridePoints = $status->getAttribute('q3_override_points');
            return $q3GroupPoints + $q3AnnualPoints + $q3OverridePoints;
        });

        $printer->addStatusFieldCallback('Quarter 4 Total Points (10/1-12/31/18)', function(ComplianceProgramStatus $status) {
            $q4GroupPoints = $status->getComplianceViewGroupStatus('quarter_four')->getPoints();
            $q4AnnualPoints = $status->getAttribute('q4_annual_points');
            $q4OverridePoints = $status->getAttribute('q4_override_points');
            return $q4GroupPoints + $q4AnnualPoints + $q4OverridePoints;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new HFMA2018ComplianceProgramReportPrinter();

        return $printer;
    }

}


class HFMA2018ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

        $q1GroupStatus = $status->getComplianceViewGroupStatus('quarter_one');
        $q2GroupStatus = $status->getComplianceViewGroupStatus('quarter_two');
        $q3GroupStatus = $status->getComplianceViewGroupStatus('quarter_three');
        $q4GroupStatus = $status->getComplianceViewGroupStatus('quarter_four');

        $annualPhysicalExam =
            $status->getComplianceViewStatus("annual_physical_exam")->getPoints() +
            $status->getComplianceViewStatus("annual_physical_exam_q1")->getPoints() +
            $status->getComplianceViewStatus("annual_physical_exam_q2")->getPoints() +
            $status->getComplianceViewStatus("annual_physical_exam_q3")->getPoints() +
            $status->getComplianceViewStatus("annual_physical_exam_q4")->getPoints();
        $preventiveServices =
            $status->getComplianceViewStatus("preventive_service")->getPoints() +
            $status->getComplianceViewStatus("preventive_service_q1")->getPoints() +
            $status->getComplianceViewStatus("preventive_service_q2")->getPoints() +
            $status->getComplianceViewStatus("preventive_service_q3")->getPoints() +
            $status->getComplianceViewStatus("preventive_service_q4")->getPoints();
        $fluShot =
            $status->getComplianceViewStatus("flushot")->getPoints() +
            $status->getComplianceViewStatus("flushot_q1")->getPoints() +
            $status->getComplianceViewStatus("flushot_q2")->getPoints() +
            $status->getComplianceViewStatus("flushot_q3")->getPoints() +
            $status->getComplianceViewStatus("flushot_q4")->getPoints();
        $donateBlood =
            $status->getComplianceViewStatus("donate_blood")->getPoints() +
            $status->getComplianceViewStatus("donate_blood_q1")->getPoints() +
            $status->getComplianceViewStatus("donate_blood_q2")->getPoints() +
            $status->getComplianceViewStatus("donate_blood_q3")->getPoints() +
            $status->getComplianceViewStatus("donate_blood_q4")->getPoints();
        $mealPrep =
            $status->getComplianceViewStatus("meal_preparation_service")->getPoints() +
            $status->getComplianceViewStatus("meal_preparation_service_q1")->getPoints() +
            $status->getComplianceViewStatus("meal_preparation_service_q2")->getPoints() +
            $status->getComplianceViewStatus("meal_preparation_service_q3")->getPoints() +
            $status->getComplianceViewStatus("meal_preparation_service_q4")->getPoints();
        $healthProgramMembership =
            $status->getComplianceViewStatus("health_program_membership")->getPoints() +
            $status->getComplianceViewStatus("health_program_membership_q1")->getPoints() +
            $status->getComplianceViewStatus("health_program_membership_q2")->getPoints() +
            $status->getComplianceViewStatus("health_program_membership_q3")->getPoints() +
            $status->getComplianceViewStatus("health_program_membership_q4")->getPoints();
        $regularFitnessTraining =
            $status->getComplianceViewStatus("regular_fitness_training")->getPoints() +
            $status->getComplianceViewStatus("regular_fitness_training_q1")->getPoints() +
            $status->getComplianceViewStatus("regular_fitness_training_q2")->getPoints() +
            $status->getComplianceViewStatus("regular_fitness_training_q3")->getPoints() +
            $status->getComplianceViewStatus("regular_fitness_training_q4")->getPoints();
        $participateWalk =
            $status->getComplianceViewStatus("participate_walk_run")->getPoints() +
            $status->getComplianceViewStatus("participate_walk_run_q1")->getPoints() +
            $status->getComplianceViewStatus("participate_walk_run_q2")->getPoints() +
            $status->getComplianceViewStatus("participate_walk_run_q3")->getPoints() +
            $status->getComplianceViewStatus("participate_walk_run_q4")->getPoints();
        $participateHalfMarathon =
            $status->getComplianceViewStatus("participate_half_marathon")->getPoints() +
            $status->getComplianceViewStatus("participate_half_marathon_q1")->getPoints() +
            $status->getComplianceViewStatus("participate_half_marathon_q2")->getPoints() +
            $status->getComplianceViewStatus("participate_half_marathon_q3")->getPoints() +
            $status->getComplianceViewStatus("participate_half_marathon_q4")->getPoints();
        $participateMarathon =
            $status->getComplianceViewStatus("participate_marathon")->getPoints() +
            $status->getComplianceViewStatus("participate_marathon_q1")->getPoints() +
            $status->getComplianceViewStatus("participate_marathon_q2")->getPoints() +
            $status->getComplianceViewStatus("participate_marathon_q3")->getPoints() +
            $status->getComplianceViewStatus("participate_marathon_q4")->getPoints();
        $elearning =
            $status->getComplianceViewStatus("elearning")->getPoints() +
            $status->getComplianceViewStatus("elearning_q1")->getPoints() +
            $status->getComplianceViewStatus("elearning_q2")->getPoints() +
            $status->getComplianceViewStatus("elearning_q3")->getPoints() +
            $status->getComplianceViewStatus("elearning_q4")->getPoints();
        $smokingCessation =
            $status->getComplianceViewStatus("smoking_cessation")->getPoints() +
            $status->getComplianceViewStatus("smoking_cessation_q1")->getPoints() +
            $status->getComplianceViewStatus("smoking_cessation_q2")->getPoints() +
            $status->getComplianceViewStatus("smoking_cessation_q3")->getPoints() +
            $status->getComplianceViewStatus("smoking_cessation_q4")->getPoints();
        $otherWellnessEvents =
            $status->getComplianceViewStatus("other_wellness_events")->getPoints() +
            $status->getComplianceViewStatus("other_wellness_events_q1")->getPoints() +
            $status->getComplianceViewStatus("other_wellness_events_q2")->getPoints() +
            $status->getComplianceViewStatus("other_wellness_events_q3")->getPoints() +
            $status->getComplianceViewStatus("other_wellness_events_q4")->getPoints();


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
                background-color:#436EEE;
                font-weight:normal;
                color:#FFFFFF;
                font-size:10pt;
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

            #programTable {
                border-collapse: collapse;
                margin:0 auto;
            }

            #programTable tr th, #programTable tr td{
                padding: 10px;
                text-align: center;
                border:1px solid #0063dc;
            }

        </style>

        <script type="text/javascript">
            // Set max points text for misc points earned

            $(function() {
                $('.phipTable .headerRow.headerRow-annual_activities').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#006699;'>ANNUAL ACTIVITIES - Earn points at anytime during 2018</th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_one').before("<tr><td colspan='6' style='height:60px; text-align:center; font-style: italic; color: white; background-color:#006699; font-size:10pt; '>**Points updated manually will be credited in the Quarter that the activity is <strong>submitted</strong>. Points for Regular Fitness Training & eLearning are credited based on the date activity is completed.</td></tr>");
                $('.phipTable .headerRow.headerRow-quarter_one').before("<tr><th colspan='6' style='height:20px; border:none;'></th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_one').before("<tr><th colspan='6' style='height:60px; text-align:center; color: white; background-color:#006699; font-size:13pt; '>QUARTERLY WELLNESS ACTIVITIES: <br />Earn points ONLY during the designated time period</th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_one').before("<tr><th colspan='6' style='height:10px; border:none;'></th></tr>");

                $('.phipTable .headerRow.headerRow-quarter_one').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#006699;'>1ST QUARTER: FINANCIAL FITNESS FEBRUARY 1 - MARCH 31, 2018</th></tr>");

                $('.phipTable .headerRow.headerRow-quarter_two').before("<tr style='height:36px;'><th colspan='2' style='text-align:center;'>QUARTER 1 ACTIVITIES SUBTOTAL</th><th></th><th style='text-align:center;'><?php echo $q1GroupStatus->getPoints(); ?></th><th></th><th></th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_two').before("<tr style='height:36px; text-align:center; color: white; background-color:#26B000;'><th colspan='2' style='text-align:center;'>TOTAL POINTS EARNED 2/1—3/31/18</th><th></th><th style='text-align:center;'><?php echo $q1GroupStatus->getPoints() + $status->getAttribute('q1_annual_points') + $status->getAttribute('q1_override_points'); ?></th><th></th><th></th></tr>");

                $('.phipTable .headerRow.headerRow-quarter_two').before("<tr><th colspan='6' style='height:10px; border:none;'></th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_two').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#006699;'>2nd QUARTER: SPRING TRAINING APRIL 1 - JUNE 30, 2018</th></tr>");

                $('.phipTable .headerRow.headerRow-quarter_three').before("<tr style='height:36px;'><th colspan='2' style='text-align:center;'>QUARTER 2 ACTIVITIES SUBTOTAL</th><th></th><th style='text-align:center;'><?php echo $q2GroupStatus->getPoints(); ?></th><th></th><th></th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_three').before("<tr style='height:36px; text-align:center; color: white; background-color:#26B000;'><th colspan='2' style='text-align:center;'>TOTAL POINTS EARNED  4/1—6/30/18</th><th></th><th style='text-align:center;'><?php echo $q2GroupStatus->getPoints() + $status->getAttribute('q2_annual_points') + $status->getAttribute('q2_override_points'); ?></th><th></th><th></th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_three').before("<tr><th colspan='6' style='height:10px; border:none;'></th></tr>");

                $('.phipTable .headerRow.headerRow-quarter_three').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#006699;'>3RD QUARTER: MORE MATTERS JULY 1 - SEPTEMBER 30, 2018</th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_four').before("<tr style='height:36px;'><th colspan='2' style='text-align:center;'>QUARTER 3 ACTIVITIES SUBTOTAL</th><th></th><th style='text-align:center;'><?php echo $q3GroupStatus->getPoints(); ?></th><th></th><th></th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_four').before("<tr style='height:36px; text-align:center; color: white; background-color:#26B000;'><th colspan='2' style='text-align:center;'>TOTAL POINTS EARNED 7/1—9/30/18</th><th></th><th style='text-align:center;'><?php echo $q3GroupStatus->getPoints() + $status->getAttribute('q3_annual_points') + $status->getAttribute('q3_override_points'); ?></th><th></th><th></th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_four').before("<tr><th colspan='6' style='height:10px; border:none;'></th></tr>");
                $('.phipTable .headerRow.headerRow-quarter_four').before("<tr><th colspan='6' style='height:36px; text-align:center; color: white; background-color:#006699;'>4TH QUARTER: MINDFULNESS OCTOBER 1 - DECEMBER 31, 2018</th></tr>");

                $('.phipTable .view-gratitude_journal').after("<tr style='height:36px; text-align:center; color: white; background-color:#26B000;'><th colspan='2' style='text-align:center;'>TOTAL POINTS EARNED 10/1—12/31/18</th><th></th><th style='text-align:center;'><?php echo $q4GroupStatus->getPoints() + $status->getAttribute('q4_annual_points') + $status->getAttribute('q4_override_points'); ?></th><th></th><th></th></tr>");
                $('.phipTable .view-gratitude_journal').after("<tr style='height:36px;'><th colspan='2' style='text-align:center;'>QUARTER 4 ACTIVITIES SUBTOTAL</th><th></th><th style='text-align:center;'><?php echo $q4GroupStatus->getPoints(); ?></th><th></th><th></th></tr>");

                $('.phipTable .headerRow.headerRow-quarter_one').children(':eq(0)').html('<strong>2</strong>. Program');
                $('.phipTable .headerRow.headerRow-quarter_one').children(':eq(1)').html('Requirement');
                $('.phipTable .headerRow.headerRow-quarter_one').children(':eq(2)').html('Points Per Activity');
                $('.phipTable .headerRow.headerRow-quarter_two').children(':eq(0)').html('<strong>3</strong>. Program');
                $('.phipTable .headerRow.headerRow-quarter_two').children(':eq(1)').html('Requirement');
                $('.phipTable .headerRow.headerRow-quarter_two').children(':eq(2)').html('Points Per Activity');
                $('.phipTable .headerRow.headerRow-quarter_three').children(':eq(0)').html('<strong>4</strong>. Program');
                $('.phipTable .headerRow.headerRow-quarter_three').children(':eq(1)').html('Requirement');
                $('.phipTable .headerRow.headerRow-quarter_three').children(':eq(2)').html('Points Per Activity');
                $('.phipTable .headerRow.headerRow-quarter_four').children(':eq(0)').html('<strong>5</strong>. Program');
                $('.phipTable .headerRow.headerRow-quarter_four').children(':eq(1)').html('Requirement');
                $('.phipTable .headerRow.headerRow-quarter_four').children(':eq(2)').html('Points Per Activity');

                $('.phipTable tr td.points').each(function() {
                    $(this).html($(this).html() + ' points');
                });

                $('.phipTable .headerRow.headerRow-annual_activities').children(':eq(1)').html('Requirement');
                $('.phipTable .headerRow.headerRow-annual_activities').children(':eq(2)').html('Points Per Activity');

                $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)')
                    .html('<strong>B</strong>. BIOMETRIC BONUS POINTS: Based on Wellness Screening Results');
                $('.view-comply_with_blood_pressure_screening_test').children(':eq(0)').attr('rowspan', 3);
                $('.view-comply_with_blood_pressure_screening_test td.links').attr('rowspan', 3);
                $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test').children(':eq(0)').remove();
                $('.view-comply_with_triglycerides_screening_test').children(':eq(0)').remove();
                $('.view-comply_with_total_hdl_cholesterol_ratio_screening_test td.links').remove();
                $('.view-comply_with_triglycerides_screening_test td.links').remove();

                $('.view-preventive_service').children(':eq(0)').attr('rowspan', 2);
                $('.view-preventive_service td.links').attr('rowspan', 2);
                $('.view-flushot').children(':eq(0)').remove();
                $('.view-flushot td.links').remove();

                $('.view-donate_blood').children(':eq(0)').html('<strong>C</strong>. Donate Blood');
                $('.view-meal_preparation_service').children(':eq(0)').html('<strong>D</strong>. Meal Preparation Service');
                $('.view-health_program_membership').children(':eq(0)').html('<strong>E</strong>. Health Program Membership');
                $('.view-regular_fitness_training').children(':eq(0)').html('<strong>F</strong>. Regular Fitness Training');
                $('.view-participate_walk_run').children(':eq(0)').html('<strong>G</strong>. Athletic Events');
                $('.view-participate_walk_run').children('.links').attr('rowspan', 3);
                $('.view-participate_half_marathon').children('.links').remove();
                $('.view-participate_marathon').children('.links').remove();

                $('.view-elearning').children(':eq(0)').html('<strong>H</strong>. E-learning');
                $('.view-smoking_cessation').children(':eq(0)').html('<strong>I</strong>. Smoking Cessation');
                $('.view-other_wellness_events').children(':eq(0)').html('<strong>J</strong>. Other HFMA Wellness Events');
                

                $('.view-participate_walk_run').children(':eq(0)').attr('rowspan', 3);
                $('.view-participate_half_marathon').children(':eq(0)').remove();
                $('.view-participate_marathon').children(':eq(0)').remove();

                $('.view-hfma_fitness_challenge').children(':eq(0)').html('<strong>C</strong>. QUARTERLY CHALLENGES');
                $('.view-hfma_fitness_challenge').children(':eq(0)').attr('rowspan', 3);
                $('.view-step_it_up').children(':eq(0)').remove();
                $('.view-flash_challenge').children(':eq(0)').remove();

                $('.view-hydrate').children(':eq(0)').html('<strong>C</strong>. QUARTERLY CHALLENGES');
                $('.view-hydrate').children(':eq(0)').attr('rowspan', 3);
                $('.view-5_a_day').children(':eq(0)').remove();
                $('.view-more_fruit').children(':eq(0)').remove();

                $('.view-greater_happiness').children(':eq(0)').html('<strong>C</strong>. QUARTERLY CHALLENGES');
                $('.view-greater_happiness').children(':eq(0)').attr('rowspan', 3);
                $('.view-better_sleep').children(':eq(0)').remove();
                $('.view-gratitude_journal').children(':eq(0)').remove();


                $('.phipTable .headerRow.headerRow-compliance_overrides').nextAll().css('display', 'none');
                $('.phipTable .headerRow.headerRow-compliance_overrides').css('display', 'none');

                $('.view-annual_physical_exam .points')[0].innerHTML = "<?= $annualPhysicalExam; ?> points";
                $('.view-preventive_service .points')[0].innerHTML = "<?= $preventiveServices; ?> points";
                $('.view-flushot .points')[0].innerHTML = "<?= $fluShot; ?> points";
                $('.view-donate_blood .points')[0].innerHTML = "<?= $donateBlood; ?> points";
                $('.view-meal_preparation_service .points')[0].innerHTML = "<?= $mealPrep; ?> points";
                $('.view-health_program_membership .points')[0].innerHTML = "<?= $healthProgramMembership; ?> points";
                $('.view-regular_fitness_training .points')[0].innerHTML = "<?= $regularFitnessTraining; ?> points";
                $('.view-participate_walk_run .points')[0].innerHTML = "<?= $participateWalk; ?> points";
                $('.view-participate_half_marathon .points')[0].innerHTML = "<?= $participateHalfMarathon; ?> points";
                $('.view-participate_marathon .points')[0].innerHTML = "<?= $participateMarathon; ?> points";
                $('.view-elearning .points')[0].innerHTML = "<?= $elearning; ?> points";
                $('.view-smoking_cessation .points')[0].innerHTML = "<?= $smokingCessation; ?> points";
                $('.view-other_wellness_events .points')[0].innerHTML = "<?= $otherWellnessEvents; ?> points";

            });
        </script>
        <!-- Text atop report card-->
        <p style="text-align:center; font-size: 13pt; font-weight: bolder;">The HFMA Wellness Program gives you tools and motivation to improve and maintain your health.</p>
        <p style="text-align:center; font-size: 12pt; font-weight: bolder;">NEW 2018 UPDATE! All HFMA Benefit-Employees are eligible to participate. </p>
        <p>Dear <?php echo $status->getUser()->getFullName() ?>,</p>

        <p>HFMA cares about your health and <strong>overall well-being!</strong> We have partnered with HMI Health and
            Axion to implement our Wellness Program. The wellness program provides you with fun, robust programming options
            geared towards specific areas of your health that need improvement. This Wellness Program is your way to
            better, healthier living.</p>

        <p style="font-weight: bolder; font-size: 12pt; text-align: center">HOW DOES THE 2018 PROGRAM WORK? </p>
        <p>
            Participation in the program will earn wellness points that will be tracked through the HMI Wellness Website
            at <a href="http://www.myhmihealth.com">www.myhmihealth.com</a>. Rewards will be based on points earned during
            the wellness year (2/1/18 through 12/31/18).
        </p>

        <div>
            <table id="programTable">
                <tr style="background-color:#006699; color: #ffffff;">
                    <th colspan="4">WELLNESS REWARDS STRUCTURE</th>
                </tr>
                <tr style="height:5px;">
                </tr>
                <tr style="background-color:#006699; color: #ffffff">
                    <th>INCENTIVE PERIOD</th>
                    <th>QUARTERLY INCENTIVE REQUIREMENT</th>
                    <th>REWARD*</th>
                    <th>REWARD DISTRIBUTION*</th>
                </tr>
                <tr>
                    <td>February 1 — March 31, 2018</td>
                    <td>Earn 50 Points</td>
                    <td>$50.00</td>
                    <td><em>4/13/18</em></td>
                </tr>
                <tr>
                    <td>April 1 — June 30, 2018</td>
                    <td>Earn 100 Points</td>
                    <td>$100.00</td>
                    <td><em>7/13/18</em></td>
                </tr>
                <tr>
                    <td>July 1 — September 30, 2018</td>
                    <td>Earn 100 Points</td>
                    <td>$100.00</td>
                    <td><em>10/15/18</em></td>
                </tr>
                <tr>
                    <td>October 1 — December 31, 2018</td>
                    <td>Earn 100 Points</td>
                    <td>$100.00</td>
                    <td><em>1/15/18</em></td>
                </tr>
                <tr style="background-color:#006699; font-weight: 600; color: #ffffff;">
                    <td colspan="2">TOTAL REWARDS</td>
                    <td>$350.00</td>
                    <td>$100.00</td>
                </tr>
            </table>
        </div><br />

        <p style="font-weight: bold; text-align: center">
            *Earned Rewards will be credited via payroll in the period following the end of each Quarterly Incentive Period.
            Must be an active, benefit-eligible employee on the pay dates indicated above in order to receive reward.
        </p>


        <p style="font-weight: bold; text-align: center">See the following chart for a description of the wellness points you can earn.</p>

        <p>
            <strong>Notice:</strong> <em>The HFMA Wellness Program is committed to helping you achieve your best health.
            Rewards for participating in a wellness program are available to all employees. If you think you might be
            unable to meet a standard for a reward under this wellness program, you might qualify for an opportunity to
            earn the same reward by different means. Contact Human Resources and we will work with you (and, if you wish,
            your doctor) to find a wellness program with the same reward that is right for you in light of your health status.</em>
        </p>


        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>

        <?php
    }


    public $showUserNameInLegend = true;
}
