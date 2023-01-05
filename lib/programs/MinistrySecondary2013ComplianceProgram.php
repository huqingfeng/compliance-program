<?php

/**
 * Contains all classes for Ministry's secondary compliance program. This is
 * a custom table format that is mostly override-driven but does integrate
 * with activity tracker and elearning among a few other views.
 */

class MinistrySecondary2013RecordSteps extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityId);
    }

    public function __construct($startDate, $endDate, $activityId, $questionId, $monthThreshold, $points)
    {
        $this->activityId = $activityId;
        $this->questionId = $questionId;

        parent::__construct($startDate, $endDate);

        $this->points = $points;
        $this->monthThreshold = $monthThreshold;

        $links = $this->getLinks();
        $link = reset($links);
        $link->setLinkText('Record Your Steps');
    }

    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        $steps = array();

        foreach($records as $record) {
            $month = date('Y-m', strtotime($record->getDate()));

            $answers = $record->getQuestionAnswers();

            if(isset($answers[$this->questionId]) && ($answer = $answers[$this->questionId]->getAnswer()) && ctype_digit($answer)) {
                if(!isset($steps[$month])) {
                    $steps[$month] = 0;
                }

                $steps[$month] += (int) $answer;
            }
        }

        $monthsOverThreshold = 0;

        foreach($steps as $month => $stepsTaken) {
            if($stepsTaken >= $this->monthThreshold) {
                $monthsOverThreshold++;
            }
        }

        return new ComplianceViewStatus(
            $this,
            null,
            $monthsOverThreshold * $this->points
        );
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()
            ->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $points;
    private $monthThreshold;
    private $activityId;
    private $questionId;
}

class MinistrySecondary2013FileAffidavitComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(264);
    }

    public function __construct($startDate, $endDate, array $questionIds, $points)
    {
        parent::__construct($startDate, $endDate);

        $this->questionIds = $questionIds;
        $this->points = $points;

        $links = $this->getLinks();
        $link = reset($links);
        $link->setLinkText('File Affidavit');
    }

    public function getStatus(User $user)
    {
        $records = $this->getRecords($user);

        if($record = reset($records)) {
            $points = $this->points;

            $answers = $record->getQuestionAnswers();

            foreach($this->questionIds as $questionId) {
                if(!isset($answers[$questionId]) || !$answers[$questionId]->getAnswer()) {
                    $points = 0;

                    break;
                }
            }
        } else {
            $points = 0;
        }

        return new ComplianceViewStatus(
            $this,
            null,
            $points
        );
    }

    protected function getRecords(User $user)
    {
        return $this->getActivity()
            ->getRecords($user, $this->getStartDate(), $this->getEndDate());
    }

    private $points;
    private $questionIds;
}

class MinistrySecondary2013ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserFields(null, null, null, null, null, null, null, null, true);
        $printer->setShowComment(false, false, false);
        $printer->setShowPoints(true, true, true);
        $printer->setShowCompliant(true, false, false);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new MinistrySecondary2013Printer();
    }

    public function loadGroups()
    {
        $this->addComplianceViewGroup($this->getFitnessGroup());
        $this->addComplianceViewGroup($this->getEducationGroup());
        $this->addComplianceViewGroup($this->getLifeStyleGroup());

        $this->configureViews();
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        $root = $query->getRootAlias();

        $query->andWhere("$root.relationship_type = ?", Relationship::EMPLOYEE);

        parent::preQuery($query, $withViews);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $compliantInAllGroups = true;

        $totalPoints = 0;

        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            $groupMaxPoints = $groupStatus->getComplianceViewGroup()
                ->getPointsRequiredForCompliance();

            $groupPoints = $groupStatus->getPoints();

            if($groupPoints >= $groupMaxPoints) {
                $groupStatus->setStatus(ComplianceStatus::COMPLIANT);
                $groupStatus->setPoints($groupMaxPoints);
            } elseif($groupPoints > 0) {
                $groupStatus->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                $groupStatus->setPoints($groupPoints);
            } else {
                $groupStatus->setStatus(ComplianceStatus::NOT_COMPLIANT);
                $groupStatus->setPoints(0);
            }

            $totalPoints += $groupStatus->getPoints();

            if(!$groupStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $compliantInAllGroups = false;
            }
        }

        $status->setPoints($totalPoints);

        if($compliantInAllGroups) {
            $status->setStatus(ComplianceStatus::COMPLIANT);
        } elseif($totalPoints > 0) {
            $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }
    }

    protected function configureView(ComplianceView $view, $name, $reportName, $timesPerYear, $pointsValue, $maximumPoints, $link = null)
    {
        $view->setName($name);
        $view->setReportName($reportName);
        $view->setAttribute('times_per_year', $timesPerYear);
        $view->setAttribute('points_value', $pointsValue);
        $view->setMaximumNumberOfPoints($maximumPoints);

        if($link) {
            $view->addLink($link);
        }
    }

    protected function configureViews()
    {
        $miscInfo = array(
            'style_stress'              => 'Stress is on the rise. Here is a self guided program to help you de-stress and live longer. When you do, you can also earn points! The program consists of four classes that are 30-40 minutes in length.  Please see the Action Link in the last column on the right of this line.',
            'style_exercise'            => 'You are interested in exercise, but not a group kind of a person. What can you do on your own? This 90-Day walking program is divided into three phases and helps you make exercise a regular part of your life!  Please see the Action Link in the last column on the right of this line.',
            'style_weight'              => 'Weight management is a very personal challenge. Some like to participate in the powerful group program approach with great success. Others prefer a self guided path to help them achieve their goals. Find it here, with tools and support, including 14 lessons in all. Please see the Action Link in the last column on the right of this line.',
            'style_commitment'          => '
                Alcohol is a powerful drug and sometimes it is helpful to have personalized assistance.
                Here is a tool to help you learn how to control alcohol and not let it control you.
                The course part of this program contains 12 lessons, and when you’re done you earn points.
                Please see the Action Link in the last column on the right of this line.
                <br/><br/>
                Seatbelts save lives. Make your pledge; protect yourself and your family while earning
                points. Please see the Action Link in the last column on the right of this line.
                <br/><br/>
                Helmets make perfect sense. Do you wear yours? Make your pledge to wear yours here,
                and earn points for doing it. Please see the Action Link in the last column on the right
                of this line.
            ',
            'style_story'               => 'Has Ministry\'s wellness program offering helped you? Has it saved your life? Share your story and encourage your coworkers to participate--for their family, for themselves. Please see the Action Link in the last column on the right of this line.',
            'style_coach'               => 'If you participate in Health Coaching through Employee Health, you also earn points! Please see the Action Link in the last column on the right of this line.',
            'style_chronic'             => 'If you participate in a Chronic Condition program with Xerox, you get points! Please see the Action Link in the last column on the right of this line.',
            'style_tobacco_program'     => 'Stop smoking. Here is a Lifestyle Management Program consisting of 12 lessons taken over a 5 week period, which you can use on your own. It has a wealth of tools to help you succeed. And, when you do, you earn more points! Please see the Action Link in the last column on the right of this line.',
            'style_weight_program'      => 'If you are participating in a proven program like Weight Watchers or Jenny Craig, you can qualify for more points. Proof of active participation for a minimum of 4 months is required. Organized programs that provide weight management support and education, such as those listed above, qualify (meal programs such as nutrisystem do not). Please see the Action Link in the last column on the right of this line to submit proof of participation.',
            'wep_wep'                   => 'Earn points for attending a wellness education presentation that provides on opportunity to learn how healthy lifestyle choices can make a positive impact on your health.  Qualifying programs include those sponsored by Ministry or another organization. Proof of attendance created by the organizer is required. Please see the Action Link in the last column on the right of this line to submit documentation.',
            'wep_tobacco'               => 'Tobacco use in its several forms is a known health hazard. Learn the risks and how to protect yourself here. Please see the Action Link in the last column on the right of this line to complete the eLearning lesson(s).',
            'wep_alcohol_use'           => 'These eLearning lessons will provide deeper education on the effects and risks of alcohol use. Please see the Action Link in the last column on the right of this line.',
            'wep_substance_abuse'       => 'Substance abuse is an ever widening challenge. More people, young and old, are subject to harm from addiction. Learn more by here. Please see the Action Link in the last column on the right of this line.',
            'wep_healthy_weight'        => 'Find out what is considered a healthy weight and how to achieve it. Please see the Action Link in the last column on the right of this line.',
            'wep_depression'            => 'Depression affects a large percentage of the population and has many degrees of severity. You or a loved one could be suffering and unaware of how to get help. Learn more here. Please see the Action Link in the last column on the right of this line.',
            'wep_nutrition'             => 'What to eat? When? What\'s my goal? Nutrition help is on the way. Please see the Action Link in the last column on the right of this line.',
            'wep_exercise'              => 'Get moving! But how do I fit it into my schedule? What\'s really beneficial and what is just a waste of time? Learn more here. Please see the Action Link in the last column on the right of this line.',
            'wep_stress'                => 'Less sleep. More electronic connections. No peace. No rest. What can I do? Does it really matter? Yes! Learn more here. Please see the Action Link in the last column on the right of this line.',
            'fitness_non_pedo'          => '
                Acceptable documentation of 360 minutes of exercise in a calendar month (average of 90 minutes per week) includes:<br />

                <ul>
                    <li>Electronically reported gym attendance (e.g. monthly report of gym ID card swipes) </li>

                    <li>A receipt, registration verification or certificate of completion from a fitness class, or a note from the instructor;</li>

                    <li>A copy of your activity log is also acceptable if training for an event.</li>
                </ul>

                <br />

                You will have 30 days from the end of the calendar month to submit your proof of exercise.  Please see the Action Link in the last column on the right of this line.',
            'fitness_pedo'              => 'Record your steps to earn points for a calendar month in which you reach 200,000 steps (average of 50,000 steps per week).  You have 30 days from the end of the month to submit your steps. Please see the Action Link in the last column on the right of this line.',
            'fitness_pedo_bonus'        => 'Earn bonus points when you continue exercising and reach 25,000 steps over and above the 200,000 steps in a single month. Please see the Action Link in the last column on the right of this line.',
            'fitness_community'         => 'Earn points for up to two community fitness events per program year. Submit proof of registration/receipt or a certificate of completion for participation in a marathon, organized run /walk, etc. Please see the Action Link in the last column on the right of this line to submit documentation.'


        );

        foreach($miscInfo as $viewName => $helpText) {
            if($view = $this->getComplianceView($viewName)) {
                $view->setAttribute('help_text', $helpText);
            }
        }
    }

    protected function getEducationGroup()
    {
        $group = new ComplianceViewGroup('wep', 'Education Activities - 25 Points');
        $group->setPointsRequiredForCompliance(25);

        $group->addComplianceView($this->getOverrideView('wep_wep', 'Wellness Education Presentation(s)', 'Five', 5, 25, new Link('Update Your File', '/content/chp-document-uploader')));
        $group->addComplianceView($this->getElearningView('wep_tobacco', 'Tobacco', 'Twice', 5, 10, 'wep_tobacco'));
        $group->addComplianceView($this->getElearningView('wep_alcohol_use', 'Alcohol Use', 'Twice', 5, 10, 'wep_alcohol_use'));
        $group->addComplianceView($this->getElearningView('wep_substance_abuse', 'Substance Abuse', 'Twice', 5, 10, 'wep_substance_abuse'));
        $group->addComplianceView($this->getElearningView('wep_healthy_weight', 'Healthy Weight', 'Three', 5, 15, 'wep_healthy_weight'));
        $group->addComplianceView($this->getElearningView('wep_depression', 'Depression', 'Twice', 5, 10, 'wep_depression'));
        $group->addComplianceView($this->getElearningView('wep_nutrition', 'Nutrition', 'Three', 5, 15, 'wep_nutrition'));
        $group->addComplianceView($this->getElearningView('wep_exercise', 'Exercise', 'Twice', 5, 10, 'wep_exercise'));
        $group->addComplianceView($this->getElearningView('wep_stress', 'Stress', 'Twice', 5, 10, 'wep_stress'));

        return $group;
    }

    protected function getElearningView($name, $reportName, $timesPerYear, $pointsValue, $maximumPoints, $alias)
    {
        $view = new CompleteELearningGroupSet($this->getStartDate(), $this->getEndDate(), $alias);
        $view->setPointsPerLesson($pointsValue);

        $this->configureView($view, $name, $reportName, $timesPerYear, $pointsValue, $maximumPoints);

        return $view;
    }

    protected function getFitnessGroup()
    {
        $group = new ComplianceViewGroup('fitness', ' Fitness Activities – 25 Points');
        $group->setPointsRequiredForCompliance(25);

        $group->addComplianceView($this->getOverrideView('fitness_non_pedo', 'Non-pedometer<br/> 360 or more minutes/month', 'Four', 5, 20, new Link('Update Your File', '/content/chp-document-uploader')));
        $group->addComplianceView($this->getStepsView('fitness_pedo', 'Pedometer<br/> 200,000 steps/month', 'Four', 5, 20, 265, 102, 200000));
        $group->addComplianceView($this->getStepsView('fitness_pedo_bonus', 'Pedometer Bonus<br/> 25,000 steps over and above the 200,000 in a month', 'Once', 5, 5, 265, 102, 225000));
        $group->addComplianceView($this->getOverrideView('fitness_community', 'Community Fitness Event', 'Twice', 5, 10, new Link('Update Your File', '/content/chp-document-uploader')));

        return $group;
    }

    protected function getLifeStyleGroup()
    {
        $group = new ComplianceViewGroup('style', 'Lifestyle Activities – 25 Points');
        $group->setPointsRequiredForCompliance(25);

        $group->addComplianceView($this->getOverrideView('style_stress', 'Stress Management', 'Once', 5, 5, new Link('Living Easy', '/content/12088?course_id=2743')));
        $group->addComplianceView($this->getOverrideView('style_exercise', 'Exercise', 'Once', 5, 5, new Link('Living Fit', '/content/12088?course_id=2744')));
        $group->addComplianceView($this->getOverrideView('style_weight', 'Living Lean', 'Once', 5, 5, new Link('Living Lean', '/content/12088?course_id=2741')));
        $group->addComplianceView($this->getOverrideView('style_alcohol', 'Alcohol Management', 'Once', 10, 10, new Link('Living Smart Managing Alcohol', '/content/12088?course_id=2742')));

        $group->addComplianceView($this->getFileAffidavitView('style_commitment', 'Seatbelt/Helmet/Responsible Alcohol Commitment', 'Once', 5, 5, array(99, 100, 101)));

        $group->addComplianceView($this->getOverrideView('style_story', 'Tell Your Story', 'Once', 5, 5, new Link('Submit Story', '/content/14158')));
        $group->addComplianceView($this->getOverrideView('style_coach', 'Health Coaching', 'Once', 10, 10, new Link('I Did This', '/content/i_did_this')));
        $group->addComplianceView($this->getOverrideView('style_chronic', 'Chronic Condition Program', 'Once', 10, 10, new Link('I Participated In ...', '/content/i_participated_in_this')));
        $group->addComplianceView($this->getOverrideView('style_tobacco_program', 'Tobacco Cessation Program', 'Once', 10, 10, new Link('Living Free', '/content/12088?course_id=2740')));
        $group->addComplianceView($this->getOverrideView('style_weight_program', 'Weight Management Program', 'Once', 10, 10, new Link('Update Your File', '/content/chp-document-uploader')));

        return $group;
    }

    protected function getFileAffidavitView($name, $reportName, $timesPerYear, $pointsValue, $maximumPoints, $questionId)
    {
        $view = new MinistrySecondary2013FileAffidavitComplianceView($this->getStartDate(), $this->getEndDate(), $questionId, $pointsValue);

        $this->configureView($view, $name, $reportName, $timesPerYear, $pointsValue, $maximumPoints);

        return $view;
    }

    protected function getOverrideView($name, $reportName, $timesPerYear, $pointsValue, $maximumPoints, $link = null)
    {
        $view = new PlaceHolderComplianceView(null, 0);

        $this->configureView($view, $name, $reportName, $timesPerYear, $pointsValue, $maximumPoints, $link);

        return $view;
    }

    protected function getStepsView($name, $reportName, $timesPerYear, $pointsValue, $maximumPoints, $activityId, $questionId, $monthThreshold)
    {
        $view = new MinistrySecondary2013RecordSteps($this->getStartDate(), $this->getEndDate(), $activityId, $questionId, $monthThreshold, $pointsValue);

        $this->configureView($view, $name, $reportName, $timesPerYear, $pointsValue, $maximumPoints);

        return $view;
    }
}

class MinistrySecondary2013Printer implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $fitness = $status->getComplianceViewGroupStatus('fitness');
        $wep = $status->getComplianceViewGroupStatus('wep');
        $style = $status->getComplianceViewGroupStatus('style');
        ?>
    <style type="text/css">
        table.group tr th, table.group tr td {
            text-align:left;
            vertical-align:middle;
        }

        h3.title {
            color:#345A92;
            text-align:center !important;
            margin-top:10px;
            margin-bottom:5px;
        }

        table.group tr .subtitle {
            width:50px;
            text-align:center;
        }

        table.group tr td.answer {
            text-align:center;
        }

        table.table-really-condensed th, table.table-really-condensed td {
            padding:0px 2px;
        }

        table.group tr td.program {
            padding-left:32px;
            height:30px;
            width:300px;
        }

        table.group tr .action_links {
            text-align:center;
            width:200px;
        }

        table.group tr .sub-heading {
            padding-left:16px;
            text-align:left;
        }

        table.group {
            margin-bottom:10px;
            width:100%;
        }

        a.more-info-anchor {
            text-decoration:none;
            display:block;
            float:left;
            padding-right:20px;
        }

        .subtitle.<?php echo sprintf('status-%s', ComplianceStatus::COMPLIANT) ?> {
            background-color:#E1FFDB;
        }

        .subtitle.<?php echo sprintf('status-%s', ComplianceStatus::PARTIALLY_COMPLIANT) ?> {
            background-color:#FDFFDB;
        }

        .subtitle.<?php echo sprintf('status-%s', ComplianceStatus::NOT_COMPLIANT) ?> {
            background-color:#FFDBDB;
        }
    </style>
       <p><a href="/resources/4335/NCW-HealthyRewardsFAQ.pdf">Frequently Asked Questions</a></p>

    <h3>Healthy Rewards for Ministry</h3>
    <!--<p>The maximum points available to be earned is 25 in each category and 75 total per program year. This program is available to benefits eligible employees only; spouses are not eligible to participate in this additional incentive program.</p>-->
    <p> When you earn 25 points in one of the categories shown
        (Fitness/Education/Lifestyle), you will receive $25 to purchase health
        and wellness products on the A Healthier You e-commerce site.</p>
    <ul>
        <li>Earn up to $75 per program year by accumulating 25 points in all 3
            healthy reward categories.
        </li>
        <li>All activities must be submitted no later then September 30, 2013.
        </li>
        <li>Rewards will be credited to your e-commerce account in January
            2014.
        </li>
        <li>Please note that per IRS regulations, cash awards such as these are
            taxable.
        </li>
        <li>These Healthy Rewards are available to benefit eligible employees
            only; spouses are not eligible to participate in this reward
            program.
        </li>
        <li><span style="font-weight:bold;">Opening April 2013</span> - The
            <span style="font-family:italics;">A Healthier You</span> store can
            be found at <a href="http://www.co-store.com/ahealthieryou">www.co-store.com/ahealthieryou</a>.
        </li>

    </ul>

    <h3 class="title"><?php echo $fitness->getComplianceViewGroup()
        ->getReportName() ?></h3>

    <table class="group table table-bordered table-really-condensed">
        <tr>
            <th class="subtitle sub-heading">Milestone</th>
            <th class="subtitle">Times Per Year*</th>
            <th class="subtitle">Points Value</th>
            <th class="subtitle">Possible Points Annually</th>
            <th class="subtitle">My Points</th>
            <th class="action_links">Action Links</th>
        </tr>
        <tr>

        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('fitness_non_pedo')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('fitness_pedo')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('fitness_pedo_bonus')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('fitness_community')) ?>
        <tr>
            <th colspan="3" class="sub-heading">Total Points</th>
            <th class="subtitle"><?php echo $fitness->getComplianceViewGroup()
                ->getPointsRequiredForCompliance(); ?></th>
            <th class="subtitle <?php echo sprintf('status-%s', $fitness->getStatus()) ?>">
                <?php echo $fitness->getPoints() ?>
            </th>
            <th class="action_links"></th>
        </tr>
    </table>

    <h3 class="title"><?php echo $wep->getComplianceViewGroup()
        ->getReportName() ?></h3>

    <table class="group table table-bordered table-really-condensed">
        <tr>
            <th class="subtitle sub-heading">Milestone</th>
            <th class="subtitle">Times Per Year*</th>
            <th class="subtitle">Points Value</th>
            <th class="subtitle">Possible Points Annually</th>
            <th class="subtitle">My Points</th>
            <th class="action_links">Action Links</th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_wep')) ?>
        <tr>
            <th colspan="6" class="sub-heading">Online Learning</th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_tobacco')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_alcohol_use')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_substance_abuse')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_healthy_weight')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_depression')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_nutrition')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_exercise')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('wep_stress')) ?>
        <tr>
            <th colspan="3" class="sub-heading">Total Points</th>
            <th class="subtitle"><?php echo $wep->getComplianceViewGroup()
                ->getPointsRequiredForCompliance() ?></th>
            <th class="subtitle <?php echo sprintf('status-%s', $wep->getStatus()) ?>">
                <?php echo $wep->getPoints() ?>
            </th>
            <th class="action_links"></th>
        </tr>
    </table>

    <h3 class="title"><?php echo $style->getComplianceViewGroup()
        ->getReportName() ?></h3>

    <table class="group table table-bordered table-really-condensed">
        <tr>
            <th class="subtitle sub-heading">Milestone</th>
            <th class="subtitle">Times Per Year*</th>
            <th class="subtitle">Points Value</th>
            <th class="subtitle">Possible Points Annually</th>
            <th class="subtitle">My Points</th>
            <th class="action_links">Action Links</th>
        </tr>
        <tr>
            <th colspan="6" class="sub-heading">LifeStyle Improvement Programs
            </th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_stress')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_exercise')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_weight')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_alcohol')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_tobacco_program')) ?>

        <tr>
            <th colspan="6" class="sub-heading">My Commitments</th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_commitment')) ?>
        <tr>
            <th colspan="6" class="sub-heading">Other Initiatives</th>
        </tr>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_story')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_coach')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_chronic')) ?>
        <?php $this->printViewStatus($status->getComplianceViewStatus('style_weight_program')) ?>
        <tr>
            <th colspan="3" class="sub-heading">Total Points</th>
            <th class="subtitle"><?php echo $style->getComplianceViewGroup()
                ->getPointsRequiredForCompliance() ?></th>
            <th class="subtitle <?php echo sprintf('status-%s', $style->getStatus()) ?>">
                <?php echo $style->getPoints() ?>
            </th>
            <th class="action_links"></th>
        </tr>
    </table>

    <br/>
    <br/>

    <p>If you are unable to complete one of the activities listed above due to a
        medical condition, but would still like to participate, please contact
        your local Employee Health office to discuss alternatives for your
        participation.</p>
    <?php
    }

    protected function printViewStatus($status)
    {
        $view = $status->getComplianceView();
        $popoverId = sprintf('%s-popover', $view->getName());
        ?>
    <tr>
        <td class="program">
            <?php if($help = $view->getAttribute('help_text')) : ?>
            <a id="<?php echo $popoverId ?>" href="#" class="more-info-anchor"
                onclick="return false;">
                <i class="icon-info-sign"></i>
                <?php echo $view->getReportName(true) ?>
            </a>
            <script type="text/javascript">
                $(function () {
                    $("#<?php echo $popoverId ?>").popover({
                        title: <?php echo json_encode(preg_replace('|<br[ ]*/?>.*|', '', $view->getReportName())) ?>,
                        content: <?php echo json_encode($help) ?>,
                        trigger:'hover',
                        html:true
                    });
                });
            </script>
            <?php else : ?>
            <?php echo $view->getReportName(true) ?>
            <?php endif ?>
        </td>
        <td class="answer"><?php echo $view->getAttribute('times_per_year') ?></td>
        <td class="answer"><?php echo $view->getAttribute('points_value') ?></td>
        <td class="answer"><?php echo $view->getMaximumNumberOfPoints() ?></td>
        <td class="answer"><?php echo $status->getPoints() ?></td>
        <td class="action_links">
            <?php echo implode(' ', $view->getLinks()) ?>
        </td>
    </tr>
    <?php
    }
}