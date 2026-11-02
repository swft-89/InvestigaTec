<?php
declare(strict_types=1);

function brevo_send_email(string $email, string $name, string $subject, string $html, string $text): void
{
    $html .= '<p>Por favor, no respondas a este correo; esta dirección no recibe respuestas.</p>';
    $text .= "\n\nPor favor, no respondas a este correo; esta dirección no recibe respuestas.";

    $apiKey = getenv('BREVO_API_KEY');
    $senderEmail = getenv('BREVO_SENDER_EMAIL');
    $senderName = getenv('BREVO_SENDER_NAME') ?: 'InvestigaTec';
    if (!is_string($apiKey) || trim($apiKey) === '' ||
        !is_string($senderEmail) || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Configura BREVO_API_KEY y un remitente BREVO_SENDER_EMAIL válido.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extensión cURL de PHP es necesaria para enviar correos con Brevo.');
    }

    $payload = json_encode([
        'sender' => ['email' => $senderEmail, 'name' => $senderName],
        'to' => [['email' => $email, 'name' => $name]],
        'subject' => $subject,
        'htmlContent' => $html,
        'textContent' => $text,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($payload)) throw new RuntimeException('No se pudo preparar el correo para Brevo.');

    $request = curl_init('https://api.brevo.com/v3/smtp/email');
    if ($request === false) throw new RuntimeException('No se pudo iniciar la conexión con Brevo.');
    curl_setopt_array($request, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . trim($apiKey),
            'content-type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($request);
    $status = (int)curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($request);
    curl_close($request);

    if ($response === false || $status < 200 || $status >= 300) {
        throw new RuntimeException(
            $response === false
                ? 'Falló la comunicación con Brevo: ' . $curlError
                : 'Brevo rechazó el correo con código HTTP ' . $status . '.'
        );
    }
}

function application_public_url(string $path): string
{
    $baseUrl = getenv('APP_URL') ?: getenv('RENDER_EXTERNAL_URL');
    if (!is_string($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Configura APP_URL con la URL pública HTTPS de la aplicación.');
    }
    $parts = parse_url($baseUrl);
    if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' ||
        empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) ||
        isset($parts['query']) || isset($parts['fragment'])) {
        throw new RuntimeException('APP_URL debe ser una URL HTTPS pública válida.');
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

function dispatch_notification_email(PDO $pdo, int $notificationId): ?bool
{
    $claim = $pdo->prepare(
        "UPDATE ext_notificacion SET estado_envio = 'ENVIANDO'
         WHERE id_notificacion = :id AND estado_envio = 'PENDIENTE'"
    );
    $claim->execute(['id' => $notificationId]);
    if ($claim->rowCount() !== 1) return null;

    try {
        $query = $pdo->prepare(
            "SELECT n.mensaje, n.tipo, u.correo,
                    trim(concat_ws(' ', u.nombre, u.apellido_paterno, u.apellido_materno)) AS nombre
             FROM ext_notificacion n
             JOIN usuarios u ON u.id_usuario = n.id_usuario
             WHERE n.id_notificacion = :id"
        );
        $query->execute(['id' => $notificationId]);
        $notification = $query->fetch();
        if (!$notification) throw new RuntimeException('No se encontró el destinatario de la notificación.');

        $subject = match ($notification['tipo']) {
            'CAMBIO_ESTADO' => 'Actualización de tu artículo · InvestigaTec',
            'REPORTE_ARTICULO' => 'Nuevo reporte de artículo · InvestigaTec',
            'RESPUESTA_AYUDA' => 'Respuesta a tu solicitud · InvestigaTec',
            default => 'Nueva notificación · InvestigaTec',
        };
        $message = (string)$notification['mensaje'];
        $escapedMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = $message . "\n\nConsulta InvestigaTec: " . application_public_url('?page=notificaciones');
        $html = '<p>' . nl2br($escapedMessage, false) . '</p><p><a href="' .
            htmlspecialchars(application_public_url('?page=notificaciones'), ENT_QUOTES, 'UTF-8') .
            '">Consultar notificaciones</a></p>';
        brevo_send_email(
            (string)$notification['correo'],
            (string)$notification['nombre'],
            $subject,
            $html,
            $text
        );

        $sent = $pdo->prepare(
            "UPDATE ext_notificacion SET estado_envio = 'ENVIADO'
             WHERE id_notificacion = :id AND estado_envio = 'ENVIANDO'"
        );
        $sent->execute(['id' => $notificationId]);
        return true;
    } catch (Throwable $error) {
        $failed = $pdo->prepare(
            "UPDATE ext_notificacion SET estado_envio = 'ERROR'
             WHERE id_notificacion = :id AND estado_envio = 'ENVIANDO'"
        );
        $failed->execute(['id' => $notificationId]);
        error_log('InvestigaTec correo de notificación ' . $notificationId . ': ' . $error->getMessage());
        return false;
    }
}

function dispatch_pending_notification_emails(PDO $pdo, ?int $projectId = null): int
{
    $sql = "SELECT id_notificacion FROM ext_notificacion WHERE estado_envio = 'PENDIENTE'";
    $parameters = [];
    if ($projectId !== null) {
        $sql .= ' AND id_proyecto = :proyecto';
        $parameters['proyecto'] = $projectId;
    }
    $sql .= ' ORDER BY id_notificacion';
    $query = $pdo->prepare($sql);
    $query->execute($parameters);
    $failures = 0;
    foreach ($query->fetchAll(PDO::FETCH_COLUMN) as $notificationId) {
        if (dispatch_notification_email($pdo, (int)$notificationId) === false) $failures++;
    }
    return $failures;
}
