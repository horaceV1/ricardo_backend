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

/* themes/contrib/belgrade/templates/form/container.html.twig */
class __TwigTemplate_54da32b40f4139cb688e11779cb80d0b extends Template
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
        // line 23
        $context["classes"] = [(((($tmp =         // line 24
($context["has_parent"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("js-form-wrapper") : ("")), (((($tmp =         // line 25
($context["has_parent"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("form-wrapper") : ("")), (((($tmp = (($_v0 =         // line 26
($context["attributes"] ?? null)) && is_array($_v0) || $_v0 instanceof ArrayAccess && in_array($_v0::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v0["data-drupal-selector"] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "data-drupal-selector", [], "array", false, false, true, 26))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (("container--" . (($_v1 = ($context["attributes"] ?? null)) && is_array($_v1) || $_v1 instanceof ArrayAccess && in_array($_v1::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v1["data-drupal-selector"] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "data-drupal-selector", [], "array", false, false, true, 26)))) : (""))];
        // line 29
        yield "<div";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 29), "html", null, true);
        yield ">";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["children"] ?? null), "html", null, true);
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["has_parent", "attributes", "children"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/belgrade/templates/form/container.html.twig";
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
        return array (  49 => 29,  47 => 26,  46 => 25,  45 => 24,  44 => 23,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/form/container.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/form/container.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 23];
        static $filters = ["escape" => 29];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set'],
                ['escape'],
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
