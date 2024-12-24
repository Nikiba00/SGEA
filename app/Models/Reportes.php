<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reportes extends Model
{
    protected $table = 'reportes';

    protected $primaryKey = 'id_reporte';
    protected $fillable = [
        'evento_id',
        'usuario_id',
        'id_tipoReporte',
        'archivo',
        'articulo_id'
    ];

    public function tipoReporte() {
        return $this->belongsTo(TipoReporte::class, 'id_tipoReporte', 'id_tipoReporte');
    }

    public function evento(){
        return $this->belongsTo(eventos::class, 'evento_id', 'id');
    }

    public function usuario(){
        return $this->belongsTo(usuarios::class, 'usuario_id', 'id');
    }

    public function articulo(){
        return $this->belongsTo(articulos::class, 'articulo_id', 'id');
    }
}