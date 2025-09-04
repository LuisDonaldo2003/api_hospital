// JavaScript simple para verificar autenticación

document.addEventListener('DOMContentLoaded', function() {
    // Solo verificar autenticación cada 2 minutos
    setInterval(checkAuthStatus, 120000);
    
    function checkAuthStatus() {
        fetch('/pulse/check-auth', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok && response.status === 401) {
                alert('Tu sesión ha expirado. Serás redirigido al login.');
                window.location.href = '/pulse/login';
            }
        })
        .catch(error => {
            // Ignorar errores de red
        });
    }
});
