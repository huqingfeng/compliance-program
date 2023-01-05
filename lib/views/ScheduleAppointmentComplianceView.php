<?php

class ScheduleAppointmentComplianceView extends DateBasedComplianceView
{
    public function __construct($sd, $ed)
    {
        $this->setStartDate($sd);
        $this->setEndDate($ed);
    }

    public function getDefaultStatusSummary($status)
    {
        if($status == ComplianceViewStatus::COMPLIANT) {
            return 'Schedule an appointment';
        } else {
            return null;
        }
    }

    public function getDefaultName()
    {
        return 'schedule_appointment';
    }

    public function getDefaultReportName()
    {
        return 'Schedule Appointment';
    }

    public function setAppointmentTypeId($id)
    {
        $this->typeId = $id;
    }

    public function getStatus(User $user)
    {
        $query = '
      SELECT appointments.date
      FROM appointment_times
      INNER JOIN appointments ON appointments.id = appointment_times.appointmentid
      WHERE user_id = ?
      AND appointments.date BETWEEN ? AND ?
    ';

        $args = array($user->getID(), $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        if($this->typeId) {
            $query .= ' AND appointments.typeid = ?';
            $args[] = $this->typeId;
        }

        $query .= '
      ORDER BY appointments.date DESC
      LIMIT 1
    ';

        $db = Piranha::getInstance()->getDatabase();
        $db->executeSelect($query, $args);

        if($row = $db->getNextRow()) {
            return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, date('m/d/Y', strtotime($row['date'])));
        } else {
            return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
        }
    }

    protected $typeId;
}