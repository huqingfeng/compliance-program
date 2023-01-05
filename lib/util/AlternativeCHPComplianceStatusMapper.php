<?php

class AlternativeCHPComplianceStatusMapper extends ComplianceStatusMapper
{
    public function __construct(array $mappings = array())
    {
        parent::__construct($mappings);

        $this->addMappings(array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Done', '/images/shape/lights/done.jpg'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('Not Required', '/images/shape/lights/notrequired.jpg'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Not Done Yet', '/images/shape/lights/notdoneyet.jpg'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done Yet', '/images/shape/lights/notdoneyet.jpg')
        ));
    }
}