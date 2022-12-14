<?php
class GVT2015BFMapper extends ComplianceStatusPointMapper
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

class GVT2015ComplyWithBodyFatBMIScreeningTestComplianceView extends ComplyWithBodyFatBMIScreeningTestComplianceView
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

class GVT2015ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new GVT2015Printer();
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
        $printer->setShowUserContactFields(null, null, true);

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
            $data['Total Points (Ignoring Alternatives)'] = $status->getAttribute('total_points_ignoring_alternatives');
            $data['Total Points (Last Year)'] = $status->getAttribute('prior_status')->getPoints();
            $data['Point Difference'] = $data['Total Points'] - $data['Total Points (Last Year)'];
            $data['Monthly Surcharge'] = $status->getComment();
            $data['Level']  = $status->getAttribute('level');
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

        $hraDate = '2014-04-01';



        // Build the core group
        $preventionEventGroup = new ComplianceViewGroup('Prevention Event');

        $hraScreeningView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
        $hraScreeningView->setName('hra_screening');
        $hraScreeningView->setReportName('Screening Program');
        $preventionEventGroup->addComplianceView($hraScreeningView);

        $privateConsultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $privateConsultationView->setName('consultation');
        $privateConsultationView->setReportName('Consultation');
        $preventionEventGroup->addComplianceView($privateConsultationView);

        $this->addComplianceViewGroup($preventionEventGroup);



        // Used for override to force compliant
        $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $forceCompliant->setName('force_compliant');
        $forceCompliant->setReportName('Force Overall Compliant');
        $preventionEventGroup->addComplianceView($forceCompliant);

        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements');
        $biometricsGroup->setPointsRequiredForCompliance(0);

        $smokingView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Tobacco');
        $smokingView->setName('tobacco');
        $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $biometricsGroup->addComplianceView($smokingView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setIndicateSelfReportedResults(false);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '??? 140/??? 90');


        $biometricsGroup->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $triglView->setIndicateSelfReportedResults(false);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '??? 200');


        $biometricsGroup->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setName('glucose');
        $glucoseView->setIndicateSelfReportedResults(false);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-64 or 100-125');
        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '??? 49 or ??? 126');


        $biometricsGroup->addComplianceView($glucoseView);

        $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($programStart, $programEnd);
        $cholesterolView->setIndicateSelfReportedResults(false);
        $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
        $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '??? 99 or ??? 200');
        $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '??? 89 or ??? 241');


        $biometricsGroup->addComplianceView($cholesterolView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
        $totalHDLRatioView->setIndicateSelfReportedResults(false);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');

        $biometricsGroup->addComplianceView($totalHDLRatioView);

        $bodyFatBMIView = new GVT2015ComplyWithBodyFatBMIScreeningTestComplianceView($programStart, $programEnd);
        $bodyFatBMIView->setIndicateSelfReportedResults(false);
        $bodyFatBMIView->setBmiView($this->getBmiView($programStart, $programEnd));
        $bodyFatBMIView->setBodyFatView($this->getBodyFatView($programStart, $programEnd));
        $bodyFatBMIView->setComplianceStatusPointMapper(new GVT2015BFMapper());
        $bodyFatBMIView->setReportName('Better of Body Fat or BMI');
        $bodyFatBMIView->setName('bf_bmi');
        $bodyFatBMIView->setUsePoints(true);


        $aliasBloodPressure = 'alt_bloodpressure';
        $aliasTriglycerides = 'alt_triglycerides';
        $aliasBloodSugar = 'alt_bloodsugar';
        $aliasCholesterolRatio = 'alt_cholesterolratio';
        $aliasBmi = 'alt_bmi';

        $aliasAll = array(
            $aliasBloodPressure => array(clone $bloodPressureView),
            $aliasTriglycerides => array(clone $triglView),
            $aliasBloodSugar => array(clone $glucoseView),
            $aliasCholesterolRatio => array(clone $cholesterolView, clone $totalHDLRatioView),
            $aliasBmi => array(clone $bodyFatBMIView)
        );

        $this->configureViewForElearningAlternative($bloodPressureView, $aliasBloodPressure, $aliasAll);
        $this->configureViewForElearningAlternative($triglView, $aliasTriglycerides, $aliasAll);
        $this->configureViewForElearningAlternative($glucoseView, $aliasBloodSugar, $aliasAll);
        $this->configureViewForElearningAlternative($cholesterolView, $aliasCholesterolRatio, $aliasAll);
        $this->configureViewForElearningAlternative($totalHDLRatioView, $aliasCholesterolRatio, $aliasAll);
        $this->configureViewForElearningAlternative($bodyFatBMIView, $aliasBmi, $aliasAll);

        $biometricsGroup->addComplianceView($bodyFatBMIView);

        $this->addComplianceViewGroup($biometricsGroup);
    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $alias, $allAliases)
    {
        // These user ids are exempt from having to complete all aliases

        $userIdsToIgnore = array(
            2639872,
            2639885,
            2639902,
            2639913,
            2639926,
            2639938,
            2639980,
            2639989,
            2640002,
            2640016,
            2640036,
            2640037,
            2640041,
            2640118,
            2640120,
            2640214,
            2640226,
            2640249,
            2640291,
            2640323,
            2640394,
            2640421,
            2640945,
            2640958,
            2641007,
            2641013,
            2641029,
            2641040,
            2641044,
            2641047,
            2641053,
            2641059,
            2641062,
            2641084,
            2641088,
            2641113,
            2641135,
            2641147,
            2641158,
            2641162,
            2641185,
            2641361,
            2643132,
            2644380,
            2654811,
            2684105,
            2684114,
            2684225,
            2684251,
            2685253,
            2711637,
            2711960,
            2711978,
            2711980,
            2721110,
            2748218,
            2748226,
            2748231,
            2748262,
            2756431,
            2764406,
            2771798,
            2787111,
            2787154,
            2787171,
            2787446,
            2787471
        );

        static $callCache = array(); // Keep the latest user's data around

        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use(&$callCache, $alias, $allAliases, $userIdsToIgnore) {
            $view = $status->getComplianceView();

            if($view->getStartDate('Y-m-d') != '2014-04-01') {
                return;
            }

            if(!isset($callCache['user_id']) || $user->id != $callCache['user_id']) {
                $callCache = array('user_id' => $user->id, 'aliases' => array());

                foreach($allAliases as $testAlias => $testsToCheck) {
                    foreach($testsToCheck as $testToCheck) {
                        if($testToCheck->getMappedStatus($user)->getPoints() < $testToCheck->getMaximumNumberOfPoints()) {
                            $callCache['aliases'][] = $testAlias;

                            break;
                        }
                    }
                }
            }

            if($status->getAttribute('has_result') && $status->getPoints() < $view->getMaximumNumberOfPoints()) {
                $aliasesToUse = in_array($user->id, $userIdsToIgnore) ?
                    array($alias) : $callCache['aliases'];

                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), $aliasesToUse);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());

                $view->addLink(new Link('Alternative', "/content/9420?action=lessonManager&tab_alias={$alias}"));

                $elearningStatus = $elearningView->getStatus($user);

                if($elearningStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $originalPoints = $status->getPoints();

                    $status->setComment(sprintf('%s<br/>(Alternative Used;<br/>Otherwise %s points)', $status->getComment(), $status->getPoints()));
                    $status->setPoints($view->getMaximumNumberOfPoints());
                    $status->setAttribute('extra_points', $view->getMaximumNumberOfPoints() - $originalPoints);
                }
            }
        });
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $thisRequirements = $status->getComplianceViewGroupStatus('Requirements');
        $thisPrevention = $status->getComplianceViewGroupStatus('Prevention Event');
        $thisTobacco = $status->getComplianceViewStatus('Tobacco');

        if(!$this->evaluateOverall) {
            return;
        }

        $hasBiometricOverride = false;

        if ($status->getComplianceViewStatus('force_compliant')->isCompliant()) {
            $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);
            $thisPrevention->setStatus(ComplianceStatus::COMPLIANT);
            $thisTobacco->setStatus(ComplianceStatus::COMPLIANT);

            $status->setStatus(ComplianceStatus::COMPLIANT);
            $hasBiometricOverride = true;
        }

        $biometricPoints = 0;

        $extraPoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                if(($viewExtraPoints = $viewStatus->getAttribute('extra_points')) !== null) {
                    $extraPoints += $viewExtraPoints;
                }
            }
        }

        $status->setAttribute('total_points_ignoring_alternatives', $status->getPoints() - $extraPoints);


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

        $priorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($status->getUser(), '2014-06-01', '2014-12-31');
        $priorPriorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($status->getUser(), '2013-06-24', '2013-12-31');

        $status->setAttribute('prior_status', $priorStatus);
        $status->setAttribute('prior_prior_status', $priorPriorStatus);

        if(!$thisRequirements->isCompliant()) {
            $priorRequirements = $priorStatus->getComplianceViewGroupStatus('Requirements');

            if($biometricPoints >= 10 || $thisRequirements->getPoints() - 2 >= $priorRequirements->getPoints()) {
                $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);

                parent::evaluateAndStoreOverallStatus($status);
            }
        }

        if($thisPrevention->getComplianceViewStatus('hra_screening')->getStatus() == ComplianceViewStatus::COMPLIANT
            && $thisPrevention->getComplianceViewStatus('consultation')->getStatus() == ComplianceViewStatus::COMPLIANT
            && $thisRequirements->getStatus() == ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

        if($status->isCompliant()) {
            $status->setComment('$0');
        } elseif($thisPrevention->isCompliant() && $thisTobacco->isCompliant()) {
            $status->setComment('$30');
        } elseif($thisPrevention->isCompliant()) {
            $status->setComment('$45');
        } else {
            $status->setComment('$90');
        }

        if($thisPrevention->getComplianceViewStatus('hra_screening')->getStatus() == ComplianceViewStatus::COMPLIANT
            && $thisPrevention->getComplianceViewStatus('consultation')->getStatus() == ComplianceViewStatus::COMPLIANT) {

            if($biometricPoints >= 10 || $thisRequirements->getPoints() - 2 >= $priorRequirements->getPoints()) {
                $status->setAttribute('level', 'one');
            } elseif($biometricPoints >= 6) {
                $status->setAttribute('level', 'two');
            } elseif($biometricPoints >= 1) {
                $status->setAttribute('level', 'three');
            }
        }
    }

    public function getPriorYearBiometricsComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        $program = new GVT2015ComplianceProgram($startDate, $endDate);

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

    public $evaluateOverall = true;
}

class GVT2015Printer extends CHPStatusBasedComplianceProgramReportPrinter
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
        <p>The associates of Greenville Technology have made great strides in improving their health through
            participation in the wellness program. We want to continue to move forward in helping you to achieve
            your  optimal health by incentivizing efforts toward wellness in 2015. While the requirements for
            eligibility in the health plan have remained the same, you will notice that your report card has
            changed for 2015. Our goal is to provide additional point opportunities for our associates who are
            taking strides toward better health.</p>
        <p>
            <ul>
                <li>Being Tobacco free is now worth 2 points (previously 1 point)</li>
                <li>Maintaining an optimal Triglyceride level is worth 2 points (previously 1 point)</li>
                <li>Maintaining an optimal Glucose level is worth 2 points (previously 1 point)</li>
                <li>Those with a less than optimal body fat percentage or BMI (Body Mass Index)
                    have an opportunity to receive points for levels that last year yielded no points.</li>
                <li><strong>Points for improvement</strong>- In keeping with our goal of rewarding improvement
                    at all levels, we are also introducing a 2 point improvement opportunity.  If you complete
                    the HRA, Screening and Private Consultation and are unable to reach Level 1 with 10 or more
                    points, improving 2 total points over the previous year will allow you to obtain the Level
                    1 incentive.*</li>
            </ul>
        </p>
        <p>*Because this is our first year with the new report card and the point values for 2014
            are not the same, your 2014 results have been populated in the new report card for the purpose of
            calculating improvement in 2015.
            <a href="compliance_programs?id=431" target="_blank">Click here to view your 2014 results in the new
                    report card.</a>
        </p>

        <p><strong>By completing the following steps in 2015, associates will be eligible for a premium
                reduction in their health plan effective 01/01/2016.</strong>
        </p>
        <p><strong>Step 1</strong>- Complete your HRA, on-site health screening and private one-on-one consultation.
            Screenings will be scheduled beginning in June at all three locations. Onsite consultations will follow.
        </p>
        <p><strong>Step 2</strong>- The number of points you earn on your 2015 report card will determine your premium
            reduction for 2016.</p>
        <ul>
            <li>Level 1: Score 10-15 points*</li>
            <li>Level 2: Score 6-9 points</li>
            <li>Level 3: Score 1-5 points</li>
        </ul>
        <p>*If you have a medical reason for not being able to reach one or more of the requirements, you
            can contact Kamara Means in Human Resources to discuss a reasonable alternative.
        </p>

        <div style="border:3px solid #88a7bd; color:#284f88">
            <div style="font-weight:bold; margin:10px; text-align: center;">SPOUSES are encouraged to participate in the screenings and consultations in 2015!</div>

            <div style="margin: 10px;">Spouses who complete an HRA, onsite screening and a private phone consultation will receive a $100 gift card.
                Schedules will be available online in June for spouses to sign up for a screening.</div>
        </div>


    <?php
    }

    public function printClientNote()
    {
        ?>

    <?php
    }

    protected function printCustomRows(ComplianceProgramStatus $status)
    {
        ?>
        <tr class="headerRow finalHeaderRow">
            <th colspan="4">Program Status</th>
            <td colspan="2">Your Points</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="4">
                1. Your Status
                <br/><small><strong>- Complete Screening Program and Consultation<br/>- Obtain 10+ points or improve by 2 points since last year</strong></small>
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
        $priorPriorStatus = $status->getAttribute('prior_prior_status');


        $escaper = new hpn\common\text\Escaper;

        $lastYearRequirementsGroup = $priorStatus->getComplianceViewGroupStatus('Requirements');
        $priorLastYearRequirementsGroup = $priorPriorStatus->getComplianceViewGroupStatus('Requirements');

        // We're going to hack in the Tobacco result into the Requirements
        // group.

        ?>
        <script type="text/javascript">
            $(function() {
                $('.group-tobacco td').each(function() {
                    if($(this).html() == 'Requirements') {
                        $(this).html('Point Values');
                    }
                });

                $('.view-tobacco.statusRow4 .summary').html(
                    'Negative'
                );

                $('.view-tobacco.statusRow1 .summary').html(
                    'Positive'
                );

                <?php foreach($lastYearRequirementsGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                $('<?php echo $escaper->escapeJs(".view-{$viewStatus->getComplianceView()->getName()} .resource") ?>').append(
                        '<br/><small><strong>Last Year:</strong><br/>Result: <?php echo $escaper->escapeJs("{$viewStatus->getComment()} <br/>Points: {$viewStatus->getPoints()}") ?>' + '</small>'
                );
                <?php endforeach ?>


                $('.headerRow.totalRow th').first().html(
                    '2015 Point Totals:<br/><br/>' +
                    '2014 Point Totals:<br/>'
                );

                <?php $maxPriorPoints = $escaper->escapeJs($lastYearRequirementsGroup->getComplianceViewGroup()->getMaximumNumberOfPoints()) ?>
                <?php $noAlternativesPoints = $escaper->escapeJs($status->getAttribute('total_points_ignoring_alternatives')) ?>

                $('.headerRow.totalRow td:eq(2)').first().append(
                    '<?php echo "<br/><br/>$maxPriorPoints" ?>'
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
            .view-force_compliant {
                display:none;
            }

            .requirements {
                text-align: center;
            }

            .headerRow.group-requirements {
                border-bottom:2px solid #D7D7D7;
            }

            .finalHeaderRow {
                background-color:#69AAFF !important;
            }
        </style>
        <?php
        parent::printReport($status);
    }
}
