
jQuery(document).ready(function($) {
    // Verificar preferencia guardada
    const darkMode = localStorage.getItem('darkMode');
    
    // Función para activar modo oscuro
    const enableDarkMode = () => {
        $('body').addClass('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
    }

    // Función para desactivar modo oscuro
    const disableDarkMode = () => {
        $('body').removeClass('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
    }

    // Aplicar modo oscuro si estaba activado
    if (darkMode === 'enabled') {
        enableDarkMode();
    }

    // Evento click del botón
    $('#dark-mode-toggle').click(function() {
        const darkMode = localStorage.getItem('darkMode');
        if (darkMode !== 'enabled') {
            enableDarkMode();
        } else {
            disableDarkMode();
        }
    });

    // Detectar preferencia del sistema
    if (window.matchMedia && !localStorage.getItem('darkMode')) {
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            enableDarkMode();
        }
    }
});

// ...existing code...