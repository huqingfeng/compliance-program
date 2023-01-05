<?php use_javascript('/apps/frontend/modules/compliance_programs/js/edit_compliance_program_exceptions.js') ?>

<?php if(sfConfig::get('mod_compliance_programs_show_document_uploader_link', false) && !$sessionUser->hasAttribute(Attribute::CLIENT_WELLNESS_COORDINATOR)) : ?>
    <a href="<?php echo sprintf('/content/chp-document-uploader?user_id=%s', $user->id) ?>" target="_blank" class="btn btn-primary" style="margin: 10px 0;">Upload and download PDFs</a>
<?php endif ?>

<?php if(sfConfig::get('mod_compliance_programs_show_screening_entry_link') && !$sessionUser->hasAttribute(Attribute::CLIENT_WELLNESS_COORDINATOR)) : ?>
    <div><a href="/content/112345">Screening Entry & Management</a></div>
<?php endif ?>

<p>
    You are editing compliance exceptions for <?php echo $user ?>.
    <?php if($program_record->description) : ?>
    <?php echo sprintf('(%s)', $program_record->description) ?>
    <?php endif ?>
</p>

<p>To change the status of a user's compliance, assign new required dates, or
    to mark a view as not required, simply fill out the form and select save.</p>

<?php if(!empty($program_points)) : ?>
    <p><?php echo sprintf('Total Points: <span class="label label-info">%s</span>', $program_points) ?></p>
<?php endif ?>


<?php echo $form->renderFormTag(url_for('compliance_programs/editComplianceProgramExceptions?id='.$program_record->id.'&user_id='.$user->id), array('id' => 'edit_compliance_program_exceptions')) ?>
<ul>
    <?php echo $form ?>
    <li>
        <input type="submit" value="Save"/>
    </li>
</ul>
</form>