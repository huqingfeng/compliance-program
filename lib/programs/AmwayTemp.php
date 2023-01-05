<?php

class AmwayTemp extends Amway20102011ComplianceProgram
{
    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new AmwayTempPrinter();
    }

    public function preQuery(Doctrine_Query $query, $withViews = true)
    {
        parent::preQuery($query, $withViews);

        $this->getComplianceProgramRecord()->id = 92;
        sfConfig::set('symfony.view.compliance_programs_index_layout', 'minimal');
        sfConfig::set('app_combine_assets', false);
    }
}

class AmwayTempPrinter implements ComplianceProgramReportPrinter
{
    private function printTableRows(ComplianceProgramStatus $status)
    {
        foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
            foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                $this->printTableRow($viewStatus);
            }
        }
    }

    private function getViewName(ComplianceView $view)
    {
        $viewName = '<span>'.$view->getReportName().'</span>';

        if($view->getOptional()) {
            $viewName .= '<span class="notRequired">(Not Required)</span>';
        }

        return $viewName;
    }

    private function printTableRow(ComplianceViewStatus $status)
    {
        $view = $status->getComplianceView();
        ?>
    <tr>
        <td class="resource"><?php echo $this->getViewName($view); ?></td>
        <td class="phipstatus">
            <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
            <?php
            if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
                echo "<br/>Date Completed:<br/>".$status->getComment();
            } else if($status->getStatus() == ComplianceViewStatus::NA_COMPLIANT) {
                echo "<br/>N/A";
            }
            ?>
        </td>
        <td class="moreInfo">
            <?php
            $i = 0;
            foreach($view->getLinks() as $link) {
                echo $i++ > 0 ? ', ' : ' ';
                echo $link;
            }
            ?>
        </td>
        <td class="exemption">
            <?php if(!$status->isCompliant() && !$view instanceof CompleteHRAComplianceView) { ?>
            <a href="/resources/1702/verification.form2011.pdf">I did this</a> /
            <a href="/resources/1696/exceptionform2011.pdf">I am exempt</a>
            <?php } ?>
        </td>
    </tr>
    <?php
    }

    public function printReport(ComplianceProgramStatus $status)
    {

        //sfContext::getInstance()->getController()->getActionStack()->getLastEntry()->getActionInstance()->setLayout('minimal');
        sfContext::getInstance()->getResponse()->addStyleSheet('/css/compliance/Amway20092010ComplianceProgram.css');

        $user = $status->getUser();
        $eid = $user->getUserUniqueIdentifiers()
            ->filter(function ($v) { return $v->getIdentifierType() == 'amway_employee_id'; })->getFirst();
        ?>
    <style type="text/css">
            /**
             * Amway 2009-2010 compliance program styles
             * Ported from 2008-2009 program
            */
        ul {
            padding-left:1em;
        }

        div#phipSubPages {
            float:left;
            width:170px;
            margin-left:10px;
        }

        div#phipSubPages div#subpagelinks {
            text-align:right;
            font-size:.86em;
        }

        div#phipSubPages div#subpagelinks a {
            text-decoration:none;
            color:#42669A;
            display:block;
            padding:10px 0;
            text-decoration:none;
        }

        div#phipSubPages div#subpagelinks a:hover {
            color:#BBC3CB;
            text-decoration:underline;
        }

        div#phip {
            float:left;
            width:98%;
        }

        div#sectionBorder {
            background:#D9DBDC;
            width:2px;
            height:580px;
            float:left;
            margin-right:25px;
            margin-left:20px;
        }

        div#phip table#phipTable {
            width:100%;
            border-collapse:collapse;
        }

        div#phip table#phipTable thead th {
            background-color:#42669A;
            color:#FFFFFF;
            padding:15px;
            border:1px solid #5C7BB1;

        }

        div#phip div.pageTitle {
            color:#BC7D32;
            font-size:18pt;
            margin-bottom:20px;
        }

        div#phip table#legend {
            border:0px;
            margin:0px;
            padding:0px;
            border-collapse:collapse;
            width:100%;
        }

        div#phip table#legend td {
            background-color:#DCECFB;

        }

        div#phip table#legend td#firstColumn {
            color:#365784;
            padding:10px;
            font-size:14pt;
            text-align:center;
            width:100px;
            border-right:2px solid #FFFFFF;
        }

        div#phip table#legend td#secondColumn {
            text-align:center;
        }

        div#phip table#legend td#secondColumn table#secondColumnTable {
            width:100%;
            border:none;
            font-size:.9em;
        }

        div#phip table#phipTable tbody td {
            height:45px;
        }

        div#phip table#phipTable thead th {
            text-align:center;
            font-weight:normal;
            font-size:10pt;
        }

        div#phip table#phipTable thead th#resource {
            width:26%;
        }

        div#phip table#phipTable tbody td {
            text-align:center;
            padding:5px;
            font-size:smaller;
        }

        div#phip table#phipTable tbody td.resource {
            width:194px;
            text-align:left;
            padding:4px;
        }

        div#phip table#phipTable tbody {
            border:1px solid #DCDFE3;
            border-top:0px;
        }

        div#phip div#endNote {
            font-size:0.8em;
            margin-top:2em;
        }

        div#phip table#phipTable tbody #totalComplianceRow {
            border-bottom-width:1px;
            border-bottom-style:solid;
            border-bottom-color:#CCCCCC;
        }

        div#phip table#phipTable tbody #totalComplianceRow td.resource {
            font-weight:bold;
        }

        .subnote {
            font-size:.9em;
        }

        .notRequired {
            display:block;
            color:#FF0000;
            font-size:smaller;
            font-weight:bold;
        }

        .light {
            width:15px;
        }
    </style>
    <div style="width:10in; margin:0; padding:0.35in 0.35in 0 0.35in;">
        <table style="width:100%;margin-bottom:0.8in;">
            <tr>
                <td style="width:70%;">
                    Amway<br/>
                    c/o Circle Health Partners, Inc.<br/>
                    450 East 96th St., Ste 500<br/>
                    Indianapolis, IN 46240
                </td>
                <td style="width:294px;">
                    <img src="/images/amway/Optimal You Logo with tag.jpg" alt=""/>
                </td>
            </tr>
        </table>
        <table style="width:100%;margin-bottom:0.2in;">
            <tr style="font-weight:bold;padding-top:10em;">
                <td style="width:70%;"><br/>
                    <u>Personalized for:</u><br/>

                    <div style="margin-left:0.5in;">
                        <?php echo $user ?><br/>
                        <?php echo $user->getFullAddress('<br/>') ?>
                    </div>
                </td>
                <td><br/>
                    Employee ID: <?php echo $eid ?><br/>
                    Claims as of: 06/30/2011
                </td>
            </tr>
        </table>
        <div>
        </div>
        <div id="phip">
            <div class="pageTitle">My Incentive Report Card</div>
            <table id="legend">
                <tr>
                    <td id="firstColumn">LEGEND</td>
                    <td id="secondColumn">
                        <table id="secondColumnTable">
                            <tr>
                                <td>
                                    <img src="/images/lights/greenlight.gif" class="light" alt=""/> Completed
                                </td>
                                <td>
                                    <img src="/images/lights/yellowlight.gif" class="light" alt=""/> Partially Completed
                                </td>
                                <td>
                                    <img src="/images/lights/redlight.gif" class="light" alt=""/> Not Completed
                                </td>
                                <td>
                                    <img src="/images/lights/whitelight.gif" class="light" alt=""/> N/A
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table id="phipTable">
                <thead>
                    <tr>
                        <th class="resource">Resource</th>
                        <th class="status">Status</th>
                        <th class="information">More Info</th>
                        <th class="links">Links</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="totalComplianceRow">
                        <td class="resource">Overall Compliance</td>
                        <td class="status">
                            <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
                        </td>
                        <td class="information"></td>
                        <td class="links"></td>
                    </tr>
                    <?php $this->printTableRows($status); ?>
                </tbody>

            </table>

            <div id="endNote">
                <div>If you have any questions about your Optimal You report card please call toll free: (866) 682-3020
                    ext. 207.
                </div>
                <br/>

                <div>If you feel that you are exempt from a requirement or have already completed it, please click the
                    related link, complete the form and have it signed by your Physician. The form outlines your options
                    for submitting the form.
                </div>
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
    <?php
    }
}