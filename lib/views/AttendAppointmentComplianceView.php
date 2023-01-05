<?php
/**
 * This view evaluates if a user has attended an appointment.
 */
class AttendAppointmentComplianceView extends DateBasedComplianceView
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
        return $status == ComplianceViewStatus::COMPLIANT ? 'Complete Consultation' : null;
    }

    public function getDefaultName()
    {
        return 'attend_appointment';
    }

    public function getDefaultReportName()
    {
        return 'Attend Appointment';
    }

    public function setNumberRequired($numberRequired)
    {
        $this->numberRequired = $numberRequired;
    }

    public function getStatus(User $user)
    {
        $consultationQuery = "
          SELECT appointments.date
          FROM appointment_times
          INNER JOIN appointments ON appointments.id = appointment_times.appointmentid
          WHERE user_id = ?
          AND appointments.date BETWEEN ? AND ?
          AND appointment_times.showed = '1'
        ";

        if($this->typeIds) {
            $consultationQuery .= sprintf(
                'AND appointments.typeid IN %s',
                Formatter::sqlSet($this->typeIds)
            );
        }

        $consultationQuery .= '
          ORDER BY appointments.date ASC
          LIMIT ?
        ';

        $db = new Database();

        $appointments = $db->getResultsForQuery($consultationQuery,
            $user->getID(),
            $this->getStartDate('Y-m-d'),
            $this->getEndDate('Y-m-d'),
            $this->numberRequired
        );

        $numberOfAppointments = count($appointments);

        if($this->pointsPer === null) {
            if($numberOfAppointments == $this->numberRequired) {
                $lastAppointment = end($appointments);

                $status = ComplianceViewStatus::COMPLIANT;
                $comment = date('m/d/Y', strtotime($lastAppointment['date']));
            } elseif($numberOfAppointments > 0) {
                $status = ComplianceStatus::PARTIALLY_COMPLIANT;
                $comment = null;
            } else {
                $status = ComplianceStatus::NOT_COMPLIANT;
                $comment = null;
            }

            return new ComplianceViewStatus($this, $status, null, $comment);
        } else {
            return new ComplianceViewStatus($this, null, $this->pointsPer * $numberOfAppointments);
        }
    }

    private $pointsPer;
    private $numberRequired = 1;
    private $typeIds;
}