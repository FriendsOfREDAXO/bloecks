#!/bin/bash
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
SRCDIR=$( cd "$( dirname "$SCRIPT_DIR/./" )" && pwd );
DSTDIR=$( cd "$( dirname "$SCRIPT_DIR/../" )" && pwd );
NAME=${SCRIPT_DIR##*/}

# build for production
grunt --production

cd $SCRIPT_DIR/..;

if [ -f "$NAME.zip" ];then
    rm -f "$NAME.zip";
fi;

# generate release zip file
zip -r "$NAME.zip" "./$NAME/" \
    -x "*node_modules*" \
       "*.git*" \
       "rsync*" \
       "*Gruntfile*" \
       "*package.json" \
       "*assets_src*" \
       "*.sh" \
       "*.exclude" \
       "*.DS*" \
       "*._*" \
       "*.css.map" \
       "*.js.map"
