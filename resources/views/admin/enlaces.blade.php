@extends('layouts.admin')

@section('content')
<div class="container-fluid p-0">
    <!-- Encabezado Panel verde -->
    <div style="background-color: #00bf63;" class="text-white p-3 mb-4">
        <h1 class="display-4 text-center m-0">Enlaces del Sistema</h1>
    </div>

    <!-- Números de contacto destacados -->
    <div class="container mb-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card bg-dark text-white">
                    <div class="card-header bg-secondary">
                        <h4 class="m-0">Números de Contacto</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6 text-center">
                                <h5 class="text-muted mb-2">Contacto de Pagos</h5>
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-credit-card me-2" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z" />
                                        <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z" />
                                    </svg>
                                    {{ $enlaces->numero_contacto ?? 'No configurado' }}
                                </h3>
                            </div>
                            <div class="col-md-6 text-center">
                                <h5 class="text-muted mb-2">Atención al Cliente</h5>
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-telephone-fill me-2" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z" />
                                    </svg>
                                    {{ $enlaces->telefono_atencion ?? 'No configurado' }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                                <label class="form-label fw-bold">Número de Contacto para Pagos</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="numero_contacto"
                                    value="{{ $enlaces->numero_contacto ?? '' }}"
                                    placeholder="Ej: 3235903774">
                                <small class="text-muted">Este número se mostrará en la vista principal para pagos.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Teléfono de Atención al Cliente</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="telefono_atencion"
                                    value="{{ $enlaces->telefono_atencion ?? '' }}"
                                    placeholder="Ej: 3001234567">
                                <small class="text-muted">Número de atención al cliente (se guardará con prefijo +57).</small>
                            </div>

                            <!-- Nuevos campos para métodos de pago -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Número Nequi</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="numero_nequi"
                                    value="{{ $enlaces->numero_nequi ?? '' }}"
                                    placeholder="Ej: 3001234567">
                                <small class="text-muted">Número de Nequi para recibir pagos.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Número Daviplata</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="numero_daviplata"
                                    value="{{ $enlaces->numero_daviplata ?? '' }}"
                                    placeholder="Ej: 3001234567">
                                <small class="text-muted">Número de Daviplata para recibir pagos.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Número Transfiya</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="numero_transfiya"
                                    value="{{ $enlaces->numero_transfiya ?? '' }}"
                                    placeholder="Ej: 3001234567">
                                <small class="text-muted">Número de Transfiya para recibir pagos.</small>
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
                                        <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v7.293l2.646-2.647a.5.5 0 0 1 .708.708l-3.5 3.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L7.5 9.293V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z" />
                                    </svg>
                                    Guardar Enlaces
                                </button>
                            </div>

                            <!-- Agregar después del último input en el formulario, antes del botón de guardar -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        name="mostrar_boton_whatsapp"
                                        id="mostrarBotonWhatsapp"
                                        value="1"
                                        {{ ($enlaces->mostrar_boton_whatsapp ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="mostrarBotonWhatsapp">
                                        Mostrar botón flotante de WhatsApp
                                    </label>
                                </div>
                                <small class="text-muted">Activa o desactiva el botón flotante de WhatsApp en la página principal.</small>
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