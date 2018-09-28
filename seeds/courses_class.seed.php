<?php

class Courses_Class_Seed extends Stalker_Seed
{
    public function main_seed() {
        return array(
            array(
                "__forced" => true,
                "id" => 1,
                "branch_id" => 5,
                "min_kids" => 5
            ),
            array(
                "id" => 2,
                "branch_id" => 1,
                "min_kids" => 6,
                "type" => "comp"
            ),
            array(
                "id" => 3,
                "branch_id" => 5,
                "min_kids" => 6,
                "type" => "comp"
            ),
            array(
                "id" => 4,
                "branch_id" => 5,
                "min_kids" => 6,
                "type" => "comp"
            )
        );
    }

    public function temporary_seed() {
        return array(
            array(
                "branch_id" => 1,
                "min_kids" => 5
            ),
            array(
                "branch_id" => 1,
                "min_kids" => 6,
                "type" => "comp"
            )
        );
    }
}
