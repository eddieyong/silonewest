<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../mainlogin.html");
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$username = $_SESSION['username'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pickup_address = trim($_POST['pickup_address']);
    $delivery_address = trim($_POST['delivery_address']);
    $pickup_date = trim($_POST['pickup_date']);
    $pickup_contact = trim($_POST['pickup_contact']);
    $delivery_contact = trim($_POST['delivery_contact']);

    // Start transaction
    $mysqli->begin_transaction();

    try {
        // Insert the main order
        $stmt = $mysqli->prepare("INSERT INTO orders (customer_username, pickup_address, delivery_address, pickup_date, pickup_contact, delivery_contact) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $pickup_address, $delivery_address, $pickup_date, $pickup_contact, $delivery_contact);
        $stmt->execute();
        $order_id = $mysqli->insert_id;

        // Insert order items
        $items_stmt = $mysqli->prepare("INSERT INTO order_items (order_id, goods_type, weight, quantity) VALUES (?, ?, ?, ?)");
        
        // Process each item
        foreach ($_POST['items'] as $item) {
            if (!empty($item['goods_type']) && !empty($item['weight']) && !empty($item['quantity'])) {
                $items_stmt->bind_param("isdi", 
                    $order_id,
                    $item['goods_type'],
                    $item['weight'],
                    $item['quantity']
                );
                $items_stmt->execute();
            }
        }

        $mysqli->commit();
        $success_message = "Order placed successfully! Our team will review your request.";
    } catch (Exception $e) {
        $mysqli->rollback();
        $error_message = "Error placing order: " . $e->getMessage();
    }
}

include 'user-header.php';
?>

<style>
    .container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
    }

    .page-header {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        text-align: center;
    }

    .page-title {
        color: #5c1f00;
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }

    .page-description {
        color: #666;
        font-size: 1.1rem;
    }

    .order-form {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }

    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .section-title {
        color: #5c1f00;
        font-size: 1.2rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #333;
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #5c1f00;
        outline: none;
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .alert {
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .btn-submit {
        background: #5c1f00;
        color: white;
        padding: 1rem 2rem;
        border: none;
        border-radius: 5px;
        font-size: 1.1rem;
        cursor: pointer;
        width: 100%;
        transition: background-color 0.3s;
    }

    .btn-submit:hover {
        background: #7a2900;
    }

    .form-note {
        font-size: 0.9rem;
        color: #666;
        margin-top: 0.3rem;
    }

    .required-field::after {
        content: " *";
        color: #dc3545;
    }

    .items-container {
        margin-bottom: 1.5rem;
    }

    .item-row {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
        position: relative;
    }

    .item-row .remove-item {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        font-size: 1.2rem;
    }

    .item-row .remove-item:hover {
        color: #c82333;
    }

    .add-item-btn {
        background: #5c1f00;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .add-item-btn:hover {
        background: #7a2900;
    }

    .item-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 1rem;
    }

    @media (max-width: 768px) {
        .item-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Place Transportation Order</h1>
        <p class="page-description">Fill in the details below to request a transportation service</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form class="order-form" method="POST" action="" id="orderForm">
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-map-marker-alt"></i> Pickup Information
            </h2>
            <div class="form-group">
                <label class="required-field">Pickup Address</label>
                <textarea class="form-control" name="pickup_address" required placeholder="Enter complete pickup address"></textarea>
            </div>
            <div class="form-group">
                <label class="required-field">Pickup Contact Number</label>
                <input type="tel" class="form-control" name="pickup_contact" required placeholder="Enter contact number for pickup location">
            </div>
            <div class="form-group">
                <label class="required-field">Preferred Pickup Date</label>
                <input type="date" class="form-control" name="pickup_date" required min="<?php echo date('Y-m-d'); ?>">
                <p class="form-note">Please select a date at least 24 hours from now</p>
            </div>
        </div>

        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-truck"></i> Delivery Information
            </h2>
            <div class="form-group">
                <label class="required-field">Delivery Address</label>
                <textarea class="form-control" name="delivery_address" required placeholder="Enter complete delivery address"></textarea>
            </div>
            <div class="form-group">
                <label class="required-field">Delivery Contact Number</label>
                <input type="tel" class="form-control" name="delivery_contact" required placeholder="Enter contact number for delivery location">
            </div>
        </div>

        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-box"></i> Goods Information
            </h2>
            <div class="items-container" id="itemsContainer">
                <!-- Items will be added here -->
            </div>
            <button type="button" class="add-item-btn" onclick="addItem()">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fas fa-paper-plane"></i> Submit Order Request
        </button>
    </form>
</div>

<script>
function addItem() {
    const container = document.getElementById('itemsContainer');
    const itemIndex = container.children.length;
    
    const itemRow = document.createElement('div');
    itemRow.className = 'item-row';
    itemRow.innerHTML = `
        <button type="button" class="remove-item" onclick="removeItem(this)">
            <i class="fas fa-times"></i>
        </button>
        <div class="item-grid">
            <div class="form-group">
                <label class="required-field">Type of Goods</label>
                <input type="text" class="form-control" name="items[${itemIndex}][goods_type]" required 
                    placeholder="Describe the type of goods">
            </div>
            <div class="form-group">
                <label class="required-field">Weight (KG)</label>
                <input type="number" class="form-control" name="items[${itemIndex}][weight]" required 
                    step="0.01" min="0" placeholder="Weight in KG">
            </div>
            <div class="form-group">
                <label class="required-field">Quantity</label>
                <input type="number" class="form-control" name="items[${itemIndex}][quantity]" required 
                    min="1" placeholder="Number of items">
            </div>
        </div>
    `;
    
    container.appendChild(itemRow);
}

function removeItem(button) {
    button.closest('.item-row').remove();
}

// Add first item row by default
document.addEventListener('DOMContentLoaded', function() {
    addItem();
});

// Form validation
document.getElementById('orderForm').onsubmit = function(e) {
    const items = document.querySelectorAll('.item-row');
    if (items.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to your order.');
        return false;
    }
    return true;
};
</script>

<?php $mysqli->close(); ?> 