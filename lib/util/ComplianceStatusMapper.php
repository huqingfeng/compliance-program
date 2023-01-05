<?php

class ComplianceStatusMapper
{
    public function __construct(array $mappings = array())
    {
        $this->mapping = $this->_getMappings();

        $this->addMappings($mappings);
    }

    /**
     * Adds a mapping.
     *
     * @param int $status
     * @param ComplianceStatusMapping $mapping
     * @return ComplianceStatusMapper this instance
     */
    public function addMapping($status, ComplianceStatusMapping $mapping)
    {
        $this->mapping[$status] = $mapping;

        return $this;
    }

    /**
     * Adds an array of mappings.
     *
     * @param array $mappings
     * @return ComplianceStatusMapper this instance
     */
    public function addMappings(array $mappings)
    {
        foreach($mappings as $status => $mapping) {
            $this->mapping[$status] = $mapping;
        }

        return $this;
    }

    /**
     * Returns all the mappings.
     *
     * @return array
     */
    public function getMappings()
    {
        return $this->mapping;
    }

    /**
     * Returns a specific mapping.
     *
     * @param int $status
     * @return ComplianceStatusMapping|null
     */
    public function getMapping($status)
    {
        return isset($this->mapping[$status]) ? $this->mapping[$status] : null;
    }

    /**
     * Chain call to getMapping($status)->getText()
     *
     * @param int $status
     * @return string
     */
    public function getText($status)
    {
        return isset($this->mapping[$status]) ? $this->mapping[$status]->getText() : null;
    }

    /**
     * Chain call to getMapping($status)->getLight()
     *
     * @param int $status
     * @return string
     */
    public function getLight($status)
    {
        return isset($this->mapping[$status]) ? $this->mapping[$status]->getLight() : null;
    }

    protected function _getMappings()
    {
        return array(
            ComplianceStatus::COMPLIANT           => new ComplianceStatusMapping('Done', '/images/lights/greenlight.gif'),
            ComplianceStatus::PARTIALLY_COMPLIANT => new ComplianceStatusMapping('Partially Done', '/images/lights/yellowlight.gif'),
            ComplianceStatus::NOT_COMPLIANT       => new ComplianceStatusMapping('Not Done', '/images/lights/redlight.gif'),
            ComplianceStatus::NA_COMPLIANT        => new ComplianceStatusMapping('N/A', '/images/lights/whitelight.gif')
        );
    }

    private $mapping;
}