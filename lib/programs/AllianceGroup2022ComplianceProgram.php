<?php
  use hpn\steel\query\SelectQuery;

  class AllianceGroupComplyWithWaistHipScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView {
    public function getTestName() {
      return 'waist_hip';
    }

    public function getDefaultName() {
      return 'comply_with_waist_hip_ratio_screening_test';
    }

    public function getDefaultReportName() {
      return 'Waist/Hip Ratio (%)';
    }

    protected function useRawFallbackQuestionValue() {
      return true;
    }
  }

  class AllianceGroupComplyWithBMIWaistHipRatioScreeningTestComplianceView extends ComplyWithBMIWaistHipRatioScreeningTestComplianceView {
    public function allowPointsOverride() {
      return true;
    }

    public function getStatusSummary($status) {
      return sprintf(
        'Waist Hip: %s, BMI: %s',
        $this->waistView->getStatusSummary($status),
        $this->bmiView->getStatusSummary($status)
      );
    }
  }

  class AllianceGroup2022CompleteScreeningComplianceView extends CompleteScreeningComplianceView {
    public function getData(User $user) {
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

  class AllianceGroup2022ComplianceProgram extends ComplianceProgram {
    public function getProgramReportPrinter($preferredPrinter = null) {
      return new AllianceGroup2022WMS2Printer();
    }

    public function getAdminProgramReportPrinter() {
      $printer = new BasicComplianceProgramAdminReportPrinter();
      $printer->setShowUserFields(true, true, false, false, true);
      $printer->setShowTotals(false);
      $printer->setShowStatus(false, false, false);
      $printer->setShowComment(false, false, false);
      $printer->setShowCompliant(false, false, false);
      $printer->setShowPoints(false, false, false);

      $printer->addCallbackField('hiredate', function (User $user) {
        return $user->getHiredate();
      });

      $printer->addCallbackField('location', function (User $user) {
        return $user->getLocation();
      });

      $printer->addCallbackField('employee_ssn', function (User $user) {
        if ($user->getRelationshipType() == 2)
          return $user->getRelationshipUser() ? $user->getRelationshipUser()->getSocialSecurityNumber(): '';
        else
          return $user->getSocialSecurityNumber();
      });

      $printer->addMultipleStatusFieldsCallback(function (ComplianceProgramStatus $status) {
        $user = $status->getUser();
        $data = array();

        foreach ($status->getComplianceViewGroupStatuses() as $groupStatus) {
          foreach ($groupStatus->getComplianceViewStatuses() as $viewStatus) {
            $viewName = $viewStatus->getComplianceView()->getReportName();

            if ($groupStatus->getComplianceViewGroup()->getName() == 'Requirements')
              $data[sprintf('%s Result', $viewName)] = $viewStatus->getComment();
            elseif ($groupStatus->getComplianceViewGroup()->getName() == 'Prevention Event')
              $data[sprintf('%s Comment', $viewName)] = $viewStatus->getComment();

            $data[sprintf('%s Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
          }
        }

        $data['Total Points'] = $status->getPoints();
        $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

        return $data;
      });

      return $printer;
    }

    public function loadGroups() {
      ini_set('memory_limit', '2500M');

      date_default_timezone_set('America/New_York');

      $programStart = $this->getStartDate();
      $programEnd = $this->getEndDate();

      $hraStart = '2022-10-04';
      $hraEnd = '2022-11-30';

      $screeningStart = '2022-07-01';
      $screeningEnd = '2022-12-31';

      $preventionEventGroup = new ComplianceViewGroup('Prevention Event');

      $hpa = new CompleteHRAComplianceView($hraStart, $hraEnd);
      $hpa->setReportName('Health Risk Assessment (HRA)');
      $hpa->setName('hra');
      $hpa->emptyLinks();
      $preventionEventGroup->addComplianceView($hpa);

      $scr = new CompleteScreeningComplianceView($screeningStart, $screeningEnd);
      $scr->setReportName('Screening Program');
      $scr->setName('screening');
      $scr->emptyLinks();
      $preventionEventGroup->addComplianceView($scr);

      $this->addComplianceViewGroup($preventionEventGroup);

      $biometricsGroup = new ComplianceViewGroup('Requirements');
      $biometricsGroup->setPointsRequiredForCompliance(10);

      $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($screeningStart, $screeningEnd);
      $bloodPressureView->setIndicateSelfReportedResults(false);
      $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
      $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
      $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
      $bloodPressureView->overrideSystolicTestRowData(0, 0, 119, 139);
      $bloodPressureView->overrideDiastolicTestRowData(0, 0, 79, 89);
      $biometricsGroup->addComplianceView($bloodPressureView);

      $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($screeningStart, $screeningEnd);
      $triglView->setIndicateSelfReportedResults(false);
      $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
      $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
      $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');
      $triglView->overrideTestRowData(0, 0, 149, 199);
      $biometricsGroup->addComplianceView($triglView);

      $ha1cView = new ComplyWithHa1cScreeningTestComplianceView($screeningStart, $screeningEnd);
      $ha1cView->setName('ha1c');
      $ha1cView->setIndicateSelfReportedResults(false);
      $ha1cView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
      // $ha1cView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-64 or 100-125');
      // $ha1cView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');
      $biometricsGroup->addComplianceView($ha1cView);

      $cholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($screeningStart, $screeningEnd);
      $cholesterolView->setIndicateSelfReportedResults(false);
      $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 1, 0, 0));
      $cholesterolView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '≤ 99 or ≥ 200');
      $cholesterolView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 89 or ≥ 241');
      $cholesterolView->overrideTestRowData(89, 100, 199, 241);
      $biometricsGroup->addComplianceView($cholesterolView);

      $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($screeningStart, $screeningEnd);
      $totalHDLRatioView->setIndicateSelfReportedResults(false);
      $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
      $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, null);
      $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '> 5');
      $biometricsGroup->addComplianceView($totalHDLRatioView);

      $waistBMIView = new AllianceGroupComplyWithBMIWaistHipRatioScreeningTestComplianceView($screeningStart, $screeningEnd);
      $waistBMIView->setBmiView($this->getBmiView($screeningStart, $screeningEnd));
      $waistBMIView->setWaistHipView($this->getWaistHipView($screeningStart, $screeningEnd));
      $waistBMIView->setComplianceStatusPointMapper(new NSK2013BFMapper());
      $waistBMIView->setReportName('Better of Hip/Waist Ratio or BMI');
      $waistBMIView->setName('waist_bmi');
      $waistBMIView->setPreMapCallback(function (ComplianceViewStatus $status, User $user){
        $hasWaistResult = $status->getAttribute('waist_has_result');
        $hasBmiResult = $status->getAttribute('bmi_has_result');
        $waistResult = $status->getAttribute('waist_result');
        $bmiResult = $status->getAttribute('bmi_result');

        if ($user->getGender() == 'F') {
          if ($hasWaistResult && $waistResult <= 0.8) {
            $status->setPoints(4);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult >= 18.5 && $bmiResult <= 25) {
            $status->setPoints(4);
            $status->setComment('BMI:'. $bmiResult);
          } elseif ($hasWaistResult && $waistResult >= 0.81 && $waistResult <= 0.86) {
            $status->setPoints(3);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult >= 17 && $bmiResult <= 30) {
            $status->setPoints(3);
            $status->setComment('BMI:'. $bmiResult);
          } elseif ($hasWaistResult && $waistResult >= 0.87 && $waistResult <= 1.14) {
            $status->setPoints(2);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult >= 15 && $bmiResult <= 35) {
            $status->setPoints(2);
            $status->setComment('BMI:'. $bmiResult);
          } elseif ($hasWaistResult && $waistResult >= 1.15 && $waistResult <= 1.17) {
            $status->setPoints(1);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult >= 13 && $bmiResult <= 40) {
            $status->setPoints(1);
            $status->setComment('BMI:'. $bmiResult);
          } elseif ($hasWaistResult && $waistResult > 1.17) {
            $status->setPoints(0);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult < 13 && $bmiResult > 40) {
            $status->setPoints(0);
            $status->setComment('BMI:'. $bmiResult);
          } else {
            $status->setPoints(0);
            $status->setComment('BMI:'. $bmiResult);
          }
        } else {
          if ($hasWaistResult && $waistResult <= 0.95) {
            $status->setPoints(4);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult >= 18.5 && $bmiResult <= 25) {
            $status->setPoints(4);
            $status->setComment('BMI:'. $bmiResult);
          } elseif ($hasWaistResult && $waistResult >= 0.96 && $waistResult <= 1.1) {
            $status->setPoints(3);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult >= 17 && $bmiResult <= 30) {
            $status->setPoints(3);
            $status->setComment('BMI:'. $bmiResult);
          } elseif ($hasWaistResult && $waistResult >= 1.11 && $waistResult <= 1.15) {
            $status->setPoints(2);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult >= 15 && $bmiResult <= 35) {
            $status->setPoints(2);
            $status->setComment('BMI:'. $bmiResult);
          } elseif ($hasWaistResult && $waistResult >= 1.16 && $waistResult <= 2) {
            $status->setPoints(1);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult >= 13 && $bmiResult <= 40) {
            $status->setPoints(1);
            $status->setComment('BMI:'. $bmiResult);
          } elseif ($hasWaistResult && $waistResult > 2) {
            $status->setPoints(0);
            $status->setComment('Waist Hip:'. $waistResult);
          } elseif ($hasBmiResult && $bmiResult < 13 && $bmiResult > 40) {
            $status->setPoints(0);
            $status->setComment('BMI:'. $bmiResult);
          } else {
            $status->setPoints(0);
            $status->setComment('BMI:'. $bmiResult);
          }
        }
      });
      // $waistBMIView->setUsePoints(true);
      $biometricsGroup->addComplianceView($waistBMIView);


      $smokingView = new ComplyWithSmokingHRAQuestionComplianceView($hraStart, $hraEnd);
      $smokingView->setName('tobacco');
      $smokingView->setReportName('Tobacco');
      $smokingView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $smokingView->setStatusSummary(ComplianceStatus::COMPLIANT, 'Non-User');
      $smokingView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, 'User');
      $biometricsGroup->addComplianceView($smokingView);

      $this->addComplianceViewGroup($biometricsGroup);

      $alternativeGroup = new ComplianceViewGroup('Alternatives');

      $elearningView = new CompleteELearningLessonsComplianceView($programStart, '2022-12-16');
      $elearningView->setReportName('eLearning Alternatives');
      $elearningView->setName('alternatives');
      $elearningView->setNumberRequired(5);
      $elearningView->emptyLinks();
      $elearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&tab_alias=all_lessons'));
      $alternativeGroup->addComplianceView($elearningView);

      $this->addComplianceViewGroup($alternativeGroup);

      $forceOverrideGroup = new ComplianceViewGroup('Force Override');

      $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $forceCompliant->setName('force_compliant');
      $forceCompliant->setReportName('Force Overall Compliant');
      $forceOverrideGroup->addComplianceView($forceCompliant);

      $this->addComplianceViewGroup($forceOverrideGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status) {
      parent::evaluateAndStoreOverallStatus($status);

      $user = $status->getUser();

      $thisPrevention = $status->getComplianceViewGroupStatus('Prevention Event');
      $thisRequirements = $status->getComplianceViewGroupStatus('Requirements');

      if ($thisPrevention->getStatus() == ComplianceStatus::COMPLIANT && $thisRequirements->getStatus() == ComplianceStatus::COMPLIANT)
        $status->setStatus(ComplianceStatus::COMPLIANT);
      elseif ($thisPrevention->getStatus() == ComplianceStatus::COMPLIANT && $status->getComplianceViewStatus('alternatives')->getStatus() == ComplianceStatus::COMPLIANT)
        $status->setStatus(ComplianceStatus::COMPLIANT);
      elseif ($status->getComplianceViewStatus('force_compliant')->getStatus() == ComplianceStatus::COMPLIANT)
        $status->setStatus(ComplianceStatus::COMPLIANT);
    }

    public function useParallelReport() {
      return false;
    }

    private function getBmiView($programStart, $programEnd) {
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

    private function getWaistHipView($programStart, $programEnd) {
      $view = new PointBasedComplyWithScreeningTestComplianceView(
        $programStart,
        $programEnd,
        'AllianceGroupComplyWithWaistHipScreeningTestComplianceView'
      );

      $view->setIndicateSelfReportedResults(false);

      $view->addRange(4, 0.0, 0.95, 'M');
      $view->addRange(3, 0.96, 1.1, 'M');
      $view->addRange(2, 1.11, 1.15, 'M');
      $view->addRange(1, 1.16, '2.0', 'M');
      $view->addDefaultStatusSummaryForGender(0, 'M', '&gt;2.0');


      $view->addRange(4, 0.0, 0.8, 'F');
      $view->addRange(3, 0.81, 0.86, 'F');
      $view->addRange(2, 0.87, 1.14, 'F');
      $view->addRange(1, 1.15, 1.17, 'F');
      $view->addDefaultStatusSummaryForGender(0, 'F', '&gt;1.17');

      return $view;
    }

    private $relationshipTypes = array(Relationship::SPOUSE, Relationship::EMPLOYEE);
  }


  class AllianceGroup2022WMS2Printer implements ComplianceProgramReportPrinter {
    public function getStatusMappings(ComplianceView $view) {
      if ($view->getName() == 'waist_bmi')
        return array(
          4 => ComplianceStatus::COMPLIANT,
          3 => ComplianceStatus::PARTIALLY_COMPLIANT,
          2 => ComplianceStatus::NOT_COMPLIANT,
          1 => ComplianceStatus::NOT_COMPLIANT,
          0 => ComplianceStatus::NOT_COMPLIANT
        );
      elseif ($view->getName() == 'tobacco')
          return array(
            4 => ComplianceStatus::COMPLIANT,
            1 => ComplianceStatus::NOT_COMPLIANT
          );
      else
        return array(
          4 => ComplianceStatus::COMPLIANT,
          2 => ComplianceStatus::PARTIALLY_COMPLIANT,
          1 => ComplianceStatus::NOT_COMPLIANT
        );
    }

    public function getClass(ComplianceView $view) {
      if ($view->getName() == 'waist_bmi')
        return array(
          4 => 'success',
          3 => 'warning',
          2 => 'danger',
          1 => 'danger',
          0 => 'danger'
        );
      elseif ($view->getName() == 'tobacco')
        return array(
          ComplianceStatus::COMPLIANT => 'success',
          ComplianceStatus::NOT_COMPLIANT => 'danger'
        );
      else
        return array(
          ComplianceStatus::COMPLIANT => 'success',
          ComplianceStatus::PARTIALLY_COMPLIANT => 'warning',
          ComplianceStatus::NOT_COMPLIANT => 'danger'
        );
    }

    public function printReport(ComplianceProgramStatus $status) {
      $escaper = new hpn\common\text\Escaper;

      $requirementsStatus = $status->getComplianceViewGroupStatus('Requirements');

      $thisYearTotalPoints = $requirementsStatus->getPoints();

      $classFor = function ($rawPct) {
        return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning'): 'danger';
      };

      $that = $this;

      $groupTable = function (ComplianceViewGroupStatus $group) use ($classFor, $that) {
        ob_start();

        if ($group->getComplianceViewGroup()->getName() == 'Requirements'): ?>
          <table class="table table-condensed">
            <thead>
              <tr>
                <th>Test</th>
                <th>Target</th>
                <th class="text-center">Point Values</th>
                <th class="text-center">Your Points</th>
                <th class="text-center">Results</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($group->getComplianceViewStatuses() as $viewStatus): ?>
                <?php $view = $viewStatus->getComplianceView() ?>
                <?php $warningLabel = $view->getStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                <?php $printed = false ?>
                <?php $mappings = $that->getStatusMappings($view); ?>
                <?php $class = $that->getClass($view); ?>
                <?php $j = 0 ?>
                <?php foreach ($mappings as $sstatus => $mapping): ?>
                  <?php if ($warningLabel !== null || $sstatus != ComplianceStatus::PARTIALLY_COMPLIANT): ?>
                    <tr>
                      <?php if ($j < 1): ?>
                        <td rowspan="<?= $warningLabel === null ? (count($mappings) - 1): count($mappings) ?>">
                          <?= $view->getReportName() ?>
                          <br/>
                          <?php foreach ($viewStatus->getComplianceView()->getLinks() as $link): ?>
                            <div><?= $link->getHTML() ?></div>
                          <?php endforeach; ?>
                        </td>
                      <?php endif; ?>
                      <td>
                        <span class="label label-<?= $class[$sstatus] ?>">
                          <?= $view->getStatusSummary($sstatus) ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <?= $view->getStatusPointMapper()->getPoints($sstatus) ?>
                      </td>
                      <td class="text-center">
                        <?php if ($viewStatus->getComplianceView()->getName() == 'waist_bmi'): ?>
                          <?php if ($viewStatus->getPoints() == $sstatus): ?>
                            <span class="label label-<?= $class[$sstatus] ?>"><?= $viewStatus->getPoints() ?></span>
                          <?php endif; ?>
                        <?php elseif ($viewStatus->getComplianceView()->getName() == 'comply_with_total_hdl_cholesterol_ratio_screening_test'): ?>
                          <?php if ($viewStatus->getPoints() > 0 && $sstatus == 4): ?>
                            <span class="label label-<?= $class[$sstatus] ?>"><?= $viewStatus->getPoints() ?></span>
                          <?php elseif ($viewStatus->getPoints() == 0 && $sstatus < 4): ?>
                            <span class="label label-<?= $class[$sstatus] ?>"><?= $viewStatus->getPoints() ?></span>
                          <?php endif; ?>
                        <?php else : ?>
                          <?php if ($viewStatus->getStatus() == $sstatus): ?>
                            <span class="label label-<?= $class[$sstatus] ?>"><?= $viewStatus->getPoints() ?></span>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <?php if ($viewStatus->getComplianceView()->getName() == 'waist_bmi'): ?>
                          <?php if ($viewStatus->getPoints() == $sstatus): ?>
                            <span class="label label-<?= $class[$sstatus] ?>">
                              <?= $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                              <?= $viewStatus->getComment() ?>
                            </span>
                          <?php endif; ?>
                        <?php elseif ($viewStatus->getComplianceView()->getName() == 'comply_with_total_hdl_cholesterol_ratio_screening_test'): ?>
                          <?php if ($viewStatus->getPoints() > 0 && $sstatus == 4): ?>
                            <span class="label label-<?= $class[$sstatus] ?>">
                              <?= $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                              <?= $viewStatus->getComment() ?>
                            </span>
                          <?php elseif ($viewStatus->getPoints() == 0 && $sstatus < 4): ?>
                            <span class="label label-<?= $class[$sstatus] ?>">
                              <?= $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                              <?= $viewStatus->getComment() ?>
                            </span>
                          <?php endif; ?>
                        <?php else : ?>
                          <?php if ($viewStatus->getStatus() == $sstatus): ?>
                            <span class="label label-<?= $class[$sstatus] ?>">
                              <?= $viewStatus->getAttribute('original_comment') ? "{$viewStatus->getAttribute('original_comment')} <br />" : '' ?>
                              <?= $viewStatus->getComment() ?>
                            </span>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php $j++ ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else : ?>
          <table class="details-table">
            <thead>
              <tr>
                <th>Item</th>
                <th class="points">Status</th>
                <th class="text-center">Date of Exam</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1 ?>
              <?php foreach ($group->getComplianceViewStatuses() as $viewStatus): ?>
                <?php
                  if ($viewStatus->isCompliant())
                    $pct = 1;
                  else if ($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT)
                    $pct = 0.5;
                  else
                    $pct = 0;

                  $text = $viewStatus->getText();
                ?>
                <?php $class = $classFor($pct) ?>
                <tr class="<?= 'view-', $viewStatus->getComplianceView()->getName() ?>">
                  <td class="name">
                    <?= $i ?>.
                    <?= $viewStatus->getComplianceView()->getReportName() ?>
                  </td>
                  <td class="points <?= $class ?>">
                    <?= $text ?>
                  </td>
                  <td class="links text-center">
                    <div><?= $viewStatus->getComment() ?></div>
                  </td>
                </tr>
                <?php $i++ ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
        <?php
          return ob_get_clean();
      };

      $tableRow = function ($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
        ob_start();

        if ($group->getComplianceViewGroup()->getMaximumNumberOfPoints() === null) {
          if ($group->getComplianceViewGroup()->getName() == 'Prevention Event') {
            if ($group->getComplianceViewStatus('hra')->getStatus() == ComplianceViewStatus::COMPLIANT
                && $group->getComplianceViewStatus('screening')->getStatus() == ComplianceViewStatus::COMPLIANT) {
              $pct = 1;
              $actual = 'Done';
            } elseif ($group->getComplianceViewStatus('hra')->getStatus() == ComplianceViewStatus::COMPLIANT
                || $group->getComplianceViewStatus('screening')->getStatus() == ComplianceViewStatus::COMPLIANT) {
              $pct = 0.5;
              $actual = 'Not Done';
            } else {
              $pct = 0;
              $actual = 'Not Done';
            }
          } else {
            if ($group->isCompliant())
              $pct = 1;
            else if ($group->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT)
              $pct = 0.5;
            else
              $pct = 0;

            $actual = $group->getText();
          }

          $target = 'Done';
        } else {
          $points = $group->getPoints();
          $target = '<strong>'.$group->getComplianceViewGroup()->getMaximumNumberOfPoints().'</strong><br/>points';
          $actual = '<strong>'.$points.'</strong><br/>points';
          $pct = $points / $group->getComplianceViewGroup()->getPointsRequiredForCompliance();
        }

        $class = $classFor($pct);
        if ($pct > 1) $pct = 1;

        ?>

        <tr class="picker closed">
          <td class="name">
            <?= $name ?>
            <div class="triangle"></div>
          </td>
          <td class="points target">
            <?= $target ?>
          </td>
          <td class="points <?= $class ?>">
            <?= $actual ?>
          </td>
          <td class="pct">
            <div class="pgrs">
              <div class="bar <?= $class ?>" style="width: <?= max(1, $pct * 100) ?>%"></div>
            </div>
          </td>
        </tr>
        <tr class="details closed">
          <td colspan="4">
            <?= $groupTable($group) ?>
          </td>
        </tr>

        <?php

        return ob_get_clean();
      };

      $maxPriorPoints = $escaper->escapeJs($requirementsStatus->getComplianceViewGroup()->getMaximumNumberOfPoints());

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
          font-size: 1em;
        }


        .point-totals th {
          text-align: right;
          padding-right: 10px;
        }

        .point-totals td {
          text-align: center;
        }

        .total-status td, .spouse-status td {
          text-align: center;
        }
      </style>

      <div class="row">
        <div class="col-md-12">
          <h1>2022 Incentive Report Card</h1>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <hr/>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <p>
            Receive HSA contributions by completing the following steps:

            <ul>
              <li>Complete an HRA (complete by 11/18/2022)</li>
              <li>Complete a health screening (complete by 11/18/2022)</li>
              <li>
                Earn a minimum 10 of 15 points for having in-range biometrics,
                OR complete 1 of the alternative options mentioned in the yellow
                box below if 10 points aren't earned (complete by 12/16/2022)
              </li>
            </ul>

            If you are unable to attend the onsite screening, please contact the
            Circle Wellness Customer Service Department for alternative options
            at 866-682-3020 x 204
          </p>

          <div class="alert alert-warning">
            IF YOU HAVE NOT ACCUMULATED 10 OR MORE POINTS IN YOUR REPORT CARD, YOU MUST COMPLETE 1 OF THE FOLLOWING CHOICES.
            <ol>
              <li>
                <a href="/wms1/pdf/clients/alliance/Physician_Verification_Form_2022.pdf"  target="_blank" style="text-decoration:underline">DOWNLOAD A PHYSICIAN’S VERIFICATION FORM</a>
                AND TAKE IT TO YOUR DOCTOR AND THEN SUBMIT IT VIA HIPPA SECURE
                FAX (1-800-887-9579 OR VIA THE UPLOAD DOCUMENT TOOL).
              </li>
              <li>
                <a href="/content/9420?action=lessonManager&tab_alias=all_lessons"  target="_blank" style="text-decoration:underline">COMPLETE 5 E-LEARNING LESSONS</a>.
              </li>
            </ol>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <table id="activities">
            <thead>
              <tr>
                <th></th>
                <th class="points">Target</th>
                <th class="points">Actual</th>
                <th class="text-center">Progress</th>
              </tr>
            </thead>
            <tbody>
              <?= $tableRow('Prevention Events', $status->getComplianceViewGroupStatus('Prevention Event')) ?>
              <?= $tableRow('Biometric Measures', $status->getComplianceViewGroupStatus('Requirements')) ?>
              <tr class="point-totals">
                <th>2022 Point Totals</th>
                <td><?= $maxPriorPoints ?></td>
                <td><?= $status->getPoints() ?></td>
              </tr>
              <tr class="point-totals">
                <td colspan="4"><hr/></td>
              </tr>
              <tr class="total-status">
                <th>
                  Your Program Status
                  <ul>
                    <li>Complete HRA and Screening</li>
                    <li>
                      Obtain 10+ points or complete a reasonable alternative
                      <ul>
                        <li>
                          <a href="/wms1/pdf/clients/alliance/Physician_Verification_Form_2022.pdf"  target="_blank">Download this physician form</a>
                          and take it to your doctor. Upload it via HIPPA secure
                          fax to 1-800-887-9579 or via the upload documents
                          button.
                        </li>
                        <li>
                          <a href="/content/9420?action=lessonManager&tab_alias=all_lessons" target="_blank">Complete 5 elearning lessons</a>.
                        </li>
                      </ul>
                    </li>
                  </ul>
                </th>
                <td colspan="3">
                  <?php if ($status->isCompliant()): ?>
                    <span class="label label-success">Done</span>
                  <?php elseif ($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT): ?>
                    <span class="label label-warning">Partially Done</span>
                  <?php else : ?>
                    <span class="label label-danger">Not Done</span>
                  <?php endif; ?>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <script type="text/javascript">
        $(function () {
          $.each($('#activities .picker'), function() {
            $(this).click(function (e) {
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

          $more = $('#more-info-toggle');
          $moreContent = $('#more-info');

          $more.click(function (e) {
            e.preventDefault();

            if ($more.html() == 'More...') {
              $moreContent.css({ display: 'block' });
              $more.html('Less...');
            } else {
              $moreContent.css({ display: 'none' });
              $more.html('More...');
            }
          });
        });
      </script>

      <?php
    }
  }
?>
