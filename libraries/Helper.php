<?php

namespace Libraries;

/**
 * 輔助工具類別
 */
class Helper
{
    /**
     * 檢查輸入參數是否為 **整數** 或 **字串型態的整數**
     *
     * @param  mixed  $num  待檢參數
     * @return boolean
     */
    public static function IsInteger(mixed $num): bool
    {
        return is_numeric($num) && preg_match('/^\-?\d+$/', $num);
    }

    /**
     * 移除輸入數字字串末尾的 0 和小數點
     *
     * @param  string  $num  待檢參數
     * @return string
     */
    public static function TrimTrailingZeroAndPoint(string $num): string
    {
        if (preg_match('/\.\d*0$/', $num))
        {
            $num = preg_replace('/0+$/', '', $num);
            if (preg_match('/\.$/', $num))
            {
                $num = preg_replace('/\.$/', '', $num);
            }
        }
        return $num;
    }

    /**
     * 輸入數字字串具有小數部分時，移除末尾的 0、連續 9（移除後進位）和小數點
     *
     * @param  string  $num  待檢參數
     * @return string
     */
    public static function TrimTrailingAndCarry(string $num): string
    {
        # 字串具小數部分末尾有最少 3 個 9 時，整併進位之
        $regex = '/\d\.\d*[^9]?(9{3,})$/';

        # 循環清除末尾連續的 9 直到少於 3 個 9
        while (preg_match($regex, $num, $matches))
        {
            # 取得末尾連續 9 的個數
            $trailLength = strlen($matches[1]);

            # 從原數字字串移除末尾連續的 9
            $num = substr((string) $num, 0, -$trailLength + 1);

            # 若移除連續 9 之後已無小數部分，這時小數點仍然存在，須去除之
            $num = Helper::TrimTrailingZeroAndPoint($num);

            # 取得剩餘的小數部分長度
            $fracLength = strlen(explode('.', $num)[1]);

            # 取小數部分長度減一位四捨五入
            $num = round($num, $fracLength - 1);
        }

        # 最後再次清理末尾的 0 或小數點
        return self::TrimTrailingZeroAndPoint($num);
    }

    /**
     * 取得輸入參數的小數部分位數；不符合格式時一律返回 0
     *
     * @param  mixed  $num  待檢參數
     * @return integer
     */
    public static function GetFractionalDigit(mixed $num): int
    {
        if (is_numeric($num) && preg_match('/\./', $num))
        {
            $strNum = self::TrimTrailingZeroAndPoint($num);
            $arrNum = explode('.', $strNum);
            if (count($arrNum) > 1)
            {
                return strlen($arrNum[1]);
            }
        }
        return 0;
    }

    /**
     * 格式化命令行輸出
     *
     * @param  string   $text       輸出字串
     * @param  string   $hexColor   `#RRGGBB` 格式色碼
     * @param  boolean  $breakLine  最後是否換行，預設 `false`
     * @param  boolean  $underline  字元是否帶底線，預設 `false`
     * @return string               ANSI 格式化輸出字元
     */
    public static function ColorText(string $text, string $hexColor = '', bool $breakLine = false, bool $underline = false): string
    {
        $eot = $breakLine ? PHP_EOL : '';
        $udl = $underline ? ';4' : '';
    
        if ($hexColor === '' || is_null($hexColor))
        {
            return "{$text}{$eot}";
        }
        else
        {
            list($r, $g, $b) = sscanf($hexColor, '#%02X%02X%02X');
            return "\033[38;2;{$r};{$g};{$b}{$udl}m{$text}\033[0m{$eot}";
        }
    }
}
