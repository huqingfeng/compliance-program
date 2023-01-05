<?php
use hpn\steel\query\SelectQuery;

class ComplyWithCotinineScreeningTestDirectComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function  __construct($startDate, $endDate, $screening = null)
    {
        parent::__construct($startDate, $endDate, $screening);
        $this->addResultMapping('positive', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('Positive', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('Postivie', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('P', ComplianceStatus::NOT_COMPLIANT, 'Positive');
        $this->addResultMapping('nos', ComplianceStatus::COMPLIANT, 'Negative');
        $this->addResultMapping('negative', ComplianceStatus::COMPLIANT, 'Negative');
        $this->addResultMapping('Negtaive', ComplianceStatus::COMPLIANT, 'Negative');
        $this->addResultMapping('N', ComplianceStatus::COMPLIANT, 'Negative');
        $this->addResultMapping('Non-User', ComplianceStatus::COMPLIANT, 'Non-User');
    }

    public function getTestName()
    {
        return 'cotinine';
    }

    public function getDefaultName()
    {
        return 'comply_with_cotinine_screening_test';
    }

    public function getStatus(User $user) {

        $startDate = $this->getStartDate('Y-m-d');
        $endDate = $this->getEndDate('Y-m-d');

        $query = SelectQuery::create()
        ->select('cotinine')
        ->from('screening')
        ->where('user_id = ?', [$user->getId()])
        ->andWhere('date BETWEEN ? and ?', [$startDate, $endDate])
        ->andWhere('cotinine IS NOT NULL')
        ->orderBy('date desc')
        ->execute()
        ->toArray();

        if(empty($query)) {
            return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
        } else {
            switch ($query[0]['cotinine']) {
                case 'Positive':
                case 'positive':
                case 'P':
                case 'p':
                    return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT, null, "Positive");
                case 'Negative':
                case 'negative':
                case 'N':
                case 'n':
                    return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, "Negative");
            }
        }

        return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
    }
}
