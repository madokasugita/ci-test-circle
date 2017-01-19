#!/bin/bash

echo "START SHELL: $0"
LIST=`git diff --name-only origin/develop | grep -e '.php$'`

if [ -z "$LIST" ]; then
    echo "PHP file not changed."
    exit 0
fi


if [ -n "$CI_PULL_REQUEST" ]; then
    cp -R vendor/wimg/php-compatibility vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/PHPCompatibility
    echo "START PROCESS: phpcs"
    git diff --name-only origin/develop \
        | grep -e '.php$' \
        # | xargs vendor/bin/phpcs -n --standard=rules/phpcs_rules.xml --report=checkstyle 
        | xargs vendor/bin/phpcs -n --standard=rules/phpcs_rules.xml --report=checkstyle \
        | bundle exec checkstyle_filter-git diff origin/develop \
        | bundle exec saddler report 
        --require saddler/reporter/github \
        --reporter Saddler::Reporter::Github::PullRequestReviewComment
    echo "END PROCESS: phpcs"
    
    echo "START PROCESS: phpmd"
    git diff --name-only origin/develop \
        | grep -e '.php$' \
        | xargs -I{} vendor/bin/phpmd {} text rules/phpmd_rules.xml \
        | uniq -c -d 
        # | uniq -c -d \
        # | bundle exec checkstyle_filter-git diff origin/develop \
        # | bundle exec saddler report \
        # --require saddler/reporter/github \
        # --reporter Saddler::Reporter::Github::PullRequestReviewComment
    echo "END PROCESS: phpmd"
fi
echo "END SHELL: $0"
