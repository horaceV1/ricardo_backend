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

/* themes/contrib/belgrade/templates/layout/region--sidebar-first.html.twig */
class __TwigTemplate_387e8f4be62e81bf058b86b42d3c4827 extends Template
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
        $_v0 = ('' === $tmp = implode('', iterator_to_array((function () use (&$context, $macros, $blocks) {
            // line 24
            yield "  ";
            $context["container"] = \Drupal\Component\Utility\Html::getClass(($context["region_container"] ?? null));
            // line 25
            yield "
  ";
            // line 26
            if ((($tmp = ($context["content"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 27
                yield "    <aside";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 27), "html", null, true);
                yield ">
      ";
                // line 28
                if ((($tmp = ($context["region_container"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 29
                    yield "      <div class=\"";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["container"] ?? null), "html", null, true);
                    yield "\">
        ";
                }
                // line 31
                yield "          ";
                yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
                // line 34
                yield "        ";
                if ((($tmp = ($context["region_container"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 35
                    yield "      </div>
      ";
                }
                // line 37
                yield "    </aside>
  ";
            }
            yield from [];
        })(), false))) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 23
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(Twig\Extension\CoreExtension::spaceless($_v0));
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["region", "region_container", "content", "attributes"]);        yield from [];
    }

    // line 31
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 32
        yield "            ";
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
        return "themes/contrib/belgrade/templates/layout/region--sidebar-first.html.twig";
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
        return array (  106 => 32,  99 => 31,  93 => 23,  87 => 37,  83 => 35,  80 => 34,  77 => 31,  71 => 29,  69 => 28,  64 => 27,  62 => 26,  59 => 25,  56 => 24,  54 => 23,  51 => 22,  49 => 19,  48 => 17,  45 => 15,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/layout/region--sidebar-first.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/layout/region--sidebar-first.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 17, "apply" => 23, "if" => 26, "block" => 31];
        static $filters = ["clean_class" => 19, "escape" => 27, "spaceless" => 23];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'apply', 'if', 'block'],
                ['clean_class', 'escape', 'spaceless'],
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
