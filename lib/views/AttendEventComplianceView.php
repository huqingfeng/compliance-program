<?php
/**
 * This view evaluates if a user has attended an event.
 */
class AttendEventComplianceView extends DateBasedComplianceView
{
    public function __construct($programStart, $programEnd)
    {
        $this->setStartDate($programStart);
        $this->setEndDate($programEnd);
    }

    /**
     * Will only give credit for these type ids if called.
     *
     * @param array $typeIds
     */
    public function bindTypeIds(array $typeIds)
    {
        $this->typeIds = $typeIds;
    }

    /**
     * Switches the evaluation mode to a point-based view and gives
     * $points for each attendance.
     *
     * @param int|null $points
     */
    public function setPointsPerAttendance($points)
    {
        $this->pointsPer = $points;
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Attend Event' : null;
    }

    public function getDefaultName()
    {
        return 'attend_event';
    }

    public function getDefaultReportName()
    {
        return 'Attend Event';
    }

    public function setNumberRequired($numberRequired)
    {
        $this->numberRequired = $numberRequired;
    }

    public function getStatus(User $user)
    {
        $eventQuery = "
            SELECT event_dates.event_date_id, event_dates.date
            FROM event_dates
            INNER JOIN events ON events.event_id = event_dates.event_id
            LEFT JOIN event_registrations ON event_registrations.event_date_id = event_dates.event_date_id
            LEFT JOIN event_walk_ins ON event_walk_ins.event_date_id = event_dates.event_date_id
            WHERE (
              (event_registrations.user_attended = 1 AND event_registrations.user_id = ?) OR
              event_walk_ins.user_id = ?
            )
            AND event_dates.date BETWEEN ? AND ?
            AND event_registrations.user_attended = 1
        ";

        if($this->typeIds) {
            $eventQuery .= sprintf(
                ' AND events.event_type_id IN %s',
                Formatter::sqlSet($this->typeIds)
            );
        }

        $eventQuery .= ' ORDER BY event_dates.date ASC';

        $args = array(
            $user->id,
            $user->id,
            $this->getStartDate('Y-m-d'),
            $this->getEndDate('Y-m-d')
        );

        if($this->pointsPer === null) {
            $eventQuery .= ' LIMIT ?';
            $args[] = $this->numberRequired;
        }

        $db = new Database();

        $events = array();

        $db->executeSelect($eventQuery, $args);

        while($row = $db->getNextRow()) {
            $events[$row['event_date_id']] = $row['date'];
        }

        $numberOfEvents = count($events);

        if($this->pointsPer === null) {
            if($numberOfEvents == $this->numberRequired) {
                $lastEvent = end($events);

                $status = ComplianceViewStatus::COMPLIANT;
                $comment = date('m/d/Y', strtotime($lastEvent['date']));
            } elseif($numberOfEvents > 0) {
                $status = ComplianceStatus::PARTIALLY_COMPLIANT;
                $comment = null;
            } else {
                $status = ComplianceStatus::NOT_COMPLIANT;
                $comment = null;
            }

            return new ComplianceViewStatus($this, $status, null, $comment);
        } else {
            return new ComplianceViewStatus($this, null, $this->pointsPer * $numberOfEvents);
        }
    }

    private $pointsPer;
    private $numberRequired = 1;
    private $typeIds;
}