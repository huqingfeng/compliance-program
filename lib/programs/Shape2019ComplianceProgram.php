<?php

class Shape2019BMINicotineCardComplianceProgramView extends PlaceHolderComplianceView
{
    public function getStatus(User $user)
    {
        $records = $user->getDataRecords('goal-tracking-GoalScreeningForm');

        $nicotineUse = false;
        foreach($records as $record) {
            if($record->exists()
                && ($record->nicotine_use != '' || $record->bmi != '')
                && date('Y-m-d', strtotime($record->getCreationDate())) >= '2019-03-01'
                && date('Y-m-d', strtotime($record->getCreationDate())) <= '2019-05-30') {
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

class Shape2019FamilyWellnessComplianceProgramView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'enroll_program';
    }

    public function getDefaultReportName()
    {
        return 'Enroll/Re-enroll in 2018-2019 Weight Mgt or Nicotine Program';
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
        if($record->participant_agreed && $record->signed_date_of_participant > self::Shape2019BMINICOTINESTARTDATE) {
            $isAgreementSigned = true;
        } else {
            $isAgreementSigned = false;
        }

        $records =  $user->getDataRecords('goal-tracking-GoalScreeningForm');

        foreach($records as $record) {
            if(date('Y-m-d', strtotime($record->getCreationDate())) >= self::Shape2019BMINICOTINESTARTDATE
                && date('Y-m-d', strtotime($record->getCreationDate())) <= self::Shape2019BMINICOTINEENDDATE) {
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
        $appintmentTimes = AppointmentTime::getScheduledAppointments($user, '2019-05-01');

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
                    && date('Y-m-d', strtotime($reportEdit->created_at)) >= self::Shape2019BMINICOTINESTARTDATE
                    && date('Y-m-d', strtotime($reportEdit->created_at)) <= self::Shape2019BMINICOTINEENDDATE) {
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

    const Shape2019BMINICOTINESTARTDATE = '2019-03-01';
    const Shape2019BMINICOTINEENDDATE = '2019-05-01';
}

class Shape2019WeightManagementNicotineProgramView extends PlaceHolderComplianceView
{
    const SHAPE_2018_COACHING_RECORD_ID = 1354;
    public function getStatus(User $user)
    {
        if($user->hasAttribute(Attribute::COACHING_END_USER)) {
            $program = ComplianceProgramRecordTable::getInstance()->find(self::SHAPE_2018_COACHING_RECORD_ID);

            if($program) {
                $coachingProgram = $program->getComplianceProgram();
                $coachingProgram->setActiveUser($user);
                $status = $coachingProgram->getStatus();

                if($status->getPoints() >= 20) {
                    return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
                }
            }
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }
}

class Shape2018CompleteScreeningComplianceView  extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {
        $testsRequired = array(
            'cholesterol', 'glucose'
        );

        foreach($testsRequired as $test) {
            if(!isset($array[$test]) || !trim($array[$test])) {
                return false;
            }
        }

        return true;
    }
}

class Shape2019ComplianceProgramAdminReportPrinter extends BasicComplianceProgramAdminReportPrinter
{
//    protected function showUser(User $user)
//    {
//        return !$user->expired();
//    }
}

class Shape2019ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Shape2019WMS2Printer();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new Shape2019ComplianceProgramAdminReportPrinter();

        // Full SSN over last 4
        $printer->setShowUserFields(null, null, null, false, true);
        $printer->setShowUserContactFields(true, null, true);
        $printer->setShowCompliant(null, false, null);
        $printer->setShowText(null, false, null);
        $printer->setShowStatus(null, false, null);
        $printer->setShowPoints(null, false, null);
        $printer->setShowComment(null, false, null);

        $printer->setShowTotals(false);

        $printer->addCallbackField('covered_social_security_number', function (User $user) {
            if($user->getRelationshipType() == Relationship::EMPLOYEE) {
                return $user->getSocialSecurityNumber();
            } else {
                return $user->getEmployeeUser() ? $user->getEmployeeUser()->getSocialSecurityNumber() : '';
            }
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
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done', '/images/shape/lights/notdoneyet_new_color.jpg'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('N/A', '/images/shape/lights/notrequired.jpg')
        )));

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $hraGroup = new ComplianceViewGroup('requirements_hra');

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('HRA (Health Risk Appraisal)<br />');
        $hra->setName('hra');
        $hra->setAttribute('about', '');
        $hra->setStatusSummary(ComplianceStatus::COMPLIANT, 'Can only be completed annually between March 1, 2019 and May 1, 2019.');
        $hra->setAttribute('did_this_link', '/content/i_did_this');

        if (sfConfig::get('app_wms2')) {
            $hra->emptyLinks();
            $hra->addLink(new \Link("My HRA & Results", "/compliance/shape-2018/my-health"));
        }

        $hraGroup->addComplianceView($hra);
        $this->addComplianceViewGroup($hraGroup);


        $tobaccoGroup = new ComplianceViewGroup('requirements_tobacco');

        $tobacco = new Shape2019BMINicotineCardComplianceProgramView(ComplianceStatus::NOT_COMPLIANT);
        $tobacco->setReportName('BMI/Nicotine Card');
        $tobacco->setName('tobacco_bmi');
        $tobacco->setStatusSummary(ComplianceStatus::COMPLIANT, 'Can only be completed annually between March 1, 2019 and May 1, 2019.');
        $tobacco->setAttribute('about', 'BMI/Nicotine card must be completed<br /> at the Fitness Factory or with your<br /> Primary Care Physician.');
        $tobacco->setAttribute('did_this_link', '/content/i_did_this');
        $tobaccoGroup->addComplianceView($tobacco);

        $this->addComplianceViewGroup($tobaccoGroup);


        $physicalGroup = new ComplianceViewGroup('requirements_physical');

        $phye = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $phye->setName('physical');
        $phye->setReportName('Preventative Physical Form');
        $phye->setAttribute('about', 'Fax or mail the Physical Form to Circle Wellness. <a href="https://static.hpn.com/wms2/documents/clients/shape/2018-19_Employee_Physical_Form_new.pdf" target="_blank">Click here</a> for the form.');
        $phye->setAttribute('did_this_link', '/content/i_did_this');
        $phye->setStatusSummary(ComplianceStatus::COMPLIANT, 'Employee Year! Physical must be completed sometime between May 1, 2017 and May 1, 2019.');
        $phye->setEvaluateCallback(array($this, 'physicalIsRequired'));
        $phye->setPreMapCallback(function(ComplianceViewStatus $status, User $user) {
            $startDate = '2017-05-01';
            $endDate = '2019-05-01';

            if(!$status->isCompliant() && !$status->getUsingOverride()) {

                $prevPhysicalView = new CompletePreventionPhysicalExamComplianceView($startDate, $endDate);

                if($prevPhysicalView->getStatus($user)->isCompliant()) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } else {
                    $scrView = new Shape2018CompleteScreeningComplianceView($startDate, $endDate);

                    if($scrView->getStatus($user)->isCompliant()) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    }
                }
            }
        });
        $physicalGroup->addComplianceView($phye);

        $this->addComplianceViewGroup($physicalGroup);


        $diseaseGroup = new ComplianceViewGroup('requirements_disease');

        $requiredUserIds = $this->getDiseaseManagementUserIds();
        $disease = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $disease->setReportName('Care Management');
        $disease->setName('disease_management');
        $disease->setAttribute('about', 'Only required if contacted directly by <br> Priority Health via mail.');
        $disease->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you are required to participate in the Care Management program, you must enroll by October 31, 2018 and meet all requirements by May 1, 2019. Please note that your status light will be updated once at the beginning of November 2018 and weekly beginning in March 2019. All those in Care Management are required to complete a physical with their physician every year regardless of whether it is their year to turn in a form to Circle Wellness.');
        $disease->setPreMapCallback(function(ComplianceViewStatus $status, User $user) use ($requiredUserIds) {
            if(in_array($user->id, $requiredUserIds)) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        });
        $diseaseGroup->addComplianceView($disease);

        $this->addComplianceViewGroup($diseaseGroup);


        $weightGroup = new ComplianceViewGroup('requirements_weight');

        $weightProgram = new Shape2019WeightManagementNicotineProgramView();
        $weightProgram->setReportName('Complete the 2018-19 Wellness Program');
        $weightProgram->setName('completed_program');
        $weightProgram->setAttribute('about', 'Only required if currently enrolled in Wellness Program.');
        $weightProgram->setAttribute('did_this_link', '/content/i_did_this');
        //$weightProgram->setAttribute('did_this_link', 'mailto:ffactory@shape.com');
        $weightProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you are in the Wellness Program, all requirements must be completed by February 28, 2019.');
        $weightGroup->addComplianceView($weightProgram);

        $this->addComplianceViewGroup($weightGroup);


        $wellnessGroup = new ComplianceViewGroup('requirements_wellness');

        $enrollProgram = new Shape2019FamilyWellnessComplianceProgramView($startDate, $endDate);
        $enrollProgram->setReportName('Enroll/Re-enroll in 2019-20 Wellness Program');
        $enrollProgram->setName('enroll_program');
        $enrollProgram->setAttribute('about', 'Only required if BMI is 30 or greater <br /> or you use nicotine products.');
        $enrollProgram->setAttribute('link_add', 'Call Fitness Factory for an appointment.');
        $enrollProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'All participants will show non-compliant until they have completed their BMI/Nicotine card between 3/1/2019 and 5/1/2019. After that, if required, you must attend a group enrollment session in the month of May.');
        $wellnessGroup->addComplianceView($enrollProgram);

        $this->addComplianceViewGroup($wellnessGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $enrollStatus = $status->getComplianceViewStatus('enroll_program');

        if(!$enrollStatus->getAttribute('show_not_required') && !$enrollStatus->getAttribute('show_actual_green')) {
            if($enrollStatus->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                $status->getComplianceViewGroupStatus('requirements_wellness')->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        }
    }


    public function physicalIsRequired(User $user)
    {
        return $user->relationship_type == Relationship::EMPLOYEE;
    }

    public function getDiseaseManagementUserIds()
    {
        return array(
            2726804,
            2858864,
            3122329,
            94391,
            2701343,
            2701372,
            3121948,
            99981,
            99011,
            2727009,
            2858855,
            2674694,
            2968367,
            2967566,
            3123154,
            100121,
            2859012,
            2968403,
            87521,
            3122542,
            2701036,
            2968415,
            2674716,
            2726987,
            3524054,
            2700981,
            114011,
            100411,
            2858744,
            3122119,
            2727014,
            111721,
            2612681,
            3122080,
            2858650,
            2674752,
            2968517,
            87561,
            3526658,
            2724402,
            94511,
            100811,
            3122314,
            3122566,
            3122797,
            106231,
            2859090,
            108561,
            2701146,
            2968571,
            2968580,
            2726947,
            2968598,
            3123319,
            3523981,
            3122170,
            3123505,
            108671,
            2701368,
            2780329,
            2727002,
            115641,
            2780339,
            2780345,
            3122974,
            87641,
            101491,
            2968742,
            89531,
            107331,
            3122638,
            3012965,
            3523787,
            3532703,
            2676341,
            2701278,
            2674818,
            112151,
            92431,
            2968817,
            3007263,
            91661,
            2858854,
            107631,
            2674836,
            2701315,
            107641,
            3523957,
            2967866,
            90891,
            94321,
            2968919,
            107301,
            2780438,
            111781,
            2726935,
            141171,
            3122983,
            2859072,
            2968937,
            3121978,
            89821,
            2700989,
            2674859,
            2780464,
            2969030,
            2969033,
            2701070,
            2701241,
            102681,
            98681,
            102731,
            102771,
            91221,
            2780485,
            89891,
            2858756,
            2859029,
            3122137,
            2780502,
            99391,
            89931,
            112721,
            141281,
            3121999,
            2969141,
            2858646,
            3515511,
            2969174,
            2969177,
            2858978,
            2859086,
            111811,
            2859137,
            2969222,
            3122560,
            2858657,
            3123022,
            103401,
            106811,
            2726963,
            2969267,
            3122869,
            110201,
            110231,
            2701236,
            3138814,
            2674951,
            2780548,
            3123361,
            103591,
            2701380,
            3523962,
            2967863,
            2858946,
            88711,
            103741,
            103761,
            3122002,
            2969402,
            3123232,
            2726785,
            2701002,
            3123529,
            2701133,
            2969444,
            2727021,
            2969456,
            90261,
            2726936,
            114861,
            2701229,
            2675014,
            2858770,
            2726870,
            3122089,
            2780633,
            3523821,
            104501,
            2674837,
            114651,
            3523927,
            2780644,
            99741,
            114711,
            94431,
            2967788,
            104821,
            2969606,
            2859021,
            3524053,
            2675051,
            2859041,
            3123427,
            2701274,
            3122410,
            2858702,
            2858906,
            3123277,
            105101,
            105171,
            107861,
            2616783,
            3122389,
            2726825,
            2780696,
            99541,
            3121963,
            2858711,
            2859067,
            2859157,
            113691,
            90531,
            2700563,
            105471,
            89451,
            105481,
            105591,
            98431,
            111251,
            2858966,
            2675101,
            2726787,
            2859104,
            105711,
            2675107,
            2859002,
            3123211,
            94301,
            2969876,
            112431,
            2969882,
            106801,
            115051,
            111501,
            106041,
            3123223,
            2726904,
            2859087,
            97591,
            2726810,
            2726867
        );
    }
}


class Shape2019WMS2Printer implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $classForStatus = function($status) {
            if ($status == ComplianceStatus::COMPLIANT) {
                return 'success';
            } else if ($status == ComplianceStatus::NA_COMPLIANT) {
                return 'info';
            } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $circle = function($status, $text) use ($classForStatus) {
            $class = $status === 'shape' ? 'shape' : $classForStatus($status);
            ob_start();
            ?>
            <div class="circle-range">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>">
                    <div style="font-size: 1.2em; line-height: 1.5em;"><?php echo $text ?></div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };


        $groupTable = function(ComplianceViewGroupStatus $group) use ($classForStatus) {
            ob_start();
            ?>

            <table class="details-table">
                <thead>
                <tr>
                    <th class="requirementscolumn">Item</th>
                    <th class="comment">Details</th>
                    <th class="status">Status</th>
                    <th class="text-center"></th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1 ?>
                <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $view = $viewStatus->getComplianceView() ?>
                    <?php $class = $classForStatus($viewStatus->getStatus()) ?>
                    <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                        <td class="requirementscolumn">
                            <strong><?php echo $view->getReportName() ?></strong>
                            <br/>
                            <?php echo $view->getAttribute('about') ?>
                            <br/>
                            <?php echo implode(' ', $view->getLinks()) ?>
                        </td>
                        <td class="comment">
                            <?php echo $view->getStatusSummary(ComplianceStatus::COMPLIANT) ?>
                        </td>
                        <td class="status">
                            <img src="<?php echo $viewStatus->getLight() ?>" alt="" class="status-<?php echo $viewStatus->getStatus() ?>" />
                        </td>
                        <td class="links text-center">
                            <?php if(!$viewStatus->isCompliant() && $view->getAttribute('did_this_link')) : ?>
                                <a href="<?php echo $view->getAttribute('did_this_link') ?>">I did this</a>
                            <?php endif ?>
                            <?php if(!$viewStatus->isCompliant() && $linkAdd = $view->getAttribute('link_add')) : ?>
                                <?php echo $linkAdd ?>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php $i++ ?>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classForStatus, $groupTable) {
            ob_start();

            $class = $classForStatus($group->getStatus());
            ?>
            <tr class="picker">
                <td class="name">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="points <?php echo $class ?>">
                    Status
                </td>
                <td></td>
            </tr>
            <tr class="details">
                <td colspan="3">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        };



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
                width: 80px;
            }
            #activities .status {
                vertical-align: top;
                text-align: center;
                width: 80px;
            }

            #activities .links {
                vertical-align: top;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
                width: 600px;
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
                text-align: center;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
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

            .details-table .requirementscolumn {
                width: 280px;
                vertical-align: text-top;
            }

            .details-table .comment {
                width: 280px;
                vertical-align: text-top;
            }

            .details-table tr td{
                padding-bottom: 1em;
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
                font-size: 1.4em;
            }

            .circle-range-inner {
                padding: 22% 10%;
            }

            @media (max-width: 1199px) {
                .circle-range-inner {
                    font-size: 1.2rem;
                }
            }

            @media (max-width: 991px) {
                .circle-range-inner {
                    font-size: 2rem;
                }
            }

            #total_points {
                display: inline-block;
                height: 100%;
                margin-top: 46%;
                font-size: 1.3em;
            }

            #header-text {
                font-weight: bold;
                font-size: 1.1em;
            }
        </style>
        <div class="row">
            <div class="col-md-12">
                <h1>Annual Requirements <small>PROGRAM</small></h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div id="header-text">
                    <p style="font-weight:bold">
                        The 2019-2020 SFW Annual Requirements must be <span style="color:red;">completed</span>
                        between March 1, 2019 and May 1, 2019. Please view the list of requirements below; each requirement
                        shows related details and has a light indicator showing if it has been completed, is incomplete,
                        or is not required of you this year. Failure to complete all requirements will result in an
                        insurance premium increase of approximately $1,500 per year, per individual. Couples can be charged
                        approximately $3,000 if both are non-compliant.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12" <?php echo !sfConfig::get('app_wms2') ? 'style="margin-top: 250px;"' : '' ?>>
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <div class="row">
                                <div class="col-md-4">
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-xs-8 col-xs-offset-2 col-md-12 col-md-offset-0">
                                            <?php echo $circle(
                                                $status->getStatus(),
                                                '2019-2020<br/>Annual<br/>Requirements'
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <tbody>
                    <?php echo $tableRow('HRA (Health Risk Appraisal)', $status->getComplianceViewGroupStatus('requirements_hra')) ?>
                    <?php echo $tableRow('BMI/Nicotine Card', $status->getComplianceViewGroupStatus('requirements_tobacco')) ?>
                    <?php echo $tableRow('Preventative Physical Form', $status->getComplianceViewGroupStatus('requirements_physical')) ?>
                    <?php echo $tableRow('Care Management', $status->getComplianceViewGroupStatus('requirements_disease')) ?>
                    <?php echo $tableRow('Complete the 2018-19 Wellness Program', $status->getComplianceViewGroupStatus('requirements_weight')) ?>
                    <?php echo $tableRow('Enroll/Re-enroll in 2019-20 Wellness Program', $status->getComplianceViewGroupStatus('requirements_wellness')) ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script type="text/javascript">
            $(function() {
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

                $('.details-table .name').width($('.picker td.name').first().width());
                $('.details-table .points').width($('.picker td.points').first().width());
                $('.details-table .links').width($('.picker td.pct').first().width());

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
                    .attr('src', '/images/shape/lights/notdoneyet_new_color.jpg');
                $('.view-enroll_program .status-<?php echo ComplianceStatus::COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/done.jpg');
                $('.view-enroll_program .status-<?php echo ComplianceStatus::NOT_COMPLIANT ?>')
                    .attr('src', '/images/shape/lights/notdoneyet_new_color.jpg');
                <?php endif ?>
            });
        </script>
        <?php
    }
}