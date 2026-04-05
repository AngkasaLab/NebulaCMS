<?php

return [
    // Layout — Step labels
    'step_requirements' => 'Requirements',
    'step_database'     => 'Database',
    'step_site'         => 'Site',
    'step_admin'        => 'Admin',
    'step_install'      => 'Install',
    'step_done'         => 'Done',

    // Welcome (Requirements)
    'requirements_title'       => 'System Requirements',
    'requirements_lead'        => 'Make sure your server meets all the following requirements before continuing the installation.',
    'php_min'                  => 'minimum :version',
    'status_ok'                => 'OK',
    'status_not_met'           => 'Not met',
    'extensions_title'         => 'PHP Extensions',
    'ext_available'            => 'Available',
    'ext_not_found'            => 'Not found',
    'directories_title'        => 'Directory Permissions',
    'dir_writable'             => 'Writable',
    'dir_not_writable'         => 'Not writable',
    'requirements_not_met'     => 'Requirements not met.',
    'requirements_not_met_sub' => 'Fix the failed items above before continuing.',
    'btn_continue'             => 'Continue',
    'btn_back'                 => 'Back',

    // Database
    'database_title'      => 'Database Configuration',
    'database_lead'       => 'Enter your MySQL / MariaDB connection details.',
    'connection_failed'   => 'Connection failed:',
    'label_host'          => 'Host',
    'label_port'          => 'Port',
    'label_database_name' => 'Database Name',
    'label_username'      => 'Username',
    'label_password'      => 'Password',
    'password_optional'   => '(optional)',
    'btn_test_connection' => 'Test Connection',
    'testing'             => 'Testing…',
    'connection_success'  => 'Connected successfully',
    'server_error'        => 'Could not reach server',

    // Site
    'site_title'          => 'Site Settings',
    'site_lead'           => 'Basic site configuration. This can be changed later via the admin panel.',
    'label_site_name'     => 'Site Name',
    'label_site_url'      => 'Site URL',
    'label_environment'   => 'Environment',
    'env_production'      => 'Production',
    'env_production_desc' => 'Debug disabled. For live sites.',
    'env_development'     => 'Development',
    'env_development_desc'=> 'Debug enabled. For development.',
    'label_default_lang'  => 'Default Language',

    // Account
    'account_title'       => 'Administrator Account',
    'account_lead'        => 'Create a super admin account to manage your site.',
    'label_name'          => 'Name',
    'label_email'         => 'Email',
    'label_confirm_pass'  => 'Confirm Password',
    'placeholder_min_chars'=> 'Minimum 8 characters',
    'placeholder_repeat'  => 'Repeat password',
    'account_info'        => 'This account will have full access as a <strong>Super Administrator</strong>.',
    'btn_start_install'   => 'Start Installation',

    // Installing
    'installing_title'       => 'Installing NebulaCMS',
    'installing_lead'        => 'Please wait, do not close this page.',
    'install_step_1'         => 'Writing configuration file',
    'install_step_2'         => 'Generating application key',
    'install_step_3'         => 'Database migration & access roles',
    'install_step_4'         => 'Creating administrator account',
    'install_step_5'         => 'Seeding sample content',
    'install_step_6'         => 'Finalizing installation',
    'install_failed'         => 'Installation failed.',
    'install_back_retry'     => '← Go back and try again',
    'unexpected_error'       => 'An unexpected error occurred.',
    'cannot_reach_server'    => 'Cannot reach server: ',

    // Installing — controller messages
    'install_step_1_label'   => 'Writing configuration file (.env)',
    'install_step_2_label'   => 'Generating application key',
    'install_step_3_label'   => 'Database migration & access roles',
    'install_step_4_label'   => 'Creating administrator account',
    'install_step_5_label'   => 'Seeding sample content',
    'install_step_6_label'   => 'Finalizing installation',
    'invalid_install_request'=> 'Invalid install request. Reload the page or start again from the admin step.',
    'session_expired'        => 'Install session expired or unknown. Please retry from the admin step.',
    'invalid_install'        => 'Invalid installation. Restart from the admin step.',
    'db_connection_success'  => 'Connection successful!',

    // Done
    'done_title'             => 'Installation Complete',
    'done_lead'              => 'NebulaCMS is ready to use.',
    'done_config_written'    => 'Configuration file written',
    'done_migration'         => 'Database migration complete',
    'done_seeded'            => 'Initial data seeded',
    'done_admin_created'     => 'Administrator account created',
    'done_locked'            => 'The installer is now automatically locked. Keep your admin credentials in a safe place.',
    'btn_go_admin'           => 'Go to Admin',
    'btn_view_site'          => 'View Site',

    // Language switcher
    'lang_en' => 'English',
    'lang_id' => 'Bahasa Indonesia',
];
