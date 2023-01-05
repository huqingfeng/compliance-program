<?php

class ProvenaTobaccoFree extends ComplianceView
{
    public function __construct($name = 'provena_tobacco_free')
    {
        $this->name = $name;
    }

    public function getStatus(User $user)
    {
        $status = ($record = $user->getNewestDataRecord($this->name)) &&
            $record->getDataFieldValue('compliant') ?

            ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT;

        return new ComplianceViewStatus($this, $status);
    }

    public function getDefaultName()
    {
        return 'tobacco';
    }

    public function getDefaultReportName()
    {
        return 'Tobacco';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    private $name;
}
