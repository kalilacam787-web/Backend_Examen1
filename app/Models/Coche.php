<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coche extends Model
{
    protected $table ='coches';

    public $timestamps =false;

    protected $fillable =[

        'marca',
        'modelo',
        'color',
        'precio',
        'foto',
        'foto_public_id',
        'cliente_id',
    ];

    protected $casts =[
        'precio'=> 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function revisiones(): HasMany
    {
        return $this->hasMany(Revision::class);
    }
}
