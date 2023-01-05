<?php echo $sf_data->getRaw('program')->getActionTemplateCustomizations() ?>

<div class="nav-tabs-centered">
    <ul id="compliance_tabs" class="nav nav-tabs">
        <?php if($has_team) : ?>
            <li id="tab-dashboard">
                <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
            </li>
            <li id="tab-team-dashboard">
                <a href="<?php echo url_for("compliance_programs/showTeamDashboard?id={$record->id}") ?>">My Team's Dashboard</a>
            </li>
            <?php if($is_owner_user && $can_manage_teams_buddies && (isset($_GET['admin']) || sfConfig::get('mod_compliance_programs_team_display_manage_team_tab', false) )) : ?>
                <li id="tab-manage-team">
                    <a href="<?php echo url_for("compliance_programs/manageTeam?id={$record->id}") ?>">Manage My Team</a>
                </li>
            <?php endif ?>
        <?php elseif($has_buddy) : ?>
            <li id="tab-dashboard">
                <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
            </li>
            <li id="tab-buddy-dashboard">
                <a href="<?php echo url_for("compliance_programs/showBuddyDashboard?id={$record->id}") ?>">My Buddy's Dashboard</a>
            </li>
        <?php elseif($has_team_invite || $sent_buddy_request || $has_buddy_request) : ?>
            <li id="tab-dashboard">
                <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
            </li>
        <?php else : ?>
            <li id="tab-dashboard">
                <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
            </li>
            <?php if($can_manage_teams_buddies) : ?>
                <?php if($can_create_new_team) : ?>
                    <li id="tab-new-team">
                        <a href="<?php echo url_for("compliance_programs/newTeam?id={$record->id}") ?>">Create a New Team</a>
                    </li>
                <?php endif ?>
                <li id="tab-join-team">
                    <a href="<?php echo url_for("compliance_programs/joinTeam?id={$record->id}") ?>">Join an Existing Team</a>
                </li>
                <li id="tab-choose-buddy">
                    <a href="<?php echo url_for("compliance_programs/chooseBuddy?id={$record->id}") ?>">Choose a Buddy</a>
                </li>
            <?php endif ?>
        <?php endif ?>
        <li id="tab-leaderboard" class="active">
            <a href="<?php echo url_for("compliance_programs/teamLeaderboard?id={$record->id}") ?>">Team Leaderboard</a>
        </li>
    </ul>
</div>

<br/>

<style type="text/css">
    #accordion a.accordion-toggle {
        text-decoration:none;
    }

    .steps-label {
        font-size:13px;
        padding:3px 4px;
    }
</style>

<?php if($total_steps) : ?>
    <p style="text-align:right;" id="total-steps">
    <span class="steps-label label label-info">
        <?php echo number_format($total_points) ?> total steps (Company Wide)
    </span>
    </p>
<?php endif ?>

<div class="accordion" id="accordion">
    <?php $i = 1 ?>
    <?php foreach($teams as $teamName => $teamData) : ?>
        <?php $shortTeamName = Doctrine_Inflector::urlize($teamName) ?>
        <div class="accordion-group">
            <div class="accordion-heading">
                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="<?php echo sprintf('#team-%s', $shortTeamName) ?>">
                    <?php echo "#{$i} {$teamName}" ?>
                    <div class="pull-right">
                        <span class="steps-label label label-info"><?php echo number_format($teamData['average_points']) ?> <?php echo "average $points_label" ?></span>
                        <span class="steps-label label label-info total-steps"><?php echo number_format($teamData['points']) ?> total <?php echo $points_label ?></span>
                    </div>
                </a>
            </div>
            <div id="<?php echo sprintf('team-%s', $shortTeamName) ?>" class="accordion-body collapse">
                <table class="table table-condensed table-hover" style="width:50%;margin:0 auto;">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th><?php echo $points_label ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $j = 1 ?>
                    <?php foreach($teamData['users'] as $userId => $userData) : ?>
                        <tr class="accordion-inner">
                            <td><?php echo "#{$j} {$userData['name']}" ?></td>
                            <td><?php echo number_format($userData['points']) ?></td>
                        </tr>
                        <?php $j++ ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php $i++ ?>
    <?php endforeach ?>
</div>

<p>Team average is based on total points divided by the number of team
    members.</p>

<script>
    $(function(){
        $('body').on('click', '.accordion-toggle', function(){
            var showHide = $(this).attr('href');
            $('.accordion-body').hide();
            $(showHide).fadeIn();
        });
    });
</script>