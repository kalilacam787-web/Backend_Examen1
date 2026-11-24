<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudinaryImageService
{
    public function upload(UploadedFile $image): array
    {
        $credentials = $this->credentials();
        $contents = file_get_contents($image->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer la imagen.');
        }

        $parameters = [
            'folder' => $credentials['folder'],
            'timestamp' => time(),
        ];

        $response = Http::timeout(60)
            ->asMultipart()
            ->attach('file', $contents, $image->getClientOriginalName())
            ->post($this->endpoint($credentials['cloud_name'], 'upload'), [
                ...$parameters,
                'api_key' => $credentials['api_key'],
                'signature' => $this->signature($parameters, $credentials['api_secret']),
            ])
            ->throw();

        $result = $response->json();

        if (! is_array($result) || ! isset($result['secure_url'], $result['public_id'])) {
            throw new RuntimeException('Cloudinary no devolvió los datos de la imagen.');
        }

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    public function delete(string $publicId): void
    {
        $credentials = $this->credentials();
        $parameters = [
            'public_id' => $publicId,
            'timestamp' => time(),
        ];

        $result = Http::timeout(60)
            ->asMultipart()
            ->post($this->endpoint($credentials['cloud_name'], 'destroy'), [
                ...$parameters,
                'api_key' => $credentials['api_key'],
                'signature' => $this->signature($parameters, $credentials['api_secret']),
            ])
            ->throw()
            ->json();

        if (! in_array($result['result'] ?? null, ['ok', 'not found'], true)) {
            throw new RuntimeException('Cloudinary no pudo eliminar la imagen.');
        }
    }

    private function credentials(): array
    {
        $credentials = [
            'cloud_name' => config('services.cloudinary.cloud_name'),
            'api_key' => config('services.cloudinary.api_key'),
            'api_secret' => config('services.cloudinary.api_secret'),
            'folder' => config('services.cloudinary.folder'),
        ];

        foreach (['cloud_name', 'api_key', 'api_secret', 'folder'] as $key) {
            if (! is_string($credentials[$key]) || $credentials[$key] === '') {
                throw new RuntimeException('Configura las credenciales de Cloudinary en el archivo .env.');
            }
        }

        return $credentials;
    }

    private function endpoint(string $cloudName, string $action): string
    {
        return 'https://api.cloudinary.com/v1_1/'.rawurlencode($cloudName).'/image/'.$action;
    }

    private function signature(array $parameters, string $apiSecret): string
    {
        // Cloudinary firma los parámetros ordenados junto con el secreto.
        ksort($parameters);

        $parts = [];
        foreach ($parameters as $key => $value) {
            $parts[] = $key.'='.$value;
        }

        return sha1(implode('&', $parts).$apiSecret);
    }
}
