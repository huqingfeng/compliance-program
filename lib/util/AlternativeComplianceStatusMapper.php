<?php

class AlternativeComplianceStatusMapper extends ComplianceStatusMapper
{
    public function __construct(array $mappings = array())
    {
        $this->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Criteria Met', '/images/lights/greenlight.gif'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('N/A', '/images/lights/whitelight.gif'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Working on It', '/images/lights/yellowlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Started', '/images/lights/redlight.gif')
        ));
    }
}