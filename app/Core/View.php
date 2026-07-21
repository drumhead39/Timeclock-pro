<?php
declare(strict_types=1);

namespace App\Core;

final class View {
  public static function render(string $view, array $data = []): void {
    extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . '/../Views/' . $view . '.php';
    if (!file_exists($viewFile)) throw new \RuntimeException("View not found: $view");
    include __DIR__ . '/../Views/layout.php';
  }
}
