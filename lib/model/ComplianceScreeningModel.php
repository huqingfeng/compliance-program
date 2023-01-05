<?php

/**
 * Contains methods that query for screening data for compliance for users.
 */
class ComplianceScreeningModel
{
    public function getScreeningRow(User $user, \DateTime $start, \DateTime $end)
    {
        $rowKey = $this->getRowKey($user, $start, $end);

        if($this->rowKey == $rowKey) {
            return $this->row;
        } else {
            $screening = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates(
                $user,
                $start,
                $end,
                array(
                    'merge'            => true,
                    'require_complete' => false
                )
            );

            $this->row = $screening ? $screening : null;
            $this->rowKey = $rowKey;

            return $this->row;
        }
    }

    private function getRowKey(User $user, \DateTime $start, \DateTime $end)
    {
        return "{$user->id},{$start->format('U')},{$end->format('U')}";
    }

    private $row = null;
    private $rowKey = null;
}