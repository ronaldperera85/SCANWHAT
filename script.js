document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const toggleButton = document.querySelector('.toggle-btn');
    const themeCheckbox = document.getElementById('theme-checkbox');
    const body = document.body;
    const navLinks = document.querySelectorAll('nav ul li a');
    const contentPlaceholder = document.getElementById('content-placeholder');
    const logoutIcon = document.getElementById('logout-icon');

    toggleButton.addEventListener('click', function () {
    // Primero, verificamos si la barra se va a colapsar o a expandir
    const isCollapsing = !sidebar.classList.contains('collapsed');

    // Aplicamos el cambio visual
    sidebar.classList.toggle('collapsed');
    
    // Actualizamos el aria-expanded del botón
    toggleButton.setAttribute('aria-expanded', !isCollapsing);

    // 1. PRIMERO, si la barra se está colapsando, sacamos el foco de ella.
    if (isCollapsing) {
        toggleButton.focus();
    }

    // 2. LUEGO, ahora que el foco está seguro, ocultamos la barra.
    sidebar.setAttribute('aria-hidden', isCollapsing);
});


    // Cambia el tema
    themeCheckbox.addEventListener('change', function () {
        body.classList.toggle('dark-theme');
    });

    // Cargar contenido de la página
    function loadContent(page) {
        fetch(page)
            .then(response => response.text())
            .then(data => {
                contentPlaceholder.innerHTML = data;
                initEventListeners();
                initRegisterPhoneFormListener();
                initChangePasswordFormListener();  // Ensure the listener is initialized after content loads
                if (page === 'pages/admin.php') {
                    initTokenToggle();
                }
            })
            .catch(error => {
                console.error('Error loading content:', error);
                contentPlaceholder.innerHTML = '<p>Error al cargar el contenido.</p>';
            });
    }

    function initEventListeners() {
        const addPhoneButton = document.getElementById('add-phone');
        if (addPhoneButton) {
            addPhoneButton.addEventListener('click', () => {
                loadContent('pages/registrar_telefono.php');
            });
        }

        document.querySelectorAll('.connect-btn').forEach(button => {
            button.addEventListener('click', async function () {
                const phoneNumber = this.getAttribute('data-phone-number');
                if (!phoneNumber) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'No se pudo obtener el número de teléfono.',
                    });
                    return;
                }
                this.disabled = true;
                const loadingIndicator = document.createElement('img');
                loadingIndicator.src = 'img/loading.gif';
                loadingIndicator.alt = 'Cargando...';
                loadingIndicator.width = 20;
                loadingIndicator.style.marginLeft = '10px'; // Add some spacing
                this.textContent = 'Conectando...';
                this.parentNode.appendChild(loadingIndicator);

                try {
                    const response = await fetch('pages/mis_telefonos.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'action': 'connect',
                            'phoneNumber': phoneNumber
                        })
                    });

                    if (response.ok) {
                        loadContent('pages/mis_telefonos.php');
                    } else {
                        console.error("Error en la solicitud connect:", await response.text());
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Error al conectar el número.',
                        });
                    }
                } catch (error) {
                    console.error("Error de red:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error de red al conectar el número.',
                    });
                } finally {
                    this.disabled = false;
                    this.textContent = 'Conectar';
                }
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async function () {
                const phoneNumber = this.getAttribute('data-phone-number');
                if (!phoneNumber) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'No se pudo obtener el número de teléfono para cerrar sesión.',
                    });
                    return;
                }
                Swal.fire({
                    title: "¿Estás seguro de que deseas cerrar sesión de este número?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, cerrar sesión!',
                    cancelButtonText: 'Cancelar'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        this.disabled = true;
                        this.textContent = 'Cerrando Sesión...';

                        try {
                            const disconnectResponse = await fetch('pages/mis_telefonos.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams({
                                    'action': 'disconnect_user',
                                    'phoneNumber': phoneNumber
                                })
                            });

                            if (disconnectResponse.ok) {
                                loadContent('pages/mis_telefonos.php');
                            } else {
                                console.error("Error al desconectar:", await disconnectResponse.text());
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Error al cerrar sesión del número.',
                                });
                            }
                        } catch (error) {
                            console.error("Error de red:", error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Error de red al cerrar sesión del número.',
                            });
                        } finally {
                            this.disabled = false;
                            this.textContent = 'Cerrar Sesión';
                        }
                    }
                });
            });
        });
        
        // Evento para envío de mensajes
        const sendMessageForm = document.getElementById('sendMessageForm');
        if (sendMessageForm) {
            
            // =======================================================================
            // CÓDIGO AÑADIDO: Lógica para autocompletar el token dinámicamente
            // =======================================================================
            const waAccountSelect = document.getElementById('waAccountSend');
            const apiTokenInput = document.getElementById('apiTokenSend');

            if (waAccountSelect && apiTokenInput) {
                // Función para actualizar el token
                const updateToken = () => {
                    const selectedOption = waAccountSelect.options[waAccountSelect.selectedIndex];
                    if (selectedOption) {
                        apiTokenInput.value = selectedOption.dataset.token || '';
                    }
                };
                
                // Actualizar el token cuando el usuario cambia la selección
                waAccountSelect.addEventListener('change', updateToken);
                
                // Actualizar el token también al cargar la página para el valor inicial
                updateToken();
            }
            // =======================================================================
            // FIN DEL CÓDIGO AÑADIDO
            // =======================================================================

            sendMessageForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const apiSendChatUrl = this.closest('.card').dataset.apiSendChatUrl;
                const apiToken = document.getElementById('apiTokenSend').value;
                const waAccount = document.getElementById('waAccountSend').value;
                const recipientAccount = document.getElementById('recipientAccountSend').value;
                const messageText = document.getElementById('messageTextSend').value;
                const responseContainer = document.getElementById('sendMessageResponse');

                if (!apiToken || !waAccount || !recipientAccount || !messageText) {
                    Swal.fire({
                        icon: 'warning',
                        title: '¡Campos Incompletos!',
                        text: 'Por favor, completa todos los campos para enviar el mensaje.',
                    });
                    return;
                }

                responseContainer.innerHTML = 'Enviando mensaje...';

                try {
                    const response = await fetch(apiSendChatUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            token: apiToken,
                            uid: waAccount,
                            to: recipientAccount,
                            text: messageText,
                            custom_uid: 'dev-test-' + Date.now()
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        responseContainer.innerHTML = `<p class="success">Mensaje enviado correctamente.</p>`;
                    } else {
                        console.error("Error en el envío de mensaje:", data);
                        responseContainer.innerHTML = `<p class="error">Error: ${data.error || 'No se pudo enviar el mensaje'}</p>`;
                    }
                } catch (error) {
                    console.error("Error de red:", error);
                    responseContainer.innerHTML = `<p class="error">Error de red al enviar el mensaje.</p>`;
                }
            });
        }
    }

    function initRegisterPhoneFormListener() {
        const registerPhoneForm = document.getElementById('registerPhoneForm');
        if (registerPhoneForm) {
            registerPhoneForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                const phoneNumberInput = document.getElementById('numero');
                const phoneNumber = phoneNumberInput.value;
                const registerPhoneResponse = document.getElementById('registerPhoneResponse');

                if (!validatePhoneNumber(phoneNumber)) {
                    return;
                }

                 // Mostrar el GIF de carga
                registerPhoneResponse.innerHTML = '<img src="img/loading.gif" alt="Cargando..." width="50">';
                registerPhoneResponse.innerHTML += ' Envíando solicitud...';

                try {
                    const response = await fetch('pages/registrar_telefono.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            numero: phoneNumber
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        registerPhoneResponse.innerHTML = `<p class="success">${data.message}</p>`;
                        if (data.qrCode) {
                            registerPhoneResponse.innerHTML += `<div class="qr-container"><img src="${data.qrCode}" alt="QR Code"></div>`;
                        }
                        const volverBtn = document.createElement('button');
                        volverBtn.textContent = 'Volver a Mis Teléfonos';
                        volverBtn.className = 'btn btn-primary';
                        volverBtn.addEventListener('click', function() {
                            loadContent('pages/mis_telefonos.php');
                        });
                        registerPhoneResponse.appendChild(volverBtn);
                    } else {
                        registerPhoneResponse.innerHTML = `<p class="error">${data.message}</p>`;
                    }
                    phoneNumberInput.value = '';
                } catch (error) {
                    console.error('Error de red:', error);
                    registerPhoneResponse.innerHTML = `<p class="error">Error de red al registrar el número.</p>`;
                }
            });
        }
    }
    
    function initChangePasswordFormListener() {
        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                const oldPassword = document.getElementById('old_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const changePasswordResponse = document.getElementById('changePasswordResponse');

                // =======================================================================
            // CÓDIGO AÑADIDO: Validación con SweetAlert
            // =======================================================================
            if (!oldPassword || !newPassword || !confirmPassword) {
                Swal.fire({
                    icon: 'warning',
                    title: '¡Campos Incompletos!',
                    text: 'Por favor, completa todos los campos para cambiar tu contraseña.',
                });
                return; // Detiene la ejecución de la función aquí si hay campos vacíos
            }
            // =======================================================================
            // FIN DEL CÓDIGO AÑADIDO
            // =======================================================================
                
                changePasswordResponse.innerHTML = 'Enviando solicitud...';

                try {
                    const formData = new URLSearchParams();
                    formData.append('change_password', '1');
                    formData.append('old_password', oldPassword);
                    formData.append('new_password', newPassword);
                    formData.append('confirm_password', confirmPassword);

                    const response = await fetch('pages/mi_cuenta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                         changePasswordResponse.innerHTML = ''; // Limpia el mensaje de "enviando"
                    changePasswordForm.reset(); // Limpia los campos del formulario
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message, // Muestra el mensaje de éxito del servidor
                    });
                    // =========================
                } else {
                    // === CAMBIO AQUÍ: USAR SWEETALERT PARA EL ERROR ===
                    changePasswordResponse.innerHTML = ''; // Limpia el mensaje de "enviando"
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Cambiar Contraseña',
                        text: data.message,
                    });
                }
            } catch (error) {
                console.error('Error de red:', error);
                changePasswordResponse.innerHTML = `<p class="error">Error de red al cambiar la contraseña.</p>`;
            }
        });
    }
}

    function validatePhoneNumber(phoneNumber) {
        const phoneNumberRegex = /^\d{10,15}$/;
        const phoneNumberInput = document.getElementById('numero');

        if (!phoneNumberRegex.test(phoneNumber)) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Por favor, ingrese un número de teléfono válido en formato WhatsApp (ej: 584125927917 o 573205649404).',
        });
        phoneNumberInput.focus();
        return false;
        }
        return true;
    }

    function initTokenToggle() {
        const tokenElements = document.querySelectorAll('.token-value');

        tokenElements.forEach(tokenElement => {
            const fullToken = tokenElement.textContent;
            const shortenedToken = 'eyJhbGciOiJI...';
            let isShortened = true;

            tokenElement.textContent = shortenedToken;
            tokenElement.style.cursor = 'pointer';

            tokenElement.addEventListener('click', function() {
                if (isShortened) {
                    tokenElement.textContent = fullToken;
                } else {
                    tokenElement.textContent = shortenedToken;
                }
                isShortened = !isShortened;
            });
        });
    }

    // Cargar la página inicial
    loadContent('pages/dashboard.php');

    // Asignar clase activa al enlace de dashboard
    navLinks.forEach(link => link.classList.remove('active'));
    document.querySelector('a[data-page="pages/dashboard.php"]').classList.add('active');

    // Evento para navegación
    navLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            navLinks.forEach(link => link.classList.remove('active'));
            this.classList.add('active');
            loadContent(this.dataset.page);
        });
    });

    // Evento de cierre de sesión
    if (logoutIcon) {
        logoutIcon.addEventListener('click', function () {
            window.location.href = 'pages/logout.php';
        });
    }

});