# Why? What for?
Canon provides source code for cups driver, but with some proprietary binary libs.

This libs available only for x86. No way for direct compiling cnijfilter for ARM (and other architectures). :(

# How it works
- Build https://github.com/endlessm/cnijfilter-common for x86
- Copy all needed x86 libs using recursive `ldd`. And copy it to /usr/lib/bjlib/x86/
- Patch all executables: set interpreter to `/usr/lib/bjlib/x86/ld-linux.so.2` and rpath to `/usr/lib/bjlib/x86`
- Install these patched packages to ARM system
- Install `qemu-user` and `qemu-user-binfmt`
- Now it works. Maybe :)

# How to use
On any x86 machine:
```bash
# Install dendencies
sudo apt install debootstrap

# Start building in chroot
sudo ./build.sh build

# Get .deb packages
ls -lah ./result/full
ls -lah ./result/light
```

On ARM machine:
```bash
# Install dendencies
sudo apt install qemu-user qemu-user-binfmt

# Install common for all printers package
sudo dpkg -i cnijfilter-common.deb

# Install printer-specific package
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

# Where i can download already builded .deb's?
Nowhere. Build it by yourself. :)
