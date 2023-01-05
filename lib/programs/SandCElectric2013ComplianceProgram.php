<?php

class SandCElectric2013ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $phase1 = new ComplianceViewGroup('Phases 1 & 2 – Know & Understand Your Numbers');

        $screening = new CompleteScreeningComplianceView($startDate, '2013-12-31');
        $screening->setReportName('Complete Biometric Screening');
        $screening->emptyLinks();
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/onlineregistrationrequest/landingpage/5'));
        $screening->addLink(new Link('Results', '/content/989'));
        //$screening->setAttribute('report_name_link', '/content/1094#1aBioScreen');

        $phase1->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, '2013-12-31');
        $hra->setReportName('Complete Health Risk Assessment');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HRA', '/content/1006'));
        $hra->addLink(new Link('Results', '/content/989'));
        //$hra->setAttribute('report_name_link', '/content/1094#1bHRA');
        $phase1->addComplianceView($hra);

        $coaching = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coaching->setName('coaching');
        $coaching->setReportName('Complete 1 Health Coaching Call');
        $coaching->emptyLinks();
        $coaching->setAttribute('deadline', $this->getEndDate('12/31/2013'));
        //$screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/onlineregistrationrequest/landingpage/5'));
        //$coaching->setAttribute('report_name_link', '/content/1094#1cCoach');
        $phase1->addComplianceView($coaching);

        $this->addComplianceViewGroup($phase1);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new SandCElectric2013ComplianceProgramReportPrinter();
    }
    
    public function getAdminProgramReportPrinter() {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        
        $printer->setShowUserFields(null, null, true, false, true);
        
        $printer->addCallbackField('employee_id', function (User $user) {
            return $user->employeeid;
        });
        
        return $printer;
    }    
}

class SandCElectric2013ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $this->addStatusCallbackColumn('Deadline', function (ComplianceViewStatus $status) {
            $view = $status->getComplianceView();

            $default = $view instanceof DateBasedComplianceView ?
                $view->getEndDate('m/d/Y') : '';

            return $view->getAttribute('deadline', $default);
        });

        $startDate = $status->getComplianceProgram()->getEndDate('F d, Y');

        $this->setShowLegend(true);
        $this->setShowTotal(true);
        $this->pageHeading = '2013 Wellness Initiative Program';
        $this->tableHeaders['completed'] = 'Date Done';
        $this->tableHeaders['total_status'] = 'Status of 1ABC being done on or before '.$startDate;
        $this->tableHeaders['total_link'] = 'Needed for $50 Gift Card';
        $this->tableHeaders['links'] = 'Action Links';

        parent::printReport($status);
        ?>
        <p><small><!--* Phase 1 requirements must also be met in addition to points required.</small>--></p>
        <?php
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
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
                font-size:16px;
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
                width:190px;
            }
        </style>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

        <?php
    }
}