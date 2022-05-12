<?php

namespace App;

/**
 * 處理器元類別
 */
abstract class Handler
{
    /**
     * 處理器物件名稱
     *
     * @var string
     */
    protected $_className;

    /**
     * 處理器物件單一實例
     *
     * @var self|null
     */
    protected static $_uniqueInstance;

    /**
     * 取得處理器物件實例
     *
     * @return self
     */
    abstract public static function getInstance();

    /**
     * 建構子
     */
    protected function __construct() {}
}
