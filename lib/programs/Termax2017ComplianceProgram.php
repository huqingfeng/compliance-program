<?php

use hpn\steel\query\SelectQuery;

class Termax2017HomePageComplianceProgram extends ComplianceProgram
{
    public function loadEvaluators()
    {

    }

    public function loadGroups()
    {
        $user = sfContext::getInstance()->getUser()->getUser();

        $programStart = $this->getStartDate();
        $programEnd = $this->getEndDate();

        $group = new ComplianceViewGroup('Procedure');

        if(sfConfig::get('mod_compliance_programs_hmi_website_flow_show_contact_information', true)) {
            if(sfConfig::get('mod_compliance_programs_hmi_website_flow_use_update_contact_information_view', false)) {
                $contactView = new UpdateContactInformationComplianceView($programStart, $programEnd);
            } else {
                $contactView = new RequiredInformationEnteredComplianceView();
            }

            $contactView->setName('contact_information');
            $contactView->setReportName(___(sfConfig::get('mod_compliance_programs_hmi_website_flow_contact_information_title', 'Contact Information')));
            $contactView->setAttribute('text', ___(sfConfig::get('mod_compliance_programs_hmi_website_flow_contact_information_text', 'Enter all of your personal information.')));
            $contactView->setAttribute('continue', '/my_account/updateAll?redirect=/');
            $contactView->emptyLinks();
            $contactView->addLink(new Link(___('View/Change'), sfConfig::get('mod_compliance_programs_hmi_website_flow_contact_information_link', '/my_account/updateAll?redirect=/')));

            $group->addComplianceView($contactView);
        }

        if(sfConfig::get('mod_compliance_programs_hmi_website_flow_show_appointment', true)) {
            $appointmentView = new ScheduleAppointmentComplianceView(date('Y-m-d'), $programEnd);
            $appointmentView->setName('schedule_appointment');
            $appointmentView->setReportName(sfConfig::get('mod_compliance_programs_hmi_website_flow_schedule_title', 'Schedule'));
            $appointmentView->setAttribute('text', sfConfig::get('mod_compliance_programs_hmi_website_flow_schedule_text', 'Schedule an appointment.'));
            $appointmentView->setAttribute('continue', sfConfig::get('mod_compliance_programs_hmi_website_flow_schedule_link', '/content/1051'));
            $appointmentView->addLink(new Link('View/Change', '/content/1051'));

            if(sfConfig::get('mod_compliance_programs_hmi_website_flow_schedule_button_override')) {
                $appointmentView->emptyLinks();
                $appointmentView->addLink(new Link('Call to Schedule', '/content/how-to-schedule'));
                $appointmentView->setAttribute('always_show_links', true);
                $appointmentView->setAttribute('always_show_links_when_current', true);
                $appointmentView->setAttribute('continue', '?button_override=1');
                $appointmentView->setAttribute('button_override', true);

                $appointmentView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) use($appointmentView) {
                    if($user->getNewestDataRecord(
                        sfConfig::get('mod_compliance_programs_hmi_website_flow_hmi_schedule_button_override', HMIWebsiteFlowComplianceProgram::SCHEDULE_BUTTON_OVERRIDE_TYPE),
                        true)->clicked) {
                        $appointmentView->emptyLinks();
                        $appointmentView->addLink(new Link('View/Change', '/content/how-to-schedule'));
                        $status->setStatus(ComplianceStatus::COMPLIANT);
                    }
                });
            }

            $group->addComplianceView($appointmentView);
        }

        if(sfConfig::get('mod_compliance_programs_hmi_website_flow_show_hra', true)) {

            if ($user->getLanguage() == 'es') {
                $hraSurvey = new CompleteSurveyComplianceView(46);
                $hraSurvey->emptyLinks();
                $hraSurvey->addLink(new Link('Take', '/surveys/46'));
            } else {
                $hraSurvey = new CompleteSurveyComplianceView(43);
                $hraSurvey->emptyLinks();
                $hraSurvey->addLink(new Link('Take', '/surveys/43'));
            }

            $hraSurvey->setName('complete_survey');
            $hraSurvey->setAttribute('text', ___(sfConfig::get('mod_compliance_programs_hmi_website_flow_hpa_text', 'Complete the questionnaire')));
            $hraSurvey->setAttribute('always_show_links_when_current', true);
            $hraSurvey->setReportName(___(sfConfig::get('mod_compliance_programs_hmi_website_flow_hpa_title', 'HPA Questionnaire')));

            $hraSurvey->setPostEvaluateCallback(function (ComplianceViewStatus $status) {
                if(!$status->isCompliant()) {
                    $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
                }
            });

            $group->addComplianceView($hraSurvey);
        }

        $this->addComplianceViewGroup($group);

        if (sfConfig::get('mod_compliance_programs_hmi_website_flow_screening_hra_for_results', false)) {
            $resultsGroup = new ComplianceViewGroup('results_group');

            $resultsView = new CompleteHRAAndScreeningComplianceView($programStart, $programEnd);
            $resultsView->setName('results');

            $resultsGroup->addComplianceView($resultsView);
            $this->addComplianceViewGroup($resultsGroup);
        }
    }

    protected function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status)
    {
        if (($config = sfConfig::get('mod_compliance_programs_hmi_website_flow_record_integration'))
            && isset($config['compliance_program_record_id'], $config['views'])) {
            $record = ComplianceProgramRecordTable::getInstance()->find($config['compliance_program_record_id']);
            $extProgram = $record->getComplianceProgram() ;
            $extProgram->setActiveUser($status->getUser());
            $extStatus = $extProgram->getStatus();

            foreach($config['views'] as $extViewName => $localViewName) {
                if ($extStatus->getComplianceViewStatus($extViewName)->isCompliant()) {
                    $status->getComplianceViewStatus($localViewName)->setStatus(ComplianceStatus::COMPLIANT);
                }
            }
        }
    }
}