<?php
  use hpn\steel\query\SelectQuery;

  class FranklinPrecisionIndustry2022WMS3StepsComplianceView extends DateBasedComplianceView {
    public function __construct($startDate, $endDate, $threshold, $pointsPer) {
      $this->setDateRange($startDate, $endDate);
      $this->threshold = $threshold;
      $this->pointsPer = $pointsPer;
      $this->pauseDate = '2022-06-01';
      $this->restartDate = '2022-06-30';
    }

    public function getDefaultStatusSummary($status) {
      return null;
    }

    public function getDefaultName() {
      return "regular_physical_activity_wms3";
    }

    public function getDefaultReportName() {
      return "Regular Physical Activity ({$this->threshold})";
    }

    public function getStatus(User $user) {
      $_db = Database::getDatabase();
      $startDate = date('Y-m-d', $this->startDate);
      $endDate = date('Y-m-d', $this->endDate);

      $fitnessTrackingQuery =
        "SELECT ftd.activity_date, ftd.value FROM wms3.fitnessTracking_data ftd
          LEFT JOIN wms3.fitnessTracking_participants ftp ON ftp.id = ftd.participant
          WHERE ftp.wms1Id = ".$user->id." AND ftd.type = 1 AND ftd.activity_date >= '".$startDate."'
          AND ftd.activity_date <= '".$endDate."';";

      $data = $_db->getResultsForQuery($fitnessTrackingQuery);

      $stepsData = array();
      foreach($data as $record) {
        $activityDate = date('Y-m-d', strtotime($record['activity_date']));

        $stepsData[$activityDate] = $record['value'];
      }

      $year           = 2022;
      $firstDayOfYear = mktime(0, 0, 0, 1, 1, $year);
      $nextMonday     = strtotime('monday', $firstDayOfYear);
      $nextSunday     = strtotime('sunday', $nextMonday);

      $weeks = array();
      while (date('Y', $nextMonday) == $year) {
        $weeks[] = array(
          'start_date' => date('Y-m-d', $nextMonday),
          'end_date' => date('Y-m-d', $nextSunday)
        );

        $nextMonday = strtotime('+1 week', $nextMonday);
        $nextSunday = strtotime('+1 week', $nextSunday);
      }

      $ranges = array(
        'quarter1' => array('2022-01-01', '2022-03-31'),
        'quarter2' => array('2022-04-01', '2022-06-30'),
        'quarter3' => array('2022-07-01', '2022-09-30'),
        'quarter4' => array('2022-10-01', '2022-12-31')
      );

      $points = 0;
      $quarterPoints = array(
        'quarter1' => 0,
        'quarter2' => 0,
        'quarter3' => 0,
        'quarter4' => 0,
      );

      foreach($weeks as $week) {
        $weeklySteps = 0;
        foreach($stepsData as $date => $steps)
          if(($week['start_date'] <= $date && $date <= $week['end_date'])  && !($date  >= $this->pauseDate && $date <= $this->restartDate))
            $weeklySteps += $steps;

        if($weeklySteps >= $this->threshold) {
          $points += $this->pointsPer;

          foreach($ranges as $quarterName => $dateRange) {
            $startDate = $dateRange[0];
            $endDate =  $dateRange[1];

            if($startDate <= $week['start_date'] && $week['start_date'] <= $endDate)
              $quarterPoints[$quarterName] += $this->pointsPer;
          }
        }
      }

      $status = new ComplianceViewStatus($this, null, $points);
      $status->setAttribute('quarter1_points', $quarterPoints['quarter1']);
      $status->setAttribute('quarter2_points', $quarterPoints['quarter2']);
      $status->setAttribute('quarter3_points', $quarterPoints['quarter3']);
      $status->setAttribute('quarter4_points', $quarterPoints['quarter4']);

      return $status;
    }

    private $threshold;
    private $pointsPer;
    private $pauseDate;
    private $restartDate;
  }

  class FranklinPrecisionIndustry2022ComplianceProgram extends ComplianceProgram {
    public function getProgramReportPrinter($preferredPrinter = null) {
      return new FranklinPrecisionIndustry2022WMS2Printer();
    }

      public function getAdminProgramQarterlyReportPrinter($quarter)
      {
          $printer = new BasicComplianceProgramAdminReportPrinter();
          $printer->setShowUserFields(true, true, false, false, true);
          $printer->setShowTotals(false);
          $printer->setShowStatus(false, false, false);
          $printer->setShowComment(false, false, false);
          $printer->setShowCompliant(false, false, false);
          $printer->setShowPoints(false, false, false);
          $printer->setShowUserContactFields(null, null, true);

          $printer->addCallbackField('hiredate', function(User $user) {
              return $user->getHiredate();
          });

          $printer->addCallbackField('location', function(User $user) {
              return $user->getLocation();
          });

          $printer->addCallbackField('employee_ssn', function (User $user) {
              if($user->getRelationshipType() == 2) {
                  return $user->getRelationshipUser() ? $user->getRelationshipUser()->getSocialSecurityNumber() : '';
              } else {
                  return $user->getSocialSecurityNumber();
              }
          });

          $printer->addMultipleStatusFieldsCallback(function(ComplianceProgramStatus $status) use($quarter) {
              $user = $status->getUser();
              $data = array();

              $totalQuarterlyPoints = 0;
              foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
                  foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
                      $viewName = $viewStatus->getComplianceView()->getReportName();

                      if($quarter == "Q1") {
                          $data[sprintf('%s %s Points', $viewName, $quarter)] = $viewStatus->getAttribute('quarter1');
                          $totalQuarterlyPoints += $viewStatus->getAttribute('quarter1');
                      } else if($quarter == "Q2") {
                          $data[sprintf('%s %s Points', $viewName, $quarter)] = $viewStatus->getAttribute('quarter2');
                          $totalQuarterlyPoints += $viewStatus->getAttribute('quarter2');
                      } else if($quarter == "Q3") {
                          $data[sprintf('%s %s Points', $viewName, $quarter)] = $viewStatus->getAttribute('quarter3');
                          $totalQuarterlyPoints += $viewStatus->getAttribute('quarter3');
                      } else if($quarter == "Q4") {
                          $data[sprintf('%s %s Points', $viewName, $quarter)] = $viewStatus->getAttribute('quarter4');
                          $totalQuarterlyPoints += $viewStatus->getAttribute('quarter4');
                      }

                  }
              }

              $data[sprintf('Total %s Points', $quarter)] = $totalQuarterlyPoints ;

              return $data;
          });

          return $printer;
      }



    public function getAdminProgramReportPrinter() {
      $printer = new BasicComplianceProgramAdminReportPrinter();

      $printer->setShowUserContactFields(null, null, true);

      $printer->addStatusFieldCallback('Section 1 Total Points', function(ComplianceProgramStatus $status) {
        return $status->getAttribute('section_one_points');
      });

      $printer->addStatusFieldCallback('Quarter 1 Points', function(ComplianceProgramStatus $status) {
        return $status->getAttribute('quarter_1_points');
      });

      $printer->addStatusFieldCallback('Quarter 2 Points', function(ComplianceProgramStatus $status) {
        return $status->getAttribute('quarter_2_points');
      });

      $printer->addStatusFieldCallback('Quarter 3 Points', function(ComplianceProgramStatus $status) {
        return $status->getAttribute('quarter_3_points');
      });

      $printer->addStatusFieldCallback('Quarter 4 Points', function(ComplianceProgramStatus $status) {
        return $status->getAttribute('quarter_4_points');
      });

      $printer->addStatusFieldCallback('Tobacco Consent Form Completed', function(ComplianceProgramStatus $status) {
        $user = $status->getUser();
        $record = $user->getNewestDataRecord('cotinine_consent_form_2022');

        if($record->exists())
          return 'Y';
        else
          return 'N';
      });

      return $printer;
    }

    public function validateDate($date, $format = 'Y-m-d') {
      $d = DateTime::createFromFormat($format, $date);
      return $d && $d->format($format) === $date;
    }

    public function loadGroups() {
      ini_set('memory_limit', '2500M');

      $quarterlyDateRange = $this->getQuerterlyRanges();

      $programStart = $this->getStartDate();
      $programEnd = $this->getEndDate();

      $adjustedStart = '2022-01-01';
      $adjustedEnd = '2022-07-01';

      $wellnessGroup = new ComplianceViewGroup('Wellness Program');
      $wellnessGroup->setPointsRequiredForCompliance(0);

      $hra = new CompleteHRAComplianceView($programStart, $programEnd);
      $hra->setReportName('Health Risk Assessment');
      $hra->setName('hra');
      $hra->emptyLinks();
      $hra->addLink(new Link('Take HRA', '/content/my-health'));
      $wellnessGroup->addComplianceView($hra);

      $scr = new CompleteScreeningComplianceView($adjustedStart, $adjustedEnd);
      $scr->setReportName('Onsite Screening');
      $scr->setName('screening');
      $scr->emptyLinks();
      $scr->addLink(new Link('Sign-Up', '/content/cotinine-consent'));
      $wellnessGroup->addComplianceView($scr);

      $registerView = new PlaceHolderComplianceView(null, 0);
      $registerView->setReportName('Register Circle Wellness');
      $registerView->setName('register');
      $registerView->setMaximumNumberOfPoints(20);
      $registerView->setComplianceStatusPointMapper(new ComplianceStatusPointMapper(20, 0, 0, 0));
      $registerView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
        $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          foreach($quarterlyDateRange as $quarterName => $dateRange) {
            if($quarterName != 'quarter1') continue;

            $wms2_login_query = "SELECT id from user_data_records where type = 'wms2_login' and user_id = $user->id;";

            $_db = Database::getDatabase();
            $data = $_db->getResultsForQuery($wms2_login_query);

            $wms2_login = !empty($data);

            if ($wms2_login) {
              $status->setPoints($maxPoints);
              $status->setAttribute('quarter1', $maxPoints);
              $status->setStatus(ComplianceStatus::COMPLIANT);
            }
          }
        }
      });
      $wellnessGroup->addComplianceView($registerView);

      $this->addComplianceViewGroup($wellnessGroup);


      $biometricGroup = new ComplianceViewGroup('Biometric');
      $biometricGroup->setPointsRequiredForCompliance(0);

      $bmiView = new PlaceHolderComplianceView(null, 0);
      $bmiView->setMaximumNumberOfPoints(10);
      $bmiView->setReportName('BMI');
      $bmiView->setName('bmi');
      $bmiView->setAttribute('goal', '< or equal to 29.9');
      $bmiView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange, $adjustedStart, $adjustedEnd) {
        $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();
        $overridePointAdded = false;
        $status->setAttribute('real_result', 'No Screening');

        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          $alternative = new ComplyWithBMIScreeningTestComplianceView($adjustedStart, $adjustedEnd);
          $alternative->setUseOverrideCreatedDate(true);
          $alternative->overrideTestRowData(null, null, 29.999, null);
          $alternativeStatus = $alternative->getStatus($user);

          if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $screeningDate = date('Y-m-d', strtotime($alternativeStatus->getAttribute('date')));

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
              $startDate = $dateRange[0];
              $endDate =  $dateRange[1];

              if($startDate <= $screeningDate && $screeningDate <= $endDate)
                $status->setAttribute($quarterName, $maxPoints);
            }
          }

          if($alternativeStatus->getAttribute('real_result') && $alternativeStatus->getAttribute('real_result') != 'No Screening')
            $status->setAttribute('real_result', round($alternativeStatus->getAttribute('real_result'), 2));
        }
      });
      $bmiView->setPreMapCallback($this->checkImprovement(array('bmi')));
      $bmiView->addLink(new Link('alternative', '/resources/10751/2022_FPI_biometric_alternative.pdf', false, '_blank'));
      $biometricGroup->addComplianceView($bmiView);

      $glucoseView = new PlaceHolderComplianceView(null, 0);
      $glucoseView->setMaximumNumberOfPoints(10);
      $glucoseView->setReportName('Glucose');
      $glucoseView->setName('glucose');
      $glucoseView->setAttribute('goal', '< or equal to 120');
      $glucoseView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange, $adjustedStart, $adjustedEnd) {
        $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();
        $overridePointAdded = false;
        $status->setAttribute('real_result', 'No Screening');

        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          $alternative = new ComplyWithGlucoseScreeningTestComplianceView($adjustedStart, $adjustedEnd);
          $alternative->setUseOverrideCreatedDate(true);
          $alternative->overrideTestRowData(null, null, 120, null);
          $alternativeStatus = $alternative->getStatus($user);

          if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $screeningDate = date('Y-m-d', strtotime($alternativeStatus->getAttribute('date')));

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
              $startDate = $dateRange[0];
              $endDate =  $dateRange[1];

              if($startDate <= $screeningDate && $screeningDate <= $endDate)
                $status->setAttribute($quarterName, $maxPoints);
            }
          }

          if($alternativeStatus->getAttribute('real_result') && $alternativeStatus->getAttribute('real_result') != 'No Screening')
            $status->setAttribute('real_result', round($alternativeStatus->getAttribute('real_result'), 2));
        }
      });
      $glucoseView->setPreMapCallback($this->checkImprovement(array('glucose')));
      $glucoseView->addLink(new Link('alternative', '/resources/10751/2022_FPI_biometric_alternative.pdf', false, '_blank'));
      $biometricGroup->addComplianceView($glucoseView);

      $cotinineView = new PlaceHolderComplianceView(null, 0);
      $cotinineView->setMaximumNumberOfPoints(10);
      $cotinineView->setReportName('Non-tobacco User');
      $cotinineView->setName('cotinine');
      $cotinineView->setAttribute('goal', '');
      $cotinineView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange, $adjustedStart, $adjustedEnd) {
        $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();
        $overridePointAdded = false;
        $status->setAttribute('real_result', 'No Screening');

        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          $alternative = new ComplyWithCotinineScreeningTestComplianceView($adjustedStart, $adjustedEnd);
          $alternative->setUseOverrideCreatedDate(true);
          $alternative->overrideTestRowData(null, null, 6, null);
          $alternativeStatus = $alternative->getStatus($user);

          if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $screeningDate = date('Y-m-d', strtotime($alternativeStatus->getAttribute('date')));

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
              $startDate = $dateRange[0];
              $endDate =  $dateRange[1];

              if($startDate <= $screeningDate && $screeningDate <= $endDate)
                $status->setAttribute($quarterName, $maxPoints);
            }
          }

          if($alternativeStatus->getAttribute('real_result') && $alternativeStatus->getAttribute('real_result') != 'No Screening')
            $status->setAttribute('real_result', round($alternativeStatus->getAttribute('real_result'), 2));
        }
      });
      $cotinineView->addLink(new Link('alternative', '/content/12088', false, '_blank'));
      $biometricGroup->addComplianceView($cotinineView);

      $bloodPressureView = new PlaceHolderComplianceView(null, 0);
      $bloodPressureView->setMaximumNumberOfPoints(10);
      $bloodPressureView->setReportName('Blood Pressure');
      $bloodPressureView->setName('blood_pressure');
      $bloodPressureView->setAttribute('goal', '< or equal to 135/86');
      $bloodPressureView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange, $adjustedStart, $adjustedEnd) {
        $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();
        $overridePointAdded = false;
        $status->setAttribute('real_result', 'No Screening');

        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          $alternative = new ComplyWithBloodPressureScreeningTestComplianceView($adjustedStart, $adjustedEnd);
          $alternative->setUseOverrideCreatedDate(true);
          $alternative->overrideSystolicTestRowData(0, 0, 135, 135);
          $alternative->overrideDiastolicTestRowData(0, 0, 86, 86);
          $alternativeStatus = $alternative->getStatus($user);

          if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $screeningDate = date('Y-m-d', strtotime($alternativeStatus->getAttribute('date')));

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
              $startDate = $dateRange[0];
              $endDate =  $dateRange[1];

              if($startDate <= $screeningDate && $screeningDate <= $endDate)
                  $status->setAttribute($quarterName, $maxPoints);
            }
          }

          if($alternativeStatus->getAttribute('real_result') && $alternativeStatus->getAttribute('real_result') != 'No Screening')
            $status->setAttribute('real_result', round($alternativeStatus->getAttribute('real_result'), 2));
        }
      });
      $bloodPressureView->setPreMapCallback($this->checkImprovement(array('systolic', 'diastolic')));
      $bloodPressureView->addLink(new Link('alternative', '/resources/10751/2022_FPI_biometric_alternative.pdf', false, '_blank'));
      $biometricGroup->addComplianceView($bloodPressureView);

      $hdlRatioView = new PlaceHolderComplianceView(null, 0);
      $hdlRatioView->setMaximumNumberOfPoints(10);
      $hdlRatioView->setReportName('TC/HDL Ratio');
      $hdlRatioView->setName('hdl_ratio');
      $hdlRatioView->setAttribute('goal', '< or equal to 4.5');
      $hdlRatioView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange, $adjustedStart, $adjustedEnd) {
        $maxPoints = $status->getComplianceView()->getMaximumNumberOfPoints();
        $overridePointAdded = false;
        $pointAdded = false;
        $status->setAttribute('real_result', 'No Screening');

        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          $alternative = new ComplyWithTotalHDLCholesterolRatioScreeningTestComplianceView($adjustedStart, $adjustedEnd);
          $alternative->setUseOverrideCreatedDate(true);
          $alternative->overrideTestRowData(null, null, 4.5, null);
          $alternativeStatus = $alternative->getStatus($user);

          if($alternativeStatus->getStatus() == ComplianceStatus::COMPLIANT) {
            $screeningDate = date('Y-m-d', strtotime($alternativeStatus->getAttribute('date')));

            foreach($quarterlyDateRange as $quarterName => $dateRange) {
              $startDate = $dateRange[0];
              $endDate =  $dateRange[1];

              if($startDate <= $screeningDate && $screeningDate <= $endDate)
                $status->setAttribute($quarterName, $maxPoints);
            }
          }

          if($alternativeStatus->getAttribute('real_result') && $alternativeStatus->getAttribute('real_result') != 'No Screening')
            $status->setAttribute('real_result', round($alternativeStatus->getAttribute('real_result'), 2));
        }
      });
      $hdlRatioView->setPreMapCallback($this->checkImprovement(array('totalhdlratio')));
      $hdlRatioView->addLink(new Link('alternative', '/resources/10751/2022_FPI_biometric_alternative.pdf', false, '_blank'));
      $biometricGroup->addComplianceView($hdlRatioView);

      $this->addComplianceViewGroup($biometricGroup);

      $preventiveGroup = new ComplianceViewGroup('Preventive');
      $preventiveGroup->setPointsRequiredForCompliance(0);

      $dentalExamView = new PlaceHolderComplianceView(null, 0);
      $dentalExamView->setMaximumNumberOfPoints(10);
      $dentalExamView->setReportName('Dental Exam');
      $dentalExamView->setName('dental_exam');
      $dentalExamView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        }
      });
      $preventiveGroup->addComplianceView($dentalExamView);

      $eyeExamView = new PlaceHolderComplianceView(null, 0);
      $eyeExamView->setMaximumNumberOfPoints(10);
      $eyeExamView->setReportName('Eye Exam');
      $eyeExamView->setName('eye_exam');
      $eyeExamView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        }
      });
      $preventiveGroup->addComplianceView($eyeExamView);

      $ageExamView = new PlaceHolderComplianceView(null, 0);
      $ageExamView->setMaximumNumberOfPoints(15);
      $ageExamView->setReportName('1 age specific exam ($15) Ex. Mammogram, Pap, PSA, colonoscopy. If under the age for these tests, an annual physician’s exam counts');
      $ageExamView->setName('age_exam');
      $ageExamView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        }
      });
      $preventiveGroup->addComplianceView($ageExamView);

      $this->addComplianceViewGroup($preventiveGroup);

      $communityGroup = new ComplianceViewGroup('Community');
      $communityGroup->setPointsRequiredForCompliance(0);

      $companyVolunteeringView = new PlaceHolderComplianceView(null, 0);
      $companyVolunteeringView->setMaximumNumberOfPoints(20);
      $companyVolunteeringView->setReportName('Company Volunteering (2 opportunities per year, $10 each)');
      $companyVolunteeringView->setName('company_volunteering');
      $companyVolunteeringView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          foreach($quarterlyDateRange as $quarterName => $dateRange) {
            $startDate = $dateRange[0];
            $endDate =  $dateRange[1];

            $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144532, 10);
            $alternative->setUseOverrideCreatedDate(true);
            $alternativeStatus = $alternative->getStatus($user);


            if($alternativeStatus->getPoints() > 0)
              $status->setAttribute($quarterName, $alternativeStatus->getPoints());
          }
        }
      });
      $companyVolunteeringView->addLink(new Link('Complete Attestation Form Here', '/content/12048?action=showActivity&activityidentifier=144532'));
      $communityGroup->addComplianceView($companyVolunteeringView);

      $this->addComplianceViewGroup($communityGroup);

      $fitnessGroup = new ComplianceViewGroup('Fitness');
      $fitnessGroup->setPointsRequiredForCompliance(0);

      $activityChallengeView = new PlaceHolderComplianceView(null, 0);
      $activityChallengeView->setMaximumNumberOfPoints(20);
      $activityChallengeView->setReportName('Group Activity Challenge ($10 per challenge x 2 per year)');
      $activityChallengeView->setName('activity_challenge');
      $activityChallengeView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        }
      });
      $fitnessGroup->addComplianceView($activityChallengeView);

      $stepsTrackingView = new PlaceHolderComplianceView(null, 0);
      $stepsTrackingView->setMaximumNumberOfPoints(117);
      $stepsTrackingView->setReportName('Weekly Steps Tracking (Individual) 40,000 week (Mon-Sun) = $3/week');
      $stepsTrackingView->setName('steps_tracking');
      $stepsTrackingView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange, $programStart, $programEnd) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = date('Y-m-d', strtotime($status->getComment()));
          if($points) {
            if($date) {
              $pauseDate = '2022-06-01';
              $restartDate = '2022-06-30';

              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(($date  >= $startDate && $date <= $endDate) && !($date  >= $pauseDate && $date <= $restartDate)) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          $alternative = new FranklinPrecisionIndustry2022WMS3StepsComplianceView($programStart, $programEnd, 40000, 3);
          $alternative->setUseOverrideCreatedDate(true);
          $alternativeStatus = $alternative->getStatus($user);

          $status->setAttribute('quarter1', $alternativeStatus->getAttribute('quarter1_points'));
          $status->setAttribute('quarter2', $alternativeStatus->getAttribute('quarter2_points'));
          $status->setAttribute('quarter3', $alternativeStatus->getAttribute('quarter3_points'));
          $status->setAttribute('quarter4', $alternativeStatus->getAttribute('quarter4_points'));
        }
      });
      $stepsTrackingView->addLink(new Link('Connect Device', '/content/fitness'));

      $fitnessGroup->addComplianceView($stepsTrackingView);

      $walkingTogetherView = new PlaceHolderComplianceView(null, 0);
      $walkingTogetherView->setMaximumNumberOfPoints(45);
      $walkingTogetherView->setReportName('Walking Together (3 times a year $15/each)');
      $walkingTogetherView->setName('walking_together');
      $walkingTogetherView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          foreach($quarterlyDateRange as $quarterName => $dateRange) {
            $startDate = $dateRange[0];
            $endDate =  $dateRange[1];


            $alternative = new CompleteArbitraryActivityComplianceView($startDate, $endDate, 144533, 15);
            $alternative->setUseOverrideCreatedDate(true);
            $alternativeStatus = $alternative->getStatus($user);

            if($alternativeStatus->getPoints() > 0)
              $status->setAttribute($quarterName, $alternativeStatus->getPoints());
          }
        }
      });
      $walkingTogetherView->addLink(new Link('Complete Attestation Form', '/content/12048?action=showActivity&activityidentifier=144533'));
      $fitnessGroup->addComplianceView($walkingTogetherView);

      $this->addComplianceViewGroup($fitnessGroup);

      $onlineEducationGroup = new ComplianceViewGroup('Online Education');
      $onlineEducationGroup->setPointsRequiredForCompliance(0);

      $tobaccoCessationView = new PlaceHolderComplianceView(null, 0);
      $tobaccoCessationView->setMaximumNumberOfPoints(20);
      $tobaccoCessationView->setReportName('"Living Free" Tobacco Cessation Course **');
      $tobaccoCessationView->setName('tobacco_cessation_course');
      $tobaccoCessationView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange, $adjustedStart, $adjustedEnd) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        } else {
          $alternative = new ComplyWithCotinineScreeningTestComplianceView($adjustedStart, $adjustedEnd);
          $alternative->setUseOverrideCreatedDate(true);
          $alternativeStatus = $alternative->getStatus($user);


          if($alternativeStatus->getAttribute('has_result') && $alternativeStatus->getStatus() == ComplianceStatus::NOT_COMPLIANT)
            $status->setAttribute('qualified', true);
          else
            $status->setAttribute('qualified', false);
        }
      });
      $tobaccoCessationView->addLink(new Link('Living Free Course', '/content/12088'));
      $onlineEducationGroup->addComplianceView($tobaccoCessationView);

      $this->addComplianceViewGroup($onlineEducationGroup);

      $otherGroup = new ComplianceViewGroup('Other');
      $otherGroup->setPointsRequiredForCompliance(0);

      $registerTeladocView = new PlaceHolderComplianceView(null, 0);
      $registerTeladocView->setMaximumNumberOfPoints(20);
      $registerTeladocView->setReportName('Register Teladoc');
      $registerTeladocView->setName('register_teladoc');
      $registerTeladocView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use ($quarterlyDateRange) {
        $overridePointAdded = false;
        if($status->getUsingOverride()) {
          $points = $status->getPoints();
          $date = $status->getComment();
          if($points) {
            if($date) {
              foreach($quarterlyDateRange as $quarterName => $dateRange) {
                $startDate = $dateRange[0];
                $endDate =  $dateRange[1];

                if(!$overridePointAdded) {
                  if(date('Y-m-d', strtotime($date))  >= $startDate && date('Y-m-d', strtotime($date)) <= $endDate) {
                    $status->setAttribute($quarterName, $points);
                    $status->setAttribute('total_points', $points);
                    $overridePointAdded = true;
                  }
                }
              }
            }

            if(!$overridePointAdded) {
              $status->setAttribute('quarter1', $points);
              $status->setAttribute('total_points', $points);
            }
          }
        }
      });
      $otherGroup->addComplianceView($registerTeladocView);

      $this->addComplianceViewGroup($otherGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status) {
      parent::evaluateAndStoreOverallStatus($status);

      $quarter1Points = 0;
      $quarter2Points = 0;
      $quarter3Points = 0;
      $quarter4Points = 0;
      foreach($status->getComplianceViewGroupStatuses() as $groupStatus) {
        $totalGroupPoints = 0;
        foreach($groupStatus->getComplianceViewStatuses() as $viewStatus) {
          $view = $viewStatus->getComplianceView();
          if($viewStatus->getAttribute('quarter1'))
            $quarter1Points += $viewStatus->getAttribute('quarter1');

          if($viewStatus->getAttribute('quarter2'))
            $quarter2Points += $viewStatus->getAttribute('quarter2');

          if($viewStatus->getAttribute('quarter3'))
            $quarter3Points += $viewStatus->getAttribute('quarter3');

          if($viewStatus->getAttribute('quarter4'))
            $quarter4Points += $viewStatus->getAttribute('quarter4');

          $totalViewPoints = $viewStatus->getAttribute('quarter1') + $viewStatus->getAttribute('quarter2') + $viewStatus->getAttribute('quarter3') + $viewStatus->getAttribute('quarter4');
          if($totalViewPoints > $view->getMaximumNumberOfPoints()) $totalViewPoints = $view->getMaximumNumberOfPoints();
          $viewStatus->setPoints($totalViewPoints);

          $totalGroupPoints += $totalViewPoints;
        }

        $groupStatus->setPoints($totalGroupPoints);
      }

      $status->setAttribute('quarter_1_points', $quarter1Points);
      $status->setAttribute('quarter_2_points', $quarter2Points);
      $status->setAttribute('quarter_3_points', $quarter3Points);
      $status->setAttribute('quarter_4_points', $quarter4Points);
      $status->setAttribute('total_points', $quarter1Points+$quarter2Points+$quarter3Points+$quarter4Points);
      $status->setPoints($quarter1Points+$quarter2Points+$quarter3Points+$quarter4Points);
    }

    protected function getQuerterlyRanges() {
      $ranges = array(
        'quarter1' => array('2022-01-01', '2022-03-31'),
        'quarter2' => array('2022-04-01', '2022-06-30'),
        'quarter3' => array('2022-07-01', '2022-09-30'),
        'quarter4' => array('2022-10-01', '2022-12-31')
      );

      return $ranges;
    }

    public function useParallelReport() {
      return false;
    }

    protected function checkImprovement(array $tests, $calculationMethod = 'decrease') {
      $programStart = new \DateTime('@'.$this->getStartDate());
      $programEnd = new \DateTime('@'.$this->getEndDate());

      $lastStart = new \DateTime('2020-07-01');
      $lastEnd = new \DateTime('2021-06-30');

      return function(ComplianceViewStatus $status, User $user) use ($tests, $programStart, $programEnd, $lastStart, $lastEnd, $calculationMethod) {
          static $cache = null;

          if ($cache === null || $cache['user_id'] != $user->id)
            $cache = array(
              'user_id' => $user->id,
              'this' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $programStart, $programEnd),
              'last' => ScreeningTable::getInstance()->findOneCompletedForUserBetweenDates($user, $lastStart, $lastEnd)
            );

          if (count($tests) > 0 && $cache['this'] && $cache['last']) {
              $isImproved = false;

              foreach($tests as $test) {
                if($test == 'bmi') {
                  if($cache['last'][0]['height'] !== null && $cache['last'][0]['weight'] !== null && is_numeric($cache['last'][0]['height']) && is_numeric($cache['last'][0]['weight']) && $cache['last'][0]['height'] > 0)
                    $cache['last'][0][$test] = ($cache['last'][0]['weight'] * 703) / ($cache['last'][0]['height'] * $cache['last'][0]['height']);

                  if($cache['this'][0]['height'] !== null && $cache['this'][0]['weight'] !== null && is_numeric($cache['this'][0]['height']) && is_numeric($cache['this'][0]['weight']) && $cache['this'][0]['height'] > 0)
                    $cache['this'][0][$test] = ($cache['this'][0]['weight'] * 703) / ($cache['this'][0]['height'] * $cache['this'][0]['height']);
                }

                $lastVal = isset($cache['last'][0][$test]) ? (float) $cache['last'][0][$test] : null;
                $thisVal = isset($cache['this'][0][$test]) ? (float) $cache['this'][0][$test] : null;

                if($lastVal && $thisVal) {
                  if($calculationMethod == 'decrease') {
                    if (!$thisVal || !$lastVal || $lastVal * 0.90 >= $thisVal) {
                      $isImproved = true;

                      break;
                    }
                  } else {
                    if (!$thisVal || !$lastVal || $lastVal * 1.10 <= $thisVal) {
                      $isImproved = true;

                      break;
                    }
                  }
                }
              }

              if ($isImproved)
                $status->setStatus(ComplianceStatus::COMPLIANT);
            }
        };
    }
  }


  class FranklinPrecisionIndustry2022WMS2Printer implements ComplianceProgramReportPrinter {
    public function getStatusMappings(ComplianceView $view) {
        if($view->getName() == 'waist_bmi')
          return array(
            4 => ComplianceStatus::COMPLIANT,
            3 => ComplianceStatus::PARTIALLY_COMPLIANT,
            2 => ComplianceStatus::NOT_COMPLIANT,
            1 => ComplianceStatus::NOT_COMPLIANT,
            0 => ComplianceStatus::NOT_COMPLIANT
          );
        elseif($view->getName() == 'tobacco')
          return array(
            4 => ComplianceStatus::COMPLIANT,
            1 => ComplianceStatus::NOT_COMPLIANT
          );
        return array(
          4 => ComplianceStatus::COMPLIANT,
          2 => ComplianceStatus::PARTIALLY_COMPLIANT,
          1 => ComplianceStatus::NOT_COMPLIANT
        );
    }

    public function getClass($points, $max) {
        if ($points < $max/2)
          return "danger";
        else if ($points >= $max)
          return "success";
        else
          return "warning";
    }

    public function printReport(ComplianceProgramStatus $status) {
      $wellnessGroup = $status->getComplianceViewGroupStatus('Wellness Program');
      $biometricGroup = $status->getComplianceViewGroupStatus('Biometric');
      $preventiveGroup = $status->getComplianceViewGroupStatus('Preventive');
      $communityGroup = $status->getComplianceViewGroupStatus('Community');
      $fitnessGroup = $status->getComplianceViewGroupStatus('Fitness');
      $onlineEducationGroup = $status->getComplianceViewGroupStatus('Online Education');
      $otherGroup = $status->getComplianceViewGroupStatus('Other');

      $wellnessPoints = $wellnessGroup->getPoints();
      $wellnessMaxPoints = $wellnessGroup->getComplianceViewGroup()->getMaximumNumberOfPoints();

      $biometricPoints = $biometricGroup->getPoints();
      $biometricMaxPoints = $biometricGroup->getComplianceViewGroup()->getMaximumNumberOfPoints();

      $preventivePoints = $preventiveGroup->getPoints();
      $preventiveMaxPoints = $preventiveGroup->getComplianceViewGroup()->getMaximumNumberOfPoints();

      $communityPoints = $communityGroup->getPoints();
      $communityMaxPoints = $communityGroup->getComplianceViewGroup()->getMaximumNumberOfPoints();

      $fitnessPoints = $fitnessGroup->getPoints();
      $fitnessMaxPoints = $fitnessGroup->getComplianceViewGroup()->getMaximumNumberOfPoints();

      $onlineEducationPoints = $onlineEducationGroup->getPoints();
      $onlineEducationMaxPoints = $onlineEducationGroup->getComplianceViewGroup()->getMaximumNumberOfPoints();

      $otherPoints = $otherGroup->getPoints();
      $otherMaxPoints = $otherGroup->getComplianceViewGroup()->getMaximumNumberOfPoints();

      ?>

      <style type="text/css">
        #activities {
          width: 100%;
          border-collapse: separate;
          border-spacing: 5px;
          min-width: 500px;
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
          overflow: hidden;
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
          width: 436px;
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

        #point-discounts {
          width: 100%;
        }

        #point-discounts td {
          vertical-align: middle;
          padding: 5px;
        }

        .circle-range-inner-beacon {
          background-color: #48C7E8;
          color: #FFF;
        }

        .activity .circle-range {
          border-color: #489DE8;
        }

        .circle-range .circle-points {
          font-size: 1em;
        }


        .point-totals th {
          text-align: right;
          padding-right: 10px;
        }

        .point-totals td {
          text-align: center;
        }

        .total-status td, .spouse-status td {
          text-align: center;
        }

        #wms1 h3[toggle] {
          font-size: 20px;
          color: #333D46;
          background: #ECEFF1;
          cursor: pointer;
          padding: 10px 20px;
          border-radius: 2px;
        }

        #wms1 h3[toggle]:hover {
          color: #48C7E8;
        }

        #wms1 h3[toggle] i {
          margin-right: 10px;
        }

        .date-input {
          width: 100%;
          height: 39px;
          font-size: 1.7rem;
          text-align: center;
          cursor: pointer;
        }

        .date-input:hover {
          background: #ECEFF1;
          outline: none !important;
          border: 1px solid;
        }

        .shadow {
          box-shadow: 0 3px 6px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0);
          height: 100px;
        }

        .grand-total-container {
          box-shadow: 0 3px 6px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0);
        }

        .quarter-indicator {
          width: 50px;
          height: 50px;
        }

        .quarter-even {
          background-color: #48c7e8;
          display: inline-block;

        }

        .quarter-odd {
          background-color: #0cc2ab;
          display: inline-block;
        }

        .quarter {
          text-align: center;
          color: #fff;
          vertical-align: middle;
          line-height: 50px;
          font-size: 20px;
          margin-bottom: 15px;
        }

        .quarter-rectangles {
          margin-top: 20px;
        }

        .quarter-rectangles .col-md-5 {
          padding: 0 !important;
        }

        .grand-total {
          height: 220px;
          background: #333d46;
        }

        .scores-container {
          display: inline-block;
          width: 70%;
        }

        .grand-total-container {
          width: 29%;
          display: inline-block;
        }

        .quarter-points {
          text-align: right;
          padding-right: 20px;
          width: auto;
          float: right;
          font-size: 40px;
          font-weight: bold;
          position: relative;
          top: -14px;
        }

        .quarter-points-odd {
          color: #0cc2ab;
        }

        .quarter-points-even {
          color: #48c7e8;;
        }

        .quarter-range {
          float: left;
          width: 100%;
        }

        .quarter-year {
          color: #333d47;
          float: left;
          margin-left: 15px;
        }

        .quarter-months {
          color: #9c9c9c;
          float: right;
          text-align: right;
          margin-right: 15px;
          text-transform: uppercase;
          font-size: 10px;
          padding-top: 4px;
        }

        .grand-total-header {
          text-align: center;
          color: #fff;
          padding-top: 20px;
          letter-spacing: .3em;
          font-weight: bold;
        }

        .blue-circle {
          height: 150px;
          width: 150px;
          border-radius: 50%;
          background: -webkit-linear-gradient(#81e3fe, #00afda);
          background: -o-linear-gradient(#81e3fe, #00afda);
          background: linear-gradient(#81e3fe, #00afda);
          padding: 3px;
          margin: 0 auto;
          position: relative;
          top: 10px;
        }

        .grand-total-points {
          padding: 2rem;
          background: #333d46;
          border-radius: 50%;
          width: 100%;
          height: 100%;
          text-align: center;
          color: #ced5dd;
        }

        .total-points {
          font-size: 35px;
          font-weight: bold;
          position: relative;
          top: 7px;
        }

        .total-points-text {
          position: relative;
          bottom: 12px;
        }

        .red {
          color: #F44336;
        }

        .green {
          color: #66BB6A;
        }

        .fa-times, .fa-check {
          margin-right: 10px;
          width: 15px;
          display: inline-block;
          text-align: center;
        }

        @media (max-width: 500px) {
          .collapsible-points-report-card {
            max-width: 500px;
            min-width: 320px;
          }

          .triangle {
            display: none;
          }

          .scores-container {
            width: 100%;
          }

          .quarter-rectangles .col-md-5 {
            display: inline-block;
            width: 45%;
            margin-left: 12px;
          }

          .quarter-points {
            font-size: 30px;
            padding-top: 10px;
          }

          .grand-total-container {
            width: 100%;
            margin-top: 10px;

          }

          .grand-total-container .row .col-md-12 {
            padding: 0 12px;
          }

          .grand-total-header {
            width: 61%;
            padding: 0 10px;
            height: 100%;
            float: left;
            position: relative;
            top: 35px;
            font-size: 17px;
          }

          .grand-total {
            height: auto;
            float: left;
            width: 100%;
            padding: 20px 0px;
          }

          .blue-circle {
            float: right;
            width: 100px;
            height: 100px;
            padding: 7px;
            top: 0;
            margin-right: 40px;

          }

          .grand-total-points {
            padding: 0;
          }

          .total-points {
            font-size: 30px;
          }

          .collapsible-points-report-card tr.details > td {
            padding: 0;
          }
        }
      </style>

      <div class="row">
        <div class="col-md-12">
          <h1>2022 Incentive Report Card</h1>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <hr/>
        </div>
      </div>

        <div class="scores-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="row quarter-rectangles">
                        <div class="col-md-5 shadow">
                            <div class="quarter-indicator quarter-odd">
                              <div class="quarter">Q1</div>
                            </div>
                            <div class="quarter-points quarter-points-odd"><?= $status->getAttribute('quarter_1_points') ?></div>
                            <div class="quarter-range"><span class="quarter-year">2022</span><span class="quarter-months">Jan - Mar</span></div>
                        </div>
                        <div class="col-md-5 col-md-offset-1 shadow">
                            <div class="quarter-indicator quarter-even">
                                <div class="quarter">Q2</div>
                            </div>
                            <div class="quarter-points quarter-points-even"><?= $status->getAttribute('quarter_2_points') ?></div>
                            <div class="quarter-range"><span class="quarter-year">2022</span><span class="quarter-months">Apr - Jun</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="row quarter-rectangles">
                        <div class="col-md-5 shadow">
                            <div class="quarter-indicator quarter-even">
                                <div class="quarter">Q3</div>
                            </div>
                            <div class="quarter-points quarter-points-even"><?= $status->getAttribute('quarter_3_points') ?></div>
                            <div class="quarter-range"><span class="quarter-year">2022</span><span class="quarter-months">Jul - Sep</span></div>
                        </div>
                        <div class="col-md-5 col-md-offset-1 shadow">
                            <div class="quarter-indicator quarter-odd">
                                <div class="quarter">Q4</div>
                            </div>
                            <div class="quarter-points quarter-points-odd"><?= $status->getAttribute('quarter_4_points') ?></div>
                            <div class="quarter-range"><span class="quarter-year">2022</span><span class="quarter-months">Oct - Dec</span></div>
                            <div class="quarter-text"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grand-total-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="grand-total">
                        <div class="grand-total-header">GRAND TOTAL</div>
                        <div class="blue-circle">
                            <div class="grand-total-points">
                                <div class="total-points"><?= $status->getAttribute('total_points') ?></div>
                                <div class="total-points-text">Bucks</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-12">
                <table id="activities">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="points">Annual Maximum</th>
                            <th class="points">Bucks Earned</th>
                            <th class="text-center">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="picker open">
                            <td class="name"><?= $wellnessGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target">None</td>
                            <td class="points actual <?= $this->getClass($wellnessPoints, 20) ?>"><?= $wellnessPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($wellnessPoints, 20) ?>" style="width: <?= ($wellnessPoints / 20) * 100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($wellnessGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <?php if($view->getName() == 'register') : ?>
                                                <tr class="view-hra">
                                                    <td class="name"><?= $view->getReportName()?></td>
                                                    <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                                    <td class="points actual <?= $viewStatus->getPoints() ? "success" : "danger" ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                                    <td class="item-progress" style="width: 100px;">
                                                        <div class="pgrs pgrs-tiny">
                                                            <div class="bar <?= $viewStatus->getPoints() ? "success" : "danger" ?>" style="width: <?= $viewStatus->getPoints() ? 100 : 0 ?>%;"></div>
                                                        </div>
                                                    </td>
                                                    <td class="links text-center" style="width: 120px;">
                                                        <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                            <div><?php echo $link->getHTML() ?></div>
                                                        <?php endforeach ?>
                                                    </td>
                                                </tr>
                                            <?php else : ?>
                                                <tr class="view-hra">
                                                    <td class="name"><?= $view->getReportName()?></td>
                                                    <td class="points target">None</td>
                                                    <td class="points actual <?= $viewStatus->getStatus() == ComplianceStatus::COMPLIANT ? "success" : "danger" ?>"><?php echo $viewStatus->getStatus() == ComplianceStatus::COMPLIANT ? 'Done' : 'Not Yet' ?></td>
                                                    <td class="item-progress" style="width: 100px;">
                                                        <div class="pgrs pgrs-tiny">
                                                            <div class="bar <?= $viewStatus->getStatus() == ComplianceStatus::COMPLIANT ? "success" : "danger" ?>" style="width: <?= $viewStatus->getStatus() == ComplianceStatus::COMPLIANT ? 100 : 0 ?>%;"></div>
                                                        </div>
                                                    </td>
                                                    <td class="links text-center" style="width: 120px;">
                                                        <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                            <div><?php echo $link->getHTML() ?></div>
                                                        <?php endforeach ?>
                                                    </td>
                                                </tr>
                                            <?php endif?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="picker open">
                            <td class="name"><?= $biometricGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target"><?= $biometricMaxPoints ?></td>
                            <td class="points actual <?= $this->getClass($biometricPoints, $biometricMaxPoints) ?>"><?= $biometricGroup->getPoints() ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($biometricPoints, $biometricMaxPoints) ?>" style="width: <?= ($biometricPoints/$biometricMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="5">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($biometricGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <tr class="view-hra">
                                                <td class="name"><span ><?= $view->getReportName() ?><?php echo $view->getAttribute('goal') ? ' ('.$view->getAttribute('goal').')' : '' ?></span><div style="font-size:8px;">Your Result: <?php echo $viewStatus->getAttribute('real_result') ?></div> </td>
                                                <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                                <td class="points actual <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= ($viewStatus->getPoints() / $view->getMaximumNumberOfPoints())*100 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                        <div><?php echo $link->getHTML() ?></div>
                                                    <?php endforeach ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>


                        <tr class="picker open">
                            <td class="name"><?= $preventiveGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target"><?= $preventiveMaxPoints ?></td>
                            <td class="points actual <?= $this->getClass($preventivePoints, $preventiveMaxPoints) ?>"><?= $preventivePoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($preventivePoints, $preventiveMaxPoints) ?>" style="width: <?= ($preventivePoints/$preventiveMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <td colspan="5"><p style="color:red; width: 90%; text-align: center; font-weight: bold;">Exams must be completed between January 1, 2022-December 31, 2022. Circle Wellness will receive a file from UMR to award your bucks.</p></td>
                                        </tr>
                                        <tr>
                                            <th>Item</th>
                                            <th class="points">Maximum</th>
                                            <th class="points">Actual</th>
                                            <th class="points">Progress</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($preventiveGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                            <?php $view = $viewStatus->getComplianceView() ?>
                                            <tr class="view-hra">
                                                <td class="name"><?= $view->getReportName()?></td>
                                                <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                                <td class="points actual <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                                <td class="item-progress" style="width: 100px;">
                                                    <div class="pgrs pgrs-tiny">
                                                        <div class="bar <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= ($viewStatus->getPoints() / $view->getMaximumNumberOfPoints())*100 ?>%;"></div>
                                                    </div>
                                                </td>
                                                <td class="links text-center" style="width: 120px;">
                                                    <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                    <div><?php echo $link->getHTML() ?></div>
                                                    <?php endforeach ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>

                        <tr class="picker open">
                            <td class="name"><?= $communityGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target"><?= $communityMaxPoints ?></td>
                            <td class="points actual <?= $this->getClass($communityPoints, $communityMaxPoints) ?>"><?= $communityPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($communityPoints, $communityMaxPoints) ?>" style="width: <?= ($communityPoints/$communityMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="points">Maximum</th>
                                        <th class="points">Actual</th>
                                        <th class="points">Progress</th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($communityGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                    <?php $view = $viewStatus->getComplianceView() ?>
                                    <tr class="view-hra">
                                        <td class="name"><?= $view->getReportName()?></td>
                                        <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                        <td class="points actual <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                        <td class="item-progress" style="width: 100px;">
                                            <div class="pgrs pgrs-tiny">
                                                <div class="bar <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= ($viewStatus->getPoints() / $view->getMaximumNumberOfPoints())*100 ?>%;"></div>
                                            </div>
                                        </td>
                                        <td class="links text-center" style="width: 120px;">
                                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                <div><?php echo $link->getHTML() ?></div>
                                            <?php endforeach ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>



                        <tr class="picker open">
                            <td class="name"><?= $fitnessGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target"><?= $fitnessMaxPoints ?></td>
                            <td class="points actual <?= $this->getClass($fitnessPoints, $fitnessMaxPoints) ?>"><?= $fitnessPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($fitnessPoints, $fitnessMaxPoints) ?>" style="width: <?= ($fitnessPoints/$fitnessMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="points">Maximum</th>
                                        <th class="points">Actual</th>
                                        <th class="points">Progress</th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($fitnessGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                    <?php $view = $viewStatus->getComplianceView() ?>
                                    <tr class="view-hra">
                                        <td class="name"><?= $view->getReportName()?></td>
                                        <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                        <td class="points actual <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                        <td class="item-progress" style="width: 100px;">
                                            <div class="pgrs pgrs-tiny">
                                                <div class="bar <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= ($viewStatus->getPoints() / $view->getMaximumNumberOfPoints())*100 ?>%;"></div>
                                            </div>
                                        </td>
                                        <td class="links text-center" style="width: 120px;">
                                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                <div><?php echo $link->getHTML() ?></div>
                                            <?php endforeach ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>

                        <tr class="picker open">
                            <td class="name"><?= $onlineEducationGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target"><?= $onlineEducationMaxPoints ?></td>
                            <td class="points actual <?= $this->getClass($onlineEducationPoints, $onlineEducationMaxPoints) ?>"><?= $onlineEducationPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($onlineEducationPoints, $onlineEducationMaxPoints) ?>" style="width: <?= ($onlineEducationPoints/$onlineEducationMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="points">Maximum</th>
                                        <th class="points">Actual</th>
                                        <th class="points">Progress</th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($onlineEducationGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                    <?php $view = $viewStatus->getComplianceView() ?>
                                    <tr class="view-hra">
                                        <td class="name"><?= $view->getReportName()?><?php echo $viewStatus->getAttribute('qualified') ? ' - Qualified' : '' ?></td>
                                        <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                        <td class="points actual <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                        <td class="item-progress" style="width: 100px;">
                                            <div class="pgrs pgrs-tiny">
                                                <div class="bar <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= ($viewStatus->getPoints() / $view->getMaximumNumberOfPoints())*100 ?>%;"></div>
                                            </div>
                                        </td>
                                        <td class="links text-center" style="width: 120px;">
                                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                <div><?php echo $link->getHTML() ?></div>
                                            <?php endforeach ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>


                        <tr class="picker open">
                            <td class="name"><?= $otherGroup->getComplianceViewGroup()->getName() ?><div class="triangle"></div>
                            </td>
                            <td class="points target"><?= $otherMaxPoints ?></td>
                            <td class="points actual <?= $this->getClass($otherPoints, $otherMaxPoints) ?>"><?= $otherPoints ?></td>
                            <td class="pct">
                                <div class="pgrs">
                                    <div class="bar <?= $this->getClass($otherPoints, $otherMaxPoints) ?>" style="width: <?= ($otherPoints/$otherMaxPoints)*100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="details open">
                            <td colspan="4">
                                <table class="details-table">
                                    <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="points">Maximum</th>
                                        <th class="points">Actual</th>
                                        <th class="points">Progress</th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($otherGroup->getComplianceViewStatuses() as $viewStatus) : ?>
                                    <?php $view = $viewStatus->getComplianceView() ?>
                                    <tr class="view-hra">
                                        <td class="name"><?= $view->getReportName()?></td>
                                        <td class="points target"><?= $view->getMaximumNumberOfPoints()?></td>
                                        <td class="points actual <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>"><?= $viewStatus->getPoints() ?? 0?></td>
                                        <td class="item-progress" style="width: 100px;">
                                            <div class="pgrs pgrs-tiny">
                                                <div class="bar <?= $this->getClass($viewStatus->getPoints(), $view->getMaximumNumberOfPoints()) ?>" style="width: <?= ($viewStatus->getPoints() / $view->getMaximumNumberOfPoints())*100 ?>%;"></div>
                                            </div>
                                        </td>
                                        <td class="links text-center" style="width: 120px;">
                                            <?php foreach($viewStatus->getComplianceView()->getLinks() as $link) : ?>
                                                <div><?php echo $link->getHTML() ?></div>
                                            <?php endforeach ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>




                        </tbody>
                    </table>
                </div>
            </div>

            <script type="text/javascript">
                $(function() {

                    $('[toggle]').on('click', function(){
                        let value = $(this).attr('toggle');
                        let icon = $(this).find('i');
                        $('#'+value).toggle();
                        if (icon.hasClass('fa-chevron-right')) {
                            icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                        } else {
                            icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                        }
                    });

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
            <?php
        }
    }
