<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Código de Verificación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 30px;
        }
        .container {
            background-color: #ffffff;
            padding: 20px 30px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .code {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin: 20px 0;
        }
        .footer {
            font-size: 12px;
            color: #999999;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hola {{ $user->name }},</h2>
        <p>Gracias por registrarte en nuestra plataforma.</p>
        <p>Tu código de verificación es:</p>
        <div class="code">{{ $code }}</div>
        <p>Ingresa este código en la plataforma para completar la verificación de tu cuenta.</p>
        <p>Si tú no realizaste este registro, puedes ignorar este correo.</p>
        <div class="footer">
            <p>Plataforma Médica © {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>
