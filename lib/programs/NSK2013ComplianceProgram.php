<?php

class NSK2013ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new NSK2013Printer();
        $printer->showResult(true);

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserLocation(true);
        $printer->setShowUserFields(true, true, false, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
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
                }
            }

            $data['Total Points'] = $status->getPoints();
            $data['Total Points (Last Year)'] = $status->getAttribute('prior_status')->getPoints();
            $data['Point Difference'] = $data['Total Points'] - $data['Total Points (Last Year)'];
            $data['Monthly Savings'] = $status->getComment();
            $data['Has Biometric Override'] = $status->getAttribute('has_biometric_override') ? 'Yes' : 'No';
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $hraDate = '2013-04-01';

        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');       

        $hpa = new CompleteHRAComplianceView($hraDate, $programEnd);
        $hpa->setReportName('Health Risk Assessment (HRA)');
        $hpa->setName('hra');
        $hpa->emptyLinks();
        $preventionEventGroup->addComplianceView($hpa);        
        
        $scr = new CompleteScreeningComplianceView($programStart, $programEnd);
        $scr->setReportName('Screening Program');
        $scr->setName('screening');
        $scr->emptyLinks();
        $preventionEventGroup->addComplianceView($scr);        

        $viewHra = new ViewViewBasedHpaReportComplianceView($hraDate, $programEnd, array($hpa, $scr));
        $viewHra->setReportName('View HRA/Screening Report');
        $viewHra->setName('view_hra');
        $viewHra->setStatusSummary(ComplianceStatus::COMPLIANT, 'View HRA/Screening Report when available');
        $preventionEventGroup->addComplianceView($viewHra);

        $this->addComplianceViewGroup($preventionEventGroup);

        $tobaccoGroup = new ComplianceViewGroup('Tobacco');

        $smokingView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco');
        $tobaccoGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($tobaccoGroup);



        $biometricsGroup = new ComplianceViewGroup('Requirements');
        $biometricsGroup->setPointsRequiredForCompliance(0);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setIndicateSelfReportedResults(false);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');

        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setIndicateSelfReportedResults(false);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');

        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setIndicateSelfReportedResults(false);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-64 or 100-125');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');

        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setIndicateSelfReportedResults(false);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');

        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setIndicateSelfReportedResults(false);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new NSK2013ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setIndicateSelfReportedResults(false);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setComplianceStatusPointMapper(new NSK2013BFMapper());
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        if(!$this->evaluateOverall) {
            return;
        }

        $thisRequirements = $status->getComplianceViewGroupStatus('Requirements');

        $thisPrevention = $status->getComplianceViewGroupStatus('Prevention Event');

        $thisTobacco = $status->getComplianceViewGroupStatus('Tobacco');

        $hasBiometricOverride = false;

        $biometricPoints = 0;

        foreach($thisRequirements->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getUsingOverride()) {
                $hasBiometricOverride = true;

                if(($originalStatus = $viewStatus->getAttribute('original_status', false)) !== false) {
                    $viewStatus->setStatus($originalStatus);
                }

                if(($originalPoints = $viewStatus->getAttribute('original_points', false)) !== false) {
                    $viewStatus->setPoints($originalPoints);
                }

                if(($originalComment = $viewStatus->getAttribute('original_comment', false)) !== false) {
                    $viewStatus->setComment($originalComment);
                }
            }

            $biometricPoints += $viewStatus->getPoints();
        }

        $status->setAttribute('has_biometric_override', $hasBiometricOverride);

        $thisRequirements->setPoints($biometricPoints);

        if($hasBiometricOverride || $biometricPoints >= 11) {
            if($thisRequirements->getStatus() != ComplianceStatus::COMPLIANT) {
                $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);

                parent::evaluateAndStoreOverallStatus($status);
            }
        } else {
            if($thisRequirements->getStatus() != ComplianceStatus::NOT_COMPLIANT) {
                $thisRequirements->setStatus(ComplianceStatus::NOT_COMPLIANT);

                parent::evaluateAndStoreOverallStatus($status);
            }
        }

        $priorStatus = $status->getComplianceProgram()->get2012BiometricsComplianceViewStatuses($status->getUser());

        $status->setAttribute('prior_status', $priorStatus);

        if(!$thisRequirements->isCompliant()) {
            $priorStatus = $status->getComplianceProgram()->get2012BiometricsComplianceViewStatuses($status->getUser());

            $priorRequirements = $priorStatus->getComplianceViewGroupStatus('Requirements');

            if($thisRequirements->getPoints() - 2 >= $priorRequirements->getPoints()) {
                $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);

                parent::evaluateAndStoreOverallStatus($status);
            }
        }

        if($status->isCompliant()) {
            $status->setComment('$90');
        } elseif($thisPrevention->isCompliant() && $thisTobacco->isCompliant()) {
            $status->setComment('$60');
        } elseif($thisPrevention->isCompliant()) {
            $status->setComment('$45');
        } else {
            $status->setComment('$0');
        }
    }

    public function get2012BiometricsComplianceViewStatuses(User $user)
    {
        $program = $this->cloneForEvaluation('2012-05-15', '2012-12-31');

        // Change name of the cloned views so that new overrides don't apply to
        // last year's results.

        foreach($program->getComplianceViews() as $cV) {
            $cV->setUseOverrides(false);
        }

        $program->evaluateOverall = false;

        $program->setActiveUser($user);

        return $program->getStatus();
    }

    private function getBmiView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBMIScreeningTestComplianceView'
        );

        $view->setIndicateSelfReportedResults(false);

        $view->addRange(4, 18.5, 25.0, 'E');
        $view->addRange(3, 17.0, 30.0, 'E');
        $view->addRange(2, 15.0, 35.0, 'E');
        $view->addRange(1, 13.0, 40.0, 'E');
        $view->setStatusSummary(0, '&lt;13 or &gt;40');


        return $view;
    }

    private function getBodyFatView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBodyFatScreeningTestComplianceView'
        );

        $view->setIndicateSelfReportedResults(false);

        $view->addRange(4, 2.0, 18.0, 'M');
        $view->addRange(3, 0.0, 25.0, 'M');
        $view->addRange(2, 0.0, 30.0, 'M');
        $view->addRange(1, 0.0, 35.0, 'M');
        $view->addDefaultStatusSummaryForGender(0, 'M', '&gt;35');


        $view->addRange(4, 12.0, 25.0, 'F');
        $view->addRange(3, 0.0, 32.0, 'F');
        $view->addRange(2, 0.0, 37.0, 'F');
        $view->addRange(1, 0.0, 42.0, 'F');
        $view->addDefaultStatusSummaryForGender(0, 'F', '&gt;42');




        return $view;
    }

    protected $evaluateOverall = true;
}

class NSK2013BFMapper extends ComplianceStatusPointMapper
{
    public function __construct()
    {

    }

    public function getMaximumNumberOfPoints()
    {
        return 4;
    }

    public function getPoints($status)
    {
        return $status;
    }

    private $mapping;
}

class NSK2013ComplyWithBodyFatBMIScreeningTestComplianceView extends ComplyWithBodyFatBMIScreeningTestComplianceView
{
    public function allowPointsOverride()
    {
        return true;
    }

    public function getStatusSummary($status)
    {
        return sprintf(
            'Body Fat: %s, BMI: %s',
            $this->bodyFatView->getStatusSummary($status),
            $this->bmiView->getStatusSummary($status)
        );
    }
}

class NSK2013Printer extends CHPStatusBasedComplianceProgramReportPrinter
{
    protected function pointBasedViewStatusMatchesMapping($viewStatus, $mapping)
    {
        if($viewStatus->getComplianceView()->getName() == 'bf_bmi') {
            return $viewStatus->getPoints() == $mapping;
        } else {
            return parent::pointBasedViewStatusMatchesMapping($viewStatus, $mapping);
        }
    }

    protected function getStatusMappings(ComplianceView $view)
    {
        $mappings = parent::getStatusMappings($view);

        if($view->getName() == 'bf_bmi') {
            return array(
                4 => $mappings[ComplianceStatus::COMPLIANT],
                3 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                2 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                1 => $mappings[ComplianceStatus::PARTIALLY_COMPLIANT],
                0 => $mappings[ComplianceStatus::NOT_COMPLIANT]
            );
        } else {
            return $mappings;
        }
    }

    public function printClientMessage()
    {
        ?>
        <p>Below is your 2013 Incentive Report Card.</p>
        <p><a href="/content/program_overview">CLICK HERE </a> to view the full details of the 2013 program.</p>

    <?php
    }
    
    public function printClientNote()
    {
        ?>
    
    <p>If you are struggling to reach these goals, please consider participating in the Circle Wellness
        Consultation/Coaching program. <a href="/content/130023?action=Consultation">Click here</a> for more details.</p>
    <?php
    }

    protected function printCustomRows(ComplianceProgramStatus $status)
    {
        ?>
        <tr class="headerRow finalHeaderRow">
            <th colspan="4">Program Status</th>
            <td colspan="2">Your Status</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="4">
                1. Your Status
                <br/><small><strong>- Complete Prevention Event and Tobacco<br/>- Obtain 11+ points or improve by 2 points since last year</strong></small>
            </td>
            <td class="status" colspan="2">
                <img src="<?php echo $status->getLight() ?>" alt="" class="light" />
            </td>
        </tr>
        <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $priorStatus = $status->getAttribute('prior_status');

        if(!$status->getComplianceViewStatus('hra')->isCompliant() || !$status->getComplianceViewStatus('screening')->isCompliant()) {
            $status->getComplianceViewStatus('view_hra')->getComplianceView()->emptyLinks();
        }

        $escaper = new hpn\common\text\Escaper;

        $lastYearRequirementsGroup = $priorStatus->getComplianceViewGroupStatus('Requirements');

        // We're going to hack in the Tobacco result into the Requirements
        // group.

        ?>
        <script type="text/javascript">
            $(function() {
                $('.group-tobacco td').each(function() {
                   if($(this).html() == 'Requirements') {
                       $(this).html('Result');
                   }
                });

                $('.view-comply_with_cotinine_screening_test td.requirements').html(
                    '<?php echo $escaper->escapeJs($status->getComplianceViewStatus('comply_with_cotinine_screening_test')->getComment()) ?>'
                );

                <?php foreach($lastYearRequirementsGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                    $('<?php echo $escaper->escapeJs(".view-{$viewStatus->getComplianceView()->getName()} .resource") ?>').append(
                        '<br/><small><strong>Last Year:</strong><br/>Result: <?php echo $escaper->escapeJs("{$viewStatus->getComment()} <br/>Points: {$viewStatus->getPoints()}") ?>' + '</small>'
                    );
                <?php endforeach ?>

                $('.headerRow.totalRow th').first().html(
                        '2013 Point Totals:<br/><br/><small><strong>2012 Point Totals:</strong>'
                );

                $('.headerRow.totalRow td:eq(2)').first().append(
                    '<br/><br/><?php echo $escaper->escapeJs($lastYearRequirementsGroup->getComplianceViewGroup()->getMaximumNumberOfPoints()) ?>'
                );

                $('.headerRow.totalRow td:eq(3)').first().append(
                    '<br/><br/><?php echo $escaper->escapeJs($lastYearRequirementsGroup->getPoints()) ?>'
                );

                <?php if($status->getAttribute('has_biometric_override')) : ?>
                    $('.finalHeaderRow').before('<tr class="headerRow"><th colspan="7" style="text-align:center;">Medical exception form received</th></tr>');
                <?php endif ?>
            });
        </script>
        <style type="text/css">
            .requirements {
                text-align: center;
            }

            .headerRow.group-requirements {
                border-bottom:2px solid #D7D7D7;
            }

            .finalHeaderRow {
                background-color:#69AAFF !important;
            }
            
            .view-bf_bmi.statusRow0 {
                color: black;
            }
        </style>
        <?php
        parent::printReport($status);
    }
}