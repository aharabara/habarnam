<?php

return '<?php
namespace \App\Models\;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 */
class ' . $className . ' extends Model
{
    protected $table = "' . $tableName . '";

    protected $attributes = [];
    protected $fillable = [];
    protected $cast = [];
}';