# AI-PGAALS Android app

This Android app opens the hosted AI-PGAALS system at `https://smartlearn6.site`.
It needs an internet connection and uses the same hosted accounts and database as the website.

## Download an APK

After the Android APK workflow completes, open the repository's **Actions** tab, select
**Build Android APK**, open the newest successful run, and download the
`AI-PGAALS-Android-APK` artifact. Extract the ZIP and install `app-debug.apk` on the phone.
Android may ask you to allow installs from the browser or file manager used to open it.

The debug APK is suitable for direct testing and installation. A Play Store release should
use a separately configured release signing key; never commit that key to the repository.
