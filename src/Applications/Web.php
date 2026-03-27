<?php

declare(strict_types=1);

namespace Jotup\Applications;

use Jotup\Config;

class Web extends Base
{

    public function __construct()
    {
        defined('JOTUP_DEBUG') or define('JOTUP_DEBUG', false);
        $this->registerErrorHandler(Config::get('error.level', E_ALL));
    }
    protected function registerErrorHandler(int $levels): void
    {
        set_error_handler([$this, 'errorHandler'], $levels);
    }

    public function errorHandler(int $level, string $message, string $file = '', int $line = 0): bool
    {
        $error = '<div style="padding: 10px; margin: 10px; font: 14px Arial; background: bisque; border: coral 2px solid; color: #000;">';
        $error .= sprintf('<b>Level:</b> %s<br /><b>Message:</b> %s', $level, $message);
        if ($file) {
            $error .= sprintf('<br /><br />%sFile : %s', $file, $line);
        }
        $error .= '</div>';
        echo($error);
        return true;
    }
}