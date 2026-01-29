<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* page.html.twig */
class __TwigTemplate_4e672e2e121c715f39ef0d904b53df75 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'skip_link' => [$this, 'block_skip_link'],
            'top_bar' => [$this, 'block_top_bar'],
            'header' => [$this, 'block_header'],
            'navigation' => [$this, 'block_navigation'],
            'highlighted' => [$this, 'block_highlighted'],
            'help' => [$this, 'block_help'],
            'messages' => [$this, 'block_messages'],
            'tabs' => [$this, 'block_tabs'],
            'action_links' => [$this, 'block_action_links'],
            'main' => [$this, 'block_main'],
            'footer' => [$this, 'block_footer'],
            'page_bottom' => [$this, 'block_page_bottom'],
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 44
        yield "
<div";
        // line 45
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
        yield ">
  ";
        // line 46
        yield from $this->unwrap()->yieldBlock('skip_link', $context, $blocks);
        // line 51
        yield "
  ";
        // line 52
        yield from $this->unwrap()->yieldBlock('top_bar', $context, $blocks);
        // line 57
        yield "
  ";
        // line 58
        yield from $this->unwrap()->yieldBlock('header', $context, $blocks);
        // line 65
        yield "
  ";
        // line 66
        yield from $this->unwrap()->yieldBlock('navigation', $context, $blocks);
        // line 73
        yield "
  ";
        // line 74
        yield from $this->unwrap()->yieldBlock('highlighted', $context, $blocks);
        // line 79
        yield "
  ";
        // line 80
        yield from $this->unwrap()->yieldBlock('help', $context, $blocks);
        // line 85
        yield "
  ";
        // line 86
        yield from $this->unwrap()->yieldBlock('messages', $context, $blocks);
        // line 91
        yield "
  ";
        // line 92
        yield from $this->unwrap()->yieldBlock('tabs', $context, $blocks);
        // line 101
        yield "
  ";
        // line 102
        yield from $this->unwrap()->yieldBlock('action_links', $context, $blocks);
        // line 107
        yield "
  ";
        // line 108
        yield from $this->unwrap()->yieldBlock('main', $context, $blocks);
        // line 131
        yield "
  ";
        // line 132
        yield from $this->unwrap()->yieldBlock('footer', $context, $blocks);
        // line 139
        yield "
  ";
        // line 140
        yield from $this->unwrap()->yieldBlock('page_bottom', $context, $blocks);
        // line 145
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["attributes", "page", "page_top", "primary_local_tasks", "secondary_local_tasks", "action_links", "main_container", "page_bottom"]);        yield from [];
    }

    // line 46
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_skip_link(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 47
        yield "    <a href=\"#main-content\" class=\"visually-hidden-focusable\">
      ";
        // line 48
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Skip to main content"));
        yield "
    </a>
  ";
        yield from [];
    }

    // line 52
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_top_bar(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 53
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "top_bar", [], "any", false, false, true, 53)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 54
            yield "      ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "top_bar", [], "any", false, false, true, 54), "html", null, true);
            yield "
    ";
        }
        // line 56
        yield "  ";
        yield from [];
    }

    // line 58
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_header(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 59
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header", [], "any", false, false, true, 59)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 60
            yield "      <header role=\"banner\">
        ";
            // line 61
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header", [], "any", false, false, true, 61), "html", null, true);
            yield "
      </header>
    ";
        }
        // line 64
        yield "  ";
        yield from [];
    }

    // line 66
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_navigation(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 67
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "navigation", [], "any", false, false, true, 67)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 68
            yield "      <nav role=\"navigation\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Main navigation"));
            yield "\">
        ";
            // line 69
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "navigation", [], "any", false, false, true, 69), "html", null, true);
            yield "
      </nav>
    ";
        }
        // line 72
        yield "  ";
        yield from [];
    }

    // line 74
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_highlighted(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 75
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "highlighted", [], "any", false, false, true, 75)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 76
            yield "      ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "highlighted", [], "any", false, false, true, 76), "html", null, true);
            yield "
    ";
        }
        // line 78
        yield "  ";
        yield from [];
    }

    // line 80
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_help(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 81
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "help", [], "any", false, false, true, 81)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 82
            yield "      ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "help", [], "any", false, false, true, 82), "html", null, true);
            yield "
    ";
        }
        // line 84
        yield "  ";
        yield from [];
    }

    // line 86
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_messages(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 87
        yield "    ";
        if ((($tmp = ($context["page_top"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 88
            yield "      ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["page_top"] ?? null), "html", null, true);
            yield "
    ";
        }
        // line 90
        yield "  ";
        yield from [];
    }

    // line 92
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_tabs(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 93
        yield "    ";
        if ((($tmp = ($context["primary_local_tasks"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 94
            yield "      ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["primary_local_tasks"] ?? null), "html", null, true);
            yield "
    ";
        }
        // line 96
        yield "
    ";
        // line 97
        if ((($tmp = ($context["secondary_local_tasks"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 98
            yield "      ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["secondary_local_tasks"] ?? null), "html", null, true);
            yield "
    ";
        }
        // line 100
        yield "  ";
        yield from [];
    }

    // line 102
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_action_links(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 103
        yield "    ";
        if ((($tmp = ($context["action_links"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 104
            yield "      ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["action_links"] ?? null), "html", null, true);
            yield "
    ";
        }
        // line 106
        yield "  ";
        yield from [];
    }

    // line 108
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_main(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 109
        yield "    <main id=\"main-content\" role=\"main\">
      ";
        // line 110
        if ((($tmp = ($context["main_container"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 111
            yield "        <div class=\"main-container ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["main_container"] ?? null), "html", null, true);
            yield "\">
      ";
        }
        // line 113
        yield "
        <div class=\"row\">
          ";
        // line 115
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "content", [], "any", false, false, true, 115), "html", null, true);
        yield "

          ";
        // line 117
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 117)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 118
            yield "            ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 118), "html", null, true);
            yield "
          ";
        }
        // line 120
        yield "
          ";
        // line 121
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 121)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 122
            yield "            ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 122), "html", null, true);
            yield "
          ";
        }
        // line 124
        yield "        </div>

      ";
        // line 126
        if ((($tmp = ($context["main_container"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 127
            yield "        </div>
      ";
        }
        // line 129
        yield "    </main>
  ";
        yield from [];
    }

    // line 132
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_footer(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 133
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer", [], "any", false, false, true, 133)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 134
            yield "      <footer role=\"contentinfo\">
        ";
            // line 135
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer", [], "any", false, false, true, 135), "html", null, true);
            yield "
      </footer>
    ";
        }
        // line 138
        yield "  ";
        yield from [];
    }

    // line 140
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_page_bottom(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 141
        yield "    ";
        if ((($tmp = ($context["page_bottom"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 142
            yield "      ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["page_bottom"] ?? null), "html", null, true);
            yield "
    ";
        }
        // line 144
        yield "  ";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "page.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  434 => 144,  428 => 142,  425 => 141,  418 => 140,  413 => 138,  407 => 135,  404 => 134,  401 => 133,  394 => 132,  388 => 129,  384 => 127,  382 => 126,  378 => 124,  372 => 122,  370 => 121,  367 => 120,  361 => 118,  359 => 117,  354 => 115,  350 => 113,  344 => 111,  342 => 110,  339 => 109,  332 => 108,  327 => 106,  321 => 104,  318 => 103,  311 => 102,  306 => 100,  300 => 98,  298 => 97,  295 => 96,  289 => 94,  286 => 93,  279 => 92,  274 => 90,  268 => 88,  265 => 87,  258 => 86,  253 => 84,  247 => 82,  244 => 81,  237 => 80,  232 => 78,  226 => 76,  223 => 75,  216 => 74,  211 => 72,  205 => 69,  200 => 68,  197 => 67,  190 => 66,  185 => 64,  179 => 61,  176 => 60,  173 => 59,  166 => 58,  161 => 56,  155 => 54,  152 => 53,  145 => 52,  137 => 48,  134 => 47,  127 => 46,  120 => 145,  118 => 140,  115 => 139,  113 => 132,  110 => 131,  108 => 108,  105 => 107,  103 => 102,  100 => 101,  98 => 92,  95 => 91,  93 => 86,  90 => 85,  88 => 80,  85 => 79,  83 => 74,  80 => 73,  78 => 66,  75 => 65,  73 => 58,  70 => 57,  68 => 52,  65 => 51,  63 => 46,  59 => 45,  56 => 44,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "page.html.twig", "themes/contrib/belgrade/templates/page/page.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["block" => 46, "if" => 53];
        static $filters = ["escape" => 45, "t" => 48];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['block', 'if'],
                ['escape', 't'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
