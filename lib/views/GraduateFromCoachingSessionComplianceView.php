<?php

class GraduateFromCoachingSessionComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getDefaultName()
    {
        return 'graduate_from_coaching_session';
    }

    public function getDefaultReportName()
    {
        return 'Graduate From Coaching Session';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        if($this->requireTargeted) {
            require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/administration/userCenterLib.php', sfConfig::get('sf_root_dir'));

            if(!validForCoaching($user)
                || !in_array($user->id, sfConfig::get('app_legacy_user_center_coaching_only_include_user_ids', array()))) {
                return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
            } elseif($this->useTargeted) {
                return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
            }
        }

        $edit = CoachingReportEditTable::getInstance()->findMostRecentEditForUser($user, array(
            'start_date' => new DateTime('@'.$this->getStartDate()),
            'end_date'   => new DateTime('@'.$this->getEndDate())
        ));

        if($edit && ($status = $edit->getFieldValue('status_update'))) {
            $risk = CoachingReference::getStatus($status);

            if(isset($this->riskMap[$risk])) {
                $status = new ComplianceViewStatus($this, $this->riskMap[$risk]);

                if($edit->getFieldValue('date')) {
                    $status->setAttribute('completed_date', $edit->getFieldValue('date'));
                }

                return $status;
            }
        }

        return new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
    }

    public function setRequireTargeted($boolean)
    {
        $this->requireTargeted = $boolean;
    }

    public function setUseTargeted($boolean)
    {
        $this->useTargeted = $boolean;
    }

    protected $requireTargeted = false;

    protected $useTargeted = false;

    protected $riskMap = array(
        Risk::BORDERLINE => ComplianceStatus::PARTIALLY_COMPLIANT,
        Risk::NO_RISK    => ComplianceStatus::NA_COMPLIANT,
        Risk::OK         => ComplianceStatus::COMPLIANT,
        Risk::RISK       => ComplianceStatus::NOT_COMPLIANT
    );
}