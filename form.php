<?php
/*
 * Config fields
 * You should configure this before using this contact form.
 */
$recipientEmailAddress = 'your_email_address@local.host';
$recipientName = 'YOUR_NAME_HERE';
$additionalHeaders = array(
	'MIME-Version: 1.0',
	'Content-Type: text/html; charset=ISO-8859-1',
//	'Content-Type: text/html; charset=UTF-8',
);
/* You can add new allowed tags or remove the existing one, inside the quotes below */
$allowedTags = '<div><p><span><h1><h2><h3><h4><h5><h6><br><hr><code><pre><blockquote><cite>';
$debug = false;
//#! placeholder: use the fields from config.
// Ex: to display the product: #input_product# will display the value specified in the product field of the contact form
// etc...
$emailSubject = 'You have been contacted by @input_username';
$thankYouMessage = 'Thank you for your message. We will be getting back to you as soon as possible.';

/*
* Fields configuration for validation
*/
$fieldsConfig = array(
	'input_username' => array('value_not_empty' => 'Please specify a value for the Name field' ),
	'input_phone' => array('value_not_empty' => 'Please specify a value for the Phone field' ),
	'input_email' => array(
		// FUNCTION_NAME => ERROR_MESSAGE
		'value_not_empty' => 'Please specify a value for the Email field',
		'is_valid_email' => 'Please enter a valid email',
	),
	'input_message' => array(
		'value_not_empty' => 'Please enter a message',
	),
);

//#! How to: Add a new input to the contact form
//#! How to: Access this field later on: $postFields['input_email2']
/*
$fieldsConfig['input_email2'] = array(
	// FUNCTION_NAME => ERROR_MESSAGE
	'value_not_empty' => 'Please specify a value for the Email 2 field',
	'is_valid_email' => 'Please enter a valid email 2',
);
*/

//<editor-fold desc=":: HELPER FUNCTIONS ::">
function jsonSuccess( $data ) {
	header('Content-Type: application/json');
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');
	exit( json_encode( array( 'success' => true, 'data' => $data ) ) );
}
function jsonError( $data ) {
	header('Content-Type: application/json');
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');
	if( is_string($data)){
		$data = array( $data );
	}
	exit( json_encode( array( 'success' => false, 'data' => $data ) ) );
}
function value_not_empty( $value ){
	return ( ! empty( $value ) );
}

function is_valid_email( $value ){
	return filter_var($value, FILTER_VALIDATE_EMAIL);
}
function esc_html( $value, $stripTags = true, $allowTags = '' ) {
	if( $stripTags ){
		$value = strip_tags($value, $allowTags );
	}
	return trim( $value );
}
function unescape_html( $value ) {
	return stripslashes( $value );
}
//</editor-fold desc=":: HELPER FUNCTIONS ::">

//<editor-fold desc=":: Validate POST request ::">
if( 'POST' != strtoupper($_SERVER['REQUEST_METHOD']) ){
	jsonError( 'Invalid Request.' );
}

//#! Setup the post fields list
$postFields = array();
$fields = ( isset($_POST['fields']) ? $_POST['fields'] : null );
if( empty($fields)) {
	jsonError( 'Invalid request, fields are missing.');
}

parse_str($_POST['fields'], $postFields);

//#! Make sure the fields are there
if( empty($postFields)) {
	jsonError( 'Invalid request, fields are missing.');
}

//#! Holds the errors generated during the request
$errors = array();


//#! Sanitize data
foreach( $postFields as $fieldName => &$fieldValue ) {
	$fieldValue = esc_html( $fieldValue, true, $allowedTags );
}

//#! Validate request
foreach( $fieldsConfig as $fieldName => $validationRules ) {
	if( isset( $postFields[$fieldName] ) ){

		$fv = $postFields[$fieldName];

		foreach( $validationRules as $fn => $err) {
			$result = call_user_func( $fn, $fv );
			if( ! $result ){
				array_push( $errors, $err );
			}
		}
	}
	else { array_push( $errors, 'Invalid request, input <strong>'.$fieldName.'</strong> is missing.' ); }
}

//#! Check for errors
if( ! empty($errors)){
	jsonError( $errors );
}

//#! Unescape fields
foreach($postFields as &$input) {
	unescape_html($input);
}

//#! Replace placeholders
foreach($postFields as $k => $v ) {
	$emailSubject = str_replace( '@'.$k, $v, $emailSubject );
}
//</editor-fold desc=":: Validate POST request ::">

//<editor-fold desc=":: Compose message and send email ::">
$subject = $emailSubject;
$message = '<div>';
$message .= $postFields['input_message'];
$message .= '</div>';

//#! Configure headers

array_push( $additionalHeaders, sprintf( 'From: %s<%s>', $recipientName, $recipientEmailAddress ) );
array_push( $additionalHeaders, sprintf( 'Reply-to: %s<%s>', $postFields['input_username'], $postFields['input_email'] ) );
array_push( $additionalHeaders, 'X-Mailer: PHP/' . phpversion() );
$additionalHeaders = implode( "\r\n", $additionalHeaders);

$result = @mail( $recipientEmailAddress, $subject, $message, $additionalHeaders );

if( true != $result ) {
	if( $debug ){
		jsonError($result);
	}
	else {
		jsonError('An error occurred, please try again later.');
	}
}

jsonSuccess($thankYouMessage);
//</editor-fold desc=":: Compose message and send email ::">