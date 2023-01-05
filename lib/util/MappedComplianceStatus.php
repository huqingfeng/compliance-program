<?php
/**
 * Binds a ComplianceStatus object with a ComplianceStatusMapper, adding
 * getText() and getLight()
 */
class MappedComplianceStatus extends ComplianceStatus
{
    public function __construct(ComplianceStatusMapper $mapping, $status = null, $points = null, $comment = null)
    {
        parent::__construct($status, $points, $comment);

        $this->mapping = $mapping;
    }

    public function getComplianceStatusMapping()
    {
        return $this->mapping;
    }

    public function getText()
    {
        return $this->mapping->getText($this->getStatus());
    }

    public function getLight()
    {
        return $this->mapping->getLight($this->getStatus());
    }

    private $mapping;
}