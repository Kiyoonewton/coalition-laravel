@extends('layouts.app')

@section('title', 'Products - Inventory Manager')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold">Product Inventory</h2>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
                    Add Product
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="productsTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total Value</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted">Loading products...</p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end"><strong>Totals:</strong></td>
                                {{-- <td><strong id="footerTotalPrice">$0.00</strong></td> --}}
                                {{-- <td><strong id="footerTotalQuantity">0</strong></td> --}}
                                <td><strong id="footerTotalValue">$0.00</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="productForm">
                    <div class="modal-body">
                        <input type="hidden" id="productId">

                        <div class="mb-3">
                            <label for="productName" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="productName" required>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="productPrice" class="form-label">Price ($)</label>
                            <input type="number" class="form-control" id="productPrice" step="0.01" min="0"
                                required>
                            <div class="invalid-feedback" id="priceError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="productQuantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="productQuantity" min="0" required>
                            <div class="invalid-feedback" id="quantityError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <span id="saveBtnText">Save Product</span>
                            <span id="saveBtnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let products = [];

            $(document).ready(function() {
                // Setup CSRF token
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                loadProducts();
            });

            function loadProducts() {
                $.ajax({
                    url: '/api/products',
                    method: 'GET',
                    success: function(data) {
                        products = data;
                        renderProducts();
                        updateStats();
                    },
                    error: function() {
                        showAlert('Failed to load products', 'danger');
                    }
                });
            }

            function renderProducts() {
                const tbody = $('#productsTableBody');
                tbody.empty();

                if (products.length === 0) {
                    tbody.html(`
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        No products found
                    </td>
                </tr>
            `);
                    return;
                }

                products.forEach(function(product) {
                    const totalValue = (product.quantity * product.price).toFixed(2);

                    const row = `
                <tr>
                    <td>${product.id}</td>
                    <td><strong>${product.name}</strong></td>
                    <td>$${parseFloat(product.price).toFixed(2)}</td>
                    <td>${product.quantity}</td>
                    <td>$${totalValue}</td>
                    <td class="text-center">
                        <button class="btn " onclick="editProduct(${product.id})">
                            Edit
                        </button>
                        <button class="btn " onclick="deleteProduct(${product.id})">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
                    tbody.append(row);
                });
            }

            function updateStats() {
                const total = products.length;
                let totalValue = 0;
                let totalPrice = 0;
                let totalQty = 0;

                for (let i = 0; i < products.length; i++) {
                    totalPrice += parseFloat(products[i].price);
                    totalQty += parseInt(products[i].quantity);
                    totalValue += parseFloat(products[i].price) * products[i].quantity;
                }

                // Update stats cards
                $('#totalProducts').text(total);
                $('#totalValue').text('$' + totalValue.toFixed(2));

                // Update footer totals
                $('#footerTotalPrice').text('$' + totalPrice.toFixed(2));
                $('#footerTotalQuantity').text(totalQty);
                $('#footerTotalValue').text('$' + totalValue.toFixed(2));
            }

            function resetForm() {
                $('#productForm')[0].reset();
                $('#productId').val('');
                $('#productModalLabel').text('Add Product');
                $('#saveBtnText').text('Save Product');

                // Clear any validation errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
            }

            function editProduct(id) {
                const product = products.find(p => p.id === id);
                if (!product) return;

                $('#productId').val(product.id);
                $('#productName').val(product.name);
                $('#productPrice').val(product.price);
                $('#productQuantity').val(product.quantity);
                $('#productModalLabel').text('Edit Product');
                $('#saveBtnText').text('Update Product');

                const modal = new bootstrap.Modal($('#productModal'));
                modal.show();
            }

            $('#productForm').on('submit', function(e) {
                e.preventDefault();

                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                const productId = $('#productId').val();
                const formData = {
                    name: $('#productName').val(),
                    price: $('#productPrice').val(),
                    quantity: $('#productQuantity').val()
                };

                // Show loading
                $('#saveBtn').prop('disabled', true);
                $('#saveBtnText').addClass('d-none');
                $('#saveBtnSpinner').removeClass('d-none');

                const url = productId ? `/api/products/${productId}` : '/api/products';
                const method = productId ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: JSON.stringify(formData),
                    contentType: 'application/json',
                    success: function(response) {
                        loadProducts();
                        const modal = bootstrap.Modal.getInstance($('#productModal'));
                        modal.hide();
                        showAlert(productId ? 'Product updated successfully' : 'Product added successfully',
                            'success');
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.name) {
                                $('#productName').addClass('is-invalid');
                                $('#nameError').text(errors.name[0]);
                            }
                            if (errors.price) {
                                $('#productPrice').addClass('is-invalid');
                                $('#priceError').text(errors.price[0]);
                            }
                            if (errors.quantity) {
                                $('#productQuantity').addClass('is-invalid');
                                $('#quantityError').text(errors.quantity[0]);
                            }
                        } else {
                            showAlert('Failed to save product', 'danger');
                        }
                    },
                    complete: function() {
                        $('#saveBtn').prop('disabled', false);
                        $('#saveBtnText').removeClass('d-none');
                        $('#saveBtnSpinner').addClass('d-none');
                    }
                });
            });

            function deleteProduct(id) {
                if (!confirm('Are you sure you want to delete this product?')) {
                    return;
                }

                $.ajax({
                    url: `/api/products/${id}`,
                    method: 'DELETE',
                    success: function() {
                        loadProducts();
                        showAlert('Product deleted successfully', 'success');
                    },
                    error: function() {
                        showAlert('Failed to delete product', 'danger');
                    }
                });
            }

            function showAlert(message, type) {
                const alert = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
                $('body').append(alert);

                setTimeout(function() {
                    $('.alert').alert('close');
                }, 3000);
            }
        </script>
    @endpush
@endsection
