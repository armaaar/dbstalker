<?php
include_once './core/stalker_configuration.class.php';
include_once './core/stalker_registerar.class.php';
include_once './core/stalker_schema.class.php';
include_once './core/stalker_validator.class.php';
include_once './core/stalker_singleton.class.php';
include_once './core/stalker_database.class.php';
include_once './core/stalker_table.class.php';
include_once './core/stalker_migrator.class.php';

foreach ( glob("./tables/*.table.php") as $file ) {
	require_once $file;
}

Stalker_Registerar::auto_register();
var_dump(Stalker_Registerar::get_registerd_tables());
var_dump(Stalker_Configuration::database_connection());
var_dump(Stalker_Configuration::table_settings());
var_dump(Stalker_Configuration::custom_feilds_lengths());

$fa = Stalker_Migrator::need_migration();
var_dump($fa);
$fa = Stalker_Migrator::migrate();
var_dump($fa);
