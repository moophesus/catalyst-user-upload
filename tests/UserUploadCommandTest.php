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
        $data = yaml_parse_file(__DIR__.'/testdata/validation.yaml');
        return array_map(fn($value) => [$value['input'], $value['numErrors'], $value['errors']], $data);
    }
  
}