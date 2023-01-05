<?php

class OnlineLightsProgramAdminReportPrinter implements ComplianceProgramAdminReportPrinter
{
    public function printAdminReport(ComplianceProgramReport $report, $output)
    {

        fwrite($output, $this->returnJavaScript());

        ob_start();
        ?>
    <style type="text/css">
        #online_lights_program_admin_report_printer,
        .dataTables_scroll table,
        .FixedColumns_Cloned {
            border-collapse:collapse;
        }

        #online_lights_program_admin_report_printer tr.even,
        #online_lights_program_admin_report_printer tr.even td,
        .FixedColumns_Cloned tr.even,
        .FixedColumns_Cloned tr.even td {
            background-color:#FFFFFF;
        }

        #online_lights_program_admin_report_printer tr.odd,
        #online_lights_program_admin_report_printer tr.odd td,
        .FixedColumns_Cloned tr.odd,
        .FixedColumns_Cloned tr.odd td {
            background-color:#EDEDED;
        }

        #online_lights_program_admin_report_printer .light {
            width:18px;
        }

        .dataTables_scrollFoot .FixedColumns_Cloned tfoot {
            display:none;
        }

        .dataTables_wrapper .ui-toolbar {
            padding:5px;
        }

        .dataTables_wrapper .dataTables_info {
            width:60%;
            float:left;
        }

        .dataTables_wrapper .DataTables_sort_wrapper {
            cursor:default;
        }

        .dataTables_wrapper .DataTables_sort_wrapper .ui-icon {
            float:left;
        }
    </style>

    <p>Please be patient. Because of the size of the table the script will take a while to convert the table to a clean
        display with some nice, added, features.</p>
    <?php
        fwrite($output, ob_get_clean());

        fwrite($output, '<table id="online_lights_program_admin_report_printer">');

        $i = 0;

        $totals = array('Total Compliant', null, null, null, null);

        foreach($report as $status) {
            $user = $status->getUser();

            $totalsIndex = 5;
            $userData['first_name'] = $user->getFirstName();
            $userData['last_name'] = $user->getLastName();
            $userData['date_of_birth'] = $user->getDateOfBirth();
            $userData['insurance_plan_type'] = $user->getInsurancePlanType();
            $userData['insurance_type'] = $user->getInsurancetype();

            $groupNumber = 1;
            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                $groupKey = $groupNumber.'. '.$groupStatus->getComplianceViewGroup()->getReportName();

                $userData[$groupKey] = $this->getLight($groupStatus->getLight());

                if(!isset($totals[$totalsIndex])) $totals[$totalsIndex] = 0;
                if($groupStatus->isCompliant()) $totals[$totalsIndex]++;

                $viewNumber = 1;
                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $totalsIndex++;
                    $viewKey = $groupNumber.'.'.$viewNumber.'. '.$viewStatus->getComplianceView()->getReportName();
                    $userData[$viewKey] = $this->getLight($viewStatus->getLight());
                    if(!isset($totals[$totalsIndex])) $totals[$totalsIndex] = 0;
                    if($viewStatus->isCompliant()) $totals[$totalsIndex]++;
                    $viewNumber++;
                }
                $groupNumber++;
                $totalsIndex++;
            }

            $totalsIndex++;
            if(!isset($totals[$totalsIndex])) $totals[$totalsIndex] = 0;
            if($status->isCompliant()) $totals[$totalsIndex]++;
            $userData['Compliance Program'] = $this->getLight($status->getLight());

            $this->printRow($output, $userData, $i++ == 0);
        }

        // Hack together percentages..
        foreach($totals as $key => $row) {
            if(is_numeric($row)) $totals[$key] = $row.' ('.round($row / $i * 100, 2).'%)';
        }


        fwrite($output, '</tbody><tfoot>');
        $this->printRow($output, $totals, false, 'lastRow');
        fwrite($output, '</tfoot></table>');
    }

    private function getLight($lightImage)
    {
        return '<img src="'.$lightImage.'" alt="" class="light" />';
    }

    private function printRow($output, $data, $header = false, $rowCSS = '')
    {
        if($header) {
            fwrite($output, '<thead><tr>');
            foreach(array_keys($data) as $value) {
                fwrite($output, '<th>'.$value.'</th>');
            }
            fwrite($output, '</tr></thead><tbody>');
        }

        fwrite($output, '<tr class="'.$rowCSS.'">');
        foreach($data as $value) {
            fwrite($output, '<td>'.$value.'</td>');
        }
        fwrite($output, '</tr>');
    }

    private function returnJavaScript()
    {
        use_asset_bundle('jQuery');
        use_asset_bundle('jQueryUI');
        use_asset_bundle('DataTablesFixedColumns');

        ob_start();
        include_javascripts();
        include_stylesheets();
        ?>
    <script type="text/javascript">
        $(document).ready(function () {
            $table = $("#online_lights_program_admin_report_printer");
            oTable = $table.dataTable({
                "bJQueryUI":true,
                "bPaginate":false,
                "sDom":'<"H"fr>t<"F"i>',
                "sScrollY":"350",
                "sScrollX":"100%",
                "oLanguage":{"sSearch":'Filter :'}
            });

            new FixedColumns(oTable, {
                "columns":3
            });
        });
    </script>
    <?php
        return ob_get_clean();
    }
}