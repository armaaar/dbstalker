<?php
include_once './core/stalker_configuration.core.php';
include_once './core/stalker_validator.core.php';
include_once './core/stalker_database.core.php';
include_once './core/stalker_information_schema.core.php';
include_once './core/stalker_backup.core.php';

Stalker_Backup::create_backup();
