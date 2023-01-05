<?php

error_reporting(0);

class RenewingManagement2022ComplianceProgram extends ComplianceProgram
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

        $hraScreengEnd = '2022-05-13';

        $required = new ComplianceViewGroup('Required');

        $hra = new CompleteHRAComplianceView($startDate, $hraScreengEnd);
        $hra->setName("hra");

        $required->addComplianceView($hra);

        $screening = new CompleteScreeningComplianceView($startDate, $hraScreengEnd);
        $screening->setName("screening");

        $required->addComplianceView($screening);

        $coaching = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coaching->setName("coaching");
        $coaching->setReportName('High Risk Coaching');

        $coaching->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            require_once sfConfig::get('sf_root_dir').
            '/apps/frontend/modules/legacy/legacy_lib/lib/functions/getExtendedRiskForUser2010.php';

            $riskIdentifiers = $user->getClient()->getConfigurationParameter('app_legacy_extended_risk_report2010_riskIdentifiers', array());

            $resultIdentifiers = array(
                605 => 'Diastolic',
                604 => 'Systolic',
                606 => 'Cholesterol',
                607 => 'HDL'
            );

            $hra = HRA::getNewestHRABetweenDates($user, '2022-01-01', '2022-05-13', false, false);
            $hraData = $hra->getHRA();
            $screeningData = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
                $user,
                new DateTime('2022-01-01'),
                new DateTime('2022-05-13'),
                array(
                    'merge'            => true,
                    'require_complete' => false
                )
            );

            $risk = getChpExtendedRiskForUser($user, array_unique(array_merge(array_keys($riskIdentifiers), array_keys($resultIdentifiers))), !empty($hraData['id']) ? $hraData['id'] : false, !empty($screeningData['id']) ? $screeningData['id'] : false, '2022-01-01', '2022-11-01');

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
        return new RenewingManagement2022ComplianceProgramReportPrinter();
    }
}

class RenewingManagement2022ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter
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
            <h1>2022 Wellness Program</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <hr/>
        </div>
    </div>

    <h3>
        All employees are eligible to participate in the wellness program, which includes a confidential health screening
        and HRA questionnaire. Spouses who are on the medical plan are also eligible to participate. Employees on the
        medical plan who complete the health screening and HRA will earn a premium incentive.
    </h3>

    <h3>
        The wellness program includes high risk health coaching for those who qualify. If you have 4 or more risk factors
        based on your HRA and health screening results, you qualify for coaching. A few weeks after your screening, you
        will receive a letter in the mail inviting you to connect with your Circle Wellness Health Coach. The program
        consists of 2 coaching calls between you and your coach to review your results, set goals and work to improve
        your health. The program is 100% confidential and is completely optional for those who qualify. Also, participation
        is not required to earn the premium incentive.
    </h3>

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
                <span class="label grey-label">Complete by 5/13/2022</span>
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
                <span class="label grey-label">Complete by 5/13/2022</span>
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