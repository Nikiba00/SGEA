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
        o "Rechazado".
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
                        <th class="col-btn"></th>
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
                                <!--GENERAR REPORTE-->
                                <!--<a href="{!! url($art->evento_id . '/articulo/' . $art->id) !!}" 
                                    onclick="event.preventDefault(); generarReporte('{{ $art->evento_id }}', '{{ $art->id }}');" 
                                    class="tooltip-container">
                                    <i class="las la-file-alt la-2x"></i>
                                    <span class="tooltip-text">Generar reporte</span>
                                </a>-->
                                <a href="javascript:void(0);" 
                                    onclick="event.preventDefault(); generarReporte('{{ $art->evento_id }}', '{{ $art->id }}');" 
                                    class="tooltip-container">
                                    <i class="las la-file-alt la-2x"></i>
                                    <span class="tooltip-text">Generar reporte</span>
                                </a>
                                <!--VISTA PREVIA DEL REPORTE-->
                                <a href="{!! url($art->evento_id . '/articulo/' . $art->id . '/edit')!!}"
                                    class="tooltip-container">
                                    <i class="las la-eye la-2x"></i>
                                    <span class="tooltip-text">Vista previa</span>
                                </a>
                                <!--DESCARGAR EL REPORTE-->
                                <a href="{{url('articulos/' . $art->id)}}" onclick="event.preventDefault();"
                                    class="tooltip-container">
                                    <i class="las la-download la-2x"></i>
                                    <span class="tooltip-text">Descargar</span>
                                </a>
                                <form id="delete-form-{{ $art->id }}" action="{{ url('articulos/' . $art->id) }}" method="POST"
                                    style="display: none;">
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
<!--<script>
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
        form.action = `/${eventoId}/articulo/${articuloId}/generarReporte`;
        form.innerHTML = '@csrf';
        form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;

        console.log(form.action)
        document.body.appendChild(form);
        form.submit();
    }
</script>-->

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
        height: 120px;
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
        width: 120px;
        background-color: lightgray;
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
        transition: opacity 0.3s;
    }

    .tooltip-container:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
</style>

<!--<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteSelected = document.getElementById('deleteSelected');
        const selectAll = document.getElementById('selectAll');

        if (deleteSelected) {
            deleteSelected.addEventListener('click', function () {
                const selectedCheckboxes = document.querySelectorAll('.selectRow:checked');
                let ids = [];
                selectedCheckboxes.forEach(function (checkbox) {
                    ids.push(checkbox.getAttribute('data-id'));
                });

                if (ids.length > 0) {
                    Swal.fire({
                        title: '¿Está seguro?',
                        text: '¡No podrá revertir esto!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'No, cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('{{ route('articulos.deleteMultiple') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ ids: ids })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Éxito',
                                            text: 'Registros eliminados correctamente.'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Error: ' + data.error
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Ha ocurrido un error al eliminar los registros.'
                                    });
                                });
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Información',
                        text: 'No se seleccionaron registros.'
                    });
                }
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.selectRow');
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
            });
        }
    });
</script>-->
<!--<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        const articleForm = document.getElementById('article-form');
        const noAutorsText = document.getElementById('No-Autors');
        const ci = document.getElementById('corresp-instructions');
        const selectedAuthorSelect = document.getElementById('selected-author');
        const selectedAuthorsList = document.querySelector('.selectedAutors');
        const plusAuthorBtn = document.getElementById('plus-author-btn');
        const minusAuthorBtn = document.getElementById('minus-author-btn');
        const selectedAuthorsInput = document.getElementById('selected-authors-input');

        const createArticleModal = document.getElementById('create-modal');
        const registerAuthorModal = document.getElementById('register-author-modal');

        let selectedAuthors = [];


        const updateAuthorList = () => {
            selectedAuthorsList.innerHTML = '';

            if (selectedAuthors.length === 0) {
                noAutorsText.style.display = 'block';
                ci.style.display = 'none';
            } else {
                noAutorsText.style.display = 'none';
                ci.style.display = 'block';
            }

            selectedAuthors.forEach((author, index) => {
                const checkbox = document.createElement('input');
                const newListItem = document.createElement('li');
                checkbox.type = 'checkbox';
                checkbox.class = 'correspondencia';
                checkbox.name = 'corresponding_author';
                checkbox.checked = author.corresponding;
                checkbox.addEventListener('change', () => {
                    selectedAuthors.forEach(a => a.corresponding = false);
                    author.corresponding = checkbox.checked;
                    updateAuthorList();
                });

                newListItem.textContent = `${index + 1}. ${author.name} `;
                newListItem.prepend(checkbox);
                newListItem.setAttribute('data-value', author.id);
                selectedAuthorsList.appendChild(newListItem);
            });

            const anyChecked = selectedAuthors.some(a => a.corresponding);
            selectedAuthorsList.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                if (!checkbox.checked) {
                    checkbox.disabled = anyChecked;
                }
            });

        };

        const updateSelectedAuthorsInput = () => {
            const selectedAuthorsData = selectedAuthors.map(author => ({
                id: author.id.toString(),
                corresponding: author.corresponding,
                institucion: author.institucion
            }));
            selectedAuthorsInput.value = JSON.stringify(selectedAuthorsData);
            console.log('Campo oculto:', selectedAuthorsInput.value);

        };

        function resetRegisterAuthorForm() {
            const registerAuthorForm = document.getElementById('register-author-form');
            //reseteamos los valores de los imputs
            registerAuthorForm.reset();
            //habilitamos los campos 
            document.querySelectorAll('#register-author-form input').forEach(input => {
                input.disabled = false;
            });
        }


        plusAuthorBtn.addEventListener('click', () => {
            const selectedValue = selectedAuthorSelect.value;
            const selectedText = selectedAuthorSelect.options[selectedAuthorSelect.selectedIndex].text;
            if (!selectedValue) {
                Swal.fire({ title: '¡Cuidado!', text: 'Por favor, seleccione un autor de la lista desplegable.', icon: 'warning', }); return;
            }
            if (selectedAuthors.find(author => author.id === selectedValue)) {
                Swal.fire({ title: 'Cuidado!', text: 'El autor seleccionado ya se encuentra en la lista.', icon: 'warning', }); return;
            }
            fetch('{{ route('revisar-existencia') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ author_id: selectedValue })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.exists) {
                        createArticleModal.style.display = 'none';
                        registerAuthorModal.style.display = 'block';
                        if (data.user) {
                            document.querySelector('input[name="id"]').value = data.user.id || '';
                            document.querySelector('input[name="curp"]').value = data.user.curp || '';
                            document.querySelector('input[name="nombre"]').value = data.user.nombre || '';
                            document.querySelector('input[name="ap_paterno"]').value = data.user.ap_paterno || '';
                            document.querySelector('input[name="ap_materno"]').value = data.user.ap_materno || '';
                            document.querySelector('input[name="telefono"]').value = data.user.telefono || '';
                            document.querySelector('input[name="email"]').value = data.user.email || '';
                            // Bloquear los campos
                            document.querySelectorAll('#register-author-form input').forEach(input => {
                                if (input.name !== 'institucion') {
                                    input.disabled = true;
                                }
                            });
                        }
                    } else {
                        selectedAuthors.push({ id: selectedValue, name: selectedText, corresponding: false, institucion: data.user.institucion });
                        updateAuthorList(); updateSelectedAuthorsInput(); noAutorsText.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });

        minusAuthorBtn.addEventListener('click', () => {
            const selectedValue = selectedAuthorSelect.value;
            if (!selectedValue) {
                Swal.fire({
                    title: '¡Cuidado!',
                    text: 'Por favor, seleccione un autor de la lista desplegable.',
                    icon: 'warning',
                });
                return;
            }
            const authorIndex = selectedAuthors.findIndex(author => author.id === selectedValue);
            if (authorIndex === -1) {
                Swal.fire({
                    title: '¡Cuidado!',
                    text: 'El autor seleccionado no está en la lista.',
                    icon: 'warning',
                });
                return;
            }
            selectedAuthors.splice(authorIndex, 1);
            updateSelectedAuthorsInput();
            updateAuthorList();
        });

        articleForm.addEventListener('submit', (event) => {
            const hasCorrespondingAuthor = selectedAuthors.some(author => author.corresponding);
            if (!hasCorrespondingAuthor) {
                Swal.fire({
                    title: '¡Cuidado!',
                    text: 'Seleccione un autor de correspondencia.',
                    icon: 'warning',
                });
                event.preventDefault();
                return
            } else {
                updateSelectedAuthorsInput();
            }
        });

        document.getElementById('save-author-btn').addEventListener('click', async () => {
            let newAuthorId = document.querySelector('input[name="id"]').value || '';
            const newAuthorCurp = document.querySelector('input[name="curp"]').value || '';
            const newAuthorNombre = document.querySelector('input[name="nombre"]').value || '';
            const newAuthorApPaterno = document.querySelector('input[name="ap_paterno"]').value || '';
            const newAuthorApMaterno = document.querySelector('input[name="ap_materno"]').value || '';
            const newAuthorTelefono = document.querySelector('input[name="telefono"]').value || '';
            const newAuthorEmail = document.querySelector('input[name="email"]').value || '';
            const newAuthorInstitucion = document.querySelector('input[name="institucion"]').value || '';

            const newAuthorName = `${newAuthorApPaterno} ${newAuthorApMaterno} ${newAuthorNombre}`;

            if (!newAuthorCurp || !newAuthorNombre || !newAuthorApPaterno || !newAuthorApMaterno || !newAuthorTelefono || !newAuthorEmail || !newAuthorInstitucion) {
                // swal('Todos los campos son obligatorios.',' ','error');
                Swal.fire({
                    title: '¡Cuidado!',
                    text: 'Todos los campos son obligatorios. ',
                    icon: 'warning',
                });
                return;
            }

            const authorData = {
                id: newAuthorId, curp: newAuthorCurp, nombre: newAuthorNombre,
                ap_paterno: newAuthorApPaterno, ap_materno: newAuthorApMaterno, telefono: newAuthorTelefono, email: newAuthorEmail, institucion: newAuthorInstitucion
            };

            if (newAuthorId === "" || newAuthorId === null) {
                try {
                    const response = await fetch('{{ route('insertar-usuario') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ authorData })
                    });
                    const data = await response.json();
                    if (data.error) {
                        // alert(data.error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error
                        });
                        return;
                    } else {
                        newAuthorId = data.id.toString();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    return;
                }
            }

            // Verificar si el autor ya existe en el combo o en el array
            const isAuthorInArray = selectedAuthors.some(author => author.id === newAuthorId);
            const isAuthorInSelect = Array.from(selectedAuthorSelect.options).some(option => option.value === newAuthorId);

            if (isAuthorInArray) {
                // swal('El autor ya existe.',' ','error');
                Swal.fire({
                    title: '¡Cuidado!',
                    text: 'El autor ya existe.',
                    icon: 'warning',
                });
                return;
            } else {
                if (isAuthorInSelect === false) {
                    // agregar el nuevo autor al combo de selección
                    const newOption = document.createElement('option');
                    newOption.value = newAuthorId;
                    newOption.text = newAuthorName;
                    selectedAuthorSelect.appendChild(newOption);
                }
                // agregar el autor al array
                selectedAuthors.push({ id: newAuthorId, name: newAuthorName, corresponding: false, institucion: newAuthorInstitucion });
                updateAuthorList();
                updateSelectedAuthorsInput();
                noAutorsText.style.display = 'none';
                // manejo de modales
                registerAuthorModal.style.display = 'none';
                createArticleModal.style.display = 'block';
                resetRegisterAuthorForm();
            }
        });

        var registerAuthorBtn = document.getElementById('register-author-btn');
        var saveAuthorBtn = document.getElementById('save-author-btn');

        registerAuthorBtn.addEventListener('click', function () {
            createArticleModal.style.display = 'none';
            registerAuthorModal.style.display = 'block';
        });

        saveAuthorBtn.addEventListener('click', function () {
            registerAuthorModal.style.display = 'none';
            createArticleModal.style.display = 'block';
        });
        updateAuthorList();
        updateSelectedAuthorsInput();
    });
</script>-->
<!--<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        const curpInput = document.getElementById('curp'); const idInput = document.getElementById('id');
        const nombreInput = document.getElementById('nombre'); const apPaternoInput = document.getElementById('ap_paterno');
        const apMaternoInput = document.getElementById('ap_materno'); const emailInput = document.getElementById('email');
        const telefonoInput = document.getElementById('telefono'); const institucionInput = document.getElementById('institucion');
        const curpInfo = document.getElementById('curp-info'); const emailError = document.getElementById('email-error');

        function resetRegisterAuthorForm() {
            const registerAuthorForm = document.getElementById('register-author-form');
            registerAuthorForm.reset();
        }

        function fillForm(userData) {
            if (typeof userData === 'object') {
                idInput.value = userData.id || '';
                nombreInput.value = userData.nombre || '';
                nombreInput.disabled = true;
                apPaternoInput.value = userData.ap_paterno || '';
                apPaternoInput.disabled = true
                apMaternoInput.value = userData.ap_materno || '';
                apMaternoInput.disabled = true;
                emailInput.value = userData.email || '';
                emailInput.disabled = true;
                telefonoInput.value = userData.telefono || '';
                telefonoInput.disabled = true;
            } else {
                console.error('Invalid user data provided to fillForm function!');
            }
        }

        function unlockInputs() {
            document.querySelectorAll('#register-author-form input').forEach(input => {
                if (input.name !== 'institucion' || input.name == 'curp') {
                    input.disabled = false;
                }
                if (input.name == 'curp') {
                    input.disabled = false;
                }
            });
        }

        curpInput.addEventListener('input', () => {
            fetch('{{ route('verify-curp') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ curp: curpInput.value })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'exists') {
                        fillForm(data.user);
                        curpInfo.textContent = "El usuario ya existe, favor de llenar los datos que faltan";
                        curpInfo.style.display = 'block';
                    } else {
                        curpInfo.style.display = 'none';
                        unlockInputs();
                        idInput.value = '';
                        nombreInput.value = '';
                        apPaternoInput.value = '';
                        apMaternoInput.value = '';
                        emailInput.value = '';
                        telefonoInput.value = '';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });

        emailInput.addEventListener('input', () => {
            fetch('{{ route('verify-email') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: emailInput.value })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'exists') {
                        emailError.style.display = 'block';
                    } else {
                        emailError.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });


        const registerAuthorModal = document.getElementById('register-author-modal');
        const createArticleModal = document.getElementById('create-modal');
        const closeButtons = document.querySelectorAll('.modal .close');

        closeButtons.forEach(function (closeBtn) {
            closeBtn.addEventListener('click', function () {
                resetRegisterAuthorForm(); unlockInputs();
                createArticleModal.style.display = 'block';
                closeBtn.parentElement.parentElement.style.display = 'none';
            });
        });
    });
</script>-->
<!--<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteSelected = document.getElementById('deleteSelected');
        const selectAll = document.getElementById('selectAll');

        if (deleteSelected) {
            deleteSelected.addEventListener('click', function () {
                const selectedCheckboxes = document.querySelectorAll('.selectRow:checked');
                let ids = [];
                selectedCheckboxes.forEach(function (checkbox) {
                    ids.push(checkbox.getAttribute('data-id'));
                });

                if (ids.length > 0) {
                    fetch('{{ route('articulos.deleteMultiple') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ ids: ids })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Registros eliminados correctamente.');
                                location.reload();
                            } else {
                                alert('Error: ' + data.error);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                } else {
                    alert('No se seleccionaron registros.');
                }
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.selectRow');
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
            });
        }
    });
</script>-->