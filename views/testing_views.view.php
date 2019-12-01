<?php

class Testing_Views extends Stalker_View
{
    public function view_query() {
        return "SELECT `courses_class`.`id`, `courses_class`.`min_kids`, `test`.`f3`
                FROM `courses_class`
                LEFT JOIN `test`
                    ON `courses_class`.`id` = `test`.`id2`
                ORDER BY `courses_class`.`type`";
    }
}
