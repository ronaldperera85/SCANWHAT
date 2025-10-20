document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const toggleButton = document.querySelector('.toggle-btn');
    const themeCheckbox = document.getElementById('theme-checkbox');
    const body = document.body;
    const navLinks = document.querySelectorAll('nav ul li a');
    const contentPlaceholder = document.getElementById('content-placeholder');
    const logoutIcon = document.getElementById('logout-icon');

    if (toggleButton && sidebar) {
        toggleButton.addEventListener('click', function () {
            const isCollapsing = !sidebar.classList.contains('collapsed');
            sidebar.classList.toggle('collapsed');
            toggleButton.setAttribute('aria-expanded', String(!isCollapsing));
            if (isCollapsing) {
                toggleButton.focus();
            }
            sidebar.setAttribute('aria-hidden', String(isCollapsing));
        });
    }

    if (themeCheckbox && body) {
        themeCheckbox.addEventListener('change', function () {
            body.classList.toggle('dark-theme');
        });
    }

    function loadContent(pageName) {
        const physicalPath = `pages/${pageName}.php`;
        fetch(physicalPath)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.text();
            })
            .then(data => {
                contentPlaceholder.innerHTML = data;
                initEventListeners();
                initRegisterPhoneFormListener();
                initChangePasswordFormListener();
                initDeveloperFormListeners(); // Llamada a la nueva función
                if (pageName === 'admin' || pageName === 'mis_telefonos') {
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
                loadContent('registrar_telefono');
            });
        }

        document.querySelectorAll('.connect-btn').forEach(button => {
            button.addEventListener('click', async function () {
                const phoneNumber = this.getAttribute('data-phone-number');
                if (!phoneNumber) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: 'No se pudo obtener el número de teléfono.' });
                    return;
                }
                this.disabled = true;
                this.textContent = 'Conectando...';

                try {
                    const response = await fetch('mis_telefonos', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ 'action': 'connect', 'phoneNumber': phoneNumber })
                    });
                    if (response.ok) {
                        loadContent('mis_telefonos');
                    } else {
                        console.error("Error en la solicitud connect:", await response.text());
                        Swal.fire({ icon: 'error', title: 'Error!', text: 'Error al conectar el número.' });
                    }
                } catch (error) {
                    console.error("Error de red:", error);
                    Swal.fire({ icon: 'error', title: 'Error!', text: 'Error de red al conectar el número.' });
                }
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async function () {
                const phoneNumber = this.getAttribute('data-phone-number');
                if (!phoneNumber) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: 'No se pudo obtener el número de teléfono para cerrar sesión.' });
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
                            const disconnectResponse = await fetch('mis_telefonos', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams({ 'action': 'disconnect_user', 'phoneNumber': phoneNumber })
                            });
                            if (disconnectResponse.ok) {
                                loadContent('mis_telefonos');
                            } else {
                                console.error("Error al desconectar:", await disconnectResponse.text());
                                Swal.fire({ icon: 'error', title: 'Error!', text: 'Error al cerrar sesión del número.' });
                            }
                        } catch (error) {
                            console.error("Error de red:", error);
                            Swal.fire({ icon: 'error', title: 'Error!', text: 'Error de red al cerrar sesión del número.' });
                        } finally {
                            this.disabled = false;
                            this.textContent = 'Cerrar Sesión';
                        }
                    }
                });
            });
        });
    }

    function initDeveloperFormListeners() {
        const sendMessageForm = document.getElementById('sendMessageForm');
        if (!sendMessageForm) return;

        const waAccountSelect = document.getElementById('waAccountSend');
        const apiTokenInput = document.getElementById('apiTokenSend');
        const groupSelect = document.getElementById('groupSelect');
        const recipientAccountInput = document.getElementById('recipientAccountSend');
        const groupLoader = document.getElementById('groupLoader');
        const cardElement = sendMessageForm.closest('.card');
        const apiBaseUrl = cardElement.getAttribute('data-api-base-url');
        const apiSendChatUrl = cardElement.getAttribute('data-api-send-chat-url');

        waAccountSelect.addEventListener('change', async function() {
            const selectedOption = this.options[this.selectedIndex];
            const token = selectedOption.getAttribute('data-token');
            const numero = this.value;

            apiTokenInput.value = token || '';
            recipientAccountInput.value = '';
            recipientAccountInput.readOnly = false;
            groupSelect.innerHTML = '<option value="">-- Cargando grupos... --</option>';
            groupSelect.disabled = true;
            groupLoader.style.display = 'inline';

            if (!numero) {
                groupSelect.innerHTML = '<option value="">-- Seleccione un número primero --</option>';
                groupLoader.style.display = 'none';
                return;
            }

            try {
                const groupsApiUrl = `${apiBaseUrl}/api/groups/${numero}`;
                const response = await fetch(groupsApiUrl);
                if (!response.ok) throw new Error(`Error del servidor: ${response.status}`);
                
                const data = await response.json();
                groupSelect.innerHTML = '';
                
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = '-- Enviar a un número individual --';
                groupSelect.appendChild(defaultOption);
                
                if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                    data.data
                        .filter(group => group.id && group.id.endsWith('@g.us'))
                        .forEach(group => {
                            const option = document.createElement('option');
                            option.value = group.id;
                            option.textContent = `${group.name || 'Grupo sin nombre'} - [${group.id}]`;
                            groupSelect.appendChild(option);
                        });
                } else {
                    const noGroupsOption = document.createElement('option');
                    noGroupsOption.value = '';
                    noGroupsOption.textContent = '-- No se encontraron grupos --';
                    noGroupsOption.disabled = true;
                    groupSelect.insertBefore(noGroupsOption, groupSelect.firstChild);
                    groupSelect.selectedIndex = 0;
                }
            } catch (error) {
                console.error('Error al obtener los grupos:', error);
                groupSelect.innerHTML = '<option value="">-- Error al cargar grupos --</option>';
            } finally {
                groupLoader.style.display = 'none';
                groupSelect.disabled = false;
            }
        });

        groupSelect.addEventListener('change', function() {
            const selectedGroupId = this.value;
            if (selectedGroupId) {
                recipientAccountInput.value = selectedGroupId;
                recipientAccountInput.readOnly = true;
            } else {
                recipientAccountInput.value = '';
                recipientAccountInput.readOnly = false;
                recipientAccountInput.focus();
            }
        });
        
        sendMessageForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            // Lógica de tu formulario original que enviaba JSON
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
                // NOTA: Usando JSON como lo tenías en tu script general original
                const response = await fetch(apiSendChatUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        token: apiToken,
                        uid: waAccount,
                        to: recipientAccount,
                        text: messageText,
                        custom_uid: 'dev-test-' + Date.now()
                    })
                });

                const data = await response.json();
                responseContainer.innerHTML = ''; 
                
                if (response.ok && data.success) { // Doble chequeo
                    Swal.fire({
                        icon: 'success',
                        title: '¡Mensaje Enviado!',
                        text: 'El mensaje ha sido enviado correctamente.'
                    });
                    sendMessageForm.reset(); 
                    // Resetear los campos relacionados
                    apiTokenInput.value = '';
                    groupSelect.innerHTML = '<option value="">-- Seleccione un número primero --</option>';
                    groupSelect.disabled = true;

                } else {
                    console.error("Error en el envío de mensaje:", data);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Enviar',
                        text: `Error: ${data.error || data.message || 'No se pudo enviar el mensaje.'}`
                    });
                }
            } catch (error) {
                console.error("Error de red:", error);
                responseContainer.innerHTML = ''; 
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Red',
                    text: 'Ocurrió un error de red al intentar enviar el mensaje.'
                });
            }
        });
    }

    function initRegisterPhoneFormListener() {
        const registerPhoneForm = document.getElementById('registerPhoneForm');
        if (registerPhoneForm) {
            registerPhoneForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const phoneNumberInput = document.getElementById('numero');
                const phoneNumber = phoneNumberInput.value.trim();
                if (!validatePhoneNumber(phoneNumber)) {
                    return;
                }
                Swal.fire({
                    title: 'Generando QR...',
                    text: 'Por favor, espera un momento.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                try {
                    const response = await fetch('registrar_telefono', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ numero: phoneNumber })
                    });
                    const data = await response.json();
                    if (data.success) {
                        phoneNumberInput.value = '';
                        Swal.fire({
                            title: '¡Código QR Generado!',
                            html: `<p>${data.message}</p><div class="qr-container" style="margin-top: 15px;"><img src="${data.qrCode}" alt="QR Code" style="max-width: 100%; height: auto;"></div>`,
                            icon: 'success',
                            confirmButtonText: 'Volver a Mis Teléfonos'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                loadContent('mis_telefonos');
                            }
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error al Registrar', text: data.message });
                    }
                } catch (error) {
                    console.error('Error de red o de parseo:', error);
                    Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar con el servidor. Verifica tu conexión o inténtalo más tarde.' });
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
                if (!oldPassword || !newPassword || !confirmPassword) {
                    Swal.fire({ icon: 'warning', title: '¡Campos Incompletos!', text: 'Por favor, completa todos los campos para cambiar tu contraseña.' });
                    return;
                }
                changePasswordResponse.innerHTML = 'Enviando solicitud...';
                try {
                    const formData = new URLSearchParams();
                    formData.append('change_password', '1');
                    formData.append('old_password', oldPassword);
                    formData.append('new_password', newPassword);
                    formData.append('confirm_password', confirmPassword);
                    const response = await fetch('mi_cuenta', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        changePasswordResponse.innerHTML = '';
                        changePasswordForm.reset();
                        Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message });
                    } else {
                        changePasswordResponse.innerHTML = '';
                        Swal.fire({ icon: 'error', title: 'Error al Cambiar Contraseña', text: data.message });
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
            if (phoneNumberInput) phoneNumberInput.focus();
            return false;
        }
        return true;
    }

    function initTokenToggle() {
        document.querySelectorAll('.token-value').forEach(tokenElement => {
            const fullToken = tokenElement.textContent;
            const shortenedToken = '••••••••••••••••••••••••••';
            let isShortened = true;
            tokenElement.textContent = shortenedToken;
            tokenElement.style.cursor = 'pointer';
            tokenElement.addEventListener('click', function() {
                isShortened = !isShortened;
                tokenElement.textContent = isShortened ? shortenedToken : fullToken;
            });
        });
    }

    loadContent('dashboard');

    const dashboardLink = document.querySelector('a[data-page="dashboard"]');
    if (dashboardLink) {
        navLinks.forEach(link => link.classList.remove('active'));
        dashboardLink.classList.add('active');
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            loadContent(this.dataset.page);
        });
    });

    if (logoutIcon) {
        logoutIcon.addEventListener('click', function () {
            window.location.href = 'logout';
        });
    }
});