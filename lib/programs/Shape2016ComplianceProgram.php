<?php

class ShapePrinter extends CHPShapeComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <p style="font-weight:bold">
            The 2016-2017 SFW Annual Requirements must be met between March 1, 2016 and May 1, 2016. Please
            view the list of requirements below; each requirement shows related details and has a light indicator
            showing if it has been completed, is incomplete, or is not required of you this year.
        </p>
        <script type="text/javascript">
            $(function() {
                $('.view-disease_management .status-<?php echo ComplianceStatus::NA_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/notrequired.jpg');

                <?php if($status->getComplianceViewStatus('enroll_program')->getAttribute('show_not_required')) : ?>
                $('.view-enroll_program .status-<?php echo ComplianceStatus::NA_COMPLIANT ?>').attr('src', '/images/shape/lights/notrequired.jpg');
                <?php elseif($status->getComplianceViewStatus('enroll_program')->getAttribute('show_actual_green')) : ?>
                $('.view-enroll_program .status-<?php echo ComplianceStatus::COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?php echo ComplianceStatus::PARTIALLY_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?php echo ComplianceStatus::NA_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                <?php else : ?>
                $('.view-enroll_program .status-<?php echo ComplianceStatus::NA_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/pending.jpg');
                $('.view-enroll_program .status-<?php echo ComplianceStatus::COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/notcompliant.jpg');
                <?php endif ?>
            });
        </script>
        <?php
        parent::printHeader($status);
    }

    public function  printFooter(ComplianceProgramStatus $status)
    {
        ?>
        <p>* If your physician completes your BMI card, you are responsible to
            turn the card into the Fitness Factory on time.</p>

        <p>** This site will be updated first, by mid-March (to include historical
            information through 3/1), then updated again every week through May 1.</p>

        <p>*** This requirement will be determined when the 2015-2016 program
            ends on April 30, 2016 and will be updated to reflect your status on
            May 4, 2016. Please direct your questions to your coach in the meantime.</p>

        <p>This program is designed to promote good health and disease prevention. The program
            applies to all employees of Shape Corp. Family of Companies. If it is unreasonably
            difficult or medically inadvisable for you to satisfy the program standard, we
            will provide a reasonable alternative through which you can satisfy the program
            standard. Recommendations of your personal physicians will be accommodated in
            administering the reasonable alternative. Please contact Holly Severance at 616.844.3239 for more information on reasonable alternatives.</p>
        <?php
    }
}

class Shape2016BMINicotineCardComplianceProgramView extends PlaceHolderComplianceView
{
    public function getStatus(User $user)
    {
        $records = $user->getDataRecords('goal-tracking-GoalScreeningForm');

        $nicotineUse = false;
        foreach($records as $record) {
            if($record->exists()
                && $record->nicotine_use != ''
                && date('Y-m-d', strtotime($record->getCreationDate())) >= '2016-02-01'
                && date('Y-m-d', strtotime($record->getCreationDate())) <= '2016-06-01') {
                $nicotineUse = true;
            }
        }

        if($nicotineUse) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }
    }
}

class Shape2016FamilyWellnessComplianceProgramView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'enroll_program';
    }

    public function getDefaultReportName()
    {
        return 'Enroll/Re-enroll in 2016-2017 Weight Mgt or Nicotine Program';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $isAttendEnrollment = $this->isAttendEnrollment($user);

        $isShowedAppt = $this->isShowedAppt($user);

        $record = $user->getNewestDataRecord('shape_family_wellness_agreement');
        if($record->participant_agreed && $record->signed_date_of_participant > self::SHAPE2016BMINICOTINESTARTDATE) {
            $isAgreementSigned = true;
        } else {
            $isAgreementSigned = false;
        }

        $records =  $user->getDataRecords('goal-tracking-GoalScreeningForm');

        foreach($records as $record) {
            if(date('Y-m-d', strtotime($record->getCreationDate())) >= self::SHAPE2016BMINICOTINESTARTDATE
                && date('Y-m-d', strtotime($record->getCreationDate())) <= self::SHAPE2016BMINICOTINEENDDATE) {
                if($record->nicotine_program == 'Not Required') {
                    $status = new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
                    $status->setAttribute('show_not_required', true);

                    return $status;
                } else if ($record->nicotine_program == 'Required') {

                    if($isAttendEnrollment || $isAgreementSigned || $isShowedAppt) {
                        $status = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
                        $status->setAttribute('show_actual_green', true);

                        return $status;
                    }

                    $status = new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);

                    return $status;
                }
            }
        }

        if($isAttendEnrollment || $isAgreementSigned || $isShowedAppt) {
            $status = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
            $status->setAttribute('show_actual_green', true);

            return $status;
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
    }

    protected function isShowedAppt(User $user)
    {
        $appintmentTimes = AppointmentTime::getScheduledAppointments($user, '2016-01-01');

        foreach($appintmentTimes as $appintmentTime) {
            if($appintmentTime->getAppointment()->getTypeId() == 56
                && $appintmentTime->getShowStatus() == APPOINTMENT_USER_SHOWED) return true;
        }

        return false;
    }

    public function isAttendEnrollment(User $user)
    {
        $session = CoachingSessionTable::getInstance()->findMostRecentSessionForUser($user);
        if(is_object($session)) {
            $report = CoachingReportTable::getInstance()->findMostRecentReport($session);
            if(is_object($report)) {
                $reportEdit = CoachingReportEditTable::getInstance()->findMostRecentEdit($report);
                if(is_object($reportEdit)
                    && date('Y-m-d', strtotime($reportEdit->created_at)) >= self::SHAPE2016BMINICOTINESTARTDATE
                    && date('Y-m-d', strtotime($reportEdit->created_at)) <= self::SHAPE2016BMINICOTINEENDDATE) {
                    $recordedDocument = $reportEdit->getRecordedDocument();
                    $recordedFields = $recordedDocument->getRecordedDocumentFields();

                    foreach($recordedFields as $recordedField) {
                        $name = $recordedField->getFieldName();
                        $value = $recordedField->getFieldValue();

                        if($name == 'attended_group_enrollment' && $value) return true;
                    }
                }
            }
        }

        return false;
    }

    const SHAPE2016BMINICOTINESTARTDATE = '2016-03-01';
    const SHAPE2016BMINICOTINEENDDATE = '2016-06-01';
}

class Shape2016WeightManagementNicotineProgramView extends PlaceHolderComplianceView
{
    const SHAPE_2015_COACHING_RECORD_ID = 427;
    public function getStatus(User $user)
    {
         if($user->hasAttribute(Attribute::COACHING_END_USER)) {
            $program = ComplianceProgramRecordTable::getInstance()->find(self::SHAPE_2015_COACHING_RECORD_ID);

            if($program) {
                $coachingProgram = $program->getComplianceProgram();
                $coachingProgram->setActiveUser($user);
                $status = $coachingProgram->getStatus();
                if($status->getStatus() == ComplianceStatus::COMPLIANT) {
                    return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
                }
            }
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }
}

class ShapeCompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {
        $testsRequired = array(
            'cholesterol', 'triglycerides', 'ldl', 'hdl', 'glucose'
        );

        foreach($testsRequired as $test) {
            if(!isset($array[$test]) || !trim($array[$test])) {
                return false;
            }
        }

        return true;
    }
}

class Shape2016ComplianceProgramAdminReportPrinter extends BasicComplianceProgramAdminReportPrinter
{
    protected function showUser(User $user)
    {
        return !$user->expired();
    }
}

class Shape2016ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new ShapePrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new Shape2016ComplianceProgramAdminReportPrinter();

        // Full SSN over last 4
        $printer->setShowUserFields(null, null, null, false, true);
        $printer->setShowUserContactFields(true, null, null);

        $printer->setShowTotals(false);

        $printer->addCallbackField('covered_social_security_number', function (User $user) {
            return $user->covered_social_security_number;
        });

        $printer->addCallbackField('location', function (User $user) {
            return $user->location;
        });

        $printer->addCallbackField('division', function (User $user) {
            return $user->division;
        });

        $printer->addCallbackField('coaching_end_user', function(User $user) {
            return $user->hasAttribute(Attribute::COACHING_END_USER) ? 'Yes' : 'No';
        });

        $printer->setShowStatus(null, null, true);

        return $printer;
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new ComplianceStatusMapper(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Done', '/images/shape/lights/done.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Done', '/images/shape/lights/incomplete.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done', '/images/shape/lights/notdoneyet.jpg'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('N/A', '/images/shape/lights/notrequired.jpg')
        )));

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        $group = new ComplianceViewGroup('Requirements');

        $hra = new CompleteHRAComplianceView($startDate, '2016-05-01');
        $hra->setReportName('HRA (Health Risk Appraisal)<br />');
        $hra->setName('hra');
        $hra->setAttribute('about', '');
        $hra->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete annually between March 1 and May 1');
        $hra->setAttribute('did_this_link', '/content/i_did_this');

        if (sfConfig::get('app_wms2')) {
            $hra->emptyLinks();
            $hra->addLink(new \Link("My HRA & Results", "/compliance/shape-2015/my-health"));
        }
        
        $group->addComplianceView($hra);

        $tobacco = new Shape2016BMINicotineCardComplianceProgramView(ComplianceStatus::NOT_COMPLIANT);
        $tobacco->setReportName('BMI/Nicotine Card');
        $tobacco->setName('tobacco_bmi');
        $tobacco->setStatusSummary(ComplianceStatus::COMPLIANT, 'Complete annually between March 1 and May 1');
        $tobacco->setAttribute('about', 'BMI Measured with Fitness Factory staff, or with your Primary Care Physician. Sign Nicotine card in presence of Fitness Factory staff or HR manager.*');
        $tobacco->setAttribute('did_this_link', '/content/i_did_this');
        $group->addComplianceView($tobacco);

        $phye = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $phye->setName('physical');
        $phye->setReportName('Preventive Physical');
        $phye->setAttribute('about', 'Turn in the <a href="/resources/6992/2016-17 Spouse Physical Form.pdf" target="_blank">Physical Form</a>, completed.');
        $phye->setAttribute('did_this_link', '/content/i_did_this');
        $phye->setStatusSummary(ComplianceStatus::COMPLIANT, 'Spouse Year! Show physical was completed sometime between May 1, 2014 and May 1, 2016.<br/>
        ');
        $phye->setEvaluateCallback(array($this, 'physicalIsRequired'));
        $phye->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $startDate = '2014-05-01';
            $endDate = '2016-05-01';

            if(!$status->isCompliant() && !$status->getUsingOverride()) {

                $prevPhysicalView = new CompletePreventionPhysicalExamComplianceView($startDate, $endDate);

                if($prevPhysicalView->getStatus($user)->isCompliant()) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } else {
                    $scrView = new ShapeCompleteScreeningComplianceView($startDate, $endDate);

                    if($scrView->getStatus($user)->isCompliant()) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    }
                }
            }
        });
        $group->addComplianceView($phye);

        $disease = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $disease->setReportName('Disease Management **');
        $disease->setName('disease_management');
        $disease->setAttribute('did_this_link', '/content/1038');
        $disease->setAttribute('about', 'Only required if contacted directly by Priority Health.');
        $disease->setAttribute('link_add', 'If you were required for the Disease Management in 2015/2016, you must meet all of the requirements by May 1, 2015.');
        $disease->setStatusSummary(ComplianceStatus::COMPLIANT, 'Must comply with Priority Health guidelines only if contacted.');
        $group->addComplianceView($disease);


        $completeProgram = new Shape2016WeightManagementNicotineProgramView();
        $completeProgram->setReportName('Complete 2015-2016 Weight Management or Nicotine Program ***');
        $completeProgram->setName('completed_program');
        $completeProgram->setAttribute('about', 'Only required if currently enrolled in Weight Mgt. or Nicotine Program');
        $completeProgram->setAttribute('did_this_link', '/content/i_did_this');
        //$completeProgram->setAttribute('did_this_link', 'mailto:ffactory@shape.com');
        $completeProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you qualified for the Weight Mgt or Nicotine Program in 2015, it must be completed by May 1, 2016.');
        $group->addComplianceView($completeProgram);

        $enrollProgram = new Shape2016FamilyWellnessComplianceProgramView($startDate, $endDate);
        $enrollProgram->setReportName('Enroll/Re-enroll in 2016-2017 Wellness Program');
        $enrollProgram->setName('enroll_program');
        $enrollProgram->setAttribute('about', 'Only required if BMI is 30 or greater or you use nicotine products and wish to participate.');
        $enrollProgram->setAttribute('link_add', 'Call Fitness Factory for an appointment.');
        $enrollProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'If required, enrollment must be completed by June 1, 2016.');
        $group->addComplianceView($enrollProgram);



        $this->addComplianceViewGroup($group);
    }

    public function physicalIsRequired(User $user)
    {
        return $user->relationship_type == Relationship::SPOUSE;
    }

    public function isRequired(User $user)
    {
        return $user->hiredate === null || $user->getDateTimeObject('hiredate')->format('U') < strtotime('2015-01-01');
    }
}