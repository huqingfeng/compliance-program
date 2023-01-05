<?php

use hpn\steel\query\SelectQuery;

class ComplyWithTobaccoUseScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public $startDate;
    public $endDate;

    public function  __construct($startDate, $endDate, $screening = null)
    {
        parent::__construct($startDate, $endDate, $screening);

        $this->addResultMapping('Y', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('N', ComplianceStatus::COMPLIANT, 'Negative');

        $this->startDate = date('Y-m-d', $startDate);

        if (is_string($endDate)) {
            $this->endDate = $endDate;
        } else {
            $this->endDate = date('Y-m-d', $endDate);
        }
    }

    public function getTestName()
    {
        return 'tobacco_use';
    }

    public function getDefaultName()
    {
        return 'comply_with_tobacco_use_screening_test';
    }

    public function getStatus(User $user) {
        $query = SelectQuery::create()
        ->select('tobacco_use')
        ->from('screening')
        ->where('user_id = ?', [$user->getId()])
        ->andWhere('date BETWEEN ? and ?', [$this->startDate, $this->endDate])
        ->orderBy('date desc')
        ->execute()
        ->toArray();

        if(empty($query)) {
            return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
        } else {
            if($query[0]['tobacco_use'] == 'N') {
                return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, $query[0]['tobacco_use']);
            } else {
                return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT, null, $query[0]['tobacco_use']);
            }
        }

        return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);

    }
}
