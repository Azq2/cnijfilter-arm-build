#!/bin/bash
BIN=$0
ROOT=$(dirname $0)
ACTION=$1

CHROOT="$ROOT/chroot"

DEBIAN_VERSION=buster
DEBIAN_REPO=http://deb.debian.org/debian/

function copy_to_chroot {
	mkdir "$CHROOT/opt" -p
	cp "$ROOT/build/$1" "$CHROOT/opt/$1"
	
	if [[ $2 != "" ]]; then
		chmod "$2" "$CHROOT/opt/$1"
	fi
}

function prepare_chroot {
	if [[ ! -f "$CHROOT/usr/bin/bash" ]]; then
		echo "Downloading debian $DEBIAN_VERSION..."
		
		# Create dir for chroot
		[[ -d "$CHROOT" ]] || mkdir -p "$CHROOT"
		
		# Download debian
		debootstrap --variant=minbase --arch=i386 "$DEBIAN_VERSION" "$CHROOT" "$DEBIAN_REPO"
	fi
	
	copy_to_chroot setup.sh +x
	copy_to_chroot build.sh +x
	copy_to_chroot repack.php +x
	copy_to_chroot get-libs.php +x
	copy_to_chroot remove-dups.php +x
}

function go_chroot {
	unshare -u chroot "$CHROOT" $@
}

if [[ $ACTION = "build" ]]
then
	prepare_chroot
	go_chroot /opt/setup.sh build
elif [[ $ACTION = "bash" ]]; then
	prepare_chroot
	go_chroot /opt/setup.sh bash
elif [[ $ACTION = "root_bash" ]]; then
	prepare_chroot
	go_chroot /opt/setup.sh root_bash
elif [[ $ACTION = "clean" ]]; then
	prepare_chroot
	go_chroot /opt/setup.sh clean
elif [[ $ACTION = "distclean" ]]; then
	rm -rf "$CHROOT"
	echo "OK"
else
	echo "Build:"
	echo "  $BIN build"
	echo ""
	echo "Bash console (user):"
	echo "  $BIN bash"
	echo ""
	echo "Bash console (root):"
	echo "  $BIN bash"
	echo ""
	echo "Clean:"
	echo "  $BIN clean"
	echo ""
	echo "Clean all:"
	echo "  $BIN distclean"
fi
