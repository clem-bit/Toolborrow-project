<?php
class Response {
    public static function json($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function error(string $msg, int $code = 400): void {
        self::json(['error' => $msg], $code);
    }
}
?>
