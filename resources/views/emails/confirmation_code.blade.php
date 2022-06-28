<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body>
    <h2>Hola {{$usuario['nombres']}}, gracias por registrarte en <strong>Programación y más</strong> !</h2>
    <p>Por favor confirma tu correo electrónico.</p>
    <p>Para ello simplemente debes hacer click en el siguiente enlace:</p>

    <a href="http://127.0.0.1:8000/api/register/verify/{{$usuario['confirmation_code']}}">
        Clic para confirmar tu email
    </a>
</body>
</html>

