<?php
/**
 *
 * @package   Commons_Booking_Admin
 * @author    Florian Egermann <florian@macht-medien.de>
 * @license   GPL-2.0+
 * @link      http://www.wielebenwir.de
 * @copyright 2015 wielebenwir
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package Commons_Booking_Codes
 * @author  Florian Egermann <email@example.com>
 */
class Commons_Booking_Codes {

  public $table_name;

  public $codes_array;
  public $csv;

  public $item_id; // always set
  public $date_start;
  public $date_end;

  public $date;
  public $daterange_start;
  public $timeframe_id;

/**
 * Constructor.
 *
 * @param $item_id 
 * @param $date_start
 * @param $date_end
 *
 */
  public function __construct( $item_id ) {
 
    // get Codes from Settings page
    $settings = new Commons_Booking_Admin_Settings;

    global $wpdb;
    $this->table_name = $wpdb->prefix . 'cb_codes';

    $this->prefix = "commons-booking";

    $this->daterange_start = date('Y-m-d', strtotime( '-30 days' )); // currentdate - 30 days

    $this->item_id = $item_id;

    $csv = $settings->get( 'codes', 'codes_pool' ); // codes as csv 
    $this->codes_array = $this->split_csv( $csv );  // codes as array


}

public function set_timeframe ( $timeframe_id, $date_start, $date_end ) {

    $this->timeframe_id = $timeframe_id;
    $this->date_start = $date_start;
    $this->date_end = $date_end;
  }

/**
 * Get settings from backend.
 */
  public function split_csv( $csv ) {

    $splitted = explode(",", $csv);
    $splitted = preg_grep('#S#', array_map('trim', $splitted)); // Remove Empty
    return ($splitted);
  }

/**
 * Get all entries from the codes DB. Ignore dates earlier than daterange_start 
 *
 * @return array
 */
  public function get_codes( ) {
    global $wpdb;
    $codes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_name  WHERE item_id = %s AND booking_date > $this->daterange_start", $this->item_id ), ARRAY_A); // get dates from db
    // $single = $this->split_csv( $codes );
    return $codes;
  }

 /**
 * Get code for date / item 
 *
 * @return array
 */
  public function get_code( $date ) {
    global $wpdb;
    $code = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_name  WHERE item_id = %s AND booking_date > $this->$date", $this->item_id ), ARRAY_A); // get dates from db
    return $code;
  }


/**
 * Compare timeframe dates and entries in the codes db 
 * */
  public function compare() {
    $codes_db = $this->get_codes();

    $tfDates = get_dates_between( $this->date_start, $this->date_end );
    $codeDates = array();

    foreach ( $codes_db as $entry ) {
      array_push ($codeDates, $entry['booking_date']);
    }
    
    $matched = array();
    $missing = array();
    $missingFlat = '';

    for ( $i = 0; $i < count($tfDates); $i++ ) {

      $index = array_search( $tfDates[ $i ], $codeDates );
      $temp = array();
      if ( ($index !== FALSE) ) {
        $temp[ 'date'] = $tfDates[ $i ];
        $temp[ 'code'] = $codes_db[ $index ]['bookingcode'];
        array_push ($matched, $temp);
      } else {
        $temp[ 'date'] = $tfDates[ $i ];
        array_push ($missing, $temp);
      }
    }
    $this->matchedDates = $matched;
    $this->missingDates = $missing;
  }

/**
 * Handle the display of the dates/codes interface
 */
public function render() {

  echo ( '<h2>Codes: ' . get_the_title( $this->item_id ) . '</h2>');

  if ( $this->missingDates ) { 
    ?>

    <?php new Admin_Table_Message ( __('No codes generated or codes missing.', $this->prefix), 'error' ); ?>
    <form id="codes" method="POST">
      <input class="hidden" name="id" value="<?= $this->timeframe_id; ?>">  
      <input class="hidden" name="generate" value="generate">
      <input type="submit" value="<?php _e('Generate Codes', $this->prefix)?>" id="submit_generate" class="button-primary" name="submit_generate">
    </form>

    <?php
    if (isset($_REQUEST['generate'])) {
      $sql = $this->sql_insert( $this->item_id, $this->missingDates, $this->codes_array );
    }
  } else { // no Codes missing?>
    <?php   
  } // end if $missingDates

  $allDates = array_merge ($this->missingDates, $this->matchedDates);
  $this->render_table( $allDates );
}
/**
 * Render the dates/codes-table.
 *
 */
public function render_table( $dates ) {
  ?>
  <table class="widefat striped">
    <thead>
      <tr>
        <th><?php _e( 'Date' ); ?></th>
        <th><?php _e( 'Code' ); ?></th>
      </tr>
    </thead>
  <?php foreach ($dates as $row) {
      if ( !isset($row[ 'code' ])) { 
        $row[ 'code' ] = ('<span style="color:red">'. __( ' Missing Code', $this->prefix) .'</span>'); 
      } ?>
    <tr><td><?php _e( date( 'j.n.y', strtotime( $row[ 'date' ] ))); ?></td><td><?php _e( $row[ 'code' ] ); ?></td></tr>
  <?php } // end foreach ?>
  </table>
  <?php
}
/**
 * Add pointers.
 * @TODO: check for security / split into prepare_sql and do_sql
 *
 * @param $itemid 
 * @param $array list of dates
 * @param $array list of codes
 */
private function sql_insert( $itemid, $array, $codes) {

  new WP_Admin_Notice( __( 'Error Messages' ), 'error' );

  global $wpdb;
  $table_name = $wpdb->prefix . 'cb_codes'; 

  shuffle( $codes ); // randomize array

  if ( count( $codes ) < count( $array )) {
    new Admin_Table_Message ( __('Not enough codes defined. Enter them in the Settings.', $this->prefix), 'error' );
    return false;

  }

  $sqlcols = "item_id,booking_date,bookingcode";
  $sqlcontents = array();
  $sqlquery = '';
  $count = count( $array );

  for ( $i=0; $i < $count; $i++ ) {
    array_push($sqlcontents, '("' . $itemid. '","' . $array[$i]['date'] . '","' . $codes[$i] . '")');
  }
  $sqlquery = 'INSERT INTO ' . $table_name . ' (' . $sqlcols . ') VALUES ' . implode (',', $sqlcontents ) . ';';

  $wpdb->query($sqlquery);
  }
}