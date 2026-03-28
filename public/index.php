<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

defined('JOTUP_DEBUG') or define('JOTUP_DEBUG', \Jotup\Env::getBool('APP_DEBUG'));
defined('APP_CORE_PATH') or define('APP_CORE_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

$bootstrap = new \App\Bootstrap();
$app = new \Jotup\Application\Web($bootstrap);

echo '<html><body style="background: #191a1c; color: #b7b7b7;">';


echo '</body></html>';