<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: ProductController
 * 
 * Automatically generated via CLI.
 */
class ProductController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
    }

     // GET /products
    public function index()
    {
    $this->api->require_method('GET');
    $this->api->require_jwt();
    $this->api->rate_limit();

    $stmt = $this->db->raw(
        'SELECT id, product_name, description, price, quantity, created_at
         FROM products ORDER BY id DESC',
        []
    );
    $this->api->respond($stmt->fetchAll(PDO::FETCH_ASSOC));
}
 
    // GET /products/{id}
    public function show($id)
    {
        $this->api->require_method('GET');
        $this->api->require_jwt();
 
        $stmt = $this->db->raw('SELECT * FROM products WHERE id = ?', [(int) $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if (!$product) {
            $this->api->respond_error('Product not found', 404);
        }
        $this->api->respond($product);
    }
 
    // POST /products
    public function store()
    {
        $this->api->require_method('POST');
        $this->api->require_jwt();
        $input = $this->api->body();
 
        $this->validate_input($input);
 
        $this->db->raw(
            'INSERT INTO products (product_name, description, price, quantity, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [
                trim($input['product_name']),
                $input['description'] ?? null,
                $input['price'],
                (int) ($input['quantity'] ?? 0),
            ]
        );
 
        $this->api->respond(['message' => 'Product created'], 201);
    }
 
    // PUT /products/{id}
    public function update($id)
    {
        $this->api->require_method('PUT');
        $this->api->require_jwt();
        $input = $this->api->body();
 
        $this->validate_input($input);
 
        $check = $this->db->raw('SELECT id FROM products WHERE id = ?', [(int) $id]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $this->api->respond_error('Product not found', 404);
        }
 
        $this->db->raw(
            'UPDATE products
             SET product_name = ?, description = ?, price = ?, quantity = ?
             WHERE id = ?',
            [
                trim($input['product_name']),
                $input['description'] ?? null,
                $input['price'],
                (int) ($input['quantity'] ?? 0),
                (int) $id,
            ]
        );
 
        $this->api->respond(['message' => 'Product updated']);
    }
 
   // DELETE /products/{id}
public function destroy($id)
{
    $this->api->require_method('DELETE');
    $this->api->require_jwt();

    $this->db->raw('DELETE FROM products WHERE id = ?', [(int) $id]);
    $this->api->respond(['message' => 'Product deleted']);
}
 
    private function validate_input($input)
    {
        if (trim($input['product_name'] ?? '') === '') {
            $this->api->respond_error('Product name is required', 422);
        }
        if (!isset($input['price']) || !is_numeric($input['price']) || $input['price'] < 0) {
            $this->api->respond_error('Valid price is required', 422);
        }
        if (isset($input['quantity']) && $input['quantity'] !== '' && (!is_numeric($input['quantity']) || $input['quantity'] < 0)) {
            $this->api->respond_error('Quantity must be 0 or more', 422);
        }
    }

}
