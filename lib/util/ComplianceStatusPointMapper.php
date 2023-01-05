<?php

class ComplianceStatusPointMapper
{
    public function __construct($compliantPoints = 1, $partialPoints = 0, $naPoints = 1, $notCompliantPoints = 0)
    {
        $this->mapping = array(
            ComplianceStatus::COMPLIANT           => $compliantPoints,
            ComplianceStatus::PARTIALLY_COMPLIANT => $partialPoints,
            ComplianceStatus::NA_COMPLIANT        => $naPoints,
            ComplianceStatus::NOT_COMPLIANT       => $notCompliantPoints
        );
    }

    public function getMaximumNumberOfPoints()
    {
        return max($this->mapping);
    }

    public function getPoints($status)
    {
        return isset($this->mapping[$status]) ? $this->mapping[$status] : null;
    }

    private $mapping;
}