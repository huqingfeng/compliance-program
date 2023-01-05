<?php
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

class JSP2010ScreeningTestPrinter extends ScreeningProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        echo '<br/><br/>';
        echo Content::getApplicableContent('screening-chart');
    }
}

class JSPActivityComplianceView extends CompleteActivityComplianceView
{
    public function getActivity()
    {
        return new ActivityTrackerActivity(32);
    }

    public function __construct($startDate, $endDate)
    {
        parent::__construct($startDate, $endDate);
    }

    public function getStatus(User $user)
    {
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());

        if(count($records)) {
            $status = ComplianceStatus::COMPLIANT;
            $firstRecord = current($records);
            $comment = $firstRecord->getDate();
        } else {
            $status = ComplianceStatus::NOT_COMPLIANT;
            $comment = null;
        }

        return new ComplianceViewStatus($this, $status, null, $comment);
    }
}

class JSPCareCounselorComplianceView extends ComplianceView
{
    public function getStatus(User $user)
    {
        return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
    }

    public function __construct() { }

    public function getDefaultName() { return 'health_reach'; }

    public function getDefaultReportName() { return 'Health Reach'; }

    public function getDefaultStatusSummary($status) { return null; }
}

class JSPCoreBiometricsOrHealthCoachComplianceView extends ComplyWithCoreBiometricsComplianceView
{
    public function __construct($startDate, $endDate)
    {
        parent::__construct($startDate, $endDate);

        $this->coachView = new GraduateFromCoachingSessionComplianceView($startDate, $endDate);
    }

    public function getStatus(User $user)
    {
        $status = parent::getStatus($user);

        if(!$status->isCompliant()) {
            $coachStatus = $this->coachView->getStatus($user);

            if($coachStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            } else if($coachStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
            }
        }

        return $status;
    }

    private $coachView;
}

class JSP2010ComplianceProgram extends ComplianceProgram
{

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = null;

        if($preferredPrinter == 'ScreeningProgramReportPrinter' && $this->getActiveUser() !== null) {
            $printer = new JSP2010ScreeningTestPrinter();

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
            $printer = new JSP2010ComplianceProgramReportPrinter();
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

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $this->getComplianceStatusMapper()->addMapping(ComplianceStatus::NA_COMPLIANT, new ComplianceStatusMapping(
            'No status at this time. Will change if a Care Counselor is trying to reach you and/or required calls are not being made.',
            '/images/lights/whitelight.gif'
        ));

        $startGroupEndDate = '2010-08-05';

        $this->addComplianceViewGroup(
            ComplianceViewGroup::create('start', 'Starting Requirements')
                ->addComplianceView(
                CompleteScreeningComplianceView::create($startDate, $startGroupEndDate)
                    ->setName('complete_screening')
                    ->setReportName('<a href="/content/1094#a2screen">Complete the Wellness Screening</a>')
                    ->emptyLinks()
                    ->addLink(new Link('Results', '/content/989'))
            )
                ->addComplianceView(
                CompleteHRAComplianceView::create($startDate, $startGroupEndDate)
                    ->setName('complete_hra')
                    ->setReportName('<a href="/content/1094#a1hra">Complete the Health Power Assessment</a>')
            )
        );

        $this->addComplianceViewGroup(
            ComplianceViewGroup::create('ongoing', 'Ongoing Requirements (All Year/Quarters)')
                ->addComplianceView(
                JSPCareCounselorComplianceView::create(ComplianceStatus::NA_COMPLIANT)
                    ->setAttribute('deadline', 'Ongoing')
                    ->setName('one_care_counselor')
                    ->setReportName('<a href="/content/1094#b1calls">Make all required calls</a> to the Care Counselor before receiving certain types of health care and other times. If a nurse calls you, return their calls and work with them until you are told (by the nurse) you are done')
                    ->addLink(new Link('Show Call List', '/content/301725?sitemapIdentifier=229400&like_home=1'))
            )
                ->addComplianceView(
                CompleteAssignedELearningLessonsComplianceView::create($startDate, $endDate)
                    ->setAttribute('deadline', 'Ongoing')
                    ->setName('1_elearning')
                    ->setReportName('Pass extra e-learning Lessons from your Care Counselor and/or Health Coach - if recommended.')
            )
        );

        $oneGroupEndDate = '2010-08-05';
        $this->addComplianceViewGroup(
            ComplianceViewGroup::create('one', 'First 3 Months (May, June, and July 2010)')
                ->addComplianceView(
                UpdateDoctorInformationComplianceView::create($startDate, $oneGroupEndDate)
                    ->setName('doctor_information')
                    ->setReportName('<a href="/content/1094#b3docInfo">Have a Main Doctor</a>')
            )
                ->addComplianceView(
                UpdateContactInformationComplianceView::create($startDate, $oneGroupEndDate)
                    ->setName('contact_information')
                    ->setReportName('<a href="/content/1094#b2persInfo">Personal Contact Info Up-to-Date</a>')
            )
                ->addComplianceView(
                CompleteELearningGroupSet::create($startDate, $oneGroupEndDate, 'required')
                    ->setAllowAllLessons(true)
                    ->setName('2_elearning')
                    ->setReportName('Pass Group e-Learning Lessons')
                    ->setEvaluateCallback(function (User $u) { return $u->getRelationshipType() == Relationship::EMPLOYEE; })
            )
        );

        $twoGroupEndDate = '2010-11-08';
        $this->addComplianceViewGroup(
            ComplianceViewGroup::create('two', 'Second 3 Months (Aug, Sep & Oct 2010)')
                ->addComplianceView(
                JSPActivityComplianceView::create($startDate, $twoGroupEndDate)
                    ->setReportName('Participate in at least 1 qualified health improvement activity')
                    ->setAttribute('deadline', date('n/d/y', strtotime($twoGroupEndDate)))
                    ->setEvaluateCallback(function (User $u) { return $u->getRelationshipType() == Relationship::EMPLOYEE; })
            )
                ->addComplianceView(
                JSPCoreBiometricsOrHealthCoachComplianceView::create($startDate, $twoGroupEndDate)
                    ->setCompliancePointStatusMapper(new CompliancePointStatusMapper(60, 60))
                    ->setName('core_biometrics')
                    ->setReportName('Have a core biometric score of 60 or more points -OR- work with a doctor or health coach to improve it.')
                    ->addLink(new Link('Score Details', '?preferredPrinter=ScreeningProgramReportPrinter'))
                    ->addLink(new Link('Doctor Option/Form', '/content/1094#d2bCoach'))
                    ->addLink(new Link('Health Coach option/sign-up', '/content/8733'))
            )
                ->addComplianceView(
                CompleteELearningGroupSet::create('2010-08-01', $twoGroupEndDate, 'required_oct')
                    ->setAllowAllLessons(true)
                    ->setName('3_elearning')
                    ->setReportName('Pass New Group e-Learning Lessons')
                    ->setEvaluateCallback(function (User $u) { return $u->getRelationshipType() == Relationship::EMPLOYEE; })
            )
        );

        $threeGroupEndDate = '2011-02-09';
        $this->addComplianceViewGroup(
            ComplianceViewGroup::create('three', 'Third 3 Months (Nov, Dec & Jan 2011)')
                ->addComplianceView(
                CompleteSurveyComplianceView::create(1)
                    ->setName('does_it_work_survey')
                    ->setReportName('Complete the <strong>HAP Benefit</strong> Survey')
                    ->setAttribute('deadline', date('n/d/y', strtotime('2011-01-31')))
            )
                ->addComplianceView(
                CompleteELearningGroupSet::create('2010-11-09', $threeGroupEndDate, 'req_nov')
                    ->setName('4_elearning')
                    ->setReportName('Pass New Group e-Learning Lessons')
                    ->setEvaluateCallback(function (User $u) { return $u->getRelationshipType() == Relationship::EMPLOYEE; })
            )
        );

        $this->addComplianceViewGroup(
            ComplianceViewGroup::create('four', 'Last 3 Months (Feb, Mar & Apr 2011)')
                ->addComplianceView(
                CompleteELearningGroupSet::create('2011-02-01', '2011-04-30', 'required_feb')
                    ->setName('5_elearning')
                    ->setReportName('Pass any 2 of the new group e-Learning Lessons')
                    ->setNumberRequired(2)
                    ->setEvaluateCallback(function (User $u) { return $u->getRelationshipType() == Relationship::EMPLOYEE; })
            )
        );
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        $root = $query->getRootAlias();
        $query->andWhereIn("$root.relationship_type", array(Relationship::EMPLOYEE, Relationship::SPOUSE));

        parent::preQuery($query, $withViews);
    }
}

class JSP2010ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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

        #report img.light, #legend img.light {
            width:2em;
        }

        #legend {
            float:right;
            margin-left:2em;
            width:15em;
        }

        #legend td {
            vertical-align:top;
        }

        #information .miscellaneous {
            clear:both;
        }

        #_title {
            font-weight:bold;
            text-align:center;
        }
    </style>

    <div id="_title">New Benefit Incentives/Rewards: Premium Contributions & Penalties</div>
    <div id="information">
        <table id="legend">
            <thead>
                <tr>
                    <th colspan="2">Status Color Key:</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($status->getComplianceProgram()->getComplianceStatusMappings() as $mapping) : ?>
                <tr>
                    <td><img src="<?php echo $mapping->getLight() ?>" alt="" class="light"/></td>
                    <td>= <?php echo $mapping->getText() ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <p>
            To retain the lowest premium contribution and receive the greatest benefits, eligible employees and
            spouses MUST EACH take action and meet all criteria below:
        </p>
        <ol>
            <li>Complete <strong><u>BOTH</u></strong> starting requirements (A, below) by July 31, 2010.</li>
            <li>Complete ALL extra requirements by the deadlines during EACH current 3 month period (below).</li>
        </ol>
        <p>
            Employees and eligible spouses meeting all criteria will <u>keep their premium contribution low</u> AND <u>avoid</u>
            $1,020 to
            $2040 in <u>additional</u> payroll deductions per family over the next 12 months AND <u>avoid additional</u>
            out-of-pocket costs.
        </p>

        <p>Here are some tips about the table below and using it:</p>
        <ul>
            <li>Requirements for Employees and Spouses differ slightly.</li>
            <li>And, every three months the list of requirements changes. Please check this page regularly.</li>
            <li>In the first column, click on the text in blue for why the action is important.</li>
            <li>Use the links in the left column to get things done or needed information</li>
            <li><a href="/content/1094">Click here</a>, for the full explanation of requirements for the entire year.
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

                    <p>
                        <strong><?php echo $status->getUser()
                            ->getRelationshipType() == Relationship::EMPLOYEE ? 'As an Employee' : 'As a spouse' ?></strong>
                        in the plan, your current requirements for the premium contribution incentive are listed below.
                    </p>
                </th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th colspan="5">
                    <p><strong>Does your spouse have coverage under the health plan?</strong></p>

                    <p>If so, to keep the premium contribution as low as possible and avoid the premium contribution
                        increase:</p>
                    <ul>
                        <li>You must meet your requirements as listed above; AND</li>
                        <li>Your spouse must ALSO meet the requirements listed in ABCDE&amp;F when they login (using
                            their personal login).
                        </li>
                    </ul>
                    <p>If applicable, please be sure you are checking with each other to make sure all requirements are
                        being met by BOTH of you</li>
                </th>
            </tr>
        </tfoot>

        <tbody>
            <?php $i = 0 ?>
            <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
            <?php $group = $groupStatus->getComplianceViewGroup() ?>
            <?php if($group->getName() == 'screening') continue; ?>
            <tr class="group">
                <td><?php echo getLetterFromNumber($i).'. '.$group->getReportName() ?></td>
                <th class="date">Deadline</th>
                <th class="comment">Date Done</th>
                <th class="light">Status</th>
                <th class="links">Action Links</th>
            </tr>
            <?php $j = 0 ?>
            <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <?php $view = $viewStatus->getComplianceView() ?>
                <?php if($view->getName() == 'one_care_counselor' || $viewStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) : ?>
                    <?php
                    $reportName = $view->getReportName();

                    if($view->getName() == 'core_biometrics') {
                        $reportName .= sprintf(' <strong>My Score = %s</strong>', $viewStatus->getPoints());
                    }
                    ?>
                    <tr class="view">
                        <td class="name"><?php echo ($j + 1).'. '.$reportName ?></td>
                        <td class="date"><?php echo $view->getAttribute('deadline', $view instanceof DateBasedComplianceView ? $view->getEndDate('n/d/y') : 'See Below') ?></td>
                        <td class="comment"><?php echo $viewStatus->getComment() ?></td>
                        <td class="light"><img src="<?php echo $viewStatus->getLight(); ?>" class="light" alt=""/></td>
                        <td class="links"><?php echo implode(' ', $view->getLinks()) ?></td>
                    </tr>
                    <?php $j++ ?>
                    <?php endif ?>
                <?php endforeach ?>
            <?php if(!$j) : ?>
                <tr class="view">
                    <td class="name" colspan="5">No additional requirements this quarter.</td>
                </tr>
                <?php endif ?>
            <?php $i++ ?>
            <?php endforeach ?>
        </tbody>
    </table>
    <?php
    }
}
