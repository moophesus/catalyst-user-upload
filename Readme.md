# Catalyst challenge tests

The following scripts are tested in the docker environment running the following configuration:
1. Ubuntu 22.04
2. PHP 8.1
3. MySQL 5.7
4. php8.1-mysql library for database connection

No additional library is needed to test the result scripts. However, there are more libraries installed in order to get the **phpunit** library for unit testing purpose. The unit tests are located in `tests` folder. If running in the docker environment, you can test it within the `tests` folder and run `../vendor/bin/phpunit`

The resultant scripts are located in `src` folder.

The `env` folder is the docker script configurations that was used to set up this challenge.

The Git process involves in branching and merging back to `origin/main`, with each branch for each challenge. On completion for challenge and have tested the code, it was merged back to the main branch for release.

## Challenge 1 - User upload task via CSV format

##### Assumptions:
* It will ignore any other flags if not specified in the command.
* CSV files contains header that specifies the field name. Can be turned off by header flag in code.
* Assuming the host in the format of 127.0.0.1 or localhost, without specifying port and database schema, since I used mysqli instead of PDO libraries.
* default MySQL port 3306 and database `test`, the database is default to this value and can be change in the code.
* Assumping the order of precedence as Help command > Create Table command > Dry Run command > Normal executions
* Continue to process user even if an error occurred, but will be given as warning messages back to the user.
* Will show the number of successful inserts into the database, and output any unsuccessful users along with their cause of error.
* Create table for inserting users if table users does not exist even though --create_table is not presented.
* Used mysqli library for database access.

Result code is in `src/user_upload.php`

## Challenge 2 - 1 to 100 FooBar sequence

##### Assumptions:
* Output additional end line after 100 for readability.

Result code is in `src/foobar.php`

