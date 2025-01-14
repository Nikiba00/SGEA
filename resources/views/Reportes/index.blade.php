@extends('layouts.master')
<title>Reportes</title>
@section('Content')
<div class="container">
    <div>
        <h1 id="titulo-h1">Reportes de mis artículos</h1>
    </div>
    <div class="warning">
        <strong>Importante:</strong>
        Únicamente puedes generar reportes de los artículos en los que hayas sido seleccionado
        como autor de correspondencia y de aquellos reportes cuyo estado sea "Aceptado"
        o "Rechazado".</br>En caso de que el estado del artículo sea "Aceptado", podrás generar también
        el archivo de cesión de derechos y la hoja de referencia para realizar el pago correspondiente.
    </div>
    @if($articulos->isEmpty())
        <strong>No hay datos</strong>
    @else
        <div class="ajuste">
            <table id="example" class="display  responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th class="col1"></th>
                        <th>Título</th>
                        <th>Autores</th>
                        <th>Área</th>
                        <th>Estado</th>
                        <th class="col-btn">Reportes</th>
                        @if($hayAceptados)
                            <th>Cesión de Derechos</th>
                            <th>Pago</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($articulos as $art)
                        <tr>
                            <td class="col1"></td>
                            <td>
                                <a href="{!! url($art->evento_id . '/articulo/' . $art->id) !!}" style="color:#000;">
                                    <strong>{{ Helper::truncate($art->titulo, 65) }}</strong>
                                </a>
                            </td>
                            <td>
                                <ul>
                                    @foreach ($art->autores->sortBy('orden') as $autor)
                                        <li>
                                            <a href="{{url(session('eventoID') . '/autor/' . $autor->usuario->id)}}"
                                                style="color:#000;">
                                                {{ $autor->orden }}. {{ $autor->usuario->nombre_autor}}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>{!!$art->area->nombre!!}</td>
                            <td>{!!$art->estado!!}</td>
                            <td class="col-btn"><!--AQUÍ LOS BOTONES DE LOS REPORTES-->
                                <a href="javascript:void(0);"
                                    onclick="event.preventDefault(); generarReporte('{{ $art->evento_id }}', '{{ $art->id }}');"
                                    class="tooltip-container">
                                    <i class="las la-file-alt la-2x"></i>
                                    <span class="tooltip-text">Generar reporte</span>
                                </a>
                                <!--VISTA PREVIA DEL REPORTE-->
                                <!--<a href="{!! url($art->evento_id . '/articulo/' . $art->id . '/edit')!!}"
                                    class="tooltip-container">
                                    <i class="las la-eye la-2x"></i>
                                    <span class="tooltip-text">Vista previa</span>
                                </a>-->
                                <!--DESCARGAR EL REPORTE-->
                                <!--<a href="{{url('articulos/' . $art->id)}}" onclick="event.preventDefault();"
                                    class="tooltip-container">
                                    <i class="las la-download la-2x"></i>
                                    <span class="tooltip-text">Descargar</span>
                                </a>-->
                                <form id="delete-form-{{ $art->id }}" action="{{ url('articulos/' . $art->id) }}" method="POST"
                                    style="display: none;">
                                    @method('DELETE')
                                    @csrf
                                </form>
                            </td>
                            @if($hayAceptados)
                                <td>
                                    <a hhref="javascript:void(0);"
                                    onclick="event.preventDefault(); descargarCesion('{{ $art->evento_id }}', '{{ $art->id }}');"
                                    class="tooltip-container">
                                        <i class="las la-download la-2x"></i>
                                        <span class="tooltip-text">Descargar cesión de derechos</span>
                                    </a>
                                    <a href="{!! url($art->evento_id . '/articulo/' . $art->id . '/edit')!!}"
                                        class="tooltip-container">
                                        <i class="las la-upload la-2x"></i>
                                        <span class="tooltip-text">Subir cesión de derechos</span>
                                    </a>
                                </td>
                                <td>
                                    <a href="javascript:void(0);"
                                    onclick="event.preventDefault(); descargarReferencia('{{ $art->evento_id }}', '{{ $art->id }}');"
                                    class="tooltip-container">
                                        <i class="las la-download la-2x"></i>
                                        <span class="tooltip-text">Descargar hoja de referencia</span>
                                    </a>
                                    <a href="{!! url($art->evento_id . '/articulo/' . $art->id . '/edit')!!}"
                                        class="tooltip-container">
                                        <i class="las la-upload la-2x"></i>
                                        <span class="tooltip-text">Subir comprobante de pago</span>
                                    </a>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

<!--SCRIPT PARA EL BOTÓN DE GENERAR REPORTE-->
<script>
    function generarReporte(eventoId, articuloId) {
        Swal.fire({
            title: 'Generando reporte...',
            text: 'Por favor espere mientras procesamos su solicitud',
            icon: 'info',
            showConfirmButton: false,
            allowOutsideClick: false
        });

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ url('') }}/${eventoId}_${articuloId}/generarReporte`;
        form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;

        document.body.appendChild(form);
        form.submit();
    }
</script>
<script>
    function descargarCesion(eventoId, articuloId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ url('') }}/${eventoId}_${articuloId}/descargarCesion`;
        form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;

        document.body.appendChild(form);
        form.submit();
    }
</script>
<script>
    function descargarReferencia(eventoId, articuloId) {
        Swal.fire({
            title: 'Generando archivo...',
            text: 'Por favor espere mientras procesamos su solicitud',
            icon: 'info',
            showConfirmButton: false,
            allowOutsideClick: false
        });

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ url('') }}/${eventoId}_${articuloId}/descargarReferencia`;
        form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;

        document.body.appendChild(form);
        form.submit();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if (session('success'))
        Swal.fire({
            title: 'Archivo generado',
            text: "{{ session('success') }}",
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });
    @elseif (session('error'))
        Swal.fire({
            title: 'Error',
            text: "{{ session('error') }}",
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    @endif
</script>

<style>
    .selectedAutors li {
        display: flex;
        flex-direction: row-reverse;
        align-items: center;
        white-space: nowrap;
    }

    .selectedAutors li .correspondencia {

        background-color: red;


    }

    .warning {
        padding: 20px;
        background-color: #FFE88AFF;
        color: #000;
        border-radius: 5px;
        margin-bottom: 10px;
        margin-top: 5px;
        width: 1236px;
        height: 150px;
        border: 2px solid yellow;
        display: grid;
        align-items: center;
    }

    .col1 {
        width: 5px;
    }

    .col-btn {
        width: 80px;
    }

    .tooltip-container {
        position: relative;
        display: inline-block;
    }

    .tooltip-container .tooltip-text {
        visibility: hidden;
        background-color: rgba(211, 211, 211, 0.9);
        color: #000;
        text-align: center;
        border-radius: 5px;
        padding: 5px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        opacity: 0;
        transition: opacity 0.3s, width 0.3s;
        font-size: 12px;
        max-width: 200px;
        word-wrap: break-word;
    }

    .tooltip-container:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
</style>