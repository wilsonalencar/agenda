<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Zizaco\Entrust\Traits\EntrustUserTrait;

class User extends Authenticatable
{
    use EntrustUserTrait;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','access_level'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany('App\Models\Role');
    }

    /**
     * The atividades that belong to the user.
     */
    public function atividades()
    {
        return $this->belongsToMany('App\Models\Atividade');
    }

    /**
     * The comentarios that belong to the user.
     */
    public function comentarios()
    {
        return $this->belongsToMany('App\Models\Comentario');
    }

    /**
     * Get the tributos for the user.
     */
    public function tributos()
    {
        return $this->belongsToMany('App\Models\Tributo');
    }

    /**
     * The empresas that belong to the user.
     */
    public function empresas()
    {
        return $this->belongsToMany('App\Models\Empresa');
    }

    /**
     * Check if user is online.
     */
    public function isOnline()
    {
        return Cache::has('user-is-online-' . $this->id);
    }


}
