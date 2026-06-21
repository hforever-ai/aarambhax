<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    /** @dataProvider publicRoutes */
    public function test_public_pages_load_with_200(string $path, string $expectedSnippet): void
    {
        $response = $this->get($path);
        $response->assertStatus(200);
        $response->assertSee($expectedSnippet, false);
    }

    public static function publicRoutes(): array
    {
        return [
            'homepage'        => ['/',                  'Aarambha'],
            'verifier page'   => ['/verifier',          'Citation Verifier'],
            'pricing'         => ['/pricing',           'How Aarambhax compares'],
            'blog index'      => ['/blog',              'Practical drafting reference'],
            'faq'             => ['/faq',               'Frequently Asked Questions'],
            'about'           => ['/about',             'About Aarambhax Legal'],
            'contact'         => ['/contact',           'Contact'],
            'privacy'         => ['/privacy',           'Privacy Policy'],
            'terms'           => ['/terms',             'Terms of Service'],
            'accessibility'   => ['/accessibility',     'Accessibility Statement'],
            'sample drafts'   => ['/sample-drafts',     'See what Aarambhax actually produces'],
            'login'           => ['/login',             'Log in'],
            'register'        => ['/register',          'Create account'],
        ];
    }

    public function test_sitemap_index_returns_xml(): void
    {
        $r = $this->get('/sitemap.xml');
        $r->assertStatus(200);
        $r->assertHeader('Content-Type', 'application/xml');
        $r->assertSee('<sitemapindex', false);
    }

    public function test_robots_txt_returns_text(): void
    {
        $r = $this->get('/robots.txt');
        $r->assertStatus(200);
        $r->assertSee('Sitemap:', false);
    }

    public function test_app_routes_redirect_when_unauthenticated(): void
    {
        foreach (['/app', '/app/drafts', '/app/cases', '/app/clients', '/app/hearings', '/app/settings/profile', '/app/settings/telegram'] as $path) {
            $r = $this->get($path);
            $this->assertContains($r->status(), [302, 401], "Expected redirect/401 for $path, got {$r->status()}");
        }
    }
}
