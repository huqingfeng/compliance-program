<?php
/**
 * Holds status information about a ComplianceViewGroup
 */
class ComplianceViewGroupStatus extends MappedComplianceStatus
{
    public function __construct(ComplianceViewGroup $group, $status = null, $points = null, $comment = null)
    {
        parent::__construct($group->getComplianceProgram()->getComplianceStatusMapper(), $status, $points, $comment);

        $this->viewGroup = $group;
        $this->viewStatuses = array();
    }

    /**
     * @return ComplianceViewGroup
     */
    public function getComplianceViewGroup()
    {
        return $this->viewGroup;
    }

    /**
     * @param ComplianceViewStatus $status
     * @return ComplianceViewGroupStatus
     */
    public function addComplianceViewStatus(ComplianceViewStatus $status)
    {
        $this->viewStatuses[$status->getComplianceView()->getName()] = $status;

        return $this;
    }

    /**
     * Returns all the compliance view statuses in this group status.
     *
     * @return array
     */
    public function getComplianceViewStatuses()
    {
        return $this->viewStatuses;
    }

    /**
     * Gets a specific view status in this group status according to name.
     *
     * @param string $name
     * @return ComplianceViewStatus|null
     */
    public function getComplianceViewStatus($name)
    {
        return isset($this->viewStatuses[$name]) ? $this->viewStatuses[$name] : null;
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
    private $viewGroup;
    private $viewStatuses;
}