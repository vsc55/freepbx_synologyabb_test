<div class="panel panel-primary" style="display: none;">
    <div class="panel-heading">
        <h3 class="panel-title">
          <img src='modules/synologyactivebackupforbusiness/assets/images/entry.cgi-mini.png'>
          <b><?php echo _("Status Backup"); ?><b></h3>
    </div>
    <div class="panel-body">
    
        <div class="element-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="ABB_Info_Serv">Server address:</label>
                            </div>
                            <div class="col-md-9">
                                <?php echo $info['server']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="ABB_Info_Username">Username:</label>
                            </div>
                            <div class="col-md-9">
                                <?php echo $info['user']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="ABB_Info_lastbackup">Last backup time:</label>
                            </div>
                            <div class="col-md-9">
                                <?php echo $info['lastbackup']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="ABB_Info_nextbackup">Next backup time:</label>
                            </div>
                            <div class="col-md-9">
                                <?php echo $info['nextbackup']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="ABB_Info_server_status">Service Status:</label>
                            </div>
                            <div class="col-md-9">
                                <?php echo $info['server_status']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="ABB_Info_portal">Restore portal:</label>
                            </div>
                            <div class="col-md-9">
                                <a href="<?php echo $info['portal']?>"  target="_blank">Click here</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
    <div class="panel-footer" style="display: none;"></div>
</div>