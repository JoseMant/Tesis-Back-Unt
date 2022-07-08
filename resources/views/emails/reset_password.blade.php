<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body>
    <h2>Hola {{$usuario['nombres']}} {{$usuario['apellidos']}}, por favor haga click en el siguiente enlace  para continuar con con la recuperación de su contraseña del <strong>SISTEMA DE GRADOS Y TÍTULOS</strong></h2>
    <!-- <p>Por favor confirma tu correo electrónico.</p> -->
    <!-- <p>Para ello simplemente debes hacer click en el siguiente enlace:</p> -->

    <a href="http://127.0.0.1:8000/api/register/verify/{{$usuario['reset_password']}}">
        Clic para continuar
    </a>
</body>
</html>

