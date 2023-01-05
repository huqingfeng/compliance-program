<?php echo $sf_data->getRaw('program')->getActionTemplateCustomizations() ?>

<div class="nav-tabs-centered">
    <ul id="compliance_tabs" class="nav nav-tabs">
        <li id="tab-dashboard">
            <a href="<?php echo url_for("compliance_programs/index?id={$record_id}") ?>">My Dashboard</a>
        </li>
        <?php if($can_create_new_team) : ?>
        <li id="tab-new-team" class="active">
            <a href="<?php echo url_for("compliance_programs/newTeam?id={$record_id}") ?>">Create a New Team</a>
        </li>
        <?php endif ?>
        <li id="tab-join-team">
            <a href="<?php echo url_for("compliance_programs/joinTeam?id={$record_id}") ?>">Join an Existing Team</a>
        </li>
        <li id="tab-choose-buddy">
            <a href="<?php echo url_for("compliance_programs/chooseBuddy?id={$record_id}") ?>">Choose a Buddy</a>
        </li>
        <?php if($team_leaderboard) : ?>
            <li id="tab-leaderboard">
                <a href="<?php echo url_for("compliance_programs/teamLeaderboard?id={$record_id}") ?>">Team Leaderboard</a>
            </li>
        <?php endif ?>
    </ul>
</div>
<br/>
<div id="new-team-instructions">
    <p>To create a new team, fill out the form below and select <em>Create</em>. Afterwards, you will
        have the ability to invite people to join your team.</p>
</div>

<?php echo $form->renderFormTag(url_for("compliance_programs/newTeam?id={$record_id}")) ?>
    <ul>
        <?php echo $form ?>
    </ul>

    <?php if (isset($_GET['duplicate'])): ?>
    <div style="color: red">The team name "<?php echo $_GET['duplicate']?>" has already been taken. Please choose a different team name.</div>
    <?php endif; ?>

    <div class="form-actions">
        <input class="btn btn-primary" type="submit" value="Create" />
        <input class="btn" type="button" href="<?php echo url_for("compliance_programs/index?id={$record_id}") ?>" value="Cancel" onclick="window.location.href = $(this).attr('href');" />
    </div>
