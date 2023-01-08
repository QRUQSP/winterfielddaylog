<?php
//
// Description
// -----------
// This method will return everything for the UI for Field Day Logger
//
// Cabrillo spec found at: http://wwrof.org/cabrillo/cabrillo-specification-v3/
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get QSO for.
//
// Returns
// -------
//
function qruqsp_winterfielddaylog_exportCabrillo($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'winterfielddaylog', 'private', 'checkAccess');
    $rc = qruqsp_winterfielddaylog_checkAccess($ciniki, $args['tnid'], 'qruqsp.winterfielddaylog.qsoList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    $rc = ciniki_core_dbDetailsQuery($ciniki, 'qruqsp_winterfielddaylog_settings', 'tnid', $args['tnid'], 'qruqsp.winterfielddaylog', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.winterfielddaylog.9', 'msg'=>'', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Get the list of qsos
    //
    $strsql = "SELECT qruqsp_winterfielddaylog_qsos.id, "
        . "qruqsp_winterfielddaylog_qsos.qso_dt, "
        . "DATE_FORMAT(qruqsp_winterfielddaylog_qsos.qso_dt, '%Y-%m-%d %H%i') AS qso_dt_display, "
        . "qruqsp_winterfielddaylog_qsos.callsign, "
        . "qruqsp_winterfielddaylog_qsos.class, "
        . "qruqsp_winterfielddaylog_qsos.section, "
        . "qruqsp_winterfielddaylog_qsos.band, "
        . "qruqsp_winterfielddaylog_qsos.mode, "
        . "qruqsp_winterfielddaylog_qsos.frequency, "
        . "qruqsp_winterfielddaylog_qsos.operator "
        . "FROM qruqsp_winterfielddaylog_qsos "
        . "WHERE qruqsp_winterfielddaylog_qsos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND YEAR(qso_dt) = 2023 "
        . "ORDER BY qso_dt ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.winterfielddaylog', array(
        array('container'=>'qsos', 'fname'=>'id', 
            'fields'=>array('id', 'qso_dt', 'qso_dt_display', 'callsign', 'class', 'section', 'band', 'mode', 'frequency', 'operator'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $qsos = isset($rc['qsos']) ? $rc['qsos'] : array();

    //
    // Process QSOs
    //
    $cabrillo_qsos = '';
    $bands = array();
    $modes = array();
    $operators = array();
    $qso_points = 0;
    $multipliers = array();
    $qso_index = array(); // For dup checking
    foreach($qsos as $qso) {
        if( !in_array($qso['band'], $bands) ) {
            $bands[] = $qso['band'];
        }
        if( !in_array($qso['mode'], $modes) ) {
            $modes[] = $qso['mode'];
        }
        $multiplier = $qso['band'] . '-' . $qso['mode'];
        if( !in_array($multiplier, $multipliers) ) {
            $multipliers[] = $multiplier;
        }
        $qso_idx = $qso['callsign'] . '-' . $qso['band'] . '-' . $qso['mode'];
        if( in_array($qso_idx, $qso_index) ) {
            // Dup, skip
            continue;
        } else {
            $qso_index[] = $qso_idx;
        }
        if( $qso['mode'] == 'CW' || $qso['mode'] == 'DIG' ) {
            $qso_points += 2;
        } else {
            $qso_points += 1;
        }
        $cabrillo_qsos .= "QSO: ";
        if( $qso['frequency'] != '' ) {
            $qso['frequency'] = preg_replace("/[^0-9]/", "", $qso['frequency']);
        } else {
            switch($qso['band']) {
                case 160: $qso['frequency'] = 1800; break;
                case 80: $qso['frequency'] = 3500; break;
                case 40: $qso['frequency'] = 7000; break;
                case 20: $qso['frequency'] = 14000; break;
                case 15: $qso['frequency'] = 21000; break;
                case 10: $qso['frequency'] = 28000; break;
                case 6: $qso['frequency'] = 50; break;
                case 2: $qso['frequency'] = 144; break;
                case 220: $qso['frequency'] = 222; break;
                case 440: $qso['frequency'] = 70; break;
            }
        }
        $cabrillo_qsos .= sprintf(" %5s", $qso['frequency']);

        if( $qso['mode'] == 'DIG' ) {
            $cabrillo_qsos .= " DG";
        } else {
            $cabrillo_qsos .= " " . $qso['mode'];
        }
        $cabrillo_qsos .= " " . $qso['qso_dt_display'];
        $cabrillo_qsos .= sprintf(" %-13s", (isset($settings['callsign']) ? $settings['callsign'] : ''));
        $cabrillo_qsos .= sprintf(" %-6s", (isset($settings['class']) ? $settings['class'] : ''));
        $cabrillo_qsos .= sprintf(" %-3s", (isset($settings['section']) ? $settings['section'] : ''));
        $cabrillo_qsos .= sprintf(" %-13s", $qso['callsign']);
        $cabrillo_qsos .= sprintf(" %-4s", $qso['class']);
        $cabrillo_qsos .= sprintf(" %-3s", $qso['section']);
        $cabrillo_qsos .= "\r\n";

        if( !in_array(strtoupper($qso['operator']), $operators) ) {
            $operators[] = strtoupper($qso['operator']);
        }
    }

    $cabrillo = '';
    $cabrillo .= "START-OF-LOG: 3.0\r\n";
    $cabrillo .= "CONTEST: WFD\r\n";
    $cabrillo .= "Created-By: QRUQSP.org WinterFieldDayLogger2023\r\n";
    $cabrillo .= "CLUB: " . (isset($settings['club']) ? $settings['club'] : '') . "\r\n";
    $cabrillo .= "LOCATION: " . (isset($settings['location']) ? $settings['location'] : '') . "\r\n";
    $cabrillo .= "ARRL-SECTION: " . (isset($settings['section']) ? $settings['section'] : '') . "\r\n";
    $cabrillo .= "CATEGORY: " . (isset($settings['class']) ? $settings['class'] : '') . "\r\n";

    
    $score = $qso_points;

    //
    // Band/Mode multipliers
    //
    if( count($multipliers) > 1 ) { 
        $score *= count($multipliers);
    }

    //
    // Power level multiplier
    //
    if( isset($settings['category-power']) && $settings['category-power'] == 'QRP' ) {
        $cabrillo .= "CATEGORY-POWER: QRP\r\n";
        $score = $score * 2;
//    } elseif( isset($settings['category-power']) && $settings['category-power'] == 'LOW' ) {
//        $cabrillo .= "CATEGORY-POWER: LOW\r\n";
//        $score = $score * 2;
    } else {
        $cabrillo .= "CATEGORY-POWER: " . (isset($settings['category-power']) ? $settings['category-power'] : '') . "\r\n";
        $score = $score;
    }

    // Soapbox
    $bonus = 0;
    if( isset($settings['soapbox-non-commercial-power']) && $settings['soapbox-non-commercial-power'] == 'yes' ) {
        $cabrillo .= "SOAPBOX: 500 points for not using commercial power\r\n";
        $bonus += 500;
    }
    if( isset($settings['soapbox-outdoors']) && $settings['soapbox-outdoors'] == 'yes' ) {
        $cabrillo .= "SOAPBOX: 500 points for setting up outdoors\r\n";
        $bonus += 500;
    }
    if( isset($settings['soapbox-away-from-home']) && $settings['soapbox-away-from-home'] == 'yes' ) {
        $cabrillo .= "SOAPBOX: 500 points for setting up away from home\r\n";
        $bonus += 500;
    }
    if( isset($settings['soapbox-setup-antenna']) && $settings['soapbox-setup-antenna'] == 'yes' ) {
        $cabrillo .= "SOAPBOX: 500 points for antenna setup\r\n";
        $bonus += 500;
    }
    if( isset($settings['soapbox-satellite-qso']) && $settings['soapbox-satellite-qso'] == 'yes' ) {
        $cabrillo .= "SOAPBOX: 500 points for Satellite QSO";
        $bonus += 500;
        if( isset($settings['soapbox-satellite-qso-with']) && $settings['soapbox-satellite-qso-with'] != '' ) {
            $cabrillo .= " (w/" . $settings['soapbox-satellite-qso-with'] . ")";
        }
        $cabrillo .= "\r\n";
    }
    if( isset($settings['soapbox-mobile']) && $settings['soapbox-mobile'] == 'yes' ) {
        $cabrillo .= "SOAPBOX: 250 points for mobile\r\n";
        $bonus += 250;
    }
    if( $bonus > 0 ) {
        $cabrillo .= "SOAPBOX: BONUS Total " . $bonus . "\r\n";
    }
    if( isset($settings['soapbox-freeform']) && $settings['soapbox-freeform'] != '' ) {
        $cabrillo .= "SOAPBOX: " . $settings['soapbox-freeform'] . "\r\n";
    }
    $score += $bonus;

    $cabrillo .= "CALLSIGN: " . (isset($settings['callsign']) ? $settings['callsign'] : '') . "\r\n";
    $cabrillo .= "CATEGORY-OPERATOR: " . (isset($settings['category-operator']) ? $settings['category-operator'] : '') . "\r\n";
//    $cabrillo .= "CATEGORY-ASSISTED: " . (isset($settings['category-assisted']) ? $settings['category-assisted'] : '') . "\r\n";
/*    if( count($bands) > 1 ) {
        $cabrillo .= "CATEGORY-BAND: ALL\r\n";
    } elseif( count($bands) == 1 ) {
        $cabrillo .= "CATEGORY-BAND: " . $bands[0] . "\r\n";
    } else {
        $cabrillo .= "CATEGORY-BAND: \r\n";
    }
    if( count($modes) > 1 ) {
        $cabrillo .= "CATEGORY-MODE: MIXED\r\n";
    } elseif( count($modes) == 1 ) {
        $cabrillo .= "CATEGORY-MODE: " . $modes[0] . "\r\n";
    } else {
        $cabrillo .= "CATEGORY-MODE: \r\n";
    } */
//    $cabrillo .= "CATEGORY-STATION: " . (isset($settings['category-station']) ? $settings['category-station'] : 'FIXED') . "\r\n";
//    $cabrillo .= "CATEGORY-TRANSMITTER: " . (isset($settings['category-transmitter']) ? $settings['category-transmitter'] : '') . "\r\n";
    $cabrillo .= "CLAIMED-SCORE: " . $score . "\r\n";
    if( isset($settings['category-operator']) && $settings['category-operator'] == 'MULTI-OP' && count($operators) > 0 ) {
        $cabrillo .= "OPERATORS: " . implode(',', $operators) . "\r\n";
    }
    
    $cabrillo .= "NAME: " . (isset($settings['name']) ? $settings['name'] : '') . "\r\n";
    $cabrillo .= "ADDRESS: " . (isset($settings['address']) ? $settings['address'] : '') . "\r\n";
    $cabrillo .= "ADDRESS-CITY: " . (isset($settings['city']) ? $settings['city'] : '') . "\r\n";
    $cabrillo .= "ADDRESS-STATE: " . (isset($settings['state']) ? $settings['state'] : '') . "\r\n";
    $cabrillo .= "ADDRESS-POSTALCODE: " . (isset($settings['postal']) ? $settings['postal'] : '') . "\r\n";
    $cabrillo .= "ADDRESS-COUNTRY: " . (isset($settings['country']) ? $settings['country'] : '') . "\r\n";
    $cabrillo .= "EMAIL: " . (isset($settings['email']) ? $settings['email'] : '') . "\r\n";


    $cabrillo .= $cabrillo_qsos;
    $cabrillo .= "END-OF-LOG:\r\n";

    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT', true, 200);
    header("Content-type: text/plain");
    header('Content-Disposition: attachment; filename="winterfieldday2023.log"');

    print $cabrillo;
    
    return array('stat'=>'exit');
}
?>
