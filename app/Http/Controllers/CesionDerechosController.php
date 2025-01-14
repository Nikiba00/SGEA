<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\articulos;
use App\Models\usuarios;
use App\Models\articulosAutores;
use App\Models\eventos;
use DB;
use TCPDF;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Storage;
use Carbon\Carbon;

class CesionDerechosController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:cesionDerechos.download')->only('descargarCesionDerechos');
    }

    public function descargarCesionDerechos($eventoId, $articuloId)
    {
        // CONSULTA TITULO, EVENTO, ACRONIMO, LOGO, EDICION
        $articulo = articulos::select('articulos.titulo', 'eventos.nombre as nombre_evento', 'eventos.logo', 'eventos.acronimo', 'eventos.edicion')
            ->join('eventos', 'articulos.evento_id', '=', 'eventos.id')
            ->where('articulos.id', $articuloId)
            ->first();

        if (!$articulo) {
            return response()->json(['error' => 'Artículo no encontrado'], 404);
        }

        // CONSULTA PARA NOMBRES DE AUTORES POR APELLIDO
        $autores = DB::table('articulos as a')
            ->selectRaw('
        a.id AS articulo_id, 
        string_agg(CONCAT(u.ap_paterno, \' \', u.ap_materno, \' \', u.nombre), \',\' ORDER BY aa.orden) AS autores
    ')
            ->join('articulos_autores as aa', 'a.id', '=', 'aa.articulo_id')
            ->join('usuarios as u', 'aa.usuario_id', '=', 'u.id')
            ->where('a.id', $articuloId)
            ->groupBy('a.id')
            ->first();

        //CONSULTA DE NOMBRE COMPLETO AUTOR CORRESPONDENCIA
        $nombreCompleto = DB::table('articulos_autores as aa')
            ->join('usuarios as u', 'aa.usuario_id', '=', 'u.id')
            ->where('aa.articulo_id', $articuloId)
            ->where('aa.correspondencia', true)
            ->selectRaw("CONCAT(u.nombre, ' ', u.ap_paterno, ' ', u.ap_materno) as nombre_completo")
            ->value('nombre_completo');
        //dd($nombreCompleto);

        //CORREO DE CORRESPONDENCIA
        $correoCorrespondencia = articulosAutores::where('articulo_id', $articuloId)
            ->where('correspondencia', true)
            ->value('email');

        //FECHA DE CREACIÓN DEL ARCHIVO
        $fecha = Carbon::now()->translatedFormat('j \d\e F \d\e\l Y');

        $fileName = 'Cesión de derechos_' . $articulo->titulo;
        
        $folderPath = storage_path("app/public/EventImgs/{$articulo->acronimo}{$articulo->edicion}/cesion_derechos");
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);  // Crear la carpeta con permisos adecuados
        }

        $outputPath = storage_path("/app/public/EventImgs/{$articulo->acronimo}{$articulo->edicion}/cesion_derechos/{$fileName}.pdf");
        if (file_exists($outputPath)) {
            // Si el archivo existe, eliminarlo
            unlink($outputPath);
        }
        //$outputPath = storage_path("/app/public/archivos_generados/cesion_derechos_{$articulo->id}.pdf");
        // Crea una instancia de TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, 'A4', true, 'UTF-8', false);

        // Configuración básica del documento
        $pdf->SetCreator('SGEA');
        $pdf->SetAuthor($articulo->nombre_evento);
        $pdf->SetTitle('Cesión de Derechos: ' . $articulo->titulo);
        $pdf->SetSubject('Cesión de Derechos');
        $pdf->SetMargins(20, 10, 20);//left, top, right
        $pdf->SetAutoPageBreak(true, 20);
        
        $pdf->setPrintHeader(false);
        // Agrega una página
        $pdf->AddPage();

        // Agregar el logo de la base de datos (ubicación dinámica)
        $logoPath = storage_path("/app/public/EventImgs/{$articulo->acronimo}{$articulo->edicion}/logo/{$articulo->logo}");
        $tecNM = storage_path("/app/public/EventImgs/TecNMpng.png");
        $ittol = storage_path("/app/public/EventImgs/logo-instituto-tecnologico-de-toluca.png");

        $this->generarHeader($pdf, $logoPath, $tecNM, $ittol);
        
        //Image(x,y,w,h,type,link) Se miden en milímetros
        //LETTER (216mmX279mm) A4 (210mmX297mm)
        //Usamos tamaño carta
        $pdf->setFont('helvetica', 'B', 14);
        $pdf->Cell(0, 38, 'Derechos de autor', 0, 0, 'C');

        $pdf->Ln(25);
        $pdf->setFont('helvetica', '', 7);
        $textoMini = "Para garantizar la uniformidad de trato para todos los trabajos aceptados al {$articulo->acronimo}-{$articulo->edicion}, no se pueden sustituir otros formularios por este formulario, ni se puede cambiar la redacción del mismo. Este formulario está destinado a proteger los Derechos de Autor del material original presentado en el {$articulo->acronimo}-{$articulo->edicion} y debe acompañar a las obras publicadas por el {$articulo->acronimo}-{$articulo->edicion}. Lea atentamente y requisite el formulario, conservando una copia en resguardo.";
        $pdf->MultiCell(0, 7, $textoMini, 0, 'J', false, 1);

        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 0, 'TÍTULO PROPUESTO DE LA CONTRIBUCIÓN:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $articulo->titulo, 0, 0, 'L');

        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 0, 'NOMBRE COMPLETO DE LOS AUTORES:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $autores->autores, 0, 0, 'L');

        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 0, 'TÍTULO DE LA PUBLICACIÓN DE ' . $articulo->acronimo . ' ' . $articulo->edicion . ':', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $articulo->titulo, 0, 0, 'L');

        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'BU', 10);
        $pdf->Cell(0, 0, 'TRANSFERENCIA DE DERECHOS', 0, 1, 'C');

        $pdf->setFont('helvetica', '', 10);
        $html1 = 'El(La) suscrito(a) cede al <b>' . $articulo->nombre_evento . ' (' . $articulo->acronimo . '-' . $articulo->edicion . ')</b> todos los derechos 
        de autor que puedan existir en y para el trabajo anterior, y cualquier trabajo derivado revisado o ampliado presentado al '
            . $articulo->acronimo . '-' . $articulo->edicion . ' por el(la) suscrito(a) basado en la obra. El(La) que suscribe garantiza que la obra es 
        original y que es el(la) autor(a) de la misma; y en la medida en que el trabajo incorpore pasajes externos, figuras, datos de otro 
        material o de otros trabajos; el(la) autor(a) ha obtenido los permisos necesarios.';
        $pdf->writeHTML($html1, true, false, true, false, 'J');

        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'BU', 10);
        $pdf->Cell(0, 0, 'RESPONSABILIDADES DEL AUTOR', 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->setFont('helvetica', '', 10);
        $html2 = 'El ' . $articulo->acronimo . '-' . $articulo->edicion . ' distribuirá sus publicaciones técnicas en todo el mundo y quiere asegurarse 
        que el material enviado en sus publicaciones esté legalmente disponible para los lectores, respetando las siguientes premisas:
        <ol style="list-style-type: none;">
            <li>a) Los autores deben asegurarse que su trabajo cumpla con los requisitos de la política del ' . $articulo->acronimo . '-'
            . $articulo->edicion . ', incluidas las disposiciones que salvaguarden la originalidad, la autoría, las responsabilidades del autor, 
            evitando malas prácticas por parte de los autores.<br></li>
            <li>b) El(La) autor(a) no puede realizar cambios de autoría, incluidos, entre otros, cambios en la correspondencia del(los) 
            autor(es) o la secuencia de autores, después de la aceptación de un manuscrito.</li>
        </ol>';
        $pdf->writeHTML($html2, true, false, true, false, 'J');

        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'BU', 10);
        $pdf->Cell(0, 0, 'TÉRMINOS GENERALES', 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->setFont('helvetica', '', 10);
        $html3 = 'El ' . $articulo->acronimo . '-' . $articulo->edicion . ' distribuirá sus publicaciones técnicas en todo el mundo y quiere asegurarse 
        que el material enviado en sus publicaciones esté legalmente disponible para los lectores, respetando las siguientes premisas:
        <ol style="list-style-type: decimal;">
            <li>El(La) firmante declara que tiene el poder y la autoridad para hacer  ejectuar esta asginación.<br></li>
            <li>El(La) firmante se responsabiliza de mantener indemne al ' . $articulo->acronimo . '-' . $articulo->edicion . ' de cualquier daño o 
            gasto que pudiera ocasionarse en caso de incumplimiento de cualquiera de las garantías establecidas amteriormente.<br></li>
            <li>En caso de que el trabajo anterior no sea aceptado para ser publicado en el libro del '
            . $articulo->acronimo . '-' . $articulo->edicion . ', o sea retirado por el(los) autor(es) antes de su aceptación por parte del '
            . $articulo->acronimo . '-' . $articulo->edicion . ', la transferencia de derechos de autor anterior será nula y sin efecto, y todos los 
            materiales incorporados en el trabajo enviado al ' . $articulo->acronimo . '-' . $articulo->edicion . ' serán destruidos.<br></li>
            <li>Para trabajos de autoría conjunta, todos los coautores deben firmar, o en su defecto, uno de los autores deberá firmar como 
            agente autorizado por los demás.</li>
        </ol>';
        $pdf->writeHTML($html3, true, false, true, false, 'J');

        $pdf->AddPage();
        $this->generarHeader($pdf, $logoPath, $tecNM, $ittol);

        $pdf->Ln(25);
        $pdf->SetFont('helvetica', 'BU', 10);
        $pdf->Cell(0, 0, 'DERECHOS RETENIDOS/TÉRMINOS Y CONDICIONES', 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->setFont('helvetica', '', 10);
        $html4 = 'El ' . $articulo->acronimo . '-' . $articulo->edicion . ' distribuirá sus publicaciones técnicas en todo el mundo y quiere asegurarse 
        que el material enviado en sus publicaciones esté legalmente disponible para los lectores, respetando las siguientes premisas:
        <ol style="list-style-type: decimal;">
            <li>Los autores conservan todos los derechos de propiedad sobre cualquier proceso, procedimiento o artículo de fabricación 
            descrito en el trabajo.<br></li>
            <li>Los autores pueden reproducir o autorizar a otros a reproducir la obra, material extraído textualmente de la obra u obras 
            derivadas para uso personal del autor o para uso de la empresa, siempre que se indique la fuente y el aviso de derechos de autor 
            del ' . $articulo->acronimo . '-' . $articulo->edicion . '. Las copias no se deben utilizar de cualquier forma que implique el respaldo 
            del ' . $articulo->acronimo . '-' . $articulo->edicion . ' a un producto o servicio de cualquier empleador, y las copias en sí podrán ser 
            ofrecidas a la venta.<br></li>
            <li>Los autores podrán hacer una distribución limitada de la totalidad o de alguna parte de la obra antes de la publicación si 
            informan con anticipación al ' . $articulo->acronimo . '-' . $articulo->edicion . ' de la naturaleza y alcance de dicha distribución 
            limitada.<br></li>
            <li>Para todos los usuarios no contemplados en los numerales 2 y 3, los autores deberán solicitar permiso al Instituto Tecnológico 
            de Toluca para reproducir o autorizar la reproducción de la obra o material extraído textualmente de la obra, incluido el 
            contenido gráfico, tales como Figuras y Tablas.<br></li>
            <li>Aunque se permite a los autores reutilizar todo o algunas partes del trabajo en otros trabajos, esto no incluye la concesión 
            de solicitudes de terceros para la reimpresión, reedición u otros tipos de reutilización. El Instituto Tecnológico de Toluca debe 
            atender todas las solicitudes de terceros.</li>
        </ol>';
        $pdf->writeHTML($html4, true, false, true, false, 'J');

        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 0, 'INFORMACIÓN PARA LOS AUTORES', 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->setFont('helvetica', '', 10);
        $html5 = '<b><u>Propiedad de los derechos de autor del ' . $articulo->acronimo . '-' . $articulo->edicion . '</u></b><br><br>
        Es política formal del ' . $articulo->acronimo . '-' . $articulo->edicion . ' poseer los derechos de autor de todo el material sujeto a 
        derechos de autor en sus publicaciones técnicas y de las contribuciones individuales contenidas en ellas, con el fin de protger los 
        intereses del ' . $articulo->acronimo . '-' . $articulo->edicion . ', sus autores y sus empleadores y, al mismo tiempo, para facilitar la 
        reutilización adecuada de este material por otros.<br><br>
        <b><u>Política de reimpresión/republicación</u></b><br><br>
        El ' . $articulo->acronimo . '-' . $articulo->edicion . ' requiere el consentimiento del primer autor <b>o de correspondencia</b> como 
        condición para otorgar los derechos de reimpresión o republicación a otros o para permitir el uso de una obra con fines de promoción 
        o mercadeo.<br><br>
        Firmado por y en nombre del autor:';
        $pdf->writeHTML($html5, true, false, true, false, 'J');

        $pdf->Ln(2);
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $nombreCompleto, 0, 1, 'L');
        $pdf->Line(20, 228, 170, 228);

        $pdf->Ln(8);
        $pdf->setFont('helvetica', '', 10);
        $html6 = '<b>Autor/agente autorizado por los coautores<br><br>Email: </b>'.$correoCorrespondencia.'';
        $pdf->writeHTML($html6, true, false, true, false, 'J');
        
        $html7='<b>Fecha</b><br><br>'.$fecha.'';
        $pdf->writeHTMLCell(50, 10, 135, 235, $html7);
        $pdf->Line(30, 249, 80, 249);

        $pdf->Ln(20);
        $pdf->setFont('helvetica', '', 7);
        $textoMini2 = "Favor de requisitar completamente este formulario, firmarlo de forma autógrafa, enviando una copia digital al siguiente correo electrónico.";
        $pdf->MultiCell(0, 7, $textoMini2, 0, 'J', false, 1);

        /*
        I - Envía el archivo al navegador para mostrarlo
        D - Forzar la descarga del archivo
        F - Guarda archivo en el servidor (también abre la ventana de descarga del sistema)
        S - Devuelve archivo como una cadena
        E - Devuelve el archivo como una cadena base64
        */
        $pdf->Output($outputPath, 'F'); // Guarda el archivo en el servidor

        return response()->download($outputPath);
    }

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }
    
    public function destroy(string $id)
    {
        //
    }

    private function generarHeader($pdf, $logoPath, $tecNM, $ittol){
        $pdf->Image($logoPath, 28, 11, 20, 20); // Logo del evento
        $pdf->Image($tecNM, 82, 11, 40, 15); // Imagen adicional
        $pdf->Image($ittol, 160, 11, 20, 20); // Otra imagen        
    }
}
