<?php
  class Heinens2022ComplianceProgram extends ComplianceProgram {
  public function getAdminProgramReportPrinter() {
    $printer = new BasicComplianceProgramAdminReportPrinter();

    $printer->setShowUserContactFields(null, null, true);
    $printer->setShowCompliant(false, null, null);
    $printer->addEndStatusFieldCallBack('Total Premium Earned', function(ComplianceProgramStatus $status) {
        return $status->getPoints() . "%";
    });

    return $printer;
  }

  public function getProgramReportPrinter($preferredPrinter = null) {
    return new Heinens2022ComplianceProgramReportPrinter();
  }

  public function hasPartiallyCompliantStatus() {
    return false;
  }

  public function loadGroups() {
      $programStart = $this->getStartDate();
      $programEnd = $this->getEndDate();

      $group = new ComplianceViewGroup('screening_incentive', 'Report Card');
      $group->setPointsRequiredForCompliance(50);

      $affidavitView = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $affidavitView->setName('affidavit_response');
      $affidavitView->setReportName('Tobacco Affidavit Response');
      $affidavitView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
        $affidavit_record = UserDataRecord::getNewestRecord($user, 'heinens_tobacco_2022', true);
        $accepted = $affidavit_record->getDataFieldValue("smoker") === "1";
        $denied = $affidavit_record->getDataFieldValue("smoker") === "0";

        if ($denied) {
          $status->setPoints(20);
          $status->setStatus(ComplianceStatus::COMPLIANT);
          $status->setComment("Negative");
        } else if ($accepted)
          $status->setComment("Positive");
        else
          $status->setComment("Not Taken");
      });
      $group->addComplianceView($affidavitView);

      $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
      $hraView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(0, 0, 0, 0));
      $group->addComplianceView($hraView);

      $bmiView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd, true);
      $bmiView->overrideTestRowData(null, null, 26.9, null);
      $bmiView->emptyLinks();
      $bmiView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
      $bmiView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
        $screening = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
          $user,
          new DateTime("2022-01-01"),
          new DateTime("2022-03-18"),
          array(
            'fields'           => ["waist"],
            'merge'            => true,
            'require_complete' => false,
            'filter'           => null
          )
        );

        if (!empty($screening["waist"] ))
          if(($screening["waist"] <= 40) && $user->gender == "M") {
            $status->setPoints(10);
            $status->setStatus(ComplianceStatus::COMPLIANT);
          } else if (($screening["waist"] <= 35) && $user->gender == "F") {
            $status->setPoints(10);
            $status->setStatus(ComplianceStatus::COMPLIANT);
          }
      });

      $group->addComplianceView($bmiView);

      $cholesterolView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($programStart, $programEnd);
      $cholesterolView->setReportName('Cholesterol/HDL');
      $cholesterolView->overrideTestRowData(null, null, 4, null);
      $cholesterolView->emptyLinks();
      $cholesterolView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));

      $group->addComplianceView($cholesterolView);

      $triglyceridesView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
      $triglyceridesView->setReportName('Triglycerides/HDL');
      $triglyceridesView->overrideTestRowData(null, null, 3, null);
      $triglyceridesView->emptyLinks();
      $triglyceridesView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5, 0, 0, 0));
      $triglyceridesView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
          $screening = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
              $user,
              new DateTime("2022-01-01"),
              new DateTime("2022-03-18"),
              array(
                  'fields'           => ["hdl", "triglycerides"],
                  'merge'            => true,
                  'require_complete' => false,
                  'filter'           => null
              )
          );

          if (!empty($screening['hdl']) && !empty($screening['triglycerides']) && is_numeric($screening['hdl']) && is_numeric($screening['triglycerides'])) {
            $triHDLRatio = $screening['triglycerides'] / $screening['hdl'];

            if(!$status->getUsingOverride()) {
                if($triHDLRatio <= 3) {
                    $status->setPoints(5);
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } else {
                    $status->setPoints(0);
                    $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                }

                $status->setComment(round($triHDLRatio, 1));
            }


          } else {
              if(!$status->getUsingOverride()) {
                  $status->setComment("No Screening");
                  $status->setPoints(0);
                  $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
              }
          }
      });

      $group->addComplianceView($triglyceridesView);

      $ha1cView = new ComplyWithHa1cScreeningTestComplianceView($programStart, $programEnd);
      $ha1cView->overrideTestRowData(null, null, 5.6, null);
      $ha1cView->emptyLinks();
      $ha1cView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 0, 0, 0));
      $ha1cView->setStatusSummary(ComplianceStatus::COMPLIANT, '');

      $group->addComplianceView($ha1cView);

      $elearningView = new CompleteELearningGroupSet($programStart, "2022-05-31", 'tobacco');
      $elearningView->setReportName('Complete 6 Tobacco Related Elearning Lessons');
      $elearningView->setName('elearning_tobacco_alternative');
      $elearningView->setNumberRequired(6);
      $elearningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user){
        $lessons_completed = $status->getComment();

        $affidavit_record = UserDataRecord::getNewestRecord($user, 'heinens_tobacco_2022', true);
        $accepted = $affidavit_record->getDataFieldValue("smoker") == 1;

        if ($lessons_completed >= 6 && $accepted)
          $status->setPoints(20);
        else {
          $status->setPoints(0);
          $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }
      });
      $group->addComplianceView($elearningView);

      $this->addComplianceViewGroup($group);
    }
}

  class Heinens2022ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter {
    public function calc_result($value) {
      if ($value)
        return "Compliant";
      return "Not Compliant";
    }

    public function printReport(ComplianceProgramStatus $status) {
      $user = $status->getUser();
      $premium_earned = $status->getPoints();
      $affidavit_record = UserDataRecord::getNewestRecord($user, 'heinens_tobacco_2022', true);
      $accepted = $affidavit_record->getDataFieldValue("smoker") === "1";
      $denied = $affidavit_record->getDataFieldValue("smoker") === "0";
      $hra_status = $status->getComplianceViewStatus("complete_hra")->getStatus() == 4;
      $bmi_points = $status->getComplianceViewStatus("comply_with_bmi_screening_test")->getPoints();
      $bmi_result = $status->getComplianceViewStatus("comply_with_bmi_screening_test")->getComment();
      $bmi_status = $bmi_points == 10;
      $cholesterol_points = $status->getComplianceViewStatus("comply_with_total_hdl_cholesterol_ratio_screening_test")->getPoints();
      $cholesterol_result = $status->getComplianceViewStatus("comply_with_total_hdl_cholesterol_ratio_screening_test")->getComment();
      $cholesterol_status = $cholesterol_points == 5;
      $triglyceride_points = $status->getComplianceViewStatus("comply_with_triglycerides_screening_test")->getPoints();
      $triglyceride_result = $status->getComplianceViewStatus("comply_with_triglycerides_screening_test")->getComment();
      $triglyceride_override = $status->getComplianceViewStatus("comply_with_triglycerides_screening_test")->getAttribute("override");
      $triglyceride_status = $triglyceride_points == 5;
      $ha1c_points = $status->getComplianceViewStatus("comply_with_ha1c_screening_test")->getPoints();
      $ha1c_result = $status->getComplianceViewStatus("comply_with_ha1c_screening_test")->getComment();
      $ha1c_status = $ha1c_points == 10;
      $tobacco_points = $status->getComplianceViewStatus("elearning_tobacco_alternative")->getPoints();
      $tobacco_result = $status->getComplianceViewStatus("elearning_tobacco_alternative")->getComment();
      $tobacco_status = $tobacco_points == 20;

      ?>

      <style type="text/css">
        #wms1 {
          font-size: 15px;
        }

        .fs13 {
          font-size: 13px !important;
        }

        .bund {
          font-weight:bold;
          text-decoration:underline;
        }

        <?php if (!sfConfig::get('app_wms2')) : ?>
          .letter {
            font-family:Arial, sans-serif;
            font-size:11pt;
            width:8.5in;
            height:11in;
          }
        <?php endif ?>

        .light {
          width:0.3in;
        }

        .subtitle {
          padding-left: 20px;
        }

        #results {
          width:7.6in;
          margin:0 0.5in;
        }

        #results th, td {
          padding:0.01in 0.05in;
          border: none;
          padding: 1px;
        }

        .correct {
          color:#4CAF50;
        }

        .incorrect {
          color: #F44336;
        }

        .action_link {
          text-align: center;
        }

        .collapsible-points-report-card i {
          font-size: 20px;
        }

        .collapsible-points-report-card .open .triangle {
          display: none;
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

        .large {
          width: 190px;
        }
        .medium {
          width: 100px;
        }

        .pad20 {
          padding-left: 20px;
          padding-right: 20px;
        }

        .border-less td {
          border-top: 0px !important;
        }

        #content p {
          font-size: 15px !important;
        }

        .collapsible-points-report-card {
         width:100%;
         min-width:500px;
         border-collapse:separate;
         border-spacing:5px
        }
        .collapsible-points-report-card tr.picker {
         background-color:#efefef;
         padding:5px
        }
        .collapsible-points-report-card tr.picker td,
        .collapsible-points-report-card tr.picker td {
         padding:5px;
         border:2px solid transparent
        }
        .collapsible-points-report-card tr.picker .name {
         font-size:1.2em;
         position:relative
        }
        .collapsible-points-report-card tr.details {
         background-color:transparent
        }
        .collapsible-points-report-card .points {
         text-align:center;
         width:65px
        }
        .collapsible-points-report-card thead .target,
        .collapsible-points-report-card thead .actual,
        .collapsible-points-report-card thead .group-progress,
        .collapsible-points-report-card thead .item-progress {
         text-align:center
        }
        .collapsible-points-report-card tbody .target {
         background-color:#48c7e8;
         color:#FFF
        }
        .collapsible-points-report-card tbody .success {
         background-color:#73c26f;
         color:#FFF
        }
        .collapsible-points-report-card tbody .warning {
         background-color:#fdb73b;
         color:#FFF
        }
        .collapsible-points-report-card tbody .danger {
         background-color:#fd3b3b;
         color:#FFF
        }
        .collapsible-points-report-card .pct {
         width:30%
        }
        .collapsible-points-report-card .pgrs {
         height:50px;
         background-color:#CCC;
         position:relative
        }
        .collapsible-points-report-card .pgrs-tiny {
         height:10px;
         width:80%;
         margin:0 auto
        }
        .collapsible-points-report-card .pgrs .bar {
         position:absolute;
         top:0;
         left:0;
         bottom:0
        }
        .collapsible-points-report-card .triangle {
         position:absolute;
         right:15px;
         top:15px
        }
        .collapsible-points-report-card tr.details.closed {
         display:none
        }
        .collapsible-points-report-card tr.details.open {
         display:table-row
        }
        .collapsible-points-report-card tr.details>td {
         padding:25px
        }
        .collapsible-points-report-card .details-table {
         width:100%;
         border-collapse:separate;
         border-spacing:5px
        }
        .collapsible-points-report-card .details-table .actions {
         text-align:center
        }
        .collapsible-points-report-card .details-table thead th {
         background-color:inherit;
         color:inherit
        }
        .collapsible-points-report-card .details-table thead .name {
         width:40%
        }
        .collapsible-points-report-card .details-table thead .points {
         width:10%
        }
        .collapsible-points-report-card .details-table thead .item-progress {
         width:20%;
         text-align:center
        }
        .collapsible-points-report-card .details-table .name {
         width:200px
        }
        .collapsible-points-report-card .closed .triangle {
         width:0;
         height:0;
         border-style:solid;
         border-width:12.5px 0 12.5px 21.7px;
         border-color:transparent transparent transparent #48c8e8
        }
        .collapsible-points-report-card .open .triangle {
         width:0;
         height:0;
         border-style:solid;
         border-width:21.7px 12.5px 0 12.5px;
         border-color:#48c8e8 transparent transparent transparent
        }
        .collapsible-points-report-card tr.picker:hover {
         cursor:pointer
        }
        .collapsible-points-report-card tr.picker:hover td {
         border-color:#48c8e8
        }

        .table {
         border-collapse:collapse !important
        }
        .table-bordered th,
        .table-bordered td {
         border:1px solid #ddd !important
        }

        .table {
         width:100%;
         max-width:100%;
         margin-bottom:25px
        }
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
         padding:8px;
         line-height:1.846;
         vertical-align:top;
         border-top:1px solid #ddd
        }
        .table>thead>tr>th {
         vertical-align:bottom;
         border-bottom:2px solid #ddd
        }
        .table>caption+thead>tr:first-child>th,
        .table>colgroup+thead>tr:first-child>th,
        .table>thead:first-child>tr:first-child>th,
        .table>caption+thead>tr:first-child>td,
        .table>colgroup+thead>tr:first-child>td,
        .table>thead:first-child>tr:first-child>td {
         border-top:0
        }
        .table>tbody+tbody {
         border-top:2px solid #ddd
        }
        .table .table {
         background-color:#fff
        }
        .table-condensed>thead>tr>th,
        .table-condensed>tbody>tr>th,
        .table-condensed>tfoot>tr>th,
        .table-condensed>thead>tr>td,
        .table-condensed>tbody>tr>td,
        .table-condensed>tfoot>tr>td {
         padding:5px
        }
        .table-bordered {
         border:1px solid #ddd
        }
        .table-bordered>thead>tr>th,
        .table-bordered>tbody>tr>th,
        .table-bordered>tfoot>tr>th,
        .table-bordered>thead>tr>td,
        .table-bordered>tbody>tr>td,
        .table-bordered>tfoot>tr>td {
         border:1px solid #ddd
        }
        .table-bordered>thead>tr>th,
        .table-bordered>thead>tr>td {
         border-bottom-width:2px
        }
        .table-striped>tbody>tr:nth-of-type(odd) {
         background-color:#f9f9f9
        }
        .table-hover>tbody>tr:hover {
         background-color:#f5f5f5
        }
        table col[class*="col-"] {
         position:static;
         float:none;
         display:table-column
        }
        table td[class*="col-"],
        table th[class*="col-"] {
         position:static;
         float:none;
         display:table-cell
        }
        .table>thead>tr>td.active,
        .table>tbody>tr>td.active,
        .table>tfoot>tr>td.active,
        .table>thead>tr>th.active,
        .table>tbody>tr>th.active,
        .table>tfoot>tr>th.active,
        .table>thead>tr.active>td,
        .table>tbody>tr.active>td,
        .table>tfoot>tr.active>td,
        .table>thead>tr.active>th,
        .table>tbody>tr.active>th,
        .table>tfoot>tr.active>th {
         background-color:#f5f5f5
        }
        .table-hover>tbody>tr>td.active:hover,
        .table-hover>tbody>tr>th.active:hover,
        .table-hover>tbody>tr.active:hover>td,
        .table-hover>tbody>tr:hover>.active,
        .table-hover>tbody>tr.active:hover>th {
         background-color:#e8e8e8
        }
        .table>thead>tr>td.success,
        .table>tbody>tr>td.success,
        .table>tfoot>tr>td.success,
        .table>thead>tr>th.success,
        .table>tbody>tr>th.success,
        .table>tfoot>tr>th.success,
        .table>thead>tr.success>td,
        .table>tbody>tr.success>td,
        .table>tfoot>tr.success>td,
        .table>thead>tr.success>th,
        .table>tbody>tr.success>th,
        .table>tfoot>tr.success>th {
         background-color:#dff0d8
        }
        .table-hover>tbody>tr>td.success:hover,
        .table-hover>tbody>tr>th.success:hover,
        .table-hover>tbody>tr.success:hover>td,
        .table-hover>tbody>tr:hover>.success,
        .table-hover>tbody>tr.success:hover>th {
         background-color:#d0e9c6
        }
        .table>thead>tr>td.info,
        .table>tbody>tr>td.info,
        .table>tfoot>tr>td.info,
        .table>thead>tr>th.info,
        .table>tbody>tr>th.info,
        .table>tfoot>tr>th.info,
        .table>thead>tr.info>td,
        .table>tbody>tr.info>td,
        .table>tfoot>tr.info>td,
        .table>thead>tr.info>th,
        .table>tbody>tr.info>th,
        .table>tfoot>tr.info>th {
         background-color:#e1bee7
        }
        .table-hover>tbody>tr>td.info:hover,
        .table-hover>tbody>tr>th.info:hover,
        .table-hover>tbody>tr.info:hover>td,
        .table-hover>tbody>tr:hover>.info,
        .table-hover>tbody>tr.info:hover>th {
         background-color:#d8abe0
        }
        .table>thead>tr>td.warning,
        .table>tbody>tr>td.warning,
        .table>tfoot>tr>td.warning,
        .table>thead>tr>th.warning,
        .table>tbody>tr>th.warning,
        .table>tfoot>tr>th.warning,
        .table>thead>tr.warning>td,
        .table>tbody>tr.warning>td,
        .table>tfoot>tr.warning>td,
        .table>thead>tr.warning>th,
        .table>tbody>tr.warning>th,
        .table>tfoot>tr.warning>th {
         background-color:#ffe0b2
        }
        .table-hover>tbody>tr>td.warning:hover,
        .table-hover>tbody>tr>th.warning:hover,
        .table-hover>tbody>tr.warning:hover>td,
        .table-hover>tbody>tr:hover>.warning,
        .table-hover>tbody>tr.warning:hover>th {
         background-color:#ffd699
        }
        .table>thead>tr>td.danger,
        .table>tbody>tr>td.danger,
        .table>tfoot>tr>td.danger,
        .table>thead>tr>th.danger,
        .table>tbody>tr>th.danger,
        .table>tfoot>tr>th.danger,
        .table>thead>tr.danger>td,
        .table>tbody>tr.danger>td,
        .table>tfoot>tr.danger>td,
        .table>thead>tr.danger>th,
        .table>tbody>tr.danger>th,
        .table>tfoot>tr.danger>th {
         background-color:#f9bdbb
        }
        .table-hover>tbody>tr>td.danger:hover,
        .table-hover>tbody>tr>th.danger:hover,
        .table-hover>tbody>tr.danger:hover>td,
        .table-hover>tbody>tr:hover>.danger,
        .table-hover>tbody>tr.danger:hover>th {
         background-color:#f7a6a4
        }
        .table-responsive {
         overflow-x:auto;
         min-height:.01%
        }
        @media screen and (max-width:767px) {
         .table-responsive {
          width:100%;
          margin-bottom:18.75px;
          overflow-y:hidden;
          -ms-overflow-style:-ms-autohiding-scrollbar;
          border:1px solid #ddd
         }
         .table-responsive>.table {
          margin-bottom:0
         }
         .table-responsive>.table>thead>tr>th,
         .table-responsive>.table>tbody>tr>th,
         .table-responsive>.table>tfoot>tr>th,
         .table-responsive>.table>thead>tr>td,
         .table-responsive>.table>tbody>tr>td,
         .table-responsive>.table>tfoot>tr>td {
          white-space:nowrap
         }
         .table-responsive>.table-bordered {
          border:0
         }
         .table-responsive>.table-bordered>thead>tr>th:first-child,
         .table-responsive>.table-bordered>tbody>tr>th:first-child,
         .table-responsive>.table-bordered>tfoot>tr>th:first-child,
         .table-responsive>.table-bordered>thead>tr>td:first-child,
         .table-responsive>.table-bordered>tbody>tr>td:first-child,
         .table-responsive>.table-bordered>tfoot>tr>td:first-child {
          border-left:0
         }
         .table-responsive>.table-bordered>thead>tr>th:last-child,
         .table-responsive>.table-bordered>tbody>tr>th:last-child,
         .table-responsive>.table-bordered>tfoot>tr>th:last-child,
         .table-responsive>.table-bordered>thead>tr>td:last-child,
         .table-responsive>.table-bordered>tbody>tr>td:last-child,
         .table-responsive>.table-bordered>tfoot>tr>td:last-child {
          border-right:0
         }
         .table-responsive>.table-bordered>tbody>tr:last-child>th,
         .table-responsive>.table-bordered>tfoot>tr:last-child>th,
         .table-responsive>.table-bordered>tbody>tr:last-child>td,
         .table-responsive>.table-bordered>tfoot>tr:last-child>td {
          border-bottom:0
         }
        }
      </style>

      <style type="text/css" media="print">
          body {
            margin:0.5in;
            padding:0;
          }
      </style>

      <div class="letter">
        <img src="https://master.hpn.com/resources/10514/HeinensLogo.jpg" style="max-width: 200px; display: block; margin: auto;margin-top:20px; margin-bottom: 20px;">

        <div class="pad20">
          <p>
            Dear <?= $user->first_name ?> <?= $user->last_name ?>,
          </p>
          <p>
            Let's get healthy, Heinen's! All associates and spouses
            can complete the Wellness Biometric Screening to earn up
            to a 50% contribution rate reduction for medical
            coverage in the 2022-2023 benefit year.
          </p>
          <p>
            The overall percentage of the premium for both the
            associate and spouse will be averaged together to
            determine the couple's total percentage of premium
            reduction. For example, an associate achieving three (3)
            healthy ranges for 20% , and a spouse achieving one (1)
            healthy range for 5%, will receive a 12.5% premium
            incentive (20% + 5% = 25%/2 = 12.5%).
          </p>
          <p>
            Please see the Program Guide for three (3) appeals
            options if you did not meet your healthy range goals.
          </p>
          <p>Here are your results.</p>
        </div>

        <div>
          <table id="heinens-table" class="collapsible-points-report-card">
            <thead>
              <tr>
                <th class="name"></th>
                <th class="points target">Earned</th>
                <th class="points actual">Possible</th>
                <th class="group-progress">Progress</th>
              </tr>
            </thead>

            <tbody>
              <tr class="picker open" id="core-actions">
                <td class="name">
                  Premium Earned
                  <div class="triangle"></div>
                </td>

                <td class="points target">
                  <strong><?= $premium_earned?></strong>%
                </td>

                <td class="points actual warning">
                  <strong>50</strong>%
                </td>

                <td class="group-progress">
                  <div class="pgrs">
                    <div class="bar success" style="width:<?= $premium_earned*2?>%;">
                    </div>
                  </div>
                </td>
              </tr>

              <tr class="details open" id="core-actions-details">
                <td colspan="4">
                  <div>
                    <table id="screening_results" class="table table-condensed">
                      <thead>
                        <tr>
                          <th style="text-align: left;">1. Screening Metric</th>
                          <th class="text-center">
                            Employer Goal
                          </th>
                          <th class="text-center">Result</th>
                          <th class="text-center medium">
                            Status
                          </th>
                          <th class="text-center large">
                            Premium Earned If Met
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>
                            <span class="item-title subtitle">
                              a. Body Mass Index (BMI)*
                            </span>
                          </td>
                          <td class="text-center">
                            <i class="fs13 fal fa-less-than-equal">
                            </i>
                            26.9
                          </td>
                          <td class="text-center">
                            <?= $bmi_result?>
                          </td>
                          <?php if ($bmi_status) :?>
                            <td class="text-center correct">
                              <i class="far fa-check"></i>
                            </td>
                          <?php else: ?>
                            <td class="text-center incorrect">
                              <i class="far fa-times"></i>
                            </td>
                          <?php endif; ?>
                          <td class="action_link">10%</td>
                        </tr>
                        <tr>
                          <td>
                            <span class="item-title subtitle">
                              b. Total Cholesterol/HDL
                            </span>
                          </td>
                          <td class="text-center">
                            <i class="fs13 fal fa-less-than-equal">
                            </i>
                            4.0
                          </td>
                          <td class="text-center">
                            <?= $cholesterol_result?>
                          </td>
                          <?php if ($cholesterol_status) :?>
                            <td class="text-center correct">
                              <i class="far fa-check"></i>
                            </td>
                          <?php else: ?>
                            <td class="text-center incorrect">
                              <i class="far fa-times"></i>
                            </td>
                          <?php endif; ?>
                          <td class="action_link">5%</td>
                        </tr>
                        <tr>
                          <td>
                            <span class="item-title subtitle">
                              c. Triglycerides/HDL
                            </span>
                          </td>
                          <td class="text-center">
                            <i class="fs13 fal fa-less-than-equal">
                            </i>
                            3.0
                          </td>
                          <td class="text-center">
                            <?= $triglyceride_result?>
                          </td>
                          <?php if ($triglyceride_status) :?>
                            <td class="text-center correct">
                              <i class="far fa-check"></i>
                            </td>
                          <?php else: ?>
                            <td class="text-center incorrect">
                              <i class="far fa-times"></i>
                            </td>
                          <?php endif; ?>
                          <td class="action_link">5%</td>
                        </tr>
                        <tr>
                          <td>
                            <span class="item-title subtitle">
                              d. A1C
                            </span>
                          </td>
                          <td class="text-center">
                            <i class="fs13 fal fa-less-than-equal">
                            </i>
                            5.6
                          </td>
                          <td class="text-center">
                            <?= $ha1c_result?>
                          </td>
                          <?php if ($ha1c_status) :?>
                            <td class="text-center correct">
                              <i class="far fa-check"></i>
                            </td>
                          <?php else: ?>
                            <td class="text-center incorrect">
                              <i class="far fa-times"></i>
                            </td>
                          <?php endif; ?>
                          <td class="action_link">10%</td>
                        </tr>
                        <tr class="ajax_hide">
                          <td>
                            <span class="item-title subtitle">
                              e. Tobacco/Nicotine
                            </span>
                          </td>
                          <td class="text-center">Negative</td>
                          <td class="text-center">
                            <?php
                              if ($accepted)
                                echo 'Positive';
                              elseif ($denied)
                                echo 'Negative';
                            ?>
                          </td>
                          <?php if ($accepted) :?>
                            <td class="text-center incorrect">
                              <i class="far fa-times"></i>
                            </td>
                          <?php elseif($denied): ?>
                            <td class="text-center correct">
                              <i class="far fa-check"></i>
                            </td>
                          <?php else: ?>
                            <td class="text-center"></td>
                          <?php endif; ?>
                          <td class="action_link">20%</td>
                        </tr>
                      </tbody>
                    </table>
                    <table class="table table-condensed">
                      <thead>
                        <tr>
                          <th>2. Tobacco Affidavit</th>
                          <th class="text-center medium">
                            Completed
                          </th>
                          <th class="text-center large">Link</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>
                            <span class="item-title">
                              Complete Tobacco Affidavit
                            </span>
                          </td>
                          <?php if ($accepted || $denied) :?>
                            <td class="text-center correct">
                              <i class="far fa-check"></i>
                            </td>
                          <?php else: ?>
                            <td class="text-center incorrect">
                              <i class="far fa-times"></i>
                            </td>
                          <?php endif; ?>
                          <td class="action_link">
                            <a href="/content/heinens-tobacco-affidavit?year=2022">
                              Tobacco Affidavit
                            </a>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <?php if ($accepted): ?>
                      <table class="table table-condensed">
                        <thead>
                          <tr>
                            <th>3. Tobacco/Nicotine Alternative</th>
                            <th class="text-center medium">
                              Completed
                            </th>
                            <th class="text-center large">Link</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td>
                              <span class="item-title">
                                If a tobacco user & would like the
                                20% premium incentive you must
                                complete six (6) E-Learning Lessons
                                from the Tobacco & Nicotine section
                              </span>
                            </td>
                            <?php if ($tobacco_status) :?>
                              <td class="text-center correct">
                                <i class="far fa-check"></i>
                              </td>
                            <?php elseif ($tobacco_result > 0): ?>
                              <td class="text-center">
                                <?= $tobacco_result?> out of 6
                              </td>
                            <?php else: ?>
                              <td class="text-center incorrect">
                                <i class="far fa-times"></i>
                              </td>
                            <?php endif; ?>
                            <td class="action_link">
                              <a href="/content/9420?action=lessonManager&tab_alias=tobacco">
                                Complete Lessons
                              </a>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    <?php endif;?>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div style="padding: 20px; padding-top: 0px;">
            <p>
              <span class="bund">RESULTS APPEALS PROCESS</span>

              <br />

              If you would like to participate in the Appeals
              process, please take
              <a href="pdf/clients/heinens/Appeals_Form_2022.pdf" target="_blank">
                this form</a>
              to be completed and signed by your primary care
              physician. You can fax the completed form or submit an
              electric copy which you can upload
              <a href="https://public.gwapps.com/f/ehs_upload">here</a>.
            </p>
            <p>
              *Waist measurement automatically corrects elevated BMI
              due to lean muscle mass, even if the individual fails
              the BMI goal. (Female less than or equal to 35 inches.
              Male less than or equal to 40 inches.)
            </p>
          </div>
        </div>
      </div>

      <?php
    }
  }
?>
