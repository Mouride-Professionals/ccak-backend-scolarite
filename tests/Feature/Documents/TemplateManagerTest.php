<?php

namespace Tests\Feature\Documents;

use App\Contracts\Templates\DocumentTemplateInterface;
use App\Enums\DocumentType;
use App\Services\Templates\TemplateManager;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class TemplateManagerTest extends TestCase
{
    private TemplateManager $manager;
    private DocumentTemplateInterface $mockTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new TemplateManager();
        $this->mockTemplate = Mockery::mock(DocumentTemplateInterface::class);
        $this->mockTemplate->shouldReceive('getName')->andReturn('Test Template');
    }

    public function test_can_register_template(): void
    {
        $this->manager->register(DocumentType::TRANSCRIPT, $this->mockTemplate);

        $this->assertTrue($this->manager->has(DocumentType::TRANSCRIPT));
    }

    public function test_can_get_registered_template(): void
    {
        $this->manager->register(DocumentType::TRANSCRIPT, $this->mockTemplate);

        $template = $this->manager->get(DocumentType::TRANSCRIPT);

        $this->assertSame($this->mockTemplate, $template);
    }

    public function test_throws_exception_when_getting_unregistered_template(): void
    {
        $this->manager->register(DocumentType::TRANSCRIPT, $this->mockTemplate);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No template registered for type: ATTESTATION');

        $this->manager->get(DocumentType::ATTESTATION);
    }

    public function test_has_returns_false_for_unregistered_template(): void
    {
        $this->assertFalse($this->manager->has(DocumentType::TRANSCRIPT));
    }

    public function test_can_register_multiple_templates(): void
    {
        $mockTemplate2 = Mockery::mock(DocumentTemplateInterface::class);
        $mockTemplate2->shouldReceive('getName')->andReturn('Second Template');

        $this->manager->register(DocumentType::TRANSCRIPT, $this->mockTemplate);
        $this->manager->register(DocumentType::TRANSCRIPT, $mockTemplate2);

        $this->assertTrue($this->manager->has(DocumentType::TRANSCRIPT));
        $this->assertTrue($this->manager->has(DocumentType::TRANSCRIPT));
    }
}
