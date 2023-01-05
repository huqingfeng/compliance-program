<?php
/**
 * A view based on both hra and screening compliance.
 */
class CompleteHRAAndScreeningSeparateDateComplianceView extends DateBasedComplianceView
{
    public function __construct($hraStartDate, $hraEndDate, $screeningStartDate, $screeningEndDate)
    {
        $this->setHraStartDate($hraStartDate);
        $this->setHraEndDate($hraEndDate);
        $this->setScreeningStartDate($screeningStartDate);
        $this->setScreeningEndDate($screeningEndDate);

        $this->setStartDate($screeningStartDate);
        $this->setEndDate($screeningEndDate);
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
        }

        if($hraRangeIds) {
            $viewStatus->setAttribute('hra_range_ids', $hraRangeIds);
        }

        if($screeningId !== null) {
            $viewStatus->setAttribute('screening_id', $screeningId);
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
        $hraView = new CompleteHRAComplianceView($this->getHraStartDate(), $this->getHraEndDate());
        $hraView->setComplianceViewGroup($this->getComplianceViewGroup());

        return $hraView;
    }

    protected function getScreeningView()
    {
        $screeningView = new CompleteScreeningComplianceView(
            $this->screeningStartDate ? $this->screeningStartDate : $this->getScreeningStartDate(),
            $this->screeningEndDate ? $this->screeningEndDate : $this->getScreeningEndDate()
        );

        $screeningView->setComplianceViewGroup($this->getComplianceViewGroup());

        return $screeningView;
    }

    protected function getScreeningStartDate() {
        return $this->screeningStartDate;
    }

    protected function setScreeningStartDate($value)
    {
        if(is_callable($value)) {
            $this->screeningStartDate = $value;
        } else {
            $this->screeningStartDate = is_numeric($value) ? $value : strtotime($value);

            if($this->screeningStartDate === false) {
                throw new InvalidArgumentException('Invalid date: '.$value);
            }
        }

        return $this;
    }

    protected function getScreeningEndDate() {
        return $this->screeningEndDate;
    }

    protected function setScreeningEndDate($value)
    {
        if(is_callable($value)) {
            $this->screeningEndDate = $value;
        } else {
            $this->screeningEndDate = is_numeric($value) ? $value : strtotime($value);

            if($this->screeningEndDate === false) {
                throw new InvalidArgumentException('Invalid date: '.$value);
            }
        }

        return $this;
    }

    protected function getHraStartDate() {
        return $this->hraStartDate;
    }

    protected function setHraStartDate($value)
    {
        if(is_callable($value)) {
            $this->hraStartDate = $value;
        } else {
            $this->hraStartDate = is_numeric($value) ? $value : strtotime($value);

            if($this->hraStartDate === false) {
                throw new InvalidArgumentException('Invalid date: '.$value);
            }
        }

        return $this;
    }

    protected function getHraEndDate() {
        return $this->hraEndDate;
    }

    protected function setHraEndDate($value)
    {
        if(is_callable($value)) {
            $this->hraEndDate = $value;
        } else {
            $this->hraEndDate = is_numeric($value) ? $value : strtotime($value);

            if($this->hraEndDate === false) {
                throw new InvalidArgumentException('Invalid date: '.$value);
            }
        }

        return $this;
    }

    private $screeningStartDate;
    private $screeningEndDate;
    private $hraStartDate;
    private $hraEndDate;
}