<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>RIFFY Bingo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5 (CDN) -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .paso-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .paso-title {
            color: #fa9044;
            font-weight: bold;
            letter-spacing: 0.5px;
            font-size: 28px;
        }

        p {
            font-size: 18px;
            margin-bottom: 0.8rem;
        }

        .logo-container {
            height: 80px;
            display: flex;
            align-items: center;
        }

        .logo-container img {
            height: 100%;
            width: auto;
            display: block;
        }

        .btn-naranja {
            background-color: #fa9044;
            border-color: #fa9044;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 20px;
        }

        .text-verde {
            color: #00bf63;
        }

        .text-amarillo {
            color: #FFD700;
            font-size: 20px;
        }
        
        .text-naranja {
            color: #fa9044;
        }

        .bingo-container {
            background-color: #121212;
            border-radius: 8px;
        }

        .form-control {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            padding: 10px;
        }

        .form-label {
            font-size: 18px;
        }

        .nav-link-custom {
            font-size: 16px;
        }

        .btn {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 18px;
        }

        .border-naranja {
            border: 1px dashed #ffffff !important;
        }

        .etiqueta-archivo {
            cursor: pointer;
            color: #ffffff;
            font-size: 18px;
        }

        /* Contenedor de vista previa con grid para agrupar en cuadraditos */
        #previewContainer {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        /* Imagenes de vista previa en cuadraditos */
        .img-preview {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border: 2px solid #fa9044;
            border-radius: 5px;
            position: relative;
        }
        
        /* Estilo para bingo cerrado */
        .bingo-cerrado-container {
            background-color: #121212;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            display: none;
        }

        .bingo-cerrado-titulo {
            color: #fa9044;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 0.75rem;
            line-height: 1.2;
            text-align: center;
        }

        .bingo-cerrado-fecha {
            color: #ffffff;
            font-size: 20px;
            margin-bottom: 1rem;
        }

        .bingo-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        @media (min-width: 768px) {
            .bingo-container {
                max-width: 500px;
                margin: 0 auto;
            }
            
            .bingo-cerrado-container {
                max-width: 500px;
                margin: 0 auto;
            }

            body {
                font-size: 20px;
            }

            p {
                font-size: 20px;
            }

            .paso-title {
                font-size: 32px;
            }
            
            .bingo-cerrado-titulo {
                font-size: 32px;
            }

            .form-control,
            .form-label,
            .etiqueta-archivo {
                font-size: 20px;
            }

            .text-amarillo,
            .btn-naranja {
                font-size: 22px;
            }
            
            .bingo-cerrado-fecha {
                font-size: 22px;
            }

            .logo-container {
                height: 50px;
            }
        }

        @media (min-width: 992px) {
            .bingo-container {
                max-width: 600px;
            }
            
            .bingo-cerrado-container {
                max-width: 600px;
            }
        }

        /* Botón de borrar sobre cada preview */
        .delete-btn {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #fa9044;
            border: none;
            color: #fff;
            font-size: 14px;
            padding: 1px 6px;
            cursor: pointer;
            border-radius: 50%;
        }

        /* Contenedor de cada preview */
        .preview-item {
            position: relative;
            width: 100%;
            height: 100px;
        }
    </style>
</head>

<body>
    @php
        // Obtener los enlaces de la base de datos
        $enlaces = App\Models\Enlace::first() ?? new App\Models\Enlace();
        $numeroContacto = $enlaces->numero_contacto ?? '3235903774'; // Valor por defecto
    @endphp

    <!-- Cabecera -->
    <header class="py-2 border-bottom border-secondary" style="background-color: #00bf63;">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- Logo -->
            <div class="logo-container">
                <img src="{{ asset('images/RiffyLogo.png') }}" alt="Logo">
            </div>
            <!-- Enlaces -->
            <div>
                <a href="{{ route('cartones.index') }}" class="text-white text-decoration-none me-3 nav-link-custom">Buscar mi cartón</a>
                @if($enlaces->grupo_whatsapp)
                    <a href="{{ $enlaces->grupo_whatsapp }}" target="_blank" class="text-white text-decoration-none nav-link-custom d-none d-md-inline">Grupo Whatsapp</a>
                    <a href="{{ $enlaces->grupo_whatsapp }}" target="_blank" class="text-white text-decoration-none nav-link-custom d-inline d-md-none">Grupo WA</a>
                @else
                    <a href="#" class="text-white text-decoration-none nav-link-custom d-none d-md-inline">Grupo Whatsapp</a>
                    <a href="#" class="text-white text-decoration-none nav-link-custom d-inline d-md-none">Grupo WA</a>
                @endif
            </div>
        </div>
    </header>

    <div class="dropdown">
        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="menuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            ☰ Seleccionar Bingo
        </button>
        <ul class="dropdown-menu" id="bingoDropdownList" aria-labelledby="menuDropdown">
            <!-- Aquí se cargarán dinámicamente los bingos -->
        </ul>
    </div>

    <!-- Contenedor principal -->
    <div class="container py-4">
        <!-- Contenedor para bingo cerrado -->
        <div class="bingo-cerrado-container" id="bingoCerradoContainer">
            <h1 class="bingo-cerrado-titulo">No hay más cartones disponibles para este Bingo</h1>
            
            <h3 class="bingo-cerrado-fecha" id="bingoCerradoFecha">
                <!-- La fecha se llenará dinámicamente -->
            </h3>
            
            <div class="bingo-actions">
                <a href="{{ route('cartones.index') }}" class="btn btn-naranja text-white">
                    Buscar mi cartón
                </a>
                @if($enlaces->grupo_whatsapp)
                    <a href="{{ $enlaces->grupo_whatsapp }}" target="_blank" class="btn btn-naranja text-white">
                        Grupo Whatsapp
                    </a>
                @endif
            </div>
        </div>
    
        <!-- Formulario de compra -->
        <div class="bingo-container p-4" id="bingoFormContainer">
            <!-- FORMULARIO -->
            <form action="{{ route('bingo.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="bingo_id" id="bingo_id" value="{{ $bingo->id ?? '' }}">
                <!-- Paso 1 -->
                <h4 class="paso-title text-center mb-2">Paso 1</h4>
                <p class="text-start mb-3 fw-bold">Escoge la cantidad de cartones:</p>
                <div class="bg-black p-3 rounded mb-4">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <button
                            type="button"
                            id="btnMinus"
                            class="btn btn-light fw-bold px-3 py-2">-</button>

                        <input
                            type="number"
                            id="inputCartones"
                            name="cartones"
                            class="form-control mx-2 text-center bg-white text-dark fw-bold"
                            style="max-width: 900px;"
                            value="1"
                            min="1">

                        <button
                            type="button"
                            id="btnPlus"
                            class="btn btn-light fw-bold px-3 py-2">+</button>
                    </div>
                    <!-- Sección de precios en HTML con formato correcto -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div><span class="text-verde fw-bold">Precio cartón:</span></div>
                        <div class="text-end fw-bold" id="precioCarton">${{ number_format((float)($bingo->precio ?? 6000), 2, '.', '.') }} Pesos</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div><span class="text-verde fw-bold">Total:</span></div>
                        <div class="text-end fw-bold" id="totalPrice">${{ number_format((float)($bingo->precio ?? 6000), 0, '', '.') }} Pesos</div>
                    </div>
                </div>

                <!-- Paso 2 -->
                <h4 class="paso-title text-center mb-2">Paso 2</h4>
                <p class="paso-title text-center mb-2">Ingresa tus datos:</p>
                <div class="bg-black p-3 rounded mb-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre y Apellidos</label>
                        <input
                            type="text"
                            name="nombre"
                            class="form-control bg-white text-dark"
                            placeholder="Ingresa tu nombre completo"
                            required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Celular</label>
                        <input
                            type="tel"
                            name="celular"
                            class="form-control bg-white text-dark"
                            placeholder="Ingresa tu número de celular"
                            required>
                    </div>
                </div>

                <!-- Paso 3 -->
                <h4 class="paso-title text-center mb-2">Paso 3</h4>
                <p class="text-start mb-3">Realiza el pago y toma una captura del comprobante</p>
                <div class="bg-black p-3 rounded mb-4">
                    <div class="row">
                        <div class="col-12">
                            <div class="text-verde mb-2 fw-bold">Nequi: <span class="text-white">{{ $numeroContacto }}</span></div>
                            <div class="text-verde mb-2 fw-bold">Daviplata: <span class="text-white">{{ $numeroContacto }}</span></div>
                            <div class="text-verde mb-3 fw-bold">Transfiya: <span class="text-white">{{ $numeroContacto }}</span></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><span class="text-amarillo fw-bold">Total a pagar:</span></div>
                                <div class="text-end fw-bold" id="totalPagar">${{ number_format($bingo->precio ?? 6000, 0, ',', '.') }} Pesos</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 4 -->
                <h4 class="paso-title text-center mb-2">Paso 4</h4>
                <p class="text-start mb-3 fw-bold">Sube tu comprobante de pago</p>
                <div class="bg-black p-3 rounded mb-4">
                    <div class="border-naranja rounded p-3 text-center mb-3">
                        <!-- Botón para seleccionar archivos -->
                        <label class="etiqueta-archivo mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="currentColor" class="bi bi-camera me-2" viewBox="0 0 16 16">
                                <path d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.12-.879l.83-.828
                                         A1 1 0 0 1 6.827 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0
                                         12.828 5H14a1 1 0 0 1 1 1zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2
                                         2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828
                                         A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828
                                         A2 2 0 0 1 3.172 4z" />
                                <path d="M8 11a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0
                                         5m0 1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M3
                                         6.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0" />
                            </svg>
                            <span>Foto/Captura de pantalla</span>
                            <input
                                type="file"
                                name="comprobante[]"
                                id="comprobante"
                                style="display: none;"
                                accept="image/*"
                                multiple
                                required>
                        </label>
                    </div>
                    <!-- Contenedor para la vista previa de las imágenes -->
                    <div id="previewContainer"></div>
                    <button class="btn btn-naranja text-white fw-bold w-100 py-2" style="margin-top: 10px;">
                        RESERVAR MIS CARTONES
                    </button>

                </div>
            </form>

            <!-- Ayuda -->
            <div class="text-center mt-4 fw-bold">
                <p class="mb-1">¿Cómo comprar?</p>
                @if($enlaces->video_1)
                    <div class="ratio ratio-16x9 mt-2" style="max-width: 640px; margin: 0 auto;">
                        <iframe 
                            src="{{ str_replace('watch?v=', 'embed/', $enlaces->video_1) }}" 
                            title="Video tutorial de compra" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                    </div>
                @else
                    <span class="text-decoration-underline text-warning">video 1 aquí</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    </script>

<!-- Script para manejar la lógica de + / -, cálculo de total, y vista previa con eliminación de imágenes -->
<script>
        // Declare updateTotal in the global scope
let PRICE_PER_CARTON = parseFloat({{ $bingo->precio ?? 6000 }});
let inputCartones, btnMinus, btnPlus, totalPrice, totalPagar, precioCarton;

// Function to format numbers with thousands separators (without decimals)
function formatNumber(number) {
    return `$${Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")} Pesos`;
}

// Make updateTotal a global function
function updateTotal() {
    let quantity = parseInt(inputCartones.value, 10);
    if (isNaN(quantity) || quantity < 1) {
        quantity = 1;
        inputCartones.value = 1;
    }
    const total = quantity * PRICE_PER_CARTON;

    // Update cart price and totals using the same format (without decimals)
    precioCarton.textContent = formatNumber(PRICE_PER_CARTON);
    totalPrice.textContent = formatNumber(total);
    totalPagar.textContent = formatNumber(total);
}

// Initialize elements after DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get elements for quantity and total
    inputCartones = document.getElementById('inputCartones');
    btnMinus = document.getElementById('btnMinus');
    btnPlus = document.getElementById('btnPlus');
    totalPrice = document.getElementById('totalPrice');
    totalPagar = document.getElementById('totalPagar');
    precioCarton = document.getElementById('precioCarton');

    // Obtener los contenedores para los estados de bingo
    const bingoFormContainer = document.getElementById('bingoFormContainer');
    const bingoCerradoContainer = document.getElementById('bingoCerradoContainer');

    btnMinus.addEventListener('click', () => {
        let quantity = parseInt(inputCartones.value, 10);
        if (quantity > 1) {
            quantity--;
            inputCartones.value = quantity;
            updateTotal();
        }
    });

    btnPlus.addEventListener('click', () => {
        let quantity = parseInt(inputCartones.value, 10);
        quantity++;
        inputCartones.value = quantity;
        updateTotal();
    });

    inputCartones.addEventListener('change', updateTotal);
    updateTotal();

    // File handling for multiple preview with deletion
    let selectedFiles = []; // Global array of selected files
    let dt = new DataTransfer(); // DataTransfer object to simulate FileList

    const fileInput = document.getElementById('comprobante');
    const previewContainer = document.getElementById('previewContainer');

    fileInput.addEventListener('change', () => {
        // Add new files to global array
        const newFiles = Array.from(fileInput.files);
        newFiles.forEach(file => {
            selectedFiles.push(file);
        });
        updateFileInput();
        updatePreview();
    });

    function updateFileInput() {
        dt = new DataTransfer();
        selectedFiles.forEach(file => {
            dt.items.add(file);
        });
        fileInput.files = dt.files;
    }

    function updatePreview() {
        previewContainer.innerHTML = '';
        
        // Si no hay archivos seleccionados, no hacer nada más
        if (selectedFiles.length === 0) return;
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Create container for image and delete button
                const previewItem = document.createElement('div');
                previewItem.classList.add('preview-item');

                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-preview');

                const deleteBtn = document.createElement('button');
                deleteBtn.textContent = 'X';
                deleteBtn.classList.add('delete-btn');
                deleteBtn.setAttribute('data-index', index); // Agregar índice como atributo
                deleteBtn.addEventListener('click', function() {
                    const idx = parseInt(this.getAttribute('data-index'));
                    // Eliminar el archivo del array
                    selectedFiles.splice(idx, 1);
                    // Actualizar el input file y la vista previa
                    updateFileInput();
                    updatePreview();
                });

                previewItem.appendChild(img);
                previewItem.appendChild(deleteBtn);
                previewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });
    }
    
    // Load bingos
    cargarBingos();
    
    // Update every 5 seconds
    setInterval(cargarBingos, 5000);
});

function cargarBingos() {
    fetch("{{ route('bingos.all') }}") // Route that returns all bingos (open and closed)
        .then(response => response.json())
        .then(data => {
            let dropdownList = document.getElementById("bingoDropdownList");
            dropdownList.innerHTML = ""; // Clear dropdown

            // Separamos los bingos en abiertos y cerrados
            const bingosAbiertos = data.filter(bingo => bingo.estado === 'abierto');
            const bingosCerrados = data.filter(bingo => bingo.estado !== 'abierto');
            
            if (bingosAbiertos.length > 0 || bingosCerrados.length > 0) {
                // Primero mostrar los bingos abiertos
                if (bingosAbiertos.length > 0) {
                    const headerAbiertos = document.createElement("li");
                    headerAbiertos.innerHTML = '<h6 class="dropdown-header">Bingos Disponibles</h6>';
                    dropdownList.appendChild(headerAbiertos);
                    
                    bingosAbiertos.forEach(bingo => {
                        let listItem = document.createElement("li");
                        let item = document.createElement("a");
                        item.classList.add("dropdown-item");
                        item.href = "#";
                        item.dataset.bingoId = bingo.id;
                        item.dataset.bingoPrecio = bingo.precio;
                        item.dataset.bingoEstado = bingo.estado;
                        item.textContent = `${bingo.nombre} - ${bingo.fecha_formateada}`;
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            seleccionarBingo(bingo);
                        });

                        listItem.appendChild(item);
                        dropdownList.appendChild(listItem);
                    });
                    
                    // Agregar separador si hay bingos cerrados
                    if (bingosCerrados.length > 0) {
                        const divider = document.createElement("li");
                        divider.innerHTML = '<hr class="dropdown-divider">';
                        dropdownList.appendChild(divider);
                    }
                }
                
                // Luego mostrar los bingos cerrados
                if (bingosCerrados.length > 0) {
                    const headerCerrados = document.createElement("li");
                    headerCerrados.innerHTML = '<h6 class="dropdown-header">Bingos Cerrados</h6>';
                    dropdownList.appendChild(headerCerrados);
                    
                    bingosCerrados.forEach(bingo => {
                        let listItem = document.createElement("li");
                        let item = document.createElement("a");
                        item.classList.add("dropdown-item", "text-muted");
                        item.href = "#";
                        item.dataset.bingoId = bingo.id;
                        item.dataset.bingoPrecio = bingo.precio;
                        item.dataset.bingoEstado = bingo.estado;
                        item.textContent = `${bingo.nombre} - ${bingo.fecha_formateada}`;
                        
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            seleccionarBingo(bingo);
                        });

                        listItem.appendChild(item);
                        dropdownList.appendChild(listItem);
                    });
                }
            } else {
                dropdownList.innerHTML = `<li class="text-center p-2">No hay bingos disponibles</li>`;
            }
        })
        .catch(error => console.error("Error al cargar los bingos:", error));
}

// Función para seleccionar un bingo y actualizar la interfaz
function seleccionarBingo(bingo) {
    // Update bingo ID
    document.getElementById('bingo_id').value = bingo.id;

    // Update menu title
    document.getElementById('menuDropdown').textContent = `☰ ${bingo.nombre}`;
    
    // Obtener los contenedores
    const bingoFormContainer = document.getElementById('bingoFormContainer');
    const bingoCerradoContainer = document.getElementById('bingoCerradoContainer');
    const bingoCerradoFecha = document.getElementById('bingoCerradoFecha');
    
    if (bingo.estado === 'abierto') {
        // Si el bingo está abierto, mostrar el formulario y ocultar el mensaje de bingo cerrado
        bingoFormContainer.style.display = 'block';
        bingoCerradoContainer.style.display = 'none';
        
        // Update price per card
        PRICE_PER_CARTON = parseFloat(bingo.precio);
        
        // Update totals
        updateTotal();
    } else {
        // Si el bingo está cerrado, ocultar el formulario y mostrar el mensaje de bingo cerrado
        bingoFormContainer.style.display = 'none';
        bingoCerradoContainer.style.display = 'block';
        
        // Actualizar la fecha del bingo cerrado
        bingoCerradoFecha.textContent = `${bingo.nombre} - ${bingo.fecha_formateada}`;
    }
}
    </script>

</body>

</html>