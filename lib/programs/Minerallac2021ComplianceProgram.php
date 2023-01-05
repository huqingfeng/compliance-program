<?php

error_reporting(0);

class Minerallac2021ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

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

        $hra = new CompleteHRAComplianceView($startDate, $endDate);
        $hra->setName("hra");

        $required->addComplianceView($hra);

        $screening = new CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setName("screening");

        $required->addComplianceView($screening);

        $coaching = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coaching->setName("coaching");
        $coaching->setReportName('High Risk Coaching');

        $coaching->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            require_once sfConfig::get('sf_root_dir').
            '/apps/frontend/modules/legacy/legacy_lib/lib/functions/getExtendedRiskForUser2010.php';

            $risk = getChpExtendedRiskForUser($user, false, false, false, $startdate = "2021-02-03", $enddate = "2021-11-01");

            if ($risk['number_of_risks'] >= 4) {
                $status->setComment("Qualified");
            } else {
                $status->setComment("Not Qualified");
            }
        });

        $required->addComplianceView($coaching);

        $this->addComplianceViewGroup($required);
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new Minerallac2021ComplianceProgramReportPrinter();
    }
}

class Minerallac2021ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function displayStatus($status, $incorrect = false) {
        if ($status->getStatus() == ComplianceStatus::COMPLIANT) {
            return '<i class="fa fa-check success"></i>';
        } else if (($status->getStatus() == ComplianceStatus::NOT_COMPLIANT ||
            $status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) && $incorrect) {
            return '<i class="fa fa-times danger"></i>';
        } else {
            return '<label class="label label-danger">Incomplete</label>';
        }
    }

    public function printReport(ComplianceProgramStatus $status)
    { 
        $hraStatus = $status->getComplianceViewStatus('hra');
        $screeningStatus = $status->getComplianceViewStatus('screening');
        $coachingStatus = $status->getComplianceViewStatus('coaching');

    ?>

     <style>
        .grey-label {
            background: #546E7A;
            white-space: normal;
            display: inline-block;
            line-height: 18px;
            text-align: left;
            font-size: 12px;
            padding: .3em .6em .3em;
        }

        .success {
            color: #74c36e;
        }

        .danger {
            color: #F15752;
        }

        #nsk-card strong {
            line-height: 20px;
            margin-bottom: 10px;
            display: inline-block;
        }

        h2 {
            margin-top: 40px;
        }

        h3 {
            line-height: 24px;
        }

        .basic-report-card {
            margin-bottom: 20px;
            border-radius: 2px;
            border-color: #EEEEEE;
        }

        .basic-report-card.thick {
            border: 2px solid #E0E0E0;;
        }

        .basic-report-card strong {
            display: inline-block;
            line-height: 20px;
            margin-bottom: 10px;
        }

        .basic-report-card .icon {
            padding-right: 0;
        }

        span.subheading {
            display: inline-block;
            margin-top: -5px;
            line-height: 20px;
            margin-bottom: 10px;
        }
    </style>

    <div class="row">
        <div class="col-md-12">
            <h1>2021 Minerallac Wellness Program</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <hr/>
        </div>
    </div>

    <h3><strong>Please complete the following activities for the Minerallac Wellness Program:</strong></h3>

    <h3><strong>1:</strong> Complete a free, confidential health screening between February 3 - April 3, 2021.<br><br>An onsite screening will be offered at the Hampshire location on March 3rd. If an onsite screening is not offered at your location or if you are unable to attend the onsite screening, you can call Circle Wellness Customer Support at 1-866-682-3020 ext 204 to request an on-demand packet for a LabCorp location.</h3>

    <h3><strong>2:</strong> Complete the Health Risk Assessment questionnaire (HRA) <strong>before</strong> your health screening. The HRA is matched with your screening results to give you a comprehensive health report. If you do not complete your HRA the information you receive on your health will not be as comprehensive.</h3>

    <h3><strong>3:</strong> High Risk Health Coaching Program<br><br>If you have 4 or more risk factors, you will receive a letter in the mail inviting you to participate in the high risk health coaching program a few weeks after your screening. You will also be eligible to do a 6 month recheck of your screening results at a LabCorp location. These offerings are a benefit and are no additional cost to you.</h3>

    <div class="row">
        <div class="col-sm-2 icon"></div>
        <div class="col-sm-5"><strong>Item</strong></div>
        <div class="col-sm-3 actions text-center"><strong>Action</strong></div>
        <div class="col-sm-2 item text-center"><strong>Status</strong></div>
    </div>
    <div class="basic-report-card thick">
        <div class="row">
            <div class="col-sm-12"><br></div>
        </div>
        <div class="row">
            <div class="col-sm-2 icon"><i class="fad fa-chart-pie" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
            <div class="col-sm-5">
                <strong>Health Risk Assessment</strong><br>
                <span class="label grey-label">Complete between 2/3/2021 - 4/3/2021</span>
            </div>
            <div class="col-sm-3 actions">
                <a class="btn btn-primary btn-sm" href="/compliance/minerallac-2021/hra/content/my-health">Take HRA</a>
            </div>
            <div class="col-sm-2 item">
                <?= $this->displayStatus($hraStatus) ?>
            </div>
        </div>
        <div class="row"><div class="col-sm-12"><hr></div></div>
        <div class="row">
            <div class="col-sm-2 icon"><i class="fad fa-clipboard-list" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
            <div class="col-sm-5">
                <strong>Health Screening</strong><br>
                <span class="label grey-label">Complete between 2/3/2021 - 4/3/2021</span>
            </div>
            <div class="col-sm-3 actions">
                <a class="btn btn-primary btn-sm" href="/compliance/minerallac-2021/appointment/content/schedule-appointments">Sign Up</a> 
            </div>
            <div class="col-sm-2 item">
                <?= $this->displayStatus($screeningStatus) ?>
            </div>
        </div>
        <div class="row"><div class="col-sm-12"><hr></div></div>
        <div class="row">
            <div class="col-sm-2 icon"><i class="fad fa-user-md" style="--fa-primary-color: #6CA2BC;--fa-secondary-color: #96AAB5;"></i></div>
            <div class="col-sm-5">
                <strong>High Risk Health Coaching Program</strong><br>
            </div>
            <div class="col-sm-3 actions"></div>
            <div class="col-sm-2 item">
                <?php if ($coachingStatus->getComment() == "Qualified") : ?>
                    <label class="label label-danger">Qualified</label>
                <?php else: ?>
                    <label class="label" style="background: #90A4AE;">Not Qualified</label>
                <?php endif;?>
            </div>
        </div>
        <div class="row"><div class="col-sm-12"><br></div></div>
    </div>
    <?php 
}
}