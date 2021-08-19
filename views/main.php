<?php
    $info_status = $syno->getAgentStatus();
    echo sprintf('<div class="alert alert-info" role="alert"><b>%s</b> %s</div>', _("Agent Version:"), $syno->getAgentVersion());
?>





<div class="panel panel-success">
    <div class="panel-heading">
        <h3 class="panel-title">
            <?php echo _("IP/Host Server: ") . '<span id="info_server">'. _("Loading...") . '</span>'; ?>
        </h3>
    </div>
    <div class="panel-body">
        <p><?php echo _("Username: ") . '<span id="info_user">'. _("Loading...") . '</span>'; ?></p>
        <br>

        <p><?php echo _("Last Backup: ") . '<span id="info_lastbackup">'. _("Loading...") . '</span>'; ?></p>
        <p><?php echo _("Next Backup: ") . '<span id="info_nextbackup">'. _("Loading...") . '</span>'; ?></p>
        <br>
        <p><?php echo _("Status: ") . '<span id="info_status">'. _("Loading...") . '</span>'; ?></p>

        
    </div>
    <div class="panel-footer">
        <a href="#" id="info_portal" target="_blank"><?php echo _("To access the recovery portal click here"); ?></a>
    </div>
</div>


<?php 
    // echo '<textarea  rows="10" cols="200">';
    // print_r($info_status); 
    // echo "</textarea>";
?>



<!-- <form>
    
    <div class="mb-3">
        <label for="optServer" class="form-label">Server</label>
        <input type="email" class="form-control" id="optServer" aria-describedby="serverHelp">
        <div id="serverHelp" class="form-text">We'll never share your email with anyone else.</div>
    </div>
    <div class="mb-3">
        <label for="optUserName" class="form-label">UserName</label>
        <input type="email" class="form-control" id="optUserName" aria-describedby="userHelp">
        <div id="userHelp" class="form-text">We'll never share your email with anyone else.</div>
    </div>
    <div class="mb-3">
        <label for="optPassword" class="form-label">Password</label>
        <input type="password" class="form-control" id="w">
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="exampleCheck1">
        <label class="form-check-label" for="exampleCheck1">Check me out</label>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
</form>
 -->
