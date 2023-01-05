<?php

class IntegrityAerospaceGroup2012ComplianceProgram extends ComplianceProgram
{
    public function hasPartiallyCompliantStatus()
    {
        return false;
    }

    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $mapping = new ComplianceStatusMapper();

        $mapping->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Completed', '/images/shape/lights/notext-green.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Completed', '/images/shape/lights/notext-yellow.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Completed', '/images/shape/lights/notext-red.jpg'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Required', '/images/shape/lights/notext-black.jpg')
        ));

        $this->setComplianceStatusMapper($mapping);

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $hraView = new CompleteHRAComplianceView($programStart, '2012-11-30');
        $hraView->setName('hra');
        $hraView->setReportName('HRA (Health Risk Appraisal)');
        $requiredGroup->addComplianceView($hraView);

        $screeningForm = new CompleteScreeningComplianceView($programStart, '2012-11-30');
        $screeningForm->setName('screening');
        $screeningForm->setReportName('Biometric Screening');
        $requiredGroup->addComplianceView($screeningForm);

        $coachingView = new AttendAppointmentComplianceView($programStart, '2013-05-31');
        $coachingView->setReportName('Health Coaching');
        $coachingView->setName('coaching');
        $coachingView->setNumberRequired(2);
        $coachingView->setOptional(true);
        $requiredGroup->addComplianceView($coachingView);

        // Only used to affect admin compliance - this isn't shown on
        // the report card itself.

        $spouseView = new RelatedUserCompleteComplianceViewsComplianceView(
            $this,
            array('hra', 'screening', 'coaching'),
            Relationship::get()
        );

        $requiredGroup->addComplianceView($spouseView);

        $this->addComplianceViewGroup($requiredGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new IntegrityAerospaceGroup2012ComplianceProgramReportPrinter();
    }
}

class IntegrityAerospaceGroup2012ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');
        $hraViewStatus = $requiredGroupStatus->getComplianceViewStatus('hra');
        $screeningViewStatus = $requiredGroupStatus->getComplianceViewStatus('screening');
        $coachingViewStatus = $requiredGroupStatus->getComplianceViewStatus('coaching');

        if($user->relationshipUsers->count()) {
            $spouse = $user->relationshipUsers->getFirst();
            $employee = $status->getUser();
            $status->getComplianceProgram()->setActiveUser($spouse);
            $spouseStatus = $status->getComplianceProgram()->getStatus();
            $status->getComplianceProgram()->setActiveUser($employee);

            $spouseRequiredGroupStatus = $spouseStatus->getComplianceViewGroupStatus('required');
            $spouseHraViewStatus = $spouseRequiredGroupStatus->getComplianceViewStatus('hra');
            $spouseScreeningViewStatus = $spouseRequiredGroupStatus->getComplianceViewStatus('screening');
            $spouseCoachingViewStatus = $spouseRequiredGroupStatus->getComplianceViewStatus('coaching');
        } else {
            $spouseStatus = false;
        }

        $this->printCSS();
        ?>
    <p>
        The Integrity Aerospace Group’s Wellness Program is focused on helping you and your family stay healthy. There
        are 2 requirements (unless you are contacted and need to complete the coaching calls) to maintain compliance in
        2013 and be eligible for the $600 incentive.
    </p>

    <p>
        Spouses that are on IAG’s medical plan are required to participate in order for the employee to receive the $600
        incentive.


    </p>

    <p>
        The requirements below outline the steps you will need to complete. If you choose not to participate in the
        wellness program, you will not be eligible for the $600 incentive.</p>

    </div>
    <div id="name"><?php echo $user->getFullName() ?></div><br/>

<div id="program_information">
    <div id="legend">
        <?php foreach($status->getComplianceProgram()->getComplianceStatusMapper()
                          ->getMappings() as $sstatus => $mapping) {
        ?>
        <?php if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT || $status->getComplianceProgram()
            ->hasPartiallyCompliantStatus()
        ) {
            ?>
            <span class="legendEntry"><img src="<?php echo $mapping->getLight(); ?>"
                class="light"/> <?php echo $mapping->getText(); ?></span>
            <?php } ?>
        <?php } ?>
    </div>
    <table id="program_status">
        <thead>
            <tr class="headerrow">
                <td>Requirements</td>
                <td>Deadline for Completion</td>
                <td>My Status</td>
                <?php if($spouseStatus) : ?>
                    <td>Spouse's Status</td>
                <?php endif ?>
                <td>Additional Information*</td>
            </tr>
        </thead>
        <tbody>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                    <span class="title"><?php echo $hraViewStatus->getComplianceView()->getReportName() ?></span>
                    <br/>
                    <a href="/content/11358" class="moreinfo"></a>

                    <a href="/content/989" class="moreinfo">Complete HRA</a>
                </td>
                <td class="myrequirementscolumn">
                    <?php echo $hraViewStatus->getComplianceView()->getEndDate('m/d/Y')  ?>
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $hraViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <?php if($spouseStatus) : ?>
                    <td class="statuscolumn">
                        <img src="<?php echo $spouseHraViewStatus->getLight() ?>" class="light" alt=""/>
                    </td>
                <?php endif ?>
                <td class="subnote">
                    * If you disagree with your status, please call us 616-524-5450 x204.
                </td>
            </tr>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                        <span class="title"><?php echo $screeningViewStatus->getComplianceView()
                            ->getReportName() ?></span>
                    <br/>
                    Attend an on-site screening at your location on the dates available
                </td>
                <td class="myrequirementscolumn">
                    <?php echo $screeningViewStatus->getComplianceView()->getEndDate('m/d/Y') ?><br/>
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $screeningViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <?php if($spouseStatus) : ?>
                <td class="statuscolumn">
                    <img src="<?php echo $spouseScreeningViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <?php endif ?>
                <td class="subnote">
                    * If you are unable to attend an onsite screening, please contact HR for a packet that can be
                    used with your primary care physician.
                </td>
            </tr>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                        <span class="title"><?php echo $coachingViewStatus->getComplianceView()
                            ->getReportName() ?></span>
                    <br/>
                    Only if contacted
                </td>
                <td class="myrequirementscolumn">
                    Must comply with coaching guidelines only if contacted.
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $coachingViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <?php if($spouseStatus) : ?>
                    <td class="statuscolumn">
                        <img src="<?php echo $spouseCoachingViewStatus->getLight() ?>" class="light" alt=""/>
                    </td>
                <?php endif ?>
                <td></td>
            </tr>
        </tbody>
    </table>
        <?php
    }

    protected function printCSS()
    {
        ?>
    <style type="text/css">
        #program_status, #program_information, #legend, #name {
            margin:25px 20px 10px;
            border-collapse:collapse;
        }

        #program_status a {
            color:#CC6633;
            text-decoration:underline;
        }

        #program_status td {
            padding:3px;
            font-size:.95em;
        }

        #program_status a:hover {
            color:#691A90;
        }

        #program_status .nametitle {
            font-size:1.3em;
            font-weight:bold;
        }

        #program_status .headerrow td {
            background-color:#000000;
            color:#FFFFFF;
            border-left:1px solid #CC6633;
            border-right:1px solid #CC6633;
            font-size:1.1em;
        }

        #program_status .title {
            font-weight:bold;
        }

        #program_status tbody td {
            vertical-align:top;
        }

        #program_status .requiredrow td {
            padding-bottom:10px;
        }

        #program_status .requirementscolumn {
            width:33%;
            border-right:1px solid #CC6633;
        }

        #program_status .requirementscolumn a.moreinfo {
            font-style:italic;
        }

        #program_status .myrequirementscolumn {
            width:30%;
            border-right:1px solid #CC6633;
        }

        #program_status .statuscolumn {
            width:10%;
            border-right:1px solid #CC6633;
            text-align:center;
        }

        #program_status .subnote {
            font-size:.8em;
        }

        #program_information {
            font-weight:bold;
        }

        #legend {
            clear:both;
            text-align:center;
        }

        #name {
            text-align:center;
            font-weight:bold;
            color:#42669A;
            font-size:12pt;
            float:left;
        }

        .legendEntry {
            text-align:center;
            padding:2px;
        }

        .legendEntry .light {
            vertical-align:bottom;
        }

        .myrequirementscolumn {
            font-weight:bold;
        }
    </style>
    <?php
    }
}