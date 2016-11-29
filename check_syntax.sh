#!/bin/bash

echo "Start"
LIST=`git diff --name-only origin/develop | grep -e '.php$'`

if [ -z "$LIST" ]; then
    echo "PHP file not changed."
    exit 0
fi


if [ -n "$CI_PULL_REQUEST" ]; then
    WARN=`git diff --name-only origin/develop \
        | grep -e '.php$' \
        | xargs vendor/bin/phpcs -n --standard=PSR2 --report=checkstyle \
        | bundle exec checkstyle_filter-git diff origin/develop`

    if [ -n "$WARN" ]; then
	git diff --name-only origin/develop \
        | grep -e '.php$' \
	| xargs vendor/bin/phpcs -n --standard=PSR2 --report=checkstyle \
	| bundle exec checkstyle_filter-git diff origin/develop \
        | bundle exec saddler report \
        --require saddler/reporter/github \
        --reporter Saddler::Reporter::Github::PullRequestReviewComment
	exit 1
    fi
fi
