let examenData = [];
let respuestasUsuario = {};
let preguntaActual = 0;
let timerInterval;

document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const examenId = params.get('id');

    const loader = document.getElementById('loader-examen');
    const contenido = document.getElementById('examen-contenido');

    // 1. OBTENER DATOS DEL USUARIO LOGUEADO
    fetch('../api/auth/validarSesion.php')
        .then(res => res.json())
        .then(userData => {
            if (userData.logueado) {
                const userDiv = document.getElementById('sim-info-user');
                if(userDiv) userDiv.innerText = `Usuario: ${userData.nombre}`;
            }
        })
        .catch(err => console.log('Error sesión:', err));

    if (!examenId) {
        loader.innerHTML = '<h2 style="color:#dc2626;">Error: Examen no especificado.</h2>';
        return;
    }

    // 2. OBTENER DATOS DEL EXAMEN
    fetch(`../api/examenes/cargarExamen.php?examen_id=${examenId}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                loader.innerHTML = `<h2 style="color:#dc2626;">${data.error}</h2>`;
                return;
            }
            
            examenData = data;
            
            // LLENAR EL TÍTULO DEL EXAMEN EN LA CABECERA
            const examDiv = document.getElementById('sim-info-exam');
            if(examDiv) examDiv.innerText = data.examen.titulo;
            
            setTimeout(() => {
                iniciarTemporizador(data.examen.tiempo_limite_segundos);
                renderPregunta();
                
                loader.style.display = 'none';
                contenido.style.display = 'block';
            }, 1000);
        })
        .catch(err => {
            console.error('Error:', err);
            loader.innerHTML = '<h2 style="color:#dc2626;">Error al cargar el examen.</h2>';
        });
});

function iniciarTemporizador(segundos) {
    const timerDiv = document.getElementById('temporizador');
    if (!timerDiv) return;

    if (segundos <= 0) {
        timerDiv.innerText = "Sin límite de tiempo";
        return;
    }

    let tiempoRestante = segundos;
    
    timerInterval = setInterval(() => {
        let min = Math.floor(tiempoRestante / 60);
        let sec = tiempoRestante % 60;
        timerDiv.innerText = `${min}:${sec < 10 ? '0' + sec : sec}`;
        
        if (tiempoRestante <= 0) {
            clearInterval(timerInterval);
            alert("¡Se acabó el tiempo!");
            finalizarExamen();
        }
        tiempoRestante--;
    }, 1000);
}

function renderPregunta() {
    const cont = document.getElementById('area-dinamica'); 
    if(!cont) return;
    
    const pre = examenData.preguntas[preguntaActual];
    const total = examenData.preguntas.length;

    let opcionesHTML = '';
    
    if (pre.tipo_pregunta === 'multiple') {
        pre.opciones.forEach(op => {
            opcionesHTML += `
                <label class="option">
                    <input type="checkbox" name="preg_${pre.id}" value="${op.id}" ${respuestasUsuario[pre.id]?.includes(op.id) ? 'checked' : ''} onchange="guardarMultiple(${pre.id})">
                    ${op.texto}
                </label>
            `;
        });
    } else {
        pre.opciones.forEach(op => {
            const checked = respuestasUsuario[pre.id] && respuestasUsuario[pre.id].includes(op.id) ? 'checked' : '';
            opcionesHTML += `
                <label class="option">
                    <input type="radio" name="preg_${pre.id}" value="${op.id}" ${checked} onchange="guardarRespuesta(${pre.id}, ${op.id})">
                    ${op.texto}
                </label>
            `;
        });
    }

    const isPrimera = preguntaActual === 0;
    const isUltima = preguntaActual === total - 1;
    const disabledPrimera = isPrimera ? 'disabled' : '';
    const disabledUltima = isUltima ? 'disabled' : '';

    cont.innerHTML = `
        <h3>Pregunta ${preguntaActual + 1} de ${total}</h3>
        <div class="question-card">
            ${pre.multimedia_url ? `<img src="https://exitoimpulso.uy/${pre.multimedia_url}" style="max-width:100%; border-radius:8px; margin-bottom:15px;">` : ''}
            <h4>${pre.enunciado}</h4>
            <div style="margin-top: 15px;">
                ${opcionesHTML}
            </div>
        </div>
        
        <div class="nav-buttons">
            <button class="btn-nav" ${disabledPrimera} onclick="irAPrimera()">&lt;&lt; Primera</button>
            <button class="btn-nav" ${disabledPrimera} onclick="anteriorPregunta()">&lt; Anterior</button>
            
            <div style="flex-grow: 1;"></div>
            
            ${isUltima 
                ? '<button class="btn-nav btn-finalizar" onclick="finalizarExamen()">Finalizar Examen</button>'
                : `<button class="btn-nav" onclick="siguientePregunta()">Siguiente &gt;</button>`
            }
            <button class="btn-nav" ${disabledUltima} onclick="irAUltima()">Última &gt;&gt;</button>
        </div>
    `;
}

function irAPrimera() { preguntaActual = 0; renderPregunta(); window.scrollTo(0, 0); }
function anteriorPregunta() { if (preguntaActual > 0) { preguntaActual--; renderPregunta(); window.scrollTo(0, 0); } }
function siguientePregunta() { if (preguntaActual < examenData.preguntas.length - 1) { preguntaActual++; renderPregunta(); window.scrollTo(0, 0); } }
function irAUltima() { preguntaActual = examenData.preguntas.length - 1; renderPregunta(); window.scrollTo(0, 0); }

function guardarRespuesta(pregId, opId) { respuestasUsuario[pregId] = [opId]; }
function guardarMultiple(pregId) {
    let seleccionadas = [];
    document.querySelectorAll(`input[name="preg_${pregId}"]:checked`).forEach(el => { seleccionadas.push(parseInt(el.value)); });
    respuestasUsuario[pregId] = seleccionadas;
}

function finalizarExamen() {
    clearInterval(timerInterval);
    const cont = document.getElementById('area-dinamica'); 
    if(cont) {
        cont.innerHTML = `
            <div class="question-card" style="text-align: center;">
                <h2>Examen Finalizado</h2>
                <p>Has respondido ${Object.keys(respuestasUsuario).length} de ${examenData.preguntas.length} preguntas.</p>
                <button class="btn-nav btn-finalizar" onclick="corregirExamen()">Ver Corrección y Fundamentos</button>
            </div>
        `;
    }
}

function corregirExamen() {
    const examenId = examenData.examen.id;
    
    const todosLosIds = examenData.preguntas.map(p => p.id);
    
    fetch('../api/examenes/guardarResultado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            examen_id: examenId, 
            respuestas: respuestasUsuario,
            preguntas_ids: todosLosIds 
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderCorreccion(data);
        } else {
            alert(data.error || "Error al corregir el examen.");
        }
    });
}

function renderCorreccion(data) {
    const cont = document.getElementById('area-dinamica'); 
    if(!cont) return;

    const timerDiv = document.getElementById('temporizador');
    if (timerDiv) timerDiv.style.display = 'none'; 

    let detalleHTML = '';

    data.detalle.forEach((pre, index) => {
        let opcionesHTML = '';
        
        pre.opciones.forEach(op => {
            let clase = 'opcion-normal';
            let icono = '';
            const marcadaPorUsuario = pre.respuestas_usuario.includes(parseInt(op.id));
            const esCorrectaDB = op.es_correcta == 1;
            const sinRespuesta = pre.respuestas_usuario.length === 0;

            if (esCorrectaDB) {
                if (sinRespuesta) {
                    clase = 'opcion-amarilla';
                    icono = ' ⚠️ (Correcta - No respondida)';
                } else {
                    clase = 'opcion-correcta';
                    icono = ' ✅ (Correcta)';
                }
            } else if (marcadaPorUsuario && !esCorrectaDB) {
                clase = 'opcion-incorrecta';
                icono = ' ❌ (Tu respuesta)';
            }

            opcionesHTML += `<div class="${clase}">${op.texto} ${icono}</div>`;
        });

        detalleHTML += `
            <div class="question-card">
                <h4>Pregunta ${index + 1}: ${pre.enunciado}</h4>
                ${pre.multimedia_url ? `<img src="https://exitoimpulso.uy/${pre.multimedia_url}" style="max-width:100%; border-radius:8px; margin-bottom:15px;">` : ''}
                <div style="margin: 15px 0;">${opcionesHTML}</div>
                <div class="fundamento-box">
                    <strong>📚 Fundamento / Explicación:</strong><br>
                    ${pre.explicacion || 'No hay explicación proporcionada para esta pregunta.'}
                </div>
            </div>
        `;
    });

    // AQUÍ ESTÁN LAS VARIABLES QUE FALTABAN
    const estadoExamen = data.aprobado ? 'APROBADO' : 'REPROBADO';
    const colorEstado = data.aprobado ? 'green' : 'red';

    let botonPDF = '';
    if (data.aprobado) {
        botonPDF = `<button class="btn-nav btn-pdf" onclick="generarPDF(${data.puntaje}, ${data.correctas}, ${data.total}, '${data.codigo_verificacion}')">Descargar Comprobante (PDF)</button>`;
    } else {
        botonPDF = `<p style="color:#dc2626; font-weight:600;">Debes aprobar (${data.nota_minima}% mín.) para descargar el comprobante.</p>`;
    }

    cont.innerHTML = `
        <div class="resumen-card" style="padding: 40px; border: 2px solid #1E3A8A;">
            <img src="../assets/img/logo.png" style="width: 80px; margin-bottom: 15px;">
            <h2 style="color: #1E3A8A; margin: 0 0 5px 0;">Resultado del Simulacro</h2>
            <p style="font-size: 18px; color: #6b7280; margin: 0 0 20px 0;"><strong>${examenData.examen.titulo}</strong></p>
            <h1 style="font-size: 64px; color: ${colorEstado}; margin: 10px 0;">${data.puntaje}%</h1>
            <h3 style="font-size: 24px; color: ${colorEstado}; margin: 0 0 20px 0;">${estadoExamen}</h3>
            <p style="font-size: 16px; color: #4b5563;">Respuestas correctas: <strong>${data.correctas} de ${data.total}</strong></p>
            <div style="display:flex; gap:10px; justify-content:center; align-items:center; margin-top:25px;">
                ${botonPDF}
                <button class="btn-nav btn-finalizar" onclick="window.location.href='../opciones del portal/index.html'">Volver al Portal</button>
            </div>
        </div>
        <hr style="margin: 30px 0;">
        <h2 style="color: #1E3A8A; text-align: center;">Revisión detallada</h2>
        ${detalleHTML}
    `;
    
    window.scrollTo(0, 0);
}

// ==========================================
// GENERACIÓN DE PDF
// ==========================================
function generarPDF(puntaje, correctas, total, codigo) {
    fetch('../api/auth/validarSesion.php')
        .then(res => res.json())
        .then(data => {
            if (data.logueado) {
                document.getElementById('pdf-user').innerText = data.nombre;
                document.getElementById('pdf-exam').innerText = examenData.examen.titulo;
                document.getElementById('pdf-score').innerText = puntaje + '%';
                document.getElementById('pdf-correct').innerText = correctas + ' de ' + total + ' correctas';
                document.getElementById('pdf-date').innerText = new Date().toLocaleDateString('es-UY');

                const urlVerificacion = `https://exitoimpulso.uy/verificar/?codigo=${codigo}`;
                const qrImg = document.getElementById('pdf-qr-img');
                
                // 1. Asignamos la URL del QR para que empiece a descargar la imagen
                qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(urlVerificacion)}`;

                const element = document.getElementById('template-pdf');
                const opt = {
                    margin:       [10, 10, 10, 10], 
                    filename:     'Comprobante_' + examenData.examen.titulo + '.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, allowTaint: true, backgroundColor: '#ffffff' },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak:    { mode: ['avoid-all'] } 
                };

                // 2. LA MAGIA AQUÍ: Buscamos TODAS las imágenes del PDF (Logo, Firma, QR)
                const images = element.querySelectorAll('img');
                const promises = [];

                images.forEach(img => {
                    // Si ya está cargada o no tiene src, la ignoramos
                    if (!img.src || img.complete) return;
                    
                    // Creamos una promesa por cada imagen que falte cargar
                    promises.push(new Promise(resolve => {
                        img.onload = resolve;
                        img.onerror = resolve; // Si falla, igual dejamos pasar para no trabar el PDF
                    }));
                });

                // 3. Cuando TODAS las imágenes terminen de cargar, esperamos 500ms y descargamos
                Promise.all(promises).then(() => {
                    setTimeout(() => {
                        html2pdf().set(opt).from(element).save();
                    },1500); // Medio segundo extra de margen de seguridad
                });
            }
        });
}