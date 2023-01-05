<?php

class Android2013CampaignTobaccoComplianceView extends ComplianceView
{
    public function __construct($startDate, $endDate) 
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function getStatus(User $user) 
    {
        $hraScreeningView = new CompleteHRAAndScreeningComplianceView($this->startDate, $this->endDate);
        $hraScreeningViewStatus = $hraScreeningView->getStatus($user);
        
        
        $nonSmokerView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($this->startDate, $this->endDate);
        $nonSmokerViewStatus = $nonSmokerView->getStatus($user);
        
        if($hraScreeningViewStatus->getStatus() != ComplianceStatus::COMPLIANT ) {
            return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        } elseif($nonSmokerViewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            return new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT);
        }
    }
    
    public function getDefaultStatusSummary($status) 
    {
        return null;
    }
    
    public function getDefaultName() 
    {
        return 'non_tobacco';
    }
    
    public function getDefaultReportName() 
    {
        return 'Non Tobacco';
    }
}

class Android2013CampaignComplianceProgram extends ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new Android2013CampaignPrinter();
        $printer->showResult(true);
        return $printer;
    }
    
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();


        $campaigns = new ComplianceViewGroup('Campaigns');
        $campaigns->setPointsRequiredForCompliance(0);

        $healthyHolidays = new Android2014WeightProgram('2012-11-01', '2013-12-31');
        $healthyHolidays->setReportName('Campaign 1 - Healthy Holidays');
        $healthyHolidays->setAttribute('report_name_link', '/content/android_campaign1');
        $healthyHolidays->setName('c1_healthy_holidays');
        $healthyHolidays->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15000, 0, 0, 0));
        $healthyHolidays->setStatusSummary(ComplianceStatus::COMPLIANT, ' ');
        $healthyHolidays->setAllowPointsOverride(true);
        $campaigns->addComplianceView($healthyHolidays);
        
        $c1 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $c1->setReportName('Campaign 2 - Your Choice');
        $c1->setAttribute('report_name_link', '/content/android_campaign2');
        $c1->setName('c2_choice');
        $c1->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15000, 0, 0, 0));
        $c1->setStatusSummary(ComplianceStatus::COMPLIANT, ' ');
        $campaigns->addComplianceView($c1);

        $hshnt = new Android2013CampaignTobaccoComplianceView($programStart, $programEnd);
        $hshnt->setReportName('Campaign 3 - Health Screening & HRA/Non-Tobacco User');
        $hshnt->setAttribute('report_name_link', '/content/android_campaign3');
        $hshnt->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15000, 5000, 0, 0));
        $hshnt->setStatusSummary(ComplianceStatus::COMPLIANT, ' ');
        $campaigns->addComplianceView($hshnt);

        $c2 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $c2->setReportName('Campaign 4 - Your Choice');
        $c2->setAttribute('report_name_link', '/content/android_campaign4');
        $c2->setName('c4_choice');
        $c2->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(15000, 0, 0, 0));
        $c2->setStatusSummary(ComplianceStatus::COMPLIANT, ' ');
        $campaigns->addComplianceView($c2);
        
        $this->addComplianceViewGroup($campaigns);
    }
}


class Android2013CampaignPrinter extends CHPStatusBasedComplianceProgramReportPrinter
{
    public function  __construct()
    {
        $this->setPageHeading('Wellness Campaigns');
    }
    
    public function printClientMessage()
    {
        ?>
        <script type="text/javascript">
            $(function() {
                $('.totalRow.group-requirements').nextAll().each(function() {
                    $(this).find(':nth-child(1)').attr('colspan', 2);
                    $(this).find(':nth-child(2)').remove();
                    $(this).find(':nth-child(2)').html('');
                    $(this).find(':nth-child(4)').attr('colspan', 2);
                    $(this).find(':nth-child(5)').remove();
                });
            });
        </script>
    <style type="text/css">
  

        .headerRow.group-campaigns {
            border-top: 2px solid #D7D7D7;
        }

        .view-bf_bmi.statusRow4 {
            background-color:#BEE3FE;
        }

        .view-bf_bmi.statusRow3 {
            background-color:#DDEBF4;
        }

        .view-bf_bmi.statusRow2 {
            background-color:#FFFDBD;
        }

        .view-bf_bmi.statusRow1 {
            background-color:#FFDC40;
        }

        .view-bf_bmi.statusRow0 {
            background-color:#FF6040;
        }
    </style>
    <p>
        <style type="text/css">
            #legendEntry3, #legendEntry2 {
                display:none;
            }
        </style>
    </p>
    <p>
        In 2013, you have an opportunity to earn additional wellness points that may be redeemed for gifts throughout
        the course of the campaigns. Those who participate in all 4 campaigns will be eligible to enter a drawing for an
        additional gift. For more details on the individual campaigns, please click on the links below.

    </p>

    <?php
    }    
    
    public function printClientNote()
    {
        ?>
        <p style="margin-top:20px;font-size:smaller;">You may accumulate a maximum of 60,000 points for the year for the campaigns.
           Please see the <a href="/content/wellness_campaigns">2013 Wellness Campaigns</a> document for complete details.</p>

        <p style="margin-top:20px;font-size:smaller;">Your Choice for Campaigns 2&4 include: Smoking Cessation Challenge, Weight Loss Challenge, Annual Physical Exam,
            My Health Matters. <br /> You cannot select the same challenge twice.</p>

        <?php
    }    
}