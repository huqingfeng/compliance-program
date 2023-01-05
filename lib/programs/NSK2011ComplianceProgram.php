<?php

class ViewNSK2011HPAComplianceView extends DateBasedComplianceView
{
    public function __construct($s, $d)
    {
        $this
            ->setStartDate($s)
            ->setEndDate($d);
    }

    public function getStatus(User $user)
    {
        $logQuery = '
      SELECT COUNT(*) as views
      FROM request_logs
      WHERE user_id = ?
      AND request_uri REGEXP ?
    ';

        $db = Database::getDatabase();
        $db->executeSelect($logQuery, $user->getID(), 'content/(751|752)');

        $count = $db->getNextRow();

        if($count['views']) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getDefaultName()
    {
        return 'view_hpa_report';
    }

    public function getDefaultReportName()
    {
        return 'View HPA';
    }
}

class NSK2011Printer extends CHPComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $user = Piranha::getInstance()->getUser();
        ?>
    <style type="text/css">
        #overviewCriteria {
            width:100%;
            border-collapse:collapse;
        }

        #overviewCriteria th {
            background-color:#42669A;
            color:#FFFFFF;
            font-weight:normal;
            font-size:11pt;
            padding:5px;
        }

        #overviewCriteria td {
            width:33.3%;
            vertical-align:top;
        }
    </style>
    <p>Hello <?php echo $user->getFullName() ?>,</p>
    <p>Welcome to The NSK Wellness Website. This site was developed not only to track your wellness requirements, but
        also to be used
        as a great resource for health related topics and questions. We encourage you to explore the site while also
        fulfilling your requirements.
    </p>
    <p><strong>Step 1</strong>- Complete your on-site health screening</p>
    <p><strong>Step 2</strong>- View your <a href="/content/989"> results online</a>
        results online</p>
    <p>The current requirements and your current status for each are summarized below.</p>
    <table id="overviewCriteria">
        <thead>
            <tr>
                <th>Overview Criteria</th>
                <th>Incentive if done:</th>
                <th>If not done:</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <span>Complete the Wellness Screening, consultation, and earn at least x points from below.</span>
                </td>
                <td>
                    <span>You will be eligible for the premium health plan</span>
                </td>
                <td>
                    <span>You will be not be eligible for the premium health plan</span>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    }
}

class NSK2011ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $group = new ComplianceViewGroup('required', 'Required');

        $group
            ->addComplianceView(CompleteHRAComplianceView::create($start, $end))
            ->addComplianceView(CompleteScreeningComplianceView::create($start, $end))
            ->addComplianceView(ViewHPAComplianceView::create($start, $end));
        //->addComplianceView(
        //ComplyWithCotinineScreeningTestComplianceView::create($start, $end)
        //->setNoTestResultStatus(ComplianceStatus::NOT_COMPLIANT)
        //->setName('cotinine')
        //->setReportName('Cotinine')
        //);

        $this->addComplianceViewGroup($group);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new NSK2011Printer();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserFields(null, null, null, false, true, null, null, true);

        // They want the newest cotinine result

        $printer->addCallbackField('cotinine', function (User $user) {
            global $_db;

            $_db->executeSelect("
        SELECT cotinine
        FROM screening
        WHERE user_id = ?
        AND cotinine IS NOT NULL
        AND TRIM(cotinine) != ''
        ORDER BY date DESC, id DESC
        LIMIT 1
      ", $user->id);

            if($row = $_db->getNextRow()) {
                return $row['cotinine'];
            } else {
                return '';
            }
        });

        return $printer;
    }
}