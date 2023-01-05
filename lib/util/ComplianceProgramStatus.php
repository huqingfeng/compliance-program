<?php
/**
 * This object holds a number of ComplianceViewGroupStatus objects for a user
 * which represent her status for the program.
 */
class ComplianceProgramStatus extends MappedComplianceStatus
{
    /**
     * Constructs a compliance program status.
     *
     * @param ComplianceProgram $complianceProgram
     * @param User $user
     */
    public function __construct(ComplianceProgram $complianceProgram, User $user)
    {
        parent::__construct($complianceProgram->getComplianceStatusMapper());

        $this->groupStatuses = array();
        $this->program = $complianceProgram;
        $this->user = $user;
    }

    /**
     * @return ComplianceProgram
     */
    public function getComplianceProgram()
    {
        return $this->program;
    }

    public function addComplianceViewGroupStatus(ComplianceViewGroupStatus $status)
    {
        $this->groupStatuses[$status->getComplianceViewGroup()->getName()] = $status;
    }

    public function getComplianceViewGroupStatuses()
    {
        return $this->groupStatuses;
    }

    public function getComplianceViewStatus($name)
    {
        foreach($this->groupStatuses as $groupStatus) {
            if($status = $groupStatus->getComplianceViewStatus($name)) {
                return $status;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return ComplianceViewGroupStatus
     */
    public function getComplianceViewGroupStatus($name)
    {
        return isset($this->groupStatuses[$name]) ? $this->groupStatuses[$name] : null;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function addAttributes(array $attributes)
    {
        foreach($attributes as $name => $value) {
            $this->attributes[$name] = $value;
        }
    }

    public function getAttribute($name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ?
            $this->attributes[$name] : $default;
    }

    private $attributes = array();
    private $groupStatuses = array();
    private $program;
    private $user;
}