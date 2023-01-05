<?php

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
    <p>
        Welcome to The NSK Wellness Website. This site was developed not only
        to track your wellness requirements, but also to be used as a great
        resource for health related topics and questions. We encourage you to
        explore the site while also fulfilling your requirements. Below are the
        requirements for 2012 to receive your incentive for 2013. To view the
        new program for 2013 and your current standing based off of your 2012
        results <a href="/compliance_programs?id=210">click here</a>.</p>
    <p>
        <strong>Step 1</strong>- Complete your on-site health screening
        (or on-demand health screening) and HRA</p>
    <p>
        <strong>Step 2</strong>- View your results online once BOTH Screening
        & HRA results are available (approximately 3-5 days after your health screening)</p>
    <p>You will also have the opportunity to participate in a web/phone consultation 4-6 weeks
        following your health screening. This will give you the opportunity to have a nurse
        explain your results and answer any questions you may have regarding your
        online report. If after participating in the consultation you are eligible
        for an extended risk coaching program you can also participate in this year-
        round phone and e-mail based program where you will be paired with a coach
        to help assist you with your high risk areas. Both the Consultations and
        Extended Risk Coaching are voluntary for 2012. Information on these new programs
        will be available at the health screenings.</p>
    <p>The current requirements and your current status for each are summarized below.</p>
    <!--<table id="overviewCriteria">
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
    </table>-->
    <?php
    }
}

class NSK2011ComplianceProgramBeta extends ComplianceProgram
{
    public function loadGroups()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        $group = new ComplianceViewGroup('required', 'Required');

        $viewHra = new ViewHpaReportComplianceView($start, $end, ViewHpaReportComplianceView::LOGIC_BOTH_AT_ONCE);
        $viewHra->setReportName('View HRA/Screening Reports');
        $viewHra->setName('view_hpa_report');

        $group
            ->addComplianceView(CompleteHRAComplianceView::create($start, $end))
            ->addComplianceView(CompleteScreeningComplianceView::create($start, $end))
            ->addComplianceView($viewHra);
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
        $printer->setShowUserLocation(true);
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