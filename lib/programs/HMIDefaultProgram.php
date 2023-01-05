<?php
  class HMIDefaultProgram extends ComplianceProgram {
    public function getProgramReportPrinter($preferredPrinter = null) {
      return new HMIDefaultProgramReportPrinter();
    }

    public function loadGroups() {}
  }

  class HMIDefaultProgramReportPrinter extends BasicComplianceProgramReportPrinter {
    public function printReport(ComplianceProgramStatus $status) {
      ?>

      <style type="text/css">
        aside {
          padding: 0 1rem;
          background-color: #f8fafc;
          border: 1px solid #e7eaf2;
          border-radius: 0.25rem;
          font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        aside p {
          position: relative;
          padding-left: 5rem;
        }

        aside i {
          background-color: transparent !important;
          text-align: center;
          margin-top: -0.95rem;
          font-size: 1.4rem;
        }

        aside i, aside q {
          position: absolute;
          top: 63%;
          left: 1rem;
        }

        aside q {
          margin-top: -1.2rem;
        	background-color: #ffb65e;
        	text-align: left;
        	transform: rotate(-60deg) skewX(-30deg) scale(1,.866);
        }

        aside q:before, aside q:after {
        	content: '';
        	position: absolute;
        	background-color: inherit;
        }

        aside q, aside q:before, aside q:after {
          display: inline-block;
        	width:  1.5rem;
        	height: 1.5rem;
          border-radius: 0;
        	border-top-right-radius: 30%;
        }

        aside q:before {
        	transform: rotate(-135deg) skewX(-45deg) scale(1.414,.707) translate(0,-50%);
        }

        q:after {
        	transform: rotate(135deg) skewY(-45deg) scale(.707,1.414) translate(50%);
        }

        aside i {
          width: 1.5rem;
          height: 1.5rem;
          line-height: 1.5rem;
          background-color: #ced2db;
          border-radius: 999px;
          color: white;
          font-size: 1.25rem;
        }
      </style>

      <aside>
        <p>
          <q></q>
          <i class="fas fa-exclamation"></i>
          Although your employer may offer an incentive for participation in the
          wellness program, they currently do not offer this online tracking
          program.
        </p>
      </aside>

      <?php
    }
  }
?>
