<?php
/**
 * Plugin Name: Українська служба доставки Delivery для WooCommerce
 * Description: Плагін доставки Українською службою Delivery для WooCommerce
 * Version: 1.0.0
 * Author: Ice Design
 * Author URI: https://ice-design.pp.ua
 * Text Domain: wc-Delivery-shipping
 * License URI: license.txt
 * Requires PHP: 7.4
 * Tested up to: 9.0
 * WC tested up to: 6.5
*/

/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    function delivery_shipping_method() {
        if ( ! class_exists( 'Delivery_Shipping_Method' ) ) {
            class Delivery_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
    
                public function __construct() {
                    $this->id                 = 'delivery'; 
                    $this->method_title       = __( 'Delivery Доставка', 'delivery' );  
                    $this->method_description = __( 'Delivery метод доставки', 'delivery' ); 
 
                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries = array('UA');
 
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Доставка Delivery', 'delivery' );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->form_fields = array(
 
                     'enabled' => array(
                          'title' => __( 'Увімкнути', 'delivery' ),
                          'type' => 'checkbox',
                          'description' => __( 'Увімкнути цю доставку.', 'delivery' ),
                          'default' => 'yes'
                          ),
 
                     'title' => array(
                        'title' => __( 'Заголовок', 'delivery' ),
                          'type' => 'text',
                          'description' => __( 'Заголовок для відображення на сайті', 'delivery' ),
                          'default' => __( 'Доставка Delivery', 'delivery' )
                          ),
                    );
 
                }
 
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
               public function calculate_shipping($package = Array() ) {
                    
                    $cost = 0;
 
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => $cost
                    );
 
                    $this->add_rate( $rate );
                    
                }
            }
        }
    }
 
    add_action( 'woocommerce_shipping_init', 'delivery_shipping_method' );
 
    function add_delivery_shipping_method( $methods ) {
        $methods[] = 'Delivery_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_delivery_shipping_method' );
 
    function delivery_validate_order( $posted )   {
 
        $packages = WC()->shipping->get_packages();
 
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
         
        if( is_array( $chosen_methods ) && in_array( 'delivery', $chosen_methods ) ) { ?>
            <script>
                jQuery(document).ready(function() {
                    jQuery('#city').prop('disabled', true);
                    jQuery('#warehouses').prop('disabled', true);
                    fetch('https://api.ice-design.pp.ua/delivery.php?area=1')
                        .then((response) => {
                            return response.json();
                        })
                        .then((data) => {
                            let arrays = Object.values(data.data);
                            jQuery('#delivery').find('option').remove();
                            for (let i = 0; i < arrays.length; i++) {
                                jQuery('#delivery').append(jQuery("<option></option>", {value: arrays[i]['id'], text: arrays[i]['name']}));
                            }
                        });
                });
                
                jQuery('#delivery').on('change', function() {
                    fetch('https://api.ice-design.pp.ua/delivery.php?region='+this.value)
                        .then((response) => {
                            return response.json();
                        })
                        .then((data) => {
                            let arrays = Object.values(data.data);
                            jQuery('#city').prop('disabled', false);
                            jQuery('#city').find('option').remove();
                            jQuery('input[name="delivery_delivery_name"]').val(jQuery(this).find('option:selected').text());
                            jQuery('#city').append(jQuery("<option></option>", {value: 0, text: 'Оберіть Місто'}));
                            for (let i = 0; i < arrays.length; i++) {
                                jQuery('#city').append(jQuery("<option></option>", {value: arrays[i]['id'], text: arrays[i]['name']}));
                            }
                        });
                });
                jQuery('#city').on('change', function() {
                    fetch('https://api.ice-design.pp.ua/delivery.php?city='+this.value)
                        .then((response) => {
                            return response.json();
                        })
                        .then((data) => {
                            let arrays = Object.values(data.data);
                            jQuery('#warehouses').prop('disabled', false);
                            jQuery('#warehouses').find('option').remove();
                            jQuery('input[name="delivery_city_name"]').val(jQuery(this).find('option:selected').text());
                            jQuery('#warehouses').append(jQuery("<option></option>", {value: 0, text: 'Оберіть відділеня'}));
                            for (let i = 0; i < arrays.length; i++) {
                                jQuery('#warehouses').append(jQuery("<option></option>", {value: arrays[i]['id'], text: arrays[i]['name']+arrays[i]['address']}));
                            }
                        });
                });
                jQuery('#warehouses').on('change', function() {
                    jQuery('input[name="delivery_warehouses_name"]').val(jQuery(this).find('option:selected').text());
                });
            </script>
            <style>#delivery_checkout_field{display:block !important}</style>
            <input type="hidden" name="delivery_delivery_name" value="">
            <input type="hidden" name="delivery_city_name" value="">
            <input type="hidden" name="delivery_warehouses_name" value="">
            <?   
        } 
    }
 
    add_action( 'woocommerce_review_order_before_cart_contents', 'delivery_validate_order' , 10 );
    add_action( 'woocommerce_after_checkout_validation', 'delivery_validate_order' , 10 );
    add_action( 'woocommerce_after_checkout_billing_form', 'delivery_checkout_field' );
    add_action( 'woocommerce_checkout_update_order_meta', 'delivery_save_field', 25 );
    add_action( 'woocommerce_admin_order_data_after_shipping_address', 'delivery_print_field_value', 25 );
    add_filter( 'woocommerce_get_order_item_totals', 'delivery_field_in_email', 25, 2 );
     
    function delivery_print_field_value( $order ) {
     
        if( $method = get_post_meta( $order->get_id(), 'delivery', true ) ) {
            echo '<p><strong>Область:</strong><br>' . esc_html( $method ) . '</p>';
        }
        if( $method = get_post_meta( $order->get_id(), 'city', true ) ) {
            echo '<p><strong>Місто:</strong><br>' . esc_html( $method ) . '</p>';
        }
        if( $method = get_post_meta( $order->get_id(), 'warehouses', true ) ) {
            echo '<p><strong>Відділеня Делівері:</strong><br>' . esc_html( $method ) . '</p>';
        }
    }
     
    function delivery_field_in_email( $rows, $order ) {
     
        if( is_order_received_page() ) {
            return $rows;
        }
     
        $rows[ 'billing_delivery' ] = array(
            'label' => 'Область',
            'value' => get_post_meta( $order->get_id(), 'delivery', true )
        );
        $rows[ 'billing_city' ] = array(
            'label' => 'Місто',
            'value' => get_post_meta( $order->get_id(), 'city', true )
        );
        $rows[ 'billing_warehouses' ] = array(
            'label' => 'Відділеня Делівері',
            'value' => get_post_meta( $order->get_id(), 'warehouses', true )
        );
     
        return $rows;
     
    }



    function delivery_save_field( $order_id ){
     
        if( ! empty( $_POST[ 'delivery_delivery_name' ] ) ) {
            update_post_meta( $order_id, 'delivery', sanitize_text_field( $_POST[ 'delivery_delivery_name' ] ) );
        }
        if( ! empty( $_POST[ 'delivery_city_name' ] ) ) {
            update_post_meta( $order_id, 'city', sanitize_text_field( $_POST[ 'delivery_city_name' ] ) );
        }
        if( ! empty( $_POST[ 'delivery_warehouses_name' ] ) ) {
            update_post_meta( $order_id, 'warehouses', sanitize_text_field( $_POST[ 'delivery_warehouses_name' ] ) );
        }
     
    }


    function delivery_checkout_field( $checkout ) {
    
        echo '<div id="delivery_checkout_field" style="display: none;"><h2>' . __('Дані доставки Delivery') . '</h2>';
    
        woocommerce_form_field( 'delivery', array(
            'type' => 'select',
            'class' => array('delivery_region form-row-wide'),
            'label' => 'Оберіть область',
            'placeholder' => __('Оберіть область'),
            'options' => array(
                '0' => 'Оберіть область',
            )
        ), $checkout->get_value( 'delivery' ));
        
        
        woocommerce_form_field( 'city', array(
            'type' => 'select',
            'class' => array('delivery_city form-row-wide'),
            'label' => 'Оберіть Місто',
            'placeholder' => __('Оберіть Місто'),
            'options' => array(
                '0' => 'Оберіть Місто',
            )
        ), $checkout->get_value( 'city' ));

        woocommerce_form_field( 'warehouses', array(
            'type' => 'select',
            'class' => array('delivery_warehouses form-row-wide'),
            'label' => 'Оберіть відділеня',
            'placeholder' => __('Оберіть відділеня'),
            'options' => array(
                '0' => 'Оберіть відділеня',
            )
        ), $checkout->get_value( 'warehouses' ));
    
        echo '</div>';
    
    }
}