<?php
require_once (DIR_LIB.'CbaseFunction.php');
require_once 'Crypt/Blowfish_NoMCrypt.php';

$GLOBAL_blowfish = new Crypt_Blowfish(SYSTEM_RANDOM_STRING);
/**
 * 暗号化する
 */
function encrypt($queryString)
{
    global $GLOBAL_blowfish;

    return url_base64_encode($GLOBAL_blowfish->encrypt($queryString));
}
/**
 * 複合化する
 */
function decrypt($queryString)
{
    global $GLOBAL_blowfish;

    return $GLOBAL_blowfish->decrypt(url_base64_decode($queryString));
}
