<?php

declare(strict_types=1);

namespace Jotup\Log\Routes;

use Jotup\Log\LogData;

class Bootstrap extends Route
{

    public function write(LogData $data): void
    {
        $error = '<div style="padding: 10px; margin: 10px; font: 14px Arial; background: bisque; border: coral 2px solid; color: #000;">';
        $error .= sprintf('<b>Level:</b> %s<br /><b>Message:</b> %s', $data->level, $data->message);
        if ($data->context) {
            $error .= '<p>' . json_encode($data->context) . '</p>';
        }
        $error .= '</div>';
        echo($error);
    }
}