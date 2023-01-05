<?php
/**
 * Evaluates if a user has completed an HRA between two dates.
 */
class CompleteHRAComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
        $this->addLink(new Link(sprintf('Take %s', sfConfig::get('app_legacy_hra_abbreviation', 'HRA')), '/content/989'));
        $this->addLink(new Link('Results', '/content/989'));
    }

    public function getDefaultStatusSummary($status)
    {
        if($status == ComplianceViewStatus::COMPLIANT) {
            return 'Complete HRA';
        }

        return null;
    }

    public function getDefaultName()
    {
        return 'complete_hra';
    }

    public function getDefaultReportName()
    {
        return 'Complete HRA';
    }

    public function getStatus(User $user)
    {
        $hraQuery = '
            SELECT date,done,id
            FROM hra
            WHERE user_id = ?
            AND date BETWEEN ? AND ?
            ORDER BY done = 1 DESC, date ASC
        ';

        $db = Database::getDatabase();

        $db->executeSelect(
            $hraQuery,
            $user->getID(),
            $this->getStartDate('Y-m-d'),
            $this->getEndDate('Y-m-d')
        );

        $hraRow = $db->getNextRow();

        $rangeIds = $hraRow ? array($hraRow['id']) : array();

        while($hraRow && ($nextHraRow = $db->getNextRow())) {
            $rangeIds[] = $nextHraRow['id'];
        }

        if($hraRow && $hraRow['done'] == 1) {
            $hraDate = date('m/d/Y', strtotime($hraRow['date']));

            $status = new ComplianceViewStatus($this, ComplianceStatus::COMPLIANT, null, $hraDate);
            $status->setAttribute('id', $hraRow['id']);
            $status->setAttribute('range_ids', $rangeIds);
            $status->setAttribute('date', $hraDate);
        } else if($hraRow) {
            $status = new ComplianceViewStatus($this, ComplianceStatus::PARTIALLY_COMPLIANT);
        } else {
            $status = new ComplianceViewStatus($this, ComplianceStatus::NOT_COMPLIANT);
        }

        return $status;
    }

    public function setDefaultComment($comment)
    {
        $this->defaultComment = $comment;

        return $this;
    }

    /**
     * If true, will show the date of a taken HRA even if not in range.
     *
     * @param boolean $boolean
     * @return CompleteHRAComplianceView
     */
    public function setShowDateTaken($boolean)
    {
        $this->showDateTaken = $boolean;

        return $this;
    }

    private $defaultComment = null;
    private $showDateTaken = false;
}