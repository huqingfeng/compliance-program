<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class LoyolaUniversity2015ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(null, null, true);

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new LoyolaUniversity2015ComplianceReportPrinter();
        return $printer;
    }
    
    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        
        $required = new ComplianceViewGroup('required', 'Core Actions Required by <span style="color:red">November 21st, 2014</span>');

        $screening = new CompleteScreeningComplianceView($startDate, '2014-11-21');
        $screening->setReportName('Annual Onsite Wellness Screening');
        $screening->setAttribute('report_name_link', '/content/1094#ascreen');
        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, '2014-11-21');
        $hra->setReportName('Complete the Health Power Assessment');
        $hra->setAttribute('report_name_link', '/content/1094#bhpa');
        $required->addComplianceView($hra);
        
        $this->addComplianceViewGroup($required);
        
        $eLearningGroup = new ComplianceViewGroup('elearning', 'You must complete at least three (10 available) eLearning lessons listed below by <span style="color:red">February 27th, 2015</span>');
        $elearningEndDate = '2015-02-27';

        $managingCholesterol = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(184));
        $managingCholesterol->setAttribute('_screening_printer_hack', 10);
        $managingCholesterol->setName('managing_cholesterol');
        $managingCholesterol->setReportName('Managing Cholesterol');
        $eLearningGroup->addComplianceView($managingCholesterol);

        $cholesterolControl = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(1));
        $cholesterolControl->setName('cholesterol_control');
        $cholesterolControl->setReportName('Cholesterol Control');
        $eLearningGroup->addComplianceView($cholesterolControl);
        
        $dietaryFats = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate,  new ELearningLesson_v2(1011));
        $dietaryFats->setName('dietary_fats');
        $dietaryFats->setReportName('Dietary Fats');
        $eLearningGroup->addComplianceView($dietaryFats);
        
        $eatingHealthy = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(178));
        $eatingHealthy->setName('Eating Healthy');
        $eatingHealthy->setReportName('Eating Healthy');
        $eLearningGroup->addComplianceView($eatingHealthy);
        
        $preventingDiabetes = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(115));
        $preventingDiabetes->setName('preventing_diabetes');
        $preventingDiabetes->setReportName('Preventing Diabetes');
        $eLearningGroup->addComplianceView($preventingDiabetes);
        
        $healthyLife = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(180));
        $healthyLife->setName('healthy_life');
        $healthyLife->setReportName('Exercising for a Healthy Life');
        $eLearningGroup->addComplianceView($healthyLife);
        
        $backExercise = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(198));
        $backExercise->setName('back_exercise');
        $backExercise->setReportName('Back Exercise');
        $eLearningGroup->addComplianceView($backExercise);
        
        $managingStress = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(181));
        $managingStress->setName('managing_stress');
        $managingStress->setReportName('Managing Stress');
        $eLearningGroup->addComplianceView($managingStress);        
        
        $battlingGerms = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(1086));
        $battlingGerms->setName('battling_germs');
        $battlingGerms->setReportName('Battling Germs: Facts & Tips');
        $eLearningGroup->addComplianceView($battlingGerms);

        $wiseHealthDecisions = new CompleteELearningLessonComplianceView($startDate, $elearningEndDate, new ELearningLesson_v2(20));
        $wiseHealthDecisions->setName('wise_health_decisions');
        $wiseHealthDecisions->setReportName('Making Wise Health Decisions');
        $eLearningGroup->addComplianceView($wiseHealthDecisions);

        $eLearningGroup->setNumberOfViewsRequired(3);
        
        $this->addComplianceViewGroup($eLearningGroup);
    }
    
}



class LoyolaUniversity2015ComplianceReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->pageHeading = '<img src="/images/hmii/loyola/loyola_university_logo.png" style="width:320px;" /><br/ >
                                <br/ >LOYOLA UNIVERSITY CHICAGO <br /><br />2015 WELLNESS REWARDS PROGRAM';
        $this->setScreeningResultsLink(new FakeLink('Complete eLearning Lessons', '#'));
    }
    
    public function printHeader(ComplianceProgramStatus $status)
    {
        $elearningStatus = $status->getComplianceViewGroupStatus('elearning');
        
        $numCompliant = 0;
        foreach($elearningStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->isCompliant()) $numCompliant++;
        }
        
        ?>

    <script type="text/javascript">
        $(function(){
           $('.phipTable tbody').children(':eq(5)').children(':eq(3)').html('<a href="/content/9420">LINK TO ELEARNING CENTER</a>');
           
           

           <?php if($numCompliant > 0 && $numCompliant < 3) : ?>
               $('td:contains("Status of All Criteria =")').parent().html('<td colspan="1" class="center">Status of All Criteria =</td>' +
                                                                  '<td style="color: red;background-color:white; font-size: 9pt;">' +
                                                                  'Still Working <br />not listed</td><td class="white"><img src="/images/lights/redlight.gif" class="light">' +
                                                                  '</td><td colspan="2"></td>');

           <?php elseif($numCompliant >= 3) : ?>
               $('td:contains("Status of All Criteria =")').parent().html('<td colspan="1" class="center">Status of All Criteria =</td>' +
                                                                  '<td style="background-color:white;"></td><td class="white"><img src="/images/lights/greenlight.gif" class="light">' +
                                                                  '</td><td colspan="2"></td>');
           <?php else : ?>
               $('td:contains("Status of All Criteria =")').attr('colspan', 2);
           <?php endif ?>
        });
    </script>
    
    <style type="text/css">
        .status img {
            width:25px;
        }
    </style>
    
    <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>
    
    <p>There will be a number of opportunities this year to participate in the Wellness Program. For those employees who participate 
        in the 2015 Open Enrollment Wellness Incentives, you will be able to receive a $100 incentive if completed by <span style="color:red;">February 27th, 2015</span>. 
        To receive the incentive, you MUST take action and meet all criteria below:</p>
    
    <ol>
        <li>Participate in the Wellness Screening</li>
        <li>Complete the Health Power Assessment (HPA) – Confidential Lifestyle Questionnaire</li>
        <li>Complete <strong>at least</strong> three online eLearning Lessons</li>
    </ol>
    
    <p>To achieve the $100 incentive, all actions listed must be completed by <span style="color:red;">February 27th, 2015</span>.</p>
    
    <p style="font-weight: bold;">If you do not complete the required actions by the required deadlines, you will not receive the incentive.</p>
    
    <?php
    }
}