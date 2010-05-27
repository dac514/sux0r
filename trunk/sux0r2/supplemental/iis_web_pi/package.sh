#!/bin/bash

mkdir /tmp/suxpack
cd /tmp/suxpack
svn export https://sux0r.svn.sourceforge.net/svnroot/sux0r/trunk/sux0r2 sux0r
mv sux0r/supplemental/iis_web_pi/install.sql .
mv sux0r/supplemental/iis_web_pi/manifest.xml .
mv sux0r/supplemental/iis_web_pi/parameters.xml .
mv sux0r/supplemental/iis_web_pi/sux0r/web.config sux0r
mv sux0r/supplemental/iis_web_pi/sux0r/supplemental/sql/db-mysql-iis-extra.sql sux0r/supplemental/sql
rm -rf sux0r/supplemental/iis_web_pi
