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

/* modules/custom/artigos_relacionados/templates/artigos-relacionados-carousel.html.twig */
class __TwigTemplate_affb1800981e9cc88abaa1c2ec4e38cf extends Template
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
        // line 10
        if ((($tmp = ($context["articles"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 11
            yield "<div class=\"artigos-relacionados\">
  <h2 class=\"artigos-relacionados__title\">";
            // line 12
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Artigos Relacionados"));
            yield "</h2>
  <div class=\"artigos-relacionados__carousel\">
    <button class=\"carousel__nav carousel__nav--prev\" aria-label=\"";
            // line 14
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Anterior"));
            yield "\">
      <span aria-hidden=\"true\">&lsaquo;</span>
    </button>
    <div class=\"carousel__track-container\">
      <div class=\"carousel__track\">
        ";
            // line 19
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["articles"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["article"]) {
                // line 20
                yield "          <div class=\"carousel__slide\">
            ";
                // line 21
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $context["article"], "html", null, true);
                yield "
          </div>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['article'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 24
            yield "      </div>
    </div>
    <button class=\"carousel__nav carousel__nav--next\" aria-label=\"";
            // line 26
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Próximo"));
            yield "\">
      <span aria-hidden=\"true\">&rsaquo;</span>
    </button>
  </div>
  <div class=\"carousel__dots\"></div>
</div>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["articles"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/custom/artigos_relacionados/templates/artigos-relacionados-carousel.html.twig";
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
        return array (  82 => 26,  78 => 24,  69 => 21,  66 => 20,  62 => 19,  54 => 14,  49 => 12,  46 => 11,  44 => 10,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/custom/artigos_relacionados/templates/artigos-relacionados-carousel.html.twig", "/var/www/html/web/modules/custom/artigos_relacionados/templates/artigos-relacionados-carousel.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 10, "for" => 19];
        static $filters = ["t" => 12, "escape" => 21];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'for'],
                ['t', 'escape'],
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
