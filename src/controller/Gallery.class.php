<?php
class Gallery extends Controller
{
    // picture gallery: shows every picture in /pictures/gallery (sorted by file name). View: src/view/gallery/show.phtml
    // The description of a picture (alt text) is taken from the file name: "our-shop-front.webp" → "our shop front".
    // Better descriptions: 'gallery_alts' => ['our-shop-front.webp' => 'The shop front in spring'] in config/site.php
    public function showAction()
    {
        $this->title = t('gallery.title');
        $this->description = t('gallery.intro');

        $folder = BASEPATH . '/pictures/gallery';
        $alts = Config::get('site.gallery_alts', []);
        $alts = is_array($alts) ? $alts : [];

        $files = is_dir($folder) ? scandir($folder) : [];
        $files = array_filter($files, function ($file) use ($folder) {
            // no dotfiles, only pictures
            return $file[0] !== '.' && is_file($folder . '/' . $file)
                && preg_match('/\.(webp|jpe?g|png|gif|avif)$/i', $file);
        });
        natcasesort($files);

        $images = [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $images[] = [
                'src' => '/pictures/gallery/' . rawurlencode($file),
                'alt' => isset($alts[$file]) && is_string($alts[$file]) ? $alts[$file] : trim(preg_replace('/[-_]+/', ' ', $name)),
            ];
        }
        $this->data['images'] = $images;
    }
}
