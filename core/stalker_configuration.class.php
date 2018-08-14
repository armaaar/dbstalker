<?php

class Stalker_Configuration {
    private static $configuration;
    private static $fatal_error;

    const ENGINE = "InnoDB";
    const CHARSET = "utf8";
    const COLLATION = "utf8_general_ci";
    const ID_LENGTH = 11;
    const EMAIL_LENGTH = 255;
    const PASSWORD_LENGTH = 40;
    const PHONE_LENGTH = 20;
    const IP_LENGTH = 45;
    const LINK_LENGTH = 512;

    private function __construct() {}
    
    private static function read_stalker_configuration() {
            self::$configuration = json_decode(file_get_contents("./stalker_config.json"));
            if(json_last_error()===JSON_ERROR_NONE) {
                self::$fatal_error = FALSE;
                return TRUE;
            } else {
                self::$fatal_error = TRUE;
                return json_last_error();
            }
    }

    private static function public_decorator(Closure $public_function) {
        if(empty(self::$configuration)) {
            self::read_stalker_configuration();
        }
        if(!self::$fatal_error) {
            return $public_function();
        }
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
                    $settings->charset = (explode("_",self::$configuration->settings->collate))[0];
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

                if(!property_exists(self::$configuration->customLengths, "id")
                || property_exists(self::$configuration->customLengths, "id")
                && Stalker_Validator::is_id(self::$configuration->customLengths->id)) {
                    $lengths->id = self::ID_LENGTH;
                } else {
                    $lengths->id = self::$configuration->customLengths->id;
                }

                if(!property_exists(self::$configuration->customLengths, "email")
                || property_exists(self::$configuration->customLengths, "email")
                && Stalker_Validator::is_id(self::$configuration->customLengths->email)) {
                    $lengths->email = self::EMAIL_LENGTH;
                } else {
                    $lengths->email = self::$configuration->customLengths->email;
                }

                if(!property_exists(self::$configuration->customLengths, "password")
                || property_exists(self::$configuration->customLengths, "password")
                && Stalker_Validator::is_id(self::$configuration->customLengths->password)) {
                    $lengths->password = self::PASSWORD_LENGTH;
                } else {
                    $lengths->password = self::$configuration->customLengths->password;
                }

                if(!property_exists(self::$configuration->customLengths, "phone")
                || property_exists(self::$configuration->customLengths, "phone")
                && Stalker_Validator::is_id(self::$configuration->customLengths->phone)) {
                    $lengths->phone = self::PHONE_LENGTH;
                } else {
                    $lengths->phone = self::$configuration->customLengths->phone;
                }

                if(!property_exists(self::$configuration->customLengths, "ip")
                || property_exists(self::$configuration->customLengths, "ip")
                && Stalker_Validator::is_id(self::$configuration->customLengths->ip)) {
                    $lengths->ip = self::IP_LENGTH;
                } else {
                    $lengths->ip = self::$configuration->customLengths->ip;
                }

                if(!property_exists(self::$configuration->customLengths, "link")
                || property_exists(self::$configuration->customLengths, "link")
                && Stalker_Validator::is_id(self::$configuration->customLengths->link)) {
                    $lengths->link = self::LINK_LENGTH;
                } else {
                    $lengths->link = self::$configuration->customLengths->link;
                }
            }
            return $lengths;
        });
    }

    
}