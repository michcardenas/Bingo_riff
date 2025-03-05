@extends('layouts.admin')

@section('title', 'Gestión de Enlaces')

@section('content')
<div class="container-fluid p-0">
    <!-- Encabezado Panel verde -->
    <div style="background-color: #00bf63;" class="text-white p-3 mb-4">
        <h1 class="display-4 text-center m-0">Enlaces del Sistema</h1>
    </div>

    <!-- Formulario para gestionar enlaces -->
    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card bg-dark text-white">
                    <div class="card-header bg-secondary">
                        <h4 class="m-0">Configuración de Enlaces</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('enlaces.update') }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <div class="mb-4">
                                <label class="form-label fw-bold">Número de Contacto</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="numero_contacto" 
                                    value="{{ $enlaces->numero_contacto ?? '' }}"
                                    placeholder="Ej: 3235903774">
                                <small class="text-muted">Este número se mostrará en la vista principal para pagos.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Enlace Video 1</label>
                                <input 
                                    type="url" 
                                    class="form-control" 
                                    name="video_1" 
                                    value="{{ $enlaces->video_1 ?? '' }}"
                                    placeholder="https://youtube.com/watch?v=...">
                                <small class="text-muted">URL del video tutorial principal.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Enlace Video 2</label>
                                <input 
                                    type="url" 
                                    class="form-control" 
                                    name="video_2" 
                                    value="{{ $enlaces->video_2 ?? '' }}"
                                    placeholder="https://youtube.com/watch?v=...">
                                <small class="text-muted">URL del video tutorial secundario (opcional).</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Grupo de WhatsApp</label>
                                <input 
                                    type="url" 
                                    class="form-control" 
                                    name="grupo_whatsapp" 
                                    value="{{ $enlaces->grupo_whatsapp ?? '' }}"
                                    placeholder="https://chat.whatsapp.com/...">
                                <small class="text-muted">Enlace de invitación al grupo de WhatsApp.</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button 
                                    type="submit" 
                                    class="btn btn-success px-4 py-2" 
                                    style="background-color: #00bf63;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save me-2" viewBox="0 0 16 16">
                                        <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v7.293l2.646-2.647a.5.5 0 0 1 .708.708l-3.5 3.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L7.5 9.293V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                                    </svg>
                                    Guardar Enlaces
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if(session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection