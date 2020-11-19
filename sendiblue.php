<?php
/**
 * Plugin Name: Sendinblue for WooCommerce
 * Plugin URI: https://goodtechies.com/
 * Description: No Description
 * Version: 1.0
 * Author: Md Mehedi Hasan
 * Author URI: https://twitter.com/HeyMehedi
 * Text Domain: sendinblue-for-woocommerce
 * Requires at least: 5.0
 * Requires PHP: 5.6
 */

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Add checkbox field to the checkout
 **/

add_action('woocommerce_after_order_notes', 'my_custom_checkout_field');

function my_custom_checkout_field($checkout)
{

    echo '<div id="my-new-field">';

    woocommerce_form_field('is_checked', array(
        'type' => 'checkbox',
        'class' => array('input-checkbox'),
        'label' => __('I have read and agreed.'),
        'default' => 1,
    ), $checkout->get_value('is_checked'));

    echo '</div>';
}

/**
 * Update the order meta with field value
 **/
add_action('woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta');

function my_custom_checkout_field_update_order_meta($order_id)
{
    if ($_POST['is_checked']) {
        update_post_meta($order_id, 'is_checked', esc_attr($_POST['is_checked']));
    }

}

/**
 * WooCommerce Order Data
 **/
add_action('woocommerce_order_status_processing', 'wc_send_order_to_sendiblue');
function wc_send_order_to_sendiblue($order_id)
{
    $order = new WC_Order($order_id);
    $ifHave = file_put_contents(__DIR__ . "/temp_data.log", $order, LOCK_EX);
    if ($ifHave) {
        $orderData = file_get_contents(__DIR__ . "/temp_data.log");
        $json_decode_order = json_decode($orderData, true);

        $heymehedi_email = $json_decode_order['billing']['email'];
        $heymehedi_first_name = $json_decode_order['billing']['first_name'];
        $heymehedi_last_name = $json_decode_order['billing']['last_name'];
        $heymehedi_phone = $json_decode_order['billing']['phone'];

        $heymehedi_metaKeys = $json_decode_order['meta_data'];

        foreach ($heymehedi_metaKeys as $metaKey) {
            if ($metaKey['key'] == "is_checked" && $metaKey['value'] == 1) {
                if ($heymehedi_email != '') {

                    /**
                     * Creating New Contact
                     **/
                    $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', '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');

                    $apiInstance = new SendinBlue\Client\Api\ContactsApi(
                        new GuzzleHttp\Client(),
                        $config
                    );
                    $createContact = new \SendinBlue\Client\Model\CreateContact(); // Values to create a contact
                    $createContact['email'] = "$heymehedi_email";
                    $createContact['attributes'] = array(
                        "FIRSTNAME" => "$heymehedi_first_name",
                        "LASTNAME" => "$heymehedi_last_name",
                    );
                    $createContact['listIds'] = [2];

                    try {
                        $apiInstance->createContact($createContact);
                    } catch (Exception $e) {
                        echo 'Exception when calling ContactsApi->createContact: ', $e->getMessage(), PHP_EOL;
                    }
                }
            }
        }
    }
}