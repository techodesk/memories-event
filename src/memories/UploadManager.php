<?php
use Aws\S3\S3Client;

class UploadManager
{
    private S3Client $client;
    private string $bucket;
    private string $folder;
    private string $cdnUrl;

    public function __construct(array $conf)
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $conf['region'],
            'endpoint' => $conf['endpoint'],
            'credentials' => [
                'key' => $conf['key'],
                'secret' => $conf['secret']
            ],
        ]);
        $this->bucket = $conf['bucket'];
        $this->folder = $conf['folder'] ?? '';
        $this->cdnUrl = rtrim($conf['cdn_url'] ?? '', '/');
    }

    /**
     * Create an empty folder in the bucket.
     */
    public function createFolder(string $folder): void
    {
        $folder = rtrim($this->folder . $folder, '/') . '/';
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key'    => $folder,
            'Body'   => ''
        ]);
    }

    public function upload(string $path, string $filename): string
    {
        $key = $this->folder . uniqid('event_', true) . '_' . basename($filename);
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'SourceFile' => $path,
            'ACL' => 'public-read',
            'ContentType' => $this->detectMime($path)
        ]);
        if ($this->cdnUrl) {
            return $this->cdnUrl . '/' . $key;
        }
        return $key;
    }

    public function uploadToFolder(string $folder, string $path, string $filename): string
    {
        $folder = rtrim($this->folder . $folder, '/');
        $key = $folder . '/' . uniqid('', true) . '_' . basename($filename);
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'SourceFile' => $path,
            'ACL' => 'public-read',
            'ContentType' => $this->detectMime($path)
        ]);
        if ($this->cdnUrl) {
            return $this->cdnUrl . '/' . $key;
        }
        return $key;
    }

    private function detectMime(string $path): string
    {
        if (is_file($path)) {
            $type = mime_content_type($path);
            if ($type !== false) {
                return $type;
            }
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'webp' => 'image/webp',
            'mp4'  => 'video/mp4',
            'mov'  => 'video/quicktime',
            'webm' => 'video/webm',
            'mkv'  => 'video/x-matroska',
            'avi'  => 'video/x-msvideo',
            'flv'  => 'video/x-flv',
            'wmv'  => 'video/x-ms-wmv',
            '3gp'  => 'video/3gpp',
            'mpeg' => 'video/mpeg',
            'mpg'  => 'video/mpeg',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }

    public function listFiles(string $folder): array
    {
        $prefix = rtrim($this->folder . $folder, '/') . '/';
        $res = $this->client->listObjectsV2([
            'Bucket' => $this->bucket,
            'Prefix' => $prefix
        ]);
        $files = [];
        foreach ($res['Contents'] ?? [] as $obj) {
            if (rtrim($obj['Key'], '/') === $prefix) {
                continue;
            }
            $files[] = $this->cdnUrl ? $this->cdnUrl . '/' . $obj['Key'] : $obj['Key'];
        }
        return $files;
    }
}
