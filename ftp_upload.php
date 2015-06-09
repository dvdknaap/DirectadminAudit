/bin/sh
# CURL Backup Transfer
# Version 0.1a
# Copyright 2006, Sensson (www.sensson.net)
#
# This script makes it possible to transfer
# backups using your secondary uplink
# like eth1.

ETH=eth0
CURL=/usr/local/bin/curl

result=`$CURL --interface $ETH -T $ftp_local_file -u $ftp_username:$ftp_password ftp://$ftp_ip$ftp_path$ftp_remote_file 2>&1`

if grep -q -o -i "curl: (67) Access denied: 530.*$" <<< "$result"; then
          echo "FTP access denied. Please check your login details."
          exit 1
fi
if grep -q -o -i "curl: (6) Couldn't resolve host.*$" <<< "$result"; then
          echo "Host could not be resolved. Please check your host details."
          exit 1
fi
if grep -q -o -i "curl: (9) Uploaded unaligned file size.*$" <<< "$result"; then
          echo "File could not be uploaded. Please check your path."
          exit 1
fi
if grep -q -o -i "curl: Can't open.*$" <<< "$result"; then
          echo "Can't open $ftp_local_file"
          exit 1
fi
