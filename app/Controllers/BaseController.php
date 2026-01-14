<?php
namespace App\Controllers;

use App\Config\Database;

abstract class BaseController
{
    protected $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    protected function render($view, $data = [])
    {
        extract($data);
        
        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            die("View file not found: {$viewFile}");
        }
        
        require_once __DIR__ . '/../../views/layouts/header.php';
        require_once $viewFile;
        require_once __DIR__ . '/../../views/layouts/footer.php';
    }
    
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }
    
    protected function isAuth()
    {
        return isset($_SESSION['user_id']);
    }
    
    protected function requireAuth()
    {
        if (!$this->isAuth()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'Unauthorized'], 401);
            } else {
                $this->redirect('/login');
            }
        }
    }
    
    protected function requireAdmin()
    {
        $this->requireAuth();
        
        if ($_SESSION['role'] !== 'admin') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'Forbidden'], 403);
            } else {
                $this->setFlash('error', 'Admin access required');
                $this->redirect('/');
            }
        }
    }
    
    protected function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    protected function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    protected function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    
    protected function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    protected function validateCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}