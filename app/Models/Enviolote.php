<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enviolote extends Model
{
    //

    protected $morphClass = 'MorphEnvioLote';
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'email_1',
        'email_2', 
        'email_3',
        'id_empresa',
        'id_tributo',
        'regra_geral',
        'id'
    ];

    /**
     * Get the estabelecimentos for this empresa.
     */
    public function estabelecimentos()
    {
        return $this->belongsTo('App\Models\Estabelecimento', 'estabelecimento_id');
    }

    /**
     * Get the estabelecimentos for this empresa.
     */
    public function municipios()
    {
        return $this->belongsTo('App\Models\Municipio');
    }

    /**
     * Get the empresa record associated with the estabelecimento.
     */
    public function statusprocadm()
    {
        return $this->belongsTo('App\Models\Statusprocadm','status_id');
    }
}










