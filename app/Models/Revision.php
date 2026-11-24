<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revision extends Model
{
    protected $table ='revisiones';

    public $timestamps =false;

    protected $fillable =[

        'codigo',
        'aceite',
        'filtro',
        'frenos',
        'coche_id',
    ];

    protected $casts =[

        'aceite' =>'boolean',
        'filtro' =>'boolean',
        'frenos' =>'boolean',
    ];

    public function coche(): BelongsTo
    {
        return $this->belongsTo(Coche::class);
    }
}
