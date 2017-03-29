<?php

/**
 *
 * 2007/07/30 ver1.01 エラーがあった場合returnしていなかったバグを修正
 * 2007/10/24 ver2.00 処理をクラスに分散
 */

define('DIR_ROOT', '../');

require_once (DIR_ROOT.'crm_define.php');

$dir_user = DIR_IMG_USER;
$dir_img = DIR_IMG;

echo <<<__HTML__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>PREVIEW</title>
<meta http-equiv="content-script-type" content="text/javascript">
<meta http-equiv="content-style-type" content="text/css">
<link href="{$dir_user}360_userpage-min.css" type="text/css" rel="stylesheet">
<link href="{$dir_user}360_enq-min.css" type="text/css" rel="stylesheet">
<link href="{$dir_user}360_enqmatrix-min.css" type="text/css" rel="stylesheet">
<script src="{$dir_img}jquery-1.7.1.min.js" type="text/javascript"></script>
<script src="{$dir_img}360_cellselecter.js" type="text/javascript"></script>
<style>
body{
    background:#FFF;
}
</style>
</head>
<body>
<div id="maincontainer">
</div>
</body>
</html>
__HTML__
;
