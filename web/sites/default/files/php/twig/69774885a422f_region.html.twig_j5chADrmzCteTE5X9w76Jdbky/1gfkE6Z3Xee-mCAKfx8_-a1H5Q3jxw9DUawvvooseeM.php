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

/* @belgrade/layout/region.html.twig */
class __TwigTemplate_224ebdfaf2da8c6dbef3300c9fa2b7cf extends Template
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
        // line 15
        yield "
";
        // line 17
        $context["classes"] = ["region", ("region-" . \Drupal\Component\Utility\Html::getClass(        // line 19
($context["region"] ?? null)))];
        // line 22
        yield "
";
        // line 23
        $context["container"] = \Drupal\Component\Utility\Html::getClass(($context["region_container"] ?? null));
        // line 24
        yield "
";
        // line 25
        if ((($tmp = ($context["content"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 26
            yield "  <section";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 26), "html", null, true);
            yield ">
    ";
            // line 27
            if ((($tmp = ($context["region_container"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 28
                yield "    <div class=\"";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["container"] ?? null), "html", null, true);
                yield "\">
      ";
            }
            // line 30
            yield "        ";
            yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
            // line 33
            yield "      ";
            if ((($tmp = ($context["region_container"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 34
                yield "    </div>
    ";
            }
            // line 36
            yield "  </section>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["region", "region_container", "content", "attributes"]);        yield from [];
    }

    // line 30
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 31
        yield "          ";
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
        return "@belgrade/layout/region.html.twig";
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
        return array (  99 => 31,  92 => 30,  84 => 36,  80 => 34,  77 => 33,  74 => 30,  68 => 28,  66 => 27,  61 => 26,  59 => 25,  56 => 24,  54 => 23,  51 => 22,  49 => 19,  48 => 17,  45 => 15,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@belgrade/layout/region.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/layout/region.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 17, "if" => 25, "block" => 30];
        static $filters = ["clean_class" => 19, "escape" => 26];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'block'],
                ['clean_class', 'escape'],
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
