<?php
class ComplyWithBMIScreeningTestComplianceView extends ComplyWithScreeningTestComplianceView
{
    public function  __construct($startDate, $endDate, $useBMIField = false)
    {
        parent::__construct($startDate, $endDate);

        $this->useBMIField = $useBMIField;
    }

    public function getTestName()
    {
        return 'bmi';
    }

    public function getDefaultReportName()
    {
        return 'Body Mass Index (BMI)';
    }

    public function getDefaultName()
    {
        return 'comply_with_bmi_screening_test';
    }

    protected function useRawFallbackQuestionValue()
    {
        return true;
    }

    public function getFallbackQuestionId()
    {
        return 602;
    }

    protected function getScreeningRow(User $user, $fields)
    {
        // If they asked for bmi, make sure we get height and weight
        $bmiIndex = null;
        foreach($fields as $key => $field) {
            if($field == 'bmi') {
                $bmiIndex = $key;
                break;
            }
        }
        if($bmiIndex !== null) {
            $fields[] = 'weight';
            $fields[] = 'height';
        }

        $row = parent::getScreeningRow($user, $fields);

        if($this->useBMIField && isset($row['bmi']) && $row['bmi']) {
            return $row;
        }

        // if we have height and weight, make sure we calculate BMI too
        if(isset($row['height']) && isset($row['weight']) && $row['height'] && $row['weight']) {
            if($row['height'] !== null && $row['weight'] !== null && is_numeric($row['height']) && is_numeric($row['weight']) && $row['height'] > 0) {
                $bmi = ($row['weight'] * 703) / ($row['height'] * $row['height']);
            } else {
                $bmi = null;
            }

            $row['bmi'] = $bmi;
        }

        return $row;

    }
}
