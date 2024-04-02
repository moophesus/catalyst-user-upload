<?php
class DatabaseSettings {
    /*  Default values for database settings, can be overridden depending on the behavior of the specifications
    For now if the values are not set, we will throw exceptions stating that the values are not set */
    public $user;
    public $password;
    public $host;
    # Default database test
    public $database = 'test';
}

class Message {
    public $message;
 
    public function __construct($message) {
        $this->message = $message;
    }
}

class SuccessMessage extends Message {
    public function __construct($message) {
        parent::__construct($message);
    }
}

class WarningMessage extends Message {
    public function __construct($message) {
        parent::__construct($message);
    }
}

class FailMessage extends Message {
    public function __construct($message) {
        parent::__construct($message);
    }
}

function logErrorOrWarn($message) {
    if ($message instanceof FailMessage)
        echo "Error executing command:\n- ".$message->message."\n";
    elseif ($message instanceof WarningMessage)
        echo "Warning executing command:\n- ".$message->message."\n";
}

class UserUploadService {
    private $dbSettings;
    private $db;
    private static $CREATE_TABLE_SCRIPT = "CREATE TABLE users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(30) NOT NULL,
        surname VARCHAR(30) NOT NULL,
        email VARCHAR(50) NOT NULL UNIQUE
    )";

    private static $DROP_TABLE_SCRIPT = "DROP TABLE users";
    private static $TABLE_EXISTS_SCRIPT = "SELECT 1 FROM users LIMIT 1";
    private static $INSERT_USER_SCRIPT = "INSERT INTO users (name, surname, email) VALUES (?, ?, ?)";

    public function __construct($dbSettings) {
        $this->dbSettings = $dbSettings;
    }

    public function connect() {
        $this->db = mysqli_connect($this->dbSettings->host, $this->dbSettings->user, $this->dbSettings->password, $this->dbSettings->database);
        if (!$this->db) 
            throw new Exception("Could not connect to the database. Please check your database settings.");
    }

    public function tableExists() {
        try {
            $result = $this->db->query(UserUploadService::$TABLE_EXISTS_SCRIPT);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function createTable() {
        if ($this->tableExists()) {
            return new WarningMessage("Table users already exists, skipping creation");
        }
        if ($this->db->query(UserUploadService::$CREATE_TABLE_SCRIPT)) {
            return new SuccessMessage("Table users created successfully");
        } else {
            return new FailMessage("Error creating table: " . $this->db->error);
        }
    }

    public function clear() {
        if (!$this->tableExists()) 
            return new WarningMessage("Table users does not exist, skipping drop");
        if ($this->db->query(UserUploadService::$DROP_TABLE_SCRIPT)) 
            return new SuccessMessage("Table users dropped successfully");
        else 
            return new FailMessage("Error dropping table: " . $this->db->error);
    }

    public function insertUser($user) {
        $stmt = $this->db->prepare(UserUploadService::$INSERT_USER_SCRIPT);
        $name = $user->name();
        $surname = $user->surname();
        $email = $user->email();
        $stmt->bind_param("sss", $name, $surname, $email);

        try {
            $stmt->execute();
            return new SuccessMessage("User ($name, $surname, $email) inserted successfully");
        } catch (Exception $e) {
            if ($this->db->errno == 1062) {
                return new WarningMessage("User ($name, $surname, $email) email already exists - skipping user");
            } else {
                return new WarningMessage("Error inserting user ($name, $surname, $email): $this->db->error - skipping user");
            }
        }
    }

    public function beginTransaction() {
        $this->db->autocommit(false);
        $this->db->begin_transaction();
    }

    public function commit() {
        $this->db->commit();
    }

    public function rollback() {
        $this->db->rollback();
    }

    public function close() {
        if ($this->db)
            $this->db->close();
    }

    public function validateUser($user) {
        return !empty($user->name()) && !empty($user->surname()) && !empty($user->email()) && filter_var($user->email(), FILTER_VALIDATE_EMAIL);
    }
}

class User {
    private $name;
    private $surname;
    private $email;

    public function __construct($name, $surname, $email) {
        $this->setName($name);
        $this->setSurname($surname);
        $this->setEmail($email);
    }

    public function name() {
        return $this->name;
    }

    public function surname() {
        return $this->surname;
    }
    
    public function email() {
        return $this->email;
    }

    private function setName($name) {
        $this->name = ucwords(strtolower(trim($name)));
    }

    private function setSurname($surname) {
        $this->surname = ucwords(strtolower(trim($surname)));
    }

    private function setEmail($email) {
        $this->email = strtolower(trim($email));
    }
}

abstract class Command {
    public abstract function execute();
}

class UserUploadCommand extends Command {
    public $dryRun = false;
    public $file;
    public $createTable = false;
    public $dbSettings;
    # If the CSV file contains a header
    public $header = true;

    private $COLUMN_MAPPING = [
        "name" => 0,
        "surname" => 1,
        "email" => 2
    ];

    public function __construct() {
        $this->dbSettings = new DatabaseSettings();
    }

    public function execute() {
        $errors = $this->validate();
        $file = null;
        if (!empty($errors)) {
            logErrorOrWarn(new FailMessage(implode("\n- ", $errors)));
            return;
        }
        $service = new UserUploadService($this->dbSettings);
        try {
            $service->connect();
            if ($this->createTable) logErrorOrWarn($this->createTable());
            else {
                $file = @fopen($this->file, "r");
                if ($file === FALSE) 
                    throw new Exception("Could not open file: ".$this->file);
                $service->beginTransaction();
                if (!$service->tableExists()) $service->createTable();

                $line = 0;
                $warning = [];
                $numUserInserted = 0;
                while (($data = $this->parse($file)) !== FALSE) {
                    $line++;
                    if ($this->header && $line == 1) $this->parseHeader($data);
                    else {
                        $user = new User($data[$this->COLUMN_MAPPING['name']], $data[$this->COLUMN_MAPPING['surname']], $data[$this->COLUMN_MAPPING['email']]);
                        if ($service->validateUser($user)) {
                            $message = $service->insertUser($user);
                            if ($message instanceof SuccessMessage) $numUserInserted++;
                            else array_push($warning, sprintf("Line %d: %s", $line, $message->message));
                        } else {
                            array_push($warning, sprintf("Line %d: Validation failed for user (%s, %s, %s) - skipping user", $line, $user->name(), $user->surname(), $user->email()));
                        }
                    }
                }
                echo "$numUserInserted users inserted successfully\n";
                if (!empty($warning)) logErrorOrWarn(new WarningMessage(implode("\n- ", $warning)));
                $this->dryRun ? $service->rollback() : $service->commit();
            }
        } catch (Exception $e) {
            logErrorOrWarn(new FailMessage($e->getMessage()));
        } finally {
            if ($file != null) {
                try {
                    fclose($file);
                } catch (Exception $e) {
                }
            }
            $service->close();
        }
    }

    

    private function createTable() {
        $service = new UserUploadService($this->dbSettings);
        $service->connect();
        $message = $service->createTable();
        $service->close();
        return $message;
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

    private function parse($file) {
        return fgetcsv($file);
    }

    private function parseHeader($data) {
        foreach ($data as $idx => $column) {
            if (array_key_exists($column, $this->COLUMN_MAPPING))
                $this->COLUMN_MAPPING[$column] = $idx;
        }
    }

    public function clear() {
        $service = new UserUploadService($this->dbSettings);
        try {
            $service->connect();
            $message = $service->clear();
        } catch (Exception $e) {
        } finally {
            $service->close();
        }
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

class CommandParser {

    public function parse($args) {
        $builder = new UserUploadCommandBuilder();

        while ($args) {
            $arg = trim(array_shift($args));
            if ($arg == "--file") {
                $value = !empty($args) ? trim(array_shift($args)) : null;
                $builder->file($value);
            } elseif ($arg == "--create_table") {
                $builder->createTable();
            } elseif ($arg == "--dry_run") {
                $builder->dryRun();
            } elseif ($arg == "--help") {
                $builder->help();
            } elseif ($arg == "-u") {
                $value = !empty($args) ? trim(array_shift($args)) : null;
                $builder->dbUser($value);
            } elseif ($arg == "-p") {
                $value = !empty($args) ? trim(array_shift($args)) : null;
                $builder->dbPassword($value);
            } elseif ($arg == "-h") {
                $value = !empty($args) ? trim(array_shift($args)) : null;
                $builder->dbHost($value);
            }
        }
        return $builder->build();
    }
}

function main($args) {
    $need_clear = true;
    $command = (new CommandParser())->parse($args);
    if(is_null($command)) $command = new UserUploadHelpCommand();
    $command->execute();
    if ($command instanceof UserUploadCommand && $need_clear) $command->clear();
}

if (!(isset($_ENV['UNIT_TEST']) && $_ENV['UNIT_TEST'] == true)) {
    main($argv);
}
?>