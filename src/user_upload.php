<?php
class DatabaseSettings {
    public $user = null;
    public $password = null;
    public $host = null;
}

abstract class Command {
    public abstract function execute();
}

class UserUploadCommand extends Command {
    public $dry_run = false;
    public $file;
    public $create_table = false;
    public $db_settings;

    public function __construct() {
        $this->dry_run = $dry_run;
        $this->file = $file;
        $this->create_table = $create_table;
        $this->db_settings = new DatabaseSettings();
    }

    public function execute() {
        echo "Executing UserUploadCommand\n";
    }
    
}

class UserUploadHelpCommand extends Command {
    public function execute() {
        echo "Usage: user_upload.php [options] [arguments]\n";
    }
}

class UserUploadCommandBuilder {
    private $command = null;

    public function help() {
        $this->command = new UserUploadHelpCommand();
        return $this;
    }

    public function build() {
        return $this->command;
    }
}
?>