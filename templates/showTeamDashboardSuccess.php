<?php echo $sf_data->getRaw('program')->getActionTemplateCustomizations() ?>

<div class="nav-tabs-centered">
    <ul id="compliance_tabs" class="nav nav-tabs">
        <li id="tab-dashboard">
            <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
        </li>
        <li id="tab-buddy-dashboard" class="active">
            <a href="<?php echo url_for("compliance_programs/showTeamDashboard?id={$record->id}") ?>">My Team's Dashboard</a>
        </li>
        <?php if($is_owner_user && $can_manage_teams_buddies && (isset($_GET['admin']) || sfConfig::get('mod_compliance_programs_team_display_manage_team_tab', false)) ) : ?>
            <li id="tab-manage-team">
                <a href="<?php echo url_for("compliance_programs/manageTeam?id={$record->id}") ?>">Manage My Team</a>
            </li>
        <?php endif ?>
        <?php if($team_leaderboard) : ?>
            <li id="tab-leaderboard">
                <a href="<?php echo url_for("compliance_programs/teamLeaderboard?id={$record->id}") ?>">Team Leaderboard</a>
            </li>
        <?php endif ?>
    </ul>
</div>
<br/>
<?php if($is_owner_user && $alert_not_enough_members) : ?>
    <div class="alert alert-error">
        <h4>Your team needs more people!</h4>

        <p>Teams must contain <?php echo $team_members_minimum ?> members. Use the form under
            <em>Manage My Team</em> above to invite more people to your team.</p>
    </div>
<?php endif ?>

<?php echo $rendering ?>