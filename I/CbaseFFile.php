<?php

/**
 * ファイル関係を扱うクラス
 * @package Cbase.Research.Lib
 */
class FFile
{
    /**
     * 指定ファイルを保存する(配列でのアップロードには非対応)
     * 拡張子なしのファイルはエラーになる
     * @param  array  $prmFiles    $_FILESを指定
     * @param  string $prmName     $_FILES内のnameを指定
     * @param  string $prmDir      保存先フォルダ
     * @param  string $prmFileName ファイル名（拡張子は自動でつく）
     * @param  array  $prmExt      許可する拡張子（配列で、array("doc","txt")などとする）
     * @param  int    $size        許可する上限サイズ
     * @return bool   成功すればtrue
     * @author Cbase akama
     */
    public function uploadFile ($prmFiles, $prmName, $prmDir, $prmFileName, $prmExt=array(), $size="")
    {
        $prmFile = $prmFiles;
        $fileTmp     = $prmFile[$prmName]['tmp_name'];
        $fileName    = $prmFile[$prmName]['name'];
        //$fileSize    = $prmFile[$prmName]['size'];
        //$fileSype    = $prmFile[$prmName]['type'];

            if ($size && $size <= $prmFile[$prmName]["size"]) {
                echo "サイズが上限を超えています。".$fileName;
                exit;
            }

        $kaku="";
        if (is_uploaded_file($fileTmp)) {
            $ext = FFile::getExtension($fileName);

            $extFlag = true;
            if (0 < count($prmExt)) {
                if(!in_array($ext, $prmExt)) $extFlag = false;
            }

            if ($extFlag) {
                $saveName = $prmFileName.".".$ext;
                move_uploaded_file($fileTmp, $prmDir.$saveName);

                return true;
            } else {
                $error="ファイルの種類に誤りがあります。";
                echo $error;
                exit;
            }
        }

        return false;
    }

    /**
     * 指定ファイルをチェックのうえ保存する(配列でのアップロードに対応)
     * 拡張子なしのファイルはエラーになる
     * @param  array  $prmFiles    $_FILESを指定
     * @param  string $prmName     $_FILES内のnameを指定
     * @param  string $prmDir      保存先フォルダ
     * @param  string $prmFileName ファイル名（拡張子は自動でつく）(ファイル名_1からの連番.拡張子となる)
     * @param  array  $prmExt      許可する拡張子（配列で、array("doc","txt")などとする）
     * @param  int    $size        許可する上限サイズ
     * @return int    アップロードした数
     * @author Cbase akama
     */
    public function uploadFileArray ($prmFiles, $prmName, $prmDir, $prmFileName, $prmExt=array(), $size="")
    {
        $prmFile = $prmFiles;
        $i = 0;
        foreach ($prmFile[$prmName]['name'] as $key => $val) {
            $fileTmp     = $prmFile[$prmName]['tmp_name'][$key];
            $fileName    = $prmFile[$prmName]['name'][$key];

            if ($size && $size <= $prmFile[$prmName]["size"][$key]) {
                echo "サイズが上限を超えています。".$fileName;
                exit;
            }

            $kaku="";
            if (is_uploaded_file($fileTmp)) {
                $ext = FFile::getExtension($fileName);

                $extFlag = true;
                if (0 < count($prmExt)) {
                    if(!in_array($ext, $prmExt)) $extFlag = false;
                }

                if ($extFlag) {
                    $saveName = $prmFileName."_".(++$i).".".$ext;
                    $uploadFile["tmp"] = $fileTmp;
                    $uploadFile["file"] = $prmDir.$saveName;
                    $uploadFiles[] = $uploadFile;
                } else {
                    $error="ファイルの種類に誤りがあります。".$fileName;
                    echo $error;
                    exit;
                }
            }
        }
        foreach ($uploadFiles as $val) {
                move_uploaded_file($val["tmp"], $val["file"]);
        }

        return count($uploadFiles);
    }

    public function downloadCsv($prmAryData, $prmFileName="", $prmContentsType="application/csv")
    {
        if (is_void($prmFileName)) {
            $prmFileName = date("Ymd_His").".csv";
        }
        $file = "";
        foreach ($prmAryData as $data) {
            $file .= implode(",", escapeCsv($data))."\r\n";
        }
        FFile::download(encodeDownloadOut($file), $prmFileName, $prmContentsType);
    }

    /**
     * サーバ上のファイルをダウンロード
     * 使用例：
     * FFile::downloadFile (...);
     * exit;←exit;を入れること
     * @param string $prmDir          フォルダ
     * @param string $prmFileName     ファイル名
     * @param string $prmContentsType 必要があればcontentsTypeを指定できる
     * @author Cbase akama
     */
    public function downloadFile($prmDir, $prmFileName, $prmContentsType="application/octet-stream")
    {
        $file = file_get_contents($prmDir.$prmFileName);
        if (is_false($file)) {
            echo "ファイル読込エラー";
            exit;
        }
        FFile::download($file, $prmFileName, $prmContentsType);
    }

    /**
     * データをダウンロード
     * 使用例：
     * FFile::download (...);
     * exit;←exit;を入れること
     * @param string $prmFile         ダウンロードするデータの中身
     * @param string $prmFileName     ダウンロードファイル名
     * @param string $prmContentsType 必要があればcontentsTypeを指定できる
     * @author Cbase akama
     */
    public function download($prmFile, $prmFileName,$prmContentsType="application/octet-stream")
    {
        mb_output_handler("none");
        header("Pragma: private");
        header("Cache-Control: private");
        header("Content-Type: ".$prmContentsType);
        header("Content-Disposition: attachment; filename=".encodeDownloadFilename($prmFileName));
        header("Content-Length: ".strlen($prmFile));
        echo $prmFile;
        exit;
    }

    /**
     * ファイル名から拡張子を取得
     * @param  string $prmFileName ファイル名
     * @return string 拡張子
     * @author Cbase akama
     */
    public function getExtension($prmFileName)
    {
        //.で区切って最後の文字を返す
        $ary = explode(".", $prmFileName);

        return array_pop($ary);
    }

    /**
     * 指定ディレクトリの指定名・指定拡張子に一致するファイル名を配列で返す
     * @param  string $prmDir  指定ディレクトリ
     * @param  string $prmFile 指定名
     * @param  string $prmExt  指定拡張子
     * @return array  一致したファイルの配列
     * @author Cbase akama
     */
    public function getAnyFile($prmDir, $prmFile="", $prmExt="")
    {
        $file = $prmFile? "/^".$prmFile."$/": "";
        $ext = $prmExt? "/^".$prmExt."$/": "";

        return FFile::getAnyFileRegex($prmDir, $file, $ext);
    }

    /**
     * 指定ディレクトリの指定名・指定拡張子に一致するファイル名を配列で返す
     * ファイル名・拡張子名に正規表現を使えるバージョン
     * @param  string $prmDir  指定ディレクトリ
     * @param  string $prmFile 指定名
     * @param  string $prmExt  指定拡張子
     * @return array  一致したファイルの配列
     * @author Cbase akama
     */
    public function getAnyFileRegex($prmDir, $prmFile="", $prmExt="")
    {
        //フォルダかどうか
        if( !is_dir( $prmDir ) ) return false;

        $result = array();    // 戻り値用の配列

        $handle = opendir( $prmDir );
        while ( false !== $file = readdir( $handle ) ) {
            // 自分自身と上位階層のディレクトリを除外
            if ($file != "." && $file != "..") {
                if ( !is_dir( $prmDir.$file ) ) {
                    $flag = true;
                    if ($prmFile || $prmExt) {
                        $ary = explode(".", $file);
                        if($prmFile && (!@preg_match($prmFile, $ary[0]))) $flag = false;
                        if($prmExt && (!@preg_match($prmExt, $ary[1]))) $flag = false;
                    }

                    // ファイルならばパスを格納
                    if($flag)
                        $result[ $file ] = $prmDir.$file;
                }
            }
        }
        closedir( $handle );
        uasort( $result, "strcmp" ); // uasort() でないと添え字が失われます

        return $result;
    }
}
