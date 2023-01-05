<?php

class CompleteCoachingAppointmentComplianceView extends DateBasedComplianceView
{
    public function __construct($programStart, $programEnd, $requiredShowed = 1)
    {
        $this
            ->setStartDate($programStart)
            ->setEndDate($programEnd);
        $this->requiredShowed = $requiredShowed;
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Complete Coaching Appointment' : null;
    }

    public function getDefaultName()
    {
        return 'complete_coaching_appointment';
    }

    public function getDefaultReportName()
    {
        return 'Coaching Appointment';
    }

    public function getStatus(User $user)
    {
        $consultationQuery = "
      SELECT appointments.date
      FROM appointment_times
      INNER JOIN appointments ON appointments.id = appointment_times.appointmentid
      WHERE user_id = ?
      AND appointments.typeid IN (35)
      AND appointments.date BETWEEN ? AND ?
      AND appointment_times.showed = '1'
      LIMIT $this->requiredShowed
    ";

        $_db = Piranha::getInstance()->getDatabase();
        $_db->executeSelect($consultationQuery, $user->getID(), $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        $total = 0;
        while($consultationRow = $_db->getNextRow()) $total++;

        if($total >= $this->requiredShowed) {
            return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT);
        } else {
            return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
        }
    }

    private $requiredShowed;
}