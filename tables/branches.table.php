<?php

class Branches extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build(function($table){
            $table->varchar("name", 255);
        });
    }

    public function course_classes() {
        return $this->has_many("Courses_Class", "branch_id");
    }
}
