<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.1/css/all.css">
    <style>
        main{
            width: 900px;
        }
        .container {
            height: 200px;
            width: 60%;
            background: linear-gradient(#1B5583,white);
            padding-bottom: 5px;
            border-bottom-right-radius: 25%;
            border-bottom-left-radius: 25%;
            display: block;
            margin-right: auto;
            margin-left: auto;


        }
        .contenido{
            width: 60%;
            display: block;
            margin-right: auto;
            margin-left: auto;
            margin-top: -50px;
            padding-top: 50px;
        }
        .cabecera{
            text-align: center;
        }
        .nombreunt{
            margin-top: -15px;
            font-family: serif;
            color: #000;
        }
        h1{
            margin-top: -12px;
        }
        h4{
            text-align: center;
            font-family: helvetica;
        }
        .mensaje{
            margin: 0px 70px;
            text-align: center;
            font-family: helvetica;
            padding-top: 1px;
        }
        main {
            margin: 15px 380px;

        }
        .btn{
            padding: 8px 40px;
        }
        .btn:hover{
            background-color: white;
            color: black;
        }
        button{
            border: 1px solid #1B5583;
            background: #1B5583;
            color: #fff;
            border-radius: 20px;
        }
        span{
            margin: 0 100px;
            display: block;
            margin-right: auto;
            margin-left: auto;
        }
        ul {
            list-style: none;
        }
        .redes {
            margin-top: -15px;
            font-size: 25px;
            display: flex;
        }
        .redes ul{
            display: block;
            margin-left: auto;
            margin-right: auto;
						padding: 0;
        }
        .redes img{
            filter: grayscale(1);
        }
        .redes img:hover{
            filter: grayscale(0);
        }
        .img2{
            padding-left: 7px;
        }
        .robot{
            /* float: left; */
            margin-top: -10px;
        }
        .mb-0{
            clear:both;
            padding-top: 7px ;
        }
        @media(max-width: 767px){
            main{
                width: 900px;
            }
            .container {
                width: 100%;
                background: linear-gradient(#22651a,white);
                padding-bottom: 5px;
                display: block;
                margin-right: auto;
                margin-left: auto;
            }
            .contenido{
                width: 100%;
                display: block;
                margin-right: auto;
                margin-left: auto;
            }
            .mensaje{
                margin: 0px 90px;
                text-align: center;
                font-family: helvetica;
                padding-top: 1px;
            }
        }
    </style>
</head>
<body>
<main>
    <div class="container">
        <div class="cabecera">
            <a href="#"><img src="https://upload.wikimedia.org/wikipedia/commons/6/6e/Universidad_Nacional_de_Trujillo_-_Per%C3%BA_vector_logo.png" class="logo" width="200px"></a>
            <div class="nombreunt">
                <h3>UNIVERSIDAD NACIONAL DE TRUJILLO</h3>
                <h1>UNT</h1></div>
        </div>
    </div>
    <div class="contenido">
        <div class="alert alert-info mensaje" role="alert">
						<div style="text-align: center;">
							<h4>{{$cabecera}}</h4>
							<img class="robot" src="https://i.ibb.co/5RsJVpc/robot-Confirmation.png" width="50%">
						</div>
						<hr>
						<div style="text-align: justify!important;">
							<p>Hola {{$usuario['nombres']}} {{$usuario['apellidos']}}, por favor confirma tu correo electrónico para continuar con {{$mensaje}}. Para ello simplemente debes hacer click en el siguiente enlace:
                            </p>
						</div>
            <hr>
						<a href="http://tramites-uraa.unitru.edu.pe/confirmation-validated/{{$usuario['confirmation_code']}}" target="_blank"><button type="button" class="btn btn-info">Confirmar Correo</button></a>
        </div>
        <div class="redes">
            <ul>
                <a href="https://unitru.edu.pe"><img src="https://cdn.icon-icons.com/icons2/272/PNG/512/Chrome_30036.png" width="35px"></a>
                <a href="https://twitter.com/unitruoficial?lang=es"><img src="https://i.pinimg.com/originals/ec/41/47/ec41475eafca0883460602acf1b59e82.png" width="35px"></a>
                <a href="https://es-la.facebook.com/untlaunicaoficial/"><img src="https://www.clipartmax.com/png/full/95-953617_512-x-512-facebook-circle-icon-jpg.png" width="35px"></a>
            </ul>
        </div>
    </div>
</main>
</body>
</html>
