<?php

class ShapePrinter2018 extends CHPShapeComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        ?>
        <p style="font-weight:bold">
            The 2018-2019 SFW Annual Requirements must be completed by May 1, 2018. Please view the list
            of requirements below; each requirement shows related details and has a light indicator showing
            if it has been completed, is incomplete, or is not required of you this year. Failure to complete
            all requirements will result in an insurance premium increase of approximately $1,500 per year,
            per individual. Couples can be charged approximately $3,000 if both are non-compliant.
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

class Shape2018BMINicotineCardComplianceProgramView extends PlaceHolderComplianceView
{
    public function getStatus(User $user)
    {
        $records = $user->getDataRecords('goal-tracking-GoalScreeningForm');

        $nicotineUse = false;
        foreach($records as $record) {
            if($record->exists()
                && $record->nicotine_use != ''
                && date('Y-m-d', strtotime($record->getCreationDate())) >= '2018-03-01'
                && date('Y-m-d', strtotime($record->getCreationDate())) <= '2018-05-01') {
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

class Shape2018FamilyWellnessComplianceProgramView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'enroll_program';
    }

    public function getDefaultReportName()
    {
        return 'Enroll/Re-enroll in 2017-2017 Weight Mgt or Nicotine Program';
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
        if($record->participant_agreed && $record->signed_date_of_participant > self::Shape2017BMINICOTINESTARTDATE) {
            $isAgreementSigned = true;
        } else {
            $isAgreementSigned = false;
        }

        $records =  $user->getDataRecords('goal-tracking-GoalScreeningForm');

        foreach($records as $record) {
            if(date('Y-m-d', strtotime($record->getCreationDate())) >= self::Shape2017BMINICOTINESTARTDATE
                && date('Y-m-d', strtotime($record->getCreationDate())) <= self::Shape2017BMINICOTINEENDDATE) {
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
        $appintmentTimes = AppointmentTime::getScheduledAppointments($user, '2018-05-01');

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
                    && date('Y-m-d', strtotime($reportEdit->created_at)) >= self::Shape2017BMINICOTINESTARTDATE
                    && date('Y-m-d', strtotime($reportEdit->created_at)) <= self::Shape2017BMINICOTINEENDDATE) {
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

    const Shape2017BMINICOTINESTARTDATE = '2018-03-01';
    const Shape2017BMINICOTINEENDDATE = '2018-05-01';
}

class Shape2018WeightManagementNicotineProgramView extends PlaceHolderComplianceView
{
    const SHAPE_2017_COACHING_RECORD_ID = 1221;
    public function getStatus(User $user)
    {
        if($user->hasAttribute(Attribute::COACHING_END_USER)) {
            $program = ComplianceProgramRecordTable::getInstance()->find(self::SHAPE_2017_COACHING_RECORD_ID);

            if($program) {
                $coachingProgram = $program->getComplianceProgram();
                $coachingProgram->setActiveUser($user);
                $status = $coachingProgram->getStatus();
                $onsiteStatus = $status->getComplianceViewStatus('consultation_onsite');
                $consultationGroupStatus = $status->getComplianceViewGroupStatus('consultation');


                if($status->getPoints() >= 20
                    && $onsiteStatus->getStatus() == ComplianceViewStatus::COMPLIANT
                    && $consultationGroupStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT) {
                    return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
                }
            }
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }
}

class ShapeCompleteScreeningComplianceView2018  extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {
        $testsRequired = array(
            'cholesterol', 'hdl', 'glucose', 'diastolic', 'systolic'
        );

        foreach($testsRequired as $test) {
            if(!isset($array[$test]) || !trim($array[$test])) {
                return false;
            }
        }

        return true;
    }
}

class Shape2018ComplianceProgramAdminReportPrinter extends BasicComplianceProgramAdminReportPrinter
{
//    protected function showUser(User $user)
//    {
//        return !$user->expired();
//    }
}

class Shape2018ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Shape2018WMS2Printer();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new Shape2018ComplianceProgramAdminReportPrinter();

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
        $hra->setStatusSummary(ComplianceStatus::COMPLIANT, 'Can only be completed annually between March 1, 2018 and May 1, 2018.');
        $hra->setAttribute('did_this_link', '/content/i_did_this');

        if (sfConfig::get('app_wms2')) {
            $hra->emptyLinks();
            $hra->addLink(new \Link("My HRA & Results", "/compliance/shape-2018/my-health"));
        }

        $hraGroup->addComplianceView($hra);
        $this->addComplianceViewGroup($hraGroup);


        $tobaccoGroup = new ComplianceViewGroup('requirements_tobacco');

        $tobacco = new Shape2018BMINicotineCardComplianceProgramView(ComplianceStatus::NOT_COMPLIANT);
        $tobacco->setReportName('BMI/Nicotine Card');
        $tobacco->setName('tobacco_bmi');
        $tobacco->setStatusSummary(ComplianceStatus::COMPLIANT, 'Can only be completed annually between March 1, 2018 and May 1, 2018.');
        $tobacco->setAttribute('about', 'BMI/Nicotine card must be completed<br /> at the Fitness Factory or with your<br /> Primary Care Physician.');
        $tobacco->setAttribute('did_this_link', '/content/i_did_this');
        $tobaccoGroup->addComplianceView($tobacco);

        $this->addComplianceViewGroup($tobaccoGroup);


        $physicalGroup = new ComplianceViewGroup('requirements_physical');

        $phye = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $phye->setName('physical');
        $phye->setReportName('Preventative Physical Form');
        $phye->setAttribute('about', 'Fax or mail the Physical Form to Circle Wellness. <a href="https://static.hpn.com/wms2/documents/clients/shape/2017-18_Spouse_Physical_Form.pdf" target="_blank">Click here</a> for the form.');
        $phye->setAttribute('did_this_link', '/content/i_did_this');
        $phye->setStatusSummary(ComplianceStatus::COMPLIANT, 'Spouse Year! Physical must be completed sometime between May 1, 2016 and May 1, 2018.');
        $phye->setEvaluateCallback(array($this, 'physicalIsRequired'));
        $phye->setPreMapCallback(function(ComplianceViewStatus $status, User $user) {
            $startDate = '2016-05-01';
            $endDate = '2018-05-01';

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
        $physicalGroup->addComplianceView($phye);

        $this->addComplianceViewGroup($physicalGroup);


        $diseaseGroup = new ComplianceViewGroup('requirements_disease');

        $requiredUserIds = $this->getDiseaseManagementUserIds();
        $disease = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $disease->setReportName('Care Management');
        $disease->setName('disease_management');
        $disease->setAttribute('about', 'Only required if contacted directly by <br> Priority Health via mail.');
        $disease->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you are required to participate in the Care Management program, you must enroll by October 31, 2017 and meet all requirements by May 1, 2018. Please note that your status light will be updated once at the beginning of November 2017 and weekly beginning in March 2018. All those in Care Management are required to have an office visit with their physician between May 1, 2017 and April 30, 2018 regardless of whether it is their year to turn in a form to Circle Wellness.');
        $disease->setPreMapCallback(function(ComplianceViewStatus $status, User $user) use ($requiredUserIds) {
            if(in_array($user->id, $requiredUserIds)) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        });
        $diseaseGroup->addComplianceView($disease);

        $this->addComplianceViewGroup($diseaseGroup);


        $weightGroup = new ComplianceViewGroup('requirements_weight');

        $weightProgram = new Shape2018WeightManagementNicotineProgramView();
        $weightProgram->setReportName('Complete the 2017-2018 Wellness Program');
        $weightProgram->setName('completed_program');
        $weightProgram->setAttribute('about', 'Only required if currently enrolled in Wellness Program.');
        $weightProgram->setAttribute('did_this_link', '/content/i_did_this');
        //$weightProgram->setAttribute('did_this_link', 'mailto:ffactory@shape.com');
        $weightProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'If you are in the Wellness Program, all requirements must be completed by February 28, 2018.');
        $weightGroup->addComplianceView($weightProgram);

        $this->addComplianceViewGroup($weightGroup);


        $wellnessGroup = new ComplianceViewGroup('requirements_wellness');

        $enrollProgram = new Shape2018FamilyWellnessComplianceProgramView($startDate, $endDate);
        $enrollProgram->setReportName('Enroll/Re-enroll in 2018-2019 Wellness Program');
        $enrollProgram->setName('enroll_program');
        $enrollProgram->setAttribute('about', 'Only required if BMI is 30 or greater <br /> or you use nicotine products.');
        $enrollProgram->setAttribute('link_add', 'Call Fitness Factory for an appointment.');
        $enrollProgram->setStatusSummary(ComplianceStatus::COMPLIANT, 'All participants will show non-compliant until they have completed their BMI/Nicotine card between 3/1/2018 and 5/1/2018. After that, if required, you must attend a group enrollment session in the month of May.');
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
        return $user->relationship_type == Relationship::SPOUSE;
    }

    public function getDiseaseManagementUserIds()
    {
        return array(
            87511,
            87521,
            87641,
            87871,
            87921,
            87941,
            88501,
            88711,
            88731,
            88801,
            88831,
            88971,
            89091,
            89291,
            89531,
            89821,
            90051,
            90411,
            90701,
            90891,
            90951,
            90971,
            91551,
            91611,
            91801,
            92151,
            92981,
            93301,
            93461,
            93541,
            93871,
            93961,
            94051,
            94391,
            94461,
            94581,
            94661,
            94961,
            95441,
            96561,
            96671,
            96701,
            97271,
            97621,
            97981,
            98391,
            98471,
            98681,
            98701,
            99011,
            99391,
            99681,
            99851,
            99891,
            100871,
            101351,
            101691,
            102341,
            102351,
            102581,
            102721,
            102971,
            103001,
            103231,
            103341,
            103351,
            103431,
            103591,
            103761,
            103771,
            103861,
            104111,
            104321,
            104721,
            104841,
            104951,
            105101,
            105191,
            105711,
            105761,
            105941,
            106041,
            106811,
            107201,
            107291,
            107331,
            107581,
            107631,
            107671,
            108401,
            108481,
            108561,
            109191,
            109331,
            109521,
            109541,
            110041,
            110251,
            110271,
            110881,
            111581,
            111811,
            111971,
            112351,
            112431,
            112621,
            113191,
            113661,
            113701,
            113721,
            113791,
            113901,
            113941,
            113951,
            113981,
            114111,
            114861,
            114881,
            115521,
            115761,
            115831,
            115981,
            116041,
            141071,
            141091,
            141171,
            141281,
            141291,
            2612681,
            2612726,
            2612754,
            2612761,
            2612772,
            2616783,
            2674685,
            2674686,
            2674715,
            2674716,
            2674769,
            2674796,
            2674813,
            2674836,
            2674850,
            2674851,
            2674860,
            2674874,
            2674929,
            2674951,
            2674957,
            2674996,
            2675014,
            2675079,
            2675129,
            2676351,
            2700562,
            2700925,
            2700927,
            2701014,
            2701035,
            2701036,
            2701046,
            2701064,
            2701070,
            2701077,
            2701100,
            2701113,
            2701133,
            2701182,
            2701229,
            2701344,
            2709151,
            2724388,
            2726829,
            2726904,
            2726907,
            2726991,
            2727007,
            2727027,
            2729964,
            2780265,
            2780295,
            2780329,
            2780377,
            2780404,
            2780445,
            2780461,
            2780464,
            2780482,
            2780501,
            2780548,
            2780578,
            2780604,
            2780605,
            2780626,
            2780644,
            2780693,
            2780705,
            2780730,
            2781972,
            2783164,
            2858702,
            2858704,
            2858713,
            2858714,
            2858728,
            2858736,
            2858744,
            2858770,
            2858790,
            2858804,
            2858806,
            2858810,
            2858827,
            2858854,
            2858861,
            2858863,
            2858905,
            2858908,
            2858915,
            2858978,
            2858997,
            2859002,
            2859007,
            2859017,
            2859019,
            2859051,
            2859055,
            2859063,
            2859067,
            2859072,
            2859083,
            2859086,
            2859104,
            2859114,
            2859157,
            2859159,
            2967461,
            2967533,
            2967866,
            2968265,
            2968268,
            2968277,
            2968316,
            2968355,
            2968424,
            2968598,
            2968748,
            2968817,
            2968901,
            2968916,
            2968988,
            2969030,
            2969090,
            2969123,
            2969171,
            2969177,
            2969210,
            2969222,
            2969237,
            2969402,
            2969423,
            2969444,
            2969549,
            2969672,
            2969816,
            2969894,
            2971349,
            2978840,
            2988176,
            3012965,
            3012968,
            3122230,
            3122314,
            3122974,
            3123040,
            3123076,
            3123103,
            3123343
        );
    }
}


class Shape2018WMS2Printer implements ComplianceProgramReportPrinter
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
                        The 2018-2019 SFW Annual Requirements must be <span style="color:red;">completed</span> by May 1, 2018.
                        Please view the list of requirements below; each requirement shows related details and has a
                        light indicator showing if it has been completed, is incomplete, or is not required of you this year.
                        Failure to complete all requirements will result in an insurance premium increase of approximately
                        $1,500 per year, per individual. Couples can be charged approximately $3,000 if both are non-compliant.
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
                                                '2018-2019<br/>Annual<br/>Requirements'
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
                    <?php echo $tableRow('Complete the 2017-2018 Wellness Program', $status->getComplianceViewGroupStatus('requirements_weight')) ?>
                    <?php echo $tableRow('Enroll/Re-enroll in 2018-2019 Wellness Program', $status->getComplianceViewGroupStatus('requirements_wellness')) ?>
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