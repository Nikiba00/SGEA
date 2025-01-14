<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Reportes;
use App\Models\articulos;
use App\Models\eventos;
use App\Models\articulosAutores;
use App\Models\usuarios;
use App\Models\Areas;
use App\Models\revisoresArticulos;
use App\Models\User;
use TCPDF;
use Spatie\Permission\Models\Role;
use DB;
use Carbon\Carbon;

class AgendasController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:agendas.index')->only('index');
    }
    public function index($eventoId)
    {
        $usuario = auth()->user();
        //dd($usuario);
        try {
            $articulos = eventos::join('articulos', 'eventos.id', '=', 'articulos.evento_id')
                ->join('areas', 'articulos.area_id', '=', 'areas.id')
                ->join('articulos_autores', 'articulos.id', '=', 'articulos_autores.articulo_id')
                ->join('usuarios', 'articulos_autores.usuario_id', '=', 'usuarios.id')
                ->where('eventos.id', $eventoId)
                ->where('articulos.estado', "Aceptado")
                ->selectRaw('
                eventos.nombre AS evento,
                articulos.titulo AS titulo,
                areas.nombre AS area,
                STRING_AGG(usuarios.nombre || \' \' || usuarios.ap_paterno || \' \' || COALESCE(usuarios.ap_materno, \'\'), \', \' ORDER BY articulos_autores.orden) AS autores,
                STRING_AGG(articulos_autores.institucion, \', \' ORDER BY articulos_autores.orden) AS instituciones,
                (SELECT articulos_autores.email 
                 FROM articulos_autores 
                 WHERE articulos_autores.articulo_id = articulos.id 
                 AND articulos_autores.correspondencia = TRUE LIMIT 1) AS correo
                ')
                ->groupBy('eventos.nombre', 'articulos.titulo', 'areas.nombre', 'articulos.id') // Agrega "articulos.id"
                ->get();
            //dd($articulos);

            $evento = eventos::select('id', 'logo', 'nombre', 'acronimo', 'fecha_inicio', 'fecha_fin', 'edicion')
                ->where('id', $eventoId)
                ->first();
            //dd($evento);

            $fechaInicio = Carbon::parse($evento->fecha_inicio);
            $fechaFin = Carbon::parse($evento->fecha_fin);

            // Dar formato a las fechas
            $formatoInicio = $fechaInicio->translatedFormat('l d \\d\\e F \\d\\e Y');
            $formatoFin = $fechaFin->translatedFormat('l d \\d\\e F \\d\\e Y');
            // Calcula la diferencia en días
            $duracion = $fechaFin->diffInDays($fechaInicio) + 1;
            //dd($duracion);
            return view('Agendas.index', compact('articulos', 'evento', 'duracion', 'formatoInicio', 'formatoFin'));
        } catch (\Exception $e) {
            dd($e);
            return redirect()->back()->with('error', 'Hubo un problema al generar la agenda. Inténtelo nuevamente.');
        }



    }
    
    /*public function generarAgenda()
    {
        try {
            // Obtén los datos del formulario
            $horaSeleccionada = $request->input('hora'); // Hora seleccionada del formulario
            $fechaInicio = $request->input('fecha_inicio'); // Fecha de inicio del evento
            $fechaFin = $request->input('fecha_fin'); // Fecha de fin del evento
            $evento = Evento::find($request->input('evento_id')); // Datos del evento (ajusta según tu modelo)

            // Calcula la duración en horas (o días si es necesario)
            $duracionEvento = (strtotime($fechaFin) - strtotime($fechaInicio)) / 3600; // en horas

            // Inicia TCPDF
            $pdf = new TCPDF();

            // Agrega una página
            $pdf->AddPage();

            // Título
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Agenda del Evento: ' . $evento->nombre, 0, 1, 'C');
            $pdf->Ln(10);

            // Configura la tabla
            $pdf->SetFont('helvetica', '', 10);

            // Establece el número de columnas (dependiendo de la duración del evento)
            $numeroColumnas = $duracionEvento + 1; // +1 por la primera columna con la hora

            // Establece el número de filas (dependiendo de la hora seleccionada)
            $numeroFilas = (int) $horaSeleccionada + 1; // +1 para incluir la fila de encabezado

            // Crea la cabecera de la tabla (horas)
            $pdf->Cell(30, 10, 'Hora', 1, 0, 'C');
            for ($i = 1; $i <= $duracionEvento; $i++) {
                $pdf->Cell(30, 10, "Columna $i", 1, 0, 'C');
            }
            $pdf->Ln();

            // Rellenar las filas con los datos (por ejemplo, para cada hora seleccionada)
            for ($i = 0; $i < $numeroFilas; $i++) {
                $pdf->Cell(30, 10, "Hora " . ($i + 1), 1, 0, 'C');
                for ($j = 0; $j < $duracionEvento; $j++) {
                    // Aquí puedes agregar los datos dinámicos para cada celda
                    // Por ejemplo, mostrar la información del artículo en esa columna y fila
                    $pdf->Cell(30, 10, 'Dato', 1, 0, 'C');
                }
                $pdf->Ln();
            }

            // Salida del PDF
            $pdf->Output('agenda_evento.pdf', 'I');

            return redirect()->back()->with('success', 'Agenda generada correctamente.');
        } catch (\Exception $e) {
            dd($e);
            return redirect()->back()->with('error', 'Hubo un problema al generar la agenda. Inténtelo nuevamente.');
        }
    }*/
}