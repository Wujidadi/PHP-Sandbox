<?php

chdir(__DIR__);
define('BASE_DIR', dirname(__DIR__, 2));
require_once BASE_DIR . '/bootstrap/cli.php';

use Libraries\Helper;
use App\Handlers\SpyFamily\Currency;

# 建立匯率物件實例
$currecyObject = Currency::getInstance();

# 取得命令行參數
$option = getopt('d:', []);    // 參數 d for Daric

# 定義 Daric 金額
$daric = (isset($option['d']) && is_numeric($option['d'])) ? $daric = $option['d'] : '1';    // 預設 1D

# 定義 Daric 金額
$currecyObject->SetDaric($daric);
$formattedDaric = $currecyObject->GetFormattedDaric();
$formattedPent = $currecyObject->GetFormattedPent();
$formattedJPY = $currecyObject->GetFormattedJPY();
$formattedTWD = $currecyObject->GetFormattedTWD();
$formattedUSD = $currecyObject->GetFormattedUSD();

# 組合輸出內容
$conversionMessage = sprintf("%s\n%s\n%s\n%s\n%s",
    '   ' . $formattedDaric  . ' D (ダルク, Daric)',
    Helper::ColorText(' = ' . $formattedPent . ' P (ペント, Pent)', HEX_COLOR['Yellow']),
    Helper::ColorText(' = ' . $formattedJPY  . ' JPY (円, 日圓)',   HEX_COLOR['Pink']),
    Helper::ColorText(' = ' . $formattedTWD  . ' TWD (新臺幣元)',   HEX_COLOR['LimeGreen']),
    Helper::ColorText(' = ' . $formattedUSD  . ' USD (美元)',       HEX_COLOR['CornflowerBlue']),
);

# 輸出
echo $conversionMessage;
