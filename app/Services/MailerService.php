<?php
declare(strict_types=1);

namespace App\Services;

final class MailerService {

  public static function sendWithAttachment(string $to, string $subject, string $body, string $filePath, string $fileName): bool {
    if (!$to) return false;
    $content = file_get_contents($filePath);
    if ($content === false) return false;

    $boundary = "==Multipart_Boundary_x" . md5((string)microtime(true)) . "x";
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

    $msg = "--{$boundary}\r\n";
    $msg .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
    $msg .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $msg .= $body . "\r\n\r\n";

    $msg .= "--{$boundary}\r\n";
    $msg .= "Content-Type: application/pdf; name=\"{$fileName}\"\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n";
    $msg .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
    $msg .= chunk_split(base64_encode($content)) . "\r\n";
    $msg .= "--{$boundary}--";

    return mail($to, $subject, $msg, implode("\r\n", $headers));
  }
}
