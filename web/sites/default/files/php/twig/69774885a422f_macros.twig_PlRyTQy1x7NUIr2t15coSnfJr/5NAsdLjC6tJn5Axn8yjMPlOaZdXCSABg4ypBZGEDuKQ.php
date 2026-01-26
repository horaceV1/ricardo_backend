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

/* @belgrade/macros.twig */
class __TwigTemplate_a8bae036636286e84c7181d5855ffd3f extends Template
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
        // line 2
        yield "
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["icon", "classes", "width", "height", "path", "desc"]);        yield from [];
    }

    // line 3
    public function macro_getIcon($icon = null, $width = null, $height = null, $classes = null, $title = null, $desc = null, $path = null, ...$varargs): string|Markup
    {
        $macros = $this->macros;
        $context = [
            "icon" => $icon,
            "width" => $width,
            "height" => $height,
            "classes" => $classes,
            "title" => $title,
            "desc" => $desc,
            "path" => $path,
            "varargs" => $varargs,
        ] + $this->env->getGlobals();

        $blocks = [];

        return ('' === $tmp = implode('', iterator_to_array((function () use (&$context, $macros, $blocks) {
            // line 4
            yield "
  ";
            // line 5
            $context["title"] = (((($tmp = ($context["title"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (($context["title"] ?? null)) : (Twig\Extension\CoreExtension::capitalize($this->env->getCharset(), ($context["icon"] ?? null))));
            // line 6
            yield "
  ";
            // line 12
            yield "
  <svg class=\"beo beo-";
            // line 13
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["icon"] ?? null), "html", null, true);
            yield " ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["classes"] ?? null), "html", null, true);
            yield "\"
       width=\"";
            // line 14
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((array_key_exists("width", $context)) ? (Twig\Extension\CoreExtension::default(($context["width"] ?? null), 16)) : (16)), "html", null, true);
            yield "\"
       height=\"";
            // line 15
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((array_key_exists("height", $context)) ? (Twig\Extension\CoreExtension::default(($context["height"] ?? null), 16)) : (16)), "html", null, true);
            yield "\"
       fill=\"currentColor\"
       aria-hidden=\"true\"
       viewBox=\"0 0 16 16\"
       role=\"img\">
    <title>";
            // line 20
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title"] ?? null), "html", null, true);
            yield "</title>

  ";
            // line 31
            yield "  ";
            $context["default_path"] = "/themes/contrib/belgrade/images/belgrade-icons.svg";
            // line 32
            yield "  ";
            $context["icon_path"] = ((array_key_exists("path", $context)) ? (Twig\Extension\CoreExtension::default(($context["path"] ?? null), ($context["default_path"] ?? null))) : (($context["default_path"] ?? null)));
            // line 33
            yield "  <use xlink:href=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["icon_path"] ?? null), "html", null, true);
            yield "#";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["icon"] ?? null), "html", null, true);
            yield "\"/>

    ";
            // line 35
            if ((($tmp = ($context["desc"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 36
                yield "      <desc>";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["desc"] ?? null), "html", null, true);
                yield "</desc>
    ";
            }
            // line 38
            yield "  </svg>

";
            yield from [];
        })(), false))) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@belgrade/macros.twig";
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
        return array (  125 => 38,  119 => 36,  117 => 35,  109 => 33,  106 => 32,  103 => 31,  98 => 20,  90 => 15,  86 => 14,  80 => 13,  77 => 12,  74 => 6,  72 => 5,  69 => 4,  51 => 3,  44 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@belgrade/macros.twig", "/var/www/html/web/themes/contrib/belgrade/templates/macros.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["macro" => 3, "set" => 5, "if" => 35];
        static $filters = ["capitalize" => 5, "escape" => 13, "default" => 14];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['macro', 'set', 'if'],
                ['capitalize', 'escape', 'default'],
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
