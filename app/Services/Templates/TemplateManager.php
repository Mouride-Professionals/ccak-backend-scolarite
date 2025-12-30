<?php

namespace App\Services\Templates;

use App\Contracts\Templates\DocumentTemplateInterface;
use App\Enums\DocumentType;
use InvalidArgumentException;

class TemplateManager
{
    /** @var array<string, DocumentTemplateInterface> */
    protected array $templates = [];

    public function register(DocumentType $type, DocumentTemplateInterface $template): void
    {
        $this->templates[$type->value] = $template;
    }

    public function get(DocumentType $type): DocumentTemplateInterface
    {
        if (!isset($this->templates[$type->value])) {
            throw new InvalidArgumentException("No template registered for type: $type->value");
        }

        return $this->templates[$type->value];
    }

    public function has(DocumentType $type): bool
    {
        return isset($this->templates[$type->value]);
    }
}
