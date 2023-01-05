<?php

class NSK2015ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new NSK2015Printer();
        $printer->showResult(true);

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
            $data['Has Biometric Override'] = $status->getAttribute('has_biometric_override') ? 'Yes' : 'No';
            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '2500M');

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $hraDate = '2015-05-01';

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

        // Used for override to force compliant
        $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $forceCompliant->setName('force_compliant');
        $forceCompliant->setReportName('Force Overall Compliant');
        $preventionEventGroup->addComplianceView($forceCompliant);

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

            if($view->getStartDate('Y-m-d') != '2015-05-01') {
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

                    $status->setComment(sprintf('%s<br/>(Alternative Used;<br/>Otherwise %s points; <br/> e-Learning completion date: %s)', $status->getComment(), $status->getPoints(), $elearningStatus->getComment()));
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
        $thisTobacco = $status->getComplianceViewGroupStatus('Tobacco');

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

        $priorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($status->getUser(), '2014-04-01', '2014-12-31');
        $priorPriorStatus = $status->getComplianceProgram()->getPriorYearBiometricsComplianceViewStatuses($status->getUser(), '2013-02-01', '2014-03-01');

        $status->setAttribute('prior_status', $priorStatus);
        $status->setAttribute('prior_prior_status', $priorPriorStatus);

        if(!$thisRequirements->isCompliant()) {
            $priorRequirements = $priorStatus->getComplianceViewGroupStatus('Requirements');

            if($thisRequirements->getPoints() - 2 >= $priorRequirements->getPoints()) {
                $thisRequirements->setStatus(ComplianceStatus::COMPLIANT);

                parent::evaluateAndStoreOverallStatus($status);
            }
        }

        $allCompliant = true;
        foreach($thisPrevention->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getComplianceView()->getName() == 'force_compliant') continue;
            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                $allCompliant = false;
            }
        }

        foreach($thisTobacco->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                $allCompliant = false;
            }
        }

        if($thisRequirements->getStatus() != ComplianceStatus::COMPLIANT) {
            $allCompliant = false;
        }

        if($allCompliant) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        }

        $thisPreventionCompliant = true;
        foreach($thisPrevention->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getComplianceView()->getName() == 'force_compliant') continue;
            if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT) {
                $thisPreventionCompliant = false;
            }
        }

        if($this->isNewHire($status->getUser())) {
            $status->setComment('$0');
        }if($status->isCompliant()) {
            $status->setComment('$0');
        } elseif($thisPreventionCompliant && $thisTobacco->isCompliant()) {
            if($this->isSpouse($status->getUser())) {
                $status->setComment('No Financial Impact for Biometric Results');
            } else {
                $status->setComment('$30');
            }
        } elseif($thisPreventionCompliant) {
            $status->setComment('$45');
        } else {
            $status->setComment('$90');
        }
    }

    public function getPriorYearBiometricsComplianceViewStatuses(User $user, $startDate, $endDate)
    {
        $program = $this->cloneForEvaluation($startDate, $endDate);

        // Change name of the cloned views so that new overrides don't apply to
        // last year's results.

        foreach($program->getComplianceViews() as $cV) {
            $cV->setUseOverrides(false);
        }

        $program->evaluateOverall = false;

        $program->setActiveUser($user);

        return $program->getStatus();
    }

    public function isSpouse(User $user)
    {
        return $user->getRelationshipType() == 2 ? true : false;
    }

    public function isNewHire(User $user)
    {
        $groupNewHireMapper = array(
            'Bennington'    => '2015-06-01',
            'Ann Arbor'     => '2015-06-15',
            'Liberty'       => '2015-07-13',
            'Plainfield'    => '2015-07-27',
            'Clarinda'      => '2015-08-04',
            'Franklin'      => '2015-08-10',
            'Dyersburg'     => '2015-08-24'
        );

        $groups = GroupTable::getInstance()->createQuery('g')
            ->innerJoin('g.users u')
            ->innerJoin('g.groupType gt')
            ->andWhere('u.id = ?', $user->id)
            ->andWhere('gt.id = 2')
            ->execute();

        foreach($groups as $group) {
            if(isset($groupNewHireMapper[$group->getName()])
                && $user->getHiredate() > $groupNewHireMapper[$group->getName()]) {
                return true;
            }
        }

        return false;
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
    const RECORD_ID = 454;
}

class NSK2015Printer extends CHPStatusBasedComplianceProgramReportPrinter
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
        <div id="old_reports">
            <p>
                <a href="/compliance_programs/index?id=210">
                    View your 2012 Program
                </a>
            </p>
            <p>
                <a href="/compliance_programs/index?id=269">
                    View your 2013 Program
                </a>
            </p>
            <p>
                <a href="/compliance_programs/index?id=358">
                    View your 2014 Program
                </a>
            </p>
        </div>
        <p>Below is your 2015 Incentive Report Card.</p>
        <p id="overview"><a href="/content/1094employees2015">CLICK HERE </a> to view the full details of the 2015 program.</p>
        <p id="alternative_statement"><span style="color:red">IF YOU HAVE “ALTERNATIVE” LINKS IN THE BIOMETRIC SECTION OF YOUR REPORT CARD, YOU MUST COMPLETE
                ALL LESSONS WITHIN ALL ALTERNATIVE LINKS FOR YOUR POINTS TO POPULATE IN YOUR REPORT CARD.</span></p>

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
        $isSpouse = $status->getComplianceProgram()->isSpouse($status->getUser());
        $relatedUserStatus = $status->getComplianceProgram()->getRelatedUserComplianceStatus($status->getUser());

        ?>
        <tr class="headerRow finalHeaderRow">
            <th colspan="4">Program Status</th>
            <td colspan="2">Your Status</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="4">
                1. Your Status
                <br/><small><strong>- Complete HRA, Screening and test negative for cotinine<br/>
                        <?php echo !$isSpouse ? '- Obtain 11+ points or improve by 2 points since last year' : '' ?></strong></small>
            </td>
            <td class="status" colspan="2">
                <img src="<?php echo $status->getLight() ?>" alt="" class="light" />
            </td>
        </tr>
        <?php if($isSpouse || $status->getComplianceProgram()->getRelatedUser($status->getUser())) : ?>
        <tr>
            <td colspan="4">
                1. <?php echo $isSpouse ? "Employee Status " : "Your Spouse's Status" ?>
            </td>
            <td class="status" colspan="2" id="related_user_status">
                <img src="<?php echo $relatedUserStatus->getLight() ?>" alt="" class="light" />
            </td>
        </tr>
        <?php
            endif;
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $priorStatus = $status->getAttribute('prior_status');
        $priorPriorStatus = $status->getAttribute('prior_prior_status');
        $isSpouse = $status->getComplianceProgram()->isSpouse($status->getUser());


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
                       $(this).html('Result');
                   }
                });

                $('.view-comply_with_cotinine_screening_test td.requirements').html(
                    '<?php echo $escaper->escapeJs($status->getComplianceViewStatus('comply_with_cotinine_screening_test')->getComment()) ?>'
                );

                <?php if($isSpouse) : ?>
                $('#old_reports').hide();
                $('#alternative_statement').html(
                    '<span style="color:red">In 2015, spouses are not required to complete the lessons within the alternative links on their report cards, however, you may complete them if you choose. Lessons contain valuable information on how to reach the optimal screening levels.</span>'
                );

                $('#overview').html('<a href="/content/1094spouses2015">CLICK HERE </a> to view spouse requirements for the 2015 program.');

                $('.headerRow.totalRow th').first().html(
                        '2015 Point Totals:<br/><br/>'
                );


                <?php else : ?>

                <?php foreach($lastYearRequirementsGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                $('<?php echo $escaper->escapeJs(".view-{$viewStatus->getComplianceView()->getName()} .resource") ?>').append(
                    '<br/><small><strong>Last Year:</strong><br/>Result: <?php echo $escaper->escapeJs("{$viewStatus->getComment()} <br/>Points: {$viewStatus->getPoints()}") ?>' + '</small>'
                );
                <?php endforeach ?>

                $('.headerRow.totalRow th').first().html(
                    '2015 Point Totals:<br/><br/>' +
                    '2015 Without Alternatives:<br/><br/>' +
                    '2014 Point Totals:<br/><br/>' +
                    '2013 Point Totals:'
                );

                <?php $maxPriorPoints = $escaper->escapeJs($lastYearRequirementsGroup->getComplianceViewGroup()->getMaximumNumberOfPoints()) ?>
                <?php $noAlternativesPoints = $escaper->escapeJs($status->getAttribute('total_points_ignoring_alternatives')) ?>

                $('.headerRow.totalRow td:eq(2)').first().append(
                    '<?php echo "<br/><br/>$maxPriorPoints<br/><br/>$maxPriorPoints<br/><br/>$maxPriorPoints" ?>'
                );

                $('.headerRow.totalRow td:eq(3)').first().html(
                    '<?php echo $status->getPoints() ?>' +
                    '<br/><br/><?php echo $noAlternativesPoints ?>' +
                    '<br/><br/><?php echo $escaper->escapeJs($lastYearRequirementsGroup->getPoints()) ?>' +
                    '<br/><br/><?php echo $escaper->escapeJs($priorLastYearRequirementsGroup->getPoints()) ?>'
                );
                <?php endif ?>



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
