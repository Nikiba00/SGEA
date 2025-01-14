<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CesionDerechos extends Model
{
    use HasFactory;
    protected $table = 'pagos'; // Nombre de la tabla
    protected $primaryKey = 'id_cesion';
    protected $fillable = [
        'evento_id',
        'usuario_id',
        'articulo_id',
        'comprobante',
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