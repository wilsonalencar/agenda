<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movtocontacorrente extends Model
{
    //

    protected $morphClass = 'MorphMovtoConta';
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'periodo_apuracao',
        'estabelecimento_id', 
        'vlr_guia',
        'vlr_gia',
        'vlr_sped',
        'vlr_dipam',
        'usuario_update',
        'dipam'
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
}
