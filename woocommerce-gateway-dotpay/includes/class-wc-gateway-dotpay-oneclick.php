<?php
/**
 * Oneclick
 *
 * The dotpay oneclick class handles cards data.
 *
 *
 * @property    string $customer_user_agent Customer User agent.
 */
class WC_Gateway_Dotpay_Oneclick {
    
    protected $table = 'dotpay_oneclick';
    
    protected $status = false;
    
    public function __construct() {
        $this->create_table();
        $this->status = $this->check_table();
    }
    
    /**
     * 
     * @global type $wpdb
     */
    protected function create_table() {
        global $wpdb;
        
        $sqlCreateTable = <<<END
            CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$this->table}` (
                `oneclick_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `oneclick_order` bigint(20) NOT NULL,
                `oneclick_user` bigint(20) NOT NULL,
                `oneclick_card_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `oneclick_card_hash` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `oneclick_card_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`oneclick_id`),
                UNIQUE KEY `oneclick_card_hash` (`oneclick_card_hash`),
                UNIQUE KEY `oneclick_order` (`oneclick_order`),
                UNIQUE KEY `oneclick_card_id` (`oneclick_card_id`),
                KEY `oneclick_user` (`oneclick_user`),
                KEY `oneclick_card_title` (`oneclick_card_title`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

END;
            
        try {
            $wpdb->query($sqlCreateTable);
        } catch (Exception $exc) {
           /**
            * NOP
            */
        }
    }
    
    /**
     * 
     * @global type $wpdb
     * @return boolean
     */
    protected function check_table() {
        global $wpdb;
        
        $ok = false;
        
        $results = $wpdb->get_results('SHOW TABLES;', ARRAY_N);
        foreach ($results as $tables) {
            foreach ($tables as $table) {
                if($table === "{$wpdb->prefix}{$this->table}") {
                   $ok = true;
                   break 2;
                }
            }
        }
        
        return $ok;
    }
    
    /**
     * 
     * @return type
     */
    public function getStatus() {
        return $this->status;
    }
    
    /**
     * 
     */
    public function card_add() {
        
    }
    
    /**
     * 
     */
    public function card_register() {
        
    }
    
    /**
     * 
     */
    public function card_del() {
        
    }
    
    /**
     * 
     */
    public function card_list($user) {
        global $wpdb;
        
        $sql = <<<END
            SELECT *
            FROM {$wpdb->prefix}{$this->table}
            WHERE
                oneclick_user = '{$user}'
                AND
                oneclick_card_id IS NOT NULL
            ;

END;
            $results = array();
            if($this->getStatus()) {
                $results = $wpdb->get_results($sql, ARRAY_A);
            }
            
            return $results;
                
    }
    
    /**
     * 
     */
    public function card_getIdByCardHash($user, $cardHash) {
        global $wpdb;
        
        $sql = <<<END
            SELECT *
            FROM {$wpdb->prefix}{$this->table}
            WHERE
                oneclick_user = '{$user}'
                AND
                oneclick_card_hash = '{$cardHash}'
            LIMIT 1
            ;

END;
            
            $results = null;
            if($this->getStatus()) {
                $row = $wpdb->get_row($sql, ARRAY_A);
                $results = isset($row['oneclick_id']) ? $row['oneclick_id'] : null;
            }
            
            return $results;
    }
    
	/**
	 * Remove all line items (products, coupons, shipping, taxes) from the order.
	 *
	 * @param string $type Order item type. Default null.
	 */
//	public function remove_order_items( $type = null ) {
//		global $wpdb;
//
//		if ( ! empty( $type ) ) {
//			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_order_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_order_items items WHERE itemmeta.order_item_id = items.order_item_id AND items.order_id = %d AND items.order_item_type = %s", $this->id, $type ) );
//			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $this->id, $type ) );
//		} else {
//			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_order_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_order_items items WHERE itemmeta.order_item_id = items.order_item_id and items.order_id = %d", $this->id ) );
//			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $this->id ) );
//		}
//	}

	/**
	 * Get all item meta data in array format in the order it was saved. Does not group meta by key like get_item_meta().
	 *
	 * @param mixed $order_item_id
	 * @return array of objects
	 */
//	public function get_item_meta_array( $order_item_id ) {
//		global $wpdb;
//
//		// Get cache key - uses get_cache_prefix to invalidate when needed
//		$cache_key       = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'item_meta_array_' . $order_item_id;
//		$item_meta_array = wp_cache_get( $cache_key, 'orders' );
//
//		if ( false === $item_meta_array ) {
//			$item_meta_array = array();
//			$metadata        = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d ORDER BY meta_id", absint( $order_item_id ) ) );
//			foreach ( $metadata as $metadata_row ) {
//				$item_meta_array[ $metadata_row->meta_id ] = (object) array( 'key' => $metadata_row->meta_key, 'value' => $metadata_row->meta_value );
//			}
//			wp_cache_set( $cache_key, $item_meta_array, 'orders' );
//		}
//
//		return $item_meta_array ;
//	}

	/**
	 * Get the downloadable files for an item in this order.
	 *
	 * @param  array $item
	 * @return array
	 */
//	public function get_item_downloads( $item ) {
//		global $wpdb;
//
//		$product_id   = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];
//		$product      = wc_get_product( $product_id );
//		if ( ! $product ) {
//			/**
//			 * $product can be `false`. Example: checking an old order, when a product or variation has been deleted.
//			 * @see \WC_Product_Factory::get_product
//			 */
//			return array();
//		}
//		$download_ids = $wpdb->get_col( $wpdb->prepare("
//			SELECT download_id
//			FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
//			WHERE user_email = %s
//			AND order_key = %s
//			AND product_id = %s
//			ORDER BY permission_id
//		", $this->billing_email, $this->order_key, $product_id ) );
//
//		$files = array();
//
//		foreach ( $download_ids as $download_id ) {
//
//			if ( $product->has_file( $download_id ) ) {
//				$files[ $download_id ]                 = $product->get_file( $download_id );
//				$files[ $download_id ]['download_url'] = $this->get_download_url( $product_id, $download_id );
//			}
//		}
//
//		return apply_filters( 'woocommerce_get_item_downloads', $files, $item, $this );
//	}

}
