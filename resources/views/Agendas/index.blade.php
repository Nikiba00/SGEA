@extends('layouts.master')
<title>Agenda</title>
@section('Content')
<div class="container">
    <div>
        <h1 id="titulo-h1">Agenda del evento: {{$evento->acronimo}} - {{$evento->edicion}}</h1>
    </div>
    <div>
        <p>
            <br><b>Fecha de inicio del evento: </b>{{$formatoInicio}}
            <br><b>Fecha de finalización del evento: </b>{{$formatoFin}}
            <br><b>Duración total del evento: </b>{{$duracion}} días
        </p>
    </div>
    @if($articulos->isEmpty())
        <strong>Aún no hay artículos aceptados</strong>
    @else
        <div class="ajuste">
            <div style="text-align: center;">
                <br>
                <h2 style="text-align: center; display: inline-block; margin-right: 10px;">
                    Artículos disponibles para la creación de agenda
                    <sup>
                        <span style="cursor: pointer; color: #FF0000FF; position: relative; vertical-align: middle;"
                            title="Únicamente se muestran los artículos con calificación aprobatoria (Aceptados)">
                            <i class="las la-info-circle"></i>
                        </span>
                    </sup>
                </h2>
            </div>

            <table id="example" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 36%">Título</th>
                        <th style="width: 10%">Área</th>
                        <th style="width: 22%">Autores</th>
                        <th style="width: 22%">Instituciones</th>
                        <th style="width: 10%">Correo de correspondencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($articulos as $art)
                        <tr>
                            <td style="width: 36%">
                                <a href="{!! url($evento->id . '/articulo/' . $art->id) !!}" style="color:#000;">
                                    <strong>{{ Helper::truncate($art->titulo, 65) }}</strong>
                                </a>
                            </td>
                            <td style="width: 10%">{!!$art->area!!}</td>
                            <td style="width: 22%">{!!$art->autores!!}</td>
                            <td style="width: 22%">{!!$art->instituciones!!}</td>
                            <td style="width: 10%">{!!$art->correo!!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection