<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataExtra extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'dataextra';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'data',
        'periodo_apuracao'
    ];

}
