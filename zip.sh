#!/bin/bash
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
SRCDIR=$( cd "$( dirname "$SCRIPT_DIR/./" )" && pwd );
DSTDIR=$( cd "$( dirname "$SCRIPT_DIR/../" )" && pwd );

cd $SCRIPT_DIR;

zip -r ../bloecks.zip . -x "*node_modules*" -x "*.git*" -x "rsync*" -x "Gruntfile*" -x "package.json" -x ".*" -x "*assets_src*" -x "zip.sh"
