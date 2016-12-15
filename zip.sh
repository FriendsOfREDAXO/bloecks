#!/bin/bash
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
SRCDIR=$( cd "$( dirname "$SCRIPT_DIR/./" )" && pwd );
DSTDIR=$( cd "$( dirname "$SCRIPT_DIR/../" )" && pwd );

cd $SCRIPT_DIR/..;

zip -r bloecks.zip ./bloecks/ -x "*node_modules*" "*.git*" "rsync*" "*Gruntfile*" "*package.json" ".*" "*assets_src*" "*.sh" "*.exclude"
