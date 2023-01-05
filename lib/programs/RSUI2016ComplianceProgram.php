<?php

class RSUI2016ComplianceProgramReportPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->page_heading = 'My Wellness Report Card - To be completed in 2016';
    }

    public function printReport(\ComplianceProgramStatus $status)
    {
        $elearningStatus = $status->getComplianceViewStatus('additional_elearning');

        $physicalStatus = $status->getComplianceViewStatus('physical_alternative');
    ?>

        <script type="text/javascript">
            $(function() {
                $('.headerRow.group-elearning').children(':eq(2)').html('Your Points');
                $('.view-additional_elearning').children('.links').after('<td></td><td style="text-align: center;"><?php echo $elearningStatus->getPoints() ?></td>');
                $('.headerRow.group-elearning').hide();
                $('.view-additional_elearning').hide();
                $('.headerRow.group-physical').hide();
                $('.view-physical_alternative').hide();
                $('.totalRow.group-health-score').children(':eq(0)').html('Total Health Score');
                $('.totalRow.group-health-score').children(':eq(1)').html('<img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>');
                $('.phipTable').after('<p>The Health Score is based on three biometric measures - Blood Pressure, Cholesterol, and Glucose</p>');
                $('.totalRow.group-health-score').before('<tr class="statusRow newViewRow view-physical_alternative"><td class="resource">4. Annual Physical Alternative </td><td colspan="2"></td> <td class="status"><?php echo $physicalStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td><td class="status"><?php echo $physicalStatus->getPoints() ?></td><td colspan="2" class="empty"></td></tr>')
            });
        </script>

    <?php

        parent::printReport($status);
    }

    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>
    <style type="text/css">
        .phipTable .resource {
            width:240px;
        }

        .phipTable .links {
            width:240px;
        }

        .phipTable .requirements {
            display:none;
        }

        .phipTable tr td.status, .phipTable tr td.links {
            vertical-align:top;
        }

        p {
            margin-top: 10px;;
            margin-bottom: 10px;
        }
    </style>

    <p>Hi <?php echo $_user->getFirstName() ?>,</p>
    <p>
        To earn the 2017 Preferred Medical Rate, you and your spouse/domestic partner (if applicable) are required
        to complete the following during 2016:</p>
    <ul>
        <li>Biometric Screening</li>
        <li>Health Risk Appraisal</li>
        <li>Earn a minimum Health Score of 250 points</li>
    </ul>

    <p>To learn more about the Preferred Medical Rate requirements, click <a href="/resources/7583/Earning_the_2017_Preferred_Medical_Rate.040716.pdf">here.</a></p>

    <p style="text-align: center; font-weight:bold;">**The deadline for completing all requirements is 10/14/2016.**</p>

    <p>
        Below is your report card and total Health Score.  You must complete the Biometric Screening and
        Health Risk Appraisal.  In addition, you must meet the minimum Health Score requirement. Green
        lights will appear on your Report Card when that requirement has been fulfilled.
    </p>

    <p>
        <em>Please note – this is your personal report card and does not reflect the status of your spouse/domestic partner.</em>
    </p>

    <?php
    }

    public function printClientNote()
    {
        ?>
    <br/>
    <br/>

    <?php
    }
}

class RSUI2016ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new RSUI2016ComplianceProgramReportPrinter();
        $printer->showResult(true);

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        return new RSUI2016ComplianceProgramAdminReportPrinter();
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', 'Required for Preferred Medical Rate - To be completed in 2016');

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Complete Screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Results', '/content/989'));
        $screeningView->setFilter(function($row) {
           return isset($row['creatinine']) && (bool) trim($row['creatinine']);
        });
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take / View Results', '/content/989'));
        //$hraView->addLink(new Link('Historical Results', '/content/301716'));
        $requiredGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($requiredGroup);

        $physicalGroup = new ComplianceViewGroup('Physical');

        $physicalAlternativeView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT, 0);
        $physicalAlternativeView->setName('physical_alternative');
        $physicalAlternativeView->setReportName('Annual Physical Alternative');
        $physicalAlternativeView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 50, 0, 0));
        $physicalAlternativeView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setPoints(100);
            }
        });
        $physicalGroup->addComplianceView($physicalAlternativeView);

        $this->addComplianceViewGroup($physicalGroup);

        $elearningGroup = new ComplianceViewGroup('Elearning');

        $program = $this;
        $elearningView = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT, 0);
        $elearningView->setName('additional_elearning');
        $elearningView->setReportName('Complete 2 additional e-Learning Lessons');
        $elearningView->setMaximumNumberOfPoints(500);
        $elearningGroup->addComplianceView($elearningView);

        $this->addComplianceViewGroup($elearningGroup);
//        $this->configureViewsForAdditionalElearning($elearningView, $programStart, $programEnd);

        $pointsGroup = new ComplianceViewGroup('Health Score');
        $pointsGroup->setMaximumNumberOfPoints(300);
        $pointsGroup->setPointsRequiredForCompliance(250);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 50, 0, 0));
        $bloodPressureView->overrideSystolicTestRowData(null, null, 120, 139);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 80, 89);
        $pointsGroup->addComplianceView($bloodPressureView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->overrideTestRowData(49, 65, 99, 125);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 50, 0, 0));
        $pointsGroup->addComplianceView($glucoseView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 50, 0, 0));
        $totalHDLRatioView->overrideTestRowData(0, 0, 4, 5);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '4.1-5');
        $pointsGroup->addComplianceView($totalHDLRatioView);

        $this->addComplianceViewGroup($pointsGroup);

        $this->configureViewForElearningAlternative($bloodPressureView, 'blood_pressure_alt_2015');
        $this->configureViewForElearningAlternative($glucoseView, 'glucose_alt_2015');
        $this->configureViewForElearningAlternative($totalHDLRatioView, 'cholesterol_alt_2015');
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $healthScoreStatus = $status->getComplianceViewGroupStatus('Health Score');

        $physicalScoreStatus = $status->getComplianceViewGroupStatus('Physical');

        $ineligibleLessonIDs = array();
        foreach($healthScoreStatus->getComplianceViewStatuses() as $healthViewStatus) {
            if($healthViewStatus->getAttribute('extra_points') && $healthViewStatus->getAttribute('lessons_used_for_alternatives')) {
                foreach($healthViewStatus->getAttribute('lessons_used_for_alternatives') as $alternativeLesson) {
                    $ineligibleLessonIDs[] = $alternativeLesson;
                }
            }
        }

        $additionalElearningStatus = $status->getComplianceViewStatus('additional_elearning');
        $view = $additionalElearningStatus->getComplianceView();

        $elearningView = new CompleteELearningLessonsComplianceView($programStart, $programEnd, null, 2, $ineligibleLessonIDs);
        $elearningView->setPointsPerLesson(250);
        $elearningView->setMaximumNumberOfPoints(500);
        $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());

        $elearningStatus = $elearningView->getStatus($user);

        $points = $elearningStatus->getPoints() < 500 ? $elearningStatus->getPoints() : 500;
        $additionalElearningStatus->setPoints($points);
        $additionalElearningStatus->setStatus($elearningStatus->getStatus());
        $view->addLink(new Link('e-Learning Center', "/content/9420?action=lessonManager&tab_alias=all_lessons"));

        $healthScoreStatus->setPoints(min($healthScoreStatus->getComplianceViewGroup()->getMaximumNumberOfPoints(), $healthScoreStatus->getPoints() + $physicalScoreStatus->getPoints()));
        if($healthScoreStatus->getPoints() >= $healthScoreStatus->getComplianceViewGroup()->getPointsRequiredForCompliance()) {
            $healthScoreStatus->setStatus(ComplianceViewGroupStatus::COMPLIANT);
        }

        $requiredStatus = $status->getComplianceViewGroupStatus('required');
        if($requiredStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT
            && $healthScoreStatus->getStatus() == ComplianceViewGroupStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
    }

    protected function configureViewForElearningAlternative(DateBasedComplianceView $view, $alias)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use(&$callCache, $alias) {
            $view = $status->getComplianceView();

            if($status->getAttribute('has_result') && $status->getPoints() < $view->getMaximumNumberOfPoints()) {
                if($status->getPoints() == 0) {
                    $numberRequired = 2;
                } else {
                    $numberRequired = 1;
                }

                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), array($alias));
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());
                $elearningView->setNumberRequired($numberRequired);

                $view->addLink(new Link('Alternative', "/content/9420?action=lessonManager&tab_alias={$alias}"));

                $elearningStatus = $elearningView->getStatus($user);

                if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT ||
                    $elearningStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                    $originalPoints = $status->getPoints();

                    $status->setStatus($elearningStatus->getStatus());
                    $status->setAttribute('extra_points', $status->getPoints() - $originalPoints);
                    $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));

                    $completedlessons = $elearningStatus->getAttribute('lessons_completed');

                    $ignoredLessons = array();
                    if($numberRequired == 2) {
                        if(isset($completedlessons[0])) {
                            $ignoredLessons[] =  $completedlessons[0];
                        }

                        if(isset($completedlessons[1])) {
                            $ignoredLessons[] =  $completedlessons[1];
                        }
                    } else {
                        if(isset($completedlessons[0])) {
                            $ignoredLessons[] =  $completedlessons[0];
                        }
                    }

                    if(count($ignoredLessons) > 0) {
                        $status->setAttribute('lessons_used_for_alternatives', $ignoredLessons);
                    }
                }
            }
        });
    }

    protected function configureViewsForAdditionalElearning($view, $programStart, $programEnd)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $viewStatus, User $user) use ($programStart, $programEnd) {
            $view = $viewStatus->getComplianceView();
            $ineligibleLessonIDs = array();

            $elearningView = new CompleteELearningLessonsComplianceView($programStart, $programEnd, null, 2, $ineligibleLessonIDs);
            $elearningView->setPointsPerLesson(250);
            $elearningView->setMaximumNumberOfPoints(500);
            $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());

            $elearningStatus = $elearningView->getStatus($user);

            $points = $elearningStatus->getPoints() < 500 ? $elearningStatus->getPoints() : 500;
            $viewStatus->setPoints($points);
            $viewStatus->setStatus($elearningStatus->getStatus());
            $view->addLink(new Link('e-Learning Center', "/content/9420?action=lessonManager&tab_alias=all_lessons"));
        });
    }
}

class RSUI2016ComplianceProgramAdminReportPrinter implements ComplianceProgramAdminReportPrinter
{
    public function printAdminReport(ComplianceProgramReport $report, $output)
    {
        $employees = array();

        $spouses = array();

        foreach($report as $status) {
            $user = $status->getUser();

            $extraPoints = 0;
            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                        $extraPoints += $viewExtraPoints;
                    }
                }
            }

            $fields = array(
                'ID'                     => $user->id,
                'SSN'                    => $user->getSocialSecurityNumber(true),
                'First Name'             => $user->first_name,
                'Last Name'              => $user->last_name,
                'Screening - Compliant'  => $status->getComplianceViewStatus('complete_screening')->isCompliant() ? 'Yes' : 'No',
                'HRA - Compliant'        => $status->getComplianceViewStatus('complete_hra')->isCompliant() ? 'Yes' : 'No',
                'Annual Physical Alternative' => $status->getComplianceViewStatus('physical_alternative')->getPoints(),
                'Additional Elearning - Points' => $status->getComplianceViewStatus('additional_elearning')->getPoints(),
                'Elearning Alternative points'    => $extraPoints,
                'Health Score'           => $status->getComplianceViewGroupStatus('Health Score')->getPoints(),
                'Overall - Compliant'    => $status->isCompliant() ? 'Yes' : 'No'
            );

            if($user->relationship_type == \Relationship::EMPLOYEE) {
                $employees[$user->id] = $fields;
            } elseif($user->relationship_type == \Relationship::SPOUSE && $user->relationship_user_id) {
                $spouses[$user->relationship_user_id] = $fields;
            }

            if($user->id != $report->getUser()->id) {
                $user->delink(false);
            }
        }

        $csvRows = array();

        foreach($employees as $employeeId => $employeeData) {
            $csvRow = array();

            foreach($employeeData as $fieldName => $fieldValue) {
                $csvRow["EE {$fieldName}"] = $fieldValue;
            }

            $spouse = isset($spouses[$employeeId]) ? $spouses[$employeeId] : null;

            foreach($employeeData as $fieldName => $fieldValue) {
                $csvRow["Spouse {$fieldName}"] = $spouse ? $spouse[$fieldName] : '';
            }

            $csvRows[] = $csvRow;
        }

        $i = 0;

        foreach($csvRows as $csvRow) {
            if(!$i) {
                fputcsv($output, array_keys($csvRow));
            }

            fputcsv($output, $csvRow);

            $i++;
        }
    }
}
