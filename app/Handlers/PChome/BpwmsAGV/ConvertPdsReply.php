<?php

namespace App\Handlers\PChome\BpwmsAGV;

use App\Handler;

/**
 * PChome 金財通 WMS 派發出庫指示至極智嘉 AGV，產生 AGV 回應格式資料的處理類別
 */
class ConvertPdsReply extends Handler
{
    const OWNER_CODE = '010001';

    /**
     * 輸入值 JSON 檔路徑
     *
     * JSON 檔內容應為符合 `wcs_trans_log` 資料表 `WTL_DISPATCHER_RAW_DATA` 欄位格式的 JSON 資料
     *
     * @var string
     */
    protected $_InputFile = BASE_DIR . '/storage/PChome/BpwmsAGV/PdsReply/input.json';

    /**
     * 輸入資料物件
     *
     * @var object
     */
    protected $_InputData;

    /**
     * 輸出 JSON 檔路徑
     *
     * JSON 檔內容應內容為符合 AGV 出庫單按箱回傳 API 格式的 JSON 資料
     *
     * @var string
     */
    protected $_OutputFile = BASE_DIR . '/storage/PChome/BpwmsAGV/PdsReply/output.json';

    /**
     * 輸出資料物件
     *
     * @var object
     */
    protected $_OutputData;

    /**
     * 轉換開始的時間戳
     *
     * @var integer
     */
    protected $_StartTime;

    /**
     * 容器編號
     *
     * @var string
     */
    protected $_ContainerCode;

    /**
     * 偽貨架及儲位編號基底值
     *
     * @var integer
     */
    protected $_FakePosCode;

    /**
     * 品項編號陣列
     *
     * @var string[]
     */
    protected $_ItemList = [];

    /**
     * 總品項數
     *
     * @var integer
     */
    protected $_TotalItem = 0;

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
        $this->_StartTime = time();
        $this->_ContainerCode = '12A' . $this->_RandomLetter() . substr((string) $this->_StartTime, -6);
        $this->_FakePosCode = (int) date('YmdHis');
        $this->_GetInputJSON();
        $this->_ConvertData();
        $this->_SetOutputJSON();
    }

    /**
     * 取得 JSON 輸入值
     *
     * @return void
     */
    protected function _GetInputJSON()
    {
        $json = file_get_contents($this->_InputFile);

        $input = json_decode($json);

        if (!is_null($input))
        {
            $this->_InputData = $input;
        }
        else
        {
            throw new \Exception('輸入 JSON 格式不正確', 1);
        }
    }

    /**
     * 轉換 `wcs_trans_log`.`WTL_DISPATCHER_RAW_DATA` 格式的輸入資料為 AGV 按箱回傳格式
     *
     * @return void
     */
    protected function _ConvertData()
    {
        $skuList = [];

        foreach ($this->_InputData->SkuList as $i => $sku)
        {
            if ($i > 0)
            {
                $this->_FakePosCode++;
            }

            if (!in_array($sku->SkuCode, $this->_ItemList))
            {
                $this->_ItemList[] = $sku->SkuCode;
            }

            $skuList[] = [
                'out_order_code' => $this->_InputData->OrderCode,
                'item' => $sku->RowNum,
                'sku_code' => $sku->SkuCode,
                'sku_level' => $sku->SkuLevel,
                'amount' => $sku->SkuQty,
                'owner_code' => self::OWNER_CODE,
                'expiration_date' => isset($sku->ExpiryDate) ? strtotime($sku->ExpiryDate) * 1000 : null,
                'out_batch_code' => $sku->OutBatchCode,
                'pick_order_item_finish_time' => isset($sku->CompleteTime) ? strtotime($sku->CompleteTime) * 1000 : $this->_GetPresentMillisecond(),
                'lack_flag' => 0,
                'is_last_container' => 1,
                'container_amount' => 1,
                'sequence_no' => [],
                'shelf_bin_list' => [
                    [
                        'shelf_code' => "FSC{$this->_FakePosCode}",
                        'shelf_bin_code' => "FSB{$this->_FakePosCode}",
                        'quantity' => $sku->SkuQty
                    ]
                ]
            ];
        }

        $this->_TotalItem = count($this->_ItemList);

        $this->_OutputData = [
            'header' => [
                'warehouse_code' => 'geekplus',
                'interface_code' => 'feedback_outbound_container',
                'user_id' => 'fakeReplyByWCS',
                'user_key' => ''
            ],
            'body' => [
                'warehouse_code' => 'geekplus',
                'container_list' => [
                    [
                        'container_code' => $this->_ContainerCode,
                        'container_type' => 2,
                        'pallet_code' => '',
                        'sku_amount' => $this->_InputData->SkuTotal,
                        'sku_type_amount' => $this->_TotalItem,
                        'creation_date' => $this->_StartTime * 1000,
                        'picker' => "FAKE",
                        'sku_list' => $skuList
                    ]
                ]
            ]
        ];
    }

    /**
     * 寫入 JSON 輸出值
     *
     * @return void
     */
    protected function _SetOutputJSON()
    {
        $json = json_encode($this->_OutputData, 448);
        file_put_contents($this->_OutputFile, $json);
    }

    /**
     * 取得當前的毫秒級時間戳
     *
     * @return integer
     */
    protected function _GetPresentMillisecond()
    {
        $now = explode('.', (string) microtime(true));
        $integerPart = $now[0];
        $decimalPart = $now[1];
        $msPart = str_pad(substr($decimalPart, 0, 3), 3, '0');
        return (int) ($integerPart . $msPart);
    }

    /**
     * 隨機產生一個大寫英文字母
     *
     * @return string
     */
    protected function _RandomLetter()
    {
        $dict = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $seed = mt_rand(0, 25);
        return substr($dict, $seed, 1);
    }
}
