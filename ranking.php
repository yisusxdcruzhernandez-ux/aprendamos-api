<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');   // Permite peticiones desde la app Android
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require 'conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];

// ── GET: obtener ranking (con filtro de tema opcional) ──────────────
if ($metodo === 'GET') {
    $tema = $_GET['tema'] ?? null;

    if ($tema && $tema !== 'todos') {
        $stmt = $pdo->prepare("SELECT * FROM ranking WHERE tema = ? ORDER BY pct DESC, tiempo ASC LIMIT 50");
        $stmt->execute([$tema]);
    } else {
        $stmt = $pdo->query("SELECT * FROM ranking ORDER BY pct DESC, tiempo ASC LIMIT 50");
    }

    echo json_encode($stmt->fetchAll());
    exit;
}

// ── POST: guardar nueva entrada ─────────────────────────────────────
if ($metodo === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);

    // Validaciones básicas
    if (empty($datos['nombre']) || empty($datos['tema'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan datos']);
        exit;
    }

    $nombre   = substr(trim($datos['nombre']), 0, 80);
    $tema     = substr($datos['tema'], 0, 30);
    $aciertos = (int)($datos['aciertos'] ?? 0);
    $total    = (int)($datos['total'] ?? 1);
    $pct      = (int)($datos['pct'] ?? 0);
    $tiempo   = (int)($datos['tiempo'] ?? 0);
    $fecha    = date('Y-m-d');

    $stmt = $pdo->prepare(
        "INSERT INTO ranking (nombre, tema, aciertos, total, pct, tiempo, fecha)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nombre, $tema, $aciertos, $total, $pct, $tiempo, $fecha]);

    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// ── DELETE: limpiar ranking (con contraseña) ─────────────────────────
if ($metodo === 'DELETE') {
    $datos = json_decode(file_get_contents('php://input'), true);
    if (($datos['pass'] ?? '') !== 'Jakai') {
        http_response_code(403);
        echo json_encode(['error' => 'Contraseña incorrecta']);
        exit;
    }
    $pdo->exec("DELETE FROM ranking");
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);