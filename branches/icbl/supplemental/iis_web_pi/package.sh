#!/bin/bash

set -o verbose
mkdir /tmp/suxpack
cd /tmp/suxpack
svn export https://sux0r.svn.sourceforge.net/svnroot/sux0r/trunk/sux0r2 sux0r
mv sux0r/supplemental/iis_web_pi/install.sql .
mv sux0r/supplemental/iis_web_pi/manifest.xml .
mv sux0r/supplemental/iis_web_pi/parameters.xml .
mv sux0r/supplemental/iis_web_pi/sux0r/web.config sux0r
mv sux0r/supplemental/iis_web_pi/sux0r/supplemental/sql/db-mysql-iis-extra.sql sux0r/supplemental/sql
mv sux0r/supplemental/iis_web_pi/sux0r/documentation/README-IIS.txt sux0r/documentation
mv sux0r/supplemental/iis_web_pi/sux0r/templates/sux0r/home/home.tpl sux0r/templates/sux0r/home
rm -rf sux0r/supplemental/iis_web_pi
zip -r sux0r.zip .
mv sux0r.zip ~/Desktop
rm -rf /tmp/suxpack