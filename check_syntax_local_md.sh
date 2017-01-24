#!/bin/bash
# ローカル環境テスト用


echo "********************"
echo "* start $0"
echo "********************"
if [ "$1" = "" ]; then  
    LIST=`git diff --name-only | grep -e '.php$'`

    echo "********************"
    echo "* condition diff"
    echo "********************"
    if [ -z "$LIST" ]; then
        echo "********************"
        echo "* PHP file not changed."
        echo "********************"
        exit 0
    fi
    RANGE="git diff --name-only"
else
    RANGE="find $1/*"
fi

set -eu

DATESTR=`date +%Y%m%d%H%M`

set +e
echo "********************"
echo "* exec check"
echo "********************"
# $RANGE \
#     | grep -e '.php$' \
#     | xargs vendor/bin/phpcs -n --standard=rules/phpcs_rules.xml --report=full --report-file=phpcs_result_$DATESTR.log
#     
$RANGE \
    | grep -e '.php$' \
    | xargs -I{} vendor/bin/phpmd {} xml rules/phpmd_rules.xml --reportfile phpmd_result_$DATESTR.log
set -e

echo "********************"
echo "* save outputs"
echo "********************"
LOG_DIR="log"

if [ ! -e $LOG_DIR ]; then
    mkdir "$LOG_DIR"
fi
# mv "phpcs_result_$DATESTR.log" "$LOG_DIR/"
mv "phpmd_result_$DATESTR.log" "$LOG_DIR/"

# echo "********************"
# echo "* PHP CodeSniffer"
# echo "********************"
# cat $LOG_DIR/phpcs_result_$DATESTR.log

echo "********************"
echo "* PHP Mess Detector"
echo "********************"
cat $LOG_DIR/phpmd_result_$DATESTR.log
echo "******************************************************************"
# cat $LOG_DIR/phpmd_result_$DATESTR.log \
    # | pmd_translate_checkstyle_format translate --file=$LOG_DIR/phpmd_result_$DATESTR.log
    pmd_translate_checkstyle_format translate $LOG_DIR/phpmd_result_$DATESTR.log

echo "********************"
echo "* end   $0"
echo "********************"
