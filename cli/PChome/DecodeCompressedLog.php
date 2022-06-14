<?php

chdir(__DIR__);
define('BASE_DIR', dirname(__DIR__, 2));
require_once BASE_DIR . '/bootstrap/cli.php';

use Libraries\Helper;
use App\Handlers\PChome\CompressedLogs\CompressedLogDecoder;

try
{
    CompressedLogDecoder::getInstance()->Run();

    $message = "轉換完畢";
    $messageColor = HEX_COLOR['Gold'];
}
catch (Throwable $ex)
{
    $exType = get_class($ex);
    $exCode = $ex->getCode();
    $exMessage = $ex->getMessage();
    $exTrace = $ex->getTraceAsString();

    $message = "{$exType} ({$exCode}) {$exMessage}\n{$exTrace}";
    $messageColor = HEX_COLOR['Salmon'];
}

echo Helper::ColorText($message, $messageColor, true);
