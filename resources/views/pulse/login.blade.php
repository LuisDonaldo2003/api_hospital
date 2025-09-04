<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Laravel Pulse - Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 15px;
            box-sizing: border-box;
            overflow-x: hidden;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            margin: auto;
        }
        
        .pulse-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .pulse-logo i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .pulse-logo h3 {
            color: #333;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .pulse-logo p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-pulse {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-pulse:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .director-badge {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 10px 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh; padding: 0;">
        <div class="row justify-content-center" style="width: 100%; margin: 0;">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4" style="padding: 15px;">
                <div class="login-card">
                    <div class="pulse-logo">
                        <i class="fas fa-heartbeat"></i>
                        <h3>Laravel Pulse</h3>
                        <p>Monitoreo del Sistema Hospitalario</p>
                    </div>

                    <div class="director-badge">
                        <i class="fas fa-shield-alt"></i>
                        Acceso exclusivo para Director General
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if (session('message'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('message') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ url('/pulse/authenticate') }}">
                        @csrf
                        
                        <div class="form-floating">
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   placeholder="Correo electrónico"
                                   value="{{ old('email') }}" 
                                   required>
                            <label for="email">
                                <i class="fas fa-envelope me-2"></i>Correo electrónico
                            </label>
                        </div>

                        <div class="form-floating">
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Contraseña"
                                   required>
                            <label for="password">
                                <i class="fas fa-lock me-2"></i>Contraseña
                            </label>
                        </div>

                        <button type="submit" class="btn btn-pulse text-white">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Acceder a Pulse
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Solo usuarios con rol "Director General" pueden acceder
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
