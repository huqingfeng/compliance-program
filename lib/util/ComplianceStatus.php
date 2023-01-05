<?php

class ComplianceStatus
{
    const COMPLIANT = 4;
    const NA_COMPLIANT = 3;
    const PARTIALLY_COMPLIANT = 2;
    const NOT_COMPLIANT = 1;

    public static function get($mapped = false)
    {
        $status = array(
            self::COMPLIANT           => 'Compliant',
            self::NA_COMPLIANT        => 'N/A (Compliant)',
            self::PARTIALLY_COMPLIANT => 'Partially Compliant',
            self::NOT_COMPLIANT       => 'Not Compliant'
        );

        return $mapped ? $status : array_keys($status);
    }

    public function __construct($status = null, $points = null, $comment = null)
    {
        $this->status = $status;
        $this->comment = $comment;
        $this->points = $points;

        $this->pointStatusMapper = null;
        $this->statusPointMapper = null;
    }

    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     * @return ComplianceStatus
     */
    public function setComment($comment = null)
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPoints()
    {
        if($this->points === null) {
            return $this->status === null || $this->statusPointMapper === null ?
                null : $this->statusPointMapper->getPoints($this->status);
        }

        return $this->points;
    }

    /**
     * @param float|null $points
     * @return ComplianceStatus
     */
    public function setPoints($points = null)
    {
        $this->points = $points;

        return $this;
    }

    public function getStatus()
    {
        if($this->status === null) {
            return $this->points === null || $this->pointStatusMapper === null ?
                null : $this->pointStatusMapper->getStatus($this->points);
        }

        return $this->status;
    }

    /**
     * @param int|null $status
     * @return ComplianceStatus
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCompliant()
    {
        return in_array($this->getStatus(), array(ComplianceStatus::COMPLIANT, ComplianceStatus::NA_COMPLIANT));
    }

    /**
     * @param ComplianceStatusPointMapper $p
     * @return ComplianceStatus
     */
    public function setStatusPointMapper(ComplianceStatusPointMapper $p)
    {
        $this->statusPointMapper = $p;

        return $this;
    }

    /**
     * @param CompliancePointStatusMapper $p
     * @return ComplianceStatus
     */
    public function setPointStatusMapper(CompliancePointStatusMapper $p)
    {
        $this->pointStatusMapper = $p;

        return $this;
    }

    public function hasStatusPointMapper()
    {
        return $this->statusPointMapper != null;
    }

    public function hasPointStatusMapper()
    {
        return $this->pointStatusMapper != null;
    }

    protected $comment;
    protected $points;
    protected $status;
    protected $statusPointMapper;
    protected $pointStatusMapper;
}