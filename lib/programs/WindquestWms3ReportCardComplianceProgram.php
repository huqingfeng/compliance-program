<?php
use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class WindquestWMS3ReportCard extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        return ;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '3000M');
    }

    public function adminTemplate($value)
    {
        if ($value == "admin") {
            $output = "admin";
        } elseif ($value == "ajax") {
            $output = "ajax&full_name=" . $_GET['full_name'] . "&affidavit=" . $_GET['affidavit']. "&hra_override="
                . $_GET['hra_override'] . "&screening_override=" . $_GET['screening_override'] . "&override=" . $_GET['override'];
        } elseif ($value == "report") {
            $output = "report";
        } elseif ($value == "affidavit") {
            $output = "affidavit";
        } else {
            $output = "view";
        }
        return $output;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {

        $view = $this->adminTemplate($_GET['view']);
        $additional = "";

        if (!empty($_GET['update'])) {
            $additional .= "&update=true";
        }
        if (!empty($_GET['db'])) {
            $user = $this->getActiveUser();
            $wms1Id = $user->id;
            $wms2Id = $user->wms2_user_id;
            $wms2Account = $user->wms2_account_id;
            $additional .= "&db=true";
        } else {
            $wms1Id = $_GET['wms1Id'];
            $wms2Id = $_GET['wms2Id'];
            $wms2Account = $_GET['wms2Account'];
        }

        $domain = $this->getDomain();
        $url = $domain."wms3/public/windquest?bypass=true&id=".$_GET['id']."&wms1Id=".$wms1Id."&wms2Id=".$wms2Id."&wms2Account=".$wms2Account."&view=".$view.$additional;
        if(isset($_GET['display_list'])) {
            $url.='&display_list=true';
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        echo $result;

        return ;
    }
}

class RSUIWMS3ReportCardReportPrinter extends BasicComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $fixGroupName = function($name) use($status) {
            $group = $status->getComplianceViewGroupStatus($name)->getComplianceViewGroup();

            $group->setReportName(sprintf(
                $group->getReportName(),
                $status->getComplianceProgram()->getDisplayableEndDate('m/d/Y', $status->getUser())
            ));
        };

        $fixGroupName('core');
        $fixGroupName('numbers');

        parent::printReport($status);
    }

    protected function printCustomRows($status)
    {
    }

    public function __construct()
    {
    }

    public function printHeader(ComplianceProgramStatus $status)
    {

    }
}
