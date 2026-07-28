document.getElementById('formRegistro').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.style.display = 'none';
    mensajeDiv.className = '';

    const data = {
        nombre: document.getElementById('nombre').value,
        correo: document.getElementById('correo').value,
        celular: document.getElementById('celular').value,
        password: document.getElementById('password').value,
        confirm_password: document.getElementById('confirm_password').value
    };

    try {
        // Fetch API al backend PHP
        const response = await fetch('../api/auth/registro.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            mensajeDiv.textContent = result.message;
            mensajeDiv.classList.add('mensaje-success');
            mensajeDiv.style.display = 'block';
            
            // Redirigir al login después de 2 segundos
            setTimeout(() => {
                window.location.href = '../ingreso/index.html';
            }, 2000);
        } else {
            // Mostrar errores enviados por PHP
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