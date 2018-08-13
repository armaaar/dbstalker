<?php

class Courses_Class extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build(function($table){
            $table->id("id")->primary();
            $table->id("clevel_id")->index();
            $table->id("branch_id")->index();
            $table->int("min_kids", 3)->default(1);
            $table->int("max_kids", 3);
            $table->varchar("schedule", 1024);
            $table->enum("type", array('norm', 'comp'))->default('norm');
            $table->boolean("uniform")->default(false);
            $table->date("start_date")->nullable()->default(NULL);
            $table->int("period", 3);
            $table->int("price", 6);
            $table->enum("state", array(
                'working',
                'pending',
                'waiting',
                'finished',
                'totally_finished',
                'cancelled'
            ));
            $table->int("starting_month", 2)->default(1);
            $table->id("comp_class_id")->index()->nullable()->default(NULL);
            $table->int("comp_class_session", 3)->nullable()->default(NULL);
        });
    }
}
