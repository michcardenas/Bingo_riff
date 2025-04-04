@extends('layouts.admin')

@section('content')
<div class="container-fluid p-0">
    <!-- Encabezado Panel verde -->
    <div style="background-color: #00bf63;" class="text-white p-3 mb-4">
        <h1 class="display-4 text-center m-0">Bingo RIFFY, cosas para arreglar y agregar.</h1>
    </div>

    <!-- Buscador de participantes por número de serie -->
    <div class="container mb-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <h3 class="card-title text-center" style="color: #ff7b00;">Buscar numero de serie</h3>
                        
                        <div class="mb-3">
                            <input type="text" id="serieSearch" class="form-control form-control-lg text-center" 
                                placeholder="000000" maxlength="6">
                        </div>
                        
                        <div id="searchResults" class="mt-4">
                            <!-- Los resultados de búsqueda se mostrarán aquí -->
                        </div>
                        
                        <div id="statusMessage" class="text-center mt-3 text-muted fst-italic">
                            <!-- Mensajes sobre el estado del bingo se mostrarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Estado del bingo (desde el controlador)
        const bingo = {
            id: "{{ $bingo->id }}",
            estado: "{{ $bingo->estado }}",
            activo: "{{ $bingo->estado }}" === "abierto",
            archivado: "{{ $bingo->estado }}" === "archivado"
        };
        
        // Desactivar campo de búsqueda si el bingo está archivado
        const serieInput = document.getElementById('serieSearch');
        if (serieInput && bingo.archivado) {
            serieInput.disabled = true;
            serieInput.placeholder = "Bingo archivado - Búsqueda no disponible";
        }
        
        // Actualizar mensaje de estado inicial
        actualizarMensajeEstado();
        
        // Configurar evento de búsqueda
        if (serieInput && !bingo.archivado) {
            serieInput.addEventListener('input', function() {
                const numeroSerie = this.value.trim();
                if (numeroSerie.length >= 6) {
                    buscarParticipante(numeroSerie);
                } else if (numeroSerie.length === 0) {
                    document.getElementById('searchResults').innerHTML = '';
                }
            });
            
            serieInput.addEventListener('keypress', function(e) {
                if (e.key === "Enter") {
                    const numeroSerie = this.value.trim();
                    if (numeroSerie.length > 0) {
                        buscarParticipante(numeroSerie);
                    }
                }
            });
        }
        
        // Función para actualizar el mensaje de estado
        function actualizarMensajeEstado() {
            const statusDiv = document.getElementById('statusMessage');
            if (!statusDiv) return;
            
            if (bingo.archivado) {
                statusDiv.textContent = "Este bingo ha sido archivado. No se pueden mostrar datos hasta que se cree uno nuevo.";
                statusDiv.classList.add('text-warning');
            } else if (!bingo.activo) {
                statusDiv.textContent = "El bingo está cerrado, pero aún puedes consultar y actualizar datos.";
                statusDiv.classList.add('text-danger');
            } else {
                statusDiv.textContent = "El bingo está activo y en juego.";
                statusDiv.classList.add('text-success');
            }
        }
        
// Función para buscar participante por número de serie con URL correcta
function buscarParticipante(numeroSerie) {
    console.group('🔍 INICIANDO BÚSQUEDA DE PARTICIPANTE');
    console.log('Número de serie original:', numeroSerie);
    
    const resultadosDiv = document.getElementById('searchResults');
    console.log('Elemento para resultados encontrado:', !!resultadosDiv);
    
    if (!resultadosDiv) {
        console.error('No se encontró el elemento #searchResults');
        console.groupEnd();
        return;
    }
    
    // Si el bingo está archivado, no mostrar datos
    console.log('Estado del bingo:', bingo);
    if (bingo.archivado) {
        console.log('🚫 Bingo archivado, cancelando búsqueda');
        resultadosDiv.innerHTML = '';
        console.groupEnd();
        return;
    }
    
    // Formatear el número de serie con ceros a la izquierda
    const serieFormateada = numeroSerie.padStart(6, '0');
    console.log('Serie con formato (6 dígitos):', serieFormateada);
    
    // Mostrar indicador de carga
    console.log('Mostrando indicador de carga');
    resultadosDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-info" role="status">
                <span class="visually-hidden">Buscando...</span>
            </div>
            <p class="mt-2">Buscando participante con serie: ${serieFormateada}...</p>
        </div>
    `;
    
    // URL CORREGIDA de la petición - eliminamos el prefijo 'admin/' que ya está en la ruta base
    const apiUrl = `/api/bingos/${bingo.id}/participantes/serie/${serieFormateada}`;
    console.log('URL de la API:', apiUrl);
    
    // Capturar errores de red y servidor adecuadamente
    console.log('⏳ Iniciando petición fetch');
    fetch(apiUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('📥 Respuesta recibida del servidor');
        console.log('Status code:', response.status);
        console.log('Status text:', response.statusText);
        console.log('Headers:', [...response.headers.entries()]);
        console.log('Response type:', response.type);
        console.log('Response URL:', response.url);
        
        if (!response.ok) {
            console.error('🚨 Respuesta con error:', response.status);
            
            if (response.status === 404) {
                console.warn('Participante no encontrado (404)');
                throw new Error('No se encontró ningún participante con ese número de serie');
            } else {
                return response.text().then(text => {
                    console.error('Texto de error:', text);
                    try {
                        // Intentar parsear como JSON por si acaso
                        const jsonError = JSON.parse(text);
                        console.error('Error como JSON:', jsonError);
                        throw new Error(jsonError.error || `Error del servidor (${response.status})`);
                    } catch (e) {
                        // Si no es JSON, usar el texto tal cual
                        throw new Error(`Error al buscar el participante (${response.status}): ${text.substring(0, 100)}...`);
                    }
                });
            }
        }
        
        console.log('✅ Respuesta exitosa, procesando JSON');
        return response.json();
    })
    .then(participante => {
        console.log('📋 Datos del participante recibidos:', participante);
        
        // El resto de la función permanece igual...
        // ...
    })
    .catch(error => {
        console.error('🚨 Error en la búsqueda:', error);
        console.error('Stack trace:', error.stack);
        
        resultadosDiv.innerHTML = `
            <div class="alert alert-warning text-center" role="alert">
                <i class="bi bi-exclamation-triangle"></i> 
                ${error.message || 'No se encontró ningún participante con ese número de serie'}
            </div>
        `;
    })
    .finally(() => {
        console.groupEnd(); // Cerrar grupo principal de búsqueda
    });
}
    });
</script>
@endsection