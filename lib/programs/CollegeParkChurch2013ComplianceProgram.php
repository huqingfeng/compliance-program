<?php

class CollegeParkChurch2013ComplianceProgram extends ComplianceProgram
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

    $coaching = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
    $coaching->setName('coaching');
    $coaching->setReportName('Complete Private Consultation');
    $required->addComplianceView($coaching);

    $this->addComplianceViewGroup($required);
  }

  public function getProgramReportPrinter($preferredPrinter = null)
  {
    return new CollegeParkChurch2013ComplianceProgramReportPrinter();
  }
}

class CollegeParkChurch2013ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

  <p>2013 Report Card View</p>


  <p>Welcome to The College Park Church Wellness Website! This site was developed not only to track
      your wellness participation requirements, but also to be used as a great resource for health-related
      topics and questions. We encourage you to explore the site while also fulfilling your requirements.
      Employees on the Medical Plan that participate in the 3 steps outlined below will receive a $540.00
      annual ($45/month) premium reduction for their incentive. Employees not covered under the Medical plan
      AND spouses (though not a requirement of incentive) may also participate in the program at no additional cost.
  </p>

  <p><strong>Step 1</strong>- Complete your Health Screening</p>

  <p><strong>Step 2</strong>- Complete your Health Power Questionnaire</p>

  <p><strong>Step 3</strong>- Complete your Private Consultation</p>

  <p>The current requirements and your current status for each are summarized below.</p>



  <?php
  }
}