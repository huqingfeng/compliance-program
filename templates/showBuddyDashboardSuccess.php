<?php echo $sf_data->getRaw('program')->getActionTemplateCustomizations() ?>

<div class="nav-tabs-centered">
    <ul id="compliance_tabs" class="nav nav-tabs">
        <li id="tab-dashboard">
            <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
        </li>
        <li id="tab-buddy-dashboard" class="active">
            <a href="<?php echo url_for("compliance_programs/showBuddyDashboard?id={$record->id}") ?>">My Buddy's Dashboard</a>
        </li>
        <?php if($team_leaderboard) : ?>
            <li id="tab-leaderboard">
                <a href="<?php echo url_for("compliance_programs/teamLeaderboard?id={$record->id}") ?>">Team Leaderboard</a>
            </li>
        <?php endif ?>
    </ul>
</div>
<br/>
<?php echo $rendering ?>