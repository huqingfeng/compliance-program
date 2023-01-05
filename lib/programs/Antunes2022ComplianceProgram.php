<?php
  define("ANTUNES_ACTIVITY_RECORD",  "antunes_activities_2022");

  class Antunes2022ComplianceProgram extends ComplianceProgram {
    public function loadEvaluators() { }

    public function getActivity(User $user) {
      $record = UserDataRecord::getNewestRecord($user, ANTUNES_ACTIVITY_RECORD, true);

      return $record;
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status) {
      $user = $status->getUser();

      $dateFormat = "m/d/Y";
      $activityRecord = $this->getActivity($user);

      $screening = $status->getComplianceViewStatus("complete_screening");
      $screeningDate = $screening->getComment();
      $screening = $screening->getStatus();

      $quarter1 = $status->getComplianceViewStatus("quarter1");

      $quarter1View = $quarter1->getComplianceView();
      $quarter1Activity = $activityRecord->getDataFieldValue("quarter1Activity");
      $quarter1Date = $activityRecord->getDataFieldValue("quarter1Date");
      $quarter1Comment = $activityRecord->getDataFieldValue("quarter1Comment");

      if (!empty($quarter1Activity)) {
        $quarter1View->setReportName("Quarter 1 - " . $quarter1Activity);
        $quarter1View->setAttribute("comment", $quarter1Comment);
        $quarter1->setComment(date($dateFormat,strtotime($quarter1Date)));
        $quarter1->setAttribute('activity', $quarter1Activity);
        $quarter1->setStatus(ComplianceStatus::COMPLIANT);
      }

      $quarter2 = $status->getComplianceViewStatus("quarter2");
      $quarter2View = $quarter2->getComplianceView();
      $quarter2Activity = $activityRecord->getDataFieldValue("quarter2Activity");
      $quarter2Date = $activityRecord->getDataFieldValue("quarter2Date");
      $quarter2Comment = $activityRecord->getDataFieldValue("quarter2Comment");

      if (!empty($quarter2Activity)) {
        $quarter2View->setReportName("Quarter 2 - " . $quarter2Activity);
        $quarter2View->setAttribute("comment", $quarter2Comment);
        $quarter2->setComment(date($dateFormat,strtotime($quarter2Date)));
        $quarter2->setAttribute('activity', $quarter2Activity);
        $quarter2->setStatus(ComplianceStatus::COMPLIANT);
      }


      $quarter3 = $status->getComplianceViewStatus("quarter3");
      $quarter3View = $quarter3->getComplianceView();
      $quarter3Activity = $activityRecord->getDataFieldValue("quarter3Activity");
      $quarter3Date = $activityRecord->getDataFieldValue("quarter3Date");
      $quarter3Comment = $activityRecord->getDataFieldValue("quarter3Comment");

      if (!empty($quarter3Activity)) {
          $quarter3View->setReportName("Quarter 3 - " . $quarter3Activity);
          $quarter3View->setAttribute("comment", $quarter3Comment);
          $quarter3->setComment(date($dateFormat,strtotime($quarter3Date)));
          $quarter3->setAttribute('activity', $quarter3Activity);
          $quarter3->setStatus(ComplianceStatus::COMPLIANT);
        }

      $quarter4 = $status->getComplianceViewStatus("quarter4");

      $quarter4View = $quarter4->getComplianceView();
      $quarter4Activity = $activityRecord->getDataFieldValue("quarter4Activity");
      $quarter4Date = $activityRecord->getDataFieldValue("quarter4Date");
      $quarter4Comment = $activityRecord->getDataFieldValue("quarter4Comment");

      if (!empty($quarter4Activity)) {
        $quarter4View->setReportName("Quarter 4 - " . $quarter4Activity);
        $quarter4View->setAttribute("comment", $quarter4Comment);
        $quarter4->setComment(date($dateFormat,strtotime($quarter4Date)));
        $quarter4->setAttribute('activity', $quarter4Activity);
        $quarter4->setStatus(ComplianceStatus::COMPLIANT);
      }

      $overall = $status->getComplianceViewStatus("overview");

      $quarter1Status = $quarter1->getStatus();
      $quarter2Status = $quarter2->getStatus();
      $quarter3Status = $quarter3->getStatus();
      $quarter4Status = $quarter4->getStatus();

      $mostRecent = null;
      $dates = [strtotime($screeningDate), strtotime($quarter1Date), strtotime($quarter2Date), strtotime($quarter3Date), strtotime($quarter4Date)];
      foreach($dates as $date){
        $curDate = $date;
        if ($curDate > $mostRecent)
          $mostRecent = $curDate;
      }

      if ($screening==ComplianceStatus::COMPLIANT &&
          $quarter1Status==ComplianceStatus::COMPLIANT &&
          $quarter2Status==ComplianceStatus::COMPLIANT &&
          $quarter3Status==ComplianceStatus::COMPLIANT &&
          $quarter4Status==ComplianceStatus::COMPLIANT) {

          $overall->setStatus(ComplianceStatus::COMPLIANT);
          $overall->setComment(date($dateFormat,$mostRecent));
        }
    }

    public function loadGroups() {
      $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

      $startDate = $this->getStartDate();
      $endDate = $this->getEndDate();

      $screening = new ComplianceViewGroup('Screening');

      $screeningView = new CompleteScreeningComplianceView($startDate, $endDate);
      $screeningView->setReportName('Biometric Screening');
      $screeningView->emptyLinks();
      $screeningView->addLink(new Link('Results', '/compliance/antunes2018/my-health/content/my-health'));

      $screening->addComplianceView($screeningView);

      $this->addComplianceViewGroup($screening);

      $wellnessEvents = new ComplianceViewGroup('Wellness Events');

      $quarter1 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $quarter1->setName('quarter1');
      $quarter1->setReportName('Quarter 1');
      $quarter1->setAttribute("original_report_name", 'Quarter 1');
      $quarter1->addLink(new Link('Log Activity', '/content/antunes_activities?year=2022'));
      $wellnessEvents->addComplianceView($quarter1);

      $quarter2 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $quarter2->setName('quarter2');
      $quarter2->setReportName('Quarter 2');
      $quarter2->setAttribute("original_report_name", 'Quarter 2');
      $quarter2->addLink(new Link('Log Activity', '/content/antunes_activities?year=2022'));
      $wellnessEvents->addComplianceView($quarter2);

      $quarter3 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $quarter3->setName('quarter3');
      $quarter3->setReportName('Quarter 3');
      $quarter3->setAttribute("original_report_name", 'Quarter 3');
      $quarter3->addLink(new Link('Log Activity', '/content/antunes_activities?year=2022'));
      $wellnessEvents->addComplianceView($quarter3);

      $quarter4 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $quarter4->setName('quarter4');
      $quarter4->setReportName('Quarter 4');
      $quarter4->setAttribute("original_report_name", 'Quarter 4');
      $quarter4->addLink(new Link('Log Activity', '/content/antunes_activities?year=2022'));
      $wellnessEvents->addComplianceView($quarter4);

      $this->addComplianceViewGroup($wellnessEvents);

      $summary = new ComplianceViewGroup('Overview');

      $overview = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $overview->setName('overview');
      $overview->setReportName('Biometric Screening + Quarterly Wellness Events');
      $summary->addComplianceView($overview);

      $this->addComplianceViewGroup($summary);
    }

    public function getAdminProgramReportPrinter() {
      $printer = new BasicComplianceProgramAdminReportPrinter();

      $printer->setShowCompliant(false, false, false);
      $printer->setShowStatus(false,false,false);
      $printer->setShowPoints(false,false,false);
      $printer->setShowComment(false,false,false);

      $printer->addCallbackField('employee_id', function (User $user) {
        return $user->employeeid;
      });

      $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
        $data = array();

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus)
          foreach($groupStatus->getComplianceViewStatuses() as $viewStatus)
            if($groupStatus->getComplianceViewGroup()->getName() == 'Wellness Events') {
              $viewName = $viewStatus->getComplianceView()->getAttribute('original_report_name');
              $data[sprintf('%s - Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
              $data[sprintf('%s - Activity', $viewName)] = $viewStatus->getAttribute('activity');
              $data[sprintf('%s - Date', $viewName)] = $viewStatus->getComment();
            } else {
              $viewName = $viewStatus->getComplianceView()->getReportName();
              $data[sprintf('%s - Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
              $data[sprintf('%s - Comment', $viewName)] = $viewStatus->getComment();
            }

        $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

        return $data;
      });

      return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null) {
      return new Antunes2022ComplianceProgramReportPrinter();
    }
  }

  class Antunes2022ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter {
    public function printReport(ComplianceProgramStatus $status) {
      $this->setShowLegend(true);
      $this->tableHeaders['completed'] = 'Date Done';
      $this->setShowTotal(false);

      $screening = $status->getComplianceViewStatus('complete_screening');
      $quarter1 = $status->getComplianceViewStatus('quarter1');
      $quarter2 = $status->getComplianceViewStatus('quarter2');
      $quarter3 = $status->getComplianceViewStatus('quarter3');
      $quarter4 = $status->getComplianceViewStatus('quarter4');
      $overview = $status->getComplianceViewStatus('overview');

      ?>

      <style type="text/css">
        #wms1 h1 {
          padding: 1rem;
          background-color: #0368a4;
          color: white;
          text-align: center;
          font-family: Roboto;
          font-size: 1.8rem;
          font-weight: bold;
          border-radius: 0.25rem;
        }

        #wms1 table {
          border-collapse: separate;
          table-layout: fixed;
          width: 100%;
          line-height: 1.5rem;
        }

        #wms1 table + table {
          margin-top: 1rem;
        }

        #wms1 th {
          padding: 1rem;
          background-color: #014265;
          color: white;
          border: 1px solid #014265;
          font-weight: bold;
          text-align: center;
        }

        #wms1 th:first-of-type {
          border-top-left-radius: 0.25rem;
          text-align: left;
        }

        #wms1 th:last-of-type {
          border-top-right-radius: 0.25rem;
        }

        #wms1 td {
          padding: 1rem;
          color: #57636e;
          border-left: 1px solid #e8e8e8;
          border-bottom: 1px solid #e8e8e8;
          text-align: center;
        }

        #wms1 td:first-of-type {
          border-bottom-left-radius: 0.25rem;
          font-weight: bold;
          text-align: left;
        }

        #wms1 td:last-of-type {
          border-bottom-right-radius: 0.25rem;
          border-right: 1px solid #e8e8e8;
        }

        #wms1 a {
          color: #0085f4;
          font-size: 1rem;
          text-transform: uppercase;
        }

        #wms1 a:hover, #wms1 a:focus, #wms1 a:active {
          color: #0052C1;
          text-decoration: none;
        }

        #wms1 i {
          width: 2.5rem;
          height: 2.5rem;
          line-height: 2.5rem;
          background-color: #ced2db;
          border-radius: 999px;
          color: white;
          font-size: 1.75rem;
        }

        #wms1 i.fa-check {
          background-color: #4fd3c2;
        }

        #wms1 i.fa-exclamation {
          background-color: #ffb65e;
        }

        #wms1 i.fa-times {
          background-color: #dd7370;
        }

        #legend {
          display: flex;
          justify-content: center;
        }

        #legend p {
          position: relative;
          width: 18rem;
          height: 3.5rem;
          line-height: 4rem;
          margin: 2rem 1rem;
          padding-left: 1rem;
          background-color: #ebf1fa;
          text-align: center;
          font-size: 1.5rem;
        }

        #legend i {
          position: absolute;
          left: 1rem;
          top: 50%;
          margin-top: -1.25rem;
        }

        @media only screen and (min-width: 769px) and (max-width: 1200px) {
          #legend p {
            padding-left: 2rem;
            font-size: 1.25rem;
          }
        }

        @media only screen and (max-width: 769px) {
          #legend {
            flex-wrap: wrap;
            margin: 1.5rem 0;
          }

          #legend p {
            width: 100%;
            margin: 0.5rem 0;
            padding: 0;
          }
        }

        @media only screen and (max-width: 550px) {
          #wms1 table {
            table-layout: auto;
          }
        }
      </style>

      <script type="text/javascript">
        $(function() {
          $('.panel-heading.responsive-panel-heading').remove();
        });
      </script>


      <h1>2022 WELLNESS PROGRAM</h1>

      <div id="legend">
        <p><i class="far fa-check"></i> CRITERIA MET</p>
        <p><i class="fas fa-exclamation"></i> IN PROGRESS</p>
        <p><i class="far fa-times"></i> NOT STARTED</p>
        <p><i class="far fa-minus"></i> N/A</p>
      </div>

      <table>
        <thead>
          <tr>
            <th colspan="4">1. Screening</th>
            <th>Date Done</th>
            <th>Status</th>
            <th>Links</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="4">A. Biometric Screening</td>
            <td><?= $screening->getComment() ?></td>
            <td>
              <i class="<?= getStatus($screening->getStatus()) ?>">
              </i>
            </td>
            <td>
              <?php
                foreach ($screening->getComplianceView()->getLinks() as $link)
                  echo $link->getHTML();
              ?>
            </td>
          </tr>
        </tbody>
      </table>

      <table>
        <thead>
          <tr>
            <th colspan="4">2. Wellness Events</th>
            <th>Date Done</th>
            <th>Status</th>
            <th>Links</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="4">A. <?= $quarter1->getComplianceView()->getReportName() ?></td>
            <td><?= $quarter1->getComment() ?></td>
            <td>
              <i class="<?= getStatus($quarter1->getStatus()) ?>">
              </i>
            </td>
            <td>
              <?php
                foreach ($quarter1->getComplianceView()->getLinks() as $link)
                  echo $link->getHTML();
              ?>
            </td>
          </tr>
          <tr>
            <td colspan="4">B. <?= $quarter2->getComplianceView()->getReportName() ?></td>
            <td><?= $quarter2->getComment() ?></td>
            <td>
              <i class="<?= getStatus($quarter2->getStatus()) ?>">
              </i>
            </td>
            <td>
              <?php
                foreach ($quarter2->getComplianceView()->getLinks() as $link)
                  echo $link->getHTML();
              ?>
            </td>
          </tr>
          <tr>
            <td colspan="4">C. <?= $quarter3->getComplianceView()->getReportName() ?></td>
            <td><?= $quarter3->getComment() ?></td>
            <td>
              <i class="<?= getStatus($quarter3->getStatus()) ?>">
              </i>
            </td>
            <td>
              <?php
                foreach ($quarter3->getComplianceView()->getLinks() as $link)
                  echo $link->getHTML();
              ?>
            </td>
          </tr>
          <tr>
            <td colspan="4">D. <?= $quarter4->getComplianceView()->getReportName() ?></td>
            <td><?= $quarter4->getComment() ?></td>
            <td>
              <i class="<?= getStatus($quarter4->getStatus()) ?>">
              </i>
            </td>
            <td>
              <?php
                foreach ($quarter4->getComplianceView()->getLinks() as $link)
                  echo $link->getHTML();
              ?>
            </td>
          </tr>
        </tbody>
      </table>

      <table>
        <thead>
          <tr>
            <th colspan="4">3. Overview</th>
            <th>Date Done</th>
            <th>Status</th>
            <th>Links</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="4">
              A. Biometric Screening + Quarterly Wellness Events
            </td>
            <td><?= $overview->getComment() ?></td>
            <td>
              <i class="<?= getStatus($overview->getStatus()) ?>">
              </i>
            </td>
            <td>
              <?php
                foreach ($overview->getComplianceView()->getLinks() as $link)
                  echo $link->getHTML();
              ?>
            </td>
          </tr>
        </tbody>
      </table>

      <?php
    }
  }

  function getStatus($code) {
    if ($code == 4) return 'far fa-check';
    if ($code == 2) return 'fas fa-exclamation';
    if ($code == 1) return 'far fa-times';
    return 'far fa-minus';
  }
?>
