<?php
  use hpn\steel\query\UpdateQuery;

  class Revcor2022ComplianceProgram extends ComplianceProgram {
    public function getLocalActions() {
      return array(
        'dashboardCounts' => array($this, 'executeHealthRiskScore'),
        'setLanguage' => array($this, 'setLanguage')
      );
    }


    public function setLanguage(sfActions $actions) {
      $this->setActiveUser($actions->getSessionUser());

      UpdateQuery::create()
      ->update('users')
      ->set('language', $_GET['language'])
      ->where('id = (?)', [$_GET['userid']])
      ->execute();

      exit;
      return;
    }

    public function executeHealthRiskScore(sfActions $actions) {
      $this->setActiveUser($actions->getSessionUser());

      ?>

      <style type="text/css">
        .bold {
          font-weight: bold;
        }

        .title {
          font-size: 13pt;
        }
      </style>

      <?php

      if ($_GET['spanish']) {
        ?>
          <div>
            <p class="bold title" style="text-align: center">
              Acerca de la Puntuación de Riesgo para la Salud
            </p>
            <p class="bold title">
              ¿Cómo se calcula mi puntaje de riesgo para la salud?
            </p>
            <p>
              Su puntaje de riesgo para la salud se enfoca en 6 de sus resultados de detección. Tener resultados dentro del rango objetivo disminuye los riesgos y puede ayudar con muchos objetivos de salud y bienestar. Cuanto menor sea su puntaje de riesgo para la salud, más pueden ayudar sus resultados a alcanzar sus metas. Cuanto más alto sea el puntaje, mayores serán los riesgos de contraer afecciones graves y afecciones que pueden afectar su calidad de vida y otros objetivos.
            </p>

            <p>
              <ul>
                <li><span class="bold">Presión Arterial:</span> se agrega 1 punto por unidad de presión arterial por encima de 119/79 <span class="bold">(Sistólica/Diastólica)</span>. Se puede obtener un crédito de -5 puntos por CADA UNO de sus resultados que se encuentren en el rango objetivo o por debajo de él.</li>
                <li><span class="bold">Colesterol LDL:</span> se agrega 1 punto por unidad de LDL por encima de 99 mg / dl. Se puede obtener un crédito de -5 puntos si se encuentra en o por debajo de su objetivo de LDL.</li>
                <li><span class="bold">Glucosa:</span> se añade 1 punto por unidad de glucosa por encima de 99 mg / dl. Se puede obtener un crédito de -5 puntos si la glucosa es igual o inferior a 99 mg / dl.</li>
                <li><span class="bold">Triglicéridos:</span> se añade 1 punto por cada 10 unidades de triglicéridos por encima de 149 mg / dl. Se puede obtener un crédito de -5 puntos si los triglicéridos son iguales o inferiores a 149 mg / dl.</li>
                <li><span class="bold">Uso de Tabaco:</span> se agregan 40 puntos por el uso de cualquier producto de tabaco.</li>
              </ul>
            </p>

            <p class="bold title">¿Cómo se establece mi meta de puntuación de riesgo para la salud personal?</p>
            <p>Una puntuación de -20 significa que los 6 resultados están dentro del rango objetivo: ¡el objetivo ideal!</p>
            <p>
              Puede que ya estés allí. De lo contrario, una meta razonable es avanzar hacia la meta ideal. ¿Cuál de los 4 objetivos siguientes se aplica a usted? Cualquiera que sea su puntuación ahora, ¿qué tan rápido y lejos puede saltar y alcanzar la meta ideal? ¿Qué acciones te ayudarán a llegar allí?
            </p>

            <ol>
              <li>Si su puntuación actual es de –20 a cero, ¡enhorabuena! Siga haciendo las cosas que le ayuden a mantenerse en este rango. Mejor aún, tome las medidas necesarias para alcanzar y mantenerse en el puntaje ideal de -20.</li>
              <li>Si su puntaje actual es de 1 a 25, entonces esfuércese por alcanzar un puntaje de 0 o menos.</li>
              <li>Si su puntaje actual es de 26 a 40, entonces esfuércese por lograr un puntaje de 25 o menos.</li>
              <li>Si su puntaje actual es mayor a 40, entonces esfuércese por lograr un puntaje de 40 o menos.</li>
            </ol>

            <p class="bold title">¿Está buscando recursos que puedan ayudarlo con muchas decisiones y objetivos de salud y bienestar?</p>
            <p>
              Empower Health ofrece una amplia variedad de recursos útiles a través de este sitio web. Un lugar para comenzar es haciendo clic en los enlaces de las lecciones electrónicas para cada resultado de la evaluación anterior. O bien, vaya a la página de inicio y haga clic en Recursos de salud, atención y bienestar para explorar incluso más de 1,000 lecciones electrónicas, más de 500 videos, herramientas para la toma de decisiones médicas y más.
            </p>
            <p class="bold title">¿Necesitas más Ayuda?</p>
            <p>Si tiene preguntas adicionales, comuníquese con Empower Health llamando al <a href="tel:8663676974">866.367.6974</a> o enviando un correo electrónico a <a href="mailto:support@empowerhealthservices.com">support@empowerhealthservices.com</a></p>
          </div>
        <?php
      } else {
        ?>
          <div>
            <p class="bold title" style="text-align: center">
              About the Health Risk Score
            </p>
            <p class="bold title">
              How is my health risk score calculated?
            </p>
            <p>
              Your health risk score focuses on 6 of your screening results.
              Having results in the target range decreases risks and can help
              with many health and wellbeing goals. The lower your health risk
              score, the more your results can help toward your goals. The
              higher the score, the greater the risks are of getting serious
              conditions and conditions that can affect your quality of life
              and other goals.
            </p>

            <p>
              <ul>
                <li><span class="bold">Blood Pressure:</span> 1 point is added per BP unit above 119/79 <span class="bold">(Systolic/Diastolic)</span>. A credit of -5 points can be earned for EACH of your results that are at or below the target range.</li>
                <li><span class="bold">LDL Cholesterol:</span> 1 point is added per LDL unit above 99 mg/dl.  A credit of -5 points can be earned if you are at or below your LDL target. </li>
                <li><span class="bold">Glucose:</span> 1 point is added per Glucose unit above 99 mg/dl. A credit of -5 points can be earned if Glucose is at or below 99 mg/dl.</li>
                <li><span class="bold">Triglycerides:</span> 1 point is added per 10 Triglyceride units above 149 mg/dl. A credit of -5 points can be earned if Triglycerides are at or below 149 mg/dl.</li>
                <li><span class="bold">Tobacco Use:</span> 40 points are added for using any tobacco product.</li>
              </ul>
            </p>

            <p class="bold title">How is my personal health risk score goal set?</p>
            <p>A score of -20 means all 6 results are in the target range – the ideal goal!</p>
            <p>
              You may be there already. If not, a reasonable goal is making jumps of progress toward the ideal goal.
              Which of the 4 goals below applies to you?  Whatever your score is now, how fast and far can you jump
              toward and reach the ideal goal?  What actions will help you get there?
            </p>

            <ol>
              <li>If your current score is –20 to zero, congratulations!  Keep doing the things that help you to stay in this range. Better yet, take the actions needed to get to and stay at the ideal score of -20.</li>
              <li>If your current score is 1 to 25, then strive to reach a score of 0 or less.</li>
              <li>If your current score is 26 to 40, then strive to achieve a score of 25 or less</li>
              <li>If your current score is greater than 40, then strive to achieve a score of 40 or less</li>
            </ol>

            <p class="bold title">Looking for resources that can help with many health and wellbeing decisions and goals?</p>
            <p>
              Empower Health offers a wide variety of helpful resources through this website.  A place to start is by
              clicking on the e-lesson links for each screening result above.  Or, go to the home page and click on Health,
              Care & Wellbeing Resources to explore even 1,000+ e-lessons, 500+ videos, medical decision-making tools and more.
            </p>
            <p class="bold title">Need more Assistance?</p>
            <p>Should you have additional questions, please contact Empower Health by calling <a href="tel:8663676974">(866)367-6974</a> or by emailing <a href="mailto:support@empowerhealthservices.com">support@empowerhealthservices.com</a></p>
          </div>
        <?php
      }
    }

    public function getAdminProgramReportPrinter() {
      $printer = new BasicComplianceProgramAdminReportPrinter();

      $printer->setShowUserContactFields(null, null, true);
      $printer->setShowCompliant(null, null, null);

      $printer->addEndStatusFieldCallBack('Health Risk Score', function(ComplianceProgramStatus $status) {
        return $status->getAttribute('health_risk_points');
      });

      return $printer;
    }

    public function getProgramReportPrinter($preferredPrinter = null) {
      return new Revcor2022ComplianceProgramReportPrinter();
    }

    public function hasPartiallyCompliantStatus() {
      return false;
    }

    public function loadGroups() {
      $programStart = $this->getStartDate();
      $programEnd = $this->getEndDate();

      $coreGroup = new ComplianceViewGroup('core', 'Core Program');

      $screeningView = new CompleteScreeningComplianceView($programStart, $programEnd);
      $screeningView->setReportName('A.Complete Wellness Screening');
      $screeningView->setName('screening');
      $screeningView->emptyLinks();
      $screeningView->addLink(new Link('Sign-Up', '/content/1051?action=appointmentList&filter[type]='));
      $screeningView->addLink(new Link('Results', '/content/989'));
      $coreGroup->addComplianceView($screeningView);

      $hraView = new CompleteHRAComplianceView($programStart, $programEnd);
      $hraView->setReportName('B.	Complete Empower Risk Assessment');
      $hraView->setName('hra');
      $hraView->emptyLinks();
      $hraView->addLink(new Link('Take HPA', '/content/989'));
      $hraView->addLink(new Link('Results', '/content/989'));
      $coreGroup->addComplianceView($hraView);

      $this->addComplianceViewGroup($coreGroup);

      $biometric = new ComplianceViewGroup('biometric', 'Biometric');
      $biometric->setPointsRequiredForCompliance(70);


      $systolicView = new ComplyWithSystolicBloodPressureScreeningTestComplianceView($programStart, $programEnd);
      $systolicView->setReportName('A. Blood pressure – Systolic');
      $systolicView->setName('systolic');
      $systolicView->overrideTestRowData(null, null, 119, null);
      $systolicView->setAttribute('goal', '≤ 119');
      $systolicView->emptyLinks();
      $systolicView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $result = $status->getAttribute('real_result');
        $target = 119;

        if($result && is_numeric($result)) {
          $difference = $result - $target;
          $status->setAttribute('result_target', $difference);

          if($difference <= 0)
            $status->setAttribute('health_risk_points', -5);
          else
            $status->setAttribute('health_risk_points', $difference);
        }
      });

      $biometric->addComplianceView($systolicView);

      $diastolicView = new ComplyWithDiastolicBloodPressureScreeningTestComplianceView($programStart, $programEnd);
      $diastolicView->setReportName('B. Blood Pressure – Diastolic');
      $diastolicView->setName('diastolic');
      $diastolicView->overrideTestRowData(null, null, 79, null);
      $diastolicView->setAttribute('goal', '≤ 79');
      $diastolicView->emptyLinks();
      $diastolicView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $result = $status->getAttribute('real_result');
        $target = 79;

        if($result && is_numeric($result)) {
          $difference = $result - $target;
          $status->setAttribute('result_target', $difference);

          if($difference <= 0)
            $status->setAttribute('health_risk_points', -5);
          else
            $status->setAttribute('health_risk_points', $difference);
        }
      });

      $biometric->addComplianceView($diastolicView);

      $ldlView = new ComplyWithLDLScreeningTestComplianceView($programStart, $programEnd);
      $ldlView->setReportName('C. LDL Cholesterol');
      $ldlView->setName('ldl');
      $ldlView->overrideTestRowData(null, null, 99, null);
      $ldlView->setAttribute('goal', '≤ 99');
      $ldlView->emptyLinks();
      $ldlView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $result = $status->getAttribute('real_result');
        $target = 99;

        if($result && is_numeric($result)) {
          $difference = $result - $target;
          $status->setAttribute('result_target', $difference);

          if($difference <= 0)
            $status->setAttribute('health_risk_points', -5);
          else
            $status->setAttribute('health_risk_points', $difference);
        }
      });

      $biometric->addComplianceView($ldlView);

      $trigView = new ComplyWithTriglyceridesScreeningTestComplianceView($programStart, $programEnd);
      $trigView->setReportName('D. Triglycerides');
      $trigView->setName('triglycerides');
      $trigView->overrideTestRowData(null, null, 149, null);
      $trigView->setAttribute('goal', '≤ 149');
      $trigView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $result = $status->getAttribute('real_result');
        $target = 149;

        if($result && is_numeric($result)) {
          $difference = $result - $target;
          $status->setAttribute('result_target', $difference);

          if($difference <= 0)
            $status->setAttribute('health_risk_points', -5);
          else
            $status->setAttribute('health_risk_points', round($difference/10));
        }
      });

      $biometric->addComplianceView($trigView);

      $glucoseView = new ComplyWithGlucoseScreeningTestComplianceView($programStart, $programEnd);
      $glucoseView->setReportName('E.	Glucose');
      $glucoseView->setName('glucose');
      $glucoseView->overrideTestRowData(null, null, 99, null);
      $glucoseView->setAttribute('goal', '≤ 99');
      $glucoseView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $result = $status->getAttribute('real_result');
        $target = 99;

        if($result && is_numeric($result)) {
            $difference = $result - $target;
            $status->setAttribute('result_target', $difference);

            if($difference <= 0)
              $status->setAttribute('health_risk_points', -5);
            else
              $status->setAttribute('health_risk_points', $difference);
        }
      });
      $biometric->addComplianceView($glucoseView);


      $tobaccoView = new ComplyWithCotinineScreeningTestComplianceView($programStart, $programEnd);
      $tobaccoView->setReportName('F. Tobacco Use - <span style="font-size:10pt; font-style: italic;">includes any type: cigarettes, cigars, pipe, chew & dip</span>');
      $tobaccoView->setName('tobacco');
      $tobaccoView->setAttribute('goal', 'N or Negative');
      $tobaccoView->setPostEvaluateCallback(function(ComplianceViewStatus $status, User $user) {
        $result = $status->getComment();

        if($result == 'P' || $result == 'Positive')
          $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
        else if($result == 'N' || $result == 'Negative')
          $status->setStatus(ComplianceStatus::COMPLIANT);

        if($status->getStatus() == ComplianceStatus::COMPLIANT)
          $status->setAttribute('health_risk_points', 0);
        else
          $status->setAttribute('health_risk_points', 40);
      });

      $biometric->addComplianceView($tobaccoView);

      $this->addComplianceViewGroup($biometric);

      $forceOverrideGroup = new ComplianceViewGroup('Force Override');

      $forceCompliant = new PlaceHolderComplianceView(ComplianceStatus::NOT_COMPLIANT);
      $forceCompliant->setName('force_compliant');
      $forceCompliant->setReportName('AQF Override');
      $forceOverrideGroup->addComplianceView($forceCompliant);

      $this->addComplianceViewGroup($forceOverrideGroup);
    }

    public function evaluateAndStoreOverallStatus(ComplianceProgramStatus $status) {
        parent::evaluateAndStoreOverallStatus($status);

        $biometricGroup = $status->getComplianceViewGroupStatus('biometric');

        $forceOverride = $status->getComplianceViewStatus('force_compliant');

        $healthRiskPoints = 0;
        foreach($biometricGroup->getComplianceViewStatuses() as $viewStatus) {
          if($viewStatus->getAttribute('health_risk_points')) {
            $healthRiskPoints += $viewStatus->getAttribute('health_risk_points');
          }
        }

        if($healthRiskPoints <= 0) {
          $status->setStatus(ComplianceStatus::COMPLIANT);
          $biometricGroup->setStatus(ComplianceStatus::COMPLIANT);
        } elseif($forceOverride->getStatus() == ComplianceStatus::COMPLIANT) {
          $status->setStatus(ComplianceStatus::COMPLIANT);
          $biometricGroup->setStatus(ComplianceStatus::COMPLIANT);
        } else {
          $status->setStatus(ComplianceStatus::NOT_COMPLIANT);
          $biometricGroup->setStatus(ComplianceStatus::NOT_COMPLIANT);
        }

        $status->setAttribute('health_risk_points', $healthRiskPoints);
      }
  }

  class Revcor2022ComplianceProgramReportPrinter implements ComplianceProgramReportPrinter {
    public function printReport(ComplianceProgramStatus $status) {
      $user = $status->getUser();

      if(trim(strtolower($user->miscellaneous_data_1)) == 'noletter') {
          return;
      }

      ?>

      <style type="text/css">
        .letter {
          font-family:Arial, sans-serif;
          font-size:11pt;
          height:11in;
          margin: 0 20px;
          position: relative;
        }

        .headerRow {
          background-color:#88b2f6;
          font-weight:bold;
          font-size:10pt;
          height:35px;
        }

        .bund {
          font-weight:bold;
          text-decoration:underline;
        }

        .light {
          width:0.3in;
        }

        #results {
          width:98%;
          margin:0 0.1in;
        }

        #results th, td {
          padding:0.01in 0.05in;
          border:0.01in solid #000;
          text-align:center;
          padding: 1px;
        }

        #results .status-<?= ComplianceStatus::COMPLIANT ?> {
          background-color:#90FF8C;
        }

        #results .status-<?= ComplianceStatus::PARTIALLY_COMPLIANT ?> {
          background-color:#F9FF8C;
        }

        #results .status-<?= ComplianceStatus::NOT_COMPLIANT ?> {
          background-color:#DEDEDE;
        }

        #not_compliant_notes p{
          margin: 3px 0;
        }

        #ratingsTable tr{
          height: 35px;
        }

        .activity_name {
          padding-left: 10px;
        }

        .noBorder {
          border:none !important;
        }

        .right {
          text-align: right;
          padding-right: 10px;
        }

        .bold {
          font-weight: bold;
        }

        .underline{
          text-decoration: underline;
        }

        .color_details {
          margin-bottom: 10px;
        }

        .print {
          position: absolute;
          top: 0;
          left: 0;
          border: 1px solid #0096fb;
          padding: 0.5rem 1.5rem;
          text-align: center;
          border-radius: 5px;
          text-decoration: none;
          color: #0096fb;
          letter-spacing: 0.2rem;
        }

        .print:hover, .print:active, .print:focus {
          background-color: #0096fb;
          color: white !important;
          text-decoration: none;
        }

        .language-toggle {
          position: absolute;
          top: 0;
          right: 0;
          display: flex;
          align-items: center;
          color: #404447;
          cursor: pointer;
          width: 12rem;
        }

        .language-toggle > img {
          padding-left: 16px;
        }

        .language-toggle ul {
          position: absolute;
          top: 100%;
          left: 0;
          display: none;
          margin: 0;
          margin-top: 0.5rem;
          padding: 10px 18px;
          width: calc(100% - 20px);
          list-style-type: none;
          background-color: #fffefe;
          border-radius: 5px;
          box-shadow: 0 0 6px #B2B2B2;
        }

        .language-toggle.open ul {
          display: inline-block;
        }

        .language-toggle ul::before {
          content: "\00a0";
          position: absolute;
          top: -5px;
          left: 27px;
          display: block;
          height: 15px;
          width:  15px;
          background-color: #fffefe;
          transform:         rotate( -45deg );
          -moz-transform:    rotate( -45deg );
          -ms-transform:     rotate( -45deg );
          -o-transform:      rotate( -45deg );
          -webkit-transform: rotate( -45deg );
          box-shadow: 2px -2px 2px 0 rgba( 178, 178, 178, .4 );
        }
      </style>

      <style type="text/css" media="print">
        body {
          margin:0.5in;
          padding:0;
        }

        .language-toggle {
          display: none;
        }
      </style>

      <script>
        $( document ).ready(function() {
          $('.language-toggle').on('click', function (event) {
            event.stopPropagation();

            if ($(this).hasClass('open'))
              $(this).removeClass('open');
            else
              $(this).addClass('open');
          });

          $(window).on('click', function () {
            $('.language-toggle').removeClass('open');
          });

          $('.language-toggle ul li').on('click', function () {
            let language = $(this).attr('data-language');
            $('.letter').css('display', 'none');
            $(`#${language}`).css('display', 'block');
            $.ajax({url: `/compliance_programs/localAction?id=1711&local_action=setLanguage&language=${language}&userid=<?=$user->id?>`});
          });
        });
      </script>

      <div id="en" class="letter"<?= $user->language == 'en' ? '' : ' style="display: none;"'?>>
        <p style="clear: both;">
          <div style="float: left; width: 46%;">
            <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
            4205 Westbrook Drive<br />
            Aurora, IL 60504
          </div>

          <div style="float: right; width: 48%; text-align: right">
            <img src="/images/empower/revcor_logo.gif" style="height:50px;"  />
          </div>
        </p>

        <p style="clear: both">&nbsp;</p>

        <div style="margin-left:10px;position:relative;">
          <a href="/content/print_reportcard?view=true" class="print"><i class="far fa-print"></i> PRINT</a>
          <div class="language-toggle">
            <img src="/images/i18n/english.png" /> ENGLISH
            <ul>
              <li data-language="es"><img src="/images/i18n/spanish.png" /> ESPAÑOL</li>
            </ul>
          </div>
          <p style="margin: 2rem auto; font-weight: bold; font-size: 16pt; color:#38474f;border-bottom: 3px solid #38474f; width: fit-content; padding-bottom: 3px;">
            2022 WELLNESS INCENTIVE PROGRAM
          </p>
          <p>
            Thank you for your participation in the Empower Health screening program.  Through this program, each
            participant is provided with an overall health risk score and a personal health risk score goal.
          </p>
          <p>
            To earn the full incentive for this year, participants must achieve an overall health risk score of 0 or less.
          </p>
          <p>
            In order to earn the full incentive for next year, participants will need to achieve an overall health
            risk score of 0 or less or meet their personal health risk score goal.
          </p>
        </div>

        <?= $this->getTable($status) ?>

        <div id="not_compliant_notes">
            <div style="width: 56%; float: left">
              <p>
                Login to <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> any time to:
                <ul>
                  <li>View all of your screening results and links in the report;  AND</li>
                  <li>Access powerful tools and resources for optimizing your health, care and well-being.</li>
                </ul>

                <p>
                  Thank you for getting your wellness screening done this year. This and many of your other
                  actions reflect how you value your own well-being and the well-being of others at home
                  and work.
                </p><br />

                Best Regards,<br /><br />
                Empower Health Services
              </p>
            </div>

            <div style="width: 43%; float: right; background-color: #cceeff;">
                <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Some of these online tools include:</div>
                <div style="font-size: 9pt;">
                  <ul>
                    <li>Over 1,000 e-lessons</li>
                    <li>
                      The Healthwise® Knowledgebase for decisions about medical tests, medicines, other
                      treatments, risks and other topics
                    </li>
                    <li>Over 500 videos</li>
                    <li>Decision tools for over 170 elective care decisions</li>
                    <li>Cholesterol, body metrics, blood sugars, women’s health, men’s health and over 40 other learning centers.</li>
                  </ul>
                </div>
              </div>
          </div>
      </div>

      <div id="es" class="letter"<?= $user->language == 'es' ? '' : ' style="display: none;"'?>>
        <p style="clear: both;">
          <div style="float: left; width: 46%;">
            <img src="/images/empower/ehs_logo.jpg" style="height:50px;" /><br />
            4205 Westbrook Drive<br />
            Aurora, IL 60504
          </div>

          <div style="float: right; width: 48%; text-align: right">
            <img src="/images/empower/revcor_logo.gif" style="height:50px;"  />
          </div>
        </p>

        <p style="clear: both">&nbsp;</p>

        <div style="margin-left:10px;position:relative;">
          <a href="/content/print_reportcard?view=true" class="print"><i class="far fa-print"></i> IMPRIMER</a>
          <div class="language-toggle">
            <img src="/images/i18n/spanish.png" /> ESPAÑOL
            <ul>
              <li data-language="en"><img src="/images/i18n/english.png" /> ENGLISH</li>
            </ul>
          </div>
          <p style="margin: 2rem auto; font-weight: bold; font-size: 16pt; color:#38474f;border-bottom: 3px solid #38474f; width: fit-content; padding-bottom: 3px;">
            2022 Programa de Incentivos de Bienestar
          </p>
          <p>
            Gracias por su participación en el programa de detección de Empower Health. A través de este programa, a cada participante se le proporciona una puntuación de riesgo de salud general y una meta de puntuación de riesgo de salud personal.
          </p>
          <p>
            Para obtener el incentivo completo de este año, los participantes deben lograr una puntuación de riesgo de salud general de 0 o menos.
          </p>
          <p>
            Para ganar el incentivo completo para el próximo año, los participantes deberán lograr una puntuación de riesgo de salud general de 0 o menos o cumplir con su meta de puntuación de riesgo de salud personal.
          </p>
        </div>

        <?= $this->getSpanishTable($status) ?>

        <div id="not_compliant_notes">
            <div style="width: 56%; float: left">
              <p>
                Inicie sesión en <a href="https://empowerhealthservices.hpn.com">https://empowerhealthservices.hpn.com</a> en cualquier momento para:
                <ul>
                  <li>Ver todos los enlaces y los resultados de la detección en el informe; Y</li>
                  <li>Acceda a poderosas herramientas y recursos para optimizar su salud, atención y bienestar.</li>
                </ul>

                <p>
                  Gracias por hacerse su examen de bienestar este año. Esta y muchas de sus otras acciones reflejan cómo valora su propio bienestar y el bienestar de los demás en el hogar y el trabajo.
                </p><br />

                Atentamente,<br /><br />
                Empoderar los Servicios de Salud
              </p>
            </div>

            <div style="width: 43%; float: right; background-color: #cceeff;">
                <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Las herramientas en línea incluyen:</div>
                <div style="font-size: 9pt;">
                  <ul>
                    <li>Más de 1,000 lecciones</li>
                    <li>
                      El Healthwise Knowledgebase® para decisiones
                      sobre exámenes médicos, medicamentos,
                      tratamientos, riesgos y más
                    </li>
                    <li>Más de 500 videos</li>
                    <li>Herramientas para más de 170 opciones deatención electiva</li>
                    <li>Colesterol, métricas corporales, azúcares en la sangre, información sobre la salud de las mujeres, información sobre la salud de los hombres y más de 40 centros de aprendizaje adicionales</li>
                  </ul>
                </div>
              </div>
          </div>
      </div>

      <p style="clear: both;">&nbsp;</p>

      <?= $this->getJSON($status) ?>

      <?php
    }

    private function getTable($status) {
      $user = $status->getUser();
      $screeningStatus = $status->getComplianceViewStatus('screening');
      $hraStatus = $status->getComplianceViewStatus('hra');

      $systolicStatus = $status->getComplianceViewStatus('systolic');
      $diastolicStatus = $status->getComplianceViewStatus('diastolic');
      $ldlStatus = $status->getComplianceViewStatus('ldl');
      $triglyceridesStatus = $status->getComplianceViewStatus('triglycerides');
      $glucoseStatus = $status->getComplianceViewStatus('glucose');
      $tobaccoStatus = $status->getComplianceViewStatus('tobacco');
      $forceOverride = $status->getComplianceViewStatus('force_compliant');

      ob_start();
      ?>

      <p style="text-align:center">
        <table id="results">
          <tbody>
              <tr>
                <td colspan="6" style="text-align: left"><?= $user->first_name.' '.$user->last_name ?></td>
              </tr>
              <tr class="headerRow">
                <th colspan="2" style="text-align: left; width: 430px;">1. Get Started – Get these done by 2/4/2022</th>
                <th colspan="2">Date Done</th>
                <th colspan="1">Status</th>
                <th colspan="1" style="width: 160px;">Action Links</th>
              </tr>
              <tr class="status-<?= $screeningStatus->getStatus() ?>">
                <td colspan="2" style="text-align: left;" class="activity_name"><?= $screeningStatus->getComplianceView()->getReportName() ?></td>
                <td colspan="2"><?= $screeningStatus->getComment() ?></td>
                <td colspan="1"><?= $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Done' : 'Not Done' ?></td>
                <td colspan="1" class="center">
                  <?php
                    foreach($screeningStatus->getComplianceView()->getLinks() as $link)
                      echo $link->getHTML()."\n";
                  ?>
                </td>
              </tr>
              <tr class="status-<?= $hraStatus->getStatus() ?>">
                <td colspan="2" style="text-align: left;" class="activity_name"><?= $hraStatus->getComplianceView()->getReportName() ?> (optional)</td>
                <td colspan="2"><?= $hraStatus->getComment() ?></td>
                <td colspan="1"><?= $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Done' : 'Not Done' ?></td>
                <td colspan="1" class="center">
                  <?php
                    foreach($hraStatus->getComplianceView()->getLinks() as $link)
                      echo $link->getHTML()."\n";
                  ?>
                </td>
              </tr>
              <tr class="headerRow">
                <th style="text-align: left; width: 260px;">2. Some key health measures to get in the target goal range</th>
                <th>2022 Target Goal</th>
                <th>My Result</th>
                <th style="width: 130px;">The Amount My Result is Above or Below Target</th>
                <th style="width: 100px;">Health Risk Points & Credits (-)</th>
                <th style="width: 160px;">Action Links</th>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1"><?= $systolicStatus->getComplianceView()->getReportName() ?></td>
                <td><?= $systolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $systolicStatus->getComment() ?></td>
                <td><?= $systolicStatus->getAttribute('result_target') ?></td>
                <td><?= $systolicStatus->getAttribute('health_risk_points') ?></td>
                <td colspan="1" rowspan="2" class="center">
                  <a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Explore e-lessons</a>
                </td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1"><?= $diastolicStatus->getComplianceView()->getReportName() ?></td>
                <td><?= $diastolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $diastolicStatus->getComment() ?></td>
                <td><?= $diastolicStatus->getAttribute('result_target') ?></td>
                <td><?= $diastolicStatus->getAttribute('health_risk_points') ?></td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1"><?= $ldlStatus->getComplianceView()->getReportName() ?></td>
                <td><?= $ldlStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $ldlStatus->getComment() ?></td>
                <td><?= $ldlStatus->getAttribute('result_target') ?></td>
                <td><?= $ldlStatus->getAttribute('health_risk_points') ?></td>
                <td colspan="1" rowspan="2" class="center">
                  <a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Explore e-lessons</a>
                </td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1"><?= $triglyceridesStatus->getComplianceView()->getReportName() ?></td>
                <td><?= $triglyceridesStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $triglyceridesStatus->getComment() ?></td>
                <td><?= $triglyceridesStatus->getAttribute('result_target') ?></td>
                <td><?= $triglyceridesStatus->getAttribute('health_risk_points') ?></td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1"><?= $glucoseStatus->getComplianceView()->getReportName() ?></td>
                <td><?= $glucoseStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $glucoseStatus->getComment() ?></td>
                <td><?= $glucoseStatus->getAttribute('result_target') ?></td>
                <td><?= $glucoseStatus->getAttribute('health_risk_points') ?></td>
                <td colspan="1" class="center">
                  <a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Explore e-lessons</a>
                </td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1"><?= $tobaccoStatus->getComplianceView()->getReportName() ?></td>
                <td><?= $tobaccoStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td colspan="2"><?= $tobaccoStatus->getComment() ?></td>
                <td><?= $tobaccoStatus->getAttribute('health_risk_points') ?></td>
                <td colspan="1" class="center">
                  <a href="/content/9420?action=lessonManager&tab_alias=tobacco">Explore e-lessons</a>
                </td>
              </tr>
              <tr class="headerRow">
                <td colspan="4" style="text-align: left;">3.	Goals and My Total Health Risk Score</td>
                <td>Health Risk Score</td>
                <td>Is score ≤ 0 or is AQF received?</td>
              </tr>
              <tr>
                <td colspan="4" style="text-align: left;">
                  <ol style="list-style-type: upper-alpha;">
                    <li>If your score is 0 or less &#8594; <span class="bold" style="color: green">Congratulations!  The lower the better!</span></li>
                    <li>If your score is 1-25 &#8594; Your goal is to reach 0 or less in 2023</li>
                    <li>If your score is 26-40 &#8594; Your goal is to reach 25 or less in 2023</li>
                    <li>If your score is 41 or more &#8594; Your goal is to reach 40 or less in 2023</li>
                  </ol>
                </td>
                <td colspan="1"><?= $status->getAttribute('health_risk_points') ?></td>
                <td colspan="1" class="center bold">
                  <?php
                    if($status->getStatus() == ComplianceViewStatus::COMPLIANT)
                      echo 'Yes, Congrats!';
                    elseif($forceOverride->getStatus() == ComplianceStatus::COMPLIANT)
                      echo 'AQF Received';
                    else
                      echo 'Not yet!  See 3E';
                  ?>
                </td>
              </tr>
              <tr>
                <td colspan="6">
                  <p style="margin:10px; width:95%;">
                    E. If risk score is >0, you may still earn the full incentive by having your physician
                     complete and submit the Alternate Qualification Form by 3/4/2022.  You can
                     <a href="/pdf/clients/revcor/2022_AQF.pdf" download="2022_AQF">download</a>
                     the form here and submit it back to Empower Health via:
                     <ul style="margin-left:100px; margin-top:-10px;text-align: left;">
                      <li>Fax completed form to 630.385.0156 - Attn: Reports Department</li>
                      <li>Mail completed form to: EHS Reports Department - 4205 Westbrook Drive, Aurora, IL 60504</li>
                     </ul>
                  </p>

                  <p>
                    See action links to learn more about each & ways to reach & stay in the ideal range.
                    <a href="/compliance_programs/localAction?id=1711&local_action=dashboardCounts">About the health risk score</a>.
                  </p>
                </td>
              </tr>
            </tbody>
        </table>
      </p>

      <?php

      return ob_get_clean();
    }

    private function getSpanishTable($status) {
      $user = $status->getUser();
      $screeningStatus = $status->getComplianceViewStatus('screening');
      $hraStatus = $status->getComplianceViewStatus('hra');

      $systolicStatus = $status->getComplianceViewStatus('systolic');
      $diastolicStatus = $status->getComplianceViewStatus('diastolic');
      $ldlStatus = $status->getComplianceViewStatus('ldl');
      $triglyceridesStatus = $status->getComplianceViewStatus('triglycerides');
      $glucoseStatus = $status->getComplianceViewStatus('glucose');
      $tobaccoStatus = $status->getComplianceViewStatus('tobacco');
      $forceOverride = $status->getComplianceViewStatus('force_compliant');

      ob_start();
      ?>

      <p style="text-align:center">
        <table id="results">
          <tbody>
              <tr>
                <td colspan="6" style="text-align: left"><?= $user->first_name.' '.$user->last_name ?></td>
              </tr>
              <tr class="headerRow">
                <th colspan="2" style="text-align: left; width: 430px;">1. Comience: termínelos antes del 2/4/2022</th>
                <th colspan="2">Fecha de Finalización</th>
                <th colspan="1">Estado</th>
                <th colspan="1" style="width: 160px;">Enlaces de Acción</th>
              </tr>
              <tr class="status-<?= $screeningStatus->getStatus() ?>">
                <td colspan="2" style="text-align: left;" class="activity_name">A. Examen de detección de bienestar completo</td>
                <td colspan="2"><?= $screeningStatus->getComment() ?></td>
                <td colspan="1"><?= $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Hecho' : 'No Hecho' ?></td>
                <td colspan="1" class="center">
                  <?php
                    foreach($screeningStatus->getComplianceView()->getLinks() as $link)
                      echo str_replace('Sign-Up', 'Registro', str_replace('Results', 'Resultados', $link->getHTML())) . "\n";
                  ?>
                </td>
              </tr>
              <tr class="status-<?= $hraStatus->getStatus() ?>">
                <td colspan="2" style="text-align: left;" class="activity_name">B. Evaluación completa de riesgos de empoderamiento (opcional)</td>
                <td colspan="2"><?= $hraStatus->getComment() ?></td>
                <td colspan="1"><?= $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Hecho' : 'No Hecho' ?></td>
                <td colspan="1" class="center">
                  <?php
                    foreach($hraStatus->getComplianceView()->getLinks() as $link)
                      echo str_replace('Take HPA', 'Tome HPA', str_replace('Results', 'Resultados', $link->getHTML())) . "\n";
                  ?>
                </td>
              </tr>
              <tr class="headerRow">
                <th style="text-align: left; width: 260px;">2. Algunas medidas de salud clave para alcanzar el rango objetivo</th>
                <th>Meta 2022</th>
                <th>Mi resultado</th>
                <th style="width: 130px;">La cantidad que mi resultado está por encima o por debajo del objetivo</th>
                <th style="width: 100px;">Puntos y créditos de riesgo para la salud (-)</th>
                <th style="width: 160px;">Enlaces de Acción</th>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1">A. Presión arterial - sistólica</td>
                <td><?= $systolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $systolicStatus->getComment() ?></td>
                <td><?= $systolicStatus->getAttribute('result_target') ?></td>
                <td><?= $systolicStatus->getAttribute('health_risk_points') ?></td>
                <td colspan="1" rowspan="2" class="center">
                  <a href="/content/9420?action=lessonManager&tab_alias=blood_pressure">Explore las lecciones electrónicas</a>
                </td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1">B. Presión arterial - diastólica</td>
                <td><?= $diastolicStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $diastolicStatus->getComment() ?></td>
                <td><?= $diastolicStatus->getAttribute('result_target') ?></td>
                <td><?= $diastolicStatus->getAttribute('health_risk_points') ?></td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1">C. Colesterol LDL</td>
                <td><?= $ldlStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $ldlStatus->getComment() ?></td>
                <td><?= $ldlStatus->getAttribute('result_target') ?></td>
                <td><?= $ldlStatus->getAttribute('health_risk_points') ?></td>
                <td colspan="1" rowspan="2" class="center">
                  <a href="/content/9420?action=lessonManager&tab_alias=cholesterol">Explore las lecciones electrónicas</a>
                </td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1">D. Triglicéridos</td>
                <td><?= $triglyceridesStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $triglyceridesStatus->getComment() ?></td>
                <td><?= $triglyceridesStatus->getAttribute('result_target') ?></td>
                <td><?= $triglyceridesStatus->getAttribute('health_risk_points') ?></td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1">E. Glucosa</td>
                <td><?= $glucoseStatus->getComplianceView()->getAttribute('goal') ?></td>
                <td><?= $glucoseStatus->getComment() ?></td>
                <td><?= $glucoseStatus->getAttribute('result_target') ?></td>
                <td><?= $glucoseStatus->getAttribute('health_risk_points') ?></td>
                <td colspan="1" class="center">
                  <a href="/content/9420?action=lessonManager&tab_alias=blood_sugars">Explore las lecciones electrónicas</a>
                </td>
              </tr>
              <tr>
                <td style="text-align: left;" colspan="1">F. Consumo de tabaco: <span style="font-size:10pt; font-style: italic;">incluye cualquier tipo: cigarrillos, puros, pipa, masticar y mojar.</span></td>
                <td>N o Negativo</td>
                <td colspan="2"><?= $tobaccoStatus->getComment() ?></td>
                <td><?= $tobaccoStatus->getAttribute('health_risk_points') ?></td>
                <td colspan="1" class="center">
                  <a href="/content/9420?action=lessonManager&tab_alias=tobacco">Explore las lecciones electrónicas</a>
                </td>
              </tr>
              <tr class="headerRow">
                <td colspan="4" style="text-align: left;">3.	Metas y mi puntaje total de riesgo para la salud</td>
                <td>Puntaje de Riesgo para la Salud</td>
                <td>¿La puntuación es ≤ 0 o se recibe AQF?</td>
              </tr>
              <tr>
                <td colspan="4" style="text-align: left;">
                  <ol style="list-style-type: upper-alpha;">
                    <li>Si su puntaje es 0 o menos &#8594; <span class="bold" style="color: green">¡Felicitaciones! ¡Cuanto más bajo mejor!</span></li>
                    <li>Si su puntaje es 1-25 &#8594; Su meta es llegar a 0 o menos en 2023</li>
                    <li>Si su puntaje es 26-40 &#8594; Su meta es llegar a 25 o menos en 2023</li>
                    <li>Si su puntaje es 41 o más &#8594; Su meta es llegar a 40 o menos en 2023</li>
                  </ol>
                </td>
                <td colspan="1"><?= $status->getComplianceViewGroupStatus('biometric')->getAttribute('health_risk_points') ?></td>
                <td colspan="1" class="center bold">
                  <?php
                    if($status->getStatus() == ComplianceViewStatus::COMPLIANT)
                      echo 'Si, Felicidades!';
                    elseif($forceOverride->getStatus() == ComplianceStatus::COMPLIANT)
                      echo 'AQF Recibid' . ($user->getGender() == 'M' ? 'o' : 'a');
                    else
                      echo '¡Aún no! Ver 3E';
                  ?>
                </td>
              </tr>
              <tr>
                <td colspan="6">
                  <p style="margin:10px; width:95%;">
                    E. Si la puntuación de riesgo es> 0, aún puede ganar el incentivo completo si su médico completa y envía el Formulario de calificación alternativa antes del 3/4/2022. Puede:
                     <a href="/pdf/clients/revcor/2022_AQF.pdf" download="2022_AQF">descargar</a>
                     el formulario aquí y enviarlo a Empower Health a través de:
                     <ul style="margin-left:100px; margin-top:-10px;text-align: left;">
                      <li>Envíe el formulario completado por fax al 630.385.0156 - Attn: Reports Department</li>
                      <li>Envíe el formulario completo a: Departamento de Informes de EHS - 4205 Westbrook Drive, Aurora, IL 60504</li>
                     </ul>
                  </p>

                  <p>
                    Consulte los enlaces de acción para obtener más información sobre cada uno y las formas de alcanzar y mantenerse en el rango ideal
                    <a href="/compliance_programs/localAction?id=1711&local_action=dashboardCounts&spanish=true">Acerca de la puntuación de riesgo para la salud</a>.
                  </p>
                </td>
              </tr>
            </tbody>
        </table>
      </p>

      <?php

      return ob_get_clean();
    }


    private function getJSON($status) {
      $user = $status->getUser();
      $screeningStatus = $status->getComplianceViewStatus('screening');
      $hraStatus = $status->getComplianceViewStatus('hra');

      $systolicStatus = $status->getComplianceViewStatus('systolic');
      $diastolicStatus = $status->getComplianceViewStatus('diastolic');
      $ldlStatus = $status->getComplianceViewStatus('ldl');
      $triglyceridesStatus = $status->getComplianceViewStatus('triglycerides');
      $glucoseStatus = $status->getComplianceViewStatus('glucose');
      $tobaccoStatus = $status->getComplianceViewStatus('tobacco');
      $forceOverride = $status->getComplianceViewStatus('force_compliant');

      if($status->getStatus() == ComplianceViewStatus::COMPLIANT) {
        $english_aqf = 'Yes, Congrats!';
        $spanish_aqf = 'Si, Felicidades!';
      } elseif($forceOverride->getStatus() == ComplianceStatus::COMPLIANT) {
        $english_aqf = 'AQF Received';
        $spanish_aqf = 'AQF Recibid' . ($user->getGender() == 'M' ? 'o' : 'a');
      } else {
        $english_aqf = 'Not yet!  See 3E';
        $spanish_aqf = '¡Aún no! Ver 3E';
      }

      $json = array(
        'screening_date' => $screeningStatus->getComment() ? $screeningStatus->getComment() : '',
        'screening_status' => $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'done' : '',
        'hra_date' => $hraStatus->getComment() ? $hraStatus->getComment() : '',
        'hra_status' => $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'done' : '',
        'systolic_result' => $systolicStatus->getComment(),
        'systolic_range' => $systolicStatus->getAttribute('result_target') !== Null ? $systolicStatus->getAttribute('result_target') : '',
        'systolic_points' => $systolicStatus->getAttribute('health_risk_points') !== Null ? $systolicStatus->getAttribute('health_risk_points') : '',
        'diastolic_result' => $diastolicStatus->getComment(),
        'diastolic_range' => $diastolicStatus->getAttribute('result_target') !== Null ? $diastolicStatus->getAttribute('result_target') : '',
        'diastolic_points' => $diastolicStatus->getAttribute('health_risk_points') !== Null ? $diastolicStatus->getAttribute('health_risk_points') : '',
        'ldl_result' => $ldlStatus->getComment(),
        'ldl_range' => $ldlStatus->getAttribute('result_target') !== Null ? $ldlStatus->getAttribute('result_target') : '',
        'ldl_points' => $ldlStatus->getAttribute('health_risk_points') !== Null ? $ldlStatus->getAttribute('health_risk_points') : '',
        'triglycerides_result' => $triglyceridesStatus->getComment(),
        'triglycerides_range' => $triglyceridesStatus->getAttribute('result_target') !== Null ? $triglyceridesStatus->getAttribute('result_target') : '',
        'triglycerides_points' => $triglyceridesStatus->getAttribute('health_risk_points') !== Null ? $triglyceridesStatus->getAttribute('health_risk_points') : '',
        'glucose_result' => $glucoseStatus->getComment(),
        'glucose_range' => $glucoseStatus->getAttribute('result_target') !== Null ? $glucoseStatus->getAttribute('result_target') : '',
        'glucose_points' => $glucoseStatus->getAttribute('health_risk_points') !== Null ? $glucoseStatus->getAttribute('health_risk_points') : '',
        'tobacco_result' => $tobaccoStatus->getComment(),
        'tobacco_points' => $tobaccoStatus->getAttribute('health_risk_points') !== Null ? $tobaccoStatus->getAttribute('health_risk_points') : '',
        'overall_points' => $status->getAttribute('health_risk_points') !== Null ? $status->getAttribute('health_risk_points') : '',
        'english' => array(
          'screening_status' => $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Done' : 'Not Done',
          'hra_status' => $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Done' : 'Not Done',
          'aqf' => $english_aqf
        ),
        'spanish' => array(
          'screening_status' => $screeningStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Hecho' : 'No Hecho',
          'hra_status' => $hraStatus->getStatus() == ComplianceViewStatus::COMPLIANT ? 'Hecho' : 'No Hecho',
          'aqf' => $spanish_aqf
        )
      );

      ob_start();
      ?>

      <p style="display: none;">
        JSON<?= json_encode($json) ?>JSON
      </p>

      <?php

      return ob_get_clean();
    }
  }
?>
