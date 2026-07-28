// assets/js/login.js
document.getElementById('formLogin').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.style.display = 'none';
    mensajeDiv.className = '';

    const data = {
        correo: document.getElementById('correo').value,
        password: document.getElementById('password').value
    };

    try {
        // Recuerda cambiar "ExitoImpulso" por el nombre de tu carpeta si es diferente
        const response = await fetch('/api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            // Redirigir según lo que nos diga PHP
            // Si es admin (rol 1) va a administrador, si es usuario (rol 2) va al portal
            window.location.href = '../' + result.redirect;
        } else {
            mensajeDiv.textContent = result.error || 'Ocurrió un error inesperado.';
            mensajeDiv.classList.add('mensaje-error');
            mensajeDiv.style.display = 'block';
        }
    } catch (error) {
        mensajeDiv.textContent = 'Error de conexión con el servidor.';
        mensajeDiv.classList.add('mensaje-error');
        mensajeDiv.style.display = 'block';
        console.error('Error:', error);
    }
});