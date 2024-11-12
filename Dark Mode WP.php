<?php
/*
Plugin Name: Dark Mode WP
Plugin URI: https://www.oliverodev.com/
Description: Añade un modo oscuro a tu sitio web de WordPress con soporte para Elementor.
Version: 1.1
Author URI: https://www.oliverodev.com/
Author: Alexis Olivero
*/

if (!defined('ABSPATH')) exit;

define('DARK_MODE_WP_VERSION', '1.1');

function dark_mode_wp_enqueue_scripts() {
    // Asegurar que los estilos se carguen después de Elementor
    wp_enqueue_style(
        'dark-mode-wp-style', 
        plugin_dir_url(__FILE__) . 'css/dark-mode-wp.css',
        array('elementor-frontend'),
        DARK_MODE_WP_VERSION
    );
    
    // Prioridad muy alta para sobreescribir estilos de Elementor
    wp_style_add_data('dark-mode-wp-style', 'priority', 999);
    
    wp_enqueue_script(
        'dark-mode-wp-script', 
        plugin_dir_url(__FILE__) . 'js/dark-mode-wp.js', 
        array('jquery', 'elementor-frontend'), 
        DARK_MODE_WP_VERSION, 
        true
    );

    // Pasar variables al JavaScript
    wp_localize_script('dark-mode-wp-script', 'darkModeWP', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dark_mode_wp_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'dark_mode_wp_enqueue_scripts', 999);

// Añadir botón del modo oscuro
function dark_mode_wp_add_button() {
    echo '<button 
        id="dark-mode-toggle" 
        class="dark-mode-toggle" 
        aria-label="Alternar modo oscuro"
        title="Alternar modo oscuro"
        data-dark-mode="false">
        <span class="screen-reader-text">Alternar modo oscuro</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
    </button>';
}
add_action('wp_footer', 'dark_mode_wp_add_button');

// Guardar preferencia del usuario
function dark_mode_wp_save_preference() {
    check_ajax_referer('dark_mode_wp_nonce', 'nonce');
    $dark_mode = isset($_POST['dark_mode']) ? sanitize_text_field($_POST['dark_mode']) : 'false';
    set_cookie('wp_dark_mode_preference', $dark_mode, time() + YEAR_IN_SECONDS);
    wp_send_json_success();
}
add_action('wp_ajax_dark_mode_wp_save_preference', 'dark_mode_wp_save_preference');
add_action('wp_ajax_nopriv_dark_mode_wp_save_preference', 'dark_mode_wp_save_preference');

// Añadir soporte para Elementor y contenido de posts
function dark_mode_wp_elementor_support() {
    if (!did_action('elementor/loaded')) {
        return;
    }
    
    add_filter('body_class', function($classes) {
        global $post;
        $force_dark = false;
        
        if (is_singular() && $post) {
            $force_dark = get_post_meta($post->ID, '_force_dark_mode', true) === '1';
        }
        
        if ($force_dark || (isset($_COOKIE['wp_dark_mode_preference']) && $_COOKIE['wp_dark_mode_preference'] === 'true')) {
            $classes[] = 'dark-mode-active';
            $classes[] = 'elementor-dark-mode';
            $classes[] = 'dark-mode-content';
            if ($force_dark) {
                $classes[] = 'dark-mode-forced';
            }
        }
        return $classes;
    });

    // Añadir clases a los contenedores de posts
    add_filter('post_class', function($classes) {
        if (isset($_COOKIE['wp_dark_mode_preference']) && $_COOKIE['wp_dark_mode_preference'] === 'true') {
            $classes[] = 'dark-mode-post';
        }
        return $classes;
    });
}
add_action('init', 'dark_mode_wp_elementor_support');

// Añadir estilos específicos para posts en el head
function dark_mode_wp_add_custom_styles() {
    ?>
    <style>
        .dark-mode-active .elementor-widget-theme-post-content,
        .dark-mode-active .elementor-post__text,
        .dark-mode-active .elementor-post__title,
        .dark-mode-active .elementor-widget-container p,
        .dark-mode-active .elementor-text-editor,
        .dark-mode-active .entry-content {
            color: #ffffff !important;
        }
        
        .dark-mode-active .elementor-post,
        .dark-mode-active article.post {
            background-color: #2d2d2d !important;
        }

        .dark-mode-active .elementor-widget-container h1,
        .dark-mode-active .elementor-widget-container h2,
        .dark-mode-active .elementor-widget-container h3,
        .dark-mode-active .elementor-widget-container h4,
        .dark-mode-active .elementor-widget-container h5,
        .dark-mode-active .elementor-widget-container h6 {
            color: #ffffff !important;
        }

        /* Estilos para modo oscuro forzado */
        .dark-mode-forced {
            background-color: #1a1a1a !important;
        }
        
        .dark-mode-forced .elementor-widget-container,
        .dark-mode-forced .elementor-post,
        .dark-mode-forced article.post,
        .dark-mode-forced .elementor-section,
        .dark-mode-forced .elementor-column-wrap {
            background-color: #2d2d2d !important;
        }
        
        .dark-mode-forced .elementor-post__card {
            background-color: #333333 !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'dark_mode_wp_add_custom_styles', 999);

// Agregar metabox para forzar modo oscuro
function dark_mode_wp_add_meta_box() {
    add_meta_box(
        'dark_mode_wp_force',
        'Modo Oscuro Forzado',
        'dark_mode_wp_meta_box_html',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'dark_mode_wp_add_meta_box');

// HTML del metabox
function dark_mode_wp_meta_box_html($post) {
    $force_dark = get_post_meta($post->ID, '_force_dark_mode', true);
    ?>
    <label>
        <input type="checkbox" name="force_dark_mode" value="1" <?php checked($force_dark, '1'); ?>>
        Forzar modo oscuro en este post
    </label>
    <?php
}

// Guardar la configuración del metabox
function dark_mode_wp_save_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $force_dark = isset($_POST['force_dark_mode']) ? '1' : '0';
    update_post_meta($post_id, '_force_dark_mode', $force_dark);
}
add_action('save_post', 'dark_mode_wp_save_meta_box');
