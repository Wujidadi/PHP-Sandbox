<?php

namespace App\Handlers\SpyFamily;

use App\Handler;
use Libraries\Helper;

/**
 * 《SPY×FAMILY間諜家家酒》中的匯率計算等事項處理類別
 */
class Currency extends Handler
{
    /**
     * 小數取值精確位數
     *
     * @var integer
     */
    const PRECISION = 16;

    /**
     * 匯率設定檔路徑
     *
     * @var string
     */
    protected $_ExchangeRateConfig = BASE_DIR . '/storage/SpyFamily/ExchangeRate.json';

    /**
     * 匯率物件
     *
     * @var object
     */
    protected $_ExchangeRate = null;

    /**
     * Daric 兌日圓匯率
     *
     * @var string
     */
    protected $_DaricJPY;

    /**
     * Daric 兌美元匯率
     *
     * @var string
     */
    protected $_DaricUSD;

    /**
     * Daric 兌新台幣匯率
     *
     * @var string
     */
    protected $_DaricTWD;

    /**
     * Daric 計算基準值
     *
     * @var string
     */
    protected $_Daric;

    /**
     * Daric 計算基準值的小數位數
     *
     * @var integer
     */
    protected $_DaricFracDigit;

    /**
     * 格式化的 Daric 幣值文字
     *
     * @var string
     */
    protected $_FormattedDaric;

    /**
     * Daric 兌換 Pent 的幣值
     *
     * @var string
     */
    protected $_Pent;

    /**
     * Pent 幣值的小數位數
     *
     * @var integer
     */
    protected $_PentFracDigit;

    /**
     * 格式化的 Pent 幣值文字
     *
     * @var string
     */
    protected $_FormattedPent;

    /**
     * Daric 兌換日圓的幣值
     *
     * @var string
     */
    protected $_JPY;

    /**
     * 格式化的日圓幣值文字
     *
     * @var string
     */
    protected $_FormattedJPY;

    /**
     * Daric 兌換新台幣的幣值
     *
     * @var string
     */
    protected $_TWD;

    /**
     * 格式化的新台幣幣值文字
     *
     * @var string
     */
    protected $_FormattedTWD;

    /**
     * Daric 兌換美元的幣值
     *
     * @var string
     */
    protected $_USD;

    /**
     * 格式化的美元幣值文字
     *
     * @var string
     */
    protected $_FormattedUSD;

    protected static $_uniqueInstance = null;

    public static function getInstance(): self
    {
        if (self::$_uniqueInstance == null) self::$_uniqueInstance = new self();
        return self::$_uniqueInstance;
    }

    protected function __construct()
    {
        $this->_className = basename(__FILE__, '.php');
        $this->_DefineExchangeRate();
    }

    /**
     * 解析匯率設定檔，並定義相關變數
     *
     * @return void
     */
    protected function _DefineExchangeRate()
    {
        $data = file_get_contents($this->_ExchangeRateConfig);
        $this->_ExchangeRate = json_decode($data);

        // $this->_DaricJPY = bcdiv('100000', '300', self::PRECISION * 2);    // 根據第 6 卷第 36 話
        $this->_DaricJPY = 320;    // 根據《SPY×FAMILY 公式ファンブック EYES ONLY》第 63 頁
    
        $this->_DaricUSD = bcdiv($this->_DaricJPY, (string) $this->_ExchangeRate->USDJPY, self::PRECISION * 2);
    
        $this->_DaricTWD = bcmul($this->_DaricJPY, (string) $this->_ExchangeRate->JPYTWD, self::PRECISION * 2);
    }

    /**
     * 設定 Daric 計算基準值
     *
     * @param  string|integer  $value  由命令行指定的 Daric 值
     * @return void
     */
    public function SetDaric(string|int $value): void
    {
        $this->_Daric = (string) $value;
        $this->_DaricFracDigit = Helper::GetFractionalDigit($this->_Daric);
        $this->_FormatDaric();

        # 連動呼叫計算及建構匯兌資訊方法，直接生成相關資訊
        $this->_BuildExchangeRateInfo();
    }

    /**
     * 取得 Daric 計算基準值（輸入值）
     *
     * @return string
     */
    public function GetDaric(): string
    {
        return $this->_Daric;
    }

    /**
     * 格式化要輸出的 Daric 幣值文字
     *
     * @return void
     */
    protected function _FormatDaric(): void
    {
        $part = explode('.', $this->_Daric);
        if (count($part) > 1)
        {
            $this->_FormattedDaric = number_format($part[0], 0) . '.' . $part[1];
        }
        else
        {
            $this->_FormattedDaric = number_format($part[0], 0);
        }
    }

    /**
     * 取得格式化的 Daric 幣值文字
     *
     * @return string
     */
    public function GetFormattedDaric(): string
    {
        return $this->_FormattedDaric;
    }

    /**
     * 計算並建構 Daric 兌換 Pent 及其他貨幣的匯兌資訊
     *
     * @return void
     */
    protected function _BuildExchangeRateInfo()
    {
        $this->_SetPent();
        $this->_SetJPY();
        $this->_SetTWD();
        $this->_SetUSD();
    }

    /**
     * 設定 Daric 兌 Pent 幣值
     *
     * @return void
     */
    protected function _SetPent()
    {
        $this->_PentFracDigit = $this->_DaricFracDigit > 2 ? $this->_DaricFracDigit - 2 : 0;
        $this->_Pent = bcmul($this->_Daric, '100', $this->_PentFracDigit);
        $this->_FormatPent();
    }

    /**
     * 取得 Daric 兌 Pent 幣值
     *
     * @return string
     */
    public function GetPent(): string
    {
        return $this->_Pent;
    }

    /**
     * 格式化要輸出的 Pent 幣值文字
     *
     * @return void
     */
    protected function _FormatPent(): void
    {
        $part = explode('.', $this->_Pent);
        if (count($part) > 1)
        {
            $this->_FormattedPent = number_format($part[0], 0) . '.' . $part[1];
        }
        else
        {
            $this->_FormattedPent = number_format($part[0], 0);
        }
    }

    /**
     * 取得格式化後的 Pent 幣值文字
     *
     * @return string
     */
    public function GetFormattedPent(): string
    {
        return $this->_FormattedPent;
    }

    /**
     * 設定 Daric 兌日圓幣值
     *
     * @return void
     */
    protected function _SetJPY()
    {
        $this->_JPY = Helper::TrimTrailingAndCarry(bcmul($this->_Daric, $this->_DaricJPY, self::PRECISION));
        $this->_FormatJPY();
    }

    /**
     * 取得 Daric 兌日圓幣值
     *
     * @return string
     */
    public function GetJPY(): string
    {
        return $this->_JPY;
    }

    /**
     * 格式化要輸出的日圓幣值文字
     *
     * @return void
     */
    protected function _FormatJPY(): void
    {
        $part = explode('.', $this->_JPY);
        if (count($part) > 1)
        {
            $this->_FormattedJPY = number_format($part[0], 0) . '.' . $part[1];
        }
        else
        {
            $this->_FormattedJPY = number_format($part[0], 0);
        }
    }

    /**
     * 取得格式化後的日圓幣值文字
     *
     * @return string
     */
    public function GetFormattedJPY(): string
    {
        return $this->_FormattedJPY;
    }

    /**
     * 設定 Daric 兌新台幣幣值
     *
     * @return void
     */
    protected function _SetTWD()
    {
        $this->_TWD = Helper::TrimTrailingAndCarry(bcmul($this->_Daric, $this->_DaricTWD, self::PRECISION));
        $this->_FormatTWD();
    }

    /**
     * 取得 Daric 兌新台幣幣值
     *
     * @return string
     */
    public function GetTWD(): string
    {
        return $this->_TWD;
    }

    /**
     * 格式化要輸出的新台幣幣值文字
     *
     * @return void
     */
    protected function _FormatTWD(): void
    {
        $part = explode('.', $this->_TWD);
        if (count($part) > 1)
        {
            $this->_FormattedTWD = number_format($part[0], 0) . '.' . $part[1];
        }
        else
        {
            $this->_FormattedTWD = number_format($part[0], 0);
        }
    }

    /**
     * 取得格式化後的新台幣幣值文字
     *
     * @return string
     */
    public function GetFormattedTWD(): string
    {
        return $this->_FormattedTWD;
    }

    /**
     * 設定 Daric 兌美元幣值
     *
     * @return void
     */
    protected function _SetUSD()
    {
        $this->_USD = Helper::TrimTrailingAndCarry(bcmul($this->_Daric, $this->_DaricUSD, self::PRECISION));
        $this->_FormatUSD();
    }

    /**
     * 取得 Daric 兌美元幣值
     *
     * @return string
     */
    public function GetUSD(): string
    {
        return $this->_USD;
    }

    /**
     * 格式化要輸出的美元幣值文字
     *
     * @return void
     */
    protected function _FormatUSD(): void
    {
        $part = explode('.', $this->_USD);
        if (count($part) > 1)
        {
            $this->_FormattedUSD = number_format($part[0], 0) . '.' . $part[1];
        }
        else
        {
            $this->_FormattedUSD = number_format($part[0], 0);
        }
    }

    /**
     * 取得格式化後的美元幣值文字
     *
     * @return string
     */
    public function GetFormattedUSD(): string
    {
        return $this->_FormattedUSD;
    }
}
