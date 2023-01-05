<?php

class CompliancePointStatusMapper
{
    public function __construct($pointsForCompliant = 1, $pointsForPartial = 0.001)
    {
        $this->pointsCompliant = $pointsForCompliant;
        $this->pointsPartial = $pointsForPartial;
    }

    public function getStatus($points)
    {
        if($points >= $this->pointsCompliant) {
            return ComplianceStatus::COMPLIANT;
        } else if($points >= $this->pointsPartial) {
            return ComplianceStatus::PARTIALLY_COMPLIANT;
        } else {
            return ComplianceStatus::NOT_COMPLIANT;
        }
    }

    private $pointsCompliant;
    private $pointsPartial;
}
