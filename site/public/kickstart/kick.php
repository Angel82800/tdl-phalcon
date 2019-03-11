<?php include_once('secure.php');?>
###Todyl Shield V4 Salt Kickstart
#linux ks=https://www.todyl.com/kickstart/kick.php loglevel=debug console=tty0 console=ttyS0,115200 text ENV=(PRD|PRDREKICK|DEV)
#(Add/modify for static) ip=96.56.155.61 netmask=255.255.255.248 dns=8.8.8.8 gateway=96.56.155.57
###
%pre
#!/bin/bash

#Parameters to variables
if grep -i "ENV=[a-z0-9]" /proc/cmdline
then
   ENV=`cat /proc/cmdline | sed 's/.*ENV=\([^ ]*\).*/\1/'`
   echo $ENV > /tmp/env
fi 

#Preserve the custom configuration
if [ $ENV == "PRDREKICK" ] ; then
  #Grab disk
  DISK=`fdisk -l | grep "16.0 GB" | head -1 | cut -d ":" -f1 | cut -f 2 -d " "`
  NAME=`fdisk -l | grep "16.0 GB" | head -1 | cut -d ":" -f1 | cut -d "/" -f3`
  #Salt config
  mkdir /mnt/${NAME}2
  mount ${DISK}2 /mnt/${NAME}2
  cp -R /mnt/${NAME}2/etc/salt/ /tmp/salt
  #PKI config
  mkdir /mnt/${NAME}5
  mount ${DISK}5 /mnt/${NAME}5
  cp -R /mnt/${NAME}5/etc/pki/squid/ /tmp/pki
  #Hostname
  cat /mnt/${NAME}2/etc/hostname | cut -d '.' -f1 > /tmp/hostn
  #Unmount
  umount /mnt/${NAME}2
  umount /mnt/${NAME}5
fi

#Environment config
if [ $ENV == "PRD" ] || [ $ENV == "PRDREKICK" ] ; then
    #Disk name config
    DISK=`fdisk -l | grep "16.0 GB" | head -1 | cut -d ":" -f1 | cut -d "/" -f3`
	echo 'rootpw --iscrypted $6$SALT$xpJJMTjGxwr10dItaTHBg1dLX8lZPvm7jfb.v/LMGz9VaRKljWQ2WzHw4xtiBgbmf9k7C0W0/UVXTdYGOEDI.1' > /tmp/rootpass-include
	#Paritioning config - lax space for prod
	echo "ignoredisk --only-use=$DISK" > /tmp/part-include
	echo "clearpart --all --drives=$DISK" >> /tmp/part-include
	echo "bootloader --location=mbr --timeout=5 --driveorder=$DISK --append=\"console=tty0 console=ttyS0,115200 text\" --iscrypted --password=\"grub.pbkdf2.sha512.10000.740D45F6457A2F5545E02361DF8889FC99FD49B64E886EB70D0CEAA3AF07B0CD69BA404564E6A1F27C1D03FFCB3F710AED7E468488C2C87300D680EEF52C7EC0.C4E7563C490640520E60E267F5044BF89382D0A0FF6BFDF98B1719154D916D56A290D0A0FADEA15DE1464A74F95C255CD23CAE8BA949619771C1A965675895FA\"" >> /tmp/part-include
	echo "part / --fstype=ext4 --size=11500 --asprimary --ondisk=$DISK" >> /tmp/part-include
	echo "part swap --size=500 --ondisk=$DISK" >> /tmp/part-include
	echo "part /boot --fstype=ext4 --size=300 --ondisk=$DISK" >> /tmp/part-include
	echo "part /tmp --fstype=ext4 --size=100 --ondisk=$DISK" >> /tmp/part-include
	echo "part /var --fstype=ext4 --size=1500 --ondisk=$DISK" >> /tmp/part-include
	echo "part /opt --fstype=ext4 --size=600 --ondisk=$DISK" >> /tmp/part-include
else
    echo 'rootpw --iscrypted $6$SALT$7KWFlTmNzlM6sXkiadrjnsNpT4Xotvk6A/fDBNLpMxP5Hk4uDNYzRBQ2i.VdwMDPcjjs1O5w8NS597tqYjO65/' > /tmp/rootpass-include
	DISK="sda"
	#Paritioning config - give discipline to space usage
	echo "ignoredisk --only-use=$DISK" > /tmp/part-include
	echo "clearpart --all --drives=$DISK" >> /tmp/part-include
	echo "bootloader --location=mbr --timeout=5 --driveorder=$DISK --append=\"console=tty0 console=ttyS0,115200 text\" --iscrypted --password=\"grub.pbkdf2.sha512.10000.740D45F6457A2F5545E02361DF8889FC99FD49B64E886EB70D0CEAA3AF07B0CD69BA404564E6A1F27C1D03FFCB3F710AED7E468488C2C87300D680EEF52C7EC0.C4E7563C490640520E60E267F5044BF89382D0A0FF6BFDF98B1719154D916D56A290D0A0FADEA15DE1464A74F95C255CD23CAE8BA949619771C1A965675895FA\"" >> /tmp/part-include
	echo "part / --fstype=ext4 --size=3000 --asprimary --ondisk=$DISK" >> /tmp/part-include
	echo "part swap --size=500 --ondisk=$DISK" >> /tmp/part-include
	echo "part /boot --fstype=ext4 --size=300 --ondisk=$DISK" >> /tmp/part-include
	echo "part /tmp --fstype=ext4 --size=500 --ondisk=$DISK" >> /tmp/part-include
	echo "part /var --fstype=ext4 --size=1500 --ondisk=$DISK" >> /tmp/part-include
	echo "part /opt --fstype=ext4 --size=600 --ondisk=$DISK" >> /tmp/part-include
	echo "part /future_reserved --fstype=ext4 --size=8100 --ondisk=$DISK" >> /tmp/part-include
fi
%end

#Base config setup
install
text
reboot
url --url http://mirror.centos.org/centos/7/os/x86_64

# System authorization information
auth --enableshadow --passalgo=sha512

# Keyboard layouts
keyboard --vckeymap=us --xlayouts='us'

# System language
lang en_US.UTF-8

# Network information
network  --bootproto=dhcp --device=enp0s20f0 --onboot=yes --noipv6
network  --bootproto=dhcp --device=enp0s20f1 --onboot=off --noipv6
network  --bootproto=dhcp --device=enp0s20f2 --onboot=off --noipv6
network  --bootproto=dhcp --device=enp0s20f3 --onboot=off --noipv6

# Root password -- "python -c 'import crypt; print crypt.crypt("PASS", "$6$SALT$")'"
%include /tmp/rootpass-include

# Security
firewall --disabled
selinux --permissive

# Do not configure the X Window System
skipx

# System timezone
timezone America/New_York --isUtc

# Bootloader & Partitioning
%include /tmp/part-include

# Package install
%packages --ignoremissing
@core
wget
%end

%post --nochroot --log=/mnt/sysimage/root/post.log
#Variable carry
cp /tmp/env /mnt/sysimage/tmp/env
cp -R /tmp/salt /mnt/sysimage/tmp/salt
cp -R /tmp/pki  /mnt/sysimage/tmp/pki
cp /tmp/hostn /mnt/sysimage/tmp/hostn
%end

# Salt Config
%post --log=/root/post2.log

#Variable set
ENV=`cat /tmp/env`

#1 Set Hostname
dmistatus=`dmidecode -s system-serial-number`
if [ "$dmistatus" == "To be filled by O.E.M." ] ; then
    hostn=`date +%s | sha256sum | cut -c1-9`
else
    hostn=`dmidecode -s system-serial-number`
fi
hostnamectl set-hostname $hostn.shield.todyl.com
echo \"DHCP_HOSTNAME=$hostn.shield.todyl.com\" > /etc/sysconfig/network
hostn=`date +%s | sha256sum | cut -c1-9`
if [ $ENV == "PRDREKICK" ] ; then hostn=`cat /tmp/hostn`; fi #Pull the old hostname
hostnamectl set-hostname $hostn.shield.todyl.com
echo "DHCP_HOSTNAME=$hostn.shield.todyl.com" > /etc/sysconfig/network
echo "$hostn.shield.todyl.com" > /etc/hostname

#2 Install salt-minion
yum -y install https://repo.saltstack.com/yum/redhat/salt-repo-latest-2.el7.noarch.rpm 
yum clean expire-cache
yum -y install salt-minion

#3 Pull minion and grains files, hardset the minion ID
if [ $ENV == "PRD" ] ; then
  wget https://www.todyl.com/kickstart/minion.php?ENV=$ENV -O /etc/salt/minion
  sed -i s/"id: null"/"id: $hostn.shield.todyl.com"/g /etc/salt/minion
  wget https://www.todyl.com/kickstart/grains.php?ENV=$ENV -O /etc/salt/grains
elif [ $ENV == "PRDREKICK" ] ; then
  cp -R /tmp/salt/* /etc/salt/
  mkdir -p /opt/etc/pki/squid/
  cp -R /tmp/pki/* /opt/etc/pki/squid/
else
  #Dev must have Thrust running
  wget http://10.50.10.30/kickstart/minion.php?ENV=$ENV -O /etc/salt/minion
  wget http://10.50.10.30/kickstart/grains.php?ENV=$ENV -O /etc/salt/grains
fi

#4 Start salt-minion service and setup networking
chkconfig salt-minion on
salt-call state.apply shield/networking
sed -i s/"DNS1=127.0.0.1"/"DNS1=8.8.8.8"/g /etc/sysconfig/network-scripts/ifcfg-enp0s20f0 #This temporarily changes the DNS until bind is installed, overwritten during highstate

#5 Rc.local to reset the hostname & config for initial highstate
if [ $ENV == "PRD" ] ; then
  wget https://www.todyl.com/kickstart/rc.php?ENV=$ENV -O /etc/rc.d/rc.local
  chmod +x /etc/rc.d/rc.local
elif [ $ENV == "PRDREKICK" ] ; then
  wget https://www.todyl.com/kickstart/rc.php?key=<?php if (isset($_GET["key"])) echo $_GET["key"] ?>&ENV=$ENV -O /etc/rc.d/rc.local
  chmod +x /etc/rc.d/rc.local
fi

#6 Clean up salt on dev 
if [ $ENV == "DEV" ] ; then
	yum -y remove salt-minion
	rm -f /etc/salt
fi
%end

%post --nochroot
#Cleanup
ENV=`cat /tmp/env`
rm /tmp/env
yum -y update
%end