<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use \Illuminate\Database\Migrations\Migration;

class CreateQueueTable extends Migration
{
    public function up()
    {
        $schema = Manager::schema();
        $schema->create('queue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue');
            $table->longText('payload');
            $table->tinyInteger('attempts')->unsigned();
            $table->boolean('reserved')->default(false);
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
            $table->index(['queue', 'reserved', 'reserved_at']);
        });
    }

    public function down()
    {
        $schema = Manager::schema();
        $schema->dropIfExists("queue");
    }
}
