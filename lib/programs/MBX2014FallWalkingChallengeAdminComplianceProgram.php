<?php

class MBX2014FallWalkingChallengeAdminComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new BasicComplianceProgramAdminReportPrinter();

        $printer->setShowStatus(false, false, false);
        $printer->setShowComment(false, false, false);
        $printer->setShowPoints(false, false, false);

        $printer->addStatusFieldCallback('Steps', function(ComplianceProgramStatus $status) {
            return $status->getComplianceViewStatus('walking_program')->getAttribute('total_steps');
        });

        return $printer;
    }

    public function loadGroups()
    {
        $group = new ComplianceViewGroup('Walking Program');

        $view = new HmiParticipateInWalkingChallenge(
            $this->getStartDate(), $this->getEndDate(), 'mbx_fall_walking_program', 0, false
        );

        $view->setName('walking_program');
        $view->setReportName('Walking Program');
        $group->addComplianceView($view);

        $this->addComplianceViewGroup($group);
    }
}