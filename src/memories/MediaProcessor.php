<?php

class MediaProcessor
{
    private UploadManager $uploader;
    private string $stagingDir;

    public function __construct(UploadManager $uploader, string $stagingDir)
    {
        $this->uploader = $uploader;
        $this->stagingDir = rtrim($stagingDir, '/');
    }

    public function processAndUpload(int $eventId, string $uploadFolder, array $file, string $sessionId): ?string
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $eventDir = $this->stagingDir . '/event_' . $eventId;
        if (!is_dir($eventDir)) {
            mkdir($eventDir, 0777, true);
        }
        $base = uniqid($sessionId . '_', true);
        $dest = $eventDir . '/' . $base . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }
        if ($this->isVideo($dest)) {
            $processed = $eventDir . '/' . $base . '.mp4';
            $this->processVideo($dest, $processed);
            unlink($dest);
            $dest = $processed;
        } elseif ($this->isImage($dest)) {
            $this->processImage($dest);
        }
        $url = $this->uploader->uploadToFolder($uploadFolder, $dest, basename($dest));
        unlink($dest);
        return $url;
    }

    private function isVideo(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $video = ['mp4','mov','webm','mkv','avi','flv','wmv','3gp','mpeg','mpg'];
        return in_array($ext, $video, true);
    }

    private function isImage(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $img = ['jpg','jpeg','png','gif','bmp','webp'];
        return in_array($ext, $img, true);
    }

    private function processVideo(string $input, string $output): void
    {
        $cmd = sprintf(
            'ffmpeg -i %s -vf scale=1280:-2 -c:v libx264 -preset veryfast -crf 23 -c:a aac -y %s 2>&1',
            escapeshellarg($input),
            escapeshellarg($output)
        );
        shell_exec($cmd);
    }

    private function processImage(string $path): void
    {
        $info = getimagesize($path);
        if (!$info) {
            return;
        }
        list($width, $height) = $info;
        $max = 1600;
        if ($width <= $max && $height <= $max) {
            return;
        }
        $ratio = min($max / $width, $max / $height);
        $newW = (int)($width * $ratio);
        $newH = (int)($height * $ratio);
        $src = imagecreatefromstring(file_get_contents($path));
        if (!$src) {
            return;
        }
        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagejpeg($dst, $path, 85);
        imagedestroy($src);
        imagedestroy($dst);
    }
}
