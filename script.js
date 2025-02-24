document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const toggleButton = document.querySelector('.toggle-btn');
    const themeCheckbox = document.getElementById('theme-checkbox');
    const body = document.body;
    const navLinks = document.querySelectorAll('nav ul li a');
    const contentPlaceholder = document.getElementById('content-placeholder');
    const logoutIcon = document.getElementById('logout-icon');

    toggleButton.addEventListener('click', function () {
        sidebar.classList.toggle('collapsed');
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
                    alert("Error: No se pudo obtener el número de teléfono.");
                    return;
                }
                this.disabled = true;
                this.textContent = 'Conectando...';

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
                        alert('Error al conectar el número');
                    }
                } catch (error) {
                    console.error("Error de red:", error);
                    alert('Error de red al conectar el número.');
                } finally {
                    this.disabled = false;
                    this.textContent = 'Conectar';
                }
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async function () {
                const phoneId = this.getAttribute('data-phone-id');
                const phoneNumber = this.getAttribute('data-phone-number');
                if (!phoneId || !phoneNumber) {
                    alert("Error: No se pudo obtener la información necesaria para cerrar sesión.");
                    return;
                }

                if (confirm("¿Estás seguro de que deseas cerrar sesión de este número?")) {
                    this.disabled = true;
                    this.textContent = 'Cerrando Sesión...';

                    try {
                        const disconnectResponse = await fetch('pages/mis_telefonos.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                'action': 'delete',
                                'phoneNumber': phoneNumber
                            })
                        });

                        if (!disconnectResponse.ok) {
                            console.error("Error al desconectar:", await disconnectResponse.text());
                            alert('Error al cerrar sesión del número.');
                            this.disabled = false;
                            this.textContent = 'Cerrar Sesión';
                            return;
                        }

                        const deleteResponse = await fetch('pages/mis_telefonos.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                'action': 'delete',
                                'id': phoneId,
                                'phoneNumber': phoneNumber
                            })
                        });

                        if (deleteResponse.ok) {
                            loadContent('pages/mis_telefonos.php');
                        } else {
                            console.error("Error en la solicitud delete:", await deleteResponse.text());
                            alert('Error al cerrar sesión del número.');
                        }
                    } catch (error) {
                        console.error("Error de red:", error);
                        alert('Error de red al cerrar sesión del número.');
                    } finally {
                        this.disabled = false;
                        this.textContent = 'Cerrar Sesión';
                    }
                }
            });
        });

               // Evento para envío de mensajes
               const sendMessageForm = document.getElementById('sendMessageForm');
if (sendMessageForm) {
    sendMessageForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        // Obtener la URL desde el atributo data-api-send-chat-url del contenedor
        const apiSendChatUrl = this.closest('.card').dataset.apiSendChatUrl;

        const apiToken = document.getElementById('apiTokenSend').value;
        const waAccount = document.getElementById('waAccountSend').value;
        const recipientAccount = document.getElementById('recipientAccountSend').value;
        const messageText = document.getElementById('messageTextSend').value;
        const responseContainer = document.getElementById('sendMessageResponse');

        if (!apiToken || !waAccount || !recipientAccount || !messageText) {
            alert("Todos los campos son obligatorios.");
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
                    text: messageText
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
                 registerPhoneResponse.innerHTML += ' Envíando solicitud...'; // Add this line

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
                     registerPhoneResponse.appendChild(volverBtn); // Append the button to the response container
                    } else {
                        registerPhoneResponse.innerHTML = `<p class="error">${data.message}</p>`;
                    }
                    phoneNumberInput.value = ''; // Clear the input field regardless of success or failure
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
                         changePasswordResponse.innerHTML = `<p class="success">${data.message}</p>`;
                    } else {
                        changePasswordResponse.innerHTML = `<p class="error">${data.message}</p>`;
                    }
                } catch (error) {
                    console.error('Error de red:', error);
                    changePasswordResponse.innerHTML = `<p class="error">Error de red al cambiar la contraseña.</p>`;
                }
            });
        }
    }

    function validatePhoneNumber(phoneNumber) {
        const phoneNumberRegex = /^584\d{9,10}$/; // Regular expression for 584XXXXXXXXX or 584XXXXXXXXXX
        const phoneNumberInput = document.getElementById('numero');

        if (!phoneNumberRegex.test(phoneNumber)) {
            alert('Por favor, ingrese un número de teléfono válido en formato venezolano (ej: 584123456789 o 584241234567). Debe comenzar con 584 y tener entre 12 y 13 dígitos.');
            phoneNumberInput.focus();
            return false;
        }
        return true;
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

    // Inicializar eventos en la primera carga
    initEventListeners();
    initRegisterPhoneFormListener();
      initChangePasswordFormListener();

});