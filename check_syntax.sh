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
echo " condition diff"
echo "********************"
if [ -z "$LIST" ]; then
    echo "********************"
    echo " PHP file not changed."
    echo "********************"
    exit 0
fi

echo "********************"
echo "* install gems"
echo "********************"
gem install --no-document checkstyle_filter-git saddler saddler-reporter-github pmd_translate_checkstyle_format

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
    # | xargs -I{} vendor/bin/phpcs {} -n --standard=rules/phpcs_rules.xml --report=checkstyle --report-file=phpcs_result.xml
    
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

echo "********************"
echo "* select reporter"
echo "********************"
# if [ -n "$CI_PULL_REQUEST" ]; then
#     # when not pull request
#     REPORTER=Saddler::Reporter::Github::CommitReviewComment
# else
#     REPORTER=Saddler::Reporter::Github::PullRequestReviewComment
# fi
if [ -z "${CI_PULL_REQUEST}" ]; then
    # when not pull request
    REPORTER=Saddler::Reporter::Github::CommitReviewComment
else
    REPORTER=Saddler::Reporter::Github::PullRequestReviewComment
fi

echo "********************"
echo "* PHP CodeSniffer"
echo "********************"
cat phpcs_result.xml \
    | checkstyle_filter-git diff origin/develop \
    | saddler report --require saddler/reporter/github --reporter $REPORTER
    
cat phpcs_result.xml

echo "********************"
echo "* PHP Mess Detector"
echo "********************"
cat phpmd_result.xml \
    | pmd_translate_checkstyle_format translate \
    | checkstyle_filter-git diff origin/develop \
    | saddler report --require saddler/reporter/github --reporter $REPORTER
    
cat phpmd_result.xml

echo "********************"
echo "* end   $0"
echo "********************"
