<?php
/**
 * Plugin Name: Paychangu Payment Gateway for GiveWP
 * Plugin URI:  https://paychangu.com
 * Description: Paychangu add-on gateway for GiveWP.
 * Author: PayChangu
 * Author URI: https://profiles.wordpress.org/paychangultd
 * Version: 1.1.0
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Give\Helpers\Form\Utils as FormUtils;

/**
 * Paychangu Gateway form output
 *
 * Paychangu Gateway does not use a CC form
 *
 * @return bool
 **/
function waf_paychangu_for_give_form_output( $form_id ) {

	if (FormUtils::isLegacyForm($form_id)) {
		return false;
	}

	printf(
		'
		<fieldset class="no-fields">
			<p style="text-align: center;"><b>%1$s</b></p>
			<p style="text-align: center;">
				<b>%2$s</b>%3$s
			</p>
		</fieldset>
	',
		__( 'Make your donation quickly and securely with Paychangu', 'give' ),
		__( 'How it works: ', 'give' ),
		__( 'You will be redirected to Paychangu to pay using your Mobile Money or credit/debit card. You will then be brought back to this page to view your receipt.', 'give' )
	);

	return true;

}
add_action( 'give_paychangu_cc_form', 'waf_paychangu_for_give_form_output' );

/**
 * Register payment method.
 *
 * @since 1.1.0
 *
 * @param array $gateways List of registered gateways.
 *
 * @return array
 */
function waf_paychangu_for_give_register_payment_method( $gateways ) {
  
    // Duplicate this section to add support for multiple payment method from a custom payment gateway.
    $gateways['paychangu'] = array(
      'admin_label'    	=> 'Paychangu', 
      'checkout_label' 	=> 'Paychangu',
    );
    
    return $gateways;
  }
  
add_filter( 'give_payment_gateways', 'waf_paychangu_for_give_register_payment_method' );

/**
 * Register Section for Payment Gateway Settings.
 *
 * @param array $sections List of payment gateway sections.
 *
 * @since 1.1.0
 *
 * @return array
 */
function waf_paychangu_for_give_register_payment_gateway_sections( $sections ) {
	
	// `paychangu-settings` is the name/slug of the payment gateway section.
	$sections['paychangu-settings'] = 'Paychangu';

	return $sections;
}

add_filter( 'give_get_sections_gateways', 'waf_paychangu_for_give_register_payment_gateway_sections' );

// Get currently supported currencies from Paychangu endpoint
function waf_paychangu_for_give_get_supported_currencies($string = false){
	$currency_array = array('MWK', 'NGN', 'ZAR', 'GBP', 'USD');
	if ($string === true) {
		return implode(", ", $currency_array);
	}
	return $currency_array;
}

/**
 * Register Admin Settings.
 *
 * @param array $settings List of admin settings.
 *
 * @since 1.1.0
 *
 * @return array
 */
function waf_paychangu_for_give_register_payment_gateway_setting_fields( $settings ) {

	switch ( give_get_current_setting_section() ) {

		case 'paychangu-settings':
			$settings = array(
				array(
					'id'   => 'give_title_paychangu',
                    'desc' => 'Our Supported Currencies: <strong>' . esc_html(waf_paychangu_for_give_get_supported_currencies(true)) . '.</strong>',
					'type' => 'title',
				),
				array(
					'id'   => 'paychangu-invoicePrefix',
					'name' => 'Invoice Prefix',
					'desc' => 'Please enter a prefix for your invoice numbers. If you use your Paychangu account for multiple stores ensure this prefix is unique as Paychangu will not allow orders with the same invoice number.',
					'type' => 'text',
				),
                array(
					'id'   => 'paychangu-publicKey',
					'name' => 'Public Key',
					'desc' => 'Required: Enter your Public Key here. You can get your Public Key from <a href="https://in.paychangu.com/user/profile/api">here</a>',
					'type' => 'text',
				),
                array(
					'id'   => 'paychangu-secretKey',
					'name' => 'Secret Key',
					'desc' => 'Required: Enter your Secret Key here. You can get your Secret Key from <a href="https://in.paychangu.com/user/profile/api">here</a>',
					'type' => 'text',
				),
                array(
                    'id'   => 'give_title_paychangu',
                    'type' => 'sectionend',
                )
			);

			break;

	} // End switch().

	return $settings;
}

add_filter( 'give_get_settings_gateways', 'waf_paychangu_for_give_register_payment_gateway_setting_fields' );


/**
 * Process Paychangu checkout submission.
 *
 * @param array $posted_data List of posted data.
 *
 * @since  1.1.0
 * @access public
 *
 * @return void
 */
function waf_paychangu_for_give_process( $posted_data ) {
	// Make sure we don't have any left over errors present.
	give_clear_errors();

	// Any errors?
	$errors = give_get_errors();

	// No errors, proceed.
	if ( ! $errors ) {
		$form_id         = intval( $posted_data['post_data']['give-form-id'] );
		$price_id        = ! empty( $posted_data['post_data']['give-price-id'] ) ? $posted_data['post_data']['give-price-id'] : 0;
		$donation_amount = ! empty( $posted_data['price'] ) ? $posted_data['price'] : 0;
		$payment_mode = ! empty( $posted_data['post_data']['give-gateway'] ) ? $posted_data['post_data']['give-gateway'] : '';
		$redirect_to_url  = ! empty( $posted_data['post_data']['give-current-url'] ) ? $posted_data['post_data']['give-current-url'] : site_url();

		// Setup the payment details.
		$donation_data = array(
			'price'           => $donation_amount,
			'give_form_title' => $posted_data['post_data']['give-form-title'],
			'give_form_id'    => $form_id,
			'give_price_id'   => $price_id,
			'date'            => $posted_data['date'],
			'user_email'      => $posted_data['user_email'],
			'purchase_key'    => $posted_data['purchase_key'],
			'currency'        => give_get_currency( $form_id ),
			'user_info'       => $posted_data['user_info'],
			'status'          => 'pending',
			'gateway'         => 'paychangu',
		);

		// Record the pending donation.
		$donation_id = give_insert_payment( $donation_data );

		if ( ! $donation_id ) {
			// Record Gateway Error as Pending Donation in Give is not created.
			give_record_gateway_error(
				__( 'Paychangu Error', 'paychangu-for-give' ),
				sprintf(
				/* translators: %s Exception error message. */
					__( 'Unable to create a pending donation with Give.', 'paychangu-for-give' )
				)
			);

			// Send user back to checkout.
			give_send_back_to_checkout( '?payment-mode=paychangu' );
			return;
		}

        // Paychangu args
        $public_key = give_get_option( 'paychangu-publicKey' );
		$secret_key = give_get_option( 'paychangu-secretKey' );
        $tx_ref = give_get_option( 'paychangu-invoicePrefix' ) . $donation_id . strtotime('now');
        $currency_array = waf_paychangu_for_give_get_supported_currencies();
		$currency = give_get_currency( $form_id );
        $first_name = $donation_data['user_info']['first_name'];
        $last_name = $donation_data['user_info']['last_name'];
        $email = $donation_data['user_email'];
		$title = "Payment For Items on " . get_bloginfo('name');
		$callback_url = get_site_url() . "/wp-json/waf-paychangu-for-give/v1/process-success";

		// Validate data before send payment Paychangu request
		$invalid = 0;
		$error_msg = array();
        if ( !empty($public_key) && !empty($secret_key) && wp_http_validate_url($callback_url) ) {
            $public_key = sanitize_text_field($public_key);
			$secret_key = sanitize_text_field($secret_key);
            $callback_url = sanitize_url($callback_url);
        } else {
			array_push($error_msg, 'The payment setting of this website is not correct, please contact Administrator');
            $invalid++;
        }
        if ( !empty($tx_ref) ) {
            $tx_ref = sanitize_text_field($tx_ref);
        } else {
			array_push($error_msg, 'It seems that something is wrong with your order. Please try again');
            $invalid++;
        }
        if ( !empty($donation_amount) && is_numeric($donation_amount) ) {
            $donation_amount = floatval(sanitize_text_field($donation_amount));
        } else {
			array_push($error_msg, 'It seems that you have submitted an invalid donation amount for this order. Please try again');
            $invalid++;
        }
        if ( !empty($email) && is_email($email) ) {
            $email = sanitize_email($email);
        } else {
			array_push($error_msg, 'Your email is empty or not valid. Please check and try again');
            $invalid++;
        }
        if ( !empty($first_name) ) {
            $first_name = sanitize_text_field($first_name);
        } else {
			array_push($error_msg, 'Your first name is empty or not valid. Please check and try again');
            $invalid++;
        }
        if ( !empty($last_name) ) {
            $last_name = sanitize_text_field($last_name);
        } else {
			array_push($error_msg, 'Your last name is empty or not valid. Please check and try again');
            $invalid++;
        }
		if ( !empty($title) ) {
            $title = sanitize_text_field($title);
        } else {
			array_push($error_msg, 'The order title is empty or not valid. Please check and try again');
            $invalid++;
        }
        if ( !empty($currency) && in_array($currency, $currency_array) ) {
            $currency = sanitize_text_field($currency);
        } else {
			array_push($error_msg, 'The currency code is not valid. Please check and try again');
            $invalid++;
        }

		if ( $invalid === 0 ) {
			$apiUrl = 'https://api.paychangu.com/payment';
			$apiResponse = wp_remote_post($apiUrl,
				[
					'method' => 'POST',
					'headers' => [
						'content-type' => 'application/json',
						'Authorization' => 'Bearer ' . $secret_key,
					],
					'body' => json_encode(array(
						"amount" => $donation_amount,
						"currency" => $currency,
						"email" => $email,
						"first_name" => $first_name,
						"last_name" => $last_name,
						"callback_url" => $callback_url,
						"return_url" => $redirect_to_url,
						"tx_ref" => $tx_ref,
						"customization" => array(
							"title" => $title,
							"description" => $title
						),
						"meta" => array(
							"uuid" => "uuid",
      						"response" => "Response",
							"redirect_to_url" => $redirect_to_url,
							"donation_id" => $donation_id,
							"form_id" => $form_id,
							"price_id" => $price_id
						)
					))
				]
			);
			if (!is_wp_error($apiResponse)) {
				$apiBody = json_decode(wp_remote_retrieve_body($apiResponse));
				$external_url = $apiBody->data->checkout_url;
				if ($apiBody->status == 'success' && $external_url) {
					wp_redirect($external_url);
					die();
				} else {
					give_set_error( 'paychangu_request_error', "Payment was declined by Paychangu." );
					give_send_back_to_checkout( '?payment-mode=paychangu' );
					die();	
				}
			} else {
				give_set_error( 'paychangu_request_error', "Payment was declined by Paychangu." );
				give_send_back_to_checkout( '?payment-mode=paychangu' );
				die();
			}
		} else {
			give_set_error( 'paychangu_validate_error', implode("<br>", $error_msg) );
			give_send_back_to_checkout( '?payment-mode=paychangu' );
			die();
		}
	} else {
		give_send_back_to_checkout( '?payment-mode=paychangu' );
		die();
	}
}
add_action( 'give_gateway_paychangu', 'waf_paychangu_for_give_process' );


// Register process success rest api
add_action('rest_api_init', 'waf_paychangu_for_give_add_callback_url_endpoint_process_success');

function waf_paychangu_for_give_add_callback_url_endpoint_process_success() {
	register_rest_route(
		'waf-paychangu-for-give/v1/',
		'process-success',
		array(
			'methods' => 'GET',
			'callback' => 'waf_paychangu_for_give_process_success'
		)
	);
}

// Callback function of process success rest api
function waf_paychangu_for_give_process_success($request_data) {

	$parameters = $request_data->get_params();
	$secret_key = sanitize_text_field(give_get_option( 'paychangu-secretKey' ));
	$payment_mode = 'paychangu';
	$tx_ref = sanitize_text_field($parameters['tx_ref']);

	if ( $tx_ref ) {
		// Verify Paychangu payment
		$paychangu_request = wp_remote_get(
			'https://api.paychangu.com/verify-payment/' . $tx_ref,
			[
				'method' => 'GET',
				'headers' => [
					'content-type' => 'application/json',
					'Authorization' => 'Bearer ' . $secret_key,
				]
			]
		);

		if (!is_wp_error($paychangu_request) && 200 == wp_remote_retrieve_response_code($paychangu_request)) {
			$paychangu_payment = json_decode(wp_remote_retrieve_body($paychangu_request));
			$status = $paychangu_payment->status;
			$redirect_to_url = $paychangu_payment->data->meta->redirect_to_url;
			$donation_id = $paychangu_payment->data->meta->donation_id;
			$form_id = $paychangu_payment->data->meta->form_id;
			$price_id = $paychangu_payment->data->meta->price_id;

			if ( $status === "success" ) {
                give_update_payment_status( $donation_id, 'publish' );
				give_set_payment_transaction_id( $donation_id, $tx_ref );
                give_insert_payment_note( $donation_id, "Payment via Paychangu successful with Reference ID: " . $tx_ref );
				give_send_to_success_page();
				die();
			} else if ($status === "cancelled") {
                give_update_payment_status( $donation_id, 'failed' );
                give_insert_payment_note( $donation_id, "Payment was canceled.");
                give_set_error( 'paychangu_request_error', "Payment was canceled." );
				wp_redirect( $redirect_to_url . "?form-id=" . $form_id . "&level-id=" . $price_id . "&payment-mode=paychangu#give-form-" . $form_id . "-wrap" );
				die();
			} else {
                give_update_payment_status( $donation_id, 'failed' );
                give_insert_payment_note( $donation_id, "Payment was declined by Paychangu.");
				give_set_error( 'paychangu_request_error', "Payment was declined by Paychangu." );
				wp_redirect( $redirect_to_url . "?form-id=" . $form_id . "&level-id=" . $price_id . "&payment-mode=paychangu#give-form-" . $form_id . "-wrap" );
				die();
			}
		}
	}
	die();
}