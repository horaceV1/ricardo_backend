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

/* modules/contrib/bootstrap_layout_builder/templates/blb-section.html.twig */
class __TwigTemplate_c6542d63778ce96cd572b52e641c93b4 extends Template
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
        if ((($tmp = ($context["content"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 14
            yield "  ";
            $context["classes"] = ["layout", "row", ((((($_v0 = (($_v1 =             // line 17
($context["content"] ?? null)) && is_array($_v1) || $_v1 instanceof ArrayAccess && in_array($_v1::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v1["#settings"] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "#settings", [], "array", false, false, true, 17))) && is_array($_v0) || $_v0 instanceof ArrayAccess && in_array($_v0::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v0["remove_gutters"] ?? null) : CoreExtension::getAttribute($this->env, $this->source, (($_v2 = ($context["content"] ?? null)) && is_array($_v2) || $_v2 instanceof ArrayAccess && in_array($_v2::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v2["#settings"] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "#settings", [], "array", false, false, true, 17)), "remove_gutters", [], "array", false, false, true, 17)) === "1")) ? ("no-gutters") : ("")), "layout-builder__layout"];
            // line 20
            yield "
  <div ";
            // line 21
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 21), "html", null, true);
            yield ">
    ";
            // line 22
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_prefix"] ?? null), "html", null, true);
            yield "
    ";
            // line 23
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(range(1, 12));
            foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
                // line 24
                yield "      ";
                $context["region"] = ("blb_region_col_" . $context["i"]);
                // line 25
                yield "      ";
                if ((($tmp = (($_v3 = ($context["content"] ?? null)) && is_array($_v3) || $_v3 instanceof ArrayAccess && in_array($_v3::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v3[($context["region"] ?? null)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), ($context["region"] ?? null), [], "array", false, false, true, 25))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 26
                    yield "        <div ";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($_v4 = ($context["region_attributes"] ?? null)) && is_array($_v4) || $_v4 instanceof ArrayAccess && in_array($_v4::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v4[($context["region"] ?? null)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["region_attributes"] ?? null), ($context["region"] ?? null), [], "array", false, false, true, 26)), "html", null, true);
                    yield ">
          ";
                    // line 27
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($_v5 = ($context["content"] ?? null)) && is_array($_v5) || $_v5 instanceof ArrayAccess && in_array($_v5::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v5[($context["region"] ?? null)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), ($context["region"] ?? null), [], "array", false, false, true, 27)), "html", null, true);
                    yield "
        </div>
      ";
                }
                // line 30
                yield "    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['i'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 31
            yield "    ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_suffix"] ?? null), "html", null, true);
            yield "
  </div>

";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["content", "attributes", "title_prefix", "region_attributes", "title_suffix"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/bootstrap_layout_builder/templates/blb-section.html.twig";
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
        return array (  88 => 31,  82 => 30,  76 => 27,  71 => 26,  68 => 25,  65 => 24,  61 => 23,  57 => 22,  53 => 21,  50 => 20,  48 => 17,  46 => 14,  44 => 13,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/contrib/bootstrap_layout_builder/templates/blb-section.html.twig", "/var/www/html/web/modules/contrib/bootstrap_layout_builder/templates/blb-section.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 13, "set" => 14, "for" => 23];
        static $filters = ["escape" => 21];
        static $functions = ["range" => 23];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'set', 'for'],
                ['escape'],
                ['range'],
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
