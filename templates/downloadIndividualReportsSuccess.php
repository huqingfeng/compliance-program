<p>This utility will build a ZIP of compliance program PDFs.
    Please fill out the form below and select Download.</p>

<?php echo $form->renderFormTag(url_for('compliance_programs/downloadIndividualReports')) ?>
<ul>
    <?php echo $form ?>
    <li>
        <input type="submit" value="Download"/>
    </li>
</ul>
</form>