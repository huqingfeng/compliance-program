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
        <li id="tab-join-team">
            <a href="<?php echo url_for("compliance_programs/joinTeam?id={$record_id}") ?>">Join an Existing Team</a>
        </li>
        <li id="tab-choose-buddy" class="active">
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

<div class="well well-small">
    <div id="invite-buddy-instructions">
        <p>To invite a person to be your buddy, enter their last name below and select <em>Search</em>.
            Then, select <em>Invite</em> next to the appropriate person.</p>
    </div>

    <form class="form-search input-append" method="get" action="<?php echo url_for("compliance_programs/chooseBuddy?id={$record_id}") ?>">
        <input type="hidden" name="id" value="<?php echo $record_id ?>" />
        <input type="text" name="last_name" value="<?php echo $last_name ?>" class="search-query" placeholder="Search by last name" style="border-radius:0" />
        <button type="submit" class="btn" id="search-submit">
            <i class="icon-search"></i> Search
        </button>
    </form>
</div>

<?php if(isset($users)) : ?>
    <div class="alert"><?php echo sprintf('%s users were found with the given information.', count($users)) ?></div>

    <?php if(count($users)) : ?>
        <table class="table table-hover table-condensed">
            <thead>
            <tr>
                <th>Name</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($users as $id => $user) : ?>
                <tr>
                    <td>
                        <?php echo $user['display_name'] ?>
                    </td>
                    <td style="text-align:right">
                        <form class="form-inline" style="margin:0" method="post" action="<?php echo url_for("compliance_programs/inviteBuddy?id={$record_id}") ?>">
                            <input type="hidden" name="user_id" value="<?php echo $id ?>" />
                            <input class="btn btn-primary" type="submit" value="Invite" />
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
<?php endif ?>