<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use PHPJasper\PHPJasper;
use App\Models\Reporte;
use App\Models\TipoReporte;
use App\Models\eventos;
use App\Models\usuarios;
use App\Models\articulos;

class ReportesController extends Controller
{
    public function __construct(){
        $this->middleware('can:reportes.index')->only('index');
        $this->middleware('can:reportes.create')->only('store'); 
        $this->middleware('can:reportes.destroy')->only('destroy'); 
    }

    //METODO PARA GENERAR UN NUEVO REPORTE

}