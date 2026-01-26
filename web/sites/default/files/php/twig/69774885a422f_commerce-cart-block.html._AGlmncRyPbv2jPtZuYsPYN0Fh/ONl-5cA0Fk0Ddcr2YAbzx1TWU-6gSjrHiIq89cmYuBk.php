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

/* themes/contrib/belgrade/templates/commerce/commerce-cart-block.html.twig */
class __TwigTemplate_7c12fb1c64da03c522448d9cac1bbdc3 extends Template
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
        // line 1
        $macros["svg"] = $this->macros["svg"] = $this->load("@belgrade/macros.twig", 1)->unwrap();
        // line 2
        yield "
<div";
        // line 3
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", ["cart-block"], "method", false, false, true, 3), "html", null, true);
        yield ">
  <div class=\"cart-block__menu-item\">
    <a
      class=\"cart-block__trigger\"
      href=\"";
        // line 7
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["url"] ?? null), "html", null, true);
        yield "\"
      ";
        // line 8
        if ((($tmp = ($context["content"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 9
            yield "        data-bs-toggle=\"collapse\"
        data-bs-target=\"#cart-dropdown\"
        aria-expanded=\"false\"
        aria-controls=\"cart-dropdown\"
      ";
        }
        // line 14
        yield "    >
      <span class=\"cart-block__icon\">
        ";
        // line 16
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["svg"]->getTemplateForMacro("macro_getIcon", $context, 16, $this->getSourceContext())->macro_getIcon(...[((array_key_exists("cart_icon", $context)) ? (Twig\Extension\CoreExtension::default(($context["cart_icon"] ?? null), "basket")) : ("basket")), 18, 18, "me-1"]));
        yield "
      </span>
      ";
        // line 18
        if ((($tmp = ($context["cart_show_label"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 19
            yield "        <span class=\"cart-block__count\">
          ";
            // line 20
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(($context["count_text"] ?? null));
            yield "
        </span>
      ";
        } else {
            // line 23
            yield "        <span class=\"cart-block__count";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["cart_count_circle"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (" cart-block__count--badge cart-block__count--absolute") : ("")));
            yield "\">
          ";
            // line 24
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["count"] ?? null), "html", null, true);
            yield "
        </span>
      ";
        }
        // line 27
        yield "    </a>
  </div>
  ";
        // line 29
        if ((($tmp = ($context["content"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 30
            yield "  <div id=\"cart-dropdown\" class=\"cart-block__dropdown cart-block__dropdown--";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["cart_dropdown_position"] ?? null), "html", null, true);
            yield " collapse bg-";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["cart_contents_bg"] ?? null), "html", null, true);
            yield " text-";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["cart_contents_text"] ?? null), "html", null, true);
            yield " ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["cart_dropdown_animation"] ?? null), "html", null, true);
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["cart_hover_display"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (" cart-block__dropdown--hover") : ("")));
            yield "\">
    <div class=\"cart-block__dropdown-inner\">
      <div class=\"cart-block__header\">
        <div class=\"cart-block__title\">";
            // line 33
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((array_key_exists("cart_contents_title", $context)) ? (Twig\Extension\CoreExtension::default(($context["cart_contents_title"] ?? null), t("Shopping bag"))) : (t("Shopping bag"))), "html", null, true);
            yield "</div>
        <button
          type=\"button\"
          class=\"cart-block__close\"
          data-bs-toggle=\"collapse\"
          data-bs-target=\"#cart-dropdown\"
          aria-label=\"Close\"
        >
          <span class=\"cart-block__close-icon\">";
            // line 41
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["svg"]->getTemplateForMacro("macro_getIcon", $context, 41, $this->getSourceContext())->macro_getIcon(...["x", 20, 20]));
            yield "</span>
        </button>
      </div>
      <div class=\"cart-block__summary\">
        ";
            // line 45
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["content"] ?? null), "html", null, true);
            yield "
      </div>
      <div class=\"cart-block__actions\">
        ";
            // line 48
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["links"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["link"]) {
                // line 49
                yield "          ";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::merge($context["link"], ["#attributes" => ["class" => ["btn", ("btn-outline-" . ($context["cart_contents_text"] ?? null)), "w-100"]]]), "html", null, true);
                yield "
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['link'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 51
            yield "      </div>
    </div>
  </div>
  ";
        }
        // line 55
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["attributes", "url", "content", "cart_icon", "cart_show_label", "count_text", "cart_count_circle", "count", "cart_dropdown_position", "cart_contents_bg", "cart_contents_text", "cart_dropdown_animation", "cart_hover_display", "cart_contents_title", "links"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/belgrade/templates/commerce/commerce-cart-block.html.twig";
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
        return array (  163 => 55,  157 => 51,  148 => 49,  144 => 48,  138 => 45,  131 => 41,  120 => 33,  106 => 30,  104 => 29,  100 => 27,  94 => 24,  89 => 23,  83 => 20,  80 => 19,  78 => 18,  73 => 16,  69 => 14,  62 => 9,  60 => 8,  56 => 7,  49 => 3,  46 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/commerce/commerce-cart-block.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/commerce/commerce-cart-block.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["import" => 1, "if" => 8, "for" => 48];
        static $filters = ["escape" => 3, "default" => 16, "raw" => 20, "t" => 33, "merge" => 49];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['import', 'if', 'for'],
                ['escape', 'default', 'raw', 't', 'merge'],
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
