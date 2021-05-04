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
