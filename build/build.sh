#!/bin/bash
cd /opt/build

GIT_REPO=https://github.com/endlessm/cnijfilter-common
GIT_REVISION=0c5cfbf85cd6efc46d2dbb9da68ce8fac2815371

if [[ ! -f /opt/build/git.ok ]]; then
	echo "Download cnijfilter-common..."
	rm -rf /opt/build/cnijfilter-common
	git clone $GIT_REPO cnijfilter-common || exit 1
	cd /opt/build/cnijfilter-common
	git checkout $GIT_REVISION || exit 1
fi

cd /opt/build/cnijfilter-common

ACTUAL_REVISION=$(git rev-parse HEAD)

if [[ $ACTUAL_REVISION != $GIT_REVISION ]]; then
	rm -rf /opt/build/cnijfilter-common
	echo "Invalid revision: $ACTUAL_REVISION, expected: $GIT_REVISION ($GIT_REPO)"
	exit 1
fi

touch /opt/build/git.ok

if [[ ! -f /opt/build/build.ok ]]; then
	echo "Build cnijfilter-common..."
	fakeroot debian/rules binary || exit 1
	touch /opt/build/build.ok
fi

# php /opt/repack.php full
php /opt/repack.php light
