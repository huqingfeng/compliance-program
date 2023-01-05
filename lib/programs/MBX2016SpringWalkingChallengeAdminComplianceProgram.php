<?php
require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

class MBX2016ParticipateInWalkingChallenge extends DateBasedComplianceView
{
    public function __construct($startDate, $endDate)
    {
        $this->setDateRange($startDate, $endDate);
    }

    public function getDefaultName()
    {
        return 'participate_in_walking_challenge';
    }

    public function getDefaultReportName()
    {
        return 'Participate In Walking Challenge';
    }

    public function getDefaultStatusSummary($status)
    {
        return null;
    }

    public function getStatus(User $user)
    {
        $totalSteps = 0;

        $data = get_all_fitbit_data($user->id, $this->getStartDate('Y-m-d'), $this->getEndDate('Y-m-d'));

        if($data && isset($data['total_steps']) && $data['total_steps'] > 0) {
            $totalSteps = $data['total_steps'];
        }

        $status = new ComplianceViewStatus($this, null, 0);
        $status->setAttribute('total_steps', $totalSteps);

        return $status;
    }
}

class MBX2016ComplianceProgramAdminReportPrinter extends BasicComplianceProgramAdminReportPrinter
{
    protected function showUser(User $user)
    {
        return !$user->expired();
    }
}

class MBX2016SpringWalkingChallengeAdminComplianceProgram extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        $printer = new MBX2016ComplianceProgramAdminReportPrinter();

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

        $view = new MBX2016ParticipateInWalkingChallenge($this->getStartDate(), $this->getEndDate());
        $view->setName('walking_program');
        $view->setReportName('Walking Program');
        $group->addComplianceView($view);

        $this->addComplianceViewGroup($group);
    }
}