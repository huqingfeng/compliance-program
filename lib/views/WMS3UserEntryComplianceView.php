<?php

class WMS3UserEntryComplianceView extends ComplianceView
{
    public function __construct($programName, $name, $startDate = null, $endDate = null, $pointsAwarded = 1, $maximumPoints = null, $pointCap = true, $default_status = ComplianceStatus::NOT_COMPLIANT)
    {
        if (empty($programName) || empty($name)) {
            echo "ERROR: Program Name and Item Name not set!";
        } else {
            $this->entryId = $programName . ":" . $name;
            $this->setName($name);
            $this->programName = $programName;
        }

        if (!empty($startDate)){
            $this->startDate = is_numeric($startDate) ? date('Y-m-d', $startDate) : $startDate;
        } else {
            $this->startDate = date('Y-01-01');
        }

        if (!empty($endDate)){
            $this->endDate = is_numeric($endDate) ? date('Y-m-d', $endDate) : $endDate;
        } else {
            $this->endDate = date('Y-12-31');
        }

        $this->pointsAwarded = $pointsAwarded;
        $this->setMaximumNumberOfPoints($maximumPoints);
        $this->pointCap = $pointCap;
        
        $this->status = $default_status;
    }

    public function allowPointsOverride()
    {
        return $this->allowPointsOverride === null ?
            parent::allowPointsOverride() : $this->allowPointsOverride;
    }

    public function getDefaultName()
    {
        return 'item_'.$this->getReportName();
    }

    public function getDefaultReportName()
    {
        return 'Report Card Item';
    }

    public function getStatus(User $user)
    {

        if (!empty($this->entryId)) {
            $dataQuery = '
                SELECT id, entry, points_awarded, value, activity_date, activity_id, comments
                FROM wms3.reportcard_user_entries
                WHERE user_id = ?
                AND activity_date BETWEEN ? AND ?
                AND deleted = 0
                AND entry = ?
                ORDER BY activity_date ASC
            ';

            $db = Database::getDatabase();

            $this->results = $db->getResultsForQuery(
                $dataQuery,
                $user->getID(),
                $this->startDate,
                $this->endDate,
                $this->entryId
            );

            $this->points = 0;
            $this->comment = '';

            foreach ($this->results as $key => $entry) {
                $this->points += $entry['points_awarded'];
                if ($key > 0) $this->comment .= ", ";
                $this->comment .= $entry['activity_date'].":".$entry['comments'];
            }

            $maxPoints = $this->getMaximumNumberOfPoints();

            if ($this->points >= $maxPoints) {
                $this->status = ComplianceStatus::COMPLIANT;
                if ($this->pointCap) $this->points = $maxPoints;
            }
        } 

        return new ComplianceViewStatus($this, $this->status, $this->points);
    }

    public function createModalView() {

        $link_text = $this->getAttribute('link_text') ?? 'Log Activity';
        $modal_title = $this->getAttribute('modal_title') ?? 'Log Activity';
        $modal_activities = $this->getAttribute('modal_activities') ?? null;
        $modal_placeholder = $this->getAttribute('modal_placeholder') ?? 'Activity Type';

        $currentDay = date("Y-m-d");
        if ($currentDay < $this->startDate) $currentDay = $this->startDate;
        if ($currentDay > $this->endDate) $currentDay = $this->endDate;

        $link = '<a modal_id="'.$this->entryId.'">'.$link_text.'</a>';
        $modal = '<div modal_id="'.$this->entryId.'" class="modal_data" >';
        $modal .= '<h3>'.$modal_title.'</h3>';
        $modal .= '<i class="fa fa-times close red"></i>';
        $modal .= '<p>Log new entry:</p>';
        $modal .= '<div class="data_entry" entry_key="'.$this->entryId.'" points_awarded="'.$this->pointsAwarded.'">';
        $modal .= '    <input type="date" name="log_date" value="'.$currentDay.'" min="'.$this->startDate.'" max="'.$this->endDate.'" >';
        if (!empty($modal_activities)) {
            if (is_array($modal_activities)) {
                $modal .= '<select class="grow" name="comments">';
                foreach($modal_activities as $activity) {
                    $modal .= '<option value="'.$activity.'">'.$activity.'</option>';
                }
                $modal .= '</select>';
            } else {
                $modal .= '<input class="disabled grow" type="text" name="comments" value="'.$modal_activities.'">';
            }

        } else {
            $modal .= '<input class="grow" type="text" name="comments" placeholder="'.$modal_placeholder.'">';
        }
        $modal .= '    <button class="btn btn-primary">Submit Entry</button>';
        $modal .= '</div>';
        $modal .= '<span class="red">*By clicking submit entry, I certify I did complete this activity and can provide proof if requested.</span>';
        $modal .= '<p>Previous Entries:</p>';
        foreach ($this->results as $key => $entry) {
            $modal .= '<div class="user_entry" entry_id="'.$entry['id'].'">';
            $modal .= '<span class="delete"><i class="fa fa-times red"></i></span>';
            $modal .= '<span class="name">';
            $modal .= $entry['activity_date'] . ": ";
            $modal .= '</span>';
            $modal .= '<span class="comment">';
            $modal .= $entry['comments'];
            $modal .= '</span>';
            $modal .= '</div>';
        }
        if (empty($this->results)) $modal .= '<div class="none">None</div>';
        $modal .= '</div>';
        return $link . $modal;
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function setAllowPointsOverride($boolean)
    {
        $this->allowPointsOverride = $boolean;
    }

    private $allowPointsOverride = null;
    private $status;
    private $points;
    private $pointsAwarded;
    private $entryId;
    private $startDate;
    private $endDate;
    private $programName;
}
