<?php

class SBCParrRichey2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowStatus(false, false, false);
        $printer->setShowText(true, true, true);
        $printer->setShowUserContactFields(null, null, true);

        $printer->addCallbackField('location', function (User $user) {
            return $user->getLocation();
        });

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->emptyLinks();
        $screening->addLink(new Link('Results', '/content/989'));
        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $required->addComplianceView($hra);

        $coaching = new AttendAppointmentComplianceView($startDate, $endDate);
        $coaching->bindTypeIds(array(11, 21, 46));
        $coaching->setName('coaching');
        $coaching->setReportName('Complete Private Consultation');
        $required->addComplianceView($coaching);

        $this->addComplianceViewGroup($required);

        $elearning = new CompleteELearningLessonsComplianceView($startDate, $endDate, null, 3, null, null);
        $elearning->setName('elearning');
        $elearning->setReportName('Complete 3 e-Learning Lessons');
        $required->addComplianceView($elearning);

        $this->addComplianceViewGroup($required);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SBCParrRichey2016ComplianceProgramReportPrinter();
    }
}

class SBCParrRichey2016ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printHeader(ComplianceProgramStatus $status)
    {
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

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>


        <p>Welcome to The SBC Wellness Website! This site was developed not only to track
            your wellness participation requirements, but also to be used as a great resource for health-related
            topics and questions. We encourage you to explore the site while also fulfilling your requirements.
        </p>

        <p><strong>Step 1</strong>- Complete your Health Screening</p>

        <p><strong>Step 2</strong>- Complete your Health Power Questionnaire</p>

        <p><strong>Step 3</strong>- Complete Private Consultation</p>

        <p><strong>Step 4</strong>- Complete 3 e-learning lessons of your choice by 10/1/2016</p>

        <p>The current requirements and your current status for each are summarized below.</p>



    <?php
    }
}