<?php
/**
 * Defines a specific type of ComplianceView where status is based on a
 * start and end date.
 */
abstract class DateBasedComplianceView extends ComplianceView
{
    public function getStartDate($format = 'U')
    {
        $group = $this->getComplianceViewGroup();
        $program = $group ? $group->getComplianceProgram() : null;
        $user = $program ? $program->getActiveUser() : null;

        if($user && ($override = $this->getEligibleOverride($user)) && $override->getNewStartDate() !== null) {
            $date = $override->getDateTimeObject('new_start_date')->format('U');
        } else if(is_callable($this->startDate)) {
            $function = $this->startDate;
            $date = $function('U', $user);
        } else {
            $date = $this->startDate;
        }

        return $date === null ? null : date($format, $date);
    }

    public function getEndDate($format = 'U')
    {
        $group = $this->getComplianceViewGroup();
        $program = $group ? $group->getComplianceProgram() : null;
        $user = $program ? $program->getActiveUser() : null;

        if($user && ($override = $this->getEligibleOverride($user)) && $override->getNewEndDate() !== null) {
            $date = $override->getDateTimeObject('new_end_date')->format('U');
        } else if(is_callable($this->endDate)) {
            $date = call_user_func($this->endDate, 'U', $user);
        } else {
            $date = $this->endDate;
        }

        return $date === null ? null : date($format, $date);
    }

    public function getStartDateTime()
    {
        return new DateTime('@'.$this->getStartDate('U'));
    }

    public function getEndDateTime()
    {
        return new DateTime('@'.$this->getEndDate('U'));
    }

    /**
     * @param User $user
     * @return ComplianceViewStatus
     */
    public function getMappedStatus(User $user)
    {
        if(!$this->allowOptionalDates && ($this->startDate === null || $this->endDate === null)) {
            throw new MenagerieException('Dates not set.');
        }

        return parent::getMappedStatus($user);
    }

    /**
     * Sets the start date that will be used to evaluate the start of this
     * view. This can be a unix timestamp, something parseable by strtotime,
     * or a callback.
     *
     * @param mixed $value If given a callback, it will be called with param ($format, $user = null)
     * @return DateBasedComplianceView This instance
     */
    public function setStartDate($value)
    {
        if(is_callable($value)) {
            $this->startDate = $value;
        } else {
            $this->startDate = is_numeric($value) ? $value : strtotime($value);

            if($this->startDate === false) {
                throw new InvalidArgumentException('Invalid date: '.$value);
            }
        }

        return $this;
    }

    /**
     * Sets the end date that will be used to evaluate the end of this
     * view, unless a callback is given. This can be a unix timestamp,
     * something parseable by strtotime, or a callback.
     *
     * @param mixed $value If given a callback, it will be called with param ($format, $user = null)
     * @return DateBasedComplianceView This instance
     */
    public function setEndDate($value)
    {
        if(is_callable($value)) {
            $this->endDate = $value;
        } else {
            $this->endDate = is_numeric($value) ? $value : strtotime($value);

            if($this->endDate === false) {
                throw new InvalidArgumentException('Invalid date: '.$value);
            }
        }

        return $this;
    }

    public function setDateRange($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    /**
     * If set to true, this view doesn't require dates, but may use the
     * data if present.
     *
     * @param boolean $boolean
     * @return DateBasedComplianceView This instance
     */
    public function setAllowOptionalDates($boolean)
    {
        $this->allowOptionalDates = $boolean;

        return $this;
    }

    protected function getStartDateGetter()
    {
        $bp = $this;

        return function ($format, $user = null) use ($bp) {
            return $bp->getStartDate($format);
        };
    }

    protected function getEndDateGetter()
    {
        $bp = $this;

        return function ($format, $user = null) use ($bp) {
            return $bp->getEndDate($format);
        };
    }

    public function allowPointsOverride()
    {
        return $this->allowPointsOverride === null ?
            parent::allowPointsOverride() : $this->allowPointsOverride;
    }

    public function setAllowPointsOverride($boolean)
    {
        $this->allowPointsOverride = $boolean;
    }

    private $allowPointsOverride = null;
    protected $startDate;
    protected $endDate;
    protected $allowOptionalDates = false;
}