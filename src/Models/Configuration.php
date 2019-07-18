<?php
namespace Models;


use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $attributes = [
        'id',
        'key',
        'value',
        'type',
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'name'
    ];

    protected $casts = [
        'value' => 'array'
    ];


}