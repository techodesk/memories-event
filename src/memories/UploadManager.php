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
            'ContentType' => mime_content_type($path)
        ]);
        if ($this->cdnUrl) {
            return $this->cdnUrl . '/' . $key;
        }
        return $key;
    }
}
