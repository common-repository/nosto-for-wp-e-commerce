<?php
/*
	Plugin Name: WP e-Commerce Nosto Tagging
	Plugin URI: http://wordpress.org/extend/plugins/wp-e-commerce-nosto-tagging/
	Description: Implements the required tagging blocks for using Nosto marketing automation service.
	Author: Nosto Solutions Ltd
	Version: 1.0.3
	License: GPLv2
*/

/*	Copyright 2013 Nosto Solutions Ltd  (email : PLUGIN AUTHOR EMAIL)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Main plugin class.
 *
 * @package WP e-Commerce Nosto Tagging
 * @since   1.0.0
 */
class WPEC_Nosto_Tagging
{
	/**
	 * Plugin version.
	 * Used for dependency checks.
	 *
	 * @since 1.0.0
	 */
	const VERSION = '1.0.3';

	/**
	 * Minimum WordPress version this plugin works with.
	 * Used for dependency checks.
	 *
	 * @since 1.0.0
	 */
	const MIN_WP_VERSION = '3.5';

	/**
	 * Minimum WP e-Commerce plugin version this plugin works with.
	 * Used for dependency checks.
	 *
	 * @since 1.0.0
	 */
	const MIN_WPSC_VERSION = '3.8.9.1';

	/**
	 * Default server address for the Nosto marketing automation service.
	 * Used on plugin config page.
	 *
	 * @since 1.0.0
	 */
	const DEFAULT_NOSTO_SERVER_ADDRESS = 'connect.nosto.com';

	/**
	 * Value for marking a product that is in stock.
	 * Used in product tagging.
	 *
	 * @since 1.0.0
	 */
	const PRODUCT_IN_STOCK = 'InStock';

	/**
	 * Value for marking a product that is not in stock.
	 * Used in product tagging.
	 *
	 * @since 1.0.0
	 */
	const PRODUCT_OUT_OF_STOCK = 'OutOfStock';

	/**
	 * The working instance of the plugin.
	 *
	 * @since 1.0.0
	 * @var WPEC_Nosto_Tagging|null
	 */
	protected static $instance = null;

	/**
	 * The plugin directory path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $plugin_dir = '';

	/**
	 * The URL to the plugin directory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $plugin_url = '';

	/**
	 * The plugin base name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $plugin_name = '';

	/**
	 * Gets the working instance of the plugin.
	 *
	 * @since 1.0.0
	 * @return WPEC_Nosto_Tagging|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Plugin uses Singleton pattern, hence the constructor is private.
	 *
	 * @since 1.0.0
	 * @return WPEC_Nosto_Tagging
	 */
	private function __construct() {
		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_name = plugin_basename( __FILE__ );

		register_activation_hook( $this->plugin_name, array( $this, 'activate' ) );
		register_deactivation_hook( $this->plugin_name, array( $this, 'deactivate' ) );
		// The uninstall hook callback needs to be a static class method or function.
		register_uninstall_hook( $this->plugin_name, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Initializes the plugin.
	 *
	 * Register hooks outputting tagging blocks and Nosto elements.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		if ( is_admin() ) {
			$this->load_class( 'WPEC_Nosto_Tagging_Admin' );
			$admin = new WPEC_Nosto_Tagging_Admin();
			add_action( 'wpsc_register_settings_tabs', array( $admin, 'register_tab' ) );
			add_action( 'wpsc_load_settings_tab_class', array( $admin, 'register_tab' ) );
			add_action( 'admin_init', array( $admin, 'register_settings' ) );
			add_filter( 'plugin_action_links', array( $admin, 'register_action_links' ), 10, 2 );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

			add_action( 'wpsc_top_of_products_page', array( $this, 'tag_product' ) );
			add_action( 'wpsc_top_of_products_page', array( $this, 'tag_category' ) );
			add_action( 'wp_footer', array( $this, 'tag_customer' ) );
			add_action( 'wp_footer', array( $this, 'tag_cart' ) );
			add_action( 'wpsc_transaction_results_shutdown', array( $this, 'tag_order' ) );

			if ( $this->use_default_elements() ) {
				add_action( 'wpsc_theme_footer', array( $this, 'add_product_page_bottom_elements' ) );
				add_action( 'wpsc_top_of_products_page', array( $this, 'add_category_page_top_elements' ) );
				add_action( 'wpsc_theme_footer', array( $this, 'add_category_page_bottom_elements' ) );
				add_action( 'wpsc_bottom_of_shopping_cart', array( $this, 'add_cart_page_bottom_elements' ) );
				add_action( 'wpecnt_top_of_search_results', array( $this, 'add_search_page_top_elements' ) );
				add_action( 'wpecnt_bottom_of_search_results', array( $this, 'add_search_page_bottom_elements' ) );
				add_action( 'wpecnt_top_of_pages', array( $this, 'add_page_top_elements' ) );
				add_action( 'wpecnt_bottom_of_pages', array( $this, 'add_page_bottom_elements' ) );
			}
		}

		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Hook callback function for activating the plugin.
	 *
	 * Sets default config values if they do not exist.
	 * Creates the Top Sellers page or only publishes it if it already exists.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		if ( $this->check_dependencies() ) {
			add_option( 'nosto_tagging_server_address', self::DEFAULT_NOSTO_SERVER_ADDRESS );
			add_option( 'nosto_tagging_account_id', '' );
			add_option( 'nosto_tagging_use_default_elements', 1 );

			$this->load_class( 'WPEC_Nosto_Tagging_Top_Sellers_Page' );
			$page_id = get_option( 'nosto_tagging_top_sellers_page_id', null );
			$page    = new WPEC_Nosto_Tagging_Top_Sellers_Page( $page_id );
			$page->publish();
			if ( null === $page_id ) {
				add_option( 'nosto_tagging_top_sellers_page_id', $page->get_id() );
			} else {
				update_option( 'nosto_tagging_top_sellers_page_id', $page->get_id() );
			}
		}
	}

	/**
	 * Hook callback function for deactivating the plugin.
	 *
	 * Un-publishes the Top Sellers page.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		$page_id = get_option( 'nosto_tagging_top_sellers_page_id' );
		if ( $page_id ) {
			$this->load_class( 'WPEC_Nosto_Tagging_Top_Sellers_Page' );
			$page = new WPEC_Nosto_Tagging_Top_Sellers_Page( $page_id );
			$page->unpublish();
		}
	}

	/**
	 * Hook callback function for uninstalling the plugin.
	 *
	 * Deletes the Top Sellers page and plugin config values.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		$page_id = get_option( 'nosto_tagging_top_sellers_page_id' );
		if ( $page_id ) {
			// This has to be a static method, so we load the top sellers class through
			// the main plugin instance. The instance will already exist at this point,
			// so there will be no unnecessary instantiation.
			// This is just to avoid duplicating the code in WPEC_Nosto_Tagging::load_class().
			WPEC_Nosto_Tagging::get_instance()->load_class( 'WPEC_Nosto_Tagging_Top_Sellers_Page' );
			$page = new WPEC_Nosto_Tagging_Top_Sellers_Page( $page_id );
			$page->remove();
		}

		delete_option( 'nosto_tagging_server_address' );
		delete_option( 'nosto_tagging_account_id' );
		delete_option( 'nosto_tagging_use_default_elements' );
		delete_option( 'nosto_tagging_top_sellers_page_id' );
		delete_option( 'widget_wpec_nosto_element_widget' );
	}

	/**
	 * Getter for the plugin base name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Load class file based on class name.
	 *
	 * The file are expected to be located in the plugin "classes" directory.
	 *
	 * @since 1.0.0
	 * @param string $class_name The name of the class to load.
	 */
	public function load_class( $class_name ) {
		$file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
		require_once( $this->plugin_dir . 'classes/' . $file );
	}

	/**
	 * Renders a template file.
	 *
	 * The file is expected to be located in the plugin "templates" directory.
	 *
	 * @since 1.0.0
	 * @param string $template The name of the template
	 * @param array  $data     The data to pass to the template file
	 */
	public function render( $template, $data = array() ) {
		extract( $data );
		$file = $template . '.php';
		require( $this->plugin_dir . 'templates/' . $file );
	}

	/**
	 * Registers the Nosto JavaScript to be added to the page head section.
	 *
	 * Both the server address and the account id need to be set for the
	 * script to be added.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		$account_id = get_option( 'nosto_tagging_account_id' );

		if ( ! empty( $account_id ) ) {
			wp_enqueue_script( 'nosto-tagging-script', $this->plugin_url . 'js/embed.js' );
			$params = array(
				'accountId'     => esc_js( $account_id ),
			);
			wp_localize_script( 'nosto-tagging-script', 'NostoTagging', $params );
		}
	}

	/**
	 * Hook callback function for tagging products.
	 *
	 * Gathers necessary data and renders the product tagging div.
	 *
	 * @since 1.0.0
	 */
	public function tag_product() {
		if ( wpsc_is_single_product() ) {
			$product = array();

			while ( wpsc_have_products() ) {
				wpsc_the_product();

				$product_id = (int) wpsc_the_product_id();

				$product['url']        = (string) wpsc_the_product_permalink();
				$product['product_id'] = $product_id;
				$product['name']       = (string) wpsc_the_product_title();
				$product['image_url']  = (string) wpsc_the_product_image( '', '', $product_id );

				if ( wpsc_product_has_variations( $product_id ) ) {
					$price = $this->get_lowest_product_variation_price( $product_id );
				} else {
					$price = wpsc_calculate_price( $product_id, false, true );
				}
				$product['price'] = $this->format_price( $price );

				$product['price_currency_code'] = $this->get_currency_iso_code();

				if ( wpsc_product_has_stock( $product_id ) ) {
					$product['availability'] = self::PRODUCT_IN_STOCK;
				} else {
					$product['availability'] = self::PRODUCT_OUT_OF_STOCK;
				}

				$product['categories'] = array();
				$category_terms        = wp_get_product_categories( $product_id );
				foreach ( $category_terms as $category_term ) {
					$category_path = $this->build_category_string( $category_term );
					if ( ! empty( $category_path ) ) {
						$product['categories'][] = $category_path;
					}
				}

				$product['description'] = (string) wpsc_the_product_description();

				if ( wpsc_product_has_variations( $product_id ) ) {
					$list_price = $this->get_lowest_product_variation_price( $product_id );
				} else {
					$list_price = wpsc_calculate_price( $product_id, false, false );
				}
				$product['list_price'] = $this->format_price( $list_price );

				$product['date_published'] = (string) get_post_time( 'Y-m-d' );
			}

			if ( ! empty( $product ) ) {
				$this->render( 'product-tagging', array( 'product' => $product ) );
			}
		}
	}

	/**
	 * Hook callback function for tagging categories.
	 *
	 * Gathers necessary data and renders the category tagging div.
	 *
	 * @since 1.0.0
	 */
	public function tag_category() {
		if ( wpsc_is_in_category() ) {
			$category_slug = get_query_var( 'wpsc_product_category' );
			if ( ! empty( $category_slug ) ) {
				$category_term = get_term_by( 'slug', $category_slug, 'wpsc_product_category' );
				$category_path = $this->build_category_string( $category_term );
				if ( ! empty( $category_path ) ) {
					$this->render( 'category-tagging', array( 'category_path' => $category_path ) );
				}
			}
		}
	}

	/**
	 * Hook callback function for tagging logged in customers.
	 *
	 * Gathers necessary data and renders the customer tagging div.
	 *
	 * @since 1.0.0
	 */
	public function tag_customer() {
		if ( is_user_logged_in() ) {
			$user     = wp_get_current_user();
			$customer = $this->get_customer_data( $user );
			if ( ! empty( $customer ) ) {
				$this->render( 'customer-tagging', array( 'customer' => $customer ) );
			}
		}
	}

	/**
	 * Hook callback function for tagging cart content.
	 *
	 * Gathers necessary data and renders the cart tagging div.
	 *
	 * @since 1.0.0
	 */
	public function tag_cart() {
		if ( 0 < wpsc_cart_item_count() ) {
			global $wpsc_cart;

			$line_items = array();

			while ( wpsc_have_cart_items() ) {
				wpsc_the_cart_item();

				$current_item = $wpsc_cart->cart_item;

				// If the item has a parent, it means that it is a product variation
				// and we are interested in the parent product data.
				$parent = $this->get_parent_post( $current_item->product_id );
				if ( $parent ) {
					$product_id   = $parent->ID;
					$product_name = $parent->post_title;
				} else {
					$product_id   = wpsc_cart_item_product_id();
					$product_name = wpsc_cart_item_name();
				}

				$line_item = array(
					'product_id'          => (int) $product_id,
					'quantity'            => (int) wpsc_cart_item_quantity(),
					'name'                => (string) $product_name,
					'unit_price'          => $this->format_price( wpsc_cart_single_item_price( false ) ),
					'price_currency_code' => $this->get_currency_iso_code(),
				);

				$line_items[] = $line_item;
			}

			if ( ! empty( $line_items ) ) {
				$this->render( 'cart-tagging', array( 'line_items' => $line_items ) );
			}
		}
	}

	/**
	 * Hook callback function for tagging successful orders.
	 *
	 * Gathers necessary data and renders the order tagging div.
	 *
	 * @since 1.0.0
	 * @param WPSC_Purchase_Log $purchase_log The purchase log object
	 */
	public function tag_order( $purchase_log ) {
		if ( $purchase_log instanceof WPSC_Purchase_Log ) {
			$order = array(
				'order_number' => $purchase_log->get( 'id' ),
				'buyer'        => array(),
				'line_items'   => array(),
			);

			$checkout_form  = new WPSC_Checkout_Form_Data( $purchase_log->get( 'id' ) );
			$order['buyer'] = array(
				'first_name' => $checkout_form->get( 'billingfirstname' ),
				'last_name'  => $checkout_form->get( 'billinglastname' ),
				'email'      => $checkout_form->get( 'billingemail' ),
			);

			$products = $purchase_log->get_cart_contents();
			if ( is_array( $products ) ) {
				foreach ( $products as $product ) {
					// If the item has a parent, it means that it is a product variation
					// and we are interested in the parent product data.
					$parent = $this->get_parent_post( $product->prodid );
					if ( $parent ) {
						$product_id   = $parent->ID;
						$product_name = $parent->post_title;
					} else {
						$product_id   = $product->prodid;
						$product_name = $product->name;
					}

					$line_item = array(
						'product_id'          => (int) $product_id,
						'quantity'            => (int) $product->quantity,
						'name'                => (string) $product_name,
						'unit_price'          => $this->format_price( $product->price ),
						'price_currency_code' => $this->get_currency_iso_code(),
					);

					$order['line_items'][] = $line_item;
				}
			}

			if ( ! empty( $order['line_items'] ) ) {
				// Add special line items for tax, shipping and discounts.
				$gateway_data = $purchase_log->get_gateway_data();

				// If product prices are tax exclusive, then we have the total tax separated
				// from the product prices and we need to also tag it separately.
				if ( isset( $gateway_data['tax'] ) && 0 < $gateway_data['tax'] ) {
					$order['line_items'][] = array(
						'product_id'          => - 1,
						'quantity'            => 1,
						'name'                => 'Tax',
						'unit_price'          => $this->format_price( $gateway_data['tax'] ),
						'price_currency_code' => $this->get_currency_iso_code(),
					);
				}

				if ( isset( $gateway_data['shipping'] ) && 0 < $gateway_data['shipping'] ) {
					$order['line_items'][] = array(
						'product_id'          => - 1,
						'quantity'            => 1,
						'name'                => 'Shipping',
						'unit_price'          => $this->format_price( $gateway_data['shipping'] ),
						'price_currency_code' => $this->get_currency_iso_code(),
					);
				}

				if ( isset( $gateway_data['discount'] ) && 0 < $gateway_data['discount'] ) {
					$order['line_items'][] = array(
						'product_id'          => - 1,
						'quantity'            => 1,
						'name'                => 'Discount',
						'unit_price'          => $this->format_price( - $gateway_data['discount'] ),
						'price_currency_code' => $this->get_currency_iso_code(),
					);
				}

				$this->render( 'order-tagging', array( 'order' => $order ) );
			}
		}
	}

	/**
	 * Hook callback function for outputting the Nosto elements at the bottom of the product page.
	 *
	 * @since 1.0.0
	 */
	public function add_product_page_bottom_elements() {
		if ( wpsc_is_single_product() ) {
			$default_element_ids = array(
				'productpage-nosto-1',
				'productpage-nosto-2',
				'productpage-nosto-3',
			);
			$element_ids         = apply_filters( 'wpecnt_add_product_page_bottom_elements', $default_element_ids );
			if ( is_array( $element_ids ) && ! empty( $element_ids ) ) {
				$this->render( 'nosto-elements', array( 'element_ids' => $element_ids ) );
			}
		}
	}

	/**
	 * Hook callback function for outputting the Nosto elements at the top of the category pages.
	 *
	 * @since 1.0.0
	 */
	public function add_category_page_top_elements() {
		if ( wpsc_is_in_category() ) {
			$default_element_ids = array(
				'productcategory-nosto-1',
			);
			$element_ids         = apply_filters( 'wpecnt_add_category_page_top_elements', $default_element_ids );
			if ( is_array( $element_ids ) && ! empty( $element_ids ) ) {
				$this->render( 'nosto-elements', array( 'element_ids' => $element_ids ) );
			}
		}
	}

	/**
	 * Hook callback function for outputting the Nosto elements at the bottom of the category page.
	 *
	 * @since 1.0.0
	 */
	public function add_category_page_bottom_elements() {
		if ( wpsc_is_in_category() ) {
			$default_element_ids = array(
				'productcategory-nosto-2',
			);
			$element_ids         = apply_filters( 'wpecnt_add_category_page_bottom_elements', $default_element_ids );
			if ( is_array( $element_ids ) && ! empty( $element_ids ) ) {
				$this->render( 'nosto-elements', array( 'element_ids' => $element_ids ) );
			}
		}
	}

	/**
	 * Hook callback function for outputting the Nosto elements at the bottom of the shopping cart page.
	 *
	 * @since 1.0.0
	 */
	public function add_cart_page_bottom_elements() {
		$default_element_ids = array(
			'cartpage-nosto-1',
			'cartpage-nosto-2',
			'cartpage-nosto-3',
		);
		$element_ids         = apply_filters( 'wpecnt_add_cart_page_bottom_elements', $default_element_ids );
		if ( is_array( $element_ids ) && ! empty( $element_ids ) ) {
			$this->render( 'nosto-elements', array( 'element_ids' => $element_ids ) );
		}
	}

	/**
	 * Hook callback function for outputting the Nosto elements at the top of the search result page.
	 *
	 * @since 1.0.0
	 */
	public function add_search_page_top_elements() {
		$default_element_ids = array(
			'searchpage-nosto-1',
		);
		$element_ids         = apply_filters( 'wpecnt_add_search_page_top_elements', $default_element_ids );
		if ( is_array( $element_ids ) && ! empty( $element_ids ) ) {
			$this->render( 'nosto-elements', array( 'element_ids' => $element_ids ) );
		}
	}

	/**
	 * Hook callback function for outputting the Nosto elements at the bottom of the search result page.
	 *
	 * @since 1.0.0
	 */
	public function add_search_page_bottom_elements() {
		$default_element_ids = array(
			'searchpage-nosto-2',
		);
		$element_ids         = apply_filters( 'wpecnt_add_search_page_bottom_elements', $default_element_ids );
		if ( is_array( $element_ids ) && ! empty( $element_ids ) ) {
			$this->render( 'nosto-elements', array( 'element_ids' => $element_ids ) );
		}
	}

	/**
	 * Hook callback function for outputting the Nosto elements at the top of all pages.
	 *
	 * @since 1.0.0
	 */
	public function add_page_top_elements() {
		$default_element_ids = array(
			'pagetemplate-nosto-1',
		);
		$element_ids         = apply_filters( 'wpecnt_add_page_top_elements', $default_element_ids );
		if ( is_array( $element_ids ) && ! empty( $element_ids ) ) {
			$this->render( 'nosto-elements', array( 'element_ids' => $element_ids ) );
		}
	}

	/**
	 * Hook callback function for outputting the Nosto elements at the bottom of all pages.
	 *
	 * @since 1.0.0
	 */
	public function add_page_bottom_elements() {
		$default_element_ids = array(
			'pagetemplate-nosto-2',
		);
		$element_ids         = apply_filters( 'wpecnt_add_page_bottom_elements', $default_element_ids );
		if ( is_array( $element_ids ) && ! empty( $element_ids ) ) {
			$this->render( 'nosto-elements', array( 'element_ids' => $element_ids ) );
		}
	}

	/**
	 * Registers widget for showing Nosto elements in the shop sidebars.
	 *
	 * @since 1.0.0
	 */
	public function register_widgets() {
		$this->load_class( 'WPEC_Nosto_Element_Widget' );
		register_widget( 'WPEC_Nosto_Element_Widget' );
	}

	/**
	 * Checks if we are to use the default Nosto elements or not.
	 *
	 * Note that this setting does not affect the sidebar widget element
	 * or the Top Sellers page element.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	protected function use_default_elements() {
		return (int) get_option( 'nosto_tagging_use_default_elements', 1 );
	}

	/**
	 * Get parent post for given post post id if it has one.
	 *
	 * This function is used to get the base product post for a product variation.
	 *
	 * @since 1.0.0
	 * @param int    $post_id The post id to find the parent post for
	 * @param string $type    The type of the parent post
	 * @return WP_Post|null
	 */
	protected function get_parent_post( $post_id, $type = 'wpsc-product' ) {
		$parent_post_id = (int) get_post_field( 'post_parent', $post_id );
		if ( 0 !== $parent_post_id ) {
			$parent_post = get_post( $parent_post_id );
			if ( $parent_post instanceof WP_Post && $parent_post->post_type === $type ) {
				return $parent_post;
			}
		}

		return null;
	}

	/**
	 * Get customer data for tagging for the WP_User object.
	 *
	 * @since 1.0.0
	 * @param WP_User $user The user for which to get the data
	 * @return array
	 */
	protected function get_customer_data( $user ) {
		$customer = array();

		if ( $user instanceof WP_User ) {
			$customer['first_name'] = $user->user_firstname;
			$customer['last_name']  = ! empty( $user->user_lastname ) ? $user->user_lastname : $user->user_login;
			$customer['email']      = $user->user_email;
		}

		return $customer;
	}

	/**
	 * Formats price into Nosto format, e.g. 1000.99.
	 *
	 * @since 1.0.0
	 * @param string|int|float $price The price to format
	 * @return string
	 */
	protected function format_price( $price ) {
		return number_format( (float) $price, 2, '.', '' );
	}

	/**
	 * Builds a tagging string of the given category including all its parent categories.
	 *
	 * @since 1.0.0
	 * @param object $category_term The term object to build the category path string from
	 * @return string
	 */
	protected function build_category_string( $category_term ) {
		$category_path = '';

		if ( is_object( $category_term ) && ! empty( $category_term->term_id ) ) {
			$category_terms   = $this->get_parent_terms( $category_term );
			$category_terms[] = $category_term;

			$category_term_names = array();
			foreach ( $category_terms as $category_term ) {
				$category_term_names[] = $category_term->name;
			}

			if ( ! empty( $category_term_names ) ) {
				$category_path = DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $category_term_names );
			}
		}

		return $category_path;
	}

	/**
	 * Get a list of all parent terms for given term.
	 *
	 * The list is sorted starting from the most distant parent.
	 *
	 * @since 1.0.0
	 * @param object $term     The term object to find parent terms for
	 * @param string $taxonomy The taxonomy type for the terms
	 * @return array
	 */
	protected function get_parent_terms( $term, $taxonomy = 'wpsc_product_category' ) {
		if ( empty( $term->parent ) ) {
			return array();
		}

		$parent = get_term( $term->parent, $taxonomy );

		if ( is_wp_error( $parent ) ) {
			return array();
		}

		$parents = array( $parent );

		if ( $parent->parent && ( $parent->parent !== $parent->term_id ) ) {
			$parents = array_merge( $parents, $this->get_parent_terms( $parent, $taxonomy ) );
		}

		return array_reverse( $parents );
	}

	/**
	 * Gets the current currency ISO code.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function get_currency_iso_code() {
		/** @var $wpdb wpdb */
		global $wpdb;

		$type = get_option( 'currency_type' );
		$data = wp_cache_get( $type, 'wpsc_currency_iso_code' );

		if ( ! $data ) {
			$query = "SELECT `code`
					  FROM `" . WPSC_TABLE_CURRENCY_LIST . "`
					  WHERE `id` = %d
					  LIMIT 1";
			$data  = $wpdb->get_row( $wpdb->prepare( $query, $type ), ARRAY_A );
			wp_cache_set( $type, $data, 'wpsc_currency_iso_code' );
		}

		if ( isset( $data['code'] ) ) {
			return $data['code'];
		}

		return '';
	}

	/**
	 * Checks plugin dependencies.
	 *
	 * Mainly that the WP and WPSC versions are equal to or greater than
	 * the defined minimums.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	protected function check_dependencies() {
		global $wp_version;

		$title = sprintf( __( 'WP e-Commerce Nosto Tagging %s not compatible.' ), self::VERSION );
		$args  = array(
			'back_link' => true,
		);

		if ( version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			deactivate_plugins( $this->plugin_name );

			$msg = __( 'Looks like you\'re running an older version of WordPress, you need to be running at least
					WordPress %1$s to use WP e-Commerce Nosto Tagging %2$s.' );

			wp_die( sprintf( $msg, self::MIN_WP_VERSION, self::VERSION ), $title, $args );
			return false;
		}

		if ( ! defined( 'WPSC_VERSION' ) ) {
			deactivate_plugins( $this->plugin_name );

			$msg = __( 'Looks like you\'re not running any version of WP e-Commerce, you need to be running at least
					WP e-Commerce %1$s to use WP e-Commerce Nosto Tagging %2$s.' );

			wp_die( sprintf( $msg, self::MIN_WPSC_VERSION, self::VERSION ), $title, $args );
			return false;
		} else if ( version_compare( WPSC_VERSION, self::MIN_WPSC_VERSION, '<' ) ) {
			deactivate_plugins( $this->plugin_name );

			$msg = __( 'Looks like you\'re running an older version of WP e-Commerce, you need to be running at least
					WP e-Commerce %1$s to use WP e-Commerce Nosto Tagging %2$s.' );

			wp_die( sprintf( $msg, self::MIN_WPSC_VERSION, self::VERSION ), $title, $args );
			return false;
		}

		return true;
	}

	/**
	 * Gets the lowest price of a product's variations.
	 *
	 * @since 1.0.2
	 * @param int $product_id Product ID
	 * @return float
	 */
	protected function get_lowest_product_variation_price( $product_id ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		static $price_cache = array();

		if ( isset( $price_cache[$product_id] ) ) {
			$results = $price_cache[$product_id];
		} else {
			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT pm.meta_value AS price, pm2.meta_value AS special_price
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.id AND pm.meta_key = '_wpsc_price'
				INNER JOIN {$wpdb->postmeta} AS pm2 ON pm2.post_id = p.id AND pm2.meta_key = '_wpsc_special_price'
				WHERE p.post_type = 'wpsc-product'
				AND p.post_parent = %d
			", $product_id ) );
			$price_cache[$product_id] = $results;
		}

		$prices = array();

		foreach ( $results as $row ) {
			$price         = (float) $row->price;
			$special_price = (float) $row->special_price;
			if ( $special_price != 0 && $special_price < $price ) {
				$prices[] = $special_price;
			} else {
				$prices[] = $price;
			}
		}

		sort( $prices );
		if ( empty( $prices ) ) {
			$prices[] = 0;
		}

		return apply_filters( 'wpsc_do_convert_price', $prices[0], $product_id );
	}
}

add_action( 'plugins_loaded', array( WPEC_Nosto_Tagging::get_instance(), 'init' ) );
