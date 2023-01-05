<?php

class CompletePrivateConsultationComplianceView extends DateBasedComplianceView
{
    public function __construct($programStart, $programEnd)
    {
        $this
            ->setStartDate($programStart)
            ->setEndDate($programEnd);
    }

    public function getDefaultStatusSummary($status)
    {
        return $status == ComplianceViewStatus::COMPLIANT ? 'Complete Consultation' : null;
    }

    public function getDefaultName()
    {
        return 'complete_private_consultation';
    }

    public function getDefaultReportName()
    {
        return 'Private Consultation';
    }

    public function getStatus(User $user)
    {
        $consultationQuery = "
      SELECT appointments.date
      FROM appointment_times
      INNER JOIN appointments ON appointments.id = appointment_times.appointmentid
      WHERE user_id = ?
      AND appointments.typeid IN (11, 21)
      AND appointments.date BETWEEN ? AND ?
      AND appointment_times.showed = '1'
      LIMIT 1
    ";

        $_db = Piranha::getInstance()->getDatabase();
        $_db->executeSelect($consultationQuery, $user->getID(), $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        if($consultationRow = $_db->getNextRow()) {
            return new ComplianceViewStatus($this, ComplianceViewStatus::COMPLIANT, null, date('m/d/Y', strtotime($consultationRow['date'])));
        } else {
            return new ComplianceViewStatus($this, ComplianceViewStatus::NOT_COMPLIANT);
        }
    }
}