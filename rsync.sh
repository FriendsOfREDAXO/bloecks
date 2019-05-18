#!/bin/bash
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
SRCDIR=$( cd "$( dirname "$SCRIPT_DIR/./" )" && pwd );
DSTDIR=$( cd "$( dirname "$SCRIPT_DIR/../" )" && pwd );

# rsync template folders
for src in $(find $SRCDIR -type d -iname "assets");do

    dst=$(echo $src | sed "s/\/assets$/\//");
    dst=$(echo $dst | sed "s/\/redaxo\/src\//\/assets\//");

    # create folders if neccessary
    if [ ! -d "$dst" ];then
        mkdir -p $dst;
    fi;

    # rsync folders
    rsync -a --exclude-from="$SCRIPT_DIR/rsync.exclude" $src/ $dst/
done;

exit;
