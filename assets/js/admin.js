// assets/js/admin.js
const API_URL = '/api/admin';

// Variables globales
let preguntasCache = [];
let preguntaEditandoIndex = -1; // Variable para saber qué pregunta del cache estamos editando

// Verificar si es admin al cargar
fetch('/api/auth/validarSesion.php')
    .then(res => res.json())
    .then(data => {
        if (!data.logueado) {
            window.location.href = '../ingreso/index.html';
        } else if (data.rol_id != 1) {
            alert('Acceso denegado. Tu cuenta no es de administrador.');
            window.location.href = '../opciones del portal/index.html';
        }
    });

// Cargar datos iniciales
document.addEventListener('DOMContentLoaded', () => {
    listarCategorias();
});

function cargarVista(vista, btn) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.getElementById('view-' + vista).classList.add('active');
    
    document.querySelectorAll('.sidebar button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (vista === 'categorias') listarCategorias();
    if (vista === 'examenes') {
        cargarSelectCategorias();
        listarExamenes();
    }
    if (vista === 'preguntas') {
        cargarCategoriasPreguntas(); // Carga el menú de categorías
        document.getElementById('contenedor-opciones').innerHTML = '';
        agregarOpcion(); agregarOpcion(); // Iniciar con 2 opciones por defecto
        actualizarBotonesNavegacion();
    }
    if (vista === 'estadisticas') cargarEstadisticas();
    if (vista === 'usuarios') listarUsuarios();
}

// ==========================================
// ESTADÍSTICAS ADMIN
// ==========================================
function cargarEstadisticas() {
    fetch(`${API_URL}/estadisticas.php`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('stat-usuarios').innerText = data.total_usuarios;
                document.getElementById('stat-rendidos').innerText = data.total_rendidos;
                document.getElementById('stat-promedio-gral').innerText = data.promedio_general + '%';

                const tbody = document.getElementById('tabla-top-examenes');
                tbody.innerHTML = '';
                if (data.top_examenes && data.top_examenes.length > 0) {
                    data.top_examenes.forEach(ex => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${ex.titulo}</td>
                                <td>${ex.veces_rendido}</td>
                                <td>${Math.round(ex.promedio)}%</td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="3">Aún no se han rendido exámenes.</td></tr>';
                }
            }
        });
}

// ==========================================
// CRUD USUARIOS
// ==========================================
function listarUsuarios() {
    fetch(`${API_URL}/usuarios.php`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('tabla-usuarios');
            tbody.innerHTML = '';
            if (data.data && data.data.length > 0) {
                data.data.forEach(u => {
                    const esAdmin = u.rol_id == 1;
                    const esActivo = u.estado == 1;

                    tbody.innerHTML += `
                        <tr>
                            <td>${u.id}</td>
                            <td>${u.nombre}</td>
                            <td>${u.correo}</td>
                            <td>${u.celular || '-'}</td>
                            <td>
                                <button class="btn-action ${esAdmin ? 'btn-edit' : 'btn-delete'}" onclick="cambiarRol(${u.id}, ${u.rol_id})">
                                    ${esAdmin ? 'Admin' : 'Usuario'}
                                </button>
                            </td>
                            <td>
                                <span style="color: ${esActivo ? '#16a34a' : '#dc2626'}; font-weight: 600;">
                                    ${esActivo ? 'Activo' : 'Bloqueado'}
                                </span>
                            </td>
                            <td>
                                <button class="btn-action ${esActivo ? 'btn-delete' : 'btn-edit'}" onclick="cambiarEstado(${u.id}, ${u.estado})">
                                    ${esActivo ? 'Bloquear' : 'Activar'}
                                </button>
                                <button class="btn-action btn-delete" onclick="eliminarUsuario(${u.id})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7">No hay usuarios registrados.</td></tr>';
            }
        });
}

function cambiarRol(id, rolActual) {
    const nuevoRol = rolActual == 1 ? 2 : 1;
    fetch(`${API_URL}/usuarios.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, campo: 'rol_id', valor: nuevoRol })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) { alert(data.message); listarUsuarios(); }
        else alert(data.error);
    });
}

function cambiarEstado(id, estadoActual) {
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    fetch(`${API_URL}/usuarios.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, campo: 'estado', valor: nuevoEstado })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) { alert(data.message); listarUsuarios(); }
        else alert(data.error);
    });
}

function eliminarUsuario(id) {
    if (confirm('¿Estás seguro de ELIMINAR este usuario? Se borrarán todos sus historiales.')) {
        fetch(`${API_URL}/usuarios.php?id=${id}`, { method: 'DELETE' })
            .then(res => res.json())
            .then(data => {
                if (data.success) { alert(data.message); listarUsuarios(); }
                else alert(data.error);
            });
    }
}

// ==========================================
// CRUD CATEGORÍAS
// ==========================================
function listarCategorias() {
    fetch(`${API_URL}/categorias.php`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('tabla-categorias');
            tbody.innerHTML = '';
            if (data.data) {
                data.data.forEach(cat => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${cat.id}</td>
                            <td>${cat.nombre}</td>
                            <td>${cat.descripcion || '-'}</td>
                            <td>
                                <button class="btn-action btn-edit" onclick="editarCategoria(${cat.id}, '${cat.nombre}', '${cat.descripcion || ''}')">Editar</button>
                                <button class="btn-action btn-delete" onclick="eliminarCategoria(${cat.id})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                });
            }
        });
}

function guardarCategoria() {
    const id = document.getElementById('cat_id').value;
    const nombre = document.getElementById('cat_nombre').value;
    const descripcion = document.getElementById('cat_descripcion').value;

    if (!nombre) { alert('El nombre es obligatorio'); return; }

    fetch(`${API_URL}/categorias.php`, {
        method: id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, nombre: nombre, descripcion: descripcion })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) { alert(data.message); limpiarFormularioCat(); listarCategorias(); }
        else alert(data.error);
    });
}

function editarCategoria(id, nombre, descripcion) {
    document.getElementById('cat_id').value = id;
    document.getElementById('cat_nombre').value = nombre;
    document.getElementById('cat_descripcion').value = descripcion;
}

function eliminarCategoria(id) {
    if (confirm('¿Estás seguro de eliminar esta categoría? Se eliminarán sus exámenes también.')) {
        fetch(`${API_URL}/categorias.php?id=${id}`, { method: 'DELETE' })
            .then(res => res.json())
            .then(data => { if (data.success) listarCategorias(); });
    }
}

function limpiarFormularioCat() {
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_nombre').value = '';
    document.getElementById('cat_descripcion').value = '';
}

// ==========================================
// CRUD EXÁMENES
// ==========================================
function cargarSelectCategorias() {
    fetch(`${API_URL}/categorias.php`)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('ex_categoria');
            select.innerHTML = '<option value="">Seleccione una categoría</option>';
            if (data.data) {
                data.data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                });
            }
        });
}

function listarExamenes() {
    fetch(`${API_URL}/examenes.php`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('tabla-examenes');
            tbody.innerHTML = '';
            if (data.data) {
                data.data.forEach(ex => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${ex.id}</td>
                            <td>${ex.categoria_nombre}</td>
                            <td>${ex.titulo}</td>
                            <td>${ex.tiempo_limite_segundos > 0 ? (ex.tiempo_limite_segundos/60)+' min' : 'Sin límite'}</td>
                            <td>${ex.cantidad_preguntas > 0 ? ex.cantidad_preguntas + ' al azar' : 'Todas'}</td>
                            <td>${ex.nota_aprobacion}%</td>
                            <td>
                                <button class="btn-action btn-edit" onclick="editarExamen(${ex.id}, ${ex.categoria_id}, '${ex.titulo}', '${ex.descripcion || ''}', ${ex.tiempo_limite_segundos}, ${ex.cantidad_preguntas}, ${ex.nota_aprobacion})">Editar</button>
                                <button class="btn-action btn-delete" onclick="eliminarExamen(${ex.id})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                });
            }
        });
}

function guardarExamen() {
    const id = document.getElementById('ex_id').value;
    const categoria_id = document.getElementById('ex_categoria').value;
    const titulo = document.getElementById('ex_titulo').value;
    const descripcion = document.getElementById('ex_descripcion').value;
    const tiempo = document.getElementById('ex_tiempo').value || 0;
    const cantidad = document.getElementById('ex_cantidad').value || 0;
    const nota = document.getElementById('ex_nota').value || 60; // NUEVO

    if (!categoria_id || !titulo) { alert('Categoría y título son obligatorios'); return; }

    fetch(`${API_URL}/examenes.php`, {
        method: id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, categoria_id, titulo, descripcion, tiempo_limite_segundos: tiempo, cantidad_preguntas: cantidad, nota_aprobacion: nota })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) { alert(data.message); limpiarFormularioEx(); listarExamenes(); }
        else alert(data.error);
    });
}

function editarExamen(id, cat_id, titulo, descripcion, tiempo, cantidad, nota) {
    document.getElementById('ex_id').value = id;
    document.getElementById('ex_categoria').value = cat_id;
    document.getElementById('ex_titulo').value = titulo;
    document.getElementById('ex_descripcion').value = descripcion;
    document.getElementById('ex_tiempo').value = tiempo;
    document.getElementById('ex_cantidad').value = cantidad;
    document.getElementById('ex_nota').value = nota; // NUEVO
}
function eliminarExamen(id) {
    if (confirm('¿Eliminar este examen y todas sus preguntas?')) {
        fetch(`${API_URL}/examenes.php?id=${id}`, { method: 'DELETE' })
            .then(res => res.json())
            .then(data => { if (data.success) listarExamenes(); });
    }
}

function limpiarFormularioEx() {
    document.getElementById('ex_id').value = '';
    document.getElementById('ex_categoria').value = '';
    document.getElementById('ex_titulo').value = '';
    document.getElementById('ex_descripcion').value = '';
    document.getElementById('ex_tiempo').value = '';
    document.getElementById('ex_cantidad').value = '';
}

// ==========================================
// CRUD PREGUNTAS Y NAVEGACIÓN
// ==========================================
function cargarCategoriasPreguntas() {
    fetch(`${API_URL}/categorias.php`)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('pre_categoria');
            select.innerHTML = '<option value="">Seleccione una categoría</option>';
            if (data.data) {
                data.data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                });
            }
        });
}

function cargarExamenesPorCategoria() {
    const cat_id = document.getElementById('pre_categoria').value;
    const selectEx = document.getElementById('pre_examen');
    selectEx.innerHTML = '<option value="">Seleccione un examen</option>';
    document.getElementById('tabla-preguntas').innerHTML = '<tr><td colspan="4">Selecciona un examen para ver las preguntas.</td></tr>';
    
    preguntasCache = [];
    limpiarFormulario();
    actualizarBotonesNavegacion();

    if (!cat_id) return;

    fetch(`${API_URL}/examenes.php`)
        .then(res => res.json())
        .then(data => {
            if (data.data) {
                data.data.filter(ex => ex.categoria_id == cat_id).forEach(ex => {
                    selectEx.innerHTML += `<option value="${ex.id}">${ex.titulo}</option>`;
                });
            }
        });
}

function agregarOpcion(texto = '', correcta = false) {
    const cont = document.getElementById('contenedor-opciones');
    const div = document.createElement('div');
    div.className = 'op-row';
    div.innerHTML = `
        <input type="checkbox" class="op-correcta" ${correcta ? 'checked' : ''}>
        <input type="text" class="op-texto" placeholder="Opción de respuesta" value="${texto}">
        <button onclick="this.parentElement.remove()">X</button>
    `;
    cont.appendChild(div);
}

function subirImagen() {
    const fileInput = document.getElementById('pre_imagen');
    if (fileInput.files.length === 0) return;

    const formData = new FormData();
    formData.append('imagen', fileInput.files[0]);

    fetch('/api/admin/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('pre_imagen_url').value = data.ruta;
            document.getElementById('pre_imagen_preview').src = '/' + data.ruta;
            document.getElementById('pre_imagen_preview').style.display = 'block';
        } else {
            alert(data.error);
        }
    });
}

function listarPreguntas(mantenerIndex = -1) {
    const examen_id = document.getElementById('pre_examen').value;
    const tbody = document.getElementById('tabla-preguntas');
    tbody.innerHTML = '';
    
    if (!examen_id) {
        preguntasCache = [];
        tbody.innerHTML = '<tr><td colspan="4">Selecciona un examen para ver las preguntas.</td></tr>';
        actualizarBotonesNavegacion();
        return;
    }

    fetch(`${API_URL}/preguntas.php?examen_id=${examen_id}`)
        .then(res => res.json())
        .then(data => {
            if (data.data) {
                preguntasCache = data.data;
                data.data.forEach(pre => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${pre.id}</td>
                            <td>${pre.enunciado.substring(0, 50)}...</td>
                            <td>${pre.tipo_pregunta}</td>
                            <td>
                                <button class="btn-action btn-edit" onclick="editarPregunta(${pre.id})">Editar</button>
                                <button class="btn-action btn-delete" onclick="eliminarPregunta(${pre.id})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                });
                
                if (mantenerIndex !== -1 && mantenerIndex < preguntasCache.length) {
                    cargarPreguntaEnFormulario(mantenerIndex);
                }
            } else {
                preguntasCache = [];
            }
            actualizarBotonesNavegacion();
        });
}

function editarPregunta(id) {
    const index = preguntasCache.findIndex(p => parseInt(p.id) === parseInt(id));
    if (index === -1) return;
    cargarPreguntaEnFormulario(index);
}

function cargarPreguntaEnFormulario(index) {
    if (index < 0 || index >= preguntasCache.length) return;
    preguntaEditandoIndex = index;
    const pre = preguntasCache[index];

    document.getElementById('pre_id').value = pre.id;
    document.getElementById('pre_id_visible').innerText = pre.id;
    
    document.getElementById('pre_enunciado').value = pre.enunciado;
    document.getElementById('pre_explicacion').value = pre.explicacion || '';
    document.getElementById('pre_tipo').value = pre.tipo_pregunta;
    
    if (pre.multimedia_url) {
        document.getElementById('pre_imagen_url').value = pre.multimedia_url;
        document.getElementById('pre_imagen_preview').src = '/' + pre.multimedia_url;
        document.getElementById('pre_imagen_preview').style.display = 'block';
    } else {
        document.getElementById('pre_imagen_url').value = '';
        document.getElementById('pre_imagen_preview').style.display = 'none';
        document.getElementById('pre_imagen').value = '';
    }

    const cont = document.getElementById('contenedor-opciones');
    cont.innerHTML = '';
    if (pre.opciones.length === 0) {
        agregarOpcion();
        agregarOpcion();
    } else {
        pre.opciones.forEach(op => {
            agregarOpcion(op.texto, parseInt(op.es_correcta) === 1);
        });
    }

    actualizarBotonesNavegacion();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function limpiarFormulario() {
    preguntaEditandoIndex = -1;
    document.getElementById('pre_id').value = '';
    document.getElementById('pre_id_visible').innerText = '-';
    document.getElementById('pre_enunciado').value = '';
    document.getElementById('pre_explicacion').value = '';
    document.getElementById('pre_imagen_url').value = '';
    document.getElementById('pre_imagen').value = '';
    document.getElementById('pre_imagen_preview').style.display = 'none';
    document.getElementById('contenedor-opciones').innerHTML = '';
    agregarOpcion(); agregarOpcion();
    actualizarBotonesNavegacion();
}

function guardarPregunta() {
    const id = document.getElementById('pre_id').value;
    const examen_id = document.getElementById('pre_examen').value;
    const enunciado = document.getElementById('pre_enunciado').value;
    const explicacion = document.getElementById('pre_explicacion').value;
    const multimedia_url = document.getElementById('pre_imagen_url').value;
    const tipo = document.getElementById('pre_tipo').value;

    if (!examen_id || !enunciado) { alert('Selecciona un examen y escribe el enunciado'); return; }

    let opciones = [];
    let hayCorrecta = false;
    document.querySelectorAll('#contenedor-opciones > div').forEach(div => {
        const texto = div.querySelector('.op-texto').value;
        const es_correcta = div.querySelector('.op-correcta').checked;
        if (es_correcta) hayCorrecta = true;
        if (texto) opciones.push({ texto, es_correcta });
    });

    if (opciones.length < 2) { alert('Agrega al menos 2 opciones.'); return; }
    if (!hayCorrecta) { alert('Debes marcar al menos una opción como correcta.'); return; }

    const metodo = id ? 'PUT' : 'POST';
    const datos = { examen_id, enunciado, explicacion, multimedia_url, tipo_pregunta: tipo, opciones };
    if (id) datos.id = id;

    fetch(`${API_URL}/preguntas.php`, {
        method: metodo,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            if (id) {
                listarPreguntas(preguntaEditandoIndex);
            } else {
                listarPreguntas();
                limpiarFormulario();
            }
        } else {
            alert(data.error);
        }
    });
}

function eliminarPregunta(id) {
    if (confirm('¿Eliminar esta pregunta y sus opciones?')) {
        fetch(`${API_URL}/preguntas.php?id=${id}`, { method: 'DELETE' })
            .then(res => res.json())
            .then(data => { if (data.success) listarPreguntas(); });
    }
}

// ==========================================
// FUNCIONES DE NAVEGACIÓN
// ==========================================
function actualizarBotonesNavegacion() {
    const total = preguntasCache.length;
    const isFirst = preguntaEditandoIndex <= 0;
    const isLast = preguntaEditandoIndex >= total - 1;

    // Usamos querySelector para encontrar los botones por su atributo onclick
    const btnPrimera = document.querySelector('button[onclick="editarPreguntaPrimera()"]');
    const btnAnterior = document.querySelector('button[onclick="editarPreguntaAnterior()"]');
    const btnSiguiente = document.querySelector('button[onclick="editarPreguntaSiguiente()"]');
    const btnUltima = document.querySelector('button[onclick="editarPreguntaUltima()"]');

    if(btnPrimera) btnPrimera.disabled = isFirst || total === 0;
    if(btnAnterior) btnAnterior.disabled = isFirst || total === 0;
    if(btnSiguiente) btnSiguiente.disabled = isLast || total === 0;
    if(btnUltima) btnUltima.disabled = isLast || total === 0;
}

function editarPreguntaPrimera() { 
    if (preguntasCache.length > 0) cargarPreguntaEnFormulario(0); 
}

function editarPreguntaAnterior() { 
    if (preguntaEditandoIndex > 0) cargarPreguntaEnFormulario(preguntaEditandoIndex - 1); 
}

function editarPreguntaSiguiente() { 
    if (preguntaEditandoIndex < preguntasCache.length - 1) cargarPreguntaEnFormulario(preguntaEditandoIndex + 1); 
}

function editarPreguntaUltima() { 
    if (preguntasCache.length > 0) cargarPreguntaEnFormulario(preguntasCache.length - 1); 
}

// ==========================================
// IMPORTACIÓN JSON
// ==========================================
function importarJSON() {
    const fileInput = document.getElementById('importJsonFile');
    const examen_id = document.getElementById('pre_examen').value;

    if (!examen_id) { alert('Primero selecciona un examen en el menú desplegable de arriba.'); return; }
    if (fileInput.files.length === 0) { alert('Selecciona un archivo .json para importar.'); return; }

    const file = fileInput.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        try {
            let contenido = e.target.result;
            
            let inicio = contenido.indexOf('[');
            let fin = contenido.lastIndexOf(']');
            
            if (inicio === -1 || fin === -1) {
                throw new Error("No se encontró la estructura de array [ ] en el archivo.");
            }
            
            contenido = contenido.substring(inicio, fin + 1);
            contenido = contenido.replace(/([{,]\s*)(\w+)\s*:/g, '$1"$2":');
            contenido = contenido.replace(/,\s*([}\]])/g, '$1');
            
            let data = JSON.parse(contenido);
            
            if (!Array.isArray(data)) throw new Error("El archivo no contiene un array de preguntas.");

            fetch('/api/admin/importarPreguntas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ examen_id: examen_id, preguntas: data })
            })
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    alert(resData.message);
                    listarPreguntas();
                    fileInput.value = '';
                } else {
                    alert(resData.error);
                }
            });

        } catch (error) {
            alert("Error al leer el JSON: " + error.message);
        }
    };
    reader.readAsText(file);
}

// ==========================================
// MODAL SIMULADOR ADMIN
// ==========================================
function abrirModalSimulador() {
    document.getElementById('modalSimulador').style.display = 'flex';
    cargarCategoriasModal();
}

function cerrarModalSimulador() {
    document.getElementById('modalSimulador').style.display = 'none';
    document.getElementById('modal_examen').innerHTML = '<option value="">Seleccione un examen</option>';
}

function cargarCategoriasModal() {
    fetch(`${API_URL}/categorias.php`)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('modal_categoria');
            select.innerHTML = '<option value="">Seleccione una categoría</option>';
            if (data.data) {
                data.data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                });
            }
        });
}

function cargarExamenesModal() {
    const cat_id = document.getElementById('modal_categoria').value;
    const selectEx = document.getElementById('modal_examen');
    selectEx.innerHTML = '<option value="">Cargando exámenes...</option>';

    if (!cat_id) {
        selectEx.innerHTML = '<option value="">Seleccione un examen</option>';
        return;
    }

    fetch(`${API_URL}/examenes.php`)
        .then(res => res.json())
        .then(data => {
            selectEx.innerHTML = '<option value="">Seleccione un examen</option>';
            if (data.data) {
                data.data.filter(ex => ex.categoria_id == cat_id).forEach(ex => {
                    selectEx.innerHTML += `<option value="${ex.id}">${ex.titulo}</option>`;
                });
                
                if (selectEx.options.length === 1) {
                    selectEx.innerHTML = '<option value="">No hay exámenes en esta categoría</option>';
                }
            }
        });
}

function confirmarInicioSimulador() {
    const examen_id = document.getElementById('modal_examen').value;
    if (!examen_id) {
        alert('Por favor selecciona un examen válido.');
        return;
    }
    cerrarModalSimulador();
    window.open('../simulador/index.html?id=' + examen_id, '_blank');
}

// ==========================================
// SESIÓN
// ==========================================
function cerrarSesion() {
    fetch('/api/auth/logout.php')
        .then(res => res.json())
        .then(() => window.location.href = '../index.html');
}