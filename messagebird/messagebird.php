<?php

if (!defined('WHMCS'))
    die('This file cannot be accessed directly');

function messagebird_config()
{
    $configarray = array(
        'name'          => 'MessageBird',
        'description'   => 'Integrates MessageBird into WHMCS for SMS Notifcations for Clients.',
        'version'       => '1.0',
        'author'        => 'Jake Walker',
        'fields'        => array(
            'api_key' => array(
                'FriendlyName'  => 'API Key',
                'Type'          => 'text',
                'Size'          => '64',
                'Description'   => 'Add your MessageBird API Key',
                'Default'       => ''
            )
        )
    );

    return $configarray;
}

function messagebird_activate()
{
    return array(
        'status'        => 'success',
        'description'   => 'MessageBird module has been activated. Add your API Key, you can find this by logging into MessageBird.'
    );
}

function messagebird_deactivate()
{
    return array(
        'status'        => 'success',
        'description'   => 'MessageBird module has been deactivated.'
    );
}

function messagebird_upgrade($vars)
{
    // No upgrade path yet.
}

function messagebird_output($vars)
{
    echo '
        <p>To configure, go to Setup -> Addon Modules -> MessageBird -> Configure.</p>
    ';
}

function messagebird_sidebar($vars)
{
    // n/a
}
