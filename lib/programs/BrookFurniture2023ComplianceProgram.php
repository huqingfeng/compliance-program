<?php
  use hpn\steel\query\SelectQuery;

  class BrookFurniture2023ComplianceProgram extends ComplianceProgram {
    public function getAdminProgramReportPrinter() {
      $printer = new BasicComplianceProgramAdminReportPrinter();

      $printer->setShowUserContactFields(null, null, true);

      $printer->setShowCompliant(false, null, null);

      $printer->addEndStatusFieldCallBack('Compliance Program - Compliant', function(ComplianceProgramStatus $status) {
        $numberCompliant = $status->getComplianceViewGroupStatus("healthy_measures")->getPoints();

        if($numberCompliant >= 4)
          return 'Yes';
        else
          return 'No';
      });


      $printer->addEndStatusFieldCallBack('Compliance Program - Num of Compliant', function(ComplianceProgramStatus $status) {
        $numberCompliant = $status->getComplianceViewGroupStatus("healthy_measures")->getPoints();

        return $numberCompliant;
      });

      return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null) {
      return new BrookFurniture2023ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus() {
      return false;
    }

    public function loadGroups() {
      $programStart = $this->getStartDate();
      $programEnd = $this->getEndDate();

      $group = new ComplianceViewGroup('healthy_measures', 'Health Assessment');
      $group->setPointsRequiredForCompliance(4);

      $hdlView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
      $hdlView->setReportName('Cholesterol/HDL Ratio');
      $hdlView->overrideTestRowData(null, null, 4.9, null);
      $hdlView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 4.9');
      $hdlView->emptyLinks();
      $hdlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $hdlView->setAttribute('screening_view', true);

      $group->addComplianceView($hdlView);

      $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
      $ldlView->overrideTestRowData(null, null, 129, null);
      $ldlView->emptyLinks();
      $ldlView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $ldlView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 129');
      $ldlView->setAttribute('screening_view', true);
      $group->addComplianceView($ldlView);

      $triView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
      $triView->overrideTestRowData(null, null, 149, null);
      $triView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 149');
      $triView->emptyLinks();
      $triView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $triView->setAttribute('screening_view', true);
      $group->addComplianceView($triView);

      $gluView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
      $gluView->setReportName('Blood Glucose');
      $gluView->overrideTestRowData(null, null, 99, null);
      $gluView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 99');
      $gluView->emptyLinks();
      $gluView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $gluView->setAttribute('screening_view', true);
      $group->addComplianceView($gluView);

      $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
      $bmiView->setReportName('BMI');
      $bmiView->setStatusSummary(ComplianceStatus::COMPLIANT, '≤ 30');
      $bmiView->overrideTestRowData(null, null, 30, null );
      $bmiView->emptyLinks();
      $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $bmiView->setAttribute('screening_view', true);
      $bmiView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
        if ($status->getComment() == "No Screening" || $status->getComment() == "Test Not Taken") {
          $hra = SelectQuery::create()
            ->select('height_text as height, weight_text as weight')
            ->from('hra')
            ->where('user_id = ?', array($user->id))
            ->andWhere('date BETWEEN ? and ?', array(date("Y-m-d", $this->getStartDate()), date("Y-m-d", $this->getEndDate())))
            ->limit(1)
            ->execute()->toArray();

          if(isset($hra[0])) {
            $hra = $hra[0];

            if (is_numeric($hra["weight"]) && is_numeric($hra["height"])) {
              $bmi = number_format(($hra["weight"] / ($hra["height"]*$hra["height"])) * 703, 2);

              $status->setComment($bmi);

              if ($bmi <= 30)
                $status->setStatus(ComplianceStatus::COMPLIANT);
              else
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
          }
        }
      });
      $group->addComplianceView($bmiView);

      $cotinineView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
      $cotinineView->setReportName('Cotinine');
      $cotinineView->setName('cotinine');
      $cotinineView->setStatusSummary(ComplianceStatus::COMPLIANT, '');
      $cotinineView->emptyLinks();
      $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $cotinineView->setAttribute('screening_view', true);
      $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
        if($status->getStatus() == ComplianceViewStatus::COMPLIANT)
          $status->setComment('Pass');
        else
          $status->setComment('Fail');
      });
      $group->addComplianceView($cotinineView);

      $this->addComplianceViewGroup($group);
    }
  }

  class BrookFurniture2023ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter {
    public function printReport(ComplianceProgramStatus $status) {
      $user = $status->getUser();

      if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter')
        return;

      $core_points = $status->getComplianceViewGroupStatus("healthy_measures")->getPoints();

      ?>

      <style type="text/css">
        .bund {
          font-weight:bold;
        }

        .red_text {
          color: red;
          font-weight: normal;
          font-style: italic;
        }

        .light {
          width:0.3in;
        }

        #results {
          width:7.6in;
          margin:0 0.5in;
        }

        #results th, td {
          padding:0.01in 0.05in;
          border:0.01in solid #000;
          text-align:center;
          padding: 1px;
        }

        #results th {
          background-color:#FFFFFF;
        }

        #results .status-<?= ComplianceStatus::COMPLIANT ?> {
          background-color:#90FF8C;
        }

        #results .status-<?= ComplianceStatus::PARTIALLY_COMPLIANT ?> {
          background-color:#F9FF8C;
        }

        #results .status-<?= ComplianceStatus::NOT_COMPLIANT ?> {
          background-color:#DEDEDE;
        }

        #not_compliant_notes p{
          margin: 5px 0;
        }
      </style>

      <style type="text/css" media="print">
        body {
          margin:0.5in;
          padding:0;
        }
      </style>

      <div class="letter">
        <p style="text-align:center;font-size:18pt;font-weight:bold;">
          Health Assessment
        </p>

        <p style="margin-top:0.5in; margin-left:0.75in;">
          <?php if (sfConfig::get('app_wms2')) : ?>
            <br/> <br/> <br/>
          <?php endif ?>
         <br/> <br/> <br/>

          <?= $user->getFullName() ?> <br/>
          <?= $user->getFullAddress("<br/>") ?>
        </p>

        <p>&nbsp;</p>
        <p>&nbsp;</p>

        <p>Dear <?= $user->first_name . " " . $user->last_name ?>,</p>

        <p>
          Thank you for participating in the Wellness Screening. In partnership
          with Health Maintenance Institute, Brook Furniture Rental has selected
          six “Health Standards” for you to strive to achieve.
        </p>

        <p>
          For participating in the wellness screenings, you have earned the
          <strong>Wellness Participant Discount</strong> and will pay less for
          medical coverage during 2023.
        </p>

        <p>
          In addition, Team members that <strong>meet 4 of the 6 Health
          Standards outlined below</strong>, will <strong>also receive the
          Wellness Healthy Discount</strong> and earn the <strong>maximum
          discount</strong> available for your 2023 medical premiums.
        </p>

        <?= $this->getTable($status) ?>

        <?php if($core_points >= 4) : ?>
          <p class="bund">RESULTS:</p>

          <p>
            CONGRATULATIONS! You have earned the <strong>Healthy Wellness
            Discount for 2023</strong>. Based on your wellness screening
            results, you met at least four of the required Health Standards.
          </p>

          <p>No further action is required on your part. </p>
        <?php else : ?>
          <div id="not_compliant_notes">
            <p class="bund">RESULTS:</p>

            <p>
              Based on your results of the wellness screening results, you
              <strong>DID NOT meet at least 4 of the 6 Health Standards.</strong>
              You may still earn the <strong>Wellness Healthy Discount</strong>
              by completing the <strong>ALTERNATE PROCESS</strong> requirements.
            </p>

            <p class="bund">ALTERNATE QUALIFIER:</p>

            <p style="color:red">
              Complete Alternate Qualification Form & submit to HMI by November
              18, 2022 or within 30 days of hire date:
            </p>

            <p>
              Team members that satisfy the designated ‘Alternate Process’
              requirement
              <a href="<?= sfConfig::get('app_wms2') ? '/wms1' : '' ?>/pdf/clients/brook_furniture/2022_AQF.pdf">and submit the qualification form</a>
              will also earn the Wellness Healthy Discount. To earn the
              <strong>Wellness Healthy Discount</strong>, Health Maintenance
              Institute (HMI) <strong>must</strong> have documentation from your
              Physician acknowledging the "Health Standard" areas of concern.
              Please follow-up with your Physician and submit the Alternate
              Qualification Form included in your results packet,
              <span style="color:red">complete with your Physician's signature</span>.
              If your completed form is not received by HMI on or before
              November 18, 2022 or within 30 days of hire date, you will NOT
              qualify for the <strong>Wellness Healthy Discount</strong>.
            </p>
          </div>
        <?php endif ?>

        <p>&nbsp;</p>
      </div>

      <?php
    }

    private function getTable($status) {
      ob_start();

      ?>

      <p style="text-align:center">
        <table id="results">
          <thead>
            <tr>
              <th>Health Standard</th>
              <th>Acceptable Range</th>
              <th>Your Result</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($status->getComplianceViewGroupStatus('healthy_measures')->getComplianceViewStatuses() as $viewStatus) : ?>
              <tr class="status-<?= $viewStatus->getStatus() ?>">
                <td><?= $viewStatus->getComplianceView()->getReportName() ?></td>
                <td>
                  <?= $viewStatus->getComplianceView()->getStatusSummary(ComplianceStatus::COMPLIANT) ?>
                </td>
                <td class="your-result">
                  <img src="<?= $viewStatus->getLight() ?>" class="light" />
                  <?= $viewStatus->getComment() ?>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </p>

      <?php

      return ob_get_clean();
    }
  }
