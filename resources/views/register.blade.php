<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="{{asset('SGEA/public/assets/img/ITTOL.ico')}}" type="image/x-icon">
    <link rel="stylesheet" href="./css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Registrarse</title>
</head>

<body>
    <div class="background"></div>
    <div class="container ">
        <div class="content">
            <div class="header-img">
                <img src="{{asset('SGEA/public/assets/img/TECNM.png')}}" alt="">
            </div>
            <h2 class="logo">SGEA<i class='bx bxs-calendar-event'></i></h2>
            @if(isset($usuarios))
            <h1>existe</h1>
            @endif
            <div class="text-content">
                <h2>Bienvenidos! <br> <span>Sistema de Gestion de Eventos Academicos</span> </h2>
                <p>Este es un sistema de gestion de eventos academicos, el cual tiene como objetivo eficientar el
                    desarrollo de eventos institucionales</p>
                <div class="social-icons">
                    <a href="#"><i class='bx bxl-facebook-circle'></i></a>
                    <a href="#"><i class='bx bxl-instagram-alt'></i></a>
                    <a href="#"><i class='bx bxl-youtube'></i></a>
                </div>
            </div>
        </div>

        <div class="logreg-box">
            <div class="form-box login">


                <div class="register">
                    <form method="POST" id="registration-form" action="{{ route('registrar') }}">
                        @csrf
                        <h1>REGISTRARSE</h1>

                        <div class="input-box">
                            <span class="icon"><i class='bx bxs-id-card'></i></span>
                            <input type="text" id="curp" name="curp" required>
                            <label for="email" class="form-label">CURP</label>
                            <span id="curp-message" style="color: red;"></span>
                        </div>
                        <div class="input-box">
                            <span class="icon"><i class='bx bx-male'></i></span>
                            <input type="text" id="register-name" name="nombre" required>
                            <label for="nombre" class="form-label">Nombre</label>
                            
                        </div>

                        <div class="input-box">
                            <span class="icon"><i class='bx bxs-user-rectangle'></i></span>
                            <input type="text" id="ap_pat" name="ap_paterno" required>
                            <label for="ap_pat" class="form-label">Apellido Paterno</label>
                        </div>

                        <div class="input-box">
                            <span class="icon"><i class='bx bxs-user'></i></span>
                            <input type="text" id="noap_mat" name="ap_materno" required>
                            <label for="ap_mat" class="form-label">Apellido Materno</label>
                        </div>                 

                        <div class="input-box">
                            <span class="icon"><i class='bx bxs-envelope' ></i></span>
                            <input type="email" id="email" name="email" required>
                            <label for="email" class="form-label">Correo electrónico:</label>
                        </div>

                        <div class="input-box">
                            <span class="icon"><i class='bx bxs-phone' ></i></span>
                            <input type="text" id="telefono" name="telefono" required>
                            <label for="telefono" class="form-label">Telefono:</label>
                        </div>

                        <div class="input-box">
                            <span class="toggle-password" onclick="togglePassword('password')"><i class='bx bxs-show'></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <label for="password" class="form-label">Contraseña:</label>
                        </div>
                        <div class="input-box">
                            <span class="toggle-password" onclick="togglePassword('confirm-password')"><i class='bx bxs-show'></i></span>
                            <input type="password" class="form-control" id="confirm-password" name="confirm-password" required>
                            <label for="confirm-password" class="form-label">Confirmar Contraseña:</label>
                        </div>
                        <div class="remember-forgot">
                        <label for="privacy_policy">
                            <input type="checkbox" id="privacy_policy" name="privacy_policy" required> Acepto el <a href="#" onclick="openModal('privacyModal')">aviso de privacidad</a>
                        </label>
                        </div>
                        <button type="submit" class="btn btn-primary">registrarse</button>
                    </form>
                    <p>¿Ya tienes cuenta? <a href="{{route('login')}}">Iniciar Sesion</a></p>
                </div>

            </div>
        </div>
        <div id="password-error-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('password-error-modal')">&times;</span>
        <p id="password-error-message"></p>
    </div>
</div>
        <!-- Modal de Aviso de Privacidad -->
    <div id="privacyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('privacyModal')">&times;</span>
            <h1>Aviso de Privacidad</h1>
            <p>Aquí va el contenido del aviso de privacidad...</p>
        </div>
    </div>
    </div>
    </div>    
    <script src="{{asset('SGEA/public/js/scriptLogin.js')}}"></script>
    <script>
       
    </script>
</body>

</html>