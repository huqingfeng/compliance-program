<?php

abstract class CompletePreventionComplianceView extends DateBasedComplianceView
{
    public abstract function getPreventionType();

    public function __construct($startDate, $endDate, array $codes = null)
    {
        $this->setDateRange($startDate, $endDate);

        if (isset ($codes)) {
            $this->codes = $codes;
        }

        $this->minimumAge = null;
    }

    public function bindAlias($alias = null)
    {
        $this->alias = $alias;
    }

    public function requiredForUser(User $user)
    {
        if($this->minimumAge === null) {
            return true;
        } else {
            $userAge = $user->getAge(
                $this->minimumAgeFrom ? $this->minimumAgeFrom : $this->getStartDate(),
                true
            );

            return $userAge >= $this->minimumAge;
        }
    }

    public function getDefaultStatusSummary($status)
    {
        if($status == ComplianceViewStatus::COMPLIANT) {
            return sprintf('Complete %s', PreventionType::name($this->getPreventionType()));
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return sprintf('prevention_%s', $this->getPreventionType());
    }

    public function getDefaultReportName()
    {
        return PreventionType::name($this->getPreventionType());
    }

    public function getStatus(User $user)
    {
        if(!$this->requiredForUser($user)) {
            return new ComplianceViewStatus($this, ComplianceViewStatus::NA_COMPLIANT);
        } else {
            $preventionQuery = "
        SELECT prevention_data.id,prevention_data.date
        FROM prevention_data
        INNER JOIN prevention_codes ON prevention_codes.code = prevention_data.code
      ";

            $args = array();

            if($this->alias) {
                $preventionQuery .= '
          INNER JOIN prevention_code_set_prevention_codes ON prevention_code_set_prevention_codes.prevention_code_id = prevention_codes.id
          INNER JOIN prevention_code_sets ON (
            prevention_code_sets.id = prevention_code_set_prevention_codes.prevention_code_set_id
            AND prevention_code_sets.alias = ?
          )
        ';

                $args[] = $this->alias;
            }

            $preventionQuery .= '
        WHERE user_id = ?
        AND prevention_data.date BETWEEN ? AND ?
      ';

            $args[] = $user->id;
            $args[] = $this->getStartDate('Y-m-d');
            $args[] = $this->getEndDate('Y-m-d');

            if(($type = $this->getPreventionType()) !== null) {
                $preventionQuery .= '
          AND prevention_codes.type = ?  
        ';

                $args[] = $this->getPreventionType();
            }

            if($this->closingDate) {
                $preventionQuery .= ' AND prevention_data.created_at <= ?';
                $args[] = $this->closingDate;
            }

            if (isset($this->codes)) {
                $codes = implode(', ', $this->codes);
                $preventionQuery .= ' AND prevention_data.code IN ('.$codes.') ';
            }

            $preventionQuery .= '
        ORDER BY prevention_data.date DESC
        LIMIT 1
      ';

            $db = Piranha::getInstance()->getDatabase();
            $db->executeSelect(
                $preventionQuery,
                $args
            );

            if($db->getNumberOfRows() < 1) {
                // hack, I know. But it was the only way I could see to get it done   :(
                if($user->getClient() == "SBMF") {
                    
                    $overridesQuery = "SELECT status FROM compliance_view_status_overrides WHERE (compliance_view_name IN ('blood_pressure', 'glucose', 'total_hdl_ratio', 'bmi', 'bmi_maintain', 'elearning_bp', 'elearning_gc', 'elearning_tc', 'bmi_reduction', 'elearning_bmi'))AND (status in (4,2)) AND (user_id = ?) AND (compliance_program_record_id = 1212)";
                    $overrideArgs = [$user->getId()];
                    $db->executeSelect($overridesQuery, $overrideArgs);

                    $preventionRow = $db->getNextRow();
                    
                    if($db->getNumberOfRows() > 0) {
                        $complianceStatus = new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, null);
                        return $complianceStatus;
                    }
                }
                return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
            } else {
                $preventionRow = $db->getNextRow();
                $complianceStatus = new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, date('m/d/Y', strtotime($preventionRow['date'])));

                return $complianceStatus;
            }
        }
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

    /**
     * Defines a minimum age that a user must be for this view to be required.
     *
     * @param int $age
     * @param string|null $from The date to evaluate age at. If not provided, defaults to $this->getStartDate()
     */
    public function setMinimumAge($age, $from = null)
    {
        $this->minimumAge = $age;
        $this->minimumAgeFrom = is_numeric($from) ? $from : strtotime($from);
    }

    protected $minimumAgeFrom;
    protected $minimumAge;
    protected $closingDate;
    protected $alias;
    protected $codes;
}