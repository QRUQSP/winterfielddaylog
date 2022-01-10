<?php
//
// Description
// -----------
// This method searchs for a QSOs for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get QSO for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function qruqsp_winterfielddaylog_dupSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'callsign'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    $args['callsign'] = strtoupper($args['callsign']);

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'winterfielddaylog', 'private', 'checkAccess');
    $rc = qruqsp_winterfielddaylog_checkAccess($ciniki, $args['tnid'], 'qruqsp.winterfielddaylog.qsoSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
        . "qruqsp_winterfielddaylog_qsos.operator "
        . "FROM qruqsp_winterfielddaylog_qsos "
        . "WHERE qruqsp_winterfielddaylog_qsos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND YEAR(qso_dt) = 2022 "
        . "AND ("
            . "callsign LIKE '" . ciniki_core_dbQuote($ciniki, $args['callsign']) . "%' "
            . "OR callsign LIKE '% " . ciniki_core_dbQuote($ciniki, $args['callsign']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.winterfielddaylog', array(
        array('container'=>'duplicates', 'fname'=>'id', 
            'fields'=>array('id', 'qso_dt', 'qso_dt_display', 'callsign', 'class', 'section', 'band', 'mode', 'frequency', 'operator')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $duplicates = isset($rc['duplicates']) ? $rc['duplicates'] : array();

    return array('stat'=>'ok', 'duplicates'=>$duplicates);
}
?>
