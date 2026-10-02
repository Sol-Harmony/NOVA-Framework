<?php
class Gallery extends Controller
{
    // picture gallery: shows every picture in /pictures/gallery (sorted by file name). View: src/view/gallery/show.phtml
    // The description of a picture (alt text) is taken from the file name: "our-shop-front.webp" → "our shop front".
    public function showAction()
    {
        $this->title = t('gallery.title');
        $this->description = t('gallery.intro');

        $folder = BASEPATH . '/pictures/gallery';

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
                'alt' => trim(preg_replace('/[-_]+/', ' ', $name)),
            ];
        }
        $this->data['images'] = $images;
    }
}
