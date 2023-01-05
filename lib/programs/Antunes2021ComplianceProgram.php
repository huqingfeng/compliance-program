<?php
define("ANTUNES_ACTIVITY_RECORD",  "antunes_activities_2021");

class Antunes2021ComplianceProgram extends ComplianceProgram
{
    public function loadEvaluators()
    {

    }

    public function getActivity(User $user) {
        $record = UserDataRecord::getNewestRecord($user, ANTUNES_ACTIVITY_RECORD, true);

        return $record;
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $dateFormat = "m/d/Y";
        $activityRecord = $this->getActivity($user);

        $screening = $status->getComplianceViewStatus("complete_screening");
        $screeningDate = $screening->getComment();
        $screening = $screening->getStatus();

        $quarter1 = $status->getComplianceViewStatus("quarter1");

        $quarter1View = $quarter1->getComplianceView();
        $quarter1Activity = $activityRecord->getDataFieldValue("quarter1Activity");
        $quarter1Date = $activityRecord->getDataFieldValue("quarter1Date");
        $quarter1Comment = $activityRecord->getDataFieldValue("quarter1Comment");

        if (!empty($quarter1Activity)) {
            $quarter1View->setReportName("Quarter 1 - " . $quarter1Activity);
            $quarter1View->setAttribute("comment", $quarter1Comment);
            $quarter1->setComment(date($dateFormat,strtotime($quarter1Date)));
            $quarter1->setAttribute('activity', $quarter1Activity);
            $quarter1->setStatus(ComplianceStatus::COMPLIANT);
        }

        $quarter2 = $status->getComplianceViewStatus("quarter2");
        $quarter2View = $quarter2->getComplianceView();
        $quarter2Activity = $activityRecord->getDataFieldValue("quarter2Activity");
        $quarter2Date = $activityRecord->getDataFieldValue("quarter2Date");
        $quarter2Comment = $activityRecord->getDataFieldValue("quarter2Comment");

        if (!empty($quarter2Activity)) {
            $quarter2View->setReportName("Quarter 2 - " . $quarter2Activity);
            $quarter2View->setAttribute("comment", $quarter2Comment);
            $quarter2->setComment(date($dateFormat,strtotime($quarter2Date)));
            $quarter2->setAttribute('activity', $quarter2Activity);
            $quarter2->setStatus(ComplianceStatus::COMPLIANT);
        }


        $quarter3 = $status->getComplianceViewStatus("quarter3");
        $quarter3View = $quarter3->getComplianceView();
        $quarter3Activity = $activityRecord->getDataFieldValue("quarter3Activity");
        $quarter3Date = $activityRecord->getDataFieldValue("quarter3Date");
        $quarter3Comment = $activityRecord->getDataFieldValue("quarter3Comment");

        if (!empty($quarter3Activity)) {
            $quarter3View->setReportName("Quarter 3 - " . $quarter3Activity);
            $quarter3View->setAttribute("comment", $quarter3Comment);
            $quarter3->setComment(date($dateFormat,strtotime($quarter3Date)));
            $quarter3->setAttribute('activity', $quarter3Activity);
            $quarter3->setStatus(ComplianceStatus::COMPLIANT);
        }


        $quarter4 = $status->getComplianceViewStatus("quarter4");

        $quarter4View = $quarter4->getComplianceView();
        $quarter4Activity = $activityRecord->getDataFieldValue("quarter4Activity");
        $quarter4Date = $activityRecord->getDataFieldValue("quarter4Date");
        $quarter4Comment = $activityRecord->getDataFieldValue("quarter4Comment");

        if (!empty($quarter4Activity)) {
            $quarter4View->setReportName("Quarter 4 - " . $quarter4Activity);
            $quarter4View->setAttribute("comment", $quarter4Comment);
            $quarter4->setComment(date($dateFormat,strtotime($quarter4Date)));
            $quarter4->setAttribute('activity', $quarter4Activity);
            $quarter4->setStatus(ComplianceStatus::COMPLIANT);
        }

        $overall = $status->getComplianceViewStatus("overview");

        $quarter1Status = $quarter1->getStatus();
        $quarter2Status = $quarter2->getStatus();
        $quarter3Status = $quarter3->getStatus();
        $quarter4Status = $quarter4->getStatus();

        $mostRecent = null;
        $dates = [strtotime($screeningDate), strtotime($quarter1Date), strtotime($quarter2Date), strtotime($quarter3Date), strtotime($quarter4Date)];
        foreach($dates as $date){
            $curDate = $date;
            if ($curDate > $mostRecent) {
                $mostRecent = $curDate;
            }
        }

        if ($screening==ComplianceStatus::COMPLIANT &&
            $quarter1Status==ComplianceStatus::COMPLIANT &&
            $quarter2Status==ComplianceStatus::COMPLIANT &&
            $quarter3Status==ComplianceStatus::COMPLIANT &&
            $quarter4Status==ComplianceStatus::COMPLIANT) {

            $overall->setStatus(ComplianceStatus::COMPLIANT);
            $overall->setComment(date($dateFormat,$mostRecent));
        }
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $screening = new ComplianceViewGroup('Screening');

        $screeningView = new CompleteScreeningComplianceView($startDate, $endDate);
        $screeningView->setReportName('Biometric Screening');
        $screeningView->emptyLinks();
        $screeningView->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/antunes'));
        $screeningView->addLink(new Link('Results', '/compliance/antunes2018/my-health/content/my-health'));

        $screening->addComplianceView($screeningView);

        $this->addComplianceViewGroup($screening);

        $wellnessEvents = new ComplianceViewGroup('Wellness Events');

        $quarter1 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $quarter1->setName('quarter1');
        $quarter1->setReportName('Quarter 1');
        $quarter1->setAttribute("original_report_name", 'Quarter 1');
        $quarter1->addLink(new Link('Log Activity', '/content/antunes_activities?year=2021'));
        $wellnessEvents->addComplianceView($quarter1);

        $quarter2 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $quarter2->setName('quarter2');
        $quarter2->setReportName('Quarter 2');
        $quarter2->setAttribute("original_report_name", 'Quarter 2');
        $quarter2->addLink(new Link('Log Activity', '/content/antunes_activities?year=2021'));
        $wellnessEvents->addComplianceView($quarter2);

        $quarter3 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $quarter3->setName('quarter3');
        $quarter3->setReportName('Quarter 3');
        $quarter3->setAttribute("original_report_name", 'Quarter 3');
        $quarter3->addLink(new Link('Log Activity', '/content/antunes_activities?year=2021'));
        $wellnessEvents->addComplianceView($quarter3);

        $quarter4 = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $quarter4->setName('quarter4');
        $quarter4->setReportName('Quarter 4');
        $quarter4->setAttribute("original_report_name", 'Quarter 4');
        $quarter4->addLink(new Link('Log Activity', '/content/antunes_activities?year=2021'));
        $wellnessEvents->addComplianceView($quarter4);

        $this->addComplianceViewGroup($wellnessEvents);

        $summary = new ComplianceViewGroup('Overview');

        $overview = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $overview->setName('overview');
        $overview->setReportName('Biometric Screening + Quarterly Wellness Events');
        $summary->addComplianceView($overview);

        $this->addComplianceViewGroup($summary);
    }

    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowCompliant(false, false, false);
        $printer->setShowStatus(false,false,false);
        $printer->setShowPoints(false,false,false);
        $printer->setShowComment(false,false,false);

        $printer->addCallbackField('employee_id', function (User $user) {
            return $user->employeeid;
        });

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $data = array();

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    if($groupStatus->getComplianceViewGroup()->getName() == 'Wellness Events') {
                        $viewName = $viewStatus->getComplianceView()->getAttribute('original_report_name');
                        $data[sprintf('%s - Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                        $data[sprintf('%s - Activity', $viewName)] = $viewStatus->getAttribute('activity');
                        $data[sprintf('%s - Date', $viewName)] = $viewStatus->getComment();
                    } else {
                        $viewName = $viewStatus->getComplianceView()->getReportName();
                        $data[sprintf('%s - Compliant', $viewName)] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                        $data[sprintf('%s - Comment', $viewName)] = $viewStatus->getComment();
                    }
                }
            }

            $data['Total Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Antunes2021ComplianceProgramReportPrinter();
    }
}

class Antunes2021ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $this->setShowLegend(true);
        $this->pageHeading = '2021 Wellness Program';
        $this->tableHeaders['completed'] = 'Date Done';
        $this->setShowTotal(false);

        parent::printReport($status);

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
                background: #0069a3;
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
                background-color:#0069a3;
            }

            .phipTable {
                font-size:12px;
                margin-bottom:10px;
            }

            .phipTable th, .phipTable td {
                padding:1px 2px;
                border:1px solid #005583;
            }

            .phipTable .points {
                width:80px;
            }

            .phipTable .links {
                width:250px;
            }
        </style>

        <script type="text/javascript">
            $(function() {
                <?php foreach($status->getComplianceViewGroupStatuses() as $groupStatus) : ?>
                <?php foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) : ?>
                <?php $view = $viewStatus->getComplianceView() ?>
                <?php if($popoverContent = $view->getAttribute('link_popover')) : ?>
                $('a[href="#popover_<?php echo $view->getName() ?>"]').popover({
                    title: <?php echo json_encode(preg_replace('|<br[ ]*/?>.*|', '', $view->getReportName())) ?>,
                    content: <?php echo json_encode($popoverContent) ?>,
                    trigger: 'hover',
                    html: true
                });
                <?php endif ?>
                <?php endforeach ?>
                <?php endforeach ?>

            });
        </script>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>
        <p>The table below shows the status of your key actions in the wellness program.</p>
        <p>Click on any link to learn more and get these things done for your wellbeing and other rewards!</p>

        <?php
    }
}