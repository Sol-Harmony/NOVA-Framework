<?php
class Sitemap extends Controller
{
    // /sitemap.xml: the pages of the menu plus the legal pages (the Router maps it here)
    public function showAction()
    {
        // a sitemap needs full addresses, and those need 'url' in config/site.php
        if (Seo::baseUrl() === '' || Seo::noindex()) {
            $this->abort(404);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (Seo::pages() as $path) {
            $xml .= '  <url><loc>' . htmlspecialchars(Seo::absolute($path), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        $this->asText($xml, 'application/xml; charset=utf-8');
    }
}
