<?php echo $sf_data->getRaw('program')->getActionTemplateCustomizations() ?>

<?php $wms3_modifier = (sfConfig::get('wms3_fitness_tracking', false)) ? "WMS3" : ""; ?>

<div class="nav-tabs-centered">
    <ul id="compliance_tabs" class="nav nav-tabs">
        <li id="tab-dashboard">
            <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
        </li>
        <li id="tab-buddy-dashboard" class="active">
            <a href="<?php echo url_for("compliance_programs/showTeamDashboard".$wms3_modifier."?id={$record->id}") ?>">My Team's Dashboard</a>
        </li>
        <?php if($is_owner_user && $can_manage_teams_buddies) : ?>
            <li id="tab-manage-team">
                <a href="<?php echo url_for("compliance_programs/manageTeam?id={$record->id}") ?>">Manage My Team</a>
            </li>
        <?php endif ?>
        <?php if($team_leaderboard) : ?>
            <li id="tab-leaderboard">
                <a href="<?php echo url_for("compliance_programs/teamLeaderboard".$wms3_modifier."?id={$record->id}") ?>">Team Leaderboard</a>
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
<div class="page-header">
    <h3><?= $team_name ?></h3>
</div>
 
<table class="table table-striped">
    <thead>
        <tr>
            <th>Team Member</th>
            <th>Total Steps</th>
            <th>Average Daily Steps</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($campaign_data as $user) : ?>
        <tr> 
            <td><?= $user->first_name?> <?= $user->last_name?></td>
            <td><?= number_format($user->total_steps)?></td>
            <td><?= number_format($user->daily_steps_average)?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th>Grand Totals</th>
            <th colspan="2"><?= number_format($team_grand_total,0,".",",") ?></th>
        </tr>
        <tr>
            <th>Average (By member)</th>
            <th><?= number_format($team_average_total,0,".",",") ?></th>
            <th><?= number_format($team_average_daily,0,".",",") ?></th>
        </tr>
    </tfoot>
</table>
