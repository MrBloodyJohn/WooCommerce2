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
    
    protected $table = 'woocommerce_dotpay_oneclick';
    
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
    public function card_add($orderId, $userId, $cardTitle) {
        global $wpdb;
        
        $result = 0;
        
        if($this->getStatus()) {
            $count = 100;
            do {
                $cardHash = $this->generateCardHash();
                $test = $wpdb->insert(
                    "{$wpdb->prefix}{$this->table}"
                    , array( 
                        'oneclick_order' => "{$orderId}"
                        ,'oneclick_user' => "{$userId}"
                        ,'oneclick_card_title' => "{$cardTitle}" 
                        ,'oneclick_card_hash' => "{$cardHash}" 
                    )
                    , array(
                        '%d'
                        ,'%d'
                        ,'%s'
                        ,'%s'
                    )
                );
                
                if(false !== $test) {
                    $result = $cardHash;
                    break;
                }
                
                $count--;
            } while($count);
        }
            
        return $result;
    }
    
    private function generateCardHash() {
        $microtime = '' . microtime(true);
        $md5 = md5($microtime);
        
        $mtRand = mt_rand(0, 11);
        
        $md5Substr = substr($md5, $mtRand, 21);
        
        $a = substr($md5Substr, 0, 6);
        $b = substr($md5Substr, 6, 5);
        $c = substr($md5Substr, 11, 6);
        $d = substr($md5Substr, 17, 4);
        
        return "{$a}-{$b}-{$c}-{$d}";
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
     * 
     */
    public function card_getHashByOrderId($user, $orderId) {
        global $wpdb;
        
        $sql = <<<END
            SELECT *
            FROM {$wpdb->prefix}{$this->table}
            WHERE
                oneclick_user = '{$user}'
                AND
                oneclick_order = '{$orderId}'
            LIMIT 1
            ;

END;
            
            $results = null;
            if($this->getStatus()) {
                $row = $wpdb->get_row($sql, ARRAY_A);
                $results = isset($row['oneclick_card_hash']) ? $row['oneclick_card_hash'] : null;
            }
            
            return $results;
    }
}
