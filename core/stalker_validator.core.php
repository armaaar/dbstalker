<?php

class Stalker_Validator
{
    private function __construct() {}

    public static function regex_check($item, $type) {
        if(empty($item) && !($item === 0 || $item === 0.0 || $item === "0"))
        {
            return false;
        }
        $type = strtolower($type);
        $regex = NULL;
        if($type == 'email')
        {
            $regex = '/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/';
        } elseif ($type == 'date')
        {
            $regex = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/';
        } elseif ($type == 'datetime')
        {
            $regex = '/^([0-9]{4})-([0-1][0-9])-([0-3][0-9])(?:( [0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/';
        } elseif ($type == 'gender')
        {
            $regex = '/^[mfMF]$/';
        } elseif ($type == 'phone')
        {
            $regex = '/^[^a-zA-Z.,]+$/';
        } elseif ($type == 'country')
        {
            $regex = '/^[a-zA-Z]{2,3}$/';
        } elseif ( $type == 'link')
        {
            $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \+\*\-\_\?\&\;\%\=\.]*)*\/?$/i';
        } elseif ( $type == 'number')
        {
            $regex = '/^-?\d+$/';
        } elseif ($type == 'float')
        {
            $regex = '/^-?([0-9]*[.])?[0-9]*$/';
        } elseif ( $type == '12hours')
        {
            $regex = '/(\d+)\s?:\s?(\d+)\s?(\w)/';
        } elseif ( $type == '24hours')
        {
            $regex = '/(\d+)\s?:\s?(\d+)\s?:\s?(\d+)/';
        } elseif ( $type == 'base64')
        {
            $regex = '/^data:\w+|.+\/\w+|.+;base64,/i';
        } elseif  ($type == 'json' ) {
            $regex = '/(?(DEFINE)
            (?<json>(?>\s*(?&object)\s*|\s*(?&array)\s*))
            (?<object>(?>\{\s*(?>(?&pair)(?>\s*,\s*(?&pair))*)?\s*\}))
            (?<pair>(?>(?&STRING)\s*:\s*(?&value)))
            (?<array>(?>\[\s*(?>(?&value)(?>\s*,\s*(?&value))*)?\s*\]))
            (?<value>(?>true|false|null|(?&STRING)|(?&NUMBER)|(?&object)|(?&array)))
            (?<STRING>(?>"(?>\\\\(?>["\\\\\/bfnrt]|u[a-fA-F0-9]{4})|[^"\\\\\0-\x1F\x7F]+)*"))
            (?<NUMBER>(?>-?(?>0|[1-9][0-9]*)(?>\.[0-9]+)?(?>[eE][+-]?[0-9]+)?))
            )
            \A(?&json)\z/x';
        }

        if(!$regex)
        {
            return null;
        }
        return preg_match($regex, $item);
    }

    public static function is_id($id, $zero_allowed= false)
    {
        if($zero_allowed)
        {
            if((empty($id) && $id !=0 ) || is_null($id) || !self::regex_check($id, 'number'))
            {
                return false;
            }
        } else {
            if( empty($id) || is_null($id) || !self::regex_check($id, 'number'))
            {
                return false;
            }
        }
        return true;
    }

    public static function validate_to_schema(Stalker_Table $table, array $schema)
    {
        $validation_errors = array();

        foreach ($schema as $name => $col) {
            $validation_errors[$name] = array();

            $col_is_index = (
                array_key_exists("key", $col)
                && preg_match('/^(PRI)$/',$col["key"])
                && array_key_exists("validator", $col)
                && ($col['validator'] == 'id' || $col['validator'] == 'zero_allowed_id')
            );

            if(!property_exists($table, $name) && !$col_is_index && !array_key_exists("default", $col))
            {
                $validation_errors[$name][] = "Field can't be empty";
            }

            if(property_exists($table, $name))
            {
                if(!(array_key_exists("null", $col) && $col['null'] == true && is_null($table->{$name})))
                {
                    if(array_key_exists("validator", $col))
                    {
                        if($col['validator'] == 'id') {
                            if(!self::is_id($table->{$name})) {
                              $validation_errors[$name][] = "Invalid id";
                            }
                        } elseif($col['validator'] == 'zero_allowed_id') {
                            if(!self::is_id($table->{$name}, true)) {
                              $validation_errors[$name][] = "Invalid id";
                            }
                        } elseif(!self::regex_check($table->{$name}, $col['validator'])) {
                            $validation_errors[$name][] = "Invalid value. Value must be a ".$col['validator'];
                        }
                    } elseif($col['type'][0] == 'enum' && !in_array($table->{$name}, $col['type'][1])) {
                        $validation_errors[$name][] = "Invalid value";
                    } elseif(is_array($table->{$name})) {
                        $validation_errors[$name][] = "Field can't be an array";
                    } elseif(is_null($table->{$name})) {
                        $validation_errors[$name][] = "Field can't be empty";
                    }
                }
            }

            if(empty($validation_errors[$name])) {
                unset($validation_errors[$name]);
            }
        }

        return empty($validation_errors) ? null : $validation_errors;
    }

}
