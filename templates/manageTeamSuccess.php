<?php echo $sf_data->getRaw('program')->getActionTemplateCustomizations() ?>

<?php $wms3_modifier = (sfConfig::get('wms3_fitness_tracking', false)) ? "WMS3" : ""; ?>

<script type="text/javascript">
    $(function(){
        $('#save_team_name').click(function() {
            if($('input[name=new_team_name]').val() == '') {
                alert('Team name cannot be empty!');
                return false;
            }
        });
    });

</script>

<style>
    .mobile {
        display: none !important;
    }
</style>

<div class="nav-tabs-centered">
    <ul id="compliance_tabs" class="nav nav-tabs">
        <li id="tab-dashboard">
            <a href="<?php echo url_for("compliance_programs/index?id={$record_id}") ?>">My Dashboard</a>
        </li>
        <li id="tab-team-dashboard">
            <a href="<?php echo url_for("compliance_programs/showTeamDashboard".$wms3_modifier."?id={$record->id}") ?>">My Team's Dashboard</a>
        </li>
        <li id="tab-manage-team" class="active">
            <a href="<?php echo url_for("compliance_programs/manageTeam?id={$record_id}") ?>">Manage My Team</a>
        </li>
        <?php if($team_leaderboard) : ?>
            <li id="tab-leaderboard">
                <a href="<?php echo url_for("compliance_programs/teamLeaderboard".$wms3_modifier."?id={$record->id}") ?>">Team Leaderboard</a>
            </li>
        <?php endif ?>
    </ul>
</div>
<br/>
<div class="page-header">
    <h3>Managing <em><?php echo $team['name'] ?></em></h3>
</div>

<?php if($alert_not_enough_members) : ?>
    <div id="alert-more-people" class="alert alert-error">
        <h4>Your team needs more people!</h4>

        <p>Teams must contain <?php echo $team_members_minimum ?> members. Use the form under
            <em>Invite</em> below to invite more people to your team.</p>
    </div>
<?php elseif($alert_not_enough_members_accepted) : ?>
    <div id="alert-more-accepted-people" class="alert alert-error">
        <h4>Your team members need to accept their invitations!</h4>

        <p>Teams must contain <?php echo $team_members_minimum ?> members. As captain, it is
            your responsibility to make sure that all of the team's members have accepted their
            invitation.</p>
    </div>
<?php endif ?>

<div class="tabbable">
    <ul class="nav nav-tabs">
        <?php if(!$alert_maximum_members) : ?>
            <li class="active"><a href="#invite" data-toggle="tab">Invite</a></li>
        <?php endif ?>
        <li class="<?php echo $alert_maximum_members ? 'active' : '' ?>"><a href="#members" data-toggle="tab">Members</a></li>
        <li><a href="#team" data-toggle="tab">Team</a></li>
    </ul>
    <div class="tab-content">
        <?php if(!$alert_maximum_members) : ?>
            <div class="tab-pane active" id="invite">
                <div class="well well-small">
                    <div id="manage-team-instructions">
                        <p>To invite a person to your team, enter their last name below and select <em>Search</em>.
                            Then, select <em>Invite</em> next to the appropriate person.</p>
                    </div>

                    <form class="form-search input-append" method="get" action="<?php echo url_for("compliance_programs/manageTeam") ?>">
                        <input type="hidden" name="id" value="<?php echo $record_id ?>" />
                        <input type="text" name="last_name" value="<?php echo $last_name ?>" class="search-query" placeholder="Search by last name" style="border-radius:0" />
                        <button type="submit" class="btn" id="search-submit">
                            <i class="icon-search"></i> Search
                        </button>
                    </form>
                </div>

                <?php if(isset($males) && isset($females)) : ?>
                    <?php $femaleActive = count($males) === 0 && count($females) > 0; ?>
                    <?php $maleActive = !$femaleActive; ?>
                    <div class="alert alert-warning"><?php echo sprintf('%s users were found with the given information.', count($males) + count($females)) ?></div>

                    <div class="tabbable tabs-left">
                        <ul class="nav nav-tabs">
                            <li class="<?php echo $maleActive ? 'active' : ''; ?>">
                                <a href="#tab-invite-men" data-toggle="tab">Men</a>
                            </li>
                            <li class="<?php echo $femaleActive ? 'active' : ''; ?>">
                                <a href="#tab-invite-women" data-toggle="tab">Women</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane <?php echo $maleActive ? 'active' : '' ?>" id="tab-invite-men">
                                <?php if($remove_males) : ?>
                                    <div class="alert alert-warning">Your team has already has the maximum number of men allowed.</div>
                                <?php elseif(!count($males)) : ?>
                                    <div class="alert alert-warning">0 men were found with the given information.</div>
                                <?php else : ?>
                                    <table class="table table-hover table-condensed">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach($males as $id => $user) : ?>
                                            <tr>
                                                <td>
                                                    <?php echo $user['display_name'] ?>
                                                </td>
                                                <td style="text-align:right">
                                                    <form class="form-inline" style="margin:0" method="post" action="<?php echo url_for("compliance_programs/teamInviteUser?id={$record_id}") ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $id ?>" />
                                                        <input class="btn btn-primary" type="submit" value="Invite" />
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach ?>
                                        </tbody>
                                    </table>
                                <?php endif ?>
                            </div>
                            <div class="tab-pane <?php echo $femaleActive ? 'active' : '' ?>" id="tab-invite-women">
                                <?php if($remove_females) : ?>
                                    <div class="alert alert-warning">Your team has already has the maximum number of women allowed.</div>
                                <?php elseif(!count($females)) : ?>
                                    <div class="alert alert-warning">0 women were found with the given information.</div>
                                <?php else : ?>
                                    <table class="table table-hover table-condensed">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach($females as $id => $user) : ?>
                                            <tr>
                                                <td>
                                                    <?php echo $user['display_name'] ?>
                                                </td>
                                                <td style="text-align:right">
                                                    <form class="form-inline" style="margin:0" method="post" action="<?php echo url_for("compliance_programs/teamInviteUser?id={$record_id}") ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $id ?>" />
                                                        <input class="btn btn-primary" type="submit" value="Invite" />
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach ?>
                                        </tbody>
                                    </table>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                <?php endif ?>
            </div>
        <?php endif ?>

        <div class="tab-pane <?php echo $alert_maximum_members ? 'active' : '' ?>" id="members">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th></th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 0; ?>
                <?php foreach($team['users'] as $id => $user) : ?>
                    <tr>
                        <td>
                            <?php if($team['owner_user_id'] == $id) : ?>
                                <span class="label label-success">Captain</span>
                            <?php else : ?>

                            <?php endif ?>

                            <?php if($user['accepted']) : ?>
                                <span class="label label-info desktop">Team Member</span>
                                <span class="label label-info mobile" style="">Member</span>
                            <?php else : ?>
                                <span class="label label-warning desktop">Invitation Sent</span>
                                <span class="label label-warning mobile">Invited</span>
                            <?php endif ?>
                        </td>
                        <td><?php echo $user['first_name'] ?></td>
                        <td><?php echo $user['last_name'] ?></td>
                        <td>
                            <?php if(count($team['users']) == 1) : ?>
                                <form class="form-inline" style="margin:0" method="post" action="<?php echo url_for("compliance_programs/teamDelete?id={$record_id}") ?>" onsubmit="return confirm('Are you sure you want to delete this team?');">
                                    <input type="submit" class="btn btn-danger" value="Delete Team" />
                                </form>
                            <?php endif ?>

                            <?php if($team['owner_user_id'] != $id) : ?>
                                <form class="form-inline" style="margin:0" method="post" action="<?php echo url_for("compliance_programs/teamRemoveUser?id={$record_id}") ?>" onsubmit="return confirm('Are you sure you want to remove this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $id ?>" />
                                    <input type="submit" class="btn btn-danger" value="Remove" />
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane" id="team">
            <div class="well well-small">
                <div id="manage-team-instructions">
                    <p>To change your team name, enter the new team name below and select <em>Save</em>.
                </div>

                <form class="form-search input-append" method="post" action="<?php echo url_for("compliance_programs/teamEditName") ?>">
                    <input type="hidden" name="id" value="<?php echo $record_id ?>" />
                    <input type="text" name="new_team_name" value="<?php echo $team['name'] ?>" class="search-query" placeholder="Enter new team name" style="border-radius:0" />
                    <input type="submit"  class="btn btn-warning" value="Save" id="save_team_name" />
                </form>
            </div>

        </div>
    </div>
</div>
