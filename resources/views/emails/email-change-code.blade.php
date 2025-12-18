<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Código de Verificación - Cambio de Correo</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Raleway:wght@400;700&display=swap');

    body {
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
      font-family: 'Raleway', sans-serif;
    }

    .container {
      max-width: 600px;
      margin: auto;
      background-color: #ffffff;
      border-radius: 8px;
      padding: 40px 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .logo {
      text-align: center;
      margin-bottom: 35px;
    }

    .logo img {
      width: 220px;
      max-width: 90%;
      display: block;
      margin: 0 auto;
    }

    h1 {
      text-align: center;
      color: #181147;
      font-size: 28px;
      margin-bottom: 20px;
    }

    p {
      color: #444;
      font-size: 16px;
      line-height: 1.6;
    }

    .code-box {
      text-align: center;
      background-color: #fbea41;
      padding: 20px;
      margin: 30px auto;
      border-radius: 6px;
      font-size: 32px;
      font-weight: bold;
      color: #181147;
      letter-spacing: 4px;
    }

    .info-box {
        background-color: #eef2f7;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #181147;
    }

    .footer {
      text-align: center;
      font-size: 12px;
      color: #888;
      margin-top: 40px;
    }

    @media (max-width: 620px) {
      .container {
        padding: 30px 20px;
      }

      .code-box {
        font-size: 26px;
      }

      .logo img {
        width: 160px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
        <div style="background: url('https://i.imgur.com/fs6dUKR.png') center/contain no-repeat; width: 400px; height: 200px; margin: 0 auto;"></div>
    </div>

    <h1>Solicitud de Cambio de Correo</h1>
    <p>Hola <strong>{{ $user->name }}</strong>,</p>
    <p>Hemos recibido una solicitud para cambiar tu dirección de correo electrónico en la plataforma del Hospital IMSS-Bienestar Coyuca de Catalan.</p>

    <div class="info-box">
        <strong>Nuevo Correo Solicitado:</strong> {{ $new_email }}
    </div>

    <p>Por favor, ingresa el siguiente código en la plataforma para autorizar este cambio. Este código se envía a tu dirección de correo electrónico actual por seguridad:</p>

    <div class="code-box">{{ $code }}</div>

    <p>Este código es válido por 5 minutos. Si tú no solicitaste este cambio, por favor contacta al administrador de inmediato y asegúrate de que tu cuenta esté segura.</p>

    <div class="footer">
    Hospital IMSS-Bienestar Coyuca de Catalan© {{ date('Y') }}<br>
    Todos los derechos reservados.
    </div>
  </div>
</body>
</html>
