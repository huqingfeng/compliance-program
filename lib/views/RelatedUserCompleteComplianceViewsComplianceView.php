<?php
/**
 * Evaluates given views from the perspective of a related user, such as a
 * spouse. Only status-based views are supported.
 */
class RelatedUserCompleteComplianceViewsComplianceView extends ComplianceView
{
    public function __construct(ComplianceProgram $program, array $viewNames, array $relationshipTypes = array(Relationship::SPOUSE))
    {
        $this->viewNames = $viewNames;
        $this->program = $program;
        $this->relationshipTypes = $relationshipTypes;
    }

    public function getDefaultName()
    {
        return 'spouse_complete';
    }

    public function getDefaultReportName()
    {
        return 'Spouse Status';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function setPointsPerCompletion($points, $isScaleFactor = false)
    {
        $this->pointsPerCompletion = $points;
        $this->isScaleFactor = $isScaleFactor;
    }

    public function getStatus(User $user)
    {
        $relationshipUsers = array();

        if($user->relationship_user_id && !$user->relationshipUser->expired()) {
            $relationshipUsers[] = $user->relationshipUser;
        }

        foreach($user->relationshipUsers as $relatedUser) {
            if(!$relatedUser->expired()) {
                $relationshipUsers[] = $relatedUser;
            }
        }

        $points = 0;

        foreach($relationshipUsers as $relatedUser) {
            if(in_array($relatedUser->relationship_type, $this->relationshipTypes)) {
                $compliant = true;
                $partial = false;

                $spouseViewStatuses = array();
                $viewStatusPoints = 0;

                foreach($this->viewNames as $viewName) {
                    $view = $this->program->getComplianceView($viewName);

                    $viewStatus = $view->getMappedStatus($relatedUser);

                    switch($viewStatus->getStatus()) {
                        case ComplianceStatus::COMPLIANT:
                            $partial = true;

                            break;

                        case ComplianceStatus::PARTIALLY_COMPLIANT:
                            $partial = true;
                            $compliant = false;

                            break;

                        case ComplianceStatus::NOT_COMPLIANT:
                            $compliant = false;

                            break;
                    }

                    $spouseViewStatuses[$viewName] = $viewStatus;
                    $viewStatusPoints += $viewStatus->getPoints();
                }

                if($this->pointsPerCompletion !== null) {
                    if($this->isScaleFactor) {
                        $points += $viewStatusPoints * $this->pointsPerCompletion;
                    } elseif ($compliant) {
                        $points += $this->pointsPerCompletion;
                    }
                } else {
                    $status = new ComplianceViewStatus(
                        $this,
                        $compliant ? ComplianceStatus::COMPLIANT : (
                        $partial ? ComplianceStatus::PARTIALLY_COMPLIANT : ComplianceStatus::NOT_COMPLIANT
                        )
                    );

                    $status->setAttribute('compliance_view_statuses', $spouseViewStatuses);

                    return $status;
                }
            }
        }

        if($this->pointsPerCompletion) {
            return new ComplianceViewStatus($this, null, $points);
        } else {
            return new ComplianceViewStatus($this, ComplianceStatus::NA_COMPLIANT);
        }
    }

    private $viewNames;
    private $program;
    private $relationshipTypes;
    private $pointsPerCompletion;
}