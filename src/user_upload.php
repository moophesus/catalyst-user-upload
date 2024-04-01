<?php
class DatabaseSettings {
    /*  Default values for database settings, can be overridden depending on the behavior of the specifications
    For now if the values are not set, we will throw exceptions stating that the values are not set */
    public $user;
    public $password;
    public $host;
}

abstract class Command {
    public abstract function execute();
}

class ValidateException extends Exception {
    public function __construct($message) {
        parent::__construct($message);
    }
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

    private function hasDatabaseSettings() {
        return !empty($this->dbSettings->user) && !empty($this->dbSettings->password) && !empty($this->dbSettings->host);
    }

    private function databaseNotSetError() {
        $errors = [];
        if (empty($this->dbSettings->user))
            array_push($errors, "Database user not set, please set the database by specifying the -u directive");
        if (empty($this->dbSettings->password))
            array_push($errors, "Database password not set, please set the database by specifying the -p directive");
        if (empty($this->dbSettings->host))
            array_push($errors, "Database host not set, please set the database by specifying the -h directive");
        return $errors;
    }

    public function validate() {
        $errors = [];
        if (!$this->hasDatabaseSettings())
            $errors = array_merge($errors, $this->databaseNotSetError());
        if (!$this->createTable && empty($this->file))
            array_push($errors, "CSV file not set, please set the CSV file to be parsed by specifying the --file directive");
        return $errors;
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