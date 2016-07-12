MSYS SSH Binary Extrator
------------------------

Extracts MSYS SSH binaries (ssh.exe and relevant DLLs) from [Git Portable](https://git-scm.com/download/win) and automatically pushes them to [ssh-win32](https://github.com/cubiclesoft/ssh-win32) and [ssh-win64](https://github.com/cubiclesoft/ssh-win32).  MIT or LGPL.

Git Portable is, in my opinion, currently the best source of pre-compiled MSYS binaries.  We've got the built-in trust that the source repo is extremely unlikely to ever do horrible things (e.g. deploy malware), it is updated regularly because it is Git itself, and their Git/MSYS based system functions properly on Windows in a Portable format.  MSYS is normally NOT portable which is NOT awesome.  Git Portable makes MSYS awesome.  However, if you just want a native-ish ssh.exe, downloading ~30MB and extracting ~128MB of executables isn't ideal and then figuring out the DLL dependency mess is another nuisance/hassle.  This project solves those problems by completely automating the entire process:  Whenever Git Portable updates, the ssh-win32 and ssh-win64 repos will update within 24 hours (unless something breaks upstream).

Only ssh.exe is being included at the moment.  There are alternatives for the other MSYS SSH related tools that are, in my opinion, better.

License
-------

This extraction code is MIT or LGPL, your choice.  Although, I'm not exactly sure what this code is good for that you would want to reuse it.  It's simply here so you can look at the extraction code and verify that it isn't doing anything untoward.

Verifying Binary Integrity
--------------------------

You can always do a raw binary comparison between the EXE and DLLs in the target repos with the original binaries that you can download yourself.  They should always perfectly match bit for bit, byte for byte.

About the DLL Dependencies
--------------------------

There are a number of included DLL dependencies that bloat out the target repositories a bit.  You can confirm that they are all necessary to start ssh.exe with [Dependency Walker](http://dependencywalker.com/).  The most bizarre entry in the list is the MSYS SQLite DLL.  It is referenced by the MSYS Kerberos DLL, which is referenced by the MSYS GSSAPI DLL, which is referenced by ssh.exe all with regular import DLL references.  So the ~800KB MSYS SQLite DLL is necessary to start ssh.exe even if none of the real code in that DLL will likely ever be called.

The DLL dependencies are calculated by repeatedly looking for matching strings in the executable and subsequently referenced DLLs.  That way, only referenced DLLs are included in the final repositories.  It actually takes a bit of time to do the calculations since the code just does an unimpressive string search and then reads in and concatenates each matching DLL instead of processing the PE file format.
