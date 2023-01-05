<?php

class FZ2010ComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new FZ2010ProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserContactFields(true);

        return $printer;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        $currentProgram = $this;

        $hraGroup = new ComplianceViewGroup('HRA Questionnaire');

        $hraView = new CompleteHRAComplianceView($startDate, $endDate);
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA now', '/content/989'));
        $hraGroup->addComplianceView($hraView);

        $this->addComplianceViewGroup($hraGroup);

        $preventionGroup = new ComplianceViewGroup('Preventive Care');

        $physicalExamView = new CompletePreventionPhysicalExamComplianceView($startDate, $endDate);
        $physicalExamView->setReportName('Physical Exam<sup style="color:#FF0000">*</sup>');
        $physicalExamView->addLink(new Link('I did this', '/resources/1687/fz.verification.2011.pdf'));
        $physicalExamView->setMinimumAge(18);
        $physicalExamView->setStartDate(
            function ($format, User $user) use ($currentProgram) {
                $age = $user->getAge($currentProgram->getStartDate(), true);
                $programEnd = $currentProgram->getEndDate('U');
                $monthOffset = $age < 40 ? 24 : 12;

                return date($format, strtotime("-$monthOffset months", $programEnd));
            }
        );
        $preventionGroup->addComplianceView($physicalExamView);

        $this->addComplianceViewGroup($preventionGroup);

        $elearningGroup = new ComplianceViewGroup('e-Learning Lessons');

        $requiredLessonsView = new CompleteRequiredELearningLessonsComplianceView('2010-10-01', '2011-10-14');
        $elearningGroup->addComplianceView($requiredLessonsView);

        $electiveLessonsView = new CompleteAdditionalELearningLessonsComplianceView($startDate, $endDate);
        $electiveLessonsView->setReportName('Complete Required Additional eLearning Lessons');
        $electiveLessonsView->setNumberToComplete(4);
        $electiveLessonsView->setAllowPastCompleted(false);
        $elearningGroup->addComplianceView($electiveLessonsView);

        $this->addComplianceViewGroup($elearningGroup);
    }
}

class FZ2010ProgramReportPrinter extends CHPComplianceProgramReportPrinter
{
    public function printClientMessage()
    {
        $_user = Piranha::getInstance()->getUser();
        ?>
    <p>Hello <?php echo $_user->getFullName(); ?>,</p>
    <p>
        Welcome to the Feyen Zylstra Wellness Website! This site was developed to not
        only track your wellness requirements for 2011 but it can also be a great
        resource for health related topics and questions. Circle Health was selected
        to be the long term administrator of our program to assure that all individual
        information will remain completely private. We encourage you to explore the
        site while also fulfilling your 2011 requirements that include completing a
        Health Power Assessment, complying with the preventative health screening, and
        learning about your health through the E-learning lessons. These requirements
        must be completed by 09/30/2011 in order to not incur a $750.00 fee
        (another $750.00 fee for insured primary spouses) that will be payroll
        deducted in January 2012.
    </p>
    <br/>
    <br/>

    <a href="/resources/1687/fz.verification.2011.pdf">Print Verification form here</a>

    <br/>
    <br/>


    <p>
        The <u>current</u> requirements and your <u>current</u> status
        for each are summarized below.
    </p>


    <br/>
    <br/>

    <a href="/request_archive_collections/show?id=11">View 2010 Report Card</a>

    <br/>
    <br/>
    <?php
    }

    public function printClientNote()
    {
        ?>
    <p><sub><span style="color:#FF0000">*</span> Please note that our insurance covers annual physicals per calendar
        year NOT by the date of your last physical. For example: if you received your physical on 9/15/09, you can get
        another physical anytime in 2010; you don’t have to wait until after 9/15/10.</sub></p>
    <p><sub><span style="color:#FF0000">*</span> Please note as well, that eLearning Lessons must be taken in the
        current program year. If you took a given lesson previously, you would need to take it again to get
        credit.</sub></p>

    <?php
    }
}