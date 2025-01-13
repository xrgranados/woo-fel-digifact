<?php

namespace Digifact\Admin;

/**
 * Class SettingsPage
 *
 * Represents a settings page for the DigiFact plugin.
 *
 * @package Digifact\Admin
 * @author Rafael Granados <xr.grandoso@gmail.com>
 */
class SettingsPage
{
    /**
     * Initializes the settings page.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'init']);
    }

    /**
     * Add the menu to the admin page.
     *
     * @return void
     */
    public function addMenu()
    {
        add_submenu_page(
            'digifact',
            'Configuración de Digifact',
            'Configuración',
            'manage_options',
            'digifact-settings',
            [$this, 'renderSettingsPage'],
        );
    }

    /**
     * Initializes the settings page.
     *
     * @return void
     */
    public function init()
    {
        $this->registerSettings();
    }

    /**
     * Registers the settings for the DigiFact plugin.
     *
     * @return void
     */
    public function registerSettings()
    {
        register_setting('digifact_settings_group', 'digifact_settings', [
            'sanitize_callback' => [$this, 'sanitizeSettings']
        ]);

        register_setting('digifact_settings_group', 'digifact_settings');

        add_settings_section('digifact_section', 'Credenciales de Digifact', null, 'digifact-settings');

        $fields = [
            'digifact_nit' => __('NIT', 'fel-digifact'),
            'digifact_name' => __('Nombre', 'fel-digifact'),
            'digifact_email' => __('Email', 'fel-digifact'),
            'digifact_address' => __('Dirección', 'fel-digifact'),
            'digifact_trade_name' => __('Nombre Comercial', 'fel-digifact'),
            'digifact_user' => __('Usuario', 'fel-digifact'),
            'digifact_password' => __('Contraseña', 'fel-digifact')
        ];

        foreach ($fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                [$this, 'displayField'],
                'digifact-settings',
                'digifact_section',
                ['field' => $field, 'label' => $label]
            );
        }

        add_action('admin_notices', [$this, 'displaySettingsNotices']);
    }

    /**
     * Displays a field in the DigiFact settings page.
     *
     * @param array $args An array of arguments for the field. Expected keys are 'field' and 'label'.
     * @return void
     */
    public function displayField($args)
    {
        $field = $args['field'];
        $value = get_option('digifact_settings')[$field] ?? '';
        $type = ($field === 'digifact_password') ? 'password' : 'text';
        echo "<input type='$type' name='digifact_settings[$field]' value='" . esc_attr($value) . "' />";
    }

    /**
     * Sanitizes the DigiFact settings.
     *
     * @param array $input The DigiFact settings.
     * @return array The sanitized DigiFact settings.
     */
    public function sanitizeSettings($input)
    {
        $output = [];
        $errors = [];

        // Custom validations for the DigiFact settings
        if (empty($input['digifact_nit'])) {
            $errors[] = __('El NIT es obligatorio.', 'fel-digifact');
        }

        // verify email
        if (!filter_var($input['digifact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('El email no es válido.', 'fel-digifact');
        }

        if (empty($input['digifact_user'])) {
            $errors[] = __('El usuario es obligatorio.', 'fel-digifact');
        }
        if (empty($input['digifact_password'])) {
            $errors[] = __('La contraseña es obligatoria.', 'fel-digifact');
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                add_settings_error(
                    'digifact_settings',
                    'digifact_settings_error',
                    $error,
                    'error'
                );
            }
            return get_option('digifact_settings');
        }

        $output['digifact_nit'] = sanitize_text_field($input['digifact_nit']);
        $output['digifact_user'] = sanitize_text_field($input['digifact_user']);
        $output['digifact_password'] = sanitize_text_field($input['digifact_password']);
        $output['digifact_name'] = sanitize_text_field($input['digifact_name']);
        $output['digifact_email'] = sanitize_text_field($input['digifact_email']);
        $output['digifact_address'] = sanitize_text_field($input['digifact_address']);
        $output['digifact_trade_name'] = sanitize_text_field($input['digifact_trade_name']);

        // add error if settings are not saved
        add_settings_error(
            'digifact_settings',
            'digifact_settings_updated',
            'Configuración de Digifact actualizada correctamente.',
            'updated'
        );

        return $output;
    }

    /**
     * Displays the settings notices.
     *
     * @return void
     */
    public function displaySettingsNotices()
    {
        // show notices error/notifications settings
        settings_errors('digifact_settings');
    }

    /**
     * Renders the settings page.
     *
     * @return void
     */
    public function renderSettingsPage()
    {
        ?>
        <div class="wrap">
            <div id="digifact-settings-container" class="df-container">
                <div class="df-logo">
                    <img src="<?php echo plugins_url('assets/img/digifact-logo.png', dirname(__DIR__, 2)); ?>" alt="DigiFact" class="img-fluid border-none">
                </div>

                <div class="df-content">
                    <h2 class="text-2xl font-bold mb-4"><?php _e('Configuración de Digifact', 'fel-digifact'); ?></h2>
                    <hr>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('digifact_settings_group');
                        do_settings_sections('digifact-settings');
                        submit_button();
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
} // End SettingsPage class
