<?php

class RequiredInformationEnteredComplianceView extends ComplianceView
{
    public function __construct()
    {
        $this->addLink(new Link('Enter/Update Info', '/my_account'));
    }

    public function getStatus(User $user)
    {
        return new ComplianceViewStatus($this,
            $user->requiredInformationEntered() ? ComplianceStatus::COMPLIANT : ComplianceStatus::NOT_COMPLIANT
        );
    }

    public function getDefaultName()
    {
        return 'enter_required_information';
    }

    public function getDefaultReportName()
    {
        return 'Enter Required Information';
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Have Required Information Entered' : null;
    }
}