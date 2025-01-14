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

class ReportesController extends Controller
{
    public function __construct()
    {
        //REVISAR LOS MIDDLEWARE PORQUE NO JALA LA AUTENTICACIÓN
        $this->middleware('can:reportes.index')->only('index');
        $this->middleware('can:reportes.create')->only('crearReporteArticulo', 'crearReporteEvento');
        $this->middleware('can:reportes.read')->only('verReporte', 'verReporteEvento');
        $this->middleware('can:reportes.download')->only('descargarReporte');
        //$this->middleware('can:reportes.update')->only('actualizarReporte');
    }

    public function index($eventoId, $id)
    {
        $usuario = $id;
        $usuario = auth()->user();//id del usuario
        //dd($usuario);

        $articulos = articulos::where('evento_id', $eventoId)
            ->whereIn('estado', ['Aceptado', 'Rechazado'])
            ->whereHas('autores', function ($query) use ($usuario) {
                $query->where('usuario_id', $usuario->id)
                    ->where('correspondencia', true);
            })
            ->with('area')
            ->get();
        //dd($articulos);

        $hayAceptados = $articulos->contains('estado', 'Aceptado');
        //dd($hayAceptados);

        $Areas = areas::select('nombre', 'id')->get();
        //dd($Areas);

        return view('Reportes.index', compact('articulos', 'Areas', 'hayAceptados'));
    }

    public function crearReporteArticulo($eventoId, $articuloId)
    {
        $usuario = auth()->user();
        //dd($usuario);

        try {
            //OBTIENE LOS ARTÍCULOS QUE TENGAN ESTADO ACEPTADO O RECHAZADO - sólo de estos se generará reporte
            $cumpleEstado = articulos::with('area', 'evento')
                ->where('id', $articuloId)
                ->whereIn('estado', ['Aceptado', 'Rechazado'])
                ->firstOrFail();
            //dd($cumpleEstado);

            //VALIDACIÓN DE QUE EL AUTOR SEA EL DE CORRESPONDENCIA - sólo este podrá generar reporte del artículo
            $esAutorCorrespondencia = articulosAutores::where('articulo_id', $cumpleEstado->id)
                ->where('usuario_id', $usuario->id)
                ->where('correspondencia', true)
                ->exists();
            //dd($esAutorCorrespondencia);
            if (!$esAutorCorrespondencia) {//PROBABLEMENTE SEA INNECESARIA
                abort(403, 'No tienes permiso para generar este reporte.');
            }

            //CONSULTA DE DATOS PARA EL REPORTE EXCEPTO PROMEDIO DE SIMILITUD, PUNTUACIÓN MÁXIMA Y RÚBRICA (parameter.json)
            $articulo = articulos::query()->select([
                'eventos.logo',
                'articulos.titulo',
                DB::raw("autores_autores.nombres AS autor"),
                'articulos_autores.email AS correo',
                'areas.nombre AS area',
                'articulos.estado',
                DB::raw("
                        ROUND(
                            (COALESCE(ra.puntuacion, 0) +
                            COALESCE(ra2.puntuacion, 0) +
                            COALESCE(ra3.puntuacion, 0)) /
                            NULLIF(
                                (CASE WHEN ra.puntuacion IS NOT NULL THEN 1 ELSE 0 END) +
                                (CASE WHEN ra2.puntuacion IS NOT NULL THEN 1 ELSE 0 END) +
                                (CASE WHEN ra3.puntuacion IS NOT NULL THEN 1 ELSE 0 END), 0
                            ), 2
                        ) AS puntuacion
                    "),
                'eventos.nombre AS evento',
                'eventos.acronimo',
                'eventos.edicion',
                'ra.comentarios AS comentarios1',
                'ra2.comentarios AS comentarios2',
                'ra3.comentarios AS comentarios3',
            ])
                ->join('eventos', 'eventos.id', '=', 'articulos.evento_id')
                ->join('articulos_autores', function ($join) {
                    $join->on('articulos_autores.articulo_id', '=', 'articulos.id')
                        ->where('articulos_autores.correspondencia', true);
                })
                ->join('usuarios', 'usuarios.id', '=', 'articulos_autores.usuario_id')
                ->join('areas', 'areas.id', '=', 'articulos.area_id')
                ->leftJoinSub(
                    DB::table('articulos_autores as aa2')
                        ->select([
                            'aa2.articulo_id',
                            DB::raw("STRING_AGG(CONCAT(u2.nombre, ' ', u2.ap_paterno, ' ', u2.ap_materno), ', ' ORDER BY aa2.orden) AS nombres")
                        ])
                        ->join('usuarios as u2', 'u2.id', '=', 'aa2.usuario_id')
                        ->groupBy('aa2.articulo_id'),
                    'autores_autores',
                    'autores_autores.articulo_id',
                    '=',
                    'articulos.id'
                )
                ->leftJoin('revisores_articulos as ra', function ($join) {
                    $join->on('ra.articulo_id', '=', 'articulos.id')->where('ra.orden', '=', 1);
                })
                ->leftJoin('revisores_articulos as ra2', function ($join) {
                    $join->on('ra2.articulo_id', '=', 'articulos.id')->where('ra2.orden', '=', 2);
                })
                ->leftJoin('revisores_articulos as ra3', function ($join) {
                    $join->on('ra3.articulo_id', '=', 'articulos.id')->where('ra3.orden', '=', 3);
                })
                ->where('articulos.id', $articuloId)
                ->groupBy([
                    'eventos.logo',
                    'articulos.titulo',
                    'autores_autores.nombres',
                    'articulos_autores.email',
                    'areas.nombre',
                    'articulos.estado',
                    'eventos.nombre',
                    'ra.puntuacion',
                    'ra2.puntuacion',
                    'ra3.puntuacion',
                    'eventos.acronimo',
                    'eventos.edicion',
                    'ra.comentarios',
                    'ra2.comentarios',
                    'ra3.comentarios',
                ])->first();
            //dd($articulo);

            $similitudAvg = DB::table('revisores_articulos')
                ->where('articulo_id', $articuloId)
                ->selectRaw("ROUND(AVG(CAST(REPLACE(similitud, '%', '') AS NUMERIC)), 2) AS promedio")
                ->value('promedio');
            //dd($similitudAvg);

            $parametrosFile = 'public/EventImgs/' . $articulo->acronimo . $articulo->edicion . '/' . $articulo->acronimo . $articulo->edicion . 'parameter.json';
            //dd($parametrosFile);
            if (Storage::exists($parametrosFile)) {
                $jsonData = Storage::get($parametrosFile);
                $parametros = json_decode($jsonData, true);
                $maxToApprove = $parametros['MaxToApprove'];
                $questions = $parametros['Questions'];
                $optionAns = $parametros['OptionAnswers'];
                //dd($questions, $optionAns);
            }

            //FECHA DE CREACIÓN DEL REPORTE
            $fecha = Carbon::now()->translatedFormat('j / F / Y');
            //dd($fecha);

            //VERIFICAR SI YA EXISTE UN REPORTE DE ESTE ARTICULO. EN CASO DE QUE SÍ, LO ELIMINA (REEMPLAZO) BDD
            /*$reporteExistente = Reportes::where('articulo_id', $articuloId)->first();
            if ($reporteExistente) {
                $this->eliminarReporteExistente($articuloId, null);
            }*/
            //dd($reporteExistente);

            $storePath = storage_path("app/public/EventImgs/{$articulo->acronimo}{$articulo->edicion}/reportesAutor");
            if (!file_exists($storePath)) {
                mkdir($storePath, 0755, true);
            }
            //dd($storePath);

            //$nombreReporte = "Reporte del artículo_{$articulo->titulo} ({$articulo->acronimo}-{$articulo->edicion})_" . Carbon::now('America/Mexico_City')->format('d-m-Y_H-i');
            $nombreReporte = "Reporte del artículo_{$articulo->titulo} ({$articulo->acronimo}-{$articulo->edicion})";
            //dd($nombreReporte);
            $outputPath = storage_path("/app/public/EventImgs/{$articulo->acronimo}{$articulo->edicion}/reportesAutor/{$nombreReporte}.pdf");
            if (file_exists($outputPath)) {
                // Si el archivo existe, eliminarlo
                unlink($outputPath);
            }
            //dd($outputPath);

            //////////////////////////////////////////////////////////////////////////////////////////////////
            //////////////////////////////////////////////////////////////////////////////////////////////////
            //////////////////////////////////////////////////////////////////////////////////////////////////
            
            $logoPath = storage_path("/app/public/EventImgs/{$articulo->acronimo}{$articulo->edicion}/logo/{$articulo->logo}");
            $tecNM = storage_path("/app/public/EventImgs/TecNMpng.png");
            $ittol = storage_path("/app/public/EventImgs/logo-instituto-tecnologico-de-toluca.png");
            $pdf = new class ($logoPath, $tecNM, $ittol, $fecha) extends TCPDF {
                protected $logoPath;
                protected $tecNM;
                protected $ittol;
                protected $fecha;
                public function __construct($logoPath, $tecNM, $ittol, $fecha)
                {
                    parent::__construct('P', 'mm', 'Letter', true, 'UTF-8', false);
                    $this->logoPath = $logoPath;
                    $this->tecNM = $tecNM;
                    $this->ittol = $ittol;
                    $this->fecha = $fecha;
                }
                public function Header()
                {
                    //ANCHO TOTAL DE LA HOJA = 210.00014444444
                    //ALTO TOTAL DE LA HOJA = 297.00008333333
                    $maxWidth = 25;
                    $this->setFont('helvetica', 'B', 9);
                    $this->Cell(0, 5, $this->fecha, 0, 0, 'R');
                    $this->Image($this->logoPath, 20, 5, $maxWidth, 0, '', '', true);
                    $this->Image($this->tecNM, 85, 10, $maxWidth + 15, 0, '', '', true);
                    $this->Image($this->ittol, 166, 8, $maxWidth - 5, 0, '', '', true);
                    $this->Ln(20);
                }
                public function Footer()
                {
                    $this->SetY(-15);
                    $this->SetFont('helvetica', 'I', 8);
                    $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
                }
            };
            
            // Configuración básica del documento
            $pdf->SetCreator('SGEA');
            $pdf->SetAuthor($articulo->nombre_evento);
            $pdf->SetTitle('Reporte de: ' . $articulo->titulo);
            $pdf->SetSubject('Reporte para autor');
            $pdf->SetMargins(20, 10, 20);//left, top, right
            $pdf->SetAutoPageBreak(true, 20);

            $pdf->AddPage();

            //Image(x,y,w,h,type,link) Se miden en milímetros
            //LETTER (216mmX279mm) A4 (210mmX297mm)
            //Usamos tamaño carta
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 55, 'REPORTE DEL ARTÍCULO:', 0, 0, 'C');
            $pdf->Ln(35);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 0, $articulo->titulo, 0, 0, 'L');

            $pdf->Ln(15);
            $pdf->setFont('helvetica', '', 10);
            $html1 = '<b>Autor(es): </b>' . $articulo->autor;
            $pdf->writeHTML($html1, true, false, true, false, 'J');

            $pdf->Ln(5);
            $pdf->setFont('helvetica', '', 10);
            $html2 = '<b>Correo de correspondencia: </b>' . $articulo->correo;
            $pdf->writeHTML($html2, true, false, true, false, 'J');

            $pdf->Ln(5);
            $pdf->setFont('helvetica', '', 10);
            $html3 = '<b>Área en la que participa el artículo: </b>' . $articulo->area;
            $pdf->writeHTML($html3, true, false, true, false, 'J');

            $pdf->Ln(5);
            $pdf->setFont('helvetica', '', 10);
            $html4 = '<b>Estado del artículo: </b>' . $articulo->estado;
            $pdf->writeHTML($html4, true, false, true, false, 'J');

            $html5 = '<b>Puntuación final: </b>' . $articulo->puntuacion . '/' . $maxToApprove . '';
            $pdf->writeHTMLCell(50, 10, 135, 89, $html5);

            $pdf->Ln(8);
            $pdf->setFont('helvetica', '', 10);
            $html6 = '<b>Evento en el que participa: </b>' . $articulo->evento . ' (' . $articulo->acronimo . '-' . $articulo->edicion . ')';
            $pdf->writeHTML($html6, true, false, true, false, 'J');

            $pdf->Ln(8);
            $pdf->setFont('helvetica', '', 10);
            $h = '<b>Promedio de similitud: </b>' . $similitudAvg . ' %';
            $pdf->writeHTML($h, true, false, true, false, 'J');

            $pdf->Ln(12);
            $pdf->setFont('helvetica', '', 10);
            $html7 = '<b>Comentarios: </b><br><br><b>Revisor 1: </b>' . $articulo->comentarios1 . '.
                <br><br><br><br><b>Revisor 2: </b>' . $articulo->comentarios2 . '.
                <br><br><br><br><b>Revisor 3: </b>' . $articulo->comentarios3 . '.';
            $pdf->writeHTML($html7, true, false, true, false, 'J');

            $pdf->setFont('helvetica', 'BU', 10);
            $pdf->Cell(0, 30, 'RÚBRICA', 0, 0, 'C');

            $pdf->Ln(20);
            $pdf->setFont('helvetica', '', 10);
            // Crear una tabla en el PDF
            $html = '<table border="1" cellpadding="4">';
            // Cabecera de la tabla (opcional, puedes omitirla si no deseas encabezado)
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="width: 100%;"><b>Pregunta</b></th>';
            $html .= '</tr>';
            $html .= '</thead>';
            // Cuerpo de la tabla (solo preguntas)
            $html .= '<tbody>';
            foreach ($questions as $question) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($question) . '</td>'; // Texto de la pregunta
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';

            // Escribir la tabla en el PDF
            $pdf->writeHTML($html, true, false, true, false, '');

            $pdf->Output($nombreReporte, 'I');

            return redirect()->back()->with('success', 'El reporte se generó correctamente.');
        } catch (\Exception $e) {
            //dd($e);
            return redirect()->back()->with('error', 'Hubo un problema al generar el reporte. Inténtelo nuevamente.');
        }
    }

    public function crearReporteEvento($eventoId)
    {
        $evento = eventos::findOrFail($eventoId);
        //dd($evento);
        $usuario = auth()->user();
        //dd($usuario);

        try {
            $verificaRol = $usuario->hasAnyRole(['Administrador', 'Comite']);
            if (!$verificaRol) {
                abort(403, 'No tienes permisos para generar este reporte.');
            }

            $usuariosAutor = DB::table('usuarios')
                ->join('model_has_roles', 'usuarios.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'Autor')
                ->join('articulos_autores', 'usuarios.id', '=', 'articulos_autores.usuario_id')
                ->where('articulos_autores.evento_id', $eventoId)
                ->select(
                    'usuarios.id',
                    DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.ap_paterno, ' ', usuarios.ap_materno) as nombre"),
                    'usuarios.email as correo',
                    DB::raw('COUNT(articulos_autores.articulo_id) as articulos'),
                    DB::raw('MAX(articulos_autores.institucion) as institucion')
                )
                ->groupBy('usuarios.id', 'usuarios.nombre', 'usuarios.ap_paterno', 'usuarios.ap_materno', 'usuarios.email')
                ->orderBy('nombre')
                ->get();
            //dd($usuariosAutor);

            $revisoresDet = DB::table('usuarios')
                ->join('model_has_roles', 'usuarios.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'Revisor')
                ->join('revisores_articulos', 'usuarios.id', '=', 'revisores_articulos.usuario_id')
                ->where('revisores_articulos.evento_id', $eventoId)
                ->select(
                    'usuarios.id',
                    DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.ap_paterno, ' ', usuarios.ap_materno) as nombre"),
                    'usuarios.email as correo',
                    DB::raw('COUNT(revisores_articulos.articulo_id) as articulos')
                )
                ->groupBy('usuarios.id', 'usuarios.nombre', 'usuarios.ap_paterno', 'usuarios.ap_materno', 'usuarios.email')
                ->orderBy('nombre')
                ->get();
            //dd($revisoresDet);

            $comite = DB::table('usuarios')
            ->join('model_has_roles', 'usuarios.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Comite')
            ->select(
                'usuarios.id',
                DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.ap_paterno, ' ', usuarios.ap_materno) as nombre"),
                'usuarios.email as correo',
                'usuarios.telefono as telefono'
            )
            ->groupBy('usuarios.id', 'usuarios.nombre', 'usuarios.ap_paterno', 'usuarios.ap_materno', 'usuarios.email', 'usuarios.telefono')
            ->orderBy('nombre')
            ->get();
            //dd($comite);

            //VERIFICA SI YA EXISTE UN REPORTE DEL EVENTO - Sí: lo elimina/reemplaza; No: continúa generando el reporte
            $reporteExistente = Reportes::where('evento_id', $eventoId)->first();
            //dd($reporteExistente);
            if ($reporteExistente) {
                $this->eliminarReporteExistente(null, $eventoId);
            }

            //CONSULTAS PARA EL REPORTE
            //DATOS DEL EVENTO
            $evento = eventos::where('id', $eventoId)
                ->select('logo', 'nombre', 'acronimo', 'fecha_inicio', 'fecha_fin', 'edicion', 'estado')
                ->first();
            //dd($evento);

            //TÍTULO, ESTADO, AREA Y AUTORES DE CADA ARTÍCULO ORDENADOS POR AREA
            $articulos = articulos::join('articulos_autores as aa', 'articulos.id', '=', 'aa.articulo_id')
                ->join('usuarios as u', 'aa.usuario_id', '=', 'u.id')
                ->join('areas as area', 'articulos.area_id', '=', 'area.id')
                ->select('articulos.titulo', 'articulos.estado', 'area.nombre')
                ->selectRaw("STRING_AGG(DISTINCT CONCAT(u.nombre, ' ', u.ap_paterno, ' ', u.ap_materno), ', ') AS AUTORES")
                ->where('articulos.evento_id', $eventoId)  // Filtrando por evento_id
                ->groupBy('articulos.id', 'area.nombre')
                ->orderBy('area.nombre')
                ->get();
            //dd($articulos);

            //MÍNIMO Y MÁXIMO DE CALIFICACIÓN DE TODOS LOS ARTÍCULOS
            $minPuntuacion = revisoresArticulos::where('evento_id', $eventoId)->min('puntuacion');
            $maxPuntuacion = revisoresArticulos::where('evento_id', $eventoId)->max('puntuacion');
            //dd($minPuntuacion, $maxPuntuacion);

            //TÍTULO Y CORREO DE CORRESPONDENCIA
            $artCorrespondencia = articulos::select('articulos.titulo', 'articulos_autores.email AS correo_correspondencia')
                ->join('articulos_autores', 'articulos.id', '=', 'articulos_autores.articulo_id')
                ->where('articulos_autores.correspondencia', true)
                ->get();
            //dd($articulosCorrespondencia);

            //CANTIDAD DE ARTÍCULOS POR ÁREA
            $artArea = Areas::select('areas.nombre as area', DB::raw('COUNT(articulos.id) as cantidad_articulos'))
                ->leftJoin('articulos', 'areas.id', '=', 'articulos.area_id') // Join por id
                ->groupBy('areas.id', 'areas.nombre') // Agrupar por ID de área para evitar problemas de duplicados
                ->get();
            //dd($artArea);

            //TOTAL DE REVISORES
            $count = DB::table('revisores_articulos')->distinct('usuario_id')->count('usuario_id');
            //dd($count);

            //CANTIDAD DE ARTÍCULOS POR ESTADO
            $artEstado = articulos::where('evento_id', $eventoId)
                ->selectRaw('estado, COUNT(id) as cantidad_articulos')
                ->groupBy('estado')
                ->get();
            //dd($artEstado);

            //TOTAL DE ARTÍCULOS DEL EVENTO
            $totalArt = articulos::where('evento_id', $eventoId)->count();
            //dd($totalArt);

            //TÍTULO Y PROMEDIO DE PUNTUACIÓN DE CADA ARTÍCULO
            $artAvg = articulos::select('articulos.titulo', DB::raw('TRUNC(AVG(revisores_articulos.puntuacion), 2) as promedio_puntuacion'))
                ->join('revisores_articulos', 'articulos.id', '=', 'revisores_articulos.articulo_id')
                ->where('articulos.evento_id', $eventoId)
                ->groupBy('articulos.id', 'articulos.titulo')
                ->orderBy('promedio_puntuacion', 'desc')
                ->get();
            //dd($artAvg);

            //CANTIDAD DE AUTORES POR INSTITUCION
            $autoresInst = articulosAutores::select('institucion', \DB::raw('COUNT(institucion) as total'))
                ->groupBy('institucion')
                ->orderBy('institucion')
                ->get();
            //dd($autoresInst);

            //QUIENES FUERON LOS REVISORES DE CADA ARTICULO
            $artRevisores = DB::table('articulos as art')
                ->select('art.titulo', 'art.estado', DB::raw("STRING_AGG(DISTINCT CONCAT(u.nombre, ' ', u.ap_paterno, ' ', u.ap_materno), ', ') AS revisores"))
                ->join('revisores_articulos as ra', 'art.id', '=', 'ra.articulo_id')
                ->join('usuarios as u', 'ra.usuario_id', '=', 'u.id')
                ->where('art.evento_id', $eventoId) // Condición por evento
                ->groupBy('art.id', 'art.titulo')
                ->get();
            //dd($artRevisores);

            //PROMEDIO DE SIMILITUD DE CADA ARTÍCULO
            $artSimilitud = articulos::select('articulos.titulo', DB::raw('TRUNC(AVG(CAST(REPLACE(revisores_articulos.similitud, \'%\', \'\') AS DECIMAL)), 2) as promedio_similitud'))
                ->join('revisores_articulos', 'articulos.id', '=', 'revisores_articulos.articulo_id')
                ->where('articulos.evento_id', $eventoId)
                ->groupBy('articulos.id', 'articulos.titulo')
                ->orderBy('promedio_similitud', 'asc') // Ordenar de menor a mayor
                ->get();
            //dd($artSimilitud);

            //FECHA DE CREACIÓN DEL REPORTE
            $fecha = Carbon::now()->translatedFormat('j / F / Y');
            //dd($fecha);

            //NOMBRE DEL REPORTE
            $fileName = 'Reporte general del evento_' . $evento->nombre . ' (' . $evento->acronimo . '-' . $evento->edicion . ')';
            //dd($fileName);

            $folderPath = storage_path("app/public/EventImgs/{$evento->acronimo}{$evento->edicion}/reporteEvento");
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);  // Crear la carpeta con permisos adecuados
            }

            $outputPath = storage_path("/app/public/EventImgs/{$evento->acronimo}{$evento->edicion}/reporteEvento/{$fileName}.pdf");
            if (file_exists($outputPath)) {
                // Si el archivo existe, eliminarlo
                unlink($outputPath);
            }
            $inicio = Carbon::parse($evento->fecha_inicio)->locale('es')->isoFormat('dddd DD [de] MMMM [de] YYYY');
            $fin = Carbon::parse($evento->fecha_fin)->locale('es')->isoFormat('dddd DD [de] MMMM [de] YYYY');

            $logoPath = storage_path("/app/public/EventImgs/{$evento->acronimo}{$evento->edicion}/logo/{$evento->logo}");
            $tecNM = storage_path("/app/public/EventImgs/TecNMpng.png");
            $ittol = storage_path("/app/public/EventImgs/logo-instituto-tecnologico-de-toluca.png");
            $pdf = new class ($logoPath, $tecNM, $ittol, $evento, $fecha) extends TCPDF {
                protected $logoPath;
                protected $tecNM;
                protected $ittol;
                protected $evento;
                protected $fecha;
                public function __construct($logoPath, $tecNM, $ittol, $evento, $fecha)
                {
                    parent::__construct('P', 'mm', 'Letter', true, 'UTF-8', false);
                    $this->logoPath = $logoPath;
                    $this->tecNM = $tecNM;
                    $this->ittol = $ittol;
                    $this->evento = $evento;
                    $this->fecha = $fecha;
                }
                public function Header()
                {
                    //ANCHO TOTAL DE LA HOJA = 210.00014444444
                    //ALTO TOTAL DE LA HOJA = 297.00008333333
                    $maxWidth = 25;
                    $this->setFont('helvetica', 'B', 9);
                    $this->Cell(0, 5, $this->fecha, 0, 0, 'R');
                    $this->Image($this->logoPath, 20, 5, $maxWidth, 0, '', '', true);
                    $this->Image($this->tecNM, 85, 10, $maxWidth + 15, 0, '', '', true);
                    $this->Image($this->ittol, 166, 8, $maxWidth - 5, 0, '', '', true);
                    $this->Ln(20);
                    $this->setFont('helvetica', 'B', 14);
                    $this->Cell(0, 0, 'Reporte general del evento:', 0, 0, 'C');
                    $this->Ln(7);
                    $this->Cell(0, 0, $this->evento->nombre . ' (' . $this->evento->acronimo . '-' . $this->evento->edicion . ')', 0, 0, 'L');





                    /*// Obtener el ancho y alto total de la página
                    $pageWidth = $this->getPageWidth();  // 210mm
                    $pageHeight = $this->getPageHeight(); // 297mm
                    // Obtener los márgenes
                    $margins = $this->getMargins();
                    $leftMargin = $margins['left'];  // 20mm
                    $topMargin = $margins['top'];    // 50mm
                    $rightMargin = $margins['right']; // 20mm
                    // Dibujar los márgenes (esto es opcional si solo quieres visualizar los márgenes)
                    $this->SetDrawColor(255, 0, 0); // Color de borde (rojo en este caso)
                    $this->SetLineWidth(0.5); // Grosor de línea
                    // Dibujar el margen izquierdo, superior, derecho e inferior
                    // Para el margen inferior, asumimos que se quiere dibujar hasta el borde inferior de la página.
                    $this->Rect($leftMargin, $topMargin, $pageWidth - $leftMargin - $rightMargin, $pageHeight - $topMargin);*/
                }
                public function Footer()
                {
                    $this->SetY(-15);
                    $this->SetFont('helvetica', 'I', 8);
                    $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
                }
            };
            $pdf->SetCreator('SGEA');
            $pdf->SetAuthor($evento->nombre);
            $pdf->SetTitle('Reporte general del evento: ' . $evento->nombre);
            $pdf->SetSubject('Reporte general de eventos');
            $pdf->SetMargins(20, 50, 20);//left, top, right
            $pdf->SetAutoPageBreak(true, 20);

            $pdf->AddPage();

            $pdf->setFont('helvetica', '', 10);
            $html = '<b>Periodo del evento:</b> ' . $inicio . ' al ' . $fin;
            $pdf->writeHTML($html, true, false, true, false, 'L');

            $pdf->Ln(5);
            $pdf->setFont('helvetica', '', 10);
            $html1 = '<b>Estado:</b> ' . $evento->estado;
            $pdf->writeHTML($html1, true, false, true, false, 'L');

            $pdf->Ln(5);
            $tableWidth = 80;
            $leftMargin = ($pdf->GetPageWidth() - $tableWidth) / 2;
            $pdf->SetX($leftMargin);
            $tbl = <<<EOD
            <table border="1" cellpadding="2" nobr="true">
                <tr>
                    <th align="center">Total de artículos recibidos</th>
                    <th width="40" align="center">$totalArt</th>
                </tr>
            </table>
            EOD;
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Cantidad de artículos por área', 0, 0, 'C');
            $pdf->Ln(6);
            $pdf->setFont('helvetica', '', 10);
            $tbl = '<table border="1" cellpadding="2" nobr="true">';
            $tbl .= '<tr>
                        <th align="center" width="150"><b>Área</b></th>
                        <th align="center" width="100"><b>Artículos recibidos</b></th>
                     </tr>';
            foreach ($artArea as $item) {
                $tbl .= '<tr>
                        <td align="center">' . $item->area . '</td>
                        <td align="center">' . $item->cantidad_articulos . '</td>
                    </tr>';
            }
            $tbl .= '</table>';
            $pdf->SetX(60, true);
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->Ln(6);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Cantidad de artículos por estado del artículo', 0, 0, 'C');
            $pdf->Ln(6);
            $pdf->setFont('helvetica', '', 10);
            $tbl = '<table border="1" cellpadding="2" nobr="true">';
            $tbl .= '<tr>
                        <th align="center"><b>Estado del artículo</b></th>
                        <th align="center"><b>Cantidad de artículos</b></th>
                     </tr>';
            foreach ($artEstado as $item) {
                $tbl .= '<tr>
                        <td align="center">' . $item->estado . '</td>
                        <td align="center">' . $item->cantidad_articulos . '</td>
                    </tr>';
            }
            $tbl .= '</table>';
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->Ln(6);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Calificación máxima y mínima obtenida en el evento', 0, 0, 'C');
            $pdf->Ln(7);
            $pdf->setFont('helvetica', '', 10);
            $tbl = <<<EOD
            <table border="1" cellpadding="2" nobr="true">
                <tr>
                    <td align="center" width="120"><b>Calificación mínima</b></td>
                    <td align="center" width="60">$minPuntuacion</td>
                </tr>
                <tr>
                    <td align="center"><b>Calificación máxima</b></td>
                    <td align="center">$maxPuntuacion</td>
                </tr>
            </table>
            EOD;
            $pdf->setX(70, true);
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->Ln(5);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Revisores', 0, 0, 'C');
            $pdf->Ln(6);
            $pdf->setFont('helvetica', '', 10);
            $tableWidth = 80;
            $leftMargin = ($pdf->GetPageWidth() - $tableWidth) / 2;
            $pdf->SetX($leftMargin);
            $tbl = <<<EOD
            <table border="1" cellpadding="2" nobr="true">
                <tr>
                    <th align="center"><b>Total de revisores</b></th>
                    <th width="40" align="center">$count</th>
                </tr>
            </table>
            EOD;
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->Ln(50);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Revisores por artículo', 0, 0, 'C');
            $pdf->Ln(6);
            $pdf->setFont('helvetica', '', 10);
            $tableWidth = $pdf->GetPageWidth();
            $tbl = '<table border="1" cellpadding="2" nobr="true">';
            $tbl .= '<tr>
                        <th width="200" align="center"><b>Artículo</b></th>
                        <th width="80" align="center"><b>Estado</b></th>
                        <th width="200" align="center"><b>Revisores</b></th>
                     </tr>';
            foreach ($artRevisores as $item) {
                $tbl .= '<tr>
                        <td align="center">' . $item->titulo . '</td>
                        <td align="center">' . $item->estado . '</td>
                        <td align="center">' . $item->revisores . '</td>
                    </tr>';
            }
            $tbl .= '</table>';
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->Ln(10);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Autores por institución', 0, 0, 'C');
            $pdf->Ln(6);
            $pdf->setFont('helvetica', '', 10);
            $tbl = '<table border="1" cellpadding="2" nobr="true">';
            $tbl .= '<tr>
                        <th width="80%" align="center"><b>Institución</b></th>
                        <th width="20%" align="center"><b>Cantidad</b></th>
                     </tr>';
            foreach ($autoresInst as $item) {
                $tbl .= '<tr>
                        <td align="left">' . $item->institucion . '</td>
                        <td align="center">' . $item->total . '</td>
                    </tr>';
            }
            $tbl .= '</table>';
            $tableWidth = 500;
            //$pdf->setX(20,true);
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->Ln(10);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Detalles de artículos', 0, 0, 'C');
            $pdf->Ln(6);
            $pdf->setFont('helvetica', '', 8);
            $correspondenciaMap = $artCorrespondencia->pluck('correo_correspondencia', 'titulo')->toArray();
            $tbl = <<<EOD
            <table border="1" cellpadding="2" nobr="true">
                <thead>
                    <tr>
                        <th align="center" width="23%"><b>Título del artículo</b></th>    
                        <th align="center" width="15%"><b>Estado</b></th>
                        <th align="center" width="18%"><b>Área</b></th>
                        <th align="center" width="22%"><b>Autores</b></th>
                        <th align="center" width="22%"><b>Correo de correspondencia</b></th>
                    </tr>
                </thead>
                <tbody>
            EOD;
            foreach ($articulos as $item) {
                $correo = isset($correspondenciaMap[$item->titulo]) ? $correspondenciaMap[$item->titulo] : 'No disponible';

                $tbl .= <<<EOD
                    <tr>
                        <td align="left" width="23%">{$item->titulo}</td>
                        <td align="center" width="15%">{$item->estado}</td>
                        <td align="center" width="18%">{$item->nombre}</td>
                        <td align="center" width="22%">{$item->autores}</td>
                        <td align="center" width="22%">{$correo}</td>
                    </tr>
                EOD;
            }
            $tbl .= <<<EOD
                </tbody>
            </table>
            EOD;
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->Ln(25);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Artículos, puntuación obtenida final y promedio de porcentaje de similitud', 0, 0, 'C');
            $pdf->Ln(7);
            $pdf->setFont('helvetica', '', 10);
            $artSimilitudMap = $artSimilitud->keyBy('titulo');  //MAPEO POR
            $artAvgMap = $artAvg->keyBy('titulo');              //TÍTULO
            $articulosCombinados = $artSimilitud->map(function ($item) use ($artAvgMap) {   //Ambas variables combinadas basadas en similitud
                return [
                    'titulo' => $item->titulo,
                    'promedio_similitud' => $item->promedio_similitud,
                    'promedio_puntuacion' => $artAvgMap->get($item->titulo)->promedio_puntuacion ?? 'No disponible',
                ];
            })->sortBy('promedio_similitud');   //ORDEN POR SIMILITUD DE MENOR A MAYOR
            $tbl = <<<EOD
                <table border="1" cellpadding="2" nobr="true">
                    <thead>
                        <tr>
                            <th align="center" width="60%"><b>Título del artículo</b></th>
                            <th align="center" width="20%"><b>Promedio de puntuación</b></th>
                            <th align="center" width="20%"><b>Promedio de similitud</b></th>
                        </tr>
                    </thead>
                    <tbody>
                EOD;
            foreach ($articulosCombinados as $articulo) {
                $tbl .= <<<EOD
                        <tr>
                            <td align="left" width="60%">{$articulo['titulo']}</td>
                            <td align="center" width="20%">{$articulo['promedio_puntuacion']} %</td>
                            <td align="center" width="20%">{$articulo['promedio_similitud']}</td>
                        </tr>
                EOD;
            }
            $tbl .= <<<EOD
                    </tbody>
                </table>
                EOD;
            $pdf->writeHTML($tbl, true, false, false, false, '');

            $pdf->Ln(15);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Detalles de autores', 0, 0, 'C');
            $pdf->Ln(7);
            $pdf->setFont('helvetica', '', 8);
            $tbl = <<<EOD
                <table border="1" cellpadding="2" nobr="true">
                    <thead>
                        <tr>
                            <th align="center" width="30%"><b>Autor</b></th>
                            <th align="center" width="30%"><b>Institución</b></th>
                            <th align="center" width="25%"><b>Correo</b></th>
                            <th align="center" width="15%"><b>*Artículos</b></th>
                        </tr>
                    </thead>
                    <tbody>
                EOD;
            foreach ($usuariosAutor as $item) {
                $tbl .= <<<EOD
                        <tr>
                            <td align="left" width="30%">$item->nombre</td>
                            <td align="left" width="30%">$item->institucion</td>
                            <td align="center" width="25%">$item->correo</td>
                            <td align="center" width="15%">$item->articulos</td>
                        </tr>
                EOD;
            }
            $tbl .= <<<EOD
                    </tbody>
                </table>
                EOD;
            $pdf->writeHTML($tbl, false, false, false, false, '');
            $pdf->Ln(1);
            $pdf->setFont('helvetica', '', 6);
            $pdf->Cell(0, 0, '*Cantidad de artículos del evento en los que participa', 0, 0, 'L');

            $pdf->Ln(15);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Detalles de revisores', 0, 0, 'C');
            $pdf->Ln(7);
            $pdf->setFont('helvetica', '', 8);
            $tbl = <<<EOD
                <table border="1" cellpadding="2" nobr="true">
                    <thead>
                        <tr>
                            <th align="center" width="170"><b>Revisor</b></th>
                            <th align="center" width="160"><b>Correo</b></th>
                            <th align="center" width="70"><b>*Artículos</b></th>
                        </tr>
                    </thead>
                    <tbody>
                EOD;
            foreach ($revisoresDet as $item) {
                $tbl .= <<<EOD
                        <tr>
                            <td align="left" width="170">$item->nombre</td>
                            <td align="center" width="160">$item->correo</td>
                            <td align="center" width="70">$item->articulos</td>
                        </tr>
                EOD;
            }
            $tbl .= <<<EOD
                    </tbody>
                </table>
                EOD;
            $pdf->setX(34);
            $pdf->writeHTML($tbl, false, false, false, false, '');
            $pdf->Ln(1);
            $pdf->setFont('helvetica', '', 6);
            $pdf->setX(34);
            $pdf->Cell(0, 0, '*Cantidad de artículos del evento en los que es revisor', 0, 0, 'L');

            $pdf->Ln(15);
            $pdf->setFont('helvetica', 'B', 12);
            $pdf->Cell(0, 0, 'Comité organizador', 0, 0, 'C');
            $pdf->Ln(7);
            $pdf->setFont('helvetica', '', 8);
            $tbl = <<<EOD
                <table border="1" cellpadding="2" nobr="true">
                    <thead>
                        <tr>
                            <th align="center" width="170"><b>Nombre</b></th>
                            <th align="center" width="160"><b>Correo</b></th>
                            <th align="center" width="70"><b>Teléfono</b></th>
                        </tr>
                    </thead>
                    <tbody>
                EOD;
            foreach ($comite as $item) {
                $tbl .= <<<EOD
                        <tr>
                            <td align="left" width="170">$item->nombre</td>
                            <td align="center" width="160">$item->correo</td>
                            <td align="center" width="70">$item->telefono</td>
                        </tr>
                EOD;
            }
            $tbl .= <<<EOD
                    </tbody>
                </table>
                EOD;
            $pdf->setX(34);
            $pdf->writeHTML($tbl, false, false, false, false, '');
            $pdf->Ln(1);
            $pdf->setFont('helvetica', '', 6);
            $pdf->setX(34);
            $pdf->Cell(0, 0, '*Cantidad de artículos del evento en los que es revisor', 0, 0, 'L');

            $pdf->Output($outputPath, 'I'); // Guarda el archivo en el servidor
            return redirect()->back()->with('success', 'El reporte se generó correctamente.');
        } catch (\Exception $e) {
            dd($e);
            return redirect()->back()->with('error', 'Hubo un problema al generar el reporte. Inténtelo nuevamente.');
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