<?php

use hpn\steel\query\SelectQuery;


class Bison2015ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate('U');

        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $coreGroup = new ComplianceViewGroup('core', 'Program');
        
        $hraView = new CompleteHRAComplianceView($programStart, '2015-10-08');
        $hraView->setReportName('Health Power Profile Questionnaire');
        $hraView->setName('complete_hra');
        $hraView->setAttribute('report_name_link', '/content/1094#ahpa');
        $hraView->setAttribute('requirement', 'Complete the Online Health Risk Assessment (HRA) Questionnaire');
        $hraView->setAttribute('deadline', '10/08/2015');
        $hraView->emptyLinks();
        $hraView->addLink(new Link('Take HRA', '/content/989'));
        $coreGroup->addComplianceView($hraView);
        
        $screeningView = new CompleteScreeningComplianceView($programStart, '2015-09-11');
        $screeningView->setReportName('2015 Wellness Screening');
        $screeningView->setName('complete_screening');
        $screeningView->setAttribute('report_name_link', '/content/1094#bScreen');
        $screeningView->setAttribute('requirement', 'Complete the Biometric Health Screening');
        $screeningView->setAttribute('deadline', '09/11/2015');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', '/content/how-to-schedule'));
        $coreGroup->addComplianceView($screeningView);
        
        $this->addComplianceViewGroup($coreGroup);

        $wellnessGroup = new ComplianceViewGroup('wellness_programs', 'Program');
        $wellnessGroup->setPointsRequiredForCompliance(50);

        // While it contains the employee constant, an employee can't have a
        // related employee user so it doesn't matter.

        $nonSmokerView = new ComplyWithSmokingByBeingNonSmokerHRAQuestionComplianceView($programStart, $programEnd);
        $nonSmokerView->setName('non_smoker_view');
        $nonSmokerView->setReportName('Non-tobacco User');
        $nonSmokerView->setAttribute('report_name_link', '/sitemaps/health_centers/15946');      
        $nonSmokerView->setAttribute('requirement', 'ALL dependents must be non-tobacco users if family coverage');
        $nonSmokerView->addLink(new Link('Review Tobacco Lessons', '/content/9420?action=lessonManager&tab_alias=tobacco'));
        $wellnessGroup->addComplianceView($nonSmokerView);
        

        $BMIView = new ComplyWithBMIScreeningTestComplianceView($programStart, $programEnd);
        $BMIView->setReportName('Body Mass Index (BMI)');
        $BMIView->setName('bmi');
        $BMIView->setAttribute('report_name_link', '/sitemaps/health_centers/15932');  
        $BMIView->setAttribute('requirement', '18.5 – 24.9');
        $BMIView->overrideTestRowData(18.5, null, null, 24.9);
        $BMIView->addLink(new Link('Review Body Metrics Lessons', '/content/9420?action=lessonManager&tab_alias=body_fat'));
        $wellnessGroup->addComplianceView($BMIView);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($programStart, $programEnd);
        $bloodPressureView->setName('blood_pressure');
        $bloodPressureView->setAttribute('report_name_link', '/sitemaps/health_centers/15919');
        $bloodPressureView->setAttribute('requirement_systolic', 'Systolic 130 or lower');
        $bloodPressureView->setAttribute('requirement_diastolic', 'Diastolic 84 or lower');
        $bloodPressureView->overrideSystolicTestRowData(null, null, null, 130);
        $bloodPressureView->overrideDiastolicTestRowData(null, null, null, 84);
        $bloodPressureView->addLink(new Link('Review Blood Pressure Lessons', '/content/9420?action=lessonManager&tab_alias=blood_pressure'));
        $bloodPressureView->setPostEvaluateCallback(function($status) {
           if($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        });
        $wellnessGroup->addComplianceView($bloodPressureView);
        
        $ldlCholesterolView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
        $ldlCholesterolView->setName('ldl');
        $ldlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $ldlCholesterolView->setAttribute('requirement', '< 130 mg/dL');
        $ldlCholesterolView->addLink(new Link('Review Blood Fat Lessons', '/content/9420?action=lessonManager&tab_alias=blood_health'));
        $ldlCholesterolView->overrideTestRowData(null, null, null, 129.999);
        $wellnessGroup->addComplianceView($ldlCholesterolView);        
        
        $hdlCholesterolView = new ComplyWithHDLScreeningTestComplianceView($programStart, $programEnd);
        $hdlCholesterolView->setName('hdl');
        $hdlCholesterolView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $hdlCholesterolView->setAttribute('requirement', '> 40 mg/dL');
        $hdlCholesterolView->overrideTestRowData(40.001, null, null, null);
        $wellnessGroup->addComplianceView($hdlCholesterolView);        
        
        $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
        $trigView->setName('triglycerides');
        $trigView->setAttribute('report_name_link', '/sitemaps/health_centers/15913');
        $trigView->setAttribute('requirement', '< 150 mg/dL');
        $trigView->overrideTestRowData(null, null, null, 149.999);
        $trigView->addLink(new Link('Sign-Up', '/content/how-to-schedule'));
        $wellnessGroup->addComplianceView($trigView, false);        
        
        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
        $glucoseView->setName('glucose');
        $glucoseView->setAttribute('report_name_link', '/sitemaps/health_centers/15401');
        $glucoseView->setAttribute('requirement', '< 100 mg/dL');
        $glucoseView->overrideTestRowData(null, null, null, 99.999);
        $glucoseView->addLink(new Link('Review Blood Sugar Lessons', '/content/9420?action=lessonManager&tab_alias=blood_sugars'));
        $wellnessGroup->addComplianceView($glucoseView);        
        
        $wellnessGroup->setPointsRequiredForCompliance(100);
        $this->addComplianceViewGroup($wellnessGroup);

        $optionalCostGroup = new ComplianceViewGroup('optional_cost', 'Optional Cost Savings');

        $onsiteOne = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $onsiteOne->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $onsiteOne->setName('onsite_one');
        $onsiteOne->setReportName('Complete 1 onsite or telephonic health coaching call');
        $onsiteOne->setAttribute('requirement', 'Complete 1 onsite or telephonic health coaching call');
        $optionalCostGroup->addComplianceView($onsiteOne);

        $onsiteNPOne = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $onsiteNPOne->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
        $onsiteNPOne->setName('onsite_np_one');
        $onsiteNPOne->setReportName('Complete 1 onsite visit with NP or work with your physician on your health care plan');
        $onsiteNPOne->setAttribute('requirement', 'Complete 1 onsite visit with NP or work with your physician on your health care plan');
        $optionalCostGroup->addComplianceView($onsiteNPOne);

        $onsiteTwo = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $onsiteTwo->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $onsiteTwo->setName('onsite_two');
        $onsiteTwo->setReportName('Complete 2 onsite or telephonic health coaching calls');
        $onsiteTwo->setAttribute('requirement', 'Complete 2 onsite or telephonic health coaching calls');
        $optionalCostGroup->addComplianceView($onsiteTwo);

        $onsiteNPTwo = new PlaceHolderComplianceView(ComplianceViewStatus::NOT_COMPLIANT);
        $onsiteNPTwo->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $onsiteNPTwo->setName('onsite_np_two');
        $onsiteNPTwo->setReportName('Complete 2 onsite visits with NP or work with your physician on your health care plan');
        $onsiteNPTwo->setAttribute('requirement', 'Complete 2 onsite visits with NP or work with your physician on your health care plan');
        $optionalCostGroup->addComplianceView($onsiteNPTwo);

        $this->addComplianceViewGroup($optionalCostGroup);

        
    }

    public function getAdminProgramReportPrinter()
    {
        $that = $this;

        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserContactFields(true);
        $printer->setShowUserFields(null, null, null, null, null, null, null, null, true);

        $printer->addCallbackField('Tier Achieved', function(User $user) use($that) {
            return $that->getCalculatedTier($user);
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $printer = new Bison2015ComplianceProgramReportPrinter();

        return $printer;
    }
    
    
    public function getTierStatus(User $user)
    {
        $views = array('non_smoker_view' ,'bmi', 'blood_pressure', 'ldl', 'hdl', 'triglycerides', 'glucose');

        $currentUser = $this->getActiveUser();

        $this->setActiveUser($user);
        $this->setDoDispatch(false);

        $status = $this->getStatus();

        $this->setDoDispatch(true);
        $this->setActiveUser($currentUser);

        $ret = array('hra_date' => '', 'screening_date' => '', 'number_of_levels' => 0);

        foreach($views as $viewName) {
            $viewStatus = $status->getComplianceViewStatus($viewName);
            if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $ret['number_of_levels']++;
            }
        }
        
        $hraStatus = $status->getComplianceViewStatus('complete_hra');
        $ret['hra_date'] = $hraStatus->getComment();
        
        $screeningStatus = $status->getComplianceViewStatus('complete_screening');
        $ret['screening_date'] = $screeningStatus->getComment(); 

        return $ret;
    }
    
    public function getCalculatedTier(User $user)
    {
        $employeeTier = $this->getFinalIndividualCalculatedTier($user);

        foreach($user->relationshipUsers as $user) {
            if($user->getRelationshipType() == Relationship::SPOUSE) {
                $spouseTier = $this->getFinalIndividualCalculatedTier($user);
            } elseif ($user->getRelationshipType() == Relationship::OTHER_DEPENDENT) {
                if(!isset($otherDependentOneTier)) {
                    $otherDependentOneTier = $this->getFinalIndividualCalculatedTier($user);
                } elseif(!isset($otherDependentTwoTier)) {
                    $otherDependentTwoTier = $this->getFinalIndividualCalculatedTier($user);
                } else {
                    $otherDependentThreeTier = $this->getFinalIndividualCalculatedTier($user);
                }
            }
        }

        $tierLevels = array(
            'Tier 4 Basic (Increased Savings)' => '4',
            'Tier 3 Basic (Savings)'             => '3',
            'Tier 2 Basic (Base Level)'         => '2',
            'Tier 1 Basic (Premium Increase)'   => '1'
        );

        $lowestTier = $employeeTier;
        if(isset($spouseTier) && $tierLevels[$lowestTier] > $tierLevels[$spouseTier]) {
            $lowestTier = $spouseTier;
        }

        if(isset($otherDependentOneTier) && $tierLevels[$lowestTier] > $tierLevels[$otherDependentOneTier]) {
            $lowestTier = $otherDependentOneTier;
        }

        if(isset($otherDependentTwoTier) && $tierLevels[$lowestTier] > $tierLevels[$otherDependentTwoTier]) {
            $lowestTier = $otherDependentTwoTier;
        }

        if(isset($otherDependentThreeTier) && $tierLevels[$lowestTier] > $tierLevels[$otherDependentThreeTier]) {
            $lowestTier = $otherDependentThreeTier;
        }

        return $lowestTier;
    }

    public function getInitialIndividualCalculatedTier(User $user)
    {
        $employeeTier = $this->getTierStatus($user);
        $employeeHraCompleted = !empty($employeeTier['hra_date']) ? true : false;
        $employeeScreeningCompleted = !empty($employeeTier['screening_date']) ? true : false;
        $employeeNumTarget = $employeeTier['number_of_levels'];


        if($employeeHraCompleted && $employeeScreeningCompleted && $employeeNumTarget >= 5) {
            return 'Tier 4 Basic (Increased Savings)';
        } elseif($employeeHraCompleted && $employeeScreeningCompleted && $employeeNumTarget >= 3) {
            return 'Tier 3 Basic (Savings)';
        } elseif($employeeHraCompleted && $employeeScreeningCompleted && $employeeNumTarget >= 1) {
            return 'Tier 2 Basic (Base Level)';
        } else {
            return 'Tier 1 Basic (Premium Increase)';
        }

    }

    public function getFinalIndividualCalculatedTier(User $user)
    {
        $currentUser = $this->getActiveUser();

        $this->setActiveUser($user);
        $this->setDoDispatch(false);

        $status = $this->getStatus();

        $this->setDoDispatch(true);
        $this->setActiveUser($currentUser);

        $onsiteOneStatus = $status->getComplianceViewStatus('onsite_one');
        $onsiteNPOneStatus = $status->getComplianceViewStatus('onsite_np_one');

        $onsiteTwoStatus = $status->getComplianceViewStatus('onsite_two');
        $onsiteNPTwoStatus = $status->getComplianceViewStatus('onsite_np_two');


        $oneLevelUp = false;
        $twoLevelUp = false;
        if($onsiteOneStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $onsiteNPOneStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $oneLevelUp = true;
        }

        if($onsiteTwoStatus->getStatus() == ComplianceStatus::COMPLIANT
            && $onsiteNPTwoStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $twoLevelUp = true;
        }

        $employeeTier = $this->getTierStatus($user);
        $employeeHraCompleted = !empty($employeeTier['hra_date']) ? true : false;
        $employeeScreeningCompleted = !empty($employeeTier['screening_date']) ? true : false;
        $employeeNumTarget = $employeeTier['number_of_levels'];


        if($employeeHraCompleted && $employeeScreeningCompleted && $employeeNumTarget >= 5) {
            return 'Tier 4 Basic (Increased Savings)';
        } elseif($employeeHraCompleted && $employeeScreeningCompleted && $employeeNumTarget >= 3) {
            if($oneLevelUp || $twoLevelUp) {
                return 'Tier 4 Basic (Increased Savings)';
            } else {
                return 'Tier 3 Basic (Savings)';
            }

        } elseif($employeeHraCompleted && $employeeScreeningCompleted && $employeeNumTarget >= 1) {
            if($twoLevelUp) {
                return 'Tier 4 Basic (Increased Savings)';
            } elseif ($oneLevelUp) {
                return 'Tier 3 Basic (Savings)';
            } else {
                return 'Tier 2 Basic (Base Level)';
            }
        } else {
            return 'Tier 1 Basic (Premium Increase)';
        }

    }


    const ROLLING_START_DATE_ACTIVITY_DATE = '2012-08-15';
}


class Bison2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function __construct()
    {
        $this->setShowLegend(false);

        $this->tableHeaders['points_possible'] = 'Maximum Possible Points';
        $this->screeningLinkArea = '<br/><br/>
      Green Range = 10 pts <br/>
      Yellow Range = 5 pts<br/><br/>
      <a href="/content/1094#2aKBHM"> Click here for more info &amp; tips to improve.</a><br/><br/>
      <em>Points appear 5-10 days after screening.</em>
    ';
        
        $this->addStatusCallbackColumn('Requirement', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();
            
            return $view->getAttribute('requirement');
        });
        
        $this->addStatusCallbackColumn('Points Per Activity', function(ComplianceViewStatus $status) {
            $view = $status->getComplianceView();
            return $view->getAttribute('points_per_activity');
        });
        
        
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $this->setShowTotal(false);
        $this->setScreeningResultsLink(new Link('Have These Screening Results in the Ideal Zone:', '/content/1094#2aKBHM'));

        ?>

<?php
    }
    
    public function printReport(ComplianceProgramStatus $status) 
    {       
        $coreGroupStatus = $status->getComplianceViewGroupStatus('core');

        $completeHraStatus = $coreGroupStatus->getComplianceViewStatus('complete_hra');
        $completeScreeningStatus = $coreGroupStatus->getComplianceViewStatus('complete_screening');
        
        $wellnessGroupStatus = $status->getComplianceViewGroupStatus('wellness_programs');

        $optionalGroupStatus = $status->getComplianceViewGroupStatus('optional_cost');

        
        $noTobaccoUserStatus = $wellnessGroupStatus->getComplianceViewStatus('non_smoker_view');
        $bmiStatus = $wellnessGroupStatus->getComplianceViewStatus('bmi');
        $bloodPressureStatus = $wellnessGroupStatus->getComplianceViewStatus('blood_pressure');
        $ldlStatus = $wellnessGroupStatus->getComplianceViewStatus('ldl');
        $hdlStatus = $wellnessGroupStatus->getComplianceViewStatus('hdl');
        $triglyceridesStatus = $wellnessGroupStatus->getComplianceViewStatus('triglycerides');
        $glucoseStatus = $wellnessGroupStatus->getComplianceViewStatus('glucose');

        $onsiteOneStatus = $optionalGroupStatus->getComplianceViewStatus('onsite_one');
        $onsiteNPOneStatus = $optionalGroupStatus->getComplianceViewStatus('onsite_np_one');

        $onsiteTwoStatus = $optionalGroupStatus->getComplianceViewStatus('onsite_two');
        $onsiteNPTwoStatus = $optionalGroupStatus->getComplianceViewStatus('onsite_np_two');
        
        $numCompliant = 0;
        
        foreach($wellnessGroupStatus->getComplianceViewStatuses() as $viewStatus) {
            if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) $numCompliant++;
        }
        
        $user = $status->getUser();

        foreach($user->relationshipUsers as $relationshipUser) {
            if($relationshipUser->getRelationshipType() == Relationship::SPOUSE) {
                $spouseTier = $status->getComplianceProgram()->getTierStatus($relationshipUser);
            } elseif ($relationshipUser->getRelationshipType() == Relationship::OTHER_DEPENDENT) {
                if(!isset($dependentOneTier)) {
                    $dependentOneTier = $status->getComplianceProgram()->getTierStatus($relationshipUser);
                } elseif(!isset($dependentTwoTier)) {
                    $dependentTwoTier = $status->getComplianceProgram()->getTierStatus($relationshipUser);
                } else {
                    $dependentThreeTier = $status->getComplianceProgram()->getTierStatus($relationshipUser);
                }
            }
        }

        ?>
    <style type="text/css">
        .pageHeading {
            display:none;
        }

        #altPageHeading {
            font-weight:bold;
            margin-bottom:20px;
            text-align:center;
        }

        .phipTable .headerRow {
            background-color: #0033FF;
            font-weight:normal;
            color:#FFFFFF;
            font-size:10pt;
            height:36px;
            text-align: center;
        }

        #legend td {
            padding:8px !important;
        }

        .legendEntry {
            width:auto;
            float:right;
            display:inline-block;
            padding:0 8px;
        }

        .phipTable {
            width:100%;
            border-collapse:collapse;
            font-size:8pt;
        }

        .phipTable .links {
            text-align: center;
        }
        
        .phipTable .links a {
            display:inline-block;
            margin:0 3px;
        }
        
        .phipTable th, .phipTable td {
            border:1px solid #000000;
            padding:2px;
        }
        
        .light {
            width:25px;
        }
        
        .center {
            text-align:center;
        }
        
        .deadline, .result {
            width:100px;
            text-align: center;
        }

        .date-completed, .requirement, .status, .tier_hra, .tier_screening, .tier_num, .tier_premium {
            text-align: center;
        }
        
        #tier_table {
            margin:0 auto;
        }
        
        #tier_table td{
            padding-right: 20px;
            border-bottom:1px solid black;
            padding-top: 10px;
        }
        
        #tier_table span {
            color: red;
        }
        
        #bottom_statement {
            padding-top:20px;
        }
        
        #tier_total {
            font-weight: bold;
            text-align: center;
        }
    </style>

    <script type="text/javascript">
        // Set max points text for misc points earned
    </script>
    <!-- Text atop report card-->
    <div class="pageHeading">2015 Bison Cares for You Program</div>

    <p><strong>Hello <?php echo $status->getUser()->getFullName(); ?>,</strong></p>

    <p>Welcome to your summary page for the Bison Cares for You Program. Recently, you and/or your 
        covered dependents over age eighteen were eligible to participate in the Bison Gear & Engineering 
        Corporation Annual Health Risk Assessment. The premium contribution tier achieved by you and your 
        eligible dependents can be seen below in the Premium Contribution Tier Calculation section.
    </p>
    <p>
        Results of the program were calculated based on Bison’s Premium Contribution and Wellness Standards for 
        their 2015 Health Risk Assessment as shown below.
    </p>
    
    <div>
        <table id="tier_table">
            <tr>
                <td><span>Tier 1 Basic</span> <br />(Premium Increase)</td>
                <td>Base Premium for Basic Plan – Employee and/or covered dependents 18 and over did not participate in the<br />
                    Annual Health Risk Assessment.</td>
            </tr>
            <tr>
                <td><span>Tier 2 Basic</span> <br />(Base Level)</td>
                <td>Participation in the Annual Health Risk Assessment.<br />
                    All covered dependents 18 and over must participate.	</td>
            </tr>
            <tr>
                <td><span>Tier 3 Basic</span> <br />(Savings)</td>
                <td>Participation in the HRA and achieve target levels for at least 3 of the 7 biometric measurements.<br />
                    Each dependent 18 and over must achieve at least 3 target biometrics</td>
            </tr>
            <tr>
                <td><span>Tier 4 Basic</span> <br />(Increased Savings)</td>
                <td>Participation in the HRA and achieve target levels for at least 5 of the 7 biometric measurements.<br />
                    Each dependent 18 and over must achieve at least 5 target biometrics</td>
            </tr>        
        </table>
    <div><br />
    
    <p>Thank you for your participation this year. Bison would encourage you to continue to participate in future
        wellness initiatives and also visit the onsite clinic.
    </p>
    

<table class="phipTable">
    <tbody>                
        <tr class="headerRow headerRow-core">
            <th class="center">1. Core Actions Required By 09/12/2015</th>
            <th class="deadline">Deadline</th>
            <th class="date-completed">Date Completed</th>
            <th class="status">Status</th>
            <th class="links">Links</th>
        </tr>
        <tr class="view-complete_hra">
            <td>
                <a href="<?php echo $completeHraStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>A</strong>. <?php echo $completeHraStatus->getComplianceView()->getAttribute('requirement') ?></a>
            </td>
            <td class="deadline">
                <?php echo $completeHraStatus->getComplianceView()->getAttribute('deadline') ?>
            </td>        
            <td class="date-completed">
                <?php echo $completeHraStatus->getComment() ?>
            </td>                
            <td class="status">
                <img src="<?php echo $completeHraStatus->getLight(); ?>" class="light"/>
            </td>
            <td class="links">
                <?php foreach($completeHraStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        <tr class="view-complete_screening">
            <td>
                <a href="<?php echo $completeScreeningStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>B</strong>. <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('requirement') ?></a>
            </td>
            <td class="deadline">
                <?php echo $completeScreeningStatus->getComplianceView()->getAttribute('deadline') ?>
            </td>        
            <td class="date-completed">
                <?php echo $completeScreeningStatus->getComment() ?>
            </td>                     
            <td class="center">
                <img src="<?php echo $completeScreeningStatus->getLight(); ?>" class="light"/>
            </td>
            <td class="center">
                <?php foreach($completeScreeningStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr class="headerRow headerRow-wellness_programs">
            <td class="center">2. Biometrics Monitored</td>
            <td class="result">Result</td>
            <td class="requirement">Required Ranges</td>
            <td class="status">Status</td>
            <td class="links">Links</td>
        </tr>
        <tr>
            <td>
                <a href="<?php echo $noTobaccoUserStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>1</strong>. <?php echo $noTobaccoUserStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="result"><?php echo $noTobaccoUserStatus->getComment() ?></td>
            <td class="requirement"><?php echo $noTobaccoUserStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $noTobaccoUserStatus->getLight() ?>" class="light" /></td>
            <td class="links">
                <?php foreach($noTobaccoUserStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr>
            <td>
                <a href="<?php echo $bmiStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>2</strong>. <?php echo $bmiStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="result"><?php echo $bmiStatus->getComment() ?></td>
            <td class="requirement"><?php echo $bmiStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $bmiStatus->getLight() ?>" class="light" /></td>
            <td class="links">
                <?php foreach($bmiStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr>
            <td>
                <a href="<?php echo $bloodPressureStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>3</strong>. <?php echo $bloodPressureStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="result"><?php echo $bloodPressureStatus->getComment() ?></td>
            <td class="requirement">
                <?php echo $bloodPressureStatus->getComplianceView()->getAttribute('requirement_systolic') ?><br />
                <?php echo $bloodPressureStatus->getComplianceView()->getAttribute('requirement_diastolic') ?>
            
            </td>
            <td class="status"><img src="<?php echo $bloodPressureStatus->getLight() ?>" class="light" /></td>
            <td class="links">
                <?php foreach($bloodPressureStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr>
            <td>
                <a href="<?php echo $ldlStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>4</strong>. <?php echo $ldlStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="result"><?php echo $ldlStatus->getComment() ?></td>
            <td class="requirement"><?php echo $ldlStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $ldlStatus->getLight() ?>" class="light" /></td>
            <td class="links" rowspan="3">
                <?php foreach($ldlStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>        
        
        <tr>
            <td>
                <a href="<?php echo $hdlStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>5</strong>. <?php echo $hdlStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="result"><?php echo $hdlStatus->getComment() ?></td>
            <td class="requirement"><?php echo $hdlStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $hdlStatus->getLight() ?>" class="light" /></td>
        </tr>       
        
        <tr>
            <td>
                <a href="<?php echo $triglyceridesStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>6</strong>. <?php echo $triglyceridesStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="result"><?php echo $triglyceridesStatus->getComment() ?></td>
            <td class="requirement"><?php echo $triglyceridesStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $triglyceridesStatus->getLight() ?>" class="light" /></td>
        </tr> 
        
        <tr>
            <td>
                <a href="<?php echo $glucoseStatus->getComplianceView()->getAttribute('report_name_link')?>">
                <strong>7</strong>. <?php echo $glucoseStatus->getComplianceView()->getReportName() ?></a>
            </td>
            <td class="result"><?php echo $glucoseStatus->getComment() ?></td>
            <td class="requirement"><?php echo $glucoseStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $glucoseStatus->getLight() ?>" class="light" /></td>
            <td class="links">
                <?php foreach($glucoseStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>
        
        <tr>
            <td colspan="3" style="height:36px; text-align: center; font-size:12pt; background-color: #0033FF; color:white">Number of Target Levels Achieved</td>
            <td class="status" style="font-size:11pt; font-weight:bold;"><?php echo $numCompliant ?></td>
            <td></td>
        </tr>

        <tr>
            <td colspan="3" style="height:36px; text-align: center; font-size:12pt; background-color: #0033FF; color:white">Initial Individual Tier Level Achieved</td>
            <td class="status" style="font-size:11pt; font-weight:bold;"><?php echo $status->getComplianceProgram()->getInitialIndividualCalculatedTier($status->getUser()) ?></td>
            <td></td>
        </tr>

        <tr class="headerRow headerRow-wellness_programs">
            <td class="center">3. Optional Cost Savings</td>
            <td class="result">Count</td>
            <td class="requirement">Requirements</td>
            <td class="status">Status</td>
            <td class="links">Links</td>
        </tr>

        <tr>
            <td rowspan="2">
                <a href="<?php echo $onsiteOneStatus->getComplianceView()->getAttribute('report_name_link')?>">
                    <strong>1</strong>. Move Up 1 Tier Level</a>
            </td>
            <td class="result"><?php echo $onsiteOneStatus->getPoints() ?></td>
            <td class="requirement"><?php echo $onsiteOneStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $onsiteOneStatus->getLight() ?>" class="light" /></td>
            <td class="links">
                <?php foreach($onsiteOneStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr>
            <td class="result"><?php echo $onsiteNPOneStatus->getPoints() ?></td>
            <td class="requirement"><?php echo $onsiteNPOneStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $onsiteNPOneStatus->getLight() ?>" class="light" /></td>
            <td class="links">
                <?php foreach($onsiteNPOneStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr>
            <td rowspan="2">
                <a href="<?php echo $onsiteTwoStatus->getComplianceView()->getAttribute('report_name_link')?>">
                    <strong>2</strong>. Move Up 2 Tier Levels</a>
            </td>
            <td class="result"><?php echo $onsiteTwoStatus->getPoints() ?></td>
            <td class="requirement"><?php echo $onsiteTwoStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $onsiteTwoStatus->getLight() ?>" class="light" /></td>
            <td class="links">
                <?php foreach($onsiteTwoStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr>
            <td class="result"><?php echo $onsiteNPTwoStatus->getPoints() ?></td>
            <td class="requirement"><?php echo $onsiteNPTwoStatus->getComplianceView()->getAttribute('requirement') ?></td>
            <td class="status"><img src="<?php echo $onsiteNPTwoStatus->getLight() ?>" class="light" /></td>
            <td class="links">
                <?php foreach($onsiteNPTwoStatus->getComplianceView()->getLinks() as $link) {
                    echo $link->getHTML()."\n";
                }?>
            </td>
        </tr>

        <tr>
            <td colspan="3" style="height:36px; text-align: center; font-size:12pt; background-color: #0033FF; color:white">Final Individual Tier Level Achieved</td>
            <td class="status" style="font-size:11pt; font-weight:bold;"><?php echo $status->getComplianceProgram()->getFinalIndividualCalculatedTier($status->getUser()) ?></td>
            <td></td>
        </tr>

        <tr style="height:38px">
            <td colspan="5"></td>
        </tr>
        
        <tr class="headerRow">
            <td colspan="5">Premium Contribution Tier Calculation</td>
        </tr>
        
        <tr class="headerRow">
            <th class="tier_individual">Individual</th>
            <th class="tier_hra">HRA</th>
            <th class="tier_screening">Screening</th>
            <th class="tier_num">Number of Target Levels Achieved</th>
            <th class="tier_premium">Premium Contribution Tier Achieved</th>
        </tr>
        
        <tr>
            <td class="tier_individual">Employee</td>
            <td class="tier_hra"><?php echo $completeHraStatus->getComment() ?></th>
            <td class="tier_screening"><?php echo $completeScreeningStatus->getComment() ?></td>
            <td class="tier_num"><?php echo $numCompliant ?></td>
            <td class="tier_premium"></td>
        </tr>
    <?php 
        if(isset($spouseTier)) {
?>
        <tr>
            <td class="tier_individual">Spouse</td>
            <td class="tier_hra"><?php echo $spouseTier['hra_date'] ?></td>
            <td class="tier_screening"><?php echo $spouseTier['screening_date'] ?></td>
            <td class="tier_num"><?php echo $spouseTier['number_of_levels'] ?></td>
            <td class="tier_premium"></td>
        </tr>
    <?php
        }

        if(isset($dependentOneTier)) {
                ?>
        <tr>
            <td class="tier_individual">Dependent</td>
            <td class="tier_hra"><?php echo $dependentOneTier['hra_date'] ?></td>
            <td class="tier_screening"><?php echo $dependentOneTier['screening_date'] ?></td>
            <td class="tier_num"><?php echo $dependentOneTier['number_of_levels'] ?></td>
            <td class="tier_premium"></td>
        </tr>
        
    <?php
        }

        if(isset($dependentTwoTier)) {
                ?>
        <tr>
            <td class="tier_individual">Dependent</td>
            <td class="tier_hra"><?php echo $dependentTwoTier['hra_date'] ?></td>
            <td class="tier_screening"><?php echo $dependentTwoTier['screening_date'] ?></td>
            <td class="tier_num"><?php echo $dependentTwoTier['number_of_levels'] ?></td>
            <td class="tier_premium"></td>
        </tr>
        
    <?php
        }
        
        if(isset($dependentThreeTier)) {
                ?>
        <tr>
            <td class="tier_individual">Dependent</td>
            <td class="tier_hra"><?php echo $dependentThreeTier['hra_date'] ?></td>
            <td class="tier_screening"><?php echo $dependentThreeTier['screening_date'] ?></td>
            <td class="tier_num"><?php echo $dependentThreeTier['number_of_levels'] ?></td>
            <td class="tier_premium"></td>
        </tr>
        
    <?php
        }
    ?>
        
        <tr>
            <td colspan="4" style="height:26px; text-align: center; font-size:11pt; background-color: #0033FF; color:white">Premium Contribution Tier Achieved</td>
            <td id="tier_total"><?php echo $status->getComplianceProgram()->getCalculatedTier($status->getUser()) ?></td>
        </tr>
        
    </tbody>
 </table>
    <?php
    }


    public $showUserNameInLegend = true;
}
