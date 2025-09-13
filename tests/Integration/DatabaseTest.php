<?php

declare(strict_types=1);

namespace PhpEmbeddings\Tests\Integration;

use PhpEmbeddings\Core\DB;
use PhpEmbeddings\Embedders\BuiltinSmallEmbedder;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private string $testDbPath;
    private DB $db;

    protected function setUp(): void
    {
        $this->testDbPath = sys_get_temp_dir() . '/test_vectors_' . uniqid() . '.sqlite';
        $this->db = DB::open($this->testDbPath);
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            $this->db->close();
        }
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }

    public function testAddAndRetrieveDocument(): void
    {
        $embedder = new BuiltinSmallEmbedder();
        $embedding = $embedder->embed("Test document");
        
        $this->db->addDocument("doc1", "Test document", $embedding, ["category" => "test"]);
        
        $doc = $this->db->getDocument("doc1");
        $this->assertNotNull($doc);
        $this->assertEquals("doc1", $doc['id']);
        $this->assertEquals("Test document", $doc['text']);
        $this->assertEquals(["category" => "test"], $doc['metadata']);
    }

    public function testSearchDocuments(): void
    {
        $embedder = new BuiltinSmallEmbedder();
        
        // Add test documents
        $this->db->addDocument(
            "doc1", 
            "Machine learning is fascinating", 
            $embedder->embed("Machine learning is fascinating"),
            ["category" => "ai"]
        );
        
        $this->db->addDocument(
            "doc2", 
            "Cooking recipes for dinner", 
            $embedder->embed("Cooking recipes for dinner"),
            ["category" => "food"]
        );
        
        // Search
        $results = $this->db->search($embedder->embed("artificial intelligence"), 2);
        
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(2, count($results));
        
        if (!empty($results)) {
            $this->assertArrayHasKey('id', $results[0]);
            $this->assertArrayHasKey('score', $results[0]);
            $this->assertArrayHasKey('text', $results[0]);
        }
    }

    public function testSearchWithFilter(): void
    {
        $embedder = new BuiltinSmallEmbedder();
        
        // Add test documents
        $this->db->addDocument(
            "doc1", 
            "AI and machine learning", 
            $embedder->embed("AI and machine learning"),
            ["category" => "ai", "lang" => "en"]
        );
        
        $this->db->addDocument(
            "doc2", 
            "Intelligence artificielle", 
            $embedder->embed("Intelligence artificielle"),
            ["category" => "ai", "lang" => "fr"]
        );
        
        // Search with filter
        $results = $this->db->search(
            $embedder->embed("artificial intelligence"), 
            10, 
            ["lang" => "en"]
        );
        
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertEquals("en", $result['metadata']['lang']);
        }
    }

    public function testGetAllDocuments(): void
    {
        $embedder = new BuiltinSmallEmbedder();
        
        $this->db->addDocument("doc1", "Document 1", $embedder->embed("Document 1"));
        $this->db->addDocument("doc2", "Document 2", $embedder->embed("Document 2"));
        
        $docs = $this->db->getAllDocuments();
        $this->assertCount(2, $docs);
    }

    public function testGetDocumentCount(): void
    {
        $embedder = new BuiltinSmallEmbedder();
        
        $this->assertEquals(0, $this->db->getDocumentCount());
        
        $this->db->addDocument("doc1", "Document 1", $embedder->embed("Document 1"));
        $this->assertEquals(1, $this->db->getDocumentCount());
        
        $this->db->addDocument("doc2", "Document 2", $embedder->embed("Document 2"));
        $this->assertEquals(2, $this->db->getDocumentCount());
    }

    public function testExportToJsonl(): void
    {
        $embedder = new BuiltinSmallEmbedder();
        $this->db->addDocument("doc1", "Test", $embedder->embed("Test"));
        
        $exportPath = sys_get_temp_dir() . '/export_' . uniqid() . '.jsonl';
        $this->db->exportToJsonl($exportPath);
        
        $this->assertFileExists($exportPath);
        
        $content = file_get_contents($exportPath);
        $this->assertStringContainsString('"id":"doc1"', $content);
        
        unlink($exportPath);
    }
}
