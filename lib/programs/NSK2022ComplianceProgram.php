<?php
  use hpn\steel\query\SelectQuery;

  class NSK2022ComplianceProgram extends ComplianceProgram {
    public function getProgramReportPrinter($preferredPrinter = null) {
      return new NSK2022WMS2Printer();
    }

    public function getAdminProgramReportPrinter() {
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
        if($user->getRelationshipType() == 2)
          return $user->getRelationshipUser() ? $user->getRelationshipUser()->getSocialSecurityNumber() : '';
        else
          return $user->getSocialSecurityNumber();
      });

      $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
        $user = $status->getUser();
        $data = [];

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
          foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
            $viewName = $viewStatus->getComplianceView()->getReportName();

            if($groupStatus->getComplianceViewGroup()->getName() == 'Requirements')
              $data[sprintf('%s Result', $viewName)] = $viewStatus->getComment();
            elseif($groupStatus->getComplianceViewGroup()->getName() == 'Prevention Event')
              $data[sprintf('%s Comment', $viewName)] = $viewStatus->getComment();

            $data[sprintf('%s Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';

            if (in_array($viewName,  ['Health Risk Assessment', 'Viewed Video', 'Biometric Screening']))
              $data[sprintf('%s Completion Date', $viewName)] = $viewStatus->getComment();
          }
        }

        $data['Monthly Surcharge'] = $status->getComment();
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

      $coreGroup = new ComplianceViewGroup('Core');

      $hpa = new CompleteHRAComplianceView($programStart, $programEnd);
      $hpa->setReportName('Health Risk Assessment');
      $hpa->setName('hra');
      $hpa->emptyLinks();
      $coreGroup->addComplianceView($hpa);

      $scr = new CompleteScreeningComplianceView($programStart, $programEnd);
      $scr->setReportName('Biometric Screening');
      $scr->setName('screening');
      $scr->emptyLinks();
      $coreGroup->addComplianceView($scr);

      $video = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $video->setName('video');
      $video->setReportName('Viewed Video');
      $video->setName('video');
      $video->emptyLinks();
      $video->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $result = SelectQuery::create()
          ->select('count(distinct(lesson_id)) as lessons, tbk.completion_date as date')
          ->from('tbk_lessons_complete tbk')
          ->where('tbk.user_id = ?', [$user->id])
          ->andWhere('tbk.completion_date BETWEEN ? AND ?', ['2022-01-01', '2022-08-26'])
          ->hydrateSingleRow()
          ->execute();

        $lessons = $result['lessons'] ?? 0;

        if ($result['lessons']) {
          $status->setStatus(ComplianceStatus::COMPLIANT);
          $status->setComment($result['date']);
        }
      });
      $coreGroup->addComplianceView($video);

      // Used for override to force compliant
      $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $forceCompliant->setName('force_compliant');
      $forceCompliant->setReportName('Force Overall Compliant');
      $coreGroup->addComplianceView($forceCompliant);

      $this->addComplianceViewGroup($coreGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status) {
      parent::evaluateAndStoreOverallStatus($status);

      $user = $status->getUser();

      $thisCore = $status->getComplianceViewGroupStatus('Core');

      if(!$this->evaluateOverall)
        return;

      if ($status->getComplianceViewStatus('force_compliant')->isCompliant()) {
        $thisCore->setStatus(ComplianceStatus::COMPLIANT);

        $status->setStatus(ComplianceStatus::COMPLIANT);
      }

      $screeningAppointmentDate = $status->getComplianceViewStatus('screening')->getComment();

      if($screeningAppointmentDate == date('m/d/Y', strtotime($screeningAppointmentDate))) {
        $startDate = '2022-01-01';
        $endDate = date('m/d/Y', strtotime('+40 days', strtotime($screeningAppointmentDate)));

        $appt = SelectQuery::create()
          ->select('at.id')
          ->from('appointment_times at')
          ->innerJoin('appointments a')
          ->on('a.id = at.appointmentid')
          ->where('a.date BETWEEN ? AND ?', [$startDate, $endDate])
          ->andWhere('a.typeid IN (11, 21)')
          ->andWhere('at.user_id = ?', [$user->id])
          ->andWhere('at.showed = 1')
          ->hydrateSingleScalar()
          ->execute();

        if($appt)
          $status->getComplianceViewStatus('screening')->setStatus(ComplianceStatus::COMPLIANT);
      }

      $requirementsComplete = 0;

      if ($status->getComplianceViewStatus('hra')->isCompliant())
        $requirementsComplete++;
      if ($status->getComplianceViewStatus('screening')->isCompliant())
        $requirementsComplete++;
      if ($status->getComplianceViewStatus('video')->isCompliant())
        $requirementsComplete++;

      if ($status->getUser()->getHiredate() >= '2022-04-01') {
        $status->setComment('$0');
      } else {
        if($status->isCompliant() || $requirementsComplete == 3) {
          $status->setComment('$0');
          $status->setStatus(ComplianceStatus::COMPLIANT);
        } else if($requirementsComplete == 2) {
          $status->setComment('$30');
        } else if($requirementsComplete) {
          $status->setComment('$45');
        } else {
          $status->setComment('$90');
        }
      }

      if($status->getComplianceViewStatus('force_compliant')->getComment() != '') {
        $status->setComment($status->getComplianceViewStatus('force_compliant')->getComment());
      }
    }

    public function useParallelReport() {
      return false;
    }

    public function isSpouse(User $user) {
      return $user->getRelationshipType() == 2 ? true : false;
    }

    public function getRelatedUser(User $user) {
      $relatedUser = false;

      $relationshipUsers = [];

      if($user->relationship_user_id && !$user->relationshipUser->expired())
        $relationshipUsers[] = $user->relationshipUser;

      foreach($user->relationshipUsers as $relationshipUser)
        if(!$relationshipUser->expired())
          $relationshipUsers[] = $relationshipUser;

      foreach($relationshipUsers as $relationshipUser) {
        if(in_array($relationshipUser->relationship_type, $this->relationshipTypes)) {
          $relatedUser = $relationshipUser;

          break;
        }
      }

      return $relatedUser;
    }

    public function getRelatedUserComplianceStatus(User $user) {
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

    private $relationshipTypes = [Relationship::SPOUSE, Relationship::EMPLOYEE];
    protected $evaluateOverall = true;
    const RECORD_ID = 1724;
  }


  class NSK2022WMS2Printer implements ComplianceProgramReportPrinter {
    public function printReport(ComplianceProgramStatus $status) {
      $isSpouse = $status->getComplianceProgram()->isSpouse($status->getUser());
      $showSpouse = $isSpouse || $status->getComplianceProgram()->getRelatedUser($status->getUser());

      $hra = $status->getComplianceViewStatus('hra')->isCompliant();
      $screening = $status->getComplianceViewStatus('screening')->isCompliant();
      $video = $status->getComplianceViewStatus('video')->isCompliant();

      ?>

      <style type="text/css">
        #activities {
          width: 100%;
          border-collapse: separate;
          border-spacing: 5px;
          min-width: 500px;
        }

        #activities .points {
          text-align: center;
          width: 65px;
        }

        .success {
          background-color: #73c26f;
          color: #FFF;
        }

        .failure {
          background-color: #FD3B3B;
          color: #FFF;
        }

        .total-status td, .spouse-status td {
          text-align: center;
        }

        .alert a {
          color: #2196f3 !important;
          text-decoration: none !important;
        }

        .alert a:hover,
        .alert a:focus {
          color: #0a6ebd !important;
          text-decoration: none !important;
        }

        .panel {
          border: 0;
          border-radius: 2px;
          -webkit-box-shadow: 0 1px 4px rgba(0,0,0,0.3);
          box-shadow: 0 1px 4px rgba(0,0,0,0.3);
          margin-bottom: 25px;
          background-color: #fff;
        }

        .panel-heading {
          padding: 10px 15px;
          border-bottom: 1px solid transparent;
          border-top-right-radius: 2px;
          border-top-left-radius: 2px;
        }

        .panel-heading .row div {
          display: inline-block;
          width: 49%;
        }
      </style>

      <div class="row">
        <div class="col-md-12">
          <h1>2022 Incentive Report Card</h1>
        </div>
      </div>

      <div class="row">
          <div class="col-md-12">
              <a href="/compliance/nsk-2022/overview">Full Program Details</a><br/>
              <a href="/content/nsk-previous-program-years">Previous Program Years</a>
          </div>
      </div>

      <div class="row">
          <div class="col-md-12">
              <hr/>
          </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="alert alert-warning" style="color: inherit; background-color: transparent; border: 0px solid transparent;">
            <p>NEW simplified requirements for 2022! All requirements must be completed by August 26, 2022.</p>
            <ol>
              <li>
                <a href="/compliance/nsk-2022/hra/content/my-health">
                  Complete Health Risk Assessment (HRA)
                </a>
              </li>
              <li>
                <a href="/compliance/nsk-2022/schedule/content/schedule-appointments">
                  Schedule & Complete Biometric Screening
                </a>
              </li>
              <li>
                <a href="/compliance/nsk-2022/video-library/content/the-big-know?user_id=<?= $status->getUser()->id ?>">
                  View 1 Video Lesson from Video Library
                </a>
              </li>
            </ol>
            <p>Full Program Details on <a href="/compliance/nsk-2022/overview">Program Overview Page</a></p>
            <p>Track your progress below on your Report Card:</p>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-heading <?= $hra ? 'success' : 'failure' ?>">
          <div class="row">
            <div class="col-md-6">Health Risk Assessment</div>
            <div class="col-md-6 text-right"><?= $hra ? 'Complete' : 'Incomplete' ?></div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-heading <?= $screening ? 'success' : 'failure' ?>">
          <div class="row">
            <div class="col-md-6">Biometric Screening</div>
            <div class="col-md-6 text-right"><?= $screening ? 'Complete' : 'Incomplete' ?></div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-heading <?= $video ? 'success' : 'failure' ?>">
          <div class="row">
            <div class="col-md-6">Viewed Video Lesson</div>
            <div class="col-md-6 text-right"><?= $video ? 'Complete' : 'Incomplete' ?></div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <table id="activities">
            <tbody>
              <tr class="total-status">
                <th>
                  Your Program Status
                </th>
                <td colspan="3">
                  <?php if ($status->isCompliant()) : ?>
                    <span class="label label-success">Done</span>
                  <?php elseif ($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                    <span class="label label-warning">Partially Done</span>
                  <?php else : ?>
                    <span class="label label-danger">Not Done</span>
                  <?php endif ?>
                </td>
              </tr>
              <?php if ($showSpouse) : ?>
                <?php $relatedUserStatus = $status->getComplianceProgram()->getRelatedUserComplianceStatus($status->getUser()); ?>
                <tr class="spouse-status">
                  <th>Your Spouse's Status</th>
                  <td colspan="3">
                    <?php if ($relatedUserStatus->isCompliant()) : ?>
                      <span class="label label-success">Done</span>
                    <?php elseif ($relatedUserStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                      <span class="label label-warning">Partially Done</span>
                    <?php else : ?>
                      <span class="label label-danger">Not Done</span>
                    <?php endif ?>
                  </td>
                </tr>
              <?php endif ?>
            </tbody>
          </table>
          <br/>
          <p>
            <strong>Pregnant?</strong> Are you pregnant or 6 months postpartum? You may complete a
            <a href="/compliance/nsk-2021/my-report-card/pdf/clients/nsk/NSK_2022_Pregnancy_Exception_Form.pdf">pregnancy exception form</a>
            and submit it to Circle Wellness to obtain credit for your screening.
          </p>
        </div>
      </div>

      <?php
    }
  }
?>
