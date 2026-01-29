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

/* themes/contrib/belgrade/templates/node/node--article--full.html.twig */
class __TwigTemplate_97ec4612f1dbb6b1030959bb74f1d55c extends Template
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
        // line 63
        yield "
";
        // line 64
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("belgrade/page.article"), "html", null, true);
        yield "

";
        // line 66
        $context["classes"] = ["node", ("node--type-" . \Drupal\Component\Utility\Html::getClass(CoreExtension::getAttribute($this->env, $this->source,         // line 68
($context["node"] ?? null), "bundle", [], "any", false, false, true, 68))), (((($tmp =         // line 69
($context["view_mode"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (("node--view-mode-" . \Drupal\Component\Utility\Html::getClass(($context["view_mode"] ?? null)))) : ("")), "container-narrow"];
        // line 72
        yield "
<article";
        // line 73
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 73), "html", null, true);
        yield ">

  ";
        // line 75
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_prefix"] ?? null), "html", null, true);
        yield "
  ";
        // line 76
        if ((($context["label"] ?? null) &&  !($context["page"] ?? null))) {
            // line 77
            yield "    <h2";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_attributes"] ?? null), "html", null, true);
            yield ">
      <a href=\"";
            // line 78
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["url"] ?? null), "html", null, true);
            yield "\" rel=\"bookmark\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["label"] ?? null), "html", null, true);
            yield "</a>
    </h2>
  ";
        }
        // line 81
        yield "  ";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_suffix"] ?? null), "html", null, true);
        yield "


  ";
        // line 84
        if ((($tmp =  !Twig\Extension\CoreExtension::testEmpty($this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_image", [], "any", false, false, true, 84)))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 85
            yield "    <div class=\"node--type-article__image mb-4\">
      ";
            // line 86
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_image", [], "any", false, false, true, 86), "html", null, true);
            yield "
      <time class=\"article-date date-badge\" datetime=\"";
            // line 87
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "created", [], "any", false, false, true, 87), "value", [], "any", false, false, true, 87), "Y-m-d\\TH:i:sP"), "html", null, true);
            yield "\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->env->getFilter('format_date')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "created", [], "any", false, false, true, 87), "value", [], "any", false, false, true, 87), "custom", "l, j F Y - H:i"), "html", null, true);
            yield "\">
        ";
            // line 88
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->env->getFilter('format_date')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "created", [], "any", false, false, true, 88), "value", [], "any", false, false, true, 88), "custom", "M"), "html", null, true);
            yield " <span class=\"day\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "created", [], "any", false, false, true, 88), "value", [], "any", false, false, true, 88), "j"), "html", null, true);
            yield "</span>
      </time>
    </div>
  ";
        }
        // line 92
        yield "

  ";
        // line 94
        if ((($context["display_submitted"] ?? null) && Twig\Extension\CoreExtension::testEmpty($this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_image", [], "any", false, false, true, 94))))) {
            // line 95
            yield "    <footer class=\"byline my-3\">
      ";
            // line 96
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["author_picture"] ?? null), "html", null, true);
            yield "
      <div";
            // line 97
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["author_attributes"] ?? null), "html", null, true);
            yield ">
        ";
            // line 98
            yield t("Submitted by @author_name on @date", ["@author_name" => $this->env->getExtension(\Drupal\Core\Template\TwigExtension::class)->renderVar(($context["author_name"] ?? null)), "@date" => $this->env->getExtension(\Drupal\Core\Template\TwigExtension::class)->renderVar(($context["date"] ?? null)), ]);
            // line 99
            yield "      </div>
    </footer>
  ";
        }
        // line 102
        yield "
  <div";
        // line 103
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content_attributes"] ?? null), "addClass", ["node__content"], "method", false, false, true, 103), "html", null, true);
        yield ">
    ";
        // line 104
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->withoutFilter(($context["content"] ?? null), "field_image"), "html", null, true);
        yield "
  </div>

</article>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["node", "view_mode", "attributes", "title_prefix", "label", "page", "title_attributes", "url", "title_suffix", "content", "display_submitted", "author_picture", "author_attributes", "author_name", "date", "content_attributes"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/belgrade/templates/node/node--article--full.html.twig";
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
        return array (  145 => 104,  141 => 103,  138 => 102,  133 => 99,  131 => 98,  127 => 97,  123 => 96,  120 => 95,  118 => 94,  114 => 92,  105 => 88,  99 => 87,  95 => 86,  92 => 85,  90 => 84,  83 => 81,  75 => 78,  70 => 77,  68 => 76,  64 => 75,  59 => 73,  56 => 72,  54 => 69,  53 => 68,  52 => 66,  47 => 64,  44 => 63,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/belgrade/templates/node/node--article--full.html.twig", "/var/www/html/web/themes/contrib/belgrade/templates/node/node--article--full.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 66, "if" => 76, "trans" => 98];
        static $filters = ["escape" => 64, "clean_class" => 68, "render" => 84, "date" => 87, "format_date" => 87, "without" => 104];
        static $functions = ["attach_library" => 64];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'trans'],
                ['escape', 'clean_class', 'render', 'date', 'format_date', 'without'],
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
