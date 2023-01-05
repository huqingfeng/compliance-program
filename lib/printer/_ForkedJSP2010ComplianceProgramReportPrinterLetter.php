<?php
class JSP2010ComplianceProgramReportPrinterLetter implements ComplianceProgramReportPrinter
{
    public function printReport(ComplianceProgramStatus $status)
    {
        $user = $status->getUser();

        $printer = new JSP2010ComplianceProgramReportPrinter();
        ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        </head>
        <body>
            <div style="width:8.5in;height:11in;" id="letter">

                <table style="margin-bottom:0.25in">

                    <tr>
                        <td style="vertical-align:top;padding-left:0.3in;">
                            <h4>J.S. Paluch Benefit Plan Notice</h4><img src="/images/jsp/jspletter1.jpg"
                            style="width:0.4in;position:absolute;"/>

                            <div style="padding-left:0.5in;position:relative;top:-0.1in;">
                                c/o HPN Compliance Center<br/>119 W Vallette<br/>Elmhurst, IL 60126<br/><span
                                style="color:#0000FF;font-weight:bold">Action Items to Keep Your Premium Low</span>
                            </div>
                            <br/><br/>
                            <br/><br/><br/>
                            <?php echo $user->getFullName() ?><br/>
                            <?php echo $user->getFullAddress('<br/>') ?><br/><br/><br/><br/>

                            <div style="position:relative;left:-0.3in;">Dear <?php echo $user->getFirstName() ?>:</div>


                        </td>
                        <td style="width:0.2in;"></td>
                        <td style="vertical-align:top;">
                            <div style="text-align:center;margin:0 0 0.2in 0;">Print
                                Date: <?php echo date('m/d/Y') ?></div>
                            <div><strong>J.S. Paluch Health Plan Requirement Reminder</strong></div>
                            <br/>


                            <p>Thanks for all requirements that you have completed to date.</p>


                            <p>And, thank you for all your other actions to help assure better
                                health, health care and benefits for you and others you care about.</p>

                            <div style="text-align:left;position:relative;left:-40px;top:120px;"><span
                                style="color:#FF0000;font-weight:bold;font-size:1.1em;">2nd Compliance Reminder Notice as of: </span><span
                                style="color:#0000FF;font-weight:bold"><?php echo date('m/d/Y') ?></span></div>

                        </td>
                    </tr>
                </table>

                <div style="font-size:0.875em;">
                    <p>You are receiving this letter as a reminder to do something important. It’s for your better
                        health <u>and</u> so you can save a lot of money –
                        now and in the future.</p>

                    <p>As you know, with the J.S. Paluch health plan:</p>
                    <ul>
                        <li>ALL participating employees and spouses must meet certain requirements EACH quarter.</li>
                        <li>These requirements are actions that must be taken by the end of each quarter.</li>
                        <li><b><u>One</u></b> of these requirements is to:&nbsp;&nbsp;<span
                            style="color:#0000FF;font-weight:bold;"> Work with a doctor <u>or</u> health coach if your biometric score is less than 60.</span>
                        </li>
                    </ul>


                    <p><span style="color:#0000FF;font-weight:bold;style=" font-size:1.2em;"">Your biometric score
                        is <?php echo $status->getPoints() === null ? 0 : $status->getPoints() ?>.</span>  This comes
                        from your wellness screening in the Spring/Early Summer.</p>


                    <strong style="font-size:1.2em;"><i>So, which of the following will you do – option #1 or #2
                        ?</i></strong><br/>

                    <ol>
                        <li><strong style="font-size:1.2em;">Work with a health coach</strong> - see below & back of
                            this letter (page 2)
                            <ul>
                                <li>You can earn credit for a coaching sequence (2-5 calls as determined by you and your
                                    coach).
                                </li>
                                <li>All coaching calls are done by appointment – current available times are on most
                                    Mondays, Tuesdays and
                                    Thursdays.
                                </li>
                                <li>Make the first appointment online or by phone – see page 2 for details.</li>
                                <li>All coaching is confidential and HIPAA compliant.</li>
                                <li>This option involves no personal expense.</li
                            </ul>
                        </li>
                        <li><strong style="font-size:1.2em;">Work with your own medical doctor</strong> – see below &
                            enclosed form
                            <ul>
                                <li>This requires at least 1 visit with your doctor and returning the <u>enclosed
                                    form</u> after it is properly completed form
                                    signed by you AND your doctor.
                                </li>
                                <li>You must bring the <u>enclosed form</u> AND your Health Power Assessment report to
                                    the visit with your doctor.
                                </li>
                                <li>You are responsible for returning the properly completed form – NOT your doctor’s
                                    office.
                                </li>
                                <li>Once completed, you can mail it (using the enclosed business reply envelope) or fax
                                    it – see bottom of form.
                                </li>
                                <li>This option will involve applicable out-of-pocket expenses (co-pays, co-insurance,
                                    deductibles).
                                </li
                            </ul>
                        </li>
                    </ol>
                    <p><b>Decide which option works best for you – THEN – take action now to get it done!</b></p>

                    <p>Remember, to keep your premium contribution low and avoid increases of $120-$240 or more in
                        payroll deductions over the next 3
                        months (beginning November):</p>
                    <ul>
                        <li>You must get one of the above options done by October 31, 2010; AND</li>
                        <li>Get ALL other quarterly actions required done by October 31, 2010.</li>
                    </ul>
                    <p>Use the <u style="color:#0000FF">www.jsp-healthadvantage.com</u>:</p>
                    <ul>
                        <li>To review details about your score, ALL your other requirements, status, and check for
                            messages
                        </li>
                        <li>To get things done and to use powerful tools for your better health and health care.</li>
                    </ul>
                    <p>Thank you, in advance, for your prompt action regarding this reminder.</p>
                </div>
            </div>
            <style type="text/css">
                table td {
                    width:50%;
                }

                #letter {
                    padding:0.2in;
                }

                #report {
                    font-size:0.75em;
                    text-align:left;
                }

                #report ul {
                    margin:0;
                    padding:0;
                    list-style-position:inside;
                }

                #information, #_title {
                    display:none;
                }

                body {
                    font-size:0.90em;
                }

                #sfWebDebug {
                    display:none;
                }
            </style>
        </body>
    </html>
    <?php
    }
}