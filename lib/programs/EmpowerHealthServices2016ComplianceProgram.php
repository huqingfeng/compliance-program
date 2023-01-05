<?php
class EmpowerHealthServices2016CompleteScreeningComplianceView extends CompleteScreeningComplianceView
{
    public function getData(User $user)
    {
        $clientSet = sfConfig::get('mod_legacy_email_upon_new_reports_ehs_client_ids', array());

        $secondFieldsClients = isset($clientSet['second_set']) ? $clientSet['second_set'] : array();

        $thirdFieldsClients = isset($clientSet['third_set']) ? $clientSet['third_set'] : array();

        $fourthFieldsClients = isset($clientSet['fourth_set']) ? $clientSet['fourth_set'] : array();

        if(in_array($user->client_id, $secondFieldsClients)) {
            $requiredFields = $this->getSecondRequiredFields();
        } else if(in_array($user->client_id, $thirdFieldsClients)) {
            $requiredFields = $this->getThirdRequiredFields();
        } else if(in_array($user->client_id, $fourthFieldsClients)) {
            $requiredFields = $this->getFourthRequiredFields();
        } else {
            $requiredFields = $this->getFirstRequiredFields();
        }

        $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter()
            )
        );

        foreach($requiredFields as $requiredField) {
            if(!isset($data[$requiredField]) || empty($data[$requiredField])) {
                return array();
            }
        }

        return $data;
    }

    protected function getFirstRequiredFields()
    {
        return array(
            'wbc',
            'rbc',
            'hemoglobin',
            'hematocrit',
            'mcv',
            'mch',
            'mchc',
            'rdw',
            'plateletcount',
            'cholesterol',
            'hdl',
            'totalhdlratio',
            'triglycerides',
            'glucose',
            'bun',
            'creatinine',
            'totalprotein',
            'albumin',
            'gfr',
            'gfraa',
            'totalbilirubin',
            'bilirubindirect',
            'alkalinephosphatase',
            'ggt',
            'ast',
            'alt',
            'sodium',
            'potassium',
            'chloride',
            'magnesium',
            'calcium',
            'phosphorus',
            'ld',
            'uricacid',
            'tfour',
            'totaliron',
            'systolic',
            'diastolic',
            'height',
            'weight'
        );
    }

    protected function getSecondRequiredFields()
    {
        return array(
            'wbc',
            'rbc',
            'hemoglobin',
            'hematocrit',
            'mcv',
            'mch',
            'mchc',
            'rdw',
            'plateletcount',
            'cholesterol',
            'hdl',
            'totalhdlratio',
            'triglycerides',
            'glucose',
            'bun',
            'creatinine',
            'totalprotein',
            'albumin',
            'gfr',
            'gfraa',
            'totalbilirubin',
            'bilirubindirect',
            'alkalinephosphatase',
            'ggt',
            'ast',
            'alt',
            'sodium',
            'potassium',
            'chloride',
            'magnesium',
            'calcium',
            'phosphorus',
            'ld',
            'uricacid',
            'tfour',
            'totaliron'
        );
    }

    protected function getThirdRequiredFields()
    {
        return array(
            'cholesterol',
            'systolic',
            'diastolic',
            'height',
            'weight'
        );
    }

    protected function getFourthRequiredFields()
    {
        return array(
            'cholesterol',
            'hdl',
            'ldl',
            'triglycerides',
            'totalhdlratio',
            'glucose',
            'height',
            'weight',
            'systolic',
            'diastolic'
        );
    }
}


class EmpowerHealthServices2016ComplianceProgram extends ComplianceProgram
{
    public function loadGroups()
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $required = new ComplianceViewGroup('Required');

        $screening = new EmpowerHealthServices2016CompleteScreeningComplianceView($startDate, $endDate);
        $screening->setName('screening');

        $required->addComplianceView($screening);

        $hra = new CompleteHRAComplianceView($startDate, $endDate);

        $required->addComplianceView($hra);

        $this->addComplianceViewGroup($required);
    }
}