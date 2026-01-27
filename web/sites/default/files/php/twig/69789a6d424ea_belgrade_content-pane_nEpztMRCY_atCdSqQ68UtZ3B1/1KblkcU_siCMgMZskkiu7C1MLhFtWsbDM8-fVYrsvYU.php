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

/* belgrade:content-pane */
class __TwigTemplate_8bdb910de9eca79da4a895f26abd5a2c extends Template
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
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("core/components.belgrade--content-pane"));
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->addAdditionalContext($context, "belgrade:content-pane"));
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->validateProps($context, "belgrade:content-pane"));
        // line 2
        $context["classes"] = ["content-pane", (((($tmp =         // line 4
($context["variant"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (("content-pane--" . ($context["variant"] ?? null))) : ("")), (((($tmp =         // line 5
($context["collapsible"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("content-pane--collapsible") : (""))];
        // line 8
        yield "
";
        // line 9
        $context["content_id"] = ("content-pane-" . Twig\Extension\CoreExtension::random($this->env->getCharset()));
        // line 10
        yield "
<div ";
        // line 11
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 11), "html", null, true);
        yield ">
  ";
        // line 12
        if (((($context["title"] ?? null) || ($context["icon"] ?? null)) || ($context["description"] ?? null))) {
            // line 13
            yield "    <div class=\"content-pane__header\">
      ";
            // line 14
            if ((($context["title"] ?? null) || ($context["icon"] ?? null))) {
                // line 15
                yield "        <div class=\"content-pane__title-row\">
          ";
                // line 16
                if ((($tmp = ($context["icon"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 17
                    yield "            <span class=\"content-pane__icon\" aria-hidden=\"true\">
              ";
                    // line 18
                    $macros["svg"] = $this->macros["svg"] = $this->load("@belgrade/macros.twig", 18)->unwrap();
                    // line 19
                    yield "              ";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["svg"]->getTemplateForMacro("macro_getIcon", $context, 19, $this->getSourceContext())->macro_getIcon(...[($context["icon"] ?? null), 16, 16]));
                    yield "
            </span>
          ";
                }
                // line 22
                yield "          ";
                if ((($tmp = ($context["title"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 23
                    yield "            <h3 class=\"content-pane__title ";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["collapsible"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("content-pane__title--collapsible") : ("")));
                    yield "\" ";
                    if ((($tmp = ($context["collapsible"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield "data-bs-toggle=\"collapse\" href=\"#";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["content_id"] ?? null), "html", null, true);
                        yield "-content\" role=\"button\" aria-expanded=\"";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["collapsed"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("false") : ("true")));
                        yield "\" aria-controls=\"";
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["content_id"] ?? null), "html", null, true);
                        yield "-content\"";
                    }
                    yield ">";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title"] ?? null), "html", null, true);
                    yield "</h3>
          ";
                }
                // line 25
                yield "        </div>
      ";
            }
            // line 27
            yield "      ";
            if ((($tmp = ($context["description"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 28
                yield "        <p class=\"content-pane__description\">";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["description"] ?? null), "html", null, true);
                yield "</p>
      ";
            }
            // line 30
            yield "    </div>
  ";
        }
        // line 32
        yield "
  <div ";
        // line 33
        if ((($tmp = ($context["collapsible"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "id=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["content_id"] ?? null), "html", null, true);
            yield "-content\"";
        }
        yield " class=\"content-pane__content";
        yield (((($tmp = ($context["collapsible"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (" collapse" . (((($tmp = ($context["collapsed"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("") : (" show"))), "html", null, true)) : (""));
        yield "\">
    <div class=\"content-pane__content-inner\">
      ";
        // line 35
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["content"] ?? null), "html", null, true);
        yield "
    </div>
  </div>
</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["variant", "collapsible", "attributes", "title", "icon", "description", "collapsed", "content"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "belgrade:content-pane";
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
        return array (  140 => 35,  129 => 33,  126 => 32,  122 => 30,  116 => 28,  113 => 27,  109 => 25,  91 => 23,  88 => 22,  81 => 19,  79 => 18,  76 => 17,  74 => 16,  71 => 15,  69 => 14,  66 => 13,  64 => 12,  60 => 11,  57 => 10,  55 => 9,  52 => 8,  50 => 5,  49 => 4,  48 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "belgrade:content-pane", "themes/contrib/belgrade/components/content-pane/content-pane.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 2, "if" => 12, "import" => 18];
        static $filters = ["escape" => 11];
        static $functions = ["random" => 9];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'import'],
                ['escape'],
                ['random'],
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
