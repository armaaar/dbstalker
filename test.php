<?php
include_once './core/stalker_configuration.core.php';
include_once './core/stalker_registerar.core.php';
include_once './core/stalker_schema.core.php';
include_once './core/stalker_validator.core.php';
include_once './core/stalker_database.core.php';
include_once './core/stalker_table.core.php';
include_once './core/stalker_seed.core.php';
include_once './core/stalker_seeder.core.php';
include_once './core/stalker_view.core.php';
include_once './core/stalker_information_schema.core.php';
include_once './core/stalker_migrator.core.php';
include_once './core/stalker_backup.core.php';

foreach ( glob("./tables/*.table.php") as $file ) {
    require_once $file;
}

foreach ( glob("./views/*.view.php") as $file ) {
    require_once $file;
}

foreach ( glob("./seeds/*.seed.php") as $file ) {
	require_once $file;
}

ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

Stalker_Registerar::auto_register();
/*
var_dump(Stalker_Registerar::get_registerd_tables());
var_dump(Stalker_Configuration::database_connection());
var_dump(Stalker_Configuration::table_settings());
var_dump(Stalker_Configuration::custom_feilds_lengths());

$fa = Stalker_Migrator::need_migration_data();
var_dump($fa);
$fa = Stalker_Migrator::migrate();
var_dump($fa);
$fa = Stalker_Seeder::seed_main_seeds();
var_dump($fa);
$fa = Stalker_Seeder::seed_temporary_seeds();
var_dump($fa);

$fa = Stalker_Seeder::delete_temporary_seeds();
var_dump($fa);
$fa = Stalker_Seeder::delete_main_seeds();
var_dump($fa);

$fa = Stalker_Backup::restore_backup();
var_dump($fa);
*/
