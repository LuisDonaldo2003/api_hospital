<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class InjectPulseLogout
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo inyectar el botón si estamos en una página de Pulse y hay autenticación
        $pulsePath = config('pulse.path', 'pulse');
        if (($request->is($pulsePath.'*') || $request->is('*/'.$pulsePath.'*')) && Session::has('pulse_director_authenticated')) {
            $content = $response->getContent();
            
            // Verificar que el contenido sea HTML
            if (str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
                $logoutButton = $this->getLogoutButtonHtml();
                
                // Inyectar el botón después del tag <head>
                $content = str_replace(
                    '</head>',
                    $logoutButton . '</head>',
                    $content
                );
                
                $response->setContent($content);
            }
        }

        return $response;
    }

    private function getLogoutButtonHtml(): string
    {
        $directorName = Session::get('pulse_director_name', 'Director');
        $csrfToken = csrf_token();
        
        return "
        <link href=\"" . asset('pulse-assets/pulse-auth.css') . "\" rel=\"stylesheet\">
        <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css\" rel=\"stylesheet\">
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Crear la barra de logout
            const authBar = document.createElement('div');
            authBar.id = 'pulse-auth-bar';
            authBar.innerHTML = `
                <div style=\"display: flex; align-items: center; gap: 10px;\">
                    <i class=\"fas fa-shield-alt\" style=\"font-size: 16px;\"></i>
                    <span class=\"pulse-session-indicator\">Laravel Pulse - <strong>" . htmlspecialchars($directorName) . "</strong></span>
                </div>
                <form method=\"POST\" action=\"/pulse/logout\" style=\"margin: 0;\">
                    <input type=\"hidden\" name=\"_token\" value=\"{$csrfToken}\">
                    <button type=\"submit\" id=\"pulse-logout-btn\">
                        <i class=\"fas fa-sign-out-alt\" style=\"margin-right: 5px;\"></i>
                        Cerrar Sesión
                    </button>
                </form>
            `;
            
            // Insertar al inicio del body
            document.body.insertBefore(authBar, document.body.firstChild);
            
            // Ajustar el margen del body
            document.body.style.marginTop = '60px';
            document.body.style.paddingTop = '10px';
            
            // Manejar el click del botón logout
            const logoutBtn = document.getElementById('pulse-logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (confirm('¿Estás seguro de que deseas cerrar la sesión de Laravel Pulse?')) {
                        // Mostrar estado de carga
                        logoutBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Cerrando...';
                        logoutBtn.disabled = true;
                        
                        // Enviar el formulario
                        this.closest('form').submit();
                    }
                });
            }
        });
        </script>
        ";
    }
}
