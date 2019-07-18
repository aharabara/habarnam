<?php

return '<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use \Illuminate\Database\Migrations\Migration;

class ' . $className . ' extends Migration
{
    public function up()
    {
        $schema = Manager::schema();
        $schema->create("' . $tableName . '", function (Blueprint $table) {
            $table->increments("id");
            // your fields
            $table->timestamps();
        });
    }

    public function down()
    {
        $schema = Manager::schema();
        $schema->dropIfExists("' . $tableName . '");
    }
}
';