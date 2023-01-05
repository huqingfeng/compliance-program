<?php

use hpn\data\stream\SelectableHttpStreamer;

/**
 * Represents a full report of status for a population.
 */
class ParallelComplianceProgramReport extends ComplianceProgramReport
{
    /**
     * @return ComplianceProgramStatus|null
     */
    public function current()
    {
        if(!count($this->buffer)) {
            $this->fillBuffer(true);
        }

        if(!count($this->buffer)) {
            return false;
        }

        $element = reset($this->buffer);

        $user = UserTable::getInstance()->find($element[0]);

        if(!isset($this->clients[$user->client_id])) {
            $this->clients[$user->client_id] = $user->client;
        } else {
            $user->client = clone $this->clients[$user->client_id];
        }

        $this->program->setActiveUser($user);

        $status = new ComplianceProgramStatus($this->program, $user);

        foreach($this->program->getComplianceViewGroups() as $group) {
            $groupStatus = new ComplianceViewGroupStatus($group);

            foreach($group->getComplianceViews() as $viewName => $view) {
                $viewStatus = new ComplianceViewStatus(
                    $view,
                    $element[1][$viewName]['status'],
                    $element[1][$viewName]['points'],
                    $element[1][$viewName]['comment']
                );

                $viewStatus->setUsingOverride($element[1][$viewName]['using_override']);

                foreach($element[1][$viewName]['attributes'] as $attrName => $attrValue) {
                    $viewStatus->setAttribute($attrName, $attrValue);
                }

                $groupStatus->addComplianceViewStatus($viewStatus);
            }

            $group->importStatus($groupStatus);

            $status->addComplianceViewGroupStatus($groupStatus);
        }

        $this->program->importStatus($status);

        $this->program->setActiveUser(null);

        return $status;
    }

    public function next()
    {
        if(!count($this->buffer)) {
            $this->fillBuffer(true);
        }

        array_shift($this->buffer);

        $this->i++;
    }

    public function key()
    {
        return $this->i;
    }

    public function valid()
    {
        return count($this->buffer) || !$this->streamer->isEmpty();
    }

    public function rewind()
    {
        $this->initialize();
    }

    protected function fillBuffer($block = false)
    {
        while(!count($this->buffer) && !$this->streamer->isEmpty()) {
            foreach($this->streamer->dequeue($block ? 300 : 0) as $requestData) {
                $decodedRequestData = json_decode($requestData, true);

                if($decodedRequestData === null) {
                    throw new \RuntimeException("Invalid request data: {$requestData}");
                }

                foreach($decodedRequestData as $userId => $data) {
                    $this->buffer[] = array($userId, $data);
                }
            }
        }
    }

    protected function initialize()
    {
        $this->i = 0;
        $this->buffer = array();
        $this->streamer = new SelectableHttpStreamer(
            sfConfig::get('app_parallelization_level')
        );

        $programId = $this->program->getComplianceProgramRecord()->id;

        $query = clone $this->query;

        $this->program->preQuery($query);

        $query->select('id');
        $query->setHydrationMode(Doctrine_Core::HYDRATE_SINGLE_SCALAR);

        $this->collection = $query->execute();

        $baseParameters = array(
            "api_key=".self::API_KEY,
            "compliance_program_record_id={$programId}"
        );

        $current = $baseParameters;

        $limit = sfConfig::get('app_parallelization_units');
        $i = 0;
        $j = 0;
        $total = count($this->collection);

        foreach((array)$this->collection as $userId) {
            $j++;

            $current[] = "ids[]={$userId}";

            if(count($current)- count($baseParameters) == $limit || $j == $total) {
                $this->streamer->enqueueRequest(
                    $i,
                    '127.0.0.1',
                    80,
                    false,
                    'GET',
                    '/compliance_programs/calculateComplianceProgramStatus?'.implode('&', $current)
                );

                $current = $baseParameters;

                $i++;
            }
        }
    }

    const API_KEY = '63952182-b86c-4cd0-bc55-16340bbee8ef';
    protected $clients = array();
    protected $streamer;
    protected $buffer;
    protected $i;
    protected $client;
    protected $program;
    protected $query;
    protected $collection;
}