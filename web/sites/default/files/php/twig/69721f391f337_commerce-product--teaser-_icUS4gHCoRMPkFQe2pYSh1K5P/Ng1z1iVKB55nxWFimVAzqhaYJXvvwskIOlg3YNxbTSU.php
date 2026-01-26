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

/* themes/contrib/belgrade/templates/commerce/teasers/commerce-product--teaser--belgrade.html.twig */
class __TwigTemplate_deb413a958e3a5a216e85dfe600f9ea1 extends Template
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
        // line 22
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("belgrade/product.teaser.belgrade"), "html", null, true);
        yield "

";
        // line 24
        $macros["svg"] = $this->macros["svg"] = $this->load("@belgrade/macros.twig", 24)->unwrap();
        // line 25
        yield "
";
        // line 27
        $context["classes"] = ["product", "position-relative", "mb-3"];
        // line 33
        yield "
<article";
        // line 34
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 34), "html", null, true);
        yield ">
  <div class=\"product-image position-relative text-white bg-primary\">
    <div class=\"product-hover d-flex flex-column align-items-center justify-content-center position-absolute top-0 w-100 h-100\">
      ";
        // line 37
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["svg"]->getTemplateForMacro("macro_getIcon", $context, 37, $this->getSourceContext())->macro_getIcon(...["eye-fill", 34, 34, "mb-2"]));
        yield "
      <div class=\"fs-md\">";
        // line 38
        yield "View product";
        yield "</div>
    </div>";
        // line 40
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["product"] ?? null), "images", [], "any", false, false, true, 40), "html", null, true);
        // line 41
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["product"] ?? null), "variation_images", [], "any", false, false, true, 41), "html", null, true);
        // line 42
        yield "</div>
  <div class=\"product-info d-flex align-items-center justify-content-between\">
    <div class=\"product-title fw-bold\">";
        // line 44
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["product"] ?? null), "title", [], "any", false, false, true, 44), "html", null, true);
        yield "</div>
    <a href=\"";
        // line 45
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["product_url"] ?? null), "html", null, true);
        yield "\" class=\"stretched-link\"></a>";
        // line 46
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->withoutFilter(($context["product"] ?? null), "title", "variation_attributes", "images", "variation_images"), "html", null, true);
        // line 47
        yield "</div>
</article>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["attributes", "product", "product_url"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/belgrade/templates/commerce/teasers/commerce-product--teaser--belgrade.html.twig";
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
        return array (  90 => 47,  88 => 46,  85 => 45,  81 => 44,  77 => 42,  75 => 41,  73 => 40,  69 => 38,  65 => 37,  59 => 34,  56 => 33,  54 => 27,  51 => 25,  49 => 24,  44 => 22,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/commerce/teasers/commerce-product--teaser--belgrade.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/commerce/teasers/commerce-product--teaser--belgrade.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["import" => 24, "set" => 27];
        static $filters = ["escape" => 22, "without" => 46];
        static $functions = ["attach_library" => 22];

        try {
            $this->sandbox->checkSecurity(
                ['import', 'set'],
                ['escape', 'without'],
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
