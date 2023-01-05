<?php
class TransmaticCoaching extends PlaceHolderComplianceView
{
}

class Transmatic2010ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setName('hra');
        $requiredGroup->addComplianceView($hraView);

        $physicianForm = new CompleteScreeningComplianceView('2011-01-01', $programEnd);
        $physicianForm->setReportName('Physician Form');
        $physicianForm->setRequireOnlineEntry(true);
        $physicianForm->setName('physician_form');
        $requiredGroup->addComplianceView($physicianForm);

        $coachingView = new TransmaticCoaching(ComplianceStatus::NA_COMPLIANT);
        $coachingView->setReportName('Health Coaching');
        $coachingView->setName('coaching');
        $requiredGroup->addComplianceView($coachingView);

        $elearningView = new CompleteRequiredELearningLessonsComplianceView($programStart, $programEnd);
        $elearningView->setReportName('E-Learning');
        $elearningView->setName('elearning');
        $requiredGroup->addComplianceView($elearningView);

        $this->addComplianceViewGroup($requiredGroup);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Transmatic2010ComplianceProgramReportPrinter();
    }
}

class Transmatic2010ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
        $elearningStatus = $requiredGroupStatus->getComplianceViewStatus('elearning');
        ?>
    <div id="program_information">
        <p>
            The Trans-Matic Health Management Program is focused on helping you and
            your family stay healthy. There are new easier requirements to maintain
            compliance in 2011.
        </p>

        <p>
            The requirements below outline the steps you need to do throughout the
            year. If you choose not to comply with the Health Management program, you
            will be subject to a higher level of employee cost sharing. The
            annual differential will be $500 per person / $1,000 per couple. This rate is for 2012.
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
                    * If you disagree with your status, please contact us at 616-524-5450 x204.
                </td>
            </tr>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                    <span class="title"><a href="/resources/1784/2011physician.transmatic.pdf">Physician Form</a></span>
                    <a href="/content/11358" class="moreinfo">- more info</a>
                    <br/>
                    Receive from your Primary Care Physician <a href="/resources/1784/2011physician.transmatic.pdf">Download
                    Form</a>
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
                    <br/>&bull;Two coaching sessions completed by June 30, 2011
                    <br/>&bull;Two coaching sessions completed by September 30, 2011
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $coachingViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <td></td>
            </tr>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                    <span class="title">E-Learning</span>
                    <a href="/content/9420?action=lessonManager&tab_alias=required" class="moreinfo">- more info</a>
                </td>
                <td class="myrequirementscolumn">
                    Must be completed by June 30, 2011
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $elearningStatus->getLight() ?>" class="light" alt=""/>
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
