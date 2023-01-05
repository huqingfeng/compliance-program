<?php
  class TrueScripts2022ComplianceProgram extends ComplianceProgram {
    protected function getActivityView($name, $activityId, $points, $reportName = null, $pointsPerRecord = null, $link = true) {
      if ($pointsPerRecord === null)
        $pointsPerRecord = $points;

      $view = new CompleteArbitraryActivityComplianceView(
        $this->getStartDate(),
        $this->getEndDate(),
        $activityId,
        $pointsPerRecord
      );

      $view->setMaximumNumberOfPoints($points);

      $view->setName($name);

      if ($reportName !== null)
        $view->setReportName($reportName);

      if (!$link)
        $view->emptyLinks();

      return $view;
    }

    protected function getPlaceHolderView($name, $points, $reportName = null) {
      $view = new PlaceHolderComplianceView(null, 0);
      $view->setName($name);
      $view->setMaximumNumberOfPoints($points);

      if ($reportName !== null)
        $view->setReportName($reportName);

      return $view;
    }


    public function getProgramReportPrinter($preferredPrinter = null) {
      return new TrueScripts2022WMS2ComplianceProgramPrinter();
    }


    public function loadGroups() {
      $startDate = $this->getStartDate();
      $endDate = $this->getEndDate();

      $prevention = new ComplianceViewGroup('Preventative');
      $prevention->setPointsRequiredForCompliance(0);

      $hra = new CompleteHRAComplianceView($startDate, $endDate);
      $hra->setReportName('Health Risk Assessment (HRA)');
      $hra->setName('hra');
      $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
      $hra->emptyLinks();
      $prevention->addComplianceView($hra);

      $scr = new CompleteScreeningComplianceView($startDate, $endDate);
      $scr->setReportName('Biometric Screening');
      $scr->setName('screening');
      $scr->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(250, 0, 0, 0));
      $scr->emptyLinks();
      $prevention->addComplianceView($scr);

      $physical = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144534, 100);
      $physical->setReportName('Physical');
      $physical->setName('physical');
      $physical->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(100, 0, 0, 0));
      $prevention->addComplianceView($physical);

      $fluVaccine = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 20, 50);
      $fluVaccine->setReportName('Flu Vaccine');
      $fluVaccine->setName('flu_vaccine');
      $fluVaccine->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
      $prevention->addComplianceView($fluVaccine);

      $this->addComplianceViewGroup($prevention);

      $lunchLearns = new ComplianceViewGroup('Lunch & Learns');
      $lunchLearns->setPointsRequiredForCompliance(0);

      $lunchLearn1 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144535, 50);
      $lunchLearn1->setReportName('Lunch & Learn #1');
      $lunchLearn1->setName('lunch_learn_1');
      $lunchLearn1->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
      $lunchLearns->addComplianceView($lunchLearn1);

      $lunchLearn2 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144536, 50);
      $lunchLearn2->setReportName('Lunch & Learn #2');
      $lunchLearn2->setName('lunch_learn_2');
      $lunchLearn2->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
      $lunchLearns->addComplianceView($lunchLearn2);

      $lunchLearn3 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144537, 50);
      $lunchLearn3->setReportName('Lunch & Learn #3');
      $lunchLearn3->setName('lunch_learn_3');
      $lunchLearn3->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
      $lunchLearns->addComplianceView($lunchLearn3);

      $lunchLearn4 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144538, 50);
      $lunchLearn4->setReportName('Lunch & Learn #4');
      $lunchLearn4->setName('lunch_learn_4');
      $lunchLearn4->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
      $lunchLearns->addComplianceView($lunchLearn4);

      $this->addComplianceViewGroup($lunchLearns);

      $challenges = new ComplianceViewGroup('Challenges');
      $challenges->setPointsRequiredForCompliance(0);

      $challenge1 = new PlaceHolderComplianceView(null, 0);
      $challenge1->setReportName('5 for 5 - 3/7-4/10');
      $challenge1->setName('challenge_1');
      $challenge1->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(125, 0, 0, 0));
      $challenge1->setMaximumNumberOfPoints(125);
      $challenges->addComplianceView($challenge1);

      $challenge2 = new PlaceHolderComplianceView(null, 0);
      $challenge2->setReportName('Hydr8- 6/26-7/23');
      $challenge2->setName('challenge_2');
      $challenge2->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(125, 0, 0, 0));
      $challenge2->setMaximumNumberOfPoints(125);
      $challenges->addComplianceView($challenge2);

      $challenge3 = new PlaceHolderComplianceView(null, 0);
      $challenge3->setReportName('Challenge #3');
      $challenge3->setName('challenge_3');
      $challenge3->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(125, 0, 0, 0));
      $challenge3->setMaximumNumberOfPoints(125);
      $challenges->addComplianceView($challenge3);

      $challenge4 = new PlaceHolderComplianceView(null, 0);
      $challenge4->setReportName('Challenge #4');
      $challenge4->setName('challenge_4');
      $challenge4->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(125, 0, 0, 0));
      $challenge4->setMaximumNumberOfPoints(125);
      $challenges->addComplianceView($challenge4);

      $this->addComplianceViewGroup($challenges);

      $bookClub = new ComplianceViewGroup('Book Club');
      $bookClub->setPointsRequiredForCompliance(0);

      $bookClub1 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144539, 25);
      $bookClub1->setReportName('Book Club #1');
      $bookClub1->setName('book_club_1');
      $bookClub1->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
      $bookClub->addComplianceView($bookClub1);

      $bookClub2 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144540, 25);
      $bookClub2->setReportName('Book Club #2');
      $bookClub2->setName('book_club_2');
      $bookClub2->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(25, 0, 0, 0));
      $bookClub->addComplianceView($bookClub2);

      $this->addComplianceViewGroup($bookClub);

      $extracurricularFitnessActivities = new ComplianceViewGroup('Extracurricular Fitness Activities');
      $extracurricularFitnessActivities->setPointsRequiredForCompliance(0);

      $extracurricular1 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144541, 75);
      $extracurricular1->setReportName('Extracurricular Fitness Activity #1');
      $extracurricular1->setName('extracurricular_1');
      $extracurricular1->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
      $extracurricularFitnessActivities->addComplianceView($extracurricular1);

      $extracurricular2 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144542, 75);
      $extracurricular2->setReportName('Extracurricular Fitness Activity #2');
      $extracurricular2->setName('extracurricular_2');
      $extracurricular2->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
      $extracurricularFitnessActivities->addComplianceView($extracurricular2);

      $extracurricular3 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144543, 75);
      $extracurricular3->setReportName('Extracurricular Fitness Activity #3');
      $extracurricular3->setName('extracurricular_3');
      $extracurricular3->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
      $extracurricularFitnessActivities->addComplianceView($extracurricular3);

      $extracurricular4 = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144544, 75);
      $extracurricular4->setReportName('Extracurricular Fitness Activity #4');
      $extracurricular4->setName('extracurricular_4');
      $extracurricular4->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
      $extracurricularFitnessActivities->addComplianceView($extracurricular4);

      $this->addComplianceViewGroup($extracurricularFitnessActivities);

      foreach ($this->getComplianceViews() as $view)
        foreach ($view->getLinks() as $link)
            if ($link->getLinkText() == 'Enter/Update Info')
                $link->setLinkText('Update');
    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $alias) {
      $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($alias) {
          $view = $status->getComplianceView();

          if ($status->getAttribute('has_result') && !$status->isCompliant()) {
              $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), $alias);
              $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
              $elearningView->setName('alternative_'.$view->getName());
              $elearningView->useAlternateCode(true);

              $view->addLink(new Link('Alternative', "/content/9420?action=lessonManager&tab_alias={$alias}"));

              $elearningStatus = $elearningView->getStatus($user);

              $lessonCompleted = count($elearningStatus->getAttribute('lessons_completed'));

              if ($status->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
                  if ($lessonCompleted >= 2) {
                      $status->setStatus(ComplianceStatus::COMPLIANT);
                      $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                  } elseif ($lessonCompleted >= 1) {
                      $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                      $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                  }
              } elseif ($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                  if ($lessonCompleted >= 1) {
                      $status->setStatus(ComplianceStatus::COMPLIANT);
                      $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                  }
              }
          }
      });
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status){
      parent::evaluateAndStoreOverallStatus($status);

      $totalpoints = 0;
      foreach ($status->getComplianceViewGroupStatuses() as $groupStatus) {
        $points = 0;
        foreach ($groupStatus->getComplianceViewStatuses() as $viewStatus) {
          if ($viewStatus->getStatus() == ComplianceStatus::COMPLIANT || $viewStatus->getPoints()) {
            $view = $viewStatus->getComplianceView();
            $viewStatus->setPoints($view->getMaximumNumberOfPoints());
            $points += $view->getMaximumNumberOfPoints();
          }
        }
        $groupStatus->setPoints($points);
        $totalpoints += $points;
      }
      $status->setPoints($totalpoints);

      if ($status->getPoints() >= 800)
        $status->setStatus(ComplianceStatus::COMPLIANT);
      else
        $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
    }
  }

  class TrueScripts2022WMS2ComplianceProgramPrinter implements ComplianceProgramReportPrinter {
    public function printReport(ComplianceProgramStatus $status) {
      $user = $status->getUser();

      $totalpoints = $status->getPoints();

      $classFor = function($rawPct) {
        return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
      };

      $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor) {
        ob_start();

        if ($group->getComplianceViewGroup()->getName() == 'Biometrics') : ?>
          <table class="table table-condensed">
            <thead>
              <tr>
                <th>Test</th>
                <th>Target</th>
                <th class="text-center">Point Values</th>
                <th class="text-center">Result</th>
                <th class="text-center">Your Points</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($group->getComplianceViewStatuses() as $viewStatus): ?>
                <?php $view = $viewStatus->getComplianceView() ?>
                <tr>
                  <td rowspan="3">
                    <?= $view->getReportName() ?>
                    <br/>
                    <?php foreach ($viewStatus->getComplianceView()->getLinks() as $link): ?>
                      <div><?= $link->getHTML() ?></div>
                    <?php endforeach; ?>
                  </td>
                  <td>
                    <span class="label label-success">
                      <?= $view->getStatusSummary(ComplianceStatus::COMPLIANT) ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <?= $view->getStatusPointMapper()->getPoints(ComplianceStatus::COMPLIANT) ?>
                  </td>
                  <td class="text-center">
                    <?php if ($viewStatus->getStatus() == ComplianceStatus::COMPLIANT): ?>
                      <span class="label label-success">
                        <?= $viewStatus->getComment() ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) : ?>
                      <span class="label label-success">
                        <?= $viewStatus->getPoints() ?>
                      </span>
                    <?php endif ?>
                  </td>
                </tr>
                <tr>
                  <td>
                    <span class="label label-warning">
                      <?= $view->getStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <?= $view->getStatusPointMapper()->getPoints(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                  </td>
                  <td class="text-center">
                    <?php if ($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT): ?>
                      <span class="label label-success">
                        <?= $viewStatus->getComment() ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT): ?>
                      <span class="label label-warning">
                        <?= $viewStatus->getPoints() ?>
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td>
                    <span class="label label-danger">
                      <?= $view->getStatusSummary(ComplianceStatus::NOT_COMPLIANT) ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <?= $view->getStatusPointMapper()->getPoints(ComplianceStatus::NOT_COMPLIANT) ?>
                  </td>
                  <td class="text-center">
                    <?php if ($viewStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT): ?>
                      <span class="label label-success">
                        <?= $viewStatus->getComment() ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ($viewStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT): ?>
                      <span class="label label-danger">
                        <?= $viewStatus->getPoints() ?>
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        <?php else: ?>
          <table class="details-table">
            <thead>
              <tr>
                <th>Item</th>
                <th class="points">Maximum</th>
                <th class="points">Actual</th>
                <th class="text-center">Progress</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($group->getComplianceViewStatuses() as $viewStatus): ?>
                <?php $pct = $viewStatus->getPoints() / $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
                <?php $class = $classFor($pct) ?>
                  <tr>
                    <td class="name">
                      <?= $viewStatus->getComplianceView()->getReportName() ?>
                    </td>
                    <td class="points">
                      <?= $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
                    </td>
                    <td class="points <?= $class ?>">
                      <?= $viewStatus->getPoints() ?>
                    </td>
                    <td class="text-center">
                      <div class="pgrs pgrs-tiny">
                        <div class="bar <?= $class ?>" style="width: <?= max(1, $pct * 100) ?>%"></div>
                      </div>
                    </td>
                    <td class="text-center">
                      <?php foreach ($viewStatus->getComplianceView()->getLinks() as $link): ?>
                        <div><?= $link->getHTML() ?></div>
                      <?php endforeach; ?>
                    </td>
                  </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        <?php endif;

        return ob_get_clean();
      };

      $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
        ob_start();

        $points = $group->getPoints();
        $target = $group->getComplianceViewGroup()->getMaximumNumberOfPoints();

        $pct = $points / $target;

        $class = $classFor($pct);
        ?>

        <tr class="picker closed">
          <td class="name">
            <?= $name ?>
            <div class="triangle"></div>
          </td>
          <td class="points target">
            <strong><?= $target ?></strong><br/>
            points
          </td>
          <td class="points <?= $class ?>">
            <strong><?= $points ?></strong><br/>
            points
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
      }

      ?>

      <style type="text/css">
        #activities {
          width: 100%;
          min-width: 500px;
          border-collapse: separate;
          border-spacing: 5px;
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
          width: 200px;
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

        .circle-range {
          margin-top: 50px;
        }

        .circle-range-inner-success {
          background-color: #73c26f;
          color: #FFF;
        }

        .circle-range-inner-danger {
          background-color: #A0A0A0;
          color: #FFF;
        }

        .activity .circle-range {
          border-color: #489DE8;
        }

        .circle-range .circle-points {
          font-size: 1.4em;
        }

        .legend {
        	width: 25px;
        	height: 25px;
        	border-radius: 50%;
        }

        .green-circle {
        	background: #74c46e;
        }

        .orange-circle {
        	background: #feb839;
        }

        .red-circle {
        	background-color: #f25752;
        }

        #point-discounts td {
          padding: 10px;
        }

        #point-discounts #discount-success {
          width: 30px;
          height: 30px;
          border-radius: 15px;
          background-color: #74c36e;
        }

        #point-discounts #discount-warning {
          width: 30px;
          height: 30px;
          border-radius: 15px;
          background-color: #fdb83b;
        }

        #point-discounts #discount-danger {
          width: 30px;
          height: 30px;
          border-radius: 15px;
          background-color: #f15752;
        }

        <?php if ($status->getUser()->insurancetype): ?>
          #physician-reviewed-link {
            display: none;
          }
        <?php endif; ?>
      </style>

      <script type="text/javascript">
        $(function() {
          $.each($('#activities .picker'), function() {
            $(this).click(function(e) {
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
        });
      </script>

      <img src="/images/banners/incentives_banner.png" style="width: 100%;">
      <div class="row">
        <div class="col-md-12">
          <h1>MY REPORT CARD</h1>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12 text-center">
          <div class="row">
            <div class="col-md-3"></div>

            <div class="col-md-6">
              <div class="row">
              	<div class="circle-range">
                  <div class="circle-range-inner circle-range-inner-<?= $status->getStatus() == ComplianceStatus::COMPLIANT ? 'success' : 'danger' ?> ">
                    <div style="font-size: 1.3em; line-height: 1.0em;"><span class="circle-points"><br/><?= $totalpoints; ?></span><br/><br/>Points</div>
                  </div>
  	            </div>
              </div>
            </div>

            <div class="col-md-3"></div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <p><a href="#" id="more-info-toggle">Click for more information...</a></span></p>

          <div id="more-info" style="display: none">
              <p>
                Below is your Report Card for tracking wellness activities. You will have the opportunity to earn
                incentive points towards the wellness program incentive from 1/1/2022 to 12/31/2022. You are not
                required to do every activity. There are 1500 possible points, you will receive an HSA/Premium
                Reduction of $500 if you earn 800 or more points. Additionally, for every 300 points accumulated
                your name will be entered into a drawing (a maximum of 5 times) for exercise equipment.
              </p>

              <p>
                The Health Screenings will take place on-site in September. When you complete your wellness
                screening, your status will be updated below, providing you wellness points. The Health Risk
                Assessment should be taken prior to your health screening, but within 30 days of your screening
                so that both HRA and screening can be paired for your Personalized Screening Report. More details
                will be communicated closer to the time of the screenings.
              </p>

              <p>
                Other point earning opportunities are available in the Prevention, Lunch & Learn, Challenges, Book
                Clubs & Extracurricular Fitness Activities categories in your Report Card. You can click the "Update"
                links to the right of each activity to log those activities for the dates they occurred. You may
                log back to the beginning of the program (1/1/2022) for completed activities. The Health Screening,
                HRA and Challenges will be populated automatically in the Circle Wellness portal.
              </p>

              <p>
                Human Resources will be communicating for details for Lunch & Learns, Challenges, Book Clubs and
                Extracurricular throughout the year.
              </p>

              <p>
                Incentives will be awarded on 12/8/2022.
              </p>

              <p>
                If you have any questions or need assistance with your logging, please contact Customer Service
                at 866-682-3020 x204.
              </p>
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
              <?= $tableRow('Preventative', $status->getComplianceViewGroupStatus('Preventative')) ?>
              <?= $tableRow('Lunch & Learns', $status->getComplianceViewGroupStatus('Lunch & Learns')) ?>
              <?= $tableRow('Challenges', $status->getComplianceViewGroupStatus('Challenges')) ?>
              <?= $tableRow('Book Club', $status->getComplianceViewGroupStatus('Book Club')) ?>
              <?= $tableRow('Extracurricular Fitness Activities', $status->getComplianceViewGroupStatus('Extracurricular Fitness Activities')) ?>
            </tbody>
          </table>
        </div>
      </div>

      <script type="text/javascript">
        $(function() {
          $more = $('#more-info-toggle');
          $moreContent = $('#more-info');

          $more.click(function(e) {
            e.preventDefault();

            if ($more.html() == 'Click for more information...') {
              $moreContent.css({ display: 'block' });
              $more.html('Less...');
            } else {
              $moreContent.css({ display: 'none' });
              $more.html('Click for more information...');
            }
          });
        });
      </script>

      <?php
    }
  }
?>
