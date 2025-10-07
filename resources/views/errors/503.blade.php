<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema en Mantenimiento - Hospital IMSS-Bienestar</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            max-width: 500px;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: rotate 2s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        h1 {
            margin-bottom: 20px;
            font-size: 28px;
        }
        p {
            margin: 15px 0;
            opacity: 0.9;
            font-size: 16px;
            line-height: 1.5;
        }
        .frontend-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .frontend-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        .info-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>Sistema en Mantenimiento</h1>
        <p>El sistema hospitalario está temporalmente fuera de servicio para realizar mejoras y mantenimiento.</p>
        
        <div class="info-box">
            <strong>Nota:</strong> Si estás intentando acceder al sistema administrativo, utiliza el enlace de abajo para ir a la interfaz principal.
        </div>
        
        <a href="{{ config('app.frontend_url', 'http://localhost:4200') }}/maintenance" class="frontend-link">
            Ir al Panel de Mantenimiento
        </a>
        
        <p style="margin-top: 30px; font-size: 14px; opacity: 0.7;">
            Para asistencia urgente, contacte al administrador del sistema
        </p>
    </div>
</body>
</html>
