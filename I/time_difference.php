<?PHP

//
// 現在の時間と任意の時間の差を返すクラスです。
// 任意の時間は "Y-m-d H:i:s"という形式のみサポートします。
// また、差は日数、時間、分の何れかを使えます。
// $_nDTKindに0(日数)か1(時間)か2(分)を入力。何も入れないと日数で返します。
// なお、端数は切り上げです。40分とかなら、1時間とカウント。20時間は1日。30秒は1分です。
// by h.nagasawa('A`)
//
/**
 * 現在の時間と任意の時間の差を返すクラスです。
 * 任意の時間は "Y-m-d H:i:s"という形式のみサポートします。
 * また、差は日数、時間、分の何れかを使えます。
 * $_nDTKindに0(日数)か1(時間)か2(分)を入力。何も入れないと日数で返します。
 * なお、端数は切り上げです。40分とかなら、1時間とカウント。20時間は1日。30秒は1分です。
 * @package Cbase.Research.Lib
*/
class time_difference
{
    public $m_nTimeDiff;

    /**
     *  コンストラクタ
     */
    public function time_difference()
    {
        $this->m_nTimeDiff = 0;
    }

    /**
     * 今の時間との時間計算します
     * @param mixed $_szTime  ターゲットの時間
     * @param int   $_nDTKind モード
     */
    public function SetTimeDiff($_szTime,$_nDTKind = 0)
    {
        /* 現在の日付時間 */
        $szNowTime = date("Y-m-d-H-i-s");

        /* 元の日付時間 */
        $_szTime = str_replace(":","-",$_szTime);
        $_szTime = str_replace(" ","-",$_szTime);

        /* マイクロ秒に戻す */
        list($yr1,$mt1,$dy1,$hr1,$mn1,$sc1) = explode("-",$_szTime);
        list($yr2,$mt2,$dy2,$hr2,$mn2,$sc2) = explode("-",$szNowTime);
        $nDatetime1 = mktime($hr1,$mn1,$sc1,$mt1,$dy1,$yr1); // 元の
        $nDatetime2 = mktime($hr2,$mn2,$sc2,$mt2,$dy2,$yr2); // 現在

        switch ($_nDTKind) {
            case 0:
                /* 1日は86400秒なので割る */
                $fTimeDiff = ceil(($nDatetime2 - $nDatetime1) / 86400);
                break;
            case 1:
                /* 1時間は3600秒なので割る */
                $fTimeDiff = ceil(($nDatetime2 - $nDatetime1) / 3600);
                break;
            case 2:
                /* 1分は60秒なので割る */
                $fTimeDiff = ceil(($nDatetime2 - $nDatetime1) / 60);
                break;
        }
        $this->m_nTimeDiff = (int) $fTimeDiff;
    }

    /**
     * 計算された値を返します
     * @return int 時間
     */
    public function GetTimeDiff()
    {
        return $this->m_nTimeDiff;
    }
}

?>
