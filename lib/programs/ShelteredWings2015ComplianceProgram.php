<?php

class ShelteredWings2015ComplianceProgram extends ComplianceProgram
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

    $required = new ComplianceViewGroup('Services');

    $screening = new CompleteScreeningComplianceView($startDate, $endDate);
    $screening->emptyLinks();
    $screening->addLink(new Link('Results', '/content/989'));

    $required->addComplianceView($screening);

    $hra = new CompleteHRAComplianceView($startDate, $endDate);
    $required->addComplianceView($hra);

    $coaching = new CompletePrivateConsultationComplianceView($startDate, $endDate);
    $coaching->setName('coaching');
    $coaching->setReportName('Complete Private Consultation');
    $required->addComplianceView($coaching);

    $this->addComplianceViewGroup($required);
  }

  public function getProgramReportPrinter($preferredPrinter = null)
  {
    return new ShelteredWings2015ComplianceProgramReportPrinter();
  }
}

class ShelteredWings2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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



      <p>Welcome to Sheltering Wings Wellness Website! This site was developed not only to track your
          wellness participation requirements, but also to be used as a great resource for health-related
          topics and questions. We encourage you to explore the site while also competing your health screening,
          HPA questionnaire and private consultation. The personal information on this site is completely
          secure and confidential. This is a free service to you and we hope you enjoy these benefits by
          learning more about your personal health!
  </p>

  <p><strong>Step 1</strong>- Complete your Health Screening</p>

  <p><strong>Step 2</strong>- Complete your Health Power Questionnaire (HPA)</p>

  <p><strong>Step 3</strong>- Complete your Private Consultation</p>

  <p>The current services and your current status for each are summarized below.</p>



  <?php
  }
}