<?php
include '../includes/auth.php';
include '../includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Create customer record if new customer
        $customerId = $_POST['customer_id'];
        if ($_POST['customer_type'] == 'new') {
            $stmt = $pdo->prepare("INSERT INTO customer (Name, ContactInfo) VALUES (?, ?)");
            $stmt->execute([$_POST['customer_name'], $_POST['customer_contact']]);
            $customerId = $pdo->lastInsertId();
        }
        
        // Create sales transaction
        $stmt = $pdo->prepare("INSERT INTO salestransaction (CustomerID, EmployeeID, Date, TotalAmount, PaymentMethod) 
                              VALUES (?, ?, CURDATE(), ?, ?)");
        $employeeId = 1; // In a real system, this would be the logged-in employee
        $stmt->execute([$customerId, $employeeId, $_POST['total_amount'], $_POST['payment_method']]);
        $transactionId = $pdo->lastInsertId();
        
        // Add sales details and update inventory
        foreach ($_POST['products'] as $product) {
            $stmt = $pdo->prepare("INSERT INTO salesdetails (TransactionID, ProductID, Quantity, Price) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$transactionId, $product['id'], $product['quantity'], $product['price']]);
            
            // Update inventory
            $stmt = $pdo->prepare("UPDATE inventory SET QuantityAvailable = QuantityAvailable - ? WHERE ProductID = ?");
            $stmt->execute([$product['quantity'], $product['id']]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Sales transaction recorded successfully!";
        header("Location: view.php?id=$transactionId");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error recording transaction: " . $e->getMessage();
    }
}

// Get products for dropdown
$products = $pdo->query("SELECT p.ProductID, p.Name, p.Price, p.Type, p.Size, i.QuantityAvailable 
                        FROM product p 
                        JOIN inventory i ON p.ProductID = i.ProductID 
                        WHERE i.QuantityAvailable > 0")->fetchAll();

// Get existing customers
$customers = $pdo->query("SELECT * FROM customer")->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">New Sales Transaction</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Sales</a></li>
        <li class="breadcrumb-item active">New Transaction</li>
    </ol>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form id="salesForm" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="customer_type" id="existingCustomer" value="existing" checked>
                            <label class="form-check-label" for="existingCustomer">Existing Customer</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="customer_type" id="newCustomer" value="new">
                            <label class="form-check-label" for="newCustomer">New Customer</label>
                        </div>
                    </div>
                </div>
                
                <div id="existingCustomerFields">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Select Customer</label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['CustomerID']; ?>">
                                    <?php echo htmlspecialchars($customer['Name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="newCustomerFields" style="display: none;">
                    <div class="mb-3">
                        <label for="customer_name" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name">
                    </div>
                    <div class="mb-3">
                        <label for="customer_contact" class="form-label">Contact Info</label>
                        <input type="text" class="form-control" id="customer_contact" name="customer_contact">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select class="form-select" id="payment_method" name="payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                    </select>
                </div>
                
                <hr>
                
                <h5>Products</h5>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            <!-- Products will be added here dynamically -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                <td><span id="grandTotal">₱0.00</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-8">
                        <select class="form-select" id="productSelect">
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['ProductID']; ?>" 
                                        data-name="<?php echo htmlspecialchars($product['Name']); ?>"
                                        data-price="<?php echo $product['Price']; ?>"
                                        data-stock="<?php echo $product['QuantityAvailable']; ?>">
                                    <?php echo htmlspecialchars($product['Name']); ?> - 
                                    <?php echo $product['Type']; ?> (<?php echo $product['Size']; ?>) - 
                                    ₱<?php echo number_format($product['Price'], 2); ?> - 
                                    Stock: <?php echo $product['QuantityAvailable']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" id="productQuantity" min="1" value="1">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" id="addProduct">Add</button>
                    </div>
                </div>
                
                <input type="hidden" name="total_amount" id="totalAmount" value="0">
                
                <button type="submit" class="btn btn-success">Complete Transaction</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle between existing and new customer
    document.querySelectorAll('input[name="customer_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('existingCustomerFields').style.display = 
                this.value === 'existing' ? 'block' : 'none';
            document.getElementById('newCustomerFields').style.display = 
                this.value === 'new' ? 'block' : 'none';
        });
    });
    
    // Add product to the table
    document.getElementById('addProduct').addEventListener('click', function() {
        const productSelect = document.getElementById('productSelect');
        const productId = productSelect.value;
        const productName = productSelect.selectedOptions[0].dataset.name;
        const productPrice = parseFloat(productSelect.selectedOptions[0].dataset.price);
        const productStock = parseInt(productSelect.selectedOptions[0].dataset.stock);
        const quantityInput = document.getElementById('productQuantity');
        let quantity = parseInt(quantityInput.value);
        
        if (!productId) {
            alert('Please select a product');
            return;
        }
        
        if (isNaN(quantity)) {
            alert('Please enter a valid quantity');
            quantityInput.focus();
            return;
        }
        
        if (quantity < 1) {
            alert('Quantity must be at least 1');
            quantityInput.focus();
            return;
        }
        
        if (quantity > productStock) {
            alert(`Only ${productStock} available in stock`);
            quantityInput.focus();
            return;
        }
        
        // Check if product already exists in table
        const existingRow = document.querySelector(`#productTable tr[data-product-id="${productId}"]`);
        if (existingRow) {
            const existingQty = parseInt(existingRow.querySelector('.product-quantity').value);
            const newQty = existingQty + quantity;
            
            if (newQty > productStock) {
                alert(`Total quantity (${newQty}) exceeds available stock (${productStock})`);
                return;
            }
            
            existingRow.querySelector('.product-quantity').value = newQty;
            existingRow.querySelector('.product-total').textContent = `₱${(newQty * productPrice).toFixed(2)}`;
        } else {
            // Add new row
            const row = document.createElement('tr');
            row.dataset.productId = productId;
            row.innerHTML = `
                <td>${productName}</td>
                <td>₱${productPrice.toFixed(2)}</td>
                <td><input type="number" class="form-control product-quantity" name="products[${productId}][quantity]" value="${quantity}" min="1" max="${productStock}"></td>
                <td class="product-total">₱${(quantity * productPrice).toFixed(2)}</td>
                <td><button type="button" class="btn btn-danger btn-sm remove-product">Remove</button></td>
            `;
            
            // Add hidden inputs for form submission
            const hiddenInputs = `
                <input type="hidden" name="products[${productId}][id]" value="${productId}">
                <input type="hidden" name="products[${productId}][price]" value="${productPrice}">
            `;
            row.insertAdjacentHTML('beforeend', hiddenInputs);
            
            document.getElementById('productTable').appendChild(row);
        }
        
        // Update grand total
        updateGrandTotal();
        
        // Reset inputs
        productSelect.value = '';
        quantityInput.value = 1;
    });
    
    // Remove product from table
    document.getElementById('productTable').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-product')) {
            e.target.closest('tr').remove();
            updateGrandTotal();
        }
    });
    
    // Update quantity and total when changed
    document.getElementById('productTable').addEventListener('change', function(e) {
        if (e.target.classList.contains('product-quantity')) {
            const row = e.target.closest('tr');
            const price = parseFloat(row.querySelector('input[name*="[price]"]').value);
            const quantity = parseInt(e.target.value);
            
            if (isNaN(quantity)) {
                e.target.value = 1;
                row.querySelector('.product-total').textContent = `₱${price.toFixed(2)}`;
            } else {
                row.querySelector('.product-total').textContent = `₱${(quantity * price).toFixed(2)}`;
            }
            
            updateGrandTotal();
        }
    });
    
    // Calculate grand total
    function updateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.product-total').forEach(cell => {
            total += parseFloat(cell.textContent.replace('₱', ''));
        });
        
        document.getElementById('grandTotal').textContent = `₱${total.toFixed(2)}`;
        document.getElementById('totalAmount').value = total;
    }
});
</script>

<?php include '../includes/footer.php'; ?>