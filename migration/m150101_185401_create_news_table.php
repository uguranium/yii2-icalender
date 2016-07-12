<?php

use yii\db\Migration;

class m150101_185401_create_news_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('icalender_main', [
            'id'            => $this->primaryKey(),
            'special_id'    => $this->integer(),
            'METHOD'        => $this->string(),
            'VERSION'       => $this->string(),
            'PRODID'        => $this->string(),
            'X-WR-CALNAME'  => $this->string(),
            'X-WR-TIMEZONE' => $this->string(),
            'CALSCALE'      => $this->string(),
            'PREFERRED_LANGUAGE'    => $this->string(),
            'created_at' => $this->integer()
        ]);

        $this->createTable('icalender_event', [
            'id'            => $this->primaryKey(),
            'icalender_id'  => $this->integer(),
            'special_id'    => $this->integer(),
            'UID'           => $this->integer(),
            'DTSTART'       => $this->string(),
            'DTEND'         => $this->string(),
            'SUMMARY'       => $this->string(),
            'RESOURCES'     => $this->string()
        ]);
    }
}