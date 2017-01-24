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
echo "* install gems"
echo "********************"
gem install --no-document checkstyle_filter-git saddler saddler-reporter-github pmd_translate_checkstyle_format

set +e
echo "********************"
echo "* exec check"
echo "********************"    
git diff --name-only origin/develop \
    | grep -e '.php$' \
    | xargs -I{} vendor/bin/phpmd {} xml rules/phpmd_rules.xml --reportfile phpmd_result.xml
set -e

echo "********************"
echo "* save outputs"
echo "********************"
LINT_RESULT_DIR="$CIRCLE_ARTIFACTS/lint"

mkdir "$LINT_RESULT_DIR"
cp -v "phpmd_result.xml" "$LINT_RESULT_DIR/"

echo "********************"
echo "* select reporter"
echo "********************"
if [ -z "${CI_PULL_REQUEST}" ]; then
    # when not pull request
    REPORTER=Saddler::Reporter::Github::CommitReviewComment
else
    REPORTER=Saddler::Reporter::Github::PullRequestReviewComment
fi

echo "********************"
echo "* PHP Mess Detector"
echo "********************"
cat phpmd_result.xml \
    | pmd_translate_checkstyle_format translate  --cpd-translate\
    | checkstyle_filter-git diff origin/develop \
    | saddler report --require saddler/reporter/github --reporter $REPORTER
    
cat phpmd_result.xml

echo "**************************************************************************************"

cat phpmd_result.xml \
    | pmd_translate_checkstyle_format translate  --cpd-translate>> chk_phpmd_result.xml

cat chk_phpmd_result.xml

echo "********************"
echo "* end   $0"
echo "********************"
