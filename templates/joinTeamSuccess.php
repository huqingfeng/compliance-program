<?php echo $sf_data->getRaw('program')->getActionTemplateCustomizations() ?>

<div class="nav-tabs-centered">
    <ul id="compliance_tabs" class="nav nav-tabs">
        <li id="tab-dashboard">
            <a href="<?php echo url_for("compliance_programs/index?id={$record_id}") ?>">My Dashboard</a>
        </li>
        <?php if($can_create_new_team) : ?>
        <li id="tab-new-team">
            <a href="<?php echo url_for("compliance_programs/newTeam?id={$record_id}") ?>">Create a New Team</a>
        </li>
        <?php endif ?>
        <li id="tab-join-team" class="active">
            <a href="<?php echo url_for("compliance_programs/joinTeam?id={$record_id}") ?>">Join an Existing Team</a>
        </li>
        <li id="tab-choose-buddy">
            <a href="<?php echo url_for("compliance_programs/chooseBuddy?id={$record_id}") ?>">Choose a Buddy</a>
        </li>
        <?php if($team_leaderboard) : ?>
            <li id="tab-leaderboard">
                <a href="<?php echo url_for("compliance_programs/teamLeaderboard?id={$record->id}") ?>">Team Leaderboard</a>
            </li>
        <?php endif ?>
    </ul>
</div>
<br/>
<p>To join an existing team, contact the captain of the team and ask them to invite you. Once the team captain invites you to join the team, you must accept the invitation in order to be on that team.</p>