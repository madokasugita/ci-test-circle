#!/bin/bash

set -eu

echo "********************"
echo "* start $0"
echo "********************"
echo "* repository :$CIRCLE_PROJECT_REPONAME"
echo "* branch     :$CIRCLE_BRANCH"
echo "* github url :$CIRCLE_COMPARE_URL"
echo "********************"

LIST=`git diff --name-only origin/develop | grep -e '.php$'`

echo "********************"
echo "* condition diff"
echo "********************"
if [ -z "$LIST" ]; then
    echo "********************"
    echo "* PHP file not changed."
    echo "********************"
    exit 0
fi

echo "********************"
echo "* copy PHPCompatibility"
echo "********************"
cp -R vendor/wimg/php-compatibility vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/PHPCompatibility

set +e
echo "********************"
echo "* exec check"
echo "********************"
git diff --name-only origin/develop \
    | grep -e '.php$' \
    | xargs vendor/bin/phpcs -n --standard=rules/phpcs_rules.xml --report=checkstyle --report-file=phpcs_result.xml

git diff --name-only origin/develop \
    | grep -e '.php$' \
    | xargs -I{} vendor/bin/phpmd {} xml rules/phpmd_rules.xml>> phpmd_result.xml

set -e

echo "********************"
echo "* save outputs"
echo "********************"
LINT_RESULT_DIR="$CIRCLE_ARTIFACTS/lint"

mkdir "$LINT_RESULT_DIR"
cp -v "phpcs_result.xml" "$LINT_RESULT_DIR/"
cp -v "phpmd_result.xml" "$LINT_RESULT_DIR/"

if [ -n "${CI_PULL_REQUEST}" ]; then
    echo "********************"
    echo "* PHP CodeSniffer"
    echo "********************"
    cat phpcs_result.xml \
        | bundle exec checkstyle_filter-git diff origin/develop \
        | bundle exec saddler report \
        --require saddler/reporter/github \
        --reporter Saddler::Reporter::Github::PullRequestReviewComment
    
    cat phpcs_result.xml
    
    echo "********************"
    echo "* PHP Mess Detector"
    echo "********************"
    set +e
    git diff --name-only origin/develop \
        | grep -e '.php$' \
        | xargs -I{} vendor/bin/phpmd {} xml rules/phpmd_rules.xml \
        | bundle exec pmd_translate_checkstyle_format translate \
        | bundle exec checkstyle_filter-git diff origin/develop \
        | bundle exec saddler report \
        --require saddler/reporter/github \
        --reporter Saddler::Reporter::Github::PullRequestReviewComment
    set -e
    
    cat phpmd_result.xml
    
    echo "********************"
    echo "* Github Alert"
    echo "********************"
    PCS_RESULT=`cat phpcs_result.xml \
    | bundle exec checkstyle_filter-git diff origin/develop \
    | grep -o "<error [^<]*/>"`
    
    PMD_RESULT=`git diff --name-only origin/develop \
    | grep -e '.php$' \
    | xargs -I{} vendor/bin/phpmd {} xml rules/phpmd_rules.xml \
    | bundle exec checkstyle_filter-git diff origin/develop \
    | grep -o "<error [^<]*/>"`
    
    echo "*****PHPCS*****"
    echo "$PCS_RESULT"
    echo "*****PHPMD*****"
    echo "$PMD_RESULT"
    
    if [ -n "$PCS_RESULT" -o -n "$PMD_RESULT" ]; then
        exit 1
    fi
fi

echo "********************"
echo "* end   $0"
echo "********************"
