<?php
class DatabaseSettings {
    /*  Default values for database settings, can be overridden depending on the behavior of the specifications
    For now if the values are not set, we will throw exceptions stating that the values are not set */
    public $user = null;
    public $password = null;
    public $host = null;
}

abstract class Command {
    public abstract function execute();
}

class UserUploadCommand extends Command {
    public $dryRun = false;
    public $file;
    public $createTable = false;
    public $dbSettings;

    public function __construct() {
        $this->dbSettings = new DatabaseSettings();
    }

    public function execute() {
        echo "Executing UserUploadCommand\n";
    }
    
}

class UserUploadHelpCommand extends Command {
    private static $helpText = <<<TEXT
    Usage: user_upload.php [options]
        --file [csv file name] - this is the name of the CSV to be parsed
        --create_table - this will cause the MySQL users table to be built (and no further action will be taken)
        --dry_run - this will be used with the --file directive in case we want to run the script but not insert
        into the DB. All other functions will be executed, but the database won't be altered
        -u - MySQL username
        -p - MySQL password
        -h - MySQL host

    TEXT;

    public function execute() {
        echo UserUploadHelpCommand::$helpText;
    }
}

class UserUploadCommandBuilder {
    private $command = null;

    private static $DUMMY;

    public function __construct() {
        self::$DUMMY = new UserUploadCommand();
    }

    public function help() {
        $this->command = new UserUploadHelpCommand();
        return $this;
    }

    public function file($file) {
        $this->getUserUploadCommand()->file = $file;
        return $this;
    }

    public function createTable() {
        $this->getUserUploadCommand()->createTable = true;
        return $this;
    }

    public function dryRun() {
        $this->getUserUploadCommand()->dryRun = true;
        return $this;
    }

    public function dbUser($user) {
        $this->getUserUploadCommand()->dbSettings->user = $user;
        return $this;
    }

    public function dbPassword($password) {
        $this->getUserUploadCommand()->dbSettings->password = $password;
        return $this;
    }

    public function dbHost($host) {
        $this->getUserUploadCommand()->dbSettings->host = $host;
        return $this;
    }

    public function build() {
        return $this->command;
    }

    private function getUserUploadCommand() {
        if ($this->command == null) 
            $this->command = new UserUploadCommand();
        
        // If the command is a help command, the help command has all precedence and returns a dummy command
        if ($this->command instanceof UserUploadHelpCommand)
            return self::$DUMMY;
        
        return $this->command;
    }
}
?>