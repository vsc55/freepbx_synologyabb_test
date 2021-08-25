<div class="panel panel-warning" style="display: none;">
    <div class="panel-heading">
        <h3 class="panel-title">
          <img src='modules/synologyactivebackupforbusiness/assets/images/entry.cgi-mini.png'>
          <b><?php echo _("Not Connection, pending configuration!"); ?><b></h3>
    </div>
    <div class="panel-body">
      <form>
        <div class="form-group">
          <label for="ABBServer">IP/Address Server</label>
          <input type="text" class="form-control" id="ABBServer" aria-describedby="serverHelp" placeholder="nas.example.com">
          <small id="serverHelp" class="form-text text-muted">Active Backup for Business server IP address.</small>
        </div>
        <div class="form-group">
          <label for="ABBUser">User</label>
          <input type="text" class="form-control" id="ABBUser" aria-describedby="userHelp" placeholder="Username">
          <small id="userHelp" class="form-text text-muted">User with which this equipment will be added to the server.</small>
        </div>
        <div class="form-group">
          <label for="ABBPassword">Password</label>
          <input type="password" class="form-control" id="ABBPassword" placeholder="Password">
        </div>
        
        <button type="submit" class="btn btn-success btn-lg btn-block">Conectar</button>
      </form>
    </div>
    <div class="panel-footer panel-version">
      <b><?php echo sprintf( _("Agent Version: %s"), $syno->getAgentVersion() ); ?></b>
    </div>
</div>