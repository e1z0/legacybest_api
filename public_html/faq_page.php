<h1>Cannot run on MacOS</h1>
On the newest MacOS Versions all downloaded apps from unknown sources are blacklisted, to whitelist such app use command in terminal:
xattr -dr com.apple.quarantine Release/Mac/legacybest.app
