<?php

namespace App\Handlers\PChome\CompressedLogs;

use App\Handler;

/**
 * 解壓縮並反序列化日誌處理器
 */
class CompressedLogDecoder extends Handler
{
    /**
     * 輸入值檔案路徑
     *
     * 檔案內容應為經 `serialize`、`gzcompress` 和 `base64_encode` 三道處理的序列化壓縮日誌資料
     *
     * @var string
     */
    protected $_InputFile = BASE_DIR . '/storage/PChome/CompressedLogs/input.txt';

    /**
     * 輸出檔案路徑
     *
     * 檔案內容應為解壓縮並反序列化後，人類可讀的原始日誌資料
     *
     * @var string
     */
    protected $_OutputFile = BASE_DIR . '/storage/PChome/CompressedLogs/output.txt';

    /**
     * 要處理的日誌資料
     *
     * @var string
     */
    protected $_Data = '';

    protected static $_uniqueInstance = null;

    public static function getInstance(): self
    {
        if (self::$_uniqueInstance == null) self::$_uniqueInstance = new self();
        return self::$_uniqueInstance;
    }

    protected function __construct()
    {
        $this->_className = basename(__FILE__, '.php');
    }

    /**
     * 執行轉換工作
     *
     * @return void
     */
    public function Run()
    {
        $this->_GetInputText();
        $this->_ConvertData();
        $this->_SetOutputText();
    }

    /**
     * 取得輸入（序列化壓縮日誌）資料
     *
     * @return void
     */
    protected function _GetInputText()
    {
        $this->_Data = file_get_contents($this->_InputFile);
    }

    /**
     * 將輸入資料解壓縮並反序列化
     *
     * @return void
     */
    protected function _ConvertData()
    {
        $this->_Data = unserialize(gzuncompress(base64_decode($this->_Data)));
    }

    /**
     * 寫入輸出（人類可讀的原始日誌）資料
     *
     * @return void
     */
    protected function _SetOutputText()
    {
        file_put_contents($this->_OutputFile, $this->_Data);
    }
}
