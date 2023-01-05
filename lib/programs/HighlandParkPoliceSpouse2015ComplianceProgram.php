<?php

class HighlandParkPoliceSpouse2015ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', 'Required Actions to Participate &amp; Earn Incentive');

        $mapper = new ComplianceStatusPointMapper(1, 0, 1, 0);

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setReportName('Biometric Screening - Blood Test, Blood Pressure...');
        $screening->emptyLinks();
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/onlineregistrationrequest/landingpage/5'));
        $screening->addLink(new Link('Results', '/content/989'));
        $screening->setAttribute('report_name_link', '/content/1094_hppolsp#aBioScreen');
        $screening->setComplianceStatusPointMapper($mapper);

        $requiredGroup->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Health Risk Assessment');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HRA', '/content/1006'));
        $hra->addLink(new Link('Results', '/content/989'));
        $hra->setAttribute('report_name_link', '/content/1094_hppolsp#bHRA');
        $hra->setComplianceStatusPointMapper($mapper);
        $requiredGroup->addComplianceView($hra);

        $disease = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $disease->setName('disease');
        $disease->setReportName('Disease Management');
        $disease->addLink(new Link('Learn More', '/content/1094_hppolsp#cDiseaseManCoach'));
        $disease->setAttribute('report_name_link', '/content/1094_hppolsp#cDiseaseManCoach');
        $disease->setMaximumNumberOfPoints(75);
        $disease->setComplianceStatusPointMapper($mapper);
        $requiredGroup->addComplianceView($disease);

        $lifestyle = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $lifestyle->setName('lifestyle');
        $lifestyle->setReportName('Lifestyle Coaching');
        $lifestyle->addLink(new Link('Learn More', '/content/1094_hppolsp#dLifestyleCoach'));
        $lifestyle->setAttribute('report_name_link', '/content/1094_hppolsp#dLifestyleCoach');
        $lifestyle->setMaximumNumberOfPoints(40);
        $lifestyle->setComplianceStatusPointMapper($mapper);
        $requiredGroup->addComplianceView($lifestyle);

        $dental = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $dental->setName('dental');
        $dental->setReportName('Dental Exam');
        $dental->addLink(new Link('Learn More / Get Form', '/content/1094_hppolsp#fDental'));
        $dental->setAttribute('report_name_link', '/content/1094_hppolsp#fDental');
        $dental->setMaximumNumberOfPoints(10);
        $dental->setComplianceStatusPointMapper($mapper);
        $requiredGroup->addComplianceView($dental);

        $tobaccoFree = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $tobaccoFree->setName('tobacco');
        $tobaccoFree->setReportName('Tobacco Free Pledge');
        $tobaccoFree->addLink(new Link('Learn More / Get Form', '/content/1094_hppolsp#hTobaccoFree'));
        $tobaccoFree->setAttribute('report_name_link', '/content/1094_hppolsp#hTobaccoFree');
        $tobaccoFree->setMaximumNumberOfPoints(10);
        $tobaccoFree->setComplianceStatusPointMapper($mapper);
        $requiredGroup->addComplianceView($tobaccoFree);

        $seatbelt = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $seatbelt->setName('seatbelt');
        $seatbelt->setReportName('Seat Belt Pledge');
        $seatbelt->addLink(new Link('Learn More / Get Form', '/content/1094_hppolsp#iSeatBelt'));
        $seatbelt->setAttribute('report_name_link', '/content/1094_hppolsp#iSeatBelt');
        $seatbelt->setMaximumNumberOfPoints(5);
        $seatbelt->setComplianceStatusPointMapper($mapper);
        $requiredGroup->addComplianceView($seatbelt);

        $this->addComplianceViewGroup($requiredGroup);
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {

        $user = $status->getUser();

        $employeeUser = false;

        $relationshipUsers = array();

        if($user->relationship_user_id && !$user->relationshipUser->expired()) {
            $relationshipUsers[] = $user->relationshipUser;
        }

        foreach($user->relationshipUsers as $relatedUser) {
            if(!$relatedUser->expired()) {
                $relationshipUsers[] = $relatedUser;
            }
        }

        foreach($relationshipUsers as $relatedUser) {
            if(in_array($relatedUser->relationship_type, $this->relationshipTypes)) {
                $employeeUser = $relatedUser;

                break;
            }
        }

        if($employeeUser) {
            $employeeRecord = ComplianceProgramRecordTable::getInstance()->find(self::EMPLOYEE_RECORD_ID);

            $employeeProgram = $employeeRecord->getComplianceProgram();

            $employeeProgram = $employeeProgram->cloneForEvaluation($employeeProgram->getStartDate(), $employeeProgram->getEndDate());

            $employeeProgram->setActiveUser($employeeUser);

            $employeeStatus = $employeeProgram->getStatus();

            $employeeDentalStatus = $employeeStatus->getComplianceViewStatus('dental');

            if($employeeDentalStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->getComplianceViewStatus('dental')->setStatus(ComplianceStatus::COMPLIANT);
            }

            $employeeTobaccoStatus = $employeeStatus->getComplianceViewStatus('tobacco');

            if($employeeTobaccoStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->getComplianceViewStatus('tobacco')->setStatus(ComplianceStatus::COMPLIANT);
            }

            $employeeSeatbeltStatus = $employeeStatus->getComplianceViewStatus('seatbelt');

            if($employeeSeatbeltStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                $status->getComplianceViewStatus('seatbelt')->setStatus(ComplianceStatus::COMPLIANT);
            }
        }

        parent::evaluateAndStoreOverallStatus($status);
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->addCallbackField('employee_id', function (User $user) {
            return $user->employeeid;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new HighlandParkPoliceSpouse2015ComplianceProgramReportPrinter();
    }

    const EMPLOYEE_RECORD_ID = 409;

    private $relationshipTypes = array(Relationship::SPOUSE, Relationship::EMPLOYEE);
}

class HighlandParkPoliceSpouse2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $this->setShowLegend(true);
        $this->pageHeading = '2015 WIN (Wellness Initiative Program)';
        $this->tableHeaders['completed'] = 'Date Done';
        $this->setShowTotal(false);

        parent::printReport($status);

        ?>
        <!--<div style="font-size:0.85em;text-align:center">
            * Required actions must also be done in addition to points required and required
            actions by spouse (if applicable).<br/>
            ** You receive the applicable credit: If this requirement is recommended and you
            complete it; or If it does not apply to you.
        </div>-->
    <?php
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $endDate = $status->getComplianceProgram()->getEndDate('m/d/Y');

        ?>
        <style type="text/css">
            #overviewCriteria {
                width:100%;
                border-collapse:collapse;
            }

            #legendText {
                background-color:#90C4DE;
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

            .phipTable .headerRow {
                background-color:#90C4DE;
            }

            .phipTable {
                font-size:12px;
                margin-bottom:10px;
            }

            .phipTable th, .phipTable td {
                padding:1px 2px;
                border:1px solid #a0a0a0;
            }

            .phipTable .points {
                width:80px;
            }

            .phipTable .links {
                width:250px;
            }
        </style>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>
        <p>The matrix below shows the status of your key actions and points that count toward the WIN program.</p>
        <p>Click on any link to learn more and get these things done for your wellbeing and other rewards!</p>

    <?php
    }


    protected function printCustomRows($status)
    {
        ?>
        <tr class="headerRow headerRow-required">
            <th>2. Total Required</th>
            <td>Current Total</td>
            <td>Status</td>
            <td>Minimum Needed for Incentive By 10/31/2015</td>
        </tr>
        <tr>
            <td>Actions completed as of: <?php echo date('m/d/Y') ?> =</td>
            <td style="text-align:center">
                <?php echo $status->getPoints() ?>
            </td>
            <td style="text-align:center"><img class="light" src="<?php echo $status->getLight() ?>" /></td>
            <td style="text-align:center">

            </td>
        </tr>
    <?php
    }
}