<?php

require_once sprintf('%s/apps/frontend/modules/legacy/legacy_lib/content/clients/ucan/steps/lib.php', sfConfig::get('sf_root_dir'));

use hpn\steel\query\SelectQuery;



class BedelFinancial2022FirstComplianceProgram extends ComplianceProgram
{

    protected function getActivityView($name, $activityId, $points, $reportName = null, $pointsPerRecord = null, $link = true)
    {
        if($pointsPerRecord === null){
            $pointsPerRecord = $points;
        }

        $view = new CompleteArbitraryActivityComplianceView(
            $this->getStartDate(),
            $this->getEndDate(),
            $activityId,
            $pointsPerRecord
        );

        $view->setMaximumNumberOfPoints($points);

        $view->setName($name);

        if($reportName !== null) {
            $view->setReportName($reportName);
        }

        if(!$link) {
            $view->emptyLinks();
        }

        return $view;
    }

    protected function getPlaceHolderView($name, $points, $reportName = null)
    {
        $view = new PlaceHolderComplianceView(null, 0);
        $view->setName($name);
        $view->setMaximumNumberOfPoints($points);

        if($reportName !== null) {
            $view->setReportName($reportName);
        }

        return $view;
    }


    public function getProgramReportPrinter($preferredPrinter = null)
    {
        return new BedelFinancial2022FirstWMS2ComplianceProgramPrinter();
    }



    public function setActiveUser(User $user = null)
    {
        if ($this->getMode() == ComplianceProgram::MODE_INDIVIDUAL) {
            refresh_fitbit_data($user);
        }

        return parent::setActiveUser($user);
    }

    public function filterFitbitData($data, $startDate, $endDate)
    {
        $ret = array();

        foreach($data as $k => $v) {
            $stamp = strtotime($k);

            if ($stamp >= $startDate && $stamp <= $endDate) {
                $ret[$stamp] = $v;
            }
        }

        return $ret;
    }

    private function getBmiView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBMIScreeningTestComplianceView'
        );

        $view->setIndicateSelfReportedResults(false);

        $view->addRange(4, 18.5, 25.0, 'E');
        $view->addRange(3, 17.0, 30.0, 'E');
        $view->addRange(2, 15.0, 35.0, 'E');
        $view->addRange(1, 13.0, 40.0, 'E');
        $view->setStatusSummary(0, '&lt;13 or &gt;40');


        return $view;
    }

    private function getBodyFatView($programStart, $programEnd)
    {
        $view = new PointBasedComplyWithScreeningTestComplianceView(
            $programStart,
            $programEnd,
            'ComplyWithBodyFatScreeningTestComplianceView'
        );

        $view->setIndicateSelfReportedResults(false);

        $view->addRange(4, 2.0, 18.0, 'M');
        $view->addRange(3, 0.0, 25.0, 'M');
        $view->addRange(2, 0.0, 30.0, 'M');
        $view->addRange(1, 0.0, 35.0, 'M');
        $view->addDefaultStatusSummaryForGender(0, 'M', '&gt;35');


        $view->addRange(4, 12.0, 25.0, 'F');
        $view->addRange(3, 0.0, 32.0, 'F');
        $view->addRange(2, 0.0, 37.0, 'F');
        $view->addRange(1, 0.0, 42.0, 'F');
        $view->addDefaultStatusSummaryForGender(0, 'F', '&gt;42');


        return $view;
    }

    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $biometrics = new ComplianceViewGroup('Biometrics');
        $biometrics->setPointsRequiredForCompliance(0);

        $bloodPressureView = new ComplyWithBloodPressureScreeningTestComplianceView($startDate, $endDate);
        $bloodPressureView->setIndicateSelfReportedResults(false);
        $bloodPressureView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 5, 0, 0));
//        $bloodPressureView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '120 - 139/80 - 89');
//        $bloodPressureView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 140/≥ 90');
        $this->configureViewForElearningAlternative($bloodPressureView, 'blood_pressure_2022');
        $biometrics->addComplianceView($bloodPressureView);

        $triglView = new ComplyWithTriglyceridesScreeningTestComplianceView($startDate, $endDate);
        $triglView->setIndicateSelfReportedResults(false);
        $triglView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 5, 0, 0));
//        $triglView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '150 - 199');
//        $triglView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 200');
        $this->configureViewForElearningAlternative($triglView, 'triglycerides_2022');
        $biometrics->addComplianceView($triglView);

        $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($startDate, $endDate);
        $glucoseView->setIndicateSelfReportedResults(false);
        $glucoseView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 5, 0, 0));
//        $glucoseView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '50-64 or 100-125');
//        $glucoseView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≤ 49 or ≥ 126');
        $this->configureViewForElearningAlternative($glucoseView, 'glucose_2022');
        $biometrics->addComplianceView($glucoseView);

        $totalHDLRatioView = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($startDate, $endDate);
        $totalHDLRatioView->setIndicateSelfReportedResults(false);
        $totalHDLRatioView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 5, 0, 0));
//        $totalHDLRatioView->setStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT, '5.01-6.99');
//        $totalHDLRatioView->setStatusSummary(ComplianceStatus::NOT_COMPLIANT, '≥ 7');
        $this->configureViewForElearningAlternative($totalHDLRatioView, 'hdl_2022');
        $biometrics->addComplianceView($totalHDLRatioView);

        $bodyFatView = new ComplyWithBodyFatScreeningTestComplianceView($startDate, $endDate);
        $bodyFatView->setIndicateSelfReportedResults(false);
        $bodyFatView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(10, 5, 0, 0));
        $bodyFatView->setReportName('Body Fat');
        $bodyFatView->setName('bodyfat');
        $this->configureViewForElearningAlternative($bodyFatView, 'waist_2022');
        $biometrics->addComplianceView($bodyFatView);


        $prevention = new ComplianceViewGroup('Prevention');
        $prevention->setPointsRequiredForCompliance(0);
        $prevention->setAttribute('available_points', 50);
        $prevention->setMaximumNumberOfPoints(50);

        $scr = new CompleteScreeningComplianceView($startDate, $endDate);
        $scr->setReportName('Biometric Screening');
        $scr->setName('screening');
        $scr->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
        $scr->emptyLinks();
        $prevention->addComplianceView($scr);

        $eye = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 395, 2);
        $eye->setMaximumNumberOfPoints(2);
        $eye->setReportName('Eye Exam');
        $eye->setName('eye_exam');
        $eye->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $prevention->addComplianceView($eye);

        $fluTetView = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 121235, 2);
        $fluTetView->setReportName('Tetanus & Flu Vaccinations');
        $fluTetView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $fluTetView->setName('prevention_vaccine');
        $prevention->addComplianceView($fluTetView);

        $dental = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 396, 2);
        $dental->setMaximumNumberOfPoints(2);
        $dental->setReportName('Dental Exam');
        $dental->setName('dental_exam');
        $dental->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(2, 0, 0, 0));
        $prevention->addComplianceView($dental);


        $community = new ComplianceViewGroup('Community');
        $community->setPointsRequiredForCompliance(0);
        $community->addComplianceView($this->getActivityView('donate_blood', 346, 5, null, 10, true));
        $community->setMaximumNumberOfPoints(5);
        $community->setAttribute('available_points', 5);

        $brain = new ComplianceViewGroup('Mind');
        $brain->setPointsRequiredForCompliance(0);

        $beingVideo = new PlaceHolderComplianceView(null, 0);
        $beingVideo->setMaximumNumberOfPoints(5);
        $beingVideo->setReportName('Being Video of Your Choice');
        $beingVideo->setName('mind_being_video');
        $beingVideo->addLink(new Link('Being Videos', '/content/learn-by-video', false, '_blank'));
        $beingVideo->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $user_id = $user->id;
            $result = SelectQuery::create()
            ->select('count(distinct(lesson_id)) as lessons')
            ->from('tbk_lessons_complete tbk')
            ->where('tbk.user_id = ?', array($user_id))
            ->andWhere('tbk.completion_date BETWEEN ? AND ?', array('2022-01-01', '2022-06-30'))
            ->hydrateSingleRow()
            ->execute();

            $lessons = $result['lessons'] ?? 0;
            $points = intval($status->getPoints()) ?? 0;
            
            if ($lessons >= 1) {
                $points += $lessons;
                if ($points > 5) $points = 5;
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            $status->setPoints($points);
    
        });
        $brain->addComplianceView($beingVideo);

        $brainElearningView = new CompleteELearningGroupSet($startDate, $endDate, 'mental_health');
        $brainElearningView->setReportName('E-learning of Your Choice');
        $brainElearningView->setName('mind_elearning');
        $brainElearningView->setPointsPerLesson(1);
        $brainElearningView->setMaximumNumberOfPoints(5);
        $brainElearningView->useAlternateCode(true);
        $brainElearningView->emptyLinks();
        $brainElearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&amp;tab_alias[]=mental_health', false, '_blank'));
        $brain->addComplianceView($brainElearningView);

        $brain->setMaximumNumberOfPoints(50);
        $brain->setAttribute('available_points', 75);


        $fitness = new ComplianceViewGroup('Exercise');
        $fitness->setPointsRequiredForCompliance(0);

//        $steps = new PlaceHolderComplianceView(null, 0);
        $steps = new BedelFinancialWMS3StepsComplianceView($startDate, $endDate, 42000, 3);
        $steps->setMaximumNumberOfPoints(78);
        $steps->setReportName('42,000 steps in a week <br /> <span style="text-align: center;">(3 pts/week)</span>');
        $steps->setName('steps');
        $steps->addLink(new Link('Fitness Tracker', '/content/fitness'));
        $fitness->addComplianceView($steps);


        $fitness->setMaximumNumberOfPoints(78);
        $fitness->setAttribute('available_points', 78);

        $nutrition = new ComplianceViewGroup('Nutrition');
        $nutrition->setPointsRequiredForCompliance(0);

        $beingVideo = new PlaceHolderComplianceView(null, 0);
        $beingVideo->setMaximumNumberOfPoints(5);
        $beingVideo->setReportName('Being Video of Your Choice');
        $beingVideo->setName('nutrition_being_video');
        $beingVideo->addLink(new Link('Being Videos', '/content/learn-by-video', false, '_blank'));
        $beingVideo->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $user_id = $user->id;
            $result = SelectQuery::create()
            ->select('count(distinct(lesson_id)) as lessons')
            ->from('tbk_lessons_complete tbk')
            ->where('tbk.user_id = ?', array($user_id))
            ->andWhere('tbk.completion_date BETWEEN ? AND ?', array('2022-01-01', '2022-06-30'))
            ->hydrateSingleRow()
            ->execute();

            $lessons = $result['lessons'] ?? 0;
            $points = intval($status->getPoints()) ?? 0;
            
            if ($lessons > 5) {
                $points += $lessons - 5;
                if ($points > 5) $points = 5;
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            $status->setPoints($points);
    
        });
        $nutrition->addComplianceView($beingVideo);

        $nutritionElearningView = new CompleteELearningGroupSet($startDate, $endDate, 'foods_nutrition_key');
        $nutritionElearningView->setReportName('Lesson of Your Choice');
        $nutritionElearningView->setName('nutrition_elearning');
        $nutritionElearningView->setPointsPerLesson(1);
        $nutritionElearningView->setMaximumNumberOfPoints(5);
        $nutritionElearningView->useAlternateCode(true);
        $nutritionElearningView->emptyLinks();
        $nutritionElearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&amp;tab_alias[]=foods_nutrition_key', false, '_blank'));
        $nutrition->addComplianceView($nutritionElearningView);

        $nutrition->setMaximumNumberOfPoints(60);
        $nutrition->setAttribute('available_points', 95);

        $stress = new ComplianceViewGroup('De-stress');
        $stress->setPointsRequiredForCompliance(0);

        $beingVideo = new PlaceHolderComplianceView(null, 0);
        $beingVideo->setMaximumNumberOfPoints(5);
        $beingVideo->setReportName('Being Video of Your Choice');
        $beingVideo->setName('destress_being_video');
        $beingVideo->addLink(new Link('Being Videos', '/content/learn-by-video', false, '_blank'));
        $beingVideo->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
            $user_id = $user->id;
            $result = SelectQuery::create()
            ->select('count(distinct(lesson_id)) as lessons')
            ->from('tbk_lessons_complete tbk')
            ->where('tbk.user_id = ?', array($user_id))
            ->andWhere('tbk.completion_date BETWEEN ? AND ?', array('2022-01-01', '2022-06-30'))
            ->hydrateSingleRow()
            ->execute();

            $lessons = $result['lessons'] ?? 0;
            $points = intval($status->getPoints()) ?? 0;
            
            if ($lessons > 10) {
                $points += $lessons - 10;
                if ($points > 5) $points = 5;
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }

            $status->setPoints($points);
    
        });
        $stress->addComplianceView($beingVideo);

        $stressElearningView = new CompleteELearningGroupSet($startDate, $endDate, 'stress_reslience');
        $stressElearningView->setReportName('Lesson of Your Choice');
        $stressElearningView->setName('stress_elearning');
        $stressElearningView->setPointsPerLesson(1);
        $stressElearningView->setMaximumNumberOfPoints(5);
        $stressElearningView->useAlternateCode(true);
        $stressElearningView->emptyLinks();
        $stressElearningView->addLink(new Link('Complete Lessons', '/content/9420?action=lessonManager&amp;tab_alias[]=stress_reslience', false, '_blank'));
        $stress->addComplianceView($stressElearningView);

        $stress->setMaximumNumberOfPoints(75);
        $stress->setAttribute('available_points', 85);


        $this->addComplianceViewGroup($biometrics);
        $this->addComplianceViewGroup($prevention);
        $this->addComplianceViewGroup($community);
        $this->addComplianceViewGroup($brain);
        $this->addComplianceViewGroup($fitness);
        $this->addComplianceViewGroup($nutrition);
        $this->addComplianceViewGroup($stress);



        foreach($this->getComplianceViews() as $view) {
            foreach($view->getLinks() as $link) {
                if($link->getLinkText() == 'Enter/Update Info') {
                    $link->setLinkText('Update');
                }
            }
        }
    }

    protected function configureViewForElearningAlternative(ComplianceView $view, $alias)
    {
        $view->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($alias) {
            $view = $status->getComplianceView();

            if($status->getAttribute('has_result') && !$status->isCompliant()) {
                $elearningView = new CompleteELearningGroupSet($view->getStartDate(), $view->getEndDate(), $alias);
                $elearningView->setComplianceViewGroup($view->getComplianceViewGroup());
                $elearningView->setName('alternative_'.$view->getName());
                $elearningView->useAlternateCode(true);

                $view->addLink(new Link('Alternative', "/content/9420?action=lessonManager&tab_alias={$alias}"));

                $elearningStatus = $elearningView->getStatus($user);

                $lessonCompleted = count($elearningStatus->getAttribute('lessons_completed'));

                if($status->getStatus() == ComplianceStatus::NOT_COMPLIANT) {
                    if($lessonCompleted >= 2) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                        $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                    } elseif($lessonCompleted >= 1) {
                        $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
                        $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                    }
                } elseif($status->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) {
                    if($lessonCompleted >= 1) {
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                        $status->setComment(sprintf('%s (Alternative Used)', $status->getComment()));
                    }
                }

            }
        });
    }
}


class BedelFinancial2022FirstWMS2ComplianceProgramPrinter implements ComplianceProgramReportPrinter
{

    public function printReport(ComplianceProgramStatus $status)
    {

        $user = $status->getUser();

        $totalPoints = $status->getPoints();
        if(isset($_GET['points_only'])) {
        	echo $totalPoints;
        	die;
        }
        $classForPoints = function($points) {
            if ($points >= 125) {
                return 'success';
            } else if ($points >= 50.1) {
                return 'warning';
            } else {
                return 'danger';
            }
        };

        $circle = function($points, $text) use ($classForPoints) {
            $class = $points === 'beacon' ? 'beacon' : $classForPoints($points);
            ob_start();
            ?>
            <div class="circle-range">
                <div class="circle-range-inner circle-range-inner-<?php echo $class ?>">
                    <div style="font-size: 1.3em; line-height: 1.0em;"><?php echo $text ?></div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        };

        $classFor = function($rawPct) {
            return $rawPct > 0 ? ($rawPct >= 1 ? 'success' : 'warning') : 'danger';
        };

        $groupTable = function(ComplianceViewGroupStatus $group) use ($classFor) {
            ob_start();

            if ($group->getComplianceViewGroup()->getName() == 'Biometrics') : ?>
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>Test</th>
                        <th>Target</th>
                        <th class="text-center">Point Values</th>
                        <th class="text-center">Result</th>
                        <th class="text-center">Your Points</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $view = $viewStatus->getComplianceView() ?>
                        <tr>
                            <td rowspan="3">
                                <?php echo $view->getReportName() ?>
                                <br/>
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                    <div><?php echo $link->getHTML() ?></div>
                                <?php endforeach ?>
                            </td>
                            <td><span class="label label-success"><?php echo $view->getStatusSummary(ComplianceStatus::COMPLIANT) ?></span></td>
                            <td class="text-center">
                                <?php echo $view->getStatusPointMapper()->getPoints(ComplianceStatus::COMPLIANT) ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) : ?>
                                    <span class="label label-success"><?php echo $viewStatus->getComment() ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT) : ?>
                                    <span class="label label-success"><?php echo $viewStatus->getPoints() ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="label label-warning"><?php echo $view->getStatusSummary(ComplianceStatus::PARTIALLY_COMPLIANT) ?></span></td>
                            <td class="text-center">
                                <?php echo $view->getStatusPointMapper()->getPoints(ComplianceStatus::PARTIALLY_COMPLIANT) ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                    <span class="label label-success"><?php echo $viewStatus->getComment() ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT) : ?>
                                    <span class="label label-warning"><?php echo $viewStatus->getPoints() ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="label label-danger"><?php echo $view->getStatusSummary(ComplianceStatus::NOT_COMPLIANT) ?></span></td>
                            <td class="text-center">
                                <?php echo $view->getStatusPointMapper()->getPoints(ComplianceStatus::NOT_COMPLIANT) ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT) : ?>
                                    <span class="label label-success"><?php echo $viewStatus->getComment() ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-center">
                                <?php if($viewStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT) : ?>
                                    <span class="label label-danger"><?php echo $viewStatus->getPoints() ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php else : ?>

                <table class="details-table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th class="points">Maximum</th>
                        <th class="points">Actual</th>
                        <th class="text-center">Progress</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($group->getComplianceViewStatuses() as $viewStatus) : ?>
                        <?php $pct = $viewStatus->getPoints() / $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?>
                        <?php $class = $classFor($pct) ?>
                        <tr>
                            <td class="name">
                                <?php echo $viewStatus->getComplianceView()->getReportName() ?>
                            </td>
                            <td class="points"><?php echo $viewStatus->getComplianceView()->getMaximumNumberOfPoints() ?></td>
                            <td class="points <?php echo $class ?>">
                                <?php echo $viewStatus->getPoints() ?>
                            </td>
                            <td class="text-center">
                                <div class="pgrs pgrs-tiny">
                                    <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                    <div><?php echo $link->getHTML() ?></div>
                                <?php endforeach ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
        <?php
            endif;
            return ob_get_clean();
        };

        $tableRow = function($name, ComplianceViewGroupStatus $group) use ($classFor, $groupTable) {
            ob_start();

            $points = $group->getPoints();
            $target = $group->getComplianceViewGroup()->getMaximumNumberOfPoints();

            $pct = $points / $target;

            $class = $classFor($pct);
            ?>
            <tr class="picker closed">
                <td class="name">
                    <?php echo $name ?>
                    <div class="triangle"></div>
                </td>
                <td class="points target">
                    <strong><?php echo $target ?></strong><br/>
                    points
                </td>
                <td class="points <?php echo $class ?>">
                    <strong><?php echo $points ?></strong><br/>
                    points
                </td>
                <td class="pct">
                    <div class="pgrs">
                        <div class="bar <?php echo $class ?>" style="width: <?php echo max(1, $pct * 100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <tr class="details closed">
                <td colspan="4">
                    <?php echo $groupTable($group) ?>
                </td>
            </tr>
            <?php

            return ob_get_clean();
        }

        ?>
        <style type="text/css">
            #activities {
                width: 100%;
                min-width: 500px;
                border-collapse: separate;
                border-spacing: 5px;
            }

            #activities tr.picker {
                background-color: #EFEFEF;
                padding: 5px;
            }

            #activities tr.details {
                background-color: transparent;
            }

            #activities tr.picker td, #activities tr.picker th {
                padding: 5px;
                border: 2px solid transparent;
            }

            #activities .points {
                text-align: center;
                width: 65px;
            }

            tr.picker .name {
                font-size: 1.2em;
                position: relative;
            }

            .target {
                background-color: #48c7e8;
                color: #FFF;
            }

            .success {
                background-color: #73c26f;
                color: #FFF;
            }

            .warning {
                background-color: #fdb73b;
                color: #FFF;
            }

            .text-center {
                text-align: center;
            }

            .danger {
                background-color: #FD3B3B;
                color: #FFF;
            }

            .pct {
                width: 30%;
            }

            .pgrs {
                height: 50px;
                background-color: #CCC;
                position: relative;
            }

            .pgrs-tiny {
                height: 10px;
                width: 80%;
                margin: 0 auto;
            }

            .pgrs .bar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
            }

            .triangle {
                position: absolute;
                right: 15px;
                top: 15px;
            }

            tr.details.closed {
                display: none;
            }

            tr.details.open {
                display: table-row;
            }

            tr.details > td {
                padding: 25px;
            }

            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 5px;
            }

            .details-table .name {
                width: 200px;
            }

            .closed .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12.5px 0 12.5px 21.7px;
                border-color: transparent transparent transparent #48c8e8;
            }

            .open .triangle {
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 21.7px 12.5px 0 12.5px;
                border-color: #48c8e8 transparent transparent transparent;
            }

            #activities tr.picker:hover {
                cursor: pointer;
            }

            #activities tr.picker:hover td {
                border-color: #48c8e8;
            }

            .circle-range {
                margin-top: 50px;
            }

            .circle-range-inner-beacon {
                background-color: #48C7E8;
                color: #FFF;
            }

            .circle-range-inner-danger {
                background-color: #A0A0A0;
                color: #FFF;
            }

            .activity .circle-range {
                border-color: #489DE8;
            }

            .circle-range .circle-points {
                font-size: 1.4em;
            }

            .legend {
            	width: 25px; 
            	height: 25px;
            	border-radius: 50%;
            }

            .green-circle {
            	background: #74c46e;
            }

            .orange-circle {
            	background: #feb839;
            }

            .red-circle {
            	background-color: #f25752;
            }

            #point-discounts {
                /*width: 100%;*/
            }
            #point-discounts td {
                padding: 10px;
            }
            #point-discounts #discount-success {
                width: 30px;
                height: 30px;
                border-radius: 15px;
                background-color: #74c36e;
            }
            #point-discounts #discount-warning {
                width: 30px;
                height: 30px;
                border-radius: 15px;
                background-color: #fdb83b;
            }
            #point-discounts #discount-danger {
                width: 30px;
                height: 30px;
                border-radius: 15px;
                background-color: #f15752;
            }

            <?php if($status->getUser()->insurancetype) : ?>
            #physician-reviewed-link {
                display: none;
            }
            <?php endif ?>
        </style>

        <script type="text/javascript">
            $(function() {
                $.each($('#activities .picker'), function() {
                    $(this).click(function(e) {
                        if ($(this).hasClass('closed')) {
                            $(this).removeClass('closed');
                            $(this).addClass('open');
                            $(this).nextAll('tr.details').first().removeClass('closed');
                            $(this).nextAll('tr.details').first().addClass('open');
                        } else {
                            $(this).addClass('closed');
                            $(this).removeClass('open');
                            $(this).nextAll('tr.details').first().addClass('closed');
                            $(this).nextAll('tr.details').first().removeClass('open');
                        }
                    });
                });
            });
        </script>
        <img src="/images/banners/incentives_banner.png" style="width: 100%;">
        <div class="row">
            <div class="col-md-12">
                <h1>MY REPORT CARD</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-center">
                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                        	<div class="circle-range">
				                <div class="circle-range-inner circle-range-inner-danger">
				                    <div style="font-size: 1.3em; line-height: 1.0em;"><span class="circle-points quarter-1"><?php echo time() >= strtotime('2022-07-01') ? '' : $totalPoints; ?></span><br/><br/>1/1/2022 - 6/30/2022</div>
				                </div>
				            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row">
                    		<div class="circle-range">
				                <div class="circle-range-inner circle-range-inner-danger">
				                    <div style="font-size: 1.3em; line-height: 1.0em;"><span class="circle-points quarter-2"><?php echo time() >= strtotime('2022-07-01') ? '' : 0; ?></span><br/><br/>7/1/2022 - 12/31/2022</div>
				                </div>
				            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row">
                            <table id="point-discounts">
                                <tbody>
                                <tr>
                                	
                                    <td>Points</td><td>Half Days of PTO</td>
                                </tr>
                                <tr>
                                    <td>&#8805;100</td><td>3</td>
                                </tr>
                                <tr>
                                    <td>90-99</td><td>2</td>
                                </tr>
                                <tr>
                                    <td>80-89</td><td>1</td>
                                </tr>
                                <tr>
                                    <td>0-79</td><td>0</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>




            </div>
        </div>
        <div class="row">
            <div class="col-md-12">



                <p><a href="#" id="more-info-toggle">Click for more information...</a></span></p>

                <div id="more-info" style="display: none">
                    <p>
                        The Wellness Screenings will take place on-site in January & July.
                    </p>

                    <p>
                        When you complete your wellness screening, your results will be loaded into your report below,
                        providing you wellness points.
                    </p>

                    <p>
                        You will have the opportunity to earn incentive points towards the wellness program 1/1-6/30/2022
                        AND 7/1-12/31/2022. You are not required to meet the target range for every measure. The criteria
                        for meeting these ranges are listed below in your Biometrics. If your screening measure falls
                        into a borderline/at-risk range (color coded below in yellow or red), you have the option to
                        complete Reasonable Alternative’s that will be indicated as "Alternative" links next to each
                        Biometric measure in your Report Card.
                    </p>

                    <p>
                        Reasonable Alternatives consist of E-learning lessons of your choice. There are over 1000 lessons
                        available and you will need to complete 2 lessons for each measure that shows an "Alternative"
                        link. If you do not have any "Alternative" links, you have already earned a 10 point value based
                        on your result and do not need to do any lessons for the Biometric Measures section of your
                        Report Card.
                    </p>

                    <p>
                        Other point earning opportunities are available in the Prevention, Community, Mind, Exercise,
                        Nutrition & De-Stress categories in your Report Card. You can click the "Update" links to to the
                        right of each activity to log those activities for the dates they occurred. You may log back to
                        the beginning of the program for each of the 6-month periods of the program. The Biometric
                        Screening, Being Videos, E-learning, Steps (Exercise category) will be populated automatically
                        in the Circle Wellness portal. You may Sync a device for your Steps.
                    </p>

                    <p>
                        PTO days will be rewarded on 7/1/2022 for the 1/1-6/30/2022 program period and 1/3/2023 for
                        the 7/1-12/31/2022 program period.
                    </p>

                    <p>
                        If you have any questions or need assistance with your logging, please contact Customer Service
                        at 866-682-3020 x204.
                    </p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="points">Target</th>
                        <th class="points">Actual</th>
                        <th class="text-center">Progress</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $tableRow('Biometrics', $status->getComplianceViewGroupStatus('Biometrics')) ?>
                    <?php echo $tableRow('Prevention', $status->getComplianceViewGroupStatus('Prevention')) ?>
                    <?php echo $tableRow('Community', $status->getComplianceViewGroupStatus('Community')) ?>
                    <?php echo $tableRow('Mind', $status->getComplianceViewGroupStatus('Mind')) ?>
                    <?php echo $tableRow('Exercise', $status->getComplianceViewGroupStatus('Exercise')) ?>
                    <?php echo $tableRow('Nutrition', $status->getComplianceViewGroupStatus('Nutrition')) ?>
                    <?php echo $tableRow('De-stress', $status->getComplianceViewGroupStatus('De-stress')) ?>

                    </tbody>
                </table>
            </div>
        </div>

        <script type="text/javascript">
            $(function() {
            	<?php if(time() >= strtotime('2022-07-01')): ?>
        		$.ajax({
            		url: '/compliance/bedelfinancial-2022/covid19/compliance_programs?id=1710&points_only=true',
            		success: function(reply) {

            			$('span.quarter-1').html(<?php echo $totalPoints; ?>);
            			$('span.quarter-2').html(reply);
            		}
            	})
            	<?php endif; ?>
                $more = $('#more-info-toggle');
                $moreContent = $('#more-info');

                $more.click(function(e) {
                    e.preventDefault();

                    if ($more.html() == 'Click for more information...') {
                        $moreContent.css({ display: 'block' });
                        $more.html('Less...');
                    } else {
                        $moreContent.css({ display: 'none' });
                        $more.html('Click for more information...');
                    }
                });
            });
        </script>
        <?php
    }
}