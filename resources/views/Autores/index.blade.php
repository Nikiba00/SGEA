@extends('layouts.master')
<title>Autores</title>
@section('Content')
    <div class="container">
        <div class="search-create">
            <h1 id="titulo-h1">Autores</h1>
        </div>
        @if($autores->isEmpty())
            <strong>No hay autores registrados en este momento</strong>
        @else
        <div class="ajuste" >
            <table id="example" class="display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>AUTOR</th>
                        <th>INSTITUCIÓN</th>
                        @if(auth()->user()->hasRole(['Administrador']))
                        <th>EMAIL DE CORRESPONDENCIA</th>
                        @endif
                        <th>CONTROLES</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($autores as $autor)
                    <tr>
                        <td>{!!$autor->usuario->nombre_completo!!} </td>
                        <td>{{ Helper::truncate($autor->institucion, 50) }}</td>
                        @if(auth()->user()->hasRole(['Administrador']))
                        <td>
                            <a href="mailto:{!!$autor->email!!}">{!!$autor->email!!}</a>
                        </td>
                        @endif
                        <td>
                            <a href="{{url ($autor->evento_id.'/autor/'.$autor->usuario->id) }}"><i class="las la-info-circle la-2x"></i></a>
                            @if(auth()->user()->hasRole(['Administrador','Comite']))
                                <a href="{!! url($autor->evento_id.'/autores/'.$autor->usuario->id.'/edit')!!}">
                                    <i class="las la-user-edit la-2x"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
@endsection