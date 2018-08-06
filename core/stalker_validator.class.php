<?php

class Stalker_Validator 
{
    private function __construct() {}
    
    public static function regexCheck($item,$type) {
        if(empty($item) && $item !=0)
        {
            return false;
        }
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
            $regex = '/^\d+$/';
        } elseif ($type == 'float')
        {
            $regex = '/^([0-9]*[.])?[0-9]*$/';
        } elseif ( $type == '12hours')
        {
            $regex = '/(\d+)\s?:\s?(\d+)\s?(\w)/';
        } elseif ( $type == '24hours')
        {
            $regex = '/(\d+)\s?:\s?(\d+)\s?:\s?(\d+)/';
        } elseif ( $type == 'base64')
        {
        $regex = '/^data:\w+|.+\/\w+|.+;base64,/i';
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
            if((empty($id) && $id !=0 ) || is_null($id) || !regexCheck($id, 'number'))
            {
                return false;
            }
        } else {
            if( empty($id) || is_null($id) || !regexCheck($id, 'number'))
            {
                return false;
            }
        }
        return true;
    }
  
}