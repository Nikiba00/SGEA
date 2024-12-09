@extends('layouts.master')
    <title>Mis Reportes</title>
@section('Content')
    <div class="container">
        <div class="search-create">
            <h1 id="titulo-h1"> Mis Reportes</h1>
        </div>    
        @if($Articulos->isEmpty())
            <strong>No hay datos</strong>
        @else
        <div class="ajuste" >
            <table id="example" class="display  responsive nowrap" style="width:100%">
                <thead>            
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>TITULO</th>
                        <th>AUTORES</th>
                        <th>Area</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($Articulos as $art)
                    <tr>
                        <td><input type="checkbox" class="selectRow" data-id="{{ $art->id }}"></td>
                        <td>
                            <a href="{!! url($art->evento_id.'/articulo/'.$art->id) !!}" style="color:#000;">
                                <strong>{{ Helper::truncate($art->titulo, 65) }}</strong>
                            </a>
                        </td>
                            <td>
                                <ul>
                                    @foreach ($art->autores->sortBy('orden') as $autor)
                                        <li>
                                            <a href="{{url(session('eventoID').'/autor/'.$autor->usuario->id )}}" style="color:#000;">
                                                {{ $autor->orden }}. {{ $autor->usuario->nombre_autor}} 
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            @role(['Administrador','Organizador'])
                            <td>
                                <a href="mailto:{!!$art->autor_correspondencia->email!!}" style="text-decoration:underline;">{!!$art->autor_correspondencia->email!!}</a>
                            </td>
                            <td>
                                <ul>
                                    @if($art->revisores->isEmpty())
                                        No asignados
                                    @else
                                        @foreach ($art->revisores->sortBy('orden') as $revisor)
                                            <li style="">
                                                {{ $revisor->orden}}:
                                                <a href="{{url('/usuarios/'.$revisor->usuario->id )}}">
                                                    {{ $revisor->usuario->nombre_completo}}
                                                </a>
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>
                            </td>
                            @endrole
                            <td>{!!$art->area->nombre!!}</td>
                            <td>
                                <a href="{!! url($art->evento_id.'/articulo/'.$art->id) !!}"><i class="las la-info-circle la-2x"></i></a>
                                <a href="{!! url($art->evento_id.'/articulo/'.$art->id.'/edit')!!}"><i class="las la-edit la-2x"></i></a>
                                <a href="{{url('articulos/'.$art->id)}}" onclick="event.preventDefault(); 
                                        Swal.fire({
                                            title: '¿Estás seguro?',
                                            text: '¿Realmente desea eliminar este articulo?',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonText: 'Sí, eliminar',
                                            cancelButtonText: 'No, cancelar'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                document.getElementById('delete-form-{{ $art->id }}').submit();
                                            }
                                        });
                                    ">
                                        <i class="las la-trash-alt la-2x"></i>
                                    </a>
                                <form id="delete-form-{{ $art->id }}" action="{{ url('articulos/'.$art->id) }}" method="POST" style="display: none;">
                                    @method('DELETE')
                                    @csrf
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
      @endif
    </div>
        
    <div id="create-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Registro de Artículo</h2>
            {!! Form::open(['url' => '/articulos', 'enctype' => 'multipart/form-data', 'id' => 'article-form']) !!}
                {!! Form::label('title', 'Titulo del Articulo:') !!}
                <input type="text" id="titulo" name="titulo" required>
                
                {!! Form::label('desc', 'Resumen del Articulo:') !!}
                <textarea rows="4" cols="50" id="description" name="resumen"></textarea>
                
                {!! Form::label('are', 'Area del Articulo:') !!}
                <select name="area_id" required>
                    <option value="">Seleccionar...</option>
                    @foreach ($Areas as $area)
                        <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                    @endforeach
                </select>

                <label for="pfd">Subir archivo pdf:</label>
                {!! Form::file('pdf', ['id' => 'archivoPDF', 'class' => 'form-control', 'accept' => '.pdf,.docx,.doc']) !!}

                <br><hr><br>
                <!-------------------------------------------------- AUTORES --------------------------------------------->
                
                {!! Form::hidden('selected_authors',null,['id'=> 'selected-authors-input'])!!}

                <h3>{!! Form::label('', 'Autores del Articulo:') !!}</h3>
                
                <div class="showList" style ="display:flex;justify-content:center;align-items:cener;padding:3%;">
                    <span id="No-Autors"><strong>No hay autores Asignados</strong></span>
                    <ul class="selectedAutors" ></ul>
                </div>
                <span id="corresp-instructions"style ="display:none; color:#348aa7; font-size:15px"><i class="las la-info-circle"></i>Marcar  casilla para seleccionar autor de contacto </span>
               
                {!! Form::label('label_instruction', 'Seleccionar Autor:') !!}
                <select name="autor" id="selected-author">
                    @if($Autores=== null)
                        <option value="">Aun no se han registrado autores</option>
                    @else
                        <option value="">Seleccionar...</option>
                        @if(Auth::user()->id!==1)
                            <option value="{{ Auth::user()->id }}">{{ Auth::user()->nombre_completo }}</option>
                        @endif
                        @foreach ($Autores as $autor)
                            @if($autor->id !== Auth::user()->id)
                                <option value="{{ $autor->id }}">{{ $autor->ap_paterno }} {{ $autor->ap_materno }} {{ $autor->nombre}}</option>
                            @endif
                        @endforeach 
                    @endif
                </select>
                 <div class="cntrls" style="display:flex;align-items:center;justify-content:space-evenly;margin-bottom:2vh;">
                    <button type="button" id="plus-author-btn" style="color:#fff;background-color:#1a2d51;">Asignar</button>
                    <button type="button" id="minus-author-btn" style="color:#fff;background-color:#1a2d51;">Quitar</button>
                </div>
                <p>¿No encuentra su Autor? <a href="#" id="register-author-btn"><strong>Registrar Autor</strong></a></p>
                <button type="submit" style="background-color:#1a2d51;color:#fff;">Guardar articulo</button>
                
            {!! Form::close() !!}
        </div>
    </div>

    <div id="register-author-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Registrar Autor </h2>
            <form id="register-author-form" metod="POST" >  
                @csrf  
                <input type="hidden" name="id" id="id">
                <label for="curp">CURP:</label>
                <input type="text" id="curp" name="curp" required>
                <span id="curp-info" style="color:green; display:none;">Se verifico la CURP, favor de llenar todos los campos</span>
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
                <label for="ap_paterno">Apellido Paterno:</label>
                <input type="text" id="ap_paterno" name="ap_paterno" required>
                <label for="ap_materno">Apellido Materno:</label>
                <input type="text" id="ap_materno" name="ap_materno" required>
                <label for="email" id= "email-input">Email:</label>
                <span id="email-error" style="color:red; display:none;">Este Correo ya se encuentra registrado en otro usuario</span>
                <input type="email" id="email" name="email" required>
                <label for="telefono">Teléfono:</label>
                <input type="tel" id="telefono" name="telefono" required>
                <label for="institucion">Institución:</label>
                <input type="text" id="institucion" name="institucion" required>
                <button type="button" id="save-author-btn">Registrar Autor</button>
            </form>
        </div>
    </div>
@endsection