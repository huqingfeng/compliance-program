<p>This utility will build a ZIP file containing one CSV for each compliance
    program that belongs to a given descendants of a selected parent client.</p>

<?php echo $form->renderFormTag(url_for('compliance_programs/batchAdminReport')) ?>
<ul>
    <?php echo $form ?>
    <li>
        <input type="submit" value="Download"/>
    </li>
</ul>
</form>