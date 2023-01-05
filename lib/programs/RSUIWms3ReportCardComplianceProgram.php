<?php
use hpn\common\text\Escaper;
use hpn\steel\query\SelectQuery;

class RSUIWMS3ReportCard extends ComplianceProgram
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
            $output = "ajax&value=" . $_GET['value'] . "&item=" . $_GET['item'] . "&action=" . $_GET['action'];
        } elseif ($value == "report") {
            $output = "report";
        } else {
            $output = "view";
        }
        return $output;
    }

    public function getProgramReportPrinter($preferredPrinter = null)
    {
        global $_user;

        // CHP Admins: Beckie Quandee, Brenda Tower, Mickey Sommers, Kathy Williams, Tammy Hoersten
        // Janna Siemon, Linda Bueche, Marsha Wittebols, Steve Husk, Stephen Gray, Matthew Grimm, Tricia Acton
        // Andy Sommers, Rachel Heagle, Jennifer Pletch, Christina Husk, Sandy Husk
        // RSUI Admins: Alejandra Garofalo, ErikaN Ervin, StacyA Manning
        $admins = [2617550,2686795,2617566,2617558,2989775,2989790,2731003,2989808,2617526,2617563,2884251,2716329,3045740,2617542,2617565,2691239,2617553,2918102,2779021,3574034];

        if (!empty($_GET['wms1Id'])) {
            $wms1Id = $_GET['wms1Id'];
            $wms2Id = $_GET['wms2Id'] ?? $_user->wms2_user_id;
            $wms2Account = $_GET['wms2Account'] ?? $_user->wms2_account_id;
        } else {
            $wms1Id = $_user->id;
            $wms2Id = $_user->wms2_user_id;
            $wms2Account = $_user->wms2_account_id;
        }

        $program = ($_GET['id']) ?? "1206";
        $year = ($_GET['year']) ?? "2019";
        $view = ($_GET['view']) ?? "view";

        if (($view == "admin" || $view == "report") && !in_array($_user->id,$admins)) {
            header('HTTP/1.0 403 Forbidden');
            echo "<h2 style='font-size: 18pt; color: red; text-align: center'><i style='font-size: 6rem; margin-bottom: 20px;' class='fa fa-exclamation-triangle'></i><br>Access Denied, Administration Credentials Required</h2>";
            return;
        }

        $view = $this->adminTemplate($view);
        $token = "7bc9b94d-8a55-410c-bec8-4787d02c0e7b";

        $domain = $this->getDomain();

        $url = $domain."wms3/public/rsui?bypass=true&token=".$token."&id=".$program."&wms1Id=".$wms1Id."&wms2Id=".$wms2Id."&wms2Account=".$wms2Account."&view=".$view."&year=".$year;
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
