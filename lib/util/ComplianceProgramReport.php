<?php
/**
 * Represents a full report of status for a population.
 */
class ComplianceProgramReport implements Iterator
{
    /**
     * Constructs a ComplianceProgramReport object.
     *
     * @param ComplianceProgram $program The program that each user will be run through.
     * @param Doctrine_Query $query The query on the user table. Will be configured to hydrate on demand.
     * @param User $user The session user running the report
     */
    public function __construct(ComplianceProgram $program, Doctrine_Query $query, User $user)
    {
        $this->program = $program;
        $this->client = $program->getComplianceProgramRecord()->client;
        $this->query = $query;
        $this->user = $user;

        $this->initialize();
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return ComplianceProgramStatus|null
     */
    public function current()
    {
        //echo memory_get_usage()/1024/1024 .' MB <br/>';

        $user = $this->collection->current();

        if($user === null) {
            return null;
        } else {
            if($user->client_id == $this->client->id) {
                $user->setRelated('client', clone $this->client);
            }

            $this->program->setActiveUser($user);
            $status = $this->program->getStatus();
            $this->program->setActiveUser(null);

            return $status;
        }
    }

    public function next()
    {
        $this->collection->next();
    }

    public function key()
    {
        return $this->collection->key();
    }

    public function valid()
    {
        return $this->collection->valid();
    }

    public function rewind()
    {
        $this->collection->rewind();
    }

    protected function initialize()
    {
        $this->program->preQuery($this->query);

        $this->query->setStreamResults();

        $this->collection = $this->query->executeBatch();
    }

    protected $client;
    protected $program;
    protected $query;
    protected $collection;
    protected $user;
}