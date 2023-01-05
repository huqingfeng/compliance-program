<?php

class Glenbrook2012Printer extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->tableHeaders['points_earned'] = '# Tickets Earned';
        $this->tableHeaders['points_possible'] = '# Tickets Possible';
        $this->tableHeaders['links'] = 'Action Links';
        ?>
    <style type="text/css">
        .phipTable {
            width:100%;
            border-collapse:collapse;
            font-size:8pt;
        }

        .phipTable .headerRow, .phipTable .headerRow th {
            font-weight:bold !important;
            color:#FFFFFF;
            font-size:10pt;
        }

        .phipTable td.links {
            width:190px;
        }
    </style>

    <script type="text/javascript">
        $(function () {
            $('.view-win .points').first().remove();
            $('.view-run .points').first().remove();
            $('.view-lnl .points').first().attr('rowspan', 3);
        });
    </script>

    <div style="text-align:center;font-weight:bold">And Report Card</div>

    <p>Welcome to your summary page of My Rewards/To-Dos and Report Card for the 2011-2012 academic year.</p>

    <p>The Activity Points and Raffle Tickets you earn will make you eligible for many more awards this year! </p>

    <ul>
        <li>Semester 1 Raffle/Dec. 16th –Twenty $100 gift certificates to be awarded (10 winners at GBS/OCC and 10
            winners at GBN/District). Tickets you earn through December 15th will be entered in a drawing to be held on
            December 16th.;

        </li>
        <li>Semester 2 Raffle/May 6th -- Twenty $100 gift certificates to be awarded (10 at GBS/OCC and 10 at
            GBN/District). Tickets you earned through May 6th as well as those earned in Semester 1 will be entered in a
            drawing to be held on May 7th

        </li>
        <li>A quality wearable for earning 350 Activity Points by May 6th;</li>
        <li>End-of-Year Party and Raffle -- A chance to win a $1,000 travel voucher and many, many more prizes donated
            by local merchants will be awarded. All tickets you earned in Semester 1 and Semester 2 will again be
            entered in this mega-drawing! YOU MUST BE PRESENT TO WIN. (Date of Party/Raffle to be announced.)
        </li>

    </ul>

    <p>These are <strong>in addition </strong>to the rewards of better health, health care and well-being from your
        actions throughout the year.</p>

    <p><strong>How can I earn raffle tickets?</strong></p>
    <p>Here's an example of how this would work:</p>
    <p><u>Semester 1 – Let’s say you earned a tickets for:</u></p>

    <ul>
        <li>Participating in Wellness Screening/HPA</li>
        <li>Participating in Lunch n Learn #1</li>
        <li>Participating in Lunch n Learn #2</li>
        <li>Reach 150 Activity Points</li>
        <li>A Charity Walk/Run in which you participated during the 1st Semester.</li>

    </ul>
    <p>TOTAL TICKETS EARNED FOR DEC. 16TH DRAWING = 5</p>

    <p><u>Semester 2 – Then, you earned tickets for:</u></p>

    <ul>
        <li>Participating in Lunch n Learn #3</li>
        <li>Participating in Lunch n Learn #4</li>
        <li>Reach 150 Activity Points (if you didn’t earn a ticket first semester)</li>
        <li>Reach 275 Activity Points</li>
        <li>Reach 350 Activity Points</li>
        <li>Each Charity Walk/run in which you participate during the 2nd Semester</li>
        <li>If you’re on the winning team from Semester 1</li>
        <li>If you’re on the winning team from Semester 2</li>


    </ul>

    <p>TICKETS EARNED IN SEMESTER 2 = 7</p>
    <p>+ TICKETS EARNED IN SEMESTER 1 + 5 </p>

    <p>TOTAL TICKETS EARNED FOR MAY 7TH DRAWING = 12</p>

    <p><strong>For more info and tips to get started:</strong></p>

    <p>Click on links in first column to learn more about each option or <a href="/content/1094">here for details about
        all options</a>

    <p>To begin earning points and raffle tickets, click on the links in the Action Links column. </p>

    <p>Please note the following: </p>

    <ul>
        <li>Action Item 1.A. – your Report Card will be updated to reflect an earned ticket after the HPA/Screening
            Report has been mailed to your home (about 3 weeks after your screening date.)
        </li>
        <li>Action Items 1. B, C, and D will be updated to reflect an earned ticket after verification of your
            participation is received from your Wellness Coordinator.
        </li>
        <li>Action Item 2.A. – it’s important to enter Activity/Exercise points on a timely basis. All Activity/Exercise
            points for a given month must be entered by the 15th of the following month; e.g., all September points must
            be entered by October 15th. (EXCEPTION: Points earned in April must be entered by midnight, May 6th. )
        </li>
    </ul>

    <p><strong>Rewards Report Card:</strong></p>
    <br/>
    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        parent::printReport($status);
        ?>
    <br/>
    <br/>
    <!--<p>
      *  Tickets earned for 1BCD will show after: 1)You sign-up/enter info; and 2)Participation/verification is received via your wellness coordinator.</li>
    </p> -->
    <?php
    }

    public function printCustomRows($status)
    {
        $totalTickets = $status->getComplianceViewGroupStatus('one')->getPoints();

        ?>
    <tr class="headerRow">
        <th colspan="4"><strong>3</strong>. Raffle Eligibility Status & Tickets Earned</th>
    </tr>
    <tr>
        <td>
            Semester Raffle - December 16, 2011<br/>
            Semester Raffle - May 7, 2011<br/>
            End-of-Year Raffle at End-of-Year Party - Date TBA
        </td>
        <td colspan="3">
            <?php echo sprintf('%s %s Earned', $totalTickets, $totalTickets == 1 ? 'ticket' : 'tickets') ?>
        </td>
    </tr>
    <?php
    }

    protected function printView(ComplianceViewGroup $group, ComplianceViewStatus $viewStatus)
    {
        // @TODO total hack to change the text back to points for the second group
        parent::printView($group, $viewStatus);

        $this->printedViews++;

        if($this->printedViews == 6) {
            $this->tableHeaders['points_earned'] = '# Points Earned';
            $this->tableHeaders['points_possible'] = '# Points Possible';
        }

    }

    protected function printGroupPointBasedTotal(ComplianceViewGroupStatus $groupStatus)
    {
        $group = $groupStatus->getComplianceViewGroup();

        if($group->getName() == 'one') {
            $group = $groupStatus->getComplianceViewGroup();
            ?>
        <tr>
            <td style="text-align:right;color:#0000FF;"><span
                style="font-style:italic">Total Tickets as of <?php echo date('m/d/Y') ?></span> = &nbsp;&nbsp;</td>
            <td class="points"><?php echo $groupStatus->getPoints() ?></td>
            <td class="points"><?php echo $group->getMaximumNumberOfPoints() ?></td>

        </tr>
        <?php
        } else {
            parent::printGroupPointBasedTotal($groupStatus);
        }
    }

    private $printedViews = 0;
}

class Glenbrook2012PhysicalActivityPoints extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate, $points = null)
    {
        $this->points = $points;
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'phys_act_'.$this->points;
    }

    public function getDefaultReportName()
    {
        return 'Physical Activity - '.$this->points.' Points';
    }

    public function getStatus(User $user)
    {
        $db = Database::getDatabase();

        $db->executeSelect('
      SELECT SUM(p.points) AS points
      FROM mileage_points p
      INNER JOIN mileage_registrants r ON r.id = p.registrant_id
      INNER JOIN mileage_date_ranges d ON d.id = p.date_range_id
      WHERE r.user_id = ?
      AND d.record_start_date >= ?
      AND d.record_end_date <= ?
    ', $user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d')
        );

        $row = $db->getNextRow();

        $points = $row['points'];

        return new ComplianceViewStatus(
            $this,
            $this->points === null ? null : ($points >= $this->points ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT),
            $this->points === null ? $points : null
        );
    }

    private $points;
}

class Glenbrook2012ComplianceProgram extends ComplianceProgram
{
    public static function getTickets(User $user, $withHRA = true)
    {
        $start_date = sfConfig::get('app_legacy_mileage_monsters_start_date');
        $end_date = sfConfig::get('app_legacy_mileage_monsters_end_date');

        $user_registration = MileageRegistrants::getRegistrationForUser($user, $start_date, $end_date);

        $totalTickets = 0;

        if($user_registration) {
            foreach($user_registration->getAssignedTickets($start_date, $end_date) as $ticket) {
                $totalTickets += $ticket->getNumTickets();
            }

            if($withHRA && $user_registration->completedHRAScreening($start_date, $end_date)) {
                $totalTickets++;
            }

            foreach($user_registration->getMileageEvents($start_date, $end_date) as $mileage_event) {
                $totalTickets += $mileage_event->getNumTickets();
            }

            /**
            foreach($user_registration->getAutomaticTickets($start_date, $end_date) as $automaticTicket){
            $totalTickets += $automaticTicket->getNumTickets();
            }*/
        }

        return $totalTickets;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Glenbrook2012Printer();
        $printer->setShowLegend(false);
        $printer->setShowTotal(false);
        $printer->setShowPointBasedGroupTotal(true);

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $group = new ComplianceViewGroup('one', 'Earn raffle tickets from A-G below through '.$this->getEndDate('F j, Y'));
        $group->setPointsRequiredForCompliance(0);

        $scrHra = new CompleteHRAAndScreeningComplianceView($startDate, $endDate);
        $scrHra->setReportName('Complete Wellness Screening & HPA');
        $scrHra->setAttribute('report_name_link', '/content/1094#1ascreen');
        $scrHra->setMaximumNumberOfPoints(1);
        $scrHra->addLink(new Link('Sign-Up', '#'));
        $scrHra->addLink(new Link('HPA & Results', '/content/989'));
        $scrHra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $group->addComplianceView($scrHra);

        $lnl = new Glenbrook2012TicketsView(null, 0);
        $lnl->setName('lnl');
        $lnl->setReportName('Participate in <strong>Shape Your Life</strong> Lunch & Learns');
        $lnl->setAttribute('report_name_link', '/content/1094#1blunch');
        $lnl->addLink(new Link('View Topics/Sign-Up', '/content/4820?action=Listing&actions[Listing]=eventList&actions[Calendar]=eventBulletin&actions[My+Registrations+%26+Waitlists]=viewScheduledEvents'));
        $group->addComplianceView($lnl);

        $run = new PlaceHolderComplianceView(null, 0);
        $run->setName('run');
        $run->setReportName('Participate in Charity Walk/Runs');
        $run->setAttribute('report_name_link', '/content/1094#1cwalkrun');
        $run->emptyLinks();
        //$run->addLink(new Link('Enter Info & Verify w/Coordinator', '/content/12048?action=showActivity&activityidentifier=23'));
        $run->addLink(new Link('Details & Local Events', '/content/5614'));
        $run->setMaximumNumberOfPoints(7);
        $group->addComplianceView($run);

        $winTeam = new PlaceHolderComplianceView(null, 0);
        $winTeam->setName('win');
        $winTeam->setReportName('Participate on a winning team');
        $winTeam->setAttribute('report_name_link', '/content/1094#1dteam');
        //$winTeam->addLink(new Link('Sign-Up<br />', '/content/8733999'));
        $winTeam->addLink(new FakeLink('1 extra ticket for each member of the <a href="/content/110308">winning team</a> each semester.', '#'));
        $winTeam->setMaximumNumberOfPoints(2);
        $group->addComplianceView($winTeam);

        $pointsOne = new Glenbrook2012PhysicalActivityPoints($startDate, $endDate, 150);
        $pointsOne->setReportName('Get 150 points (see #2 below)');
        //$pointsOne->addLink(new FakeLink('Earn a ticket for a semester', '#'));
        $pointsOne->setMaximumNumberOfPoints(1);
        $pointsOne->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $group->addComplianceView($pointsOne);

        $pointsTwo = new Glenbrook2012PhysicalActivityPoints($startDate, $endDate, 275);
        $pointsTwo->setReportName('Get 275 points');
        //$pointsTwo->addLink(new FakeLink('Earn a ticket for a semester', '#'));
        $pointsTwo->setMaximumNumberOfPoints(1);
        $pointsTwo->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $group->addComplianceView($pointsTwo);

        $pointsThree = new Glenbrook2012PhysicalActivityPoints($startDate, $endDate, 350);
        $pointsThree->setReportName('Get 350 points');
        //$pointsThree->addLink(new FakeLink('Earn a ticket for a semester', '#'));
        $pointsThree->setMaximumNumberOfPoints(1);
        $pointsThree->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $group->addComplianceView($pointsThree);

        $this->addComplianceViewGroup($group);

        $points = new ComplianceViewGroup('Earn points during the year');
        $points->setPointsRequiredForCompliance(0);

        $phys = new Glenbrook2012PhysicalActivityPoints($startDate, $endDate);
        $phys->setReportName('Get Regular Physical Activity / Exercise');
        $phys->setAttribute('report_name_link', '/content/1094#2afitness');
        $phys->emptyLinks();
        $phys->addLink(new Link('Enter/Update Points', '/content/110307'));
        $phys->setMaximumNumberOfPoints(480);
        $points->addComplianceView($phys);

        $this->addComplianceViewGroup($points);


    }
}

class Glenbrook2012TicketsView extends PlaceHolderComplianceView
{
    public function getStatus(User $user)
    {
        $tickets = Glenbrook2012ComplianceProgram::getTickets($user, false);

        return new ComplianceViewStatus($this, null, $tickets);
    }
}
