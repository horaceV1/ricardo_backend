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

/* core/modules/filter/templates/filter-tips.html.twig */
class __TwigTemplate_e492ec712962a29748326844ddea19ec extends Template
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
        // line 21
        if ((($tmp = ($context["multiple"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 22
            yield "  <h2>";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Text Formats"));
            yield "</h2>
";
        }
        // line 24
        yield "
";
        // line 25
        if ((($tmp = Twig\Extension\CoreExtension::length($this->env->getCharset(), ($context["tips"] ?? null))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 26
            yield "  ";
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["tips"] ?? null));
            foreach ($context['_seq'] as $context["name"] => $context["tip"]) {
                // line 27
                yield "
    ";
                // line 28
                if ((($tmp = ($context["multiple"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 29
                    yield "      <div";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
                    yield ">
      <h3>";
                    // line 30
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["tip"], "name", [], "any", false, false, true, 30), "html", null, true);
                    yield "</h3>
    ";
                }
                // line 32
                yield "
    ";
                // line 33
                if ((($tmp = Twig\Extension\CoreExtension::length($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, $context["tip"], "list", [], "any", false, false, true, 33))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 34
                    yield "      <ul>
      ";
                    // line 35
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["tip"], "list", [], "any", false, false, true, 35));
                    foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                        // line 36
                        yield "        <li";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["tip"], "attributes", [], "any", false, false, true, 36), "html", null, true);
                        yield ">";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "tip", [], "any", false, false, true, 36), "html", null, true);
                        yield "</li>
      ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 38
                    yield "      </ul>
    ";
                }
                // line 40
                yield "
    ";
                // line 41
                if ((($tmp = ($context["multiple"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 42
                    yield "      </div>
    ";
                }
                // line 44
                yield "
  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['name'], $context['tip'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["multiple", "tips", "attributes"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/modules/filter/templates/filter-tips.html.twig";
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
        return array (  113 => 44,  109 => 42,  107 => 41,  104 => 40,  100 => 38,  89 => 36,  85 => 35,  82 => 34,  80 => 33,  77 => 32,  72 => 30,  67 => 29,  65 => 28,  62 => 27,  57 => 26,  55 => 25,  52 => 24,  46 => 22,  44 => 21,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/modules/filter/templates/filter-tips.html.twig", "/var/www/html/web/core/modules/filter/templates/filter-tips.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 21, "for" => 26];
        static $filters = ["t" => 22, "length" => 25, "escape" => 29];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'for'],
                ['t', 'length', 'escape'],
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
