<?php

class Android2012PCPCampaign extends UpdateDoctorInformationComplianceView
{
    public function getStatus(User $user)
    {
        $status = parent::getStatus($user);

        if(!$user->getNewestDataRecord('android_actions', true)->performed) {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        $points = $status->getStatus() == ComplianceStatus::COMPLIANT ?
            5000 : 0;

        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, $points);
    }
}

class Android2012CompleteScreeningView extends CompleteScreeningComplianceView
{
    protected function evaluateStatus(User $user, $array)
    {
        if(!parent::evaluateStatus($user, $array)) {
            return false;
        }

        $cotinine = trim((string) $array['cotinine']);

        return $cotinine != '' && $cotinine != '0' ?
            ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;
    }
}

class Android2012CompleteHraAndScreeningComplianceView extends CompleteHRAAndScreeningComplianceView
{
    protected function getScreeningView()
    {
        return new Android2012CompleteScreeningView($this->startDate, $this->endDate);
    }
}

class Android2012Vegetables extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(253);
    }

    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        // 85 = number_of_servings

        $servings = array();

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            $date = date('Y-m-d', strtotime($record->getDate()));

            if(!isset($servings[$date])) {
                $servings[$date] = 0;
            }

            if(isset($answers[85]) && is_numeric($answers[85]->getAnswer())) {
                $servings[$date] += $answers[85]->getAnswer();
            }
        }

        $daysCompliant = 0;

        foreach($servings as $day => $servings) {
            if($servings >= 5) {
                $daysCompliant++;
            }
        }

        return new ComplianceViewStatus(
            $this,
            null,
            $daysCompliant >= 44 ? 15000 : 0
        );
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $activityId;
    private $pointsPerRecord;
}

class Android2012ComplyWithCotinineScreeningTestComplianceView extends ComplyWithCotinineScreeningTestComplianceView
{
    public function allowPointsOverride()
    {
        return true;
    }
}

class Android2012ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Android2012ComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        $printer->addCallbackField('employee_id', function (User $user) {
            return (string) $user->employeeid;
        });

        $printer->addCallbackField('location', function (User $user) {
            return $user->location;
        });

        $printer->addCallbackField('team_name', function (User $user) {
            $db = Database::getDatabase();

            $db->executeSelect('
        SELECT t.name
        FROM walking_teams t
        INNER JOIN walking_registrants r ON r.team_id = t.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT 1
      ', $user->id);

            if($row = $db->getNextRow()) {
                return $row['name'];
            } else {
                return '';
            }
        });

        return $printer;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate('U');
        $programEnd = $this->getEndDate('U');

        // Android users are compliant if they have HRA and Screening Done .. i.e.
        // have earned 5000 points.

        $requiredGroup = new ComplianceViewGroup('requirement', 'Requirement');
        $requiredGroup->setPointsRequiredForCompliance(5000);

        $consultationView = new CompletePrivateConsultationComplianceView($programStart, $programEnd);
        $consultationView->setReportName('Complete Consultation');
        $consultationView->setAttribute('comment', 'Date Completed');
        $requiredGroup->addComplianceView($consultationView);

        $this->addComplianceViewGroup($requiredGroup);

        $s = new Android2012CompleteHraAndScreeningComplianceView($programStart, $programEnd);
        $s->setReportName('Complete HRA and Screening');
        $s->setAttribute('comment', 'Date Completed');

        $s->setPostEvaluateCallback(function (ComplianceViewStatus $status, User $user) use ($consultationView) {
            if($status->getStatus() == ComplianceStatus::COMPLIANT
                && $consultationView->getStatus($user)->getStatus() == ComplianceStatus::COMPLIANT
            ) {
                $status->setPoints(5000);
            } else {
                $status->setPoints(0);
            }
        });

        $requiredGroup->addComplianceView($s);

        $cotinineView = new Android2012ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
        $cotinineView->setReportName('Cotinine (Tobacco Screening)');
        $cotinineView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(5000, 0, 0, 0));
        $requiredGroup->addComplianceView($cotinineView);

        $optionalGroup = new ComplianceViewGroup('Campaign');
        $optionalGroup->setPointsRequiredForCompliance(0);

        //$walkingProgram = new Android2012WalkingProgram($programStart, $programEnd);
        //$walkingProgram->setReportName('Get Up and Move! Walking Campaign');
        //$walkingProgram->setName('android_walking_campaign');
        //$optionalGroup->addComplianceView($walkingProgram);

        $weightProgram = new Android2012WeightProgram('2011-08-01', '2012-05-31');
        $weightProgram->setReportName('Holiday Weight Program');
        $optionalGroup->addComplianceView($weightProgram);

        $pcp = new Android2012PCPCampaign('2012-03-01', '2012-04-30');
        $pcp->setReportName('PCP & Self Exam Campaign');
        $pcp->emptyLinks();
        $optionalGroup->addComplianceView($pcp);

        $veg = new Android2012Vegetables('2012-04-30', '2012-06-29');
        $veg->setReportName('Nutrition Challenge - Fruits & Vegetables');
        $veg->emptyLinks();
        $optionalGroup->addComplianceView($veg);

        $this->addComplianceViewGroup($requiredGroup);
        $this->addComplianceViewGroup($optionalGroup);
    }
}

/**
 * Evaluates Android status for their holiday weight program and gives them
 * 5000 points if they have participated and 15000 if their newest weight + 2
 * is less than or equal to their old weight.
 */
class Android2012WeightProgram extends ComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->startDate = ctype_digit($startDate) ? $startDate : strtotime($startDate);
        $this->endDate = ctype_digit($endDate) ? $endDate : strtotime($endDate);

        if($this->startDate === false) {
            throw new \InvalidArgumentException("Invalid start date: $startDate");
        }

        if($this->endDate === false) {
            throw new \InvalidArgumentException("Invalid end: $endDate");
        }
    }

    public function getStatus(User $user)
    {
        $hasFinal = false;

        $weighIns = array();

        foreach($user->getDataRecords('holiday_weigh_in') as $record) {
            if($record->weight) {
                $date = strtotime($record->date);

                if($date >= $this->startDate && $date <= $this->endDate) {
                    $weighIns[] = array(
                        'weight' => $record->weight,
                        'final'  => $record->final,
                        'date'   => $record->date
                    );

                    if($record->final) {
                        $hasFinal = true;
                    }
                }
            }
        }

        usort($weighIns, function ($a, $b) {
            $l = strtotime($a['date']);
            $r = strtotime($b['date']);

            if($l < $r) {
                return -1;
            } else if($l > $r) {
                return 1;
            } else {
                return 0;
            }
        });

        if(count($weighIns) >= 2 && $hasFinal) {
            $weightInPoints = 5000;

            $first = array_shift($weighIns);

            foreach($weighIns as $weighIn) {
                if($weighIn['final']) {
                    if(($weighIn['weight'] - $first['weight']) <= 2) {
                        $weightInPoints += 10000;
                    }

                    break;
                }
            }
        } else {
            $weightInPoints = 0;
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, $weightInPoints);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultReportName()
    {
        return 'Weight';
    }

    public function getDefaultName()
    {
        return 'android_weight';
    }

    private $startDate;
    private $endDate;
}

class Android2012WalkingProgram extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getStatus(User $user)
    {
        $db = Database::getDatabase();

        $db->executeSelect('
      SELECT SUM(walking_points.points) AS points
      FROM walking_points
      INNER JOIN walking_registrants
      ON walking_registrants.id = walking_points.registrant_id
      WHERE walking_registrants.user_id = ?
      AND walking_points.date BETWEEN ? AND ?
    ', $user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        $row = $db->getNextRow();

        // User gets 5000 pts for participating, and 15,000 if their weight
        // has decreased or only increased by 2

        $weighIns = $user->getDataRecords('holiday_weigh_in');

        $weightInPoints = 0;

        if(count($weighIns)) {
            $first = array_shift($weighIns)->weight;

            if($first) {
                $weightInPoints += 5000;

                foreach($weighIns as $weighIn) {
                    if($weighIn->final) {

                        if($weighIn->weight && ($weighIn->weight - $first) <= 2) {
                            $weightInPoints += 10000;
                        }

                        break;
                    }
                }
            }
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, $row['points'] + $weightInPoints);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultReportName()
    {
        return 'Walking';
    }

    public function getDefaultName()
    {
        return 'android_walking';
    }
}

class Android2012ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    private function printTableRows(ComplianceProgramStatus $status)
    {
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            $group = $groupStatus->getComplianceViewGroup();
            ?>
        <tr class="thead" xmlns="http://www.w3.org/1999/html">
            <th class="resource"><?php echo $group->getReportName() ?></th>
            <th class="status">
                <?php echo $group->getName() == 'requirement' ? 'Status' : 'Links'  ?>
            </th>
            <th class="points">Points</th>
        </tr>
        <?php
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                $this->printTableRow($viewStatus);
            }
        }
    }

    private function getViewName(ComplianceView $view)
    {
        $viewName = '<span>'.$view->getReportName().'</span>';

        if($view->getOptional()) {
            $viewName .= '<span class="notRequired">(Not Required)</span>';
        }

        return $viewName;
    }

    private function printTableRow(ComplianceViewStatus $status)
    {
        $view = $status->getComplianceView();
        $group = $view->getComplianceViewGroup();
        $c = $view->getAttribute('comment', 'Result');
        ?>
    <tr class="<?php echo sprintf('view-%s', $view->getName()) ?>">
        <td class="resource"><?php echo $this->getViewName($view); ?></td>
        <td class="phipstatus">
            <?php if($group->getName() == 'requirement') : ?>
            <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
            <?php
            if($status->getStatus() == ComplianceStatus::COMPLIANT || $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                echo "<br/>$c:<br/>", $status->getComment();
            }
            ?>
            <?php else : ?>
            <?php echo implode(' ', $view->getLinks()) ?>
            <?php endif ?>
        </td>
        <td class="points">
            <span class="hook"><?php echo $status->getPoints() ?></span>
        </td>
    </tr>
    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        use_stylesheet('/css/compliance/Amway20092010ComplianceProgram.css');
        $user = $status->getUser();
        ?>
    <style type="text/css">
        div#phip {
            float:none;
            width:100%;
        }

        .view-complete_screening_hra .points span.hook {
            position:relative;
            top:-25px;
        }
    </style>
    <p>Hello <?php echo $user; ?></p>

    <p class="subnote">
        <em>Using a shared computer?</em>
        <strong>If you are not <?php echo $user; ?>, <a href="/logout">Please click here</a>.</strong>
    </p>
    <hr/>

    <div style="font-size:0.95em;">
        <p>At Android, your health matters! We have created the following report card for you to easily access your
            wellness incentive status.</p>

        <p><strong>CONFIDENTIALITY:</strong>No one from Android Industries, including Android medical staff, has access
            to your personal medical results.</p>

        <p>
            <strong>Step 1 - </strong>Complete your on-site health screenings and <a href="/content/989">Health Risk
            Assessment</a>. Screenings will conclude September 28, 2012.
        </p>

        <p><strong>Step 2 - </strong>Complete your on-site or web/phone consultation.</p>

        <p>
            By completing Steps 1 and 2 above you will receive a wellness credit toward your 2013 medical premium rate
            if enrolling in Android medical plans for 2013.
        </p>

        <p>View your <a href="/compliance_programs?id=160">preventative screenings status.</a></p>
        <!-- <p>Hourly Team Members – Paid Weekly</p>
           <ul>
             <li>Non-tobacco users will get a wellness credit of $6.73 per paycheck ($350 annually)</li>
             <li>Tobacco users will get a wellness credit of $3.36 per paycheck ($175 annually)</li>
           </ul>
          </p>
          <p>Salaried Team Members – Paid Bi-Weekly</p>
           <ul>
             <li>Non-tobacco users will get a wellness credit of $13.46 per paycheck ($350 annually)</li>
             <li>Tobacco users will get a wellness credit of $6.72 per paycheck ($175 annually)</li>
           </ul>
          </p>-->
    </div>
    <hr/>
    <div id="phip">
        <div class="pageTitle">My Incentive Report Card</div>
        <table id="legend">
            <tr>
                <td id="firstColumn">LEGEND</td>
                <td id="secondColumn">
                    <table id="secondColumnTable">
                        <tr>
                            <td>
                                <img src="/images/lights/greenlight.gif" class="light" alt=""/> Compliant
                            </td>
                            <td>
                                <img src="/images/lights/yellowlight.gif" class="light" alt=""/> Partially Compliant
                            </td>
                            <td>
                                <img src="/images/lights/redlight.gif" class="light" alt=""/> Not Compliant
                            </td>
                            <td>
                                <img src="/images/lights/whitelight.gif" class="light" alt=""/> N/A
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table id="phipTable">
            <tbody>
                <tr id="totalComplianceRow">
                    <td class="resource">Overall Compliance</td>
                    <td class="status">
                        <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
                    </td>
                    <td class="points">
                        <?php echo $status->getPoints() ?>
                    </td>
                </tr>
                <?php $this->printTableRows($status); ?>
            </tbody>

        </table>

        <div id="endNote">
            <div>If you have any questions about your report card please call toll
                free: (866) 682-3020 ext. 204.
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
    <?php
    }
}