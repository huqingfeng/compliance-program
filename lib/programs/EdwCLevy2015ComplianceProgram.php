<?php

class EdwCLevy2015ComplianceProgram extends ComplianceProgram
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

        $printer->addCallbackField('date_of_birth', function(User $user) {
            return $user->date_of_birth;
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

        $coachingOverall = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingOverall->setName('coaching_overall');
        $coachingOverall->setReportName('Complete 4 Coaching Sessions (if applicable)');
        $required->addComplianceView($coachingOverall);

        $coachingSession1 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession1->setName('coaching_session1');
        $coachingSession1->setReportName('Session 1');
        $required->addComplianceView($coachingSession1);

        $coachingSession2 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession2->setName('coaching_session2');
        $coachingSession2->setReportName('Session 2');
        $required->addComplianceView($coachingSession2);

        $coachingSession3 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession3->setName('coaching_session3');
        $coachingSession3->setReportName('Session 3');
        $required->addComplianceView($coachingSession3);

        $coachingSession4 = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coachingSession4->setName('coaching_session4');
        $coachingSession4->setReportName('Session 4');
        $required->addComplianceView($coachingSession4);

        $this->addComplianceViewGroup($required);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new EdwCLevy2015ComplianceProgramReportPrinter();
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        $requiredGroupStatus = $status->getComplianceViewGroupStatus('Required');
        $sessionOverallStatus = $requiredGroupStatus->getComplianceViewStatus('coaching_overall');
        $session1Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session1');
        $session2Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session2');
        $session3Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session3');
        $session4Status = $requiredGroupStatus->getComplianceViewStatus('coaching_session4');

        $coachingStatus = array();

        $coachingStartDate = '2015-01-01';
        $coachingEndDate = '2015-12-31';

        $session = CoachingSessionTable::getInstance()->findMostRecentSessionForUser($status->getUser());
        if(is_object($session)) {
            $reports = CoachingReportTable::getInstance()->findActiveReports($session);

            foreach($reports as $report) {
                if(is_object($report)) {
                    if($report->getDate('Y-m-d') < $coachingStartDate || $report->getDate('Y-m-d') > $coachingEndDate) continue;

                    $reportEdit = CoachingReportEditTable::getInstance()->findMostRecentEdit($report);

                    if(is_object($reportEdit)) {
                        $recordedDocument = $reportEdit->getRecordedDocument();
                        $recordedFields = $recordedDocument->getRecordedDocumentFields();

                        $coachingData = array();
                        $isContact = true;
                        foreach($recordedFields as $recordedField) {
                            $name = $recordedField->getFieldName();
                            $value = $recordedField->getFieldValue();
                            if(empty($value) || !empty($defaults[$name])) continue;

                            if($name == 'attempt' && !empty($name)) $isContact = false;
                            $coachingData[$name] = $value;
                        }

                        if($isContact) {
                            if(!isset($coachingStatus['session1'])) {
                                $coachingStatus['session1'] = $coachingData;
                            } elseif(!isset($coachingStatus['session2'])) {
                                $coachingStatus['session2'] = $coachingData;
                            } elseif(!isset($coachingStatus['session3'])) {
                                $coachingStatus['session3'] = $coachingData;
                            } elseif(!isset($coachingStatus['session4'])) {
                                $coachingStatus['session4'] = $coachingData;
                            }
                        }
                    }
                }
            }
        }

        if(isset($coachingStatus['session1'])
        && isset($coachingStatus['session1']['contact'])
        && isset($coachingStatus['session1']['total_minutes'])
        && $coachingStatus['session1']['total_minutes'] > 0) {
            $session1Status->setStatus(ComplianceStatus::COMPLIANT);
            $session1Status->setComment($coachingStatus['session1']['date']);
        }

        if(isset($coachingStatus['session2'])
            && isset($coachingStatus['session2']['contact'])
            && isset($coachingStatus['session2']['total_minutes'])
            && $coachingStatus['session2']['total_minutes'] > 0) {
            $session2Status->setStatus(ComplianceStatus::COMPLIANT);
            $session2Status->setComment($coachingStatus['session2']['date']);
        }

        if(isset($coachingStatus['session3'])
            && isset($coachingStatus['session3']['contact'])
            && isset($coachingStatus['session3']['total_minutes'])
            && $coachingStatus['session3']['total_minutes'] > 0) {
            $session3Status->setStatus(ComplianceStatus::COMPLIANT);
            $session3Status->setComment($coachingStatus['session3']['date']);
        }

        if(isset($coachingStatus['session4'])
            && isset($coachingStatus['session4']['contact'])
            && isset($coachingStatus['session4']['total_minutes'])
            && $coachingStatus['session4']['total_minutes'] > 0) {
            $session4Status->setStatus(ComplianceStatus::COMPLIANT);
            $session4Status->setComment($coachingStatus['session4']['date']);
        }


        if($session1Status->getStatus() == ComplianceStatus::COMPLIANT
            && $session2Status->getStatus() == ComplianceStatus::COMPLIANT
            && $session3Status->getStatus() == ComplianceStatus::COMPLIANT
            && $session4Status->getStatus() == ComplianceStatus::COMPLIANT) {
            $sessionOverallStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

    }
}

class EdwCLevy2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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

    <script type="text/javascript" >
        $(function() {
           $('.view-coaching_session1').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 1</span>');
            $('.view-coaching_session2').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 2</span>');
            $('.view-coaching_session3').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 3</span>');
            $('.view-coaching_session4').children(':eq(0)').html('<span style="padding-left: 20px;">• Session 4</span>');
        });
    </script>

    <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>

    <p> <a href="<?php echo url_for('/compliance_programs/?id=249') ?>">2013 Incentive Status</a></p>
    <p> <a href="<?php echo url_for('/compliance_programs/?id=320') ?>">2014 Incentive Status</a></p>


        <p>Welcome to The Edw. C. Levy Co. Wellness Website! This site was developed not only to track
        your wellness participation requirements, but also to be used as a great resource for health-related
        topics and questions. We encourage you to explore the site while also fulfilling your
        requirements. Employees and Spouses must participate in the following steps if you are enrolled
        in the Levy Medical Plan or beginning January 1, 2016 you and family will be enrolled in the Standard Plan
        (this plan has higher deductibles, higher co-pays, higher out-of-pocket costs and higher prescription co-pays).
    </p>

    <p><strong>Step 1</strong>- Complete your Health Risk Appraisal (HRA) at least one week prior (but not more than 3 weeks prior) to your
        health screening.</p>

    <p><strong>Step 2</strong>- Complete your on-site or on-demand health screening and
        consultation. Screenings are scheduled April through June, see your Wellness
        Coordinator for your location's date(s) and details.</p>

    <p><strong>Step 3</strong>- Based on your risk factors, you may be enrolled in the Personal
        Coaching Sessions. You will be notified at the time of your on-site
        screening, or after your on-demand screening, if you are enrolled for
        coaching. If enrolled you MUST participate in the 4 quarterly coaching
        sessions to be compliant.</p>

    <p>The current requirements and your current status for each are summarized below.</p>


    <?php
    }
}