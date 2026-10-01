<?php
class Robots extends Controller
{
    // /robots.txt (the Router maps it here, see Router.class.php)
    public function showAction()
    {
        $lines = ['User-agent: *'];
        if (Seo::noindex()) {
            $lines[] = 'Disallow: /';      // 'noindex' => true in config/site.php: keep every search engine out
        } else {
            $lines[] = 'Disallow:';
            if (Seo::baseUrl() !== '') {
                $lines[] = 'Sitemap: ' . Seo::baseUrl() . '/sitemap.xml';
            }
        }
        $this->asText(implode("\n", $lines) . "\n");
    }
}
