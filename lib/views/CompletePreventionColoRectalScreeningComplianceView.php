<?php

class CompletePreventionColoRectalScreeningComplianceView extends DateBasedComplianceView
{
    public function getDefaultStatusSummary($status)
    {
        switch($status) {
            case ComplianceViewStatus::COMPLIANT:
                return 'Complete Colo-Rectal Screening';
                break;
            default:
                return null;
        }
    }

    public function __construct($programStart, $programEnd)
    {
        $this
            ->setStartDate($programStart)
            ->setEndDate($programEnd)
            ->minimumAge = null;
    }

    public function getDefaultName()
    {
        return 'prevention_colo_rectal_screening';
    }

    public function getDefaultReportName()
    {
        return 'Colo-Rectal Screening';
    }

    public function setMinimumAge($age)
    {
        $this->minimumAge = $age;
    }

    public function setClosingDate($date)
    {
        $fdate = strtotime($date);

        if($fdate === false) {
            throw new InvalidArgumentException('Invalid date: '.$date);
        }

        $this->closingDate = date('Y-m-d H:i:s', $fdate);

        return $this;
    }

    public function getStatus(User $user)
    {
        $complianceAge = $user->getAge($this->getStartDate(), true);

        if($this->minimumAge !== null && $complianceAge < $this->minimumAge) {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        } else {
            // We are going to use a custom query, since this is a special case for requirements.
            $earliestDateAccepted8 = date('Y-m-d', strtotime('-120 months', $this->getStartDate('U')));
            $earliestDateAccepted9 = date('Y-m-d', strtotime('-60 months', $this->getStartDate('U')));
            $earliestDateAccepted10 = date('Y-m-d', strtotime('-12 months', $this->getStartDate('U')));
            $latestDateAccepted = $this->getEndDate('Y-m-d');

            $preventionQuery = "
        SELECT prevention_data.id,prevention_data.date
        FROM prevention_data
        INNER JOIN prevention_codes ON prevention_codes.code = prevention_data.code
        WHERE user_id = ? AND (
             ( prevention_codes.type = 8  AND prevention_data.date >= ? )
          OR ( prevention_codes.type = 9  AND prevention_data.date >= ? )
          OR ( prevention_codes.type = 10 AND prevention_data.date >= ? )
        )
        AND prevention_data.date <= ?
      ";

            $args = array($user->getID(), $earliestDateAccepted8, $earliestDateAccepted9, $earliestDateAccepted10, $latestDateAccepted);

            if($this->closingDate) {
                $preventionQuery .= ' AND prevention_data.created_at <= ?';
                $args[] = $this->closingDate;
            }

            $preventionQuery .= "
        ORDER BY prevention_data.date DESC
        LIMIT 1
      ";

            $_db = Piranha::getInstance()->getDatabase();
            $_db->executeSelect($preventionQuery, $args);

            $firstRow = $_db->getNextRow();

            if($firstRow === false) {
                return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
            } else {
                $dateFinished = date('m/d/Y', strtotime($firstRow['date']));

                return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, $dateFinished);
            }
        }
    }

    private $minimumAge;
    protected $closingDate;
}