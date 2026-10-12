<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: APIController
 * 
 * Automatically generated via CLI.
 */
class APIController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
    }

     // POST /REGISTER
    public function register()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit(null, 5, 60);
        $input = $this->api->body();
 
        $username = trim($input['username'] ?? '');
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
 
        if ($username === '' || strlen($username) < 3 || strlen($username) > 50) {
            $this->api->respond_error('Username must be 3-50 characters', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $this->api->respond_error('Valid email is required', 422);
        }
        if (strlen($password) < 8) {
            $this->api->respond_error('Password must be at least 8 characters', 422);
        }
 
        $stmt = $this->db->raw(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            [$username, $email]
        );
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->api->respond_error('Username or email already exists', 409);
        }
 
        // Role ay laging 'user' (hindi galing sa input)
        $this->db->raw(
            "INSERT INTO users (username, email, password, role, is_active, created_at, updated_at)
             VALUES (?, ?, ?, 'user', 1, NOW(), NOW())",
            [$username, $email, password_hash($password, PASSWORD_BCRYPT)]
        );
 
        $this->api->respond(['message' => 'Registered successfully'], 201);
    }

         // POST /login
    public function login()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit(null, 10, 60);
        $input    = $this->api->body();
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
 
        $stmt = $this->db->raw(
            'SELECT * FROM users WHERE username = ? AND is_active = 1',
            [$username]
        );
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if ($user && password_verify($password, $user['password'])) {
            $tokens = $this->api->issue_tokens([
                'id'   => $user['id'],
                'role' => $user['role'],
            ]);
            $this->api->respond($tokens);
        } else {
            $this->api->respond_error('Invalid credentials', 401);
        }
    }
 
    // POST /refresh
    public function refresh()
    {
        $this->api->require_method('POST');
        $input = $this->api->body();
        $this->api->refresh_access_token($input['refresh_token'] ?? '');
    }
 
    // POST /logout
    public function logout()
    {
        $this->api->require_method('POST');
        $input = $this->api->body();
        $this->api->revoke_refresh_token($input['refresh_token'] ?? '');
        $this->api->respond(['message' => 'Logged out']);
    }
 
    // GET /profile
    public function profile()
    {
        $this->api->require_method('GET');
        $auth = $this->api->require_jwt();
        $uid  = $auth['id'] ?? $auth['sub'] ?? 0;
 
        $stmt = $this->db->raw(
            'SELECT id, username, email, role, created_at FROM users WHERE id = ?',
            [$uid]
        );
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
        $this->api->respond($user ?: ['message' => 'User not found']);
    }
}
