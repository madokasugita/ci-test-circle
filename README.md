参考資料
http://qiita.com/yuji0602/items/28b0c2363bae8fce055a

# P環境構築
PHPMDとPHPCSを使ってPHPの静的解析を行う

## PHPMDとは
phpmdはPHP Mess Ditectorの略で、PHPコードの潜在的なバグになりそうな箇所や実装上の問題を検出してくれるツールである。例えば未使用の変数の指摘、多数のpublicメソッドのある巨大クラスの検出、一文字変数等もこのツールで検出可能だ。  
引用元:http://www.ryuzee.com/contents/blog/3479

## PHPCSとは
PHP_CodeSnifferは2つのPHPスクリプトのセットです。 定義されたコーディング標準の違反を検出するPHP、JavaScript、CSSファイルをトークン化するphpcsスクリプト、コーディング標準違反を自動的に修正する2番目のphpcbfスクリプト PHP_CodeSnifferは、コードをきれいにして一貫性を保つための重要な開発ツールです。(翻訳)  
引用元:https://github.com/squizlabs/PHP_CodeSniffer

## インストール方法
### composerのインストールの確認
```ターミナル
$ composer -v
 ```
### composerのインストール
```ターミナル
$ cd ~/src
$ curl -sS https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
 ```
 
### composerのインストールの確認
```ターミナル
$ composer -v
   ______
  / ____/___  ____ ___  ____  ____  ________  _____
 / /   / __ \/ __ `__ \/ __ \/ __ \/ ___/ _ \/ ___/
/ /___/ /_/ / / / / / / /_/ / /_/ (__  )  __/ /
\____/\____/_/ /_/ /_/ .___/\____/____/\___/_/
                    /_/
Composer version 1.3.1 2017-01-07 18:08:51
 ```
 
### PHPMDとPHPCSのインストール
```ターミナル
$ git clone https://github.com/madokasugita/ci-test-circle.git
$ cd ci-test-circle
$ composer install
$ cp -R vendor/wimg/php-compatibility vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/PHPCompatibility

###ローカルでのコードのチェック
 ```ターミナル
 [vagrant@cbase-local ci-test-circle]$ ./check_syntax_local.sh /work/www/src/test/test
 ********************
 * start ./check_syntax_local.sh
 ********************
 ********************
 * exec check
 ********************
 Time: 1.59 secs; Memory: 7.5Mb
 
 ********************
 * save outputs
 ********************
 ********************
 * PHP CodeSniffer
 ********************
 
 FILE: /work/www/src/test/test/test_Connect.php
 ----------------------------------------------------------------------
 FOUND 1 ERROR AFFECTING 1 LINE
 ----------------------------------------------------------------------
  2 | ERROR | [x] Space after opening parenthesis of function call
    |       |     prohibited
 ----------------------------------------------------------------------
 PHPCBF CAN FIX THE 1 MARKED SNIFF VIOLATIONS AUTOMATICALLY
 ----------------------------------------------------------------------
 
 
 FILE: /work/www/src/test/test/test_Connect_vagrant.php
 ----------------------------------------------------------------------
 FOUND 16 ERRORS AFFECTING 11 LINES
 ----------------------------------------------------------------------
  14 | ERROR | [x] Expected 1 space after TRY keyword; 0 found
  19 | ERROR | [x] Expected 1 space after closing parenthesis; found 0
  21 | ERROR | [x] Expected 1 space after closing brace; 0 found
  21 | ERROR | [x] Expected 1 space after ELSE keyword; 0 found
  38 | ERROR | [x] Expected 1 space after closing parenthesis; found 0
  39 | ERROR | [x] Line indented incorrectly; expected at least 8
     |       |     spaces, found 7
  40 | ERROR | [x] Expected 1 space after closing brace; 0 found
  40 | ERROR | [x] Expected 1 space after ELSE keyword; 0 found
  41 | ERROR | [x] Line indented incorrectly; expected at least 8
     |       |     spaces, found 7
  43 | ERROR | [x] Expected 1 space after closing parenthesis; found 0
  45 | ERROR | [x] Expected 1 space after closing brace; 0 found
  45 | ERROR | [x] Expected 1 space after ELSE keyword; 0 found
  50 | ERROR | [x] Expected 1 space after WHILE keyword; 0 found
  50 | ERROR | [x] Expected 1 space after closing parenthesis; found 0
  54 | ERROR | [x] Expected 1 space after closing brace; 0 found
  54 | ERROR | [x] Expected 1 space after closing parenthesis; found 0
 ----------------------------------------------------------------------
 PHPCBF CAN FIX THE 16 MARKED SNIFF VIOLATIONS AUTOMATICALLY
 ----------------------------------------------------------------------
 
 
 FILE: /work/www/src/test/test/test.php
 ----------------------------------------------------------------------
 FOUND 5 ERRORS AFFECTING 5 LINES
 ----------------------------------------------------------------------
  17 | ERROR | [x] Line indented incorrectly; expected 0 spaces, found
     |       |     4
  20 | ERROR | [x] No space found after comma in function call
  21 | ERROR | [x] Line indented incorrectly; expected 4 spaces, found
     |       |     8
  23 | ERROR | [x] Line indented incorrectly; expected 4 spaces, found
     |       |     8
  24 | ERROR | [x] Line indented incorrectly; expected 0 spaces, found
     |       |     4
 ----------------------------------------------------------------------
 PHPCBF CAN FIX THE 5 MARKED SNIFF VIOLATIONS AUTOMATICALLY
 ----------------------------------------------------------------------

 ```
 "ci-test-circle/check_syntax_local.sh"は実行するとコード内容を解析してくれます。
 第一引数は、チェックしたいディレクトりを与えてあげてください。
 結果は"ci-test-circle/log/"に格納されます。
 