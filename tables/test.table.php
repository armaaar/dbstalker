<?php

class Test extends Stalker_Table
{
    public function schema() {
        return Stalker_Schema::build(function($table){
            $table->float("f1");
            $table->double("f2");
            $table->decimal("f3", 10, 0);
            $table->double("f4")->unique();;
            $table->id("id2")->index();
            $table->bigint("bigger",20)->unsigned_zerofill();
            $table->smallint("smaller",5)->unsigned();
        });
    }
}
