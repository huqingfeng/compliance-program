<?php


class HighlandParkPoliceEmployee2015ComplianceProgram extends ComplianceProgram
{
    const SPOUSE_RECORD_ID = 408;

    public function loadEvaluators()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $spouseRecord = ComplianceProgramRecordTable::getInstance()->find(self::SPOUSE_RECORD_ID);

        $total = new ComplianceViewGroup('totals', 'Totals Required for Incentive');
        //$total->setPointsRequiredForCompliance(50);

        $mine = new ProgramStatusEvaluatorComplianceView($this->cloneForEvaluation($startDate, $endDate));
        $mine->setName('mine');
        $mine->setReportName('My Total Points as of: '.date('m/d/Y').' =');
        $mine->setMaximumNumberOfPoints(355);
        $total->addComplianceView($mine, true);

        if($spouseRecord) {
            $spouseProgram = $spouseRecord->getComplianceProgram();

            $spouse = new HighlandParkPoliceEmployeeEvaluateSpouseComplianceView($spouseProgram);
            $spouse->setName('spouse');
            $spouse->setReportName('Actions completed by spouse as of: '.date('m/d/Y').' = ');
            $total->addComplianceView($spouse, true);
        }

        $this->addComplianceViewGroup($total);
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $requiredGroup = new ComplianceViewGroup('required', 'Required Actions to Participate &amp; Earn Points');

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setReportName('Biometric Screening - Blood Test, Blood Pressure...');
        $screening->emptyLinks();
        $screening->addLink(new Link('Sign-Up', 'https://lifestyle.advocatehealth.com/onlineregistrationrequest/landingpage/5'));
        $screening->addLink(new Link('Results', '/content/989'));
        $screening->setAttribute('report_name_link', '/content/1094_hppolee#1aBioScreen');

        $requiredGroup->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setReportName('Health Risk Assessment');
        $hra->emptyLinks();
        $hra->addLink(new Link('Take HRA', '/content/1006'));
        $hra->addLink(new Link('Results', '/content/989'));
        $hra->setAttribute('report_name_link', '/content/1094_hppolee#1bHRA');
        $requiredGroup->addComplianceView($hra);

        $fair = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $fair->setName('wellness_fair');
        $fair->setReportName('Wellness Fair');
        $fair->addLink(new Link('Learn More', '#'));
        $fair->setAttribute('report_name_link', '/content/1094_hppolee#1cWellnessFair');

        $requiredGroup->addComplianceView($fair);

        $coaching = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $coaching->setName('health_coaching');
        $coaching->setReportName('Health Coaching');
        $coaching->addLink(new Link('Learn More / Get Form', '/content/1094_hppolee#2dDental'));
        $coaching->setAttribute('report_name_link', '/content/1094_hppolee#2dDental');
        $requiredGroup->addComplianceView($coaching);

        $this->addComplianceViewGroup($requiredGroup);

        $actionsGroup = new ComplianceViewGroup('actions', 'Actions for Points');
        $actionsGroup->setPointsRequiredForCompliance(0);

        $coachingStatusChecker = function(ComplianceViewStatus $status, User $user) use($coaching) {
            if ($coaching->getMappedStatus($user)->isCompliant()) {
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        };

        $disease = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $disease->setName('disease');
        $disease->setReportName('Disease Management - as recommended; points if not');
        $disease->addLink(new Link('Learn More', '/content/1094_hppolee#2aDiseaseManCoach'));
        $disease->setAttribute('report_name_link', '/content/1094_hppolee#2aDiseaseManCoach');
        $disease->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(75, 0, 0, 0));
        $disease->setPostEvaluateCallback($coachingStatusChecker);
        $actionsGroup->addComplianceView($disease);

        $lifestyle = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $lifestyle->setName('lifestyle');
        $lifestyle->setReportName('Lifestyle Coaching - as recommended; points if not');
        $lifestyle->addLink(new Link('Learn More', '/content/1094_hppolee#2bLifestyleCoach'));
        $lifestyle->setAttribute('report_name_link', '/content/1094_hppolee#2bLifestyleCoach');
        $lifestyle->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(40, 0, 0, 0));
        $lifestyle->setPostEvaluateCallback($coachingStatusChecker);
        $actionsGroup->addComplianceView($lifestyle);

        $preventive = new PlaceHolderComplianceView(null, 0);
        $preventive->setName('preventive');
        $preventive->setReportName('Recommended Preventive Screenings @ Milestones');
        $preventive->addLink(new Link('Learn More / Get Form', '/content/1094_hppolee#2cPrevScreen'));
        $preventive->setAttribute('report_name_link', '/content/1094_hppolee#2cPrevScreen');
        $preventive->setMaximumNumberOfPoints(50);
        $actionsGroup->addComplianceView($preventive);

        $dental = new PlaceHolderComplianceView(null, 0);
        $dental->setName('dental');
        $dental->setReportName('Dental Exam');
        $dental->addLink(new Link('Learn More / Get Form', '/content/1094_hppolee#2dDental'));
        $dental->setAttribute('report_name_link', '/content/1094_hppolee#2dDental');
        $dental->setMaximumNumberOfPoints(10);
        $actionsGroup->addComplianceView($dental);

        $physical = new PlaceHolderComplianceView(null, 0);
        $physical->setName('physical');
        $physical->setReportName('Bi-Annual Physical; points if not scheduled year');
        $physical->addLink(new Link('Learn More / Get Form', '/content/1094_hppolee#2eWellExam'));
        $physical->setAttribute('report_name_link', '/content/1094_hppolee#2eWellExam');
        $physical->setMaximumNumberOfPoints(5);
        $actionsGroup->addComplianceView($physical);

        $tobaccoFree = new PlaceHolderComplianceView(null, 0);
        $tobaccoFree->setName('tobacco');
        $tobaccoFree->setReportName('Tobacco Free Pledge');
        $tobaccoFree->addLink(new Link('Learn More / Get Form', '/content/1094_hppolee#2eTobaccoFree'));
        $tobaccoFree->setAttribute('report_name_link', '/content/1094_hppolee#2eTobaccoFree');
        $tobaccoFree->setMaximumNumberOfPoints(10);
        $actionsGroup->addComplianceView($tobaccoFree);

        $seatbelt = new PlaceHolderComplianceView(null, 0);
        $seatbelt->setName('seatbelt');
        $seatbelt->setReportName('Seat Belt Pledge');
        $seatbelt->addLink(new Link('Learn More / Get Form', '/content/1094_hppolee#2fSeatBelt'));
        $seatbelt->setAttribute('report_name_link', '/content/1094_hppolee#2fSeatBelt');
        $seatbelt->setMaximumNumberOfPoints(5);
        $actionsGroup->addComplianceView($seatbelt);

        $sickLeaveUsage = new PlaceHolderComplianceView(null, 0);
        $sickLeaveUsage->setName('sick_leave');
        $sickLeaveUsage->setReportName('Sick Leave Usage - Except FMLA for another.');
        $sickLeaveUsage->addLink(new Link('Learn More', '/content/1094_hppolee#2gSickLeave'));
        $sickLeaveUsage->setAttribute('report_name_link', '/content/1094_hppolee#2gSickLeave');
        $sickLeaveUsage->setMaximumNumberOfPoints(10);
        $actionsGroup->addComplianceView($sickLeaveUsage);

        $fitnessBonus = new PlaceHolderComplianceView(null, 0);
        $fitnessBonus->setName('fitness_bonus');
        $fitnessBonus->setReportName('Fitness Bonus Test');
        $fitnessBonus->addLink(new Link('Learn More / Get Form', '/content/1094_hppolee#2hFitnessBonus'));
        $fitnessBonus->setAttribute('report_name_link', '/content/1094_hppolee#2hFitnessBonus');
        $fitnessBonus->setMaximumNumberOfPoints(10);
        $actionsGroup->addComplianceView($fitnessBonus);

        $noInjuryAccidents = new PlaceHolderComplianceView(null, 0);
        $noInjuryAccidents->setName('no_injury');
        $noInjuryAccidents->setReportName('No Injury-Related Preventable Accidents');
        $noInjuryAccidents->addLink(new Link('Learn More', '/content/1094_hppolee#2iNoInjury'));
        $noInjuryAccidents->setAttribute('report_name_link', '/content/1094_hppolee#2iNoInjury');
        $noInjuryAccidents->setMaximumNumberOfPoints(10);
        $actionsGroup->addComplianceView($noInjuryAccidents);

        $this->addComplianceViewGroup($actionsGroup);
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
        return new HighlandParkPoliceEmployee2015ComplianceProgramReportPrinter();
    }
}

class HighlandParkPoliceEmployee2015ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            ** You receive the applicable points: If this requirement is recommended and you
                complete it; or If it does not apply to you.
        </div>-->
    <?php
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $endDate = $status->getComplianceProgram()->getEndDate('m/d/Y');

        $statusText = $status->getPoints() >= 205 ? '10%' : ($status->getPoints() >= 155 ? '5%' : '0%');

        if(!$status->getComplianceViewStatus('spouse')->isCompliant()) {
            $statusText = '0%';
        }

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


        <script type="text/javascript">
            $(function() {
                $('tr.view-mine').prev('tr.headerRow').find('td').last().html(
                    'Minimum Points Needed for Incentive  By <?php echo $endDate ?>'
                );

                $('tr.view-mine .links').html('205 points for 10%; 155 for 5%');
                $('tr.view-spouse .links').html('');

                $('tr.headerRow-totals td:eq(0)').html('Current Total');
                $('tr.view-mine td:eq(1)').html('<?php echo $status->getPoints() ?>');
                $('tr.view-mine td:eq(2)').html('<?php echo $statusText ?>');

                $('tr.view-spouse td:eq(1)').html('<?php echo $status->getComplianceViewStatus('spouse')->getPoints() ?>');
            });
        </script>

        <p>Hello <?php echo $status->getUser()->getFullName() ?>,</p>
        <p>The matrix below shows the status of your key actions and points that count toward the WIN program.</p>
        <p>Click on any link to learn more and get these things done for your wellbeing and other rewards!</p>

    <?php
    }

}