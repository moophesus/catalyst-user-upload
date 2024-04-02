<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../src/user_upload.php';

class UserUploadCommandTest extends TestCase {

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($input, $expectedNumErrors, $expectedErrors) {
        $command = new UserUploadCommand();
        $command->dryRun = $input[0];
        $command->file = $input[1];
        $command->createTable = $input[2];
        $command->dbSettings->user = $input[3];
        $command->dbSettings->password = $input[4];
        $command->dbSettings->host = $input[5];
        
        $errors = $command->validate();
        $this->assertSame(count($errors), $expectedNumErrors);
        foreach ($expectedErrors as $expectedError) {
            $this->assertContains($expectedError, $errors);
        }
    }

    public static function validateProvider() {
        $data = yaml_parse_file(__DIR__."/testdata/validation.yaml");
        return array_map(fn($value) => [$value["input"], $value["numErrors"], $value["errors"]], $data);
    }

    /**
     * @dataProvider userProvider
     */
    public function testUser($input, $expectedOutput) {
        $user = new User($input[0], $input[1], $input[2]);
        $this->assertSame($user->name(), $expectedOutput[0]);
        $this->assertSame($user->surname(), $expectedOutput[1]);
        $this->assertSame($user->email(), $expectedOutput[2]);
    }

    public static function userProvider() {
        $data = yaml_parse_file(__DIR__."/testdata/user.yaml");
        return array_map(fn($value) => [$value["input"], $value["output"]], $data);
    }

    /**
     * @dataProvider validateUserProvider
     */
    public function testValidateUser($input, $expectedOutput) {
        $service = new UserUploadService(new DatabaseSettings());
        $user = new User($input[0], $input[1], $input[2]);
        $this->assertSame($service->validateUser($user), $expectedOutput);
    }

    public static function validateUserProvider() {
        $data = yaml_parse_file(__DIR__."/testdata/validateUser.yaml");
        return array_map(fn($value) => [$value["input"], $value["output"]], $data);
    }

}