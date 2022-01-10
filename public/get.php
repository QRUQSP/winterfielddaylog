<?php
//
// Description
// -----------
// This method will return everything for the UI for Field Day Logger
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
function qruqsp_winterfielddaylog_get($ciniki) {
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
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.winterfielddaylog.17', 'msg'=>'', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load the sections
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'winterfielddaylog', 'private', 'sectionsLoad');
    $rc = qruqsp_winterfielddaylog_sectionsLoad($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.winterfielddaylog.20', 'msg'=>'', 'err'=>$rc['err']));
    }
    $sections = $rc['sections'];
    $areas = $rc['areas'];
    
    //
    // Get the list of qsos
    //
    $strsql = "SELECT qruqsp_winterfielddaylog_qsos.id, "
        . "qruqsp_winterfielddaylog_qsos.qso_dt, "
        . "DATE_FORMAT(qruqsp_winterfielddaylog_qsos.qso_dt, '%b %d %H:%i') AS qso_dt_display, "
        . "qruqsp_winterfielddaylog_qsos.callsign, "
        . "qruqsp_winterfielddaylog_qsos.class, "
        . "qruqsp_winterfielddaylog_qsos.section, "
        . "qruqsp_winterfielddaylog_qsos.band, "
        . "qruqsp_winterfielddaylog_qsos.mode, "
        . "qruqsp_winterfielddaylog_qsos.frequency, "
        . "qruqsp_winterfielddaylog_qsos.flags, "
        . "qruqsp_winterfielddaylog_qsos.operator "
        . "FROM qruqsp_winterfielddaylog_qsos "
        . "WHERE qruqsp_winterfielddaylog_qsos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND YEAR(qso_dt) = 2022 "
        . "ORDER BY qso_dt DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.winterfielddaylog', array(
        array('container'=>'qsos', 'fname'=>'id', 
            'fields'=>array('id', 'qso_dt', 'qso_dt_display', 'callsign', 'class', 'section', 'band', 'mode', 'frequency', 'flags', 'operator'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $qsos = isset($rc['qsos']) ? $rc['qsos'] : array();

    $rsp = array('stat'=>'ok', 'qsos'=>$qsos, 'areas'=>$areas, 'sections'=>$sections, 'settings'=>$settings);

    $bands = array();
    $modes = array(
        'CW' => 0,
        'PH' => 0,
        'DIG' => 0,
        );
    $rsp['band'] = '';
    $rsp['mode'] = '';
    $rsp['frequency'] = '';
    $rsp['flags'] = 0;
    $rsp['gota_stats'] = array(
        
        );
    $rsp['mode_band_stats'] = array();
    foreach(['CW'=>'CW', 'PH'=>'PH', 'DIG'=>'DIG', 'totals'=>'Totals'] as $k => $v) {
        $rsp['mode_band_stats'][$k] = array(
            'label' => $v,
            '160' => array('label' => '160 M', 'num_qsos' => 0),
            '80' => array('label' => '80 M', 'num_qsos' => 0),
            '40' => array('label' => '40 M', 'num_qsos' => 0),
            '20' => array('label' => '20 M', 'num_qsos' => 0),
            '15' => array('label' => '15 M', 'num_qsos' => 0),
            '10' => array('label' => '10 M', 'num_qsos' => 0),
            '6' => array('label' => '6 M', 'num_qsos' => 0),
            '2' => array('label' => '2 M', 'num_qsos' => 0),
            '220' => array('label' => '1.25 M', 'num_qsos' => 0),
            '440' => array('label' => '70 Cm', 'num_qsos' => 0),
//            'satellite' => array('label' => 'SAT', 'num_qsos' => 0),
//            'gota' => array('label' => 'GOTA', 'num_qsos' => 0),
            'other' => array('label' => 'OTHER', 'num_qsos' => 0),
            'totals' => array('label' => 'Totals', 'num_qsos' => 0),
            );
    }
    $rsp['section_band_stats'] = array();
    
    foreach($sections as $label => $section) {
        $rsp['section_band_stats'][$label] = array(
            'label' => $label,
            '160' => array('label' => '160 M', 'num_qsos' => 0),
            '80' => array('label' => '80 M', 'num_qsos' => 0),
            '40' => array('label' => '40 M', 'num_qsos' => 0),
            '20' => array('label' => '20 M', 'num_qsos' => 0),
            '15' => array('label' => '15 M', 'num_qsos' => 0),
            '10' => array('label' => '10 M', 'num_qsos' => 0),
            '6' => array('label' => '6 M', 'num_qsos' => 0),
            '2' => array('label' => '2 M', 'num_qsos' => 0),
            '220' => array('label' => '1.25 M', 'num_qsos' => 0),
            '440' => array('label' => '70 Cm', 'num_qsos' => 0),
//            'satellite' => array('label' => 'SAT', 'num_qsos' => 0),
//            'gota' => array('label' => 'GOTA', 'num_qsos' => 0),
            'other' => array('label' => 'OTHER', 'num_qsos' => 0),
            'totals' => array('label' => 'Totals', 'num_qsos' => 0),
            );
    }
    $rsp['section_band_stats']['totals'] = array(
        'label' => 'Totals',
        '160' => array('label' => '160 M', 'num_qsos' => 0),
        '80' => array('label' => '80 M', 'num_qsos' => 0),
        '40' => array('label' => '40 M', 'num_qsos' => 0),
        '20' => array('label' => '20 M', 'num_qsos' => 0),
        '15' => array('label' => '15 M', 'num_qsos' => 0),
        '10' => array('label' => '10 M', 'num_qsos' => 0),
        '6' => array('label' => '6 M', 'num_qsos' => 0),
        '2' => array('label' => '2 M', 'num_qsos' => 0),
        '220' => array('label' => '1.25 M', 'num_qsos' => 0),
        '440' => array('label' => '70 Cm', 'num_qsos' => 0),
//        'satellite' => array('label' => 'SAT', 'num_qsos' => 0),
//        'gota' => array('label' => 'GOTA', 'num_qsos' => 0),
        'other' => array('label' => 'OTHER', 'num_qsos' => 0),
        'totals' => array('label' => 'Totals', 'num_qsos' => 0),
        );

    //
    // There are 85 bits needed for the map, store as 3 32 bit intergers to make sure works on 32 bit systems
    //
    $map_bits = array(
        0 => 0,
        1 => 0,
        2 => 0,
        );

    $rsp['gota_stats'] = array();
    $rsp['gota_stats']['totals'] = array(
        'label' => 'Totals',
        'CW' => array('label' => 'CW', 'num_qsos' => 0),
        'DIG' => array('label' => 'DIG', 'num_qsos' => 0),
        'PH' => array('label' => 'PH', 'num_qsos' => 0),
        'totals' => array('label'=>'Totals', 'num_qsos' => 0),
        );
    //
    // Get stats
    //
    $multipliers = array();
    $qso_index = array();
    foreach($qsos as $qso) {
        // Dup Check
        $qso_idx = $qso['callsign'] . '-' . $qso['band'] . '-' . $qso['mode'];
        if( in_array($qso_idx, $qso_index) ) {
            // Dup, skip
            continue;
        } else {
            $qso_index[] = $qso_idx;
        }
        // Multipliers
        $multiplier = $qso['band'] . '-' . $qso['mode'];
        if( !in_array($multiplier, $multipliers) ) {
            $multipliers[] = $multiplier;
        }

        if( $rsp['band'] == '' ) {
            $rsp['band'] = $qso['band'];
            $rsp['mode'] = $qso['mode'];
            $rsp['frequency'] = $qso['frequency'];
        }
        $section = $qso['section'];
        if( isset($rsp['sections'][$section]) ) {
            $rsp['sections'][$section]['num_qsos']++;
            if( isset($rsp['sections'][$section]['bit']) && $rsp['sections'][$section]['bit'] > 0 ) {
                $bit = $rsp['sections'][$section]['bit'];
                $mul = intdiv($bit-1, 32);
                $bit = $bit - (32 * $mul);
                $map_bits[$mul] |= pow(2, $bit-1);
            }
        }
        if( !isset($bands[$qso['band']]) ) {
            $bands[$qso['band']] = 1;
        } else {
            $bands[$qso['band']]++;
        }
        if( !isset($modes[$qso['mode']]) ) {
            $modes[$qso['mode']] = 1;
        } else {
            $modes[$qso['mode']]++;
        }
        if( isset($rsp['mode_band_stats'][$qso['mode']][$qso['band']]) ) {
            $rsp['mode_band_stats'][$qso['mode']][$qso['band']]['num_qsos']++;
            $rsp['mode_band_stats'][$qso['mode']]['totals']['num_qsos']++;
            $rsp['mode_band_stats']['totals'][$qso['band']]['num_qsos']++;
            $rsp['mode_band_stats']['totals']['totals']['num_qsos']++;
        } else {
            // Should never happen, checked when entered
            error_log('unknown mode: ' . $qso['mode'] . ' band: ' . $qso['band']);
            error_log(print_r($qso,true));
        }
        if( isset($rsp['section_band_stats'][$qso['section']][$qso['band']]) ) {
            $rsp['section_band_stats'][$qso['section']][$qso['band']]['num_qsos']++;
            $rsp['section_band_stats'][$qso['section']]['totals']['num_qsos']++;
            $rsp['section_band_stats']['totals'][$qso['band']]['num_qsos']++;
            $rsp['section_band_stats']['totals']['totals']['num_qsos']++;
        } else {
            // Should never happen, checked when entered
            error_log('unknown section: ' . $qso['section'] . ' band: ' . $qso['band']);
            error_log(print_r($qso,true));
        }
        if( ($qso['flags']&0x01) == 0x01 ) {
            if( $qso['operator'] == '' && isset($settings['callsign']) ) {
                $qso['operator'] = $settings['callsign'];
            }
            if( !isset($rsp['gota_stats'][$qso['operator']]) ) {
                $rsp['gota_stats'][$qso['operator']] = array(
                    'label' => $qso['operator'],
                    'CW' => array('label' => 'CW', 'num_qsos' => 0),
                    'DIG' => array('label' => 'DIG', 'num_qsos' => 0),
                    'PH' => array('label' => 'PH', 'num_qsos' => 0),
                    'totals' => array('label'=>'Totals', 'num_qsos' => 0),
                    );
            }
       
            if( isset($rsp['gota_stats'][$qso['operator']][$qso['mode']]['num_qsos']) ) {
                $rsp['gota_stats'][$qso['operator']][$qso['mode']]['num_qsos'] += 1;
                $rsp['gota_stats'][$qso['operator']]['totals']['num_qsos'] += 1;
                $rsp['gota_stats']['totals'][$qso['mode']]['num_qsos'] += 1;
            }
        }
    }
    
//    $rsp['map_url'] = '/qruqsp-mods/winterfielddaylog/ui/maps/' 
//        . sprintf("%08X", $map_bits[2]) . '_' . sprintf("%08X", $map_bits[1]) . '_' . sprintf("%08X", $map_bits[0]) . '.png';

    //
    // Calculate score
    //
    // Band/Mode multipliers
    $qso_points = ($modes['CW']*2) + ($modes['DIG']*2) + $modes['PH'];
    $score = $qso_points;
    if( count($multipliers) > 1 ) {
        $score = $score * count($multipliers);
    }

    //
    // Power multipliers
    //
    $power_multiplier = 1;
    if( isset($settings['category-power']) && $settings['category-power'] == 'QRP' ) {
        $power_multiplier = 4;
    } elseif( isset($settings['category-power']) && $settings['category-power'] == 'LOW' ) {
        $power_multiplier = 2;
    }
    $score *= $power_multiplier;

    $bonus = 0;
    if( isset($settings['soapbox-non-commercial-power']) && $settings['soapbox-non-commercial-power'] == 'yes' ) {
        $bonus += 1500;
    }
    if( isset($settings['soapbox-outdoors']) && $settings['soapbox-outdoors'] == 'yes' ) {
        $bonus += 1500;
    }
    if( isset($settings['soapbox-away-from-home']) && $settings['soapbox-away-from-home'] == 'yes' ) {
        $bonus += 1500;
    }
    if( isset($settings['soapbox-satellite-qso']) && $settings['soapbox-satellite-qso'] == 'yes' ) {
        $bonus += 1500;
    }
    $score += $bonus;
    $rsp['scores'] = array(
        array('label' => 'Phone Contacts', 'value' => $modes['PH']),
        array('label' => 'CW Contacts', 'value' => $modes['CW']),
        array('label' => 'Digital Contacts', 'value' => $modes['DIG']),
        array('label' => 'Contact Points', 'value' => $qso_points),
        array('label' => 'Band/Mode Multipliers', 'value' => count($multipliers) > 1 ? count($multipliers) : 1),
        array('label' => 'Power Multiplier', 'value' => $power_multiplier),
        array('label' => 'Bonus', 'value' => $bonus),
        array('label' => 'Total Score', 'value' => $score),
        );

    $rsp['mydetails'] = array(
        array('label' => 'Call Sign', 'value' => (isset($settings['callsign']) ? $settings['callsign'] : '')),
        array('label' => 'Class', 'value' => (isset($settings['class']) ? $settings['class'] : '')),
        array('label' => 'Section', 'value' => (isset($settings['section']) ? $settings['section'] : '')),
        );

    //
    // Setup areas vertical
    //
    $vareas = array();
    $row = array();
    foreach($areas as $aid => $area) {
        $row[] = array('label' => $area['name']);
    }
    for($i = 0; $i < 13; $i++) {    
        $row = array();
        foreach($areas as $aid => $area) {
            if( isset($area['sections'][$i]['label']) ) {
                $row[] = array('label' => $area['sections'][$i]['label']);
            } else {
                $row[] = array('label' => '');
            }
        }
        $vareas[] = $row;
    }
    $rsp['vareas'] = $vareas;

    //
    // Setup map_sections
    //
    $map_sections = array();
    foreach($rsp['sections'] as $k => $section) {
        if( $section['num_qsos'] > 0 ) {
            $map_sections[] = $k;
        }
    }
    sort($map_sections);
    $rsp['map_sections'] = implode(',', $map_sections);

    //
    // Get the recent qsos
    //
    $rsp['recent'] = array_slice($qsos, 0, 25);
    $rsp['totals'] = array(
        'gota_stats' => array_shift($rsp['gota_stats']),
        'mode_band_stats' => array_pop($rsp['mode_band_stats']),
        'section_band_stats' => array_pop($rsp['section_band_stats']),
        );

    return $rsp;
}
?>
