<?php

class AutocamCoaching extends PlaceHolderComplianceView
{
    public function getStatus(User $user)
    {
        $status = parent::getStatus($user);

        if(in_array($user->getState(), array('CA', 'MA'))) {
            $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }

        return $status;
    }
}

class Autocam2010ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('hra');
        $requiredGroup->addComplianceView($hraView);

        $physicianForm = new CompleteScreeningComplianceView('2008-01-01', $programEnd);
        $physicianForm->setRequireOnlineEntry(true);
        $physicianForm->setName('physician_form');
        $requiredGroup->addComplianceView($physicianForm);

        $coachingView = new AutocamCoaching(ComplianceStatus::NA_COMPLIANT);
        $coachingView->setReportName('Coaching');
        $coachingView->setName('coaching');
        $requiredGroup->addComplianceView($coachingView);

        $this->addComplianceViewGroup($requiredGroup);
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, null);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Autocam2010ComplianceProgramReportPrinter();
    }
}

class Autocam2010ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $_user = Piranha::getInstance()->getUser();
        $this->printCSS();

        $complianceProgram = $status->getComplianceProgram();
        $requiredGroupStatus = $status->getComplianceViewGroupStatus('required');
        $hraViewStatus = $requiredGroupStatus->getComplianceViewStatus('hra');
        $physicianFormViewStatus = $requiredGroupStatus->getComplianceViewStatus('physician_form');
        $coachingViewStatus = $requiredGroupStatus->getComplianceViewStatus('coaching');
        ?>
    <div id="program_information">
        <p>
            The Autocam Wellness Program is focused on helping you and
            your family stay healthy. There are new easier requirements to maintain
            compliance in 2010.
        </p>

        <p>
            The requirements below outline the steps you need to do throughout the
            year. If you choose not to comply with the Wellness Program, you will be subject to additional premium
            sharing. Currently the annual differential is $1,896 per employee. This rate may change for 2011.
        </p>
    </div>
    <table id="program_status">
        <thead>
            <tr>
                <td class="nametitle">Hello <?php echo $_user->getFullName() ?></td>
            </tr>
            <tr class="headerrow">
                <td>Requirements</td>
                <td>My Requirements</td>
                <td>Status</td>
                <td>I did this*</td>
            </tr>
        </thead>
        <tbody>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                    <span class="title">HRA (Health Risk Appraisal)</span>
                    <a href="/content/11358" class="moreinfo">- more info</a>
                    <br/>
                    <a href="/content/989">Complete HRA</a>
                </td>
                <td class="myrequirementscolumn">
                    Yearly, Due <?php echo $complianceProgram->getEndDate('m/d/Y') ?>
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $hraViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <td class="subnote">
                    * If you disagree with your status, please call us 1-866-682-3020 x204.
                </td>
            </tr>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                    <span class="title">Physician Form</span>
                    <a href="/content/11358" class="moreinfo">- more info</a>
                    <br/>
                    Receive from your Primary Care Physician (Exam must be completed between 4/1/09 - 9/30/10.)<a
                    href="/resources/1963/2010Physician.Form.pdf">Download Physician Form</a>
                </td>
                <td class="myrequirementscolumn">
                    Yearly, Due <?php echo $complianceProgram->getEndDate('m/d/Y') ?>
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $physicianFormViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <td></td>
            </tr>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                    <span class="title">Health Coaching</span>
                    <a href="/content/11358" class="moreinfo">- more info</a>
                    <br/>only if contacted
                </td>
                <td class="myrequirementscolumn">
                    Must comply with coaching guidelines only if contacted.
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $coachingViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
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
        #program_status, #program_information {
            margin:25px 20px 10px;
            border-collapse:collapse;
        }

        #program_status .light {
            width:24px;
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
    </style>
    <?php
    }
}