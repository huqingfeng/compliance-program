<?php
/**
 * Holds status information about a compliance view.
 */
class ComplianceViewStatus extends MappedComplianceStatus
{
    public function __construct(ComplianceView $view, $status = null, $points = null, $comment = null)
    {
        // We will instantiate a default incase this class is used outside of the
        // compliance framework.

        if(($group = $view->getComplianceViewGroup()) && ($program = $group->getComplianceProgram())) {
            $mapper = $program->getComplianceStatusMapper();
        } else {
            $mapper = new ComplianceStatusMapper();
        }

        parent::__construct($mapper, $status, $points, $comment);

        $this->view = $view;
        $this->usingOverride = false;
    }

    /**
     * @return ComplianceView
     */
    public function getComplianceView()
    {
        return $this->view;
    }

    /**
     * @param ComplianceView $view
     * @return ComplianceViewStatus
     */
    public function setComplianceView(ComplianceView $view)
    {
        $this->view = $view;

        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getUsingOverride()
    {
        return $this->usingOverride;
    }

    public function setUsingOverride($boolean)
    {
        $this->usingOverride = $boolean;

        return $this;
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
    private $usingOverride;
    private $view;
}