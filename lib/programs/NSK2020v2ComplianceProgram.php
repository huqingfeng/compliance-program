<?php

use hpn\steel\query\SelectQuery;

class NSK2020v2CompleteScreeningComplianceView extends CompleteScreeningComplianceView
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

class NSK2020v2ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new NSK2020v2WMS2Printer();
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

                    if ($viewStatus->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                        $data[sprintf('%s Compliant', $viewName)] = 'N/A';
                    } else {
                        $data[sprintf('%s Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                    }

                    if($viewName == 'Health Risk Assessment (HRA)') {
                        $data['HRA Date'] = $viewStatus->getComment();
                    }
                }
            }

            $data['Phone Consultation Showed'] = $this->getPhoneConsultShowed($user);
            $data['Monthly Surcharge'] = $status->getComment();
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    protected function getTickerCountdown($status)
    {
        $tickerCountDown = 0;
        if($screeningDate = $status->getAttribute('screening_date')) {
            if(time() >= strtotime($screeningDate)) {
                $dateDiff = floor((time() - strtotime($screeningDate)) / (60 * 60 * 24));
                $tickerCountDown = max(0, 41 - $dateDiff);
            }
        }

        return $tickerCountDown;
    }

    protected function getPhoneConsultShowed($user)
    {
        $row = SelectQuery::create()
            ->select('a.date, at.showed')
            ->from('appointments a')
            ->innerJoin('appointment_times at')
            ->on('at.appointmentid = a.id')
            ->where('at.user_id = ?', array($user->getID()))
            ->andWhere('a.typeid = 21')
            ->andWhere('a.date BETWEEN ? AND ?', array('2020-01-01', '2020-12-31'))
            ->andWhere('at.showed = 1')
            ->hydrateSingleRow()
            ->groupBy('at.user_id')
            ->execute();

        $phoneConsultShowed = '';
        if(isset($row['date']) && isset($row['showed']) && $row['showed']) {
            $phoneConsultShowed .= 'Yes';
            $phoneConsultShowed .= " ({$row['date']})";
        }

        return $phoneConsultShowed;
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
        $hra = new CompleteHRAComplianceView($programStart, "2020-09-11");
        $hra->setReportName('Health Risk Assessment (HRA)');
        $hra->setName('hra');
        $hra->emptyLinks();
        $reportCardGroup->addComplianceView($hra);

        // Tobacco Affidavit Compliancy
        $tobaccoAffidavit = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobaccoAffidavit->setName('tobacco_affidavit');
        $tobaccoAffidavit->setReportName('Be Tobacco/Nicotine Free or Complete Ulliance Day One Program');
        $reportCardGroup->addComplianceView($tobaccoAffidavit);

        // Ulliance Compliancy
        $ulliance = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $ulliance->setName('ulliance');
        $ulliance->setReportName('Complete Ulliance Day One Program');
        $reportCardGroup->addComplianceView($ulliance);

        // Private Consultation Compliancy
        $consultation = new CompletePrivateConsultationComplianceView($programStart, "2020-09-11");
        $consultation->setName('consultation');
        $consultation->setReportName('Private Consultation/Coaching Session');
        $reportCardGroup->addComplianceView($consultation);

        // GUAG Compliancy
        $guag = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $guag->setName('guag');
        $guag->setReportName('Get Up and Go Challenge');
        $reportCardGroup->addComplianceView($guag);

        // Living Easy Compliancy
        $livingEasy = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $livingEasy->setName('living_easy');
        $livingEasy->setReportName('Complete LivingEasy Stress and Resilience Course');
        $reportCardGroup->addComplianceView($livingEasy);

        $this->addComplianceViewGroup($reportCardGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();

        if(!$this->evaluateOverall) {
            return;
        }

        $hra_status = $status->getComplianceViewStatus('hra');
        $tobacco_status = $status->getComplianceViewStatus('tobacco_affidavit');
        $record = UserDataRecord::getNewestRecord($user, 'nsk_tobacco_2020', true);

        $accepted = $record->getDataFieldValue("smoker") === "1";
        $denied = $record->getDataFieldValue("smoker") === "0";

        $ulliance_status = $status->getComplianceViewStatus('ulliance');

        if ($denied || $ulliance_status->getStatus() == ComplianceStatus::COMPLIANT) {
            $tobacco_status->setStatus(ComplianceStatus::COMPLIANT);
        }

        if ($accepted && $ulliance_status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
            $ulliance_status->setStatus(ComplianceStatus::NOT_COMPLIANT); 
        }

        $consultation_status = $status->getComplianceViewStatus('consultation');
        $guag_status = $status->getComplianceViewStatus('guag');
        $living_easy_status = $status->getComplianceViewStatus('living_easy');

        if ($hra_status->isCompliant() && ($accepted || $denied)) {
            $record->setDataFieldValue("schedule_appointment_enabled", true);
        } else {
            $record->setDataFieldValue("schedule_appointment_enabled", false);
        }

        if ($this->isNewHire($status->getUser())) {
            $status->setComment('$0');
        } else {
            if($hra_status->isCompliant() && $tobacco_status->isCompliant() && $consultation_status->isCompliant() && ($guag_status->isCompliant() || $living_easy_status->isCompliant())) {
                $status->setComment('$0');
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } elseif($hra_status->isCompliant() && $tobacco_status->isCompliant() && $consultation_status->isCompliant()) {
                $status->setComment('$30');
            } elseif($hra_status->isCompliant() && $tobacco_status->isCompliant()) {
                $status->setComment('$45');
            } else {
                $status->setComment('$90');
            }
        }
    }

    public function useParallelReport()
    {
        return false;
    }

    public function isSpouse(User $user)
    {
        return $user->getRelationshipType() == 2 ? true : false;
    }

    public function isNewHire(User $user)
    {
        $groupNewHireMapper = array(
            '1931'  => '2020-05-31',
            '1930'  => '2020-05-31',
            '1935'  => '2020-05-31',
            '1937'  => '2020-05-31',
            '1932'  => '2020-05-31',
            '1934'  => '2020-05-31',
            '1933'  => '2020-05-31'
        );

        if(isset($groupNewHireMapper[$user->client_id])
            && $user->getHiredate() >= $groupNewHireMapper[$user->client_id]) {
            return true;
        }

        return false;
    }

    public function getRelatedUser(User $user)
    {
        $relatedUser = false;

        $relationshipUsers = array();

        if($user->relationship_user_id && !$user->relationshipUser->expired()) {
            $relationshipUsers[] = $user->relationshipUser;
        }

        foreach($user->relationshipUsers as $relationshipUser) {
            if(!$relationshipUser->expired()) {
                $relationshipUsers[] = $relationshipUser;
            }
        }

        foreach($relationshipUsers as $relationshipUser) {
            if(in_array($relationshipUser->relationship_type, $this->relationshipTypes)) {
                $relatedUser = $relationshipUser;

                break;
            }
        }

        return $relatedUser;
    }

    public function getRelatedUserComplianceStatus(User $user)
    {
        $relatedUser = $this->getRelatedUser($user);

        $programRecord = ComplianceProgramRecordTable::getInstance()->find(self::RECORD_ID);

        $program = $programRecord->getComplianceProgram();

        $program->setActiveUser($user);
        $status = $program->getStatus();
        $status->setStatus(ComplianceStatus::NA_COMPLIANT);

        if($relatedUser) {
            $program->setActiveUser($relatedUser);
            $status = $program->getStatus();
        }

        return $status;
    }

    private $relationshipTypes = array(Relationship::SPOUSE, Relationship::EMPLOYEE);
    protected $evaluateOverall = true;
    const RECORD_ID = 1496;
}


class NSK2020v2WMS2Printer implements ComplianceProgramReportPrinter
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

    public function displayStatus($status) {
        if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
            return '<i class="fa fa-check success"></i>';
        } else {
            return '<label class="label label-danger">Incomplete</label>';
        }
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();
        $record = UserDataRecord::getNewestRecord($user, 'nsk_tobacco_2020', true);
        $accepted = $record->getDataFieldValue("smoker") === "1";

        $hraCompliant = $status->getComplianceViewStatus('hra');
        $tobaccoCompliant = $status->getComplianceViewStatus('tobacco_affidavit');
        $ullianceCompliant = $status->getComplianceViewStatus('ulliance');
        $phoneConsultation = $status->getComplianceViewStatus('consultation');
        $guagCompliant = $status->getComplianceViewStatus('guag');
        $livingEasyCompliant = $status->getComplianceViewStatus('living_easy');

        $appointment_link = "/compliance/nsk-2020/schedule/content/schedule-appointments";
        if ((!$hraCompliant->isCompliant() || !$tobaccoCompliant->isCompliant()) && !$accepted) { 
            $appointment_link = ""; ?>
            <script>
                $(function(){
                    $("#appointment_btn").click(function(e){
                        e.preventDefault();
                        e.stopPropagation();
                        alert("You must complete both the HRA and tobacco affidavit before scheduling a phone consultation appointment. You can complete these requirements by going to your My Report Card page and clicking on the links for each requirement.");
                    });
                });
            </script>
  <?php } ?>
        <style>
            .purple-label {
                background: #9c27b0;
                white-space: normal;
                display: inline-block;
                line-height: 16px;
                text-align: left;
            }

            .success {
                color: #74c36e;
            }

            #nsk-card strong {
                line-height: 20px;
                margin-bottom: 10px;
                display: inline-block;
            }

            h3 {
                line-height: 24px;
            }
        </style>

        <div class="row">
            <div class="col-md-12">
                <h1>2020 Incentive Report Card</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <a href="/compliance/nsk-2020/overview">Full Program Details</a><br/>
                <a href="/content/nsk-previous-program-years">Previous Program Years</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <h3>Due to the global pandemic and current work environment, we have made some changes to this year’s wellness benefit and incentive program. <a href="/compliance/nsk-2020/overview">Full Program Details here</a></h3>

        <h3><strong>Current Surcharge: <?= $status->getComment(); ?></strong></h3>

        <h2>Complete the following steps for your 2020 Wellness Incentive Program</h2>

        <div id="nsk-card" class="basic-report-card">
            <div class="row">
                <div class="col-sm-12"><br></div>
            </div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-chart-pie" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-5">
                    <strong>Health Risk Assessment</strong><br>
                    <span class="label purple-label">Complete between 01/01/2020 - 9/11/2020</span>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/compliance/nsk-2020/my-health">Take HRA</a>
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($hraCompliant) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-file-alt" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-5">
                    <strong>Be Tobacco/Nicotine Free or Complete Ulliance Day One Program</strong><br>
                    <span class="label purple-label">Complete affidavit between 6/1/2020 & 9/11/2020. If you are a tobacco/nicotine user, you can participate in the Day One nicotine cessation program at <a style="color: #e0e0FF; text-decoration: underline;" href="http://nsk.lifeadvisorwellness.com">http://nsk.lifeadvisorwellness.com</a> or call Ulliance to enroll at 888-699-3554 by 10/12/2020. The Day One nicotine cessation program must be completed by 1/17/2021. </span>
                </div>
                <div class="col-sm-3 actions">
                    <?php if ($accepted): ?>
                        <a class="btn btn-primary btn-sm" target="_blank" href="http://nsk.lifeadvisorwellness.com/">Complete Ulliance Program</a>
                    <?php else: ?>
                        <a class="btn btn-primary btn-sm" href="/compliance/nsk-2020/my-report-card/content/nsk-tobacco-affidavit">Sign Affidavit</a>
                    <?php endif; ?>
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($tobaccoCompliant) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-user-md" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-5"><strong>Private Web/Phone Consultation</strong><br>
                    <span class="label purple-label">Schedule by 8/21/2020. Complete by 9/11/2020. Must complete the HRA and Tobacco/Nicotine Affidavit BEFORE scheduling a phone consultation.</span><br><br>
                </div>
                <div class="col-sm-3 actions">
                    <a id="appointment_btn" class="btn btn-primary btn-sm" href="<?= $appointment_link ?>">Sign up</a>
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($phoneConsultation) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-walking" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-5"><strong>Get Up and Go Challenge</strong><br>
                    <span class="label purple-label">Average at least 5,000 steps a day for two weeks. There will be three challenges to choose from with start dates of 7/6, 7/27, and 8/17. Status will be updated within a week of the campaign completion.</span><br><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/compliance/nsk-2020/home">Join a Challenge</a>
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($guagCompliant) ?>
                </div>
            </div>
            <div class="row"><div class="col-sm-12"><hr></div></div>
            <div class="row">
                <div class="col-sm-2 icon"><i class="fad fa-school" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
                <div class="col-sm-5"><strong>Complete LivingEasy Stress and Resilience Course</strong><br>
                    <span class="label purple-label">Allow at least 4 weeks to complete this online course. Enroll by 8/17/2020. It must be completed by 9/11/2020. Status will be updated weekly upon course completion.</span><br><br>
                </div>
                <div class="col-sm-3 actions">
                    <a class="btn btn-primary btn-sm" href="/compliance/nsk-2020/living-easy/content/12088?filter=livingeasy">Sign up</a>
                </div>
                <div class="col-sm-2 item">
                    <?= $this->displayStatus($livingEasyCompliant) ?>
                </div>
            </div>
        </div>
    <?php }
}
