<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agendas extends Model
{
    protected $table = 'agendas';
    protected $primaryKey = 'id_agenda';
    protected $fillable = [
        'evento_id',
        'creado_por',
        'archivo'
    ];

    public function evento()
    {
        return $this->belongsTo(eventos::class, 'evento_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(usuarios::class, 'creado_por', 'id');
    }
}