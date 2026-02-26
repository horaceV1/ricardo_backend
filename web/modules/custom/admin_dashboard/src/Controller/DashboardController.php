<?php

namespace Drupal\admin_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for the admin dashboard page.
 */
class DashboardController extends ControllerBase {

  /**
   * Builds the admin dashboard page.
   *
   * @return array
   *   A render array for the dashboard.
   */
  public function build(): array {
    $panels = $this->getDashboardPanels();

    $build = [
      '#theme' => 'admin_dashboard',
      '#panels' => $panels,
      '#attached' => [
        'library' => ['admin_dashboard/dashboard'],
      ],
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    return $build;
  }

  /**
   * Returns the dashboard panel definitions.
   *
   * @return array
   *   An array of panel definitions.
   */
  protected function getDashboardPanels(): array {
    $panels = [];

    // Blog Posts (content type: curso).
    $panels[] = [
      'title' => $this->t('Criar / Editar Artigos do Blog'),
      'description' => $this->t('Gerir publicações do blog, criar novos artigos e editar os existentes.'),
      'icon' => 'edit_note',
      'url' => Url::fromRoute('system.admin_content', [], ['query' => ['type' => 'curso']]),
      'add_url' => Url::fromRoute('node.add', ['node_type' => 'curso']),
      'add_label' => $this->t('Novo Artigo'),
      'color' => 'blue',
    ];

    // Dynamic Form Types.
    $panels[] = [
      'title' => $this->t('Criar / Editar Tipos de Formulários'),
      'description' => $this->t('Configurar e gerir os tipos de formulários dinâmicos disponíveis.'),
      'icon' => 'dynamic_form',
      'url' => Url::fromRoute('entity.dynamic_form.collection'),
      'add_url' => Url::fromRoute('entity.dynamic_form.add_form'),
      'add_label' => $this->t('Novo Formulário'),
      'color' => 'purple',
    ];

    // Incentive Forms (content type: article / Formulário Incentivos).
    $panels[] = [
      'title' => $this->t('Criar / Editar Formulários de Incentivos'),
      'description' => $this->t('Gerir formulários de incentivos e as suas páginas associadas.'),
      'icon' => 'description',
      'url' => Url::fromRoute('system.admin_content', [], ['query' => ['type' => 'article']]),
      'add_url' => Url::fromRoute('node.add', ['node_type' => 'article']),
      'add_label' => $this->t('Novo Formulário'),
      'color' => 'emerald',
    ];

    // Courses (content type: cursos).
    $panels[] = [
      'title' => $this->t('Criar / Editar Cursos'),
      'description' => $this->t('Gerir os cursos disponíveis na plataforma de formação.'),
      'icon' => 'school',
      'url' => Url::fromRoute('system.admin_content', [], ['query' => ['type' => 'cursos']]),
      'add_url' => Url::fromRoute('node.add', ['node_type' => 'cursos']),
      'add_label' => $this->t('Novo Curso'),
      'color' => 'amber',
    ];

    // User Profiles.
    $panels[] = [
      'title' => $this->t('Gerir Perfis de Utilizadores'),
      'description' => $this->t('Administrar contas de utilizadores, permissões e perfis.'),
      'icon' => 'people',
      'url' => Url::fromRoute('entity.user.collection'),
      'add_url' => Url::fromRoute('user.admin_create'),
      'add_label' => $this->t('Novo Utilizador'),
      'color' => 'cyan',
    ];

    // Submission Assignments (Attributions).
    $panels[] = [
      'title' => $this->t('Gerir Atribuições'),
      'description' => $this->t('Visualizar e gerir as atribuições de submissões a funcionários.'),
      'icon' => 'assignment_ind',
      'url' => Url::fromRoute('submission_assignment.admin_overview'),
      'add_url' => NULL,
      'add_label' => NULL,
      'color' => 'rose',
    ];

    // Commerce Products.
    $panels[] = [
      'title' => $this->t('Criar / Editar Produtos'),
      'description' => $this->t('Gerir o catálogo de produtos, preços e variações.'),
      'icon' => 'shopping_cart',
      'url' => Url::fromRoute('entity.commerce_product.collection'),
      'add_url' => Url::fromRoute('entity.commerce_product.add_page'),
      'add_label' => $this->t('Novo Produto'),
      'color' => 'orange',
    ];

    // Homepage Manager.
    $panels[] = [
      'title' => $this->t('Editar Página Inicial'),
      'description' => $this->t('Gerir o conteúdo da página inicial do site (hero, estatísticas, funcionalidades, etc.).'),
      'icon' => 'home',
      'url' => Url::fromRoute('system.admin_content', [], ['query' => ['type' => 'homepage']]),
      'add_url' => Url::fromRoute('node.add', ['node_type' => 'homepage']),
      'add_label' => $this->t('Nova Página Inicial'),
      'color' => 'indigo',
    ];

    // Contact Page Manager.
    $panels[] = [
      'title' => $this->t('Editar Página de Contacto'),
      'description' => $this->t('Gerir o conteúdo da página de contacto (informações, FAQ, mapa, etc.).'),
      'icon' => 'contact_mail',
      'url' => Url::fromRoute('system.admin_content', [], ['query' => ['type' => 'contact_page']]),
      'add_url' => Url::fromRoute('node.add', ['node_type' => 'contact_page']),
      'add_label' => $this->t('Nova Página de Contacto'),
      'color' => 'teal',
    ];

    return $panels;
  }

}
