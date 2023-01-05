<?php

class SBC2018ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowStatus(false, false, false);
        $printer->setShowText(true, true, true);
        $printer->setShowUserContactFields(null, null, true);

        $printer->addCallbackField('location', function (User $user) {
            return $user->getLocation();
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('required', 'My Requirements');

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Complete HRA Questionnaire');
        $hra->setName('hra');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HRA', sfConfig::get('app_wms2') ? '/compliance/sbc-2016/my-health' : '/content/989'));
        $required->addComplianceView($hra);

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setName('screening');
        $screening->setReportName('Complete Biometric Screening');
        $screening->emptyLinks();
        $screening->addLink(new Link('Results', '/content/989'));
        $required->addComplianceView($screening);

        $coaching = new AttendAppointmentComplianceView($startDate, $endDate);
        $coaching->bindTypeIds(array(11, 21, 46));
        $coaching->setName('coaching');
        $coaching->setReportName('Complete Private Consultation');
        $required->addComplianceView($coaching);

        $program = $this;

        $coachingOverall = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingOverall->setName('coaching_overall');
        $coachingOverall->setReportName('Complete 4 Coaching Sessions (if applicable)');
        $required->addComplianceView($coachingOverall);

        $coachingSession1 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession1->setName('coaching_session1');
        $coachingSession1->setReportName('Session 1');
        $coachingSession1->setPostEvaluateCallback(function($status, $user) use ($program) {
            $coachingStatus = $program->getCoachingData($user);

            if(isset($coachingStatus['session1'])
                && isset($coachingStatus['session1']['contact'])
                && isset($coachingStatus['session1']['total_minutes'])
                && $coachingStatus['session1']['total_minutes'] > 0) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
                $status->setComment($coachingStatus['session1']['date']);
            }
        });
        $required->addComplianceView($coachingSession1);

        $coachingSession2 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession2->setName('coaching_session2');
        $coachingSession2->setReportName('Session 2');
        $coachingSession2->setPostEvaluateCallback(function($status, $user) use ($program) {
            $coachingStatus = $program->getCoachingData($user);

            if(isset($coachingStatus['session2'])
                && isset($coachingStatus['session2']['contact'])
                && isset($coachingStatus['session2']['total_minutes'])
                && $coachingStatus['session2']['total_minutes'] > 0) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
                $status->setComment($coachingStatus['session2']['date']);
            }
        });
        $required->addComplianceView($coachingSession2);

        $coachingSession3 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession3->setName('coaching_session3');
        $coachingSession3->setReportName('Session 3');
        $coachingSession3->setPostEvaluateCallback(function($status, $user) use ($program) {
            $coachingStatus = $program->getCoachingData($user);

            if(isset($coachingStatus['session3'])
                && isset($coachingStatus['session3']['contact'])
                && isset($coachingStatus['session3']['total_minutes'])
                && $coachingStatus['session3']['total_minutes'] > 0) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
                $status->setComment($coachingStatus['session3']['date']);
            }
        });
        $required->addComplianceView($coachingSession3);

        $coachingSession4 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession4->setName('coaching_session4');
        $coachingSession4->setReportName('Session 4');
        $coachingSession4->setPostEvaluateCallback(function($status, $user) use ($program) {
            $coachingStatus = $program->getCoachingData($user);

            if(isset($coachingStatus['session4'])
                && isset($coachingStatus['session4']['contact'])
                && isset($coachingStatus['session4']['total_minutes'])
                && $coachingStatus['session4']['total_minutes'] > 0) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
                $status->setComment($coachingStatus['session4']['date']);
            }
        });
        $required->addComplianceView($coachingSession4);

        $this->addComplianceViewGroup($required);


        $biometricsGroup = new ComplianceViewGroup('Requirements', 'My Biometric Results Points');
        $biometricsGroup->setPointsRequiredForCompliance(0);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->overrideSystolicTestRowData(0, 0, 139.999, 140);
        $bloodPressureView->overrideDiastolicTestRowData(0, 0, 89.999, 90);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $this->configureViewForElearningAlternative($bloodPressureView, 177);
        $biometricsGroup->addComplianceView($bloodPressureView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->overrideTestRowData(0, 0, 124.999, 125);
        $this->configureViewForElearningAlternative($glucoseView, 218);
        $biometricsGroup->addComplianceView($glucoseView);

        $totalLDLView = new ComplyWithLDLScreeningTestComplianceView($startDate, $endDate);
        $totalLDLView->setReportName('LDL Cholesterol');
        $totalLDLView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalLDLView->overrideTestRowData(0, 0, 129.999, 158.999);
        $this->configureViewForElearningAlternative($totalLDLView, 184);
        $biometricsGroup->addComplianceView($totalLDLView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $endDate);
        $bodyFatBMIView->setIndicateSelfReportedResults(false);
        $bodyFatBMIView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $bodyFatBMIView->overrideBMITestRowData(0, 0, 29.999, 30);
        $bodyFatBMIView->overrideBodyFatTestRowData(0, 0, 31.999, 32, 'F');
        $bodyFatBMIView->overrideBodyFatTestRowData(0, 0, 24.999, 25, 'M');
        $bodyFatBMIView->setReportName('Better of BMI or Body Fat');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);
        $this->configureViewForElearningAlternative($bodyFatBMIView, 1118);
        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $tobaccoView = new ComplyWithSmokingHRAQuestionComplianceView($startDate, $endDate);
        $tobaccoView->setReportName('Tobacco Status');
        $tobaccoView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $tobaccoView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'Non Smoker');
        $tobaccoView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Non Smoker');
        $this->configureViewForElearningAlternative($tobaccoView, 186);
        $biometricsGroup->addComplianceView($tobaccoView);


        $this->addComplianceViewGroup($biometricsGroup);
    }


    protected function configureViewForElearningAlternative(ComplianceView $view, $lessonId)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($lessonId) {
            $view = $status->getComplianceView();

            $lessonQuizMapper = array(
                186     => 3931,
                1118    => 4769,
                184     => 3734,
                218     => 3535,
                177     => 3572
            );

            $quizId = isset($lessonQuizMapper[$lessonId]) ? $lessonQuizMapper[$lessonId] : null;

            if(!$status->isCompliant() && $quizId) {

                $elearningView = new CompleteELearningLessonComplianceView($view->getStartDate(), $view->getEndDate(), new ELearningLesson_v2($lessonId));
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());

                $view->addLink(new Link('Alternative', "/content/9420?action=displayQuiz&category_url=L2NvbnRlbnQvOTQyMD9hY3Rpb249bGVzc29uTWFuYWdlciZ0YWJfYWxpYXM9YWxsX2xlc3NvbnM%3D&category=QWxsIExlc3NvbnM%3D&quiz_id={$quizId}"));

                $elearningStatus = $elearningView->getStatus($user);

                if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                    $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                }
            }
        });
    }

    public function getCoachingData(User $user)
    {
        $coachingStatus = array();

        $coachingStartDate = '2018-01-01';
        $coachingEndDate = '2018-10-01';

        $session = CoachingSessionTable::getInstance()->findMostRecentSessionForUser($user);
        if(is_object($session)) {
            $reports = CoachingReportTable::getInstance()->findActiveReports($session);

            foreach($reports as $report) {
                if(is_object($report)) {
                    if($report->getDate('Y-m-d') < $coachingStartDate || $report->getDate('Y-m-d') > $coachingEndDate) continue;

                    $reportEdit = CoachingReportEditTable::getInstance()->findMostRecentEdit($report);

                    if(is_object($reportEdit)) {
                        $recordedDocument = $reportEdit->getRecordedDocument();
                        $recordedFields = $recordedDocument->getRecordedDocumentFields();

                        $coachingData = array();
                        $isContact = true;
                        foreach($recordedFields as $recordedField) {
                            $name = $recordedField->getFieldName();
                            $value = $recordedField->getFieldValue();
                            if(empty($value)) continue;

                            if(($name == 'communication_type' && $value == 'attempt') || ($name == 'total_minutes' && $value <= 1)) $isContact = false;
                            $coachingData[$name] = $value;
                        }

                        if($isContact) {
                            if(!isset($coachingStatus['session1'])) {
                                $coachingStatus['session1'] = $coachingData;
                            } elseif(!isset($coachingStatus['session2'])) {
                                $coachingStatus['session2'] = $coachingData;
                            } elseif(!isset($coachingStatus['session3'])) {
                                $coachingStatus['session3'] = $coachingData;
                            } elseif(!isset($coachingStatus['session4'])) {
                                $coachingStatus['session4'] = $coachingData;
                            }
                        }
                    }
                }
            }
        }

        return $coachingStatus;
    }



    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SBC2018ComplianceProgramReportPrinter();
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');
        $sessionOverallStatus = $requiredGroupStatus->getComplianceViewStatus('coaching_overall');
        $session1Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session1');
        $session2Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session2');
        $session3Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session3');
        $session4Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session4');

        $coachingCompliant = 0;
        if($session1Status->getStatus() == ComplianceStatus::COMPLIANT) $coachingCompliant++;
        if($session2Status->getStatus() == ComplianceStatus::COMPLIANT) $coachingCompliant++;
        if($session3Status->getStatus() == ComplianceStatus::COMPLIANT) $coachingCompliant++;
        if($session4Status->getStatus() == ComplianceStatus::COMPLIANT) $coachingCompliant++;

        if($coachingCompliant >= 3) {
            $sessionOverallStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

    }
}

class SBC2018ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
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

    public function printReport(ComplianceProgramStatus $status)
    {
        $escaper = new hpn\common\text\Escaper;

        $user = $status->getUser();

        $requirementsStatus = $status->getComplianceViewGroupStatus('Requirements');

        $thisYearTotalPoints = $requirementsStatus->getPoints();

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $that = $this;

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

        $textForStatus = function($status) {
            if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
                return 'Done';
            } else if ($status->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                $coachingViews = array(
                    'coaching_overall',
                    'coaching_session1',
                    'coaching_session2',
                    'coaching_session3',
                    'coaching_session4'
                );

                if($status->getComment() == 'Not Required') {
                    return 'Not Required';
                } elseif(in_array($status->getComplianceView()->getName(), $coachingViews)) {
                    return 'Pending';
                } else {
                    return 'Not Required';
                }

            } else {
                return 'Not Done';
            }
        };


        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor, $classForStatus, $textForStatus, $that) {
            ob_start();

            if ($group->getComplianceViewGroup()->getName() == 'Requirements') : ?>
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>Test</th>
                        <th class="text-center">Target</th>
                        <th class="text-center">Point Values</th>
                        <th class="text-center">Your Points</th>
                        <th class="text-center">Results</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $view = $viewStatus->getComplianceView() ?>
                        <?php $warningLabel = $view->getStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                        <?php $class = $that->getClass($view); ?>
                        <?php $j = 0 ?>
                            <tr>
                                <td>
                                    <?php echo $view->getReportName() ?>
                                    <br/>
                                    <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                        <div><?php echo $link->getHTML() ?></div>
                                    <?php endforeach ?>
                                </td>

                                <td class="text-center"><?php echo $view->getStatusSummary(ComplianceStatus::NOT_COMPLIANT) ?></td>
                                <td class="text-center">
                                    <?php echo $view->getStatusPointMapper()->getPoints(ComplianceStatus::NOT_COMPLIANT) ?>
                                </td>
                                <td class="text-center">
                                    <span class="label label-<?php echo $class[ComplianceStatus::NOT_COMPLIANT] ?>"><?php echo $viewStatus->getPoints() ?></span>
                                </td>
                                <td class="text-center">
                                        <span class="label label-<?php echo $class[ComplianceStatus::NOT_COMPLIANT] ?>"><?php echo $viewStatus->getComment() ?></span>
                                </td>
                            </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php else : ?>
                <table class="details-table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th class="points">Status</th>
                        <th class="points">deadline</th>
                        <th class="text-center">Links</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1 ?>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $class = $classForStatus($viewStatus->getStatus()) ?>
                        <?php $statusText = $textForStatus($viewStatus) ?>
                        <tr class="<?php echo 'view-', $viewStatus->getComplianceView()->getName() ?>">
                            <td class="name">
                                <?php echo $i ?>.
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            </td>
                            <td class="points <?php echo $class ?>" <?php echo $class == 'info' ? 'style="background-color: #CCC;"' : ''?>><?php echo $statusText ?>
                            </td>
                            <td><?php echo $viewStatus->getComplianceView()->getAttribute('deadline') ?></td>
                            <td class="links text-center">
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                    <div><?php echo $link->getHTML() ?></div>
                                <?php endforeach ?>
                            </td>
                        </tr>
                        <?php $i++ ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
            <?php
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $classForStatus, $textForStatus, $groupTable) {
            ob_start();

            $numOfViews = 0;
            $numOfCompliant = 0;
            foreach($group->getComplianceViewStatuses() as $viewStatus) {
                $numOfViews++;
                if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numOfCompliant++;
            }

            if ($group->getComplianceViewGroup()->getName() == 'Requirements' || $group->getComplianceViewGroup()->getName() == 'wellness_campaign') : ?>

                <?php $points = $group->getPoints(); ?>
                <?php $target = $group->getComplianceViewGroup()->getMaximumNumberOfPoints(); ?>
                <?php $pct = $points / $target; ?>
                <?php $class = $classFor($pct); ?>
                <tr class="picker">
                    <td class="name">
                        <?php echo $name ?>
                        <div class="triangle"></div>
                    </td>
                    <td class="points <?php echo $group->getComplianceViewGroup()->getName() != 'Requirements' && $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? 'success' : '' ?> ">
                        <strong><?php echo $target ?></strong><br/>
                        points
                    </td>
                    <td class="points <?php echo $group->getComplianceViewGroup()->getName() != 'Requirements' && $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? $class : ''?>">
                        <strong><?php echo $points ?></strong><br/>
                        points
                    </td>
                    <td class="pct">
                        <div class="pgrs">
                            <div class="bar <?php echo $group->getComplianceViewGroup()->getName() != 'Requirements' && $group->getComplianceViewGroup()->getName() != 'wellness_campaign' ? $class : '' ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                        </div>
                    </td>
                </tr>
                <tr class="details">
                    <td colspan="4">
                        <?php echo $groupTable($group) ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php $pct = $numOfCompliant / $numOfViews; ?>
                <?php $class = $classForStatus($group->getStatus()); ?>
                <?php $statusText = $textForStatus($group); ?>
                <tr class="picker">
                    <td class="name">
                        <?php echo $name ?>
                        <div class="triangle"></div>
                    </td>
                    <td class="points success">Done
                    </td>
                    <td class="points <?php echo $class ?>"><?php echo $statusText ?>
                    </td>
                    <td class="pct">
                        <div class="pgrs">
                            <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                        </div>
                    </td>
                </tr>
                <tr class="details">
                    <td colspan="4">
                        <?php echo $groupTable($group) ?>
                    </td>
                </tr>

            <?php endif ?>

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

            .pct {
                width: 30%;
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

            .details-table .name {
                width: 300px;
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

            .point-totals th {
                text-align: right;
                padding-right: 10px;
            }

            .point-totals td {
                text-align: center;
            }

            .hide {
                display: none;
            }

            #option_one_title, #option_two_title {
                background:none!important;
                border:none;
                padding:0!important;
                font: inherit;
                color: #2196f3;
                cursor: pointer;
            }

        </style>

        <div class="row">
            <div class="col-md-12">
                <div id="more-info">
                    <div class="row">
                        <div class="col-md-12">
                            <p>
                                Employees will receive a 5% Medical Premium Reduction effective 1/1/2019 if the following requirements are
                                completed by BOTH employee and spouse (if applicable) by 10/1/2018.
                            </p>

                            <p>
                                <ul>
                                    <li>Complete HRA Questionnaire (Employee & Spouse)</li>
                                    <li>Complete Biometric Health Screening (Employee & Spouse)- onsite screenings will take place in 2018 (offsite alternatives will be available)</li>
                                    <li>Private Consultation (Employee & Spouse)- both onsite and phone consultations will be offered</li>
                                    <li>Complete 4 Health Coaching sessions if applicable (Employee & Spouse)- coaching will be offered to high risk participants if eligible after the screening process</li>
                                    <li>Group Level Seminars (Employee only)- offered on-site or on-line</li>
                                    <li>Meet Biometric Screening Criteria in Report Card below (Employee only)- Reasonable Alternatives will be provided if you can't obtain recommended criteria</li>
                                </ul>
                            </p>
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
                    <thead>
                    <tr>
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Status</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('My Requirements', $status->getComplianceViewGroupStatus('required')) ?>
                    <?php if($user->getRelationshipType() == Relationship::EMPLOYEE) : ?>
                    <?php echo $tableRow('My Biometric Results Points', $status->getComplianceViewGroupStatus('Requirements')) ?>
                    <?php endif ?>
                    </tbody>

                    <tr class="point-totals">
                        <th>2017 Point Totals</th>
                        <td></td>
                        <td><?php echo $thisYearTotalPoints ?></td>
                    </tr>
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


                $('.view-coaching_session1').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 1</span>');
                $('.view-coaching_session2').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 2</span>');
                $('.view-coaching_session3').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 3</span>');
                $('.view-coaching_session4').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 4</span>');

                $('.view-employee_participation').children(':eq(0)').html('5. Employee Compliance');


                $optionOneTitle = $('#option_one_title');
                $optionOneContent = $('#option_one_content');

                $optionTwoTitle = $('#option_two_title');
                $optionTwoContent = $('#option_two_content');

                $optionOneTitle.click(function() {
                    $optionOneContent.toggleClass('hide');
                });

                $optionTwoTitle.click(function() {
                    $optionTwoContent.toggleClass('hide');
                });
            });
        </script>
        <?php
    }
}
