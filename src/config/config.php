<?php
/**
 * Sample Config file
 *
 * PHP Version 5.4
 *
 * @category Sync
 * @package  CodeYellow\Sync\
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT Licence http://opensource.org/licenses/MIT
 * @link     https://github.com/codeyellowbv/sync
 *
 */
return array(
    'servers' => array(
        'example' => array(
            'url' => 'example.com/sync/example',
        )
    ),
    // Amount of seconds before a transaction becomes final. This is to ensure that all pending transactions are
    // finished.  However, it also causes a delay in syncing the data. Also note, that an additional 1 second
    // grace is always added. i.e. a grace window of 10 means that we wait between 10 and 11 seconds to make
    // the db state final, and distribute it
    'grace_window' => 10
);
