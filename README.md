# Database Stalker ORM and Migration Tool
DBStalker is an ORM and migration/seeding tool that gives you full control of your database just by providing your tables schema.

## Features
- Supports database migration.
- Support main, forced, temporary seeding with deseeding capabilities.
- Use single class for both creating ORM model and migration schema.
- Create, migrate and query views.
- Create and restore backups.

## Requirements
Check requirements in `requirements.txt`

## How to Use

## Configuration
First of all, you need to create `stalker_config.json` file. It consists of mandatory data and optional data that can alter the default behaviour. The mandatory data is about your database connection:
```JSON
"database": {
    "host": "",
    "database": "",
    "user": "",
    "password": ""
}
```
- `host`: The host name, usually 'localhost'.
- `database`: The created database name.
- `user`: The database user.
- `password`: The user password.

The optional data consists of 3 sections. First section sets the default tables Engine and Collation:
```JSON
"settings": {
    "engine": "InnoDB",
    "collation": "utf8_general_ci"
}
```

DBStalker support custom column types with custom validators. Each custom column is stored into the database as `varchar` with custom length. The second section sets the value of default custom column types' lengths:
```JSON
"customLengths": {
    "id": 11,
    "email": 255,
    "password": 64,
    "phone": 20,
    "ip": 45,
    "link": 511
}
```

DBStalker can create, restore and managa backups programatically. The third section controls how many backups should DBStalker keep at any given moment:
```JSON
"backup": {
    "perDay": -1,
    "max": 10
}
```
- `perDay`: number of backups to keep for the same day (default: -1)
- `max`: number of backups to keep overall (default: 10)
where any value < 1 is considered unlimited.

### Custom Configuration File
If you need to have a custom different path or name for your configuration file, you can set the configuration file path manually:
```php
Stalker_Configuration::set_stalker_configuration("path/to/your/custom_configuration_file.json");
```
Or you can pass the configuration object directly:
```php
Stalker_Configuration::set_stalker_configuration($configuration);
```

## Add core files
Next, You need to include all the `core` files and all your tables, seeds and views files to the project. You should user the following order to avoid errors:
```PHP
include_once './core/stalker_configuration.core.php';
include_once './core/stalker_registerar.core.php';
include_once './core/stalker_schema.core.php';
include_once './core/stalker_validator.core.php';
include_once './core/stalker_database.core.php';
include_once './core/stalker_information_schema.core.php';
include_once './core/stalker_query.core.php';
include_once './core/stalker_migrator.core.php';
include_once './core/stalker_backup.core.php';
include_once './core/stalker_table.core.php';
include_once './core/stalker_seed.core.php';
include_once './core/stalker_seeder.core.php';
include_once './core/stalker_view.core.php';

foreach ( glob("./tables/*.table.php") as $file ) {
    require_once $file;
}

foreach ( glob("./views/*.view.php") as $file ) {
    require_once $file;
}

foreach ( glob("./seeds/*.seed.php") as $file ) {
	require_once $file;
}
```

Next, DBStalker needs to be aware of the tables, seeds and views defined to perform migrations and seeding correctly. This is done using only 1 line :
```PHP
Stalker_Registerar::auto_register();
```

## Create tables
To create a table, add a file with the naming convention `table_name.table.php` to the `tables` folder with a single class extending `Stalker_Table`:
```PHP
class Branches_Info extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build( function ($table) {
            $table->varchar("name", 255);
            $table->int("max_kids", 3)->def(100);
            $table->enum("type", array('main', 'sub'))->def('main');
            $table->boolean("uniform")->def(false);
        });
    }
}
```
The table name is the same as the class name all in lower cases. `Branches_Info` class creates a `branches_info` table. The only required function in a table class in `public function schema()` that describes the table schema. Note that there is no specific naming convention for the table names as it won't affect how DBStalker works in anyway.

### Table Schema
Following is the column types available in DBStalker:
#### Integers Types
Parameters:
* `$name`: The column name
* `$length`: The type length

Types:
- `int($name, $length)`
- `tinyint($name, $length)`
- `smallint($name, $length)`
- `mediumint($name, $length)`
- `bigint($name, $length)`

#### Floating Points Types
Parameters:
* `$name`: The column name
* `$digits`: Number of digits in total
* `$points`: Number of digits after the decimal point

Types:
- `float($name, $digits=null, $points=null)`
- `double($name, $digits=null, $points=null)`
- `decimal($name, $digits, $points)`

#### Date and Time Types
Parameters:
* `$name`: The column name

Types:
- `date($name)`
- `time($name)`
- `datetime($name)`

#### Other Native Types
Parameters:
* `$name`: The column name
* `$length`: The type length
* `$vals`: The values the an ENUM column can have

Types:
- `varchar($name, $length)`
- `text($name)`
- `json($name)`
- `boolean($name)`
- `enum($name, array $vals)`

#### Custom Types
Custom types are stored as `varchar` (except the `id` type which is an `unsigned int` by default) and assigned custom validators to ensure that the date stored is as desired. default custom types lengths can be configured from `stalker_config.json` file.

Parameters:
* `$name`: The column name

Types:
- `id($name)`
- `email($name)`
- `password($name)`
- `phone($name)`
- `ip($name)`
- `link($name)`

### Additional Column Attributes
Column can have additional attributes.

- `nullable()`: column accept `null` value.
- `unsigned()`: only for numirecal column types.
- `unsigned_zerofill()`: only for numirecal column types.
- `zero_allowed()`: only for `id` column type.
- `primary()`: mark a column a primary key for the table and turns Auto_Increment On.
- `index()`: mark a column as an index.
- `unique()`: mark a column as unique.
- `def($val)`: adds a default value for the column where `$val` is the default value. If the default value is `null` then the column in `nullable()` by default.

Tables have an `id` column by default which is defined as:
```PHP
$table->id("id")->primary();
```
also for tables with seeds, a `main_seed` column is added automatically to the table which is defined as:
```PHP
$table->boolean("main_seed")->nullable()->def(NULL);
```

## Tables Relationships
Table relationships is defined inside each table's class. Assume you have 2 tables `branches` and `courses` defined as below:
```PHP
class Branches extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build( function ($table) {
            $table->varchar("name", 255);
        });
    }
}
```
```PHP
class Courses extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build(function($table){
            $table->varchar("name", 255);
            $table->id("branch_id")->index();
        });
    }
}
```

### One to One relationship
In a 'One to One' relationship, every branch has 1 course that can be defined in the `Branches` class as:
```PHP
public function course() {
    return $this->has_one("Courses", "branch_id");
}
```
which would return an instance of the target table where the first argument is the related table class name and the second argument is the column name pointing to the `id` of the original table.

Also each course belongs to 1 branch that can be defined in the `Courses` class as:
```PHP
public function branch() {
    return $this->belongs_to("Branches", "branch_id");
}
```
which would return an instance of the target table where the first argument is the related table class name and the second argument is the column name pointing to the `id` of that table.

### One to Many relationship
In a 'One to Many' relationship, every branch has many courses that can be defined in the `Branches` class as:
```PHP
public function courses() {
    return $this->has_many("Courses", "branch_id");
}
```
which would return an array of instances of the target table where the first argument is the related table class name and the second argument is the column name pointing to the `id` of the original table.

Also each course belongs to 1 branch that can be defined in the `Courses` class as:
```PHP
public function branch() {
    return $this->belongs_to("Branches", "branch_id");
}
```
which would return an instance of the target table where the first argument is the related table class name and the second argument is the column name pointing to the `id` of that table.


### Many to Many relationship
'Many to Many' relationships can be defined in one of the following ways depending on your database structure:
- Without an intermediate table
- With an intermediate table

#### Many to Many relationship Without an intermediate table
Every branch has many courses that can be defined in the `Branches` class as:
```PHP
public function courses() {
    return $this->has_many("Courses", "branch_id");
}
```
which would return an array of instances of the target table where the first argument is the related table class name and the second argument is the column name pointing to the `id` of the original table.

Also each course belongs to many branches that can be defined in the `Courses` class as:
```PHP
public function branches() {
    return $this->belongs_to_many("Branches", "branch_id");
}
```
which would return an array of instances of the target table where the first argument is the related table class name and the second argument is the column name pointing to the `id` of that table.


#### Many to Many relationship With an intermediate table
Assume a third intermediate table defined as below:
```PHP
class Branches_Courses extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build(function($table){
            $table->id("branch_id")->index();
            $table->id("course_id")->index();
        });
    }
}
```

Every branch has many courses that can be defined in the `Branches` class as:
```PHP
public function courses() {
    return $this->has_many_through("Courses", "Branches_Courses", "course_id", "branch_id");
}
```
which would return an array of instances of the target table where:
- The first argument: is the target table class name
- The second argument: is the intermediate table class name
- The third argument: is the column name pointing to the `id` of the target table in the intermediate table.
- The forth argument: is the column name pointing to the `id` of the original table in the intermediate table.

Also each course belongs to many branches that can be defined in the `Courses` class as:
```PHP
public function branch() {
    return $this->has_many_through("Branches", "Branches_Courses", "branch_id", "course_id");
}
```
Note that there is no naming convention for the table names that would affect the behaviour of the table or its relations with other tables.

There is also a `has_one_through` function that takes the same arguments as the `has_many_through` function that can be used in 'One to One' and 'One to Many' relations with an intermediate table but we recommend not using it and adjusting your database structure to not use an intermediate table for these relations.

## Table Queries
Assume you have a `branches` table defined as below:
```PHP
class Branches extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build( function ($table) {
            $table->varchar("name", 255);
            $table->int("max_kids", 3)->def(100);
            $table->int("min_kids", 3)->def(100);
            $table->enum("type", array('main', 'sub'))->def('main');
            $table->boolean("uniform")->def(false);
        });
    }
}
```

You can query a table to get desired resultls using the static `query` function and the `fetch` function, which would return an array of table instances
```PHP
$branches = Branches::query()->fetch();
```
this is gonna fetch all the `branches` table rows and return an array of `Branches` instances. you can omit the `query` function and write the above query as following and get the same results:
```PHP
$branches = Branches::fetch();
```
We will use the second form in the examples. To fetch the first record only:
```PHP
$branch = Branches::first();
```
to fetch the first 3 records:
```PHP
$branches = Branches::first(3);
```
To fetch the last record only:
```PHP
$branch = Branches::last();
```
to fetch the last 2 records:
```PHP
$branches = Branches::last(2);
```

To `ORDER` the records:
```PHP
$branches = Branches::order('name')->fetch();
```
To order by multiple columns:
```PHP
$branches = Branches::order('name', 'id')->fetch();
```
records are ordered ascendingly by default in the given order. To specify the order direction:
```PHP
$branches = Branches::order(['name', 'asc'], ['id', 'desc'])->fetch();
```
where the first array element is the column name and the second argument is the direction as `ASC` or `DESC`. you can write the above query as following and get the same results:
```PHP
$branches = Branches::order('name', ['id', 'desc'])->fetch();
```

To `LIMIT` the records:
```PHP
$branches = Branches::limit(5)->fetch();
```
that's the same as:
```PHP
$branches = Branches::first(5);
```
For pagination, you can specify the offset and row count as:
```PHP
$branches = Branches::limit(10, 5)->fetch();
```
the previous query will fetch the 5 records follwing the first 10 records of the query.

To add a `WHERE` clause:
```PHP
$branch = Branches::where('id', 5)->first();
```
This would get the record with `id = 5`, to get records where `id != 5`:
```PHP
$branches = Branches::where('id', 5, '<>')->fetch();
```
following is a list of all supported operands:
- `=` (the default operand)
- `<>`
- `>`
- `>=`
- `<`
- `<=`
- `is`
- `is not`
- `like`
- `not like`

To match 2 columns of the same table, a `true` flag is passed as the forth argument:
```PHP
$branches = Branches::where('min_kids', 'min_kids', '=', true)->fetch();
```
To fetch records where `min_kids < 5 AND max_kids > 10`:
```PHP
$branches = Branches::where('min_kids', 5, '<')->and_q('max_kids', 10, '>')->fetch();
```
To fetch records where `min_kids < 5 OR max_kids > 10`:
```PHP
$branches = Branches::where('min_kids', 5, '<')->or_q('max_kids', 10, '>')->fetch();
```
To fetch records where `min_kids < 5  AND min_kids > 2 OR max_kids = min_kids`:
```PHP
$branches = Branches::where('min_kids', 5, '<')
    ->and_q('min_kids', 2, '>')
    ->or_q('max_kids', 'min_kids', '=', true)
    ->fetch();
```
and so on.

One of the most used queries is fetching a record by it's id:
```PHP
$branch = Branches::where('id', 5)->first();
```
The above query has a special function to get a record by id:
```PHP
$branch = Branches::get(5);
```

By default, queries selects all columns.
```PHP
$branches = Branches::select('*')->fetch();
```
To select specific columns:
```PHP
$branches = Branches::select('id', 'name', 'uniform')->fetch();
```
The above query only fetches the `id`, `name` and `uniform` columns for each record. To use aggregate functions:
```PHP
$branch = Branches::select(['id', 'count'])->first();
```
The above query will select `COUNT(id)` and alias it as `id_count` in the following format `{$column_name}_{aggregate_function_name}`.

To calculate based on `DISTINCT` values, a `true` flag is passed as the third element in the array:
```PHP
$branch = Branches::select(['max_kids', 'sum', true])->first();
```
that will select `SUM(DISTINCT max_kids) AS max_kids_sum`. you can select multiple of columns:
```PHP
$branch = Branches::select('id', ['id', 'count'], ['max_kids', 'sum', true], 'uniform')->first();
```
following is a list of all supported aggregate functions:
- `AVG`
- `COUNT`
- `GROUP_CONCAT`
- `MAX`
- `MIN`
- `SUM`

To use `GROUP BY`:
```PHP
$branches = Branches::group('type')->fetch();
```
To `GROUP BY` multiple columns:
```PHP
$branches = Branches::group('type', 'max_kids')->fetch();
```
You can use aggregate functions and `GROUP BY` together:
```PHP
$branches = Branches::select(['id', 'count'])->group('type')->fetch();
```
any `HAVING` clause should always be after `GROUP BY` you can use `having` clause exatly like `where`:

```PHP
$branches = Branches::select('*', ['id', 'count'], ['max_kids', 'sum', true])
    ->group('type')
    ->having('id_count', 5, '>=')
        ->and_q('max_kids_sum', 20, '<')
    ->order('id_count')
    ->fetch();
```

## Use Table record
Assume you have 2 tables `branches` and `courses` defined as below:
```PHP
class Branches extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build( function ($table) {
            $table->varchar("name", 255);
            $table->int("max_kids", 3)->def(100);
            $table->int("min_kids", 3)->def(100);
            $table->enum("type", array('main', 'sub'))->def('main');
            $table->boolean("uniform")->def(false);
        });
    }
    public function courses() {
        return $this->has_many("Courses", "branch_id");
    }
}
```
```PHP
class Courses extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build(function($table){
            $table->varchar("name", 255);
            $table->id("branch_id")->index();
        });
    }

    public function branch() {
        return $this->belongs_to("Branches", "branch_id");
    }
}
```
To get vranch with `id=1`:
```PHP
$branch = Branches::get(1);
```
You can access the record data as any public attribute:
```PHP
$branch->id;
$branch->name;
$branch->type;
```
You can get an courses related to the branch by using the relationship function name:
```PHP
$branch->courses;
```
which will return an array of courses instances. You can access a course's data like:
```PHP
$branch->courses[0]->name;
```
Vice verse, to get the branch a course belongs to:
```PHP
$course = Courses::get(1);
$course->branch->type;
```

## Create, Update and Delete a record
To create a new table record, just create a new instance of the table class:
```PHP
$branch = new Branches();
$branch->name = "HQ";
$branch->type = "main";
$branch->save();
$branch->id; // the inserted record id
```

To Update an existing record, get the record you need to update and alter the data you want:
```PHP
$branch = Branches::get(1);
$branch->max_kids = 15;
$branch->save();
```

`save()` function validates the values according to the table schema and returns `true` if the value was created or updated successfully or `false` otherwise. To get validation errors:
```PHP
$branch = new Branches();
$branch->max_kids = "seventeen";
$errors = $branch->validate(); // ['name' => ['Field can't be empty'], 'max_kids' => ['Invalid value. Value must be a number']]
```
`validate()` returns `null` if there was no validation errors, and returns an array of errors otherwise.

To delete a record from database:
```PHP
$branch = Branches::get(1);
$branch->delete();
```
`delete()` function returns `true` if the value was deleted successfully or `false` otherwise.

## Table Seeds
To seed a table, add a file with the naming convention `table_name.seed.php` to the `seeds` folder with a single class extending `Stalker_Seed`:
```PHP
class Branches_Seed extends Stalker_Seed
{
    public function main_seed() {
        return array();
    }

    public function temporary_seed() {
        return array();
    }
}
```
The seed class name should be the same table class name + "_Seed" in format `Table_Class_Name_Seed`. For `Branches` table, a seed class named `Branches_Seed` should be created. Creating a seed class for each table is optional, You can only create seed classes for tables with seeds. Adding a seed class for a table adds a boolean column called `main_seed` in the table schema created to indicated seeded records and their type.

There are 3 types of seeds: main seeds, forced main seeds and temporary seeds.

### Main Seeds
Main seeds represents records that always have to exist in the table. It MUST have an `id` specified, all required fields (fields with no default values) must be specified as well. Columns with default values can be omited and it would have the default value assigned:
```PHP
class Branches_Seed extends Stalker_Seed
{
    public function main_seed() {
        return array(
            array(
                "id" => 1,
                "name" => "HQ",
                "min_kids" => 5
            ),
            array(
                "id" => 2,
                "name" => "Sub Branch",
                "max_kids" => 10,
                "type" => "sub"
            )
        );
    }
}
```
Main seeds enforces the existance of a record with the specified `id` but won't modify the other columns to match seeded values if changed later.

To insert main seeds to database:
```PHP
Stalker_Seeder::seed_main_seeds();
```
The above line will only insert unexisting main seed records to database without duplicating existing main seed records or modifying their values.

To insert main seeds for a specific table only:
```PHP
Stalker_Seeder::seed_table_main_seeds('table_name');
```

If for any reason you want to delete all main seeds from database:
```PHP
Stalker_Seeder::delete_main_seeds();
```

To delete main seeds for a specific table only:
```PHP
Stalker_Seeder::delete_table_main_seeds('table_name');
```

## Forced Main Seeds
Forced main seeds are like normal main seeds except that they don't only enforces that a record with the specified `id` exists, but also enforces other columns to have values the same as seeded values even if it changed later. To create a forced main seed, add `"__forced" => true` to the seed array:
```PHP
class Branches_Seed extends Stalker_Seed
{
    public function main_seed() {
        return array(
            array( // this seed is a forced main seed
                "__forced" => true,
                "id" => 1,
                "name" => "HQ",
                "min_kids" => 5
            ),
            array( // this seed is a normal main seed
                "id" => 2,
                "name" => "Sub Branch",
                "max_kids" => 10,
                "type" => "sub"
            )
        );
    }
}
```
Forced main seeds are insterted to and deleted from database the same way as normal main seeds:
```PHP
Stalker_Seeder::seed_main_seeds(); // insert main seeds to all tables in database
Stalker_Seeder::seed_table_main_seeds('table_name'); // insert main seeds to 'table_name' table only

Stalker_Seeder::delete_main_seeds(); // delete main seeds from all tables in database
Stalker_Seeder::delete_table_main_seeds('table_name'); // delete main seeds from 'table_name' table only
```

If for any reason you want to FORCE SEEd all main seeds even normal ones in a table once:
```PHP
Stalker_Seeder::seed_table_main_seeds('table_name', TRUE); // insert all main seeds as Forced main seeds to 'table_name' table only
```

### Temporary Seeds
Temporary seeds are seeds that doesn't require an `id` as it has no special meaning. It can be duplicated and noramlly used to propagate dummy data for testing that wil be deleted later. To create temporary seeds:
```PHP
class Branches_Seed extends Stalker_Seed
{
    public function temporary_seed() {
        return array(
            array(
                "name" => "test branch 1",
                "min_kids" => 5
            ),
            array(
                "name" => "test branch 2",
                "min_kids" => 6,
                "type" => "sub"
            )
        );
    }
}
```

To insert temporary seeds to database:
```PHP
Stalker_Seeder::seed_temporary_seeds();
```
The above line will always insert seed records to database even if that would duplicate records (`unique` and `primary` columns will throw an error if duplicated).

To insert temporary seeds for a specific table only:
```PHP
Stalker_Seeder::seed_table_temporary_seeds('table_name');
```

To delete all temporary seeds from database:
```PHP
Stalker_Seeder::delete_temporary_seeds();
```

To delete temporary seeds for a specific table only:
```PHP
Stalker_Seeder::delete_table_temporary_seeds('table_name');
```

## Create Views
DBStalker lets you create views using an SQL query. To create a view, add a file with the naming convention `table_name.view.php` to the `views` folder with a single class extending `Stalker_View`:
```PHP
class Branch_Courses extends Stalker_View
{
    public function view_query() {
        return "SELECT `courses`.`id`,
                    `branches`.`id` AS `branch_id`,
                    `courses`.`name` AS `course_name`,
                    `branches`.`name` AS `branch_name`,
                FROM `courses_class`
                LEFT JOIN `branches`
                    ON `branches`.`id` = `courses_class`.`branch_id`
                ORDER BY `branches`.`type`";
    }
}
```
Views are the ideal solution to create complex SQL queries that is used alot. It can be used identically as any table. You can't create, update or delete a view record.
```PHP
$branch_courses = Branch_Courses::fetch(); // get all view records
echo $branch_courses->course_name; // get property
$branch_courses = Branch_Courses::where('branch_id', 2)->order('id')->fetch(); // use any query function freely
```
It's preferable to have a unique `id` column in a view as it's mandatory for using relationships.

## Base Database Instance
All DBStalker features are built on a singleton instance connecting to database using PDO. To get the database instance:
```PHP
$db_instance = Stalker_Database::instance();
```
If you need to have multiple instances of the database connection (e.g. In case of multible database connections), you can force creating a new instance:
```PHP
$forced_new_db_instance = Stalker_Database::instance(true);
```


### Excute Raw SQL
The database instance handles PDO Preparations and data bindings and catches any `PDOException`. To execute a raw SQL query:
```PHP
$db_instance->execute("SELECT * FROM `branches` WHERE `id`=? LIMIT 1;", array(1));
```
Where the first argument is the query and the second argument is an array of the binded data.

### Transactions
DBStalker supports transactions and multipe save points using the following API:
```PHP
$db_instance->beginTransaction(); // begins transaction
$db_instance->commit(); // commits transaction
$db_instance->rollBack(); // rollBack transaction
```

You can use nested Transactions:
```PHP
$db_instance->beginTransaction(); // begins transaction
    $course->save();

    $db_instance->beginTransaction(); // begins a second nested transaction
        $branch->save();
    $db_instance->rollBack(); // rollBack the second transaction

$db_instance->commit(); // commits the first transaction transaction
// only the course record is saved.


$db_instance->beginTransaction(); // begins transaction
    $course->save();

    $db_instance->beginTransaction(); // begins a second nested transaction
        $branch->save();
    $db_instance->commit(); // all changes in the second transction is recorded but not committed yet

$db_instance->rollback(); // rollback all transactions
// no records are saved at all.
```

## Migration
To check if the database needs migration:
```PHP
Stalker_Migrator::need_migration();
```
The function returns `true` if database needs migration, `false` otherwise.

To migrate  changes:
```PHP
Stalker_Migrator::migrate();
```
Note: renaming columns or tables might result DROPPING the column or table entirely. so be careful!

## Backups
Number of backups managed by DBStalker is specified in `stalker_config.json` file. To create a backup:
```PHP
Stalker_Backup::create_backup();
```
This function creates a `.sql` file containing a full backup of the tables and views schema and data. The `sql` file name is created as `stalker-backup~{$database_name}~{date("Y-m-d")}~{date("His")}.sql`. the name consists of 3 main parts separated by `~` character:
- `$database_name` is the database name
- `date("Y-m-d")` is the date is the backup
- `date("His")` is a unique backup series

To restore a backup:
```PHP
// restore the latest backup available
Stalker_Backup::restore_backup()
// restore the latest backup in a specific date
Stalker_Backup::restore_backup("2019-12-01")
// restore a specific backup
Stalker_Backup::restore_backup("2018-09-29", "145457")
```
Note: Database names must match for the backup to be restored.

Backups usually created using a cron job. Check `backup.cron.php` for an optimal code to create a backup using DBStalker in a cron job.

## Future Features
Bellow is a list of features or tasks to do in the future:
- Cache table schema for performance.
- Cache table relations instances for performance.
- Change access modifiers for some internal class methods.
- Add custom error messages for table validations
- Create docs on how make custom column types
- Find a way to handle table and column renaming in migrations instead of droping and creating
- Double check for SQL Injections (especially in seeds).
## License
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
