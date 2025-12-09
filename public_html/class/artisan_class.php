<?php
/**
 * Artisan Class
 * 
 * Handles all artisan-related database operations
 */

class artisan_class extends db_class {
    
    /**
     * Get all active artisans
     * 
     * @param int $limit Maximum number of artisans to return
     * @param int $offset Offset for pagination
     * @return array Array of artisan records
     */
    public function get_all_artisans($limit = 50, $offset = 0) {
        try {
            $sql = "SELECT * FROM artisans 
                    WHERE status = 'active' 
                    ORDER BY featured DESC, artisan_name ASC 
                    LIMIT ? OFFSET ?";
            
            $artisans = $this->fetchAll($sql, [$limit, $offset]);
            return $artisans ? $artisans : [];
        } catch (Exception $e) {
            error_log("Get all artisans error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get artisan by ID
     * 
     * @param int $artisan_id Artisan ID
     * @return array|false Artisan record or false
     */
    public function get_artisan_by_id($artisan_id) {
        try {
            $sql = "SELECT * FROM artisans WHERE artisan_id = ? AND status = 'active'";
            return $this->fetchRow($sql, [$artisan_id]);
        } catch (Exception $e) {
            error_log("Get artisan by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get artisan by customer ID
     * 
     * @param int $customer_id Customer ID
     * @return array|false Artisan record or false
     */
    public function get_artisan_by_customer_id($customer_id) {
        try {
            $sql = "SELECT * FROM artisans WHERE customer_id = ?";
            return $this->fetchRow($sql, [$customer_id]);
        } catch (Exception $e) {
            error_log("Get artisan by customer ID error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get featured artisans
     * 
     * @param int $limit Maximum number of featured artisans
     * @return array Array of featured artisan records
     */
    public function get_featured_artisans($limit = 6) {
        try {
            $sql = "SELECT * FROM artisans 
                    WHERE status = 'active' AND featured = 1 
                    ORDER BY artisan_name ASC 
                    LIMIT ?";
            
            $artisans = $this->fetchAll($sql, [$limit]);
            return $artisans ? $artisans : [];
        } catch (Exception $e) {
            error_log("Get featured artisans error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of active artisans
     * 
     * @return int Total count
     */
    public function get_total_artisans_count() {
        try {
            $sql = "SELECT COUNT(*) as total FROM artisans WHERE status = 'active'";
            $result = $this->fetchRow($sql);
            return isset($result['total']) ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            error_log("Get total artisans count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Search artisans by name or business name
     * 
     * @param string $search_term Search term
     * @param int $limit Maximum results
     * @return array Array of matching artisans
     */
    public function search_artisans($search_term, $limit = 20) {
        try {
            $search = '%' . $search_term . '%';
            $sql = "SELECT * FROM artisans 
                    WHERE status = 'active' 
                    AND (artisan_name LIKE ? OR business_name LIKE ? OR bio LIKE ?)
                    ORDER BY featured DESC, artisan_name ASC 
                    LIMIT ?";
            
            $artisans = $this->fetchAll($sql, [$search, $search, $search, $limit]);
            return $artisans ? $artisans : [];
        } catch (Exception $e) {
            error_log("Search artisans error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get artisans by location
     * 
     * @param string $city City name
     * @param string $country Country name
     * @return array Array of artisans in that location
     */
    public function get_artisans_by_location($city = null, $country = null) {
        try {
            $conditions = ["status = 'active'"];
            $params = [];
            
            if ($city) {
                $conditions[] = "city = ?";
                $params[] = $city;
            }
            
            if ($country) {
                $conditions[] = "country = ?";
                $params[] = $country;
            }
            
            $where = implode(' AND ', $conditions);
            $sql = "SELECT * FROM artisans WHERE $where ORDER BY featured DESC, artisan_name ASC";
            
            $artisans = $this->fetchAll($sql, $params);
            return $artisans ? $artisans : [];
        } catch (Exception $e) {
            error_log("Get artisans by location error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sync artisan data from customer table
     * This ensures artisans table stays updated when customer data changes
     * 
     * @param int $customer_id Customer ID
     * @return bool Success
     */
    public function sync_from_customer($customer_id) {
        try {
            // Get customer data
            $customer_sql = "SELECT * FROM customer WHERE customer_id = ? AND user_role = 3";
            $customer = $this->fetchRow($customer_sql, [$customer_id]);
            
            if (!$customer) {
                return false;
            }
            
            // Check if artisan record exists
            $existing = $this->get_artisan_by_customer_id($customer_id);
            
            if ($existing) {
                // Update existing record
                $sql = "UPDATE artisans SET
                        artisan_name = ?,
                        business_name = ?,
                        bio = ?,
                        profile_image = ?,
                        email = ?,
                        phone = ?,
                        city = ?,
                        country = ?,
                        updated_at = NOW()
                        WHERE customer_id = ?";
                
                $params = [
                    $customer['customer_name'],
                    $customer['business_name'] ?? $customer['customer_name'],
                    $customer['bio'] ?? null,
                    $customer['customer_image'] ?? null,
                    $customer['customer_email'],
                    $customer['customer_contact'],
                    $customer['customer_city'],
                    $customer['customer_country'],
                    $customer_id
                ];
            } else {
                // Insert new record
                $sql = "INSERT INTO artisans (
                        customer_id, artisan_name, business_name, bio, profile_image,
                        email, phone, city, country, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
                
                $params = [
                    $customer_id,
                    $customer['customer_name'],
                    $customer['business_name'] ?? $customer['customer_name'],
                    $customer['bio'] ?? null,
                    $customer['customer_image'] ?? null,
                    $customer['customer_email'],
                    $customer['customer_contact'],
                    $customer['customer_city'],
                    $customer['customer_country']
                ];
            }
            
            return $this->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log("Sync artisan from customer error: " . $e->getMessage());
            return false;
        }
    }
}

