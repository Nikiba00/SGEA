@extends('layouts.master')
<title>Eventos</title>

@section('Content')
<div class="container">
    <h1>Eventos</h1>
    <div class="search-create">
        <input type="text" id="search-input" placeholder="Buscar eventos...">
        <button id="create-event-btn">Crear nuevo evento</button>
    </div>
</div>
<br><br>
<div class="container">

    <table id="example" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>LOGO</th>
                <th>NOMBRE</th>
                <th>ACRONIMO</th>
                <th>ED.</th>
                <th>FECHA INICIO</th>
                <th>FECHA FIN</th>
                @role('Administrador')
                <th> </th>
                @endrole
            </tr>
        </thead>
        <tbody>
            @foreach ($Eventos as $e)
            <tr>
                <td>
                    <img src="{{ asset('SGEA/public/assets/uploads/' . $e->img) }}" alt="logo" style="width: 150px;">
                </td>
                <td>{!!$e->nombre!!}</td>
                <td>{!!$e->acronimo!!}</td>
                <td>{!!$e->edicion!!}</td>
                <td>{!!$e->fecha_inicio!!}</td>
                <td>{!!$e->fecha_fin!!}</td>
                @role('Administrador')
                <td>
                    <a href="{!!'eventos/'.$e->id.'/edit'!!}">
                        <i class="las la-pen la-2x"></i>
                    </a>
                    <a href="{{url('eventos/'.$e->id)}}"
                        onclick="event.preventDefault(); if (confirm('¿Estás seguro de que deseas eliminar este registro?')) { document.getElementById('delete-form-{{ $e->id }}').submit(); }">
                        <i class="las la-trash-alt la-2x"></i>
                    </a>
                    <form id="delete-form-{{ $e->id }}" action="{{ url('eventos/'.$e->id) }}" method="POST"
                        style="display: none;">
                        @method('DELETE')
                        @csrf
                    </form>
                </td>
                @endrole
            </tr>
            @endforeach
        </tbody>

    </table>
</div>

<div id="create-event-modal" class="modal">
<div class="modal-content">
    <span class="close">&times;</span>
    <h2>Registro de Evento</h2>
    {!! Form::open(['url'=>'/eventos', 'enctype' => 'multipart/form-data']) !!}
    <div class="container">
        <label for="img">Imagenes en sistema:</label>

        @if (isset($sysImgs) && !empty($sysImgs))
            <div class="carousell">
                @foreach ($sysImgs as $image)
                
                <img src="{{  asset('SGEA/public/assets/uploads/'.$image) }}" alt="Imagen" class="img-thumbnail img-selectable" data-img-name="{{ $image }}" style="width: 70px;">
                @endforeach
            </div>
        @else
        <p class="">Aun no hay imagenes en el sistema</p>
        @endif
        <br>
        <hr><br>
        <input type="file" id="img" name="img" accept="image/png">
        <!-- Campo oculto para almacenar el nombre de la imagen seleccionada -->
        <input type="hidden" id="selected_img" name="img">
    </div>

    <label for="nombre">Nombre:</label>
    <input type="text" id="nombre" name="nombre" required>

    <label for="acronimo">Acrónimo:</label>
    <input type="text" id="acronimo" name="acronimo" required>

    <label for="fecha_inicio">Fecha de inicio:</label>
    <input type="date" id="fecha_inicio" name="fecha_inicio" required>

    <label for="fecha_fin">Fecha de fin:</label>
    <input type="date" id="fecha_fin" name="fecha_fin" required>

    <label for="edicion">Edición:</label>
    <input type="number" id="edicion" name="edicion" required>

    <button type="submit">Guardar evento</button>
    {!!Form::close()!!}
</div>
</div>

@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imgSelectables = document.querySelectorAll('.img-selectable');
        const imgInput = document.getElementById('img');
        const selectedImgInput = document.getElementById('selected_img');

        imgSelectables.forEach(function(img) {
            img.addEventListener('click', function() {
                // Asignar el nombre de la imagen al campo oculto
                selectedImgInput.value = this.dataset.imgName;

                // Opcional: Desactivar el campo de subir imagen para evitar confusiones
                imgInput.disabled = true;

                // Opcional: Añadir alguna clase CSS para resaltar la imagen seleccionada
                imgSelectables.forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        // Reactivar el campo de subir imagen si se selecciona un archivo
        imgInput.addEventListener('change', function() {
            selectedImgInput.value = '';
            imgInput.disabled = false;
            imgSelectables.forEach(i => i.classList.remove('selected'));
        });
    });
</script>

<style>
    .selected {
        border: 2px solid blue;
    }
</style>


