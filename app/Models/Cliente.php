<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table ='clientes';

    public $timestamps =false;

    protected $fillable = [

        'cedula',
        'direccion',
        'telefono',
    ];

    public function coches(): HasMany
    {
        return $this->hasMany(Coche::class);
    }
}
