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

/* themes/contrib/belgrade/templates/navigation/menu--main.html.twig */
class __TwigTemplate_33d6ebb576a438b5b82a834faf89b774 extends Template
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
        // line 25
        $macros["menus"] = $this->macros["menus"] = $this;
        // line 26
        $macros["͜macros"] = $this->macros["͜macros"] = $this->load("@belgrade/macros.twig", 26)->unwrap();
        // line 27
        yield "
";
        // line 29
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("belgrade/main-nav"), "html", null, true);
        yield "

";
        // line 35
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["menus"]->getTemplateForMacro("macro_menu_links", $context, 35, $this->getSourceContext())->macro_menu_links(...[($context["items"] ?? null), ($context["attributes"] ?? null), 0, ($context["menu_name"] ?? null)]));
        yield "

";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["_self", "items", "attributes", "menu_name", "menu_level"]);        yield from [];
    }

    // line 37
    public function macro_menu_links($items = null, $attributes = null, $menu_level = null, $menu_name = null, ...$varargs): string|Markup
    {
        $macros = $this->macros;
        $context = [
            "items" => $items,
            "attributes" => $attributes,
            "menu_level" => $menu_level,
            "menu_name" => $menu_name,
            "varargs" => $varargs,
        ] + $this->env->getGlobals();

        $blocks = [];

        return ('' === $tmp = implode('', iterator_to_array((function () use (&$context, $macros, $blocks) {
            // line 38
            yield "  ";
            $macros["menus"] = $this;
            // line 39
            yield "  ";
            if ((($tmp = ($context["items"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 40
                yield "    ";
                if ((($context["menu_level"] ?? null) == 0)) {
                    // line 41
                    yield "      <ul";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [(($context["menu_name"] ?? null) . "-nav")], "method", false, false, true, 41), "html", null, true);
                    yield ">
    ";
                } elseif ((                // line 42
($context["menu_level"] ?? null) == 1)) {
                    // line 43
                    yield "      <ul class=\"";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($context["menu_name"] ?? null) . "-nav__submenu"), "html", null, true);
                    yield "\">
    ";
                } else {
                    // line 45
                    yield "      <ul class=\"";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($context["menu_name"] ?? null) . "-nav__submenu-nested"), "html", null, true);
                    yield "\">
    ";
                }
                // line 47
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(($context["items"] ?? null));
                foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                    // line 48
                    yield "      ";
                    // line 49
                    $context["classes"] = [(                    // line 50
($context["menu_name"] ?? null) . "-nav__item"), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 51
$context["item"], "below", [], "any", false, false, true, 51)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ((($context["menu_name"] ?? null) . "-nav__item--has-submenu")) : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 52
$context["item"], "is_expanded", [], "any", false, false, true, 52)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ((($context["menu_name"] ?? null) . "-nav__item--expanded")) : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 53
$context["item"], "is_collapsed", [], "any", false, false, true, 53)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ((($context["menu_name"] ?? null) . "-nav__item--collapsed")) : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 54
$context["item"], "in_active_trail", [], "any", false, false, true, 54)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ((($context["menu_name"] ?? null) . "-nav__item--active-trail")) : (""))];
                    // line 57
                    yield "      ";
                    // line 58
                    $context["link_classes"] = [(                    // line 59
($context["menu_name"] ?? null) . "-nav__link"), (((                    // line 60
($context["menu_level"] ?? null) == 0)) ? ((($context["menu_name"] ?? null) . "-nav__link--top-level")) : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 61
$context["item"], "in_active_trail", [], "any", false, false, true, 61)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ((($context["menu_name"] ?? null) . "-nav__link--active")) : (""))];
                    // line 64
                    yield "      <li";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "attributes", [], "any", false, false, true, 64), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 64), "html", null, true);
                    yield ">
        ";
                    // line 65
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below", [], "any", false, false, true, 65)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 66
                        yield "          ";
                        // line 67
                        yield "          ";
                        $context["link_options"] = ["class" =>                         // line 68
($context["link_classes"] ?? null), "aria-expanded" => "false", "aria-haspopup" => "true"];
                        // line 72
                        yield "          ";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->getLink(CoreExtension::getAttribute($this->env, $this->source, $context["item"], "title", [], "any", false, false, true, 72), CoreExtension::getAttribute($this->env, $this->source, $context["item"], "url", [], "any", false, false, true, 72), ($context["link_options"] ?? null)), "html", null, true);
                        yield "
          ";
                        // line 73
                        if ((($context["menu_level"] ?? null) == 0)) {
                            // line 74
                            yield "            ";
                            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["͜macros"]->getTemplateForMacro("macro_getIcon", $context, 74, $this->getSourceContext())->macro_getIcon(...["chevron-down", 12, 12, (($context["menu_name"] ?? null) . "-nav__chevron")]));
                            yield "
          ";
                        } else {
                            // line 76
                            yield "            ";
                            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["͜macros"]->getTemplateForMacro("macro_getIcon", $context, 76, $this->getSourceContext())->macro_getIcon(...["chevron-right", 10, 10, (($context["menu_name"] ?? null) . "-nav__chevron")]));
                            yield "
          ";
                        }
                        // line 78
                        yield "          ";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["menus"]->getTemplateForMacro("macro_menu_links", $context, 78, $this->getSourceContext())->macro_menu_links(...[CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below", [], "any", false, false, true, 78), ($context["attributes"] ?? null), (($context["menu_level"] ?? null) + 1), ($context["menu_name"] ?? null)]));
                        yield "
        ";
                    } else {
                        // line 80
                        yield "          ";
                        // line 81
                        yield "          ";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->getLink(CoreExtension::getAttribute($this->env, $this->source, $context["item"], "title", [], "any", false, false, true, 81), CoreExtension::getAttribute($this->env, $this->source, $context["item"], "url", [], "any", false, false, true, 81), ["class" => ($context["link_classes"] ?? null)]), "html", null, true);
                        yield "
        ";
                    }
                    // line 83
                    yield "      </li>
    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 85
                yield "    </ul>
  ";
            }
            yield from [];
        })(), false))) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/belgrade/templates/navigation/menu--main.html.twig";
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
        return array (  182 => 85,  175 => 83,  169 => 81,  167 => 80,  161 => 78,  155 => 76,  149 => 74,  147 => 73,  142 => 72,  140 => 68,  138 => 67,  136 => 66,  134 => 65,  129 => 64,  127 => 61,  126 => 60,  125 => 59,  124 => 58,  122 => 57,  120 => 54,  119 => 53,  118 => 52,  117 => 51,  116 => 50,  115 => 49,  113 => 48,  108 => 47,  102 => 45,  96 => 43,  94 => 42,  89 => 41,  86 => 40,  83 => 39,  80 => 38,  65 => 37,  56 => 35,  51 => 29,  48 => 27,  46 => 26,  44 => 25,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/navigation/menu--main.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/navigation/menu--main.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["import" => 25, "macro" => 37, "if" => 39, "for" => 47, "set" => 49];
        static $filters = ["escape" => 29];
        static $functions = ["attach_library" => 29, "link" => 72];

        try {
            $this->sandbox->checkSecurity(
                ['import', 'macro', 'if', 'for', 'set'],
                ['escape'],
                ['attach_library', 'link'],
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
