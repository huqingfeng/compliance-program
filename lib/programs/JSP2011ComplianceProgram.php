<?php
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

class JSP2011HireDateBonusView extends ComplianceView
{
    public function getDefaultName()
    {
        return 'hire_bonus';
    }

    public function getDefaultReportName()
    {
        return 'Hire Bonus';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function allowPointsOverride()
    {
        return true;
    }

    public function getStatus(User $user)
    {
        if(!$user->planenrolldate) {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, 0, null);
        }

        $h = $user->getDateTimeObject('planenrolldate')->format('U');

        if($h < strtotime('2011-06-15')) {
            $points = 0;
        } else if($h < strtotime('2011-10-01')) {
            $points = 80;
        } else if($h < strtotime('2011-12-01')) {
            $points = 90;
        } else {
            $points = 150;
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT, $points, null);
    }
}

class JSP2011ScreeningTestPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);

        echo '<br/><br/>';
        echo Content::getApplicableContent('screening-chart');
    }
}

class JSPHealthActionsActivity extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(66);
    }

    public function __construct($startDate, $endDate)
    {
        parent::__construct($startDate, $endDate);

        static $pointsMap = null;

        if($pointsMap === null) {
            $pointsMap = array();

            foreach($this->getActivity()->getQuestions() as $question) {
                if($question->getID() == $this->questionId) {
                    $parameters = $question->getParameters();

                    foreach($parameters as $item => $answers) {
                        preg_match('/([0-9]+) Points?/', $item, $matches);

                        if(isset($matches[1]) && is_numeric($matches[1])) {
                            $points = (int) $matches[1];

                            foreach($answers as $answer) {
                                $pointsMap[$answer] = $points;
                            }
                        }
                    }
                }
            }
        }

        $this->answerPoints = $pointsMap;
    }

    public function setDefaultPoints($points)
    {
        $this->defaultPoints = $points;
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        $points = 0;

        $recorded = array();

        foreach($records as $record) {
            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->questionId]) && ($answerText = $answers[$this->questionId]->getAnswer())) {
                if(!isset($recorded[$answerText])) {
                    $recorded[$answerText] = true;

                    $points += $this->getPointsForAnswer($answerText);
                }
            } else {
                $points += $this->defaultPoints;
            }
        }

        return new ComplianceViewStatus($this, null, $points);
    }

    protected function getPointsForAnswer($item)
    {
        return isset($this->answerPoints[$item]) ?
            $this->answerPoints[$item] : $this->defaultPoints;
    }

    protected $answerPoints = array();
    protected $defaultPoints = 0;
    protected $questionId = 70;
}

class JSP2011CompleteAdditionalELearningLessonsComplianceView extends CompleteELearningLessonsComplianceView
{
    public function __construct($startDate, $endDate, ComplianceView $assignedView, ComplianceView $requiredView)
    {
        parent::__construct($startDate, $endDate, null);

        $this->assignedView = clone $assignedView;
        $this->requiredView = clone $requiredView;
    }

    public function getStatus(User $user)
    {
        $this->setPointsPerLesson(1);

        $this->requiredView->setPointsPerLesson(1);

        $status = parent::getStatus($user);

        // This view will include required points, so ignore them over 2.

        $ignorePoints = min($this->requiredView->getStatus($user)->getPoints(), 2);

        $points = 5 * ($status->getPoints() - $ignorePoints);

        if($points > 0) {
            $status->setPoints($points);
        } else {
            $status->setPoints(0);
        }

        return $status;
    }

    public function getRequiredView()
    {
        return $this->assignedView;
    }

    private $assignedView;
    private $requiredView;
}

class JSP2011ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new JSP2011ScreeningTestPrinter();
            $printer->bindGroup('extra');

            $query = ScreeningTable::getInstance()
                ->getScreeningsForUser($this->getActiveUser(), array('execute' => false));
            $screenings = ScreeningTable::getInstance()
                ->getScreeningForDates($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'), array('query' => $query));
            $screening = $screenings->getFirst();

            if($screening) {
                $printer->setPageTitle('Points Earned From My Wellness Screening On '.$screening->getDate());
            } else {
                $printer->setPageTitle('You have not had a Wellness Screening.');
            }
        } else {
            $printer = new JSP2011ComplianceProgramReportPrinter();
        }

        return $printer;
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowStatus(null, null, true);

        // The fields are defined in the JSP content program additional_fields.php
        // Unfortunatly, the additional field functionality is supposed to be dynamic,
        // but due to learning about the feature request 2hrs before it needed to be
        // implemented for a demo, we had to hack this together. So, we grab all
        // the distinct field names of the additional fields stored and add them to
        // this report via callbacks.

        $recordType = 'jsp_additional_fields';

        $fieldNameQuery = '
      SELECT DISTINCT user_data_fields.field_name
      FROM user_data_fields
      INNER JOIN user_data_records ON user_data_records.id = user_data_fields.user_data_record_id
      WHERE user_data_records.type = ?
    ';

        $db = Piranha::getInstance()->getDatabase();
        $db->executeSelect($fieldNameQuery, $recordType);

        while($row = $db->getNextRow()) {
            $printer->addCallbackField($row['field_name'], function (User $user) use ($row) {
                $record = $user->getNewestDataRecord('jsp_additional_fields');

                return $record->exists() ? $record->getDataFieldValue($row['field_name']) : null;
            });
        }

        return $printer;
    }

    public function loadEvaluators()
    {
        $bonusGroup = new ComplianceViewGroup('bonus', 'Bonus Rewards Status & Raffle Eligibility');

        $firstTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), $this->getEndDate()), array('start', 'one', 'ongoing'));
        $firstTotal->setName('first_total');
        $firstTotal->setReportName('Status of 1, 2 and 3 as of: '.date('m/d/Y'));
        $firstTotal->addLink(new FakeLink('Must be Green for Raffle', '#'));
        $bonusGroup->addComplianceView($firstTotal, true);

        $secondTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2011-09-30'), array('start', 'one', 'ongoing'), 150, false);
        $secondTotal->setName('second_total');
        $secondTotal->setReportName('5A is Green + 150 points = eligible for September 2011 Raffle');
        $secondTotal->addLink(new FakeLink('Eligible yet?', '#'));
        $secondTotal->addLink(new Link('See winners.', '#'));
        $secondTotal->setAttribute('deadline', '9/30/11');
        $bonusGroup->addComplianceView($secondTotal, true);

        $thirdTotal = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($this->getStartDate(), '2012-04-15'), array('start', 'one', 'ongoing'), 300, false);
        $thirdTotal->setName('third_total');
        $thirdTotal->setReportName('5A is Green + 300 points = eligible* for April 2012 Raffle');
        $thirdTotal->addLink(new FakeLink('Eligible yet?', '#'));
        $thirdTotal->addLink(new Link('See winners.', '#'));
        $thirdTotal->setAttribute('deadline', '4/15/12');
        $bonusGroup->addComplianceView($thirdTotal, true);


        $this->addComplianceViewGroup($bonusGroup);
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $startGroupEndDate = '2011-08-04';

        $startGroup = new ComplianceViewGroup('start', 'Starting core actions required by '.date('m/d/Y', strtotime($startGroupEndDate)));

        $startGroup->addComplianceView(
            CompleteScreeningComplianceView::create($startDate, $startGroupEndDate)
                ->setName('complete_screening')
                ->setReportName('<a href="/content/1094#1ascreen">Complete the Wellness Screening</a>')
                ->emptyLinks()
                ->setCheckAppointmentsForPartial(true)
                ->addLink(new Link('Sign-Up Now', '/content/1051?filter[type]=1'))
                ->addLink(new Link('<br />Doctor Form', '/resources/3183/JSP form 1A wscreening via provider 050511.pdf'))
        );

        $startGroup->addComplianceView(
            CompleteHRAComplianceView::create($startDate, $startGroupEndDate)
                ->setName('complete_hra')
                ->setReportName('<a href="/content/1094#1bhpa">Complete the Health Power Assessment</a>')

        );

        $startGroup->addComplianceView(
            UpdateDoctorInformationComplianceView::create($startDate, $startGroupEndDate)
                ->setName('doctor_information')
                ->setReportName('<a href="/content/1094#1cmaindoc">Have a Main Doctor</a>')

        );

        $startGroup->addComplianceView(
            UpdateContactInformationComplianceView::create($startDate, $startGroupEndDate)
                ->setName('contact_information')
                ->setReportName('<a href="/content/1094#1dpers">Update Personal Contact Info</a>')

        );

        $requiredElearnView = CompleteELearningGroupSet::create($startDate, $startGroupEndDate, 'required_2011_2012')
            ->setName('2_elearning')
            ->setReportName('<a href="/content/1094#1eelearn">Complete 2 of 4 Required e-Learning Lessons</a>')
            ->setNumberRequired(2)
            ->setAllowAllLessons(true);

        $startGroup->addComplianceView($requiredElearnView);

        $this->addComplianceViewGroup($startGroup);

        $ongoingGroup = new ComplianceViewGroup('ongoing', 'Ongoing core actions ALL year');

        $ongoingGroup->addComplianceView(
            PlaceHolderComplianceView::create(ComplianceStatus::NA_COMPLIANT)
                ->setAttribute('deadline', 'Within 5 days of being called')
                ->setName('one_care_counselor')
                ->setReportName('<a href="/content/1094#2acounsel">Make all required calls to the Care Counselor before receiving certain types of health care and other times. If a nurse calls you, return their calls and work with them until you are told (by the nurse) you are done</a>')
                ->addLink(new Link('When to Call', '/content/301725?sitemapIdentifier=229400&like_home=1'))
        );

        $returnCallsCoach = new GraduateFromCoachingSessionComplianceView($startDate, '2012-04-15');
        $returnCallsCoach->setAttribute('deadline', 'Within 5 days of being called');
        $returnCallsCoach->setName('two_health_coach');
        $returnCallsCoach->setReportName('<a href="/content/1094#2bcoach">Return Calls of a Health Coach if they call you AND work with them until you are told you are done.</a>');
        $returnCallsCoach->addLink(new Link('<br />Schedule a Call', '/content/8733'));
        $returnCallsCoach->setRequireTargeted(true);
        $ongoingGroup->addComplianceView($returnCallsCoach);

        $this->addComplianceViewGroup($ongoingGroup);

        $oneGroupEndDate = '2012-04-15';

        $followUpGroup = new ComplianceViewGroup('one', 'Screening Follow-Up Actions by '.date('m/d/Y', strtotime($oneGroupEndDate)));

        $coreView = ComplyWithCoreBiometricsComplianceView::create($startDate, $oneGroupEndDate)
            ->setName('core_biometrics')
            ->setReportName('<a href="/content/1094#3abio">Have a core biometric score of 60 or more points -OR- work with a doctor or health coach to improve it.</a>')
            ->addLink(new Link('Score Details<br/>', '?preferredPrinter=ScreeningProgramReportPrinter'))
            ->addLink(new Link('Health Coach option/sign-up<br/>', '/content/8733'))
            ->addLink(new Link('Doctor Option/Form', '/resources/3181/JSP form 3A2 improvement plan via doctor 050511.pdf'))
            ->setPostEvaluateCallback(function (ComplianceViewStatus $status, User $user) use ($returnCallsCoach) {
            if($status->getStatus() != ComplianceStatus::COMPLIANT) {
                $returnCallsCoachMod = clone $returnCallsCoach;
                $returnCallsCoachMod->setRequireTargeted(false);

                $coachStatus = $returnCallsCoachMod->getStatus($user);

                if($coachStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                } else if($coachStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                    $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                }
            }
        });

        $coreView->setPointThreshold(60);
        $coreView->setComplianceStatusPointMapper(
            new ComplianceStatusPointMapper(0, 0, 0, 0)
        );

        $followUpGroup->addComplianceView($coreView);

        $assignedElearnView = CompleteAssignedELearningLessonsComplianceView::create('2001-01-01', $oneGroupEndDate)
            ->setReportName('<a href="/content/1094#3belearn">Complete extra e-learning lessons recommended by Health Coach or Nurse</a>');


        $followUpGroup->addComplianceView($assignedElearnView);

        $this->addComplianceViewGroup($followUpGroup);

        $extraEnd = '2012-04-30';

        $extraGroup = new ComplianceViewGroup('extra', 'Earn 150+ Health-Action points through any of the options below by '.date('m/d/Y', strtotime($extraEnd)));
        $extraGroup->setPointsRequiredForCompliance(150);

        $screeningTestMapper = new ComplianceStatusPointMapper(10, 5, 0, 0);

        $totalCholesterolView = new ComplyWithTotalCholesterolScreeningTestComplianceView($startDate, $extraEnd);
        $totalCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($totalCholesterolView);

        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($startDate, $extraEnd);
        $hdlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($hdlCholesterolView);

        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($startDate, $extraEnd);
        $ldlCholesterolView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($ldlCholesterolView);

        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $extraEnd);
        $trigView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($trigView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $extraEnd);
        $glucoseView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($glucoseView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $extraEnd);
        $bloodPressureView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($bloodPressureView);

        $bodyFatBMIView = new ComplyWithBodyFatBMIScreeningTestComplianceView($startDate, $extraEnd);
        $bodyFatBMIView->setComplianceStatusPointMapper($screeningTestMapper);
        $bodyFatBMIView->setReportName('Better of body mass index or % body fat');
        $extraGroup->addComplianceView($bodyFatBMIView);

        $nonSmokerView = new ComplyWithCotinineScreeningTestComplianceView($startDate, $extraEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-Smoker/Low Exposure to Nicotine/Cotinine');
        $nonSmokerView->setComplianceStatusPointMapper($screeningTestMapper);
        $extraGroup->addComplianceView($nonSmokerView);

        $preventiveExamsView = new ObtainPreventiveExamComplianceView($startDate, $extraEnd, 5);
        $preventiveExamsView->setReportName('<a href="/content/1094#4bscreen">Get Recommended Preventive Screenings/Exams</a>');
        $preventiveExamsView->setMaximumNumberOfPoints(20);
        $preventiveExamsView->setName('do_preventive_exams');
        $extraGroup->addComplianceView($preventiveExamsView);

        $fluVaccineView = new ImmunizationsActivityComplianceView($startDate, $extraEnd, 5);
        $fluVaccineView->setReportName('<a href="/content/1094#4cimmun">Get Recommended Immunizations</a>');
        $fluVaccineView->setMaximumNumberOfPoints(20);
        $fluVaccineView->setName('flu_vaccine');
        $extraGroup->addComplianceView($fluVaccineView);

        $physicalActivityView = new PhysicalActivityComplianceView($startDate, $extraEnd);
        $physicalActivityView->setReportName('<a href="/content/1094#4dphys">Get Regular Physical Activity</a>');
        $physicalActivityView->setMaximumNumberOfPoints(100);
        $physicalActivityView->setMinutesDivisorForPoints(60);
        $physicalActivityView->setMonthlyPointLimit(100);
        $physicalActivityView->setName('physical_activity');
        $extraGroup->addComplianceView($physicalActivityView);

        $stressView = new JSPHealthActionsActivity($startDate, $extraEnd);
        $stressView->setName('stress');
        $stressView->setReportName('<a href="/content/1094#4ehealth">Verify Other Key Health Actions - weight, smoking, stress</a>');
        $stressView->setMaximumNumberOfPoints(200);
        $extraGroup->addComplianceView($stressView);

        $additionalELearningLessonsView = new JSP2011CompleteAdditionalELearningLessonsComplianceView($startDate, $extraEnd, $assignedElearnView, $requiredElearnView);
        $additionalELearningLessonsView->setNumberRequired(0);
        $additionalELearningLessonsView->setReportName('<a href="/content/1094#4felearn">Complete Extra e-Learning Lessons</a>');
        $additionalELearningLessonsView->setName('extra_elearning');
        $additionalELearningLessonsView->setMaximumNumberOfPoints(50);
        $extraGroup->addComplianceView($additionalELearningLessonsView);

        $volunteeringView = new VolunteeringComplianceView($startDate, $extraEnd);
        $volunteeringView->setName('volunteering');
        $volunteeringView->setReportName('<a href="/content/1094#4gvol">Regular Volunteering - Type & Time</a>');
        $volunteeringView->setMinutesDivisorForPoints(60);
        $volunteeringView->setMaximumNumberOfPoints(50);
        $extraGroup->addComplianceView($volunteeringView);

        $improveScore = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $improveScore->setName('improve_score');
        $improveScore->setReportName('<a href="/content/1094#4hbio">Improve Biometric Score -- Show via Re-Test with Doctor</a>');
        $improveScore->addLink(new Link('Progress Screening Form', '/resources/3341/JSP form 4H wscreening progress re-test via provider 081211.pdf'));
        $improveScore->setMaximumNumberOfPoints(50);
        $improveScore->setAttribute('deadline', '4/14/12');
        $improveScore->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(50, 0, 0, 0));
        $extraGroup->addComplianceView($improveScore);

        $test = new ShareAStoryComplianceView($startDate, $extraEnd, 25);
        $test->setName('test');
        $test->setReportName('<a href="/content/1094#4itest">Submit Testimonial - how HAP helped you/family member</a>');
        $test->setMaximumNumberOfPoints(50);
        $extraGroup->addComplianceView($test);

        $extra = new JSP2011HireDateBonusView();
        $extra->setMaximumNumberOfPoints(150);
        $extra->setReportName('Grace points for those enrolled after 6/15/11 with less time to get 150 points');
        $extraGroup->addComplianceView($extra);

        $this->addComplianceViewGroup($extraGroup);
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        $root = $query->getRootAlias();
        $query->andWhereIn("$root.relationship_type", array(Relationship::EMPLOYEE, Relationship::SPOUSE));

        parent::preQuery($query, $withViews);
    }
}

class JSP2011ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        ?>
    <style type="text/css">
        #report td, #report th {
            border:0.1em solid #000;
        }

        #report thead  th {
            font-weight:normal;
        }

        #report thead th strong {
            font-size:1.3em;
            font-weight:normal;
        }

        #report tfoot th {
            font-weight:normal;
        }

        #report tbody td.name {
            padding-left:1em;
        }

        #report .name {
            width:40%;
        }

        #report .penalty {
            width:12em;
        }

        #report .light, #report .date, #report .links {
            text-align:center;
        }

        #report {
            width:100%;
            border-collapse:collapse;
        }

        #information, #report {
            font-size:0.875em;
        }

        #report .group, #report th.penalty {
            font-weight:bold;
            background-color:#3DA5D1;
            color:#FFFFFF;
        }

        #report .date {
            width:10em;
        }

        #report th, #report td {
            padding:0.4em;
        }

        #report img.light {
            width:2em;
        }

        #legend_image {
            float:right;
        }

        #information .miscellaneous {
            clear:both;
        }

        #_title {
            font-weight:bold;
            text-align:center;
        }

        .points_earned, .points_possible {
            text-align:center;
        }

        .clear {
            clear:both;
        }
    </style>

    <div id="information">
        <p>
            Welcome to the new JSP Health Advantage Plan Benefit Incentive/To-Do Summary page for the benefit period May
            1, 2011 through June 30, 2012.
        </p>

        <p>
            <em style="color:#0000FF">Do you want to keep the lowest premium contribution, receive the greatest savings
                and other benefits?</em>
        </p>

        <div id="legend_image">
            <img src="/resources/3110/jsp.2012.legend.jpg" alt="Legend"/>
        </div>

        <div>
            <strong><u>If yes, here are the requirements (or To-Dos) that EACH adult in the HAP benefit MUST
                do:</u></strong>
            <ul>
                <li>Both the Employee <strong><u>AND</u></strong> Spouse in the plan must each meet <strong><u>ALL</u>
                </strong>requirements in the table below.
                </li>
                <li>Each person must take action and meet each requirement by the deadline noted.</li>
            </ul>
        </div>

        <div>
            <strong>By taking action and meeting all requirements you and your family will:</strong>
            <ul>
                <li>Save (avoid) <strong><u>$1,240-$2,480</u></strong> or more in annual premium increases and other
                    out-of-pocket expenses.
                </li>
                <li>Qualify for 2 bonus raffles – one in September 2011 and another in April 2012.</li>
                <li>Benefit from improvements in health, health care and wellbeing – now and throughout life</li>
            </ul>
        </div>

        <p>Alert: If one person meets the requirements, but the other does not, your premium will increase $10-$40 per
            week for up to 14 months AND you will NOT be eligible for Bonus Rewards.</p>


        Here are some tips about the table below and using it:
        <ul>
            <li>In the first column, click on the text in blue to learn why the action is important.</li>
            <li>Use the Action Links in the right column to get things done or more information.</li>
            <li><a href="/content/1094">Click here</a> for more details about the requirements, rewards and penalties.
            </li>
            <li>Review this page often to check your status, get To-Dos done and earn more points for more benefits.
            </li>
        </ul>

        <div class="miscellaneous"></div>
    </div>
    <table id="report">
        <thead>
            <tr>
                <th colspan="5">
                    <p>
                        <strong>Details for: <?php echo $status->getUser()->getFullName() ?></strong>
                    </p>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0 ?>
            <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
            <?php $group = $groupStatus->getComplianceViewGroup() ?>
            <?php if($group->getName() == 'extra') : ?>
                <tr class="group">
                    <td><?php echo ($i + 1).'. '.$group->getReportName() ?></td>
                    <th class="date">Deadline</th>
                    <th class="points_earned"># Points Earned</th>
                    <th class="points_possible"># Points Possible</th>
                    <th class="links">Action Links</th>
                </tr>
                <?php $j = 0 ?>
                <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $view = $viewStatus->getComplianceView() ?>
                    <?php if($j < 8) : ?>
                        <?php if(!$j) : ?>
                            <tr class="view" id="screening_view">
                                <td class="name"><a href="/content/1094#4abio">A. Have these screening results in the
                                    ideal zone:</a></td>
                                <td colspan="4">
                      <span style="font-style:italic;">
                        Note: These biometric points are totaled after 1A is done.
                      </span>
                                </td>
                            </tr>
                            <?php endif ?>
                        <tr class="view">
                            <td class="name">&nbsp;&nbsp;&nbsp; &bull; <?php echo $view->getReportName() ?></td>
                            <td class="date"><?php echo $view->getAttribute('deadline', $view instanceof DateBasedComplianceView ? $view->getEndDate('n/d/y') : '') ?></td>
                            <td class="points_earned"><?php echo $viewStatus->getPoints() ?></td>
                            <td class="points_possible"><?php echo $view->getMaximumNumberOfPoints() ?></td>
                            <?php if(!$j) : ?>
                            <td class="links" rowspan="8">
                                <a href="?preferredPrinter=ScreeningProgramReportPrinter">Click for these 8 results.</a>
                                <br/>
                                <br/>
                                <a href="/content/989">Click for all screening results.</a>
                            </td>
                            <?php endif ?>
                        </tr>
                        <?php else : ?>
                        <tr class="view">
                            <td class="name"><?php echo getLetterFromNumber($j - 7).'. '.$view->getReportName() ?></td>
                            <td class="date"><?php echo $view->getAttribute('deadline', $view instanceof DateBasedComplianceView ? $view->getEndDate('n/d/y') : '') ?></td>
                            <td class="points_earned"><?php echo $viewStatus->getPoints() ?></td>
                            <td class="points_possible"><?php echo $view->getMaximumNumberOfPoints() ?></td>
                            <td class="links"><?php echo implode(' ', $view->getLinks()) ?></td>
                        </tr>
                        <?php endif ?>
                    <?php $j++ ?>
                    <?php endforeach ?>
                <tr class="view">
                    <td class="name" style="text-align:right">
                        Total Points & Status as of: <?php echo date('m/d/Y') ?> =
                    </td>
                    <td class="date"><?php echo $view->getAttribute('deadline', $view instanceof DateBasedComplianceView ? $view->getEndDate('n/d/y') : '') ?></td>
                    <td class="points_earned">
                        <?php echo $groupStatus->getPoints() ?>
                    </td>
                    <td class="points_possible">
                        <img src="<?php echo $groupStatus->getLight() ?>" class="light" alt=""/>
                    </td>
                    <td class="links"></td>
                </tr>
                <?php else : ?>
                <tr class="group">
                    <td><?php echo ($i + 1).'. '.$group->getReportName() ?></td>
                    <th class="date">Deadline</th>
                    <th class="comment">Date Done</th>
                    <th class="light">Status</th>
                    <th class="links">Action Links</th>
                </tr>
                <?php $j = 0 ?>
                <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                    <?php $view = $viewStatus->getComplianceView() ?>
                    <tr class="view">
                        <td class="name"><?php echo getLetterFromNumber($j).'. '.$view->getReportName() ?></td>
                        <td class="date"><?php echo $view->getAttribute('deadline', $view instanceof DateBasedComplianceView ? $view->getEndDate('n/d/y') : '') ?></td>
                        <td class="comment"><?php echo $viewStatus->getComment() ?></td>
                        <td class="light"><img src="<?php echo $viewStatus->getLight(); ?>" class="light" alt=""/></td>
                        <td class="links"><?php echo implode(' ', $view->getLinks()) ?></td>
                    </tr>
                    <?php $j++ ?>
                    <?php endforeach ?>
                <?php endif ?>
            <?php $i++ ?>
            <?php if($group->getName() == 'start') : ?>
                <tr class="view">
                    <td class="name" style="text-align:right">
                        All core actions done on or before: 08/04/2011 =
                    </td>
                    <td class="date"></td>
                    <td class="points_earned"></td>
                    <td class="points_earned">
                        <?php echo $groupStatus->isCompliant() ? 'Yes' : 'No' ?>
                    </td>
                    <td class="links"></td>
                </tr>
                <?php endif ?>

            <?php endforeach ?>
        </tbody>
    </table>
    <p style="margin-top:4px;font-style:italic;">* To qualify for the April Raffle you must: 1) Be an employee or spouse
        in the J.S. Paluch health plan; 2) Have all actions done (green or clear
        status) in sections 1, 2, and 3; and 3) Have 300 or more points in
        section 4 by 4/15/12.</p>
    <?php
    }
}
