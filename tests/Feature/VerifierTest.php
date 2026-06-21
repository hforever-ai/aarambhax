<?php

namespace Tests\Feature;

use Tests\TestCase;

class VerifierTest extends TestCase
{
    public function test_verifier_page_loads(): void
    {
        $this->get('/verifier')->assertStatus(200)->assertSee('Citation Verifier');
    }

    public function test_verifier_classifies_known_statute_as_verified(): void
    {
        $r = $this->post('/verifier', [
            'draft_text' => 'The applicant prays for anticipatory bail under BNSS §482. Cited Arnesh Kumar v. State of Bihar, (2014) 8 SCC 273.',
        ]);
        $r->assertStatus(200);
        $r->assertSee('✓ Verified');
        $r->assertSee('BNSS');
    }

    public function test_verifier_marks_unknown_statute_as_suspect(): void
    {
        $r = $this->post('/verifier', [
            'draft_text' => 'Some draft citing the FAKE-CODE §111 which does not exist. Also cites BNSS §483 for context. Padding to meet minimum length requirement.',
        ]);
        $r->assertStatus(200);
        $r->assertSee('Suspect');
    }

    public function test_verifier_requires_minimum_length(): void
    {
        $r = $this->post('/verifier', ['draft_text' => 'short']);
        $r->assertSessionHasErrors('draft_text');
    }
}
