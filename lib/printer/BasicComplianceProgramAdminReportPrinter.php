<?php

class BasicComplianceProgramAdminReportPrinter implements ComplianceProgramAdminReportPrinter
{
    /**
     * Adds a callback to this printer that should accept a User object and
     * return a single scalar value.
     *
     * This is intended to add additional information about the User object.
     *
     * @param $text
     * @param $callback
     */
    public function addCallbackField($text, $callback)
    {
        $this->callbacks[$text] = $callback;
    }

    public function addEndCallbackField($text, $callback)
    {
        $this->endCallbacks[$text] = $callback;
    }

    public function addEndStatusFieldCallBack($text, $callback)
    {
        $this->endStatusFieldCallbacks[$text] = $callback;
    }

    public function addMultipleCallbackFields($callback)
    {
        $this->multiCallbacks[] = $callback;
    }

    /**
     * Adds a callback that accepts a ComplianceProgramStatus object and
     * returns a single scalar value.
     *
     * @param $text
     * @param $callback
     */
    public function addStatusFieldCallback($text, $callback)
    {
        $this->statusCallbacks[$text] = $callback;
    }

    /**
     * Adds a callback to this printer that accepts a ComplianceProgramStatus
     * object and returns an array of multiple fields.
     *
     * This is intended to add multiple status-related fields with one callback.
     *
     * @param $callback
     */
    public function addMultipleStatusFieldsCallback($callback)
    {
        $this->multiStatusCallbacks[] = $callback;
    }

    public function addGroupTypeByAlias($alias)
    {
        $groupType = GroupTypeQuery::createQuery('t')
            ->where('t.alias = ?', $alias)
            ->limit(1)
            ->fetchOne();

        if($groupType) {
            $fieldName = sprintf('Groups - %s', $groupType->name);
            $groupTypeId = $groupType->id;

            $this->addCallbackField($fieldName, function (User $user) use ($alias, $groupTypeId) {
                $groups = array();

                foreach($user->groups as $group) {
                    if($group->group_type_id == $groupTypeId) {
                        $groups[] = $group->name;
                    }
                }

                return implode(',', $groups);
            });
        }
    }

    public function setShowTotals($bool)
    {
        $this->showTotals = $bool;
    }

    public function setEmployeeOnly($bool)
    {
        $this->employeeOnly = $bool;
    }

    public function setShowCompliant($program = null, $group = null, $view = null)
    {
        if($program !== null) $this->showProgramCompliant = $program;
        if($group !== null) $this->showGroupCompliant = $group;
        if($view !== null) $this->showViewCompliant = $view;
    }

    public function setShowText($program = null, $group = null, $view = null)
    {
        if($program !== null) $this->showProgramText = $program;
        if($group !== null) $this->showGroupText = $group;
        if($view !== null) $this->showViewText = $view;
    }

    public function setShowStatus($program = null, $group = null, $view = null)
    {
        if($program !== null) $this->showProgramStatus = $program;
        if($group !== null) $this->showGroupStatus = $group;
        if($view !== null) $this->showViewStatus = $view;
    }

    public function setShowPoints($program = null, $group = null, $view = null)
    {
        if($program !== null) $this->showProgramPoints = $program;
        if($group !== null) $this->showGroupPoints = $group;
        if($view !== null) $this->showViewPoints = $view;
    }

    public function setShowComment($program = null, $group = null, $view = null)
    {
        if($program !== null) $this->showProgramComment = $program;
        if($group !== null) $this->showGroupComment = $group;
        if($view !== null) $this->showViewComment = $view;
    }

    public function setShowUserFields($firstName = null, $lastName = null, $dateOfBirth = null, $lastFourSSN = null, $social_security_number = null, $relationshipText = null, $division = null, $userGroup = null, $employeeId = null, $hireDate = null, $workShift = null)
    {
        if($firstName !== null) $this->showUserFirstName = $firstName;
        if($lastName !== null) $this->showUserLastName = $lastName;
        if($dateOfBirth !== null) $this->showUserDateOfBirth = $dateOfBirth;
        if($hireDate !== null) $this->showUserHireDate = $hireDate;
        if($lastFourSSN !== null) $this->showUserLastFourSSN = $lastFourSSN;
        if($social_security_number !== null) $this->showUserSSN = $social_security_number;
        if($relationshipText !== null) $this->showUserRelationshipText = $relationshipText;
        if($division !== null) $this->showUserDivision = $division;
        if($userGroup !== null) $this->showUserGroup = $userGroup;
        if($employeeId !== null) $this->showEmployeeId = $employeeId;
        if($workShift !== null) $this->showWorkShift = $workShift;
    }

    public function setOptIn($enable = false)
    {
        if($enable) $this->showOptIn = true;
    }

    public function setShowUserInsuranceTypes($insurancePlanType = null, $insuranceType = null)
    {
        if($insurancePlanType !== null) $this->showUserInsurancePlanType = $insurancePlanType;
        if($insuranceType !== null) $this->showUserInsuranceType = $insuranceType;
    }

    public function setShowUserContactFields($address = null, $phoneNumbers = null, $emailAddresses = null)
    {
        if($address !== null) $this->showUserAddress = $address;
        if($phoneNumbers !== null) $this->showUserPhoneNumbers = $phoneNumbers;
        if($emailAddresses !== null) $this->showUserEmailAddresses = $emailAddresses;
    }

    public function setShowEmailAddresses($primary = null, $alternate = null, $sendEmployerEmail = null)
    {
        if($primary !== null) $this->showPrimaryEmailAddress = $primary;
        if($alternate !== null) $this->showAlternateEmailAddress = $alternate;
        if($sendEmployerEmail !== null) $this->showSendEmployerEmail = $sendEmployerEmail;
    }

    public function setShowShowRelatedUserFields($firstName = null, $lastName = null, $social_security_number = null)
    {
        if($firstName !== null) $this->showRelatedUserFirstName = $firstName;
        if($lastName !== null) $this->showRelatedUserLastName = $lastName;
        if($social_security_number !== null) $this->showRelatedUserSSN = $social_security_number;
    }

    public function setShowUserLocation($bool)
    {
        $this->showUserLocation = $bool;
    }

    protected function postProcess(array $data)
    {
        return $data;
    }

    public function printAdminReport(ComplianceProgramReport $report, $output)
    {
        global $_db;

        $i = 0;

        $totals = array();

        $userModel = new UserModel();

        $csvData = array();

        $csvHeaders = array();

        foreach($report as $status) {
            $user = $status->getUser();

            if(!$this->showUser($user)) {
                if($user->id != $report->getUser()->id) {
                    $user->delink(false);
                }

                continue;
            }

            if ($this->employeeOnly) {
                if ($user->getRelationshipType(false) == 0){

                } else {
                    continue;
                }
            }

            if($this->showRelatedUserFirstName || $this->showRelatedUserLastName || $this->showRelatedUserSSN) {
                $relatedUser = $user->getRelationshipUser();
            } else {
                $relatedUser = false;
            }

            $userData = array();

            $userData['user_id'] = $user->getID();
            if($this->showUserGroup) $userData['usergroup'] = $user->client->usergroup;
            if($this->showUserFirstName) $userData['first_name'] = $user->first_name;
            if($this->showUserLastName) $userData['last_name'] = $user->last_name;
            if($this->showUserDateOfBirth) $userData['date_of_birth'] = $user->getDateOfBirth();
            if($this->showUserHireDate) $userData['hire_date'] = $user->getHiredate();
            if($this->showUserInsurancePlanType) {
                if (isset($_SESSION['insurance_plan_rename']))
                    $userData[$_SESSION['insurance_plan_rename']] = $user->getInsurancePlanType();
                else
                    $userData['insurance_plan_type'] = $user->getInsurancePlanType();
            }
            if($this->showUserInsuranceType) $userData['insurance_type'] = $user->getInsurancetype();
            if($this->showUserLastFourSSN) $userData['last_four_social_security_number'] = $user->getSocialSecurityNumber(false, true);
            if($this->showUserSSN) $userData['social_security_number'] = $user->getSocialSecurityNumber();
            if($this->showUserRelationshipText) $userData['relationship_text'] = $user->getRelationshipType(true);
            if($this->showUserDivision) $userData['division'] = $user->division;
            if($this->showEmployeeId) $userData['employee_id'] = $user->employeeid;
            if($this->showWorkShift) $userData['work_shift'] = $user->work_shift;

            if($this->showOptIn) {
                $user_id = $user->id;
                $optin = $_db->getResultsForQuery("SELECT dr.id, dr.user_id, df.field_name, df.field_value 
                                                   FROM user_data_records AS dr LEFT JOIN user_data_fields AS 
                                                   df ON df.user_data_record_id = dr.id WHERE dr.type = 'optin_response_2018' 
                                                   and dr.user_id = ". $user_id);
                $optin = $optin[0]['field_value'];

                if ($optin == "0")
                    $userData['optin'] = "Opted Out";
                else if ($optin == "1")
                    $userData['optin'] = "Opted In";
                else
                    $userData['optin'] = "No Selection";
            }

            if($this->showUserLocation) {
                $userData['location'] = $user->getLocation();
            }

            if($this->showUserAddress) {
                $userData['address_address_line_1'] = $user->address_line_1;
                $userData['address_address_line_2'] = $user->address_line_2;
                $userData['address_city'] = $user->city;
                $userData['address_state'] = $user->state;
                $userData['address_zip_code'] = $user->zip_code;
            }

            if($this->showUserPhoneNumbers) {
                $userData['home_phone_number'] = $user->home_phone_number;
                $userData['cell_phone_number'] = $user->cell_phone_number;
                $userData['day_phone_number'] = $user->day_phone_number;
            }

            if($this->showUserEmailAddresses) {
                $userEmailData = $userModel->getEmailAddresses($user->id);

                if($this->showPrimaryEmailAddress) {
                    $userData['primary_email_address'] = isset($userEmailData['Primary']) ?
                        $userEmailData['Primary']['email_address'] : '';
                }

                if($this->showAlternateEmailAddress) {
                    $userData['alternate_email_address'] = isset($userEmailData['Alternate']) ?
                        $userEmailData['Alternate']['email_address'] : '';
                }

                if($this->showSendEmployerEmail) {
                    $userData['send_employer_emails'] = $user->send_employer_emails;
                }
            }

            if($this->showRelatedUserFirstName) $userData['related_user_first_name'] = $relatedUser ? $relatedUser->getFirstName() : null;
            if($this->showRelatedUserLastName) $userData['related_user_last_name'] = $relatedUser ? $relatedUser->getLastName() : null;
            if($this->showRelatedUserSSN) $userData['related_user_social_security_number'] = $relatedUser ? $relatedUser->getSocialSecurityNumber() : null;

            foreach($this->callbacks as $name => $callback) {
                $userData[$name] = $callback($user);
            }

            foreach($this->multiCallbacks as $callback) {
                foreach(call_user_func($callback, $user) as $fName => $fValue) {
                    $userData[$fName] = $fValue;
                }
            }

            $participatedInAnything = false;

            foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                $groupKey = $groupStatus->getComplianceViewGroup()->getReportName();

                if(!isset($totals[$groupKey])) {
                    $totals[$groupKey] = array(
                        'compliant'     => 0,
                        'participating' => 0
                    );
                }

                if($groupStatus->isCompliant()) {
                    $totals[$groupKey]['compliant']++;
                }

                if(
                    $groupStatus->getPoints() > 0 ||
                    $groupStatus->getStatus() == ComplianceStatus::COMPLIANT ||
                    $groupStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT
                ) {
                    $totals[$groupKey]['participating']++;

                    $participatedInAnything = true;
                }

                if($this->showGroupCompliant) $userData[$groupKey.' - Compliant'] = $groupStatus->isCompliant() ? 'Yes' : 'No';
                if($this->showGroupStatus) $userData[$groupKey.' - Status'] = $groupStatus->getStatus();
                if($this->showGroupText) $userData[$groupKey.' - Text'] = $groupStatus->getText();
                if($this->showGroupPoints) $userData[$groupKey.' - Points'] = $groupStatus->getPoints();
                if($this->showGroupComment) $userData[$groupKey.' - Comment'] = $groupStatus->getComment();

                foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                    $viewKey = $this->showGroupNameInViewName ?
                        $groupKey.' - '.$viewStatus->getComplianceView()->getReportName() :
                        $viewStatus->getComplianceView()->getReportName();

                    if(!isset($totals[$viewKey])) {
                        $totals[$viewKey] = array(
                            'compliant'     => 0,
                            'participating' => 0
                        );
                    }

                    if($viewStatus->isCompliant()) {
                        $totals[$viewKey]['compliant']++;
                    }

                    if(
                        $viewStatus->getPoints() > 0 ||
                        $viewStatus->getStatus() == ComplianceStatus::COMPLIANT ||
                        $viewStatus->getStatus() == ComplianceStatus::PARTIALLY_COMPLIANT
                    ) {
                        $totals[$viewKey]['participating']++;
                    }

                    if($this->showViewCompliant) $userData[$viewKey.' - Compliant'] = $viewStatus->isCompliant() ? 'Yes' : 'No';
                    if($this->showViewStatus) $userData[$viewKey.' - Status'] = $viewStatus->getStatus();
                    if($this->showViewText) $userData[$viewKey.' - Text'] = $viewStatus->getText();
                    if($this->showViewPoints) $userData[$viewKey.' - Points'] = $viewStatus->getPoints();

                    if($this->showViewComment) {
                        $comment = $viewStatus->getComment();

                        if($viewStatus->getUsingOverride()) {
                            $comment = "(Using Override) $comment";
                        }

                        $userData[$viewKey.' - Comment'] = $comment;
                    }
                }
            }

            foreach($this->statusCallbacks as $name => $callback) {
                $userData[$name] = call_user_func($callback, $status);
            }

            foreach($this->multiStatusCallbacks as $callback) {
                foreach(call_user_func($callback, $status) as $fName => $fValue) {
                    $userData[$fName] = $fValue;
                }
            }

            if(!isset($totals['Compliance Program'])) {
                $totals['Compliance Program'] = array(
                    'compliant'     => 0,
                    'participating' => 0
                );
            }

            if($status->isCompliant()) {
                $totals['Compliance Program']['compliant']++;
            }

            if($participatedInAnything) {
                $totals['Compliance Program']['participating']++;
            }

            if($this->showProgramCompliant) $userData['Compliance Program - Compliant'] = $status->isCompliant() ? 'Yes' : 'No';
            if($this->showProgramStatus) $userData['Compliance Program - Status'] = $status->getStatus();
            if($this->showProgramText) $userData['Compliance Program - Text'] = $status->getText();
            if($this->showProgramPoints) $userData['Compliance Program - Points'] = $status->getPoints();
            if($this->showProgramComment) $userData['Compliance Program - Comment'] = $status->getComment();

            foreach($this->endCallbacks as $name => $callback) {
                $userData[$name] = $callback($user);
            }

            foreach($this->endStatusFieldCallbacks as $name => $callback) {
                $userData[$name] = call_user_func($callback, $status);
            }

            if($user->id != $report->getUser()->id) {
                $user->delink(false);
            }

            $csvData[] = array(
                'data'     => $userData,
                'sort_key' => "{$user->last_name} {$user->first_name} {$user->id}"
            );

            if(!$i) {
                $csvHeaders = array_keys($userData);
            }

            $i++;
        }

        $csvData = $this->postProcess($csvData);

        usort($csvData, function($a, $b) {
            return strcmp($a['sort_key'], $b['sort_key']);
        });

        $w = 0;

        foreach($csvData as $csvRow) {
            if(!$w) {
                fputcsv($output, array_keys($csvRow['data']));
            }

            fputcsv($output, $csvRow['data']);

            $w++;
        }

        if($this->showTotals) {
            fputcsv($output, array('', '', ''));
            fputcsv($output, array('', '', ''));
            fputcsv($output, array('', '', ''));

            fputcsv($output, array('Name', 'Total Compliant', 'Total Participating'));

            foreach($totals as $name => $total) {
                $compliantPercent = round($total['compliant'] / $i * 100, 2);
                $participatingPercent = round($total['participating'] / $i * 100, 2);

                fputcsv($output, array(
                    $name,
                    "{$total['compliant']} ($compliantPercent%)",
                    "{$total['participating']} ($participatingPercent%)",
                ));
            }
        }
    }

    public function setShowGroupNameInViewName($bool)
    {
        $this->showGroupNameInViewName = $bool;
    }

    protected function showUser(User $user)
    {
        return true;
    }

    private $statusCallbacks = array();
    private $multiStatusCallbacks = array();
    private $callbacks = array();
    private $endCallbacks = array();
    private $endStatusFieldCallbacks = array();
    private $multiCallbacks = array();

    private $showGroupNameInViewName = true;

    private $employeeOnly = false;

    private $showTotals = true;
    private $showUserFirstName = true;
    private $showUserLastName = true;
    private $showUserDateOfBirth = false;
    private $showUserHireDate = false;
    private $showUserLastFourSSN = true;
    private $showUserSSN = false;
    private $showEmployeeId = false;
    private $showUserInsurancePlanType = true;
    private $showUserInsuranceType = true;
    private $showUserRelationshipText = true;
    private $showUserDivision = false;
    private $showUserGroup = false;
    private $showUserLocation = true;
    private $showWorkShift = false;

    private $showOptIn = false;

    private $showUserAddress = false;
    private $showUserPhoneNumbers = false;
    private $showUserEmailAddresses = false;

    private $showPrimaryEmailAddress = true;
    private $showAlternateEmailAddress = true;
    private $showSendEmployerEmail = true;

    private $showRelatedUserFirstName = true;
    private $showRelatedUserLastName = true;
    private $showRelatedUserSSN = false;

    private $showProgramCompliant = true;
    private $showProgramText = false;
    private $showProgramStatus = false;
    private $showProgramPoints = true;
    private $showProgramComment = true;

    private $showGroupCompliant = true;
    private $showGroupText = false;
    private $showGroupStatus = false;
    private $showGroupPoints = true;
    private $showGroupComment = true;

    private $showViewCompliant = true;
    private $showViewText = false;
    private $showViewStatus = false;
    private $showViewPoints = true;
    private $showViewComment = true;
}