<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_winterfielddaylog_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['qso'] = array(
        'name' => 'QSO',
        'sync' => 'yes',
        'o_name' => 'qso',
        'o_container' => 'qsos',
        'table' => 'qruqsp_winterfielddaylog_qsos',
        'fields' => array(
            'qso_dt' => array('name'=>'UTC Date Time of QSO'),
            'callsign' => array('name'=>'Call Sign'),
            'class' => array('name'=>'Class'),
            'section' => array('name'=>'Section'),
            'band' => array('name'=>'Band'),
            'mode' => array('name'=>'Mode'),
            'frequency' => array('name'=>'Frequency', 'default'=>''),
            'operator' => array('name'=>'Operator', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'notes' => array('name'=>'Notes', 'default'=>''),
            ),
        'history_table' => 'qruqsp_winterfielddaylog_history',
        );
     $objects['setting'] = array(
         'type'=>'settings',
         'name'=>'Field Day Settings',
         'table'=>'qruqsp_winterfielddaylog_settings',
         'history_table'=>'qruqsp_winterfielddaylog_history',
         );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
