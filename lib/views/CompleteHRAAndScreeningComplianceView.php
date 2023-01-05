<?php
/**
 * A view based on both hra and screening compliance.
 */
class CompleteHRAAndScreeningComplianceView extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    public function getDefaultName()
    {
        return 'complete_screening_hra';
    }

    public function getDefaultReportName()
    {
        return 'Screening Program';
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Complete HRA and Screening' : null;
    }

    public function setScreeningDates($startDate, $endDate)
    {
        $this->screeningStartDate = $startDate;
        $this->screeningEndDate = $endDate;
    }

    public function getStatus(User $user)
    {
        $hraStatus = $this->getHraView()->getStatus($user);
        $hraComment = $hraStatus->getComment();
        $hraConstant = $hraStatus->getStatus();

        $screeningStatus = $this->getScreeningView()->getStatus($user);
        $screeningComment = $screeningStatus->getComment();
        $screeningConstant = $screeningStatus->getStatus();

        $hraId = $hraStatus->getAttribute('id');
        $hraRangeIds = $hraStatus->getAttribute('range_ids');
        $screeningId = $screeningStatus->getAttribute('id');
        $screeningMergeIds = $screeningStatus->getAttribute('merge_ids');
        $screeningRangeIds = $screeningStatus->getAttribute('range_ids');

        if($hraComment && $screeningComment) {
            $comment = "HRA ($hraComment), Screening ($screeningComment)";
        } else if($hraComment) {
            $comment = "HRA ($hraComment)";
        } else if($screeningComment) {
            $comment = "Screening ($screeningComment)";
        } else {
            $comment = null;
        }

        if($hraConstant == ComplianceViewStatus::COMPLIANT && $screeningConstant == ComplianceViewStatus::COMPLIANT) {
            $status = ComplianceStatus::COMPLIANT;
        } else if(count(array_intersect(array($hraConstant, $screeningConstant), array(ComplianceStatus::COMPLIANT, ComplianceStatus::PARTIALLY_COMPLIANT)))) {
            // If any are green or yellow
            $status = ComplianceStatus::PARTIALLY_COMPLIANT;
        } else {
            $status = ComplianceStatus::NOT_COMPLIANT;
        }

        $viewStatus = new ComplianceViewStatus($this, $status, null, $comment);

        if($hraId !== null) {
            $viewStatus->setAttribute('hra_id', $hraId);
            $viewStatus->setAttribute('hra_comment', $hraComment);
        }

        if($hraRangeIds) {
            $viewStatus->setAttribute('hra_range_ids', $hraRangeIds);
        }

        if($screeningId !== null) {
            $viewStatus->setAttribute('screening_id', $screeningId);
            $viewStatus->setAttribute('screening_comment', $screeningComment);
        }

        if($screeningMergeIds !== null) {
            $viewStatus->setAttribute('screening_merge_ids', $screeningMergeIds);
        }

        if($screeningRangeIds !== null) {
            $viewStatus->setAttribute('screening_range_ids', $screeningRangeIds);
        }

        return $viewStatus;
    }

    protected function getHraView()
    {
        $hraView = new CompleteHRAComplianceView($this->getStartDate(), $this->getEndDate());
        $hraView->setComplianceViewGroup($this->getComplianceViewGroup());

        return $hraView;
    }

    protected function getScreeningView()
    {
        $screeningView = new CompleteScreeningComplianceView(
            $this->screeningStartDate ? $this->screeningStartDate : $this->getStartDate(),
            $this->screeningEndDate ? $this->screeningEndDate : $this->getEndDate()
        );

        $screeningView->setComplianceViewGroup($this->getComplianceViewGroup());

        return $screeningView;
    }

    private $screeningStartDate;
    private $screeningEndDate;
}