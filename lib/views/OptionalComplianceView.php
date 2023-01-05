<?php

class OptionalComplianceView extends ComplianceView
{
    public function __construct(ComplianceView $view)
    {
        $this->view = $view;
    }

    public function setComplianceViewGroup(ComplianceViewGroup $group)
    {
        parent::setComplianceViewGroup($group);
        $this->view->setComplianceViewGroup($group);
    }

    public function getDefaultReportName()
    {
        return $this->view->getDefaultReportName();
    }

    public function getDefaultName()
    {
        return $this->view->getDefaultName();
    }

    public function getDefaultStatusSummary($constant)
    {
        return $this->view->getDefaultStatusSummary($constant);
    }

    public function getStatus(User $user)
    {
        $status = $this->view->getStatus($user);

        if(!$status->isCompliant()) {
            $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        }

        $status->setComplianceView($this);

        return $status;
    }

    public function __call($name, $arguments)
    {
        call_user_func_array(array($this->view, $name), $arguments);
    }

    protected $view;
}

?>
