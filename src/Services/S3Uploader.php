<?php

namespace MlabPs\AwsUploadAssets\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Uploader
{
    private S3Client $s3Client;
    private string $bucket;
    private string $pathPrefix;

    public function __construct(
        string $bucket,
        string $accessKeyId,
        string $secretAccessKey,
        string $region = 'eu-south-1',
        string $pathPrefix = 'products/'
    ) {
        $this->bucket = $bucket;
        $this->pathPrefix = rtrim($pathPrefix, '/') . '/';

        // Inizializza il client S3
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ]);
    }

    /**
     * Carica un file su S3
     * 
     * @param string $localFilePath Percorso del file locale
     * @param string $s3Key Chiave (path) del file su S3
     * @param array $options Opzioni aggiuntive per l'upload
     * @return array Risultato dell'upload
     * @throws \Exception
     */
    public function uploadFile(string $localFilePath, ?string $s3Key = null, array $options = []): array
    {
        if (!file_exists($localFilePath)) {
            throw new \Exception("File not found: {$localFilePath}");
        }
        $sourceFile = $localFilePath;

        //clean the doc root form localFilepath
        $localFilePath = str_replace(_PS_ROOT_DIR_, '', $localFilePath);
        if(is_null($s3Key)) {
            $s3Key = ltrim($localFilePath, '/');
        }

        // Aggiungi il prefisso al path
        $fullKey = $this->pathPrefix . $s3Key;

        // Determina il content type dal file
        $contentType = $this->getContentType($localFilePath);

        // Parametri di default
        $params = array_merge([
            'Bucket' => $this->bucket,
            'Key' => $fullKey,
            'SourceFile' => $sourceFile,
            'ContentType' => $contentType
        ], $options);

        try {
            $result = $this->s3Client->putObject($params);
            
            return [
                'success' => true,
                'url' => $result['ObjectURL'] ?? null,
                'key' => $fullKey,
                'etag' => $result['ETag'] ?? null,
            ];
        } catch (AwsException $e) {
            throw new \Exception(
                "AWS S3 Upload Error: " . $e->getAwsErrorMessage()
            );
        }
    }

    /**
     * Carica più file contemporaneamente
     * 
     * @param array $files Array di file con formato ['local_path' => 's3_key']
     * @return array Risultati degli upload
     */
    public function uploadMultipleFiles(array $files): array
    {
        $results = [];
        
        foreach ($files as $localPath => $s3Key) {
            try {
                $results[$s3Key] = $this->uploadFile($localPath);
            } catch (\Exception $e) {
                $results[$s3Key] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Elimina un file da S3
     * 
     * @param string $s3Key Chiave del file da eliminare
     * @return bool
     */
    public function deleteFile(string $s3Key): bool
    {
        $fullKey = $this->pathPrefix . ltrim($s3Key, '/');

        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $fullKey,
            ]);
            
            return true;
        } catch (AwsException $e) {
            throw new \Exception(
                "AWS S3 Delete Error: " . $e->getAwsErrorMessage()
            );
        }
    }

    /**
     * Verifica se un file esiste su S3
     * 
     * @param string $s3Key Chiave del file
     * @return bool
     */
    public function fileExists(string $s3Key): bool
    {
        $fullKey = $this->pathPrefix . ltrim($s3Key, '/');

        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $fullKey,
            ]);
            
            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    /**
     * Ottiene l'URL pubblico di un file su S3
     * 
     * @param string $s3Key Chiave del file
     * @return string URL del file
     */
    public function getPublicUrl(string $s3Key): string
    {
        $fullKey = $this->pathPrefix . ltrim($s3Key, '/');
        
        return $this->s3Client->getObjectUrl($this->bucket, $fullKey);
    }

    /**
     * Testa la connessione a S3
     * 
     * @return bool
     * @throws \Exception
     */
    public function testConnection(): bool
    {
        try {
            // Verifica che il bucket esista
            $this->s3Client->headBucket([
                'Bucket' => $this->bucket,
            ]);
            
            return true;
        } catch (AwsException $e) {
            throw new \Exception(
                "AWS S3 Connection Test Failed: " . $e->getAwsErrorMessage()
            );
        }
    }

    /**
     * Determina il content type di un file
     * 
     * @param string $filePath Percorso del file
     * @return string Content type
     */
    private function getContentType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Ottiene il nome del bucket
     * 
     * @return string
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * Ottiene il prefisso del path
     * 
     * @return string
     */
    public function getPathPrefix(): string
    {
        return $this->pathPrefix;
    }
}
