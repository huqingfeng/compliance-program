<?php

require_once 'lib/functions/getExtendedRiskForUser2010.php';

use hpn\steel\query\SelectQuery;

error_reporting(0);

class MeridianHealth2021CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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
                'required_fields'  => array('cotinine')
            )
        );

        return $data;
    }
}

class MeridianHealth2021ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MeridianHealth2021WMS2Printer();
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
            $data['Earnings'] = $status->getComment();

            return $data;
        });

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        date_default_timezone_set('America/New_York');

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        // Build the core group
        $reportCardGroup = new ComplianceViewGroup('Report Card');

        // HRA Compliancy
        $hra = new CompleteHRAComplianceView($programStart, "2021-06-16");
        $hra->setName('hra');
        $hra->setReportName('Health Risk Assessment (HRA)');
        $hra->emptyLinks();
        $reportCardGroup->addComplianceView($hra);

        // Screening Compliancy
        $screening = new MeridianHealth2021CompleteScreeningComplianceView($programStart, "2021-06-16");
        $screening->setName('screening');
        $screening->setReportName('Complete Wellness Screening');
        $reportCardGroup->addComplianceView($screening);

        // Tobacco Compliancy
        $tobacco = new ComplyWithCotinineScreeningTestDirectComplianceView($programStart, "2021-09-30");
        $tobacco->setName('tobacco');
        $tobacco->setReportName('Be Tobacco/Nicotine Free');
        $reportCardGroup->addComplianceView($tobacco);

        // Living Free Compliancy
        $livingFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingFree->setName('living_free');
        $livingFree->setReportName('Living Free Course');
        $reportCardGroup->addComplianceView($livingFree);

        // Wellness Activities Compliancy
        $wellnessActivities = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $wellnessActivities->setName('wellness_activities');
        $wellnessActivities->setReportName('Wellness Activities - Earn 50 Points');
        $reportCardGroup->addComplianceView($wellnessActivities);

        // Biometric Goals Compliancy
        $biometric = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $biometric->setName('biometric_goals');
        $biometric->setReportName('Meet at least 3 Biometric Goals');
        $reportCardGroup->addComplianceView($biometric);

        // Waist Compliancy
        $waist = new ComplyWithWaistScreeningTestComplianceView($programStart, $programEnd);
        $waist->setName('waist');
        $waist->setReportName('Waist Circumference');
        $waist->overrideTestRowData(null, null, 40, null, 'M');
        $waist->overrideTestRowData(null, null, 35, null, 'F');
        $reportCardGroup->addComplianceView($waist);

        // Blood Pressure Compliancy
        $blood_pressure = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $blood_pressure->setName('blood_pressure');
        $blood_pressure->setReportName('Blood Pressure');
        $blood_pressure->overrideSystolicTestRowData(null, null, 130, null);
        $blood_pressure->overrideDiastolicTestRowData(null, null, 85, null);
        $reportCardGroup->addComplianceView($blood_pressure);

        // HDL Compliancy
        $hdl = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdl->setName('hdl');
        $hdl->setReportName('HDL');
        $hdl->overrideTestRowData(null, 40, null, null, 'M');
        $hdl->overrideTestRowData(null, 50, null, null, 'F');
        $reportCardGroup->addComplianceView($hdl);

        // Triglycerides Compliancy
        $triglycerides = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglycerides->setName('triglycerides');
        $triglycerides->setReportName('Triglycerides');
        $triglycerides->overrideTestRowData(null, null, 150, null);
        $reportCardGroup->addComplianceView($triglycerides);

        // Glucose Compliancy
        $glucose = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucose->setName('glucose');
        $glucose->setReportName('Fasting Glucose');
        $glucose->overrideTestRowData(null, null, 100, null);
        $reportCardGroup->addComplianceView($glucose);     

        // Hemoglobin Compliancy
        $ha1c = new ComplyWithHa1cScreeningTestComplianceView($programStart, $programEnd);
        $ha1c->setName('hemoglobin');
        $ha1c->setReportName('Hemoglobin A1C');
        $ha1c->overrideTestRowData(0, .1, 5.9, null);
        $reportCardGroup->addComplianceView($ha1c);

        // Elearning Compliancy
        $elearning = new CompleteELearningLessonsComplianceView("2020-12-31", $programEnd);
        $elearning->setPointsPerLesson(5);
        $elearning->setMaximumNumberOfPoints(30);
        $elearning->setName('elearning');
        $elearning->setReportName('Complete Elearning Lessons');
        $reportCardGroup->addComplianceView($elearning);

        // Living Fit Compliancy
        $livingFit = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingFit->setName('living_fit');
        $livingFit->setReportName('Living Fit 90 Day Walking Challenge');
        $livingFit->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $reportCardGroup->addComplianceView($livingFit);

        // Living Lean Compliancy
        $livingLean = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingLean->setName('living_lean');
        $livingLean->setReportName('Living Lean Course');
        $livingLean->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $reportCardGroup->addComplianceView($livingLean);

        // Living Easy Compliancy
        $livingEasy = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingEasy->setName('living_easy');
        $livingEasy->setReportName('Living Easy Course');
        $livingEasy->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $reportCardGroup->addComplianceView($livingEasy);

        // Living Well Rested Compliancy
        $livingWellRested = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingWellRested->setName('living_well_rested');
        $livingWellRested->setReportName('Living Well Rested Course');
        $livingWellRested->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $reportCardGroup->addComplianceView($livingWellRested);

        // Living Smart Compliancy
        $livingSmart = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingSmart->setName('living_smart');
        $livingSmart->setReportName('Living Smart Course');
        $livingSmart->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $reportCardGroup->addComplianceView($livingSmart);

        // Living Well With Diabetus Compliancy
        $livingWell = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingWell->setName('living_well');
        $livingWell->setReportName('Living Well Course');
        $livingWell->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $reportCardGroup->addComplianceView($livingWell);

        // Being Videos
        $beingVideos = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $beingVideos->setName('being_videos');
        $beingVideos->setReportName('Being Videos');
        $beingVideos->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15, 0, 0, 0));
        $beingVideos->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $user_id = $user->id;
            $result = SelectQuery::create()
            ->select('count(distinct(lesson_id)) as lessons')
            ->from('tbk_lessons_complete tbk')
            ->where('tbk.user_id = ?', array($user_id))
            ->andWhere('tbk.completion_date BETWEEN ? AND ?', array('2021-01-01', '2021-12-31'))
            ->hydrateSingleRow()
            ->execute();

            $lessons = $result['lessons'] ?? 0;
            $points = intval($status->getComment()) ?? 0;
            $points += $lessons * 5;
            
            if ($points >= 15) {
                $points = 15;
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            $status->setPoints($points);
    
        });
        $reportCardGroup->addComplianceView($beingVideos);

        // High Risk Coaching Program
        $coachingProgram = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $coachingProgram->setName('coaching_program');
        $coachingProgram->setReportName('High Risk Coaching Program');
        $coachingProgram->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $reportCardGroup->addComplianceView($coachingProgram);

        // Private Consultation Compliancy
        $consultation = new CompletePrivateConsultationComplianceView($programStart, "2021-07-15");
        $consultation->setName('consultation');
        $consultation->setReportName('Private Consultation/Coaching Session');
        $consultation->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
        $reportCardGroup->addComplianceView($consultation); 

        $this->addComplianceViewGroup($reportCardGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        // Gather Status
        $hraStatus = $status->getComplianceViewStatus('hra');
        $screeningStatus = $status->getComplianceViewStatus('screening');
        $tobaccoStatus = $status->getComplianceViewStatus('tobacco');
        $livingFreeStatus = $status->getComplianceViewStatus('living_free');
        $wellnessActivitiesStatus = $status->getComplianceViewStatus('wellness_activities');
        $biometricGoalsStatus = $status->getComplianceViewStatus('biometric_goals');
        $waistStatus = $status->getComplianceViewStatus('waist');
        $bloodPressureStatus = $status->getComplianceViewStatus('blood_pressure');
        $hdlStatus = $status->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $status->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $status->getComplianceViewStatus('glucose');
        $hemoglobinStatus = $status->getComplianceViewStatus('hemoglobin');
        $elearningStatus = $status->getComplianceViewStatus('elearning');
        $livingFitStatus = $status->getComplianceViewStatus('living_fit');
        $livingLeanStatus = $status->getComplianceViewStatus('living_lean');
        $livingEasyStatus = $status->getComplianceViewStatus('living_easy');
        $livingWellRestedStatus = $status->getComplianceViewStatus('living_well_rested');
        $livingSmartStatus = $status->getComplianceViewStatus('living_smart');
        $livingWellStatus = $status->getComplianceViewStatus('living_well');
        $beingVideos = $status->getComplianceViewStatus('being_videos');
        $coachingStatus = $status->getComplianceViewStatus('coaching_program');
        $consultationStatus = $status->getComplianceViewStatus('consultation');

        $has_screening = false;

        if ($screeningStatus->getStatus() == ComplianceStatus::COMPLIANT) $has_screening = true;

        // Tobacco Logic Calculation
        if (($tobaccoStatus->getComment() == "Positive" || empty($tobaccoStatus->getComment())) && $livingFreeStatus->getStatus() != ComplianceStatus::COMPLIANT) {
            $tobaccoStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        if ($livingFreeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $tobaccoStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

        // Biometric Calculation
        $biometric_met = 0;
        if ($waistStatus->isCompliant()) $biometric_met++;
        if ($bloodPressureStatus->isCompliant()) $biometric_met++;
        if ($hdlStatus->isCompliant()) $biometric_met++;
        if ($triglyceridesStatus->isCompliant()) $biometric_met++;
        if ($hemoglobinStatus->isCompliant()) $glucoseStatus->setStatus(ComplianceStatus::COMPLIANT);
        if ($glucoseStatus->isCompliant()) $biometric_met++;

        if ($biometric_met >= 3) {
            $biometricGoalsStatus->setStatus(ComplianceStatus::COMPLIANT);
            $biometricGoalsStatus->setPoints(30);
        } else {
            $biometricGoalsStatus->setPoints(0);
        }

        // High Risk Coaching Calculation
        $risks = getExtendedRiskForUser($user, false, false, false, $startdate = "2021-01-01", $enddate = "2021-12-31");
        $risks = $risks['number_of_risks'];

        if ($risks >= 4 && $has_screening) {
            $coachingStatus->setComment("Qualified");
        } else {
            $coachingStatus->setComment("Not Qualified");
        }

        $elearningView = $elearningStatus->getComplianceView();
        if (($biometric_met >= 3 && $has_screening) || !$has_screening ) {
            $elearningStatus->setComment("Not Qualified");
            $elearningStatus->setPoints(0);
        } else {
            $points = $elearningStatus->getPoints();
            $max_points = 30;

            if ($points > $max_points) $elearningStatus->setPoints($max_points);

            $elearningView->setMaximumNumberOfPoints($max_points);

            if ($points == 30) $elearningStatus->setStatus(ComplianceStatus::COMPLIANT);
        }   

        // Gather Points and Point Calculation
        $wellness_points = 0;
        $wellness_points += $biometricGoalsStatus->getPoints();
        $wellness_points += $elearningStatus->getPoints();
        $wellness_points += $livingFitStatus->getPoints();
        $wellness_points += $livingLeanStatus->getPoints();
        $wellness_points += $livingEasyStatus->getPoints();
        $wellness_points += $livingWellRestedStatus->getPoints();
        $wellness_points += $livingSmartStatus->getPoints();
        $wellness_points += $livingWellStatus->getPoints();
        $wellness_points += $beingVideos->getPoints();
        $wellness_points += $coachingStatus->getPoints();
        $wellness_points += $consultationStatus->getPoints();

        $wellnessActivitiesStatus->setPoints($wellness_points);

        if ($wellness_points >= 50) {
            $wellnessActivitiesStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

        // Calculate Earnings
        $earnings = 0;
        if ($hraStatus->isCompliant() & $screeningStatus->isCompliant()) {
            $earnings += 280;
        }
        if ($tobaccoStatus->isCompliant()) {
            $earnings += 80;
        }
        if ($wellnessActivitiesStatus->isCompliant()) {
            $earnings += 360;
        }
        $status->setComment('$'.$earnings);
    }
}


class MeridianHealth2021WMS2Printer implements ComplianceProgramReportPrinter
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

    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $hraStatus = $status->getComplianceViewStatus('hra');
        $screeningStatus = $status->getComplianceViewStatus('screening');
        $tobaccoStatus = $status->getComplianceViewStatus('tobacco');
        $wellnessActivitiesStatus = $status->getComplianceViewStatus('wellness_activities');
        $biometricGoalsStatus = $status->getComplianceViewStatus('biometric_goals');
        $waistStatus = $status->getComplianceViewStatus('waist');
        $bloodPressureStatus = $status->getComplianceViewStatus('blood_pressure');
        $hdlStatus = $status->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $status->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $status->getComplianceViewStatus('glucose');
        $hemoglobinStatus = $status->getComplianceViewStatus('hemoglobin');
        $elearningStatus = $status->getComplianceViewStatus('elearning');
        $livingFitStatus = $status->getComplianceViewStatus('living_fit');
        $livingLeanStatus = $status->getComplianceViewStatus('living_lean');
        $livingEasyStatus = $status->getComplianceViewStatus('living_easy');
        $livingWellRestedStatus = $status->getComplianceViewStatus('living_well_rested');
        $livingSmartStatus = $status->getComplianceViewStatus('living_smart');
        $livingWellStatus = $status->getComplianceViewStatus('living_well');
        $beingVideos = $status->getComplianceViewStatus('being_videos');
        $coachingStatus = $status->getComplianceViewStatus('coaching_program');
        $consultationStatus = $status->getComplianceViewStatus('consultation');

        $wellnessPoints = $wellnessActivitiesStatus->getPoints();

        $appointment_link = "/compliance/meridian-health-2021/schedule/content/schedule-appointments";
        ?>
        <style>
            .grey-label {
                background: #546E7A;
                white-space: normal;
                display: inline-block;
                line-height: 18px;
                text-align: left;
                font-size: 12px;
                padding: .3em .6em .3em;
            }

            .success {
                color: #74c36e;
            }

            .danger {
                color: #F15752;
            }

            #nsk-card strong {
                line-height: 20px;
                margin-bottom: 10px;
                display: inline-block;
            }

            h2 {
                margin-top: 40px;
            }

            h3 {
                line-height: 24px;
            }

            .basic-report-card {
                margin-bottom: 20px;
                border-radius: 2px;
                border-color: #EEEEEE;
            }

            .basic-report-card.thick {
                border: 2px solid #E0E0E0;;
            }

            .basic-report-card strong {
                display: inline-block;
                line-height: 20px;
                margin-bottom: 10px;
            }

            .basic-report-card .icon {
                padding-right: 0;
            }

            span.subheading {
                display: inline-block;
                margin-top: -5px;
                line-height: 20px;
                margin-bottom: 10px;
            }
        </style>

        <div class="row">
            <div class="col-md-12">
                <h1>2021 Incentive Report Card</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <h2>Earn up to $720 in premium incentives by completing the following wellness steps:</h2>

        <h3>
            <strong>Step 1:</strong> Earn $280 by completing the HRA and Health Screening with Tobacco Test at a
            Meridian Health Services Provider location, or call the Circle Wellness customer service dept at 866-682-3020
            x 204 to order an on-demand packet to screen at a LabCorp location (complete between 1/1/2021- 5/31/2021).
            <span style="color: red;">NOW EXTENDED! New deadline 6/15/2021!</span>
        </h3>

        <h3>
            <strong>Step 2:</strong> Earn $80 by testing negative for tobacco. If you test positive for tobacco you can
            earn $80 by completing the <a href="/search-learn/lifestyle-management/content/12088?filter=livingfree">"Living Free"</a>
            tobacco cessation program by 9/1/2021. <span style="color: red;">NOW EXTENDED! New Deadline 9/30/2021!</span>
        </h3>

        <h3><strong>Step 3:</strong> Earn $360 by accummulating 50 points for participating in wellness activities (1/1/2021- 11/1/2021).</h3>

        <h3><strong>Current Earnings: <?= $status->getComment(); ?></strong></h3>

        <h2 style="text-transform: uppercase; margin-top: 60px;"><strong>Complete the following steps for your 2021 Wellness Program</strong></h2>
        <h2>Step 1: Complete the HRA. Complete a Health Screening with Tobacco Test</h2>
        <div class="row">
            <div class="col-sm-2 icon"></div>
            <div class="col-sm-5"><strong>Item</strong></div>
            <div class="col-sm-3 actions text-center"><strong>Action</strong></div>
            <div class="col-sm-2 item text-center"><strong>Status</strong></div>
        </div>
        <div class="basic-report-card thick">
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-chart-pie" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-5">
                    <strong>HRA</strong><br>
                    <span class="label grey-label">Complete between 1/1/2021- 6/15/2021</span>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/compliance/meridian-health-2021/hra/content/my-health">Take HRA</a>
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($hraStatus) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-clipboard-list" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-5">
                    <strong>Screening with Tobacco Test</strong><br>
                    <span class="label grey-label">Complete between 1/1/2021- 6/15/2021</span>
                </div>
                <div class="col-sm-3 actions"></div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($screeningStatus) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
        </div>
        <h2>Step 2: Tobacco Status</h2>
        <div class="row">
            <div class="col-sm-2 icon"></div>
            <div class="col-sm-5"><strong>Item</strong></div>
            <div class="col-sm-3 actions text-center"><strong>Action</strong></div>
            <div class="col-sm-2 item text-center"><strong>Status</strong></div>
        </div>
        <div class="basic-report-card thick">
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-smoking" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-5">
                    <strong>Tobacco Status</strong><br>
                    <span class="label grey-label">Complete between 1/1/2021 - 9/30/2021</span>
                </div>
                <div class="col-sm-3 actions">
                    <?php if ($tobaccoStatus->getComment() == 'Positive'): ?>
                        <a class="btn btn-primary btn-sm" href="/search-learn/lifestyle-management/content/12088?filter=livingfree">Living Free Program</a>
                    <?php endif; ?>
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($tobaccoStatus, $tobaccoStatus->getComment() == 'Positive') ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
        </div>
        <h2>Step 3: Wellness Activities - Earn 50 Points by completing activities below between 1/1/2021 - 11/1/2021</h2>
        <div class="row">
            <div class="col-sm-2 icon"></div>
            <div class="col-sm-4"><strong>Biometric Measures</strong></div>
            <div class="col-sm-2 actions text-center"><strong>Goal</strong></div>
            <div class="col-sm-2 actions text-center"><strong>Earned Points</strong></div>
            <div class="col-sm-2 item text-center"><strong>Status</strong></div>
        </div>
        <div class="basic-report-card">
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-stethoscope" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-4"><strong>Biometric Goals</strong><br>
                    <span class="label grey-label">Meet 3 of 5 biometric goals to earn 30 points. If you do not meet at least three goals you may still earn the points by completing eLearning lessons.</span><br><br>
                </div>
                <div class="col-sm-2 actions text-center">30 Points</div>
                <div class="col-sm-2 actions text-center"><?= $biometricGoalsStatus->getPoints() ?> Points</div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($biometricGoalsStatus) ?>
                </div>
            </div>
                    <div class="row">
            <div class="col-sm-2 icon"></div>
            <div class="col-sm-4"><strong></strong></div>
            <div class="col-sm-2 actions text-center"><strong>Goal</strong></div>
            <div class="col-sm-2 actions text-center"><strong>Results</strong></div>
            <div class="col-sm-2 item text-center"><strong>Status</strong></div>
        </div>
            <div class="row">
                <div class="col-sm-2 icon"></div>
                <div class="col-sm-4"><strong>Waist Circumference</strong></div>
                <div class="col-sm-2 actions text-center">Men ≤ 40<br>Women ≤ 35</div>
                <div class="col-sm-2 actions text-center"><?= $waistStatus->getComment() ?></div>
                <div class="col-sm-2 item text-center"><?= $this->displayStatus($waistStatus, $waistStatus->getComment() != "No Screening") ?></div>
            </div>
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"></div>
                <div class="col-sm-4"><strong>Blood Pressure</strong></div>
                <div class="col-sm-2 actions text-center">≤ 130/85</div>
                <div class="col-sm-2 actions text-center"><?= $bloodPressureStatus->getComment() ?></div>
                <div class="col-sm-2 item text-center"><?= $this->displayStatus($bloodPressureStatus, $bloodPressureStatus->getComment() != "No Screening") ?></div>
            </div>
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"></div>
                <div class="col-sm-4"><strong>HDL</strong></div>
                <div class="col-sm-2 actions text-center">Men ≥ 40<br>Women ≥ 50</div>
                <div class="col-sm-2 actions text-center"><?= $hdlStatus->getComment() ?></div>
                <div class="col-sm-2 item text-center"><?= $this->displayStatus($hdlStatus, $hdlStatus->getComment() != "No Screening") ?></div>
            </div>
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"></div>
                <div class="col-sm-4"><strong>Triglycerides</strong></div>
                <div class="col-sm-2 actions text-center">≤ 150</div>
                <div class="col-sm-2 actions text-center"><?= $triglyceridesStatus->getComment() ?></div>
                <div class="col-sm-2 item text-center"><?= $this->displayStatus($triglyceridesStatus, $triglyceridesStatus->getComment() != "No Screening") ?></div>
            </div>
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"></div>
                <div class="col-sm-4"><strong>Fasting Glucose or HgA1c</strong></div>
                <div class="col-sm-2 actions text-center">Glucose ≤ 100<br>HgA1c ≤ 5.9</div>
                <div class="col-sm-2 actions text-center"><?= $glucoseStatus->getComment() ?><br><?= $hemoglobinStatus->getComment() ?></div>
                <div class="col-sm-2 item text-center"><?= $this->displayStatus($glucoseStatus, ($glucoseStatus->getComment() != "No Screening") || ($hemoglobinStatus->getComment() != "No Screening")) ?></div>
            </div>
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2 icon"></div>
            <div class="col-sm-3"><strong>Activities</strong></div>
            <div class="col-sm-3 actions text-center"><strong>Action</strong></div>
            <div class="col-sm-2 actions text-center"><strong>Earned Points</strong></div>
            <div class="col-sm-2 item text-center"><strong>Status</strong></div>
        </div>
        <div class="basic-report-card">
            <div class="row"><div class="col-sm-12"><br></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-school" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Elearning</strong><br>
                    <span class="label grey-label">5 Points (<?= $elearningStatus->getComplianceView()->getMaximumNumberOfPoints(); ?> Points Max)<br>Points only awarded as reasonable alternative to meet biometric goals.</span><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/search-learn/elearning/content/9420?action=lessonManager&tab_alias=all_lessons">View Courses</a>
                </div>
                <div class="col-sm-2 actions">
                    <?= $elearningStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?php if ($elearningStatus->getComment() == "Not Qualified") :?>
                        <label class="label" style="background: #90A4AE"><?= $elearningStatus->getComment() ?></label>
                    <?php else: ?>
                        <?= $this->displayStatus($elearningStatus) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-book" style="--fa-primary-color: #F05A28;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Living Fit 90 Day Walking Challenge</strong><br>
                    <span class="label grey-label">20 Points</span><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/search-learn/lifestyle-management/content/12088?filter=livingfit">Start the Program</a>
                </div>
                <div class="col-sm-2 actions">
                    <?= $livingFitStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($livingFitStatus) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-book" style="--fa-primary-color: #1B75BB;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Living Lean</strong><br>
                    <span class="label grey-label">15 Points</span><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/search-learn/lifestyle-management/content/12088?filter=livinglean">Start the Program</a>
                </div>
                <div class="col-sm-2 actions">
                    <?= $livingLeanStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($livingLeanStatus) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-book" style="--fa-primary-color: #EC297B;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Living Easy</strong><br>
                    <span class="label grey-label">15 Points</span><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/search-learn/lifestyle-management/content/12088?filter=livingeasy">Start the Program</a>
                </div>
                <div class="col-sm-2 actions">
                    <?= $livingEasyStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($livingEasyStatus) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-book" style="--fa-primary-color: #605CA9;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Living Well Rested</strong><br>
                    <span class="label grey-label">15 Points</span><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/search-learn/lifestyle-management/content/12088?filter=livingwellrested">Start the Program</a>
                </div>
                <div class="col-sm-2 actions">
                    <?= $livingWellRestedStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($livingWellRestedStatus) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-book" style="--fa-primary-color: #7AC943;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Living Smart</strong><br>
                    <span class="label grey-label">15 Points</span><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/search-learn/lifestyle-management/content/12088?filter=livingsmart">Start the Program</a>
                </div>
                <div class="col-sm-2 actions">
                    <?= $livingSmartStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($livingSmartStatus) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-book" style="--fa-primary-color: #92268F;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Living Well with Diabetes</strong><br>
                    <span class="label grey-label">15 Points</span><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/search-learn/lifestyle-management/content/12088?filter=livingwell">Start the Program</a>
                </div>
                <div class="col-sm-2 actions">
                    <?= $livingWellStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($livingWellStatus) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-video" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Being Videos</strong><br>
                    <span class="label grey-label">5 Points (15 Points Max)</span><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/search-learn/learn-by-video/content/learn-by-video">Watch Videos</a>
                </div>
                <div class="col-sm-2 actions">
                    <?= $beingVideos->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($beingVideos) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-user-headset" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>High Risk Coaching Program</strong><br>
                    <span class="label grey-label">20 Points<br>Participants will be notified via their report card if they qualify for coaching. If qualified call 866-682-3020 ext. 125 to enroll by 7/31/2021. If no answer, please leave message</span><br>
                </div>
                <div class="col-sm-3 actions">
                </div>
                <div class="col-sm-2 actions">
                    <?= $coachingStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?php if ($coachingStatus->getComment() == "Qualified") :?>
                        <label class="label label-danger"><?= $coachingStatus->getComment() ?></label>
                    <?php else: ?>
                        <label class="label" style="background: #90A4AE"><?= $coachingStatus->getComment() ?></label>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-phone" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-3"><strong>Private Web/Phone Consultation</strong><br><span class="subheading">(Schedule by 6/30/2021 and complete by 7/15/2021)</span><br>
                    <span class="label grey-label">5 Points</span><br><br>
                </div>
                <div class="col-sm-3 actions">
                    Please call 866-682-3020 ext. 204 to schedule
                </div>
                <div class="col-sm-2 actions">
                    <?= $consultationStatus->getPoints() ?> Points
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($consultationStatus) ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-2 icon"></div>
            <div class="col-sm-4"><strong></strong></div>
            <div class="col-sm-2 actions text-center"><strong>Goal</strong></div>
            <div class="col-sm-2 actions text-center"><strong>Total Points</strong></div>
            <div class="col-sm-2 item text-center"><strong>Status</strong></div>
        </div>
        <div class="basic-report-card thick">
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-laptop-medical" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-4"><strong>Wellness Activities Summary</strong><br><br>
                </div>
                <div class="col-sm-2 actions text-center">50 Points</div>
                <div class="col-sm-2 actions text-center"><?= $wellnessPoints ?> Points</div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($wellnessActivitiesStatus) ?>
                </div>
            </div>
        </div>
        <?php }
    }
