<?php
  use hpn\steel\query\SelectQuery;

  class SandCElectric2023CompleteScreeningComplianceView extends CompleteScreeningComplianceView {
      public function getData(User $user) {
        return ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
            new DateTime('@'.$this->getStartDate()),
            new DateTime('@'.$this->getEndDate()),
            array(
                'require_online'   => false,
                'merge'            => true,
                'require_complete' => false,
                'filter'           => $this->getFilter(),
                'required_fields'  => array('cholesterol')
            )
        );
      }
  }

  class SandCElectric2023ComplianceProgram extends ComplianceProgram {
    public function getTrack(User $user) {
      if($this->lastTrack && $this->lastTrack['user_id'] == $user->id) {
        return $this->lastTrack['track'];
      } else {
        $track = $user->getGroupValueFromTypeName('S&C Track 2023', 'SCENARIO C');

        $this->lastTrack = array('user_id' => $user->id, 'track' => $track);

        return $track;
      }
    }

    public function loadGroups() {
      global $_user;

      $this->setComplianceStatusMapper(new AlternativeComplianceStatusMapper());

      $startDate = $this->getStartDate();
      $endDate = $this->getEndDate();

      $req = new ComplianceViewGroup('core', 'Requirements');

      $ampSignup = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $ampSignup->setName('amp_signup');
      $ampSignup->setReportName('<b>A.</b> AMP UP! Sign Up! Card');
      $ampSignup->setAttribute('deadline', '03/31/2023');
      $ampSignup->addLink(new Link('Get AMP UP! Card', '/resources/10357/2019_AMP_UP_Sign_Up_Card.pdf'));
      $ampSignup->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));

      $req->addComplianceView($ampSignup);

      $screening = new SandCElectric2023CompleteScreeningComplianceView($startDate, $endDate);
      $screening->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $screening->setAttribute('deadline', '03/31/2023');
      $screening->setReportName('<b>B.</b> Complete Biometric Screening');
      $screening->emptyLinks();
      $screening->setName('screening');
      $screening->addLink(new Link('Upload Results', '/content/chp-document-uploader'));

      $req->addComplianceView($screening);

      $hra = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $hra->setReportName('<b>C.</b> Complete Health Risk Assessment');
      $hra->setAttribute('deadline', '06/30/2023');
      $hra->emptyLinks();
      $hra->setName('hra');
      $hra->addLink(new Link('Health Risk Assessment', "/surveys/39"));
      $hra->addLink(new Link('Health Risk Assessment (PDF)', '/resources/10659/032021_Health_Risk_Assessment_AU.pdf'));
      $hra->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));
      $hra->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $view = $status->getComplianceView();

        $surveyView = new CompleteSurveyComplianceView(39);
        $surveyView->setComplianceViewGroup($view->getComplianceViewGroup());
        $surveyView->setName('alternative_'.$view->getName());

        $surveyStatus = $surveyView->getStatus($user);

        if($surveyStatus->getStatus() == ComplianceStatus::COMPLIANT
          && date('Y-m-d', strtotime($surveyStatus->getComment())) >= '2022-10-01')
          $status->setStatus(ComplianceStatus::COMPLIANT);
      });

      $req->addComplianceView($hra);

      $physView = $this->generateBaseView(
        'physical',
        '<b>D.</b> Complete 1 Annual Physical',
        '09/30/2023',
        array(new Link('Exam Confirmation Form', '/resources/10484/Form_855_Exam_Confirmation_10_19.pdf'))
      );
      $physView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(1, 0, 0, 0));

      $req->addComplianceView($physView);

      $inRangeView = $this->generateBaseView(
        'in_range',
        '<b>E.</b> No Next Steps',
        '',
        array(),
        ComplianceStatus::COMPLIANT
      );

      $req->addComplianceView($inRangeView);

      $focusClasses = $this->generateBaseView(
        '4_focus_classes',
        'Advocate Aurora Health Program: Attend 4 Focus Classes',
        '09/30/2023',
        array(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=144552', false, '_self'))
      );
      $focusClasses->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $self_reported = new CompleteArbitraryActivityComplianceView('2022-10-01', '2023-09-30', 144552, 1);
        $points = $status->getPoints() + $self_reported->getStatus($user)->getPoints();
        $status->setPoints($points);

        if ($points >= 4)
          $status->setStatus(ComplianceStatus::COMPLIANT);
        elseif ($points >= 1)
          $status->setStatus(ComplianceStatus::PARTIALLY_COMPLIANT);
      });

      $req->addComplianceView($focusClasses);

      $dietician = $this->generateBaseView(
        'dietician',
        'Advocate Aurora Health Program: 3-Month Engagement with Registered Dietician',
        '09/30/2023'
      );

      $req->addComplianceView($dietician);

      $nurse = $this->generateBaseView(
        'nurse',
        'Advocate Aurora Health Program: 3-Month Engagement with Registered Nurse',
        '09/30/2023'
      );

      $req->addComplianceView($nurse);

      $livongo = $this->generateBaseView(
        'livongo',
        'BCBS Program: Participation in the appropriate Livongo Program as determined by the Livongo Program Manager',
        '09/30/2023',
        array(new Link('Enter/Update Info', '/content/12048?action=showActivity&activityidentifier=144553', false, '_self'))
      );
      $livongo->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $self_reported = new CompleteArbitraryActivityComplianceView('2022-10-01', '2023-09-30', 144553, 1);
        $points = $self_reported->getStatus($user)->getPoints();
        $status->setPoints($points);

        if ($points >= 1)
          $status->setStatus(ComplianceStatus::COMPLIANT);
      });

      $req->addComplianceView($livongo);

      $attestationForm = $this->generateBaseView(
        'attestation_form',
        'Advocate Aurora Health Program: Submission of the completed Physician Attestation Form 1087 that indicates you are managing your condition with the help of your PCP or other appropriate medical professional',
        '09/30/2023',
        array(new Link('Physician Attestation Form', '/pdf/clients/sandc/Physician_Attestation_Form_1087.pdf', false, '_blank'))
      );

      $req->addComplianceView($attestationForm);

      $this->addComplianceViewGroup($req);
    }

    public function getProgramReportPrinter($preferredPrinter = null) {
      return new SandCElectric2023ComplianceProgramReportPrinter();
    }

    public function getAdminProgramReportPrinter() {
      $printer = new BasicComplianceProgramAdminReportPrinter();

      $program = $this;

      $printer->setShowUserFields(null, null, true, false, true, null, null, null, true);
      $printer->setShowUserContactFields(true, null, true);

      $printer->addCallbackField('employee_id', function (User $user) {
        return $user->employeeid;
      });

      $printer->addCallbackField('member_id', function (User $user) {
        return $user->member_id;
      });

      $printer->addCallbackField('track', function (User $user) {
        $track = $user->getGroupValueFromTypeName('S&C Track 2023', 'SCENARIO C');

        return $track;
      });

      $printer->addCallbackField('Health Action Plan Completion', function (User $user) use($program) {
        $program->setActiveUser($user);
        $GHIndicator = $program->getGHIndicator();

        return $GHIndicator;
      });

      $printer->addCallbackField('Weigh In Screening Date', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        return isset($data['date']) ? $data['date'] : null;
      });

      $printer->addCallbackField('Weigh In - Height', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        return isset($data['height']) ? $data['height'] : null;
      });

      $printer->addCallbackField('Weigh In - Weight', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        return isset($data['weight']) ? $data['weight'] : null;
      });

      $printer->addCallbackField('Weigh In - BMI', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0) {
            $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);
        }

        return isset($bmi) ? $bmi : null;
      });

      $printer->addCallbackField('Weigh In - Body Fat ', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        return isset($data['bodyfat']) ? $data['bodyfat'] : null;
      });

      $printer->addCallbackField('Weigh In - Program Goal', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0)
          $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);

        $programGoal = null;
        if(isset($bmi) && $bmi >= 18.5 && $bmi <= 24.9) {
          $programGoal = 'MAINTAIN';
        } elseif (isset($bmi) && $bmi > 24.9) {
          $idealBMI = 24.9;

          $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
          $idealDecreasedWeight = $data['weight'] - ($data['weight'] * 0.04);

          $programGoal = $idealBMIWeight >= $idealDecreasedWeight ? 'Reach Health BMI' : 'Change Initial Body Weight by 4%';
        } elseif (isset($bmi) && $bmi < 18.5) {
          $idealBMI = 18.5;

          $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
          $idealIncreasedWeight = $data['weight'] + ($data['weight'] * 0.04);

          $programGoal = $idealBMIWeight <= $idealIncreasedWeight ? 'Reach Health BMI' : 'Change Initial Body Weight by 4%';
        }

        return $programGoal;
      });

      $printer->addCallbackField('Weigh In - Goal Weight', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0)
          $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);

        $goalWeight = null;
        if(isset($bmi) && $bmi >= 18.5 && $bmi <= 24.9) {
          $goalWeight = $data['weight'];
        } elseif (isset($bmi) && $bmi > 24.9) {
          $idealBMI = 24.9;

          $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
          $idealDecreasedWeight = $data['weight'] - ($data['weight'] * 0.04);

          $goalWeight = round($idealBMIWeight >= $idealDecreasedWeight ? $idealBMIWeight : $idealDecreasedWeight, 2);
        } elseif (isset($bmi) && $bmi < 18.5) {
          $idealBMI = 18.5;

          $idealBMIWeight = ($idealBMI * $data['height'] * $data['height']) / 703.0;
          $idealIncreasedWeight = $data['weight'] + ($data['weight'] * 0.04);

          $goalWeight = round($idealBMIWeight <= $idealIncreasedWeight ? $idealBMIWeight : $idealIncreasedWeight, 2);
        }

        return $goalWeight;
      });

      $printer->addCallbackField('Weight Out Screening Date', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        return isset($data['date']) ? $data['date'] : null;
      });

      $printer->addCallbackField('Weight Out - Height', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        return isset($data['height']) ? $data['height'] : null;
      });

      $printer->addCallbackField('Weight Out - Weight', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        return isset($data['weight']) ? $data['weight'] : null;
      });

      $printer->addCallbackField('Weight Out - BMI', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        if(isset($data['weight']) && isset($data['height']) && $data['height']> 0 && $data['height'] > 0)
          $bmi = ($data['weight'] * 703.0) / ($data['height'] * $data['height']);

        return isset($bmi) ? $bmi : null;
      });

      $printer->addCallbackField('Weight Out - Body Fat ', function (User $user) {
        $data = SandCElectric2023ComplianceProgram::getScreeningData($user);

        return isset($data['bodyfat']) ? $data['bodyfat'] : null;
      });

      return $printer;
    }

    public static function getScreeningData(User $user, $startDate = '2022-10-01', $endDate = '2023-09-30') {
      $data = ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user,
        new DateTime($startDate),
        new DateTime($endDate),
        array(
          'require_online'   => false,
          'merge'            => false,
          'order'             => true,
          'require_complete' => false,
          'required_fields'  => array('weight', 'height')
        )
      );

      return isset($data[0]) ? $data[0] : null;
    }

    public function getGHIndicator() {
      return $this->GHIndicator;
    }

    public function getAllRequiredCoreViews() {
      return array(
        'amp_signup',
        'screening',
        'hra',
        'physical'
      );
    }

    public function getTrackRequiredCoreViews(User $user) {
      $requiredCoreViews = array(
        'IN RANGE'  => array(
          'in_range'
        ),
        'SCENARIO A'  => array(
          '4_focus_classes',
          'attestation_form'
        ),
        'SCENARIO B'  => array(
          '4_focus_classes',
          'dietician',
          'livongo',
          'attestation_form'
        ),
        'SCENARIO C'  => array(
          'nurse',
          'livongo',
          'attestation_form'
        ),
      );

      $track = trim($this->getTrack($user));

      return $requiredCoreViews[$track];
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status) {
      $user = $status->getUser();
      $track = trim($this->getTrack($user));

      $allRequiredCoreViews = $this->getAllRequiredCoreViews();
      $trackRequiredCoreViews = $this->getTrackRequiredCoreViews($user);

      $allRequiredCompliant = true;
      foreach($allRequiredCoreViews as $allRequiredView) {
        $viewStatus = $status->getComplianceViewStatus($allRequiredView);
        if($viewStatus->getStatus() != ComplianceStatus::COMPLIANT)
          $allRequiredCompliant = false;
      }

      $requiredCoreCompliant = false;

      foreach($trackRequiredCoreViews as $requiredView) {
        $viewStatus = $status->getComplianceViewStatus($requiredView);
        if($viewStatus->getStatus() == ComplianceStatus::COMPLIANT)
          $requiredCoreCompliant = true;
      }

      if($requiredCoreCompliant)
        foreach($trackRequiredCoreViews as $requiredView) {
          $viewStatus = $status->getComplianceViewStatus($requiredView);
          $viewStatus->setStatus(ComplianceStatus::COMPLIANT);
        }

      if($allRequiredCompliant && $requiredCoreCompliant)
        $status->setStatus(ComplianceStatus::COMPLIANT);
      else
        $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
    }

    private function generateBaseView($name, $reportName, $deadline, array $links = array(), $defaultStatus = ComplianceStatus::NOT_COMPLIANT) {
      $ageAppropriate = new PlaceHolderComplianceView($defaultStatus);
      $ageAppropriate->setName($name);
      $ageAppropriate->setReportName($reportName);
      $ageAppropriate->setAttribute('deadline', $deadline);
      $ageAppropriate->setAllowPointsOverride(true);

      foreach($links as $link)
        $ageAppropriate->addLink($link);

      return $ageAppropriate;
    }

    private $hideMarker = '<span class="hide-view">hide</span>';
    private $lastTrack = null;
    private $GHIndicator = 'No';
  }

  class SandCElectric2023ComplianceProgramReportPrinter extends BasicComplianceProgramReportPrinter {
    public function printReport(ComplianceProgramStatus $status) {
      $program = $status->getComplianceProgram();
      $user = $status->getUser();
      $core_views = $program->getAllRequiredCoreViews();
      $track_views = $program->getTrackRequiredCoreViews($user);
      $track = $program->getTrack($user);

      $fifth_activity_name = count($track_views) > 1 ? '<b>E.</b> Next Steps: PICK ONE of the action items below: <br /> <br />' : '';
      $fifth_activity_status = 0;

      foreach ($track_views as $index => $name) {
        $view = $status->getComplianceViewStatus($name);

        if ($index) $fifth_activity_name .= '<br /> <br /> <b>OR</b> <br /> <br />';
        $fifth_activity_name .= $view->getComplianceView()->getReportName(true);
        $fifth_activity_status = max($fifth_activity_status, $view->getStatus());
      }

      ?>

      <style type="text/css">
        html {
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        #page #wms3-content h1,
        #page #content h1 {
          padding: 0.5rem;
          background-color: #8587b9;
          color: white;
          text-align: center;
          font-family: Roboto;
          font-size: 1.5rem;
          font-weight: bold;
          border-radius: 0.25rem;
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        #page #wms3-content aside,
        #page #content aside {
          padding: 0 1rem;
          background-color: #f8fafc;
          border: 1px solid #e7eaf2;
          border-radius: 0.25rem;
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        #page #wms3-content aside p:first-of-type + p,
        #page #content aside p:first-of-type + p {
          position: relative;
          padding-left: 3rem;
        }

        #page #wms3-content aside i,
        #page #content aside i {
          background-color: transparent !important;
          text-align: center;
          margin-top: -0.95rem;
          font-size: 1.25rem;
        }

        #page #wms3-content aside i,
        #page #wms3-content q,
        #page #content aside i,
        #page #content q {
          position: absolute;
          top: 50%;
          left: 0.5rem;
        }

        #page #wms3-content q,
        #page #content q {
          margin-top: -1.2rem;
        	background-color: #ffb65e;
        	text-align: left;
        }

        #page #wms3-content q:before,
        #page #wms3-content q:after,
        #page #content q:before,
        #page #content q:after {
        	content: '';
        	position: absolute;
        	background-color: inherit;
        }

        #page #wms3-content q,
        #page #wms3-content q:before,
        #page #wms3-content q:after,
        #page #content q,
        #page #content q:before,
        #page #content q:after {
          display: inline-block;
        	width:  1.5rem;
        	height: 1.5rem;
          border-radius: 0;
        	border-top-right-radius: 30%;
        }

        #page #wms3-content q,
        #page #content q {
        	transform: rotate(-60deg) skewX(-30deg) scale(1,.866);
        }

        #page #wms3-content q:before,
        #page #content q:before {
        	transform: rotate(-135deg) skewX(-45deg) scale(1.414,.707) translate(0,-50%);
        }

        #page #wms3-content q:after,
        #page #content q:after {
        	transform: rotate(135deg) skewY(-45deg) scale(.707,1.414) translate(50%);
        }

        #page #wms3-content table,
        #page #content table {
          border-collapse: separate;
          table-layout: fixed;
          width: 100%;
          line-height: 1.5rem;
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        #page #wms3-content table + table,
        #page #content table + table {
          margin-top: 1rem;
        }

        #page #wms3-content th,
        #page #content th {
          padding: 1rem;
          background-color: #014265;
          color: white;
          border: 1px solid #014265;
          font-weight: bold;
          text-align: center;
        }

        #page #wms3-content th:first-of-type,
        #page #content th:first-of-type {
          border-top-left-radius: 0.25rem;
          text-align: left;
        }

        #page #wms3-content th:last-of-type,
        #page #content th:last-of-type {
          border-top-right-radius: 0.25rem;
        }

        #page #wms3-content td,
        #page #content td {
          padding: 1rem;
          color: #57636e;
          border-left: 1px solid #e8e8e8;
          border-bottom: 1px solid #e8e8e8;
          text-align: center;
        }

        #page #wms3-content tr:last-of-type td:first-of-type,
        #page #content tr:last-of-type td:first-of-type {
          border-bottom-left-radius: 0.25rem;
        }

        #page #wms3-content td:last-of-type,
        #page #content td:last-of-type {
          border-right: 1px solid #e8e8e8;
        }

        #page #wms3-content tr:last-of-type td:last-of-type,
        #page #content tr:last-of-type td:last-of-type {
          border-bottom-right-radius: 0.25rem;
        }

        #page #wms3-content a,
        #page #content a {
          display: inline-block;
          color: #0085f4 !important;
          font-size: 1rem;
          text-transform: uppercase;
          text-decoration: none !important;
        }

        #page #wms3-content a + a,
        #page #content a + a {
          margin-top: 1rem;
        }

        #page #wms3-content a:hover,
        #page #wms3-content a:focus,
        #page #wms3-content a:active,
        #page #content a:hover,
        #page #content a:focus,
        #page #content a:active {
          color: #0052C1 !important;
          text-decoration: none !important;
        }

        #page #wms3-content i,
        #page #content i {
          width: 1.5rem;
          height: 1.5rem;
          line-height: 1.5rem;
          background-color: #ced2db;
          border-radius: 999px;
          color: white;
          font-size: 1.25rem;
        }

        #page #wms3-content i.fa-check,
        #page #content i.fa-check {
          background-color: #4fd3c2;
        }

        #page #wms3-content i.fa-exclamation,
        #page #content i.fa-exclamation {
          background-color: #ffb65e;
        }

        #page #wms3-content i.fa-times,
        #page #content i.fa-times {
          background-color: #dd7370;
        }

        #legend {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin: 2rem 0;
        }

        #legend div {
          display: flex;
          justify-content: center;
          flex-wrap: wrap;
        }

        #legend h2 {
          margin: 2rem 0;
          color: #23425e;
          font-size: 1.75rem;
          letter-spacing: 0.4rem;
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        #legend p {
          position: relative;
          width: 11rem;
          height: 2.5rem;
          line-height: 2.5rem;
          margin: 0.25rem 0.25rem;
          padding-left: 1.25rem;
          background-color: #ebf1fa;
          text-align: center;
          font-size: 1.1rem;
        }

        #legend i {
          position: absolute;
          left: 1rem;
          top: 50%;
          margin-top: -0.75rem;
        }

        @media only screen and (max-width: 1200px) {
          #legend {
            flex-direction: column;
            align-items: flex-start;
          }

          #legend > div {
            align-content: flex-start;
          }
        }

        @media only screen and (max-width: 1060px) {
          #page #wms3-content table,
          #page #content table {
            table-layout: auto;
          }
        }
      </style>

      <h1>2023 AMP UP! Report Card</h1>

      <aside>
        <p><?= $status->getUser()->getFullName() ?></p>
        <p>
          <b>
            <q></q>
            <i class="fas fa-exclamation"></i>
            Note: Some actions you took within the past 30-60 days may not
            show until next month. Please allow 30-60 days for updates relying
            on claims and/or any required forms you have submitted.
          </b>
        </p>
        <p>
          If you have any questions/concerns about your report card please
          contact the AMP UP! Help Desk
          <a href="tel:+18007615856">800-761-5856</a>
          (M-F from 8am-8pm CST)
        </p>
      </aside>

      <div id="legend">
        <h2><?= $track ?></h2>
        <div>
          <div>
            <p><i class="far fa-check"></i> CRITERIA MET</p>
            <p><i class="fas fa-exclamation"></i> IN PROGRESS</p>
          </div>
          <div>
            <p><i class="far fa-times"></i> NOT STARTED</p>
            <p><i class="far fa-minus"></i> N/A</p>
          </div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th colspan="4">1. Requirements</th>
            <th>Deadline</th>
            <th>Date Done</th>
            <th>Count Completed</th>
            <th>Status</th>
            <th>Links</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($core_views as $name) { ?>
            <?php $view = $status->getComplianceViewStatus($name) ?>
            <tr>
              <td colspan="4" style="text-align: left;">
                <?= $view->getComplianceView()->getReportName(true) ?>
              </td>
              <td>
                <?= $view->getComplianceView()->getAttribute('deadline', '') ?>
              </td>
              <td>
                <?= $view->getComment() ?>
              </td>
              <td>
                <?= $view->getPoints() != '' ? $view->getPoints() : 0 ?>
              </td>
              <td>
                <i class="<?= getStatus($view->getStatus()) ?>"></i>
              </td>
              <td>
                <?php
                  foreach ($view->getComplianceView()->getLinks() as $link)
                    echo $link->getHTML();
                ?>
              </td>
            </tr>
          <?php } ?>
          <?php foreach ($track_views as $index => $name) { ?>
            <?php $view = $status->getComplianceViewStatus($name) ?>
            <tr>
              <?php if (!$index) { ?>
                <td colspan="4" rowspan="<?= count($track_views) ?>" style="text-align: left;">
                  <?= $fifth_activity_name ?>
                </td>
              <?php } ?>
              <td>
                <?= $view->getComplianceView()->getAttribute('deadline', '') ?>
              </td>
              <td>
                <?= $view->getComment() ?>
              </td>
              <td>
                <?= $view->getPoints() != '' ? $view->getPoints() : 0 ?>
              </td>
              <td>
                <i class="<?= getStatus($fifth_activity_status) ?>"></i>
              </td>
              <td>
                <?php
                  foreach ($view->getComplianceView()->getLinks() as $link)
                    echo $link->getHTML();
                ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>

      <?php
    }
  }

  function getStatus($code) {
    if ($code == 4) return 'far fa-check';
    if ($code == 2) return 'fas fa-exclamation';
    if ($code == 1) return 'far fa-times';
    return 'far fa-minus';
  }
?>
