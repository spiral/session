<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Session\Bootloaders;

use Spiral\Core\Bootloaders\Bootloader;
use Spiral\Session\SectionInterface;
use Spiral\Session\Session;
use Spiral\Session\SessionInterface;
use Spiral\Session\SessionSection;

class SessionBootloader extends Bootloader
{
    const BINDINGS = [
        SessionInterface::class => Session::class,
        SectionInterface::class => SessionSection::class
    ];
}