<?php

use hpn\steel\query\SelectQuery;

error_reporting(0);

class RuoffMortgage2022CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter(),
                'required_fields'  => array('systolic', 'diastolic', 'triglycerides', 'hdl', 'bodyfat', 'cholesterol', 'glucose')
            )
        );

        return $data;
    }
}

class RuoffMortgage2022ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new RuoffMortgage2022WMS2Printer();
    }

    public function getAdminProgramQarterlyReportPrinter($quarter)
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserFields(true, true, false, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);
        $printer->setShowUserContactFields(null, null, true);

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

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) use($quarter) {
            $user = $status->getUser();
            $data = array();

            $totalQuarterlyPoints = 0;
            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($quarter == "Q1") {
                        $data[sprintf('%s %s Points', $viewName, $quarter)] = $viewStatus->getAttribute('q1_points');
                        $totalQuarterlyPoints += $viewStatus->getAttribute('q1_points');
                    } else if($quarter == "Q2") {
                        $data[sprintf('%s %s Points', $viewName, $quarter)] = $viewStatus->getAttribute('q2_points');
                        $totalQuarterlyPoints += $viewStatus->getAttribute('q2_points');
                    } else if($quarter == "Q3") {
                        $data[sprintf('%s %s Points', $viewName, $quarter)] = $viewStatus->getAttribute('q3_points');
                        $totalQuarterlyPoints += $viewStatus->getAttribute('q3_points');
                    } else if($quarter == "Q4") {
                        $data[sprintf('%s %s Points', $viewName, $quarter)] = $viewStatus->getAttribute('q4_points');
                        $totalQuarterlyPoints += $viewStatus->getAttribute('q4_points');
                    }

                }
            }

            $data[sprintf('Total %s Points', $quarter)] = $totalQuarterlyPoints ;

            return $data;
        });

        return $printer;
    }


    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserFields(true, true, false, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);
        $printer->setShowUserContactFields(null, null, true);

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

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($groupStatus->getComplianceViewGroup()->getName() == 'Requirements') {
                        $data[sprintf('%s Result', $viewName)] = $viewStatus->getComment();
                    } elseif($groupStatus->getComplianceViewGroup()->getName() == 'Prevention Event') {
                        $data[sprintf('%s Comment', $viewName)] = $viewStatus->getComment();
                    }

                    $data[sprintf('%s Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';

                    if($viewName == 'Screening Program') {
                        $data['Enrollment Date'] = $user->planenrolldate;
                    }
                }
            }

            $ruoffWellnessGroup = $status->getComplianceViewGroupStatus('Ruoff Wellness');
            $preventativeGroup = $status->getComplianceViewGroupStatus('Preventive Health Measures');
            $selfCareGroup = $status->getComplianceViewGroupStatus('Self-Care');
            $q1_points = 0;
            $q2_points = 0;
            $q3_points = 0;
            $q4_points = 0;
            $ruoffPoints = 0;
            $preventionPoints = 0;
            $selfCarePoints = 0;

            foreach($ruoffWellnessGroup->getComplianceViewStatuses() as $viewStatus) {
                $q1_points += $viewStatus->getAttribute('q1_points');
                $q2_points += $viewStatus->getAttribute('q2_points');
                $q3_points += $viewStatus->getAttribute('q3_points');
                $q4_points += $viewStatus->getAttribute('q4_points');
                $ruoffPoints += $viewStatus->getPoints();
            }

            foreach($preventativeGroup->getComplianceViewStatuses() as $viewStatus) {
                $q1_points += $viewStatus->getAttribute('q1_points');
                $q2_points += $viewStatus->getAttribute('q2_points');
                $q3_points += $viewStatus->getAttribute('q3_points');
                $q4_points += $viewStatus->getAttribute('q4_points');
                $preventionPoints += $viewStatus->getPoints();
            }

            foreach($selfCareGroup->getComplianceViewStatuses() as $viewStatus) {
                $q1_points += $viewStatus->getAttribute('q1_points');
                $q2_points += $viewStatus->getAttribute('q2_points');
                $q3_points += $viewStatus->getAttribute('q3_points');
                $q4_points += $viewStatus->getAttribute('q4_points');
                $selfCarePoints += $viewStatus->getPoints();
            }

            $total_points = $q1_points + $q2_points + $q3_points + $q4_points;

            $data['Quarter 1 Points'] = $q1_points;
            $data['Quarter 2 Points'] = $q2_points;
            $data['Quarter 3 Points'] = $q3_points;
            $data['Quarter 4 Points'] = $q4_points;
            $data['Ruoff Wellness Points'] = $ruoffPoints;
            $data['Preventive Health Points'] = $preventionPoints;
            $data['Self-Care Points'] = $selfCarePoints;

            $data['Total Points'] = $total_points ;

            return $data;
        });


        return $printer;
    }

    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        date_default_timezone_set('America/New_York');

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $screeningStart = "2022-06-01";
        $screeningEnd = "2022-09-17";

        $ruoffWellnessGroup = new ComplianceViewGroup('Ruoff Wellness');

        $fitness_class = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $fitness_class->setName('fitness_class');
        $fitness_class->setReportName('Ruoff Fitness Class: Virtual or In Person <strong>(15 points)</strong>');
        // $fitness_class->setAllowPointsOverride(true);
        $fitness_class->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 15, 0);
        });
        $ruoffWellnessGroup->addComplianceView($fitness_class);

        $lunch_and_learn = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $lunch_and_learn->setName('lunch_and_learn');
        $lunch_and_learn->setReportName('Ruoff Lunch and Learn: Virtual or In Person <strong>(10 points)</strong>');
        $lunch_and_learn->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 0);
        });
        $ruoffWellnessGroup->addComplianceView($lunch_and_learn);

        $one_on_one = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $one_on_one->setName('one_on_one');
        $one_on_one->setReportName('One on One Health Coaching: Virtual or In Person <strong>(10 points per session)</strong>');
        $one_on_one->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 0);
        });
        $ruoffWellnessGroup->addComplianceView($one_on_one);

        $health_programming = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $health_programming->setName('health_programming');
        $health_programming->setReportName('Ruoff Health Programming: Virtual or In Person <strong>(Variant)</strong>');
        $health_programming->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 0);
        });
        $ruoffWellnessGroup->addComplianceView($health_programming);

        $virtual_only = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $virtual_only->setName('virtual_only');
        $virtual_only->setReportName('Virtual Only Health Challenges <strong>(10 points)</strong>');
        $virtual_only->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 0);
        });
        $ruoffWellnessGroup->addComplianceView($virtual_only);

        $this->addComplianceViewGroup($ruoffWellnessGroup);

        // Build the Prevention group
        $preventionEventGroup = new ComplianceViewGroup('Preventive Health Measures');

        $wellness_physical = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $wellness_physical->setName('wellness_physical');
        $wellness_physical->setReportName('Wellness Physical <strong>(30 points)</strong');
        $wellness_physical->setMaximumNumberOfPoints(30);
        $wellness_physical->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 30, 30);
        });
        $preventionEventGroup->addComplianceView($wellness_physical);

        $dental_exam = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $dental_exam->setName('dental_exam');
        $dental_exam->setReportName('Dental Exam <strong>(10 points)</strong>');
        $dental_exam->setMaximumNumberOfPoints(20);
        $dental_exam->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 20);
        });
        $preventionEventGroup->addComplianceView($dental_exam);

        $eye_exam = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $eye_exam->setName('eye_exam');
        $eye_exam->setReportName('Eye Exam <strong>(10 points)</strong>');
        $eye_exam->setMaximumNumberOfPoints(20);
        $eye_exam->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 20);
        });
        $preventionEventGroup->addComplianceView($eye_exam);

        $flu_shot = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $flu_shot->setName('flu_shot');
        $flu_shot->setReportName('Flu shot <strong>(10 points)<strong>');
        $flu_shot->setMaximumNumberOfPoints(10);
        $flu_shot->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 10);
        });
        $preventionEventGroup->addComplianceView($flu_shot);

        $colonoscopy = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $colonoscopy->setName('colonoscopy');
        $colonoscopy->setReportName('Colonoscopy <strong>(20 points)</strong>');
        $colonoscopy->setMaximumNumberOfPoints(20);
        $colonoscopy->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 20, 20);
        });
        $preventionEventGroup->addComplianceView($colonoscopy);

        $breast_screening = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $breast_screening->setName('breast_screening');
        $breast_screening->setReportName('Breast Screening <strong>(20 points)</strong>');
        $breast_screening->setMaximumNumberOfPoints(20);
        $breast_screening->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 20, 20);
        });
        $preventionEventGroup->addComplianceView($breast_screening);

        $heart_scan = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $heart_scan->setName('heart_scan');
        $heart_scan->setReportName('Heart Smart Scan <strong>(20 points)</strong>');
        $heart_scan->setMaximumNumberOfPoints(20);
        $heart_scan->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 20, 20);
        });
        $preventionEventGroup->addComplianceView($heart_scan);

        $skin_screen = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $skin_screen->setName('skin_screen');
        $skin_screen->setReportName('Skin Screen <strong>(20 points)</strong>');
        $skin_screen->setMaximumNumberOfPoints(20);
        $skin_screen->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 20, 20);
        });
        $preventionEventGroup->addComplianceView($skin_screen);

        $scr = new CompleteScreeningComplianceView($screeningStart, $screeningEnd);
        $scr->setReportName('Health Screening - Complete between Jun 1 - Sept 17 <strong>(10 points)</strong>');
        $scr->setName('screening');
        $scr->emptyLinks();
        $scr->addLink(new Link('', ''));
        $scr->setMaximumNumberOfPoints(10);
        $scr->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 10);
        });
        $preventionEventGroup->addComplianceView($scr);

        $biometrics = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $biometrics->setName('biometrics');
        $biometrics->setReportName('3 of 5 of the specified biometric markers in range (or complete two coaching sessions through Circle Wellness by Dec 19. <strong>(30 points)</strong> - Qualified <br /> <ul><li>If qualified, call 1-866-682-3020 ext.106 to schedule your first coaching call. Must enroll by Oct 14.</li></ul>');
        $biometrics->setMaximumNumberOfPoints(30);
        $biometrics->addLink(new Link('Take an HRA', '/compliance/ruoffmortgage-2022/hra/content/my-health'));
        $preventionEventGroup->addComplianceView($biometrics);

        $hpa = new CompleteHRAComplianceView('2022-06-01', '2022-09-17');
        $hpa->setReportName('Health Risk Assessment—Complete between Jun 1-Sept 17 <strong>(5 points)</strong>');
        $hpa->setName('hra');
        $hpa->emptyLinks();
        $hpa->setMaximumNumberOfPoints(5);
        $hpa->addLink(new Link('Take an HRA', '/compliance/ruoffmortgage-2022/hra/content/my-health'));
        $hpa->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 5, 5);

            if (empty($user->planenrolldate) || !$this->validateDate($user->planenrolldate) || strtotime($user->planenrolldate) <= strtotime("2022-09-01")) {
                if(date('Y-m-d') < "2022-06-01" || date('Y-m-d') > "2022-12-30") {
                    $status->getComplianceView()->emptyLinks();
                    $status->getComplianceView()->addLink(new Link(' ', '#'));
                }
            } else {
                $startDate = $user->planenrolldate;
                $endDate = date('Y-m-d', strtotime('+30 days', strtotime($startDate)));

                if(date('Y-m-d') < $startDate || date('Y-m-d') > $endDate) {
                    $status->getComplianceView()->emptyLinks();
                    $status->getComplianceView()->addLink(new Link(' ', '#'));
                }
            }
        });
        $hpa->setMaximumNumberOfPoints(5);
        $preventionEventGroup->addComplianceView($hpa);

        $freedom_from_smoking = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $freedom_from_smoking->setName('freedom_from_smoking');
        $freedom_from_smoking->setReportName('Attend Freedom From Smoking <strong>(Offered twice annually through Ruoff 30 points)</strong>');
        $freedom_from_smoking->setMaximumNumberOfPoints(30);
        $freedom_from_smoking->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 30, 30);
        });
        $preventionEventGroup->addComplianceView($freedom_from_smoking);

        $community_health = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $community_health->setName('community_health');
        $community_health->setReportName('Community health programming (Check with Wellness Coordinator to verify if any outside programming you attend can earn you points towards your incentive!) (Variant)');
        $community_health->setMaximumNumberOfPoints(50);
        $community_health->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 50);
        });
        $preventionEventGroup->addComplianceView($community_health);

        $this->addComplianceViewGroup($preventionEventGroup);

        $biometricsGroup = new ComplianceViewGroup('Biometric Markers');

        $bmiWaistView = new ComplyWithBMIWaistScreeningTestComplianceView($screeningStart, $screeningEnd);
        $bmiWaistView->setReportName('Waist Circumference/BMI');
        $bmiWaistView->overrideBMITestRowData(0, 0, 29.999, 29.999);
        $bmiWaistView->overrideWaistTestRowData(0, 0, 35, 35, 'F');
        $bmiWaistView->overrideWaistTestRowData(0, 0, 40, 40, 'M');
        $biometricsGroup->addComplianceView($bmiWaistView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($screeningStart, $screeningEnd);
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $glucoseView->overrideTestRowData(0, 0, 100, 100);
        $glucoseView->setPostEvaluateCallback($this->checkImprovement(array('glucose')));
        $biometricsGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStart, $screeningEnd);
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 132, 132);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 82, 82);
        $bloodPressureView->setPostEvaluateCallback($this->checkImprovement(array('systolic', 'diastolic')));
        $biometricsGroup->addComplianceView($bloodPressureView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($screeningStart, $screeningEnd);
        $ldlView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, null);
        $ldlView->overrideTestRowData(0, 0, 130, 130);
        $ldlView->setPostEvaluateCallback($this->checkImprovement(array('ldl')));
        $biometricsGroup->addComplianceView($ldlView);

        $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($screeningStart, $screeningEnd);
        $smokingView->setName('tobacco');
        $smokingView->setReportName('Tobacco');
        $biometricsGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($biometricsGroup);

        $selfCareGroup = new ComplianceViewGroup('Self-Care');

        $financial_planning = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $financial_planning->setName('financial_planning');
        $financial_planning->setReportName('Financial planning <strong>(10 points)</strong> Merrill Lynch meeting or Webinar');
        $financial_planning->setMaximumNumberOfPoints(10);
        $financial_planning->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 10);
        });
        $selfCareGroup->addComplianceView($financial_planning);

        $therapy = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $therapy->setName('therapy');
        $therapy->setReportName('Therapy <strong>(10 points per session)</strong>');
        $therapy->setMaximumNumberOfPoints(60);
        $therapy->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 60);
        });
        $selfCareGroup->addComplianceView($therapy);

        $nutritional_logging = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $nutritional_logging->setName('nutritional_logging');
        $nutritional_logging->setReportName('Proof of consistent nutritional logging with Weight Watchers, Noom, Fitbit, MFP, etc. <strong>(1 month majority logging, which is at least 5 of 7 days a week for a month, will earn participants 10 points)</strong>');
        $nutritional_logging->setMaximumNumberOfPoints(60);
        $nutritional_logging->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 10, 60);
        });
        $selfCareGroup->addComplianceView($nutritional_logging);

        $volunteer_work = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $volunteer_work->setName('volunteer_work');
        $volunteer_work->setReportName('Volunteer work <strong>(2 hours = 15 points)</strong>');
        $volunteer_work->setMaximumNumberOfPoints(60);
        $volunteer_work->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 15, 60);
        });
        $selfCareGroup->addComplianceView($volunteer_work);

        $bike_5k = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $bike_5k->setName('bike_5k');
        $bike_5k->setReportName('Complete a community 5K or bike ride <strong>(15 points)</strong>');
        $bike_5k->setMaximumNumberOfPoints(60);
        $bike_5k->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            if (!empty($status->getComment())) $this->calculatePoints($status, 15, 60);
        });
        $selfCareGroup->addComplianceView($bike_5k);

        $active_minutes = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $active_minutes->setName('active_minutes');
        $active_minutes->setReportName('Participate in 350 Active Minutes in a month using your Fitbit tracker. <strong>(15 points)</strong>. ');
        $active_minutes->setMaximumNumberOfPoints(75);
        $active_minutes->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
            $overrides = str_replace(' ', '', $status->getComment());
            $q1_override = null; $q2_override = null; $q3_override = null; $q4_override = null;

            if (strpos($overrides, ',') == false) {
                $arr = explode(":", $overrides);
                $quarter = $arr[0];
                $value = $arr[1];

                if (strtolower($quarter) == "q1") $q1_override = $value;
                else if (strtolower($quarter) == "q2") $q2_override = $value;
                else if (strtolower($quarter) == "q3") $q3_override = $value;
                else if (strtolower($quarter) == "q4") $q4_override = $value;
            } else {
                $quarter_overrides = explode(",", $overrides);

                foreach($quarter_overrides as $override) {
                    $arr = explode(":", $override);
                    $quarter = $arr[0];
                    $value= $arr[1];

                    if (strtolower($quarter) == "q1") $q1_override = $value;
                    else if (strtolower($quarter) == "q2") $q2_override = $value;
                    else if (strtolower($quarter) == "q3") $q3_override = $value;
                    else if (strtolower($quarter) == "q4") $q4_override = $value;
                }
            }

            $participant = SelectQuery::create()
            ->select('id')
            ->from('wms3.fitnessTracking_participants')
            ->where('wms1Id = ?', array($user->id))
            ->execute()->toArray();

            $participant = $participant[0]['id'] ?? null;

            $months = [1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0,12=>0];

            if (!is_null($participant)) {
               $active_minutes = SelectQuery::create()
               ->select('value, activity_date')
               ->from('wms3.fitnessTracking_data')
               ->where('activity_date BETWEEN ? AND ?', array('2022-01-01 00:00:00', '2022-12-16 23:59:59'))
               ->andWhere('type IN (4)')
               ->andWhere('participant = ?', array($participant))
               ->andWhere('status = 1')
               ->execute()->toArray();

               foreach($active_minutes as $record) {
                    $date = strtotime($record['activity_date']);
                    $month = date("n", $date);
                    $months[$month] += $record['value'];
                }
            }

            $q1_points = $q1_override ?? 0;
            $q2_points = $q2_override ?? 0;
            $q3_points = $q3_override ?? 0;
            $q4_points = $q4_override ?? 0;
            $total = 0; $total += $q1_override + $q2_override + $q3_override + $q4_override;

            foreach($months as $month => $minute_total) {
                if ($total < 75) {
                    if ($month >= 1 && $month <= 3 && (($minute_total/350) >= 1)) {
                        if (!isset($q1_override)) {
                            $q1_points += 15;
                            $total += 15;
                        }
                    } else if ($month >= 4 && $month <= 6 && (($minute_total/350) >= 1)) {
                        if (!isset($q2_override)) {
                            $q2_points += 15;
                            $total += 15;
                        }
                    } else if ($month >= 7 && $month <= 9 && (($minute_total/350) >= 1)) {
                        if (!isset($q3_override)) {
                            $q3_points += 15;
                            $total += 15;
                        }
                    } else if ($month >= 10 && $month <= 12 && (($minute_total/350) >= 1)) {
                        if (!isset($q4_override)) {
                            $q4_points += 15;
                            $total += 15;
                        }
                    }
                }
            }

            if ($total > 75) $total = 75;

            $status->setAttribute('q1_points', $q1_points);
            $status->setAttribute('q2_points', $q2_points);
            $status->setAttribute('q3_points', $q3_points);
            $status->setAttribute('q4_points', $q4_points);
            $status->setPoints($total);
            if ($total > 0) $status->setStatus(ComplianceStatus::COMPLIANT);
        });

        $selfCareGroup->addComplianceView($active_minutes);

        $this->addComplianceViewGroup($selfCareGroup);
    }

    protected function checkImprovement(array $tests, $calculationMethod = 'decrease') {
        $programStart = new \DateTime('@'.$this->getStartDate());
        $programEnd = new \DateTime('@'.$this->getEndDate());

        $lastStart = new \DateTime('2021-01-01');
        $lastEnd = new \DateTime('2021-12-31');

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
                $isImproved = false;

                foreach($tests as $test) {
                    if($test == 'bmi') {
                        if($cache['last'][0]['height'] !== null && $cache['last'][0]['weight'] !== null && is_numeric($cache['last'][0]['height']) && is_numeric($cache['last'][0]['weight']) && $cache['last'][0]['height'] > 0) {
                            $cache['last'][0][$test] = ($cache['last'][0]['weight'] * 703) / ($cache['last'][0]['height'] * $cache['last'][0]['height']);
                        }

                        if($cache['this'][0]['height'] !== null && $cache['this'][0]['weight'] !== null && is_numeric($cache['this'][0]['height']) && is_numeric($cache['this'][0]['weight']) && $cache['this'][0]['height'] > 0) {
                            $cache['this'][0][$test] = ($cache['this'][0]['weight'] * 703) / ($cache['this'][0]['height'] * $cache['this'][0]['height']);
                        }
                    }


                    $lastVal = isset($cache['last'][0][$test]) ? (float) $cache['last'][0][$test] : null;
                    $thisVal = isset($cache['this'][0][$test]) ? (float) $cache['this'][0][$test] : null;


                    if($lastVal && $thisVal) {
                        if($calculationMethod == 'decrease') {
                            if (!$thisVal || !$lastVal || $lastVal * 0.90 >= $thisVal) {
                                $isImproved = true;

                                break;
                            }
                        } else {
                            if (!$thisVal || !$lastVal || $lastVal * 1.10 <= $thisVal) {
                                $isImproved = true;

                                break;
                            }
                        }
                    }
                }

                if ($isImproved) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        };
    }

    protected function calculatePoints($status, $pointsPer, $maxPoints) {
        $date = $status->getComment();
        if ($status->getPoints() > 0) $pointsPer = $status->getPoints();

        if (strpos($date, ',') == false) {
            if (strpos($date, ':') !== false) {
                $arr = explode(":", $date);
                $date = $arr[0];
                $pointsPer = $arr[1];
            }
            if (strtotime($date) >= strtotime("2022-01-01") && strtotime($date) <= strtotime("2022-12-19")) {
                $this->calculateQuarterPoints($status, $pointsPer, $date);
                $status->setPoints($pointsPer);
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        } else {
            $dates = explode(",", $date);
            $points = 0;
            foreach($dates as $date) {
                if (strpos($date, ':') !== false) {
                    $arr = explode(":", $date);
                    $date = $arr[0];
                    $pointsPer = $arr[1];
                }
                if ($points < $maxPoints || $maxPoints == 0) {
                    if ($this->calculateQuarterPoints($status, $pointsPer, $date)) {
                        $points += $pointsPer;
                    }
                }
            }
            if ($points > 0) $status->setStatus(ComplianceStatus::COMPLIANT);
            $status->setPoints($points);
        }
    }

    protected function calculateQuarterPoints($status, $points, $date) {
        $date = strtotime($date);

        if ($date >= strtotime("2022-01-01") && $date <= strtotime("2022-03-31")) {
            $q1_points = $status->getAttribute('q1_points') ?? 0;
            $q1_points += $points;
            $status->setAttribute('q1_points', $q1_points);
            return true;
        } else if ($date >= strtotime("2022-04-01") && $date <= strtotime("2022-06-30")) {
            $q2_points = $status->getAttribute('q2_points') ?? 0;
            $q2_points += $points;
            $status->setAttribute('q2_points', $q2_points);
            return true;
        } else if ($date >= strtotime("2022-07-01") && $date <= strtotime("2022-09-30")) {
            $q3_points = $status->getAttribute('q3_points') ?? 0;
            $q3_points += $points;
            $status->setAttribute('q3_points', $q3_points);
            return true;
        } else if ($date >= strtotime("2022-10-01") && $date <= strtotime("2022-12-19")) {
            $q4_points = $status->getAttribute('q4_points') ?? 0;
            $q4_points += $points;
            $status->setAttribute('q4_points', $q4_points);
            return true;
        }
        return false;
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();


        $biometricGroup = $status->getComplianceViewGroupStatus('Biometric Markers');
        $biometricItem = $status->getComplianceViewStatus('biometrics');
        $screeningItem = $status->getComplianceViewStatus('screening');

        $biometric_passed = 0;

        foreach($biometricGroup->getComplianceViewStatuses() as $viewStatus) {
            if ($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $biometric_passed++;
            }
        }

        $appt = SelectQuery::create()
        ->select('at.id')
        ->from('appointment_times at')
        ->innerJoin('appointments a')
        ->on('a.id = at.appointmentid')
        ->where('a.date BETWEEN ? AND ?', array('2022-01-01', '2022-12-16'))
        ->andWhere('a.typeid IN (35)')
        ->andWhere('at.user_id = ?', array($user->id))
        ->andWhere('at.showed = 1')
        ->execute()->toArray();


        $biometrics_override = SelectQuery::create()
            ->hydrateSingleScalar()
            ->select('status')
            ->from('compliance_view_status_overrides')
            ->where('user_id = ?', array($user->id))
            ->andWhere('compliance_program_record_id = ?', array(RuoffMortgage2022ComplianceProgram::RuoffMortgage_2022_RECORD_ID))
            ->andWhere('compliance_view_name = ?', array('biometrics'))
            ->execute();

        if($biometrics_override) {
            if($biometrics_override == ComplianceStatus::COMPLIANT) {
                $biometricItem->setStatus(ComplianceStatus::COMPLIANT);
                $this->calculatePoints($biometricItem, 30, 30);
            } elseif($biometrics_override == ComplianceStatus::NOT_COMPLIANT) {
                $biometricItem->setStatus(ComplianceStatus::NOT_COMPLIANT);
            } elseif($biometrics_override == ComplianceStatus::NA_COMPLIANT) {
                $biometricItem->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        } else {
            if((count($appt) >= 2 || $biometric_passed >= 3) && !empty($screeningItem->getComment())) {
                $biometricItem->setStatus(ComplianceStatus::COMPLIANT);
                $biometricItem->setComment($screeningItem->getComment());

                $biometricItem->getComplianceView()->setReportName('3 of 5 of the specified biometric markers in range (or complete two coaching sessions through Circle Wellness by Dec 19. <strong>(30 points)</strong> - Complete <br /> <ul><li>If qualified, call 1-866-682-3020 ext.106 to schedule your first coaching call. Must enroll by Oct 14.</li></ul>');

                $this->calculatePoints($biometricItem, 30, 30);
            } elseif ($screeningItem->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
                $biometricItem->getComplianceView()->setReportName('3 of 5 of the specified biometric markers in range (or complete two coaching sessions through Circle Wellness by Dec 19. <strong>(30 points)</strong> - Unqualified <br /> <ul><li>If qualified, call 1-866-682-3020 ext.106 to schedule your first coaching call. Must enroll by Oct 14.</li></ul>');
                $biometricItem->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        }

    }

    public function useParallelReport()
    {
        return false;
    }

    const RuoffMortgage_2022_RECORD_ID = 1704;
}


class RuoffMortgage2022WMS2Printer implements ComplianceProgramReportPrinter
{
    public function getStatusMappings(ComplianceView $view)
    {
        if($view->getName() == 'waist_bmi') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                3 => ComplianceStatus::PARTIALLY_COMPLIANT,
                2 => ComplianceStatus::NOT_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT,
                0 => ComplianceStatus::NOT_COMPLIANT
            );
        } elseif($view->getName() == 'tobacco') {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT
            );
        } else {
            return array(
                4 => ComplianceStatus::COMPLIANT,
                2 => ComplianceStatus::PARTIALLY_COMPLIANT,
                1 => ComplianceStatus::NOT_COMPLIANT
            );
        }
    }

    public function getClass($status)
    {
        if($status == ComplianceStatus::COMPLIANT) {
            return "success";
        } else if($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
            return "warning";
        } else if($status == ComplianceStatus::NOT_COMPLIANT) {
            return "danger";
        } else if($status == ComplianceStatus::NA_COMPLIANT) {
            return "norisk";
        }
    }


    public function printReport(ComplianceProgramStatus $status)
    {
        $escaper = new hpn\common\text\Escaper;
        $q1_points = 0;
        $q2_points = 0;
        $q3_points = 0;
        $q4_points = 0;

        $ruoffWellnessGroup = $status->getComplianceViewGroupStatus('Ruoff Wellness');
        $preventativeGroup = $status->getComplianceViewGroupStatus('Preventive Health Measures');
        $selfCareGroup = $status->getComplianceViewGroupStatus('Self-Care');

        $hraCompletion = $status->getComplianceViewStatus('hra');
        $hraClass = ($hraCompletion->getStatus() == ComplianceStatus::COMPLIANT)? "fa fa-check green" : "fa fa-times red";
        $screeningCompletion = $status->getComplianceViewStatus('screening');
        $screeningClass = ($screeningCompletion->getStatus() == ComplianceStatus::COMPLIANT)? "fa fa-check green" : "fa fa-times red";

        $ruoffPoints = 0;
        $preventionPoints = 0;
        $preventionMax = 0;
        $selfCarePoints = 0;
        $selfCareMax = 0;

        foreach($ruoffWellnessGroup->getComplianceViewStatuses() as $viewStatus) {
            $q1_points += $viewStatus->getAttribute('q1_points');
            $q2_points += $viewStatus->getAttribute('q2_points');
            $q3_points += $viewStatus->getAttribute('q3_points');
            $q4_points += $viewStatus->getAttribute('q4_points');
            $ruoffPoints += $viewStatus->getPoints();
        }

        foreach($preventativeGroup->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $preventionMax += $view->getMaximumNumberOfPoints();

            $q1_points += $viewStatus->getAttribute('q1_points');
            $q2_points += $viewStatus->getAttribute('q2_points');
            $q3_points += $viewStatus->getAttribute('q3_points');
            $q4_points += $viewStatus->getAttribute('q4_points');
            $preventionPoints += $viewStatus->getPoints();
        }

        foreach($selfCareGroup->getComplianceViewStatuses() as $viewStatus) {
            $view = $viewStatus->getComplianceView();
            $selfCareMax += $view->getMaximumNumberOfPoints();

            $q1_points += $viewStatus->getAttribute('q1_points');
            $q2_points += $viewStatus->getAttribute('q2_points');
            $q3_points += $viewStatus->getAttribute('q3_points');
            $q4_points += $viewStatus->getAttribute('q4_points');
            $selfCarePoints += $viewStatus->getPoints();
        }

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $total_points = $q1_points + $q2_points + $q3_points + $q4_points;
        $pointsClass = ($total_points >= 100 && $ruoffPoints > 0 && $preventionPoints > 0 && $selfCarePoints > 0)? "fa fa-check green" : "fa fa-times red";

        $that = $this;

        ?>
        <style type="text/css">
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

            .norisk {
                background-color: #CCC;
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

        <div class="scores-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="row quarter-rectangles">
                        <div class="col-md-5 shadow">
                            <div class="quarter-indicator quarter-odd">
                                <div class="quarter">Q1</div>
                            </div>
                            <div class="quarter-points quarter-points-odd"><?= $q1_points ?></div>
                            <div class="quarter-range"><span class="quarter-year">2022</span><span class="quarter-months">Jan - Mar</span></div>
                        </div>
                        <div class="col-md-5 col-md-offset-1 shadow">
                            <div class="quarter-indicator quarter-even">
                                <div class="quarter">Q2</div>
                            </div>
                            <div class="quarter-points quarter-points-even"><?= $q2_points ?></div>
                            <div class="quarter-range"><span class="quarter-year">2022</span><span class="quarter-months">Apr - Jun</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="row quarter-rectangles">
                        <div class="col-md-5 shadow">
                            <div class="quarter-indicator quarter-even">
                                <div class="quarter">Q3</div>
                            </div>
                            <div class="quarter-points quarter-points-even"><?= $q3_points ?></div>
                            <div class="quarter-range"><span class="quarter-year">2022</span><span class="quarter-months">Jul - Sep</span></div>
                        </div>
                        <div class="col-md-5 col-md-offset-1 shadow">
                            <div class="quarter-indicator quarter-odd">
                                <div class="quarter">Q4</div>
                            </div>
                            <div class="quarter-points quarter-points-odd"><?= $q4_points ?></div>
                            <div class="quarter-range"><span class="quarter-year">2022</span><span class="quarter-months">Oct - Dec</span></div>
                            <div class="quarter-text"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grand-total-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="grand-total">
                        <div class="grand-total-header">GRAND TOTAL</div>
                        <div class="blue-circle">
                            <div class="grand-total-points">
                                <div class="total-points"><?= $total_points ?></div>
                                <div class="total-points-text">POINTS</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" style="padding: 20px; font-weight: bolder;">
            <p><i class="<?= $hraClass ?>"></i> HRA (completed between June 1-Sept 17, 2022)</p>
            <p><i class="<?= $screeningClass ?>"></i> Biometric Screening (completed between June 1-Sept 17, 2022)</p>
            <p><i class="<?= $pointsClass ?>"></i> Earn 100 Wellness Points by completing activities in each category</p>
        </div>

        <h3 toggle="program_overview"><i class="fa fa-chevron-right"></i> How to earn the $650 Premium Incentive</h3>

        <div id="program_overview" style="display:none;">
            <p>Employees on the Ruoff Mortgage medical plan will receive an annual $650 premium incentive in 2023 by completing the following steps in 2022:</p>

            <p><strong>Step 1: Complete a Health Risk Assessment between June 1- Sept 17, 2022 (earn 5 points).</strong></p>

            <p style="margin-bottom: 0;"><strong>Step 2: Complete a Health Screening between June 1-Sept 17, 2022 (earn 10 points).</strong></p>
            <ul>
                <li>Employees can either attend the onsite screenings offered at Ruoff HQ (will take place in August-look for further communication), request an on-demand requisition form from Circle Wellness to go to a LabCorp, or download a physician’s form in the left menu bar to take to their PCP.</li>
                <li>Screenings must include to be accepted:
                    <ul>
                        <li>Biometric Markers: Height, Weight, Blood Pressure, Waist Circumference or BMI</li>
                        <li>Glucose, A1C, HDL, LDL, Triglyceride</li>
                        <li>Completion date between Jun 1-Sept 17, 2022. This includes the HRA. Anything outside of those dates will not be accepted. If you are a new hire, please discuss your deadlines with your HR Dept.</li>
                    </ul>
                </li>
                <li>The biometrics to earn points for having 3 of 5 specified biometrics in range are:
                    <ul>
                        <li>BMI &#8804; 29.9 or Waist Circumference &#8804;35 (F) and &#8804;40 (M)</li>
                        <li>Glucose: &#8804; 100</li>
                        <li>Diastolic BP &#8804;82</li>
                        <li>Systolic BP &#8804;132</li>
                        <li>LDL &#8804;130</li>
                        <li>Tobacco free (as answered in HRA)</li>
                    </ul>
                </li>
            </ul>

            <p><strong>Step 3: Earn 100 overall Wellness Points by December 19, 2022. Must complete activities in each of the 3 categories to qualify. Late submissions will not be accepted.</strong></p>
        </div>

        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="points">Annual Maximum</th>
                            <th class="points">Points Earned</th>
                            <th class="text-center">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="picker open">
                            <td class="name"><?= $ruoffWellnessGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target">None</td>
                            <td class="points actual <?= $this->getClass($ruoffWellnessGroup->getStatus()) ?>"><?= $ruoffPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($ruoffWellnessGroup->getStatus()) ?>" style="width: <?= ($ruoffPoints / 45) * 100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr style="font-size: 20px; text-align: center;"><td><a href="/document-uploader/upload">Upload Form</a></td><td colspan="4"><a href="/compliance/ruoffmortgage-2022/report-card/pdf/clients/ruoff/Wellness_Validation.pdf">Download Form</a></td></tr>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($ruoffWellnessGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <tr class="view-hra">
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target">None</td>
                                                <td class="points actual <?= $viewStatus->getPoints() ? "success" : "danger" ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $viewStatus->getPoints() ? "success" : "danger" ?>" style="width: <?= $viewStatus->getPoints() ? 100 : 0 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php if (empty($viewStatus->getComplianceView()->getLinks())):?>
                                                    <a href="/compliance/ruoffmortgage-2022/report-card/pdf/clients/ruoff/Wellness_Validation.pdf">Download Form</a>
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
                            <td class="name"><?= $preventativeGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target"><?= $preventionMax ?></td>
                            <td class="points actual <?= $this->getClass($preventativeGroup->getStatus()) ?>"><?= $preventionPoints ?></td>

                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($preventativeGroup->getStatus()) ?>" style="width: <?= ($preventionPoints/$preventionMax)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr style="font-size: 20px; text-align: center;"><td><a href="/document-uploader/upload">Upload Form</a></td><td colspan="4"><a href="/compliance/ruoffmortgage-2022/report-card/pdf/clients/ruoff/Preventative_Exam_Validation.pdf">Download Form</a></td></tr>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($preventativeGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <tr class="view-hra">
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                                <td class="points actual <?= $this->getClass($viewStatus->getStatus()) ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $this->getClass($viewStatus->getStatus()) ?>" style="width: <?= ($viewStatus->getPoints() / $view->getMaximumNumberOfPoints())*100 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php if (empty($viewStatus->getComplianceView()->getLinks())):?>
                                                    <a href="/compliance/ruoffmortgage-2022/report-card/pdf/clients/ruoff/Preventative_Exam_Validation.pdf">Download Form</a>
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
                            <td class="name"><?= $selfCareGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target"><?= $selfCareMax ?></td>
                            <td class="points actual <?= $this->getClass($selfCareGroup->getStatus()) ?>"><?= $selfCarePoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($selfCareGroup->getStatus()) ?>" style="width: <?= ($selfCarePoints/$selfCareMax)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr style="font-size: 20px; text-align: center;"><td><a href="/document-uploader/upload">Upload Form</a></td><td colspan="4"><a href="/compliance/ruoffmortgage-2022/report-card/pdf/clients/ruoff/Self_Care_Validation.pdf">Download Form</a></td></tr>
                                        <tr>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($selfCareGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <tr class="view-hra">
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                                <td class="points actual <?= $this->getClass($viewStatus->getStatus()) ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $this->getClass($viewStatus->getStatus()) ?>" style="width: <?= ($viewStatus->getPoints() / $view->getMaximumNumberOfPoints())*100 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php if (empty($viewStatus->getComplianceView()->getLinks())):?>
                                                    <a href="/compliance/ruoffmortgage-2022/report-card/pdf/clients/ruoff/Self_Care_Validation.pdf">Download Form</a>
                                                    <?php else: ?>
                                                        <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                        <div><?php echo $link->getHTML() ?></div>
                                                        <?php endforeach ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <script type="text/javascript">
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
            <?php
        }
    }
