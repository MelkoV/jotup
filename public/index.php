<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

defined('JOTUP_DEBUG') or define('JOTUP_DEBUG', \Jotup\Env::getBool('APP_DEBUG'));

$app = new \Jotup\Applications\Web();

echo '<html><body style="background: #191a1c; color: #b7b7b7;">';



echo '</body></html>';