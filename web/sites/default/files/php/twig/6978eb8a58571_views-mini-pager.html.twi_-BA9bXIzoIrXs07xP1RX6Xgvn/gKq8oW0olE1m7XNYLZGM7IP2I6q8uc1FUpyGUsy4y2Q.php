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

/* themes/contrib/belgrade/templates/views/views-mini-pager.html.twig */
class __TwigTemplate_a023d6cf99d6e5dadc1545b605d3db97 extends Template
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
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 13
        yield "
";
        // line 14
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("belgrade/mini-pager"), "html", null, true);
        yield "

";
        // line 16
        if ((CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "previous", [], "any", false, false, true, 16) || CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "next", [], "any", false, false, true, 16))) {
            // line 17
            yield "  <nav class=\"mini-pager\" role=\"navigation\" aria-labelledby=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["heading_id"] ?? null), "html", null, true);
            yield "\">
    <h4 id=\"";
            // line 18
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["heading_id"] ?? null), "html", null, true);
            yield "\" class=\"visually-hidden\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Pagination"));
            yield "</h4>
    <ul class=\"js-pager__items pagination\">
      <li class=\"page-item ";
            // line 20
            if ((($tmp =  !CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "previous", [], "any", false, false, true, 20)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "disabled";
            }
            yield "\">
        <a href=\"";
            // line 21
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "previous", [], "any", false, false, true, 21), "href", [], "any", false, false, true, 21), "html", null, true);
            yield "\" class=\"page-link\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Go to previous page"));
            yield "\" rel=\"prev\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->withoutFilter(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "previous", [], "any", false, false, true, 21), "attributes", [], "any", false, false, true, 21), "href", "title", "rel"), "html", null, true);
            yield ">
          <span>";
            // line 22
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Previous page"));
            yield "</span>
        </a>
      </li>
      ";
            // line 25
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "current", [], "any", false, false, true, 25)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 26
                yield "        <li class=\"page-item\">
          <span class=\"page-link\">";
                // line 27
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "current", [], "any", false, false, true, 27), "html", null, true);
                yield "</span>
        </li>
      ";
            }
            // line 30
            yield "      <li class=\"page-item ";
            if ((($tmp =  !CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "next", [], "any", false, false, true, 30)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "disabled";
            }
            yield "\">
        <a href=\"";
            // line 31
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "next", [], "any", false, false, true, 31), "href", [], "any", false, false, true, 31), "html", null, true);
            yield "\" class=\"page-link\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Go to next page"));
            yield "\" rel=\"next\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->withoutFilter(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["items"] ?? null), "next", [], "any", false, false, true, 31), "attributes", [], "any", false, false, true, 31), "href", "title", "rel"), "html", null, true);
            yield ">
          <span>";
            // line 32
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Next page"));
            yield "</span>
        </a>
      </li>
    </ul>
  </nav>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["items", "heading_id"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/belgrade/templates/views/views-mini-pager.html.twig";
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
        return array (  112 => 32,  104 => 31,  97 => 30,  91 => 27,  88 => 26,  86 => 25,  80 => 22,  72 => 21,  66 => 20,  59 => 18,  54 => 17,  52 => 16,  47 => 14,  44 => 13,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/views/views-mini-pager.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/views/views-mini-pager.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 16];
        static $filters = ["escape" => 14, "t" => 18, "without" => 21];
        static $functions = ["attach_library" => 14];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape', 't', 'without'],
                ['attach_library'],
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
