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

/* themes/contrib/belgrade/templates/layout/region--navigation.html.twig */
class __TwigTemplate_f500f4a19e3af36cb14ae99202e3e5de extends Template
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
            'content' => [$this, 'block_content'],
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 16
        $context["classes"] = ["offcanvas", "region", ("region-" . \Drupal\Component\Utility\Html::getClass(        // line 19
($context["region"] ?? null))), (((($tmp =         // line 20
($context["navigation_position"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (("offcanvas-" . ($context["navigation_position"] ?? null))) : ("")), "ps-md-3"];
        // line 24
        yield "
";
        // line 25
        $context["container"] = \Drupal\Component\Utility\Html::getClass(($context["region_container"] ?? null));
        // line 26
        yield "
";
        // line 27
        if ((($tmp = ($context["content"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 28
            yield "  <section";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 28), "html", null, true);
            yield " data-bs-scroll=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["navigation_body_scrolling"] ?? null), "html", null, true);
            yield "\" data-bs-backdrop=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["navigation_backdrop"] ?? null), "html", null, true);
            yield "\" tabindex=\"-1\" id=\"navigationRegion\" aria-labelledby=\"navigationRegionLabel\">
    <div class=\"offcanvas-header\">
      <a href=\"";
            // line 30
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
            yield "\" rel=\"home\" class=\"site-logo\">
      ";
            // line 31
            if ((($tmp = ($context["inline_logo"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 32
                yield "        ";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(Twig\Extension\CoreExtension::source($this->env, ($context["site_logo"] ?? null), true));
                yield "
      ";
            } else {
                // line 34
                yield "        <img src=\"";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_logo"] ?? null), "html", null, true);
                yield "\" alt=\"";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Home"));
                yield "\" />
      ";
            }
            // line 36
            yield "      </a>
      <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"offcanvas\" aria-label=\"Close\"></button>
    </div>
    <div class=\"offcanvas-body\">
      ";
            // line 40
            yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
            // line 43
            yield "    </div>
  </section>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["region", "navigation_position", "region_container", "content", "attributes", "navigation_body_scrolling", "navigation_backdrop", "inline_logo", "site_logo"]);        yield from [];
    }

    // line 40
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 41
        yield "        ";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["content"] ?? null), "html", null, true);
        yield "
      ";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/belgrade/templates/layout/region--navigation.html.twig";
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
        return array (  113 => 41,  106 => 40,  97 => 43,  95 => 40,  89 => 36,  81 => 34,  75 => 32,  73 => 31,  69 => 30,  59 => 28,  57 => 27,  54 => 26,  52 => 25,  49 => 24,  47 => 20,  46 => 19,  45 => 16,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/layout/region--navigation.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/layout/region--navigation.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 16, "if" => 27, "block" => 40];
        static $filters = ["clean_class" => 19, "escape" => 28, "t" => 34];
        static $functions = ["path" => 30, "source" => 32];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'block'],
                ['clean_class', 'escape', 't'],
                ['path', 'source'],
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
