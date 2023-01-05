<?php
/**
 * A compliance view represents a single "Category" that a user's compliance
 * can be evaluated in. Examples include completing an HRA during a date range.
 * Different compliance views must be defined by implementing the
 * abstract methods of this class.
 */

use hpn\steel\query\SelectQuery;

abstract class ComplianceView implements MenagerieSecurable
{
    public function secureAccess(sfProjectUser $user, $action = null)
    {
        return true;
    }

    public function __toString()
    {
        return (string) $this->getReportName();
    }

    /**
     * Creates an instance of this compliance view by forwarding arguments to
     * the constructor and returning the created instance. This exists to circumvent
     * php's lack of dereferencing.
     * This is useful in making the API chainable, as one can do e.g.
     * CompleteHRA::create(xxx,yyy)->setStartDate('xxx')->setEndDate('xxx')->setReportName('complete it')
     *
     * @return ComplianceView
     */
    public static function create()
    {
        $r = new ReflectionClass(get_called_class());

        return $r->newInstanceArgs(func_get_args());
    }

    /**
     * Decided if the implementing class getStatus method should be called or
     * if a cached status object or NA status object should be used.
     *
     * @param User $user
     * @return ComplianceStatus
     */
    protected function getBaseStatus(User $user, &$saved = false)
    {
        if(($group = $this->getComplianceViewGroup()) && $program = $group->getComplianceProgram()) {
            $record = $program->getComplianceProgramRecord();
        } else {
            $record = false;
        }

        if($this->evaluateCallback && !call_user_func($this->evaluateCallback, $user)) {
            return new ComplianceViewStatus($this, $this->evaluateStatus, 0, $this->evaluateComment);
        } else {
            return $this->getStatus($user);
        }
    }

    /**
     * The framework calls this method. This method does some work before and after
     * calling getStatus(), which is defined by the inheriting class.
     *
     * @param User $user
     * @return ComplianceViewStatus
     */
    public function getMappedStatus(User $user)
    {
        $status = $this->getBaseStatus($user, $saved);

        if($saved) {
            if($this->pointStatusMapper != null) {
                $status->setPointStatusMapper($this->pointStatusMapper);
            }

            if($this->statusPointMapper != null) {
                $status->setStatusPointMapper($this->statusPointMapper);
            }

            return $status;
        }

        if($this->preMapCallback) {
            call_user_func($this->preMapCallback, $status, $user);
        }

        if(($override = $this->getEligibleOverride($user)) && (!$override->getIgnoreStatusNA() || $status->getStatus() != ComplianceStatus::NA_COMPLIANT)) {
            if($override->getStatus() !== null) {
                if($this->useOverrideCreatedDate) {
                    $overrideCreatedDate = $this->getOverrideCreatedDate($override->getId());

                    $status->setUsingOverride(true);
                    $status->setAttribute('original_status', $status->getStatus());
                    $status->setStatus($override->getStatus());
                    $status->setAttribute('override_created_date', $overrideCreatedDate);
                } else {
                    $status->setUsingOverride(true);
                    $status->setAttribute('original_status', $status->getStatus());
                    $status->setStatus($override->getStatus());
                }

            }

            if($override->getPoints() !== null) {
                if($this->useOverrideCreatedDate) {
                    $overrideCreatedDate = $this->getOverrideCreatedDate($override->getId());

                    $status->setUsingOverride(true);

                    $status->setAttribute('original_points', $status->getPoints());
                    $status->setAttribute('override_created_date', $overrideCreatedDate);

                    if($override->add_points) {
                        $currentPoints = $status->getPoints() ? $status->getPoints() : 0;

                        $status->setPoints($override->getPoints() + $currentPoints);
                    } else {
                        $status->setPoints($override->getPoints());
                    }

                } else {
                    $status->setUsingOverride(true);

                    $status->setAttribute('original_points', $status->getPoints());

                    if($override->add_points) {
                        $currentPoints = $status->getPoints() ? $status->getPoints() : 0;

                        $status->setPoints($override->getPoints() + $currentPoints);
                    } else {
                        $status->setPoints($override->getPoints());
                    }
                }
            }

            if($override->getComment() !== null) {
                $status->setUsingOverride(true);
                $status->setAttribute('original_comment', $status->getComment());
                $status->setComment($override->getComment());
            }

            if($override->getNotRequired() && $status->getStatus() != ComplianceStatus::COMPLIANT) {
                $status->setUsingOverride(true);

                if(!$status->getAttribute('original_status')) {
                    $status->setAttribute('original_status', $status->getStatus());
                }

                $status->setStatus(ComplianceStatus::NA_COMPLIANT);
            }
        } else if($this->optional && $status->getStatus() != ComplianceStatus::COMPLIANT) {
            $status->setStatus(ComplianceStatus::NA_COMPLIANT);
        } else if($this->alternativeView) {
            if($this->alternativeLinks) {
                foreach($this->alternativeView->getLinks() as $link) {
                    $this->addLink($link);
                }
            }

            $this->alternativeView->setComplianceViewGroup($this->getComplianceViewGroup());

            $altStatus = $this->alternativeView->getMappedStatus($user);

            if($altStatus->isCompliant() || $this->forceAlternativeView) {
                $status->addAttributes(array(
                    'original_status'  => $status->getStatus(),
                    'original_points'  => $status->getPoints(),
                    'original_comment' => $status->getComment()
                ));

                $status->setStatus($altStatus->getStatus());
                $status->setComment($altStatus->getComment());
                $status->setPoints($altStatus->getPoints());

                if($this->alternativeCallback !== null) {
                    call_user_func($this->alternativeCallback, $status, $altStatus);
                }
            }
        }

        if($this->pointStatusMapper != null) {
            $status->setPointStatusMapper($this->pointStatusMapper);
        }

        if($this->statusPointMapper != null) {
            $status->setStatusPointMapper($this->statusPointMapper);
        }

        $maximumPoints = $this->getMaximumNumberOfPoints();

        if($maximumPoints !== null && (!$status->getUsingOverride() || $this->pointsOverrideHonorsMaximum) && $status->getPoints() > $maximumPoints) {
            $status->setPoints($maximumPoints);
        }

        if($this->postEvaluateCallback) {
            call_user_func($this->postEvaluateCallback, $status, $user);
        }

        return $status;
    }

    /**
     * @param User $user
     * @return ComplianceViewStatus
     */
    public abstract function getStatus(User $user);

    /**
     * Sets a descriptive status summary. This is used for some views that
     * want to print text regarding what is required to achieve a given
     * status.
     *
     * @param int $status A ComplianceStatus constant
     * @param string $summary
     * @return ComplianceView This instance.
     */
    public function setStatusSummary($status, $summary)
    {
        $this->statusSummaries[$status] = $summary;

        return $this;
    }

    /**
     * Returns the configured status summary for a given status.
     *
     * @param int $status A ComplianceStatus constant
     * @return string|null
     */
    public function getStatusSummary($status)
    {
        if(array_key_exists($status, $this->statusSummaries)) {
            return $this->statusSummaries[$status];
        } else {
            return $this->getDefaultStatusSummary($status);
        }
    }

    public function addAttributes(array $attributes)
    {
        foreach($attributes as $name => $value) {
            $this->attributes[$name] = $value;
        }

        return $this;
    }

    /**
     * Sets an arbitrary attribute to a value that can be obtained later.
     *
     * @param string $name
     * @param mixed $value
     * @return ComplianceViewGroup This instance.
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Gets an arbitrary attribute. This might be used to pass variables to
     * templates, for example.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }

    /**
     * Returns the default status description/text given a status constant.
     *
     * @param int $status
     */
    public abstract function getDefaultStatusSummary($status);

    /**
     * A default name for this view.
     */
    public abstract function getDefaultName();


    public abstract function getDefaultReportName();

    /**
     * Sets the point-to-status mapper. This can be used on views that only
     * setup points earned, and remap those points to an appropriate status.
     *
     * @param CompliancePointStatusMapper $mapper
     * @return ComplianceView This instance.
     */
    public function setCompliancePointStatusMapper(CompliancePointStatusMapper $mapper = null)
    {
        $this->pointStatusMapper = $mapper;

        return $this;
    }

    public function hasCompliancePointStatusMapper()
    {
        return $this->pointStatusMapper != null;
    }

    /**
     * @param ComplianceStatusPointMapper $mapper
     * @return ComplianceView This instance.
     */
    public function setComplianceStatusPointMapper(ComplianceStatusPointMapper $mapper = null)
    {
        $this->statusPointMapper = $mapper;

        return $this;
    }

    public function hasComplianceStatusPointMapper()
    {
        return $this->statusPointMapper != null;
    }

    /**
     * @return ComplianceViewGroup
     */
    public function getComplianceViewGroup()
    {
        return $this->viewGroup;
    }

    /**
     * We temporarily allow set null until we can fix up all the unneeded calls.
     *
     * @param ComplianceViewGroup $g
     * @return ComplianceView This instance.
     */
    public function setComplianceViewGroup(ComplianceViewGroup $g = null)
    {
        $this->viewGroup = $g;

        return $this;
    }

    public function getName()
    {
        if($this->name === null) {
            return $this->getDefaultName();
        } else {
            return $this->name;
        }
    }

    /**
     * Returns true if this view is optional.
     *
     * @return boolean
     */
    public function getOptional()
    {
        return $this->optional;
    }

    /**
     * @param string|null $v
     * @return ComplianceView This instance.
     */
    public function setName($v = null)
    {
        $this->name = $v;

        return $this;
    }

    public function getReportName($forHTML = false)
    {
        if($this->reportName === null) {
            $name = $this->getDefaultReportName();
        } else {
            $name = $this->reportName;
        }

        return $this->getAttribute('report_name_link') && $forHTML ?
            sprintf('<a href="%s" %s>%s</a>', $this->getAttribute('report_name_link'), $this->getAttribute('report_name_link_popup') ? 'target="_blank"' : '', $name) : $name;
    }

    /**
     * @param string|null $v
     * @return ComplianceView This instance.
     */
    public function setReportName($v = null)
    {
        $this->reportName = $v;

        return $this;
    }

    /**
     * @param Link $link
     * @return ComplianceView This instance.
     */
    public function addLink(Link $link)
    {
        $this->links[] = $link;

        return $this;
    }

    public function allowPointsOverride()
    {
        return !$this->hasComplianceStatusPointMapper() && $this->getComplianceViewGroup()->pointBased();
    }

    public function setUseOverrides($bool)
    {
        $this->useOverrides = $bool;
    }

    public function setPointsOverrideHonorsMaximum($bool)
    {
        $this->pointsOverrideHonorsMaximum = $bool;
    }

    /**
     * If this is a required view, an alternative can be set. If this status
     * is not compliant, the alternative will be evaluated for pts/status/comment
     * instead.
     *
     * @param ComplianceView $view
     * @param bool $addLinks
     * @param callable|null $alternativeCallback If an alternative is used, this will be called with the ComplianceViewStatus object.
     * @param bool $forceAlternativeView
     * @return ComplianceView this instance
     */
    public function setAlternativeComplianceView(ComplianceView $view = null, $addLinks = false, $alternativeCallback = null, $forceAlternativeView = false)
    {
        $this->alternativeView = $view;
        $this->alternativeLinks = $addLinks;
        $this->alternativeCallback = $alternativeCallback;
        $this->forceAlternativeView = $forceAlternativeView;

        return $this;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function hasLinks()
    {
        return (boolean) count($this->links);
    }

    /**
     * @return ComplianceView This instance
     */
    public function emptyLinks()
    {
        $this->links = array();

        return $this;
    }

    public function getMaximumNumberOfPoints()
    {
        if($this->maximumNumberOfPoints !== null) {
            return $this->maximumNumberOfPoints;
        } else if($this->statusPointMapper !== null) {
            return $this->statusPointMapper->getMaximumNumberOfPoints();
        } else {
            return null;
        }
    }

    /**
     * Sets a maximum allowed number of points for this view. The status
     * returned by the implementing class will then be capped to this amount.
     *
     * @param int $value
     * @return ComplianceView This instance.
     */
    public function setMaximumNumberOfPoints($value)
    {
        $this->maximumNumberOfPoints = $value;

        return $this;
    }

    /**
     * Sets a callback that will be called to determine if a view should be
     * evaluated. If it is not evaluated ( i.e. the callback returns false )
     * the group status will receive a ComplianceViewStatus with NA compliance,
     * null points/comment.
     *
     * @param callable $callback Will be given a user obj as a parameter. Must return true or false for whether or not the view should be evaluated.
     * @param int $status If the callback returns false, this status will be assigned.
     * @param mixed $comment
     * @return ComplianceView This instance
     */
    public function setEvaluateCallback($callback, $status = ComplianceStatus::NA_COMPLIANT, $comment = null)
    {
        $this->evaluateCallback = $callback;
        $this->evaluateStatus = $status;
        $this->evaluateComment = $comment;

        return $this;
    }

    /**
     * Marks this view as optional. That is, if true and status is not compliant,
     * it will be remapped to NA.
     *
     * @param boolean $boolean
     * @return ComplianceView
     */
    public function setOptional($boolean)
    {
        $this->optional = $boolean;

        return $this;
    }

    public function setPreMapCallback($callback)
    {
        $this->preMapCallback = $callback;
    }

    /**
     * Sets up a post-evaluation callback (POST mapping) that is called after
     * this view's status is calculated. The callback will be given a
     * ComplianceViewStatus object and can mutate it in any way fit.
     *
     * @param callable $callback
     * @return ComplianceView
     */
    public function setPostEvaluateCallback($callback)
    {
        $this->postEvaluateCallback = $callback;

        return $this;
    }

    /**
     * @return ComplianceStatusPointMapper
     */
    public function getStatusPointMapper()
    {
        return $this->statusPointMapper;
    }

    public function eachBatch(ArrayContainer $batch)
    {

    }

    /**
     * Each view may override this method to add data to the initial user query.
     */
    public function preQuery(Doctrine_Query $query)
    {

    }

    public function setOverrideComplianceProgramRecord(ComplianceProgramRecord $record = null)
    {
        $this->overrideComplianceProgramRecord = $record;
    }

    protected function getEligibleOverride(User $user)
    {
        if($this->useOverrides) {
            // Compliance calculates one user at a time so we cache
            // the results of the last user so future calls are quicker
            // but memory consumption stays low.

            $group = $this->getComplianceViewGroup();
            $complianceProgram = $group ? $group->getComplianceProgram() : null;

            if($complianceProgram) {
                $complianceProgramRecord = $complianceProgram->getComplianceProgramRecord();

                if($complianceProgramRecord) {
                    if(self::$overrideCache['user_id'] != $user->id) {
                        self::$overrideCache['overrides'] = $user->getComplianceViewStatusOverridesSortedByIdDesc();
                        self::$overrideCache['user_id'] = $user->id;
                    }

                    foreach(self::$overrideCache['overrides'] as $override) {
                        if($override->isEligible($this, $this->overrideComplianceProgramRecord)) {
                            return $override;
                        }
                    }
                }
            }
        }

        return null;
    }

    public function setUseOverrideCreatedDate($useOverrideCreatedDate)
    {
        $this->useOverrideCreatedDate = $useOverrideCreatedDate;
    }
    
    private function getOverrideCreatedDate($overrideId)
    {
        return SelectQuery::create()
            ->select('updated_at')
            ->from('compliance_view_status_overrides')
            ->where('id = ?', array($overrideId))
            ->hydrateSingleScalar()
            ->execute();
    }

    private static $overrideCache = array('user_id' => null, 'overrides' => array());
    private $useOverrides = true;
    private $pointsOverrideHonorsMaximum = true;
    private $name;
    private $reportName;
    private $links = array();
    private $viewGroup;
    private $pointStatusMapper;
    private $statusPointMapper;
    private $statusSummaries = array();
    private $attributes = array();
    private $maximumNumberOfPoints = null;
    private $evaluateCallback = null;
    private $evaluateStatus = ComplianceStatus::NA_COMPLIANT;
    private $evaluateComment = null;
    private $optional = false;
    private $alternativeView = null;
    private $alternativeLinks = false;
    private $alternativeCallback = null;
    private $forceAlternativeView = false;
    private $postEvaluateCallback = null;
    private $preMapCallback = null;
    private $overrideComplianceProgramRecord = null;
    private $useOverrideCreatedDate = false;
}