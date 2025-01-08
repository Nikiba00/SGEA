@extends('layouts.master')
<title>Artículos por Revisar</title>
@section('Content')
    <div class="container">
        <div class="search-create">
            <h1 id="titulo-h1">Artículos por Revisar</h1>
        </div>
        @if($articulos->isEmpty())
            <strong>No Tiene artículos Pendientes</strong>
        @else
            <table id="example" class="display nowrap" style="width:100%">
                <thead>
                    <th>Artículo</th>
                    <th>Autores</th>
                    <th>Correspondencia</th>
                    <th>Área</th>
                    <th>Controles</th>
                </thead>
                <tbody>
                    @foreach ($articulos as $art)
                    <tr>

                        <td><strong>{!! $art->titulo!!}</strong></td>
                        <td>
                            <ul>
                                @foreach ($art->autores->sortBy('orden') as $autor)
                                <li>
                                    <a href="{{url ('usuarios/'.$autor->usuario->id) }}" style="color:#000;">{{ $autor->orden }}. {{ $autor->usuario->nombre_autor}} </a>
                                </li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            <a href="mailto:{!!$art->autor_correspondencia->email!!}" style="text-decoration:underline;">{!!$art->autor_correspondencia->email!!}</a>
                        </td>
                        <td>{{$art->area->nombre}}</td>
                        <td>
                            <a href="{{url(session('eventoID').'_'.$art->id.'/revision/')}}"><button>Iniciar Revisión</button></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>

@endsection