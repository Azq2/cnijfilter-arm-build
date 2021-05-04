# Why? What for?
Canon provides source code for cups driver, but with some proprietary binary libs.

This libs available only for x86. No way for direct compiling cnijfilter for ARM (and other architectures). :(

# How it works
- Build https://github.com/endlessm/cnijfilter-common for x86
- Copy all needed x86 libs using recursive `ldd`. And copy it to /usr/lib/bjlib/x86/
- Patch all executables: set interpreter to `/usr/lib/bjlib/x86/ld-linux.so.2` and rpath to `/usr/lib/bjlib/x86`
- Install these patched packages to ARM system
- Install `qemu-user` and `qemu-user-binfmt` (or `qemu-user-static`)
- Now it works. Maybe :)

# How to use
On any x86 machine:
```bash
# Install dependencies
sudo apt install debootstrap git util-linux

# Get build system
git clone https://github.com/Azq2/cnijfilter-arm-build

# Start building in chroot
cd cnijfilter-arm-build
sudo ./build.sh build

# Get .deb packages
ls -lah ./result/full
ls -lah ./result/light
```

On ARM machine:
```bash
# Install dependencies
sudo apt install qemu-user qemu-user-binfmt # or sudo apt install qemu-user-static

# Install common for all printers package
sudo dpkg -i cnijfilter-common.deb

# Install printer-specific package
# Choose right package for your printer! e400series only for reference!
sudo dpkg -i cnijfilter-e400series.deb
```

# Full vs Light
| Item              | Full | Light |
|-------------------|------|-------|
| PPD files         | +    | +     |
| CUPS filters      | +    | +     |
| CUPS backends     | +    | -     |
| lgmon             | +    | -     |
| canon-maintenance | +    | -     |
| cngpij            | +    | -     |
| cngpijmnt         | +    | -     |
| cnijlgmon2        | +    | -     |
| cnijnetprn        | +    | -     |
| cnijnpr           | +    | -     |
| docs              | +    | -     |

In most cases full package is not necessary. For CUPS working we need only CUPS filters.

Backends for usb or net already presents in CUPS.

# Security
CUPS filters don't need any specific permissions.

Minimal apparmor rules:
```
#include <tunables/global>

/usr/bin/bjfilter* {
	#include <abstractions/base>
	@{PROC}/sys/vm/mmap_min_addr r,
}

/usr/bin/cif[a-z]*[0-9d]* {
	#include <abstractions/base>
	@{PROC}/sys/vm/mmap_min_addr r,
}

/usr/lib/cups/filter/{cmdtocanonij,pstocanonbj,pstocanonij} {
	#include <abstractions/base>
	@{PROC}/sys/vm/mmap_min_addr r,
}
```

Create file **/etc/apparmor.d/cnijfilter-filters** with this contents and restart apparmor:
```
sudo systemctl reload apparmor
sudo aa-enforce cnijfilter-filters
```

**Note:** this minimal file full coverage all executables in **light** package. For **full** package you need write additional rules by yourself.

# Why not use debian multiarch (like dpkg --add-architecture i386)?
I dont know how make it work. I think, i386 libs on armhf is broken. All my attepts ends by apt error:
```
The following packages have unmet dependencies:
 libcups2:i386 : Depends: libavahi-client3:i386 (>= 0.6.16) but it is not going to be installed
                 Depends: libavahi-common3:i386 (>= 0.6.16) but it is not going to be installed
                 Depends: libc6:i386 (>= 2.28) but it is not going to be installed
                 Depends: libgnutls30:i386 (>= 3.6.9) but it is not going to be installed
                 Depends: libgssapi-krb5-2:i386 (>= 1.17) but it is not going to be installed
                 Depends: zlib1g:i386 (>= 1:1.2.0) but it is not going to be installed
```

# Where i can download already builded .deb's?
Nowhere. Build it by yourself. :)
