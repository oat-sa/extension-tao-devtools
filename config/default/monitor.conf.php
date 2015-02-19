<?php
/**
 * Default config header
 *
 * To replace this add a file generis/conf/header/monitor.conf.php
 */

return array(
    'enabled' => false,
    'adapters' => array(
        'tao' => array(
            'writeOnlyDuplicated' => true, //Write only a report for requests that have duplicated Calls
        )
    ),
    'proxyPersistenceMap' => array(
        'default' => array(
            'driver' => 'oat\taoDevTools\models\Monitor\Persistence\SqlProxyDriver'
        )
    )

);
