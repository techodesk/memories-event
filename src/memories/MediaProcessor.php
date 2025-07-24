<?php

class MediaProcessor
{
    private UploadManager $uploader;
    private string $uploadDir;

    public function __construct(UploadManager $uploader, string $uploadDir)
    {
        $this->uploader = $uploader;
        $this->uploadDir = rtrim($uploadDir, '/');
    }

	public function processAndUpload(int $eventId, string $uploadFolder, array $file, string $sessionId): ?string
	{
		if (!is_uploaded_file($file['tmp_name'])) {
			return null;
		}

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$base = uniqid($sessionId . '_', true);
		$tempDir = $this->uploadDir . '/temp';
		$eventDir = $this->uploadDir . '/event_' . $eventId;

		// Ensure both folders exist
		if (!is_dir($tempDir)) {
			if (!mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
				error_log("Failed to create temp dir: $tempDir");
				return null;
			}
		}

		if (!is_dir($eventDir)) {
			if (!mkdir($eventDir, 0775, true) && !is_dir($eventDir)) {
				error_log("Failed to create event dir: $eventDir");
				return null;
			}
		}

		// Save upload to temp folder
		$tempPath = $tempDir . '/' . $base . '.' . $ext;
		if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
			error_log("Failed to move uploaded file to $tempPath");
			return null;
		}

		// Prepare final output path
		$finalPath = $eventDir . '/' . $base . '.mp4';
		$processed = $tempPath;

		if ($this->isVideo($tempPath)) {
			if ($this->processVideo($tempPath, $finalPath)) {
				unlink($tempPath); // remove original
				$processed = $finalPath;
			}
		} elseif ($this->isImage($tempPath)) {
			$this->processImage($tempPath);
			$finalPath = $eventDir . '/' . $base . '.' . $ext;
			rename($tempPath, $finalPath);
			$processed = $finalPath;
		} else {
			// just move to event dir if unknown
			$finalPath = $eventDir . '/' . $base . '.' . $ext;
			rename($tempPath, $finalPath);
			$processed = $finalPath;
		}

		$url = $this->uploader->uploadToFolder($uploadFolder, $processed, basename($processed));
		unlink($processed);
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

    private function processVideo(string $input, string $output): bool
    {
        $cmd = sprintf(
            'ffmpeg -i %s -vf scale=1280:-2 -c:v libx264 -preset veryfast -crf 23 -c:a aac -y %s 2>&1',
            escapeshellarg($input),
            escapeshellarg($output)
        );
        shell_exec($cmd);
        return is_file($output);
    }

    private function processImage(string $path): void
    {
        $info = getimagesize($path);
        if (!$info) {
            return;
        }

        $src = imagecreatefromstring(file_get_contents($path));
        if (!$src) {
            return;
        }

        // Correct orientation based on EXIF data if available
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = $exif['Orientation'] ?? 1;
            switch ((int)$orientation) {
                case 3:
                    $src = imagerotate($src, 180, 0);
                    break;
                case 6:
                    $src = imagerotate($src, -90, 0);
                    break;
                case 8:
                    $src = imagerotate($src, 90, 0);
                    break;
            }
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $max = 1600;
        if ($width > $max || $height > $max) {
            $ratio = min($max / $width, $max / $height);
            $newW = (int)($width * $ratio);
            $newH = (int)($height * $ratio);
            $dst = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagejpeg($dst, $path, 85);
            imagedestroy($dst);
        } else {
            imagejpeg($src, $path, 85);
        }
        imagedestroy($src);
    }
}
