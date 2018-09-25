<?php
/*
Copyright (c) 2015-2018, Hj Ahmad Rasyid Hj Ismail "ahrasis" ahrasis@gmail.com
Internal Resync Tool For ISPConfig DNS Server.
BSD3 License. All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

*       Redistributions of source code must retain the above copyright notice,
        this list of conditions and the following disclaimer.
*       Redistributions in binary form must reproduce the above copyright notice,
        this list of conditions and the following disclaimer in the documentation
        and/or other materials provided with the distribution.
*       Neither the name of ISPConfig nor the names of its contributors
        may be used to endorse or promote products derived from this software without
        specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 'AS IS' AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/*******************************************************************
        
        INSTRUCTIONS:
        Download this and simply run in your terminal:
        wget https://raw.githubusercontent.com/ahrasis/ISPConfig-Tools/master/resync_dns.php
        php -q resync_dns.php your.domain.tld.
        Note the dot at the end of your targeted domain. If
        domain isn't supplied, all zones will be resynced.
        
********************************************************************/
        
/*      Get the required file for access and other app functions. */

require_once '/usr/local/ispconfig/interface/lib/config.inc.php';
require_once '/usr/local/ispconfig/interface/lib/app.inc.php';

/*      Get database access by using ispconfig default configuration so no
        user and its password are disclosed. Exit if its connection failed */

$ip_updater = mysqli_connect($conf['db_host'], $conf['db_user'], $conf['db_password'], $conf['db_database']);
if (mysqli_connect_errno()) {
        printf("\r\nConnection to ISPConfig database failed!\r\n", mysqli_connect_error());
        exit();
}

/*      Now do dns resync for whatever purpose that you may need it. */

$mdomain=$argv[1];
if(is_null($mdomain)) {
        $zones = $app->db->queryAllRecords("SELECT id,origin,serial FROM dns_soa WHERE active = 'Y'");
} else {
        $zones = $app->db->queryAllRecords("SELECT id,origin,serial FROM dns_soa WHERE origin = '$mdomain' AND active = 'Y'");
}
if(is_array($zones) && !empty($zones)) {
        foreach($zones as $zone) {
                $records = $app->db->queryAllRecords("SELECT id,serial FROM dns_rr WHERE zone = ".$zone['id']." AND active = 'Y'");
                if(is_array($records)) {
                        foreach($records as $rec) {
                                $new_serial = $app->validate_dns->increase_serial($rec["serial"]);
                                $app->db->datalogUpdate('dns_rr', "serial = '".$new_serial."'", 'id', $rec['id']);
                        }
                }
                $new_serial = $app->validate_dns->increase_serial($zone["serial"]);
                $app->db->datalogUpdate('dns_soa', "serial = '".$new_serial."'", 'id', $zone['id']);
        }
}

/*      Lastly, we close database connection. */

mysqli_close($ip_updater);

?>
