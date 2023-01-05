<?php

require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/legacy/legacy_lib/lib/functions/getLetterFromNumber.php';

use hpn\common\text\Escaper;

class BasicComplianceProgramReportPrinter implements ComplianceProgramReportPrinter
{
    public function setShowPointBasedGroupTotal($boolean)
    {
        $this->showPointBasedGroupTotal = $boolean;

        return $this;
    }

    public function printHeader(ComplianceProgramStatus $status)
    {
        $templateVariables = array('user' => array('full_name' => $status->getUser()->getFullName()));
        Piranha::getInstance()->getContentReferencer()->printContent('110283', $templateVariables);
    }

    public function printReport(ComplianceProgramStatus $status)
    {
        $this->escaper = new Escaper();

        $user = $status->getUser();

        // Default style sheet is in content 110284

        echo ContentTable::getInstance()->findApplicable('110284', $user->client);
        ?>
    <div class="pageHeading"><?php echo $this->pageHeading ?></div>
    <?php $this->printHeader($status) ?>
    <table class="phipTable">
        <?php if($this->showLegend) : ?>
        <thead id="legend">
            <tr>
                <td colspan="6">
                    <div id="legendText">
                        <?php echo $this->escaper->escapeHtml($this->showUserNameInLegend ? (string) $user : 'Legend') ?>
                    </div>
                    <?php
                    foreach($status->getComplianceProgram()->getComplianceStatusMapper()
                                ->getMappings() as $sstatus => $mapping) :
                        if($sstatus != ComplianceStatus::PARTIALLY_COMPLIANT || $status->getComplianceProgram()
                            ->hasPartiallyCompliantStatus()
                        ) :
                            ?>
                            <div class="legendEntry">
                                <img src="<?php echo $mapping->getLight() ?>" class="light" alt=""/>
                                = <?php echo $mapping->getText() ?>
                            </div>
                            <?php
                        endif;
                    endforeach
                    ?>
                </td>
            </tr>
            <?php if($this->showName) : ?>
            <tr>
                <td colspan="6" style="text-align:left;font-size:13px;padding:5px;">
                    <span>Details for: <strong><?php echo $this->escaper->escapeHtml($status->getUser()) ?></strong></span>
                </td>
            </tr>
            <?php endif ?>
        </thead>
        <?php endif ?>
        <tbody>
            <?php
            $groupNumber = 0;
            $this->viewNumber = $this->numberViewsOnly ? 1 : 0;

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) :
                $group = $groupStatus->getComplianceViewGroup();
                if(!$this->showGroup($group)) continue;
                $groupNumber++;

                ?>
                <tr class="headerRow headerRow-<?php echo $group->getName() ?>">
                    <?php if($this->numberViewsOnly) : ?>
                    <th><?php echo $group->getReportName() ?></th>
                    <?php else : ?>
                    <th><?php echo sprintf('<strong>%s</strong>. %s', $groupNumber, $group->getReportName()) ?></th>
                    <?php endif ?>
                    <?php foreach(array_keys($this->callBacks) as $name) : ?>
                    <td><?php echo $group->pointBased() ? '' : (isset($this->tableHeaders[$name]) ? $this->tableHeaders[$name] : $name) ?></td>
                    <?php endforeach ?>
                    <?php if(!$group->pointBased()) : ?>
                    <?php if($this->showCompleted) : ?>
                        <td><?php echo $this->tableHeaders['completed'] ?></td>
                    <?php endif ?>
                    <?php if($this->showStatus) : ?>
                        <td><?php echo $this->tableHeaders['status'] ?></td>
                    <?php endif ?>
                    <td><?php echo $this->tableHeaders['links'] ?></td>
                    <?php else : ?>
                    <td><?php echo $this->tableHeaders['points_earned'] ?></td>
                    <td><?php echo $this->tableHeaders['points_possible'] ?></td>
                    <td><?php echo $this->tableHeaders['links'] ?></td>
                    <?php endif ?>
                </tr>
                <?php
                if(!$this->numberViewsOnly) {
                    $this->viewNumber = 0;
                }

                if($this->useViewSections) {
                    foreach($group->getComplianceViewsBySection() as $section => $views) {
                        if($section) :
                            $sectionClass = Doctrine_Inflector::urlize($section);
                            ?>
                            <tr class="<?php echo "headerRow viewSectionRow $sectionClass" ?>">
                                <th colspan="<?php echo (1 + count($this->callBacks) + 3) ?>" style="text-align:center">
                                    <?php echo $section ?>
                                </th>
                            </tr>
                            <?php
                        endif;

                        $skipped = 0;

                        foreach($views as $view) {
                            $viewStatus = $groupStatus->getComplianceViewStatus($view->getName());

                            if($skipped > 0) {
                                $skipped--;
                                continue;
                            }

                            if($viewsToSkip = $view->getAttribute('_screening_printer_hack')) {
                                $skipped = $viewsToSkip - 1;
                                $this->printScreeningViews($groupStatus, $viewStatus);
                                continue;
                            }

                            if($this->showNa || $viewStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                                $this->printView($group, $viewStatus);
                            }
                        }
                    }
                } else {
                    foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                        if($this->showNa || $viewStatus->getStatus() != ComplianceStatus::NA_COMPLIANT) {
                            $this->printView($group, $viewStatus);
                        }
                    }
                }

                if($this->showPointBasedGroupTotal && $group->pointBased()) {
                    $this->printGroupPointBasedTotal($groupStatus);
                }
            endforeach
            ?>
            <?php $this->printCustomRows($status) ?>
        </tbody>
        <?php if($this->showTotal) : ?>
        <tr class="headerRow">
            <?php if($status->getPoints() !== null) : ?>
            <td class="center" colspan="<?php echo 1 + count($this->callBacks) ?>">
                <?php echo $this->tableHeaders['total_status'] ?>
            </td>
            <td class="points"><?php echo $status->getPoints(); ?></td>
            <td class="white"><img src="<?php echo $status->getLight(); ?>" class="light" alt=""/></td>
            <td colspan="">
                <?php echo $this->tableHeaders['total_link'] ?>
            </td>
            <?php else : ?>
            <td colspan="<?php echo 1 + count($this->callBacks) + ($this->showCompleted ? 1 : 0) + ($this->showStatus ? 1 : 0) ?>" class="center">
                <?php echo $this->tableHeaders['total_status'] ?>
            </td>
            <td class="white">
                <img src="<?php echo $status->getLight(); ?>" class="light" alt=""/>
            </td>
            <td colspan="2">
                <?php echo $this->tableHeaders['total_link'] ?>
            </td>
            <?php endif ?>
        </tr>
        <?php endif ?>
    </table>
    <?php
    }

    public function addStatusCallbackColumn($name, $callback)
    {
        $this->callBacks[$name] = $callback;
    }

    public function setShowTotal($boolean)
    {
        $this->showTotal = $boolean;

        return $this;
    }

    public function setUseViewSections($boolean)
    {
        $this->useViewSections = $boolean;

        return $this;
    }

    protected function showGroup($group)
    {
        return true;
    }

    protected function printCustomRows($status)
    {

    }

    public function setShowLegend($boolean)
    {
        $this->showLegend = $boolean;

        return $this;
    }

    public function setScreeningResultsLink($link)
    {
        $this->screeningResultsLink = $link;
    }

    protected function printGroupPointBasedTotal(ComplianceViewGroupStatus $groupStatus)
    {
        $group = $groupStatus->getComplianceViewGroup();
        ?>
    <tr>
        <td style="text-align:right;color:#0000FF;"><span style="font-style:italic">Totals</span> = &nbsp;&nbsp;</td>
        <td class="points"><?php echo $groupStatus->getPoints() ?></td>
        <td class="points"><?php echo $group->getMaximumNumberOfPoints() ?></td>
        <td></td>
    </tr>
    <?php
    }

    protected function printScreeningViews(ComplianceViewGroupStatus $groupStatus, ComplianceViewStatus $firstViewStatus)
    {
        $group = $groupStatus->getComplianceViewGroup();
        $firstView = $firstViewStatus->getComplianceView();
        $viewCount = $firstView->getAttribute('_screening_printer_hack');
        $i = 0;

        $views = array();

        foreach($group->getComplianceViews() as $view) {
            if($view->getName() == $firstView->getName()) {
                $i = $viewCount;
                $views[] = $view;
                $i--;
            } else if($i > 0) {
                $views[] = $view;
                $i--;
            }
        }

        if(!$this->screeningResultsLink) {
            $this->screeningResultsLink = new FakeLink('Have These Screening Results in the Ideal Zone:', '#');
        }

        ?>
    <tr>
        <td>
            <?php if($this->numberScreeningCategory) : ?>
            <?php echo sprintf('<strong>%s</strong>. %s',
                $this->numberViewsOnly ? $this->viewNumber++ : getLetterFromNumber($this->viewNumber++),
                (string) $this->screeningResultsLink
            ) ?>
            <?php else : ?>
            <?php echo (string) $this->screeningResultsLink ?>
            <?php endif ?>
        </td>
        <?php foreach($this->callBacks as $callBack) : ?>
        <td class="callback" style="text-align:center;"></td>
        <?php endforeach ?>
        <?php if(!$group->pointBased()) : ?>
        <?php if($this->showCompleted) : ?>
            <td class="result"></td>
        <?php endif ?>
        <?php if($this->showStatus) : ?>
            <td class="status"></td>
        <?php endif ?>
        <td class="links"></td>
        <?php else : ?>
        <td class="points"></td>
        <td class="points"></td>
        <td class="links"></td>
        <?php endif ?>
    </tr>
    <?php

        $i = 0;

        foreach($views as $view) {
            $viewStatus = $groupStatus->getComplianceViewStatus($view->getName());
            ?>
        <tr>
            <td style="padding-left:18px;">
                &bull; <?php echo $view->getReportName(true) ?>
            </td>
            <?php foreach($this->callBacks as $callBack) : ?>
            <td class="callback" style="text-align:center;">
                <?php echo call_user_func($callBack, $viewStatus); ?>
            </td>
            <?php endforeach ?>
            <?php if(!$group->pointBased()) : ?>
                <?php if($this->showCompleted) : ?>
                    <td class="result">
                        <?php echo $this->getCompleted($group, $viewStatus) ?>
                    </td>
                <?php endif ?>
                <?php if($this->showStatus) : ?>
                    <td class="status">
                        <img src="<?php echo $viewStatus->getLight() ?>" alt=""/>
                    </td>
                <?php endif ?>
            <?php else : ?>
            <td class="points">
                <?php echo $viewStatus->getPoints() ?>
            </td>
            <td class="points">
                <?php echo $view->getMaximumNumberOfPoints() ?>
            </td>
            <?php endif ?>

            <?php if($i === 0) : ?>
            <td class="links" rowspan="<?php echo $viewCount ?>">
                <a href="?preferredPrinter=ScreeningProgramReportPrinter<?php echo isset($_GET['id']) ? "&id={$this->escaper->escapeUrl($_GET['id'])}" : '' ?>">
                    <?php echo sprintf($this->screeningLinkText, $viewCount) ?>
                </a>
                <?php echo $this->screeningAllResultsArea ?>
                <?php echo $this->screeningLinkArea ?>
            </td>
            <?php endif ?>
        </tr>
        <?php
            $i++;
        }
    }

    protected function getCompleted(ComplianceViewGroup $group,
                                    ComplianceViewStatus $viewStatus)
    {
        return $viewStatus->getComment();
    }

    protected function printView(ComplianceViewGroup $group, ComplianceViewStatus $viewStatus)
    {
        $view = $viewStatus->getComplianceView();
        ?>
    <tr class="view-<?php echo $view->getName() ?>">
        <td>
            <?php echo sprintf('<strong>%s</strong>. %s',
            $this->numberViewsOnly ? $this->viewNumber++ : getLetterFromNumber($this->viewNumber++),
            $view->getReportName(true)
        ) ?>
            <?php if($view->getAttribute('footnote')) : ?>
            <br/>
            <div style="text-align:center;font-style:italic;">
                <sub><?php echo $view->getAttribute('footnote') ?></sub>
            </div>
            <?php endif ?>
        </td>
        <?php foreach($this->callBacks as $callBack) : ?>
        <td class="callback" style="text-align:center;"><?php echo call_user_func($callBack, $viewStatus) ?></td>
        <?php endforeach ?>
        <?php if(!$group->pointBased()) : ?>
        <?php if($this->showCompleted) : ?>
            <td class="result"><?php echo $this->getCompleted($group, $viewStatus) ?></td>
        <?php endif ?>
        <?php if($this->showStatus) : ?>
            <td class="status"><?php echo $view->getAttribute('status', '<img src="'.$viewStatus->getLight().'" class="light" alt="" />') ?></td>
        <?php endif ?>
        <td class="links"><?php echo implode(' ', $view->getLinks()) ?></td>
        <?php else : ?>
        <td class="points"><?php echo $viewStatus->getPoints() ?></td>
        <td class="points"><?php echo $view->getMaximumNumberOfPoints() ?></td>
        <td class="links"><?php echo implode(' ', $view->getLinks()) ?></td>
        <?php endif ?>
    </tr>
    <?php
    }

    protected $escaper;
    protected $screeningLinkText = 'Click here for the %s results';
    private $useViewSections = true;
    private $showLegend = true;
    protected $showName = false;
    private $showPointBasedGroupTotal = false;
    private $showTotal = true;
    private $callBacks = array();
    private $viewNumber = 0;
    protected $tableHeaders = array(
        'completed'        => 'Completed',
        'status'           => 'Status',
        'links'            => 'Links',
        'points_earned'    => '# Points Earned',
        'points_possible'  => '# Points Possible',
        'total_status'     => 'Status of All Criteria =',
        'total_link'       => ''
    );
    public $showCompleted = true;
    public $showStatus = true;
    public $numberViewsOnly = false;
    public $numberScreeningCategory = true;
    public $showUserNameInLegend = false;
    protected $showNa = true;
    private $screeningResultsLink;
    protected $screeningLinkArea = '';
    protected $screeningAllResultsArea = '
        <br/>
        <br/>
        <br/>
        <a href="/content/989">Click for all screening results</a>
    ';
    protected $pageHeading = 'Rewards/To-Do Summary Page';
}