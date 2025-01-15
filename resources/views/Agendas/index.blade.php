@extends('layouts.master')
<title>Agenda</title>
@section('Content')
<div class="container">
    <div class="search-create">
        <h1 id="titulo-h1">Agenda del evento: {{$evento->acronimo}} - {{$evento->edicion}}</h1>
        <button class="tooltip" id="create-btn"><i class="las la-plus-circle la-2x"></i><span
                class="tooltip-box">Generar agenda</span></button>
    </div>
    <div>
        <p>
            <b>Fecha de inicio del evento: </b>{{$formatoInicio}}
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

<div id="create-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Detalles del evento</h2>
        <h4>{{$evento->nombre}} ({{$evento->acronimo}}-{{$evento->edicion}})</h4>
        {!! Form::open(['url' => '/agendas', 'enctype' => 'multipart/form-data', 'id' => 'agenda-form']) !!}
        {!! Form::checkbox('activo', '1', false, ['id' => 'activo']) !!} Activo

        <!--<div style="display: flex; align-items: center; sgap: 10px;">
            <label for="hora_inicio">Hora de inicio:</label>
            <select id="hora_inicio" name="hora_inicio" style="width: 80px;">
                @for ($i = 0; $i < 24; $i++)
                    <option value="{{ str_pad($i, 1, '0', STR_PAD_LEFT) }}">
                        {{ str_pad($i, 1, '0', STR_PAD_LEFT) }}:00
                    </option>
                @endfor
            </select>
            <label for="hora_fin">Hora de fin:</label>
            <select id="hora_fin" name="hora_fin" style="width: 80px;">
                @for ($i = 0; $i < 24; $i++)
                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00
                    </option>
                @endfor
            </select><br>
        </div>-->
        <!--{!! Form::button('Generar agenda', ['type' => 'button', 'id' => 'save-event-btn']) !!}-->
        {!!Form::close()!!}
    </div>
</div>
@endsection

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const horaInicio = document.getElementById("hora_inicio");
        const horaFin = document.getElementById("hora_fin");

        function validarHoras() {
            const inicio = parseInt(horaInicio.value, 10);
            const fin = parseInt(horaFin.value, 10);

            if (inicio >= fin) {
                alert("La hora de inicio debe ser menor a la hora de fin.");
                horaFin.value = "";
            }
        }

        // Escucha los cambios en ambos selectores
        horaInicio.addEventListener("change", validarHoras);
        horaFin.addEventListener("change", validarHoras);
    });
</script>

<style>
    /*label {
        display: inline-flex; /* Asegura que el texto y el checkbox estén en línea
        /*align-items: center; /* Alinea verticalmente el checkbox y el texto
        /*gap: 5px; /* Espacio entre el checkbox y el texto
    }*/

    /*input[type="checkbox"] {
        /*margin: 0; /* Elimina márgenes que puedan afectar la alineación
    }
    input[type="checkbox"] {
        width: 15px;
        height: 15px;
        accent-color: #007bff; /* Cambia el color del checkbox
        vertical-align: middle;
        margin-right: 5px;
    }*/
</style>
<style>
    .reset-container * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    input[type="checkbox"] {
        opacity: 1;
        /* Asegúrate de que sea visible */
        position: static;
        /* Restaura la posición al flujo normal */
        margin: 0;
        /* Elimina márgenes no deseados */
    }

    .custom-checkbox {
        opacity: 1;
        position: static;
        margin: 0;
    }
</style>