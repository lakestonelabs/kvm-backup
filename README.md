# kvm-backup
A texted-based KVM VM backup utility that utilizes QEMU's APIs.  Supports both full and incremental (not snapshots) backups.

This is currently in pre-alpha stage so do expect for things to work, at all.  Heavy development is currently ongoing and many things will change.

## Why did I create this?

Yes there are many programs out there that can backup KVM/Qemu images but most rely on virsh's snapshot tools which don't follow the usual "snapshot" scenario and thus create very large files.  kvm-backup utilizes the latest Qemu "dirty-block" technology to keep track of changed blocks in VMs and is therefore able to create very efficient full and incremental backups, fast!

## Roadblocks

Unfortunately the very latest branches of Qemu don't support the polling of the current backup job's progress.  This makes backup tools like this not very effective.  There is bleeding-edge support for this that the Qemu developers have demo'd but it's not currently available.

As there are many ways to skin a cat I am actively developing a workaround.  Once this roadblock can  be solved vigorous developement will resume.
