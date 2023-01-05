<?php echo $sf_data->getRaw('compliance_program')->getActionTemplateCustomizations() ?>

<?php $wms3_modifier = (sfConfig::get('wms3_fitness_tracking', false)) ? "WMS3" : ""; ?>

<?php if($allow_teams) : ?>

    <div class="nav-tabs-centered">
        <ul id="compliance_tabs" class="nav nav-tabs">
            <?php if($has_team) : ?>
                <li id="tab-dashboard" class="active">
                    <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
                </li>
                <li id="tab-team-dashboard">
                    <a href="<?php echo url_for("compliance_programs/showTeamDashboard".$wms3_modifier."?id={$record->id}") ?>">My Team's Dashboard</a>
                </li>
                <?php if($is_owner_user && $can_manage_teams_buddies) : ?>
                    <li id="tab-manage-team">
                        <a href="<?php echo url_for("compliance_programs/manageTeam?id={$record->id}") ?>">Manage My Team</a>
                    </li>
                <?php endif ?>
            <?php elseif($has_buddy) : ?>
                <li id="tab-dashboard" class="active">
                    <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
                </li>
                <li id="tab-buddy-dashboard">
                    <a href="<?php echo url_for("compliance_programs/showBuddyDashboard?id={$record->id}") ?>">My Buddy's Dashboard</a>
                </li>
            <?php elseif($has_team_invite || $sent_buddy_request || $has_buddy_request) : ?>
                <li id="tab-dashboard" class="active">
                    <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
                </li>
            <?php else : ?>
                <li id="tab-dashboard" class="active">
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

            <?php if($team_leaderboard) : ?>
                <li id="tab-leaderboard">
                    <a href="<?php echo url_for("compliance_programs/teamLeaderboard".$wms3_modifier."?id={$record->id}") ?>">Team Leaderboard</a>
                </li>
            <?php endif ?>
        </ul>
        <br/>
    </div>
    
    <br/>

    <?php if($has_team_invite) : ?>
        <div class="alert alert-block alert-info">
            <h4>You have a team invite.</h4>

            <p class="buddy-info"><?php echo $owner_user ?> sent you an invitation to join <em><?php echo $team['name'] ?></em>. To respond, select a choice below.</p>

            <br/>

            <div style="text-align:center">
                <form style="display:inline" method="post" action="<?php echo url_for('compliance_programs/invitationRespond') ?>">
                    <input type="hidden" name="id" value="<?php echo $record->id ?>" />
                    <input class="btn btn-primary" type="submit" value="Accept Invitation" name="_accept" />
                    <input class="btn btn-danger" type="submit" value="Decline Invitation" name="_decline" />
                </form>
            </div>
        </div>
    <?php elseif($has_team) : ?>
        <div class="alert alert-success">
            You are a member of <em><?php echo $team['name'] ?></em>. To view your team's dashboard, select
                <em>My Team's Dashboard</em> above.
        </div>

        <?php if($is_owner_user && $alert_not_enough_members && $can_manage_teams_buddies) : ?>
            <div class="alert alert-error">
                <h4>Your team needs more people!</h4>

                <p>Teams must contain <?php echo $team_members_minimum ?> members. Use the form under
                    <em>Manage My Team</em> above to invite more people to your team.</p>
            </div>
        <?php endif ?>
    <?php elseif($has_buddy) : ?>
        <div class="alert alert-success" style="text-align: center;">
            Your buddy is <?php echo $buddy_user ?>. To view his or her dashboard, select <em>My Buddy's Dashboard</em> above.

            <?php if ((isset($allow_buddy_removal) && $allow_buddy_removal) || isset($_GET['admin'])) : ?>
                <br/>
                <br/>
                <form style="" method="post" action="<?php echo url_for('compliance_programs/buddyRemove') ?>">
                    <input type="hidden" name="id" value="<?php echo $record->id ?>" />
                    <input class="btn btn-danger" type="submit" value="Remove Buddy" />
                </form>
            <?php endif ?>
        </div>
    <?php elseif($sent_buddy_request) : ?>
        <div class="alert alert-block alert-info">
            <h4>Your buddy request is pending.</h4>

            <p class="buddy-info">You sent <?php echo $buddy_user ?> a buddy request. To modify or cancel this request, select Cancel Invitation below.</p>

            <br/>

            <div style="text-align:center">
                <form style="display:inline" method="post" action="<?php echo url_for('compliance_programs/buddyInvitationCancel') ?>">
                    <input type="hidden" name="id" value="<?php echo $record->id ?>" />
                    <input class="btn btn-danger" type="submit" value="Cancel Invitation" />
                </form>
            </div>
        </div>
    <?php elseif($has_buddy_request) : ?>
        <div class="alert alert-block alert-info">
            <h4>You have a buddy invite.</h4>

            <p class="buddy-info"><?php echo $buddy_user ?> sent you an invitation to be his or her buddy. To respond, select a choice below.</p>

            <br/>

            <div style="text-align:center">
                <form style="display:inline" method="post" action="<?php echo url_for('compliance_programs/buddyInvitationRespond') ?>">
                    <input type="hidden" name="id" value="<?php echo $record->id ?>" />
                    <input class="btn btn-primary" type="submit" value="Accept Invitation" name="_accept" />
                    <input class="btn btn-danger" type="submit" value="Decline Invitation" name="_decline" />
                </form>
            </div>
        </div>
    <?php elseif($can_manage_teams_buddies) : ?>
        <div id="no-team-or-buddy-instructions" class="alert alert-block alert-info">
            <h4>You haven't joined a team or selected a buddy yet.</h4>
            <p>To create a new team, select <em>Create a new team</em> above. If you wish
                to join an existing team, contact the captain of the team and ask
                them to invite you. If you only want to work with one other individual,
                select <em>Choose a buddy</em> above to partner up with them.</p>
        </div>
    <?php endif ?>
<?php elseif ($record->id == 1150): ?>

<div class="nav-tabs-centered">
    <ul id="compliance_tabs" class="nav nav-tabs">
    <li id="tab-dashboard" class="active">
        <a href="<?php echo url_for("compliance_programs/index?id={$record->id}") ?>">My Dashboard</a>
    </li>
    <?php if($team_leaderboard) : ?>
        <li id="tab-leaderboard">
            <a href="<?php echo url_for("compliance_programs/teamLeaderboard?id={$record->id}") ?>">Leaderboard</a>
        </li>
    <?php endif ?>
    </ul>
</div>
<?php endif ?>

<?php echo $compliance_program->render($sf_request->getParameter('preferredPrinter'), ESC_RAW) ?>
