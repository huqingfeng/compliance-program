<?php

use hpn\steel\query\SelectQuery;


class GoshenDieCutting2016ComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowUserFields(null, null, true, false, true, false, null, null, false);
        $printer->setShowUserLocation(false);
        $printer->setShowShowRelatedUserFields(false, false, false);
        $printer->setShowUserInsuranceTypes(false, false);
        $printer->setShowCompliant(true, false, false);
        $printer->setShowStatus(false,false,false);
        $printer->setShowPoints(false,false,false);
        $printer->setShowComment(false,false,false);


        $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) {
            $hraStatus = $status->getComplianceViewStatus('hra');
            $screeningStatus  = $status->getComplianceViewStatus('screening');

            return array(
                'Hra Date'          => $hraStatus->getComment(),
                'Screening Date'          => $screeningStatus->getComment(),
            );
        });


        return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $printer = new BasicComplianceProgramReportPrinter();

        return $printer;
    }

    public function hasPartiallyCompliantStatus()
    {
        return true;
    }

    public function loadGroups()
    {
        $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $preventionEventGroup = new ComplianceViewGroup('required', 'Prevention Events');

        $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
        $hraView->setReportName('Health Risk Appraisal (HRA)');
        $hraView->setName('hra');
        $hraView->emptyLinks();
        $preventionEventGroup->addComplianceView($hraView);

        $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
        $screeningView->setReportName('Virtual Wellness Screening');
        $screeningView->setName('screening');
        $screeningView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $screeningDate = SelectQuery::create()
                ->select('date')
                ->from('screening')
                ->where('user_id = ?', array($user->id))
                ->andWhere('date BETWEEN ? AND ?', array($this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d')))
                ->hydrateSingleScalar()
                ->orderBy('date DESC')
                ->execute();

            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                $status->setComment(date('m/d/Y', strtotime($screeningDate)));
            }
        });
        $screeningView->emptyLinks();
        $preventionEventGroup->addComplianceView($screeningView);

        $this->addComplianceViewGroup($preventionEventGroup);

    }

}