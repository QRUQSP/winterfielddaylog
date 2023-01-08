<?php
//
// Description
// -----------
// Check current qsos for matching callsign, band and mode.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_winterfielddaylog_checkDupe(&$ciniki, $tnid, $args) {

    //
    // Check for existing qso
    //
    $strsql = "SELECT qruqsp_winterfielddaylog_qsos.id, "
        . "qruqsp_winterfielddaylog_qsos.qso_dt, "
        . "qruqsp_winterfielddaylog_qsos.callsign, "
        . "qruqsp_winterfielddaylog_qsos.class, "
        . "qruqsp_winterfielddaylog_qsos.section, "
        . "qruqsp_winterfielddaylog_qsos.band, "
        . "qruqsp_winterfielddaylog_qsos.mode, "
        . "qruqsp_winterfielddaylog_qsos.frequency, "
        . "qruqsp_winterfielddaylog_qsos.operator, "
        . "qruqsp_winterfielddaylog_qsos.notes "
        . "FROM qruqsp_winterfielddaylog_qsos "
        . "WHERE qruqsp_winterfielddaylog_qsos.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND YEAR(qruqsp_winterfielddaylog_qsos.qso_dt) = 2023 "
        . "AND qruqsp_winterfielddaylog_qsos.callsign = '" . ciniki_core_dbQuote($ciniki, $args['callsign']) . "' "
        . "AND qruqsp_winterfielddaylog_qsos.band = '" . ciniki_core_dbQuote($ciniki, $args['band']) . "' "
        . "AND qruqsp_winterfielddaylog_qsos.mode = '" . ciniki_core_dbQuote($ciniki, $args['mode']) . "' "
        . "";
    if( isset($args['id']) && $args['id'] != '' ) {
        $strsql .= "AND qruqsp_winterfielddaylog_qsos.id <> '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.winterfielddaylog', 'qso');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.winterfielddaylog.30', 'msg'=>'Unable to load contact', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        return array('stat'=>'ok', 'dupe'=>'yes');
    }

    return array('stat'=>'ok', 'dupe'=>'no');
}
?>
