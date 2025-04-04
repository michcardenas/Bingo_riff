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
        
// Función para buscar participante por número de serie
function buscarParticipante(numeroSerie) {
    const resultadosDiv = document.getElementById('searchResults');
    if (!resultadosDiv) return;
    
    // Si el bingo está archivado, no mostrar datos
    if (bingo.archivado) {
        resultadosDiv.innerHTML = '';
        return;
    }
    
    // Formatear el número de serie con ceros a la izquierda
    const serieFormateada = numeroSerie.padStart(6, '0');
    
    // Mostrar indicador de carga
    resultadosDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-info" role="status">
                <span class="visually-hidden">Buscando...</span>
            </div>
            <p class="mt-2">Buscando participante con serie: ${serieFormateada}...</p>
        </div>
    `;
    
    // Capturar errores de red y servidor adecuadamente
    fetch(`/admin/api/bingos/${bingo.id}/participantes/serie/${serieFormateada}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Respuesta del servidor:', response.status);
        
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('No se encontró ningún participante con ese número de serie');
            } else {
                return response.text().then(text => {
                    console.error('Error respuesta:', text);
                    throw new Error(`Error al buscar el participante (${response.status})`);
                });
            }
        }
        return response.json();
    })
    .then(participante => {
        console.log('Datos del participante:', participante);
        
        // Construir HTML para mostrar los datos del participante
        let html = `
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Telefono</th>
                        <th>#Cartón</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${participante.nombre}</td>
                        <td>${participante.telefono}</td>
                        <td>${participante.carton}</td>
                    </tr>
                </tbody>
            </table>
        `;
        
        // Si ya es ganador, mostrar el premio
        if (participante.ganador) {
            html += `
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-trophy"></i> 
                    Este participante ya fue marcado como ganador del premio: 
                    <strong>${participante.premio}</strong>
                </div>
            `;
        } else {
            // Agregar campo para el premio y botón de ganador
            html += `
                <form id="ganadorForm">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" name="premio" id="premioInput" class="form-control" 
                            placeholder="Escribir premio ganado" value="">
                        <button type="submit" class="btn btn-success">ganador</button>
                    </div>
                </form>
            `;
        }
        
        resultadosDiv.innerHTML = html;
        
        // Configurar evento para el formulario de ganador si existe
        const ganadorForm = document.getElementById('ganadorForm');
        if (ganadorForm) {
            ganadorForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const premio = document.getElementById('premioInput').value.trim();
                if (premio === "") {
                    alert("Por favor, ingrese el premio ganado");
                    return;
                }
                
                // Crear formData y agregar el token CSRF
                const formData = new FormData();
                formData.append('premio', premio);
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'PATCH');
                
                // Enviar petición AJAX
                fetch(`/admin/api/bingos/${bingo.id}/participantes/${participante.id}/ganador`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`¡${participante.nombre} ha sido marcado como ganador de: ${premio}!`);
                        // Volver a buscar para actualizar la vista
                        buscarParticipante(numeroSerie);
                    } else {
                        alert(data.error || 'Ocurrió un error al marcar el ganador');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error al marcar el ganador');
                });
            });
        }
    })
    .catch(error => {
        console.error('Error en la búsqueda:', error);
        resultadosDiv.innerHTML = `
            <div class="alert alert-warning text-center" role="alert">
                <i class="bi bi-exclamation-triangle"></i> 
                ${error.message || 'No se encontró ningún participante con ese número de serie'}
            </div>
        `;
    });
}
    });
</script>
@endsection