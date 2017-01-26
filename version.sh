#!/bin/bash
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
SRCDIR=$( cd "$( dirname "$SCRIPT_DIR/./" )" && pwd );
DSTDIR=$( cd "$( dirname "$SCRIPT_DIR/../" )" && pwd );
NAME=${SCRIPT_DIR##*/}
VERSION=$1

if [ "$VERSION" != "" ];then

    # find all .php files and replace '@version' with the current Version
    # grep -rl "@version" . | xargs sed '' -e "s/\@version(^:+)(.*)$/@version\1$VERSION/g";
    find . -type f -name "*.php" -exec sed -i '' -E "s/\@version([^:]+)?:([ ]+)?(.*)$/@version\1:\2$VERSION/g" {} \;

    # find all .yml files and replace 'version: '([0-9\.\-a-z]+)' with the current version
    find . -type f -name "*.yml" -exec sed -i '' -E "s/\version([^:]+)?:([^']+)?'([^']+)'$/version\1:\2'$VERSION'/g" {} \;
    # find . -type f -name "*.yml" -exec sed -i "s/version(^'+)'([0-9\.\-a-z]+)'$/version\1'$VERSION'/g" {};
else
    echo 'Please define a version no: ./version.sh "1.0.0.-rc1"';
fi;
