<?php

class Courses_Class extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build(function($table){
            $table->id("branch_id")->index()->nullable();
            $table->int("min_kids", 3)->def(1);
            $table->enum("type", array('norm', 'comp'))->def('norm');
            $table->boolean("uniform")->def(false);
        });
    }

    public function branch() {
        return $this->belongs_to("Branches", "branch_id");
    }
}
