<?php

namespace Drupal\tutorial_videos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for serving tutorial videos to the admin help page.
 */
class TutorialVideosController extends ControllerBase {

  /**
   * Base path where tutorial videos are stored.
   */
  protected function getBasePath(): string {
    return DRUPAL_ROOT . '/../private/Tutorial Videos';
  }

  /**
   * Define the structured chapter order and video metadata.
   */
  protected function getChaptersConfig(): array {
    return [
      [
        'id' => 'backend',
        'title' => 'Painel Administrativo (Backend)',
        'description' => 'Aprenda a gerir o conteúdo do site através do painel de administração Drupal.',
        'folder' => 'Backend',
        'videos' => [
          [
            'filename' => 'Editar - Páginas Contacto e Inicial.mp4',
            'title' => 'Editar Páginas — Contacto e Inicial',
            'description' => 'Como editar o conteúdo das páginas de contacto e da página inicial do site.',
          ],
          [
            'filename' => 'Editar e Criar - Blog.mp4',
            'title' => 'Editar e Criar — Blog',
            'description' => 'Como criar e editar artigos no blog do site.',
          ],
          [
            'filename' => 'Editar e Criar - Cursos.mp4',
            'title' => 'Editar e Criar — Cursos',
            'description' => 'Como gerir os cursos disponíveis na plataforma.',
          ],
          [
            'filename' => 'Criar Módulos - Cursos.mp4',
            'title' => 'Criar Módulos — Cursos',
            'description' => 'Como adicionar e organizar módulos dentro de cada curso.',
          ],
          [
            'filename' => 'Editar e Criar - Produtos.mp4',
            'title' => 'Editar e Criar — Produtos',
            'description' => 'Como gerir os produtos disponíveis na loja.',
          ],
          [
            'filename' => 'Editar e Criar - Formulários de Incentivos.mp4',
            'title' => 'Editar e Criar — Formulários de Incentivos',
            'description' => 'Como criar e configurar formulários de incentivos para os utilizadores.',
          ],
          [
            'filename' => 'Editar e Criar - Tipos de Formulários.mp4',
            'title' => 'Editar e Criar — Tipos de Formulários',
            'description' => 'Como gerir os diferentes tipos de formulários dinâmicos.',
          ],
          [
            'filename' => 'Editar e Criar - Perfis de Utilizadores.mp4',
            'title' => 'Editar e Criar — Perfis de Utilizadores',
            'description' => 'Como gerir e editar os perfis dos utilizadores registados.',
          ],
          [
            'filename' => 'Atribuição Técnico.mp4',
            'title' => 'Atribuição de Técnico',
            'description' => 'Como atribuir um técnico responsável a uma submissão de formulário.',
          ],
        ],
      ],
      [
        'id' => 'frontend',
        'title' => 'Gestão de Processos (Frontend)',
        'description' => 'Aprenda a gerir submissões e processos através da interface do site.',
        'folder' => 'Frontend',
        'videos' => [
          [
            'filename' => 'Gestão Processos - Admin.mp4',
            'title' => 'Gestão de Processos — Administrador',
            'description' => 'Como o administrador gere os processos de submissão dos clientes.',
          ],
          [
            'filename' => 'Gestão Processos - Técnico.mp4',
            'title' => 'Gestão de Processos — Técnico',
            'description' => 'Como o técnico trabalha com os processos que lhe foram atribuídos.',
          ],
        ],
      ],
    ];
  }

  /**
   * GET /api/tutorials
   *
   * Lists all tutorial chapters and their videos.
   */
  public function listTutorials() {
    $current_user = \Drupal::currentUser();
    $is_admin = $current_user->hasPermission('administer site configuration');
    $is_tecnico = in_array('tecnico', $current_user->getRoles());

    if (!$is_admin && !$is_tecnico) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    $base_path = $this->getBasePath();
    $chapters_config = $this->getChaptersConfig();

    $chapters = [];
    foreach ($chapters_config as $chapter) {
      $chapter_path = $base_path . '/' . $chapter['folder'];

      $videos = [];
      foreach ($chapter['videos'] as $index => $video) {
        $file_path = $chapter_path . '/' . $video['filename'];
        if (file_exists($file_path)) {
          $videos[] = [
            'index' => $index + 1,
            'title' => $video['title'],
            'description' => $video['description'],
            'filename' => $video['filename'],
            'size' => filesize($file_path),
            'size_human' => $this->formatBytes(filesize($file_path)),
            'stream_url' => '/api/tutorial-video/' . urlencode($chapter['folder']) . '/' . urlencode($video['filename']),
          ];
        }
      }

      if (!empty($videos)) {
        $chapters[] = [
          'id' => $chapter['id'],
          'title' => $chapter['title'],
          'description' => $chapter['description'],
          'video_count' => count($videos),
          'videos' => $videos,
        ];
      }
    }

    return new JsonResponse([
      'chapters' => $chapters,
      'total_videos' => array_sum(array_column($chapters, 'video_count')),
    ]);
  }

  /**
   * GET /api/tutorial-video/{chapter}/{filename}
   *
   * Streams a tutorial video file with range request support.
   */
  public function streamVideo($chapter, $filename, Request $request) {
    $current_user = \Drupal::currentUser();
    $is_admin = $current_user->hasPermission('administer site configuration');
    $is_tecnico = in_array('tecnico', $current_user->getRoles());

    if (!$is_admin && !$is_tecnico) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    // Sanitize inputs to prevent directory traversal.
    $chapter = basename(urldecode($chapter));
    $filename = basename(urldecode($filename));

    $allowed_chapters = ['Backend', 'Frontend'];
    if (!in_array($chapter, $allowed_chapters)) {
      return new JsonResponse(['error' => 'Invalid chapter'], 400);
    }

    $file_path = $this->getBasePath() . '/' . $chapter . '/' . $filename;

    if (!file_exists($file_path) || !is_file($file_path)) {
      return new JsonResponse(['error' => 'Video not found'], 404);
    }

    // Verify it's an mp4 file.
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($extension !== 'mp4') {
      return new JsonResponse(['error' => 'Invalid file type'], 400);
    }

    $response = new BinaryFileResponse($file_path);
    $response->headers->set('Content-Type', 'video/mp4');
    $response->headers->set('Accept-Ranges', 'bytes');
    $response->headers->set('Cache-Control', 'private, max-age=86400');
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);

    return $response;
  }

  /**
   * Format bytes to human readable string.
   */
  protected function formatBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
      $bytes /= 1024;
      $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
  }

}
