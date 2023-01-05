<?php

use hpn\steel\query\SelectQuery;

require_once 'lib/functions/getExtendedRiskForUser2010.php';

class XrayTechnologies2022ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();
        $printer->setShowUserLocation(true);
        $printer->setShowUserFields(true, true, false, false, true);
        $printer->setShowTotals(false);
        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowCompliant(false, false, false);
        $printer->setShowPoints(false, false, false);
        $printer->setShowUserContactFields(null, null, true);

        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $data = array();

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewName = $viewStatus->getComplianceView()->getReportName();

                    if($groupStatus->getComplianceViewGroup()->getName() == 'Requirements') {
                        $data[sprintf('%s Result', $viewName)] = $viewStatus->getComment();
                    } elseif($groupStatus->getComplianceViewGroup()->getName() == 'required') {
                        $data[sprintf('%s Comment', $viewName)] = $viewStatus->getComment();
                    }

                    if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                        $data[sprintf('%s Compliant', $viewName)] =  'Yes';
                    } elseif($viewStatus->getStatus() == ComplianceStatus::NA_COMPLIANT) {
                        $data[sprintf('%s Compliant', $viewName)] =  'Not Required';
                    } else {
                        $data[sprintf('%s Compliant', $viewName)] =  'No';
                    }
                }
            }

            $data['Compliance Program - Comment'] = $status->getComment();
            $data['Compliance Program - Compliant'] = $status->isCompliant() ? 'Yes' : 'No';

            return $data;
        });

        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new XrayTechnologies2022WMS2Printer();
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    function getRisk(User $user) {
        $riskIdentifiers = $user->getClient()->getConfigurationParameter('app_legacy_extended_risk_report2010_riskIdentifiers', array());
        $resultIdentifiers = array(
            605 => 'Diastolic',
            604 => 'Systolic',
            606 => 'Cholesterol',
            607 => 'HDL'
        );

        $hra = HRA::getNewestHRABetweenDates($user, '2022-08-01', '2022-11-02', false, false);
        $hraData = $hra->getHRA();
        $screeningData = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
            $user,
            new DateTime('2022-08-01'),
            new DateTime('2022-11-02'),
            array(
                'merge'            => true,
                'require_complete' => false
            )
        );

        $risk = getChpExtendedRiskForUser($user, array_unique(array_merge(array_keys($riskIdentifiers), array_keys($resultIdentifiers))), !empty($hraData['id']) ? $hraData['id'] : false, !empty($screeningData['id']) ? $screeningData['id'] : false, '2022-08-01', '2022-11-02');

        return $risk['number_of_risks'];
    }

    public function loadGroups()
    {
        $mapping = new ComplianceStatusMapper();

        $this->setComplianceStatusMapper($mapping);

        $program_start = $this->getStartDate();
        $program_end = $this->getEndDate();

        $core_group = new ComplianceViewGroup('required', 'Prevention Events');

        $hra_view = new CompleteHRAComplianceView($program_start, $program_end);
        $hra_view->setReportName('Health Risk Appraisal (HRA)');
        $hra_view->setName('hra');
        $core_group->addComplianceView($hra_view);

        $screeningView = new CompleteScreeningComplianceView($program_start, $program_end);
        $screeningView->setReportName('Wellness Screening');
        $screeningView->setName('screening');
        $core_group->addComplianceView($screeningView);

        $coaching_view = new PlaceHolderComplianceView(ComplianceStatus::NA_COMPLIANT);
        $coaching_view->setReportName('Share Your Results');
        $coaching_view->setName('share_results');
        $core_group->addComplianceView($coaching_view);

        $consultation_view = new CompletePrivateConsultationComplianceView($program_start, $program_end);
        $consultation_view->setReportName('Web/Phone Consultation');
        $consultation_view->setName('consultation');
        $core_group->addComplianceView($consultation_view);


        $spouseParticipation = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
        $spouseParticipation->setName('spouse_participation');
        $spouseParticipation->setReportName('Spouse Compliance');
        $spouseParticipation->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            if($user->getRelationshipType() == Relationship::EMPLOYEE) {
                $spouseUser = $user->getSpouseUser();
            } elseif($user->getRelationshipType() == Relationship::SPOUSE) {
                $spouseUser = $user->getEmployeeUser();
            }
            $override = $status->getUsingOverride();

            if(!$spouseUser || (isset($this->options['in_spouse']) && $this->options['in_spouse']))  {
                if (!$override) $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            } else {
                $program = ComplianceProgramRecordTable::getInstance()->find(XrayTechnologies2022ComplianceProgram::XRAYTECHNOLOGIES_2022_RECORD_ID)->getComplianceProgram(array('in_spouse' => true));

                $program->setActiveUser($spouseUser);
                $overallStatus = $program->getStatus();

                $requiredGroupStatus = $overallStatus->getComplianceViewGroupStatus('required');
                $hraStatus = $requiredGroupStatus->getComplianceViewStatus('hra');
                $screeningStatus = $requiredGroupStatus->getComplianceViewStatus('screening');
                $shareStatus = $requiredGroupStatus->getComplianceViewStatus('share_results');


                $status->setAttribute('has_spouse', true);
                if ($user->getRelationshipType() == Relationship::EMPLOYEE) {
                    $status->setAttribute('spouse_text', 'Spouse');
                } elseif($user->getRelationshipType() == Relationship::SPOUSE) {
                    $status->setAttribute('spouse_text', 'Employee');
                }

                $status->setAttribute('spouse_hra_status', $hraStatus->getStatus());

                $status->setAttribute('spouse_screening_status', $screeningStatus->getStatus());

                $status->setAttribute('spouse_share_status', $shareStatus->getStatus());

                if($hraStatus->getStatus() == ComplianceStatus::COMPLIANT
                    && $screeningStatus->getStatus() == ComplianceStatus::COMPLIANT
                    && $shareStatus->getStatus() == ComplianceStatus::COMPLIANT) {
                    $status->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        });
        $core_group->addComplianceView($spouseParticipation, true);

        $this->addComplianceViewGroup($core_group);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        parent::evaluateAndStoreOverallStatus($status);

        $user = $status->getUser();
        $risk = $this->getRisk($user);

        $hra_view_status = $status->getComplianceViewStatus('hra');
        $screening_view_status = $status->getComplianceViewStatus('screening');
        $share_results_view_status = $status->getComplianceViewStatus('share_results');
        $consultation_view_status = $status->getComplianceViewStatus('consultation');

        if ($consultation_view_status->getStatus() != ComplianceStatus::COMPLIANT) {
            $consultation_view_status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }

        if ($hra_view_status->getStatus() == ComplianceStatus::COMPLIANT && $risk >= 4) {
            if ($consultation_view_status->getStatus() == ComplianceStatus::COMPLIANT) {
                $share_results_view_status->setStatus(ComplianceStatus::COMPLIANT);
            } else {
                $share_results_view_status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            }
        } else if ($hra_view_status->getStatus() == ComplianceStatus::COMPLIANT) {
            $share_results_view_status->setStatus(ComplianceStatus::COMPLIANT);
        }

        $share_results_override = SelectQuery::create()
            ->hydrateSingleScalar()
            ->select('status')
            ->from('compliance_view_status_overrides')
            ->where('user_id = ?', array($user->id))
            ->andWhere('compliance_program_record_id = ?', array(XrayTechnologies2022ComplianceProgram::XRAYTECHNOLOGIES_2022_RECORD_ID))
            ->andWhere('compliance_view_name = ?', array('share_results'))
            ->execute();

        if($share_results_override) {
            if($share_results_override == ComplianceStatus::COMPLIANT) {
                $share_results_view_status->setStatus(ComplianceStatus::COMPLIANT);
            } elseif($share_results_override == ComplianceStatus::NOT_COMPLIANT) {
                $share_results_view_status->setStatus(ComplianceStatus::NOT_COMPLIANT);
            } elseif($share_results_override == ComplianceStatus::NA_COMPLIANT) {
                $share_results_view_status->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        }
    }

    const XRAYTECHNOLOGIES_2022_RECORD_ID = 1739;
}


class XrayTechnologies2022WMS2Printer implements ComplianceProgramReportPrinter
{
    public function getLabel($status)
    {
        if ($status == ComplianceStatus::COMPLIANT) {
            return '<i class="fa fa-check text-success"></i>';
        } else if ($status == ComplianceStatus::PARTIALLY_COMPLIANT) {
            return '<label class="label label-danger">Incomplete</label>';
        } else if ($status == ComplianceStatus::NA_COMPLIANT ) {
            return '<label class="label label-warning">Pending</label>';
        } else {
            return '<label class="label label-danger">Incomplete</label>';
        }
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $hra_view_status = $status->getComplianceViewStatus('hra');
        $screening_view_status = $status->getComplianceViewStatus('screening');
        $share_results_view_status = $status->getComplianceViewStatus('share_results');
        $spouse_status = $status->getComplianceViewStatus('spouse_participation');


        $hra_status = $this->getLabel($hra_view_status->getStatus());
        $screening_status = $this->getLabel($screening_view_status->getStatus());
        $share_results_status = $this->getLabel($share_results_view_status->getStatus());

        ?>

        <style>
            .banner-img {
                width: 100%;
            }

            #wms1 h3 {
                font-size: 34px;
            }

            #edw_reportcard .basic-report-card {
                padding-bottom: 20px;
            }

            #edw_reportcard .row {
                display: flex;
                align-items: center;
            }

            .previous_cards {
                margin-top: 10px;
                display: inline-block;
            }

            .alert-norisk {
                margin: 20px 40px 0px;
                padding: 10px 20px;
                color: #444;
            }

            .top-buffer-10 {
                margin-top: 10px;
            }

            .basic-report-card {
                padding-bottom: 20px;
            }

            .spouse-status {
                padding-bottom: 10px;
            }

            @media (max-width: 767px) {
                .main-content ul {
                    padding-left: 20px;
                }

                .top-buffer-10 {
                    margin-top: 0px;
                }

                .row div {
                    margin-bottom: 10px;
                }

                .row div .btn-primary {
                    margin-bottom: 10px;
                }

                .basic-report-card strong {
                    padding-left:20px;
                    padding-right:20px;
                    display: inline-block;
                }

                .basic-report-card .alert-norisk {
                    display: block !important;
                    margin: 20px 20px 0px;
                }
            }
        </style>

        <div id="">
            <h3>2022 X-Ray Report Card</h3>
            <div class="main-content">
                <p>The 2022 wellness program will offer the same simplified requirements this year while giving you a more in-depth picture of your well-being.</p>

                <ol>
                    <li>
                        <p>
                           Let’s avoid flu season! We felt it was in our employees’ best interest to begin accepting biometric
                           screenings in August this year to avoid sending you to your physician’s office or to the lab during
                           flu season! We hope you will take advantage of this opportunity!
                        </p>
                    </li>
                    <li>
                        <p>
                            There are only TWO requirements that everyone must complete in 2022 to be compliant for 2023:
                            Take the Health Risk Assessment and Complete a Screening. If you are identified by Circle Wellness
                            through your HRA and Screening results to be of higher risk, you will be notified by letter and
                            will be required to either complete 1 health coaching call with Circle Wellness or submit a form
                            from your doctor stating that you have shared your results with them.
                        </p>
                    </li>
                    <li>
                        <p>
                            We are offering the expanded health screening panel again in 2022.  We want to help you identify more areas
                            of concern and the expanded testing panel will provide you with a better look at your overall health.
                            <a href="http://www.static.hpn.com/pdf/clients/xray/Screening_Components_29_Panel_Plus_HBA1C.pdf" target="_blank">Click here to view all results that will be tested.</a>
                            All results are confidential and will only be accessed by Circle Wellness.
                        </p>
                    </li>
                </ol>

            </div>
            <div class="basic-report-card">
                <div class="row">
                    <div class="col-sm-12"><br></div>
                </div>
                <div class="row">
                    <div class="col-sm-2 icon"><i class="fa fa-chart-pie"></i></div>
                    <div class="col-sm-4">
                        <div>
                            <div class="visible-xs-block visible-sm-block text-center"><strong>Health Risk Assessment (HRA)</strong>
                            </div>
                            <div class="hidden-xs hidden-sm"><strong>Health Risk Assessment (HRA)</strong></div>
                        </div>
                    </div>
                    <div class="col-sm-4 actions">
                        <a class="btn btn-primary btn-sm" href="/compliance/xray-industries-2022/hra/content/my-health">Take HRA</a>
                    </div>
                    <div class="col-sm-2 icon hra"><?= $hra_status; ?></div>
                </div>
                <div class = "alert alert-norisk" style="display: inline-block;">Complete between 8/1/2022 & 11/2/2022</div>
                <div class="row">
                    <div class="col-sm-12">
                        <hr>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 icon"><i class="fa fa-file-alt"></i></div>
                    <div class="col-sm-4">
                        <div>
                            <div class="visible-xs-block visible-sm-block text-center"><strong>Health Screening</strong></div>
                            <div class="hidden-xs hidden-sm"><strong>Health Screening</strong></div>
                        </div>
                    </div>
                    <div class="col-sm-4 actions">
                        <a class="btn btn-primary btn-sm" href="http://xray.circlewellondemand.com/" target="_blank">Request On-Demand Packet</a>
                        <a class="btn btn-primary btn-sm top-buffer-10" href="/resources/10763/Xray_Industries_2022_PCP_Form.pdf" target="_blank">Download Physician Form</a>
                    </div>
                    <div class="col-sm-2 icon screening"><?= $screening_status; ?></div>
                </div>
                <div class = "alert alert-norisk">Request an on-demand packet to take to a local LabCorp location or download the physician form and bring it to your doctor. Complete between 8/1/2022 & 11/2/2022</div>
                <div class="row">
                    <div class="col-sm-12">
                        <hr>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 icon"><i class="fa fa-user-md"></i></div>
                    <div class="col-sm-4">
                        <div>
                            <div class="visible-xs-block visible-sm-block text-center"><strong>Share Your Results With Your Doctor or Complete a Coaching Call</strong></div>
                            <div class="hidden-xs hidden-sm"><strong>Share Your Results With Your Doctor or Complete a Coaching Call</strong></div>
                        </div>

                    </div>
                    <div class="col-sm-4 actions">
                        <a class="btn btn-primary btn-sm" href="/resources/10764/2022_XRAY_Physician_Verification_Form.doc" target="_blank">Download Form</a>
                    </div>
                    <div class="col-sm-2 icon consultation"><?= $share_results_status; ?></div>
                </div>
                <div class = "alert alert-norisk">If you have 4 or more risks, share your results with your physician and have them complete and return the Physician Form or call Circle Wellness at 1-866-682-3020; ext. 204 to complete one coaching call with the health coach by 3/1/22</div>

                <?php if($spouse_status->getAttribute('has_spouse')):?>
                    <div class="row">
                        <div class="col-sm-12">
                            <hr>
                        </div>
                    </div>

                    <div class="row" style="padding-bottom:20px;">
                        <div class="col-sm-4">
                            <div class="label label-info" style="font-size:13pt; margin-left:35px;">
                                Spouse Compliance
                            </div>
                        </div>
                        <div class="col-sm-8">
                        </div>
                    </div>

                    <div class="row spouse-status">
                        <div class="col-sm-2 icon"><i class="fa fa-chart-pie"></i></div>
                        <div class="col-sm-4">
                            <div>
                                <div class="visible-xs-block visible-sm-block text-center"><strong>Health Risk Assessment (HRA)</strong>
                                </div>
                                <div class="hidden-xs hidden-sm"><strong>Health Risk Assessment (HRA)</strong></div>
                            </div>
                        </div>
                        <div class="col-sm-4 actions">

                        </div>
                        <div class="col-sm-2 icon hra"><?= $this->getLabel($spouse_status->getAttribute('spouse_hra_status')); ?></div>
                    </div>

                    <div class="row spouse-status">
                        <div class="col-sm-2 icon"><i class="fa fa-file-alt"></i></div>
                        <div class="col-sm-4">
                            <div>
                                <div class="visible-xs-block visible-sm-block text-center"><strong>Health Screening</strong></div>
                                <div class="hidden-xs hidden-sm"><strong>Health Screening</strong></div>
                            </div>
                        </div>
                        <div class="col-sm-4 actions">

                        </div>
                        <div class="col-sm-2 icon screening"><?= $this->getLabel($spouse_status->getAttribute('spouse_screening_status')); ?></div>
                    </div>

                    <div class="row spouse-status">
                        <div class="col-sm-2 icon"><i class="fa fa-user-md"></i></div>
                        <div class="col-sm-4">
                            <div>
                                <div class="visible-xs-block visible-sm-block text-center"><strong>Share Your Results With Your Doctor or Complete a Coaching Call</strong></div>
                                <div class="hidden-xs hidden-sm"><strong>Share Your Results With Your Doctor or Complete a Coaching Call</strong></div>
                            </div>

                        </div>
                        <div class="col-sm-4 actions">

                        </div>
                        <div class="col-sm-2 icon screening"><?= $this->getLabel($spouse_status->getAttribute('spouse_share_status')); ?></div>
                    </div>
                <?php endif ?>

            </div>
        </div>
        <script type="text/javascript">

        </script>
        <?php
    }
}

