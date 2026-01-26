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

/* themes/contrib/belgrade/templates/page/page--path--frontpage.html.twig */
class __TwigTemplate_55379805fe8d8215a00fdbdf07551cd8 extends Template
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

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 46
        return "page--node--layout.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 44
        $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("belgrade/page.front");
        // line 46
        $this->parent = $this->load("page--node--layout.html.twig", 46);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/belgrade/templates/page/page--path--frontpage.html.twig";
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
        return array (  50 => 46,  48 => 44,  41 => 46,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/page/page--path--frontpage.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/page/page--path--frontpage.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["do" => 44, "extends" => 46];
        static $filters = [];
        static $functions = ["attach_library" => 44];

        try {
            $this->sandbox->checkSecurity(
                ['do', 'extends'],
                [],
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
