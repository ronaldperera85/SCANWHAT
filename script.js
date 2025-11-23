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
                initDeveloperFormListeners();
                if (pageName === 'admin' || pageName === 'mis_telefonos') {
                    initTokenToggle();
                }
                if (pageName === 'monitoreo') {
                    initMonitoring();
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

                const loadingIndicator = document.createElement('img');
                loadingIndicator.src = 'img/loading.gif';
                loadingIndicator.alt = 'Cargando...';
                loadingIndicator.width = 20;
                loadingIndicator.style.marginLeft = '10px';
                loadingIndicator.classList.add('loading-indicator');

                this.parentNode.appendChild(loadingIndicator);

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
                } finally {
                    if (!document.querySelector('.swal2-container')) {
                        this.disabled = false;
                        this.textContent = 'Conectar';
                        const indicator = this.parentNode.querySelector('.loading-indicator');
                        if (indicator) {
                            indicator.remove();
                        }
                    }
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

        waAccountSelect.addEventListener('change', async function () {
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

        groupSelect.addEventListener('change', function () {
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

                if (response.ok && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Mensaje Enviado!',
                        text: 'El mensaje ha sido enviado correctamente.'
                    });
                    sendMessageForm.reset();
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

    // =========================================================================
    // === FUNCIÓN initMonitoring AUTOMÁTICA (Sin botón manual) ===
    // =========================================================================
    function initMonitoring() {
    const monitorGrid = document.getElementById('monitor-grid');
    if (!monitorGrid) return;

    // Variables de control
    let isUpdating = false;
    
    // Obtenemos los teléfonos del dataset
    let phones = [];
    try {
        phones = JSON.parse(monitorGrid.dataset.phones || '[]');
    } catch (e) {
        console.error('Error parsing phones data attribute:', e);
        phones = [];
    }

    // Limpieza de temporizadores anteriores (importante para SPA)
    if (monitorGrid._monitoring_timer) {
        clearInterval(monitorGrid._monitoring_timer);
        monitorGrid._monitoring_timer = null;
    }

    function setLastChecked(uid) {
        const last = document.getElementById(`last-checked-${uid}`);
        if (last) last.querySelector('span').textContent = new Date().toLocaleTimeString();
    }

    // Función Proxy (Ruta Dinámica)
    async function fetchViaProxy(uid) {
        try {
            let basePath = '';
            // Detección automática de ruta local vs producción
            if (window.location.pathname.includes('/scanwhat/')) {
                basePath = '/scanwhat/proxy/status_proxy.php';
            } else {
                basePath = '/proxy/status_proxy.php';
            }
            const proxyUrl = `${basePath}?uid=${encodeURIComponent(uid)}`;
            
            const res = await fetch(proxyUrl, { cache: 'no-store' });

            if (!res.ok) {
                return { ok: false, error: 'HTTP ' + res.status };
            }
            const data = await res.json();
            if (data && data.success && data.data) {
                return { ok: true, status: data.data.status, raw: data.data };
            }
            return { ok: false, error: data?.message || 'Respuesta inválida' };
        } catch (err) {
            return { ok: false, error: 'Proxy error: ' + (err.message || String(err)) };
        }
    }

    function updateCardUI(uid, result) {
        const display = document.getElementById(`status-display-${uid}`);
        const text = document.getElementById(`status-text-${uid}`);
        
        if (!display || !text) return;

        let cls = 'status-error';
        let txt = 'Error';

        if (result.ok) {
            switch (result.status) {
                case 'authenticated': 
                case 'conectado': 
                    cls = 'status-conectado'; txt = 'Conectado'; break;
                case 'unauthenticated': 
                case 'desconectado': 
                    cls = 'status-desconectado'; txt = 'Desconectado'; break;
                case 'initializing':
                case 'initializing_or_failed': 
                    cls = 'status-inicializando'; txt = 'Inicializando'; break;
                default: 
                    cls = 'status-error'; txt = String(result.status || 'Desconocido');
            }
        } else {
            const err = (result.error || '').toString();
            if (/401|403|404|no autorizado|no autorizado/i.test(err) || /no\s*conect/i.test(err)) {
                cls = 'status-desconectado';
                txt = 'No conectado';
            } else if (/timeout|proxy error|network|cors/i.test(err)) {
                cls = 'status-error';
                txt = 'Error de conexión';
            } else {
                cls = 'status-error';
                txt = err.replace(/^HTTP\s*/i, '');
                if (!txt) txt = 'Error de conexión';
            }
        }

        display.className = 'status-display ' + cls;
        text.textContent = txt;
        text.style.opacity = '1'; // Restaurar opacidad al terminar
        setLastChecked(uid);
    }

    // --- LÓGICA DE ACTUALIZACIÓN MASIVA ---
    async function updateAllStatuses() {
        if (isUpdating) return; // Evitar solapamiento de peticiones
        isUpdating = true;

        // Feedback visual sutil (opacidad en el texto) para saber que está refrescando
        phones.forEach(uid => {
            const text = document.getElementById(`status-text-${uid}`);
            if(text) text.style.opacity = '0.5';
        });

        // Ejecutar peticiones en paralelo
        const promises = phones.map(async (uid) => {
            const res = await fetchViaProxy(uid);
            updateCardUI(uid, res);
        });

        await Promise.all(promises);
        isUpdating = false;
    }

    // Ejecutar comprobación inicial inmediata
    updateAllStatuses();

    // Programar timer: 61 segundos
    // (61s es ideal porque tu backend tiene caché de 60s, así aseguramos obtener el dato fresco)
    monitorGrid._monitoring_timer = setInterval(updateAllStatuses, 61000);
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
            tokenElement.addEventListener('click', function () {
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