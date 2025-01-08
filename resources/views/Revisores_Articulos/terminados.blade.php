@extends('layouts.master')
<title>Artículos revisados</title>
@section('Content')
<div class="container">
    <div class="search-create">
        @if(auth()->user()->hasRole('Autor'))
            <h1 id="titulo-h1">Historial de Revisiones</h1>
        @else
            <h1 id="titulo-h1">Artículos Revisados</h1>
        @endif
    </div>
    @if(auth()->user()->hasRole('Autor'))
        <div class="information" style="margin-bottom:5vh;">
            <i class="las la-info-circle"></i>
                <span>Usted sólo podrá ver las evaluaciones de los artículos en los que <strong>usted es autor de contacto</strong> </span>
        </div>
    @endif

    @if($articulos->isEmpty())
        @if(auth()->user()->hasRole('Autor'))
            <strong>Aún no hay Artículos que hayan finalizado su revisión</strong>
        @else
            <strong>Aún no ha revisado ningún Artículo</strong>
        @endif
    @else
        <div class="ajuste">
            <table id="example" class="display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ARTÍCULO</th>
                        @if(!auth()->user()->hasRole('Autor'))
                            <th>Puntuación asignada</th>
                        @endif
                        <th>Estado</th>
                        @if(auth()->user()->hasRole('Autor'))
                            <th> </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($articulos as $ra)
                        <tr>
                            <td><a href="{!! url(session('eventoID') . '/articulo/' . $ra->articulo->id) !!}"
                                    style="color:#000;">{!!$ra->articulo->titulo!!} </a></td>
                            @if(!auth()->user()->hasRole('Autor'))
                                <td><strong>{!!$ra->puntuacion!!} / 30</strong></td>
                            @endif
                            <td>
                                <strong>{!!$ra->articulo->estado!!}</strong>
                            </td>
                            @if(auth()->user()->hasRole('Autor'))
                                <td>
                                    <a href="{{url('revisores/' . $ra->articulo->id)}}"> <button>Ver Detalles</button></a>
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