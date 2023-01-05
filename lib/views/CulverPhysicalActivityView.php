<?php
require_once sprintf('%s/web/wms3/framework/modules/beacon/traits/LightActivitiesData.php', sfConfig::get('sf_root_dir'));

use hpn\steel\query\SelectQuery;


class CulverPhysicalActivityView extends CompleteActivityComplianceView
{
	
    use LightActivitiesData;
    
    public function getActivity()
    {
        return new ActivityTrackerActivity($this->activityID);
    }

    public function _setID($id)
    {
        $this->activityID = $id;
        $this->emptyLinks();
        $this->addLink(new ActivityComplianceViewLink($this->getActivity()));

        return $this;
    }

    public function setFractionalDivisorForPoints($value)
    {
        $this->fractionalPointDivisor = $value;

        return $this;
    }

    public function setMinutesDivisorForPoints($value)
    {
        $this->pointDivisor = $value;

        return $this;
    }

    public function setPointsMultiplier($value)
    {
        $this->pointsMultiplier = $value;
    }

    /**
     * @param int $value
     * @return PhysicalActivityComplianceView
     */
    public function setMonthlyPointLimit($value)
    {
        $this->monthlyPointLimit = $value;

        return $this;
    }

    public function getStatus(User $user) {
        
        $records = $this->getActivity()->getRecords($user, $this->getStartDate(), $this->getEndDate());
        $activityPoints = 0;
        $activitySteps = 0;
        $todays_steps = 0;
        foreach($records as $record) {
            

            
            $answers = $record->getQuestionAnswers();

            $activity_date = date('Y-m-d', strtotime($answers[ActivityTrackerQuestion::ACTIVITY]->getRecord()->getDate()));
            // 
            $activities = $this->getStepsToMinutes();  

            if(!isset($monthMinutes[$answers[ActivityTrackerQuestion::ACTIVITY]->getAnswer()])) {
                $monthMinutes[$answers[ActivityTrackerQuestion::ACTIVITY]->getAnswer()] = 0;
            }

            if(isset($answers[ActivityTrackerQuestion::ACTIVITY], $answers[43]) && $answers[ActivityTrackerQuestion::ACTIVITY]->getAnswer() == 'Other' && $answers[43]->getAnswer() == 'SIM Activity') {
                
                // This is a straightup hack for H&H to not include SIM Bonus points
                // as part of the monthly. Not sure how to best implement this yet.
//                $totalPoints += $this->getPoints($answers[ActivityTrackerQuestion::MINUTES]->getAnswer());
            } else if(isset($answers[ActivityTrackerQuestion::MINUTES])) {

                if($activity_date == date('Y-m-d', time()) && isset($activities[$answers[ActivityTrackerQuestion::ACTIVITY]->getAnswer()])) {
                    
                    $todays_steps+= $activities[$answers[ActivityTrackerQuestion::ACTIVITY]->getAnswer()] * $answers[ActivityTrackerQuestion::MINUTES]->getAnswer();
                }
                $monthMinutes[$answers[ActivityTrackerQuestion::ACTIVITY]->getAnswer()] += $answers[ActivityTrackerQuestion::MINUTES]->getAnswer();
            }
        }

        if(isset($monthMinutes)) {
            foreach($monthMinutes as $activity => $minutes) {

                if(isset($activities[$activity])) {
                    $stats = $this->getPoints($minutes, $activities[$activity]);

                    $activityPoints+= $stats['points'];

//                if($activityPoints > $this->monthlyPointLimit) {
//                    $activityPoints = $this->monthlyPointLimit;
//                }
                    $activitySteps+= $stats['steps'];
                }
            }
        }


        $activityPoints = round($activityPoints, 1);


        $fitness_points = $this->getFitnessTrackerPoints($user);
        $todays_steps+= $fitness_points['todays_steps'];
        $totalPoints = min(260, $fitness_points['points'] + $activityPoints);
        

        $this->setAttribute('steps_data', ['activities' => ['points' => $activityPoints, 'steps' => $activitySteps], 'fitnessTracker' => $fitness_points, 'todays_steps' => $todays_steps]);

        return $this->fixStatus(
            new ComplianceViewStatus($this, null, $totalPoints),
            $records
        );
    }

    public function getFitnessTrackerPoints($user) {
    	
        $steps = 0;
        $todays_steps = 0;
        $user_id = $user->id;
        $default_vals = ['points' => 0, 'steps' => 0, 'todays_steps' => 0];
        $participant_id = SelectQuery::create()
        ->select('id')
        ->from('wms3.fitnessTracking_participants')
        ->where('wms1Id = ?', [$user_id])
        ->execute()
        ->toArray();
        
        if(empty($participant_id)) {
        	return $default_vals;
        }

        $fitness_tracker_steps = SelectQuery::create()
        ->select('value, activity_date')
        ->from("wms3.fitnessTracking_data")
        ->where('type = 1')
        ->andWhere('insert_dt BETWEEN ? and ?', [date('Y-m-d', $this->getStartDate()), date('Y-m-d',$this->getEndDate()).' 23:59:59'])
        ->andWhere('activity_date BETWEEN ? and ?', [date('Y-m-d', $this->getStartDate()), date('Y-m-d',$this->getEndDate()).' 23:59:59'])
        ->andWhere('participant = ?', [$participant_id[0]['id']])
        ->execute()
        ->toArray();
        
        
        if(empty($fitness_tracker_steps)) {
        	return $default_vals;
        }
        foreach($fitness_tracker_steps as $value) {
            if($value['activity_date'] == date('Y-m-d', time())) {
                $todays_Steps+= $value['value'];
            }
            $steps+= $value['value'];
        }
        return ['points' => (round(($steps / 2000), 1)), 'steps' => $steps, 'todays_steps' => $todays_steps];
    }


    /**
     * Translates minutes into a point figure.
     *
     * @param int $minutes
     * @return int
     */
    private function getPoints($minutes, $conversion)
    {
        $steps = ($conversion * $minutes);
        $points = ($steps / 2000);
        return ['points' => $points, 'steps' => $steps];

        return $this->pointsMultiplier * round(((int) ($this->fractionalPointDivisor * $minutes / $this->pointDivisor)) / $this->fractionalPointDivisor, 2);
    }

    private $activityID = ActivityTrackerActivity::PHYSICAL_ACTIVITY;
    private $fractionalPointDivisor = 4;
    private $pointsMultiplier = 1;
    private $pointDivisor = 1;
    private $monthlyPointLimit = null;
}

