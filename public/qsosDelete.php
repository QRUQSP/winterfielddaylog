<?php
//
// Description
// -----------
// This method will delete an qso.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the qso is attached to.
// qso_id:            The ID of the qso to be removed.
//
// Returns
// -------
//
function qruqsp_winterfielddaylog_qsosDelete(&$ciniki) {
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
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'winterfielddaylog', 'private', 'checkAccess');
    $rc = qruqsp_winterfielddaylog_checkAccess($ciniki, $args['tnid'], 'ciniki.winterfielddaylog.qsosDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the qso
    //
    $strsql = "SELECT id, uuid "
        . "FROM qruqsp_winterfielddaylog_qsos "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.winterfielddaylog', 'qso');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $qsos = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.winterfielddaylog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the qso
    //
    foreach($qsos as $qso) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.winterfielddaylog.qso', $qso['id'], $qso['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.winterfielddaylog');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.winterfielddaylog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', 'winterfielddaylog');

    return array('stat'=>'ok');
}
?>
