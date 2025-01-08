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
use JasperPHP\JasperPHP;
use DB;
use Carbon\Carbon;

class ReportesController extends Controller
{
    public function __construct()
    {
        //REVISAR LOS MIDDLEWARE PORQUE NO JALA LA AUTENTICACIÓN
        $this->middleware('can:reportes.index')->only('index');
        $this->middleware('can:reportes.create')->only('crearReporteArticulo', 'crearReporteEvento');
        $this->middleware('can:reportes.view')->only('verReporte', 'verReporteEvento');
        $this->middleware('can:reportes.download')->only('descargarReporte');
        //$this->middleware('can:reportes.update')->only('actualizarReporte');
    }

    public function index($eventoId, $id)
    {
        $usuario = $id;
        $usuario = auth()->user();//id del usuario
        //dd($usuario);

        //No hay nada referente a participantes. Está hablando del modelo participantes
        /*$esAutor = $usuario->participantes()->where('evento_id', $eventoId)->where('rol', 'autor')->exists();
        //$esAutor = $id->getRoleInEvent($eventoId);
        dd($esAutor);

        SI NO ES AUTOR NO DEBERÍA ENTRAR A LA VISTA DE REPORTES. EN LA VISTA SE MOSTRARÁ EL MENSAJE DE QUE 
        SOLO PUEDE GENERAR REPORTE DE LOS ARTICULOS EN LOS QUE FUE SELECCIONADO COMO AUTOR DE CORRESPONDENCIA
        if (!$esAutor) {
            return redirect()->route('home')->with('error', 'No tienes acceso a esta vista.');
        }*/

        /*$articulos = articulos::where('evento_id', $eventoId)->whereIn('estado', ['Aceptado', 'Rechazado'])->whereHas('articulos_autores', function ($query) use ($usuario) {
            $query->where('usuario_id', $usuario->id)
                ->where('correspondencia', true);
        })->with(['area'])->get();*/
        $articulos = articulos::where('evento_id', $eventoId)
            ->whereIn('estado', ['Aceptado', 'Rechazado'])
            ->whereHas('autores', function ($query) use ($usuario) {
                $query->where('usuario_id', $usuario->id)
                    ->where('correspondencia', true);
            })
            ->with('area')
            ->get();

        //dd($articulos);

        $Areas = areas::select('nombre', 'id')->get();
        /*$articulos = articulos::where('evento_id', $eventoId)
            ->whereIn('estado', ['Aceptado', 'Rechazado'])
            ->get()
            ->filter(function ($articulo) use ($usuario) {
                return $articulo->autorCorrespondencia && $articulo->autorCorrespondencia->usuario_id === $usuario->id;
            })
            ->load('area');*/
        //dd($articulos);    

        /*$articulos = articulos::where('evento_id', $eventoId)
            ->whereIn('estado', ['Aceptado', 'Rechazado'])->get();*/

        return view('Reportes.index', compact('articulos', 'Areas'));
    }

    public function crearReporteArticulo($eventoId, $articuloId)
    {
        $usuario = auth()->user();
        //dd($usuario);

        //OBTIENE LOS ARTÍCULOS QUE TENGAN ESTADO ACEPTADO O RECHAZADO - sólo de estos se generará reporte
        $articulo = articulos::with('area', 'evento')
            ->where('id', $articuloId)
            ->whereIn('estado', ['Aceptado', 'Rechazado'])
            ->firstOrFail();
        //dd($articulo);

        //VALIDACIÓN DE QUE EL AUTOR SEA EL DE CORRESPONDENCIA - sólo este podrá generar reporte del artículo
        $esAutorCorrespondencia = articulosAutores::where('articulo_id', $articulo->id)
            ->where('usuario_id', $usuario->id)
            ->where('correspondencia', true)
            ->exists();
        //dd($esAutorCorrespondencia);

        if (!$esAutorCorrespondencia) {//PROBABLEMENTE SEA INNECESARIA
            abort(403, 'No tienes permiso para generar este reporte.');
        }

        //VERIFICAR SI YA EXISTE UN REPORTE DE ESTE ARTICULO. EN CASO DE QUE SÍ, LO ELIMINA (REEMPLAZO)
        $reporteExistente = Reportes::where('articulo_id', $articuloId)->first();
        if ($reporteExistente) {
            $this->eliminarReporteExistente($articuloId, null);
        }
        //dd($reporteExistente);

        $evento = eventos::find($eventoId);
        //dd($evento);
        $fileParameter = 'public/EventImgs/' . $evento->acronimo . $evento->edicion . '/' . $evento->acronimo . $evento->edicion . 'parameter.json';
        //dd($fileParameter);
        if (Storage::exists($fileParameter)) {
            $jsonData = Storage::get($fileParameter);
            $parametros = json_decode($jsonData, true);
            $maxToApprove = $parametros['MaxToApprove'];
        }
        //dd($maxToApprove);

        //CONSULTA CRUDA A LA BDD PARA MEJOR CONTROL
        $query = "
            SELECT e.logo, a.titulo, autores_autores.nombres AS autor, aa.email AS correo, ar.nombre AS area, a.estado, 
            ROUND(
                (COALESCE(ra.puntuacion, 0) + 
                COALESCE(ra2.puntuacion, 0) + 
                COALESCE(ra3.puntuacion, 0)) / 
                NULLIF(
                    (CASE WHEN ra.puntuacion IS NOT NULL THEN 1 ELSE 0 END) + 
                    (CASE WHEN ra2.puntuacion IS NOT NULL THEN 1 ELSE 0 END) + 
                    (CASE WHEN ra3.puntuacion IS NOT NULL THEN 1 ELSE 0 END), 0
                ), 2
            ) AS puntuacion, e.nombre AS evento, e.acronimo, e.edicion, ra.comentarios AS comentarios1, ra2.comentarios AS comentarios2, ra3.comentarios AS comentarios3
            FROM articulos a
            JOIN eventos e ON e.id = a.evento_id
            JOIN articulos_autores aa ON aa.articulo_id = a.id AND aa.correspondencia = TRUE
            JOIN usuarios u ON u.id = aa.usuario_id
            JOIN areas ar ON ar.id = a.area_id
            LEFT JOIN (
                SELECT aa2.articulo_id, STRING_AGG(CONCAT(u2.nombre, ' ', u2.ap_paterno, ' ', u2.ap_materno), ', ' ORDER BY aa2.orden) AS nombres
                FROM articulos_autores aa2
                JOIN usuarios u2 ON u2.id = aa2.usuario_id
                GROUP BY aa2.articulo_id
            ) AS autores_autores ON autores_autores.articulo_id = a.id
            LEFT JOIN revisores_articulos ra ON ra.articulo_id = a.id AND ra.orden = 1
            LEFT JOIN revisores_articulos ra2 ON ra2.articulo_id = a.id AND ra2.orden = 2
            LEFT JOIN revisores_articulos ra3 ON ra3.articulo_id = a.id AND ra3.orden = 3
            WHERE a.id = ?
            GROUP BY e.logo, a.titulo, autores_autores.nombres, aa.email, ar.nombre, a.estado, e.nombre, ra.puntuacion, ra2.puntuacion, ra3.puntuacion, e.acronimo, e.edicion, ra.comentarios, ra2.comentarios, ra3.comentarios
        ";
        $resultado = DB::select($query, [$articuloId]);
        //dd($resultado);

        if (empty($resultado)) {
            abort(404, 'No se encontraron datos para generar el reporte.');
        }

        //DATOS PARA EL REPORTE - el 0 es para el arreglo
        $data = [
            'logo' => $resultado[0]->logo, //No recuerdo para qué deje esta pero luego vemos
            'titulo' => $resultado[0]->titulo,
            'autor' => $resultado[0]->autor,
            'correo' => $resultado[0]->correo,
            'area' => $resultado[0]->area,
            'estado' => $resultado[0]->estado,
            'puntuacion' => $resultado[0]->puntuacion,
            'evento' => $resultado[0]->evento,
            'acronimo' => $resultado[0]->acronimo,
            'edicion' => $resultado[0]->edicion,
            'comentarios1' => $resultado[0]->comentarios1,
            'comentarios2' => $resultado[0]->comentarios2,
            'comentarios3' => $resultado[0]->comentarios3,
            'puntuacionMax' => $maxToApprove
        ];
        //dd($data);

        //Revisar las líneas para el nombre
        $nombreReporte = "Reporte del artículo: {$articulo->titulo} ({$articulo->evento->acronimo}-{$articulo->evento->edicion})_" . Carbon::now('America/Mexico_City')->format('d-m-Y_H:i') . ".pdf";
        //dd($nombreReporte);

        // Ruta del template y archivo de salida
        //$templatePath = resource_path('reports/ReporteAutor.jrxml');
        $templatePath = str_replace('\\', '/', resource_path('reports/ReporteAutor.jrxml'));
        //dd($templatePath);
        //$outputPath = storage_path("app/public/EventImgs/{$articulo->evento->acronimo}{$articulo->evento->edicion}/{$nombreReporte}");
        $outputPath = storage_path("app/public/reports/{$nombreReporte}");
        //dd($outputPath);

        if (!file_exists(storage_path('app/public/reports'))) {
            mkdir(storage_path('app/public/reports'), 0777, true);
        }
        
        $outputDir = dirname($outputPath);
        //dd($outputDir);

        if (!file_exists($templatePath)) {
            throw new \Exception("No se encontró la plantilla: {$templatePath}");
        }
        
        if (!is_dir(dirname($outputPath)) || !is_writable(dirname($outputPath))) {
            throw new \Exception("La carpeta de salida no existe o no tiene permisos: {$outputPath}");
        }

        if (!is_dir($outputDir) || !is_writable($outputDir)) {
            throw new \Exception("La ruta de salida no es válida o no tiene permisos: $outputDir");
        }
        $data = array_map(function ($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'auto') : $value;
        }, $data);
        //dd($data);

        try {
            $this->generarReportePDF($data, $templatePath, $outputPath);
            return back()->with('success', "Reporte '{$nombreReporte}' generado con éxito.");
        } catch (\Exception $e) {
            //return back()->with('error', 'Error al generar el reporte: ' . $e->getMessage() . ' en ' . $e->getFile() . ' en la línea ' . $e->getLine() . ' Stack Trace: ' . $e->getTraceAsString());
            //dd($e);
            return back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    public function crearReporteEvento($eventoId)
    {
        $evento = eventos::findOrFail($eventoId);

        $usuario = auth()->user();
        $verificaRol = $usuario->participantes()->where('evento_id', $eventoId)
            ->whereIn('rol', ['administrador', 'comité'])
            ->exists();

        if (!$verificaRol) {
            abort(403, 'No tienes permisos para generar este reporte.');
        }

        //VERIFICA SI YA EXISTE UN REPORTE DEL EVENTO - Sí: lo elimina/reemplaza; No: continúa generando el reporte
        $reporteExistente = Reportes::where('evento_id', $eventoId)->first();
        if ($reporteExistente) {
            $this->eliminarReporteExistente(null, $eventoId);
        }

        //CONSULTA CRUDA SQL PARA MEJOR CONTROL
        $query = "
            SELECT 
                
            FROM 
            JOIN 
            JOIN 
            JOIN 
            JOIN 
            LEFT JOIN revisores_articulos ra ON art.id = ra.articulo_id AND art.evento_id = ra.evento_id
            WHERE 
            GROUP BY 
            ORDER BY 
        ";

        //RESULTADO DE LA CONSULTA PARA EJECUTARLA
        //$resultado = DB::select($query, ['eventoId' => $articulo->evento_id,'articuloId' => $articuloId]);

        //VALIDACIÓN DE RESULTADOS
        /*if (empty($resultado)) {
            abort(404, 'No se encontraron datos para generar el reporte.');
        }*/

        // Obtener la información del evento
        //$evento = eventos::with(['participantes', 'articulos'])->findOrFail($eventoId);

        //CORREGIR LO QUE VA EN EL DATA DE ACUERDO CON LA CONSULTA PARA REPORTES DE EVENTOS
        $data = [
            'evento_nombre' => $evento->nombre,
            'evento_acronimo' => $evento->acronimo,
            'evento_edicion' => $evento->edicion,
            'evento_fecha_inicio' => $evento->fecha_inicio->format('d/m/Y'),
            'evento_fecha_fin' => $evento->fecha_fin->format('d/m/Y'),
            'participantes' => $evento->participantes->pluck('usuario.nombre')->implode(', '),
            'total_articulos' => $evento->articulos->count(),
        ];

        $nombreReporte = "Reporte general del evento: {$evento->nombre} ({$evento->acronimo}-{$evento->edicion})_" . now()->format('d/m/Y_H:i') . ".pdf";

        // Ruta del template y archivo de salida
        $templatePath = resource_path('reports/ReporteEvento.jrxml');
        $outputPath = storage_path("app/public/reportes/evento_{$evento->acronimo}_{$evento->edicion}_" . now()->format('d_m_Y_H_i') . ".pdf");

        // Llamar al método privado para generar el PDF
        try {
            $this->generarReportePDF($data, $templatePath, $outputPath);

            // Mostrar mensaje indicando que el reporte se ha generado
            return back()->with('success', 'Reporte del evento generado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    public function verReporte($reporteId)
    {
        $usuario = auth()->user();

        $reporte = Reportes::with(['evento', 'articulo'])->findOrFail($reporteId);

        $rutaArchivo = storage_path("app/public/reportes/{$reporte->archivo}");
        if (!file_exists($rutaArchivo)) {
            return back()->with('error', 'El reporte no existe');
        }

        if ($reporte->articulo_id) {
            $esAutorCorrespondencia = articulosAutores::where('articulo_id', $reporte->articulo_id)
                ->where('usuario_id', $usuario->id)
                ->where('correspondencia', true)
                ->exists();

            if (!$esAutorCorrespondencia) {
                abort(403, 'No tienes permisos para ver este reporte.');
            }
        } elseif ($reporte->evento_id) {
            $tieneRol = $usuario->participantes()->where('evento_id', $reporte->evento_id)
                ->whereIn('rol', ['administrador', 'comité'])
                ->exists();

            if (!$tieneRol) {
                abort(403, 'No tienes permisos para ver este reporte.');
            }
        } else {
            abort(404, 'Reporte no válido.');
        }

        return response()->file($rutaArchivo);
    }

    public function descargarReporte($reporteId)
    {
        $reporte = Reportes::findOrFail($reporteId);

        $archivoPath = storage_path("app/public/reportes/{$reporte->archivo}");

        if (!file_exists($archivoPath)) {
            return back()->with('error', 'El archivo del reporte no existe en el sistema. Por favor haz clic en "Generar reporte"');
        }

        return response()->download($archivoPath, $reporte->archivo, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /*$data Datos necesarios para llenar el reporte.
     $templatePath Ruta al archivo .jrxml.
     $outputPath Ruta donde se generará el PDF.*/
    private function generarReportePDF(array $data, string $templatePath, string $outputPath)
    {
        if (!file_exists($templatePath)) {
            throw new \Exception("La plantilla del reporte no se encontró en la ruta: {$templatePath}");
        }
        //dd($templatePath);

        $jasper = new \JasperPHP\JasperPHP();
        //dd($jasper);

        try {
            // Ruta para el archivo temporal de datos (en formato JSON)
            $jsonDataPath = storage_path('app/public/temp_data.json');
            //dd($jsonDataPath);

            $data = array_map(function ($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'auto') : $value;
            }, $data);
            $data = array_map(function ($value) {
                if (is_string($value)) {
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        // Si no está en UTF-8, intenta convertirla
                        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                    }
                }
                return $value;
            }, $data);

            $data = array_map(function ($value) {
                if (is_string($value)) {
                    // Elimina caracteres no UTF-8
                    $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
                }
                return $value;
            }, $data);
            /*$data = [
                'records' => [
                    array_map(function ($value) {
                        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'auto') : $value;
                    }, $data)
                ]
            ];*/
            //dd($data);
            // 1. Genera el archivo JSON con los datos
            file_put_contents($jsonDataPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            //dd($jsonDataPath, $data);
            //dd($jsonDataPath, file_get_contents($jsonDataPath));
            // 2. Configura las opciones de entrada y salida
            $options = [
                'format' => ['pdf'], // Formato de salida
                'locale' => 'es_MX',    // Idioma (puedes ajustarlo según sea necesario)
                'params' => [],      // Parámetros adicionales (vacío si no usas)
                'db_connection' => [ // Conexión vacía, porque estás usando JSON
                    'driver' => 'json',
                    'data_file' => $jsonDataPath,
                ],
            ];
            //dd($options);

            // 3. Compila el archivo JRXML a un archivo .jasper
            $compiledTemplate = str_replace('.jrxml', '.jasper', $templatePath);
            //dd($compiledTemplate, file_exists($templatePath));
            if (!file_exists($compiledTemplate)) {
                $jasper->compile($templatePath)->execute();
                //dd(file_exists($compiledTemplate), $compiledTemplate);
            }

            $options['log'] = storage_path('logs/jasper_error.log');
            // 4. Llama a PHPJasper para procesar el reporte
            $jasper->process(
                $compiledTemplate, // Plantilla compilada
                $outputPath,       // Ruta de salida
                $options           // Opciones de configuración
            )->execute();
            dd($jasper->output());
            //dd('PDF generado');
            //dd(file_exists($outputPath . '.pdf'));


            // 5. Limpia el archivo JSON temporal
            if (file_exists($jsonDataPath)) {
                unlink($jsonDataPath);
            }

            //return response()->json(['success' => true, 'file' => $outputPath]);
            dd('PDF generado correctamente: ' . $outputPath . '.pdf');

        } catch (\Exception $e) {
            // Manejo de errores
            //return response()->json(['success' => false, 'message' => utf8_encode($e->getMessage())]);
            dd('No se generó el archivo: ' . $outputPath . '.pdf');
        }
    }

    private function eliminarReporteExistente($articuloId = null, $eventoId = null)
    {
        $query = Reportes::query();

        if ($articuloId) {
            $query->where('articulo_id', $articuloId);
        } elseif ($eventoId) {
            $query->where('evento_id', $eventoId);
        }

        $reporteExistente = $query->first();

        if ($reporteExistente) {
            try {
                $archivoPath = "public/reportes/{$reporteExistente->archivo}";
                if (Storage::exists($archivoPath)) {
                    Storage::delete($archivoPath);
                }

                $reporteExistente->delete();
            } catch (\Exception $e) {
                return back()->with('error', 'Error en la generación del reporte' . $e->getMessage());
            }
        }
    }
    /*public function actualizarReporte($reporteId)
    {
        $reporte = Reportes::findOrFail($reporteId);
        $usuario = auth()->user();

        if (!$usuario->hasAnyRole(['administrador', 'comité'])) {
            return back()->with('error', 'No tienes permisos para actualizar este reporte.');
        }

        $archivoPath = storage_path("app/public/reportes/{$reporte->archivo}");
        if (file_exists($archivoPath)) {
            unlink($archivoPath);
        }

        try {
            //PENDIENTE EN LA CONDICIÓN SI VA COMO id O id_tipoReporte
            if ($reporte->tipoReporte->id === 1) {//Reporte de Artículo
                $this->crearReporteArticulo($reporte->articulo_id);
            }
            // Si el reporte es de tipo 'evento'
            else if ($reporte->tipoReporte->id === 2) {//Reporte del Evento
                $this->crearReporteEvento($reporte->evento_id);
            }

            return back()->with('success', 'Reporte actualizado correctamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar el reporte: ' . $e->getMessage());
        }
    }*/
    /*CONSULTA VIEJA PARA EL REPORTE VERSION 1 DE AUTOR
    SELECT e.logo, art.titulo, STRING_AGG(u.nombre || ' ' || u.ap_paterno || ' ' || u.ap_materno, ', ') AS "autor", MAX(CASE WHEN aa.correspondencia THEN aa.email ELSE ' ' END) AS "correo", area.nombre AS "area", art.estado, CASE WHEN ra.puntuacion IS NULL THEN ' ' ELSE ra.puntuacion::TEXT END, e.nombre AS "evento", e.acronimo, e.edicion, ra.comentarios
    FROM eventos e
    JOIN articulos art ON e.id = art.evento_id
    JOIN areas area ON art.area_id = area.id
    JOIN articulos_autores aa ON art.id = aa.articulo_id AND art.evento_id = aa.evento_id
    JOIN usuarios u ON aa.usuario_id = u.id
    LEFT JOIN revisores_articulos ra ON art.id = ra.articulo_id AND art.evento_id = ra.evento_id
    WHERE e.id = $P{evento_id} AND art.id = $P{articulo_id}
    GROUP BY e.logo, art.titulo, area.nombre, art.estado, ra.puntuacion, e.nombre, e.acronimo, e.edicion, ra.comentarios
    ORDER BY art.titulo

    */
}