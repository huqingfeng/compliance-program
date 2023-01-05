<?php

class NN2016ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', 'Required');

        $hraView = new CompleteHRAComplianceView($programStart, '2016-08-15');
        $hraView->setName('hra');
        $requiredGroup->addComplianceView($hraView);

        $physicianForm = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $physicianForm->setReportName('Physician Form');
        $physicianForm->setName('physician_form');
        $physicianForm->setAlternativeComplianceView(
            new CompleteScreeningComplianceView('2016-04-01', '2016-08-31')
        );
        $requiredGroup->addComplianceView($physicianForm);

        $coachingView = new Autocam2013Coaching(ComplianceStatus::NA_COMPLIANT);
        $coachingView->setReportName('Coaching');
        $coachingView->setName('coaching');
        $requiredGroup->addComplianceView($coachingView);

        $this->addComplianceViewGroup($requiredGroup);
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true, null, null);
        $printer->setShowUserFields(null, null, true, false, true, null, true);
        $printer->setShowUserLocation(true);

        $printer->addCallbackField('employee_ssn', function (User $user) {
            if($user->getRelationshipType() == 2) {
                return $user->getRelationshipUser() ? $user->getRelationshipUser()->getSocialSecurityNumber() : '';
            } else {
                return $user->getSocialSecurityNumber();
            }
        });

        $printer->addCallbackField('primary_email_address', function(User $user) {
            $userEmails = $userEmails = $user->getEmailAddresses();;
            if(isset($userEmails['Primary'])) {
                return $userEmails['Primary']['email_address'];
            } else {
                return null;
            }
        });

        $printer->addCallbackField('secondary_email_address', function(User $user) {
            $userEmails = $userEmails = $user->getEmailAddresses();;
            if(isset($userEmails['Alternate'])) {
                return $userEmails['Alternate']['email_address'];
            } else {
                return null;
            }
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new NN2016ComplianceProgramReportPrinter();
    }
}

class NN2016ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
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
            The NN. Inc Wellness Program is focused on helping you and your family stay healthy.
            There are three requirements to maintain compliance in 2016.
        </p>

        <p>The requirements below outline the steps you need to do throughout the year.
            If you choose not to comply with the Wellness Program, you will be subject to additional premium sharing.</p>

        <div id="legend">
            <span>
                Not Completed<br/>
                <img class="light" src="/images/lights/redlight.gif" alt="Not Completed" />
            </span>

            <span>
                Partially Completed<br/>
                <img class="light" src="/images/lights/yellowlight.gif" alt="Partially Completed" />
            </span>

            <span>
                Completed<br/>
                <img class="light" src="/images/lights/greenlight.gif" alt="Completed" />
            </span>

            <span>
                Not Required<br/>
                <img class="light" src="/images/lights/whitelight.gif" alt="Not Required" />
            </span>

            <div style="clear:both"></div>
        </div>
    </div>

    <table id="program_status">
        <thead>
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
                    <a href="/compliance/amway_2015/my-health">Complete HRA</a>
                </td>
                <td class="myrequirementscolumn">
                    Yearly, Due <?php echo $complianceProgram->getEndDate('m/d/Y') ?>
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $hraViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <td class="subnote">
                    * If you disagree with your status, please call us 616-524-5450 x204.
                </td>
            </tr>
            <tr class="requiredrow">
                <td class="requirementscolumn">
                    <span class="title">Physician Form</span>
                    Receive from your Primary Care Physician (Exam must be completed between 04/1/16 - 08/31/16)<a
                    href="/resources/6914/2016 Physician Form-NN, Inc..pdf">Download Physician Form</a>
                </td>
                <td class="myrequirementscolumn">
                    Yearly, Due 08/31/2016
                </td>
                <td class="statuscolumn">
                    <img src="<?php echo $physicianFormViewStatus->getLight() ?>" class="light" alt=""/>
                </td>
                <td></td>
            </tr>
            <tr class="requiredrow">
                <td class="requirementscolumn">

                    <span class="title">Health Coaching</span>
                    <a href="/resources/6974/NN, Inc. Health Coaching.pdf" class="moreinfo">- more info</a>
                    <br/>only if contacted
                </td>
                <td class="myrequirementscolumn">
                    Deadline 12/31/2016
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
        #legend {
            text-align:center;
        }

        #legend span {

            display:inline-block;
            width:120px;
        }

        #program_status, #program_information {
            margin:25px 20px 10px;
            border-collapse:collapse;
        }

        #program_status .light, #program_information .light {
            width:25px;
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