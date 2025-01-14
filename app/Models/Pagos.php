<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagos extends Model
{
    use HasFactory;

    protected $table = 'cesion_derechos'; // Nombre de la tabla

    protected $primaryKey = 'id_pago';
    protected $fillable = [
        'evento_id',
        'usuario_id',
        'articulo_id',
        'archivo',
        'fecha'
    ];

    public $timestamps = false; // Si no tienes los campos created_at y updated_at

    public function evento()
    {
        return $this->belongsTo(eventos::class, 'evento_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(usuarios::class, 'usuario_id', 'id');
    }

    public function articulo()
    {
        return $this->belongsTo(articulos::class, 'articulo_id', 'id');
    }
}