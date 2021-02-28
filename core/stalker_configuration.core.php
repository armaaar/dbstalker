<?php

class Stalker_Configuration {
    private static $configuration;
    private static $fatal_error;

    const ENGINE = "InnoDB";
    const CHARSET = "utf8";
    const COLLATION = "utf8_general_ci";
    const ID_LENGTH = 11;
    const EMAIL_LENGTH = 255;
    const PASSWORD_LENGTH = 64;
    const PHONE_LENGTH = 20;
    const IP_LENGTH = 45;
    const LINK_LENGTH = 511;
    const BACKUP_PER_DAY = -1;
    const BACKUP_MAX = 10;

    private function __construct() {}

    public static function set_stalker_configuration($config = "./stalker_config.json") {
        if (is_string($config)) {
            self::$configuration = json_decode(file_get_contents("./stalker_config.json"));
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::$fatal_error = json_last_error();
                return FALSE;
            }
        } else if (is_object($config)) {
            self::$configuration = $config;
        } else if (is_array($config)) {
            self::$configuration = (object) $config;
        }

        if (
            !is_object(self::$configuration)
            || !property_exists(self::$configuration, "database")
        ) {
            self::$fatal_error = "Invalid database configurations";
            return FALSE;
        }
        self::$fatal_error = FALSE;
        return TRUE;
    }

    private static function public_decorator(Closure $public_function) {
        if(empty(self::$configuration)) {
            self::set_stalker_configuration();
        }
        if(!self::$fatal_error) {
            return $public_function();
        }
        $trace = debug_backtrace();
        $caller = $trace[1];
        trigger_error($caller['class']. "::" .$caller['function']. " -> " . self::$fatal_error, E_USER_ERROR);
        return FALSE;
    }

    public static function database_connection() {
        return self::public_decorator(function() {
            return self::$configuration->database;
        });
    }

    public static function table_settings() {
        return self::public_decorator(function() {
            $settings = new stdClass();
            if(!property_exists(self::$configuration, "settings")) {
                $settings->engine = self::ENGINE;
                $settings->charset = self::CHARSET;
                $settings->collation = self::COLLATION;
            } else {
                if(!property_exists(self::$configuration->settings, "engine")) {
                    $settings->engine = self::ENGINE;
                } else {
                    $settings->engine = self::$configuration->settings->engine;
                }
                if(!property_exists(self::$configuration->settings->collation, "collation")) {
                    $settings->charset = self::CHARSET;
                    $settings->collation = self::COLLATION;
                } else {
                    $settings->collate = self::$configuration->settings->collate;
                    $settings->charset = explode("_",self::$configuration->settings->collate);
                    $settings->charset = $settings->charset[0];
                }
            }
            return $settings;
        });
    }

    public static function custom_feilds_lengths() {
        return self::public_decorator(function() {
            $lengths = new stdClass();
            if(!property_exists(self::$configuration, "customLengths")) {
                $lengths->id = self::ID_LENGTH;
                $lengths->email = self::EMAIL_LENGTH;
                $lengths->password = self::PASSWORD_LENGTH;
                $lengths->phone = self::PHONE_LENGTH;
                $lengths->ip = self::IP_LENGTH;
                $lengths->link = self::LINK_LENGTH;
            } else {
                if(property_exists(self::$configuration->customLengths, "id")
                && Stalker_Validator::is_id(self::$configuration->customLengths->id)) {
                    $lengths->id = self::$configuration->customLengths->id;
                } else {
                    $lengths->id = self::ID_LENGTH;
                }

                if(property_exists(self::$configuration->customLengths, "email")
                && Stalker_Validator::is_id(self::$configuration->customLengths->email)) {
                    $lengths->email = self::$configuration->customLengths->email;
                } else {
                    $lengths->email = self::EMAIL_LENGTH;
                }

                if(property_exists(self::$configuration->customLengths, "password")
                && Stalker_Validator::is_id(self::$configuration->customLengths->password)) {
                    $lengths->password = self::$configuration->customLengths->password;
                } else {
                    $lengths->password = self::PASSWORD_LENGTH;
                }

                if(property_exists(self::$configuration->customLengths, "phone")
                && Stalker_Validator::is_id(self::$configuration->customLengths->phone)) {
                    $lengths->phone = self::$configuration->customLengths->phone;
                } else {
                    $lengths->phone = self::PHONE_LENGTH;
                }

                if(property_exists(self::$configuration->customLengths, "ip")
                && Stalker_Validator::is_id(self::$configuration->customLengths->ip)) {
                    $lengths->ip = self::$configuration->customLengths->ip;
                } else {
                    $lengths->ip = self::IP_LENGTH;
                }

                if(property_exists(self::$configuration->customLengths, "link")
                && Stalker_Validator::is_id(self::$configuration->customLengths->link)) {
                    $lengths->link = self::$configuration->customLengths->link;
                } else {
                    $lengths->link = self::LINK_LENGTH;
                }
            }
            return $lengths;
        });
    }

    public static function backup_settings() {
        return self::public_decorator(function() {
            $settings = new stdClass();
            if(!property_exists(self::$configuration, "backup")) {
                $settings->max = self::BACKUP_MAX;
                $settings->per_day = self::BACKUP_PER_DAY;
            } else {
                if(property_exists(self::$configuration->backup, "max")
                && Stalker_Validator::regex_check(self::$configuration->backup->max, "number")) {
                    $settings->max = self::$configuration->backup->max;
                } else {
                    $settings->max = self::BACKUP_MAX;
                }

                if(property_exists(self::$configuration->backup, "perDay")
                && Stalker_Validator::regex_check(self::$configuration->backup->perDay, "number")) {
                    $settings->per_day = self::$configuration->backup->perDay;
                } else {
                    $settings->per_day = self::BACKUP_PER_DAY;
                }
            }
            return $settings;
        });
    }
}
