<?php
/**
 * Type interface
 *
 * PHP Version 5.4
 *
 * @category Sync
 * @package  CodeYellow\Sync\
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT Licence http://opensource.org/licenses/MIT
 * @link     https://github.com/codeyellowbv/sync
 */

namespace CodeYellow\Sync;

/**
 * Contains constants for the different types of request
 *
 * @category Sync
 * @package  CodeYellow\Sync\
 * @author   Stefan Majoor <stefan@codeyellow.nl>
 * @license  MIT Licence http://opensource.org/licenses/MIT
 * @link     https://github.com/codeyellowbv/sync
 */
interface Type
{
    const TYPE_NEW      = 'new';
    const TYPE_MODIFIED = 'modified';
}
