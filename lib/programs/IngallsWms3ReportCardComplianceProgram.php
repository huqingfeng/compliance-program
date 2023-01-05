<?php
use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class IngallsWMS3ReportCard extends ComplianceProgram
{
    public function getAdminProgramReportPrinter()
    {
        return ;
    }

    public function loadGroups()
    {
        ini_set('memory_limit', '3000M');
    }

    public function loadSessionParameters()
    {
        // get user's data needing for redirect querystring back to wms3 page on wms2
        if (isset($_GET['loopback']) && $_GET['loopback'] == "wms3IngallsReportCard"){
            $url = "/compliance/ingalls-2018-2019/well-rewards/compliance_programs?id=1361";
            $url .= "&view=".$_GET['view'];
            $url .= "&newHire=".$_GET['newHire'];
            $url .= "&lastUpdated=".$_GET['lastUpdated'];
        } else {
            $url = "/compliance_programs?id=1361";
            if (isset($_GET['view'])) $url .= "&view=".$_GET['view'];
            $url .= "&newHire=".$_GET['newHire'];
            $url .= "&lastUpdated=".$_GET['lastUpdated'];
        }

        $_SESSION['manua_override_fitbit_parameters'] = array(
            'activity_id' => '509',
            'question_id' => '110',
            'start_date' => '2016-03-14',
            'end_date' => '2016-06-12',
            'product_name'  => 'Total Steps',
            'header_text'  => '<p><a href="'.$url.'">Back to My Dashboard</a></p>',
            'override' => 0
        );
    }

    public function getTemplate($value)
    {
        if ($value == "admin" && isset($_GET['editUserId'])) {
            $output = "admin&editUserId=". $_GET['editUserId'];
        } elseif ($value == "ajax") {
            $output = "ajax&action=" . $_GET['action'] . "&value=". $_GET['value'] . "&category=". $_GET['category'];
        } elseif ($value == "adminreport") {
            $output = "adminreport";
        } else {
            $output = "ingalls";
        }
        return $output;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        $additional = "";

        if (empty($_GET['wms1Id'])) {
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

        if (isset($_GET['lastUpdated'])) {
            $additional .= "&lastUpdated=" . $_GET['lastUpdated'];
        } else {
            $additional .= "&lastUpdated=0";
        }

        if (!empty($_GET['newHire'])) {
            $additional .= "&newHire=true";
        }

        $view = $_GET['view'] ?? "ingalls";
        $view = $this->getTemplate($view);

        $domain = $this->getDomain();
        $url = $domain."wms3/public/ingalls?bypass=true&id=".$_GET['id']."&wms1Id=".$wms1Id."&wms2Id=".$wms2Id."&wms2Account=".$wms2Account."&view=".$view.$additional;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        echo $result;
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
