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
        <!-- <div style="overflow-x:auto; overflow-y:auto; max-height:500px;"> -->
        <div class="ajuste" >
        <button id="deleteSelected">Eliminar seleccionados</button>
            <table id="example" class="display nowrap" style="width:100%">
                <thead>
                    <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                        <th>AUTOR</th>
                        <th>INSTITUCION</th>
                        @role('Administrador')
                        <th>EMAIL DE CORRESPONDENCIA</th>
                        @endrole
                        <th>CONTROLES</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($autores as $autor)
                    <tr>
                    <td><input type="checkbox" class="selectRow" data-id="{{ $autor->id }}"></td>
                        <td>
                            <a href="{{url ($autor->evento_id.'/autor/'.$autor->usuario->id) }}" style="color:#000;">
                            {!!$autor->usuario->nombre_completo!!} 
                            </a>
                        </td>
                        <td><strong>{{ Helper::truncate($autor->institucion, 50) }}</strong></td>
                        @role('Administrador')
                        <td>
                            <a href="mailto:{!!$autor->email!!}">{!!$autor->email!!}</a>
                        </td>
                        @endrole
                        <td>
                            <a href="{{url ($autor->evento_id.'/autor/'.$autor->usuario->id) }}"><i class="las la-info-circle la-2x"></i></a>
                            @role(['Administrador','Organizador'])
                                <a href="{!! url($autor->evento_id.'/autores/'.$autor->usuario->id.'/edit')!!}">
                                    <i class="las la-user-edit la-2x"></i>
                                </a>
                            @endrole
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
@endsection