<?php

class Courses_Class extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build(function($table){
            $table->id("branch_id")->index();
            $table->int("min_kids", 3)->default(1);
            $table->enum("type", array('norm', 'comp'))->default('norm');
            $table->boolean("uniform")->default(false);
        });
    }
}
