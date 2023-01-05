<?php

class TCU2015ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new TCU2015Printer();
        $printer->showResult(true);
        $printer->setShowMaxPoints(false);

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
        $preventionEventGroup->setPointsRequiredForCompliance(0);

        $hpa = new CompleteHRAComplianceView($hraDate, $programEnd);
        $hpa->setReportName('Health Risk Assessment (HRA)');
        $hpa->setName('hra');
        $hpa->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(500, 0, 0, 0));
        $hpa->emptyLinks();
        $preventionEventGroup->addComplianceView($hpa);

        $scr = new CompleteScreeningComplianceView($programStart, $programEnd);
        $scr->setReportName('Screening Program');
        $scr->setName('screening');
        $scr->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(250, 0, 0, 0));
        $scr->emptyLinks();
        $preventionEventGroup->addComplianceView($scr);

        // Used for override to force compliant
        $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $forceCompliant->setName('force_compliant');
        $forceCompliant->setReportName('Force Overall Compliant');
        $preventionEventGroup->addComplianceView($forceCompliant);

        $this->addComplianceViewGroup($preventionEventGroup);


        $biometricsGroup = new ComplianceViewGroup('Requirements');
//        $biometricsGroup->setPointsRequiredForCompliance(0);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setIndicateSelfReportedResults(false);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $bloodPressureView->overrideDiastolicTestRowData(null, null, 90, 90);
        $bloodPressureView->overrideSystolicTestRowData(null, null, 140, 140);
//        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
//        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $biometricsGroup->addComplianceView($bloodPressureView);

        $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlView->overrideTestRowData(null, null, 160, 160);
        $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
//        $ldlView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
//        $ldlView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 160');
        $biometricsGroup->addComplianceView($ldlView);


        $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
        $bmiView->overrideTestRowData(0, 0, 29.9, 29.9);
        $bmiView->setReportName('BMI');
        $bmiView->setName('bmi');

        $aliasBloodPressure = 'alt_bloodpressure';
        $aliasCholesterolRatio = 'alt_cholesterolratio';
        $aliasBmi = 'alt_bmi';

        $aliasAll = array(
            $aliasBloodPressure => array(clone $bloodPressureView),
            $aliasCholesterolRatio => array(clone $ldlView),
            $aliasBmi => array(clone $bmiView)
        );

        $this->configureViewForElearningAlternative($bloodPressureView, $aliasBloodPressure, $aliasAll);
        $this->configureViewForElearningAlternative($ldlView, $aliasCholesterolRatio, $aliasAll);
        $this->configureViewForElearningAlternative($bmiView, $aliasBmi, $aliasAll);

        $biometricsGroup->addComplianceView($bmiView);

        $smokingView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $smokingView->setReportName('Nicotine');
        $biometricsGroup->addComplianceView($smokingView);

        $this->addComplianceViewGroup($biometricsGroup);
    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $alias, $allAliases)
    {
        // These user ids are exempt from having to complete all aliases

        $userIdsToIgnore = array();

        static $callCache = array(); // Keep the latest user's data around

        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use(&$callCache, $alias, $allAliases, $userIdsToIgnore) {
            $view = $status->getComplianceView();
            $view->setAttribute('alternative', false);
            $view->addLink(new Link('Elearning Lessons ', "/content/9420?action=lessonManager&tab_alias={$alias}"));

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

                $view->setAttribute('alternative', true);

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
        $thisTobacco = $status->getComplianceViewGroupStatus('Nicotine');

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

    public function isEmployee(User $user)
    {
        return $user->getRelationshipType() == 0 ? true : false;
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

           $status = $status->getComplianceViewStatus('hra');
        }

        return $status;
    }

    private $relationshipTypes = array(Relationship::SPOUSE, Relationship::EMPLOYEE);
    protected $evaluateOverall = true;
    const RECORD_ID = 515;
}

class TCU2015Printer extends CHPStatusBasedComplianceProgramReportPrinter
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

        return $mappings;
    }

    public function printClientMessage()
    {
        ?>

        <p>Below is your 2015 Incentive Report Card.</p>
        <p id="overview"><a href="/content/1094employees2015">CLICK HERE </a> to view the full details of the 2015 program.</p>
        <p>Welcome to Teachers Credit Union website! This site was developed not only to track your wellness
            requirements, but also to be used as a great resource for health related topics and questions.
            We encourage you to explore the site while also fulfilling your requirements. By completing the
            items below in 2015 you can earn incentives. You are not required to meet the target range for
            every measure. The criteria for meeting these ranges are listed below.</p>

        <?php
    }

    public function printClientNote()
    {
        ?>

        <?php
    }


    public function printReport(ComplianceProgramStatus $status)
    {
        $isEmployee = $status->getComplianceProgram()->isEmployee($status->getUser());
        $isSpouse = $status->getComplianceProgram()->isSpouse($status->getUser());
        $relatedUserStatus = $status->getComplianceProgram()->getRelatedUserComplianceStatus($status->getUser());
        $bloodPressureStatus = $status->getComplianceViewStatus('comply_with_blood_pressure_screening_test');
        $ldlStatus = $status->getComplianceViewStatus('comply_with_ldl_screening_test');
        $bmiStatus = $status->getComplianceViewStatus('bmi');
        $nicotineStatus = $status->getComplianceViewStatus('comply_with_cotinine_screening_test');


        $escaper = new hpn\common\text\Escaper;


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

                $('.group-prevention-event').children(':eq(3)').html('Points');
                $('.group-prevention-event').children(':eq(4)').html('Date Complete');

                <?php if($status->getComplianceProgram()->getRelatedUser($status->getUser())) : ?>
                    $('.view-screening').after('<tr class="statusRow newViewRow pointBased statusRow4 mainRow related_user_status"><td class="resource">3. <?php echo $isSpouse ? 'Team Member Status' : 'Spouse Status' ?></td><td class="summary">Complete HRA</td><td class="points"><?php echo $relatedUserStatus->getPoints() ?></td><td class="points"><?php echo $relatedUserStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td><td class="points"><?php echo $relatedUserStatus->getComment() ?></td><td></td></tr>');
                    $('.related_user_status').css('border-bottom', '2px solid #D7D7D7');
                <?php else : ?>
                    $('.view-screening').css('border-bottom', '2px solid #D7D7D7');
                <?php endif ?>

                $('.view-comply_with_cotinine_screening_test').children('.requirements').html('Negative');


                <?php if($status->getAttribute('has_biometric_override')) : ?>
                $('.finalHeaderRow').before('<tr class="headerRow"><th colspan="7" style="text-align:center;">Medical exception form received</th></tr>');
                <?php endif ?>

                $('.group-requirements').before('<tr id="legend_row"><td><div id="legendText">Legend</div></td><td colspan="5"><span class="legendEntry" id="legendEntry4" style="margin: 50px 0 50px 60px;"><img src="/images/lights/greenlight.gif" class="light" alt="">Inside Range </span><span class="legendEntry" id="legendEntry1"><img src="/images/lights/redlight.gif" class="light" alt="">Outside Range </span></div></td></tr>')


                $('.group-requirements').children(':eq(1)').html('Goal');
                $('.group-requirements').children(':eq(1)').after('<td>Your Results</td>');
                $('.group-requirements').children(':eq(1)').attr('colspan', 1);

                $('.group-requirements').children(':eq(4)').html('<td>Alternatives</td>');
                $('.group-requirements').children(':eq(5)').html('Additional Resources');

                $('.view-comply_with_blood_pressure_screening_test').children(':eq(1)').attr('colspan', 1);
                $('.view-comply_with_ldl_screening_test').children(':eq(1)').attr('colspan', 1);
                $('.view-bmi').children(':eq(1)').attr('colspan', 1);
                $('.view-comply_with_cotinine_screening_test').children(':eq(1)').attr('colspan', 1);

                $('.view-comply_with_blood_pressure_screening_test').children(':eq(1)').after('<td><?php echo $bloodPressureStatus->getComment() ?></td>');
                $('.view-comply_with_ldl_screening_test').children(':eq(1)').after('<td><?php echo $ldlStatus->getComment() ?></td>');
                $('.view-bmi').children(':eq(1)').after('<td><?php echo $bmiStatus->getComment() ?></td>');
                $('.view-comply_with_cotinine_screening_test').children(':eq(1)').after('<td><?php echo $nicotineStatus->getComment() ?></td>');


                <?php if($bloodPressureStatus->getComplianceView()->getAttribute('alternative')) : ?>
                $('.view-comply_with_blood_pressure_screening_test').children(':eq(4)').html('<a href="/content/tcu_alternatives">Alternative</a>');
                <?php endif ?>

                <?php if($ldlStatus->getComplianceView()->getAttribute('alternative')) : ?>
                $('.view-comply_with_ldl_screening_test').children(':eq(4)').html('<a href="/content/tcu_alternatives">Alternative</a>');
                <?php endif ?>

                <?php if($bmiStatus->getComplianceView()->getAttribute('alternative')) : ?>
                $('.view-bmi').children(':eq(4)').html('<a href="/content/tcu_alternatives">Alternative</a>');
                <?php endif ?>

                <?php if($nicotineStatus->getComplianceView()->getAttribute('alternative')) : ?>
                $('.view-comply_with_cotinine_screening_test').children(':eq(4)').html('<a href="/content/tcu_alternatives">Alternative</a>');
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

            #legend_row {
                height: 60px;
                border: 2px solid #ffffff;
             }
        </style>
        <?php
        parent::printReport($status);
    }
}
