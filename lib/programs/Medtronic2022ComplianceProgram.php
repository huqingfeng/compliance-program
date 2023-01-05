<?php
  class Medtronic2022CompleteScreeningComplianceView extends CompleteScreeningComplianceView {
    public function getData(User $user) {
      $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
          new DateTime('@'.$this->getStartDate()),
          new DateTime('@'.$this->getEndDate()),
          array(
            'require_online'   => false,
            'merge'            => true,
            'require_complete' => false,
            'filter'           => $this->getFilter(),
            'required_fields'  => array('glucose')
          )
      );

      if(count($data) > 0) $this->setAttribute('has_screening', true);

      return $data;
    }
  }

  class Medtronic2022ComplianceProgram extends ComplianceProgram {
    public function getAdminProgramReportPrinter() {
      $printer = new BasicComplianceProgramAdminReportPrinter();

      $printer->setShowUserContactFields(null, null, true);

      $printer->setShowCompliant(false, null, null);

      $printer->addStatusFieldCallback('# of Goals Met', function(ComplianceProgramStatus $status) {
        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

        return $healthyGroupStatus->getAttribute('number_compliant');
      });

      $printer->addCallbackField('employeeid', function (User $user) {
        return (string) $user->getEmployeeid();
      });

      return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null) {
      return new Medtronic2022ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus() {
      return false;
    }

    public function loadGroups() {
      $programStart = $this->getStartDate();
      $programEnd = $this->getEndDate();

      $coreGroup = new ComplianceViewGroup('core', 'Program');

      $screeningView = new Medtronic2022CompleteScreeningComplianceView($programStart, $programEnd);
      $screeningView->setReportName('1. Complete the wellness screening');
      $screeningView->setName('complete_screening');
      $screeningView->emptyLinks();
      $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
      $screeningView->setAttribute('goal', '12/05/22');
      $screeningView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2000, 0, 0, 0));
      $coreGroup->addComplianceView($screeningView);

      $this->addComplianceViewGroup($coreGroup);

      $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
      $group->setPointsRequiredForCompliance(3);

      $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
      $bmiView->setReportName('1. Body Mass Index');
      $bmiView->overrideTestRowData(null, null, 27.5, null);
      $bmiView->emptyLinks();
      $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $bmiView->setAttribute('goal', '≤27.5');
      $bmiView->setAttribute('screening_view', true);
      $bmiView->setPreMapCallback($this->validateScreening());
      $group->addComplianceView($bmiView);

      $waistView = new ComplyWithWaistScreeningTestComplianceView($programStart, $programEnd);
      $waistView->setReportName('2. Waist');
      $waistView->setName('waist');
      $waistView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $waistView->overrideTestRowData(null, null, 40, null, 'M');
      $waistView->overrideTestRowData(null, null, 35, null, 'F');
      $waistView->setAttribute('goal', 'F≤35  M≤40');
      $waistView->setPreMapCallback($this->validateScreening());
      $group->addComplianceView($waistView);

      $bpView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
      $bpView->setReportName('3. Blood Pressure');
      $bpView->setName('blood_pressure');
      $bpView->overrideSystolicTestRowData(null, null, 139.999, null);
      $bpView->overrideDiastolicTestRowData(null, null, 89.999, null);
      $bpView->emptyLinks();
      $bpView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $bpView->setAttribute('goal', '<140 / <90');
      $bpView->setAttribute('screening_view', true);
      $bpView->setPreMapCallback($this->validateScreening());
      $group->addComplianceView($bpView);

      $hdlRatioView = new ComplyWithNonHDLCholesterolScreeningTestComplianceView($programStart, $programEnd);
      $hdlRatioView->setReportName('4. Non-HDL Cholesterol');
      $hdlRatioView->overrideTestRowData(null, null, 129.999, null);
      $hdlRatioView->emptyLinks();
      $hdlRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $hdlRatioView->setAttribute('goal', '<130');
      $hdlRatioView->setAttribute('screening_view', true);
      $hdlRatioView->setPreMapCallback($this->validateScreening());
      $group->addComplianceView($hdlRatioView);

      $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
      $gluView->setReportName('5. Glucose');
      $gluView->overrideTestRowData(null, null, 99, null);
      $gluView->emptyLinks();
      $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $gluView->setAttribute('screening_view', true);
      $gluView->setAttribute('goal', '≤99');
      $gluView->setPreMapCallback($this->validateScreening());
      $group->addComplianceView($gluView);

      $cotinineView = new ComplyWithCigaretteSmokingScreeningTestComplianceView($programStart, $programEnd);
      $cotinineView->setReportName('6. Tobacco Use Status');
      $cotinineView->setName('tobacco');
      $cotinineView->emptyLinks();
      $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $cotinineView->setAttribute('goal', 'Non-User');
      $cotinineView->setAttribute('screening_view', true);
      $cotinineView->setPreMapCallback($this->validateScreening());
      $group->addComplianceView($cotinineView);

      $this->addComplianceViewGroup($group);

      $additionalGroup = new ComplianceViewGroup('additional', 'Additional');
      $additionalGroup->setPointsRequiredForCompliance(0);

      $qualificationForm = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $qualificationForm->setReportName('Alternate Qualification Form');
      $qualificationForm->setName('alternate_qualification_form');
      $qualificationForm->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1000, 0, 0, 0));
      $qualificationForm->setAttribute('goal', 'Complete');
      $additionalGroup->addComplianceView($qualificationForm);

      $this->addComplianceViewGroup($additionalGroup);
    }

    protected function validateScreening() {
      $programStart = $this->getStartDate();
      $programEnd = $this->getEndDate();

      return function($status, $user) use ($programStart, $programEnd) {
        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
          $user,
          new DateTime('@'.$programStart),
          new DateTime('@'.$programEnd),
          array(
            'require_online'   => false,
            'merge'            => true,
            'require_complete' => false,
            'required_fields'  => array('glucose')
          )
        );

        if($status->getComplianceView()->getName() == 'blood_pressure') {
          if(strpos(strtolower($status->getAttribute('systolic_real_result')), 'decline') !== false) {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            $status->setComment($status->getAttribute('systolic_real_result'));
          } elseif(strpos(strtolower($status->getAttribute('diastolic_real_result')), 'decline') !== false) {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            $status->setComment($status->getAttribute('diastolic_real_result'));
          }
        } else {
          if(strpos(strtolower($status->getAttribute('real_result')), 'decline') !== false) {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            $status->setComment($status->getAttribute('real_result'));
          }
        }

        if(!isset($data['glucose']) || empty($data['glucose'])) {
          $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
          $status->setComment('');
        }

        return $data;
      };
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status) {
        parent::evaluateAndStoreOverallStatus($status);

        $healthGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $noncompliantValues = array('qns', 'tnp', 'test not taken', 'declined');

        $numCompliant = 0;
        foreach($healthGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getComplianceView()->getName() == 'biometrics') continue;

            if (in_array(strtolower($viewStatus->getAttribute('real_result')), $noncompliantValues)) {
              $viewStatus->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
              $viewStatus->setComment($viewStatus->getAttribute('real_result'));
            }

            if ($viewStatus->getComplianceView()->getName() == 'blood_pressure' &&
                (in_array(strtolower($viewStatus->getAttribute('systolic_real_result')), $noncompliantValues) ||
                 in_array(strtolower($viewStatus->getAttribute('diastolic_real_result')), $noncompliantValues))) {
              $viewStatus->setStatus(ComplianceViewStatus::NOT_COMPLIANT);
              $viewStatus->setComment($viewStatus->getAttribute('systolic_real_result') . ' / ' . $viewStatus->getAttribute('diastolic_real_result'));
            }

            if($viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT) $numCompliant++;
        }

        if($numCompliant >= 5)
          $healthGroupStatus->setPoints(2000);
        elseif ($numCompliant >= 4)
          $healthGroupStatus->setPoints(1500);
        elseif ($numCompliant >= 3)
          $healthGroupStatus->setPoints(1000);
        else
          $healthGroupStatus->setPoints(0);

        if($numCompliant >= 3)
          $healthGroupStatus->setStatus(ComplianceStatus::COMPLIANT);
        else
          $healthGroupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);

        $healthGroupStatus->setAttribute('number_compliant', $numCompliant);

        $coreStatus = $status->getComplianceViewGroupStatus('core');
        $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
        $additionalGroupStatus = $status->getComplianceViewGroupStatus('additional');

        $status->setPoints($coreStatus->getPoints() + $healthyGroupStatus->getPoints() + $additionalGroupStatus->getPoints());
      }
  }

  class Medtronic2022ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter {
    public function printReport(ComplianceProgramStatus $status) {
      $user = $status->getUser();

      $coreStatus = $status->getComplianceViewGroupStatus('core');
      $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');

      if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter')
        return;

      ?>

      <style type="text/css">
        html {
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        #page #wms3-content h1,
        #page #content h1 {
          margin-top: 2rem;
          padding: 0.5rem;
          background-color: #6fb001;
          color: white;
          text-align: center;
          font-family: Roboto;
          font-size: 1.5rem;
          font-weight: bold;
          border-radius: 0.25rem;
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        #page #wms3-content aside,
        #page #content aside {
          margin-top: 2rem;
          padding: 1.5rem;
          background-color: #f8fafd;
          border: 1px solid #d1d9e8;
          border-radius: 0.25rem;
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
          color: #263b4f;
        }

        #page #wms3-content .warning,
        #page #content .warning {
          position: relative;
          padding-left: 3rem;
          color: #E53935;
        }

        #page #wms3-content .warning i,
        #page #content .warning i {
          background-color: transparent !important;
          text-align: center;
          margin-top: -0.95rem;
          font-size: 1.25rem;
        }

        #page #wms3-content .warning i,
        #page #wms3-content q,
        #page #content .warning i,
        #page #content q {
          position: absolute;
          top: 50%;
          left: 0.5rem;
        }

        #page #wms3-content q,
        #page #content q {
          margin-top: -1.2rem;
        	background-color: #ffb65e;
        	text-align: left;
        }

        #page #wms3-content q:before,
        #page #wms3-content q:after,
        #page #content q:before,
        #page #content q:after {
        	content: '';
        	position: absolute;
        	background-color: inherit;
        }

        #page #wms3-content q,
        #page #wms3-content q:before,
        #page #wms3-content q:after,
        #page #content q,
        #page #content q:before,
        #page #content q:after {
          display: inline-block;
        	width:  1.5rem;
        	height: 1.5rem;
          border-radius: 0;
        	border-top-right-radius: 30%;
        }

        #page #wms3-content q,
        #page #content q {
        	transform: rotate(-60deg) skewX(-30deg) scale(1,.866);
        }

        #page #wms3-content q:before,
        #page #content q:before {
        	transform: rotate(-135deg) skewX(-45deg) scale(1.414,.707) translate(0,-50%);
        }

        #page #wms3-content q:after,
        #page #content q:after {
        	transform: rotate(135deg) skewY(-45deg) scale(.707,1.414) translate(50%);
        }

        #page #wms3-content table,
        #page #content table {
          border-collapse: collapse;
          margin-bottom: 2rem;
          width: 100%;
          line-height: 1.5rem;
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        #page #wms3-content table + table,
        #page #content table + table {
          margin-top: 1rem;
        }

        #page #wms3-content th,
        #page #content th {
          padding: 1rem;
          background-color: #014265;
          color: white;
          border: 1px solid #014265;
          font-weight: bold;
          text-align: center;
        }

        #page #wms3-content tr:first-of-type th:first-of-type,
        #page #content tr:first-of-type th:first-of-type {
          border-top-left-radius: 0.25rem;
          text-align: left;
        }

        #page #wms3-content tr:first-of-type th:last-of-type,
        #page #content tr:first-of-type th:last-of-type {
          border-top-right-radius: 0.25rem;
        }

        #page #wms3-content td,
        #page #content td {
          padding: 1rem;
          color: #57636e;
          border-left: 1px solid #e8e8e8;
          border-bottom: 1px solid #e8e8e8;
          text-align: center;
        }

        #page #wms3-content tr:last-of-type td:first-of-type,
        #page #content tr:last-of-type td:first-of-type {
          border-bottom-left-radius: 0.25rem;
        }

        #page #wms3-content td:last-of-type,
        #page #content td:last-of-type {
          border-right: 1px solid #e8e8e8;
        }

        #page #wms3-content tr:last-of-type td:last-of-type,
        #page #content tr:last-of-type td:last-of-type {
          border-bottom-right-radius: 0.25rem;
        }

        #page #wms3-content a,
        #page #content a {
          display: inline-block;
          color: #0085f4 !important;
          text-decoration: none !important;
        }

        #page #wms3-content a:hover,
        #page #wms3-content a:focus,
        #page #wms3-content a:active,
        #page #content a:hover,
        #page #content a:focus,
        #page #content a:active {
          color: #0052C1 !important;
          text-decoration: none !important;
        }

        #page #wms3-content i,
        #page #content i {
          width: 1.5rem;
          height: 1.5rem;
          line-height: 1.5rem;
          background-color: #ced2db;
          border-radius: 999px;
          color: white;
          font-size: 1.25rem;
        }

        #page #wms3-content i.fa-check,
        #page #content i.fa-check {
          background-color: #4fd3c2;
        }

        #page #wms3-content i.fa-exclamation,
        #page #content i.fa-exclamation {
          background-color: #ffb65e;
        }

        #page #wms3-content i.fa-times,
        #page #content i.fa-times {
          background-color: #dd7370;
        }

        #page #wms3-content .split,
        #page #content .split {
          display: flex;
          justify-content: space-between;
        }

        @media only screen and (max-width: 1060px) {
          #page #wms3-content table,
          #page #content table {
            table-layout: auto;
          }
        }
      </style>

      <div class="split">
        <p>
          4205 Westbrook Drivec<br />
          Aurora, IL 60504
        </p>
        <img src="/images/empower/ehs_logo.jpg" style="height:50px;" />
      </div>

      <p style="margin-left:0.75in; padding-top:.56in; clear: both;">
        <?= $user->getFullName() ?> <br/>
        <?= $user->getFullAddress("<br/>") ?>
      </p>

      <aside>
        <div class="split">
          <p><b>Dear <?= $user->first_name ?>,</b></p>
          <p><b><?= date("m/d/Y") ?></b></p>
        </div>

        <p>
          Congratulations for participating in the 2022 Wellness Screening
          program. Below is a chart that outlines your Healthy Measures results
          and reward points. Your reward points will be visible on
          <a href="http://healthiertogether.medtronic.com">healthiertogether.medtronic.com</a>
          within six weeks. Please note that your test results are not shared
          with Medtronic and remain confidential.
        </p>
      </aside>

      <h1>Your Healthy Measures Results for 2022</h1>

      <?= $this->getTable($status) ?>

      <p>
        Visit <a href="http://medtronic.hpn.com">medtronic.hpn.com</a> to view
        your screening results, links in the report, and to access powerful
        tools and resources for optimizing your wellbeing such as:
      </p>

      <ul>
        <li>
          Healthwise® Knowledgebase for decisions about medical tests,
          medications, other treatments and risks
        </li>
        <li>
          Over 500 videos and 1,000 e-lessons on health and wellbeing
        </li>
        <li>
          Decision Tools for over 170 elective care decisions
        </li>
        <li>
          Cholesterol, body metrics, blood sugars, women’s and men’s health and
          over 40 other learning centers
        </li>
      </ul>

      <p>Thank you for participating in the Wellness Screening program.</p>

      <br />
      <br />

      <div class="split">
        <p>
          Best Regards,<br />
          Empower Health Services
        </p>
        <img src="/images/empower/medtronic_logo.jpg" style="height:60px;"  />
      </div>

      <?php if($coreStatus->getStatus() == ComplianceStatus::COMPLIANT && $healthyGroupStatus->getAttribute('number_compliant') <= 2) : ?>
        <p>
          <img src="/images/medtronic/AQF.png" style="margin-top:2rem;" />
        </p>
      <?php endif; ?>

      <?php
    }

    private function getTable($status) {
      $coreStatus = $status->getComplianceViewGroupStatus('core');
      $healthyGroupStatus = $status->getComplianceViewGroupStatus('healthy_measures');
      $additionalGroupStatus = $status->getComplianceViewGroupStatus('additional');

      $qualificationFormStatus = $status->getComplianceViewStatus('alternate_qualification_form');

      ob_start();

      ?>

      <table>
        <tbody>
          <tr>
            <th colspan="4">A. INCENTIVE ACTIONS</th>
            <th>Date Done</th>
            <th>Goal Deadline</th>
            <th>Goal Met</th>
            <th>Points Possible</th>
            <th>My Points</th>
          </tr>

          <?php foreach ($coreStatus->getComplianceViewStatuses() as $viewStatus) : ?>
            <tr>
              <td colspan="4" style="text-align:left;">
                <?= $viewStatus->getComplianceView()->getReportName() ?>
              </td>
              <td>
                <?= $viewStatus->getComment() ?>
              </td>
              <td>
                <?= $viewStatus->getComplianceView()->getAttribute('goal') ?>
              </td>
              <td>
                <i class="far fa-<?= $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'check' : 'times' ?>"></i>
              </td>
              <td>
                <?= $viewStatus->getComplianceView()->getMaximumNumberOfPoints()  ?>
              </td>
              <td>
                <?= $viewStatus->getPoints()  ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <tr>
            <th colspan="4">B. INCENTIVE MEASURES</th>
            <th>My Result</th>
            <th>Goal Range</th>
            <th>Goal Met</th>
            <th></th>
            <th></th>
          </tr>

          <?php
            $count = 1;
            $first = true;
            foreach ($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus)
              $count++;
          ?>
          <?php foreach ($healthyGroupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
            <tr>
              <td colspan="4" style="text-align:left;">
                <?= $viewStatus->getComplianceView()->getReportName() ?>
              </td>
              <td>
                <?= $viewStatus->getComment() ?>
              </td>
              <td>
                <?= $viewStatus->getComplianceView()->getAttribute('goal') ?>
              </td>
              <td>
                <i class="far fa-<?= $viewStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'check' : 'times' ?>"></i>
              </td>

              <?php if($first) : ?>
                <td rowspan="<?= $count ?>">
                  <div style="min-width:7rem;">
                    0-2 met = 0 pts.<br/>
                    3 = 1,000 pts.<br/>
                    4 = 1,500 pts.<br/>
                    5+ = 2,000 pts.
                  </div>
                </td>
                <td rowspan="<?= $count ?>">
                  <?= $healthyGroupStatus->getPoints() ?>
                </td>
                <?php $first = false; ?>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td colspan="4">
              # of Goals Met
            </td>
            <td>
              <?= $healthyGroupStatus->getAttribute('number_compliant') ?>
            </td>
            <td>
              3 or more
            </td>
            <td>
              <i class="far fa-<?= $healthyGroupStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'check' : 'times' ?>"></i>
            </td>
          </tr>
          <?php if ($qualificationFormStatus->getStatus() == ComplianceStatus::COMPLIANT): ?>
            <tr>
              <td colspan="4" style="text-align:left;">
                <?= $qualificationFormStatus->getComplianceView()->getReportName() ?>
              </td>
              <td>
                <?= $qualificationFormStatus->getComment() ?>
              </td>
              <td>
                <?= $qualificationFormStatus->getComplianceView()->getAttribute('goal') ?>
              </td>
              <td>
                <i class="far fa-<?= $qualificationFormStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'check' : 'times' ?>"></i>
              </td>
              <td>
                <?= $qualificationFormStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
              </td>
              <td>
                <?= $qualificationFormStatus->getPoints() ?>
              </td>
            </tr>
          <?php endif ?>

          <tr>
            <th colspan="4" style="text-align:left;">POINT TOTAL</th>
            <th></th>
            <th></th>
            <th></th>
            <th>Total Reward Points</th>
            <th><?= $coreStatus->getPoints() + $healthyGroupStatus->getPoints() + $additionalGroupStatus->getPoints() ?></th>
          </tr>

          <tr>
            <td colspan="9" style="text-align:left;">
              <?php if ($healthyGroupStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT && $coreStatus->getComplianceViewStatus('complete_screening')->getComplianceView()->getAttribute('has_screening')) : ?>
                <p class="warning">
                  <q></q>
                  <i class="fas fa-exclamation"></i>
                  You may complete and return the
                  <a href="/pdf/clients/medtronic/AQF.pdf">Alternate Qualification Form</a>
                  to earn 1,000 points for Healthy Measures, for a total of
                  3,000 points for completing the screening program and the
                  form.
                </p>
              <?php endif; ?>
              <p style="text-align:center;">
                Visit
                <a href="http://healthiertogether.medtronic.com">healthiertogether.medtronic.com</a>
                to view all your options to earn reward points.
              </p>
            </td>
          </tr>
        </tbody>
      </table>

      <?php

      return ob_get_clean();
    }
  }
?>
