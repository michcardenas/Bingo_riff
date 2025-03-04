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

        @media (min-width: 768px) {
            .bingo-container {
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

            .form-control,
            .form-label,
            .etiqueta-archivo {
                font-size: 20px;
            }

            .text-amarillo,
            .btn-naranja {
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
    <!-- Cabecera -->
    <header class="py-2 border-bottom border-secondary" style="background-color: #00bf63;">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- Logo -->
            <div class="logo-container">
                <img src="{{ asset('images/RiffyLogo.png') }}" alt="Logo">
            </div>
            <!-- Enlaces -->
            <div>
                <a href="{{ route('buscarcartones') }}" class="text-white text-decoration-none me-3 nav-link-custom">Buscar mi cartón</a>
                <a href="#" class="text-white text-decoration-none nav-link-custom d-none d-md-inline">Grupo Whatsapp</a>
                <a href="#" class="text-white text-decoration-none nav-link-custom d-inline d-md-none">Grupo WA</a>
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
        <div class="bingo-container p-4">
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
                            <div class="text-verde mb-2 fw-bold">Nequi: <span class="text-white">3235903774</span></div>
                            <div class="text-verde mb-2 fw-bold">Daviplata: <span class="text-white">3235903774</span></div>
                            <div class="text-verde mb-3 fw-bold">Transfiya: <span class="text-white">3235903774</span></div>
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
                <a href="#" class="text-decoration-underline text-warning">
                    video 1 aquí
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    </script>

  <!-- Script para manejar la lógica de + / -, cálculo de total, y vista previa con eliminación de imágenes -->
<script>
    // Precio dinámico (asegurando que se interprete como número decimal)
    let PRICE_PER_CARTON = parseFloat({{ $bingo->precio ?? 6000 }});

    // Elementos de cantidad y total
    const inputCartones = document.getElementById('inputCartones');
    const btnMinus = document.getElementById('btnMinus');
    const btnPlus = document.getElementById('btnPlus');
    const totalPrice = document.getElementById('totalPrice');
    const totalPagar = document.getElementById('totalPagar');
    const precioCarton = document.getElementById('precioCarton');

    // Función para formatear números con separadores de miles (sin decimales)
    function formatNumber(number) {
        return `$${Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")} Pesos`;
    }

    function updateTotal() {
        let quantity = parseInt(inputCartones.value, 10);
        if (isNaN(quantity) || quantity < 1) {
            quantity = 1;
            inputCartones.value = 1;
        }
        const total = quantity * PRICE_PER_CARTON;
        
        // Actualizar precio del cartón y totales usando el mismo formato (sin decimales)
        precioCarton.textContent = formatNumber(PRICE_PER_CARTON);
        totalPrice.textContent = formatNumber(total);
        totalPagar.textContent = formatNumber(total);
    }

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

    // Manejo de archivos para vista previa múltiple con eliminación
    let selectedFiles = []; // Array global de archivos seleccionados
    let dt = new DataTransfer(); // Objeto DataTransfer para simular FileList

    const fileInput = document.getElementById('comprobante');
    const previewContainer = document.getElementById('previewContainer');

    fileInput.addEventListener('change', () => {
        // Agregar nuevos archivos al array global
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
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Crear contenedor para la imagen y botón de borrar
                const previewItem = document.createElement('div');
                previewItem.classList.add('preview-item');

                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-preview');

                const deleteBtn = document.createElement('button');
                deleteBtn.textContent = 'X';
                deleteBtn.classList.add('delete-btn');
                deleteBtn.addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    updateFileInput();
                    updatePreview();
                });

                previewItem.appendChild(img);
                previewItem.appendChild(deleteBtn);
                previewContainer.appendChild(previewItem);
            }
            reader.readAsDataURL(file);
        });
    }
</script>
<script>
    function cargarBingos() {
        fetch("{{ route('bingos.get') }}") // Ruta que devuelve JSON con los bingos
            .then(response => response.json())
            .then(data => {
                let dropdownList = document.getElementById("bingoDropdownList");
                dropdownList.innerHTML = ""; // Limpiar el dropdown

                if (data.length > 0) {
                    data.forEach(bingo => {
                        let listItem = document.createElement("li");
                        let item = document.createElement("a");
                        item.classList.add("dropdown-item");
                        item.href = "#";
                        item.dataset.bingoId = bingo.id;
                        item.dataset.bingoPrecio = bingo.precio;
                        item.textContent = `${bingo.nombre} - ${bingo.fecha_formateada}`;
                        item.addEventListener('click', function(e) {
                            e.preventDefault();

                            // Actualizar ID de bingo
                            document.getElementById('bingo_id').value = bingo.id;

                            // Actualizar precio por cartón (asegurando que sea un número decimal)
                            PRICE_PER_CARTON = parseFloat(bingo.precio);
                            
                            // Actualizar totales
                            updateTotal();

                            // Actualizar título del menú
                            document.getElementById('menuDropdown').textContent = `☰ ${bingo.nombre}`;
                        });

                        listItem.appendChild(item);
                        dropdownList.appendChild(listItem);
                    });
                } else {
                    dropdownList.innerHTML = `<li class="text-center p-2">No hay bingos disponibles</li>`;
                }
            })
            .catch(error => console.error("Error al cargar los bingos:", error));
    }

    // Cargar los bingos al inicio
    cargarBingos();

    // Actualizar cada 5 segundos
    setInterval(cargarBingos, 5000);
</script>

</body>

</html>