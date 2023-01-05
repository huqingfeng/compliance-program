<?php

use hpn\steel\query\SelectQuery;

class Amway2015ComplianceProgram extends ComplianceProgram
{
    /**
     * Redirects users to a content page if they are spouses
     *
     * @param sfActions $actions
     */
    public function handleInvalidUser(sfActions $actions)
    {
        $actions->getUser()->setNoticeFlash('Spouses are not required to participate in the compliance program. Thank you.');

        $actions->redirect('/');
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        if($this->getMode() == ComplianceProgram::MODE_INDIVIDUAL) {
            $query->andWhere(
                sprintf('%s.relationship_type != ?', $query->getRootAlias()), Relationship::SPOUSE
            );
        } else if($this->getMode() == ComplianceProgram::MODE_ADMIN) {
            $query->andWhere(
                sprintf('%s.client_id != 2305', $query->getRootAlias())
            );
        }

        parent::preQuery($query, $withViews);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Amway2015ComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, true, null, null, null, null, true);
        $printer->setShowUserFields(null, null, null, false, true);

        $printer->addGroupTypeByAlias('department');

        $printer->addCallbackField('programparticipant', function (User $user) {
            return (string) SelectQuery::create()
                ->hydrateSingleScalar()
                ->from('users')
                ->select('programparticipant')
                ->where('id = ?', array($user->id))
                ->execute();
        });

        $printer->addCallbackField('employee_id', function (User $user) {
            return (string) $user->getUserUniqueIdentifier('amway_employee_id');
        });

        $printer->addCallbackField('client_executive', function(User $user) {
            return $user->hasAttribute(Attribute::CLIENT_EXECUTIVE_USER) ? 1 : 0;
        });

        return $printer;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '5000M');

        $programStart = $this->getStartDate('U');
        $programEnd = $this->getEndDate('U');

        $requiredGroup = new ComplianceViewGroup('Required');

        $screeningView = new CompleteScreeningComplianceView($programStart, '2015-09-30');
        $screeningView->setReportName('Onsite Health Screening');
        $requiredGroup->addComplianceView($screeningView);

        $hraView = new CompleteHRAComplianceView('2015-06-29', '2015-10-31');
        $hraView->setReportName('Health Questionnaire');
        $requiredGroup->addComplianceView($hraView);

        $consultationView = new CompletePrivateConsultationComplianceView($programStart, '2015-11-25');
        $consultationView->setReportName('Private Consultation/Coaching Session');
        $consultationView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]=11'));
        $requiredGroup->addComplianceView($consultationView);

        $this->addComplianceViewGroup($requiredGroup);
    }
}

class Amway2015ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    private function printTableRows(ComplianceProgramStatus $status)
    {
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
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
        } else {
            $viewName .= '<span class="notRequired">(Required)</span>';
        }

        return $viewName;
    }

    private function printTableRow(ComplianceViewStatus $status)
    {
        $view = $status->getComplianceView();
        ?>
    <tr>
        <td class="resource"><?php echo $this->getViewName($view); ?></td>
        <td class="phipstatus">
            <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
            <?php
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                echo "<br/>Date Completed:<br/>".$status->getComment();
            } else if($status->getStatus() == ComplianceViewStatus::NA_COMPLIANT) {
                echo "<br/>N/A";
            }
            ?>
        </td>
        <td class="moreInfo">
            <?php if($view instanceof CompleteHRAComplianceView) : ?>
            <span style="font-size:0.85em"></span>
            <?php endif ?>
        </td>
        <td class="exemption">
            <?php
            $i = 0;
            foreach($view->getLinks() as $link) {
                echo $i++ > 0 ? '<br/>' : ' ';
                echo $link;
            }
            ?>
        </td>
    </tr>
    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        use_stylesheet('/css/compliance/Amway20092010ComplianceProgram.css');
        $user = $status->getUser();
        ?>
    <p>Hello <?php echo $user; ?></p>

    <em>Using a shared computer?</em>
    <strong>If you are not <?php echo $user; ?>, <a href="/logout">Please click here</a>.</strong>
    </p>
    <p>We continually look for ways to enhance the Optimal You incentive program and process to ensure we are making it convenient and beneficial for you.</p>
    <p style="color:#ff0000">To be eligible for the Optimal You incentive, participants must:</p>
    <p>1. Complete the health risk assessment (HRA) questionnaire<br />
       2. Complete the on-site health screening<br />
       3. Participate in one required coaching session</p>
    <p style="color:#ff0000">Key dates to remember and complete in order to be eligible for the incentive to be paid in 2016:</p>
    <p> 1.    Dates to complete the HRA questionnaire: July 1 - October 31<br />
        2.    Deadline for screening participation: September 30, 2015<br />
        3.    Deadline for coaching completion: November 25, 2015<br /></p>
    <p style="color:#ff0000">Other highlights to the 2015 program include:</p>
        <ul>
        <li>The on-site health screenings and blood panels will occur in July in California and August - September in Michigan.</li>
        <ul><li>On-demand packets will be sent to service center employees prior to August 1.</li></ul></ul>
        <ul><li>The on-site coaching sessions will begin in August in California and in October in Michigan.</li>
        <ul><li>Telephonic coaching sessions will start in August for the service centers.</li></ul></ul></p>

        <p  style="color:#ff0000">Amway-insured employee participants who meet all of the requirements will receive a $500 contribution into their Health Savings Account (HSA).</p>
        <p><a href="/resources/5679/OY_Incentive_2015_Flyer with_QA2.062615.pdf">More details on the 2015 health screenings and Optimal You incentive</a>
















    <hr/>
    <div id="phipSubPages">
        My Incentive Report Card Links
        <div id="subpagelinks">
            <!--<a href="/resources/3829/2013 Incentive PlanP2.pdf">FAQ</a>-->
            <a href="/support">Need Assistance</a>
            <!--<a href="/resources/3923/Health Screening Exception Form-2013.pdf">Screening Exception Form</a><br/>-->
            <a href="<?php echo url_for('compliance_programs/index?id=337') ?>">2013/2014 Incentive Status</a>

            <a href="<?php echo url_for('compliance_programs/index?id=267') ?>">2012/2013 Incentive Status</a>
            <a href="<?php echo url_for('compliance_programs/index?id=207') ?>">2011/2012 Incentive Status</a>
            <a href="<?php echo url_for('compliance_programs/index?id=92') ?>">2010/2011 Incentive Status</a>

            <a href="<?php echo url_for('compliance_programs/index?id=16') ?>">2009/2010 Incentive Status</a>
            <a href="/request_archive_collections/show?id=12">2008/2009 Incentive
                Status</a>
            <a href="/request_archive_collections/show?id=10">2007/2008 Incentive
                Status</a>
        </div>
    </div>
    <div id="sectionBorder"></div>
    <div id="phip">
        <div class="pageTitle">My Incentive Report Card</div>
        <table id="legend">
            <tr>
                <td id="firstColumn">LEGEND</td>
                <td id="secondColumn">
                    <table id="secondColumnTable">
                        <tr>
                            <td>
                                <img src="/images/lights/greenlight.gif" class="light" alt=""/> Completed
                            </td>
                            <td>
                                <img src="/images/lights/yellowlight.gif" class="light" alt=""/> Partially Completed
                            </td>
                            <td>
                                <img src="/images/lights/redlight.gif" class="light" alt=""/> Not Completed
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
            <thead>
                <tr>
                    <th class="resource">Resource</th>
                    <th class="status">Status</th>
                    <th class="information">More Info</th>
                    <th class="links">Links</th>
                </tr>
            </thead>
            <tbody>
                <tr id="totalComplianceRow">
                    <td class="resource">Overall Compliance</td>
                    <td class="status">
                        <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
                    </td>
                    <td class="information"></td>
                    <td class="links"></td>
                </tr>
                <?php $this->printTableRows($status); ?>
            </tbody>

        </table>

        <div id="endNote">
            <div>If you have any questions about your Optimal You report card please call toll free: (866) 682-3020 ext.
                207.
            </div>
            <br/>
        </div>
        <div style="clear: both;"></div>
    </div>
    <?php
    }
}