<?php
/**
 * CartManager Class
 * 
 */
class CartManager
{
    private $db;
    
    /**
     * Constructor - initialize with database connection
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Get current cart items
     * 
     * @return array The cart items
     */
    public function getCart()
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        return $_SESSION['cart'];
    }
    
    /**
     * Add an item to the cart
     * 
     * @param array $item The item to add
     * @return bool Success status
     */
    public function addItem($item)
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if product exists
        try {
            $stmt = $this->db->prepare("SELECT id, name, price, stock, image FROM products WHERE id = ? AND active = 1 AND stock > 0");
            $stmt->execute([$item['id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                return false; // Product not found or out of stock
            }
            
            // Find if product already exists in cart
            $existingItemIndex = $this->findItemIndex($item['id']);
            
            if ($existingItemIndex !== false) {
                // Update quantity
                $_SESSION['cart'][$existingItemIndex]['quantity']++;
            } else {
                // Add new item to cart with verified data from database
                $_SESSION['cart'][] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'quantity' => 1,
                    'image' => $product['image'] ?: 'images/product-placeholder.jpg'
                ];
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error adding item to cart: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update item quantity
     * 
     * @param int $itemId The item ID
     * @param int $quantity New quantity
     * @return bool Success status
     */
    public function updateQuantity($itemId, $quantity)
    {
        $quantity = max(1, (int)$quantity); // Ensure quantity is at least 1
        
        $index = $this->findItemIndex($itemId);
        if ($index !== false) {
            $_SESSION['cart'][$index]['quantity'] = $quantity;
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove item from cart
     * 
     * @param int $itemId The item ID to remove
     * @return bool Success status
     */
    public function removeItem($itemId)
    {
        $index = $this->findItemIndex($itemId);
        if ($index !== false) {
            array_splice($_SESSION['cart'], $index, 1);
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate cart totals
     * 
     * @return array Cart totals including subtotal, tax, shipping, total
     */
    public function calculateTotals()
    {
        $subtotal = 0;
        $itemCount = 0;
        
        foreach ($this->getCart() as $item) {
            $subtotal += $item['price'] * $item['quantity'];
            $itemCount += $item['quantity'];
        }
        
        // Tax and shipping could come from configuration
        $taxRate = 0.07; // 7%
        $tax = $subtotal * $taxRate;
        $shipping = 0; // Free shipping
        
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $subtotal + $tax + $shipping,
            'item_count' => $itemCount
        ];
    }
    
    /**
     * Clear the cart
     * 
     * @return void
     */
    public function clearCart()
    {
        $_SESSION['cart'] = [];
    }
    
    /**
     * Find item index in cart by ID
     * 
     * @param int $itemId Item ID to find
     * @return int|bool The index if found, false otherwise
     */
    private function findItemIndex($itemId)
    {
        foreach ($_SESSION['cart'] as $index => $item) {
            if ($item['id'] == $itemId) {
                return $index;
            }
        }
        
        return false;
    }
}
