<?php
session_start();

/**
 * Cart Handler class using OOP principles
 */
class CartHandler {
    private $db;
    private $cart;
    
    /**
     * Constructor - initialize database connection and cart
     */
    public function __construct() {
        // Initialize database connection
        try {
            $this->db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $this->cart = &$_SESSION['cart'];
    }
    
    /**
     * Add a product to cart
     * 
     * @param int $productId Product ID to add
     * @param int $quantity Quantity to add
     * @return array Response data
     */
    public function addProduct($productId, $quantity) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        
        if ($quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid quantity. Please specify a positive number.'
            ];
        }
        
        try {
            // Get product from database
            $stmt = $this->db->prepare("SELECT id, name, price, image FROM products WHERE id = ? AND active = 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Product not found or is not available.'
                ];
            }
            
            // If product exists in cart, update quantity
            if (isset($this->cart[$productId])) {
                $this->cart[$productId]['quantity'] += $quantity;
            } else {
                // Add new product to cart
                $this->cart[$productId] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'quantity' => $quantity
                ];
            }
            
            // Calculate cart totals
            $cartTotals = $this->calculateCartTotals();
            
            return [
                'success' => true,
                'message' => 'Product added to cart.',
                'itemQuantity' => $this->cart[$productId]['quantity'],
                'itemSubtotal' => $this->cart[$productId]['price'] * $this->cart[$productId]['quantity'],
                'itemsCount' => $cartTotals['itemsCount'],
                'subtotal' => $cartTotals['subtotal'],
                'tax' => $cartTotals['tax'],
                'total' => $cartTotals['total'],
                'cart' => array_values($this->cart)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update product quantity
     * 
     * @param int $productId Product ID to update
     * @param int $quantity New quantity
     * @return array Response data
     */
    public function updateQuantity($productId, $quantity) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        
        if (!isset($this->cart[$productId])) {
            return [
                'success' => false,
                'message' => 'Product not in cart.'
            ];
        }
        
        if ($quantity <= 0) {
            // If quantity is 0 or less, remove the item
            unset($this->cart[$productId]);
            $cartTotals = $this->calculateCartTotals();
            
            return [
                'success' => true,
                'message' => 'Product removed from cart.',
                'itemsCount' => $cartTotals['itemsCount'],
                'subtotal' => $cartTotals['subtotal'],
                'tax' => $cartTotals['tax'],
                'total' => $cartTotals['total'],
                'cart' => array_values($this->cart)
            ];
        }
        
        // Update quantity
        $this->cart[$productId]['quantity'] = $quantity;
        
        // Get updated totals
        $cartTotals = $this->calculateCartTotals();
        $itemSubtotal = $this->cart[$productId]['price'] * $quantity;
        
        return [
            'success' => true,
            'message' => 'Quantity updated successfully.',
            'itemQuantity' => $quantity,
            'itemSubtotal' => $itemSubtotal,
            'itemsCount' => $cartTotals['itemsCount'],
            'subtotal' => $cartTotals['subtotal'],
            'tax' => $cartTotals['tax'],
            'total' => $cartTotals['total'],
            'cart' => array_values($this->cart)
        ];
    }
    
    /**
     * Remove product from cart
     * 
     * @param int $productId Product ID to remove
     * @return array Response data
     */
    public function removeProduct($productId) {
        $productId = (int)$productId;
        
        if (!isset($this->cart[$productId])) {
            return [
                'success' => false,
                'message' => 'Product not in cart.'
            ];
        }
        
        // Remove item from cart
        unset($this->cart[$productId]);
        
        // Calculate cart totals
        $cartTotals = $this->calculateCartTotals();
        
        return [
            'success' => true,
            'message' => 'Product removed from cart.',
            'itemsCount' => $cartTotals['itemsCount'],
            'subtotal' => $cartTotals['subtotal'],
            'tax' => $cartTotals['tax'],
            'total' => $cartTotals['total'],
            'cart' => array_values($this->cart)
        ];
    }
    
    /**
     * Apply promo code to cart
     * 
     * @param string $code Promo code to apply
     * @param float $discount Discount amount
     * @return array Response data
     */
    public function applyPromo($code, $discount) {
        $_SESSION['promo_code'] = [
            'code' => $code,
            'discount' => (float)$discount
        ];
        
        return [
            'success' => true,
            'message' => 'Promo code applied successfully.'
        ];
    }
    
    /**
     * Calculate cart totals
     * 
     * @return array Cart total values
     */
    private function calculateCartTotals() {
        $subtotal = 0;
        $itemsCount = 0;
        
        foreach ($this->cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
            $itemsCount += $item['quantity'];
        }
        
        // Apply shipping and tax
        $shipping = 5.00;
        $taxRate = 0.08;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $shipping + $tax;
        
        // Apply discount if exists
        if (isset($_SESSION['promo_code'])) {
            $total -= $_SESSION['promo_code']['discount'];
        }
        
        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'itemsCount' => $itemsCount
        ];
    }
}

// Handle cart actions
try {
    $handler = new CartHandler();
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
                    $response = $handler->addProduct($_POST['product_id'], $_POST['quantity']);
                }
                break;
                
            case 'update':
                if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
                    $response = $handler->updateQuantity($_POST['product_id'], $_POST['quantity']);
                }
                break;
                
            case 'remove':
                if (isset($_POST['product_id'])) {
                    $response = $handler->removeProduct($_POST['product_id']);
                }
                break;
                
            case 'apply_promo':
                if (isset($_POST['code']) && isset($_POST['discount'])) {
                    $response = $handler->applyPromo($_POST['code'], $_POST['discount']);
                }
                break;
        }
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
