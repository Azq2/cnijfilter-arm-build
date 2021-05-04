#!/bin/bash
ACTION=$1

export LC_ALL=C

hostname localhost

echo "Update chroot..."
apt update
apt full-upgrade
apt install -y -f debhelper libcups2-dev libxml2-dev libtiff-dev automake autoconf autotools-dev \
	libtool libglib2.0-dev libgtk2.0-dev libpopt-dev libusb-1.0-0-dev libtool libtool-bin \
	gcc g++ gdb binutils sudo git php-cli patchelf rsync

[[ -d /opt/build ]] || mkdir /opt/build
chown nobody:nogroup /opt/build

cd /opt/build

if [[ $ACTION = "build" ]]
then
	sudo -u nobody /opt/build.sh
elif [[ $ACTION = "bash" ]]; then
	sudo -u nobody -s
elif [[ $ACTION = "root_bash" ]]; then
	sudo -s
elif [[ $ACTION = "clean" ]]; then
	rm -rf /opt/build
	echo "OK"
fi
