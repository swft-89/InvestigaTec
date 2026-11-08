<?php
declare(strict_types=1);

use Cloudinary\Cloudinary as CloudinaryClient;

function investigatec_cloudinary(): ?CloudinaryClient
{
    static $client = null;
    static $initialized = false;
    if ($initialized) return $client;
    $initialized = true;

    $credentials = [
        'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
        'api_key' => getenv('CLOUDINARY_API_KEY'),
        'api_secret' => getenv('CLOUDINARY_API_SECRET'),
    ];
    $configured = array_filter($credentials, static fn($value): bool =>
        is_string($value) && trim($value) !== ''
    );
    if (!$configured) return null;
    if (count($configured) !== count($credentials)) {
        throw new RuntimeException('Faltan variables de entorno de Cloudinary.');
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Falta instalar las dependencias de Composer para Cloudinary.');
    }
    require_once $autoload;

    $client = new CloudinaryClient([
        'cloud' => $credentials,
        'url' => ['secure' => true],
    ]);

    return $client;
}

function investigatec_cloudinary_upload(
    string $localPath,
    string $publicId,
    string $resourceType = 'image',
    string $deliveryType = 'upload'
): array {
    $cloudinary = investigatec_cloudinary();
    if (!$cloudinary) throw new RuntimeException('Cloudinary no está configurado.');

    $result = $cloudinary->uploadApi()->upload($localPath, [
        'public_id' => $publicId,
        'resource_type' => $resourceType,
        'type' => $deliveryType,
        'overwrite' => false,
        'use_filename' => false,
        'unique_filename' => false,
    ]);
    $uploadedPublicId = $result['public_id'] ?? null;
    if (!is_string($uploadedPublicId) || $uploadedPublicId !== $publicId) {
        throw new RuntimeException('Cloudinary no devolvió el identificador del archivo.');
    }

    return [
        'public_id' => $uploadedPublicId,
        'secure_url' => $result['secure_url'] ?? null,
        'bytes' => $result['bytes'] ?? null,
    ];
}

function investigatec_cloudinary_delete(
    string $publicId,
    string $resourceType,
    string $deliveryType
): void {
    $cloudinary = investigatec_cloudinary();
    if (!$cloudinary) throw new RuntimeException('Cloudinary no está configurado.');

    $result = $cloudinary->uploadApi()->destroy($publicId, [
        'resource_type' => $resourceType,
        'type' => $deliveryType,
        'invalidate' => true,
    ]);
    if (($result['result'] ?? null) !== 'ok' && ($result['result'] ?? null) !== 'not found') {
        throw new RuntimeException('Cloudinary no confirmó la eliminación del archivo.');
    }
}

function investigatec_cloudinary_pdf_url(string $storedReference): ?string
{
    $prefix = 'cloudinary:authenticated:image:';
    if (!str_starts_with($storedReference, $prefix)) return null;

    $publicId = substr($storedReference, strlen($prefix));
    if (!preg_match('~^investigatec/projects/[1-9][0-9]*/[a-f0-9]{32}$~', $publicId)) {
        throw new RuntimeException('El identificador del PDF en Cloudinary no es válido.');
    }
    $cloudinary = investigatec_cloudinary();
    if (!$cloudinary) throw new RuntimeException('Cloudinary no está configurado para recuperar este PDF.');

    return $cloudinary->uploadApi()->privateDownloadUrl($publicId, 'pdf', [
        'resource_type' => 'image',
        'type' => 'authenticated',
        'expires_at' => time() + 60,
    ]);
}
