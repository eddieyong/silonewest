<?php
session_start();
require_once 'functions.php';

// Check if the user is logged in and has the 'Admin' role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$message = '';
$messageType = '';
$supplier = null;

// Get supplier ID from URL
if (!isset($_GET['id'])) {
    header("Location: view-suppliers.php");
    exit();
}

$supplier_id = $_GET['id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = trim($_POST['company_name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);

    // Get old supplier data for comparison
    $stmt = $mysqli->prepare("SELECT * FROM supplier WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $old_supplier = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Update supplier
    $stmt = $mysqli->prepare("UPDATE supplier SET company_name = ?, contact_person = ?, email = ?, phone = ?, address = ?, city = ?, postcode = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", 
        $company_name,
        $contact_person,
        $email,
        $phone,
        $address,
        $city,
        $postcode,
        $supplier_id
    );

    if ($stmt->execute()) {
        // Log the activity with details of what changed
        $changes = array();
        if ($old_supplier['company_name'] !== $company_name) $changes[] = "company name to '{$company_name}'";
        if ($old_supplier['contact_person'] !== $contact_person) $changes[] = "contact person to '{$contact_person}'";
        if ($old_supplier['email'] !== $email) $changes[] = "email to '{$email}'";
        if ($old_supplier['phone'] !== $phone) $changes[] = "phone to '{$phone}'";
        
        $description = "Updated supplier: {$company_name}";
        if (!empty($changes)) {
            $description .= " (Changed " . implode(", ", $changes) . ")";
        }
        
        logActivity($mysqli, 'supplier', $description);
        $_SESSION['success_msg'] = "Supplier updated successfully!";
        header("Location: view-suppliers.php");
        exit();
    } else {
        $message = "Error updating supplier: " . $mysqli->error;
        $messageType = "error";
    }
    $stmt->close();
}

// Fetch supplier data
$stmt = $mysqli->prepare("SELECT * FROM supplier WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();
$stmt->close();

if (!$supplier) {
    header("Location: view-suppliers.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier - SILO</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .container {
            padding: 20px 30px;
            background: #f8f9fa;
            min-height: calc(100vh - 72px);
        }

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .page-title {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }

        .supplier-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #5c1f00;
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .btn-submit {
            background: #5c1f00;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background: #7a2900;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #333;
            padding: 12px 24px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }

        .btn-cancel:hover {
            background: #e9ecef;
        }

        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
    </style>
</head>
<body>
    <?php include 'admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Edit Supplier</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="supplier-form">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $supplier_id); ?>">
                <div class="form-group">
                    <label for="company_name">Company Name *</label>
                    <input type="text" id="company_name" name="company_name" required 
                           value="<?php echo htmlspecialchars($supplier['company_name']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_person">Contact Person</label>
                        <input type="text" id="contact_person" name="contact_person"
                               value="<?php echo htmlspecialchars($supplier['contact_person']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($supplier['email']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($supplier['phone']); ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($supplier['address']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city"
                               value="<?php echo htmlspecialchars($supplier['city']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="postcode">Postcode</label>
                        <input type="text" id="postcode" name="postcode"
                               value="<?php echo htmlspecialchars($supplier['postcode']); ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="view-suppliers.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $mysqli->close(); ?> 