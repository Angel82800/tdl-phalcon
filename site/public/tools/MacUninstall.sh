#!/bin/sh

echo "Todyl Support Defender Removal Tool"

if [ "$1" = "-p" ]; then
    rm -rf /Users/Shared/Library
    rm -rf /Users/Shared/Todyl/Todyl\ Defender.ini
    exit
fi

username=`echo $USER`

killall TodylDefender > /dev/null 2>&1
killall "Todyl Defender" > /dev/null 2>&1

found=0
if [ -d "/Applications/Todyl Defender.app" ]; then
    found=1;
fi

if [ -d "/Applications/TodylDefender.app" ]; then
    found=1;
fi

# stop todylcore daemon
launchctl unload /Library/LaunchDaemons/com.todyl.todylCoreHelper.plist > /dev/null 2>&1
rm -rf /Library/LaunchDaemons/com.todyl.todylCoreHelper.plist > /dev/null 2>&1
rm -rf /Library/PrivilegedHelperTools/com.todyl.todylCoreHelper > /dev/null 2>&1
rm -rf /Applications/TodylDefender.app > /dev/null 2>&1
rm -rf "/Applications/Todyl Defender.app" > /dev/null 2>&1

# Remove all users launch
rm -rf /Library/LaunchAgents/com.todyl.Defender.plist > /dev/null 2>&1

if [ "$1" = "-f" ]; then
    # restore original DNS settings
    ./Resources/dns_util.sh set_orig_dns > /dev/null

    #Remove preferences to simulate fresh install
    rm -rf /Users/Shared/Library > /dev/null 2>&1
    rm -rf /Users/Shared/Todyl/Todyl\ Defender.ini

    pushd /Users/ > /dev/null 2>&1
    for f in * ; do
	rm -rf $f/Library/Preferences/com.todyl.Todyl\ Defender.plist > /dev/null 2>&1
	killall -u $f cfprefsd > /dev/null 2>&1
	# Delete any other local copies of TodylDefender.app in case they are there.
	rm -rf $f/Applications/TodylDefender.app > /dev/null 2>&1
    done
    popd > /dev/null 2>&1
fi

if [ "$found" = "1" ]; then
    echo "Successfully removed Todyl Defender"
fi
