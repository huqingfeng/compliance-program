<?php

require_once 'lib/functions/getExtendedRiskForUser2010.php';

use hpn\steel\query\SelectQuery;

error_reporting(0);

class MeridianHealth2022CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.strtotime('2022-06-15')),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter(),
                'required_fields'  => array('cotinine')
            )
        );

        return $data;
    }
}

class MeridianHealth2022ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MeridianHealth2022WMS2Printer();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowGroupNameInViewName(false);
        $printer->setShowUserFields(true, true, false, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, true);
        $printer->setShowCompliant(false, false, true);
        $printer->setShowPoints(false, false, true);

        $printer->addCallbackField('hiredate', function(User $user) {
            return $user->getHiredate();
        });

        $printer->addCallbackField('location', function(User $user) {
            return $user->getLocation();
        });

        $printer->addCallbackField('employee_ssn', function (User $user) {
            if($user->getRelationshipType() == 2) {
                return $user->getRelationshipUser() ? $user->getRelationshipUser()->getSocialSecurityNumber() : '';
            } else {
                return $user->getSocialSecurityNumber();
            }
        });

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $user = $status->getUser();
            $data = array();

            $data['Cotinine'] = $status->getComplianceViewStatus('tobacco')->getComment() ?? "Not Taken";

            $wellnessMeasuresGroup = $status->getComplianceViewGroupStatus('Wellness Measures');
            $tobaccoGroup = $status->getComplianceViewGroupStatus('Tobacco Status');
            $wellnessActivitiesGroup = $status->getComplianceViewGroupStatus('Wellness Activities');
            $nutritionFitnessGroup = $status->getComplianceViewGroupStatus('Nutrition and Fitness');
            $preventionActivitiesGroup = $status->getComplianceViewGroupStatus('Prevention Activities');

            $wellnessMeasuresPoints = 0;        $wellnessMeasuresMaxPoints = 0;
            $tobaccoPoints = 0;                 $tobaccoMaxPoints = 0;
            $wellnessActivitiesPoints = 0;      $wellnessActivitiesMaxPoints = 0;
            $nutritionFitnessPoints = 0;        $nutritionFitnessMaxPoints = 0;
            $preventionActivitiesPoints = 0;    $preventionActivitiesMaxPoints = 0;

            foreach($wellnessMeasuresGroup->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();
                $wellnessMeasuresPoints += $viewStatus->getPoints();
                $wellnessMeasuresMaxPoints += $view->getMaximumNumberOfPoints();
            }

            foreach($tobaccoGroup->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();
                $tobaccoPoints += $viewStatus->getPoints();
                $tobaccoMaxPoints += $view->getMaximumNumberOfPoints();
            }

            foreach($wellnessActivitiesGroup->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();
                $wellnessActivitiesPoints += $viewStatus->getPoints();
                $wellnessActivitiesMaxPoints += $view->getMaximumNumberOfPoints();
            }

            foreach($nutritionFitnessGroup->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();
                $nutritionFitnessPoints += $viewStatus->getPoints();
                $nutritionFitnessMaxPoints += $view->getMaximumNumberOfPoints();
            }

            foreach($preventionActivitiesGroup->getComplianceViewStatuses() as $viewStatus) {
                $view = $viewStatus->getComplianceView();
                $preventionActivitiesPoints += $viewStatus->getPoints();
                $preventionActivitiesMaxPoints += $view->getMaximumNumberOfPoints();
            }

            $wellnessMeasuresStatus = $status->getComplianceViewStatus('wellness_measures');
            $tobaccoStatus = $status->getComplianceViewStatus('tobacco');

            $step1Compliance = $wellnessMeasuresStatus->isCompliant();
            $step2Compliance = $tobaccoStatus->isCompliant();
            $step3Total = min(360, $wellnessActivitiesPoints + $nutritionFitnessPoints + $preventionActivitiesPoints);
            $step3Compliance = $step3Total >= 360;

            $totalPoints = $wellnessMeasuresPoints + $tobaccoPoints + $step3Total;

            $data['Step 1 Earned'] = ($step1Compliance) ? "Yes" : "No";
            $data['Step 2 Earned'] = ($step2Compliance) ? "Yes" : "No";
            $data['Step 3 Earned'] = ($step3Compliance) ? "Yes" : "No";
            $data['Step 3 Earnings'] = "$".$step3Total;
            $data['Total Earnings'] = "$".$totalPoints;

            return $data;
        });

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        date_default_timezone_set('America/New_York');

        $programName = "MeridianHealth2022";
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();
        $coreEnd = '2022-06-15';

        // Wellness Measures Group
        $wellnessMeasuresGroup = new ComplianceViewGroup('Wellness Measures');

        // HRA Compliancy
        $hra = new CompleteHRAComplianceView($programStart, $coreEnd);
        $hra->setName('hra');
        $hra->setReportName('HRA (complete between 1/1/2022-6/15/2022)');
        $hra->emptyLinks();
        $hra->setAttribute('hide', true);
        $wellnessMeasuresGroup->addComplianceView($hra);

        // Screening Compliancy
        $screening = new MeridianHealth2022CompleteScreeningComplianceView($programStart, $coreEnd);
        $screening->setName('screening');
        $screening->setReportName('Screening + tobacco test (complete between 1/1/2022-6/15/2022)');
        $screening->setAttribute('hide', true);
        $screening->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
          if($user->id == 3684369)
              $status->setStatus(ComplianceStatus::COMPLIANT);
        });
        $wellnessMeasuresGroup->addComplianceView($screening);

        // Wellness Measures Compliancy
        $wellnessMeasures = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $wellnessMeasures->setName('wellness_measures');
        $wellnessMeasures->setReportName('1. HRA (complete between 1/1/2022-6/15/2022)<br>
        2. Screening + tobacco test (complete between 1/1/2022-6/15/2022)');
        $wellnessMeasures->addLink(new Link('Take HRA', '/compliance/meridian-health-2022/hra/content/my-health'));
        $wellnessMeasures->addLink(new Link('Schedule Screening', '/compliance/meridian-health-2022/schedule/content/schedule-appointments'));
        $wellnessMeasures->setMaximumNumberOfPoints(280);
        $wellnessMeasures->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(280, 0, 0, 0));
        $wellnessMeasuresGroup->addComplianceView($wellnessMeasures);

        $this->addComplianceViewGroup($wellnessMeasuresGroup);

        // Tobacco Group
        $tobaccoGroup = new ComplianceViewGroup('Tobacco Status');

        // Tobacco Compliancy
        $tobacco = new ComplyWithCotinineScreeningTestDirectComplianceView($programStart, $coreEnd);
        $tobacco->setName('tobacco');
        $tobacco->setReportName('Tobacco Cessation—Complete “Living Free” if positive for tobacco (complete between 1/1-9/1)');
        $tobacco->setMaximumNumberOfPoints(80);
        $tobacco->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(80, 0, 0, 0));
        $tobacco->addLink(new Link('Living Free Course', '/search-learn/lifestyle-management/content/12088'));
        $tobaccoGroup->addComplianceView($tobacco);

        // Living Free Compliancy
        $livingFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingFree->setName('living_free');
        $livingFree->setReportName('Living Free Course');
        $livingFree->setAttribute('hide', true);
        $tobaccoGroup->addComplianceView($livingFree);

        $this->addComplianceViewGroup($tobaccoGroup);

        // Wellness Activities Group
        $wellnessActivitiesGroup = new ComplianceViewGroup('Wellness Activities');

        // Biometric Goals Compliancy
        $biometric = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $biometric->setName('biometric_goals');
        $biometric->setReportName('Biometrics 3 of 5 (or e-learning)<br>
                            <span class="tab"></span> 1) Waist Measure: men ≤ 40” / women ≤ 35"<br>
                            <span class="tab"></span> 2) Fasting Glucose: ≤ 100 or Hemoglobin A1c < 5.7<br>
                            <span class="tab"></span> 3) Blood Pressure: ≤ 130/85 <br>
                            <span class="tab"></span> 4) HDL Cholesterol: men ≥ 40 / women ≥ 50<br>
                            <span class="tab"></span> 5) Triglycerides: ≤ 150');

        $biometric->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(120, 0, 0, 0));
        $biometric->setMaximumNumberOfPoints(120);
        $biometric->addLink(new Link('Schedule Screening', '/compliance/meridian-health-2022/schedule/content/schedule-appointments'));
        $wellnessActivitiesGroup->addComplianceView($biometric);

        // Waist Compliancy
        $waist = new ComplyWithWaistScreeningTestComplianceView($programStart, $coreEnd);
        $waist->setName('waist');
        $waist->setReportName('Waist Circumference');
        $waist->overrideTestRowData(null, null, 40, null, 'M');
        $waist->overrideTestRowData(null, null, 35, null, 'F');
        $waist->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($waist);

        // Blood Pressure Compliancy
        $blood_pressure = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $coreEnd);
        $blood_pressure->setName('blood_pressure');
        $blood_pressure->setReportName('Blood Pressure');
        $blood_pressure->overrideSystolicTestRowData(null, null, 130, null);
        $blood_pressure->overrideDiastolicTestRowData(null, null, 85, null);
        $blood_pressure->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($blood_pressure);

        // HDL Compliancy
        $hdl = new ComplyWithHDLScreeningTestComplianceView($programStart, $coreEnd);
        $hdl->setName('hdl');
        $hdl->setReportName('HDL');
        $hdl->overrideTestRowData(null, 40, null, null, 'M');
        $hdl->overrideTestRowData(null, 50, null, null, 'F');
        $hdl->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($hdl);

        // Triglycerides Compliancy
        $triglycerides = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $coreEnd);
        $triglycerides->setName('triglycerides');
        $triglycerides->setReportName('Triglycerides');
        $triglycerides->overrideTestRowData(null, null, 150, null);
        $triglycerides->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($triglycerides);

        // Glucose Compliancy
        $glucose = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $coreEnd);
        $glucose->setName('glucose');
        $glucose->setReportName('Fasting Glucose');
        $glucose->overrideTestRowData(null, null, 100, null);
        $glucose->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($glucose);

        // Hemoglobin Compliancy
        $ha1c = new ComplyWithHa1cScreeningTestComplianceView($programStart, $coreEnd);
        $ha1c->setName('hemoglobin');
        $ha1c->setReportName('Hemoglobin A1C');
        $ha1c->overrideTestRowData(0, .1, 5.9, null);
        $ha1c->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($ha1c);

        // Elearning Compliancy
        $elearning = new CompleteELearningLessonsComplianceView($programStart, $programEnd);
        $elearning->setPointsPerLesson(1);
        $elearning->setName('elearning');
        $elearning->setReportName('Elearning – if you do not have 3 of 5 biometrics in range, you can still earn $120 by completing 1 e-learning lesson per biometric out of range.');
        $elearning->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($elearning);

        // Health Coaching Program
        $coachingProgram = new CompleteCoachingAppointmentComplianceView($programStart, $programEnd, 2);
        $coachingProgram->setName('coaching_program');
        $coachingProgram->setReportName('Health Coaching - Unqualified<br>
            <ul>
                <li>Complete 2 health coaching calls if qualified based on high risks from screening + HRA </li>
                <li>Call 1-866-682-3020 ext. 125 to schedule your first coaching call. Must enroll by 7/30</li>
            </ul>');
        $coachingProgram->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(40, 0, 0, 0));
        $coachingProgram->setMaximumNumberOfPoints(0);
        $coachingProgram->setAttribute('disabled', true);
        $wellnessActivitiesGroup->addComplianceView($coachingProgram);

        // Results Consultation Compliancy
        $consultation = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $consultation->setName('consultation');
        $consultation->setReportName('Results Consultation Call<br>
            <ul>
                <li>Discuss your screening + HRA results with a health coach to set goals</li>
                <li>Call 1-866-682-3020 ext. 204 to schedule your consult no later than June 30th (actual consult calls must be completed no later than July 15th)</li>
            </ul>');
        $consultation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $consultation->setMaximumNumberOfPoints(15);
        $wellnessActivitiesGroup->addComplianceView($consultation);

        // Being Videos
        $beingVideos = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $beingVideos->setName('being_videos');
        $beingVideos->setReportName('Being Videos <strong>($10/video)</strong>');
        $beingVideos->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $user_id = $user->id;
            $result = SelectQuery::create()
            ->select('count(distinct(lesson_id)) as lessons')
            ->from('tbk_lessons_complete tbk')
            ->where('tbk.user_id = ?', array($user_id))
            ->andWhere('tbk.completion_date BETWEEN ? AND ?', array('2022-01-01', '2022-11-01'))
            ->hydrateSingleRow()
            ->execute();

            $lessons = $result['lessons'] ?? 0;
            $points = intval($status->getComment()) ?? 0;
            $points += $lessons * 10;

            if ($points > 60) {
                $points = 60;
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            $status->setPoints($points);

        });
        $beingVideos->setMaximumNumberOfPoints(60);
        $beingVideos->addLink(new Link('Being Videos', '/search-learn/learn-by-video/content/learn-by-video'));
        $wellnessActivitiesGroup->addComplianceView($beingVideos);

        // Online Lifestyle Management Compliancy
        $lifestyleManagement = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $lifestyleManagement->setName('lifestyle_management');
        $lifestyleManagement->setReportName('Online Lifestyle Management Courses (link to this in Resources) <strong>($40/course)</strong>');
        $lifestyleManagement->setMaximumNumberOfPoints(160);
        $lifestyleManagement->addLink(new Link('Lifestyle Management', '/search-learn/lifestyle-management/content/12088'));
        $wellnessActivitiesGroup->addComplianceView($lifestyleManagement);

        // Living Fit Compliancy
        $livingFit = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingFit->setName('living_fit');
        $livingFit->setReportName('Living Fit 90 Day Walking Challenge');
        $livingFit->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($livingFit);

        // Living Lean Compliancy
        $livingLean = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingLean->setName('living_lean');
        $livingLean->setReportName('Living Lean Course');
        $livingLean->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($livingLean);

        // Living Easy Compliancy
        $livingEasy = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingEasy->setName('living_easy');
        $livingEasy->setReportName('Living Easy Course');
        $livingEasy->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($livingEasy);

        // Living Well Rested Compliancy
        $livingWellRested = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingWellRested->setName('living_well_rested');
        $livingWellRested->setReportName('Living Well Rested Course');
        $livingWellRested->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($livingWellRested);

        // Living Smart Compliancy
        $livingSmart = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingSmart->setName('living_smart');
        $livingSmart->setReportName('Living Smart Course');
        $livingSmart->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($livingSmart);

        // Living Well With Diabetus Compliancy
        $livingWell = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingWell->setName('living_well');
        $livingWell->setReportName('Living Well Course');
        $livingWell->setAttribute('hide', true);
        $wellnessActivitiesGroup->addComplianceView($livingWell);

        // Community Activity Compliancy
        $communityActivity = new WMS3UserEntryComplianceView($programName, 'community_activity', $programStart, $programEnd, 10, 20);
        $communityActivity->setReportName('Community Activity <strong>($10/activity)</strong>');
        $communityActivity->setAttribute('modal', true);
        $communityActivity->setAttribute('modal_title', "Participate in a Community Volunteering Activity");
        $communityActivity->setAttribute('modal_activities', ['Run', 'Walk', 'Swim', 'Play']);
        $wellnessActivitiesGroup->addComplianceView($communityActivity);

        $this->addComplianceViewGroup($wellnessActivitiesGroup);

        // Fitness Nutrition Group
        $nutritionFitnessGroup = new ComplianceViewGroup('Nutrition and Fitness');

        // Nutrition Program Compliancy
        $nutritionProgram = new WMS3UserEntryComplianceView($programName, 'nutrition_program', $programStart, $programEnd, 10, 40);
        $nutritionProgram->setReportName('Nutrition Program (Jenny Craig, Weight Watchers, etc) <strong>($10/entry)</strong>');
        $nutritionProgram->setAttribute('modal', true);
        $nutritionProgram->setAttribute('modal_title', "Complete a Nutrition Program");
        $nutritionProgram->setAttribute('modal_placeholder', "Program Name");
        $nutritionFitnessGroup->addComplianceView($nutritionProgram);

        // Log Water Compliancy
        $logWater = new WMS3UserEntryComplianceView($programName, 'log_water', $programStart, $programEnd, 1, 10);
        $logWater->setReportName('Log Water <strong>($1/entry)</strong>');
        $logWater->setAttribute('modal', true);
        $logWater->setAttribute('modal_title', "Log Water");
        $logWater->setAttribute('modal_placeholder', "Glasses of Water (8oz)");
        $nutritionFitnessGroup->addComplianceView($logWater);

        // Log Food Compliancy
        $logFood = new WMS3UserEntryComplianceView($programName, 'log_food', $programStart, $programEnd, 1, 10);
        $logFood->setReportName('Log Food <strong>($1/entry)</strong>');
        $logFood->setAttribute('modal', true);
        $logFood->setAttribute('modal_title', "Log Food");
        $logFood->setAttribute('modal_placeholder', "What you ate and drank today");
        $nutritionFitnessGroup->addComplianceView($logFood);

        // Log Steps Compliancy
        $logSteps = new WMS3UserEntryComplianceView($programName, 'log_steps', $programStart, $programEnd, 1, 10);
        $logSteps->setReportName('Log Steps <strong>($1/entry)</strong>');
        $logSteps->setAttribute('modal', true);
        $logSteps->setAttribute('modal_title', "Log Steps");
        $logSteps->setAttribute('modal_placeholder', "# of steps you walked today");
        $nutritionFitnessGroup->addComplianceView($logSteps);

        // Cardio Exercise Compliancy
        $cardioExercise = new WMS3UserEntryComplianceView($programName, 'cardio_exercise', $programStart, $programEnd, 1, 10);
        $cardioExercise->setReportName('Cardio Exercise <strong>($1/entry)</strong>');
        $cardioExercise->setAttribute('modal', true);
        $cardioExercise->setAttribute('modal_title', "Cardio Exercise");
        $cardioExercise->setAttribute('modal_placeholder', "# of minutes you performed a cardio workout");
        $nutritionFitnessGroup->addComplianceView($cardioExercise);

        // Strength Exercise Compliancy
        $strengthExercise = new WMS3UserEntryComplianceView($programName, 'strength_exercise', $programStart, $programEnd, 1, 10);
        $strengthExercise->setReportName('Strength Exercise <strong>($1/entry)</strong>');
        $strengthExercise->setAttribute('modal', true);
        $strengthExercise->setAttribute('modal_title', "Strength Exercise");
        $strengthExercise->setAttribute('modal_placeholder', '# of minutes you performed a strength workout');
        $nutritionFitnessGroup->addComplianceView($strengthExercise);

        $this->addComplianceViewGroup($nutritionFitnessGroup);

        // Prevention Activities Group
        $preventionActivitiesGroup = new ComplianceViewGroup('Prevention Activities');

        // Annual Physical Compliancy
        $annualPhysical = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $annualPhysical->setReportName('Annual Physical By PCP (to be verified by UGS)');
        $annualPhysical->setName('annual_physical');
        $annualPhysical->setMaximumNumberOfPoints(40);
        $annualPhysical->setAllowPointsOverride(true);
        $annualPhysical->addLink(new Link('Download Form', 'https://static.hpn.com/pdf/clients/meridian_health/2022_Meridian_Preventative_Exam_Validation_Form.pdf'));
        $preventionActivitiesGroup->addComplianceView($annualPhysical);

        // Dental Exam Compliancy
        $dentalExam = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $dentalExam->setReportName('Dental Exam');
        $dentalExam->setName('dental_exam');
        $dentalExam->setMaximumNumberOfPoints(20);
        $dentalExam->setAllowPointsOverride(true);
        $dentalExam->addLink(new Link('Download Form', 'https://static.hpn.com/pdf/clients/meridian_health/2022_Meridian_Preventative_Exam_Validation_Form.pdf'));
        $preventionActivitiesGroup->addComplianceView($dentalExam);

        // Eye Exam Compliancy
        $eyeExam = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $eyeExam->setReportName('Eye Exam');
        $eyeExam->setName('eye_exam');
        $eyeExam->setMaximumNumberOfPoints(20);
        $eyeExam->setAllowPointsOverride(true);
        $eyeExam->addLink(new Link('Download Form', 'https://static.hpn.com/pdf/clients/meridian_health/2022_Meridian_Preventative_Exam_Validation_Form.pdf'));
        $preventionActivitiesGroup->addComplianceView($eyeExam);

        // Flu Shot Compliancy
        $fluShot = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $fluShot->setReportName('Flu Shot');
        $fluShot->setName('flu_shot');
        $fluShot->setMaximumNumberOfPoints(20);
        $fluShot->setAllowPointsOverride(true);
        $fluShot->addLink(new Link('Download Form', 'https://static.hpn.com/pdf/clients/meridian_health/2022_Meridian_Preventative_Exam_Validation_Form.pdf'));
        $preventionActivitiesGroup->addComplianceView($fluShot);

        // Skin Screening Compliancy
        $skinScreening = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $skinScreening->setReportName('Skin Screening');
        $skinScreening->setName('skin_screening');
        $skinScreening->setMaximumNumberOfPoints(20);
        $skinScreening->setAllowPointsOverride(true);
        $skinScreening->addLink(new Link('Download Form', 'https://static.hpn.com/pdf/clients/meridian_health/2022_Meridian_Preventative_Exam_Validation_Form.pdf'));
        $preventionActivitiesGroup->addComplianceView($skinScreening);

        // COVID Vaccine Compliancy
        $covidVaccine = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $covidVaccine->setReportName('COVID vaccine (points only for 1 shot)');
        $covidVaccine->setName('covid_vaccine');
        $covidVaccine->setMaximumNumberOfPoints(20);
        $covidVaccine->setAllowPointsOverride(true);
        $covidVaccine->addLink(new Link('Download Form', 'https://static.hpn.com/pdf/clients/meridian_health/2022_Meridian_Preventative_Exam_Validation_Form.pdf'));
        $preventionActivitiesGroup->addComplianceView($covidVaccine);

        // COVID Vaccine Compliancy
        $otherExams = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $otherExams->setReportName('Other Preventive Exams<br>
                                    -well woman (Pap + Breast Exam)<br>
                                    -well man (prostate, colonoscopy, etc)');
        $otherExams->setName('other_exams');
        $otherExams->setMaximumNumberOfPoints(40);
        $otherExams->setAllowPointsOverride(true);
        $otherExams->addLink(new Link('Download Form', 'https://static.hpn.com/pdf/clients/meridian_health/2022_Meridian_Preventative_Exam_Validation_Form.pdf'));
        $preventionActivitiesGroup->addComplianceView($otherExams);

        $this->addComplianceViewGroup($preventionActivitiesGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        // Wellness Measures Status (HRA & Sceening)
        $wellnessMeasuresStatus = $status->getComplianceViewStatus('wellness_measures');
        $hraStatus = $status->getComplianceViewStatus('hra');
        $screeningStatus = $status->getComplianceViewStatus('screening');

        if ($hraStatus->isCompliant() && $screeningStatus->isCompliant()) {
            $wellnessMeasuresStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

        // Tobacco Status
        $tobaccoStatus = $status->getComplianceViewStatus('tobacco');
        $livingFreeStatus = $status->getComplianceViewStatus('living_free');

        if ($livingFreeStatus->isCompliant()) {
            $tobaccoStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

        // Wellness Activities
        $biometricGoalsStatus = $status->getComplianceViewStatus('biometric_goals');
        $waistStatus = $status->getComplianceViewStatus('waist');
        $bloodPressureStatus = $status->getComplianceViewStatus('blood_pressure');
        $hdlStatus = $status->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $status->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $status->getComplianceViewStatus('glucose');
        $hemoglobinStatus = $status->getComplianceViewStatus('hemoglobin');

        $elearningStatus = $status->getComplianceViewStatus('elearning');

        $coachingStatus = $status->getComplianceViewStatus('coaching_program');

        // Biometric Status Calculation
        $biometricsCompliant = 0;
        if ($waistStatus->isCompliant()) $biometricsCompliant++;
        if ($bloodPressureStatus->isCompliant()) $biometricsCompliant++;
        if ($hdlStatus->isCompliant()) $biometricsCompliant++;
        if ($triglyceridesStatus->isCompliant()) $biometricsCompliant++;
        if ($glucoseStatus->isCompliant() || $hemoglobinStatus->isCompliant()) $biometricsCompliant++;

        if ($biometricsCompliant >= 3) {
            $biometricGoalsStatus->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            if ($screeningStatus->isCompliant()) {
                $elearningView = $elearningStatus->getComplianceView();
                $elearningView->setAttribute('hide', false);
                $remainingItems = 5 - $biometricsCompliant;
                if ($elearningStatus->getPoints() >= $remainingItems) $biometricGoalsStatus->setStatus(ComplianceStatus::COMPLIANT);
            }
        }

        // High Risk Coaching Calculation
        $risks = getExtendedRiskForUser($user, false, false, false, $startdate = "2022-01-01", $enddate = "2022-12-31");
        $risks = $risks['number_of_risks'];

        if ($risks >= 4 && $screeningStatus->isCompliant()) {
            $coachingView = $coachingStatus->getComplianceView();
            $coachingView->setAttribute('disabled', false);
            $coachingView->setReportName('Health Coaching - Qualified<br>
        <ul>
            <li>Complete 2 health coaching calls if qualified based on high risks from screening + HRA </li>
            <li>Call 1-866-682-3020 ext. 125 to schedule your first coaching call. Must enroll by 7/30</li>
        </ul>');
            $coachingView->setMaximumNumberOfPoints(40);

            if ($coachingStatus->isCompliant()) {
                $coachingStatus->setPoints(40);
            }
        } else {
            $coachingStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
            $coachingStatus->setPoints(0);
        }

        // Lifestyle Management Status
        $lifestyleManagementStatus = $status->getComplianceViewStatus('lifestyle_management');

        $livingFitStatus = $status->getComplianceViewStatus('living_fit');
        $livingLeanStatus = $status->getComplianceViewStatus('living_lean');
        $livingEasyStatus = $status->getComplianceViewStatus('living_easy');
        $livingWellRestedStatus = $status->getComplianceViewStatus('living_well_rested');
        $livingSmartStatus = $status->getComplianceViewStatus('living_smart');
        $livingWellStatus = $status->getComplianceViewStatus('living_well');

        $wellnessMaximum = $lifestyleManagementStatus->getComplianceView()->getMaximumNumberOfPoints();

        $wellnessPoints = 0;
        if ($livingFitStatus->isCompliant()) $wellnessPoints++;
        if ($livingLeanStatus->isCompliant()) $wellnessPoints++;
        if ($livingEasyStatus->isCompliant()) $wellnessPoints++;
        if ($livingWellRestedStatus->isCompliant()) $wellnessPoints++;
        if ($livingSmartStatus->isCompliant()) $wellnessPoints++;
        if ($livingWellStatus->isCompliant()) $wellnessPoints++;
        $wellnessPoints *= 40;

        if ($wellnessPoints > $wellnessMaximum) $wellnessPoints = $wellnessMaximum;
        $lifestyleManagementStatus->setPoints($wellnessPoints);
    }
}


class MeridianHealth2022WMS2Printer implements ComplianceProgramReportPrinter
{
    public function getStatusMappings(ComplianceView $view)
    {
        if($view->getName() == 'bf_bmi') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                3 => ComplianceStatus::PARTIALLY_COMPLIANT,
                2 => ComplianceStatus::NOT_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT,
                0 => ComplianceStatus::NOT_COMPLIANT
            );
        } else {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                2 => ComplianceStatus::PARTIALLY_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT
            );
        }
    }

    public function getClass(ComplianceView $view)
    {
        if($view->getName() == 'bf_bmi') {
            return array(
                4 => 'success',
                3 => 'warning',
                2 => 'danger',
                1 => 'danger',
                0 => 'danger'
            );
        } else {
            return array(
                ComplianceStatus::COMPLIANT => 'success',
                ComplianceStatus::PARTIALLY_COMPLIANT => 'warning',
                ComplianceStatus::NOT_COMPLIANT => 'danger'
            );
        }
    }

    public function displayStatus($status, $incorrect = false) {
        if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
            return '<i class="fa fa-check success"></i>';
        } else if (($status->getStatus() == ComplianceStatus::NOT_COMPLIANT ||
            $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) && $incorrect) {
            return '<i class="fa fa-times danger"></i>';
        } else {
            return '<label class="label label-danger">Incomplete</label>';
        }
    }

    public function getDisplayClass($points, $max)
    {
        if ($points < $max/2 || empty($max)) {
            return "danger";
        } else if ($points >= $max) {
            return "success";
        } else {
            return "warning";
        }
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $wellnessMeasuresGroup = $status->getComplianceViewGroupStatus('Wellness Measures');
        $tobaccoGroup = $status->getComplianceViewGroupStatus('Tobacco Status');
        $wellnessActivitiesGroup = $status->getComplianceViewGroupStatus('Wellness Activities');
        $nutritionFitnessGroup = $status->getComplianceViewGroupStatus('Nutrition and Fitness');
        $preventionActivitiesGroup = $status->getComplianceViewGroupStatus('Prevention Activities');

        $wellnessMeasuresPoints = 0;        $wellnessMeasuresMaxPoints = 0;
        $tobaccoPoints = 0;                 $tobaccoMaxPoints = 0;
        $wellnessActivitiesPoints = 0;      $wellnessActivitiesMaxPoints = 0;
        $nutritionFitnessPoints = 0;        $nutritionFitnessMaxPoints = 0;
        $preventionActivitiesPoints = 0;    $preventionActivitiesMaxPoints = 0;

        foreach($wellnessMeasuresGroup->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $wellnessMeasuresPoints += $viewStatus->getPoints();
            $wellnessMeasuresMaxPoints += $view->getMaximumNumberOfPoints();
        }

        foreach($tobaccoGroup->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $tobaccoPoints += $viewStatus->getPoints();
            $tobaccoMaxPoints += $view->getMaximumNumberOfPoints();
        }

        foreach($wellnessActivitiesGroup->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $wellnessActivitiesPoints += $viewStatus->getPoints();
            $wellnessActivitiesMaxPoints += $view->getMaximumNumberOfPoints();
        }

        foreach($nutritionFitnessGroup->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $nutritionFitnessPoints += $viewStatus->getPoints();
            $nutritionFitnessMaxPoints += $view->getMaximumNumberOfPoints();
        }

        foreach($preventionActivitiesGroup->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $preventionActivitiesPoints += $viewStatus->getPoints();
            $preventionActivitiesMaxPoints += $view->getMaximumNumberOfPoints();
        }

        $wellnessMeasuresStatus = $status->getComplianceViewStatus('wellness_measures');
        $tobaccoStatus = $status->getComplianceViewStatus('tobacco');

        $step1Compliance = $wellnessMeasuresStatus->isCompliant();
        $step2Compliance = $tobaccoStatus->isCompliant();
        $step3Total = min(360, $wellnessActivitiesPoints + $nutritionFitnessPoints + $preventionActivitiesPoints);
        $step3Compliance = $step3Total >= 360;

        $totalPoints = $wellnessMeasuresPoints + $tobaccoPoints + $step3Total;

        ?>

         <style type="text/css">

            ::placeholder {
              color: #B0BEC5;
              opacity: 1;
            }

            .tab {
                display: inline-block;
                width: 20px;
            }

            .disabled {
                background-color: #aaa !important;
            }

            .grow {
                flex-grow: 1;
            }

            .btn.btn-primary {
                outline: none !important;
                box-shadow: none !important;
                letter-spacing: 1px;
            }

            #activities {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
                min-width: 500px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
                line-height: 18px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
                overflow: hidden;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }

            .details-table .name {
                width: 436px;
            }

            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            #point-discounts {
                width: 100%;
            }

            #point-discounts td {
                vertical-align: middle;
                padding: 5px;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1em;
            }


            .point-totals th {
                text-align: right;
                padding-right: 10px;
            }

            .point-totals td {
                text-align: center;
            }

            .total-status td, .spouse-status td {
                text-align: center;
            }

            #wms1 h3 {
                line-height: 24px;
            }

            #wms1 h3[toggle] {
                font-size: 20px;
                color: #333D46;
                background: #ECEFF1;
                cursor: pointer;
                padding: 10px 20px;
                border-radius: 2px;
            }

            #wms1 h3[toggle]:hover {
                color: #48C7E8;
            }

            #wms1 h3[toggle] i {
                margin-right: 10px;
            }

            .date-input {
                width: 100%;
                height: 39px;
                font-size: 1.7rem;
                text-align: center;
                cursor: pointer;
            }

            .date-input:hover {
                background: #ECEFF1;
                outline: none !important;
                border: 1px solid;
            }

            .shadow {
                box-shadow: 0 3px 6px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0);
                height: 100px;
            }

            .grand-total-container {
                box-shadow: 0 3px 6px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0);
            }

            .quarter-indicator {
                width: 50px;
                height: 50px;
            }

            .quarter-even {
                background-color: #48c7e8;
                display: inline-block;

            }

            .quarter-odd {
                background-color: #0cc2ab;
                display: inline-block;
            }

            .quarter {
                text-align: center;
                color: #fff;
                vertical-align: middle;
                line-height: 50px;
                font-size: 20px;
                margin-bottom: 15px;
            }

            .quarter-rectangles {
                margin-top: 20px;
            }

            .quarter-rectangles .col-md-5 {
                padding: 0 !important;
            }

            .grand-total {
                height: 220px;
                background: #333d46;
            }

            .scores-container {
                display: inline-block;
                width: 70%;
            }

            .grand-total-container {
                width: 29%;
                display: inline-block;
            }

            .quarter-points {
                text-align: right;
                padding-right: 20px;
                width: auto;
                float: right;
                font-size: 40px;
                font-weight: bold;
                position: relative;
                top: -14px;
            }

            .quarter-points-odd {
                color: #0cc2ab;
            }

            .quarter-points-even {
                color: #48c7e8;;
            }

            .quarter-range {
                float: left;
                width: 100%;
            }

            .quarter-year {
                color: #333d47;
                float: left;
                margin-left: 15px;
            }

            .quarter-months {
                color: #9c9c9c;
                float: right;
                text-align: right;
                margin-right: 15px;
                text-transform: uppercase;
                font-size: 10px;
                padding-top: 4px;
            }

            .grand-total-header {
                text-align: center;
                color: #fff;
                padding-top: 20px;
                letter-spacing: .3em;
                font-weight: bold;
            }

            .blue-circle {
                height: 150px;
                width: 150px;
                border-radius: 50%;
                background: -webkit-linear-gradient(#81e3fe, #00afda);
                background: -o-linear-gradient(#81e3fe, #00afda);
                background: linear-gradient(#81e3fe, #00afda);
                padding: 3px;
                margin: 0 auto;
                position: relative;
                top: 10px;
            }

            .grand-total-points {
                padding: 2rem;
                background: #333d46;
                border-radius: 50%;
                width: 100%;
                height: 100%;
                text-align: center;
                color: #ced5dd;
            }

            .total-points {
                font-size: 35px;
                font-weight: bold;
                position: relative;
                top: 7px;
            }

            .total-points-text {
                position: relative;
                bottom: 12px;
            }

            .red {
                color: #F44336;
            }

            .green {
                color: #66BB6A;
            }

            .fa-times, .fa-check {
                margin-right: 10px;
                width: 15px;
                display: inline-block;
                text-align: center;
            }

            .modal_data {
                display: none;
            }

            #full_screen_modal {
                position: fixed;
                width: 100vw;
                height: 100vh;
                background: #000000cc;
                top: 0;
                left: 0;
                z-index: 999;
                display: none;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

             #full_screen_modal.active {
                display: flex;
             }

            #full_screen_modal .modal_content {
                min-height: 200px;
                max-width: 800px;
                width: 100%;
                background: #fff;
                border-radius: 4px;
                flex-direction: column;
                padding: 40px;
                position: relative;
            }

            #full_screen_modal .modal_content .close {
                position: absolute;
                top: 40px;
                right: 40px;
            }

            #full_screen_modal .modal_content .user_entry .close i:hover {
                color: #D32F2F;
            }

            #full_screen_modal .modal_content .data_entry {
                display: flex;
                flex-wrap: wrap;
            }

            #full_screen_modal .modal_content h3 {
                font-size: 24px;
                margin-top: 0;
                padding-top: 0;
                padding-right: 60px;
                letter-spacing: 1px;
            }

            #full_screen_modal .modal_content p {
                font-size: 16px;
                margin-top: 20px;
                margin-bottom: 0;
            }

            #full_screen_modal .modal_content input[type='date'] {
                padding: 5px 10px;
                border: 1px solid #CFD8DC;
                margin-right: 20px;
                border-radius: 4px;
                min-height: 40px;
                outline: none !important;
                box-shadow: none !important;
                font-size: 16px;
            }

            #full_screen_modal .modal_content input[type='date']:hover {
                border: 1px solid #2196F3;
            }

            #full_screen_modal .modal_content input[type='date']:focus {
                border: 1px solid #1976D2;
            }

            #full_screen_modal .modal_content input[type='text'], #full_screen_modal .modal_content select {
                padding: 4px 10px;
                border: 1px solid #CFD8DC;
                margin-right: 20px;
                border-radius: 4px;
                min-height: 40px;
                outline: none !important;
                box-shadow: none !important;
            }

            #full_screen_modal .modal_content input[type='text']:hover {
                border: 1px solid #2196F3;
            }

            #full_screen_modal .modal_content input[type='text']:focus {
                border: 1px solid #1976D2;
            }

            #full_screen_modal .modal_content input[type='text'].disabled {
                pointer-events: none;
                background: #F5F5F5;
            }

            #full_screen_modal .modal_content select {
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                border-radius: .4rem;
                outline: none !important;
                font-size: 1.6rem;
                padding: .8rem 2rem;
                padding-right: 5rem;
                height: auto;
                min-width: 22rem;
                line-height: initial;
                font-family: "Roboto";
                letter-spacing: .1rem;
                border-color: #CFD8DC;
                height: 3.9rem;
                background-image: linear-gradient(45deg, transparent 50%, #CFD8DC 50%),
                linear-gradient(135deg, #CFD8DC 50%, transparent 50%),
                linear-gradient(to right, #CFD8DC, #CFD8DC);
                background-position: calc(100% - 2rem) 1.6rem,
                calc(100% - 1.5rem) 1.6rem,
                calc(100% - 4rem) .8rem;
                background-size: .5rem .5rem,
                .5rem .5rem,
                .1rem 2.1rem;
                background-repeat: no-repeat;
                transition: background-image .3s ease-in-out, border .3s ease-in-out;
            }

            #full_screen_modal .modal_content select:hover {
                border-color: #2196F3;
                background-image: linear-gradient(
            45deg, transparent 50%, #2196F3 50%), linear-gradient(
            135deg, #2196F3 50%, transparent 50%), linear-gradient(to right, #CFD8DC, #CFD8DC);
                transition: background-image .1s ease-in-out, border .1s ease-in-out;
            }

            #full_screen_modal .modal_content select:focus {
                background-image: linear-gradient(
            45deg, transparent 50%, #1976D2 50%), linear-gradient(
            135deg, #1976D2 50%, transparent 50%), linear-gradient(to right, #90CAF9, #90CAF9);
                border-color: #1976D2;
                outline: 0;
            }

            #full_screen_modal .modal_content .user_entry {
                font-size: 16px;
                display: flex;
                align-items: center;
            }

            #full_screen_modal .modal_content .user_entry .delete {
                margin-right: 10px;
                font-size: 20px;
                cursor: pointer;
            }

            #full_screen_modal .modal_content .user_entry .delete i {
                cursor: pointer;
            }

            #full_screen_modal .modal_content .user_entry .delete i:hover {
                color: #D32F2F;
            }

            #full_screen_modal .modal_content .user_entry .name {
                margin-right: 5px;
                font-weight: 500;
            }

            @media (max-width: 500px) {
                .collapsible-points-report-card {
                    max-width: 500px;
                    min-width: 320px;
                }

                .triangle {
                    display: none;
                }

                .scores-container {
                    width: 100%;
                }

                .quarter-rectangles .col-md-5 {
                    display: inline-block;
                    width: 45%;
                    margin-left: 12px;
                }

                .quarter-points {
                    font-size: 30px;
                    padding-top: 10px;
                }

                .grand-total-container {
                    width: 100%;
                    margin-top: 10px;

                }

                .grand-total-container .row .col-md-12 {
                    padding: 0 12px;
                }

                .grand-total-header {
                    width: 61%;
                    padding: 0 10px;
                    height: 100%;
                    float: left;
                    position: relative;
                    top: 35px;
                    font-size: 17px;
                }

                .grand-total {
                    height: auto;
                    float: left;
                    width: 100%;
                    padding: 20px 0px;
                }

                .blue-circle {
                    float: right;
                    width: 100px;
                    height: 100px;
                    padding: 7px;
                    top: 0;
                    margin-right: 40px;

                }

                .grand-total-points {
                    padding: 0;
                }

                .total-points {
                    font-size: 30px;
                }

                .collapsible-points-report-card tr.details > td {
                    padding: 0;
                }
            }
        </style>

        <div class="row">
            <div class="col-md-12">
                <h1>2022 Incentive Report Card</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <h2>Earn up to $720 in premium incentives by completing the following wellness steps:</h2>

        <h3>
            <i class="<?= ($step1Compliance) ? "fa fa-check green": "fa fa-times red" ?>"></i>
            <strong>Step 1:</strong> Earn $280 by completing the HRA and Health Screening with Tobacco Test at a Meridian
            Health Services Provider location, or call the Circle Wellness customer service dept at 866-682-0060 x 204
            to order an on-demand packet to screen at a LabCorp location (complete between 1/1/2022- 5/31/2022,
            <span style="color: red;">NOW EXTENDED to June 15, 2022</span>)
        </h3>

        <h3>
            <i class="<?= ($step2Compliance) ? "fa fa-check green": "fa fa-times red" ?>"></i><strong>Step 2:</strong> Earn $80 by testing negative for tobacco. If you test positive for tobacco you can earn $80 by completing the "Living Free" tobacco cessation program by 9/1/2022.
        </h3>

        <h3><i class="<?= ($step3Compliance) ? "fa fa-check green": "fa fa-times red" ?>"></i><strong>Step 3 ($<?= $step3Total ?>/$360):</strong> Earn up to $360 for participating in wellness activities below (1/1/2022-11/1/2022).</h3>

        <h3><strong>Current Earnings: $<?= $totalPoints ?></strong></h3>

        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="points">Annual Dollar Amount</th>
                            <th class="points">Dollars Earned</th>
                            <th class="text-center">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="picker open">
                            <td class="name"><?= $wellnessMeasuresGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target">$<?= $wellnessMeasuresMaxPoints ?></td>
                            <td class="points actual <?= $this->getDisplayClass($wellnessMeasuresPoints, $wellnessMeasuresMaxPoints) ?>">$<?= $wellnessMeasuresPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getDisplayClass($wellnessMeasuresPoints, $wellnessMeasuresMaxPoints) ?>" style="width: <?= ($wellnessMeasuresPoints > 0) ? 100 : 0 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($wellnessMeasuresGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <?php if ($view->getAttribute('hide') == true) continue; ?>
                                            <tr>
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target">$<?= $view->getMaximumNumberOfPoints() ?></td>
                                                <td class="points actual <?= $viewStatus->getPoints() ? "success" : "danger" ?>">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $viewStatus->getPoints() ?? 0?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $viewStatus->getPoints() ? "success" : "danger" ?>" style="width: <?= $viewStatus->getPoints() ? 100 : 0 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php if (empty($viewStatus->getComplianceView()->getLinks())):?>
                                                    <a href="/compliance/ruoffmortgage-2022/report-card/pdf/clients/ruoff/2022_Ruoff_Wellness_Validation.pdf">Download Form</a>
                                                    <?php else: ?>
                                                        <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                        <div><?php echo $link->getHTML() ?></div>
                                                        <?php endforeach ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>

                        <tr class="picker open">
                            <td class="name"><?= $tobaccoGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target">$<?= $tobaccoMaxPoints ?></td>
                            <td class="points actual <?= $this->getDisplayClass($tobaccoPoints, $tobaccoMaxPoints) ?>">$<?= $tobaccoPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getDisplayClass($tobaccoPoints, $tobaccoMaxPoints) ?>" style="width: <?= ($tobaccoPoints > 0) ? 100 : 0 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($tobaccoGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <?php if ($view->getAttribute('hide') == true) continue; ?>
                                            <tr>
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $view->getMaximumNumberOfPoints() ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="points actual <?= $viewStatus->getPoints() ? "success" : "danger" ?>">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $viewStatus->getPoints() ?? 0?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $viewStatus->getPoints() ? "success" : "danger" ?>" style="width: <?= $viewStatus->getPoints() ? 100 : 0 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php if (empty($viewStatus->getComplianceView()->getLinks())):?>
                                                    <a href="/compliance/ruoffmortgage-2022/report-card/pdf/clients/ruoff/2022_Ruoff_Wellness_Validation.pdf">Download Form</a>
                                                    <?php else: ?>
                                                        <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                        <div><?php echo $link->getHTML() ?></div>
                                                        <?php endforeach ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="picker open">
                            <td class="name"><?= $wellnessActivitiesGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target">$<?= $wellnessActivitiesMaxPoints ?></td>
                            <td class="points actual <?= $this->getDisplayClass($wellnessActivitiesPoints, $wellnessActivitiesMaxPoints) ?>">$<?= $wellnessActivitiesPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getDisplayClass($wellnessActivitiesPoints, $wellnessActivitiesMaxPoints) ?>" style="width: <?= ($wellnessActivitiesPoints/$wellnessActivitiesMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($wellnessActivitiesGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <?php $disabled = $view->getAttribute('disabled') ?>
                                            <?php if ($view->getAttribute('hide') == true) continue; ?>
                                            <tr>
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="<?= $disabled ? "disabled" : "" ?> points target">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $view->getMaximumNumberOfPoints() ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="<?= $disabled ? "disabled" : "" ?> points actual <?= $this->getDisplayClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $viewStatus->getPoints() ?? 0?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="<?= $disabled ? "disabled" : "" ?> bar <?= $this->getDisplayClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= $viewStatus->getPoints()/$view->getMaximumNumberOfPoints()*100 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php if ($view->getAttribute('modal') == true): ?>
                                                        <?= $view->createModalView() ?>
                                                    <?php endif;?>
                                                    <?php if (empty($viewStatus->getComplianceView()->getLinks())):?>
                                                    <?php else: ?>
                                                        <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                        <div><?php echo $link->getHTML() ?></div>
                                                        <?php endforeach ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="picker open">
                            <td class="name"><?= $nutritionFitnessGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target">$<?= $nutritionFitnessMaxPoints ?></td>
                            <td class="points actual <?= $this->getDisplayClass($nutritionFitnessPoints, $nutritionFitnessMaxPoints) ?>">$<?= $nutritionFitnessPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getDisplayClass($nutritionFitnessPoints, $nutritionFitnessMaxPoints) ?>" style="width: <?= ($nutritionFitnessPoints/$nutritionFitnessMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($nutritionFitnessGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <?php if ($view->getAttribute('hide') == true) continue; ?>
                                            <tr>
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $view->getMaximumNumberOfPoints() ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="points actual <?= $this->getDisplayClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $viewStatus->getPoints() ?? 0?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $this->getDisplayClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= $viewStatus->getPoints()/$view->getMaximumNumberOfPoints()*100 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php if ($view->getAttribute('modal') == true): ?>
                                                        <?= $view->createModalView() ?>
                                                    <?php endif;?>

                                                    <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                    <div><?php echo $link->getHTML() ?></div>
                                                    <?php endforeach ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="picker open">
                            <td class="name"><?= $preventionActivitiesGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target">$<?= $preventionActivitiesMaxPoints ?></td>
                            <td class="points actual <?= $this->getDisplayClass($preventionActivitiesPoints, $preventionActivitiesMaxPoints) ?>">$<?= $preventionActivitiesPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getDisplayClass($preventionActivitiesPoints, $preventionActivitiesMaxPoints) ?>" style="width: <?= ($preventionActivitiesPoints/$preventionActivitiesMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($preventionActivitiesGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <?php if ($view->getAttribute('hide') == true) continue; ?>
                                            <tr>
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $view->getMaximumNumberOfPoints() ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="points actual <?= $this->getDisplayClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>">
                                                    <?php if (!empty($view->getMaximumNumberOfPoints())): ?>
                                                        $<?= $viewStatus->getPoints() ?? 0?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $this->getDisplayClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= $viewStatus->getPoints()/$view->getMaximumNumberOfPoints()*100 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php if ($view->getAttribute('modal') == true): ?>
                                                        <?= $view->createModalView() ?>
                                                    <?php endif;?>

                                                    <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                    <div><?php echo $link->getHTML() ?></div>
                                                    <?php endforeach ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="full_screen_modal">
                <div class="modal_content">

                </div>
            </div>

            <script type="text/javascript">
                var base = 'https://master.hpn.com/wms3/public/ehs_hmi_reportcards?bypass=true&method=';
                let data_updated = false;
                let user_id = <?= $user->id ?>;

                function saveEntry() {
                    let url = base + 'saveSimpleEntry';
                    let form = $('#full_screen_modal .data_entry');
                    let data_send = {};

                    data_send.activity_date = form.find('[name="log_date"]').val();
                    data_send.comments = form.find('[name="comments"]').val();
                    data_send.points_awarded = form.attr('points_awarded');
                    data_send.entry = form.attr('entry_key');
                    data_send.user_id = user_id;

                    console.log(data_send);
                    $.ajax({
                        type: 'post',
                        data: data_send,
                        url: url,
                        success: function(reply) {
                            reply = JSON.parse(reply);

                            if (reply.success) {
                                let html = '<div class="user_entry" entry_id="'+reply.entry_id+'"><span class="delete"><i class="fa fa-times red"></i></span><span class="name">'+data_send.activity_date+': </span><span class="comment">'+data_send.comments+'</span></div>';
                                $('#full_screen_modal .modal_content').append(html);
                                $('#full_screen_modal .modal_content .none').remove();
                                $('.user_entry .delete i').on('click', function(){
                                    let entry_id = $(this).parents('.user_entry').attr('entry_id');
                                    deleteEntry(entry_id);
                                });
                                data_updated = true;
                            }
                        }
                    })
                }

                function deleteEntry(entry_id) {
                    let url = base + 'deleteEntry';

                    let data_send = {};
                    data_send.entry_id = entry_id;

                    console.log(data_send);
                    $.ajax({
                        type: 'post',
                        data: data_send,
                        url: url,
                        success: function(reply) {
                            reply = JSON.parse(reply);

                            if (reply.success) {
                                $('.user_entry[entry_id="'+entry_id+'"]').remove();
                                data_updated = true;
                            }
                        }
                    })
                }

                $(function() {
                    $('[toggle]').on('click', function(){
                        let value = $(this).attr('toggle');
                        let icon = $(this).find('i');
                        $('#'+value).toggle();
                        if (icon.hasClass('fa-chevron-right')) {
                            icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                        } else {
                            icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                        }
                    });

                    $('a[modal_id]').on('click', function(){
                        let modal_id = $(this).attr('modal_id');
                        $('#full_screen_modal').addClass('active');
                        let content = $('.modal_data[modal_id="'+modal_id+'"]')[0].innerHTML;
                        $('#full_screen_modal .modal_content').html(content);

                        $('#full_screen_modal .close').on('click', function(){
                            $('#full_screen_modal').removeClass('active');
                            if (data_updated) location.reload();
                        });

                        $('.user_entry .delete i').on('click', function(){
                            let entry_id = $(this).parents('.user_entry').attr('entry_id');
                            deleteEntry(entry_id);
                        });

                        $('.data_entry .btn.btn-primary').on('click', function(){
                            saveEntry();
                        });
                    });


                    $.each($('#activities .picker'), function() {
                        $(this).click(function(e) {
                            if ($(this).hasClass('closed')) {
                                $(this).removeClass('closed');
                                $(this).addClass('open');
                                $(this).nextAll('tr.details').first().removeClass('closed');
                                $(this).nextAll('tr.details').first().addClass('open');
                            } else {
                                $(this).addClass('closed');
                                $(this).removeClass('open');
                                $(this).nextAll('tr.details').first().addClass('closed');
                                $(this).nextAll('tr.details').first().removeClass('open');
                            }
                        });
                    });
                });
            </script>
        <?php }
    }
