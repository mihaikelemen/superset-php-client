<?php

declare(strict_types=1);

namespace Superset\Tests\Unit\Dto;

use Superset\Config\SerializerConfig;
use Superset\Dto\Dashboard;
use Superset\Serializer\SerializerService;
use Superset\Tests\BaseTestCase;

/**
 * @group unit
 * @group dto
 *
 * @covers \Superset\Dto\Dashboard
 */
final class DashboardTest extends BaseTestCase
{
    private SerializerService $serializer;

    protected function setUp(): void
    {
        $this->serializer = SerializerService::create(new SerializerConfig());
    }

    public function testCanBeInstantiated(): void
    {
        $dashboard = new Dashboard(id: 123);

        $this->assertInstanceOf(Dashboard::class, $dashboard);
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(Dashboard::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testConstructorWithAllParameters(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-01T10:00:00Z');
        $updatedAt = new \DateTimeImmutable('2024-01-02T10:00:00Z');

        $dashboard = new Dashboard(
            id: 123,
            title: 'Test Dashboard',
            slug: 'test-dashboard',
            url: 'https://superset.example.com/dashboard',
            isPublished: true,
            css: '.custom { color: red; }',
            position: '{"key": "value"}',
            metadata: '{"description": "test"}',
            owners: [['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe']],
            createdBy: ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith'],
            updatedBy: ['id' => 3, 'first_name' => 'Bob', 'last_name' => 'Johnson'],
            updatedAt: $updatedAt,
            tags: [
                ['id' => 1, 'name' => 'tag1', 'type' => 1],
                ['id' => 2, 'name' => 'tag2', 'type' => 1],
            ],
            roles: [['id' => 1, 'name' => 'Admin']],
            thumbnail: '/thumbnail.png',
            isManagedExternally: false
        );

        $this->assertSame(123, $dashboard->id);
        $this->assertSame('Test Dashboard', $dashboard->title);
        $this->assertSame('test-dashboard', $dashboard->slug);
        $this->assertSame('https://superset.example.com/dashboard', $dashboard->url);
        $this->assertTrue($dashboard->isPublished);
        $this->assertSame('.custom { color: red; }', $dashboard->css);
        $this->assertSame('{"key": "value"}', $dashboard->position);
        $this->assertSame('{"description": "test"}', $dashboard->metadata);
        $this->assertCount(1, $dashboard->owners);
        $this->assertSame(2, $dashboard->createdBy['id']);
        $this->assertSame(3, $dashboard->updatedBy['id']);
        $this->assertSame($updatedAt, $dashboard->updatedAt);
        $this->assertCount(2, $dashboard->tags);
        $this->assertCount(1, $dashboard->roles);
        $this->assertSame('/thumbnail.png', $dashboard->thumbnail);
        $this->assertFalse($dashboard->isManagedExternally);
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $dashboard = new Dashboard(id: 456);

        $this->assertSame(456, $dashboard->id);
        $this->assertNull($dashboard->title);
        $this->assertNull($dashboard->slug);
        $this->assertNull($dashboard->url);
        $this->assertNull($dashboard->isPublished);
        $this->assertNull($dashboard->css);
        $this->assertNull($dashboard->position);
        $this->assertNull($dashboard->metadata);
        $this->assertSame([], $dashboard->owners);
        $this->assertNull($dashboard->createdBy);
        $this->assertNull($dashboard->updatedBy);
        $this->assertNull($dashboard->updatedAt);
        $this->assertSame([], $dashboard->tags);
        $this->assertSame([], $dashboard->roles);
        $this->assertNull($dashboard->thumbnail);
        $this->assertNull($dashboard->isManagedExternally);
    }

    public function testHydrateFromCompleteApiResponse(): void
    {
        $data = [
            'id' => 789,
            'dashboard_title' => 'Production Dashboard',
            'slug' => 'production-dashboard',
            'url' => '/superset/dashboard/production/',
            'published' => true,
            'css' => '.dashboard { background: white; }',
            'position_json' => '{"GRID_ID": {"children": []}}',
            'json_metadata' => '{"timed_refresh_immune_slices": []}',
            'owners' => [
                ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Smith'],
                ['id' => 2, 'first_name' => 'Bob', 'last_name' => 'Jones'],
            ],
            'created_by' => ['id' => 3, 'first_name' => 'Charlie', 'last_name' => 'Brown'],
            'changed_by' => ['id' => 4, 'first_name' => 'Diana', 'last_name' => 'Prince'],
            'changed_on_utc' => '2024-01-15T14:30:00+00:00',
            'tags' => [
                ['id' => 10, 'name' => 'production', 'type' => 1],
                ['id' => 11, 'name' => 'owner:1', 'type' => 3],
            ],
            'roles' => [
                ['id' => 5, 'name' => 'Admin'],
                ['id' => 6, 'name' => 'Public'],
            ],
            'thumbnail_url' => '/api/v1/dashboard/789/thumbnail/xyz789/',
            'is_managed_externally' => true,
        ];

        $dashboard = $this->serializer->hydrate($data, Dashboard::class);

        $this->assertInstanceOf(Dashboard::class, $dashboard);
        $this->assertSame(789, $dashboard->id);
        $this->assertSame('Production Dashboard', $dashboard->title);
        $this->assertSame('production-dashboard', $dashboard->slug);
        $this->assertSame('/superset/dashboard/production/', $dashboard->url);
        $this->assertTrue($dashboard->isPublished);
        $this->assertSame('.dashboard { background: white; }', $dashboard->css);
        $this->assertSame('{"GRID_ID": {"children": []}}', $dashboard->position);
        $this->assertSame('{"timed_refresh_immune_slices": []}', $dashboard->metadata);
        $this->assertCount(2, $dashboard->owners);
        $this->assertSame('Charlie', $dashboard->createdBy['first_name']);
        $this->assertSame('Diana', $dashboard->updatedBy['first_name']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dashboard->updatedAt);
        $this->assertCount(2, $dashboard->tags);
        $this->assertCount(2, $dashboard->roles);
        $this->assertSame('/api/v1/dashboard/789/thumbnail/xyz789/', $dashboard->thumbnail);
        $this->assertTrue($dashboard->isManagedExternally);
    }

    public function testHydrateFromMinimalApiResponse(): void
    {
        $data = [
            'id' => 999,
            'dashboard_title' => 'Minimal Dashboard',
        ];

        $dashboard = $this->serializer->hydrate($data, Dashboard::class);

        $this->assertInstanceOf(Dashboard::class, $dashboard);
        $this->assertSame(999, $dashboard->id);
        $this->assertSame('Minimal Dashboard', $dashboard->title);
        $this->assertNull($dashboard->slug);
        $this->assertNull($dashboard->url);
        $this->assertNull($dashboard->isPublished);
        $this->assertSame([], $dashboard->owners);
        $this->assertNull($dashboard->createdBy);
        $this->assertNull($dashboard->updatedBy);
        $this->assertNull($dashboard->updatedAt);
        $this->assertSame([], $dashboard->tags);
    }

    public function testHydrateFromRealSupersetApiResponse(): void
    {
        $data = [
            'id' => 134,
            'dashboard_title' => 'EN - Profiles',
            'slug' => 'profiles',
            'url' => '/superset/dashboard/profiles/',
            'published' => true,
            'css' => '',
            'owners' => [
                [
                    'first_name' => 'Clémentine',
                    'id' => 3,
                    'last_name' => 'BLANCHON',
                ],
            ],
            'created_by' => [
                'first_name' => 'Joel',
                'id' => 134,
                'last_name' => 'Gomes',
            ],
            'changed_by' => [
                'first_name' => 'Mihai',
                'id' => 137,
                'last_name' => 'kelemen',
            ],
            'changed_on_utc' => '2025-10-21T11:38:12.413017+00:00',
            'tags' => [
                ['id' => 2, 'name' => 'owner:3', 'type' => 3],
                ['id' => 14, 'name' => 'user', 'type' => 1],
            ],
            'roles' => [
                ['id' => 2, 'name' => 'Public'],
            ],
            'thumbnail_url' => '/api/v1/dashboard/134/thumbnail/ea1c8609ffafb23d418c2b165b8a6d76/',
        ];

        $dashboard = $this->serializer->hydrate($data, Dashboard::class);

        $this->assertInstanceOf(Dashboard::class, $dashboard);
        $this->assertSame(134, $dashboard->id);
        $this->assertSame('EN - Profiles', $dashboard->title);
        $this->assertSame('profiles', $dashboard->slug);
        $this->assertSame('/superset/dashboard/profiles/', $dashboard->url);
        $this->assertTrue($dashboard->isPublished);
        $this->assertSame('', $dashboard->css);
        $this->assertCount(1, $dashboard->owners);
        $this->assertSame('Joel', $dashboard->createdBy['first_name']);
        $this->assertSame('Mihai', $dashboard->updatedBy['first_name']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dashboard->updatedAt);
        $this->assertCount(2, $dashboard->tags);
        $this->assertCount(1, $dashboard->roles);
    }

    public function testDehydrateToArray(): void
    {
        $updatedAt = new \DateTimeImmutable('2024-01-02T10:00:00Z');

        $dashboard = new Dashboard(
            id: 555,
            title: 'Dehydrate Test',
            slug: 'dehydrate-test',
            isPublished: true,
            updatedAt: $updatedAt,
            tags: [['id' => 1, 'name' => 'test-tag', 'type' => 1]]
        );

        $normalized = $this->serializer->dehydrate($dashboard);

        $this->assertIsArray($normalized);
        $this->assertSame(555, $normalized['id']);
        $this->assertSame('Dehydrate Test', $normalized['dashboard_title']);
        $this->assertSame('dehydrate-test', $normalized['slug']);
        $this->assertTrue($normalized['published']);
        $this->assertArrayHasKey('changed_on_utc', $normalized);
        $this->assertIsArray($normalized['tags']);
        $this->assertSame('test-tag', $normalized['tags'][0]['name']);
    }

    public function testConstructorParametersArePublic(): void
    {
        $reflection = new \ReflectionClass(Dashboard::class);

        foreach (['id', 'title', 'slug', 'url', 'isPublished', 'css', 'position', 'metadata', 'owners', 'createdBy', 'updatedBy', 'updatedAt', 'tags', 'roles', 'thumbnail', 'isManagedExternally'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue($property->isPublic());
        }
    }

    public function testConstructorParameters(): void
    {
        $reflection = new \ReflectionClass(Dashboard::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(16, $parameters);

        $this->assertSame('id', $parameters[0]->getName());
        $this->assertSame('title', $parameters[1]->getName());
        $this->assertSame('slug', $parameters[2]->getName());
        $this->assertSame('url', $parameters[3]->getName());
        $this->assertSame('isPublished', $parameters[4]->getName());
        $this->assertSame('css', $parameters[5]->getName());
        $this->assertSame('position', $parameters[6]->getName());
        $this->assertSame('metadata', $parameters[7]->getName());
        $this->assertSame('owners', $parameters[8]->getName());
        $this->assertSame('createdBy', $parameters[9]->getName());
        $this->assertSame('updatedBy', $parameters[10]->getName());
        $this->assertSame('updatedAt', $parameters[11]->getName());
        $this->assertSame('tags', $parameters[12]->getName());
        $this->assertSame('roles', $parameters[13]->getName());
        $this->assertSame('thumbnail', $parameters[14]->getName());
        $this->assertSame('isManagedExternally', $parameters[15]->getName());
    }
}
